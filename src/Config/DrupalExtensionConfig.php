<?php

namespace Cheppers\Robo\Drupal\Config;

/**
 * @todo Add ::$phpcsConfig property.
 * @todo Add ::$tsLintConfig property.
 * @todo Add ::$esLintConfig property.
 * @todo Add ::$scssLintConfig property.
 * @todo Decision about ::$hasTypeScript vs ::$typeScriptDir vs ::$tsLintConfig - (same with JavaScript and SCSS).
 */
class DrupalExtensionConfig extends BaseConfig
{
    public $enabled = true;

    /**
     * Root directory of the package.
     *
     * @var string
     */
    public $path = '';

    /**
     * First part of the composer.json:name.
     *
     * @var string
     */
    public $packageVendor = '';

    /**
     * Second part of the composer.json:name.
     *
     * @var string
     */
    public $packageName = '';

    /**
     * @var bool
     */
    public $hasGit = false;

    /**
     * @var null|\Cheppers\Robo\Drupal\Config\PhpcsConfig
     */
    public $phpcs = null;

    public $hasJavaScript = false;

    public $hasTypeScript = false;

    public $hasCSS = false;

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
        $this->propertyMapping['hasGit'] = 'hasGit';
        $this->propertyMapping['phpcs'] = [
            'type' => 'subConfig',
            'class' => PhpcsConfig::class,
        ];
        $this->propertyMapping['hasJavaScript'] = 'hasJavaScript';
        $this->propertyMapping['hasTypeScript'] = 'hasTypeScript';
        $this->propertyMapping['hasCSS'] = 'hasCSS';
        $this->propertyMapping['hasSCSS'] = 'hasSCSS';
        $this->propertyMapping['scssLint'] = 'scssLint';

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
