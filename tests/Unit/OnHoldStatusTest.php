<?php

namespace Tests\Unit;

use App\Conversation;
use App\Thread;
use Tests\TestCase;

/**
 * Covers the OnHoldStatus module (ARMS-12) and the two fork patches it
 * depends on: the conversation.status_name Eventy filter in the default
 * case of Conversation::statusCodeToName() and Thread::statusCodeToName().
 */
class OnHoldStatusTest extends TestCase
{
    const ONHOLD = 5;

    protected $statuses_backup = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive — load the provider directly.
        require_once __DIR__.'/../../Modules/OnHoldStatus/Providers/OnHoldStatusServiceProvider.php';

        // Static arrays persist across tests in the same process — back them up.
        $this->statuses_backup = [
            'statuses'        => Conversation::$statuses,
            'status_icons'    => Conversation::$status_icons,
            'status_classes'  => Conversation::$status_classes,
            'status_colors'   => Conversation::$status_colors,
            'thread_statuses' => Thread::$statuses,
        ];
    }

    protected function tearDown(): void
    {
        Conversation::$statuses = $this->statuses_backup['statuses'];
        Conversation::$status_icons = $this->statuses_backup['status_icons'];
        Conversation::$status_classes = $this->statuses_backup['status_classes'];
        Conversation::$status_colors = $this->statuses_backup['status_colors'];
        Thread::$statuses = $this->statuses_backup['thread_statuses'];

        parent::tearDown();
    }

    protected function bootModule()
    {
        (new \Modules\OnHoldStatus\Providers\OnHoldStatusServiceProvider(app()))->boot();
    }

    /**
     * Without the module, the fork patch must preserve core behaviour:
     * unknown status codes render as an empty string.
     * (The Eventy singleton is rebuilt per test, so no filter is registered here.)
     */
    public function test_unknown_status_renders_empty_without_module()
    {
        $this->assertSame('', Conversation::statusCodeToName(self::ONHOLD));
        $this->assertSame('', Thread::statusCodeToName(self::ONHOLD));
    }

    public function test_module_registers_on_hold_status()
    {
        $this->bootModule();

        // Name resolution through the fork-patch filter, on both models.
        $this->assertSame('On Hold', Conversation::statusCodeToName(self::ONHOLD));
        $this->assertSame('On Hold', Thread::statusCodeToName(self::ONHOLD));

        // All four Conversation registries + the Thread registry get the status,
        // so Blade's direct array indexing cannot hit an undefined key.
        $this->assertArrayHasKey(self::ONHOLD, Conversation::$statuses);
        $this->assertArrayHasKey(self::ONHOLD, Conversation::$status_icons);
        $this->assertArrayHasKey(self::ONHOLD, Conversation::$status_classes);
        $this->assertArrayHasKey(self::ONHOLD, Conversation::$status_colors);
        $this->assertArrayHasKey(self::ONHOLD, Thread::$statuses);

        // Status-change validation (Conversation::changeStatus) accepts it.
        $this->assertTrue(array_key_exists(self::ONHOLD, Conversation::$statuses));
    }

    /**
     * Dropdowns render registry order — On Hold must sit after Pending,
     * not at the end after Spam (ARMS-14).
     */
    public function test_on_hold_is_ordered_after_pending()
    {
        $this->bootModule();

        $expected = [
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_PENDING,
            self::ONHOLD,
            Conversation::STATUS_CLOSED,
            Conversation::STATUS_SPAM,
        ];

        $this->assertSame($expected, array_keys(Conversation::$statuses));
        $this->assertSame($expected, array_keys(Conversation::$status_icons));
        $this->assertSame($expected, array_keys(Conversation::$status_classes));
        $this->assertSame($expected, array_keys(Conversation::$status_colors));

        // Booting twice must not duplicate or move the entry (idempotency).
        $this->bootModule();
        $this->assertSame($expected, array_keys(Conversation::$statuses));
    }

    public function test_existing_statuses_are_unaffected()
    {
        $this->bootModule();

        $this->assertSame('Pending', Conversation::statusCodeToName(Conversation::STATUS_PENDING));
        $this->assertSame('Active', Conversation::statusCodeToName(Conversation::STATUS_ACTIVE));
        $this->assertSame('Not changed', Thread::statusCodeToName(Thread::STATUS_NOCHANGE));

        // Truly unknown codes still render empty even with the module active.
        $this->assertSame('', Conversation::statusCodeToName(99));
    }

    /**
     * The Mine folder and chat list are live queries filtering on an
     * "open statuses" whitelist — the module must extend it so On-Hold
     * conversations do not vanish from the Mine folder (regression found
     * live on the demo instance, 13 Jul).
     */
    public function test_open_statuses_whitelist_includes_on_hold()
    {
        // Without the module, On-Hold must not be in the whitelist.
        // (assertNotContains rather than asserting the exact default array,
        // so an unrelated module extending the whitelist doesn't break this.)
        $this->assertNotContains(
            self::ONHOLD,
            \Eventy::filter('conversation.open_statuses', [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING])
        );

        $this->bootModule();

        $this->assertContains(
            self::ONHOLD,
            \Eventy::filter('conversation.open_statuses', [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING])
        );
    }

    /**
     * If the module is ever deactivated while status-5 rows exist,
     * getStatus() must fall back gracefully (core guard, Conversation.php:623)
     * so Blade's $status_classes[getStatus()] indexing cannot crash.
     */
    public function test_get_status_falls_back_for_unregistered_codes()
    {
        $conversation = new Conversation();
        $conversation->status = self::ONHOLD;

        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->getStatus());

        $this->bootModule();

        $this->assertSame(self::ONHOLD, $conversation->getStatus());
    }
}
