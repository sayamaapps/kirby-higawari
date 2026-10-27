<?php

/**
 * English strings: the default language.
 * `{name}` placeholders are filled in by Sayamaapps\Higawari\I18n::translate().
 * tests/I18nTest.php checks that every language file has the same keys and
 * placeholders as this one. Keys are part of the contract: never rename one
 * without a CHANGELOG entry.
 */
return [
	// Weekdays, ISO-8601 numbering (1 = Monday).
	'day.1' => 'Monday', 'day.2' => 'Tuesday', 'day.3' => 'Wednesday', 'day.4' => 'Thursday',
	'day.5' => 'Friday', 'day.6' => 'Saturday', 'day.7' => 'Sunday',

	// PHP date() format after the weekday.
	'format.date' => 'j F Y',

	// Snippet texts.
	'snippet.heading' => 'Daily menu',
	'snippet.range' => '{from} – {to}',
	'snippet.markers' => 'Markers: {markers}',
	'snippet.until' => 'Until {date}',

	// Panel texts (blueprints use them as higawari.panel.*).
	'panel.tab.label' => 'Daily menu',
	'panel.info.text' => 'Pick the date and enter the dishes, then save. The menu is shown on that day only (or on as many days as the site sets). Without a date, no menu is shown. “Clear menu” at the top removes all dishes and the date at once.',
	'panel.info.until.text' => 'Pick the last day and enter the dishes, then save. The menu is shown up to and including that day. Without a date, no menu is shown. “Clear menu” at the top removes all dishes and the date at once.',
	'panel.date.label' => 'Valid on',
	'panel.date.help' => 'The day the dishes are served. Empty: nothing is shown.',
	'panel.date.until.label' => 'Shown until',
	'panel.date.until.help' => 'The last day the dishes are shown, that day included. Empty: nothing is shown.',
	'panel.dishes.label' => 'Dishes',
	'panel.dish.name.label' => 'Dish',
	'panel.dish.description.label' => 'Further lines (optional)',
	'panel.dish.description.help' => 'One line each, for example the sauces or sides.',
	'panel.dish.price.label' => 'Price in euros (optional)',
	'panel.dish.price.help' => 'Leave empty to show the dish without a price.',
	'panel.dish.markers.label' => 'Markers (optional)',
	'panel.dish.markers.help' => 'Free text, shown as written, for example “V, L”.',
];
