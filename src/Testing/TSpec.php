<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Testing;

use function array_any;
use function array_filter;
use function array_values;

/**
 * Represents a parsed .t specification file.
 *
 * Test specification files (.t) define expected behavior for HCL parsing tests,
 * including expected parse results, type specifications, and diagnostic messages.
 * This class provides methods to query expectations and validate parser output.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class TSpec
{
    /**
     * Create a new TSpec.
     *
     * @param mixed                     $result      Expected parsed value that the HCL parser should produce.
     *                                               Can be any valid PHP type (array, string, number, bool, null)
     *                                               representing the expected parse tree or evaluation result.
     * @param mixed                     $resultType  Expected type specification for the parsed result.
     *                                               Defines the expected type structure that should be inferred
     *                                               from the parsed HCL content during type checking.
     * @param array<ExpectedDiagnostic> $diagnostics Collection of expected diagnostic messages (errors/warnings)
     *                                               that should be produced during parsing. Empty array indicates
     *                                               successful parsing with no expected issues.
     */
    public function __construct(
        public mixed $result,
        public mixed $resultType,
        public array $diagnostics,
    ) {}

    /**
     * Parse a .t specification from content.
     *
     * @param  string $content HCL-formatted .t specification content defining test expectations.
     * @return self   Parsed specification with expected results and diagnostics.
     */
    public static function parse(string $content): self
    {
        return new TSpecParser()->parse($content);
    }

    /**
     * Parse a .t specification from a file path.
     *
     * @param  string $path Filesystem path to the .t specification file.
     * @return self   Parsed specification loaded from the file.
     */
    public static function fromFile(string $path): self
    {
        return new TSpecParser()->parseFile($path);
    }

    /**
     * Check if this spec expects errors.
     *
     * @return bool True if at least one error diagnostic is expected.
     */
    public function expectsErrors(): bool
    {
        return array_any($this->diagnostics, fn ($diagnostic): bool => $diagnostic->severity === 'error');
    }

    /**
     * Check if this spec expects warnings.
     *
     * @return bool True if at least one warning diagnostic is expected.
     */
    public function expectsWarnings(): bool
    {
        return array_any($this->diagnostics, fn ($diagnostic): bool => $diagnostic->severity === 'warning');
    }

    /**
     * Check if this spec expects successful parsing (no errors).
     *
     * @return bool True if no error diagnostics are expected (warnings allowed).
     */
    public function expectsSuccess(): bool
    {
        return !$this->expectsErrors();
    }

    /**
     * Get expected error count.
     *
     * @return int Number of error diagnostics expected (excludes warnings).
     */
    public function expectedErrorCount(): int
    {
        $count = 0;

        foreach ($this->diagnostics as $diagnostic) {
            if ($diagnostic->severity !== 'error') {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    /**
     * Get all expected errors.
     *
     * @return array<ExpectedDiagnostic> Filtered array containing only error diagnostics.
     */
    public function expectedErrors(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (ExpectedDiagnostic $d): bool => $d->severity === 'error',
        ));
    }
}
