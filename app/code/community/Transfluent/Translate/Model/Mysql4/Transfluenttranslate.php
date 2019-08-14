<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Mysql4_Transfluenttranslate extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {
        $this->_init('transfluenttranslate/transfluenttranslate', 'id');
    }
}
