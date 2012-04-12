<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#

class Mage_Thub_Model_Run_Xmltag {

   var $refID;         // Unique ID number of the tag
   var $name;         // Name of the tag
   var $attributes = array();   // Array (assoc) of attributes for this tag
   var $tags = array();      // An array of refID's for children tags
   var $contents;         // textual (CDATA) contents of a tag
   var $children = array();   // Collection (type: xml_tag) of child tag's


   function Xml_tag(&$document,$tag_name,$tag_attrs=array(),$tag_contents='') {
      // Constructor function for xml_tag class


      // Set object variables
      $this->name = $tag_name;
      $this->attributes = $tag_attrs;
      $this->contents = $tag_contents;

      $this->tags = array();         // Initialize children array/collection
      $this->children = array();
   }

   function addChild (&$document,$tag_name,$tag_attrs=array(),$tag_contents='') {
      // Adds a child tag object to the current tag object


      // Create child instance
      $count = count($this->children);
      $this->children[$count] = Mage::getModel('thub/run_xmltag');
      $this->children[$count]->Xml_tag($document,$tag_name,$tag_attrs,$tag_contents);

      // Add object reference to document index
      $document->xml_index[$document->xml_reference] =& $this->children[(count($this->children) - 1)];

      // Assign document index# to child
      $document->xml_index[$document->xml_reference]->refID = $document->xml_reference;

      // Add child index# to parent collection of child indices
      array_push($this->tags,$document->xml_reference);

      // Update document index counter
      $document->xml_reference++;

      // Return child index#
      return ($document->xml_reference - 1);
	}
}

?>