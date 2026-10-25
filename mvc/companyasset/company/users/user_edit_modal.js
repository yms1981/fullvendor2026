/* global jQuery */
(function ($) {
    'use strict';

    function t(key, fallback) {
        var lang = window.fvUserEditModalLang || {};
        var value = lang[key];
        if (value != null && String(value).trim() !== '') {
            return String(value);
        }
        return fallback != null ? String(fallback) : '';
    }

    function esc(s) {
        return $('<div>').text(String(s == null ? '' : s)).html();
    }

    function loadingHtml() {
        var label = t('loading', '');
        return (
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-danger" role="status" aria-hidden="true"></div>' +
            (label ? '<div class="mt-2 text-muted">' + esc(label) + '</div>' : '') +
            '</div>'
        );
    }

    function loadEditForm(userId) {
        var $body = $('#modal_edit_user_body');
        var base = String(window.fvUserEditFormUrlBase || '').replace(/\/$/, '');
        if (!userId || !base) {
            $body.html('<div class="alert alert-danger">' + esc(t('load_form_error', '')) + '</div>');
            return;
        }

        $body.html(loadingHtml());

        $.ajax({
            url: base + '/' + encodeURIComponent(userId),
            type: 'GET',
            dataType: 'html',
            timeout: 30000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
            .done(function (html) {
                var content = html == null ? '' : String(html).trim();
                if (!content || content.indexOf('form_edit') === -1) {
                    $body.html('<div class="alert alert-danger">' + esc(t('load_form_error', '')) + '</div>');
                    return;
                }
                $body.html(content);
            })
            .fail(function () {
                $body.html('<div class="alert alert-danger">' + esc(t('load_form_error', '')) + '</div>');
            });
    }

    $(function () {
        var $modal = $('#modalEditUser');
        if (!$modal.length) {
            return;
        }

        $modal.on('click', '.js-modal-toggle-password', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $inp = $btn.closest('.input-group').find('input[name="password"]');
            if (!$inp.length) {
                return;
            }
            var $icon = $btn.find('.fa').first();
            if ($inp.attr('type') === 'password') {
                $inp.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $inp.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        $modal.on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var userId = button && button.length ? button.data('user-id') : null;
            if (!userId) {
                userId = $modal.data('pending-user-id');
            }
            $modal.removeData('pending-user-id');
            if (userId) {
                loadEditForm(userId);
            }
        });

        $modal.on('hidden.bs.modal', function () {
            $modal.removeData('pending-user-id');
            $('#modal_edit_user_body').html(loadingHtml());
        });
    });
})(jQuery);
