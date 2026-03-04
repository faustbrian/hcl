<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Testing\ExpectedDiagnostic;
use Cline\Hcl\Testing\TSpec;
use Cline\Hcl\Testing\TSpecParser;
use Cline\Hcl\Validation\SourceRange;

describe('ExpectedDiagnostic', function (): void {
    test('stores severity and range', function (): void {
        $range = SourceRange::at(1, 1, 0);
        $diagnostic = new ExpectedDiagnostic('error', $range);

        expect($diagnostic->severity)->toBe('error');
        expect($diagnostic->range)->toBe($range);
    });

    test('stores warning severity', function (): void {
        $range = SourceRange::at(5, 10, 50);
        $diagnostic = new ExpectedDiagnostic('warning', $range);

        expect($diagnostic->severity)->toBe('warning');
    });
});

describe('TSpec', function (): void {
    describe('parse', function (): void {
        test('parses empty result', function (): void {
            $spec = TSpec::parse('result = null');

            expect($spec->result)->toBeNull();
            expect($spec->resultType)->toBeNull();
            expect($spec->diagnostics)->toBe([]);
        });

        test('parses result value', function (): void {
            $spec = TSpec::parse('result = { key = "value" }');

            expect($spec->result)->toBe(['key' => 'value']);
        });
    });

    describe('expectsWarnings', function (): void {
        test('returns false when no warnings', function (): void {
            $spec = new TSpec(null, null, []);

            expect($spec->expectsWarnings())->toBeFalse();
        });

        test('returns true when has warnings', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $spec = new TSpec(null, null, [
                new ExpectedDiagnostic('warning', $range),
            ]);

            expect($spec->expectsWarnings())->toBeTrue();
        });

        test('returns false when only errors', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $spec = new TSpec(null, null, [
                new ExpectedDiagnostic('error', $range),
            ]);

            expect($spec->expectsWarnings())->toBeFalse();
        });
    });

    describe('expectsErrors', function (): void {
        test('returns false when no errors', function (): void {
            $spec = new TSpec(null, null, []);

            expect($spec->expectsErrors())->toBeFalse();
        });

        test('returns true when has errors', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $spec = new TSpec(null, null, [
                new ExpectedDiagnostic('error', $range),
            ]);

            expect($spec->expectsErrors())->toBeTrue();
        });
    });

    describe('expectsSuccess', function (): void {
        test('returns true when no errors', function (): void {
            $spec = new TSpec(null, null, []);

            expect($spec->expectsSuccess())->toBeTrue();
        });

        test('returns false when has errors', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $spec = new TSpec(null, null, [
                new ExpectedDiagnostic('error', $range),
            ]);

            expect($spec->expectsSuccess())->toBeFalse();
        });
    });

    describe('expectedErrorCount', function (): void {
        test('returns zero when no errors', function (): void {
            $spec = new TSpec(null, null, []);

            expect($spec->expectedErrorCount())->toBe(0);
        });

        test('counts only errors not warnings', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $spec = new TSpec(null, null, [
                new ExpectedDiagnostic('error', $range),
                new ExpectedDiagnostic('warning', $range),
                new ExpectedDiagnostic('error', $range),
            ]);

            expect($spec->expectedErrorCount())->toBe(2);
        });
    });

    describe('expectedErrors', function (): void {
        test('returns empty array when no errors', function (): void {
            $spec = new TSpec(null, null, []);

            expect($spec->expectedErrors())->toBe([]);
        });

        test('filters out warnings', function (): void {
            $range = SourceRange::at(1, 1, 0);
            $error = new ExpectedDiagnostic('error', $range);
            $warning = new ExpectedDiagnostic('warning', $range);
            $spec = new TSpec(null, null, [$error, $warning]);

            $errors = $spec->expectedErrors();

            expect($errors)->toHaveCount(1);
            expect($errors[0])->toBe($error);
        });
    });
});

