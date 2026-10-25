<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    use HandlesImageUpload;
    use SupportsCategoryValidationMessages;

    protected $itemId;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    function run($categoryId=null)
    {
        $postData = $this->input->post();

        $response = $this->put("{$this->entityPath}/{$categoryId}", $postData);

        if ($this->hasError($response)) {
            $name = trim((string) ($postData['category_name'] ?? ''));
            $response = $this->localizeCategoryApiErrorResponse(
                is_array($response) ? $response : [],
                $name
            );
            $defaultMsg = $this->formatCategoryNameExistsMessage($name);
            $this->jsonError(
                $response,
                $defaultMsg !== '' ? $defaultMsg : ($this->langArr['category_exists'] ?? ''),
                422
            );
        }

        $postData = $this->prepareImageData($postData, 'category_image');

        if (isset($postData['image']))
            $this->multipartPost("{$this->entityPath}/{$categoryId}/updateImage", $postData);

        // La API sincroniza el catálogo FullVendor automáticamente al actualizar la categoría.
        $successMsg = $this->langArr['category_updated']
            ?? $this->langArr['category_success']
            ?? '';
        $this->jsonSuccess($successMsg);
    }
}
