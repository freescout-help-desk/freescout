<?php

namespace Codedge\Updater\Contracts;

interface UpdaterContract
{
    /**
     * Get a source type instance.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function source($name = '');
}
