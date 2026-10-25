(function () {
    const new_btn_notify = document.querySelector('#btn_notify');
    const input_confirm = document.querySelector('#modal-notify input[name="confirm"]');
    const modal_notify = $('#modal-notify');

    if (!new_btn_notify || !modal_notify.length) {
        return;
    }

    new_btn_notify.addEventListener('click', function () {
        const lang = typeof fvCartLang === 'function' ? fvCartLang() : {};
        if (input_confirm) {
            swal({
                title: lang.link_expired || '',
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
            }).then(function (willContinue) {
                if (willContinue) {
                    modal_notify.modal('show');
                }
            });
        } else {
            modal_notify.modal('show');
        }
    });
})();
