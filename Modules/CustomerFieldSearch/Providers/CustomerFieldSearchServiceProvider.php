<?php

namespace Modules\CustomerFieldSearch\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Extends the existing customer-name/email/phone plain-text search (Search >
 * Customers tab, the ticket sidebar's shared ajaxSearch lookup used by
 * Change Customer/Merge/Cc-Bcc/New Ticket/advanced search, and Search >
 * Conversations) to also match against customer custom field values (e.g.
 * Account Number, ID Card) added by the paid Crm module. See README.md for
 * why each hook is safe to add an OR-condition from and why it must use a
 * whereExists() subquery rather than a join.
 */
class CustomerFieldSearchServiceProvider extends ServiceProvider
{
    const TABLE = 'customer_customer_field';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->hooks();
    }

    public function hooks()
    {
        // Search > Customers tab (ConversationsController::searchCustomers).
        // 3 args: Eventy's Filter::fire() truncates to exactly whatever count
        // a listener registers with — $like_op would silently be dropped
        // without it.
        \Eventy::addFilter('search.customers.text_match', function ($query, $q, $like_op) {
            return $this->addCustomFieldMatch($query, $q, 'customers.id', $like_op);
        }, 20, 3);

        // Ticket sidebar / Change Customer / Merge / Cc-Bcc / New Ticket /
        // advanced search's Customer filter (CustomersController::ajaxSearch,
        // shared by all of these). 2 args, no $like_op passed — this call
        // site always uses plain 'like', so we do too.
        \Eventy::addFilter('search.customers.ajax_text_match', function ($query, $q) {
            return $this->addCustomFieldMatch($query, $q, 'customers.id', 'like');
        }, 20, 2);

        // Search > Conversations tab (Conversation::search). Reuses an
        // existing hook already fired from inside the correctly-grouped
        // native-match closure, before mailbox scoping's AND boundary — no
        // new core patch needed for this one. conversations.customer_id is
        // used directly; no join to customers required.
        \Eventy::addFilter('search.conversations.or_where', function ($query, $filters, $q) {
            $like_op = \Helper::isPgSql() ? 'ilike' : 'like';

            return $this->addCustomFieldMatch($query, $q, 'conversations.customer_id', $like_op);
        }, 20, 3);
    }

    /**
     * ORs in "does this customer have a custom field value starting with
     * $q" via a correlated whereExists — never a join, which would multiply
     * result rows per matching field value on every one of these call sites.
     *
     * Prefix-anchored ($q% not %q%) so the index added by this module's
     * migration can actually be used at 100k+ row scale.
     */
    protected function addCustomFieldMatch($query, $q, $customerIdColumn, $like_op)
    {
        if (!is_string($q) || $q === '' || !$this->customerFieldTableExists()) {
            return $query;
        }

        // No manual case-folding here: LIKE/ILIKE already respect the
        // column's collation, and the rest of ajaxSearch()'s own native
        // matching (name/email/phone) doesn't lowercase the search term
        // either — folding it here would actually break matching against a
        // case-sensitive collation, since the column value itself isn't
        // lowered.
        $value = $this->likeEscape($q);

        $query->orWhereExists(function ($sub) use ($customerIdColumn, $value, $like_op) {
            $sub->select(DB::raw(1))
                ->from(self::TABLE)
                ->whereColumn(self::TABLE.'.customer_id', $customerIdColumn)
                ->where(self::TABLE.'.value', $like_op, $value.'%');
        });

        return $query;
    }

    /**
     * Escapes LIKE metacharacters (% _ \) in the raw search term so a
     * customer typing e.g. "50%" or "a_b" can't turn part of their own query
     * into a wildcard.
     */
    protected function likeEscape($value)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * This fires on every search keystroke, so a raw Schema::hasTable() call
     * (an information_schema/pg_catalog query) on every request adds up.
     * Cached with a bounded TTL rather than forever — the table only
     * appears/disappears when the Crm module is installed/uninstalled, a
     * rare event, but a permanent cache could go stale if that happens
     * without an app-level cache clear in between.
     */
    protected function customerFieldTableExists()
    {
        return Cache::remember('customerfieldsearch.table_exists', now()->addMinutes(15), function () {
            return Schema::hasTable(self::TABLE);
        });
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
