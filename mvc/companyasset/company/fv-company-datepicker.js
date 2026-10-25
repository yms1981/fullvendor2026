/**
 * Datepicker global del panel company (jQuery UI).
 * Usa window.fvCompanyDateFormatJs (mismo formato del perfil de empresa).
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    function phpToJqueryUi(phpFormat) {
        var php = String(phpFormat || 'm/d/Y');
        var out = '';
        for (var i = 0; i < php.length; i++) {
            if (php.charAt(i) === '\\' && i + 1 < php.length) {
                out += php.charAt(i + 1);
                i += 1;
                continue;
            }
            switch (php.charAt(i)) {
                case 'Y': out += 'yy'; break;
                case 'y': out += 'y'; break;
                case 'm': out += 'mm'; break;
                case 'n': out += 'm'; break;
                case 'd': out += 'dd'; break;
                case 'j': out += 'd'; break;
                case 'M': out += 'M'; break;
                default: out += php.charAt(i);
            }
        }
        return out || 'mm/dd/yy';
    }

    function resolveJqueryUiFormat() {
        // Preferir format_php del perfil (misma fuente que listados) y luego format_js.
        if (window.fvCompanyDateFormatPhp) {
            return phpToJqueryUi(window.fvCompanyDateFormatPhp);
        }
        if (window.fvCompanyDateFormatJs) {
            return String(window.fvCompanyDateFormatJs);
        }
        return 'mm/dd/yy';
    }

    function isoFromDate(d) {
        if (!(d instanceof Date) || isNaN(d.getTime())) {
            return '';
        }
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return y + '-' + m + '-' + day;
    }

    function dateFromIso(iso) {
        if (!iso) {
            return null;
        }
        var parts = String(iso).split('-');
        if (parts.length !== 3) {
            return null;
        }
        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        return isNaN(d.getTime()) ? null : d;
    }

    function resolveHidden($input) {
        var target = $input.attr('data-fv-iso-target');
        if (!target) {
            var id = $input.attr('id') || '';
            if (id.indexOf('_display') > -1) {
                var base = id.replace(/_display$/, '');
                if ($('#' + base + '_iso').length) {
                    target = '#' + base + '_iso';
                } else if ($('#' + base).length) {
                    target = '#' + base;
                }
            } else if (id && $('#' + id + '_iso').length) {
                target = '#' + id + '_iso';
            }
        }
        return target ? $(target) : $();
    }

    function syncHidden($input, date) {
        var $hidden = resolveHidden($input);
        if ($hidden.length) {
            $hidden.val(date ? isoFromDate(date) : '');
        }
    }

    function triggerFilterIfNeeded($input) {
        if (typeof window.buscar_porfiltro === 'function') {
            window.buscar_porfiltro();
            return;
        }
        var $form = $input.closest('form');
        var autoSubmit = $input.attr('data-fv-autosubmit') || $input.data('fvAutosubmit');
        if ($form.length && ($form.attr('id') === 'form-buscar-filtro' || autoSubmit)) {
            $form.trigger('submit');
        } else {
            $input.trigger('change');
        }
    }

    function initOne(el) {
        var $input = $(el);
        if (!$input.length || $input.data('fvCompanyDatepickerReady')) {
            return;
        }
        // Exige API jQuery UI (getDate / dateFormat)
        if (typeof $.fn.datepicker !== 'function' || !$.datepicker || typeof $.datepicker.formatDate !== 'function') {
            return;
        }

        var format = resolveJqueryUiFormat();
        try {
            $input.datepicker('destroy');
        } catch (e) { /* ignore */ }

        var options = {
            dateFormat: format,
            changeMonth: true,
            changeYear: true,
            yearRange: 'c-20:c+10',
            onSelect: function () {
                var selected = $(this).datepicker('getDate');
                syncHidden($input, selected);
                triggerFilterIfNeeded($input);
            }
        };
        var maxIso = $input.attr('data-fv-max') || '';
        var maxDate = dateFromIso(maxIso);
        if (maxDate) {
            options.maxDate = maxDate;
        }

        $input.datepicker(options);

        var $hidden = resolveHidden($input);
        if ($hidden.length) {
            var d = dateFromIso($hidden.val());
            if (d) {
                $input.datepicker('setDate', d);
            }
        }

        $input.data('fvCompanyDatepickerReady', true);
    }

    function upgradeNativeDateInputs(root) {
        $(root).find('input[type="date"]').each(function () {
            var $native = $(this);
            if ($native.data('fvUpgraded') || $native.hasClass('fv-skip-company-datepicker')) {
                return;
            }
            var name = $native.attr('name') || '';
            var iso = $native.val() || '';
            var id = $native.attr('id') || (name
                ? name.replace(/[^a-zA-Z0-9_\-]/g, '_')
                : ('fv_date_' + Math.random().toString(36).slice(2, 8)));
            var displayId = id + '_display';
            var hiddenId = id + '_iso';
            var classes = (($native.attr('class') || 'form-control') + ' fv-company-datepicker').replace(/\s+/g, ' ').trim();
            var max = $native.attr('max') || '';
            var $display = $('<input type="text" autocomplete="off" readonly />')
                .attr('id', displayId)
                .attr('class', classes)
                .attr('data-fv-iso-target', '#' + hiddenId);
            if ($native.attr('tabindex')) {
                $display.attr('tabindex', $native.attr('tabindex'));
            }
            if (max) {
                $display.attr('data-fv-max', max);
            }
            var $hidden = $('<input type="hidden" />')
                .attr('id', hiddenId)
                .attr('name', name)
                .val(iso);
            $native.replaceWith($display);
            $display.after($hidden);
            $display.data('fvUpgraded', true);
            initOne($display.get(0));
            if (max) {
                var maxDate = dateFromIso(max);
                if (maxDate) {
                    $display.datepicker('option', 'maxDate', maxDate);
                }
            }
        });
    }

    function initAll(root) {
        root = root || document;
        if (typeof $.fn.datepicker !== 'function' || !$.datepicker) {
            return false;
        }
        $(root).find('.fv-company-datepicker').each(function () {
            initOne(this);
        });
        upgradeNativeDateInputs(root);
        return true;
    }

    function boot() {
        var tries = 0;
        var timer = setInterval(function () {
            tries += 1;
            if (initAll(document)) {
                clearInterval(timer);
            } else if (tries > 60) {
                clearInterval(timer);
            }
        }, 100);
    }

    window.fvInitCompanyDatepickers = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, window.jQuery);
