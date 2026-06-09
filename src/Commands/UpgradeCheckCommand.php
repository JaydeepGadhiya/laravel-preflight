<?php

namespace Jaydeep\UpgradeAssistant\Commands;

use Illuminate\Console\Command;
use Jaydeep\UpgradeAssistant\Analyzers\CodeAnalyzer;
use Jaydeep\UpgradeAssistant\Analyzers\ComposerAnalyzer;
use Jaydeep\UpgradeAssistant\Analyzers\ConfigAnalyzer;
use Jaydeep\UpgradeAssistant\Analyzers\EnvAnalyzer;
use Jaydeep\UpgradeAssistant\Report\ReportGenerator;
use Jaydeep\UpgradeAssistant\VersionRegistry\VersionRegistry;

class UpgradeCheckCommand extends Command
{
    protected $signature = 'upgrade:check
                            {version? : Target Laravel major version to check against (e.g. 9, 10, 11)}
                            {--report : Also write a Markdown report to storage/upgrade-report.md}';

    protected $description = 'Scan the codebase for issues before upgrading to a target Laravel version.';

    /** @var VersionRegistry */
    private $registry;

    public function __construct(VersionRegistry $registry)
    {
        parent::__construct();
        $this->registry = $registry;
    }

    public function handle(): int
    {
        $this->printBanner();

        // Resolve target version
        $targetVersion = $this->argument('version');

        $basePath         = base_path();
        $composerAnalyzer = new ComposerAnalyzer($basePath);
        $currentVersion   = $composerAnalyzer->getCurrentLaravelVersion() ?? 'unknown';

        if ($targetVersion === null) {
            $targetVersion = $this->registry->getLatestVersion();
            $this->line("  No version specified — defaulting to latest: <comment>Laravel {$targetVersion}</comment>");
        }

        $supported = $this->registry->getSupportedVersions();
        if (!in_array((int) $targetVersion, array_map('intval', $supported))) {
            $this->error("Version {$targetVersion} is not supported.");
            $this->line('  Supported versions: <comment>' . implode(', ', $supported) . '</comment>');
            return 1;
        }

        $this->line("  Current Laravel: <info>v{$currentVersion}</info>");
        $this->line("  Target  Laravel: <info>v{$targetVersion}</info>");
        $this->newLine();

        if ($currentVersion !== 'unknown' && (int) $currentVersion >= (int) $targetVersion) {
            $this->info("  You are already on Laravel {$currentVersion}. Nothing to upgrade.");
            return 0;
        }

        // Collect breaking changes
        $changes = $this->registry->getBreakingChanges(
            $currentVersion === 'unknown' ? '0' : $currentVersion,
            (string) $targetVersion
        );

        if (empty($changes)) {
            $this->info('No known breaking changes found for this version range.');
            return 0;
        }

        // Run analyzers
        $this->line('  <comment>Scanning codebase...</comment>');

        $changes = $composerAnalyzer->analyze($changes);
        $changes = (new CodeAnalyzer($basePath))->analyze($changes);
        $changes = (new ConfigAnalyzer($basePath))->analyze($changes);
        $changes = (new EnvAnalyzer($basePath))->analyze($changes);

        // Build report
        $report = new ReportGenerator($changes, $currentVersion, $targetVersion);

        $this->printConsoleReport($report);

        if ($this->option('report')) {
            $this->writeMarkdownReport($report);
        }

        return $report->hasIssues() ? 1 : 0;
    }

    private function printConsoleReport(ReportGenerator $report): void
    {
        $critical = $report->countBySeverity('critical');
        $warning  = $report->countBySeverity('warning');
        $info     = $report->countBySeverity('info');
        $safe     = count($report->getPassed());

        $this->newLine();
        $this->line('  ┌─────────────────────────────────────────────┐');
        $this->line('  │          UPGRADE COMPATIBILITY REPORT        │');
        $this->line('  └─────────────────────────────────────────────┘');
        $this->newLine();

        if (!$report->hasIssues()) {
            $this->line('  <info>No issues detected!</info> Your codebase looks compatible.');
            $this->line("  All {$safe} checks passed.");
            $this->newLine();
            return;
        }

        $this->line("  <error> CRITICAL </error> {$critical}   <comment>WARNING</comment> {$warning}   <info>INFO</info> {$info}   Passed {$safe}");
        $this->newLine();

        $severityColor = [
            'critical' => 'error',
            'warning'  => 'comment',
            'info'     => 'info',
        ];

        // Group by category for cleaner output
        $grouped = [];
        foreach ($report->getDetected() as $change) {
            $grouped[$change->category][] = $change;
        }

        foreach ($grouped as $category => $changes) {
            $this->line('  <fg=cyan;options=bold>── ' . strtoupper($category) . '</fg=cyan;options=bold>');
            $this->newLine();

            foreach ($changes as $change) {
                $tag   = $severityColor[$change->severity] ?? 'info';
                $badge = strtoupper($change->severity);

                $this->line("  <{$tag}>[{$badge}]</{$tag}> <options=bold>{$change->title}</options=bold>");
                $this->line("         {$change->description}");

                if (!empty($change->locations)) {
                    foreach ($change->locations as $loc) {
                        $this->line("         <fg=gray>  → {$loc}</fg=gray>");
                    }
                }

                $this->line("         <fg=green>Fix:</fg=green> {$change->fix}");
                $this->newLine();
            }
        }

        if ($safe > 0) {
            $this->line("  <info>{$safe} check(s) passed</info> (no issues found for those).");
            $this->newLine();
        }

        $this->line('  Run with <comment>--report</comment> to save a full Markdown report:');
        $this->line('  <comment>php artisan upgrade:check --report</comment>');
        $this->newLine();
    }

    private function writeMarkdownReport(ReportGenerator $report): void
    {
        $mdPath   = base_path('storage/upgrade-report.md');
        $htmlPath = base_path('storage/upgrade-report.html');

        if (file_put_contents($mdPath, $report->toMarkdown()) !== false) {
            $this->line("  <info>Markdown report saved to:</info> storage/upgrade-report.md");
        } else {
            $this->error('  Could not write storage/upgrade-report.md');
        }

        if (file_put_contents($htmlPath, $report->toHtml()) !== false) {
            $this->line("  <info>HTML report saved to:</info>     storage/upgrade-report.html");
        } else {
            $this->error('  Could not write storage/upgrade-report.html');
        }
    }

    private function printBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=cyan>Laravel Preflight</fg=cyan> <fg=gray>by Jaydeep</fg=gray>');
        $this->line('  <fg=gray>─────────────────────────────────────────</fg=gray>');
        $this->newLine();
    }
}
