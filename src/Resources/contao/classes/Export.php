<?php

namespace HoerElectronic\ContaoImport;

/***
 * 
 * 
 * 
 ***/
class Export extends \BackendModule {

	/*
	 * 
	 */
	protected $xml_writer = null;

	/*
	 * 
	 */
	protected $types_cache = null;
	protected $groups_cache = null;
	protected $files_cache = null;
	protected $pages_cache = null;

	/*
	 * 
	 */
	protected static $db_isotope = [
		'id',
		'pid',
		'gid',
		'tstamp',
		'dateAdded',
		'type',
		'orderPages',
		'published',
	];

	/**
	 * 
	 * 
	 * @param	array	$data
	 * 
	 * @return	array	
	 **/
	protected function compileIdentifier($data) {

		if (is_array($data)) foreach (array('sku', 'name', 'alias') as $key) {
			$b = (array_key_exists($key, $data)) ? $this->convertData([$key, $data[$key]]) : null;
			if ($b && is_array($b) && count($b) == 2)
				return $b;
		}

		return ['missing', null];
	}

	/**
	 * Internally used function to prepare the data (e.g. 'MLT 5') of one field (e.g. 'name')
	 * from Isotope's table 'tl_iso_product' to be exported to XML.
	 * 
	 * If the given data was serialised before (e.g. into 'a:0:{}'), it will be unserialised now (e.g. into an array).
	 * 
	 * @param	array	$key_and_value	Array with two items:
	 * 		$key_and_value[0] - Name of the database-field where the data comes from
	 * 		$key_and_value[1] - Data of the database-field to be modified if needed
	 * 
	 * @return	array|null	- Array with two items:
	 * 		$res[0]: Name of the given database-field, maybe changed
	 * 		$res[1]: Data of the given database-field, maybe changed
	 * 		- Null is returned in case of faulty data or data that shall not automatically be included into XML.
	 **/
	protected function convertData($key_and_value) {

		// Read arguments which are given as an array:
		if (! is_array($key_and_value) || count($key_and_value) != 2 || ! is_scalar($key_and_value[0]))
			return null;
		$key = $key_and_value[0];
		$value = $key_and_value[1];

		// Recognise serialised values:
		$v = unserialize($value);
		if ($v !== FALSE)
			$value = $v;

		// Skip empty fields:
		if (! $value || (is_array($value) && ! count($value))) {
			return null;
		}

		// Convert data if needed:
		switch ($key) {

			case 'download_order':	return null;

			case 'producttype':		$value = $this->getProductType($value);		break;
			case 'group':			$value = $this->getGroup($value);			break;
			case 'categories':		$value = $this->getPages($value);			break;

			case 'shipping_weight':
				// Skip if value equals ['', 'kg']:
				if (is_array($value) && count($value) == 2 && $value[0] == '' && $value[1] == 'kg')
					return null;
				break;

			case 'shipping_price':
				if ($value == '0.00')
					return null;
				break;

			case 'download':
				if (! is_array($value))
					return null;
				foreach ($value as $k => $v)
					$value[$k] = $this->getFile($v);
				break;
		}

		// Return pair of data:
		return [$key, $value];
	}

	/**
	 * 
	 * 
	 **/
	protected function writeXmlValue($key_and_value, $attributes_array = null) {

		// Read the given pair of data:
		if (! is_array($key_and_value) || count($key_and_value) != 2 || ! is_scalar($key_and_value[0]))
			return;
		$key = $key_and_value[0];
		$value = $key_and_value[1];

		// Ensure that attributes are given as an array:
		if (! is_array($attributes_array))
			$attributes_array = [];

		if (is_array($value)) {
			if (! count($value))
				return;
			$this->xml_writer->startElementNS('isotope', $key, null);
			foreach ($attributes_array as $k => $v)
				$this->xml_writer->writeAttribute($k, $v);
			foreach ($value as $k => $v) {
				if (is_integer($k))
					$this->writeXmlValue(['item', $v], ['index' => $k]);
				else
					$this->writeXmlValue([$k, $v]);
			}
			$this->xml_writer->endElement();
		}
		else {
			if (! $value)
				return;
			$this->xml_writer->startElementNS('isotope', $key, null);
			foreach ($attributes_array as $k => $v)
				$this->xml_writer->writeAttribute($k, $v);
			$this->xml_writer->text($value);
			$this->xml_writer->endElement();
		}
	}

	/**
	 * 
	 * 
	 **/
	protected function getProductType($type_id) {

		if (! $type_id)
			return null;

		if (! $this->types_cache) {
			$this->types_cache = [];
			$res = $this->Database->prepare("SELECT id, name FROM tl_iso_producttype")->execute();
			while ($data = $res->next())
				$this->types_cache[$data->id] = $data->name;
		}

		return (array_key_exists($type_id, $this->types_cache)) ? $this->types_cache[$type_id] : '';
	}

