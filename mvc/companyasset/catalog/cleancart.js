window.cleanCart = async function cleanCart(ev) {
    let el = (ev && (ev.currentTarget || ev.target)) ? (ev.currentTarget || ev.target) : null;
    if (!el || !el.getAttribute || !el.getAttribute('data-href')) {
        var fallback = document.querySelector('a[data-href*="vaciarCarrito"], a[data-redirect]');
        if (fallback) el = fallback;
    }
    const fetchUrl = (el && el.getAttribute && el.getAttribute('data-href')) ? el.getAttribute('data-href') : (typeof url_base !== 'undefined' && typeof token !== 'undefined' ? url_base + 'catalogo/producto/vaciarCarrito?token=' + encodeURIComponent(token) : null);
    const redirectUrl = (el && el.getAttribute && el.getAttribute('data-redirect')) ? el.getAttribute('data-redirect') : (typeof url_base !== 'undefined' && typeof token !== 'undefined' ? url_base + 'catalogo/producto?token=' + encodeURIComponent(token) : null);

    const confirmText = (typeof cleanCartConfirmText !== 'undefined') ? cleanCartConfirmText : 'Clear cart?';
    const confirmBtn = (typeof cleanCartYes !== 'undefined') ? cleanCartYes : 'Yes';
    const cancelBtn = (typeof cleanCartNo !== 'undefined') ? cleanCartNo : 'No';
    const errUrlMsg = (typeof cleanCartErrorUrl !== 'undefined') ? cleanCartErrorUrl : 'Could not get URL to clear cart.';
    const errDefaultMsg = (typeof cleanCartErrorDefault !== 'undefined') ? cleanCartErrorDefault : 'Error clearing cart';
    Swal.fire({
        text: confirmText,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28A745",
        cancelButtonColor: "#d33",
        confirmButtonText: confirmBtn,
        cancelButtonText: cancelBtn,
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger"
        },
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        if (!fetchUrl) {
            if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', errUrlMsg, 'error');
            else if (typeof Swal !== 'undefined') Swal.fire('', errUrlMsg, 'error');
            return;
        }
        try {
            const response = await fetch(fetchUrl, { method: 'GET' });
            const contentType = response.headers.get('content-type');
            let data = {};
            if (contentType && contentType.indexOf('application/json') !== -1) {
                data = await response.json();
            } else {
                const text = await response.text();
                try { data = JSON.parse(text); } catch (e) { data = { success: false, message: text || errDefaultMsg }; }
            }
            if (data.success) {
                if (redirectUrl) window.location.href = redirectUrl;
                else location.reload();
            } else {
                const msg = data.message || data.msg || errDefaultMsg;
                if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', msg, 'error');
                else if (typeof Swal !== 'undefined') Swal.fire('', msg, 'error');
            }
        } catch (err) {
            const msg = err.message || errDefaultMsg;
            if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', msg, 'error');
            else if (typeof Swal !== 'undefined') Swal.fire('', msg, 'error');
        }
    });
};
