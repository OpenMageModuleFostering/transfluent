<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Block_Loginform extends Mage_Adminhtml_Block_System_Config_Form_Field {
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {}

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $token  = Mage::getStoreConfig('transfluenttranslate/account/token');
        if ($token != '') {
            $html = $this->getLayout()->createBlock('transfluenttranslate/account')->setTemplate('transfluent/account/logged.phtml')->toHtml();
        } else {
            $html = $this->getLayout()->createBlock('transfluenttranslate/account')->setTemplate('transfluent/account/login.phtml')->toHtml();
        }
        $html .= $this->getLayout()->createBlock('transfluenttranslate/account')->setTemplate('transfluent/account/action.phtml')->toHtml();
        $res = '<td>' . $html . '</td>';
        return $this->_decorateRowHtml($element, $res);
    }
}
