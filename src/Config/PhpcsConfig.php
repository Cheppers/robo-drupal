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
        parent::initPropertyMapping();

        $this->propertyMapping['files'] = 'files';
        $this->propertyMapping['filesGitStaged'] = 'filesGitStaged';
        $this->propertyMapping['exclude'] = 'exclude';
        $this->propertyMapping['standard'] = 'standard';
        $this->propertyMapping['failOn'] = 'failOn';
        $this->propertyMapping['lintReporters'] = 'lintReporters';
        $this->propertyMapping['extensions'] = 'extensions';
        $this->propertyMapping['ignore'] = 'ignore';

        return $this;
    }
}
