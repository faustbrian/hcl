<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Parser;

/**
 * Token types for the HCL lexer.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum TokenType: string
{
    // Literals
    case String = 'STRING';
    case Heredoc = 'HEREDOC';
    case Number = 'NUMBER';
    case Bool = 'BOOL';
    case Null = 'NULL';
    case Identifier = 'IDENTIFIER';

    // Structural
    case LeftBrace = 'LEFT_BRACE';
    case RightBrace = 'RIGHT_BRACE';
    case LeftBracket = 'LEFT_BRACKET';
    case RightBracket = 'RIGHT_BRACKET';
    case LeftParen = 'LEFT_PAREN';
    case RightParen = 'RIGHT_PAREN';
    case Equals = 'EQUALS';
    case Comma = 'COMMA';
    case Dot = 'DOT';
    case Colon = 'COLON';
    case Arrow = 'ARROW';
    case Ellipsis = 'ELLIPSIS';

    // Arithmetic Operators
    case Plus = 'PLUS';
    case Minus = 'MINUS';
    case Star = 'STAR';
    case Slash = 'SLASH';
    case Percent = 'PERCENT';

    // Comparison Operators
    case EqualEqual = 'EQUAL_EQUAL';
    case BangEqual = 'BANG_EQUAL';
    case Less = 'LESS';
    case LessEqual = 'LESS_EQUAL';
    case Greater = 'GREATER';
    case GreaterEqual = 'GREATER_EQUAL';

    // Logical Operators
    case AmpAmp = 'AMP_AMP';
    case PipePipe = 'PIPE_PIPE';
    case Bang = 'BANG';

    // Conditional
    case Question = 'QUESTION';

    // Keywords
    case For = 'FOR';
    case In = 'IN';
    case If = 'IF';

    // Special
    case Interpolation = 'INTERPOLATION';
    case Comment = 'COMMENT';
    case Newline = 'NEWLINE';
    case Eof = 'EOF';
}
