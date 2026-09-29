<?php

namespace Modules\Workflows\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class WorkflowViewsTest extends TestCase
{
    /**
     * @var \Closure|null
     */
    private $previousRouteResolver;

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

        if (!Route::has('mailboxes.workflows')) {
            require base_path('Modules/Workflows/Http/routes.php');
            // Names set with ->name() are not in the lookup until this refresh.
            Route::getRoutes()->refreshNameLookups();
        }

        $this->previousRouteResolver = app('request')->getRouteResolver();
        // Layout reads request attributes through the current route. An unbound route throws.
        $route = new class {
            public function getName()
            {
                return 'mailboxes.update';
            }

            public function parameter($name, $default = null)
            {
                return $default;
            }

            public function parameters()
            {
                return [];
            }
        };
        app('request')->setRouteResolver(function () use ($route) {
            return $route;
        });
    }

    protected function tearDown(): void
    {
        $this->clearUser();
        if ($this->previousRouteResolver) {
            app('request')->setRouteResolver($this->previousRouteResolver);
        }

        parent::tearDown();
    }

    public function test_index_lists_the_workflow_name_and_automatic_type(): void
    {
        $mailbox = $this->mailboxDouble();
        $workflow = (object) [
            'id' => 9,
            'name' => 'Close billing',
            'type' => 'automatic',
            'active' => true,
            'sort_order' => 2,
        ];

        $html = $this->renderPage('workflows::index', [
            'mailbox' => $mailbox,
            'workflows' => [$workflow],
        ]);

        $this->assertStringContainsString('Close billing', $html);
        $this->assertStringContainsString('Automatic', $html);
    }

    public function test_edit_warns_when_max_executions_is_above_one_and_has_no_run_submit(): void
    {
        $mailbox = $this->mailboxDouble();
        $workflow = (object) [
            'id' => 4,
            'name' => 'Close billing',
            'type' => 'automatic',
            'active' => true,
            'apply_to_previous' => false,
            'max_executions' => 2,
            'match' => 'all',
            'conditions' => [
                ['type' => 'subject', 'operator' => 'contains', 'value' => 'invoice'],
            ],
            'actions' => [
                ['type' => 'change_status', 'value' => 'closed'],
            ],
        ];

        $html = $this->renderPage('workflows::edit', [
            'mailbox' => $mailbox,
            'workflow' => $workflow,
            'users' => [
                ['id' => 3, 'name' => 'Ada'],
            ],
            'conditionGroups' => [
                'message' => [
                    'title' => 'Message',
                    'items' => [
                        'subject' => [
                            'title' => 'Subject',
                            'operators' => ['contains' => 'Contains'],
                        ],
                    ],
                ],
            ],
            'actionTypes' => ['stop', 'change_status', 'assign'],
        ]);

        $this->assertMatchesRegularExpression('/<input[^>]*name="max_executions"/', $html);
        $this->assertStringContainsString('A value above 1 can let workflows trigger each other.', $html);
        $this->assertStringNotContainsString('name="run"', $html);
    }

    public function test_edit_posts_one_indexed_row_and_embeds_operator_map(): void
    {
        $groups = $this->conditionGroups();
        $html = $this->renderPage('workflows::edit', [
            'mailbox' => $this->mailboxDouble(),
            'workflow' => (object) [
                'id' => 4,
                'name' => 'Close billing',
                'type' => 'automatic',
                'active' => true,
                'apply_to_previous' => false,
                'max_executions' => 1,
                'match' => 'all',
                'conditions' => [
                    ['type' => 'subject', 'operator' => 'contains', 'value' => 'invoice'],
                ],
                'actions' => [
                    ['type' => 'assign', 'value' => ['user_id' => 4, 'only_if_available' => true]],
                ],
            ],
            'users' => [
                ['id' => 4, 'name' => 'Ada'],
            ],
            'conditionGroups' => $groups,
            'actionTypes' => ['stop', 'assign', 'notification'],
        ]);

        $this->assertStringContainsString('conditions[0][type]', $html);
        $this->assertStringContainsString('conditions[0][operator]', $html);
        $this->assertStringContainsString('conditions[0][value]', $html);
        $this->assertStringContainsString('actions[0][value][user_id]', $html);
        $this->assertStringContainsString('actions[0][value][only_if_available]', $html);
        $this->assertStringNotContainsString('conditions[][type]', $html);
        $this->assertStringNotContainsString('actions[][type]', $html);
        $this->assertStringContainsString('"waiting_since":{"in_the_last":', $html);
        $this->assertStringContainsString('workflowOperators', $html);
        $this->assertStringContainsString("createElement('option')", $html);

        $dated = $this->renderPage('workflows::edit', [
            'mailbox' => $this->mailboxDouble(),
            'workflow' => (object) [
                'id' => 4,
                'name' => 'Close billing',
                'type' => 'automatic',
                'active' => true,
                'apply_to_previous' => false,
                'max_executions' => 1,
                'match' => 'all',
                'conditions' => [
                    ['type' => 'waiting_since', 'operator' => 'in_the_last', 'value' => ['number' => 2, 'unit' => 'days']],
                ],
                'actions' => [],
            ],
            'users' => [],
            'conditionGroups' => $groups,
            'actionTypes' => ['assign'],
        ]);

        $this->assertStringContainsString('conditions[0][value][number]', $dated);
        $this->assertStringContainsString('conditions[0][value][unit]', $dated);
    }

    public function test_settings_menu_links_to_workflows(): void
    {
        $html = view('workflows::partials.settings_menu', [
            'mailbox' => $this->mailboxDouble(),
        ])->render();

        $this->assertStringContainsString('/workflows', $html);
    }

    /**
     * @return array
     */
    private function conditionGroups(): array
    {
        return [
            'message' => [
                'title' => 'Message',
                'items' => [
                    'subject' => [
                        'title' => 'Subject',
                        'operators' => ['contains' => 'Contains'],
                    ],
                ],
            ],
            'dates' => [
                'title' => 'Dates',
                'items' => [
                    'waiting_since' => [
                        'title' => 'Waiting since',
                        'operators' => [
                            'in_the_last' => 'In the last',
                            'not_in_the_last' => 'Not in the last',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $view
     * @param array  $data
     * @return string
     */
    private function renderPage(string $view, array $data): string
    {
        auth()->setUser($this->userDouble());

        try {
            return view($view, $data)->render();
        } finally {
            $this->clearUser();
        }
    }

    /**
     * logout() writes an activity log. Clear the guard without that write.
     *
     * @return void
     */
    private function clearUser(): void
    {
        $guard = auth()->guard();
        $user = new \ReflectionProperty($guard, 'user');
        $user->setAccessible(true);
        $user->setValue($guard, null);
        $loggedOut = new \ReflectionProperty($guard, 'loggedOut');
        $loggedOut->setAccessible(true);
        $loggedOut->setValue($guard, true);
    }

    /**
     * @return object
     */
    private function mailboxDouble()
    {
        return new class {
            public $id = 7;
            public $name = 'Billing';
            public $email = 'billing@example.com';

            public function isArchived()
            {
                return false;
            }

            public function isConnected()
            {
                return true;
            }
        };
    }

    /**
     * @return Authenticatable
     */
    private function userDouble(): Authenticatable
    {
        return new class implements Authenticatable {
            public $id = 1;
            public $first_name = 'Ada';
            public $last_name = 'Lovelace';
            public $role = 1;
            public $photo_url = '';

            public function getAuthIdentifierName()
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return $this->id;
            }

            public function getAuthPassword()
            {
                return '';
            }

            public function getRememberToken()
            {
                return null;
            }

            public function setRememberToken($value)
            {
            }

            public function getRememberTokenName()
            {
                return 'remember_token';
            }

            public function can($ability, $arguments = [])
            {
                return true;
            }

            public function isAdmin()
            {
                return true;
            }

            public function hasManageMailboxPermission($mailboxId, $permission)
            {
                return false;
            }

            public function mailboxesCanView($cache = false)
            {
                return [];
            }

            public function getWebsiteNotificationsInfo($cache = true)
            {
                return [
                    'data' => [],
                    'unread_count' => 0,
                    'html' => '',
                    'notifications' => new class {
                        public function hasMorePages()
                        {
                            return false;
                        }
                    },
                ];
            }
        };
    }
}
