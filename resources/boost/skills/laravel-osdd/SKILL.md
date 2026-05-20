---
name: laravel-osdd
description: Use when working in a Laravel application that depends on xefi/laravel-osdd. This package replaces the standard app/, database/ and config/ folders with isolated "layers", each one a self-contained Composer package with its own models, migrations, seeders, tests and service provider. Trigger any time you would normally run a make:* command, write a migration, create a model, register a seeder, or add a provider — files must live inside a layer, not in app/. Also trigger when the user mentions OSDD, "layers", osdd:* artisan commands, or asks to scaffold a new domain.
---

# Laravel OSDD

`xefi/laravel-osdd` restructures a Laravel application into independent **layers**. Each layer is its own Composer package, installed via a local path repository, with its own namespace, service provider, migrations, seeders, and tests. The standard `app/`, `database/` and root `config/` directories are removed by `osdd:start` and never recreated.

## When to use this skill

Use this skill whenever `xefi/laravel-osdd` is in the project's `composer.json`. From that point on:

- **Never** create files under `app/`, `database/migrations/`, `database/seeders/`, `database/factories/`, or root `config/`. Those directories do not exist in an OSDD project.
- **Never** run a vanilla `make:*` command (`make:model`, `make:migration`, `make:controller`, …). Always use the `osdd:*` equivalent, which targets a specific layer.
- **Never** edit `bootstrap/providers.php` to register a domain provider. Layer service providers are auto-registered via each layer's `composer.json` (`extra.laravel.providers`).
- **Never** add a `psr-4` entry for `App\\`, `Database\\Factories\\` or `Database\\Seeders\\` to the root `composer.json`. `osdd:start` strips them on purpose.

## Features

- **Project bootstrap**: convert a fresh Laravel app into the OSDD layout (deletes `app/`, `database/`, root `config/`; creates a starter `functional/users/` and `technical/osdd/`). Example usage:

```bash
php artisan osdd:start
```

- **Layer creation**: scaffold a new self-contained layer (Composer package with its own `src/`, `database/`, `tests/`, and service provider) and register it in the root `composer.json` via a wildcard path repository. Example usage:

```bash
php artisan osdd:layer functional/orders --generators=migration,model,factory,service-provider,test
```

- **`osdd:*` make mirrors**: every Laravel `make:*` command has an `osdd:*` equivalent that targets a layer via `--layer=vendor/package` (covers `model`, `controller`, `migration`, `factory`, `seeder`, `policy`, `request`, `resource`, `test`, `config`, `view`, `class`, and more). Example usage:

```bash
php artisan osdd:model Order --layer=functional/orders --factory --migration
```

- **Layer-scoped seeders**: every layer registers its seeders through `LayerServiceProvider::loadSeeders([...], priority: 0)`; `osdd:seed` then runs them all across layers in priority order. Example usage:

```bash
php artisan osdd:seed --fresh
```

- **Layer-scoped config**: `osdd:config` creates `{layer}/config/{name}.php` and automatically injects `$this->overrideConfigFrom(...)` into the layer's `register()` method. Example usage:

```bash
php artisan osdd:config orders --layer=functional/orders
```

- **PHPUnit suite sync**: `osdd:phpunit` walks every discovered layer and adds a `<testsuite>` entry for each one to the root `phpunit.xml`. Idempotent. Example usage:

```bash
php artisan osdd:phpunit
```

- **`LayerServiceProvider` base class**: every layer's own provider extends `Xefi\LaravelOSDD\LayerServiceProvider` (not Laravel's `ServiceProvider`) to gain `loadSeeders()` and `overrideConfigFrom()`. Example usage:

```php
class OrdersServiceProvider extends \Xefi\LaravelOSDD\LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadSeeders([OrdersSeeder::class]);
    }
}
```

- **Tinker auto-aliases**: short class names (`User`, `Order`, …) are aliased to the first matching FQCN across all layers, so `User::find(1)` works without a `use` statement inside `php artisan tinker`.

