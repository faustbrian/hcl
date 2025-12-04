<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Parser;

use Cline\Hcl\Exceptions\ParserException;

use function array_key_exists;
use function array_map;
use function array_values;
use function fmod;
use function is_array;
use function is_float;
use function is_int;
use function is_numeric;
use function is_scalar;
use function is_string;
use function mb_substr;
use function str_contains;
use function str_starts_with;

/**
 * Expression parser using Pratt precedence climbing.
 *
 * Handles all HCL expressions including:
 * - Literals (strings, numbers, bools, null, heredocs)
 * - Arithmetic operators (+, -, *, /, %)
 * - Comparison operators (==, !=, <, <=, >, >=)
 * - Logical operators (&&, ||, !)
 * - Ternary conditionals (condition ? a : b)
 * - Index/attribute access (foo[0], foo.bar, foo["key"])
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ExpressionParser
{
    /**
     * Operator precedence levels (higher = binds tighter).
     */
    private const array PRECEDENCE = [
        // Ternary (lowest)
        TokenType::Question->value => 1,
        // Logical OR
        TokenType::PipePipe->value => 2,
        // Logical AND
        TokenType::AmpAmp->value => 3,
        // Equality
        TokenType::EqualEqual->value => 4,
        TokenType::BangEqual->value => 4,
        // Comparison
        TokenType::Less->value => 5,
        TokenType::LessEqual->value => 5,
        TokenType::Greater->value => 5,
        TokenType::GreaterEqual->value => 5,
        // Addition/Subtraction
        TokenType::Plus->value => 6,
        TokenType::Minus->value => 6,
        // Multiplication/Division
        TokenType::Star->value => 7,
        TokenType::Slash->value => 7,
        TokenType::Percent->value => 7,
    ];

    private int $position = 0;

    /**
     * Variable bindings for for expressions.
     *
     * @var array<string, mixed>
     */
    private array $forBindings = [];

    /**
     * Create a new expression parser.
     *
     * @param array<Token> $tokens The token stream
     */
    public function __construct(
        private readonly array $tokens,
    ) {}

    /**
     * Parse and evaluate an expression starting from the current position.
     *
     * @param  int   $minPrecedence Minimum precedence for this call
     * @return mixed The evaluated result
     */
    public function parseExpression(int $minPrecedence = 0): mixed
    {
        $left = $this->parseUnary();

        while (!$this->isAtEnd()) {
            $token = $this->peek();
            $precedence = $this->getPrecedence($token->type);

            if ($precedence === 0 || $precedence < $minPrecedence) {
                break;
            }

            // Handle ternary conditional
            if ($token->type === TokenType::Question) {
                $left = $this->parseTernary($left);

                continue;
            }

            // Binary operator
            $this->advance();
            $right = $this->parseExpression($precedence + 1);
            $left = $this->evaluateBinary($left, $token->type, $right);
        }

        return $left;
    }

    /**
     * Get current position for external tracking.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Set position for external control.
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Parse a unary expression (!, -).
     *
     * @return mixed The evaluated result
     */
    private function parseUnary(): mixed
    {
        $token = $this->peek();

        if ($token->type === TokenType::Bang) {
            $this->advance();
            $operand = $this->parseUnary();

            return !$operand;
        }

        if ($token->type === TokenType::Minus) {
            // Check if this is a unary minus (not binary)
            // Unary minus is only valid at start or after an operator
            $this->advance();
            $operand = $this->parseUnary();

            return -$this->toFloat($operand);
        }

        return $this->parsePrimary();
    }

    /**
     * Parse a primary expression (literal, identifier, parenthesized).
     *
     * @throws ParserException If invalid syntax
     * @return mixed           The evaluated result
     */
    private function parsePrimary(): mixed
    {
        $token = $this->peek();

        return match ($token->type) {
            TokenType::Number => $this->parseNumber(),
            TokenType::String, TokenType::Interpolation => $this->advance()->value,
            TokenType::Heredoc => $this->advance()->value,
            TokenType::Bool => $this->parseBool(),
            TokenType::Null => $this->parseNull(),
            TokenType::LeftBracket => $this->parseArray(),
            TokenType::LeftBrace => $this->parseObject(),
            TokenType::LeftParen => $this->parseGrouped(),
            TokenType::Identifier => $this->parseIdentifier(),
            default => throw ParserException::unexpectedToken($token, 'expression'),
        };
    }

    /**
     * Parse a ternary conditional expression.
     *
     * @param  mixed $condition The condition value
     * @return mixed The result of the ternary
     */
    private function parseTernary(mixed $condition): mixed
    {
        $this->advance(); // consume ?
        $this->skipNewlines();
        $trueValue = $this->parseExpression();
        $this->skipNewlines();
        $this->consume(TokenType::Colon, ':');
        $this->skipNewlines();
        $falseValue = $this->parseExpression(1); // same precedence level

        return $condition ? $trueValue : $falseValue;
    }

    /**
     * Evaluate a binary operation.
     *
     * @param  mixed     $left     Left operand
     * @param  TokenType $operator The operator token type
     * @param  mixed     $right    Right operand
     * @return mixed     The result
     */
    private function evaluateBinary(mixed $left, TokenType $operator, mixed $right): mixed
    {
        return match ($operator) {
            // Arithmetic
            TokenType::Plus => $this->toFloat($left) + $this->toFloat($right),
            TokenType::Minus => $this->toFloat($left) - $this->toFloat($right),
            TokenType::Star => $this->toFloat($left) * $this->toFloat($right),
            TokenType::Slash => $this->toFloat($left) / $this->toFloat($right),
            TokenType::Percent => fmod($this->toFloat($left), $this->toFloat($right)),
            // Comparison
            TokenType::EqualEqual => $left === $right,
            TokenType::BangEqual => $left !== $right,
            TokenType::Less => $left < $right,
            TokenType::LessEqual => $left <= $right,
            TokenType::Greater => $left > $right,
            TokenType::GreaterEqual => $left >= $right,
            // Logical
            TokenType::AmpAmp => $left && $right,
            TokenType::PipePipe => $left || $right,
            default => throw ParserException::unexpectedToken(
                new Token($operator, $operator->value, 0, 0),
                'binary operator',
            ),
        };
    }

    /**
     * Get operator precedence.
     *
     * @param  TokenType $type The token type
     * @return int       The precedence level (0 if not an operator)
     */
    private function getPrecedence(TokenType $type): int
    {
        return self::PRECEDENCE[$type->value] ?? 0;
    }

    /**
     * Parse a number literal.
     */
    private function parseNumber(): float|int
    {
        $token = $this->advance();

        return str_contains($token->value, '.') ? (float) $token->value : (int) $token->value;
    }

    /**
     * Parse a boolean literal.
     */
    private function parseBool(): bool
    {
        return $this->advance()->value === 'true';
    }

    /**
     * Parse a null literal.
     */
    private function parseNull(): mixed
    {
        $this->advance();

        return null;
    }

    /**
     * Parse a grouped expression in parentheses.
     *
     * @return mixed The evaluated result
     */
    private function parseGrouped(): mixed
    {
        $this->advance(); // consume (
        $this->skipNewlines();
        $result = $this->parseExpression();
        $this->skipNewlines();
        $this->consume(TokenType::RightParen, ')');

        return $result;
    }

    /**
     * Parse an array literal or for expression.
     *
     * @return array<mixed> The array value
     */
    private function parseArray(): array
    {
        $this->advance(); // consume [
        $this->skipNewlines();

        // Check for for expression: [for x in list : expr]
        if ($this->check(TokenType::For)) {
            return $this->parseForExpressionList();
        }

        $elements = [];

        while (!$this->check(TokenType::RightBracket) && !$this->isAtEnd()) {
            $this->skipNewlines();

            if ($this->check(TokenType::RightBracket)) {
                break;
            }

            $elements[] = $this->parseExpression();
            $this->skipNewlines();

            if ($this->check(TokenType::Comma)) {
                $this->advance();
            }

            $this->skipNewlines();
        }

        $this->consume(TokenType::RightBracket, ']');

        return $elements;
    }

    /**
     * Parse a for expression that produces a list.
     * Syntax: [for var in collection : expr]
     *
     * @return array<mixed> The resulting list
     */
    private function parseForExpressionList(): array
    {
        $this->advance(); // consume 'for'
        $this->skipNewlines();

        // Parse iterator variable(s)
        $keyVar = null;
        $valueVar = $this->consume(TokenType::Identifier, 'identifier')->value;
        $this->skipNewlines();

        if ($this->check(TokenType::Comma)) {
            $this->advance();
            $this->skipNewlines();
            $keyVar = $valueVar;
            $valueVar = $this->consume(TokenType::Identifier, 'identifier')->value;
            $this->skipNewlines();
        }

        $this->consume(TokenType::In, 'in');
        $this->skipNewlines();

        // Parse the collection expression
        $collection = $this->parseExpression();
        $this->skipNewlines();

        $this->consume(TokenType::Colon, ':');
        $this->skipNewlines();

        // We need to parse the expression template without evaluating
        // Store current position and capture the expression tokens
        $exprStart = $this->position;

        // Skip to the closing bracket, tracking depth
        /** @var int<0, max> $depth Decremented in loop when matching brackets */
        $depth = 1;

        while (!$this->isAtEnd() && $depth > 0) {
            $token = $this->peek();

            if ($token->type === TokenType::LeftBracket) {
                ++$depth;
            } elseif ($token->type === TokenType::RightBracket) {
                --$depth;

                if ($depth === 0) {
                    break;
                }
            }

            $this->advance();
        }

        $exprEnd = $this->position;
        $this->consume(TokenType::RightBracket, ']');

        // Evaluate the for expression
        $result = [];

        if (!is_array($collection)) {
            return $result;
        }

        foreach ($collection as $key => $value) {
            // Reset position and evaluate expression with bound variables
            $this->position = $exprStart;
            $this->forBindings[$valueVar] = $value;

            if ($keyVar !== null) {
                $this->forBindings[$keyVar] = $key;
            }

            $result[] = $this->parseExpression();
        }

        // Restore position
        $this->position = $exprEnd + 1;
        $this->forBindings = [];

        return $result;
    }

    /**
     * Parse an object literal.
     *
     * @return array<string, mixed> The object value
     */
    private function parseObject(): array
    {
        $this->advance(); // consume {
        $this->skipNewlines();

        $entries = [];

        while (!$this->check(TokenType::RightBrace) && !$this->isAtEnd()) {
            $this->skipNewlines();

            if ($this->check(TokenType::RightBrace)) {
                break;
            }

            if ($this->check(TokenType::Comment)) {
                $this->advance();

                continue;
            }

            $keyToken = $this->advance();
            $key = $keyToken->value;

            $this->skipNewlines();

            if ($this->check(TokenType::Equals)) {
                $this->advance();
            } elseif ($this->check(TokenType::Colon)) {
                $this->advance();
            }

            $this->skipNewlines();
            $entries[$key] = $this->parseExpression();
            $this->skipNewlines();

            if ($this->check(TokenType::Comma)) {
                $this->advance();
            }

            $this->skipNewlines();
        }

        $this->consume(TokenType::RightBrace, '}');

        return $entries;
    }

    /**
     * Parse an identifier, function call, or reference.
     *
     * @return mixed The parsed value
     */
    private function parseIdentifier(): mixed
    {
        $nameToken = $this->advance();
        $name = $nameToken->value;

        // Check for function call
        if ($this->check(TokenType::LeftParen)) {
            return $this->parseFunctionCall($name);
        }

        // Check for index/attribute access
        $value = $this->resolveIdentifier($name);

        while ($this->check(TokenType::Dot) || $this->check(TokenType::LeftBracket)) {
            if ($this->check(TokenType::Dot)) {
                $this->advance();

                // Check for splat: .* or .*. followed by attribute
                if ($this->check(TokenType::Star)) {
                    $this->advance();
                    $value = $this->applySplat($value);

                    continue;
                }

                $attr = $this->consume(TokenType::Identifier, 'identifier')->value;
                $value = $this->accessAttribute($value, $attr);
            } elseif ($this->check(TokenType::LeftBracket)) {
                $this->advance();
                $this->skipNewlines();

                // Check for [*] splat syntax
                if ($this->check(TokenType::Star)) {
                    $this->advance();
                    $this->skipNewlines();
                    $this->consume(TokenType::RightBracket, ']');
                    $value = $this->applySplat($value);

                    continue;
                }

                $index = $this->parseExpression();
                $this->skipNewlines();
                $this->consume(TokenType::RightBracket, ']');
                $value = $this->accessIndex($value, $index);
            }
        }

        return $value;
    }

    /**
     * Parse a function call.
     *
     * @param  string $name Function name
     * @return mixed  The function result (as a special structure)
     */
    private function parseFunctionCall(string $name): mixed
    {
        $this->advance(); // consume (
        $this->skipNewlines();

        $args = [];

        while (!$this->check(TokenType::RightParen) && !$this->isAtEnd()) {
            $this->skipNewlines();

            if ($this->check(TokenType::RightParen)) {
                break;
            }

            $args[] = $this->parseExpression();
            $this->skipNewlines();

            if ($this->check(TokenType::Comma)) {
                $this->advance();
            }

            $this->skipNewlines();
        }

        $this->consume(TokenType::RightParen, ')');

        // Return function as special structure for later evaluation
        return [
            '__function__' => $name,
            '__args__' => $args,
        ];
    }

    /**
     * Resolve an identifier to its value.
     *
     * For generic parsing, we return a reference placeholder.
     *
     * @param  string $name The identifier name
     * @return mixed  The resolved value
     */
    private function resolveIdentifier(string $name): mixed
    {
        // Check for bindings first (used in for expressions)
        if (array_key_exists($name, $this->forBindings)) {
            return $this->forBindings[$name];
        }

        // Return as a reference string for later resolution
        return '${'.$name.'}';
    }

    /**
     * Access an attribute on a value.
     *
     * @param  mixed  $value     The value to access
     * @param  string $attribute The attribute name
     * @return mixed  The attribute value
     */
    private function accessAttribute(mixed $value, string $attribute): mixed
    {
        if (is_array($value) && array_key_exists($attribute, $value)) {
            return $value[$attribute];
        }

        // Return dotted reference for unresolved values
        if (is_string($value) && str_starts_with($value, '${')) {
            $ref = mb_substr($value, 2, -1);

            return '${'.$ref.'.'.$attribute.'}';
        }

        return null;
    }

    /**
     * Access an index on a value.
     *
     * @param  mixed $value The value to access
     * @param  mixed $index The index (int or string)
     * @return mixed The indexed value
     */
    private function accessIndex(mixed $value, mixed $index): mixed
    {
        /** @var int|string $key */
        $key = is_int($index) || is_string($index) ? $index : (is_scalar($index) ? (string) $index : 0);

        if (is_array($value) && array_key_exists($key, $value)) {
            return $value[$key];
        }

        // Return indexed reference for unresolved values
        if (is_string($value) && str_starts_with($value, '${')) {
            $ref = mb_substr($value, 2, -1);

            return '${'.$ref.'['.$key.']}';
        }

        return null;
    }

    /**
     * Apply splat operator to iterate over a list and extract values.
     *
     * The splat operator (.* or [*]) iterates over a list and collects
     * values. If followed by an attribute access, it extracts that
     * attribute from each element.
     *
     * @param  mixed        $value The value to apply splat to
     * @return array<mixed> The resulting list
     */
    private function applySplat(mixed $value): array
    {
        // For splat to work, we need an array
        if (!is_array($value)) {
            // Return splat reference for unresolved values
            if (is_string($value) && str_starts_with($value, '${')) {
                $ref = mb_substr($value, 2, -1);

                return ['${'.$ref.'[*]}'];
            }

            return [];
        }

        // Check if we have an attribute to extract
        if ($this->check(TokenType::Dot)) {
            $this->advance();
            $attr = $this->consume(TokenType::Identifier, 'identifier')->value;

            return array_map(fn ($item): mixed => is_array($item) && array_key_exists($attr, $item)
                ? $item[$attr]
                : null, array_values($value));
        }

        // Just return all values
        return array_values($value);
    }

    /**
     * Skip newline tokens.
     */
    private function skipNewlines(): void
    {
        while ($this->check(TokenType::Newline)) {
            $this->advance();
        }
    }

    /**
     * Check if the current token is of the given type.
     */
    private function check(TokenType $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->type === $type;
    }

    /**
     * Get the current token without advancing.
     */
    private function peek(): Token
    {
        return $this->tokens[$this->position];
    }

    /**
     * Advance to the next token and return the previous one.
     */
    private function advance(): Token
    {
        if (!$this->isAtEnd()) {
            ++$this->position;
        }

        return $this->tokens[$this->position - 1];
    }

    /**
     * Consume a token of the expected type.
     *
     * @throws ParserException If the token doesn't match
     */
    private function consume(TokenType $type, string $expected): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        if ($this->isAtEnd()) {
            throw ParserException::unexpectedEof($expected);
        }

        throw ParserException::unexpectedToken($this->peek(), $expected);
    }

    /**
     * Check if we've reached the end of the token stream.
     */
    private function isAtEnd(): bool
    {
        return $this->peek()->type === TokenType::Eof;
    }

    /**
     * Convert a value to float for arithmetic operations.
     */
    private function toFloat(mixed $value): float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }
}
