<?php
namespace Koeshiro\Pipes;

use Koeshiro\Pipes\Exceptions\ListenerException;
use Koeshiro\Pipes\Interfaces\ListenerInterface;
use Koeshiro\Pipes\Interfaces\ProcessInterface;
use Closure;

/**
 * Listning procces for wait results
 *
 * @param array<PipeInterface> $Processes
 * @param int $MaxTime
 *            - in microseconds default 7000000 (7seconds)
 * @param bool $Strict
 *            - if $strict false ignoring strerr results and continue
 *            listning while time is not over or all processes is not done
 */
class Listener implements ListenerInterface
{

    /**
     * Sub processes (pipe`s)
     *
     * @var array<Pipes\Pipe>
     */
    protected $Processes = [];

    /**
     * timeout microseconds
     *
     * @var integer
     */
    protected $MaxWorkTime = 0;

    /**
     * Listener of end work of sub processes
     *
     * @var Closure
     */
    protected $ThenListner = null;

    /**
     * Listener of some error
     *
     * @var Closure
     */
    protected $CatchListner = null;

    /**
     * Listener of steps
     *
     * @var Closure
     */
    protected $StepListner = null;

    /**
     * work mode
     *
     * @var string
     */
    protected $Strict = true;

    /**
     * work is over
     *
     * @var string
     */
    protected $Done = false;

    /**
     * some error in work
     *
     * @var string
     */
    protected $Error = false;

    public function __construct(array $Processes, int $MaxTime = 7000000, bool $Strict = false)
    {
        foreach ($Processes as $key => $Procces) {
            if (! ($Procces instanceof ProcessInterface)) {
                throw new ListenerException("Proccess [$key] mast be instance of 'Pipes\Interfaces\ProcessInterface'");
            }
        }
        $this->Processes = $Processes;
        $this->MaxWorkTime = $MaxTime;
        $this->Strict = $Strict;
    }

    /**
     * Starting listning
     */
    public function execute()
    {
        $this->listner();
    }

    public function step(Closure $Listner)
    {
        $this->StepListner = $Listner;
        return $this;
    }

    public function then(Closure $Listner)
    {
        $this->ThenListner = $Listner;
        return $this;
    }

    public function catch(Closure $Listner)
    {
        $this->CatchListner = $Listner;
        return $this;
    }

    /**
     * calculate the timeout
     *
     * @param int $MaxTime
     *            - time in microseconds
     * @param int $StartTime
     *            - microtime(true) time in microseconds
     * @return int
     */
    protected function calcTimeout(int $MaxTime, int $StartTime)
    {
        return ($MaxTime - (microtime(true) * 1000000 - $StartTime));
    }

    /**
     * Starting listning process for
     * WARNING: pcntl_wait
     */
    protected function listner()
    {
        try {
            /**
             * ready sub processes
             *
             * @var array $Results
             */
            $Results = [];
            /**
             * Count of child processes
             *
             * @var int $CountChildProcesses
             */
            $CountChildProcesses = count($this->Processes);
            /**
             * Links to pipe sub processes
             *
             * @var array $Processes
             */
            $Processes = [];
            /**
             *
             * @var Process $Child
             */
            foreach ($this->Processes as $Child) {
                $Processes[$Child->getPId()] = $Child;
            }
            $status = 0;
            $Test = null;
            /**
             * time of start work
             *
             * @var float $start
             */
            $start = microtime(true) * 1000000;
            while ($this->calcTimeout($this->MaxWorkTime, $start) >= 0) {
                $time = $this->calcTimeout($this->MaxWorkTime, $start);
                if ($time <= 0) {
                    break;
                }
                $pid = pcntl_wait($status, WNOHANG, $Test);
                unset($Processes[$pid]);
                if (count($Processes) == 0) {
                    break;
                }
                usleep(10000);
            }
            call_user_func($this->ThenListner, $this->Processes);
        } catch (\Exception $E) {
            call_user_func($this->CatchListner, $E, $this->Processes);
        }
    }
}

