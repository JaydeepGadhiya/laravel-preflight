<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

/**
 * Breaking changes when upgrading FROM Laravel 9 TO Laravel 10.
 * Reference: https://laravel.com/docs/10.x/upgrade
 */
class Laravel10Upgrade extends BaseUpgrade
{
    public function getBreakingChanges(): array
    {
        return [
            // --- PHP ---
            $this->change(
                'PHP 8.1 minimum required',
                'Laravel 10 requires PHP 8.1 or higher. PHP 8.0 is no longer supported.',
                'critical',
                'php',
                'Update your server and composer.json PHP constraint to ^8.1.'
            ),

            // --- Code ---
            $this->change(
                'Bus::dispatchNow() removed',
                'Illuminate\Support\Facades\Bus::dispatchNow() was removed. Use dispatchSync() instead.',
                'critical',
                'code',
                'Replace all Bus::dispatchNow() calls with Bus::dispatchSync().',
                '/Bus::dispatchNow\s*\(|dispatchNow\s*\(/'
            ),

            $this->change(
                'assertDeleted() removed from tests',
                'assertDeleted() has been removed. Use assertModelMissing() instead.',
                'critical',
                'code',
                'Replace $this->assertDeleted($model) with $this->assertModelMissing($model) in tests.',
                '/->assertDeleted\s*\(/'
            ),

            $this->change(
                'assertSoftDeleted() signature changed',
                'assertSoftDeleted() now requires the model class or table name as first argument.',
                'warning',
                'code',
                'Review assertSoftDeleted() calls and ensure arguments match the new signature.',
                '/->assertSoftDeleted\s*\(/'
            ),

            $this->change(
                'Model $dates property fully removed',
                'The $dates Eloquent property was deprecated in L9 and is fully removed in L10.',
                'critical',
                'code',
                'Move all date fields to $casts: `\'field\' => \'datetime\'`.',
                '/protected\s+\$dates\s*=/'
            ),

            $this->change(
                'Predis 1.x dropped — must use Predis 2.x',
                'Support for predis/predis v1 was dropped. Upgrade to v2 if you use Predis.',
                'warning',
                'composer',
                'Run: composer require predis/predis:^2.0',
                null,
                'predis/predis'
            ),

            $this->change(
                'Return types added to core framework classes',
                'Many Illuminate framework classes added native PHP return types. Custom subclasses must add matching return types.',
                'warning',
                'code',
                'Review any classes that extend Illuminate core classes and add missing return type declarations.',
                '/extends\s+(Controller|Model|Middleware|FormRequest|Notification|Mailable|Job|Event|Listener)/'
            ),

            $this->change(
                'Rule::in() and Rule::notIn() return Illuminate\Validation\Rules\In',
                'These methods no longer return a string. If you pass them to string functions, wrap with (string).',
                'info',
                'code',
                'Review code that treats Rule::in() return value as a string.',
                '/Rule::(in|notIn)\s*\(/'
            ),

            $this->change(
                'Closure-based exception reporting changed',
                'The $dontReport property and reportable() closures have stricter handling in L10.',
                'info',
                'code',
                'Review app/Exceptions/Handler.php for custom reportable() or $dontReport usage.',
                '/\$dontReport|->reportable\s*\(/'
            ),

            $this->change(
                'Eloquent whereHas deep nesting performance change',
                'whereHas() with deeply nested relationships has changed query generation in L10.',
                'info',
                'code',
                'Review whereHas() usage with nested relations and test query output.',
                '/->whereHas\s*\(/'
            ),

            $this->change(
                'getQueueableRelations() return type declaration required',
                'In Laravel 10 (PHP 8.1), the Queueable trait\'s getQueueableRelations() method has a native array return type. Any subclass overriding this method without the return type will throw a fatal TypeError.',
                'warning',
                'code',
                'Add `: array` return type to every getQueueableRelations() override in your codebase.',
                '/function\s+getQueueableRelations\s*\(/'
            ),

            // --- Config ---
            $this->change(
                'config/hashing.php "bcrypt.rounds" default changed',
                'The default bcrypt rounds increased in Laravel 10. Existing passwords remain valid but new ones use more rounds.',
                'info',
                'config',
                'Optionally set BCRYPT_ROUNDS in .env to match your previous config/hashing.php value.',
                null, null,
                'hashing.bcrypt'
            ),
        ];
    }
}
