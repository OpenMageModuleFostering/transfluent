<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_AttributeOption extends Transfluent_Translate_Model_AttributeName {
    private $_option_id;

    public function SetTextId($text_id) {
        if (!preg_match("/store\-([0-9]{1,})\-attribute\-([0-9]{1,})\-option\-([0-9]{1,})/", $text_id, $matches)) {
            throw new Exception('Invalid text id');
        }
        $this->_text_id = $text_id;
        $this->_store = $matches[1];
        $this->_attribute_id = $matches[2];
        $this->_option_id = $matches[3];
    }

    public function OrderTitle() {
        $attribute = $this->Attribute();
        $title = Mage::helper('transfluenttranslate/text')->__('Attribute option');
        if ($attribute) {
            $title .= ': ' . $attribute->getStoreLabel($this->_store);
        }
        if ($attribute && $attribute->usesSource()) {
            try {
                $title .= ' - ' . $attribute->getSource()->getOptionText($this->_option_id);
            } catch (Exception $e) {
            }
        }
        return $title;
    }
}
