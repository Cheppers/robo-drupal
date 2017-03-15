<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Cheppers\LintReport\Reporter\BaseReporter;
use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\Robo\DrupalCoreTestsTaskLoader;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Drush\DrushTaskLoader;
use Cheppers\Robo\Git\GitTaskLoader;
use Cheppers\Robo\Serialize\SerializeTaskLoader;
use League\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\Tasks;
use Webmozart\PathUtil\Path;

class RoboFile extends Tasks
{
    use DrupalCoreTestsTaskLoader;
    use DrushTaskLoader;
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
    protected $vendorDir = 'vendor';

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
        $this->roboDrupalRoot = Utils::getRoboDrupalRoot();

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
    public function listDrupalTests(): TaskInterface
    {
        return $this->getTaskDrupalCoreTestsList();
    }

    public function testDrupal(
        array $args,
        array $options = [
            'site' => '',
            'php' => '',
            'db' => '',
        ]
    ): CollectionBuilder {
        $siteId = $this->validateInputSiteId($options['site']);
        $phpVariants = $this->validateInputPhpVariantIds($options['php']);
        $databaseServers = $this->validateInputDatabaseServerIds($options['db']);

        $subjects = [];
        foreach ($args as $arg) {
            $subjects = array_merge($subjects, explode(',', $arg));
        }

        $pc = $this->projectConfig;
        $cb = $this->collectionBuilder();

        if (!$subjects) {
            $subjects = Utils::filterDisabled($pc->defaultDrupalTestSubjects);
        }

        if (!$subjects) {
            $cb->addCode(function () {
                $this->yell('@todo Better error message. Subject is mandatory.', 40, 'red');

                return 1;
            });

            return $cb;
        }

        $placeholders = [
            '{php}' => '',
            '{db}' => '',
            '{siteBranch}' => $siteId,
        ];
        foreach ($databaseServers as $databaseServer) {
            $tasks = [];
            $placeholders['{db}'] = $databaseServer->id;
            foreach ($phpVariants as $phpVariant) {
                $placeholders['{php}'] = $phpVariant->id;
                $url = $pc->getSiteVariantUrl($placeholders);

                if (!$tasks) {
                    $tasks['enable.simpletest'] = $this->getTaskDrushPmEnable($url, ['simpletest']);
                }

                $taskId = "run-tests.{$phpVariant->id}.{$databaseServer->id}";
                $tasks[$taskId] = $this->getTaskDrupalCoreTestsRun($subjects, $siteId, $phpVariant, $databaseServer);
            }

            $cb->addTaskList($tasks);
        }

        return $cb;
    }

    public function cleanDrupalTests(): CollectionBuilder
    {
        return $this->getTaskDrupalCoreTestsClean();
    }

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

    /**
     * Make all VCS tracked files and directories writable.
     */
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
        if (!$this->projectConfig && file_exists(Utils::$projectConfigFileName)) {
            $this->projectConfig = include Utils::$projectConfigFileName;
        }

