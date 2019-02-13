<?php

/**
 * In this class contains order status grid column function.
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
class Apptha_Paypaladaptive_Block_Adminhtml_Renderersource_Orderstatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    /*
     * Getting order status
     * 
     * @param object $row collection row
     * @return string order status
     */

    public function render(Varien_Object $row) {
        $orderId = $row->getData($this->getColumn()->getIndex());
        $orders = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('entity_id', $orderId);
        foreach ($orders as $order) {

            // Changing order status first letter capital 
            return str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($order->getStatus()))));
        }
    }

}
