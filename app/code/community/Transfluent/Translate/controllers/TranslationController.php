<?php

class Transfluent_Translate_TranslationController extends Mage_Core_Controller_Front_Action {
    private $_handlers = array(
        "/store\-([0-9]{1,})\-tag\-([0-9]{1,})/" => '_saveTagName',
        "/store\-([0-9]{1,})\-product\-([0-9]{1,})\-(.*)/" => '_saveProductDetails',
        "/store\-([0-9]{1,})\-attribute\-([0-9]{1,})\-option\-([0-9]{1,})/" => '_saveAttributeOptions',
        "/store\-([0-9]{1,})\-attribute\-([0-9]{1,})/" => '_saveAttributeName',
        "/store\-([0-9]{1,})\-category\-([0-9]{1,})\-name/" => '_saveCategoryName',
        "/store\-([0-9]{1,})\-category\-([0-9]{1,})\-description/" => '_saveCategoryDescription',
        "/store\-([0-9]{1,})\-category\-([0-9]{1,})\-meta\-title/" => '_saveCategoryMetaTitle',
        "/store\-([0-9]{1,})\-category\-([0-9]{1,})\-meta\-keywords/" => '_saveCategoryMetaKeywords',
        "/store\-([0-9]{1,})\-category\-([0-9]{1,})\-meta\-description/" => '_saveCategoryMetaDescription',
        "/store\-([0-9]{1,})\-([0-9]{1,})\-cms\-page\-([0-9]{1,})\-(.*)/" => '_saveCmsPageDetails',
        "/store\-([0-9]{1,})\-([0-9]{1,})\-cms\-block\-([0-9]{1,})\-(.*)/" => '_saveCmsBlockDetails',
    );

    private function serveRequest($payload) {
        $status = true;
        if ($payload instanceof Exception) {
            $payload = array(
                'status' => 'ERROR',
                'error' => array(
                    'type' => get_class($payload),
                    'message' => $payload->getMessage()
                )
            );
            $status = false;
        }
        $this->getResponse()
            ->setBody(
                Mage::helper('core')->jsonEncode($payload))
            ->setHttpResponseCode($status === true ? 200 : 400)
            ->setHeader('Content-type', 'application/json', true);
    }

    public function getProductIdsAction() {
        try {
            $this->_validateToken();

            $category_id = $this->getRequest()->getParam('category_id');
            $store = $this->getRequest()->getParam('store');
            /** @var Mage_Catalog_Model_Category $cat */
            $cat = Mage::getModel('catalog/category')->setStoreId($store);
            $cat->load($category_id);
            $product_ids = array();
            if ($cat->getProductCount() > 0) {
                $model = Mage::getModel('catalog/product')->setStoreId($store);
                /** @var Mage_Catalog_Model_Resource_Product_Collection $products */
                $products = $model->getCollection();
                $products->addStoreFilter($store);
                $products->addCategoryFilter($cat);
                $product_ids = $products->getAllIds();
            }
            $response = array(
                'products' => $product_ids
            );
        } catch (Exception $e) {
            $response = $e;
        }
        $this->serveRequest($response);
    }

    public function preDispatch() {
        if ($this->getRequest()->getActionName() == 'returnToQuote') {
            // Make sure admin session gets initialized before normal dispatch routine, otherwise admin is always logged out
            Mage::getSingleton('core/session', array('name'=>'adminhtml'));
        }
        parent::preDispatch();
    }

