<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#


   class Mage_Thub_Model_Run_Inventory
   {

       const TAG_ADD_PRODUCTS               =  "ADDPRODUCTS";
       const TAG_ADD_CATEGORY               =  "ADDCATEGORY";
       const TAG_ADD_MANUFACTURER           =  "ADDMANUFACTURER";
       const TAG_UP_DESCRIPTION             =  "UPDATEDESCRIPTION";
       const TAG_UP_PRICE                   =  "UPDATEPRICE";
       const TAG_UP_INVENTORY               =  "UPDATEINVENTORY";
       const TAG_SECURITY_KEY               =  "SECURITYKEY";
       const TAG_ITEMS_ITEM_CATEGORY        =  "CATEGORY";
       const TAG_ITEMS_ITEM_ITEMNAME        =  "ITEMNAME";
       const TAG_ITEMS_ITEM_MANUFACTURER    =  "MANUFACTURER";
       const TAG_ITEMS_ITEM_ITEMDESCRIPTION =  "ITEMDESCRIPTION";
       const TAG_ITEMS_ITEM_PRICE           =  "PRICE";
       const TAG_ITEMS_ITEM_SALEPRICE       =  "SALEPRICE";
       const TAG_ITEMS_ITEM_QUANTITYINSTOCK =  "QUANTITYINSTOCK";
       const TAG_ITEMS_ITEM_WEIGHT          =  "UNITWEIGHT";
       const TAG_ITEMS_ITEM_ITEMCODE        =  "ITEMCODE";
       const TAG_ITEMS_ITEM_ITEMCODEPARENT  =  "ITEMCODEPARENT";


       const OPTION_IS_UNIQUE                     =  "0";
       const OPTION_IS_REQUIRED                   =  "0";
       const OPTION_IS_CONFIGURABLE               =  "0";
       const OPTION_IS_CONFIGURABLE_YES           =  "1";
       const OPTION_IS_SEARCHABLE                 =  "0";
       const OPTION_IS_VISIBLE_IN_ADVANCED_SEARCH =  "0";
       const OPTION_IS_COMPARABLE                 =  "0";
       const OPTION_IS_FILTERABLE                 =  "0";
       const OPTION_IS_VISIBLE_ON_FRONT           =  "1";


       const OPTION_VISIBLE_SIMPLE_PRODUCT        =  "1";
       const OPTION_VISIBLE_CONFIGURABLE_PRODUCT  =  "4";    // 1- nowhere; 2- catalog, 3- search, 4 catalog&search

       const OPTION_SCOPE_IN_STORE_VIEW           =  "0";
       const OPTION_SCOPE_IN_WEBSITE              =  "2";
       const OPTION_SCOPE_IN_GLOBAL               =  "1";


       const PRODUCT_TAX_CLASS_NAME_DEFAULT       = "taxable goods";

       const SET_WEBSITE_DEFAULT                  = 1; // set website default
       const SET_PRODUCT_STATUS                   = 1;  // enable -1, disable -2
       const SET_CONFIGURABLE_PRODUCT_STATUS      = 2;  // enable -1, disable -2




       protected $item_tag   = array( "ITEMNAME",
                                      "MANUFACTURER",
                                      "ITEMCODE",
                                      "CATEGORY",
                                      "ITEMCODEPARENT",
                                      "ITEMDESCRIPTION",
                                      "PRICE",
                                      "QUANTITYINSTOCK");

       protected $itemTagValue = array ();
       protected $_thub;
       protected $_xmlRequest = null;
       protected $_xmlResponse = null;
       protected $RequestParams = array();
       protected $root,$envelope,$itemsTag;
       protected $listCategories = array();
       protected $newCategories = array();
       protected $newCategories2 = array();
       protected $addIndex = null;
       protected $addPath = null;
       protected $itemsTagResponse = null;
       protected $data = array();
       protected $options = array();
       protected $arrayItems = array();
       protected $QuantityInStockWEB;
       protected $AddedOptionInSession = array(); // ������ ����������� ����� �� ������� ������

       protected $Atribute_set_collection =null;
       protected $Atribute_set_default =null;
       protected $Atribute_set_thub =array();
       protected $AddedSetAtribute = null;
       protected $NewProductIsConfigurable = false;
       protected $ParentProductIsConfigurable = false;
       protected $TaxClassID = null;

       protected $CurrentItem = null;

       protected $stores =null;
       protected $storeId =0;  // default store

       protected $Attributes          = null;
       protected $Attribute           = null;

      public function init($RequestParams=array()){
          $this->version = Mage::getVersion();
          //$version = '1.3.2.3';
          //$this->isVersionPatch01 = $this->version=='1.3.2.8' || $this->version=='1.3.2.7' || $this->version=='1.3.2.6' || $this->version=='1.3.2.5' || $this->version=='1.3.2.4' || $this->version=='1.3.2.3' || $this->version=='1.3.1.1' || $this->version=='1.3.2.1' || $this->version=='1.3.2.2' || $this->version=='1.3.2' || $this->version=='1.3.0' || $this->version=='1.3.1' || $this->version=='1.4.0.1';
          //SET THIS IF MAGENTO VERSION LESS THAN 1.3.2.8
          //$this->isVersionPatch01 = false;
          
          //SET THIS IF MAGENTO VERSION higher THAN 1.3.2.8
          $this->isVersionPatch01 = true;
         
         if (is_array($RequestParams)){
            $this->RequestParams =$RequestParams;
           return true;
         }else{
           return false;
         }
      }
       /**
        * Set XML Request
        *
        * @param   Mage_Thub_Model_Run_Thubxml $xmlRequest
        * @return  boolean
        */
       public function setXmlRequest (Mage_Thub_Model_Run_Thubxml $xmlRequest){
          $this->_xmlRequest = $xmlRequest;
          return true;
       }
       /**
        * Get XML Response
        *
        * @return Mage_Thub_Model_Run_Thubxml
        */
       public function getXmlResponse (){
   //       $this->_xmlRequest = $xmlRequest;
          return (is_object($this->_xmlResponse)?$this->_xmlResponse:false);
       }
       /**
        * Inventory
        *
        * @return
        */
       public function runInventory (){
          if (count($this->RequestParams)==0){
             $this->loadParameters();
          }   else{
             $this->loadParameters();
          }
          $this->CreateHeaderXml();
          $this->initStore();
//          $this->testInventory();
          $this->mainInventory();

       }

       public function showXML(){
          header("Content-type: application/xml");
          print $this->_xmlResponse->generate();
       }

       public function initStore(){
       		//MJ 03092009
       		//StoreID has to be zero otherwise Magento does not display products in customer site
       		$this->storeId = 0;
          /*
          if (isset($this->RequestParams[self::TAG_SECURITY_KEY])){
             if (is_numeric((int)$this->RequestParams[self::TAG_SECURITY_KEY])){
                $this->storeId = (int)$this->RequestParams[self::TAG_SECURITY_KEY];
             }else{
                $this->storeId = 0;
             }
          }
					*/

          $stores = Mage::getModel('core/store')
                ->getResourceCollection()
                ->setLoadDefault(true)
                ->load()
                ->toArray();

//          var_dump($this->storeId);

       }








       public function testInventory (){

/*         if($_catalogs = Mage::getModel('catalog/category')
               ->loadByAttribute("name", "454545")){

            echo("<pre>")  ;
                var_dump( $_catalogs->toArray());
            echo("</pre>");
         }else{
            echo("Not data");
         }
*/
/*
               return false;*/


        $_collection = Mage::getModel('catalog/category')->getCollection();


        $_collection->addAttributeToSelect('name')
//            ->addAttributeToSelect('url_key')
            ->addAttributeToSelect('is_active') ;
//            ->joinUrlRewrite();

        $_collection->addAttributeToSort('name');

        $_collection->addFieldToFilter('entity_id',array("nin"=>'1'));
      //  $_collection->addFieldToFilter('name',"454545");

         foreach($_collection as $_element){
            //$_catalog
//            $_attributes=$_element->getAttribute("name");
//               echo("<pre>")  ;
//                   var_dump(  $_element->toArray(array("name","path",'entity_id')));
              //     var_dump( $_name);
//               echo("</pre>");

/*            foreach($_attributes as $_attribute){
//                $_name=$_attribute->getAttribute('path');
                $_name=$_attribute->getData('name');
               $attribute=$_attribute->toArray();
               echo("<pre>")  ;
                   var_dump( $attribute);
              //     var_dump( $_name);
               echo("</pre>");
            }
*/
         }


/*            $category   = Mage::getSingleton('catalog/category');
            $tree       = $category->getTreeModel();

            // Create copy of categories attributes for choosed store
            $tree->load();
            $root = $tree->getNodeById(0);


            foreach ($root->getAllChildNodes() as $node) {
            	$category->setStoreId(0)
            	   ->load($node->getId());
            	$category->
            }
*/



       }


       public function mainInventory (){

            $this->_xmlResponse->createTag("StoreID",   array(), "", $this->envelope, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);
            $this->_xmlResponse->createTag("StoreName", array(), "", $this->envelope, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);

            $this->_xmlResponse->createTag("StatusCode",   array(), "0", $this->envelope);
            $this->_xmlResponse->createTag("StatusMessage",   array(), "All OK", $this->envelope, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);


            // create tag ITEMS
            $items = $this->_xmlResponse->createTag("Items", array(), '', $this->root);
            // greate tag AddProduct
           // $this->_xmlResponse->createTag("AddProducts",   array(), $this->RequestParams[self::TAG_ADD_PRODUCTS], $this->root, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);


            $itemsCount = 0;
            $itemsProcessed = 0;

            $this->_xmlRequest->getTag($this->itemsTag, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);

            /////////////////////////////////
            ////                          ///
            ////  foreach for all item    ///
            ////                          ///
            /////////////////////////////////
            $error_stat=0;

/*               echo("<pre>");
            	var_dump($this->itemsTag,$_tagTags);
            	echo("</pre>");*/









         $IndexItem = 0;
         foreach($_tagTags as $k=>$itemTag){
             $this->clearMessage();

                $flItemUpdate=true;
             	 $this->options=array();
             	 $ArrayOption2 = array();


                $this->_xmlRequest->getTag($itemTag, $_tagName, $_tagAttributes, $_tagContents, $_item_tag);
                unset($this->itemTagValue);
         /*      echo("<pre>");
            	var_dump($_item_tag,$_tagName);
            	echo("</pre>");*/
                $tagValue="";
                foreach ($_item_tag  as $nameTag){
                   $array = array();
//                   $itemNameTag = $this->_xmlRequest->getChildByName($nameTag,  "ITEM");
                   $this->_xmlRequest->getTag($nameTag,  $_tagName, $_tagAttributes, $tagValue, $_orderTags);
                   if($_tagName != "ITEMOPTION") {
                      $this->itemTagValue[$_tagName] = $tagValue;
                      continue;
                   }else{
                      $array["flag"] = 1;
                      $array["option"] = $_tagAttributes['NAME'];
                      $array["option2"] = str_replace(" ","_",strtolower($_tagAttributes['NAME']));
                      $array["value"] = $tagValue;
                      $array["value2"] = str_replace(" ","_",$tagValue);

                      $this->options[str_replace(" ","_",strtolower($_tagAttributes['NAME']))]=$tagValue;
                      $ArrayOption2 [] = $array;
                   }

                }
               // ��������� �������� ���� MANUFACTURER � ������ �����
               if (isset($this->itemTagValue[self::TAG_ITEMS_ITEM_MANUFACTURER])){
                      $array["flag"] = 0;
                      $array["option"] = strtolower(self::TAG_ITEMS_ITEM_MANUFACTURER);
                      $array["option2"] = self::TAG_ITEMS_ITEM_MANUFACTURER;
                      $array["value"] = $this->itemTagValue[self::TAG_ITEMS_ITEM_MANUFACTURER];
                      $array["value2"] = str_replace(" ","_",$this->itemTagValue[self::TAG_ITEMS_ITEM_MANUFACTURER]);
               //	$this->options[strtolower(self::TAG_ITEMS_ITEM_MANUFACTURER)]= $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_MANUFACTURER];
               }

               $arrayItem = array();
               $arrayItem ['index'] = $IndexItem; $IndexItem++;
               $arrayItem ['item'] = $this->itemTagValue;
               $arrayItem ['option'] = $this->options;
               $arrayItem ['option_'] = $ArrayOption2;


               $this->arrayItems[] =  $arrayItem;
            }



         usort($this->arrayItems, array("Mage_Thub_Model_Run_Inventory", "sortItems"));

    /*        echo("<pre>");
            var_dump($this->arrayItems);
            echo("</pre>");
      */

          foreach  ($this->arrayItems as $key => $item){
             $flItemUpdate =true;
             $this->clearMessage();
             $this->Attributes = null;

/*            echo("<pre>");
//            var_dump( $this->arrayItems[$this->CurrentItem]['item'],$class,$this->RequestParams);
            var_dump($this->RequestParams[self::TAG_UP_PRICE]);
            echo("</pre>");*/

               $this->CurrentItem = $key;


               // XML RESPONSE
               $itemResponse =  $this->_xmlResponse->createTag("Item", array(), '', $items );
//               $this->_xmlResponse->createTag("ItemCode",   array(), $itemCode, $item, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);

            if($this->RequestParams[self::TAG_ADD_PRODUCTS]==1){

      //         $this->_xmlResponse->createTag("Mode",   array(), $this->arrayItems[$this->CurrentItem]['item'][$_tagName], self::TAG_ADD_PRODUCTS, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);




              // var_dump($this->getAttributes_());
              // continue;



               if (isset($item['item'][self::TAG_ITEMS_ITEM_CATEGORY])){
                  $this->CheckCategory();
               }

               if (isset($item['item'][self::TAG_ITEMS_ITEM_MANUFACTURER])){
                  $this->options = array();
               	$this->options[self::TAG_ITEMS_ITEM_MANUFACTURER] = $item['item'][self::TAG_ITEMS_ITEM_MANUFACTURER];
               	if (!$this->AddOptions()){
                     $this->addMessage("Error added option ".self::TAG_ITEMS_ITEM_MANUFACTURER);
               	}
//                  $this->AddedOptionInSession['id'];
                  $this->options = array();
               }



             //  $this->getAttributes();
//               return ;


               if (!$this->CheckProduct(true)){
//               echo("4");
                  if (count($item['option'])>0){  // Create Configurable product


                     $this->options = $this->arrayItems[$this->CurrentItem]['option'];
//               echo("6");
                     if ($this->AddOptions()){
                        if ($this->Set()){
//                     	echo("<pre>");
//               echo("7");
//                     	var_dump($this->AddedSetAtribute );
//                     	echo("</pre>");

                           if (!$ParentProduct=$this->GetParentProduct()){
                              $this->NewProductIsConfigurable = true;
                              if (!$ParentProduct=$this->AddProduct(true)){
//                                   	echo("tt2");
                                 $this->addMessage("Create configurable product");
                              }else{
                                 $this->addMessage("Not added configurable product");
                                 $error_stat=1;
                                 $flItemUpdate =false;

                              }

                           }
//                           echo("tt0");
                           $this->NewProductIsConfigurable = false;
                           if ($this->AddProduct()){
//                           	echo("tt3");
                              $error_stat=1;
                              $flItemUpdate =false;
                           }

                           $this->UpdateConfigurableProduct($ParentProduct);

//                         if ($this->AddConfgurableProduct()){
//                            if ($this->AddProduct()){
//                               $error_stat=1;
//                               $flItemUpdate =true;
//                            }
//                         }

                        }
                     }else{
                        $this->addMessage("Option added error");

                        $error_stat=1;
                        $flItemUpdate =false;
                     }



                  }else{
//                  echo("tt5");
                     if ($this->AddProduct()){
                        $error_stat=1;
                        $flItemUpdate =false;
                     }
                  }

               }else{
                        $this->addMessage("Product alredy in store!");
                        $error_stat=1;
                        $flItemUpdate =false;
               }



            }else{
               // if tag   ADDPRODUCTS==0
               
               if (isset($this->RequestParams[self::TAG_UP_PRICE])){
                  if((int)$this->RequestParams[self::TAG_UP_PRICE]==1){
                     $this->QuantityInStockWEB = 0;
                     if (!$this->UpdatePrice()){
                       $error_stat=1;
                       $flItemUpdate =false;
                     }
                  }
               }
               if (isset($this->RequestParams[self::TAG_UP_INVENTORY])){
                  if((int)$this->RequestParams[self::TAG_UP_INVENTORY]==1){
                     $this->QuantityInStockWEB = 0;
                     if (!$this->UpdateStock()){
                       $error_stat=1;
                       $flItemUpdate =false;
                     }
                  }
               }

            }

            $this->_xmlResponse->createTag("ItemCode",   array(), $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE], $itemResponse, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);

            if ($flItemUpdate){
               $this->_xmlResponse->createTag("InventoryUpdateStatus",   array(),"0", $itemResponse);
            }else{
               $this->_xmlResponse->createTag("InventoryUpdateStatus",   array(),"1", $itemResponse);
            }
            $this->_xmlResponse->createTag("QuantityInStockWEB",   array(),(string)$this->QuantityInStockWEB, $itemResponse);



            foreach($this->message as $mesage){
               $this->_xmlResponse->createTag("Message",   array(), $mesage, $itemResponse, Mage_Thub_Model_Run_Run::__ENCODE_RESPONSE);
            }

         }



      }

      public  function  UpdateStock(){
         if  (!isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){   // Not tag ITEMCODE in XML request
            $this->addMessage("Not tag ITEMCODE in XML request.");
            return false;
         }
         if  (!isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_QUANTITYINSTOCK])){   // Not tag QUANTITYINSTOCK in XML request
            $this->addMessage("Not tag QUANTITYINSTOCK in XML request.");
            return false;
         }
         if(!$productId = Mage::getResourceModel('catalog/product')
                           ->getIdBySku( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){
            $this->addMessage("Not found product SKU=". $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE]);
            return false;
         }
         
         if ($this->isVersionPatch01) {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 					
            $_product = new Mage_Catalog_Model_Product();
         } else {
            $_product = Mage::getModel('catalog/product');
         }
         if (!$_product) return false;
         $_product->load($productId);
         
         if($_product->isSuper()){
            $this->addMessage("Product type is SUPER. ");
            return false;
         }


         $_stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);

         $this->QuantityInStockWEB =  $_stockItem->getQty();
         if ($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_QUANTITYINSTOCK]>0)
            $IsInStock=true;
         else
            $IsInStock=false;
					
				//-- 12/07/2010  MJ - THIS IS ALLOW ORDERING OF PRODUCTS EVEN IF QUANTITY IS 0.
				if ($_stockItem["use_config_backorders"] == "0"){
						$IsInStock=true;
				}
				
         $_stockItem
            ->setQty( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_QUANTITYINSTOCK])
            ->setIsInStock($IsInStock)
            ->save();

