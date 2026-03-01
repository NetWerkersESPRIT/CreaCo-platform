<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class AiProcessManager
{
    private string $scriptPath;
    private ?Process $process = null;

    public function __construct(string $scriptPath)
    {
        // path to the python server entry point
        $this->scriptPath = $scriptPath;
    }

    public function isRunning(): bool
    {
        if (!$this->process) {
            return false;
        }

        return $this->process->isRunning();
    }

    public function start(): void
    {
        if ($this->isRunning()) {
            return;
        }

        // start the python service in background
        $this->process = new Process(["python", $this->scriptPath]);
        $this->process->setTimeout(0);
        $this->process->start();
    }
}