    public function returnToQuoteAction() {
        $source_store_id = $this->getRequest()->getParam('source_store');
        $target_store_id = $this->getRequest()->getParam('target_store');
        $quote_id = $this->getRequest()->getParam('quote_id');
        $quote_type = $this->getRequest()->getParam('type');

        $mage_admin_url = Mage::getModel('adminhtml/url');
        /** @var Mage_Adminhtml_Model_Url $mage_admin_url */
        $mage_admin_url->setStore(0);

        $admin_html_helper = Mage::helper('adminhtml');
        /** @var Mage_Adminhtml_Helper_Data $admin_html_helper */

        if ($admin_html_helper->getCurrentUserId()) {
            // LOGGED-IN: REDIRECT TO: BASE + transfluent/adminhtml_transfluentorder/redirectToQuote/quote_id/HkvrSz7z/source_store/1/target_store/4/
            $quote_order_step3_url = $mage_admin_url->getUrl('adminhtml/Adminhtml_Transfluentorder/redirectToQuote', array('source_store' => $source_store_id, 'target_store' => $target_store_id, 'quote_id' => $quote_id, 'type' => $quote_type));
            $this->getResponse()->setRedirect($quote_order_step3_url);
            return;
        }
        // NOT LOGGED IN: SAVE QUOTE DETAILS INTO A COOKIE AND THEN REDIRECT TO PLAIN STEP URL (LOGIN CLEARS ANY REQUEST OR ROUTE PARAMETERS)
        $cookie = Mage::getSingleton('core/cookie');
        /** @var Mage_Core_Model_Cookie $cookie */
        $cookie->set('_tf_restore_quote', serialize(array('source' => $source_store_id, 'target' => $target_store_id, 'quote_id' => $quote_id)), null, '/');
        //$uri_action = ($quote_type == 'category' ? 'orderByCategoryStep3' : 'orderFromCmsStep3'); // CMS view handles both of them for now..
        $uri_action = 'orderFromCmsStep3';
        $quote_order_step3_url = $mage_admin_url->getUrl('adminhtml/Adminhtml_Transfluentorder/' . $uri_action);
        $this->getResponse()->setRedirect($quote_order_step3_url);
    }

    public function getCmsPageAction() {
        try {
            $this->_validateToken();
            $page_id = $this->getRequest()->getParam('page_id');
            $collision = $this->getRequest()->getParam('collision');

            $target_store_id = $this->getRequest()->getParam('target_store');
            $source_store_id = $this->getRequest()->getParam('source_store');
            $cms_page_model = Mage::getModel('cms/page');
            /** @var Mage_Cms_Model_Page $cms_page_model */
            $page = $cms_page_model->setStoreId($source_store_id)->load($page_id);
            /** @var Mage_Cms_Model_Page $page */
            if ($page->isEmpty() || $page->isObjectNew()) {
                throw new Exception('PAGE_NOT_ASSOCIATED_WITH_STORE');
            }

            if ($collision != 'overwrite') {
                $cms_page_collection = $cms_page_model->getCollection();
                /** @var Mage_Cms_Model_Resource_Page_Collection $cms_page_collection */
                $cms_page_collection->addFilter('identifier', $page->getIdentifier());
                if ($cms_page_collection->count() != 1) {
                    $tmp_cms_page_model = Mage::getModel('cms/page');
                    /** @var Mage_Cms_Model_Page $cms_page_model */
                    foreach ($cms_page_collection AS $tmp_page) {
                        /** @var Mage_Cms_Model_Page $tmp_page */
                        $tmp_page = $tmp_cms_page_model->setStoreId($target_store_id)->load($tmp_page->getId());
                        if ($tmp_page->isObjectNew() || $tmp_page->isEmpty() || $tmp_page->getId() == $page->getId()) {
                            continue;
                        }
                        switch ($collision) {
                            case 'source':
                                // Use page in target store as source for translation
                                // ..page is bound to target store view, use as source!
                                $page = $tmp_page;
                                break 2;
                            default:
                            case 'translated':
                                // Assume page is already translated [if it has been bound to target store view]
                                throw new Exception('PAGE_ALREADY_TRANSLATED');
                        }
                    }
                }
            }

            $page_url = Mage::app()->getStore($source_store_id)->getUrl($page->getIdentifier());
            $response = array(
                'id' => $page_id,
                'source_id' => $page->getId(),
                'is_active' => $page->getIsActive(),
                'title' => $page->getTitle(),
                'meta' => array(
                    'keywords' => $page->getMetaKeywords(),
                    'description' => $page->getMetaDescription(),
                ),
                'identifier' => $page->getIdentifier(),
                'url' => $page_url,
                'content' => $page->getContent(),
                'store_id' => $page->store_id
            );
        } catch (Exception $e) {
            $response = $e;
        }
        $this->serveRequest($response);
    }

