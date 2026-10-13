<?php
declare(strict_types=1);

namespace Keycheat;

/**
 * Renders the normalized keybindings model into a single self-contained HTML
 * cheat sheet. Rows are rendered server-side; a tiny script only filters the
 * existing DOM (no data blob embedded, no dependencies).
 */
final class HtmlRenderer
{
    /**
     * Accent palette (Catppuccin Macchiato). Each source gets a stable color
     * derived from its id, so the renderer stays app-agnostic.
     */
    private const PALETTE = [
        'var(--blue)',
        'var(--peach)',
        'var(--green)',
        'var(--mauve)',
        'var(--teal)',
        'var(--yellow)',
        'var(--pink)',
        'var(--lavender)',
    ];

    public function render(array $data): string
    {
        $sources = $data['sources'] ?? null;
        if (!is_array($sources)) {
            throw new \InvalidArgumentException("data.sources must be an array");
        }

        $total = 0;
        foreach ($sources as $src) {
            $total += count($src['bindings'] ?? []);
        }

        $generated = (string)($data['generated_at'] ?? '');
        $date = $generated !== '' ? substr($generated, 0, 10) : 'unknown';
        $sub = $total . ' shortcuts from ' . count($sources) . ' sources · generated ' . $date;

        $body = '';
        foreach ($sources as $src) {
            $body .= $this->renderSource($src);
        }

        return $this->shell($sub, $body);
    }

    private function renderSource(array $src): string
    {
        $bindings = $src['bindings'] ?? [];
        $id = (string)($src['id'] ?? '');
        $color = self::colorFor($id);
        $name = self::esc((string)($src['name'] ?? $id));
        $extraction = (string)($src['extraction'] ?? '');
        $sample = !empty($src['sample']);

        $head = '<div class="shead">'
            . '<span class="dot" style="background:' . $color . '"></span>'
            . '<h2>' . $name . '</h2>'
            . '<span class="count">' . count($bindings) . '</span>'
            . ($extraction !== ''
                ? '<span class="kind" title="static = parsed from the config file; runtime = queried from the running app">'
                    . self::esc($extraction) . '</span>'
                : '')
            . ($sample
                ? '<span class="badge" title="placeholder data for the demo, not from a real config">sample</span>'
                : '')
            . '</div>';

        $rows = '';
        foreach ($bindings as $b) {
            $rows .= $this->renderBinding($src, $b);
        }

        return '<section>' . $head . $rows . '</section>';
    }

    private function renderBinding(array $src, array $b): string
    {
        $keys = $b['keys'] ?? [];
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $desc = (string)($b['description'] ?? '');
        $action = (string)($b['action'] ?? '');

        $hay = self::norm(
            (string)($src['name'] ?? $src['id'] ?? '') . ' '
            . implode(' ', array_map('strval', $keys)) . ' '
            . $desc . ' ' . $action
        );

        return '<div class="binding" data-hay="' . self::esc($hay) . '">'
            . '<div class="keys">' . self::keysHtml($keys) . '</div>'
            . '<div class="desc">' . self::esc($desc) . '</div>'
            . '<div class="action">' . self::esc($action) . '</div>'
            . '</div>';
    }

    private static function colorFor(string $id): string
    {
        $n = count(self::PALETTE);
        return self::PALETTE[(crc32($id) % $n + $n) % $n];
    }

    private static function keysHtml(array $keys): string
    {
        $chords = [];
        foreach ($keys as $chord) {
            $parts = [];
            foreach (explode('+', (string)$chord) as $k) {
                $parts[] = '<kbd>' . self::esc($k) . '</kbd>';
            }
            $chords[] = implode('', $parts);
        }
        return implode('<span class="sep">then</span>', $chords);
    }

