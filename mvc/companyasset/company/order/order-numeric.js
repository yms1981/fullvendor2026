(function (window) {
    'use strict';

    /**
     * Alinea con PHP fv_resolve_numeric_group_separator:
     * group vacío (format_id 4) no debe caer en '.' cuando el decimal también es '.'.
     */
    function resolveGroupSeparator(fmt) {
        var dec = (fmt && fmt.radixPoint) ? String(fmt.radixPoint) : ',';
        var sep = (fmt && fmt.groupSeparator != null) ? String(fmt.groupSeparator) : '';
        if (sep === '') {
            sep = (dec === ',') ? '.' : ',';
        }
        if (sep === dec) {
            sep = (dec === ',') ? '.' : ',';
        }
        return sep;
    }

    function getFmt(override) {
        var base = override || window.FORMAT_NUMERIC_ORDER || window.FORMAT_NUMERIC || {
            digits: 2,
            radixPoint: ',',
            groupSeparator: '.'
        };
        return {
            digits: base.digits != null ? base.digits : 2,
            radixPoint: base.radixPoint || ',',
            groupSeparator: resolveGroupSeparator(base)
        };
    }

    function escRegExp(str) {
        return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Convierte un valor localizado (ej. "1.234,56" o "200.00") a número.
     */
    function fvParseLocaleNumber(val, fmtOverride) {
        var fmt = getFmt(fmtOverride);
        if (val == null || val === '') {
            return 0;
        }

        var raw = String(val).trim();
        if (!raw) {
            return 0;
        }

        var dec = fmt.radixPoint || ',';
        var sep = fmt.groupSeparator;
        var clean = raw.replace(/[^\d.,\-]/g, '');

        if (clean === '' || clean === '-') {
            return 0;
        }

        // Entrada alternativa: "200.00" cuando el locale usa coma decimal.
        if (dec === ',' && /^\d+\.\d{1,2}$/.test(clean)) {
            return parseFloat(clean) || 0;
        }
        // Entrada alternativa: "200,00" cuando el locale usa punto decimal.
        if (dec === '.' && /^\d+,\d{1,2}$/.test(clean)) {
            return parseFloat(clean.replace(',', '.')) || 0;
        }

        var s = clean;
        if (sep && sep !== dec) {
            s = s.replace(new RegExp(escRegExp(sep), 'g'), '');
        }
        if (dec && dec !== '.') {
            s = s.replace(new RegExp(escRegExp(dec)), '.');
        }
        return parseFloat(s) || 0;
    }

    function fvFormatLocaleNumber(num, fmtOverride) {
        var fmt = getFmt(fmtOverride);
        var digits = fmt.digits != null ? fmt.digits : 2;
        var dec = fmt.radixPoint || ',';
        var sep = fmt.groupSeparator;
        var n = Number(num);
        if (!isFinite(n)) {
            n = 0;
        }
        var parts = n.toFixed(digits).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
        return parts.join(dec);
    }

    window.fvResolveNumericGroupSeparator = resolveGroupSeparator;
    window.fvParseLocaleNumber = fvParseLocaleNumber;
    window.fvFormatLocaleNumber = fvFormatLocaleNumber;

    if (window.jQuery) {
        window.jQuery(document).on('blur', '.input-price, #sale_price_add, #input_discount', function () {
            var $el = window.jQuery(this);
            var raw = $el.val();
            if (raw == null || String(raw).trim() === '') {
                return;
            }
            var parsed = fvParseLocaleNumber(raw);
            $el.val(fvFormatLocaleNumber(parsed));
        });
    }
})(window);
