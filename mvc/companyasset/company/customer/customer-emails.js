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
        $('input[name="email"]').not('#fv_ce_email_input, #fv_ce_list_new_email').each(function () {
            if ($(this).attr('type') !== 'hidden' || $(this).attr('id') === 'email_hidden') {
                $(this).val(val);
            }
        });
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

    function rowSendCatalog(row) {
        if (typeof row.send_catalog === 'undefined' || row.send_catalog === null) {
            return true;
        }
        return !!row.send_catalog;
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
            var sendCatalog = rowSendCatalog(row);
            var badge = isPrimary
                ? '<span class="badge badge-success">' + escapeHtml(t('default_badge') || t('primary_yes')) + '</span>'
                : '<button type="button" class="btn btn-sm btn-outline-primary fv-ce-list-set-primary" data-id="' + id + '">' +
                    escapeHtml(t('set_primary')) + '</button>';
            var catalogCheck =
                '<label class="fv-ce-list__catalog mb-0">' +
                '<input type="checkbox" class="fv-ce-list-send-catalog" data-id="' + id + '"' +
                (isPrimary || sendCatalog ? ' checked' : '') +
                (isPrimary ? ' disabled' : '') + '>' +
                '<span>' + escapeHtml(t('send_catalog')) + '</span>' +
                '</label>';
            var delBtn = '';
            if (!isPrimary) {
                delBtn =
                    '<button type="button" class="btn btn-sm btn-outline-danger fv-ce-list-delete" data-id="' + id + '" title="' +
                    escapeHtml(t('eliminar')) + '">' +
                    '<i class="fa-solid fa-trash" aria-hidden="true"></i></button>';
            }

            $list.append(
                '<li class="list-group-item' + (isPrimary ? ' is-primary' : '') + '" data-id="' + id + '"' +
                (isPrimary ? ' data-primary="1"' : ' data-primary="0"') + '>' +
                '<span class="fv-ce-list__email">' + escapeHtml(email) + '</span>' +
                '<span class="fv-ce-list__actions">' + badge + catalogCheck + delBtn + '</span>' +
                '</li>'
            );
        });
    }

    function openListModal() {
        hideError($('#fv_ce_list_new_error'));
        $('#fv_ce_list_new_email').val('');
        $('#fv_ce_list_new_primary').prop('checked', false);
        $('#fv_ce_list_new_send_catalog').prop('checked', true).prop('disabled', false);
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
            success: function (resp) {
                var email = resp && resp.data && resp.data.email ? resp.data.email : '';
                if (email) {
                    syncPrimaryField(email);
                }
                loadEmails();
            },
            error: function (xhr) {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: extractError(xhr), confirmButtonText: t('btn_accept') });
                }
            }
        });
    }

    function setSendCatalogById(id, enabled, $checkbox) {
        if (!id) {
            return;
        }
        $.ajax({
            url: emailsUrl(id + '/send-catalog'),
            method: 'POST',
            headers: apiHeaders(),
            data: JSON.stringify({ send_catalog: !!enabled }),
            success: function () {
                loadEmails();
            },
            error: function (xhr) {
                if ($checkbox && $checkbox.length) {
                    $checkbox.prop('checked', !enabled);
                }
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: extractError(xhr), confirmButtonText: t('btn_accept') });
                }
            }
        });
    }

    function deleteEmailById(id) {
        if (!id) {
            return;
        }
        var row = (lastRows || []).find(function (r) {
            return parseInt(r.id, 10) === parseInt(id, 10);
        });
        if (row && row.is_primary) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: t('cannot_delete_primary') || t('error_generic'),
                    confirmButtonText: t('btn_accept')
                });
            }
            return;
        }
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
    }

    function addEmailFromListModal() {
        var email = String($('#fv_ce_list_new_email').val() || '').trim();
        var isPrimary = $('#fv_ce_list_new_primary').is(':checked');
        var sendCatalog = isPrimary ? true : $('#fv_ce_list_new_send_catalog').is(':checked');
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
            data: JSON.stringify({
                email: email,
                is_primary: isPrimary,
                send_catalog: sendCatalog
            }),
            success: function (resp) {
                $('#fv_ce_list_new_email').val('');
                $('#fv_ce_list_new_primary').prop('checked', false);
                $('#fv_ce_list_new_send_catalog').prop('checked', true);
                hideError($err);
                if (isPrimary && resp && resp.data && resp.data.email) {
                    syncPrimaryField(resp.data.email);
                }
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
                '<tr class="fv-customer-emails-empty-row"><td colspan="4" class="text-muted text-center py-3">' +
                escapeHtml(t('empty')) +
                '</td></tr>'
            );
            return;
        }

        rows.forEach(function (row) {
            var id = parseInt(row.id, 10) || 0;
            var email = String(row.email || '');
            var isPrimary = !!row.is_primary;
            var sendCatalog = rowSendCatalog(row);
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
            if (!isPrimary) {
                actions += '<button type="button" class="btn btn-outline-danger fv-crud-btn--row fv-ce-delete" title="' +
                    escapeHtml(t('eliminar')) + '"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>';
            }
            actions += '</div>';

            $tbody.append(
                '<tr data-id="' + id + '" data-email="' + escapeHtml(email) +
                '" data-primary="' + (isPrimary ? '1' : '0') +
                '" data-send-catalog="' + (sendCatalog ? '1' : '0') + '">' +
                '<td>' + escapeHtml(email) + '</td>' +
                '<td>' + primaryBadge + '</td>' +
                '<td class="text-center"><input type="checkbox" class="fv-ce-send-catalog"' +
                (isPrimary || sendCatalog ? ' checked' : '') +
                (isPrimary ? ' disabled' : '') +
                ' title="' + escapeHtml(t('send_catalog')) + '"></td>' +
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

    function syncSendCatalogCheckboxState() {
        var isPrimary = $('#fv_ce_is_primary').is(':checked');
        if (isPrimary) {
            $('#fv_ce_send_catalog').prop('checked', true).prop('disabled', true);
        } else {
            $('#fv_ce_send_catalog').prop('disabled', false);
        }
    }

    function openModal(editRow) {
        hideError($('#fv_ce_email_error'));
        if (editRow) {
            $('#fv_customer_email_modal_title').text(t('edit_email'));
            $('#fv_ce_edit_id').val(editRow.id);
            $('#fv_ce_email_input').val(editRow.email);
            $('#fv_ce_is_primary').prop('checked', !!editRow.is_primary);
            $('#fv_ce_send_catalog').prop('checked', !!editRow.is_primary || rowSendCatalog(editRow));
        } else {
            $('#fv_customer_email_modal_title').text(t('add_email'));
            $('#fv_ce_edit_id').val('');
            $('#fv_ce_email_input').val('');
            $('#fv_ce_is_primary').prop('checked', false);
            $('#fv_ce_send_catalog').prop('checked', true);
        }
        syncSendCatalogCheckboxState();
        $('#fv_customer_email_modal').modal('show');
    }

    function extractError(xhr) {
        if (xhr && xhr.responseJSON) {
            var j = xhr.responseJSON;
            var emailMsg = (j.errors && j.errors.email && j.errors.email[0])
                ? String(j.errors.email[0])
                : (typeof j.message === 'string' ? j.message : '');
            if (emailMsg) {
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
        var sendCatalog = isPrimary ? true : $('#fv_ce_send_catalog').is(':checked');
        var $err = $('#fv_ce_email_error');
        var $btn = $('#fv_ce_save_btn');

        hideError($err);
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError($err, t('email_invalid'));
            return;
        }

        $btn.prop('disabled', true);
        var payload = {
            email: email,
            is_primary: isPrimary,
            send_catalog: sendCatalog
        };
        var req = editId
            ? { url: emailsUrl(editId), method: 'PUT', data: JSON.stringify(payload) }
            : { url: emailsUrl(), method: 'POST', data: JSON.stringify(payload) };

        $.ajax({
            url: req.url,
            method: req.method,
            headers: apiHeaders(),
            data: req.data,
            success: function (resp) {
                $('#fv_customer_email_modal').modal('hide');
                if (isPrimary) {
                    syncPrimaryField(email);
                } else if (resp && resp.data && resp.data.is_primary && resp.data.email) {
                    syncPrimaryField(resp.data.email);
                }
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

        loadEmails();

        $('#fv_customer_emails_add_btn').on('click', function () {
            openListModal();
        });

        $('#btn_change_email').off('click').on('click', function () {
            openListModal();
        });

        $('#fv_ce_save_btn').on('click', saveEmail);

        $('#fv_ce_is_primary').on('change', syncSendCatalogCheckboxState);
        $('#fv_ce_list_new_primary').on('change', function () {
            if ($(this).is(':checked')) {
                $('#fv_ce_list_new_send_catalog').prop('checked', true).prop('disabled', true);
            } else {
                $('#fv_ce_list_new_send_catalog').prop('disabled', false);
            }
        });

        $('#fv_customer_emails_tbody').on('click', '.fv-ce-edit', function () {
            var $tr = $(this).closest('tr');
            openModal({
                id: $tr.data('id'),
                email: $tr.data('email'),
                is_primary: String($tr.data('primary')) === '1',
                send_catalog: String($tr.data('send-catalog')) !== '0'
            });
        });

        $('#fv_customer_emails_tbody').on('click', '.fv-ce-set-primary', function () {
            setPrimaryById($(this).closest('tr').data('id'));
        });

        $('#fv_customer_emails_tbody').on('change', '.fv-ce-send-catalog', function () {
            var $cb = $(this);
            if ($cb.is(':disabled') || String($cb.closest('tr').data('primary')) === '1') {
                $cb.prop('checked', true);
                return;
            }
            setSendCatalogById($cb.closest('tr').data('id'), $cb.is(':checked'), $cb);
        });

        $('#fv_email_others_preview').on('click', function (e) {
            e.preventDefault();
            openListModal();
        });

        $('#fv_ce_emails_list').on('click', '.fv-ce-list-set-primary', function () {
            setPrimaryById($(this).data('id'));
        });

        $('#fv_ce_emails_list').on('change', '.fv-ce-list-send-catalog', function () {
            var $cb = $(this);
            if ($cb.is(':disabled')) {
                $cb.prop('checked', true);
                return;
            }
            setSendCatalogById($cb.data('id'), $cb.is(':checked'), $cb);
        });

        $('#fv_ce_emails_list').on('click', '.fv-ce-list-delete', function () {
            deleteEmailById($(this).data('id'));
        });

        $('#fv_ce_list_add_btn').on('click', addEmailFromListModal);

        $('#fv_ce_list_new_email').on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                addEmailFromListModal();
            }
        });

        $('#fv_customer_emails_tbody').on('click', '.fv-ce-delete', function () {
            deleteEmailById($(this).closest('tr').data('id'));
        });
    });
})(jQuery);
