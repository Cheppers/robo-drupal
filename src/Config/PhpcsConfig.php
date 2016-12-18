<?php

namespace Cheppers\Robo\Drupal\Config;

class PhpcsConfig extends BaseConfig
{
    public $paths = [];

    public $exclude = [];

    public $standard = 'Drupal';

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
