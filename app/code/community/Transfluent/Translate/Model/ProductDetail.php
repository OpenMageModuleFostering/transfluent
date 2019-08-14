<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_ProductDetail extends Mage_Core_Model_Abstract {
    private $_text_id;
    private $_store;
    private $_product_id;
    private $_attribute_name;

    public function _construct() {
        parent::_construct();
    }

    public function SetTextId($text_id) {
        if (!preg_match("/store\-([0-9]{1,})\-product\-([0-9]{1,})\-(.*)/", $text_id, $matches)) {
            throw new Exception('Invalid text id');
        }
        $this->_text_id = $text_id;
        $this->_store = $matches[1];
        $this->_product_id = $matches[2];
        $this->_attribute_name = $matches[3];
    }

    /**
     * @return Mage_Catalog_Model_Product|null
     */
    public function Product() {
        $model = Mage::getModel('catalog/product')->load($this->_product_id);
        /** @var Mage_Catalog_Model_Product $model */
        if ($model && $model->getId()) {
            return $model;
        }
        return null;
    }

    public function OrderTitle() {
        $product = $this->Product();
        $title = Mage::helper('transfluenttranslate/text')->__('Product');
        $title .= ' (' . Mage::helper('transfluenttranslate/text')->__($product->getResource()->getAttribute($this->_attribute_name)->getFrontend()->getLabel()) . ')';
        if ($product) {
            $title .= ': ' . '<a href="' . $product->getProductUrl() . '">' . $product->getName() . '</a>';
        }
        return $title;
    }
}
