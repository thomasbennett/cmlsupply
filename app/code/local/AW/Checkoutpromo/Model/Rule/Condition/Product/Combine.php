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
class AW_Checkoutpromo_Model_Rule_Condition_Product_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('checkoutpromo/rule_condition_product_combine');
    }

    public function getNewChildSelectOptions()
    {
        $productCondition = Mage::getModel('checkoutpromo/rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $pAttributes = array();
        $iAttributes = array();
        foreach ($productAttributes as $code => $label) {
            if (strpos($code, 'quote_item_')===0) {
                $iAttributes[] = array('value' => 'checkoutpromo/rule_condition_product|'.$code, 'label' => $label);
            } else {
                $pAttributes[] = array('value' => 'checkoutpromo/rule_condition_product|'.$code, 'label' => $label);
            }
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, array(
            array('value' => 'checkoutpromo/rule_condition_product_combine', 'label' => Mage::helper('catalog')->__('Conditions Combination')),
            array('label' => Mage::helper('checkoutpromo')->__('Cart Item Attribute'), 'value' => $iAttributes),
            array('label' => Mage::helper('checkoutpromo')->__('Product Attribute'), 'value' => $pAttributes),
        ));
        return $conditions;
    }

    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }
}
