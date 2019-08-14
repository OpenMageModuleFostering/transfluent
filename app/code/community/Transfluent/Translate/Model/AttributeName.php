<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_AttributeName extends Mage_Core_Model_Abstract {
    protected $_text_id;
    protected $_store;
    protected $_attribute_id;

    public function _construct() {
        parent::_construct();
    }

    public function SetTextId($text_id) {
        if (!preg_match("/store\-([0-9]{1,})\-attribute\-([0-9]{1,})/", $text_id, $matches)) {
            throw new Exception('Invalid text id');
        }
        $this->_text_id = $text_id;
        $this->_store = $matches[1];
        $this->_attribute_id = $matches[2];
    }

    public function Attribute() {
        try {
            $model = Mage::getModel('eav/entity_attribute')->load($this->_attribute_id);
        } catch (Exception $e) {
            return null;
        }
        /** @var Mage_Eav_Model_Entity_Attribute $model */
        if ($model && $model->getId()) {
            return $model;
        }
        return null;
    }

    public function OrderTitle() {
        $attribute = $this->Attribute();
        $title = Mage::helper('transfluenttranslate/text')->__('Attribute');
        if ($attribute) {
            $title .= ': ' . $attribute->getStoreLabel($this->_store);
        }
        return $title;
    }
}
