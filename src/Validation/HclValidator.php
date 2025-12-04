<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Validation;

use Cline\Hcl\Parser\Lexer;
use Cline\Hcl\Parser\Token;
use Cline\Hcl\Parser\TokenType;
use Throwable;

use function count;
use function explode;
use function in_array;
use function mb_strlen;

/**
 * Validates HCL syntax according to the official specification.
 *
 * This validator checks for syntax issues that go beyond basic parsing,
 * such as single-line block constraints and formatting requirements.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class HclValidator
{
    private string $input;

    /** @var array<Token> */
    private array $tokens;

    private int $position = 0;

    /** @var array<Diagnostic> */
    private array $diagnostics = [];

    /**
     * Validate HCL content.
     *
     * @param  string           $input The HCL source code
     * @return ValidationResult The validation result with any diagnostics
     */
    public function validate(string $input): ValidationResult
    {
        $this->input = $input;
        $this->diagnostics = [];
        $this->position = 0;

        $lexer = new Lexer($input);

        try {
            $this->tokens = $lexer->tokenize();
        } catch (Throwable $throwable) {
            // Lexer errors become diagnostics
            $this->diagnostics[] = Diagnostic::error(
                $throwable->getMessage(),
                SourceRange::at(1, 1, 0),
            );

            return new ValidationResult($this->diagnostics);
        }

        $this->validateBody();

        return new ValidationResult($this->diagnostics);
    }

    /**
     * Validate the body (top-level or block body).
     */
    private function validateBody(): void
    {
        $this->skipNewlinesAndComments();

        while (!$this->isAtEnd()) {
            $this->validateTopLevelElement();
            $this->skipNewlinesAndComments();
        }
    }

    /**
     * Validate a top-level element (attribute or block).
     */
    private function validateTopLevelElement(): void
    {
        if ($this->isAtEnd()) {
            return;
        }

        $token = $this->peek();

        // Must start with identifier
        if (!$this->isIdentifierLike($token->type)) {
            $this->advance();

            return;
        }

        $this->advance();
        $this->skipNewlinesAndComments();

        if ($this->isAtEnd()) {
            return;
        }

        // Check if this is an assignment or a block
        if ($this->check(TokenType::Equals)) {
            $this->validateAttribute();
        } else {
            $this->validateBlock();
        }
    }

    /**
     * Validate an attribute assignment.
     */
    private function validateAttribute(): void
    {
        $this->advance(); // consume =
        $this->skipNewlinesAndComments();
        $this->skipExpression();

        // Check for invalid comma after attribute in block context
        if (!$this->check(TokenType::Comma)) {
            return;
        }

        $commaToken = $this->peek();
        $this->diagnostics[] = Diagnostic::error(
            'Each argument must be on its own line',
            $this->rangeFromToken($commaToken),
        );
        $this->advance();
    }

    /**
     * Validate a block definition.
     */
    private function validateBlock(): void
    {
        // Skip labels
        while ($this->check(TokenType::String) || $this->check(TokenType::Identifier)) {
            $this->advance();
            $this->skipNewlinesAndComments();
        }

        if (!$this->check(TokenType::LeftBrace)) {
            return;
        }

        $openBrace = $this->advance();

        // Check for unclosed block
        if (!$this->hasMatchingCloseBrace()) {
            $this->diagnostics[] = Diagnostic::error(
                'Unclosed block definition',
                $this->rangeFromToken($openBrace),
            );

            return;
        }

        // Check for single-line block
        $isSingleLine = $this->isSingleLineBlock($openBrace);

        if ($isSingleLine) {
            $this->validateSingleLineBlock();
        } else {
            $this->validateMultiLineBlock();
        }
    }

    /**
     * Check if there's a matching close brace for the current block.
     */
    private function hasMatchingCloseBrace(): bool
    {
        $savedPos = $this->position;
        $depth = 1;

        while (!$this->isAtEnd() && $depth > 0) {
            $token = $this->advance();

            if ($token->type === TokenType::LeftBrace) {
                ++$depth;
            } elseif ($token->type === TokenType::RightBrace) {
                --$depth;
            }
        }

        $found = $depth === 0;
        $this->position = $savedPos;

        return $found;
    }

    /**
     * Check if a block is defined on a single line.
     */
    private function isSingleLineBlock(Token $openBrace): bool
    {
        // Look ahead to find closing brace, check if on same line
        $savedPos = $this->position;
        $depth = 1;
        $openLine = $openBrace->line;

        /** @var int<0, max> $depth Decremented in loop when matching braces */
        while (!$this->isAtEnd() && $depth > 0) {
            $token = $this->advance();

            if ($token->type === TokenType::LeftBrace) {
                ++$depth;
            } elseif ($token->type === TokenType::RightBrace) {
                --$depth;

                if ($depth === 0) {
                    $isSameLine = $token->line === $openLine;
                    $this->position = $savedPos;

                    return $isSameLine;
                }
            } elseif ($token->type === TokenType::Newline && $depth === 1) {
                // Has a newline before closing, not single-line
                $this->position = $savedPos;

                return false;
            }
        }

        $this->position = $savedPos;

        return false;
    }

    /**
     * Validate a single-line block.
     */
    private function validateSingleLineBlock(): void
    {
        $attributeCount = 0;
        $hasNestedBlock = false;

        while (!$this->isAtEnd() && !$this->check(TokenType::RightBrace)) {
            $this->skipNewlinesAndComments();

            if ($this->check(TokenType::RightBrace)) {
                break;
            }

            $token = $this->peek();

            if (!$this->isIdentifierLike($token->type)) {
                $this->advance();

                continue;
            }

            $attrName = $this->advance();
            $this->skipNewlinesAndComments();

            if ($this->check(TokenType::Equals)) {
                // It's an attribute
                ++$attributeCount;

                if ($attributeCount > 1) {
                    $this->diagnostics[] = Diagnostic::error(
                        'Only one argument is allowed in a single-line block definition',
                        $this->rangeFromToken($attrName),
                    );
                }

                $this->advance(); // consume =
                $this->skipNewlinesAndComments();
                $this->skipExpression();

                // Check for comma
                if ($this->check(TokenType::Comma)) {
                    $commaToken = $this->peek();
                    $this->diagnostics[] = Diagnostic::error(
                        'Each argument must be on its own line',
                        $this->rangeFromToken($commaToken),
                    );
                    $this->advance();
                }
            } elseif ($this->check(TokenType::LeftBrace) || $this->check(TokenType::String) || $this->check(TokenType::Identifier)) {
                // It's a nested block
                $this->diagnostics[] = Diagnostic::error(
                    'A single-line block definition cannot contain another block definition',
                    $this->rangeFromToken($attrName),
                );
                $hasNestedBlock = true;
                $this->skipNestedBlock();
            }
        }

        if (!$this->check(TokenType::RightBrace)) {
            return;
        }

        $this->advance();
    }

    /**
     * Validate a multi-line block.
     */
    private function validateMultiLineBlock(): void
    {
        $this->skipNewlinesAndComments();

        while (!$this->isAtEnd() && !$this->check(TokenType::RightBrace)) {
            $this->validateTopLevelElement();
            $this->skipNewlinesAndComments();
        }

        if (!$this->check(TokenType::RightBrace)) {
            return;
        }

        $this->advance();
    }

    /**
     * Skip over an expression.
     */
    private function skipExpression(): void
    {
        $depth = 0;

        while (!$this->isAtEnd()) {
            $token = $this->peek();

            if (in_array($token->type, [TokenType::LeftBrace, TokenType::LeftBracket, TokenType::LeftParen], true)) {
                ++$depth;
                $this->advance();
            } elseif (in_array($token->type, [TokenType::RightBrace, TokenType::RightBracket, TokenType::RightParen], true)) {
                if ($depth === 0) {
                    break;
                }

                --$depth;
                $this->advance();
            } elseif ($token->type === TokenType::Newline || $token->type === TokenType::Comma) {
                if ($depth === 0) {
                    break;
                }

                $this->advance();
            } elseif ($token->type === TokenType::Eof) {
                break;
            } else {
                $this->advance();
            }
        }
    }

    /**
     * Skip over a nested block.
     */
    private function skipNestedBlock(): void
    {
        // Skip labels
        while ($this->check(TokenType::String) || $this->check(TokenType::Identifier)) {
            $this->advance();
        }

        if (!$this->check(TokenType::LeftBrace)) {
            return;
        }

        $this->advance();
        $depth = 1;

        while (!$this->isAtEnd() && $depth > 0) {
            $token = $this->advance();

            if ($token->type === TokenType::LeftBrace) {
                ++$depth;
            } elseif ($token->type === TokenType::RightBrace) {
                --$depth;
            }
        }
    }

    /**
     * Skip newlines and comments.
     */
    private function skipNewlinesAndComments(): void
    {
        while ($this->check(TokenType::Newline) || $this->check(TokenType::Comment)) {
            $this->advance();
        }
    }

    /**
     * Check if token type is identifier-like (can be used as name).
     */
    private function isIdentifierLike(TokenType $type): bool
    {
        return in_array($type, [TokenType::Identifier, TokenType::Bool, TokenType::Null, TokenType::For, TokenType::In, TokenType::If], true);
    }

    /**
     * Create a source range from a token.
     */
    private function rangeFromToken(Token $token): SourceRange
    {
        $length = mb_strlen($token->value);
        $byte = $this->calculateByteOffset($token);

        return SourceRange::span(
            $token->line,
            $token->column,
            $byte,
            $token->line,
            $token->column + $length,
            $byte + $length,
        );
    }

    /**
     * Calculate approximate byte offset for a token.
     */
    private function calculateByteOffset(Token $token): int
    {
        // This is an approximation - for exact byte offsets we'd need
        // to track them during lexing
        $lines = explode("\n", $this->input);
        $byte = 0;

        for ($i = 0; $i < $token->line - 1 && $i < count($lines); ++$i) {
            $byte += mb_strlen($lines[$i]) + 1; // +1 for newline
        }

        return $byte + ($token->column - 1);
    }

    /**
     * Check if current token is of the given type.
     */
    private function check(TokenType $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->type === $type;
    }

    /**
     * Get current token.
     */
    private function peek(): Token
    {
        return $this->tokens[$this->position];
    }

    /**
     * Advance to next token.
     */
    private function advance(): Token
    {
        if (!$this->isAtEnd()) {
            ++$this->position;
        }

        return $this->tokens[$this->position - 1];
    }

    /**
     * Check if at end of tokens.
     */
    private function isAtEnd(): bool
    {
        return $this->peek()->type === TokenType::Eof;
    }
}