        if (!$this->projectConfig) {
            $class = $this->projectConfigClass;
            $this->projectConfig = new $class();
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

        if (!empty($this->composerInfo['config']['vendor-dir'])) {
            $this->vendorDir = $this->composerInfo['config']['vendor-dir'];
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

    protected function getFallbackFileName(string $fileName, string $path): string
    {
        if (file_exists("$path/$fileName")) {
            return '';
        }

        $paths = [
            getcwd(),
            $this->roboDrupalRoot,
        ];

        $root = [
            'Gemfile',
        ];
        foreach ($paths as $path) {
            if (strpos($fileName, 'node_modules/.bin/') === 0) {
                if (file_exists("$path/$fileName")) {
                    return "$path/$fileName";
                }
            } elseif (in_array($fileName, $root)) {
                if (file_exists("$path/$fileName")) {
                    return "$path/$fileName";
                }
            } elseif (file_exists("$path/src/$fileName")) {
                return "$path/src/$fileName";
            }
        }

        throw new \InvalidArgumentException("Has no fallback for file: '$fileName'");
    }

    //region Task builders.
    protected function getTaskDrushPmEnable(string $uri, array $extensions): CollectionBuilder
    {
        $options = [
            'root' => $this->projectConfig->drupalRootDir,
            'uri' => $uri,
            'yes' => true,
        ];

        return $this->taskDrush('pm-enable', $options, $extensions);
    }

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\CoreTests\ListTask|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskDrupalCoreTestsList(): CollectionBuilder
    {
        return $this
            ->taskDrupalCoreTestsList()
            ->setOutput($this->output())
            ->setDrupalRoot($this->projectConfig->drupalRootDir);
    }

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\CoreTests\RunTask|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskDrupalCoreTestsRun(
        array $subjects,
        string $siteId,
        PhpVariantConfig $phpVariant,
        DatabaseServerConfig $databaseServer
    ): CollectionBuilder {
        $pc = $this->projectConfig;
        $url = $pc->getSiteVariantUrl([
            '{siteBranch}' => $siteId,
            '{php}' => $phpVariant->id,
            '{db}' => $databaseServer->id,
        ]);
        $backToRootDir = $this->backToRootDir($pc->drupalRootDir);

        // @todo Configurable protocol. HTTP vs HTTPS.
        return $this
            ->taskDrupalCoreTestsRun()
            ->setDrupalRoot($this->projectConfig->drupalRootDir)
            ->setUrl("http://$url")
            ->setXml(Path::join($backToRootDir, $pc->reportDir, 'tests'))
            ->setColorized(true)
            ->setNonHtml(true)
            ->setPhpExecutable(PHP_BINARY)
            ->setPhp($phpVariant->getPhpExecutable())
            ->setArguments($subjects);
    }

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\CoreTests\CleanTask|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskDrupalCoreTestsClean(): CollectionBuilder
    {
        return $this
            ->taskDrupalCoreTestsClean()
            ->setDrupalRoot($this->projectConfig->drupalRootDir);
    }

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
                foreach (array_unique($site->urls) as $siteDir) {
                    $dir = "{$pc->drupalRootDir}/sites/{$siteDir}";
                    if (file_exists($dir)) {
                        $this->_chmod($dir, 0777, $mask);

                        if (file_exists("$dir/settings.php")) {
                            $this->_chmod("$dir/settings.php", 0666, $mask);
                        }
                    }
                }
            }

            return 0;
        };
    }

    protected function getTaskPublicFilesClean(string $siteDir): \Closure
    {
        return $this->getTaskDirectoryClean("{$this->projectConfig->drupalRootDir}/sites/{$siteDir}/files");
    }

    protected function getTaskPrivateFilesClean($siteDir): \Closure
    {
        return $this->getTaskDirectoryClean("{$this->projectConfig->outerSitesSubDir}/{$siteDir}/private");
    }

    protected function getTaskDirectoryClean(string $dir): \Closure
    {
        return function () use ($dir) {
            $this->_mkdir($dir);

            $entry = new \DirectoryIterator($dir);
            while ($entry->valid()) {
                if (!$entry->isDot() && $entry->isDir()) {
                    $this->_deleteDir($entry->getRealPath());
                } elseif ($entry->isFile() || $entry->isLink()) {
                    $this->_remove($entry->getRealPath());
                }

                $entry->next();
            }

            return 0;
        };
    }

    /**
     * Build a pre-configured DrushSiteInstall task.
     *
     * @todo Support advanced config management tools.
     */
    protected function getTaskDrushSiteInstall(
        SiteConfig $site,
        DatabaseServerConfig $db,
        PhpVariantConfig $php
    ): TaskInterface {
        $pc = $this->projectConfig;
        $backToRootDir = $this->backToRootDir($pc->drupalRootDir);

        $siteDir = $pc->getSiteVariantDir([
            '{siteBranch}' => $site->id,
            '{db}' => $db->id,
            '{php}' => $php->id,
        ]);

        $task = $this
            ->taskDrush('site-install')
            ->setWorkingDirectory($pc->drupalRootDir)
            ->setDrushExecutable("$backToRootDir/{$this->binDir}/drush")
            ->setCmdOption('yes', true)
            ->setCmdOption('sites-subdir', $siteDir);

        $configDir = "{$pc->outerSitesSubDir}/$siteDir/config/sync";
        if (file_exists($configDir) && glob("$configDir/*.yml")) {
            $task->setCmdOption('config-dir', "$backToRootDir/$configDir");
        }

        $task->setCmdArguments([$site->installProfileName]);

        return $task;
    }
    //endregion

    //region Input validators.
    protected function validateInputSiteId(string $siteId, bool $required = false): SiteConfig
    {
        if (!$siteId) {
            if ($required) {
                throw new \InvalidArgumentException('Site ID is required', 1);
            }

            $siteId = $this->projectConfig->getDefaultSiteId();
        }

        if ($siteId) {
            if (!array_key_exists($siteId, $this->projectConfig->sites)) {
                throw new \InvalidArgumentException("Unknown site ID: '$siteId'", 1);
            }

            return $this->projectConfig->sites[$siteId];
        }

        throw  new \Exception('There is no site. Run "robo site:create"');
    }

    /**
     * @return PhpVariantConfig[]
     */
    protected function validateInputPhpVariantIds(string $input, bool $required = false): array
    {
        if (!$input && $required) {
            throw new \InvalidArgumentException('@todo Line: ' . __LINE__);
        }

        return $this->validateInputIdList(
            $input,
            $this->projectConfig->phpVariants,
            'Unknown PHP variant identifiers: "%s"'
        );
    }

    protected function validateInputPhpVariantId(string $input, bool $required = false): PhpVariantConfig
    {
        if (!$input) {
            if ($required) {
                throw new \InvalidArgumentException('@todo Line: ' . __LINE__);
            }

            return reset($this->projectConfig->phpVariants);
        }

        if (!isset($this->projectConfig->phpVariants[$input])) {
            throw new \InvalidArgumentException(sprintf('Unknown PHP variant identifier: "%s"', $input));
        }

        return $this->projectConfig->phpVariants[$input];
    }

    /**
     * @return DatabaseServerConfig[]
     */
    protected function validateInputDatabaseServerIds(string $input, bool $required = false): array
    {
        if (!$input && $required) {
            throw new \InvalidArgumentException('@todo Line: ' . __LINE__);
        }

        return $this->validateInputIdList(
            $input,
            $this->projectConfig->databaseServers,
            'Unknown Database Server identifiers: "%s"'
        );
    }

    /**
     * @return array
     */
    protected function validateInputIdList(string $input, array $available, string $errorMsgTpl): array
    {
        if (!$input) {
            return $available;
        }

        $ids = explode(',', $input);
        $missingIds = array_diff($ids, array_keys($available));
        if ($missingIds) {
            throw new \InvalidArgumentException(sprintf($errorMsgTpl, implode(', ', $missingIds)));
        }

        return array_intersect_key($available, array_flip($ids));
    }
    //endregion

    protected function getGitTrackedFiles(array $paths): array
    {
        $result = $this
            ->taskGitListFiles()
            ->setPaths($paths)
            ->run()
            ->stopOnFail();

        return array_keys($result['files']);
    }
}
