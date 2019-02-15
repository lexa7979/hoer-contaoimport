<?php

namespace HoerElectronic\ContaoImport;


function my_unserialize($serialized_data) {

	if (!is_string($serialized_data)) {
		return $serialized_data;
	}

	try {
		$d = unserialize($serialized_data);
		$s = serialize($d);
	}
	catch (\Throwable $error) {
		return $serialized_data;
	}

	return ($s == $serialized_data) ? $d : $serialized_data;
}

/***
 *
 *
 *
 ***/
class Backup extends \BackendModule {

	protected $strTemplate = 'be_isobackup';

	protected $tmpDir = 'hoer_isobackup';
	protected $exportFile = 'product_export';
	protected $importFile = 'product_import';

	/**
	 * @property	Object	$xml_writer
	 *
	 * Reference of a XMLWriter-object which will be used
	 * to generate XML-data during export
	 */
	protected $xml_writer = null;

	/**
	 * @property	array		$db_product_special
	 *
	 * This array lists all fields within the Isotope-table 'tl_iso_product'
	 * which are used to save structural data (e.g. relations with other tables).
	 *
	 * The data of these fields will be handled in a special way.
	 */
	protected static $db_product_special = ['id', 'pid', 'gid', 'tstamp', 'dateAdded', 'type', 'orderPages', 'published', 'start', 'stop'];

	/**
	 * @property	array		$producttypes_cache
	 * @property	array		$groups_cache
	 * @property	array		$attributes_cache
	 * @property	array		$taxclass_cache
	 * @property	array		$membergroup_cache
	 * @property	array		$files_cache
	 * @property	array		$pages_cache
	 *
	 * These arrays are used to cache the content of some Contao-tables:
	 * - tl_iso_producttype
	 * - tl_iso_group
	 * - tl_iso_attribute
	 * - tl_iso_tax_class
	 * - tl_member_group
	 * - tl_files
	 * - tl_page
	 *
	 * Note:
	 * The Isotope-table 'tl_iso_product' contains one additional field
	 * for every custom product-attribute in 'tl_iso_attribute',
	 * e.g. [
	 * 			'supported_payments'=> [ 'id' => ..., 'name' => ..., 'type' => ..., 'legend' => ..., 'description' => ... ],
	 * 			'operating_voltage'	=> [ 'id' => ..., 'name' => ..., 'type' => ..., 'legend' => ..., 'description' => ... ],
	 * 			...
	 * 		]
	 */
	protected $producttypes_cache = null;
	protected $groups_cache = null;
	protected $attributes_cache = null;
	protected $taxclass_cache = null;
	protected $membergroup_cache = null;
	protected $files_cache = null;
	protected $pages_cache = null;

