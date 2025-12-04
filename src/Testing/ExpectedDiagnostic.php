<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Hcl\Testing;

use Cline\Hcl\Validation\SourceRange;

/**
 * Represents an expected diagnostic from a .t specification file.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ExpectedDiagnostic
{
    /**
     * Create a new expected diagnostic.
     *
     * @param string      $severity The severity ('error' or 'warning')
     * @param SourceRange $range    The expected source range
     */
    public function __construct(
        public string $severity,
        public SourceRange $range,
    ) {}
}
