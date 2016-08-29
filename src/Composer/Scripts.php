<?php

namespace Cheppers\Robo\Drupal\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

/**
 * Class Scripts.
 *
 * @package Cheppers\DcTester\Composer
 */
class Scripts
{
    /**
     * @param \Composer\Script\Event $event
     *
     * @return bool
     */
    public static function phpcsConfigSet(Event $event): int
    {
        if (!$event->isDevMode()) {
            $event->getIO()->write('To call "' . __METHOD__ . '" method is allowed only in "dev" mode.');

            return false;
        }

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
