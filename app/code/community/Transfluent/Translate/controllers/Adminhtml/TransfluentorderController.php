<?php

/**
 * Class Transfluent_Translate_Adminhtml_TransfluentorderController
 */
class Transfluent_Translate_Adminhtml_TransfluentorderController extends Mage_Adminhtml_Controller_Action {
    protected $_publicActions = array('redirectToQuote');

    public function redirectToQuoteAction() {
        $quote_id = $this->getRequest()->getParam('quote_id');
        $source_store_id = $this->getRequest()->getParam('source_store');
        $target_store_id = $this->getRequest()->getParam('target_store');
        Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("transfluent/adminhtml_transfluentorder/orderFromCmsStep3", array("quote_id" => $quote_id, "source" => $source_store_id, "target" => $target_store_id)));
    }

    protected function _initAction() {
        $this->loadLayout()
            ->getLayout()
            ->createBlock('transfluenttranslate/adminhtml_transfluentorder_edit_form')
            ->toHtml();
        return $this;
    }

    public function indexAction() {
        $this->_initAction();
        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
        );
        $this->renderLayout();
    }


    public function orderAction() {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $force_translate = intval($this->getRequest()->getParam('force_translate'));
        $level = intval($this->getRequest()->getParam('level'));
        $store_id = intval($this->getRequest()->getParam('store_to'));
        $store_from_id = intval($this->getRequest()->getParam('store_from'));

        $productIdJson = $this->getRequest()->getParam('product_id');
        $instructions = $this->getRequest()->getParam('instructions')
            ? $this->getRequest()->getParam('instructions')
            : '';
        $fields_to_translate_in = $this->getRequest()->getParam('fields_to_translate');
        $translate_fields = array();
        foreach ($fields_to_translate_in AS $field_to_translate) {
            $translate_fields[] = $field_to_translate;
        }

        $productIds = json_decode($productIdJson);
        if ($productIds === null) {
            return false;
        }

        $status = null;

        $order_model = Mage::getModel('transfluenttranslate/transfluentorder');
        /** @var Transfluent_Translate_Model_Transfluentorder $order_model */

        try {
            foreach ($productIds as $productId) {
                if (intval($productId)) {

                    // ordering translation for product
                    $status = $order_model->orderTranslationForProduct(
                        $productId,
                        $store_id,
                        $store_from_id,
                        $level,
                        $instructions,
                        $translate_fields,
                        $force_translate);

                    if (true !== $status) {
                        break;
                    }
                }
            }

            if ($status === true) {
                $this->_outputSuccessJson($order_model::MSG_ORDER_SUCCESS);
                return true;
            } else {
                $error_msg =
                    Transfluent_Translate_Exception_Base
                        ::create('ETransfluentOrderFail')
                        ->getMessage();
            }
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }

        $this->_outputErrorJson($error_msg);
        return false;
    }

    /**
     * @return bool
     */
    public function tag_orderAction() {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $instructions = $this->getRequest()->getParam('instructions') ? $this->getRequest()->getParam('instructions') : '';
        $level = intval($this->getRequest()->getParam('level'));
        $store_from_id = intval($this->getRequest()->getParam('from_store'));
        $store_to_id = intval($this->getRequest()->getParam('to_store'));

        $tags = $this->getRequest()->getParam('tags');

        if ($store_to_id === $store_from_id) {
            $e = new Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        if (empty($tags)) {
            $e = new Transfluent_Translate_Exception_ETagNotFound();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        if (!array_filter($tags, 'intval')) {
            $e = new Transfluent_Translate_Exception_EInvalidTagFormat();
            $this->_outputErrorJson($e->getMessage());
            return false;
        }

        $all_ok = true;
        foreach ($tags AS $tag_id) {
            try {
                $order_model = Mage::getModel('transfluenttranslate/transfluentorder');
                /** @var Transfluent_Translate_Model_Transfluentorder $order_model */

                // ordering translation for tag
                $status = $order_model->orderTranslationForTag(
                    $tag_id,
                    $store_to_id,
                    $store_from_id,
                    $level,
                    $instructions);
                if (true === $status) {
                    $all_ok = $all_ok && true;
                    continue;
                }
            } catch (Exception $e) {
                $this->_outputErrorJson($e->getMessage());
                return false;
            }
            $all_ok = false;
        }
        if ($all_ok) {
            $this->_outputSuccessJson("All done! Thank you for the order.");
            return true;
        }
    }

    /**
     * order translation by attribute
     */
    public function attribute_orderAction() {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $instructions = $this->getRequest()->getParam('instructions')
            ? $this->getRequest()->getParam('instructions')
            : '';
        $translate_name = $this->getRequest()->getParam('translate_name');
        $translate_values = $this->getRequest()->getParam('translate_values');
        $attribute_id = intval($this->getRequest()->getParam('attribute_id'));
        $level = intval($this->getRequest()->getParam('level'));
        $stores = $this->getRequest()->getParam('stores');
        $translate_from_store_id = intval($this->getRequest()->getParam('from_store'));

        if (empty($stores)) {
            $this->getResponse()
                ->setBody(
                    '<div class="notification-global">'
                    . 'You did select any target languages. '
                    . 'Please pick at least one target language.</div>');

            return false;
        }
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */

        $source_language_id = $language_helper->GetStoreLocale($translate_from_store_id);


        $output_html = "";
        $all_ok = true;
        foreach ($stores AS $store_id) {
            /** @var Mage_Core_Model_Store $store */
            $store_language = $language_helper->GetStoreLocale($store_id);
            if ($source_language_id == $store_language) {
                continue;
            }
            $output_html .= '<strong>'
                . $language_helper->getLanguageNameByCode($source_language_id, true)
                . '-'
                . $language_helper->getLanguageNameByCode($store_language, true)
                . ':</strong> ';
            try {
                $order_model = Mage::getModel('transfluenttranslate/transfluentorder');
                /** @var Transfluent_Translate_Model_Transfluentorder $order_model */

                // ordering translation for attribute
                $status = $order_model->orderTranslationForAttribute(
                    $attribute_id,
                    $store_id,
                    $translate_from_store_id,
                    $level,
                    $instructions,
                    (boolean)$translate_name,
                    (boolean)$translate_values);
                if (true === $status) {
                    $output_html .= '<ul class="messages"><li class="success-msg"><ul><li><span>Order placed successfully!</span></li></ul></li></ul>';
                    $all_ok = $all_ok && true;
                    continue;
                }
            } catch (Transfluent_Translate_Exception_Base $e) {
                $this->getResponse()->setBody(
                    '<div class="notification-global">' . $e->getMessage() . '</div>');
            } catch (Exception $e) {
                $this->getResponse()->setBody(
                    '<div class="notification-global">' . htmlspecialchars($e->getMessage()) . '</div>');
            }
            $all_ok = false;
        }
        if ($all_ok) {
            $output_html .= '<ul class="messages"><li class="success-msg"><ul><li><span>All done! Thank you for the order.</span></li></ul></li></ul>';
            $this->getResponse()->setBody($output_html);
        }
        return $all_ok;
    }


    /**
     * order by category
     */
    public function category_orderAction() {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $instructions = $this->getRequest()->getParam('instructions')
            ? $this->getRequest()->getParam('instructions')
            : '';
        $translate_name = $this->getRequest()->getParam('translate_name');
        $translate_desc = $this->getRequest()->getParam('translate_desc');
        $translate_meta = $this->getRequest()->getParam('translate_meta');
        $translate_subcat = $this->getRequest()->getParam('translate_subcat');
        $category_id = intval($this->getRequest()->getParam('category_id'));
        $level = intval($this->getRequest()->getParam('level'));
        $stores = $this->getRequest()->getParam('stores');

        if (empty($stores)) {
            $this->getResponse()->setBody(
                '<div class="notification-global">You did select any target languages. Please pick at least one target language.</div>');
            return;
        }
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $translate_from_store_id = $this->getRequest()->getParam('from_store');
        $source_language_id = $language_helper->GetStoreLocale($translate_from_store_id);

        $all_ok = true;
        $output_html = "";
        foreach ($stores AS $store_id) {
            /** @var Mage_Core_Model_Store $store */
            $store_language = $language_helper->GetStoreLocale($store_id);
            if ($source_language_id == $store_language) {
                continue;
            }
            $output_html .= '<strong>'
                . $language_helper->getLanguageNameByCode($source_language_id, true)
                . '-'
                . $language_helper->getLanguageNameByCode($store_language, true)
                . ':</strong> ';
            try {
                $order_model = Mage::getModel('transfluenttranslate/transfluentorder');
                /** @var Transfluent_Translate_Model_Transfluentorder $order_model */

                // ordering translation for category
                $status = $order_model->orderTranslationForCategory(
                    $category_id,
                    $store_id,
                    $translate_from_store_id,
                    $level,
                    $instructions,
                    (boolean)$translate_name,
                    (boolean)$translate_desc,
                    (boolean)$translate_meta,
                    (boolean)$translate_subcat);
                if ($status === true) {
                    $output_html .= '<ul class="messages"><li class="success-msg"><ul><li><span>Order placed successfully!</span></li></ul></li></ul>';
                    $all_ok = $all_ok && true;
                    continue;
                }
            } catch (Transfluent_Translate_Exception_Base $e) {
                $this->getResponse()->setBody(
                    '<div class="notification-global">' . $e->getMessage() . '</div>');
            } catch (Exception $e) {
                $this->getResponse()->setBody(
                    '<div class="notification-global">' . htmlspecialchars($e->getMessage()) . '</div>');
            }
            $all_ok = false;
        }
        if ($all_ok) {
            $output_html .= '<ul class="messages"><li class="success-msg"><ul><li><span>All done! Thank you for the order.</span></li></ul></li></ul>';
            $this->getResponse()->setBody($output_html);
        }
    }


    public function grid_orderAction() {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $force_translate = $this->getRequest()->getParam('force_translate');
        $level = $this->getRequest()->getParam('level');
        $store_id = $this->getRequest()->getParam('store_to');
        $store_from_id = $this->getRequest()->getParam('translate_from');
        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */
        $products = $this->getRequest()->getParam('products');
        $instructions = $this->getRequest()->getParam('instructions')
            ? $this->getRequest()->getParam('instructions')
            : '';

        $fields_to_translate_in = $this->getRequest()->getParam('fields_to_translate');
        $translate_fields = array();
        if (empty($fields_to_translate_in)) {
            foreach ($language_helper->DefaultProductFieldsToTranslate() AS $default_field_to_translate) {
                $translate_fields[] = $default_field_to_translate;
            }
        } else {
            $translate_fields = $fields_to_translate_in;
        }

        $success_count = 0;
        foreach ($products as $product_id) {
            try {
                $order_model = Mage::getModel('transfluenttranslate/transfluentorder');
                /** @var Transfluent_Translate_Model_Transfluentorder $order_model */

                // ordering translation for product
                $status = $order_model->orderTranslationForProduct(
                    $product_id,
                    $store_id,
                    $store_from_id,
                    $level,
                    $instructions,
                    $translate_fields,
                    $force_translate);

                if (true === $status) {
                    $success_count++;
                }
            } catch (Transfluent_Translate_Exception_ETransfluentProductNotFoundBase $e) {
                $e = new Transfluent_Translate_Exception_ETransfluentSomeSelectedProductsNotFoundBase();
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('adminhtml')->__($e->getMessage()));
            } catch (Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase $e) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('adminhtml')->__($e->getMessage()));
                break;
            } catch (Transfluent_Translate_Exception_Base $e) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(Mage::helper('adminhtml')->__($e->getMessage()));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(($e->getMessage()
                        ? $e->getMessage()
                        : 'An unknown error occurred. Please contact Transfluent\'s support (support@transfluent.com).'));
            }
        }

        if (count($products) == $success_count) {
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('adminhtml')->__('Thank you for the order!'));
        } else if ($success_count > 0) {
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('adminhtml')->__('Some product were ordered successfully but there was issues with others. Thank you for the order!'));
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addNotice(Mage::helper('adminhtml')->__('Failed to place order! Please see details above.'));
        }
    }

    public function orderByCategoryStep5Action($init_layout = true) {
        if ($init_layout) {
            $this->_initAction();
        }

        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        $quote_id = $this->getRequest()->getParam('quote_id');
        $source = $this->getRequest()->getParam('source');
        $instructions = $this->getRequest()->getParam('instructions');
        $instructions .= PHP_EOL . PHP_EOL . 'Text is from webstore: ' . Mage::app()->getStore($source)->getBaseUrl() . PHP_EOL . PHP_EOL;

        $data = $translate->OrderCategoryQuote($quote_id, $instructions);
        if (!$data || $data['status'] != 'OK') {
            $this->getLayout()->getMessagesBlock()->addError('Failed to place the order. Error: ' . @$data['error']['message']);
            $this->orderByCategoryStep3Action(false);
            return;
        }

        /*
        if (!isset($data['response']['status_code']) || $data['response']['status_code'] != 3) {
            $this->getLayout()->getMessagesBlock()->addError('Something unexpected happened while processing the order. Please contact support@transfluent.com to resolve the situation.');
            $this->orderByCategoryStep3Action(false);
            return;
        }*/

        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/category_step5.phtml')->setData('fork_id', $data['response'])->setData('quote_id', $quote_id)
        );
        $this->renderLayout();
    }

    public function orderByCategoryStep4Action($init_layout = true) {
        if ($init_layout) {
            $this->_initAction();
        }

        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/category_step4.phtml')
        );
        $this->renderLayout();
    }

    public function orderFromCmsStep5Action() {
        $this->_initAction();

        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        $quote_id = $this->getRequest()->getParam('quote_id');
        $source = $this->getRequest()->getParam('source');
        $instructions = $this->getRequest()->getParam('instructions');
        $instructions .= PHP_EOL . PHP_EOL . 'Text is from webstore: ' . Mage::app()->getStore($source)->getBaseUrl() . PHP_EOL . PHP_EOL;

        $data = $translate->OrderCmsQuote($quote_id, $instructions);
        if (!$data || $data['status'] != 'OK') {
            $this->getLayout()->getMessagesBlock()->addError('Failed to place the order. Error: ' . @$data['error']['message']);
            $this->orderFromCmsStep3Action(false);
            return;
        }

        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/cms_step5.phtml')->setData('fork_id', $data['response'])->setData('quote_id', $quote_id)
        );
        $this->renderLayout();
    }

    public function orderFromCmsStep3Action($init_layout = true) {
        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        $quote_id = $this->getRequest()->getParam('quote_id');
        $target = $this->getRequest()->getParam('target');
        $source = $this->getRequest()->getParam('source');
        $cookie = Mage::getSingleton('core/cookie');
        /** @var Mage_Core_Model_Cookie $cookie */

        if ($quote_id && $this->getRequest()->getParam('isAjax')) {
            $data = $translate->GetCategoryQuote($quote_id);
            $this->getResponse()
                ->setBody(
                    Mage::helper('core')->jsonEncode($data['response']))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
            return;
        } else if ($cookie->get('_tf_restore_quote')) {
            $cookie->delete('_tf_restore_quote', '/');
            $quote_data = unserialize($cookie->get('_tf_restore_quote'));
            $quote_id = $quote_data['quote_id'];
            $target = (int)$quote_data['target'];
            $source = (int)$quote_data['source'];
        }

        if ($init_layout) {
            // Preserve any pre-generated errors
            $this->_initAction();
        }

        $translate_blocks = $this->getRequest()->getParam('translate_blocks');
        $translate_pages = $this->getRequest()->getParam('translate_pages');
        $all_cms_page_ids = $this->getRequest()->getParam('cms_page_ids') ?: array();
        $all_cms_block_ids = $this->getRequest()->getParam('cms_block_ids') ?: array();

        if (!$quote_id && (!$translate_blocks || !$translate_pages)) {
            if (empty($all_cms_page_ids) && empty($all_cms_block_ids)) {
                $this->orderFromCmsStep2Action(false);
                return;
            }
        }

        $cms_page_model = Mage::getModel('cms/page');
        if ($translate_pages) {
            $all_cms_page_ids = $cms_page_model->getCollection()->getAllIds();
        }
        $cms_page_ids_str = implode(",", $all_cms_page_ids);

        $cms_block_model = Mage::getModel('cms/block');
        if ($translate_blocks) {
            $all_cms_block_ids = $cms_block_model->getCollection()->getAllIds();
        }
        $cms_block_ids_str = implode(",", $all_cms_block_ids);

        $source_store = Mage::app()->getStore($source);
        $target_store = Mage::app()->getStore($target);
        $level = $this->getRequest()->getParam('level');
        $collision_strategy = $this->getRequest()->getParam('collision');

        if (!$quote_id) {
            /** @var Transfluent_Translate_Helper_Languages $languageHelper */
            $languageHelper = Mage::helper('transfluenttranslate/languages');
            $source_store_locale_code = $languageHelper->GetStoreLocale($source_store->getCode());
            $target_store_locale_code = $languageHelper->GetStoreLocale($target_store->getCode());
            $data = $translate->CreateCmsContentQuote($source, $source_store_locale_code, $target, $target_store_locale_code, $level, $collision_strategy, $cms_page_ids_str, $cms_block_ids_str);
        }

        $block = $this->getLayout()->createBlock('transfluenttranslate/adminhtml_transfluentorder')->setTemplate('transfluent/order/cms_step3.phtml');

        if (!$quote_id && $data['status'] == 'ERROR') {
            $block->setData('quote_id', null);
            $this->getLayout()->getMessagesBlock()->addError($data['error']['message']);
        } else if (!$quote_id) {
            $quote_id = $data['response'];
            $block->setData('quote_id', $quote_id);
        } else {
            $block->setData('quote_id', $quote_id);
        }

        $this->_addContent($block);
        $this->renderLayout();
    }

    public function orderFromCmsStep2Action($init_layout = true) {
        if ($init_layout) {
            $this->_initAction();
        }

        $target = $this->getRequest()->getParam('target');
        if (!$target) {
            $this->getLayout()->getMessagesBlock()->addError('Please select a target language&store for translations!');
            $this->orderByCategoryStep1Action(false);
            return;
        }
        $source = $this->getRequest()->getParam('source');
        if ($source == $target) {
            $this->getLayout()->getMessagesBlock()->addError('You can not translate into the source store as each store may have only one locale. You need a pair of stores in different languages, please refer our getting started guide for Magento to do that.');
            $this->orderByCategoryStep1Action(false);
            return;
        }

        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/cms_step2.phtml')
        );
        $this->renderLayout();
    }

    public function orderFromCmsStep1Action($init_layout = true) {
        if ($init_layout) {
            $this->_initAction();
        }

        if (!Mage::getStoreConfig('transfluenttranslate/account/token')) {
            $login_url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'transfluenttranslate'));
            $this->getLayout()->getMessagesBlock()->addError('Please login or create an account <a href="' . $login_url . '">first</a>!');
        }
        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/cms_step1.phtml')
        );
        $this->renderLayout();
    }

    public function orderByCategoryStep1Action($init_layout = true) {
        if ($init_layout) {
            $this->_initAction();
        }

        if (!Mage::getStoreConfig('transfluenttranslate/account/token')) {
            $login_url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'transfluenttranslate'));
            $this->getLayout()->getMessagesBlock()->addError('Please login or create an account <a href="' . $login_url . '">first</a>!');
        }
        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/category_step1.phtml')
        );
        $this->renderLayout();
    }

    public function orderByCategoryStep2Action() {
        $this->_initAction();

        $target = $this->getRequest()->getParam('target');
        if (!$target) {
            $this->getLayout()->getMessagesBlock()->addError('Please select a target language&store for translations!');
            $this->orderByCategoryStep1Action(false);
            return;
        }
        $source = $this->getRequest()->getParam('source');
        if ($source == $target) {
            $this->getLayout()->getMessagesBlock()->addError('You can not translate into the source store as each store may have only one locale. You need a pair of stores in different languages, please refer our getting started guide for Magento to do that.');
            $this->orderByCategoryStep1Action(false);
            return;
        }
        $source_store = Mage::app()->getStore($source);
        $target_store = Mage::app()->getStore($target);
        if ($source_store->getRootCategoryId() != $target_store->getRootCategoryId()) {
            $this->getLayout()->getMessagesBlock()->addError('The source store and target store MUST have a common root category!');
            $this->orderByCategoryStep1Action(false);
            return;
        }

        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/category_step2.phtml')
        );
        $this->renderLayout();
    }

    public function orderByCategoryStep3Action($init_layout = true) {
        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        $quote_id = $this->getRequest()->getParam('quote_id');

        if ($quote_id && $this->getRequest()->getParam('isAjax')) {
            $data = $translate->GetCategoryQuote($quote_id);
            $this->getResponse()
                ->setBody(
                    Mage::helper('core')->jsonEncode($data['response']))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
            return;
        }

        if ($init_layout) {
            // Preserve any pre-generated errors
            $this->_initAction();
        }

        if ($this->getRequest()->getParam('update_quote_btn')) {
            if (!$this->getRequest()->getParam('translate_fields')) {
                $this->getLayout()->getMessagesBlock()->addError('Please select at least one product field to translate!');
            } else {
                $data = $translate->UpdateCategoryQuote($quote_id, $this->getRequest()->getParam('translate_fields'));
            }
        }

        $target = $this->getRequest()->getParam('target');
        $source = $this->getRequest()->getParam('source');
        $source_store = Mage::app()->getStore($source);
        $target_store = Mage::app()->getStore($target);
        $level = $this->getRequest()->getParam('level');
        $collision_strategy = $this->getRequest()->getParam('collision');

        $categories = $this->getRequest()->getParam('chk_group');
        if (empty($categories)) {
            $this->getLayout()->getMessagesBlock()->addError('Please select at least one category!');
            $this->orderByCategoryStep2Action(false);
            return;
        }

        if (!$quote_id) {
            /** @var Transfluent_Translate_Helper_Languages $languageHelper */
            $languageHelper = Mage::helper('transfluenttranslate/languages');
            $source_store_locale_code = $languageHelper->GetStoreLocale($source_store->getCode());
            $target_store_locale_code = $languageHelper->GetStoreLocale($target_store->getCode());
            $data = $translate->CreateCategoryQuote($source, $source_store_locale_code, $target, $target_store_locale_code, $level, $collision_strategy, $this->getRequest()->getParam('chk_group'));
        }

        $block = $this->getLayout()->createBlock('transfluenttranslate/adminhtml_transfluentorder')->setTemplate('transfluent/order/category_step3.phtml');

        if (!$quote_id && $data['status'] == 'ERROR') {
            $block->setData('quote_id', null);
            $this->getLayout()->getMessagesBlock()->addError($data['error']['message']);
        } else if (!$quote_id) {
            $quote_id = $data['response'];
            $block->setData('quote_id', $quote_id);
        } else {
            $block->setData('quote_id', $quote_id);
        }

        $this->_addContent($block);
        $this->renderLayout();
    }

    public function formAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder_edit_form')
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
            ->setBody(
                Mage::helper('transfluenttranslate/util')
                    ->getSuccessJson($message))
            ->setHeader('Content-type', 'application/json', true);
    }
}
