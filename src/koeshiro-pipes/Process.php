<?php
namespace Koeshiro\Pipes;

use Koeshiro\Pipes\Exceptions\ProcessException;
use Koeshiro\Pipes\Interfaces\ProcessInterface;
use TypeError;

/**
 * Create sub procces by command and arguments
 *
 * @param string $cmd
 *            - command for start sub process (example: php)
 * @param array $args
 *            - arguments for command (example: [['key' => '-f' , 'value' => '/home/test/test.php']])
 */
class Process implements ProcessInterface
{

    protected $Uid = null;

    protected $stdin = null;

    protected $stdout = null;

    protected $stderr = null;

    protected $process = null;

    protected $cmd = '';

    protected $args = [];

    protected $result = '';

    protected $errors = '';

    protected $pipes = [];

    protected $ProcStatus = [];

    /**
     * Create sub procces by command and arguments
     *
     * @param string $cmd
     *            - command for start sub process (example: php)
     * @param array $args
     *            - arguments for command (example: [['key' => '-f' , 'value' => '/home/test/test.php']])
     */
    public function __construct($cmd, array $args = array())
    {
        $this->cmd = $cmd;
        $this->args = $args;
        foreach ($this->args as $arg) {
            if (! is_scalar($arg['value'])) {
                throw new TypeError('Argument ' . $arg['key'] . ' has not scalar value.', 1);
            }
        }
    }

    public function isOpened()
    {
        return $this->process !== null;
    }

    public function open()
    {
        if ($this->isOpened()) {
            throw new ProcessException("Process is already opened");
        }
        $descriptorspec = array(
            0 => array(
                'pipe',
                'r'
            ),
            1 => array(
                'pipe',
                'w'
            ),
            2 => array(
                'pipe',
                'w'
            )
        );
        $cmd = $this->cmd;
        foreach ($this->args as $arg) {
            $cmd .= ' ' . $arg['key'];
            if ($arg['value']) {
                $cmd .= ' ' . $arg['value'];
            }
        }
        // creating new process
        $this->process = proc_open($cmd, $descriptorspec, $this->pipes);
        // saving pipes in public variables for more usefull
        $this->stdin = $this->pipes[0];
        $this->stdout = $this->pipes[1];
        $this->stderr = $this->pipes[2];
        // set not blocking read mode
        $this->pipeBlockMode(false);
        return $this->process;
    }

    /**
     * Create and return Uid of process (existing only on this object)
     *
     * @return string
     */
    public function getUid()
    {
        if (is_null($this->Uid)) {
            $this->Uid = uniqid();
        }
        return $this->Uid;
    }

    /**
     * Method for get process id
     *
     * @return int
     */
    public function getPId(): int
    {
        $status = $this->getProcessStatus();
        return $status['pid'];
    }

    /**
     * Reading stdout
     *
     * @param int $Size
     * @return string
     */
    public function read(int $Size = 65535): string
    {
        if (is_resource($this->process)) {
            $block = fread($this->pipes[1], $Size);
            $this->result .= $block;
            return $block;
        } else {
            throw new ProcessException('Error process is not resource.');
        }
    }

    /**
     * Reading stderr
     *
     * @param int $Size
     * @return string
     */
    public function readStderr(int $Size = 65535): string
    {
        if (is_resource($this->process)) {
            $block = fread($this->pipes[2], $Size);
            $this->errors .= $block;
            return $block;
        } else {
            throw new ProcessException('Error process is not resource.');
        }
    }

    /**
     * Write stdin
     *
     * @param int $Size
     * @return string
     */
    public function write($Data)
    {
        if (is_resource($this->process)) {
            return fwrite($this->pipes[0], $Data);
        } else {
            throw new ProcessException('Error process is not resource.');
        }
    }

    public function wait(int $timeout = 0)
    {
        $timeoutSeconds = $timeout / 10000000;
        $timeoutMicroseconds = $timeout % 10000000;
        $read = [
            $this->pipes[0]
        ];
        $write = [
            $this->pipes[1]
        ];
        $exceptions = [
            $this->pipes[2]
        ];
        stream_select($read, $write, $exceptions, $timeoutSeconds, $timeoutMicroseconds);
    }

    /**
     * Method for group change pipes blocking mode
     *
     * @param bool $Mode
     */
    public function pipeBlockMode($Mode = false)
    {
        stream_set_blocking($this->pipes[0], $Mode);
        stream_set_blocking($this->pipes[1], $Mode);
        stream_set_blocking($this->pipes[2], $Mode);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Koeshiro\Pipes\Interfaces\ProcessInterface::getResult()
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Return strerr resource
     *
     * @return resource
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * Return strout resource
     *
     * @return resource
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     * Return strerr resource
     *
     * @return resource
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * Getting and process status.
     *
     * @return array
     */
    public function getProcessStatus()
    {
        $this->ProcStatus = proc_get_status($this->process);
        return $this->ProcStatus;
    }

    /**
     * Function for read proccess info
     * Data:
     * Status: (proc_get_status - https://www.php.net/manual/ru/function.proc-get-status.php)
     * command string
     * pid int - id of procces in a system
     * running bool
     * signaled bool
     * exitcode int
     * termsig int
     * stopsig int
     * cmd string
     * args array
     * stdout string - last info from stdout pipe
     * stderr string - last info from stderr pipe
     *
     * @return array
     * @see https://www.php.net/manual/ru/function.proc-get-status.php
     */
    public function state()
    {
        if (is_resource($this->process)) {
            // read stdout
            $this->result .= stream_get_contents($this->pipes[1], - 1, strlen($this->result));
            // read stderr
            $this->errors .= stream_get_contents($this->pipes[2], - 1, strlen($this->errors));
            $status = $this->getProcessStatus();
            $exit_code = $status['exitcode'];
            $info = array(
                'status' => $status,
                'cmd' => $this->cmd,
                'args' => $this->args,
                'exit' => $exit_code,
                'stdout' => $this->result,
                'stderr' => $this->errors
            );
        } else {
            throw new ProcessException('Error process is not resource.');
        }
        return $info;
    }

    /**
     * Clean up all the file descriptors between the main and child processes.
     */
    protected function closePipes()
    {
        if (is_iterable($this->pipes)) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }
    }

    /**
     * Method for close proccess.
     * Return exit code.
     * WARNING if exit code is equals -1 exit code could be read earlier
     *
     * @return int
     */
    public function close()
    {
        $this->closePipes();
        if (is_resource($this->process)) {
            $exit_code = proc_close($this->process);
        } else if (array_key_exists('running', $this->ProcStatus) && ! $this->ProcStatus['running']) {
            $exit_code = $this->$this->ProcStatus['exitcode'];
        } else {
            $exit_code = 0;
        }
        return $exit_code;
    }

    /**
     * Method for kill process
     * WARNING required posix
     *
     * @return bool
     */
    public function kill()
    {
        return posix_kill($this->getPID(), SIGKILL);
    }

    /**
     * Run close proccess
     */
    function __destruct()
    {
        $this->close();
    }

    /**
     * initialize Pipe by command and array args
     *
     * @param string $ProcessFile
     *            -- bash command
     * @param array ...$args
     * @return \Koeshiro\Pipes\Process
     */
    public static function init(string $ProcessFile, ...$args)
    {
        $Settings = [];
        foreach ($args as $arg) {
            $Settings[] = [
                'key' => $arg['key'],
                'value' => escapeshellarg($arg['value'])
            ];
        }
        return new Process($ProcessFile, $Settings);
    }
}

