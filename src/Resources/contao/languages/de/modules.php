<?php

$item_group = [
	'MOD' => [
		'isobackup'		=> ['Datensicherung', 'Shopdaten exportieren und importieren'],
	],
];
foreach ($item_group as $type => $items) foreach ($items as $key => $value) $GLOBALS['TL_LANG'][$type][$key] = $value;
