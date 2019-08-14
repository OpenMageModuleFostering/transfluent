<?php

class Transfluent_Translate_Exception_Base extends Exception {
    protected $_message = 'An unknown error occured';

    public function __construct() {
        $arguments = func_get_args();

        $message = Mage::helper('Core')->__($this->_message);
        parent::__construct(sprintf($message, $arguments), 1023);
    }

    /**
     * Create a new exception from our backend error messages.
     *
     * @param $name
     * @return Transfluent_Translate_Exception_Base
     */
    public static function create($name) {
        $name = 'Transfluent_Translate_Exception_' . str_replace('Exception', '', $name);
        try {
            return new $name();
        } catch (Exception $e) {
            return null;
        }
    }

}