<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\CssSelector\Node;

/**
 * Represents a "<namespace>|<element>" node.
 *
 * This component is a port of the Python cssselect library,
 * which is copyright Ian Bicking, @see https://github.com/SimonSapin/cssselect.
 *
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class ElementNode extends AbstractNode
{
    private $namespace;
    private $element;

    /**
     * @param string|null $namespace
     * @param string|null $element
     */
    public function __construct($namespace = null, $element = null)
    {
        $this->namespace = $namespace;
        $this->element = $element;
    }

    /**
     * @return string|null
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string|null
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * {@inheritdoc}
     */
    public function getSpecificity()
    {
        return new Specificity(0, 0, $this->element ? 1 : 0);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $element = $this->element ?: '*';

        return sprintf('%s[%s]', $this->getNodeName(), $this->namespace ? $this->namespace.'|'.$element : $element);
    }
}
