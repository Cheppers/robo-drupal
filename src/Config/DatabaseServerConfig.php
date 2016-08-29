<?php

namespace Cheppers\Robo\Drupal\Config;

/**
 * Class DatabaseServer.
 *
 * @package Cheppers\Robo\Drupal\Config
 */
class DatabaseServerConfig
{
    /**
     * @var array
     */
    public static $driverMap = [
        'mysql' => [
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
        'pgsql' => [
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
        'sqlite' => [
            'database' => '',
        ],
    ];

    /**
     * @var string
     */
    public $id = '';

    /**
     * @var array
     */
    public $connection = [];

    /**
     * @var array
     */
    public $connectionLocal = [];

    /**
     * @var string
     */
    public $authenticationMethod = 'user:pass';

    public function __construct($driver = 'mysql')
    {
        $this->connection = static::$driverMap[$driver];
        if ($driver === 'sqlite') {
            $this->authenticationMethod = 'none';
        }
    }
}
