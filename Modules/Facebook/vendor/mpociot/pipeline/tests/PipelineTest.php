<?php

namespace Mpociot\Pipeline\Tests;

use PHPUnit\Framework\TestCase;
use Mpociot\Pipeline\Pipeline;

class PipelineTest extends TestCase
{
    public function testPipelineBasicUsage()
    {
        $pipeOne = function ($piped, $next) {
            $_SERVER['__test.pipe.one'] = $piped;

            return $next($piped);
        };
        $pipeTwo = function ($piped, $next) {
            $_SERVER['__test.pipe.two'] = $piped;

            return $next($piped);
        };

        $result = (new Pipeline())
                    ->send('foo')
                    ->through([$pipeOne, $pipeTwo])
                    ->then(function ($piped) {
                        return $piped;
                    });

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);
        $this->assertEquals('foo', $_SERVER['__test.pipe.two']);

        unset($_SERVER['__test.pipe.one']);
        unset($_SERVER['__test.pipe.two']);
    }

    public function testPipelineUsageWithObjects()
    {
        $result = (new Pipeline())
            ->send('foo')
            ->through([new PipelineTestPipeOne])
            ->then(function ($piped) {
                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testPipelineUsageWithParameters()
    {
        $parameters = ['one', 'two'];

        $result = (new Pipeline())
            ->send('foo')
            ->through('Mpociot\Pipeline\Tests\PipelineTestParameterPipe:'.implode(',', $parameters))
            ->then(function ($piped) {
                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals($parameters, $_SERVER['__test.pipe.parameters']);

        unset($_SERVER['__test.pipe.parameters']);
    }

    public function testPipelineUsageWithMultiplePassables()
    {

        $pipeOne = function ($piped, $pipedTwo, $next) {
            $_SERVER['__test.pipe.one_1'] = $piped;
            $_SERVER['__test.pipe.one_2'] = $pipedTwo;

            return $next($piped, $pipedTwo);
        };
        $pipeTwo = function ($piped, $pipedTwo, $next) {
            $_SERVER['__test.pipe.two_1'] = $piped;
            $_SERVER['__test.pipe.two_2'] = $pipedTwo;

            return $next($piped, $pipedTwo);
        };

        $result = (new Pipeline())
            ->send('foo', 'bar')
            ->through([$pipeOne, $pipeTwo])
            ->then(function ($piped, $pipedTwo) {
                return [$piped, $pipedTwo];
            });

        $this->assertEquals(['foo', 'bar'], $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one_1']);
        $this->assertEquals('bar', $_SERVER['__test.pipe.one_2']);
        $this->assertEquals('foo', $_SERVER['__test.pipe.two_1']);
        $this->assertEquals('bar', $_SERVER['__test.pipe.two_2']);

        unset($_SERVER['__test.pipe.one_1']);
        unset($_SERVER['__test.pipe.one_2']);
        unset($_SERVER['__test.pipe.two_1']);
        unset($_SERVER['__test.pipe.two_2']);
    }

    public function testPipelineUsageWithMultipleParameters()
    {

        $pipeOne = function ($piped, $next, $pipedTwo) {
            $_SERVER['__test.pipe.one_1'] = $piped;
            $_SERVER['__test.pipe.one_2'] = $pipedTwo;

            return $next($piped);
        };
        $pipeTwo = function ($piped, $next, $pipedTwo) {
            $_SERVER['__test.pipe.two_1'] = $piped;
            $_SERVER['__test.pipe.two_2'] = $pipedTwo;

            return $next($piped);
        };

        $result = (new Pipeline())
            ->send('foo')
            ->through([$pipeOne, $pipeTwo])
            ->with('bar')
            ->then(function ($piped) {
                return $piped;
            });

        $this->assertEquals('foo', $result);
        $this->assertEquals('foo', $_SERVER['__test.pipe.one_1']);
        $this->assertEquals('bar', $_SERVER['__test.pipe.one_2']);
        $this->assertEquals('foo', $_SERVER['__test.pipe.two_1']);
        $this->assertEquals('bar', $_SERVER['__test.pipe.two_2']);

        unset($_SERVER['__test.pipe.one_1']);
        unset($_SERVER['__test.pipe.one_2']);
        unset($_SERVER['__test.pipe.two_1']);
        unset($_SERVER['__test.pipe.two_2']);
    }

    public function testPipelineViaChangesTheMethodBeingCalledOnThePipes()
    {
        $pipelineInstance = new Pipeline();
        $result = $pipelineInstance->send('data')
            ->through(new PipelineTestPipeOne)
            ->via('differentMethod')
            ->then(function ($piped) {
                return $piped;
            });
        $this->assertEquals('data', $result);
    }
}

class PipelineTestPipeOne
{
    public function handle($piped, $next)
    {
        $_SERVER['__test.pipe.one'] = $piped;

        return $next($piped);
    }

    public function differentMethod($piped, $next)
    {
        return $next($piped);
    }
}

class PipelineTestParameterPipe
{
    public function handle($piped, $next, $parameter1 = null, $parameter2 = null)
    {
        $_SERVER['__test.pipe.parameters'] = [$parameter1, $parameter2];

        return $next($piped);
    }
}