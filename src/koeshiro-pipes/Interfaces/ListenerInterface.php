<?php
namespace Koeshiro\Pipes\Interfaces;

interface ListenerInterface
{

    /**
     * Listning procces for wait results
     *
     * @param array $Processes
     * @param int $MaxTime
     *            - in microseconds default 7000000 (7seconds)
     * @param bool $Strict
     *            - if $strict false ignoring strerr results and continue
     *            listning while time is not over or all processes is not done
     */
    public function __construct(array $Processes, int $MaxTime = 7000000, bool $Strict = false);

    /**
     * Listner of end work of sub processes
     *
     * @param \Closure $Listner
     * @return self
     */
    public function then(\Closure $Listner);

    /**
     * Listner of some error
     *
     * @param \Closure $Listner
     * @return self
     */
    public function catch(\Closure $Listner);

    /**
     * Listner of steps
     *
     * @param \Closure $Listner
     * @return self
     */
    public function step(\Closure $Listner);

    /**
     * Starting listning
     */
    public function execute();
}

