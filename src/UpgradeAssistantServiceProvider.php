<?php

namespace Jaydeep\UpgradeAssistant;

use Illuminate\Support\ServiceProvider;
use Jaydeep\UpgradeAssistant\Commands\UpgradeCheckCommand;
use Jaydeep\UpgradeAssistant\VersionRegistry\VersionRegistry;

class UpgradeAssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VersionRegistry::class, function () {
            return new VersionRegistry();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpgradeCheckCommand::class,
            ]);
        }
    }
}
