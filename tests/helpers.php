<?php

/**
 * Shared test helpers, loaded by tests/bootstrap.php.
 *
 * expect() and holds() are one PHPUnit assertion each, so a failing check
 * ends the test and PHPUnit prints the label. Kirby fixtures boot a throwaway
 * site with the package symlinked as site/plugins/higawari.
 */

use Kirby\Cms\App;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use PHPUnit\Framework\Assert;

/** Strict comparison ($expected === $actual). */
function expect(mixed $expected, mixed $actual, string $what): void
{
	Assert::assertSame($expected, $actual, $what);
}

function holds(bool $ok, string $what): void
{
	Assert::assertTrue($ok, $what);
}

/**
 * Tests without an App (I18n, Menu) read the plugin's texts through Kirby's
 * translation loader, with the same array index.php registers.
 */
function higawari_texts_without_app(): void
{
	Kirby\Toolkit\I18n::$load = static fn (string $locale): array => (require dirname(__DIR__) . '/config/translations.php')[$locale] ?? [];
}

/** The callable must throw an instance of $class. */
function throws(string $class, callable $fn, string $what): void
{
	try {
		$fn();
	} catch (Throwable $e) {
		holds($e instanceof $class, $what . ': threw ' . $e::class . ' instead of ' . $class);
		return;
	}
	holds(false, $what . ': nothing thrown, expected ' . $class);
}

/** A moment in Europe/Berlin (or another zone): at('2026-10-10 12:00'). */
function at(string $when, string $zone = 'Europe/Berlin'): DateTimeImmutable
{
	return new DateTimeImmutable($when, new DateTimeZone($zone));
}

/* --------------------------------------------------------------------------
 * Kirby fixtures
 * -------------------------------------------------------------------------- */

const HIGAWARI_HOME_BLUEPRINT = "title: Startseite\ntabs:\n  tageskarte:\n    extends: higawari/tabs/daily-menu\n";

/**
 * A throwaway site with the package symlinked as site/plugins/higawari, a
 * home page with $fields (structure values are YAML-encoded the way the Panel
 * stores them) and a home blueprint with the Tageskarte tab. Removed after $run.
 */
function higawari_with_site(array $fields, callable $run): void
{
	$base = dirname(__DIR__);
	$tmp = sys_get_temp_dir() . '/higawari-kirby-' . bin2hex(random_bytes(6));
	foreach (['public', 'content/home', 'site/plugins', 'site/templates', 'site/blueprints/pages', 'storage'] as $dir) {
		mkdir($tmp . '/' . $dir, 0700, true);
	}
	symlink($base, $tmp . '/site/plugins/higawari');
	file_put_contents($tmp . '/site/templates/default.php', 'Fixture');
	file_put_contents($tmp . '/site/blueprints/pages/home.yml', HIGAWARI_HOME_BLUEPRINT);
	$blocks = ['Title: Home'];
	foreach ($fields as $name => $value) {
		$blocks[] = ucfirst($name) . ': ' . (is_array($value) ? "\n\n" . Yaml::encode($value) : (string)$value);
	}
	file_put_contents($tmp . '/content/home/home.txt', implode("\n\n----\n\n", $blocks) . "\n");
	try {
		$run($tmp);
	} finally {
		Dir::remove($tmp);
	}
}

/** A fresh Kirby on a higawari_with_site() directory. Options are passed as they are. */
function higawari_kirby(string $tmp, array $options = []): App
{
	\Kirby\Template\Snippet::$cache = [];
	// Kirby keeps loaded blueprints per process; the Panel blueprints depend on the options.
	\Kirby\Cms\Blueprint::$loaded = [];
	// The snippet and the page method read kirby() (App::instance()), so the new app must be the current one.
	// App::destroy() is not used: Kirby includes a plugin's index.php once per process, so a destroyed plugin registry stays empty.
	return App::instance(new App([
		'roots' => [
			'base' => $tmp,
			'index' => $tmp . '/public',
			'content' => $tmp . '/content',
			'site' => $tmp . '/site',
			'storage' => $tmp . '/storage',
		],
		'options' => ['url' => 'http://example.invalid'] + $options,
	]));
}
