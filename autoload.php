<?php

/**
 * Class map for Kirby's F::loadClasses(). Keys are lowercase class names,
 * values are paths relative to the plugin root. tests/AutoloadTest.php checks
 * the map against src/.
 */

return [
	'sayamaapps\\higawari\\daily' => 'src/Daily.php',
	'sayamaapps\\higawari\\i18n' => 'src/I18n.php',
	'sayamaapps\\higawari\\menu' => 'src/Menu.php',
	'sayamaapps\\higawari\\options' => 'src/Options.php',
	'sayamaapps\\higawari\\panel' => 'src/Panel.php',
];
