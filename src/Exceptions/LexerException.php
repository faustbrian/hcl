<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Exceptions;

use RuntimeException;

/**
 * Base exception for all lexer-related errors during HCL tokenization.
 *
 * This abstract exception serves as the parent class for all exceptions thrown during
 * the lexical analysis phase of HCL parsing. The lexer is responsible for breaking the
 * raw HCL source text into tokens, and this exception hierarchy handles errors like
 * unexpected characters, malformed strings, or invalid heredoc syntax encountered during
 * tokenization. Extend this class to create specific lexer error types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class LexerException extends RuntimeException implements HclException
{
    // Abstract base - no factory methods
}
