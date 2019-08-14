<?php

/**
 * Class Transfluent_Translate_Helper_Category
 */
class Transfluent_Translate_Helper_Category extends Mage_Core_Helper_Abstract {

    /**
     * get categories
     *
     * @return array
     */
    public function getCategoryIdsArray() {
        $category = Mage::getModel('catalog/category');
        $tree = $category->getTreeModel();
        $tree->load();
        $ids = $tree->getCollection()->getAllIds();
        return $ids;
    }

    private function categoriesToArray($ids, &$visited = array()) {
        $arr = array();
        if (!empty($ids) && is_array($ids)) {
            foreach ($ids as $id) {
                $ret = $this->categoryToArray($id, $visited);
                if ($ret !== null)
                    $arr[] = $ret;
            }
        }
        return $arr;
    }

    private function categoryToArray($id, &$visited) {
        if (in_array($id, $visited)) return null;

        $visited[] = $id;

        $arr = null;
        /** @var Mage_Catalog_Model_Category $cat */
        $cat = Mage::getModel('catalog/category');
        $cat->load($id);
        if ($cat->getName() && $cat->getProductCount()) {
            $arr = array(
                'value' => $id,
                'label' => $cat->getName(),
                'productCount' => $cat->getProductCount(),
                'children' => $this->categoriesToArray($cat->getAllChildren(true), $visited)
            );
        }
        return $arr;
    }

    /**
     * gets all the productIDs from all the categories
     *
     * @return string
     */
    public function getCategoryProducts() {
        $category = Mage::getModel('catalog/category');
        $tree = $category->getTreeModel();
        $tree->load();
        $ids = $tree->getCollection()->getAllIds();
        $products = array();

        $categoryData = null;
        foreach ($ids as $id) {
            $category = Mage::getModel('catalog/category')->load(intval($id));

            if ($category->getName() && $category->getProductCount()) {
                foreach ($category->getProductCollection() as $product) {
                    $product = Mage::getModel('catalog/product')->load($product->getId());
                    $products[$id][] = $product->getId();
                }
            }
        }


        return json_encode($products);
    }


    /**
     *  get checkbox html of categories
     *
     * @param array - category ids
     * @param array
     * @return string
     */
    public function getCategoriesHTML($category_ids = null, $selected_ids = array()) {
        if (is_null($category_ids)) {
            $category_ids = $this->getCategoryIdsArray();
        }
        $visited = array();
        $html = $this->categoryArrayToHtml($category_ids, $visited, $selected_ids);
        return $html;
    }

    /**
     * @param array
     * @param array
     * @param array
     * @return string
     */
    private function categoryArrayToHtml($category_ids, &$visited = array(), $selected_ids = array()) {
        ini_set('memory_limit', '128M');
        set_time_limit(0);

        $html = "<ul style=\"margin-left: 15px\">";
        foreach ($category_ids AS $category_id) {
            if (in_array($category_id, $visited)) continue;
            $visited[] = $category_id;
            /** @var Mage_Catalog_Model_Category $cat */
            $cat = Mage::getModel('catalog/category');
            $cat->load($category_id);
            if (!$cat->getName()) {
                continue;
            }
            $html .= "<li>";
            $html .= "<label><input type='checkbox' name='chk_group[]' " . (in_array($category_id, $selected_ids) ? 'checked="checked"' : '') . " value=" . $category_id . " /> " . $cat->getName() . '<br>';
                //. " (" . $cat->getProductCount() . ")" . "<br>";

            $cat_children_ids = $cat->getAllChildren(true);
            if (!empty($cat_children_ids)) {
                $html .= $this->categoryArrayToHtml($cat_children_ids, $visited, $selected_ids);
            }
            unset($cat_children_ids);

            $html .= "</label></li>";
        }

        $html .= "</ul>";
        return $html;
    }
}
