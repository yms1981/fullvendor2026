(function ($) {
    'use strict';

    function cfg() {
        return window.FV_PORTAL_PASSWORD || {};
    }

    function i18n(key) {
        var map = (cfg().i18n || {});
        return String(map[key] || '');
    }

    function apiHeaders() {
        var token = String(cfg().apiToken || '');
        var headers = {
            'Accept': 'application/json',
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

    function setEyeIcon($btn, visible) {
        $btn.find('i')
            .toggleClass('fa-eye', !visible)
            .toggleClass('fa-eye-slash', visible);
        var label = visible ? i18n('hide') : i18n('show');
        if (label) {
            $btn.attr('title', label).attr('aria-label', label);
        }
    }

    function toggleInputType($input, $btn) {
        var show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        setEyeIcon($btn, show);
    }

    $(function () {
        var $readonly = $('#customer_portal_password');
        var $toggle = $('#fv_portal_password_toggle');
        var $changeBtn = $('#fv_portal_password_change_btn');
        var $modal = $('#fv_portal_password_modal');
        var $newPwd = $('#fv_portal_password_new');
        var $confirmPwd = $('#fv_portal_password_confirm');
        var $error = $('#fv_portal_password_error');
        var $saveBtn = $('#fv_portal_password_save_btn');

        if (!$readonly.length) {
            return;
        }

        $toggle.on('click', function () {
            if ($readonly.prop('disabled')) {
                return;
            }
            toggleInputType($readonly, $toggle);
        });

        $(document).on('click', '.js-fv-portal-pwd-eye', function () {
            var target = $(this).attr('data-target');
            var $input = $(target);
            if (!$input.length) {
                return;
            }
            toggleInputType($input, $(this));
        });

        $changeBtn.on('click', function () {
            if ($changeBtn.prop('disabled')) {
                return;
            }
            $newPwd.val('');
            $confirmPwd.val('');
            $error.addClass('d-none').text('');
            $newPwd.attr('type', 'password');
            $confirmPwd.attr('type', 'password');
            $modal.find('.js-fv-portal-pwd-eye').each(function () {
                setEyeIcon($(this), false);
            });
            setEyeIcon($toggle, $readonly.attr('type') === 'text');
            $modal.modal('show');
        });

        $saveBtn.on('click', function () {
            var password = String($newPwd.val() || '');
            var confirm = String($confirmPwd.val() || '');
            $error.addClass('d-none').text('');

            if (password.trim() === '') {
                $error.removeClass('d-none').text(i18n('required') || i18n('mismatch'));
                return;
            }
            if (password !== confirm) {
                $error.removeClass('d-none').text(i18n('mismatch'));
                return;
            }

            var customerId = parseInt(cfg().customerId || 0, 10) || 0;
            var base = String(cfg().apiBaseUrl || '/api').replace(/\/$/, '');
            if (customerId < 1) {
                return;
            }

            $saveBtn.prop('disabled', true);

            $.ajax({
                url: base + '/v2/customer/' + customerId + '/portal-password',
                type: 'POST',
                dataType: 'json',
                headers: apiHeaders(),
                data: JSON.stringify({
                    password: password,
                    password_confirmation: confirm,
                }),
                success: function (response) {
                    var plain = (response && response.password_plain != null)
                        ? String(response.password_plain)
                        : password;
                    $readonly.val(plain).attr('type', 'password').prop('disabled', false);
                    setEyeIcon($toggle, false);
                    $toggle.prop('disabled', plain === '');
                    $modal.modal('hide');
                    var title = (response && response.message) ? response.message : i18n('updated');
                    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        Swal.fire({
                            title: title,
                            icon: 'success',
                            confirmButtonText: i18n('btn_accept') || 'OK',
                        });
                    }
                },
                error: function (xhr) {
                    var body = xhr.responseJSON || {};
                    var msg = body.message || i18n('error');
                    var errors = body.errors || {};
                    var detail = Object.values(errors)
                        .reduce(function (acc, curr) { return acc.concat(curr); }, [])
                        .join('\n');
                    if (detail) {
                        $error.removeClass('d-none').text(detail);
                    } else if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        Swal.fire({
                            title: msg,
                            icon: 'error',
                            confirmButtonText: i18n('btn_accept') || 'OK',
                        });
                    } else {
                        $error.removeClass('d-none').text(msg);
                    }
                },
                complete: function () {
                    $saveBtn.prop('disabled', false);
                },
            });
        });
    });
}(jQuery));
