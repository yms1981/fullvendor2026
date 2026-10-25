/* global jQuery */
(function ($) {
    'use strict';

    function cfg() {
        return window.userListModalConfig || {};
    }

    function langUser(k, fb) {
        var L = window.companyUserFormLang || {};
        var v = L[k];
        return (v !== undefined && v !== null && String(v).length) ? String(v) : (fb || '');
    }

    function lblCli(k, fb) {
        var L = window.userModalCustomerLabels || {};
        var v = L[k];
        return (v !== undefined && v !== null && String(v).length) ? String(v) : (fb || '');
    }

    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function valOf(x) {
        if (x === null || x === undefined) {
            return '';
        }
        if (typeof x === 'object') {
            if (x.name !== undefined && x.name !== null) {
                return String(x.name);
            }
            return '';
        }
        return String(x);
    }

    function formatAddress(addr, labels) {
        if (!addr || typeof addr !== 'object') {
            return '';
        }
        var parts = [];
        var street = [addr.street, addr.street_number].filter(Boolean).join(' ');
        if (street) {
            parts.push(street);
        }
        var city = valOf(addr.city);
        var st = valOf(addr.state);
        var co = valOf(addr.country);
        var line2 = [city, st, co].filter(Boolean).join(', ');
        if (line2) {
            parts.push(line2);
        }
        if (addr.zipcode) {
            parts.push(String(addr.zipcode));
        }
        if (addr.zone) {
            parts.push(String(addr.zone));
        }
        if (addr.shipping_notes) {
            parts.push(String(addr.shipping_notes));
        }
        return parts.join(' · ');
    }

    function pickAddresses(customer) {
        var commercial = null;
        var dispatch = null;
        var list = customer.addresses;
        if (!list) {
            return { commercial: null, dispatch: null };
        }
        if (!Array.isArray(list)) {
            list = [list];
        }
        list.forEach(function (a) {
            var t = (a.pivot && a.pivot.type) || a.type;
            if (t === 'commercial') {
                commercial = a;
            }
            if (t === 'dispatch') {
                dispatch = a;
            }
        });
        return { commercial: commercial, dispatch: dispatch };
    }

    function renderCustomerCard(customer) {
        var cg = customer.customer_group;
        var groupName = cg && (cg.name || cg.group_name) ? String(cg.name || cg.group_name) : '';

        var rows = [];
        function row(label, value) {
            if (!value && value !== 0) {
                return;
            }
            rows.push('<div class="row py-1 border-bottom"><div class="col-sm-4 text-muted small">' + esc(label) + '</div><div class="col-sm-8">' + esc(String(value)) + '</div></div>');
        }

        row(lblCli('nombre', 'Name'), customer.name);
        row(lblCli('col_business_name', 'Business name'), customer.business_name);
        row(lblCli('col_email', 'Email'), customer.email);
        row(lblCli('col_phone', 'Phone'), customer.phone);
        row(lblCli('col_cellphone', 'Cell'), customer.cellphone);
        row(lblCli('phones', 'Phones'), customer.phones);
        row(lblCli('col_tax', 'Tax ID'), customer.tax_id || customer.dni_tax);
        row(lblCli('field_cust_id_kor', 'ERP ID'), customer.cust_id_kor);
        if (customer.id_kor !== undefined && customer.id_kor !== null && String(customer.id_kor) !== '') {
            row('ID KOR', customer.id_kor);
        }
        row(lblCli('field_customer_group', 'Group'), groupName);
        row(lblCli('col_notes', 'Notes'), customer.notes);

        var ad = pickAddresses(customer);
        row(lblCli('col_commercial_address', 'Commercial address'), formatAddress(ad.commercial));
        row(lblCli('col_dispatch_delivery_address', 'Dispatch address'), formatAddress(ad.dispatch));

        if (!rows.length) {
            return '<p class="text-muted mb-0 small">' + esc(langUser('user_modal_customer_error', '')) + '</p>';
        }
        return '<div class="small">' + rows.join('') + '</div>';
    }

    function destroyModalRelatedSelect2($modal) {
        ['#modal-customer-select', '#modal-warehouse-select'].forEach(function (sel) {
            var $el = $modal.find(sel);
            if (!$el.length || !$el.data('select2')) {
                return;
            }
            try {
                $el.select2('destroy');
            } catch (err) {
                // ignore
            }
        });
    }

    function populateWarehouseSelectOptions($modal) {
        var $s = $modal.find('#modal-warehouse-select');
        if (!$s.length) {
            return;
        }
        var phText = langUser('user_modal_select_warehouse_placeholder', '');
        var $ph = $s.find('option[value=""]').first();
        if ($ph.length) {
            phText = $ph.text();
        }
        var rows = Array.isArray(cfg().warehouses) ? cfg().warehouses : [];
        $s.empty();
        $('<option>', { value: '', text: phText }).appendTo($s);
        rows.forEach(function (item) {
            var id = item && item.id != null ? String(item.id) : '';
            if (id === '') {
                return;
            }
            var text = item && item.name != null ? String(item.name) : id;
            $('<option>', { value: id, text: text }).appendTo($s);
        });
    }

    function resetWarehouseSelectDom($modal) {
        var $wh = $modal.find('#modal-warehouse-select');
        if (!$wh.length) {
            return;
        }
        var phText = '';
        var $ph = $wh.find('option[value=""]').first();
        if ($ph.length) {
            phText = $ph.text();
        } else {
            phText = langUser('user_modal_select_warehouse_placeholder', '');
        }
        $wh.empty();
        $('<option>', { value: '', text: phText }).appendTo($wh);
    }

    function whenSelect2Ready(cb) {
        var n = 0;
        function tick() {
            if (typeof $.fn.select2 === 'function') {
                cb();
                return;
            }
            n += 1;
            if (n > 100) {
                return;
            }
            setTimeout(tick, 50);
        }
        tick();
    }

    function normalizeWarehouseRows(data) {
        if (data === null || data === undefined) {
            return [];
        }
        if (Array.isArray(data)) {
            return data;
        }
        if (typeof data === 'object' && Array.isArray(data.data)) {
            return data.data;
        }
        return [];
    }

    function initCustomerSelect2($modal) {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }
        var $s = $modal.find('#modal-customer-select');
        if (!$s.length || $modal.find('#modal-customer-select-wrap').hasClass('d-none')) {
            return;
        }
        var ph = langUser('user_modal_select_customer_placeholder', '');
        try {
            if ($s.hasClass('select2-hidden-accessible') || $s.data('select2')) {
                $s.select2('destroy');
            }
        } catch (e1) {
            // ignore
        }
        $s.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: ph,
            allowClear: true,
            minimumResultsForSearch: 0
        });
    }

    function initWarehouseSelect2($modal) {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }
        var $s = $modal.find('#modal-warehouse-select');
        if (!$s.length || $modal.find('#modal-warehouse-select-wrap').hasClass('d-none')) {
            return;
        }
        try {
            if ($s.hasClass('select2-hidden-accessible') || $s.data('select2')) {
                $s.select2('destroy');
            }
        } catch (e2) {
            // ignore
        }
        var staticOptions = $s.find('option[value!=""]').length;
        if (staticOptions < 1) {
            populateWarehouseSelectOptions($modal);
            staticOptions = $s.find('option[value!=""]').length;
        }
        var ph = langUser('user_modal_select_warehouse_placeholder', '');
        if (staticOptions > 0) {
            $s.select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: ph,
                allowClear: true,
                minimumResultsForSearch: 0
            });
            return;
        }
        var url = cfg().searchWarehouseUrl;
        if (!url) {
            return;
        }
        resetWarehouseSelectDom($modal);
        $s.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: ph,
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: url,
                delay: 250,
                data: function (params) {
                    return { term: params.term || '' };
                },
                processResults: function (data) {
                    var rows = normalizeWarehouseRows(data);
                    return {
                        results: rows
                            .map(function (item) {
                                var id = item.id != null ? String(item.id) : '';
                                var text = item.name != null ? String(item.name) : id;
                                return { id: id, text: text };
                            })
                            .filter(function (r) {
                                return r.id !== '';
                            })
                    };
                }
            },
            language: {
                noResults: function () {
                    return langUser('user_modal_search_no_results', '');
                }
            }
        });
    }

    function syncRelatedSelect2($modal, role) {
        whenSelect2Ready(function () {
            if (role === 'customer') {
                initCustomerSelect2($modal);
            } else if (role === 'warehouse') {
                initWarehouseSelect2($modal);
            }
        });
    }

    function setRoleUi(role) {
        var $modal = $('#modalAddUser');
        var $customerWrap = $modal.find('#modal-customer-select-wrap');
        var $warehouseWrap = $modal.find('#modal-warehouse-select-wrap');
        var $seller = $modal.find('#seller-fields');
        var $profile = $modal.find('#profile_seller');
        var $related = $modal.find('#related_id');
        var $cardWrap = $modal.find('#modal-customer-card-wrap');
        var $custSel = $modal.find('#modal-customer-select');
        var $mainPhones = $modal.find('#modal-user-main-phones-row');

        $custSel.val('');
        if (role === 'warehouse') {
            populateWarehouseSelectOptions($modal);
        } else {
            resetWarehouseSelectDom($modal);
        }
        $modal.find('#modal-warehouse-select').val('');

        if (role === 'customer') {
            $mainPhones.removeClass('d-none');
            $customerWrap.removeClass('d-none');
            $warehouseWrap.addClass('d-none');
            $seller.hide();
            $profile.prop('disabled', true).val('');
            $related.val($custSel.val() || '');
            var cid = String($custSel.val() || '');
            if (cid) {
                $cardWrap.removeClass('d-none');
                loadCustomerCard(cid);
            } else {
                $cardWrap.addClass('d-none');
                $modal.find('#modal-customer-card-body').html('');
            }
        } else if (role === 'warehouse') {
            $mainPhones.removeClass('d-none');
            $customerWrap.addClass('d-none');
            $warehouseWrap.removeClass('d-none');
            $seller.hide();
            $profile.prop('disabled', true).val('');
            $related.val('');
            $cardWrap.addClass('d-none');
            $modal.find('#modal-customer-card-body').html('');
        } else if (role === 'seller') {
            $mainPhones.addClass('d-none');
            $customerWrap.addClass('d-none');
            $warehouseWrap.addClass('d-none');
            $seller.show();
            $profile.prop('disabled', false);
            $related.val('');
            $cardWrap.addClass('d-none');
            $modal.find('#modal-customer-card-body').html('');
        } else {
            $mainPhones.removeClass('d-none');
            $customerWrap.addClass('d-none');
            $warehouseWrap.addClass('d-none');
            $seller.hide();
            $profile.prop('disabled', true).val('');
            $related.val('');
            $cardWrap.addClass('d-none');
            $modal.find('#modal-customer-card-body').html('');
        }
    }

    function loadCustomerCard(customerId) {
        var $modal = $('#modalAddUser');
        var $body = $modal.find('#modal-customer-card-body');
        var base = cfg().customerDetailBaseUrl || '';
        if (!customerId || !base) {
            $body.html('');
            return;
        }
        $body.html('<p class="text-muted mb-0 small">' + esc(langUser('user_modal_customer_loading', '')) + '</p>');
        $.ajax({
            url: base + encodeURIComponent(customerId),
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .done(function (res) {
                var cardHtml = res && (res.card_html != null ? res.card_html : res.html);
                if (typeof cardHtml === 'string' && cardHtml.trim() !== '') {
                    $body.html(cardHtml);
                    return;
                }
                var c = res && res.data ? res.data : null;
                if (!c) {
                    $body.html('<p class="text-danger mb-0 small">' + esc(langUser('user_modal_customer_error', '')) + '</p>');
                    return;
                }
                $body.html(renderCustomerCard(c));
            })
            .fail(function () {
                $body.html('<p class="text-danger mb-0 small">' + esc(langUser('user_modal_customer_error', '')) + '</p>');
            });
    }

    $(function () {
        var $modal = $('#modalAddUser');
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

        $modal.on('show.bs.modal', function (e) {
            var role = cfg().defaultRole || 'manager';
            if (e && e.relatedTarget) {
                var dr = $(e.relatedTarget).data('defaultRole');
                if (dr) {
                    role = String(dr);
                }
            }
            destroyModalRelatedSelect2($modal);
            $modal.find('#role').val(role).trigger('change');
        });

        $modal.on('hidden.bs.modal', function () {
            destroyModalRelatedSelect2($modal);
            var f = document.getElementById('form_add');
            if (f && f.reset) {
                f.reset();
            }
            resetWarehouseSelectDom($modal);
            var $pw = $modal.find('input[name="password"]');
            if ($pw.length) {
                $pw.attr('type', 'password');
            }
            $modal.find('.js-modal-toggle-password .fa').removeClass('fa-eye-slash').addClass('fa-eye');
            $modal.find('#related_id').val('');
            $modal.find('#modal-customer-select').val('');
            $modal.find('#modal-warehouse-select').val('');
            $modal.find('#modal-customer-card-body').html('');
            $modal.find('#modal-customer-card-wrap').addClass('d-none');
        });

        $modal.on('change', '#role', function () {
            var role = String($(this).val() || '');
            destroyModalRelatedSelect2($modal);
            setRoleUi(role);
            syncRelatedSelect2($modal, role);
        });

        $modal.on('change', '#modal-customer-select', function () {
            var id = String($(this).val() || '');
            $modal.find('#related_id').val(id);
            if (id) {
                $modal.find('#modal-customer-card-wrap').removeClass('d-none');
                loadCustomerCard(id);
            } else {
                $modal.find('#modal-customer-card-wrap').addClass('d-none');
                $modal.find('#modal-customer-card-body').html('');
            }
        });

        $modal.on('change', '#modal-warehouse-select', function () {
            $modal.find('#related_id').val(String($(this).val() || ''));
        });
    });
})(jQuery);
