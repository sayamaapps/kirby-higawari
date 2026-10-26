# Higawari

**Kirby 5 · PHP 8.2 to 8.5 · MIT**

Daily menu for [Kirby CMS](https://getkirby.com) 5. *Higawari* (日替わり) is Japanese for "changes every day": a menu of dishes, each menu valid on a date you set, shown on that day and nothing else.

- **A date, a window.** A menu is valid on its date, or on as many days as the site sets, counted from that date. Without a date nothing is shown, so an old card cannot linger.
- **The Panel.** A tab with the date and up to five dishes (name, further lines, price, markers). Dish names are the only required text.
- **Plain output.** One snippet with semantic HTML and `higawari-*` classes. No JavaScript, no request to another server, no cookie.
- **Optional text when nothing is valid.** Either nothing, or a sentence of your choice.
- **Nothing that writes from outside.** Higawari has no chat, bot or write endpoint. Changes by chat belong to the separate package [Fude](https://github.com/sayamaapps/kirby-fude), which reads the marker in the dishes blueprint (see below).

Code, comments and this README are in English. The Panel texts and the snippet texts are in German and English.

## Requirements

- Kirby 5 (`getkirby/cms` ^5.0)
- PHP 8.2 to 8.5, with the `mbstring` extension

## Installation

```sh
composer require sayamaapps/kirby-higawari
```

Composer installs the plugin into `site/plugins/higawari/`. Until the package is on Packagist, add the repository first:

```json
"repositories": [{ "type": "vcs", "url": "https://github.com/sayamaapps/kirby-higawari" }]
```

**ZIP:** download the release from [GitHub](https://github.com/sayamaapps/kirby-higawari/releases) and unpack it to `site/plugins/higawari/`.

**Git submodule:** from the Kirby project root:

```sh
git submodule add https://github.com/sayamaapps/kirby-higawari site/plugins/higawari
```

Off by default. Switch it on in `site/config/config.php`:

```php
return [
    'sayamaapps.higawari.active' => true,
];
```

## Configuration

All options are read as `sayamaapps.higawari.<name>`, flat or nested. The spelling `higawari.<name>` of 1.x still works until 2.0, with a deprecation in debug mode (see [Deprecations](#deprecations)). `null` counts as "not set". A broken value is reported to the PHP error log once and the default is used; with Kirby's `debug` option on, it throws instead.

| Option | Default | Meaning |
|---|---|---|
| `active` | `false` | Master switch. Only a real `true` switches it on. |
| `days` | `1` | How many days a menu is shown, counted from its date: `1` is the day itself, `3` is the date and the two days after. From 1 to 366. Only in mode `from`; mode `until` ignores it. |
| `dateMode` | `from` | What the date means, see [Two date modes](#two-date-modes). `from`: the first day, shown for `days` days. `until`: the last day ("Anzeigen bis"). Any other value is reported and `from` is used. |
| `timezone` | `Europe/Berlin` | The zone in which "today" is decided. |
| `currency` | `€` | Shown after a price, with a space: `7,50 €`. An empty string shows bare numbers: `7,50`. |
| `emptyText` | `null` | Shown in place of the menu when none is valid. `null` shows nothing. |
| `note` | `null` | A line under the dishes, for example about prices or allergens. |
| `language` | site language | `de` or `en`, for the snippet and Panel texts. Otherwise the default language, then the first two letters of `locale`, then English. |

## Content field contract

The Panel tab writes two fields. Their names are a compatibility promise: sites that already store these field names keep working unchanged, so their content needs no migration.

| Field | Type | Meaning |
|---|---|---|
| `tageskarteDatum` | date | "Gilt am", in mode `until` "Anzeigen bis": the day the menu is valid on, or with `dateMode` `until` the last day it is shown. Empty: nothing is shown. |
| `tagesgerichte` | structure, max. 5 | The dishes (see below). |

Dish fields:

| Field | Type | Meaning |
|---|---|---|
| `gericht` | text, required, max. 160 | The dish. |
| `beschreibung` | textarea, max. 300 | Further lines, one per line break. |
| `preis` | number, 0 to 999, step 0.01 | Optional. Empty shows the dish without a price. |
| `kennzeichnungen` | text, max. 40 | Optional markers, shown as written. |

Reading rules, the same for the Panel content and imported data: a dish without a name is dropped; a price that cannot be read (`abc`, `1000`, `12.345`) drops the dish rather than showing a wrong amount; a decimal comma and a decimal point both work; at most five dishes are shown, in order.

### Two date modes

- **`from`** (default): the date is the first day. The menu is shown on it and on the `days` − 1 days after it.
- **`until`**: the date is the last day. The menu is shown on every day up to and including it, with no first day, so a card entered "until Friday" is visible at once and disappears after Friday. `days` does not apply.

Switching the mode changes the meaning of the stored date, not the content: a site that moves from `from` to `until` has to re-enter the dates that should now mean "until".

### Why these fields

- **`gericht`, `preis`, `beschreibung`:** the same price check applies to every price. The extra text can be kept as one string or split into lines; Higawari splits it, because a line break in a Panel field is the way editors write a list of sauces or sides.
- **`kennzeichnungen`:** free text, because an allergen or marker list is a legal matter that the site should decide, not the package. A structured list (for example EU allergen numbers) would be a guess at that decision.
- **`tageskarteDatum`:** the day the menu is served, plus the window (`days`), so a menu can be shown for more than one day. An undated menu that stays visible until it is removed is deliberately not supported: a daily menu without a date would show last week's dishes.
- **Five dishes:** the cap is five, both in the code and in the blueprint, so a larger blueprint cannot show more dishes than the code does.
- **Price format:** `7,50 €` or `7,50`, depending on whether the Panel shows the unit. The `currency` option covers both.

### Left out on purpose

- **Weekend and closing-day rules.** The menu logic has none. Opening hours are another package's job ([Noren](https://github.com/sayamaapps/kirby-noren)).
- **Chat, bots and write endpoints.** Changes by chat are Fude's job.
- **"Tageskarte leeren" as a Panel button.** The clearing itself is here (see below). The button needs a dialog in the site's own Panel area, which only the site can define.
- **The picture beside the menu**, and its layout. Those are site design.

## Blueprints

Add the tab to a page blueprint with `extends`:

```yaml
# site/blueprints/pages/home.yml
tabs:
  tageskarte:
    extends: higawari/tabs/daily-menu
```

Pieces for your own blueprints: `higawari/tabs/daily-menu` (the tab), `higawari/fields/date` and `higawari/fields/dishes`. The tab and the date show the texts of the configured `dateMode`, so the same blueprint works in both modes.

## Snippet

```php
// The page that renders the template:
snippet('higawari');

// Another page, and a fixed clock (a DateTimeImmutable, for tests and previews):
snippet('higawari', ['source' => $site->homePage(), 'now' => $customNow]);
```

Markup, with classes only and no inline style:

```html
<section class="higawari" aria-labelledby="higawari-titel">
  <h2 class="higawari-titel" id="higawari-titel">Tageskarte</h2>
  <p class="higawari-gilt"><time datetime="2026-10-10">Samstag, 10.10.2026</time></p>
  <ul class="higawari-gerichte">
    <li class="higawari-gericht">
      <span class="higawari-name">Kartoffelsuppe</span>
      <span class="higawari-preis">6,90 €</span>
      <p class="higawari-beschreibung">mit Brot<br>und Kräutern</p>
      <p class="higawari-kennzeichnungen">Kennzeichnungen: V, L</p>
    </li>
  </ul>
  <p class="higawari-hinweis">…</p>        <!-- only with the note option -->
</section>
```

With `dateMode` `until`, the line reads `Bis einschließlich Samstag, 10.10.2026` (English: `Until Saturday, 10 October 2026`), and its `datetime` is the last day.

With nothing valid, the snippet prints nothing, or only `<p class="higawari-leer">` with the `emptyText` option. Every value is escaped. The page is never cached by Kirby, because the menu depends on the clock. To change the markup, create `site/snippets/higawari.php`; Kirby uses it instead.

No CSS is shipped. The classes are the hooks for your own stylesheet.

## Page method

```php
$card = $page->higawariCard();                    // now, in the configured zone
// [
//     'from' => '2026-10-10', 'to' => '2026-10-10',   // mode until: from is today, to the date
//     'dishes' => [
//         ['name' => 'Kartoffelsuppe', 'lines' => ['mit Brot', 'und Kräutern'], 'price' => '6,90 €', 'markers' => 'V, L'],
//     ],
// ]
// or null: plugin off, not valid that day, or no usable dish
```

## Clearing

```php
use Sayamaapps\Higawari\Menu;

$page->update(Menu::CLEARED);   // all dishes and the date, in one step
```

`Higawari\Menu::CLEARED` (1.x) is the same constant.

`CLEARED` empties both fields together. Keeping the date would hide a menu entered the next day, because the old date has already passed.

## Fude

The dishes field carries a `fude:` marker, so a site with Fude 1.4 or later gets the menu as a chat collection without a line in `site/config/fude.php`: a bot may replace the whole menu, update or delete a dish (at most five), and every change sets `tageskarteDatum`. That is today, unless the chat names another day ("the menu for tomorrow"); the marker sets `editable`, Fude refuses a past day. The bot learns what the date means from the field's label and help text. The marker sets `readImages`, so the chat side may read a photo of the blackboard as the new menu; the dishes on it still arrive as a draft that someone confirms. `touch` stamps the day in Fude's `fude.timezone` (default Europe/Berlin). Without Fude, Kirby ignores the marker.

| | Dish |
|---|---|
| **Required** | `gericht` |
| **Asked** | — (the menu comes as one message or a photo; asking every day would slow it down) |
| **Free** | `beschreibung`, `preis`, `kennzeichnungen` |

- In mode `until` a change by chat without a named day sets the date to today, so the menu is shown until today only. The bot names the last day ("bis Freitag").
- The collection is named `<page id>-tagesgerichte`, for example `home-tagesgerichte`.
- The page needs both fields, as the tab brings them. A blueprint that extends only `higawari/fields/dishes` makes every Fude route fail, because the date to stamp is missing.
- To switch it off, or to change it, use Fude's overrides in `site/config/fude.php`: `'home-tagesgerichte' => false` removes the collection, an array under the same name replaces it.

## Privacy

Higawari processes no visitor data: it sets no cookies, uses no browser storage and makes no request to a third party. There is nothing to add to a site's privacy policy.

## Deprecations

Kept until 2.0. Nothing breaks in 1.x; a site moves at its own pace.

| 1.x | Now | Note |
|---|---|---|
| `higawari.<name>` options (for example `'higawari.active' => true`) | `sayamaapps.higawari.<name>` | Read as a fallback. With Kirby's `debug` option on, a deprecation is raised. The new spelling wins when both are set. |
| Classes `Higawari\Menu`, `Higawari\Options`, `Higawari\I18n`, `Higawari\Daily`, `Higawari\Panel` | `Sayamaapps\Higawari\…` with the same names | Class aliases in `legacy.php`; no code change needed. |

Also unchanged in 1.x: the snippet name, the blueprint names, the content field names and the page method `higawariCard()`.

## Development

```sh
composer install
composer test                     # PHPUnit
composer analyze:psalm            # Psalm, errorLevel 3
composer analyze:phpmd            # PHP Mess Detector
composer analyze:unused           # unused Composer packages
composer analyze:require-checker  # undeclared dependencies
composer normalize:check          # composer.json layout
```

Code style: `getkirby/php-cs`, installed globally because it needs PHP 8.3 or newer (`composer global require getkirby/php-cs:0.2.1`, then `kirby-cs fix --dry-run --diff`).

The Kirby tests boot a real Kirby 5 on a throwaway site and need no network connection. CI runs the tests on PHP 8.2 to 8.5 with the lowest and the highest dependency versions, and against the Kirby 6 pre-release as a warning.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT, see [LICENSE](LICENSE).
