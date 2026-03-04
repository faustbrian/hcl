<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Testing\TSpec;
use Cline\Hcl\Validation\HclValidator;

/**
 * HCL Validator Tests.
 *
 * These tests validate our HCL validator against the official HashiCorp HCL
 * specification test fixtures that define expected diagnostics.
 */
describe('HclValidator', function (): void {
    beforeEach(function (): void {
        $this->validator = new HclValidator();
    });

    describe('valid syntax (from .t specs)', function (): void {
        test('accepts empty file per empty.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/empty.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/empty.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
            expect($result->errorCount())->toBe(0);
        });

        test('accepts simple attributes per expected.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/attributes/expected.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/attributes/expected.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });

        test('accepts empty single-line block per single_empty_oneline.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_empty_oneline.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/blocks/single_empty_oneline.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });

        test('accepts single-line block with one attribute per single_oneline.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_oneline.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/blocks/single_oneline.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });

        test('accepts multi-line block per single_expected.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_expected.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/blocks/single_expected.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });

        test('accepts hash comments per hash_comment.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/comments/hash_comment.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/comments/hash_comment.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });

        test('accepts slash comments per slash_comment.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/comments/slash_comment.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/comments/slash_comment.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });

        test('accepts multiline comments per multiline_comment.t', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/comments/multiline_comment.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/comments/multiline_comment.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsSuccess())->toBeTrue();
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('specsuite/structure/attributes/singleline_bad.t', function (): void {
        test('rejects comma-separated attributes with correct diagnostic location', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/attributes/singleline_bad.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/attributes/singleline_bad.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsErrors())->toBeTrue();
            expect($result->hasErrors())->toBeTrue();
            expect($result->errorCount())->toBeGreaterThanOrEqual($tspec->expectedErrorCount());

            // Verify first error location matches .t spec
            $expectedError = $tspec->expectedErrors()[0];
            $actualError = $result->errors()[0];

            expect($actualError->range->fromLine)->toBe($expectedError->range->fromLine);
            expect($actualError->range->fromColumn)->toBe($expectedError->range->fromColumn);
        });
    });

    describe('specsuite/structure/blocks/single_unclosed.t', function (): void {
        test('rejects unclosed block with diagnostic', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_unclosed.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/blocks/single_unclosed.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsErrors())->toBeTrue();
            expect($result->hasErrors())->toBeTrue();
        });
    });

    describe('specsuite/structure/blocks/single_oneline_invalid.t', function (): void {
        test('rejects invalid single-line block constructs', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_oneline_invalid.hcl'));
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/blocks/single_oneline_invalid.t'));

            $result = $this->validator->validate($hcl);

            expect($tspec->expectsErrors())->toBeTrue();
            expect($result->hasErrors())->toBeTrue();

            // Should have multiple errors as per the .t file
            expect($result->errorCount())->toBeGreaterThanOrEqual(1);
        });

        test('first error location matches .t spec for multiple attributes', function (): void {
            // Test the specific case: a { b = "foo", c = "bar" }
            $hcl = 'a { b = "foo", c = "bar" }';

            $result = $this->validator->validate($hcl);

            expect($result->hasErrors())->toBeTrue();

            // First error should be at the comma (column 14)
            $firstError = $result->errors()[0];
            expect($firstError->range->fromLine)->toBe(1);
            expect($firstError->range->fromColumn)->toBe(14);
        });

        test('detects nested block in single-line block', function (): void {
            // From single_oneline_invalid.hcl: a { d {} }
            $hcl = 'a { d {} }';

            $result = $this->validator->validate($hcl);

            expect($result->hasErrors())->toBeTrue();
            expect($result->errors()[0]->message)->toContain('single-line block');
        });
    });

    describe('specsuite/structure/attributes/unexpected.t (schema validation)', function (): void {
        test('unexpected.t defines schema errors not syntax errors', function (): void {
            // This test documents that unexpected.t is for schema validation
            // (validating against a spec), not syntax validation
            $tspec = TSpec::fromFile(testFixture('specsuite/structure/attributes/unexpected.t'));

            // The .t file expects errors, but they're schema errors
            expect($tspec->expectsErrors())->toBeTrue();

            // Our syntax validator should accept this as valid HCL syntax
            $hcl = file_get_contents(testFixture('specsuite/structure/attributes/unexpected.hcl'));
            $result = $this->validator->validate($hcl);

            // Valid syntax, invalid schema (but we don't validate schema)
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('validation result API', function (): void {
        test('isValid returns true when no errors', function (): void {
            $result = $this->validator->validate('a = 1');

            expect($result->isValid())->toBeTrue();
            expect($result->hasErrors())->toBeFalse();
        });

        test('isValid returns false when has errors', function (): void {
            $result = $this->validator->validate('a { d {} }');

            expect($result->isValid())->toBeFalse();
            expect($result->hasErrors())->toBeTrue();
        });

        test('errors returns only error diagnostics', function (): void {
            $result = $this->validator->validate('a { d {} }');

            $errors = $result->errors();

            expect($errors)->not->toBeEmpty();

            foreach ($errors as $error) {
                expect($error->severity->value)->toBe('error');
            }
        });

        test('errorCount returns correct count', function (): void {
            $result = $this->validator->validate('a { b = 1, c = 2, d {} }');

            // Multiple errors: comma after b, comma after c, nested block d
            expect($result->errorCount())->toBeGreaterThanOrEqual(2);
        });
    });
});
