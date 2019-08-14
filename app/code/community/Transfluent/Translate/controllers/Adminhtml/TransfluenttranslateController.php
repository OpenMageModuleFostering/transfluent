<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Adminhtml_TransfluenttranslateController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('transfluenttranslate/items')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Items Manager'),
                Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }

    protected function _getStoreByCode($storeCode) {
        $store = Mage::app()->getStore($storeCode);
        return $store->getId();
    }

    protected function _getAllProductIds() {
        $ids = array();
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*');

        foreach ($collection as $product) {
            $ids[] = $product->getId();
        }

        return $ids;
    }

    public function indexAction() {
        $this->_initAction();
        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluenttranslate')
        );
        $this->renderLayout();
    }

    public function editAction() {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::getModel('transfluenttranslate/transfluenttranslate')->load($id);

        if ($order->getId()) {
            Mage::register('transfluenttranslate_data', $order);
            $this->loadLayout();
            $this->_setActiveMenu('transfluenttranslate/items');
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this
                ->_addContent(
                    $this
                        ->getLayout()
                        ->createBlock('transfluenttranslate/adminhtml_transfluenttranslate_edit'))
                ->_addLeft(
                    $this
                        ->getLayout()
                        ->createBlock('transfluenttranslate/adminhtml_transfluenttranslate_edit_tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('transfluenttranslate')->__('Item does not exist'));
            $this->_redirect('*/*/');
        }
    }

    /**
     * calculate and return quote for tags
     *
     * @return bool
     */
    public function get_tag_quoteAction() {

        set_time_limit(0);
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $level = intval($this->getRequest()->getParam('level'));
        $store_from_id = intval($this->getRequest()->getParam('store_from_id'));
        $store_to_id = intval($this->getRequest()->getParam('store_to_id'));
        $tags = $this->getRequest()->getParam('tags');
        $instructions = $this->getRequest()->getParam('instructions') ? 
                        $this->getRequest()->getParam('instructions') : 
                        Mage::getStoreConfig(
                                            'transfluenttranslate/transfluenttranslate_settings/transfluent_instructions',
                                            $store_to_id);

        if ($store_to_id === $store_from_id) {
            $e = new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        if (empty($tags) || !array_filter($tags, 'intval')) {
            $e = new Transfluent_Translate_Exception_ETransfluentInvalidInputTagsBase();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        $tag_helper = Mage::helper('transfluenttranslate/tag');
        /** @var Transfluent_Translate_Helper_Tag $tag_helper */
        $tag_models = $tag_helper->getTags($tags, $store_from_id);

        if (empty($tag_models)) {
            $e = new Transfluent_Translate_Exception_ETagNotFound();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        $translate_model = new Transfluent_Translate_Model_Transfluenttranslate();
        $getQuoteResponse = $translate_model
            ->getTagQuote($tag_models, $store_to_id, $store_from_id, $level);

        if (empty($getQuoteResponse) || !array_key_exists('status', $getQuoteResponse)) {
            $e = new Transfluent_Translate_Exception_ETransfluentUnknownBackendResponseBase();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        $error_msg = null;
        if ('OK' == $getQuoteResponse['status']) {
            $getQuoteResponse = $getQuoteResponse['response'];

            $result = array(
                'status' => 'success',
                'wordCount' => $getQuoteResponse['count'],
                'cost' => $getQuoteResponse['price']['amount'],
                'currency' => $getQuoteResponse['price']['currency'],
                'instruction' => Mage::helper('core')->quoteEscape($instructions)
            );

            $this->_outputSuccessJson($result);
            return true;
        } else if ('ERROR' == $getQuoteResponse['status']) {
            if (array_key_exists('type', $getQuoteResponse['error'])) {
                $e = Transfluent_Translate_Exception_Base::create($getQuoteResponse['error']['type']);
                if ($e !== null)
                    $error_msg = $e->getMessage();
            }
        }

        $e = new Transfluent_Translate_Exception_ETransfluentUnknownErrorNoEstimateBase();
        $error_msg = $error_msg ? : $e->getMessage();
        $this->_outputErrorJson($error_msg);
        return false;
    }

    public function get_category_quoteAction() {
        set_time_limit(0);
        if (!$this->getRequest()->isPost()) {
            return;
        }
        $translate_name = $this->getRequest()->getParam('translate_name');
        $translate_desc = $this->getRequest()->getParam('translate_desc');
        $translate_meta = $this->getRequest()->getParam('translate_meta');
        $translate_subcat = $this->getRequest()->getParam('translate_subcat');
        if (!$translate_name && !$translate_desc && !$translate_meta) {
            $error_msg = 'You did not select anything to be translated. Pick either attribute name, description or meta data to be translated.';
        }
        $level = intval($this->getRequest()->getParam('level'));
        $stores = $this->getRequest()->getParam('stores');
        if (empty($stores) || !is_array($stores)) {
            $error_msg = 'You did select any target languages. Please pick at least one target language.';
            $stores = array();
        }
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $translate_from_store_id = $this->getRequest()->getParam('from_store');
        $source_language_id = $language_helper->GetStoreLocale($translate_from_store_id);
        $category_id = $this->getRequest()->getParam('category_id');

        $category_model = Mage::getModel('catalog/category');
        /** @var Mage_Catalog_Model_Category $category_model */
        $category = $category_model->setStoreId($translate_from_store_id)->load($category_id);
        if (!$category) {
            $error_msg = 'Could not find category to translate!';
        }
        /** @var Mage_Catalog_Model_Category $category */

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */
        $text = '';
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
        if ($translate_subcat) {
            $translate_model = Mage::getModel('transfluenttranslate/transfluenttranslate');
            /** @var Transfluent_Translate_Model_Transfluenttranslate $translate_model */

            $text .= $translate_model->getTextsToTranslateForAllSubCategories(
                $category,
                $translate_name,
                $translate_desc,
                $translate_meta);
        }

        $total_words = 0;
        $total_price = '0';
        $price_currency = 'EUR';
        $output_html = "";
        foreach ($stores AS $store_id) {
            /** @var Mage_Core_Model_Store $store */
            $store_language = $language_helper->GetStoreLocale($store_id);
            $output_html .= '<strong>'
                . $language_helper->getLanguageNameByCode($source_language_id, true)
                . '-'
                . $language_helper->getLanguageNameByCode($store_language, true)
                . ':</strong> ';
            if ($source_language_id == $store_language) {
                $output_html .= 'Unfortunately proof-reading is not yet supported!<br>';
                continue;
            }
            $response = $tf_client->FreeTextWordCount(
                $level,
                $text,
                $language_helper->getLangByCode($source_language_id, true),
                $language_helper->getLangByCode($store_language, true));
            if ($response && @$response['status'] == 'OK') {
                $response = $response['response'];
                $output_html .= $response['count']
                    . ' words to translate. Translation costs '
                    . $response['price']['amount']
                    . $response['price']['currency']
                    . '.<br>';
                $price_currency = $response['price']['currency'];
                $locale_details = localeconv();
                $total_price = bcadd(
                    $total_price,
                    str_replace(
                        '.',
                        $locale_details['decimal_point'],
                        (string)$response['price']['amount']),
                    2);
                $total_words += $response['count'];
                continue;
            } else if ($response && @$response['status'] == 'ERROR') {
                switch (@$response['error']['type']) {
                    case 'ELanguagePairNotSupportedException':
                        $e = new Transfluent_Translate_Exception_ELanguagePairNotSupported();
                        $output_html .= $e->getMessage();
                        continue;
                }
                $output_html .= 'Failed to estimate. Please try again. If problem persist, please contact our support.<br>';
            }
        }
        if (!isset($error_msg) || !$error_msg) {
            $output_html .= '<strong>Total:</strong> '
                . $total_words
                . ' words, '
                . $total_price
                . $price_currency
                . '<br>';
            $output_html .= ' - <a href="#" onclick="$(\'tf_translate_form_instructions\').toggle(); return false;">Instructions</a><br>';
            $instructions = $this->getRequest()->getParam('instructions')
                ? $this->getRequest()->getParam('instructions')
                : Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_instructions');
            $instructions = $instructions ? $instructions : '';
            $output_html .= '<div id="tf_translate_form_instructions" style="display: none;"><textarea id="tf_translate_instructions_txt" name="instructions" cols=60 rows=4>' . Mage::helper('core')->quoteEscape($instructions) . '</textarea><br></div>';
            $output_html .= '<br>';
            $output_html .= '<span id="quote_action_buttons_container">';
            $output_html .= '<button title="Order" type="button" id="tf_place_order_btn" class="scalable save" onclick="OrderTranslation(this); return false;"><span><span><span>Order translation</span></span></span></button> ';
            $output_html .= '<button title="Cancel" type="button" class="scalable cancel" onclick="ResetEstimation();" style=""><span><span><span>Cancel</span></span></span></button>';
            $output_html .= '</span>';
        } else {
            $error_msg = (isset($error_msg)
                ? $error_msg
                : 'An error occurred and costs could not be estimated at the moment. Please try again!');
            $output_html .= '<div class="notification-global">' . $error_msg . '</div><br>';
            $output_html .= '<button title="OK" type="button" class="scalable back" onclick="ResetEstimation();" style=""><span><span><span>OK</span></span></span></button>';
        }

        $this->getResponse()->setBody($output_html);
    }

    public function get_attribute_quoteAction() {
        set_time_limit(0);
        if (!$this->getRequest()->isPost()) {
            return;
        }
        $translate_name = $this->getRequest()->getParam('translate_name');
        $translate_values = $this->getRequest()->getParam('translate_values');
        if (!$translate_name && !$translate_values) {
            $error_msg = 'You did not select anything to be translated. Pick either attribute name, options or both to be translated.';
        }
        $level = $this->getRequest()->getParam('level');
        $stores = $this->getRequest()->getParam('stores');
        if (empty($stores) || !is_array($stores)) {
            $error_msg = 'You did select any target languages. Please pick at least one target language.';
            $stores = array();
        }
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $translate_from_store_id = $this->getRequest()->getParam('from_store');
        $source_language_id = $language_helper->GetStoreLocale($translate_from_store_id);
        $attribute_id = $this->getRequest()->getParam('attribute_id');

        $attribute_model = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Entity_Attribute $attribute_model */
        $attribute = $attribute_model->load($attribute_id);
        if (!$attribute) {
            $error_msg = 'Could not find attribute to translate!';
        }

        $tf_client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $tf_client */
        $text = '';
        if ($translate_name) {
            $text .= $attribute->getStoreLabel($translate_from_store_id) . PHP_EOL;
        }
        if ($translate_values) {
            $admin_values_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($attribute_id)
                ->setStoreFilter(0, false)
                ->load();
            $admin_values = array();
            foreach ($admin_values_collection as $item) {
                /** @var Mage_Eav_Model_Entity_Attribute_Option $item */
                $admin_values[$item->getId()] = $item->getValue();
            }
            $store_values = array();
            $values_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($attribute_id)
                ->setStoreFilter($translate_from_store_id, false)
                ->load();
            foreach ($values_collection as $item) {
                /** @var Mage_Eav_Model_Entity_Attribute_Option $item */
                $store_values[$item->getId()] = $item->getValue();
            }
            foreach ($admin_values AS $item_id => $item_text) {
                if (isset($store_values[$item_id])) {
                    $text .= $store_values[$item_id] . PHP_EOL;
                    continue;
                }
                $text .= $item_text . PHP_EOL;
            }
        }

        $total_words = 0;
        $total_price = '0';
        $price_currency = 'EUR';
        $output_html = "";
        foreach ($stores AS $store_id) {
            /** @var Mage_Core_Model_Store $store */
            $store_language = $language_helper->GetStoreLocale($store_id);
            $output_html .= '<strong>'
                . $language_helper->getLanguageNameByCode($source_language_id, true)
                . '-'
                . $language_helper->getLanguageNameByCode($store_language, true)
                . ':</strong> ';
            if ($source_language_id == $store_language) {
                $output_html .= 'Unfortunately proof-reading is not yet supported!<br>';
                continue;
            }
            $response = $tf_client->FreeTextWordCount(
                $level,
                $text,
                $language_helper->getLangByCode($source_language_id, true),
                $language_helper->getLangByCode($store_language, true));
            if ($response && @$response['status'] == 'OK') {
                $response = $response['response'];
                $output_html .= $response['count']
                    . ' words to translate. Translation costs '
                    . $response['price']['amount']
                    . $response['price']['currency']
                    . '.<br>';
                $price_currency = $response['price']['currency'];
                $locale_details = localeconv();
                $total_price = bcadd(
                    $total_price,
                    str_replace(
                        '.',
                        $locale_details['decimal_point'],
                        (string)$response['price']['amount']),
                    2);
                $total_words += $response['count'];
                continue;
            } else if ($response && @$response['status'] == 'ERROR') {
                switch (@$response['error']['type']) {
                    case 'ELanguagePairNotSupportedException':
                        $e = new Transfluent_Translate_Exception_ELanguagePairNotSupported();
                        $output_html .= $e->getMessage();
                        continue;
                }
                $output_html .= 'Failed to estimate. Please try again. If problem persist, please contact our support.<br>';
            }
        }
        if (!isset($error_msg) || !$error_msg) {
            $output_html .= '<strong>Total:</strong> '
                . $total_words
                . ' words, '
                . $total_price
                . $price_currency
                . '<br>';
            $output_html .= ' - <a href="#" onclick="$(\'tf_translate_form_instructions\').toggle(); return false;">Instructions</a><br>';
            $instructions = $this->getRequest()->getParam('instructions')
                ? $this->getRequest()->getParam('instructions')
                : Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_instructions');
            $instructions = $instructions ? $instructions : '';
            $output_html .= '<div id="tf_translate_form_instructions" style="display: none;"><textarea id="tf_translate_instructions_txt" name="instructions" cols=60 rows=4>' . Mage::helper('core')->quoteEscape($instructions) . '</textarea><br></div>';
            $output_html .= '<br>';
            $output_html .= '<span id="quote_action_buttons_container">';
            $output_html .= '<button title="Order" type="button" id="tf_place_order_btn" class="scalable save" onclick="OrderTranslation(this); return false;"><span><span><span>Order translation</span></span></span></button> ';
            $output_html .= '<button title="Cancel" type="button" class="scalable cancel" onclick="ResetEstimation();" style=""><span><span><span>Cancel</span></span></span></button>';
            $output_html .= '</span>';
        } else {
            $error_msg = (isset($error_msg)
                ? $error_msg
                : 'An error occurred and costs could not be estimated at the moment. Please try again!');
            $output_html .= '<div class="notification-global">' . $error_msg . '</div><br>';
            $output_html .= '<button title="OK" type="button" class="scalable back" onclick="ResetEstimation();" style=""><span><span><span>OK</span></span></span></button>';
        }

        $this->getResponse()->setBody($output_html);
    }

    /**
     * calculates quote
     */
    public function get_quoteAction() {
        set_time_limit(0);
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $force_translate = intval($this->getRequest()->getParam('force_translate'));
        $level = intval($this->getRequest()->getParam('level'));
        $store_from_id = intval($this->getRequest()->getParam('store_from'));
        $store_to_id = intval($this->getRequest()->getParam('store_to'));
        $fields_to_translate_in = $this->getRequest()->getParam('fields_to_translate');
        $product_id_json = $this->getRequest()->getParam('product_id');
        $instructions = $this->getRequest()->getParam('instructions');

        if (empty($fields_to_translate_in)) {
            $fields_to_translate_in = null;
        }

        if (empty($instructions)) {
            $instructions = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_instructions', $store_to_id);
        }

        $products = json_decode($product_id_json);
        if ($products === null) {
            $this->_outputErrorJson('Input-product-ID(s) format is invalid!');
            return false;
        }

        if (empty($products)) {
            $e = new Transfluent_Translate_Exception_ETransfluentProductNotFoundBase();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

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

        $translate_model = Mage::getModel('transfluenttranslate/transfluenttranslate');
        /** @var Transfluent_Translate_Model_Transfluenttranslate $translate_model */

        // quote calculation by model
        $result = $translate_model->getQuote(
            $store_from_id,
            $store_to_id,
            $level,
            $products,
            $force_translate,
            $fields_to_translate_in);

        if (!empty($result) && is_array($result)) {
            $result['instruction'] = Mage::helper('core')->quoteEscape($instructions);
            $this->_outputSuccessJson($result);
            return true;
        }
        return false;
    }

    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluenttranslate_grid')
                ->toHtml()
        );
    }

    /**
     * @param $error_msg
     */
    private function _outputErrorJson($error_msg) {
        $this->getResponse()
            ->setBody(
                Mage::helper('transfluenttranslate/util')
                    ->getErrorJson($error_msg))
            ->setHeader('Content-type', 'application/json', true);
    }

    /**
     * @param $message
     */
    private function _outputSuccessJson($message) {
        $this->getResponse()
            ->setBody(json_encode($message))
            ->setHeader('Content-type', 'application/json', true);
    }
}

