<?php

/**
 * In this class contains payment functinality like success, failure and cancel
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Varun S
 * @Modified Date   January 02,2017
 *
 * */
 class Apptha_Paypaladaptive_AdaptiveController extends Mage_Core_Controller_Front_Action {
    /*
     * Apptha payPal adaptive payment action
     */
    public function redirectAction() {
        /*
         *  Checking whether order id available or not
         */
        $errorFlag = 0;
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $orderId = $order->getId();
        $orderStatus = $order->getStatus();
        if (empty($orderId) || $orderStatus != 'pending') {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("No order for processing found"));
            $this->_redirect('checkout/cart', array('_secure' => true));
            return FALSE;
        }
        /*
         * Initilize adaptive payment data
         */
        $trackingId = $this->generateTrackingID();
        $configurationData = $this->getConfigurations();
        /*
         * Checking where marketplace enable or not and Calculating receiver data
         */
        if ($configurationData['enabledMarplace'] == 1) {
            $receiverData = Mage::helper('paypaladaptive')->getMarketplaceSellerData();
        } elseif ($configurationData['enabledAirhotels'] == 1) {
            $receiverData = Mage::helper('paypaladaptive')->getAirhotelsHostData();
        } else {
            $receiverData = Mage::helper('paypaladaptive')->getSellerData();
        }
        /*
         * If Checking whether receiver count greater than 5 or not
         */
        $receiverCount = count($receiverData);
        if ($receiverCount > 5) {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("You have ordered more than 5 partner products"));
            $this->_redirect('checkout/cart', array('_secure' => true));
            return;
        }
        /*
         * Geting checkout grand total amount 
         */
        $grandTotal = round(Mage::helper('paypaladaptive')->getGrandTotal(), 2);
        /*
         * Validating, grandtotal > reciever amount and no negative seller amounts
         */
        $sellerTotal = round($this->getAmountTotal($receiverData), 2);
        $validationFlag = $this->validateTotalAmount($grandTotal, $receiverData);
        if ($validationFlag) {
            /*
             * Initilize receiver data and invoice ID
             */
            $receiverAmountArray = $receiverEmailArray = $receiverPrimaryArray = $receiverInvoiceIdArray = array();
            $invoiceId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $paypalInvoiceId = $invoiceId . $trackingId;

            foreach ($receiverData as $data) {
                /*
                 * Getting receiver paypal id
                 */
                $receiverPaypalId = $data['seller_id'];
                $receiverAmountArray[] = round($data['amount'], 2);
                $receiverEmailArray[] = $receiverPaypalId;
                $receiverPrimaryArray[] = 'false';
                $receiverInvoiceIdArray[] = $paypalInvoiceId;
            }
            /**
             *  Getting admin details
            **/
            $receiverEmailArray[] = Mage::helper('paypaladaptive')->getAdminPaypalId();
            $receiverInvoiceIdArray[] = $paypalInvoiceId;
            $receiverPrimaryArray[] = 'true';
            $receiverAmountArray[] = round($grandTotal, 2);
        /**
        * Make API call and check for response
        **/
        $resArray = Mage::getModel('paypaladaptive/apicall')->CallPay($configurationData, $trackingId, $receiverEmailArray, $receiverAmountArray, $receiverPrimaryArray, $receiverInvoiceIdArray);
        
        $ack = strtoupper($resArray["responseEnvelope"]["ack"]);

        } else {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("The Discount Applied is not valid for this payment gateway.  Please contact our Support."));
            $errorFlag = 1;
            Mage::log($order->getData(), null, 'paypal.log');
        }
        if ($ack == "SUCCESS" && $errorFlag != 1) {
            $cmd = "cmd=_ap-payment&paykey=" . urldecode($resArray["payKey"]);
            /*
             * Assigning session value for paykey , tracking id and order id
             */
            $this->setSessionData($trackingId,urldecode($resArray["payKey"]),$invoiceId,$configurationData['paymentMethod']);
            /*
             * Storing seller payment details to paypaladaptivedetails table 
             */
            foreach ($receiverData as $data) {
                /*
                 * Initilizing payment data for save 
                 */
                $dataSellerId = $data['seller_id'];
                $dataAmount = round($data['amount'], 2);
                $dataCommissionFee = round($data['commission_fee'], 2);
                $dataPayKey = $resArray["payKey"];
                $dataGroupType = 'seller';
                $shippingFee = $data['shipping_fee'];
                $discountAmount = $data['coupon_amount'];
                /*
                 * Calling save function for storing seller payment data
                 */
                $paramsToSave = $this->constructArrayToSave($dataSellerId, $dataAmount, $dataCommissionFee, $dataPayKey, $dataGroupType, $shippingFee, $discountAmount);
                Mage::getModel('paypaladaptive/save')->saveOrderData($paramsToSave);
            }
            /*
             * Initilizing payment data for save
             */
            $dataSellerId = Mage::helper('paypaladaptive')->getAdminPaypalId();
            $isAdminDiscountFeePayer= Mage::helper('paypaladaptive/adaptive')->getIsAdminDiscountFeePayer();
            $dataAmount = $grandTotal - $sellerTotal;
            $dataCommissionFee = 0;
            $dataPayKey = $resArray["payKey"];
            $dataGroupType = 'admin';
            $orderShippingMethod = $order->getShippingMethod(); 
            if($orderShippingMethod != 'apptha_apptha'){
            $adminshippingFee = $order->getBaseShippingAmount();
            }
            if($isAdminDiscountFeePayer){
                $adminDiscountAmount = abs($order->getBaseDiscountAmount());
            }
            /*
             * Calling save function for storing owner payment data
             */
            $paramsToSave = $this->constructArrayToSave($dataSellerId, $dataAmount, $dataCommissionFee, $dataPayKey, $dataGroupType,$adminshippingFee, $adminDiscountAmount);
            Mage::getModel('paypaladaptive/save')->saveOrderData($paramsToSave);
            /*
             * Redirect to Paypal site
             */
            $this->RedirectToPayPal($cmd);
        } else {
            if($errorFlag != 1){
                Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("There is an error with Paypal's payment gateway. Please contact our Support."));
                Mage::log($resArray, null, 'paypal.log');
            }
            $this->_redirect('checkout/cart', array('_secure' => true));
        }
        return;
    }

    /*
     * Payment success function
     */
    public function returnAction() {
       $this->_redirect('checkout/onepage/success', array('_secure' => true));
       return;
    }

    /*
     * PayPal ipn notification action
     */
    public function ipnnotificationAction() {
      /*
       * Getting pay key and tracking id
       */
        $payKey = $_POST['pay_key'];
        if($payKey == ""){
            $session = Mage::getSingleton('checkout/session');
            $payKey = $session->getPaypalAdaptivePayKey();
        }
        $transactionId = '';
        $paymentCollection = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()->addFieldToFilter('pay_key', $payKey)->getFirstItem();
        $paypalAdaptive = $paymentCollection->getSellerInvoiceId();

        if(count($paymentCollection) >= 1){
        /*
        * Make the Payment Details call using PayPal API
        */
        $resArray = Mage::getModel('paypaladaptive/apicall')->CallPaymentDetails($payKey, $transactionId, $trackingId);

        $ack = strtoupper($resArray["responseEnvelope"]["ack"]);
           
    if ($ack == "SUCCESS") {

         try {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($paypalAdaptive);
    
            $transactionIdData = $resArray["paymentInfoList"]["paymentInfo"][0]["transactionId"];
    
            $order->setLastTransId($transactionIdData)->save();
            if ($order->canInvoice()) {
                $items = $order->getAllItems ();
                /**
                * Generate invoice for shippment.
                */
                $this->createInvoiceAndUpdate($items, $order);
                /*
                 * Saving payment success details
                 */
                $this->updateTransactionIdAndStatus($payKey, $trackingId, $resArray);
        }
      } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
            Mage::log($resArray, null, 'paypal.log');
                }
        }
    }
    }

    /*
     * Order cancel action 
     */

    public function cancelAction() {

        try {
            $session = Mage::getSingleton('checkout/session');
            $paypalAdaptive = $session->getPaypalAdaptiveRealOrderId();
            $payKey = $session->getPaypalAdaptivePayKey();
            $trackingId = $session->getPaypalAdaptiveTrackingId();

            if (empty($paypalAdaptive)) {
                Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("No order for processing found"));
                $this->_redirect('checkout/cart', array('_secure' => true));
                return;
            }
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($paypalAdaptive);
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("Payment Canceled."));

            /*
             * Changing payment status
             */
            Mage::getModel('paypaladaptive/save')->cancelPayment($paypalAdaptive, $payKey, $trackingId);

            $session->unsPaypalAdaptivePayKey();
            $session->unsPaypalAdaptiveTrackingId();
            $session->unsPaypalAdaptiveRealOrderId();

            $this->_redirect('checkout/cart', array('_secure' => true));
            return;
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError(Mage::helper('paypaladaptive')->__("Unable to cancel Paypal Adaptive Checkout."));
            $this->_redirect('checkout/cart', array('_secure' => true));
            return;
        }
    }

    /*
     * Calculate sum of receiver amount
     * 
     * @param array $receiverData receiver data
     * @return decimal $amountTotal total amount
     */

    public function validateTotalAmount($grandTotal, $receiverData) {
        $amountTotal = 0;
        $noErrorflag = 1;
        foreach ($receiverData as $data) {
            $amountTotal = $amountTotal + $data['amount'] - $data['coupon_amount'];
            if($data['amount'] <= 0){
                $noErrorflag = 0;
            }
        }
        if($grandTotal < $amountTotal){
            $noErrorflag = 0;
        }
        return $noErrorflag;
    }

    public function getAmountTotal($receiverData) {
                $amountTotal = 0;
                foreach ($receiverData as $data) {
                    $amountTotal = $amountTotal + $data['amount'];
                }
                return $amountTotal;
    }
    /*
     * Generate key
     *
     * @return string $char key
     */

    public function generateCharacter() {
        $possible = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        return substr($possible, mt_rand(0, strlen($possible) - 1), 1);
    }

    /*
     * Generate tracking id
     * 
     * @return string $GUID tracking id
     */

    public function generateTrackingID() {
        $GUID = $this->generateCharacter() . $this->generateCharacter() . $this->generateCharacter();
        $GUID .=$this->generateCharacter() . $this->generateCharacter() . $this->generateCharacter();
        return $GUID.$this->generateCharacter() . $this->generateCharacter() . $this->generateCharacter();
    }

    /*
     * Redirect to paypal.com here    
     */

    public function RedirectToPayPal($cmd) {
        $mode = Mage::helper('paypaladaptive')->getPaymentMode();
        $payPalURL = "";
        if ($mode == 1) {
            $payPalURL = "https://www.sandbox.paypal.com/webscr?" . $cmd;
        } else {
            $payPalURL = "https://www.paypal.com/webscr?" . $cmd;
        }
        Mage::app()->getResponse()->setRedirect($payPalURL);
        return FALSE;
    }

    /**
    * Set paykey , tracking id and order id to session
    * @param string $trackingId tracking Id
    * @param string $dataPayKey PayPal pay key
    * @param string $invoiceId invoice Id
    * @param string $paymentMethod payment method
    */
    public function setSessionData($trackingId,$payKey,$invoiceId,$paymentMethod)
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setPaypalAdaptiveTrackingId($trackingId);
        $session->setPaypalAdaptivePayKey($payKey);
        $session->setPaypalAdaptiveRealOrderId($invoiceId);
        $session->setPaypalAdaptivePaymentMethod($paymentMethod);
    }

    /**
    * Getting static configuration data and data from backend 
    **/

    public function getConfigurations()
    {
        return  array(
        "actionType" => "PAY",
        "cancelUrl" => Mage::getUrl('paypaladaptive/adaptive/cancel', array('_secure' => true)),
        "returnUrl" => Mage::getUrl('paypaladaptive/adaptive/return', array('_secure' => true)),
        "ipnNotificationUrl" => Mage::getUrl('paypaladaptive/adaptive/ipnnotification', array('_secure' => true)),
        "currencyCode" => Mage::app()->getStore()->getCurrentCurrencyCode(),
        "senderEmail" => "",
        "reverseAllParallelPaymentsOnError" => "",
        "memo" => "",
        "pin" => "",
        "preapprovalKey" => "",
        "feesPayer" => Mage::helper('paypaladaptive')->getFeePayer(),
        "paymentMethod" => Mage::helper('paypaladaptive')->getPaymentMethod(),
        "enabledMarplace" => Mage::helper('paypaladaptive')->getModuleInstalledStatus('Apptha_Marketplace'),
        "enabledAirhotels" => Mage::helper('paypaladaptive')->getModuleInstalledStatus('Apptha_Airhotels'),
        );
    }

     /**
     * Save payment details to paypaladaptivedetails table
     *
     * @param string $dataSellerId receiver id
     * @param decimal $dataAmount receiver amount
     * @param decimal $dataCommissionFee receiver commission
     * @param string $dataCurrencyCode currency code
     * @param string $dataPayKey PayPal pay key
     * @param string $dataGroupType receiver group type
     * @param string $dataTrackingId PayPal tracking id
     * @param decimal $grandTotal Order grand total
     * @param string $paymentMethod payment method
     * @return array set all params to an array with corresponding key to return
     */

    public function constructArrayToSave($dataSellerId, $dataAmount, $dataCommissionFee, $dataPayKey, $dataGroupType, $shippingFee, $discountAmount)
        {
            $session = Mage::getSingleton('checkout/session');
            $invoiceId = $session->getLastRealOrderId();
            $trackingId = $session->getPaypalAdaptiveTrackingId();
            $orderId = Mage::getModel('sales/order')->loadByIncrementId($invoiceId)->getId();
            $configuration = $this->getConfigurations();
            $grandTotal = round(Mage::helper('paypaladaptive')->getGrandTotal(), 2);

        return array(
                'orderId' => $orderId, 
                'invoiceId' => $invoiceId, 
                'dataSellerId' => $dataSellerId, 
                'dataAmount' => $dataAmount, 
                'dataCommissionFee' => $dataCommissionFee, 
                'dataCurrencyCode' => $configuration['currencyCode'],
                'dataPayKey' => $dataPayKey, 
                'dataGroupType' => $dataGroupType, 
                'dataTrackingId' => $trackingId,
                'grandTotal' => $grandTotal, 
                'paymentMethod' => $configuration['paymentMethod'],
                'shippingFee' => $shippingFee,
                'discountAmount' => $discountAmount
                );
        }

     /**
     * create invoice and update the customer
     *
     * @param array $item orderItems
     * @param array $order currentOrder 
     */
    public function createInvoiceAndUpdate ($items, $order)
    {
        $itemCount = 0;
        $sellerProduct = array();
        foreach ( $items as $item ) {
           $products = Mage::helper ( 'marketplace/marketplace' )->getProductInfo ( $item->getProductId () );
           $orderEmailData [$itemCount] ['seller_id'] = $products->getSellerId ();
           $orderEmailData [$itemCount] ['product_qty'] = $item->getQtyOrdered ();
           $orderEmailData [$itemCount] ['product_id'] = $item->getProductId ();
           $sellerProduct[$products->getSellerId ()][$item->getProductId ()]    = $item->getQtyOrdered ();
           $itemCount = $itemCount + 1;
        }
        $sellerIds = array ();
        foreach ( $orderEmailData as $data ) {
           if (! in_array ( $data ['seller_id'], $sellerIds )) {
              $sellerIds [] = $data ['seller_id'];
           }
        }
        foreach ( $sellerIds as $id ) {
           $itemsarray = $itemsArr = array ();
            foreach ( $order->getAllItems () as $item ) {
               $productsCol = Mage::helper ( 'marketplace/marketplace' )->getProductInfo ( $item->getProductId () );
               $itemId = $item->getItemId ();
               if($productsCol->getSellerId () == $id){
                  $itemsarray [$itemId] = $sellerProduct[$id][$item->getProductId ()];
                  $itemsArr [] = $itemId;
               }else{
                  $itemsarray [$itemId] = 0;
               }
           }
           /**
            * Generate invoice for shippment.
            */
           Mage::getModel ( 'sales/order_invoice_api' )->create ( $order->getIncrementId (), $itemsarray, '', 1, 1 );
           Mage::getModel ( 'marketplace/order' )->updateSellerOrderItemsBasedOnSellerItems ( $itemsArr, $order->getEntityId(), 1 );
           }
    }

     /**
     * UpdateTrasation Id and status for IPN response
     *
     * @param string $payKey pay key
     * @param string $dataTrackingId PayPal tracking id 
     * @param array $resArray Payment response from Paypal 
     */
    public function updateTransactionIdAndStatus($payKey, $trackingId, $resArray)
    {
        for ($inc = 0; $inc <= 5; $inc++) {
            $senderEmail = $resArray["senderEmail"];
            $receiverEmail = $resArray["paymentInfoList"]["paymentInfo"][$inc]["receiver"]["email"];
            $receiverInvoiceId = $resArray["paymentInfoList"]["paymentInfo"][$inc]["receiver"]["invoiceId"];
            /**
            * Skip loop to avoid processing
            **/
            if($receiverInvoiceId == ''){
               continue;
            }
            /**
            * Process IPN response to update status and records
            **/
            if (isset($resArray["paymentInfoList"]["paymentInfo"][$inc]["transactionId"])) {
                $receiverTransactionId = $resArray["paymentInfoList"]["paymentInfo"][$inc]["transactionId"];
            } else {
                $receiverTransactionId = '';
            }
            if (isset($resArray["paymentInfoList"]["paymentInfo"][$inc]["transactionStatus"])) {
                $receiverTransactionStatus = $resArray["paymentInfoList"]["paymentInfo"][$inc]["transactionStatus"];
            } else {
                $receiverTransactionStatus = 'Pending';
            }
            /*
            * Updating transaction id and status
            */
            Mage::getModel('paypaladaptive/save')->update($payKey, $receiverTransactionId, $receiverTransactionStatus, $senderEmail, $receiverEmail, $receiverInvoiceId);
        }
    }
    
}