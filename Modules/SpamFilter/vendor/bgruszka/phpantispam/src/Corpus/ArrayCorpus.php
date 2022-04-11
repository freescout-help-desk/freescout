<?php

namespace PHPAntiSpam\Corpus;

use PHPAntiSpam\Tokenizer\TokenizerInterface;

class ArrayCorpus implements CorpusInterface
{

    protected $messages = [];
    protected $tokenizer;
    protected $lexemes = [];
    public $messagesCount = ['spam' => 0, 'nospam' => 0];

    public function __construct($messages, TokenizerInterface $tokenizer, $options = [])
    {
        $this->messages = $messages;
        $this->tokenizer = $tokenizer;

        // next
        foreach ($this->messages as $message) {
            $this->messagesCount[$message['category']]++;

            $words = $tokenizer->tokenize($message['content']);

            foreach ($words as $word) {
                $word = $this->normalizeWord($word);

                if (isset($options['min_word_length']) && strlen($word) < $options['min_word_length']) {
                    continue;
                }

                $this->updateLexem($word, $message['category']);
            }
        }
    }

    public function updateLexem($word, $category)
    {
        if (!isset($this->lexemes[$word])) {
            $this->lexemes[$word] = ['spam' => 0, 'nospam' => 0];
        }

        $this->lexemes[$word][$category]++;
    }

    public function getLexemes(array $words)
    {
        $lexemes = [];

        foreach($words as $word) {
            if (!isset($this->lexemes[$word])) {
                continue;
            }

            $lexemes[$word] = $this->lexemes[$word];
        }

        return $lexemes;
    }

    public function setLexemes(array $lexemes)
    {
        $this->lexemes = $lexemes;
    }

    public function getTokenizer()
    {
        return $this->tokenizer;
    }

    /**
     * Normalize word
     *
     * @param string $word
     * @return string
     */
    private function normalizeWord($word)
    {
        return strtolower(trim($word));
    }

}

?>
