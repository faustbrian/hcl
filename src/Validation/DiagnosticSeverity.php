<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Validation;

/**
 * Diagnostic severity levels.
 *
 * Defines the severity classification for validation diagnostics. Errors indicate
 * critical issues that prevent successful parsing or violate language specification,
 * while warnings highlight potential problems or non-idiomatic code patterns.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum DiagnosticSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
