<?php

/**
 * In this class contains all the database manipulation functionality.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   November 24,2017
 * @Modified By     Varun S
 * @Modified Date   November 24,2017
 *
 * */
class Apptha_Paypaladaptive_Model_Save {

    /**
     * Save payment details to paypaladaptivedetails table
     *
     * @param array $params array with orderid invoiceid receiverid receiveramount receivercommission currencycode paykey trackingid grandtotal 
     * @param string $paymentMethod payment method
     */
    public function saveOrderData($params) {

        /*
         * If checking whether seller or owner for store data 
         */
        try {
            $paymentCollection = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                    ->addFieldToFilter('seller_invoice_id', $params['invoiceId'])
                    ->addFieldToFilter('seller_id', $params['dataSellerId']);

            if (count($paymentCollection) >= 1) {
                try {
                    $table_name = Mage::getSingleton('core/resource')->getTableName('paypaladaptivedetails');
                    $connection = Mage::getSingleton('core/resource')
                            ->getConnection('core_write');
                    $connection->beginTransaction();
                    $where[] = $connection->quoteInto('seller_invoice_id = ?', $params['invoiceId']);
                    $where[] = $connection->quoteInto('seller_id = ?', $params['dataSellerId']);
                    $connection->delete($table_name, $where);
                    $connection->commit();
                } catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    return;
                }
            }

            /*
             * Assigning seller payment data 
             */
            $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails');
            $collections->setSellerInvoiceId($params['invoiceId']);
            $collections->setOrderId($params['orderId']);
            $collections->setSellerId($params['dataSellerId']);
            $collections->setSellerAmount($params['dataAmount']);
            $collections->setCommissionAmount($params['dataCommissionFee']);
            $collections->setShippingAmount($params['shippingFee']);
            $collections->setCouponDiscountAmount($params['discountAmount']);
            $collections->setGrandTotal($params['grandTotal']);
            $collections->setCurrencyCode($params['dataCurrencyCode']);
            $collections->setOwnerPaypalId(Mage::helper('paypaladaptive')->getAdminPaypalId());
            $collections->setPayKey($params['dataPayKey']);
            $collections->setGroupType($params['dataGroupType']);
            $collections->setTrackingId($params['dataTrackingId']);
            $collections->setTransactionStatus('Pending');
            $collections->setPaymentMethod($params['paymentMethod']);
            $collections->save();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            return;
        }
    }

    /**
     * Update transaction id and status in paypaladaptivedetails table
     *
     * @param string $dataPayKey PayPal pay key
     * @param string $dataTrackingId PayPal tracking id
     * @param string $receiverTransactionId receiver transaction id
     * @param string $receiverTransactionStatus receiver transaction status
     * @param string $senderEmail sender PayPal mail id
     * @param string $receiverEmail receiver PayPal mail id
     * @param string $receiverInvoiceId receiver receiver invoice id  
     */
    public function update($payKey, $receiverTransactionId, $receiverTransactionStatus, $senderEmail, $receiverEmail, $receiverInvoiceId) {

        $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                ->addFieldToFilter('pay_key', $payKey)
                ->addFieldToFilter('seller_id', $receiverEmail)
                ->addFieldToFilter('seller_invoice_id', $receiverInvoiceId);
        if (count($collections) >= 1) {
            try {
                /*
                 * Change transaction status first letter capital 
                 */
                $receiverTransactionStatus = str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($receiverTransactionStatus))));

                $table_name = Mage::getSingleton('core/resource')->getTableName('paypaladaptivedetails');
                $connection = Mage::getSingleton('core/resource')
                        ->getConnection('core_write');
                $connection->beginTransaction();
                $fields = array();
                $fields['seller_transaction_id'] = $receiverTransactionId;
                $fields['buyer_paypal_mail'] = $senderEmail;
                $fields['transaction_status'] = $receiverTransactionStatus;
                $where[] = $connection->quoteInto('pay_key = ?', $payKey);
                $where[] = $connection->quoteInto('seller_invoice_id = ?', $receiverInvoiceId);
                $where[] = $connection->quoteInto('seller_id = ?', $receiverEmail);
                $connection->update($table_name, $fields, $where);
                $connection->commit();
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
                return;
            }
        }
    }

    /**
     * Refund payment action
     *
     * @param array $params invoiceid paykey trackingid transactinid encryptedrefundtransactionid refundstatus refundnetamount refundfeeamount refundgrossamount refundtransactionstatus receiveremail currencycode
     */
    public function refund($params) {

        try {
            $payDetails = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                    ->addFieldToFilter('seller_invoice_id', $params['incrementId'])
                    ->addFieldToFilter('pay_key', $params['payKey'])
                    ->addFieldToFilter('tracking_id', $params['trackingId'])
                    ->addFieldToFilter('seller_id', $params['receiverEmail']);

            $firstRow = Mage::helper('paypaladaptive')->getFirstRowData($payDetails);

            if (!empty($firstRow['buyer_paypal_mail'])) {
                $buyerPaypalMail = $firstRow['buyer_paypal_mail'];
            } else {
                $buyerPaypalMail = '';
            }
            /*
             * Change transaction status first letter capital 
             */
            $refundStatus = str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($params['refundStatus']))));

            /*
             * Assigning payment data 
             */
            $collections = Mage::getModel('paypaladaptive/refunddetails');
            $collections->setIncrementId($params['incrementId']);
            $collections->setOrderId($params['orderId']);
            $collections->setSellerPaypalId($params['receiverEmail']);
            $collections->setPayKey($params['payKey']);
            $collections->setTrackingId($params['trackingId']);
            $collections->setTransactionId($params['transactionId']);
            $collections->setEncryptedRefundTransactionId($params['encryptedRefundTransactionId']);
            $collections->setRefundNetAmount($params['refundNetAmount']);
            $collections->setRefundFeeAmount($params['refundFeeAmount']);
            $collections->setRefundGrossAmount($params['refundGrossAmount']);
            $collections->setbuyerPaypalMail($params['buyerPaypalMail']);
            $collections->setRefundTransactionStatus($params['refundTransactionStatus']);
            $collections->setRefundStatus($refundStatus);
            $collections->setCurrencyCode($params['currencyCode']);

            $collections->save();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            return;
        }
    }

    /*
     * Collect seller PayPal id for refund process
     * 
     * @param int $incrementId increment id
     * @param string $sellerId seller id
     * 
     * @return string $sellerPaypalId seller PayPal id
     */

    public function sellerPaypalIdForRefund($incrementId, $sellerId) {

        $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                ->addFieldToFilter('seller_invoice_id', $incrementId)
                ->addFieldToFilter('seller_id', $sellerId);

        $sellerPaypalId = '';
        $firstRow = Mage::helper('paypaladaptive')->getFirstRowData($collections);
        if (!empty($firstRow)) {
            $sellerPaypalId = $firstRow['seller_id'];
        }

        return $sellerPaypalId;
    }

    /*
     * Collect seller refund data
     * 
     * @param array $items items
     * @param string $incrementId increment id
     * @param int $flag flag
     * 
     * @return array $sellerData seller data
     */

    public function sellerDataForRefund($items, $incrementId, $flag) {

        $sellerData = array();
        /*
         * Preparing seller share 
         */
        foreach ($items as $item) {

            $sellerAmount = 0;
            $productId = $item->getProductId();

            $commissionData = Mage::getModel('paypaladaptive/commissiondetails')->getCollection()
                    ->addFieldToFilter('product_id', $productId)
                    ->addFieldToFilter('increment_id', $incrementId);
            $firstRow = Mage::helper('paypaladaptive')->getFirstRowData($commissionData);

            if (!empty($firstRow['seller_id'])) {
                $commissionValue = $firstRow['commission_value'];
                $commissionMode = $firstRow['commission_mode'];
                $sellerId = $firstRow['seller_id'];

                if ($flag == 1) {
                    $productAmount = $item->getPrice() * $item->getQtyInvoiced();
                } else {
                    $productAmount = $item->getPrice() * $item->getQty();
                }

                if ($commissionMode == 'percent') {
                    $productCommission = $productAmount * ($commissionValue / 100);
                    $sellerAmount = $productAmount - $productCommission;
                } else {
                    $productCommission = $commissionValue;
                    $sellerAmount = $productAmount - $commissionValue;
                }
                /*
                 * Calculating seller share individually
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
    }

    /*
     * Save commission details to paypaladaptivecommissiondetails table
     *    
     * @param string $incrementId increment id
     * @param int $productId product id
     * @param decimal $commissionValue commission value
     * @param string $commissionMode commission mode
     * @param string $sellerId seller PayPal id
     */

    public function saveCommissionData($incrementId, $productId, $commissionValue, $commissionMode, $sellerId) {

        try {
            $commissionData = Mage::getModel('paypaladaptive/commissiondetails')->getCollection()
                    ->addFieldToFilter('product_id', $productId)
                    ->addFieldToFilter('increment_id', $incrementId);
            $firstRow = Mage::helper('paypaladaptive')->getFirstRowData($commissionData);

            if (!empty($firstRow['product_id']) && $firstRow['product_id'] == $productId) {

                $table_name = Mage::getSingleton('core/resource')->getTableName('paypaladaptivecommissiondetails');
                $connection = Mage::getSingleton('core/resource')
                        ->getConnection('core_write');
                $connection->beginTransaction();
                $fields = array();
                $fields['commission_mode'] = $commissionMode;
                $fields['commission_value'] = $commissionValue;
                $fields['seller_id'] = $sellerId;
                $where[] = $connection->quoteInto('product_id = ?', $productId);
                $where[] = $connection->quoteInto('increment_id = ?', $incrementId);
                $connection->update($table_name, $fields, $where);
                $connection->commit();
            } else {
                /*
                 * Assigning seller payment data
                 */
                $collections = Mage::getModel('paypaladaptive/commissiondetails');
                $collections->setProductId($productId);
                $collections->setIncrementId($incrementId);
                $collections->setCommissionMode($commissionMode);
                $collections->setCommissionValue($commissionValue);
                $collections->setSellerId($sellerId);
                $collections->save();
            }
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            return;
        }
    }

    /**
     * Update payment status as refunded
     *
     * @param int $incrementId increment id
     * @param string $payKey pay key
     * @param string $dataTrackingId PayPal tracking id
     * @param string $receiverEmail receiver PayPal id
     */
    public function changePaymentStatus($incrementId, $payKey, $trackingId, $receiverEmail) {

        $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                ->addFieldToFilter('pay_key', $payKey)
                ->addFieldToFilter('tracking_id', $trackingId)
                ->addFieldToFilter('seller_id', $receiverEmail)
                ->addFieldToFilter('seller_invoice_id', $incrementId);

        if (count($collections) >= 1) {
            try {
                $table_name = Mage::getSingleton('core/resource')->getTableName('paypaladaptivedetails');
                $connection = Mage::getSingleton('core/resource')
                        ->getConnection('core_write');
                $connection->beginTransaction();
                $fields = array();
                $fields['transaction_status'] = 'Refunded';
                $where[] = $connection->quoteInto('pay_key = ?', $payKey);
                $where[] = $connection->quoteInto('tracking_id = ?', $trackingId);
                $where[] = $connection->quoteInto('seller_invoice_id = ?', $incrementId);
                $where[] = $connection->quoteInto('seller_id = ?', $receiverEmail);
                $connection->update($table_name, $fields, $where);
                $connection->commit();
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                return;
            }
        }
    }

    /**
     * Update payment status as canceled
     *
     * @param int $paypalAdaptive invoice id
     * @param string $payKey pay key
     * @param string $dataTrackingId PayPal tracking id 
     */
    public function cancelPayment($paypalAdaptive, $payKey, $trackingId) {

        $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection()
                ->addFieldToFilter('pay_key', $payKey)
                ->addFieldToFilter('tracking_id', $trackingId)
                ->addFieldToFilter('seller_invoice_id', $paypalAdaptive);

        if (count($collections) >= 1) {
            try {
                $table_name = Mage::getSingleton('core/resource')->getTableName('paypaladaptivedetails');
                $connection = Mage::getSingleton('core/resource')
                        ->getConnection('core_write');
                $connection->beginTransaction();
                $fields = array();
                $fields['transaction_status'] = 'Canceled';
                $where[] = $connection->quoteInto('pay_key = ?', $payKey);
                $where[] = $connection->quoteInto('tracking_id = ?', $trackingId);
                $where[] = $connection->quoteInto('seller_invoice_id = ?', $paypalAdaptive);
                $connection->update($table_name, $fields, $where);
                $connection->commit();
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
                return;
            }
        }
    }
}