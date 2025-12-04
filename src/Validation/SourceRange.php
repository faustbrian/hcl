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
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class SourceRange
{
    /**
     * Create a new source range.
     *
     * @param int $fromLine   Starting line number (1-indexed)
     * @param int $fromColumn Starting column number (1-indexed)
     * @param int $fromByte   Starting byte offset (0-indexed)
     * @param int $toLine     Ending line number (1-indexed)
     * @param int $toColumn   Ending column number (1-indexed)
     * @param int $toByte     Ending byte offset (0-indexed)
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
     */
    public static function at(int $line, int $column, int $byte): self
    {
        return new self($line, $column, $byte, $line, $column + 1, $byte + 1);
    }

    /**
     * Create a source range spanning from one position to another.
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
