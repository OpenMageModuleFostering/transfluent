<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_CategoryDetail extends Mage_Core_Model_Abstract {
    private $_text_id;
    private $_store;
    private $_category_id;
    private $_detail_name;

    public function _construct() {
        parent::_construct();
    }

    public function SetTextId($text_id) {
        if (!preg_match("/store\-([0-9]{1,})\-category\-([0-9]{1,})\-([a-z\-]{1,})/", $text_id, $matches)) {
            throw new Exception('Invalid text id');
        }
        $this->_text_id = $text_id;
        $this->_store = $matches[1];
        $this->_category_id = $matches[2];
        $this->_detail_name = $matches[3];
    }

    public function Category() {
        $model = Mage::getModel('catalog/category')->setStoreId($this->_store)->load($this->_category_id);
        /** @var Mage_Catalog_Model_Category $model */
        if ($model && $model->getId()) {
            return $model;
        }
        return null;
    }

    public function OrderTitle() {
        $category = $this->Category();
        $title = '';
        switch ($this->_detail_name) {
            case 'name':
                $title .= Mage::helper('transfluenttranslate/text')->__('Category');
                break;
            case 'description':
                $title .= Mage::helper('transfluenttranslate/text')->__('Category description');
                break;
            case 'meta-description':
                $title .= Mage::helper('transfluenttranslate/text')->__('Category meta description');
                break;
            case 'meta-title':
                $title .= Mage::helper('transfluenttranslate/text')->__('Category meta title');
                break;
            case 'meta-keywords':
                $title .= Mage::helper('transfluenttranslate/text')->__('Category meta keywords');
                break;
        }
        if ($category) {
            $title .= ': ' . '<a href="' . $category->getUrl() . '">' . $category->getName() . '</a>';
        }
        return $title;
    }
}
