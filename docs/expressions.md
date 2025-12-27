---
title: Expressions
description: Advanced expression handling in the HCL parser.
---

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
