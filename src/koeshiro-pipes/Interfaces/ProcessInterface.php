<?php
namespace Koeshiro\Pipes\Interfaces;

interface ProcessInterface
{

    /**
     * Create sub procces by command and arguments
     *
     * @param string $cmd
     *            - command for start sub process (example: php)
     * @param array $args
     *            - arguments for command (example: [['key' => '-f' , 'value' => '/home/test/test.php']])
     */
    public function __construct(string $cmd, array $args = array());

    public function isOpened();

    /**
     * Returning resource
     */
    public function open();

    /**
     * Method for get process id
     *
     * @return int
     */
    public function getPId(): int;

    /**
     * Create and return Uid of process (existing only on this object)
     *
     * @return string
     */
    public function getUid();

    /**
     * Return result
     *
     * @return resource
     */
    public function getResult();

    /**
     * Return stdin resource
     *
     * @return resource
     */
    public function getStdin();

    /**
     * Return strout resource
     *
     * @return resource
     */
    public function getStdout();

    /**
     * Return strerr resource
     *
     * @return resource
     */
    public function getStderr();

    /**
     * Getting and process status.
     *
     * @return array
     */
    public function getProcessStatus();

    /**
     * Method for group change pipes blocking mode
     *
     * @param bool $Mode
     */
    public function pipeBlockMode(bool $Mode = false);

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
     */
    public function state();

    /**
     * Reading stdout
     *
     * @param int $Size
     * @return string
     */
    public function read(int $Size = 65535): string;

    /**
     * Reading stderr
     *
     * @param int $Size
     * @return string
     */
    public function readStderr(int $Size = 65535): string;

    /**
     * Write stdin
     *
     * @param int $Size
     * @return string
     */
    public function write(string $Data);

    public function wait(int $timeout);

    /**
     * Method for close proccess.
     * Return exit code.
     * WARNING if exit code is equals -1 exit code could be read earlier
     *
     * @return int
     */
    public function close();

    /**
     * Method for kill process
     * WARNING required posix
     *
     * @return bool
     */
    public function kill();

    /**
     * init
     */
    public static function init(string $ProcessFile, ...$args);
}

