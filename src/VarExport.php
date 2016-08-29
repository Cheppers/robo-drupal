<?php

namespace Cheppers\Robo\Drupal;

class VarExport
{
    /**
     * @param mixed $value
     */
    public static function any($value, int $depth = 1, string $indent = '    '): string
    {
        switch (gettype($value)) {
            case 'NULL':
                return static::null();

            case 'boolean':
                return static::boolean($value);

            case 'integer':
            case 'double':
            case 'float':
                return static::number($value);

            case 'string':
                return static::string($value);

            case 'array':
                return static::map($value, $depth, $indent);
        }

        return '';
    }

    public static function null(): string
    {
        return 'null';
    }

    public static function boolean(bool $boolean): string
    {
        return $boolean ? 'true' : 'false';
    }

    /**
     * @param int|float $number
     */
    public static function number($number): string
    {
        return (string) $number;
    }

    public static function string(string $string): string
    {
        return var_export($string, true);
    }

    public static function map(array $array, int $depth = 1, string $indent = '    '): string
    {
        if (!$array) {
            return '[]';
        }

        $isNumericIndexed = Utils::isNumericIndexedArray($array);

        $lines = ['['];
        foreach ($array as $key => $value) {
            $line = $isNumericIndexed ? $indent : $indent . static::string($key) . " => ";
            $line .= static::any($value, $depth + 1, $indent) . ',';
            $lines[] = str_repeat($indent, $depth) . $line;
        }
        $lines[] = str_repeat($indent, $depth) . ']';

        return implode("\n", $lines);
    }
}
