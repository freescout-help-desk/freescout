<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Storage\Handler;

@trigger_error(sprintf('The class %s is deprecated since Symfony 3.4 and will be removed in 4.0. Use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler instead.', MemcacheSessionHandler::class), E_USER_DEPRECATED);

/**
 * @author Drak <drak@zikula.org>
 *
 * @deprecated since version 3.4, to be removed in 4.0. Use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler instead.
 */
class MemcacheSessionHandler implements \SessionHandlerInterface
{
    private $memcache;

    /**
     * @var int Time to live in seconds
     */
    private $ttl;

    /**
     * @var string Key prefix for shared environments
     */
    private $prefix;

    /**
     * Constructor.
     *
     * List of available options:
     *  * prefix: The prefix to use for the memcache keys in order to avoid collision
     *  * expiretime: The time to live in seconds
     *
     * @param \Memcache $memcache A \Memcache instance
     * @param array     $options  An associative array of Memcache options
     *
     * @throws \InvalidArgumentException When unsupported options are passed
     */
    public function __construct(\Memcache $memcache, array $options = array())
    {
        if ($diff = array_diff(array_keys($options), array('prefix', 'expiretime'))) {
            throw new \InvalidArgumentException(sprintf('The following options are not supported "%s"', implode(', ', $diff)));
        }

        $this->memcache = $memcache;
        $this->ttl = isset($options['expiretime']) ? (int) $options['expiretime'] : 86400;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2s';
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return $this->memcache->get($this->prefix.$sessionId) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return $this->memcache->set($this->prefix.$sessionId, $data, 0, time() + $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->memcache->delete($this->prefix.$sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // not required here because memcache will auto expire the records anyhow.
        return true;
    }

    /**
     * Return a Memcache instance.
     *
     * @return \Memcache
     */
    protected function getMemcache()
    {
        return $this->memcache;
    }
}
