<?php

/**
 * In this class contains the function for prepare status options.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 10,2014
 * @Modified By     Ramkumar
 * @Modified Date   January 10,2014
 *
 * */
class Apptha_Paypaladaptive_Model_Status extends Varien_Object {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    /**
     * Finds and returns user by ID or username
     *
     * @return array status options
     */
    static public function getOptionArray() {
        return array(
            static::STATUS_ENABLED => Mage::helper('paypaladaptive')->__('Enabled'),
            static::STATUS_DISABLED => Mage::helper('paypaladaptive')->__('Disabled')
        );
    }

}