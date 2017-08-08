<?php

namespace Sweetchuck\Robo\Drupal\Tests\Unit\Robo\Task;

use Sweetchuck\AssetJar\AssetJar;
use Sweetchuck\Robo\Drupal\Robo\Task\ComposerPackagePathsTask;
use Sweetchuck\Robo\Drupal\Test\Helper\Dummy\Process as DummyProcess;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Robo\Robo;

/**
 * @covers \Sweetchuck\Robo\Drupal\Robo\Task\ComposerPackagePathsTask
 */
class ComposerPackagePathsTaskTest extends Unit
{
    /**
     * @var \Sweetchuck\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        DummyProcess::reset();

        parent::setUp();
    }

    /**
     * @return array
     */
    public function casesGetCommand()
    {
        return [
            'defaults' => [
                'composer show -P',
                [],
            ],
            'working directory' => [
                "cd 'my-wd' && composer show -P",
                [
                    'workingDirectory' => 'my-wd',
                ],
            ],
            'composer' => [
                'composer.phar show -P',
                [
                    'composerExecutable' => 'composer.phar',
                ],
            ],
            'wd+composer' => [
                "cd 'my-wd' && composer.phar show -P",
                [
                    'workingDirectory' => 'my-wd',
                    'composerExecutable' => 'composer.phar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options): void
    {
        $task = new ComposerPackagePathsTask($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }

    /**
     * @return array
     */
    public function casesParseOutput()
    {
        return [
            'empty' => [
                [],
                '',
            ],
            'one line' => [
                [
                    'a/b' => 'c',
                ],
                implode("\n", [
                    'a/b c',
                    ''
                ]),
            ],
            'more lines with trailing space' => [
                [
                    'a/b' => 'c',
                    'd/e' => 'f ',
                ],
                implode("\n", [
                    'a/b c',
                    'd/e f ',
                    ''
                ]),
            ],
        ];
    }

    /**
     * @dataProvider casesParseOutput
     */
    public function testParseOutput(array $expected, string $stdOutput): void
    {
        $this->tester->assertEquals(
            $expected,
            ComposerPackagePathsTask::parseOutput($stdOutput)
        );
    }

    /**
     * @return array
     */
    public function casesRun(): array
    {
        return [
            'empty' => [
                [],
            ],
            'simple' => [
                [
                    'a/b c',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRun
     */
    public function testRun(array $expected): void
    {
        $fakeStdOutput = '';
        foreach ($expected as $packageName => $packagePath) {
            $fakeStdOutput .= "$packageName $packagePath\n";
        }

        $assetJar = new AssetJar();

        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        /** @var \Sweetchuck\Robo\Drupal\Robo\Task\ComposerPackagePathsTask $task */
        $task = Stub::construct(
            ComposerPackagePathsTask::class,
            [
                [
                    'assetJar' => $assetJar,
                    'assetJarMapping' => [
                        'workingDirectory' => ['wd'],
                        'packagePaths' => ['pp'],
                    ],
                    'workingDirectory' => 'my-wd',
                ],
            ],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        $processIndex = count(DummyProcess::$instances);
        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => 0,
            'stdOutput' => $fakeStdOutput,
            'stdError' => '',
        ];

        $result = $task->run();

        $this->assertEquals(
            $expected,
            $result['packagePaths'],
            'Package paths in the task result'
        );

        $this->assertEquals(
            'my-wd',
            $task->getAssetJarValue('workingDirectory'),
            'Working directory in the asset jar'
        );

        $this->assertEquals(
            $expected,
            $task->getAssetJarValue('packagePaths')
        );
    }

    public function testRunFail(): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        /** @var \Sweetchuck\Robo\Drupal\Robo\Task\ComposerPackagePathsTask $task */
        $task = Stub::construct(
            ComposerPackagePathsTask::class,
            [],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        $processIndex = count(DummyProcess::$instances);
        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => 1,
            'stdOutput' => '',
            'stdError' => '',
        ];

        $result = $task->run();

        $this->assertEquals(1, $result->getExitCode());
    }
}
