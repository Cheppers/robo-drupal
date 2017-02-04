<?php

namespace Helper\Module;

use Codeception\Module as CodeceptionModule;
use Robo\Robo;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RoboTaskRunner extends CodeceptionModule
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $roboTaskOutput = null;

    protected $roboTaskExitCode = 0;

    public function getRoboTaskExitCode(): int
    {
        return $this->roboTaskExitCode;
    }

    protected $roboTaskStdOutput = '';

    public function getRoboTaskStdOutput(): string
    {
        return $this->roboTaskStdOutput;
    }

    protected $roboTaskStdError = '';

    public function getRoboTaskStdError(): string
    {
        return $this->roboTaskStdError;
    }

    public function runRoboTask(string $class, string ...$args): void
    {
        $this->roboTaskOutput = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        array_unshift($args, 'RoboTaskRunner.php');
        $this->roboTaskExitCode = Robo::run(
            $args,
            [
                $class,
            ],
            'RoboDrupalTester',
            '0.0.0-alpha0',
            $this->roboTaskOutput
        );

        $this->roboTaskStdOutput = $this->roboTaskOutput->fetch();
    }
}
