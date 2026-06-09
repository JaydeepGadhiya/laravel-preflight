<?php

namespace Jaydeep\UpgradeAssistant\Analyzers;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

class ConfigAnalyzer extends BaseAnalyzer
{
    public function analyze(array $changes): array
    {
        foreach ($changes as $change) {
            if ($change->configKey === null) {
                continue;
            }

            // configKey format: "filename.key" or just "filename"
            $parts      = explode('.', $change->configKey, 2);
            $fileName   = $parts[0];
            $keyName    = isset($parts[1]) ? $parts[1] : null;
            $configFile = $this->basePath . '/config/' . $fileName . '.php';

            if (!file_exists($configFile)) {
                $change->detected    = true;
                $change->locations[] = 'config/' . $fileName . '.php (file missing)';
                continue;
            }

            if ($keyName !== null) {
                $content = @file_get_contents($configFile);
                if ($content === false) {
                    continue;
                }
                $hasKey = strpos($content, "'" . $keyName . "'") !== false
                       || strpos($content, '"' . $keyName . '"') !== false;

                if (!$hasKey) {
                    $change->detected    = true;
                    $change->locations[] = 'config/' . $fileName . '.php (key "' . $keyName . '" missing)';
                }
            }
        }

        return $changes;
    }
}
