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
            /** @var Mage_Catalog_Model_Product $model */

            $available_fields = array();
            $products_out = array();
            foreach ($product_ids AS $product_id) {
                $product = $product_model->setStoreId($target_store_id)->load($product_id);
                if (!$product) {
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
                            $master_product = $product_model->setStoreId($source_store_id)->load($product_id);
                            $product_data[$product_field] = $master_product->getData($product_field);
                            continue;
                        }
                    }
                    $product_data[$product_field] = $product->getData($product_field);
                }
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

