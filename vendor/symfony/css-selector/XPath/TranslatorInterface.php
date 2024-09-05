<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\CssSelector\XPath;

use Symfony\Component\CssSelector\Node\SelectorNode;

/**
 * XPath expression translator interface.
 *
 * This component is a port of the Python cssselect library,
 * which is copyright Ian Bicking, @see https://github.com/SimonSapin/cssselect.
 *
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
interface TranslatorInterface
{
    /**
     * Translates a CSS selector to an XPath expression.
     *
     * @param string $cssExpr
     * @param string $prefix
     *
     * @return string
     */
    public function cssToXPath($cssExpr, $prefix = 'descendant-or-self::');

    /**
     * Translates a parsed selector node to an XPath expression.
     *
     * @param SelectorNode $selector
     * @param string       $prefix
     *
     * @return string
     */
    public function selectorToXPath(SelectorNode $selector, $prefix = 'descendant-or-self::');
}
