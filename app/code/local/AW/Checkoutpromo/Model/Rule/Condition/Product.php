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
class AW_Checkoutpromo_Model_Rule_Condition_Product extends Mage_CatalogRule_Model_Rule_Condition_Product
{

    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $helper = Mage::helper('checkoutpromo');
        $attributes['quote_item_qty'] = $helper->__('Quantity in cart');
        $attributes['quote_item_price'] = $helper->__('Price in cart');
        $attributes['quote_item_row_total'] = $helper->__('Row total in cart');
    }

    /**
     * Validate Product Rule Condition
     *
     * @param Varien_Object $object
     * @return bool
     */
    public function validate(Varien_Object $object)
    {
        $product = Mage::getModel('catalog/product')
            ->load($object->getProductId())
            ->setQuoteItemQty($object->getQty())
            ->setQuoteItemPrice($object->getPrice())
            ->setQuoteItemRowTotal($object->getRowTotal());

        return parent::validate($product);
    }
}