/*            echo("<pre>");
            var_dump($this->QuantityInStockWEB,$product);//$_product->toArray());
            echo("</pre>");*/

         return true;
      }



      static  function sortItems($a, $b){
         if (!isset($a['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT])||!isset($b['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT])){
            if ($a['item'][self::TAG_ITEMS_ITEM_ITEMCODE] == $b['item'][self::TAG_ITEMS_ITEM_ITEMCODE]) {
               return 0;
            }
            return ($a['item'][self::TAG_ITEMS_ITEM_ITEMCODE] < $b['item'][self::TAG_ITEMS_ITEM_ITEMCODE]) ? -1 : 1;

         }
          if ($a['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT] == $b['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT]) {
             if ($a['index']== $b['index']) {
               return 0;
             }
             return ($a['index'] < $b['index']) ? -1 : 1;
          }
          return ($a['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT] < $b['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT]) ? -1 : 1;
      }



      public  function  UpdateConfigurableProduct($parentProduct){
         $productParentId = Mage::getModel('catalog/product')
                              ->getIdBySku($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT]);

         if ($this->isVersionPatch01) {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 					
            $parentProduct = new Mage_Catalog_Model_Product();
         } else {
            $parentProduct = Mage::getModel('catalog/product');
         }
         if(!$parentProduct) return false;
         $parentProduct->load($productParentId);



         $productId = Mage::getModel('catalog/product')
                              ->getIdBySku($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE]);
                              
         if ($this->isVersionPatch01) {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 					
            $product = new Mage_Catalog_Model_Product();
         } else {
            $product = Mage::getModel('catalog/product');
         }
         $product->load($productId);
                              

         $products = $parentProduct->getTypeInstance()->getUsedProducts();
         $data = array();
         $fladd = true;
         if($products) {
            foreach ($products as $product_) {
            	$data_ = array();
               foreach ($parentProduct->getTypeInstance()->getUsedProductAttributes() as $attribute) {
                  $data_[] = array(
                      'attribute_id' => $attribute->getId(),
                      'label'        => $product_->getAttributeText($attribute->getAttributeCode()),
                      'value_index'  => $product_->getData($attribute->getAttributeCode()),
                      'is_percent'   => 0,
                      'pricing_value'=> "",
                  );
               }
               if ($product_->getId()==$product->getId()){
                    $fladd = false;
               }
                  $data[$product_->getId()] = $data_;
            }
         }
         if ($fladd){
            $data_ = array();
            foreach ($parentProduct->getTypeInstance()->getUsedProductAttributes() as $attribute) {
               $data_[] = array(
                      'attribute_id' => $attribute->getId(),
                      'label'        => $product->getAttributeText($attribute->getAttributeCode()),
                      'value_index'  => $product->getData($attribute->getAttributeCode()),
                      'is_percent'   => 0,
                      'pricing_value'=> "",
               );
            }
            $data[$product->getId()] = $data_;
         }

         $parentProduct->setConfigurableProductsData($data);
         $parentProduct->save($parentProduct);

