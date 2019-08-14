<?php

/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
class Transfluent_Translate_Helper_Languages extends Mage_Core_Helper_Abstract {
    public function getTransfluentLangs() {
        $languages_json = <<<EOFJSON
{"status":"OK","response":[{"1":{"name":"English","code":"en-gb","id":1}},{"2":{"name":"French","code":"fr-fr","id":2}},{"3":{"name":"German","code":"de-de","id":3}},{"4":{"name":"Chinese (Mandarin, Simplified)","code":"zh-cn","id":4}},{"5":{"name":"Chinese","code":"zh-hk","id":5}},{"6":{"name":"Spanish","code":"es-es","id":6}},{"7":{"name":"Japanese","code":"ja-jp","id":7}},{"8":{"name":"Korean","code":"ko-kr","id":8}},{"9":{"name":"Tagalog (Philippines)","code":"tl-ph","id":9}},{"10":{"name":"Portuguese","code":"pt-br","id":10}},{"11":{"name":"Finnish","code":"fi-fi","id":11}},{"12":{"name":"Italian","code":"it-it","id":12}},{"13":{"name":"Dutch","code":"nl-nl","id":13}},{"14":{"name":"Swedish","code":"sv-se","id":14}},{"15":{"name":"Russian","code":"ru-ru","id":15}},{"16":{"name":"Hindi","code":"hi-in","id":16}},{"17":{"name":"Arabic","code":"ar-sa","id":17}},{"18":{"name":"Malay","code":"ms-my","id":18}},{"19":{"name":"Romanian","code":"ro-ro","id":19}},{"20":{"name":"Lithuanian","code":"lt-lt","id":20}},{"21":{"name":"Hebrew","code":"he-il","id":21}},{"22":{"name":"Danish","code":"da-dk","id":22}},{"23":{"name":"Vietnamese","code":"vi-vn","id":23}},{"24":{"name":"Polish","code":"pl-pl","id":24}},{"25":{"name":"Turkish","code":"tr-tr","id":25}},{"26":{"name":"Thai","code":"th-th","id":26}},{"27":{"name":"Norwegian","code":"no-no","id":27}},{"28":{"name":"Ukrainian","code":"uk-ua","id":28}},{"29":{"name":"Hungarian","code":"hu-hu","id":29}},{"30":{"name":"Greek","code":"el-gr","id":30}},{"31":{"name":"Czech","code":"cs-cz","id":31}},{"32":{"name":"Catalan","code":"ca-ad","id":32}},{"33":{"name":"Esperanto","code":"eo-eo","id":33}},{"34":{"name":"Bengali","code":"bn-bd","id":34}},{"35":{"name":"Urdu","code":"ur-pk","id":35}},{"36":{"name":"Latin","code":"la-it","id":36}},{"37":{"name":"Persian","code":"fa-ir","id":37}},{"38":{"name":"Slovak","code":"sk-sk","id":38}},{"39":{"name":"Bulgarian","code":"bg-bg","id":39}},{"40":{"name":"Estonian","code":"et-ee","id":40}},{"41":{"name":"Latvian","code":"lv-lv","id":41}},{"42":{"name":"Somali","code":"so-so","id":42}},{"43":{"name":"Serbian","code":"sr-rs","id":43}},{"44":{"name":"Croatian","code":"hr-hr","id":44}},{"45":{"name":"Albanian","code":"sq-al","id":45}},{"46":{"name":"Azerbaijani","code":"az-az","id":46}},{"47":{"name":"Tamil","code":"ta-in","id":47}},{"48":{"name":"Swahili","code":"sw-ke","id":48}},{"49":{"name":"Macedonian","code":"mk-mk","id":49}},{"50":{"name":"Tibetan","code":"bo-cn","id":50}},{"51":{"name":"Punjabi","code":"pa-in","id":51}},{"52":{"name":"Javanese","code":"jv-id","id":52}},{"53":{"name":"Armenian","code":"hy-am","id":53}},{"54":{"name":"Basque","code":"eu-es","id":54}},{"55":{"name":"Kyrgyz","code":"ky-kg","id":55}},{"56":{"name":"Chinese (Traditional)","code":"zh-tw","id":56}},{"57":{"name":"Amharic","code":"am-et","id":57}},{"58":{"name":"Akan-Twi","code":"tw-gh","id":58}},{"59":{"name":"Hausa","code":"ha-ng","id":59}},{"60":{"name":"Igbo","code":"ig-ng","id":60}},{"61":{"name":"Slovenian","code":"sl-si","id":61}},{"128":{"name":"Indonesian","code":"id-id","id":128}},{"129":{"name":"Portuguese","code":"pt-pt","id":129}},{"130":{"name":"Afrikaans","code":"af-za","id":130}},{"131":{"name":"Bosnian","code":"bs-ba","id":131}},{"132":{"name":"Georgian","code":"ka-ge","id":132}},{"133":{"name":"Kazakh","code":"kk-kz","id":133}},{"134":{"name":"Central Khmer","code":"km-kh","id":134}},{"135":{"name":"Lao","code":"lo-la","id":135}},{"136":{"name":"Pashto","code":"ps-af","id":136}},{"137":{"name":"Uzbek","code":"uz-uz","id":137}},{"138":{"name":"Mongolian","code":"mn-mn","id":138}},{"139":{"name":"Nepali","code":"ne-np","id":139}},{"140":{"name":"Welsh","code":"cy-gb","id":140}},{"141":{"name":"Icelandic","code":"is-is","id":141}},{"142":{"name":"Taiwan Chinese","code":"zh-sg","id":142}},{"143":{"name":"Tigrinya","code":"ti-bo","id":143}},{"144":{"name":"Spanish","code":"es-la","id":144}},{"145":{"name":"French","code":"fr-ca","id":145}},{"146":{"name":"English","code":"en-ca","id":146}},{"147":{"name":"English","code":"en-sg","id":147}},{"148":{"name":"English","code":"en-us","id":148}},{"149":{"name":"Spanish","code":"es-cl","id":149}},{"150":{"name":"Spanish","code":"es-co","id":150}},{"151":{"name":"Spanish","code":"es-mx","id":151}},{"152":{"name":"Spanish","code":"es-ve","id":152}},{"153":{"name":"Norwegian Bokmål","code":"nb-no","id":153}},{"154":{"name":"Norwegian Nynorsk","code":"nn-no","id":154}},{"500":{"name":"Pseudo language","code":"xx-xx","id":500}}]}
EOFJSON;
        return Mage::helper('core')->jsonDecode($languages_json);
    }

