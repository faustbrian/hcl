<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Exceptions;

use Cline\Hcl\Parser\Token;
use Cline\Hcl\Parser\TokenType;
use RuntimeException;

use function sprintf;

/**
 * Exception thrown when the parser encounters an error.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ParserException extends RuntimeException
{
    /**
     * Create an exception for an unexpected token.
     *
     * @param Token            $token    The unexpected token
     * @param string|TokenType $expected The expected token type or description
     */
    public static function unexpectedToken(Token $token, TokenType|string $expected): self
    {
        $expectedStr = $expected instanceof TokenType ? $expected->value : $expected;

        return new self(
            sprintf("Unexpected token '%s' (%s) at line %d, column %d. Expected %s", $token->value, $token->type->value, $token->line, $token->column, $expectedStr),
        );
    }

    /**
     * Create an exception for an unexpected end of file.
     *
     * @param string $context What was expected
     */
    public static function unexpectedEof(string $context): self
    {
        return new self('Unexpected end of file while parsing '.$context);
    }

    /**
     * Create an exception for an invalid block type.
     *
     * @param string $type   The invalid block type
     * @param int    $line   The line number
     * @param int    $column The column number
     */
    public static function invalidBlockType(string $type, int $line, int $column): self
    {
        return new self(sprintf("Invalid block type '%s' at line %d, column %d", $type, $line, $column));
    }

    /**
     * Create an exception for a missing required field.
     *
     * @param string $field   The missing field name
     * @param string $context The context (e.g., "credential", "group")
     */
    public static function missingRequiredField(string $field, string $context): self
    {
        return new self(sprintf("Missing required field '%s' in %s", $field, $context));
    }

    /**
     * Create an exception for an invalid value.
     *
     * @param string $field    The field name
     * @param string $expected The expected type
     * @param string $actual   The actual type
     */
    public static function invalidValue(string $field, string $expected, string $actual): self
    {
        return new self(sprintf("Invalid value for '%s': expected %s, got %s", $field, $expected, $actual));
    }

    /**
     * Create an exception for a duplicate definition.
     *
     * @param string $type The type (e.g., "credential", "group")
     * @param string $name The duplicate name
     */
    public static function duplicateDefinition(string $type, string $name): self
    {
        return new self(sprintf("Duplicate %s definition: '%s'", $type, $name));
    }

    /**
     * Create an exception for an unresolved reference.
     *
     * @param string $reference The unresolved reference
     */
    public static function unresolvedReference(string $reference): self
    {
        return new self(sprintf("Unresolved reference: '%s'", $reference));
    }
}
