<?php

namespace Modules\SortableCustomFields\Providers;

use Modules\CustomFields\Entities\CustomField;
use Modules\SortableCustomFields\Entities\UserColumnPreference;
use Illuminate\Support\ServiceProvider;

define('CF_SORTABLE_MODULE', 'sortablecustomfields');

class SortableCustomFieldsServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->registerRoutes();
        $this->hooks();
    }

    public static function createSlug($str, $delimiter = '_')
    {
        $slug = \Str::slug($str, $delimiter, 'en');
        return $slug;
    }

    protected function registerRoutes()
    {
        \Route::middleware(['web', 'auth'])->post(
            'sortablecustomfields/columns',
            'Modules\SortableCustomFields\Http\Controllers\ColumnPreferencesController@save'
        )->name('sortablecustomfields.columns.save');

        // This fork's RouteCollection only refreshes its name -> route index
        // once, at the end of the app's normal route-loading pass. A module
        // provider's boot() runs after that pass, so route('...') on a route
        // registered here would resolve to nothing without this — the route
        // still works (it's in the collection), only the *name* lookup is
        // stale until refreshed.
        \Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Per-user column preferences for a mailbox, keyed by custom_field_id.
     * Empty (not missing-key) for guests/no mailbox — callers should treat
     * an absent key as "visible and sortable", not this collection itself.
     */
    protected function userPreferences($mailboxId)
    {
        if (!$mailboxId || !auth()->check()) {
            return collect();
        }

        return UserColumnPreference::forUserMailbox(auth()->id(), $mailboxId);
    }

    protected function isVisibleToUser($custom_field, $preferences)
    {
        $pref = $preferences->get($custom_field->id);

        return $pref ? (bool) $pref->visible : true;
    }

    protected function isSortableForUser($custom_field, $preferences)
    {
        $pref = $preferences->get($custom_field->id);

        return $pref ? (bool) $pref->sortable : true;
    }

    public function hooks()
    {
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function ($javascripts) {
            $javascripts[] = \Module::getPublicPath(CF_SORTABLE_MODULE).'/js/module.js';

            return $javascripts;
        });

        \Eventy::addFilter('stylesheets', function ($styles) {
            $styles[] = \Module::getPublicPath(CF_SORTABLE_MODULE).'/css/style.css';

            return $styles;
        });

        // Sort by custom fields
        //
        // threls fork patch: upstream concatenated $_REQUEST['sorting']['sort_by']
        // straight into a DB::Raw() string used as a LIKE pattern — an
        // authenticated agent could break out of the SQL string literal via the
        // conversation list's sort param (SQL injection). It also matched via
        // LIKE against the slugified name, where '_' is a SQL wildcard for "any
        // one character", so a slug could false-positive-match the wrong field.
        // Fixed by resolving the request's slug against this mailbox's real
        // CustomField names first (untrusted input never reaches SQL) and
        // building the join/alias only from that trusted, already-slugged value.
        \Eventy::addFilter('folder.conversations_query', function ($query_conversations, $folder = null) {

            if (isset($_REQUEST['sorting']['sort_by']) && is_string($_REQUEST['sorting']['sort_by']) && strpos($_REQUEST['sorting']['sort_by'], 'custom_') === 0) {
                $requestedSlug = str_replace('custom_', '', $_REQUEST['sorting']['sort_by']);
                $order = strtolower($_REQUEST['sorting']['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                // Conversation::getQueryByFolder() always passes $folder here — use its
                // mailbox_id directly rather than guessing from the request, which the
                // rest of this module does (fragile: relies on the URL always carrying
                // an `id`/`mailbox_id` param).
                $mailbox_id = $folder->mailbox_id ?? (request()->mailbox_id ?? request()->id ?? 0);

                $sortField = null;
                if ($mailbox_id) {
                    $sortField = CustomField::where('mailbox_id', $mailbox_id)
                        ->distinct('name')
                        ->get()
                        ->first(function ($customField) use ($requestedSlug) {
                            return self::createSlug($customField->name, '_') === $requestedSlug;
                        });
                }

                // A user who's turned sorting off for this field (see the
                // Columns control) shouldn't have a stale sort param for it
                // silently keep working server-side.
                if ($sortField && !$this->isSortableForUser($sortField, $this->userPreferences($mailbox_id))) {
                    $sortField = null;
                }

                if ($sortField) {
                    $quotedName = \DB::connection()->getPdo()->quote($sortField->name);
                    $alias = 'sort_'.self::createSlug($sortField->name, '_');

                    $query_conversations = $query_conversations->leftJoin(\DB::raw('(select conversation_custom_field.custom_field_id, conversation_custom_field.conversation_id, conversation_custom_field.value, custom_fields.name from conversation_custom_field left join custom_fields on conversation_custom_field.custom_field_id = custom_fields.id where custom_fields.name = '.$quotedName.') a'), 'a.conversation_id', '=', 'conversations.id');
                    $query_conversations = $query_conversations->selectRaw('conversations.*, a.value as '.$alias);
                    $query_conversations = $query_conversations->orderBy($alias, $order);
                }
            }

            return $query_conversations;

        // 2 args: Eventy's Filter::fire() truncates to exactly this many
        // regardless of how many the caller passes — without it, $folder
        // would always be null here even though getQueryByFolder() passes it.
        }, 20, 2);

        \Eventy::addAction('conversations_table.col_before_conv_number', function ($conversation) {

            $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;

            if ($mailbox_id) {
                $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                    // groupBy('name') does not work in PostgreSQL.
                    ->distinct('name')
                    ->get();
            }

            if (isset($custom_fields) && count($custom_fields)) {
                $preferences = $this->userPreferences($mailbox_id);
                foreach ($custom_fields as $custom_field) {
                    if (!$custom_field->show_in_list) {
                        continue;
                    }
                    if (!$this->isVisibleToUser($custom_field, $preferences)) {
                        continue;
                    }
                    $slug = $this->createSlug($custom_field->name, '_');
                    ob_start()
                        ?>
                    <col class="conv-<?= $slug ?>">
                    <?php
                    $output = ob_get_clean();
                    echo $output;
                }
            }
        }, 20, 3);

        \Eventy::addAction('conversations_table.th_before_conv_number', function () {
            $sorting = ['sort_by' => 'date', 'order' => 'asc'];

            if (isset($_REQUEST['sorting']) && is_string($_REQUEST['sorting']['sort_by'] ?? null)) {
                $sorting['sort_by'] = request()->sorting['sort_by'];
                // threls fork patch: $sorting['order'] is echoed into a data-order
                // attribute below — normalize to a strict asc/desc enum here so an
                // attacker-controlled value can never break out of the attribute
                // (was reflected unescaped, a stored/reflected XSS via the sort
                // param).
                $sorting['order'] = strtolower((string) (request()->sorting['order'] ?? '')) === 'desc' ? 'desc' : 'asc';
            }

            $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;

            if ($mailbox_id) {
                $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                    // groupBy('name') does not work in PostgreSQL.
                    ->distinct('name')
                    ->get();
            }

            if (isset($custom_fields) && count($custom_fields)) {
                $preferences = $this->userPreferences($mailbox_id);
                foreach ($custom_fields as $custom_field) {
                    if (!$custom_field->show_in_list) {
                        continue;
                    }
                    if (!$this->isVisibleToUser($custom_field, $preferences)) {
                        continue;
                    }
                    $slug = $this->createSlug($custom_field->name, '_');
                    $sortable = $this->isSortableForUser($custom_field, $preferences);
                    ob_start()
                        ?>
                    <th class="custom-field-th">
                        <?php if ($sortable): ?>
                        <span class="conv-col-sort custom-field-tr" data-sort-by="custom_<?= $slug ?>" data-order="<?= ($sorting['sort_by'] == 'custom_'.$slug) ? $sorting['order'] : 'desc' ?>">
                            <?= e(__($custom_field->name)) ?>
                            <?= ($sorting['sort_by'] == 'custom_'.$slug && $sorting['order'] == 'asc') ? '↓' : '' ?>
                            <?= ($sorting['sort_by'] == 'custom_'.$slug && $sorting['order'] == 'desc') ? '↑' : '' ?>
                        </span>
                        <?php else: ?>
                        <span class="custom-field-tr custom-field-th-static"><?= e(__($custom_field->name)) ?></span>
                        <?php endif; ?>
                    </th>
                    <?php
                    $output = ob_get_clean();
                    echo $output;
                }
            }
        }, 20, 3);

        \Eventy::addAction('conversations_table.td_before_conv_number', function ($conversation) {
            if (isset($conversation->custom_fields)) {
                $preferences = $this->userPreferences($conversation->mailbox_id);
                foreach ($conversation->custom_fields as $custom_field) {
                    if (!$custom_field->show_in_list) {
                        continue;
                    }
                    if (!$this->isVisibleToUser($custom_field, $preferences)) {
                        continue;
                    }
                    ob_start()
                        ?>
                    <td class="custom-field-td <?= $this->createCSSClassForCustomField($custom_field) ?>">
                    <a href="<?= $conversation->url() ?>" title="<?= __('View conversation') ?>"><?= e($custom_field->getAsText()) ?></a>
                    </td>
                    <?php
                    $output = ob_get_clean();
                    echo $output;
                }
            }
        }, 20, 3);

        \Eventy::addAction('conversations_table.row_class', function ($conversation) {
            if (isset($conversation->custom_fields)) {
                $preferences = $this->userPreferences($conversation->mailbox_id);
                foreach ($conversation->custom_fields as $custom_field) {
                    if (!$custom_field->show_in_list) {
                        continue;
                    }
                    if (!$this->isVisibleToUser($custom_field, $preferences)) {
                        continue;
                    }
                    echo ' ';
                    echo $this->createCSSClassForCustomField($custom_field);
                    echo ' ';
                }
            }
        });

        // threls fork patch call site (conversations_table.blade.php): renders
        // the "Columns" control — which of this mailbox's custom fields show
        // as columns, and which of those are sortable, per agent.
        \Eventy::addAction('conversations_table.toolbar', function ($folder = null) {
            if (!auth()->check()) {
                return;
            }

            $mailbox_id = ($folder->mailbox_id ?? null) ?: (request()->mailbox_id ?? request()->id ?? 0);
            if (!$mailbox_id) {
                return;
            }

            $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                ->where('show_in_list', true)
                ->distinct('name')
                ->get();

            if (!count($custom_fields)) {
                return;
            }

            $preferences = $this->userPreferences($mailbox_id);
            $hiddenCount = 0;
            foreach ($custom_fields as $custom_field) {
                if (!$this->isVisibleToUser($custom_field, $preferences)) {
                    $hiddenCount++;
                }
            }

            ob_start();
            ?>
            <div class="scf-columns-control" data-mailbox_id="<?= (int) $mailbox_id ?>" data-save-url="<?= route('sortablecustomfields.columns.save') ?>">
                <div class="btn-group">
                    <button type="button" class="btn btn-default scf-columns-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?= __('Columns') ?>">
                        <span class="glyphicon glyphicon-list-alt"></span>
                        <?= __('Columns') ?>
                        <?php if ($hiddenCount): ?><span class="badge scf-hidden-badge"><?= (int) $hiddenCount ?></span><?php endif; ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right scf-columns-menu" role="menu">
                        <?php foreach ($custom_fields as $custom_field): ?>
                            <?php
                                $visible = $this->isVisibleToUser($custom_field, $preferences);
                                $sortable = $this->isSortableForUser($custom_field, $preferences);
                                $slug = self::createSlug($custom_field->name, '_');
                            ?>
                            <li class="scf-columns-row" data-custom_field_id="<?= (int) $custom_field->id ?>" data-slug="<?= $slug ?>">
                                <label class="scf-columns-checkbox">
                                    <input type="checkbox" class="scf-visible-toggle magic-checkbox" <?= $visible ? 'checked' : '' ?>>
                                    <span><?= e($custom_field->name) ?></span>
                                </label>
                                <button type="button" class="scf-sortable-toggle<?= $sortable ? ' is-active' : '' ?>" <?= $visible ? '' : 'disabled' ?> aria-pressed="<?= $sortable ? 'true' : 'false' ?>" title="<?= $sortable ? __('Sortable — click to make static') : __('Not sortable — click to allow sorting') ?>">
                                    <span class="glyphicon glyphicon-sort"></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                        <li role="separator" class="divider"></li>
                        <li class="scf-columns-footer">
                            <a href="#" class="scf-reset-columns"><?= __('Reset to default') ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
            $output = ob_get_clean();
            echo $output;

        // 1 arg: conversations_table.blade.php passes $folder ?? null as the
        // one extra argument (see the same Eventy truncation note above).
        }, 20, 1);
    }

    private function createCSSClassForCustomField($custom_field)
    {
        $propName = $this->createSlug($custom_field->name, '-');
        $propValue = $this->createSlug($custom_field->getAsText(), '-');

        return 'cf_'.$propName.'_'.$propValue;
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('sortablecustomfields.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php',
            'sortablecustomfields'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
