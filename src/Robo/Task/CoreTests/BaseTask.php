<?php

namespace Cheppers\Robo\Drupal\Robo\Task\CoreTests;

use Cheppers\Robo\Drush\Utils;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\CommandInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Cheppers\Robo\Drupal\Robo\Task\BaseTask as RoboBaseTask;
use Symfony\Component\Process\Process;

abstract class BaseTask extends RoboBaseTask implements
    CommandInterface,
    ContainerAwareInterface,
    OutputAwareInterface
{
    use ContainerAwareTrait;
    use OutputAwareTrait;

    /**
     * @var \Symfony\Component\Process\Process
     */
    protected $processClass = Process::class;

    /**
     * @var array
     */
    protected $assets = [];

    //region Options.
    //region Option - drupalRoot.
    /**
     * @var string
     */
    protected $drupalRoot = '';

    public function getDrupalRoot(): string
    {
        return $this->drupalRoot;
    }

    /**
     * @return $this
     */
    public function setDrupalRoot(string $drupalRoot)
    {
        $this->drupalRoot = $drupalRoot;

        return $this;
    }
    //endregion

    //region Option - phpExecutable.
    /**
     * @var string
     */
    protected $phpExecutable = '';

    public function getPhpExecutable(): string
    {
        return $this->phpExecutable;
    }

    /**
     * @return $this
     */
    public function setPhpExecutable(string $phpExecutable)
    {
        $this->phpExecutable = $phpExecutable;

        return $this;
    }
    //endregion

    //region Option - arguments.
    /**
     * @var array
     */
    protected $arguments = [];

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }
    //endregion

    /**
     * @var bool
     */
    protected $quiet = false;

    public function isQuiet(): bool
    {
        return $this->quiet;
    }

    /**
     * @return $this
     */
    public function setQuiet(bool $quiet)
    {
        $this->quiet = $quiet;

        return $this;
    }
    //endregion

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'assetJar':
                    $this->setAssetJar($value);
                    break;

                case 'assetJarMapping':
                    $this->setAssetJarMapping($value);
                    break;

                case 'drupalRoot':
                    $this->setDrupalRoot($value);
                    break;

                case 'phpExecutable':
                    $this->setPhpExecutable($value);
                    break;

                case 'arguments':
                    $this->setArguments($value);
                    break;

                case 'quiet':
                    $this->setQuiet($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(): Result
    {
        $command = $this->getCommand();
        $this->printTaskInfo('Run: <info>{command}</info>', ['command' => $command]);

        $this->runPrepare();

        /** @var \Symfony\Component\Process\Process $process */
        $process = new $this->processClass($command);
        $exitCode = $process->run(function ($type, $data) {
            $this->runCallback($type, $data);
        });

        $this->runReleaseAssets();

        return new Result($this, $exitCode, $process->getErrorOutput(), $this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        $cmdPattern = '';
        $cmdArgs = [];

        $drupalRoot = $this->getDrupalRoot();
        if ($drupalRoot) {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg($drupalRoot);
        }

        $cmdPattern .= '%s core/scripts/run-tests.sh';
        $cmdArgs[] = escapeshellcmd($this->getPhpExecutable() ?: PHP_BINARY);

        foreach ($this->buildOptions() as $name => $value) {
            switch (gettype($value)) {
                case 'boolean':
                    if ($value) {
                        $cmdPattern .= " --{$name}";
                    }
                    break;

                case 'integer':
                    if ($value !== 0) {
                        $cmdPattern .= " --{$name} %d";
                        $cmdArgs[] = $value;
                    }
                    break;

                case 'string':
                    if ($value) {
                        $cmdPattern .= " --{$name} %s";
                        $cmdArgs[] = escapeshellarg($value);
                    }
                    break;
            }
        }

        $arguments = Utils::filterDisabled($this->getArguments());
        if ($arguments) {
            $cmdPattern .= ' %s';
            $cmdArgs[] = escapeshellarg(implode(',', $arguments));
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }

    /**
     * @return $this
     */
    protected function runPrepare()
    {
        return $this;
    }

    protected function buildOptions(): array
    {
        return [];
    }

    protected function runCallback(string $type, string $data): void
    {
        switch ($type) {
            case Process::OUT:
                if (!$this->isQuiet()) {
                    $this->output()->write($data);
                }
                break;

            case Process::ERR:
                $this->printTaskError($data);
                break;
        }
    }

    /**
     * @return $this
     */
    protected function runReleaseAssets()
    {
        if (!$this->hasAssetJar()) {
            return $this;
        }

        $assetJar = $this->getAssetJar();
        foreach ($this->assets as $name => $value) {
            if ($this->assetJarMapping) {
                $parents = $this->getAssetJarMap($name);
                if ($parents) {
                    $assetJar->setValue($parents, $value);
                }
            }
        }

        return $this;
    }
}
