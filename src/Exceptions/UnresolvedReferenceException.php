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
 * Exception thrown when a reference cannot be resolved.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnresolvedReferenceException extends ParserException
{
    /**
     * Create an exception for an unresolved reference.
     *
     * @param  string $reference The unresolved reference identifier that could not be resolved
     * @return self   The exception instance with the reference identifier in the error message
     */
    public static function forReference(string $reference): self
    {
        return new self(sprintf("Unresolved reference: '%s'", $reference));
    }
}
