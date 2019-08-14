<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Transfluenttranslate_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
    public function __construct() {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'transfluenttranslate';
        $this->_controller = 'adminhtml_transfluenttranslate';
        $this->removeButton('save');
        $this->removeButton('delete');
        $this->removeButton('reset');
    }

    public function getHeaderText() {
        if(Mage::registry('transfluenttranslate_data') && Mage::registry('transfluenttranslate_data')->getId()) {
            return Mage::helper('transfluenttranslate')->__("View order for text: '%s'", $this->htmlEscape(Mage::registry('transfluenttranslate_data')->getTransfluenttranslateTextid()));
        } else {
            return Mage::helper('transfluenttranslate')->__('N/A');
        }
    }
}