### How to detect an OSDD project

Three reliable signals (any one is enough):

1. `xefi/laravel-osdd` appears in `composer.json` under `require`.
2. A `functional/` or `technical/` directory exists at the project root with subdirectories containing `composer.json` files of `"type": "layer"`.
3. `bootstrap/providers.php` is empty (`return [];`) and there is no `app/Models/` directory.

If none of these hold, this skill does not apply — fall back to standard Laravel conventions.

## Anatomy of a layer

A layer lives in a subdirectory of one of the configured layer paths (defaults: `functional/` and `technical/`, see `config/osdd.php`). The layout produced by `osdd:layer` is:

```
functional/orders/
├── composer.json                       ← "type": "layer", required
├── src/
│   ├── Models/
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   ├── Policies/
│   └── Providers/OrdersServiceProvider.php
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
└── tests/
    ├── Feature/
    └── Unit/
```

Two non-negotiable rules in the layer's `composer.json`:

1. `"type": "layer"` — this is what makes `LayersCollection` discover the directory. Without it the layer is invisible.
2. PSR-4 maps the **vendor + package** name in PascalCase to `src/`. For `functional/orders` that is `Functional\Orders\\` → `src/`. Sub-namespaces follow:
   - Models → `Functional\Orders\Models` (`src/Models/`)
   - Controllers → `Functional\Orders\Http\Controllers` (`src/Http/Controllers/`)
   - Service providers → `Functional\Orders\Providers` (`src/Providers/`)
   - Factories → `Functional\Orders\Database\Factories` (`database/factories/`)
   - Seeders → `Functional\Orders\Database\Seeders` (`database/seeders/`)
   - Feature tests → `Functional\Orders\Tests\Feature` (`tests/Feature/`)
   - Unit tests → `Functional\Orders\Tests\Unit` (`tests/Unit/`)

The vendor-segment of the layer name maps to the top-level config category (`functional`, `technical`). Anything under `functional/` is domain code; anything under `technical/` is cross-cutting infrastructure (auth, OSDD config, etc.).

### Layer naming rules

`osdd:layer` validates the package part of the name against `/^[a-z0-9-]+$/` — lowercase letters, digits, hyphens only. Hyphens are converted to PascalCase for the namespace:

| Layer name | Namespace root | Service provider |
|---|---|---|
| `functional/orders` | `Functional\Orders` | `OrdersServiceProvider` |
| `functional/my-auth-layer` | `Functional\MyAuthLayer` | `MyAuthLayerServiceProvider` |
| `technical/feature-flags` | `Technical\FeatureFlags` | `FeatureFlagsServiceProvider` |

Pick names that read as a single noun-phrase. The package is converted via `Str::studly()` for the namespace and `Str::snake(Str::pluralStudly(...))` for the default table name (e.g. `order` → `orders`, `feature-flag` → `feature_flags`).

### What goes in `functional/` vs `technical/`

- **`functional/`** holds business domains — Users, Orders, Billing, Catalog. Each layer owns its own models, controllers, business rules, and tests. If a non-technical stakeholder would recognise the name, it belongs here.
- **`technical/`** holds cross-cutting infrastructure — the `osdd` config layer, authentication wiring, feature flags, observability, base middleware. If multiple functional layers consume it, or it has no domain meaning, it belongs here.

Don't put HTTP plumbing (controllers, requests, routes) in a `technical/` layer — domains stay self-contained. Don't duplicate the same model in two layers — if `Order` and `Customer` interact, pick the dominant domain and add a Composer `require` from the other.

## The two service providers

- **`Xefi\LaravelOSDD\LaravelOSDDServiceProvider`** — the package's own provider. Registers all `osdd:*` commands, the `SeederRegistry` singleton, publishes `config/osdd.php`, and (when `php artisan tinker` runs) builds class-name aliases so you can reference layer models by their short name in tinker without typing the full FQCN.

