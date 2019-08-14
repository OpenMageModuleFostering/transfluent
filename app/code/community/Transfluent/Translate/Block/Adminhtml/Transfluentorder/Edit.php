<?php

/**
 * Class Transfluent_Translate_Block_Adminhtml_transfluentorder_Edit
 */
class Transfluent_Translate_Block_Adminhtml_transfluentorder_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
    public function __construct() {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'transfluentorder';
        $this->_controller = 'adminhtml_transfluentorder';
        $this->removeButton('save');
        $this->removeButton('delete');
        $this->removeButton('reset');
    }

    public function getHeaderText() {
         return "blah";
    }
}
