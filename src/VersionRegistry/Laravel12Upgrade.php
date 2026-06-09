<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

/**
 * Breaking changes when upgrading FROM Laravel 11 TO Laravel 12.
 * Reference: https://laravel.com/docs/12.x/upgrade
 */
class Laravel12Upgrade extends BaseUpgrade
{
    public function getBreakingChanges(): array
    {
        return [
            // --- PHP ---
            $this->change(
                'PHP 8.2 minimum required',
                'Laravel 12 continues to require PHP 8.2 or higher (same as L11). PHP 8.1 is not supported.',
                'critical',
                'php',
                'Ensure your server and composer.json PHP constraint is ^8.2.'
            ),

            // --- Composer packages ---
            $this->change(
                'doctrine/dbal no longer pulled in automatically',
                'Laravel 12 dropped doctrine/dbal as an implicit dependency. Schema operations now use native column type inspection. If your code directly depends on doctrine/dbal, you must add it explicitly.',
                'warning',
                'composer',
                'Run: composer require doctrine/dbal if you use it directly. Otherwise remove any workarounds that were added for doctrine/dbal compatibility.',
                '/doctrine\\\\dbal|Doctrine\\\\DBAL/',
                'doctrine/dbal'
            ),

            // --- Code: removed deprecated methods ---
            $this->change(
                'Model::reguard() static method removed',
                'The static Model::reguard() method deprecated in L11 has been removed in L12.',
                'critical',
                'code',
                'Remove Model::reguard() calls. Mass assignment protection is on by default — no explicit reguard needed.',
                '/Model::reguard\s*\(/'
            ),

            $this->change(
                'Http Response::json() throws on invalid JSON',
                'Illuminate\Http\Client\Response::json() now throws a JsonException when the response body is not valid JSON, instead of returning null.',
                'warning',
                'code',
                'Wrap ->json() calls in try/catch for JsonException, or use ->json() only after checking ->successful() and Content-Type.',
                '/->json\s*\(\s*\)/'
            ),

            $this->change(
                'assertJsonPath() strict type comparison',
                'assertJsonPath() in tests now uses strict comparison (===) by default. Loose string/int mismatches will fail.',
                'warning',
                'code',
                'Review test assertions using assertJsonPath() and ensure expected values match the exact type returned by the API.',
                '/->assertJsonPath\s*\(/'
            ),

            $this->change(
                'Collection::groupBy() preserves original keys',
                'Collection::groupBy() now preserves original item keys within each group. Code relying on re-indexed groups (0, 1, 2...) will break.',
                'warning',
                'code',
                'Add ->values() after ->groupBy() where you need zero-indexed groups.',
                '/->groupBy\s*\(/'
            ),

            $this->change(
                'whereRelation() / orWhereRelation() signature change',
                'The whereRelation() method now accepts a Closure as the second argument (matching whereHas() style), removing the old positional operator argument.',
                'warning',
                'code',
                'Update whereRelation() calls: replace whereRelation(\'rel\', \'col\', \'val\') with whereRelation(\'rel\', fn($q) => $q->where(\'col\', \'val\')).',
                '/->whereRelation\s*\(|->orWhereRelation\s*\(/'
            ),

            $this->change(
                'Str::password() removed',
                'The Str::password() helper deprecated in L11 is removed in L12.',
                'critical',
                'code',
                'Replace Str::password() with Str::random($length) or a dedicated password generation library.',
                '/Str::password\s*\(/'
            ),

            $this->change(
                'schedule()->withoutOverlapping() default cache store changed',
                'Scheduled task overlap locking now uses the default cache store instead of the file store. Behaviour changes if your default cache is not file-based.',
                'info',
                'code',
                'Explicitly call ->withoutOverlapping()->onOneServer() if you need consistent locking across workers.',
                '/->withoutOverlapping\s*\(/'
            ),

            $this->change(
                'Storage::fake() returns a new FakeDisk instance',
                'Storage::fake() now returns a Illuminate\Filesystem\FakeDisk instance. Code type-hinting the old return type will fail.',
                'info',
                'code',
                'Update any type hints on Storage::fake() return values to FakeDisk or remove the type hint.',
                '/Storage::fake\s*\(/'
            ),

            $this->change(
                'Broadcasting channel model binding stricter',
                'Broadcasting channel route model binding now enforces that the bound model matches the authenticated user\'s gate policy. Implicit allows are removed.',
                'warning',
                'code',
                'Define explicit channel authorization policies in routes/channels.php for all private/presence channels.',
                '/Broadcast::channel\s*\(/'
            ),

            $this->change(
                'spatie/laravel-ignition must be upgraded to ^2.0',
                'Laravel 12 requires spatie/laravel-ignition ^2.0. The ^1.x release is incompatible with the updated framework internals.',
                'warning',
                'composer',
                'Run: composer require spatie/laravel-ignition:^2.0 --dev',
                null,
                'spatie/laravel-ignition'
            ),

            // --- Config ---
            $this->change(
                'config/database.php "options" key required for SQLite WAL',
                'SQLite WAL mode configuration now uses a dedicated "options" key in the connection config.',
                'info',
                'config',
                'Add an "options" array to your SQLite connection in config/database.php if you use WAL mode.',
                null, null,
                'database.connections'
            ),

            // --- Env ---
            $this->change(
                'APP_LOCALE and APP_FALLBACK_LOCALE replace config/app.php defaults',
                'Laravel 12 resolves locale from APP_LOCALE and APP_FALLBACK_LOCALE env variables first. Add them to .env for consistent behaviour.',
                'info',
                'env',
                'Add APP_LOCALE=en and APP_FALLBACK_LOCALE=en to your .env and .env.example.',
                null, null, null,
                'APP_LOCALE'
            ),

            $this->change(
                'APP_FAKER_LOCALE replaces faker_locale in config/app.php',
                'The faker_locale setting moved to an APP_FAKER_LOCALE environment variable in L12.',
                'info',
                'env',
                'Add APP_FAKER_LOCALE=en_US to your .env and .env.example.',
                null, null, null,
                'APP_FAKER_LOCALE'
            ),
        ];
    }
}
