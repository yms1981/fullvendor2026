function fvCartLang() {
    if (window.fvOpenCartLang && typeof window.fvOpenCartLang === 'object') {
        return window.fvOpenCartLang;
    }
    var block = (typeof options !== 'undefined' && options.lang)
        ? (options.lang.cmp_cart || options.lang['cmp-cart'] || {})
        : {};
    return block.lang || block || {};
}

const form_submit = document.getElementById('form_send');
const btn_notify = document.getElementById('btn-send-email');
const btn_deletecart = document.getElementsByClassName('btn-delete-cart');

(function () {
    function cartLang() {
        return typeof fvCartLang === 'function' ? fvCartLang() : {};
    }

    function hideAppLoader() {
        try {
            if (typeof AppLoader !== 'undefined' && AppLoader && typeof AppLoader.hide === 'function') {
                AppLoader.hide();
            }
        } catch (e) {}
        // Por si el loader de navegación quedó programado con delay
        setTimeout(function () {
            try {
                if (typeof AppLoader !== 'undefined' && AppLoader && typeof AppLoader.hide === 'function') {
                    AppLoader.hide();
                }
            } catch (e2) {}
        }, 80);
    }

    function showMsg(title, icon) {
        var lang = cartLang();
        var okText = lang.ok || lang.yes || 'OK';
        hideAppLoader();
        if (typeof swal === 'function') {
            swal({
                title: title || '',
                icon: icon || 'error',
                button: {
                    text: okText,
                    value: true,
                    visible: true,
                    className: icon === 'success' ? 'btn btn-success' : 'btn btn-danger'
                }
            });
            return;
        }
        if (typeof alert_func === 'function') {
            alert_func([title || '', icon || 'error']);
            return;
        }
        window.alert(title || '');
    }

    function setLoading(btn, loading) {
        if (!btn) {
            return;
        }
        if (loading) {
            if (!btn.getAttribute('data-original-html')) {
                btn.setAttribute('data-original-html', btn.innerHTML);
            }
            var loaderText = btn.getAttribute('data-loader') || '';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> '
                + (loaderText ? (' ' + loaderText) : '');
            btn.classList.add('disabled');
            btn.setAttribute('aria-disabled', 'true');
            return;
        }
        var original = btn.getAttribute('data-original-html');
        if (original) {
            btn.innerHTML = original;
            btn.removeAttribute('data-original-html');
        }
        btn.classList.remove('disabled');
        btn.removeAttribute('aria-disabled');
        hideAppLoader();
    }

    function resolveSubmitMessage(data) {
        var lang = cartLang();
        var key = '';
        if (data && typeof data === 'object') {
            key = String(data.msg || data.error || '');
        }
        if (key && lang[key]) {
            return lang[key];
        }
        if (data && data.message && String(data.message).trim() !== '') {
            return String(data.message);
        }
        return lang.submit_failed || key || '';
    }

    function confirmSubmit(btn) {
        var lang = cartLang();
        var url = btn.getAttribute('href');
        if (!url) {
            hideAppLoader();
            return;
        }

        var hasItems = btn.getAttribute('data-has-items');
        if (hasItems === '0' || hasItems === 'false') {
            hideAppLoader();
            showMsg(lang.empty_cart || '', 'warning');
            return;
        }

        var ask = function () {
            return new Promise(function (resolve) {
                if (typeof swal !== 'function') {
                    resolve(true);
                    return;
                }
                swal({
                    title: lang.question_confirm_order || lang.submit_order || '',
                    icon: 'warning',
                    buttons: {
                        cancel: {
                            text: lang.cancel || '',
                            value: false,
                            visible: true,
                            className: 'sweet-danager',
                            closeModal: true
                        },
                        confirm: {
                            text: lang.yes || lang.submit_order || '',
                            value: true,
                            visible: true,
                            className: 'sweet-warning',
                            closeModal: true
                        }
                    }
                }).then(function (ok) {
                    resolve(!!ok);
                });
            });
        };

        hideAppLoader();
        ask().then(function (ok) {
            if (!ok) {
                hideAppLoader();
                return;
            }
            setLoading(btn, true);
            hideAppLoader();
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return { success: false, msg: 'submit_failed' };
                    });
                })
                .then(function (data) {
                    hideAppLoader();
                    if (!data || !data.success) {
                        setLoading(btn, false);
                        showMsg(resolveSubmitMessage(data), 'error');
                        return;
                    }
                    var redirect = data.redirect || btn.getAttribute('data-redirect') || '';
                    var okMsg = resolveSubmitMessage(data) || lang.orden_created || '';
                    setLoading(btn, false);
                    if (typeof swal === 'function') {
                        swal({
                            title: okMsg,
                            icon: 'success',
                            button: {
                                text: lang.ok || lang.yes || 'OK',
                                value: true,
                                visible: true,
                                className: 'btn btn-success'
                            }
                        }).then(function () {
                            window.location.href = redirect || url.replace(/convert_carttoorder\/\d+.*/, 'abandoned');
                        });
                        return;
                    }
                    if (redirect) {
                        window.location.href = redirect;
                        return;
                    }
                    window.location.reload();
                })
                .catch(function () {
                    setLoading(btn, false);
                    hideAppLoader();
                    showMsg(lang.submit_failed || '', 'error');
                });
        });
    }

    // Capture phase: evitar que navigation-loader active el loader a pantalla completa
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest
            ? e.target.closest('#btn_submit_order, .btn-submit-cart-order')
            : null;
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
            e.stopImmediatePropagation();
        }
        hideAppLoader();
        if (btn.classList.contains('disabled') || btn.getAttribute('aria-disabled') === 'true') {
            return;
        }
        confirmSubmit(btn);
    }, true);
})();
