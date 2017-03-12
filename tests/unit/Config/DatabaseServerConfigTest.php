<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Config;

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;

class DatabaseServerConfigTest extends BaseConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected $className = DatabaseServerConfig::class;

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
                ['driver' => 'mysql'],
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
                ['driver' => 'pgsql'],
            ],
            'sqlite' => [
                [
                    'driver' => 'sqlite',
                    'connection' => [
                        'driver' => 'sqlite',
                        'database' => '',
                    ],
                    'authenticationMethod' => 'none',
                ],
                ['driver' => 'sqlite'],
            ],
        ];
    }

    /**
     * @dataProvider casesConstructor
     */
    public function testConstructor(array $expected, array $data)
    {
        $dbServerConfig = new DatabaseServerConfig($data);
        $this->tester->assertEquals($expected['connection'], $dbServerConfig->connection);
        $this->tester->assertEquals($expected['authenticationMethod'], $dbServerConfig->authenticationMethod);
    }
}
