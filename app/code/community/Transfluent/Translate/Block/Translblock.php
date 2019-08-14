<?php

class Transfluent_Translate_Block_Translblock extends Mage_Adminhtml_Block_System_Config_Form_Field {
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
    }

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $token = Mage::getStoreConfig('transfluenttranslate/account/token');
        if ($token != '') {
            $html = $this->getLayout()->createBlock('transfluenttranslate/estimate')->setTemplate('transfluent/estimate_section.phtml')->toHtml();
        } else {
            $html = '<div class="notification-global">' . $this->__('Please finish Transfluent extension configuration first.') . '</div>';
        }

        $res = '<td>' . $html . '</td>';

        return $this->_decorateRowHtml($element, $res);
    }
}
