<?php

/**
 * In this class contains payment grid functions
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Ramkumar M
 * @Modified Date   March 24,2014
 *
 * */
class Apptha_Paypaladaptive_Adminhtml_PaymentdetailsController extends Mage_Adminhtml_Controller_action {
    /*
     * Initiate payment details grid 
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('paypaladaptive/paymentdetails')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Paypal Adaptive Payment'), Mage::helper('adminhtml')->__('Paypal Adaptive Payment'));

        $this->getLayout()->getBlock('head')->setTitle($this->__('Paypal Adaptive Payment Details'));

        return $this;
    }

    /*
     * Index action
     */

    public function indexAction() {

        $this->_initAction()
                ->renderLayout();
    }

}

