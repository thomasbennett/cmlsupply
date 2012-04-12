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
class AW_Checkoutpromo_Block_Adminhtml_Checkoutpromo_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $model = Mage::registry('checkoutpromo_rule');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');
        $this->setForm($form);

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => $this->__('General Information')));

        if ($model->getId()) {
            $fieldset->addField('rule_id', 'hidden', array(
                'name' => 'rule_id',
            ));
        }

        $fieldset->addField('product_ids', 'hidden', array(
            'name' => 'product_ids',
        ));

        $fieldset->addField('name', 'text', array(
            'name' => 'name',
            'label' => $this->__('Rule Name'),
            'title' => $this->__('Rule Name'),
            'required' => true,
        ));

        $fieldset->addField('description', 'textarea', array(
            'name' => 'description',
            'label' => $this->__('Description'),
            'title' => $this->__('Description'),
            'style' => 'width: 98%; height: 100px;',
        ));

        $fieldset->addField('is_active', 'select', array(
            'label'     => $this->__('Status'),
            'title'     => $this->__('Status'),
            'name'      => 'is_active',
            'required' => true,
            'options'    => array(
                '1' => $this->__('Active'),
                '0' => $this->__('Inactive'),
            ),
        ));


        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('website_ids', 'multiselect', array(
                'name'      => 'website_ids[]',
                'label'     => Mage::helper('catalogrule')->__('Websites'),
                'title'     => Mage::helper('catalogrule')->__('Websites'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_config_source_website')->toOptionArray(),
            ));
        }
        else {
            $fieldset->addField('website_ids', 'hidden', array(
                'name'      => 'website_ids[]',
                'value'     => Mage::app()->getStore(true)->getWebsiteId()
            ));
            $model->setWebsiteIds(Mage::app()->getStore(true)->getWebsiteId());
        }

        $customerGroups = Mage::getResourceModel('customer/group_collection')
            ->load()->toOptionArray();

        $found = false;
        foreach($customerGroups as $group)
            if($group['value']==0)
            {
                $found = true;
                break;
            }
        if(!$found)
            array_unshift($customerGroups, array('value' => 0, 'label' => $this->__('NOT LOGGED IN')));

        $fieldset->addField('customer_group_ids', 'multiselect', array(
            'name'      => 'customer_group_ids[]',
            'label'     => $this->__('Customer Groups'),
            'title'     => $this->__('Customer Groups'),
            'required'  => true,
            'values'    => $customerGroups,
        ));

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);

        $fieldset->addField('from_date', 'date', array(
            'name'   => 'from_date',
            'label'  => $this->__('From Date'),
            'title'  => $this->__('From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
        ));

        $fieldset->addField('to_date', 'date', array(
            'name'   => 'to_date',
            'label'  => $this->__('To Date'),
            'title'  => $this->__('To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
        ));

        $fieldset->addField('sort_order', 'text', array(
            'name' => 'sort_order',
            'label' => $this->__('Rule Priority'),
        ));

        $form->setValues($model->getData());

        return parent::_prepareForm();
    }
}