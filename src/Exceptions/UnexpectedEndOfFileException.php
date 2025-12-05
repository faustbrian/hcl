<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Exceptions;

/**
 * Exception thrown when end of file is reached unexpectedly during parsing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedEndOfFileException extends ParserException
{
    /**
     * Create an exception for an unexpected end of file.
     *
     * @param  string $context What was expected when EOF was encountered
     * @return self   The exception instance
     */
    public static function whileParsing(string $context): self
    {
        return new self('Unexpected end of file while parsing '.$context);
    }
}
