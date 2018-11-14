<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\FragmentListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\UriSigner;

class FragmentListenerTest extends TestCase
{
    public function testOnlyTriggeredOnFragmentRoute()
    {
        $request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');

        $listener = new FragmentListener(new UriSigner('foo'));
        $event = $this->createGetResponseEvent($request);

        $expected = $request->attributes->all();

        $listener->onKernelRequest($event);

        $this->assertEquals($expected, $request->attributes->all());
        $this->assertTrue($request->query->has('_path'));
    }

    public function testOnlyTriggeredIfControllerWasNotDefinedYet()
    {
        $request = Request::create('http://example.com/_fragment?_path=foo%3Dbar%26_controller%3Dfoo');
        $request->attributes->set('_controller', 'bar');

        $listener = new FragmentListener(new UriSigner('foo'));
        $event = $this->createGetResponseEvent($request, HttpKernelInterface::SUB_REQUEST);

        $expected = $request->attributes->all();

        $listener->onKernelRequest($event);

        $this->assertEquals($expected, $request->attributes->all());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAccessDeniedWithNonSafeMethods()
    {
        $request = Request::create('http://example.com/_fragment', 'POST');

        $listener = new FragmentListener(new UriSigner('foo'));
        $event = $this->createGetResponseEvent($request);

        $listener->onKernelRequest($event);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAccessDeniedWithWrongSignature()
    {
        $request = Request::create('http://example.com/_fragment', 'GET', array(), array(), array(), array('REMOTE_ADDR' => '10.0.0.1'));

        $listener = new FragmentListener(new UriSigner('foo'));
        $event = $this->createGetResponseEvent($request);

        $listener->onKernelRequest($event);
    }

    public function testWithSignature()
    {
        $signer = new UriSigner('foo');
        $request = Request::create($signer->sign('http://example.com/_fragment?_path=foo%3Dbar%26_controller%3Dfoo'), 'GET', array(), array(), array(), array('REMOTE_ADDR' => '10.0.0.1'));

        $listener = new FragmentListener($signer);
        $event = $this->createGetResponseEvent($request);

        $listener->onKernelRequest($event);

        $this->assertEquals(array('foo' => 'bar', '_controller' => 'foo'), $request->attributes->get('_route_params'));
        $this->assertFalse($request->query->has('_path'));
    }

    public function testRemovesPathWithControllerDefined()
    {
        $request = Request::create('http://example.com/_fragment?_path=foo%3Dbar%26_controller%3Dfoo');

        $listener = new FragmentListener(new UriSigner('foo'));
        $event = $this->createGetResponseEvent($request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        $this->assertFalse($request->query->has('_path'));
    }

    public function testRemovesPathWithControllerNotDefined()
    {
        $signer = new UriSigner('foo');
        $request = Request::create($signer->sign('http://example.com/_fragment?_path=foo%3Dbar'), 'GET', array(), array(), array(), array('REMOTE_ADDR' => '10.0.0.1'));

        $listener = new FragmentListener($signer);
        $event = $this->createGetResponseEvent($request);

        $listener->onKernelRequest($event);

        $this->assertFalse($request->query->has('_path'));
    }

    private function createGetResponseEvent(Request $request, $requestType = HttpKernelInterface::MASTER_REQUEST)
    {
        return new GetResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $request, $requestType);
    }
}
