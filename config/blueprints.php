<?php

use Kirby\Cms\App;
use Sayamaapps\Higawari\Panel;

// Blueprint pieces for `extends:` in a site's own blueprints. The date and the
// tab depend on sayamaapps.higawari.dateMode, so Kirby calls them with the app
// (see Sayamaapps\Higawari\Panel).
return [
	'higawari/fields/date' => function (App $kirby): array {
		return Panel::dateField($kirby);
	},
	'higawari/fields/dishes' => __DIR__ . '/../blueprints/fields/dishes.yml',
	'higawari/tabs/daily-menu' => function (App $kirby): array {
		return Panel::tab($kirby);
	},
];
