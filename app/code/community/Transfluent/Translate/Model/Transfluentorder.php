<?php

/**
 * Class Transfluent_Translate_Model_Transfluentorder
 */
class Transfluent_Translate_Model_Transfluentorder extends Mage_Core_Model_Abstract {

    /** const MSG_ORDER_SUCCESS successful order message */
    const MSG_ORDER_SUCCESS = 'order was successful';

    public function _construct() {
        parent::_construct();
        $this->_init('transfluenttranslate/transfluenttranslate');
    }

    /**
     * order translation for product
     *
     * @param $product_id
     * @param $store_id
     * @param $store_from_id
     * @param $level
     * @param string $instructions
     * @param array $translate_fields_in
     * @param bool $force_translate
     *
     * @return bool
     *
     * @throws Transfluent_Translate_Exception_ETransfluentUnknownErrorBase
     * @throws Exception
     * @throws Transfluent_Translate_Exception_ETransfluentProductNotFoundBase
     * @throws Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase
     * @throws Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase
     * @throws Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase
     */
    public function orderTranslationForProduct($product_id, $store_id, $store_from_id, $level, $instructions = '', $translate_fields_in = array(), $force_translate = false) {
        set_time_limit(0);

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $source_language = $language_helper->GetStoreLocale($store_from_id);
        $source_language_id = $language_helper->getLangByCode($source_language, true);

        $model = Mage::getModel('catalog/product');
        /** @var Mage_Catalog_Model_Product $model */
        $product = $model->setStoreId($store_from_id)->load($product_id);
        /** @var Mage_Core_Model_Abstract $product */
        if (!$product) {
            throw new Transfluent_Translate_Exception_ETransfluentProductNotFoundBase();
        }
        $translated_product = $model->setStoreId($store_id)->load($product->getId());
        /** @var Mage_Catalog_Model_Product $translated_product */

        if (!$translate_fields_in) {
            $translate_fields = array();
            foreach ($language_helper->DefaultProductFieldsToTranslate() AS $default_field_to_translate) {
                if ($force_translate || !$translated_product->getExistsStoreValueFlag($default_field_to_translate)) {
                    $translate_fields[] = $default_field_to_translate;
                }
            }
        } else {
            $translate_fields = $translate_fields_in;
        }

        if (empty($translate_fields)) {
            throw new Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase();
        }

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */
        $text_hashes = array();
        $keys_to_order = array();
        foreach ($translate_fields AS $translate_field) {
            $text_id = 'store-' . $store_id . '-product-' . $product->getId() . '-' . $translate_field;
            $text_str = $product->getData($translate_field);
            if ($text_str === '' || $text_str === false || is_null($text_str)) {
                continue;
            }
            $response = $tf_client->SaveText($text_id, $source_language_id, $text_str);
            if ($response && isset($response['error']['type']) && $response['error']['type'] == 'EBackendTextAlreadyUpToDate') {
                // Ignore
            } else if (!$response || @$response['status'] != 'OK') {
                if (@$response['error']['type'] == 'EBackendSecurityViolation') {
                    throw new Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase();
                } else if (@$response['error']['type'] == 'ENoJobs') {
                    throw new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
                }
                throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
            }
            $keys_to_order[] = $text_id;
            $text_hashes[$text_id] = md5($text_str);
        }

        $target_language = $language_helper->GetStoreLocale($store_id);
        $target_language_id = $language_helper->getLangByCode($target_language, true);
        $callback_url = Mage::getUrl('transfluenttranslate/translation/save') . '?th=' . md5(Mage::getStoreConfig('transfluenttranslate/account/token'));
        $context_comment = 'Text is part of product page in this webstore: ' . $product->getProductUrl() . PHP_EOL;

        if ($instructions) {
            $context_comment .= PHP_EOL . 'Instructions:' . PHP_EOL . '=============' . PHP_EOL . PHP_EOL;
            $context_comment .= $instructions;
        }

        $text_ids = array();
        foreach ($keys_to_order AS $text_id) {
            $text_ids[] = array('id' => $text_id);
        }

        $response = $tf_client->TextsTranslate('Magento', $source_language_id, Mage::helper('core')->jsonEncode(array($target_language_id)), Mage::helper('core')->jsonEncode($text_ids), $context_comment, $callback_url, $level);
        if ($response && @$response['status'] == 'OK') {
            foreach ($keys_to_order AS $text_id) {
                $order_model = Mage::getModel('transfluenttranslate/transfluenttranslate');
                $order_model->setTextId($text_id)
                    ->setSourceStore($store_from_id)
                    ->setTargetStore($store_id)
                    ->setSourceLanguage($source_language_id)
                    ->setTargetLanguage($target_language_id)
                    ->setLevel($level)
                    ->setStatus(1);
                if (isset($text_hashes[$text_id])) {
                    $order_model->setSourceTextHash($text_hashes[$text_id]);
                }
                $order_model->save();
            }
            return true;
        }

        $error_msg = 'Something went wrong, the order might have not been placed properly. Please contact support@transfluent.com to resolve the issue. We apologize for the inconvenience!';
        if ($response && @$response['status'] == 'ERROR' && @$response['error']['type']) {
            $e = Transfluent_Translate_Exception_Base::create($response['error']['type']);
            if ($e !== null)
                $error_msg = $e->getMessage();
        }
        throw new Exception($error_msg);
    }

