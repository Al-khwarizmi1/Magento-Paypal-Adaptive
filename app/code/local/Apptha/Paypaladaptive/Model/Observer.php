<?php

/**
 * In this class contains the following functions refund, execute payment and adaptive product tab 
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 02,2014
 * @Modified By     Varun S
 * @Modified Date   November 24,2017
 *
 * */
class Apptha_Paypaladaptive_Model_Observer {
    /*
     * Payment refund process (Creditmemo)
     * 
     * @param object refund collection
     */

    public function adaptiveRefundAction(Varien_Event_Observer $observer) {

        $creditmemo = $observer->getEvent()->getCreditmemo();
        $orderId = $creditmemo->getOrderId();

        $order = Mage::getModel('sales/order')->load($creditmemo->getOrderId());
        $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();

        $incrementId = $order->getIncrementId();
        $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                ->addFieldToFilter('seller_invoice_id', $incrementId);
        
        /*
         * Refund process status
         */
        $offlineRefundStatus = Mage::helper('paypaladaptive')->getRefundStatus();

        /*
         * Check whether payment created by using Apptha PayPal Adaptive method or not for refund
         */

        if ($paymentMethodCode != 'paypaladaptive' && count($collections) > 1 || $offlineRefundStatus != 1) {
            return;
        }

        $firstRow = Mage::helper('paypaladaptive')->getFirstRowData($collections);

        $adminEmail = $firstRow['owner_paypal_id'];
        $payKey = $firstRow['pay_key'];
        $trackingId = $firstRow['tracking_id'];
        $transactionId = $firstRow['seller_transaction_id'];
        $currencyCode = $firstRow['currency_code'];


        $items = $order->getAllItems();
        $newItems = $creditmemo->getAllItems();

        $sellerData = Mage::getModel('paypaladaptive/save')->sellerDataForRefund($items, $incrementId, 1);
        $newSellerData = Mage::getModel('paypaladaptive/save')->sellerDataForRefund($newItems, $incrementId, 0);

        $receiverAmountArray = $receiverEmailArray = array();
        $adminTotalCommission = 0;
        foreach ($sellerData as $data) {

            $sellerId = $data['seller_id'];
            $receiverAmount = $adminCommission = 0;
            if ($data['amount'] == $newSellerData[$sellerId]['amount']) {
                $receiverAmount = $data['amount'];
                $adminCommission = $data['commission_fee'];
            } else {
                if (!empty($newSellerData[$sellerId]['amount'])) {
                    $receiverAmount = $data['amount'] - $newSellerData[$sellerId]['amount'];
                    $adminCommission = $data['commission_fee'] - $newSellerData[$sellerId]['commission_fee'];
                }
            }
            if ($receiverAmount > 0) {
                /*
                 * get receiver PayPal id
                 */
                $receiverPaypalId = Mage::getModel('paypaladaptive/save')->sellerPaypalIdForRefund($incrementId, $data['seller_id']);
                $receiverAmountArray[] = round($receiverAmount, 2);
                $receiverEmailArray[] = $receiverPaypalId;
                $adminTotalCommission = round($adminTotalCommission + $adminCommission, 2);
            }
        }
        /*
         * Get admin PayPal id and Amount
         */
        $subTotal = array_sum($receiverAmountArray) + $adminTotalCommission;
        $receiverEmailArray[] = $adminEmail;
        $receiverAmountArray[] = round($adminTotalCommission + $creditmemo->getGrandTotal() - $subTotal, 2);

        $resArray = Mage::getModel('paypaladaptive/apicall')->CallRefund($payKey, $transactionId, $trackingId, $receiverEmailArray, $receiverAmountArray, $currencyCode);

        $ack = strtoupper($resArray["responseEnvelope"]["ack"]);

        if ($ack == "SUCCESS") {

            /*
             * Save refund details
             */
            for ($inc = 0; $inc <= 5; $inc++) {

                if (!empty($resArray["refundInfoList"]["refundInfo"][$inc]["encryptedRefundTransactionId"])) {

                    $encryptedRefundTransactionId = $resArray["refundInfoList"]["refundInfo"][$inc]["encryptedRefundTransactionId"];
                    $refundStatus = $resArray["refundInfoList"]["refundInfo"][$inc]["refundStatus"];
                    $refundNetAmount = $resArray["refundInfoList"]["refundInfo"][$inc]["refundNetAmount"];
                    $refundFeeAmount = $resArray["refundInfoList"]["refundInfo"][$inc]["refundFeeAmount"];
                    $refundGrossAmount = $resArray["refundInfoList"]["refundInfo"][$inc]["refundGrossAmount"];
                    $refundTransactionStatus = $resArray["refundInfoList"]["refundInfo"][$inc]["refundTransactionStatus"];
                    $receiverEmail = $resArray["refundInfoList"]["refundInfo"][$inc]["receiver"]["email"];
                    $currencyCode = $resArray["currencyCode"];
                    $paramsToSave = array(
                        'orderId'  =>  $orderId, 
                        'incrementId' =>  $incrementId, 
                        'payKey' =>  $payKey, 
                        'trackingId' =>  $trackingId, 
                        'transactionId' =>  $transactionId, 
                        'encryptedRefundTransactionId' =>  $encryptedRefundTransactionId, 
                        'refundStatus' =>  $refundStatus, 
                        'refundNetAmount' =>  $refundNetAmount, 
                        'refundFeeAmount' =>  $refundFeeAmount, 
                        'refundGrossAmount' =>  $refundGrossAmount, 
                        'refundTransactionStatus' =>  $refundTransactionStatus, 
                        'receiverEmail' =>  $receiverEmail, 
                        'currencyCode' => $currencyCode ,
                    );
                    Mage::getModel('paypaladaptive/save')->refund($paramsToSave);
                    Mage::getModel('paypaladaptive/save')->changePaymentStatus($incrementId, $payKey, $trackingId, $receiverEmail);
                } else {
                    if ($refundStatus != 'REFUNDED') {
                        $url = Mage::helper('adminhtml')
                                ->getUrl('adminhtml/sales_order_creditmemo/new', array('order_id' => $creditmemo->getOrderId()));
                        Mage::app()->getFrontController()->getResponse()->setRedirect($url);
                        Mage::app()->getResponse()->sendResponse();
                        Mage::throwException(Mage::helper('paypaladaptive')->__('API connection failed : ') . $resArray["refundInfoList"]["refundInfo"][$inc]["refundStatus"]);
                        Mage::log('Response'.$resArray, null, 'paypal.log');                                           
                    }
                }
            }
        } else {
            /*
             * Refund process failed action
             */
            $url = Mage::helper('adminhtml')
                    ->getUrl('adminhtml/sales_order_creditmemo/new', array('order_id' => $creditmemo->getOrderId()));
            Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            Mage::app()->getResponse()->sendResponse();
            Mage::throwException(Mage::helper('paypaladaptive')->__('API connection failed : ') . $resArray["error"][0]["message"]);
        }
    }

