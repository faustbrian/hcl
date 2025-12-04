<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Exceptions\LexerException;
use Cline\Hcl\Exceptions\ParserException;
use Cline\Hcl\Parser\Token;
use Cline\Hcl\Parser\TokenType;

describe('LexerException', function (): void {
    describe('unexpectedCharacter', function (): void {
        test('creates exception with character and position', function (): void {
            $exception = LexerException::unexpectedCharacter('@', 5, 10);

            expect($exception->getMessage())->toBe("Unexpected character '@' at line 5, column 10");
        });
    });

    describe('unterminatedString', function (): void {
        test('creates exception with position', function (): void {
            $exception = LexerException::unterminatedString(3, 15);

            expect($exception->getMessage())->toBe('Unterminated string starting at line 3, column 15');
        });
    });

    describe('unterminatedComment', function (): void {
        test('creates exception with position', function (): void {
            $exception = LexerException::unterminatedComment(7, 1);

            expect($exception->getMessage())->toBe('Unterminated multi-line comment starting at line 7, column 1');
        });
    });

    describe('malformedHeredoc', function (): void {
        test('creates exception with position and reason', function (): void {
            $exception = LexerException::malformedHeredoc(10, 5, 'missing delimiter');

            expect($exception->getMessage())->toBe('Malformed heredoc at line 10, column 5: missing delimiter');
        });
    });
});

describe('ParserException', function (): void {
    describe('unexpectedToken', function (): void {
        test('creates exception with TokenType expected', function (): void {
            $token = new Token(TokenType::Identifier, 'foo', 2, 5);
            $exception = ParserException::unexpectedToken($token, TokenType::Equals);

            expect($exception->getMessage())->toContain("Unexpected token 'foo'");
            expect($exception->getMessage())->toContain('line 2');
            expect($exception->getMessage())->toContain('column 5');
            expect($exception->getMessage())->toContain('Expected EQUALS');
        });

        test('creates exception with string expected', function (): void {
            $token = new Token(TokenType::Number, '123', 1, 1);
            $exception = ParserException::unexpectedToken($token, 'an identifier');

            expect($exception->getMessage())->toContain("Unexpected token '123'");
            expect($exception->getMessage())->toContain('Expected an identifier');
        });
    });

    describe('unexpectedEof', function (): void {
        test('creates exception with context', function (): void {
            $exception = ParserException::unexpectedEof('block definition');

            expect($exception->getMessage())->toBe('Unexpected end of file while parsing block definition');
        });
    });

    describe('invalidBlockType', function (): void {
        test('creates exception with type and position', function (): void {
            $exception = ParserException::invalidBlockType('unknown', 3, 1);

            expect($exception->getMessage())->toBe("Invalid block type 'unknown' at line 3, column 1");
        });
    });

    describe('missingRequiredField', function (): void {
        test('creates exception with field and context', function (): void {
            $exception = ParserException::missingRequiredField('name', 'credential block');

            expect($exception->getMessage())->toBe("Missing required field 'name' in credential block");
        });
    });

    describe('invalidValue', function (): void {
        test('creates exception with field, expected and actual', function (): void {
            $exception = ParserException::invalidValue('port', 'integer', 'string');

            expect($exception->getMessage())->toBe("Invalid value for 'port': expected integer, got string");
        });
    });

    describe('duplicateDefinition', function (): void {
        test('creates exception with type and name', function (): void {
            $exception = ParserException::duplicateDefinition('credential', 'postgres');

            expect($exception->getMessage())->toBe("Duplicate credential definition: 'postgres'");
        });
    });

    describe('unresolvedReference', function (): void {
        test('creates exception with reference', function (): void {
            $exception = ParserException::unresolvedReference('${var.missing}');

            expect($exception->getMessage())->toBe("Unresolved reference: '\${var.missing}'");
        });
    });
});
