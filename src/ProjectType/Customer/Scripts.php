<?php

namespace Sweetchuck\Robo\Drupal\ProjectType\Customer;

use Sweetchuck\Robo\Drupal\ProjectType\Base as Base;
use Sweetchuck\Robo\Drupal\ProjectType\Incubator as Incubator;
use Sweetchuck\Robo\Drupal\Utils;
use Sweetchuck\Robo\Drupal\VarExport;
use Robo\Robo;
use Stringy\StaticStringy;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class Scripts extends Base\Scripts
{
    /**
     * {@inheritdoc}
     */
    protected static $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Sweetchuck\Robo\Drupal\ProjectType\Customer\ProjectConfig
     */
    protected static $projectConfig = null;

    /**
     * @var string
     */
    protected static $inputSiteBranch = 'default';

    /**
     * @var string
     */
    protected static $inputSiteProfile = 'new';

    /**
     * @var string
     */
    protected static $inputSiteMachineNameLong = '';

    /**
     * @var string
     */
    protected static $inputSiteMachineNameShort = '';

    /**
     * @var string
     */
    protected static $inputThemeFrontendMachineName = '';

    /**
     * @var string
     */
    protected static $inputThemeBackendMachineName = '';

    /**
     * @var string
     */
    protected static $inputDatabaseUsername = '';

    /**
     * @var string
     */
    protected static $inputDatabasePassword = '';

    /**
     * {@inheritdoc}
     */
    protected static function oneTimeMain(): void
    {
        parent::oneTimeMain();
        static::projectConfigLocalCreate();
        static::siteCreate();
        static::themeCreate();
    }

    protected static function projectConfigLocalCreate(): void
    {
        $io = static::$event->getIO();

        $fileName = Utils::$projectConfigLocalFileName;
        if (static::$fs->exists($fileName)) {
            $io->write("The '<info>{$fileName}</info>' already exists", true);

            return;
        }

        $defaults = static::getDefaultMySQLConnection();

        static::$inputDatabaseUsername = $io->ask(
            static::ioAskQuestion('MySQL username', $defaults['username']),
            $defaults['username']
        );

        // @todo Hide password.
        static::$inputDatabasePassword = $io->ask(
            static::ioAskQuestion('MySQL password', $defaults['password']),
            $defaults['password']
        );

        $fileContent = "<?php\n\n";

        $tpl = "\$projectConfig->databaseServers[%s]->connectionLocal['%s'] = %s;\n";
        $usernameSafe = VarExport::string(static::$inputDatabaseUsername);
        $passwordSafe = VarExport::string(static::$inputDatabasePassword);
        foreach (static::$projectConfig->databaseServers as $dbId => $dbServerConfig) {
            $connection = $dbServerConfig->getConnection();
            if ($connection['driver'] === 'mysql' && empty($connection['username'])) {
                $dbIdSafe = VarExport::string($dbId);

                $fileContent .= "\n";

                $dbServerConfig->connectionLocal['username'] = $defaults['username'];
                $fileContent .= sprintf($tpl, $dbIdSafe, 'username', $usernameSafe);

                $dbServerConfig->connectionLocal['password'] = $defaults['password'];
                $fileContent .= sprintf($tpl, $dbIdSafe, 'password', $passwordSafe);

                if ($defaults['host'] && !Utils::isLocalhost($defaults['host'])) {
                    $dbServerConfig->connectionLocal['host'] = $defaults['host'];
                    $fileContent .= sprintf($tpl, $dbIdSafe, 'host', VarExport::string($defaults['host']));
                }

                if ($defaults['port'] !== Utils::getDefaultMysqlPort()) {
                    $dbServerConfig->connectionLocal['port'] = $defaults['port'];
                    $fileContent .= sprintf($tpl, $dbIdSafe, 'port', VarExport::number($defaults['port']));
                }
            }
        }

        // @todo Error handling.
        file_put_contents($fileName, $fileContent);

        static::$projectConfig = null;
        static::initProjectConfig();
    }

    protected static function siteCreate(): void
    {
        static::siteCreateInput();
        static::siteCreateMain();
    }

    protected static function siteCreateInput(): void
    {
        $defaults = static::getEnvVars([
            'siteBranch' => static::$inputSiteBranch,
            'siteProfile' => static::$inputSiteProfile,
            'siteMachineNameLong' => StaticStringy::underscored(static::$inputNewNameMachine),
            'siteMachineNameShort' => StaticStringy::underscored(static::$inputNewNameMachine),
        ]);

        $pc = static::$projectConfig;

        if ($defaults['siteBranch']) {
            $default = $defaults['siteBranch'];
        } else {
            $default = static::$inputSiteBranch;
        }
        static::$inputSiteBranch = static::$event->getIO()->askAndValidate(
            static::ioAskQuestion(
                'Name of the directory under DRUPAL_ROOT/sites/',
                $default,
                'drupal_site_dir'
            ),
            function (?string $input) {
                return static::validateSiteBranch($input);
            },
            static::$ioAttempts,
            $default
        );

        $profiles = static::ioSelectDrupalProfileChoices($pc->drupalRootDir, false);
        if ($defaults['siteProfile']) {
            $default = $defaults['siteProfile'];
        } elseif (isset($profiles[static::$inputSiteBranch])) {
            $default = static::$inputSiteBranch;
        } else {
            $default = static::$inputSiteProfile;
        }

        static::$inputSiteProfile = static::$event->getIO()->select(
            static::ioAskQuestion(
                'Select an installation profile',
                $default
            ),
            $profiles + ['new' => 'Create a new profile'],
            $default,
            static::$ioAttempts
        );

        if ($defaults['siteMachineNameLong']) {
            $default = $defaults['siteMachineNameLong'];
        } elseif (static::$inputSiteBranch !== 'default') {
            $default = static::$inputSiteBranch;
        } elseif (static::$inputSiteProfile === 'new') {
            $default = static::$inputNewNameMachine;
        } else {
            $default = static::$inputSiteProfile;
        }
        $default = preg_replace('/[\.-]+/', '_', $default);

        static::$inputSiteMachineNameLong = static::$event->getIO()->askAndValidate(
            static::ioAskQuestion(
                'Long version of the machine-name',
                $default,
                'drupal_extension'
            ),
            function (?string $input) {
                return static::validateDrupalExtensionMachineName($input, true);
            },
            static::$ioAttempts,
            $default
        );

        $default = $defaults['siteMachineNameShort'] ?: static::$inputSiteMachineNameLong;
        static::$inputSiteMachineNameShort = static::$event->getIO()->askAndValidate(
            static::ioAskQuestion(
                'Short version of the machine-name',
                $default,
                'drupal_extension'
            ),
            function (?string $input) {
                return static::validateDrupalExtensionMachineName($input, true);
            },
            static::$ioAttempts,
            $default
        );
    }

    protected static function siteCreateMain(): void
    {
        if (static::$inputSiteProfile === 'new') {
            static::$inputSiteProfile = static::$inputSiteMachineNameLong;
            static::newInstanceFromDrupalProfileCustomer(
                static::$projectConfig->drupalRootDir . '/profiles/custom',
                static::$inputSiteProfile
            );
        }

        $statusCode = Robo::run(
            [
                'my.php',
                'site:create',
                static::$inputSiteBranch,
                '--profile=' . static::$inputSiteProfile,
                "--long=" . static::$inputSiteMachineNameLong,
                '--short=' . static::$inputSiteMachineNameShort,
            ],
            [
                Incubator\RoboFile::class,
            ],
            'RoboDrupal',
            '0.0.0-alpha0'
        );

        if ($statusCode !== 0) {
            throw new \Exception('@todo');
        }

        static::$projectConfig = null;
        static::initProjectConfig();
    }

    protected static function themeCreate(): void
    {
        static::themeCreateInput();
        static::themeCreateMain();
    }

    protected static function themeCreateInput(): void
    {
        $defaults = static::getEnvVars([
            'themeFrontendMachineName' => null,
            'themeBackendMachineName' => null,
        ]);

        $default = $defaults['themeFrontendMachineName'] ?: static::$inputSiteMachineNameShort . 'f';
        static::$inputThemeFrontendMachineName = static::$event->getIO()->askAndValidate(
            static::ioAskQuestion(
                'Machine name of the front-end theme',
                $default,
                'drupal_extension'
            ),
            function (?string $input) {
                return static::validateDrupalExtensionMachineName($input, false);
            },
            static::$ioAttempts,
            $default
        );

        if ($defaults['themeBackendMachineName']) {
            $default = $defaults['themeBackendMachineName'];
        } elseif (preg_match('/f$/', static::$inputThemeFrontendMachineName)) {
            $default = preg_replace('/f$/', 'b', static::$inputThemeFrontendMachineName);
        } elseif (static::$inputThemeFrontendMachineName) {
            $default = static::$inputThemeFrontendMachineName . 'b';
        } else {
            $default = '';
        }
        static::$inputThemeBackendMachineName = static::$event->getIO()->askAndValidate(
            static::ioAskQuestion(
                'Machine name of the back-end theme',
                $default,
                'drupal_extension'
            ),
            function (?string $input) {
                return static::validateDrupalExtensionMachineName($input, false);
            },
            static::$ioAttempts,
            $default
        );
    }

    protected static function themeCreateMain(): void
    {
        $profileDir = static::$projectConfig->drupalRootDir . '/profiles/custom/' . static::$inputSiteProfile;
        $profileDirExists = static::$fs->exists($profileDir);

        if (static::$inputThemeFrontendMachineName) {
            static::themeCreateBasedOn(static::$inputThemeFrontendMachineName, 'bartik');
            if ($profileDirExists) {
                static::replaceThemeInProfileConfig(
                    $profileDir,
                    'bartik',
                    static::$inputThemeFrontendMachineName
                );
            }
        }

        if (static::$inputThemeBackendMachineName) {
            static::themeCreateBasedOn(static::$inputThemeBackendMachineName, 'seven');
            if ($profileDirExists) {
                static::replaceThemeInProfileConfig(
                    $profileDir,
                    'seven',
                    static::$inputThemeBackendMachineName
                );
            }
        }
    }

    protected static function themeCreateBasedOn(string $machineName, string $baseTheme): void
    {
        $dir = Path::join(static::$projectConfig->drupalRootDir, "themes/custom/$machineName");
        static::$fs->mkdir($dir);
        $info = [
            'core' => static::$drupalCoreVersionMain . '.x',
            'type' => 'theme',
            'package' => static::$inputNewNameMachine,
            'name' => $machineName,
            'version' => '1.0-dev',
            'base theme' => $baseTheme,
            'description' => 'Useless description',
            'alt text' => 'Useless alternate text',
        ];

        $baseThemeDir = static::drupalGetPath('theme', $baseTheme);
        if ($baseThemeDir) {
            $baseInfo = Yaml::parse(file_get_contents("$baseThemeDir/$baseTheme.info.yml"));
            $info['regions'] = $baseInfo['regions'] ?? [];
            $info['regions_hidden'] = $baseInfo['regions_hidden'] ?? [];

            $filesToCopy = [
                'logo.svg',
                'screenshot.png',
            ];
            foreach ($filesToCopy as $fileToCopy) {
                if (static::$fs->exists("$baseThemeDir/$fileToCopy")) {
                    static::$fs->copy("$baseThemeDir/$fileToCopy", "$dir/$fileToCopy");
                }
            }
        }

        $result = file_put_contents("$dir/$machineName.info.yml", Yaml::dump($info) . "\n");
        if ($result === false) {
            throw new \Exception('@todo');
        }

        $roboDrupalRoot = Utils::getRoboDrupalRoot();
        $files = (new Finder())
            ->in("$roboDrupalRoot/src/Templates/drupal/themes/common")
            ->files();
        $replacements = [
            'machine_name' => $machineName,
        ];
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $dstFileName = Path::join($dir, strtr($file->getRelativePathname(), $replacements));
            static::$fs->mkdir(Path::getDirectory($dstFileName));
            file_put_contents($dstFileName, strtr($file->getContents(), $replacements));
        }
    }

    protected static function replaceThemeInProfileConfig(string $profileDir, string $from, string $to): void
    {
        $files = (new Finder())
            ->in("$profileDir/config")
            ->name("*.yml");

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $oldName = $file->getPathname();
            $newName = str_replace($from, $to, $oldName);
            if ($oldName !== $newName) {
                static::$fs->rename($oldName, $newName);
            }
            file_put_contents(
                $newName,
                str_replace($from, $to, file_get_contents($newName))
            );
        }

        // @todo Be sure the $to is added to the "themes" list.
        $profile = basename($profileDir);
        $fileName = "$profileDir/$profile.info.yml";
        file_put_contents(
            $fileName,
            str_replace($from, $to, file_get_contents($fileName))
        );
    }

    /**
     * @todo Implement.
     */
    protected static function drupalGetPath(string $type, string $name): ?string
    {
        $siteId = static::$projectConfig->getDefaultSiteId();
        $drupalRoot = static::$projectConfig->drupalRootDir;
        $profile = static::$projectConfig->sites[$siteId]->installProfileName;
        $options = [
            "$drupalRoot/profiles/$profile/{$type}s/$name",
            "$drupalRoot/profiles/$profile/{$type}s/contrib/$name",
            "$drupalRoot/profiles/$profile/{$type}s/custom/$name",
            "$drupalRoot/{$type}s/$name",
            "$drupalRoot/{$type}s/contrib/$name",
            "$drupalRoot/{$type}s/custom/$name",
            "$drupalRoot/core/{$type}s/$name",
            "$drupalRoot/core/{$type}s/contrib/$name",
            "$drupalRoot/core/{$type}s/custom/$name",
        ];
        foreach ($options as $path) {
            if (static::$fs->exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
