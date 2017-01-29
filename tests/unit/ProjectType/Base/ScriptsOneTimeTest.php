<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\ProjectType\Base;

use Cheppers\Robo\Drupal\ProjectType\Base\ScriptsOneTime;
use Codeception\Test\Unit;

class ScriptsOneTimeTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesGetDrupalProfiles(): array
    {
        $fixturesDir = codecept_data_dir('fixtures/drupal_root');

        return [
            '01 without hidden profiles' => [
                [
                    'a',
                    'b',
                    'c',
                    'minimal',
                    'standard',
                ],
                "$fixturesDir/01",
                false,
            ],
            '01 with hidden profiles' => [
                [
                    'a',
                    'b',
                    'c',
                    'd',
                    'minimal',
                    'standard',
                    'testing',
                ],
                "$fixturesDir/01",
                true,
            ],
        ];
    }

    /**
     * @dataProvider casesGetDrupalProfiles
     */
    public function testGetDrupalProfiles(array $expected, string $drupalRoot, bool $withHiddenOnes): void
    {
        $className = ScriptsOneTime::class;
        $methodName = 'getDrupalProfiles';
        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        $instance = new ScriptsOneTime();
        $actual = array_keys($method->invokeArgs($instance, [$drupalRoot, $withHiddenOnes]));
        $this->tester->assertEquals($expected, $actual);
    }

    public function casesIoSelectDrupalProfileChoices(): array
    {
        $fixturesDir = codecept_data_dir('fixtures/drupal_root');

        return [
            '01 without hidden profiles' => [
                [
                    'a' => 'Test profile A (a)',
                    'b' => 'Test profile B (b)',
                    'c' => 'Test profile C (c)',
                    'minimal' => 'Minimal (minimal)',
                    'standard' => 'Standard (standard)',
                ],
                "$fixturesDir/01",
                false,
            ],
            '01 with hidden profiles' => [
                [
                    'a' => 'Test profile A (a)',
                    'b' => 'Test profile B (b)',
                    'c' => 'Test profile C (c)',
                    'd' => 'Test profile D (d)',
                    'minimal' => 'Minimal (minimal)',
                    'standard' => 'Standard (standard)',
                    'testing' => 'Testing (testing)',
                ],
                "$fixturesDir/01",
                true,
            ],
        ];
    }

    /**
     * @dataProvider casesIoSelectDrupalProfileChoices
     */
    public function testIoSelectDrupalProfileChoices(array $expected, string $drupalRoot, bool $withHiddenOnes): void
    {
        $className = ScriptsOneTime::class;
        $methodName = 'ioSelectDrupalProfileChoices';
        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        $instance = new ScriptsOneTime();
        $actual = $method->invokeArgs($instance, [$drupalRoot, $withHiddenOnes]);
        $this->tester->assertEquals($expected, $actual);
    }
}
