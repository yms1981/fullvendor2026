(function () {
    function cartLang() {
        if (typeof fvCartLang === 'function') {
            return fvCartLang();
        }
        if (window.fvOpenCartLang && typeof window.fvOpenCartLang === 'object') {
            return window.fvOpenCartLang;
        }
        var block = (typeof options !== 'undefined' && options.lang)
            ? (options.lang.cmp_cart || options.lang['cmp-cart'] || {})
            : {};
        return block.lang || block || {};
    }

    function hideAppLoader() {
        try {
            if (typeof AppLoader !== 'undefined' && AppLoader && typeof AppLoader.hide === 'function') {
                AppLoader.hide();
            }
        } catch (e) {}
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

    function askConfirm(lang) {
        return new Promise(function (resolve) {
            if (typeof swal === 'function') {
                swal({
                    title: lang.question_delete || '',
                    icon: 'warning',
                    buttons: {
                        cancel: {
                            text: lang.cancel || '',
                            value: false,
                            visible: true,
                            className: 'sweet-danager',
                            closeModal: true,
                        },
                        confirm: {
                            text: lang.yes || '',
                            value: true,
                            visible: true,
                            className: 'sweet-warning',
                            closeModal: true
                        },
                    },
                }).then(function (willDelete) {
                    resolve(!!willDelete);
                });
                return;
            }
            resolve(window.confirm(lang.question_delete || ''));
        });
    }

    function deleteCart(btn) {
        var url = btn.getAttribute('data-url');
        var redirect = btn.getAttribute('data-redirect');
        var lang = cartLang();

        if (!url) {
            showMsg(lang.delete_error || lang.not_deleted || '', 'error');
            return;
        }

        askConfirm(lang).then(function (willDelete) {
            if (!willDelete) {
                hideAppLoader();
                return;
            }

            btn.disabled = true;
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
                        return { success: false, msg: 'delete_error' };
                    });
                })
                .then(function (data) {
                    btn.disabled = false;
                    hideAppLoader();

                    if (!data || !data.success) {
                        var errKey = String((data && (data.msg || data.error)) || 'delete_error');
                        var errMsg = (lang[errKey] || (data && data.message) || lang.delete_error || lang.not_deleted || errKey);
                        showMsg(errMsg, 'error');
                        return;
                    }

                    var okMsg = lang.cart_deleted || (data.msg && lang[data.msg]) || data.msg || '';
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
                            if (redirect) {
                                window.location.href = redirect;
                                return;
                            }
                            location.reload();
                        });
                        return;
                    }

                    if (redirect) {
                        window.location.href = redirect;
                        return;
                    }
                    location.reload();
                })
                .catch(function () {
                    btn.disabled = false;
                    hideAppLoader();
                    showMsg(lang.delete_error || lang.not_deleted || '', 'error');
                });
        });
    }

    // Capture: no dejar que otros handlers (confirm orden / notify) intercepten el eliminar
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest
            ? e.target.closest('.btn-delete-cart, [data-action="delete-cart"]')
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
        if (btn.disabled || btn.classList.contains('disabled')) {
            return;
        }
        deleteCart(btn);
    }, true);
})();
