<?php
/**
 * Widget del asistente FullVendor.
 * - Voz/dictado: Web Speech API del navegador.
 * - Cerebro local: base de conocimiento api/lang (cmp-asistente) + acciones de formulario.
 * - Fallback ChatGPT: POST /api/v2/assistant/chat (claves solo en api/.env).
 * - Contexto: pantalla actual (controlador/método) e inspección del formulario.
 *
 * Todos los textos visibles provienen de api/lang/{es-ES|en-EN}.json -> sección "cmp-asistente".
 */
$CI = get_instance();

$asistLangId = $CI->session->userdata('language_id');
$asist = $CI->langmodel->getMdl('cmp-asistente', $asistLangId);
$asist = is_array($asist) ? $asist : [];

// Contexto de pantalla: directorio + clase + método (en minúsculas) para la ayuda contextual.
$asistDir = '';
$asistClass = '';
$asistMethod = '';
if (is_object($CI->router)) {
    $asistDir = strtolower((string) ($CI->router->fetch_directory() ?? ''));
    $asistClass = strtolower((string) ($CI->router->fetch_class() ?? ''));
    $asistMethod = strtolower((string) ($CI->router->fetch_method() ?? ''));
}
$asistScreen = trim(str_replace('//', '/', $asistDir . $asistClass . '/' . $asistMethod), '/');

$asistCtx = [
    'lang'       => ((int) $asistLangId === 2) ? 'es' : 'en',
    'screen'     => $asistScreen,
    'directory'  => trim($asistDir, '/'),
    'controller' => $asistClass,
    'method'     => $asistMethod,
];

$asistApi = [
    'apiBaseUrl' => rtrim(base_url('api'), '/'),
    'apiToken'   => (string) ($CI->session->userdata('token') ?? ''),
    'companyId'  => (int) ($CI->session->userdata('compId') ?? 0),
];

