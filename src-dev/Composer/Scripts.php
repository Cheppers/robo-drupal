<?php

namespace Cheppers\Robo\Drupal\Composer;

use Cheppers\GitHooks\Main as GitHooksComposerScripts;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

class Scripts
{
    /**
     * @var \Composer\Script\Event
     */
    protected static $event;

    /**
     * @var \Closure
     */
    protected static $processCallbackWrapper;

    public static function postInstallCmd(Event $event): bool
    {
        static::init($event);
        GitHooksComposerScripts::deploy($event);
        static::phpcsConfigSet();
        static::yarnInstall();

        return true;
    }

    public static function postUpdateCmd(Event $event): bool
    {
        static::init($event);
        GitHooksComposerScripts::deploy($event);
        static::phpcsConfigSet();

        return true;
    }

    protected static function init(Event $event)
    {
        static::$event = $event;
        static::$processCallbackWrapper = function (string $type, string $buffer) {
            static::processCallback($type, $buffer);
        };
    }

    protected static function phpcsConfigSet(): bool
    {
        $cmdPattern = '%s --config-set installed_paths %s';
        /** @var \Composer\Config $config */
        $config = static::$event->getComposer()->getConfig();
        $cmdArgs = [
            escapeshellcmd($config->get('bin-dir') . '/phpcs'),
            escapeshellarg($config->get('vendor-dir') . '/drupal/coder/coder_sniffer'),
        ];

        $process = new Process(vsprintf($cmdPattern, $cmdArgs));
        $process->run(static::$processCallbackWrapper);

        return $process->getExitCode() === 0;
    }

    protected static function yarnInstall()
    {
        $cmd = 'yarn install';
        $process = new Process($cmd);
        $process->run(static::$processCallbackWrapper);

        return $process->getExitCode() === 0;
    }

    protected static function processCallback(string $type, string $buffer)
    {
        if ($type === Process::OUT) {
            static::$event->getIO()->write($buffer);
        } else {
            static::$event->getIO()->writeError($buffer);
        }
    }
}
