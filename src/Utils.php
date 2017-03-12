<?php

namespace Cheppers\Robo\Drupal;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class Utils
{
    const DEFAULT_HTTP_PORT = 80;

    const DEFAULT_HTTPS_PORT = 443;

    const DEFAULT_MYSQL_PORT = 3306;

    /**
     * @var string
     */
    public static $projectConfigFileName = 'ProjectConfig.php';

    /**
     * @var string
     */
    public static $projectConfigLocalFileName = 'ProjectConfig.local.php';

    /**
     * @var bool[]
     */
    public static $phpFileExtensions = [
        'php' => true,
        'inc' => true,
        'profile' => true,
        'module' => true,
        'install' => true,
        'theme' => true,
        'engine' => true,
    ];

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

    /**
     * @return bool[]
     */
    public static function phpFileExtensionPatterns(string $prefix, string $suffix): array
    {
        $patterns = [];
        foreach (static::$phpFileExtensions as $phpFileExtension => $status) {
            $patterns["{$prefix}{$phpFileExtension}{$suffix}"] = $status;
        }

        return $patterns;
    }

    /**
     * Get the root directory of the "cheppers/robo-drupal" package.
     *
     * @todo The "composer/installers" can broke this.
     */
    public static function getRoboDrupalRoot(): string
    {
        return Path::canonicalize(Path::join(__DIR__, '/..'));
    }

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

    public static function getDefaultMysqlPort(): int
    {
        return (int) ini_get('mysqli.default_port') ?: static::DEFAULT_MYSQL_PORT;
    }

    public static function isLocalhost(string $host): bool
    {
        return ($host === '127.0.0.1' || $host === 'localhost');
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
    
    public static function getCustomDrupalProfiles(string $drupalRoot): array
    {
        $profiles = [];

        if (file_exists("$drupalRoot/profiles/custom")) {
            $root = "$drupalRoot/profiles/custom";
        } else {
            $root = "$drupalRoot/profiles";
        }

        /** @var Finder $dirs */
        $dirs = (new Finder())
            ->in($root)
            ->directories()
            ->depth(0);
        foreach ($dirs as $dir) {
            $profiles[$dir->getBasename()] = "$root/" . $dir->getRelativePathname();
        }

        return $profiles;
    }

    public static function filterFileNames(array $fileNames, array $excludePatterns, array $includePatterns): array
    {
        $return = [];
        foreach ($fileNames as $fileName) {
            if (!static::fileNameMatch($fileName, $excludePatterns)
                || static::fileNameMatch($fileName, $includePatterns)
            ) {
                $return[] = $fileName;
            }
        }

        return $return;
    }

    public static function fileNameMatch(string $fileName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $fileName)) {
                return true;
            }

            if (preg_match('@/$@u', $pattern) && strpos($fileName, $pattern) === 0) {
                return true;
            }

            if (strpos($pattern, '**/') === 0
                && strpos($fileName, '/') === false
                && fnmatch($pattern, "a/$fileName")
            ) {
                return true;
            }

            if ($fileName === $pattern) {
                return true;
            }
        }

        return false;
    }

    public static function cleanDirectory(string $dir): void
    {
        (new Filesystem())->remove(static::directDirectoryDescendants($dir));
    }

    public static function directDirectoryDescendants(string $dir): Finder
    {
        $files = new Finder();

        return $files
            ->in($dir)
            ->depth('== 0');
    }
}
