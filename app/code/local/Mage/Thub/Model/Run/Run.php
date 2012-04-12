<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#



class Mage_Thub_Model_Run_Run extends Mage_Thub_Model_Run_Abstract
  {

    /**
     * Available options
     *
     * @var array
     */
// error_reporting(E_ALL && ~E_NOTICE );


   const __ENCODE_RESPONSE = true;
//    const __ENCODE_RESPONSE = false;

    protected $__ENCODE_RESPONSE;
    protected $_options;
    protected $STORE_NAME= 'NOT_FOUND_STORE_NAME';
    protected $STORE_ID =null;
//    protected $status_list = array("NEW","COMPLETE","CLOSED","PENDING", "PROCESSING", "HOLDED", "CANCELED");
    protected $RequestOrders = array();

    const STATE_NEW        = 'new';
    const STATE_PROCESSING = 'processing';
    const STATE_COMPLETE   = 'complete';
    const STATE_CLOSED     = 'closed';
    const STATE_CANCELED   = 'canceled';
    const STATE_HOLDED     = 'holded';
    const STATE_UNHOLDED   = 'unholded';
    const STATE_PENDINGPAYMENT = 'pending_payment';
    const _ENCODE_RESPONSE = 'false';

    protected   $types = array('AE'=>'Amex', 'VI'=>'Visa', 'MC'=>'MasterCard', 'DI'=>'Discover','OT'=>'Other',''=>'');

    protected   $carriers = array('dhl'=>'DHL',
                                  'fedex'=>'FedEx',
                                  'ups'=>'UPS',
                                  'usps'=>'USPS',
                                  'freeshipping'=>"Free Shipping" ,
                                  'flatrate'=>"Flat Rate",
                                  'tablerate'=>"Best Way");
                        //"titul" => 'code'
    protected   $carriers_ =array('DHL'=>'dhl',
                                  'FEDEX'=>'fedex',
                                  'UPS'=>'ups',
                                  'USPS'=>'usps');


 protected   $PayMethodsCC = array( 'paypal_express'      ,
                                       'paypal_standard'     ,
                                       'paypal_direct'       ,
                                       'paypaluk_express'    ,
                                       'paypaluk_direct'     ,
                                       'ccsave'              ,
                                       'authorizenet'        ,
                                       'payflow_pro'         ,
																			 'linkpoint');


 protected   $PayMethods = array(   'paypal_express'      => 'PayPal Express',
                                       'paypal_standard'     => 'PayPal Standard',
                                       'paypal_direct'       => 'Paypal Direct',

                                       'paypaluk_express'    => 'PaypalUk Express',
                                       'paypaluk_direct'     => 'PaypalUk Direct',

                                       'ccsave'              => 'Credit Card (saved)',
                                       'checkmo'             => 'Check / Money order',
                                       'free'                => 'No Payment Information Required',
                                       'purchaseorder'       => 'Purchase Order' ,

                                       'authorizenet'        => 'Credit Card (Authorize.net)',
                                       'payflow_pro'         => 'Credit Card (Payflow Pro)',
									   
																				'linkpoint'           => 'linkpoint'  
                                      );


    protected $status_list = array(
                      self::STATE_PROCESSING,
                      self::STATE_COMPLETE,
                      self::STATE_CLOSED,
                      self::STATE_CANCELED,
                      self::STATE_HOLDED,
                      self::STATE_UNHOLDED
                      );

    /**
     * Script arguments
     *
     * @var array
     */
    protected $_orders = null;

    protected $xmlRequest = null;
    protected $xmlResponse = null;
    protected $root = null;
    protected $envelope = null;

    protected $_current_order = null;
    protected $send_email = false;
    protected $Msg = array();
    protected $result = '';

    protected $RequestParams = array();
    protected $filters = array();
    protected $QB_NUMBER_OF_DAYS,$QB_ORDER_START_NUMBER,$QB_PROVIDER;


    protected $_tagName = array(),  $_tagAttributes= array(),$_tagContents=array(),$_tagTags= array();

    /**
     * Installer data model to store data between installations steps
     *
     * @var Mage_Install_Model_Installer_Data|Mage_Install_Model_Session
     */
  //  protected $_dataModel;

    /**
     * Current application
     *
     * @var Mage_Core_Model_App
     */
   // protected $_app;

    public function Get__ENCODE_RESPONSE (){
    	if ($this->__ENCODE_RESPONSE===true){;
    	   return false;
    	}
    	return self::__ENCODE_RESPONSE;
//    	return $this->__ENCODE_RESPONSE;
    }
    public function Set__ENCODE_RESPONSE ($fl = false){
    	$this->__ENCODE_RESPONSE= $fl;
    }

    /**
     * Run
     *
     * @return boolean
     */
    public function run()
    {

      $string = Mage::app()->getRequest()->getParam('request');
      if (empty($string)){
         $error = 'Empty request';
//         $this->addError($error);
         echo($error);
          return false;
      }else{

        //    echo($string);
         $this->LoadXml($string);
         $this->CheckXmlRequst();
         $this->CheckUser();
         $this->SetDefaultStoreName();
         $this->SetParametersFilter();
         $this->CreateHeaderXml();

         switch($this->RequestParams['COMMAND']){
            case "GETORDERS":   $this->GetOrders();
            break;
            case "UPDATE"."ORDERS"."SHIPPING"."STATUS":   $this->UpdateOrdersShippingStatus();
            break;
            case "UPDATE"."ORDERS"."PAYMENT"."STATUS":   $this->UpdateOrdersPaymentStatus();
            break;
            case "UPDATE"."INVENTORY":   $this->UpdateInventory();
            break;
            default:
                print($this->xmlErrorResponse('unknown', '9999', 'Unknown Command '.$this->RequestParams['COMMAND'], $this->STORE_NAME, ''));exit;
         }

      	return true;
      }


    }

   public function UpdateInventory(){
      try{
          $_inventory = Mage::getModel('thub/run_inventory');
          $_inventory->init($this->RequestParams);
          $_inventory->setXmlRequest($this->xmlRequest);
          $_inventory->runInventory();
          $_inventory->showXML();
          return true;
      } catch (Exception $e) {
          print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9001',
                "Critical Error UpdateInventory (Exception e)".$e, $this->STORE_NAME, ''));
          exit;
      }
   }



    public function LoadXml($string){


          $this->xmlRequest = Mage::getModel('thub/run_thubxml');
          $this->xmlRequest->loadString($string);
          $this->xmlRequest->parse();
          return true;
    }


    public  function CheckUser(){
         try{


        // return true;
       $user = Mage::getSingleton('admin/user');
       $userRole= Mage::getSingleton('admin/mysql4_acl_role');
       //$userRole = Mage::getSingleton('admin/role');



       $username=$this->RequestParams['USERID'];
       $password=$this->RequestParams['PASSWORD'];
       if ($user->authenticate($username, $password)) {
//            $role=$user->getRoles($user);
          //  $user_=$user->hasAssig   ned2Role($user);
            $loadRole=$userRole->load($user->getRoles($user));
/*             if (strtoupper($loadRole['role_name'])!="ADMINISTRATORS"){
                 print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9000',
                         'You have a no rights. Access level.', $this->STORE_NAME, ''));
                 exit;
             }
             */
/*             if ($row->block){
                 print(xmlErrorResponse($this->RequestParams['COMMAND'], '9000',
                     'User block. Remove blocking', STORE_NAME, ''));
                 exit;
             }
  */
        }else{
           print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9000',
                   'Order download service authentication failure - Login/Password supplied did not match', $this->STORE_NAME, ''));
           exit;
        }

         } catch (Exception $e) {
              print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9001',
                   'Service authentication failure - ', $this->STORE_NAME, ''));
              exit;
         }


    }

   public  function SetParametersFilter(){

// --------- MB 30/05/2009 start --------
      if (isset($this->RequestParams['NOTENCODE'])) {
         $this->Set__ENCODE_RESPONSE(true);
      } else{
         $this->Set__ENCODE_RESPONSE(false);
      }
// --------- MB 30/05/2009 end ----------

      if (isset($this->RequestParams['STATUS'])) {
         $this->filters['QB_STATUS']= $this->RequestParams['STATUS'];
      } else{
         $this->filters['QB_STATUS']= "ALL";
       }
      if (isset($this->RequestParams['PROVIDER'])) {
         $this->QB_PROVIDER= $this->RequestParams['PROVIDER'];
      } else{
         $this->QB_PROVIDER= "";
       }

      if (isset($this->RequestParams['SECURITYKEY'])&&$this->RequestParams['SECURITYKEY']!=""){
         $this->filters['PROVIDER']=" and o.vendor_id=v.vendor_id ";
      }else{
         $this->filters['PROVIDER']=" and o.vendor_id=v.vendor_id ";
      }

      if (isset($this->RequestParams['LIMITORDERCOUNT'])&&(int)$this->RequestParams['LIMITORDERCOUNT']!=0){
         $this->filters['QB_ORDERS_PER_RESPONSE']=(string)$this->RequestParams['LIMITORDERCOUNT'];
      }else{
         $this->filters['QB_ORDERS_PER_RESPONSE']='25';
      }
       $filter_order_start_number="";
      if (isset($this->RequestParams['ORDERSTARTNUMBER'])){
      	$this->QB_ORDER_START_NUMBER = (int)$this->RequestParams['ORDERSTARTNUMBER'];
//         $this->filters['QB_ORDER_START_NUMBER']=array("gteq"=>(string)$this->QB_ORDER_START_NUMBER);
        if($this->QB_ORDER_START_NUMBER>0){
            $this->filters['QB_ORDER_START_NUMBER']=array("gteq"=>(int)$this->QB_ORDER_START_NUMBER);
            $this->filters['QB_ORDER_CREATE_DATE']=array("nin"=>date("Y-m-d",mktime(0,0,0,0,0,0)));
            
//$thedate = date("Y-m-d", strtotime("1 year ago"));
//$thesearch =array(array('updated_at'=>array('from'=>"$thedate")));

            
        }else{
         	$this->QB_ORDER_START_NUMBER = 0;
        }
      }else{
         	$this->QB_ORDER_START_NUMBER = 0;
      }
      
      
      
      
      if((int)$this->QB_ORDER_START_NUMBER == 0){
       	if (isset($this->RequestParams['NUMBEROFDAYS'])){
            $this->QB_NUMBER_OF_DAYS =(int) $this->RequestParams['NUMBEROFDAYS'];
            $this->filters['QB_ORDER_CREATE_DATE']=array("from"=>date("Y-m-d",mktime(0,0,0,date("m"),date("d")-$this->QB_NUMBER_OF_DAYS+1,date("Y"))));
            $this->filters['QB_ORDER_START_NUMBER']=array("nin"=>"0");
         }else{
            $this->filters['QB_ORDER_CREATE_DATE']=array("nin"=>date("Y-m-d",mktime(0,0,0,0,0,0)));
            $this->filters['QB_ORDER_START_NUMBER']=array("gteq"=>(int)$this->QB_ORDER_START_NUMBER);
         }
      }
      
			if (isset($this->RequestParams['DOWNLOADSTARTDATE'])){
				//$date1='2010-02-21 02:13:40';
				$date1 = $this->RequestParams['DOWNLOADSTARTDATE'];
				
				$thedate = date("Y-m-d H:m:s", strtotime($date1));
				$this->filters['QB_ORDER_CREATE_DATE']=array('created_at'=>array('from'=>"$thedate"));
			}      
      
      return true;
   }


      public  function  CreateHeaderXml(){
         $this->xmlResponse = Mage::getModel('thub/run_thubxml');
         $this->xmlResponse->version='1.0';
         $this->xmlResponse->encoding='ISO-8859-1';

      	$this->root = $this->xmlResponse->createTag("RESPONSE", array('Version'=>'4.5'));
      	$this->envelope = $this->xmlResponse->createTag("Envelope", array(), '', $this->root);
      	$this->xmlResponse->createTag("Command", array(), $this->RequestParams['COMMAND'], $this->envelope);
      }


      public  function  SetDefaultStoreName(){
         $storeId = 1;
         if (isset($this->RequestParams['SECURITYKEY'])&&$this->RequestParams['SECURITYKEY']!=""){
            	$storeId=(int)$this->RequestParams['SECURITYKEY'];
         }


            $storeCollection = Mage::getModel('core/store')
               ->getCollection()
               ->addFieldToFilter('store_id', $storeId)
               ->load();




  /*          $storeCollection = Mage::getModel('core/store_group')
               ->getCollection()
               ->addFieldToFilter('default_store_id', 1)
               ->load();
    */


//          echo("<pre>");
//          var_dump($storeCollection->toArray());
//          echo("</pre>");

          foreach ($storeCollection->toArray() as $store){
             if (isset($store[0]['name'])){
                $this->STORE_NAME=$store[0]['name'];
             }
             if (isset($store[0]['store_id'])){
                $this->STORE_ID=$store[0]['store_id'];
             }

          }

          Mage::register('store_id', $this->STORE_ID);
      }

      public  function  GetStoreId(){
         return $this->STORE_ID;
      }

      public  function  UpdateOrdersPaymentStatus(){
         try{
            $ordersTag = $this->xmlRequest->getChildByName(0, "ORDERS");
            if ((count($ordersTag) <1)||$ordersTag==null){
                 print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
                         'Error XML request! Not found required tag Orders', $this->STORE_NAME, ''));
                 exit;
            }


            $this->xmlRequest->getTag($ordersTag, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);
            if (count($_tagTags) == 0) $no_orders = true; else $no_orders = false;

            $this->xmlResponse->createTag("StatusCode", array(), ($no_orders?"1000":"0"), $this->envelope);
            $this->xmlResponse->createTag("StatusMessage", array(), $no_orders?"No Orders returned":"All Ok", $this->envelope);

            if ($no_orders){
              print($this->xmlResponse->generate()); exit;
            }



            $ordersNode = $this->xmlResponse->createTag("Orders", array(), '', $this->root);
            foreach($_tagTags as $k=>$v){
              $this->Msg = array();
              $this->xmlRequest->getTag($v, $_tagName, $_tagAttributes, $_tagContents, $_orderTags);
              $orderNode = $this->xmlResponse->createTag("Order",  array(), '',     $ordersNode);
              unset($TAGNAME);
//              unset($issetTag);
              $issetTag['HOSTORDERID']=true;
              $issetTag['LOCALORDERID']=true;
              $issetTag['PAYMENTSTATUS']=true;
              foreach($_orderTags as $k1=>$v1){
                $this->xmlRequest->getTag($v1, $_tagName, $_tagAttributes, $_tagContents, $_tempTags);
                $TAGNAME=strtoupper($_tagName);
                $this->RequestOrders[$TAGNAME] = $_tagContents;

                switch ($TAGNAME){
                   case 'HOSTORDERID':
                      $issetTag['HOSTORDERID']=false;
                   break;
                   case 'LOCALORDERID':
                      $issetTag['LOCALORDERID']=false;
                   break;
                   case 'PAYMENTSTATUS':
                      $issetTag['PAYMENTSTATUS']=false;
                   break;
                }
              }


            	$errorMsg = '';
            	foreach($issetTag as $key => $fl){
            		if ($fl===true){
            			$errorMsg .= $key." ";
            		}
            	}
               if ($errorMsg!=''){
                  print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
                         'Error XML request! Not found required tags ('.$errorMsg.') in XML request', $this->STORE_NAME, ''));

                  exit;
               }


               if (isset($this->RequestOrders['CLEAREDON'])){


               }


               $this->xmlResponse->createTag('HostOrderID',  array(),$this->RequestOrders['HOSTORDERID'], $orderNode);
               $this->xmlResponse->createTag('LocalOrderID',  array(), $this->RequestOrders['LOCALORDERID'], $orderNode);


               $this->xmlResponse->createTag('LocalOrderID',  array(), $this->RequestOrders['LOCALORDERID'], $orderNode);

               $orders = Mage::getResourceModel('sales/order_collection')
                  ->addAttributeToSelect('*')
                  ->addFieldToFilter('increment_id', $this->RequestOrders['HOSTORDERID'])
                  ->addAttributeToFilter('store_id', $this->GetStoreId())
                  ->load();
               $orders_array=$orders->toArray();

              // echo("<pre>"); var_dump($orders_array,$this->RequestOrders['HOSTORDERID']); echo("</pre>");


               if (count($orders_array)==0){
                  $this->Msg[] = 'Order not found';
                  $this->result = 'Failed';
               }else{
                  foreach($orders_array as $orders_el){
                  	$this->_current_order = Mage::getModel('sales/order')
                           ->load($orders_el['entity_id']);
                     if ($this->SetPaymentStatus()){
                           $this->result = 'Success';
                     }else{
                           $this->result = 'Failed';
                     }

                     break;
                  }
               }




               //$this->Msg='';
  //              var_dump($orders_array[1]);
               $this->xmlResponse->createTag('HostStatus',  array(), $this->result, $orderNode);
               if (count($this->Msg)>0){
               	$ind=1;
               	foreach($this->Msg as $Msg){
                     $this->xmlResponse->createTag('StatusMessage'.$ind++,  array(), $Msg, $orderNode);
                  }
               }

            }

              print($this->xmlResponse->generate()); exit;


         } catch (Exception $e) {
             Mage::printException($e);
         }
           return true;

      }
    //***************************************************
    //
    //      Update Orders Shipping Status Service
    //
    //***************************************************

      public  function  UpdateOrdersShippingStatus(){

         try{


            $ordersTag = $this->xmlRequest->getChildByName(0, "ORDERS");
            if ((count($ordersTag) <1)||$ordersTag==null){
                 print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
                         'Error XML request! Not found required tag Orders', $this->STORE_NAME, ''));
                 exit;
            }


            $this->xmlRequest->getTag($ordersTag, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);
            if (count($_tagTags) == 0) $no_orders = true; else $no_orders = false;

            $this->xmlResponse->createTag("StatusCode", array(), ($no_orders?"1000":"0"), $this->envelope);
            $this->xmlResponse->createTag("StatusMessage", array(), $no_orders?"No Orders returned":"All Ok", $this->envelope);

            if ($no_orders){
              print($this->xmlResponse->generate()); exit;
            }



            $ordersNode = $this->xmlResponse->createTag("Orders", array(), '', $this->root);
            foreach($_tagTags as $k=>$v){
              $this->Msg = array();
              $this->xmlRequest->getTag($v, $_tagName, $_tagAttributes, $_tagContents, $_orderTags);
              $orderNode = $this->xmlResponse->createTag("Order",  array(), '',     $ordersNode);
              unset($TAGNAME);
              unset ($this->RequestOrders);
              $issetTag['HOSTORDERID']=true;
              $issetTag['LOCALORDERID']=true;
              $issetTag['SHIPPEDON']=true;
              $issetTag['SHIPPEDVIA']=true;
              $issetTag['TRACKINGNUMBER']=true;
              foreach($_orderTags as $k1=>$v1){
                $this->xmlRequest->getTag($v1, $_tagName, $_tagAttributes, $_tagContents, $_tempTags);
                $TAGNAME=strtoupper($_tagName);
                $this->RequestOrders[$TAGNAME] = $_tagContents;
//                $issetTag[$TAGNAME]=true;

                switch ($TAGNAME){
                   case 'HOSTORDERID':
                      $issetTag['HOSTORDERID']=false;
                   break;
                   case 'LOCALORDERID':
                      $issetTag['LOCALORDERID']=false;
                   break;
                   case 'SHIPPEDON':
                      $issetTag['SHIPPEDON']=false;
                   break;
                   case 'SHIPPEDVIA':
                      $issetTag['SHIPPEDVIA']=false;
                   break;
                   case 'TRACKINGNUMBER':
                      $issetTag['TRACKINGNUMBER']=false;
                      $this->RequestOrders[$TAGNAME] = explode(",",$this->RequestOrders[$TAGNAME]);

                   break;
                }
/*                if (strtoupper($_tagName)=='HOSTORDERID')
                  $this->xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
                if (strtoupper($_tagName)=='LOCALORDERID')
                  $this->xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
*/
              }
//              if ($issetTag['HOSTORDERID']||$issetTag['LOCALORDERID']||$issetTag['SHIPPEDON']||$issetTag['SHIPPEDVIA']||$issetTag['TRACKINGNUMBER']){
            	$errorMsg = '';
            	foreach($issetTag as $key => $fl){
            		if ($fl){  $errorMsg .= $key." ";
            		}
            	}
               if ($errorMsg!=""){
                  print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
                         'Error XML request! Not found required tags ('.$errorMsg.') in XML request', $this->STORE_NAME, ''));

                  exit;
               }

               if (isset($this->RequestOrders['NOTIFYCUSTOMER'])){
                  $this->send_email=(strtoupper($this->RequestOrders['NOTIFYCUSTOMER'])=="YES");
               }


               $this->xmlResponse->createTag('HostOrderID',  array(),$this->RequestOrders['HOSTORDERID'], $orderNode);
               $this->xmlResponse->createTag('LocalOrderID',  array(), $this->RequestOrders['LOCALORDERID'], $orderNode);


               $orders = Mage::getResourceModel('sales/order_collection')
                  ->addAttributeToSelect('*')
                  ->addFieldToFilter('increment_id', $this->RequestOrders['HOSTORDERID'])
                 // ->addAttributeToFilter('store_id', $this->GetStoreId())
                  ->load();
               $orders_array=$orders->toArray();

             //  echo("<pre>"); var_dump($orders_array); echo("</pre>");


               if (count($orders_array)==0){
                  $this->Msg[] = 'Order not found';
                  $this->result = 'Failed';
               }else{
   
					//Update for magento 
				    if(array_key_exists('items',$orders_array))
		             $orders_array_w =$orders_array['items'];
	                 else
		             $orders_array_w =$orders_array;	
                    // End 
				  					  
                  foreach($orders_array_w as $orders_el){
				      $this->_current_order = Mage::getModel('sales/order')
                           ->load($orders_el['entity_id']);
                     if ($this->AddInvoice()){
                           $this->result = 'Success';
                     }
                     if ($this->AddShipment()){
                           $this->result = 'Success';
                     }elseif ($this->ChangeShipment()){
                           $this->result = 'Success';
                     }else{
                           $this->result = 'Failed';
                     }

                     break;
                  }
               }


               $this->xmlResponse->createTag('HostStatus',  array(), $this->result, $orderNode);
               if (count($this->Msg)>0){
               	$ind=1;
               	foreach($this->Msg as $Msg){
                     $this->xmlResponse->createTag('StatusMessage'.$ind++,  array(), $Msg, $orderNode);
                  }
               }

            }



              print($this->xmlResponse->generate()); exit;


         } catch (Exception $e) {
             Mage::printException($e);
         }
           return true;
      }


    protected function _initShipment(){
         try {
         	$shipment = false;
/*            $orders = Mage::getModel('sales/order')
                  ->load($orderID);
*/          if (!$this->_current_order->getId()) {
                  $this->Msg[] = 'Error. Order not longer exist.';
                  $this->result = 'Failed';
                  return false;
            }
            if (!$this->_current_order->canShip()) {

            //  Ship �沲�裡!
            // Added Track and Content

                  return false;
            }

             // Not Ship

            $convertor  = Mage::getModel('sales/convert_order');
            $_shipment    = $convertor->toShipment($this->_current_order);

//            $savedQtys = $this->_getItemQtys();
            $savedQtys = array();
            // 寡ᣫ殨㡪 櫲񞟓hipment 塭 Item  饠Order
            // Item - 衯鲨 믲ﱻ㡢�柡 Ӯ沲�衯鲨.

            //$this->_getItemQtys();
            foreach ($this->_current_order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip()) {
                    continue;
                }
                $_item = $convertor->itemToShipmentItem($orderItem);
                if (isset($savedQtys[$orderItem->getId()])) {
                    $qty = $savedQtys[$orderItem->getId()];
                }
                else {
                    $qty = $orderItem->getQtyToShip();
                }
                $_item->setQty($qty);
            	$_shipment->addItem($_item);
            }

	         foreach($this->RequestOrders['TRACKINGNUMBER'] as $trackNumber){
	            if (!empty($trackNumber)){
                   if (!$CarrierCode =$this->getShippingCode($this->RequestOrders['SHIPPEDVIA'])){
                   	  $CarrierCode="custom";
      	              $Title = $this->RequestOrders['SHIPPEDVIA'];
                   }elseif (isset($this->RequestOrders['SERVICEUSED'])){
     	                 $Title = $this->RequestOrders['SERVICEUSED'];
                   }else{
      	              $Title = $this->RequestOrders['SHIPPEDVIA'];
                   }

                   $_track = Mage::getModel('sales/order_shipment_track')
                       ->setNumber($trackNumber)
                       ->setCarrierCode($CarrierCode)
                       ->setTitle($Title);
                   $_shipment->addTrack($_track);
               }
            }
//            }*/

         return $_shipment;
         }catch (Exception $e) {
        	      $this->Msg[] = "Critical Error _initShipment (Exception e)" ;
        }
    }

    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    public function ChangeShipment(){
        try {

            if (!$this->_current_order->getId()) {
                  $this->Msg[] = 'Error. Order not longer exist.';
                  $this->result = 'Failed';
                  return false;
            }
            if (!$this->_current_order->canUnhold()) {

            //  Ship �沲�裡!
            // Added Track and Content


                  $_shipments=$this->_current_order->getShipmentsCollection();
                  $shipments_array=$_shipments->toarray();

                  if (count($_shipments)==0){
                     $this->Msg[] = 'Error. Not object shipment.';
                     $this->result = 'Failed';
                     return false;
                  }
/*                  echo("-------------");
                   echo("<pre>"); var_dump($shipments_array); echo("</pre>");*/

                 foreach ($_shipments as $_shipment){
            	         foreach($this->RequestOrders['TRACKINGNUMBER'] as $trackNumber){
            	            if (!empty($trackNumber)){
              	                if (!$CarrierCode =$this->getShippingCode($this->RequestOrders['SHIPPEDVIA'])){
              	                	  $CarrierCode="custom";
                  	              $Title = $this->RequestOrders['SHIPPEDVIA'];
              	                }elseif (isset($this->RequestOrders['SERVICEUSED'])){
                 	                 $Title = $this->RequestOrders['SERVICEUSED'];
              	                }else{
                  	              $Title = $this->RequestOrders['SHIPPEDVIA'];
              	                }

                               $_track = Mage::getModel('sales/order_shipment_track')
                                   ->setNumber($trackNumber)
                                   ->setCarrierCode($CarrierCode)
                                   ->setTitle($Title);
                               $_shipment->addTrack($_track);
                           }
                        }
                        break;
                 }
                  $this->Msg[] = 'Add Track Information.';

                   $comment = "\nOrder shipped on ".$this->RequestOrders['SHIPPEDON'].
                                  " via ".$this->RequestOrders['SHIPPEDVIA'].
                                  " track number(s) ".implode(",",$this->RequestOrders['TRACKINGNUMBER']).
                      (isset($this->RequestOrders['SERVICEUSED'])?
                            " using ".$this->RequestOrders['SERVICEUSED']." service.\n" : "."
                      );

                   $_shipment->addComment($comment,true );

                   if ($this->send_email) {
                       $_shipment->setEmailSent(true);
                   }
                            // $this->RequestOrders['send_email']
                   $_shipment->Save();
                   $this->Msg[] = 'Add Content Information.';
                   $_shipment->sendUpdateEmail($this->send_email, $comment);
                   if ((strtoupper($this->RequestOrders['NOTIFYCUSTOMER'])=="YES")){
                      $this->Msg[] = 'Send Mail.';
                   }
                   return true;
                  break;


            }else {
                return false;
            }

        }catch (Exception $e) {
        	      $this->Msg[] = "Critical Error ChangeShipment (Exception e)" ;
        }
    }

    public function AddShipment(){
//        $data = $this->getRequest()->getPost('shipment');
        try {
            if ($shipment = $this->_initShipment()) {
                $shipment->register();
                $this->Msg[] = 'Create Shipment .';

                $comment = "\nOrder shipped on ".$this->RequestOrders['SHIPPEDON'].
                               " via ".$this->RequestOrders['SHIPPEDVIA'].
                               " track number ".implode(",",$this->RequestOrders['TRACKINGNUMBER']).
                   (isset($this->RequestOrders['SERVICEUSED'])?
                         " using ".$this->RequestOrders['SERVICEUSED']." service.\n" : "."
                   );

                $shipment->addComment($comment,true );
                $this->Msg[] = 'Add Content Information.';

                if ($this->send_email) {
                    $shipment->setEmailSent(true);
                }

                $this->_saveShipment($shipment);
                $this->Msg[] = 'Save Shipment .';
                $shipment->sendUpdateEmail($this->send_email, $comment);
                if ($this->send_email){
                   $this->Msg[] = 'Send Mail.';
                }

                return true;
            }else {
                return false;
            }
        }catch (Mage_Core_Exception $e) {
        	      $this->Msg[] = "Critical Error AddShipment (Mage_Core_Exception e)";
      //      $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
        	      $this->Msg[] = "Critical Error AddShipment (Exception e)" ;
         //   $this->_getSession()->addError($this->__('Can not save shipment.'));
        }
//        $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));

    }

      protected function _initInvoice($update = false)
    {
        $_invoice = false;




      //  if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
      //      $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
      //  }
       // elseif ($orderId = $this->getRequest()->getParam('order_id')) {
       //     $order      = Mage::getModel('sales/order')->load($orderId);
            /**
             * Check order existing
             */


          if (!$this->_current_order->getId()) {
                  $this->Msg[] = 'Error. Order not longer exist.';
                  $this->result = 'Failed';
                  return false;
            }
            if (!$this->_current_order->canInvoice()) {

            //  Ship �沲�裡!
            // Added Track and Content
                $this->Msg[] = 'Can not create Invoice';

                  return false;
            }

            /**
             * Check invoice create availability
             */


            $convertor  = Mage::getModel('sales/convert_order');
            $_invoice    = $convertor->toInvoice($this->_current_order);

//            $savedQtys = $this->_getItemQtys();
            $savedQtys = array();
            // 寡ᣫ殨㡪 櫲񞟓hipment 塭 Item  饠Order
            // Item - 衯鲨 믲ﱻ㡢�柡 Ӯ沲�衯鲨.

            //$this->_getItemQtys();
//            $convertor  = Mage::getModel('sales/convert_order');
///            $invoice    = $convertor->toInvoice($order);

//            $savedQtys = $this->_getItemQtys();
//            foreach ($order->getAllItems() as $orderItem) {
            foreach ($this->_current_order->getAllItems() as $orderItem) {
                if (!$orderItem->isDummy() && !$orderItem->getQtyToInvoice()) {
                    continue;
                }

//                if (!$update && $orderItem->isDummy() && !empty($savedQtys) && !$this->_needToAddDummy($orderItem, $savedQtys)) {
                if ($orderItem->isDummy() && !empty($savedQtys) ) {
                    continue;
                }
                $item = $convertor->itemToInvoiceItem($orderItem);

                if (isset($savedQtys[$orderItem->getId()])) {
                    $qty = $savedQtys[$orderItem->getId()];
                }
                else {
                    if ($orderItem->isDummy()) {
                        $qty = 1;
                    } else {
                        $qty = $orderItem->getQtyToInvoice();
                    }
                }
                $item->setQty($qty);
                $_invoice->addItem($item);
            }
            $_invoice->collectTotals();
       // }

       // Mage::register('current_invoice', $invoice);
        return $_invoice;
    }

    /**
     * Save data for invoice and related order
     *
     * @param   Mage_Sales_Model_Order_Invoice $invoice
     * @return  Mage_Adminhtml_Sales_Order_InvoiceController
     */
    protected function _saveInvoice($invoice)
    {
        $invoice->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return $this;
    }




    public function AddInvoice()
    {
     //   $data = $this->getRequest()->getPost('invoice');
        try {
            if ($invoice = $this->_initInvoice()) {
  /*
                if (!empty($data['capture_case'])) {
                    $invoice->setRequestedCaptureCase($data['capture_case']);
                }

                if (!empty($data['comment_text'])) {
                    $invoice->addComment($data['comment_text'], isset($data['comment_customer_notify']));
                }
*/
                $invoice->register();
                $this->Msg[] = 'Add Invoice';

/*                if (!empty($data['send_email'])) {
                    $invoice->setEmailSent(true);
                }
  */
                $invoice->getOrder()->setIsInProcess(true);

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
/*                $shipment = false;
                if (!empty($data['do_shipment'])) {
                    $shipment = $this->_prepareShipment($invoice);
                    if ($shipment) {
                        $transactionSave->addObject($shipment);
                    }
                }
                $transactionSave->save();
  */
                /**
                 * Sending emails
                 */
//                $comment = '';
//                if (isset($data['comment_customer_notify'])) {
//                    $comment = $data['comment_text'];
//                }
//                $invoice->sendEmail(!empty($data['send_email']), $comment);
//                if (is_object($shipment)) {
                  //  $shipment->sendEmail(!empty($data['send_email']));
//                }

//                if (!empty($data['do_shipment'])) {
                   // $this->_getSession()->addSuccess($this->__('Invoice and shipment was successfully created.'));
//                }
//                else {
                    //$this->_getSession()->addSuccess($this->__('Invoice was successfully created.'));
//                }


                $this->_saveInvoice($invoice);
                $this->Msg[] = 'Save Invoice .';
               // $this->_redirect('*/sales_order/view', array('order_id' => $invoice->getOrderId()));
                return;
            }
            else {
            //    $this->_forward('noRoute');
                $this->Msg[] = 'Filed create Invoice';
                return;
            }

        }catch (Mage_Core_Exception $e) {
        	      $this->Msg[] = "Critical Error AddInvoice (Mage_Core_Exception e)";
      //      $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
        	      $this->Msg[] = "Critical Error AddInvoice (Exception e)";
         //   $this->_getSession()->addError($this->__('Can not save shipment.'));
        }

       // $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
    }










     public function SetPaymentStatus (){
         try {
            if (!$this->_current_order->getId()) {
                  $this->Msg[] = 'Error. Order not longer exist.';
                  $this->result = 'Failed';
                  return false;
            }
            switch(strtolower($this->RequestOrders['HOSTSTATUS'])){

/*    self::STATE_COMPLETE,
                      self::STATE_CLOSED,
                      self::STATE_CANCELED,
                      self::STATE_HOLDED);*/

            	case self::STATE_CANCELED:
                  if ($this->_current_order->getState() === self::STATE_CANCELED )  {
                  	return false;
                  }elseif (!$this->_current_order->canCancel()){
                       return false;
            	   } else{
            	   	$this->_current_order->cancel()->Save();
            	   }
            	break;
            	case self::STATE_CLOSED:
                  if ($this->_current_order->getState() === self::STATE_CLOSED )  {
                  	return false;
                  }

            	break;
            	case self::STATE_HOLDED:
                  if ($this->_current_order->getState() === self::STATE_HOLDED )  {
                        return false;
                  }elseif (!$this->_current_order->canHold()){
                        return false;
            	   } else{
            	   	$this->_current_order->hold()->Save();

            	   }
            	break;
            	case self::STATE_UNHOLDED:
                  if (!$this->_current_order->canUnhold()){
                        return false;
            	   } else{
            	   	$this->_current_order->unhold()->Save();
            	   }
            	break;
            	case self::STATE_COMPLETE:
                  if ($this->_current_order->getState() === self::STATE_HOLDED )  {
                  	return false;
            	   }
            	break;
            	case self::STATE_PROCESSING:
                  if ($this->_current_order->getState() === self::STATE_PROCESSING )  {
                  	return false;
            	   }
            	break;
            	default:
            	   return false;

            }

         }catch (Exception $e) {
        	      $this->Msg[] = "Critical Error SetPaymentStatus (Exception e)" ;
        	      return false;
         }
         return true;
     }


     public function sendOrderUpdateEmail($notifyCustomer=true, $comment='')
    {
        $bcc = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
        if (!$notifyCustomer && !$bcc) {
            return $this;
        }

        $mailTemplate = Mage::getModel('core/email_template');
        if ($notifyCustomer) {
            $customerEmail = $this->getCustomerEmail();
            $mailTemplate->addBcc($bcc);
        } else {
            $customerEmail = $bcc;
        }

        if ($this->getCustomerIsGuest()) {
            $template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $this->getStoreId());
            $customerName = $this->getBillingAddress()->getName();
        } else {
            $template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $this->getStoreId());
            $customerName = $this->getCustomerName();
        }

        $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store' => $this->getStoreId()))
            ->sendTransactional(
                $template,
                Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $this->getStoreId()),
                $customerEmail,
                $customerName,
                array(
                    'order'     => $this,
                    'billing'   => $this->getBillingAddress(),
                    'comment'   => $comment
                )
            );
        return $this;
    }










    //***************************************************
    //
    //      update  Orders Service
    //
    //***************************************************
      public  function  GetOrders(){

       try{

         $this->_orders = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToSelect('*')
            ->joinAttribute('billing_firstname', 'order_address/firstname', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_lastname', 'order_address/lastname', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_street', 'order_address/street', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_company', 'order_address/company', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_city', 'order_address/city', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_region', 'order_address/region', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_country', 'order_address/country_id', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_postcode', 'order_address/postcode', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_telephone', 'order_address/telephone', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_fax', 'order_address/fax', 'billing_address_id', null, 'left')
            ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_street', 'order_address/street', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_company', 'order_address/company', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_city', 'order_address/city', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_region', 'order_address/region', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_country', 'order_address/country_id', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_postcode', 'order_address/postcode', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_telephone', 'order_address/telephone', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_fax', 'order_address/fax', 'shipping_address_id', null, 'left')
           
      ->addFieldToFilter('created_at', array($this->filters['QB_ORDER_CREATE_DATE']))
     // ->addFieldToFilter('created_at',array('created_at'=>array('from'=>"$thedate")))
            
            ->addFieldToFilter('status', array("nin" => self::STATE_CANCELED))
            ->addFieldToFilter('status', array("nin" => self::STATE_CLOSED))
            ->addFieldToFilter('status', array("nin" => self::STATE_PENDINGPAYMENT))
            //->addFieldToFilter('increment_id', array($this->filters['QB_ORDER_START_NUMBER']))
            ->addAttributeToFilter('store_id', $this->GetStoreId())
            
            //->addAttributeToSort('increment_id', 'asc')
            ->addAttributeToSort('created_at', 'asc')
            
            ->setPageSize($this->filters['QB_ORDERS_PER_RESPONSE'])
            ->load();

  //        exit;


         if (count($this->_orders)==0){
         		print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '1000','No Orders returned', $this->STORE_NAME, ''));
           exit;
         }



        $this->OrderToXml( ) ;


        } catch (Exception $e) {
             Mage::printException($e);
        }
      }


      protected  function  OrderToXml($order_array = array() ){

               $orders_array=$this->_orders->toArray();
   /*            
               echo("<pre>");
               var_dump($orders_array);
               echo("</pre>");
	*/		   
			   
			$this->xmlResponse->createTag("StatusCode", array(), "0", $this->envelope);
        	$this->xmlResponse->createTag("StatusMessage", array(), "All OK", $this->envelope);
        	//$this->xmlResponse->createTag("Provider", array(), $this->QB_PROVIDER, $this->envelope);
        	$this->xmlResponse->createTag("Provider", array(), "Magento", $this->envelope);
            $ordersNode = $this->xmlResponse->createTag("Orders", array(), '', $this->root);
            foreach ($this->_orders as $_order) {

            $orders=$_order->toArray();

			/* echo("<pre>");
               var_dump($orders);
              echo("</pre>");
            */ 

			
            $_payment=$_order->getPayment();
            $payment=$_payment->toArray();
              /* echo("<pre>");
               var_dump($payment);
               echo("</pre>");
                */


            $orderNode = $this->xmlResponse->createTag("Order", array(), '', $ordersNode );

 // --------- MB 18/02/2009 start --------

            $_date=Mage::getModel("core/date");

            $timestamp_convert_data=$_date->timestamp($orders["created_at"]);
            $dateCreateOrder_=date("Y-m-d", $timestamp_convert_data);
            $timeCreateOrder_=date("h:i:s", $timestamp_convert_data);

// --------- MB 18/02/2009 end ----------

            $this->xmlResponse->createTag("OrderID",          array(), $orders['increment_id'], $orderNode );
            $this->xmlResponse->createTag("ProviderOrderRef", array(),$orders['increment_id'],  $orderNode);
            $this->xmlResponse->createTag("Date",             array(), $dateCreateOrder_   ,$orderNode );
            $this->xmlResponse->createTag("Time",             array(), $timeCreateOrder_   ,$orderNode );
            $this->xmlResponse->createTag("TimeZone",         array(), 'not found',                        $orderNode,  $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("UpdatedOn",       array(), $orders['created_at'], $orderNode);
            $this->xmlResponse->createTag("StoreId",          array(), $orders['store_id'], $orderNode);
            $this->xmlResponse->createTag("StoreName",        array(), $this->STORE_NAME,   $orderNode,  $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("CustomerID",       array(), $orders['customer_id'], $orderNode);
 //         $this->xmlResponse->createTag("SalesRep",         array(), "not found",            $orderNode,  $this->Get__ENCODE_RESPONSE());
           
           // Start - To Get Gift message for hole order <Ganesh> 08/11/2010
		    $comment1 = "";
            if (isset($orders['customer_note'])&&$orders['customer_note']!='')
		   {
               $comment1 = $orders['customer_note']."\r\n";
			 //$comment1 = "Customer special instructions to test"."\r\n";
           }
		   if (isset($orders["gift_message_id"])&&$orders["gift_message_id"]!=null&&$orders["gift_message_id"]!=0)
		   {
		      $_giftMessage = Mage::helper('giftmessage/message')->getGiftMessage($orders["gift_message_id"]);
		      $comment1 .= "Gift Message: ".$_giftMessage->getMessage();
		   }
		   if ($comment1 !=""){
		      $this->xmlResponse->createTag("Comment", array(), $comment1,    $orderNode,  $this->Get__ENCODE_RESPONSE());
		   }
		   
		    /*   start - Old code
		    if (isset($orders['customer_note'])&&$orders['customer_note']!=''){
               $this->xmlResponse->createTag("Comment",          array(), $orders['customer_note'],    $orderNode,  $this->Get__ENCODE_RESPONSE());
            }
			// Start - To Get Gift message for hole order <Ganesh> 08/11/2010
		    if (isset($orders["gift_message_id"])&&$orders["gift_message_id"]!=null&&$orders["gift_message_id"]!=0){
     		$_giftMessage = Mage::helper('giftmessage/message')->getGiftMessage($orders[gift_message_id]);
			$this->xmlResponse->createTag("GiftMessage", array(), $_giftMessage->getMessage(), $chargesNode);
           	}
            // End - To Get Gift message for hole order <Ganesh> 08/11/2010	
			End Old code */
           
           // End - To Get Gift message for hole order <Ganesh> 08/11/2010			   

		
            if (isset($orders['order_currency_code'])&&$orders['order_currency_code']!=''){
               $this->xmlResponse->createTag("Currency",          array(), $orders['order_currency_code'],    $orderNode,  $this->Get__ENCODE_RESPONSE());
            }
           // $this->xmlResponse->createTag("Currency",         array(), $orders['order_currency_code'],    $orderNode, $this->Get__ENCODE_RESPONSE());

            $BillNode = $this->xmlResponse->createTag("Bill", array(), "", $orderNode);
            $ShipNode  = $this->xmlResponse->createTag("Ship",    array(), '', $orderNode);
            $itemsNode  = $this->xmlResponse->createTag("Items",  array(), '', $orderNode);
            $chargesNode  = $this->xmlResponse->createTag("Charges", array(), '', $orderNode);

             /////////////////////////////////////
             //   billing info
             /////////////////////////////////////
            $PayStatus = "Cleared";
						if(!isset($orders['total_paid'])){
							$orders['total_paid'] = 0;
						}
            if (isset($orders['grand_total'])&&isset($orders['total_paid'])){
            	if ( ($orders['grand_total'] - $orders['total_paid'] )  > 0.00 )
                  $PayStatus = "Pending";
            }

            //$this->xmlResponse->createTag("GrandTotal",array(), $orders['grand_total'],   $BillNode);
            //$this->xmlResponse->createTag("TotalPaid",array(), $orders['total_paid'],   $BillNode);
            //$this->xmlResponse->createTag("PaymentDue",array(), $orders['grand_total'] - $orders['total_paid'],   $BillNode);


            $this->xmlResponse->createTag("PayStatus",array(), $PayStatus,   $BillNode);
            $this->xmlResponse->createTag("PayMethod", array(),$this->getPayMethodName($payment['method']),$BillNode, $this->Get__ENCODE_RESPONSE());



			if(!array_key_exists('billing_firstname',$orders) && !array_key_exists('billing_lastname',$orders) )
			{
			
				$billingAddressArray = $_order->getBillingAddress()->toArray();
				$orders["billing_firstname"]=	$billingAddressArray["firstname"];
				$orders["billing_lastname"]	=	$billingAddressArray["lastname"];
				$orders["billing_company"]	=	$billingAddressArray["company"];
				$orders["billing_street"]	=	$billingAddressArray["street"];
				$orders["billing_city"]		=	$billingAddressArray["city"];
				$orders["billing_region"]	=	$billingAddressArray["region"];
				$orders["billing_postcode"]	=	$billingAddressArray["postcode"];
				$orders["billing_country"]	=	$billingAddressArray["country_id"];
				//$orders["customer_email"]	=	$billingAddressArray["customer_email"]?$billingAddressArray["customer_email"]:$orders["customer_email"];
				if (isset($billingAddressArray["customer_email"])) {
					$orders["customer_email"]	=	$billingAddressArray["customer_email"];
				} else {
					$orders["customer_email"]	=	$orders["customer_email"];
				}
				$orders["billing_telephone"]=	$billingAddressArray["telephone"];
			}




					if (isset($orders['billing_firstname'])){
						$this->xmlResponse->createTag("FirstName", array(), $this->_maxlen($orders["billing_firstname"],45), $BillNode, $this->Get__ENCODE_RESPONSE());
					} else {
						$this->xmlResponse->createTag("FirstName", array(), "Guest", $BillNode);
					}
					if (isset($orders['billing_lastname'])){
            $this->xmlResponse->createTag("LastName", array(), $this->_maxlen($orders["billing_lastname"],45), $BillNode, $this->Get__ENCODE_RESPONSE());
					} else {
						$this->xmlResponse->createTag("LastName", array(), "Guest", $BillNode);
					}
            
          if (!empty($orders["billing_company"])){
               $this->xmlResponse->createTag("CompanyName", array(), $this->_maxlen($orders["billing_company"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
          }
               
						if (!empty($orders["billing_street"])){
            	$billing_street = explode("\n", $orders["billing_street"]);
            	$billing_address1 = (empty($billing_street[0])?"":$billing_street[0]);
            	$billing_address2 = (empty($billing_street[1])?"":$billing_street[1]);
	            $this->xmlResponse->createTag("Address1", array(), $this->_maxlen($billing_address1,75), $BillNode, $this->Get__ENCODE_RESPONSE());
	            $this->xmlResponse->createTag("Address2", array(), $this->_maxlen($billing_address2,75), $BillNode, $this->Get__ENCODE_RESPONSE());
						}
						
	            if (!empty($orders["billing_city"])) $this->xmlResponse->createTag("City", array(), $this->_maxlen($orders["billing_city"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["billing_region"])) $this->xmlResponse->createTag("State", array(), $this->_maxlen($orders["billing_region"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["billing_country"])) $this->xmlResponse->createTag("Country", array(), $this->_maxlen($orders["billing_country"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["billing_postcode"])) $this->xmlResponse->createTag("Zip", array(), $this->_maxlen($orders["billing_postcode"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
            	if (!empty($orders["billing_telephone"])) $this->xmlResponse->createTag("Phone", array(), $this->_maxlen($orders["billing_telephone"],15), $BillNode, $this->Get__ENCODE_RESPONSE());

            if (isset($orders["customer_email"])){
               $this->xmlResponse->createTag("Email", array(), $this->_maxlen($orders["customer_email"],150), $BillNode, $this->Get__ENCODE_RESPONSE());
            }

                                       /////////////////////////////////////
                                       //   CreditCard info
                                       /////////////////////////////////////




            if (in_array($payment['method'],$this->PayMethodsCC)){
               $BillCreditCardNode = $this->xmlResponse->createTag("CreditCard", array(), "", $BillNode);
               if (isset($payment['cc_type'])){
                  if ($cc_type=$this->getCcTypeName($payment['cc_type'])){
                     $this->xmlResponse->createTag("CreditCardType", array(), $cc_type, $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());
                  }
               }else
                  $this->xmlResponse->createTag("CreditCardType", array(), "", $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());

               if (isset($payment['amount_paid'])){
                  $this->xmlResponse->createTag("CreditCardCharge", array(), $payment['amount_paid'], $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());
               }else{
                  $this->xmlResponse->createTag("CreditCardCharge", array(), "", $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());
               }
               if (isset($payment['cc_exp_month'])&&isset($payment['cc_exp_year'])){
                  $this->xmlResponse->createTag("ExpirationDate", array(),sprintf('%02d',$payment['cc_exp_month']).substr($payment['cc_exp_year'],-2,2), $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());

               }else{
                  $this->xmlResponse->createTag("ExpirationDate", array(),"", $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());
               }



               $CreditCardName = (isset($payment['cc_owner'])?($payment['cc_owner']):"");
               $this->xmlResponse->createTag("CreditCardName", array(), $CreditCardName, $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());

             //  $CreditCardNumber = (isset($payment['cc_last4'])?$this->getCcNumberXXXX($payment['cc_last4']):"");
               $CreditCardNumber = (isset($payment['cc_number_enc'])? $_payment->decrypt($payment['cc_number_enc']):(isset($payment['cc_last4'])?$this->getCcNumberXXXX($payment['cc_last4']):""));
               $this->xmlResponse->createTag("CreditCardNumber", array(), $CreditCardNumber, $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());
               $AuthDetails = (isset($payment['cc_trans_id'])?"cc_trans_id=".$payment['cc_trans_id'].";":"");
               $AuthDetails .= (isset($payment['last_trans_id'])?"last_trans_id=".$payment['last_trans_id'].";":"");
               $this->xmlResponse->createTag("AuthDetails", array(), $AuthDetails, $BillCreditCardNode, $this->Get__ENCODE_RESPONSE());

            }
                                       /////////////////////////////////////
                                       //   shipping info
                                       /////////////////////////////////////

            $shipping_address1 = "";
            $shipping_address2 = "";
		   if(!array_key_exists('shipping_firstname',$orders) && !array_key_exists('shipping_lastname',$orders) )
		   {
		   	
			$shippingAddressArray = $_order->getShippingAddress();
			if(is_array($shippingAddressArray))		   	
			$shippingAddressArray = $shippingAddressArray->toArray();
			
		   	$orders["shipping_firstname"]=$shippingAddressArray["firstname"];
		   	$orders["shipping_lastname"]=$shippingAddressArray["lastname"];
		   	$orders["shipping_company"]=$shippingAddressArray["company"];
		   	$orders["shipping_street"]=$shippingAddressArray["street"];
		   	$orders["shipping_city"]=$shippingAddressArray["city"];
		   	$orders["shipping_region"]=$shippingAddressArray["region"];
		   	$orders["shipping_postcode"]=$shippingAddressArray["postcode"];
		   	$orders["shipping_country"]=$shippingAddressArray["country_id"];
		   	$orders["customer_email"]=$shippingAddressArray["customer_email"]?$shippingAddressArray["customer_email"]:$orders["customer_email"];
		   	$orders["shipping_telephone"]=$shippingAddressArray["telephone"];
		   			   	
		   	
		   }

            if (isset($orders["shipping_street"])){
	            $shipping_street = explode("\n", $orders["shipping_street"]);
	            $shipping_address1 = (empty($shipping_street[0])?"":$shipping_street[0]);
	            $shipping_address2 = (empty($shipping_street[1])?"":$shipping_street[1]);
						}
						$shipMethodDescription = "";
            if (isset($orders["shipping_description"])){
            	$shipMethodDescription = $this->_maxlen($orders["shipping_description"],45);
            	$this->xmlResponse->createTag("ShipCarrierName", array(), $this->_maxlen($orders["shipping_description"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());
            }
            if ($shipMethodDescription == "" && isset($orders["shipping_method"])){
            	$shipMethodDescription = $this->_maxlen($orders["shipping_method"],45);
            }
						$this->xmlResponse->createTag("ShipMethod", array(), $shipMethodDescription , $ShipNode, $this->Get__ENCODE_RESPONSE());


						if (isset($orders['shipping_firstname'])){
		            $this->xmlResponse->createTag("FirstName", array(), $this->_maxlen($orders["shipping_firstname"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());
						} else {
							$this->xmlResponse->createTag("FirstName", array(), "Guest", $ShipNode);
						}
						if (isset($orders['shipping_lastname'])){
		          $this->xmlResponse->createTag("LastName", array(), $this->_maxlen($orders["shipping_lastname"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());
						} else {
							$this->xmlResponse->createTag("LastName", array(), "Guest", $ShipNode);
						}


						if (!empty($orders["shipping_street"])){
	            $shipping_street = explode("\n", $orders["shipping_street"]);
	            $shipping_address1 = (empty($shipping_street[0])?"":$shipping_street[0]);
	            $shipping_address2 = (empty($shipping_street[1])?"":$shipping_street[1]);
	            $this->xmlResponse->createTag("Address1", array(), $this->_maxlen($shipping_address1,75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            $this->xmlResponse->createTag("Address2", array(), $this->_maxlen($shipping_address2,75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	          }
	          
	            if (!empty($orders["shipping_company"]))
	               $this->xmlResponse->createTag("CompanyName", array(), $this->_maxlen($orders["shipping_company"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	
	            if (!empty($orders["shipping_city"])) $this->xmlResponse->createTag("City", array(), $this->_maxlen($orders["shipping_city"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["shipping_region"])) $this->xmlResponse->createTag("State", array(), $this->_maxlen($orders["shipping_region"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["shipping_country"])) $this->xmlResponse->createTag("Country", array(), $this->_maxlen($orders["shipping_country"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["shipping_postcode"])) $this->xmlResponse->createTag("Zip", array(), $this->_maxlen($orders["shipping_postcode"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            if (!empty($orders["shipping_telephone"])) $this->xmlResponse->createTag("Phone", array(), $this->_maxlen($orders["shipping_telephone"],15), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            if (isset($orders["customer_email"])){
	            	$this->xmlResponse->createTag("Email", array(), $this->_maxlen($orders["customer_email"],150), $ShipNode, $this->Get__ENCODE_RESPONSE());
	            }


            $item = Mage::getModel('sales/order_item')
                  ->getCollection()
                //  ->addAttributeToSelect('*')
                  ->setOrderFilter($orders["entity_id"])
               //   ->setOrder('000000001')
                  ->load()
                  ;
            $_items = $_order->getItemsCollection()->load();
            $item_array=$_items->toArray();
/*
             echo("<pre>");
             print_r($item_array);
             echo("</pre>");
/*             echo("<pre>");
             print_r($_items);
             echo("</pre>");
  */



                                       /////////////////////////////////////
                                       //   items info
                                      /////////////////////////////////////

						$cofigProdUnitPrice = 0.0;
						$cofigProdUnitTotal = 0.0;

            if (is_array($item_array) and (count($item_array)>=1))
			{
               // $item_array =  $item_array["items"];
               $skipProduct=array();
			   
               foreach($_items  as $_itm) 
			   {

                  $itm =$_itm->toArray();
                  
/*********************************************************************************************************
  echo("<pre>");
  var_dump($itm);
*********************************************************************************************************/                  


					 if(isset($itm['parent_item_id'])&&$itm['parent_item_id']!==NULL){
						if( in_array($itm['parent_item_id'],$skipProduct)){
							continue;
						 }
					  }
                    $attributes_info = array();
                 
                    $ItemProductType = "";
					if(isset($itm['product_type'])&&$itm['product_type']!==NULL){
					$ItemProductType = $itm['product_type'];
					} else {
					$ItemProductType = "Simple Product";
					}
					$ItemParentId = "";
					if(isset($itm['parent_item_id'])&&$itm['parent_item_id']!==NULL){
						$ItemParentId = $itm['parent_item_id'];
					}
					
					// Start - To Get Gift message for Item order <Ganesh> 09/11/2010 
                      $ItemGiftMessage = "";
				      if (isset($itm["gift_message_id"])&&$itm["gift_message_id"]!=null&&$itm["gift_message_id"]!=0)
					  {
				      $_giftMessage_item = Mage::helper('giftmessage/message')->getGiftMessage($itm["gift_message_id"]);
				      $ItemGiftMessage = "\r\nGift Message: ".$_giftMessage_item->getMessage();
					  }else{
					  $ItemGiftMessage =" ";
					  }
    				// End - To Get Gift message for Item order <Ganesh> 09/11/2010
					
					
					if($ItemProductType == 'configurable')
					{
						$cofigProdUnitPrice = $itm["base_price"];
						$cofigProdUnitTotal = $itm["base_row_total"];
						//continue;
					}
                     
					  
					  if(isset($itm['has_children'])&&$itm['has_children']==true){
						 if(isset($itm['product_type'])&&$itm['product_type']=='configurable'){
								
							$skipProduct[]=$itm['item_id'];
							if(isset($itm['product_options'])&&$itm['product_options']!==''){
								$options = unserialize($itm['product_options']);
							   if(isset($options['attributes_info'])&&$options['attributes_info']!==''){
								  $attributes_info = $options['attributes_info'];
							   }
														//If the item is configurable product - then the SKU returned should be that of the Single item that was selected.
							   if(isset($options['simple_sku'])){
								$itm['sku']=$options['simple_sku'];
							   }
							}
						 }

						 // ***  UNCOMMENT THE FOLLOWING SECTION IF BUNDLED PRODUCT SHOULD BE POSTED TO qb AS ZERO QUANTITY AND AMOUNT
						 if(isset($itm['product_type'])&&$itm['product_type']=='bundle'){
						    //$itm["qty_ordered"]=0;
						    //$itm["base_row_total"]=0;
						    //$itm["base_price"]=0;
						    $itm["weight"] = 0;
						 }
					  }
  
						 	if ($ItemParentId == ''){
						 	} else {
						    $itm["base_row_total"]=0;
						    $itm["base_price"]=0;
							}	
  			  
$itemNode = $this->xmlResponse->createTag("Item",    array(), '',    $itemsNode);
$this->xmlResponse->createTag("ItemType",       array(), $ItemProductType, $itemNode);
// $this->xmlResponse->createTag("cofigProdUnitPrice",       array(), $cofigProdUnitPrice, $itemNode);

$itemUnitPrice = $itm["base_price"];
$itemUnitTotal = $itm["base_row_total"];
if ($itemUnitPrice == 0 && $cofigProdUnitPrice > 0) {
	$itemUnitPrice = $cofigProdUnitPrice ;
}
if ($itemUnitTotal == 0 && $cofigProdUnitTotal > 0) {
	$itemUnitTotal = $cofigProdUnitTotal ;
}
       
				   $ItemDescription = "";
				   $this->xmlResponse->createTag("ItemCode",       array(), $itm["sku"], $itemNode,$this->Get__ENCODE_RESPONSE());

				   $this->xmlResponse->createTag("ItemCodeParent",       array(), $ItemParentId, $itemNode,$this->Get__ENCODE_RESPONSE());


				   $ItemDescription = $this->_maxlen($itm["name"],75).(isset($itm["description"])?(" ".$itm["description"]):"");
                   // Start - To Get Gift message for Item order <Ganesh> 09/11/2010                   
				   $ItemDescription .= $ItemGiftMessage;
				   // End - To Get Gift message for Item order <Ganesh> 09/11/2010 
				   $this->xmlResponse->createTag("ItemDescription",array(), $ItemDescription,      $itemNode, $this->Get__ENCODE_RESPONSE()); 
                   $this->xmlResponse->createTag("Quantity",       array(), $itm["qty_ordered"],  $itemNode);
                   $this->xmlResponse->createTag("UnitPrice",      array(), $itemUnitPrice,        $itemNode);
                   $this->xmlResponse->createTag("ItemTotal",      array(), $itemUnitTotal,  $itemNode);
                   $this->xmlResponse->createTag("ItemUnitWeight",      array(), $itm["weight"],  $itemNode);

                   //$cofigProdUnitPrice = 0.0;
                   for ($i=1; $i<6; $i++) {
                      $this->xmlResponse->createTag("CustomField".(string)$i,      array(), '',  $itemNode);
                   }


// --------- MB 30/05/2009 start --------
                   $addOption = array();
                   if ($options =  $_itm->getProductOptions()) {
                      if (isset($options['options'])) {
                          $addOption = array_merge($addOption, $options['options']);
                      }
                      if (isset($options['additional_options'])) {
                          $addOption = array_merge($addOption, $options['additional_options']);
                      }
                      if (!empty($options['attributes_info'])) {
                          $addOption= array_merge($addOption, $options['attributes_info']);
                      }
                   }

                   if (count($addOption)>0){
                      $ItemOptions  = $this->xmlResponse->createTag("ItemOptions",  array(), '', $itemNode);
                      if (count($addOption)>0){
                        foreach($addOption as $optionItem){
                           $this->xmlResponse->createTag("ItemOption", array("Name"=>$optionItem['label'],"Value"=>$optionItem['value']), '',  $ItemOptions,$this->Get__ENCODE_RESPONSE());
                        }
                      }
                   }elseif (count($attributes_info)>0){
                      $ItemOptions  = $this->xmlResponse->createTag("ItemOptions",  array(), '', $itemNode);
                      foreach($attributes_info as $attribute){
                         $this->xmlResponse->createTag("ItemOption",  array("Name"=>$attribute['label'],"Value"=>$attribute['value']), '',  $ItemOptions,$this->Get__ENCODE_RESPONSE());
                      }

                   }
// --------- MB 30/05/2009 end ----------

               }
            }


                               /////////////////////////////////////
                               //   Charges info
                               /////////////////////////////////////

             $this->xmlResponse->createTag("Shipping",       array(), $orders["base_shipping_amount"], $chargesNode,$this->Get__ENCODE_RESPONSE());
             $this->xmlResponse->createTag("Handling",       array(), '0.0000', $chargesNode,$this->Get__ENCODE_RESPONSE());
             $this->xmlResponse->createTag("Tax",       array(), $orders["base_tax_amount"], $chargesNode,$this->Get__ENCODE_RESPONSE());
             if (isset($orders["tax_refunded"])&&$orders["tax_refunded"]!=null&&$orders["tax_refunded"]!=0){
                $TaxOther = $orders["tax_refunded"];
             }

             if (isset($orders["tax_canceled"])&&$orders["tax_canceled"]!=null&&$orders["tax_canceled"]!=0){
                if (isset($TaxOther))
                   $TaxOther += $orders["tax_canceled"];
                else
                   $TaxOther = $orders["tax_canceled"];
             }
             if (isset($TaxOther))
                $this->xmlResponse->createTag("TaxOther",       array(), $TaxOther, $chargesNode);
//             $this->xmlResponse->createTag("Fee",       array(), '', $chargesNode);

//             $FeeDetailsNode=$this->xmlResponse->createTag("FeeDetails",       array(), '', $chargesNode);
//             $this->xmlResponse->createTag("FeeDetail",       array(), '', $FeeDetailsNode);
//             $this->xmlResponse->createTag("FeeName",       array(), '', $FeeDetailsNode);
//             $this->xmlResponse->createTag("FeeValue",       array(), '', $FeeDetailsNode);

            
 
// Start - To create GiftCertificate Tag  <Ganesh> 09/10/2010
 
              // Previous code for create Discount TAG and GiftCertificate TAG
			  
              /* $this->xmlResponse->createTag("Discount",       array(),  $orders["base_discount_amount"], $chargesNode);
             	if (isset($orders["base_gift_cards_amount"])&&$orders["base_gift_cards_amount"]!=null&&$orders["base_gift_cards_amount"]!=0){
             		$this->xmlResponse->createTag("GiftCertificate",       array(), $orders["base_gift_cards_amount"], $chargesNode);
            	}
              */			
			/*
			 
			  echo ("<pre>");
			  var_dump ($orders["base_giftcert_amount_invoiced"]);
			  
			  echo ("<pre>");
			  var_dump ($orders["giftcert_amount_invoiced"]);
			  
			  echo ("<pre>");
			  var_dump ($orders["giftcert_amount"]);
			*/
			  
    		$this->xmlResponse->createTag("Discount",       array(),  $orders["base_discount_amount"], $chargesNode);
			if (isset($orders["giftcert_amount"])&&$orders["giftcert_amount"]!=null&&$orders["giftcert_amount"]!=0){
			$this->xmlResponse->createTag("GiftCertificate",       array(), $orders["giftcert_amount"], $chargesNode);
            }else if(isset($orders["giftcert_amount_invoiced"])&&$orders["giftcert_amount_invoiced"]!=null&&$orders["giftcert_amount_invoiced"]!=0){
			$this->xmlResponse->createTag("GiftCertificate",       array(), $orders["giftcert_amount_invoiced"], $chargesNode);
            }else if(isset($orders["base_giftcert_amount_invoiced"])&&$orders["base_giftcert_amount_invoiced"]!=null&&$orders["base_giftcert_amount_invoiced"]!=0){
			$this->xmlResponse->createTag("GiftCertificate",       array(), $orders["giftcert_amount_invoiced"], $chargesNode);
            }				
			
          // End - To create GiftCertificate Tag  <Ganesh> 09/10/2010     
            $this->xmlResponse->createTag("Total",       array(),  $orders["base_grand_total"], $chargesNode);
						
// ************  COUPON LOGIC REMOVED AND NOT USED ******************************             
//             if (isset($orders["coupon_code"])){
//                 if ($orders["coupon_code"]!=""){
//                 $discount = Mage::getModel('salesrule/rule')->getResourceCollection()
//                        ->addFieldToFilter('coupon_code', $orders["coupon_code"])
//                        ->load();
//                    $discount_=$discount->toArray();
//                    if ($discount_['totalRecords']!==0){
//
//
//                        if (isset($discount_['items'][0])){
//                           $discount_=$discount_['items'][0];
//
//                           $CouponsNode=$this->xmlResponse->createTag("Coupons",       array(), '', $chargesNode);
//                           $this->xmlResponse->createTag("Coupon",       array(), $discount_['name'], $CouponsNode,$this->Get__ENCODE_RESPONSE());
//                           $this->xmlResponse->createTag("CouponCode",       array(), $orders["coupon_code"], $CouponsNode,$this->Get__ENCODE_RESPONSE());
//                           $this->xmlResponse->createTag("CouponID",       array(), $discount_['rule_id'], $CouponsNode,$this->Get__ENCODE_RESPONSE());
//                           $this->xmlResponse->createTag("CouponDescription",       array(), $discount_['description'], $CouponsNode,$this->Get__ENCODE_RESPONSE());
//                           $this->xmlResponse->createTag("CouponAction",       array(), $discount_['simple_action'], $CouponsNode,$this->Get__ENCODE_RESPONSE());
//                           $this->xmlResponse->createTag("CouponValue",       array(), $discount_['discount_amount'], $CouponsNode,$this->Get__ENCODE_RESPONSE());
//                        }
//                    }
//                 }
//             }
// ************  COUPON LOGIC REMOVED AND NOT USED ******************************
  
  
  
  
  
             
             for ($i=1; $i<6; $i++) {
                $this->xmlResponse->createTag("CustomField".(string)$i,      array(), '',  $chargesNode);
             }

         }
         header("Content-type: application/xml");
         print($this->xmlResponse->generate());

      }

   public  function CheckXmlRequst(){
       $this->xmlRequest->getTag(0, $this->_tagName, $this->_tagAttributes, $this->_tagContents, $this->_tagTags);
       if (strtoupper(trim($this->_tagName)) != 'REQUEST') {
           print($this->xmlErrorResponse('unknown', '9999',
                   'Unknown request', $this->STORE_NAME, ''));
           exit;
       }
       if (count($this->_tagTags) == 0) {
           print($this->xmlErrorResponse('unknown', '9999',
                   'REQUEST tag doesnt have necessry parameters', $this->STORE_NAME, ''));
           exit;
       }
       $this->RequestParams = Array();
       foreach ($this->_tagTags as $k=>$v){
           $this->xmlRequest->getTag($v, $tN, $tA, $tC, $tT);
           $this->RequestParams[strtoupper($tN)] = trim($tC);
       }

       if (!isset($this->RequestParams['COMMAND'])) {
           print($this->xmlErrorResponse('unknown', '9999',
                   'Command is not set', $this->STORE_NAME, ''));
           exit;
       }
       $this->RequestParams['COMMAND'] = strtoupper($this->RequestParams['COMMAND']);

  // print($this->RequestParams['COMMAND']);
       if(  ($this->RequestParams['COMMAND'] != ('GET'.'ORDERS'))
            && ($this->RequestParams['COMMAND'] != ('UPDATE'.'ORDERS'))
            && ($this->RequestParams['COMMAND'] != ('UPDATE'.'INVENTORY'))
            && ($this->RequestParams['COMMAND'] != ('UPDATE'.'ORDERS'.'SHIPPING'.'STATUS'))
            && ($this->RequestParams['COMMAND'] != ('UPDATE'.'ORDERS'.'PAYMENT'.'STATUS'))){
          print($this->xmlErrorResponse('unknown', '9999',
                  'Unknown Command '.$this->RequestParams['COMMAND'], $this->STORE_NAME, ''));
          exit;
       }
       return true;
   }


    protected function _getDataModel()
    {
        if (is_null($this->_dataModel)) {
            $this->_dataModel = Mage::getModel('thub/run_data');
        }
        return $this->_dataModel;
    }


    /**
     * Init installation
     *
     * @param Mage_Core_Model_App $app
     * @return boolean
     */
    public function init(Mage_Core_Model_App $app)
    {
       // $this->_app = $app;
       // $tmp=$this->_getDataModel();
     //   var_dump($tmp);

       // $this->_getThub()->setDataModel($this->_getDataModel());

        /**
         * Check if already installed
         */
//        if ($this->_app->isInstalled()) {
          //  $this->addError('ERROR: Magento is already installed');
  //          return false;
    //    }

        return true;
    }



      public  function xmlErrorResponse($command, $code, $message, $provider="", $request_id='') {
         $xmlError = Mage::getModel('thub/run_error');
         return $xmlError->xmlErrorResponse($command, $code, $message, $provider, $request_id);
      }

    protected function _maxlen($str,$len){

        if (strlen($str)>$len)
           return substr($str,0,$len);
        else
           return $str;
    }

    public function getCcTypeName($ccType)
    {
        return isset($this->types[$ccType]) ? $this->types[$ccType] : false;
    }

    public function getPayMethodName($method)
    {
        return isset($this->PayMethods[$method]) ? $this->PayMethods[$method] : "unknown method:".$method;
    }

    public function getCcNumberXXXX($ccNumber){
        return "XXXX-XXXX-XXXX-".$ccNumber;
    }

    public function getShippingCode($shipp){
       $shipp = strtoupper($shipp);
       if (array_key_exists($shipp, $this->carriers_)){
          return $this->carriers_[$shipp];
       }
       return false;
    }
 }
