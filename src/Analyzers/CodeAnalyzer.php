<?php

namespace Jaydeep\UpgradeAssistant\Analyzers;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

class CodeAnalyzer extends BaseAnalyzer
{
    /** @var string[] */
    private $scanDirs;

    /**
     * @param string[] $scanDirs Directories relative to basePath to scan
     */
    public function __construct(string $basePath, array $scanDirs = ['app', 'routes', 'database', 'resources', 'config', 'tests'])
    {
        parent::__construct($basePath);
        $this->scanDirs = $scanDirs;
    }

    public function analyze(array $changes): array
    {
        $files = $this->collectFiles();

        foreach ($changes as $change) {
            if ($change->searchPattern === null) {
                continue;
            }

            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content === false) {
                    continue;
                }
                if (@preg_match($change->searchPattern, $content) === 1) {
                    $change->detected    = true;
                    $relative            = $this->relativePath($file);
                    if (!in_array($relative, $change->locations, true)) {
                        $change->locations[] = $relative;
                    }
                }
            }
        }

        return $changes;
    }

    /** @return string[] */
    private function collectFiles(): array
    {
        $files = [];
        foreach ($this->scanDirs as $dir) {
            $fullPath = $this->basePath . '/' . $dir;
            $found    = $this->findFilesRecursively($fullPath, 'php');
            $files    = array_merge($files, $found);
        }
        return array_unique($files);
    }
}
