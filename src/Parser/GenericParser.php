<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Parser;

use Cline\Hcl\Exceptions\UnexpectedEndOfFileException;
use Cline\Hcl\Exceptions\UnexpectedTokenException;

use function array_merge;
use function count;
use function in_array;
use function is_array;

/**
 * Generic HCL parser that accepts any valid HCL syntax.
 *
 * This parser creates a generic AST from any HCL content without
 * enforcing a specific schema. Use this as a foundation for
 * schema-specific parsers or for HCL ↔ JSON conversion.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class GenericParser
{
    private int $position = 0;

    private readonly ExpressionParser $expressionParser;

    /**
     * Create a new parser instance.
     *
     * @param array<Token> $tokens The token stream to parse from the lexer
     */
    public function __construct(
        private readonly array $tokens,
    ) {
        $this->expressionParser = new ExpressionParser($tokens);
    }

    /**
     * Parse the token stream into a generic AST.
     *
     * @throws UnexpectedEndOfFileException If an unexpected EOF is encountered
     * @throws UnexpectedTokenException     If an unexpected token is encountered
     * @return array<string, mixed>         The parsed AST
     */
    public function parse(): array
    {
        $ast = [];

        $this->skipNewlines();

        while (!$this->isAtEnd()) {
            $this->skipNewlines();

            if ($this->isAtEnd()) {
                break;
            }

            // Skip comments
            if ($this->check(TokenType::Comment)) {
                $this->advance();

                continue;
            }

            $this->parseTopLevel($ast);

            $this->skipNewlines();
        }

        return $ast;
    }

    /**
     * Parse a top-level element (attribute or block).
     *
     * @param  array<string, mixed>         $ast The AST to populate
     * @throws UnexpectedEndOfFileException If an unexpected EOF is encountered
     * @throws UnexpectedTokenException     If an unexpected token is encountered
     */
    private function parseTopLevel(array &$ast): void
    {
        $this->skipNewlines();

        if ($this->check(TokenType::Comment)) {
            $this->advance();

            return;
        }

        $nameToken = $this->consumeIdentifierOrKeyword();
        $name = $nameToken->value;

        $this->skipNewlines();

        // Check if this is an assignment or a block
        if ($this->check(TokenType::Equals)) {
            // It's an attribute assignment
            $this->advance();
            $this->skipNewlines();
            $ast[$name] = $this->parseValue();
        } else {
            // It's a block - collect labels
            $labels = [];

            while ($this->check(TokenType::String) || $this->check(TokenType::Identifier)) {
                if ($this->check(TokenType::LeftBrace)) {
                    break;
                }

                $token = $this->advance();
                $labels[] = $token->value;
            }

            $this->skipNewlines();
            $this->consume(TokenType::LeftBrace, '{');
            $this->skipNewlines();

            $body = $this->parseBlockBody();

            $this->skipNewlines();
            $this->consume(TokenType::RightBrace, '}');

            // Nest the block under its labels
            $this->nestBlock($ast, $name, $labels, $body);
        }
    }

    /**
     * Nest a block under its type and labels.
     *
     * For example: service "http" "web_proxy" { ... }
     * Becomes: { "service": { "http": { "web_proxy": { ... } } } }
     *
     * @param array<string, mixed> $ast    The AST to modify
     * @param string               $type   The block type
     * @param array<string>        $labels The block labels
     * @param array<string, mixed> $body   The block body
     */
    private function nestBlock(array &$ast, string $type, array $labels, array $body): void
    {
        if (!isset($ast[$type]) || !is_array($ast[$type])) {
            $ast[$type] = [];
        }

        if ($labels === []) {
            // No labels - merge directly
            /** @var array<string, mixed> $typeArr */
            $typeArr = $ast[$type];
            $ast[$type] = array_merge($typeArr, $body);

            return;
        }

        // Navigate/create nested structure
        /** @var array<string, mixed> $current */
        $current = &$ast[$type];

        foreach ($labels as $i => $label) {
            if (!isset($current[$label]) || !is_array($current[$label])) {
                $current[$label] = [];
            }

            if ($i === count($labels) - 1) {
                // Last label - merge body
                /** @var array<string, mixed> $labelArr */
                $labelArr = $current[$label];
                $current[$label] = array_merge($labelArr, $body);
            } else {
                /** @var array<string, mixed> $next */
                $next = &$current[$label];
                $current = &$next;
            }
        }
    }

    /**
     * Parse the body of a block.
     *
     * @throws UnexpectedEndOfFileException If an unexpected EOF is encountered
     * @throws UnexpectedTokenException     If an unexpected token is encountered
     * @return array<string, mixed>         The parsed body
     */
    private function parseBlockBody(): array
    {
        $body = [];

        while (!$this->check(TokenType::RightBrace) && !$this->isAtEnd()) {
            $this->skipNewlines();

            if ($this->check(TokenType::RightBrace)) {
                break;
            }

            // Skip comments
            if ($this->check(TokenType::Comment)) {
                $this->advance();

                continue;
            }

            $keyToken = $this->consumeIdentifierOrKeyword();
            $key = $keyToken->value;

            $this->skipNewlines();

            // Check if this is a nested block or an assignment
            if ($this->check(TokenType::Equals)) {
                // It's an assignment
                $this->advance();
                $this->skipNewlines();
                $body[$key] = $this->parseValue();
            } else {
                // It's a nested block
                $labels = [];

                while ($this->check(TokenType::String) || $this->check(TokenType::Identifier)) {
                    if ($this->check(TokenType::LeftBrace)) {
                        break;
                    }

                    $labels[] = $this->advance()->value;
                }

                $this->skipNewlines();
                $this->consume(TokenType::LeftBrace, '{');
                $this->skipNewlines();

                $nestedBody = $this->parseBlockBody();

                $this->skipNewlines();
                $this->consume(TokenType::RightBrace, '}');

                // Nest the block
                $this->nestBlock($body, $key, $labels, $nestedBody);
            }

            $this->skipNewlines();
        }

        return $body;
    }

    /**
     * Parse a value expression.
     *
     * Delegates to ExpressionParser for full expression support including
     * operators, ternary conditionals, and heredocs.
     *
     * @throws UnexpectedEndOfFileException If an unexpected EOF is encountered
     * @throws UnexpectedTokenException     If an unexpected token is encountered
     * @return mixed                        The parsed value (native PHP types)
     */
    private function parseValue(): mixed
    {
        // Sync expression parser position with our position
        $this->expressionParser->setPosition($this->position);

        // Parse expression with full operator support
        $result = $this->expressionParser->parseExpression();

        // Sync our position back from expression parser
        $this->position = $this->expressionParser->getPosition();

        return $result;
    }

    /**
     * Skip newline tokens.
     *
     * Advances past all consecutive newline tokens in the stream.
     */
    private function skipNewlines(): void
    {
        while ($this->check(TokenType::Newline)) {
            $this->advance();
        }
    }

    /**
     * Check if the current token is of the given type.
     *
     * @param  TokenType $type The type to check
     * @return bool      True if the current token matches
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
     *
     * @return Token The current token
     */
    private function peek(): Token
    {
        return $this->tokens[$this->position];
    }

    /**
     * Advance to the next token and return the previous one.
     *
     * @return Token The previous token
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
     * @param  TokenType                    $type     The expected type
     * @param  string                       $expected Description of what was expected
     * @throws UnexpectedEndOfFileException If an unexpected EOF is encountered
     * @throws UnexpectedTokenException     If an unexpected token is encountered
     * @return Token                        The consumed token
     */
    private function consume(TokenType $type, string $expected): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        if ($this->isAtEnd()) {
            throw UnexpectedEndOfFileException::whileParsing($expected);
        }

        throw UnexpectedTokenException::at($this->peek(), $expected);
    }

    /**
     * Consume an identifier or keyword token.
     *
     * In HCL, keywords (true, false, null) can be used as identifiers
     * in attribute name position.
     *
     * @throws UnexpectedEndOfFileException If an unexpected EOF is encountered
     * @throws UnexpectedTokenException     If an unexpected token is encountered
     * @return Token                        The consumed token
     */
    private function consumeIdentifierOrKeyword(): Token
    {
        $token = $this->peek();

        if (in_array($token->type, [TokenType::Identifier, TokenType::Bool, TokenType::Null], true)) {
            return $this->advance();
        }

        if ($this->isAtEnd()) {
            throw UnexpectedEndOfFileException::whileParsing('identifier');
        }

        throw UnexpectedTokenException::at($token, 'identifier');
    }

    /**
     * Check if we've reached the end of the token stream.
     *
     * @return bool True if at end
     */
    private function isAtEnd(): bool
    {
        return $this->peek()->type === TokenType::Eof;
    }
}