	/**
	 * 
	 * 
	 **/
	protected function getGroup($group_id) {

		if (! $group_id)
			return null;

		if (! $this->groups_cache) {
			$this->groups_cache = [];
			$res = $this->Database->prepare("SELECT id, name FROM tl_iso_group")->execute();
			while ($data = $res->next())
				$this->groups_cache[$data->id] = $data->name;
		}

		return (array_key_exists($group_id, $this->groups_cache)) ? $this->groups_cache[$group_id] : null;
	}

	/**
	 * 
	 * 
	 **/
	protected function getFile($binary_uuid) {

		if (! $binary_uuid)
			return null;

		$uuid = bin2hex($binary_uuid);

		if (! $this->files_cache)
			$this->files_cache = [];
	
		if (! array_key_exists($uuid, $this->files_cache)) {
			$res = $this->Database->prepare("SELECT path, name FROM tl_files WHERE uuid = ?")->execute($binary_uuid);
			$data = $res->next();
			$this->files_cache[$uuid] = ($data) ? ['filepath' => $data->path, 'filename' => $data->name] : "Contao-UUID:0x$uuid";
		}

		return $this->files_cache[$uuid];
	}

	/**
	 * 
	 * 
	 * 
	 * @param	integer|array	$page_ids
	 * 
	 * @return	null|array
	 **/
	protected function getPages($page_ids) {

		if (! $page_ids)
			return null;

		if (! is_array($page_ids)) {

			if (! $this->pages_cache)
				$this->pages_cache = [];

			if (! array_key_exists($page_ids, $this->pages_cache)) {
				$res = $this->Database->prepare("SELECT alias FROM tl_page WHERE id = ?")->execute($page_ids);
				$data = $res->next();
				$this->pages_cache[$page_ids] = ($data) ? ['alias' => $data->alias] : null;
			}

			return $this->pages_cache[$page_ids];
		}
		else {

			$a = [];
			foreach ($page_ids as $key => $id) {
				$p = $this->getPages($id);
				if ($p)
					$a[$key] = $p;
			}

			return (count($a)) ? $a : null;
		}


	}

	/**
	 * 
	 * 
	 **/
	public function generate() {

		header('Content-Type: application/xml');
		header('Content-Disposition: inline; filename="isotope.xml"');

		$this->xml_writer = new \XMLWriter();
		$this->xml_writer->openURI('php://output');

		$this->xml_writer->setIndent(true);
		$this->xml_writer->startDocument('1.0', 'UTF-8');
		$this->xml_writer->startElementNS('isotope', 'product-list', 'http://hoer-electronic.de');

		$products = $this->Database->prepare("SELECT * FROM tl_iso_product p WHERE NOT p.pid > 0 ORDER BY id")->execute();
		while ($p_data = $products->next()) {
			//
			$p_data = $p_data->row();
			// $ident_array = static::compileIdentifier($p_data);
			$ident = $this->compileIdentifier($p_data);
			// Look for product's variants:
			$v_data = $this->Database->prepare("SELECT * FROM tl_iso_product p WHERE p.pid = ? ORDER BY id")->execute($p_data['id']);
			$variants = [];
			while ($v = $v_data->next())
				$variants[] = $v->row();
			//
			for ($i = -1; $i < count($variants); $i++) {
				// Get data of current product:
				$row = ($i == -1) ? $p_data : $variants[$i];
				// Open new XML-range for current product:
				$this->xml_writer->startElementNS('isotope', 'product', null);
				if ($ident[1])
					$this->xml_writer->writeAttribute('id', $ident[1]);
				$this->xml_writer->writeAttribute('id-type', $ident[0]);
				if ($i >= 0)
					$this->xml_writer->writeAttribute('variant-index', $i);
				// Memorise some structural data connected to Isotope's database:
				$this->xml_writer->startElementNS('isotope', 'contao-data', null);
				foreach (static::$db_isotope as $key) {
					if (array_key_exists($key, $row))
						$this->writeXmlValue($this->convertData([$key, $row[$key]]));
				}
				$this->xml_writer->endElement();
				//
				if ($i == -1) {
					foreach (['type' => 'producttype', 'gid' => 'group', 'orderPages' => 'categories'] as $key1 => $key2) {
						if (array_key_exists($key1, $row))
							$this->writeXmlValue($this->convertData([$key2, $row[$key1]]));
					}
				}
				// 
				foreach ($row as $key => $value) {
					if ($key == $ident[0] || array_search($key, static::$db_isotope) !== FALSE)
						continue;
					$this->writeXmlValue($this->convertData([$key, $value]));
				}
				//
				$this->xml_writer->endElement();
			}
		}

		$this->xml_writer->endElement();
		$this->xml_writer->flush();
		exit;
	}

	/**
	 * 
	 * 
	 **/
	protected function compile() {

		return '';
	}
}
