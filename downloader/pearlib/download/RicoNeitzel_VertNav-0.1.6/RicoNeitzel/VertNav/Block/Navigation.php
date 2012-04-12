<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   RicoNeitzel
 * @package    RicoNeitzel_VertNav
 * @copyright  Copyright (c) 2009 Vinai Kopp http://netzarbeiter.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog vertical navigation
 *
 * @category   RicoNeitzel
 * @package    RicoNeitzel_VertNav
 * @author     Vinai Kopp <vinai@netzarbeiter.com>
 */
class RicoNeitzel_VertNav_Block_Navigation extends Mage_Catalog_Block_Navigation
{
	
	/**
	 * Add the customer group to the cache key so this module is compatible with more extensions.
	 * Netzarbeiter_GroupsCatalog
	 * Netzarbeiter_LoginCatalog
	 *
	 * @return string
	 */
    public function getCacheKey()
    {
        $key = 'VERTNAV_' . parent::getCacheKey();
        $key .= $this->_getCustomerGroupId();
        return $key;
    }
    
    /**
     * check if we should hide the categories because of Netzarbeiter_LoginCatalog
     *
     * @return boolean
     */
    protected function _checkLoginCatalog()
    {
    	return $this->_isLoginCatalogInstalledAndActive() && $this->_loginCatalogHideCategories();
    }
    
    /**
     * Check if the Netzarbeter_LoginCatalog extension is installed and active
     *
     * @return boolean
     */
    protected function _isLoginCatalogInstalledAndActive()
    {
    	if ($node = Mage::getConfig()->getNode('modules/Netzarbeiter_LoginCatalog'))
    	{
    		return strval($node->active) == 'true';
    	}
    	return false;
    }
    
    /**
     * Check if the Netzarbeter_LoginCatalog extension is configured to hide categories from logged out customers
     *
     * @return boolean
     */
    protected function _loginCatalogHideCategories()
    {
    	if (! Mage::getSingleton('customer/session')->isLoggedIn()
			&& Mage::helper('logincatalog')->moduleActive()
			&& Mage::helper('logincatalog')->getConfig('hide_categories')) {
				return true;
		}
		return false;
    }
    
    /**
     * This method is only here to provide compatibility with the Netzarbeter_LoginCatalog extension
     *
     * @param Varien_Data_Tree_Node $category
     * @param int $level
     * @param bool $last
     * @return string
     */
    public function drawItem($category, $level=0, $last=false)
    {
        if ($this->_checkLoginCatalog()) return '';
        return parent::drawItem($category, $level, $last);
    }
    
    /**
     * Add project specific formatting
     *
     * @param Varien_Data_Tree_Node $category
     * @param integer $level
     * @param array $class
     * @return string
     */
    public function drawOpenCategoryItem(Varien_Data_Tree_Node $category, $level=0, array $class=null)
    {
        $html = '';
        
        if ($this->_checkLoginCatalog()) return $html;
        
        if (!$category->getIsActive()) return $html;
        
        if (! isset($class)) $class = array();
        
        $class[] = 'level' . $level;
        $class[] = $this->_getClassNameFromCategoryName($category);
        if ($this->_isCurentCategory($category))
        {
        	$class[] = 'active';
        }
        else
        {
			$class[] = $this->isCategoryActive($category) ? 'parent' : 'inactive';
        }
        
        // indent HTML!
        $html .= str_pad ( "", ($level * 2 ) + 2, " " ).sprintf('<li class="%s">', implode(" ", $class))."\n";

        $html .= str_pad ( "", (($level * 2 ) + 4), " " ).'<a href="'.$this->getCategoryUrl($category).'"><span>'.$this->htmlEscape($category->getName()).'</span></a>'."\n";

        if (in_array($category->getId(), $this->getCurrentCategoryPath()))
        {
            $children = $category->getChildren();
            $hasChildren = $children && ($childrenCount = $children->count());
            if ($hasChildren)
            {
            	$children = $this->toLinearArray($children);
                $htmlChildren = '';
				
                foreach ($children as $i => $child)
                {
                	$class = array();
                	if ($childrenCount == 1)
                	{
                		$class[] = 'only';
                	}
                	else
                	{
	                	if (! $i) $class[] = 'first';
	                	if ($i == $childrenCount-1) $class[] = 'last';
                	}
                	if (isset($children[$i+1]) && $this->isCategoryActive($children[$i+1])) $class[] = 'prev';
                	if (isset($children[$i-1]) && $this->isCategoryActive($children[$i-1])) $class[] = 'next';
                    $htmlChildren.= $this->drawOpenCategoryItem($child, $level+1, $class);
                }

                if (!empty($htmlChildren))
                {
					// indent HTML!
                    $html.= str_pad ( "", ($level * 2 ) + 2, " " ).'<ul>'."\n"
                            .$htmlChildren."\n".
                            str_pad ( "", ($level * 2 ) + 2, " " ).'</ul>';
                }
            }
        }
        // indent HTML!
        $html.= "\n".str_pad ( "", ($level * 2 ) + 2, " " ).'</li>'."\n";
        return $html;
    }
    
    
    public function toLinearArray($collection)
    {
    	$array = array();
    	foreach ($collection as $item) $array[] = $item;
    	return $array;
    }
    
    protected function _getClassNameFromCategoryName(Varien_Data_Tree_Node $category)
    {
    	$name = $category->getName();
    	$name = strtolower(preg_replace('/-{2,}/', '-', preg_replace('/[\s\W_]/', '-', $name)));
    	return $name;
    }
	
	/**
	 * Return the current customer group id. Logged out customers get the group id 0,
	 * not the default set in system > config > customers
	 *
	 * @return integer
	 */
	protected function _getCustomerGroupId()
	{
		$session = Mage::getSingleton('customer/session');
		if (! $session->isLoggedIn()) $customerGroupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
		else $customerGroupId = $session->getCustomerGroupId();
		return $customerGroupId;
	}
	
	protected function _isCurentCategory($category)
	{
		return ($cat = $this->getCurrentCategory()) && $cat->getId() == $category->getId();
	}
}
