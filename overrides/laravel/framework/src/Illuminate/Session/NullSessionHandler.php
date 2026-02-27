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
}
