<?php

namespace Codedge\Updater\Events;

/**
 * HasWrongPermissions.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class HasWrongPermissions
{
    protected $repository;

    /**
     * UpdateFailed constructor.
     *
     * @param $repository
     */
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
}
