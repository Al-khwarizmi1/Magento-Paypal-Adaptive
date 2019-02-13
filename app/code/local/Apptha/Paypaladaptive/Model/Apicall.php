<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_PayPaladaptive
 * @version     0.1.2
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */

/**
 * In this class contains the PayPal Api call functions
 */

class Apptha_Paypaladaptive_Model_Apicall {
    /**
     * Pay call to PayPal 
     * 
     * @param string $methodName call method
     * @param string $JSONData JSONRequest
     * 
     * @return array PayPal response
     */

    function hashCall($methodName, $JSONData) {
        /**
         * Set the curl parameters     
         */
        $ApiUserName = Mage::helper('paypaladaptive')->getApiUserName();
        $ApiPassword = Mage::helper('paypaladaptive')->getApiPassword();
        $ApiSignature = Mage::helper('paypaladaptive')->getApiSignature();
        $ApiAppID = Mage::helper('paypaladaptive')->getAppID();
        $mode = Mage::helper('paypaladaptive')->getPaymentMode();

        if ($mode == 1) {
            $ApiEndpoint = "https://svcs.sandbox.paypal.com/AdaptivePayments";
            $ApiEndpoint .= "/" . $methodName;
            $ApiAppID = "APP-80W284485P519543T";
        } else {
            $ApiEndpoint = "https://svcs.paypal.com/AdaptivePayments";
            $ApiEndpoint .= "/" . $methodName;
        }

            /**
            * Set the header parameters
            */
        $header = array(
            "X-PAYPAL-SECURITY-USERID: ".$ApiUserName,
            "X-PAYPAL-SECURITY-PASSWORD: ".$ApiPassword,
            "X-PAYPAL-SECURITY-SIGNATURE: ".$ApiSignature,
            "X-PAYPAL-REQUEST-DATA-FORMAT: JSON",
            "X-PAYPAL-RESPONSE-DATA-FORMAT: JSON",
            "X-PAYPAL-SERVICE-VERSION: 1.3.0",
            "X-PAYPAL-APPLICATION-ID: ".$ApiAppID,
            );

            /**
            * Set the default parameters
            */
            $defaults = array(
                'requestEnvelope' => array(
                  'errorLanguage' => 'en_US',
                ),
                'detailLevel' => 'ReturnAll',
            );
            $JSONData = array_merge($defaults, $JSONData);

            try {

            $curl = curl_init($ApiEndpoint);
            /**
             * Set the curl parameters
             */
             $options = array(
                CURLOPT_HTTPHEADER      => $header,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
                /** CURLOPT_SSL_VERIFYPEER  => true,  if SSL enabled */
                /** CURLOPT_SSL_VERIFYHOST  => 2, // if SSL enabled  */
                CURLOPT_POSTFIELDS  => json_encode($JSONData),
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_VERBOSE        => 1,
                /** CURLOPT_CAINFO         => Mage::getBaseDir('lib') . '/paypaladaptive/cacert.pem',  */
                );

            curl_setopt_array($curl, $options);

            /**
            * Execute curl operation
            **/

            $rawResponse = curl_exec($curl);

            /**
            * Get response from curl operation and decode
            **/

            $response = json_decode($rawResponse, true);

            /**
            * Terminate the curl operation
            **/

            curl_close($curl);
            /**
             * Return Response data
             */
            if (strtoupper($response["responseEnvelope"]["ack"]) != "SUCCESS" ) {
                Mage::log($JSONData, null, 'paypal.log');
            }

            return $response;
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            Mage::log('Request', null, 'paypal.log');
            Mage::log($JSONData, null, 'paypal.log');
            Mage::log('Response', null, 'paypal.log');
            Mage::log($response, null, 'paypal.log');
            return;
        }
    }

    /**
     * Prepares the parameters for the PaymentDetails API Call    
     * 
     * @param string $payKey PayPal pay key
     * @param string $transactionId PayPal transaction id
     * @param string $trackingId Paypal tracking id
     * 
     * @return array PayPal response
     */

    public function CallPaymentDetails($payKey, $transactionId, $trackingId) {

        /**
         * Collection the information to make the PaymentDetails call        
         */
         $JSONData = array();

         if ($payKey != "" ) {
             $JSONData["payKey"] = $payKey;
         } elseif ($transactionId != "") {
             $JSONData["transactionId"] = $transactionId;
         } elseif ($trackingId != "" ) {
             $JSONData["trackingId"] = $trackingId;
         }
        /**
         * Make the PaymentDetails call to PayPal
         */
         return $this->hashCall("PaymentDetails", $JSONData);
    }