- **`Xefi\LaravelOSDD\LayerServiceProvider`** — the abstract base class **every layer's own provider must extend** (not Laravel's `ServiceProvider`). It exposes:
  - `loadSeeders(array $seeders, int $priority = 0)` — registers seeder classes with the global registry. Lower priority runs first. This is the **only** way to make a seeder runnable by `osdd:seed`.
  - `overrideConfigFrom(string $path, string $key)` — like `mergeConfigFrom`, but the layer's values win over previously-loaded defaults. Used by the technical OSDD layer to override `config('osdd')` from inside the application.

A freshly generated layer provider looks like:

```php
namespace Functional\Orders\Providers;

use Functional\Orders\Database\Seeders\OrdersSeeder;
use Xefi\LaravelOSDD\LayerServiceProvider;

class OrdersServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadSeeders([OrdersSeeder::class]);
        }
    }

    public function register(): void
    {
        //
    }
}
```

### What the generator does NOT wire for you

The generated provider only handles **migrations** and **seeders**. Anything else a layer needs must be added by hand to `boot()`:

```php
public function boot(): void
{
    if ($this->app->runningInConsole()) {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadSeeders([OrdersSeeder::class]);
    }

    // Add these only when the layer has them — they are NOT generated.
    $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'orders');
    $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'orders');

    // Policies aren't auto-discovered across layers — register them explicitly.
    Gate::policy(Order::class, OrderPolicy::class);
}
```

There is no `routes/`, `lang/`, or `resources/views/` directory created by `osdd:layer` — create them manually when needed (or generate views with `osdd:view`, which creates `resources/views/` on demand). Route files must always be added by hand.

### Tests directory

`osdd:layer --generators=test` only creates `tests/Feature/`. If you need `tests/Unit/`, generate a unit test once with `osdd:test SomeTest --unit --layer=…` — the directory will be created on first use.

## Commands

### Project bootstrap

```bash
php artisan osdd:start
```

Run **once**, immediately after `composer require xefi/laravel-osdd`, on a fresh Laravel app. It:

1. Creates `functional/users/` (a starter Users layer with Model, Factory, Seeder, and migration).
2. Creates `technical/osdd/` (a layer that owns the `config/osdd.php` override).
3. Deletes `app/`, `database/`, and root `config/`.
4. Clears legacy `App\\`, `Database\\Factories\\`, `Database\\Seeders\\` PSR-4 entries from the root `composer.json`.
5. Empties `bootstrap/providers.php` (layer providers are auto-discovered).
6. Prompts `composer update`.

**Destructive.** Only run on a fresh project. Never run on an existing OSDD project — it will wipe the Users layer and re-create it.

### Create a layer

```bash
# Interactive
php artisan osdd:layer

# Non-interactive
php artisan osdd:layer functional/orders \
  --target-path=/abs/path/to/functional \
  --generators=migration,model,factory,service-provider,test
```

Available generators: `migration`, `model`, `factory`, `seeder`, `service-provider`, `test`, `controller`, `policy`. Defaults: `migration, model, factory, service-provider, test`.

Behind the scenes `osdd:layer`:

1. Writes the layer's `composer.json` first (so make commands can discover it).
2. Calls each chosen generator (e.g. `osdd:migration --create=orders --layer=functional/orders`).
3. When both `model` and `factory` are selected, it passes `--factory` to `osdd:model` instead of running `osdd:factory` twice.
4. Generates the service provider from `stubs/layer/service-provider.stub` and injects its FQCN into `extra.laravel.providers` in the layer's `composer.json`.
5. Registers a path repository in the root `composer.json` using a **wildcard URL** (`./functional/*`) and adds `"vendor/package": "*"` under `require`.
6. Optionally runs `composer update vendor/package`.

The wildcard is intentional — every layer added to that directory will be picked up by a single repository entry; don't replace it with a per-layer URL.

### Generate code inside an existing layer

Every Laravel `make:*` command has an `osdd:*` mirror that accepts `--layer=vendor/package`. If `--layer` is omitted, Boost will be asked interactively via `Laravel\Prompts\search`.

