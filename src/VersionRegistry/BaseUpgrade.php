<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

abstract class BaseUpgrade
{
    /**
     * Return the list of breaking changes introduced in this version.
     *
     * @return BreakingChange[]
     */
    abstract public function getBreakingChanges(): array;

    protected function change(
        string $title,
        string $description,
        string $severity,
        string $category,
        string $fix,
        ?string $searchPattern = null,
        ?string $composerPackage = null,
        ?string $configKey = null,
        ?string $envKey = null
    ): BreakingChange {
        return new BreakingChange(
            $title,
            $description,
            $severity,
            $category,
            $fix,
            $searchPattern,
            $composerPackage,
            $configKey,
            $envKey
        );
    }
}
