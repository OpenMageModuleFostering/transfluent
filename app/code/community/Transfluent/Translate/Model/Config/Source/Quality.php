<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Config_Source_Quality extends Varien_Data_Collection {
    public function toOptionArray() {
        $options = array();
        $levels = Mage::helper('transfluenttranslate/languages')->getQualityArray();
        $options[] = array('value' => '0', 'label' => '');

        foreach ($levels AS $key => $level) {
            $options[] = array(
                'value' => $key,
                'label' => $level
            );
        }
        return ($options);
    }
}
