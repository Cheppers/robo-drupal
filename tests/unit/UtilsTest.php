<?php

namespace Cheppers\Robo\Drupal\Tests\Unit;

use Cheppers\Robo\Drupal\Utils;
use Codeception\Test\Unit;

/**
 * @covers \Cheppers\Robo\Drupal\Utils
 */
class UtilsTest extends Unit
{
    /**
     * @var \Cheppers\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    public function casesPhpFileExtensionPatterns(): array
    {
        return [
            'basic' => [
                [
                    '*.a' => true,
                    '*.b' => false,
                ],
                [
                    'a' => true,
                    'b' => false,
                ],
                '*.',
                '',
            ],
        ];
    }

    /**
     * @dataProvider casesPhpFileExtensionPatterns
     */
    public function testPhpFileExtensionPatterns(
        array $expected,
        array $extensions,
        string $prefix,
        string $suffix
    ): void {
        $backup = Utils::$phpFileExtensions;
        Utils::$phpFileExtensions = $extensions;

        $this->tester->assertEquals($expected, Utils::phpFileExtensionPatterns($prefix, $suffix));

        Utils::$phpFileExtensions = $backup;
    }

    public function testGetRoboDrupalRoot(): void
    {
        $this->tester->assertEquals(getcwd(), Utils::getRoboDrupalRoot());
    }

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
    public function testIsDefaultHttpPort(int $port, bool $expected): void
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
    public function testIsNumericIndexedArray(bool $expected, array $array): void
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
    public function testIsDrupalPackage(bool $expected, array $package): void
    {
        $this->tester->assertEquals($expected, Utils::isDrupalPackage($package));
    }

    public function casesGetDefaultMysqlPort(): array
    {
        return [
            'init 3306' => [3306, 3306],
            'ini 0' => [3306, 0],
            'ini 3311' => [3311, 3311],
        ];
    }

    /**
     * @dataProvider casesGetDefaultMysqlPort
     */
    public function testGetDefaultMysqlPort(int $expected, int $ini): void
    {
        $ini_var_name = 'mysqli.default_port';
        $backup = ini_get($ini_var_name);
        ini_set($ini_var_name, $ini);
        $this->tester->assertEquals($expected, Utils::getDefaultMysqlPort());
        ini_set($ini_var_name, $backup);
    }

    public function casesIsLocalhost(): array
    {
        return [
            '127.0.0.1' => [true, '127.0.0.1'],
            'localhost' => [true, 'localhost'],
            'example.com' => [false, 'example.com'],
        ];
    }

    /**
     * @dataProvider casesIsLocalhost
     */
    public function testIsLocalhost($expected, $hostName): void
    {
        $this->tester->assertEquals($expected, Utils::isLocalhost($hostName));
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
    ): void {
        Utils::manipulateString($text, $search, $replace, $method);
        $this->tester->assertEquals($expected, $text);
    }

    public function testManipulateStringError(): void
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

    public function testManipulateStringFail(): void
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

    public function casesAddUseStatement(): array
    {
        return [
            'basic' => [
                implode("\n", [
                    '<?php',
                    'use A\B;',
                    'use A\C;',
                    'use A\D;',
                ]),
                implode("\n", [
                    '<?php',
                    'use A\B;',
                    'use A\D;',
                ]),
                'A\C',
            ],
        ];
    }

