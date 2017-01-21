<?php

namespace Cheppers\Robo\Drupal\ProjectType\Incubator;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use Cheppers\Robo\Drupal\Utils;
use function Stringy\create as s;

class ProjectConfig extends Base\ProjectConfig
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
     * @var \Cheppers\Robo\Drupal\Config\DrupalExtensionConfig[]
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
        return $this->baseHostName ?: s($this->id)->dasherize() . '.localhost';
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
     * {@inheritdoc}
     */
    public function populateDefaultValues()
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

        return parent::populateDefaultValues();
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
