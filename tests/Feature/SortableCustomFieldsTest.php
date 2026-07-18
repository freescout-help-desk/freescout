<?php

namespace Tests\Feature;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\User;
use Illuminate\Support\Facades\Schema;
use Modules\CustomFields\Entities\CustomField;
use Modules\SortableCustomFields\Entities\UserColumnPreference;
use Tests\TestCase;

/**
 * Covers the DB-backed half of the SortableCustomFields module (ARMS-33 and
 * the follow-up per-agent Columns control): the folder.conversations_query
 * sort filter (the SQL-injection fix), th_before_conv_number's
 * order-attribute normalization (the reflected-XSS fix), and the
 * visible/sortable column preferences (storage, the save endpoint, and the
 * rendering gates that respect them). Needs a CustomField model and its two
 * tables, which belong to the paid Custom Fields module and aren't installed
 * in this repo — see tests/Fixtures/CustomFieldFixture.php.
 *
 * Deliberately does NOT use DatabaseTransactions: creating the ad hoc
 * custom_fields/conversation_custom_field tables is DDL, which implicitly
 * commits any open transaction on MySQL. Instead every row this test
 * creates is tracked and deleted explicitly in tearDown().
 */
class SortableCustomFieldsTest extends TestCase
{
    protected $createdCustomFieldsTable = false;
    protected $createdConversationCustomFieldTable = false;
    protected $createdUserColumnsTable = false;

    protected $mailboxIds = [];
    protected $folderIds = [];
    protected $conversationIds = [];
    protected $customFieldIds = [];
    protected $userIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/../../Modules/SortableCustomFields/Providers/SortableCustomFieldsServiceProvider.php';
        require_once __DIR__.'/../../Modules/SortableCustomFields/Entities/UserColumnPreference.php';
        require_once __DIR__.'/../../Modules/SortableCustomFields/Http/Controllers/ColumnPreferencesController.php';

        if (!class_exists(CustomField::class)) {
            require_once __DIR__.'/../Fixtures/CustomFieldFixture.php';
        }

