<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Adminhtml_Transfluenttranslate_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'transfluenttranslate_form',
            array(
                'legend' => Mage::helper('transfluenttranslate')->__('Order information')
            )
        );

        $fieldset->addField(
            'transfluenttranslate_textid',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Text ID'),
                'name' => 'transfluenttranslate_textid',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluenttranslate_product',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Product'),
                'name' => 'transfluenttranslate_product',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluenttranslate_store',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Store'),
                'name' => 'transfluenttranslate_store',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluenttranslate_sourcelang',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Source language'),
                'name' => 'transfluenttranslate_sourcelang',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluenttranslate_targetlang',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Target language'),
                'name' => 'transfluenttranslate_targetlang',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluenttranslate_quality',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Quality'),
                'text' => 'transfluenttranslate_quality',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluenttranslate_status',
            'select',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Status'),
                'name' => 'transfluenttranslate_status',
                'class' => 'required-entry',
                'required' => true,
                'values' => array(
                    array(
                        'value' => 1,
                        'label' => Mage::helper('transfluenttranslate')->__('Queued'),
                    ),

                    array(
                        'value' => 2,
                        'label' => Mage::helper('transfluenttranslate')->__('Completed'),
                    ),

                    array(
                        'value' => 3,
                        'label' => Mage::helper('transfluenttranslate')->__('Canceled'),
                    ),
                ),
            )
        );

        if (Mage::getSingleton('adminhtml/session')->getTransfluenttranslateData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getTransfluenttranslateData());
            Mage::getSingleton('adminhtml/session')->setTransfluenttranslateData(null);
        } elseif (Mage::registry('transfluenttranslate_data')) {
            $form->setValues(Mage::registry('transfluenttranslate_data')->getData());
        }
        return parent::_prepareForm();
    }
}
