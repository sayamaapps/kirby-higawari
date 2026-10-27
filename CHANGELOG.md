# Changelog

All notable changes to this project are documented here. Versions follow
[Semantic Versioning](https://semver.org) with these project rules:

- **A change to the content field contract** (`tageskarteDatum`, `tagesgerichte`
  and their dish fields) **or to the default output** (what the snippet shows
  without configuration) is a **major** version: a site would show other dishes
  after a routine update.

## [Unreleased]

Layout and tooling after the Kirby plugin conventions. No change for a site:
output, options, content fields, blueprints and page methods are as in 1.4.0.

### Changed

- Classes live in `Sayamaapps\Higawari\` (`src/`), loaded through the class map
  in `autoload.php`. `index.php` only registers the plugin; `config/` holds the
  plugin's definitions. The 1.x class names are aliases in `legacy.php`.
- Snippet and Panel texts are read through Kirby's translation lookup, with the
  bundled text as fallback. The result is the same without an App.
- The snippet markup is indented with tabs. Only whitespace in the rendered HTML
  changes.
- Tests run with PHPUnit (`composer test`); `tests/run.sh` is gone. The test
  helpers `finish()` and `higawari_remove_tree()` stay as deprecated internals.
- `composer.lock` is committed. CI tests the lowest, the highest and the locked
  dependency versions.

### Deprecated

- `Higawari\I18n::t()`, use `Sayamaapps\Higawari\I18n::translate()`. A
  docblock only; no run-time notice, the result is the same.
- The 1.x class names `Higawari\*`, use `Sayamaapps\Higawari\*`. A docblock only.

### Planned for 2.0

- Broken options throw Kirby's `InvalidArgumentException` class (debug mode).
- Run-time deprecation notices for the aliases and `I18n::t()`, then their removal.

## [1.4.0] — 2026-10-25

### Added

- **Date mode `until`** (`sayamaapps.higawari.dateMode`, default `from`). The
  date can be the last day a menu is shown ("Anzeigen bis") instead of the
  first ("Gilt am"). The menu is then shown on every day up to and including
  the date; `days` does not apply. The Panel label, help and tab info, the
  snippet line ("Bis einschließlich …") and the `from` of the page method (today)
  follow the mode. Sites on the default need no change.
- New texts: `snippet.until`, `panel.date.until.label`, `panel.date.until.help`,
  `panel.info.until.text`, in German and English.

## [1.3.0] — 2026-10-21

### Added

- **The chat may choose the day** (Fude 1.4, `touch` with `editable: true`).
  A draft can set `tageskarteDatum` to another day than today, such as the menu
  for tomorrow; never a past day. Without a day it stays today. The bot reads
  what the date means from the field's label and help text ("Gilt am").

### Changed

- **`conflict` with `sayamaapps/kirby-fude` < 1.4** (was < 1.3): older Fude
  versions ignore `editable` silently.

### Removed

- **`DATENSCHUTZ.md` and `PRIVACY.md`.** Higawari processes no visitor data, so it has no place in a site's privacy policy. The README says so under "Privacy".

## [1.2.0] — 2026-10-20

### Changed

- **The Fude marker allows reading a photo of the menu.** The dishes field
  sets `readImages: true`, so a chat side may read a photo of the blackboard
  and propose its dishes as the new menu, as a draft that someone confirms.
  Needs Fude 1.3 or later; `composer.json` now conflicts with older versions,
  which refuse the unknown marker option.

## [1.1.0] — 2026-10-17

### Changed

- **Fude reads the menu from the blueprint.** The dishes field carries a
  `fude:` marker (replace, update, delete, at most five dishes, the date set to
  today on every change), so a site with Fude gets the collection
  `<page id>-tagesgerichte` without its own declaration. Needs Fude 1.2 or
  later; `composer.json` conflicts with older versions, which refuse the
  marker option `touch`.
- `examples/fude.php` is gone. A site that copied it into
  `site/config/fude.php` now has the menu twice: delete that entry, or set
  `'<page id>-tagesgerichte' => false` to keep its own.

## [1.0.0] — 2026-10-11

First release. Built for the daily menus of small restaurant sites.

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