// Helper local para escapar texto visible.
$h = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<style>
    #fvAssistant, #fvAssistant * { box-sizing: border-box; }
    #fvAssistantBtn {
        position: fixed; right: 22px; bottom: 22px; z-index: 20000;
        width: 60px; height: 60px; border-radius: 50%; border: none; cursor: pointer;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: #fff; box-shadow: 0 6px 18px rgba(0,0,0,.25);
        display: flex; align-items: center; justify-content: center;
        transition: transform .15s ease, box-shadow .15s ease, bottom .2s ease;
    }
    #fvAssistantBtn:hover { transform: scale(1.06); box-shadow: 0 8px 22px rgba(0,0,0,.30); }
    #fvAssistantBtn i { font-size: 26px; line-height: 1; }
    #fvAssistantBtn.fv-listening {
        animation: fvPulse 1.1s infinite;
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }
    @keyframes fvPulse {
        0%   { box-shadow: 0 0 0 0 rgba(40,167,69,.55); }
        70%  { box-shadow: 0 0 0 16px rgba(40,167,69,0); }
        100% { box-shadow: 0 0 0 0 rgba(40,167,69,0); }
    }
    #fvAssistantPanel {
        position: fixed; right: 22px; bottom: 92px; z-index: 20000;
        width: 360px; max-width: calc(100vw - 32px); height: 480px; max-height: calc(100vh - 130px);
        background: #fff; border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,.28);
        display: none; flex-direction: column; overflow: hidden; border: 1px solid #e9ecef;
    }
    #fvAssistantPanel.fv-open { display: flex; }
    .fv-asst-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #fff;
        padding: 12px 14px; display: flex; align-items: center; justify-content: space-between;
    }
    .fv-asst-header .fv-asst-title { font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 8px; }
    .fv-asst-header .fv-asst-actions { display: flex; align-items: center; gap: 6px; }
    .fv-asst-iconbtn {
        background: rgba(255,255,255,.18); border: none; color: #fff; cursor: pointer;
        width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
    }
    .fv-asst-iconbtn:hover { background: rgba(255,255,255,.32); }
    .fv-asst-iconbtn.fv-active { background: #fff; color: #c82333; }
    .fv-asst-body { flex: 1; overflow-y: auto; padding: 12px; background: #f8f9fa; }
    .fv-asst-msg { margin-bottom: 10px; display: flex; }
    .fv-asst-msg.fv-user { justify-content: flex-end; }
    .fv-asst-bubble {
        max-width: 82%; padding: 9px 12px; border-radius: 12px; font-size: 13.5px; line-height: 1.45;
        white-space: pre-wrap; word-wrap: break-word;
    }
    .fv-asst-msg.fv-bot .fv-asst-bubble  { background: #fff; color: #212529; border: 1px solid #e9ecef; border-bottom-left-radius: 3px; }
    .fv-asst-msg.fv-user .fv-asst-bubble { background: #dc3545; color: #fff; border-bottom-right-radius: 3px; }
    .fv-asst-suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0 10px; }
    .fv-asst-chip {
        background: #fff; border: 1px solid #dc3545; color: #c82333; border-radius: 16px;
        padding: 5px 10px; font-size: 12px; cursor: pointer;
    }
    .fv-asst-chip:hover { background: #dc3545; color: #fff; }
    .fv-asst-footer { padding: 10px; border-top: 1px solid #e9ecef; background: #fff; }
    .fv-asst-inputrow { display: flex; align-items: center; gap: 6px; }
    .fv-asst-inputrow input[type=text] {
        flex: 1; border: 1px solid #ced4da; border-radius: 20px; padding: 8px 12px; font-size: 13.5px; outline: none;
    }
    .fv-asst-inputrow input[type=text]:focus { border-color: #dc3545; box-shadow: 0 0 0 .15rem rgba(220,53,69,.2); }
    .fv-asst-mic, .fv-asst-send {
        border: none; border-radius: 50%; width: 38px; height: 38px; cursor: pointer; color: #fff;
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
    }
    .fv-asst-mic  { background: #6c757d; }
    .fv-asst-mic.fv-listening { background: #28a745; animation: fvPulse 1.1s infinite; }
    .fv-asst-send { background: #dc3545; }
    .fv-asst-status { font-size: 11.5px; color: #6c757d; min-height: 16px; margin-top: 6px; text-align: center; }
</style>

<div id="fvAssistant">
    <button id="fvAssistantBtn" type="button" aria-label="<?= $h($asist['open_label'] ?? 'Open assistant') ?>"
        title="<?= $h($asist['open_label'] ?? 'Open assistant') ?>">
        <i class="mdi mdi-microphone"></i>
    </button>

    <div id="fvAssistantPanel" role="dialog" aria-label="<?= $h($asist['widget_title'] ?? 'Assistant') ?>">
        <div class="fv-asst-header">
            <span class="fv-asst-title"><i class="mdi mdi-robot-happy-outline"></i><?= $h($asist['widget_title'] ?? 'Assistant') ?></span>
            <span class="fv-asst-actions">
                <button type="button" class="fv-asst-iconbtn" id="fvAsstSpeak"
                    title="<?= $h($asist['speak_toggle'] ?? 'Read out loud') ?>"><i class="mdi mdi-volume-high"></i></button>
                <button type="button" class="fv-asst-iconbtn" id="fvAsstClear"
                    title="<?= $h($asist['clear'] ?? 'Clear') ?>"><i class="mdi mdi-broom"></i></button>
                <button type="button" class="fv-asst-iconbtn" id="fvAsstClose"
                    title="<?= $h($asist['close_label'] ?? 'Close') ?>"><i class="mdi mdi-close"></i></button>
            </span>
        </div>
        <div class="fv-asst-body" id="fvAsstBody"></div>
        <div class="fv-asst-footer">
            <div class="fv-asst-inputrow">
                <button type="button" class="fv-asst-mic" id="fvAsstMic"
                    title="<?= $h($asist['mic_start'] ?? 'Speak') ?>"><i class="mdi mdi-microphone"></i></button>
                <input type="text" id="fvAsstInput" autocomplete="off"
                    placeholder="<?= $h($asist['input_placeholder'] ?? 'Type or use the microphone…') ?>">
                <button type="button" class="fv-asst-send" id="fvAsstSend"
                    title="<?= $h($asist['send'] ?? 'Send') ?>"><i class="mdi mdi-send"></i></button>
            </div>
            <div class="fv-asst-status" id="fvAsstStatus"></div>
        </div>
    </div>
</div>

<script>
    window.FV_ASSISTANT = {
        ctx: <?= json_encode($asistCtx, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        lang: <?= json_encode($asist, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        api: <?= json_encode($asistApi, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
    };
</script>
