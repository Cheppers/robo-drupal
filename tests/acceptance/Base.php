<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance;

use Cheppers\Robo\Drupal\Test\Helper\Utils\TmpDirManager;
use Symfony\Component\Filesystem\Filesystem;

class Base
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    protected function prepareProject(string $projectName): string
    {
        $tmpDir = TmpDirManager::create();
        $templateDir = $this->fixturesRoot($projectName) . '/base';
        $this->fs->mirror($templateDir, $tmpDir);

        return $tmpDir;
    }

    protected function fixturesRoot(string $projectName): string
    {
        return codecept_data_dir("fixtures/ProjectType/Incubator/$projectName");
    }
}
