<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Pipes\PipesInterface;
use Symfony\Component\Process\Pipes\UnixPipes;
use Symfony\Component\Process\Pipes\WindowsPipes;

/**
 * Process is a thin wrapper around proc_* functions to easily
 * start independent PHP processes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Romain Neutron <imprec@gmail.com>
 */
class Process implements \IteratorAggregate
{
    const ERR = 'err';
    const OUT = 'out';

    const STATUS_READY = 'ready';
    const STATUS_STARTED = 'started';
    const STATUS_TERMINATED = 'terminated';

    const STDIN = 0;
    const STDOUT = 1;
    const STDERR = 2;

    // Timeout Precision in seconds.
    const TIMEOUT_PRECISION = 0.2;

    const ITER_NON_BLOCKING = 1; // By default, iterating over outputs is a blocking call, use this flag to make it non-blocking
    const ITER_KEEP_OUTPUT = 2;  // By default, outputs are cleared while iterating, use this flag to keep them in memory
    const ITER_SKIP_OUT = 4;     // Use this flag to skip STDOUT while iterating
    const ITER_SKIP_ERR = 8;     // Use this flag to skip STDERR while iterating

    private $callback;
    private $hasCallback = false;
    private $commandline;
    private $cwd;
    private $env;
    private $input;
    private $starttime;
    private $lastOutputTime;
    private $timeout;
    private $idleTimeout;
    private $options = array('suppress_errors' => true);
    private $exitcode;
    private $fallbackStatus = array();
    private $processInformation;
    private $outputDisabled = false;
    private $stdout;
    private $stderr;
    private $enhanceWindowsCompatibility = true;
    private $enhanceSigchildCompatibility;
    private $process;
    private $status = self::STATUS_READY;
    private $incrementalOutputOffset = 0;
    private $incrementalErrorOutputOffset = 0;
    private $tty;
    private $pty;
    private $inheritEnv = false;

    private $useFileHandles = false;
    /** @var PipesInterface */
    private $processPipes;

    private $latestSignal;

    private static $sigchild;

    /**
     * Exit codes translation table.
     *
     * User-defined errors must use exit codes in the 64-113 range.
     */
    public static $exitCodes = array(
        0 => 'OK',
        1 => 'General error',
        2 => 'Misuse of shell builtins',

        126 => 'Invoked command cannot execute',
        127 => 'Command not found',
        128 => 'Invalid exit argument',

        // signals
        129 => 'Hangup',
        130 => 'Interrupt',
        131 => 'Quit and dump core',
        132 => 'Illegal instruction',
        133 => 'Trace/breakpoint trap',
        134 => 'Process aborted',
        135 => 'Bus error: "access to undefined portion of memory object"',
        136 => 'Floating point exception: "erroneous arithmetic operation"',
        137 => 'Kill (terminate immediately)',
        138 => 'User-defined 1',
        139 => 'Segmentation violation',
        140 => 'User-defined 2',
        141 => 'Write to pipe with no one reading',
        142 => 'Signal raised by alarm',
        143 => 'Termination (request to terminate)',
        // 144 - not defined
        145 => 'Child process terminated, stopped (or continued*)',
        146 => 'Continue if stopped',
        147 => 'Stop executing temporarily',
        148 => 'Terminal stop signal',
        149 => 'Background process attempting to read from tty ("in")',
        150 => 'Background process attempting to write to tty ("out")',
        151 => 'Urgent data available on socket',
        152 => 'CPU time limit exceeded',
        153 => 'File size limit exceeded',
        154 => 'Signal raised by timer counting virtual time: "virtual timer expired"',
        155 => 'Profiling timer expired',
        // 156 - not defined
        157 => 'Pollable event',
        // 158 - not defined
        159 => 'Bad syscall',
    );

    /**
     * @param string|array   $commandline The command line to run
     * @param string|null    $cwd         The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env         The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input       The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout     The timeout in seconds or null to disable
     * @param array          $options     An array of options for proc_open
     *
     * @throws RuntimeException When proc_open is not installed
     */
    public function __construct($commandline, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = null)
    {
        if (!\function_exists('proc_open')) {
            throw new RuntimeException('The Process class relies on proc_open, which is not available on your PHP installation.');
        }

        $this->commandline = $commandline;
        $this->cwd = $cwd;

        // on Windows, if the cwd changed via chdir(), proc_open defaults to the dir where PHP was started
        // on Gnu/Linux, PHP builds with --enable-maintainer-zts are also affected
        // @see : https://bugs.php.net/bug.php?id=51800
        // @see : https://bugs.php.net/bug.php?id=50524
        if (null === $this->cwd && (\defined('ZEND_THREAD_SAFE') || '\\' === \DIRECTORY_SEPARATOR)) {
            $this->cwd = getcwd();
        }
        if (null !== $env) {
            $this->setEnv($env);
        }

        $this->setInput($input);
        $this->setTimeout($timeout);
        $this->useFileHandles = '\\' === \DIRECTORY_SEPARATOR;
        $this->pty = false;
        $this->enhanceSigchildCompatibility = '\\' !== \DIRECTORY_SEPARATOR && $this->isSigchildEnabled();
        if (null !== $options) {
            @trigger_error(sprintf('The $options parameter of the %s constructor is deprecated since Symfony 3.3 and will be removed in 4.0.', __CLASS__), E_USER_DEPRECATED);
            $this->options = array_replace($this->options, $options);
        }
    }

    public function __destruct()
    {
        $this->stop(0);
    }

    public function __clone()
    {
        $this->resetProcessData();
    }

