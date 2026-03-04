<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Parser\Token;
use Cline\Hcl\Parser\TokenType;

describe('Token', function (): void {
    describe('is', function (): void {
        test('returns true when token matches type', function (): void {
            $token = new Token(TokenType::Identifier, 'test', 1, 1);

            expect($token->is(TokenType::Identifier))->toBeTrue();
        });

        test('returns false when token does not match type', function (): void {
            $token = new Token(TokenType::Identifier, 'test', 1, 1);

            expect($token->is(TokenType::String))->toBeFalse();
        });
    });

    describe('isOneOf', function (): void {
        test('returns true when token matches one of the types', function (): void {
            $token = new Token(TokenType::Identifier, 'test', 1, 1);

            expect($token->isOneOf(TokenType::String, TokenType::Identifier, TokenType::Number))->toBeTrue();
        });

        test('returns false when token matches none of the types', function (): void {
            $token = new Token(TokenType::Identifier, 'test', 1, 1);

            expect($token->isOneOf(TokenType::String, TokenType::Number, TokenType::Bool))->toBeFalse();
        });

        test('returns true with single matching type', function (): void {
            $token = new Token(TokenType::LeftBrace, '{', 1, 1);

            expect($token->isOneOf(TokenType::LeftBrace))->toBeTrue();
        });
    });

    describe('properties', function (): void {
        test('stores type correctly', function (): void {
            $token = new Token(TokenType::String, 'hello', 5, 10);

            expect($token->type)->toBe(TokenType::String);
        });

        test('stores value correctly', function (): void {
            $token = new Token(TokenType::String, 'hello', 5, 10);

            expect($token->value)->toBe('hello');
        });

        test('stores line correctly', function (): void {
            $token = new Token(TokenType::String, 'hello', 5, 10);

            expect($token->line)->toBe(5);
        });

        test('stores column correctly', function (): void {
            $token = new Token(TokenType::String, 'hello', 5, 10);

            expect($token->column)->toBe(10);
        });
    });
});
