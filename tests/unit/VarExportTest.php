<?php

namespace Cheppers\Robo\Drupal\Tests\Unit;

use Cheppers\Robo\Drupal\VarExport;

/**
 * Class VarExportTest.
 *
 * @covers \Cheppers\Robo\Drupal\VarExport
 */
class VarExportTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesAny(): array
    {
        return [
            'resource' => [
                '',
                STDOUT,
            ],
        ];
    }

    /**
     * @dataProvider casesAny
     */
    public function testAny($expected, $value, $depth = 1, string $indent = '    ')
    {
        $this->tester->assertEquals($expected, VarExport::any($value, $depth, $indent));
    }

    /**
     * @return array
     */
    public function casesString(): array
    {
        return [
            'empty' => ['', "''"],
            'simple quote' => ["'", "'\\''"],
            'double quote' => ['"', "'\"'"],
            'new line' => ["a\nb", "'a\nb'"],
            'chars' => ['ab', "'ab'"],
            'number' => ['42', "'42'"],
        ];
    }

    /**
     * @dataProvider casesString
     *
     * @param string $string
     * @param string $expected
     */
    public function testString(string $string, string $expected)
    {
        $this->tester->assertEquals($expected, VarExport::string($string));
    }

    public function casesMap(): array
    {
        return [
            [
                '[]',
                [],
                0,
            ],
            [
                implode("\n", [
                    '[',
                    "    'a' => 'b',",
                    ']',
                ]),
                [
                    'a' => 'b',
                ],
                0
            ],
            [
                implode("\n", [
                    '[',
                    "    'a' => [",
                    "        'b' => null,",
                    "        'c' => 42,",
                    "        'f' => true,",
                    "        'g' => false,",
                    '    ],',
                    ']',
                ]),
                [
                    'a' => [
                        'b' => null,
                        'c' => 42,
                        'f' => true,
                        'g' => false,
                    ],
                ],
                0
            ],
        ];
    }

    /**
     * @dataProvider casesMap
     */
    public function testMap(string $expected, array $array, int $indent)
    {
        $this->tester->assertEquals($expected, VarExport::map($array, $indent));
    }
}
