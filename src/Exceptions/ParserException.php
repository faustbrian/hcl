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
 * Base exception for all parser-related errors during HCL syntax analysis.
 *
 * This abstract exception serves as the parent class for all exceptions thrown during
 * the parsing phase of HCL processing. The parser operates after lexical analysis and
 * is responsible for building the abstract syntax tree (AST) from tokens, validating
 * structure, checking for duplicate definitions, verifying required fields, and ensuring
 * values match expected types. Extend this class to create specific parser error types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class ParserException extends RuntimeException implements HclException
{
    // Abstract base - no factory methods
}