```bash
php artisan osdd:model Order --layer=functional/orders --factory --migration
php artisan osdd:controller OrderController --layer=functional/orders --resource --model=Order
php artisan osdd:migration add_status_to_orders_table --table=orders --layer=functional/orders
php artisan osdd:request StoreOrderRequest --layer=functional/orders
php artisan osdd:test Feature/Orders/PlaceOrderTest --layer=functional/orders
php artisan osdd:policy OrderPolicy --layer=functional/orders --model=Order
php artisan osdd:seeder OrdersSeeder --layer=functional/orders
php artisan osdd:factory OrderFactory --layer=functional/orders
php artisan osdd:config orders --layer=functional/orders
php artisan osdd:view orders.index --layer=functional/orders
php artisan osdd:class Services/OrderPricer --layer=functional/orders
```

Full list of mirrored generators: `cast`, `channel`, `class`, `config`, `console`, `controller`, `enum`, `event`, `exception`, `factory`, `interface`, `job`, `listener`, `mail`, `middleware`, `migration`, `model`, `notification`, `observer`, `policy`, `request`, `resource`, `rule`, `scope`, `seeder`, `provider`, `test`, `trait`, `view`.

Notes on a few that diverge from vanilla Laravel:

- **`osdd:model`** — bypasses Laravel's interactive "also create…" prompt. Use the flags directly: `--factory`, `--migration`, `--seed`, `--controller`, `--resource`, `--api`, `--requests`, `--policy`, or `--all`. When `--factory` is set, the generated model uses a custom stub that adds `use HasFactory;` and `#[UseFactory(...)]` pointing at the layer's factory namespace. Without `--factory`, the model is bare — neither the trait nor the attribute is added. **Nested models work**: `osdd:model Admin/User --layer=…` creates `src/Models/Admin/User.php` with namespace `…\Models\Admin`.
- **`osdd:config <name>`** — creates `{layer}/config/{name}.php` **and** injects `$this->overrideConfigFrom(__DIR__ . '/../../config/{name}.php', '{name}');` into the layer's `register()` method. The injection is idempotent; don't add this line by hand. If the layer's service provider doesn't exist, the config file is still created but no override is wired.
- **`osdd:migration`** — places the file in the resolved layer's `database/migrations/` directory; the layer's service provider already loads it via `loadMigrationsFrom`.
- **`osdd:test`** — `--unit` switches the namespace to `Tests\Unit` and the path to `tests/Unit/`; otherwise it's `Tests\Feature` / `tests/Feature/`.
- **`osdd:policy --model=Name`** — generates a full Laravel policy with type-hinted model methods (`viewAny`, `view`, `create`, …). Without `--model`, a minimal stub class is generated.
- **`osdd:controller --requests`** — generates `Store{Model}Request` and `Update{Model}Request` in the same layer alongside the controller.

### Run seeders across all layers

```bash
php artisan osdd:seed
php artisan osdd:seed --fresh    # runs migrate:fresh first
php artisan osdd:seed --refresh  # runs migrate:refresh first
```

`osdd:seed` iterates the `SeederRegistry` and calls `db:seed --class=…` for every registered class. A seeder appears in this list **only** if its layer's service provider called `$this->loadSeeders([...])`. If `osdd:seed` reports no seeders, the missing piece is a `loadSeeders()` call in the relevant `LayerServiceProvider::boot()`.

### Sync `phpunit.xml` with layer test suites

```bash
php artisan osdd:phpunit
```

Walks every discovered layer and, for each one with a `tests/` directory, adds a `<testsuite name="vendor/package"><directory>relative/path/to/tests</directory></testsuite>` entry to the root `phpunit.xml`. Idempotent — existing suites are skipped. Run after creating a layer if you want `vendor/bin/phpunit` to pick it up automatically.

## Configuration

`config/osdd.php` (publishable via `php artisan vendor:publish --tag=osdd`):

