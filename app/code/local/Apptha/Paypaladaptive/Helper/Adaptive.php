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
 * @Creation Date   December 28,2017
 * @Modified By     Varun S
 * @Modified Date   December 28,2017
 *
 * */
class Apptha_Paypaladaptive_Helper_Adaptive extends Mage_Core_Helper_Abstract {
    /**
     *  Get Admin Discount Fee Payer
     *
     * @return int FeePayer
     */
    public function getIsAdminDiscountFeePayer() {
        return Mage::getStoreConfig('payment/paypaladaptive/feepayer_discount');
    }

    /**
     *  Get Shipping Cost
     * @param array $sellerinfo
     * @param object $order
     * @param int $itemQty
     * @return int Shipping cost
     */
    public function getShippingCost($sellerInfo, $order,$itemQty){
        $shippingAddress = $order->getShippingAddress()->getData();
        $orderShippingMethod = $order->getShippingMethod();
        $shippingCountry = $shippingAddress['country_id'];
        $marketplaceShipping = Mage::getStoreConfigFlag ( 'marketplace/shipping/shippingcost');
        $sellerCountry = $sellerInfo['country'];
        $sellerNationalShipping = $sellerInfo['national_shipping_cost'];
        $sellerInternationalShipping = $sellerInfo['international_shipping_cost'];
        if($orderShippingMethod == 'apptha_apptha' && !$marketplaceShipping ){
            if($shippingCountry == $sellerCountry){
                $shippingCost = $sellerNationalShipping * $itemQty;
            } else{
                $shippingCost = $sellerInternationalShipping * $itemQty;
            }
        }else{
            $shippingCost = 0;
        }
        return $shippingCost;
    }

    /**
     *  Get Percent Per Product
     * @param array $sellerinfo
     * @return int Percent Per Product
     */
    public function getPercentPerProduct($sellerInfo){
        $commissionMode = Mage::getStoreConfig('marketplace/product/commission_mode');
        if($commissionMode == 'seller'){
            $percentPerProduct = $sellerInfo['commission'];
        }
        if($commissionMode == 'catalog'){
            $percentPerProduct = $this->getCategoryCommission($productId);
        }
        if($percentPerProduct == 0){
            $percentPerProduct = Mage::getStoreConfig('marketplace/marketplace/percentperproduct');
        }
        return $percentPerProduct;
    }

    /**
    * Get commission details for a product
    *
    * @return minimum commission amount if product is associated with multiple categories
    */
    public function getCategoryCommission($productId) {
        $categoryIds = Mage::getModel('catalog/product')->load($productId)->getCategoryIds();
        $commission = array(0);
        foreach ($categoryIds as $categoryId) {
            $commission[] = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($categoryId)->getCommission();
            }
        return min($commission);
    }

    /**
     *  Get coupon rule
     *  @param int $couponCode
     * @return object coupon rule
     */
    public function getCouponRule($couponCode){
        $oCoupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
        return  Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
    }
    /**
     *  Get coupon type
     *  @param object $couponCode
     * @return string coupon type
     */
    public function getCouponType($couponCode){
        $couponRule = $this->getCouponRule($couponCode);
        return $couponRule->getSimpleAction();

    }

    /**
     *  Get Coupon Config Amount
     *  @param object $couponCode
     * @return int Coupon Config Amount
     */
    public function getCouponConfigAmount($couponCode){
        $couponRule = $this->getCouponRule($couponCode);
        return $couponRule->getDiscountAmount();
    }
    /**
     *  Get Coupon Amount
     * @param int $productAmount
     * @param int $couponCode
     * @param int $itemQty
     * @return int CouponAmount
     */
    public function getCouponAmount($productAmount, $couponCode,$itemQty){
        $couponAmount = 0;
        $couponPercent = $this->getCouponConfigAmount($couponCode);
        $couponType = $this->getCouponType($couponCode);
        $isAdminDiscountFeePayer = $this->getIsAdminDiscountFeePayer();
    if($couponType == 'by_percent' && !$isAdminDiscountFeePayer){
        $couponAmount = $productAmount * ($couponPercent / 100);
    }
    if($couponType == 'by_fixed' && !$isAdminDiscountFeePayer){
        $couponAmount = $couponPercent * $itemQty;
    }
    return $couponAmount;
    }

    /**
     *  Calculating coupon amount in cart
     *  @param array $sellerData
     *  @param int $couponCode
     *  @return array $sellerdata
     */
    public function calculateCartCoupon($sellerData, $couponCode){
        $totalSellerAmount = $this->getSellerProductTotal($sellerData);
        $couponDiscountAmount = $this->getCouponConfigAmount($couponCode);
        foreach ($sellerData as $key => $seller) {
            $sellerAmount = $seller['amount'] + $seller['commission_fee'] - $seller['shipping_fee'];
            $discount = round((($sellerAmount / $totalSellerAmount) * $couponDiscountAmount),2);
            $sellerData[$key]['coupon_amount'] = $discount;
            $sellerData[$key]['amount'] = $seller['amount'] - $discount;
        }
        return $sellerData;
    }

    /**
     *  Get Seller Product Total
     *  @param array $sellerData
     * @return int commistion percent
     */
    public function getSellerProductTotal($sellerData) {
                $amountTotal = 0;
                foreach ($sellerData as $data) {
                    $amountTotal = $amountTotal + $data['amount'] + $data['commission_fee'] - $data['shipping_fee'];
                }
                return $amountTotal;
            }
}
?>