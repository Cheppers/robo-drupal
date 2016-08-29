<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Config;

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;

class DatabaseServerConfigTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function casesConstructor()
    {
        return [
            'mysql' => [
                [
                    'connection' => [
                        'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
                        'driver' => 'mysql',
                        'username' => '',
                        'password' => '',
                        'host' => '127.0.0.1',
                        'port' => 3306,
                        'collation' => 'utf8mb4_general_ci',
                        'prefix' => '',
                        'database' => '',
                    ],
                    'authenticationMethod' => 'user:pass',
                ],
                'mysql',
            ],
            'pgsql' => [
                [
                    'connection' => [
                        'namespace' => 'Drupal\\Core\\Database\\Driver\\pgsql',
                        'driver' => 'pgsql',
                        'username' => '',
                        'password' => '',
                        'host' => '127.0.0.1',
                        'port' => 5432,
                        'collation' => 'utf8mb4_general_ci',
                        'prefix' => '',
                        'database' => '',
                    ],
                    'authenticationMethod' => 'user:pass',
                ],
                'pgsql',
            ],
            'sqlite' => [
                [
                    'connection' => [
                        'database' => '',
                    ],
                    'authenticationMethod' => 'none',
                ],
                'sqlite',
            ],
        ];
    }

    /**
     * @dataProvider casesConstructor
     */
    public function testConstructor($expected, $driver)
    {
        $dbServerConfig = new DatabaseServerConfig($driver);
        $this->tester->assertEquals($expected['connection'], $dbServerConfig->connection);
        $this->tester->assertEquals($expected['authenticationMethod'], $dbServerConfig->authenticationMethod);
    }
}
