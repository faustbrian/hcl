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
 * Exception thrown when an invalid or unrecognized block type is encountered during parsing.
 *
 * This exception is raised when the parser encounters a block type that is not defined
 * in the HCL schema or configuration, such as an unknown top-level block like "unknown_block"
 * when only "credential", "group", or specific block types are permitted in the configuration.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidBlockTypeException extends ParserException
{
    /**
     * Create an exception for an invalid block type at a specific location.
     *
     * @param string $type   The invalid block type name that was encountered in the configuration
     * @param int    $line   The line number in the source file where the invalid block type was found
     * @param int    $column The column number in the source file where the invalid block type begins
     */
    public static function at(string $type, int $line, int $column): self
    {
        return new self(sprintf("Invalid block type '%s' at line %d, column %d", $type, $line, $column));
    }
}
