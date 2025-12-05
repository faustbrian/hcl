<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Validation;

/**
 * Represents a validation diagnostic (error or warning).
 *
 * Diagnostics are produced during HCL parsing and validation to report syntax errors,
 * semantic issues, or warnings about problematic code patterns. Each diagnostic includes
 * a severity level, descriptive message, and precise source location for error reporting.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Diagnostic
{
    /**
     * Create a new diagnostic.
     *
     * @param DiagnosticSeverity $severity Severity level indicating whether this is an error
     *                                     or warning. Errors prevent successful parsing while
     *                                     warnings indicate potential issues.
     * @param string             $message  Human-readable description of the diagnostic issue,
     *                                     explaining what went wrong and potentially how to fix it.
     * @param SourceRange        $range    Source code location where the issue occurs, including
     *                                     line, column, and byte positions for precise error reporting.
     */
    public function __construct(
        public DiagnosticSeverity $severity,
        public string $message,
        public SourceRange $range,
    ) {}

    /**
     * Create an error diagnostic.
     *
     * @param  string      $message Human-readable error description.
     * @param  SourceRange $range   Source location of the error.
     * @return self        New diagnostic with error severity.
     */
    public static function error(string $message, SourceRange $range): self
    {
        return new self(DiagnosticSeverity::Error, $message, $range);
    }

    /**
     * Create a warning diagnostic.
     *
     * @param  string      $message Human-readable warning description.
     * @param  SourceRange $range   Source location of the warning.
     * @return self        New diagnostic with warning severity.
     */
    public static function warning(string $message, SourceRange $range): self
    {
        return new self(DiagnosticSeverity::Warning, $message, $range);
    }
}
