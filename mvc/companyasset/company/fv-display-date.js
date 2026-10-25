(function (window) {
    'use strict';

    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function parseDate(value) {
        if (!value) {
            return null;
        }
        var d = value instanceof Date ? value : new Date(value);
        return isNaN(d.getTime()) ? null : d;
    }

    /**
     * Aplica un format_php de PHP (m/d/Y, d/m/Y, Y-m-d, d M Y, etc.) a un Date.
     */
    function formatWithPhpPattern(d, phpFormat) {
        var map = {
            Y: String(d.getFullYear()),
            y: String(d.getFullYear()).slice(-2),
            m: pad2(d.getMonth() + 1),
            n: String(d.getMonth() + 1),
            d: pad2(d.getDate()),
            j: String(d.getDate()),
            M: MONTHS[d.getMonth()],
            H: pad2(d.getHours()),
            i: pad2(d.getMinutes()),
            s: pad2(d.getSeconds())
        };

        var out = '';
        var i = 0;
        var fmt = String(phpFormat || 'm/d/Y');
        while (i < fmt.length) {
            // Escape PHP: \X → literal X
            if (fmt.charAt(i) === '\\' && i + 1 < fmt.length) {
                out += fmt.charAt(i + 1);
                i += 2;
                continue;
            }
            var ch = fmt.charAt(i);
            if (Object.prototype.hasOwnProperty.call(map, ch)) {
                out += map[ch];
            } else {
                out += ch;
            }
            i += 1;
        }
        return out;
    }

    function resolvePhpFormat() {
        if (window.fvCompanyDateFormatPhp) {
            return String(window.fvCompanyDateFormatPhp);
        }
        return 'm/d/Y';
    }

    window.fvFormatDisplayDate = function (value) {
        var d = parseDate(value);
        if (!d) {
            return value ? String(value) : '';
        }
        return formatWithPhpPattern(d, resolvePhpFormat());
    };

    window.fvFormatDisplayDateTime = function (value) {
        var d = parseDate(value);
        if (!d) {
            return value ? String(value) : '';
        }
        return window.fvFormatDisplayDate(d) + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
    };
})(window);
