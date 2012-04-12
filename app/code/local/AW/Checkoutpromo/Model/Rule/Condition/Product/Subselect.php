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
class AW_Checkoutpromo_Model_Rule_Condition_Product_Subselect
    extends AW_Checkoutpromo_Model_Rule_Condition_Product_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('checkoutpromo/rule_condition_product_subselect')
            ->setValue(null);
    }

    public function loadArray($arr, $key='conditions')
    {
        $this->setAttribute($arr['attribute']);
        $this->setOperator($arr['operator']);
        parent::loadArray($arr, $key);
        return $this;
    }

    public function asXml($containerKey='conditions', $itemKey='condition')
    {
        $xml .= '<attribute>'.$this->getAttribute().'</attribute>'
            .'<operator>'.$this->getOperator().'</operator>'
            .parent::asXml($containerKey, $itemKey);
        return $xml;
    }

    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'qty'  => Mage::helper('checkoutpromo')->__('total quantity'),
            'row_total'  => Mage::helper('checkoutpromo')->__('total amount'),
        ));
        return $this;
    }

    public function loadOperatorOptions()
    {
        $helper = Mage::helper('checkoutpromo');
        $this->setOperatorOption(array(
            '=='  => $helper->__('is'),
            '!='  => $helper->__('is not'),
            '>='  => $helper->__('equals or greater than'),
            '<='  => $helper->__('equals or less than'),
            '>'   => $helper->__('greater than'),
            '<'   => $helper->__('less than'),
            '()'  => $helper->__('is one of'),
            '!()' => $helper->__('is not one of'),
        ));
        return $this;
    }

    public function getValueElementType()
    {
        return 'text';
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml().
              Mage::helper('checkoutpromo')->__("If %s %s %s for a subselection of items in cart matching %s of these conditions:",
              $this->getAttributeElement()->getHtml(),
              $this->getOperatorElement()->getHtml(),
              $this->getValueElement()->getHtml(),
              $this->getAggregatorElement()->getHtml()
           );
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
        if (!$this->getConditions()) {
            return false;
        }
        $attr = $this->getAttribute();
        $total = 0;
        foreach ($object->getAllItems() as $item) {
            if (parent::validate($item)) {
                $total += $item->getData($attr);
            }
        }
        return $this->validateAttribute($total);
    }
}
