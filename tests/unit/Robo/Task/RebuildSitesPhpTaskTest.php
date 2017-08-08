<?php

namespace Sweetchuck\Robo\Drupal\Tests\Unit\Robo\Task;

use Sweetchuck\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Sweetchuck\Robo\Drupal\Robo\Task\RebuildSitesPhpTask;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Robo\Robo;
use Robo\Task\Filesystem\FilesystemStack;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Sweetchuck\Robo\Drupal\Robo\Task\RebuildSitesPhpTask
 */
class RebuildSitesPhpTaskTest extends Unit
{
    /**
     * @var \Sweetchuck\Robo\Drupal\Test\UnitTester
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
                [
                    'id' => 'foo',
                    'drupalRootDir' => "{$fixturesDir}/with-example-sites-php",
                ],
            ],
            'without example.sites.php' => [
                implode("\n", [
                    '<?php',
                    '',
                    '$sites = [];',
                    '',
                ]),
                [
                    'id' => 'foo',
                    'drupalRootDir' => "{$fixturesDir}/without-example-sites-php",
                ],
            ],
            '70106.my.default.foo.localhost' => [
                implode("\n", [
                    '<?php',
                    '',
                    '$sites = [',
                    "  '70106.my.default.foo.localhost' => 'default.my',",
                    '];',
                    '',
                ]),
                [
                    'id' => 'foo',
                    'drupalRootDir' => "{$fixturesDir}/without-example-sites-php",
                    'phpVariants' => [
                        '70106' => [],
                    ],
                    'databaseServers' => [
                        'my' => [],
                    ],
                    'sites' => [
                        'default' => [],
                    ],
                ],
            ],
            '(70106|50630).(my|pg).(default|other).foo.localhost' => [
                implode("\n", [
                    '<?php',
                    '',
                    '$sites = [',
                    "  '70106.my.default.foo.localhost' => 'default.my',",
                    "  '50630.my.default.foo.localhost' => 'default.my',",
                    "  '70106.pg.default.foo.localhost' => 'default.pg',",
                    "  '50630.pg.default.foo.localhost' => 'default.pg',",
                    "  '70106.my.other.foo.localhost' => 'other.my',",
                    "  '50630.my.other.foo.localhost' => 'other.my',",
                    "  '70106.pg.other.foo.localhost' => 'other.pg',",
                    "  '50630.pg.other.foo.localhost' => 'other.pg',",
                    '];',
                    '',
                ]),
                [
                    'id' => 'foo',
                    'drupalRootDir' => "{$fixturesDir}/without-example-sites-php",
                    'phpVariants' => [
                        '70106' => [],
                        '50630' => [],
                    ],
                    'databaseServers' => [
                        'my' => [],
                        'pg' => [],
                    ],
                    'sites' => [
                        'default' => [],
                        'other' => [],
                    ],
                ],
            ],
            '(70106|50630).(my|pg).(default|other).incubator' => [
                implode("\n", [
                    '<?php',
                    '',
                    '$sites = [',
                    "  '1080.70106.my.default.incubator' => 'default.my',",
                    "  '1080.50630.my.default.incubator' => 'default.my',",
                    "  '1080.70106.pg.default.incubator' => 'default.pg',",
                    "  '1080.50630.pg.default.incubator' => 'default.pg',",
                    "  '1080.70106.my.other.incubator' => 'other.my',",
                    "  '1080.50630.my.other.incubator' => 'other.my',",
                    "  '1080.70106.pg.other.incubator' => 'other.pg',",
                    "  '1080.50630.pg.other.incubator' => 'other.pg',",
                    '];',
                    '',
                ]),
                [
                    'id' => 'foo',
                    'baseHostName' => 'incubator',
                    'baseHostPort' => 1080,
                    'drupalRootDir' => "{$fixturesDir}/without-example-sites-php",
                    'phpVariants' => [
                        '70106' => [],
                        '50630' => [],
                    ],
                    'databaseServers' => [
                        'my' => [],
                        'pg' => [],
                    ],
                    'sites' => [
                        'default' => [],
                        'other' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRun
     */
    public function testRun(
        string $expected,
        array $projectConfig
    ) {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $pc = new ProjectConfig($projectConfig);
        $pc->populateDefaultValues();

        $options = [
            'projectConfig' => $pc,
        ];

        /** @var \Sweetchuck\Robo\Drupal\Robo\Task\RebuildSitesPhpTask $task */
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

        $this->tester->assertEquals($expected, file_get_contents("{$projectConfig['drupalRootDir']}/sites/sites.php"));
        $this->tester->assertEquals($pc->getSitesPhpDefinition(), $result['sitesPhp']);
    }

    protected function cleanFixturesDir()
    {
        $fixturesDir = $this->getFixturesDir();
        $filesToDelete = array_filter(
            [
                "$fixturesDir/with-example-sites-php/sites/sites.php",
                "$fixturesDir/without-example-sites-php/sites/sites.php",
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
