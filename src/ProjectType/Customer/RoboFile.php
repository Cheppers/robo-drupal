<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\AssetJar\AssetJar;
use Cheppers\LintReport\Reporter\CheckstyleReporter;
use Cheppers\Robo\Drupal\Config\PhpcsConfig;
use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Phpcs\PhpcsTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;

class RoboFile extends Base\RoboFile
{
    use PhpcsTaskLoader;

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

        $phpcsConfig->filesGitStaged += [
            '*.php' => true,
            '*.inc' => true,
            '*.profile' => true,
            '*.module' => true,
            '*.install' => true,
            '*.theme' => true,
            '*.engine' => true,
        ];

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
                // @todo Add other files.
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
        $phpcsConfig = $this->getPhpcsConfigDrupal();

        return $this
            ->collectionBuilder()
            ->addTaskList([
                'lint.phpcs.drupal' => $this->getTaskPhpcsLint($phpcsConfig),
            ]);
    }
    //endregion

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

    /**
     * @return \Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskPhpcsLint(PhpcsConfig $phpcsConfig): TaskInterface
    {
        $env = $this->getEnvironment();
        $reports_dir = $this->projectConfig->reportsDir;

        if ($env === 'ci') {
            $checkstyleLintReporter = new CheckstyleReporter();
            $checkstyleLintReporter->setDestination("$reports_dir/checkstyle/phpcs.{$phpcsConfig->standard}.xml");
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
}
