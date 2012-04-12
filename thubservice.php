<?php

  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Magento  #
  #       Copyright (c) Atandra LLC.          #
  #       www.atandra.com                     #
  #                                           #
  #-------------------------------------------#


if (version_compare(phpversion(), '5.2.0', '<')===true) {
    die('ERROR: Whoops, it looks like you have an invalid PHP version. Magento supports PHP 5.2.0 or newer.');
}

require 'app/Mage.php';

try {
    Mage::setIsDeveloperMode(true);

   $app = Mage::app('');

    $thub = Mage::getSingleton('thub/run_run');

    $thub->init($app);
   // var_dump($thub);

    $thub->run();
    /* @var $installer Mage_Install_Model_Installer_Console */

/*    if ($installer->init($app)          // initialize installer
        && $installer->checkConsole()   // check if the script is run in shell, otherwise redirect to web-installer
        && $installer->setArgs()        // set and validate script arguments
        && $installer->install())       // do install
    {
        echo 'SUCCESS: ' . $installer->getEncryptionKey() . "\n";
        exit;
    }
  */
} catch (Exception $e) {
    Mage::printException($e);
}

//print get_class($app);