    public function getCmsBlockAction() {
        try {
            $this->_validateToken();
            $block_id = $this->getRequest()->getParam('block_id');
            $collision = $this->getRequest()->getParam('collision');

            $target_store_id = $this->getRequest()->getParam('target_store');
            $source_store_id = $this->getRequest()->getParam('source_store');
            $cms_block_model = Mage::getModel('cms/block');
            /** @var Mage_Cms_Model_Block $cms_block_model */
            $block = $cms_block_model->setStoreId($source_store_id)->load($block_id);
            /** @var Mage_Cms_Model_Block $block */
            if ($block->isEmpty() || $block->isObjectNew()) {
                throw new Exception('BLOCK_NOT_ASSOCIATED_WITH_STORE');
            }

            if ($collision != 'overwrite') {
                $cms_block_collection = $cms_block_model->getCollection();
                /** @var Mage_Cms_Model_Resource_Block_Collection $cms_block_collection */
                $cms_block_collection->addFilter('identifier', $block->getIdentifier());
                if ($cms_block_collection->count() != 1) {
                    $tmp_cms_block_model = Mage::getModel('cms/block');
                    /** @var Mage_Cms_Model_Block $cms_block_model */
                    foreach ($cms_block_collection AS $tmp_block) {
                        /** @var Mage_Cms_Model_Block $tmp_block */
                        $tmp_block = $tmp_cms_block_model->setStoreId($target_store_id)->load($tmp_block->getId());
                        if ($tmp_block->isObjectNew() || $tmp_block->isEmpty() || $tmp_block->getId() == $block->getId()) {
                            continue;
                        }
                        switch ($collision) {
                            case 'source':
                                // Use block in target store as source for translation
                                // ..block is bound to target store view, use as source!
                                $block = $tmp_block;
                                break 2;
                            default:
                            case 'translated':
                                // Assume block is already translated [if it has been bound to target store view]
                                throw new Exception('BLOCK_ALREADY_TRANSLATED');
                        }
                    }
                }
            }

            $response = array(
                'id' => $block_id,
                'source_id' => $block->getId(),
                'is_active' => $block->getIsActive(),
                'title' => $block->getTitle(),
                'identifier' => $block->getIdentifier(),
                'content' => $block->getContent(),
                'store_id' => $block->store_id
            );
        } catch (Exception $e) {
            $response = $e;
        }
        $this->serveRequest($response);
    }

    public function getProductDetailsAction() {
        try {
            $this->_validateToken();
            $product_ids_str = $this->getRequest()->getParam('product_ids');
            $product_fields_str = $this->getRequest()->getParam('translate_fields');
            $collision = $this->getRequest()->getParam('collision');
            $product_ids = explode(",", $product_ids_str);
            $product_fields = explode(",", $product_fields_str);
            $target_store_id = $this->getRequest()->getParam('target_store');
            $source_store_id = $this->getRequest()->getParam('source_store');
            $product_model = Mage::getModel('catalog/product');
            /** @var Mage_Catalog_Model_Product $product_model */
            $master_product = Mage::getModel('catalog/product');
            /** @var Mage_Catalog_Model_Product $product_model */

            $available_fields = array();
            $products_out = array();
            foreach ($product_ids AS $product_id) {
                $product = $product_model->setStoreId($target_store_id)->load($product_id);
                /** @var Mage_Catalog_Model_Product $product */
                $master_product = $master_product->setStoreId($source_store_id)->load($product_id);
                /** @var Mage_Catalog_Model_Product $master_product */
                if (!$product) { // should be $product->isObjectNew() || $product->isEmpty() ?
                    continue;
                }
                /** @var Mage_Catalog_Model_Product $product */
                if (empty($available_fields)) {
                    $available_fields = array_keys($product->getData());
                }

                $product_data = array("id" => $product_id);
                foreach ($product_fields AS $product_field) {
                    if ($product->getExistsStoreValueFlag($product_field)) {
                        if ($collision == 'translated') {
                            // Skip value: assume it has been already translated
                            continue;
                        } else if ($collision == 'overwrite') {
                            // Delete: Overwrite store specific values with new translations
                            $product_data[$product_field] = $master_product->getData($product_field);
                            continue;
                        }
                    }
                    $product_data[$product_field] = $product->getData($product_field);
                }
                $product_data['exists'] = !$master_product->isObjectNew();
                $product_data['url'] = $master_product->getUrlInStore();
                $products_out[] = $product_data;
            }

            $response = array(
                'product_details' => $products_out,
                'available_fields' => $available_fields
            );
        } catch (Exception $e) {
            $response = $e;
        }
        $this->serveRequest($response);
    }

