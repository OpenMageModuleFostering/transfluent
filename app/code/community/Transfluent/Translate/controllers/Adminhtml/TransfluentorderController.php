<?php

/**
 * Class Transfluent_Translate_Adminhtml_TransfluentorderController
 */
class Transfluent_Translate_Adminhtml_TransfluentorderController extends Mage_Adminhtml_Controller_Action {
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

        $instructions = $this->getRequest()->getParam('instructions') ? : '';
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

    public function getquoteAction() {
        $this->_initAction();

        $this->_addContent(
            $this
                ->getLayout()
                ->createBlock('transfluenttranslate/adminhtml_transfluentorder')
                ->setTemplate('transfluent/order/order.phtml')
        );
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
