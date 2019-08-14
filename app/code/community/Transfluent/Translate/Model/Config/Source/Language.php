<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Config_Source_Language extends Varien_Data_Collection {
    public function toOptionArray() {
        $options = array();
        $languages = Mage::helper('transfluenttranslate/languages')->getLanguages();

        $options[] = array('value' => '0', 'label' => '(Please choose one language)');
        foreach ($languages as $language) {
            $options[] = array(
                'value' => $language['id'],
                'label' => $language['name']
            );
        }
        return ($options);
    }
}
