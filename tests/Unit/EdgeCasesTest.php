<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Exceptions\ParserException;
use Cline\Hcl\Hcl;
use Cline\Hcl\Parser\Lexer;
use Cline\Hcl\Parser\TokenType;
use Cline\Hcl\Validation\HclValidator;

describe('Lexer Edge Cases', function (): void {
    describe('operators', function (): void {
        test('tokenizes ellipsis operator', function (): void {
            $lexer = new Lexer('a = [1, 2, var.list...]');
            $tokens = $lexer->tokenize();
            $hasEllipsis = array_any($tokens, fn ($token): bool => $token->type === TokenType::Ellipsis);

            expect($hasEllipsis)->toBeTrue();
        });

        test('tokenizes equality operator', function (): void {
            $lexer = new Lexer('result = a == b');
            $tokens = $lexer->tokenize();
            $hasEqualEqual = array_any($tokens, fn ($token): bool => $token->type === TokenType::EqualEqual);

            expect($hasEqualEqual)->toBeTrue();
        });

        test('tokenizes inequality operator', function (): void {
            $lexer = new Lexer('result = a != b');
            $tokens = $lexer->tokenize();
            $hasBangEqual = array_any($tokens, fn ($token): bool => $token->type === TokenType::BangEqual);

            expect($hasBangEqual)->toBeTrue();
        });

        test('tokenizes less than or equal', function (): void {
            $lexer = new Lexer('result = a <= b');
            $tokens = $lexer->tokenize();
            $hasLessEqual = array_any($tokens, fn ($token): bool => $token->type === TokenType::LessEqual);

            expect($hasLessEqual)->toBeTrue();
        });

        test('tokenizes greater than or equal', function (): void {
            $lexer = new Lexer('result = a >= b');
            $tokens = $lexer->tokenize();
            $hasGreaterEqual = array_any($tokens, fn ($token): bool => $token->type === TokenType::GreaterEqual);

            expect($hasGreaterEqual)->toBeTrue();
        });

        test('tokenizes logical AND', function (): void {
            $lexer = new Lexer('result = a && b');
            $tokens = $lexer->tokenize();
            $hasAmpAmp = array_any($tokens, fn ($token): bool => $token->type === TokenType::AmpAmp);

            expect($hasAmpAmp)->toBeTrue();
        });

        test('tokenizes logical OR', function (): void {
            $lexer = new Lexer('result = a || b');
            $tokens = $lexer->tokenize();
            $hasPipePipe = array_any($tokens, fn ($token): bool => $token->type === TokenType::PipePipe);

            expect($hasPipePipe)->toBeTrue();
        });

        test('tokenizes arrow operator', function (): void {
            $lexer = new Lexer('items = { for k, v in map : k => v }');
            $tokens = $lexer->tokenize();
            $hasArrow = array_any($tokens, fn ($token): bool => $token->type === TokenType::Arrow);

            expect($hasArrow)->toBeTrue();
        });

        test('tokenizes heredoc', function (): void {
            $lexer = new Lexer("content = <<EOF\nHello\nWorld\nEOF");
            $tokens = $lexer->tokenize();
            $hasHeredoc = array_any($tokens, fn ($token): bool => $token->type === TokenType::Heredoc);

            expect($hasHeredoc)->toBeTrue();
        });

        test('tokenizes indented heredoc', function (): void {
            $lexer = new Lexer("content = <<-EOF\n  Hello\n  World\n  EOF");
            $tokens = $lexer->tokenize();
            $hasHeredoc = array_any($tokens, fn ($token): bool => $token->type === TokenType::Heredoc);

            expect($hasHeredoc)->toBeTrue();
        });
    });

    describe('slash comments', function (): void {
        test('tokenizes double-slash comments', function (): void {
            $lexer = new Lexer("// This is a comment\nkey = \"value\"");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::Comment);
        });
    });
});

