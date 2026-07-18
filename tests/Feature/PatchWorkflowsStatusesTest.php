<?php

namespace Tests\Feature;

use App\Conversation;
use App\Thread;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Covers the onholdstatus:patch-workflows command (ARMS-26), which adds
 * On-Hold to the Workflows module's hardcoded Status condition/action
 * option lists. Workflows itself isn't installed in this repo (paid,
 * runtime-installed, not git-tracked — see the command's own docblock for
 * why it needs a direct file patch rather than an Eventy hook), so these
 * tests run the command against a fixture file that mirrors the real
 * module's two status arrays exactly, rather than the genuine file.
 *
 * The fixture's exact whitespace is what makes the command's occurrence-
 * count guard meaningful to test here — it does NOT prove the real
 * Workflow.php file on the demo server has byte-identical indentation
 * (that was transcribed from a terminal paste, not verified byte-for-byte).
 * The guard is exactly what protects the real file if that transcription
 * is off: a mismatch means 0 occurrences found, and the command refuses to
 * touch anything rather than guessing.
 */
class PatchWorkflowsStatusesTest extends TestCase
{
    protected $fixturePath;
    protected $statuses_backup = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive — load directly and
        // register the provider so the command is available to Artisan.
        require_once __DIR__.'/../../Modules/OnHoldStatus/Console/PatchWorkflowsStatuses.php';
        require_once __DIR__.'/../../Modules/OnHoldStatus/Providers/OnHoldStatusServiceProvider.php';

        // Registering the provider also runs its boot(), which mutates
        // Conversation/Thread's static status registries — those persist
        // across every test in this process (see OnHoldStatusTest, which
        // guards against exactly this), so back them up too.
        $this->statuses_backup = [
            'statuses'        => Conversation::$statuses,
            'status_icons'    => Conversation::$status_icons,
            'status_classes'  => Conversation::$status_classes,
            'status_colors'   => Conversation::$status_colors,
            'thread_statuses' => Thread::$statuses,
        ];

        $this->app->register(\Modules\OnHoldStatus\Providers\OnHoldStatusServiceProvider::class);

        $this->fixturePath = sys_get_temp_dir().'/Workflow_'.uniqid().'.php';
        File::put($this->fixturePath, $this->fixtureContents());
        config(['onholdstatus.workflows_patch_target' => $this->fixturePath]);
    }

    protected function tearDown(): void
    {
        File::delete($this->fixturePath);
        File::delete($this->fixturePath.'.bak');

        Conversation::$statuses = $this->statuses_backup['statuses'];
        Conversation::$status_icons = $this->statuses_backup['status_icons'];
        Conversation::$status_classes = $this->statuses_backup['status_classes'];
        Conversation::$status_colors = $this->statuses_backup['status_colors'];
        Thread::$statuses = $this->statuses_backup['thread_statuses'];

        parent::tearDown();
    }

    protected function fixtureContents()
    {
        $statusValues = "                            Conversation::STATUS_ACTIVE => __('Active'),\n"
            ."                            Conversation::STATUS_PENDING => __('Pending'),\n"
            ."                            Conversation::STATUS_CLOSED => __('Closed'),\n"
            ."                            Conversation::STATUS_SPAM => __('Spam'),\n";

        return "<?php\n\nclass Workflow\n{\n"
            // Condition shape: has 'operators' before, 'triggers' after.
            ."                    'status' => [\n"
            ."                        'title' => __('Status'),\n"
            ."                        'operators' => [\n"
            ."                            'equal' => __('Is equal to'),\n"
            ."                        ],\n"
            ."                        'values' => [\n"
            .$statusValues
            ."                        ],\n"
            ."                        'triggers' => [\n"
            ."                            'conversation.status_changed',\n"
            ."                        ]\n"
            ."                    ],\n"
            // Action shape: 'title' => 'Change Status', no operators/triggers.
            ."                    'status' => [\n"
            ."                        'title' => __('Change Status'),\n"
            ."                        'values' => [\n"
            .$statusValues
            ."                        ]\n"
            ."                    ],\n"
            ."}\n";
    }

    public function test_patches_both_condition_and_action_arrays()
    {
        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows'));

        $content = File::get($this->fixturePath);
        $this->assertSame(2, substr_count($content, 'OnHoldStatusServiceProvider::STATUS_ONHOLD'));
        $this->assertStringContainsString("__('On Hold')", $content);
    }

    public function test_is_idempotent()
    {
        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows'));
        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows'));

        $content = File::get($this->fixturePath);
        $this->assertSame(2, substr_count($content, 'OnHoldStatusServiceProvider::STATUS_ONHOLD'));
    }

    public function test_creates_a_backup_before_patching()
    {
        $original = File::get($this->fixturePath);

        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows'));

        $this->assertTrue(File::exists($this->fixturePath.'.bak'));
        $this->assertSame($original, File::get($this->fixturePath.'.bak'));
    }

    /**
     * The safety guard: if the file doesn't look exactly like what this
     * command expects (Workflows changed shape, or the transcribed
     * whitespace doesn't match), it must refuse to modify anything rather
     * than guess.
     */
    public function test_refuses_to_patch_when_occurrence_count_is_unexpected()
    {
        File::put($this->fixturePath, "<?php\nclass Workflow {}\n");
        $original = File::get($this->fixturePath);

        $this->assertSame(1, \Artisan::call('onholdstatus:patch-workflows'));

        $this->assertSame($original, File::get($this->fixturePath), 'file must be untouched when the guard fails');
        $this->assertFalse(File::exists($this->fixturePath.'.bak'), 'no backup should be written when nothing is patched');
    }

    public function test_no_ops_cleanly_when_workflows_is_not_installed()
    {
        config(['onholdstatus.workflows_patch_target' => sys_get_temp_dir().'/does-not-exist-'.uniqid().'.php']);

        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows'));
    }

    public function test_revert_removes_the_patch()
    {
        $original = File::get($this->fixturePath);

        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows'));
        $this->assertSame(0, \Artisan::call('onholdstatus:patch-workflows', ['--revert' => true]));

        $content = File::get($this->fixturePath);
        $this->assertSame(0, substr_count($content, 'OnHoldStatusServiceProvider::STATUS_ONHOLD'));
        $this->assertSame($original, $content);
    }
}