//        echo("<pre>");
//        Var_dump($data);
//        echo("</pre>");
         return true;
      }








      public  function  UpdatePrice(){

         if  (!isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){   // Not tag ITEMCODE in XML request
            $this->addMessage("Not tag ITEMCODE in XML request.");
            return false;
         }
         if  (!isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_PRICE])){   // Not tag ITEMPRICE in XML request
            $this->addMessage("Not tag ITEMPRICE in XML request.");
            return false;
         }

/*         if  (!isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_SALEPRICE])){   // Not tag SALEPRICE in XML request
            $this->addMessage("Not tag ITEMSALEPRICE in XML request.");
            return false;
         }*/

/**          if(!$productId = Mage::getResourceModel('catalog/product')
                           ->getIdBySku( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){
            $this->addMessage("Not found product SKU=". $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE]);
            return false;
         }
*/

         if(!$productId = Mage::getModel('catalog/product')
                           ->getIdBySku( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){
         // echo "TTTTEEEESSSSSTT\n";
         // exit;
                           
            $this->addMessage("Not found product SKU=". $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE]);
            return false;
         }
         // echo "\$productId=$productId\n";
         
         if ($this->isVersionPatch01) {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 					
            $_product = new Mage_Catalog_Model_Product();
         } else {
            $_product = Mage::getModel('catalog/product');
         }
         if(!$_product) return false;
         $_product->load($productId);
         
         // echo ("\$_product->getEntityIdField() = ".print_r($_product->getEntityIdField(), true));
         
         // $_product->setData('_edit_mode', true);
         // Mage::register('_product', $_product);
         // Mage::dispatchEvent('catalog_product_edit_action', array('_product' => $_product));
         if (Mage::app()->isSingleStoreMode()) {
            $_product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
         }
         //?????????  it seems needed only for magento profiler
         // Mage::dispatchEvent('catalog_product_prepare_save', array('product' => $product, 'request' => ''));         


         $_stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);

         $this->QuantityInStockWEB =  $_stockItem->getQty();

         if  (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_SALEPRICE])){   //
            if ((string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_SALEPRICE]>0){
               $newSalePrice = (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_SALEPRICE];
               $_product->setSpecialPrice($newSalePrice);
            }else{
               $newSalePrice = NULL;
            }
//            $this->addMessage("getSpecialPrice(".(string)$newSalePrice.")");
            
            // $_product->setSpecialPrice($newSalePrice);
         }
         
         $_product->setPrice( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_PRICE]);
         // $_product->setCanSaveConfigurableAttributes(1);
         try{
            $_product->save(); 
            //echo "$price, $itemNum added\n";
         } catch (Exception $e){         
            // echo "$price, $itemNum not added\n";
            echo "exception:$e";
         }             
         
         
         /*            echo("<pre>");
            var_dump($_product->getSpecialPrice(),$_product->toArray());
            echo("</pre>");
*/
         return true;
      }



