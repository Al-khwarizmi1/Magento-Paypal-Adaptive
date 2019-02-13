<?php

/**
 * In this class contains refund grid.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 13,2014
 * @Modified By     Ramkumar M
 * @Modified Date   January 13,2014
 *
 * */
class Apptha_Paypaladaptive_Block_Adminhtml_Refunddetails_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    /*
     * Class constructor
     */

    public function __construct() {
        parent::__construct();
        $this->setId('refunddetailssGrid');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /*
     * Prepare refund grid collection
     * 
     * @return collection
     */

    protected function _prepareCollection() {
        $collections = Mage::getModel('paypaladaptive/refunddetails')->getCollection();
        $this->setCollection($collections);
        return parent::_prepareCollection();
    }

    /*
     * Prepare refund grid columns
     * 
     * @return collection
     */

    protected function _prepareColumns() {

        $this->addColumn('increment_id', array(
            'header' => Mage::helper('paypaladaptive')->__('Order Id'),
            'width' => '20px',
            'index' => 'increment_id',
            'type' => 'number',
        ));

        $this->addColumn('seller_paypal_id', array(
            'header' => Mage::helper('paypaladaptive')->__('Paypal Id'),
            'width' => '20px',
            'index' => 'seller_paypal_id',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('paypaladaptive')->__('Created At'),
            'width' => '20px',
            'index' => 'created_at',
        ));

        $this->addColumn('refund_net_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Refund Net Amount'),
            'width' => '20px',
            'index' => 'refund_net_amount',
        ));

        $this->addColumn('refund_fee_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Refund Fee Amount'),
            'width' => '20px',
            'index' => 'refund_fee_amount',
        ));

        $this->addColumn('refund_gross_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Refund Gross Amount'),
            'width' => '20px',
            'index' => 'refund_gross_amount',
        ));

        $this->addColumn('currency_code', array(
            'header' => Mage::helper('paypaladaptive')->__('Currency Code'),
            'width' => '20px',
            'index' => 'currency_code',
        ));

        $this->addColumn('buyer_paypal_mail', array(
            'header' => Mage::helper('paypaladaptive')->__('Buyer Paypal Id'),
            'width' => '20px',
            'index' => 'buyer_paypal_mail',
        ));

        $this->addColumn('transaction_id', array(
            'header' => Mage::helper('paypaladaptive')->__('Transaction Id'),
            'width' => '20px',
            'index' => 'transaction_id',
        ));

        $this->addColumn('refund_status', array(
            'header' => Mage::helper('paypaladaptive')->__('Refund Status'),
            'width' => '20px',
            'index' => 'refund_status',
        ));

        $this->addColumn('view', array(
            'header' => Mage::helper('paypaladaptive')->__('Action'),
            'width' => '80',
            'type' => 'action',
            'getter' => 'getOrderId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('paypaladaptive')->__('View'),
                    'url' => array('base' => 'adminhtml/sales_order/view/'),
                    'field' => 'order_id'
                )
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));



        return parent::_prepareColumns();
    }

    /*
     * Get row url 
     * 
     * @param $row row collection
     * @return bool 
     */

    public function getRowUrl($row) {
        return FALSE;
    }

}

