<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This implementation uses the '_controller' request attribute to determine
 * the controller to execute and uses the request attributes to determine
 * the controller method arguments.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ControllerResolver implements ArgumentResolverInterface, ControllerResolverInterface
{
    private $logger;

    /**
     * If the ...$arg functionality is available.
     *
     * Requires at least PHP 5.6.0 or HHVM 3.9.1
     *
     * @var bool
     */
    private $supportsVariadic;

    /**
     * If scalar types exists.
     *
     * @var bool
     */
    private $supportsScalarTypes;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        $this->supportsVariadic = method_exists('ReflectionParameter', 'isVariadic');
        $this->supportsScalarTypes = method_exists('ReflectionParameter', 'getType');
    }

    /**
     * {@inheritdoc}
     *
     * This method looks for a '_controller' request attribute that represents
     * the controller name (a string like ClassName::MethodName).
     */
    public function getController(Request $request)
    {
        if (!$controller = $request->attributes->get('_controller')) {
            if (null !== $this->logger) {
                $this->logger->warning('Unable to look for the controller as the "_controller" parameter is missing.');
            }

            return false;
        }

        if (\is_array($controller)) {
            return $controller;
        }

        if (\is_object($controller)) {
            if (method_exists($controller, '__invoke')) {
                return $controller;
            }

            throw new \InvalidArgumentException(sprintf('Controller "%s" for URI "%s" is not callable.', \get_class($controller), $request->getPathInfo()));
        }

        if (false === strpos($controller, ':')) {
            if (method_exists($controller, '__invoke')) {
                return $this->instantiateController($controller);
            } elseif (\function_exists($controller)) {
                return $controller;
            }
        }

        $callable = $this->createController($controller);

        if (!\is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable. %s', $request->getPathInfo(), $this->getControllerError($callable)));
        }

        return $callable;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This method is deprecated as of 3.1 and will be removed in 4.0. Implement the ArgumentResolverInterface and inject it in the HttpKernel instead.
     */
    public function getArguments(Request $request, $controller)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated as of 3.1 and will be removed in 4.0. Implement the %s and inject it in the HttpKernel instead.', __METHOD__, ArgumentResolverInterface::class), E_USER_DEPRECATED);

        if (\is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (\is_object($controller) && !$controller instanceof \Closure) {
            $r = new \ReflectionObject($controller);
            $r = $r->getMethod('__invoke');
        } else {
            $r = new \ReflectionFunction($controller);
        }

        return $this->doGetArguments($request, $controller, $r->getParameters());
    }

    /**
     * @param Request                $request
     * @param callable               $controller
     * @param \ReflectionParameter[] $parameters
     *
     * @return array The arguments to use when calling the action
     *
     * @deprecated This method is deprecated as of 3.1 and will be removed in 4.0. Implement the ArgumentResolverInterface and inject it in the HttpKernel instead.
     */
    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated as of 3.1 and will be removed in 4.0. Implement the %s and inject it in the HttpKernel instead.', __METHOD__, ArgumentResolverInterface::class), E_USER_DEPRECATED);

        $attributes = $request->attributes->all();
        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes)) {
                if ($this->supportsVariadic && $param->isVariadic() && \is_array($attributes[$param->name])) {
                    $arguments = array_merge($arguments, array_values($attributes[$param->name]));
                } else {
                    $arguments[] = $attributes[$param->name];
                }
            } elseif ($param->getClass() && $param->getClass()->isInstance($request)) {
                $arguments[] = $request;
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } elseif ($this->supportsScalarTypes && $param->hasType() && $param->allowsNull()) {
                $arguments[] = null;
            } else {
                if (\is_array($controller)) {
                    $repr = sprintf('%s::%s()', \get_class($controller[0]), $controller[1]);
                } elseif (\is_object($controller)) {
                    $repr = \get_class($controller);
                } else {
                    $repr = $controller;
                }

                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }

        return $arguments;
    }

    /**
     * Returns a callable for the given controller.
     *
     * @param string $controller A Controller string
     *
     * @return callable A PHP callable
     *
     * @throws \InvalidArgumentException
     */
    protected function createController($controller)
    {
        if (false === strpos($controller, '::')) {
            throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
        }

        list($class, $method) = explode('::', $controller, 2);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        return array($this->instantiateController($class), $method);
    }

    /**
     * Returns an instantiated controller.
     *
     * @param string $class A class name
     *
     * @return object
     */
    protected function instantiateController($class)
    {
        return new $class();
    }

    private function getControllerError($callable)
    {
        if (\is_string($callable)) {
            if (false !== strpos($callable, '::')) {
                $callable = explode('::', $callable);
            }

            if (class_exists($callable) && !method_exists($callable, '__invoke')) {
                return sprintf('Class "%s" does not have a method "__invoke".', $callable);
            }

            if (!\function_exists($callable)) {
                return sprintf('Function "%s" does not exist.', $callable);
            }
        }

        if (!\is_array($callable)) {
            return sprintf('Invalid type for controller given, expected string or array, got "%s".', \gettype($callable));
        }

        if (2 !== \count($callable)) {
            return 'Invalid format for controller, expected array(controller, method) or controller::method.';
        }

        list($controller, $method) = $callable;

        if (\is_string($controller) && !class_exists($controller)) {
            return sprintf('Class "%s" does not exist.', $controller);
        }

        $className = \is_object($controller) ? \get_class($controller) : $controller;

        if (method_exists($controller, $method)) {
            return sprintf('Method "%s" on class "%s" should be public and non-abstract.', $method, $className);
        }

        $collection = get_class_methods($controller);

        $alternatives = array();

        foreach ($collection as $item) {
            $lev = levenshtein($method, $item);

            if ($lev <= \strlen($method) / 3 || false !== strpos($item, $method)) {
                $alternatives[] = $item;
            }
        }

        asort($alternatives);

        $message = sprintf('Expected method "%s" on class "%s"', $method, $className);

        if (\count($alternatives) > 0) {
            $message .= sprintf(', did you mean "%s"?', implode('", "', $alternatives));
        } else {
            $message .= sprintf('. Available methods: "%s".', implode('", "', $collection));
        }

        return $message;
    }
}