    /**
     * Runs the process.
     *
     * The callback receives the type of output (out or err) and
     * some bytes from the output in real-time. It allows to have feedback
     * from the independent process during execution.
     *
     * The STDOUT and STDERR are also available after the process is finished
     * via the getOutput() and getErrorOutput() methods.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     * @param array         $env      An array of additional env vars to set when running the process
     *
     * @return int The exit status code
     *
     * @throws RuntimeException When process can't be launched
     * @throws RuntimeException When process stopped after receiving signal
     * @throws LogicException   In case a callback is provided and output has been disabled
     *
     * @final since version 3.3
     */
    public function run($callback = null/*, array $env = array()*/)
    {
        $env = 1 < \func_num_args() ? func_get_arg(1) : null;
        $this->start($callback, $env);

        return $this->wait();
    }

    /**
     * Runs the process.
     *
     * This is identical to run() except that an exception is thrown if the process
     * exits with a non-zero exit code.
     *
     * @param callable|null $callback
     * @param array         $env      An array of additional env vars to set when running the process
     *
     * @return self
     *
     * @throws RuntimeException       if PHP was compiled with --enable-sigchild and the enhanced sigchild compatibility mode is not enabled
     * @throws ProcessFailedException if the process didn't terminate successfully
     *
     * @final since version 3.3
     */
    public function mustRun(callable $callback = null/*, array $env = array()*/)
    {
        if (!$this->enhanceSigchildCompatibility && $this->isSigchildEnabled()) {
            throw new RuntimeException('This PHP has been compiled with --enable-sigchild. You must use setEnhanceSigchildCompatibility() to use this method.');
        }
        $env = 1 < \func_num_args() ? func_get_arg(1) : null;

        if (0 !== $this->run($callback, $env)) {
            throw new ProcessFailedException($this);
        }

        return $this;
    }

