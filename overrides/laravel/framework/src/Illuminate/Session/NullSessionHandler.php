<?php

namespace Illuminate\Session;

use SessionHandlerInterface;

class NullSessionHandler implements SessionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        return true;
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
