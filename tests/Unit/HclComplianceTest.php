<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Hcl;

/**
 * HCL Compliance Tests.
 *
 * These tests validate our parser against the official HashiCorp HCL
 * specification test fixtures from https://github.com/hashicorp/hcl
 */
describe('HCL Compliance', function (): void {
    describe('empty files', function (): void {
        test('parses empty file', function (): void {
            $result = Hcl::parseFile(testFixture('specsuite/empty.hcl'));

            expect($result)->toBe([]);
        });
    });

    describe('comments', function (): void {
        test('parses hash comments', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/comments/hash_comment.hcl'));
            $result = Hcl::parse($hcl);

            // Comments are stripped, empty result
            expect($result)->toBe([]);
        });

        test('parses slash comments', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/comments/slash_comment.hcl'));
            $result = Hcl::parse($hcl);

            expect($result)->toBe([]);
        });

        test('parses multiline comments', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/comments/multiline_comment.hcl'));
            $result = Hcl::parse($hcl);

            expect($result)->toBe([]);
        });
    });

    describe('structure/attributes', function (): void {
        test('parses expected attributes', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/attributes/expected.hcl'));
            $result = Hcl::parse($hcl);

            expect($result)->toHaveKey('a');
            expect($result)->toHaveKey('b');
            expect($result)->toHaveKey('c');
            expect($result['a'])->toBe('a value');
            expect($result['b'])->toBe('b value');
            expect($result['c'])->toBe('c value');
        });
    });

    describe('structure/blocks', function (): void {
        test('parses empty block', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_expected.hcl'));
            $result = Hcl::parse($hcl);

            expect($result)->toHaveKey('a');
            expect($result['a'])->toBe([]);
        });

        test('parses empty oneline block', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_empty_oneline.hcl'));
            $result = Hcl::parse($hcl);

            expect($result)->toHaveKey('a');
            expect($result['a'])->toBe([]);
        });

        test('parses oneline block with attribute', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_oneline.hcl'));
            $result = Hcl::parse($hcl);

            expect($result)->toHaveKey('a');
            expect($result['a'])->toHaveKey('b');
            expect($result['a']['b'])->toBe('foo');
        });
    });

    describe('expressions/primitive_literals', function (): void {
        test('parses whole numbers', function (): void {
            $result = Hcl::parse('whole_number = 5');

            expect($result['whole_number'])->toBe(5);
        });

        test('parses fractional numbers', function (): void {
            $result = Hcl::parse('fractional_number = 3.2');

            expect($result['fractional_number'])->toBe(3.2);
        });

        test('parses high precision numbers', function (): void {
            $result = Hcl::parse('num = 3.14159265358979323846264338327950288419716939937510582097494459');

            // PHP float precision limits apply
            expect($result['num'])->toBeFloat();
        });

        test('parses ASCII strings', function (): void {
            $result = Hcl::parse('string_ascii = "hello"');

            expect($result['string_ascii'])->toBe('hello');
        });

        test('parses Unicode BMP strings', function (): void {
            $result = Hcl::parse('string_unicode_bmp = "ЖЖ"');

            expect($result['string_unicode_bmp'])->toBe('ЖЖ');
        });

        test('parses Unicode astral plane strings', function (): void {
            $result = Hcl::parse('string_unicode_astral = "👩‍👩‍👧‍👦"');

            expect($result['string_unicode_astral'])->toBe('👩‍👩‍👧‍👦');
        });

        test('parses booleans', function (): void {
            $result = Hcl::parse("t = true\nf = false");

            expect($result['t'])->toBe(true);
            expect($result['f'])->toBe(false);
        });

        test('parses null', function (): void {
            $result = Hcl::parse('n = null');

            expect($result['n'])->toBeNull();
        });

        test('parses full primitive_literals fixture', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/expressions/primitive_literals.hcl'));
            $result = Hcl::parse($hcl);

            expect($result['whole_number'])->toBe(5);
            expect($result['fractional_number'])->toBe(3.2);
            expect($result['string_ascii'])->toBe('hello');
            expect($result['string_unicode_bmp'])->toBe('ЖЖ');
            expect($result['true'])->toBe(true);
            expect($result['false'])->toBe(false);
            expect($result['null'])->toBeNull();
        });
    });

    describe('expressions/operators', function (): void {
        test('parses and evaluates equality operators', function (): void {
            $result = Hcl::parse('exactly = "a" == "a"');

            expect($result['exactly'])->toBe(true);
        });

        test('parses and evaluates inequality operators', function (): void {
            $result = Hcl::parse('lt = 1 < 2');

            expect($result['lt'])->toBe(true);
        });

        test('parses and evaluates arithmetic operators', function (): void {
            $result = Hcl::parse('add = 2 + 3.5');

            expect($result['add'])->toBe(5.5);
        });

        test('parses and evaluates logical operators', function (): void {
            $result = Hcl::parse('tt = true && true');

            expect($result['tt'])->toBe(true);
        });

        test('parses and evaluates ternary conditionals', function (): void {
            $result = Hcl::parse('t = true ? "a" : "b"');

            expect($result['t'])->toBe('a');
        });

        test('parses and evaluates unary not operator', function (): void {
            $result = Hcl::parse('t = !true');

            expect($result['t'])->toBe(false);
        });

        test('parses full operators fixture', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/expressions/operators.hcl'));
            $result = Hcl::parse($hcl);

            // Equality tests
            expect($result['equality']['==']['exactly'])->toBe(true);
            expect($result['equality']['==']['not'])->toBe(false);
            expect($result['equality']['!=']['exactly'])->toBe(false);
            expect($result['equality']['!=']['not'])->toBe(true);

            // Inequality tests
            expect($result['inequality']['<']['lt'])->toBe(true);
            expect($result['inequality']['<']['gt'])->toBe(false);
            expect($result['inequality']['<=']['eq'])->toBe(true);
            expect($result['inequality']['>']['gt'])->toBe(true);
            expect($result['inequality']['>=']['eq'])->toBe(true);

            // Arithmetic tests
            expect($result['arithmetic']['add'])->toBe(5.5);
            expect($result['arithmetic']['sub'])->toBe(1.5);
            expect($result['arithmetic']['mul'])->toBe(9.0);
            expect($result['arithmetic']['div'])->toBe(0.1);
            expect($result['arithmetic']['mod'])->toBe(1.0);

            // Logical binary tests
            expect($result['logical_binary']['&&']['tt'])->toBe(true);
            expect($result['logical_binary']['&&']['ff'])->toBe(false);
            expect($result['logical_binary']['||']['tf'])->toBe(true);
            expect($result['logical_binary']['||']['ff'])->toBe(false);

            // Logical unary tests
            expect($result['logical_unary']['!']['t'])->toBe(false);
            expect($result['logical_unary']['!']['f'])->toBe(true);

            // Conditional tests
            expect($result['conditional']['t'])->toBe('a');
            expect($result['conditional']['f'])->toBe('b');
        });
    });

    describe('expressions/heredoc', function (): void {
        test('parses basic heredoc', function (): void {
            $hcl = <<<'HCL'
                content = <<EOT
                Foo
                Bar
                EOT
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['content'])->toContain('Foo');
            expect($result['content'])->toContain('Bar');
        });

        test('parses flush heredoc with indentation stripping', function (): void {
            $hcl = <<<'HCL'
                content = <<-EOT
                    Foo
                    Bar
                EOT
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['content'])->toContain('Foo');
            expect($result['content'])->toContain('Bar');
        });

        test('parses full heredoc fixture', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/expressions/heredoc.hcl'));
            $result = Hcl::parse($hcl);

            // Normal heredocs
            expect($result['normal']['basic'])->toContain('Foo');
            expect($result['normal']['basic'])->toContain('Bar');
            expect($result['normal']['basic'])->toContain('Baz');

            // Flush heredocs (with indentation stripping)
            expect($result['flush']['basic'])->toContain('Foo');
            expect($result['flush']['indented'])->toContain('Foo');
        });
    });

    describe('official spec example', function (): void {
        test('parses nested blocks with multiple labels', function (): void {
            $hcl = <<<'HCL'
                io_mode = "async"

                service "http" "web_proxy" {
                  listen_addr = "127.0.0.1:8080"

                  process "main" {
                    command = ["/usr/local/bin/awesome-app", "server"]
                  }

                  process "mgmt" {
                    command = ["/usr/local/bin/awesome-app", "mgmt"]
                  }
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['io_mode'])->toBe('async');
            expect($result['service']['http']['web_proxy']['listen_addr'])->toBe('127.0.0.1:8080');
            expect($result['service']['http']['web_proxy']['process']['main']['command'])
                ->toBe(['/usr/local/bin/awesome-app', 'server']);
            expect($result['service']['http']['web_proxy']['process']['mgmt']['command'])
                ->toBe(['/usr/local/bin/awesome-app', 'mgmt']);
        });
    });

    describe('error handling', function (): void {
        test('rejects unclosed blocks', function (): void {
            $hcl = file_get_contents(testFixture('specsuite/structure/blocks/single_unclosed.hcl'));

            expect(fn (): array => Hcl::parse($hcl))->toThrow(Exception::class);
        });

        test('rejects comma-separated attributes in block body', function (): void {
            // Per HCL spec, comma-separated attributes on the same line in a block
            // body are invalid syntax (unlike object expressions).
            $hcl = 'a { b = "foo", c = "bar" }';

            expect(fn (): array => Hcl::parse($hcl))->toThrow(Exception::class);
        });
    });
});

describe('HCL Gaps Summary', function (): void {
    /**
     * This test documents the gaps between our implementation and full HCL compliance.
     */
    test('documents known gaps', function (): void {
        $implemented = [
            'heredoc' => 'Heredoc syntax (<<EOT and <<-EOT) - IMPLEMENTED',
            'operators' => 'Arithmetic/comparison/logical operators - IMPLEMENTED',
            'conditionals' => 'Ternary conditionals (condition ? a : b) - IMPLEMENTED',
            'indexing' => 'Index expressions (foo[0], foo["key"]) - IMPLEMENTED',
        ];

        $remaining = [
            'for_expressions' => 'For expressions ([for x in y : x]) not yet supported',
            'splat' => 'Splat expressions (foo[*].bar) not yet supported',
            'template_directives' => 'Template directives (%{if}, %{for}) not yet supported',
        ];

        // This just documents the gaps - always passes
        expect($implemented)->toHaveCount(4);
        expect($remaining)->toHaveCount(3);
    });
});
