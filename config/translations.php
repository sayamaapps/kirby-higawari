<?php

use Sayamaapps\Higawari\I18n;

// Every string under sayamaapps.higawari.<key>, as the blueprints and I18n read it.
// The short spelling higawari.<key> stays registered for sites that use it in their own blueprints.
$translations = [];
foreach (I18n::LANGUAGES as $language) {
	foreach (I18n::table($language) as $key => $text) {
		$translations[$language]['sayamaapps.higawari.' . $key] = $text;
		$translations[$language]['higawari.' . $key] = $text;
	}
}

return $translations;
