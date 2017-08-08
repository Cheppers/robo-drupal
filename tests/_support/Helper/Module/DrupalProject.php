<?php

namespace Sweetchuck\Robo\Drupal\Test\Helper\Module;

use Sweetchuck\Robo\Drupal\Test\Helper\Utils\TmpDirManager;
use Sweetchuck\Robo\Drupal\Utils;
use Codeception\Lib\ModuleContainer;
use Codeception\Module as CodeceptionModule;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

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
        if (!isset(static::$projectCache[$templateName])) {
            static::$projectCache[$templateName] = TmpDirManager::create();

            codecept_debug(sprintf(
                'Create a new "%s" Drupal project from scratch. Cache dir: "%s"',
                $templateName,
                static::$projectCache[$templateName]
            ));

            $srcDir = $this->getProjectTemplateDir($templateName);
            $this->fs->mirror($srcDir, static::$projectCache[$templateName]);

            $this
                ->gitInit(Path::join(static::$projectCache[$templateName], 'root'))
                ->addRoboDrupalToProject(static::$projectCache[$templateName])
                ->initExtensions(static::$projectCache[$templateName]);
        }

        codecept_debug(sprintf(
            'Create a new "%s" Drupal project from cache "%s" into "%s"',
            $templateName,
            static::$projectCache[$templateName],
            $dstDir
        ));

        $this->fs->mirror(static::$projectCache[$templateName], $dstDir);
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

    public function seeManagedExtensionsTable(array $extensions, string $text)
    {
        $rows = [];
        foreach ($extensions as $name => $path) {
            $rows[] = ['drupal', $name, $path];
        }

        $output = new BufferedOutput();
        (new Table($output))
            ->setHeaders(['Vendor', 'Name', 'Path'])
            ->addRows($rows)
            ->render();

        $this->assertContains($output->fetch(), $text);
    }

    public function seeManagedExtensionsYaml(array $extensions, string $text)
    {
        $expected = [];
        foreach ($extensions as $name => $path) {
            $expected[] = "$name:";
            $expected[] = '    vendor: drupal';
            $expected[] = "    name: $name";
            $expected[] = "    path: $path";
        }

        $this->assertContains(implode(PHP_EOL, $expected), $text);
    }

    /**
     * @return $this
     */
    protected function addRoboDrupalToProject(string $dstDir)
    {
        $cmdPattern = 'cd %s && composer config %s %s %s';
        $cmdArgs = [
            escapeshellarg("$dstDir/root"),
            'repositories.local:sweetchuck/robo-drupal',
            'path',
            escapeshellarg(getcwd()),
        ];
        $this->execute(vsprintf($cmdPattern, $cmdArgs));

        $this->composerRequire($dstDir, ['sweetchuck/robo-drupal:*']);

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
            codecept_debug($result['stdOutput']);
            codecept_debug($result['stdError']);

            $this->assertEquals($expectedExitCode, $result['exitCode'], '::execute() exit code');
        }

        return $result;
    }
}
