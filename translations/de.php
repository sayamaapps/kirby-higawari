<?php

/**
 * Deutsche Texte. Gleiche Schlüssel und Platzhalter wie translations/en.php
 * (tests/I18nTest.php prüft das).
 */
return [
	// Wochentage, ISO-8601 (1 = Montag).
	'day.1' => 'Montag', 'day.2' => 'Dienstag', 'day.3' => 'Mittwoch', 'day.4' => 'Donnerstag',
	'day.5' => 'Freitag', 'day.6' => 'Samstag', 'day.7' => 'Sonntag',

	// PHP-date()-Format nach dem Wochentag.
	'format.date' => 'd.m.Y',
	// Uhrzeit neben einem Gericht: volle Stunde ohne Minuten („15 Uhr“), sonst „14:30 Uhr“.
	'format.time' => '{hour}:{minute}',
	'format.time.hour' => '{hour}',

	// Texte der Anzeige.
	'snippet.heading' => 'Tageskarte',
	'snippet.range' => '{from} – {to}',
	'snippet.markers' => 'Kennzeichnungen: {markers}',
	'snippet.until' => 'Bis einschließlich {date}',
	'snippet.time.until' => 'bis {time} Uhr',
	'snippet.time.from' => 'ab {time} Uhr',

	// Texte im Panel.
	'panel.tab.label' => 'Tageskarte',
	'panel.info.text' => 'Datum wählen, Gerichte eintragen, speichern. Die Karte wird an diesem Tag gezeigt '
		. '(oder an so vielen Tagen, wie die Website festlegt). Ohne Datum erscheint keine Karte. '
		. '„Tageskarte leeren“ entfernt alle Gerichte und das Datum auf einmal.',
	'panel.info.until.text' => 'Datum wählen, Gerichte eintragen, speichern. Die Karte wird bis einschließlich diesem Tag gezeigt. '
		. 'Ohne Datum erscheint keine Karte. „Tageskarte leeren“ entfernt alle Gerichte und das Datum auf einmal.',
	'panel.date.label' => 'Gilt am',
	'panel.date.help' => 'Der Tag, an dem die Gerichte serviert werden. Leer: es wird nichts gezeigt.',
	'panel.date.until.label' => 'Anzeigen bis',
	'panel.date.until.help' => 'Der letzte Tag, an dem die Gerichte gezeigt werden, dieser Tag eingeschlossen. Leer: es wird nichts gezeigt.',
	'panel.dishes.label' => 'Gerichte',
	'panel.dish.name.label' => 'Gericht',
	'panel.dish.description.label' => 'Weitere Zeilen (optional)',
	'panel.dish.description.help' => 'Eine Zeile je Angabe, etwa Soßen oder Beilagen.',
	'panel.dish.price.label' => 'Preis in Euro (optional)',
	'panel.dish.price.help' => 'Leer lassen, dann erscheint das Gericht ohne Preis.',
	'panel.dish.variant.label' => 'Variante (optional)',
	'panel.dish.variant.help' => 'Eine zweite Ausführung mit eigenem Preis, etwa „mit Wiener“ oder „kleine Portion“. '
		. 'Erscheint als eigene Zeile unter dem Gericht; der Preis oben gilt ohne die Variante.',
	'panel.dish.variant.price.label' => 'Preis der Variante in Euro (optional)',
	'panel.dish.variant.price.help' => 'Wird nur zusammen mit einer Variante gezeigt.',
	'panel.dish.markers.label' => 'Kennzeichnungen (optional)',
	'panel.dish.markers.help' => 'Freier Text, wird so gezeigt, etwa „V, L“.',
	'panel.dish.time.label' => 'Erhältlich',
	'panel.dish.time.help' => 'Ganztags: den ganzen Tag. Bis: das Gericht verschwindet zur Uhrzeit, etwa ein Mittagsgericht bis 15 Uhr. '
		. 'Ab: das Gericht steht den ganzen Tag da, mit dem Hinweis „ab … Uhr“.',
	'panel.dish.clock.label' => 'Uhr',
	'panel.dish.clock.help' => 'Gilt nur bei „Bis“ oder „Ab“, bei „Ganztags“ egal.',
	'panel.note.label' => 'Hinweis unter der Karte (optional)',
	'panel.note.help' => 'Eine Zeile unter den Gerichten, etwa „Di–Fr bis 15 Uhr: Mittagsgericht 9,90 €“. Bleibt stehen, '
		. 'wenn die Karte wechselt. Leer: keine Zeile.',
];
