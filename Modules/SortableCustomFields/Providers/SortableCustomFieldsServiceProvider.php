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

            $sortBy = request()->input('sorting.sort_by');
            if (is_string($sortBy) && strpos($sortBy, 'custom_') === 0) {
                $requestedSlug = str_replace('custom_', '', $sortBy);
                $order = strtolower((string) (request()->input('sorting.order') ?? 'asc')) === 'desc' ? 'desc' : 'asc';
                // Conversation::getQueryByFolder() always passes $folder here — use its
                // mailbox_id directly rather than guessing from the request, which the
                // rest of this module does (fragile: relies on the URL always carrying
                // an `id`/`mailbox_id` param).
                $mailbox_id = $folder->mailbox_id ?? (request()->mailbox_id ?? request()->id ?? 0);

                $sortField = null;
                if ($mailbox_id) {
                    // distinct('name') alone would compile to SELECT DISTINCT *
                    // (id is always unique, so it never actually dedupes) —
                    // dedup at the collection level instead.
                    $sortField = CustomField::where('mailbox_id', $mailbox_id)
                        ->get()
                        ->unique('name')
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
                    // Table names inside DB::raw() bypass the query builder's own
                    // prefixing, so they need it applied manually (same pattern as
                    // app/Conversation.php's raw CONCAT()/customers queries).
                    $prefix = \DB::getTablePrefix();
                    $joinSql = '(select '.$prefix.'conversation_custom_field.custom_field_id, '.$prefix.'conversation_custom_field.conversation_id, '.$prefix.'conversation_custom_field.value, '.$prefix.'custom_fields.name'
                        .' from '.$prefix.'conversation_custom_field'
                        .' left join '.$prefix.'custom_fields on '.$prefix.'conversation_custom_field.custom_field_id = '.$prefix.'custom_fields.id'
                        .' where '.$prefix.'custom_fields.name = '.$quotedName.') a';

                    $query_conversations = $query_conversations->leftJoin(\DB::raw($joinSql), 'a.conversation_id', '=', 'conversations.id');
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
                    // groupBy('name') does not work in PostgreSQL; distinct('name')
                    // alone would compile to SELECT DISTINCT * (id is always
                    // unique, so it never dedupes) — unique() on the collection
                    // actually works, and works the same on every DB driver.
                    ->get()
                    ->unique('name');
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
                    echo '<col class="conv-'.$slug.'">';
                }
            }
        }, 20, 3);

        \Eventy::addAction('conversations_table.th_before_conv_number', function () {
            $sorting = ['sort_by' => 'date', 'order' => 'asc'];

            $requestSortBy = request()->input('sorting.sort_by');
            if (is_string($requestSortBy)) {
                $sorting['sort_by'] = $requestSortBy;
                // threls fork patch: $sorting['order'] is echoed into a data-order
                // attribute below — normalize to a strict asc/desc enum here so an
                // attacker-controlled value can never break out of the attribute
                // (was reflected unescaped, a stored/reflected XSS via the sort
                // param).
                $sorting['order'] = strtolower((string) (request()->input('sorting.order') ?? '')) === 'desc' ? 'desc' : 'asc';
            }

            $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;

            if ($mailbox_id) {
                $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                    // groupBy('name') does not work in PostgreSQL; distinct('name')
                    // alone would compile to SELECT DISTINCT * (id is always
                    // unique, so it never dedupes) — unique() on the collection
                    // actually works, and works the same on every DB driver.
                    ->get()
                    ->unique('name');
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
                    $label = e(__($custom_field->name));

                    echo '<th class="custom-field-th">';
                    if ($sortable) {
                        $orderAttr = ($sorting['sort_by'] == 'custom_'.$slug) ? $sorting['order'] : 'desc';
                        $arrow = '';
                        if ($sorting['sort_by'] == 'custom_'.$slug) {
                            $arrow = $sorting['order'] == 'asc' ? '↓' : ($sorting['order'] == 'desc' ? '↑' : '');
                        }
                        echo '<span class="conv-col-sort custom-field-tr" data-sort-by="custom_'.$slug.'" data-order="'.$orderAttr.'">';
                        echo $label.' '.$arrow;
                        echo '</span>';
                    } else {
                        echo '<span class="custom-field-tr custom-field-th-static">'.$label.'</span>';
                    }
                    echo '</th>';
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
                    echo '<td class="custom-field-td '.e($this->createCSSClassForCustomField($custom_field)).'">';
                    echo '<a href="'.e($conversation->url()).'" title="'.e(__('View conversation')).'">'.e($custom_field->getAsText()).'</a>';
                    echo '</td>';
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

            // Deliberately no request()->id fallback here (unlike the sort
            // filter/col/th hooks): conversations_table.blade.php is also
            // included from the customer profile and search views, neither of
            // which pass a real $folder — it falls back to a dummy Folder
            // with no mailbox_id there. request()->id in those contexts is
            // some other route param entirely (e.g. the customer's id on
            // /customers/{id}/), not a mailbox id, and could coincidentally
            // match a real mailbox — showing/saving against the wrong
            // mailbox's fields. A genuine mailbox view always sets
            // $folder->mailbox_id, so trusting only that is both simpler and
            // correct.
            $mailbox_id = $folder->mailbox_id ?? null;
            if (!$mailbox_id) {
                return;
            }

            $custom_fields = CustomField::where('mailbox_id', $mailbox_id)
                ->where('show_in_list', true)
                ->get()
                ->unique('name');

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
                            <?php $checkboxId = 'scf-visible-'.(int) $custom_field->id; ?>
                            <li class="scf-columns-row" data-custom_field_id="<?= (int) $custom_field->id ?>" data-slug="<?= $slug ?>">
                                <input type="checkbox" id="<?= $checkboxId ?>" class="scf-visible-toggle magic-checkbox" <?= $visible ? 'checked' : '' ?>>
                                <label for="<?= $checkboxId ?>" class="scf-columns-checkbox"><?= e($custom_field->name) ?></label>
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
