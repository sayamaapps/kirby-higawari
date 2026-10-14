# Changelog

All notable changes to this project are documented here. Versions follow
[Semantic Versioning](https://semver.org) with these project rules:

- **A change to the content field contract** (`tageskarteDatum`, `tagesgerichte`
  and their dish fields) **or to the default output** (what the snippet shows
  without configuration) is a **major** version: a site would show other dishes
  after a routine update.
- **A change to the privacy text** (`DATENSCHUTZ.md` / `PRIVACY.md`) is marked
  **[privacy text]**. Every site using the plugin must then review its privacy
  policy.

## [1.0.0] — 2026-10-11

First release. Built from the daily menus of two existing sites, generalised
into one package.

### Added

- Daily menu: a date (`tageskarteDatum`) and up to five dishes
  (`tagesgerichte`: name, further lines, price, markers), shown on the date or on
  `sayamaapps.higawari.days` days counted from it.
- Options `sayamaapps.higawari.active`, `days`, `timezone`, `currency`,
  `emptyText`, `note`, `language`.
- Panel tab `higawari/tabs/daily-menu` with its pieces `higawari/fields/date`
  and `higawari/fields/dishes`, in German and English.
- Snippet `higawari` with semantic HTML and `higawari-*` classes, no script and no
  request to another server.
- Page method `higawariCard()` and `Menu::CLEARED` for "clear the menu".
- `examples/fude.php`: the declaration for Fude, which is the only route for
  changes by chat.
- Tests: Kirby-free rules, translation parity, Kirby snippet, options and
  clearing, Panel blueprints.
