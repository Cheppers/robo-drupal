<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Config;

use Cheppers\Robo\Drupal\Config\GeneralReleaseConfig;

class GeneralReleaseConfigTest extends BaseConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected $className = GeneralReleaseConfig::class;

    /**
     * @return array
     */
    public function casesConstructor()
    {
        return [
            'all in one' => [
                [
                    'releaseDir' => 'my-release-dir',
                    'gitRemoteName' => 'my-remote',
                    'gitRemoteBranch' => 'my-r-branch',
                    'gitLocalBranch' => 'my-l-branch',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesConstructor
     */
    public function testConstructor(array $expected)
    {
        $class = $this->className;
        $config = new $class($expected);
        foreach ($expected as $property => $value) {
            $this->tester->assertEquals($expected[$property], $config->$property);
        }
    }
}
