<?php

namespace Jaydeep\UpgradeAssistant\Data;

class BreakingChange
{
    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var string critical|warning|info */
    public $severity;

    /** @var string php|composer|code|config|env|middleware|routes */
    public $category;

    /** @var string */
    public $fix;

    /** @var string|null Regex pattern to search in PHP/Blade files */
    public $searchPattern;

    /** @var string|null Composer package name to look for in composer.json */
    public $composerPackage;

    /** @var string|null Config file.key to check existence (e.g. "session.same_site") */
    public $configKey;

    /** @var string|null .env key that must be present */
    public $envKey;

    /** @var bool Whether this issue was detected in the codebase */
    public $detected = false;

    /** @var string[] File paths where the issue was found */
    public $locations = [];

    public function __construct(
        string $title,
        string $description,
        string $severity,
        string $category,
        string $fix,
        ?string $searchPattern = null,
        ?string $composerPackage = null,
        ?string $configKey = null,
        ?string $envKey = null
    ) {
        $this->title          = $title;
        $this->description    = $description;
        $this->severity       = $severity;
        $this->category       = $category;
        $this->fix            = $fix;
        $this->searchPattern  = $searchPattern;
        $this->composerPackage = $composerPackage;
        $this->configKey      = $configKey;
        $this->envKey         = $envKey;
    }
}
