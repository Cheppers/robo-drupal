<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task\CoreTests;

use Cheppers\Robo\Drupal\Robo\Task\CoreTests\CleanTask;

class CleanTaskTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesGetCommand(): array
    {
        return [
            'plain' => [
                "php core/scripts/run-tests.sh --clean",
                [],
            ],
            'drupal_root' => [
                "cd 'my/drupal/root' && php core/scripts/run-tests.sh --clean",
                [
                    'drupalRoot' => 'my/drupal/root',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options)
    {
        $options += ['phpExecutable' => 'php'];

        $task = new CleanTask($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }
}
