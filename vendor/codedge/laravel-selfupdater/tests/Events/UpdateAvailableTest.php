<?php

namespace Codedge\Updater\Tests\Events;

use Codedge\Updater\Events\UpdateAvailable;
use Codedge\Updater\Tests\TestCase;

class UpdateAvailableTest extends TestCase
{
    /**
     * @var string
     */
    protected $eventName;

    /**
     * @var string
     */
    protected $versionAvailable;

    public function setUp()
    {
        parent::setUp();
        $this->eventName = 'Update available';
        $this->versionAvailable = '1.1.0';
    }

    public function testGetEventName()
    {
        $instance = new UpdateAvailable($this->versionAvailable);
        $this->assertSame($this->eventName, $instance->getEventName());
    }

    public function testGetVersionAvailable()
    {
        $instance = new UpdateAvailable($this->versionAvailable);
        $this->assertSame($this->versionAvailable, $instance->getVersionAvailable());
        $this->assertStringStartsWith('v', $instance->getVersionAvailable('v'));
        $this->assertStringEndsWith('version', $instance->getVersionAvailable('', 'version'));
    }

}