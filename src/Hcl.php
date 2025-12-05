<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl;

use Cline\Hcl\Exceptions\FileReadException;
use Cline\Hcl\Exceptions\ParserException;
use Cline\Hcl\Parser\GenericParser;
use Cline\Hcl\Parser\Lexer;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

use function array_any;
use function array_is_list;
use function file_get_contents;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function preg_match;
use function sprintf;
use function str_repeat;
use function str_replace;
use function throw_if;

/**
 * Generic HCL parsing and conversion utilities.
 *
 * Provides methods to parse any valid HCL content and convert
 * between HCL and JSON formats.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Hcl
{
    /**
     * Parse HCL content into a PHP array.
     *
     * @param  string               $content The HCL content
     * @throws ParserException      If parsing fails
     * @return array<string, mixed> The parsed data
     */
    public static function parse(string $content): array
    {
        $lexer = new Lexer($content);
        $tokens = $lexer->tokenize();
        $parser = new GenericParser($tokens);

        return $parser->parse();
    }

    /**
     * Parse an HCL file into a PHP array.
     *
     * @param  string               $path The file path to read and parse
     * @throws FileReadException    If the file cannot be read
     * @throws ParserException      If parsing fails
     * @return array<string, mixed> The parsed data
     */
    public static function parseFile(string $path): array
    {
        $content = file_get_contents($path);

        throw_if($content === false, FileReadException::forPath($path));

        return self::parse($content);
    }

    /**
     * Convert HCL content to JSON.
     *
     * @param  string          $hcl    The HCL content
     * @param  bool            $pretty Whether to pretty-print the JSON
     * @throws ParserException If parsing fails
     * @return string          The JSON string
     */
    public static function toJson(string $hcl, bool $pretty = true): string
    {
        $data = self::parse($hcl);
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $flags);
    }

    /**
     * Convert JSON to HCL.
     *
     * @param  string $json The JSON string
     * @return string The HCL content
     */
    public static function fromJson(string $json): string
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return self::arrayToHcl($data);
    }

    /**
     * Convert a PHP array to HCL.
     *
     * @param  array<string, mixed> $data   The data to convert
     * @param  int                  $indent Current indentation level
     * @return string               The HCL content
     */
    public static function arrayToHcl(array $data, int $indent = 0): string
    {
        $lines = [];
        $indentStr = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (self::isBlock($value)) {
                // It's a block structure
                /** @var array<string, mixed> $value */
                $lines[] = self::formatBlock((string) $key, $value, $indent);
            } else {
                // It's an attribute
                $lines[] = $indentStr.self::formatKey((string) $key).' = '.self::formatValue($value, $indent);
            }
        }

        return implode("\n", $lines).($indent === 0 ? "\n" : '');
    }

    /**
     * Check if a key-value pair represents a block.
     *
     * A block is detected when the value is an associative array
     * that doesn't look like a simple object value. Lists and
     * non-array values are never treated as blocks.
     *
     * @param  mixed $value The value to check
     * @return bool  True if this represents an HCL block structure
     */
    private static function isBlock(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Lists are not blocks
        if (array_is_list($value)) {
            return false;
        }

        // Check if all keys are strings (potential labels)
        foreach ($value as $k => $v) {
            if (!is_string($k)) {
                return false;
            }

            // If any value is an associative array, it's likely a block
            if (is_array($v) && !array_is_list($v)) {
                return true;
            }
        }

        // If we have nested structure, treat as block
        // Otherwise, it's likely a simple object/map value
        return false;
    }

    /**
     * Format a block structure.
     *
     * @param  string               $type   The block type
     * @param  array<string, mixed> $data   The block data
     * @param  int                  $indent Current indentation
     * @return string               The formatted block
     */
    private static function formatBlock(string $type, array $data, int $indent): string
    {
        $indentStr = str_repeat('  ', $indent);
        $lines = [];

        foreach ($data as $label => $content) {
            if (is_array($content) && !array_is_list($content)) {
                $hasNestedBlocks = array_any($content, fn ($v): bool => is_array($v) && !array_is_list($v));

                if ($hasNestedBlocks) {
                    // Recurse with additional label
                    foreach ($content as $nestedLabel => $nestedContent) {
                        if (is_array($nestedContent) && !array_is_list($nestedContent)) {
                            /** @var array<string, mixed> $nestedContent */
                            $lines[] = $indentStr.$type.' "'.self::escapeString($label).'" "'.self::escapeString((string) $nestedLabel).'" {';
                            $lines[] = self::arrayToHcl($nestedContent, $indent + 1);
                            $lines[] = $indentStr.'}';
                            $lines[] = '';
                        } else {
                            // Simple value at this level
                            $lines[] = $indentStr.$type.' "'.self::escapeString($label).'" {';
                            $lines[] = str_repeat('  ', $indent + 1).self::formatKey((string) $nestedLabel).' = '.self::formatValue($nestedContent, $indent + 1);
                            $lines[] = $indentStr.'}';
                            $lines[] = '';
                        }
                    }
                } else {
                    // Single label block
                    /** @var array<string, mixed> $content */
                    $lines[] = $indentStr.$type.' "'.self::escapeString($label).'" {';
                    $lines[] = self::arrayToHcl($content, $indent + 1);
                    $lines[] = $indentStr.'}';
                    $lines[] = '';
                }
            } else {
                // Simple attribute inside block type
                $lines[] = $indentStr.$type.' {';
                $lines[] = str_repeat('  ', $indent + 1).self::formatKey($label).' = '.self::formatValue($content, $indent + 1);
                $lines[] = $indentStr.'}';
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format a key for HCL output.
     *
     * Quotes keys that contain special characters or don't match
     * the identifier pattern. Valid identifiers remain unquoted.
     *
     * @param  string $key The key to format
     * @return string The formatted key (quoted if necessary)
     */
    private static function formatKey(string $key): string
    {
        // Quote keys that need it
        if (preg_match('/^[a-zA-Z_]\w*$/', $key)) {
            return $key;
        }

        return '"'.self::escapeString($key).'"';
    }

    /**
     * Format a value for HCL output.
     *
     * @param  mixed  $value  The value to format
     * @param  int    $indent Current indentation
     * @return string The formatted value
     */
    private static function formatValue(mixed $value, int $indent = 0): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return '"'.self::escapeString($value).'"';
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return self::formatArray($value, $indent);
            }

            /** @var array<string, mixed> $value */
            return self::formatObject($value, $indent);
        }

        // Unknown type - convert to empty string
        return '""';
    }

    /**
     * Format an array for HCL output.
     *
     * @param  array<mixed> $array  The array to format
     * @param  int          $indent Current indentation
     * @return string       The formatted array
     */
    private static function formatArray(array $array, int $indent): string
    {
        if ($array === []) {
            return '[]';
        }

        $items = [];

        foreach ($array as $item) {
            $items[] = self::formatValue($item, $indent);
        }

        // Short arrays on one line
        $oneLine = '['.implode(', ', $items).']';

        if (mb_strlen($oneLine) < 80) {
            return $oneLine;
        }

        // Long arrays on multiple lines
        $indentStr = str_repeat('  ', $indent + 1);
        $closeIndent = str_repeat('  ', $indent);

        return '[
'.$indentStr.implode(',
'.$indentStr, $items).",\n{$closeIndent}]";
    }

    /**
     * Format an object/map for HCL output.
     *
     * @param  array<string, mixed> $object The object to format
     * @param  int                  $indent Current indentation
     * @return string               The formatted object
     */
    private static function formatObject(array $object, int $indent): string
    {
        if ($object === []) {
            return '{}';
        }

        $items = [];
        $indentStr = str_repeat('  ', $indent + 1);

        foreach ($object as $key => $value) {
            $items[] = $indentStr.self::formatKey($key).' = '.self::formatValue($value, $indent + 1);
        }

        $closeIndent = str_repeat('  ', $indent);

        return "{\n".implode("\n", $items).sprintf('%s%s}', PHP_EOL, $closeIndent);
    }

    /**
     * Escape a string for HCL output.
     *
     * @param  string $string The string to escape
     * @return string The escaped string
     */
    private static function escapeString(string $string): string
    {
        return str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $string,
        );
    }
}
