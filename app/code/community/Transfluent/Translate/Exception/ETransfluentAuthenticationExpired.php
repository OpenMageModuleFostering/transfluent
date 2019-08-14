<?PHP

/**
 * Class Transfluent_Translate_Exception_ETransfluentAuthenticationExpired
 *
 */
class Transfluent_Translate_Exception_ETransfluentAuthenticationExpiredBase extends Transfluent_Translate_Exception_Base
{
    protected $_message = "Your Transfluent.com authentication has expired. Please perform re-authentication in <a href='%s'>Configuration</a>-section.</div>";

    public function __construct() {
        parent::__construct(Mage::helper("adminhtml")
            ->getUrl("adminhtml/system_config/edit/section/transfluenttranslate/"));
    }
}