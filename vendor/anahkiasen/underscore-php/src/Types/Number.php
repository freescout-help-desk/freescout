<?php
namespace Underscore\Types;

use Underscore\Methods\NumberMethods;
use Underscore\Traits\Repository;

/**
 * Number repository.
 *
 * @mixin NumberMethods
 */
class Number extends Repository
{
    /**
     * The method used to convert new subjects.
     *
     * @type string
     */
    protected $typecaster = 'toInteger';

    /**
     * Get a default value for a new repository.
     *
     * @return mixed
     */
    protected function getDefault()
    {
        return 0;
    }
}
