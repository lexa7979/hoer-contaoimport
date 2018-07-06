<?php

var_dump("Hallo lexA!"); exit;

// Add back end modules
if (! array_key_exists('hoer', $GLOBALS['BE_MOD']))
	array_insert($GLOBALS['BE_MOD'], 1, array('hoer' => array()));

array_insert($GLOBALS['BE_MOD']['hoer'], 1, array(
    'export' => array(
        'callback'          => 'HoerElectronic\ContaoImport\ExportPage',//'Isotope\BackendModule\Setup',
        'tables'            => array(),
        'icon'              => 'system/modules/isotope/assets/images/application-monitor.png',
        'javascript'        => 'system/modules/isotope/assets/js/backend.js',
    ),
));

if (array_key_exists('ISO_MOD', $GLOBALS) && array_key_exists('miscellaneous:hide', $GLOBALS['ISO_MOD']))
	$GLOBALS['ISO_MOD']['miscellaneous:hide']['export'] = array(
		'callback'			=> '',
		'icon'              => 'system/modules/isotope/assets/images/setup-integrity.png'
	);
// (
// 	'labels' => array
// 	(
// 		'tables'            => array(\Isotope\Model\Label::getTable()),
// 		'icon'              => 'system/modules/isotope/assets/images/setup-labels.png'
// 	),
// 	'integrity' => array
// 	(
// 		'callback'          => 'Isotope\BackendModule\Integrity',
// 		'icon'              => 'system/modules/isotope/assets/images/setup-integrity.png'
// 	),
// )


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
