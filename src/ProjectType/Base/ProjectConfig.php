<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Cheppers\Robo\Drupal\Config\BaseConfig;

class ProjectConfig extends BaseConfig
{
    /**
     * Reports directory.
     *
     * @var string
     */
    public $reportsDir = 'reports';

    /**
     * Drupal root.
     *
     * @var string
     */
    public $drupalRootDir = 'drupal_root';

    /**
     * Public HTML directory.
     *
     * @var string
     */
    public $publicHtmlDir = 'public_html';

    /**
     * Root directory of "sites" directory outside of "$drupalRootDir".
     *
     * Relative path from the project root.
     *
     * @var string
     */
    public $outerSitesSubDir = 'sites';

    /**
     * Environment type. Allowed values are: dev, ci, prod.
     *
     * @var string
     */
    public $environment = 'dev';

    /**
     * Path to the Git executable.
     *
     * @var string
     */
    public $gitExecutable = 'git';

    /**
     * @var string
     */
    public $composerExecutable = 'composer';

    /**
     * @var \Cheppers\Robo\Drupal\Config\SiteConfig[]
     */
    public $sites = [];

    /**
     * @var string
     */
    public $defaultSiteId = '';

    public function getDefaultSiteId(): string
    {
        if ($this->defaultSiteId) {
            if (!isset($this->sites[$this->defaultSiteId])) {
                trigger_error("The configured default site ID '{$this->defaultSiteId}' does not exists.");
            } else {
                return $this->defaultSiteId;
            }
        }

        if (isset($this->sites['default'])) {
            return 'default';
        }

        $first = each($this->sites);

        return $first['key'] ?? '';
    }

    /**
     * @return $this
     */
    public function populateDefaultValues()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();
        $this->propertyMapping += [
            'reportsDir' => 'reportsDir',
            'drupalRootDir' => 'drupalRootDir',
            'publicHtmlDir' => 'publicHtmlDir',
            'outerSitesSubDir' => 'outerSitesSubDir',
            'environment' => 'environment',
            'gitExecutable' => 'gitExecutable',
            'composerExecutable' => 'composerExecutable',
            'sites' => [
                'type' => 'subConfigs',
                'class' => '\Cheppers\Robo\Drupal\Config\SiteConfig',
            ],
            'defaultSiteId' => 'defaultSiteId',
        ];

        return $this;
    }
}
