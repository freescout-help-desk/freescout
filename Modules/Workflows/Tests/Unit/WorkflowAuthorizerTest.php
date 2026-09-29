<?php

namespace Modules\Workflows\Tests\Unit;

use Modules\Workflows\Providers\WorkflowsServiceProvider;
use Modules\Workflows\Services\WorkflowAuthorizer;
use Tests\TestCase;

class WorkflowAuthorizerTest extends TestCase
{
    public function test_admin_can_manage_when_the_option_and_permission_are_off(): void
    {
        $user = $this->userDouble(true, false);

        $this->assertTrue(WorkflowAuthorizer::canManage($user, false));
    }

    public function test_non_admin_can_manage_when_the_option_is_on(): void
    {
        $user = $this->userDouble(false, false);

        $this->assertTrue(WorkflowAuthorizer::canManage($user, true));
    }

    public function test_non_admin_can_manage_with_permission_1001_when_the_option_is_off(): void
    {
        $user = $this->userDouble(false, true);

        $this->assertTrue(WorkflowAuthorizer::canManage($user, false));
        $this->assertSame([1001], $user->permissionsSeen);
    }

    public function test_non_admin_without_permission_cannot_manage_when_the_option_is_off(): void
    {
        $user = $this->userDouble(false, false);

        $this->assertFalse(WorkflowAuthorizer::canManage($user, false));
    }

    public function test_non_object_cannot_manage_when_the_option_is_off(): void
    {
        $this->assertFalse(WorkflowAuthorizer::canManage(null, false));
    }

    public function test_non_object_can_manage_when_the_option_is_on(): void
    {
        $this->assertTrue(WorkflowAuthorizer::canManage(null, true));
    }

    public function test_permission_name_is_manage_workflows(): void
    {
        $this->assertSame('Manage workflows', WorkflowAuthorizer::permissionName());
    }

    public function test_hooks_register_permission_list_and_name_filters(): void
    {
        $events = new class {
            public $filters = [];

            public function addFilter($hook, $callback, $priority = 20, $arguments = 1)
            {
                $this->filters[] = [
                    'hook' => $hook,
                    'callback' => $callback,
                    'priority' => $priority,
                    'arguments' => $arguments,
                ];
            }

            public function addAction($hook, $callback, $priority = 20, $arguments = 1)
            {
            }
        };

        WorkflowsServiceProvider::hooks($events);

        $list = $this->recorded($events->filters, 'user_permissions.list');
        $name = $this->recorded($events->filters, 'user_permissions.name');

        $this->assertNotNull($list);
        $this->assertSame(1, $list['arguments']);
        $this->assertNotNull($name);
        $this->assertSame(2, $name['arguments']);

        $once = call_user_func($list['callback'], [1, 2]);
        $this->assertSame([1, 2, 1001], $once);
        $this->assertSame([1, 2, 1001], call_user_func($list['callback'], $once));
        $this->assertSame([1, '1001'], call_user_func($list['callback'], [1, '1001']));
        $this->assertSame('permissions', call_user_func($list['callback'], 'permissions'));

        $this->assertSame('Manage workflows', call_user_func($name['callback'], '', 1001));
        $this->assertSame('Manage workflows', call_user_func($name['callback'], '', '1001'));
        $this->assertSame('keep', call_user_func($name['callback'], 'keep', 2));
        $this->assertSame('', call_user_func($name['callback'], '', 2));
    }

    public function test_missing_permission_method_is_false(): void
    {
        $user = new class {
            public function isAdmin()
            {
                return false;
            }
        };

        $this->assertFalse(WorkflowAuthorizer::canManage($user, false));
    }

    /**
     * @param bool $admin
     * @param bool $permitted
     * @return object
     */
    private function userDouble($admin, $permitted)
    {
        return new class($admin, $permitted) {
            public $permissionsSeen = [];
            private $admin;
            private $permitted;

            public function __construct($admin, $permitted)
            {
                $this->admin = $admin;
                $this->permitted = $permitted;
            }

            public function isAdmin()
            {
                return $this->admin;
            }

            public function hasPermission($permission)
            {
                $this->permissionsSeen[] = $permission;

                return $this->permitted && $permission === 1001;
            }
        };
    }

    /**
     * @param array  $records
     * @param string $name
     * @return array|null
     */
    private function recorded(array $records, $name)
    {
        foreach ($records as $record) {
            if ($record['hook'] === $name) {
                return $record;
            }
        }

        return null;
    }
}
