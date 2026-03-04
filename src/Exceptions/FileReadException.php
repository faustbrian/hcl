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
 * Exception thrown when an HCL configuration file cannot be read from the filesystem.
 *
 * This exception is raised when attempting to load an HCL file that doesn't exist,
 * lacks read permissions, or encounters other I/O errors during file access. This
 * is typically the first exception thrown in the parsing pipeline before lexing begins.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class FileReadException extends RuntimeException implements HclException
{
    /**
     * Create an exception for a failed file read operation.
     *
     * @param string $path The absolute or relative path to the file that could not be read,
     *                     used for error reporting and debugging file access issues
     */
    public static function forPath(string $path): self
    {
        return new self('Failed to read file: '.$path);
    }
}
