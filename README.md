[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

A pure PHP parser for HCL (HashiCorp Configuration Language). Parse, validate, and convert HCL to JSON and back.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/hcl
```

## Usage

### Parse HCL

```php
use Cline\Hcl\Hcl;

$hcl = <<<'HCL'
io_mode = "async"

service "http" "web_proxy" {
  listen_addr = "127.0.0.1:8080"

  process "main" {
    command = ["/usr/local/bin/awesome-app", "server"]
  }
}
HCL;

$data = Hcl::parse($hcl);
// [
//   'io_mode' => 'async',
//   'service' => [
//     'http' => [
//       'web_proxy' => [
//         'listen_addr' => '127.0.0.1:8080',
//         'process' => [
//           'main' => ['command' => ['/usr/local/bin/awesome-app', 'server']]
//         ]
//       ]
//     ]
//   ]
// ]
```

### Convert HCL to JSON

```php
$json = Hcl::toJson($hcl);
```

### Convert JSON to HCL

```php
$hcl = Hcl::fromJson($json);
```

### Validate HCL Syntax

```php
use Cline\Hcl\Validation\HclValidator;

$validator = new HclValidator();
$result = $validator->validate($hcl);

if ($result->isValid()) {
    echo "Valid HCL!";
} else {
    foreach ($result->errors() as $error) {
        echo "{$error->message} at line {$error->range->fromLine}\n";
    }
}
```

## Features

- **Block parsing** - Nested blocks with multiple labels (`service "http" "web" { }`)
- **All HCL types** - Strings, numbers, booleans, arrays, objects
- **Function calls** - Captured as structured data (`file("path")` → `['__function__' => 'file', '__args__' => ['path']]`)
- **Interpolation** - `"${var.name}"` preserved in output
- **Comments** - Hash (`#`), slash (`//`), and multi-line (`/* */`)
- **Heredocs** - Standard and indented (`<<EOF`, `<<-EOF`)
- **Validation** - Syntax validation with line/column error locations
- **Bidirectional conversion** - HCL ↔ JSON

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://github.com/faustbrian/hcl/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/hcl.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/hcl.svg

[link-tests]: https://github.com/faustbrian/hcl/actions
[link-packagist]: https://packagist.org/packages/cline/hcl
[link-downloads]: https://packagist.org/packages/cline/hcl
[link-security]: https://github.com/faustbrian/hcl/security
[link-maintainer]: https://github.com/faustbrian
[link-contributors]: ../../contributors
