<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#

   class Mage_Thub_Model_Run_Error {
      protected $xmlResponse;
      protected $xmlRoot;
      protected $xmlEnvelope;

      protected function _construct(){
      }


      public  function xmlErrorResponse($command, $code, $message, $provider="", $request_id='') {
        header("Content-type: application/xml");
         $this->xmlResponse = Mage::getModel('thub/run_thubxml');
         $this->xmlResponse->loadString('<?xml version="1.0" encoding="UTF-8"?>');
         $this->xmlRoot = $this->xmlResponse->createTag("RESPONSE", array('Version'=>'4.1'));
         $this->xmlEnvelope = $this->xmlResponse->createTag("Envelope", array(), '', $this->xmlRoot);
         $this->xmlResponse->createTag("Command", array(), $command, $this->xmlEnvelope);
         $this->xmlResponse->createTag("StatusCode", array(), $code, $this->xmlEnvelope);
         $this->xmlResponse->createTag("StatusMessage", array(), $message, $this->xmlEnvelope);
         //$this->xmlResponse->createTag("Provider", array(), $provider, $this->xmlEnvelope);
         $this->xmlResponse->createTag("Provider", array(), "Magento", $this->xmlEnvelope);
         return $this->xmlResponse->generate();
      }
   }

?>