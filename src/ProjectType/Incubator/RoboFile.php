<?php

namespace Cheppers\Robo\Drupal\ProjectType\Incubator;

use Cheppers\AssetJar\AssetJar;
use Cheppers\LintReport\Reporter\CheckstyleReporter;
use Cheppers\Robo\Drupal\Config\DrupalExtensionConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use Cheppers\Robo\Drupal\Robo\ComposerTaskLoader;
use Cheppers\Robo\Drupal\Robo\DrupalCoreTestsTaskLoader;
use Cheppers\Robo\Drupal\Robo\DrupalTaskLoader;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\ESLint\ESLintTaskLoader;
use Cheppers\Robo\Phpcs\PhpcsTaskLoader;
use Cheppers\Robo\ScssLint\ScssLintTaskLoader;
use Cheppers\Robo\TsLint\TsLintTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\Task\Filesystem\loadShortcuts as FilesystemShortcuts;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class RoboFile extends Base\RoboFile
{
    use ComposerTaskLoader;
    use DrupalCoreTestsTaskLoader;
    use DrupalTaskLoader;
    use ESLintTaskLoader;
    use PhpcsTaskLoader;
    use ScssLintTaskLoader;
    use TsLintTaskLoader;
    use FilesystemShortcuts;

    protected $areManagedDrupalExtensionsInitialized = false;

    /**
     * {@inheritdoc}
     */
    protected $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig
     */
    protected $projectConfig;

    //region Tasks.
    //region Self
    //region Self - Git hooks.
    /**
     * Git "pre-commit" hook callback.
     */
    public function selfGithookPreCommit(): CollectionBuilder
    {
        $this->environment = 'git-hook';

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'lint.composer.lock' => $this->taskComposerValidate(),
                'lint.phpcs.psr2' => $this->getTaskPhpcsLint([
                    'standards' => ['PSR2'],
                    'files' => $this->selfPhpcsFiles,
                ]),
            ]);
    }

    /**
     * Git "post-checkout" hook callback.
     *
     * @param string $oldRef
     *   The ref of the previous HEAD.
     * @param string $newRef
     *   The ref of the new HEAD (which may or may not have changed).
     * @param string $isBranch
     *   A flag indicating whether the checkout was a branch checkout (changing
     *   branches, flag=1) or a file checkout (retrieving a file from the index,
     *   flag=0).
     *
     * @return CollectionBuilder
     */
    public function selfGithookPostCheckout(string $oldRef, string $newRef, string $isBranch): CollectionBuilder
    {
        $this->environment = 'git-hook';

        return $this->collectionBuilder()->addCode(function () use ($oldRef, $newRef, $isBranch) {
            $pc  = $this->projectConfig;
            // @todo Create dedicated Robo task. Maybe in the cheppers/robo-git package.
            $command = sprintf(
                '%s diff --exit-code --name-only %s..%s -- %s %s',
                escapeshellcmd($pc->gitExecutable),
                escapeshellarg($oldRef),
                escapeshellarg($newRef),
                escapeshellarg('composer.json'),
                escapeshellarg('composer.lock')
            );

            $process = new Process($command);
            $process->run();
            if (!$process->isSuccessful()) {
                $this->yell('The "composer.{json|lock}" has changed. You have to run `composer install`', 40, 'yellow');
            }

            return 0;
        });
    }
    //endregion

    //region Self - Lint.
    /**
     * @todo Move this settings to ProjectConfig.php.
     *
     * @var string[]
     */
    protected $selfPhpcsFiles = [
        'src/',
        'RoboFile.php',
    ];

    public function selfLint(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addTaskList([
                'lint:phpcs' => $this->getTaskPhpcsLint([
                    'standards' => ['PSR2'],
                    'files' => $this->selfPhpcsFiles,
                ]),
            ]);
    }

    public function selfLintPhpcs(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addTaskList([
                'lint:phpcs' => $this->getTaskPhpcsLint([
                    'standards' => ['PSR2'],
                    'files' => $this->selfPhpcsFiles,
                ]),
                'composer:validate' => $this->taskComposerValidate(),
            ]);
    }
    //endregion

    /**
     * Lists the managed Drupal extensions.
     */
    public function selfManagedExtensions(
        array $options = [
            'format' => 'table',
        ]
    ) {
        $managedExtensions = $this->getManagedDrupalExtensions();
        $rows = [];
        foreach ($managedExtensions as $e) {
            $rows[$e->packageName] = [
                'vendor' => $e->packageVendor,
                'name' => $e->packageName,
                'path' => $e->path,
            ];
        }

        switch ($options['format']) {
            case 'table':
                (new Table($this->output()))
                    ->setHeaders(['Vendor', 'Name', 'Path'])
                    ->addRows($rows)
                    ->render();
                break;

            case 'yaml':
                $this->output()->write(Yaml::dump($rows));
                break;
        }
    }
    //endregion

    //region Git hooks.
    public function githooksInstall(): ?CollectionBuilder
    {
        $extensions = Utils::filterDisabled(
            $this->getManagedDrupalExtensions(),
            'hasGit'
        );

        if (!$extensions) {
            $this->say('There is no managed extension under Git VCS.');

            return null;
        }

        $cb = $this->collectionBuilder();
        foreach ($extensions as $extension) {
            $cb->addCode($this->getTaskGitHookInstall($extension));
        }

        return $cb;
    }

    /**
     * @todo Implement.
     */
    public function githooksUninstall(): ?CollectionBuilder
    {
        $this->yell('@todo');

        return null;
    }

    public function githookPreCommit(string $extensionPath, string $extensionName): CollectionBuilder
    {
        $extensions = $this->getManagedDrupalExtensions();
        $extension = $extensions[$extensionName];

        $this->environment = 'git-hook';

        return $this
            ->collectionBuilder()
            ->addTask($this->getTaskPhpcsLintDrupalExtension($extension));
    }
    //endregion

    //region Site - CRUD.
    /**
     * @option string $profile Name of the install profile.
     * @option string $long    Long machine-name prefix. Example: "awesome"
     * @option string $short   Short machine-name prefix. Example: "aws"
     */
    public function siteCreate(
        string $sitesSubDir = '',
        array $options = [
            'profile|p' => 'standard',
            'long|l' => '',
            'short|s' => '',
        ]
    ): CollectionBuilder {
        $pc = $this->projectConfig;
        if (!$sitesSubDir) {
            $defaultSettingsPhp = "{$pc->drupalRootDir}/sites/default/settings.php";
            $sitesSubDir = (!$this->fs->exists($defaultSettingsPhp) ? 'default' : $options['profile']);
        }

        $o = array_filter([
            'siteBranch' => $sitesSubDir,
            'installProfile' => $options['profile'],
            'machineNameLong' => $options['long'],
            'machineNameShort' => $options['short'],
        ]);

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'create:site' => $this->getTaskDrupalSiteCreate($o),
                'rebuild:sites-php' => $this->getTaskDrupalRebuildSitesPhp(),
            ]);
    }

    public function siteDelete(
        string $siteConfig,
        array $options = [
            'yes' => false,
        ]
    ): ?CollectionBuilder {
        $siteConfig = $this->validateInputSiteId($siteConfig, true);

        // @todo Better description.
        $is_sure = $options['yes'] || $this->io()->confirm('Are you sure?', false);
        if (!$is_sure) {
            return null;
        }

        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskDrupalSiteDelete($siteConfig))
            ->addTask($this->getTaskDrupalRebuildSitesPhp());
    }

    public function siteInstall(
        string $siteId = '',
        array $options = [
            'db' => '',
            'php' => '',
        ]
    ): CollectionBuilder {
        $site = $this->validateInputSiteId($siteId);
        $databaseServers = $this->validateInputDatabaseServerIds($options['db']);
        $phpVariant = $this->validateInputPhpVariantId($options['php']);

        $placeholders = [
            '{siteBranch}' => $site->id,
            '{php}' => $phpVariant->id,
            '{db}' => null,
        ];

        $cb = $this->collectionBuilder();
        $cb->addCode($this->getTaskUnlockSettingsPhp($site->id));
        foreach ($databaseServers as $databaseServer) {
            $placeholders['{db}'] = $databaseServer->id;
            $siteDir = $this->projectConfig->getSiteVariantDir($placeholders);
            $cb->addCode($this->getTaskPublicFilesClean($siteDir));
            $cb->addCode($this->getTaskPrivateFilesClean($siteDir));
            $cb->addTask($this->getTaskDrushSiteInstall($site, $databaseServer, $phpVariant));
        }

        return $cb;
    }
    //endregion

    //region Lint.
    public function lint(array $extensionNames): CollectionBuilder
    {
        $extensionNames = $this->validateInputExtensionNames($extensionNames);
        $managedDrupalExtensions = $this->getManagedDrupalExtensions();

        $cb = $this->collectionBuilder();
        foreach ($extensionNames as $extensionName) {
            $extension = $managedDrupalExtensions[$extensionName];
            $cb->addTask($this->getTaskPhpcsLintDrupalExtension($extension));

            if ($extension->hasSCSS) {
                $cb->addTask($this->getTaskScssLintDrupalExtension($extension));
            }

            if ($extension->hasTypeScript) {
                $cb->addTask($this->getTaskTsLintDrupalExtension($extension));
            } else {
                $cb->addTask($this->getTaskESLintDrupalExtension($extension));
            }
        }

        return $cb;
    }

    public function lintPhpcs(array $extensionNames): CollectionBuilder
    {
        $extensionNames = $this->validateInputExtensionNames($extensionNames);
        $managedDrupalExtensions = $this->getManagedDrupalExtensions();

        $cb = $this->collectionBuilder();
        foreach ($extensionNames as $extensionName) {
            $extension = $managedDrupalExtensions[$extensionName];
            $cb->addTask($this->getTaskPhpcsLintDrupalExtension($extension));
        }

        return $cb;
    }

    public function lintScss(array $extensionNames): CollectionBuilder
    {
        // @todo Configurable directory for "css".
        $extensionNames = $this->validateInputExtensionNames($extensionNames);
        $managedDrupalExtensions = $this->getManagedDrupalExtensions();

        $cb = $this->collectionBuilder();
        foreach ($extensionNames as $extensionName) {
            $extension = $managedDrupalExtensions[$extensionName];
            if ($extension->hasSCSS) {
                $cb->addTask($this->getTaskScssLintDrupalExtension($extension));
            }
        }

        return $cb;
    }

    public function lintTs(array $extensionNames): CollectionBuilder
    {
        // @todo Configurable directory for "js".
        $extensionNames = $this->validateInputExtensionNames($extensionNames);
        $managedDrupalExtensions = $this->getManagedDrupalExtensions();

        $cb = $this->collectionBuilder();
        foreach ($extensionNames as $extensionName) {
            $extension = $managedDrupalExtensions[$extensionName];
            if ($extension->hasTypeScript) {
                $cb->addTask($this->getTaskTsLintDrupalExtension($extension));
            }
        }

        return $cb;
    }

    public function lintEs(array $extensionNames): CollectionBuilder
    {
        // @todo Configurable directory for "js".
        $extensionNames = $this->validateInputExtensionNames($extensionNames);
        $managedDrupalExtensions = $this->getManagedDrupalExtensions();

        $cb = $this->collectionBuilder();
        foreach ($extensionNames as $extensionName) {
            $extension = $managedDrupalExtensions[$extensionName];
            if (!$extension->hasTypeScript) {
                $cb->addTask($this->getTaskESLintDrupalExtension($extension));
            }
        }

        return $cb;
    }
    //endregion

    /**
     * Rebuild DRUPAL_ROOT/sites/sites.php.
     */
    public function rebuildSitesPhp(): TaskInterface
    {
        return $this->getTaskDrupalRebuildSitesPhp();
    }
    //endregion

    //region Input validators.
    protected function validateInputExtensionNames(array $extensions): array
    {
        // @todo Show a an error message in case of duplicated items.
        $managedDrupalExtensions = $this->getManagedDrupalExtensions();

        $nonExistsExtensions = array_diff_key(array_flip($extensions), $managedDrupalExtensions);
        if ($nonExistsExtensions) {
            throw new \InvalidArgumentException(
                'Unknown managed Drupal extensions: ' . implode(', ', array_keys($nonExistsExtensions))
            );
        }

        if (!$extensions) {
            $extensions = array_keys($managedDrupalExtensions);
        }

        return $extensions;
    }
    //endregion

    //region Task builders.
    protected function getTaskPhpcsLintDrupalExtension(DrupalExtensionConfig $extension): TaskInterface
    {
        $options = [
            'workingDirectory' => $extension->path,
            'files' => [
                '.' => true,
            ],
        ];

        $options['files']['**/*.css'] = $extension->hasCSS;
        $options['ignore']['*.css'] = !$extension->hasCSS;

        $options['files']['**/*.js'] = $extension->hasJavaScript;
        $options['ignore']['*.js'] = !$extension->hasJavaScript;

        return $this->getTaskPhpcsLint($options);
    }

    protected function getTaskPhpcsLint(array $options = []): TaskInterface
    {
        $environment = $this->getEnvironment();

        $options += [
            'workingDirectory' => '',
            'standards' => ['Drupal', 'DrupalPractice'],
            'failOn' => 'warning',
            'lintReporters' => [
                'lintVerboseReporter' => null,
            ],
            'ignore' => [],
            'extensions' => [],
            'files' => [
              '.',
            ],
        ];

        $standardLower = strtolower(implode('-', $options['standards']));

        $options['ignore'] += [
            'node_modules/' => true,
            '.nvmrc' => true,
            '.gitignore' => true,
            '*.json' => true,
            '*.scss' => true,
            '*.png' => true,
            '*.jpeg' => true,
            '*.jpg' => true,
            '*.svg' => true,
        ];
        $options['extensions'] += [
            'php/PHP' => true,
            'inc/PHP' => true,
        ];

        if (in_array('Drupal', $options['standards'])
            || in_array('DrupalPractice', $options['standards'])
        ) {
            $options['extensions'] += [
                'engine/PHP' => true,
                'install/PHP' => true,
                'module/PHP' => true,
                'profile/PHP' => true,
                'theme/PHP' => true,
                'js/JS' => true,
                'css/CSS' => true,
            ];
        }

        if (!empty($options['workingDirectory'])) {
            $options['phpcsExecutable'] = Path::makeAbsolute("{$this->binDir}/phpcs", getcwd());
        }

        if ($environment === 'jenkins') {
            $options['failOn'] = 'never';

            $options['lintReporters']['lintCheckstyleReporter'] = (new CheckstyleReporter())
                ->setDestination("reports/checkstyle/phpcs.{$standardLower}.xml");
        }

        if ($environment !== 'git-hook') {
            return $this->taskPhpcsLintFiles($options);
        }

        $files = $options['files'];
        unset($options['files']);

        $options['ignore'] += [
            '*.ts' => true,
            '*.rb' => true,
        ];

        $assetJar = new AssetJar();

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'git.readStagedFiles' => $this
                    ->taskGitReadStagedFiles()
                    ->setWorkingDirectory($options['workingDirectory'])
                    ->setCommandOnly(true)
                    ->setAssetJar($assetJar)
                    ->setAssetJarMap('files', ['files'])
                    ->setPaths($files),
                "lint.phpcs.{$standardLower}" => $this
                    ->taskPhpcsLintInput($options)
                    ->setAssetJar($assetJar)
                    ->setAssetJarMap('files', ['files']),
            ]);
    }

    protected function getTaskScssLintDrupalExtension(DrupalExtensionConfig $extension): TaskInterface
    {
        $task = $this
            ->taskScssLintRunFiles()
            ->setFailOn('warning')
            ->setWorkingDirectory($extension->path)
            ->addLintReporter('lintVerboseReporter')
            ->setExclude('*.css')
            ->setPaths([
                'css/',
            ]);

        $gemFile = $this->getFallbackFileName('Gemfile', $extension->path);
        if ($gemFile) {
            $task->setBundleGemFile($gemFile);
        }

        return $task;
    }

    protected function getTaskTsLintDrupalExtension(DrupalExtensionConfig $extension): TaskInterface
    {
        $task = $this
            ->taskTsLintRun()
            ->setWorkingDirectory($extension->path)
            ->setFailOn('warning')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->setPaths([
                'js/**/*.ts',
            ]);

        $configFile = $this->getFallbackFileName('tslint.json', $extension->path);
        if ($configFile) {
            $task->setConfigFile($configFile);
        }

        return $task;
    }

    protected function getTaskESLintDrupalExtension(DrupalExtensionConfig $extension): TaskInterface
    {
        $eslintExecutable = $this->getFallbackFileName('node_modules/.bin/eslint', $extension->path);
        $configFile = $this->getFallbackFileName('.eslintrc', $extension->path);
        $task = $this
            ->taskESLintRunFiles()
            ->setWorkingDirectory(Path::makeRelative($extension->path, getcwd()))
            ->setEslintExecutable(Path::makeRelative($eslintExecutable, $extension->path))
            ->setFailOn('warning')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->setFiles([
                'js/**/*.js',
            ]);

        if ($configFile) {
            $task->setConfigFile(Path::makeRelative($configFile, $extension->path));
        }

        return $task;
    }

    protected function getTaskDrupalRebuildSitesPhp(): CollectionBuilder
    {
        return $this->taskDrupalRebuildSitesPhp([
            'projectConfig' => $this->projectConfig,
        ]);
    }

    protected function getTaskDrupalSiteCreate(array $options): CollectionBuilder
    {
        $options['projectConfig'] = $this->projectConfig;

        return $this->taskDrupalSiteCreate($options);
    }

    protected function getTaskDrupalSiteDelete(SiteConfig $siteConfig): \Closure
    {
        // @todo Create a native Task.
        // @todo Separate Tasks, dir delete, ProjectConfig manipulation.
        // @todo Delete other resources: databases.
        // @todo Delete other resources: Solr, Elastic.
        // @todo Delete other resources: Nginx, Apache.
        return function () use ($siteConfig) {
            $pc = $this->projectConfig;

            foreach (array_unique($siteConfig->urls) as $siteDir) {
                $filesToDelete = [];
                $dirsToDelete = [
                    "{$pc->outerSitesSubDir}/$siteDir",
                ];

                if ($siteDir === 'default') {
                    /** @var \Symfony\Component\Finder\Finder $files */
                    $files = Finder::create()
                        ->in("{$pc->drupalRootDir}/sites/$siteDir")
                        ->depth('== 0')
                        ->notName('default.services.yml')
                        ->notName('default.settings.php');
                    foreach ($files as $file) {
                        if ($file->isDir()) {
                            $dirsToDelete[] = $file->getPathname();
                        } else {
                            $filesToDelete[] = $file->getPathname();
                        }
                    }
                } else {
                    $dirsToDelete[] = "{$pc->drupalRootDir}/sites/$siteDir";
                }

                $this->_deleteDir(array_filter($dirsToDelete, 'is_dir'));
                $this->_remove($filesToDelete);
            }

            $projectConfigFileName = Utils::$projectConfigFileName;
            if ($this->fs->exists($projectConfigFileName)) {
                $lines = file($projectConfigFileName);
                $lineIndex = 0;

                $siteIdSafe = var_export($siteConfig, true);
                $first = "  \$projectConfig->sites[$siteIdSafe] = new SiteConfig();\n";
                while ($lineIndex < count($lines) && $lines[$lineIndex] !== $first) {
                    $lineIndex++;
                }

                if ($lineIndex < count($lines) && $lines[$lineIndex] === $first) {
                    if (isset($lines[$lineIndex - 1]) && $lines[$lineIndex - 1] === "\n") {
                        // Previous empty line.
                        unset($lines[$lineIndex - 1]);
                    }

                    do {
                        unset($lines[$lineIndex++]);
                    } while (isset($lines[$lineIndex]) && $lines[$lineIndex] !== "\n");
                }

                // @todo Error handling.
                $this->fs->dumpFile($projectConfigFileName, implode('', $lines));
            }
            unset($pc->sites[$siteConfig->id]);

            return 0;
        };
    }

    protected function getTaskGitHookInstall(DrupalExtensionConfig $extension): \Closure
    {
        return function () use ($extension) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->getContainer()->get('logger');
            $logger->notice(
                'Install Git hooks for "<info>{extension}</info>"',
                [
                    'extension' => $extension->packageName,
                ]
            );

            $mask = umask();
            $hostDir = getcwd();
            $srcDirUpstream = $this->getPackagePath('cheppers/git-hooks') . '/git-hooks';
            $srcDirCustom = "{$this->roboDrupalRoot}/src/GitHooks";
            // @todo Support .git pointers.
            $dstDir = "{$extension->path}/.git/hooks";

            $this->fs->mirror($srcDirUpstream, $dstDir, null, ['override' => true]);
            $this->fs->copy("$srcDirCustom/_common", "$dstDir/_common", true);

            $file = new \DirectoryIterator($srcDirUpstream);
            while ($file->valid()) {
                if ($file->isFile() && is_executable($file->getPathname())) {
                    $this->fs->chmod("$dstDir/" . $file->getBasename(), 0777, $mask);
                }

                $file->next();
            }

            $configFileName = '_config';
            $configContentPattern = implode("\n", [
                '#!/usr/bin/env bash',
                '',
                'roboDrupalTask="githook:${roboDrupalHookName}"',
                'roboDrupalHostDir=%s',
                'roboDrupalExtensionName=%s',
                '',
            ]);
            $configContentArgs = [
                escapeshellarg($hostDir),
                escapeshellarg($extension->packageName)
            ];
            $configContent = vsprintf($configContentPattern, $configContentArgs);
            // @todo Error handling.
            $this->fs->dumpFile("$dstDir/$configFileName", $configContent);

            return 0;
        };
    }
    //endregion

    /**
     * @var null|array
     */
    protected $packagePaths = null;

    /**
     * @return string[]
     */
    protected function getPackagePaths(): array
    {
        if ($this->packagePaths === null) {
            $ppResult = $this
                ->taskComposerPackagePaths([
                    'composerExecutable' => $this->projectConfig->composerExecutable,
                ])
                ->run()
                ->stopOnFail();

            $this->packagePaths = $ppResult['packagePaths'];
        }

        return $this->packagePaths;
    }

    protected function getPackagePath(string $packageId): string
    {
        $pp = $this->getPackagePaths();

        return $pp[$packageId] ?? '';
    }

    /**
     * @return \Cheppers\Robo\Drupal\Config\DrupalExtensionConfig[]
     */
    protected function getManagedDrupalExtensions(): array
    {
        $this->initManagedDrupalExtensions();

        return Utils::filterDisabled($this->projectConfig->managedDrupalExtensions);
    }

    /**
     * @return $this
     */
    protected function initManagedDrupalExtensions()
    {
        if (!$this->projectConfig->autodetectManagedDrupalExtensions
            || $this->areManagedDrupalExtensionsInitialized
        ) {
            return $this;
        }

        $namesAndPaths = $this->collectManagedDrupalExtensions();
        foreach ($namesAndPaths as $packageName => $path) {
            list($vendor, $name) = explode('/', $packageName);
            if (!isset($this->projectConfig->managedDrupalExtensions[$name])) {
                $this->projectConfig->managedDrupalExtensions[$name] = new DrupalExtensionConfig();
            }

            $ec = $this->projectConfig->managedDrupalExtensions[$name];
            $ec->packageVendor = $vendor;
            $ec->packageName = $name;
            $ec->path = $path;
            $ec->hasGit = $this->fs->exists("$path/.git");
            $ec->hasJavaScript = $this->hasDrupalExtensionJavaScript($path);
            $ec->hasTypeScript = $this->hasDrupalExtensionTypeScript($path);
            $ec->hasCSS = $this->hasDrupalExtensionCss($path);
            $ec->hasSCSS = $this->hasDrupalExtensionScss($path);
        }

        $this->areManagedDrupalExtensionsInitialized = true;

        return  $this;
    }

    /**
     * Collect those Drupal extensions which are managed by this RoboFile.
     *
     * Composer uses symlinks on *nix systems to install local packages,
     * Usually those packages are outside the project root and the
     * `composer show -P` command resolves their real absolute path.
     *
     * @todo Cache.
     *
     * @return string[]
     */
    protected function collectManagedDrupalExtensions(): array
    {
        $this->initComposerLock();
        $managedExtensions = [];
        $packagePaths = $this->getPackagePaths();

        $currentDir = getcwd();
        foreach ($packagePaths as $packageName => $packagePath) {
            foreach (['packages', 'packages-dev'] as $lockKey) {
                // @todo Do we need the packages without ".git" dir?
                if (isset($this->composerLock[$lockKey][$packageName])
                    && Utils::isDrupalPackage($this->composerLock[$lockKey][$packageName])
                    && strpos($packagePath, $currentDir) !== 0
                ) {
                    $managedExtensions[$packageName] = $packagePath;
                }
            }
        }

        return $managedExtensions;
    }

    protected function hasDrupalExtensionTypeScript(string $path): bool
    {
        $result = $this
            ->taskGitListFiles()
            ->setWorkingDirectory($path)
            ->setPaths(['*.ts'])
            ->run();

        return !empty($result['files']);
    }

    protected function hasDrupalExtensionJavaScript(string $path): bool
    {
        $result = $this
            ->taskGitListFiles()
            ->setWorkingDirectory($path)
            ->setPaths(['*.js'])
            ->run();

        return !empty($result['files']);
    }

    protected function hasDrupalExtensionCss(string $path): bool
    {
        $result = $this
            ->taskGitListFiles()
            ->setWorkingDirectory($path)
            ->setPaths(['*.css'])
            ->run();

        return !empty($result['files']);
    }

    protected function hasDrupalExtensionScss(string $path): bool
    {
        $result = $this
            ->taskGitListFiles()
            ->setWorkingDirectory($path)
            ->setPaths(['*.scss', '*.sass'])
            ->run();

        return !empty($result['files']);
    }
}
