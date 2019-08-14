<?PHP

/**
 * Class Transfluent_Translate_Exception_ELanguagePairNotSupported
 *
 */
class Transfluent_Translate_Exception_ELanguagePairNotSupported extends Transfluent_Translate_Exception_Base
{
    protected $_message = 'Requested language pair is unfortunately not supported on selected translator level. Please try different translator level or another language pair.';
}