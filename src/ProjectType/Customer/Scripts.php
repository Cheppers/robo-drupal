<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use Cheppers\Robo\Drupal\ProjectType\Incubator as Incubator;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Drupal\VarExport;
use Robo\Robo;
use Stringy\StaticStringy;

class Scripts extends Base\Scripts
{
    /**
     * {@inheritdoc}
     */
    protected static $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Customer\ProjectConfig
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

        $fileName = 'ProjectConfig.local.php';
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

        $fileContent = <<< 'PHP'
<?php

$projectConfig = $GLOBALS['projectConfig'];

PHP;

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
        file_put_contents(Utils::$projectConfigLocalFileName, $fileContent);

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

        if ($defaults['themeFrontendMachineName']) {
            $default = $defaults['themeFrontendMachineName'];
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
        if (!static::$inputThemeFrontendMachineName && !static::$inputThemeBackendMachineName) {
            return;
        }

        static::initPatternLab();
    }

    protected static function initPatternLab(): void
    {
        // @todo
    }
}
