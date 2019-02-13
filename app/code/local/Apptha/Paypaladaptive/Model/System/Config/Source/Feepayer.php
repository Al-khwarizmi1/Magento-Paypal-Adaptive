<?php

/**
 * In this class contains the function for prepare fee payer options.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Ramkumar
 * @Modified Date   March 26,2014
 *
 * */
class Apptha_Paypaladaptive_Model_System_Config_Source_Feepayer {
    /*
     * Prepare fee payer options
     * 
     * @return array fee payer options
     */

    public function toOptionArray() {
        return array(
            array('value' => 'EACHRECEIVER', 'label' => Mage::helper('paypaladaptive')->__('Each Reciever')),
        );
    }

}