$(function () {
    $(document).on('submit', '#form_add', function (e) {
        e.preventDefault()
        // const lang = options.lang.cmp_terms.lang;

        const form = document.getElementById('form_add');
        const url = form.getAttribute('action');
        const formData = new FormData(form);
        formData.set('default', form.querySelector('[name="checkBoxDefault"]').checked ? 1 : 0);

        if (formData.get('default') == 1 && defaultEntity) {
            swal({
                title: 'Ya existe una entidad por defecto',//lang.already_exists,
                text: 'Desea cambiar?',//lang.desea_cambiar,
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: 'Cancelar',//lang.cancel,
                        value: false,
                        visible: true,
                        className: "sweet-danager",
                        closeModal: true,
                    },
                    confirm: {
                        text: 'Cambiar',//lang.change,
                        value: true,
                        visible: true,
                        className: "sweet-warning",
                        closeModal: true
                    },
                },
            }).then((confirmed) => {
                if (confirmed) {
                    submitForm(url, formData);
                }
            });
        } else {
            submitForm(url, formData);
        }
    });

    function submitForm(url, formData) {
        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Creado con exito!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    window.history.go(-1);
                });
            },
            error: function(response) {
                let title = response.responseJSON?.message ?? 'Ocurrió un error';
                const errors = response.responseJSON.errors;

                const errorsMessages = Object.values(errors || {})
                    .reduce((acc, curr) => acc.concat(curr), [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });
            }
        });
    }
});
