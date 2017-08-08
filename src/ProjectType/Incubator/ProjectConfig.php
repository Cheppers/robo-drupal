<?php

namespace Sweetchuck\Robo\Drupal\ProjectType\Incubator;

use Sweetchuck\Robo\Drupal\Config\DrupalExtensionConfig;
use Sweetchuck\Robo\Drupal\ProjectType\Base;

class ProjectConfig extends Base\ProjectConfig
{
    /**
     * @var \Sweetchuck\Robo\Drupal\Config\SiteConfig
     */
    public $siteDefaults = [];

    /**
     * One of: development (default), production.
     *
     * @var string
     */
    public $compassEnvironment = 'production';

    /**
     * @var bool
     */
    public $autodetectManagedDrupalExtensions = true;

    /**
     * @var \Sweetchuck\Robo\Drupal\Config\DrupalExtensionConfig[]
     */
    public $managedDrupalExtensions = [];

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        $this->propertyMapping += [
            'siteDefaults' => 'siteDefaults',
            'compassEnvironment' => 'compassEnvironment',
            'autodetectManagedDrupalExtensions' => 'autodetectManagedDrupalExtensions',
            'managedDrupalExtensions' => [
                'type' => 'subConfigs',
                'class' => DrupalExtensionConfig::class,
            ],
        ];

        return parent::initPropertyMapping();
    }
}
