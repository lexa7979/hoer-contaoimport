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
class Export extends \BackendModule {

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

	/**
	 *
	 *
	 **/
	public function generate() {

		// $include_contao = false;

		header('Content-Type: application/xml');
		header('Content-Disposition: inline; filename="isotope.xml"');

		$this->xml_writer = new \XMLWriter();
		$this->xml_writer->openURI('php://output');

		$this->xml_writer->setIndent(true);
		$this->xml_writer->startDocument('1.0', 'UTF-8');
		$this->xml_writer->startElementNS('isotope', 'product-list', 'http://hoer-electronic.de');

		$products = $this->Database->prepare('SELECT * FROM tl_iso_product p WHERE NOT p.pid > 0 ORDER BY id')->execute();
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

// var_dump($i, $row);continue;

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
