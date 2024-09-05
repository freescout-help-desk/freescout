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

use Symfony\Component\CssSelector\Exception\ExpressionErrorException;
use Symfony\Component\CssSelector\Node\FunctionNode;
use Symfony\Component\CssSelector\Node\NodeInterface;
use Symfony\Component\CssSelector\Node\SelectorNode;
use Symfony\Component\CssSelector\Parser\Parser;
use Symfony\Component\CssSelector\Parser\ParserInterface;

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
class Translator implements TranslatorInterface
{
    private $mainParser;

    /**
     * @var ParserInterface[]
     */
    private $shortcutParsers = array();

    /**
     * @var Extension\ExtensionInterface[]
     */
    private $extensions = array();

    private $nodeTranslators = array();
    private $combinationTranslators = array();
    private $functionTranslators = array();
    private $pseudoClassTranslators = array();
    private $attributeMatchingTranslators = array();

    public function __construct(ParserInterface $parser = null)
    {
        $this->mainParser = $parser ?: new Parser();

        $this
            ->registerExtension(new Extension\NodeExtension())
            ->registerExtension(new Extension\CombinationExtension())
            ->registerExtension(new Extension\FunctionExtension())
            ->registerExtension(new Extension\PseudoClassExtension())
            ->registerExtension(new Extension\AttributeMatchingExtension())
        ;
    }

    /**
     * @param string $element
     *
     * @return string
     */
    public static function getXpathLiteral($element)
    {
        if (false === strpos($element, "'")) {
            return "'".$element."'";
        }

        if (false === strpos($element, '"')) {
            return '"'.$element.'"';
        }

        $string = $element;
        $parts = array();
        while (true) {
            if (false !== $pos = strpos($string, "'")) {
                $parts[] = sprintf("'%s'", substr($string, 0, $pos));
                $parts[] = "\"'\"";
                $string = substr($string, $pos + 1);
            } else {
                $parts[] = "'$string'";
                break;
            }
        }

        return sprintf('concat(%s)', implode(', ', $parts));
    }

    /**
     * {@inheritdoc}
     */
    public function cssToXPath($cssExpr, $prefix = 'descendant-or-self::')
    {
        $selectors = $this->parseSelectors($cssExpr);

        /** @var SelectorNode $selector */
        foreach ($selectors as $index => $selector) {
            if (null !== $selector->getPseudoElement()) {
                throw new ExpressionErrorException('Pseudo-elements are not supported.');
            }

            $selectors[$index] = $this->selectorToXPath($selector, $prefix);
        }

        return implode(' | ', $selectors);
    }

    /**
     * {@inheritdoc}
     */
    public function selectorToXPath(SelectorNode $selector, $prefix = 'descendant-or-self::')
    {
        return ($prefix ?: '').$this->nodeToXPath($selector);
    }

    /**
     * Registers an extension.
     *
     * @return $this
     */
    public function registerExtension(Extension\ExtensionInterface $extension)
    {
        $this->extensions[$extension->getName()] = $extension;

        $this->nodeTranslators = array_merge($this->nodeTranslators, $extension->getNodeTranslators());
        $this->combinationTranslators = array_merge($this->combinationTranslators, $extension->getCombinationTranslators());
        $this->functionTranslators = array_merge($this->functionTranslators, $extension->getFunctionTranslators());
        $this->pseudoClassTranslators = array_merge($this->pseudoClassTranslators, $extension->getPseudoClassTranslators());
        $this->attributeMatchingTranslators = array_merge($this->attributeMatchingTranslators, $extension->getAttributeMatchingTranslators());

        return $this;
    }

    /**
     * @param string $name
     *
     * @return Extension\ExtensionInterface
     *
     * @throws ExpressionErrorException
     */
    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new ExpressionErrorException(sprintf('Extension "%s" not registered.', $name));
        }

        return $this->extensions[$name];
    }

    /**
     * Registers a shortcut parser.
     *
     * @return $this
     */
    public function registerParserShortcut(ParserInterface $shortcut)
    {
        $this->shortcutParsers[] = $shortcut;

        return $this;
    }

    /**
     * @return XPathExpr
     *
     * @throws ExpressionErrorException
     */
    public function nodeToXPath(NodeInterface $node)
    {
        if (!isset($this->nodeTranslators[$node->getNodeName()])) {
            throw new ExpressionErrorException(sprintf('Node "%s" not supported.', $node->getNodeName()));
        }

        return \call_user_func($this->nodeTranslators[$node->getNodeName()], $node, $this);
    }

    /**
     * @param string        $combiner
     * @param NodeInterface $xpath
     * @param NodeInterface $combinedXpath
     *
     * @return XPathExpr
     *
     * @throws ExpressionErrorException
     */
    public function addCombination($combiner, NodeInterface $xpath, NodeInterface $combinedXpath)
    {
        if (!isset($this->combinationTranslators[$combiner])) {
            throw new ExpressionErrorException(sprintf('Combiner "%s" not supported.', $combiner));
        }

        return \call_user_func($this->combinationTranslators[$combiner], $this->nodeToXPath($xpath), $this->nodeToXPath($combinedXpath));
    }

    /**
     * @return XPathExpr
     *
     * @throws ExpressionErrorException
     */
    public function addFunction(XPathExpr $xpath, FunctionNode $function)
    {
        if (!isset($this->functionTranslators[$function->getName()])) {
            throw new ExpressionErrorException(sprintf('Function "%s" not supported.', $function->getName()));
        }

        return \call_user_func($this->functionTranslators[$function->getName()], $xpath, $function);
    }

    /**
     * @param XPathExpr $xpath
     * @param string    $pseudoClass
     *
     * @return XPathExpr
     *
     * @throws ExpressionErrorException
     */
    public function addPseudoClass(XPathExpr $xpath, $pseudoClass)
    {
        if (!isset($this->pseudoClassTranslators[$pseudoClass])) {
            throw new ExpressionErrorException(sprintf('Pseudo-class "%s" not supported.', $pseudoClass));
        }

        return \call_user_func($this->pseudoClassTranslators[$pseudoClass], $xpath);
    }

    /**
     * @param XPathExpr $xpath
     * @param string    $operator
     * @param string    $attribute
     * @param string    $value
     *
     * @return XPathExpr
     *
     * @throws ExpressionErrorException
     */
    public function addAttributeMatching(XPathExpr $xpath, $operator, $attribute, $value)
    {
        if (!isset($this->attributeMatchingTranslators[$operator])) {
            throw new ExpressionErrorException(sprintf('Attribute matcher operator "%s" not supported.', $operator));
        }

        return \call_user_func($this->attributeMatchingTranslators[$operator], $xpath, $attribute, $value);
    }

    /**
     * @param string $css
     *
     * @return SelectorNode[]
     */
    private function parseSelectors($css)
    {
        foreach ($this->shortcutParsers as $shortcut) {
            $tokens = $shortcut->parse($css);

            if (!empty($tokens)) {
                return $tokens;
            }
        }

        return $this->mainParser->parse($css);
    }
}