```php
return [
    'layers' => [
        'paths' => [
            'functional' => base_path('functional'),
            'technical'  => base_path('technical'),
        ],
    ],
];
```

`LayersCollection::fromConfig()` reads `config('osdd.layers.paths')`, scans each path **recursively**, and treats any directory containing a `composer.json` with `"type": "layer"` as a layer. Nested layers (e.g. `functional/billing/invoices/`) are supported as long as each level satisfies that contract; once a layer is matched at a given depth, scanning stops descending into it.

To add a new category (e.g. `support/`), append it to `osdd.layers.paths` from inside the `technical/osdd` layer's `OsddServiceProvider::boot()` using `$this->overrideConfigFrom(...)`. Don't edit the package's own `config/osdd.php` directly.

## Idiomatic file content

The package targets Laravel 12+ and uses modern attribute-based metadata on models. When writing or editing layer files by hand, match this style — the generators do.

### Model (with factory)

```php
namespace Functional\Orders\Models;

use Functional\Orders\Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reference', 'customer_id', 'total_cents', 'status'])]
#[Hidden(['internal_notes'])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'status'      => OrderStatus::class,
        ];
    }
}
```

Use `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, and the `casts()` method (not the `$casts` property). Don't manually add `$factory =` overrides — `#[UseFactory]` is the modern equivalent.

### Factory

```php
namespace Functional\Orders\Database\Factories;

use Functional\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'reference'   => fake()->unique()->bothify('ORD-#####'),
            'total_cents' => fake()->numberBetween(1_000, 100_000),
            'status'      => 'pending',
        ];
    }
}
```

### Seeder (wired in the provider)

```php
namespace Functional\Orders\Database\Seeders;

use Functional\Orders\Models\Order;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        Order::factory()->count(25)->create();
    }
}
```

The seeder is registered by the layer provider:

```php
$this->loadSeeders([OrdersSeeder::class]);
```

If you have multiple seeders, declare relative order with the second argument:

```php
$this->loadSeeders([CountriesSeeder::class], priority: -10);  // runs first
$this->loadSeeders([OrdersSeeder::class],   priority: 0);     // default
$this->loadSeeders([AuditSeeder::class],    priority: 10);    // runs last
```

Cross-layer ordering is global — if `functional/billing` depends on `functional/users` being seeded first, give the users seeder a lower priority.

### Migration

A migration is a vanilla Laravel anonymous-class migration; OSDD only changes its location to `{layer}/database/migrations/`. There is no special signature.

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();  // points at users table from another layer — fine
            $table->string('reference')->unique();
            $table->unsignedInteger('total_cents');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

Foreign keys to tables defined in other layers work as normal — all layer migrations land in the same database. The order is determined by the migration filename timestamp, which is generated at `osdd:migration` time, so create the referenced table's migration first.

### Tests

Feature tests live at `{layer}/tests/Feature/`, unit tests at `{layer}/tests/Unit/`. Their namespace is `…\Tests\Feature` / `…\Tests\Unit` so they're autoloaded via the layer's standard PSR-4 mapping (which covers `src/`; tests are autoloaded because the layer base namespace covers any path mounted via the layer's composer.json — generators put them under the layer's namespace so PHPUnit can find them by class name).

```php
namespace Functional\Orders\Tests\Feature;

use Functional\Orders\Models\Order;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_place_an_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/orders', ['total_cents' => 5_000]);

        $response->assertCreated();
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }
}
```

`RefreshDatabase` runs **all** layers' migrations because they're all loaded by their respective providers — there is no per-layer database isolation at runtime.

## Tinker

When `php artisan tinker` runs, the package walks every layer's `src/` and registers two things:

- All layer root namespaces are added to `tinker.alias`.
- A custom autoloader maps every short class name (e.g. `User`, `Order`) to the first matching FQCN it finds across layers.

Practical consequence: you can type `User::find(1)` in tinker without `use Functional\Users\Models\User;`. If two layers define a `User` class, the first one discovered wins — disambiguate by using the FQCN.

