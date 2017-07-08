<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance;

use Symfony\Component\Filesystem\Filesystem;

class Base
{

    /**
     * @var string[]
     */
    protected $tmpDirs = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();

        register_shutdown_function(function () {
            $tmpDirs = array_filter($this->tmpDirs, 'file_exists');
            $this->fs->chmod($tmpDirs, 0700, 0, true);
            $this->fs->remove($tmpDirs);
        });
    }

    protected function prepareProject(string $projectName): string
    {
        $tmpDir = $this->createTmpDir();
        $templateDir = $this->fixturesRoot($projectName) . '/base';
        $this->fs->mirror($templateDir, $tmpDir);

        return $tmpDir;
    }

    protected function fixturesRoot(string $projectName): string
    {
        return codecept_data_dir("fixtures/ProjectType/Incubator/$projectName");
    }

    protected function createTmpDir(): string
    {
        $class = explode('\\', static::class);
        $tmpDir = tempnam(sys_get_temp_dir(), 'robo-drupal-' . end($class) . '-');
        if (!$tmpDir) {
            throw new \Exception();
        }

        unlink($tmpDir);
        mkdir($tmpDir);
        $this->tmpDirs[] = $tmpDir;

        return $tmpDir;
    }
}