    public function getCategoriesAction() {
        try {
            $this->_validateToken();

            $store = $this->getRequest()->getParam('store');
            $category_ids_str = $this->getRequest()->getParam('category_ids');
            $category_ids = explode(",", $category_ids_str);

            $categories_out = array();
            $ExtractCategoryData = function($category_id, $parent_cat_id = null) use (&$ExtractCategoryData, &$categories_out, $store) {
                /** @var Mage_Catalog_Model_Category $cat */
                $cat = Mage::getModel('catalog/category')->setStoreId($store);
                $cat->load($category_id);

                $categories_out[$category_id] = array(
                    'name' => $cat->getName(),
                    'product_count' => $cat->getProductCount(),
                );
                if ($parent_cat_id) {
                    $categories_out[$category_id]['parent_id'] = $category_id;
                }
                $cat_children_ids = $cat->getAllChildren(true);
                foreach ($cat_children_ids AS $cat_children_id) {
                    if ($cat_children_id == $category_id) {
                        continue;
                    }
                    $ExtractCategoryData($cat_children_id, $category_id);
                }
            };

            foreach ($category_ids AS $category_id) {
                $ExtractCategoryData($category_id);
            }

            $response = array(
                'categories' => $categories_out
            );
        } catch (Exception $e) {
            $response = $e;
        }
        $this->serveRequest($response);
    }

    public function pingAction() {
        $this->getResponse()
            ->setBody(
                Mage::helper('core')->jsonEncode(array("time" => time())))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
    }

    public function saveAction() {
        try {
            $this->_validateToken();

            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

            list($payload, $text_id) = $this->_parseRequest();
            $status = $this->_handleRequest($text_id, $payload);

            if ($status === true) {
                $response = array('status' => 'OK');
            } else {
                $response = array(
                    'status' => 'ERROR',
                    'error' => array(
                        'type' => 'N/A',
                        'message' => 'An unexpected error occurred.'
                    )
                );
            }
        } catch (Exception $e) {
            $response = array(
                'status' => 'ERROR',
                'error' => array(
                    'type' => get_class($e),
                    'message' => $e->getMessage()
                )
            );
            $status = false;
        }

        $this->getResponse()
            ->setBody(
                Mage::helper('core')->jsonEncode($response))
            ->setHttpResponseCode($status === true ? 200 : 500)
            ->setHeader('Content-type', 'application/json', true);
    }


    private function _validateToken() {
        $token_str = Mage::getStoreConfig('transfluenttranslate/account/token');
        if (!$token_str) {
            throw new Transfluent_Translate_Exception_EUnauthorized();
        }
        $token_hash = md5($token_str);
        $token_hash_in = $this->getRequest()->getParam('th');
        if (!$token_hash_in || $token_hash_in != $token_hash) {
            throw new Transfluent_Translate_Exception_EUnauthorized();
        }
    }

    private function _parseRequest() {
        $request_body = file_get_contents('php://input');
        $payload = Mage::helper('core')->jsonDecode($request_body, true);
        if (!isset($payload)) {
            throw new Transfluent_Translate_Exception_EInvalidInput();
        }
        if (@$payload['group_id'] != 'Magento') {
            throw new Transfluent_Translate_Exception_EInvalidJob();
        }
        $text_id = $payload['text_id'];
        return array($payload, $text_id);
    }

