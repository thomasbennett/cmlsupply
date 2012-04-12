<?php
  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#
class Mage_Thub_Model_Run_Abstract
{
    /**
     * Installer singleton
     *
     * @var Mage_Install_Model_Installer
     */
    protected $_thub;

    /**
     * Get installer singleton
     *
     * @return Mage_Install_Model_Installer
     */
    protected function _getThub()
    {
        if (is_null($this->_thub)) {
            $this->_thub = Mage::getSingleton('thub/run');
        }
        return $this->_thub;
    }
}
