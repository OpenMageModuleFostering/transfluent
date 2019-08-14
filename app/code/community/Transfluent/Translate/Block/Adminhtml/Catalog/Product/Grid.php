<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid {
    protected function _prepareMassaction() {
        parent::_prepareMassaction();

        if (!Mage::getSingleton('admin/session')->isAllowed('catalog/update_attributes')){
            return $this;
        }
        $store_id = $this->getRequest()->getParam('store');
        if (!$store_id) {
            return $this;
        }

        $stores = Mage::app()->getStores();
        if (!$stores || count($stores) < 2) {
            return $this;
        }
        $possible_source_stores = array();
        foreach ($stores AS $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($store_id == $store->getId()) {
                continue;
            }
            $possible_source_stores[] = $store->getId();
        }
        if (empty($possible_source_stores)) {
            return $this;
        }
        $email = Mage::getStoreConfig('transfluenttranslate/account/email');
        if (is_null($email)) {
            return $this;
        }

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $default_source_language_id = $language_helper->DefaultSourceLanguage($store_id);
        $default_level = $language_helper->DefaultLevel();
        $store_languages = $language_helper->getSourceLanguageArray($possible_source_stores, $default_source_language_id);
        $quality = $language_helper->getQualityArray($default_level);

        $this->getMassactionBlock()->addItem('translate', array(
            'label'=> Mage::helper('catalog')->__('Translate'),
            'url'  => $this->getCurrentUrl(),
            'additional' => array(
                'visibility_lang' => array(
                    'name' => 'translate_from',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('catalog')->__('from'),
                    'values' => $store_languages
                ),
                'visibility_quality' => array(
                    'name' => 'level',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('catalog')->__('using'),
                    'values' => $quality
                ),
                'visibility_store' => array(
                    'name' => 'translate_store',
                    'type' => 'hidden',
                    'class' => 'required-entry',
                    'value' => $store_id
                )
            )
        ));
        return $this;
    }
}