    /**
     * order translation for tag
     *
     * @param $tag_id
     * @param $store_to_id
     * @param $store_from_id
     * @param $level
     * @param string $instructions
     *
     * @return bool
     *
     * @throws Exception
     * @throws Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase
     * @throws Transfluent_Translate_Exception_ETransfluentProductNotFoundBase
     * @throws Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase
     * @throws Transfluent_Translate_Exception_ETransfluentUnknownErrorBase
     */
    public function orderTranslationForTag($tag_id, $store_to_id, $store_from_id, $level, $instructions = '') {
        set_time_limit(0);
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $source_language_code = $language_helper->GetStoreLocale($store_from_id);
        $source_language_id = $language_helper->getLangByCode($source_language_code, true);

        $model = Mage::getModel('tag/tag');
        /** @var Mage_Tag_Model_Tag $model */
        $tag = $model->setStoreId($store_from_id)->load($tag_id);
        if (!$tag) {
            throw new Transfluent_Translate_Exception_ETransfluentProductNotFoundBase();
        }
        /** @var Mage_Tag_Model_Tag $tag */
        $text_id = 'store-' . $store_to_id . '-tag-' . $tag->getId();
        $text_to_translate = $tag->getName();
        if ($text_to_translate === '' || $text_to_translate === false || is_null($text_to_translate)) {
            throw new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
        }

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */
        $response = $tf_client->SaveText($text_id, $source_language_id, $text_to_translate);

        $backEndTextAlreadyUpToDate = ('EBackendTextAlreadyUpToDate' == $response['error']['type']);
        if ($response && isset($response['error']['type']) && $backEndTextAlreadyUpToDate) {
            // Ignore
        } else if (!$response || 'OK' != @$response['status']) {
            if (@$response['error']['type'] == 'EBackendSecurityViolation') {
                throw new Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase();
            } else if (@$response['error']['type'] == 'ENoJobs') {
                throw new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
            }
            throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
        }

        $target_language = $language_helper->GetStoreLocale($store_to_id);
        $target_language_id = $language_helper->getLangByCode($target_language, true);
        $callback_url = Mage::getUrl('transfluenttranslate/translation/save') . '?th=' . md5(Mage::getStoreConfig('transfluenttranslate/account/token'));
        $context_comment = 'The text is a product tag used in a e-commerce store.' . PHP_EOL;
        // @todo FIXME: Get associated product(s) for example
        if ($instructions) {
            $context_comment .= $instructions;
        }

        $text_ids = array();
        $text_ids[] = array('id' => $text_id);

        $response = $tf_client->TextsTranslate('Magento', $source_language_id, Mage::helper('core')->jsonEncode(array($target_language_id)), Mage::helper('core')->jsonEncode($text_ids), $context_comment, $callback_url, $level);
        if ($response && @$response['status'] == 'OK') {
            foreach ($text_ids AS $row) {
                $text_id = $row['id'];
                $order_model = Mage::getModel('transfluenttranslate/transfluenttranslate');
                $order_model->setTextId($text_id)
                    ->setSourceStore($store_from_id)
                    ->setTargetStore($store_to_id)
                    ->setSourceLanguage($source_language_id)
                    ->setTargetLanguage($target_language_id)
                    ->setLevel($level)
                    ->setStatus(1);
                if (isset($text_hashes[$text_id])) {
                    $order_model->setSourceTextHash($text_hashes[$text_id]);
                }
                $order_model->save();
            }
            return true;
        }

        if ($response && "ERROR" == @$response['status'] && @$response['error']['type']) {
            $e = Transfluent_Translate_Exception_Base::create($response['error']['type']);
            if ($e) throw $e;
        }

        throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
    }

