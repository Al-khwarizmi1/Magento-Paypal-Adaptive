<?php

/**
 * In this class contains payment details grid
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 13,2014
 * @Modified By     Ramkumar M
 * @Modified Date   January 13,2014
 *
 * */
class Apptha_Paypaladaptive_Block_Adminhtml_Paymentdetails extends Mage_Adminhtml_Block_Widget_Grid_Container {
    /*
     * Class constructor
     */

    public function __construct() {
        $this->_controller = 'adminhtml_paymentdetails';
        $this->_blockGroup = 'paypaladaptive';
        $this->_headerText = Mage::helper('paypaladaptive')->__('Payment Details');
        $this->_addButtonLabel = Mage::helper('paypaladaptive')->__('');
        parent::__construct();
        $this->_removeButton('add');
    }

}

