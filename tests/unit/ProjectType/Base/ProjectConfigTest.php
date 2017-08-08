<?php

namespace Sweetchuck\Robo\Drupal\Tests\Unit\ProjectType\Base;

use Sweetchuck\Robo\Drupal\ProjectType\Base\ProjectConfig;
use Sweetchuck\Robo\Drupal\Tests\Unit\Config\BaseConfigTest;

class ProjectConfigTest extends BaseConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected $className = ProjectConfig::class;

    public function testConstructor(): void
    {
        $data = [
            'sites' => [
                'foo' => [],
            ],
        ];

        /** @var \Sweetchuck\Robo\Drupal\ProjectType\Base\ProjectConfig $config */
        $config = new $this->className($data);
        $config->populateDefaultValues();

        $this->tester->assertEquals('foo', $config->sites['foo']->id);
    }
}
