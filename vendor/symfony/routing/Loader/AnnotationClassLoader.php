<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * AnnotationClassLoader loads routing information from a PHP class and its methods.
 *
 * You need to define an implementation for the getRouteDefaults() method. Most of the
 * time, this method should define some PHP callable to be called for the route
 * (a controller in MVC speak).
 *
 * The @Route annotation can be set on the class (for global parameters),
 * and on each method.
 *
 * The @Route annotation main value is the route path. The annotation also
 * recognizes several parameters: requirements, options, defaults, schemes,
 * methods, host, and name. The name parameter is mandatory.
 * Here is an example of how you should be able to use it:
 *
 *     /**
 *      * @Route("/Blog")
 *      * /
 *     class Blog
 *     {
 *         /**
 *          * @Route("/", name="blog_index")
 *          * /
 *         public function index()
 *         {
 *         }
 *
 *         /**
 *          * @Route("/{id}", name="blog_post", requirements = {"id" = "\d+"})
 *          * /
 *         public function show()
 *         {
 *         }
 *     }
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AnnotationClassLoader implements LoaderInterface
{
    protected $reader;

    /**
     * @var string
     */
    protected $routeAnnotationClass = 'Symfony\\Component\\Routing\\Annotation\\Route';

    /**
     * @var int
     */
    protected $defaultRouteIndex = 0;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Sets the annotation class to read route properties from.
     *
     * @param string $class A fully-qualified class name
     */
    public function setRouteAnnotationClass($class)
    {
        $this->routeAnnotationClass = $class;
    }

    /**
     * Loads from annotations from a class.
     *
     * @param string      $class A class name
     * @param string|null $type  The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When route can't be parsed
     */
    public function load($class, $type = null)
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $class = new \ReflectionClass($class);
        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Annotations from class "%s" cannot be read as it is abstract.', $class->getName()));
        }

        $globals = $this->getGlobals($class);

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($class->getFileName()));

        foreach ($class->getMethods() as $method) {
            $this->defaultRouteIndex = 0;
            foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                if ($annot instanceof $this->routeAnnotationClass) {
                    $this->addRoute($collection, $annot, $globals, $class, $method);
                }
            }
        }

        if (0 === $collection->count() && $class->hasMethod('__invoke')) {
            foreach ($this->reader->getClassAnnotations($class) as $annot) {
                if ($annot instanceof $this->routeAnnotationClass) {
                    $globals['path'] = '';
                    $globals['name'] = '';

                    $this->addRoute($collection, $annot, $globals, $class, $class->getMethod('__invoke'));
                }
            }
        }

        return $collection;
    }

    protected function addRoute(RouteCollection $collection, $annot, $globals, \ReflectionClass $class, \ReflectionMethod $method)
    {
        $name = $annot->getName();
        if (null === $name) {
            $name = $this->getDefaultRouteName($class, $method);
        }
        $name = $globals['name'].$name;

        $defaults = array_replace($globals['defaults'], $annot->getDefaults());
        foreach ($method->getParameters() as $param) {
            if (false !== strpos($globals['path'].$annot->getPath(), sprintf('{%s}', $param->getName())) && !isset($defaults[$param->getName()]) && $param->isDefaultValueAvailable()) {
                $defaults[$param->getName()] = $param->getDefaultValue();
            }
        }
        $requirements = array_replace($globals['requirements'], $annot->getRequirements());
        $options = array_replace($globals['options'], $annot->getOptions());
        $schemes = array_merge($globals['schemes'], $annot->getSchemes());
        $methods = array_merge($globals['methods'], $annot->getMethods());

        $host = $annot->getHost();
        if (null === $host) {
            $host = $globals['host'];
        }

        $condition = $annot->getCondition();
        if (null === $condition) {
            $condition = $globals['condition'];
        }

        $route = $this->createRoute($globals['path'].$annot->getPath(), $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $this->configureRoute($route, $class, $method, $annot);

        $collection->add($name, $route);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return \is_string($resource) && preg_match('/^(?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/', $resource) && (!$type || 'annotation' === $type);
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
    }

    /**
     * Gets the default route name for a class method.
     *
     * @param \ReflectionClass  $class
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    protected function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method)
    {
        $name = strtolower(str_replace('\\', '_', $class->name).'_'.$method->name);
        if ($this->defaultRouteIndex > 0) {
            $name .= '_'.$this->defaultRouteIndex;
        }
        ++$this->defaultRouteIndex;

        return $name;
    }

    protected function getGlobals(\ReflectionClass $class)
    {
        $globals = array(
            'path' => '',
            'requirements' => array(),
            'options' => array(),
            'defaults' => array(),
            'schemes' => array(),
            'methods' => array(),
            'host' => '',
            'condition' => '',
            'name' => '',
        );

        if ($annot = $this->reader->getClassAnnotation($class, $this->routeAnnotationClass)) {
            if (null !== $annot->getName()) {
                $globals['name'] = $annot->getName();
            }

            if (null !== $annot->getPath()) {
                $globals['path'] = $annot->getPath();
            }

            if (null !== $annot->getRequirements()) {
                $globals['requirements'] = $annot->getRequirements();
            }

            if (null !== $annot->getOptions()) {
                $globals['options'] = $annot->getOptions();
            }

            if (null !== $annot->getDefaults()) {
                $globals['defaults'] = $annot->getDefaults();
            }

            if (null !== $annot->getSchemes()) {
                $globals['schemes'] = $annot->getSchemes();
            }

            if (null !== $annot->getMethods()) {
                $globals['methods'] = $annot->getMethods();
            }

            if (null !== $annot->getHost()) {
                $globals['host'] = $annot->getHost();
            }

            if (null !== $annot->getCondition()) {
                $globals['condition'] = $annot->getCondition();
            }
        }

        return $globals;
    }

    protected function createRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition)
    {
        return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
    }

    abstract protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot);
}
