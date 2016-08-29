<?php

namespace Cheppers\Robo\Drupal;

/**
 * Class Utils.
 *
 * @package Cheppers\Robo\Drupal
 */
class Utils
{
    const DEFAULT_HTTP_PORT = 80;

    const DEFAULT_HTTPS_PORT = 443;

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
}
