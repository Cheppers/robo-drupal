<?php

namespace Cheppers\Robo\Drupal\Config;

/**
 * Class Site.
 *
 * @package Cheppers\Drupal\Project\Config
 */
class SiteConfig
{

    /**
     * Directory name.
     *
     * @var string
     */
    public $id = '';

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
}
