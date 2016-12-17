<?php

namespace Cheppers\Robo\Drupal\Config;

use Cheppers\Robo\Drupal\Utils;
use function Stringy\create as s;

/**
 * This class is only for IDE completion helper purposes.
 *
 * @todo Check that there is only one default database.
 *
 * @package Cheppers\Robo\Drupal\Config
 */
class ProjectIncubatorConfig
{
    /**
     * Project machine-name.
     *
     * @var string
     */
    public $name = '';

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
     * Environment type. Allowed values are: dev, ci, prod.
     *
     * @var string
     */
    public $environment = 'dev';

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
     * @var string
     */
    public $releaseDir = 'release';

    /**
     * @var string
     */
    public $releaseGitRemote = 'upstream';

    /**
     * @var string
     */
    public $releaseGitBranchRemote = 'production';

    /**
     * @var string
     */
    public $releaseGitBranchLocal = 'production';

    /**
     * @var \Cheppers\Robo\Drupal\Config\SiteConfig
     */
    public $siteDefaults = [];

    /**
     * Root directory of "sites" directory outside of "$drupalRootDir".
     *
     * Relative path from the project root.
     *
     * @var string
     */
    public $outerSitesSubDir = 'sites';

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
     * @var \Cheppers\Robo\Drupal\Config\SiteConfig[]
     */
    public $sites = [];

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
     * @var \Cheppers\Robo\Drupal\Config\PhpVariantConfig[]
     */
    public $phpVariants = [];

    /**
     * @var \Cheppers\Robo\Drupal\Config\DatabaseServerConfig[]
     */
    public $databaseServers = [];

    /**
     * One of: development (default), production.
     *
     * @var string
     */
    public $compassEnvironment = 'production';

    /**
     * @var bool
     */
    public $autodetectManagedDrupalExtensions = true;

    /**
     * @var bool[]
     */
    public $managedDrupalExtensions = [];

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
        return $this->baseHostName ?: s($this->name)->dasherize() . '.localhost';
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

    public function populateDefaultValues(): void
    {
        foreach ($this->sites as $id => $site) {
            $site->id = $id;
        }

        foreach ($this->databaseServers as $id => $db) {
            $db->id = $id;
        }

        foreach ($this->phpVariants as $id => $php) {
            $php->id = $id;
        }
    }

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

        return $first ? $first['key'] : '';
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