    /**
     * Collect the information to make the Pay call
     * 
     * @param array $configurationData actionType currencyCode feesPayer IPNUrl cancelUrl returnUrl 
     * @param array $receiverEmailArray receiver email 
     * @param array $receiverAmountArray receiver amount
     * @param array $receiverPrimaryArray receiver primary value
     * @param array $receiverInvoiceIdArray receiver invoice
     * @param string $ipnNotificationUrl url
     * @param string $memo memo
     * @param string $pin pin
     * @param string $preapprovalKey preapproval key
     * @param string $reverseAllParallelPaymentsOnError error type
     * @param string $senderEmail ender email
     * @param string $trackingId PayPayl tracking id
     * @return array PayPal response 
     */

    public function CallPay($configurationData, $trackingId, $receiverEmailArray, $receiverAmountArray, $receiverPrimaryArray, $receiverInvoiceIdArray) {
        /**
         * Construct data about the transaction
        **/

        $count = sizeof($receiverEmailArray);
        $receiverData = array();
        for ($index=0 ; $index < $count ; $index++ ) {
            $receiver = array(
                'amount'  => $receiverAmountArray[$index],
                'email'  => $receiverEmailArray[$index],
                'invoiceId'  => $receiverInvoiceIdArray[$index],
                'primary' => $receiverPrimaryArray[$index],
            );
            array_push($receiverData, $receiver);
        }

        /**
         * Default fields for pay call
         */

         $reqArray = array(
                        'actionType'  => $configurationData['actionType'],
                        'currencyCode'  => $configurationData['currencyCode'],
                        'feesPayer'  => $configurationData['feesPayer'],
                        'ipnNotificationUrl' => $configurationData['ipnNotificationUrl'],
                        'cancelUrl' => $configurationData['cancelUrl'],
                        'returnUrl' => $configurationData['returnUrl'],
                        'trackingId'=> $trackingId,
                        'receiverList' => array(
                          'receiver' => $receiverData
                        ),
                    );
        /**
         * Make the Pay call to PayPal 
         */        
         return $this->hashCall("Pay", $reqArray);
    }

    /**
     * Prepares the parameters for the Refund API Call   
     * 
     * @param string $payKey PayPal pay key
     * @param string $transactionId transaction id
     * @param array $receiverEmailArray receiver email 
     * @param array $receiverAmountArray receiver amount
     * @param array $currencyCode currency code
     * @return array PayPal response 
     */

    function CallRefund($payKey, $transactionId, $trackingId, $receiverEmailArray, $receiverAmountArray, $currencyCode) {

        /**
         * Constructing of Paypal Request with required options  
        */

        $reqArray = array(
            'actionType'  => 'Refund',
            'currencyCode'  => $currencyCode,
        );

        if ( $payKey != "" ) {
            $reqArray['payKey'] = $payKey;
        } elseif ( $trackingId != "" )  {
            $reqArray['trackingId'] = $trackingId;
        } elseif ( $transactionId  != "" ) {
            $reqArray['transactionId'] = $transactionId;
        }

        /**
         * Initialise parameters for receiver data
         */

        $receiverData = array();
        $mailCount = sizeof($receiverEmailArray);
        $amountCount = sizeof($receiverAmountArray);
        
        /**
         * Constructing of receiver amount or array if available 
        */

        if($mailCount > 0 || $amountCount > 0){
            $count = $mailCount;
            if($count == 0){
                $count = $amountCount;            
            }

        for ($index=0 ; $index < $count ; $index++ ) {
            if($amountCount > 0){            
                $receiver['amount']  = $receiverAmountArray[$index];
            }
            if($mailCount > 0){
                $receiver['email']  = $receiverEmailArray[$index];
            }
            array_push($receiverData, $receiver);
            }

            $receiverList = array('receiverList' => array(
                'receiver' => $receiverData
                )
            );
            $reqArray = array_merge($reqArray, $receiverList);
        }

        
        /**
         * Make the Refund call to PayPal 
         */         
         return $this->hashCall("Refund", $reqArray);
    }
}