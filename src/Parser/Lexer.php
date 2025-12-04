<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Parser;

use Cline\Hcl\Exceptions\LexerException;

use const PHP_INT_MAX;

use function array_key_exists;
use function ctype_alnum;
use function ctype_alpha;
use function ctype_digit;
use function implode;
use function in_array;
use function mb_strlen;
use function mb_substr;
use function mb_trim;
use function preg_match;

/**
 * Tokenizes HCL input into a stream of tokens.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Lexer
{
    private const array KEYWORDS = [
        'true' => TokenType::Bool,
        'false' => TokenType::Bool,
        'null' => TokenType::Null,
        'for' => TokenType::For,
        'in' => TokenType::In,
        'if' => TokenType::If,
    ];

    private int $position = 0;

    private int $line = 1;

    private int $column = 1;

    private readonly int $length;

    /**
     * Create a new lexer instance.
     *
     * @param string $input The HCL source code to tokenize
     */
    public function __construct(
        private readonly string $input,
    ) {
        $this->length = mb_strlen($input);
    }

    /**
     * Tokenize the input into an array of tokens.
     *
     * @throws LexerException If an unexpected character is encountered
     * @return array<Token>   The token stream
     */
    public function tokenize(): array
    {
        $tokens = [];

        while (!$this->isAtEnd()) {
            $token = $this->nextToken();

            if (!$token instanceof Token) {
                continue;
            }

            $tokens[] = $token;
        }

        $tokens[] = new Token(TokenType::Eof, '', $this->line, $this->column);

        return $tokens;
    }

    /**
     * Get the next token from the input stream.
     *
     * @throws LexerException If an unexpected character is encountered
     * @return null|Token     The next token, or null if whitespace was consumed
     */
    private function nextToken(): ?Token
    {
        $this->skipWhitespace();

        if ($this->isAtEnd()) {
            return null;
        }

        $char = $this->current();
        $startLine = $this->line;
        $startColumn = $this->column;

        // Comments
        if ($char === '#' || ($char === '/' && $this->peek() === '/')) {
            return $this->readComment($startLine, $startColumn);
        }

        // Multi-line comments
        if ($char === '/' && $this->peek() === '*') {
            return $this->readMultiLineComment($startLine, $startColumn);
        }

        // Heredoc
        if ($char === '<' && $this->peek() === '<') {
            return $this->readHeredoc($startLine, $startColumn);
        }

        // Strings
        if ($char === '"') {
            return $this->readString($startLine, $startColumn);
        }

        // Numbers (including negative)
        if (ctype_digit($char) || ($char === '-' && ctype_digit($this->peek() ?? ''))) {
            return $this->readNumber($startLine, $startColumn);
        }

        // Identifiers and keywords
        if (ctype_alpha($char) || $char === '_') {
            return $this->readIdentifier($startLine, $startColumn);
        }

        // Multi-character operators (must check before single-character)
        if (($token = $this->tryMultiCharOperator($startLine, $startColumn)) instanceof Token) {
            return $token;
        }

        // Single-character tokens
        $token = match ($char) {
            '{' => new Token(TokenType::LeftBrace, $char, $startLine, $startColumn),
            '}' => new Token(TokenType::RightBrace, $char, $startLine, $startColumn),
            '[' => new Token(TokenType::LeftBracket, $char, $startLine, $startColumn),
            ']' => new Token(TokenType::RightBracket, $char, $startLine, $startColumn),
            '(' => new Token(TokenType::LeftParen, $char, $startLine, $startColumn),
            ')' => new Token(TokenType::RightParen, $char, $startLine, $startColumn),
            ',' => new Token(TokenType::Comma, $char, $startLine, $startColumn),
            '.' => new Token(TokenType::Dot, $char, $startLine, $startColumn),
            ':' => new Token(TokenType::Colon, $char, $startLine, $startColumn),
            '?' => new Token(TokenType::Question, $char, $startLine, $startColumn),
            '+' => new Token(TokenType::Plus, $char, $startLine, $startColumn),
            '-' => new Token(TokenType::Minus, $char, $startLine, $startColumn),
            '*' => new Token(TokenType::Star, $char, $startLine, $startColumn),
            '/' => new Token(TokenType::Slash, $char, $startLine, $startColumn),
            '%' => new Token(TokenType::Percent, $char, $startLine, $startColumn),
            '<' => new Token(TokenType::Less, $char, $startLine, $startColumn),
            '>' => new Token(TokenType::Greater, $char, $startLine, $startColumn),
            '!' => new Token(TokenType::Bang, $char, $startLine, $startColumn),
            '=' => new Token(TokenType::Equals, $char, $startLine, $startColumn),
            "\n" => new Token(TokenType::Newline, $char, $startLine, $startColumn),
            default => throw LexerException::unexpectedCharacter($char, $startLine, $startColumn),
        };

        $this->advance();

        if ($token->type === TokenType::Newline) {
            ++$this->line;
            $this->column = 1;
        }

        return $token;
    }

    /**
     * Try to match multi-character operators.
     *
     * @param  int        $startLine   The starting line number
     * @param  int        $startColumn The starting column number
     * @return null|Token The token if matched, null otherwise
     */
    private function tryMultiCharOperator(int $startLine, int $startColumn): ?Token
    {
        $char = $this->current();
        $next = $this->peek();

        // Three-character operators
        if ($char === '.' && $next === '.' && $this->peekAhead(2) === '.') {
            $this->advance();
            $this->advance();
            $this->advance();

            return new Token(TokenType::Ellipsis, '...', $startLine, $startColumn);
        }

        // Two-character operators
        $twoChar = $char.$next;

        $token = match ($twoChar) {
            '==' => new Token(TokenType::EqualEqual, $twoChar, $startLine, $startColumn),
            '!=' => new Token(TokenType::BangEqual, $twoChar, $startLine, $startColumn),
            '<=' => new Token(TokenType::LessEqual, $twoChar, $startLine, $startColumn),
            '>=' => new Token(TokenType::GreaterEqual, $twoChar, $startLine, $startColumn),
            '&&' => new Token(TokenType::AmpAmp, $twoChar, $startLine, $startColumn),
            '||' => new Token(TokenType::PipePipe, $twoChar, $startLine, $startColumn),
            '=>' => new Token(TokenType::Arrow, $twoChar, $startLine, $startColumn),
            default => null,
        };

        if ($token instanceof Token) {
            $this->advance();
            $this->advance();

            return $token;
        }

        return null;
    }

    /**
     * Read a heredoc literal.
     *
     * @param  int            $startLine   The starting line number
     * @param  int            $startColumn The starting column number
     * @throws LexerException If the heredoc is malformed
     * @return Token          The heredoc token
     */
    private function readHeredoc(int $startLine, int $startColumn): Token
    {
        $this->advance(); // first <
        $this->advance(); // second <

        // Check for flush heredoc
        $flush = false;

        if ($this->current() === '-') {
            $flush = true;
            $this->advance();
        }

        // Read delimiter identifier
        $delimiter = '';

        while (!$this->isAtEnd() && $this->isIdentifierChar($this->current())) {
            $delimiter .= $this->current();
            $this->advance();
        }

        if ($delimiter === '') {
            throw LexerException::malformedHeredoc($startLine, $startColumn, 'missing delimiter');
        }

        // Expect newline after delimiter
        if ($this->current() !== "\n") {
            throw LexerException::malformedHeredoc($startLine, $startColumn, 'expected newline after delimiter');
        }

        $this->advance();
        ++$this->line;
        $this->column = 1;

        // Read heredoc content until we find the closing delimiter
        $lines = [];
        $currentLine = '';

        while (!$this->isAtEnd()) {
            $char = $this->current();

            if ($char === "\n") {
                $lines[] = $currentLine;
                $currentLine = '';
                $this->advance();
                ++$this->line;
                $this->column = 1;

                // Check if next line starts with (optional whitespace +) delimiter
                $lineStart = $this->position;
                $leadingSpaces = 0;

                while (!$this->isAtEnd() && ($this->current() === ' ' || $this->current() === "\t")) {
                    ++$leadingSpaces;
                    $this->advance();
                }

                // Check for delimiter
                $potentialDelimiter = '';
                $delimiterLen = mb_strlen($delimiter);

                for ($i = 0; $i < $delimiterLen && !$this->isAtEnd(); ++$i) {
                    $potentialDelimiter .= $this->current();
                    $this->advance();
                }

                // Check that delimiter is followed by newline or EOF
                if ($potentialDelimiter === $delimiter && ($this->isAtEnd() || $this->current() === "\n")) {
                    // Found closing delimiter
                    break;
                }

                // Not the delimiter, restore position and continue reading
                $this->position = $lineStart;
                $this->column = 1;

                continue;
            }

            $currentLine .= $char;
            $this->advance();
        }

        // Build content from lines
        $content = '';

        if ($flush && $lines !== []) {
            // Calculate minimum indentation
            $minIndent = PHP_INT_MAX;

            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }

                preg_match('/^[ \t]*/', $line, $matches);
                $indent = mb_strlen($matches[0] ?? '');

                if ($indent >= $minIndent) {
                    continue;
                }

                $minIndent = $indent;
            }

            if ($minIndent === PHP_INT_MAX) {
                $minIndent = 0;
            }

            // Strip minimum indentation from each line
            $strippedLines = [];

            foreach ($lines as $line) {
                $strippedLines[] = $line === '' ? '' : mb_substr($line, $minIndent);
            }

            $content = implode("\n", $strippedLines);
        } else {
            $content = implode("\n", $lines);
        }

        // Add trailing newline if content is not empty
        if ($content !== '') {
            $content .= "\n";
        }

        return new Token(TokenType::Heredoc, $content, $startLine, $startColumn);
    }

    /**
     * Read a string literal.
     *
     * @param  int            $startLine   The starting line number
     * @param  int            $startColumn The starting column number
     * @throws LexerException If the string is unterminated
     * @return Token          The string token
     */
    private function readString(int $startLine, int $startColumn): Token
    {
        $this->advance(); // consume opening quote
        $value = '';
        $hasInterpolation = false;

        while (!$this->isAtEnd() && $this->current() !== '"') {
            $char = $this->current();

            // Check for interpolation
            if ($char === '$' && $this->peek() === '{') {
                $hasInterpolation = true;
            }

            // Handle escape sequences
            if ($char === '\\') {
                $this->advance();

                if ($this->isAtEnd()) {
                    break;
                }

                $escaped = $this->current();
                $value .= match ($escaped) {
                    'n' => "\n",
                    't' => "\t",
                    'r' => "\r",
                    '\\' => '\\',
                    '"' => '"',
                    '$' => '$',
                    default => '\\'.$escaped,
                };
                $this->advance();

                continue;
            }

            if ($char === "\n") {
                ++$this->line;
                $this->column = 0;
            }

            $value .= $char;
            $this->advance();
        }

        if ($this->isAtEnd()) {
            throw LexerException::unterminatedString($startLine, $startColumn);
        }

        $this->advance(); // consume closing quote

        return new Token(
            $hasInterpolation ? TokenType::Interpolation : TokenType::String,
            $value,
            $startLine,
            $startColumn,
        );
    }

    /**
     * Read a number literal.
     *
     * @param  int   $startLine   The starting line number
     * @param  int   $startColumn The starting column number
     * @return Token The number token
     */
    private function readNumber(int $startLine, int $startColumn): Token
    {
        $value = '';

        if ($this->current() === '-') {
            $value .= $this->current();
            $this->advance();
        }

        while (!$this->isAtEnd() && ctype_digit($this->current())) {
            $value .= $this->current();
            $this->advance();
        }

        // Handle decimals
        if (!$this->isAtEnd() && $this->current() === '.' && ctype_digit($this->peek() ?? '')) {
            $value .= $this->current();
            $this->advance();

            while (!$this->isAtEnd() && ctype_digit($this->current())) {
                $value .= $this->current();
                $this->advance();
            }
        }

        // Handle scientific notation
        if (!$this->isAtEnd() && in_array($this->current(), ['e', 'E'], true)) {
            $value .= $this->current();
            $this->advance();

            if (!$this->isAtEnd() && in_array($this->current(), ['+', '-'], true)) {
                $value .= $this->current();
                $this->advance();
            }

            while (!$this->isAtEnd() && ctype_digit($this->current())) {
                $value .= $this->current();
                $this->advance();
            }
        }

        return new Token(TokenType::Number, $value, $startLine, $startColumn);
    }

    /**
     * Read an identifier or keyword.
     *
     * @param  int   $startLine   The starting line number
     * @param  int   $startColumn The starting column number
     * @return Token The identifier or keyword token
     */
    private function readIdentifier(int $startLine, int $startColumn): Token
    {
        $value = '';

        while (!$this->isAtEnd() && $this->isIdentifierChar($this->current())) {
            $value .= $this->current();
            $this->advance();
        }

        $type = array_key_exists($value, self::KEYWORDS)
            ? self::KEYWORDS[$value]
            : TokenType::Identifier;

        return new Token($type, $value, $startLine, $startColumn);
    }

    /**
     * Read a single-line comment.
     *
     * @param  int   $startLine   The starting line number
     * @param  int   $startColumn The starting column number
     * @return Token The comment token
     */
    private function readComment(int $startLine, int $startColumn): Token
    {
        $value = '';

        // Skip comment prefix
        if ($this->current() === '#') {
            $this->advance();
        } else {
            $this->advance(); // /
            $this->advance(); // /
        }

        while (!$this->isAtEnd() && $this->current() !== "\n") {
            $value .= $this->current();
            $this->advance();
        }

        return new Token(TokenType::Comment, mb_trim($value), $startLine, $startColumn);
    }

    /**
     * Read a multi-line comment.
     *
     * @param  int            $startLine   The starting line number
     * @param  int            $startColumn The starting column number
     * @throws LexerException If the comment is unterminated
     * @return Token          The comment token
     */
    private function readMultiLineComment(int $startLine, int $startColumn): Token
    {
        $this->advance(); // /
        $this->advance(); // *
        $value = '';

        while (!$this->isAtEnd()) {
            if ($this->current() === '*' && $this->peek() === '/') {
                $this->advance(); // *
                $this->advance(); // /

                return new Token(TokenType::Comment, mb_trim($value), $startLine, $startColumn);
            }

            if ($this->current() === "\n") {
                ++$this->line;
                $this->column = 0;
            }

            $value .= $this->current();
            $this->advance();
        }

        throw LexerException::unterminatedComment($startLine, $startColumn);
    }

    /**
     * Skip whitespace characters (except newlines).
     */
    private function skipWhitespace(): void
    {
        while (!$this->isAtEnd()) {
            $char = $this->current();

            if (!in_array($char, [' ', "\t", "\r"], true)) {
                break;
            }

            $this->advance();
        }
    }

    /**
     * Check if the current character is a valid identifier character.
     *
     * @param  string $char The character to check
     * @return bool   True if the character is valid in an identifier
     */
    private function isIdentifierChar(string $char): bool
    {
        return ctype_alnum($char) || $char === '_' || $char === '-';
    }

    /**
     * Get the current character.
     *
     * @return string The current character
     */
    private function current(): string
    {
        return mb_substr($this->input, $this->position, 1);
    }

    /**
     * Peek at the next character without consuming it.
     *
     * @return null|string The next character, or null if at end
     */
    private function peek(): ?string
    {
        if ($this->position + 1 >= $this->length) {
            return null;
        }

        return mb_substr($this->input, $this->position + 1, 1);
    }

    /**
     * Peek ahead by a specific number of positions.
     *
     * @param  int         $offset The number of positions to peek ahead
     * @return null|string The character at that position, or null if at end
     */
    private function peekAhead(int $offset): ?string
    {
        if ($this->position + $offset >= $this->length) {
            return null;
        }

        return mb_substr($this->input, $this->position + $offset, 1);
    }

    /**
     * Advance to the next character.
     */
    private function advance(): void
    {
        ++$this->position;
        ++$this->column;
    }

    /**
     * Check if we've reached the end of the input.
     *
     * @return bool True if at end of input
     */
    private function isAtEnd(): bool
    {
        return $this->position >= $this->length;
    }
}
