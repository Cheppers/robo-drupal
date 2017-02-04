<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Config;

use Cheppers\Robo\Drupal\Config\DrupalExtensionConfig;

class DrupalExtensionConfigTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function casesConstructor()
    {
        return [
            'all in one' => [
                [
                    'enabled' => true,
                    'path' => 'a/b',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesConstructor
     */
    public function testConstructor(array $expected)
    {
        $config = new DrupalExtensionConfig($expected);
        foreach ($expected as $property => $value) {
            $this->tester->assertEquals($expected[$property], $config->$property);
        }
    }
}
