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

    // Texte der Anzeige.
    'snippet.heading' => 'Tageskarte',
    'snippet.range' => '{from} – {to}',
    'snippet.markers' => 'Kennzeichnungen: {markers}',

    // Texte im Panel.
    'panel.tab.label' => 'Tageskarte',
    'panel.info.text' => 'Datum wählen, Gerichte eintragen, speichern. Die Karte wird an diesem Tag gezeigt (oder an so vielen Tagen, wie die Website festlegt). Ohne Datum erscheint keine Karte. „Tageskarte leeren“ entfernt alle Gerichte und das Datum auf einmal.',
    'panel.date.label' => 'Gilt am',
    'panel.date.help' => 'Der Tag, an dem die Gerichte serviert werden. Leer: es wird nichts gezeigt.',
    'panel.dishes.label' => 'Gerichte',
    'panel.dish.name.label' => 'Gericht',
    'panel.dish.description.label' => 'Weitere Zeilen (optional)',
    'panel.dish.description.help' => 'Eine Zeile je Angabe, etwa Soßen oder Beilagen.',
    'panel.dish.price.label' => 'Preis in Euro (optional)',
    'panel.dish.price.help' => 'Leer lassen, dann erscheint das Gericht ohne Preis.',
    'panel.dish.markers.label' => 'Kennzeichnungen (optional)',
    'panel.dish.markers.help' => 'Freier Text, wird so gezeigt, etwa „V, L“.',
];
