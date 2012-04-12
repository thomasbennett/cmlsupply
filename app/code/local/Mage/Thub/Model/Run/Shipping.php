<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#


class Mage_Thub_Model_Run_Shipping extends Mage_Thub_Model_Run_Abstract
  {

    /**
     * Available options
     *
     * @var array
     */

    protected $__ENCODE_RESPONSE = false;
    protected $_options;
    protected $STORE_NAME= 'NOT_FOUND_STORE_NAME';
    protected $STORE_ID =null;
    protected $trackdata = array();
    protected $RequestOrders = array();
    /**
     * Script arguments
     *
     * @var array
     */
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
    	return $this->__ENCODE_RESPONSE;
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
            default:
                print($this->xmlErrorResponse('unknown', '9999', 'Unknown Command '.$this->RequestParams['COMMAND'], $this->STORE_NAME, ''));exit;
         }

      	return true;
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


         return true;
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
         $this->filters['PROVIDER']=" and o.vendor_id=v.vendor_id AND v.vendor_name='".$this->RequestParams['SECURITYKEY']."' ";
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
      return true;
   }


      public  function  CreateHeaderXml(){
         $this->xmlResponse = Mage::getModel('thub/run_thubxml');
         $this->xmlResponse->version='1.0';
         $this->xmlResponse->encoding='ISO-8859-1';

      	$this->root = $this->xmlResponse->createTag("RESPONSE", array('Version'=>'4.1'));
      	$this->envelope = $this->xmlResponse->createTag("Envelope", array(), '', $this->root);
      	$this->xmlResponse->createTag("Command", array(), $this->RequestParams['COMMAND'], $this->envelope);
      }


      public  function  SetDefaultStoreName(){
         $storeCollection = Mage::getModel('core/store_group')
            ->getCollection()
            ->addFieldToFilter('default_store_id', 1)
            ->load();

          foreach ($storeCollection->toArray() as $store){

             if (isset($store[0]['name'])){
                $this->STORE_NAME=$store[0]['name'];
             }
             if (isset($store[0]['group_id'])){
                $this->STORE_ID=$store[0]['group_id'];
             }

          }
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
              foreach($_orderTags as $k1=>$v1){
                $this->xmlRequest->getTag($v1, $_tagName, $_tagAttributes, $_tagContents, $_tempTags);
                $TAGNAME=strtoupper($_tagName);
                $this->RequestOrders[$TAGNAME] = $_tagContents;
                $issetTag[$TAGNAME]=true;

                switch ($TAGNAME){
                   case 'HOSTORDERID':
                      $issetTag[$TAGNAME]=false;
                   break;
                   case 'LOCALORDERID':
                      $issetTag[$TAGNAME]=false;
                   break;
                   case 'HOSTSTATUS':
                      $issetTag[$TAGNAME]=false;
                   break;
                }
/*                if (strtoupper($_tagName)=='HOSTORDERID')
                  $this->xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
                if (strtoupper($_tagName)=='LOCALORDERID')
                  $this->xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
*/
              }
//              if ($issetTag['HOSTORDERID']||$issetTag['LOCALORDERID']||$issetTag['SHIPPEDON']||$issetTag['SHIPPEDVIA']||$issetTag['TRACKINGNUMBER']){
               if ($issetTag['HOSTORDERID']){
                  print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
                         'Error XML request! Not found required tags in tag HostOrderID', $this->STORE_NAME, ''));
                  exit;
               }

               $this->xmlResponse->createTag('HostOrderID',  array(),$this->RequestOrders['HOSTORDERID'], $orderNode);
               $this->xmlResponse->createTag('LocalOrderID',  array(), $this->RequestOrders['LOCALORDERID'], $orderNode);


               $orders = Mage::getResourceModel('sales/order_collection')
                  ->addAttributeToSelect('*')
                  ->addFieldToFilter('increment_id', $this->RequestOrders['HOSTORDERID'])
                  ->addAttributeToFilter('store_id', $this->GetStoreId())
                  ->load();
               $orders_array=$orders->toArray();

             //  echo("<pre>"); var_dump($orders_array); echo("</pre>");


               if (count($orders_array)==0){
                  $this->Msg[] = 'Order not found';
                  $this->result = 'Failed';
               }else{
                  foreach($orders_array as $orders_el){
                  	$this->_current_order = Mage::getModel('sales/order')
                           ->load($orders_el['entity_id']);
                     if ($this->SetPaymentStatus()){
                           $this->result = 'Succes';
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
      }
    //***************************************************
    //
    //      Update Orders Shipping Status Service
    //
    //***************************************************

      public  function  UpdateOrdersShippingStatus(){

         try{


            $ordersTag = $this->xmlRequest->getChildByName(0, "ORDERS");
            if ((count($ordersTag) <1)||$ordersTag==null){                 print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
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
              foreach($_orderTags as $k1=>$v1){
                $this->xmlRequest->getTag($v1, $_tagName, $_tagAttributes, $_tagContents, $_tempTags);
                $TAGNAME=strtoupper($_tagName);
                $this->RequestOrders[$TAGNAME] = $_tagContents;
                $issetTag[$TAGNAME]=true;

                switch ($TAGNAME){                   case 'HOSTORDERID':
                      $issetTag[$TAGNAME]=false;
                   break;                   case 'LOCALORDERID':
                      $issetTag[$TAGNAME]=false;
                   break;
                   case 'SHIPPEDON':
                      $issetTag[$TAGNAME]=false;
                   break;
                   case 'SHIPPEDVIA':
                      $issetTag[$TAGNAME]=false;
                   break;
                   case 'TRACKINGNUMBER':
                      $issetTag[$TAGNAME]=false;
                   break;
                }
/*                if (strtoupper($_tagName)=='HOSTORDERID')
                  $this->xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
                if (strtoupper($_tagName)=='LOCALORDERID')
                  $this->xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
*/
              }
//              if ($issetTag['HOSTORDERID']||$issetTag['LOCALORDERID']||$issetTag['SHIPPEDON']||$issetTag['SHIPPEDVIA']||$issetTag['TRACKINGNUMBER']){               if ($issetTag['SHIPPEDVIA']){
                  print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '9999',
                         'Error XML request! Not found required tags in tag ORDER', $this->STORE_NAME, ''));
                  exit;               }
               if (isset($this->RequestOrders['NOTIFYCUSTOMER'])){
                  $this->send_email=(strtoupper($this->RequestOrders['NOTIFYCUSTOMER'])=="YES");
               }


               $this->xmlResponse->createTag('HostOrderID',  array(),$this->RequestOrders['HOSTORDERID'], $orderNode);
               $this->xmlResponse->createTag('LocalOrderID',  array(), $this->RequestOrders['LOCALORDERID'], $orderNode);


               $orders = Mage::getResourceModel('sales/order_collection')
                  ->addAttributeToSelect('*')
                  ->addFieldToFilter('increment_id', $this->RequestOrders['HOSTORDERID'])
                  ->addAttributeToFilter('store_id', $this->GetStoreId())
                  ->load();
               $orders_array=$orders->toArray();

             //  echo("<pre>"); var_dump($orders_array); echo("</pre>");


               if (count($orders_array)==0){
                  $this->Msg[] = 'Order not found';
                  $this->result = 'Failed';
               }else{
                  foreach($orders_array as $orders_el){                  	$this->_current_order = Mage::getModel('sales/order')
                           ->load($orders_el['entity_id']);
                     if ($this->AddShipment()){                           $this->result = 'Succes';
                     }elseif ($this->ChangeShipment()){
                           $this->result = 'Succes';
                     }else{                           $this->result = 'Failed';
                     }

                     break;
                  }
               }





//               $orders_array=$orders->toArray();
             //  echo("<pre>"); var_dump($orders_array); echo("</pre>");




/*$_tracks = Mage::getResourceModel('sales/order_shipment_track_collection')
                ->addAttributeToSelect('*')
                ->setOrderFilter(1)
   //             ->addAttributeToFilter('entity_id',  '16')
                ->load();
               $shipments_array=$_tracks->toArray();
         //   echo("<pre>"); var_dump($shipments_array); echo("</pre>");




  $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                ->addAttributeToSelect('*')
                ->setOrderFilter(1)
                ->addAttributeToFilter('increment_id',  '100000001')
                ->load();
               $shipments_array=$shipments->toArray();
//             echo("<pre>"); var_dump($shipments_array); echo("</pre>");

//               Mage_Sales_Model_Entity_Order_Shipment
*/


               //$this->Msg='';
  //              var_dump($orders_array[1]);
               $this->xmlResponse->createTag('HostStatus',  array(), $this->result, $orderNode);
               if (count($this->Msg)>0){               	$ind=1;               	foreach($this->Msg as $Msg){                     $this->xmlResponse->createTag('StatusMessage'.$ind++,  array(), $Msg, $orderNode);
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
         try {         	$shipment = false;/*            $orders = Mage::getModel('sales/order')
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
            $shipment    = $convertor->toShipment($this->_current_order);

//            $savedQtys = $this->_getItemQtys();
            $savedQtys = array();
            // 寡ᣫ殨㡪 櫲񞟓hipment 塭 Item  饠Order
            // Item - 衯鲨 믲ﱻ㡢�柡 Ӯ沲�衯鲨.

            //$this->_getItemQtys();
            foreach ($this->_current_order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip()) {
                    continue;
                }
                $item = $convertor->itemToShipmentItem($orderItem);
                if (isset($savedQtys[$orderItem->getId()])) {
                    $qty = $savedQtys[$orderItem->getId()];
                }
                else {
                    $qty = $orderItem->getQtyToShip();
                }
                $item->setQty($qty);
            	$shipment->addItem($item);
            }

//            if ($tracks = $this->getRequest()->getPost('tracking')) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($this->RequestOrders['TRACKINGNUMBER'])
                    ->setCarrierCode("custom")
                    ->setTitle($this->RequestOrders['SHIPPEDVIA']);
                $shipment->addTrack($track);
//            }*/

         return $shipment;
         }catch (Exception $e) {
        	      $this->Msg[] = "Critical Error _initShipment (Exception e)" ;
        }    }

    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    public function ChangeShipment(){        try {

            if (!$this->_current_order->getId()) {
                  $this->Msg[] = 'Error. Order not longer exist.';
                  $this->result = 'Failed';
                  return false;
            }
            if (!$this->_current_order->canUnhold()) {

            //  Ship �沲�裡!
            // Added Track and Content


                  $Shipments=$this->_current_order->getShipmentsCollection();
                  $shipments_array=$Shipments->toarray();
/*                  echo("-------------");
                   echo("<pre>"); var_dump($shipments_array); echo("</pre>");
*/                 foreach ($Shipments as $Shipment){
                        $track = Mage::getModel('sales/order_shipment_track')
                           ->setNumber($this->RequestOrders['TRACKINGNUMBER'])
                           ->setCarrierCode("custom")
                           ->setTitle($this->RequestOrders['SHIPPEDVIA']);
                           $Shipment->addTrack($track)->Save();
                        	 break;
                  }
                $this->Msg[] = 'Add Track Information.';

                $comment = "\nOrder shipped on ".$this->RequestOrders['SHIPPEDON'].
                               " via ".$this->RequestOrders['SHIPPEDVIA'].
                               " track number ".$this->RequestOrders['TRACKINGNUMBER'].
                   (isset($this->RequestOrders['SERVICEUSED'])?
                         " using ".$this->RequestOrders['SERVICEUSED']." service.\n" : "."
                   );

                $Shipment->addComment($comment,true );

                if ($this->send_email) {
                    $Shipment->setEmailSent(true);
                }
                         // $this->RequestOrders['send_email']
                $Shipment->Save();
                $this->Msg[] = 'Add Content Information.';
                $Shipment->sendUpdateEmail($this->send_email, $comment);
                if ((strtoupper($this->RequestOrders['NOTIFYCUSTOMER'])=="YES")){
                   $this->Msg[] = 'Send Mail.';
                }
                return true;
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
                               " track number ".$this->RequestOrders['TRACKINGNUMBER'].
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
        }catch (Mage_Core_Exception $e) {        	      $this->Msg[] = "Critical Error AddShipment (Mage_Core_Exception e)";
      //      $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
        	      $this->Msg[] = "Critical Error AddShipment (Exception e)" ;
         //   $this->_getSession()->addError($this->__('Can not save shipment.'));
        }
//        $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));

    }


     public function SetPaymentStatus (){         try {

         }catch (Exception $e) {
        	      $this->Msg[] = "Critical Error SetPaymentStatus (Exception e)" ;
         }     }


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


         $orders = Mage::getResourceModel('sales/order_collection')
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
            ->addFieldToFilter('increment_id', array($this->filters['QB_ORDER_START_NUMBER']))
            ->addAttributeToFilter('store_id', $this->GetStoreId())
            ->addAttributeToSort('increment_id', 'asc')
            ->setPageSize($this->filters['QB_ORDERS_PER_RESPONSE'])
            ->load();
         $orders_array=$orders->toArray();



         if (sizeof($orders_array)<1){ // 衯鲥狊           print($this->xmlErrorResponse($this->RequestParams['COMMAND'], '1000',
                   'No Orders returned', $this->STORE_NAME, ''));
           exit;
         }



//       var_dump($orders_array);



            //     var_dump($this->STORE_NAME);

        $this->OrderToXml( $orders_array) ;


        } catch (Exception $e) {
             Mage::printException($e);
        }
      }

      protected  function  OrderToXml($order_array = array() ){


         $this->xmlResponse->createTag("StatusCode", array(), "0", $this->envelope);
        	$this->xmlResponse->createTag("StatusMessage", array(), "All OK", $this->envelope);
        	$this->xmlResponse->createTag("Provider", array(), $this->QB_PROVIDER, $this->envelope);

         $ordersNode = $this->xmlResponse->createTag("Orders", array(), '', $this->root);
        	foreach($order_array as $_orders => $orders){
      //      var_dump($orders);

            $datetime=explode(" ",$orders["created_at"] );

            $dateCreateOrder= $datetime[0];
            $timeCreateOrder= $datetime[1];
            $orderNode = $this->xmlResponse->createTag("Order", array(), '', $ordersNode );
            $this->xmlResponse->createTag("OrderID",          array(), $orders['increment_id'], $orderNode );
            $this->xmlResponse->createTag("ProviderOrderRef", array(),$orders['increment_id'],  $orderNode);
            $this->xmlResponse->createTag("Date",             array(), $dateCreateOrder   ,$orderNode );
            $this->xmlResponse->createTag("Time",             array(), $timeCreateOrder   ,$orderNode );
            $this->xmlResponse->createTag("TimeZone",         array(), 'not found',                        $orderNode,  $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("StoreId",          array(), $orders['store_id'], $orderNode);
            $this->xmlResponse->createTag("StoreName",        array(), $this->STORE_NAME,   $orderNode,  $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("CustomerID",       array(), $orders['customer_id'], $orderNode);
            $this->xmlResponse->createTag("SalesRep",         array(), "not found",            $orderNode,  $this->Get__ENCODE_RESPONSE());

            // ALEX: ޲졿 衪ﭬ殲驠𐯲ﭳ �㼢ᬨ㡫᲼ ﹨⫠ 𐯱죠󯤮 롪 寡ᣨ쯱� �妰
            if (isset($orders['customer_note'])&&$orders['customer_note']!=''){
               $this->xmlResponse->createTag("Comment",          array(), $orders['customer_note'],    $orderNode,  $this->Get__ENCODE_RESPONSE());
            }

            $this->xmlResponse->createTag("Currency",         array(), $orders['order_currency_code'],    $orderNode, $this->Get__ENCODE_RESPONSE());

            $BillNode = $this->xmlResponse->createTag("Bill", array(), "", $orderNode);
            $ShipNode  = $this->xmlResponse->createTag("Ship",    array(), '', $orderNode);
            $itemsNode  = $this->xmlResponse->createTag("Items",  array(), '', $orderNode);
            $chargesNode  = $this->xmlResponse->createTag("Charges", array(), '', $orderNode);

                                       /////////////////////////////////////
                                       //   billing info
                                       /////////////////////////////////////

            $this->xmlResponse->createTag("PayStatus",array(), $orders['status'],   $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("PayMethod", array(),'not found',$BillNode, $this->Get__ENCODE_RESPONSE());
            //   $this->xmlResponse->createTag("PayStatus", array(), "", $BillNode);
            $this->xmlResponse->createTag("FirstName", array(), $this->_maxlen($orders["billing_firstname"],45), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("LastName", array(), $this->_maxlen($orders["billing_lastname"],45), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("MiddleName", array(),"", $BillNode, $this->Get__ENCODE_RESPONSE());
            if (!empty($orders["billing_company"]))
               $this->xmlResponse->createTag("CompanyName", array(), $this->_maxlen($orders["billing_company"],75), $BillNode, $this->Get__ENCODE_RESPONSE());

            $this->xmlResponse->createTag("Address1", array(), $this->_maxlen($orders["billing_street"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Address2", array(), $this->_maxlen("",75), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("City", array(), $this->_maxlen($orders["billing_city"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("State", array(), $this->_maxlen($orders["billing_region"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Country", array(), $this->_maxlen($orders["billing_country"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Zip", array(), $this->_maxlen($orders["billing_postcode"],75), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Email", array(), $this->_maxlen($orders["customer_email"],150), $BillNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Phone", array(), $this->_maxlen($orders["billing_telephone"],15), $BillNode, $this->Get__ENCODE_RESPONSE());
        //    $this->xmlResponse->createTag("State", array(), $this->_maxlen($orders["billing_state"],75), $BillNode, $this->Get__ENCODE_RESPONSE());

//            $this->xmlResponse->createTag("Email", array(), $orders["customer_email"], $BillNode, $this->Get__ENCODE_RESPONSE());


                                       /////////////////////////////////////
                                       //   shipping info
                                       /////////////////////////////////////


            $this->xmlResponse->createTag("ShipCarrierName", array(), $this->_maxlen($orders["shipping_description"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("ShipMethod", array(), $this->_maxlen($orders["shipping_method"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());

            $this->xmlResponse->createTag("FirstName", array(), $this->_maxlen($orders["shipping_firstname"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("LastName", array(), $this->_maxlen($orders["shipping_lastname"],45), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("MiddleName", array(),"", $ShipNode, $this->Get__ENCODE_RESPONSE());
            if (!empty($orders["shipping_company"]))
               $this->xmlResponse->createTag("CompanyName", array(), $this->_maxlen($orders["shipping_company"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());

            $this->xmlResponse->createTag("Address1", array(), $this->_maxlen($orders["shipping_street"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Address2", array(), $this->_maxlen("",75), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("City", array(), $this->_maxlen($orders["shipping_city"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("State", array(), $this->_maxlen($orders["shipping_region"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Country", array(), $this->_maxlen($orders["shipping_country"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Zip", array(), $this->_maxlen($orders["shipping_postcode"],75), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Email", array(), $this->_maxlen($orders["customer_email"],150), $ShipNode, $this->Get__ENCODE_RESPONSE());
            $this->xmlResponse->createTag("Phone", array(), $this->_maxlen($orders["shipping_telephone"],15), $ShipNode, $this->Get__ENCODE_RESPONSE());


            $item = Mage::getModel('sales/order_item')->getCollection()
                  ->addAttributeToSelect('*')
                  ->setOrderFilter($orders["entity_id"])
               //   ->setOrder('000000001')
                  ->load();

            $item_array=$item->toArray();

                                       /////////////////////////////////////
                                       //   items info
                                       /////////////////////////////////////

            foreach($item_array as $itm) {

                $itemNode = $this->xmlResponse->createTag("Item",    array(), '',    $itemsNode);

                $this->xmlResponse->createTag("ItemCode",       array(), $this->_maxlen($itm["sku"],25), $itemNode,$this->Get__ENCODE_RESPONSE());
                $this->xmlResponse->createTag("ItemDescription",array(), $this->_maxlen($itm["name"],75),      $itemNode, $this->Get__ENCODE_RESPONSE());
//                $this->xmlResponse->createTag("ItemDescription",array(), $this->_maxlen($itm["description"],75),      $itemNode, $this->Get__ENCODE_RESPONSE());
                $this->xmlResponse->createTag("Quantity",       array(), $itm["qty_ordered"],  $itemNode);
                $this->xmlResponse->createTag("UnitPrice",      array(), $itm["price"],        $itemNode);
                $this->xmlResponse->createTag("ItemTotal",      array(), $itm["row_total"],  $itemNode);
                $this->xmlResponse->createTag("ItemUnitWeight",      array(), $itm["weight"],  $itemNode);

                for ($i=1; $i<6; $i++) {
                   $this->xmlResponse->createTag("CustomField".(string)$i,      array(), '',  $itemNode);
                }
            }

                                       /////////////////////////////////////
                                       //   Charges info
                                       /////////////////////////////////////

             $this->xmlResponse->createTag("Shipping",       array(), $orders["shipping_amount"], $chargesNode,$this->Get__ENCODE_RESPONSE());
             $this->xmlResponse->createTag("Handling",       array(), '0.0000', $chargesNode,$this->Get__ENCODE_RESPONSE());
             $this->xmlResponse->createTag("Tax",       array(), $orders["tax_amount"], $chargesNode,$this->Get__ENCODE_RESPONSE());
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

             $this->xmlResponse->createTag("Discount",       array(),  $orders["discount_amount"], $chargesNode);
//             $this->xmlResponse->createTag("GiftCertificate",       array(), '', $chargesNode);
//             $this->xmlResponse->createTag("OtherCharge",       array(), '', $chargesNode);
             $this->xmlResponse->createTag("Total",       array(),  $orders["grand_total"], $chargesNode);

             if (isset($orders["coupon_code"])){



                if ($orders["coupon_code"]!=""){

            $discount = Mage::getModel('salesrule/rule')->getResourceCollection()
                  ->addFieldToFilter('coupon_code', $orders["coupon_code"])
                  ->load();

              $discount_=$discount->toArray();
                  $discount_=$discount_['items'][0];
              //   var_dump($discount_);

                $CouponsNode=$this->xmlResponse->createTag("Coupons",       array(), '', $chargesNode);
                $this->xmlResponse->createTag("Coupon",       array(), $discount_['name'], $CouponsNode);
                $this->xmlResponse->createTag("CouponCode",       array(), $orders["coupon_code"], $CouponsNode);
                $this->xmlResponse->createTag("CouponID",       array(), $discount_['rule_id'], $CouponsNode);
                $this->xmlResponse->createTag("CouponDescription",       array(), $discount_['description'], $CouponsNode);
                $this->xmlResponse->createTag("CouponAction",       array(), $discount_['simple_action'], $CouponsNode);
                $this->xmlResponse->createTag("CouponValue",       array(), $discount_['discount_amount'], $CouponsNode);
                }

             }
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




    public function xmlErrorResponse($command, $code, $message, $provider="", $request_id='') {
        header("Content-type: application/xml");
        $this->xmlResponse = Mage::getModel('thub/run_thubxml');
        $this->xmlResponse->loadString('<?xml version="1.0" encoding="UTF-8"?>');
        //$xmlResponse = new xml_doc();
        //$xmlResponse->version='';
       // $xmlResponse->encoding='UTF-8';
        $root = $this->xmlResponse->createTag("RESPONSE", array('Version'=>'1.0'));
        $envelope = $this->xmlResponse->createTag("Envelope", array(), '', $root);
        $this->xmlResponse->createTag("Command", array(), $command, $envelope);
        $this->xmlResponse->createTag("StatusCode", array(), $code, $envelope);
        $this->xmlResponse->createTag("StatusMessage", array(), $message, $envelope);
        $this->xmlResponse->createTag("Provider", array(), $provider, $envelope);
        return $this->xmlResponse->generate();
    }


    protected function _maxlen($str,$len){

        if (strlen($str)>$len)
           return substr($str,0,$len);
        else
           return $str;
    }






 }
