<?PHP

/**
 * Class Transfluent_Translate_Exception_ETransfluentTagsNothingToTranslate
 *
 */
class Transfluent_Translate_Exception_ETransfluentTagsNothingToTranslateBase extends Transfluent_Translate_Exception_Base
{
    protected $_message = 'The translation request was rejected because the tags either contained nothing to translate or they have been already translated.';
}