<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#

class Mage_Thub_Model_Run_Thubxml{// xml_doc {
	var $parser;			// Object Reference to parser
	var $xml;				// Raw XML code
	var $version;			// XML Version
	var $encoding;			// Encoding type
	var $dtd;				// DTD Information
	var $entities;			// Array (key/value set) of entities
	var $xml_index;			// Array of object references to XML tags in a DOC
	var $xml_reference;		// Next available Reference ID for index
	var $document;			// Document tag (type: xml_tag)
	var $stack;			// Current object depth (array of index numbers)

	function _constructor($xml='') {
		if (!empty($xml)){
   		$this->loadString($xml);
		}
	}

	public function loadString($xml=''){
		// XML Document constructor


		$this->xml = $xml;

		// Set default values
		$this->version = '1.0';
	   $this->encoding = "ISO-8859-1";
		//$this->encoding = "UTF-8";
		$this->dtd = '';
		$this->entities = array();
		$this->xml_index = array();
		$this->xml_reference = 0;
		$this->stack = array();
	}

	public function parse() {
		// Creates the object tree from XML code


		$this->parser = xml_parser_create($this->encoding);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "startElement", "endElement");
		xml_set_character_data_handler($this->parser, "characterData");
		xml_set_default_handler($this->parser, "defaultHandler");

		if (!xml_parse($this->parser, $this->xml)) {
			// Error while parsing document

			$err_code = xml_get_error_code($this->parser);
			$err_string = xml_error_string($this->parser);
			$err_line = xml_get_current_line_number($this->parser);
			$err_col = xml_get_current_column_number($this->parser);
			$err_byte = xml_get_current_byte_index($this->parser);
			print "<p><b>Error Code:</b> $err_code<br>$err_string<br><b>Line:</b> $err_line <b>Column:</b> $err_col</p>";
		}

