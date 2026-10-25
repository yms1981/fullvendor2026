<?php
/** @var array<string, string> $pt */
/** @var array<int|string, mixed>|object|null $language */
/** @var object|null $d plan row on edit; null on add */
$langRows = isset($language) && (is_array($language) || is_object($language)) ? $language : [];
$drow = isset($d) && is_object($d) ? $d : null;
$l1 = $drow && isset($drow->language1_id) ? (int) $drow->language1_id : 0;
$l2 = $drow && isset($drow->language2_id) ? (int) $drow->language2_id : 0;
// Compatibilidad si aún existe language_id en filas antiguas no migradas
if ($l1 === 0 && $drow && isset($drow->language_id) && (int) $drow->language_id > 0) {
	$l1 = (int) $drow->language_id;
}
$use1 = $l1 > 0;
$use2 = $l2 > 0;
?>
<div class="form-group row">
  <div class="col-md-6">
    <label class="d-block"><?= htmlspecialchars((string) ($pt['label_language_1'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
    <input type="hidden" name="language1_use" value="0">
    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="plan_lang1_use" name="language1_use" value="1" <?= $use1 ? 'checked' : '' ?>>
      <label class="custom-control-label" for="plan_lang1_use"><?= htmlspecialchars((string) ($pt['plan_language_checkbox_assign'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
    </div>
    <select class="form-control" name="language1_id" id="language1_id" <?= $use1 ? '' : 'disabled' ?>>
      <option value=""><?= htmlspecialchars((string) ($pt['select_language'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
      <?php foreach ($langRows as $lang) : ?>
        <?php
        $lid = is_object($lang) ? (int) ($lang->language_id ?? 0) : (int) ($lang['language_id'] ?? 0);
        if ($lid < 1) {
          continue;
        }
        $nm = is_object($lang) ? trim((string) ($lang->name ?? '')) : trim((string) ($lang['name'] ?? ''));
        ?>
        <option value="<?= $lid ?>" <?= $lid === $l1 ? 'selected' : '' ?>><?= htmlspecialchars($nm, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="d-block"><?= htmlspecialchars((string) ($pt['label_language_2'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
    <input type="hidden" name="language2_use" value="0">
    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="plan_lang2_use" name="language2_use" value="1" <?= $use2 ? 'checked' : '' ?>>
      <label class="custom-control-label" for="plan_lang2_use"><?= htmlspecialchars((string) ($pt['plan_language_checkbox_assign'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
    </div>
    <select class="form-control" name="language2_id" id="language2_id" <?= $use2 ? '' : 'disabled' ?>>
      <option value=""><?= htmlspecialchars((string) ($pt['select_language'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
      <?php foreach ($langRows as $lang) : ?>
        <?php
        $lid = is_object($lang) ? (int) ($lang->language_id ?? 0) : (int) ($lang['language_id'] ?? 0);
        if ($lid < 1) {
          continue;
        }
        $nm = is_object($lang) ? trim((string) ($lang->name ?? '')) : trim((string) ($lang['name'] ?? ''));
        ?>
        <option value="<?= $lid ?>" <?= $lid === $l2 ? 'selected' : '' ?>><?= htmlspecialchars($nm, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
<script>
(function () {
  function wire(slot) {
    var cb = document.getElementById(slot === 1 ? 'plan_lang1_use' : 'plan_lang2_use');
    var sel = document.getElementById('language' + slot + '_id');
    if (!cb || !sel) return;
    function sync() {
      sel.disabled = !cb.checked;
      if (!cb.checked) { sel.value = ''; }
    }
    cb.addEventListener('change', sync);
    sync();
  }
  wire(1);
  wire(2);
})();
</script>
