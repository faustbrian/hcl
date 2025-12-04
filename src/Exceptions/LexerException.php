<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when the lexer encounters an error.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LexerException extends RuntimeException
{
    /**
     * Create an exception for an unexpected character.
     *
     * @param string $char   The unexpected character
     * @param int    $line   The line number
     * @param int    $column The column number
     */
    public static function unexpectedCharacter(string $char, int $line, int $column): self
    {
        return new self(sprintf("Unexpected character '%s' at line %d, column %d", $char, $line, $column));
    }

    /**
     * Create an exception for an unterminated string.
     *
     * @param int $line   The line number where the string started
     * @param int $column The column number where the string started
     */
    public static function unterminatedString(int $line, int $column): self
    {
        return new self(sprintf('Unterminated string starting at line %d, column %d', $line, $column));
    }

    /**
     * Create an exception for an unterminated comment.
     *
     * @param int $line   The line number where the comment started
     * @param int $column The column number where the comment started
     */
    public static function unterminatedComment(int $line, int $column): self
    {
        return new self(sprintf('Unterminated multi-line comment starting at line %d, column %d', $line, $column));
    }

    /**
     * Create an exception for a malformed heredoc.
     *
     * @param int    $line   The line number where the heredoc started
     * @param int    $column The column number where the heredoc started
     * @param string $reason The reason the heredoc is malformed
     */
    public static function malformedHeredoc(int $line, int $column, string $reason): self
    {
        return new self(sprintf('Malformed heredoc at line %d, column %d: %s', $line, $column, $reason));
    }
}
