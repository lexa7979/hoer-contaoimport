<?php

$item_group = [
	'MOD' => [
		'hoer'				=> ['Zusatzfunktionen',		'Zusatzfunktionen'],
		'hoer_export'		=> ['Shop-Export',	'Shopdaten exportieren'],
	],
	// 'FMD' => [
	// 	'timetableview'	=> ['Kursplan',		'Fügt der Seite einen Kursplan hinzu.'],
	// ],
];
foreach ($item_group as $type => $items) foreach ($items as $key => $value) $GLOBALS['TL_LANG'][$type][$key] = $value;

// foreach ($items['MOD'] as $key => $value) $GLOBALS['TL_LANG']['MOD'][$key] = $value;
// foreach ($items['FMD'] as $key => $value) $GLOBALS['TL_LANG']['FMD'][$key] = $value;
