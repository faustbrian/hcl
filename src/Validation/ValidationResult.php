<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Validation;

use function array_filter;
use function array_values;
use function count;

/**
 * Result of validating HCL content.
 *
 * Encapsulates validation diagnostics produced during HCL parsing and validation,
 * providing convenient methods to query errors, warnings, and validation status.
 * Used to communicate validation outcomes without throwing exceptions.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param array<Diagnostic> $diagnostics Collection of diagnostics (errors and warnings) found
     *                                       during validation. Empty array indicates successful
     *                                       validation with no issues detected.
     */
    public function __construct(
        public array $diagnostics = [],
    ) {}

    /**
     * Check if validation passed (no errors).
     *
     * @return bool True if no errors exist (warnings allowed).
     */
    public function isValid(): bool
    {
        return $this->errorCount() === 0;
    }

    /**
     * Check if there are any errors.
     *
     * @return bool True if at least one error diagnostic exists.
     */
    public function hasErrors(): bool
    {
        return $this->errorCount() > 0;
    }

    /**
     * Check if there are any warnings.
     *
     * @return bool True if at least one warning diagnostic exists.
     */
    public function hasWarnings(): bool
    {
        return $this->warningCount() > 0;
    }

    /**
     * Get all error diagnostics.
     *
     * @return array<Diagnostic> Filtered array containing only error-level diagnostics.
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (Diagnostic $d): bool => $d->severity === DiagnosticSeverity::Error,
        ));
    }

    /**
     * Get all warning diagnostics.
     *
     * @return array<Diagnostic> Filtered array containing only warning-level diagnostics.
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (Diagnostic $d): bool => $d->severity === DiagnosticSeverity::Warning,
        ));
    }

    /**
     * Get the number of errors.
     *
     * @return int Count of error-level diagnostics.
     */
    public function errorCount(): int
    {
        return count($this->errors());
    }

    /**
     * Get the number of warnings.
     *
     * @return int Count of warning-level diagnostics.
     */
    public function warningCount(): int
    {
        return count($this->warnings());
    }
}
