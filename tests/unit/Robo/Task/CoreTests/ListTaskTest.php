<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task\CoreTests;

use Cheppers\Robo\Drupal\Robo\Task\CoreTests\ListTask;

class ListTaskTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesGetCommand(): array
    {
        return [
            'plain' => [
                "php core/scripts/run-tests.sh --list",
                [],
            ],
            'drupal_root' => [
                "cd 'my/drupal/root' && php core/scripts/run-tests.sh --list",
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

        $task = new ListTask($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }
}