    /**
     * @param $attribute_id
     * @param $store_id
     * @param $translate_from_store_id
     * @param $level
     * @param string $instructions
     * @param bool $translate_name
     * @param bool $translate_values
     *
     * @return bool
     *
     * @throws Transfluent_Translate_Exception_ETransfluentUnknownErrorBase
     * @throws Exception
     * @throws Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase
     * @throws Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase
     * @throws Transfluent_Translate_Exception_ETransfluentProductNotFoundBase
     * @throws Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase
     */
    public function orderTranslationForAttribute($attribute_id, $store_id, $translate_from_store_id, $level, $instructions = '', $translate_name = false, $translate_values = false) {
        set_time_limit(0);

        if (!$translate_name && !$translate_values) {
            throw new Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase();
        }

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $source_language_code = $language_helper->GetStoreLocale($translate_from_store_id);
        $source_language_id = $language_helper->getLangByCode($source_language_code, true);

        $attribute_model = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Entity_Attribute $attribute_model */
        $attribute = $attribute_model->load($attribute_id);
        if (!$attribute) {
            throw new Transfluent_Translate_Exception_ETransfluentProductNotFoundBase();
        }

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */

        $translate_fields = array();
        if ($translate_name) {
            $translate_fields['store-' . $store_id . '-attribute-' . $attribute->getId()] = $attribute->getStoreLabel($translate_from_store_id);
        }
        if ($translate_values) {
            $admin_values_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($attribute_id)->setStoreFilter(0, false)->load();
            $admin_values = array();
            foreach ($admin_values_collection as $item) {
                /** @var Mage_Eav_Model_Entity_Attribute_Option $item */
                $admin_values[$item->getId()] = $item->getValue();
            }
            $store_values = array();
            $values_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($attribute_id)->setStoreFilter($translate_from_store_id, false)->load();
            foreach ($values_collection as $item) {
                /** @var Mage_Eav_Model_Entity_Attribute_Option $item */
                $store_values[$item->getId()] = $item->getValue();
            }
            foreach ($admin_values AS $item_id => $item_text) {
                if (isset($store_values[$item_id])) {
                    $item_text = $store_values[$item_id];
                }
                $translate_fields['store-' . $store_id . '-attribute-' . $attribute->getId() . '-option-' . $item_id] = $item_text;
            }
        }

        $keys_to_order = array();
        foreach ($translate_fields AS $text_id => $text_to_translate) {
            if ($text_to_translate === '' || $text_to_translate === false || is_null($text_to_translate)) {
                continue;
            }
            $response = $tf_client->SaveText($text_id, $source_language_id, $text_to_translate);
            if ($response && isset($response['error']['type']) && $response['error']['type'] == 'EBackendTextAlreadyUpToDate') {
                // Ignore
            } else if (!$response || @$response['status'] != 'OK') {
                if (@$response['error']['type'] == 'EBackendSecurityViolation') {
                    throw new Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase();
                } else if (@$response['error']['type'] == 'ENoJobs') {
                    throw new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
                }
                throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
            }
            $keys_to_order[] = $text_id;
        }

        $target_language = $language_helper->GetStoreLocale($store_id);
        $target_language_id = $language_helper->getLangByCode($target_language, true);
        $callback_url = Mage::getUrl('transfluenttranslate/translation/save') . '?th=' . md5(Mage::getStoreConfig('transfluenttranslate/account/token'));
        $context_comment = '';
        if ($instructions) {
            $context_comment .= $instructions;
        }

        $text_ids = array();
        foreach ($keys_to_order AS $text_id) {
            $text_ids[] = array('id' => $text_id);
        }

        $response = $tf_client->TextsTranslate('Magento', $source_language_id, Mage::helper('core')->jsonEncode(array($target_language_id)), Mage::helper('core')->jsonEncode($text_ids), $context_comment, $callback_url, $level);
        if ($response && @$response['status'] == 'OK') {
            foreach ($keys_to_order AS $text_id) {
                $order_model = Mage::getModel('transfluenttranslate/transfluenttranslate');
                $order_model->setTextId($text_id)
                    ->setSourceStore($translate_from_store_id)
                    ->setTargetStore($store_id)
                    ->setSourceLanguage($source_language_id)
                    ->setTargetLanguage($target_language_id)
                    ->setLevel($level)
                    ->setStatus(1);
                if (isset($text_hashes[$text_id])) {
                    $order_model->setSourceTextHash($text_hashes[$text_id]);
                }
                $order_model->save();
            }
            return true;
        }

        if ($response && @$response['status'] == 'ERROR' && @$response['error']['type']) {
            $e = Transfluent_Translate_Exception_Base::create($response['error']['type']);
            if ($e) throw $e;
        }
        throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
    }

