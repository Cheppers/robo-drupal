<?php

namespace Cheppers\Robo\Drupal\Test\Helper\Module;

use Cheppers\Robo\Drupal\Test\Helper\Utils\TmpDirManager;
use Cheppers\Robo\Drupal\Utils;
use Codeception\Lib\ModuleContainer;
use Codeception\Module as CodeceptionModule;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DrupalProject extends CodeceptionModule
{
    /**
     * @var array
     */
    protected static $projectCache = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->fs = new Filesystem();
        parent::__construct($moduleContainer, $config);
    }

    public function createNewDrupalProject(string $dstDir, string $templateName)
    {
        if (isset(static::$projectCache[$templateName])) {
            $this->fs->mirror(static::$projectCache[$templateName], $dstDir);

            return;
        }

        $srcDir = $this->getProjectTemplateDir($templateName);
        $this->fs->mirror($srcDir, $dstDir);

        $this
            ->gitInit("$dstDir/root")
            ->addRoboDrupalToProject($dstDir)
            ->initExtensions($dstDir);

        static::$projectCache[$templateName] = TmpDirManager::create();
        $this->fs->mirror($dstDir, static::$projectCache[$templateName]);
    }

    public function seeDrupalSiteIsInstalled(string $projectRootDir, string $siteDubDir)
    {
        $this->assertFileExists("$projectRootDir/drupal_root/sites/$siteDubDir/files/.htaccess");
        $this->assertFileExists("$projectRootDir/sites/$siteDubDir/db/default__default.sqlite");

        $dbh = new \PDO("sqlite:$projectRootDir/sites/$siteDubDir/db/default__default.sqlite");
        $accounts = $dbh
            ->query('SELECT * FROM users')
            ->fetchAll();

        $this->assertEquals(2, count($accounts), 'Number of users');
    }

    /**
     * @return $this
     */
    protected function addRoboDrupalToProject(string $dstDir)
    {
        $cmdPattern = 'cd %s && composer config %s %s %s';
        $cmdArgs = [
            escapeshellarg("$dstDir/root"),
            'repositories.local:cheppers/robo-drupal',
            'path',
            escapeshellarg(getcwd()),
        ];
        $this->execute(vsprintf($cmdPattern, $cmdArgs));

        $this->composerRequire($dstDir, ['cheppers/robo-drupal:*']);

        return $this;
    }

    /**
     * @return $this
     */
    protected function initExtensions(string $dstDir)
    {
        $extensions = Utils::directDirectoryDescendants("$dstDir/extensions");
        $packages = [];
        foreach ($extensions as $extension) {
            $extensionName = $extension->getBasename();
            $packages[] = "drupal/$extensionName:*";
            $this->gitInit($extension->getPathname());
        }

        $this->composerRequire($dstDir, $packages);

        return $this;
    }

    /**
     * @return $this
     */
    protected function gitInit(string $dir)
    {
        $cmdPattern = 'cd %s && git init && git add . && git commit -m %s';
        $cmdArgs = [
            escapeshellarg($dir),
            escapeshellarg('Initial commit'),
        ];
        $this->execute(vsprintf($cmdPattern, $cmdArgs));

        return $this;
    }

    /**
     * @return $this
     */
    protected function composerRequire(
        string $dstDir,
        array $packages,
        bool $dev = false
    ) {
        $cmdPattern = 'cd %s && composer require';
        $cmdArgs = [
            escapeshellarg("$dstDir/root"),
            //$package . ($version !== '' ? ":$version" : ''),
        ];

        if ($dev) {
            $cmdPattern .= ' --dev';
        }

        $cmdPattern .= str_repeat(' %s', count($packages));
        foreach ($packages as $package) {
            $cmdArgs[] = escapeshellarg($package);
        }

        $this->execute(vsprintf($cmdPattern, $cmdArgs));

        return $this;
    }

    protected function getProjectTemplateDir(string $templateName): string
    {
        return codecept_data_dir("fixtures/Project/$templateName");
    }

    protected function execute(string $cmd, ?int $expectedExitCode = 0): array
    {
        $process = new Process($cmd, null, null, null, 240);

        codecept_debug("\$cmd = $cmd");
        $result = [
            'exitCode' => $process->run(function ($type, $data) {
                codecept_debug($data);
            }),
            'stdOutput' => $process->getOutput(),
            'stdError' => $process->getErrorOutput(),
        ];

        if ($expectedExitCode !== null && $result['exitCode'] !== $expectedExitCode) {
            throw new \Exception('@todo');
        }

        return $result;
    }
}
