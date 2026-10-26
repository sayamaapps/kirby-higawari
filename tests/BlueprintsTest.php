<?php

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;


use Sayamaapps\Higawari\Menu;
use Kirby\Form\Form;

#[CoversClass(Sayamaapps\Higawari\Panel::class)]
#[RunTestsInSeparateProcesses]
final class BlueprintsTest extends TestCase
{
	protected function tearDown(): void
	{
		App::destroy();
	}

	public function testSuite(): void
	{
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

		// Mode until: the Panel says "Anzeigen bis" / "Shown until" with its own help, and the tab's info text follows.
		// Mode from (the default) keeps the texts above, also in the same process.
		higawari_with_site([], function (string $tmp) {
			$texts = [
				'de' => ['Anzeigen bis', 'Der letzte Tag, an dem die Gerichte gezeigt werden, dieser Tag eingeschlossen. Leer: es wird nichts gezeigt.', 'Datum wählen, Gerichte eintragen, speichern. Die Karte wird bis einschließlich diesem Tag gezeigt. Ohne Datum erscheint keine Karte. „Tageskarte leeren“ entfernt alle Gerichte und das Datum auf einmal.'],
				'en' => ['Shown until', 'The last day the dishes are shown, that day included. Empty: nothing is shown.', 'Pick the last day and enter the dishes, then save. The menu is shown up to and including that day. Without a date, no menu is shown. “Clear menu” at the top removes all dishes and the date at once.'],
			];
			foreach ($texts as $language => [$label, $help, $info]) {
				$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true, 'sayamaapps.higawari.dateMode' => 'until']);
				$kirby->setCurrentTranslation($language);
				$page = $kirby->page('home');

				$fields = Form::for($page, ['values' => []])->fields()->toArray();
				expect($label, $fields['tageskartedatum']['label'] ?? null, "until $language: date label");
				expect('<p>' . $help . '</p>', $fields['tageskartedatum']['help'] ?? null, "until $language: date help (Kirby wraps help in a paragraph)");
				expect('date', $fields['tageskartedatum']['type'] ?? null, "until $language: still a date field");
				expect('<p>' . $info . '</p>', $fields['higawariinfo']['text'] ?? null, "until $language: tab info text");

				// Fude translates the raw blueprint label itself, so the bot reads "Anzeigen bis" / "Shown until".
				$raw = $page->blueprint()->fields()['tageskarteDatum'];
				expect($label, \Kirby\Toolkit\I18n::translate($raw['label'], $raw['label'], $language), "until $language: label as Fude reads it");
			}

			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true]);
			$kirby->setCurrentTranslation('de');
			$fields = Form::for($kirby->page('home'), ['values' => []])->fields()->toArray();
			expect('Gilt am', $fields['tageskartedatum']['label'] ?? null, 'from: label back to "Gilt am" after an until run');
		});

		// What Fude reads: replace the whole menu, stamp the date, never more than Menu allows.
		higawari_with_site([], function (string $tmp) {
			$marker = higawari_kirby($tmp)->page('home')->blueprint()->fields()[Menu::FIELD_DISHES]['fude'] ?? null;
			expect(['replace', 'update', 'delete'], $marker['actions'] ?? null, 'Fude marker: replace, update, delete');
			expect(Menu::MAX_DISHES, $marker['maxEntries'] ?? null, 'Fude marker: at most Menu::MAX_DISHES');
			expect(['field' => Menu::FIELD_DATE, 'value' => 'today', 'editable' => true], $marker['touch'] ?? null, 'Fude marker: every change stamps the date field, the chat may name another day');
			expect(true, $marker['readImages'] ?? null, 'Fude marker: a photo of the menu may be read');
		});
	}
}
