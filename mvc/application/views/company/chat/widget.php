<?php
/**
 * Widget de chat empresarial (estilo WhatsApp).
 * Textos: api/lang -> cmp-chat (vía langmodel, no métodos protected del controller).
 */
$CI = get_instance();

$chatLangId = $CI->session->userdata('language_id');
$chatLang = [];
if (isset($CI->langmodel) && is_object($CI->langmodel) && method_exists($CI->langmodel, 'getMdl')) {
    $chatLang = $CI->langmodel->getMdl('cmp-chat', $chatLangId);
}
$chatLang = is_array($chatLang) ? $chatLang : [];

$h = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

// Solo datos de sesión (accesibles desde vistas); no llamar métodos protected del controller.
$chatToken = (string) ($CI->session->userdata('token') ?? '');
$chatCompanyId = (int) ($CI->session->userdata('compId') ?? 0);
$chatUserId = (int) ($CI->session->userdata('user_logueado') ?? $CI->session->userdata('id') ?? 0);
$chatApiBase = rtrim(base_url('api'), '/');

$chatCfg = [
    'apiBaseUrl' => $chatApiBase,
    'apiToken' => $chatToken,
    'companyId' => $chatCompanyId,
    'userId' => $chatUserId,
    'pollMs' => 4000,
    'lang' => $chatLang,
];
?>
<link rel="stylesheet" href="<?= base_url('companyasset/css/fv-company-chat.css?v=' . (defined('APP_VERSION') ? APP_VERSION : '1')) ?>">
<div id="fvCompanyChat" aria-live="polite">
    <button type="button" id="fvChatBtn" title="<?= $h($chatLang['open_label'] ?? '') ?>" aria-label="<?= $h($chatLang['open_label'] ?? '') ?>">
        <i class="mdi mdi-message-text"></i>
        <span id="fvChatBadge" class="fv-chat-badge" hidden>0</span>
    </button>

    <div id="fvChatBackdrop" class="fv-chat-backdrop" hidden></div>

    <aside id="fvChatPanel" class="fv-chat-panel" hidden aria-label="<?= $h($chatLang['widget_title'] ?? 'Chat') ?>">
        <div class="fv-chat-header">
            <div class="fv-chat-header-main">
                <button type="button" id="fvChatBack" class="fv-chat-iconbtn" hidden title="<?= $h($chatLang['back'] ?? '') ?>">
                    <i class="mdi mdi-arrow-left"></i>
                </button>
                <div class="fv-chat-header-titles">
                    <div id="fvChatTitle" class="fv-chat-title"><?= $h($chatLang['widget_title'] ?? 'Chat') ?></div>
                    <div id="fvChatSubtitle" class="fv-chat-subtitle"><?= $h($chatLang['online_team'] ?? '') ?></div>
                </div>
            </div>
            <div class="fv-chat-header-actions">
                <button type="button" id="fvChatNew" class="fv-chat-iconbtn" title="<?= $h($chatLang['new_chat'] ?? '') ?>">
                    <i class="mdi mdi-message-plus"></i>
                </button>
                <button type="button" id="fvChatClose" class="fv-chat-iconbtn" title="<?= $h($chatLang['close_label'] ?? '') ?>">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
        </div>

        <div id="fvChatSearchWrap" class="fv-chat-search">
            <input type="search" id="fvChatSearch" placeholder="<?= $h($chatLang['search_placeholder'] ?? '') ?>" autocomplete="off">
        </div>

        <div id="fvChatListView" class="fv-chat-body">
            <div class="fv-chat-section-label" id="fvChatChatsLabel"><?= $h($chatLang['chats'] ?? '') ?></div>
            <div id="fvChatConversations" class="fv-chat-list"></div>
            <div class="fv-chat-section-label" id="fvChatContactsLabel"><?= $h($chatLang['contacts'] ?? '') ?></div>
            <div id="fvChatContacts" class="fv-chat-list"></div>
        </div>

        <div id="fvChatThreadView" class="fv-chat-thread" hidden>
            <div id="fvChatMessages" class="fv-chat-messages"></div>
            <div id="fvChatComposer" class="fv-chat-composer">
                <button type="button" id="fvChatPhoto" class="fv-chat-iconbtn" title="<?= $h($chatLang['attach_photo'] ?? '') ?>">
                    <i class="mdi mdi-image"></i>
                </button>
                <input type="file" id="fvChatPhotoInput" accept="image/*" hidden>
                <button type="button" id="fvChatMic" class="fv-chat-iconbtn" title="<?= $h($chatLang['record_audio'] ?? '') ?>">
                    <i class="mdi mdi-microphone"></i>
                </button>
                <textarea id="fvChatInput" rows="1" placeholder="<?= $h($chatLang['input_placeholder'] ?? '') ?>"></textarea>
                <button type="button" id="fvChatSend" class="fv-chat-send" title="<?= $h($chatLang['send'] ?? '') ?>">
                    <i class="mdi mdi-send"></i>
                </button>
            </div>
            <div id="fvChatRecording" class="fv-chat-recording" hidden>
                <span class="fv-chat-rec-dot"></span>
                <span id="fvChatRecordingLabel"><?= $h($chatLang['recording'] ?? '') ?></span>
                <button type="button" id="fvChatStopRec" class="fv-chat-stop"><?= $h($chatLang['stop_recording'] ?? '') ?></button>
            </div>
        </div>
    </aside>
</div>
<script>
window.FV_COMPANY_CHAT = <?= json_encode($chatCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="<?= base_url('companyasset/company/fv-company-chat.js?v=' . (defined('APP_VERSION') ? APP_VERSION : '1')) ?>"></script>