    /**
     * @dataProvider casesAddUseStatement
     */
    public function testAddUseStatement(string $expected, string $phpCode, string $class, string $as = ''): void
    {
        $this->tester->assertEquals($expected, Utils::addUseStatement($phpCode, $class, $as));
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
    public function testDirNamesByDepth(array $expected, array $fileNames): void
    {
        $this->tester->assertEquals($expected, Utils::dirNamesByDepth($fileNames));
    }

    /**
     * @return array
     */
    public function casesItemProperty2ArrayKey(): array
    {
        return [
            'empty' => [
                [],
                [],
                'id',
            ],
            'foo' => [
                [
                    'a' => [
                        'id' => 'a',
                    ],
                    'b' => [
                        'id' => 'b',
                    ],
                ],
                [
                    [
                        'id' => 'a',
                    ],
                    [
                        'id' => 'b',
                    ],
                ],
                'id',
            ],
        ];
    }

    /**
     * @dataProvider casesItemProperty2ArrayKey
     */
    public function testItemProperty2ArrayKey(array $expected, array $items, string $property): void
    {
        $this->tester->assertEquals($expected, Utils::itemProperty2ArrayKey($items, $property));
    }

    public function testItemProperty2ArrayKeyNotExists(): void
    {
        $items = [
            [
                'id' => 1,
            ],
            [
                'name' => 'foo',
            ],
        ];
        $property = 'id';
        try {
            Utils::itemProperty2ArrayKey($items, $property);
            $this->tester->fail('Where is the exception?');
        } catch (\Exception $e) {
            $this->tester->assertEquals(
                "Property doesn't exists '$property'",
                $e->getMessage()
            );
        }
    }

    public function testItemProperty2ArrayKeyUnique(): void
    {
        $items = [
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
            [
                'id' => 2,
            ],
        ];
        $property = 'id';
        try {
            Utils::itemProperty2ArrayKey($items, $property);
            $this->tester->fail('Where is the exception?');
        } catch (\Exception $e) {
            $this->tester->assertEquals(
                "Unique key already exists '2'",
                $e->getMessage()
            );
        }
    }

    /**
     * @return array
     */
    public function casesFilterDisabled(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'all in' => [
                [
                    'a' => true,
                    'c' => 'foo',
                    'e' => 1,
                    'f' => -1,
                    'h' => [
                        'enabled' => true,
                    ],
                    'j' => (object) [
                        'enabled' => true,
                    ],
                ],
                [
                    'a' => true,
                    'b' => false,
                    'c' => 'foo',
                    'd' => '',
                    'e' => 1,
                    'f' => -1,
                    'g' => 0,
                    'h' => [
                        'enabled' => true,
                    ],
                    'i' => [
                        'enabled' => false,
                    ],
                    'j' => (object) [
                        'enabled' => true,
                    ],
                    'k' => (object) [
                        'enabled' => false,
                    ],
                ],
            ],
            'non-default property' => [
                [
                    'b' => [
                        'available' => true,
                    ],
                    'e' => (object) [
                        'available' => true,
                    ],
                ],
                [
                    'a' => [
                        'enabled' => true,
                    ],
                    'b' => [
                        'available' => true,
                    ],
                    'c' => [
                        'available' => false,
                    ],
                    'd' => (object) [
                        'enabled' => true,
                    ],
                    'e' => (object) [
                        'available' => true,
                    ],
                    'f' => (object) [
                        'available' => false,
                    ],
                ],
                'available',
            ],
        ];
    }

    /**
     * @dataProvider casesFilterDisabled
     */
    public function testFilterDisabled(array $expected, array $items, string $property = 'enabled'): void
    {
        $this->tester->assertEquals($expected, Utils::filterDisabled($items, $property));
    }

    public function casesGetCustomDrupalProfiles(): array
    {
        $fixturesDir = codecept_data_dir('fixtures');

        return [
            'basic' => [
                [
                    'c' => "$fixturesDir/drupal_root/01/profiles/custom/c",
                ],
                "$fixturesDir/drupal_root/01",
            ],
            'no-custom' => [
                [
                    'a' => "$fixturesDir/drupal_root/02/profiles/a",
                ],
                "$fixturesDir/drupal_root/02",
            ],
        ];
    }

    /**
     * @dataProvider casesGetCustomDrupalProfiles
     */
    public function testGetCustomDrupalProfiles(array $expected, string $drupalRoot): void
    {
        $this->tester->assertEquals(
            $expected,
            Utils::getCustomDrupalProfiles($drupalRoot)
        );
    }

    public function casesIncludeFileNames(): array
    {
        return [
            'empty' => [
                [],
                [
                    [],
                    [],
                    [],
                ],
            ],
            'basic' => [
                [
                    'a.php',
                    'a.css',
                ],
                [
                    [
                        'a.php',
                        'a.css',
                        'a.scss',
                        'a.dev.css',
                        'a.local.css',
                    ],
                    [
                        '*.php' => 'glob',
                        '*.css' => 'glob',
                    ],
                    [
                        '*.dev.css' => 'glob',
                        '@\.local\.css$@' => 'regexp',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesIncludeFileNames
     */
    public function testIncludeFileNames(array $expected, array $args): void
    {
        $this->tester->assertEquals($expected, Utils::includeFileNames(...$args));
    }

    public function casesExcludeFileNames(): array
    {
        return [
            'basic' => [
                [
                    'a.css',
                    'a.local.css',
                ],
                [
                    [
                        'a.php',
                        'a.css',
                        'a.scss',
                        'a.dev.css',
                        'a.local.css',
                    ],
                    [
                        '*.php' => 'glob',
                        '*.css' => 'glob',
                        '@.+\.scss$@' => 'regexp',
                    ],
                    [
                        'a.css' => 'glob',
                        '@\.local\.css$@' => 'regexp',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesExcludeFileNames
     */
    public function testExcludeFileNames($expected, array $args): void
    {
        $this->tester->assertEquals($expected, Utils::excludeFileNames(...$args));
    }
}
