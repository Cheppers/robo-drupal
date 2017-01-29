<?php

namespace Cheppers\Robo\Drupal\Robo\Task;

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Drupal\VarExport;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Result;
use Robo\Task\BaseTask;
use Robo\TaskAccessor;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

use function Stringy\create as s;

class SiteCreateTask extends BaseTask implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use TaskAccessor;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs = null;

    /**
     * @var string
     */
    protected $projectRootDir = '.';

    public function getProjectRootDir(): string
    {
        return $this->projectRootDir;
    }

    /**
     * @param string $projectRootDir
     *
     * @return $this
     */
    public function setProjectRootDir(string $projectRootDir)
    {
        $this->projectRootDir = $projectRootDir ?: '.';

        return $this;
    }

    /**
     * @var string[]
     */
    protected $reservedSiteBranchNames = [
        'all',
        'simpletest',
    ];

    /**
     * @var string
     */
    protected $settingsPhp = '';

    /**
     * @var string
     */
    protected $localSettingsPhp = '';

    /**
     * @var string
     */
    protected $projectConfigPhp = '';

    /**
     * @var string
     */
    protected $sitesPhp = '';

    //region siteBranch
    /**
     * @var string
     */
    protected $siteBranch = '';

    public function getSiteBranch(): string
    {
        return $this->siteBranch;
    }

    /**
     * @param string $siteBranch
     *
     * @return $this
     */
    public function setSiteBranch(string $siteBranch)
    {
        $this->siteBranch = $siteBranch;

        return $this;
    }
    //endregion

    //region installProfile
    /**
     * @var string
     */
    protected $installProfile = 'standard';

    public function getInstallProfile(): string
    {
        return $this->installProfile;
    }

    /**
     * @param string $installProfile
     *
     * @return $this
     */
    public function setInstallProfile(string $installProfile)
    {
        $this->installProfile = $installProfile;

        return $this;
    }
    //endregion

    //region machineNameLong
    /**
     * @var string
     */
    protected $machineNameLong = '';

    public function getMachineNameLong(): string
    {
        if ($this->machineNameLong) {
            return $this->machineNameLong;
        }

        return $this->getInstallProfile() ?: 'my_project_1';
    }

    /**
     * @param string $machineNameLong
     *
     * @return $this
     */
    public function setMachineNameLong(string $machineNameLong)
    {
        $this->machineNameLong = $machineNameLong;

        return $this;
    }
    //endregion

    //region machineNameShort
    /**
     * @var string
     */
    protected $machineNameShort = '';

    public function getMachineNameShort(): string
    {
        return $this->machineNameShort ?: $this->getMachineNameLong();
    }

    /**
     * @param string $machineNameShort
     *
     * @return $this
     */
    public function setMachineNameShort(string $machineNameShort)
    {
        $this->machineNameShort = $machineNameShort;

        return $this;
    }
    //endregion

    //region projectConfig
    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig
     */
    protected $projectConfig = null;

    public function getProjectConfig(): ProjectConfig
    {
        return $this->projectConfig;
    }

    /**
     * @param \Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig $projectConfig
     *
     * @return $this
     */
    public function setProjectConfig(ProjectConfig $projectConfig)
    {
        $this->projectConfig = $projectConfig;

        return $this;
    }
    //endregion

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'projectRootDir':
                    $this->setProjectRootDir($value);
                    break;

                case 'siteBranch':
                    $this->setSiteBranch($value);
                    break;

                case 'installProfile':
                    $this->setInstallProfile($value);
                    break;

                case 'machineNameLong':
                    $this->setMachineNameLong($value);
                    break;

                case 'machineNameShort':
                    $this->setMachineNameShort($value);
                    break;

                case 'projectConfig':
                    $this->setProjectConfig($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(): Result
    {
        $this->fs = new Filesystem();

        $pc = $this->getProjectConfig();
        $siteBranch = $this->getSiteBranch();

        $exitCode = 0;
        $message = '';
        try {
            $this
                ->validateSiteBranch()
                ->readProjectConfigPhp()
                ->readSitesPhp()
                ->doAddSite();

            $sites = array_unique($pc->getSiteBranchUrls($siteBranch));
            $this->validateSiteDirs($sites);
            foreach ($pc->databaseServers as $db) {
                $this->createSiteVariant($db);
            }

            $this
                ->dumpSitesPhp()
                ->dumpProjectConfigPhp();
        } catch (\Exception $e) {
            $exitCode = max(1, $e->getCode());
            $message = $e->getMessage();
        }

        return new Result($this, $exitCode, $message);
    }

    /**
     * @return $this
     */
    protected function validateSiteBranch()
    {
        $siteBranch = $this->getSiteBranch();
        if (!preg_match('/^[0-9a-zA-Z_-]+$/', $siteBranch)) {
            throw new \InvalidArgumentException("The given site name is invalid: $siteBranch", 1);
        }

        return $this;
    }

    /**
     * @todo More strict validations.
     *
     * @return $this
     */
    protected function validateSiteDirs(array $siteDirs)
    {
        $pc = $this->getProjectConfig();
        if (!$pc) {
            throw new \InvalidArgumentException('Invalid project config', 1);
        }

        $siteBranch = $this->getSiteBranch();
        if (!$siteBranch || in_array($siteBranch, $this->reservedSiteBranchNames)) {
            throw new \InvalidArgumentException("Invalid siteBranch: '$siteBranch'", 1);
        }

        foreach ($siteDirs as $siteDir) {
            $paths = [
                "{$this->projectRootDir}/{$pc->outerSitesSubDir}/{$siteDir}",
                "{$this->projectRootDir}/{$pc->drupalRootDir}/sites/{$siteDir}",
            ];

            if ($siteDir === 'default') {
                $paths[1] .= '/settings.php';
            }

            foreach ($paths as $path) {
                if ($this->fs->exists($path)) {
                    throw new \Exception("Already exists: '$path'", 1);
                }
            }
        }

        return $this;
    }

    /**
     * @param \Cheppers\Robo\Drupal\Config\DatabaseServerConfig $db
     *
     * @return $this
     */
    protected function createSiteVariant(DatabaseServerConfig $db)
    {
        $placeholders = [
            '{db}' => $db->id,
            '{siteBranch}' => $this->getSiteBranch(),
        ];
        $siteDir = $this->getProjectConfig()->getSiteVariantDir($placeholders);

        $this
            ->readSettingsPhp()
            ->createSiteDir($siteDir)
            ->doDatabases($siteDir, $db)
            ->doHashSalt($siteDir)
            ->doServicesYml($siteDir)
            ->doConfigDirectories($siteDir)
            ->doInstallProfile()
            ->doIncludeLocalSettingsPhp()
            ->doFilePublicPath($siteDir)
            ->doFilePrivatePath($siteDir)
            ->doFileTemporaryPath($siteDir)
            ->doTranslationsPath()
            ->doFieldUiPrefix()
            ->doMiscConfigOverrides()
            ->doDevelopmentSettings()
            ->doTrustedHostPatterns($siteDir)
            ->dumpSettingsPhp($siteDir);

        return $this;
    }

    /**
     * @return $this
     */
    protected function readSettingsPhp()
    {
        $pc = $this->getProjectConfig();
        $sitesDir = Path::join($this->projectRootDir, $pc->drupalRootDir, 'sites');

        $this->settingsPhp = file_get_contents("{$sitesDir}/default/default.settings.php");
        $this->localSettingsPhp = file_get_contents("{$sitesDir}/example.settings.local.php");

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function dumpSettingsPhp(string $siteDir)
    {
        $pc = $this->getProjectConfig();

        $fullSiteDir = "{$this->projectRootDir}/{$pc->drupalRootDir}/sites/$siteDir";

        return $this
            ->filePutContent("$fullSiteDir/settings.php", $this->settingsPhp)
            ->filePutContent("$fullSiteDir/settings.local.php", $this->localSettingsPhp);
    }

    /**
     * @return $this
     */
    protected function readProjectConfigPhp()
    {
        // @todo Error handling if the file doesn't exists.
        $fileName = Path::join($this->projectRootDir, Utils::$projectConfigFileName);
        $this->projectConfigPhp = file_get_contents($fileName);

        return $this;
    }

    /**
     * @return $this
     */
    protected function dumpProjectConfigPhp()
    {
        $fileName = Path::join($this->projectRootDir, Utils::$projectConfigFileName);

        return $this->filePutContent($fileName, $this->projectConfigPhp);
    }

    /**
     * @return $this
     */
    protected function readSitesPhp()
    {
        $pc = $this->getProjectConfig();
        $sitesDir = Path::join($this->projectRootDir, $pc->drupalRootDir, 'sites');
        $exampleSitesPhp = Path::join($sitesDir, 'example.sites.php');
        $sitesPhp = Path::join($sitesDir, 'sites.php');
        if (file_exists($sitesPhp)) {
            $this->sitesPhp = file_get_contents($sitesPhp);
        } elseif (file_exists($exampleSitesPhp)) {
            $this->sitesPhp = file_get_contents($exampleSitesPhp);
        } else {
            $this->sitesPhp = "<?php\n\n";
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function dumpSitesPhp()
    {
        $pc = $this->getProjectConfig();
        $fileName = Path::join($this->projectRootDir, $pc->drupalRootDir, 'sites', 'sites.php');

        return  $this->filePutContent($fileName, $this->sitesPhp);
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doTrustedHostPatterns(string $siteDir)
    {
        $pc = $this->getProjectConfig();
        $urls = $pc->getSiteBranchUrls($this->getSiteBranch());
        $patterns = [];
        foreach (array_keys($urls, $siteDir) as $url) {
            $patterns[] = '^' . preg_quote($url) . '$';
        }
        $patternsSafe = VarExport::map($patterns, 0, '  ');
        $this->localSettingsPhp .= <<< PHP

\$settings['trusted_host_patterns'] = $patternsSafe;

PHP;

        return $this;
    }

    /**
     * @return $this
     */
    protected function doDevelopmentSettings()
    {
        $search = "\n\$settings['extension_discovery_scan_tests'] = TRUE;\n";
        $replace = "\n\$settings['extension_discovery_scan_tests'] = FALSE;\n";
        Utils::manipulateString($this->localSettingsPhp, $search, $replace);

        $search = "\n\$config['system.performance']['js']['preprocess'] = FALSE;\n";
        $replace = <<< 'PHP'
$config['system.performance']['css.gzip'] = TRUE;
$config['system.performance']['js.gzip'] = TRUE;
$config['system.performance']['response.gzip'] = TRUE;

PHP;
        Utils::manipulateString($this->localSettingsPhp, $search, $replace, 'after');

        $fileMask = '0' . decoct(0666 - umask());
        $dirMask = '0' . decoct(0777 - umask());
        $this->localSettingsPhp .= <<< PHP

\$settings['file_chmod_directory'] = $dirMask;
\$settings['file_chmod_file'] = $fileMask;

\$config['system.logging']['error_level'] = 'verbose';

\$config['devel.settings']['error_handlers'] = [4 => 4];
\$config['devel.settings']['dumper'] = 'kint';

\$config['views.settings']['ui']['show']['advanced_column'] = TRUE;
\$config['views.settings']['ui']['show']['sql_query']['enabled'] = TRUE;

PHP;

        return $this;
    }

    /**
     * @return $this
     */
    protected function doMiscConfigOverrides()
    {
        $search = "\n/**\n * Fast 404 pages:\n";
        $replace = <<< 'PHP'

$config['views.settings']['ui']['exposed_filter_any_label'] = 'new_any';

PHP;
        Utils::manipulateString($this->settingsPhp, $search, $replace, 'before');

        return $this;
    }

    /**
     * @return $this
     */
    protected function doFieldUiPrefix()
    {
        $fieldPrefix = $this->getMachineNameShort();
        $search = "\n/**\n * Fast 404 pages:\n";
        $replace = "\n\$config['field_ui.settings']['field_prefix'] = '{$fieldPrefix}_';\n";
        Utils::manipulateString($this->settingsPhp, $search, $replace, 'before');

        return $this;
    }

    /**
     * @return $this
     */
    protected function doTranslationsPath()
    {
        $pc = $this->getProjectConfig();

        $filePath = "{$pc->outerSitesSubDir}/all/translations";
        $filePathSafe = VarExport::string("../{$filePath}");
        $filePathFull = "{$this->projectRootDir}/$filePath";

        $search = "\n/**\n * Fast 404 pages:\n";
        $replace = "\n\$config['locale.settings']['translation']['path'] = {$filePathSafe};\n";
        Utils::manipulateString($this->settingsPhp, $search, $replace, 'before');

        if (!$this->fs->exists($filePathFull)) {
            $this->fs->mkdir($filePathFull, 0777 - umask());
        }

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doFileTemporaryPath(string $siteDir)
    {
        $pc = $this->getProjectConfig();

        $filePath = "{$pc->outerSitesSubDir}/$siteDir/temporary";
        $filePathSafe = VarExport::string("../{$filePath}");
        $filePathFull = "{$this->projectRootDir}/$filePath";

        $search = <<< PHP

/**
 * Session write interval:
PHP;

        $replace = "\n\$settings['file_temporary_path'] = {$filePathSafe};\n";

        Utils::manipulateString($this->settingsPhp, $search, $replace, 'before');

        $this->fs->mkdir($filePathFull);

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doFilePrivatePath(string $siteDir)
    {
        $pc = $this->getProjectConfig();

        $filePath = "{$pc->outerSitesSubDir}/$siteDir/private";
        $filePathSafe = VarExport::string("../{$filePath}");
        $filePathFull = "{$this->projectRootDir}/$filePath";

        $search = "\n# \$settings['file_private_path'] = '';\n";
        $replace = "\n\$settings['file_private_path'] = {$filePathSafe};\n";

        Utils::manipulateString($this->settingsPhp, $search, $replace);

        $this->fs->mkdir($filePathFull);

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doFilePublicPath(string $siteDir)
    {
        $pc = $this->getProjectConfig();

        $filePath = "sites/{$siteDir}/files";
        $filePathSafe = VarExport::string($filePath);
        $filePathFull = "{$this->projectRootDir}/{$pc->drupalRootDir}/$filePath";

        $search = "\n# \$settings['file_public_path'] = 'sites/default/files';\n";
        $replace = "\n\$settings['file_public_path'] = {$filePathSafe};\n";
        Utils::manipulateString($this->settingsPhp, $search, $replace);

        $this->fs->mkdir($filePathFull);

        return $this;
    }

    /**
     * @return $this
     */
    protected function doIncludeLocalSettingsPhp()
    {
        $search = <<< PHP

# if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {
#   include \$app_root . '/' . \$site_path . '/settings.local.php';
# }

PHP;
        $replace = <<< PHP

if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {
  include \$app_root . '/' . \$site_path . '/settings.local.php';
}

PHP;

        Utils::manipulateString($this->settingsPhp, $search, $replace);

        return $this;
    }

    /**
     * @return $this
     */
    protected function doInstallProfile()
    {
        $installProfile = $this->getInstallProfile();
        $installProfileSafe = VarExport::string($installProfile);

        $search = "\n# \$settings['install_profile'] = '';\n";
        $replace = <<< PHP

\$settings['install_profile'] = {$installProfileSafe};

PHP;

        Utils::manipulateString($this->settingsPhp, $search, $replace);

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doConfigDirectories(string $siteDir)
    {
        $pc = $this->getProjectConfig();
        $search = "\n\$config_directories = array();\n";

        $configSyncDir = "{$pc->outerSitesSubDir}/$siteDir/config/sync";
        $configSyncDirSafe = VarExport::string("../$configSyncDir");
        $configSyncDirFull = "{$this->projectRootDir}/$configSyncDir";

        $replace = <<< PHP

\$config_directories = [
  CONFIG_SYNC_DIRECTORY => {$configSyncDirSafe},
];

PHP;
        Utils::manipulateString($this->settingsPhp, $search, $replace);

        $this->fs->mkdir($configSyncDirFull);

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doServicesYml(string $siteDir)
    {
        $pc = $this->getProjectConfig();
        $dirPrefix = "{$this->projectRootDir}/{$pc->drupalRootDir}/sites";
        $src = "$dirPrefix/default/default.services.yml";

        if (file_exists($src)) {
            $dst = "$dirPrefix/$siteDir/services.yml";
            $this->fs->copy($src, $dst, true);
        }

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function doHashSalt(string $siteDir)
    {
        $pc = $this->getProjectConfig();

        $hashSaltFileName = "{$pc->outerSitesSubDir}/$siteDir/hash_salt.txt";
        $hashSaltFileNameSafe = VarExport::string("../{$hashSaltFileName}");
        $hashSaltFileNameFull = "{$this->projectRootDir}/$hashSaltFileName";

        Utils::manipulateString(
            $this->settingsPhp,
            "\n\$settings['hash_salt'] = '';\n",
            "\n\$settings['hash_salt'] = file_get_contents({$hashSaltFileNameSafe});\n"
        );

        return $this->filePutContent($hashSaltFileNameFull, bin2hex(openssl_random_pseudo_bytes(rand(12, 36))));
    }

    /**
     * @param string $siteDir
     * @param \Cheppers\Robo\Drupal\Config\DatabaseServerConfig $db
     *
     * @return $this
     */
    protected function doDatabases(string $siteDir, DatabaseServerConfig $db)
    {
        $pc = $this->getProjectConfig();
        $siteBranch = $this->getSiteBranch();

        $connection = $db->connection;

        // @todo The $connection['database'] could be a pattern.
        if ($connection['driver'] === 'sqlite') {
            // @todo Create the directory.
            $dbDir = $this->getSqliteDbDir($siteDir);
            $this->fs->mkdir($dbDir, 0777 - umask());
            $connection['database'] = "$dbDir/default__default.sqlite";
        } else {
            $connection['database'] = implode('__', [
                s($pc->id)->underscored(),
                s($siteBranch)->underscored(),
                'dev',
            ]);
        }

        $connectionUsername = null;
        $connectionPassword = null;
        if ($db->authenticationMethod === 'user:pass') {
            $connection += ['username' => '', 'password' => ''];
        }

        $connectionSafe = VarExport::map($connection, 2, '  ');

        $search = "\n \$databases = array();\n";
        $replace = <<< PHP

\$databases = [
  'default' => [
    'default' => {$connectionSafe},
  ],
];

PHP;

        Utils::manipulateString($this->settingsPhp, $search, $replace);

        $replace = [];
        foreach ($db->connectionLocal as $key => $value) {
            $keySafe = VarExport::string($key);
            $valueSafe = VarExport::string($value);
            $replace[] = "\$databases['default']['default'][$keySafe] = $valueSafe;";
        }

        if ($replace) {
            $search = "\n/**\n * Assertions.";
            Utils::manipulateString(
                $this->localSettingsPhp,
                $search,
                "\n" . implode("\n", $replace) . "\n",
                'before'
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function doAddSite()
    {
        $pc = $this->getProjectConfig();

        $siteBranch = $this->getSiteBranch();
        $siteBranchSafe = VarExport::string($siteBranch);

        $installProfile = $this->getInstallProfile();
        $installProfileSafe = VarExport::string($installProfile);

        $urls = $pc->getSiteBranchUrls($siteBranch);

        $pc->sites[$siteBranch] = new SiteConfig();
        $pc->sites[$siteBranch]->id = $siteBranch;
        $pc->sites[$siteBranch]->installProfileName = $installProfile;
        $pc->sites[$siteBranch]->urls = $urls;

        $urlsSafe = VarExport::map($pc->sites[$siteBranch]->urls, 1, '  ');

        $search = "\n  if (file_exists(__DIR__ . '/ProjectConfig.local.php')) {\n";
        $replace = <<< PHP

  \$projectConfig->sites[{$siteBranchSafe}] = new SiteConfig();
  \$projectConfig->sites[{$siteBranchSafe}]->id = {$siteBranchSafe};
  \$projectConfig->sites[{$siteBranchSafe}]->installProfileName = {$installProfileSafe};
  \$projectConfig->sites[{$siteBranchSafe}]->urls = {$urlsSafe};

PHP;
        Utils::manipulateString($this->projectConfigPhp, $search, $replace, 'before');

        return $this;
    }

    /**
     * @param string $siteDir
     *
     * @return $this
     */
    protected function createSiteDir(string $siteDir)
    {
        $pc = $this->getProjectConfig();
        $this->fs->mkdir("{$this->projectRootDir}/{$pc->drupalRootDir}/sites/{$siteDir}");
        $this->fs->mkdir("{$this->projectRootDir}/{$pc->outerSitesSubDir}/{$siteDir}");

        return $this;
    }

    /**
     * @param string $fileName
     * @param string $data
     *
     * @return $this
     */
    protected function filePutContent(string $fileName, string $data)
    {
        $result = file_put_contents($fileName, $data);
        if ($result === false) {
            throw new \Exception("Failed to write data to file '$fileName'");
        }

        return $this;
    }

    protected function getSqliteDbDir(string $siteDir): string
    {
        return Path::join(
            $this->projectRootDir,
            $this->projectConfig->outerSitesSubDir,
            $siteDir,
            'db'
        );
    }
}
