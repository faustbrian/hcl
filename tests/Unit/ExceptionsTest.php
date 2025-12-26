<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Exceptions\DuplicateDefinitionException;
use Cline\Hcl\Exceptions\InvalidBlockTypeException;
use Cline\Hcl\Exceptions\InvalidValueException;
use Cline\Hcl\Exceptions\MalformedHeredocException;
use Cline\Hcl\Exceptions\MissingRequiredFieldException;
use Cline\Hcl\Exceptions\UnexpectedCharacterException;
use Cline\Hcl\Exceptions\UnexpectedEndOfFileException;
use Cline\Hcl\Exceptions\UnexpectedTokenException;
use Cline\Hcl\Exceptions\UnresolvedReferenceException;
use Cline\Hcl\Exceptions\UnterminatedCommentException;
use Cline\Hcl\Exceptions\UnterminatedStringException;
use Cline\Hcl\Parser\Token;
use Cline\Hcl\Parser\TokenType;

describe('UnexpectedCharacterException', function (): void {
    test('creates exception with character and position', function (): void {
        $exception = UnexpectedCharacterException::at('@', 5, 10);

        expect($exception->getMessage())->toBe("Unexpected character '@' at line 5, column 10");
    });
});

describe('UnterminatedStringException', function (): void {
    test('creates exception with position', function (): void {
        $exception = UnterminatedStringException::at(3, 15);

        expect($exception->getMessage())->toBe('Unterminated string starting at line 3, column 15');
    });
});

describe('UnterminatedCommentException', function (): void {
    test('creates exception with position', function (): void {
        $exception = UnterminatedCommentException::at(7, 1);

        expect($exception->getMessage())->toBe('Unterminated multi-line comment starting at line 7, column 1');
    });
});

describe('MalformedHeredocException', function (): void {
    test('creates exception with position and reason', function (): void {
        $exception = MalformedHeredocException::at(10, 5, 'missing delimiter');

        expect($exception->getMessage())->toBe('Malformed heredoc at line 10, column 5: missing delimiter');
    });
});

describe('UnexpectedTokenException', function (): void {
    test('creates exception with TokenType expected', function (): void {
        $token = new Token(TokenType::Identifier, 'foo', 2, 5);
        $exception = UnexpectedTokenException::at($token, TokenType::Equals);

        expect($exception->getMessage())->toContain("Unexpected token 'foo'");
        expect($exception->getMessage())->toContain('line 2');
        expect($exception->getMessage())->toContain('column 5');
        expect($exception->getMessage())->toContain('Expected EQUALS');
    });

    test('creates exception with string expected', function (): void {
        $token = new Token(TokenType::Number, '123', 1, 1);
        $exception = UnexpectedTokenException::at($token, 'an identifier');

        expect($exception->getMessage())->toContain("Unexpected token '123'");
        expect($exception->getMessage())->toContain('Expected an identifier');
    });
});

describe('UnexpectedEndOfFileException', function (): void {
    test('creates exception with context', function (): void {
        $exception = UnexpectedEndOfFileException::whileParsing('block definition');

        expect($exception->getMessage())->toBe('Unexpected end of file while parsing block definition');
    });
});

describe('InvalidBlockTypeException', function (): void {
    test('creates exception with type and position', function (): void {
        $exception = InvalidBlockTypeException::at('unknown', 3, 1);

        expect($exception->getMessage())->toBe("Invalid block type 'unknown' at line 3, column 1");
    });
});

describe('MissingRequiredFieldException', function (): void {
    test('creates exception with field and context', function (): void {
        $exception = MissingRequiredFieldException::inContext('name', 'credential block');

        expect($exception->getMessage())->toBe("Missing required field 'name' in credential block");
    });
});

describe('InvalidValueException', function (): void {
    test('creates exception with field, expected and actual', function (): void {
        $exception = InvalidValueException::forField('port', 'integer', 'string');

        expect($exception->getMessage())->toBe("Invalid value for 'port': expected integer, got string");
    });
});

describe('DuplicateDefinitionException', function (): void {
    test('creates exception with type and name', function (): void {
        $exception = DuplicateDefinitionException::forType('credential', 'postgres');

        expect($exception->getMessage())->toBe("Duplicate credential definition: 'postgres'");
    });
});

describe('UnresolvedReferenceException', function (): void {
    test('creates exception with reference', function (): void {
        $exception = UnresolvedReferenceException::forReference('${var.missing}');

        expect($exception->getMessage())->toBe("Unresolved reference: '\${var.missing}'");
    });
});