        if (!Schema::hasTable('custom_fields')) {
            Schema::create('custom_fields', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('mailbox_id');
                $table->string('name');
                $table->boolean('show_in_list')->default(true);
            });
            $this->createdCustomFieldsTable = true;
        }

        if (!Schema::hasTable('conversation_custom_field')) {
            Schema::create('conversation_custom_field', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('conversation_id');
                $table->unsignedInteger('custom_field_id');
                $table->string('value')->nullable();
            });
            $this->createdConversationCustomFieldTable = true;
        }

        // Real migration file, not a hand-rolled schema — exercises it directly
        // rather than risking the test's schema drifting from what actually ships.
        if (!Schema::hasTable('sortablecustomfields_user_columns')) {
            require_once __DIR__.'/../../Modules/SortableCustomFields/Database/Migrations/2026_07_18_000001_create_sortablecustomfields_user_columns_table.php';
            (new \CreateSortablecustomfieldsUserColumnsTable())->up();
            $this->createdUserColumnsTable = true;
        }

        (new \Modules\SortableCustomFields\Providers\SortableCustomFieldsServiceProvider(app()))->boot();
    }

    protected function tearDown(): void
    {
        \DB::table('sortablecustomfields_user_columns')->whereIn('user_id', $this->userIds)->delete();
        \DB::table('mailbox_user')->whereIn('user_id', $this->userIds)->delete();
        \DB::table('users')->whereIn('id', $this->userIds)->delete();
        \DB::table('conversation_custom_field')->whereIn('conversation_id', $this->conversationIds)->delete();
        \DB::table('conversations')->whereIn('id', $this->conversationIds)->delete();
        \DB::table('custom_fields')->whereIn('id', $this->customFieldIds)->delete();
        \DB::table('folders')->whereIn('id', $this->folderIds)->delete();
        \DB::table('mailboxes')->whereIn('id', $this->mailboxIds)->delete();

        if ($this->createdUserColumnsTable) {
            Schema::dropIfExists('sortablecustomfields_user_columns');
        }
        if ($this->createdConversationCustomFieldTable) {
            Schema::dropIfExists('conversation_custom_field');
        }
        if ($this->createdCustomFieldsTable) {
            Schema::dropIfExists('custom_fields');
        }

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

    protected function makeConversation($mailboxId, $folderId)
    {
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailboxId,
            'folder_id'  => $folderId,
        ]);
        $this->conversationIds[] = $conversation->id;

        return $conversation;
    }

    protected function makeCustomField($mailboxId, $name, $showInList = true)
    {
        $id = \DB::table('custom_fields')->insertGetId([
            'mailbox_id'    => $mailboxId,
            'name'          => $name,
            'show_in_list'  => $showInList,
        ]);
        $this->customFieldIds[] = $id;

        return $id;
    }

    protected function setCustomFieldValue($conversationId, $customFieldId, $value)
    {
        \DB::table('conversation_custom_field')->insert([
            'conversation_id'  => $conversationId,
            'custom_field_id'  => $customFieldId,
            'value'            => $value,
        ]);
    }

    protected function baseQuery($mailboxId)
    {
        return Conversation::where('conversations.mailbox_id', $mailboxId);
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

    /**
     * Core proof the SQL-injection fix works: a sort_by that doesn't match
     * any real custom field name (including SQL-metacharacter payloads)
     * must leave the query completely untouched — no join, no reference to
     * the raw request value anywhere in the built SQL.
     */
    public function test_unmatched_and_malicious_sort_by_leaves_query_untouched()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $this->makeCustomField($mailbox->id, 'Priority');

        $maliciousPayloads = [
            "custom_x' UNION SELECT password FROM users --",
            "custom_priority' OR '1'='1",
            'custom_does_not_exist',
        ];

        foreach ($maliciousPayloads as $payload) {
            $_REQUEST['sorting'] = ['sort_by' => $payload, 'order' => 'asc'];

            $base = $this->baseQuery($mailbox->id);
            $baseSql = $base->toSql();

            $filtered = \Eventy::filter('folder.conversations_query', $this->baseQuery($mailbox->id), $folder, 1);

            $this->assertSame($baseSql, $filtered->toSql(), "payload: $payload");
            $this->assertStringNotContainsString('UNION', $filtered->toSql());
            $this->assertStringNotContainsString($payload, $filtered->toSql());
        }

        unset($_REQUEST['sorting']);
    }

    /**
     * A legitimate matching sort_by must actually add the join/order —
     * proving the fix doesn't just neuter the feature. Also proves the
     * addFilter(..., 20, 2) argument count fix: getQueryByFolder() passes
     * $folder as the 2nd arg, and Eventy's Filter::fire() truncates to
     * exactly the registered argument count, so without that fix $folder
     * would always be null here.
     */
    public function test_matching_slug_adds_join_and_order_using_folder_mailbox_id()
    {
        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);

        $this->makeCustomField($mailboxA->id, 'Priority');
        // Deliberately no "Priority" field on mailboxB, only "Category" — if
        // resolution ever fell back to mailboxB (the conflicting
        // request-derived id) instead of $folder->mailbox_id, "custom_priority"
        // would match nothing there and the filter would add no join at all,
        // which the assertions below would catch.
        $this->makeCustomField($mailboxB->id, 'Category');

        // Conflicting request-derived mailbox id (mailboxB) — $folder must win.
        request()->merge(['mailbox_id' => $mailboxB->id]);
        $_REQUEST['sorting'] = ['sort_by' => 'custom_priority', 'order' => 'desc'];

        $query = \Eventy::filter('folder.conversations_query', $this->baseQuery($mailboxA->id), $folderA, 1);

        $sql = $query->toSql();
        $this->assertStringContainsString('left join', strtolower($sql));
        $this->assertStringContainsString('sort_priority', $sql);
        $this->assertStringContainsString('order by', strtolower($sql));
        $this->assertStringContainsString('desc', strtolower($sql));

        unset($_REQUEST['sorting']);
    }

    /**
     * End-to-end proof, not just SQL-shape inspection: seed real
     * conversations with real custom-field values and confirm the query
     * actually executes and orders correctly.
     */
    public function test_sort_filter_orders_conversations_by_custom_field_value_end_to_end()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $fieldId = $this->makeCustomField($mailbox->id, 'Priority');

        $low = $this->makeConversation($mailbox->id, $folder->id);
        $high = $this->makeConversation($mailbox->id, $folder->id);
        $medium = $this->makeConversation($mailbox->id, $folder->id);

        $this->setCustomFieldValue($low->id, $fieldId, 'Low');
        $this->setCustomFieldValue($high->id, $fieldId, 'High');
        $this->setCustomFieldValue($medium->id, $fieldId, 'Medium');

        $_REQUEST['sorting'] = ['sort_by' => 'custom_priority', 'order' => 'asc'];

        $results = \Eventy::filter('folder.conversations_query', $this->baseQuery($mailbox->id), $folder, 1)->get();

        // Fetch each returned conversation's custom-field value, in the
        // order the query actually returned them — this is genuine proof
        // the ORDER BY works, not just that the SQL string looks right.
        $orderedValues = \DB::table('conversation_custom_field')
            ->whereIn('conversation_id', $results->pluck('id'))
            ->where('custom_field_id', $fieldId)
            ->get()
            ->keyBy('conversation_id');

        $actualOrder = $results->pluck('id')->map(function ($id) use ($orderedValues) {
            return $orderedValues[$id]->value;
        })->toArray();

        $this->assertSame(['High', 'Low', 'Medium'], $actualOrder);
    }

    /**
     * Non-string sorting[sort_by]/order must not throw (defensive guard
     * added alongside the injection fix).
     */
    public function test_array_shaped_sorting_params_do_not_crash()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $this->makeCustomField($mailbox->id, 'Priority');

        $_REQUEST['sorting'] = ['sort_by' => ['custom_priority'], 'order' => ['asc']];

        $filtered = \Eventy::filter('folder.conversations_query', $this->baseQuery($mailbox->id), $folder, 1);

        $this->assertNotNull($filtered);

        unset($_REQUEST['sorting']);
    }

    /**
     * The reflected-XSS fix: sorting[order] must never reach the rendered
     * data-order attribute as anything other than a strict asc/desc value,
     * and a malicious custom-field name must render escaped.
     */
    public function test_th_before_conv_number_normalizes_order_and_escapes_name()
    {
        $mailbox = $this->makeMailbox();
        $fieldName = '<script>alert(1)</script>';
        $this->makeCustomField($mailbox->id, $fieldName);

        // Real slug, not a guess — Str::slug strips '<script>' etc. down to
        // "scriptalert1script" (no separators inserted for the stripped
        // characters), so sort_by must match that exactly to hit the
        // sort_by-matches-this-column branch that echoes $sorting['order'].
        $slug = \Modules\SortableCustomFields\Providers\SortableCustomFieldsServiceProvider::createSlug($fieldName, '_');
        $sorting = [
            'sort_by' => 'custom_'.$slug,
            'order'   => '"><script>alert(document.cookie)</script>',
        ];
        // th_before_conv_number reads request()->sorting (the bound Request
        // object), not the raw superglobal — merge() so it actually sees it.
        request()->merge(['mailbox_id' => $mailbox->id, 'sorting' => $sorting]);
        $_REQUEST['sorting'] = $sorting;

        ob_start();
        \Eventy::action('conversations_table.th_before_conv_number');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('alert(document.cookie)', $html);
        $this->assertMatchesRegularExpression('/data-order="(asc|desc)"/', $html);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);

        unset($_REQUEST['sorting']);
    }

    /**
     * These four hit ColumnPreferencesController directly rather than via a
     * real HTTP request through the full middleware stack. A real
     * $this->post() here trips a pre-existing, unrelated environment issue:
     * ResponseHeaders middleware's header_remove() fails with "headers
     * already sent" once PHPUnit's own progress-dot printer has written to
     * stdout — the same reason ConversationChangeCustomerTest.php guards its
     * HTTP assertions behind `PHP_VERSION_ID >= 80400` (we run tests on
     * 8.2). Skipping these tests the same way would mean they never
     * actually run here, so instead they exercise the controller's real
     * logic — validation, mailbox authorization, upsert — directly. What's
     * NOT covered this way: that the route itself is guarded by the 'auth'
     * middleware — checked structurally instead, below.
     */
    public function test_route_is_guarded_by_auth_middleware()
    {
        $route = \Route::getRoutes()->getByName('sortablecustomfields.columns.save');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->middleware());
    }

    protected function callSaveController($user, array $data)
    {
        \Auth::login($user);
        $request = \Illuminate\Http\Request::create('/sortablecustomfields/columns', 'POST', $data);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $controller = new \Modules\SortableCustomFields\Http\Controllers\ColumnPreferencesController();

        return $controller->save($request);
    }

    public function test_save_rejects_user_without_mailbox_access()
    {
        $mailbox = $this->makeMailbox();
        $fieldId = $this->makeCustomField($mailbox->id, 'Priority');
        $outsider = $this->makeUser(); // no mailbox attached

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->callSaveController($outsider, [
            'mailbox_id'      => $mailbox->id,
            'custom_field_id' => $fieldId,
            'visible'         => 0,
            'sortable'        => 1,
        ]);
    }

    public function test_save_rejects_custom_field_from_a_different_mailbox()
    {
        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $fieldOnB = $this->makeCustomField($mailboxB->id, 'Category');
        $user = $this->makeUser($mailboxA->id);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->callSaveController($user, [
            'mailbox_id'      => $mailboxA->id,
            'custom_field_id' => $fieldOnB,
            'visible'         => 0,
            'sortable'        => 1,
        ]);
    }

    public function test_save_upserts_preference()
    {
        $mailbox = $this->makeMailbox();
        $fieldId = $this->makeCustomField($mailbox->id, 'Priority');
        $user = $this->makeUser($mailbox->id);

        $response = $this->callSaveController($user, [
            'mailbox_id'      => $mailbox->id,
            'custom_field_id' => $fieldId,
            'visible'         => 0,
            'sortable'        => 1,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, \DB::table('sortablecustomfields_user_columns')->count());
        $row = \DB::table('sortablecustomfields_user_columns')->first();
        $this->assertSame(0, (int) $row->visible);

        // Posting again for the same user/mailbox/field updates the existing
        // row (unique constraint) rather than creating a second one.
        $this->callSaveController($user, [
            'mailbox_id'      => $mailbox->id,
            'custom_field_id' => $fieldId,
            'visible'         => 1,
            'sortable'        => 0,
        ]);

        $this->assertSame(1, \DB::table('sortablecustomfields_user_columns')->count());
        $row = \DB::table('sortablecustomfields_user_columns')->first();
        $this->assertSame(1, (int) $row->visible);
        $this->assertSame(0, (int) $row->sortable);
    }

    public function test_hidden_field_is_skipped_in_col_th_td_for_that_user()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $fieldId = $this->makeCustomField($mailbox->id, 'Priority');
        $user = $this->makeUser($mailbox->id);

        UserColumnPreference::setPreference($user->id, $mailbox->id, $fieldId, [
            'visible' => false, 'sortable' => true,
        ]);

        $this->actingAs($user);
        request()->merge(['mailbox_id' => $mailbox->id]);

        $colHtml = $this->captureEventyAction('conversations_table.col_before_conv_number', null);
        $thHtml = $this->captureEventyAction('conversations_table.th_before_conv_number');

        $this->assertStringNotContainsString('conv-priority', $colHtml);
        $this->assertStringNotContainsString('Priority', $thHtml);

        // A different, unauthenticated context (or another user with no
        // preference row) still gets the default: visible.
        \Auth::logout();
        $colHtmlDefault = $this->captureEventyAction('conversations_table.col_before_conv_number', null);
        $this->assertStringContainsString('conv-priority', $colHtmlDefault);
    }

    public function test_non_sortable_preference_renders_static_header()
    {
        $mailbox = $this->makeMailbox();
        $fieldId = $this->makeCustomField($mailbox->id, 'Priority');
        $user = $this->makeUser($mailbox->id);

        UserColumnPreference::setPreference($user->id, $mailbox->id, $fieldId, [
            'visible' => true, 'sortable' => false,
        ]);

        $this->actingAs($user);
        request()->merge(['mailbox_id' => $mailbox->id]);

        $thHtml = $this->captureEventyAction('conversations_table.th_before_conv_number');

        $this->assertStringContainsString('Priority', $thHtml);
        $this->assertStringNotContainsString('data-sort-by', $thHtml);
        $this->assertStringContainsString('custom-field-th-static', $thHtml);
    }

    /**
     * The sort filter itself must also respect sortable=false — a user
     * turning sorting off for a field should stop it from being sortable
     * server-side too, not just hide the clickable header client-side.
     */
    public function test_sort_filter_ignores_field_the_user_marked_non_sortable()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $fieldId = $this->makeCustomField($mailbox->id, 'Priority');
        $user = $this->makeUser($mailbox->id);

        UserColumnPreference::setPreference($user->id, $mailbox->id, $fieldId, [
            'visible' => true, 'sortable' => false,
        ]);

        $this->actingAs($user);
        $_REQUEST['sorting'] = ['sort_by' => 'custom_priority', 'order' => 'asc'];

        $base = $this->baseQuery($mailbox->id);
        $baseSql = $base->toSql();

        $filtered = \Eventy::filter('folder.conversations_query', $this->baseQuery($mailbox->id), $folder, 1);

        $this->assertSame($baseSql, $filtered->toSql());

        unset($_REQUEST['sorting']);
    }

    public function test_toolbar_reflects_hidden_count_and_toggle_state()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $this->makeCustomField($mailbox->id, 'Priority');
        $hiddenFieldId = $this->makeCustomField($mailbox->id, 'Category');
        $user = $this->makeUser($mailbox->id);

        UserColumnPreference::setPreference($user->id, $mailbox->id, $hiddenFieldId, [
            'visible' => false, 'sortable' => true,
        ]);

        $this->actingAs($user);

        $html = $this->captureEventyAction('conversations_table.toolbar', $folder);

        $this->assertStringContainsString('scf-hidden-badge', $html);
        $this->assertMatchesRegularExpression('/scf-hidden-badge">1</', $html);
        $this->assertStringContainsString('Priority', $html);
        $this->assertStringContainsString('Category', $html);

        // The hidden field's own <li> row must not carry "checked".
        $rowStart = strpos($html, 'data-custom_field_id="'.$hiddenFieldId.'"');
        $this->assertNotFalse($rowStart);
        $rowEnd = strpos($html, '</li>', $rowStart);
        $row = substr($html, $rowStart, $rowEnd - $rowStart);
        $this->assertStringNotContainsString('checked', $row);
    }

    protected function captureEventyAction($hook, ...$args)
    {
        ob_start();
        \Eventy::action($hook, ...$args);

        return ob_get_clean();
    }
}
