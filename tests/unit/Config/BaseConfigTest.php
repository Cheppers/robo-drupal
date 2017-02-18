<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\Config;

use Cheppers\Robo\Drupal\Config\BaseConfig;
use Codeception\Test\Unit;

class BaseConfigTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    protected $className = BaseConfig::class;

    public function testPropertyMapping(): void
    {
        $className = $this->className;
        $instance = new $className();
        $class = new \ReflectionClass($instance);
        $publicProperties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($publicProperties as $key => $publicProperty) {
            if ($publicProperty->isStatic()) {
                unset($publicProperties[$key]);
            }
        }

        $propertyMapping = $class->getProperty('propertyMapping');
        $propertyMapping->setAccessible(true);

        $missing = [];
        $mapping = $propertyMapping->getValue($instance);
        foreach ($publicProperties as $property) {
            $name = $property->getName();
            $found = false;
            foreach ($mapping as $handler) {
                if ($handler['destination'] === $name) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                $missing[] = $name;
            }
        }

        $this->assertEquals([], $missing);
    }
}