/*        if ($this->getRequest()->getParam('popup')
            && $this->getRequest()->getParam('product')
            && !is_array($this->getRequest()->getParam('product'))
            && $this->getRequest()->getParam('id', false) === false) {

            $configProduct = Mage::getModel('catalog/product')
                ->setStoreId(0)
                ->load($this->getRequest()->getParam('product'))
                ->setTypeId($this->getRequest()->getParam('type'));

            /* @var $configProduct Mage_Catalog_Model_Product /
            $data = array();
            foreach ($configProduct->getTypeInstance()->getEditableAttributes() as $attribute) {

                /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute /
                if(!$attribute->getIsUnique()
                    && $attribute->getFrontend()->getInputType()!='gallery'
                    && $attribute->getAttributeCode() != $configProduct->getIdFieldName()) {
                    $data[$attribute->getAttributeCode()] = $configProduct->getData($attribute->getAttributeCode());
                }
            }

            $product->addData($data)
                ->setWebsiteIds($configProduct->getWebsiteIds());
        }*/


    /**
     * Retrieve eav entity attribute model
     *
     * @param string $code
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getAttribute($code)
    {
        if (!isset($this->_attributes[$code])) {
            $this->_attributes[$code] = $this->getProductModel()->getResource()->getAttribute($code);
        }
        if ($this->_attributes[$code] instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
            $applyTo = $this->_attributes[$code]->getApplyTo();
            if ($applyTo && !in_array($this->getProductModel()->getTypeId(), $applyTo)) {
                return false;
            }
        }
        return $this->_attributes[$code];
    }


      public function getProductAttributeSets()
    {
    	  $_productAttributeSets=null;
//        if (is_null($this->_productAttributeSets)) {

            $_productAttributeSets = array();

            $entityTypeId = Mage::getModel('eav/entity')
                ->setType('catalog_product')
                ->getTypeId();
            $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter($entityTypeId);
            foreach ($collection as $set) {
                $_productAttributeSets[$set->getAttributeSetName()] = $set->getId();
            }
//        }
        return $_productAttributeSets;
    }


      public function Set(){

            $entityType = Mage::getModel('eav/entity')
                ->setType('catalog_product')
                ->getTypeId();

//        $Default = $entityType->getDefaultAttributeSetId();


//        $values= Mage::getResourceModel('eav/entity_attribute_set_collection')
//                ->setEntityTypeFilter($entityType->getId())
//                ->load()
//                ->toOptionArray();


//         if (is_null($this->Atribute_set_collection)){



              $this->Atribute_set_collection = Mage::getModel('eav/entity_attribute_set')
                  ->getResourceCollection()
                  ->setEntityTypeFilter($entityType)
                  ->load()
                  ->toOptionArray();

              foreach($this->Atribute_set_collection as $Atribute_set){
                 if (strcasecmp($Atribute_set['label'],'Default')==0){
                    $this->Atribute_set_default['id'] = $Atribute_set['value'];
                    $this->Atribute_set_default['name'] = $Atribute_set['label'];
                 }
              }
//         }



//  ������� ��� SET'� �� ���������� �����
         $this->AddedSetAtribute = array();
         $this->AddedSetAtribute['name'] = '';
         $this->AddedSetAtribute['name2'] = '';
         $this->AddedSetAtribute['option'] = array();
//         echo("<pre>");
//         Var_dump($this->arrayItems);
//         echo("</pre>");
         $this->options = $this->arrayItems[$this->CurrentItem]['option'];
         if (count($this->options)>=1){
            ksort($this->options);
            $this->AddedSetAtribute['name2'] = strtolower(implode("-",array_flip ($this->options)));
            $this->AddedSetAtribute['name'] = "thub-".$this->AddedSetAtribute['name2'];
         }else{
            // ����� ���
            $this->AddedSetAtribute = $this->Atribute_set_default;
            return true;

         }


        $this->Atribute_set_thub= array();
         foreach($this->Atribute_set_collection as $Atribute_set){
            $nameSetAttribute = explode('-',$Atribute_set['label']);
            if (strcasecmp($nameSetAttribute[0],'thub')==0){
               if (strcasecmp($Atribute_set['label'],$this->AddedSetAtribute['name'])==0){
//               $Array = explode('_',$nameSetAttribute[1]);
/*               $arrayOption  =array();
               foreach ($Array as $option){
                  $Array = array();
                  $array['id'] = "";
                  $array['name'] = $option;
                  $arrayOption[] = $array;
               }                */
                  $Array = array();
                  $Array['id'] = $Atribute_set['value'];
                  $Array['name'] = $Atribute_set['label'];
                  $Array['name2'] = $nameSetAttribute[1];
      //               $Array['option'] = $arrayOption;
                  $this->Atribute_set_thub[] = $Array;
               }
            }
         }


//var_dump($this->Atribute_set_thub);



         if (count($this->AddedSetAtribute)>=1){
            foreach($this->Atribute_set_thub as $Atribute_set){
               if (strcasecmp($Atribute_set['name'],$this->AddedSetAtribute['name'])==0){
                  $this->AddedSetAtribute = $Atribute_set;
               }
            }
         }


//var_dump($this->AddedSetAtribute);

//var_dump($this->AddedSetAtribute);


             $arrayOption = array();



            foreach($this->options as $key=>$value){
               $_key = strtolower($key);

               $attribute = Mage::getModel('catalog/entity_attribute')
                     ->setStoreId($this->storeId)
//                     ->setAttributeSetFilter($this->Atribute_set_default['id'])
                     ->loadByCode($entityType, $_key);
               $array = array();
               $array['name'] = $_key;
               $array['value'] = $value;
               if ($attribute->getId()){
                  $array['id'] = $attribute->getId();
                  $array['add'] = false;
               }else{
                  $array['id']  = "";

                  foreach ( $this->AddedOptionInSession as $ind => $AddedOption){
                  	if (strcasecmp($AddedOption['code'], $_key)==0){  // ����� ��� �����������
                        $AttributeId=$AddedOption['id'];
                  		$flUpdate = true;

                       $attribute = Mage::getModel('catalog/entity_attribute')
                           ->setStoreId($this->storeId)
                           ->setEntityTypeId($entityType)
                           ->load($AttributeId);
                        $array['id'] = $attribute->getId();
                        $array['add'] = true;

                  }}

//               echo("<pre>");
//               var_export($attribute->getData());
//               var_export($attribute->getData());


               }
               $array['add'] = true;
               $arrayOption[] = $array;
            }
            $this->AddedSetAtribute ['option'] = $arrayOption;



         if (!isset($this->AddedSetAtribute['id'])){
// ����� ��������� � ������� Defalt

                  $AttributesCollection = Mage::getModel('eav/entity_attribute')
                      ->getResourceCollection()
                      ->setAttributeSetFilter($this->Atribute_set_default['id'])
                      ->load();

                  // ��������� � ���������� � ������������
                  foreach( $AttributesCollection as $attribute ) {
                  	// �������� ���������� �� � ������� ����������� ��������
                     foreach($this->AddedSetAtribute['option'] as $key=>$option){
                        if ($option['id']==$attribute->getId()){
                           $this->AddedSetAtribute ['option'][$key]['add'] = false; // ���� �������������
                        }
                     }
                  }
/*
echo("<pre>");
var_dump($this->AddedSetAtribute);
echo("</pre>");
  */



 // ����� ��������� Set


           $modelSet = Mage::getModel('eav/entity_attribute_set')
               ->setId($this->Atribute_set_default['id'])
               ->setEntityTypeId( $entityType);

           $modelSet = Mage::getModel('eav/entity_attribute_set');
           $modelSet->setAttributeSetName($this->AddedSetAtribute['name'])
               ->setEntityTypeId( $entityType);



           $modelSet->Save();
           $this->addMessage("Create New Atribute Set :".$this->AddedSetAtribute['name']);
           $setId = $modelSet->GetId();
           $this->AddedSetAtribute ['id'] = $setId;




//         ����  � Set'e ��� ������

           $groups = Mage::getModel('eav/entity_attribute_group')
                    ->getResourceCollection()
                    ->setAttributeSetFilter($this->Atribute_set_default['id'])
                    ->load();


            $groupId = null;
            $newGroups = array();
            foreach( $groups as $group ) {
//
                  $newGroup = clone $group;
                  $newGroup->setId(null)
                      ->setAttributeSetId($setId)
                      ->setDefaultId($group->getDefaultId());

                 if (strcasecmp($group->getAttributeGroupName(),'General')==0){
                 	   $groupId = $group->getAttributeGroupId();
                 }



                  $groupAttributesCollection = Mage::getModel('eav/entity_attribute')
                      ->getResourceCollection()
                      ->setAttributeGroupFilter($group->getId())
                      ->load();



                  $newAttributes = array();
                  foreach( $groupAttributesCollection as $attribute ) {
                      $newAttribute = Mage::getModel('eav/entity_attribute')
                          ->setId($attribute->getId())
                          //->setAttributeGroupId($newGroup->getId())
                          ->setAttributeSetId($setId)
                          ->setEntityTypeId($entityType)
                          ->setSortOrder($attribute->getSortOrder());
                      $newAttributes[] = $newAttribute;

                  }

                  if (!is_null($groupId) ){ // ��������� � ������ General
                         //$groupAttributesCollection->getSize();
                         foreach($this->AddedSetAtribute ['option'] as $k=>$option){
                            if ($option['add']===true){
//                              $this->addMessage("Add Atribute:".$option['name']." to Set".$this->AddedSetAtribute['name']);

                               $newAttribute = Mage::getModel('eav/entity_attribute')
                                 ->setEntityTypeId($entityType)
                                 ->load($option['id']);
                               $newAttribute
//                                    ->setId($option['id'])
                                    ->setAttributeSetId($setId)
                                    ->setEntityTypeId($entityType)
//                                    ->setSortOrder(0)
                                    ;
                               $this->AddedSetAtribute ['option'][$key]['add'] = false;
                               $newAttributes[] = $newAttribute;
                            }
                         }
                         $groupId = null;
                  }

                  $newGroup->setAttributes($newAttributes);
                  $newGroups[] = $newGroup;
  //             }
            }
            $modelSet->setGroups($newGroups);
/*
echo("<pre>");
var_dump($newGroups);
echo("</pre>");
 */
//            $modelSet->Save();

         $modelSet->save();

//            $modelSet->Save();
            $this->addMessage("Clone Atribute Set DEFALT in ".$this->AddedSetAtribute['name']);

         }



