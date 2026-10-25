window.fvApplyUnitQtyMask = function () {
    if (typeof Inputmask === 'undefined' || typeof _radixPoint === 'undefined') {
        return false;
    }
    var select = document.querySelector('select[name="unit_type"]');
    var digits = 0;
    if (select && select.selectedIndex >= 0) {
        var opt = select.options[select.selectedIndex];
        digits = parseInt(opt.getAttribute('data-decimals') || '0', 10);
        if (isNaN(digits) || digits < 0) {
            digits = 0;
        }
        if (digits > 9) {
            digits = 9;
        }
    }
    document.querySelectorAll('.fv-unit-qty').forEach(function (el) {
        if (el.inputmask) {
            el.inputmask.remove();
        }
        Inputmask('currency', {
            radixPoint: _radixPoint,
            groupSeparator: (typeof _groupSeparator !== 'undefined') ? _groupSeparator : '',
            digits: digits,
            digitsOptional: false,
            allowMinus: false
        }).mask(el);
    });
    return true;
};
if (!window.fvUnitQtyMaskBound) {
    window.fvUnitQtyMaskBound = true;
    $(document).on('change', 'select[name="unit_type"]', function () {
        window.fvApplyUnitQtyMask();
    });
}
