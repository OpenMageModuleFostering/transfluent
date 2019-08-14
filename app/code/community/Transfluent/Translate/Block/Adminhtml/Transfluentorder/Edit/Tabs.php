<?php

/**
 * Class Transfluent_Translate_Block_Adminhtml_transfluentorder_Edit_Tabs
 */
class Transfluent_Translate_Block_Adminhtml_transfluentorder_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
    public function __construct() {
        parent::__construct();
        $this->setId('transfluentorder_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('transfluenttranslate')->__('Orders'));
    }

    protected function _beforeToHtml() {
        $this->addTab('form_section', array(
            'label' => Mage::helper('transfluenttranslate')->__('View order details'),
            'title' => Mage::helper('transfluenttranslate')->__('View order details'),
            'content' => $this->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder_edit_tab_form')
                ->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}
