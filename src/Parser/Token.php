<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Parser;

use function in_array;

/**
 * Represents a token in the HCL lexer stream.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Token
{
    /**
     * Create a new token instance.
     *
     * @param TokenType $type   The token type
     * @param string    $value  The raw token value
     * @param int       $line   The line number where the token appears
     * @param int       $column The column number where the token starts
     */
    public function __construct(
        public TokenType $type,
        public string $value,
        public int $line,
        public int $column,
    ) {}

    /**
     * Check if this token is of the given type.
     *
     * @param  TokenType $type The type to check against
     * @return bool      True if the token matches the type
     */
    public function is(TokenType $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Check if this token is one of the given types.
     *
     * @param  TokenType ...$types The types to check against
     * @return bool      True if the token matches any of the types
     */
    public function isOneOf(TokenType ...$types): bool
    {
        return in_array($this->type, $types, true);
    }
}