//echo("<pre>");
//             var_export($newGroups);
//             $this->AddedSetAtribute );
//echo("</pre>");


//            var_export($this->AddedSetAtribute);
//$this->Atribute_set_thub
         return true;
      }







    public Function AddOptions($flConfigurableProduct = false){





        if (is_null($this->stores)) {
            $this->stores = Mage::getModel('core/store')
                ->getResourceCollection()
                ->setLoadDefault(true)
                ->load()
                ->toArray();
        }


        $this_entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
  //      print_r($this->options);
        $flreturn = true;

      foreach ($this->options as $name_Option=> $OptionValueAdd){

      // �������� ��������� ������� ������� �� $code
           $_nameOption = strtolower($name_Option);



           $attribute = Mage::getModel('catalog/entity_attribute')
               ->setStoreId($this->storeId)
               ->loadByCode($this_entityTypeId, $_nameOption);




           if ($attribute->getId()) {
              $AttributeId=$attribute->getId();
           }


        $flUpdate=false;
//            echo($_nameOption."<br>");

         // ��������� ���������c� �� ��� �����
         foreach ( $this->AddedOptionInSession as $ind => $AddedOption){



         	if (strcasecmp($AddedOption['code'], $_nameOption)==0){  // ����� ��� �����������
               $AttributeId=$AddedOption['id'];
         		$flUpdate = true;

              $attribute = Mage::getModel('catalog/entity_attribute')
                  ->setStoreId($this->storeId)
                  ->setEntityTypeId($this_entityTypeId)
                  ->load($AttributeId);


//               echo("<pre>");
//               var_export($attribute->getData());
//               var_export($attribute->getData());
//                echo($attribute->getID());
//               echo("<br></pre>");


               $this->addMessage("(update)Option alredy. Added in this session. ");
//               $flreturn = false;
//               continue;
         	}

         }

//echo("<pre>");
//var_dump($this->AddedOptionInSession);
//echo("</pre>");



       //  echo($attribute->getSourceModel());
//          echo("<pre>");
//         var_dump($attribute->toArray());//getAttributeModel());
//          echo("</pre>");
          // $attributeId=$attributeId->getIdByCode($entityType,$code );




        // ���� � ���� ���� ������� �� �� ������������ � ����� $code
         if (($attribute->getName()==$_nameOption)||$flUpdate) {


           $this->Attribute['attribute_id']          = (string)$AttributeId;
           $this->Attribute['attribute_code']        =  $_nameOption;
           $this->Attribute['option']                =  array();

//         echo("<pre>");
//         echo('Add new SET:'.$attribute->getName());
//         echo("</pre>");

            $attributeId = $attribute->getAttributeId();
            if (is_null($attribute->getSourceModel())){
              $this->addMessage("Not found source model. ");
              return false;
            }
            $result = array();
            if ($attribute->usesSource()) {


//               echo($attribute->getSourceModel());
               $method = $attribute->getSourceModel();
//                 var_dump($method);

               if (empty($method)){
//                  $Items = $attribute->getDefaultValue();

                  $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                      ->setAttributeFilter($attribute->getId())
                      ->setStoreFilter()
                      ->setPositionOrder('asc')
                      ->load()
                      ->toOptionArray();

//                       echo("test1");


               }else{
                  $test =Mage::getModel($attribute->getSourceModel())
                         ->setAttribute($attribute);

                  $optionCollection = $test->getAllOptions();
               }

//               echo("<pre>");
//               var_dump($optionCollection);
//               echo("</pre>");
//                $attribute->getSource();
               $flAdd =true; // ���������� ����� �����
               $arrOption = array();
               $arrValue = array();
               $arrDelete = array();
               $arrOrder  = array();
//                       echo("test1");
            	// �������� ����� ������� $code
            	$sortOrder = 0;

               foreach ($optionCollection as $optionId=>$optionValue) {
                   $sortOrder++;



                   if (is_array($optionValue)) {
                       $result[] = $optionValue;

                       $optionId = $optionValue['value'];
                       $optionValue_ = $optionValue ['label'];
//                       echo("test2");
                       $optionValue  =$optionValue_;
                   } else {
                       $result[] = array(
                           'value' => $optionId,
                           'label' => $optionValue
                       );
                   }
                    if (strcasecmp($OptionValueAdd, $optionValue)==0 ){
                       $array['id']=$optionId;
                       $array['value']=$optionValue;

                       $this->Attribute['option']                =  $array;

//                       $this->optionInit[]=;

                       $flAdd =false;// ����� ����. ��������� �� �����.
                       continue;
//                       echo("est");
                    }

                    if (strcasecmp($optionId, $optionValue)==0 && $optionId =="" ){
                    }else{
//                       echo("test5");

                       $array = array();
                       foreach($this->stores['items'] as $v){
                            $array[] = $optionValue;
                       }



                       $arrValue[(string)$optionId] = $array;
                       $arrDelete[(string)$optionId] =  ""  ;
                       $arrOrder[(string)$optionId] = $sortOrder;

                    }
//                     print_r($arrOption);

               }

//               echo("<pre>");
//               var_export($arrValue);
//               echo("</pre>");
               if ($flAdd ){
                  $countOption = count($arrValue);
                  $newTagOption = "option_".$countOption;



                       $array = array();
                       foreach($this->stores['items'] as $v){
                            $array[] = $OptionValueAdd;
                       }



                  $arrValue[$newTagOption] = $array;
                  $arrDelete[$newTagOption] =    "";
                  $arrOrder[$newTagOption] = (string)($sortOrder+1);
                  $arrOption['value']  = $arrValue;
                  $arrOption['delete'] = $arrDelete;
                  $arrOption['order']  = $arrOrder;

                  $data = $attribute->getData();


                  $data['attribute_id']          = (string)$AttributeId;
                  $data['attribute_code']        =  $_nameOption;
                  $data['option']                =  $arrOption;


                   $attribute->setData($data);



                   $attribute->setEntityTypeId($this_entityTypeId);

                   $attribute->setIsUserDefined(1);
                   $attribute->Save();

                 //  unset($attribute);
                   $this->addMessage("(update)Added option:".$name_Option."=".$OptionValueAdd);
//             print_r($data);

               }





            }else{
               $this->addMessage("Error Not found sourse ");
               return false;
            }

         }else{


            $attribute = Mage::getModel('catalog/entity_attribute');

                 if ($flUpdate){
                     $this->addMessage("Option alredy. Added in this session. ");
                     return false;

                 }


        // ���� � ���� ��� �������� � ������  $code


            // ������� �������
                $data =  array (
                          'attribute_code' => $_nameOption,
                          'is_global' => self::OPTION_SCOPE_IN_GLOBAL,
                          'frontend_input' => 'select',
                          'default_value_text' => '',
                          'default_value_yesno' => '0',
                          'default_value_date' => '',
                          'default_value_textarea' => '',
                          'is_unique' => self::OPTION_IS_UNIQUE,
                          'is_required' => self::OPTION_IS_REQUIRED,
                          'frontend_class' => '',
                          'is_configurable' => self::OPTION_IS_CONFIGURABLE,
                          'is_searchable' => self::OPTION_IS_SEARCHABLE,
                          'is_visible_in_advanced_search' => self::OPTION_IS_VISIBLE_IN_ADVANCED_SEARCH,
                          'is_comparable' => self::OPTION_IS_COMPARABLE,
                          'is_filterable' => self::OPTION_IS_FILTERABLE,
                          'is_visible_on_front' => self::OPTION_IS_VISIBLE_ON_FRONT,
                          'frontend_label' =>  array (),

                          'option' =>
                                array (
                                  'value' =>
                                     array (
                                       'option_0' =>
                                          array (
                                            0 => $OptionValueAdd,
                                            1 => $OptionValueAdd,
                                          ),
                                       ),
                                  'order' =>
                                     array (
                                       'option_0' => '',
                                     ),
                                  'delete' =>
                                     array (
                                       'option_0' => '',
                                     ),
                                ),
                          'backend_type' => 'int',
                          'is_user_defined' => '1',
                          'apply_to' =>
                             array (),
                          'default' =>
                             array (
                               0 => 'option_0',
                             ),
                );


               $array1 = array();
               $array2 = array();
               foreach($this->stores['items'] as $v){
                  $array1[] = $name_Option;
                  $array2[] = $OptionValueAdd;
               }


               $data['frontend_label'] =$array1;
               $data['option']['value']['option_0'] =$array2;

//               if ($this->NewProductIsConfigurable){
                  $data['is_global']= self::OPTION_SCOPE_IN_GLOBAL;
                  $data['is_configurable']= self::OPTION_IS_CONFIGURABLE_YES;
                  $data['apply_to']= array();
//               }


//                echo("Added option:".$nameOption."=".$OptionValueAdd);
                $attribute->setData($data);
                $attribute->setStoreId($this->storeId);
                $attribute->setEntityTypeId($this_entityTypeId);
                $attribute->setIsUserDefined(1);





              //  $attribute->Save();

         //   $attribute->Save();

//        $transactionSave = Mage::getModel('core/resource_transaction')
//            ->addObject($attribute)
            $attribute->save();







              $this->Attribute['attribute_id']          = (string)$attribute->getId();
              $this->Attribute['attribute_code']        =  $_nameOption;
              $this->Attribute['option']                =  array();



                 $array_ = array(
                       "id"              => $attribute->getId(),
                       "code"            => $_nameOption,
                       "name"            => $name_Option,
                       "value"           => $OptionValueAdd,
                       );
                $this->AddedOptionInSession[] =$array_;



                $this->addMessage("(create)Added option".$name_Option."=".$OptionValueAdd);
               // echo("Added option".$nameOption."=".$valueOption);


//               echo("<pre>");
//               var_export($this->AddedOptionInSession);
//               echo("</pre>");
         }

                   $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                      ->setAttributeFilter($attribute->getId())
                      ->setStoreFilter()
                      ->setPositionOrder('asc')
                      ->load()
                      ->toOptionArray();
//                      var_dump($optionCollection);

                   foreach ($optionCollection as $optionId=>$optionValue) {
                       if (strcasecmp($OptionValueAdd, $optionValue['label'])==0 ){
                       	  $array = array();
                          $array['id']=$optionValue['value'];
                          $array['value']=$optionValue['label'];
                          $this->Attribute['option']                =  $array;
                          continue;
                       }
                   }


                   $this->Attribute['option']                =  $array;

                   $this->Attributes []                     = $this->Attribute;

                unset($attribute);

      }



         return $flreturn;

    }




        public Function Option(){
//        $entityType = Mage::registry('product')->getResource()->getEntityType();

//        $Default = $entityType->getDefaultAttributeSetId();


        $values= Mage::getResourceModel('eav/entity_attribute_set_collection')
//                ->setEntityTypeFilter($entityType->getId())
                ->load()
                ->toOptionArray();



         $this->TestOption();

//         echo("<pre>");
//        print_r($values);
//        print_r(Mage::getModel('catalog/product_type')->getOptionArray());
//        print_r($this->getProductAttributeSets());
//        print_r($this->TestOption());


//         echo("</pre>");

 /*       $fieldset->addField('product_type', 'select', array(
            'label' => Mage::helper('catalog')->__('Product Type'),
            'title' => Mage::helper('catalog')->__('Product Type'),
            'name'  => 'type',
            'value' => '',
            'values'=> Mage::getModel('catalog/product_type')->getOptionArray()
        ));

   */
        }




      public  function  AddProduct($NewProductIsConfigurable = false){

         if (!isset($this->TaxClassID)){
            $this->TaxClassArray = Mage::getSingleton('tax/class_source_product')->toOptionArray();

            foreach($this->TaxClassArray as $el){
               if (strcasecmp($el['label'], self::PRODUCT_TAX_CLASS_NAME_DEFAULT)==0){  //
                  $this->TaxClassID = $el['value'];
               }
            }
         }

         $data = array(
                         "name"                    => '',
                         "description"             => '',
                         "short_description"       => '',
                         "sku"                     => '',
                         "weight"                  => '',
//                         "manufacturer"            => "",
//                         "color"                   => "",
                         "status"                  => self::SET_PRODUCT_STATUS,  // enable -1, disable -2
                         "visibility"              => 4,
                         "gift_message_available"  => 2,
                         "price"                   => '',

                         "special_price"           => '',
                         "special_from_date"       => '',
                         "special_to_date"         => "",
                         "cost"                    => "",
                         "tax_class_id"            => $this->TaxClassID,
                         "meta_title"              =>"",
                         "meta_keyword"            =>"",
                         "meta_description"        => '',
//                         "type"                   =>  "simple",

                         'custom_design' => "",
                         'custom_design_from' => "",
                         'custom_design_to' => "",
                         'custom_layout_update' => "",
                         'options_container' => 'container2',

                         'image'                   => 'no_selection',
                         'small_image'             => 'no_selection',
                         'thumbnail'               => 'no_selection',
//                         'media_gallery'           => Array(
//                                    'images'       => '',
//                                    'values'       => array(
//                                       "image"       =>null,
//                                       "small_image" =>null,
//                                       "thumbnail"   =>null
//                                    ),
//                         ),

                         "stock_data" => Array  (
                                 "use_config_manage_stock" => 1,
                                 "qty" => '',

                                 "use_config_min_qty" => 1,
                                 "use_config_min_sale_qty" => 1,
                                 "use_config_max_sale_qty" => 1,
                                 "is_qty_decimal" => 0,
                                 "use_config_backorders" => 1,
                                 "use_config_notify_stock_qty" => 1,
                                 "is_in_stock" => 1,
                         ),

                         "website_ids" => array(),


            );

            if (isset($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_SALEPRICE])){
               $data['special_price']= (float) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_SALEPRICE];
