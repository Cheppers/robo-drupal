<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task;

use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Robo\Task\SiteCreateTask;
use Cheppers\Robo\Drupal\Utils;
use Codeception\Test\Unit;
use Robo\Robo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @covers \Cheppers\Robo\Drupal\Robo\Task\SiteCreateTask
 */
class SiteCreateTaskTest extends Unit
{
    /**
     * @var \Cheppers\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    public function testRun()
    {
        $fixturesDir = $this->getFixturesDir();
        $workspaceName = 'p1';
        $workspaceDir = "$fixturesDir/$workspaceName";

        $fs = new Filesystem();
        $fs->remove("$workspaceDir/actual");
        $fs->mirror("$workspaceDir/base", "$workspaceDir/actual");

        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $siteBranch = 'okay';

        $options = [
            'projectConfig' => $this->getProjectConfig("$workspaceDir/actual"),
            'siteBranch' => $siteBranch,
            'projectRootDir' => "$workspaceDir/actual",
            'installProfile' => 'development',
            'machineNameLong' => 'my_project_01',
            'machineNameShort' => 'my_p1',
        ];

        $task = new SiteCreateTask($options);

        $result = $task->run();

        $this->tester->assertEquals('', $result->getMessage());
        $this->tester->assertEquals(0, $result->getExitCode());

        $expectedFiles = (new Finder())
            ->in("$workspaceDir/expected")
            ->files();
        /** @var \Symfony\Component\Finder\SplFileInfo $expectedFile */
        foreach ($expectedFiles as $expectedFile) {
            $filePath = $expectedFile->getRelativePathname();
            $actualFilePath = "$workspaceDir/actual/$filePath";
            $this->tester->assertTrue(is_file($actualFilePath), "File exists: '$filePath'");
            $this->tester->assertEquals(
                $expectedFile->getContents(),
                file_get_contents($actualFilePath),
                "File contents are equal: '$filePath'"
            );
        }

        $expectedDirectories = [
            'sites/all/translations',
            "drupal_root/sites/$siteBranch.my/files",
            "sites/$siteBranch.my/config/sync",
            "sites/$siteBranch.my/private",
            "sites/$siteBranch.my/temporary",
            "drupal_root/sites/$siteBranch.sl/files",
            "sites/$siteBranch.sl/config/sync",
            "sites/$siteBranch.sl/private",
            "sites/$siteBranch.sl/temporary",
            "sites/$siteBranch.sl/db",
        ];
        foreach ($expectedDirectories as $expectedDirectory) {
            $this->tester->assertTrue(
                is_dir("$workspaceDir/actual/$expectedDirectory"),
                "Directory exists: '$expectedDirectory'"
            );
        }

        $filePath = "sites/$siteBranch.my/hash_salt.txt";
        $this->tester->assertFileExists(
            "$workspaceDir/actual/$filePath",
            "File exists: '$filePath'"
        );
        $this->tester->assertGreaterThan(
            0,
            filesize("$workspaceDir/actual/$filePath"),
            "File not empty: '$filePath'"
        );
    }

    protected function getFixturesDir(): string
    {
        return codecept_data_dir('fixtures/Robo/Task/SiteCreateTask');
    }

    protected function getProjectConfig(string $projectRootDir): ProjectConfig
    {
        /** @var \Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig $projectConfig */
        $projectConfig = include $projectRootDir . '/' . Utils::$projectConfigFileName;

        return $projectConfig;
    }
}