    private function _handleRequest($text_id, $payload) {
        $status = null;
        foreach ($this->_handlers AS $pattern => $method_to_call) {
            $matches = null;
            if (preg_match($pattern, $text_id, $matches)) {
                $status = call_user_func(
                    array($this, $method_to_call),
                    $matches,
                    $payload,
                    $text_id);
                break;
            }
        }
        if ($status === null)
            throw new Transfluent_Translate_Exception_ETransfluentInvalidInputTagsBase();

        return $status;
    }

    private function _saveCategoryName($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $category_id = $matches[2];

        $translated_str = trim($payload['text']);
        return $this->_updateCategoryDetail(
            $category_id,
            $store_id,
            'name',
            $translated_str,
            $text_id,
            $payload);
    }

    private function _saveCategoryDescription($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $category_id = $matches[2];

        $translated_str = trim($payload['text']);
        return $this->_updateCategoryDetail(
            $category_id,
            $store_id,
            'description',
            $translated_str,
            $text_id,
            $payload);
    }

    private function _saveCategoryMetaTitle($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $category_id = $matches[2];

        $translated_str = trim($payload['text']);
        return $this->_updateCategoryDetail(
            $category_id,
            $store_id,
            'meta_title',
            $translated_str,
            $text_id,
            $payload);
    }

    private function _saveCategoryMetaKeywords($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $category_id = $matches[2];

        $translated_str = trim($payload['text']);
        return $this->_updateCategoryDetail(
            $category_id,
            $store_id,
            'meta_keywords',
            $translated_str,
            $text_id,
            $payload);
    }

    private function _saveCategoryMetaDescription($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $category_id = $matches[2];

        $translated_str = trim($payload['text']);
        return $this->_updateCategoryDetail(
            $category_id,
            $store_id,
            'meta_description',
            $translated_str,
            $text_id,
            $payload);
    }

    private function _updateCategoryDetail($category_id, $store_id, $key, $value, $text_id, $payload) {
        $category_model = Mage::getModel('catalog/category');
        /** @var Mage_Catalog_Model_Category $category_model */
        $category = $category_model->setStoreId($store_id)->load($category_id);
        if (!$category || !$category->getId()) {
            throw new Transfluent_Translate_Exception_ETransfluentProductNotFoundBase();
        }
        /** @var Mage_Catalog_Model_Category $category */
        $category->setData($key, $value);
        $category->save();
        return $this->_updateOrder($store_id, $text_id, $payload['level']);
    }

    private function _saveTagName($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $tag_id = $matches[2];

        $translated_tag_str = trim($payload['text']);

        $source_tag = Mage::getModel('tag/tag')->load($tag_id);
        /** @var Mage_Tag_Model_Tag $source_tag */
        /** @var Mage_Tag_Model_Tag $model */
        if (isset($payload['previous_text']) && $payload['previous_text']) {
            $model = Mage::getModel('tag/tag')
                ->setStoreId($store_id)
                ->loadByName($payload['previous_text']);
            Mage::register('isSecureArea', true);
            $model->delete();
            Mage::unregister('isSecureArea');
        }

        $model = Mage::getModel('tag/tag')
            ->setStoreId($store_id)
            ->loadByName($translated_tag_str);
        $was_added = false;
        if ($model && $model->getId() && $model->isAvailableInStore($store_id)) {
            return true;
        } else if (!$model->getId()) {
            $model->setStoreId($store_id);
            $model->setFirstStoreId($store_id);
            $was_added = true;
            $model->setStatus($source_tag->getStatus());
            $model->setAddBasePopularity($source_tag->getAddBasePopularity());
            if ($source_tag->getRatio()) {
                $model->setRatio($source_tag->getRatio());
            }
        }
        $model->setName($translated_tag_str);
        $model->save();

        if ($was_added) {
            foreach ($source_tag->getRelatedProductIds() AS $product_id) {
                $model->saveRelation($product_id, null, $store_id);
            }
        }
        return $this->_updateOrder($store_id, $text_id, $payload['level']);
    }

