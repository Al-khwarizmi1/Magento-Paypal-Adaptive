<?php

/**
 * In this class contains execute payment grid column function.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   March 25,2014
 * @Modified By     Ramkumar M
 * @Modified Date   March 25,2014
 *
 * */
class Apptha_Paypaladaptive_Block_Adminhtml_Renderersource_Executepayment extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    /*
     * Getting order status
     * 
     * @param object $row collection row
     * @return string $result order status
     */

    public function render(Varien_Object $row) {
        $orderId = $row->getData($this->getColumn()->getIndex());
        $orders = Mage::getModel('paypaladaptive/delaychaineddetails')->getCollection()
                ->addFieldToFilter('order_id', $orderId);
        foreach ($orders as $order) {
            $transactionStatus = $order->getTransactionStatus();
            $payKey = $order->getPayKey();
            $trackingId = $order->getTrackingId();
            break;
        }

        if ($transactionStatus == 'Completed') {
            $result = "<a href='" . $this->getUrl('*/*/pay', array('order_id' => $orderId, 'pay_key' => $payKey, 'tracking_id' => $trackingId)) . "' title='" . Mage::helper('paypaladaptive')->__('Click to Pay Now') . "'>" . Mage::helper('paypaladaptive')->__('Pay Now') . "</a>";
        } else {
            $result = Mage::helper("paypaladaptive")->__('NA');
        }
        return $result;
    }

}
