<?php

/**
 * Class Transfluent_Translate_Block_Adminhtml_transfluentorder_Edit_Tab_Form
 */
class Transfluent_Translate_Block_Adminhtml_transfluentorder_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'transfluentorder_form',
            array('legend' => Mage::helper('transfluenttranslate')->__('Order information'))
        );

        $fieldset->addField(
            'transfluentorder_textid',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Text ID'),
                'name' => 'transfluentorder_textid',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluentorder_quality',
            'text',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Quality'),
                'text' => 'transfluentorder_quality',
                'style' => "border: 0px; background: none;",
                'disabled' => true,
                'readonly' => true,
            )
        );

        $fieldset->addField(
            'transfluentorder_status',
            'select',
            array(
                'label' => Mage::helper('transfluenttranslate')->__('Status'),
                'name' => 'transfluentorder_status',
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


        if (Mage::getSingleton('adminhtml/session')->gettransfluentorderData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->gettransfluentorderData());
            Mage::getSingleton('adminhtml/session')->settransfluentorderData(null);
        } elseif (Mage::registry('transfluentorder_data')) {
            $form->setValues(Mage::registry('transfluentorder_data')->getData());
        }
        return parent::_prepareForm();
    }
}
