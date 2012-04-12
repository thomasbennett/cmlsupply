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
class AW_Checkoutpromo_Block_Adminhtml_Checkoutpromo_Edit_Tab_Processing extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $model = Mage::registry('checkoutpromo_rule');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('ruleismet_fieldset', array('legend' => $this->__('When the rule is met')));

//  CMS block select's
        $options=array();
        $arr=Mage::getModel('cms/block')->getCollection()->toOptionArray();
        foreach($arr as $n => $v) $options[$v['value']] = $this->__($v['label']);

        $fieldset->addField('cms_block_id', 'select', array(
            'label'     => $this->__('Show CMS block'),
            'title'     => $this->__('Show CMS block'),
            'name'      => 'cms_block_id',
            'options'   => $options,
        ));
// end of CMS block select's

        $fieldset->addField('stop_rules_processing', 'select', array(
            'label'     => $this->__('Stop further rules processing'),
            'title'     => $this->__('Stop further rules processing'),
            'name'      => 'stop_rules_processing',
            'options'    => array(
                '1' => $this->__('Yes'),
                '0' => $this->__('No'),
            ),
        ));

        $fieldset = $form->addFieldset('wheretoshow_fieldset', array('legend'=>$this->__('Show promo block on pages')));

        $fieldset->addField('show_on_shopping_cart', 'select', array(
            'label'     => $this->__('Shopping cart'),
            'title'     => $this->__('Shopping cart'),
            'name'      => 'show_on_shopping_cart',
            'options'    => array(
                '1' => $this->__('Yes'),
                '0' => $this->__('No'),
            ),
        ));

        $fieldset->addField('show_on_checkout', 'select', array(
            'label'     => $this->__('Checkout progress'),
            'title'     => $this->__('Checkout progress'),
            'name'      => 'show_on_checkout',
            'options'    => array(
                '1' => $this->__('Yes'),
                '0' => $this->__('No'),
            ),
        ));

        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }
}