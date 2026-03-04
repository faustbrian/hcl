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
 * Exception thrown when a heredoc syntax is malformed during lexical analysis.
 *
 * This exception is raised when the lexer encounters a heredoc that violates HCL
 * heredoc syntax rules, such as missing closing delimiters, mismatched indentation,
 * invalid delimiter names, or unterminated heredoc blocks. Heredocs in HCL are used
 * for multi-line string literals and must follow specific formatting requirements.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MalformedHeredocException extends LexerException
{
    /**
     * Create an exception for a malformed heredoc at a specific location.
     *
     * @param int    $line   The line number in the source file where the heredoc started
     * @param int    $column The column number in the source file where the heredoc delimiter begins
     * @param string $reason The specific reason why the heredoc is malformed (e.g., "missing closing
     *                       delimiter", "invalid delimiter name", "unterminated block")
     */
    public static function at(int $line, int $column, string $reason): self
    {
        return new self(sprintf('Malformed heredoc at line %d, column %d: %s', $line, $column, $reason));
    }
}
