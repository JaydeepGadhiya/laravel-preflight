<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

/**
 * Breaking changes when upgrading FROM Laravel 12 TO Laravel 13.
 * Source: https://laravel.com/docs/13.x/upgrade
 */
class Laravel13Upgrade extends BaseUpgrade
{
    public function getBreakingChanges(): array
    {
        return [

            // =========================================================
            // HIGH IMPACT
            // =========================================================

            $this->change(
                'Dependency versions bumped',
                'composer.json must be updated: laravel/framework ^13.0, laravel/tinker ^3.0, phpunit/phpunit ^12.0, pestphp/pest ^4.0 (if used).',
                'critical',
                'composer',
                'Update composer.json: "laravel/framework": "^13.0", "laravel/tinker": "^3.0", "phpunit/phpunit": "^12.0".',
                null,
                'laravel/framework'
            ),

            $this->change(
                'VerifyCsrfToken renamed to PreventRequestForgery',
                'The CSRF middleware class was renamed from VerifyCsrfToken to PreventRequestForgery and now includes request-origin verification via the Sec-Fetch-Site header. VerifyCsrfToken remains as a deprecated alias.',
                'critical',
                'middleware',
                'Replace all references to VerifyCsrfToken::class with Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, especially in ->withoutMiddleware() test calls.',
                '/VerifyCsrfToken/'
            ),

            // =========================================================
            // MEDIUM IMPACT
            // =========================================================

            $this->change(
                'Cache serializable_classes option defaults to false',
                'The default cache config now includes serializable_classes => false. If your application stores PHP objects in cache, unserialization will fail unless classes are explicitly allow-listed.',
                'warning',
                'config',
                'Add a serializable_classes array to config/cache.php listing every class your app serializes into cache. Set to true only if you accept the security risk.',
                '/cache\(\)|Cache::put\s*\(|Cache::remember\s*\(/',
                null,
                'cache.serializable_classes'
            ),

            $this->change(
                'DB::upsert() requires non-empty uniqueBy for MySQL/MariaDB',
                'Laravel now throws an InvalidArgumentException if uniqueBy is empty when calling upsert() on MySQL or MariaDB. Previously this generated silent invalid SQL.',
                'warning',
                'code',
                'Ensure all upsert() calls pass a non-empty uniqueBy argument.',
                '/->upsert\s*\(/'
            ),

            // =========================================================
            // LOW IMPACT
            // =========================================================

            $this->change(
                'Cache and session key prefix format changed (hyphen instead of underscore)',
                'Default cache/Redis key prefixes and session cookie names now use hyphens: "laravel-cache-" instead of "laravel_cache_". Affects apps that rely on framework-generated defaults rather than explicit config values.',
                'warning',
                'env',
                'Add CACHE_PREFIX, REDIS_PREFIX, and SESSION_COOKIE to your .env to preserve the old names, or flush/rename existing cache keys after upgrading.',
                null, null, null,
                'CACHE_PREFIX'
            ),

            $this->change(
                'Container::call() respects nullable class defaults (returns null instead of resolving)',
                'Container::call() with a nullable typed parameter that has a null default now returns null when no binding exists, instead of resolving a class instance.',
                'warning',
                'code',
                'Review closures/methods injected via Container::call() that use nullable typed parameters with null defaults. Add explicit bindings if you relied on the old resolution behavior.',
                '/\?\s*[A-Z][a-zA-Z]+\s+\$[a-zA-Z]+\s*=\s*null/'
            ),

            $this->change(
                'Model booting disallows nested instantiation',
                'Creating a new model instance while that model is still booting now throws a LogicException.',
                'warning',
                'code',
                'Move any model instantiation out of boot() and boot*() trait methods.',
                '/static\s+function\s+boot\s*\(\s*\)/'
            ),

            $this->change(
                'Polymorphic pivot table names are now pluralized',
                'When table names are inferred for polymorphic pivot models using custom pivot classes, Laravel now generates pluralized names.',
                'warning',
                'code',
                'Explicitly define the $table property on custom polymorphic pivot models to lock in the old singular name.',
                '/MorphPivot|morphedByMany\s*\(/'
            ),

            $this->change(
                'Collection model serialization now restores eager-loaded relations',
                'When Eloquent model collections are serialized (e.g. in queued jobs), eager-loaded relations are now restored on deserialization.',
                'info',
                'code',
                'Review queued jobs that deserialize model collections and depend on relations NOT being present after deserialization.',
                '/implements\s+ShouldQueue|SerializesModels/'
            ),

            $this->change(
                'Domain routes now take precedence over non-domain routes',
                'Routes with an explicit domain are now matched before non-domain routes regardless of registration order.',
                'info',
                'routes',
                'Review routes/web.php and routes/api.php for domain-vs-non-domain route conflicts. Test routing in affected environments.',
                '/Route::domain\s*\(/'
            ),

            $this->change(
                'JobAttempted event: $exceptionOccurred replaced by $exception',
                'The JobAttempted queue event property $exceptionOccurred (bool) was replaced with $exception (Throwable|null).',
                'warning',
                'code',
                'Update listeners for Illuminate\Queue\Events\JobAttempted: replace $event->exceptionOccurred with $event->exception !== null.',
                '/exceptionOccurred/'
            ),

            $this->change(
                'QueueBusy event: $connection renamed to $connectionName',
                'The QueueBusy event property $connection was renamed to $connectionName for consistency.',
                'warning',
                'code',
                'Update any listeners for Illuminate\Queue\Events\QueueBusy: rename $event->connection to $event->connectionName.',
                '/QueueBusy/'
            ),

            $this->change(
                'Manager::extend() closures now bound to the manager instance',
                'Closures passed to manager extend() methods are now bound to the manager. Code relying on a service provider or other object as $this will break.',
                'warning',
                'code',
                'Capture dependencies via use (...) in the closure instead of relying on $this inside extend() callbacks.',
                '/->extend\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*function/'
            ),

            $this->change(
                'MySQL DELETE with JOIN + ORDER BY/LIMIT now produces valid SQL',
                'Laravel now compiles full DELETE...JOIN queries including ORDER BY and LIMIT for MySQL. Previously these clauses were silently ignored.',
                'info',
                'code',
                'Test any MySQL DELETE queries that use joins with ORDER BY or LIMIT — behavior may change or throw a QueryException on incompatible MySQL variants.',
                '/->join\s*\(.*->delete\s*\(\)|->delete\s*\(.*->join\s*\(/'
            ),

            $this->change(
                'Pagination Bootstrap 3 view names changed',
                'Pagination Bootstrap 3 view names changed: "pagination::default" → "pagination::bootstrap-3", "pagination::simple-default" → "pagination::simple-bootstrap-3".',
                'warning',
                'code',
                'Update any direct references to "pagination::default" or "pagination::simple-default" view names.',
                '/pagination::default|pagination::simple-default/'
            ),

            $this->change(
                'Str factories (UUID/ULID/random) reset between tests',
                'Laravel now resets custom Str factories (createUuidsUsing, createUlidsUsing, etc.) during test teardown.',
                'info',
                'code',
                'Move custom Str factory setup into each test or setUp() method instead of relying on them persisting across tests.',
                '/createUuidsUsing\s*\(|createUlidsUsing\s*\(|createRandomStringsUsing\s*\(/'
            ),

            $this->change(
                'Js::from() uses JSON_UNESCAPED_UNICODE by default',
                'Illuminate\Support\Js::from() now outputs unescaped Unicode characters (e.g. è instead of \u00e8).',
                'info',
                'code',
                'Update test assertions or frontend output comparisons that expected escaped Unicode sequences from Js::from().',
                '/Js::from\s*\(/'
            ),

            $this->change(
                'symfony/polyfill-php85 — array_first() / array_last() global function conflicts',
                'Laravel 13 requires symfony/polyfill-php85 which defines global array_first() and array_last() on PHP < 8.5. These conflict with laravel/helpers or custom globals of the same name (different signature).',
                'warning',
                'code',
                'Replace array_first($array, $callback) helper calls with Arr::first($array, $callback). Remove conflicting global helpers.',
                '/array_first\s*\(|array_last\s*\(/'
            ),

            $this->change(
                'Default password reset notification subject changed',
                'The default password reset email subject changed from "Reset Password Notification" to "Reset your password".',
                'info',
                'code',
                'Update any tests or translation overrides that assert the old subject string "Reset Password Notification".',
                '/Reset Password Notification/'
            ),

            $this->change(
                'Custom Cache Store contract must implement touch()',
                'The Illuminate\Contracts\Cache\Store contract now requires a touch($key, $seconds) method.',
                'warning',
                'code',
                'Add a touch() method to any custom cache store implementations.',
                '/implements\s+.*Store/'
            ),
        ];
    }
}
