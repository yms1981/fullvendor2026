/* global jQuery */
(function ($) {
    'use strict';

    function cfg() {
        return window.FV_USER_AVATAR || {};
    }

    function t(key, fallback) {
        var lang = cfg().lang || {};
        var value = lang[key];
        if (value != null && String(value).trim() !== '' && String(value) !== key) {
            return String(value);
        }
        if (fallback != null && String(fallback).trim() !== '') {
            return String(fallback);
        }
        return '';
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var state = {
        userId: 0,
        name: '',
        email: '',
        file: null,
        bg: '',
        initials: '',
        $btn: null
    };

    function resetPreview() {
        var $img = $('#fvUserAvatarPreviewImg');
        var $ini = $('#fvUserAvatarPreviewInitials');
        $img.addClass('d-none').attr('src', '');
        $ini
            .removeClass('d-none')
            .addClass('d-flex')
            .text(state.initials || '?')
            .css('background', state.bg || 'hsl(0,42%,46%)');
    }

    function showPreviewUrl(url) {
        var $img = $('#fvUserAvatarPreviewImg');
        var $ini = $('#fvUserAvatarPreviewInitials');
        if (!url) {
            resetPreview();
            return;
        }
        $ini.addClass('d-none').removeClass('d-flex');
        $img.removeClass('d-none').attr('src', url);
    }

    function setError(msg) {
        var $err = $('#fvUserAvatarError');
        if (!msg) {
            $err.addClass('d-none').text('');
            return;
        }
        $err.removeClass('d-none').text(msg);
    }

    function setBusy(busy) {
        var $save = $('#fvUserAvatarSave');
        var $file = $('#fvUserAvatarFile');
        $save.prop('disabled', busy || !state.file);
        $file.prop('disabled', !!busy);
        $('#fvUserAvatarSaveLabel').text(busy ? t('avatar_uploading', '') : t('avatar_save', ''));
    }

    function openModal($btn) {
        state.$btn = $btn;
        state.userId = Number($btn.data('user-id') || 0);
        state.name = String($btn.data('user-name') || '');
        state.email = String($btn.data('user-email') || '');
        state.initials = String($btn.data('initials') || '?');
        state.bg = String($btn.data('bg') || 'hsl(0,42%,46%)');
        state.file = null;

        $('#fvUserAvatarUserName').text(state.name);
        $('#fvUserAvatarUserEmail').text(state.email);
        $('#fvUserAvatarFile').val('');
        $('#fvUserAvatarSave').prop('disabled', true);
        setError('');

        var currentUrl = String($btn.data('image-url') || '');
        if (currentUrl) {
            showPreviewUrl(currentUrl);
        } else {
            resetPreview();
        }

        $('#fvUserAvatarModal').modal('show');
    }

    function applyAvatarToButton(uuid, extension) {
        if (!state.$btn || !state.$btn.length) {
            return;
        }
        var base = String(cfg().imageBaseUrl || '').replace(/\/$/, '');
        var pureUuid = String(uuid || '').replace(/\.[a-z0-9]+$/i, '');
        var url = base + '/' + pureUuid + '?v=' + Date.now();
        state.$btn.attr('data-image-uuid', pureUuid);
        state.$btn.attr('data-image-url', url);

        var $inner = state.$btn.find('.js-user-avatar-inner');
        $inner.html(
            '<img src="' + esc(url) + '" alt="" width="44" height="44"' +
            ' class="user-list-avatar-img rounded-circle border border-light shadow-sm"' +
            ' style="object-fit:cover;display:block;"' +
            ' onerror="this.style.display=\'none\';var n=this.nextElementSibling;if(n){n.classList.remove(\'d-none\');n.classList.add(\'d-flex\');}">' +
            '<span class="user-list-avatar-initials rounded-circle border border-light shadow-sm text-white font-weight-bold align-items-center justify-content-center d-none"' +
            ' style="width:44px;height:44px;font-size:13px;line-height:1;background:' + esc(state.bg) + ';">' +
            esc(state.initials) + '</span>'
        );
    }

    function uploadAndSave() {
        if (!state.file || !state.userId) {
            return;
        }
        if (!state.name || !state.email) {
            setError(t('user_error_generic', ''));
            return;
        }

        setBusy(true);
        setError('');

        var fd = new FormData();
        fd.append('contents', state.file);

        fetch(String(cfg().uploadUrl || ''), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (body) {
                    if (!res.ok) {
                        var err = new Error((body && (body.message || body.error)) || t('user_error_upload', ''));
                        err.body = body;
                        throw err;
                    }
                    return body;
                });
            })
            .then(function (img) {
                var payloadImg = img;
                if (img && img.data && typeof img.data === 'object') {
                    payloadImg = img.data;
                }
                var uuid = (payloadImg && (payloadImg.uuid || payloadImg.id)) || '';
                var extension = (payloadImg && payloadImg.extension) || 'webp';
                if (!uuid) {
                    throw new Error(t('user_error_upload', ''));
                }
                var imageRef = String(uuid).replace(/\.[a-z0-9]+$/i, '');
                var extNorm = String(extension || 'jpg').replace(/^\./, '').toLowerCase();
                if (extNorm === 'jpeg') {
                    extNorm = 'jpg';
                }
                if (!extNorm || ['jpg', 'png', 'gif', 'webp'].indexOf(extNorm) === -1) {
                    extNorm = 'jpg';
                }
                // Solo UUID: el proxy MVC / API sirve el JPEG normalizado.
                var payload = {
                    name: state.name,
                    email: state.email,
                    image: imageRef
                };
                return fetch(String(cfg().updateUrlBase || '').replace(/\/$/, '') + '/' + encodeURIComponent(state.userId), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (body) {
                        if (!res.ok) {
                            var msg = (body && body.message) || t('user_error_save', '');
                            if (body && body.errors && typeof body.errors === 'object') {
                                var parts = [];
                                Object.keys(body.errors).forEach(function (k) {
                                    var v = body.errors[k];
                                    if (Array.isArray(v)) {
                                        v.forEach(function (x) { parts.push(x); });
                                    } else if (v) {
                                        parts.push(v);
                                    }
                                });
                                if (parts.length) {
                                    msg = parts.join(' ');
                                }
                            }
                            var err = new Error(msg);
                            err.body = body;
                            throw err;
                        }
                        return { uuid: imageRef, extension: extNorm };
                    });
                });
            })
            .then(function (info) {
                applyAvatarToButton(info.uuid, info.extension);
                $('#fvUserAvatarModal').modal('hide');
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    Swal.fire({
                        icon: 'success',
                        title: t('avatar_updated', ''),
                        confirmButtonText: t('ok', 'OK')
                    });
                }
            })
            .catch(function (err) {
                setError((err && err.message) || t('user_error_upload', ''));
            })
            .finally(function () {
                setBusy(false);
            });
    }

    function bind() {
        $(document).on('click', '.js-user-avatar-upload', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal($(this));
        });

        $('#fvUserAvatarFile').on('change', function () {
            var files = this.files || [];
            state.file = files[0] || null;
            setError('');
            $('#fvUserAvatarSave').prop('disabled', !state.file);
            if (!state.file) {
                var currentUrl = state.$btn ? String(state.$btn.data('image-url') || '') : '';
                if (currentUrl) {
                    showPreviewUrl(currentUrl);
                } else {
                    resetPreview();
                }
                return;
            }
            if (state.file.size > 5 * 1024 * 1024) {
                state.file = null;
                this.value = '';
                $('#fvUserAvatarSave').prop('disabled', true);
                setError(t('avatar_hint', ''));
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                showPreviewUrl(ev.target.result);
            };
            reader.readAsDataURL(state.file);
        });

        $('#fvUserAvatarSave').on('click', function () {
            uploadAndSave();
        });

        $('#fvUserAvatarModal').on('hidden.bs.modal', function () {
            state.file = null;
            state.$btn = null;
            $('#fvUserAvatarFile').val('');
            setError('');
        });
    }

    $(bind);
})(jQuery);
