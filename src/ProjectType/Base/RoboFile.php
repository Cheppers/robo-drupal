<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Cheppers\LintReport\Reporter\BaseReporter;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Git\GitTaskLoader;
use Cheppers\Robo\Serialize\SerializeTaskLoader;
use League\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Tasks;
use Webmozart\PathUtil\Path;

class RoboFile extends Tasks
{
    use GitTaskLoader;
    use SerializeTaskLoader;

    /**
     * @var array
     */
    protected $composerInfo = [];

    /**
     * @var array
     */
    protected $composerLock = [];

    /**
     * @var string
     */
    protected $packageVendor = '';

    /**
     * @var string
     */
    protected $packageName = '';

    /**
     * @var string
     */
    protected $binDir = 'vendor/bin';

    /**
     * @var string
     */
    protected $envNamePrefix = '';

    /**
     * @var string
     */
    protected $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig
     */
    protected $projectConfig = null;

    /**
     * Allowed values: dev, git-hook, ci, prod, jenkins
     *
     * @todo The "git-hook" isn't an environment.
     *
     * @var string
     */
    protected $environment = 'dev';

    /**
     * Root directory of the "cheppers/robo-drupal" package.
     *
     * @var string
     */
    protected $roboDrupalRoot = '';

    public function __construct()
    {
        putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
        $this->roboDrupalRoot = Path::makeAbsolute('../../..', __DIR__);

        $this
            ->initProjectConfig()
            ->initComposerInfo()
            ->initEnvNamePrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container)
    {
        BaseReporter::lintReportConfigureContainer($container);
        parent::setContainer($container);

        return $this;
    }

    //region Tasks.
    /**
     * Export the project configuration.
     *
     * @param string $format One of: json, yaml.
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    public function exportProjectConfig(string $format = 'json'): CollectionBuilder
    {
        return $this->getTaskExportProjectConfig([
            'serializer' => $format,
        ]);
    }

    public function writableWorkingCopy(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskWritableWorkingCopy());
    }

    public function unlockSettingsPhp(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskUnlockSettingsPhp());
    }
    //endregion

    /**
     * @return $this
     */
    protected function initProjectConfig()
    {
        if (!$this->projectConfig) {
            if (file_exists(Utils::$projectConfigFileName)) {
                require_once Utils::$projectConfigFileName;
                $this->projectConfig = $GLOBALS['projectConfig'];
            }

            $class = $this->projectConfigClass;
            $this->projectConfig = $GLOBALS['projectConfig'] ?? new $class();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initComposerInfo()
    {
        if ($this->composerInfo || !is_readable('composer.json')) {
            return $this;
        }

        $this->composerInfo = json_decode(file_get_contents('composer.json'), true);
        list($this->packageVendor, $this->packageName) = explode('/', $this->composerInfo['name']);

        if (!empty($this->composerInfo['config']['bin-dir'])) {
            $this->binDir = $this->composerInfo['config']['bin-dir'];
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initComposerLock()
    {
        if (!$this->composerLock) {
            if (is_readable('composer.lock')) {
                $this->composerLock = json_decode(file_get_contents('composer.lock'), true);
            }

            $this->composerLock += [
                'packages' => [],
                'packages-dev' => [],
            ];

            foreach (['packages', 'packages-dev'] as $key) {
                $this->composerLock += [$key => []];
                $this->composerLock[$key] = Utils::itemProperty2ArrayKey(
                    $this->composerLock[$key],
                    'name'
                );
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initEnvNamePrefix()
    {
        $this->envNamePrefix = strtoupper(str_replace('-', '_', $this->packageName));

        return $this;
    }

    protected function getEnvName(string $name): string
    {
        return "{$this->envNamePrefix}_" . strtoupper($name);
    }

    protected function getEnvironment(): string
    {
        if ($this->environment) {
            return $this->environment;
        }

        return getenv($this->getEnvName('environment')) ?: 'dev';
    }

    protected function backToRootDir(string $from): string
    {
        return Path::makeRelative('.', $from) ?: '.';
    }

    protected function getPhpExecutable(): string
    {
        return getenv($this->getEnvName('php_executable')) ?: PHP_BINARY;
    }

    protected function getPhpdbgExecutable(): string
    {
        return getenv($this->getEnvName('phpdbg_executable')) ?: Path::join(PHP_BINDIR, 'phpdbg');
    }

    //region Task builders.
    protected function getTaskExportProjectConfig(array $options = []): CollectionBuilder
    {
        $options['subject'] = $this->projectConfig;
        $options += [
            'serializer' => 'json',
            'destination' => $this->output(),
        ];

        return $this->taskSerialize($options);
    }

    protected function getTaskWritableWorkingCopy(): \Closure
    {
        // @todo Create dedicated Robo task.
        return function () {
            $mask = umask();

            $listFilesResult = $this
                ->taskGitListFiles()
                ->run();

            $fileNames = array_keys($listFilesResult['files']);
            $dirNamesByDepth = Utils::dirNamesByDepth($fileNames);
            ksort($dirNamesByDepth, SORT_NUMERIC);
            foreach ($dirNamesByDepth as $dirNames) {
                $this->_chmod($dirNames, 0777, $mask);
            }

            $this->_chmod($fileNames, 0666, $mask);

            return 0;
        };
    }

    protected function getTaskUnlockSettingsPhp(string $siteId = ''): \Closure
    {
        return function () use ($siteId): int {
            $pc = $this->projectConfig;
            $mask = umask();
            $sites = $siteId ?
                [$siteId => $pc->sites[$siteId]]
                : $pc->sites;

            foreach ($sites as $site) {
                $siteDir = "{$pc->drupalRootDir}/sites/{$site->id}";
                if (file_exists($siteDir)) {
                    $this->_chmod($siteDir, 0777, $mask);

                    if (file_exists("$siteDir/settings.php")) {
                        $this->_chmod("$siteDir/settings.php", 0666, $mask);
                    }
                }
            }

            return 0;
        };
    }
    //endregion
}
