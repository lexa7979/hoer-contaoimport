<?php

$new_items = [
	'introduction'	=> [
		'Isotope Backup',
		'Dies ist eine Erweiterung zum Shop-System Isotope. Sie können hier die Daten aller Produkte exportieren und importieren.',
	],

	'export-intro'	=> [
		'Produkte sichern',
		'Die Daten der Produkte, welche in Isotope registriert sind, können hier im ZIP-Format heruntergeladen werden.',
	],

	'import-intro'	=> [
		'Produkte wiederherstellen',
		'Laden Sie eine Exportdatei im ZIP-Format hoch. Diese wird dann analysiert und Sie können ggf. Änderungen am eingerichteten Shop automatisiert vornehmen lassen.',
		'Der Import funktioniert aktuell nur dann problemlos, wenn jedes Produkt einen eindeutigen Alias hat und der Alias seit dem Export nicht verändert wurde. Sollte es Produktvarianten geben, werden diese über die Artikelnummer identifiziert - diese muss daher ebenfalls eindeutig und unverändert sein.',
		'Die Analyse zeigt Unterschiede zwischen den Importdaten und den aktuellen Produktdaten auf. Das Einpflegen der Importdaten wird gerade ausgebaut - aktuell gibt es nur für eine Auswahl an Unterschieden die Möglichkeit, die Produktdaten automatisch anpassen zu lassen.',
	],

	'create-export-abort-busy'	=> 'Der Export wurde zeitweise deaktiviert: Vor kurzem wurden Änderungen an den relevanten Datentabellen in Isotope / Contao vorgenommen. Bitte versuchen Sie es etwas später noch einmal.',
	'create-export-ready'		=> 'Die Exportdaten liegen bereit und können abgerufen werden.',
	'create-export-successful'	=> 'Die aktuellen Daten wurden erfolgreich für den Export zusammengestellt und können nun abgerufen werden.',
	'create-export-progressing'	=> 'Die Daten für den Export werden gerade zusammengestellt. Bitte laden Sie die Seite neu um herauszufinden, wann die Exportdatei abgerufen werden kann.',
	'create-export-failed'		=> 'Bei der Aufbereitung der Daten ist ein Fehler aufgetreten!',

	'check-import-ready'		=> 'Eine Importdatei wurde erfolgreich hochgeladen.',
	'check-import-missing'		=> 'Es wurde noch keine Importdatei hochgeladen.',
	'check-import-failed'		=> 'Bei der Vorbereitung des Imports ist ein Fehler aufgetreten!',

	'import-timestamp'			=> 'Die Datei wurde %s unter dem Namen \'%s\' empfangen.',
	'import-timestamp-noname'	=> 'Die Datei wurde %s empfangen.',
	'import-timestamp-seconds'	=> 'vor %d Sekunden',
	'import-timestamp-minutes'	=> 'vor %d Minuten',
	'import-timestamp-hours'	=> 'vor %d Stunden',
	'import-timestamp-days'		=> 'vor %d Tagen',
	'import-timestamp-date'		=> 'am %s',

	'analysis-empty'			=> 'Es liegen noch keine Analyseergebnisse vor.',
	// 'analysis-progressing'		=> 'Die Importdaten werden gerade analysiert... Laden Sie die Seite neu um herauszufinden, ob die Analyse bereits abgeschlossen wurde.',
	'analysis-started'			=> 'Beginne die Analyse der Importdaten...',
	'analysis-readxmlfile'		=> 'Die XML-Daten werden eingelesen...',
	'analysis-readxmldata'		=> 'Die Produktdaten aus der Importdatei werden aufbereitet.',
	'analysis-import'			=> 'Die Produktdaten aus der Importdatei werden verarbeitet.',
	'analysis-isotope'			=> 'Die Produktdaten aus dem Shop werden untersucht.',
	'analysis-successful'		=> 'Die Analyse wurde erfolgreich abgeschlossen.',
	'analysis-ready'			=> 'Die Importdaten wurden bereits analysiert.',

	'import-remove'				=> 'Importdaten löschen',
	'import-analyse-start'		=> 'Analyse starten',




	'error message'		=> 'Fehlermeldung',

	'download'			=> 'Exportdatei herunterladen',
	'upload'			=> 'Importdatei hochladen',


];
foreach ($new_items as $key => $value) $GLOBALS['TL_LANG']['tl_isobackup'][$key] = $value;
