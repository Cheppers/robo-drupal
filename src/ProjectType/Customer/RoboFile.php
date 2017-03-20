<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\AssetJar\AssetJar;
use Cheppers\LintReport\Reporter\CheckstyleReporter;
use Cheppers\Robo\Bundler\BundlerTaskLoader;
use Cheppers\Robo\Compass\CompassTaskLoader;
use Cheppers\Robo\Drupal\Config\PhpcsConfig;
use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use Cheppers\Robo\Drupal\Robo\GeneralReleaseTaskLoader;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Phpcs\PhpcsTaskLoader;
use Cheppers\Robo\Yarn\YarnTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;

class RoboFile extends Base\RoboFile
{
    use BundlerTaskLoader;
    use GeneralReleaseTaskLoader;
    use CompassTaskLoader;
    use PhpcsTaskLoader;
    use YarnTaskLoader;

    /**
     * {@inheritdoc}
     */
    protected $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Customer\ProjectConfig
     */
    protected $projectConfig = null;

    protected function getPhpcsConfigDrupal(): PhpcsConfig
    {
        // @todo Configurable class.
        $phpcsConfig = new PhpcsConfig();

        $phpcsConfig->standard = 'Drupal';
        $phpcsConfig->lintReporters = [
            'lintVerboseReporter' => null,
        ];

        $phpcsConfig->filesGitStaged += Utils::phpFileExtensionPatterns('*.', '');
        $phpcsConfig->files += [
            'RoboFile.php' => true,
            Utils::$projectConfigFileName => file_exists(Utils::$projectConfigFileName),
        ];

        $customDrupalProfiles = Utils::getCustomDrupalProfiles($this->projectConfig->drupalRootDir);
        foreach ($customDrupalProfiles as $profileName => $profileDir) {
            $suggestions = [
                "$profileDir/modules/custom/",
                "$profileDir/themes/custom/",
                "$profileDir/src/",
                "$profileDir/$profileName.install",
                "$profileDir/$profileName.profile",
                "$profileDir/$profileName.drush.inc",
                "{$this->projectConfig->drupalRootDir}/drush/",
            ];
            foreach ($suggestions as $suggestion) {
                $phpcsConfig->files[$suggestion] = file_exists($suggestion);
            }
        }

        return $phpcsConfig;
    }

