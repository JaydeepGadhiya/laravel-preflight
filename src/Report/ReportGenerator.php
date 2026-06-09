<?php

namespace Jaydeep\UpgradeAssistant\Report;

use Jaydeep\UpgradeAssistant\Data\BreakingChange;

class ReportGenerator
{
    /** @var BreakingChange[] */
    private $detected;

    /** @var BreakingChange[] */
    private $passed;

    /** @var string */
    private $fromVersion;

    /** @var string */
    private $toVersion;

    /**
     * @param BreakingChange[] $allChanges
     */
    public function __construct(array $allChanges, string $fromVersion, string $toVersion)
    {
        $this->fromVersion = $fromVersion;
        $this->toVersion   = $toVersion;
        $this->detected    = array_values(array_filter($allChanges, function ($c) { return $c->detected; }));
        $this->passed      = array_values(array_filter($allChanges, function ($c) { return !$c->detected; }));
    }

    public function getDetected(): array  { return $this->detected; }
    public function getPassed(): array    { return $this->passed; }
    public function hasIssues(): bool     { return count($this->detected) > 0; }

    public function countBySeverity(string $severity): int
    {
        return count(array_filter($this->detected, function ($c) use ($severity) {
            return $c->severity === $severity;
        }));
    }

    /**
     * Build a Markdown report string.
     */
    public function toMarkdown(): string
    {
        $date     = date('Y-m-d H:i:s');
        $critical = $this->countBySeverity('critical');
        $warning  = $this->countBySeverity('warning');
        $info     = $this->countBySeverity('info');
        $total    = count($this->detected);
        $safe     = count($this->passed);

        $lines = [];
        $lines[] = '# Laravel Preflight Report';
        $lines[] = '';
        $lines[] = '| | |';
        $lines[] = '|---|---|';
        $lines[] = "| **Generated** | {$date} |";
        $lines[] = "| **Upgrading** | Laravel {$this->fromVersion} → {$this->toVersion} |";
        $lines[] = "| **Issues found** | {$total} ({$critical} critical, {$warning} warning, {$info} info) |";
        $lines[] = "| **Checks passed** | {$safe} |";
        $lines[] = '';

        if ($total === 0) {
            $lines[] = '## No issues detected';
            $lines[] = 'Your codebase looks compatible with Laravel ' . $this->toVersion . '. Review the Laravel upgrade guide for any manual steps.';
            return implode("\n", $lines);
        }

        $lines[] = '## Issues Requiring Attention';
        $lines[] = '';

        $grouped = [];
        foreach ($this->detected as $change) {
            $grouped[$change->category][] = $change;
        }

        foreach ($grouped as $category => $changes) {
            $lines[] = '### ' . strtoupper($category);
            $lines[] = '';
            foreach ($changes as $change) {
                $badge   = strtoupper($change->severity);
                $lines[] = "#### [{$badge}] {$change->title}";
                $lines[] = '';
                $lines[] = $change->description;
                $lines[] = '';
                if (!empty($change->locations)) {
                    $lines[] = '**Detected in:**';
                    foreach ($change->locations as $loc) {
                        $lines[] = '- `' . $loc . '`';
                    }
                    $lines[] = '';
                }
                $lines[] = '**Fix:** ' . $change->fix;
                $lines[] = '';
                $lines[] = '---';
                $lines[] = '';
            }
        }

        if (!empty($this->passed)) {
            $lines[] = '## Checks Passed (' . count($this->passed) . ')';
            $lines[] = '';
            $lines[] = '| Check | Category |';
            $lines[] = '|-------|----------|';
            foreach ($this->passed as $change) {
                $lines[] = '| ' . $change->title . ' | ' . $change->category . ' |';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Build a self-contained HTML report string.
     */
    public function toHtml(): string
    {
        $date        = htmlspecialchars(date('Y-m-d H:i:s'));
        $from        = htmlspecialchars($this->fromVersion);
        $to          = htmlspecialchars($this->toVersion);
        $critical    = $this->countBySeverity('critical');
        $warning     = $this->countBySeverity('warning');
        $info        = $this->countBySeverity('info');
        $total       = count($this->detected);
        $safe        = count($this->passed);
        $all         = $total + $safe;
        $score       = $all > 0 ? (int) round(($safe / $all) * 100) : 100;
        $statusLabel = $total === 0 ? 'COMPATIBLE' : ($critical > 0 ? 'ACTION REQUIRED' : 'REVIEW NEEDED');
        $statusCls   = $total === 0 ? 'compatible' : ($critical > 0 ? 'danger' : 'warn');
        $ringColor   = $score >= 80 ? '#4ade80' : ($score >= 50 ? '#f59e0b' : '#f43f5e');
        $r           = 34;
        $circ        = round(2 * M_PI * $r, 2);
        $offset      = round($circ - ($score / 100) * $circ, 2);

        $issuesHtml  = $this->buildIssuesHtml();
        $passedHtml  = $this->buildPassedHtml();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Upgrade Report — Laravel {$from} → {$to}</title>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:#07090f;--s1:#0e1118;--s2:#131722;--s3:#19202e;
      --b1:#1d2438;--b2:#2a3347;
      --tx:#dde3f0;--tm:#7888a8;--tf:#3d4d6a;
      --red:#f43f5e;--red-bg:rgba(244,63,94,.1);--red-br:rgba(244,63,94,.25);
      --ylw:#f59e0b;--ylw-bg:rgba(245,158,11,.1);--ylw-br:rgba(245,158,11,.25);
      --sky:#38bdf8;--sky-bg:rgba(56,189,248,.1);--sky-br:rgba(56,189,248,.25);
      --grn:#4ade80;--grn-bg:rgba(74,222,128,.08);--grn-br:rgba(74,222,128,.2);
      --lr:#FF2D20;--radius:10px;
      --mono:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;
    }
    html{scroll-behavior:smooth}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
         background:var(--bg);color:var(--tx);line-height:1.6;font-size:14px;min-height:100vh}

    /* PAGE HEADER */
    .ph{background:linear-gradient(160deg,#0e0507 0%,#080c16 55%,#07090f 100%);
        border-bottom:1px solid var(--b1);padding:36px 52px 28px}
    .ph-top{display:flex;align-items:flex-start;justify-content:space-between;
            flex-wrap:wrap;gap:20px;margin-bottom:28px}
    .brand{display:flex;align-items:center;gap:14px}
    .brand-icon{width:42px;height:42px;border-radius:11px;flex-shrink:0;
                background:linear-gradient(135deg,#ff2d20,#ff6020);
                display:flex;align-items:center;justify-content:center;
                font-size:20px;font-weight:900;color:#fff;
                box-shadow:0 0 18px rgba(255,45,32,.35)}
    .brand h1{font-size:21px;font-weight:800;color:#fff;letter-spacing:-.3px}
    .brand h1 em{font-style:normal;color:var(--lr)}
    .brand-sub{font-size:12px;color:var(--tm);margin-top:2px}
    .ph-right{display:flex;flex-direction:column;align-items:flex-end;gap:9px}
    .vpath{display:flex;align-items:center;gap:7px}
    .vbadge{padding:4px 11px;border-radius:6px;font-size:12px;font-weight:700;
            background:var(--s2);border:1px solid var(--b2);color:var(--tx)}
    .vbadge-to{background:var(--lr);border-color:var(--lr);color:#fff}
    .varrow{font-size:16px;color:var(--tm)}
    .sc-chip{padding:4px 13px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.07em}
    .sc-chip.compatible{background:var(--grn-bg);border:1px solid var(--grn-br);color:var(--grn)}
    .sc-chip.danger{background:var(--red-bg);border:1px solid var(--red-br);color:var(--red)}
    .sc-chip.warn{background:var(--ylw-bg);border:1px solid var(--ylw-br);color:var(--ylw)}
    /* stats */
    .stats{display:flex;gap:10px;flex-wrap:wrap;align-items:stretch}
    .sc{background:var(--s1);border:1px solid var(--b1);border-radius:var(--radius);
        padding:13px 18px;min-width:82px;text-align:center}
    .sc-n{font-size:26px;font-weight:800;line-height:1}
    .sc-l{font-size:10px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
          color:var(--tm);margin-top:3px}
    .sc-critical .sc-n{color:var(--red)}
    .sc-warning  .sc-n{color:var(--ylw)}
    .sc-info     .sc-n{color:var(--sky)}
    .sc-passed   .sc-n{color:var(--grn)}
    .sc-ring{padding:10px 14px;min-width:96px}
    .ring-wrap{display:flex;align-items:center;justify-content:center;gap:8px}
    .ring-svg{width:44px;height:44px}
    .ring-num{font-size:17px;font-weight:800;color:{$ringColor}}

    /* FILTER BAR */
    .fb{position:sticky;top:0;z-index:100;
        background:rgba(7,9,15,.88);backdrop-filter:blur(14px);
        border-bottom:1px solid var(--b1);padding:0 52px;
        transition:box-shadow .2s}
    .fb.scrolled{box-shadow:0 4px 20px rgba(0,0,0,.5)}
    .fb-inner{display:flex;align-items:center;gap:5px;overflow-x:auto;
              padding:10px 0;scrollbar-width:none}
    .fb-inner::-webkit-scrollbar{display:none}
    .ftab{display:inline-flex;align-items:center;gap:5px;white-space:nowrap;
          padding:5px 13px;border-radius:20px;border:1px solid var(--b1);
          background:transparent;color:var(--tm);font-size:12px;font-weight:600;
          cursor:pointer;transition:all .15s}
    .ftab:hover{border-color:var(--b2);color:var(--tx);background:var(--s2)}
    .ftab.active{background:var(--s2);border-color:var(--b2);color:var(--tx)}
    .ftab-critical.active{background:var(--red-bg);border-color:var(--red-br);color:var(--red)}
    .ftab-warning.active{background:var(--ylw-bg);border-color:var(--ylw-br);color:var(--ylw)}
    .ftab-info.active{background:var(--sky-bg);border-color:var(--sky-br);color:var(--sky)}
    .ftab-passed.active{background:var(--grn-bg);border-color:var(--grn-br);color:var(--grn)}
    .ftab-cnt{display:inline-flex;align-items:center;justify-content:center;
              min-width:17px;height:17px;padding:0 4px;
              border-radius:9px;background:var(--s3);font-size:10px;font-weight:700}
    .fb-sep{width:1px;height:18px;background:var(--b1);flex-shrink:0;margin:0 3px}
    .bulk-btn{margin-left:auto;display:flex;gap:4px;flex-shrink:0}
    .bbtn{padding:4px 10px;border-radius:6px;border:1px solid var(--b1);
          background:transparent;color:var(--tm);font-size:11px;font-weight:600;
          cursor:pointer;transition:all .15s}
    .bbtn:hover{background:var(--s2);color:var(--tx);border-color:var(--b2)}

    /* MAIN */
    .main{padding:28px 52px 48px;max-width:1020px}
    .sec-hd{display:flex;align-items:center;gap:8px;margin-bottom:14px;
            padding-bottom:10px;border-bottom:1px solid var(--b1)}
    .sec-title{font-size:15px;font-weight:700;color:#fff}
    .sec-badge{font-size:11px;font-weight:700;padding:2px 8px;
               border-radius:4px;background:var(--s3);color:var(--tm)}
    .sec-actions{margin-left:auto;display:flex;gap:4px}

    /* CATEGORY GROUP */
    .cat-group{margin-bottom:24px}
    .cat-hd{display:flex;align-items:center;gap:8px;margin-bottom:8px}
    .cat-pill{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
              color:#22d3ee;padding:2px 9px;border-radius:4px;
              background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.15)}
    .cat-count{font-size:11px;color:var(--tm)}

    /* ISSUE CARD */
    .ic{background:var(--s1);border:1px solid var(--b1);border-radius:var(--radius);
        margin-bottom:6px;border-left:3px solid var(--b1);overflow:hidden;
        transition:border-left-color .15s,box-shadow .15s}
    .ic:hover{box-shadow:0 2px 12px rgba(0,0,0,.35)}
    .ic.ic-critical{border-left-color:var(--red)}
    .ic.ic-warning {border-left-color:var(--ylw)}
    .ic.ic-info    {border-left-color:var(--sky)}
    .ic-hd{display:flex;align-items:center;justify-content:space-between;
           padding:12px 16px;cursor:pointer;user-select:none;gap:10px}
    .ic-hd-l{display:flex;align-items:center;gap:7px;flex:1;min-width:0;flex-wrap:wrap}
    .sev{flex-shrink:0;font-size:9px;font-weight:700;letter-spacing:.1em;
         padding:2px 7px;border-radius:4px;text-transform:uppercase}
    .sev-critical{background:var(--red-bg);color:var(--red);border:1px solid var(--red-br)}
    .sev-warning {background:var(--ylw-bg);color:var(--ylw);border:1px solid var(--ylw-br)}
    .sev-info    {background:var(--sky-bg);color:var(--sky);border:1px solid var(--sky-br)}
    .ic-title{font-size:13px;font-weight:600;color:var(--tx);line-height:1.4}
    .chevron{flex-shrink:0;width:15px;height:15px;color:var(--tf);transition:transform .2s}
    .ic.open .chevron{transform:rotate(180deg)}
    .ic-bd{display:none;padding:0 16px 14px;border-top:1px solid var(--b1)}
    .ic.open .ic-bd{display:block}
    .ic-desc{font-size:12.5px;color:var(--tm);line-height:1.7;padding:10px 0 8px}

    /* LOCATIONS */
    .locs{margin-bottom:10px}
    .locs-lbl{font-size:10px;font-weight:700;color:var(--tf);
              text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px}
    .loc-chip{display:inline-flex;align-items:center;gap:4px;
              font-family:var(--mono);font-size:11px;color:#94a3b8;
              background:rgba(13,17,23,.7);border:1px solid var(--b1);
              border-radius:5px;padding:3px 8px;margin:2px 3px 2px 0}
    .loc-dot{width:5px;height:5px;border-radius:50%;background:var(--sky);flex-shrink:0}

    /* FIX BLOCK */
    .fix-block{background:rgba(74,222,128,.05);border:1px solid var(--grn-br);
               border-radius:8px;overflow:hidden}
    .fix-hd{display:flex;align-items:center;justify-content:space-between;
            padding:6px 11px;background:rgba(74,222,128,.07);
            border-bottom:1px solid var(--grn-br)}
    .fix-lbl{font-size:10px;font-weight:700;letter-spacing:.07em;
             text-transform:uppercase;color:var(--grn)}
    .copy-btn{padding:2px 9px;border-radius:4px;border:1px solid var(--grn-br);
              background:transparent;color:var(--grn);font-size:10px;font-weight:700;
              cursor:pointer;transition:all .15s;letter-spacing:.03em}
    .copy-btn:hover{background:var(--grn-bg)}
    .copy-btn.copied{opacity:.7}
    .fix-txt{font-size:12px;color:var(--tx);line-height:1.7;padding:9px 11px}

    /* PASSED SECTION */
    .passed-wrap{margin-top:28px}
    .passed-toggle{display:flex;align-items:center;justify-content:space-between;
                   padding:13px 16px;background:var(--s1);border:1px solid var(--b1);
                   border-radius:var(--radius);cursor:pointer;user-select:none;
                   transition:background .15s}
    .passed-toggle:hover{background:var(--s2)}
    .passed-tl{display:flex;align-items:center;gap:10px}
    .passed-icon{font-size:15px;color:var(--grn)}
    .passed-title{font-size:13px;font-weight:600;color:var(--tx)}
    .passed-sub{font-size:11px;color:var(--tm)}
    .p-chev{width:15px;height:15px;color:var(--tf);transition:transform .2s;flex-shrink:0}
    .p-chev.rotated{transform:rotate(180deg)}
    .passed-body{display:none;margin-top:2px}
    .passed-body.open{display:block}
    .passed-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:4px;padding:8px 0}
    .pc{display:flex;align-items:center;gap:7px;padding:7px 11px;
        background:var(--s1);border:1px solid var(--b1);border-radius:7px}
    .pc-tick{color:var(--grn);font-size:12px;flex-shrink:0}
    .pc-name{font-size:12px;color:var(--tx);flex:1;min-width:0;
             overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .pc-cat{font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
            padding:2px 6px;border-radius:3px;background:var(--s3);color:var(--tm);flex-shrink:0}

    /* ALL CLEAR */
    .all-clear{background:var(--grn-bg);border:1px solid var(--grn-br);
               border-radius:var(--radius);padding:48px;text-align:center}
    .all-clear-icon{font-size:44px;margin-bottom:14px}
    .all-clear h2{font-size:20px;font-weight:800;color:var(--grn);margin-bottom:8px}
    .all-clear p{font-size:13px;color:var(--tm);max-width:440px;margin:0 auto;line-height:1.7}

    /* FOOTER */
    .pf{padding:22px 52px;border-top:1px solid var(--b1);color:var(--tm);font-size:12px;
        display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
    .pf a{color:var(--sky);text-decoration:none}
    .pf a:hover{text-decoration:underline}

    /* RESPONSIVE */
    @media(max-width:680px){
      .ph,.fb,.main,.pf{padding-left:18px;padding-right:18px}
      .ph{padding-top:24px;padding-bottom:20px}
      .brand h1{font-size:17px}
      .stats{gap:7px}
      .sc{padding:10px 12px;min-width:68px}
      .sc-n{font-size:22px}
      .main{padding-top:20px}
    }
    @media print{
      .fb,.bulk-btn{display:none}
      .ic-bd,.passed-body{display:block!important}
      .ic.open .ic-bd{display:block!important}
      body{background:#fff;color:#000}
    }
  </style>
</head>
<body>

  <header class="ph">
    <div class="ph-top">
      <div class="brand">
        <div class="brand-icon">&#x2191;</div>
        <div>
          <h1>Laravel <em>Upgrade</em> Assistant</h1>
          <div class="brand-sub">Generated {$date}</div>
        </div>
      </div>
      <div class="ph-right">
        <div class="vpath">
          <span class="vbadge">L{$from}</span>
          <span class="varrow">&#x2192;</span>
          <span class="vbadge vbadge-to">L{$to}</span>
        </div>
        <span class="sc-chip {$statusCls}">{$statusLabel}</span>
      </div>
    </div>
    <div class="stats">
      <div class="sc sc-critical"><div class="sc-n">{$critical}</div><div class="sc-l">Critical</div></div>
      <div class="sc sc-warning"><div class="sc-n">{$warning}</div><div class="sc-l">Warning</div></div>
      <div class="sc sc-info"><div class="sc-n">{$info}</div><div class="sc-l">Info</div></div>
      <div class="sc sc-passed"><div class="sc-n">{$safe}</div><div class="sc-l">Passed</div></div>
      <div class="sc sc-ring">
        <div class="ring-wrap">
          <svg class="ring-svg" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="{$r}" fill="none" stroke="#1d2438" stroke-width="6"/>
            <circle id="scoreRing" cx="40" cy="40" r="{$r}" fill="none" stroke="{$ringColor}"
              stroke-width="6" stroke-linecap="round"
              stroke-dasharray="{$circ}" stroke-dashoffset="{$circ}"
              transform="rotate(-90 40 40)"
              style="transition:stroke-dashoffset 1s ease"/>
          </svg>
          <span class="ring-num">{$score}%</span>
        </div>
        <div class="sc-l">Readiness</div>
      </div>
    </div>
  </header>

  <div class="fb" id="fb">
    <div class="fb-inner">
      <button class="ftab active" data-f="all">All <span class="ftab-cnt">{$total}</span></button>
      <button class="ftab ftab-critical" data-f="critical">Critical <span class="ftab-cnt">{$critical}</span></button>
      <button class="ftab ftab-warning" data-f="warning">Warning <span class="ftab-cnt">{$warning}</span></button>
      <button class="ftab ftab-info" data-f="info">Info <span class="ftab-cnt">{$info}</span></button>
      <div class="fb-sep"></div>
      <button class="ftab ftab-passed" data-f="passed">Passed <span class="ftab-cnt">{$safe}</span></button>
      <div class="bulk-btn">
        <button class="bbtn" onclick="expandAll()">Expand All</button>
        <button class="bbtn" onclick="collapseAll()">Collapse All</button>
      </div>
    </div>
  </div>

  <main class="main">
    {$issuesHtml}
    {$passedHtml}
  </main>

  <footer class="pf">
    <span>Laravel Preflight &mdash; <a href="https://github.com/JaydeepGadhiya" target="_blank">Jaydeep Gadhiya</a></span>
    <span>Laravel {$from} &#x2192; {$to}</span>
  </footer>

  <script>
    // animate score ring on load
    window.addEventListener('load', function() {
      var ring = document.getElementById('scoreRing');
      if (ring) { setTimeout(function(){ ring.style.strokeDashoffset = '{$offset}'; }, 150); }
    });

    // toggle card
    document.querySelectorAll('.ic-hd').forEach(function(hd) {
      hd.addEventListener('click', function() { this.closest('.ic').classList.toggle('open'); });
    });

    // expand / collapse all
    function expandAll()  { document.querySelectorAll('.ic').forEach(function(c){ c.classList.add('open'); }); }
    function collapseAll(){ document.querySelectorAll('.ic').forEach(function(c){ c.classList.remove('open'); }); }

    // filter tabs
    document.querySelectorAll('.ftab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.ftab').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        var f = this.dataset.f;

        if (f === 'passed') {
          document.querySelectorAll('.ic').forEach(function(c){ c.style.display = 'none'; });
          document.querySelectorAll('.cat-group').forEach(function(g){ g.style.display = 'none'; });
          var body = document.getElementById('passedBody');
          var chev = document.getElementById('passedChev');
          if (body) body.classList.add('open');
          if (chev) chev.classList.add('rotated');
          var ps = document.getElementById('passedSection');
          if (ps) { setTimeout(function(){ ps.scrollIntoView({behavior:'smooth'}); }, 50); }
          return;
        }

        document.querySelectorAll('.ic').forEach(function(c) {
          c.style.display = (f === 'all' || c.dataset.sev === f) ? '' : 'none';
        });
        document.querySelectorAll('.cat-group').forEach(function(g) {
          var vis = g.querySelectorAll('.ic:not([style*="none"])').length;
          g.style.display = vis ? '' : 'none';
        });
      });
    });

    // copy fix text
    function copyFix(btn) {
      var txt = btn.closest('.fix-block').querySelector('.fix-txt').textContent;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(function() {
          btn.textContent = 'Copied!';
          btn.classList.add('copied');
          setTimeout(function(){ btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
        });
      }
    }

    // toggle passed section
    function togglePassed() {
      var body = document.getElementById('passedBody');
      var chev = document.getElementById('passedChev');
      if (body) body.classList.toggle('open');
      if (chev) chev.classList.toggle('rotated');
    }

    // sticky bar shadow
    window.addEventListener('scroll', function() {
      var bar = document.getElementById('fb');
      if (bar) bar.classList.toggle('scrolled', window.scrollY > 60);
    });
  </script>
</body>
</html>
HTML;
    }

    private function buildIssuesHtml(): string
    {
        if (empty($this->detected)) {
            return '<div class="all-clear">
                      <div class="all-clear-icon">&#x2705;</div>
                      <h2>No issues detected!</h2>
                      <p>Your codebase looks compatible with Laravel ' . htmlspecialchars($this->toVersion) . '.
                         Review the official upgrade guide for any remaining manual steps.</p>
                    </div>';
        }

        $grouped = [];
        foreach ($this->detected as $change) {
            $grouped[$change->category][] = $change;
        }

        $total = count($this->detected);
        $html  = '<div class="sec-hd">';
        $html .= '<span class="sec-title">Issues Requiring Attention</span>';
        $html .= '<span class="sec-badge">' . $total . '</span>';
        $html .= '<div class="sec-actions">';
        $html .= '</div></div>';

        $idx = 0;
        foreach ($grouped as $category => $changes) {
            $count = count($changes);
            $html .= '<div class="cat-group" id="cat-' . htmlspecialchars($category) . '">';
            $html .= '<div class="cat-hd">';
            $html .= '<span class="cat-pill">' . htmlspecialchars(strtoupper($category)) . '</span>';
            $html .= '<span class="cat-count">' . $count . ' issue' . ($count !== 1 ? 's' : '') . '</span>';
            $html .= '</div>';

            foreach ($changes as $change) {
                $sev    = htmlspecialchars($change->severity);
                $sevLbl = strtoupper($sev);
                $html  .= '<div class="ic ic-' . $sev . '" data-sev="' . $sev . '" data-cat="' . htmlspecialchars($change->category) . '" id="ic-' . $idx++ . '">';
                $html  .= '<div class="ic-hd">';
                $html  .= '<div class="ic-hd-l">';
                $html  .= '<span class="sev sev-' . $sev . '">' . $sevLbl . '</span>';
                $html  .= '<span class="ic-title">' . htmlspecialchars($change->title) . '</span>';
                $html  .= '</div>';
                $html  .= '<svg class="chevron" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                $html  .= '</div>';
                $html  .= '<div class="ic-bd">';
                $html  .= '<p class="ic-desc">' . htmlspecialchars($change->description) . '</p>';

                if (!empty($change->locations)) {
                    $html .= '<div class="locs"><div class="locs-lbl">Detected in</div>';
                    foreach ($change->locations as $loc) {
                        $html .= '<span class="loc-chip"><span class="loc-dot"></span>' . htmlspecialchars($loc) . '</span>';
                    }
                    $html .= '</div>';
                }

                $html .= '<div class="fix-block">';
                $html .= '<div class="fix-hd"><span class="fix-lbl">Fix</span><button class="copy-btn" onclick="copyFix(this)">Copy</button></div>';
                $html .= '<div class="fix-txt">' . htmlspecialchars($change->fix) . '</div>';
                $html .= '</div>';
                $html .= '</div></div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    private function buildPassedHtml(): string
    {
        if (empty($this->passed)) {
            return '';
        }

        $count = count($this->passed);
        $html  = '<div class="passed-wrap" id="passedSection">';
        $html .= '<div class="passed-toggle" onclick="togglePassed()">';
        $html .= '<div class="passed-tl">';
        $html .= '<span class="passed-icon">&#x2713;</span>';
        $html .= '<div><div class="passed-title">Checks Passed</div>';
        $html .= '<div class="passed-sub">' . $count . ' check' . ($count !== 1 ? 's' : '') . ' with no issues found</div></div>';
        $html .= '</div>';
        $html .= '<svg class="p-chev" id="passedChev" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $html .= '</div>';
        $html .= '<div class="passed-body" id="passedBody"><div class="passed-grid">';

        foreach ($this->passed as $change) {
            $html .= '<div class="pc">';
            $html .= '<span class="pc-tick">&#x2713;</span>';
            $html .= '<span class="pc-name" title="' . htmlspecialchars($change->title) . '">' . htmlspecialchars($change->title) . '</span>';
            $html .= '<span class="pc-cat">' . htmlspecialchars($change->category) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div></div></div>';
        return $html;
    }
}
