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
 * Exception thrown when a required field is missing from an HCL configuration block.
 *
 * This exception is raised during parsing when a mandatory field is absent from a
 * configuration block definition. For example, if a "credential" block requires a
 * "type" field but it's not provided, or if a "group" block is missing its required
 * "name" field. Used for validation of configuration completeness.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingRequiredFieldException extends ParserException
{
    /**
     * Create an exception for a missing required field within a specific context.
     *
     * @param string $field   The name of the required field that is missing from the configuration
     * @param string $context The context or block type where the field is required (e.g., "credential",
     *                        "group", "resource"), used to help users identify where to add the field
     */
    public static function inContext(string $field, string $context): self
    {
        return new self(sprintf("Missing required field '%s' in %s", $field, $context));
    }
}
