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

## Documentation

- **[Getting Started](cookbooks/getting-started.md)** - Installation and basic usage
- **[HCL Syntax](cookbooks/hcl-syntax.md)** - Complete syntax reference
- **[Expressions](cookbooks/expressions.md)** - Operators, conditionals, and for expressions
- **[JSON Conversion](cookbooks/json-conversion.md)** - Convert between HCL and JSON
- **[Validation](cookbooks/validation.md)** - Validate configuration files

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

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
