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
 * Exception thrown when the lexer encounters an unexpected character during tokenization.
 *
 * This exception is raised when the lexer finds a character that is not valid in the
 * current lexical context, such as an illegal symbol in an identifier, an invalid
 * escape sequence, or a character that doesn't belong in the HCL syntax at that position.
 * This is one of the most common lexer errors indicating syntax violations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedCharacterException extends LexerException
{
    /**
     * Create an exception for an unexpected character at a specific location.
     *
     * @param string $char   The unexpected character that was encountered, typically a single
     *                       character but may be a character description for non-printable characters
     * @param int    $line   The line number in the source file where the unexpected character was found
     * @param int    $column The column number in the source file where the unexpected character appears
     */
    public static function at(string $char, int $line, int $column): self
    {
        return new self(sprintf("Unexpected character '%s' at line %d, column %d", $char, $line, $column));
    }
}