describe('Parser Expression Edge Cases', function (): void {
    test('parses unary minus', function (): void {
        $result = Hcl::parse('value = -42');

        expect($result['value'])->toBe(-42);
    });

    test('parses unary NOT', function (): void {
        $result = Hcl::parse('value = !true');

        expect($result['value'])->toBeFalse();
    });

    test('parses nested expressions with parentheses', function (): void {
        $result = Hcl::parse('value = (1 + 2) * 3');

        expect($result['value'])->toBe(9.0);
    });

    test('parses conditional expression', function (): void {
        $result = Hcl::parse('value = true ? "yes" : "no"');

        expect($result['value'])->toBe('yes');
    });

    test('parses conditional with false condition', function (): void {
        $result = Hcl::parse('value = false ? "yes" : "no"');

        expect($result['value'])->toBe('no');
    });

    test('parses null value', function (): void {
        $result = Hcl::parse('value = null');

        expect($result['value'])->toBeNull();
    });

    test('parses floating point number', function (): void {
        $result = Hcl::parse('value = 3.14159');

        expect($result['value'])->toBe(3.141_59);
    });

    test('parses scientific notation', function (): void {
        $result = Hcl::parse('value = 1e10');

        expect($result['value'])->toBeGreaterThan(0);
    });

    test('parses modulo operator', function (): void {
        $result = Hcl::parse('value = 10 % 3');

        expect($result['value'])->toEqual(1);
    });

    test('parses comparison operators', function (): void {
        $result = Hcl::parse('less = 1 < 2');
        expect($result['less'])->toBeTrue();

        $result = Hcl::parse('greater = 2 > 1');
        expect($result['greater'])->toBeTrue();
    });

    test('parses equality comparison', function (): void {
        $result = Hcl::parse('equal = 1 == 1');
        expect($result['equal'])->toBeTrue();

        $result = Hcl::parse('notequal = 1 != 2');
        expect($result['notequal'])->toBeTrue();
    });

    test('parses logical AND', function (): void {
        $result = Hcl::parse('result = true && true');
        expect($result['result'])->toBeTrue();

        $result = Hcl::parse('result = true && false');
        expect($result['result'])->toBeFalse();
    });

    test('parses logical OR', function (): void {
        $result = Hcl::parse('result = false || true');
        expect($result['result'])->toBeTrue();

        $result = Hcl::parse('result = false || false');
        expect($result['result'])->toBeFalse();
    });

    test('parses array of objects', function (): void {
        $result = Hcl::parse('items = [{ name = "a" }, { name = "b" }]');

        expect($result['items'])->toHaveCount(2);
        expect($result['items'][0]['name'])->toBe('a');
    });

    test('parses object with nested values', function (): void {
        $result = Hcl::parse('items = { a = 1, b = 2 }');

        expect($result['items'])->toBe(['a' => 1, 'b' => 2]);
    });

    test('parses member access on variable', function (): void {
        // Tests member access parsing
        $result = Hcl::parse('value = var.name');

        expect($result)->toHaveKey('value');
    });

    test('parses heredoc content', function (): void {
        $hcl = <<<'HCL'
content = <<EOF
Line 1
Line 2
EOF
HCL;
        $result = Hcl::parse($hcl);

        expect($result['content'])->toContain('Line 1');
        expect($result['content'])->toContain('Line 2');
    });

    test('parses for expression with list', function (): void {
        $result = Hcl::parse('items = [for i in [1, 2, 3] : i * 2]');

        expect($result['items'])->toBe([2.0, 4.0, 6.0]);
    });

    test('parses for expression with key and value', function (): void {
        $result = Hcl::parse('items = [for k, v in { a = 1, b = 2 } : v]');

        expect($result['items'])->toContain(1);
        expect($result['items'])->toContain(2);
    });

    test('parses splat expression with bracket syntax', function (): void {
        $hcl = <<<'HCL'
items = [{ name = "a" }, { name = "b" }]
names = items[*].name
HCL;
        $result = Hcl::parse($hcl);

        // Parser returns reference for unresolved splat
        expect($result)->toHaveKey('names');
    });

    test('parses splat expression with dot syntax', function (): void {
        $hcl = <<<'HCL'
items = [{ name = "x" }, { name = "y" }]
names = items.*.name
HCL;
        $result = Hcl::parse($hcl);

        // Parser returns reference for unresolved splat
        expect($result)->toHaveKey('names');
    });

    test('parses function call with arguments', function (): void {
        $result = Hcl::parse('value = upper("hello")');

        expect($result)->toHaveKey('value');
        expect($result['value'])->toHaveKey('__function__');
        expect($result['value']['__function__'])->toBe('upper');
    });

    test('parses function call with multiple arguments', function (): void {
        $result = Hcl::parse('value = substr("hello", 0, 3)');

        expect($result['value'])->toHaveKey('__args__');
        expect($result['value']['__args__'])->toHaveCount(3);
    });

    test('parses modulo expression', function (): void {
        $result = Hcl::parse('value = 17 % 5');

        expect($result['value'])->toBe(2.0);
    });

    test('parses index access on array', function (): void {
        $result = Hcl::parse('items = [10, 20, 30]');

        expect($result['items'])->toBe([10, 20, 30]);
    });

    test('parses nested member access', function (): void {
        $result = Hcl::parse('value = var.config.name');

        expect($result)->toHaveKey('value');
    });

    test('parses less than or equal comparison', function (): void {
        $result = Hcl::parse('check = 5 <= 5');

        expect($result['check'])->toBeTrue();
    });

    test('parses greater than or equal comparison', function (): void {
        $result = Hcl::parse('check = 6 >= 5');

        expect($result['check'])->toBeTrue();
    });

    test('parses unary minus (negation)', function (): void {
        $result = Hcl::parse('value = -5');

        expect($result['value'])->toBe(-5);
    });

    test('parses double negation', function (): void {
        $result = Hcl::parse('value = --5');

        expect($result['value'])->toBe(5.0);
    });

    test('parses negated expression in parentheses', function (): void {
        $result = Hcl::parse('value = -(3 + 2)');

        expect($result['value'])->toBe(-5.0);
    });

    test('parses logical not with true', function (): void {
        $result = Hcl::parse('value = !true');

        expect($result['value'])->toBeFalse();
    });

    test('parses logical not with false', function (): void {
        $result = Hcl::parse('value = !false');

        expect($result['value'])->toBeTrue();
    });

    test('parses ternary operator', function (): void {
        $result = Hcl::parse('value = true ? 1 : 0');

        expect($result['value'])->toBe(1);
    });

    test('parses ternary with false condition', function (): void {
        $result = Hcl::parse('value = false ? 1 : 0');

        expect($result['value'])->toBe(0);
    });

    test('parses string concatenation with interpolation', function (): void {
        $result = Hcl::parse('value = "hello-${name}"');

        expect($result['value'])->toContain('${name}');
    });
});

