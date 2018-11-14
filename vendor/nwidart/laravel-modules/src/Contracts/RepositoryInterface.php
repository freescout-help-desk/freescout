<?php

namespace Nwidart\Modules\Contracts;

interface RepositoryInterface
{
    /**
     * Get all modules.
     *
     * @return mixed
     */
    public function all();

    /**
     * Get cached modules.
     *
     * @return array
     */
    public function getCached();

    /**
     * Scan & get all available modules.
     *
     * @return array
     */
    public function scan();

    /**
     * Get modules as modules collection instance.
     *
     * @return \Nwidart\Modules\Collection
     */
    public function toCollection();

    /**
     * Get scanned paths.
     *
     * @return array
     */
    public function getScanPaths();

    /**
     * Get list of enabled modules.
     *
     * @return mixed
     */
    public function enabled();

    /**
     * Get list of disabled modules.
     *
     * @return mixed
     */
    public function disabled();

    /**
     * Get count from all modules.
     *
     * @return int
     */
    public function count();

    /**
     * Get all ordered modules.
     *
     * @return mixed
     */
    public function getOrdered();

    /**
     * Get modules by the given status.
     *
     * @param int $status
     *
     * @return mixed
     */
    public function getByStatus($status);

    /**
     * Find a specific module.
     *
     * @param $name
     *
     * @return mixed
     */
    public function find($name);

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @param $name
     *
     * @return mixed
     */
    public function findOrFail($name);
}
