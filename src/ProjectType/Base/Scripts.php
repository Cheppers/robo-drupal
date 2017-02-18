<?php

namespace Cheppers\Robo\Drupal\ProjectType\Base;

use Cheppers\GitHooks\Main as GitHooksComposerScripts;
use Cheppers\Robo\Drupal\Utils;
use Composer\Script\Event;
use DrupalComposer\DrupalScaffold\Plugin;
use Stringy\StaticStringy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class Scripts
{
    /**
     * @var \Composer\Script\Event
     */
    protected static $event = null;

    /**
     * @var string
     */
    protected static $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig
     */
    protected static $projectConfig = null;

    /**
     * @var string
     */
    protected static $baseNamespace = '\Cheppers\Robo\Drupal\ProjectType\Base';

    /**
     * @var string
     */
    protected static $packageRootDir = '.';

    /**
     * @var string
     */
    protected static $packageFileName = 'composer.json';

    /**
     * @var array
     */
    protected static $package = [];

    /**
     * @var string
     */
    protected static $oldVendorMachine = '';

    /**
     * @var string
     */
    protected static $oldVendorNamespace = '';

    /**
     * @var string
     */
    protected static $oldNameMachine = '';

    /**
     * @var string
     */
    protected static $oldNameNamespace = '';

    /**
     * @var int
     */
    protected static $ioAttempts = 3;

    /**
     * @var string
     */
    protected static $inputNewVendorMachine = '';

    /**
     * @var string
     */
    protected static $inputNewVendorNamespace = '';

    /**
     * @var string
     */
    protected static $inputNewNameMachine = '';

    /**
     * @var string
     */
    protected static $inputNewNameNamespace = '';

    /**
     * Composer script event handler.
     */
    public static function postInstallCmd(Event $event): bool
    {
        static::$event = $event;
        static::initProjectConfig();

        static::buildScaffold();
        static::createRequiredFiles();
        static::phpcsConfigSet();
        GitHooksComposerScripts::deploy($event);

        return true;
    }

    /**
     * Composer script event handler.
     */
    public static function postUpdateCmd(Event $event): bool
    {
        static::$event = $event;
        static::initProjectConfig();

        static::buildScaffold();
        static::createRequiredFiles();
        static::phpcsConfigSet();
        GitHooksComposerScripts::deploy($event);

        return true;
    }

    /**
     * Composer script event handler.
     */
    public static function postCreateProjectCmd(Event $event): bool
    {
        static::$event = $event;
        static::initProjectConfig();

        static::oneTime();

        return true;
    }

    protected static function initProjectConfig(): void
    {
        if (static::$projectConfig) {
            return;
        }

        $projectConfigFilePath = static::$packageRootDir . '/' . Utils::$projectConfigFileName;
        if (file_exists($projectConfigFilePath)) {
            require_once $projectConfigFilePath;
            if (!empty($GLOBALS['projectConfig'])) {
                static::$projectConfig = $GLOBALS['projectConfig'];

                return;
            }
        }

        $class = static::$projectConfigClass;

        static::$projectConfig = new $class();
    }

    /**
     * Trigger the main scaffold.
     */
    protected static function buildScaffold(): bool
    {
        $fs = new Filesystem();
        if (!$fs->exists(static::$projectConfig->drupalRootDir . '/autoload.php')) {
            Plugin::scaffold(static::$event);
        }

        return true;
    }

    /**
     * Initialize the untracked files and directories.
     */
    protected static function createRequiredFiles(): bool
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
                    static::$event->getIO()->write("Create a '$dir' directory");
                }
            }
        }

        return true;
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
        $process->run();
        static::$event->getIO()->write($process->getOutput(), false);
        static::$event->getIO()->write($process->getErrorOutput(), false);

        return $process->getExitCode() === 0;
    }

    protected static function oneTime(): void
    {
        static::oneTimePre();
        static::oneTimeMain();
        static::oneTimePost();
    }

    protected static function oneTimePre(): void
    {
        static::packageRead();
    }

    protected static function oneTimeMain(): void
    {
        static::removePostCreateProjectCmdScript();
        static::renamePackage();
        static::gitInit();
    }

    protected static function oneTimePost(): void
    {
        static::packageDump();
        static::composerDumpAutoload();
        static::composerUpdate();
    }

    protected static function packageRead(): void
    {
        $composerJsonFileName = static::$packageRootDir . '/' . static::$packageFileName;
        static::$package = json_decode(file_get_contents($composerJsonFileName), true);
        list(static::$oldVendorMachine, static::$oldNameMachine) = explode('/', static::$package['name']);
        $oldNamespace = array_search('src/', static::$package['autoload']['psr-4']);
        list(static::$oldVendorNamespace, static::$oldNameNamespace) = explode('\\', $oldNamespace);
    }

    protected static function packageDump(): void
    {
        file_put_contents(
            $composerJsonFileName = static::$packageRootDir . '/' . static::$packageFileName,
            json_encode(
                static::$package,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) . "\n"
        );
    }

    protected static function removePostCreateProjectCmdScript(): void
    {
        unset(static::$package['scripts']['post-create-project-cmd']);
    }

    protected static function renamePackage(): void
    {
        static::renamePackageInput();
        static::renamePackageProjectConfig();
        static::renamePackageComposer();
        static::renamePackageSource();
        static::renamePackageSummary();
    }

    protected static function renamePackageInput(): void
    {
        if (static::$event->getIO()->isInteractive() === false) {
            // @todo Provide default values or use CLI arguments.
            return;
        }

        $cwd = static::$packageRootDir === '.' ? getcwd() : static::$packageRootDir;
        $cwdParts = explode('/', $cwd);
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

        static::$inputNewVendorMachine = static::$event->getIO()->askAndValidate(
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

        static::$inputNewVendorNamespace = static::$event->getIO()->askAndValidate(
            sprintf(
                $questionPatternNamespace,
                2,
                'vendor as namespace',
                StaticStringy::upperCamelize(static::$inputNewVendorMachine)
            ),
            function (?string $input) {
                return static::validatePackageNameNamespace($input);
            },
            3,
            StaticStringy::upperCamelize(static::$inputNewVendorMachine)
        );

        static::$inputNewNameMachine = static::$event->getIO()->askAndValidate(
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

        static::$inputNewNameNamespace = static::$event->getIO()->askAndValidate(
            sprintf(
                $questionPatternNamespace,
                4,
                'name as namespace',
                StaticStringy::upperCamelize(static::$inputNewNameMachine)
            ),
            function (?string $input) {
                return static::validatePackageNameNamespace($input);
            },
            3,
            StaticStringy::upperCamelize(static::$inputNewNameMachine)
        );
    }

    protected static function renamePackageProjectConfig(): void
    {
        static::$projectConfig->id = StaticStringy::underscored(static::$inputNewNameMachine);
        $fileName = static::$packageRootDir . '/ProjectConfig.php';
        file_put_contents(
            $fileName,
            str_replace(
                StaticStringy::underscored(static::$oldNameMachine),
                static::$projectConfig->id,
                file_get_contents($fileName)
            )
        );
    }

    protected static function renamePackageComposer(): void
    {
        $oldNamespace = static::$oldVendorNamespace . '\\' . static::$oldNameNamespace . '\\';
        $newNamespace = static::$inputNewVendorNamespace . '\\' . static::$inputNewNameNamespace . '\\';

        static::$package['name'] = static::$inputNewVendorMachine . '/' . static::$inputNewNameMachine;

        $psr4 = static::$package['autoload']['psr-4'];
        static::$package['autoload']['psr-4'] = [];
        foreach ($psr4 as $namespace => $dir) {
            $namespace = static::replaceNamespace($namespace, $oldNamespace, $newNamespace);

            static::$package['autoload']['psr-4'][$namespace] = $dir;
        }

        foreach (static::$package['scripts'] as $key => $scripts) {
            if (is_string($scripts)) {
                static::$package['scripts'][$key] = static::replaceNamespace($scripts, $oldNamespace, $newNamespace);
            } else {
                foreach ($scripts as $i => $script) {
                    static::$package['scripts'][$key][$i] = static::replaceNamespace(
                        $script,
                        $oldNamespace,
                        $newNamespace
                    );
                }
            }
        }
    }

    protected static function replaceNamespace(string $namespace, string $old, string $new): string
    {
        return preg_replace('/^' . preg_quote($old) . '/', $new, $namespace);
    }

    protected static function renamePackageSource(): void
    {
        $oldNamespace = static::$oldVendorNamespace . '\\' . static::$oldNameNamespace . '\\';
        $newNamespace = static::$inputNewVendorNamespace . '\\' . static::$inputNewNameNamespace . '\\';

        $files = new Finder();
        $files
            ->in([static::$packageRootDir . '/src'])
            ->files()
            ->name('*.php');
        // @todo Replace everywhere, not just the namespaces. (For example type-hints).
        foreach ($files as $file) {
            file_put_contents(
                $file->getPathname(),
                str_replace(
                    "namespace $oldNamespace",
                    "namespace $newNamespace",
                    file_get_contents($file->getPathname())
                )
            );
        }
    }

    protected static function renamePackageSummary(): void
    {
        static::$event->getIO()->write(
            sprintf('The new package name is "<info>%s</info>"', static::$package['name']),
            true
        );

        $namespace = '\\' . static::$inputNewVendorNamespace . '\\' . static::$inputNewNameNamespace;
        static::$event->getIO()->write(
            sprintf('The new namespace name is "<info>%s</info>"', $namespace),
            true
        );
    }

    protected static function gitInit(): void
    {
        if (file_exists(static::$packageRootDir . '/.git')) {
            return;
        }

        $command = sprintf('cd %s && git init', static::$packageRootDir);
        $output = [];
        $exit_code = 0;
        exec($command, $output, $exit_code);
        if ($exit_code !== 0) {
            // @todo Do something.
        }

        GitHooksComposerScripts::deploy(static::$event);
    }

    protected static function newInstanceFromDrupalProfileCustomer(string $profilesDir, string $machine_name): void
    {
        $src = static::getRoboDrupalRoot() . '/src/Templates/drupal/profiles/customer';
        $dst = "$profilesDir/$machine_name";
        $fs = new Filesystem();
        $fs->mirror($src, $dst);
        $fs->rename("$dst/machine_name.info.yml", "$dst/$machine_name.info.yml");

        file_put_contents(
            "$dst/$machine_name.info.yml",
            str_replace(
                'name: HumanName',
                'name: ' . static::$inputNewNameNamespace,
                file_get_contents("$dst/$machine_name.info.yml")
            )
        );

        file_put_contents(
            "$dst/composer.json",
            str_replace(
                'drupal/project',
                "drupal/$machine_name",
                file_get_contents("$dst/composer.json")
            )
        );
    }

    /**
     * @todo Error handling.
     */
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

    /**
     * @todo Error handling.
     */
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

    protected static function getDrupalProfiles(string $drupalRoot, bool $withHiddenOnes = false): array
    {
        $profiles = [];

        $infoFiles = new Finder();
        $infoFiles
            ->in([
                "$drupalRoot/core/profiles",
                "$drupalRoot/profiles",
            ])
            ->name('*.info.yml')
            ->depth('< 3');

        foreach ($infoFiles as $infoFile) {
            $info = Yaml::parse(file_get_contents($infoFile->getPathname()));
            if (!empty($info['type'])
                && $info['type'] === 'profile'
                && ($withHiddenOnes || empty($info['hidden']))
            ) {
                $name = $infoFile->getBasename('.info.yml');
                $profiles[$name] = $info;
            }
        }

        ksort($profiles);

        return $profiles;
    }

    /**
     * Get the root directory of the "cheppers/robo-drupal" package.
     *
     * @todo The "composer/installers" can broke this.
     *
     * @deprecated
     *   See \Cheppers\Robo\Drupal\Utils::getRoboDrupalRoot().
     */
    protected static function getRoboDrupalRoot(): string
    {
        /** @var \Composer\Composer $composer */
        $composer = static::$event->getComposer();
        /** @var \Composer\Config $config */
        $config = $composer->getConfig();

        return $config->get('vendor-dir') . '/cheppers/robo-drupal';
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
        $pattern[] = ': ';

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
        foreach (static::getDrupalProfiles($drupalRoot, $withHiddenOnes) as $name => $info) {
            $label = $info['name'] ?? $name;
            $choices[$name] = "$label ($name)";
        }

        return $choices;
    }

    protected static function validatePackageNameMachine(?string $input): ?string
    {
        if ($input !== null) {
            if (!preg_match('/^[a-z][a-z0-9\-]*$/', $input)) {
                throw new \InvalidArgumentException('Invalid characters');
            }

            $input = preg_replace('/-{2,}/', '-', $input);
            $input = trim($input, '-');
        }

        return $input;
    }

    protected static function validatePackageNameNamespace(?string $input): ?string
    {
        if ($input !== null) {
            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $input)) {
                throw new \InvalidArgumentException('Invalid characters');
            }
        }

        return $input;
    }

    protected static function validateDrupalExtensionMachineName(?string $input): ?string
    {
        if ($input !== null) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $input)) {
                throw new \InvalidArgumentException('Invalid characters');
            }

            $input = preg_replace('/_{2,}/', '_', $input);
            $input = trim($input, '_');
        }

        return $input;
    }

    protected static function validateSiteBranch(?string $input): ?string
    {
        // @todo
        return $input;
    }
}
