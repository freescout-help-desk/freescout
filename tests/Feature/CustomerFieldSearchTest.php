<?php

namespace Tests\Feature;

use App\Conversation;
use App\Customer;
use App\Folder;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers ARMS-22: extending customer plain-text search (Search > Customers,
 * Search > Conversations, and the shared ajaxSearch lookup used by the
 * ticket sidebar/Change Customer/Merge/Cc-Bcc/New Ticket/advanced search) to
 * also match customer custom field values (e.g. Account Number, ID Card)
 * added by the paid Crm module. Uses an ad hoc customer_customer_field
 * table matching Crm's real migration schema, since Crm isn't installed in
 * this repo.
 *
 * Deliberately does NOT use DatabaseTransactions: creating the ad hoc table
 * is DDL, which implicitly commits any open transaction on MySQL. Instead
 * every row this test creates is tracked and deleted explicitly in
 * tearDown().
 */
class CustomerFieldSearchTest extends TestCase
{
    protected $createdCustomerFieldTable = false;

    protected $mailboxIds = [];
    protected $folderIds = [];
    protected $conversationIds = [];
    protected $customerIds = [];
    protected $userIds = [];
    protected $emailIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/../../Modules/CustomerFieldSearch/Providers/CustomerFieldSearchServiceProvider.php';

