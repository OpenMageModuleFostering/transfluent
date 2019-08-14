<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Account extends Mage_Core_Block_Template {
    public function getToken() {
        return Mage::getStoreConfig('transfluenttranslate/account/token');
    }

    public function getEmail() {
        return Mage::getStoreConfig('transfluenttranslate/account/email');
    }

    public function getKey($action) {
        return Mage::getSingleton('adminhtml/url')->getSecretKey("adminhtml_account", $action);
    }
}
