(function () {
    'use strict';

    var cfg = window.FV_COMPANY_CHAT || {};
    var lang = cfg.lang || {};
    var apiBase = String(cfg.apiBaseUrl || '/api').replace(/\/$/, '');
    var token = cfg.apiToken || '';
    var companyId = Number(cfg.companyId || 0);
    var userId = Number(cfg.userId || 0);
    var pollMs = Number(cfg.pollMs || 4000);

    var state = {
        open: false,
        view: 'list', // list | thread
        conversations: [],
        contacts: [],
        activeId: null,
        messages: [],
        lastMsgId: 0,
        mediaRecorder: null,
        mediaStream: null,
        audioChunks: [],
        recording: false,
        pollTimer: null,
        search: '',
        /** @type {Object.<string, boolean>} true = colapsado */
        collapsedGroups: {}
    };

    function t(key, fallback) {
        return lang[key] != null && lang[key] !== '' ? lang[key] : (fallback || key);
    }

    function $(id) {
        return document.getElementById(id);
    }

    function apiUrl(path) {
        var sep = path.indexOf('?') >= 0 ? '&' : '?';
        return apiBase + '/v2/company-chat/' + path.replace(/^\//, '') + sep + 'company_id=' + encodeURIComponent(companyId);
    }

    function authHeaders(json) {
        var h = {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
            'X-Company-Id': String(companyId)
        };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    function request(path, options) {
        options = options || {};
        var opts = {
            method: options.method || 'GET',
            headers: options.headers || authHeaders(!options.formData),
            body: options.body || undefined
        };
        if (options.formData) {
            delete opts.headers['Content-Type'];
        }
        return fetch(apiUrl(path), opts).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                if (!res.ok) {
                    var err = new Error((body && body.message) || ('HTTP ' + res.status));
                    err.status = res.status;
                    err.body = body;
                    throw err;
                }
                return body;
            });
        });
    }

    function initials(name) {
        name = String(name || '?').trim();
        var parts = name.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
        return name.slice(0, 2).toUpperCase();
    }

    function roleLabel(role) {
        if (role === 'seller') return t('role_seller', 'Seller');
        if (role === 'warehouse') return t('role_warehouse', 'Warehouse');
        if (role === 'manager') return t('role_manager', 'Manager');
        if (role === 'customer') return t('role_customer', 'Customer');
        return role || '';
    }

    /** Orden de grupos en la lista de contactos. */
    var CONTACT_GROUP_ORDER = ['manager', 'customer', 'warehouse', 'seller'];

    function contactGroupLabel(role) {
        if (role === 'manager') return t('group_managers', '');
        if (role === 'customer') return t('group_customers', '');
        if (role === 'warehouse') return t('group_warehouse', '');
        if (role === 'seller') return t('group_sellers', '');
        return roleLabel(role);
    }

    function collapsedGroupsStorageKey() {
        return 'fvChatCollapsedGroups:' + String(companyId || 0) + ':' + String(userId || 0);
    }

    function loadCollapsedGroups() {
        try {
            var raw = window.localStorage.getItem(collapsedGroupsStorageKey());
            if (!raw) {
                return {};
            }
            var parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function saveCollapsedGroups() {
        try {
            window.localStorage.setItem(
                collapsedGroupsStorageKey(),
                JSON.stringify(state.collapsedGroups || {})
            );
        } catch (e) {
            /* ignore quota / private mode */
        }
    }

    function isGroupCollapsed(role) {
        return !!(state.collapsedGroups && state.collapsedGroups[role]);
    }

    function toggleGroupCollapsed(role) {
        if (!state.collapsedGroups) {
            state.collapsedGroups = {};
        }
        state.collapsedGroups[role] = !state.collapsedGroups[role];
        saveCollapsedGroups();
    }

    /**
     * Un contacto con varios roles se muestra en un solo grupo (prioridad manager > seller > warehouse > customer).
     */
    function contactPrimaryRole(contact) {
        var roles = contact && contact.roles ? contact.roles : [];
        var priority = ['manager', 'seller', 'warehouse', 'customer'];
        var i;
        for (i = 0; i < priority.length; i++) {
            if (roles.indexOf(priority[i]) !== -1) {
                return priority[i];
            }
        }
        return roles[0] || 'seller';
    }

    function appendContactButton(container, contact, inChat) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fv-chat-item';
        var roles = (contact.roles || []).map(roleLabel).join(' · ');
        btn.innerHTML =
            avatarHtml(contact, contact.name || contact.email) +
            '<div class="fv-chat-item-main">' +
            '<div class="fv-chat-item-name">' + escapeHtml(contact.name || contact.email) + '</div>' +
            '<div class="fv-chat-item-meta">' + escapeHtml(roles) + (inChat[contact.user_id] ? ' · chat' : '') + '</div>' +
            '</div>';
        btn.addEventListener('click', function () { startChatWith(contact.user_id); });
        container.appendChild(btn);
    }

    function appendContactGroup(contactEl, role, list, inChat) {
        if (!list || !list.length) {
            return 0;
        }
        var collapsed = isGroupCollapsed(role);
        var group = document.createElement('div');
        group.className = 'fv-chat-group' + (collapsed ? ' fv-chat-group--collapsed' : '');
        group.setAttribute('data-role', role);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'fv-chat-group-toggle';
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.setAttribute('aria-controls', 'fvChatGroupBody-' + role);
        toggle.innerHTML =
            '<i class="mdi mdi-chevron-down fv-chat-group-chevron" aria-hidden="true"></i>' +
            '<span class="fv-chat-group-title">' + escapeHtml(contactGroupLabel(role)) + '</span>' +
            '<span class="fv-chat-group-count">' + list.length + '</span>';
        toggle.addEventListener('click', function () {
            toggleGroupCollapsed(role);
            var nowCollapsed = isGroupCollapsed(role);
            group.classList.toggle('fv-chat-group--collapsed', nowCollapsed);
            toggle.setAttribute('aria-expanded', nowCollapsed ? 'false' : 'true');
        });

        var body = document.createElement('div');
        body.className = 'fv-chat-group-body';
        body.id = 'fvChatGroupBody-' + role;
        list.forEach(function (c) {
            appendContactButton(body, c, inChat);
        });

        group.appendChild(toggle);
        group.appendChild(body);
        contactEl.appendChild(group);
        return list.length;
    }

    function formatTime(iso) {
        if (!iso) return '';
        try {
            var d = new Date(iso);
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return '';
        }
    }

    function previewText(msg) {
        if (!msg) return '';
        if (msg.message_type === 'image') return '📷 ' + t('photo', 'Photo');
        if (msg.message_type === 'audio') return '🎤 ' + t('audio', 'Audio');
        return msg.body || '';
    }

    function conversationTitle(c) {
        if (c.subject) return c.subject;
        var others = c.others || [];
        if (others.length === 0) return t('widget_title', 'Chat');
        return others.map(function (o) { return o.name || o.email; }).join(', ');
    }

    function setBadge(n) {
        var badge = $('fvChatBadge');
        if (!badge) return;
        if (n > 0) {
            badge.hidden = false;
            badge.textContent = n > 99 ? '99+' : String(n);
        } else {
            badge.hidden = true;
        }
    }

    function showList() {
        state.view = 'list';
        state.activeId = null;
        $('fvChatListView').hidden = false;
        $('fvChatThreadView').hidden = true;
        $('fvChatBack').hidden = true;
        $('fvChatSearchWrap').hidden = false;
        $('fvChatTitle').textContent = t('widget_title', 'Chat');
        $('fvChatSubtitle').textContent = t('online_team', '');
        var existingAv = document.getElementById('fvChatThreadAvatar');
        if (existingAv) existingAv.remove();
        renderLists();
    }

    function showThread(conversation) {
        state.view = 'thread';
        state.activeId = conversation.id;
        state.messages = [];
        state.lastMsgId = 0;
        $('fvChatListView').hidden = true;
        $('fvChatThreadView').hidden = false;
        $('fvChatBack').hidden = false;
        $('fvChatSearchWrap').hidden = true;
        $('fvChatTitle').textContent = conversationTitle(conversation);
        var roles = (conversation.others || []).map(function (o) { return roleLabel(o.role_type); }).join(' · ');
        $('fvChatSubtitle').textContent = roles || t('online_team', '');
        var headerMain = document.querySelector('#fvChatPanel .fv-chat-header-main');
        var existingAv = document.getElementById('fvChatThreadAvatar');
        if (existingAv) existingAv.remove();
        var other = (conversation.others && conversation.others[0]) || null;
        if (headerMain && other) {
            var wrap = document.createElement('div');
            wrap.id = 'fvChatThreadAvatar';
            wrap.className = 'fv-chat-header-avatar';
            wrap.innerHTML = avatarHtml(other, other.name).replace('fv-chat-avatar', 'fv-chat-avatar fv-chat-avatar--sm');
            headerMain.insertBefore(wrap, headerMain.querySelector('.fv-chat-header-titles'));
            bindAvatarFallbacks(wrap);
        }
        $('fvChatMessages').innerHTML = '<div class="fv-chat-empty">' + escapeHtml(t('no_messages', '')) + '</div>';
        loadMessages(true);
        markRead(conversation.id);
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function attachmentUrl(att) {
        if (!att || !att.id) return '';
        return apiUrl('attachments/' + att.id);
    }

    function avatarHtml(person, fallbackName) {
        var name = (person && (person.name || person.email)) || fallbackName || '?';
        var url = resolveAvatarSrc((person && person.avatar_url) || '');
        var ini = escapeHtml(initials(name));
        if (url) {
            return '<div class="fv-chat-avatar" data-initials="' + ini + '">' +
                '<img src="' + escapeHtml(url) + '" alt="" onerror="this.remove();">' +
                '</div>';
        }
        return '<div class="fv-chat-avatar">' + ini + '</div>';
    }

    function resolveAvatarSrc(url) {
        url = String(url || '').trim();
        if (!url) return '';
        if (/^https?:\/\//i.test(url) || url.indexOf('data:') === 0) {
            return url;
        }
        // /api/v2/images/{uuid} relativo al mismo host del panel
        if (url.charAt(0) === '/') {
            return url;
        }
        return apiBase.replace(/\/api\/?$/, '') + '/' + url.replace(/^\//, '');
    }

    function ticksHtml(status) {
        if (status === 'read') {
            return '<span class="fv-chat-ticks fv-chat-ticks--read" title="' + escapeHtml(t('status_read', 'Leído')) + '">' +
                '<i class="mdi mdi-check-all"></i></span>';
        }
        if (status === 'sent') {
            return '<span class="fv-chat-ticks fv-chat-ticks--sent" title="' + escapeHtml(t('status_sent', 'Enviado')) + '">' +
                '<i class="mdi mdi-check"></i></span>';
        }
        return '';
    }

    function renderMessage(m) {
        var mine = Number(m.user_id) === userId;
        var row = document.createElement('div');
        row.className = 'fv-chat-bubble-row ' + (mine ? 'mine' : 'theirs');
        row.dataset.msgId = String(m.id);

        if (!mine) {
            var av = document.createElement('div');
            av.className = 'fv-chat-msg-avatar';
            var author = m.author || {};
            if (author.avatar_url) {
                av.innerHTML = '<img src="' + escapeHtml(resolveAvatarSrc(author.avatar_url)) + '" alt="">';
                av.querySelector('img').onerror = function () {
                    av.textContent = initials(author.name || '?');
                };
            } else {
                av.textContent = initials(author.name || '?');
            }
            row.appendChild(av);
        }

        var bubble = document.createElement('div');
        bubble.className = 'fv-chat-bubble ' + (mine ? 'mine' : 'theirs');

        if (!mine && m.author && m.author.name) {
            var authorEl = document.createElement('div');
            authorEl.className = 'fv-chat-bubble-author';
            authorEl.textContent = m.author.name;
            bubble.appendChild(authorEl);
        }

        if (m.message_type === 'image' && m.attachments && m.attachments[0]) {
            var img = document.createElement('img');
            img.className = 'fv-chat-media-img';
            img.alt = t('photo', 'Photo');
            img.src = attachmentUrl(m.attachments[0]);
            fetchAttachmentBlob(m.attachments[0].id).then(function (url) {
                if (url) img.src = url;
            });
            bubble.appendChild(img);
        } else if (m.message_type === 'audio' && m.attachments && m.attachments[0]) {
            var audio = document.createElement('audio');
            audio.className = 'fv-chat-media-audio';
            audio.controls = true;
            fetchAttachmentBlob(m.attachments[0].id).then(function (url) {
                if (url) audio.src = url;
            });
            bubble.appendChild(audio);
        }

        if (m.body) {
            var text = document.createElement('div');
            text.textContent = m.body;
            bubble.appendChild(text);
        }

        var time = document.createElement('div');
        time.className = 'fv-chat-bubble-time';
        time.innerHTML = escapeHtml(formatTime(m.created_at)) + (mine ? ticksHtml(m.read_status || 'sent') : '');
        bubble.appendChild(time);

        row.appendChild(bubble);
        return row;
    }

    var blobCache = {};
    function fetchAttachmentBlob(id) {
        if (blobCache[id]) return Promise.resolve(blobCache[id]);
        return fetch(apiUrl('attachments/' + id), {
            headers: authHeaders(false)
        }).then(function (res) {
            if (!res.ok) return null;
            return res.blob();
        }).then(function (blob) {
            if (!blob) return null;
            var url = URL.createObjectURL(blob);
            blobCache[id] = url;
            return url;
        }).catch(function () { return null; });
    }

    function renderMessages(appendOnly) {
        var box = $('fvChatMessages');
        if (!box) return;
        if (!appendOnly) box.innerHTML = '';
        if (!state.messages.length) {
            box.innerHTML = '<div class="fv-chat-empty">' + escapeHtml(t('no_messages', '')) + '</div>';
            return;
        }
        if (!appendOnly) {
            state.messages.forEach(function (m) {
                box.appendChild(renderMessage(m));
            });
        } else {
            var empty = box.querySelector('.fv-chat-empty');
            if (empty) empty.remove();
            state.messages.forEach(function (m) {
                var existing = box.querySelector('[data-msg-id="' + m.id + '"]');
                if (!existing) {
                    box.appendChild(renderMessage(m));
                } else if (Number(m.user_id) === userId) {
                    // Actualizar ticks si pasó a leído
                    var timeEl = existing.querySelector('.fv-chat-bubble-time');
                    if (timeEl) {
                        timeEl.innerHTML = escapeHtml(formatTime(m.created_at)) + ticksHtml(m.read_status || 'sent');
                    }
                }
            });
        }
        box.scrollTop = box.scrollHeight;
    }

    function matchesSearch(text) {
        if (!state.search) return true;
        return String(text || '').toLowerCase().indexOf(state.search) >= 0;
    }

    function renderLists() {
        var convEl = $('fvChatConversations');
        var contactEl = $('fvChatContacts');
        if (!convEl || !contactEl) return;

        var convs = state.conversations.filter(function (c) {
            return matchesSearch(conversationTitle(c) + ' ' + previewText(c.last_message));
        });
        convEl.innerHTML = '';
        if (!convs.length) {
            convEl.innerHTML = '<div class="fv-chat-empty">' + escapeHtml(t('no_conversations', '')) + '</div>';
        } else {
            convs.forEach(function (c) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'fv-chat-item' + (c.unread_count > 0 ? ' fv-chat-item--unread' : '');
                var other = (c.others && c.others[0]) || {};
                btn.innerHTML =
                    avatarHtml(other, conversationTitle(c)) +
                    '<div class="fv-chat-item-main">' +
                    '<div class="fv-chat-item-name">' + escapeHtml(conversationTitle(c)) + '</div>' +
                    '<div class="fv-chat-item-meta">' + escapeHtml(previewText(c.last_message)) + '</div>' +
                    '</div>' +
                    '<div class="fv-chat-item-right">' +
                    (c.unread_count > 0
                        ? '<span class="fv-chat-unread" title="' + escapeHtml(t('status_unread', 'Sin leer')) + '">' + c.unread_count + '</span>'
                        : '<span class="fv-chat-read-dot" title="' + escapeHtml(t('status_read', 'Leído')) + '"></span>') +
                    '</div>';
                btn.addEventListener('click', function () { showThread(c); });
                convEl.appendChild(btn);
            });
            bindAvatarFallbacks(convEl);
        }

        var inChat = {};
        state.conversations.forEach(function (c) {
            (c.others || []).forEach(function (o) { inChat[o.user_id] = true; });
        });

        var contacts = state.contacts.filter(function (c) {
            return matchesSearch((c.name || '') + ' ' + (c.email || '') + ' ' + (c.roles || []).join(' '));
        });
        contactEl.innerHTML = '';
        if (!contacts.length) {
            contactEl.innerHTML = '<div class="fv-chat-empty">' + escapeHtml(t('no_contacts', '')) + '</div>';
        } else {
            var grouped = {};
            CONTACT_GROUP_ORDER.forEach(function (role) {
                grouped[role] = [];
            });
            contacts.forEach(function (c) {
                var role = contactPrimaryRole(c);
                if (!grouped[role]) {
                    grouped[role] = [];
                }
                grouped[role].push(c);
            });

            var rendered = 0;
            var rolesToRender = CONTACT_GROUP_ORDER.slice();
            Object.keys(grouped).forEach(function (role) {
                if (rolesToRender.indexOf(role) === -1) {
                    rolesToRender.push(role);
                }
            });
            rolesToRender.forEach(function (role) {
                rendered += appendContactGroup(contactEl, role, grouped[role] || [], inChat);
            });

            if (!rendered) {
                contactEl.innerHTML = '<div class="fv-chat-empty">' + escapeHtml(t('no_contacts', '')) + '</div>';
            }
            bindAvatarFallbacks(contactEl);
        }
    }

    function bindAvatarFallbacks(root) {
        if (!root) return;
        root.querySelectorAll('.fv-chat-avatar img').forEach(function (img) {
            img.addEventListener('error', function () {
                var parent = img.parentNode;
                if (!parent) return;
                parent.textContent = parent.getAttribute('data-initials') || '?';
            });
        });
    }

    function loadBootstrap() {
        if (!token || !companyId) return Promise.resolve();
        return Promise.all([
            request('conversations'),
            request('contacts'),
            request('unread-count')
        ]).then(function (results) {
            state.conversations = (results[0] && results[0].data) || [];
            state.contacts = (results[1] && results[1].data) || [];
            var unread = (results[2] && results[2].data) || {};
            setBadge(Number(unread.unread_messages || 0));
            if (state.view === 'list') renderLists();
        }).catch(function () {
            /* silencioso en poll */
        });
    }

    function loadMessages(full) {
        if (!state.activeId) return;
        var path = 'conversations/' + state.activeId + '/messages';
        if (!full && state.lastMsgId > 0) {
            path += '?after_id=' + state.lastMsgId;
        }
        request(path).then(function (res) {
            var rows = (res && res.data) || [];
            if (full) {
                state.messages = rows;
                if (rows.length) state.lastMsgId = rows[rows.length - 1].id;
                renderMessages(false);
            } else if (rows.length) {
                rows.forEach(function (m) {
                    var idx = state.messages.findIndex(function (x) { return x.id === m.id; });
                    if (idx >= 0) state.messages[idx] = m;
                    else state.messages.push(m);
                    if (m.id > state.lastMsgId) state.lastMsgId = m.id;
                });
                renderMessages(true);
                markRead(state.activeId);
            } else if (state.messages.length) {
                // Refrescar ticks de lectura cada ~8s aunque no haya mensajes nuevos
                if (!state._tickRefreshAt || (Date.now() - state._tickRefreshAt) > 8000) {
                    state._tickRefreshAt = Date.now();
                    request('conversations/' + state.activeId + '/messages').then(function (fullRes) {
                        var all = (fullRes && fullRes.data) || [];
                        if (!all.length) return;
                        state.messages = all;
                        state.lastMsgId = all[all.length - 1].id;
                        var box = $('fvChatMessages');
                        if (!box) return;
                        all.forEach(function (m) {
                            if (Number(m.user_id) !== userId) return;
                            var existing = box.querySelector('[data-msg-id="' + m.id + '"]');
                            if (!existing) return;
                            var timeEl = existing.querySelector('.fv-chat-bubble-time');
                            if (timeEl) {
                                timeEl.innerHTML = escapeHtml(formatTime(m.created_at)) + ticksHtml(m.read_status || 'sent');
                            }
                        });
                    }).catch(function () {});
                }
            }
        }).catch(function () {});
    }

    function markRead(conversationId) {
        request('conversations/' + conversationId + '/read', {
            method: 'POST',
            body: JSON.stringify({})
        }).then(function () {
            return request('unread-count');
        }).then(function (res) {
            var unread = (res && res.data) || {};
            setBadge(Number(unread.unread_messages || 0));
        }).catch(function () {});
    }

    function startChatWith(participantId) {
        request('conversations', {
            method: 'POST',
            body: JSON.stringify({ participant_user_ids: [participantId] })
        }).then(function (res) {
            var c = res && res.data;
            if (!c) return;
            var idx = state.conversations.findIndex(function (x) { return x.id === c.id; });
            if (idx >= 0) state.conversations[idx] = c;
            else state.conversations.unshift(c);
            showThread(c);
        }).catch(function () {
            alert(t('error_load', 'Error'));
        });
    }

    function sendText() {
        var input = $('fvChatInput');
        var body = (input && input.value || '').trim();
        if (!body || !state.activeId) return;
        input.value = '';
        request('conversations/' + state.activeId + '/messages', {
            method: 'POST',
            body: JSON.stringify({ body: body, message_type: 'text' })
        }).then(function (res) {
            var m = res && res.data;
            if (m) {
                state.messages.push(m);
                state.lastMsgId = m.id;
                renderMessages(true);
            }
            loadBootstrap();
        }).catch(function () {
            alert(t('error_send', 'Error'));
        });
    }

    function sendFile(file, messageType, durationSeconds) {
        if (!file || !state.activeId) return;
        var fd = new FormData();
        fd.append('file', file);
        fd.append('message_type', messageType);
        if (durationSeconds != null) fd.append('duration_seconds', String(durationSeconds));
        fetch(apiUrl('conversations/' + state.activeId + '/messages'), {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'X-Company-Id': String(companyId)
            },
            body: fd
        }).then(function (res) {
            return res.json().then(function (body) {
                if (!res.ok) throw new Error((body && body.message) || 'send failed');
                return body;
            });
        }).then(function (res) {
            var m = res && res.data;
            if (m) {
                state.messages.push(m);
                state.lastMsgId = m.id;
                renderMessages(true);
            }
            loadBootstrap();
        }).catch(function () {
            alert(t('error_send', 'Error'));
        });
    }

    function notifyUser(message, title) {
        var text = String(message || '').trim();
        if (!text) {
            return;
        }
        if (typeof window.swal === 'function') {
            if (title) {
                window.swal(String(title), text, 'warning');
            } else {
                window.swal({ title: text, icon: 'warning' });
            }
            return;
        }
        window.alert(title ? (String(title) + '\n\n' + text) : text);
    }

    function ensureMediaDevices() {
        if (typeof navigator === 'undefined') {
            return null;
        }
        if (!navigator.mediaDevices) {
            navigator.mediaDevices = {};
        }
        if (typeof navigator.mediaDevices.getUserMedia !== 'function') {
            var legacy = navigator.getUserMedia
                || navigator.webkitGetUserMedia
                || navigator.mozGetUserMedia
                || navigator.msGetUserMedia;
            if (typeof legacy === 'function') {
                navigator.mediaDevices.getUserMedia = function (constraints) {
                    return new Promise(function (resolve, reject) {
                        legacy.call(navigator, constraints, resolve, reject);
                    });
                };
            }
        }
        return typeof navigator.mediaDevices.getUserMedia === 'function'
            ? navigator.mediaDevices
            : null;
    }

    function micErrorMessage(err) {
        var name = (err && (err.name || err.message)) ? String(err.name || err.message) : '';
        if (name === 'insecure' || name === 'SecurityError') {
            return t('error_mic_insecure', t('error_mic', ''));
        }
        if (name === 'unsupported') {
            return t('error_mic_unsupported', t('error_mic', ''));
        }
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'denied') {
            return t('error_mic_denied', t('error_mic', ''));
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            return t('error_mic_not_found', t('error_mic', ''));
        }
        if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'AbortError') {
            return t('error_mic_busy', t('error_mic', ''));
        }
        return t('error_mic', 'Mic error');
    }

    function queryMicrophonePermission() {
        if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
            return Promise.resolve('prompt');
        }
        try {
            return navigator.permissions.query({ name: 'microphone' }).then(function (status) {
                return status && status.state ? status.state : 'prompt';
            }).catch(function () {
                return 'prompt';
            });
        } catch (e) {
            return Promise.resolve('prompt');
        }
    }

    function pickRecorderMimeType() {
        if (typeof MediaRecorder === 'undefined' || typeof MediaRecorder.isTypeSupported !== 'function') {
            return '';
        }
        var candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/aac',
            'audio/ogg;codecs=opus'
        ];
        var i;
        for (i = 0; i < candidates.length; i++) {
            if (MediaRecorder.isTypeSupported(candidates[i])) {
                return candidates[i];
            }
        }
        return '';
    }

    function requestMicrophoneStream() {
        if (typeof window.isSecureContext !== 'undefined' && !window.isSecureContext) {
            return Promise.reject({ name: 'insecure' });
        }
        var devices = ensureMediaDevices();
        if (!devices) {
            return Promise.reject({ name: 'unsupported' });
        }
        if (typeof MediaRecorder === 'undefined') {
            return Promise.reject({ name: 'unsupported' });
        }

        var constraints = {
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        };

        return queryMicrophonePermission().then(function (permState) {
            if (permState === 'denied') {
                return Promise.reject({ name: 'denied' });
            }
            // Solicitud explícita del permiso (debe ir ligada al clic del usuario).
            return devices.getUserMedia(constraints).catch(function () {
                // Algunos navegadores fallan con constraints avanzados: reintentar simple.
                return devices.getUserMedia({ audio: true });
            });
        });
    }

    function startRecording() {
        if (state.recording) {
            return;
        }
        var micBtn = $('fvChatMic');
        if (micBtn) {
            micBtn.disabled = true;
        }

        requestMicrophoneStream().then(function (stream) {
            state.audioChunks = [];
            state.mediaStream = stream;
            var mime = pickRecorderMimeType();
            try {
                state.mediaRecorder = mime
                    ? new MediaRecorder(stream, { mimeType: mime })
                    : new MediaRecorder(stream);
            } catch (e) {
                stream.getTracks().forEach(function (tr) { tr.stop(); });
                throw e;
            }
            state.recordingStartedAt = Date.now();
            state.mediaRecorder.ondataavailable = function (e) {
                if (e.data && e.data.size > 0) {
                    state.audioChunks.push(e.data);
                }
            };
            state.mediaRecorder.onerror = function () {
                stream.getTracks().forEach(function (tr) { tr.stop(); });
                state.recording = false;
                state.mediaStream = null;
                $('fvChatRecording').hidden = true;
                if (micBtn) {
                    micBtn.classList.remove('fv-active');
                    micBtn.disabled = false;
                }
                notifyUser(t('error_mic', 'Mic error'));
            };
            state.mediaRecorder.onstop = function () {
                stream.getTracks().forEach(function (tr) { tr.stop(); });
                state.mediaStream = null;
                var blobType = (state.mediaRecorder && state.mediaRecorder.mimeType) || mime || 'audio/webm';
                var blob = new Blob(state.audioChunks, { type: blobType });
                var secs = Math.max(1, Math.round((Date.now() - state.recordingStartedAt) / 1000));
                var ext = blobType.indexOf('mp4') !== -1 || blobType.indexOf('aac') !== -1
                    ? 'm4a'
                    : (blobType.indexOf('ogg') !== -1 ? 'ogg' : 'webm');
                var file = new File([blob], 'audio-' + Date.now() + '.' + ext, { type: blob.type });
                if (blob.size > 0) {
                    sendFile(file, 'audio', secs);
                }
                state.recording = false;
                $('fvChatRecording').hidden = true;
                if (micBtn) {
                    micBtn.classList.remove('fv-active');
                    micBtn.disabled = false;
                }
            };
            state.mediaRecorder.start(250);
            state.recording = true;
            $('fvChatRecording').hidden = false;
            if (micBtn) {
                micBtn.classList.add('fv-active');
                micBtn.disabled = false;
            }
        }).catch(function (err) {
            if (micBtn) {
                micBtn.disabled = false;
                micBtn.classList.remove('fv-active');
            }
            notifyUser(
                micErrorMessage(err),
                t('error_mic_permission_title', t('error_mic', ''))
            );
        });
    }

    function stopRecording() {
        if (state.mediaRecorder && state.recording) {
            try {
                if (state.mediaRecorder.state !== 'inactive') {
                    state.mediaRecorder.stop();
                }
            } catch (e) {
                if (state.mediaStream) {
                    state.mediaStream.getTracks().forEach(function (tr) { tr.stop(); });
                    state.mediaStream = null;
                }
                state.recording = false;
                $('fvChatRecording').hidden = true;
                var micBtn = $('fvChatMic');
                if (micBtn) {
                    micBtn.classList.remove('fv-active');
                    micBtn.disabled = false;
                }
            }
        }
    }

    function openPanel() {
        state.open = true;
        var panel = $('fvChatPanel');
        var backdrop = $('fvChatBackdrop');
        if (panel) {
            panel.hidden = false;
            // force reflow for transition
            void panel.offsetWidth;
            panel.classList.add('fv-open');
        }
        if (backdrop) {
            backdrop.hidden = false;
            void backdrop.offsetWidth;
            backdrop.classList.add('fv-open');
        }
        document.body.classList.add('fv-chat-sidebar-open');
        showList();
        loadBootstrap();
        startPolling();
    }

    function closePanel() {
        state.open = false;
        var panel = $('fvChatPanel');
        var backdrop = $('fvChatBackdrop');
        if (panel) panel.classList.remove('fv-open');
        if (backdrop) backdrop.classList.remove('fv-open');
        document.body.classList.remove('fv-chat-sidebar-open');
        window.setTimeout(function () {
            if (!state.open) {
                if (panel) panel.hidden = true;
                if (backdrop) backdrop.hidden = true;
            }
        }, 280);
        stopPolling();
        if (state.recording) stopRecording();
    }

    function startPolling() {
        stopPolling();
        state.pollTimer = setInterval(function () {
            if (!state.open) return;
            if (state.view === 'thread' && state.activeId) {
                loadMessages(false);
            }
            loadBootstrap();
        }, pollMs);
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    function bind() {
        var btn = $('fvChatBtn');
        if (!btn || !companyId || !token) {
            if (btn) btn.style.display = 'none';
            return;
        }

        state.collapsedGroups = loadCollapsedGroups();

        btn.addEventListener('click', function () {
            if (state.open) closePanel();
            else openPanel();
        });
        $('fvChatClose').addEventListener('click', closePanel);
        var backdrop = $('fvChatBackdrop');
        if (backdrop) backdrop.addEventListener('click', closePanel);
        $('fvChatBack').addEventListener('click', showList);
        $('fvChatNew').addEventListener('click', function () {
            showList();
            var el = $('fvChatContacts');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        $('fvChatSend').addEventListener('click', sendText);
        $('fvChatInput').addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendText();
            }
        });
        $('fvChatSearch').addEventListener('input', function (e) {
            state.search = String(e.target.value || '').toLowerCase().trim();
            renderLists();
        });
        $('fvChatPhoto').addEventListener('click', function () {
            $('fvChatPhotoInput').click();
        });
        $('fvChatPhotoInput').addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            e.target.value = '';
            if (file) sendFile(file, 'image');
        });
        $('fvChatMic').addEventListener('click', function () {
            if (state.recording) stopRecording();
            else startRecording();
        });
        $('fvChatStopRec').addEventListener('click', stopRecording);

        // badge inicial
        loadBootstrap();
        setInterval(function () {
            if (!state.open) loadBootstrap();
        }, Math.max(pollMs, 15000));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
