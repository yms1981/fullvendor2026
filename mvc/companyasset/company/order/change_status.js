const options_status = document.querySelectorAll('.submit-change-status');
//console.log(options_status)

const change_statusbysumit = (e) => {
    e.preventDefault()
    const div_button = e.srcElement.parentElement

    const span_spinner_email =  div_button.querySelector('#icon-email-spinner')
    const button = div_button.querySelector('button.dropdown-item')
    
    if (span_spinner_email){
        span_spinner_email.hidden = false        
        button.disabled = true
    } 
    
    let formdata = new FormData(e.currentTarget);
    fetch(e.srcElement.action, {
        method: 'POST',
        body: formdata
    })
    .then(response => response.json())
    .then(data => {
        
        if (!data.success) {
            alert_func([data.msg, 'error'])
            return            
        }
        //window.history.go(-1)
        location.reload()
    })    
}
$('.dropdown-menu button').click(function (e) {
    e.stopPropagation();
});

if (options_status.length > 0) {
    for (const element of options_status) {        
        element.addEventListener('submit', change_statusbysumit);
    }
}

// Select de estado en grilla de órdenes (Bootstrap Select): al cambiar, enviar POST y recargar
(function() {
    function sendStatusChange(selectEl) {
        var url = selectEl.getAttribute('data-change-status-url');
        var orderId = selectEl.getAttribute('data-order-id');
        var codStatus = selectEl.value;
        if (!url || !orderId || codStatus === '' || codStatus === undefined) return;
        var formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('cod_status', codStatus);
        fetch(url, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.success === false) {
                    if (typeof alert_func === 'function') alert_func([data.message || data.msg || 'Error', 'error']);
                    else alert(data.message || data.msg || 'Error');
                } else {
                    location.reload();
                }
            })
            .catch(function() {
                if (typeof alert_func === 'function') alert_func(['Error al cambiar estado', 'error']);
                else alert('Error al cambiar estado');
            });
    }

    function applyStatusRules(sel) {
        var currentStatus = parseInt(sel.getAttribute('data-current-status') || '0', 10);
        // Estado 9 o 10: desactivar combo completamente
        if (currentStatus === 9 || currentStatus === 10) {
            sel.disabled = true;
            return;
        }
        // Estado 11: solo permitir 7 y 14
        if (currentStatus === 11) {
            var allowedStatuses = [7, 14];
            Array.from(sel.options).forEach(function(opt) {
                var optVal = parseInt(opt.value, 10);
                if (opt.selected) return;
                opt.disabled = allowedStatuses.indexOf(optVal) === -1;
            });
        }
    }

    function paintOrderStatusSelect(sel) {
        var opt = sel.options[sel.selectedIndex];
        var color = opt ? (opt.getAttribute('data-color') || '#9ca3af') : '#9ca3af';
        sel.style.setProperty('--fv-status-color', color);
        var picker = sel.closest('.bootstrap-select');
        if (picker) picker.style.setProperty('--fv-status-color', color);
    }

    function initOrderStatusSelect() {
        if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.selectpicker) {
            return false;
        }
        document.querySelectorAll('select.order-status-select').forEach(function(sel) {
            if (sel.dataset.inited) return;
            sel.dataset.inited = '1';
            applyStatusRules(sel);
            var $sel = jQuery(sel);
            if ($sel.parent().hasClass('bootstrap-select')) {
                $sel.selectpicker('destroy');
            }
            $sel.selectpicker({
                container: 'body',
                width: 'fit',
                size: 10,
                showContent: true,
                dropupAuto: true
            });
            var picker = $sel.data('selectpicker');
            if (picker && picker.$menu) {
                picker.$menu.addClass('fv-order-status-menu');
            }
            paintOrderStatusSelect(sel);
            if (sel.disabled) {
                var wrap = sel.closest('.fv-order-status-select-wrap');
                if (wrap) wrap.classList.add('is-disabled');
                var box = sel.closest('.bootstrap-select');
                if (box) box.classList.add('disabled');
            }
            sel.addEventListener('change', function() {
                paintOrderStatusSelect(sel);
                sendStatusChange(sel);
            });
        });
        return true;
    }

    var statusSelectTries = 0;
    function scheduleOrderStatusSelect() {
        if (initOrderStatusSelect()) return;
        statusSelectTries += 1;
        if (statusSelectTries < 40) {
            setTimeout(scheduleOrderStatusSelect, 80);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleOrderStatusSelect);
    } else {
        scheduleOrderStatusSelect();
    }
})();



