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
