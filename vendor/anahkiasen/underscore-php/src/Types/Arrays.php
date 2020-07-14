<?php
namespace Underscore\Types;

use Underscore\Methods\ArraysMethods;
use Underscore\Traits\Repository;

/**
 * Arrays repository.
 *
 * @mixin ArraysMethods
 */
class Arrays extends Repository
{
    /**
     * The method used to convert new subjects.
     *
     * @type string
     */
    protected $typecaster = 'toArray';

    /**
     * Get a default value for a new repository.
     *
     * @return mixed
     */
    protected function getDefault()
    {
        return [];
    }
}
