<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_SourceText extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $text_id =  $row->getData($this->getColumn()->getIndex());
        $model = Mage::helper('transfluenttranslate/text')->ModelByTextId($text_id);
        if (!$model) {
            return '[text id: ' . $text_id . ']';
        }
        return $model->OrderTitle();
    }
}
