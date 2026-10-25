<?php
/**
 * Test Image Upload Controller
 *
 * This controller provides a simple way to test image uploads for products
 */
class TestImageUpload extends Company_Controller
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('langmodel');
    }

    /**
     * Index method - displays the test page
     */
    public function index()
    {
        if($this->input->server('REQUEST_METHOD') === 'POST'){
            $this->upload();
            exit;
        }
        $companyId = $this->getCompanyId();
        $languageId = $this->getUserData('language_id');
        $company = $this->getCompany();

        // Get all products for the company
        $products = $this->getObjectFromApi("company/{$companyId}/products", ['limit' => 10]);

        $data = [
            'company' => $company,
            'products' => $products,
            'pageTexts' => $this->langmodel->getMdl('company-product', $languageId)
        ];

        $this->load->view('company/product/test_image_upload', $data);
    }

    /**
     * Upload image for a product
     */
    public function upload()
    {
        $companyId = $this->getCompanyId();
        $productId = $this->input->post('product_id');
        // Check if product exists
        $product = $this->getObjectFromApi("product/{$productId}");
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
            echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error'. print_r($_FILES, true) ]);;
            return;
        }

        // Generate a unique filename
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newImageName = $product->sku ."." . $extension;
        $tmpName = $_FILES['image']['tmp_name'];

        $multipart = [
            [
                'name' => 'contents',
                'contents' => fopen($tmpName, 'r'),
                'filename' => $_FILES['files']['name']
            ],
            [
                'name' => 'name',
                'contents' => $newImageName
            ],
            [
                'name' => 'company_id',
                'contents' => $companyId
            ]
        ];
        // Upload the image using the API with multipart content
        $image = $this->apiRequest(
            'images',
            'POST',
            [ 'multipart'=>$multipart], false);

        $result = $this->post("product/{$productId}/image/{$image->uuid}", ['images' => $newImageName]);

        if (isset($result['success']) && $result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'image_name' => $newImageName,
                'product_id' => $productId,
                'image' => $image,
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error uploading image: ' . (isset($result['error']) ? $result['error'] : 'Unknown error') .
                        " ". $result['message'] . var_export($result,true)
            ]);
        }
    }
}
