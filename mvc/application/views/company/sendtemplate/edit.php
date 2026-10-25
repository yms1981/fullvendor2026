<?php
$texts = is_array($texts ?? null) ? $texts : [];
$kind = (string) ($kind ?? '');
$channel = (string) ($channel ?? '');
$subject = (string) ($subject ?? '');
$body = (string) ($body ?? '');
$previewSubject = (string) ($preview_subject ?? '');
$previewBody = (string) ($preview_body ?? '');
$sampleMap = is_array($sampleMap ?? null) ? $sampleMap : [];
$t = static function (string $key) use ($texts): string {
    return (string) ($texts[$key] ?? '');
};
$notice = (string) ($this->session->flashdata('fv_sendtemplate_notice') ?? '');
$isEmail = $channel === 'email';
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/sendtemplate/sendtemplate.css') ?>">
<?php if ($isEmail): ?>
<link rel="stylesheet" href="<?= base_url('companyasset/vendors/summernote/dist/summernote-bs4.css') ?>">
<?php endif; ?>
<div class="fv-sendtpl fv-sendtpl--edit">
    <header class="fv-sendtpl__head">
        <a class="fv-sendtpl__back" href="<?= company_url('sendtemplate') ?>"><?= htmlspecialchars($t('back'), ENT_QUOTES, 'UTF-8') ?></a>
        <h1><?= htmlspecialchars($t('kind_' . $kind) . ' · ' . $t('channel_' . $channel), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($isEmail ? $t('html_hint') : $t('text_hint'), ENT_QUOTES, 'UTF-8') ?></p>
    </header>
    <?php if ($notice !== ''): ?>
        <p class="fv-sendtpl__notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="fv-sendtpl__workspace">
        <section class="fv-sendtpl__editor">
            <form method="post" action="<?= company_url('sendtemplate/save') ?>" class="fv-sendtpl__form" id="fv-sendtpl-form">
                <input type="hidden" name="kind" value="<?= htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="channel" value="<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isEmail): ?>
                    <label class="fv-sendtpl__label" for="fv-sendtpl-subject"><?= htmlspecialchars($t('subject'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="fv-sendtpl__input" id="fv-sendtpl-subject" name="subject" value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                <?php endif; ?>
                <label class="fv-sendtpl__label" for="fv-sendtpl-body"><?= htmlspecialchars($t('body'), ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($isEmail): ?>
                    <p class="fv-sendtpl__designer-hint"><?= htmlspecialchars($t('designer_hint'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <textarea class="fv-sendtpl__body<?= $isEmail ? ' is-html is-designer' : '' ?>" id="fv-sendtpl-body" name="body" rows="<?= $isEmail ? 24 : 14 ?>"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></textarea>
                <p class="fv-sendtpl__placeholders"><?= htmlspecialchars($t('placeholders'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="fv-sendtpl__actions">
                    <button type="submit" class="fv-sendtpl__save"><?= htmlspecialchars($t('save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="fv-sendtpl__reset" href="<?= company_url('sendtemplate/reset/' . $kind . '/' . $channel) ?>"><?= htmlspecialchars($t('reset'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        </section>

        <aside class="fv-sendtpl__live" aria-live="polite">
            <div class="fv-sendtpl__live-head">
                <h2 class="fv-sendtpl__preview-title"><?= htmlspecialchars($t('preview'), ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="fv-sendtpl__live-badge" id="fv-sendtpl-live-badge"><?= htmlspecialchars($t('preview_live'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if ($isEmail): ?>
                <p class="fv-sendtpl__preview-subject" id="fv-sendtpl-preview-subject"><?= htmlspecialchars($previewSubject, ENT_QUOTES, 'UTF-8') ?></p>
                <iframe class="fv-sendtpl__preview" id="fv-sendtpl-preview" title="<?= htmlspecialchars($t('preview'), ENT_QUOTES, 'UTF-8') ?>"></iframe>
            <?php else: ?>
                <div class="fv-sendtpl__bubble fv-sendtpl__bubble--<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?> fv-sendtpl__bubble--edit" id="fv-sendtpl-preview-text">
                    <?= nl2br(htmlspecialchars($previewBody, ENT_QUOTES, 'UTF-8')) ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php if ($isEmail): ?>
<script src="<?= base_url('companyasset/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('companyasset/vendors/summernote/dist/summernote-bs4.min.js') ?>"></script>
<?php endif; ?>
<script>
(function () {
    var samples = <?= json_encode($sampleMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var area = document.getElementById('fv-sendtpl-body');
    var subjectInput = document.getElementById('fv-sendtpl-subject');
    var badge = document.getElementById('fv-sendtpl-live-badge');
    var form = document.getElementById('fv-sendtpl-form');
    var liveLabel = <?= json_encode($t('preview_live'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var updatingLabel = <?= json_encode($t('preview_updating'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var isEmail = <?= $isEmail ? 'true' : 'false' ?>;
    if (!area) return;

    var timer = null;
    var emailShell = { prefix: '', suffix: '', mode: 'raw' };

    function fill(text) {
        var out = String(text || '');
        Object.keys(samples || {}).forEach(function (key) {
            out = out.split(key).join(samples[key]);
        });
        return out;
    }

    function splitEmailDocument(full) {
        var html = String(full || '');
        var marker = /(<td[^>]*padding:\s*24px[^>]*>)([\s\S]*?)(<\/td>)/i;
        var match = html.match(marker);
        if (match) {
            return {
                mode: 'shell',
                prefix: html.slice(0, match.index) + match[1],
                inner: match[2],
                suffix: match[3] + html.slice(match.index + match[0].length)
            };
        }
        var bodyMatch = html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
        if (bodyMatch) {
            var open = html.match(/<body[^>]*>/i);
            var openTag = open ? open[0] : '<body>';
            var start = html.indexOf(openTag) + openTag.length;
            var end = html.toLowerCase().lastIndexOf('</body>');
            return {
                mode: 'shell',
                prefix: html.slice(0, start),
                inner: html.slice(start, end > -1 ? end : html.length),
                suffix: end > -1 ? html.slice(end) : ''
            };
        }
        return { mode: 'raw', prefix: '', inner: html, suffix: '' };
    }

    function buildEmailDocument(innerHtml) {
        if (emailShell.mode === 'shell') {
            return emailShell.prefix + innerHtml + emailShell.suffix;
        }
        var title = subjectInput ? subjectInput.value : '';
        return '<!DOCTYPE html>\n<html lang="und">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n<title>' +
            String(title).replace(/</g, '&lt;').replace(/>/g, '&gt;') +
            '</title>\n</head>\n<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">\n' +
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;"><tr><td align="center">' +
            '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">' +
            '<tr><td style="background:#dc2626;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:700;">{{company_name}}</td></tr>' +
            '<tr><td style="padding:24px;font-size:15px;line-height:1.55;">' + innerHtml + '</td></tr>' +
            '<tr><td style="padding:16px 24px;background:#f8fafc;color:#6b7280;font-size:12px;">{{company_name}}</td></tr>' +
            '</table></td></tr></table>\n</body>\n</html>';
    }

    function currentBodyValue() {
        if (isEmail && window.jQuery && jQuery(area).next('.note-editor').length) {
            var inner = jQuery(area).summernote('code');
            var full = buildEmailDocument(inner);
            area.value = full;
            return full;
        }
        return area.value;
    }

    function paint() {
        var filled = fill(currentBodyValue());
        var frame = document.getElementById('fv-sendtpl-preview');
        var text = document.getElementById('fv-sendtpl-preview-text');
        var subjectEl = document.getElementById('fv-sendtpl-preview-subject');
        if (frame) {
            frame.srcdoc = filled;
        }
        if (text) {
            text.textContent = filled;
        }
        if (subjectEl && subjectInput) {
            subjectEl.textContent = fill(subjectInput.value);
        }
        if (badge) {
            badge.textContent = liveLabel;
            badge.classList.remove('is-updating');
        }
    }

    function schedulePaint() {
        if (badge) {
            badge.textContent = updatingLabel;
            badge.classList.add('is-updating');
        }
        if (timer) clearTimeout(timer);
        timer = setTimeout(paint, 120);
    }

    function initDesigner() {
        if (!isEmail || !window.jQuery || !jQuery.fn.summernote) {
            area.addEventListener('input', schedulePaint);
            paint();
            return;
        }
        var parts = splitEmailDocument(area.value);
        emailShell = { mode: parts.mode, prefix: parts.prefix || '', suffix: parts.suffix || '' };
        jQuery(area).val(parts.inner);
        jQuery(area).summernote({
            height: 360,
            dialogsInBody: true,
            followingToolbar: false,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'hr']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onChange: schedulePaint,
                onKeyup: schedulePaint,
                onPaste: function () { setTimeout(schedulePaint, 0); }
            }
        });
        if (form) {
            form.addEventListener('submit', function () {
                currentBodyValue();
            });
        }
        if (subjectInput) {
            subjectInput.addEventListener('input', schedulePaint);
            subjectInput.addEventListener('change', schedulePaint);
        }
        paint();
    }

    function boot() {
        if (isEmail && (!window.jQuery || !jQuery.fn || !jQuery.fn.summernote)) {
            setTimeout(boot, 50);
            return;
        }
        initDesigner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
