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
