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

use function sprintf;

/**
 * Exception thrown when an unexpected token is encountered during parsing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedTokenException extends ParserException
{
    /**
     * Create an exception for an unexpected token.
     *
     * @param  Token            $token    The unexpected token encountered during parsing
     * @param  string|TokenType $expected The expected token type or description of what was expected
     * @return self             The exception instance with detailed error message including position
     */
    public static function at(Token $token, TokenType|string $expected): self
    {
        $expectedStr = $expected instanceof TokenType ? $expected->value : $expected;

        return new self(
            sprintf("Unexpected token '%s' (%s) at line %d, column %d. Expected %s", $token->value, $token->type->value, $token->line, $token->column, $expectedStr),
        );
    }
}
