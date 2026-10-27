<?php

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The class map in autoload.php matches src/: one entry per file, and every
 * entry loads its class. The 1.x names are aliases of the new classes.
 */
#[CoversNothing]
final class AutoloadTest extends TestCase
{
	public function testEveryEntryLoadsItsClass(): void
	{
		$root = dirname(__DIR__);
		foreach (require $root . '/autoload.php' as $class => $path) {
			$this->assertFileExists($root . '/' . $path);
			$this->assertTrue(class_exists($class), 'class ' . $class . ' loads through the map');
		}
	}

	public function testLegacyNamesAreAliasesOfTheNewClasses(): void
	{
		require_once dirname(__DIR__) . '/legacy.php';

		foreach (require dirname(__DIR__) . '/autoload.php' as $class => $path) {
			$short = substr($class, strlen('sayamaapps\\higawari\\'));
			$this->assertSame(
				strtolower('Sayamaapps\\Higawari\\' . $short),
				strtolower((new ReflectionClass('Higawari\\' . $short))->getName()),
				'Higawari\\' . $short . ' is an alias of the new class'
			);
		}
	}

	public function testMapListsEveryClassFileAndNothingElse(): void
	{
		$root = dirname(__DIR__);
		$expected = [];
		foreach (glob($root . '/src/*.php') ?: [] as $file) {
			$expected[strtolower('sayamaapps\\higawari\\' . basename($file, '.php'))] = 'src/' . basename($file);
		}

		$this->assertSame($expected, require $root . '/autoload.php');
	}
}