    private function _saveAttributeName($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $attribute_id = $matches[2];

        $attribute_model = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Entity_Attribute $attribute_model */
        $attribute = $attribute_model->load($attribute_id);
        if (!$attribute) {
            throw new Exception('Attribute not found!');
        }
        $store_labels = $attribute->getStoreLabels();
        $store_labels[$store_id] = $payload['text'];
        $attribute->setData('store_labels', $store_labels);
        $attribute->save();

        Mage::app()->cleanCache(array(Mage_Core_Model_Translate::CACHE_TAG));
        return $this->_updateOrder($store_id, $text_id, $payload['level']);
    }

    private function _saveAttributeOptions($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $attribute_id = $matches[2];
        $option_id = $matches[3];


        $attribute_model = Mage::getModel('catalog/resource_eav_attribute');
        $attribute_model->load($attribute_id);

        $values_by_store = array();
        $stores = Mage::app()->getStores();
        $values_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($attribute_id)
            ->setStoreFilter(0, false)
            ->load();
        foreach ($values_collection as $item) {
            /** @var Mage_Eav_Model_Entity_Attribute_Option $item */
            if ($item->getId() != $option_id) {
                continue;
            }
            $values_by_store[0] = $item->getValue();
        }
        foreach ($stores AS $store) {
            /** @var Mage_Core_Model_Store $store */
            $values_collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($attribute_id)
                ->setStoreFilter($store->getId(), false)
                ->load();
            foreach ($values_collection as $item) {
                /** @var Mage_Eav_Model_Entity_Attribute_Option $item */
                if ($item->getId() != $option_id) {
                    continue;
                }
                $values_by_store[$store->getId()] = $item->getValue();
            }
        }
        $data = array();
        foreach ($values_by_store AS $cur_store_id => $label) {
            if (!$label) {
                continue;
            }
            $data['option']['value'][$option_id][$cur_store_id] = $label;
        }
        $data['option']['value'][$option_id][$store_id] = $payload['text'];
        $attribute_model->addData($data);
        $attribute_model->save();

        Mage::app()->cleanCache(array(Mage_Core_Model_Translate::CACHE_TAG));
        return $this->_updateOrder($store_id, $text_id, $payload['level']);
    }