//                         "special_from_date"       => ''date("y.m.d"),

            }

            if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_PRICE])){
               $data['price']= (float) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_PRICE];
            }

            if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMDESCRIPTION])){
               $data['description']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMDESCRIPTION];
               $data['short_description']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMDESCRIPTION];
               $data['meta_description']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMDESCRIPTION];
            }

            if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMNAME])){
               $data['name']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMNAME];
            }

            if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){
               $data['sku']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE];
            }

            if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_WEIGHT])){
               $data['weight']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_WEIGHT];
            }

            if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_QUANTITYINSTOCK])){
               $data['stock_data']['qty']= (float) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_QUANTITYINSTOCK];
            }


            if ($NewProductIsConfigurable){
               $data['visibility']   = self::OPTION_VISIBLE_CONFIGURABLE_PRODUCT;
               $data['type']         = "configurable";
               $data['stock_data']= Array  (
                                 "use_config_manage_stock" => 1,
                                 "is_in_stock" => 1
                                  );
               $data['status']         = self::SET_CONFIGURABLE_PRODUCT_STATUS;   // enable -1, disable -2

               if (isset( $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT])){
                  $data['sku']= (string) $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT];
               }

            }else{
               $data['visibility']= self::OPTION_VISIBLE_SIMPLE_PRODUCT;
               $data['options']=  array();
            }



               $_websites =  Mage::app()->getWebsites();

               $website_ids = array();

                  foreach ($_websites as $_website) {
                     if (self::SET_WEBSITE_DEFAULT){
                        if ($_website->getIsDefault()) {
                        	$website_ids[]=$_website->getId();
                        }
                     }else{  // all website
                        $website_ids[]=$_website->getId();
                     }
                  }

               $data['website_ids']= $website_ids;






            // $product = Mage::getModel('catalog/product');
            if ($this->isVersionPatch01) {
               Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 					
               $product = new Mage_Catalog_Model_Product();
            } else {
               $product = Mage::getModel('catalog/product');
            }


            if (!$NewProductIsConfigurable){
               // Init atribute
               if (isset($this->Attributes)&&count($this->Attributes)>0){
                  foreach($this->Attributes  as $Atribute){
                     if (isset($Atribute['attribute_code'])&&isset($Atribute['option']['id'])){
                        $data[$Atribute['attribute_code']] = $Atribute['option']['id'];
                     }
                  }
               }

/*               if (false&&isset($this->AddedSetAtribute['option'])&&count($this->arrayItems[$this->CurrentItem]['option'])>0){
                  foreach($this->AddedSetAtribute['option'] as $k => $option){
                  	if (isset($option['id'])){

                        $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                            ->setAttributeFilter($option['id'])
                            ->setStoreFilter()
                            ->setPositionOrder('asc')
                            ->load()
                            ->toOptionArray();

                        foreach ($optionCollection as $optionId=>$optionValue) {
                           if (strcasecmp($option['value'],$optionValue['label'])==0){
                                 $data[$option['name']]= $optionValue['value'];
                           }
                        }
                     }
                  }
               }*/
            }

