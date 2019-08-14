<?php

class Transfluent_Translate_Block_Regform extends Mage_Adminhtml_Block_System_Config_Form_Field {
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {}

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $token = Mage::getStoreConfig('transfluenttranslate/account/token');
        if ($token != '') {
            $html = $this->__('You have successfully connected to Transfluent.com');
        } else {
            $html = $this->getLayout()->createBlock('transfluenttranslate/account')->setTemplate('transfluent/account/create.phtml')->toHtml();
        }
        $res = '<td>' . $html . '</td>';
        return $res;
    }
}
