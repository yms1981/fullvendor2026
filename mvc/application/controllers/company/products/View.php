<?php
require_once APPPATH . 'controllers/company/products/Form.php';
class View extends Form
{
    use IsAction;

    public function run($pro_id = null)
    {
        $product = $this->getObjectFromApi("product/{$pro_id}", [
            'relations' => ['categories', 'images', 'default_image', 'product_images.image', 'unit'],
        ]);

        if (!$product || (is_object($product) && isset($product->error))) {
            show_404();
        }

        // Misma lógica que Edit: imagen principal por img_order.
        try {
            $productImages = $this->getObjectFromApi("product/{$pro_id}/images");
            if (!empty($productImages) && (is_array($productImages) || is_object($productImages))) {
                $productImagesArray = is_array($productImages) ? $productImages : (array) $productImages;
                $firstOrderedImage = null;
                $minOrder = PHP_INT_MAX;
                foreach ($productImagesArray as $pi) {
                    $piObj = is_object($pi) ? $pi : (object) $pi;
                    if (isset($piObj->image_id) && isset($piObj->img_order)) {
                        $order = (int) $piObj->img_order;
                        if ($order > 0 && $order < $minOrder) {
                            $minOrder = $order;
                            $firstOrderedImage = $piObj->image_id;
                        }
                    }
                }
                if ($firstOrderedImage) {
                    $product->first_ordered_image = $firstOrderedImage;
                }
            }
        } catch (\Exception $e) {
            // Continuar sin interrumpir la vista.
        }

        $data = array('title' => 'View Product Details', 'page' => 'product');
        $data['product'] = $product;

        $data['groups'] = $this->fetchProductPriceGroups($product);

        $this->showContent('company/product/product_view', $data);
    }

}
