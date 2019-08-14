<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_LanguagePair extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $target_store_id = $row->getData($this->getColumn()->getIndex());
        $source_store_id = $row->getData('source_store');
        $target_language = $language_helper->getLanguageNameByCode($language_helper->GetStoreLocale($target_store_id), true);
        $source_language = $language_helper->getLanguageNameByCode($language_helper->GetStoreLocale($source_store_id), true);
        return $source_language . ' → ' . $target_language;
    }
}