    private static function norm(string $s): string
    {
        $s = strtolower($s);
        $s = str_replace('+', ' ', $s);
        return trim((string)preg_replace('/\s+/', ' ', $s));
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function shell(string $sub, string $body): string
    {
        $subHtml = self::esc($sub);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>keycheat — cheatsheet</title>
<style>
  :root {
    --crust: #181926;
    --mantle: #1e2030;
    --base: #24273a;
    --surface0: #363a4f;
    --surface1: #494d64;
    --overlay0: #6e738d;
    --subtext: #a5adcb;
    --text: #cad3f5;
    --mauve: #c6a0f6;
    --pink: #f5bde6;
    --lavender: #b7bdf8;
    --blue: #8aadf4;
    --green: #a6da95;
    --peach: #f5a97f;
    --teal: #8bd5ca;
    --red: #ed8796;
    --yellow: #eed49f;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; }
  body {
    background: var(--mantle);
    color: var(--text);
    font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  .wrap { max-width: 920px; margin: 0 auto; padding: 32px 20px 80px; }

  header { margin-bottom: 20px; }
  h1 {
    margin: 0 0 4px;
    font-size: 30px;
    letter-spacing: -0.02em;
    background: linear-gradient(90deg, var(--mauve), var(--pink));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .sub { color: var(--overlay0); font-size: 13px; }

  .searchbar { position: sticky; top: 0; z-index: 5; padding: 12px 0; background: linear-gradient(var(--mantle) 70%, transparent); }
  #q {
    width: 100%;
    padding: 12px 14px;
    font-size: 15px;
    color: var(--text);
    background: var(--base);
    border: 1px solid var(--surface0);
    border-radius: 10px;
    outline: none;
  }
  #q:focus { border-color: var(--mauve); box-shadow: 0 0 0 3px rgba(198,160,246,.15); }
  #q::placeholder { color: var(--overlay0); }

  section { margin-top: 28px; }
  .shead { display: flex; align-items: baseline; gap: 10px; margin-bottom: 8px; }
  .shead h2 { font-size: 16px; margin: 0; font-weight: 600; }
  .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
  .count { color: var(--overlay0); font-size: 12px; }
  .badge {
    font-size: 10px; text-transform: uppercase; letter-spacing: .06em;
    color: var(--yellow); border: 1px solid var(--surface1);
    border-radius: 999px; padding: 1px 7px;
  }
  .kind { color: var(--overlay0); font-size: 11px; }

  .binding {
    display: grid;
    grid-template-columns: minmax(150px, max-content) 1fr auto;
    align-items: center;
    gap: 14px;
    padding: 9px 12px;
    border-radius: 8px;
    border-bottom: 1px solid var(--surface0);
  }
  .binding:hover { background: var(--base); }
  .binding.hidden { display: none; }

  .keys { white-space: nowrap; }
  kbd {
    font: 12px/1 ui-monospace, "JetBrains Mono", "SFMono-Regular", Consolas, monospace;
    display: inline-block;
    padding: 4px 7px;
    margin: 1px;
    color: var(--text);
    background: var(--surface0);
    border: 1px solid var(--surface1);
    border-bottom-width: 2px;
    border-radius: 6px;
  }
  .sep { color: var(--overlay0); font-size: 11px; margin: 0 6px; font-style: italic; }
  .desc { color: var(--text); }
  .action { color: var(--overlay0); font: 12px ui-monospace, Consolas, monospace; text-align: right; }

  .empty { color: var(--overlay0); text-align: center; padding: 40px 0; }
  footer { margin-top: 40px; color: var(--overlay0); font-size: 12px; text-align: center; }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>keycheat</h1>
    <div class="sub" id="sub">{$subHtml}</div>
  </header>

  <div class="searchbar">
    <input id="q" type="search" placeholder="Filter by key, description or command…  (try: alt space, ctrl+space c, workspace)" autocomplete="off" spellcheck="false">
  </div>

  <div id="content">{$body}</div>
  <div class="empty" id="empty" style="display:none">No matching shortcuts.</div>

  <footer>
    Generated by keycheat from <code>keybindings.json</code>.
  </footer>
</div>
<script>
(function () {
  var q = document.getElementById("q");
  var rows = Array.prototype.slice.call(document.querySelectorAll(".binding"));
  var empty = document.getElementById("empty");
  function norm(s) { return s.toLowerCase().replace(/\+/g, " ").replace(/\s+/g, " ").trim(); }
  function filter() {
    var tokens = norm(q.value).split(" ").filter(Boolean);
    var shown = 0;
    rows.forEach(function (row) {
      var hay = row.getAttribute("data-hay") || "";
      var hit = tokens.every(function (t) { return hay.indexOf(t) !== -1; });
      row.classList.toggle("hidden", !hit);
      if (hit) { shown++; }
    });
    empty.style.display = shown ? "none" : "block";
    Array.prototype.forEach.call(document.querySelectorAll("section"), function (sec) {
      var any = sec.querySelector(".binding:not(.hidden)");
      sec.style.display = any ? "" : "none";
    });
  }
  q.addEventListener("input", filter);
  q.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { q.value = ""; filter(); }
  });
  filter();
})();
</script>
</body>
</html>
HTML;
    }
}