//               echo("<pre>");
//               Var_dump($data);
//               echo("</pre>");

            $product->setData($data);

            $product->setStoreId($this->storeId);


//            var_dump($this->AddedSetAtribute);

            if (isset($this->AddedSetAtribute['id'])){
               $product->setAttributeSetId((int)$this->AddedSetAtribute['id']);
            }else{
            	/***** look up proper default attribute set it for products, sdwyer, 02/12/09 ****/

            	// get config object
            	$configObj = Mage::getConfig();

            	// get table name prefix and add it to table naming
            	$db_table_prefix = trim($configObj->getTablePrefix());

            	$res = Mage::getSingleton('core/resource');

				$read = $res->getConnection('core_read');

				$select = $read->select()
            	->from(array('eas'=>$db_table_prefix . 'eav_attribute_set'), 'attribute_set_id')
	            ->join(array('eet'=>$db_table_prefix . 'eav_entity_type'), 'eas.entity_type_id = eet.entity_type_id', array())
	            ->where('eas.attribute_set_name=?', 'Default')
	            ->where('eet.entity_type_code=?', 'catalog_product');

	            $attrSetId = $read->fetchOne($select);
/*
               echo("<pre>");
               print $attrSetId;
               Var_dump($data);
               echo("</pre>");
               exit();
*/

               $product->setAttributeSetId($attrSetId); // default set atribute code = 4
            }

            if ($NewProductIsConfigurable){
               $data['type_id']= 'configurable';
               $product->setTypeId('configurable');
            }else{
               $product->setTypeId("simple");
               $data['type']= 'simple';
            }
            if (isset($this->addIndex))
               $product->setCategoryIds($this->addIndex);


            if ($NewProductIsConfigurable){



               $configurable_products_data = array();
               $UsedProductAttributeIds = array();
               $product->setConfigurableProductsData($configurable_products_data);

               $configurable_atribute_data = array();
               $indexArray = 0;

               foreach($this->AddedSetAtribute['option'] as $Atribute){
                  $array= array();
                  $array['id']             = null;
                  $array['label']          = $Atribute['name']; //*
                  $array['position']       = null;
                  $array['values']         =  Array();
                  $array['attribute_id']   = $Atribute['id']; //            [attribute_id] => 449
                  $array['attribute_code'] = $Atribute['name']; //            [attribute_code] => attribute_check
                  $array['frontend_label'] = ''; //            [frontend_label] => Check
                  $array['html_id']        = 'config_super_product__attribute_'.$indexArray; //            [html_id] => config_super_product__attribute_0
                  $configurable_atribute_data[$indexArray] =$array;
                  $UsedProductAttributeIds[$indexArray] =(int)$Atribute['id'];
                  $indexArray++;
               }

               $product->setConfigurableAttributesData($configurable_atribute_data);

               if (count($UsedProductAttributeIds)>0){
                  $product->getTypeInstance()->setUsedProductAttributeIds($UsedProductAttributeIds);
               }
               $product->setCanSaveConfigurableAttributes((bool)true);




            }

//               echo("<pre>");
//               Var_dump($product->getDATA());
//              echo("</pre>");
//              exit();


            $product->save();

            $this->addMessage("Added product SKU=". $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE]);
            return False;
      }





      public  function  InitCategory($mode = false){
         if ($mode||count($this->listCategories)===0){

            $_collection = Mage::getModel('catalog/category')->getCollection();

            $_collection->addAttributeToSelect('name')
               ->addAttributeToSelect('is_active') ;

            $_collection->addAttributeToSort('name');

           // $_element['_path']


//            $_collection->addFieldToFilter('entity_id',array());
         //  $_collection->addFieldToFilter('name',"454545");

            foreach($_collection as $_element){
               $temp = $_element->toArray(array("name","path",'entity_id'));
//            	$temp['_path'] = explode("/",$temp["path"]);
               $temp['_path'] = $_element->getPathIds();
               $this->listCategories [$temp['entity_id']]=$temp;
            }

//             echo("<pre>");
//            var_dump($this->listCategories);
//            echo("</pre>");
            foreach($this->listCategories as $key =>$_element){
               $pathNameCategory = '';

               $arrayPathNameCategory = array();
               $ind=0;

//             echo("<pre>");
//            var_dump($_element);
//            echo("</pre>");

               foreach($_element['_path'] as $_path){
                  if (isset($this->listCategories[$_path]['name'])){
                     $pathNameCategory .= $this->listCategories[$_path]['name'];
                //     echo($this->listCategories[$_path]['name']);
                     $arrayPathNameCategory [] =$this->listCategories[$_path]['name'];
                     if (count($_element['_path'])-1!=$ind){
                        $pathNameCategory .= "/";
                     }
                     $ind++;
                  }
               }
               $this->listCategories [$_path]['pathname']=$pathNameCategory;
               $this->listCategories [$_path]['arraypathname']=$arrayPathNameCategory;

            }
          //  echo($pathNameCategory);
         }
      }


      public  function  setDataCategory($name = "" , $path = '1'){
         $this->data = array(
                        "name"                  =>   $name,
                        "path"                  =>   $path,
                        "is_active"             =>   "0",
                        "url_key"               =>   "",
                        "description"           =>   "",
                        "meta_title"            =>   "",
                        "meta_keywords"         =>   "",
                        "meta_description"      =>   "",
                        "display_mode"          =>   "PRODUCTS",
                        "landing_page"          =>   "",
                        "is_anchor"             =>   "0",
                        "custom_design"         =>   "",
                        "custom_design_apply"   =>   "1",
                        "custom_design_from"    =>   "",
                        "custom_design_to"      =>   "",
                        "page_layout"           =>   "",
                        "custom_layout_update"  =>   ""
         );
      }





      public  function  CheckProduct($flMsg = false){
      	if (isset($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT])){
            $itemCodeParent = $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT];
         }else {
         	$itemCodeParent = "";
         }
      	if (isset($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE])){
            $itemCode = $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODE];
         }else {
         	$itemCode = "";
         }
         if($productIdParent = Mage::getModel('catalog/product')
                           ->setStoreId($this->storeId)
                           ->getIdBySku($itemCodeParent)){
//               var_dump($productIdParent);
            if($productId = Mage::getModel('catalog/product')
                           ->setStoreId($this->storeId)
                           ->getIdBySku($itemCode)){

               return true;
            }
         }else{
            if($productId = Mage::getModel('catalog/product')
                           ->setStoreId($this->storeId)
                           ->getIdBySku($itemCode)){

               return true;
            }
         }
         return false;

