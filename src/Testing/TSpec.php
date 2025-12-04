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
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class TSpec
{
    /**
     * Create a new TSpec.
     *
     * @param mixed                     $result      The expected result value
     * @param mixed                     $resultType  The expected result type specification
     * @param array<ExpectedDiagnostic> $diagnostics Expected diagnostics
     */
    public function __construct(
        public mixed $result,
        public mixed $resultType,
        public array $diagnostics,
    ) {}

    /**
     * Parse a .t specification from content.
     *
     * @param  string $content The .t file content
     * @return self   The parsed specification
     */
    public static function parse(string $content): self
    {
        return new TSpecParser()->parse($content);
    }

    /**
     * Parse a .t specification from a file path.
     *
     * @param  string $path The path to the .t file
     * @return self   The parsed specification
     */
    public static function fromFile(string $path): self
    {
        return new TSpecParser()->parseFile($path);
    }

    /**
     * Check if this spec expects errors.
     */
    public function expectsErrors(): bool
    {
        return array_any($this->diagnostics, fn ($diagnostic): bool => $diagnostic->severity === 'error');
    }

    /**
     * Check if this spec expects warnings.
     */
    public function expectsWarnings(): bool
    {
        return array_any($this->diagnostics, fn ($diagnostic): bool => $diagnostic->severity === 'warning');
    }

    /**
     * Check if this spec expects successful parsing (no errors).
     */
    public function expectsSuccess(): bool
    {
        return !$this->expectsErrors();
    }

    /**
     * Get expected error count.
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
     * @return array<ExpectedDiagnostic>
     */
    public function expectedErrors(): array
    {
        return array_values(array_filter(
            $this->diagnostics,
            static fn (ExpectedDiagnostic $d): bool => $d->severity === 'error',
        ));
    }
}
