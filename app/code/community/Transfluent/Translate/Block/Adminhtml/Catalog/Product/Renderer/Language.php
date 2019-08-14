<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_Language extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $value =  $row->getData($this->getColumn()->getIndex());
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        return $language_helper->getLanguageNameByCode($language_helper->getLangById($value));
    }
}
