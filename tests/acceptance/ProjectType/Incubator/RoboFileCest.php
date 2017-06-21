<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\ProjectType\Incubator;

use Cheppers\Robo\Drupal\Test\AcceptanceTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class RoboFileCest
{

    /**
     * @var string
     */
    protected $class = \ProjectTypeIncubatorRoboFile::class;

    /**
     * @var string[]
     */
    protected $tmpDirs = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();

        register_shutdown_function(function () {
            //$tmpDirs = array_filter($this->tmpDirs, 'file_exists');
            //$this->fs->chmod($tmpDirs, 0700, 0, true);
            //$this->fs->remove($tmpDirs);
        });
    }

    public function listTest(AcceptanceTester $i)
    {
        $projectName = 'siteCreate.01';
        $workingDirectory = $this->prepareProject($projectName);
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $workingDirectory,
            $this->class,
            'list',
            '--format=json'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id));

        $tasks = json_decode($i->getRoboTaskStdOutput($id), true);

        foreach (array_keys($tasks['namespaces']) as $key) {
            $item = $tasks['namespaces'][$key];
            unset($tasks['namespaces'][$key]);
            $tasks['namespaces'][$item['id']] = $item;
        }

        $i->assertEquals(
            [
                'githooks:install',
                'githooks:uninstall',
            ],
            $tasks['namespaces']['githooks']['commands']
        );
    }

    public function siteCreateTest(AcceptanceTester $i)
    {
        $projectName = 'siteCreate.01';
        $expectedDir = $this->fixturesRoot($projectName) . '/expected';
        $workingDir = $this->prepareProject($projectName);
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'site:create'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $dirs = [
            'sites/all/translations',
            'drupal_root/sites/default.my/files',
            'sites/default.my/config/sync',
            'sites/default.my/private',
            'sites/default.my/temporary',
            'drupal_root/sites/default.sl/files',
            'sites/default.sl/config/sync',
            'sites/default.sl/db',
            'sites/default.sl/private',
            'sites/default.sl/temporary',
        ];
        foreach ($dirs as $dir) {
            $i->assertDirectoryExists("$workingDir/$dir");
        }

        /** @var \Symfony\Component\Finder\Finder $files */
        $files = (new Finder())
            ->in($expectedDir)
            ->files();
        foreach ($files as $file) {
            $filePath = "$workingDir/" . $file->getRelativePathname();
            $i->openFile($filePath);
            $i->canSeeFileContentsEqual($file->getContents());
        }

        $files = [
            "$workingDir/sites/default.my/hash_salt.txt",
            "$workingDir/sites/default.sl/hash_salt.txt",
        ];
        foreach ($files as $file) {
            $i->assertGreaterThan(0, filesize($file));
        }
    }

    protected function fixturesRoot(string $projectName): string
    {
        return codecept_data_dir("fixtures/ProjectType/Incubator/$projectName");
    }

    protected function prepareProject(string $projectName): string
    {
        $tmpDir = $this->createTmpDir();
        $templateDir = $this->fixturesRoot($projectName) . '/base';
        $this->fs->mirror($templateDir, $tmpDir);

        return $tmpDir;
    }

    protected function createTmpDir(): string
    {
        $class = explode('\\', __CLASS__);
        $tmpDir = tempnam(sys_get_temp_dir(), 'robo-drupal-' . end($class) . '-');
        if (!$tmpDir) {
            throw new \Exception();
        }

        unlink($tmpDir);
        mkdir($tmpDir);
        $this->tmpDirs[] = $tmpDir;

        return $tmpDir;
    }
}
