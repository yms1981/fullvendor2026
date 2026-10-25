const submit_send = (e, formdata_updated) => {
    e.preventDefault();
    if (!form_submit || !btn_notify) {
        return;
    }

    const spinner = btn_notify.querySelector('.spinner-border');
    if (spinner) {
        spinner.classList.remove('d-none');
    }
    btn_notify.disabled = true;
    let formdata = formdata_updated ? formdata_updated : new FormData(form_submit);
    const lang = typeof fvCartLang === 'function' ? fvCartLang() : {};

    fetch(form_submit.getAttribute('action') || e.srcElement.action, {
        method: 'POST',
        body: formdata
    })
        .then(response => response.json())
        .then(data => {
            btn_notify.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }

            if (!data.success) {
                if (data.question_confirm) {
                    return alert_confirm(e, formdata, data.msg);
                }
                const errMsg = data.msg || lang.notify_error || '';
                if (typeof alert_func === 'function') {
                    alert_func([errMsg, 'error']);
                } else if (typeof swal === 'function') {
                    swal({ title: errMsg, icon: 'error' });
                }
                return;
            }

            const okMsg = data.msg || lang.notify_sent || '';
            const afterOk = function () {
                const modal = $('#modal-notify');
                if (modal.length) {
                    modal.modal('hide');
                }
                window.history.go(-1);
            };

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
                }).then(afterOk);
                return;
            }

            if (typeof alert_func === 'function') {
                alert_func([okMsg, 'success']);
            }
            afterOk();
        })
        .catch(function () {
            btn_notify.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            const errMsg = lang.notify_error || '';
            if (typeof alert_func === 'function') {
                alert_func([errMsg, 'error']);
            } else if (typeof swal === 'function') {
                swal({ title: errMsg, icon: 'error' });
            }
        });
};

const alert_confirm = (e, formdata, msg) => {
    const lang = typeof fvCartLang === 'function' ? fvCartLang() : {};
    swal({
        title: msg,
        icon: 'warning',
        buttons: {
            confirm: {
                text: lang.yes || '',
                value: true,
                visible: true,
                className: 'sweet-warning',
                closeModal: true
            },
            cancel: {
                text: lang.cancel || '',
                value: false,
                visible: true,
                className: 'sweet-danager',
                closeModal: true,
            }
        },
    }).then((willDelete) => {
        if (willDelete) {
            formdata.append('confirm', 1);
            submit_send(e, formdata);
        }
    });
};

if (form_submit) {
    form_submit.addEventListener('submit', submit_send);
}
