<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task;

use Cheppers\AssetJar\AssetJar;
use Cheppers\Robo\Drupal\Robo\Task\ComposerPackagePathsTask;
use Codeception\Test\Unit;
use Codeception\Util\Stub;

/**
 * @covers \Cheppers\Robo\Drupal\Robo\Task\ComposerPackagePathsTask
 */
class ComposerPackagePathsTaskTest extends Unit
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
        \Helper\Dummy\Process::reset();

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

        $container = \Robo\Robo::createDefaultContainer();
        \Robo\Robo::setContainer($container);

        /** @var \Cheppers\Robo\Drupal\Robo\Task\ComposerPackagePathsTask $task */
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
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        $processIndex = count(\Helper\Dummy\Process::$instances);
        \Helper\Dummy\Process::$prophecy[$processIndex] = [
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
        $container = \Robo\Robo::createDefaultContainer();
        \Robo\Robo::setContainer($container);

        /** @var \Cheppers\Robo\Drupal\Robo\Task\ComposerPackagePathsTask $task */
        $task = Stub::construct(
            ComposerPackagePathsTask::class,
            [],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        $processIndex = count(\Helper\Dummy\Process::$instances);
        \Helper\Dummy\Process::$prophecy[$processIndex] = [
            'exitCode' => 1,
            'stdOutput' => '',
            'stdError' => '',
        ];

        $result = $task->run();

        $this->assertEquals(1, $result->getExitCode());
    }
}
