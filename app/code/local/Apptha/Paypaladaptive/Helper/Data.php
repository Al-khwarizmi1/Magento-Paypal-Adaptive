<?php

/**
 * In this class contains repeated functions like url and store config status
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Ramkumar M
 * @Modified Date   April 02,2014
 *
 * */
class Apptha_Paypaladaptive_Helper_Data extends Mage_Core_Helper_Abstract {
    /*
     * Get Marketplace extenstion installed or not   
     * 
     * @param string $moduleName module name
     * @return int module status  
     */

    public function getModuleInstalledStatus($moduleName) {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array) $modules;
        if (isset($modulesArray[$moduleName])) {
            if ($moduleName == 'Apptha_Marketplace') {
                return Mage::getStoreConfig('marketplace/marketplace/activate');
            }
            if ($moduleName == 'Apptha_Airhotels') {
                return 1;
            }
        } else {
            return 0;
        }
    }

    /*
     *  Get commission percent value
     * 
     * @return int commistion percent
     */

    public function getCommissionPercent() {
        return Mage::getStoreConfig('marketplace/marketplace/percentperproduct');
    }

    /*
     * Get payment description 
     * 
     * @return string payment description
     */

    public function getPaymentDescription() {
        return Mage::getStoreConfig('payment/paypaladaptive/description');
    }

    /*
     * Get refund enable or not
     * 
     * @return int order refund status
     */

    public function getRefundStatus() {
        return Mage::getStoreConfig('payment/paypaladaptive/order_refund');
    }

    /*
     * Get  payment method
     * 
     * @return string payment method 
     */

    public function getPaymentMethod() {
        return Mage::getStoreConfig('payment/paypaladaptive/payment');
    }

    /*
     * Get fee payer
     * 
     * @return string fee payer
     */

    public function getFeePayer() {
        return Mage::getStoreConfig('payment/paypaladaptive/feepayer');
    }

    /*
     * Get order status
     * 
     * @return string new order status
     */

    public function getOrderStatus() {
        return Mage::getStoreConfig('payment/paypaladaptive/order_status');
    }

    /*
     * Get successful order status
     * 
     * @return string success order status
     */

    public function getOrderSuccessStatus() {
        return Mage::getStoreConfig('payment/paypaladaptive/order_success');
    }

    /*
     * Get payment mode 
     * 
     * @return int sandbox mode status
     */

    public function getPaymentMode() {
        return Mage::getStoreConfig('payment/paypaladaptive/sandbox');
    }

    /*
     * Get API username
     * 
     * @return string API username
     */

    public function getApiUserName() {
        return Mage::getStoreConfig('payment/paypaladaptive/paypal_api_username');
    }

    /*
     * Get API password
     * 
     * @return string API password
     */

    public function getApiPassword() {
        return Mage::getStoreConfig('payment/paypaladaptive/paypal_api_password');
    }

    /*
     * Get API signature
     * 
     * @return string API signature
     */

    public function getApiSignature() {
        return Mage::getStoreConfig('payment/paypaladaptive/paypal_api_signature');
    }

    /*
     * Get APP Id
     * 
     * @return string APP id
     */

    public function getAppID() {
        return Mage::getStoreConfig('payment/paypaladaptive/paypal_app_id');
    }

    /*
     * Get Grand Total
     * 
     * @return decimal order grand total
     */

    public function getGrandTotal() {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order');
        return $order->loadByIncrementId($session->getLastRealOrderId())->getGrandTotal();
    }

    /*
     * Get admin admin PayPal id 
     * 
     * @return string admin PayPal id
     */

    public function getAdminPaypalId() {
        return Mage::getStoreConfig('payment/paypaladaptive/merchant_paypal_mail');
    }

    /*
     * Collect defualt seller share
     * 
     * @return array $sellerData seller data
     */

    public function getSellerData() {
        /*
         * Get last order data
         */
        $session = Mage::getSingleton('checkout/session');
        $incrementId = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($incrementId);
        $orderId = $order->getId();
        if (!empty($orderId)) {

            $items = $order->getAllItems();

            $sellerData = array();
            /*
             * Prepare seller share 
             */
            foreach ($items as $item) {
                $sellerAmount = 0;
                $productId = $item->getProductId();

                $productData = Mage::getModel('paypaladaptive/productdetails')->getCollection()
                        ->addFieldToFilter('product_id', $productId);

                $firstRow = $this->getFirstRowData($productData);
                if (!empty($firstRow['product_paypal_id']) && $firstRow['is_enable'] == 1) {
                    $sellerId = $firstRow['product_paypal_id'];
                    $commisionValue = $firstRow['share_value'];
                    $commissionMode = $firstRow['share_mode'];

                    Mage::getModel('paypaladaptive/save')->saveCommissionData($incrementId, $productId, $commisionValue, $commissionMode, $sellerId);

                    $productAmount = $item->getPrice() * $item->getQtyToInvoice();

                    if ($commissionMode == 'percent') {
                        $productCommission = $productAmount * ($commisionValue / 100);
                        $sellerAmount = $productAmount - $productCommission;
                    } else {
                        $productCommission = $commisionValue;
                        $sellerAmount = $productAmount - $commisionValue;
                    }
                    /*
                     * Collect seller share individually
                     */

                    if (array_key_exists($sellerId, $sellerData)) {
                        $sellerData[$sellerId]['amount'] = $sellerData[$sellerId]['amount'] + $sellerAmount;
                        $sellerData[$sellerId]['commission_fee'] = $sellerData[$sellerId]['commission_fee'] + $productCommission;
                    } else {
                        $sellerData[$sellerId]['amount'] = $sellerAmount;
                        $sellerData[$sellerId]['commission_fee'] = $productCommission;
                        $sellerData[$sellerId]['seller_id'] = $sellerId;
                    }
                }
            }
            return $sellerData;
        } else {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("No order for processing found"));
            $url = Mage::getUrl('checkout/cart', array('_secure' => true));
            Mage::app()->getResponse()->setRedirect($url);
            return FALSE;
        }
    }   

    /*
     * Collect Marketplace seller share
     * 
     * @return array $sellerData Marketplace seller data
     */

    public function getMarketplaceSellerData() {
        /*
         * Getting last order data
         */
        $session = Mage::getSingleton('checkout/session');
        $incrementId = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $orderId = $order->getId();
        $couponCode = $order->getCouponCode();
        $couponType = Mage::helper('paypaladaptive/adaptive')->getCouponType($couponCode);
        $isAdminDiscountFeePayer = Mage::helper('paypaladaptive/adaptive')->getIsAdminDiscountFeePayer();
        if (!empty($orderId)) {
            $items = $order->getAllItems();
            $sellerData = array();
            /*
             * Prepare seller share 
             */
            foreach ($items as $item) {
                $sellerAmount = 0;
                $productId = $item->getProductId();
                $sellerProductData = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('entity_id', $productId)->addAttributeToSelect('*')->setPageSize(1);
                $product = $this->getFirstRowData($sellerProductData);
                $marketplaceGroupId = Mage::helper('marketplace')->getGroupId();
                $productGroupId = $product->getGroupId();
                if ($marketplaceGroupId == $productGroupId) {
                    $sellerInfo = Mage::getModel('marketplace/sellerprofile')->load($product->getSellerId(), 'seller_id');
                    $sellerPaypalId = $sellerInfo['paypal_id'];
                    $sellerId = $product->getSellerId();
                    if (empty($sellerInfo)) {
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("Please contact admin partner paypal id is required"));
                        $url = Mage::getUrl('checkout/cart', array('_secure' => true));
                        Mage::app()->getResponse()->setRedirect($url);
                        return;
                    }
                    $itemQty =$item->getQtyToInvoice();
                    $productAmount = $item->getPrice() * $itemQty;
                    $percentPerProduct = Mage::helper('paypaladaptive/adaptive')->getPercentPerProduct($sellerInfo);
                    $shippingCost =  Mage::helper('paypaladaptive/adaptive')->getShippingCost($sellerInfo, $order,$itemQty);
                    $productCommission = $productAmount * ($percentPerProduct / 100);
                    $couponAmount = Mage::helper('paypaladaptive/adaptive')->getCouponAmount($productAmount, $couponCode,$itemQty);
                    $sellerAmount = $productAmount - $productCommission + $shippingCost - $couponAmount;
                    Mage::getModel('paypaladaptive/save')->saveCommissionData($incrementId, $productId, $percentPerProduct, 'percent', $sellerPaypalId);
                    /*
                     * Calculate seller share individually
                     */
                    if (array_key_exists($sellerId, $sellerData)) {
                        $sellerData[$sellerId]['amount'] = $sellerData[$sellerId]['amount'] + $sellerAmount;
                        $sellerData[$sellerId]['commission_fee'] = $sellerData[$sellerId]['commission_fee'] + $productCommission;
                        $sellerData[$sellerId]['shipping_fee'] = $sellerData[$sellerId]['shipping_fee'] + $shippingCost;
                        $sellerData[$sellerId]['coupon_amount'] = $sellerData[$sellerId]['coupon_amount'] + $couponAmount;
                    } else {
                        $sellerData[$sellerId]['amount'] = $sellerAmount;
                        $sellerData[$sellerId]['commission_fee'] = $productCommission;
                        $sellerData[$sellerId]['seller_id'] = $sellerPaypalId;
                        $sellerData[$sellerId]['shipping_fee'] = $shippingCost;
                        $sellerData[$sellerId]['coupon_amount'] = $couponAmount;
                    }
                }
            }
            if($couponType == 'cart_fixed' && !$isAdminDiscountFeePayer){
            $sellerData =  Mage::helper('paypaladaptive/adaptive')->calculateCartCoupon($sellerData, $couponCode);
            }
            return $sellerData;
        } else {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("No order for processing found"));
            $url = Mage::getUrl('checkout/cart', array('_secure' => true));
            Mage::app()->getResponse()->setRedirect($url);
            return;
        }
    }

    /*
     * Get first row data from collection 
     * 
     * @param object $collections collection
     * @return array $collection collection
     */

    public function getFirstRowData($collections) {
        foreach ($collections as $collection) {
            return $collection;
        }
    }

    /*
     * Collect Airhotels host share
     * 
     * @return array $sellerData Airhotels host data
     */

    public function getAirhotelsHostData() {
        /*
         * Getting last order data
         */
        $session = Mage::getSingleton('checkout/session');
        $incrementId = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $orderId = $order->getId();
        $sellerData = array();
        if (!empty($orderId)) {

            $items = $order->getAllItems();

            /*
             * Prepare host share 
             */
            foreach ($items as $item) {
                $sellerAmount = 0;
                $productId = $item->getProductId();

                $sellerProductData = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('entity_id', $productId)->addAttributeToSelect('*')->setPageSize(1);
                $product = $this->getFirstRowData($sellerProductData);
                $sellerId = $product->getPaypalid();
                if (empty($sellerId)) {
                    Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("Please contact admin partner paypal id is required"));
                    $url = Mage::getUrl('checkout/cart', array('_secure' => true));
                    Mage::app()->getResponse()->setRedirect($url);
                    return FALSE;
                } else {
                    $productAmount = $item->getPrice() * $item->getQtyToInvoice();
                    $percentPerProduct = Mage::getStoreConfig('airhotels/custom_group/airhotels_hostfee');
                    
                    $productCommission = $productAmount * ($percentPerProduct / 100);
                    $sellerAmount = $productAmount - $productCommission;

                    Mage::getModel('paypaladaptive/save')->saveCommissionData($incrementId, $productId, $percentPerProduct, 'percent', $sellerId);
                    /*
                     * Calculate seller share individually
                     */
                    if (array_key_exists($sellerId, $sellerData)) {
                        $sellerData[$sellerId]['amount'] = $sellerData[$sellerId]['amount'] + $sellerAmount;
                        $sellerData[$sellerId]['commission_fee'] = $sellerData[$sellerId]['commission_fee'] + $productCommission;
                    } else {
                        $sellerData[$sellerId]['amount'] = $sellerAmount;
                        $sellerData[$sellerId]['commission_fee'] = $productCommission;
                        $sellerData[$sellerId]['seller_id'] = $sellerId;
                    }
                }
            }
        } else {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("No order for processing found"));
            $url = Mage::getUrl('checkout/cart', array('_secure' => true));
            Mage::app()->getResponse()->setRedirect($url);
            return FALSE;
        }
        return $sellerData;
    }
}
?>