    private function _saveCmsBlockDetails($matches, $payload, $text_id) {
        $source_store_id = $matches[1];
        $target_store_id = $matches[2];
        $cms_block_id = $matches[3];
        $field_name = $matches[4];

        $cms_block_model = Mage::getModel('cms/block');
        /** @var Mage_Cms_Model_Block $cms_block_model */
        $block = $cms_block_model->setStoreId($source_store_id)->load($cms_block_id);
        /** @var Mage_Cms_Model_Block $block */
        if ($block->isEmpty() || $block->isObjectNew()) {
            throw new Exception('SOURCE_BLOCK_NOT_FOUND');
        }
        $new_store_ids = null;
        if (in_array("0", $block->store_id)) {
            // Block visibility is "0", i.e. all stores
            $all_store_ids = array();
            $websites = Mage::app()->getWebsites();
            foreach ($websites AS $website) {
                /** @var Mage_Core_Model_Website $website */
                $stores = $website->getStores();
                foreach ($stores AS $store) {
                    /** @var Mage_Core_Model_Store $store */
                    if ($store->getId() == $target_store_id) {
                        continue;
                    }
                    $all_store_ids[] = $store->getId();
                }
            }
            $new_store_ids = $all_store_ids;
        } else if (in_array((string)$target_store_id, $block->store_id)) {
            $new_store_ids = array_filter(array_filter($block->store_id, function($value) use ($target_store_id) {
                if ($value == $target_store_id) {
                    return null;
                }
                return $value;
            }));
        }
        if ($new_store_ids) {
            $model_data = $block->getData();
            $model_data['stores'] = $new_store_ids;
            $block->setData($model_data);
            try {
                $block->save();
            } catch (Exception $e) {
                throw $e;
            }
        }

        $cms_block_collection = $cms_block_model->getCollection();
        /** @var Mage_Cms_Model_Resource_Block_Collection $cms_block_collection */
        $cms_block_collection->addFilter('identifier', $block->getIdentifier())->addStoreFilter($target_store_id);
        if ($cms_block_collection->count() == 0) {
            $translated_block = Mage::getModel('cms/block');
            $model_data = $block->getData();
            unset($model_data['block_id']);
            $model_data['stores'] = array($target_store_id);
        } else {
            $translated_block = $cms_block_collection->fetchItem();
            if ($translated_block->getId() == $block->getId()) {
                $translated_block = Mage::getModel('cms/block');
                $model_data = $block->getData();
                unset($model_data['block_id']);
                $model_data['stores'] = array($target_store_id);
            } else {
                // Updating an existing item
                $model_data = $translated_block->getData();
                if (!isset($model_data['stores']) || empty($model_data['stores'])) {
                    $model_data['stores'] = array($target_store_id);
                }
            }
        }
        /** @var Mage_Cms_Model_Block $translated_block */
        $translated_block->setData($model_data);
        switch ($field_name) {
            case 'content':
                $translated_block->setContent(($payload['text'] ? $payload['text'] : ''));
                break;
            case 'title':
                $translated_block->setTitle(($payload['text'] ? $payload['text'] : ''));
                break;
        }
        try {
            $translated_block->save();
        } catch (Exception $e) {
            throw $e;
        }

        $this->_ReplaceSourceStaticBlockIdsInCategories($source_store_id, $block->getId(), $target_store_id, $translated_block->getId());

        return true;
    }

    private function _ReplaceSourceStaticBlockIdsInCategories($source_store_id, $source_block_id, $target_store_id, $target_block_id, array $category_ids = null, array &$processed_category_ids = []) {
        /** @var Transfluent_Translate_Helper_Category $categoryHelper */
        $categoryHelper = Mage::helper('transfluenttranslate/category');
        if (is_null($category_ids)) {
            $category_ids = $categoryHelper->getCategoryIdsArray($target_store_id);
        }

        foreach ($category_ids AS $category_id) {
            if (in_array($category_id, $processed_category_ids)) continue;
            $processed_category_ids[] = $category_id;
            /** @var Mage_Catalog_Model_Category $cat */
            $cat = Mage::getModel('catalog/category');
            $cat->setStoreId($target_store_id);
            $cat->load($category_id);
            if (!$cat->getName()) {
                continue;
            }

            if (!in_array($cat->getDisplayMode(), array(Mage_Catalog_Model_Category::DM_MIXED, Mage_Catalog_Model_Category::DM_PAGE))) {
                continue;
            }
            if ($cat->getLandingPage() == $source_block_id) {
                $cat->setLandingPage($target_block_id);
                try {
                    $cat->save();
                } catch (Exception $e) {
                    throw $e;
                }
            }

            $cat_children_ids = $cat->getAllChildren(true);
            if (!empty($cat_children_ids)) {
                $this->_ReplaceSourceStaticBlockIdsInCategories($source_store_id, $source_block_id, $target_store_id, $target_block_id, $cat_children_ids, $processed_category_ids);
            }
        }
    }

