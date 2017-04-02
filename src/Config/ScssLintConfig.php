<?php

namespace Cheppers\Robo\Drupal\Config;

class ScssLintConfig extends BaseConfig
{
    /**
     * @var array
     */
    public $assetJarMapping = [];

    /**
     * @var string
     */
    public $workingDirectory = '';

    /**
     * @var string
     */
    public $bundleGemFile = '';

    /**
     * @var string
     */
    public $bundleExecutable = 'bundle';

    /**
     * @var string
     */
    public $scssLintExecutable = 'scss-lint';

    /**
     * @var string
     */
    public $failOn = 'warning';

    /**
     * @var bool
     */
    public $failOnNoFiles = true;

    /**
     * @var array
     */
    public $lintReporters = [
        'lintVerboseReporter' => null,
    ];

    /**
     * @var string
     */
    public $format = '';

    /**
     * @var array
     */
    public $require = [];

    /**
     * @var bool[]|string[]
     */
    public $linters = [];

    /**
     * @var string
     */
    public $configFile = '';

    /**
     * @var array
     */
    public $exclude = [
        '*.css' => true,
    ];

    /**
     * @var string
     */
    public $out = '';

    /**
     * @var bool
     */
    public $color = false;

    /**
     * @var array
     */
    public $paths = [];

    /**
     * @var bool[]
     */
    public $pathsGitStaged = [
        '*.scss' => true,
    ];

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();

        $this->propertyMapping['assetJarMapping'] = 'assetJarMapping';
        $this->propertyMapping['bundleExecutable'] = 'bundleExecutable';
        $this->propertyMapping['bundleGemFile'] = 'bundleGemFile';
        $this->propertyMapping['color'] = 'color';
        $this->propertyMapping['configFile'] = 'configFile';
        $this->propertyMapping['exclude'] = 'exclude';
        $this->propertyMapping['failOn'] = 'failOn';
        $this->propertyMapping['failOnNoFiles'] = 'failOnNoFiles';
        $this->propertyMapping['format'] = 'format';
        $this->propertyMapping['linters'] = 'linters';
        $this->propertyMapping['lintReporters'] = 'lintReporters';
        $this->propertyMapping['out'] = 'out';
        $this->propertyMapping['paths'] = 'paths';
        $this->propertyMapping['pathsGitStaged'] = 'pathsGitStaged';
        $this->propertyMapping['require'] = 'require';
        $this->propertyMapping['scssLintExecutable'] = 'scssLintExecutable';
        $this->propertyMapping['workingDirectory'] = 'workingDirectory';

        return $this;
    }
}
