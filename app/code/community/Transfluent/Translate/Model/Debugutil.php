<?PHP

/**
 * Class Transfluent_Translate_Model_Debugutil
 */
class Transfluent_Translate_Model_Debugutil extends Mage_Core_Model_Abstract {

    /** @var string tempFile */
    public $tempFile = '/tmp/magento_transfluent.log';

    /**
     *
     */
    public function __construct() {
    }

    /**
     * @param $stacktrace
     *
     * @return string
     */
    public function callStack($stacktrace) {
        $result = str_repeat("=", 10) . " " . date(now()) . " " . str_repeat("=", 10) . "\n";
        $i = 1;
        foreach ($stacktrace as $node) {
            $result .= "$i. " . basename($node['file']) . ":" . $node['function'] . "(" . $node['line'] . ")\n";
            $i++;
        }

        return $result;
    }

    /**
     * writes stacktrace to temp file
     *
     * @param null $stackTrace
     * @param null $fileName
     */
    public function callStackToTemp($stackTrace = null, $fileName = null) {
        if (null == $stackTrace) {
            $stackTrace = debug_backtrace();
        }

        if (null != $fileName) {
            $this->tempFile = $fileName;
        }

        file_put_contents($this->tempFile, PHP_EOL . self::callStack($stackTrace) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * pushes a string to temp file
     *
     * @param null $value
     * @param null $fileName
     */
    public function valueToTemp($value = null, $fileName = null) {
        if (null != $fileName) {
            $this->tempFile = $fileName;
        }

        if (is_array($value) || is_object($value)) {
            $value = var_export($value, true);
        }

        file_put_contents($this->tempFile, PHP_EOL . $value . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
