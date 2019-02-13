<?php
/**
 * In this class contains payment grid.
 *
 * @package         Apptha PayPal Adaptive
 * @version         0.1.1
 * @since           Magento 1.5
 * @author          Apptha Team
 * @copyright       Copyright (C) 2014 Powered by Apptha
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Date   January 13,2014
 * @Modified By     Varun S
 * @Modified Date   January 02,2017
 *
 * */
class Apptha_Paypaladaptive_Block_Adminhtml_Paymentdetails_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    /*
     * Class constructor
     */

    public function __construct() {
        parent::__construct();
        $this->setId('paymentdetailsGrid');
        $this->setDefaultSort('seller_invoice_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /*
     * Prepare payment grid collection
     * 
     * @return object collection
     */

    protected function _prepareCollection() {
        $collections = Mage::getModel('paypaladaptive/paypaladaptivedetails')->getCollection();
        $this->setCollection($collections);
        return parent::_prepareCollection();
    }

    /*
     * Prepare payment grid columns
     * 
     * @return object collection
     */

    protected function _prepareColumns() {
        $this->addColumn('seller_invoice_id', array(
            'header' => Mage::helper('paypaladaptive')->__('Order Id'),
            'width' => '20px',
            'index' => 'seller_invoice_id',
            'type' => 'number',
        ));

        $this->addColumn('seller_id', array(
            'header' => Mage::helper('paypaladaptive')->__('Paypal Id'),
            'width' => '30px',
            'index' => 'seller_id',
            'sortable' => false,
        ));


        $this->addColumn('created_at', array(
            'header' => Mage::helper('paypaladaptive')->__('Created At'),
            'width' => '20px',
            'index' => 'created_at',
            'type' => 'datetime',
        ));

        $this->addColumn('commission_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Commission Amount'),
            'width' => '20px',
            'index' => 'commission_amount',
            'sortable' => false,
        ));

        $this->addColumn('shipping_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Shipping Amount'),
            'width' => '20px',
            'index' => 'shipping_amount',
            'sortable' => false,
        ));

        $this->addColumn('coupon_discount_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Coupon Discount Amount'),
            'width' => '20px',
            'index' => 'coupon_discount_amount',
            'sortable' => false,
        ));

        $this->addColumn('seller_amount', array(
            'header' => Mage::helper('paypaladaptive')->__('Earned Amount'),
            'width' => '20px',
            'index' => 'seller_amount',
            'sortable' => false,
        ));

        $this->addColumn('group_type', array(
            'header' => Mage::helper('paypaladaptive')->__('User Type'),
            'width' => '20px',
            'index' => 'group_type',
            'sortable' => false,
        ));

        $this->addColumn('currency_code', array(
            'header' => Mage::helper('paypaladaptive')->__('Currency'),
            'width' => '20px',
            'index' => 'currency_code',
            'sortable' => false,
        ));


        $this->addColumn('buyer_paypal_mail', array(
            'header' => Mage::helper('paypaladaptive')->__('Buyer Paypal Id'),
            'width' => '30px',
            'index' => 'buyer_paypal_mail',
            'sortable' => false,
        ));

        $this->addColumn('seller_transaction_id', array(
            'header' => Mage::helper('paypaladaptive')->__('Transaction Id'),
            'width' => '30px',
            'index' => 'seller_transaction_id',
            'sortable' => false,
        ));

        $this->addColumn('transaction_status', array(
            'header' => Mage::helper('paypaladaptive')->__('Transaction Status'),
            'width' => '20px',
            'index' => 'transaction_status',
            'sortable' => false,
            'filter' => false,
        ));

        $this->addColumn('order_status', array(
            'header' => Mage::helper('paypaladaptive')->__('Order Status'),
            'width' => '20px',
            'filter' => false,
            'index' => 'order_id',
            'renderer' => 'Apptha_Paypaladaptive_Block_Adminhtml_Renderersource_Orderstatus',
            'sortable' => false
        ));


        $this->addColumn('view', array(
            'header' => Mage::helper('paypaladaptive')->__('Action'),
            'width' => '30',
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
     * @param object $row collection
     * @return bool 
     */

    public function getRowUrl($row) {
        return FALSE;
    }

}
?>