    /**
     * order translation for category
     * @param $category_id
     * @param $store_id
     * @param $translate_from_store_id
     * @param $level
     * @param $instructions
     * @param bool $translate_name
     * @param bool $translate_desc
     * @param bool $translate_meta
     * @param bool $translate_subcat
     *
     * @return bool
     *
     * @throws Transfluent_Translate_Exception_ETransfluentUnknownErrorBase
     * @throws Exception
     * @throws Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase
     * @throws Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase
     * @throws Transfluent_Translate_Exception_ETransfluentProductNotFoundBase
     * @throws Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase
     */
    public function orderTranslationForCategory($category_id, $store_id, $translate_from_store_id, $level, $instructions, $translate_name = false, $translate_desc = false, $translate_meta = false, $translate_subcat = false) {
        set_time_limit(0);

        if (!$translate_name && !$translate_desc && !$translate_meta) {
            throw new Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase();
        }

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $source_language_code = $language_helper->GetStoreLocale($translate_from_store_id);
        $source_language_id = $language_helper->getLangByCode($source_language_code, true);

        $category_model = Mage::getModel('catalog/category');
        /** @var Mage_Catalog_Model_Category $category_model */
        $category = $category_model->setStoreId($translate_from_store_id)->load($category_id);
        if (!$category) {
            throw new Transfluent_Translate_Exception_ETransfluentProductNotFoundBase();
        }
        /** @var Mage_Catalog_Model_Category $category */

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */

        if ($translate_subcat) {
            $categories_to_translate = $category->getAllChildren(true);
        } else {
            $categories_to_translate = array($category->getId());
        }
        $translate_fields = array();
        foreach ($categories_to_translate AS $category_id_to_translate) {
            $category_to_translate = $category_model->setStoreId($translate_from_store_id)->load($category_id_to_translate);
            /** @var Mage_Catalog_Model_Category $category_to_translate */
            if ($translate_name) {
                $translate_fields['store-' . $store_id . '-category-' . $category->getId() . '-name'] = $category->getName();
            }
            if ($translate_desc) {
                $translate_fields['store-' . $store_id . '-category-' . $category->getId() . '-description'] = $category->getData('description');
            }
            if ($translate_meta) {
                $translate_fields['store-' . $store_id . '-category-' . $category->getId() . '-meta-title'] = $category->getData('meta_title');
                $translate_fields['store-' . $store_id . '-category-' . $category->getId() . '-meta-keywords'] = $category->getData('meta_keywords');
                $translate_fields['store-' . $store_id . '-category-' . $category->getId() . '-meta-description'] = $category->getData('meta_description');
            }
        }

        $keys_to_order = array();
        foreach ($translate_fields AS $text_id => $text_to_translate) {
            if ($text_to_translate === '' || $text_to_translate === false || is_null($text_to_translate)) {
                continue;
            }
            $response = $tf_client->SaveText($text_id, $source_language_id, $text_to_translate);
            if ($response && isset($response['error']['type']) && $response['error']['type'] == 'EBackendTextAlreadyUpToDate') {
                // Ignore
            } else if (!$response || @$response['status'] != 'OK') {
                if (@$response['error']['type'] == 'EBackendSecurityViolation') {
                    throw new Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase();
                } else if (@$response['error']['type'] == 'ENoJobs') {
                    throw new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
                }
                throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
            }
            $keys_to_order[] = $text_id;
        }

        $target_language = $language_helper->GetStoreLocale($store_id);
        $target_language_id = $language_helper->getLangByCode($target_language, true);
        $callback_url = Mage::getUrl('transfluenttranslate/translation/save') . '?th=' . md5(Mage::getStoreConfig('transfluenttranslate/account/token'));
        $context_comment = '';
        if ($instructions) {
            $context_comment .= $instructions;
        }

        $text_ids = array();
        foreach ($keys_to_order AS $text_id) {
            $text_ids[] = array('id' => $text_id);
        }

        $response = $tf_client->TextsTranslate('Magento', $source_language_id, Mage::helper('core')->jsonEncode(array($target_language_id)), Mage::helper('core')->jsonEncode($text_ids), $context_comment, $callback_url, $level);
        if ($response && @$response['status'] == 'OK') {
            return true;
        }

        if ($response && @$response['status'] == 'ERROR' && @$response['error']['type']) {
            $e = Transfluent_Translate_Exception_Base::create($response['error']['type']);
            if ($e) throw $e;
        }
        throw new Transfluent_Translate_Exception_ETransfluentUnknownErrorBase();
    }
}
