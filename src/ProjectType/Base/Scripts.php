<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Cheppers\GitHooks\Main as GitHooksComposerScripts;
use Composer\Script\Event;
use DrupalComposer\DrupalScaffold\Plugin;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Scripts
{
    /**
     * @var ProjectConfig
     */
    protected static $projectConfig = null;

    protected static $baseNamespace = '\Cheppers\Robo\Drupal\ProjectType\Base';

    protected static function getBaseNamespace(): string
    {
        return static::$baseNamespace;
    }

    /**
     * Composer script event handler.
     */
    public static function postInstallCmd(Event $event): bool
    {
        static::initProjectConfig();
        static::buildScaffold($event);
        static::createRequiredFiles($event);
        static::phpcsConfigSet($event);
        GitHooksComposerScripts::deploy($event);

        return true;
    }

    /**
     * Composer script event handler.
     */
    public static function postUpdateCmd(Event $event): bool
    {
        static::initProjectConfig();
        static::buildScaffold($event);
        static::createRequiredFiles($event);
        static::phpcsConfigSet($event);
        GitHooksComposerScripts::deploy($event);

        return true;
    }

    protected static function initProjectConfig(): void
    {
        if (static::$projectConfig) {
            return;
        }

        $rootDir = getcwd();
        if (file_exists("$rootDir/ProjectConfig.php")) {
            require_once "$rootDir/ProjectConfig.php";
            if (!empty($GLOBALS['projectConfig'])) {
                static::$projectConfig = $GLOBALS['projectConfig'];

                return;
            }
        }

        $class = static::getBaseNamespace() . '\\ProjectConfig';

        static::$projectConfig = new $class();
    }

    /**
     * Trigger the main scaffold.
     */
    protected static function buildScaffold(Event $event): bool
    {
        $fs = new Filesystem();
        if (!$fs->exists(static::$projectConfig->drupalRootDir . '/autoload.php')) {
            Plugin::scaffold($event);
        }

        return true;
    }

    /**
     * Initialize the untracked files and directories.
     */
    protected static function createRequiredFiles(Event $event): bool
    {
        $fs = new Filesystem();
        $dirsToCreate = [
            '.' => [
                'sites/all/translations',
            ],
            static::$projectConfig->drupalRootDir => [
                'profiles',
                'modules',
                'themes',
                'libraries',
            ],
        ];
        foreach ($dirsToCreate as $root => $dirs) {
            foreach ($dirs as $dir) {
                $dir = "$root/$dir";
                if (!$fs->exists($dir)) {
                    $fs->mkdir($dir);
                    $event->getIO()->write("Create a '$dir' directory");
                }
            }
        }

        return true;
    }

    protected static function phpcsConfigSet(Event $event): bool
    {
        $cmdPattern = '%s --config-set installed_paths %s';
        /** @var \Composer\Config $config */
        $config = $event->getComposer()->getConfig();
        $cmdArgs = [
            escapeshellcmd($config->get('bin-dir') . '/phpcs'),
            escapeshellarg($config->get('vendor-dir') . '/drupal/coder/coder_sniffer'),
        ];

        $process = new Process(vsprintf($cmdPattern, $cmdArgs));
        $process->run();
        $event->getIO()->write($process->getOutput(), false);
        $event->getIO()->write($process->getErrorOutput(), false);

        return $process->getExitCode() === 0;
    }
}
