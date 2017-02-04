<?php

namespace Cheppers\Robo\Drupal\Config;

class PhpcsConfig extends BaseConfig
{
    public $files = [];

    public $filesGitStaged = [];

    public $exclude = [];

    public $standard = 'Drupal';

    public $failOn = 'warning';

    public $lintReporters = [];

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
        parent::initPropertyMapping();

        $this->propertyMapping['paths'] = 'paths';
        $this->propertyMapping['exclude'] = 'exclude';
        $this->propertyMapping['standard'] = 'standard';

        return $this;
    }
}
