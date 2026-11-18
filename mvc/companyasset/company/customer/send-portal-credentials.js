(function ($) {
    'use strict';

    function cfg() {
        return window.FV_PORTAL_PASSWORD || {};
    }

    function i18n(key) {
        var fromPortal = (cfg().i18n || {})[key];
        if (fromPortal) {
            return String(fromPortal);
        }
        var fromAssigned = (window.FV_ASSIGNED_USERS_I18N || {})[key];
        return String(fromAssigned || '');
    }

    function apiHeaders() {
        var token = String(cfg().apiToken || '');
        var headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (token !== '') {
            headers.Authorization = 'Bearer ' + token;
        }
        var locale = String(cfg().appLocale || '');
        if (locale !== '') {
            headers['X-App-Locale'] = locale;
            headers['Accept-Language'] = locale;
        }
        return headers;
    }

    function swalFire(options) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            return Swal.fire(options);
        }
        return Promise.resolve({ isConfirmed: false });
    }

    function notify(icon, text) {
        return swalFire({
            icon: icon,
            text: text,
            confirmButtonText: i18n('btn_accept') || i18n('aceptar') || 'OK',
        });
    }

    function normalizeChannel(raw) {
        var channel = String(raw || 'email').toLowerCase();
        if (channel !== 'sms' && channel !== 'whatsapp') {
            return 'email';
        }
        return channel;
    }

    function digitsOnly(raw) {
        return String(raw || '').replace(/\D+/g, '');
    }

    /**
     * Prefijo país: select comercial del cliente → botón → default compañía.
     * Nunca anteponer el país de la compañía si el select del cliente tiene país.
     */
    function resolvePhonecode($btn) {
        var $sel = $('#commercial_country_id');
        if ($sel.length) {
            var cid = String($sel.val() || '').trim();
            if (cid !== '') {
                var $opt = $sel.find('option[value="' + cid + '"]');
                var pc = digitsOnly($opt.attr('data-phonecode') || $opt.data('phonecode') || '');
                if (pc !== '' && pc !== '0') {
                    return pc;
                }
            }
            var $selected = $sel.find('option:selected');
            if ($selected.length) {
                var pcSel = digitsOnly($selected.attr('data-phonecode') || $selected.data('phonecode') || '');
                if (pcSel !== '' && pcSel !== '0') {
                    return pcSel;
                }
            }
        }

        var fromBtn = digitsOnly($btn.attr('data-phonecode') || '');
        if (fromBtn !== '' && fromBtn !== '0') {
            return fromBtn;
        }

        return digitsOnly(cfg().defaultPhonecode || '');
    }

    function withCountryPrefix(phone, phonecode) {
        var digits = digitsOnly(phone);
        var code = digitsOnly(phonecode);
        if (digits === '') {
            return '';
        }
        if (code === '') {
            return digits;
        }
        if (digits.indexOf(code) === 0 && digits.length > code.length) {
            return digits;
        }
        return code + digits;
    }

    /**
     * Quita un prefijo país incorrecto ya pegado (p. ej. 1 de USA sobre un móvil de Perú).
     */
    function toNationalDigits(raw, correctCode) {
        var digits = digitsOnly(raw);
        var code = digitsOnly(correctCode);
        if (digits === '') {
            return '';
        }
        if (code !== '' && digits.indexOf(code) === 0 && digits.length > code.length) {
            return digits.slice(code.length);
        }
        // Prefijo USA (1) u otros de 1–3 dígitos distintos al correcto.
        if (code !== '' && digits.length >= 10 && digits.length <= 12) {
            for (var len = 1; len <= 3; len++) {
                var maybe = digits.slice(0, len);
                if (maybe !== code && digits.length - len >= 8 && digits.length - len <= 10) {
                    // Solo strip si el resto parece número nacional y el prefijo era corto (1=US típico).
                    if (len === 1 && maybe === '1') {
                        return digits.slice(1);
                    }
                }
            }
        }
        return digits;
    }

    function formatPhoneDisplay(e164) {
        var digits = digitsOnly(e164);
        return digits !== '' ? ('+' + digits) : '';
    }

    function nationalPhoneFromFields() {
        var cell = digitsOnly($('#customer_cell_phone').val() || '');
        if (cell !== '') {
            return cell;
        }
        return digitsOnly($('#customer_phone').val() || $('input[name="phone"]').val() || '');
    }

    function resolvePhoneForSend($btn) {
        var code = resolvePhonecode($btn);
        var national = nationalPhoneFromFields();
        if (national === '') {
            national = toNationalDigits($btn.attr('data-phone') || '', code);
        } else {
            national = toNationalDigits(national, code);
        }
        var e164 = withCountryPrefix(national, code);
        return {
            e164: e164,
            display: formatPhoneDisplay(e164),
            phonecode: code,
        };
    }

    function confirmText(channel, email, phoneDisplay) {
        if (channel === 'sms') {
            return (i18n('portal_credentials_confirm_sms') || '').replace(':phone', phoneDisplay);
        }
        if (channel === 'whatsapp') {
            return (i18n('portal_credentials_confirm_whatsapp') || '').replace(':phone', phoneDisplay);
        }
        return (i18n('portal_credentials_confirm') || '').replace(':email', email);
    }

    function sendingText(channel) {
        if (channel === 'sms') {
            return i18n('portal_credentials_sending_sms') || i18n('portal_credentials_sending') || '...';
        }
        if (channel === 'whatsapp') {
            return i18n('portal_credentials_sending_whatsapp') || i18n('portal_credentials_sending') || '...';
        }
        return i18n('portal_credentials_sending') || '...';
    }

    $(function () {
        $(document).on('click', '.js-send-portal-credentials', function (e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.prop('disabled') || $btn.data('busy')) {
                return;
            }

            var channel = normalizeChannel($btn.attr('data-channel'));
            var userId = parseInt($btn.attr('data-user-id') || '0', 10);
            var customerId = parseInt($btn.attr('data-customer-id') || cfg().customerId || '0', 10);
            var email = String($btn.attr('data-email') || '').trim();
            var phoneInfo = resolvePhoneForSend($btn);

            if (userId < 1 || customerId < 1 || email === '') {
                notify('warning', i18n('portal_credentials_send_failed') || i18n('error'));
                return;
            }

            if (channel === 'sms' || channel === 'whatsapp') {
                if (phoneInfo.phonecode === '') {
                    notify('warning', i18n('portal_credentials_no_phonecode') || i18n('portal_credentials_send_failed') || i18n('error'));
                    return;
                }
                if (phoneInfo.e164 === '' || phoneInfo.e164 === phoneInfo.phonecode) {
                    notify('warning', i18n('portal_credentials_no_phone') || i18n('portal_credentials_send_failed') || i18n('error'));
                    return;
                }
                // Defensa: el destino DEBE empezar por el prefijo país.
                if (phoneInfo.e164.indexOf(phoneInfo.phonecode) !== 0) {
                    phoneInfo.e164 = phoneInfo.phonecode + digitsOnly(phoneInfo.e164);
                    phoneInfo.display = formatPhoneDisplay(phoneInfo.e164);
                }
            }

            var doSend = function () {
                var base = String(cfg().apiBaseUrl || '/api').replace(/\/$/, '');
                $btn.data('busy', 1).prop('disabled', true);
                swalFire({
                    title: sendingText(channel),
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function () {
                        if (typeof Swal !== 'undefined' && Swal.showLoading) {
                            Swal.showLoading();
                        }
                    },
                });

                var payload = {
                    user_id: userId,
                    channel: channel,
                };
                if (channel === 'sms' || channel === 'whatsapp') {
                    payload.phonecode = phoneInfo.phonecode;
                    payload.phone = phoneInfo.e164;
                }

                $.ajax({
                    url: base + '/v2/customer/' + customerId + '/send-portal-credentials',
                    method: 'POST',
                    headers: apiHeaders(),
                    data: JSON.stringify(payload),
                    processData: false,
                    contentType: 'application/json',
                })
                    .done(function (resp) {
                        var msg = (resp && resp.message) ? resp.message : i18n('portal_credentials_sent');
                        notify('success', msg);
                    })
                    .fail(function (xhr) {
                        var msg = i18n('portal_credentials_send_failed') || i18n('error');
                        try {
                            var body = xhr.responseJSON;
                            if (body && body.message) {
                                msg = body.message;
                            }
                        } catch (err) {}
                        notify('error', msg);
                    })
                    .always(function () {
                        $btn.data('busy', 0).prop('disabled', false);
                    });
            };

            swalFire({
                icon: 'question',
                text: confirmText(channel, email, phoneInfo.display),
                showCancelButton: true,
                confirmButtonText: i18n('btn_accept') || i18n('aceptar') || 'OK',
                cancelButtonText: i18n('cancel') || i18n('cancelar') || 'Cancel',
                reverseButtons: true,
            }).then(function (result) {
                if (result && result.isConfirmed) {
                    doSend();
                }
            });
        });
    });
})(jQuery);
