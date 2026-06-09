<?php

namespace Jaydeep\UpgradeAssistant\Analyzers;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

class ComposerAnalyzer extends BaseAnalyzer
{
    /** @var array */
    private $composerData = [];

    public function __construct(string $basePath)
    {
        parent::__construct($basePath);

        $composerFile = $this->basePath . '/composer.json';
        if (file_exists($composerFile)) {
            $decoded = json_decode(file_get_contents($composerFile), true);
            $this->composerData = is_array($decoded) ? $decoded : [];
        }
    }

    public function analyze(array $changes): array
    {
        $installed = array_merge(
            array_keys($this->composerData['require'] ?? []),
            array_keys($this->composerData['require-dev'] ?? [])
        );

        foreach ($changes as $change) {
            if ($change->composerPackage === null) {
                continue;
            }
            if (in_array($change->composerPackage, $installed, true)) {
                $change->detected  = true;
                $change->locations = ['composer.json'];
            }
        }

        return $changes;
    }

    /**
     * Detect the current Laravel major version from composer.json.
     */
    public function getCurrentLaravelVersion(): ?string
    {
        $require = $this->composerData['require'] ?? [];
        if (isset($require['laravel/framework'])) {
            if (preg_match('/\^?(\d+)/', $require['laravel/framework'], $m)) {
                return $m[1];
            }
        }
        return null;
    }

    /**
     * Return the minimum PHP version declared in composer.json, or the running PHP version.
     */
    public function getCurrentPhpVersion(): string
    {
        $phpConstraint = $this->composerData['require']['php'] ?? null;
        if ($phpConstraint && preg_match('/(\d+\.\d+)/', $phpConstraint, $m)) {
            return $m[1];
        }
        return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }
}
