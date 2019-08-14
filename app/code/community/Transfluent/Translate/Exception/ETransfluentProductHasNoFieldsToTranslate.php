<?PHP

/**
 * Class Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslate
 *
 */
class Transfluent_Translate_Exception_ETransfluentProductHasNoFieldsToTranslateBase extends Transfluent_Translate_Exception_Base
{
    protected $_message = 'Product details are already translated! You can force a translation request by setting "Use default value" option on to name, description and short description fields and saving product details before trying again.';
}