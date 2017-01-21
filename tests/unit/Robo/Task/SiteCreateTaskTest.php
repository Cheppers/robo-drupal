<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task;

use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Robo\Task\SiteCreateTask;
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
     * @var \UnitTester
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
        ];

        $task = new SiteCreateTask($options);

        $result = $task->run();

        $this->tester->assertEquals('', $result->getMessage());
        $this->tester->assertEquals(0, $result->getExitCode());

        $expectedFiles = new Finder();
        $expectedFiles
            ->files()
            ->in("$workspaceDir/expected");
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
            "drupal_root/sites/$siteBranch.my56/files",
            'sites/all/translations',
            "sites/$siteBranch.my56/config/sync",
            "sites/$siteBranch.my56/private",
            "sites/$siteBranch.my56/temporary",
        ];
        foreach ($expectedDirectories as $expectedDirectory) {
            $this->tester->assertTrue(
                is_dir("$workspaceDir/actual/$expectedDirectory"),
                "Directory exists: '$expectedDirectory'"
            );
        }

        $filePath = "sites/$siteBranch.my56/hash_salt.txt";
        $this->tester->assertTrue(
            is_file("$workspaceDir/actual/$filePath"),
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
        require "$projectRootDir/ProjectConfig.php";

        global $projectConfig;

        return $projectConfig;
    }
}
