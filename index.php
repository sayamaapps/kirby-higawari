<?php

use Kirby\Filesystem\F;

/**
 * Plugin registration for Higawari (日替わり, “changes daily”): the daily menu
 * for Kirby 5. Dishes with a validity date, shown on that day (or on N days
 * from it), plus a Panel tab and an unstyled snippet. Off by default: without
 * 'sayamaapps.higawari.active' => true the page method returns null and the
 * snippet prints nothing.
 *
 * Kirby loads this file. It registers the class map, the legacy aliases and
 * the definitions in config/. The logic lives in src/.
 *
 * Nothing here accepts input from outside the Panel. Changing a menu by chat
 * is the job of the separate package Fude, which reads the `fude:` marker in
 * blueprints/fields/dishes.yml.
 */
F::loadClasses(require __DIR__ . '/autoload.php', __DIR__);
require_once __DIR__ . '/legacy.php';

Kirby::plugin(
	name: 'sayamaapps/higawari',
	extends: [
		'options'      => require __DIR__ . '/config/options.php',
		'translations' => require __DIR__ . '/config/translations.php',
		'snippets'     => require __DIR__ . '/config/snippets.php',
		'blueprints'   => require __DIR__ . '/config/blueprints.php',
		'pageMethods'  => require __DIR__ . '/config/page-methods.php',
	],
	root: __DIR__
);
