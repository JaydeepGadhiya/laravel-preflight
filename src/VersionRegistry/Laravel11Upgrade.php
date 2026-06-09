<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

/**
 * Breaking changes when upgrading FROM Laravel 10 TO Laravel 11.
 * Reference: https://laravel.com/docs/11.x/upgrade
 */
class Laravel11Upgrade extends BaseUpgrade
{
    public function getBreakingChanges(): array
    {
        return [
            // --- PHP ---
            $this->change(
                'PHP 8.2 minimum required',
                'Laravel 11 requires PHP 8.2 or higher.',
                'critical',
                'php',
                'Update your server and composer.json PHP constraint to ^8.2.'
            ),

            // --- Skeleton / Architecture ---
            $this->change(
                'app/Http/Kernel.php replaced by bootstrap/app.php',
                'Laravel 11 uses a slim application skeleton. app/Http/Kernel.php no longer exists. Middleware is registered in bootstrap/app.php.',
                'critical',
                'middleware',
                'Migrate middleware registrations from app/Http/Kernel.php to bootstrap/app.php using ->withMiddleware().',
                '/class\s+Kernel\s+extends\s+HttpKernel/'
            ),

            $this->change(
                'app/Console/Kernel.php removed',
                'Console Kernel is gone. Scheduled tasks are now registered in routes/console.php or via Artisan::command() in bootstrap/app.php.',
                'critical',
                'code',
                'Move scheduled tasks from app/Console/Kernel.php schedule() method to routes/console.php.',
                '/class\s+Kernel\s+extends\s+ConsoleKernel/'
            ),

            $this->change(
                'app/Exceptions/Handler.php removed',
                'The Exception Handler is gone. Exception handling is now done in bootstrap/app.php via ->withExceptions().',
                'critical',
                'code',
                'Migrate custom exception rendering/reporting from app/Exceptions/Handler.php to bootstrap/app.php ->withExceptions().',
                '/class\s+Handler\s+extends\s+ExceptionHandler/'
            ),

            $this->change(
                'Service providers consolidated',
                'AuthServiceProvider, BroadcastServiceProvider, EventServiceProvider, RouteServiceProvider are no longer in the skeleton. Their functionality moves to AppServiceProvider or bootstrap/app.php.',
                'warning',
                'code',
                'Merge custom logic from AuthServiceProvider, EventServiceProvider, etc. into AppServiceProvider.',
                '/class\s+(AuthServiceProvider|BroadcastServiceProvider|EventServiceProvider|RouteServiceProvider)\s+extends/'
            ),

            $this->change(
                'Middleware moved out of app/Http/Middleware/',
                'Many first-party middleware classes were moved into the framework. Remove them from app/Http/Middleware/ if they are unmodified copies.',
                'warning',
                'middleware',
                'Delete unmodified middleware files that are now owned by the framework (e.g. TrimStrings, EncryptCookies). Keep only customised ones.',
                '/class\s+(TrimStrings|EncryptCookies|PreventRequestsDuringMaintenance|TrustHosts|TrustProxies|RedirectIfAuthenticated)\s+extends/'
            ),

            // --- Code ---
            $this->change(
                'Carbon 3.x upgrade',
                'Laravel 11 ships with Carbon 3.x. Some Carbon method signatures and behaviors changed.',
                'warning',
                'code',
                'Review Carbon usage. Key change: diffInDays() and similar methods now return floats by default. Use (int) cast if needed.',
                '/Carbon::|->diffIn(Days|Hours|Minutes|Seconds)\s*\(/'
            ),

            $this->change(
                'Model::unguard() scope changed',
                'Calling Model::unguard() in tests now only applies for the duration of the test. Check global unguard usage.',
                'info',
                'code',
                'Replace persistent Model::unguard() calls with $model->forceFill() or per-test unguard.',
                '/Model::unguard\s*\(/'
            ),

            $this->change(
                'Eloquent Model withoutTimestamps() added — old workarounds no longer needed',
                'Previous timestamp-disable patterns ($model->timestamps = false) still work but withoutTimestamps() is now available.',
                'info',
                'code',
                'Consider replacing manual timestamp disabling with $model->withoutTimestamps(fn() => ...).',
                '/\$[a-zA-Z]+->timestamps\s*=\s*false/'
            ),

            $this->change(
                'config() helper returns null for missing keys by default',
                'In L11, config() without a default will return null (not throw). Ensure you do not rely on exceptions for missing config keys.',
                'info',
                'code',
                'Audit config() calls that depend on exceptions for missing keys. Provide explicit defaults.',
                '/config\s*\(\s*[\'"][^"\']+[\'"]\s*\)/'
            ),

            // --- Sanctum ---
            $this->change(
                'Sanctum 4.x — statefulApi() changed',
                'Laravel Sanctum 4 (bundled with L11) changed the stateful domains API configuration.',
                'warning',
                'code',
                'Review config/sanctum.php stateful domains and update to Sanctum 4 format.',
                null, null,
                'sanctum.stateful'
            ),

            // --- Routes ---
            $this->change(
                'routes/api.php is no longer auto-loaded',
                'Laravel 11 does not auto-register routes/api.php. API routes (including those using auth:sanctum) will silently stop working unless you opt in via bootstrap/app.php.',
                'critical',
                'routes',
                'Add ->withRouting(api: __DIR__.\'/../routes/api.php\') inside the withRouting() call in bootstrap/app.php, or run: php artisan install:api',
                '/\bauth:sanctum\b/'
            ),

            $this->change(
                'routes/channels.php is no longer auto-loaded',
                'Laravel 11 does not auto-register routes/channels.php. Broadcast channel definitions will silently stop working unless explicitly loaded.',
                'warning',
                'routes',
                'Add channels: __DIR__.\'/../routes/channels.php\' to the withRouting() call in bootstrap/app.php.',
                '/Broadcast::channel\s*\(/'
            ),

            // --- Env ---
            $this->change(
                'APP_MAINTENANCE_DRIVER env variable added',
                'Laravel 11 introduces a maintenance mode driver. Add APP_MAINTENANCE_DRIVER to your .env.',
                'info',
                'env',
                'Add APP_MAINTENANCE_DRIVER=file to your .env and .env.example.',
                null, null, null,
                'APP_MAINTENANCE_DRIVER'
            ),

            $this->change(
                'BCRYPT_ROUNDS env variable replaces config/hashing.php rounds',
                'Hashing rounds are now controlled via BCRYPT_ROUNDS env variable.',
                'info',
                'env',
                'Add BCRYPT_ROUNDS=12 to your .env and .env.example.',
                null, null, null,
                'BCRYPT_ROUNDS'
            ),

            $this->change(
                'LOG_DEPRECATIONS_CHANNEL env variable removed from default skeleton',
                'The LOG_DEPRECATIONS_CHANNEL variable is no longer in the default .env.example in L11.',
                'info',
                'env',
                'Remove LOG_DEPRECATIONS_CHANNEL from .env if unused, or keep it if you rely on it.',
                null, null, null,
                'LOG_DEPRECATIONS_CHANNEL'
            ),
        ];
    }
}
