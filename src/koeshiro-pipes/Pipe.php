<?php
namespace Koeshiro\Pipes;

use Koeshiro\Pipes\Interfaces\PipeInterface;
use Koeshiro\Pipes\Interfaces\ProcessInterface;
use TypeError;

class Pipe implements PipeInterface
{

    /**
     *
     * @var ProcessInterface[]
     */
    protected $nodes = [];

    protected $settings = [];

    public function __construct(array $nodes, array $settings = [
        'timeout' => 10000000
    ])
    {
        foreach ($nodes as $node) {
            if (! ($node instanceof ProcessInterface)) {
                throw new TypeError("Node must be instanceof ProcessInterface");
            }
        }
        $this->nodes = $nodes;
        $this->settings = $settings;
    }

    public function start(string $startData = "")
    {
        $results = [];
        $lastData = $startData;
        foreach ($this->nodes as $node) {
            if ($node instanceof ProcessInterface) {
                $node->write($lastData);
                $node->wait($this->settings['timeout']);
                $lastData = $node->state()['stdout'];
                $results[] = $lastData;
            }
        }
        return $results;
    }
}

