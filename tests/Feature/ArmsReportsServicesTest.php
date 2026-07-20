<?php

namespace Tests\Feature;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the ArmsReports query logic (ARMS-13) that was previously only
 * checked manually via a boot script: KpiReportService, AgentPerformanceService,
 * ReportFilters, and the first_reply_at listener. tests/Unit/ArmsReportsStatsTest.php
 * already covers the pure Stats helpers — this file exercises the DB-touching
 * services against real seeded conversations/threads.
 *
 * Deliberately does NOT use DatabaseTransactions: first_reply_at is added to
 * the shared conversations table via a real migration run from setUp() when
 * missing, which is DDL and would implicitly commit any open transaction on
 * MySQL (same rationale as SortableCustomFieldsTest/CustomerFieldSearchTest).
 * Every row created is tracked and deleted explicitly in tearDown().
 */
class ArmsReportsServicesTest extends TestCase
{
    protected $addedFirstReplyAtColumn = false;

    protected $mailboxIds = [];
    protected $folderIds = [];
    protected $userIds = [];
    protected $conversationIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/../../Modules/ArmsReports/Services/Stats.php';
        require_once __DIR__.'/../../Modules/ArmsReports/Services/ReportFilters.php';
        require_once __DIR__.'/../../Modules/ArmsReports/Services/KpiReportService.php';
        require_once __DIR__.'/../../Modules/ArmsReports/Services/AgentPerformanceService.php';
        require_once __DIR__.'/../../Modules/ArmsReports/Services/Exporter.php';
        require_once __DIR__.'/../../Modules/ArmsReports/Http/Controllers/ArmsReportsController.php';
        require_once __DIR__.'/../../Modules/ArmsReports/Providers/ArmsReportsServiceProvider.php';

        // Real migration file, not a hand-rolled schema — exercises it
        // directly rather than risking the test's schema drifting from what
        // actually ships.
        if (!Schema::hasColumn('conversations', 'first_reply_at')) {
            require_once __DIR__.'/../../Modules/ArmsReports/Database/Migrations/2026_07_14_100000_add_first_reply_at_to_conversations.php';
            (new \AddFirstReplyAtToConversations())->up();
            $this->addedFirstReplyAtColumn = true;
        }

