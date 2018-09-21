<?php
namespace Underscore\Dummies;

use Underscore\Types\Arrays;

class DummyClass extends Arrays
{
    /**
     * Get the core data.
     *
     * @return self
     */
    public function getUsers()
    {
        $users = [
            ['foo' => 'bar'],
            ['bar' => 'foo'],
        ];

        return $this->setSubject($users);
    }

    /**
     * Overwrite of the map method.
     *
     * @param mixed $whatever
     *
     * @return self
     */
    public function map($whatever)
    {
        $this->subject = $whatever * 3;

        return $this;
    }
}
