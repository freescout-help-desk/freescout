<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Covers the activetonewlabel:patch-workflows command, which rewrites
 * Workflows' rule-enabled checkbox label away from the shared "Active"
 * string the conversation-status rename now overrides. Workflows itself
 * isn't installed in this repo (paid, runtime-installed, not git-tracked -
 * see the command's own docblock), so these tests run it against a
 * fixture file mirroring the real view's relevant line, rather than the
 * genuine file.
 */
class PatchWorkflowsActiveLabelTest extends TestCase
{
    protected $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive - load directly and
        // register the provider so the command is available to Artisan.
        require_once __DIR__.'/../../Modules/ActiveToNewLabel/Console/PatchWorkflowsActiveLabel.php';
        require_once __DIR__.'/../../Modules/ActiveToNewLabel/Providers/ActiveToNewLabelServiceProvider.php';

        $this->app->register(\Modules\ActiveToNewLabel\Providers\ActiveToNewLabelServiceProvider::class);

        $this->fixturePath = sys_get_temp_dir().'/update_'.uniqid().'.blade.php';
        File::put($this->fixturePath, $this->fixtureContents());
        config(['activetonewlabel.workflows_patch_target' => $this->fixturePath]);
    }

    protected function tearDown(): void
    {
        File::delete($this->fixturePath);
        File::delete($this->fixturePath.'.bak');

        parent::tearDown();
    }

    protected function fixtureContents()
    {
        return "<div class=\"form-group\">\n"
            ."                        <label for=\"active\" class=\"col-sm-2 control-label\">{{ __('Active') }}</label>\n"
            ."                        <div class=\"col-sm-6\">\n"
            ."                            <input type=\"checkbox\" name=\"active\" id=\"active\" value=\"1\" />\n"
            ."                        </div>\n"
            ."</div>\n";
    }

    public function test_patches_the_enabled_checkbox_label()
    {
        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));

        $content = File::get($this->fixturePath);
        $this->assertStringContainsString("__('Enabled')", $content);
        $this->assertStringNotContainsString("__('Active')", $content);
    }

    public function test_is_idempotent()
    {
        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));
        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));

        $content = File::get($this->fixturePath);
        $this->assertSame(1, substr_count($content, "__('Enabled')"));
    }

    public function test_creates_a_backup_before_patching()
    {
        $original = File::get($this->fixturePath);

        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));

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
        File::put($this->fixturePath, "<div>no matching label here</div>\n");
        $original = File::get($this->fixturePath);

        $this->assertSame(1, \Artisan::call('activetonewlabel:patch-workflows'));

        $this->assertSame($original, File::get($this->fixturePath), 'file must be untouched when the guard fails');
        $this->assertFalse(File::exists($this->fixturePath.'.bak'), 'no backup should be written when nothing is patched');
    }

    public function test_no_ops_cleanly_when_workflows_is_not_installed()
    {
        config(['activetonewlabel.workflows_patch_target' => sys_get_temp_dir().'/does-not-exist-'.uniqid().'.php']);

        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));
    }

    public function test_revert_restores_the_original_label()
    {
        $original = File::get($this->fixturePath);

        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));
        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows', ['--revert' => true]));

        $content = File::get($this->fixturePath);
        $this->assertSame($original, $content);
    }

    /**
     * ORIGINAL/PATCHED are written with plain \n. A file carrying CRLF
     * line endings (e.g. from a Windows-side checkout or editor) must
     * still match after normalization, not be refused by the guard.
     */
    public function test_handles_crlf_line_endings()
    {
        File::put($this->fixturePath, str_replace("\n", "\r\n", $this->fixtureContents()));

        $this->assertSame(0, \Artisan::call('activetonewlabel:patch-workflows'));

        $content = File::get($this->fixturePath);
        $this->assertStringContainsString("__('Enabled')", $content);
    }
}