    private function _saveCmsPageDetails($matches, $payload, $text_id) {
        $source_store_id = $matches[1];
        $target_store_id = $matches[2];
        $cms_page_id = $matches[3];
        $field_name = $matches[4];

        $cms_page_model = Mage::getModel('cms/page');
        /** @var Mage_Cms_Model_Page $cms_page_model */
        $page = $cms_page_model->setStoreId($source_store_id)->load($cms_page_id);
        /** @var Mage_Cms_Model_Page $page */
        if ($page->isEmpty() || $page->isObjectNew()) {
            throw new Exception('SOURCE_PAGE_NOT_FOUND');
        }
        $new_store_ids = null;
        if (in_array("0", $page->store_id)) {
            // Block visibility is "0", i.e. all stores
            $all_store_ids = array();
            $websites = Mage::app()->getWebsites();
            foreach ($websites AS $website) {
                /** @var Mage_Core_Model_Website $website */
                $stores = $website->getStores();
                foreach ($stores AS $store) {
                    /** @var Mage_Core_Model_Store $store */
                    if ($store->getId() == $target_store_id) {
                        continue;
                    }
                    $all_store_ids[] = $store->getId();
                }
            }
            $new_store_ids = $all_store_ids;
        } else if (in_array((string)$target_store_id, $page->store_id)) {
            $new_store_ids = array_filter(array_filter($page->store_id, function($value) use ($target_store_id) {
                if ($value == $target_store_id) {
                    return null;
                }
                return $value;
            }));
        }
        if ($new_store_ids) {
            $model_data = $page->getData();
            $model_data['stores'] = $new_store_ids; // Page model uses store_id somewhere else, check if this is right.. @todo FIXME
            $page->setData($model_data);
            try {
                $page->save();
            } catch (Exception $e) {
                throw $e;
            }
        }

        $cms_page_collection = $cms_page_model->getCollection();
        /** @var Mage_Cms_Model_Resource_Page_Collection $cms_page_collection */
        $cms_page_collection->addFilter('identifier', $page->getIdentifier())->addStoreFilter($target_store_id);
        if ($cms_page_collection->count() == 0) {
            $translated_page = Mage::getModel('cms/page');
            $model_data = $page->getData();
            unset($model_data['page_id']);
            $model_data['stores'] = array($target_store_id);
        } else {
            $translated_page = $cms_page_collection->fetchItem();
            if ($translated_page->getId() == $page->getId()) {
                $translated_page = Mage::getModel('cms/page');
                $model_data = $page->getData();
                unset($model_data['page_id']);
                $model_data['stores'] = array($target_store_id);
            } else {
                // Updating an existing item
                $model_data = $translated_page->getData();
                if (!isset($model_data['stores']) || empty($model_data['stores'])) {
                    $model_data['stores'] = array($target_store_id);
                }
            }
        }
        /** @var Mage_Cms_Model_Page $translated_page */
        $translated_page->setData($model_data);
        switch ($field_name) {
            case 'content':
                $translated_page->setContent(($payload['text'] ? $payload['text'] : ''));
                break;
            case 'meta-keywords':
                $translated_page->setMetaKeywords(($payload['text'] ? $payload['text'] : ''));
                break;
            case 'meta-description':
                $translated_page->setMetaDescription(($payload['text'] ? $payload['text'] : ''));
                break;
            case 'title':
                $translated_page->setTitle(($payload['text'] ? $payload['text'] : ''));
                break;
        }
        try {
            $translated_page->save();
        } catch (Exception $e) {
            throw $e;
        }

        return true;
    }

    private function _saveProductDetails($matches, $payload, $text_id) {
        $store_id = $matches[1];
        $product_id = $matches[2];
        $field_name = $matches[3];

        $product_action = Mage::getSingleton('catalog/product_action');
        /** @var Mage_Catalog_Model_Product_Action $product_action */
        $product_action->updateAttributes(
            array($product_id),
            array((string)$field_name => ($payload['text'] ? $payload['text'] : '')),
            $store_id);

        return $this->_updateOrder($store_id, $text_id, $payload['level']);
    }

    private function _updateOrder($store_id, $text_id, $level) {
        $product_helper = Mage::helper('transfluenttranslate/product');
        /** @var Transfluent_Translate_Helper_Product $product_helper */
        $order = $product_helper->GetOrder($store_id, $text_id, $level);
        if ($order && $order->count() == 1) {
            /** @var Transfluent_Translate_Model_Mysql4_Transfluenttranslate_Collection $order */
            $order->setDataToAll('status', 2);
            $order->save();
        }/* else {
            throw new Transfluent_Translate_Exception_EFailedToUpdateOrder();
        }*/
        return true;
    }
}

