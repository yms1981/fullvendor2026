<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;
    use HandlesImageUpload;
    use SupportsCategoryValidationMessages;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    public function run()
    {
        $postData = $this->input->post();
        $postData = $this->prepareImageData($postData, 'category_image');

        $response = $this->multipartPost("{$this->entityPath}", $postData);

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

        // La API sincroniza el catálogo FullVendor automáticamente al crear la categoría.
        $this->jsonSuccess($this->langArr['category_saved'] ?? 'Category saved successfully.');
    }
}
