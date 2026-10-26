<?php

namespace Sayamaapps\Higawari;

use Kirby\Cms\App;
use Kirby\Data\Data;

/**
 * The Panel blueprints that depend on `sayamaapps.higawari.dateMode`. Kirby
 * calls them with the app (index.php registers them as callbacks), so the
 * label and help of the date say what the date means: "Gilt am" in mode
 * `from`, "Anzeigen bis" in mode `until`. The Fude marker reads the same
 * label and help, so the bot learns the meaning from the Panel's own words.
 *
 * The YAML files stay the single source of the blueprint; only these keys
 * change with the mode.
 */
final class Panel
{
	/** The date field `tageskarteDatum` for the configured mode. */
	public static function dateField(App $kirby): array
	{
		$field = Data::read(dirname(__DIR__) . '/blueprints/fields/date.yml');
		if (Options::dateMode($kirby) === Menu::MODE_UNTIL) {
			$field['label'] = 'higawari.panel.date.until.label';
			$field['help'] = 'higawari.panel.date.until.help';
		}
		return $field;
	}

	/** The Tageskarte tab for the configured mode: only its info text differs. */
	public static function tab(App $kirby): array
	{
		$tab = Data::read(dirname(__DIR__) . '/blueprints/tabs/daily-menu.yml');
		if (Options::dateMode($kirby) === Menu::MODE_UNTIL) {
			$tab['fields']['higawariInfo']['text'] = 'higawari.panel.info.until.text';
		}
		return $tab;
	}
}
