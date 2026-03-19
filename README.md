# Laravel OSDD

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xefi/laravel-osdd.svg?style=flat-square)](https://packagist.org/packages/xefi/laravel-osdd)
[![Tests](https://github.com/xefi/laravel-osdd/actions/workflows/tests.yml/badge.svg)](https://github.com/xefi/laravel-osdd/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/xefi/laravel-osdd.svg?style=flat-square)](LICENSE)

![Laravel OSDD Landscape](https://raw.githubusercontent.com/xefi/art/main/laravel-osdd-landscape.png)

A Laravel package that restructures your application into independently composable **layers** — each one a self-contained Composer package with its own models, migrations, seeders, tests, and service provider.

Full documentation at **[laravel-osdd.xefi.com](https://laravel-osdd.xefi.com/)**.

---

## The Problem

Standard Laravel applications accumulate unrelated code in shared directories. As the application grows, `app/` mixes users, orders, products, and infrastructure with no clear domain boundaries. Refactoring becomes costly and testing becomes slow.

## The Solution

OSDD isolates each domain concern into its own Composer package:

```
functional/
  users/       ← Independent layer (composer.json, src/, database/, tests/)
  billing/
technical/
  osdd/        ← OSDD configuration layer
```

---

## Requirements

- PHP `^8.3`
- Laravel `^12.0` or `^13.0`

---

## Installation

```bash
composer require xefi/laravel-osdd
php artisan osdd:start
```

---

## Commands

| Command | Description |
|---------|-------------|
| `osdd:start` | Scaffold a fresh project with full OSDD architecture |
| `osdd:layer` | Create a new layer interactively or with arguments |
| `osdd:seed` | Run seeders registered across all layers |
| `osdd:phpunit` | Sync `phpunit.xml` with each layer's test suite |

All standard `make:*` commands are available as `osdd:*` variants, placing generated files inside the target layer. See the **[full command reference](https://laravel-osdd.xefi.com/commands)**.

---

## Support us

[![](https://raw.githubusercontent.com/xefi/art/main/support-landscape.svg)](https://www.xefi.com)

Since 1997, XEFI is a leader in IT performance support for small and medium-sized businesses through its nearly 200 local agencies based in France, Belgium, Switzerland and Spain. A one-stop shop for IT, office automation, software, [digitalization](https://www.xefi.com/solutions-software/), print and cloud needs. [Want to work with us ?](https://carriere.xefi.fr/metiers-software)

---

## License

MIT — see [LICENSE](LICENSE).
