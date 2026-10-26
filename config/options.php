<?php

// Options the plugin reads, relative to the plugin: `sayamaapps.higawari.<key>`.
// Each value is null on purpose. Options::get() gives the default at every
// read, and a default here would hide the spelling `higawari.<key>` (fallback).
return [
	'active'    => null,
	'days'      => null,
	'dateMode'  => null,
	'timezone'  => null,
	'currency'  => null,
	'emptyText' => null,
	'note'      => null,
	'language'  => null,
];
