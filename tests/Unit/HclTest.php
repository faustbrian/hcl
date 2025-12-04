<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Exceptions\ParserException;
use Cline\Hcl\Hcl;

describe('Hcl', function (): void {
    describe('parse', function (): void {
        test('parses simple key-value', function (): void {
            $result = Hcl::parse('key = "value"');

            expect($result)->toBe(['key' => 'value']);
        });

        test('parses numbers', function (): void {
            $result = Hcl::parse('port = 5432');

            expect($result)->toBe(['port' => 5_432]);
        });

        test('parses booleans', function (): void {
            $result = Hcl::parse("enabled = true\ndisabled = false");

            expect($result)->toBe(['enabled' => true, 'disabled' => false]);
        });

        test('parses arrays', function (): void {
            $result = Hcl::parse('tags = ["a", "b", "c"]');

            expect($result)->toBe(['tags' => ['a', 'b', 'c']]);
        });
    });

    describe('parseFile', function (): void {
        test('throws exception for non-existent file', function (): void {
            expect(fn (): array => Hcl::parseFile('/nonexistent/file.hcl'))
                ->toThrow(ParserException::class);
        });
    });

    describe('toJson', function (): void {
        test('converts HCL to pretty JSON', function (): void {
            $json = Hcl::toJson('key = "value"');

            expect($json)->toContain('"key"');
            expect($json)->toContain('"value"');
            expect($json)->toContain("\n"); // pretty print
        });

        test('converts HCL to compact JSON', function (): void {
            $json = Hcl::toJson('key = "value"', false);

            expect($json)->toBe('{"key":"value"}');
        });
    });

    describe('fromJson', function (): void {
        test('converts simple JSON to HCL', function (): void {
            $hcl = Hcl::fromJson('{"key": "value"}');

            expect($hcl)->toContain('key = "value"');
        });

        test('converts JSON with numbers', function (): void {
            $hcl = Hcl::fromJson('{"port": 5432}');

            expect($hcl)->toContain('port = 5432');
        });

        test('converts JSON with booleans', function (): void {
            $hcl = Hcl::fromJson('{"enabled": true, "disabled": false}');

            expect($hcl)->toContain('enabled = true');
            expect($hcl)->toContain('disabled = false');
        });

        test('converts JSON with null', function (): void {
            $hcl = Hcl::fromJson('{"value": null}');

            expect($hcl)->toContain('value = null');
        });

        test('converts JSON with arrays', function (): void {
            $hcl = Hcl::fromJson('{"tags": ["a", "b"]}');

            expect($hcl)->toContain('tags = ["a", "b"]');
        });

        test('converts JSON with empty array', function (): void {
            $hcl = Hcl::fromJson('{"items": []}');

            expect($hcl)->toContain('items = []');
        });

        test('converts JSON with empty object', function (): void {
            $hcl = Hcl::fromJson('{"config": {}}');

            // Empty objects are converted to empty arrays in HCL
            expect($hcl)->toContain('config = []');
        });

        test('converts JSON with nested objects', function (): void {
            $hcl = Hcl::fromJson('{"outer": {"inner": "value"}}');

            expect($hcl)->toContain('outer');
            expect($hcl)->toContain('inner = "value"');
        });
    });

    describe('arrayToHcl', function (): void {
        test('formats keys that need quoting', function (): void {
            $hcl = Hcl::arrayToHcl(['key-with-dash' => 'value']);

            expect($hcl)->toContain('"key-with-dash"');
        });

        test('does not quote simple keys', function (): void {
            $hcl = Hcl::arrayToHcl(['simple_key' => 'value']);

            expect($hcl)->toContain('simple_key = ');
            expect($hcl)->not->toContain('"simple_key"');
        });

        test('handles floats', function (): void {
            $hcl = Hcl::arrayToHcl(['pi' => 3.14]);

            expect($hcl)->toContain('pi = 3.14');
        });

        test('escapes strings with special characters', function (): void {
            $hcl = Hcl::arrayToHcl(['message' => "line1\nline2"]);

            expect($hcl)->toContain('\\n');
        });

        test('escapes strings with quotes', function (): void {
            $hcl = Hcl::arrayToHcl(['quote' => 'say "hello"']);

            expect($hcl)->toContain('\\"hello\\"');
        });

        test('escapes strings with backslashes', function (): void {
            $hcl = Hcl::arrayToHcl(['path' => 'C:\\Users']);

            expect($hcl)->toContain('\\\\');
        });

        test('escapes strings with tabs', function (): void {
            $hcl = Hcl::arrayToHcl(['text' => "col1\tcol2"]);

            expect($hcl)->toContain('\\t');
        });

        test('escapes strings with carriage return', function (): void {
            $hcl = Hcl::arrayToHcl(['text' => "line1\rline2"]);

            expect($hcl)->toContain('\\r');
        });

        test('formats long arrays on multiple lines', function (): void {
            $hcl = Hcl::arrayToHcl([
                'items' => [
                    'this is a very long string item one',
                    'this is a very long string item two',
                    'this is a very long string item three',
                ],
            ]);

            // Should contain newlines for multi-line formatting
            expect($hcl)->toContain("[\n");
        });

        test('handles block structures with nested blocks', function (): void {
            $hcl = Hcl::arrayToHcl([
                'resource' => [
                    'aws_instance' => [
                        'web' => [
                            'ami' => 'ami-123',
                        ],
                    ],
                ],
            ]);

            expect($hcl)->toContain('resource');
            expect($hcl)->toContain('"aws_instance"');
            expect($hcl)->toContain('"web"');
            expect($hcl)->toContain('ami = "ami-123"');
        });

        test('handles block with simple values', function (): void {
            $hcl = Hcl::arrayToHcl([
                'variable' => [
                    'name' => 'default_value',
                ],
            ]);

            expect($hcl)->toContain('variable');
        });
    });
});
