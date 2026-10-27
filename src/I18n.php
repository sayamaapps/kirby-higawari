<?php

namespace Sayamaapps\Higawari;

use Kirby\Toolkit\I18n as KirbyI18n;

/**
 * Strings of the plugin. The texts live in translations/<language>.php and
 * config/translations.php registers them as Kirby plugin translations under
 * `sayamaapps.higawari.<key>`. This class reads them through Kirby's own
 * translation lookup.
 *
 * The language is set once per request (Options::applyLanguage()) and
 * defaults to English. A missing key gives the key itself; tests/I18nTest.php
 * checks that both language files have the same keys.
 *
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
class I18n
{
	/** Languages the plugin ships. English is the default. */
	public const LANGUAGES = ['en', 'de'];

	/** Prefix of the keys in Kirby's translations (config/translations.php). */
	private const PREFIX = 'sayamaapps.higawari.';

	private static string $language = 'en';

	/** @var array<string, array<string,string>> */
	private static array $tables = [];

	/** Full weekday name for an ISO weekday number (1 = Monday … 7 = Sunday). */
	public static function day(int $isoWeekday): string
	{
		return self::translate('day.' . $isoWeekday);
	}

	/** The language currently in use. */
	public static function language(): string
	{
		return self::$language;
	}

	/** Sets the language for this request. Unknown languages fall back to English. */
	public static function setLanguage(string $language): void
	{
		self::$language = in_array($language, self::LANGUAGES, true) ? $language : 'en';
	}

	/** @return array<string,string> */
	public static function table(string $language): array
	{
		if (!in_array($language, self::LANGUAGES, true)) {
			return [];
		}
		return self::$tables[$language] ??= require dirname(__DIR__) . '/translations/' . $language . '.php';
	}

	/**
	 * Kirby's translation of `sayamaapps.higawari.<key>` in the language set for
	 * this request. `{name}` placeholders are replaced with the given values,
	 * unescaped. A missing key gives the key itself.
	 *
	 * The placeholders are filled here and not with Kirby's template(): its
	 * query resolution starts an App when none is running.
	 *
	 * @param array<string, scalar> $values
	 */
	public static function translate(string $key, array $values = []): string
	{
		$text = KirbyI18n::translate(self::PREFIX . $key, $key, self::$language);
		if (!is_string($text)) {
			return $key;
		}
		if ($values === []) {
			return $text;
		}
		$pairs = [];
		foreach ($values as $name => $value) {
			$pairs['{' . $name . '}'] = (string)$value;
		}
		return strtr($text, $pairs);
	}
}
