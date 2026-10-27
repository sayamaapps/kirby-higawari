<?php

$config = Kirby\PhpCs\Config::create();

// Class docblocks name the package, so the Kirby rule set must keep @package.
$config->setRules($config->getRules() + ['phpdoc_no_package' => false]);

return $config->setFinder(
	PhpCsFixer\Finder::create()
		->exclude('vendor')
		->in(__DIR__)
);
