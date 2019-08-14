<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Model_Base_Backendclient extends Mage_Adminhtml_Controller_Action {
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const PRODUCTION_HOST = 'https://transfluent.com/v2/';
    const DEV_HOST_CONFIG_FILE = 'dev_host.config';

    static $API_URL;
    static $DEV_MODE = false;
    private $email;
    private $token = null;

    public function __construct() {
        $email = Mage::getStoreConfig('transfluenttranslate/account/email');
        $token = Mage::getStoreConfig('transfluenttranslate/account/token');

        $this->SystemCheck();
        $this->email = $email;
        $this->token = $token;

        $this->setApiUrl($this->getBackendHost());
    }

    /**
     *  setter for API_URL
     *
     * @param $url
     */
    private function setApiUrl($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            self::$API_URL = $url;
        }

        if (self::PRODUCTION_HOST != $url) {
            $this->setDevMode(true);
        }
    }

    /**
     * getter for API_URL
     *
     * @return string
     */
    public function getApiUrl() {
        return self::$API_URL;
    }

    /**
     * getter for DEV_MODE
     *
     * @return bool
     */
    public function getDevMode() {
        return self::$DEV_MODE;
    }

    /**
     * setter for DEV_MODE
     *
     * @param $mode
     */
    private function setDevMode($mode) {

        if (true === $mode || false === $mode) {
            self::$DEV_MODE = $mode;
        }
    }

    private function UriFromMethod($method_name) {
        return strtolower(preg_replace("/(?!^)([A-Z]{1}[a-z0-9]{1,})/", '/$1', $method_name)) . '/';
    }

    private function SystemCheck() {
        if (function_exists('curl_init')) {
            return;
        }
        Mage::getSingleton('core/session')->addError('Transfluent\'s extension is missing cURL for PHP. Please install it now!');
        error_log('Transfluent\'s ' . __CLASS__ . ' is missing cURL extension for PHP.');
    }


    public function SetToken($token) {
        $this->token = $token;
    }

    public function getToken() {
        return $this->token;
    }

    /**
     * provides backend host name
     *
     * @return string
     */
    public function getBackendHost() {

        $API_URL = self::PRODUCTION_HOST;
        $dev_host_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::DEV_HOST_CONFIG_FILE;
        if (is_file($dev_host_file)) {
            $API_URL = trim(file_get_contents($dev_host_file));
        }

        return $API_URL;
    }

    private function SaveConfiguration($email, $token) {
        try {
            $coreConfig = Mage::getModel('core/config');
            $coreConfig->saveConfig('transfluenttranslate/account/email', $email);
            $coreConfig->saveConfig('transfluenttranslate/account/token', $token);
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function Logout() {
        return $this->SaveConfiguration(null, null);
    }

    public function Authenticate($email, $password) {
        $extension_callback_endpoint = Mage::getUrl('transfluenttranslate/');
        $version = Mage::getVersion();
        $payload = array('email' => $email, 'password' => $password, 'magento_ver' => $version, 'magento_url' => $extension_callback_endpoint);
        $response = $this->Request(__FUNCTION__, 'POST', $payload);
        $message = array();
        if (!$response['response']['token']) {
            throw new Exception('Could not authenticate with API!');
        }
        if ($response['status'] == 'ERROR') {
            $message['status'] = 'error';
            $message['message'] = $response['error']['message'] . ' ' . $response['response'];
        } else {
            $token = $response['response']['token'];
            $this->token = $token;
            $this->email = $email;
            $this->SaveConfiguration($email, $token);
            $message['status'] = 'ok';
            $message['message'] = 'You have successfully connected to Transfluent.com';
            $session = Mage::getSingleton('core/session');
            /** @var Mage_Core_Model_Session $session */
            $session->addSuccess('You have successfully authenticated. If you have not yet activated billing mode for your account, you should do it next  by contacting <a href="mailto:sales@transfluent.com">sales@transfluent.com</a> in order to setup invoicing. Or visit <a href="https://www.transfluent.com/my-account/">your Transfluent account page</a> to setup a credit card charging.');
        }
        return $message;
    }

    /**
     * /languages/ can be called without token&any authentication, we can call Request directly
     *
     * @throws \Exception
     * @return mixed
     */
    public function Languages() {
        return $this->Request(__FUNCTION__);
    }

    public function Hello() {
        return $this->CallApi(__FUNCTION__, self::HTTP_GET, array('name' => 'tests'));
    }

    public function GetCategoryQuote($quote_id) {
        $payload = array(
            'id' => $quote_id,
            'token' => $this->token
        );
        return $this->CallApi('magento/quote', self::HTTP_GET, $payload);
    }

    public function UpdateCategoryQuote($quote_id, $translate_fields) {
        $payload = array(
            'id' => $quote_id,
            'token' => $this->token,
            'translate_fields' => implode(",", $translate_fields),
            'method' => 'PUT',
            '__fork' => 1
        );
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    public function OrderCategoryQuote($quote_id, $instructions) {
        $payload = array(
            'id' => $quote_id,
            'token' => $this->token,
            'order' => true,
            'instructions' => $instructions,
            'method' => 'PUT',
            '__fork' => 1
        );
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    public function CreateCategoryQuote($source_store, $source_language, $target_store, $target_language, $level, $collision_strategy, $category_ids, $translate_fields = null) {
        $extension_callback_endpoint = Mage::getUrl('transfluenttranslate/');
        $version = Mage::getVersion();
        $payload = array(
            'magento_ver' => $version,
            'magento_url' => $extension_callback_endpoint,
            'source_store' => $source_store,
            'source_language' => $source_language,
            'target_store' => $target_store,
            'target_language' => $target_language,
            'level' => $level,
            'collision' => $collision_strategy,
            'category_ids' => '[' . implode(",", $category_ids) . ']',
            'token' => $this->token,
            'hash' => md5($this->token)
        );
        if (!is_null($translate_fields)) {
            $payload['translate_fields'] = $translate_fields;
        }
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    public function CreateAccount($email, $terms) {
        $extension_callback_endpoint = Mage::getUrl('transfluenttranslate/');
        $version = Mage::getVersion();
        $payload = array('email' => $email, 'terms' => $terms, 'magento_ver' => $version, 'magento_url' => $extension_callback_endpoint);
        $res = $this->CallApi(__FUNCTION__, self::HTTP_GET, $payload);
        if ($res['status'] == "OK") {
            $this->SaveConfiguration($email, $res['response']['token']);
            $session = Mage::getSingleton('core/session');
            /** @var Mage_Core_Model_Session $session */
            $session->addSuccess('You have successfully created a new account. We have sent your password to your email. Next you should contact <a href="mailto:sales@transfluent.com">sales@transfluent.com</a> in order to setup invoicing.');
        }
        return $res;
    }

    public function SaveText($text_id, $language, $text) {
        return $this->CallApi('text', self::HTTP_POST, array(
            'text_id' => $text_id,
            'group_id' => 'Magento',
            'language' => $language,
            'text' => $text,
            'token' => $this->token
        ));
    }

    public function Text($text_id, $source_language, $text, $token, $method) {
        return $this->CallApi(__FUNCTION__, $method, array(
            'text_id' => $text_id,
            'language' => $source_language,
            'text' => $text,
            'token' => $token
        ));
    }

    public function Texts($group_id, $source_language, $texts, $it, $method, $token) {
        return $this->CallApi(__FUNCTION__, $method, array(
            'group_id' => $group_id,
            'language' => $source_language,
            'texts' => $texts,
            'invalidate_translations' => $it,
            'token' => $token
        ));
    }

    public function TextsTranslate($group_id = null, $source_language, $target_languages, $texts, $comment, $callback_url, $level) {
        return $this->CallApi(__FUNCTION__, self::HTTP_POST, array(
            'group_id' => $group_id,
            'source_language' => $source_language,
            'target_languages' => $target_languages, //'[500]',
            'texts' => $texts,
            'comment' => $comment,
            'callback_url' => $callback_url,
            'level' => $level,
            'token' => $this->token
        ));
    }

    public function TextStatus($text_id, $source_language, $token) {
        return $this->CallApi(__FUNCTION__, self::HTTP_GET, array(
            'text_id' => $text_id,
            'language' => $source_language,
            'token' => $token
        ));
    }

    public function FreeTextWordCount($level, $text, $source_language_id, $target_language_id) {
        return $this->CallApi(__FUNCTION__, self::HTTP_POST, array(
            'free_text' => $text,
            'level' => $level,
            'source_language' => $source_language_id,
            'target_language' => $target_language_id,
            'token' => $this->token
        ));
    }

    public function TextWordCount($level, $text_id, $group_id, $source_language, $text, $it = 0, $locale, $token) {
        return $this->CallApi(__FUNCTION__, self::HTTP_GET, array(
            'level' => $level,
            'text_id' => $text_id,
            'group_id' => $group_id,
            'language' => $source_language,
            'text' => $text,
            'invalidate_translations' => $it,
            'locale' => $locale,
            'token' => $token
        ));
    }

    private function CallApi($method_name, $method = self::HTTP_GET, $payload = array()) {
        return $this->Request($method_name, $method, $payload);
    }

    private function Request($method_name, $method = self::HTTP_GET, $payload = array()) {
        $uri = $this->UriFromMethod($method_name);

        $curl_handle = curl_init(self::$API_URL . $uri);
        if (!$curl_handle) {
            throw new \Exception('Could not initialize cURL!');
        }
        switch (strtoupper($method)) {
            case self::HTTP_GET:
                $url = self::$API_URL . $uri . '?';
                $url_parameters = array();
                foreach ($payload AS $key => $value) {
                    $url_parameters[] = $key . '=' . urlencode($value);
                }
                $url .= implode("&", $url_parameters);
                curl_setopt($curl_handle, CURLOPT_URL, $url);
                break;
            case self::HTTP_POST:
                curl_setopt($curl_handle, CURLOPT_POST, TRUE);
                if (!empty($payload)) {
                    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $payload);
                }
                break;
            default:
                throw new \Exception('Unsupported request method.');
        }
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        if (self::$DEV_MODE) {
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl_handle);
        $info = curl_getinfo($curl_handle);
        curl_close($curl_handle);


        if (!$response) {
            throw new \Exception('Failed to connect with Transfluent\'s API. cURL error: ' . curl_error($curl_handle));
        }
        // !isset($info['http_code']) || $info['http_code'] != 200
        try {
            $response_obj = Mage::helper('core')->jsonDecode($response, true);
        } catch (Exception $e) {
            if ($info['http_code'] == 500) {
                throw new Exception('The order could not be processed. Please try again!');
            }
            if (self::$DEV_MODE) {
                error_log('API sent invalid JSON response: ' . $response . ', info: ' . print_r($info, true));
            }
            throw $e;
        }

        return $response_obj;
    }
}
