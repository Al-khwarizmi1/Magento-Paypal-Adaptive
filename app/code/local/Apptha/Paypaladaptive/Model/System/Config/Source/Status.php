<?php

/**
 * In this class contains the function for prepare success order status options.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Ramkumar
 * @Modified Date   January 23,2014
 *
 * */

class Apptha_Paypaladaptive_Model_System_Config_Source_Status {
    /*
     * Adaptive paypal order success status
     * 
     * @return array status option
     */
    public function toOptionArray() {
        return array(
            array('value' => 'processing', 'label' => Mage::helper('paypaladaptive')->__('Processing')),
            array('value' => 'complete', 'label' => Mage::helper('paypaladaptive')->__('Complete')),
        );
    }

}