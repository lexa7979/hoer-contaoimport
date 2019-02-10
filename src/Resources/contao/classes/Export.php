<?php

namespace HoerElectronic\ContaoImport;

/***
 *
 *
 *
 ***/
class Export extends \BackendModule {

	/**
	 * @property	Object	$xml_writer
	 *
	 * Reference of a XMLWriter-object which will be used
	 * to generate XML-data during export
	 */
	protected $xml_writer = null;

	/**
	 * @property	array	$types_cache
	 * @property	array	$groups_cache
	 * @property	array	$files_cache
	 * @property	array	$pages_cache
	 *
	 * These arrays are used to cache the content of some Contao-tables:
	 * - tl_iso_producttype
	 * - tl_iso_group
	 * - tl_files
	 * - tl_page
	 */
	protected $types_cache = null;
	protected $groups_cache = null;
	protected $files_cache = null;
	protected $pages_cache = null;

	/**
	 * @property	array	$db_product_special
	 *
	 * This array lists all fields within the Isotope-table 'tl_iso_product'
	 * which are used to save structural data (e.g. relations with other tables).
	 *
	 * The data of these fields will be handled in a special way.
	 */
	protected static $db_product_special = ['id', 'pid', 'gid', 'tstamp', 'dateAdded', 'type', 'orderPages', 'published', 'start', 'stop'];

	/**
	 * @property	array	$attributes_cache
	 *
	 * This array is used to cache the content of Isotope-table 'tl_iso_attribute'.
	 *
	 * The Isotope-table 'tl_iso_product' contains one additional field
	 * for every custom product-attribute,
	 * e.g. [
	 * 			'supported_payments'=> [ 'id' => ..., 'name' => ..., 'type' => ..., 'legend' => ..., 'description' => ... ],
	 * 			'operating_voltage'	=> [ 'id' => ..., 'name' => ..., 'type' => ..., 'legend' => ..., 'description' => ... ],
	 * 			...
	 * 		]
	 *
	 * The list will be initialised
	 * when getAttributesList() is called for the first time.
	 */
	protected static $attributes_cache = null;

	/**
	 * This function is internally used to read the list of product-attributes
	 * from the Isotope-table 'tl_iso_attribute'.
	 *
	 * The table content will be cached at the first time
	 * this function is called.
	 *
	 * @param	bool	$names_only
	 *		Set to true, if this function shall deliver the list of known attributes,
	 *		otherwise the complete data of all product-attributes is returned.
	 * @return	array
	 *		List of internal attribute-names (if $names_only == true),
	 *			e.g. [ 'supported_payments', 'operating_voltage', ... ], or
	 * 		Data of all product-attributes (if $names_only != true),
	 * 			i.e. $this->attributes_cache.
	 */
	protected function getAttributesList($names_only = true) {

		if (! $this->attributes_cache) {
			$this->attributes_cache = [];
			$res = $this->Database->prepare("SELECT id, name, field_name, type, legend, description FROM tl_iso_attribute")->execute();
			while ($data = $res->next()) {
				$this->attributes_cache[$data->field_name] = [
					'id'			=> $data->id,
					'name'			=> $data->name,
					'description'	=> $data->description,
					'type'			=> $data->type,
					'legend'		=> $data->legend,
				];
			}
		}

		return ($names_only) ? array_keys($this->attributes_cache) : $this->attributes_cache;
	}

	/**
	 * Internally used function to build an unique identifier for a given product
	 * of Isotope's database.
	 *
	 * @param	array	$data	Dataset out of Isotope's product-table 'tl_iso_product'
	 *
	 * @return	array	Array with two items:
	 * 		$res[0]: Type of identifier (i.e. 'sku', 'name', 'alias')
	 * 		$res[1]: Value of the related field of the given recordset
	 *
	 * If no identifier could be built the function returns with $res[0] == 'missing'.
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
	 * @param	array	$key_and_value	Array with two items:
	 * 		$key_and_value[0] - Name of the database-field
	 * 		$key_and_value[1] - Data of the database-field
	 * @return	null
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
	 * Queries one productgroup from the Isotope-table 'tl_iso_group'.
	 *
	 * The table content will be cached at the first time
	 * this function is called.
	 *
	 * @param	int		$group_id
	 *		Identifier of the requested record, e.g. 13
	 * @param	bool	$name_only
	 *		Set to true if only the group's name shall be returned,
	 *		otherwise the complete data will be returned as an array.
	 * @return	string|array|null
	 *		Name of the group (if $name_only == true); or
	 *		Data of the group (if $name_only != true); or
	 *		null iff group wasn't found
	 **/
	protected function getGroup($group_id, $name_only = true) {

		if (! $group_id)
			return null;

		if (! $this->groups_cache) {
			// Cache the data:
			$this->groups_cache = [];
			$res = $this->Database->prepare('SELECT id, name, sorting, product_type FROM tl_iso_group')->execute();
			while ($data = $res->next()) {
				$this->groups_cache[$data->id] = [
					'name'		=> $data->name,
					'sorting'	=> $data->sorting,
					'type'		=> $data->product_type,
				];
			}
		}

		if ($name_only) {
			return (array_key_exists($group_id, $this->groups_cache)) ? $this->groups_cache[$group_id]['name'] : null;
		}

		return (array_key_exists($group_id, $this->groups_cache)) ? $this->groups_cache[$group_id] : null;
	}

