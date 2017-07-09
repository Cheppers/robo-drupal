<?php

namespace Cheppers\Robo\Drupal\Tests\Unit;

use Codeception\Test\Unit;
use Symfony\Component\Filesystem\Filesystem;

class Base extends Unit
{
    /**
     * @var \Cheppers\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    /**
     * @var string[]
     */
    protected $tmpDirs = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->fs = new Filesystem();

        register_shutdown_function(function () {
            $this->deleteTmpDirs();
        });
    }

    protected function deleteTmpDirs(): void
    {
        $tmpDirs = array_filter($this->tmpDirs, 'file_exists');
        $this->fs->chmod($tmpDirs, 0700, 0, true);
        $this->fs->remove($tmpDirs);
    }

    protected function createTmpDir(string $parent = '', $prefix = ''): string
    {
        if (!$parent) {
            $parent = sys_get_temp_dir();
        }

        if (!$prefix) {
            $class = explode('\\', static::class);
            $prefix = 'robo-drupal-' . end($class) . '-';
        }

        $tmpDir = tempnam($parent, $prefix);
        if (!$tmpDir) {
            throw new \Exception();
        }

        unlink($tmpDir);
        mkdir($tmpDir);
        $this->tmpDirs[] = $tmpDir;

        return $tmpDir;
    }
}
