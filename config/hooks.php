<?php

use Kirby\Cms\App;
use Sayamaapps\Higawari\Daily;
use Sayamaapps\Higawari\Options;

return [
	/**
	 * Annai (sayamaapps/kirby-annai, optional) asks plugins for properties of
	 * the business node. Higawari adds today's menu as `hasMenu`. Without Annai
	 * nobody triggers this hook; Higawari does not depend on it.
	 */
	'sayamaapps.annai.business' => function (array $fields): array {
		$kirby = App::instance();
		return Options::active($kirby) ? Daily::annai($kirby, $fields) : $fields;
	},
];
