<?php

/**
 * The daily menu as JSON-LD: one <script type="application/ld+json"> with a
 * schema.org Menu (Daily::schema()), only while a card is valid. Put it into
 * the <head> or the body of the page that shows the menu.
 *
 * Variables (all optional):
 *   $source  the page that holds the menu fields, default page()
 *   $now     DateTimeImmutable, default the clock in the configured zone
 *
 * With sayamaapps.higawari.cache on, the page may stay in Kirby's page cache
 * until the menu changes with the date (Daily::cacheUntil()); otherwise it is
 * kept out of the cache, like with the menu snippet.
 */

use Sayamaapps\Higawari\Daily;
use Sayamaapps\Higawari\Options;

if (!Options::active(kirby())) {
	return;
}
$source ??= page();
$now ??= null;
Daily::cacheResponse(kirby(), $source, $now);
if ($source === null) {
	return;
}
$menu = Daily::schema(kirby(), $source, $now);
if ($menu === null) {
	return;
}
$json = json_encode(['@context' => 'https://schema.org'] + $menu, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) {
	return;
}
?>
<script type="application/ld+json"><?= $json ?></script>
