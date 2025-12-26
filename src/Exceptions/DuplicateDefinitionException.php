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
 * Exception thrown when a duplicate definition is encountered during HCL parsing.
 *
 * This exception is raised when the parser detects multiple definitions of the same
 * entity within a single configuration file, such as duplicate credential blocks,
 * group blocks, or other named definitions where uniqueness is required.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DuplicateDefinitionException extends ParserException
{
    /**
     * Create an exception for a duplicate definition.
     *
     * @param string $type The type of definition (e.g., "credential", "group", "resource")
     *                     that was duplicated in the configuration file
     * @param string $name The name of the duplicate definition that caused the conflict
     */
    public static function forType(string $type, string $name): self
    {
        return new self(sprintf("Duplicate %s definition: '%s'", $type, $name));
    }
}
