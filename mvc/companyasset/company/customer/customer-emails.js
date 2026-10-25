(function ($) {
    'use strict';

    var cfg = window.FV_CUSTOMER_EMAILS || {};
    var i18n = cfg.i18n || {};
    var customerId = parseInt(cfg.customerId || 0, 10);
    var apiToken = cfg.apiToken || '';
    var apiBase = (cfg.apiBaseUrl || '/api').replace(/\/$/, '');

    function t(key, fallback) {
        var v = i18n[key];
        return (typeof v === 'string' && v.trim() !== '') ? v : (fallback || key);
    }

    function apiHeaders() {
        var headers = {
            Authorization: 'Bearer ' + apiToken,
            Accept: 'application/json',
            'Content-Type': 'application/json'
        };
        // Preferir idioma de la compañía (no el del navegador).
        if (cfg.appLocale) {
            headers['X-App-Locale'] = String(cfg.appLocale);
        }
        if (cfg.languageId) {
            headers['X-Language-Id'] = String(cfg.languageId);
        }
        return headers;
    }

    function emailsUrl(suffix) {
        var base = apiBase + '/v2/customer/' + customerId + '/emails';
        return suffix ? (base + '/' + suffix) : base;
    }

    function showError($el, msg) {
        $el.text(msg || '').show();
    }

    function hideError($el) {
        $el.text('').hide();
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var lastRows = [];
    var OTHERS_MAX_LEN = 48;

    function syncPrimaryField(email) {
        var val = String(email || '').trim();
        $('#email_field').val(val);
        $('#email_hidden').val(val);
        $('#current_email').val(val);
    }

    function truncateText(text, maxLen) {
        var s = String(text || '');
        if (s.length <= maxLen) {
            return s;
        }
        return s.slice(0, Math.max(0, maxLen - 1)).replace(/[,\s]+$/, '') + '…';
    }

    function updateOthersPreview(rows) {
        var $preview = $('#fv_email_others_preview');
        if (!$preview.length) {
            return;
        }
        var others = (rows || []).filter(function (r) { return !r.is_primary; })
            .map(function (r) { return String(r.email || '').trim(); })
            .filter(Boolean);
        if (!others.length) {
            $preview.hide().text('').attr('data-full', '');
            return;
        }
        var full = others.join(', ');
        $preview
            .attr('data-full', full)
            .attr('title', full)
            .text(truncateText(full, OTHERS_MAX_LEN))
            .show();
    }

    function renderListModal(rows) {
        var $list = $('#fv_ce_emails_list');
        if (!$list.length) {
            return;
        }
        $list.empty();
        if (!rows || !rows.length) {
            $list.append('<li class="list-group-item text-muted text-center">' + escapeHtml(t('empty')) + '</li>');
            return;
        }
        rows.forEach(function (row) {
            var id = parseInt(row.id, 10) || 0;
            var email = String(row.email || '');
            var isPrimary = !!row.is_primary;
            var badge = isPrimary
                ? '<span class="badge badge-success">' + escapeHtml(t('default_badge') || t('primary_yes')) + '</span>'
                : '';
            var btn = isPrimary
                ? ''
                : '<button type="button" class="btn btn-sm btn-outline-primary fv-ce-list-set-primary" data-id="' + id + '">' +
                    escapeHtml(t('set_primary')) + '</button>';
            $list.append(
                '<li class="list-group-item' + (isPrimary ? ' is-primary' : '') + '" data-id="' + id + '">' +
                '<span class="fv-ce-list__email">' + escapeHtml(email) + '</span>' +
                '<span class="fv-ce-list__actions">' + badge + btn + '</span>' +
                '</li>'
            );
        });
    }

    function openListModal() {
        hideError($('#fv_ce_list_new_error'));
        $('#fv_ce_list_new_email').val('');
        $('#fv_ce_list_new_primary').prop('checked', false);
        renderListModal(lastRows);
        $('#fv_customer_emails_list_modal').modal('show');
        setTimeout(function () {
            $('#fv_ce_list_new_email').trigger('focus');
        }, 300);
    }

    function setPrimaryById(id) {
        if (!id) {
            return;
        }
        $.ajax({
            url: emailsUrl(id + '/set-primary'),
            method: 'POST',
            headers: apiHeaders(),
            success: function () {
                loadEmails();
            },
            error: function (xhr) {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: extractError(xhr), confirmButtonText: t('btn_accept') });
                }
            }
        });
    }

    function addEmailFromListModal() {
        var email = String($('#fv_ce_list_new_email').val() || '').trim();
        var isPrimary = $('#fv_ce_list_new_primary').is(':checked');
        var $err = $('#fv_ce_list_new_error');
        var $btn = $('#fv_ce_list_add_btn');

        hideError($err);
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError($err, t('email_invalid'));
            return;
        }

        $btn.prop('disabled', true);
        $.ajax({
            url: emailsUrl(),
            method: 'POST',
            headers: apiHeaders(),
            data: JSON.stringify({ email: email, is_primary: isPrimary }),
            success: function () {
                $('#fv_ce_list_new_email').val('');
                $('#fv_ce_list_new_primary').prop('checked', false);
                hideError($err);
                loadEmails();
            },
            error: function (xhr) {
                showError($err, extractError(xhr));
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    }

    function renderRows(rows) {
        lastRows = rows || [];
        var $tbody = $('#fv_customer_emails_tbody');
        $tbody.empty();
        updateOthersPreview(lastRows);
        renderListModal(lastRows);

        if (!rows || !rows.length) {
            $tbody.append(
                '<tr class="fv-customer-emails-empty-row"><td colspan="3" class="text-muted text-center py-3">' +
                escapeHtml(t('empty')) +
                '</td></tr>'
            );
            return;
        }

        rows.forEach(function (row) {
            var id = parseInt(row.id, 10) || 0;
            var email = String(row.email || '');
            var isPrimary = !!row.is_primary;
            var primaryBadge = isPrimary
                ? '<span class="badge badge-success">' + escapeHtml(t('primary_yes')) + '</span>'
                : '<span class="badge badge-secondary">' + escapeHtml(t('primary_no')) + '</span>';

            var actions = '<div class="fv-customer-emails-table__actions">';
            if (!isPrimary) {
                actions += '<button type="button" class="btn btn-outline-primary fv-crud-btn--row fv-ce-set-primary" title="' +
                    escapeHtml(t('set_primary')) + '"><i class="fa-solid fa-star" aria-hidden="true"></i></button>';
            }
            actions += '<button type="button" class="btn btn-outline-secondary fv-crud-btn--row fv-ce-edit" title="' +
                escapeHtml(t('edit_email')) + '"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>';
            actions += '<button type="button" class="btn btn-outline-danger fv-crud-btn--row fv-ce-delete" title="' +
                escapeHtml(t('eliminar')) + '"' + (isPrimary ? ' disabled' : '') +
                '><i class="fa-solid fa-trash" aria-hidden="true"></i></button>';
            actions += '</div>';

            $tbody.append(
                '<tr data-id="' + id + '" data-email="' + escapeHtml(email) + '" data-primary="' + (isPrimary ? '1' : '0') + '">' +
                '<td>' + escapeHtml(email) + '</td>' +
                '<td>' + primaryBadge + '</td>' +
                '<td class="text-center">' + actions + '</td>' +
                '</tr>'
            );
        });

        var primary = rows.find(function (r) { return !!r.is_primary; });
        if (primary && primary.email) {
            syncPrimaryField(primary.email);
        }
    }

    function loadEmails() {
        if (!customerId) {
            return;
        }
        $.ajax({
            url: emailsUrl(),
            method: 'GET',
            headers: apiHeaders(),
            success: function (resp) {
                renderRows((resp && resp.data) ? resp.data : []);
            }
        });
    }

    function openModal(editRow) {
        hideError($('#fv_ce_email_error'));
        if (editRow) {
            $('#fv_customer_email_modal_title').text(t('edit_email'));
            $('#fv_ce_edit_id').val(editRow.id);
            $('#fv_ce_email_input').val(editRow.email);
            $('#fv_ce_is_primary').prop('checked', !!editRow.is_primary);
        } else {
            $('#fv_customer_email_modal_title').text(t('add_email'));
            $('#fv_ce_edit_id').val('');
            $('#fv_ce_email_input').val('');
            $('#fv_ce_is_primary').prop('checked', false);
        }
        $('#fv_customer_email_modal').modal('show');
    }

    function extractError(xhr) {
        if (xhr && xhr.responseJSON) {
            var j = xhr.responseJSON;
            var emailMsg = (j.errors && j.errors.email && j.errors.email[0])
                ? String(j.errors.email[0])
                : (typeof j.message === 'string' ? j.message : '');
            if (emailMsg) {
                // Mensaje legacy de validación de usuarios (idioma incorrecto) → i18n de la UI.
                if (
                    emailMsg.indexOf('No puede crear otro usuario') !== -1 ||
                    emailMsg.indexOf('cannot create another user') !== -1 ||
                    emailMsg.indexOf('otro usuario con el mismo email') !== -1
                ) {
                    return t('email_already_registered', t('email_in_use'));
                }
                return emailMsg;
            }
        }
        return t('error_generic');
    }

    function saveEmail() {
        var email = String($('#fv_ce_email_input').val() || '').trim();
        var editId = String($('#fv_ce_edit_id').val() || '').trim();
        var isPrimary = $('#fv_ce_is_primary').is(':checked');
        var $err = $('#fv_ce_email_error');
        var $btn = $('#fv_ce_save_btn');

        hideError($err);
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError($err, t('email_invalid'));
            return;
        }

        $btn.prop('disabled', true);
        var req = editId
            ? {
                url: emailsUrl(editId),
                method: 'PUT',
                data: JSON.stringify({ email: email, is_primary: isPrimary })
            }
            : {
                url: emailsUrl(),
                method: 'POST',
                data: JSON.stringify({ email: email, is_primary: isPrimary })
            };

        $.ajax({
            url: req.url,
            method: req.method,
            headers: apiHeaders(),
            data: req.data,
            success: function () {
                $('#fv_customer_email_modal').modal('hide');
                loadEmails();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: t('saved_title'),
                        text: t('saved'),
                        confirmButtonText: t('btn_accept')
                    });
                }
            },
            error: function (xhr) {
                showError($err, extractError(xhr));
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    }

    $(function () {
        if (!customerId) {
            return;
        }

        // Cargar desde API para sincronizar primario legacy.
        loadEmails();

        // Grilla (+) y lápiz del campo Email → mismo modal (listado + alta + principal)
        $('#fv_customer_emails_add_btn').on('click', function () {
            openListModal();
        });

        $('#btn_change_email').off('click').on('click', function () {
            openListModal();
        });

        $('#fv_ce_save_btn').on('click', saveEmail);

        $('#fv_customer_emails_tbody').on('click', '.fv-ce-edit', function () {
            var $tr = $(this).closest('tr');
            openModal({
                id: $tr.data('id'),
                email: $tr.data('email'),
                is_primary: String($tr.data('primary')) === '1'
            });
        });

        $('#fv_customer_emails_tbody').on('click', '.fv-ce-set-primary', function () {
            setPrimaryById($(this).closest('tr').data('id'));
        });

        // Label de otros emails (junto a "Email:") → mismo modal
        $('#fv_email_others_preview').on('click', function (e) {
            e.preventDefault();
            openListModal();
        });

        $('#fv_ce_emails_list').on('click', '.fv-ce-list-set-primary', function () {
            setPrimaryById($(this).data('id'));
        });

        $('#fv_ce_list_add_btn').on('click', addEmailFromListModal);

        $('#fv_ce_list_new_email').on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                addEmailFromListModal();
            }
        });

        $('#fv_customer_emails_tbody').on('click', '.fv-ce-delete', function () {
            var $tr = $(this).closest('tr');
            if (String($tr.data('primary')) === '1') {
                return;
            }
            var id = $tr.data('id');
            var doDelete = function () {
                $.ajax({
                    url: emailsUrl(id),
                    method: 'DELETE',
                    headers: apiHeaders(),
                    success: function () {
                        loadEmails();
                    },
                    error: function (xhr) {
                        if (window.Swal) {
                            Swal.fire({ icon: 'error', title: extractError(xhr), confirmButtonText: t('btn_accept') });
                        }
                    }
                });
            };

            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: t('confirm_delete'),
                    showCancelButton: true,
                    confirmButtonText: t('eliminar'),
                    cancelButtonText: t('cancelar')
                }).then(function (result) {
                    if (result.isConfirmed) {
                        doDelete();
                    }
                });
            } else if (window.confirm(t('confirm_delete'))) {
                doDelete();
            }
        });
    });
})(jQuery);
