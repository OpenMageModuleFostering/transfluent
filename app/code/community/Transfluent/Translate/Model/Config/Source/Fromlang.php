<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Config_Source_Fromlang extends Varien_Data_Collection {
    public function toOptionArray() {
        $options = array();
        $languages = Mage::helper('transfluenttranslate/languages')->getLanguages();
        foreach ($languages as $language) {
            $options[] = array(
                'label' => $language['name'],
                'value' => $language['id']
            );
        }
        return ($options);
    }
}
