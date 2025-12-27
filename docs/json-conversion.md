---
title: JSON Conversion
description: Convert between HCL and JSON formats for interoperability.
---

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
