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

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Configures errors and exceptions handlers.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DebugHandlersListener implements EventSubscriberInterface
{
    private $exceptionHandler;
    private $logger;
    private $levels;
    private $throwAt;
    private $scream;
    private $fileLinkFormat;
    private $scope;
    private $firstCall = true;
    private $hasTerminatedWithException;

    /**
     * @param callable|null        $exceptionHandler A handler that will be called on Exception
     * @param LoggerInterface|null $logger           A PSR-3 logger
     * @param array|int            $levels           An array map of E_* to LogLevel::* or an integer bit field of E_* constants
     * @param int|null             $throwAt          Thrown errors in a bit field of E_* constants, or null to keep the current value
     * @param bool                 $scream           Enables/disables screaming mode, where even silenced errors are logged
     * @param string|array         $fileLinkFormat   The format for links to source files
     * @param bool                 $scope            Enables/disables scoping mode
     */
    public function __construct(callable $exceptionHandler = null, LoggerInterface $logger = null, $levels = E_ALL, $throwAt = E_ALL, $scream = true, $fileLinkFormat = null, $scope = true)
    {
        $this->exceptionHandler = $exceptionHandler;
        $this->logger = $logger;
        $this->levels = null === $levels ? E_ALL : $levels;
        $this->throwAt = is_numeric($throwAt) ? (int) $throwAt : (null === $throwAt ? null : ($throwAt ? E_ALL : null));
        $this->scream = (bool) $scream;
        $this->fileLinkFormat = $fileLinkFormat;
        $this->scope = (bool) $scope;
    }

    /**
     * Configures the error handler.
     */
    public function configure(Event $event = null)
    {
        if (!$event instanceof KernelEvent ? !$this->firstCall : !$event->isMasterRequest()) {
            return;
        }
        $this->firstCall = $this->hasTerminatedWithException = false;

        $handler = set_exception_handler('var_dump');
        $handler = \is_array($handler) ? $handler[0] : null;
        restore_exception_handler();

        if ($this->logger || null !== $this->throwAt) {
            if ($handler instanceof ErrorHandler) {
                if ($this->logger) {
                    $handler->setDefaultLogger($this->logger, $this->levels);
                    if (\is_array($this->levels)) {
                        $levels = 0;
                        foreach ($this->levels as $type => $log) {
                            $levels |= $type;
                        }
                    } else {
                        $levels = $this->levels;
                    }
                    if ($this->scream) {
                        $handler->screamAt($levels);
                    }
                    if ($this->scope) {
                        $handler->scopeAt($levels & ~E_USER_DEPRECATED & ~E_DEPRECATED);
                    } else {
                        $handler->scopeAt(0, true);
                    }
                    $this->logger = $this->levels = null;
                }
                if (null !== $this->throwAt) {
                    $handler->throwAt($this->throwAt, true);
                }
            }
        }
        if (!$this->exceptionHandler) {
            if ($event instanceof KernelEvent) {
                if (method_exists($kernel = $event->getKernel(), 'terminateWithException')) {
                    $request = $event->getRequest();
                    $hasRun = &$this->hasTerminatedWithException;
                    $this->exceptionHandler = function (\Exception $e) use ($kernel, $request, &$hasRun) {
                        if ($hasRun) {
                            throw $e;
                        }
                        $hasRun = true;
                        $kernel->terminateWithException($e, $request);
                    };
                }
            } elseif ($event instanceof ConsoleEvent && $app = $event->getCommand()->getApplication()) {
                $output = $event->getOutput();
                if ($output instanceof ConsoleOutputInterface) {
                    $output = $output->getErrorOutput();
                }
                $this->exceptionHandler = function ($e) use ($app, $output) {
                    $app->renderException($e, $output);
                };
            }
        }
        if ($this->exceptionHandler) {
            if ($handler instanceof ErrorHandler) {
                $h = $handler->setExceptionHandler('var_dump');
                if (\is_array($h) && $h[0] instanceof ExceptionHandler) {
                    $handler->setExceptionHandler($h);
                    $handler = $h[0];
                } else {
                    $handler->setExceptionHandler($this->exceptionHandler);
                }
            }
            if ($handler instanceof ExceptionHandler) {
                $handler->setHandler($this->exceptionHandler);
                if (null !== $this->fileLinkFormat) {
                    $handler->setFileLinkFormat($this->fileLinkFormat);
                }
            }
            $this->exceptionHandler = null;
        }
    }

    public static function getSubscribedEvents()
    {
        $events = array(KernelEvents::REQUEST => array('configure', 2048));

        if ('cli' === \PHP_SAPI && \defined('Symfony\Component\Console\ConsoleEvents::COMMAND')) {
            $events[ConsoleEvents::COMMAND] = array('configure', 2048);
        }

        return $events;
    }
}
