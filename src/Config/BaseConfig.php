<?php

namespace Sweetchuck\Robo\Drupal\Config;

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

    /**
     * @var array
     */
    protected $dataDefaultValues = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this
            ->initPropertyMapping()
            ->expandPropertyMappingShortCuts()
            ->populateProperties();
    }

    /**
     * @return $this
     */
    protected function initPropertyMapping()
    {
        $this->propertyMapping += ['id' => 'id'];

        return $this;
    }

    /**
     * @return $this
     */
    protected function expandPropertyMappingShortCuts()
    {
        foreach ($this->propertyMapping as $src => $handler) {
            if (is_string($handler)) {
                $handler = [
                    'type' => 'set',
                    'destination' => $handler,
                ];
            }

            $this->propertyMapping[$src] = $handler + ['destination' => $src];
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function populateProperties()
    {
        $data = $this->setDataDefaultValues($this->data);
        foreach ($this->propertyMapping as $src => $handler) {
            if (!array_key_exists($src, $data)) {
                continue;
            }

            switch ($handler['type']) {
                case 'set':
                    $this->{$handler['destination']} = $data[$src];
                    break;

                case 'closure':
                    $this->{$handler['destination']} = $handler['closure']($data[$src], $src);
                    break;

                case 'callback':
                    $this->{$handler['destination']} = call_user_func($handler['callback'], $data[$src], $src);
                    break;

                case 'subConfig':
                    /** @var static $subConfig */
                    $subConfig = new $handler['class']($data[$src]);
                    $this->{$handler['destination']} = $subConfig;
                    break;

                case 'subConfigs':
                    foreach ($data[$src] as $subConfigId => $subConfigData) {
                        $subConfigData += ['id' => $subConfigId];
                        $subConfig = new $handler['class']($subConfigData);
                        $this->{$handler['destination']}[$subConfig->id] = $subConfig;
                    }
                    break;
            }
        }

        return $this;
    }

    protected function setDataDefaultValues(array $data): array
    {
        return array_replace_recursive($this->dataDefaultValues, $data);
    }
}
