<?php

class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }
    public function run($category_id)
    {
        $category = $this->getObjectFromApi("{$this->entityPath}/{$category_id}", [
            'props' => ['cat_id', 'category_default', 'category_name'],
        ]);

        if (! $this->hasError($category) && (int) ($category->category_default ?? 0) === 1) {
            $this->jsonError(
                ['error' => 'default_category'],
                $this->langArr['error_delete'] ?? $this->langArr['error_edit'] ?? $this->langArr['error_eliminar'] ?? '',
                400
            );
            return;
        }

        $response = $this->destroy($category_id);

        if ($this->hasError($response)){
            $apiMsg = '';
            if (is_array($response)) {
                $apiMsg = (string) ($response['message'] ?? '');
            } elseif (is_object($response)) {
                $apiMsg = (string) ($response->message ?? '');
            }
            $isDefaultBlocked = stripos($apiMsg, 'default category') !== false
                || stripos($apiMsg, 'cannot be deleted') !== false;
            $msg = $isDefaultBlocked
                ? ($this->langArr['error_delete'] ?? $this->langArr['error_edit'] ?? $this->langArr['error_eliminar'] ?? '')
                : ($this->langArr['error_eliminar'] ?? '');
            $this->jsonError($response, $msg);
            return;
        }

        $successMsg = $this->langArr['deleted_success']
            ?? $this->langArr['eliminado_exito']
            ?? '';
        if ($successMsg === '') {
            $toltip = $this->getLangJsonSection('cmp-toltip');
            $successMsg = (string) ($toltip['success_deleted'] ?? '');
        }

        // La API sincroniza el catálogo FullVendor automáticamente al eliminar la categoría.
        $this->jsonSuccess($successMsg);
    }
}