        if (!Schema::hasTable('customer_customer_field')) {
            Schema::create('customer_customer_field', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('customer_id');
                $table->unsignedInteger('customer_field_id');
                $table->text('value');
                $table->unique(['customer_id', 'customer_field_id']);
            });
            $this->createdCustomerFieldTable = true;
        }

        (new \Modules\CustomerFieldSearch\Providers\CustomerFieldSearchServiceProvider(app()))->boot();
    }

    protected function tearDown(): void
    {
        \DB::table('customer_customer_field')->whereIn('customer_id', $this->customerIds)->delete();
        \DB::table('emails')->whereIn('id', $this->emailIds)->delete();
        \DB::table('threads')->whereIn('conversation_id', $this->conversationIds)->delete();
        \DB::table('conversations')->whereIn('id', $this->conversationIds)->delete();
        \DB::table('customers')->whereIn('id', $this->customerIds)->delete();
        \DB::table('mailbox_user')->whereIn('user_id', $this->userIds)->delete();
        \DB::table('users')->whereIn('id', $this->userIds)->delete();
        \DB::table('folders')->whereIn('id', $this->folderIds)->delete();
        \DB::table('mailboxes')->whereIn('id', $this->mailboxIds)->delete();

        if ($this->createdCustomerFieldTable) {
            Schema::dropIfExists('customer_customer_field');
        }

        config(['app.limit_user_customer_visibility' => false]);

        parent::tearDown();
    }

    protected function makeMailbox()
    {
        $mailbox = factory(Mailbox::class)->create();
        $this->mailboxIds[] = $mailbox->id;

        return $mailbox;
    }

    protected function makeFolder($mailboxId)
    {
        $folder = factory(Folder::class)->create(['mailbox_id' => $mailboxId]);
        $this->folderIds[] = $folder->id;

        return $folder;
    }

    protected function makeUser($mailboxId = null)
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        $this->userIds[] = $user->id;

        if ($mailboxId) {
            $user->mailboxes()->attach($mailboxId);
        }

        return $user;
    }

    protected function makeCustomer()
    {
        $customer = factory(Customer::class)->create();
        $this->customerIds[] = $customer->id;

        return $customer;
    }

    protected function makeConversation($mailboxId, $folderId, $customerId, $userId)
    {
        $conversation = factory(Conversation::class)->create([
            'mailbox_id'         => $mailboxId,
            'folder_id'          => $folderId,
            'customer_id'        => $customerId,
            'created_by_user_id' => $userId,
        ]);
        $this->conversationIds[] = $conversation->id;

        // Conversation::search() inner-joins threads — a conversation with
        // no thread row at all wouldn't be returned by the query regardless
        // of what matches, so every conversation needs at least one, with
        // content that does NOT itself match the search term (otherwise a
        // test could pass even if the custom-field hook were broken).
        factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'customer_id'     => $customerId,
            'to'              => json_encode(['unrelated@example.com']),
            'body'            => 'unrelated thread content',
        ]);

        return $conversation;
    }

    protected function setCustomerFieldValue($customerId, $fieldId, $value)
    {
        \DB::table('customer_customer_field')->insert([
            'customer_id'       => $customerId,
            'customer_field_id' => $fieldId,
            'value'             => $value,
        ]);
    }

    protected function searchCustomersRequest($q)
    {
        return Request::create('/conversations/search', 'GET', ['q' => $q]);
    }

    /**
     * Core correctness + safety proof together: a customer whose custom
     * field value matches must be found, and a customer with the identical
     * matching value but in a mailbox the searching user can't view must
     * NOT be — proving the new hook fires from inside the correctly-grouped
     * closure rather than bypassing mailbox scoping via a later orWhere.
     */
    public function test_customers_tab_matches_custom_field_value_and_respects_mailbox_scoping()
    {
        config(['app.limit_user_customer_visibility' => true]);

        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);
        $folderB = $this->makeFolder($mailboxB->id);
        $user = $this->makeUser($mailboxA->id);

        $visibleCustomer = $this->makeCustomer();
        $hiddenCustomer = $this->makeCustomer();

        $this->setCustomerFieldValue($visibleCustomer->id, 1, '778899001');
        $this->setCustomerFieldValue($hiddenCustomer->id, 1, '778899001');

        $this->makeConversation($mailboxA->id, $folderA->id, $visibleCustomer->id, $user->id);
        $this->makeConversation($mailboxB->id, $folderB->id, $hiddenCustomer->id, $user->id);

        $controller = new \App\Http\Controllers\ConversationsController();
        $results = $controller->searchCustomers($this->searchCustomersRequest('778899'), $user);

        $ids = collect($results->items())->pluck('id')->all();

        $this->assertContains($visibleCustomer->id, $ids);
        $this->assertNotContains($hiddenCustomer->id, $ids);
    }

    /**
     * searchCustomers() has a second, distinct mailbox-restriction path —
     * an explicit ?f[mailbox]=X filter, taken instead of the
     * $limited_visibility elseif branch when the filter mailbox is one the
     * user can view (see the `if (!empty($filters['mailbox']) &&
     * in_array(...))` branch). It builds its own separate join/where after
     * the same closure our hook fires inside, so it needs its own proof —
     * the safe-hook-placement reasoning doesn't automatically cover a
     * second, differently-shaped AND'd condition without a test.
     */
    public function test_customers_tab_explicit_mailbox_filter_respects_custom_field_match_scoping()
    {
        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);
        $folderB = $this->makeFolder($mailboxB->id);
        $user = $this->makeUser($mailboxA->id);
        $user->mailboxes()->attach($mailboxB->id); // can view both — filter, not visibility, does the restricting here

        $inFilterCustomer = $this->makeCustomer();
        $outOfFilterCustomer = $this->makeCustomer();
        $this->setCustomerFieldValue($inFilterCustomer->id, 1, '334455661');
        $this->setCustomerFieldValue($outOfFilterCustomer->id, 1, '334455661');

        $this->makeConversation($mailboxA->id, $folderA->id, $inFilterCustomer->id, $user->id);
        $this->makeConversation($mailboxB->id, $folderB->id, $outOfFilterCustomer->id, $user->id);

        $request = Request::create('/conversations/search', 'GET', [
            'q' => '334455',
            'f' => ['mailbox' => $mailboxA->id],
        ]);

        $controller = new \App\Http\Controllers\ConversationsController();
        $results = $controller->searchCustomers($request, $user);
        $ids = collect($results->items())->pluck('id')->all();

        $this->assertContains($inFilterCustomer->id, $ids);
        $this->assertNotContains($outOfFilterCustomer->id, $ids);
    }

    /**
     * ARMS-22's explicit requirement: prefix match only. A value that
     * *contains* the term but doesn't *start* with it must not match — this
     * is what makes the index this module's migration adds actually usable.
     */
    public function test_customers_tab_does_not_substring_match()
    {
        $customer = $this->makeCustomer();
        $this->setCustomerFieldValue($customer->id, 1, 'PREFIX-99887766');

        $user = $this->makeUser();
        $controller = new \App\Http\Controllers\ConversationsController();

        $results = $controller->searchCustomers($this->searchCustomersRequest('99887766'), $user);
        $ids = collect($results->items())->pluck('id')->all();
        $this->assertNotContains($customer->id, $ids, 'value containing but not starting with the term must not match');

        $results = $controller->searchCustomers($this->searchCustomersRequest('PREFIX-998'), $user);
        $ids = collect($results->items())->pluck('id')->all();
        $this->assertContains($customer->id, $ids, 'value actually starting with the term must match');
    }

    /**
     * A customer typing a literal % or _ into the search box must not have
     * it treated as a SQL wildcard (would otherwise turn a search for e.g.
     * "50%" into "match anything starting with 50, followed by anything").
     */
    public function test_like_metacharacters_are_escaped()
    {
        $decoy = $this->makeCustomer();
        $this->setCustomerFieldValue($decoy->id, 1, '50XYZ');

        $literalMatch = $this->makeCustomer();
        $this->setCustomerFieldValue($literalMatch->id, 1, '50%');

        $user = $this->makeUser();
        $controller = new \App\Http\Controllers\ConversationsController();

        $results = $controller->searchCustomers($this->searchCustomersRequest('50%'), $user);
        $ids = collect($results->items())->pluck('id')->all();

        $this->assertContains($literalMatch->id, $ids);
        $this->assertNotContains($decoy->id, $ids, '"50%" must not act as a wildcard matching "50XYZ"');
    }

    /**
     * The ticket sidebar / Change Customer / Merge / Cc-Bcc / New Ticket /
     * advanced search lookup (CustomersController::ajaxSearch). A customer
     * with two custom field values that both match the same search term
     * must appear exactly once — proving the whereExists-not-join design
     * choice actually avoids the row-multiplication risk a join would have
     * introduced here (this query, unlike searchCustomers(), doesn't always
     * groupBy('customers.id')).
     */
    public function test_ajax_search_matches_custom_field_without_duplicating_rows()
    {
        $customer = $this->makeCustomer();
        $this->setCustomerFieldValue($customer->id, 1, '445566001');
        $this->setCustomerFieldValue($customer->id, 2, '445566002');

        $user = $this->makeUser();
        $this->actingAs($user);

        $request = Request::create('/customers/ajax-search', 'GET', [
            'q'                => '445566',
            'search_by'        => 'all',
            'use_id'           => 1,
            // No email on this fixture customer — without this, ajaxSearch's
            // inner join to emails would exclude the customer entirely,
            // regardless of the custom-field match.
            'allow_non_emails' => 1,
        ]);

        $controller = new \App\Http\Controllers\CustomersController();
        $response = json_decode($controller->ajaxSearch($request)->getContent(), true);

        $matches = array_filter($response['results'], function ($row) use ($customer) {
            return $row['id'] == $customer->id;
        });

        $this->assertCount(1, $matches);
    }

    /**
     * The hook is gated on search_by == 'all' so intentionally-narrower
     * modes aren't silently broadened to also match custom fields. Name
     * mode here; see the companion phone-mode test below — they exercise
     * different code paths (this one still joins emails, phone mode
     * doesn't), so one doesn't stand in for the other.
     */
    public function test_ajax_search_does_not_broaden_narrower_search_by_modes()
    {
        $customer = $this->makeCustomer();
        $this->setCustomerFieldValue($customer->id, 1, '990011223');

        $user = $this->makeUser();
        $this->actingAs($user);

        $request = Request::create('/customers/ajax-search', 'GET', [
            'q'         => '990011223',
            'search_by' => 'name',
            'use_id'    => 1,
        ]);

        $controller = new \App\Http\Controllers\CustomersController();
        $response = json_decode($controller->ajaxSearch($request)->getContent(), true);

        $ids = array_column($response['results'], 'id');
        $this->assertNotContains($customer->id, $ids);
    }

    /**
     * Companion to the name-mode test above for search_by == 'phone' — the
     * PR description claims phone-only search isn't broadened, but only
     * name mode actually had a test; this closes that gap. Phone mode also
     * doesn't join emails at all ($join_emails stays false), a genuinely
     * different code path from name mode.
     */
    public function test_ajax_search_does_not_broaden_phone_only_search_by_mode()
    {
        $customer = $this->makeCustomer();
        $this->setCustomerFieldValue($customer->id, 1, '112233445');

        $user = $this->makeUser();
        $this->actingAs($user);

        $request = Request::create('/customers/ajax-search', 'GET', [
            'q'         => '112233445',
            'search_by' => 'phone',
            'use_id'    => 1,
        ]);

        $controller = new \App\Http\Controllers\CustomersController();
        $response = json_decode($controller->ajaxSearch($request)->getContent(), true);

        $ids = array_column($response['results'], 'id');
        $this->assertNotContains($customer->id, $ids);
    }

    /**
     * PRE-EXISTING NATIVE BEHAVIOR, confirmed via a raw toSql() probe before
     * writing this test — not introduced by this PR. ajaxSearch()'s
     * exclude_id/exclude_email conditions are added via where() (AND) while
     * the name/phone/custom-field conditions use orWhere() at the same
     * nesting level. SQL's AND-binds-tighter-than-OR precedence means the
     * compiled clause is effectively "(email match AND NOT excluded) OR
     * name-match OR phone-match OR our custom-field match" — NOT "(any
     * match) AND NOT excluded". A customer passed as exclude_id can still
     * be returned via a custom-field match (or via name/phone, pre-existing
     * and unchanged by this PR). This test documents today's actual
     * behavior for our new branch so a future fix to the underlying
     * precedence bug doesn't silently change it without a test noticing —
     * flagged separately for a scope decision, not fixed here (fixing it
     * changes exclude_id/exclude_email behavior for every ajaxSearch caller,
     * not just custom-field matches).
     */
    public function test_ajax_search_exclude_id_does_not_suppress_custom_field_match()
    {
        $customer = $this->makeCustomer();
        $this->setCustomerFieldValue($customer->id, 1, '556677889');

        $user = $this->makeUser();
        $this->actingAs($user);

        $request = Request::create('/customers/ajax-search', 'GET', [
            'q'                => '556677',
            'search_by'        => 'all',
            'use_id'           => 1,
            'allow_non_emails' => 1,
            'exclude_id'       => $customer->id,
        ]);

        $controller = new \App\Http\Controllers\CustomersController();
        $response = json_decode($controller->ajaxSearch($request)->getContent(), true);

        $ids = array_column($response['results'], 'id');

        $this->assertContains($customer->id, $ids, 'documents current (pre-existing) behavior — see docblock above');
    }

    /**
     * Same mailbox-scoping safety proof as the Customers-tab test, but for
     * ajaxSearch()'s own $limited_visibility branch specifically — it has
     * its own separate join/whereIn, added after the closure our hook fires
     * inside, so it needs its own proof the hook can't leak past it.
     */
    public function test_ajax_search_respects_mailbox_scoping_for_custom_field_match()
    {
        config(['app.limit_user_customer_visibility' => true]);

        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);
        $folderB = $this->makeFolder($mailboxB->id);
        $user = $this->makeUser($mailboxA->id);

        $visibleCustomer = $this->makeCustomer();
        $hiddenCustomer = $this->makeCustomer();
        $this->setCustomerFieldValue($visibleCustomer->id, 1, '667788990');
        $this->setCustomerFieldValue($hiddenCustomer->id, 1, '667788990');

        $this->makeConversation($mailboxA->id, $folderA->id, $visibleCustomer->id, $user->id);
        $this->makeConversation($mailboxB->id, $folderB->id, $hiddenCustomer->id, $user->id);

        $this->actingAs($user);

        $request = Request::create('/customers/ajax-search', 'GET', [
            'q'                => '667788',
            'search_by'        => 'all',
            'use_id'           => 1,
            'allow_non_emails' => 1,
        ]);

        $controller = new \App\Http\Controllers\CustomersController();
        $response = json_decode($controller->ajaxSearch($request)->getContent(), true);

        $ids = array_column($response['results'], 'id');

        $this->assertContains($visibleCustomer->id, $ids);
        $this->assertNotContains($hiddenCustomer->id, $ids);
    }

    /**
     * Regression test: ajaxSearch()'s $limited_visibility branch joins
     * conversations to restrict by mailbox, which multiplies result rows
     * for a customer with several conversations across mailboxes the user
     * can view — core has always deduped this with a groupBy('customers.id')
     * inside that branch. Unrelated to custom-field search itself, but this
     * groupBy was accidentally dropped while adding the custom-field hook
     * to this method and needs to keep working regardless of whether a
     * custom field is even involved — this test deliberately doesn't use
     * one, to isolate the two concerns.
     */
    public function test_ajax_search_does_not_duplicate_customer_with_multiple_visible_conversations()
    {
        config(['app.limit_user_customer_visibility' => true]);

        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);
        $folderB = $this->makeFolder($mailboxB->id);

        $user = $this->makeUser();
        $user->mailboxes()->attach([$mailboxA->id, $mailboxB->id]);

        $customer = $this->makeCustomer();
        $this->makeConversation($mailboxA->id, $folderA->id, $customer->id, $user->id);
        $this->makeConversation($mailboxB->id, $folderB->id, $customer->id, $user->id);

        $this->actingAs($user);

        $request = Request::create('/customers/ajax-search', 'GET', [
            'q'         => $customer->first_name,
            'search_by' => 'name',
            'use_id'    => 1,
        ]);

        $controller = new \App\Http\Controllers\CustomersController();
        $response = json_decode($controller->ajaxSearch($request)->getContent(), true);

        $matches = array_filter($response['results'], function ($row) use ($customer) {
            return $row['id'] == $customer->id;
        });

        $this->assertCount(1, $matches, 'a customer with multiple visible conversations must not be duplicated');
    }

    /**
     * Search > Conversations tab. No new core patch was needed for this
     * one — it reuses the existing search.conversations.or_where hook,
     * which already fires from inside the correctly-grouped native-match
     * closure. The seeded thread body deliberately doesn't contain the
     * search term, so a match here can only come from the custom-field
     * hook.
     */
    public function test_conversations_tab_matches_customer_custom_field_value()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $user = $this->makeUser($mailbox->id);

        $customer = $this->makeCustomer();
        $this->setCustomerFieldValue($customer->id, 1, 'IDCARD-5544332');

        $conversation = $this->makeConversation($mailbox->id, $folder->id, $customer->id, $user->id);

        $query = Conversation::search('IDCARD-554', [], $user);
        $ids = $query->pluck('id')->all();

        $this->assertContains($conversation->id, $ids);
    }

    /**
     * Same safety property as the Customers-tab and ajaxSearch tests, for
     * the reused search.conversations.or_where hook: Conversation::search()
     * always restricts to $user->mailboxesIdsCanView() regardless of the
     * app.limit_user_customer_visibility setting, applied before the
     * closure this module's hook fires inside.
     */
    public function test_conversations_tab_respects_mailbox_scoping_for_custom_field_match()
    {
        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);
        $folderB = $this->makeFolder($mailboxB->id);
        $user = $this->makeUser($mailboxA->id);

        $visibleCustomer = $this->makeCustomer();
        $hiddenCustomer = $this->makeCustomer();
        $this->setCustomerFieldValue($visibleCustomer->id, 1, 'IDCARD-9988771');
        $this->setCustomerFieldValue($hiddenCustomer->id, 1, 'IDCARD-9988772');

        $visibleConversation = $this->makeConversation($mailboxA->id, $folderA->id, $visibleCustomer->id, $user->id);
        $hiddenConversation = $this->makeConversation($mailboxB->id, $folderB->id, $hiddenCustomer->id, $user->id);

        $query = Conversation::search('IDCARD-998877', [], $user);
        $ids = $query->pluck('id')->all();

        $this->assertContains($visibleConversation->id, $ids);
        $this->assertNotContains($hiddenConversation->id, $ids);
    }

    /**
     * The migration must be idempotent (safe to run twice) and must
     * actually create a usable index rather than silently no-op.
     */
    public function test_migration_adds_index_idempotently()
    {
        require_once __DIR__.'/../../Modules/CustomerFieldSearch/Database/Migrations/2026_07_18_000001_add_index_to_customer_customer_field_value.php';

        $migration = new \AddIndexToCustomerCustomerFieldValue();
        $migration->up();
        $migration->up();

        $table = \DB::getTablePrefix().'customer_customer_field';

        if (\Helper::isPgSql()) {
            $rows = \DB::select('SELECT 1 FROM pg_indexes WHERE indexname = ?', ['customer_customer_field_value_idx']);
        } else {
            $rows = \DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', ['customer_customer_field_value_idx']);
        }

        $this->assertNotEmpty($rows);

        $migration->down();
        $migration->down();
    }
}
