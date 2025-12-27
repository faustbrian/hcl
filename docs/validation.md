---
title: Validation
description: Validate HCL configuration files and get detailed diagnostics.
---

Validate HCL configuration files and get detailed diagnostics.

**Use case:** Linting configuration files, providing user feedback in editors, or validating input before processing.

## Basic Validation

```php
use Cline\Hcl\Validation\HclValidator;

$hcl = <<<'HCL'
name = "my-app"
version = "1.0.0"
HCL;

$validator = new HclValidator();
$result = $validator->validate($hcl);

if ($result->isValid()) {
    echo "Configuration is valid!";
} else {
    foreach ($result->errors() as $error) {
        echo "Error: " . $error->message . "\n";
    }
}
```

## Validation Result

The `ValidationResult` class provides detailed information:

```php
use Cline\Hcl\Validation\HclValidator;

$validator = new HclValidator();
$result = $validator->validate($hcl);

// Check overall validity
$result->isValid();      // true if no errors (warnings allowed)

// Check for specific diagnostic types
$result->hasErrors();    // true if any errors exist
$result->hasWarnings();  // true if any warnings exist

// Get counts
$result->errorCount();   // number of errors
$result->warningCount(); // number of warnings

// Get diagnostics
$result->errors();       // array of error Diagnostic objects
$result->warnings();     // array of warning Diagnostic objects
$result->diagnostics;    // all diagnostics (errors + warnings)
```

## Diagnostic Information

Each diagnostic includes location information:

```php
foreach ($result->errors() as $diagnostic) {
    // Severity
    $diagnostic->severity; // DiagnosticSeverity::Error or Warning

    // Message
    $diagnostic->message; // Human-readable description

    // Source location
    $diagnostic->range->fromLine;   // Starting line (1-based)
    $diagnostic->range->fromColumn; // Starting column (1-based)
    $diagnostic->range->fromByte;   // Starting byte offset

    $diagnostic->range->toLine;     // Ending line
    $diagnostic->range->toColumn;   // Ending column
    $diagnostic->range->toByte;     // Ending byte offset
}
```

## Error Types

### Syntax Errors

```php
$hcl = 'name = ';  // Missing value

$result = $validator->validate($hcl);
// Error: Unexpected end of file
```

### Unclosed Blocks

```php
$hcl = <<<'HCL'
resource "aws_instance" "web" {
    ami = "ami-12345"
    # Missing closing brace
HCL;

$result = $validator->validate($hcl);
// Error: Unclosed block
```

### Invalid Tokens

```php
$hcl = 'name = @invalid';

$result = $validator->validate($hcl);
// Error: Unexpected character '@'
```

### Unterminated Strings

```php
$hcl = 'name = "unclosed string';

$result = $validator->validate($hcl);
// Error: Unterminated string
```

## Validate Files

```php
use Cline\Hcl\Validation\HclValidator;

$validator = new HclValidator();

// Validate from file path
$content = file_get_contents('config.hcl');
$result = $validator->validate($content);

// Handle results
if (!$result->isValid()) {
    foreach ($result->errors() as $error) {
        printf(
            "config.hcl:%d:%d: %s\n",
            $error->range->fromLine,
            $error->range->fromColumn,
            $error->message
        );
    }
}
```

## Creating Diagnostics

You can create custom diagnostics for your own validation:

```php
use Cline\Hcl\Validation\Diagnostic;
use Cline\Hcl\Validation\SourceRange;
use Cline\Hcl\Validation\ValidationResult;

// Create a source range
$range = SourceRange::at(line: 5, column: 10, byte: 45);

// Or a span
$range = SourceRange::span(
    fromLine: 5,
    fromColumn: 10,
    fromByte: 45,
    toLine: 5,
    toColumn: 20,
    toByte: 55
);

// Create diagnostics
$error = Diagnostic::error('Required field "name" is missing', $range);
$warning = Diagnostic::warning('Deprecated field "old_name" used', $range);

// Create result
$result = new ValidationResult([$error, $warning]);
```

## Integration Example

### CLI Linter

```php
#!/usr/bin/env php
<?php

use Cline\Hcl\Validation\HclValidator;

$file = $argv[1] ?? null;

if (!$file || !file_exists($file)) {
    echo "Usage: lint.php <file.hcl>\n";
    exit(1);
}

$content = file_get_contents($file);
$validator = new HclValidator();
$result = $validator->validate($content);

$exitCode = 0;

foreach ($result->errors() as $diagnostic) {
    printf(
        "\033[31mError\033[0m %s:%d:%d: %s\n",
        $file,
        $diagnostic->range->fromLine,
        $diagnostic->range->fromColumn,
        $diagnostic->message
    );
    $exitCode = 1;
}

foreach ($result->warnings() as $diagnostic) {
    printf(
        "\033[33mWarning\033[0m %s:%d:%d: %s\n",
        $file,
        $diagnostic->range->fromLine,
        $diagnostic->range->fromColumn,
        $diagnostic->message
    );
}

if ($result->isValid()) {
    echo "\033[32m✓\033[0m {$file} is valid\n";
}

exit($exitCode);
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

for file in $(git diff --cached --name-only | grep '\.hcl$'); do
    php lint.php "$file"
    if [ $? -ne 0 ]; then
        echo "HCL validation failed for $file"
        exit 1
    fi
done
```

### Editor Integration (LSP-style)

```php
use Cline\Hcl\Validation\HclValidator;
use Cline\Hcl\Validation\DiagnosticSeverity;

function getDiagnosticsForEditor(string $content): array
{
    $validator = new HclValidator();
    $result = $validator->validate($content);

    $diagnostics = [];

    foreach ($result->diagnostics as $d) {
        $diagnostics[] = [
            'range' => [
                'start' => [
                    'line' => $d->range->fromLine - 1,  // 0-based for LSP
                    'character' => $d->range->fromColumn - 1,
                ],
                'end' => [
                    'line' => $d->range->toLine - 1,
                    'character' => $d->range->toColumn - 1,
                ],
            ],
            'severity' => $d->severity === DiagnosticSeverity::Error ? 1 : 2,
            'message' => $d->message,
            'source' => 'hcl',
        ];
    }

    return $diagnostics;
}
```

## Validation vs Parsing

Validation is separate from parsing:

```php
use Cline\Hcl\Hcl;
use Cline\Hcl\Validation\HclValidator;
use Cline\Hcl\Exceptions\ParserException;

// Parsing throws on errors
try {
    $data = Hcl::parse($hcl);
} catch (ParserException $e) {
    // Single error, parsing stopped
    echo $e->getMessage();
}

// Validation collects all issues
$validator = new HclValidator();
$result = $validator->validate($hcl);
// Multiple errors can be reported
foreach ($result->errors() as $error) {
    echo $error->message . "\n";
}
```

Use validation when you want to:
- Report multiple issues at once
- Get precise source locations
- Distinguish between errors and warnings
- Provide editor/IDE feedback

Use parsing when you want to:
- Simply fail fast on invalid input
- Get the parsed data structure
- Handle errors with exceptions
