<?php

namespace Jaydeep\UpgradeAssistant\Analyzers;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

abstract class BaseAnalyzer
{
    /** @var string */
    protected $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
    }

    /**
     * Analyze the project and mark detected BreakingChange items.
     *
     * @param  BreakingChange[] $changes
     * @return BreakingChange[]
     */
    abstract public function analyze(array $changes): array;

    /**
     * Recursively collect files with the given extension under $directory.
     *
     * @return string[]
     */
    protected function findFilesRecursively(string $directory, string $extension = 'php'): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === $extension) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Return a path relative to the project root, normalised to forward slashes.
     */
    protected function relativePath(string $absolutePath): string
    {
        $normalAbsolute = str_replace('\\', '/', $absolutePath);
        $normalBase     = str_replace('\\', '/', $this->basePath) . '/';

        if (strpos($normalAbsolute, $normalBase) === 0) {
            return substr($normalAbsolute, strlen($normalBase));
        }
        return $normalAbsolute;
    }
}
