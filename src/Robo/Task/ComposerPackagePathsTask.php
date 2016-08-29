<?php

namespace Cheppers\Robo\Drupal\Robo\Task;

use Cheppers\AssetJar\AssetJarAware;
use Cheppers\AssetJar\AssetJarAwareInterface;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

class ComposerPackagePathsTask extends BaseTask implements CommandInterface, AssetJarAwareInterface
{
    use AssetJarAware;

    public static function parseOutput(string $stdOutput): array
    {
        $packagePaths = [];

        $stdOutput = trim($stdOutput, "\n\r");
        if (!$stdOutput) {
            return $packagePaths;
        }

        $lines = explode("\n", $stdOutput);
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 2) + [1 => ''];
            $packagePaths[$parts[0]] = $parts[1];
        }

        return $packagePaths;
    }

    /**
     * @var string
     */
    protected $processClass = Process::class;

    /**
     * @var array
     */
    protected $assets = [
        'packagePaths' => [],
    ];

    //region Option - workingDirectory
    /**
     * @var string
     */
    public $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @param string $workingDirectory
     *
     * @return $this
     */
    public function setWorkingDirectory(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;

        return $this;
    }
    //endregion

    //region Option - composerExecutable
    /**
     * @var string
     */
    public $composerExecutable = 'composer';

    public function getComposerExecutable(): string
    {
        return $this->composerExecutable;
    }

    /**
     * @param string $composerExecutable
     *
     * @return $this
     */
    public function setComposerExecutable(string $composerExecutable)
    {
        $this->composerExecutable = $composerExecutable;

        return $this;
    }
    //endregion

    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    /**
     * @param array $options
     *
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

                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'composerExecutable':
                    $this->setComposerExecutable($value);
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
        /** @var \Symfony\Component\Process\Process $process */
        $process = new $this->processClass($this->getCommand());

        $exitCode = $process->run();
        if ($exitCode) {
            return new Result($this, $exitCode, $process->getErrorOutput(), $this->assets);
        }

        $this->assets['workingDirectory'] = $this->getWorkingDirectory();
        $this->assets['packagePaths'] = static::parseOutput($process->getOutput());
        $this->releaseAssets();


        return Result::success($this, '', $this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        $cmdPattern = '';
        $cmdArgs = [];

        $wd = $this->getWorkingDirectory();
        if ($wd) {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg($wd);
        }

        $cmdPattern .= '%s show -P';
        $cmdArgs[] = escapeshellcmd($this->getComposerExecutable());

        return vsprintf($cmdPattern, $cmdArgs);
    }

    /**
     * @return $this
     */
    protected function releaseAssets()
    {
        if ($this->hasAssetJar()) {
            foreach ($this->assets as $name => $value) {
                if ($this->getAssetJarMap($name)) {
                    $this->setAssetJarValue($name, $value);
                }
            }
        }

        return $this;
    }
}
