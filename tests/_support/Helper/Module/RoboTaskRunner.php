<?php

namespace Helper\Module;

use Codeception\Module as CodeceptionModule;
use Helper\Dummy\Output as DummyOutput;
use Robo\Robo;
use Robo\Runner;
use Symfony\Component\Console\Output\OutputInterface;

class RoboTaskRunner extends CodeceptionModule
{
    /**
     * @var \Helper\Dummy\Output
     */
    protected $roboTaskStdOutput = null;

    protected $roboTaskExitCode = 0;

    public function getRoboTaskExitCode(): int
    {
        return $this->roboTaskExitCode;
    }

    public function getRoboTaskStdOutput(): string
    {
        return $this->roboTaskStdOutput->output;
    }

    public function getRoboTaskStdError(): string
    {
        /** @var \Helper\Dummy\Output $errorOutput */
        $errorOutput = $this->roboTaskStdOutput->getErrorOutput();

        return $errorOutput->output;
    }

    public function runRoboTask(string $class, string ...$args): void
    {
        $config = [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
        ];
        $this->roboTaskStdOutput = new DummyOutput($config);
        $this->roboTaskStdOutput->setErrorOutput(new DummyOutput($config));

        array_unshift($args, 'RoboTaskRunner.php', '--no-ansi');

        $container = Robo::createDefaultContainer(null, $this->roboTaskStdOutput);
        $container->add('output', $this->roboTaskStdOutput, false);

        Robo::setContainer($container);
        $runner = new Runner($class);

        $this->roboTaskExitCode = $runner->execute($args);
    }
}