describe('HclValidator Edge Cases', function (): void {
    test('validates lexer error becomes diagnostic', function (): void {
        $validator = new HclValidator();
        $result = $validator->validate('key = "unterminated');

        expect($result->hasErrors())->toBeTrue();
        expect($result->errors())->not->toBeEmpty();
    });

    test('validates block with multiple labels', function (): void {
        $validator = new HclValidator();
        $result = $validator->validate('resource "aws_instance" "web" { ami = "test" }');

        expect($result->isValid())->toBeTrue();
    });

    test('validates deeply nested blocks', function (): void {
        $validator = new HclValidator();
        $hcl = <<<'HCL'
outer "label" {
  inner "sublabel" {
    value = 1
  }
}
HCL;
        $result = $validator->validate($hcl);

        expect($result->isValid())->toBeTrue();
    });
});

describe('Hcl Conversion Edge Cases', function (): void {
    test('arrayToHcl handles complex nested structure', function (): void {
        $data = [
            'resource' => [
                'aws_instance' => [
                    'web' => [
                        'ami' => 'ami-123',
                        'instance_type' => 't2.micro',
                        'tags' => [
                            'Name' => 'WebServer',
                        ],
                    ],
                ],
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('resource');
        expect($hcl)->toContain('ami');
    });

    test('fromJson handles nested arrays', function (): void {
        $json = '{"matrix": [[1, 2], [3, 4]]}';
        $hcl = Hcl::fromJson($json);

        expect($hcl)->toContain('matrix');
    });

    test('handles block with simple nested value', function (): void {
        $data = [
            'provider' => [
                'name' => ['attr' => 'value'],
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('provider');
    });

    test('arrayToHcl handles boolean values', function (): void {
        $data = [
            'enabled' => true,
            'disabled' => false,
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('true');
        expect($hcl)->toContain('false');
    });

    test('arrayToHcl handles null values', function (): void {
        $data = [
            'nullable' => null,
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('null');
    });

    test('arrayToHcl handles empty arrays as lists', function (): void {
        $data = [
            'empty_list' => [],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('[]');
    });

    test('arrayToHcl handles list of primitives', function (): void {
        $data = [
            'ports' => [80, 443, 8_080],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('ports');
        expect($hcl)->toContain('80');
    });

    test('arrayToHcl handles triple nested blocks', function (): void {
        $data = [
            'resource' => [
                'aws_instance' => [
                    'web' => [
                        'config' => [
                            'port' => 80,
                        ],
                    ],
                ],
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('resource');
    });

    test('arrayToHcl handles strings with special characters', function (): void {
        $data = [
            'message' => 'Hello "World"',
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('message');
    });

    test('arrayToHcl handles empty arrays', function (): void {
        $data = [
            'empty' => [],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('empty = []');
    });

    test('arrayToHcl handles block with simple attribute inside', function (): void {
        // Structure where the block has non-nested array values
        $data = [
            'resource' => [
                'simple' => ['one', 'two', 'three'],
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('resource');
    });

    test('arrayToHcl handles deeply nested block with simple value at leaf', function (): void {
        $data = [
            'provider' => [
                'aws' => [
                    'region' => [
                        'value' => 'us-east-1',
                    ],
                ],
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('provider');
    });

    test('toJson with pretty false', function (): void {
        $hcl = 'key = "value"';
        $json = Hcl::toJson($hcl, false);

        expect($json)->toBe('{"key":"value"}');
    });

    test('parseFile throws on non-existent file', function (): void {
        expect(fn (): array => Hcl::parseFile('/non/existent/path.hcl'))
            ->toThrow(ParserException::class, 'Failed to read file');
    });

    test('fromJson converts json to hcl', function (): void {
        $json = '{"name": "test", "count": 5}';
        $hcl = Hcl::fromJson($json);

        expect($hcl)->toContain('name = "test"');
        expect($hcl)->toContain('count = 5');
    });

    test('arrayToHcl handles keys requiring quotes', function (): void {
        $data = [
            'normal_key' => 'value1',
            'key-with-dash' => 'value2',
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('normal_key = "value1"');
        expect($hcl)->toContain('"key-with-dash" = "value2"');
    });

    test('arrayToHcl handles long arrays on multiple lines', function (): void {
        $data = [
            'items' => [
                'this is a very long string that should cause the array to span multiple lines',
                'another very long string to ensure we exceed the 80 character threshold',
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('items = [');
    });

    test('arrayToHcl handles block with nested non-array simple value', function (): void {
        // This covers the case where block has nested blocks with non-nested content
        // that hits lines 206-209 (simple value at this level)
        $data = [
            'resource' => [
                'aws_instance' => [
                    'web' => 'simple_value',
                ],
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('resource');
        expect($hcl)->toContain('aws_instance');
    });

    test('arrayToHcl handles block type with simple non-array content', function (): void {
        // This tests the case where content is not an array (lines 222-225)
        $data = [
            'variable' => [
                'port' => 8_080,
            ],
        ];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('variable');
        expect($hcl)->toContain('port');
    });

    test('arrayToHcl handles float values', function (): void {
        $data = ['pi' => 3.141_59];

        $hcl = Hcl::arrayToHcl($data);

        expect($hcl)->toContain('pi = 3.14159');
    });
});
