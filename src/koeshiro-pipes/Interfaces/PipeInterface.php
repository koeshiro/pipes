<?php
namespace Koeshiro\Pipes\Interfaces;

interface PipeInterface
{

    /**
     * Create sub procces by command and arguments
     *
     * @param Array<Process|Listner> $node
     *            - command for start sub process (example: php)
     * @param array $args
     *            - arguments for command (example: [['key' => '-f' , 'value' => '/home/test/test.php']])
     */
    public function __construct(array $node);

    public function start();
}

