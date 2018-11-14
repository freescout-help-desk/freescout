<?php

namespace Codedge\Updater\Tests;

use Codedge\Updater\UpdaterFacade;
use ReflectionClass;

class UpdaterFacadeTest extends TestCase
{
    public function testGetFacadeAccessor()
    {
        $accessor = 'updater';
        $class = UpdaterFacade::class;

        $reflection = new ReflectionClass($class);

        $method = $reflection->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        $msg = "Expected class '$class' to have an accessor of '$accessor'.";

        $this->assertSame($accessor, $method->invoke(null), $msg);
    }

}