<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Teléfonos admin company (add/edit): prefijo +phonecode y máscara countries.phonemask vía Inputmask.
 * Requiere jQuery. Carga Inputmask si no está presente.
 */
$adminPhoneInputmaskUrl = base_url('backend/vendors/inputmask/jquery.inputmask.bundle.js');
?>
<script src="<?= htmlspecialchars($adminPhoneInputmaskUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
(function($) {
    'use strict';

    var adminCountryFlagsBaseUrl = <?= json_encode(rtrim((string) base_url(), '/') . '/images/flags/') ?>;

    function adminCountryFlagHtml(sortname) {
        var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
        if (sn.length < 2) return '';
        var src = adminCountryFlagsBaseUrl + sn + '.png';
        return '<img src="' + src.replace(/"/g, '&quot;') + '" alt="" class="admin-country-s2-flag" onerror="this.style.display=\'none\'" />';
    }

    function adminInitCountrySelect2() {
        var $jq = window.jQuery;
        var $el = $jq('#admin_company_country_id');
        if (!$el.length || typeof $jq.fn.select2 === 'undefined') {
            return false;
        }
        if ($el.hasClass('select2-hidden-accessible')) {
            return true;
        }
        try { $el.select2('destroy'); } catch (e) {}
        var placeholder = String($el.data('placeholder') || '').trim();
        $el.select2({
            placeholder: placeholder || ' ',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $jq('#admin_company_country_select_wrap'),
            matcher: function(params, data) {
                if ($jq.trim(params.term || '') === '') {
                    return data;
                }
                if (typeof data.text === 'undefined' || data.children) {
                    return null;
                }
                var term = String(params.term || '').toLowerCase();
                var raw = (typeof data.text === 'string') ? data.text : '';
                var txt = $jq('<div/>').html(raw).text().toLowerCase();
                var el = data.element;
                var sortname = el ? String($jq(el).data('sortname') || '').toLowerCase() : '';
                if (txt.indexOf(term) > -1 || sortname.indexOf(term) > -1) {
                    return data;
                }
                return null;
            },
            templateResult: function(country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $jq(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $jq('<span/>').text(($jq.trim($opt.text()) || '').toUpperCase()).html();
                var fh = adminCountryFlagHtml(sortname);
                if (fh) {
                    return $jq('<span class="admin-country-s2-line"/>').html(fh + labelUp);
                }
                return $jq('<span/>').html(labelUp);
            },
            templateSelection: function(country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $jq(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $jq('<span/>').text(($jq.trim($opt.text()) || '').toUpperCase()).html();
                var fh = adminCountryFlagHtml(sortname);
                if (fh) {
                    return $jq('<span class="admin-country-s2-line"/>').html(fh + labelUp);
                }
                return $jq('<span/>').html(labelUp);
            },
            escapeMarkup: function(m) { return m; }
        });
        return true;
    }

    function adminDialFromSelect() {
        var cid = $('#admin_company_country_id').val();
        var $opt = cid ? $('#admin_company_country_id option[value="' + cid + '"]') : $();
        var pc = parseInt($opt.attr('data-phonecode'), 10);
        return isNaN(pc) ? 0 : pc;
    }

    function adminPhoneMaskFromSelect() {
        var cid = $('#admin_company_country_id').val();
        var $opt = cid ? $('#admin_company_country_id option[value="' + cid + '"]') : $();
        var maskPh = '';
        if ($opt.length) {
            maskPh = String($opt.attr('data-phonemask') || '').trim();
            if (!maskPh) {
                var pcMask = parseInt($opt.attr('data-phonecode'), 10);
                if (!isNaN(pcMask) && pcMask === 1) {
                    maskPh = '(###) ###-####';
                }
            }
        }
        return maskPh;
    }

    function adminDigitsOnly(s) {
        return String(s || '').replace(/\D/g, '');
    }

    function adminStripDialFromFull(full, dial) {
        full = String(full || '').trim();
        if (!dial) return adminDigitsOnly(full);
        var p = '+' + String(dial);
        if (full.indexOf(p) === 0) {
            return adminDigitsOnly(full.slice(p.length));
        }
        return adminDigitsOnly(full);
    }

    function adminFullFromLocal(localSel, dial) {
        var loc = adminDigitsOnly($(localSel).val());
        if (!dial) return loc;
        return '+' + String(dial) + loc;
    }

    window.adminSyncPhoneHiddens = function adminSyncPhoneHiddens() {
        var d = adminDialFromSelect();
        $('#phone_number').val(adminFullFromLocal('#phone_number_local', d));
        $('#cell_number').val(adminFullFromLocal('#cell_number_local', d));
    };

    function adminRemovePhoneInputMasks() {
        ['phone_number_local', 'cell_number_local'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el || typeof Inputmask === 'undefined') return;
            try { Inputmask.remove(el); } catch (e) {}
        });
    }

    window.adminUpdatePhoneInputMasks = function adminUpdatePhoneInputMasks() {
        if (typeof Inputmask === 'undefined') {
            return false;
        }
        var maskPh = adminPhoneMaskFromSelect();
        var phoneEl = document.getElementById('phone_number_local');
        var cellEl = document.getElementById('cell_number_local');
        if (!phoneEl || !cellEl) {
            return true;
        }

        var rawPhone = adminDigitsOnly(phoneEl.value);
        var rawCell = adminDigitsOnly(cellEl.value);

        try { Inputmask.remove(phoneEl); } catch (e) {}
        try { Inputmask.remove(cellEl); } catch (e) {}

        if (!maskPh) {
            phoneEl.value = rawPhone;
            cellEl.value = rawCell;
            return true;
        }

        var imMask = maskPh.replace(/#/g, '9');
        var opts = {
            mask: imMask,
            placeholder: '_',
            showMaskOnHover: false,
            autoUnmask: true,
            clearIncomplete: false
        };
        new Inputmask(opts).mask(phoneEl);
        new Inputmask(opts).mask(cellEl);

        if (phoneEl.inputmask && typeof phoneEl.inputmask.setValue === 'function') {
            phoneEl.inputmask.setValue(rawPhone);
        } else {
            $(phoneEl).val(rawPhone).trigger('input');
        }
        if (cellEl.inputmask && typeof cellEl.inputmask.setValue === 'function') {
            cellEl.inputmask.setValue(rawCell);
        } else {
            $(cellEl).val(rawCell).trigger('input');
        }
        return true;
    };

    function adminUpdatePhonePrefix() {
        var cid = $('#admin_company_country_id').val();
        var $opt = cid ? $('#admin_company_country_id option[value="' + cid + '"]') : $();
        if (!$opt.length) {
            $('#phone_country_prefix, #cell_country_prefix').html('<span class="text-muted small">—</span>').attr('title', '');
            return;
        }
        var sortname = String($opt.data('sortname') || '').toLowerCase();
        var pc = parseInt($opt.attr('data-phonecode'), 10);
        if (isNaN(pc)) pc = 0;
        var flag = adminCountryFlagHtml(sortname);
        var locked = '<span class="admin-dial-locked">+' + pc + '</span>';
        var html = flag + locked;
        $('#phone_country_prefix, #cell_country_prefix').html(html).attr('title', '+' + pc);
    }

    window.adminInitPhoneLocalsFromHidden = function adminInitPhoneLocalsFromHidden() {
        adminRemovePhoneInputMasks();
        var d = adminDialFromSelect();
        $('#phone_number_local').val(adminStripDialFromFull($('#phone_number').val(), d));
        $('#cell_number_local').val(adminStripDialFromFull($('#cell_number').val(), d));
    };

    function adminInitCompanyPhones() {
        adminUpdatePhonePrefix();
        adminInitPhoneLocalsFromHidden();
        if (!adminUpdatePhoneInputMasks()) {
            var attempts = 0;
            var timer = setInterval(function() {
                if (adminUpdatePhoneInputMasks() || ++attempts >= 40) {
                    clearInterval(timer);
                }
            }, 50);
        }
        adminSyncPhoneHiddens();
    }

    $('#admin_company_country_id').on('change', function() {
        adminUpdatePhonePrefix();
        adminInitPhoneLocalsFromHidden();
        adminUpdatePhoneInputMasks();
        adminSyncPhoneHiddens();
        if (typeof adminComposeFullAddress === 'function') {
            adminComposeFullAddress();
        }
    });

    $(document).on('select2:open', '#admin_company_country_id', function() {
        setTimeout(function() {
            var $search = $('.select2-container--open .select2-search__field');
            if ($search.length) {
                $search[0].focus();
            }
        }, 0);
    });

    $('#phone_number_local, #cell_number_local').on('input blur', function() {
        adminSyncPhoneHiddens();
    });

    $('form.forms-sample').on('submit', function() {
        if (typeof adminComposeFullAddress === 'function') {
            adminComposeFullAddress();
        }
        adminSyncPhoneHiddens();
    });

    $(function() {
        function bootAdminCompanyForm() {
            if (!adminInitCountrySelect2()) {
                return false;
            }
            adminInitCompanyPhones();
            return true;
        }

        if (!bootAdminCompanyForm()) {
            var ctryAttempts = 0;
            var ctryIv = setInterval(function() {
                if (bootAdminCompanyForm() || ++ctryAttempts >= 40) {
                    clearInterval(ctryIv);
                }
            }, 100);
        }
    });
})(jQuery);
</script>
<style>
#admin_company_country_select_wrap {
    position: relative;
    width: 100%;
    max-width: 100%;
}
#admin_company_country_select_wrap .select2-container {
    width: 100% !important;
    max-width: 100%;
    box-sizing: border-box;
}
#admin_company_country_select_wrap .select2-container .select2-selection--single {
    height: calc(2.25rem + 2px);
    min-height: calc(2.25rem + 2px);
    padding: 0;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    box-sizing: border-box;
}
#admin_company_country_select_wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(2.25rem + 2px) !important;
    padding-left: 0.75rem;
    padding-right: 1.75rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#admin_company_country_select_wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem + 2px);
    top: 0;
    right: 0.35rem;
}
#admin_company_country_select_wrap .select2-dropdown {
    width: 100% !important;
    max-width: 100%;
    box-sizing: border-box;
}
.admin-country-s2-flag {
    display: inline-block;
    width: 20px;
    height: 15px;
    object-fit: cover;
    vertical-align: middle;
    border: 1px solid #ddd;
    border-radius: 2px;
    margin-right: 8px;
    flex-shrink: 0;
}
.admin-country-s2-line {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
