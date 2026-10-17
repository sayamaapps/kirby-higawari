<?php
/**
 * The Panel tab loads through a site's blueprint and the Panel form: the
 * fields, the five-dish limit, and German and English labels. No `higawari.`
 * key reaches a form. The `fude:` marker for the separate package Fude names
 * the same date field and limit as Menu. Needs vendor/ (composer install).
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../src/I18n.php';
require_once __DIR__ . '/../src/Menu.php';

use Higawari\Menu;
use Kirby\Form\Form;

/** Every string in a nested array that starts with `higawari.`: an untranslated key. */
function higawari_leaks(mixed $value): array
{
    $found = [];
    $walk = function (mixed $node, string $path) use (&$walk, &$found): void {
        if (is_array($node)) {
            foreach ($node as $key => $child) {
                if (is_string($key) && str_starts_with($key, 'higawari.')) {
                    $found[] = $path . '.' . $key;
                }
                $walk($child, $path . '.' . $key);
            }
            return;
        }
        if (is_string($node) && str_starts_with($node, 'higawari.')) {
            $found[] = $path;
        }
    };
    $walk($value, '');
    return $found;
}

higawari_with_site([], function (string $tmp) {
    $texts = [
        'de' => ['Tageskarte', 'Gilt am', 'Gerichte', 'Gericht', 'Kennzeichnungen (optional)'],
        'en' => ['Daily menu', 'Valid on', 'Dishes', 'Dish', 'Markers (optional)'],
    ];
    foreach ($texts as $language => [$tab, $date, $dishes, $dish, $markers]) {
        $kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true]);
        $kirby->setCurrentTranslation($language);
        $page = $kirby->page('home');

        // Kirby lowercases field names in the form.
        expect([$tab], array_column($page->blueprint()->tabs(), 'label'), "$language: tab label");
        $fields = Form::for($page, ['values' => []])->fields()->toArray();

        expect($date, $fields['tageskartedatum']['label'] ?? null, "$language: date label");
        expect('date', $fields['tageskartedatum']['type'] ?? null, "$language: date is a date field");
        expect($dishes, $fields['tagesgerichte']['label'] ?? null, "$language: dishes label");
        expect(5, $fields['tagesgerichte']['max'] ?? null, "$language: at most five dishes");
        expect(['gericht', 'beschreibung', 'preis', 'kennzeichnungen'], array_keys($fields['tagesgerichte']['fields'] ?? []), "$language: dish fields");
        expect($dish, $fields['tagesgerichte']['fields']['gericht']['label'] ?? null, "$language: name label");
        expect($markers, $fields['tagesgerichte']['fields']['kennzeichnungen']['label'] ?? null, "$language: markers label");
        expect([], higawari_leaks(array_intersect_key($fields, array_flip(['tageskartedatum', 'tagesgerichte', 'higawariinfo']))), "$language: no untranslated key in the tab");
    }
});

// What Fude reads: replace the whole menu, stamp the date, never more than Menu allows.
higawari_with_site([], function (string $tmp) {
    $marker = higawari_kirby($tmp)->page('home')->blueprint()->fields()[Menu::FIELD_DISHES]['fude'] ?? null;
    expect(['replace', 'update', 'delete'], $marker['actions'] ?? null, 'Fude marker: replace, update, delete');
    expect(Menu::MAX_DISHES, $marker['maxEntries'] ?? null, 'Fude marker: at most Menu::MAX_DISHES');
    expect(['field' => Menu::FIELD_DATE, 'value' => 'today'], $marker['touch'] ?? null, 'Fude marker: every change stamps the date field');
});

finish('Higawari Blueprints');
