<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Cheppers\Robo\Drupal\Config\BaseConfig;
use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\Utils;

use Stringy\StaticStringy;

class ProjectConfig extends BaseConfig
{
    /**
     * @var string
     */
    public $baseHostName = '';

    /**
     * @var int
     */
    public $baseHostPort = 0;

    /**
     * @var string
     */
    public $siteVariantUrlPattern = '{php}.{db}.{siteBranch}.{baseHost}';

    /**
     * @var string
     */
    public $siteVariantDirPattern = '{siteBranch}.{db}';

    /**
     * @var string
     */
    public $defaultSiteId = '';

    /**
     * @var string
     */
    public $defaultDatabaseServer = '';

    /**
     * @var string
     */
    public $defaultPhpVariant = '';

    /**
     * @var bool[]
     */
    public $defaultDrupalTestSubjects = [];

    /**
     * @var \Cheppers\Robo\Drupal\Config\PhpVariantConfig[]
     */
    public $phpVariants = [];

    /**
     * @var \Cheppers\Robo\Drupal\Config\DatabaseServerConfig[]
     */
    public $databaseServers = [];

    /**
     * Reports directory.
     *
     * @var string
     */
    public $reportDir = 'report';

    /**
     * Drupal root.
     *
     * @var string
     */
    public $drupalRootDir = 'drupal_root';

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
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();
        $this->propertyMapping += [
            'baseHostName' => 'baseHostName',
            'baseHostPort' => 'baseHostPort',
            'composerExecutable' => 'composerExecutable',
            'databaseServers' => [
                'type' => 'subConfigs',
                'class' => DatabaseServerConfig::class,
            ],
            'defaultDatabaseServer' => 'defaultDatabaseServer',
            'defaultPhpVariant' => 'defaultPhpVariant',
            'defaultDrupalTestSubjects' => 'defaultDrupalTestSubjects',
            'defaultSiteId' => 'defaultSiteId',
            'drupalRootDir' => 'drupalRootDir',
            'environment' => 'environment',
            'gitExecutable' => 'gitExecutable',
            'outerSitesSubDir' => 'outerSitesSubDir',
            'phpVariants' => 'phpVariants',
            'reportDir' => 'reportDir',
            'sites' => [
                'type' => 'subConfigs',
                'class' => '\Cheppers\Robo\Drupal\Config\SiteConfig',
            ],
            'siteVariantDirPattern' => 'siteVariantDirPattern',
            'siteVariantUrlPattern' => 'siteVariantUrlPattern',
        ];

        return $this;
    }

    public function getDefaultSiteId(): string
    {
        if ($this->defaultSiteId) {
            if (!isset($this->sites[$this->defaultSiteId])) {
                trigger_error(
                    "The configured default site ID '{$this->defaultSiteId}' does not exists.",
                    E_USER_WARNING
                );
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

    public function getBaseHost(): string
    {
        $baseHost = $this->getBaseHostName();
        if (!Utils::isDefaultHttpPort($this->baseHostPort)) {
            $baseHost .= ":{$this->baseHostPort}";
        }

        return $baseHost;
    }

    public function getBaseHostName(): string
    {
        return $this->baseHostName ?: StaticStringy::dasherize($this->id) . '.localhost';
    }

    public function getProjectUrls(): array
    {
        $sites = [];
        foreach (array_keys($this->sites) as $sitesSubDir) {
            $sites += $this->getSiteBranchUrls($sitesSubDir);
        }

        asort($sites);

        return $sites;
    }

    public function getSiteBranchUrls(string $siteBranch): array
    {
        $urls = [];
        $placeholders = [
            '{php}' => null,
            '{db}' => null,
            '{siteBranch}' => $siteBranch,
        ];
        foreach (array_keys($this->phpVariants) as $php) {
            $placeholders['{php}'] = $php;
            foreach ($this->databaseServers as $db) {
                $placeholders['{db}'] = $db->id;
                $urls[$this->getSiteVariantUrl($placeholders)] = $this->getSiteVariantDir($placeholders);
            }
        }

        asort($urls);

        return $urls;
    }

    public function getSiteVariantUrl(array $placeholders): string
    {
        return $this->processPattern($this->siteVariantUrlPattern, $placeholders);
    }

    public function getSiteVariantDir(array $placeholders): string
    {
        return $this->processPattern($this->siteVariantDirPattern, $placeholders);
    }

    /**
     * @param \Cheppers\Robo\Drupal\Config\SiteConfig $siteId
     * @param \Cheppers\Robo\Drupal\Config\DatabaseServerConfig[] $dbConfigs
     * @param \Cheppers\Robo\Drupal\Config\PhpVariantConfig[] $phpVariants
     *
     * @return string[]
     */
    public function getSiteVariantDirs(SiteConfig $site, array $dbServerConfigs, array $phpVariants): array
    {
        $dirs = [];
        foreach ($phpVariants as $phpVariant) {
            foreach ($dbServerConfigs as $dbServerConfig) {
                $dir = $this->getSiteVariantDir([
                    '{siteBranch}' => $site->id,
                    '{php}' => $phpVariant->id,
                    '{db}' => $dbServerConfig->id,
                ]);
                $dirs[$dir] = $dir;
            }
        }

        return $dirs;
    }

    /**
     * @return $this
     */
    public function populateDefaultValues()
    {
        foreach ($this->sites as $id => $site) {
            $site->id = $id;
        }

        if (!$this->databaseServers) {
            $this->databaseServers['my'] = new DatabaseServerConfig(['driver' => 'mysql']);
        }

        foreach ($this->databaseServers as $id => $db) {
            $db->id = $id;
        }

        if (!$this->phpVariants) {
            $this->phpVariants[PHP_VERSION_ID] = new PhpVariantConfig(['binDir' => PHP_BINDIR]);
        }

        foreach ($this->phpVariants as $id => $php) {
            $php->id = $id;
        }

        return $this;
    }

    protected function processPattern(string $pattern, array $placeholders): string
    {
        $placeholders += [
            '{baseHost}' => $this->getBaseHost(),
        ];

        $result = strtr($pattern, $placeholders);

        // @todo The delimiter can be other than "." (dot).
        return trim(preg_replace('/\.{2,}/', '.', $result), '.');
    }
}
