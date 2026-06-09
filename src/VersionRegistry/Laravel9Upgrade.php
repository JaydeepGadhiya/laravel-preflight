<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

/**
 * Breaking changes when upgrading FROM Laravel 8 TO Laravel 9.
 * Reference: https://laravel.com/docs/9.x/upgrade
 */
class Laravel9Upgrade extends BaseUpgrade
{
    public function getBreakingChanges(): array
    {
        return [
            // --- PHP ---
            $this->change(
                'PHP 8.0 minimum required',
                'Laravel 9 requires PHP 8.0 or higher. PHP 7.x is no longer supported.',
                'critical',
                'php',
                'Update your server and composer.json PHP constraint to ^8.0. Run: php -v to verify.'
            ),

            // --- Composer packages ---
            $this->change(
                'fruitcake/laravel-cors deprecated',
                'The fruitcake/laravel-cors package is superseded by Laravel\'s built-in CORS support.',
                'critical',
                'composer',
                'Remove fruitcake/laravel-cors. Use the built-in config/cors.php (already present in L9 skeleton).',
                null,
                'fruitcake/laravel-cors'
            ),

            $this->change(
                'facade/ignition replaced by spatie/laravel-ignition',
                'facade/ignition is not compatible with Laravel 9. Replace with spatie/laravel-ignition ^1.0.',
                'critical',
                'composer',
                'Run: composer remove facade/ignition && composer require spatie/laravel-ignition --dev',
                null,
                'facade/ignition'
            ),

            $this->change(
                'SwiftMailer removed — Symfony Mailer required',
                'Laravel 9 replaces SwiftMailer with Symfony Mailer. The swiftmailer/swiftmailer package is removed.',
                'critical',
                'composer',
                'Remove any direct swiftmailer usage. Update mail config transport values: "smtp", "sendmail", "mailgun", etc.',
                null,
                'swiftmailer/swiftmailer'
            ),

            // --- Code ---
            $this->change(
                'Model $dates property deprecated',
                'The $dates property on Eloquent models is deprecated. Use $casts instead.',
                'warning',
                'code',
                'Replace `protected $dates = [...]` with `protected $casts = [\'field\' => \'datetime\']`.',
                '/protected\s+\$dates\s*=/'
            ),

            $this->change(
                'castAsJson() method removed',
                'The Model::castAsJson() method was removed. Use the "json" cast in $casts instead.',
                'critical',
                'code',
                'Replace castAsJson() calls with $casts array entries using "json" or "array" cast.',
                '/castAsJson\s*\(/'
            ),

            $this->change(
                'Illuminate\Http\Testing\File fake() signature changed',
                'The fake() method signature for uploaded file testing changed in Laravel 9.',
                'warning',
                'code',
                'Review test files using UploadedFile::fake()->image() or fake()->create() for changed parameters.',
                '/UploadedFile::fake\(\)->(image|create)\s*\(/'
            ),

            $this->change(
                'Controller middleware() method via constructor deprecated',
                'Calling $this->middleware() in controller constructors is deprecated. Use attribute-based or route middleware.',
                'warning',
                'code',
                'Move middleware assignments to route definitions or use #[Middleware] attribute (PHP 8).',
                '/function\s+__construct[^}]+\$this->middleware\s*\(/'
            ),

            $this->change(
                'AuthenticatesUsers / RegistersUsers traits removed',
                'The AuthenticatesUsers and RegistersUsers traits were removed. Use Laravel Breeze or Jetstream instead.',
                'critical',
                'code',
                'Migrate auth scaffolding to Laravel Breeze (composer require laravel/breeze --dev).',
                '/AuthenticatesUsers|RegistersUsers|ResetsPasswords/'
            ),

            $this->change(
                'Flysystem 3.x upgrade — S3/FTP config changed',
                'Laravel 9 uses Flysystem 3.x. The S3 and FTP filesystem config keys changed.',
                'warning',
                'code',
                'Review config/filesystems.php S3 disk config. The "url" key changed and FTP config structure updated.',
                '/\'driver\'\s*=>\s*\'(s3|ftp|sftp)\'/'
            ),

            $this->change(
                'Str::of() and stringable methods return types changed',
                'Some Stringable methods now return string instead of Stringable. Check chained calls.',
                'info',
                'code',
                'Review code that chains methods on Str::of() and expects a Stringable return type.',
                '/Str::of\s*\(/'
            ),

            $this->change(
                'Route::home() removed',
                'The Route::home() method was removed in Laravel 9.',
                'critical',
                'code',
                'Replace Route::home() with a named route redirect: redirect()->route(\'home\').',
                '/Route::home\s*\(/'
            ),

            // --- Config ---
            $this->change(
                'config/session.php missing "same_site" key',
                'Laravel 9 requires the "same_site" key in config/session.php.',
                'warning',
                'config',
                'Add `\'same_site\' => \'lax\'` to your config/session.php.',
                null, null,
                'session.same_site'
            ),

            $this->change(
                'config/mail.php "transport" key required',
                'Laravel 9 Symfony Mailer uses a "transport" key instead of "driver" in mail config.',
                'critical',
                'config',
                'Update config/mail.php: rename "driver" to "transport" for each mailer definition.',
                null, null,
                'mail.mailers'
            ),

            // --- Code (additional) ---
            $this->change(
                'dispatch_now() global helper removed',
                'The dispatch_now() global helper function was deprecated in Laravel 8 and removed in Laravel 9. Use dispatchSync() instead.',
                'critical',
                'code',
                'Replace all dispatch_now($job) calls with dispatch($job)->dispatchSync() or Bus::dispatchSync($job).',
                '/\bdispatch_now\s*\(/'
            ),

            // --- Config (additional) ---
            $this->change(
                'config/mail.php missing "default" mailer key',
                'Laravel 9 requires a "default" key in config/mail.php pointing to the default mailer name (e.g. "smtp").',
                'critical',
                'config',
                'Add `\'default\' => env(\'MAIL_MAILER\', \'smtp\')` to the top-level of config/mail.php.',
                null, null,
                'mail.default'
            ),

            // --- Env ---
            $this->change(
                'FILESYSTEM_DISK env variable renamed',
                'The env variable FILESYSTEM_DRIVER was renamed to FILESYSTEM_DISK in Laravel 9.',
                'warning',
                'env',
                'Add FILESYSTEM_DISK to your .env and .env.example. Remove FILESYSTEM_DRIVER.',
                null, null, null,
                'FILESYSTEM_DISK'
            ),
        ];
    }
}
