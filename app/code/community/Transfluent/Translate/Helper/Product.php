<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Helper_Product extends Mage_Core_Helper_Abstract
{
    /**
     * @param $store_id
     * @param $text_id
     * @param $level
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function GetOrder($store_id, $text_id, $level)
    {
        $order_model = Mage::getModel('transfluenttranslate/transfluenttranslate');
        return $order_model->getCollection()
            ->addFieldToFilter('text_id', $text_id)
            //->addFieldToFilter('source_store', $source_store)
            ->addFieldToFilter('target_store', $store_id)
            ->addFieldToFilter('level', $level);
    }

    /**
     * get product attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $translate_fields
     *
     * @return array
     */
    public function GetProductAttribute(Mage_Catalog_Model_Product $product, array $translate_fields)
    {

        if (empty($product)) {
            return array();
        }

        /** @var Transfluent_Translate_Helper_Constant $constant_helper */
        $constant_helper = Mage::helper('transfluenttranslate/constant');
        $non_translatable_attributes = $constant_helper->getNonTranslatableAttributes();

        $productAttributes = array();
        foreach ($product->getAttributes() AS $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            if (!$attribute->getIsVisible() || $attribute->isStatic() || $attribute->isDeleted() || $attribute->getIsGlobal()) {
                continue;
            }
            if (in_array($attribute->getName(), $non_translatable_attributes)) {
                continue;
            }
            if ($attribute->usesSource()) {
                continue;
            }

            $productAttributes[] = array(
                'name' => Mage::helper('core')->quoteEscape($attribute->getName()),
                'selected' => (in_array($attribute->getName(), $translate_fields) ? true : false),
                'label' => Mage::helper('core')->quoteEscape($attribute->getFrontend()->getLabel())
            );
        }

        return $productAttributes;
    }
}
