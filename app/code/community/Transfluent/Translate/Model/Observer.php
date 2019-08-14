<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Observer extends Mage_Core_Model_Abstract {
    /**
     * @version 1.1.1
     * @param Varien_Event_Observer $observer
     * @return Mage_Core_Block_Template
     */
    private function _createTranslateFormBlock(Varien_Event_Observer $observer) {
        $block = new Mage_Core_Block_Template();
        $block->setTemplate('transfluent/product/edit.phtml');
        $block->setIsAnonymous(true);
        return $block;
    }

    /**
     * @version 1.1.1
     * @param Varien_Event_Observer $observer
     * @return Mage_Core_Block_Template
     */
    private function _createGetQuoteFormBlock(Varien_Event_Observer $observer) {
        $block = new Mage_Core_Block_Template();
        $block->setTemplate('transfluent/product/index.phtml');
        $block->setIsAnonymous(true);
        return $block;
    }

    /**
     * @version 1.1.1
     * @param Varien_Event_Observer $observer
     * @return Mage_Core_Block_Template
     */
    private function _createTranslateAttributesFormBlock(Varien_Event_Observer $observer) {
        $block = new Mage_Core_Block_Template();
        $block->setTemplate('transfluent/product/attributes/edit.phtml');
        $block->setIsAnonymous(true);
        return $block;
    }

    /**
     * @version 1.1.1
     * @param Varien_Event_Observer $observer
     * @return Mage_Core_Block_Template
     */
    private function _createTranslateCategoriesFormBlock(Varien_Event_Observer $observer) {
        $block = new Mage_Core_Block_Template();
        $block->setTemplate('transfluent/product/category/edit.phtml');
        $block->setIsAnonymous(true);
        return $block;
    }

    /**
     * @version 1.1.1
     * @param Varien_Event_Observer $observer
     * @return Mage_Core_Block_Template
     */
    private function _createTranslateTagsFormBlock(Varien_Event_Observer $observer) {
        $block = new Mage_Core_Block_Template();
        $block->setTemplate('transfluent/tag/tag/index.phtml');
        $block->setIsAnonymous(true);
        return $block;
    }

    /**
     * @version 1.1.1
     * @param Varien_Event_Observer $observer
     */
    public function hookDispatchAdminhtmlBlockHtmlBefore(Varien_Event_Observer $observer) {
        if (!Mage::getStoreConfig('transfluenttranslate/account/token')) {
            return;
        }
        $action = Mage::app()->getRequest()->getRequestedActionName();
        switch (Mage::app()->getRequest()->getControllerName()) {
            case 'catalog_product':
                switch ($action) {
                    case 'index':
                        $this->AddGetQuoteBlockToProductIndexPage($observer);
                        break;
                    case 'edit':
                        $this->AddTranslateBlockToProductEditPage($observer);
                        break;
                }
                return;
            case 'catalog_category':
                if ($action != 'edit') return;
                $this->AddTranslateBlockToCategoryEditPage($observer);
                break;
            case 'catalog_product_attribute':
                if ($action != 'edit') return;
                $this->AddTranslateBlockToProductAttributesEditPage($observer);
                break;
            case 'tag':
                if ($action != 'index') return;
                $this->AddTranslateBlockToTagIndexPage($observer);
                break;
            default:
                return;
        }
    }

    private function AddTranslateBlockToTagIndexPage(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        /** @var Mage_Core_Block_Abstract $block */
        if ($block->getNameInLayout() == 'root') {
            $extendBlock = $this->_createTranslateTagsFormBlock($observer);
            if ($extendBlock) {
                $block->getChild('content')->insert($extendBlock, '', false, 'TF_Translate_form');
            }
        }
    }

    private function AddTranslateBlockToProductAttributesEditPage(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        /** @var Mage_Core_Block_Abstract $block */
        if ($block->getNameInLayout() == 'root') {
            $extendBlock = $this->_createTranslateAttributesFormBlock($observer);
            if ($extendBlock) {
                $block->getChild('content')->insert($extendBlock, '', false, 'TF_Translate_form');
            }
        }
    }

    private function IsAjax() {
        return Mage::app()->getRequest()->isXmlHttpRequest() || Mage::app()->getRequest()->getParam('isAjax');
    }

    private function AddTranslateBlockToCategoryEditPage(Varien_Event_Observer $observer) {
        if ($this->IsAjax()) {
            return;
        }
        $block = $observer->getEvent()->getBlock();
        /** @var Mage_Core_Block_Abstract $block */
        if ($block->getNameInLayout() == 'root') {
            $extendBlock = $this->_createTranslateCategoriesFormBlock($observer);
            if ($extendBlock) {
                $block->getChild('content')->insert($extendBlock, '', false, 'TF_Translate_form');
            }
        }
    }

    private function AddTranslateBlockToProductEditPage(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        /** @var Mage_Core_Block_Abstract $block */
        if ($block->getNameInLayout() == 'root') {
            $extendBlock = $this->_createTranslateFormBlock($observer);
            if ($extendBlock) {
                $block->getChild('content')->insert($extendBlock, '', false, 'TF_Translate_form');
            }
        }
    }

    private function AddGetQuoteBlockToProductIndexPage(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        /** @var Mage_Core_Block_Abstract $block */
        if ($block->getNameInLayout() == 'root') {
            $extendBlock = $this->_createGetQuoteFormBlock($observer);
            if ($extendBlock) {
                $block->getChild('content')->insert($extendBlock, '', false, 'TF_Translate_form');
            }
        }
    }

    public function hookDispatchSaveProduct(Varien_Event_Observer $observer) {
        // @todo: Save changes to source language?
        /*
        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        $sku = $product->getSku();
        $tran_mod = Mage::getModel('transfluenttranslate/transfluenttranslate');
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        $lang_helper = Mage::helper('transfluenttranslate/languages');

        $pendings = $tran_mod
                ->getCollection()
                ->addFieldToFilter('transfluenttranslate_store', $storeId)
                ->addFieldToFilter('transfluenttranslate_product', $sku)
                ->addFieldToFilter('transfluenttranslate_status', 'Pending')
                ->addFieldToFilter('transfluenttranslate_groupid', 0)
                ->addFieldToSelect('*')
        /*
        ->addFieldToSelect('transfluenttranslate_id')
        ->addFieldToSelect('transfluenttranslate_store')
        ->addFieldToSelect('transfluenttranslate_product')
        ->addFieldToSelect('transfluenttranslate_field')
        ->addFieldToSelect('transfluenttranslate_text')
        */
        /*
                        ->getData();

                foreach ($pendings as $pending) {
                    $pend_val = $pending['transfluenttranslate_text'];
                    $prod_val = $product->getData($pending['transfluenttranslate_field']);

                    if ($pend_val != $prod_val) {
                        $text_id = $pending['transfluenttranslate_textid'];
                        $source_language = $lang_helper->getLangByCode($pending['transfluenttranslate_sourcelang']);
                        $token = $pending['transfluenttranslate_token'];
                        $translate->Text($text_id, $source_language, $prod_val, $token, 'POST');
                    }
                }
        */
    }
}
