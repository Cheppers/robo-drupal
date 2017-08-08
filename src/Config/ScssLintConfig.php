<?php

namespace Sweetchuck\Robo\Drupal\Config;

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
        $this->propertyMapping += [
            'assetJarMapping' => 'assetJarMapping',
            'bundleExecutable' => 'bundleExecutable',
            'bundleGemFile' => 'bundleGemFile',
            'color' => 'color',
            'configFile' => 'configFile',
            'exclude' => 'exclude',
            'failOn' => 'failOn',
            'failOnNoFiles' => 'failOnNoFiles',
            'format' => 'format',
            'linters' => 'linters',
            'lintReporters' => 'lintReporters',
            'out' => 'out',
            'paths' => 'paths',
            'pathsGitStaged' => 'pathsGitStaged',
            'require' => 'require',
            'scssLintExecutable' => 'scssLintExecutable',
            'workingDirectory' => 'workingDirectory',
        ];
        parent::initPropertyMapping();

        return $this;
    }
}
