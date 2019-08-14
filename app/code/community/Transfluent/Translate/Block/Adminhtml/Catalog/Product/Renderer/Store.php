<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_Store extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $value =  $row->getData($this->getColumn()->getIndex());
        $store = Mage::app()->getStore($value);
        return $store->getName();
    }
}
