<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\ProjectType\Incubator;

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Codeception\Test\Unit;
use ReflectionClass;

/**
 * @coversDefaultClass \Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig
 */
class ProjectConfigTest extends Unit
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new ReflectionClass(ProjectConfig::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \Cheppers\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    public function casesGetBaseHost(): array
    {
        return [
            [
                'a-b.localhost',
                'a_b',
                '',
                0,
            ],
            [
                'a-b.localhost',
                'aB',
                '',
                80,
            ],
            [
                'a-b.localhost:8080',
                'a-b',
                '',
                8080,
            ],
            [
                'a-b.loc',
                'p1',
                'a-b.loc',
                0,
            ],
            [
                'a_b.loc',
                'p1',
                'a_b.loc',
                80,
            ],
            [
                'ab.loc:8080',
                'p1',
                'ab.loc',
                8080,
            ],
        ];
    }

    /**
     * @dataProvider casesGetBaseHost
     *
     * @covers ::getBaseHost
     */
    public function testGetBaseHost(
        string $expected,
        string $id,
        string $baseHostName,
        int $baseHostPort
    ) {
        $pc = new ProjectConfig();
        $pc->id = $id;
        $pc->baseHostName = $baseHostName;
        $pc->baseHostPort = $baseHostPort;

        $this->tester->assertEquals($expected, $pc->getBaseHost());
    }

    public function casesGetBaseHostName(): array
    {
        return [
            ['.localhost', '', ''],
            ['a-b.localhost', 'a_b', ''],
            ['c_d.loc', 'a-b', 'c_d.loc'],
        ];
    }

    /**
     * @dataProvider casesGetBaseHostName
     *
     * @covers ::getBaseHostName
     */
    public function testGetBaseHostName(string $expected, string $name, string $baseHostName)
    {
        $pc = new ProjectConfig();
        $pc->id = $name;
        $pc->baseHostName = $baseHostName;
        $pc->baseHostPort = 8080;

        $this->tester->assertEquals($expected, $pc->getBaseHostName());
    }

    public function casesGetProjectUrls(): array
    {
        return [
            [
                [
                    '56.my.sb2.b.c' => 'sb2.my',
                    '56.pg.sb2.b.c' => 'sb2.pg',
                    '70.my.sb2.b.c' => 'sb2.my',
                    '70.pg.sb2.b.c' => 'sb2.pg',
                    '56.my.sb3.b.c' => 'sb3.my',
                    '56.pg.sb3.b.c' => 'sb3.pg',
                    '70.my.sb3.b.c' => 'sb3.my',
                    '70.pg.sb3.b.c' => 'sb3.pg',
                ],
                ['56', '70'],
                ['my', 'pg'],
            ],
        ];
    }

    /**
     * @dataProvider casesGetProjectUrls
     *
     * @covers ::getProjectUrls
     */
    public function testGetProjectUrls(array $expected, array $phpIds, array $dbIds)
    {
        $pc = new ProjectConfig();
        $pc->name = 'a';
        $pc->baseHostName = 'b.c';
        foreach ($phpIds as $phpId) {
            $pc->phpVariants[$phpId] = new PhpVariantConfig();
        }

        foreach ($dbIds as $dbId) {
            $pc->databaseServers[$dbId] = new DatabaseServerConfig();
        }

        $pc->sites['sb2'] = new SiteConfig();
        $pc->sites['sb3'] = new SiteConfig();

        $pc->populateDefaultValues();

        $this->tester->assertEquals($expected, $pc->getProjectUrls());
    }

    public function casesGetSiteBranchUrls(): array
    {
        return [
            [
                [
                    '56.my.sb1.b.c' => 'sb1.my',
                    '56.pg.sb1.b.c' => 'sb1.pg',
                    '70.my.sb1.b.c' => 'sb1.my',
                    '70.pg.sb1.b.c' => 'sb1.pg',
                ],
                ['56', '70'],
                ['my', 'pg'],
                'sb1',
            ],
        ];
    }

    /**
     * @dataProvider casesGetSiteBranchUrls
     *
     * @covers ::getSiteBranchUrls
     */
    public function testGetSiteBranchUrls(array $expected, array $phpIds, array $dbIds, string $siteBranch)
    {
        $pc = new ProjectConfig();
        $pc->name = 'a';
        $pc->baseHostName = 'b.c';
        foreach ($phpIds as $phpId) {
            $pc->phpVariants[$phpId] = new PhpVariantConfig();
        }

        foreach ($dbIds as $dbId) {
            $pc->databaseServers[$dbId] = new DatabaseServerConfig();
        }

        $pc->populateDefaultValues();

        $this->tester->assertEquals($expected, $pc->getSiteBranchUrls($siteBranch));
    }

    public function casesGetSiteVariantUrl(): array
    {
        return [
            ['', '', []],
            ['my-name-01.localhost', '{baseHost}', []],
            ['A', '{a}.{b}.{b}', ['{a}' => 'A', '{b}' => '']],
            ['A.B.B', '{a}.{b}.{b}', ['{a}' => 'A', '{b}' => 'B']],
        ];
    }

    /**
     * @dataProvider casesGetSiteVariantUrl
     *
     * @covers ::getSiteVariantUrl
     */
    public function testGetSiteVariantUrl(string $expected, string $siteVariantUrlPattern, array $placeholders)
    {
        $pc = new ProjectConfig();
        $pc->id = 'my_name_01';
        $pc->siteVariantUrlPattern = $siteVariantUrlPattern;
        $this->tester->assertEquals($expected, $pc->getSiteVariantUrl($placeholders));
    }

    public function casesGetSiteVariantDir(): array
    {
        return [
            ['', '', []],
            ['my-name-01.localhost', '{baseHost}', []],
            ['foo.mysql', '{siteBranch}.{db}', ['{db}' => 'mysql', '{siteBranch}' => 'foo']],
            ['foo', '{siteBranch}.{db}', ['{db}' => '', '{siteBranch}' => 'foo']],
        ];
    }

    /**
     * @dataProvider casesGetSiteVariantDir
     *
     * @covers ::getSiteVariantDir
     */
    public function testGetSiteVariantDir(string $expected, string $siteVariantDirPattern, array $placeholders)
    {
        $pc = new ProjectConfig();
        $pc->id = 'my_name_01';
        $pc->siteVariantDirPattern = $siteVariantDirPattern;
        $this->tester->assertEquals($expected, $pc->getSiteVariantDir($placeholders));
    }

    /**
     * @covers ::populateDefaultValues
     */
    public function testPopulateDefaultValues()
    {
        $pc = new ProjectConfig();
        $pc->phpVariants['a'] = new PhpVariantConfig();
        $pc->databaseServers['b'] = new DatabaseServerConfig();
        $pc->sites['c'] = new SiteConfig();
        $pc->populateDefaultValues();

        $this->tester->assertEquals('a', $pc->phpVariants['a']->id);
        $this->tester->assertEquals('b', $pc->databaseServers['b']->id);
        $this->tester->assertEquals('c', $pc->sites['c']->id);
    }

    /**
     * @return array
     */
    public function casesGetDefaultSiteId()
    {
        return [
            'empty' => [
                '',
                '',
                [],
            ],
            'configured' => [
                'b',
                'b',
                [
                    'default' => new SiteConfig(),
                    'a' => new SiteConfig(),
                    'b' => new SiteConfig(),
                    'c' => new SiteConfig(),
                ],
            ],
            'default' => [
                'default',
                '',
                [
                    'a' => new SiteConfig(),
                    'default' => new SiteConfig(),
                    'b' => new SiteConfig(),
                ],
            ],
            'first' => [
                'a',
                '',
                [
                    'a' => new SiteConfig(),
                    'b' => new SiteConfig(),
                    'c' => new SiteConfig(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetDefaultSiteId
     *
     * @covers ::getDefaultSiteId
     */
    public function testGetDefaultSiteId($expected, string $defaultSiteId, array $sites)
    {
        $pc = new ProjectConfig();
        $pc->defaultSiteId = $defaultSiteId;
        $pc->sites = $sites;

        $this->tester->assertEquals($expected, $pc->getDefaultSiteId());
    }

    public function casesProcessPattern(): array
    {
        return [
            'plain' => [
                'p1.loc',
                '{baseHost}',
                [],
            ],
            'full' => [
                'a',
                '{empty}.{empty}.a.{empty}.{empty}',
                ['{empty}' => ''],
            ],
        ];
    }

    /**
     * @dataProvider casesProcessPattern
     *
     * @covers ::processPattern
     */
    public function testProcessPattern(string $expected, string $pattern, array $placeholders)
    {
        $method = static::getMethod('processPattern');
        $pc = new ProjectConfig();
        $pc->id = 'p1';
        $pc->baseHostName = 'p1.loc';

        $this->tester->assertEquals(
            $expected,
            $method->invokeArgs($pc, [$pattern, $placeholders])
        );
    }
}
