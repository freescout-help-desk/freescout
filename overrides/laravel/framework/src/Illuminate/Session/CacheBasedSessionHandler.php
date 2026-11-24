<?php

namespace Illuminate\Session;

use SessionHandlerInterface;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CacheBasedSessionHandler implements SessionHandlerInterface
{
    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The number of minutes to store the data in the cache.
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new cache driven handler instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  int  $minutes
     * @return void
     */
    public function __construct(CacheContract $cache, $minutes)
    {
        $this->cache = $cache;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        return $this->cache->get($sessionId, '');
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
    {
        return $this->cache->put($sessionId, $data, $this->minutes);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
    {
        return $this->cache->forget($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Get the underlying cache repository.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Create a new session ID.
     * Required by PHP 8.6+ for classes implementing SessionHandlerInterface (will be mandatory in PHP 9.0).
     *
     * @return string
     */
    public function create_sid(): string
    {
        return \Illuminate\Support\Str::random(40);
    }

    /**
     * Validate a session ID.
     * Required by PHP 8.6+ for classes implementing SessionHandlerInterface (will be mandatory in PHP 9.0).
     *
     * @param  string  $sessionId
     * @return bool
     */
    public function validateId($sessionId): bool
    {
        return true;
    }

    /**
     * Update the session timestamp.
     * Required by PHP 8.6+ for classes implementing SessionHandlerInterface (will be mandatory in PHP 9.0).
     *
     * @param  string  $sessionId
     * @param  string  $data
     * @return bool
     */
    public function updateTimestamp($sessionId, $data): bool
    {
        return true;
    }
}
