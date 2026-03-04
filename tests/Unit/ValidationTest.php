<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Validation\Diagnostic;
use Cline\Hcl\Validation\DiagnosticSeverity;
use Cline\Hcl\Validation\SourceRange;
use Cline\Hcl\Validation\ValidationResult;

describe('SourceRange', function (): void {
    describe('at', function (): void {
        test('creates a single-position range', function (): void {
            $range = SourceRange::at(5, 10, 100);

            expect($range->fromLine)->toBe(5);
            expect($range->fromColumn)->toBe(10);
            expect($range->fromByte)->toBe(100);
            expect($range->toLine)->toBe(5);
            expect($range->toColumn)->toBe(11);
            expect($range->toByte)->toBe(101);
        });
    });

    describe('span', function (): void {
        test('creates a multi-position range', function (): void {
            $range = SourceRange::span(1, 1, 0, 3, 15, 50);

            expect($range->fromLine)->toBe(1);
            expect($range->fromColumn)->toBe(1);
            expect($range->fromByte)->toBe(0);
            expect($range->toLine)->toBe(3);
            expect($range->toColumn)->toBe(15);
            expect($range->toByte)->toBe(50);
        });
    });
});

describe('Diagnostic', function (): void {
    describe('error', function (): void {
        test('creates an error diagnostic', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $diagnostic = Diagnostic::error('Something went wrong', $range);

            expect($diagnostic->severity)->toBe(DiagnosticSeverity::Error);
            expect($diagnostic->message)->toBe('Something went wrong');
            expect($diagnostic->range)->toBe($range);
        });
    });

    describe('warning', function (): void {
        test('creates a warning diagnostic', function (): void {
            $range = SourceRange::at(2, 5, 20);
            $diagnostic = Diagnostic::warning('Potential issue', $range);

            expect($diagnostic->severity)->toBe(DiagnosticSeverity::Warning);
            expect($diagnostic->message)->toBe('Potential issue');
            expect($diagnostic->range)->toBe($range);
        });
    });
});

describe('ValidationResult', function (): void {
    describe('isValid', function (): void {
        test('returns true when no diagnostics', function (): void {
            $result = new ValidationResult([]);

            expect($result->isValid())->toBeTrue();
        });

        test('returns true when only warnings', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $result = new ValidationResult([
                Diagnostic::warning('A warning', $range),
            ]);

            expect($result->isValid())->toBeTrue();
        });

        test('returns false when has errors', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $result = new ValidationResult([
                Diagnostic::error('An error', $range),
            ]);

            expect($result->isValid())->toBeFalse();
        });
    });

    describe('hasErrors', function (): void {
        test('returns false when no errors', function (): void {
            $result = new ValidationResult([]);

            expect($result->hasErrors())->toBeFalse();
        });

        test('returns true when has errors', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $result = new ValidationResult([
                Diagnostic::error('An error', $range),
            ]);

            expect($result->hasErrors())->toBeTrue();
        });
    });

    describe('hasWarnings', function (): void {
        test('returns false when no warnings', function (): void {
            $result = new ValidationResult([]);

            expect($result->hasWarnings())->toBeFalse();
        });

        test('returns true when has warnings', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $result = new ValidationResult([
                Diagnostic::warning('A warning', $range),
            ]);

            expect($result->hasWarnings())->toBeTrue();
        });
    });

    describe('errors', function (): void {
        test('returns only error diagnostics', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $error = Diagnostic::error('An error', $range);
            $warning = Diagnostic::warning('A warning', $range);
            $result = new ValidationResult([$error, $warning]);

            expect($result->errors())->toHaveCount(1);
            expect($result->errors()[0])->toBe($error);
        });
    });

    describe('warnings', function (): void {
        test('returns only warning diagnostics', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $error = Diagnostic::error('An error', $range);
            $warning = Diagnostic::warning('A warning', $range);
            $result = new ValidationResult([$error, $warning]);

            expect($result->warnings())->toHaveCount(1);
            expect($result->warnings()[0])->toBe($warning);
        });
    });

    describe('errorCount', function (): void {
        test('returns count of errors', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $result = new ValidationResult([
                Diagnostic::error('Error 1', $range),
                Diagnostic::error('Error 2', $range),
                Diagnostic::warning('Warning', $range),
            ]);

            expect($result->errorCount())->toBe(2);
        });
    });

    describe('warningCount', function (): void {
        test('returns count of warnings', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $result = new ValidationResult([
                Diagnostic::error('Error', $range),
                Diagnostic::warning('Warning 1', $range),
                Diagnostic::warning('Warning 2', $range),
            ]);

            expect($result->warningCount())->toBe(2);
        });
    });
});
