<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Transfluenttranslate extends Mage_Core_Model_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('transfluenttranslate/transfluenttranslate');
    }

    /**
     * getQuote model
     *
     * @param $store_from_id
     * @param $store_to_id
     * @param $level
     * @param array $products
     * @param $force_translate
     * @param $fields_to_translate_in
     * @return array
     */
    public function getQuote($store_from_id, $store_to_id, $level, array $products, $force_translate, $fields_to_translate_in) {

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */

        $source_language = $language_helper->GetStoreLocale($store_from_id);
        $source_language_id = $language_helper->getLangByCode($source_language, true);

        $default_source_language_id = $language_helper->DefaultSourceLanguage($store_to_id);
        if ($source_language_id != $default_source_language_id) {
            $language_helper->SetDefaultSourceLanguage($source_language_id);
        }
        $default_level = $language_helper->DefaultLevel();
        if ($level != $default_level) {
            $language_helper->SetDefaultLevel($level);
        }

        $model = Mage::getModel('catalog/product');
        /** @var Mage_Catalog_Model_Product $model */

        $totalWordCount = 0;
        $totalCost = 0;
        $priceCurrency = "";
        $text = '';

        foreach ($products as $productId) {

            $product = $model->setStoreId($store_from_id)->load($productId);
            /** @var Mage_Catalog_Model_Product $product */
            if (!$product) {
                continue;
            }

            $translate_fields = $language_helper->getTranslateFields($product, $force_translate, $store_to_id, $fields_to_translate_in);
            $fields_already_translated = $language_helper->getAlreadyTranslatedFields($product, $force_translate, $store_to_id, $fields_to_translate_in);

            foreach ($translate_fields AS $translate_field) {
                $text .= $product->getData($translate_field) . PHP_EOL;
            }
        }

        $target_language = $language_helper->GetStoreLocale($store_to_id);
        $target_language_id = $language_helper->getLangByCode($target_language, true);
        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */

        $response = $tf_client->FreeTextWordCount($level, $text, $source_language_id, $target_language_id);

        if ($response && @$response['status'] == 'OK') {
            $response = $response['response'];

            if (0 != $response['count']) {

                $totalWordCount += $response['count'];
                $totalCost += $response['price']['amount'];

                if (empty($priceCurrency)) {
                    $priceCurrency = $response['price']['currency'];
                }
            }

            /** @var Transfluent_Translate_Helper_Constant $constant_helper */
            $constant_helper = Mage::helper('transfluenttranslate/constant');
            $non_translatable_attributes = $constant_helper->getNonTranslatableAttributes();

            $result = array(
                'status' => 'success',
                'wordCount' => $totalWordCount,
                'cost' => $totalCost,
                'currency' => $priceCurrency,
                'nonTranslatableAttributes' => $non_translatable_attributes,
            );

            $product_helper = Mage::helper('transfluenttranslate/product');
            /** @var Transfluent_Translate_Helper_Product $product_helper */
            $result['productAttributes'] = $product_helper->GetProductAttribute($product, $translate_fields);
            $result['forceTranslate'] = $force_translate;
            $result['translationFields'] = ($translate_fields) ? $translate_fields : null;
            $result['fieldsAlreadyTranslated'] = ($fields_already_translated) ? $fields_already_translated : null;

            return $result;

        } else if ($response && @$response['status'] == 'ERROR') {
            if ($response && @$response['status'] == 'ERROR' && @$response['error']['type']) {
                $e = Transfluent_Translate_Exception_Base::create($response['error']['type']);
                if ($e !== null)
                    $error_msg = $e->getMessage();
            }
        }

        if (empty($error_msg)) {
            $e = new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
            $error_msg = $e->getMessage();
        }

        return array('status' => 'error', 'message' => $error_msg);
    }

    /**
     * get text to translate for all subcategories
     *
     * @param Mage_Catalog_Model_Category $parent_category
     * @param $translate_name
     * @param $translate_desc
     * @param $translate_meta
     *
     * @return string
     */
    public function getTextsToTranslateForAllSubCategories(Mage_Catalog_Model_Category $parent_category, $translate_name, $translate_desc, $translate_meta) {
        $text = '';
        $children = $parent_category->getAllChildren(true);
        if (!$children) {
            return $text;
        }
        $category_model = Mage::getModel('catalog/category');
        /** @var Mage_Catalog_Model_Category $category_model */
        foreach ($children AS $index => $category_id) {
            if ($category_id == $parent_category->getId()) {
                continue;
            }
            $category = $category_model->setStoreId($parent_category->getStoreId())->load($category_id);
            /** @var Mage_Catalog_Model_Category $category */
            if ($translate_name) {
                $text .= $category->getName() . PHP_EOL;
            }
            if ($translate_desc && $category->getData('description')) {
                $text .= $category->getData('description') . PHP_EOL;
            }
            if ($translate_meta) {
                $text .= $category->getData('meta_title') . PHP_EOL;
                $text .= $category->getData('meta_keywords') . PHP_EOL;
                $text .= $category->getData('meta_description') . PHP_EOL;
            }
        }
        return $text;
    }

    /**
     * calculates quote for tags
     *
     * @param $tag_models
     * @param $store_to_id
     * @param $store_from_id
     * @param $level
     *
     * @return array
     */
    public function getTagQuote($tag_models, $store_to_id, $store_from_id, $level) {
        if (empty($tag_models) || empty($store_from_id) || empty($store_to_id) || empty($level)) {
            return array();
        }

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */

        $text = '';
        foreach ($tag_models AS $tag_model) {
            /** @var Mage_Tag_Model_Tag $tag_model */
            $text .= $tag_model->getName() . PHP_EOL;
        }

        $target_language = $language_helper->GetStoreLocale($store_to_id);
        $target_language_id = $language_helper->getLangByCode($target_language, true);
        $source_language = $language_helper->GetStoreLocale($store_from_id);
        $source_language_id = $language_helper->getLangByCode($source_language, true);

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */
        $response = $tf_client->FreeTextWordCount($level, $text, $source_language_id, $target_language_id);

        return $response;
    }
}
