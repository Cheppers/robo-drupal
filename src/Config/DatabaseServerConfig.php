<?php

namespace Cheppers\Robo\Drupal\Config;

class DatabaseServerConfig extends BaseConfig
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
            'driver' => 'sqlite',
            'database' => '',
        ],
    ];

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

    public function getConnection(): array
    {
        return array_replace_recursive($this->connection, $this->connectionLocal);
    }

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();

        $this->propertyMapping['connection'] = 'connection';
        $this->propertyMapping['connectionLocal'] = 'connectionLocal';
        $this->propertyMapping['authenticationMethod'] = 'authenticationMethod';

        $this->propertyMapping['driver'] = [
            'type' => 'closure',
            'closure' => function ($driver) {
                $this->connection = static::$driverMap[$driver];
                if ($driver === 'sqlite') {
                    $this->authenticationMethod = 'none';
                }
            }
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function populateProperties()
    {
        $this->data += ['driver' => 'mysql'];

        parent::populateProperties();

        return $this;
    }
}
