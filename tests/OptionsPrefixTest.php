<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sayamaapps\Higawari\Options;

/**
 * Options are read as sayamaapps.higawari.<key>, flat or nested, as in 1.x.
 * The short spelling higawari.<key> is not read and raises nothing.
 */
#[CoversClass(Options::class)]
final class OptionsPrefixTest extends TestCase
{
	public function testFlatSpellingSwitchesTheMenuOn(): void
	{
		higawari_with_site([], function (string $tmp): void {
			$kirby = higawari_kirby($tmp, ['sayamaapps.higawari.active' => true]);
			expect(true, Options::active($kirby), 'sayamaapps.higawari.active is read');
		});
	}

	public function testNestedSpellingSwitchesTheMenuOn(): void
	{
		higawari_with_site([], function (string $tmp): void {
			$kirby = higawari_kirby($tmp, ['sayamaapps' => ['higawari' => ['active' => true, 'days' => 3]]]);
			expect(true, Options::active($kirby), 'sayamaapps.higawari.active is read when nested');
			expect(3, Options::days($kirby), 'sayamaapps.higawari.days is read when nested');
		});
	}

	public function testShortSpellingIsNotRead(): void
	{
		$deprecations = [];
		set_error_handler(function (int $level, string $message) use (&$deprecations): bool {
			$deprecations[] = $message;
			return true;
		}, E_USER_DEPRECATED);

		try {
			higawari_with_site([], function (string $tmp): void {
				// Kirby reports deprecations in debug mode only; nothing is reported here.
				$kirby = higawari_kirby($tmp, ['debug' => true, 'higawari.active' => true]);
				expect(false, Options::active($kirby), 'higawari.active is not read');
			});
		} finally {
			restore_error_handler();
		}

		expect([], $deprecations, 'the short spelling raises no deprecation');
	}

	public function testUnsetOptionsGiveTheDefaults(): void
	{
		higawari_with_site([], function (string $tmp): void {
			$kirby = higawari_kirby($tmp);
			expect(false, Options::active($kirby), 'unset: menu off');
			expect(1, Options::days($kirby), 'unset: one day');
			expect('Europe/Berlin', Options::timezone($kirby), 'unset: Europe/Berlin');
			expect('€', Options::currency($kirby), 'unset: euro sign');
		});
	}
}
