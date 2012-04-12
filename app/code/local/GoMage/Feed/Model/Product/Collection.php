<?php
 /**
 * GoMage.com
 *
 * GoMage Feed Pro
 *
 * @category     Extension
 * @copyright    Copyright (c) 2010 GoMage.com (http://www.gomage.com)
 * @author       GoMage.com
 * @license      http://www.gomage.com/licensing  Single domain license
 * @terms of use http://www.gomage.com/terms-of-use
 * @version      Release: 2.0
 * @since        Class available since Release 2.0
 */

class GoMage_Feed_Model_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    
    protected $_feed_categories = null;
    
    public function isEnabledFlat()
    {        
        return false;
    }
    
    protected function _productLimitationJoinPrice()
    {
        $filters = $this->_productLimitationFilters;
        if (empty($filters['use_price_index'])) {
            return $this;
        }

        $connection = $this->getConnection();

        $joinCond = $joinCond = join(' AND ', array(
            'price_index.entity_id = e.entity_id',
            $connection->quoteInto('price_index.website_id = ?', $filters['website_id']),
            $connection->quoteInto('price_index.customer_group_id = ?', $filters['customer_group_id'])
        ));

        $fromPart = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        if (!isset($fromPart['price_index'])) {
            $minimalExpr = new Zend_Db_Expr(
                'IF(`price_index`.`tier_price`, LEAST(`price_index`.`min_price`, `price_index`.`tier_price`), `price_index`.`min_price`)'
            );
            $this->getSelect()->joinLeft(
                array('price_index' => $this->getTable('catalog/product_index_price')),
                $joinCond,
                array('price', 'tax_class_id', 'final_price', 'minimal_price'=>$minimalExpr , 'min_price', 'max_price', 'tier_price')
            );

            // Set additional field filters
            if (isset($this->_priceDataFieldFilters) && is_array($this->_priceDataFieldFilters))
            {
                foreach ($this->_priceDataFieldFilters as $filterData) {
                    $this->getSelect()->where(call_user_func_array('sprintf', $filterData));
                }
            }
        } else {
            $fromPart['price_index']['joinCondition'] = $joinCond;
            $this->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
        }

        return $this;
    }
    
    protected function _applyProductLimitations()
    {
        $this->_prepareProductLimitationFilters();
        $this->_productLimitationJoinWebsite();
        $this->_productLimitationJoinPrice();
        $filters = $this->_productLimitationFilters;

        if (!isset($filters['category_id']) && !isset($filters['visibility'])) {
            return $this;
        }

        $conditions = array(
            'cat_index.product_id=e.entity_id',
            $this->getConnection()->quoteInto('cat_index.store_id=?', $filters['store_id'])
        );
        if (isset($filters['visibility']) && !isset($filters['store_table'])) {
            $conditions[] = $this->getConnection()
                ->quoteInto('cat_index.visibility IN(?)', $filters['visibility']);
        }
        
        if (is_array($this->_feed_categories)){
            $conditions[] = $this->getConnection()
                ->quoteInto('cat_index.category_id IN(?)', $this->_feed_categories);
            $this->getSelect()->distinct();    
        }    
        else
            $conditions[] = $this->getConnection()
                ->quoteInto('cat_index.category_id=?', $filters['category_id']);

            
        if (isset($filters['category_is_anchor'])) {
            $conditions[] = $this->getConnection()
                ->quoteInto('cat_index.is_parent=?', $filters['category_is_anchor']);
        }

        $joinCond = join(' AND ', $conditions);
        $fromPart = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        if (isset($fromPart['cat_index'])) {
            $fromPart['cat_index']['joinCondition'] = $joinCond;
            $this->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
        }
        else {
            $this->getSelect()->join(
                array('cat_index' => $this->getTable('catalog/category_product_index')),
                $joinCond,
                array('cat_index_position' => 0)
            );
        }
        
        if (method_exists($this, '_productLimitationJoinStore'))
            $this->_productLimitationJoinStore();

        Mage::dispatchEvent('catalog_product_collection_apply_limitations_after', array(
            'collection'    => $this
        ));

        return $this;
    }
    
    public function addFeedCategoryFilter($condition, $value){
                    
        $this->_feed_categories = array(0);
        
        $categories = Mage::getResourceModel('catalog/category_collection')
                        ->addIsActiveFilter();
                        
        if ($condition == 'like' || $condition == 'nlike'){
            $category = Mage::getModel('catalog/category')->load($value);
            $categories->addFieldToFilter('name', array($condition=>'%'.$category->getName().'%'));
        }    
        else{ 
            $categories->addFieldToFilter('entity_id', array($condition=>$value));
        }    
                        
        foreach ($categories as $_cat){
            $this->_feed_categories[] = intval($_cat->getId()); 
        }

        $this->_applyProductLimitations();

        return $this;
    }
    

}
