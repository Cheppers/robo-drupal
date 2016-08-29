<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task;

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\Config\ProjectIncubatorConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\Robo\Task\RebuildSitesPhpTask;
use Codeception\Util\Stub;
use Robo\Robo;
use Robo\Task\Filesystem\FilesystemStack;
use Robo\Tasks;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Cheppers\Robo\Drupal\Robo\Task\RebuildSitesPhpTask
 */
class RebuildSitesPhpTaskTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cleanFixturesDir();

        return parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->cleanFixturesDir();

        parent::tearDown();
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

        $pc = new ProjectIncubatorConfig();
        $pc->name = 'foo';
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
        $filesToDelete = [
            "$fixturesDir/with-example-sites-php/sites/sites.php",
            "$fixturesDir/without-example-sites-php/sites/sites.php",
            "$fixturesDir/without-example-sites-php/sites",
        ];
        $fs = new Filesystem();
        $fs->remove($filesToDelete);
    }

    protected function getFixturesDir(): string
    {
        return codecept_data_dir('fixtures/Robo/Task/RebuildSitesPhpTask');
    }
}
