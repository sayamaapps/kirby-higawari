<?php

/**
 * Daily menu (Tageskarte): semantic HTML, classes higawari-* only, no script,
 * no request to another server. A site overrides it with
 * site/snippets/higawari.php (Kirby looks there first).
 *
 * Variables (all optional):
 *   $source  the page that holds the menu fields, default page()
 *   $now     DateTimeImmutable, default the clock in the configured zone
 *
 * Prints nothing when the plugin is off, or when no menu is valid and
 * sayamaapps.higawari.emptyText is not set.
 */

use Higawari\Daily;
use Higawari\I18n;
use Higawari\Menu;
use Higawari\Options;

if (!Options::active(kirby())) {
    return;
}
Options::applyLanguage(kirby());
$mode = Options::dateMode(kirby());

// The menu changes with the clock: Kirby's page cache must not keep this page,
// not even in the state where nothing is shown today.
kirby()->response()->cache(false);

$source ??= page();
$now ??= null;

$card = $source !== null ? Daily::card(kirby(), $source, $now) : null;
$empty = $card === null ? Options::emptyText(kirby()) : null;
if ($card === null && $empty === null) {
    return;
}
$note = Options::note(kirby());
?>
<section class="higawari" aria-labelledby="higawari-titel">
  <h2 class="higawari-titel" id="higawari-titel"><?= esc(I18n::t('snippet.heading')) ?></h2>
<?php if ($card !== null) : ?>
  <p class="higawari-gilt"><time datetime="<?= esc($mode === Menu::MODE_UNTIL ? $card['to'] : $card['from']) ?>"><?= esc(Menu::label($card['from'], $card['to'], $mode)) ?></time></p>
  <ul class="higawari-gerichte">
<?php foreach ($card['dishes'] as $dish) : ?>
    <li class="higawari-gericht">
      <span class="higawari-name"><?= esc($dish['name']) ?></span>
<?php if ($dish['price'] !== '') : ?>
      <span class="higawari-preis"><?= esc($dish['price']) ?></span>
<?php endif ?>
<?php if ($dish['lines'] !== []) : ?>
      <p class="higawari-beschreibung"><?php foreach ($dish['lines'] as $index => $line) : ?><?= $index > 0 ? '<br>' : '' ?><?= esc($line) ?><?php endforeach ?></p>
<?php endif ?>
<?php if ($dish['markers'] !== '') : ?>
      <p class="higawari-kennzeichnungen"><?= esc(I18n::t('snippet.markers', ['markers' => $dish['markers']])) ?></p>
<?php endif ?>
    </li>
<?php endforeach ?>
  </ul>
<?php if ($note !== null) : ?>
  <p class="higawari-hinweis"><?= esc($note) ?></p>
<?php endif ?>
<?php else : ?>
  <p class="higawari-leer"><?= esc($empty) ?></p>
<?php endif ?>
</section>
