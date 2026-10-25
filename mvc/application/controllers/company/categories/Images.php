<?php

/**
 * Images Controller for Categories
 *
 * This controller handles category image operations including uploading images.
 */
class Images extends Company_Controller
{
    use IsCRUD;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
    }

    /**
     * Index method
     *
     * Redirects to categories page
     */
    public function index()
    {
        redirect(company_url('categories'));
    }

    /**
     * Upload an image for a category
     * 
     * This method uploads an image to the API which will save it in MinIO
     * and the local filesystem
     */
    public function uploadImage()
    {
        $companyId = $this->getCompanyId();
        $tmpName = $_FILES['image']['tmp_name'];

        $multipart = [
            [
                'name' => 'contents',
                'contents' => fopen($tmpName, 'r'),
                'filename' => $_FILES['image']['name']
            ],
            [
                'name' => 'name',
                'contents' => $_FILES['image']['name']
            ],
            [
                'name' => 'company_id',
                'contents' => $companyId
            ],
            [
                'name' => 'type',
                'contents' => 'category'
            ]
        ];
        try {
            // Guarda en almacenamiento local: categories/{company_id}/ (IMAGES_DISK=images_local)
            $image = $this->apiRequest(
                'images',
                'POST',
                ['multipart' => $multipart],
                true  // Use associative array instead of object
            );
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $image = ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            $image = ['error' => $e->getMessage()];
        }

        header('Content-Type: application/json');
        print json_encode($image);
    }
}

