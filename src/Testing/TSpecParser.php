<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Testing;

use Cline\Hcl\Exceptions\FileReadException;
use Cline\Hcl\Hcl;
use Cline\Hcl\Validation\SourceRange;

use function array_keys;
use function count;
use function file_get_contents;
use function is_array;
use function range;
use function throw_if;

/**
 * Parses .t specsuite expectation files.
 *
 * Test specification files (.t) define expected behavior for HCL parser tests using
 * HCL syntax. These files specify expected parse results, type specifications, and
 * diagnostic messages to validate parser correctness.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TSpecParser
{
    /**
     * Parse a .t specification file from content.
     *
     * @param  string $content HCL-formatted .t specification content to parse.
     * @return TSpec  Parsed test specification with expectations.
     */
    public function parse(string $content): TSpec
    {
        $parsed = Hcl::parse($content);

        $result = $parsed['result'] ?? null;
        $resultType = $parsed['result_type'] ?? null;
        $diagnostics = $this->parseDiagnostics($parsed['diagnostics'] ?? null);

        return new TSpec($result, $resultType, $diagnostics);
    }

    /**
     * Parse a .t specification file from a file path.
     *
     * @param  string            $path Filesystem path to the .t specification file.
     * @throws FileReadException When the file cannot be read.
     * @return TSpec             Parsed test specification loaded from file.
     */
    public function parseFile(string $path): TSpec
    {
        $content = file_get_contents($path);

        throw_if($content === false, FileReadException::forPath($path));

        return $this->parse($content);
    }

    /**
     * Parse diagnostics block into structured array.
     *
     * @param  mixed                     $diagnosticsBlock Parsed HCL diagnostics block containing
     *                                                     error and warning specifications.
     * @return array<ExpectedDiagnostic> Array of expected diagnostic objects.
     */
    private function parseDiagnostics(mixed $diagnosticsBlock): array
    {
        if (!is_array($diagnosticsBlock)) {
            return [];
        }

        $result = [];

        // The diagnostics block contains error/warning blocks
        if (isset($diagnosticsBlock['error'])) {
            $errors = $diagnosticsBlock['error'];

            // Could be single error or array of errors
            if (is_array($errors) && $this->isAssociativeArray($errors)) {
                /** @var array<string, mixed> $errors */
                $result[] = $this->parseExpectedDiagnostic('error', $errors);
            } elseif (is_array($errors)) {
                foreach ($errors as $error) {
                    if (!is_array($error)) {
                        continue;
                    }

                    /** @var array<string, mixed> $error */
                    $result[] = $this->parseExpectedDiagnostic('error', $error);
                }
            }
        }

        if (isset($diagnosticsBlock['warning'])) {
            $warnings = $diagnosticsBlock['warning'];

            if (is_array($warnings) && $this->isAssociativeArray($warnings)) {
                /** @var array<string, mixed> $warnings */
                $result[] = $this->parseExpectedDiagnostic('warning', $warnings);
            } elseif (is_array($warnings)) {
                foreach ($warnings as $warning) {
                    if (!is_array($warning)) {
                        continue;
                    }

                    /** @var array<string, mixed> $warning */
                    $result[] = $this->parseExpectedDiagnostic('warning', $warning);
                }
            }
        }

        return $result;
    }

    /**
     * Parse a single expected diagnostic.
     *
     * @param  string               $severity Severity level ('error' or 'warning').
     * @param  array<string, mixed> $data     Diagnostic data with 'from' and 'to' position specifications.
     * @return ExpectedDiagnostic   Constructed diagnostic expectation.
     */
    private function parseExpectedDiagnostic(string $severity, array $data): ExpectedDiagnostic
    {
        /** @var array{line?: int, column?: int, byte?: int} $from */
        $from = is_array($data['from'] ?? null) ? $data['from'] : [];

        /** @var array{line?: int, column?: int, byte?: int} $to */
        $to = is_array($data['to'] ?? null) ? $data['to'] : [];

        $fromLine = $from['line'] ?? 1;
        $fromColumn = $from['column'] ?? 1;
        $fromByte = $from['byte'] ?? 0;

        $range = SourceRange::span(
            $fromLine,
            $fromColumn,
            $fromByte,
            $to['line'] ?? $fromLine,
            $to['column'] ?? $fromColumn,
            $to['byte'] ?? $fromByte,
        );

        return new ExpectedDiagnostic($severity, $range);
    }

    /**
     * Check if array is associative (has string keys).
     *
     * @param  mixed $arr Value to check for associative array structure.
     * @return bool  True if array has non-sequential integer or string keys.
     */
    private function isAssociativeArray(mixed $arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }

        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
