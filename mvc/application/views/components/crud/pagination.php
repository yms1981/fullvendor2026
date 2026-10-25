<?php
/**
 * @var object $page The page with the list, and the pagination info
 * @var array $pageTexts The page standard texts for the entity module listing
 */
?>
<div class="paginate fv-modern-grid__paginate w-100">
    <div class=" d-flex justify-content-between align-items-center" style="width: 99.9%">
        <?php
        $crudEntityNamePlural = $pageTexts['entityNamePlural'] ?? 'Entries';
        $pageFrom = isset($page->from) && $page->from !== '' && $page->from !== null
            ? (int) $page->from
            : ((isset($page->total) && (int) $page->total > 0) ? 1 : 0);
        $pageTo = isset($page->to) && $page->to !== '' && $page->to !== null
            ? (int) $page->to
            : (int) ($page->total ?? 0);
        $pageTotal = (int) ($page->total ?? 0);
        ?>
        <span class="showing"><?= htmlspecialchars((string) ($pageTexts['showing'] ?? 'Showing'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $crudEntityNamePlural, ENT_QUOTES, 'UTF-8') ?> <?= $pageFrom ?> <?= htmlspecialchars((string) ($pageTexts['to'] ?? 'to'), ENT_QUOTES, 'UTF-8') ?> <?= $pageTo ?> <?= htmlspecialchars((string) ($pageTexts['of'] ?? 'of'), ENT_QUOTES, 'UTF-8') ?> <?= $pageTotal ?></span>
        <?= $this->pagination->create_links() ?>
    </div>
</div>

