<?php

namespace Sayamaapps\Higawari;

/**
 * Strings of the plugin, Kirby-free. The tables live in translations/<language>.php
 * and are also registered as Kirby plugin translations under `higawari.<key>`
 * (index.php), so blueprint labels can use them. The snippet reads its own
 * tables, so a missing Kirby translation never turns a German menu English.
 *
 * The language is set once per request (Options::applyLanguage()) and
 * defaults to English. Missing keys fall back to English, then to the key.
 *
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
class I18n
{
	/** Languages the plugin ships. English is the default and the fallback. */
	public const LANGUAGES = ['en', 'de'];

	private static string $language = 'en';

	/** @var array<string, array<string,string>> */
	private static array $tables = [];

	/** Full weekday name for an ISO weekday number (1 = Monday … 7 = Sunday). */
	public static function day(int $isoWeekday): string
	{
		return self::t('day.' . $isoWeekday);
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

	/** `{name}` placeholders are replaced with the given values, unescaped. */
	public static function t(string $key, array $values = []): string
	{
		$text = self::table(self::$language)[$key] ?? self::table('en')[$key] ?? $key;
		if ($values === []) {
			return $text;
		}
		$pairs = [];
		foreach ($values as $name => $value) {
			$pairs['{' . $name . '}'] = (string)$value;
		}
		return strtr($text, $pairs);
	}

	/** @return array<string,string> */
	public static function table(string $language): array
	{
		if (!in_array($language, self::LANGUAGES, true)) {
			return [];
		}
		return self::$tables[$language] ??= require dirname(__DIR__) . '/translations/' . $language . '.php';
	}
}
