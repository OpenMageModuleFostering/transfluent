<?php

/**
 * Class Transfluent_Translate_Helper_Util
 */
class Transfluent_Translate_Helper_Util extends Mage_Core_Helper_Abstract
{
    /**
     * get success json message
     *
     * @param $string
     * @return string
     */
    public function getSuccessJson($string)
    {
        return json_encode(array('status' => 'success', 'message' => $string));
    }

    /**
     * get error json message
     *
     * @param $string
     * @return string
     */
    public function getErrorJson($string)
    {
        return json_encode(array('status' => 'error', 'message' => $string));
    }
}
