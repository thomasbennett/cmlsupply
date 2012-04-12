<?php

/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE-COMMUNITY.txt
 * 
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition
 * aheadWorks does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * aheadWorks does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Checkoutpromo
 * @copyright  Copyright (c) 2009-2010 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE-COMMUNITY.txt
 */
class AW_Checkoutpromo_Model_Rule_Condition_Product_Found
    extends AW_Checkoutpromo_Model_Rule_Condition_Product_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('checkoutpromo/rule_condition_product_found');
    }

    public function loadValueOptions()
    {
    	$this->setValueOption(array(
    		1 => 'FOUND',
    		0 => 'NOT FOUND',
    	));
    	return $this;
    }

    public function asHtml()
    {
    	$html = $this->getTypeElement()->getHtml().
    	    Mage::helper('checkoutpromo')->__('If an item is %s in the cart with %s of these conditions true:',
    		$this->getValueElement()->getHtml(), $this->getAggregatorElement()->getHtml());
       	if ($this->getId()!='1') {
       	    $html.= $this->getRemoveLinkHtml();
       	}
    	return $html;
    }

    /**
     * validate
     *
     * @param Varien_Object $object Quote
     * @return boolean
     */
    public function validate(Varien_Object $object)
    {
        $all = $this->getAggregator()==='all';
        $true = (bool)$this->getValue();
        $found = false;
        foreach ($object->getAllItems() as $item) {
            $found = $all ? true : false;
            foreach ($this->getConditions() as $cond) {
                $validated = $cond->validate($item);
                if ($all && !$validated) {
                    $found = false;
                    break;
                } elseif (!$all && $validated) {
                    $found = true;
                    break 2;
                }
            }
            if ($found && $true) {
                break;
            }
        }
        if ($found && $true) {
            // found an item and we're looking for existing one

            return true;
        } elseif (!$found && !$true) {
            // not found and we're making sure it doesn't exist
            return true;
        }
        return false;
    }
}
