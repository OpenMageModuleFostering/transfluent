<?php

/**
 * Class Transfluent_Translate_Helper_Constants
 */
class Transfluent_Translate_Helper_Constant extends Mage_Core_Helper_Abstract {

    /**
     * returns non-translatable attributes
     *
     * @return array
     */
    public function getNonTranslatableAttributes() {
        return array(
            'weight',
            'status',
            'tax_class_id',
            'visibility',
            'news_from_date',
            'news_to_date',
            'price',
            'group_price',
            'cost',
            'tier_price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'enable_googlecheckout',
            'msrp_enabled',
            'msrp_display_actual_price_type',
            'msrp',
            'thumbnail',
            'small_image',
            'image',
            'gallery',
            'media_gallery',
            'custom_design_from',
            'custom_design_to',
            'custom_layout_update',
            'options_container',
            'page_layout',
            'is_recurring',
            'recurring_profile',
            'gift_message_available',
        );
    }
}
