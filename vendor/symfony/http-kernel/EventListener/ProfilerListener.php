<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * ProfilerListener collects data for the current request by listening to the kernel events.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ProfilerListener implements EventSubscriberInterface
{
    protected $profiler;
    protected $matcher;
    protected $onlyException;
    protected $onlyMasterRequests;
    protected $exception;
    protected $profiles;
    protected $requestStack;
    protected $parents;

    /**
     * @param Profiler                     $profiler           A Profiler instance
     * @param RequestStack                 $requestStack       A RequestStack instance
     * @param RequestMatcherInterface|null $matcher            A RequestMatcher instance
     * @param bool                         $onlyException      True if the profiler only collects data when an exception occurs, false otherwise
     * @param bool                         $onlyMasterRequests True if the profiler only collects data when the request is a master request, false otherwise
     */
    public function __construct(Profiler $profiler, RequestStack $requestStack, RequestMatcherInterface $matcher = null, $onlyException = false, $onlyMasterRequests = false)
    {
        $this->profiler = $profiler;
        $this->matcher = $matcher;
        $this->onlyException = (bool) $onlyException;
        $this->onlyMasterRequests = (bool) $onlyMasterRequests;
        $this->profiles = new \SplObjectStorage();
        $this->parents = new \SplObjectStorage();
        $this->requestStack = $requestStack;
    }

    /**
     * Handles the onKernelException event.
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->onlyMasterRequests && !$event->isMasterRequest()) {
            return;
        }

        $this->exception = $event->getException();
    }

    /**
     * Handles the onKernelResponse event.
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $master = $event->isMasterRequest();
        if ($this->onlyMasterRequests && !$master) {
            return;
        }

        if ($this->onlyException && null === $this->exception) {
            return;
        }

        $request = $event->getRequest();
        $exception = $this->exception;
        $this->exception = null;

        if (null !== $this->matcher && !$this->matcher->matches($request)) {
            return;
        }

        if (!$profile = $this->profiler->collect($request, $event->getResponse(), $exception)) {
            return;
        }

        $this->profiles[$request] = $profile;

        $this->parents[$request] = $this->requestStack->getParentRequest();
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        // attach children to parents
        foreach ($this->profiles as $request) {
            if (null !== $parentRequest = $this->parents[$request]) {
                if (isset($this->profiles[$parentRequest])) {
                    $this->profiles[$parentRequest]->addChild($this->profiles[$request]);
                }
            }
        }

        // save profiles
        foreach ($this->profiles as $request) {
            $this->profiler->saveProfile($this->profiles[$request]);
        }

        $this->profiles = new \SplObjectStorage();
        $this->parents = new \SplObjectStorage();
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse', -100),
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => array('onKernelTerminate', -1024),
        );
    }
}
