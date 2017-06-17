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
        $this->propertyMapping += [
            'bundleGemFile' => 'bundleGemFile',
            'sassDir' => 'sassDir',
            'cssDir' => 'cssDir',
        ];
        parent::initPropertyMapping();

        return $this;
    }
}
