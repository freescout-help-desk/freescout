<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process\Pipes;

use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * @author Romain Neutron <imprec@gmail.com>
 *
 * @internal
 */
abstract class AbstractPipes implements PipesInterface
{
    public $pipes = array();

    private $inputBuffer = '';
    private $input;
    private $blocked = true;
    private $lastError;

    /**
     * @param resource|string|int|float|bool|\Iterator|null $input
     */
    public function __construct($input)
    {
        if (\is_resource($input) || $input instanceof \Iterator) {
            $this->input = $input;
        } elseif (\is_string($input)) {
            $this->inputBuffer = $input;
        } else {
            $this->inputBuffer = (string) $input;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        $this->pipes = array();
    }

    /**
     * Returns true if a system call has been interrupted.
     *
     * @return bool
     */
    protected function hasSystemCallBeenInterrupted()
    {
        $lastError = $this->lastError;
        $this->lastError = null;

        // stream_select returns false when the `select` system call is interrupted by an incoming signal
        return null !== $lastError && false !== stripos($lastError, 'interrupted system call');
    }

    /**
     * Unblocks streams.
     */
    protected function unblock()
    {
        if (!$this->blocked) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, 0);
        }
        if (\is_resource($this->input)) {
            stream_set_blocking($this->input, 0);
        }

        $this->blocked = false;
    }

    /**
     * Writes input to stdin.
     *
     * @throws InvalidArgumentException When an input iterator yields a non supported value
     */
    protected function write()
    {
        if (!isset($this->pipes[0])) {
            return;
        }
        $input = $this->input;

        if ($input instanceof \Iterator) {
            if (!$input->valid()) {
                $input = null;
            } elseif (\is_resource($input = $input->current())) {
                stream_set_blocking($input, 0);
            } elseif (!isset($this->inputBuffer[0])) {
                if (!\is_string($input)) {
                    if (!is_scalar($input)) {
                        throw new InvalidArgumentException(sprintf('%s yielded a value of type "%s", but only scalars and stream resources are supported', \get_class($this->input), \gettype($input)));
                    }
                    $input = (string) $input;
                }
                $this->inputBuffer = $input;
                $this->input->next();
                $input = null;
            } else {
                $input = null;
            }
        }

        $r = $e = array();
        $w = array($this->pipes[0]);

        // let's have a look if something changed in streams
        if (false === @stream_select($r, $w, $e, 0, 0)) {
            return;
        }

        foreach ($w as $stdin) {
            if (isset($this->inputBuffer[0])) {
                $written = fwrite($stdin, $this->inputBuffer);
                $this->inputBuffer = substr($this->inputBuffer, $written);
                if (isset($this->inputBuffer[0])) {
                    return array($this->pipes[0]);
                }
            }

            if ($input) {
                for (;;) {
                    $data = fread($input, self::CHUNK_SIZE);
                    if (!isset($data[0])) {
                        break;
                    }
                    $written = fwrite($stdin, $data);
                    $data = substr($data, $written);
                    if (isset($data[0])) {
                        $this->inputBuffer = $data;

                        return array($this->pipes[0]);
                    }
                }
                if (feof($input)) {
                    if ($this->input instanceof \Iterator) {
                        $this->input->next();
                    } else {
                        $this->input = null;
                    }
                }
            }
        }

        // no input to read on resource, buffer is empty
        if (!isset($this->inputBuffer[0]) && !($this->input instanceof \Iterator ? $this->input->valid() : $this->input)) {
            $this->input = null;
            fclose($this->pipes[0]);
            unset($this->pipes[0]);
        } elseif (!$w) {
            return array($this->pipes[0]);
        }
    }

    /**
     * @internal
     */
    public function handleError($type, $msg)
    {
        $this->lastError = $msg;
    }
}
