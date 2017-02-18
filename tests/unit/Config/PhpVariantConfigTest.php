<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Config;

use Cheppers\Robo\Drupal\Config\PhpVariantConfig;

class PhpVariantConfigTest extends BaseConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected $className = PhpVariantConfig::class;
    
    public function casesGetPhpExecutable(): array
    {
        return [
            'empty dir and empty file' => ['php', '', ''],
            'empty dir and absolute' => ['/bar', '', '/bar'],
            'empty dir and relative' => ['foo', '', 'foo'],
            'absolute' => ['/c/d', '/a/b', '/c/d'],
            'relative' => ['/a/b/c/d', '/a/b', 'c/d'],
        ];
    }

    /**
     * @dataProvider casesGetPhpExecutable
     */
    public function testGetPhpExecutable(string $expected, string $binDir, string $executable)
    {
        $config = new PhpVariantConfig();
        $config->binDir = $binDir;
        $config->phpExecutable = $executable;
        $this->tester->assertEquals($expected, $config->getPhpExecutable());
    }

    public function casesGetPhpdbgExecutable(): array
    {
        return [
            'empty dir and empty file' => ['phpdbg', '', ''],
            'empty dir and absolute' => ['/bar', '', '/bar'],
            'empty dir and relative' => ['foo', '', 'foo'],
            'absolute' => ['/c/d', '/a/b', '/c/d'],
            'relative' => ['/a/b/c/d', '/a/b', 'c/d'],
        ];
    }

    /**
     * @dataProvider casesGetPhpdbgExecutable
     */
    public function testGetPhpdbgExecutable(string $expected, string $binDir, string $executable)
    {
        $config = new PhpVariantConfig();
        $config->binDir = $binDir;
        $config->phpdbgExecutable = $executable;
        $this->tester->assertEquals($expected, $config->getPhpdbgExecutable());
    }
}
