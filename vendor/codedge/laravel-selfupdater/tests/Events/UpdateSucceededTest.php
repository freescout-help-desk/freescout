<?php

namespace Codedge\Updater\Tests\Events;

use Codedge\Updater\Events\UpdateSucceeded;
use Codedge\Updater\Tests\TestCase;

class UpdateSucceededTest extends TestCase
{
    /**
     * @var string
     */
    protected $eventName;

    /**
     * @var string
     */
    protected $versionUpdatedTo;

    public function setUp()
    {
        parent::setUp();
        $this->eventName = 'Update succeeded';
        $this->versionUpdatedTo = '1.9.0';
    }

    public function testGetEventName()
    {
        $obj = new UpdateSucceeded($this->versionUpdatedTo);
        $this->assertSame($this->eventName, $obj->getEventName());
    }

    public function testGetVersionAvailable()
    {
        $obj = new UpdateSucceeded($this->versionUpdatedTo);
        $this->assertSame($this->versionUpdatedTo, $obj->getVersionUpdatedTo());
        $this->assertStringStartsWith('v', $obj->getVersionUpdatedTo('v'));
        $this->assertStringEndsWith('version', $obj->getVersionUpdatedTo('', 'version'));
    }
}