        // Registers the first_reply_at listener and the "armsreports::"
        // view namespace Exporter::pdf() renders through.
        (new \Modules\ArmsReports\Providers\ArmsReportsServiceProvider(app()))->boot();
    }

    protected function tearDown(): void
    {
        \DB::table('threads')->whereIn('conversation_id', $this->conversationIds)->delete();
        \DB::table('conversations')->whereIn('id', $this->conversationIds)->delete();
        \DB::table('mailbox_user')->whereIn('user_id', $this->userIds)->delete();
        \DB::table('users')->whereIn('id', $this->userIds)->delete();
        \DB::table('folders')->whereIn('id', $this->folderIds)->delete();
        \DB::table('mailboxes')->whereIn('id', $this->mailboxIds)->delete();

        if ($this->addedFirstReplyAtColumn) {
            (new \AddFirstReplyAtToConversations())->down();
        }

        parent::tearDown();
    }

    protected function makeMailbox()
    {
        $mailbox = factory(Mailbox::class)->create();
        $this->mailboxIds[] = $mailbox->id;

        return $mailbox;
    }

    protected function makeFolder($mailboxId, $type = Folder::TYPE_UNASSIGNED)
    {
        $folder = factory(Folder::class)->create(['mailbox_id' => $mailboxId, 'type' => $type]);
        $this->folderIds[] = $folder->id;

        return $folder;
    }

    protected function makeUser($mailboxId = null)
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        if ($mailboxId) {
            $user->mailboxes()->attach($mailboxId);
        }
        $this->userIds[] = $user->id;

        return $user;
    }

    protected function makeConversation($mailboxId, $folderId, array $attrs = [])
    {
        $conversation = factory(Conversation::class)->create(array_merge([
            'mailbox_id' => $mailboxId,
            'folder_id'  => $folderId,
        ], $attrs));
        $this->conversationIds[] = $conversation->id;

        return $conversation;
    }

    protected function makeThread($conversationId, array $attrs = [])
    {
        return factory(Thread::class)->create(array_merge([
            'conversation_id' => $conversationId,
        ], $attrs));
    }

    protected function filters($mailboxId = null, $userId = null, $from = null, $to = null)
    {
        $filters = new \Modules\ArmsReports\Services\ReportFilters();
        $filters->from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $filters->to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();
        $filters->mailbox_id = $mailboxId;
        $filters->user_id = $userId;

        return $filters;
    }

    protected function cardsByLabel(array $data)
    {
        return collect($data['cards'])->keyBy('label');
    }

    protected function sectionByKey(array $data, $key)
    {
        return collect($data['sections'])->keyBy('key')->get($key);
    }

    // -- ReportFilters ------------------------------------------------------

    /**
     * gemini-code-assist review, PR #2: Carbon::parse() on unparseable
     * input threw, 500ing the whole report request.
     */
    public function test_report_filters_falls_back_on_invalid_date_input()
    {
        $request = Request::create('/arms-reports/kpis', 'GET', ['from' => 'not-a-date', 'to' => 'also-not-a-date']);

        $filters = \Modules\ArmsReports\Services\ReportFilters::fromRequest($request);

        $this->assertInstanceOf(Carbon::class, $filters->from);
        $this->assertInstanceOf(Carbon::class, $filters->to);
        $this->assertTrue($filters->from->lte($filters->to));
    }

    public function test_report_filters_swaps_dates_when_to_is_before_from()
    {
        $request = Request::create('/arms-reports/kpis', 'GET', ['from' => '2026-02-10', 'to' => '2026-02-01']);

        $filters = \Modules\ArmsReports\Services\ReportFilters::fromRequest($request);

        $this->assertTrue($filters->from->lte($filters->to));
        $this->assertSame('2026-02-01', $filters->from->format('Y-m-d'));
        $this->assertSame('2026-02-10', $filters->to->format('Y-m-d'));
    }

    public function test_report_filters_casts_mailbox_and_user_ids()
    {
        $request = Request::create('/arms-reports/kpis', 'GET', ['mailbox_id' => '5', 'user_id' => '9']);

        $filters = \Modules\ArmsReports\Services\ReportFilters::fromRequest($request);

        $this->assertSame(5, $filters->mailbox_id);
        $this->assertSame(9, $filters->user_id);
    }

    // -- KpiReportService -----------------------------------------------------

    /**
     * gemini-code-assist review, PR #2: created/resolved-today cards ignored
     * the assignee (and, by the same code path, mailbox) filter while every
     * other metric respected it.
     */
    public function test_kpi_cards_respect_mailbox_filter()
    {
        $mailboxA = $this->makeMailbox();
        $mailboxB = $this->makeMailbox();
        $folderA = $this->makeFolder($mailboxA->id);
        $folderB = $this->makeFolder($mailboxB->id);
        $user = $this->makeUser();

        $this->makeConversation($mailboxA->id, $folderA->id, ['created_by_user_id' => $user->id, 'created_at' => Carbon::today()->addHours(2)]);
        $this->makeConversation($mailboxA->id, $folderA->id, ['created_by_user_id' => $user->id, 'created_at' => Carbon::today()->addHours(3)]);
        $this->makeConversation($mailboxB->id, $folderB->id, ['created_by_user_id' => $user->id, 'created_at' => Carbon::today()->addHours(2)]);

        $data = (new \Modules\ArmsReports\Services\KpiReportService($this->filters($mailboxA->id)))->build();

        $this->assertSame(2, $this->cardsByLabel($data)['Created today']['value']);
    }

    public function test_kpi_by_hour_groups_by_creation_hour()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $this->makeUser($mailbox->id); // ConversationFactory needs a user to exist to pick as created_by_user_id

        $this->makeConversation($mailbox->id, $folder->id, ['created_at' => '2026-03-01 09:15:00']);
        $this->makeConversation($mailbox->id, $folder->id, ['created_at' => '2026-03-02 09:45:00']);
        $this->makeConversation($mailbox->id, $folder->id, ['created_at' => '2026-03-01 14:00:00']);

        $data = (new \Modules\ArmsReports\Services\KpiReportService($this->filters($mailbox->id, null, '2026-02-01', '2026-03-31')))->build();

        $rows = collect($this->sectionByKey($data, 'by_hour')['rows'])->keyBy(0);
        $this->assertSame(2, $rows['09:00'][1]);
        $this->assertSame(1, $rows['14:00'][1]);
        $this->assertSame(0, $rows['03:00'][1]);
    }

    public function test_kpi_reply_brackets_attributes_replies_to_assigned_agent()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $agent = $this->makeUser($mailbox->id);

        $conversation = $this->makeConversation($mailbox->id, $folder->id, ['user_id' => $agent->id]);
        $this->makeThread($conversation->id, [
            'type' => Thread::TYPE_MESSAGE, 'state' => Thread::STATE_PUBLISHED,
            'created_by_user_id' => $agent->id, 'created_at' => Carbon::now(),
        ]);

        $data = (new \Modules\ArmsReports\Services\KpiReportService($this->filters($mailbox->id)))->build();

        $rows = collect($this->sectionByKey($data, 'reply_brackets')['rows'])->keyBy(0);
        $agentName = trim($agent->first_name.' '.$agent->last_name);
        // headers: [Agent, '1', '2–3', '4–6', '7+'] — one reply falls in bracket "1".
        $this->assertSame(1, $rows[$agentName][1]);
    }

    /**
     * A conversation that goes Active -> Closed -> Active (via a customer
     * reply thread) counts as reopened; one that stays closed does not.
     */
    public function test_reopened_count_detects_status_thread_reopen()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $created = Carbon::parse('2026-04-01 09:00:00');

        $reopened = $this->makeConversation($mailbox->id, $folder->id, ['created_at' => $created, 'status' => Conversation::STATUS_ACTIVE]);
        $this->makeThread($reopened->id, [
            'type' => Thread::TYPE_LINEITEM, 'action_type' => Thread::ACTION_TYPE_STATUS_CHANGED,
            'status' => Conversation::STATUS_CLOSED, 'state' => Thread::STATE_PUBLISHED,
            'created_at' => $created->copy()->addHours(2),
        ]);
        $this->makeThread($reopened->id, [
            'type' => Thread::TYPE_CUSTOMER, 'status' => Conversation::STATUS_ACTIVE,
            'state' => Thread::STATE_PUBLISHED, 'created_at' => $created->copy()->addHours(4),
        ]);

        $staysClosed = $this->makeConversation($mailbox->id, $folder->id, ['created_at' => $created, 'status' => Conversation::STATUS_CLOSED]);
        $this->makeThread($staysClosed->id, [
            'type' => Thread::TYPE_LINEITEM, 'action_type' => Thread::ACTION_TYPE_STATUS_CHANGED,
            'status' => Conversation::STATUS_CLOSED, 'state' => Thread::STATE_PUBLISHED,
            'created_at' => $created->copy()->addHours(2),
        ]);

        $data = (new \Modules\ArmsReports\Services\KpiReportService($this->filters($mailbox->id, null, '2026-03-01', '2026-04-30')))->build();

        $this->assertSame(1, $this->cardsByLabel($data)['Reopened tickets']['value']);
    }

    /**
     * gemini-code-assist review, PR #2: an open-ended status interval used
     * to run to "now" even for a historical report range, inflating
     * time-in-status for anything closed long ago. It must cap at the
     * filter's own "to" instead.
     */
    public function test_time_in_status_caps_open_ended_interval_at_range_end()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        // A range far in the past relative to real "now" — if the bug were
        // present, this would accrue years of Active time instead of ~7 days.
        $created = Carbon::parse('2020-01-01 00:00:00');
        $rangeEnd = Carbon::parse('2020-01-08 00:00:00');

        $this->makeConversation($mailbox->id, $folder->id, ['created_at' => $created, 'status' => Conversation::STATUS_ACTIVE]);

        $data = (new \Modules\ArmsReports\Services\KpiReportService(
            $this->filters($mailbox->id, null, '2020-01-01', $rangeEnd->format('Y-m-d'))
        ))->build();

        $rows = collect($this->sectionByKey($data, 'time_in_status')['rows'])->keyBy(0);
        $activeStatusName = Conversation::statusCodeToName(Conversation::STATUS_ACTIVE);

        // Exactly the filter's own range end (2020-01-01 00:00 to
        // 2020-01-08 23:59:59, via endOfDay()) — never "d"-scale years,
        // which is what the pre-fix bug would have produced.
        $this->assertSame('7d 23h', $rows[$activeStatusName][2]);
    }

    /**
     * Medians must read the first_reply_at column when it's populated (the
     * launch-critical, day-one path) and fall back to deriving from threads
     * for historical rows where the column is null.
     */
    public function test_medians_prefer_first_reply_at_column_and_derive_from_threads_otherwise()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $created = Carbon::parse('2026-05-01 00:00:00');

        // Column populated: 1 hour to first reply.
        $this->makeConversation($mailbox->id, $folder->id, [
            'created_at' => $created, 'first_reply_at' => $created->copy()->addHour(),
        ]);

        // Column null: derive 3 hours to first reply from the thread.
        $withoutColumn = $this->makeConversation($mailbox->id, $folder->id, ['created_at' => $created]);
        $this->makeThread($withoutColumn->id, [
            'type' => Thread::TYPE_MESSAGE, 'state' => Thread::STATE_PUBLISHED,
            'created_by_user_id' => $this->makeUser()->id, 'created_at' => $created->copy()->addHours(3),
        ]);

        $data = (new \Modules\ArmsReports\Services\KpiReportService($this->filters($mailbox->id, null, '2026-04-01', '2026-06-01')))->build();

        // Median of [1h, 3h] = 2h.
        $this->assertSame('2h 0m', $this->cardsByLabel($data)['First-response median']['value']);
    }

    // -- AgentPerformanceService -----------------------------------------------

    public function test_agent_performance_groups_tickets_and_medians_per_assignee()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $agentA = $this->makeUser($mailbox->id);
        $agentB = $this->makeUser($mailbox->id);
        $created = Carbon::parse('2026-06-01 00:00:00');

        $this->makeConversation($mailbox->id, $folder->id, [
            'user_id' => $agentA->id, 'created_at' => $created, 'first_reply_at' => $created->copy()->addHour(),
        ]);
        $this->makeConversation($mailbox->id, $folder->id, [
            'user_id' => $agentA->id, 'created_at' => $created, 'first_reply_at' => $created->copy()->addHours(3),
        ]);
        $this->makeConversation($mailbox->id, $folder->id, [
            'user_id' => $agentB->id, 'created_at' => $created, 'first_reply_at' => $created->copy()->addHours(5),
        ]);

        $data = (new \Modules\ArmsReports\Services\AgentPerformanceService($this->filters($mailbox->id, null, '2026-05-01', '2026-07-01')))->build();

        $rows = collect($data['sections'][0]['rows'])->keyBy(0);
        $agentAName = trim($agentA->first_name.' '.$agentA->last_name);
        $agentBName = trim($agentB->first_name.' '.$agentB->last_name);

        $this->assertSame(2, $rows[$agentAName][1]);
        $this->assertSame('2h 0m', $rows[$agentAName][2]); // median of [1h, 3h]
        $this->assertSame(1, $rows[$agentBName][1]);
        $this->assertSame('5h 0m', $rows[$agentBName][2]);
    }

    // -- first_reply_at listener --------------------------------------------

    /**
     * The listener must stamp first_reply_at from the FIRST reply only —
     * medians rely on this never being overwritten by later replies.
     */
    public function test_first_reply_at_listener_stamps_only_the_first_reply()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $conversation = $this->makeConversation($mailbox->id, $folder->id, ['first_reply_at' => null]);

        // first_reply_at isn't in Conversation::$dates (it's added by this
        // module, not core), so it comes back as a plain string, not Carbon.
        $firstThread = (object) ['created_at' => Carbon::parse('2026-07-01 10:00:00')];
        \Eventy::action('conversation.user_replied', $conversation, $firstThread);
        $conversation->refresh();
        $this->assertSame('2026-07-01 10:00:00', Carbon::parse($conversation->first_reply_at)->format('Y-m-d H:i:s'));

        $secondThread = (object) ['created_at' => Carbon::parse('2026-07-02 11:00:00')];
        \Eventy::action('conversation.user_replied', $conversation, $secondThread);
        $conversation->refresh();
        $this->assertSame('2026-07-01 10:00:00', Carbon::parse($conversation->first_reply_at)->format('Y-m-d H:i:s'));
    }

    // -- Nav asset ------------------------------------------------------------

    /**
     * The dropdown-merge script (folds "ARMS Reports" into the paid
     * Reports module's own dropdown client-side) must actually be
     * registered as a page asset, or the merge silently never runs.
     */
    public function test_dropdown_merge_script_is_registered()
    {
        $javascripts = \Eventy::filter('javascripts', []);

        $this->assertNotEmpty(array_filter($javascripts, function ($path) {
            return strpos($path, 'armsreports') !== false && strpos($path, 'module.js') !== false;
        }));
    }

    // -- Controller / export pipeline ----------------------------------------

    public function test_kpis_export_as_csv()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $this->makeConversation($mailbox->id, $folder->id);

        $controller = new \Modules\ArmsReports\Http\Controllers\ArmsReportsController();
        $request = Request::create('/arms-reports/kpis', 'GET', ['format' => 'csv', 'mailbox_id' => $mailbox->id]);

        $response = $controller->kpis($request);

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_kpis_export_as_pdf()
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            $this->markTestSkipped('dompdf not installed');
        }

        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $this->makeConversation($mailbox->id, $folder->id);

        $controller = new \Modules\ArmsReports\Http\Controllers\ArmsReportsController();
        $request = Request::create('/arms-reports/kpis', 'GET', ['format' => 'pdf', 'mailbox_id' => $mailbox->id]);

        $response = $controller->kpis($request);

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
