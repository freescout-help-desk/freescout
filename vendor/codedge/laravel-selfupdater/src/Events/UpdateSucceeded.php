<?php

namespace Codedge\Updater\Events;

/**
 * UpdateFailed.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class UpdateSucceeded
{
    /**
     * @var string
     */
    protected $eventName = 'Update succeeded';

    /**
     * @var string The version of the new software package.
     */
    protected $versionUpdatedTo;

    /**
     * UpdateFailed constructor.
     *
     * @param $versionUpdatedTo
     */
    public function __construct($versionUpdatedTo)
    {
        $this->versionUpdatedTo = $versionUpdatedTo;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Get the new version.
     *
     * @param string $prepend
     * @param string $append
     *
     * @return string
     */
    public function getVersionUpdatedTo($prepend = '', $append = '')
    {
        return $prepend.$this->versionUpdatedTo.$append;
    }
}
