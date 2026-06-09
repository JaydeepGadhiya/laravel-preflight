<?php

namespace Jaydeep\UpgradeAssistant\Analyzers;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

class EnvAnalyzer extends BaseAnalyzer
{
    /** @var string[] */
    private $envKeys = [];

    public function __construct(string $basePath)
    {
        parent::__construct($basePath);
        $this->loadEnvKeys();
    }

    public function analyze(array $changes): array
    {
        foreach ($changes as $change) {
            if ($change->envKey === null) {
                continue;
            }
            if (!in_array($change->envKey, $this->envKeys, true)) {
                $change->detected    = true;
                $change->locations[] = '.env.example (key "' . $change->envKey . '" missing)';
            }
        }

        return $changes;
    }

    private function loadEnvKeys(): void
    {
        // Prefer .env.example; fall back to .env
        $candidates = [
            $this->basePath . '/.env.example',
            $this->basePath . '/.env',
        ];

        $envFile = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $envFile = $candidate;
                break;
            }
        }

        if ($envFile === null) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }
            if (strpos($line, '=') !== false) {
                $key = trim(explode('=', $line, 2)[0]);
                if ($key !== '') {
                    $this->envKeys[] = $key;
                }
            }
        }
    }
}
