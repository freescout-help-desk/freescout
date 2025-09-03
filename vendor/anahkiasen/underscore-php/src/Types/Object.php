<?php
namespace Underscore\Types;

use stdClass;
use Underscore\Methods\ObjectMethods;
use Underscore\Traits\Repository;

/**
 * Object repository.
 *
 * @mixin ObjectMethods
 */
class Object extends Repository
{
    /**
     * The method used to convert new subjects.
     *
     * @type string
     */
    protected $typecaster = 'toObject';

    /**
     * Get a default value for a new repository.
     *
     * @return mixed
     */
    protected function getDefault()
    {
        return new stdClass();
    }
}
