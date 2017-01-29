<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\Robo\Drupal\ProjectType\Base;
use Cheppers\Robo\Drupal\ProjectType\Incubator;
use Composer\Script\Event;
use Robo\Robo;

class ScriptsOneTime extends Base\ScriptsOneTime
{
    /**
     * @var string
     */
    protected static $siteBranch = 'default';

    protected static $siteProfile = '';

    protected static $siteMachineNameLong = '';

    protected static $siteMachineNameShort = '';

    protected static function oneTimeMain(Event $event)
    {
        parent::oneTimeMain($event);
        static::siteCreate($event);
    }

    protected static function siteCreate(Event $event)
    {
        static::siteCreateInput($event);
        static::siteCreateMain($event);
    }

    protected static function siteCreateInput(Event $event)
    {
        $pc = static::getProjectConfig();

        $question = static::ioAskQuestion(
            'Name of the directory under DRUPAL_ROOT/sites/',
            static::$siteBranch,
            'Only lower case letters, numbers and "._-" characters are allowed'
        );
        static::$siteBranch = $event->getIO()->askAndValidate(
            $question,
            function (?string $input) {
                return static::validateSiteBranch($input);
            },
            3,
            static::$siteBranch
        );

        $question = static::ioAskQuestion(
            'Select an installation profile',
            static::$siteProfile
        );
        static::$siteProfile = $event->getIO()->select(
            $question,
            static::ioSelectDrupalProfileChoices($pc->drupalRootDir, false),
            static::$siteProfile,
            3
        );

        if (static::$siteBranch !== 'default') {
            $default = static::$siteBranch;
        } elseif (static::$siteProfile !== 'standard') {
            $default = static::$siteProfile;
        } else {
            $default = '';
        }

        $question = static::ioAskQuestion(
            'Long version of the machine-name',
            $default
        );
        static::$siteMachineNameLong = $event->getIO()->askAndValidate(
            $question,
            function (?string $input) {
                return static::validatePackageNameMachine($input);
            },
            3,
            $default
        );

        $default = static::$siteMachineNameLong;
        $question = static::ioAskQuestion(
            'Short version of the machine-name',
            $default
        );
        static::$siteMachineNameShort = $event->getIO()->askAndValidate(
            $question,
            function (?string $input) {
                return static::validatePackageNameMachine($input);
            },
            3,
            $default
        );
    }

    protected static function siteCreateMain(Event $event): void
    {
        $statusCode = Robo::run(
            [
                'my.php',
                'site:create',
                static::$siteBranch,
                '--profile=' . static::$siteProfile,
                "--long=" . static::$siteMachineNameLong,
                '--short=' . static::$siteMachineNameShort,
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

    protected static function validateSiteBranch($input)
    {
        // @todo
        return $input;
    }
}
