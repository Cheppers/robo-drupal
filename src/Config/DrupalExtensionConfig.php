<?php

namespace Cheppers\Robo\Drupal\Config;

class DrupalExtensionConfig extends BaseConfig
{
    public $enabled = true;

    /**
     * Root directory of the package.
     *
     * @var string
     */
    public $path = '';

    public $packageVendor = '';

    public $packageName = '';

    /**
     * @var null|\Cheppers\Robo\Drupal\Config\PhpcsConfig
     */
    public $phpcs = null;

    public $hasTypeScript = false;

    public $hasSCSS = false;

    public $scssLint = [
        'paths' => [
            'css/'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();

        $this->propertyMapping['enabled'] = 'enabled';
        $this->propertyMapping['path'] = 'path';
        $this->propertyMapping['packageVendor'] = 'packageVendor';
        $this->propertyMapping['packageName'] = 'packageName';
        $this->propertyMapping['phpcs'] = [
            'type' => 'subConfig',
            'class' => PhpcsConfig::class,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function populateProperties()
    {
        parent::populateProperties();

        if ($this->phpcs === null) {
            $this->phpcs = new PhpcsConfig();
        }

        return $this;
    }
}
