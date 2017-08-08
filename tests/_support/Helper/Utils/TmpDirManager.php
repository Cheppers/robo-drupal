<?php

namespace Sweetchuck\Robo\Drupal\Test\Helper\Utils;

use Symfony\Component\Filesystem\Filesystem;

class TmpDirManager
{
    protected static $initialized = false;

    /**
     * @var string[]
     */
    protected static $tmpDirs = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected static $fs;

    protected static function initialize()
    {
        if (static::$initialized) {
            return;
        }

        static::$fs = new Filesystem();

        register_shutdown_function(function () {
            $tmpDirs = array_filter(static::$tmpDirs, 'file_exists');
            static::$fs->chmod($tmpDirs, 0700, 0, true);
            static::$fs->remove($tmpDirs);
        });

        static::$initialized = true;
    }

    public static function create(string $parent = '', $prefix = ''): string
    {
        static::initialize();

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
        static::$tmpDirs[] = $tmpDir;

        return $tmpDir;
    }
}
