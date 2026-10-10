<?php

use Higawari\Daily;
use Higawari\I18n;
use Kirby\Cms\App;

require_once __DIR__ . '/src/I18n.php';
require_once __DIR__ . '/src/Menu.php';
require_once __DIR__ . '/src/Options.php';
require_once __DIR__ . '/src/Daily.php';

/**
 * Higawari (日替わり, “changes daily”): the daily menu for Kirby 5. Dishes with
 * a validity date, shown on that day (or on N days from it), plus a Panel tab
 * and an unstyled snippet. Off by default: without
 * 'sayamaapps.higawari.active' => true the page method returns null and the
 * snippet prints nothing.
 *
 * Nothing here accepts input from outside the Panel. Changing a menu by chat
 * is the job of the separate package Fude (see examples/fude.php).
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

    // Blueprint pieces for `extends:` in a site's own blueprints.
    'blueprints' => [
        'higawari/fields/date'       => __DIR__ . '/blueprints/fields/date.yml',
        'higawari/fields/dishes'     => __DIR__ . '/blueprints/fields/dishes.yml',
        'higawari/tabs/daily-menu'   => __DIR__ . '/blueprints/tabs/daily-menu.yml',
    ],

    'pageMethods' => [
        /**
         * The menu of this page at $now, or null (plugin off, not valid that
         * day, no usable dish). See Higawari\Daily::card().
         */
        'higawariCard' => function (?DateTimeImmutable $now = null): ?array {
            return Daily::card(App::instance(), $this, $now);
        },
    ],
]);
unset($higawariTranslations);
