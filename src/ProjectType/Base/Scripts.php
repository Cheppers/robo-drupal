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
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected static $fs = null;

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
     * @var string
     */
    protected static $envNamePrefix = '';

    /**
     * @var bool
     */
    protected static $isGitRepoNew = false;

    /**
     * @var string
     */
    protected static $processClass = Process::class;

    /**
     * @todo Dynamically detect the main Drupal version.
     *
     * @var int
     */
    protected static $drupalCoreVersionMain = 8;

    /**
     * Composer script event handler.
     */
    public static function postInstallCmd(Event $event): bool
    {
        static::$event = $event;
        static::$fs = new Filesystem();
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
        static::$fs = new Filesystem();
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
        static::$fs = new Filesystem();
        static::initProjectConfig();

        $result = true;
        try {
            static::oneTime();
        } catch (\Exception $e) {
            static::$event->getIO()->writeError($e->getMessage(), true);

            $result = false;
        }

        return $result;
    }

    protected static function initProjectConfig(): void
    {
        $projectConfigFilePath = static::$packageRootDir . '/' . Utils::$projectConfigFileName;
        if (!static::$projectConfig && static::$fs->exists($projectConfigFilePath)) {
            static::$projectConfig = include $projectConfigFilePath;
        }

        if (!static::$projectConfig) {
            $class = static::$projectConfigClass;
            static::$projectConfig = new $class();
        }
    }

    /**
     * Trigger the main scaffold.
     */
    protected static function buildScaffold(): bool
    {
        if (!static::$fs->exists(static::$projectConfig->drupalRootDir . '/autoload.php')) {
            Plugin::scaffold(static::$event);
        }

        return true;
    }

    /**
     * Initialize the untracked files and directories.
     */
    protected static function createRequiredFiles(): bool
    {
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
                if (!static::$fs->exists($dir)) {
                    static::$fs->mkdir($dir);
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
        static::gitInit();
        static::removePostCreateProjectCmdScript();
        static::renamePackage();
    }

    protected static function oneTimePost(): void
    {
        static::packageDump();
        static::composerDumpAutoload();
        static::composerUpdate();
        static::gitInitialCommit();
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
        $cwd = static::$packageRootDir === '.' ? getcwd() : static::$packageRootDir;
        $cwdParts = explode(DIRECTORY_SEPARATOR, $cwd);

        $cwdPartNameMachine = array_pop($cwdParts);
        $cwdPartVendorMachine = array_pop($cwdParts);

        $defaults = static::getEnvVars([
            'vendorMachine' => $cwdPartVendorMachine,
            'vendorNamespace' => null,
            'nameMachine' => $cwdPartNameMachine,
            'nameNamespace' => null,
        ]);

        $questionPattern = 'Rename the package ({current}/4) - {title}:';

        $io = static::$event->getIO();

        $default = $defaults['vendorMachine'] ?: $cwdPartVendorMachine;
        static::$inputNewVendorMachine = $io->askAndValidate(
            static::ioAskQuestion(
                $questionPattern,
                $default,
                'composer_name_part',
                [
                    '{current}' => 1,
                    '{title}' => 'vendor as machine-name',
                ]
            ),
            function (?string $input) {
                return static::validatePackageNameMachine($input);
            },
            static::$ioAttempts,
            $default
        );

        $default = $defaults['vendorNamespace'] ?: StaticStringy::upperCamelize(static::$inputNewVendorMachine);
        static::$inputNewVendorNamespace = $io->askAndValidate(
            static::ioAskQuestion(
                $questionPattern,
                $default,
                'php_namespace',
                [
                    '{current}' => 2,
                    '{title}' => 'vendor as namespace',
                ]
            ),
            function (?string $input) {
                return static::validatePackageNameNamespace($input);
            },
            static::$ioAttempts,
            $default
        );

        $default = $defaults['nameMachine'] ?: $cwdPartNameMachine;
        static::$inputNewNameMachine = $io->askAndValidate(
            static::ioAskQuestion(
                $questionPattern,
                $default,
                'composer_name_part',
                [
                    '{current}' => 3,
                    '{title}' => 'name as machine-name',
                ]
            ),
            function (?string $input) {
                return static::validatePackageNameMachine($input);
            },
            static::$ioAttempts,
            $default
        );

        $default = $defaults['nameNamespace'] ?: StaticStringy::upperCamelize(static::$inputNewNameMachine);
        static::$inputNewNameNamespace = $io->askAndValidate(
            static::ioAskQuestion(
                $questionPattern,
                $default,
                'php_namespace',
                [
                    '{current}' => 2,
                    '{title}' => 'name as namespace',
                ]
            ),
            function (?string $input) {
                return static::validatePackageNameNamespace($input);
            },
            static::$ioAttempts,
            $default
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

        $pc = static::$projectConfig;
        $from_to = [
            'machine_name_long' => StaticStringy::underscored(static::$inputNewNameMachine),
            'MACHINE_NAME_LONG' => StaticStringy::toUpperCase(StaticStringy::underscored(static::$inputNewNameMachine)),
        ];
        $fileNames = [
            "{$pc->drupalRootDir}/drush/machine_name_long.drush.inc",
        ];
        foreach ($fileNames as $fileNameOld) {
            if (!static::$fs->exists($fileNameOld)) {
                continue;
            }

            // @todo Protect drupalRootDir to get replaced.
            $fileNameNew = strtr($fileNameOld, $from_to);
            static::$fs->rename($fileNameOld, $fileNameNew);
            file_put_contents(
                $fileNameNew,
                strtr(
                    file_get_contents($fileNameNew),
                    $from_to
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
        if (static::$fs->exists(static::$packageRootDir . '/.git')) {
            return;
        }

        static::$isGitRepoNew = true;

        $cmdPattern = '';
        $cmdArgs = [];

        if (static::$packageRootDir !== '.') {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg(static::$packageRootDir);
        }

        $cmdPattern .= 'git init';

        $command = vsprintf($cmdPattern, $cmdArgs);
        /** @var \Symfony\Component\Process\Process $process */
        $process = new static::$processClass($command);
        $exitCode = $process->run();
        if ($exitCode !== 0) {
            // @todo Error handling.
            throw new \Exception('@todo Better error message');
        }
    }

    protected static function newInstanceFromDrupalProfileCustomer(string $profilesDir, string $machineName): void
    {
        $src = Utils::getRoboDrupalRoot() . '/src/Templates/drupal/profiles/customer';
        $dst = "$profilesDir/$machineName";
        static::$fs->mirror($src, $dst);
        static::$fs->rename("$dst/machine_name.info.yml", "$dst/$machineName.info.yml");

        file_put_contents(
            "$dst/$machineName.info.yml",
            str_replace(
                'name: HumanName',
                'name: ' . static::$inputNewNameNamespace,
                file_get_contents("$dst/$machineName.info.yml")
            )
        );

        file_put_contents(
            "$dst/composer.json",
            str_replace(
                'drupal/project',
                "drupal/$machineName",
                file_get_contents("$dst/composer.json")
            )
        );
    }

    protected static function composerDumpAutoload(): void
    {
        $cmdPattern = '';
        $cmdArgs = [];

        if (static::$packageRootDir !== '.') {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg(static::$packageRootDir);
        }

        $cmdPattern .= '%s dump-autoload';
        $cmdArgs[] = escapeshellcmd($_SERVER['argv'][0]);

        $command = vsprintf($cmdPattern, $cmdArgs);
        static::$event->getIO()->write($command, true);

        /** @var \Symfony\Component\Process\Process $process */
        $process = new static::$processClass($command);
        $exitCode = $process->run();
        if ($exitCode !== 0) {
            // @todo Error handling.
            throw new \Exception('@todo Better error message');
        }
    }

    protected static function composerUpdate(): void
    {
        $cmdPattern = '';
        $cmdArgs = [];

        if (static::$packageRootDir !== '.') {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg(static::$packageRootDir);
        }

        $cmdPattern .= '%s update nothing --lock';
        $cmdArgs[] = escapeshellcmd($_SERVER['argv'][0]);

        $command = vsprintf($cmdPattern, $cmdArgs);
        static::$event->getIO()->write($command, true);

        /** @var \Symfony\Component\Process\Process $process */
        $process = new static::$processClass($command);
        $exitCode = $process->run();
        if ($exitCode !== 0) {
            // @todo Error handling.
            throw new \Exception('@todo Better error message');
        }
    }

    protected static function gitInitialCommit(): void
    {
        if (!static::$isGitRepoNew) {
            return;
        }

        $commands = [
            [
                'pattern' => 'git add %s',
                'args' => [escapeshellarg('README.md')],
            ],
            [
                'pattern' => 'git commit -m %s',
                'args' => [escapeshellarg('Initial commit')],
            ],
            [
                'pattern' => 'git add %s',
                'args' => [escapeshellarg('.')],
            ],
            [
                'pattern' => 'git commit -m %s',
                'args' => [escapeshellarg('Basic implementation')],
            ],
        ];

        $cmdPrefix = '';
        if (static::$packageRootDir !== '.') {
            $cmdPrefix = sprintf(
                'cd %s && ',
                escapeshellarg(static::$packageRootDir)
            );
        }

        $io = static::$event->getIO();
        foreach ($commands as $command) {
            $cmd = $cmdPrefix . vsprintf($command['pattern'], $command['args']);
            $io->write($cmd, true);

            /** @var \Symfony\Component\Process\Process $process */
            $process = new static::$processClass($cmd);
            if ($process->run() !== 0) {
                // @todo Better error message.
                throw new \Exception("Failed to create the initial commit with '$cmd' command.");
            }
        }
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

    protected static function ioAskQuestion(
        string $question,
        string $default,
        string $description = '',
        array $replacements = []
    ): string {
        $pattern = [
            "<question>$question</question>",
        ];

        $descriptions = static::ioAskQuestionDescriptions();
        $desc = $descriptions[$description] ?? $description;
        if ($desc) {
            $pattern[] = "<question>$desc</question>";
        }

        $pattern[] = 'Default: "<info>{default}</info>"';
        $pattern[] = ': ';


        $replacements += [
            '{question}' => $question,
            '{description}' => $desc,
            '{default}' => $default,
        ];

        return strtr(implode("\n", $pattern), $replacements);
    }

    protected static function ioAskQuestionDescriptions(): array
    {
        return [
            'php_namespace' => 'Camel case',
            'composer_name_part' => 'Only lower case letters, numbers and "-" characters are allowed',
            'drupal_site_dir' => 'Only lower case letters, numbers and "._-" characters are allowed',
            'drupal_extension' => 'Only lower case letters, numbers and "_" characters are allowed',
        ];
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

    protected static function validateDrupalExtensionMachineName(?string $input, bool $required): ?string
    {
        if ($required && trim($input) === '') {
            throw new \InvalidArgumentException('Required');
        }

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

    protected static function getEnvVars(array $vars): array
    {
        foreach ($vars as $name => $default) {
            $vars[$name] = static::getEnvVar($name, $default);
        }

        return $vars;
    }

    protected static function getEnvVar(string $name, ?string $default): ?string
    {
        $value = getenv(static::getEnvNamePrefix() . '_' . static::toEnvName($name));

        return $value === false ? $default : $value;
    }

    protected static function getEnvNamePrefix(): string
    {
        if (!static::$envNamePrefix) {
            /** @var \Composer\Package\Package $package */
            $package = static::$event->getComposer()->getPackage();
            list(, $name) = explode('/', $package->getName());

            static::$envNamePrefix = static::toEnvName($name);
        }

        return static::$envNamePrefix;
    }

    protected static function toEnvName(string $name): string
    {
        return StaticStringy::toUpperCase(StaticStringy::underscored($name));
    }

    protected static function getDefaultMySQLConnection(): array
    {
        $default = [
            'username' => '',
            'password' => '',
            'host' => '127.0.0.1',
            'port' => 3306,
        ];

        $home = getenv('HOME');
        $mysql = [];
        if ($home && static::$fs->exists("$home/.my.cnf")) {
            $myCnf = @parse_ini_file("$home/.my.cnf", true);
            $mysql = $myCnf['mysql'] ?? $mysql;
            if (isset($mysql['user'])) {
                $mysql['username'] = $mysql['user'];
                unset($mysql['user']);
            }
        }

        return $mysql + $default;
    }
}
