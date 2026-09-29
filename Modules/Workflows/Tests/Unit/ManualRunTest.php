<?php

namespace Modules\Workflows\Tests\Unit;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\Workflows\Http\Controllers\WorkflowsController;
use Modules\Workflows\Services\WorkflowRunner;
use Tests\TestCase;

class ManualRunTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!app('session')->isStarted()) {
            app('session')->start();
        }

        View::addNamespace(
            'workflows',
            base_path('Modules/Workflows/Resources/views')
        );
        Lang::addNamespace(
            'workflows',
            base_path('Modules/Workflows/Resources/lang')
        );

        if (!Route::has('conversations.workflow.run')) {
            require base_path('Modules/Workflows/Http/routes.php');
            // Names set with ->name() are not in the lookup until this refresh.
            Route::getRoutes()->refreshNameLookups();
        }
    }

    protected function tearDown(): void
    {
        app()->forgetInstance(WorkflowRunner::class);

        parent::tearDown();
    }

    public function test_conversation_menu_confirms_a_reply_and_not_a_note(): void
    {
        $conversation = new \stdClass();
        $conversation->id = 15;
        $reply = $this->workflow(3, 'Send & reply', 'reply');
        $note = $this->workflow(4, 'Leave a note', 'add_note');

        $html = view('workflows::partials.conversation_menu', [
            'conversation' => $conversation,
            'workflows' => [$reply, $note],
        ])->render();

        $replyAction = route('conversations.workflow.run', ['id' => $conversation->id, 'workflow' => $reply->id]);
        $noteAction = route('conversations.workflow.run', ['id' => $conversation->id, 'workflow' => $note->id]);

        $this->assertMatchesRegularExpression(
            '/<form[^>]*method="POST"[^>]*data-confirm="1"[^>]*>[\s\S]*Send &amp; reply[\s\S]*<\/form>/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<form[^>]*method="POST"[^>]*data-confirm="0"[^>]*>[\s\S]*Leave a note[\s\S]*<\/form>/',
            $html
        );
        $this->assertStringContainsString($replyAction, $html);
        $this->assertStringContainsString($noteAction, $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString(__('workflows::messages.run'), $html);
        $this->assertStringNotContainsString('Send & reply', $html);
    }

    public function test_manual_payload_confirms_customer_messages_and_deletes(): void
    {
        $confirmTypes = ['reply', 'email_customer', 'forward', 'move_deleted', 'delete_forever'];

        foreach ($confirmTypes as $type) {
            $this->assertSame(['confirm' => true], WorkflowsController::manualPayload([
                'actions' => [
                    ['type' => 'add_note'],
                    ['type' => $type],
                ],
            ]));
            $this->assertSame(['confirm' => true], WorkflowsController::manualPayload((object) [
                'actions' => [
                    (object) ['type' => $type],
                ],
            ]));
        }

        $this->assertSame(['confirm' => false], WorkflowsController::manualPayload([
            'actions' => [
                ['type' => 'add_note'],
                ['type' => 'change_status'],
            ],
        ]));
        $this->assertSame(['confirm' => false], WorkflowsController::manualPayload((object) [
            'actions' => [],
        ]));
        $this->assertSame(['confirm' => false], WorkflowsController::manualPayload([]));
    }

    public function test_run_manual_calls_the_container_runner_and_redirects(): void
    {
        $runner = new class extends WorkflowRunner {
            public $trigger;

            public function runOne($conversation, $workflow, array $trigger = []): void
            {
                $this->trigger = $trigger;
            }
        };
        app()->instance(WorkflowRunner::class, $runner);

        $controller = new WorkflowsController();
        $conversation = new \stdClass();
        $conversation->id = 15;

        $response = $controller->runManual($conversation, new \stdClass());

        $this->assertSame(['name' => 'manual'], $runner->trigger);
        $this->assertStringContainsString('/conversation/15', $response->getTargetUrl());
    }

    public function test_manual_runs_evaluate_date_rows_and_keep_subject_workflows(): void
    {
        $conversation = [];
        $waiting = [
            'conditions' => [
                ['type' => 'waiting_since', 'operator' => 'in_the_last', 'value' => ['number' => 1, 'unit' => 'days']],
            ],
        ];

        $this->assertTrue(WorkflowRunner::eligible($waiting, $conversation, ['name' => 'manual']));
        $this->assertFalse(WorkflowRunner::eligible($waiting, $conversation, ['name' => 'customer_reply']));

        $subject = [
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'invoice'],
            ],
        ];

        $this->assertTrue(WorkflowRunner::eligible($subject, $conversation, ['name' => 'manual']));
        $this->assertFalse(WorkflowRunner::eligible($subject, $conversation, ['name' => 'schedule']));
    }

    public function test_bulk_menu_posts_checked_conversation_ids(): void
    {
        $workflow = $this->workflow(8, 'Send & reply', 'reply');

        $html = view('workflows::partials.bulk_menu', [
            'workflows' => [$workflow],
        ])->render();

        $this->assertStringContainsString('/bulk', $html);
        $this->assertStringContainsString('conversation_id', $html);
        $this->assertStringContainsString('conv-checkbox', $html);
        $this->assertStringContainsString('data-confirm="1"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('Send &amp; reply', $html);
        $this->assertStringContainsString(
            route('conversations.workflow.bulk', ['workflow' => $workflow->id]),
            $html
        );
    }

    /**
     * @param int    $id
     * @param string $name
     * @param string $actionType
     * @return \stdClass
     */
    private function workflow($id, $name, $actionType)
    {
        $workflow = new \stdClass();
        $workflow->id = $id;
        $workflow->name = $name;
        $workflow->actions = [
            ['type' => $actionType],
        ];

        return $workflow;
    }
}
