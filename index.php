<?php

use Higawari\Daily;
use Higawari\I18n;
use Higawari\Panel;
use Kirby\Cms\App;

require_once __DIR__ . '/src/I18n.php';
require_once __DIR__ . '/src/Menu.php';
require_once __DIR__ . '/src/Options.php';
require_once __DIR__ . '/src/Daily.php';
require_once __DIR__ . '/src/Panel.php';

/**
 * Higawari (日替わり, “changes daily”): the daily menu for Kirby 5. Dishes with
 * a validity date, shown on that day (or on N days from it), plus a Panel tab
 * and an unstyled snippet. Off by default: without
 * 'sayamaapps.higawari.active' => true the page method returns null and the
 * snippet prints nothing.
 *
 * Nothing here accepts input from outside the Panel. Changing a menu by chat
 * is the job of the separate package Fude, which reads the `fude:` marker in
 * blueprints/fields/dishes.yml.
 */
$higawariTranslations = [];
foreach (I18n::LANGUAGES as $higawariLanguage) {
	foreach (I18n::table($higawariLanguage) as $higawariKey => $higawariText) {
		$higawariTranslations[$higawariLanguage]['higawari.' . $higawariKey] = $higawariText;
	}
}
unset($higawariLanguage, $higawariKey, $higawariText);

App::plugin('sayamaapps/higawari', [
	// All strings, also as blueprint labels: higawari.panel.* and higawari.snippet.*.
	'translations' => $higawariTranslations,

	'snippets' => [
		'higawari' => __DIR__ . '/snippets/higawari.php',
	],

	// Blueprint pieces for `extends:` in a site's own blueprints. The date and
	// the tab depend on sayamaapps.higawari.dateMode, so Kirby calls them with
	// the app (see Higawari\Panel).
	'blueprints' => [
		'higawari/fields/date'       => fn (App $kirby): array => Panel::dateField($kirby),
		'higawari/fields/dishes'     => __DIR__ . '/blueprints/fields/dishes.yml',
		'higawari/tabs/daily-menu'   => fn (App $kirby): array => Panel::tab($kirby),
	],

	'pageMethods' => [
		/**
		 * The menu of this page at $now, or null (plugin off, not valid that
		 * day, no usable dish). See Higawari\Daily::card().
		 */
		'higawariCard' => function (DateTimeImmutable|null $now = null): array|null {
			return Daily::card(App::instance(), $this, $now);
		},
	],
]);
unset($higawariTranslations);
