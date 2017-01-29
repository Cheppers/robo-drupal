<?php

namespace Cheppers\Robo\Drupal;

class Utils
{
    const DEFAULT_HTTP_PORT = 80;

    const DEFAULT_HTTPS_PORT = 443;

    /**
     * @var string
     */
    public static $projectConfigFileName = 'ProjectConfig.php';

    /**
     * Drupal related composer package types.
     *
     * @var string[]
     */
    public static $drupalPackageTypes = [
        'drupal-core',
        'drupal-profile',
        'drupal-module',
        'drupal-theme',
    ];

    public static function isDefaultHttpPort(int $port): bool
    {
        return !$port || in_array($port, [static::DEFAULT_HTTP_PORT, static::DEFAULT_HTTPS_PORT]);
    }

    public static function isNumericIndexedArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Check that a composer package is Drupal related or not.
     *
     * @param array $package
     *   Composer package definition.
     *
     * @return bool
     *   Return TRUE is the $package is  Drupal related.
     */
    public static function isDrupalPackage(array $package): bool
    {
        return in_array($package['type'], static::$drupalPackageTypes);
    }

    public static function manipulateString(
        string &$text,
        string $search,
        string $replace,
        string $method = 'replace'
    ): void {
        if ($method === 'before') {
            $replace .= $search;
        } elseif ($method === 'after') {
            $replace = $search . $replace;
        } elseif ($method !== 'replace') {
            throw new \InvalidArgumentException("Unknown method: '$method'");
        }

        $occurrences = null;
        $text = str_replace($search, $replace, $text, $occurrences);

        if (!$occurrences) {
            throw new \Exception("Placeholder not found: '$search'");
        }
    }

    /**
     * Get directory names grouped by depth.
     *
     * @param string[] $fileNames
     *   File names to parse.
     *
     * @return array
     *   Key is the depth level, the value is an array of directory names.
     */
    public static function dirNamesByDepth(array $fileNames): array
    {
        $dirNames = [];
        foreach ($fileNames as $fileName) {
            $dirs = explode('/', $fileName);
            array_pop($dirs);
            while ($dirs) {
                $dirName = implode('/', $dirs);
                $depth = count($dirs) - 1;
                if (!isset($dirNames[$depth][$dirName])) {
                    $dirNames[$depth][$dirName] = $dirName;
                }

                array_pop($dirs);
            }

            ksort($dirNames, SORT_NUMERIC);
        }

        return $dirNames;
    }
    
    public static function itemProperty2ArrayKey(array $items, string $property): array
    {
        $result = [];
        foreach ($items as $item) {
            if (!array_key_exists($property, $item)) {
                throw new \Exception("Property doesn't exists '$property'");
            }

            if (array_key_exists($item[$property], $result)) {
                throw new \Exception("Unique key already exists '{$item[$property]}'");
            }

            $result[$item[$property]] = $item;
        }
        
        return $result;
    }

    public static function filterDisabled(array $items, string $property = 'enabled'): array
    {
        $filtered = [];

        foreach ($items as $key => $value) {
            if ((is_scalar($value) || is_bool($value)) && $value) {
                $filtered[$key] = $value;
            } elseif (is_object($value) && property_exists($value, $property) && $value->$property) {
                // @todo Handle if the $property not exists.
                $filtered[$key] = $value;
            } elseif (is_array($value) && !empty($value[$property])) {
                // @todo Handle if the $property not exists.
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
