<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Transfluenttranslate extends Mage_Adminhtml_Block_Widget_Grid_Container {
    public function __construct() {
        $this->_controller = 'adminhtml_transfluenttranslate';
        $this->_blockGroup = 'transfluenttranslate';
        $this->_headerText = Mage::helper('transfluenttranslate')->__('Translation orders');
        parent::__construct();
        $this->removeButton('add');
    }
}