		xml_parser_free($this->parser);
	}

	public function generate() {
		// Generates XML string from the xml_doc::document object


		// Create document header
		if ($this->version == '' and $this->encoding == '') {
			// $out_header = '<' . '?xml ?' . ">\n";
			$out_header = '';
		} elseif ($this->version != '' and $this->encoding == '') {
			$out_header = '<' . "?xml version=\"{$this->version}\"?" . ">\n";
		} else {
			$out_header = '<' . "?xml version=\"{$this->version}\" encoding=\"{$this->encoding}\"?" . ">\n";
		}

		if ($this->dtd != '') {
			$out_header .= "<!DOCTYPE " . $this->dtd . ">\n";
		}

		// Get reference for root tag
		$_root =& $this->xml_index[0];

		// Create XML for root tag
		$this->xml = $this->createXML(0);

		return $out_header . $this->xml;
	}

	public function stack_location() {
		// Returns index for current working tag


		return $this->stack[(count($this->stack) - 1)];
	}

	public function startElement($parser, $name, $attrs=array()) {
		// Process a new tag


		// Check to see if tag is root-level
		if (count($this->stack) == 0) {
			// Tag is root-level (document)

			$this->document = Mage::getModel('thub/run_xmltag');

			$this->document->Xml_tag($this,$name,$attrs);

			$this->document->refID = 0;

			$this->xml_index[0] =& $this->document;
			$this->xml_reference = 1;

			$this->stack[0] = 0;

		} else {
			// Get current location in stack array
			$parent_index = $this->stack_location();

			// Get object reference to parent tag
			$parent =& $this->xml_index[$parent_index];

			// Add child to parent
			$parent->addChild($this,$name,$attrs);

			// Update stack
			array_push($this->stack,($this->xml_reference - 1));
		}

	}

	public function endElement($parser, $name) {
		// Update stack


		array_pop($this->stack);
	}

	public function characterData($parser, $data) {
		// Add textual data to the current tag


		// Get current location in stack array
		$cur_index = $this->stack_location();

		// Get object reference for tag
		$tag =& $this->xml_index[$cur_index];

		// Assign data to tag
		$tag->contents .= $data;
	}

	public function defaultHandler($parser, $data) {

	}

	public function createTag($name, $attrs=array(), $contents='', $parentID = '', $encode = false) {
		// Creates an XML tag, returns Tag Index #
		if ($encode) {
			$contents = base64_encode($contents);
			foreach ($attrs as $k=>$v){
				$attrs[$k] = base64_encode($v);
			}
			$attrs['encoding'] = "yes";
		}


		if ($parentID === '') {
			// Tag is root-level

//        $this->xmlResponse = Mage::getModel('thub/run_thubxml');
			$this->document = Mage::getModel('thub/run_xmltag');

			$this->document->Xml_tag($this,$name,$attrs,$contents);
			$this->document->refID = 0;

			$this->xml_index[0] =& $this->document;
			$this->xml_reference = 1;

			return 0;
		} else {
			// Tag is a child

			// Get object reference to parent tag
			$parent =& $this->xml_index[$parentID];
			// Add child to parent
			return $parent->addChild($this,$name,$attrs,$contents);
		}
	}


	public function createXML($tagID,$parentXML='') {
		// Creates XML string for a tag object
		// Specify parent XML to insert new string into parent XML


		$final = '';

		// Get Reference to tag object
		$tag =& $this->xml_index[$tagID];

		$name = $tag->name;
		$contents = $tag->contents;
		$attr_count = count($tag->attributes);
		$child_count = count($tag->tags);
		$empty_tag = ($tag->contents == '') ? true : false;

		// Create intial tag
		if ($attr_count == 0) {
			// No attributes

			if ($empty_tag === true) {
					$final = "<$name />";
			} else {
					$final = "<$name>$contents</$name>";
			}
		} else {
			// Attributes present

			$attribs = '';
			foreach ($tag->attributes as $key => $value) {
				$attribs .= ' ' . $key . "=\"$value\"";
			}

			if ($empty_tag === true) {
				$final = "<$name$attribs />";
			} else {
				$final = "<$name$attribs>$contents</$name>";
			}
		}

		// Search for children
		if ($child_count > 0) {
			foreach ($tag->tags as $childID) {
				$final = $this->createXML($childID,$final);
			}
		}

		if ($parentXML != '') {
			// Add tag XML to parent XML

			$stop1 = strrpos($parentXML,'</');
			$stop2 = strrpos($parentXML,' />');

			if ($stop1 > $stop2) {
				// Parent already has children

				$begin_chunk = substr($parentXML,0,$stop1);
				$end_chunk = substr($parentXML,$stop1,(strlen($parentXML) - $stop1 + 1));

				$final = $begin_chunk . $final . $end_chunk;
			} elseif ($stop2 > $stop1) {
				// No previous children

				$spc = strpos($parentXML,' ',0);

				$parent_name = substr($parentXML,1,$spc - 1);

				if ($spc != $stop2) {
					// Attributes present
					$parent_attribs = substr($parentXML,$spc,($stop2 - $spc));
				} else {
					// No attributes
					$parent_attribs = '';
				}

				$final = "<$parent_name$parent_attribs>$final</$parent_name>";
			}
		}

		return $final;
	}


	function getTag($tagID,&$name,&$attributes,&$contents,&$tags) {
		// Returns tag information via variable references from a tag index#


		// Get object reference for tag
		$tag = & $this->xml_index[$tagID];

		$name = $tag->name;
		$attributes = $tag->attributes;

		if (is_array($attributes) && array_key_exists("ENCODING", $attributes) && ($attributes['ENCODING'] == 'yes')) {
			foreach ($attributes as $k=>$v){
				if ($k != 'ENCODING'){
					$tag->attributes[$k] = base64_decode($v);
				}
			}
			$tag->contents = base64_decode($tag->contents);
		}
		$attributes = $tag->attributes;
		$contents = $tag->contents;
		$tags = $tag->tags;
	}

	function getChildByName($parentID,$childName,$startIndex=0) {
		// Returns child index# searching by name


		// Get reference for parent
		$parent =& $this->xml_index[$parentID];

		if ($startIndex > count($parent->tags)) return false;


		for ($i = $startIndex; $i < count($parent->tags); $i++) {
			$childID = $parent->tags[$i];

			// Get reference for child
			$child =& $this->xml_index[$childID];

			if ($child->name == $childName) {
				// Found child, return index#

				return $childID;
			}
		}
	}

}




?>
