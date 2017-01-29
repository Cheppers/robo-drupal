<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Composer\Script\Event;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ScriptsOneTime
{
    protected static $packageFileName = '';

    protected static $package = [];

    protected static $oldVendorMachine = '';

    protected static $oldVendorNamespace = '';

    protected static $oldNameMachine = '';

    protected static $oldNameNamespace = '';

    protected static $newVendorMachine = '';

    protected static $newVendorNamespace = '';

    protected static $newNameMachine = '';

    protected static $newNameNamespace = '';

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig
     */
    protected static $projectConfig = null;

    protected static function getProjectConfig(): ProjectConfig
    {
        if (!static::$projectConfig) {
            if (file_exists('ProjectConfig.php')) {
                require_once 'ProjectConfig.php';
            }

            static::$projectConfig = $GLOBALS['projectConfig'] ?? new ProjectConfig();
        }

        return static::$projectConfig;
    }

    public static function oneTime(Event $event): bool
    {
        static::oneTimePre($event);
        static::oneTimeMain($event);
        static::oneTimePost($event);

        return true;
    }

    protected static function oneTimePre(Event $event)
    {
        static::$packageFileName = getcwd() . '/composer.json';

        static::packageRead();
        static::removeOneTimeScript();
        static::renamePackage($event);
    }

    protected static function oneTimeMain(Event $event)
    {
    }

    protected static function oneTimePost(Event $event)
    {
        static::packageDump();
        static::composerDumpAutoload();
        static::composerUpdate();
    }

    protected static function packageRead(): void
    {
        static::$package = json_decode(file_get_contents(static::$packageFileName), true);
        list(static::$oldVendorMachine, static::$oldNameMachine) = explode('/', static::$package['name']);
        $oldNamespace = array_search('src/', static::$package['autoload']['psr-4']);
        list(static::$oldVendorNamespace, static::$oldNameNamespace) = explode('\\', $oldNamespace);
    }

    protected static function packageDump(): void
    {
        file_put_contents(
            static::$packageFileName,
            json_encode(
                static::$package,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) . "\n"
        );
    }

    protected static function removeOneTimeScript(): void
    {
        array_pop(static::$package['scripts']['post-install-cmd']);
        $fileName = getcwd() . '/src/Composer/ScriptsOneTime.php';
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    protected static function renamePackage(Event $event): void
    {
        static::renamePackageInput($event);
        static::renamePackageComposer($event);

        $event->getIO()->write(
            sprintf('The new package name is "<info>%s</info>"', static::$package['name']),
            true
        );
    }

    protected static function renamePackageInput(Event $event): void
    {
        $cwdParts = explode('/', getcwd());
        $defaultNewNameMachine = array_pop($cwdParts);
        $defaultNewVendorMachine = array_pop($cwdParts);

        $questionPatternMachine = implode("\n", [
           '<question>Rename the package (%d/4) - %s:</question>',
           '<question>Only lower case letters, numbers and "-" are allowed</question>',
           'Default: "<info>%s</info>"',
           '',
        ]);

        $questionPatternNamespace = implode("\n", [
           '<question>Rename the package (%d/4) - %s:</question>',
           '<question>Capital camel case format is allowed</question>',
           'Default: "<info>%s</info>"',
           '',
        ]);

        if ($event->getIO()->isInteractive()) {
            static::$newVendorMachine = $event->getIO()->askAndValidate(
                sprintf(
                    $questionPatternMachine,
                    1,
                    'vendor as machine-name',
                    $defaultNewVendorMachine
                ),
                function (?string $input) {
                    return static::validatePackageNameMachine($input);
                },
                3,
                $defaultNewVendorMachine
            );

            static::$newVendorNamespace = $event->getIO()->askAndValidate(
                sprintf(
                    $questionPatternNamespace,
                    2,
                    'vendor as namespace',
                    static::capitalCamelCase(static::$newVendorMachine)
                ),
                function (?string $input) {
                    return static::validatePackageNameNamespace($input);
                },
                3,
                static::capitalCamelCase(static::$newVendorMachine)
            );

            static::$newNameMachine = $event->getIO()->askAndValidate(
                sprintf(
                    $questionPatternMachine,
                    3,
                    'name as machine-name',
                    $defaultNewNameMachine
                ),
                function (?string $input) {
                    return static::validatePackageNameMachine($input);
                },
                3,
                $defaultNewNameMachine
            );

            static::$newNameNamespace = $event->getIO()->askAndValidate(
                sprintf(
                    $questionPatternNamespace,
                    4,
                    'name as namespace',
                    static::capitalCamelCase(static::$newNameMachine)
                ),
                function (?string $input) {
                    return static::validatePackageNameNamespace($input);
                },
                3,
                static::capitalCamelCase(static::$newNameMachine)
            );
        }
    }

    protected static function renamePackageComposer(Event $event): void
    {
        $oldNamespace = static::$oldVendorNamespace . '\\' . static::$oldNameNamespace . '\\';
        $newNamespace = static::$newVendorNamespace . '\\' . static::$newNameNamespace . '\\';

        static::$package['name'] = static::$newVendorMachine . '/' . static::$newNameMachine;

        unset(static::$package['autoload']['psr-4'][$oldNamespace]);
        static::$package['autoload']['psr-4'][$newNamespace] = 'src/';

        foreach (static::$package['scripts'] as $key => $scripts) {
            if (is_string($scripts)) {
                static::$package['scripts'][$key] = str_replace($oldNamespace, $newNamespace, $scripts);
            } else {
                foreach ($scripts as $i => $script) {
                    static::$package['scripts'][$key][$i] = str_replace($oldNamespace, $newNamespace, $script);
                }
            }
        }

        $fileNames = [
            'src/Composer/Scripts.php',
        ];
        foreach ($fileNames as $fileName) {
            file_put_contents(
                $fileName,
                str_replace("namespace $oldNamespace", "namespace $newNamespace", file_get_contents($fileName))
            );
        }
    }

    protected static function validatePackageNameMachine(?string $input): ?string
    {
        if ($input) {
            if (!preg_match('/^[a-z][a-z0-9\-]*$/', $input)
                || strpos($input, '--')
            ) {
                throw new \InvalidArgumentException('Invalid characters');
            }
        }

        return $input;
    }

    protected static function validatePackageNameNamespace(?string $input): ?string
    {
        if ($input) {
            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $input)) {
                throw new \InvalidArgumentException('Invalid characters');
            }
        }

        return $input;
    }

    protected static function capitalCamelCase(string $input): string
    {
        $output = '';
        foreach (explode('-', $input) as $part) {
            $output .= ucfirst($part);
        }

        return $output;
    }

    protected static function composerDumpAutoload(): void
    {
        $cmdPattern = '%s dump-autoload';
        $cmdArgs = [
            escapeshellcmd($_SERVER['argv'][0]),
        ];

        $exitCode = 0;
        $files = [];
        exec(vsprintf($cmdPattern, $cmdArgs), $files, $exitCode);
    }

    protected static function composerUpdate(): void
    {
        $cmdPattern = '%s update nothing --lock';
        $cmdArgs = [
            escapeshellcmd($_SERVER['argv'][0]),
        ];

        $exitCode = 0;
        $files = [];
        exec(vsprintf($cmdPattern, $cmdArgs), $files, $exitCode);
    }

    protected static function ioAskQuestion(string $question, string $default, string $description = ''): string
    {
        $pattern = [
            '<question>{question}</question>',
        ];

        if ($description) {
            $pattern[] = '<question>{description}</question>';
        }

        $pattern[] = 'Default: "<info>{default}</info>"';

        $replacements = [
            '{question}' => $question,
            '{description}' => $description,
            '{default}' => $default,
        ];

        return strtr(implode("\n", $pattern), $replacements);
    }

    protected static function ioSelectDrupalProfileChoices(string $drupalRoot, bool $withHiddenOnes = false): array
    {
        $choices = [];
        foreach (static::getDrupalProfiles($drupalRoot) as $name => $info) {
            if ($withHiddenOnes || empty($info['hidden'])) {
                $label = $info['name'] ?? $name;
                $choices[$name] = "$label ($name)";
            }
        }

        return $choices;
    }

    protected static function getDrupalProfiles(string $drupalRoot): array
    {
        $profiles = [];

        $infoFiles = new Finder();
        $infoFiles
            ->in(["$drupalRoot/profiles"])
            ->name('*.info.yml')
            ->depth('< 3');

        foreach ($infoFiles as $infoFile) {
            $info = Yaml::parse(file_get_contents($infoFile->getPathname()));
            if (!empty($info['type']) && $info['type'] === 'profile') {
                $name = $infoFile->getBasename('.info.yml');
                $profiles[$name] = $info;
            }
        }

        return $profiles;
    }
}
