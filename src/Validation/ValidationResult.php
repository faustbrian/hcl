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
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param array<Diagnostic> $diagnostics The diagnostics found
     */
    public function __construct(
        public array $diagnostics = [],
    ) {}

    /**
     * Check if validation passed (no errors).
     */
    public function isValid(): bool
    {
        return $this->errorCount() === 0;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return $this->errorCount() > 0;
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return $this->warningCount() > 0;
    }

    /**
     * Get all error diagnostics.
     *
     * @return array<Diagnostic>
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
     * @return array<Diagnostic>
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
     */
    public function errorCount(): int
    {
        return count($this->errors());
    }

    /**
     * Get the number of warnings.
     */
    public function warningCount(): int
    {
        return count($this->warnings());
    }
}
