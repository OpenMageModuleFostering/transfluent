<?php

/**
 * Class Transfluent_Translate_Block_Adminhtml_Transfluentorder_Edit_Form
 */
class Transfluent_Translate_Block_Adminhtml_Transfluentorder_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl(
                        '*/*/save',
                        array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $stores = Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm();

        // form
        $fieldset = $form->addFieldset('orderForm', array(
            'legend' => Mage::helper('Core')->__('Translation info')
        ));

        // source language
        $fieldset->addField('source', 'select', array(
            'label' => Mage::helper('Core')->__('Translate From'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'source',
            'note' => Mage::helper('Core')->__('Select source Store & Language'),
            'values' => $stores,
        ));

        // destination language
        $fieldset->addField('destination', 'select', array(
            'label' => Mage::helper('Core')->__('To'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'destination',
            'note' => Mage::helper('Core')->__('Select destination Store & Language'),
            'values' => $stores
        ));

        // category list
        $fieldset->addField('checkboxes', 'checkboxes', array(
            'label' => Mage::helper('Core')->__('Categories'),
            'name' => 'Checkbox',
            'required' => true,
            'values' => $this->getCategories(),
            'onclick' => "",
            'onchange' => "",
            'disabled' => false,
            'tabindex' => 4
        ));

        $fieldset->addField('submit', 'submit', array(
            'value' => 'Submit',
        ));

        $data = array();
        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * get categories
     *
     * @return array
     */
    protected function getCategories() {

        $category = Mage::getModel('catalog/category');
        $tree = $category->getTreeModel();
        $tree->load();
        $ids = $tree->getCollection()->getAllIds();
        $arr = array();
        if (!empty($ids) && is_array($ids)) {
            foreach ($ids as $id) {
                $cat = Mage::getModel('catalog/category');
                $cat->load($id);
                if ($cat->getName() && $cat->getProductCount()) {
                    $catName = trim($cat->getName()) . ' (' . $cat->getProductCount() . ')';
                    $arr[] = array('value' => $id, 'label' => $catName);
                }
            }
        }

        return $arr;
    }
}
