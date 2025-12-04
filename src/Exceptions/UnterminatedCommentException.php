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
 * Exception thrown when a multi-line comment is not properly terminated.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnterminatedCommentException extends LexerException
{
    /**
     * Create an exception for an unterminated comment.
     *
     * @param  int  $line   The line number where the unterminated comment started
     * @param  int  $column The column number where the unterminated comment started
     * @return self The exception instance with position information in the error message
     */
    public static function at(int $line, int $column): self
    {
        return new self(sprintf('Unterminated multi-line comment starting at line %d, column %d', $line, $column));
    }
}
