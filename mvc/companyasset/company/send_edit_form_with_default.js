$(function () {
    $(document).on('submit', '#form_edit', function(e) {
        e.preventDefault()
        const form = document.getElementById('form_edit')
        const formData = new FormData(form)
        formData.set('default', form.querySelector('[name="checkBoxDefault"]').checked ? 1 : 0);

        $.ajax({
            url: form.getAttribute('action'),
            type: 'post',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Editado con exito!';
                let icon = 'success';
                swal({
                    title: title,
                    icon: icon
                }).then(() => {
                    window.history.go(-1);
                });
            },
            error: function(response) {
                let title = response.responseJSON?.message ?? 'Ocurrió un error';
                let icon = 'error';
                const errors = response.responseJSON.errors;

                const errorsMessages = Object.values(errors || {})
                    .reduce((acc, curr) => acc.concat(curr), [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: icon
                });
            }

        })
    })
});
