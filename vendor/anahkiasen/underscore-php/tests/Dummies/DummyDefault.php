<?php
namespace Underscore\Dummies;

use Underscore\Types\Strings;

class DummyDefault extends Strings
{
    /**
     * Get the default value.
     *
     * @return string
     */
    public function getDefault()
    {
        return 'foobar';
    }

    /**
     * How the repository is to be cast to array.
     *
     * @return array
     */
    public function toArray()
    {
        return ['foo', 'bar'];
    }
}
