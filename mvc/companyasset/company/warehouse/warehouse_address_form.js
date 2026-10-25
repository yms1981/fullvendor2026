/**
 * País (Select2 + banderas + búsqueda) y número de calle con prefijo/máscara alineados a customer_add.
 * Requiere: jQuery, Select2, Inputmask (footer company). Config: window.WAREHOUSE_ADDRESS_FORM
 */
(function ($) {
    'use strict';

    function cfg() {
        return window.WAREHOUSE_ADDRESS_FORM || {};
    }

    function countryFlagsBaseUrl() {
        var u = cfg().countryFlagsBaseUrl || '';
        return u && u[u.length - 1] !== '/' ? u + '/' : u;
    }

    function countryFlagHtml(sortname) {
        var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
        if (sn.length < 2) {
            return '';
        }
        var base = countryFlagsBaseUrl();
        var src = base + sn + '.png';
        return '<img src="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="" style="display:inline-block;width:20px;height:15px;object-fit:cover;vertical-align:middle;border:1px solid #ddd;border-radius:2px;margin-right:8px" onerror="this.style.display=\'none\'" />';
    }

    function updateStreetNumberPrefix() {
        var $p = $('#warehouse_street_number_prefix');
        if (!$p.length) {
            return;
        }
        var cid = $('#country_id').val();
        var def = cfg().companyDefaultCountryId;
        if (!cid && def) {
            cid = String(def);
        }
        var $opt = cid ? $('#country_id option[value="' + cid + '"]') : $();
        if (!$opt.length) {
            $p.html('<span class="text-muted small">—</span>');
            return;
        }
        var sortname = String($opt.data('sortname') || '').toLowerCase();
        var pc = parseInt($opt.data('phonecode'), 10);
        if (isNaN(pc)) {
            pc = 0;
        }
        var flag = countryFlagHtml(sortname);
        $p.html(flag + '<span class="ml-1 font-weight-bold">+' + pc + '</span>');
    }

    function updateStreetNumberMask() {
        if (typeof Inputmask === 'undefined') {
            return false;
        }
        var el = document.getElementById('warehouse_street_number');
        if (!el) {
            return true;
        }
        var cid = $('#country_id').val();
        var def = cfg().companyDefaultCountryId;
        if (!cid && def) {
            cid = String(def);
        }
        var $opt = cid ? $('#country_id option[value="' + cid + '"]') : $();
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
        var raw = (el.value || '').replace(/\D/g, '');
        try {
            Inputmask.remove(el);
        } catch (e) {}

        if (!maskPh) {
            el.value = raw;
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
        new Inputmask(opts).mask(el);
        if (el.inputmask && typeof el.inputmask.setValue === 'function') {
            el.inputmask.setValue(raw);
        } else {
            $(el).val(raw).trigger('input');
        }
        return true;
    }

    function tryInitStreetNumberMask() {
        if (updateStreetNumberMask()) {
            return;
        }
        var n = 0;
        var iv = setInterval(function () {
            if (updateStreetNumberMask() || ++n >= 60) {
                clearInterval(iv);
            }
        }, 100);
    }

    function initCountrySelect() {
        var $el = $('#country_id');
        if (!$el.length || typeof $.fn.select2 === 'undefined') {
            return false;
        }
        var C = cfg();
        var phCountry = C.phCountry || '';
        var selPlaceholder = C.selPlaceholder || '';

        try {
            if ($el.hasClass('select2-hidden-accessible') || $el.data('select2')) {
                $el.select2('destroy');
            }
        } catch (e) {}

        $el.select2({
            placeholder: phCountry || selPlaceholder,
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $(document.body),
            matcher: function (params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (typeof data.text === 'undefined' || data.children) {
                    return null;
                }
                var term = params.term.toLowerCase();
                var raw = typeof data.text === 'string' ? data.text : '';
                var txt = $('<div/>').html(raw).text().toLowerCase();
                var el = data.element;
                var sortname = el ? String($(el).data('sortname') || '').toLowerCase() : '';
                if (txt.indexOf(term) > -1 || sortname.indexOf(term) > -1) {
                    return data;
                }
                return null;
            },
            templateResult: function (country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                    return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
            },
            templateSelection: function (country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                    return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
            },
            escapeMarkup: function (m) {
                return m;
            }
        });
        return true;
    }

    function bindCountryChange() {
        var lastCountryId = null;
        $('#country_id').on('change.warehouseAddr', function () {
            var v = $(this).val() || '';
            if (lastCountryId !== null && lastCountryId !== v) {
                $('#state_name').val('');
                $('#state_id').val('');
                $('#city_name').val('');
                $('#city_id').val('');
                $('#state_suggestions, #city_suggestions').empty();
            }
            lastCountryId = v;
            updateStreetNumberPrefix();
            updateStreetNumberMask();
        });
        lastCountryId = $('#country_id').val() || '';
    }

    function focusSelect2Search() {
        setTimeout(function () {
            var $f = $('.select2-container--open .select2-search__field');
            if ($f.length) {
                $f[0].focus();
            }
        }, 0);
    }

    function boot() {
        if (typeof $ === 'undefined') {
            return;
        }
        $(document).on('select2:open', '#country_id', focusSelect2Search);

        function finishInit() {
            bindCountryChange();
            afterReady();
        }

        if (initCountrySelect()) {
            finishInit();
        } else {
            var n = 0;
            var iv = setInterval(function () {
                if (initCountrySelect() || ++n >= 60) {
                    clearInterval(iv);
                    finishInit();
                }
            }, 100);
        }
    }

    function afterReady() {
        var def = cfg().companyDefaultCountryId;
        var $country = $('#country_id');
        if (def && $country.length && !$country.val()) {
            $country.val(String(def)).trigger('change');
        }
        updateStreetNumberPrefix();
        tryInitStreetNumberMask();
        setTimeout(function () {
            updateStreetNumberPrefix();
            tryInitStreetNumberMask();
        }, 0);
    }

    $(boot);
})(jQuery);
