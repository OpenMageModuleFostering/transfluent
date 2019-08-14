<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Transfluenttranslate_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('translations_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('transfluenttranslate/transfluenttranslate')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header' => Mage::helper('transfluenttranslate')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'id',
        ));

        $this->addColumn('source_text', array(
            'header' => Mage::helper('transfluenttranslate')->__('Text'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'text_id',
            'renderer' => 'Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_SourceText',
        ));

        $this->addColumn('target_store', array(
            'header' => Mage::helper('transfluenttranslate')->__('Target store'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'target_store',
            'renderer' => 'Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_Store',
        ));

        $this->addColumn('language_pair', array(
            'header' => Mage::helper('transfluenttranslate')->__('Language pair'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'target_store',
            'renderer' => 'Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_LanguagePair',
        ));

        $this->addColumn('level', array(
            'header' => Mage::helper('transfluenttranslate')->__('Level'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'level',
            'renderer' => 'Transfluent_Translate_Block_Adminhtml_Catalog_Product_Renderer_Level',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('transfluenttranslate')->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                1 => 'Queued',
                2 => 'Completed',
                3 => 'Canceled'
            ),
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return null; // @todo: Disabled
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
