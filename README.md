# SchoolPalm Module SDK

Official SDK for building SchoolPalm modules.

## Installation

Install the package via Composer:

```bash
composer require schoolpalm/module-sdk
```

## Requirements

- PHP ^8.2
- Laravel ^12.0
- Inertia.js ^2.0

## Usage

This SDK provides tools and scaffolding for creating SchoolPalm modules. It includes console commands for generating modules, validating manifests, and more.

### Console Commands

- `php artisan module:make <name>` - Create a new module
- `php artisan module:generate-stubs` - Generate stubs for module development
- `php artisan module:validate <path>` - Validate a module manifest

### Module Structure

Modules should follow the structure defined in the SDK. Use the provided generators to scaffold new modules.

For more detailed documentation, refer to the [SchoolPalm Documentation](https://schoolpalm.com/docs).

## Contributing

Contributions are welcome! Please see the [contributing guidelines](CONTRIBUTING.md) for details.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
