<?php

/**
 * In this class contains payment form function
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Ramkumar M
 * @Modified Date   January 02,2014
 *
 * */
class Apptha_Paypaladaptive_Block_Displayform extends Mage_Payment_Block_Form {
    /*
     * Class constructor
     */

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('paypaladaptive/form.phtml');
    }

}