<?php

/**
 * In this class contains the payment method functionality
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Ramkumar M
 * @Modified Date   March 27,2014
 *
 * */
class Apptha_Paypaladaptive_Model_Payment extends Mage_Payment_Model_Method_Abstract {
    /*
     * Initilize payment code and form block type
     */

    protected $_code = 'paypaladaptive';
    protected $_formBlockType = 'paypaladaptive/displayform';
    protected $_canAuthorize = true;

    /*
     * Initilize order place redirect url  
     * 
     * @return url order redirect url 
     */

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('paypaladaptive/adaptive/redirect', array('_secure' => true));
    }

}