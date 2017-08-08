<?php

namespace Sweetchuck\Robo\Drupal\Tests\Unit\Config;

use Sweetchuck\Robo\Drupal\Config\DrupalExtensionConfig;

class DrupalExtensionConfigTest extends BaseConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected $className = DrupalExtensionConfig::class;

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
