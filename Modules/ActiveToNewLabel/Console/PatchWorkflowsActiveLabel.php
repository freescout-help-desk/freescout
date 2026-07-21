<?php

namespace Modules\ActiveToNewLabel\Console;

use Illuminate\Console\Command;

/**
 * Workflows' own "is this rule enabled" checkbox reuses the exact same
 * translation string ("Active") as the conversation status this module
 * relabels to "New" via resources/lang/en.json. Without this patch, that
 * global override would also make Workflows' enabled checkbox read "New" —
 * unrelated to conversation status, and confusing on a screen for
 * configuring automation rules.
 *
 * Workflows is a paid, runtime-installed module and is NOT tracked by this
 * repo's git (.gitignore's blanket /Modules/* rule has no allowlist entry
 * for it, unlike our own modules) — a module update or reinstall silently
 * replaces its files. This command is meant to be re-run on every deploy
 * (idempotent — a no-op if already patched), the same pattern already used
 * for the Workflows Status condition/action list patch.
 */
class PatchWorkflowsActiveLabel extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'activetonewlabel:patch-workflows {--revert : Restore the original "Active" label instead of replacing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Rename Workflows' rule-enabled checkbox label away from the shared \"Active\" string";

    const ORIGINAL = "                        <label for=\"active\" class=\"col-sm-2 control-label\">{{ __('Active') }}</label>\n";

    const PATCHED = "                        <label for=\"active\" class=\"col-sm-2 control-label\">{{ __('Enabled') }}</label>\n";

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
        $content = $this->readFile($path);
        if ($content === null) {
            return 1;
        }

        if (strpos($content, self::PATCHED) !== false) {
            $this->info('Already patched — the rule-enabled checkbox already reads "Enabled".');

            return 0;
        }

        $count = substr_count($content, self::ORIGINAL);
        if ($count !== 1) {
            $this->error(
                "Expected exactly 1 occurrence of the rule-enabled checkbox label in update.blade.php, found {$count}. ".
                'Workflows may have changed shape since this patch was written — refusing to modify the file. '.
                'Check Modules/Workflows/Resources/views/update.blade.php manually and update this command\'s ORIGINAL constant if needed.'
            );

            return 1;
        }

        if (!$this->backup($path, $content)) {
            return 1;
        }

        $patched = str_replace(self::ORIGINAL, self::PATCHED, $content);
        if (!$this->writeFile($path, $patched)) {
            return 1;
        }

        $this->info('Patched: Workflows\' rule-enabled checkbox now reads "Enabled" instead of "Active".');

        return 0;
    }

    protected function revert($path)
    {
        $content = $this->readFile($path);
        if ($content === null) {
            return 1;
        }

        if (strpos($content, self::ORIGINAL) !== false) {
            $this->info('Not currently patched — nothing to revert.');

            return 0;
        }

        $count = substr_count($content, self::PATCHED);
        if ($count !== 1) {
            $this->error(
                "Expected exactly 1 occurrence of the patched label, found {$count}. ".
                'Refusing to modify the file — revert manually if the file has been edited since patching.'
            );

            return 1;
        }

        if (!$this->backup($path, $content)) {
            return 1;
        }

        $reverted = str_replace(self::PATCHED, self::ORIGINAL, $content);
        if (!$this->writeFile($path, $reverted)) {
            return 1;
        }

        $this->info('Reverted: Workflows\' rule-enabled checkbox reads "Active" again.');

        return 0;
    }

    /**
     * Reads the file and normalizes CRLF to LF, since ORIGINAL/PATCHED are
     * written with plain \n — without this, a file that happens to carry
     * Windows line endings would never match, and the occurrence-count
     * guard would refuse it as if Workflows had genuinely changed shape.
     * Returns null (after printing an error) on a read failure, rather than
     * letting `false` reach strpos()/substr_count() — both throw a
     * TypeError on `false` under PHP 8.
     */
    protected function readFile($path)
    {
        // @-suppressed: a read failure is handled explicitly below via the
        // false return, not via PHP's warning (which this app's error
        // handler escalates to a thrown ErrorException).
        $content = @file_get_contents($path);
        if ($content === false) {
            $this->error("Failed to read {$path}.");

            return null;
        }

        return str_replace("\r\n", "\n", $content);
    }

    protected function writeFile($path, $content)
    {
        if (@file_put_contents($path, $content) === false) {
            $this->error("Failed to write {$path} — check permissions and disk space.");

            return false;
        }

        return true;
    }

    protected function backup($path, $content)
    {
        return $this->writeFile($path.'.bak', $content);
    }

    /**
     * Overridable so tests can point this at a fixture file instead of the
     * real (not-installed-in-this-repo) Workflows module.
     */
    protected function targetPath()
    {
        return config('activetonewlabel.workflows_patch_target') ?: base_path('Modules/Workflows/Resources/views/update.blade.php');
    }
}
