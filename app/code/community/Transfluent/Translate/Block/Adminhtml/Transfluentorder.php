<?php

/**
 * Class Transfluent_Translate_Block_Adminhtml_Transfluentorder
 */
class Transfluent_Translate_Block_Adminhtml_Transfluentorder extends Mage_Adminhtml_Block_Widget_Form_Container {
    public function __construct() {
        $this->_controller = 'adminhtml_transfluentorder';
        $this->_blockGroup = 'transfluenttranslate';
        $this->_headerText = Mage::helper('transfluenttranslate')->__('Order new translation');

        parent::__construct();
        $this->removeButton('add');
        $this->addButton('order_reorder', array(
            'label' => Mage::helper('sales')->__('Get Quote'),
            'onclick' => 'setLocation(\'' . $this->getUrl('transfluent/adminhtml_transfluentorder/getquote') . '\')',));

        //$this->removeButton('save');
        $this->_updateButton('save', 'label', 'Place order');
        $this->_updateButton('save', 'disabled', true);
    }
}
