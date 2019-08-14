<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Tag_Tag_Grid extends Mage_Adminhtml_Block_Tag_Tag_Grid {
    protected function _prepareMassaction() {
        parent::_prepareMassaction();

        if (!Mage::getSingleton('admin/session')->isAllowed('catalog/update_attributes')){
            return $this;
        }

        $stores = Mage::app()->getStores();
        if (!$stores || count($stores) < 2) {
            return $this;
        }
        $helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $helper */
        $possible_source_languages = array();
        foreach ($stores AS $store) {
            /** @var Mage_Core_Model_Store $store */
            $possible_source_languages[] = $helper->GetStoreLocale($store->getCode());
        }
        if (empty($possible_source_languages)) {
            return $this;
        }
        $email = Mage::getStoreConfig('transfluenttranslate/account/email');
        if (is_null($email)) {
            return $this;
        }

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $default_level = $language_helper->DefaultLevel();

        $stores_and_languages = $language_helper->getSourceLanguageArrayForStores($stores);
        $quality = $language_helper->getQualityArray($default_level);

        $this->getMassactionBlock()->addItem('translate', array(
            'label'=> Mage::helper('catalog')->__('Translate'),
            'url'  => $this->getUrl('*/*/', array('_current'=>true)),
            'additional' => array(
                'store_from' => array(
                    'name' => 'store_from',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('catalog')->__('from'),
                    'values' => $stores_and_languages
                ),
                'store_to' => array(
                    'name' => 'store_to',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('catalog')->__('to'),
                    'values' => $stores_and_languages
                ),
                'quality' => array(
                    'name' => 'quality',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('catalog')->__('using'),
                    'values' => $quality
                )
            )
        ));
        return $this;
    }
}
