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
     * @var array
     */
    protected $dataDefaultValues = [
        'phpcs' => [
            'files' => [
                '.' => true,
            ],
            'standards' => [
                'Drupal' => true,
                'DrupalPractice' => true,
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        $this->propertyMapping += [
            'enabled' => 'enabled',
            'path' => 'path',
            'packageVendor' => 'packageVendor',
            'packageName' => 'packageName',
            'hasGit' => 'hasGit',
            'hasJavaScript' => 'hasJavaScript',
            'hasTypeScript' => 'hasTypeScript',
            'hasCSS' => 'hasCSS',
            'hasSCSS' => 'hasSCSS',
            'scssLint' => 'scssLint',
            'phpcs' => [
                'type' => 'subConfig',
                'class' => PhpcsConfig::class,
            ],
        ];
        parent::initPropertyMapping();

        return $this;
    }
}
