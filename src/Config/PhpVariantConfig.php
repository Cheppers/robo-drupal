<?php

namespace Cheppers\Robo\Drupal\Config;

use Webmozart\PathUtil\Path;

/**
 * Class PhpVariant.
 *
 * @package Cheppers\Robo\Drupal\Config
 */
class PhpVariantConfig
{

    /**
     * @var string
     */
    public $id = '';

    /**
     * @var string
     */
    public $binDir = '';

    /**
     * @var string
     */
    public $phpExecutable = '';

    public function getPhpExecutable(): string
    {
        if (Path::isAbsolute($this->phpExecutable)) {
            return $this->phpExecutable;
        }

        return Path::join($this->binDir, ($this->phpExecutable ?: 'php'));
    }

    /**
     * @var string
     */
    public $phpdbgExecutable = '';

    public function getPhpdbgExecutable(): string
    {
        if (Path::isAbsolute($this->phpdbgExecutable)) {
            return $this->phpdbgExecutable;
        }

        return Path::join($this->binDir, ($this->phpdbgExecutable ?: 'phpdbg'));
    }

    /**
     * @var string
     */
    public $version = '';

    /**
     * @var string
     */
    public $cliIniFile = '';

    /**
     * @var string
     */
    public $fastCgiPass = '';

    /**
     * @var bool
     */
    public $ignoreTesting = false;
}