## Navigating an existing OSDD codebase

Before adding code, locate the right layer. The `app/` and root `database/` directories don't exist — every file is under a layer.

1. **Find the layer that owns a concept.** Look for `{path}/{name}/composer.json` and check the `name` field. The layer name (`functional/orders`) tells you the path (`functional/orders/`) and namespace root (`Functional\Orders`).
2. **List all layers.** `find functional technical -maxdepth 2 -name composer.json -exec grep -l '"type": "layer"' {} \;` (or browse the two configured directories — `functional/` and `technical/` by default).
3. **Find where a class lives.** Search for the class name in the relevant layer's `src/`. Layers don't share code by accident — if a class isn't there, it's in another layer, in the package itself, or in `vendor/`.
4. **Find a route.** Routes are layer-local. Check each layer's `routes/` directory and its `LayerServiceProvider::boot()` for `loadRoutesFrom(...)` calls. Routes are only mounted if the layer provider wires them.
5. **Find a config key.** Layer-defined config lives in `{layer}/config/{name}.php` and is loaded into `config('{name}')` via `overrideConfigFrom`. Look at the layer's `LayerServiceProvider::register()` to see what keys it overrides.

When a user asks "where should I add X?", the answer is always "in the layer responsible for X" — never `app/`. If no layer matches, the answer is "create a new layer with `osdd:layer`."

## Inspecting layer state

When debugging an OSDD project:

- **List discovered layers** — start `tinker` and run `Xefi\LaravelOSDD\Layers\LayersCollection::fromConfig()->map(fn($l) => $l->manifest->name())->all();`. If a directory you expect isn't listed, its `composer.json` is missing `"type": "layer"` or the directory isn't under a path in `config('osdd.layers.paths')`.
- **List registered seeders** — `app(\Xefi\LaravelOSDD\SeederRegistry::class)->seeders();`. Empty? No layer provider called `loadSeeders()`.
- **List layer providers actually loaded** — `app()->getLoadedProviders()` and filter by namespace.
- **Check Composer wiring** — the root `composer.json` should have a `repositories[]` entry of type `path` with URL `./functional/*` (and/or `./technical/*`) and a `require` entry like `"functional/orders": "*"` for each layer. If a layer's classes don't autoload, run `composer dump-autoload` first; if that fails, the layer's `composer.json` is malformed.
- **Re-sync `phpunit.xml`** — run `osdd:phpunit` after creating a layer. If `vendor/bin/phpunit --testsuite=functional/orders` reports "unknown testsuite", the sync hasn't run.

## Recipes

### Bootstrap an OSDD project from scratch

```bash
composer create-project laravel/laravel my-app
cd my-app
composer require xefi/laravel-osdd
php artisan osdd:start            # accept "composer update" prompt
php artisan osdd:layer functional/orders   # or interactive
php artisan migrate
```

### Add a new domain to an existing OSDD project

```bash
php artisan osdd:layer functional/billing --generators=migration,model,factory,service-provider,seeder,test
# Edit functional/billing/database/seeders/BillingSeeder.php as needed
php artisan osdd:phpunit          # add the layer's testsuite to phpunit.xml
php artisan osdd:seed --fresh     # seed the new layer
```

### End-to-end: add a Resource to an existing layer

For a fully wired CRUD resource (`Invoice` in `functional/billing`) — migration, model with factory, controller with form requests, policy, route, test:

```bash
# 1. Generate everything in one shot.
php artisan osdd:model Invoice --layer=functional/billing --all
#   → creates model, factory, seeder, migration, resource controller, policy, requests

# 2. Add a route. Routes are never generated — create routes/web.php manually
#    in the layer, then load it from the layer provider.
#    functional/billing/routes/web.php:
#      Route::resource('invoices', \Functional\Billing\Http\Controllers\InvoiceController::class);
#    functional/billing/src/Providers/BillingServiceProvider.php boot():
#      $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

# 3. Wire the policy. Auto-discovery doesn't cross layers — register it explicitly.
#    In BillingServiceProvider::boot():
#      Gate::policy(Invoice::class, InvoicePolicy::class);

# 4. Write a feature test.
php artisan osdd:test InvoiceCrudTest --layer=functional/billing

# 5. Run it.
php artisan osdd:phpunit
vendor/bin/phpunit --testsuite=functional/billing
```

