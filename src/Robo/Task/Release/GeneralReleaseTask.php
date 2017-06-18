<?php

namespace Cheppers\Robo\Drupal\Robo\Task\Release;

use Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig;
use Cheppers\Robo\Drupal\Robo\Task\BaseTask;
use Cheppers\Robo\Drupal\Utils;
use Cheppers\Robo\Git\Task\GitListFilesTask;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Exception\TaskExitException;
use Robo\Result;
use Robo\Task\Composer\Update as ComposerUpdate;
use Robo\Task\File\Write as FileWriteTask;
use Robo\Task\Filesystem\FilesystemStack;
use Robo\Task\Vcs\GitStack;
use Webmozart\PathUtil\Path;

class GeneralReleaseTask extends BaseTask implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $taskName = 'General Drupal Project Release';

    //region Options.
    //region Option - projectConfig.
    /**
     * @var null|\Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig
     */
    protected $projectConfig = null;

    public function getProjectConfig(): ?ProjectConfig
    {
        return $this->projectConfig;
    }

    /**
     * @return $this
     */
    public function setProjectConfig(ProjectConfig $pc)
    {
        $this->projectConfig = $pc;

        return $this;
    }
    //endregion

    //region Option - projectRootDir.
    protected $projectRootDir = '.';

    public function getProjectRootDir(): string
    {
        return $this->projectRootDir;
    }

    /**
     * @return $this
     */
    public function setProjectRootDir(string $projectRootDir)
    {
        $this->projectRootDir = $projectRootDir;

        return $this;
    }
    //endregion

    //region Option - releaseDir.
    /**
     * @var string
     */
    protected $releaseDir = '';

    public function getReleaseDir(): string
    {
        return $this->releaseDir;
    }

    /**
     * @return $this
     */
    public function setReleaseDir(string $directory)
    {
        $this->releaseDir = $directory;

        return $this;
    }
    //endregion

    //region Option - gitRemoteName.
    protected $gitRemoteName = '';

    public function getGitRemoteName(): string
    {
        return $this->gitRemoteName;
    }

    /**
     * @return $this
     */
    public function setGitRemoteName(string $gitRemoteName)
    {
        $this->gitRemoteName = $gitRemoteName;

        return $this;
    }
    //endregion

    //region Option - gitRemoteBranch.
    protected $gitRemoteBranch = '';

    public function getGitRemoteBranch(): string
    {
        return $this->gitRemoteBranch;
    }

    /**
     * @return $this
     */
    public function setGitRemoteBranch(string $name)
    {
        $this->gitRemoteBranch = $name;

        return $this;
    }
    //endregion

    //region Option - gitLocalBranch.
    protected $gitLocalBranch = '';

    public function getGitLocalBranch(): string
    {
        return $this->gitLocalBranch;
    }

    /**
     * @return $this
     */
    public function setGitLocalBranch(string $gitLocalBranch)
    {
        $this->gitLocalBranch = $gitLocalBranch;

        return $this;
    }
    //endregion

    //region Option - excludePatterns.
    /**
     * @var array
     */
    protected $excludePatterns = [
        '*.scss' => 'glob',
        '*.ts' => 'glob',
        '.bowerrc' => 'glob',
        '.csslintrc' => 'glob',
        '.editorconfig' => 'glob',
        '.eslintignore' => 'glob',
        '.eslintrc' => 'glob',
        '.gitignore' => 'glob',
        '.jshintrc' => 'glob',
        '.npmignore' => 'glob',
        '.ruby-gemset' => 'glob',
        '.ruby-version' => 'glob',
        'bower.json' => 'glob',
        'config.rb' => 'glob',
        'Gemfile' => 'glob',
        'Gemfile.lock' => 'glob',
        'Gruntfile.js' => 'glob',
        'Guardfile' => 'glob',
        'npm-shrinkwrap.json' => 'glob',
        'package.json' => 'glob',
        'yarn.lock' => 'glob',
    ];

    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    /**
     * @return $this
     */
    public function setExcludePatterns(array $excludePatterns)
    {
        $this->excludePatterns = $excludePatterns;

        return $this;
    }

    /**
     * @return $this
     */
    public function mergeExcludePatterns(array $values)
    {
        $this->excludePatterns = $values + $this->excludePatterns;

        return $this;
    }
    //endregion

    //region Option - includePatterns.
    /**
     * @var array
     */
    protected $includePatterns = [];

    public function getIncludePatterns(): array
    {
        return $this->includePatterns;
    }

    /**
     * @return $this
     */
    public function setIncludePatterns(array $includePatterns)
    {
        $this->includePatterns = $includePatterns;

        return $this;
    }
    //endregion
    //endregion

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * @return $this;
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'projectRootDir':
                    $this->setProjectRootDir($value);
                    break;

                case 'releaseDir':
                    $this->setReleaseDir($value);
                    break;

                case 'gitRemoteName':
                    $this->setGitRemoteName($value);
                    break;

                case 'gitRemoteBranch':
                    $this->setGitRemoteBranch($value);
                    break;

                case 'gitLocalBranch':
                    $this->setGitLocalBranch($value);
                    break;

                case 'excludePatterns':
                    $this->setExcludePatterns($value);
                    break;

                case 'includePatterns':
                    $this->setIncludePatterns($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(): Result
    {
        $this->printTaskInfo('Create a general release');

        $this
            ->validate()
            ->runPrepareReleaseDir()
            ->runPrepareGitDir()
            ->runCopyFiles()
            ->runCreateGitIgnore()
            ->runAdjustRelativePathInComposer()
            ->runComposerUpdate();

        return new Result($this, 0);
    }

    /**
     * @return $this
     */
    protected function validate()
    {
        if (!$this->getReleaseDir()) {
            throw new \InvalidArgumentException('The required "releaseDir" is missing.', 1);
        }

        $pc = $this->getProjectConfig();
        if (!$pc) {
            throw new \InvalidArgumentException('The required "projectConfig" is missing.', 1);
        }

        if (!$this->getGitLocalBranch()) {
            throw new \InvalidArgumentException('The required "gitLocalBranch" is missing.', 1);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function runPrepareReleaseDir()
    {
        // @todo Resolve relative "releaseDir" from "projectRootDir".
        $releaseDir = $this->getReleaseDir();
        $task = (new FilesystemStack())->inflect($this);
        if (file_exists($releaseDir)) {
            $task->remove(Utils::directDirectoryDescendants($releaseDir));
        } else {
            $task->mkdir($releaseDir);
        }

        $task
            ->run()
            ->stopOnFail();

        return $this;
    }

    /**
     * @return $this
     */
    protected function runPrepareGitDir()
    {
        $gitRemoteName = $this->getGitRemoteName();
        $gitRemoteBranch = $this->getGitRemoteBranch();
        $gitLocalBranch = $this->getGitLocalBranch();

        if ($gitRemoteName && $gitRemoteBranch) {
            $cmdPattern = 'pull %s %s:%s';
            $cmdArgs = [
                escapeshellarg($gitRemoteName),
                escapeshellarg($gitRemoteBranch),
                escapeshellarg($gitLocalBranch),
            ];

            (new GitStack())
                ->inflect($this)
                ->exec(vsprintf($cmdPattern, $cmdArgs))
                ->run()
                ->stopOnFail();
        }

        (new FilesystemStack())
            ->inflect($this)
            ->mirror(
                Path::join($this->getProjectRootDir(), '.git'),
                Path::join($this->getReleaseDir(), '.git')
            )
            ->run()
            ->stopOnFail();

        (new GitStack())
            ->inflect($this)
            ->dir($this->getReleaseDir())
            ->exec('symbolic-ref HEAD ' . escapeshellarg("refs/heads/$gitLocalBranch"))
            ->exec('reset')
            ->run()
            ->stopOnFail();

        return $this;
    }

    /**
     * @return $this
     */
    protected function runCopyFiles()
    {
        $result = (new GitListFilesTask())
            ->inflect($this)
            ->run()
            ->stopOnFail();

        $excludePatterns = Utils::filterDisabled($this->getExcludePatterns());
        $includePatterns = Utils::filterDisabled($this->getIncludePatterns());

        $filesToCopy = Utils::filterFileNames(
            array_keys($result['files']),
            $excludePatterns,
            $includePatterns
        );

        $dst = $this->getReleaseDir();
        $fileSystemStack = new FilesystemStack();
        $fileSystemStack->inflect($this);
        foreach ($filesToCopy as $fileName) {
            $fileSystemStack->copy($fileName, "$dst/$fileName");
        }
        $fileSystemStack
            ->run()
            ->stopOnFail();

        return $this;
    }

    /**
     * @return $this
     */
    protected function runCreateGitIgnore()
    {
        $dst = $this->getReleaseDir();
        (new FileWriteTask("$dst/.gitignore"))
            ->inflect($this)
            ->lines([
                '',
                '/ProjectConfig.local.php',
            ])
            ->run()
            ->stopOnFail();

        return $this;
    }

    /**
     * @return $this
     */
    public function runAdjustRelativePathInComposer()
    {
        $projectRootDir = $this->getProjectRootDir();
        if (Path::isRelative($projectRootDir)) {
            $projectRootDir = Path::makeAbsolute($projectRootDir, getcwd());
        }

        $releaseDir = $this->getReleaseDir();
        if (Path::isRelative($releaseDir)) {
            $releaseDir = Path::makeAbsolute($releaseDir, $projectRootDir);
        }

        $fileName = "$releaseDir/composer.json";
        $composerInfo = json_decode(file_get_contents($fileName), true);
        if (isset($composerInfo['repositories'])) {
            $changed = false;
            foreach ($composerInfo['repositories'] as $key => $repo) {
                if (!isset($repo['type'])) {
                    continue;
                }

                if ($repo['type'] === 'path' && Path::isRelative($repo['url'])) {
                    $newUrl = Path::makeRelative(
                        Path::canonicalize("$projectRootDir/{$repo['url']}"),
                        $releaseDir
                    );

                    if ($composerInfo['repositories'][$key]['url'] !== $newUrl) {
                        $composerInfo['repositories'][$key]['url'] = $newUrl;
                        $changed = true;
                    }
                }

                if ($repo['type'] === 'package') {
                    foreach (['dist', 'source'] as $remote) {
                        $remoteType = $repo['package'][$remote]['type'] ?? '';
                        if ($remoteType !== 'path' || Path::isAbsolute($repo['package'][$remote]['url'])) {
                            continue;
                        }

                        $newUrl = Path::makeRelative(
                            Path::canonicalize("$projectRootDir/{$repo['package'][$remote]['url']}"),
                            $releaseDir
                        );

                        if ($composerInfo['repositories'][$key]['package'][$remote]['url'] !== $newUrl) {
                            $composerInfo['repositories'][$key]['package'][$remote]['url'] = $newUrl;
                            $changed = true;
                        }
                    }
                }
            }

            if ($changed) {
                $bytes = file_put_contents(
                    $fileName,
                    json_encode($composerInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );

                if ($bytes === false) {
                    throw new TaskExitException(
                        $this,
                        "Failed to write '$fileName'",
                        1
                    );
                }
            }
        }

        $fileName = "$releaseDir/composer.lock";
        $composerLock = json_decode(file_get_contents($fileName), true);
        if (isset($composerLock['packages'])) {
            $changed = false;
            foreach ($composerLock['packages'] as $key => $repo) {
                if (!isset($repo['dist']['type'])
                    || $repo['dist']['type'] !== 'path'
                    || Path::isAbsolute($repo['dist']['url'])
                ) {
                    continue;
                }

                $newUrl = Path::makeRelative(
                    Path::canonicalize("$projectRootDir/{$repo['dist']['url']}"),
                    $releaseDir
                );

                if ($composerLock['packages'][$key]['dist']['url'] !== $newUrl) {
                    $composerLock['packages'][$key]['dist']['url'] = $newUrl;
                    $changed = true;
                }
            }

            if ($changed) {
                $bytes = file_put_contents(
                    $fileName,
                    json_encode($composerLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );

                if ($bytes === false) {
                    throw new TaskExitException(
                        $this,
                        "Failed to write '$fileName'",
                        1
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function runComposerUpdate()
    {
        (new ComposerUpdate())
            ->inflect($this)
            ->dir($this->getReleaseDir())
            ->noDev()
            ->arg('nothing')
            ->option('--lock')
            ->run()
            ->stopOnFail();

        return $this;
    }
}
