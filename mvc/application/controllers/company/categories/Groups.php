<?php

class Groups extends Company_Controller
{
    use IsAction;
    use HandlesImageUpload;

    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    public function run($categoryId = null)
    {
        $categoryId = (int) $categoryId;
        if ($categoryId < 1) {
            show_404();
            return;
        }

        $category = $this->getObjectFromApi("category/{$categoryId}", [
            'relations' => ['imageRecord'],
        ]);

        if ($this->hasError($category)) {
            log_message('error', 'Groups::run - no se pudo cargar categoría ' . $categoryId);
            show_404();
            return;
        }

        if ((int) ($category->category_default ?? 0) === 1) {
            show_error($this->langArr['error_edit'] ?? '', 403);
            return;
        }

        if (empty($category->image_uuid) && is_object($category->imageRecord ?? null) && ! empty($category->imageRecord->uuid)) {
            $category->image_uuid = $category->imageRecord->uuid;
        } elseif (empty($category->image_uuid) && is_array($category->imageRecord ?? null) && ! empty($category->imageRecord['uuid'])) {
            $category->image_uuid = $category->imageRecord['uuid'];
        } elseif (empty($category->image_uuid) && is_string($category->image ?? null) && $category->image !== '') {
            if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $category->image, $m)) {
                $category->image_uuid = $m[1];
            }
        }

        $groupsResponse = $this->getDataFromApi("category/{$categoryId}/groups", [], true);
        $groups = [];
        if (!$this->hasError($groupsResponse)) {
            if (is_array($groupsResponse) && isset($groupsResponse['data']) && is_array($groupsResponse['data'])) {
                $groups = $groupsResponse['data'];
            }
        }

        $pageTexts = is_array($this->langArr) ? $this->langArr : [];
        $this->addJsFile(base_url('companyasset/company/categories/groups.js'));
        $this->showContent('company/categorias/groups', [
            'category' => $category,
            'groups' => $groups,
            'pageTexts' => $pageTexts,
            'pageTitle' => $pageTexts['manage_groups_title'] ?? ($pageTexts['define_groups'] ?? ''),
            'pageIcon' => 'fa-solid fa-folder-tree',
        ]);
    }

    public function saveGroup()
    {
        $categoryId = (int) $this->input->post('category_id');
        $groupName = trim((string) $this->input->post('group_name'));

        if ($categoryId < 1) {
            $this->jsonError(['error' => 'missing_category'], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }
        if ($groupName === '') {
            $this->jsonError(['error' => 'group_name'], $this->langArr['group_name_required'] ?? '', 422);
            return;
        }

        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $payload = $this->prepareImageData([
            'group_name' => $groupName,
        ], 'image');

        $response = $this->multipartPost("category/{$categoryId}/groups", $payload);

        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : [], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        $this->jsonSuccess($this->langArr['group_saved'] ?? '', is_array($response) ? $response : []);
    }

    public function updateGroupImage($groupId = null)
    {
        $groupId = (int) ($groupId ?: $this->input->post('group_id'));
        if ($groupId < 1) {
            $this->jsonError(['error' => 'missing_group'], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        if (empty($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError(['error' => 'missing_image'], $this->langArr['photo_required'] ?? '', 422);
            return;
        }

        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $payload = $this->prepareImageData([], 'image');
        $response = $this->multipartPost("categorygroup/{$groupId}", $payload);

        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : [], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        $this->jsonSuccess($this->langArr['photo_saved'] ?? ($this->langArr['group_saved'] ?? ''), is_array($response) ? $response : []);
    }

    public function deleteGroup($groupId = null)
    {
        $groupId = (int) ($groupId ?: $this->input->post('group_id'));
        if ($groupId < 1) {
            $this->jsonError(['error' => 'missing_group'], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        $response = $this->apiDelete("categorygroup/{$groupId}");
        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : [], $this->langArr['error_eliminar'] ?? '', 422);
            return;
        }

        $this->jsonSuccess($this->langArr['group_deleted'] ?? '');
    }

    public function saveSubgroup()
    {
        $groupId = (int) $this->input->post('group_id');
        $subgroupName = trim((string) $this->input->post('subgroup_name'));

        if ($groupId < 1) {
            $this->jsonError(['error' => 'missing_group'], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }
        if ($subgroupName === '') {
            $this->jsonError(['error' => 'subgroup_name'], $this->langArr['subgroup_name_required'] ?? '', 422);
            return;
        }

        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $payload = $this->prepareImageData([
            'subgroup_name' => $subgroupName,
        ], 'image');

        $response = $this->multipartPost("categorygroup/{$groupId}/subgroups", $payload);

        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : [], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        $this->jsonSuccess($this->langArr['subgroup_saved'] ?? '', is_array($response) ? $response : []);
    }

    public function updateSubgroupImage($subgroupId = null)
    {
        $subgroupId = (int) ($subgroupId ?: $this->input->post('subgroup_id'));
        if ($subgroupId < 1) {
            $this->jsonError(['error' => 'missing_subgroup'], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        if (empty($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError(['error' => 'missing_image'], $this->langArr['photo_required'] ?? '', 422);
            return;
        }

        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $payload = $this->prepareImageData([], 'image');
        $response = $this->multipartPost("categorysubgroup/{$subgroupId}", $payload);

        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : [], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        $this->jsonSuccess($this->langArr['photo_saved'] ?? ($this->langArr['subgroup_saved'] ?? ''), is_array($response) ? $response : []);
    }

    public function deleteSubgroup($subgroupId = null)
    {
        $subgroupId = (int) ($subgroupId ?: $this->input->post('subgroup_id'));
        if ($subgroupId < 1) {
            $this->jsonError(['error' => 'missing_subgroup'], $this->langArr['category_save_error_generic'] ?? '', 422);
            return;
        }

        $response = $this->apiDelete("categorysubgroup/{$subgroupId}");
        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : [], $this->langArr['error_eliminar'] ?? '', 422);
            return;
        }

        $this->jsonSuccess($this->langArr['subgroup_deleted'] ?? '');
    }

    /**
     * Árbol JSON de grupos/subgrupos para una o más categorías (selector en productos).
     * GET/POST category_ids[] o category_ids CSV.
     */
    public function tree()
    {
        $raw = $this->input->get_post('category_ids');
        if ($raw === null || $raw === '') {
            $raw = $this->input->get_post('category_id');
        }

        $ids = [];
        if (is_array($raw)) {
            $ids = array_map('intval', $raw);
        } elseif (is_string($raw) || is_numeric($raw)) {
            $parts = preg_split('/\s*,\s*/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $ids = array_map('intval', $parts);
        }
        $ids = array_values(array_unique(array_filter($ids, static function ($id) {
            return $id > 0;
        })));

        $tree = [];
        foreach ($ids as $categoryId) {
            $category = $this->getObjectFromApi("category/{$categoryId}", [
                'props' => ['cat_id', 'category_name', 'category_default'],
            ]);
            if ($this->hasError($category)) {
                continue;
            }

            $groupsResponse = $this->getDataFromApi("category/{$categoryId}/groups", [], true);
            $groups = [];
            if (!$this->hasError($groupsResponse) && is_array($groupsResponse['data'] ?? null)) {
                $groups = $groupsResponse['data'];
            }

            $normalizedGroups = [];
            foreach ($groups as $group) {
                $group = is_array($group) ? $group : (array) $group;
                $subgroups = $group['subgroups'] ?? [];
                if (is_object($subgroups)) {
                    $subgroups = (array) $subgroups;
                }
                if (! is_array($subgroups)) {
                    $subgroups = [];
                }
                $normalizedSubs = [];
                foreach ($subgroups as $subgroup) {
                    $subgroup = is_array($subgroup) ? $subgroup : (array) $subgroup;
                    $normalizedSubs[] = [
                        'subgroup_id' => (int) ($subgroup['subgroup_id'] ?? 0),
                        'subgroup_name' => (string) ($subgroup['subgroup_name'] ?? ''),
                        'image' => $subgroup['image'] ?? null,
                    ];
                }
                $normalizedGroups[] = [
                    'group_id' => (int) ($group['group_id'] ?? 0),
                    'group_name' => (string) ($group['group_name'] ?? ''),
                    'image' => $group['image'] ?? null,
                    'subgroups' => $normalizedSubs,
                ];
            }

            $tree[] = [
                'cat_id' => (int) ($category->cat_id ?? $categoryId),
                'category_name' => (string) ($category->category_name ?? ''),
                'groups' => $normalizedGroups,
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $tree]);
        exit;
    }
}
