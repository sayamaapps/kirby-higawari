<?php

/**
 * Example declaration for the Fude package (site/config/fude.php): lets a bot
 * replace, update and delete the daily menu, and sets the date to today on
 * every change. Higawari itself has no chat or write endpoint; this is only the
 * declaration Fude reads.
 *
 * The keys `actions`, `maxEntries` and `touch` and the field type `number`
 * are documented in Fude's README (Unreleased in its CHANGELOG). `touch`
 * stamps the day in Fude's option `fude.timezone` (default Europe/Berlin),
 * not PHP's time zone — set it if the restaurant is elsewhere.
 *
 * Merge the collection into the `collections` array of site/config/fude.php.
 */

return [
    'collections' => [
        'tageskarte' => [
            'kind'       => 'structure',
            'page'       => 'home',                  // the page that holds the menu
            'field'      => 'tagesgerichte',         // Higawari\Menu::FIELD_DISHES
            'key'        => 'gericht',               // identifies a dish for update and delete
            'label'      => 'Tageskarte',
            'description' => 'Today’s dishes. Each change replaces the whole menu.',
            'actions'    => ['replace', 'update', 'delete'],
            'maxEntries' => 5,                       // Higawari\Menu::MAX_DISHES
            'touch'      => ['field' => 'tageskarteDatum', 'value' => 'today'],
            'fields'     => [
                'gericht'          => ['type' => 'text', 'required' => true, 'maxlength' => 160],
                'beschreibung'     => ['type' => 'textarea', 'maxlength' => 300],
                // Decimal comma or point; min 0 and max 999 come from the blueprint.
                'preis'            => ['type' => 'number'],
                'kennzeichnungen'  => ['type' => 'text', 'maxlength' => 40],
            ],
        ],
    ],
];
