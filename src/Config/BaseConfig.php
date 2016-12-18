<?php

namespace Cheppers\Robo\Drupal\Config;

class BaseConfig
{
    /**
     * @var string
     */
    public $id = '';

    /**
     * @var array
     */
    protected $propertyMapping = [];

    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this
            ->initPropertyMapping()
            ->populateProperties();
    }

    /**
     * @return $this
     */
    protected function initPropertyMapping()
    {
        $this->propertyMapping['id'] = 'id';

        return $this;
    }

    /**
     * @return $this
     */
    protected function populateProperties()
    {
        foreach ($this->propertyMapping as $src => $handler) {
            if (!array_key_exists($src, $this->data)) {
                continue;
            }

            if (is_string($handler)) {
                $handler = [
                    'type' => 'set',
                    'destination' => $handler,
                ];
            }

            $handler += ['destination' => $src];

            switch ($handler['type']) {
                case 'set':
                    $this->{$handler['destination']} = $this->data[$src];
                    break;

                case 'closure':
                    $this->{$handler['destination']} = $handler['closure']($this->data[$src], $src);
                    break;

                case 'callback':
                    $this->{$handler['destination']} = call_user_func($handler['callback'], $this->data[$src], $src);
                    break;

                case 'subConfig':
                    /** @var static $subConfig */
                    $subConfig = new $handler['class']($this->data[$src]);
                    $this->{$handler['destination']} = $subConfig;
                    break;

                case 'subConfigs':
                    foreach ($this->data[$src] as $subConfigData) {
                        $subConfig = new $handler['class']($subConfigData);
                        $this->{$handler['destination']}[$subConfig->id] = $subConfig;
                    }
                    break;
            }
        }

        return $this;
    }
}
