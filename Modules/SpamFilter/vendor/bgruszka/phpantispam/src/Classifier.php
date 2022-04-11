<?php

namespace PHPAntiSpam;

use PHPAntiSpam\Method\MethodInterface;

class Classifier
{
    /** @var  MethodInterface */
    protected $method;

    public function setMethod(MethodInterface $method)
    {
        $this->method = $method;
    }

    public function isSpam($text)
    {
        return $this->method->calculate($text);
    }
}

?>
