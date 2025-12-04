<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Validation;

/**
 * Represents a validation diagnostic (error or warning).
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Diagnostic
{
    /**
     * Create a new diagnostic.
     *
     * @param DiagnosticSeverity $severity The severity level
     * @param string             $message  The diagnostic message
     * @param SourceRange        $range    The source location
     */
    public function __construct(
        public DiagnosticSeverity $severity,
        public string $message,
        public SourceRange $range,
    ) {}

    /**
     * Create an error diagnostic.
     */
    public static function error(string $message, SourceRange $range): self
    {
        return new self(DiagnosticSeverity::Error, $message, $range);
    }

    /**
     * Create a warning diagnostic.
     */
    public static function warning(string $message, SourceRange $range): self
    {
        return new self(DiagnosticSeverity::Warning, $message, $range);
    }
}
