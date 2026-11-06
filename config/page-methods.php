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

	/**
	 * Unix time until which this page's menu output stays the same, for
	 * kirby()->response()->expires(). See Daily::cacheUntil().
	 */
	'higawariCacheUntil' => function (\DateTimeImmutable|null $now = null): int {
		return Daily::cacheUntil(App::instance(), $this, $now);
	},

	/**
	 * The schema.org Menu of this page's card at $now, or null. See Daily::schema().
	 */
	'higawariSchema' => function (\DateTimeImmutable|null $now = null): array|null {
		return Daily::schema(App::instance(), $this, $now);
	},
];
