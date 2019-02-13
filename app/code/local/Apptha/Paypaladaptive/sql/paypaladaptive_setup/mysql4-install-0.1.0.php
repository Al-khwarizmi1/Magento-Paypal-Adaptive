<?php

/**
*  Create New tables for Apptha Paypal Adaptive version 0.1.0
*
* @package         Apptha PayPal Adaptive
* @version         0.1.1
* @since           Magento 1.5
* @author          Apptha Team
* @copyright       Copyright (C) 2014 Powered by Apptha
* @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @Creation Date   January 10,2014
* @Modified By     Ramkumar M
* @Modified Date   January 10,2014
*
* */

$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('paypaladaptivedetails')};
CREATE TABLE {$this->getTable('paypaladaptivedetails')} (
`paypaladaptivedetails_id` int(11) NOT NULL AUTO_INCREMENT,
`seller_invoice_id` int(11) NOT NULL,
`order_id` int(11) NOT NULL,
`seller_id` varchar(255) DEFAULT NULL,
`seller_amount` decimal(12,4) NOT NULL,
`commission_amount` decimal(12,4) NOT NULL,
`grand_total` decimal(12,4) NOT NULL,
`currency_code` varchar(25) NOT NULL,
`pay_key` varchar(255) NOT NULL,
`tracking_id` varchar(255) NOT NULL,
`owner_paypal_id` varchar(255) NOT NULL,
`group_type` varchar(255) NOT NULL,
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`seller_transaction_id` varchar(255) DEFAULT NULL,
`buyer_paypal_mail` varchar(255) DEFAULT NULL,
`transaction_status` varchar(255) DEFAULT NULL,
PRIMARY KEY (`paypaladaptivedetails_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('refunddetails')};
CREATE TABLE {$this->getTable('refunddetails')} (
`refunddetails_id` int(11) NOT NULL AUTO_INCREMENT,
`increment_id` int(11) NOT NULL,
`order_id` int(11) NOT NULL,
`seller_paypal_id` varchar(255) NOT NULL,
`pay_key` varchar(255) NOT NULL,
`tracking_id` varchar(255) NOT NULL,
`refund_net_amount` decimal(12,4) NOT NULL,
`refund_fee_amount` decimal(12,4) NOT NULL,
`refund_gross_amount` decimal(12,4) NOT NULL,
`currency_code` varchar(25) NOT NULL, 
`buyer_paypal_mail` varchar(255) DEFAULT NULL,
`payment_method` varchar(255) DEFAULT NULL,
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`refund_transaction_status` varchar(255) DEFAULT NULL,
`refund_status` varchar(255) DEFAULT NULL,
`encrypted_refund_transaction_id` varchar(255) DEFAULT NULL,
`transaction_id` varchar(255) DEFAULT NULL, 
PRIMARY KEY (`refunddetails_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('paypaladaptiveproductdetails')};
CREATE TABLE {$this->getTable('paypaladaptiveproductdetails')} (
`productdetails_id` int(11) NOT NULL AUTO_INCREMENT,
`product_id` int(11) NOT NULL,
`product_paypal_id` varchar(255) DEFAULT NULL,
`share_mode` varchar(255) DEFAULT NULL,
`share_value` decimal(12,4) DEFAULT NULL,
`is_enable` int(11) NOT NULL,
PRIMARY KEY (`productdetails_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('paypaladaptivecommissiondetails')};
CREATE TABLE {$this->getTable('paypaladaptivecommissiondetails')} (
`commissiondetails_id` int(11) NOT NULL AUTO_INCREMENT,
`product_id` int(11) NOT NULL,
`increment_id` int(11) NOT NULL,
`seller_id` varchar(255) DEFAULT NULL,
`commission_value` decimal(12,4) DEFAULT NULL,
`commission_mode` varchar(255) DEFAULT NULL,
PRIMARY KEY (`commissiondetails_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->endSetup();