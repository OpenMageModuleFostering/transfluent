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
    public function getCategoryArray() {
        $category = Mage::getModel('catalog/category');
        $tree = $category->getTreeModel();
        $tree->load();
        $ids = $tree->getCollection()->getAllIds();

        $arr = $this->categoriesToArray($ids);
        return $arr;
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
     * @return string
     */
    public function getCategoriesHTML() {
        $categories = $this->getCategoryArray();
        $html = $this->categoryArrayToHtml($categories);
        return $html;
    }

    /**
     * @param $categories
     * @return string
     */
    private function categoryArrayToHtml($categories) {
        $html = "<ul style=\"margin-left: 15px\">";
        foreach ($categories as $category) {
            $html .= "<li>";
            $html .= "<input type='checkbox' name='chk_group[]' value=" . $category['value'] . " /> " . $category['label']
                . " (" . $category['productCount'] . ")" . "<br/>";

            if ($category['children']) {
                $html .= $this->categoryArrayToHtml($category['children']);
            }

            $html .= "</li>";
        }

        $html .= "</ul>";
        return $html;
    }
}
