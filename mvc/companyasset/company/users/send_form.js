$(function () {
    function lang(key, fallback) {
        var L = window.companyUserFormLang || {};
        var v = L[key];
        return (v !== undefined && v !== null && String(v).length) ? String(v) : (fallback || '');
    }

    function isUserCreateForm(form) {
        return form && form.getAttribute('data-user-create') === '1';
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function buildErrorsHtml(errors) {
        if (!errors || typeof errors !== 'object') {
            return '';
        }
        var lines = Object.values(errors).reduce(function (acc, curr) {
            if (Array.isArray(curr)) {
                return acc.concat(curr);
            }
            return acc.concat([curr]);
        }, []).filter(Boolean);
        if (!lines.length) {
            return '';
        }
        return '<ul style="text-align:left;margin:0;padding-left:1.2em;">' +
            lines.map(function (x) {
                return '<li>' + escapeHtml(String(x)) + '</li>';
            }).join('') +
            '</ul>';
    }

    function showUserAlert(opts) {
        var title = opts.title || '';
        var text = opts.text || '';
        var html = opts.html || '';
        var icon = opts.icon || 'success';
        var confirmBtn = opts.confirmButtonText || lang('btn_ok', 'OK');
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            return Swal.fire({
                icon: icon,
                title: title,
                html: html || undefined,
                text: !html && text ? text : undefined,
                confirmButtonText: confirmBtn
            });
        }
        if (typeof swal === 'function') {
            var body = html ? undefined : text;
            return swal({ title: title, text: body, icon: icon });
        }
        alert(title + (text ? '\n' + text : ''));
        return typeof Promise !== 'undefined' ? Promise.resolve() : null;
    }

    function parseAjaxError(xhr) {
        var title = lang('user_error_generic', 'Error');
        var detailHtml = '';
        var detailText = '';
        var j = xhr.responseJSON;
        if (!j && xhr.responseText) {
            try {
                j = JSON.parse(xhr.responseText);
            } catch (e) {}
        }
        if (j) {
            if (j.message) {
                title = typeof j.message === 'string' ? j.message : title;
            }
            var errors = j.errors;
            detailHtml = buildErrorsHtml(errors);
            if (!detailHtml && errors && typeof errors === 'object') {
                detailText = Object.values(errors)
                    .reduce(function (acc, curr) {
                        if (Array.isArray(curr)) {
                            return acc.concat(curr);
                        }
                        return acc.concat([curr]);
                    }, [])
                    .filter(Boolean)
                    .join('\n');
            }
            if (j.existing_user && typeof j.existing_user === 'object') {
                var ex = j.existing_user;
                var exParts = [];
                exParts.push('<div class="text-start mt-2"><strong>' + escapeHtml(lang('user_existing_registered', 'Existing user')) + '</strong></div>');
                exParts.push('<div class="text-start">' + escapeHtml(lang('name', 'Name')) + ': ' + escapeHtml(String(ex.name || '')) + '</div>');
                exParts.push('<div class="text-start">' + escapeHtml(lang('email', 'Email')) + ': ' + escapeHtml(String(ex.email || '')) + '</div>');
                if (ex.is_admin) {
                    exParts.push('<div class="text-start">' + escapeHtml(lang('user_existing_admin', 'Administrator')) + '</div>');
                }
                if (ex.roles && ex.roles.length) {
                    exParts.push('<ul class="text-start mb-0" style="padding-left:1.2em;">');
                    ex.roles.forEach(function (r) {
                        exParts.push('<li>' + escapeHtml(String(r.company_name || '')) + ' — ' + escapeHtml(String(r.type || '')) + '</li>');
                    });
                    exParts.push('</ul>');
                }
                var exBlock = '<div class="small text-muted border-top pt-2 mt-2">' + exParts.join('') + '</div>';
                detailHtml = (detailHtml || '') + exBlock;
            }
        }
        return { title: title, html: detailHtml, text: detailText };
    }

    function updateProfileSelect(scope) {
        var root = scope && scope.querySelector ? scope : document;
        var roleSelect = root.querySelector('#role');
        var sellerSelect = root.querySelector('#profile_seller');
        if (!roleSelect || !sellerSelect) {
            return;
        }
        var role = String(roleSelect.value || '');
        if (role === 'seller') {
            sellerSelect.disabled = false;
        } else {
            sellerSelect.disabled = true;
            sellerSelect.value = '';
        }
    }

    function collectUserPayload(formElement) {
        const isCreate = isUserCreateForm(formElement);
        const data = {};
        const fieldNames = [
            'name', 'email', 'password', 'role', 'phone', 'cellphone', 'fax',
            'related_id', 'profile_id'
        ];
        if (!isCreate) {
            fieldNames.push('image');
        }
        fieldNames.forEach(function (name) {
            const el = formElement.querySelector('[name="' + name + '"]');
            if (!el || el.disabled) {
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) {
                    data[name] = el.value;
                }
            } else {
                data[name] = el.value;
            }
        });

        const seller = {
            phone_number: (formElement.querySelector('[name="mobile"]') || {}).value || '',
            cell_number: (formElement.querySelector('[name="cellNumber"]') || {}).value || '',
            fax: (formElement.querySelector('[name="seller_fax"]') || {}).value || null,
            email_on_salesman_order: (function () {
                const sw = formElement.querySelector('input[type="checkbox"][data-seller-email-order-flag="1"]');
                if (sw) {
                    return !!sw.checked;
                }
                const r = formElement.querySelector('[name="email_on_salesman_order"]:checked');
                return r ? r.value === '1' : false;
            })(),
            all_customers: !!(formElement.querySelector('[name="all_customers"]') || {}).checked,
            default: !!(formElement.querySelector('[name="checkBoxDefault"]') || {}).checked,
            proforma: !!(formElement.querySelector('[name="proforma"]') || {}).checked
        };

        data.seller = seller;
        var roleVal = String(data.role || '');
        if (roleVal === 'seller') {
            data.phone = seller.phone_number || data.phone || '';
            data.cellphone = seller.cell_number || data.cellphone || '';
            data.fax = seller.fax != null && seller.fax !== '' ? seller.fax : (data.fax || '');
        }
        if (isCreate) {
            delete data.image;
        }
        return data;
    }

    function collectUserEditPayload(formElement) {
        const data = {};
        ['name', 'email', 'password', 'phone', 'cellphone', 'fax', 'image',
            'first_name', 'last_name', 'address', 'city', 'state', 'zip_code'].forEach(function (name) {
            const el = formElement.querySelector('[name="' + name + '"]');
            if (!el || el.disabled) {
                return;
            }
            data[name] = el.value;
        });
        if (!data.password || !String(data.password).trim()) {
            delete data.password;
        }

        const allCustomersEl = formElement.querySelector('[name="all_customers"]');
        if (allCustomersEl) {
            const seller = {
                phone_number: (formElement.querySelector('[name="mobile"]') || {}).value || '',
                cell_number: (formElement.querySelector('[name="cellNumber"]') || {}).value || '',
                fax: (formElement.querySelector('[name="seller_fax"]') || {}).value || null,
                email_on_salesman_order: (function () {
                    const sw = formElement.querySelector('input[type="checkbox"][data-seller-email-order-flag="1"]');
                    return sw ? !!sw.checked : false;
                })(),
                all_customers: !!allCustomersEl.checked,
                default: !!(formElement.querySelector('[name="checkBoxDefault"]') || {}).checked,
                proforma: !!(formElement.querySelector('[name="proforma"]') || {}).checked
            };
            const profileEl = formElement.querySelector('[name="profile_id"]');
            if (profileEl && profileEl.value) {
                seller.profile_id = parseInt(profileEl.value, 10) || profileEl.value;
            }
            data.seller = seller;
            data.phone = seller.phone_number || data.phone || '';
            data.cellphone = seller.cell_number || data.cellphone || '';
            data.fax = seller.fax != null && seller.fax !== '' ? seller.fax : (data.fax || '');
        }
        return data;
    }

    function confirmDefaultSellerChange(onConfirm) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: 'warning',
                title: lang('user_default_seller_title', ''),
                text: lang('user_default_seller_text', ''),
                showCancelButton: true,
                confirmButtonText: lang('user_default_seller_confirm', 'OK'),
                cancelButtonText: lang('user_default_seller_cancel', 'Cancel')
            }).then(function (result) {
                if (result && result.isConfirmed) {
                    onConfirm();
                }
            });
            return;
        }
        if (typeof swal === 'function') {
            swal({
                title: lang('user_default_seller_title', ''),
                text: lang('user_default_seller_text', ''),
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: lang('user_default_seller_cancel', 'Cancel'),
                        value: false,
                        visible: true,
                        closeModal: true
                    },
                    confirm: {
                        text: lang('user_default_seller_confirm', 'Change'),
                        value: true,
                        visible: true,
                        closeModal: true
                    }
                }
            }).then(function (confirmed) {
                if (confirmed) {
                    onConfirm();
                }
            });
            return;
        }
        if (window.confirm(lang('user_default_seller_title', '') + '\n' + lang('user_default_seller_text', ''))) {
            onConfirm();
        }
    }

    function validateCreatePayload(payload) {
        if (!String(payload.name || '').trim()) {
            showUserAlert({ title: lang('user_validation_name_required', 'Name required'), icon: 'error' });
            return false;
        }
        if (!String(payload.email || '').trim()) {
            showUserAlert({ title: lang('user_validation_email_required', 'Email required'), icon: 'error' });
            return false;
        }
        if (!String(payload.password || '').trim()) {
            showUserAlert({ title: lang('user_validation_password_required', 'Password required'), icon: 'error' });
            return false;
        }
        if (String(payload.password || '').length < 8) {
            showUserAlert({ title: lang('user_validation_password_required', 'Password too short'), icon: 'error' });
            return false;
        }
        const role = String(payload.role || '');
        if (!role || ['manager', 'seller', 'warehouse', 'customer'].indexOf(role) === -1) {
            showUserAlert({ title: lang('user_validation_role_required', lang('select_role', 'Role required')), icon: 'error' });
            return false;
        }
        if (role === 'warehouse' || role === 'customer') {
            if (!payload.related_id || String(payload.related_id).trim() === '') {
                var relatedMsg = role === 'warehouse'
                    ? lang('user_validation_warehouse_required', lang('user_validation_related_required', 'Related required'))
                    : lang('user_validation_related_required', 'Related required');
                showUserAlert({ title: relatedMsg, icon: 'error' });
                return false;
            }
        }
        if (role === 'seller') {
            if (!payload.profile_id || String(payload.profile_id).trim() === '') {
                showUserAlert({ title: lang('user_validation_profile_required', 'Profile required'), icon: 'error' });
                return false;
            }
        }
        return true;
    }

    function buildCreateConfirmHtml(payload) {
        var L = window.companyUserFormLang || {};
        var lines = [];
        lines.push('<div class="text-start" style="max-height:60vh;overflow-y:auto;">');
        lines.push('<p><strong>' + escapeHtml(lang('name', 'Name')) + ':</strong> ' + escapeHtml(String(payload.name || '')) + '</p>');
        lines.push('<p><strong>' + escapeHtml(lang('email', 'Email')) + ':</strong> ' + escapeHtml(String(payload.email || '')) + '</p>');
        lines.push('<p><strong>' + escapeHtml(lang('password', 'Password')) + ':</strong> ' + escapeHtml(lang('user_password_masked', '********')) + '</p>');
        if (payload.role) {
            lines.push('<p><strong>' + escapeHtml(lang('user_confirm_label_role', 'Role')) + ':</strong> ' + escapeHtml(String(payload.role)) + '</p>');
        }
        if (payload.profile_id) {
            lines.push('<p><strong>' + escapeHtml(lang('user_confirm_label_profile', 'Profile')) + ':</strong> ' + escapeHtml(String(payload.profile_id)) + '</p>');
        }
        if (payload.related_id) {
            lines.push('<p><strong>' + escapeHtml(lang('user_confirm_label_related', 'Related')) + ':</strong> ' + escapeHtml(String(payload.related_id)) + '</p>');
        }
        if (payload.phone) {
            lines.push('<p><strong>' + escapeHtml(lang('telefono', 'Phone')) + ':</strong> ' + escapeHtml(String(payload.phone)) + '</p>');
        }
        if (payload.cellphone) {
            lines.push('<p><strong>' + escapeHtml(lang('cell_number', 'Cell')) + ':</strong> ' + escapeHtml(String(payload.cellphone)) + '</p>');
        }
        if (payload.fax) {
            lines.push('<p><strong>' + escapeHtml(lang('fax', 'Fax')) + ':</strong> ' + escapeHtml(String(payload.fax)) + '</p>');
        }
        if (payload.role === 'seller' && payload.seller) {
            var s = payload.seller;
            lines.push('<p><strong>' + escapeHtml(lang('user_confirm_label_seller', 'Seller')) + ':</strong></p>');
            lines.push('<ul class="small mb-0">');
            if (s.phone_number) {
                lines.push('<li>' + escapeHtml(lang('telefono', 'Phone')) + ': ' + escapeHtml(String(s.phone_number)) + '</li>');
            }
            if (s.cell_number) {
                lines.push('<li>' + escapeHtml(lang('cell_number', 'Cell')) + ': ' + escapeHtml(String(s.cell_number)) + '</li>');
            }
            lines.push('<li>default: ' + (s.default ? escapeHtml(lang('user_confirm_yes', 'Yes')) : escapeHtml(lang('user_confirm_no', 'No'))) + '</li>');
            lines.push('<li>proforma: ' + (s.proforma ? escapeHtml(lang('user_confirm_yes', 'Yes')) : escapeHtml(lang('user_confirm_no', 'No'))) + '</li>');
            lines.push('<li>all_customers: ' + (s.all_customers ? escapeHtml(lang('user_confirm_yes', 'Yes')) : escapeHtml(lang('user_confirm_no', 'No'))) + '</li>');
            lines.push('</ul>');
        }
        lines.push('</div>');
        return lines.join('');
    }

    function showCreateConfirmThenSend(url, form, payload) {
        var html = buildCreateConfirmHtml(payload);
        var title = lang('user_save_confirm_title', 'Confirm');
        var confirmBtn = lang('user_save_confirm_accept', 'Confirm');
        var cancelBtn = lang('user_save_confirm_cancel', 'Cancel');

        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: 'question',
                title: title,
                html: html,
                showCancelButton: true,
                confirmButtonText: confirmBtn,
                cancelButtonText: cancelBtn,
                width: '36em',
                focusCancel: true
            }).then(function (result) {
                if (result && result.isConfirmed) {
                    submitFormAsync(url, form, payload);
                }
            });
        } else if (typeof swal === 'function') {
            swal({
                title: title,
                icon: 'warning',
                buttons: {
                    cancel: { text: cancelBtn, value: false, visible: true },
                    confirm: { text: confirmBtn, value: true, visible: true }
                }
            }).then(function (ok) {
                if (ok) {
                    submitFormAsync(url, form, payload);
                }
            });
        } else if (window.confirm(title)) {
            submitFormAsync(url, form, payload);
        }
    }

    function resolveUploadUrl(form) {
        var u = form.getAttribute('data-upload-image-url');
        if (u && u.length) {
            return u;
        }
        var base = $('#companyUrl').val();
        if (typeof companyUrl !== 'undefined' && companyUrl) {
            base = companyUrl;
        }
        if (!base) {
            return '';
        }
        if (base.slice(-1) !== '/') {
            base += '/';
        }
        return base + 'users/upload_image';
    }

    $(document).on('submit', '#form_add', function (e) {
        const formCandidate = e.currentTarget;
        if (!isUserCreateForm(formCandidate)) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();

        const form = formCandidate;
        if (form.getAttribute('data-plan-limit-blocked') === '1') {
            var blockedMsg = form.getAttribute('data-plan-limit-message') || lang('user_error_generic', 'Error');
            showUserAlert({ title: blockedMsg, icon: 'warning' });
            return;
        }

        const url = form.getAttribute('action');
        const payload = collectUserPayload(form);

        if (!validateCreatePayload(payload)) {
            return;
        }

        const defaultCb = form.querySelector('[name="checkBoxDefault"]');
        const isSellerDefault = defaultCb && defaultCb.checked ? 1 : 0;

        function afterSellerWarning() {
            showCreateConfirmThenSend(url, form, payload);
        }

        if (isSellerDefault == 1 && window.hasDefaultSeller) {
            confirmDefaultSellerChange(afterSellerWarning);
        } else {
            afterSellerWarning();
        }
    });

    $(document).on('change', '#modalAddUser #role, #form_add #role', function () {
        var root = this.closest('form') || document;
        updateProfileSelect(root);
    });

    $(document).on('shown.bs.modal', '#modalAddUser', function () {
        updateProfileSelect(this);
    });

    if (document.getElementById('form_add')) {
        updateProfileSelect(document.getElementById('form_add'));
    }
    function submitFormAsync(url, form, payloadOptional) {
        const payload = payloadOptional ? JSON.parse(JSON.stringify(payloadOptional)) : collectUserPayload(form);

        if (isUserCreateForm(form)) {
            delete payload.image;
            doSend(url, payload, false);
            return;
        }

        const fileInput = form.querySelector('input[name="images"]');
        const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

        if (!file) {
            delete payload.image;
            doSend(url, payload, false);
            return;
        }

        const uploadUrl = resolveUploadUrl(form);
        if (!uploadUrl) {
            showUserAlert({
                title: lang('user_error_upload_url', 'Error'),
                icon: 'error'
            });
            return;
        }

        const mfd = new FormData();
        mfd.append('contents', file);

        fetch(uploadUrl, {
            method: 'POST',
            body: mfd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, body: j }; }); })
            .then(function (res) {
                if (!res.ok || !res.body || res.body.message === 'No file') {
                    var msg = (res.body && (res.body.message || res.body.error)) || lang('user_error_upload', 'Error');
                    showUserAlert({ title: msg, icon: 'error' });
                    return;
                }
                if (res.body.uuid) {
                    // Solo UUID: putContent ya normalizó a JPEG; el proxy sirve por UUID.
                    payload.image = String(res.body.uuid).replace(/\.[a-z0-9]+$/i, '');
                } else if (res.body.name) {
                    payload.image = res.body.name;
                }
                doSend(url, payload, false);
            })
            .catch(function (err) {
                showUserAlert({
                    title: lang('user_error_upload', 'Error'),
                    text: String(err) || '',
                    icon: 'error'
                });
            });
    }

    $(document).on('submit', '#form_edit', function (e) {
        e.preventDefault();
        const form = document.getElementById('form_edit');
        if (!form) {
            return;
        }
        const url = form.getAttribute('action');
        const payload = collectUserEditPayload(form);
        const fileInput = form.querySelector('input[name="images"]');
        const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        const defaultCb = form.querySelector('[name="checkBoxDefault"]');
        const wantsDefault = !!(defaultCb && defaultCb.checked && !defaultCb.disabled);

        function proceedEditSave() {
            if (!file) {
                if (!payload.image) {
                    delete payload.image;
                }
                doSend(url, payload, true);
                return;
            }

            const uploadUrl = resolveUploadUrl(form);
            if (!uploadUrl) {
                showUserAlert({
                    title: lang('user_error_upload_url', 'Error'),
                    icon: 'error'
                });
                return;
            }

            const mfd = new FormData();
            mfd.append('contents', file);

            fetch(uploadUrl, {
                method: 'POST',
                body: mfd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                .then(function (res) {
                    if (!res.ok || !res.body || res.body.message === 'No file') {
                        var msg = (res.body && (res.body.message || res.body.error)) || lang('user_error_upload', 'Error');
                        showUserAlert({ title: msg, icon: 'error' });
                        return;
                    }
                    if (res.body.uuid) {
                        payload.image = String(res.body.uuid).replace(/\.[a-z0-9]+$/i, '');
                    } else if (res.body.name) {
                        payload.image = res.body.name;
                    }
                    doSend(url, payload, true);
                })
                .catch(function (err) {
                    showUserAlert({
                        title: lang('user_error_upload', 'Error'),
                        text: String(err) || '',
                        icon: 'error'
                    });
                });
        }

        if (wantsDefault && window.hasDefaultSeller) {
            confirmDefaultSellerChange(proceedEditSave);
        } else {
            proceedEditSave();
        }
    });

    function doSend(url, payload, isEdit) {
        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (response) {
                var title = response.message || (isEdit ? lang('user_updated', '') : lang('user_saved', ''));
                var detailText = '';
                if (!isEdit && String(payload.role || '') === 'manager') {
                    detailText = lang('tab_manager', '');
                }
                if (!isEdit) {
                    try {
                        sessionStorage.setItem('company_users_create_success_title', String(title || ''));
                        sessionStorage.setItem('company_users_create_success_text', String(detailText || ''));
                    } catch (e) {
                        // ignore storage errors
                    }
                }
                var p = showUserAlert({
                    title: title,
                    text: detailText || undefined,
                    icon: 'success',
                    confirmButtonText: lang('btn_ok', 'OK')
                });
                var goAfterSave = function () {
                    var inAddModal = !!document.querySelector('#modalAddUser #form_add, #modalAddUser form#form_add');
                    var inEditModal = !!document.querySelector('#modalEditUser #form_edit, #modalEditUser form#form_edit');
                    if (inAddModal && typeof jQuery !== 'undefined') {
                        jQuery('#modalAddUser').modal('hide');
                    }
                    if (inEditModal && typeof jQuery !== 'undefined') {
                        jQuery('#modalEditUser').modal('hide');
                    }
                    if (!isEdit) {
                        var role = String(payload.role || '');
                        var listUrl = (typeof window.companyUsersListUrl === 'string' && window.companyUsersListUrl !== '')
                            ? window.companyUsersListUrl
                            : '';
                        if (role && ['manager', 'seller', 'warehouse', 'customer'].indexOf(role) !== -1) {
                            try {
                                var u = new URL(listUrl || window.location.href, window.location.origin);
                                u.searchParams.set('type', role);
                                listUrl = u.pathname + u.search;
                            } catch (e) {
                                listUrl = '/company/users?type=' + encodeURIComponent(role);
                            }
                        }
                        if (listUrl) {
                            window.location.assign(listUrl);
                            return;
                        }
                    }
                    if (inAddModal || inEditModal) {
                        window.location.reload();
                        return;
                    }
                    window.history.go(-1);
                };
                if (p && typeof p.then === 'function') {
                    p.then(goAfterSave);
                } else {
                    goAfterSave();
                }
            },
            error: function (xhr) {
                var err = parseAjaxError(xhr);
                showUserAlert({
                    title: err.title,
                    html: err.html || undefined,
                    text: err.html ? undefined : (err.text || undefined),
                    icon: 'error',
                    confirmButtonText: lang('btn_ok', 'OK')
                });
            }
        });
    }
});
