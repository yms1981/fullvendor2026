<?php
$texts = is_array($texts ?? null) ? $texts : [];
$items = is_array($items ?? null) ? $items : [];
$t = static function (string $key) use ($texts): string {
    return (string) ($texts[$key] ?? '');
};
$grouped = [];
foreach ($items as $item) {
    $grouped[$item['kind']][] = $item;
}
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/sendtemplate/sendtemplate.css') ?>">
<div class="fv-sendtpl">
    <header class="fv-sendtpl__head">
        <h1><?= htmlspecialchars($t('page_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($t('page_intro'), ENT_QUOTES, 'UTF-8') ?></p>
    </header>
    <?php foreach ($grouped as $kind => $rows): ?>
        <section class="fv-sendtpl__group">
            <h2><?= htmlspecialchars($t('kind_' . $kind), ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="fv-sendtpl__grid">
                <?php foreach ($rows as $row):
                    $channel = (string) $row['channel'];
                    $isEmail = $channel === 'email';
                    $previewSubject = (string) ($row['preview_subject'] ?? '');
                    $previewBody = (string) ($row['preview_body'] ?? '');
                    $cardId = 'fv-st-' . $row['kind'] . '-' . $channel;
                    ?>
                    <article class="fv-sendtpl__card" data-preview-id="<?= htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="fv-sendtpl__card-top">
                            <span class="fv-sendtpl__channel fv-sendtpl__channel--<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($t('channel_' . $channel), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($isEmail): ?>
                                <span class="fv-sendtpl__badge"><?= htmlspecialchars($t('html_badge'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isEmail && $previewSubject !== ''): ?>
                            <p class="fv-sendtpl__subject"><?= htmlspecialchars($previewSubject, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <div class="fv-sendtpl__mini-preview fv-sendtpl__mini-preview--<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($isEmail): ?>
                                <iframe
                                    class="fv-sendtpl__mini-frame"
                                    title="<?= htmlspecialchars($t('preview'), ENT_QUOTES, 'UTF-8') ?>"
                                    srcdoc="<?= htmlspecialchars($previewBody, ENT_QUOTES, 'UTF-8') ?>"
                                ></iframe>
                            <?php else: ?>
                                <div class="fv-sendtpl__bubble fv-sendtpl__bubble--<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= nl2br(htmlspecialchars($previewBody, ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <script type="application/json" class="fv-sendtpl__payload"><?= json_encode([
                            'channel' => $channel,
                            'title' => $t('kind_' . $row['kind']) . ' · ' . $t('channel_' . $channel),
                            'subject' => $previewSubject,
                            'body' => $previewBody,
                        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                        <div class="fv-sendtpl__card-actions">
                            <button type="button" class="fv-sendtpl__preview-btn" data-preview-open="<?= htmlspecialchars($cardId, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($t('preview'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <a class="fv-sendtpl__edit" href="<?= company_url('sendtemplate/edit/' . $row['kind'] . '/' . $channel) ?>">
                                <?= htmlspecialchars($t('edit'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<div class="fv-sendtpl-modal" id="fv-sendtpl-modal" hidden>
    <div class="fv-sendtpl-modal__backdrop" data-preview-close></div>
    <div class="fv-sendtpl-modal__panel" role="dialog" aria-modal="true" aria-labelledby="fv-sendtpl-modal-title">
        <header class="fv-sendtpl-modal__head">
            <h2 id="fv-sendtpl-modal-title"></h2>
            <button type="button" class="fv-sendtpl-modal__close" data-preview-close aria-label="<?= htmlspecialchars($t('close'), ENT_QUOTES, 'UTF-8') ?>">×</button>
        </header>
        <p class="fv-sendtpl-modal__subject" id="fv-sendtpl-modal-subject" hidden></p>
        <div class="fv-sendtpl-modal__body">
            <iframe class="fv-sendtpl-modal__frame" id="fv-sendtpl-modal-frame" title="<?= htmlspecialchars($t('preview'), ENT_QUOTES, 'UTF-8') ?>" hidden></iframe>
            <div class="fv-sendtpl-modal__text" id="fv-sendtpl-modal-text" hidden></div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('fv-sendtpl-modal');
    if (!modal) return;
    var titleEl = document.getElementById('fv-sendtpl-modal-title');
    var subjectEl = document.getElementById('fv-sendtpl-modal-subject');
    var frameEl = document.getElementById('fv-sendtpl-modal-frame');
    var textEl = document.getElementById('fv-sendtpl-modal-text');

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('fv-sendtpl-modal-open');
        if (frameEl) frameEl.removeAttribute('srcdoc');
    }

    function openModal(card) {
        var payloadNode = card.querySelector('.fv-sendtpl__payload');
        if (!payloadNode) return;
        var data;
        try {
            data = JSON.parse(payloadNode.textContent || '{}');
        } catch (e) {
            return;
        }
        titleEl.textContent = data.title || '';
        if (data.channel === 'email') {
            subjectEl.hidden = !(data.subject);
            subjectEl.textContent = data.subject || '';
            textEl.hidden = true;
            frameEl.hidden = false;
            frameEl.srcdoc = data.body || '';
        } else {
            subjectEl.hidden = true;
            frameEl.hidden = true;
            textEl.hidden = false;
            textEl.className = 'fv-sendtpl-modal__text fv-sendtpl-modal__text--' + (data.channel || 'sms');
            textEl.textContent = data.body || '';
        }
        modal.hidden = false;
        document.body.classList.add('fv-sendtpl-modal-open');
    }

    document.querySelectorAll('[data-preview-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-preview-open');
            var card = document.querySelector('[data-preview-id="' + id + '"]');
            if (card) openModal(card);
        });
    });
    modal.querySelectorAll('[data-preview-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