    public function getLanguages() {
        $languages = $this->getTransfluentLangs();
        $langs = array();

        foreach ($languages['response'] as $lang) {
            foreach ($lang as $_lang) {
                $langs[] = $_lang;
            }
        }

        return $langs;
    }

    public function DefaultLevel() {
        return Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_quality');
    }

    public function SetDefaultLevel($level) {
        try {
            $coreConfig = Mage::getModel('core/config');
            $coreConfig->saveConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_quality', $level);
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function DefaultSourceLanguage($store_id = 0) {
        return Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language', $store_id);
    }

    public function SetDefaultSourceLanguage($language_id) {
        try {
            $coreConfig = Mage::getModel('core/config');
            $coreConfig->saveConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language', $language_id);
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function getSourceLanguages() {
        $stores = Mage::app()->getStores();
        if (!$stores || count($stores) < 2) {
            return array();
        }
        $helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $helper */
        $source_languages = array();
        $current_store = Mage::app()->getStore(true);
        foreach ($stores AS $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($current_store && $current_store->getCode() == $store->getCode()) {
                continue;
            }
            $source_languages[] = $helper->GetStoreLocale($store->getCode());
        }
        return $source_languages;
    }

    public function getSourceLanguageSelectHtmlForStores($stores) {
        $helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $helper */
        $default_source_language_id = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language');
        $html = '<select id="translateto" class="translateto_select">';
        $stores_by_websites = array();
        foreach ($stores AS $store) {
            /** @var Mage_Core_Model_Store $store */
            if (!isset($stores_by_websites[$store->getWebsiteId()])) {
                $stores_by_websites[$store->getWebsiteId()] = array();
            }
            $stores_by_websites[$store->getWebsiteId()][] = $store;
        }
        $current_website = null;
        $current_store_frontend_name = null;
        foreach ($stores_by_websites AS $website_id => $website_stores) {
            foreach ($website_stores AS $store) {
                /** @var Mage_Core_Model_Store $store */
                $store_language = $this->GetStoreLocale($store->getCode());
                $language_id = $this->getLangByCode($store_language, true);
                if (is_null($language_id)) {
                    // Language is unsupported
                    continue;
                }
                if (!$current_website || $current_website != $website_id) {
                    $current_website = $website_id;
                    $html .= '<optgroup label="' . $store->getWebsite()->getName() . '"></optgroup>';
                }
                if (!$current_store_frontend_name || $current_store_frontend_name != $store->getFrontendName()) {
                    $html .= '<optgroup label="&nbsp;&nbsp;' . $store->getFrontendName() . '"></optgroup>';
                    $current_store_frontend_name = $store->getFrontendName();
                }
                $is_selected = ($default_source_language_id == $language_id);
                $html .= '<option value="' . $store->getId() . '"' . ($is_selected ? ' selected="SELECTED"' : '') . '>&nbsp;&nbsp;&nbsp;&nbsp;' . $store->getName() . ' (' . Mage::getStoreConfig('general/locale/code', $store->getId()) . '; ' . $helper->getLanguageNameByCode(Mage::getStoreConfig('general/locale/code', $store->getId()), true) . ')</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    public function getSourceLanguageSelectHtml($stores) {
        $helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $helper */
        $default_source_language_id = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language');
        $html = '<select id="store_language" class="translateto_select">';
        $stores_by_websites = array();
        foreach ($stores AS $store_id) {
            $store = Mage::app()->getStore($store_id);
            /** @var Mage_Core_Model_Store $store */
            if (!isset($stores_by_websites[$store->getWebsiteId()])) {
                $stores_by_websites[$store->getWebsiteId()] = array();
            }
            $stores_by_websites[$store->getWebsiteId()][] = $store;
        }
        $current_website = null;
        $current_store_frontend_name = null;
        foreach ($stores_by_websites AS $website_id => $website_stores) {
            foreach ($website_stores AS $store) {
                $language_code = $this->GetStoreLocale($store->getId());
                $language_id = $this->getLangByCode($language_code, true);
                if (is_null($language_id)) {
                    // Language is unsupported
                    continue;
                }
                if (!$current_website || $current_website != $website_id) {
                    $current_website = $website_id;
                    $html .= '<optgroup label="' . $store->getWebsite()->getName() . '"></optgroup>';
                }
                if (!$current_store_frontend_name || $current_store_frontend_name != $store->getFrontendName()) {
                    $html .= '<optgroup label="&nbsp;&nbsp;' . $store->getFrontendName() . '"></optgroup>';
                    $current_store_frontend_name = $store->getFrontendName();
                }
                $is_selected = ($default_source_language_id == $language_id);
                $html .= '<option value="' . $store->getId() . '"' . ($is_selected ? ' selected="SELECTED"' : '') . '>&nbsp;&nbsp;&nbsp;&nbsp;' . $store->getName() . ' (' . Mage::getStoreConfig('general/locale/code', $store->getId()) . '; ' . $helper->getLanguageNameByCode(Mage::getStoreConfig('general/locale/code', $store->getId()), true) . ')</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * @param Mage_Core_Model_Store[] $stores
     * @param null $selectTagId
     *
     * @return string
     */
    public function getSourceLanguageSelectForOrderHtml(array $stores, $selectTagId = null) {

        if (null == $selectTagId) {
            $selectTagId = "store_language";
        }
        $default_source_language_id = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language');
        $html = "<select id=\"$selectTagId\" class='translateto_select'>";
        $html .= "<option class='disabled_item' disabled>";
        foreach ($stores AS $store) {
            $language_code = $this->GetStoreLocale($store->getId());
            $language_id = $this->getLangByCode($language_code, true);
            if (is_null($language_id)) {
                // Language is unsupported
                continue;
            }
            $is_selected = ($default_source_language_id == $language_id);
            $html .= '<option value="' . $store->getId() . '"' . ($is_selected ? ' selected="SELECTED"' : '') . '>' . $store->getName() . ' (' . $this->getLanguageNameByCode($language_code, true) . ')</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public function getSourceLanguageArrayForStores($stores) {
        $stores_array = array();
        foreach ($stores AS $store) {
            /** @var Mage_Core_Model_Store $store */
            $store_language = $this->GetStoreLocale($store->getCode());
            $language_id = $this->getLangByCode($store_language, true);
            if (is_null($language_id)) {
                // Language is unsupported
                continue;
            }
            $stores_array[$store->getId()] = $this->getLanguageNameByCode($store_language, true) . ' (' . $store->getName() . ')';
        }
        return $stores_array;
    }

    public function DefaultProductFieldsToTranslate() {
        return array(
            'name', 'short_description', 'description'
        );
    }

    public function getSourceLanguageArray($store_ids, $default_source_language_id = null) {
        $helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $helper */
        $languages = array();
        $selected_language = null;
        $selected_language_store_id = null;
        $stores_by_websites = array();
        foreach ($store_ids AS $store_id) {
            $store = Mage::app()->getStore($store_id);
            /** @var Mage_Core_Model_Store $store */
            if (!isset($stores_by_websites[$store->getWebsiteId()])) {
                $stores_by_websites[$store->getWebsiteId()] = array();
            }
            $stores_by_websites[$store->getWebsiteId()][] = $store;
        }
        $current_website = null;
        $current_store_frontend_name = null;
        foreach ($stores_by_websites AS $website_id => $website_stores) {
            foreach ($website_stores AS $store) {
                $language_code = $this->GetStoreLocale($store->getId());
                $language_id = $this->getLangByCode($language_code, true);
                if (is_null($language_id)) {
                    // Language is unsupported
                    continue;
                }
                if (!$current_website || $current_website != $website_id) {
                    $current_website = $website_id;
                    $languages['website-' . $store->getWebsite()->getId()] = '' . $store->getWebsite()->getName() . '';
                }
                if (!$current_store_frontend_name || $current_store_frontend_name != $store->getFrontendName()) {
                    $languages['front-' . $store->getId()] = '  ' . $store->getFrontendName() . '';
                    $current_store_frontend_name = $store->getFrontendName();
                }
                $languages[(string)$store->getId()] = '    ' . $store->getName() . ' (' . Mage::getStoreConfig('general/locale/code', $store->getId()) . '; ' . $helper->getLanguageNameByCode(Mage::getStoreConfig('general/locale/code', $store->getId()), true) . ')';
            }
        }
        return $languages;
    }

    public function getLanguagesHtml($storeId = 0) {
        $languages = $this->getLanguages();
        $helper = Mage::helper('transfluenttranslate');

        if (is_int($storeId)) {
            $def = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language', $storeId);
        } else {
            $def = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_language', $helper->getStoreByCode($storeId));
        }

        $html = '<select id="translateto" class="translateto_select">';

        foreach ($languages as $language) {
            $selected = ($language['id'] == $def) ? ' selected="selected"' : '';
            $html .= '<option value="' . $language['id'] . '"' . $selected . '>' . $language['name'] . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public function getLangById($id) {
        $languages = $this->getLanguages();
        $lang_code = '';

        foreach ($languages as $lang) {
            if ($lang['id'] == $id) {
                $lang_code = $lang['code'];
            }
        }

        return $lang_code;
    }

    public function GetStoreLocale($store_code) {
        return Mage::getStoreConfig('general/locale/code', $store_code);
    }

    private function ConvertAlternativeCode($code) {
        return str_replace('_', '-', strtolower($code));
    }

    public function getLanguageNameByCode($code, $alternative_formatting = false) {
        if ($alternative_formatting) {
            $code = $this->ConvertAlternativeCode($code);
        }
        $languages = $this->getLanguages();
        foreach ($languages AS $language) {
            if ($language['code'] == $code) {
                return $language['name'];
            }
        }
        return $code;
    }

    public function getLangByCode($code, $alternative_formatting = false) {
        if ($alternative_formatting) {
            $code = $this->ConvertAlternativeCode($code);
        }
        $languages = $this->getLanguages();
        $lang_id = null;

        foreach ($languages as $lang) {
            if ($lang['code'] == $code) {
                $lang_id = $lang['id'];
            }
        }

        return $lang_id;
    }

    public function getQualityArray($default_level = null) {
        $levels = array(
            '1' => 'Native speaker',
            '2' => 'Professional translator',
        );
        $levels_out = array();
        if ($default_level) {
            $levels_out[$default_level] = $levels[$default_level];
        }
        foreach ($levels AS $level_id => $level_desc) {
            if ($level_id == $default_level) {
                continue;
            }
            $levels_out[$level_id] = $level_desc;
        }
        return $levels_out;
    }

    public function getQualityValue($id) {
        $arr = $this->getQualityArray();

        return $arr[$id];
    }

    public function getQualityCode($val) {
        $arr = $this->getQualityArray();
        $id = '';

        foreach ($arr as $key => $_a) {
            if ($_a == $val) {
                $id = $key;
            }
        }

        return $id;
    }

    public function getQualityHtml($storeId = 0) {
        $qualities = $this->getQualityArray();
        $helper = Mage::helper('transfluenttranslate');

        if (is_int($storeId)) {
            $def = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_quality', $storeId);
        } else {
            $def = Mage::getStoreConfig('transfluenttranslate/transfluenttranslate_settings/transfluent_default_quality', $helper->getStoreByCode($storeId));
        }

        $html = '<select id="tf_translate_level" class="translate_quality">';
        foreach ($qualities as $quality => $value) {
            $selected = ($quality == $def) ? ' selected="selected"' : '';
            $html .= '<option value="' . $quality . '"' . $selected . '>' . $value . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * get translate fields
     *
     * @param Mage_Core_Model_Abstract $product
     * @param $force_translate
     * @param $store_to_id
     * @param $fields_to_translate_in
     *
     * @return mixed
     */
    public function getTranslateFields(Mage_Core_Model_Abstract $product, $force_translate, $store_to_id, $fields_to_translate_in) {

        $fieldData = $this->GetTranslationFieldData($product, $force_translate, $store_to_id, $fields_to_translate_in);
        return $fieldData['translateFields'];
    }

    /**
     * @param Mage_Core_Model_Abstract $product
     * @param $force_translate
     * @param $store_to_id
     * @param $fields_to_translate_in
     *
     * @return mixed
     */
    public function getAlreadyTranslatedFields(Mage_Core_Model_Abstract $product, $force_translate, $store_to_id, $fields_to_translate_in) {
        $fieldData = $this->GetTranslationFieldData($product, $force_translate, $store_to_id, $fields_to_translate_in);
        return $fieldData['alreadyTranslatedFields'];
    }

    /**
     * @param Mage_Core_Model_Abstract $product
     * @param $force_translate
     * @param $store_to_id
     * @param $fields_to_translate_in
     *
     * @return array
     */
    private function GetTranslationFieldData(Mage_Core_Model_Abstract $product, $force_translate, $store_to_id, $fields_to_translate_in) {
        $model = Mage::getModel('catalog/product');
        /** @var Mage_Catalog_Model_Product $model */

        $translated_product = $model->setStoreId($store_to_id)->load($product->getId());
        /** @var Mage_Catalog_Model_Product $translated_product */

        $language_helper = Mage::helper('transfluenttranslate/languages');
        /** @var Transfluent_Translate_Helper_Languages $language_helper */

        $fields_already_translated = array();
        $translate_fields = array();
        if (empty($fields_to_translate_in)) {
            foreach ($language_helper->DefaultProductFieldsToTranslate() AS $default_field_to_translate) {
                if ($force_translate || !$translated_product->getExistsStoreValueFlag($default_field_to_translate)) {
                    $translate_fields[] = $default_field_to_translate;
                } else {
                    $fields_already_translated[] = $default_field_to_translate;
                }
            }
        } else {
            foreach ($fields_to_translate_in AS $field_to_translate) {
                if ($force_translate || !$translated_product->getExistsStoreValueFlag($field_to_translate)) {
                    $translate_fields[] = $field_to_translate;
                } else {
                    $fields_already_translated[] = $field_to_translate;
                }
            }
        }

        return array(
            'translateFields' => $translate_fields,
            'alreadyTranslatedFields' => $fields_already_translated
        );
    }
}
