<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Transfluenttranslate_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
    public function __construct() {
        parent::__construct();
        $this->setId('transfluenttranslate_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('transfluenttranslate')->__('Orders'));
    }

    protected function _beforeToHtml() {
        $this->addTab('form_section', array(
            'label' => Mage::helper('transfluenttranslate')->__('View order details'),
            'title' => Mage::helper('transfluenttranslate')->__('View order details'),
            'content' => $this->getLayout()->createBlock('transfluenttranslate/adminhtml_transfluenttranslate_edit_tab_form')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}
