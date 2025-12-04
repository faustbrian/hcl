<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Validation;

/**
 * Represents a range in source code.
 *
 * Defines a precise location span in source text using line, column, and byte positions.
 * Used for error reporting and diagnostic messages to pinpoint exact source locations.
 * All positions use 1-based indexing for lines and columns, 0-based for byte offsets.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class SourceRange
{
    /**
     * Create a new source range.
     *
     * @param int $fromLine   Starting line number (1-indexed). First line is 1.
     * @param int $fromColumn Starting column number (1-indexed). First column is 1.
     * @param int $fromByte   Starting byte offset (0-indexed). First byte is 0.
     * @param int $toLine     Ending line number (1-indexed). Inclusive end position.
     * @param int $toColumn   Ending column number (1-indexed). Exclusive end position.
     * @param int $toByte     Ending byte offset (0-indexed). Exclusive end position.
     */
    public function __construct(
        public int $fromLine,
        public int $fromColumn,
        public int $fromByte,
        public int $toLine,
        public int $toColumn,
        public int $toByte,
    ) {}

    /**
     * Create a source range from a single position.
     *
     * @param  int  $line   Line number (1-indexed).
     * @param  int  $column Column number (1-indexed).
     * @param  int  $byte   Byte offset (0-indexed).
     * @return self Single-character source range.
     */
    public static function at(int $line, int $column, int $byte): self
    {
        return new self($line, $column, $byte, $line, $column + 1, $byte + 1);
    }

    /**
     * Create a source range spanning from one position to another.
     *
     * @param  int  $fromLine   Starting line number (1-indexed).
     * @param  int  $fromColumn Starting column number (1-indexed).
     * @param  int  $fromByte   Starting byte offset (0-indexed).
     * @param  int  $toLine     Ending line number (1-indexed).
     * @param  int  $toColumn   Ending column number (1-indexed).
     * @param  int  $toByte     Ending byte offset (0-indexed).
     * @return self Source range spanning the specified positions.
     */
    public static function span(
        int $fromLine,
        int $fromColumn,
        int $fromByte,
        int $toLine,
        int $toColumn,
        int $toByte,
    ): self {
        return new self($fromLine, $fromColumn, $fromByte, $toLine, $toColumn, $toByte);
    }
}
