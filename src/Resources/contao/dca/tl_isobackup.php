<?php

namespace HoerElectronic\ContaoImport;

$GLOBALS['TL_DCA']['tl_isobackup'] = [

	// Contao reference: https://docs.contao.org/books/api/dca/reference.html

	// Table configuration:
	'config' 	=> [
		'label'				=> &$GLOBALS['TL_LANG']['tl_isobackup']['label'],
		'dataContainer'		=> 'Table',
		'enableVersioning'	=> false,
		'sql'				=> ['keys' => ['id' => 'primary']],
	],

	// Structure within database:
	'fields' 	=> [
		'id'		=> [
			'sql'			=> 'int(10) unsigned NOT NULL auto_increment',
		],
		'status'	=> [
			'sql'			=> 'varchar(16)',
		],
		'import_id'	=> [
			'sql'			=> 'varchar(128)',
		],
		'isotope_id'=> [
			'sql'			=> 'int(10) unsigned',
		],
		'data'		=> [
			'sql'			=> 'mediumtext',
		],
		'actions'	=> [
			'sql'			=> 'mediumtext',
		],
		'tstamp'	=> [
			'sql'			=> 'int(10) unsigned NOT NULL DEFAULT 0',
		],
	],
];
