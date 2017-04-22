<?php

namespace Cheppers\Robo\Drupal\Config;

class SassRootConfig extends BaseConfig
{
    /**
     * @var null|string
     */
    public $bundleGemFile = null;

    /**
     * @var string
     */
    public $sassDir = '';

    /**
     * @var string
     */
    public $cssDir = '';

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();

        $this->propertyMapping['bundleGemFile'] = 'bundleGemFile';
        $this->propertyMapping['sassDir'] = 'sassDir';
        $this->propertyMapping['cssDir'] = 'cssDir';

        return $this;
    }
}
