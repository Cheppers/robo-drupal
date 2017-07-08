<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\ProjectType\Incubator;

use Cheppers\Robo\Drupal\Test\AcceptanceTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

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

        //register_shutdown_function(function () {
        //    $tmpDirs = array_filter($this->tmpDirs, 'file_exists');
        //    $this->fs->chmod($tmpDirs, 0700, 0, true);
        //    $this->fs->remove($tmpDirs);
        //});
    }

    public function listTest(AcceptanceTester $i): void
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

    public function siteCreateBasicTest(AcceptanceTester $i): void
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

    public function siteCreateAdvancedTest(AcceptanceTester $i): void
    {
        $projectName = 'siteCreate.02';
        $expectedDir = $this->fixturesRoot($projectName) . '/expected';
        $workingDir = $this->prepareProject($projectName);
        $id = __METHOD__;
        $description = implode(' ', [
            'create a new site where the "drupalRootDir" and the "outerSitesSubDir" configurations are different than',
            'the default ones.',
        ]);
        $i->wantTo($description);
        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'site:create',
            'commerce',
            '--profile=minimal',
            '--long=shop',
            '--short=my'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $dirs = [
            'project/specific/all/translations',
            'web/public_html/sites/commerce.my/files',
            'project/specific/commerce.my/config/sync',
            'project/specific/commerce.my/private',
            'project/specific/commerce.my/temporary',
            'web/public_html/sites/commerce.sl/files',
            'project/specific/commerce.sl/config/sync',
            'project/specific/commerce.sl/db',
            'project/specific/commerce.sl/private',
            'project/specific/commerce.sl/temporary',
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
            "$workingDir/project/specific/commerce.my/hash_salt.txt",
            "$workingDir/project/specific/commerce.sl/hash_salt.txt",
        ];
        foreach ($files as $file) {
            $i->assertGreaterThan(0, filesize($file), "File has any content: '$file'");
        }
    }

    public function siteDeleteBasicTest(AcceptanceTester $i): void
    {
        $projectName = 'siteDelete.01';
        $workingDir = $this->prepareProject($projectName);
        $expectedDir = $this->fixturesRoot($projectName) . '/expected';
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'site:delete',
            '--yes',
            'default'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $dirs = [
            'sites/all/translations' => true,
            'sites/default.my' => false,
            'sites/default.sl' => false,
        ];
        foreach ($dirs as $dir => $shouldBeExists) {
            if ($shouldBeExists) {
                $i->assertDirectoryExists("$workingDir/$dir");
            } else {
                $i->assertFileNotExists("$workingDir/$dir");
            }
        }

        /** @var \Symfony\Component\Finder\Finder $files */
        $files = (new Finder())
            ->in($expectedDir)
            ->files();
        foreach ($files as $file) {
            $filePath = Path::join($workingDir, $file->getRelativePathname());
            $i->openFile($filePath);
            $i->canSeeFileContentsEqual($file->getContents());
        }
    }

    protected function prepareProject(string $projectName): string
    {
        $tmpDir = $this->createTmpDir();
        $templateDir = $this->fixturesRoot($projectName) . '/base';
        $this->fs->mirror($templateDir, $tmpDir);

        return $tmpDir;
    }

    protected function fixturesRoot(string $projectName): string
    {
        return codecept_data_dir("fixtures/ProjectType/Incubator/$projectName");
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