describe('TSpecParser', function (): void {
    describe('parse', function (): void {
        test('parses empty content', function (): void {
            $parser = new TSpecParser();
            $spec = $parser->parse('');

            expect($spec->result)->toBeNull();
            expect($spec->resultType)->toBeNull();
            expect($spec->diagnostics)->toBe([]);
        });

        test('parses result_type', function (): void {
            $parser = new TSpecParser();
            $spec = $parser->parse('result_type = "string"');

            expect($spec->resultType)->toBe('string');
        });

        test('parses single error diagnostic', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  error {
    from {
      line = 1
      column = 5
      byte = 4
    }
    to {
      line = 1
      column = 10
      byte = 9
    }
  }
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(1);
            expect($spec->diagnostics[0]->severity)->toBe('error');
            expect($spec->diagnostics[0]->range->fromLine)->toBe(1);
            expect($spec->diagnostics[0]->range->fromColumn)->toBe(5);
        });

        test('parses single warning diagnostic', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  warning {
    from {
      line = 2
      column = 1
      byte = 10
    }
  }
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(1);
            expect($spec->diagnostics[0]->severity)->toBe('warning');
            expect($spec->diagnostics[0]->range->fromLine)->toBe(2);
        });

        test('parses multiple error diagnostics as array', function (): void {
            $parser = new TSpecParser();
            // When parsing arrays in HCL, the structure may vary
            // This test verifies the parsing behavior
            $content = <<<'HCL'
diagnostics {
  error {
    from { line = 1 }
  }
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(1);
            expect($spec->diagnostics[0]->severity)->toBe('error');
        });

        test('parses array of multiple errors', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  error = [
    { from { line = 1 } },
    { from { line = 2 } }
  ]
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(2);
            expect($spec->diagnostics[0]->severity)->toBe('error');
            expect($spec->diagnostics[1]->severity)->toBe('error');
        });

        test('parses array of multiple warnings', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  warning = [
    { from { line = 1 } },
    { from { line = 2 } }
  ]
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(2);
            expect($spec->diagnostics[0]->severity)->toBe('warning');
            expect($spec->diagnostics[1]->severity)->toBe('warning');
        });

        test('skips non-array items in error array', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  error = [
    { from { line = 1 } },
    "invalid",
    { from { line = 2 } }
  ]
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(2);
        });

        test('skips non-array items in warning array', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  warning = [
    { from { line = 1 } },
    "invalid",
    { from { line = 2 } }
  ]
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(2);
        });

        test('parses warning diagnostic with line', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  warning {
    from { line = 3 }
  }
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(1);
            expect($spec->diagnostics[0]->severity)->toBe('warning');
            expect($spec->diagnostics[0]->range->fromLine)->toBe(3);
        });

        test('uses line default when from is empty', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  error {
    from {}
  }
}
HCL;
            $spec = $parser->parse($content);

            // When from block is empty, defaults are used
            expect($spec->diagnostics[0]->range->fromLine)->toBe(1);
            expect($spec->diagnostics[0]->range->fromColumn)->toBe(1);
            expect($spec->diagnostics[0]->range->fromByte)->toBe(0);
        });

        test('parses diagnostic with full range', function (): void {
            $parser = new TSpecParser();
            $content = <<<'HCL'
diagnostics {
  error {
    from {
      line = 1
      column = 1
      byte = 0
    }
    to {
      line = 1
      column = 5
      byte = 4
    }
  }
}
HCL;
            $spec = $parser->parse($content);

            expect($spec->diagnostics)->toHaveCount(1);
            expect($spec->diagnostics[0]->range->toLine)->toBe(1);
            expect($spec->diagnostics[0]->range->toColumn)->toBe(5);
            expect($spec->diagnostics[0]->range->toByte)->toBe(4);
        });

        test('returns empty diagnostics for null block', function (): void {
            $parser = new TSpecParser();
            $spec = $parser->parse('result = "ok"');

            expect($spec->diagnostics)->toBe([]);
        });

        test('handles non-array diagnostics block', function (): void {
            $parser = new TSpecParser();
            // This creates a diagnostics key with a string value
            $spec = $parser->parse('diagnostics = "invalid"');

            expect($spec->diagnostics)->toBe([]);
        });
    });

    describe('parseFile', function (): void {
        test('throws exception for non-existent file', function (): void {
            $parser = new TSpecParser();

            expect(fn (): TSpec => $parser->parseFile('/non/existent/file.t'))
                ->toThrow(RuntimeException::class, 'Failed to read file');
        });
    });
});
