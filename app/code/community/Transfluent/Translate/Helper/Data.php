<?php

class Transfluent_Translate_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getFields($sku) {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        $setId = $product->getAttributeSetId();
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($setId);
        $fields = array();
        foreach ($attributes AS $attr) {
            $attrId = $attr['attribute_id'];
            $attribute = Mage::getModel('eav/entity_attribute')->load($attrId);
            if ($attribute['is_visible_on_front'] == 1) {
                $fields[] = $attribute['attribute_code'];
            }
        }
        return $fields;
    }

    public function getProductEdit() {
        $action = Mage::app()->getRequest()->getActionName();
        if ($action == 'edit') {
            return true;
        }
        return false;
    }

    public function getStoreByCode($storeCode) {
        $storeId = 0;
        $stores = Mage::getModel('core/store')->getCollection()->getData();
        foreach ($stores as $store) {
            if ($store['code'] == $storeCode) {
                $storeId = $store['store_id'];
            }
        }
        return $storeId;
    }
}
