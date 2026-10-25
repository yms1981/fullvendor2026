<?php
require_once APPPATH . 'controllers/company/products/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($pro_id=null) {
        $product = $this->getObjectFromApi("product/{$pro_id}",[
            'relations'=>['categories','images','default_image','product_images.image','unit','categoryGroups','categorySubgroups']
        ]);

        // Obtener las ProductImage ordenadas por img_order para determinar la imagen principal (orden 1, luego 2, etc.)
        try {
            $productImages = $this->getObjectFromApi("product/{$pro_id}/images");
            if (!empty($productImages) && (is_array($productImages) || is_object($productImages))) {
                $productImagesArray = is_array($productImages) ? $productImages : (is_object($productImages) ? (array)$productImages : []);

                $firstOrderedImage = null;
                $minOrder = PHP_INT_MAX;

                foreach ($productImagesArray as $pi) {
                    $piObj = is_object($pi) ? $pi : (object) $pi;
                    if (isset($piObj->image_id) && $piObj->image_id !== '') {
                        $order = isset($piObj->img_order) ? (int) $piObj->img_order : 999999;
                        if ($order < $minOrder) {
                            $minOrder = $order;
                            $firstOrderedImage = $piObj->image_id;
                        }
                    }
                }

                if ($firstOrderedImage) {
                    // Agregar propiedad para que la vista pueda usar la imagen con orden 1 (o la siguiente disponible)
                    $product->first_ordered_image = $firstOrderedImage;
                    if (empty($product->default_image_id)) {
                        $product->default_image_id = $firstOrderedImage;
                    }
                    if (empty($product->product_image)) {
                        $product->product_image = $firstOrderedImage;
                    }
                }
            }
        } catch (\Exception $e) {
            // Si hay error al obtener ProductImages, continuar sin interrumpir la edición
        }

        $data = array('title' => 'Edit Product', 'page' => 'products', 'subpage' => 'productadd');
        $data['product'] = $product;

        $data['groups'] = $this->fetchProductPriceGroups($product);

        $this->addJsFile("/companyasset/company/product/semaforo.js");
        $this->addJsFile("/companyasset/company/product/change-sku.js");
        $this->addJsFile("/companyasset/company/product/category-groups.js");

        $this->showContent('company/product/product_edit', $data);
    }

}
