<?php

namespace Modules\Workflows\Tests\Unit;

use App\User;
use Modules\Workflows\Services\WorkflowUser;
use Tests\TestCase;

class WorkflowUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearWorkflowName();
    }

    protected function tearDown(): void
    {
        $this->clearWorkflowName();
        parent::tearDown();
    }

    public function test_unset_variable_returns_workflow(): void
    {
        $this->clearWorkflowName();

        $this->assertSame('Workflow', WorkflowUser::displayName());
    }

    public function test_empty_string_returns_workflow(): void
    {
        $this->setWorkflowName('');

        $this->assertSame('Workflow', WorkflowUser::displayName());
    }

    public function test_ada_lovelace_is_returned(): void
    {
        $this->setWorkflowName('Ada Lovelace');

        $this->assertSame('Ada Lovelace', WorkflowUser::displayName());
    }

    public function test_ascii_name_is_truncated_to_twenty_characters(): void
    {
        $this->setWorkflowName(str_repeat('A', 25));

        $this->assertSame(str_repeat('A', 20), WorkflowUser::displayName());
    }

    public function test_accented_name_is_truncated_by_character(): void
    {
        $this->setWorkflowName(str_repeat('é', 21));

        $name = WorkflowUser::displayName();

        $this->assertSame(20, mb_strlen($name, 'UTF-8'));
        $this->assertStringStartsWith('é', $name);
        $this->assertSame(str_repeat('é', 20), $name);
    }

    public function test_attributes_hide_the_user_and_truncate_a_long_name(): void
    {
        $this->setWorkflowName(str_repeat('N', 25));

        $attributes = WorkflowUser::attributes();

        $this->assertSame([
            'status' => User::STATUS_DELETED,
            'first_name' => str_repeat('N', 20),
            'email' => 'workflow@localhost',
        ], $attributes);
        $this->assertSame(WorkflowUser::displayName(), $attributes['first_name']);
    }

    /**
     * @param string $name
     * @return void
     */
    private function setWorkflowName(string $name): void
    {
        putenv('WORKFLOWS_USER_FULL_NAME='.$name);
        $_ENV['WORKFLOWS_USER_FULL_NAME'] = $name;
        $_SERVER['WORKFLOWS_USER_FULL_NAME'] = $name;
    }

    /**
     * @return void
     */
    private function clearWorkflowName(): void
    {
        putenv('WORKFLOWS_USER_FULL_NAME');
        unset($_ENV['WORKFLOWS_USER_FULL_NAME'], $_SERVER['WORKFLOWS_USER_FULL_NAME']);
    }
}
