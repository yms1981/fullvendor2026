<?php
/**
 * @var array $pageTexts The translated string conglomerated for this view
 */
?>
<div class="modal fade" id="modal_add_profile" tabindex="-1" role="dialog" aria-labelledby="modalAddProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddProfileLabel">
                    <?= htmlspecialchars((string) ($pageTexts['add_new_profile_vendor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal_add_profile_body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only"><?= htmlspecialchars((string) ($pageTexts['loading'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
