<?php

namespace Cheppers\Robo\Drupal\Config;

use Webmozart\PathUtil\Path;

class PhpVariantConfig extends BaseConfig
{
    /**
     * @var string
     */
    public $binDir = '';

    //region phpExecutable
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
    //endregion

    //region phpdbgExecutable
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
    //endregion

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

    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();

        $this->propertyMapping['binDir'] = 'binDir';
        $this->propertyMapping['phpExecutable'] = 'phpExecutable';
        $this->propertyMapping['phpdbgExecutable'] = 'phpdbgExecutable';
        $this->propertyMapping['version'] = 'version';
        $this->propertyMapping['cliIniFile'] = 'cliIniFile';
        $this->propertyMapping['fastCgiPass'] = 'fastCgiPass';
        $this->propertyMapping['ignoreTesting'] = 'ignoreTesting';

        return $this;
    }
}
