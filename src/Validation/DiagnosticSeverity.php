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
 * @author Brian Faust <brian@cline.sh>
 */
enum DiagnosticSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
