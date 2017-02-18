<?php

namespace Cheppers\Robo\Drupal\Config;

class SiteConfig extends BaseConfig
{
    /**
     * URI of the Drupal site.
     *
     * @var string[]
     */
    public $urls = [];

    /**
     * Drush alias for the Drupal site without the leading @.
     *
     * @var string
     */
    public $drushAliasLocal;

    /**
     * Name of the install profile for site in $siteBranch.
     *
     * @var string
     */
    public $installProfileName;

    /**
     * List of module names to ignore during `drush config-{export|import}`.
     *
     * Key-value pair of module names and enabled/disabled status.
     *
     * @var bool[]
     */
    public $configSkipModules;

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();

        $this->propertyMapping['urls'] = 'urls';
        $this->propertyMapping['drushAliasLocal'] = 'drushAliasLocal';
        $this->propertyMapping['installProfileName'] = 'installProfileName';
        $this->propertyMapping['configSkipModules'] = 'configSkipModules';

        return $this;
    }
}
