<?php

namespace Mews\Purifier\Casts;

trait WithConfig
{
    /**
     * @var mixed
     */
    protected $config;

    public function __construct($config = null)
    {
        $this->config = $config;
    }
}
