<?php

/**
 * In this class contains refund grid functionality.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 16,2014
 * @Modified By     Ramkumar M
 * @Modified Date   March 26,2014
 *
 * */
class Apptha_Paypaladaptive_Adminhtml_RefunddetailsController extends Mage_Adminhtml_Controller_action {
    /*
     * Initiate refund details grid 
     */

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('paypaladaptive/refunddetails')
                ->_addBreadcrumb(Mage::helper('paypaladaptive')->__('Paypal Adaptive Refund'), Mage::helper('paypaladaptive')->__('Paypal Adaptive Refund'));

        $this->getLayout()->getBlock('head')->setTitle($this->__('Paypal Adaptive Refund Details'));

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
