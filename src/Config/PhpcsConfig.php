<?php

namespace Cheppers\Robo\Drupal\Config;

class PhpcsConfig extends BaseConfig
{
    public $files = [];

    /**
     * @var bool[]
     */
    public $filesGitStaged = [];

    public $exclude = [];

    public $standard = 'Drupal';

    public $failOn = 'warning';

    public $lintReporters = [];

    /**
     * @todo Use \Cheppers\Robo\Drupal\Utils::phpFileExtensionPatterns().
     */
    public $extensions = [
        'php/PHP' => true,
        'inc/PHP' => true,
        'module/PHP' => true,
        'profile/PHP' => true,
        'install/PHP' => true,
        'engine/PHP' => true,
        'js/JS' => true,
        'css/CSS' => true,
    ];

    public $ignore = [];

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        $this->propertyMapping += [
            'files' => 'files',
            'filesGitStaged' => 'filesGitStaged',
            'exclude' => 'exclude',
            'standard' => 'standard',
            'failOn' => 'failOn',
            'lintReporters' => 'lintReporters',
            'extensions' => 'extensions',
            'ignore' => 'ignore',
        ];
        parent::initPropertyMapping();

        return $this;
    }
}
