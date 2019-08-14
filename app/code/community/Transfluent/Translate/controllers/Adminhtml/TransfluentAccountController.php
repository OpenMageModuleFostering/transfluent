<?php

class Transfluent_Translate_Adminhtml_TransfluentAccountController extends Mage_Adminhtml_Controller_Action {
    public function createAction() {
        $email = $this->getRequest()->getParam('email');
        $terms = $this->getRequest()->getParam('terms');

        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        $response = $translate->CreateAccount($email, $terms);
        print Mage::helper('core')->jsonEncode($response);
    }

    public function authenticateAction() {
        $e = $this->getRequest()->getParam('email');
        $p = $this->getRequest()->getParam('password');
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($translate->Authenticate($e, $p)));
    }

    public function terminateSessionAction() {
        $client = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $client */
        $mage_admin_url = Mage::getModel('adminhtml/url');
        /** @var Mage_Adminhtml_Model_Url $mage_admin_url */
        $mage_admin_url->setStore(0);
        $mage_admin_url->setControllerName('system_config');
        if (!$client->Logout()) {
            $this->_redirectError($mage_admin_url->getUrl());
            return;
        }
        $this->_redirectSuccess($mage_admin_url->getUrl());
    }

    public function logoutAction() {
        $translate = Mage::getModel('transfluenttranslate/base_backendclient');
        /** @var Transfluent_Translate_Model_Base_Backendclient $translate */
        if ($translate->Logout()) {
            print Mage::helper('core')->jsonEncode(array("status" => "OK"));
            return;
        }
        print Mage::helper('core')->jsonEncode(array("status" => "ERROR"));
    }
}