    /*
     * Save product commission data
     * 
     * @param object product edit collection
     */

    public function saveProductTabData(Varien_Event_Observer $observer) {

        /*
         * Check whether Marketplace/Airhotels enable or not  
         */
        $enabledMarplace = (int) Mage::helper('paypaladaptive')->getModuleInstalledStatus('Apptha_Marketplace');
        $enabledAirhotels = (int) Mage::helper('paypaladaptive')->getModuleInstalledStatus('Apptha_Airhotels');
        if ($enabledMarplace != 1 && $enabledAirhotels != 1) {

            $product = $observer->getEvent()->getProduct();
            try {
                $productId = $product->getId();
                $productPaypalId = Mage::app()->getRequest()->getPost('product_paypal_id');
                $shareMode = Mage::app()->getRequest()->getPost('share_mode');
                $shareValue = Mage::app()->getRequest()->getPost('share_value');
                $isEnable = Mage::app()->getRequest()->getPost('paypal_adaptive_activate');

                $productData = Mage::getModel('paypaladaptive/productdetails')->getCollection()
                        ->addFieldToFilter('product_id', $productId);
                $firstRow = Mage::helper('paypaladaptive')->getFirstRowData($productData);

                if (!empty($firstRow['product_id']) && $firstRow['product_id'] == $productId) {

                    $table_name = Mage::getSingleton('core/resource')->getTableName('paypaladaptiveproductdetails');
                    $connection = Mage::getSingleton('core/resource')
                            ->getConnection('core_write');
                    $connection->beginTransaction();
                    $fields = array();
                    if (!empty($productPaypalId)) {
                        $fields['product_paypal_id'] = $productPaypalId;
                    }
                    if (!empty($shareMode)) {
                        $fields['share_mode'] = $shareMode;
                    }
                    if (!empty($shareValue)) {
                        $fields['share_value'] = $shareValue;
                    }
                    $fields['is_enable'] = $isEnable;
                    $where[] = $connection->quoteInto('product_id = ?', $productId);
                    $connection->update($table_name, $fields, $where);
                    $connection->commit();
                } else {
                    /*
                     * Assigning seller payment data
                     */
                    $collections = Mage::getModel('paypaladaptive/productdetails');
                    $collections->setProductId($productId);
                    $collections->setProductPaypalId($productPaypalId);
                    $collections->setShareMode($shareMode);
                    $collections->setShareValue($shareValue);
                    $collections->setIsEnable($isEnable);
                    $collections->save();
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
    }

    /*
     * Add custom product tabs
     * 
     * @param object product edit collection
     */

    public function customProductTabs(Varien_Event_Observer $observer) {

        /*
         * Check whether Marketplace/Airhotels enable or not  
         */
        $enabledMarplace = (int) Mage::helper('paypaladaptive')->getModuleInstalledStatus('Apptha_Marketplace');
        $enabledAirhotels = (int) Mage::helper('paypaladaptive')->getModuleInstalledStatus('Apptha_Airhotels');
        if ($enabledMarplace != 1 && $enabledAirhotels != 1) {
            $block = $observer->getEvent()->getBlock();
            if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs && Mage::app()->getRequest()->getActionName() == 'edit' || Mage::app()->getRequest()->getParam('type')) {
                    $block->addTab('adaptivepaypal', array(
                        'label' => Mage::helper('paypaladaptive')->__('Apptha Paypal Adaptive Options'),
                        'content' => $block->getLayout()->createBlock('adminhtml/template', 'adaptivepaypal-custom-tabs', array('template' => 'paypaladaptive/tabs.phtml'))->toHtml(),
                    ));
            }
        }
    }

}
