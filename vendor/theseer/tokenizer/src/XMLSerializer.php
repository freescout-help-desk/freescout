<?php declare(strict_types = 1);
namespace TheSeer\Tokenizer;

use DOMDocument;

class XMLSerializer {

    /**
     * @var \XMLWriter
     */
    private $writer;

    /**
     * @var Token
     */
    private $previousToken;

    /**
     * @var NamespaceUri
     */
    private $xmlns;

    /**
     * XMLSerializer constructor.
     *
     * @param NamespaceUri $xmlns
     */
    public function __construct(NamespaceUri $xmlns = null) {
        if ($xmlns === null) {
            $xmlns = new NamespaceUri('https://github.com/theseer/tokenizer');
        }
        $this->xmlns = $xmlns;
    }

    /**
     * @param TokenCollection $tokens
     *
     * @return DOMDocument
     */
    public function toDom(TokenCollection $tokens): DOMDocument {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($this->toXML($tokens));

        return $dom;
    }

    /**
     * @param TokenCollection $tokens
     *
     * @return string
     */
    public function toXML(TokenCollection $tokens): string {
        $this->writer = new \XMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent(true);
        $this->writer->startDocument();
        $this->writer->startElement('source');
        $this->writer->writeAttribute('xmlns', $this->xmlns->asString());
        $this->writer->startElement('line');
        $this->writer->writeAttribute('no', '1');

        $this->previousToken = $tokens[0];
        foreach ($tokens as $token) {
            $this->addToken($token);
        }

        $this->writer->endElement();
        $this->writer->endElement();
        $this->writer->endDocument();

        return $this->writer->outputMemory();
    }

    /**
     * @param Token $token
     */
    private function addToken(Token $token) {
        if ($this->previousToken->getLine() < $token->getLine()) {
            $this->writer->endElement();

            $this->writer->startElement('line');
            $this->writer->writeAttribute('no', (string)$token->getLine());
            $this->previousToken = $token;
        }

        if ($token->getValue() !== '') {
            $this->writer->startElement('token');
            $this->writer->writeAttribute('name', $token->getName());
            $this->writer->writeRaw(htmlspecialchars($token->getValue(), ENT_NOQUOTES | ENT_DISALLOWED | ENT_XML1));
            $this->writer->endElement();
        }
    }
}
