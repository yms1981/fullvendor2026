/**
 * Asistente Fullvendor.
 *
 * - Dictado y voz de salida con la Web Speech API del navegador.
 * - Motor de intenciones local: base de conocimiento (window.FV_ASSISTANT.lang.kb).
 * - Fallback ChatGPT: POST /api/v2/assistant/chat (Bearer Sanctum; clave solo en servidor).
 * - Conoce la pantalla actual (window.FV_ASSISTANT.ctx) e inspecciona el formulario.
 *
 * Todos los textos visibles provienen de window.FV_ASSISTANT.lang (api/lang).
 */
(function () {
    'use strict';

    if (!window.FV_ASSISTANT || document.getElementById('fvAssistant') === null) {
        return;
    }

    var L = window.FV_ASSISTANT.lang || {};
    var CTX = window.FV_ASSISTANT.ctx || {};
    var API = window.FV_ASSISTANT.api || {};
    var KB = Array.isArray(L.kb) ? L.kb : [];
    var chatHistory = [];

    // ---- Helpers de texto -------------------------------------------------
    function t(key, fallback) {
        return (L && typeof L[key] === 'string' && L[key] !== '') ? L[key] : (fallback || '');
    }

    // Normaliza: minúsculas, sin acentos, sin signos, espacios colapsados.
    function norm(s) {
        return (s || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9ñ\s]/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function fmt(str, map) {
        var out = str || '';
        Object.keys(map || {}).forEach(function (k) {
            out = out.replace(new RegExp('%' + k + '%', 'g'), map[k]);
        });
        return out;
    }

    // ---- Referencias DOM --------------------------------------------------
    var btn = document.getElementById('fvAssistantBtn');
    var panel = document.getElementById('fvAssistantPanel');
    var body = document.getElementById('fvAsstBody');
    var input = document.getElementById('fvAsstInput');
    var micBtn = document.getElementById('fvAsstMic');
    var sendBtn = document.getElementById('fvAsstSend');
    var closeBtn = document.getElementById('fvAsstClose');
    var clearBtn = document.getElementById('fvAsstClear');
    var speakBtn = document.getElementById('fvAsstSpeak');
    var statusEl = document.getElementById('fvAsstStatus');

    var ttsEnabled = false;
    var greeted = false;

    // ---- Render de mensajes ----------------------------------------------
    function addMessage(text, who) {
        var wrap = document.createElement('div');
        wrap.className = 'fv-asst-msg ' + (who === 'user' ? 'fv-user' : 'fv-bot');
        var bubble = document.createElement('div');
        bubble.className = 'fv-asst-bubble';
        bubble.textContent = text;
        wrap.appendChild(bubble);
        body.appendChild(wrap);
        body.scrollTop = body.scrollHeight;
    }

    function addSuggestions() {
        var list = Array.isArray(L.suggestions) ? L.suggestions : [];
        if (!list.length) { return; }
        var box = document.createElement('div');
        box.className = 'fv-asst-suggestions';
        list.forEach(function (s) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'fv-asst-chip';
            chip.textContent = s;
            chip.addEventListener('click', function () { handleQuery(s); });
            box.appendChild(chip);
        });
        body.appendChild(box);
        body.scrollTop = body.scrollHeight;
    }

    function botSay(text) {
        addMessage(text, 'bot');
        if (ttsEnabled) { speak(text); }
    }

    // ---- Texto a voz (TTS) ------------------------------------------------
    function speak(text) {
        if (!('speechSynthesis' in window)) { return; }
        try {
            window.speechSynthesis.cancel();
            var u = new SpeechSynthesisUtterance(text);
            u.lang = (CTX.lang === 'es') ? 'es-ES' : 'en-US';
            window.speechSynthesis.speak(u);
        } catch (e) { /* noop */ }
    }

    // ---- Inspección del formulario actual --------------------------------
    function getActiveForm() {
        var scope = document.getElementById('main-content') || document;
        var forms = scope.querySelectorAll('form');
        var best = null, bestScore = -1;
        forms.forEach(function (f) {
            if (f.offsetParent === null) { return; } // oculto
            var fields = f.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea');
            if (fields.length > bestScore) { bestScore = fields.length; best = f; }
        });
        return best;
    }

    function labelFor(el, form) {
        // 1) <label for="id">
        if (el.id) {
            var lab = form.querySelector('label[for="' + CSS.escape(el.id) + '"]');
            if (lab && lab.textContent.trim()) { return lab.textContent.trim().replace(/\s*\*$/, ''); }
        }
        // 2) label contenedor
        var p = el.closest('label');
        if (p && p.textContent.trim()) { return p.textContent.trim().replace(/\s*\*$/, ''); }
        // 3) placeholder / aria-label / name
        return (el.getAttribute('aria-label') || el.getAttribute('placeholder') || el.getAttribute('name') || '').trim();
    }

    function collectFields(form) {
        if (!form) { return []; }
        var els = form.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button]):not([type=reset]), select, textarea');
        var out = [];
        els.forEach(function (el) {
            if (el.offsetParent === null && el.type !== 'hidden') {
                // visible check laxo: incluir igualmente si tiene name
            }
            var label = labelFor(el, form);
            if (!label) { return; }
            var required = el.required || el.getAttribute('aria-required') === 'true' ||
                (el.closest('.form-group') && el.closest('.form-group').querySelector('.text-danger'));
            out.push({ el: el, label: label, name: el.getAttribute('name') || '', required: !!required });
        });
        return out;
    }

    function listFieldsAnswer() {
        var form = getActiveForm();
        var fields = collectFields(form);
        if (!fields.length) { return t('form_no_fields', 'No form here.'); }
        var names = fields.map(function (f) { return '• ' + f.label + (f.required ? ' *' : ''); });
        return t('form_fields_intro', 'Fields:') + '\n' + names.join('\n');
    }

    function requiredFieldsAnswer() {
        var form = getActiveForm();
        var fields = collectFields(form).filter(function (f) { return f.required; });
        if (!getActiveForm()) { return t('form_no_fields', 'No form here.'); }
        if (!fields.length) { return t('form_no_required', 'No required fields.'); }
        var names = fields.map(function (f) { return '• ' + f.label; });
        return t('form_required_intro', 'Required:') + '\n' + names.join('\n');
    }

    function submitForm() {
        var form = getActiveForm();
        if (!form) { return t('form_submit_none', 'No form to submit.'); }
        var btnSubmit = form.querySelector('[type=submit], button:not([type=button]):not([type=reset])');
        if (!btnSubmit) {
            // intentar submit nativo
            try { form.requestSubmit ? form.requestSubmit() : form.submit(); return t('form_submit_done', 'Submitting…'); }
            catch (e) { return t('form_submit_none', 'No form to submit.'); }
        }
        btnSubmit.click();
        return t('form_submit_done', 'Submitting…');
    }

    function fillField(fieldQuery, value) {
        var form = getActiveForm();
        if (!form) { return t('form_no_fields', 'No form here.'); }
        if (value == null || value === '') { return fmt(t('form_value_missing', 'Tell me the value for %field%.'), { field: fieldQuery }); }

        var fields = collectFields(form);
        var nq = norm(fieldQuery);
        var match = null, bestScore = 0;
        fields.forEach(function (f) {
            var nl = norm(f.label);
            var nn = norm(f.name);
            var score = 0;
            if (nl === nq || nn === nq) { score = 100; }
            else if (nl.indexOf(nq) !== -1 || nq.indexOf(nl) !== -1) { score = 60; }
            else if (nn && (nn.indexOf(nq) !== -1 || nq.indexOf(nn) !== -1)) { score = 50; }
            if (score > bestScore) { bestScore = score; match = f; }
        });

        if (!match) { return fmt(t('form_field_not_found', 'Field %field% not found.'), { field: fieldQuery }); }

        var el = match.el;
        var tag = el.tagName.toLowerCase();
        if (tag === 'select') {
            var found = false;
            Array.prototype.forEach.call(el.options, function (opt) {
                if (!found && (norm(opt.textContent) === norm(value) || norm(opt.value) === norm(value))) {
                    el.value = opt.value; found = true;
                }
            });
            if (!found) { return fmt(t('form_field_not_found', 'Field %field% not found.'), { field: fieldQuery }); }
        } else if (el.type === 'checkbox') {
            var truthy = ['si', 'sí', 'yes', 'true', '1', 'on'].indexOf(norm(value)) !== -1;
            el.checked = truthy;
        } else {
            el.value = value;
        }
        // Disparar eventos para que validaciones/inputmask/select2 reaccionen.
        ['input', 'change', 'blur'].forEach(function (evt) {
            el.dispatchEvent(new Event(evt, { bubbles: true }));
        });
        if (window.jQuery) { try { window.jQuery(el).trigger('change'); } catch (e) {} }
        try { el.focus(); } catch (e) {}

        return fmt(t('form_filled', 'Set %value% in %field%.'), { value: value, field: match.label });
    }

    // ---- Detección de acciones por lenguaje natural -----------------------
    function detectFill(raw) {
        // patrones: "rellenar X con Y", "pon X en Y"/"poner Y en X", "fill X with Y", "set X to Y"
        var n = ' ' + norm(raw) + ' ';
        var m;
        // rellenar/llenar/escribe/pon  <campo> con <valor>
        m = n.match(/\s(?:rellena|rellenar|llena|llenar|escribe|escribir|pon|poner|coloca|colocar)\s+(.+?)\s+con\s+(.+?)\s*$/);
        if (m) { return { field: m[1], value: m[2] }; }
        // fill/set <campo> with/to <valor>
        m = n.match(/\s(?:fill|set|type|enter|write)\s+(.+?)\s+(?:with|to|as|=)\s+(.+?)\s*$/);
        if (m) { return { field: m[1], value: m[2] }; }
        return null;
    }

    // ---- Motor de intenciones (matching local) ----------------------------
    function matchKb(raw) {
        var n = norm(raw);
        if (!n) { return null; }
        var screen = norm(CTX.screen || '');
        var best = null, bestScore = 0;

        KB.forEach(function (item) {
            var kws = Array.isArray(item.keywords) ? item.keywords : [];
            var score = 0;
            kws.forEach(function (kw) {
                var nk = norm(kw);
                if (!nk) { return; }
                if (n === nk) { score += 12; }
                else if (n.indexOf(nk) !== -1) { score += nk.indexOf(' ') !== -1 ? 6 : 3; }
            });
            // bonus si el intent está asociado a la pantalla actual
            if (score > 0 && Array.isArray(item.screens)) {
                var onScreen = item.screens.some(function (s) { return screen.indexOf(norm(s)) !== -1; });
                if (onScreen) { score += 4; }
            }
            if (score > bestScore) { bestScore = score; best = item; }
        });

        return bestScore > 0 ? best : null;
    }

    function runAction(answer) {
        switch (answer) {
            case '__ACTION_LIST__':     return listFieldsAnswer();
            case '__ACTION_REQUIRED__': return requiredFieldsAnswer();
            case '__ACTION_SUBMIT__':   return submitForm();
            default:                    return answer;
        }
    }

    function screenHelp() {
        // ayuda contextual: intent de pantalla con mayor relevancia, o genérica.
        var screen = norm(CTX.screen || '');
        var hit = null, bestScore = 0;
        KB.forEach(function (item) {
            if (!Array.isArray(item.screens)) { return; }
            var onScreen = item.screens.some(function (s) { return screen.indexOf(norm(s)) !== -1; });
            if (onScreen) {
                var sc = item.screens.reduce(function (acc, s) {
                    return Math.max(acc, norm(s).length);
                }, 0);
                if (sc > bestScore && item.answer.indexOf('__ACTION') !== 0) { bestScore = sc; hit = item; }
            }
        });
        if (hit) { return hit.answer; }
        var label = (CTX.controller || '') + (CTX.method && CTX.method !== 'index' ? '/' + CTX.method : '');
        return fmt(t('screen_help_generic', 'You are on %screen%.'), { screen: label || (CTX.screen || '') });
    }

    function isScreenHelpQuestion(raw) {
        var n = norm(raw);
        var triggers = [
            'que puedo hacer aqui', 'que puedo hacer en esta pantalla', 'que es esta pantalla',
            'donde estoy', 'ayuda con esta pantalla', 'para que sirve esta pantalla',
            'what can i do here', 'what can i do on this screen', 'what is this screen',
            'where am i', 'help with this screen'
        ];
        return triggers.some(function (tr) { return n.indexOf(norm(tr)) !== -1; });
    }

    function rememberTurn(role, content) {
        chatHistory.push({ role: role, content: content });
        if (chatHistory.length > 16) {
            chatHistory = chatHistory.slice(chatHistory.length - 16);
        }
    }

    function askChatGpt(text) {
        var base = String(API.apiBaseUrl || '').replace(/\/$/, '');
        var token = String(API.apiToken || '');
        var companyId = Number(API.companyId || 0);
        if (!base || !token) {
            botSay(t('not_understood', "I didn't understand."));
            return;
        }

        statusEl.textContent = t('thinking', 'Thinking…');
        sendBtn.disabled = true;
        input.disabled = true;

        var url = base + '/v2/assistant/chat?company_id=' + encodeURIComponent(companyId);
        fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Company-Id': String(companyId)
            },
            body: JSON.stringify({
                message: text,
                history: chatHistory.slice(0, -1),
                context: {
                    lang: CTX.lang || 'en',
                    screen: CTX.screen || '',
                    controller: CTX.controller || '',
                    method: CTX.method || ''
                }
            })
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                return { ok: res.ok, status: res.status, body: body || {} };
            });
        }).then(function (result) {
            var reply = result.body && typeof result.body.reply === 'string' ? result.body.reply.trim() : '';
            if (result.ok && reply) {
                rememberTurn('assistant', reply);
                botSay(reply);
                return;
            }
            if (result.status === 503 || (result.body && result.body.enabled === false)) {
                botSay(t('ai_unavailable', t('not_understood', "I didn't understand.")));
                return;
            }
            if (result.body && result.body.code === 'openai_quota_exceeded') {
                botSay(t('ai_quota', t('ai_error', t('not_understood', "I didn't understand."))));
                return;
            }
            botSay(t('ai_error', t('not_understood', "I didn't understand.")));
        }).catch(function () {
            botSay(t('ai_error', t('not_understood', "I didn't understand.")));
        }).finally(function () {
            statusEl.textContent = '';
            sendBtn.disabled = false;
            input.disabled = false;
            try { input.focus(); } catch (e) {}
        });
    }

    // ---- Punto de entrada de cada consulta --------------------------------
    function handleQuery(raw) {
        var text = (raw || '').trim();
        if (!text) { return; }
        addMessage(text, 'user');
        input.value = '';

        // 1) Acción de rellenar campo
        var fill = detectFill(text);
        if (fill) { botSay(fillField(fill.field.trim(), fill.value.trim())); return; }

        // 2) Ayuda contextual de pantalla
        if (isScreenHelpQuestion(text)) { botSay(screenHelp()); return; }

        // 3) Base de conocimiento / acciones de formulario
        var item = matchKb(text);
        if (item) { botSay(runAction(item.answer)); return; }

        // 4) Fallback ChatGPT (OpenAI en API Laravel)
        rememberTurn('user', text);
        askChatGpt(text);
    }

    // ---- Reconocimiento de voz (dictado) ----------------------------------
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    var recognition = null;
    var listening = false;

    function setListening(on) {
        listening = on;
        micBtn.classList.toggle('fv-listening', on);
        btn.classList.toggle('fv-listening', on);
        statusEl.textContent = on ? t('listening', 'Listening…') : '';
    }

    function initRecognition() {
        if (!SR) { return null; }
        var r = new SR();
        r.lang = (CTX.lang === 'es') ? 'es-ES' : 'en-US';
        r.interimResults = false;
        r.maxAlternatives = 1;
        r.continuous = false;
        r.onresult = function (ev) {
            var transcript = '';
            for (var i = 0; i < ev.results.length; i++) { transcript += ev.results[i][0].transcript; }
            transcript = transcript.trim();
            if (transcript) { handleQuery(transcript); }
        };
        r.onerror = function () { setListening(false); };
        r.onend = function () { setListening(false); };
        return r;
    }

    function toggleMic() {
        if (!SR) { botSay(t('voice_unsupported', 'Voice not supported.')); return; }
        if (!recognition) { recognition = initRecognition(); }
        if (!recognition) { botSay(t('voice_unsupported', 'Voice not supported.')); return; }
        if (listening) { try { recognition.stop(); } catch (e) {} setListening(false); return; }
        try {
            recognition.lang = (CTX.lang === 'es') ? 'es-ES' : 'en-US';
            recognition.start();
            setListening(true);
        } catch (e) { setListening(false); }
    }

    // ---- Apertura / cierre del panel --------------------------------------
    function openPanel() {
        panel.classList.add('fv-open');
        if (!greeted) {
            botSay(t('greeting', 'Hi!'));
            if (Array.isArray(L.suggestions) && L.suggestions.length) {
                var title = t('suggestions_title', '');
                if (title) { addMessage(title, 'bot'); }
                addSuggestions();
            }
            greeted = true;
        }
        setTimeout(function () { try { input.focus(); } catch (e) {} }, 50);
    }

    function closePanel() {
        panel.classList.remove('fv-open');
        if (listening && recognition) { try { recognition.stop(); } catch (e) {} setListening(false); }
        if ('speechSynthesis' in window) { try { window.speechSynthesis.cancel(); } catch (e) {} }
    }

    function clearChat() {
        body.innerHTML = '';
        chatHistory = [];
        greeted = false;
        openPanel();
    }

    // ---- Eventos ----------------------------------------------------------
    btn.addEventListener('click', function () {
        if (panel.classList.contains('fv-open')) { closePanel(); } else { openPanel(); }
    });
    closeBtn.addEventListener('click', closePanel);
    clearBtn.addEventListener('click', clearChat);
    sendBtn.addEventListener('click', function () { handleQuery(input.value); });
    micBtn.addEventListener('click', toggleMic);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); handleQuery(input.value); }
    });
    speakBtn.addEventListener('click', function () {
        ttsEnabled = !ttsEnabled;
        speakBtn.classList.toggle('fv-active', ttsEnabled);
        if (!ttsEnabled && 'speechSynthesis' in window) { try { window.speechSynthesis.cancel(); } catch (e) {} }
    });

    // Ocultar el micrófono si el navegador no soporta dictado (pero deja escribir).
    if (!SR && micBtn) { micBtn.style.display = 'none'; }
})();
