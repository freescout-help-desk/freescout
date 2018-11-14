<?php
namespace Underscore;

use PHPUnit_Framework_TestCase;

abstract class UnderscoreTestCase extends PHPUnit_Framework_TestCase
{
    public $array = ['foo' => 'bar', 'bis' => 'ter'];
    public $arrayNumbers = [1, 2, 3];
    public $arrayMulti = [
        ['foo' => 'bar', 'bis' => 'ter'],
        ['foo' => 'bar', 'bis' => 'ter'],
        ['bar' => 'foo', 'bis' => 'ter'],
    ];
    public $object;

    /**
     * Restore data just in case.
     */
    public function setUp()
    {
        $this->object = (object) $this->array;
        $this->objectMulti = (object) [
            (object) $this->arrayMulti[0],
            (object) $this->arrayMulti[1],
            (object) $this->arrayMulti[2],
        ];
    }
}
