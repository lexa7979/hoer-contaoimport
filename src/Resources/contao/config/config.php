<?php

// Add back end modules
$hoer_backup = array(
	'callback'          => 'HoerElectronic\ContaoImport\Backup',
	'tables'            => array(),
	// 'icon'              => 'system/modules/isotope/assets/images/application-monitor.png',
	// 'javascript'        => 'system/modules/isotope/assets/js/backend.js',
);

if (!array_key_exists('isotope', $GLOBALS['BE_MOD'])) {
    array_insert($GLOBALS['BE_MOD'], 1, ['isotope' => array()]);
}
$GLOBALS['BE_MOD']['isotope']['isobackup'] = $hoer_backup;


// if (array_key_exists('isotope', $GLOBALS['BE_MOD'])) {
// 	$GLOBALS['BE_MOD']['isotope']['hoer_backup'] = $hoer_backup;
// }
// else {
// 	if (! array_key_exists('hoer', $GLOBALS['BE_MOD']))
// 		array_insert($GLOBALS['BE_MOD'], 1, array('hoer' => array()));
// 	$GLOBALS['BE_MOD']['hoer']['hoer_backup'] = $hoer_backup;
// }

// if (array_key_exists('ISO_MOD', $GLOBALS) && array_key_exists('miscellaneous:hide', $GLOBALS['ISO_MOD'])) {
// 	$GLOBALS['ISO_MOD']['miscellaneous:hide']['hoer_backup'] = $hoer_backup;
// }
