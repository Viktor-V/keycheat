# keycheat

> One cheat sheet for every keybinding on your machine — collected from the dotfiles you already manage with chezmoi.

Every power user ends up with keybindings spread across a dozen config files: a tiling window
manager, a launcher, a terminal, an editor. Remembering them is a losing battle, and there is no
single place to look them up. **keycheat** collects them from your [chezmoi](https://www.chezmoi.io/)
source into one normalized dataset and renders it, so you can find any shortcut in seconds.

## The problem

- Keybindings live in many formats: JSON, YAML, Lua, TOML, INI.
- Each app has its own way to browse them — or none at all.
- OS-level "shortcut overlays" only know system shortcuts. They never read *your* configs.
- Nothing of the sort is cross-platform.

## Built for chezmoi

Figuring out where every app keeps its config — and doing it on Windows, macOS and Linux — is a lot
of work and a lot of guessing. If your dotfiles are managed by
[chezmoi](https://www.chezmoi.io/), you already have one place that knows all of it.

keycheat treats the chezmoi source as its input:

- **No machine scanning.** It asks chezmoi for the paths it cares about (`chezmoi source-path`,
  `chezmoi cat <target>`), so templates are rendered exactly as they are on this machine.
- **One canonical set.** The same configs you already version and sync are the ones keycheat reads.
- **Cross-platform for free.** chezmoi already normalizes the Windows / macOS / Linux differences;
  keycheat does not have to.

It is designed as a natural chezmoi companion: ship it as the `chezmoi-keycheat` plugin so
`chezmoi keycheat` regenerates the cheat sheet, and hook it into `chezmoi apply` so the data never
goes stale.

## The idea

A small, cross-platform CLI with two halves, deliberately separated:

1. **Collect — portable core.**
   Adapters read each app's config from chezmoi (or ask the app at runtime) and normalize
   everything into a single `keybindings.json`.
2. **Present — pluggable.**
   Renderers turn that JSON into a self-contained HTML cheat sheet or Markdown. Thin, per-OS
   viewers can show it as a desktop overlay triggered by a hotkey.

Because the data is plain JSON, the core stays portable and the presentation can be whatever fits
the OS.

```
 chezmoi source (rendered per machine)     keycheat core (PHP, portable)        viewers (per-OS, thin)
 ┌────────────────────────────────┐         ┌────────────────────────────┐      ┌──────────────────────┐
 │ dot_wezterm.lua                │         │ adapters  -> normalize     │      │ browser (HTML)       │
 │ dot_config/.../Settings.json   │  --->   │           -> keybindings   │ -->  │ desktop overlay      │
 │ AppData/.../config.yaml        │         │ renderers -> .html / .md   │      │ launcher plugin      │
 └────────────────────────────────┘         └────────────────────────────┘      └──────────────────────┘
        + runtime queries (wezterm show-keys, nvim --headless)
```

## Design principles

- **chezmoi-first.** The source of truth is the chezmoi source, not a scan of the live machine.
- **Data is not presentation.** The core produces portable data; showing it is a separate,
  optional layer.
- **Adapters, not a monolith.** Each app is a small adapter. Adding one should not touch the others.
- **Ask when parsing is unreliable.** Some configs are dynamic (Neovim, WezTerm) — extract their
  *effective* bindings at runtime instead of guessing with fragile regex.
- **Zero-dependency rendering.** The HTML cheat sheet is one self-contained file.
- **Bring your own hotkey.** keycheat does not install a global hotkey daemon. It plugs into the
  one you already have: AutoHotkey, GlazeWM, Hammerspoon, your desktop environment.

## Sources (roadmap)

Each adapter knows the chezmoi target path (e.g. `.wezterm.lua`,
`AppData/Roaming/FlowLauncher/Settings/Settings.json`) and whether the config is parsed statically
or queried at runtime.

| Source        | Format | Extraction                    |
| ------------- | ------ | ----------------------------- |
| Flow Launcher | JSON   | static                        |
| GlazeWM       | YAML   | static                        |
| WezTerm       | Lua    | runtime (`wezterm show-keys`) |
| Neovim        | Lua    | runtime (`nvim --headless`)   |
| ...           | ...    | ...                           |

## Planned usage

```sh
keycheat parse          # read chezmoi-managed configs -> build/keybindings.json
keycheat render html    # -> build/cheatsheet.html
keycheat show           # render + open the cheat sheet
```

## Data model (draft)

```json
{
  "source": "glazewm",
  "context": "global",
  "keys": "alt+shift+1",
  "action": "focus --workspace 1",
  "description": "Focus workspace 1"
}
```

## Non-goals

- Not a hotkey daemon, key remapper, or application launcher.
- Not a machine-wide config scanner — it reads the configs chezmoi manages.
- Not a replacement for editor-native tools such as `which-key.nvim`.
- Not opinionated about the UI — pick your viewer.

## Status

Early design. README first, then the core and the first adapters.

## Contributing

Adapters are the friendliest place to help. Each one is a small module that turns a config file
(or a runtime command) into the normalized model.

## License

MIT
