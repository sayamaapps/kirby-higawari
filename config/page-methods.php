<?php

use Kirby\Cms\App;
use Sayamaapps\Higawari\Daily;

return [
	/**
	 * The menu of this page at $now, or null (plugin off, not valid that
	 * day, no usable dish). See Sayamaapps\Higawari\Daily::card().
	 */
	'higawariCard' => function (\DateTimeImmutable|null $now = null): array|null {
		return Daily::card(App::instance(), $this, $now);
	},
];
