<?php

namespace Codedge\Updater\Events;

/**
 * UpdateFailed.php.
 *
 * @author Holger Lösken <holger.loesken@codedge.de>
 * @copyright See LICENSE file that was distributed with this source code.
 */
class UpdateAvailable
{
    /**
     * @var string
     */
    protected $eventName = 'Update available';

    /**
     * @var string
     */
    protected $versionAvailable;

    /**
     * UpdateFailed constructor.
     *
     * @param string $versionAvailable
     */
    public function __construct($versionAvailable)
    {
        $this->versionAvailable = $versionAvailable;
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
    public function getVersionAvailable($prepend = '', $append = '')
    {
        return $prepend.$this->versionAvailable.$append;
    }
}
