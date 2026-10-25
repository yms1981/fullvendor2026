<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Image;
use App\Models\Company;
use App\Http\Controllers\ProductImageController;
use Illuminate\Foundation\Testing\RefreshDatabase;

// This is a simple test script to verify the fixImagesOrder implementation

// Create a test database connection
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Starting test for fixImagesOrder method...\n";

try {
    // Create a test company
    $company = Company::first();
    if (!$company) {
        echo "No company found in the database. Please ensure there's at least one company.\n";
        exit(1);
    }

    echo "Using company ID: {$company->id}\n";

    // Create a test product
    $product = new Product();
    $product->company_id = $company->id;
    $product->name = "Test Product for fixImagesOrder " . time();
    $product->save();

    echo "Created test product with ID: {$product->pro_id}\n";

    // Create test images with non-sequential order values
    $orderValues = [1, 3, 5, 8, 10];
    $productImages = [];

    foreach ($orderValues as $order) {
        // Create an image
        $image = new Image();
        $image->company_id = $company->id;
        $image->save();

        // Create a product image with non-sequential order
        $productImage = new ProductImage();
        $productImage->product_id = $product->pro_id;
        $productImage->image_id = $image->id;
        $productImage->img_order = $order;
        $productImage->save();

        $productImages[] = $productImage;
    }

    echo "Created " . count($productImages) . " product images with non-sequential order values: " . implode(", ", $orderValues) . "\n";

    // Verify the current order values
    $currentImages = ProductImage::where('product_id', $product->pro_id)
        ->orderBy('img_order')
        ->get();

    echo "Current image order values: ";
    foreach ($currentImages as $img) {
        echo $img->img_order . " ";
    }
    echo "\n";

    // Create an instance of the ProductImageController
    $controller = new ProductImageController();

    // Call a method that should trigger fixImagesOrder
    // We'll use reflection to call the protected fixImagesOrder method directly
    $reflectionMethod = new ReflectionMethod(ProductImageController::class, 'fixImagesOrder');
    $reflectionMethod->setAccessible(true);
    $result = $reflectionMethod->invoke($controller, $product->pro_id);

    echo "fixImagesOrder result: " . ($result ? "Changes were made" : "No changes needed") . "\n";

    // Verify the new order values
    $updatedImages = ProductImage::where('product_id', $product->pro_id)
        ->orderBy('img_order')
        ->get();

    echo "Updated image order values: ";
    $isSequential = true;
    $expectedOrder = 1;

    foreach ($updatedImages as $img) {
        echo $img->img_order . " ";
        if ($img->img_order != $expectedOrder) {
            $isSequential = false;
        }
        $expectedOrder++;
    }
    echo "\n";

    if ($isSequential) {
        echo "SUCCESS: Image order values are now sequential.\n";
    } else {
        echo "FAILURE: Image order values are not sequential.\n";
    }

    // Clean up
    foreach ($productImages as $img) {
        $img->delete();
    }
    $product->delete();

    echo "Test completed and test data cleaned up.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
