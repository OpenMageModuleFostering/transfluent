<?PHP

/**
 * Class Transfluent_Translate_Exception_ETransfluentNothingToTranslate
 *
 */
class Transfluent_Translate_Exception_ETransfluentNothingToTranslateBase extends Transfluent_Translate_Exception_Base
{
    protected $_message = 'The translation request was rejected because the product details either contained nothing to translate or the details have been already translated.';
}