/*         if(!$productIdParent = Mage::getModel('catalog/product')
                           ->getIdBySku($itemCodeParent)){

            if(!$productId = Mage::getModel('catalog/product')
                              ->getIdBySku($itemCode)){
               if ($flMsg){
                  $this->addMessage("Not found product SKU=".$itemCode);
               }
               return false;
            }
            else {
               if ($flMsg){
                  $this->addMessage("Found simple product SKU=".$itemCode);
               }
               $this->ParentProductIsConfigurable = false;
               return true;
            }


         }else{
            if(!$productIdParent = Mage::getModel('catalog/product')
                              ->load($productIdParent)){
            	if ($productIdParent->IsConfigurable()){
                  if ($flMsg){
                     $this->addMessage("Found product SKU=".$itemCode);
                  }
                  $this->ParentProductIsConfigurable = true;
                  return true;
               }else{
                  if($productId = Mage::getModel('catalog/product')
                              ->getIdBySku($itemCode)){
                     if ($flMsg){
                        $this->addMessage("found simple product SKU=".$itemCode);
                     }
                     $this->ParentProductIsConfigurable = false;
                     return true;
                  }else{
                     if ($flMsg){
                        $this->addMessage("Not found configurable product SKU=".$itemCode);
                     }
                     $this->ParentProductIsConfigurable = false;
                     return false;

                  }
               }
            }
         }
         */
      }


      public  function  GetParentProduct(){
      	if (isset($this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT])){
            $itemCodeParent = $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_ITEMCODEPARENT];
         }else {
      		return false;
         }
         if ($product=Mage::getModel('catalog/product')->
                     load(Mage::getModel('catalog/product')
                           ->setStoreId($this->storeId)
                           ->getIdBySku($itemCodeParent))){
           if ($product->getid()){
               return $product;
           }else{
               return false;
           }
         }else {
            return false;
         }
      }




      public  function  CheckCategory(){

         //   if (count($this->listCategories)==0)
         $this->InitCategory(true);
         $itemCategory = $this->arrayItems[$this->CurrentItem]['item'][self::TAG_ITEMS_ITEM_CATEGORY];

         $itemCategory = ereg_replace('//','/',$itemCategory);
         $itemCategory = ereg_replace('^/','',$itemCategory);
         $itemCategory = ereg_replace('/$','',$itemCategory);

         $this->newCategories = explode("/", $itemCategory);
         $this->newCategories2 = $this->newCategories;
         $this->addIndex ="1";
         $this->addPath="1";
         if ($this->recursionCategory(2)){
             // Add Category
             foreach($this->newCategories2 as $k => $v){
//                  if ($k>=$this->addIndex-2){

                  $this->setDataCategory($v,$this->addPath);
                  if ($this->addIndex=$this->AddCategory()){
                    // echo($id);
                  }
                //  break;
//                  }

             }
         }else{

         }


      }

      public function recursionCategory($index){
         if ($index>15) return false;


               foreach($this->listCategories as $oldCategory){

                  if ((count($oldCategory['_path']) == $index)){






/*                     if ( strstr($this->addPath,$oldCategory['path'])!=""){
                        $this->addPath.="/".$oldCategory['entity_id'];

                     }elseif ($index==count($this->newCategories)+1){

                           return true;
                     }
  */
//               echo("<pre>");
//               var_dump( $oldCategory['name'],$this->addPath,$oldCategory,$this->newCategories[$index-2],substr($oldCategory['path'],0, strrpos($oldCategory['path'],"/")));
//               echo("</pre>");
   //                     $this->addIndex=$index;
//                        $index++;
//                        if ($this->recursionCategory($index)) return true;


                     if  (( substr($oldCategory['path'],0, strrpos($oldCategory['path'],"/"))==$this->addPath)&&
                              strtoupper ($oldCategory['name'])==strtoupper ($this->newCategories[$index-2])){
//               echo("<pre>");
//               var_dump( substr($oldCategory['path'],0, strrpos($oldCategory['path'],"/")),$this->newCategories[$index-2]);
//               echo("</pre>");
//                     if ($index==2){
                        $this->addPath=$oldCategory['path'];
//                     }else{
//                        $this->addPath=substr($oldCategory['path'],0, strrpos($oldCategory['path'],"/"));
//                     }
                        $this->addIndex=$oldCategory['entity_id'];
                        unset($this->newCategories2[$index-2]);
  //             echo("<pre>");
//               var_dump( $this->addPath,$this->addIndex,$index,$oldCategory['name'],$oldCategory["path"]);
//               echo("</pre>");


                        if (($index==count($this->newCategories)+1)||(count($this->newCategories2)==0)){
                           $this->addIndex=$oldCategory['entity_id'];
                           $this->addPath=$oldCategory['path'];
                           return false; // ��������� �� �����
                        }


                        $index++;
                        if ($this->recursionCategory($index)) return true;
                        else return false;

                       // $this->Index=$oldCategory['entity_id'];

                     }
                  }
               }
         return true;

      }


      public function AddCategory(){
        $category = Mage::getModel('catalog/category');
        $category->setStoreId($this->storeId);

//        $storeId = $this->getRequest()->getParam('store');
        if (count($this->data)>0) {
            $category->addData($this->data);
            /**
             * Check "Use Default Value" checkboxes values
             */
            $category->setAttributeSetId($category->getDefaultAttributeSetId());

            try {
                #if( $this->getRequest()->getParam('image') )

                $category->save();
                $this->addMessage("Added category name ".$this->data["name"]);
                $this->addPath.='/'.$category->getId();
//                $this->AddIndex++;

                return $category->getId();
               // Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('Category saved'));
            }
            catch (Exception $e){
                return false;
            }
        }

      }


      public  function  CreateHeaderXml(){
         $this->_xmlResponse = Mage::getModel('thub/run_thubxml');
         $this->_xmlResponse->version='1.0';
         $this->_xmlResponse->encoding='ISO-8859-1';

      	$this->root = $this->_xmlResponse->createTag("RESPONSE", array('Version'=>'4.1'));
      	$this->envelope = $this->_xmlResponse->createTag("Envelope", array(), '', $this->root);
      	$this->_xmlResponse->createTag("Command", array(), $this->RequestParams['COMMAND'], $this->envelope);
      }


       /**
        * Load parameters with XML request
        *
        * @return array
        */
      public function loadParameters (){
         try{

             $this->_xmlRequest->getTag(0, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);


             foreach ($_tagTags as $k=>$v){
                 $this->_xmlRequest->getTag($v, $tN, $tA, $tC, $tT);
                 $this->RequestParams[strtoupper($tN)] = trim($tC);
             }



            $this->itemsTag = $this->_xmlRequest->getChildByName(0, "ITEMS");
            if ((count($this->itemsTag) <1)||$this->itemsTag==null){
                 print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9001',
                         'Error XML request! Not found required tag ITEMS',''   , ''));
              exit;
            }



         } catch (Exception $e) {
              print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9001',
                   'Critical Error catch (Exception $e)'.$e, "", ''));
              exit;
         }
      }

      protected  function addMessage($message='') {
         $this->message[] = $message;
      }

      protected  function clearMessage() {
         $this->message = array();
      }

      public  function xmlErrorResponse($command, $code, $message, $provider="", $request_id='') {
         $xmlError = Mage::getModel('thub/run_error');
         return $xmlError->xmlErrorResponse($command, $code, $message, $provider, $request_id);
      }

   }
?>
