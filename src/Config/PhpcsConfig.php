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

    public $standards = [
        'Drupal' => true,
        'DrupalPractice' => true,
    ];

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
        'theme/PHP' => true,
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
            'standards' => 'standards',
            'failOn' => 'failOn',
            'lintReporters' => 'lintReporters',
            'extensions' => 'extensions',
            'ignore' => 'ignore',
        ];
        parent::initPropertyMapping();

        return $this;
    }
}
