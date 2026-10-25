<?php
/**
 * @var Object $category The editing category
 * @var array $pageTexts The translated string conglomerated for this view
 */

$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

$imageUuid = '';
$imageRef = $category->imageRecord ?? $category->image_record ?? $category->image ?? null;
if (is_object($imageRef) && ! empty($imageRef->uuid)) {
    $imageUuid = (string) $imageRef->uuid;
} elseif (is_string($imageRef) && $imageRef !== '') {
    if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $imageRef, $m)) {
        $imageUuid = $m[1];
    }
}
if ($imageUuid === '' && ! empty($category->image_uuid) && is_string($category->image_uuid)) {
    if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $category->image_uuid, $m)) {
        $imageUuid = $m[1];
    }
}
$image = $imageUuid !== ''
    ? (function_exists('fv_list_image_urls')
        ? (fv_list_image_urls($imageUuid, $imageRef)['primary'] ?: company_url('image/' . $imageUuid))
        : (function_exists('fv_api_image_url') ? fv_api_image_url($imageUuid) : company_url('image/' . $imageUuid)))
    : company_url('image');

$catId = (int) ($category->cat_id ?? 0);
$categoryName = (string) ($category->category_name ?? '');
$categoryStatus = (int) ($category->category_status ?? 1);
$categoryDefault = ! empty($category->category_default);
?>
<style>
.custom-switch-red .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc3545;
    border-color: #dc3545;
}
.custom-switch-red .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.custom-switch-red .custom-control-input:checked:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
<script>
window.fvCategoryFormLang = <?= json_encode([
    'category_name_already_exists' => $pageTexts['category_name_already_exists'] ?? '',
    'category_exists' => $pageTexts['category_exists'] ?? '',
    'category_save_error_generic' => $pageTexts['category_save_error_generic'] ?? '',
    'category_success' => $pageTexts['category_success'] ?? '',
    'category_updated' => $pageTexts['category_updated'] ?? '',
    'ok' => $pageTexts['ok'] ?? '',
    'loading' => $pageTexts['loading'] ?? '',
    'load_form_error' => $pageTexts['load_form_error'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<form id="form_edit_category_modal" class="forms-sample" action="<?= company_url('categories/edit/' . $catId) ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="cat_id" value="<?= $catId ?>">
    
    <div class="form-group">
        <label><?= htmlspecialchars((string) ($pageTexts['nombre_categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <span class="color-red">*</span> </label>
        <input id="category_name_modal" class="form-control" type="text" name="category_name"
               value="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>" required>
        <div class="container_alert"></div>
    </div>
    
    <div class="form-group">
        <label for="category_status_modal"><?= htmlspecialchars((string) ($pageTexts['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <span class="color-red">*</span></label>
        <div class="d-flex align-items-center" style="min-height: 2.25rem;">
            <?php if ($categoryDefault): ?>
                <input type="hidden" name="category_status" value="<?= $categoryStatus ?>">
            <?php else: ?>
                <input type="hidden" name="category_status" value="0">
            <?php endif; ?>
            <div class="custom-control custom-switch custom-switch-red mb-0"<?= $categoryDefault ? ' title="' . htmlspecialchars((string) ($pageTexts['error_disabled'] ?? ''), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                <input type="checkbox"
                       class="custom-control-input"
                       id="category_status_modal"
                       <?= $categoryDefault ? '' : 'name="category_status"' ?>
                       value="1"
                       <?= $categoryStatus === 1 ? 'checked' : '' ?>
                       <?= $categoryDefault ? 'disabled' : '' ?>>
                <label class="custom-control-label" for="category_status_modal"></label>
            </div>
            <span class="ml-2 text-muted small">
                <?= htmlspecialchars(
                    (string) ($categoryStatus === 1
                        ? ($pageTexts['enable'] ?? '')
                        : ($pageTexts['disable'] ?? '')),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </div>
    </div>

    <div class="form-group">
        <label><?= htmlspecialchars((string) ($pageTexts['imagen'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
        <div class="d-flex flex-column align-items-center">
            <figure style="width: 100%; max-width: 300px; overflow: hidden; margin: 0 0 15px 0;" class="rounded text-center">
                <img id="main-category-image-modal"
                     src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>"
                     class="rounded-0"
                     style="display: block; width: 100%; max-height: 200px; object-fit: contain; margin: 0 auto;"
                     alt="">
            </figure>

            <div class="text-center">
                <label for="category_image_input_modal" class="btn btn-success" style="cursor: pointer;">
                    <i class="fa-solid fa-upload"></i> <?= htmlspecialchars((string) ($pageTexts['select_imagen'] ?? $pageTexts['cargar_archivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </label>
                <input type="file"
                       id="category_image_input_modal"
                       name="image"
                       accept="image/*"
                       style="display: none;">
            </div>
        </div>
        <small class="form-text text-muted text-center d-block mt-2">
            <?= htmlspecialchars((string) ($pageTexts['span_format'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: jpg, jpeg, png |
            <?= htmlspecialchars((string) ($pageTexts['max_upload'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: 10mb
        </small>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= htmlspecialchars((string) ($pageTexts['boton_cancelar'] ?? $pageTexts['cancelar'] ?? $pageTexts['cancel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </button>
        <button id="boton_enviar_modal" type="submit" class="btn btn-success">
            <?= htmlspecialchars((string) ($pageTexts['guardar'] ?? $pageTexts['save'] ?? $pageTexts['boton_enviar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>
</form>

<script>
$(document).ready(function() {
    var enableLabel = <?= json_encode((string) ($pageTexts['enable'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var disableLabel = <?= json_encode((string) ($pageTexts['disable'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    $('#category_status_modal').on('change', function() {
        var $label = $(this).closest('.form-group').find('.text-muted.small');
        $label.text(this.checked ? enableLabel : disableLabel);
    });

    $('#category_image_input_modal').on('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert(<?= json_encode((string) (($pageTexts['max_upload'] ?? '') . ': 10mb'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                $(this).val('');
                return;
            }

            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert(<?= json_encode((string) (($pageTexts['span_format'] ?? '') . ': jpg, jpeg, png'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                $(this).val('');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                $('#main-category-image-modal').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