    //region Git hooks.
    public function githookPreCommit(): CollectionBuilder
    {
        $this->environment = 'git-hook';

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'lint.phpcs.Drupal' => $this->getTaskPhpcsLint($this->getPhpcsConfigDrupal()),
                'lint.composer.lock' => $this->taskComposerValidate(),
            ]);
    }
    //endregion

    //region Lint.
    /**
     * Run all kind of linters and static analyzers.
     */
    public function lint(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addTaskList([
                'lint.phpcs.Drupal' => $this->getTaskPhpcsLint($this->getPhpcsConfigDrupal()),
                'lint.composer.lock' => $this->taskComposerValidate(),
            ]);
    }

    public function lintPhpcs(): TaskInterface
    {
        return $this->getTaskPhpcsLint($this->getPhpcsConfigDrupal());
    }

    public function lintComposerValidate(): TaskInterface
    {
        return $this->taskComposerValidate();
    }
    //endregion

    public function build(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskYarnInstall())
            ->addCode($this->getTaskBundleCheckOrInstall())
            ->addCode($this->getTaskCompassClean())
            ->addCode($this->getTaskCompassCompile());
    }

    public function bundleCheckOrInstall(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskBundleCheckOrInstall());
    }

    public function compassCompile(
        array $options = [
            'environment' => ''
        ]
    ): CollectionBuilder {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskCompassCompile($options));
    }

    public function compassClean(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskCompassClean());
    }

    public function yarnInstall(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskYarnInstall());
    }

    public function siteInstall(string $siteId = ''): TaskInterface
    {
        // @todo Check that there is a PhpVariant and DatabaseServer.
        $site = $this->validateInputSiteId($siteId);
        $phpVariant = reset($this->projectConfig->phpVariants);
        $databaseServer = reset($this->projectConfig->databaseServers);

        $placeholders = [
            '{siteBranch}' => $site->id,
            '{php}' => $phpVariant->id,
            '{db}' => $databaseServer->id,
        ];
        $siteDir = $this->projectConfig->getSiteVariantDir($placeholders);

        return $this
            ->collectionBuilder()
            ->addCode($this->getTaskUnlockSettingsPhp($site->id))
            ->addCode($this->getTaskPublicFilesClean($siteDir))
            ->addCode($this->getTaskPrivateFilesClean($siteDir))
            ->addTask($this->getTaskDrushSiteInstall($site, $databaseServer, $phpVariant));
    }

    public function release()
    {
        $task = $this
            ->taskGeneralRelease()
            ->setReleaseDir("{$this->projectConfig->releaseDir}/general")
            ->setProjectConfig($this->projectConfig)
            ->setGitLocalBranch('production');

        return $task;
    }

    //region Task builders.
    /**
     * @return \Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskPhpcsLint(PhpcsConfig $phpcsConfig): TaskInterface
    {
        $env = $this->getEnvironment();
        $reportDir = $this->projectConfig->reportDir;

        if ($env === 'ci') {
            $checkstyleLintReporter = new CheckstyleReporter();
            $checkstyleLintReporter->setDestination("$reportDir/checkstyle/phpcs.{$phpcsConfig->standard}.xml");
            $phpcsConfig->lintReporters['lintCheckstyleReporter'] = $checkstyleLintReporter;
        }

        if ($env !== 'git-hook') {
            return $this->taskPhpcsLintFiles((array) $phpcsConfig);
        }

        $options = (array) $phpcsConfig;
        unset($options['files']);
        $assetJar = new AssetJar();

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'git.staged' => $this
                    ->taskGitReadStagedFiles()
                    ->setCommandOnly(true)
                    ->setAssetJar($assetJar)
                    ->setAssetJarMap('files', ['files'])
                    ->setPaths($phpcsConfig->filesGitStaged),
                "phpcs.{$phpcsConfig->standard}" => $this
                    ->taskPhpcsLintInput($options)
                    ->setAssetJar($assetJar)
                    ->setAssetJarMap('files', ['files'])
                    ->setAssetJarMap('report', ['report']),
            ]);
    }

    protected function getTaskBundleCheckOrInstall(): \Closure
    {
        return function () {
            foreach ($this->getGemFiles() as $gemFile) {
                $checkResult = $this
                    ->taskBundleCheck()
                    ->setOutput($this->output())
                    ->setGemFile($gemFile)
                    ->run();
                if (!$checkResult->wasSuccessful()) {
                    $this
                        ->taskBundleInstall()
                        ->setGemFile($gemFile)
                        ->run()
                        ->stopOnFail();
                }
            }

            return 0;
        };
    }

    protected function getTaskCompassCompile(array $options = []): \Closure
    {
        return function () use ($options) {
            foreach ($this->getCompassConfigFiles() as $configFile) {
                $wd = pathinfo($configFile, PATHINFO_DIRNAME);
                $wd = $wd === '.' ? '' : $wd;

                $fileName = pathinfo($configFile, PATHINFO_BASENAME);
                $fileName = $fileName === 'config.rb' ? '' : $fileName;

                $this
                    ->taskCompassCompile($options)
                    ->setOutput($this->output())
                    ->setWorkingDirectory($wd)
                    ->setConfigFile($fileName)
                    ->run()
                    ->stopOnFail();
            }

            return 0;
        };
    }

    protected function getTaskCompassClean(array $options = []): \Closure
    {
        return function () use ($options) {
            foreach ($this->getCompassConfigFiles() as $configFile) {
                $wd = pathinfo($configFile, PATHINFO_DIRNAME);
                $wd = $wd === '.' ? '' : $wd;

                $fileName = pathinfo($configFile, PATHINFO_BASENAME);
                $fileName = $fileName === 'config.rb' ? '' : $fileName;

                $this
                    ->taskCompassCompile($options)
                    ->setOutput($this->output())
                    ->setWorkingDirectory($wd)
                    ->setConfigFile($fileName)
                    ->setEnvironment('development')
                    ->run()
                    ->stopOnFail();
            }

            return 0;
        };
    }

    protected function getTaskYarnInstall(array $options = []): \Closure
    {
        return function () use ($options) {
            foreach ($this->getPackageJsonFiles() as $packageJsonFile) {
                $wd = pathinfo($packageJsonFile, PATHINFO_DIRNAME);
                $wd = $wd === '.' ? '' : $wd;

                $this
                    ->taskYarnInstall($options)
                    ->setOutput($this->output())
                    ->setWorkingDirectory($wd)
                    ->run()
                    ->stopOnFail();
            }

            return 0;
        };
    }
    //endregion

    /**
     * @return string[]
     */
    protected function getGemFiles(): array
    {
        $files = $this->getGitTrackedFiles(['Gemfile*', '*/Gemfile*']);

        $gemFiles = [];
        foreach ($files as $file) {
            if (!fnmatch('*.lock', $file)) {
                $gemFiles[] = $file;
            }
        }

        return $gemFiles;
    }

    /**
     * @return string[]
     */
    protected function getCompassConfigFiles(): array
    {
        return $this->getGitTrackedFiles(['config.rb', '*/config.rb']);
    }

    /**
     * @return string[]
     */
    protected function getPackageJsonFiles(): array
    {
        return $this->getGitTrackedFiles(['package.json', '*/package.json']);
    }

    /**
     * @return string[]
     */
    protected function getComposerJsonFiles(): array
    {
        return $this->getGitTrackedFiles(['*/composer.json']);
    }
}
