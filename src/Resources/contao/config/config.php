<?php

// Add back end modules
$hoer_export = array(
	'callback'          => 'HoerElectronic\ContaoImport\Export',
	'tables'            => array(),
	// 'icon'              => 'system/modules/isotope/assets/images/application-monitor.png',
	// 'javascript'        => 'system/modules/isotope/assets/js/backend.js',
);

if (array_key_exists('isotope', $GLOBALS['BE_MOD'])) {
	$GLOBALS['BE_MOD']['isotope']['hoer_export'] = $hoer_export;
}
else {
	if (! array_key_exists('hoer', $GLOBALS['BE_MOD']))
		array_insert($GLOBALS['BE_MOD'], 1, array('hoer' => array()));
	$GLOBALS['BE_MOD']['hoer']['hoer_export'] = $hoer_export;
}

if (array_key_exists('ISO_MOD', $GLOBALS) && array_key_exists('miscellaneous:hide', $GLOBALS['ISO_MOD'])) {
	$GLOBALS['ISO_MOD']['miscellaneous:hide']['hoer_export'] = $hoer_export;
}


// // Front end module(s):
// array_insert($GLOBALS['FE_MOD'], 3, [
// 	// Group title:
// 	'Cepharum' => [
// 		// Module title(s) and corresponding class name(s):
// 		'timetableview' => 'Cepharum\Timetable\ModuleTimetable',
// 	]
// ]);

// // Style sheet
// if (TL_MODE == 'BE')
// {
// 	$GLOBALS['TL_CSS'][] = 'bundles/cepharumtimetable/style.css|static';
// }
