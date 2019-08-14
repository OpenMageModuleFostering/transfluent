<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Estimate extends Mage_Adminhtml_Block_System_Config_Form_Field {
    public function getEstimate() {
        return Mage::getSingleton('admin/session')->getEstimate();
    }

    public function clearEstimate() {
        return Mage::getSingleton('admin/session')->unsEstimate();
    }
}
