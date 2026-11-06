<?php

namespace Sayamaapps\Higawari;

/**
 * The daily menu as schema.org data (README, JSON-LD). No App object is
 * needed; Daily::schema() passes the card and the texts.
 *
 * @package   Kirby Higawari
 * @author    Simon Räder
 * @link      https://github.com/sayamaapps/kirby-higawari
 * @copyright Simon Räder
 * @license   https://opensource.org/licenses/MIT
 */
class Schema
{
	/**
	 * schema.org `Menu` of a card (card()): one `MenuSection` named $section
	 * ("Tageskarte Samstag, 10.10.2026") with a `MenuItem` per dish. A dish's
	 * lines become its `description` (joined with ", "); its price an `Offer`
	 * with `price` as a decimal with a point and `priceCurrency` (ISO 4217,
	 * left out when $currencyCode is ''). A variant with its own price is a
	 * second `Offer` of the same item, named after the variant, so `offers` is
	 * then a list: one dish, two servings. A dish without a price has no
	 * `offers`. $id is the menu's `@id` ('' leaves it out).
	 *
	 * @param array{from: string, to: string, dishes: list<array{name: string, lines: list<string>, price: string, amount: string, markers: string, variant: array{label: string, price: string, amount: string}|null}>} $card
	 * @return array<string, mixed>
	 */
	public static function menu(array $card, string $name, string $section, string $currencyCode = 'EUR', string $id = ''): array
	{
		$items = [];
		foreach ($card['dishes'] as $dish) {
			$offers = [];
			if ($dish['amount'] !== '') {
				$offers[] = self::offer($dish['amount'], $currencyCode);
			}
			$variant = $dish['variant'];
			if ($variant !== null && $variant['amount'] !== '') {
				$offers[] = ['name' => $dish['name'] . ' ' . $variant['label']] + self::offer($variant['amount'], $currencyCode);
			}
			$item = ['@type' => 'MenuItem', 'name' => $dish['name']];
			if ($dish['lines'] !== []) {
				$item['description'] = implode(', ', $dish['lines']);
			}
			if ($offers !== []) {
				$item['offers'] = count($offers) === 1 ? $offers[0] : $offers;
			}
			$items[] = $item;
		}
		$menu = ['@type' => 'Menu'];
		if ($id !== '') {
			$menu['@id'] = $id;
		}
		return $menu + [
			'name' => $name,
			'hasMenuSection' => [
				'@type' => 'MenuSection',
				'name' => $section,
				'hasMenuItem' => $items,
			],
		];
	}

	/** @return array<string, string> */
	private static function offer(string $amount, string $currencyCode): array
	{
		$offer = ['@type' => 'Offer', 'price' => $amount];
		if ($currencyCode !== '') {
			$offer['priceCurrency'] = $currencyCode;
		}
		return $offer;
	}
}
