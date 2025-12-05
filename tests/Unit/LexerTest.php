<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Exceptions\UnexpectedCharacterException;
use Cline\Hcl\Exceptions\UnterminatedCommentException;
use Cline\Hcl\Exceptions\UnterminatedStringException;
use Cline\Hcl\Parser\Lexer;
use Cline\Hcl\Parser\TokenType;

describe('Lexer', function (): void {
    describe('tokenize', function (): void {
        test('tokenizes simple assignment', function (): void {
            $lexer = new Lexer('key = "value"');
            $tokens = $lexer->tokenize();

            expect($tokens)->toHaveCount(4);
            expect($tokens[0]->type)->toBe(TokenType::Identifier);
            expect($tokens[0]->value)->toBe('key');
            expect($tokens[1]->type)->toBe(TokenType::Equals);
            expect($tokens[2]->type)->toBe(TokenType::String);
            expect($tokens[2]->value)->toBe('value');
            expect($tokens[3]->type)->toBe(TokenType::Eof);
        });

        test('tokenizes number literals', function (): void {
            $lexer = new Lexer('port = 5432');
            $tokens = $lexer->tokenize();

            expect($tokens[2]->type)->toBe(TokenType::Number);
            expect($tokens[2]->value)->toBe('5432');
        });

        test('tokenizes boolean literals', function (): void {
            $lexer = new Lexer('enabled = true');
            $tokens = $lexer->tokenize();

            expect($tokens[2]->type)->toBe(TokenType::Bool);
            expect($tokens[2]->value)->toBe('true');
        });

        test('tokenizes block structures', function (): void {
            $lexer = new Lexer('group "name" { }');
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::Identifier);
            expect($tokens[0]->value)->toBe('group');
            expect($tokens[1]->type)->toBe(TokenType::String);
            expect($tokens[1]->value)->toBe('name');
            expect($tokens[2]->type)->toBe(TokenType::LeftBrace);
            expect($tokens[3]->type)->toBe(TokenType::RightBrace);
        });

        test('tokenizes arrays', function (): void {
            $lexer = new Lexer('tags = ["a", "b"]');
            $tokens = $lexer->tokenize();

            expect($tokens[2]->type)->toBe(TokenType::LeftBracket);
            expect($tokens[3]->type)->toBe(TokenType::String);
            expect($tokens[4]->type)->toBe(TokenType::Comma);
            expect($tokens[5]->type)->toBe(TokenType::String);
            expect($tokens[6]->type)->toBe(TokenType::RightBracket);
        });

        test('tokenizes function calls', function (): void {
            $lexer = new Lexer('password = sensitive("secret")');
            $tokens = $lexer->tokenize();

            expect($tokens[2]->type)->toBe(TokenType::Identifier);
            expect($tokens[2]->value)->toBe('sensitive');
            expect($tokens[3]->type)->toBe(TokenType::LeftParen);
            expect($tokens[4]->type)->toBe(TokenType::String);
            expect($tokens[5]->type)->toBe(TokenType::RightParen);
        });

        test('tokenizes interpolated strings', function (): void {
            $lexer = new Lexer('url = "postgres://${self.host}:${self.port}"');
            $tokens = $lexer->tokenize();

            expect($tokens[2]->type)->toBe(TokenType::Interpolation);
            expect($tokens[2]->value)->toBe('postgres://${self.host}:${self.port}');
        });

        test('tokenizes comments', function (): void {
            $lexer = new Lexer("# This is a comment\nkey = \"value\"");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::Comment);
            expect($tokens[0]->value)->toBe('This is a comment');
        });

        test('tokenizes multi-line comments', function (): void {
            $lexer = new Lexer("/* Multi\nline */\nkey = \"value\"");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->type)->toBe(TokenType::Comment);
        });

        test('handles escape sequences in strings', function (): void {
            $lexer = new Lexer('text = "line1\\nline2"');
            $tokens = $lexer->tokenize();

            expect($tokens[2]->value)->toBe("line1\nline2");
        });

        test('tracks line and column numbers', function (): void {
            $lexer = new Lexer("key = \"value\"\nport = 5432");
            $tokens = $lexer->tokenize();

            expect($tokens[0]->line)->toBe(1);
            expect($tokens[0]->column)->toBe(1);
        });

        test('throws on unexpected character', function (): void {
            $lexer = new Lexer('key @ value');

            expect(fn (): array => $lexer->tokenize())
                ->toThrow(UnexpectedCharacterException::class, "Unexpected character '@'");
        });

        test('throws on unterminated string', function (): void {
            $lexer = new Lexer('key = "unterminated');

            expect(fn (): array => $lexer->tokenize())
                ->toThrow(UnterminatedStringException::class, 'Unterminated string');
        });

        test('throws on unterminated multi-line comment', function (): void {
            $lexer = new Lexer('/* unterminated');

            expect(fn (): array => $lexer->tokenize())
                ->toThrow(UnterminatedCommentException::class, 'Unterminated multi-line comment');
        });
    });
});
