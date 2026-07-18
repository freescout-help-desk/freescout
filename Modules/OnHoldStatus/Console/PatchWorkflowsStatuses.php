<?php

namespace Modules\OnHoldStatus\Console;

use Illuminate\Console\Command;

/**
 * Makes On-Hold selectable in the Workflows module's "Status" condition and
 * "Change Status" action (ARMS-26). Workflows hardcodes the four core
 * statuses as PHP array literals and exposes no Eventy hook to extend them
 * — unlike core, which this fork already patched twice for exactly this
 * problem (conversation.status_name, conversation.open_statuses, ARMS-12).
 *
 * Workflows is a paid, runtime-installed module and is NOT tracked by this
 * repo's git (.gitignore's blanket /Modules/* rule has no allowlist entry
 * for it, unlike our own modules) — a module update or reinstall silently
 * replaces its files. This command is meant to be re-run on every deploy
 * (idempotent — a no-op if already patched) so the patch survives that,
 * rather than relying on someone remembering to hand-edit the file again
 * after every Workflows update.
 *
 * Both the condition's and the action's status list happen to end in the
 * exact same four lines (down to indentation), so one string replacement
 * covers both — see the occurrence-count guard below.
 */
class PatchWorkflowsStatuses extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'onholdstatus:patch-workflows {--revert : Remove the On-Hold entry instead of adding it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Add On-Hold to the Workflows module's Status condition/action option lists";

    const MARKER = 'OnHoldStatusServiceProvider::STATUS_ONHOLD';

    const ANCHOR = "                            Conversation::STATUS_ACTIVE => __('Active'),\n"
        ."                            Conversation::STATUS_PENDING => __('Pending'),\n"
        ."                            Conversation::STATUS_CLOSED => __('Closed'),\n"
        ."                            Conversation::STATUS_SPAM => __('Spam'),\n";

    const INSERTED_LINE = "                            \\Modules\\OnHoldStatus\\Providers\\OnHoldStatusServiceProvider::STATUS_ONHOLD => __('On Hold'),\n";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->targetPath();

        if (!file_exists($path)) {
            $this->line('Workflows module not installed — nothing to patch.');

            return 0;
        }

        return $this->option('revert') ? $this->revert($path) : $this->patch($path);
    }

    protected function patch($path)
    {
        $content = file_get_contents($path);

        if (strpos($content, self::MARKER) !== false) {
            $this->info('Already patched — On Hold is already selectable in Workflows.');

            return 0;
        }

        $count = substr_count($content, self::ANCHOR);
        if ($count !== 2) {
            $this->error(
                "Expected exactly 2 occurrences of the status array in Workflow.php (the condition and the action), found {$count}. ".
                'Workflows may have changed shape since this patch was written — refusing to modify the file. '.
                'Check Modules/Workflows/Entities/Workflow.php manually and update PatchWorkflowsStatuses::ANCHOR if needed.'
            );

            return 1;
        }

        $this->backup($path, $content);

        $patched = str_replace(self::ANCHOR, self::ANCHOR.self::INSERTED_LINE, $content);
        file_put_contents($path, $patched);

        $this->info('Patched Workflows: On Hold is now selectable as a Status condition and a Change Status action.');

        return 0;
    }

    protected function revert($path)
    {
        $content = file_get_contents($path);

        if (strpos($content, self::MARKER) === false) {
            $this->info('Not currently patched — nothing to revert.');

            return 0;
        }

        $needle = self::ANCHOR.self::INSERTED_LINE;
        $count = substr_count($content, $needle);
        if ($count !== 2) {
            $this->error(
                "Expected exactly 2 occurrences of the patched status array, found {$count}. ".
                'Refusing to modify the file — revert manually if the file has been edited since patching.'
            );

            return 1;
        }

        $this->backup($path, $content);

        $reverted = str_replace($needle, self::ANCHOR, $content);
        file_put_contents($path, $reverted);

        $this->info('Reverted: On Hold removed from Workflows condition/action option lists.');

        return 0;
    }

    protected function backup($path, $content)
    {
        file_put_contents($path.'.bak', $content);
    }

    /**
     * Overridable so tests can point this at a fixture file instead of the
     * real (not-installed-in-this-repo) Workflows module.
     */
    protected function targetPath()
    {
        return config('onholdstatus.workflows_patch_target') ?: base_path('Modules/Workflows/Entities/Workflow.php');
    }
}
