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
/**
 * Checkoutpromo Rule Validator Model
 * Allows dispatching before and after events for each controller action
 */
class AW_Checkoutpromo_Model_Validator extends Mage_Core_Model_Abstract {

    protected $_rules;
    public $appliedBlockIds = array();

    protected function _construct() {
        parent::_construct();
        $this->_init('checkoutpromo/validator');
    }

    /**
     * Init validator
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @return AW_Checkoutpromo_Model_Validator
     */
    public function init($websiteId, $customerGroupId) {
       
        $this->setWebsiteId($websiteId)
                ->setCustomerGroupId($customerGroupId);

        $this->_rules = Mage::getResourceModel('checkoutpromo/rule_collection')
                        ->setValidationFilter($websiteId, $customerGroupId)
                        ->load();      
        return $this;
    }

    public function process($_quote) {

        $quote = $_quote;
        $customerSession = Mage::getSingleton('customer/session');
       

        foreach ($this->_rules as $rule) {
            // @var $rule AW_Checkoutpromo_Model_Rule 
            // already tried to validate and failed               

            if ($rule->getIsValid() === false)
                continue;

            if ($rule->getIsValid() !== true) {
                $rule->afterLoad();

           
                if (!$rule->validate($quote)) { // quote does not meet rule's conditions
                   
                    $rule->setIsValid(false);
                    continue;
                }
                $rule->setIsValid(true); // passed all validations, remember to be valid
            }

            // MSS check
            if (Mage::helper('checkoutpromo')->isMSSInstalled()
                    && $mssRuleId = $rule->getMssRuleId()
            ) {
                $object = false;

                if ($customerSession->isLoggedIn())
                    $object = $customerSession->getCustomer();
                else
                    $object = $quote;

                if (!Mage::getModel('marketsuite/filter')->checkRule($object, $mssRuleId))
                    continue;
            }

            /*
              $qty = $item->getQty();
              if ($item->getParentItem()) {
              $qty *= $item->getParentItem()->getQty();
              }

              Mage::dispatchEvent('checkoutpromo_validator_process', array(
              'rule'    => $rule,
              'item'    => $item,
              'address' => $address,
              'quote'   => $quote,
              'qty'     => $qty,
              ));
             */
            $cmsBlockId = $rule->getCmsBlockId();

            if ($rule->getShowOnShoppingCart())
                $this->appliedBlockIds['shoppingcartpromo'][] = $cmsBlockId;
            if ($rule->getShowOnCheckout())
                $this->appliedBlockIds['checkoutpromo'][] = $cmsBlockId;

            if ($rule->getStopRulesProcessing())
                break;
        }

        if (array_key_exists('shoppingcartpromo', $this->appliedBlockIds))
            $this->appliedBlockIds['shoppingcartpromo'] = array_unique($this->appliedBlockIds['shoppingcartpromo']);

        if (array_key_exists('checkoutpromo', $this->appliedBlockIds))
            $this->appliedBlockIds['checkoutpromo'] = array_unique($this->appliedBlockIds['checkoutpromo']);

        return $this;
    }

    public function mergeIds($a1, $a2, $asString=true) {
        if (!is_array($a1)) {
            $a1 = empty($a1) ? array() : explode(',', $a1);
        }
        if (!is_array($a2)) {
            $a2 = empty($a2) ? array() : explode(',', $a2);
        }
        $a = array_unique(array_merge($a1, $a2));
        if ($asString) {
            $a = implode(',', $a);
        }
        return $a;
    }

}
