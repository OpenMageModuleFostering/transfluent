<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_TagName extends Mage_Core_Model_Abstract {
    private $_text_id;
    private $_store;
    private $_tag_id;

    public function _construct() {
        parent::_construct();
    }

    public function SetTextId($text_id) {
        if (!preg_match("/store\-([0-9]{1,})\-tag\-([0-9]{1,})/", $text_id, $matches)) {
            throw new Exception('Invalid text id');
        }
        $this->_text_id = $text_id;
        $this->_store = $matches[1];
        $this->_tag_id = $matches[2];
    }

    public function Tag() {
        $source_tag = Mage::getModel('tag/tag')->load($this->_tag_id);
        /** @var Mage_Tag_Model_Tag $source_tag */
        if ($source_tag && $source_tag->getId()) {
            return $source_tag;
        }
        return null;
    }

    public function OrderTitle() {
        $tag = $this->Tag();
        $title = Mage::helper('transfluenttranslate/text')->__('Tag');
        if ($tag) {
            $store = Mage::app()->getStore();
            if ($tag->isAvailableInStore($store)) {
                $title .= ': ' . '<a href="' . $tag->getTaggedProductsUrl() . '">' . $tag->getName() . '</a>';
            } else {
                $title .= ': ' . $tag->getName();
            }
        }
        return $title;
    }
}
