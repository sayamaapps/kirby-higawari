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

use Sayamaapps\Higawari\Daily;
use Sayamaapps\Higawari\I18n;
use Sayamaapps\Higawari\Menu;
use Sayamaapps\Higawari\Options;

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
	<h2 class="higawari-titel" id="higawari-titel"><?= esc(I18n::translate('snippet.heading')) ?></h2>
<?php if ($card !== null) : ?>
<?php
$datetime = $mode === Menu::MODE_UNTIL ? $card['to'] : $card['from'];
$label = Menu::label($card['from'], $card['to'], $mode);
?>
	<p class="higawari-gilt"><time datetime="<?= esc($datetime) ?>"><?= esc($label) ?></time></p>
	<ul class="higawari-gerichte">
<?php foreach ($card['dishes'] as $dish) : ?>
		<li class="higawari-gericht">
			<span class="higawari-name"><?= esc($dish['name']) ?></span>
<?php if ($dish['price'] !== '') : ?>
			<span class="higawari-preis"><?= esc($dish['price']) ?></span>
<?php endif ?>
<?php if ($dish['variant'] !== null) : ?>
			<span class="higawari-variante"><?= esc($dish['variant']['label']) ?></span>
<?php if ($dish['variant']['price'] !== '') : ?>
			<span class="higawari-variantenpreis"><?= esc($dish['variant']['price']) ?></span>
<?php endif ?>
<?php endif ?>
<?php if ($dish['lines'] !== []) : ?>
<?php $lines = array_map(static fn (string $line): string => esc($line), $dish['lines']) ?>
			<p class="higawari-beschreibung"><?= implode('<br>', $lines) ?></p>
<?php endif ?>
<?php if ($dish['markers'] !== '') : ?>
			<p class="higawari-kennzeichnungen"><?= esc(I18n::translate('snippet.markers', ['markers' => $dish['markers']])) ?></p>
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