	/**
	 * Queries one product-type from the Isotope-table 'tl_iso_producttype'.
	 *
	 * The table content will be cached at the first time
	 * this function is called.
	 *
	 * @param	int			$type_id
	 *		Identifier of the requested record, e.g. 13
	 * @param	bool		$name_only
	 *		Set to true if only the product-type's name shall be returned,
	 *		otherwise the complete data will be returned as an array.
	 * @return	string|array|null
	 *		Name of the product-type (if $name_only == true); or
	 *		Data of the product-type (if $name_only != true); or
	 *		null iff record wasn't found
	 **/
	protected function getProductType($type_id, $name_only = true) {

		if (!$type_id) {
			return null;
		}

		if (!$this->producttypes_cache) {
			$this->producttypes_cache = [];
			$res = $this->Database->prepare('SELECT id, name, attributes, variants, variant_attributes FROM tl_iso_producttype')->execute();
			while ($data = $res->next()) {
				$this->producttypes_cache[$data->id] = [
					'name'					=> $data->name,
					'attributes'			=> $data->attributes,
					'variants'				=> $data->variants,
					'variant_attributes'	=> $data->variant_attributes,
				];
			}
		}

		if (!array_key_exists($type_id, $this->producttypes_cache)) {
			return null;
		}

		return $name_only ? $this->producttypes_cache[$type_id]['name'] : $this->producttypes_cache[$type_id];
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

		if (!$group_id) {
			return null;
		}

		if (!$this->groups_cache) {
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
	 * Queries one product-attribute from the Isotope-table 'tl_iso_attribute'.
	 *
	 * This function is internally used to read the list of product-attributes
	 * from the Isotope-table 'tl_iso_attribute'.
	 *
	 * The table content will be cached at the first time
	 * this function is called.
	 *
	 * @param	int|string	$attribute_id_or_name
	 *		ID of the requested record, e.g. 13; or
	 *		Name of the product-attribute, e.g. 'supported_payments'
	 * @param	bool		$name_only
	 *		Set to true if only the attribute's name shall be returned,
	 *		otherwise the complete data will be returned as an array.
	 * @return	array
	 *		List of internal attribute-names (if $name_only == true),
	 *			e.g. [ 'supported_payments', 'operating_voltage', ... ], or
	 * 		Data of all product-attributes (if $name_only != true),
	 * 			i.e. $this->attributes_cache.
	 */
	protected function getAttribute($attribute_id_or_name = null, $name_only = true) {

		if (! $this->attributes_cache) {
			$this->attributes_cache = [];
			$res = $this->Database->prepare("SELECT id, name, field_name, type, legend, description FROM tl_iso_attribute")->execute();
			while ($data = $res->next()) {
				$this->attributes_cache[$data->field_name] = [
					'id'			=> $data->id,
					'field_name'	=> $data->field_name,
					'name'			=> $data->name,
					'description'	=> $data->description,
					'type'			=> $data->type,
					'legend'		=> $data->legend,
				];
			}
		}

		if (is_numeric($attribute_id_or_name) && $attribute_id_or_name > 0) {
			foreach ($this->attributes_cache as $f => $v) {
				if ($v['id'] == $attribute_id_or_name) {
					return ($name_only) ? $f : $v;
				}
			}
		}
		else if (is_string($attribute_id_or_name) && $attribute_id_or_name != '') {
			if (array_key_exists($attribute_id_or_name, $this->attributes_cache)) {
				return ($name_only) ? $attribute_id_or_name : $this->attributes_cache[$attribute_id_or_name];
			}
		}

		return null;
	}

	/**
	 * Queries one tax class from the Isotope-table 'tl_iso_tax_class'.
	 *
	 * The table's content will be cached at the first time this function is called.
	 *
	 * @param	int		$taxclass_id
	 *		Identifier of the requested record, e.g. 13
	 * @param	bool	$name_only
	 *		Determines if the result will be a name-string or the complete record.
	 * @return	string|array|null
	 *		Name of the tax-class (if $name_only == true); or
	 *		Data of the tax-class (if $name_only != true); or
	 *		null iff record wasn't found
	 */
	protected function getTaxClass($taxclass_id, $name_only = true) {

		if (!$taxclass_id) {
			return null;
		}

		if (!$this->taxclass_cache) {
			$this->taxclass_cache = [];
			$res = $this->Database->prepare('SELECT id, name, fallback, rates FROM tl_iso_tax_class')->execute();
			while ($data = $res->next()) {
				$this->taxclass_cache[$data->id] = [
					'id'		=> $data->id,
					'name'		=> $data->name,
					'fallback'	=> $data->fallback,
					'rates'		=> my_unserialize($data->rates),
				];
			}
		}

		if (!array_key_exists($taxclass_id, $this->taxclass_cache)) {
			return null;
		}

		return $name_only ? $this->taxclass_cache[$taxclass_id]['name'] : $this->taxclass_cache[$taxclass_id];
	}

	/**
	 * Queries one member-group from the Contao-table 'tl_member_group'.
	 *
	 * The table's content will be cached at the first time this function is called.
	 *
	 * @param	int		$membergroup_id
	 *		Identifier of the requested record, e.g. 13
	 * @param	bool	$name_only
	 *		Determines if the result will be a name-string or the complete record.
	 * @return	string|array|null
	 * 		Name of the member-group (if $name_only == true); or
	 * 		Data of the member-group (if $name_only != true); or
	 * 		null iff record wasn't found
	 */
	protected function getMemberGroup($membergroup_id, $name_only = true) {

		if (!$membergroup_id) {
			return null;
		}

		if (!$this->membergroup_cache) {
			$this->membergroup_cache = [];
			$res = $this->Database->prepare('SELECT id, name FROM tl_member_group')->execute();
			while ($data = $res->next()) {
				$this->membergroup_cache[$data->id] = [
					'id'		=> $data->id,
					'name'		=> $data->name,
				];
			}
		}

		if (!array_key_exists($membergroup_id, $this->membergroup_cache)) {
			return null;
		}

		return $name_only ? $this->membergroup_cache[$membergroup_id]['name'] : $this->membergroup_cache[$membergroup_id];
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
	 * 		If the file was not found in the database a string
	 *		with a hexadecimal representation of the file's UUID will be delivered
	 *			e.g. "Contao-UUID:0x1063f56189f211e5bfe000163e4fa28f"
	 **/
	protected function getFile($binary_uuid) {

		if (!$binary_uuid) {
			return null;
		}

		$uuid = bin2hex($binary_uuid);

		if (!$this->files_cache) {
			$this->files_cache = [];
		}

		if (!array_key_exists($uuid, $this->files_cache)) {
			$res = $this->Database->prepare("SELECT path, name FROM tl_files WHERE uuid = ? AND type = 'file'")->execute($binary_uuid);
			$data = $res->next();
			$this->files_cache[$uuid] = ($data) ? ['filepath' => $data->path, 'filename' => $data->name] : "Contao-UUID:0x$uuid";
		}

		return $this->files_cache[$uuid];
	}

	/**
	 * Internally used function to query the data of one or more Contao-pages of the current site.
	 *
	 * @param	integer|array	$page_ids
	 *		IDs of one or more recordsets
	 * @return	array|null
	 *
	 **/
	protected function getPages($page_ids) {

		if (!$page_ids) {
			return null;
		}

		if (is_array($page_ids)) {
			$a = [];
			foreach ($page_ids as $key => $id) {
				$p = $this->getPages($id);
				if ($p) {
					$a[$key] = $p;
				}
			}
			return (count($a)) ? $a : null;
		}

		if (!$this->pages_cache) {
			$this->pages_cache = [];
		}

		if (!array_key_exists($page_ids, $this->pages_cache)) {
			$res = $this->Database->prepare('SELECT alias FROM tl_page WHERE id = ?')->execute($page_ids);
			$data = $res->next();
			$this->pages_cache[$page_ids] = ($data) ? ['alias' => $data->alias] : null;
		}

		return $this->pages_cache[$page_ids];
	}

	/**
	 * This function finds all prices which are related to the given Isotope-product.
	 *
	 * @param	int		$product_id
	 *		Identifier of a product which is registered in Isotope-table 'tl_iso_product'
	 * @return	array|null
	 *		Two-dimensional array with all found prices; or
	 *		null iff product wasn't found
	 */
	protected function getPrices($product_id) {

		if (!is_numeric($product_id) || $product_id <= 0) {
			return null;
		}

		$prices = [];

		$res1 = $this->Database->prepare('SELECT id, tax_class, member_group FROM tl_iso_product_price WHERE pid = ?')->execute($product_id);
		while ($p1 = $res1->next()) {
			$res2 = $this->Database->prepare('SELECT min, price FROM tl_iso_product_pricetier WHERE pid = ?')->execute($p1->id);
			while ($p2 = $res2->next()) {
				$prices[] = [
					'price'			=> $p2->price,
					'min'			=> $p2->min,
					'tax_class'		=> $this->getTaxClass($p1->tax_class),
					'member_group'	=> $this->getMemberGroup($p1->member_group),
				];
			}
		}

		return count($prices) ? $prices : null;
	}

	/**
	 * Internally used function to build an unique identifier for a given product
	 * of Isotope's database.
	 *
	 * @param	array	$data
	 * 		Dataset out of Isotope's product-table 'tl_iso_product'
	 * @return	array
	 * 		Array with two items:
	 * 			$res[0]: Type of identifier (i.e. 'sku', 'name', 'alias')
	 * 			$res[1]: Value of the related field of the given recordset
	 *
	 * If no identifier could be built the function returns with $res[0] == 'missing'.
	 **/
	protected function compileIdentifier($data) {

		// if (is_array($data)) foreach (array('sku', 'name', 'alias') as $key) {
		if (is_array($data)) foreach (['alias', 'name', 'sku'] as $key) {
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
	 * If the given data was serialised before (e.g. into 'a:0:{}'),
	 * it will be unserialised now (e.g. into an array).
	 *
	 * @param	array		$key_and_value
	 * 		Array with two items:
	 * 			$key_and_value[0] - Name of the database-field where the data comes from
	 * 			$key_and_value[1] - Data of the database-field to be modified if needed
	 * @return	array|null
	 * 		Array with two items:
	 * 			$res[0]: Name of the given database-field, maybe changed
	 * 			$res[1]: Data of the given database-field, maybe changed
	 * 		- Null is returned in case of faulty data or
	 *			data that shall not automatically be included into XML.
	 **/
	protected function convertData($key_and_value) {

		// Read arguments which are given as an array:
		if (!is_array($key_and_value) || count($key_and_value) != 2 || !is_scalar($key_and_value[0])) {
			return null;
		}
		list($key, $value) = $key_and_value;

		// Recognise serialised values:
		$v = unserialize($value);
		if ($v !== FALSE) {
			$value = $v;
		}

		// Skip empty fields:
		if (!$value || (is_array($value) && !count($value))) {
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
				if (is_array($value) && count($value) == 2 && $value[0] == '' && $value[1] == 'kg') {
					return null;
				}
				break;

			case 'shipping_price':
				if ($value == '0.00') {
					return null;
				}
				break;

			case 'download':
				if (!is_array($value)) {
					return null;
				}
				foreach ($value as $k => $v) {
					$value[$k] = $this->getFile($v);
				}
				break;
		}

		// Return pair of data:
		return [$key, $value];
	}

	/**
	 * This function is internally used to append the given data to the XML-file.
	 *
	 * The XML-file is accessed by $this->xml_writer.
	 *
	 * The name of the XML-node may contain additional attributes encoded as
	 * $key_and_value[0] = "name|attribute1:value1|attribute2:value2|...",
	 *		e.g. "item|language:en" will be written as
	 *			 <isotope:item language="en">...</isotope:item>
	 *
	 * @param	array		$key_and_value
	 *		Array with two items:
	 * 			$key_and_value[0] - Name of the XML-node (and attributes if applicable, see above)
	 * 			$key_and_value[1] - Data of the XML-node
	 * @param	array|null	$attributes_array
	 *		Array with additional attributes which shall be used for the current XML-node; or
	 *		null if no additional attributes shall be added
	 * @param	bool		$convert_before
	 *		Set to true iff the given data shall be processed by convertData() before it's appended to the XML-file
	 * @return	null
	 **/
	protected function writeXmlValue($key_and_value, $attributes_array = null, $convert_before = true) {

		if ($convert_before) {
			$key_and_value = $this->convertData($key_and_value);
		}

		// Read the given pair of name and data:
		if (!is_array($key_and_value) || count($key_and_value) != 2 || !is_scalar($key_and_value[0])) {
			return;
		}
		list($key, $value) = $key_and_value;
		if (!is_scalar($value) && (!is_array($value) || count($value) == 0)) {
			return;
		}

		// Check for attributes given together with the XML-node's name:
		$k_parts = explode('|', $key);
		if (is_array($k_parts) && count($k_parts) > 1) {
			if ($k_parts[0] == '') {
				return;
			}
			$key = $k_parts[0];
			if (!is_array($attributes_array)) {
				$attributes_array = [];
			}
			for ($i = 1; $i < count($k_parts); $i++) {
				$a_parts = explode(':', $k_parts[$i]);
				if (is_array($a_parts) && count($a_parts) == 2 && $a_parts[0] != '') {
					$attributes_array[$a_parts[0]] = $a_parts[1];
				}
			}
		}

		// Open a new XML-node:
		$this->xml_writer->startElementNS('isotope', $key, null);
		if (is_array($attributes_array)) {
			foreach ($attributes_array as $k => $v) {
				$this->xml_writer->writeAttribute($k, $v);
			}
		}

		// Write the node's data:
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (is_int($k)) {
					$this->writeXmlValue(['item', $v], ['index' => $k], false);
				} else {
					$this->writeXmlValue([$k, $v], null, false);
				}
			}
		} else {
			$this->xml_writer->text($value);
		}

		// Close the XML-node:
		$this->xml_writer->endElement();
	}

	protected function prepareTmpDir() {

		$dir = sys_get_temp_dir() . '/' . $this->tmpDir;

		if (file_exists($dir)) {
			if (!is_dir($dir)) {
				unlink($dir);
				mkdir($dir);
			}
		}
		else {
			mkdir($dir);
		}

		if (!is_dir($dir)) {
			throw new \Error('No access to temporary directory');
		}

		return $dir;
	}

	protected function getIsotopeTimestamp($include_tableName = false) {

		// List of tables which contain data for the export:
		$db_list = [
			'tl_iso_product',
			'tl_iso_attribute',
			'tl_iso_attribute_option',
			'tl_iso_group',
			'tl_iso_producttype',
			'tl_iso_product_price',
			'tl_iso_product_pricetier',
			'tl_iso_tax_class',
			'tl_member_group',
			'tl_page',
		];

		$max_ts = 0;
		$max_table = '';
		foreach ($db_list as $table) {
			$res = $this->Database->execute("SELECT MAX(tstamp) AS ts FROM $table")->next();
			if ($res->ts > $max_ts) {
				$max_ts = $res->ts;
				$max_table = $table;
			}
		}

		return ($include_tableName) ? [$max_ts, $max_table] : $max_ts;
	}

	/**
	 *
	 *
	 **/
	protected function createExportFile() {

		$this->xml_writer = new \XMLWriter();

		$dir = $this->prepareTmpDir();
		$exportfile = "$dir/{$this->exportFile}.xml";
		$exportzip	= "$dir/{$this->exportFile}.zip";
		$exportts	= "$dir/{$this->exportFile}.ts";
		$exportlock	= "$dir/{$this->exportFile}.lock";

		if (file_exists($exportlock)) {
			throw new \Error('Lockfile found');
		}
		$lock = rand();
		file_put_contents($exportlock, $lock);
		if (file_get_contents($exportlock) != $lock) {
			throw new \Error('Writing lockfile failed');
		}

		list($max_ts, $max_table) = $this->getIsotopeTimestamp(true);
		if (abs(time() - $max_ts) < 120) {
			unlink($exportlock);
			throw new \Error("Table '{$max_table}' was just altered - waiting...");
		}
		file_put_contents($exportts, $max_ts);

		$this->xml_writer->openURI($exportfile);

		$this->xml_writer->setIndent(true);
		$this->xml_writer->startDocument('1.0', 'UTF-8');
		$this->xml_writer->startElementNS('isotope', 'product-list', 'http://hoer-electronic.de');

		$products = $this->Database->prepare('SELECT * FROM tl_iso_product p WHERE NOT p.pid > 0 ORDER BY alias')->execute();
		while ($p_data = $products->next()) {
			// Get the product's data:
			$p_data = $p_data->row();
			$ident = $this->compileIdentifier($p_data);
			$p_data['prices'] = $this->getPrices($p_data['id']);
			$r_lang = $this->Database->prepare('SELECT id, name, description, language FROM tl_iso_product p WHERE p.pid = ? AND language <> "" ORDER BY id')->execute($p_data['id']);
			$p_data['translations'] = [];
			while ($r = $r_lang->next()) {
				$p_data['translations']['item|language:' . $r->language] = ['name' => $r->name, 'description' => $r->description, 'contao-id' => $r->id];
			}
			$p_data = array_filter($p_data);
			// Look for product's variants and their data:
			$v_data = $this->Database->prepare('SELECT * FROM tl_iso_product p WHERE p.pid = ? AND language = "" ORDER BY id')->execute($p_data['id']);
			$variants = [];
			while ($v = $v_data->next()) {
				$variant = $v->row();
				$variant['prices'] = $this->getPrices($variant['id']);
				$r_lang = $this->Database->prepare('SELECT id, name, description, language FROM tl_iso_product p WHERE p.pid = ? AND language <> "" ORDER BY id')->execute($variant['id']);
				$variant['translations'] = [];
				while ($r = $r_lang->next()) {
					$variant['translations']['item|language:' . $r->language] = ['name' => $r->name, 'description' => $r->description, 'contao-id' => $r->id];
				}
				$variants[] = array_filter($variant);
				// $variants[] = $variant;
			}
			// Open new XML-range for current product:
			$this->xml_writer->startElementNS('isotope', 'product', null);
			if ($ident[1]) {
				$this->xml_writer->writeAttribute('id', $ident[1]);
			}
			$this->xml_writer->writeAttribute('id-type', $ident[0]);
			// Process the current product and its variants:
			for ($i = -1; $i < count($variants); $i++) {
				$this->xml_writer->startElementNS('isotope', $i == -1 ? 'main' : 'variant', null);
				$row = ($i == -1) ? $p_data : $variants[$i];
				if ($i >= 0) {
					$this->xml_writer->writeAttribute('variant-index', $i);
				}
				// Split the data of the current record into different ranges:
				$ranges = [
					'skip'			=> [],
					'main'			=> [],
					'attributes'	=> [],
					'contao-data'	=> [],
				];
				foreach ($row as $key => $value) {
					if ($key == $ident[0]) {
						$ranges['skip'][$key] = $value;
					}
					else if (array_search($key, static::$db_product_special) !== false) {
						$ranges['contao-data'][$key] = $value;
						if ($i == -1) {
							switch ($key) {
								case 'type':		$ranges['main']['producttype'] = $value;	break;
								case 'gid':			$ranges['main']['group'] = $value;			break;
								case 'orderPages':	$ranges['main']['categories'] = $value;		break;
							}
						}
					}
					else if ($this->getAttribute($key) != null) {
						$ranges['attributes'][$key] = $value;
					}
					else {
						$ranges['main'][$key] = $value;
					}
				}
				// Append all current data to the XML-file:
				foreach ($ranges as $range => $data) {
					if ($range == 'skip' || !is_array($data) || count($data) == 0) {
						continue;
					}
					if ($range != 'main') {
						$this->xml_writer->startElementNS('isotope', $range, null);
					}
					foreach ($data as $key => $value) {
						$this->writeXmlValue([$key, $value]);
					}
					if ($range != 'main') {
						$this->xml_writer->endElement();
					}
				}
				$this->xml_writer->endElement();
			}
			// Close XML-range </isotope:product>
			$this->xml_writer->endElement();
		}

		$this->xml_writer->endElement();
		$this->xml_writer->flush();

		unset($this->xml_writer);
		$this->xml_writer = null;

		try {
			$zip = new \ZipArchive;
			$res = $zip->open($exportzip, \ZipArchive::CREATE);
			if ($res === true) {
				$zip->addFile($exportfile, "{$this->exportFile}.xml");
				$zip->close();
			}
		}
		catch (\Throwable $error) {

		}

		unlink($exportlock);
	}

	protected function checkExportFile() {

		list($max_ts, $max_table) = $this->getIsotopeTimestamp(true);
		if (abs(time() - $max_ts) < 120) {
			return ['success' => false, 'code' => 'create-export-abort-busy', 'message' => "Table '{$max_table}' was just altered - waiting..."];
		}

		try {
			$dir = $this->prepareTmpDir();
		}
		catch (\Throwable $error) {
			return ['success' => false, 'code' => 'create-export-failed', 'message' => $error->getMessage()];
		}

		$exportfile	= "$dir/{$this->exportFile}.xml";
		$exportts	= "$dir/{$this->exportFile}.ts";
		$exportlock	= "$dir/{$this->exportFile}.lock";

		if (file_exists($exportlock)) {
			return ['success' => false, 'code' => 'create-export-progressing'];
		}
		if (!file_exists($exportfile) || !file_exists($exportts)) {
			try {
				$this->createExportfile();
			}
			catch (\Throwable $error) {
				return ['success' => false, 'code' => 'create-export-failed', 'message' => $error->getMessage()];
			}
			return ['success' => true, 'code' => 'create-export-successful', 'file' => $exportfile];
		}

		$ts = file_get_contents($exportts);
		if (!is_numeric($ts)) {
			try {
				$this->createExportFile();
			}
			catch (\Throwable $error) {
				return ['success' => false, 'code' => 'create-export-failed', 'message' => $error->getMessage()];
			}
			return ['success' => true, 'code' => 'create-export-successful', 'file' => $exportfile];
		}

		if ($max_ts == $ts) {
			return ['success' => true, 'code' => 'create-export-ready', 'file' => $exportfile];
		}

		try {
			$this->createExportfile();
		}
		catch (\Throwable $error) {
			return ['success' => false, 'code' => 'create-export-failed', 'message' => $error->getMessage()];
		}
		return ['success' => true, 'code' => 'create-export-successful', 'file' => $exportfile];
	}

	protected function downloadExportFile() {

		try {
			$dir = $this->prepareTmpDir();
			$exportzip	= "$dir/{$this->exportFile}.zip";
			$exportfile	= "$dir/{$this->exportFile}.xml";
			if (file_exists($exportzip)) {
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="isotope-export.zip"');
				readfile($exportzip);
				exit;
			}
			if (file_exists($exportfile)) {
				header('Content-Type: application/xml');
				header('Content-Disposition: attachment; filename="isotope-export.xml"');
				readfile($exportfile);
				exit;
			}
		}
		catch (\Throwable $error) {
			return;
		}
	}

	protected function checkImportFile() {

		try {
			$dir = $this->prepareTmpDir();
		}
		catch (\Throwable $error) {
			return ['success' => false, 'code' => 'check-import-failed', 'message' => $error->getMessage()];
		}

		$importfile = "$dir/{$this->importFile}.xml";
		$importts	= "$dir/{$this->importFile}.ts";
		$importname = "$dir/{$this->importFile}.name";

		$name = (file_exists($importname)) ? file_get_contents($importname) : '';

		if (file_exists($importfile) && file_exists($importts)) {
			return ['success' => true, 'code' => 'check-import-ready', 'ts' => file_get_contents($importts), 'file' => $name];
		}

		return ['success' => false, 'code' => 'check-import-missing'];
	}

	protected function uploadImportFile() {

		try {
			$dir = $this->prepareTmpDir();
			$importfile = "$dir/{$this->importFile}.xml";
			$importzip	= "$dir/{$this->importFile}.zip";
			$importts	= "$dir/{$this->importFile}.ts";
			$importname	= "$dir/{$this->importFile}.name";

			if (array_key_exists('import', $_FILES) && is_uploaded_file($_FILES['import']['tmp_name'])) {
				foreach ([$importfile, $importzip, $importts, $importname] as $file) {
					if (file_exists($file)) {
						unlink($file);
					}
					if (file_exists($file)) {
						throw new \Error('Clean up failed');
					}
				}
				switch ($_FILES['import']['type']) {
					case 'application/zip':
					case 'application/x-zip-compressed':
						move_uploaded_file($_FILES['import']['tmp_name'], $importzip);
						try {
							$zip = new \ZipArchive;
							if ($zip->open($importzip) === true) {
								for ($i = 0; $i < $zip->numFiles; $i++) {
									$filename = $zip->getNameIndex($i);
									$fileinfo = pathinfo($filename);
									if (strtolower($fileinfo['extension']) == 'xml') {
										copy("zip://$importzip#$filename", $importfile);
										break;
									}
								}
								$zip->close();
							}
						}
						catch (\Throwable $error) {

						}
						break;
					case 'application/xml':
						move_uploaded_file($_FILES['import']['tmp_name'], $importfile);
						break;
				}
				if (file_exists($importfile)) {
					file_put_contents($importts, time());
					file_put_contents($importname, $_FILES['import']['name']);
				}
			}
		}
		catch (\Throwable $error) {

		}
	}

	protected function removeImportFile() {

		try {
			$dir = $this->prepareTmpDir();
			$importfile = "$dir/{$this->importFile}.xml";
			$importzip	= "$dir/{$this->importFile}.zip";
			$importts	= "$dir/{$this->importFile}.ts";
			$importname	= "$dir/{$this->importFile}.name";

			foreach ([$importfile, $importzip, $importts, $importname] as $file) {
				if (file_exists($file)) {
					unlink($file);
					if (file_exists($file)) {
						throw new \Error('Clean up failed');
					}
				}
			}
		}
		catch (\Throwable $error) {

		}
	}

	protected function xmlNodeToArray($xml_reader, $include_attributes = false, $allow_deeper = 10) {

		if ($include_attributes) {
			$a_attributes = [];
			$s_attributes = $xml_reader->localName;
			if ($xml_reader->hasAttributes && $xml_reader->moveToFirstAttribute()) {
				do {
					$a_attributes[$xml_reader->name] = $xml_reader->value;
					$s_attributes .= '|' . str_replace([':','|'], '_', $xml_reader->name) . ':' . str_replace('|', '_', $xml_reader->value);
				} while ($xml_reader->moveToNextAttribute());
				$xml_reader->moveToElement();
			}
		}

		// if ($xml_reader->nodeType != \XMLReader::ELEMENT) {
		// 	throw new \Error('Unexpected XML structure');
		// }
		if ($allow_deeper <= 0) {
			throw new \Error('XML structure is too complex');
		}

		// $xml_start_depth = $xml_reader->depth;
		$data = null;
		if (!$xml_reader->isEmptyElement) {
			while (1) {
				if (!$xml_reader->read()) {
					throw new \Error('Unexpected end of XML structure');
				}
				if ($xml_reader->nodeType == \XMLReader::END_ELEMENT) {
					// if ($xml_reader->depth != $xml_start_depth) {
					// 	throw new \Error(sprintf('Unexpected node-end in XML-structure (%s, %d != %d) [%d]', $xml_reader->name, $xml_reader->depth, $xml_start_depth, $allow_deeper));
					// }
					break;
				}
				switch ($xml_reader->nodeType) {
					case \XMLReader::TEXT:
						// if ($data != null) {
						// 	throw new \Error('Unexpected text-node in XML structure');
						// }
						$data = $xml_reader->value;
						break;
					case \XMLReader::ELEMENT:
						if ($data == null) {
							$data = [];
						}
						// if ($xml_reader->depth != $xml_start_depth + 1) {
						// 	throw new \Error(sprintf('Unexpected node-begin in XML-structure (%s, %d, %d)', $xml_reader->name, $xml_reader->depth, $xml_start_depth));
						// }
						$name = $xml_reader->localName;
						$node = $this->xmlNodeToArray($xml_reader, true, $allow_deeper - 1);
						if ($name == 'item' && count($node['attributes']) == 1 && array_key_exists('index', $node['attributes'])) {
							$data[$node['attributes']['index']] = $node['data'];
						}
						else {
							$data[$node['name_with_attributes']] = $node['data'];
						}
						break;
				}
			}
		}

		return ($include_attributes) ? ['attributes' => $a_attributes, 'name_with_attributes' => $s_attributes, 'data' => $data] : $data;
	}

	protected function analyseImportItem() {

		// Count import-items:
		$res = $this->Database->execute('SELECT COUNT(*) as n FROM tl_isobackup WHERE import_id IS NOT NULL')->first();
		if (!$res) {
			throw new \Error('Can\'t determine number of import-items.');
		}
		$n_total = $res->n;
		$res = $this->Database->execute('SELECT COUNT(*) as n FROM tl_isobackup WHERE status = "created" AND import_id IS NOT NULL')->first();
		if (!$res) {
			throw new \Error('Can\'t determine number of waiting import-items.');
		}
		$n_waiting = $res->n;

		if ($n_waiting == 0) {
			return 1;
		}
		$progress = (1 - ($n_waiting - 1) / $n_total);

		// Get next import-item:
		$item = $this->Database->execute('SELECT id, import_id, isotope_id, data, actions FROM tl_isobackup WHERE status = "created" AND import_id IS NOT NULL LIMIT 1')->first();
		if (!$item) {
			throw new \Error('Failed to grab waiting import-item out of database.');
		}
		if (!$this->Database->prepare('UPDATE tl_isobackup SET status = "preparing" WHERE id = ?')->execute($item->id)) {
			throw new \Error('Failed to update status of current import-item.');
		}

		$data = [];
		$xml = new \XMLReader;
		if (!$xml->XML('<?xml version="1.0" encoding="UTF-8"?' . '>' . $item->data) || !$xml->read()) {
			throw new \Error('Loading XML with XMLReader failed');
		}
		while ($xml->nodeType != \XMLReader::NONE) {
			if ($xml->nodeType == \XMLReader::ELEMENT && $xml->prefix == 'isotope') {
				switch ($xml->localName) {
					case 'main':
						$data['main'] = $this->xmlNodeToArray($xml);
						break;
					case 'variant':
						if (!array_key_exists('variants', $data)) {
							$data['variants'] = [];
						}
						if ($xml->hasAttributes && $xml->moveToAttribute('variant-index')) {
							$i = $xml->value;
							$xml->moveToElement();
							$data['variants'][$i] = $this->xmlNodeToArray($xml);
						}
						else {
							$data['variants'][] = $this->xmlNodeToArray($xml);
						}
				}
			}
			if (!$xml->read()) {
				break;
			}
		}
		$xml->close();
		unset($xml);

		if (!$this->Database->prepare('UPDATE tl_isobackup SET data = ? WHERE id = ?')->execute(json_encode($data), $item->id)) {
			throw new \Error('Failed to update data of current import-item.');
		}

		// Get alias:
		$id = explode(':', $item->import_id, 2);
		if (!is_array($id) || count($id) != 2 || $id[0] == '' || $id[1] == '') {
			throw new \Error('Can\'t determine identifier of current import-item.');
		}

		// Look for matching Isotope-product:
		switch ($id[0]) {
			case 'alias':
			case 'sku':
			case 'name':
				$isotope = $this->Database->prepare("SELECT id FROM tl_iso_product WHERE pid = 0 AND {$id[0]} = ?")->execute($id[1])->first();
				break;
			default:
				throw new \Error('Identifier of current import-item has invalid type');
		}
		if (!$isotope) {
			if (!$this->Database->prepare('UPDATE tl_isobackup SET actions = ?, status = "prepared" WHERE id = ?')
						->execute(json_encode(['import-everything']), $item->id)) {
				throw new \Error('Failed to save needed actions with current import-item.');
			}
			return $progress;
		}
		else if ($isotope->count() != 1) {
			if (!$this->Database->prepare('UPDATE tl_isobackup SET actions = ?, status = "prepared" WHERE id = ?')
						->execute(json_encode(['error' => 'more than one isotope-product match import-id']))) {
				throw new \Error('Failed to save needed actions with current import-item.');
			}
			return $progress;
		}
		if (!$this->Database->prepare('UPDATE tl_isobackup SET isotope_id = ? WHERE id = ?')->execute($isotope->id, $item->id)) {
			throw new \Error('Failed to save found Isotope-id with current import-item.');
		}



		//

		if (!$this->Database->prepare('UPDATE tl_isobackup SET status = "prepared" WHERE id = ?')->execute($item->id)) {
			throw new \Error('Failed to update status of current import-item.');
		}

		return $progress;
	}

	protected function analyseImport($step) {

		\System::loadLanguageFile('tl_isobackup');

		$code = null;
		$message = null;
		$progress = null;
		$error = null;
		$repeat_step = false;

		$step_flow = [
			'init'				=> ['cleanup',			'analysis-started'],
			'cleanup'			=> ['read-xml',			'analysis-readxml'],
			'read-xml'			=> ['analyse-import',	'analysis-import'],
			'analyse-import'	=> ['analyse-isotope',	'analysis-isotope'],
			'analyse-isotope'	=> ['successful',		'analysis-successful'],
			'successful'		=> null
		];

		try {
			switch ($step) {
				case 'init':
					$dir = $this->prepareTmpDir();
					$importfile = "$dir/{$this->importFile}.xml";
					if (!file_exists($importfile) || filesize($importfile) == 0) {
						return ['message' => 'Can\'t find import-file or XML-data empty.'];
					}
					$progress = 0;
					break;
				case 'cleanup':
					$this->Database->execute('DELETE FROM tl_isobackup');
					$this->Database->execute('ALTER TABLE tl_isobackup DROP id');
					$this->Database->execute('ALTER TABLE tl_isobackup ADD id int(10) unsigned NOT NULL auto_increment primary key first');
					$this->Database->prepare('INSERT INTO tl_isobackup (status,data,tstamp) VALUES (?,?,?)')->execute("setup", json_encode(['ts' => time(), 'status' => 'analysing']), time());
					$progress = 2;
					break;
				case 'read-xml':
					$dir = $this->prepareTmpDir();
					$importfile = "$dir/{$this->importFile}.xml";
					$xml = new \XMLReader;
					if (!$xml->open("file://$importfile") || !$xml->read()) {
						throw new \Error("Opening XML with XMLReader failed");
					}
					while ($xml->nodeType != \XMLReader::NONE) {
						if ($xml->nodeType == \XMLReader::ELEMENT) {
							if ($xml->name == 'isotope:product') {
								$element = $xml->readOuterXml();
								$xml->moveToAttribute('id');
								$id = $xml->value;
								$xml->moveToAttribute('id-type');
								$id_type = $xml->value;
								$xml->moveToElement();
								$this->Database
									->prepare('INSERT INTO tl_isobackup (status,import_id,data,tstamp) VALUES (?,?,?,?)')
									->execute('created',"$id_type:$id",$element,time());
								if ($xml->next()) {
									continue;
								}
								else {
									break;
								}
							}
						}
						if (!$xml->read()) {
							break;
						}
					}
					$xml->close();
					$progress = 10;
					break;
				case 'analyse-import':
					$sub_progress = $this->analyseImportItem();
					$repeat_step = ($sub_progress < 1);
					$progress = 10 + 60 * $sub_progress;
					break;
				case 'analyse-isotope':
					$progress = 100;
					break;
				case 'successful':
					break;
				case 'test':
					$dir = $this->prepareTmpDir();
					$importfile = "$dir/{$this->importFile}.xml";
					$xml = new \XMLReader;
					if (!$xml->open("file://$importfile") || !$xml->read()) {
						throw new \Error("Opening XML with XMLReader failed");
					}
					for ($i = 0; $i < 1000; $i++) {
						if ($xml->nodeType == \XMLReader::NONE) {
							break;
						}
						echo sprintf('<p>%d [%d] %s: %s</p>', $xml->depth, $xml->nodeType, $xml->name, $xml->value);
						if (!$xml->read()) {
							break;
						}
					}
					$xml->close();
					break;
				default:
					return ['message' => "Error: Invalid analysis-step ($step)", 'progress' => 100];
			}
		}
		catch (\Throwable $error) {
			return ['message' => 'Error: ' . $error->getMessage()];
		}

		$res = [];
		if ($repeat_step) {
			$res['next_step'] = $step;
		}
		elseif (is_array($step_flow[$step]) && count($step_flow[$step]) >= 2) {
			if ($step_flow[$step][0]) {
				$res['next_step'] = $step_flow[$step][0];
			}
			if ($message) {
				$res['message'] = $message;
			}
			elseif ($code || $step_flow[$step][1]) {
				$res['message'] = $GLOBALS['TL_LANG']['tl_isobackup'][$code ? $code : $step_flow[$step][1]];
			}
		}
		if ($progress || $progress === 0) {
			$res['progress'] = $progress;
		}
		return $res;
	}

	/**
	 * Generate the module's output
	 *
	 * @return	string
	 */
	public function generate() {

		switch (\Input::get('action')) {
			case 'download':
				$this->downloadExportFile();
				break;
			case 'upload':
				$this->uploadImportFile();
				break;
			case 'cleanup-upload':
				$this->removeImportFile();
				break;
			case 'analysis':
				if (\Input::get('step')) {
					die(json_encode($this->analyseImport(\Input::get('step'))));
				}
				break;
		}

		if (!$this->Template->lexA) {
			$this->Template->lexA = "generate";
		}
		else {
			$this->Template->lexA .= ",generate";
		}

		// $return = [
		// 	'introduction' => [],
		// ];

		// if (\BackendUser::getInstance()->isAdmin) {
        //     $objTemplate = new \BackendTemplate('be_isobackup_introduction');

		// 	$return['introduction']['label'] = &$GLOBALS['TL_LANG']['tl_isobackup']['title_legend'];
        //     $return['introduction']['html']  = $objTemplate->parse();
		// }

		$GLOBALS['TL_CSS'][] = 'bundles/hoerelectroniccontaoimport/style.css';
		$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/hoerelectroniccontaoimport/fx.js';
		\System::loadLanguageFile('tl_isobackup');

		return parent::generate();
	}

	/**
	 * This function prepares data which is needed by the template $strTemplate.
	 * The data has to be stored as properties of $this->Template.
	 **/
	protected function compile() {
		$this->Template->referer = \Input::get('ref');
		$this->Template->exportReady = $this->checkExportFile();
		$this->Template->importReady = $this->checkImportFile();

		if ($this->Template->importReady['ts']) {
			$this->Template->importReadyMoment = sprintf(
				$GLOBALS['TL_LANG']['tl_isobackup']['import-timestamp-date'],
				date('r', $this->Template->importReady['ts'])
			);
			$diff = time() - $this->Template->importReady['ts'];
			if ($diff <= 120) {
				$this->Template->importReadyMoment = sprintf($GLOBALS['TL_LANG']['tl_isobackup']['import-timestamp-seconds'], $diff);
			}
			else if ($diff <= 120 * 60) {
				$this->Template->importReadyMoment = sprintf($GLOBALS['TL_LANG']['tl_isobackup']['import-timestamp-minutes'], floor($diff / 60));
			}
			else if ($diff <= 48 * 3600) {
				$this->Template->importReadyMoment = sprintf($GLOBALS['TL_LANG']['tl_isobackup']['import-timestamp-hours'], floor($diff / 3600));
			}
			else if ($diff <= 14 * 86400) {
				$this->Template->importReadyMoment = sprintf($GLOBALS['TL_LANG']['tl_isobackup']['import-timestamp-days'], floor($diff / 86400));
			}
		}

		if (!$this->Template->lexA) {
			$this->Template->lexA = "compile";
		}
		else {
			$this->Template->lexA .= ",compile";
		}
	}
}
