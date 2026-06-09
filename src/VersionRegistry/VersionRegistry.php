<?php

namespace Jaydeep\UpgradeAssistant\VersionRegistry;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

class VersionRegistry
{
    /** @var array<string, string> version => upgrade class */
    private $upgrades = [
        '9'  => Laravel9Upgrade::class,
        '10' => Laravel10Upgrade::class,
        '11' => Laravel11Upgrade::class,
        '12' => Laravel12Upgrade::class,
        '13' => Laravel13Upgrade::class,
    ];

    /** @var string */
    private $latestVersion = '13';

    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }

    /** @return string[] */
    public function getSupportedVersions(): array
    {
        return array_keys($this->upgrades);
    }

    /**
     * Collect all breaking changes for every version between $fromVersion (exclusive)
     * and $toVersion (inclusive).
     *
     * @return BreakingChange[]
     */
    public function getBreakingChanges(string $fromVersion, string $toVersion): array
    {
        $changes = [];

        foreach ($this->upgrades as $version => $class) {
            if ((int) $version > (int) $fromVersion && (int) $version <= (int) $toVersion) {
                /** @var BaseUpgrade $upgrade */
                $upgrade = new $class();
                $changes = array_merge($changes, $upgrade->getBreakingChanges());
            }
        }

        return $changes;
    }
}
