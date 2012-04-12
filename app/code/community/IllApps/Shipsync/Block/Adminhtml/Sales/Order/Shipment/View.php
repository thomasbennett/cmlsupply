<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com) / Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Shipment view
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Sales_Order_Shipment_View
{

    /**
     * Construct
     */
    public function __construct()
    {
	/** Call parent */
    	parent::__construct();

        $i = 1;

        $packages = Mage::getModel('shipping/shipment_package')->getCollection();
        
	/** Loop through available packages */
        foreach ($packages->getData() as $package)
        {
	    if ($package['package_id'] == $this->getShipment()->getId())
	    {
		/** Get label URL */
		$url = $this->getUrl('shipsync/index/label/', array('id' => $package['package_id']));

		/** If COD label exists, show button to print */
		if ($package['cod_label_image'] != "")
		{		    
		    $codurl = $this->getUrl('shipsync/index/codlabel/', array('id' => $package['package_id']));		    

		    $this->_addButton('reprint_cod_label_' . $package, array(
			'label'     => Mage::helper('sales')->__('Print COD Label'),
			'onclick'   => 'setLocation(\'' . $codurl . '\')',
		    ));
		}

		/** Add print label button */
                $this->_addButton('reprint_label_' . $package, array(
                    'label'     => Mage::helper('sales')->__('Print Label'),
                    'onclick'   => 'setLocation(\'' . $url . '\')',
                ));
	    }
	}
    }
}
