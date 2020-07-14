<?php

namespace Lord\Laroute\Compilers;

interface CompilerInterface
{
    /**
     * Compile a template with given data.
     *
     * @param $template
     * @param $data
     *
     * @return string
     */
    public function compile($template, $data);
}
