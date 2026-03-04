<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Exceptions;

use function sprintf;

/**
 * Exception thrown when a field value has an invalid type during HCL parsing.
 *
 * This exception is raised when the parser encounters a value that doesn't match
 * the expected type for a given field, such as providing a string when an integer
 * is required, or an array when a scalar value is expected. Used for type validation
 * during configuration parsing and deserialization.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidValueException extends ParserException
{
    /**
     * Create an exception for a field with an invalid value type.
     *
     * @param string $field    The name of the field that has an invalid value type
     * @param string $expected The expected type description (e.g., "string", "integer", "array")
     * @param string $actual   The actual type that was encountered (e.g., "null", "boolean", "object")
     */
    public static function forField(string $field, string $expected, string $actual): self
    {
        return new self(sprintf("Invalid value for '%s': expected %s, got %s", $field, $expected, $actual));
    }
}
