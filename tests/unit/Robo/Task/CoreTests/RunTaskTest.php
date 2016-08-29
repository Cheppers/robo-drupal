<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Robo\Task\CoreTests;

use Cheppers\Robo\Drupal\Robo\Task\CoreTests\RunTask;
use ReflectionClass;

class RunTaskTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesGetCommand(): array
    {
        return [
            'plain' => [
                "php core/scripts/run-tests.sh --color --verbose --non-html --module 'my_module'",
                [
                    'arguments' => ['my_module'],
                ],
            ],
            'drupal_root' => [
                "cd 'my/drupal/root' && php core/scripts/run-tests.sh --color --verbose --non-html --module 'my_foo'",
                [
                    'drupalRoot' => 'my/drupal/root',
                    'arguments' => ['my_foo'],
                ],
            ],
            'all' => [
                "cd 'my/drupal/root' && php core/scripts/run-tests.sh --color --verbose --non-html --all",
                [
                    'drupalRoot' => 'my/drupal/root',
                ],
            ],
            'concurrency' => [
                "php core/scripts/run-tests.sh --concurrency 42 --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'concurrency' => 42,
                ],
            ],
            'xml' => [
                "php core/scripts/run-tests.sh --xml '../foo/bar' --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'xml' => '../foo/bar',
                ],
            ],
            'url' => [
                "php core/scripts/run-tests.sh --url 'http://a.localhost:1024' --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'url' => 'http://a.localhost:1024',
                ],
            ],
            'sqlite' => [
                "php core/scripts/run-tests.sh --sqlite 'a/b.sqlite' --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'sqlite' => 'a/b.sqlite',
                ],
            ],
            'keep-results-table' => [
                "php core/scripts/run-tests.sh --keep-results-table --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'keepResultsTable' => true,
                ],
            ],
            'keep-results' => [
                "php core/scripts/run-tests.sh --keep-results --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'keepResults' => true,
                ],
            ],
            'dbUrl' => [
                "php core/scripts/run-tests.sh --dburl 'mysql://a:b@c.d:42/drupal' --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'dbUrl' => 'mysql://a:b@c.d:42/drupal',
                ],
            ],
            'php' => [
                "php core/scripts/run-tests.sh --php '/usr/bin/php7' --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'php' => '/usr/bin/php7',
                ],
            ],
            'repeat' => [
                "php core/scripts/run-tests.sh --repeat 42 --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'repeat' => 42,
                ],
            ],
            'die-on-fail' => [
                "php core/scripts/run-tests.sh --die-on-fail --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'dieOnFail' => true,
                ],
            ],
            'browser' => [
                "php core/scripts/run-tests.sh --browser --all",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'browser' => true,
                ],
            ],
            'subjectType' => [
                "php core/scripts/run-tests.sh --class 'A\\B,E\\F'",
                [
                    'color' => false,
                    'verbose' => false,
                    'nonHtml' => false,
                    'subjectType' => 'class',
                    'arguments' => [
                        'A\\B' => true,
                        'C\\D' => false,
                        'E\\F' => true,
                    ],
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

        $task = new RunTask($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }

    /**
     * @return array
     */
    public function casesAutodetectSubjectType()
    {
        return [
            'all' => ['all', ''],
            'class' => ['class', 'A\\B'],
            'directory' => ['directory', 'src/Robo'],
            'file' => ['file', 'src/foo.php'],
            'types' => ['types', 'Foo'],
            'module' => ['module', 'foo'],
        ];
    }

    /**
     * @dataProvider casesAutodetectSubjectType
     */
    public function testAutodetectSubjectType(string $expected, string $subject)
    {
        $task = new RunTask();
        $class = new ReflectionClass($task);
        $method = $class->getMethod('autodetectSubjectType');
        $method->setAccessible(true);

        $this->tester->assertEquals($expected, $method->invoke($task, $subject));
    }

    public function testSetSubjectTypeInvalid()
    {
        $task = new RunTask();
        try {
            $task->setSubjectType('foo');
            $this->tester->fail('Where is the exception?');
        } catch (\InvalidArgumentException $e) {
        }
    }
}
