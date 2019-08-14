<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Helper_Text extends Mage_Core_Helper_Abstract {
    private $_handlers = array(
        "/store\-([0-9]{1,})\-tag\-([0-9]{1,})/" => 'Transfluent_Translate_Model_TagName',
        "/store\-([0-9]{1,})\-product\-([0-9]{1,})\-(.*)/" => 'Transfluent_Translate_Model_ProductDetail',
        "/store\-([0-9]{1,})\-category\-([0-9]{1,})\-([a-z\-]{1,})/" => 'Transfluent_Translate_Model_CategoryDetail',
        "/store\-([0-9]{1,})\-attribute\-([0-9]{1,})\-option\-([0-9]{1,})/" => 'Transfluent_Translate_Model_AttributeOption',
        "/store\-([0-9]{1,})\-attribute\-([0-9]{1,})/" => 'Transfluent_Translate_Model_AttributeName',
    );

    public function ModelByTextId($text_id) {
        $status = null;
        foreach ($this->_handlers AS $pattern => $class_name) {
            if (preg_match($pattern, $text_id, $matches)) {
                $object = new $class_name();
                $object->SetTextId($text_id);
                return $object;
            }
        }
        return null;
    }
}
