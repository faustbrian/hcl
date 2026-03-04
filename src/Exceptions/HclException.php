<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Exceptions;

use Throwable;

/**
 * Marker interface for all HCL package exceptions.
 *
 * This interface serves as a common parent for all exceptions thrown by the HCL
 * parser library, enabling consumers to catch any HCL-related exception with a
 * single catch block. All custom exceptions in this package implement this interface,
 * including lexer errors, parser errors, and file I/O exceptions.
 *
 * ```php
 * try {
 *     $config = $parser->parse($hclContent);
 * } catch (HclException $e) {
 *     // Handle any HCL parsing error
 *     logger()->error('HCL parsing failed: ' . $e->getMessage());
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface HclException extends Throwable
{
    // Marker interface - no methods required
}