	/**
	 * Queries one file from Contao-table 'tl_files'.
	 *
	 * The record's data will be cached in case the function
	 * will be called with the same UUID again.
	 *
	 * @param	string	$binary_uuid
	 *		Binary string with the UUID of the requested file
	 * @return	array|string
	 *		If a file with the UUID was found,
	 *		the file's path and name are returned;
	 *			e.g. [
	 *					'filepath'	=> 'files/downloads/datasheet1.pdf',
	 *					'filename'	=> 'datasheet1.pdf'
	 *				 ]
	 * 		Otherwise a string with a hexadecimal representation
	 *		of the file's UUID will be delivered
	 *			e.g. "Contao-UUID:0x1063f56189f211e5bfe000163e4fa28f"
	 **/
	protected function getFile($binary_uuid) {

		if (! $binary_uuid)
			return null;

		$uuid = bin2hex($binary_uuid);

		if (! $this->files_cache)
			$this->files_cache = [];

		if (! array_key_exists($uuid, $this->files_cache)) {
			$res = $this->Database->prepare("SELECT path, name FROM tl_files WHERE uuid = ? AND type = 'file'")->execute($binary_uuid);
			$data = $res->next();
			$this->files_cache[$uuid] = ($data) ? ['filepath' => $data->path, 'filename' => $data->name] : "Contao-UUID:0x$uuid";
		}

		return $this->files_cache[$uuid];
	}

	/**
	 * Internally used function to query the data of one or more Contao-pages of the current site.
	 *
	 * @param	integer|array	$page_ids	IDs of one or more
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
			// Get the product's data:
			$p_data = $p_data->row();
			$ident = $this->compileIdentifier($p_data);
			// Look for product's variants:
			$v_data = $this->Database->prepare("SELECT * FROM tl_iso_product p WHERE p.pid = ? ORDER BY id")->execute($p_data['id']);
			$variants = [];
			while ($v = $v_data->next())
				$variants[] = $v->row();
			// Process the current product and its variants:
			for ($i = -1; $i < count($variants); $i++) {
				$row = ($i == -1) ? $p_data : $variants[$i];
				// Open new XML-range for current product / variant:
				$this->xml_writer->startElementNS('isotope', 'product', null);
				if ($ident[1])
					$this->xml_writer->writeAttribute('id', $ident[1]);
				$this->xml_writer->writeAttribute('id-type', $ident[0]);
				if ($i >= 0)
					$this->xml_writer->writeAttribute('variant-index', $i);
				// Memorise some structural data connected to Isotope's database:
				$this->xml_writer->startElementNS('isotope', 'contao-data', null);
				foreach (static::$db_product_special as $key) {
					if (array_key_exists($key, $row))
						$this->writeXmlValue($this->convertData([$key, $row[$key]]));
				}
				$this->xml_writer->endElement();
				// Memorise some general data of current product:
				if ($i == -1) foreach (['type' => 'producttype', 'gid' => 'group', 'orderPages' => 'categories'] as $key1 => $key2) {
					if (array_key_exists($key1, $row))
						$this->writeXmlValue($this->convertData([$key2, $row[$key1]]));
				}
				// Memorise all data of current product / variant,
				// skipping the already processed data (identifier, structural data):
				foreach ($row as $key => $value) {
					if ($key == $ident[0] || array_search($key, static::$db_product_special) !== FALSE)
						continue;
					$this->writeXmlValue($this->convertData([$key, $value]));
				}
				// Close XML-range </isotope:product>
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