    /**
     * Starts the process and returns after writing the input to STDIN.
     *
     * This method blocks until all STDIN data is sent to the process then it
     * returns while the process runs in the background.
     *
     * The termination of the process can be awaited with wait().
     *
     * The callback receives the type of output (out or err) and some bytes from
     * the output in real-time while writing the standard input to the process.
     * It allows to have feedback from the independent process during execution.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     * @param array         $env      An array of additional env vars to set when running the process
     *
     * @throws RuntimeException When process can't be launched
     * @throws RuntimeException When process is already running
     * @throws LogicException   In case a callback is provided and output has been disabled
     */
    public function start(callable $callback = null/*, array $env = array()*/)
    {
        if ($this->isRunning()) {
            throw new RuntimeException('Process is already running');
        }
        if (2 <= \func_num_args()) {
            $env = func_get_arg(1);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName() && (2 > $r->getNumberOfParameters() || 'env' !== $r->getParameters()[1]->name)) {
                    @trigger_error(sprintf('The %s::start() method expects a second "$env" argument since Symfony 3.3. It will be made mandatory in 4.0.', static::class), E_USER_DEPRECATED);
                }
            }
            $env = null;
        }

        $this->resetProcessData();
        $this->starttime = $this->lastOutputTime = microtime(true);
        $this->callback = $this->buildCallback($callback);
        $this->hasCallback = null !== $callback;
        $descriptors = $this->getDescriptors();
        $inheritEnv = $this->inheritEnv;

        if (\is_array($commandline = $this->commandline)) {
            $commandline = implode(' ', array_map(array($this, 'escapeArgument'), $commandline));

            if ('\\' !== \DIRECTORY_SEPARATOR) {
                // exec is mandatory to deal with sending a signal to the process
                $commandline = 'exec '.$commandline;
            }
        }

        if (null === $env) {
            $env = $this->env;
        } else {
            if ($this->env) {
                $env += $this->env;
            }
            $inheritEnv = true;
        }

        if (null !== $env && $inheritEnv) {
            $env += $this->getDefaultEnv();
        } elseif (null !== $env) {
            @trigger_error('Not inheriting environment variables is deprecated since Symfony 3.3 and will always happen in 4.0. Set "Process::inheritEnvironmentVariables()" to true instead.', E_USER_DEPRECATED);
        } else {
            $env = $this->getDefaultEnv();
        }
        if ('\\' === \DIRECTORY_SEPARATOR && $this->enhanceWindowsCompatibility) {
            $this->options['bypass_shell'] = true;
            $commandline = $this->prepareWindowsCommandLine($commandline, $env);
        } elseif (!$this->useFileHandles && $this->enhanceSigchildCompatibility && $this->isSigchildEnabled()) {
            // last exit code is output on the fourth pipe and caught to work around --enable-sigchild
            $descriptors[3] = array('pipe', 'w');

            // See https://unix.stackexchange.com/questions/71205/background-process-pipe-input
            $commandline = '{ ('.$commandline.') <&3 3<&- 3>/dev/null & } 3<&0;';
            $commandline .= 'pid=$!; echo $pid >&3; wait $pid; code=$?; echo $code >&3; exit $code';

            // Workaround for the bug, when PTS functionality is enabled.
            // @see : https://bugs.php.net/69442
            $ptsWorkaround = fopen(__FILE__, 'r');
        }
        if (\defined('HHVM_VERSION')) {
            $envPairs = $env;
        } else {
            $envPairs = array();
            foreach ($env as $k => $v) {
                if (false !== $v) {
                    $envPairs[] = $k.'='.$v;
                }
            }
        }

        if (!is_dir($this->cwd)) {
            @trigger_error('The provided cwd does not exist. Command is currently ran against getcwd(). This behavior is deprecated since Symfony 3.4 and will be removed in 4.0.', E_USER_DEPRECATED);
        }

        $this->process = proc_open($commandline, $descriptors, $this->processPipes->pipes, $this->cwd, $envPairs, $this->options);

        if (!\is_resource($this->process)) {
            throw new RuntimeException('Unable to launch a new process.');
        }
        $this->status = self::STATUS_STARTED;

        if (isset($descriptors[3])) {
            $this->fallbackStatus['pid'] = (int) fgets($this->processPipes->pipes[3]);
        }

        if ($this->tty) {
            return;
        }

        $this->updateStatus(false);
        $this->checkTimeout();
    }

    /**
     * Restarts the process.
     *
     * Be warned that the process is cloned before being started.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR
     * @param array         $env      An array of additional env vars to set when running the process
     *
     * @return $this
     *
     * @throws RuntimeException When process can't be launched
     * @throws RuntimeException When process is already running
     *
     * @see start()
     *
     * @final since version 3.3
     */
    public function restart(callable $callback = null/*, array $env = array()*/)
    {
        if ($this->isRunning()) {
            throw new RuntimeException('Process is already running');
        }
        $env = 1 < \func_num_args() ? func_get_arg(1) : null;

        $process = clone $this;
        $process->start($callback, $env);

        return $process;
    }

    /**
     * Waits for the process to terminate.
     *
     * The callback receives the type of output (out or err) and some bytes
     * from the output in real-time while writing the standard input to the process.
     * It allows to have feedback from the independent process during execution.
     *
     * @param callable|null $callback A valid PHP callback
     *
     * @return int The exitcode of the process
     *
     * @throws RuntimeException When process timed out
     * @throws RuntimeException When process stopped after receiving signal
     * @throws LogicException   When process is not yet started
     */
    public function wait(callable $callback = null)
    {
        $this->requireProcessIsStarted(__FUNCTION__);

        $this->updateStatus(false);

        if (null !== $callback) {
            if (!$this->processPipes->haveReadSupport()) {
                $this->stop(0);
                throw new \LogicException('Pass the callback to the Process::start method or enableOutput to use a callback with Process::wait');
            }
            $this->callback = $this->buildCallback($callback);
        }

        do {
            $this->checkTimeout();
            $running = '\\' === \DIRECTORY_SEPARATOR ? $this->isRunning() : $this->processPipes->areOpen();
            $this->readPipes($running, '\\' !== \DIRECTORY_SEPARATOR || !$running);
        } while ($running);

        while ($this->isRunning()) {
            usleep(1000);
        }

        if ($this->processInformation['signaled'] && $this->processInformation['termsig'] !== $this->latestSignal) {
            throw new RuntimeException(sprintf('The process has been signaled with signal "%s".', $this->processInformation['termsig']));
        }

        return $this->exitcode;
    }

    /**
     * Returns the Pid (process identifier), if applicable.
     *
     * @return int|null The process id if running, null otherwise
     */
    public function getPid()
    {
        return $this->isRunning() ? $this->processInformation['pid'] : null;
    }

    /**
     * Sends a POSIX signal to the process.
     *
     * @param int $signal A valid POSIX signal (see http://www.php.net/manual/en/pcntl.constants.php)
     *
     * @return $this
     *
     * @throws LogicException   In case the process is not running
     * @throws RuntimeException In case --enable-sigchild is activated and the process can't be killed
     * @throws RuntimeException In case of failure
     */
    public function signal($signal)
    {
        $this->doSignal($signal, true);

        return $this;
    }

    /**
     * Disables fetching output and error output from the underlying process.
     *
     * @return $this
     *
     * @throws RuntimeException In case the process is already running
     * @throws LogicException   if an idle timeout is set
     */
    public function disableOutput()
    {
        if ($this->isRunning()) {
            throw new RuntimeException('Disabling output while the process is running is not possible.');
        }
        if (null !== $this->idleTimeout) {
            throw new LogicException('Output can not be disabled while an idle timeout is set.');
        }

        $this->outputDisabled = true;

        return $this;
    }

    /**
     * Enables fetching output and error output from the underlying process.
     *
     * @return $this
     *
     * @throws RuntimeException In case the process is already running
     */
    public function enableOutput()
    {
        if ($this->isRunning()) {
            throw new RuntimeException('Enabling output while the process is running is not possible.');
        }

        $this->outputDisabled = false;

        return $this;
    }

    /**
     * Returns true in case the output is disabled, false otherwise.
     *
     * @return bool
     */
    public function isOutputDisabled()
    {
        return $this->outputDisabled;
    }

    /**
     * Returns the current output of the process (STDOUT).
     *
     * @return string The process output
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getOutput()
    {
        $this->readPipesForOutput(__FUNCTION__);

        if (false === $ret = stream_get_contents($this->stdout, -1, 0)) {
            return '';
        }

        return $ret;
    }

    /**
     * Returns the output incrementally.
     *
     * In comparison with the getOutput method which always return the whole
     * output, this one returns the new output since the last call.
     *
     * @return string The process output since the last call
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getIncrementalOutput()
    {
        $this->readPipesForOutput(__FUNCTION__);

        $latest = stream_get_contents($this->stdout, -1, $this->incrementalOutputOffset);
        $this->incrementalOutputOffset = ftell($this->stdout);

        if (false === $latest) {
            return '';
        }

        return $latest;
    }

    /**
     * Returns an iterator to the output of the process, with the output type as keys (Process::OUT/ERR).
     *
     * @param int $flags A bit field of Process::ITER_* flags
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     *
     * @return \Generator
     */
    public function getIterator($flags = 0)
    {
        $this->readPipesForOutput(__FUNCTION__, false);

        $clearOutput = !(self::ITER_KEEP_OUTPUT & $flags);
        $blocking = !(self::ITER_NON_BLOCKING & $flags);
        $yieldOut = !(self::ITER_SKIP_OUT & $flags);
        $yieldErr = !(self::ITER_SKIP_ERR & $flags);

        while (null !== $this->callback || ($yieldOut && !feof($this->stdout)) || ($yieldErr && !feof($this->stderr))) {
            if ($yieldOut) {
                $out = stream_get_contents($this->stdout, -1, $this->incrementalOutputOffset);

                if (isset($out[0])) {
                    if ($clearOutput) {
                        $this->clearOutput();
                    } else {
                        $this->incrementalOutputOffset = ftell($this->stdout);
                    }

                    yield self::OUT => $out;
                }
            }

            if ($yieldErr) {
                $err = stream_get_contents($this->stderr, -1, $this->incrementalErrorOutputOffset);

                if (isset($err[0])) {
                    if ($clearOutput) {
                        $this->clearErrorOutput();
                    } else {
                        $this->incrementalErrorOutputOffset = ftell($this->stderr);
                    }

                    yield self::ERR => $err;
                }
            }

            if (!$blocking && !isset($out[0]) && !isset($err[0])) {
                yield self::OUT => '';
            }

            $this->checkTimeout();
            $this->readPipesForOutput(__FUNCTION__, $blocking);
        }
    }

    /**
     * Clears the process output.
     *
     * @return $this
     */
    public function clearOutput()
    {
        ftruncate($this->stdout, 0);
        fseek($this->stdout, 0);
        $this->incrementalOutputOffset = 0;

        return $this;
    }

    /**
     * Returns the current error output of the process (STDERR).
     *
     * @return string The process error output
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getErrorOutput()
    {
        $this->readPipesForOutput(__FUNCTION__);

        if (false === $ret = stream_get_contents($this->stderr, -1, 0)) {
            return '';
        }

        return $ret;
    }

    /**
     * Returns the errorOutput incrementally.
     *
     * In comparison with the getErrorOutput method which always return the
     * whole error output, this one returns the new error output since the last
     * call.
     *
     * @return string The process error output since the last call
     *
     * @throws LogicException in case the output has been disabled
     * @throws LogicException In case the process is not started
     */
    public function getIncrementalErrorOutput()
    {
        $this->readPipesForOutput(__FUNCTION__);

        $latest = stream_get_contents($this->stderr, -1, $this->incrementalErrorOutputOffset);
        $this->incrementalErrorOutputOffset = ftell($this->stderr);

        if (false === $latest) {
            return '';
        }

        return $latest;
    }

    /**
     * Clears the process output.
     *
     * @return $this
     */
    public function clearErrorOutput()
    {
        ftruncate($this->stderr, 0);
        fseek($this->stderr, 0);
        $this->incrementalErrorOutputOffset = 0;

        return $this;
    }

    /**
     * Returns the exit code returned by the process.
     *
     * @return int|null The exit status code, null if the Process is not terminated
     *
     * @throws RuntimeException In case --enable-sigchild is activated and the sigchild compatibility mode is disabled
     */
    public function getExitCode()
    {
        if (!$this->enhanceSigchildCompatibility && $this->isSigchildEnabled()) {
            throw new RuntimeException('This PHP has been compiled with --enable-sigchild. You must use setEnhanceSigchildCompatibility() to use this method.');
        }

        $this->updateStatus(false);

        return $this->exitcode;
    }

    /**
     * Returns a string representation for the exit code returned by the process.
     *
     * This method relies on the Unix exit code status standardization
     * and might not be relevant for other operating systems.
     *
     * @return string|null A string representation for the exit status code, null if the Process is not terminated
     *
     * @see http://tldp.org/LDP/abs/html/exitcodes.html
     * @see http://en.wikipedia.org/wiki/Unix_signal
     */
    public function getExitCodeText()
    {
        if (null === $exitcode = $this->getExitCode()) {
            return;
        }

        return isset(self::$exitCodes[$exitcode]) ? self::$exitCodes[$exitcode] : 'Unknown error';
    }

    /**
     * Checks if the process ended successfully.
     *
     * @return bool true if the process ended successfully, false otherwise
     */
    public function isSuccessful()
    {
        return 0 === $this->getExitCode();
    }

    /**
     * Returns true if the child process has been terminated by an uncaught signal.
     *
     * It always returns false on Windows.
     *
     * @return bool
     *
     * @throws RuntimeException In case --enable-sigchild is activated
     * @throws LogicException   In case the process is not terminated
     */
    public function hasBeenSignaled()
    {
        $this->requireProcessIsTerminated(__FUNCTION__);

        if (!$this->enhanceSigchildCompatibility && $this->isSigchildEnabled()) {
            throw new RuntimeException('This PHP has been compiled with --enable-sigchild. Term signal can not be retrieved.');
        }

        return $this->processInformation['signaled'];
    }

    /**
     * Returns the number of the signal that caused the child process to terminate its execution.
     *
     * It is only meaningful if hasBeenSignaled() returns true.
     *
     * @return int
     *
     * @throws RuntimeException In case --enable-sigchild is activated
     * @throws LogicException   In case the process is not terminated
     */
    public function getTermSignal()
    {
        $this->requireProcessIsTerminated(__FUNCTION__);

        if ($this->isSigchildEnabled() && (!$this->enhanceSigchildCompatibility || -1 === $this->processInformation['termsig'])) {
            throw new RuntimeException('This PHP has been compiled with --enable-sigchild. Term signal can not be retrieved.');
        }

        return $this->processInformation['termsig'];
    }

    /**
     * Returns true if the child process has been stopped by a signal.
     *
     * It always returns false on Windows.
     *
     * @return bool
     *
     * @throws LogicException In case the process is not terminated
     */
    public function hasBeenStopped()
    {
        $this->requireProcessIsTerminated(__FUNCTION__);

        return $this->processInformation['stopped'];
    }

    /**
     * Returns the number of the signal that caused the child process to stop its execution.
     *
     * It is only meaningful if hasBeenStopped() returns true.
     *
     * @return int
     *
     * @throws LogicException In case the process is not terminated
     */
    public function getStopSignal()
    {
        $this->requireProcessIsTerminated(__FUNCTION__);

        return $this->processInformation['stopsig'];
    }

    /**
     * Checks if the process is currently running.
     *
     * @return bool true if the process is currently running, false otherwise
     */
    public function isRunning()
    {
        if (self::STATUS_STARTED !== $this->status) {
            return false;
        }

        $this->updateStatus(false);

        return $this->processInformation['running'];
    }

    /**
     * Checks if the process has been started with no regard to the current state.
     *
     * @return bool true if status is ready, false otherwise
     */
    public function isStarted()
    {
        return self::STATUS_READY != $this->status;
    }

    /**
     * Checks if the process is terminated.
     *
     * @return bool true if process is terminated, false otherwise
     */
    public function isTerminated()
    {
        $this->updateStatus(false);

        return self::STATUS_TERMINATED == $this->status;
    }

    /**
     * Gets the process status.
     *
     * The status is one of: ready, started, terminated.
     *
     * @return string The current process status
     */
    public function getStatus()
    {
        $this->updateStatus(false);

        return $this->status;
    }

    /**
     * Stops the process.
     *
     * @param int|float $timeout The timeout in seconds
     * @param int       $signal  A POSIX signal to send in case the process has not stop at timeout, default is SIGKILL (9)
     *
     * @return int The exit-code of the process
     */
    public function stop($timeout = 10, $signal = null)
    {
        $timeoutMicro = microtime(true) + $timeout;
        if ($this->isRunning()) {
            // given `SIGTERM` may not be defined and that `proc_terminate` uses the constant value and not the constant itself, we use the same here
            $this->doSignal(15, false);
            do {
                usleep(1000);
            } while ($this->isRunning() && microtime(true) < $timeoutMicro);

            if ($this->isRunning()) {
                // Avoid exception here: process is supposed to be running, but it might have stopped just
                // after this line. In any case, let's silently discard the error, we cannot do anything.
                $this->doSignal($signal ?: 9, false);
            }
        }

        if ($this->isRunning()) {
            if (isset($this->fallbackStatus['pid'])) {
                unset($this->fallbackStatus['pid']);

                return $this->stop(0, $signal);
            }
            $this->close();
        }

        return $this->exitcode;
    }

    /**
     * Adds a line to the STDOUT stream.
     *
     * @internal
     *
     * @param string $line The line to append
     */
    public function addOutput($line)
    {
        $this->lastOutputTime = microtime(true);

        fseek($this->stdout, 0, SEEK_END);
        fwrite($this->stdout, $line);
        fseek($this->stdout, $this->incrementalOutputOffset);
    }

    /**
     * Adds a line to the STDERR stream.
     *
     * @internal
     *
     * @param string $line The line to append
     */
    public function addErrorOutput($line)
    {
        $this->lastOutputTime = microtime(true);

        fseek($this->stderr, 0, SEEK_END);
        fwrite($this->stderr, $line);
        fseek($this->stderr, $this->incrementalErrorOutputOffset);
    }

    /**
     * Gets the command line to be executed.
     *
     * @return string The command to execute
     */
    public function getCommandLine()
    {
        return \is_array($this->commandline) ? implode(' ', array_map(array($this, 'escapeArgument'), $this->commandline)) : $this->commandline;
    }

    /**
     * Sets the command line to be executed.
     *
     * @param string|array $commandline The command to execute
     *
     * @return self The current Process instance
     */
    public function setCommandLine($commandline)
    {
        $this->commandline = $commandline;

        return $this;
    }

    /**
     * Gets the process timeout (max. runtime).
     *
     * @return float|null The timeout in seconds or null if it's disabled
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Gets the process idle timeout (max. time since last output).
     *
     * @return float|null The timeout in seconds or null if it's disabled
     */
    public function getIdleTimeout()
    {
        return $this->idleTimeout;
    }

    /**
     * Sets the process timeout (max. runtime).
     *
     * To disable the timeout, set this value to null.
     *
     * @param int|float|null $timeout The timeout in seconds
     *
     * @return self The current Process instance
     *
     * @throws InvalidArgumentException if the timeout is negative
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $this->validateTimeout($timeout);

        return $this;
    }

    /**
     * Sets the process idle timeout (max. time since last output).
     *
     * To disable the timeout, set this value to null.
     *
     * @param int|float|null $timeout The timeout in seconds
     *
     * @return self The current Process instance
     *
     * @throws LogicException           if the output is disabled
     * @throws InvalidArgumentException if the timeout is negative
     */
    public function setIdleTimeout($timeout)
    {
        if (null !== $timeout && $this->outputDisabled) {
            throw new LogicException('Idle timeout can not be set while the output is disabled.');
        }

        $this->idleTimeout = $this->validateTimeout($timeout);

        return $this;
    }

    /**
     * Enables or disables the TTY mode.
     *
     * @param bool $tty True to enabled and false to disable
     *
     * @return self The current Process instance
     *
     * @throws RuntimeException In case the TTY mode is not supported
     */
    public function setTty($tty)
    {
        if ('\\' === \DIRECTORY_SEPARATOR && $tty) {
            throw new RuntimeException('TTY mode is not supported on Windows platform.');
        }
        if ($tty) {
            static $isTtySupported;

            if (null === $isTtySupported) {
                $isTtySupported = (bool) @proc_open('echo 1 >/dev/null', array(array('file', '/dev/tty', 'r'), array('file', '/dev/tty', 'w'), array('file', '/dev/tty', 'w')), $pipes);
            }

            if (!$isTtySupported) {
                throw new RuntimeException('TTY mode requires /dev/tty to be read/writable.');
            }
        }

        $this->tty = (bool) $tty;

        return $this;
    }

    /**
     * Checks if the TTY mode is enabled.
     *
     * @return bool true if the TTY mode is enabled, false otherwise
     */
    public function isTty()
    {
        return $this->tty;
    }

    /**
     * Sets PTY mode.
     *
     * @param bool $bool
     *
     * @return self
     */
    public function setPty($bool)
    {
        $this->pty = (bool) $bool;

        return $this;
    }

    /**
     * Returns PTY state.
     *
     * @return bool
     */
    public function isPty()
    {
        return $this->pty;
    }

    /**
     * Gets the working directory.
     *
     * @return string|null The current working directory or null on failure
     */
    public function getWorkingDirectory()
    {
        if (null === $this->cwd) {
            // getcwd() will return false if any one of the parent directories does not have
            // the readable or search mode set, even if the current directory does
            return getcwd() ?: null;
        }

        return $this->cwd;
    }

    /**
     * Sets the current working directory.
     *
     * @param string $cwd The new working directory
     *
     * @return self The current Process instance
     */
    public function setWorkingDirectory($cwd)
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * Gets the environment variables.
     *
     * @return array The current environment variables
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Sets the environment variables.
     *
     * Each environment variable value should be a string.
     * If it is an array, the variable is ignored.
     * If it is false or null, it will be removed when
     * env vars are otherwise inherited.
     *
     * That happens in PHP when 'argv' is registered into
     * the $_ENV array for instance.
     *
     * @param array $env The new environment variables
     *
     * @return self The current Process instance
     */
    public function setEnv(array $env)
    {
        // Process can not handle env values that are arrays
        $env = array_filter($env, function ($value) {
            return !\is_array($value);
        });

        $this->env = $env;

        return $this;
    }

    /**
     * Gets the Process input.
     *
     * @return resource|string|\Iterator|null The Process input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Sets the input.
     *
     * This content will be passed to the underlying process standard input.
     *
     * @param string|int|float|bool|resource|\Traversable|null $input The content
     *
     * @return self The current Process instance
     *
     * @throws LogicException In case the process is running
     */
    public function setInput($input)
    {
        if ($this->isRunning()) {
            throw new LogicException('Input can not be set while the process is running.');
        }

        $this->input = ProcessUtils::validateInput(__METHOD__, $input);

        return $this;
    }

    /**
     * Gets the options for proc_open.
     *
     * @return array The current options
     *
     * @deprecated since version 3.3, to be removed in 4.0.
     */
    public function getOptions()
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0.', __METHOD__), E_USER_DEPRECATED);

        return $this->options;
    }

    /**
     * Sets the options for proc_open.
     *
     * @param array $options The new options
     *
     * @return self The current Process instance
     *
     * @deprecated since version 3.3, to be removed in 4.0.
     */
    public function setOptions(array $options)
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0.', __METHOD__), E_USER_DEPRECATED);

        $this->options = $options;

        return $this;
    }

    /**
     * Gets whether or not Windows compatibility is enabled.
     *
     * This is true by default.
     *
     * @return bool
     *
     * @deprecated since version 3.3, to be removed in 4.0. Enhanced Windows compatibility will always be enabled.
     */
    public function getEnhanceWindowsCompatibility()
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Enhanced Windows compatibility will always be enabled.', __METHOD__), E_USER_DEPRECATED);

        return $this->enhanceWindowsCompatibility;
    }

    /**
     * Sets whether or not Windows compatibility is enabled.
     *
     * @param bool $enhance
     *
     * @return self The current Process instance
     *
     * @deprecated since version 3.3, to be removed in 4.0. Enhanced Windows compatibility will always be enabled.
     */
    public function setEnhanceWindowsCompatibility($enhance)
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Enhanced Windows compatibility will always be enabled.', __METHOD__), E_USER_DEPRECATED);

        $this->enhanceWindowsCompatibility = (bool) $enhance;

        return $this;
    }

    /**
     * Returns whether sigchild compatibility mode is activated or not.
     *
     * @return bool
     *
     * @deprecated since version 3.3, to be removed in 4.0. Sigchild compatibility will always be enabled.
     */
    public function getEnhanceSigchildCompatibility()
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Sigchild compatibility will always be enabled.', __METHOD__), E_USER_DEPRECATED);

        return $this->enhanceSigchildCompatibility;
    }

    /**
     * Activates sigchild compatibility mode.
     *
     * Sigchild compatibility mode is required to get the exit code and
     * determine the success of a process when PHP has been compiled with
     * the --enable-sigchild option
     *
     * @param bool $enhance
     *
     * @return self The current Process instance
     *
     * @deprecated since version 3.3, to be removed in 4.0.
     */
    public function setEnhanceSigchildCompatibility($enhance)
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Sigchild compatibility will always be enabled.', __METHOD__), E_USER_DEPRECATED);

        $this->enhanceSigchildCompatibility = (bool) $enhance;

        return $this;
    }

    /**
     * Sets whether environment variables will be inherited or not.
     *
     * @param bool $inheritEnv
     *
     * @return self The current Process instance
     */
    public function inheritEnvironmentVariables($inheritEnv = true)
    {
        if (!$inheritEnv) {
            @trigger_error('Not inheriting environment variables is deprecated since Symfony 3.3 and will always happen in 4.0. Set "Process::inheritEnvironmentVariables()" to true instead.', E_USER_DEPRECATED);
        }

        $this->inheritEnv = (bool) $inheritEnv;

        return $this;
    }

    /**
     * Returns whether environment variables will be inherited or not.
     *
     * @return bool
     *
     * @deprecated since version 3.3, to be removed in 4.0. Environment variables will always be inherited.
     */
    public function areEnvironmentVariablesInherited()
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Environment variables will always be inherited.', __METHOD__), E_USER_DEPRECATED);

        return $this->inheritEnv;
    }

    /**
     * Performs a check between the timeout definition and the time the process started.
     *
     * In case you run a background process (with the start method), you should
     * trigger this method regularly to ensure the process timeout
     *
     * @throws ProcessTimedOutException In case the timeout was reached
     */
    public function checkTimeout()
    {
        if (self::STATUS_STARTED !== $this->status) {
            return;
        }

        if (null !== $this->timeout && $this->timeout < microtime(true) - $this->starttime) {
            $this->stop(0);

            throw new ProcessTimedOutException($this, ProcessTimedOutException::TYPE_GENERAL);
        }

        if (null !== $this->idleTimeout && $this->idleTimeout < microtime(true) - $this->lastOutputTime) {
            $this->stop(0);

            throw new ProcessTimedOutException($this, ProcessTimedOutException::TYPE_IDLE);
        }
    }

    /**
     * Returns whether PTY is supported on the current operating system.
     *
     * @return bool
     */
    public static function isPtySupported()
    {
        static $result;

        if (null !== $result) {
            return $result;
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            return $result = false;
        }

        return $result = (bool) @proc_open('echo 1 >/dev/null', array(array('pty'), array('pty'), array('pty')), $pipes);
    }

    /**
     * Creates the descriptors needed by the proc_open.
     *
     * @return array
     */
    private function getDescriptors()
    {
        if ($this->input instanceof \Iterator) {
            $this->input->rewind();
        }
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->processPipes = new WindowsPipes($this->input, !$this->outputDisabled || $this->hasCallback);
        } else {
            $this->processPipes = new UnixPipes($this->isTty(), $this->isPty(), $this->input, !$this->outputDisabled || $this->hasCallback);
        }

        return $this->processPipes->getDescriptors();
    }

    /**
     * Builds up the callback used by wait().
     *
     * The callbacks adds all occurred output to the specific buffer and calls
     * the user callback (if present) with the received output.
     *
     * @param callable|null $callback The user defined PHP callback
     *
     * @return \Closure A PHP closure
     */
    protected function buildCallback(callable $callback = null)
    {
        if ($this->outputDisabled) {
            return function ($type, $data) use ($callback) {
                if (null !== $callback) {
                    \call_user_func($callback, $type, $data);
                }
            };
        }

        $out = self::OUT;

        return function ($type, $data) use ($callback, $out) {
            if ($out == $type) {
                $this->addOutput($data);
            } else {
                $this->addErrorOutput($data);
            }

            if (null !== $callback) {
                \call_user_func($callback, $type, $data);
            }
        };
    }

    /**
     * Updates the status of the process, reads pipes.
     *
     * @param bool $blocking Whether to use a blocking read call
     */
    protected function updateStatus($blocking)
    {
        if (self::STATUS_STARTED !== $this->status) {
            return;
        }

        $this->processInformation = proc_get_status($this->process);
        $running = $this->processInformation['running'];

        $this->readPipes($running && $blocking, '\\' !== \DIRECTORY_SEPARATOR || !$running);

        if ($this->fallbackStatus && $this->enhanceSigchildCompatibility && $this->isSigchildEnabled()) {
            $this->processInformation = $this->fallbackStatus + $this->processInformation;
        }

        if (!$running) {
            $this->close();
        }
    }

    /**
     * Returns whether PHP has been compiled with the '--enable-sigchild' option or not.
     *
     * @return bool
     */
    protected function isSigchildEnabled()
    {
        if (null !== self::$sigchild) {
            return self::$sigchild;
        }

        if (!\function_exists('phpinfo') || \defined('HHVM_VERSION')) {
            return self::$sigchild = false;
        }

        ob_start();
        phpinfo(INFO_GENERAL);

        return self::$sigchild = false !== strpos(ob_get_clean(), '--enable-sigchild');
    }

    /**
     * Reads pipes for the freshest output.
     *
     * @param string $caller   The name of the method that needs fresh outputs
     * @param bool   $blocking Whether to use blocking calls or not
     *
     * @throws LogicException in case output has been disabled or process is not started
     */
    private function readPipesForOutput($caller, $blocking = false)
    {
        if ($this->outputDisabled) {
            throw new LogicException('Output has been disabled.');
        }

        $this->requireProcessIsStarted($caller);

        $this->updateStatus($blocking);
    }

    /**
     * Validates and returns the filtered timeout.
     *
     * @param int|float|null $timeout
     *
     * @return float|null
     *
     * @throws InvalidArgumentException if the given timeout is a negative number
     */
    private function validateTimeout($timeout)
    {
        $timeout = (float) $timeout;

        if (0.0 === $timeout) {
            $timeout = null;
        } elseif ($timeout < 0) {
            throw new InvalidArgumentException('The timeout value must be a valid positive integer or float number.');
        }

        return $timeout;
    }

    /**
     * Reads pipes, executes callback.
     *
     * @param bool $blocking Whether to use blocking calls or not
     * @param bool $close    Whether to close file handles or not
     */
    private function readPipes($blocking, $close)
    {
        $result = $this->processPipes->readAndWrite($blocking, $close);

        $callback = $this->callback;
        foreach ($result as $type => $data) {
            if (3 !== $type) {
                $callback(self::STDOUT === $type ? self::OUT : self::ERR, $data);
            } elseif (!isset($this->fallbackStatus['signaled'])) {
                $this->fallbackStatus['exitcode'] = (int) $data;
            }
        }
    }

    /**
     * Closes process resource, closes file handles, sets the exitcode.
     *
     * @return int The exitcode
     */
    private function close()
    {
        $this->processPipes->close();
        if (\is_resource($this->process)) {
            proc_close($this->process);
        }
        $this->exitcode = $this->processInformation['exitcode'];
        $this->status = self::STATUS_TERMINATED;

        if (-1 === $this->exitcode) {
            if ($this->processInformation['signaled'] && 0 < $this->processInformation['termsig']) {
                // if process has been signaled, no exitcode but a valid termsig, apply Unix convention
                $this->exitcode = 128 + $this->processInformation['termsig'];
            } elseif ($this->enhanceSigchildCompatibility && $this->isSigchildEnabled()) {
                $this->processInformation['signaled'] = true;
                $this->processInformation['termsig'] = -1;
            }
        }

        // Free memory from self-reference callback created by buildCallback
        // Doing so in other contexts like __destruct or by garbage collector is ineffective
        // Now pipes are closed, so the callback is no longer necessary
        $this->callback = null;

        return $this->exitcode;
    }

    /**
     * Resets data related to the latest run of the process.
     */
    private function resetProcessData()
    {
        $this->starttime = null;
        $this->callback = null;
        $this->exitcode = null;
        $this->fallbackStatus = array();
        $this->processInformation = null;
        $this->stdout = fopen('php://temp/maxmemory:'.(1024 * 1024), 'w+b');
        $this->stderr = fopen('php://temp/maxmemory:'.(1024 * 1024), 'w+b');
        $this->process = null;
        $this->latestSignal = null;
        $this->status = self::STATUS_READY;
        $this->incrementalOutputOffset = 0;
        $this->incrementalErrorOutputOffset = 0;
    }

    /**
     * Sends a POSIX signal to the process.
     *
     * @param int  $signal         A valid POSIX signal (see http://www.php.net/manual/en/pcntl.constants.php)
     * @param bool $throwException Whether to throw exception in case signal failed
     *
     * @return bool True if the signal was sent successfully, false otherwise
     *
     * @throws LogicException   In case the process is not running
     * @throws RuntimeException In case --enable-sigchild is activated and the process can't be killed
     * @throws RuntimeException In case of failure
     */
    private function doSignal($signal, $throwException)
    {
        if (null === $pid = $this->getPid()) {
            if ($throwException) {
                throw new LogicException('Can not send signal on a non running process.');
            }

            return false;
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            exec(sprintf('taskkill /F /T /PID %d 2>&1', $pid), $output, $exitCode);
            if ($exitCode && $this->isRunning()) {
                if ($throwException) {
                    throw new RuntimeException(sprintf('Unable to kill the process (%s).', implode(' ', $output)));
                }

                return false;
            }
        } else {
            if (!$this->enhanceSigchildCompatibility || !$this->isSigchildEnabled()) {
                $ok = @proc_terminate($this->process, $signal);
            } elseif (\function_exists('posix_kill')) {
                $ok = @posix_kill($pid, $signal);
            } elseif ($ok = proc_open(sprintf('kill -%d %d', $signal, $pid), array(2 => array('pipe', 'w')), $pipes)) {
                $ok = false === fgets($pipes[2]);
            }
            if (!$ok) {
                if ($throwException) {
                    throw new RuntimeException(sprintf('Error while sending signal `%s`.', $signal));
                }

                return false;
            }
        }

        $this->latestSignal = (int) $signal;
        $this->fallbackStatus['signaled'] = true;
        $this->fallbackStatus['exitcode'] = -1;
        $this->fallbackStatus['termsig'] = $this->latestSignal;

        return true;
    }

    private function prepareWindowsCommandLine($cmd, array &$env)
    {
        $uid = uniqid('', true);
        $varCount = 0;
        $varCache = array();
        $cmd = preg_replace_callback(
            '/"(?:(
                [^"%!^]*+
                (?:
                    (?: !LF! | "(?:\^[%!^])?+" )
                    [^"%!^]*+
                )++
            ) | [^"]*+ )"/x',
            function ($m) use (&$env, &$varCache, &$varCount, $uid) {
                if (!isset($m[1])) {
                    return $m[0];
                }
                if (isset($varCache[$m[0]])) {
                    return $varCache[$m[0]];
                }
                if (false !== strpos($value = $m[1], "\0")) {
                    $value = str_replace("\0", '?', $value);
                }
                if (false === strpbrk($value, "\"%!\n")) {
                    return '"'.$value.'"';
                }

                $value = str_replace(array('!LF!', '"^!"', '"^%"', '"^^"', '""'), array("\n", '!', '%', '^', '"'), $value);
                $value = '"'.preg_replace('/(\\\\*)"/', '$1$1\\"', $value).'"';
                $var = $uid.++$varCount;

                $env[$var] = $value;

                return $varCache[$m[0]] = '!'.$var.'!';
            },
            $cmd
        );

        $cmd = 'cmd /V:ON /E:ON /D /C ('.str_replace("\n", ' ', $cmd).')';
        foreach ($this->processPipes->getFiles() as $offset => $filename) {
            $cmd .= ' '.$offset.'>"'.$filename.'"';
        }

        return $cmd;
    }

    /**
     * Ensures the process is running or terminated, throws a LogicException if the process has a not started.
     *
     * @param string $functionName The function name that was called
     *
     * @throws LogicException if the process has not run
     */
    private function requireProcessIsStarted($functionName)
    {
        if (!$this->isStarted()) {
            throw new LogicException(sprintf('Process must be started before calling %s.', $functionName));
        }
    }

    /**
     * Ensures the process is terminated, throws a LogicException if the process has a status different than `terminated`.
     *
     * @param string $functionName The function name that was called
     *
     * @throws LogicException if the process is not yet terminated
     */
    private function requireProcessIsTerminated($functionName)
    {
        if (!$this->isTerminated()) {
            throw new LogicException(sprintf('Process must be terminated before calling %s.', $functionName));
        }
    }

    /**
     * Escapes a string to be used as a shell argument.
     *
     * @param string $argument The argument that will be escaped
     *
     * @return string The escaped argument
     */
    private function escapeArgument($argument)
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            return "'".str_replace("'", "'\\''", $argument)."'";
        }
        if ('' === $argument = (string) $argument) {
            return '""';
        }
        if (false !== strpos($argument, "\0")) {
            $argument = str_replace("\0", '?', $argument);
        }
        if (!preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
            return $argument;
        }
        $argument = preg_replace('/(\\\\+)$/', '$1$1', $argument);

        return '"'.str_replace(array('"', '^', '%', '!', "\n"), array('""', '"^^"', '"^%"', '"^!"', '!LF!'), $argument).'"';
    }

    private function getDefaultEnv()
    {
        $env = array();

        foreach ($_SERVER as $k => $v) {
            if (\is_string($v) && false !== $v = getenv($k)) {
                $env[$k] = $v;
            }
        }

        foreach ($_ENV as $k => $v) {
            if (\is_string($v)) {
                $env[$k] = $v;
            }
        }

        return $env;
    }
}
