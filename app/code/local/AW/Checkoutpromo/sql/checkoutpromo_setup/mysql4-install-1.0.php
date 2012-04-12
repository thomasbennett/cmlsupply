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
$installer = $this;
$installer->startSetup();

try
{
    $installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('checkoutpromo/rule')};

CREATE TABLE {$this->getTable('checkoutpromo/rule')} (
 `rule_id` int(10) unsigned NOT NULL auto_increment,
 `name` varchar(255) NOT NULL default '',
 `description` text NOT NULL,
 `from_date` date default '0000-00-00',
 `to_date` date default '0000-00-00',
 `customer_group_ids` varchar(255) NOT NULL default '',
 `is_active` tinyint(1) NOT NULL default '0',
 `conditions_serialized` mediumtext NOT NULL,
 `stop_rules_processing` tinyint(1) NOT NULL default '1',
 `sort_order` int(10) unsigned NOT NULL default '0',
 `website_ids` text,
 `cms_block_id` smallint(6) default NULL,
 `show_on_shopping_cart` tinyint(1) NOT NULL default '0',
 `show_on_checkout` tinyint(1) NOT NULL default '1',
 `mss_rule_id` int(10) NOT NULL default '0' COMMENT 'aheadWorks Market Segmentation Suite rule ID',
 PRIMARY KEY (`rule_id`),
 KEY `sort_order` (`is_active`,`sort_order`,`to_date`,`from_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

} catch(Exception $e) { Mage::logException($e); }

$installer->endSetup();