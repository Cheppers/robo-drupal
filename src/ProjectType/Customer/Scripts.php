<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use Cheppers\Robo\Drupal\ProjectType\Incubator as Incubator;
use Robo\Robo;

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
     * {@inheritdoc}
     */
    protected static function oneTimeMain(): void
    {
        parent::oneTimeMain();
        static::siteCreate();
    }

    protected static function siteCreate(): void
    {
        static::siteCreateInput();
        static::siteCreateMain();
    }

    protected static function siteCreateInput(): void
    {
        if (!static::$event->getIO()->isInteractive()) {
            // @todo Provide default values or use the CLI arguments.
            return;
        }

        $pc = static::$projectConfig;

        $question = static::ioAskQuestion(
            'Name of the directory under DRUPAL_ROOT/sites/',
            static::$inputSiteBranch,
            'Only lower case letters, numbers and "._-" characters are allowed'
        );
        static::$inputSiteBranch = static::$event->getIO()->askAndValidate(
            $question,
            function (?string $input) {
                return static::validateSiteBranch($input);
            },
            static::$ioAttempts,
            static::$inputSiteBranch
        );

        $question = static::ioAskQuestion(
            'Select an installation profile',
            static::$inputSiteProfile
        );
        $profiles = static::ioSelectDrupalProfileChoices($pc->drupalRootDir, false);
        $profiles['new'] = 'Create a new profile';
        static::$inputSiteProfile = static::$event->getIO()->select(
            $question,
            $profiles,
            static::$inputSiteProfile,
            static::$ioAttempts
        );

        if (static::$inputSiteBranch !== 'default') {
            $default = static::$inputSiteBranch;
        } elseif (static::$inputSiteProfile === 'new') {
            $default = static::$inputNewNameMachine;
        } elseif (static::$inputSiteProfile !== 'standard') {
            $default = static::$inputSiteProfile;
        } else {
            $default = '';
        }
        $default = preg_replace('/[\.-]/', '_', $default);

        $question = static::ioAskQuestion(
            'Long version of the machine-name',
            $default
        );
        static::$inputSiteMachineNameLong = static::$event->getIO()->askAndValidate(
            $question,
            function (?string $input) {
                return static::validateDrupalExtensionMachineName($input);
            },
            static::$ioAttempts,
            $default
        );

        $default = static::$inputSiteMachineNameLong;
        $question = static::ioAskQuestion(
            'Short version of the machine-name',
            $default
        );
        static::$inputSiteMachineNameShort = static::$event->getIO()->askAndValidate(
            $question,
            function (?string $input) {
                return static::validateDrupalExtensionMachineName($input);
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
}
