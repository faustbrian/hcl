<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Testing;

use Cline\Hcl\Validation\SourceRange;

/**
 * Represents an expected diagnostic from a .t specification file.
 *
 * Used in test specifications to define expected validation errors or warnings
 * at specific source locations. This enables precise testing of parser diagnostic
 * output by comparing actual diagnostics against these expectations.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ExpectedDiagnostic
{
    /**
     * Create a new expected diagnostic.
     *
     * @param string      $severity Diagnostic severity level, either 'error' or 'warning'.
     *                              Determines the expected severity of the diagnostic that
     *                              should be produced during parsing or validation.
     * @param SourceRange $range    Expected source location where the diagnostic should occur.
     *                              Specifies the exact line, column, and byte offsets that
     *                              the diagnostic should reference in the source code.
     */
    public function __construct(
        public string $severity,
        public SourceRange $range,
    ) {}
}
