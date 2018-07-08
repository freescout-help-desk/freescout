<?php
namespace Underscore\Types;

use Underscore\Methods\StringsMethods;
use Underscore\Traits\Repository;

/**
 * Strings repository.

 *
*@mixin StringsMethods
 */
class Strings extends Repository
{
    /**
     * The method used to convert new subjects.
     *
     * @type string
     */
    protected $typecaster = 'toString';
}