### Register a layer's policies and observers

Policies and observers are not auto-registered across layers. Wire them in the layer's `LayerServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // … existing migrations/seeders …

    Gate::policy(Order::class, OrderPolicy::class);
    Order::observe(OrderObserver::class);
}
```

### Cross-layer code reference

A layer can depend on another layer's public API by typing against its FQCN — there is no special boundary mechanism, the isolation is purely organisational. If layer A needs layer B's models or services, add `"vendor/b-package": "*"` to layer A's `composer.json` `require`. Avoid bidirectional dependencies; if two layers need each other, the shared concept belongs in a third layer (often under `technical/`).

### Override a layer's config from another layer

Inside a `LayerServiceProvider::boot()`:

```php
$this->overrideConfigFrom(__DIR__ . '/../../config/billing.php', 'billing');
```

Use `overrideConfigFrom` (the layer base-class helper) rather than Laravel's `mergeConfigFrom` when you need the layer's values to win over previously-loaded defaults.

## Testing workflow

OSDD doesn't replace PHPUnit — it organises test files into per-layer suites declared in `phpunit.xml`.

### After creating a layer

```bash
php artisan osdd:phpunit
```

This adds `<testsuite name="vendor/package"><directory>vendor/package/tests</directory></testsuite>` to the root `phpunit.xml`. Without this step, `vendor/bin/phpunit` won't pick up the layer's tests.

### Running tests

```bash
vendor/bin/phpunit                                 # all suites
vendor/bin/phpunit --testsuite=functional/orders   # one layer
vendor/bin/phpunit --filter=PlaceOrderTest         # one test class
```

The default Laravel `php artisan test` works too — it delegates to `phpunit` and reads the same config.

### Database in tests

Layer migrations are loaded by each layer's service provider (in `runningInConsole()` mode, which includes test runs). `use RefreshDatabase;` in a test runs every layer's migrations, in filename-timestamp order, across the entire app. There is no per-layer database isolation.

### Test namespace convention

Test classes must live under the layer's PSR-4 root for the layer's `composer.json` to autoload them — generators do this automatically:

- Feature: `Functional\Orders\Tests\Feature\…` in `tests/Feature/`
- Unit: `Functional\Orders\Tests\Unit\…` in `tests/Unit/`

If you write a test by hand, match the layer's namespace exactly — putting a test class in `Tests\Feature\` (the default Laravel namespace) instead of `Functional\Orders\Tests\Feature\` will silently break autoloading.

## Quick decision table

| Goal | Command |
|---|---|
| New Laravel project → OSDD layout | `osdd:start` (once, on a fresh app) |
| New domain | `osdd:layer vendor/package` |
| New model in a domain | `osdd:model Name --layer=vendor/package` |
| Migration | `osdd:migration name --layer=vendor/package` |
| Controller / request / resource | `osdd:controller / osdd:request / osdd:resource --layer=…` |
| Seeder (must be wired in the layer's provider!) | `osdd:seeder Name --layer=…` then `loadSeeders([Name::class])` |
| Run all layer seeders | `osdd:seed [--fresh / --refresh]` |
| Layer-scoped config file | `osdd:config name --layer=…` (auto-injects override) |
| Register layer test suite in phpunit.xml | `osdd:phpunit` |
| Arbitrary class | `osdd:class Path/To/Class --layer=…` |

## Pre-flight checklist before generating code

Before running any `osdd:*` make command, confirm:

1. **The layer exists.** `ls functional/{layer}/composer.json` (or `technical/`). If not, run `osdd:layer vendor/package` first.
2. **The right layer.** Re-read the request: does the concept belong in an existing layer, or is it a new domain? When in doubt, ask before creating a new layer.
3. **`--layer` is passed.** Skipping it triggers an interactive prompt that hangs non-interactive sessions. Always pass `--layer=vendor/package` when calling artisan programmatically.
4. **No file collisions.** OSDD make commands forward to Laravel's base generators — they refuse to overwrite without `--force`. Use `--force` only when you have explicitly chosen to overwrite.

## Common pitfalls

- **`make:*` instead of `osdd:*`**: the file lands in `app/` (which doesn't exist) or in a stray directory and won't be autoloaded. Always prefix with `osdd:` and pass `--layer`.
- **Omitting `--layer` in non-interactive contexts**: hangs on `Laravel\Prompts\search`. Always pass `--layer=vendor/package` explicitly when scripting.
- **Forgetting `"type": "layer"` in a hand-written `composer.json`**: the layer is invisible to `LayersCollection`, so its migrations don't load, its tests aren't synced, and its seeders aren't registered. Symptom: `osdd:seed` says "No OSDD seeders registered" even though the file exists.
- **Extending `Illuminate\Support\ServiceProvider` in a layer**: works, but loses `loadSeeders()` and `overrideConfigFrom()`. Always extend `Xefi\LaravelOSDD\LayerServiceProvider`.
- **Creating a seeder file but not calling `loadSeeders()`**: the seeder won't run from `osdd:seed`. Add it to the layer provider's `boot()`.
- **Adding a route file without `loadRoutesFrom()`**: the route silently doesn't exist at runtime — `php artisan route:list` won't show it. Wire it in the layer provider.
- **Registering a policy via `AuthServiceProvider`** (which doesn't exist in an OSDD app): use `Gate::policy()` in the owning layer's `LayerServiceProvider::boot()` instead.
- **Putting a test class under the default `Tests\` namespace**: the layer's PSR-4 won't autoload it. Always use `…\Tests\Feature` or `…\Tests\Unit` matching the layer namespace.
- **Editing the package's `config/osdd.php` directly** instead of overriding it from `technical/osdd`: changes get lost on `composer update`. Override via the technical OSDD layer.
- **Re-adding `App\\` to the root `composer.json` `autoload.psr-4`**: re-introduces the dead `app/` directory mapping. `osdd:start` removes it deliberately.
- **Running `osdd:start` on a non-fresh project**: it deletes `app/`, `database/`, and `config/`. Only ever run it once, on a brand-new Laravel app.
- **Forgetting to run `osdd:phpunit`** after creating a layer: new tests are invisible to `vendor/bin/phpunit` until the suite is added.
- **Composer not seeing a new layer**: after `osdd:layer`, run `composer update vendor/package` (or accept the prompt). Without it, the layer's classes aren't installed in `vendor/composer/autoload_psr4.php`.

## Quick reference: what NEVER goes where

| If you're about to create… | …NEVER put it in… | …instead use… |
|---|---|---|
| A model | `app/Models/` | `osdd:model Name --layer=…` |
| A migration | `database/migrations/` (root) | `osdd:migration … --layer=…` |
| A controller | `app/Http/Controllers/` | `osdd:controller … --layer=…` |
| A seeder | `database/seeders/` (root) | `osdd:seeder … --layer=…` + `loadSeeders()` |
| A factory | `database/factories/` (root) | `osdd:factory … --layer=…` |
| A config file | `config/` (root) | `osdd:config name --layer=…` |
| A service provider | `app/Providers/` + `bootstrap/providers.php` | `osdd:layer` (auto-creates one) or extend `LayerServiceProvider` inside a layer |
| A route | `routes/web.php` (root, for layer-owned routes) | `{layer}/routes/web.php` + `loadRoutesFrom` in the provider |
| A view | `resources/views/` (root) | `osdd:view name --layer=…` or `{layer}/resources/views/` + `loadViewsFrom` |
| A policy | `app/Policies/` | `osdd:policy … --layer=…` + `Gate::policy()` in the provider |
