<?php

namespace Cheppers\Robo\Drupal\Tests\Unit;

use Cheppers\Robo\Drupal\Utils;

/**
 * Class UtilsTest.
 *
 * @covers \Cheppers\Robo\Drupal\Utils
 */
class UtilsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesIsDefaultHttpPort(): array
    {
        return [
            '0' => [0, true],
            '80'=> [80, true],
            '443' => [443, true],
            '42' => [42, false],
            '8080' => [8080, false],
        ];
    }

    /**
     * @dataProvider casesIsDefaultHttpPort
     */
    public function testIsDefaultHttpPort(int $port, bool $expected)
    {
        $this->tester->assertEquals($expected, Utils::isDefaultHttpPort($port));
    }

    public function casesIsNumericIndexedArray(): array
    {
        return [
            'empty' => [false, []],
            'numeric 1' => [true, ['a']],
            'numeric 2' => [true, ['a', 'b']],
            'map string 1' => [false, ['a' => 'b']],
            'map string 2' => [false, ['a' => 'b', 'c' => 'd']],
            'map numeric 1' => [false, [1 => 'a']],
            'map numeric 2' => [false, [1 => 'a', 2 => 'b']],
            'map numeric 3' => [false, [0 => 'a', 2 => 'b']],
        ];
    }

    /**
     * @dataProvider casesIsNumericIndexedArray
     */
    public function testIsNumericIndexedArray(bool $expected, array $array)
    {
        $this->tester->assertEquals($expected, Utils::isNumericIndexedArray($array));
    }

    /**
     * @return array
     */
    public function casesIsDrupalPackage(): array
    {
        return [
            'drupal-core' => [
                true,
                [
                    'type' => 'drupal-core',
                ],
            ],
            'drupal-profile' => [
                true,
                [
                    'type' => 'drupal-profile',
                ],
            ],
            'drupal-module' => [
                true,
                [
                    'type' => 'drupal-module',
                ],
            ],
            'drupal-theme' => [
                true,
                [
                    'type' => 'drupal-theme',
                ],
            ],
            'drupal-unknown' => [
                false,
                [
                    'type' => 'drupal-unknown',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesIsDrupalPackage
     */
    public function testIsDrupalPackage(bool $expected, array $package)
    {
        $this->tester->assertEquals($expected, Utils::isDrupalPackage($package));
    }

    public function casesManipulateString(): array
    {
        return [
            [
                "\na\nd\nc",
                "\na\nb\nc",
                "\nb",
                "\nd",
                'replace',
            ],
            [
                "\na\nd\nb\nc",
                "\na\nb\nc",
                "\nb",
                "\nd",
                'before',
            ],
            [
                "\na\nb\nd\nc",
                "\na\nb\nc",
                "\nb",
                "\nd",
                'after',
            ],
        ];
    }

    /**
     * @dataProvider casesManipulateString
     */
    public function testManipulateString(
        string $expected,
        string $text,
        string $search,
        string $replace,
        string $method
    ) {
        Utils::manipulateString($text, $search, $replace, $method);
        $this->tester->assertEquals($expected, $text);
    }

    public function testManipulateStringError()
    {
        $text = 'foo';
        $search = 'bar';
        $replace = 'baz';
        $method = 'replace';
        try {
            Utils::manipulateString($text, $search, $replace, $method);
            $this->tester->fail('Where is the exception?');
        } catch (\Exception $e) {
            $this->tester->assertEquals("Placeholder not found: '$search'", $e->getMessage());
        }
    }

    public function testManipulateStringFail()
    {
        $text = 'a';
        $search = 'b';
        $replace = 'c';
        $method = 'none';
        try {
            Utils::manipulateString($text, $search, $replace, $method);
            $this->tester->fail('Where is the exception?');
        } catch (\InvalidArgumentException $e) {
            $this->tester->assertEquals($e->getMessage(), "Unknown method: '$method'");
        }
    }

    /**
     * @return array
     */
    public function casesDirNamesByDepth(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            '3 level' => [
                [
                    0 => [
                        'a' => 'a',
                        'e' => 'e',
                    ],
                    1 => [
                        'a/b' => 'a/b',
                        'e/f' => 'e/f',
                    ],
                    2 => [
                        'a/b/c' => 'a/b/c',
                        'e/f/g' => 'e/f/g',
                    ],
                ],
                [
                    'a/b/c/d.txt',
                    'e/f/g/h.txt',
                    'i.txt',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesDirNamesByDepth
     */
    public function testDirNamesByDepth(array $expected, array $fileNames)
    {
        $this->tester->assertEquals($expected, Utils::dirNamesByDepth($fileNames));
    }
}
