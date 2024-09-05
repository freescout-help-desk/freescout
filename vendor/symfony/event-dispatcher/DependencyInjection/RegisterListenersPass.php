<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\EventDispatcher\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Compiler pass to register tagged services for an event dispatcher.
 */
class RegisterListenersPass implements CompilerPassInterface
{
    protected $dispatcherService;
    protected $listenerTag;
    protected $subscriberTag;

    private $hotPathEvents = array();
    private $hotPathTagName;

    /**
     * @param string $dispatcherService Service name of the event dispatcher in processed container
     * @param string $listenerTag       Tag name used for listener
     * @param string $subscriberTag     Tag name used for subscribers
     */
    public function __construct($dispatcherService = 'event_dispatcher', $listenerTag = 'kernel.event_listener', $subscriberTag = 'kernel.event_subscriber')
    {
        $this->dispatcherService = $dispatcherService;
        $this->listenerTag = $listenerTag;
        $this->subscriberTag = $subscriberTag;
    }

    public function setHotPathEvents(array $hotPathEvents, $tagName = 'container.hot_path')
    {
        $this->hotPathEvents = array_flip($hotPathEvents);
        $this->hotPathTagName = $tagName;

        return $this;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->dispatcherService) && !$container->hasAlias($this->dispatcherService)) {
            return;
        }

        $definition = $container->findDefinition($this->dispatcherService);

        foreach ($container->findTaggedServiceIds($this->listenerTag, true) as $id => $events) {
            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $this->listenerTag));
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace_callback(array(
                        '/(?<=\b)[a-z]/i',
                        '/[^a-z0-9]/i',
                    ), function ($matches) { return strtoupper($matches[0]); }, $event['event']);
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                }

                $definition->addMethodCall('addListener', array($event['event'], array(new ServiceClosureArgument(new Reference($id)), $event['method']), $priority));

                if (isset($this->hotPathEvents[$event['event']])) {
                    $container->getDefinition($id)->addTag($this->hotPathTagName);
                }
            }
        }

        $extractingDispatcher = new ExtractingEventDispatcher();

        foreach ($container->findTaggedServiceIds($this->subscriberTag, true) as $id => $attributes) {
            $def = $container->getDefinition($id);

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $def->getClass();

            if (!$r = $container->getReflectionClass($class)) {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $id));
            }
            if (!$r->isSubclassOf(EventSubscriberInterface::class)) {
                throw new InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, EventSubscriberInterface::class));
            }
            $class = $r->name;

            ExtractingEventDispatcher::$subscriber = $class;
            $extractingDispatcher->addSubscriber($extractingDispatcher);
            foreach ($extractingDispatcher->listeners as $args) {
                $args[1] = array(new ServiceClosureArgument(new Reference($id)), $args[1]);
                $definition->addMethodCall('addListener', $args);

                if (isset($this->hotPathEvents[$args[0]])) {
                    $container->getDefinition($id)->addTag('container.hot_path');
                }
            }
            $extractingDispatcher->listeners = array();
        }
    }
}

/**
 * @internal
 */
class ExtractingEventDispatcher extends EventDispatcher implements EventSubscriberInterface
{
    public $listeners = array();

    public static $subscriber;

    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->listeners[] = array($eventName, $listener[1], $priority);
    }

    public static function getSubscribedEvents()
    {
        $callback = array(self::$subscriber, 'getSubscribedEvents');

        return $callback();
    }
}
