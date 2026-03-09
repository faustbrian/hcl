## Table of Contents

1. [Getting Started](#doc-cookbooks-getting-started)
2. [HCL Syntax](#doc-cookbooks-hcl-syntax)
3. [Expressions](#doc-cookbooks-expressions)
4. [JSON Conversion](#doc-cookbooks-json-conversion)
5. [Validation](#doc-cookbooks-validation)
6. [Overview](#doc-docs-readme)
7. [Expressions](#doc-docs-expressions)
8. [Hcl Syntax](#doc-docs-hcl-syntax)
9. [Json Conversion](#doc-docs-json-conversion)
10. [Validation](#doc-docs-validation)
<a id="doc-cookbooks-getting-started"></a>

# Getting Started

Parse HCL (HashiCorp Configuration Language) content in PHP.

**Use case:** Reading Terraform, Nomad, Consul, or any HCL-based configuration files in your PHP application.

## Installation

```bash
composer require cline/hcl
```

## Basic Parsing

### Parse HCL String

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
name = "my-app"
version = "1.0.0"
enabled = true
port = 8080
HCL;

$data = Hcl::parse($hcl);

// Result:
// [
//     'name' => 'my-app',
//     'version' => '1.0.0',
//     'enabled' => true,
//     'port' => 8080,
// ]
```

### Parse HCL File

```php
use Cline\Hcl\Hcl;

$data = Hcl::parseFile('/path/to/config.hcl');
```

## Supported Value Types

```php
$hcl = <<<'HCL'
# Strings
name = "hello"
path = "/usr/local/bin"

# Numbers
count = 42
rate = 3.14
scientific = 1e10

# Booleans
enabled = true
disabled = false

# Null
optional = null

# Arrays
ports = [80, 443, 8080]
tags = ["web", "api", "production"]

# Objects/Maps
config = {
    timeout = 30
    retries = 3
}
HCL;

$data = Hcl::parse($hcl);
```

## Blocks

HCL blocks are converted to nested arrays:

```php
$hcl = <<<'HCL'
resource "aws_instance" "web" {
    ami           = "ami-12345"
    instance_type = "t2.micro"

    tags = {
        Name = "WebServer"
    }
}
HCL;

$data = Hcl::parse($hcl);

// Access nested data:
$ami = $data['resource']['aws_instance']['web']['ami'];
// 'ami-12345'
```

## Multiple Blocks

Multiple blocks of the same type are merged:

```php
$hcl = <<<'HCL'
variable "region" {
    default = "us-west-2"
}

variable "instance_type" {
    default = "t2.micro"
}
HCL;

$data = Hcl::parse($hcl);

// [
//     'variable' => [
//         'region' => ['default' => 'us-west-2'],
//         'instance_type' => ['default' => 't2.micro'],
//     ],
// ]
```

## String Interpolation

Template expressions are evaluated when possible:

```php
$hcl = <<<'HCL'
name = "app"
greeting = "Hello, ${name}!"
HCL;

$data = Hcl::parse($hcl);
// greeting => 'Hello, app!'
```

## Comments

Both line and block comments are supported:

```hcl
# This is a line comment
// This is also a line comment

/* This is a
   block comment */

name = "value"
```

## Error Handling

```php
use Cline\Hcl\Hcl;
use Cline\Hcl\Exceptions\ParserException;

try {
    $data = Hcl::parse('invalid { content');
} catch (ParserException $e) {
    echo "Parse error: " . $e->getMessage();
    // Includes line and column information
}
```

## Next Steps

- **[HCL Syntax](#doc-cookbooks-hcl-syntax)** - Complete HCL syntax reference
- **[Expressions](#doc-cookbooks-expressions)** - Operators, conditionals, and for expressions
- **[JSON Conversion](#doc-cookbooks-json-conversion)** - Convert between HCL and JSON
- **[Validation](#doc-cookbooks-validation)** - Validate HCL configuration files

<a id="doc-cookbooks-hcl-syntax"></a>

# HCL Syntax Reference

Complete reference for HCL syntax supported by this parser.

## Attributes

Simple key-value pairs:

```hcl
name = "my-app"
count = 42
enabled = true
```

Keys can be identifiers or quoted strings:

```hcl
simple_key = "value"
"key-with-dashes" = "value"
"key.with.dots" = "value"
```

## Blocks

Blocks group related configuration:

```hcl
# Block with no labels
terraform {
    required_version = ">= 1.0"
}

# Block with one label
provider "aws" {
    region = "us-west-2"
}

# Block with two labels
resource "aws_instance" "web" {
    ami = "ami-12345"
}
```

### Nested Blocks

```hcl
resource "aws_instance" "web" {
    ami = "ami-12345"

    ebs_block_device {
        device_name = "/dev/sda1"
        volume_size = 100
    }

    tags {
        Name = "WebServer"
    }
}
```

## Data Types

### Strings

```hcl
# Simple strings
name = "hello world"

# Escape sequences
escaped = "line1\nline2\ttabbed"
quoted = "say \"hello\""
backslash = "path\\to\\file"

# Heredocs for multi-line strings
description = <<EOF
This is a
multi-line string
EOF

# Indented heredocs (strips leading whitespace)
script = <<-EOF
    #!/bin/bash
    echo "Hello"
    EOF
```

### Numbers

```hcl
integer = 42
negative = -17
float = 3.14159
scientific = 1.5e10
hex = 0xFF
octal = 0o755
```

### Booleans

```hcl
enabled = true
disabled = false
```

### Null

```hcl
optional_value = null
```

### Arrays (Tuples)

```hcl
# Simple array
ports = [80, 443, 8080]

# Mixed types
mixed = ["string", 42, true, null]

# Multi-line
tags = [
    "web",
    "production",
    "critical",
]

# Nested arrays
matrix = [
    [1, 2, 3],
    [4, 5, 6],
]
```

### Objects (Maps)

```hcl
# Inline object
metadata = { name = "app", version = "1.0" }

# Multi-line object
config = {
    timeout = 30
    retries = 3
    enabled = true
}

# Nested objects
settings = {
    database = {
        host = "localhost"
        port = 5432
    }
    cache = {
        host = "redis"
        port = 6379
    }
}
```

## Comments

```hcl
# Hash comment (preferred style)
name = "value"

// Double-slash comment
count = 42

/* Block comment
   can span multiple
   lines */
enabled = true

/*
 * Formatted block comment
 */
port = 8080
```

## String Interpolation

```hcl
name = "world"
greeting = "Hello, ${name}!"  # "Hello, world!"

# With expressions
count = 5
message = "Found ${count} items"

# Nested access
server = {
    host = "localhost"
    port = 8080
}
url = "http://${server.host}:${server.port}"
```

## Operators

### Arithmetic

```hcl
sum = 5 + 3        # 8
diff = 10 - 4      # 6
product = 6 * 7    # 42
quotient = 20 / 4  # 5
remainder = 17 % 5 # 2
negation = -value
```

### Comparison

```hcl
equal = a == b
not_equal = a != b
less = a < b
greater = a > b
less_eq = a <= b
greater_eq = a >= b
```

### Logical

```hcl
and_result = a && b
or_result = a || b
not_result = !a
```

### Conditional (Ternary)

```hcl
result = condition ? "yes" : "no"
port = production ? 443 : 8080
```

## Collection Access

### Index Access

```hcl
ports = [80, 443, 8080]
first = ports[0]      # 80
second = ports[1]     # 443
```

### Attribute Access

```hcl
server = {
    host = "localhost"
    port = 8080
}
hostname = server.host  # "localhost"
```

### Splat Expressions

```hcl
users = [
    { name = "alice" },
    { name = "bob" },
]

# Get all names
names = users[*].name  # ["alice", "bob"]
names = users.*.name   # Alternative syntax
```

## For Expressions

### List Comprehension

```hcl
numbers = [1, 2, 3, 4, 5]

# Transform each element
doubled = [for n in numbers : n * 2]
# [2, 4, 6, 8, 10]

# Filter with if
evens = [for n in numbers : n if n % 2 == 0]
# [2, 4]
```

### Object Comprehension

```hcl
users = ["alice", "bob", "charlie"]

# Create object from list
user_map = { for name in users : name => upper(name) }
# { "alice" = "ALICE", "bob" = "BOB", "charlie" = "CHARLIE" }
```

### Key-Value Iteration

```hcl
source = {
    a = 1
    b = 2
}

# Iterate with key and value
swapped = { for k, v in source : v => k }
# { 1 = "a", 2 = "b" }
```

## Function Calls

```hcl
# Built-in style functions
upper_name = upper("hello")
file_content = file("config.json")
encoded = base64encode("data")

# Functions are preserved as structures when not evaluated
password = sensitive("secret123")
# { "__function__": "sensitive", "__args__": ["secret123"] }
```

## Next Steps

- **[Expressions](#doc-cookbooks-expressions)** - Advanced expression handling
- **[JSON Conversion](#doc-cookbooks-json-conversion)** - Convert between HCL and JSON

<a id="doc-cookbooks-expressions"></a>

# Expressions

Advanced expression handling in the HCL parser.

**Use case:** Parsing HCL files with complex expressions, conditionals, and transformations.

## Arithmetic Expressions

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
a = 10
b = 3

sum = 10 + 3        # 13
diff = 10 - 3       # 7
product = 10 * 3    # 30
quotient = 10 / 3   # 3.333...
remainder = 10 % 3  # 1
HCL;

$data = Hcl::parse($hcl);

// Expressions are evaluated at parse time
$data['sum'];       // 13.0
$data['product'];   // 30.0
$data['remainder']; // 1.0
```

## Comparison Expressions

```php
$hcl = <<<'HCL'
x = 5
y = 10

equal = 5 == 5          # true
not_equal = 5 != 10     # true
less = 5 < 10           # true
greater = 10 > 5        # true
less_or_eq = 5 <= 5     # true
greater_or_eq = 10 >= 5 # true
HCL;

$data = Hcl::parse($hcl);
$data['less']; // true
```

## Logical Expressions

```php
$hcl = <<<'HCL'
a = true
b = false

and_result = true && false  # false
or_result = true || false   # true
not_result = !false         # true

# Complex conditions
complex = (5 > 3) && (10 < 20)  # true
HCL;

$data = Hcl::parse($hcl);
$data['complex']; // true
```

## Conditional (Ternary) Expressions

```php
$hcl = <<<'HCL'
production = true

port = production ? 443 : 8080
message = production ? "Production mode" : "Development mode"

# Nested conditionals
env = "staging"
color = env == "prod" ? "red" : env == "staging" ? "yellow" : "green"
HCL;

$data = Hcl::parse($hcl);
$data['port'];    // 443
$data['message']; // "Production mode"
```

## String Interpolation

```php
$hcl = <<<'HCL'
name = "World"
greeting = "Hello, ${name}!"

host = "localhost"
port = 8080
url = "http://${host}:${port}/api"

# Expressions in interpolation
count = 5
status = "Found ${count * 2} items"
HCL;

$data = Hcl::parse($hcl);
$data['greeting']; // "Hello, World!"
$data['url'];      // "http://localhost:8080/api"
```

## Collection Access

### Index Access

```php
$hcl = <<<'HCL'
ports = [80, 443, 8080]

first = ports[0]   # 80
second = ports[1]  # 443
last = ports[2]    # 8080
HCL;

$data = Hcl::parse($hcl);
$data['first']; // 80
```

### Attribute Access

```php
$hcl = <<<'HCL'
server = {
    host = "localhost"
    port = 8080
    config = {
        timeout = 30
    }
}

hostname = server.host
timeout = server.config.timeout
HCL;

$data = Hcl::parse($hcl);
$data['hostname']; // "localhost"
$data['timeout'];  // 30
```

## Splat Expressions

Extract values from lists of objects:

```php
$hcl = <<<'HCL'
users = [
    { name = "alice", age = 30 },
    { name = "bob", age = 25 },
    { name = "charlie", age = 35 },
]

# Bracket splat syntax
names = users[*].name

# Dot splat syntax (alternative)
ages = users.*.age
HCL;

$data = Hcl::parse($hcl);
// Note: Splat expressions return references when not fully resolvable
// They work best with static data in the same file
```

## For Expressions

### List Transformation

```php
$hcl = <<<'HCL'
numbers = [1, 2, 3, 4, 5]

# Double each number
doubled = [for n in numbers : n * 2]
# Result: [2, 4, 6, 8, 10]

# Square each number
squared = [for n in numbers : n * n]
# Result: [1, 4, 9, 16, 25]
HCL;

$data = Hcl::parse($hcl);
$data['doubled']; // [2.0, 4.0, 6.0, 8.0, 10.0]
```

### Filtering with If

```php
$hcl = <<<'HCL'
numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]

# Filter even numbers
evens = [for n in numbers : n if n % 2 == 0]
# Result: [2, 4, 6, 8, 10]

# Filter and transform
even_doubled = [for n in numbers : n * 2 if n % 2 == 0]
# Result: [4, 8, 12, 16, 20]
HCL;

$data = Hcl::parse($hcl);
```

### Object (Map) Expressions

```php
$hcl = <<<'HCL'
names = ["alice", "bob", "charlie"]

# Create object from list
name_lengths = { for name in names : name => length(name) }
# Result: { "alice" = 5, "bob" = 3, "charlie" = 7 }
HCL;
```

### Key-Value Iteration

```php
$hcl = <<<'HCL'
source = {
    a = 1
    b = 2
    c = 3
}

# Swap keys and values
swapped = { for k, v in source : v => k }
# Result: { 1 = "a", 2 = "b", 3 = "c" }

# Transform values
incremented = { for k, v in source : k => v + 1 }
# Result: { "a" = 2, "b" = 3, "c" = 4 }
HCL;
```

## Unary Operators

```php
$hcl = <<<'HCL'
positive = 5
negative = -5
double_neg = --5  # 5

flag = true
inverted = !flag  # false
HCL;

$data = Hcl::parse($hcl);
$data['negative'];   // -5
$data['double_neg']; // 5.0
$data['inverted'];   // false
```

## Parentheses for Grouping

```php
$hcl = <<<'HCL'
# Without parentheses: 2 + 3 * 4 = 14
result1 = 2 + 3 * 4

# With parentheses: (2 + 3) * 4 = 20
result2 = (2 + 3) * 4

# Complex grouping
complex = (10 + 5) * (3 - 1) / 2
HCL;

$data = Hcl::parse($hcl);
$data['result1']; // 14.0
$data['result2']; // 20.0
```

## Function Calls

Functions are parsed but not evaluated unless built-in:

```php
$hcl = <<<'HCL'
# Function calls are preserved as structures
secret = sensitive("password123")
content = file("config.json")
encoded = base64encode("hello")
HCL;

$data = Hcl::parse($hcl);

// Functions are represented as:
$data['secret'];
// [
//     '__function__' => 'sensitive',
//     '__args__' => ['password123'],
// ]

// You can evaluate them in your application:
if (isset($data['secret']['__function__'])) {
    $func = $data['secret']['__function__'];
    $args = $data['secret']['__args__'];
    // Handle based on function name
}
```

## Operator Precedence

From highest to lowest:

1. `()` - Parentheses
2. `!`, `-` (unary) - Logical NOT, negation
3. `*`, `/`, `%` - Multiplication, division, modulo
4. `+`, `-` - Addition, subtraction
5. `<`, `<=`, `>`, `>=` - Comparison
6. `==`, `!=` - Equality
7. `&&` - Logical AND
8. `||` - Logical OR
9. `? :` - Conditional (ternary)

```php
$hcl = <<<'HCL'
# Precedence example
result = 2 + 3 * 4 > 10 && true || false
# Evaluates as: ((2 + (3 * 4)) > 10) && true || false
# = (2 + 12 > 10) && true || false
# = (14 > 10) && true || false
# = true && true || false
# = true || false
# = true
HCL;
```

## Next Steps

- **[JSON Conversion](#doc-cookbooks-json-conversion)** - Convert between HCL and JSON
- **[Validation](#doc-cookbooks-validation)** - Validate HCL configuration

<a id="doc-cookbooks-json-conversion"></a>

# JSON Conversion

Convert between HCL and JSON formats for interoperability.

**Use case:** Integrating with tools that expect JSON, generating HCL from code, or migrating configurations.

## HCL to JSON

### Convert String

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
name = "my-app"
version = "1.0.0"
enabled = true
ports = [80, 443]
HCL;

// Pretty-printed JSON (default)
$json = Hcl::toJson($hcl);
// {
//     "name": "my-app",
//     "version": "1.0.0",
//     "enabled": true,
//     "ports": [80, 443]
// }

// Compact JSON
$json = Hcl::toJson($hcl, pretty: false);
// {"name":"my-app","version":"1.0.0","enabled":true,"ports":[80,443]}
```

### Parse to Array

```php
use Cline\Hcl\Hcl;

$hcl = 'name = "my-app"';
$data = Hcl::parse($hcl);
// ['name' => 'my-app']

// Then encode as JSON yourself if needed
$json = json_encode($data, JSON_PRETTY_PRINT);
```

## JSON to HCL

### Convert String

```php
use Cline\Hcl\Hcl;

$json = '{"name": "my-app", "port": 8080, "enabled": true}';

$hcl = Hcl::fromJson($json);
// name = "my-app"
// port = 8080
// enabled = true
```

### Convert Array

```php
use Cline\Hcl\Hcl;

$data = [
    'name' => 'my-app',
    'version' => '1.0.0',
    'settings' => [
        'timeout' => 30,
        'retries' => 3,
    ],
];

$hcl = Hcl::arrayToHcl($data);
// name = "my-app"
// version = "1.0.0"
// settings = {
//   timeout = 30
//   retries = 3
// }
```

## Block Conversion

Nested structures are intelligently converted to HCL blocks:

### HCL to JSON

```php
$hcl = <<<'HCL'
resource "aws_instance" "web" {
    ami           = "ami-12345"
    instance_type = "t2.micro"

    tags = {
        Name = "WebServer"
    }
}
HCL;

$json = Hcl::toJson($hcl);
```

```json
{
    "resource": {
        "aws_instance": {
            "web": {
                "ami": "ami-12345",
                "instance_type": "t2.micro",
                "tags": {
                    "Name": "WebServer"
                }
            }
        }
    }
}
```

### JSON to HCL

```php
$data = [
    'resource' => [
        'aws_instance' => [
            'web' => [
                'ami' => 'ami-12345',
                'instance_type' => 't2.micro',
            ],
        ],
    ],
];

$hcl = Hcl::arrayToHcl($data);
```

```hcl
resource "aws_instance" "web" {
  ami = "ami-12345"
  instance_type = "t2.micro"
}
```

## Value Types

### Primitives

| HCL | JSON |
|-----|------|
| `"string"` | `"string"` |
| `42` | `42` |
| `3.14` | `3.14` |
| `true` | `true` |
| `false` | `false` |
| `null` | `null` |

### Collections

```php
// Arrays
$hcl = 'ports = [80, 443, 8080]';
$json = Hcl::toJson($hcl);
// {"ports": [80, 443, 8080]}

// Objects
$hcl = 'config = { host = "localhost", port = 8080 }';
$json = Hcl::toJson($hcl);
// {"config": {"host": "localhost", "port": 8080}}
```

### Function Calls

Functions are preserved as special structures:

```php
$hcl = <<<'HCL'
password = sensitive("secret123")
config = file("settings.json")
HCL;

$json = Hcl::toJson($hcl);
```

```json
{
    "password": {
        "__function__": "sensitive",
        "__args__": ["secret123"]
    },
    "config": {
        "__function__": "file",
        "__args__": ["settings.json"]
    }
}
```

## Roundtrip Conversion

Data is preserved when converting HCL -> JSON -> HCL:

```php
use Cline\Hcl\Hcl;

$original = <<<'HCL'
name = "roundtrip-test"
version = 42
enabled = true
tags = ["a", "b", "c"]
HCL;

// HCL -> JSON
$json = Hcl::toJson($original);

// JSON -> HCL
$backToHcl = Hcl::fromJson($json);

// Verify data integrity
$reparsed = Hcl::parse($backToHcl);
assert($reparsed['name'] === 'roundtrip-test');
assert($reparsed['version'] === 42);
assert($reparsed['enabled'] === true);
assert($reparsed['tags'] === ['a', 'b', 'c']);
```

## Formatting Options

### HCL Output

The `arrayToHcl` method produces formatted output:

```php
$data = [
    'server' => [
        'web' => [
            'host' => 'localhost',
            'port' => 8080,
        ],
    ],
];

$hcl = Hcl::arrayToHcl($data);
```

Output:
```hcl
server "web" {
  host = "localhost"
  port = 8080
}
```

### Long Arrays

Arrays exceeding 80 characters are formatted on multiple lines:

```php
$data = [
    'items' => [
        'this is a very long string',
        'another very long string that exceeds the threshold',
    ],
];

$hcl = Hcl::arrayToHcl($data);
```

Output:
```hcl
items = [
  "this is a very long string",
  "another very long string that exceeds the threshold",
]
```

## Special Characters

### String Escaping

Special characters are properly escaped:

```php
$data = [
    'message' => "Hello \"World\"\nNew line\tTab",
    'path' => 'C:\\Users\\name',
];

$hcl = Hcl::arrayToHcl($data);
// message = "Hello \"World\"\nNew line\tTab"
// path = "C:\\Users\\name"
```

### Keys with Special Characters

Keys that aren't valid identifiers are quoted:

```php
$data = [
    'simple_key' => 'value1',
    'key-with-dashes' => 'value2',
    'key.with.dots' => 'value3',
];

$hcl = Hcl::arrayToHcl($data);
// simple_key = "value1"
// "key-with-dashes" = "value2"
// "key.with.dots" = "value3"
```

## Integration Examples

### Generate Terraform Variables

```php
$variables = [
    'region' => 'us-west-2',
    'instance_type' => 't2.micro',
    'instance_count' => 3,
];

$hcl = '';
foreach ($variables as $name => $default) {
    $hcl .= Hcl::arrayToHcl([
        'variable' => [
            $name => ['default' => $default],
        ],
    ]);
}

file_put_contents('variables.tf', $hcl);
```

### Parse and Transform

```php
// Read existing config
$data = Hcl::parseFile('config.hcl');

// Transform
$data['version'] = '2.0.0';
$data['updated_at'] = date('Y-m-d');

// Write back
$hcl = Hcl::arrayToHcl($data);
file_put_contents('config.hcl', $hcl);
```

### Export for APIs

```php
// Parse HCL config
$config = Hcl::parseFile('app.hcl');

// Send as JSON to API
$response = Http::post('https://api.example.com/config', $config);

// Or explicitly convert
$json = Hcl::toJson(file_get_contents('app.hcl'));
$response = Http::withBody($json, 'application/json')
    ->post('https://api.example.com/config');
```

## Next Steps

- **[Validation](#doc-cookbooks-validation)** - Validate HCL configuration files
- **[Getting Started](#doc-cookbooks-getting-started)** - Basic usage guide

<a id="doc-cookbooks-validation"></a>

# Validation

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

## Next Steps

- **[Getting Started](#doc-cookbooks-getting-started)** - Basic usage guide
- **[HCL Syntax](#doc-cookbooks-hcl-syntax)** - Complete syntax reference

<a id="doc-docs-readme"></a>

Parse HCL (HashiCorp Configuration Language) content in PHP.

**Use case:** Reading Terraform, Nomad, Consul, or any HCL-based configuration files in your PHP application.

## Installation

```bash
composer require cline/hcl
```

## Basic Parsing

### Parse HCL String

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
name = "my-app"
version = "1.0.0"
enabled = true
port = 8080
HCL;

$data = Hcl::parse($hcl);

// Result:
// [
//     'name' => 'my-app',
//     'version' => '1.0.0',
//     'enabled' => true,
//     'port' => 8080,
// ]
```

### Parse HCL File

```php
use Cline\Hcl\Hcl;

$data = Hcl::parseFile('/path/to/config.hcl');
```

## Supported Value Types

```php
$hcl = <<<'HCL'
# Strings
name = "hello"
path = "/usr/local/bin"

# Numbers
count = 42
rate = 3.14
scientific = 1e10

# Booleans
enabled = true
disabled = false

# Null
optional = null

# Arrays
ports = [80, 443, 8080]
tags = ["web", "api", "production"]

# Objects/Maps
config = {
    timeout = 30
    retries = 3
}
HCL;

$data = Hcl::parse($hcl);
```

## Blocks

HCL blocks are converted to nested arrays:

```php
$hcl = <<<'HCL'
resource "aws_instance" "web" {
    ami           = "ami-12345"
    instance_type = "t2.micro"

    tags = {
        Name = "WebServer"
    }
}
HCL;

$data = Hcl::parse($hcl);

// Access nested data:
$ami = $data['resource']['aws_instance']['web']['ami'];
// 'ami-12345'
```

## Multiple Blocks

Multiple blocks of the same type are merged:

```php
$hcl = <<<'HCL'
variable "region" {
    default = "us-west-2"
}

variable "instance_type" {
    default = "t2.micro"
}
HCL;

$data = Hcl::parse($hcl);

// [
//     'variable' => [
//         'region' => ['default' => 'us-west-2'],
//         'instance_type' => ['default' => 't2.micro'],
//     ],
// ]
```

## String Interpolation

Template expressions are evaluated when possible:

```php
$hcl = <<<'HCL'
name = "app"
greeting = "Hello, ${name}!"
HCL;

$data = Hcl::parse($hcl);
// greeting => 'Hello, app!'
```

## Comments

Both line and block comments are supported:

```hcl
# This is a line comment
// This is also a line comment

/* This is a
   block comment */

name = "value"
```

## Error Handling

```php
use Cline\Hcl\Hcl;
use Cline\Hcl\Exceptions\ParserException;

try {
    $data = Hcl::parse('invalid { content');
} catch (ParserException $e) {
    echo "Parse error: " . $e->getMessage();
    // Includes line and column information
}
```

<a id="doc-docs-expressions"></a>

Advanced expression handling in the HCL parser.

**Use case:** Parsing HCL files with complex expressions, conditionals, and transformations.

## Arithmetic Expressions

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
a = 10
b = 3

sum = 10 + 3        # 13
diff = 10 - 3       # 7
product = 10 * 3    # 30
quotient = 10 / 3   # 3.333...
remainder = 10 % 3  # 1
HCL;

$data = Hcl::parse($hcl);

// Expressions are evaluated at parse time
$data['sum'];       // 13.0
$data['product'];   // 30.0
$data['remainder']; // 1.0
```

## Comparison Expressions

```php
$hcl = <<<'HCL'
x = 5
y = 10

equal = 5 == 5          # true
not_equal = 5 != 10     # true
less = 5 < 10           # true
greater = 10 > 5        # true
less_or_eq = 5 <= 5     # true
greater_or_eq = 10 >= 5 # true
HCL;

$data = Hcl::parse($hcl);
$data['less']; // true
```

## Logical Expressions

```php
$hcl = <<<'HCL'
a = true
b = false

and_result = true && false  # false
or_result = true || false   # true
not_result = !false         # true

# Complex conditions
complex = (5 > 3) && (10 < 20)  # true
HCL;

$data = Hcl::parse($hcl);
$data['complex']; // true
```

## Conditional (Ternary) Expressions

```php
$hcl = <<<'HCL'
production = true

port = production ? 443 : 8080
message = production ? "Production mode" : "Development mode"

# Nested conditionals
env = "staging"
color = env == "prod" ? "red" : env == "staging" ? "yellow" : "green"
HCL;

$data = Hcl::parse($hcl);
$data['port'];    // 443
$data['message']; // "Production mode"
```

## String Interpolation

```php
$hcl = <<<'HCL'
name = "World"
greeting = "Hello, ${name}!"

host = "localhost"
port = 8080
url = "http://${host}:${port}/api"

# Expressions in interpolation
count = 5
status = "Found ${count * 2} items"
HCL;

$data = Hcl::parse($hcl);
$data['greeting']; // "Hello, World!"
$data['url'];      // "http://localhost:8080/api"
```

## Collection Access

### Index Access

```php
$hcl = <<<'HCL'
ports = [80, 443, 8080]

first = ports[0]   # 80
second = ports[1]  # 443
last = ports[2]    # 8080
HCL;

$data = Hcl::parse($hcl);
$data['first']; // 80
```

### Attribute Access

```php
$hcl = <<<'HCL'
server = {
    host = "localhost"
    port = 8080
    config = {
        timeout = 30
    }
}

hostname = server.host
timeout = server.config.timeout
HCL;

$data = Hcl::parse($hcl);
$data['hostname']; // "localhost"
$data['timeout'];  // 30
```

## Splat Expressions

Extract values from lists of objects:

```php
$hcl = <<<'HCL'
users = [
    { name = "alice", age = 30 },
    { name = "bob", age = 25 },
    { name = "charlie", age = 35 },
]

# Bracket splat syntax
names = users[*].name

# Dot splat syntax (alternative)
ages = users.*.age
HCL;

$data = Hcl::parse($hcl);
// Note: Splat expressions return references when not fully resolvable
// They work best with static data in the same file
```

## For Expressions

### List Transformation

```php
$hcl = <<<'HCL'
numbers = [1, 2, 3, 4, 5]

# Double each number
doubled = [for n in numbers : n * 2]
# Result: [2, 4, 6, 8, 10]

# Square each number
squared = [for n in numbers : n * n]
# Result: [1, 4, 9, 16, 25]
HCL;

$data = Hcl::parse($hcl);
$data['doubled']; // [2.0, 4.0, 6.0, 8.0, 10.0]
```

### Filtering with If

```php
$hcl = <<<'HCL'
numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]

# Filter even numbers
evens = [for n in numbers : n if n % 2 == 0]
# Result: [2, 4, 6, 8, 10]

# Filter and transform
even_doubled = [for n in numbers : n * 2 if n % 2 == 0]
# Result: [4, 8, 12, 16, 20]
HCL;

$data = Hcl::parse($hcl);
```

### Object (Map) Expressions

```php
$hcl = <<<'HCL'
names = ["alice", "bob", "charlie"]

# Create object from list
name_lengths = { for name in names : name => length(name) }
# Result: { "alice" = 5, "bob" = 3, "charlie" = 7 }
HCL;
```

### Key-Value Iteration

```php
$hcl = <<<'HCL'
source = {
    a = 1
    b = 2
    c = 3
}

# Swap keys and values
swapped = { for k, v in source : v => k }
# Result: { 1 = "a", 2 = "b", 3 = "c" }

# Transform values
incremented = { for k, v in source : k => v + 1 }
# Result: { "a" = 2, "b" = 3, "c" = 4 }
HCL;
```

## Unary Operators

```php
$hcl = <<<'HCL'
positive = 5
negative = -5
double_neg = --5  # 5

flag = true
inverted = !flag  # false
HCL;

$data = Hcl::parse($hcl);
$data['negative'];   // -5
$data['double_neg']; // 5.0
$data['inverted'];   // false
```

## Parentheses for Grouping

```php
$hcl = <<<'HCL'
# Without parentheses: 2 + 3 * 4 = 14
result1 = 2 + 3 * 4

# With parentheses: (2 + 3) * 4 = 20
result2 = (2 + 3) * 4

# Complex grouping
complex = (10 + 5) * (3 - 1) / 2
HCL;

$data = Hcl::parse($hcl);
$data['result1']; // 14.0
$data['result2']; // 20.0
```

## Function Calls

Functions are parsed but not evaluated unless built-in:

```php
$hcl = <<<'HCL'
# Function calls are preserved as structures
secret = sensitive("password123")
content = file("config.json")
encoded = base64encode("hello")
HCL;

$data = Hcl::parse($hcl);

// Functions are represented as:
$data['secret'];
// [
//     '__function__' => 'sensitive',
//     '__args__' => ['password123'],
// ]

// You can evaluate them in your application:
if (isset($data['secret']['__function__'])) {
    $func = $data['secret']['__function__'];
    $args = $data['secret']['__args__'];
    // Handle based on function name
}
```

## Operator Precedence

From highest to lowest:

1. `()` - Parentheses
2. `!`, `-` (unary) - Logical NOT, negation
3. `*`, `/`, `%` - Multiplication, division, modulo
4. `+`, `-` - Addition, subtraction
5. `<`, `<=`, `>`, `>=` - Comparison
6. `==`, `!=` - Equality
7. `&&` - Logical AND
8. `||` - Logical OR
9. `? :` - Conditional (ternary)

```php
$hcl = <<<'HCL'
# Precedence example
result = 2 + 3 * 4 > 10 && true || false
# Evaluates as: ((2 + (3 * 4)) > 10) && true || false
# = (2 + 12 > 10) && true || false
# = (14 > 10) && true || false
# = true && true || false
# = true || false
# = true
HCL;
```

<a id="doc-docs-hcl-syntax"></a>

## Attributes

Simple key-value pairs:

```hcl
name = "my-app"
count = 42
enabled = true
```

Keys can be identifiers or quoted strings:

```hcl
simple_key = "value"
"key-with-dashes" = "value"
"key.with.dots" = "value"
```

## Blocks

Blocks group related configuration:

```hcl
# Block with no labels
terraform {
    required_version = ">= 1.0"
}

# Block with one label
provider "aws" {
    region = "us-west-2"
}

# Block with two labels
resource "aws_instance" "web" {
    ami = "ami-12345"
}
```

### Nested Blocks

```hcl
resource "aws_instance" "web" {
    ami = "ami-12345"

    ebs_block_device {
        device_name = "/dev/sda1"
        volume_size = 100
    }

    tags {
        Name = "WebServer"
    }
}
```

## Data Types

### Strings

```hcl
# Simple strings
name = "hello world"

# Escape sequences
escaped = "line1\nline2\ttabbed"
quoted = "say \"hello\""
backslash = "path\\to\\file"

# Heredocs for multi-line strings
description = <<EOF
This is a
multi-line string
EOF

# Indented heredocs (strips leading whitespace)
script = <<-EOF
    #!/bin/bash
    echo "Hello"
    EOF
```

### Numbers

```hcl
integer = 42
negative = -17
float = 3.14159
scientific = 1.5e10
hex = 0xFF
octal = 0o755
```

### Booleans

```hcl
enabled = true
disabled = false
```

### Null

```hcl
optional_value = null
```

### Arrays (Tuples)

```hcl
# Simple array
ports = [80, 443, 8080]

# Mixed types
mixed = ["string", 42, true, null]

# Multi-line
tags = [
    "web",
    "production",
    "critical",
]

# Nested arrays
matrix = [
    [1, 2, 3],
    [4, 5, 6],
]
```

### Objects (Maps)

```hcl
# Inline object
metadata = { name = "app", version = "1.0" }

# Multi-line object
config = {
    timeout = 30
    retries = 3
    enabled = true
}

# Nested objects
settings = {
    database = {
        host = "localhost"
        port = 5432
    }
    cache = {
        host = "redis"
        port = 6379
    }
}
```

## Comments

```hcl
# Hash comment (preferred style)
name = "value"

// Double-slash comment
count = 42

/* Block comment
   can span multiple
   lines */
enabled = true

/*
 * Formatted block comment
 */
port = 8080
```

## String Interpolation

```hcl
name = "world"
greeting = "Hello, ${name}!"  # "Hello, world!"

# With expressions
count = 5
message = "Found ${count} items"

# Nested access
server = {
    host = "localhost"
    port = 8080
}
url = "http://${server.host}:${server.port}"
```

## Operators

### Arithmetic

```hcl
sum = 5 + 3        # 8
diff = 10 - 4      # 6
product = 6 * 7    # 42
quotient = 20 / 4  # 5
remainder = 17 % 5 # 2
negation = -value
```

### Comparison

```hcl
equal = a == b
not_equal = a != b
less = a < b
greater = a > b
less_eq = a <= b
greater_eq = a >= b
```

### Logical

```hcl
and_result = a && b
or_result = a || b
not_result = !a
```

### Conditional (Ternary)

```hcl
result = condition ? "yes" : "no"
port = production ? 443 : 8080
```

## Collection Access

### Index Access

```hcl
ports = [80, 443, 8080]
first = ports[0]      # 80
second = ports[1]     # 443
```

### Attribute Access

```hcl
server = {
    host = "localhost"
    port = 8080
}
hostname = server.host  # "localhost"
```

### Splat Expressions

```hcl
users = [
    { name = "alice" },
    { name = "bob" },
]

# Get all names
names = users[*].name  # ["alice", "bob"]
names = users.*.name   # Alternative syntax
```

## For Expressions

### List Comprehension

```hcl
numbers = [1, 2, 3, 4, 5]

# Transform each element
doubled = [for n in numbers : n * 2]
# [2, 4, 6, 8, 10]

# Filter with if
evens = [for n in numbers : n if n % 2 == 0]
# [2, 4]
```

### Object Comprehension

```hcl
users = ["alice", "bob", "charlie"]

# Create object from list
user_map = { for name in users : name => upper(name) }
# { "alice" = "ALICE", "bob" = "BOB", "charlie" = "CHARLIE" }
```

### Key-Value Iteration

```hcl
source = {
    a = 1
    b = 2
}

# Iterate with key and value
swapped = { for k, v in source : v => k }
# { 1 = "a", 2 = "b" }
```

## Function Calls

```hcl
# Built-in style functions
upper_name = upper("hello")
file_content = file("config.json")
encoded = base64encode("data")

# Functions are preserved as structures when not evaluated
password = sensitive("secret123")
# { "__function__": "sensitive", "__args__": ["secret123"] }
```

<a id="doc-docs-json-conversion"></a>

Convert between HCL and JSON formats for interoperability.

**Use case:** Integrating with tools that expect JSON, generating HCL from code, or migrating configurations.

## HCL to JSON

### Convert String

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
name = "my-app"
version = "1.0.0"
enabled = true
ports = [80, 443]
HCL;

// Pretty-printed JSON (default)
$json = Hcl::toJson($hcl);
// {
//     "name": "my-app",
//     "version": "1.0.0",
//     "enabled": true,
//     "ports": [80, 443]
// }

// Compact JSON
$json = Hcl::toJson($hcl, pretty: false);
// {"name":"my-app","version":"1.0.0","enabled":true,"ports":[80,443]}
```

### Parse to Array

```php
use Cline\Hcl\Hcl;

$hcl = 'name = "my-app"';
$data = Hcl::parse($hcl);
// ['name' => 'my-app']

// Then encode as JSON yourself if needed
$json = json_encode($data, JSON_PRETTY_PRINT);
```

## JSON to HCL

### Convert String

```php
use Cline\Hcl\Hcl;

$json = '{"name": "my-app", "port": 8080, "enabled": true}';

$hcl = Hcl::fromJson($json);
// name = "my-app"
// port = 8080
// enabled = true
```

### Convert Array

```php
use Cline\Hcl\Hcl;

$data = [
    'name' => 'my-app',
    'version' => '1.0.0',
    'settings' => [
        'timeout' => 30,
        'retries' => 3,
    ],
];

$hcl = Hcl::arrayToHcl($data);
// name = "my-app"
// version = "1.0.0"
// settings = {
//   timeout = 30
//   retries = 3
// }
```

## Block Conversion

Nested structures are intelligently converted to HCL blocks:

### HCL to JSON

```php
$hcl = <<<'HCL'
resource "aws_instance" "web" {
    ami           = "ami-12345"
    instance_type = "t2.micro"

    tags = {
        Name = "WebServer"
    }
}
HCL;

$json = Hcl::toJson($hcl);
```

```json
{
    "resource": {
        "aws_instance": {
            "web": {
                "ami": "ami-12345",
                "instance_type": "t2.micro",
                "tags": {
                    "Name": "WebServer"
                }
            }
        }
    }
}
```

### JSON to HCL

```php
$data = [
    'resource' => [
        'aws_instance' => [
            'web' => [
                'ami' => 'ami-12345',
                'instance_type' => 't2.micro',
            ],
        ],
    ],
];

$hcl = Hcl::arrayToHcl($data);
```

```hcl
resource "aws_instance" "web" {
  ami = "ami-12345"
  instance_type = "t2.micro"
}
```

## Value Types

### Primitives

| HCL | JSON |
|-----|------|
| `"string"` | `"string"` |
| `42` | `42` |
| `3.14` | `3.14` |
| `true` | `true` |
| `false` | `false` |
| `null` | `null` |

### Collections

```php
// Arrays
$hcl = 'ports = [80, 443, 8080]';
$json = Hcl::toJson($hcl);
// {"ports": [80, 443, 8080]}

// Objects
$hcl = 'config = { host = "localhost", port = 8080 }';
$json = Hcl::toJson($hcl);
// {"config": {"host": "localhost", "port": 8080}}
```

### Function Calls

Functions are preserved as special structures:

```php
$hcl = <<<'HCL'
password = sensitive("secret123")
config = file("settings.json")
HCL;

$json = Hcl::toJson($hcl);
```

```json
{
    "password": {
        "__function__": "sensitive",
        "__args__": ["secret123"]
    },
    "config": {
        "__function__": "file",
        "__args__": ["settings.json"]
    }
}
```

## Roundtrip Conversion

Data is preserved when converting HCL -> JSON -> HCL:

```php
use Cline\Hcl\Hcl;

$original = <<<'HCL'
name = "roundtrip-test"
version = 42
enabled = true
tags = ["a", "b", "c"]
HCL;

// HCL -> JSON
$json = Hcl::toJson($original);

// JSON -> HCL
$backToHcl = Hcl::fromJson($json);

// Verify data integrity
$reparsed = Hcl::parse($backToHcl);
assert($reparsed['name'] === 'roundtrip-test');
assert($reparsed['version'] === 42);
assert($reparsed['enabled'] === true);
assert($reparsed['tags'] === ['a', 'b', 'c']);
```

## Formatting Options

### HCL Output

The `arrayToHcl` method produces formatted output:

```php
$data = [
    'server' => [
        'web' => [
            'host' => 'localhost',
            'port' => 8080,
        ],
    ],
];

$hcl = Hcl::arrayToHcl($data);
```

Output:
```hcl
server "web" {
  host = "localhost"
  port = 8080
}
```

### Long Arrays

Arrays exceeding 80 characters are formatted on multiple lines:

```php
$data = [
    'items' => [
        'this is a very long string',
        'another very long string that exceeds the threshold',
    ],
];

$hcl = Hcl::arrayToHcl($data);
```

Output:
```hcl
items = [
  "this is a very long string",
  "another very long string that exceeds the threshold",
]
```

## Integration Examples

### Generate Terraform Variables

```php
$variables = [
    'region' => 'us-west-2',
    'instance_type' => 't2.micro',
    'instance_count' => 3,
];

$hcl = '';
foreach ($variables as $name => $default) {
    $hcl .= Hcl::arrayToHcl([
        'variable' => [
            $name => ['default' => $default],
        ],
    ]);
}

file_put_contents('variables.tf', $hcl);
```

### Parse and Transform

```php
// Read existing config
$data = Hcl::parseFile('config.hcl');

// Transform
$data['version'] = '2.0.0';
$data['updated_at'] = date('Y-m-d');

// Write back
$hcl = Hcl::arrayToHcl($data);
file_put_contents('config.hcl', $hcl);
```

### Export for APIs

```php
// Parse HCL config
$config = Hcl::parseFile('app.hcl');

// Send as JSON to API
$response = Http::post('https://api.example.com/config', $config);

// Or explicitly convert
$json = Hcl::toJson(file_get_contents('app.hcl'));
$response = Http::withBody($json, 'application/json')
    ->post('https://api.example.com/config');
```

<a id="doc-docs-validation"></a>

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
