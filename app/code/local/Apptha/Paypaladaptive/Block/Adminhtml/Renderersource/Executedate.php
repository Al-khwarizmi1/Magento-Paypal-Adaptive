<?php

/**
 * In this class contains execute date grid column function.
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
class Apptha_Paypaladaptive_Block_Adminhtml_Renderersource_Executedate extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    /*
     * Get payment execute date
     * 
     * @param object $row collection row
     * @return datetime $executepaymentEate execute payment date  
     */

    public function render(Varien_Object $row) {
        $orderId = $row->getData($this->getColumn()->getIndex());
        $orders = Mage::getModel('paypaladaptive/delaychaineddetails')->getCollection()
                ->addFieldToFilter('order_id', $orderId);
        foreach ($orders as $order) {
            $executepaymentEate = $order->getExecutepaymentDate();
            break;
        }

        if (empty($executepaymentEate)) {
            $executepaymentEate = Mage::helper("paypaladaptive")->__('NA');
        }

        return $executepaymentEate;
    }

}
