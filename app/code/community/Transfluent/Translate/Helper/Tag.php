<?php

/**
 * Class Transfluent_Translate_Helper_Tag
 */
class Transfluent_Translate_Helper_Tag extends Mage_Core_Helper_Abstract
{
    /**
     * @param array $tags
     * @param $store_from_id
     *
     * @return array
     */
    public function getTags(array $tags, $store_from_id)
    {
        $model = Mage::getModel('tag/tag');
        /** @var Mage_Tag_Model_Tag $model */
        $tag_models = array();
        foreach ($tags AS $tag_id) {
            $tag = $model->setStoreId($store_from_id)->load($tag_id);
            /** @var Mage_Tag_Model_Tag $tag */
            if (!$tag || !$tag->getId()) {
                continue;
            }
            if (!$tag->isAvailableInStore($store_from_id)) {
                continue;
            }
            /** @var Mage_Tag_Model_Tag $tag */
            $tag_models[] = $tag;
        }

        return $tag_models;
    }

}
