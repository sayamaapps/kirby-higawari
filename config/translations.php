<?php

use Sayamaapps\Higawari\I18n;

// Every string, also as blueprint labels: higawari.panel.* and higawari.snippet.*.
// I18n reads its own tables, so a missing Kirby translation never changes a text.
$translations = [];
foreach (I18n::LANGUAGES as $language) {
	foreach (I18n::table($language) as $key => $text) {
		$translations[$language]['higawari.' . $key] = $text;
	}
}

return $translations;
