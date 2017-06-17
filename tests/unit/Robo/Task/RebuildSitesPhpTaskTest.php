<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task;

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\Robo\Task\RebuildSitesPhpTask;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Robo\Robo;
use Robo\Task\Filesystem\FilesystemStack;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Cheppers\Robo\Drupal\Robo\Task\RebuildSitesPhpTask
 */
class RebuildSitesPhpTaskTest extends Unit
{
    /**
     * @var \Cheppers\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    // @codingStandardsIgnoreStart
    protected function _before()
    {
        // @codingStandardsIgnoreEnd
        parent::_before();
        $this->cleanFixturesDir();
    }

    // @codingStandardsIgnoreStart
    protected function _after()
    {
        // @codingStandardsIgnoreEnd
        $this->cleanFixturesDir();
        parent::_after();
    }

    public function casesRun(): array
    {
        $fixturesDir = $this->getFixturesDir();

        return [
            'with example.sites.php' => [
                implode("\n", [
                    '<?php',
                    '',
                    '/**',
                    ' * @file',
                    ' * Dummy.',
                    ' */',
                    '$sites = [];',
                    '',
                ]),
                "{$fixturesDir}/with-example-sites-php",
                [],
                [],
                [],
            ],
            'without example.sites.php' => [
                implode("\n", [
                    '<?php',
                    '',
                    '$sites = [];',
                    '',
                ]),
                "{$fixturesDir}/without-example-sites-php",
                [],
                [],
                [],
            ],
            'php7.my56.default.foo.localhost' => [
                implode("\n", [
                    '<?php',
                    '',
                    '$sites = [',
                    "  'php7.my56.default.foo.localhost' => 'default.my56',",
                    '];',
                    '',
                ]),
                "{$fixturesDir}/without-example-sites-php",
                ['php7'],
                ['my56'],
                ['default'],
            ],
        ];
    }

    /**
     * @dataProvider casesRun
     */
    public function testRun(
        string $expected,
        string $drupalRootDir,
        array $phpVariants,
        array $databaseServers,
        array $sites
    ) {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $pc = new ProjectConfig();
        $pc->id = 'foo';
        $pc->drupalRootDir = $drupalRootDir;
        foreach ($phpVariants as $phpVariant) {
            $pc->phpVariants[$phpVariant] = new PhpVariantConfig();
        }

        foreach ($databaseServers as $databaseServer) {
            $pc->databaseServers[$databaseServer] = new DatabaseServerConfig();
        }

        foreach ($sites as $site) {
            $pc->sites[$site] = new SiteConfig();
        }

        $pc->populateDefaultValues();

        $options = [
            'projectConfig' => $pc,
        ];

        /** @var \Cheppers\Robo\Drupal\Robo\Task\RebuildSitesPhpTask $task */
        $task = Stub::construct(
            RebuildSitesPhpTask::class,
            [$options],
            [
                '_mkdir' => function ($dir) {
                    return (new FilesystemStack())
                        ->mkdir($dir)
                        ->run();
                }
            ]
        );

        $result = $task->run();

        $this->tester->assertEquals($expected, file_get_contents("$drupalRootDir/sites/sites.php"));
        $this->tester->assertEquals($pc->getProjectUrls(), $result['projectUrls']);
    }

    protected function cleanFixturesDir()
    {
        $fixturesDir = $this->getFixturesDir();
        $filesToDelete = array_filter(
            [
                "$fixturesDir/with-example-sites-php/sites/sites.php",
                //"$fixturesDir/without-example-sites-php/sites/sites.php",
                "$fixturesDir/without-example-sites-php/sites",
            ],
            'file_exists'
        );

        (new Filesystem())->remove($filesToDelete);
    }

    protected function getFixturesDir(): string
    {
        return codecept_data_dir('fixtures/Robo/Task/RebuildSitesPhpTask');
    }
}
