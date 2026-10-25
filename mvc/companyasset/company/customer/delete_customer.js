const delete_customer = async (id) => {
    
    const response = await fetch(options.url + '/customer/delete_customer/' + id);
    if (!response.ok) throw new Error(`Error al obtener datos: ${response.status} - ${response.statusText}`);
    
    const data = await response.json();

    if (data.success) {
        location.reload();
    } else {

        if (data.confirm) {            
            confirm_elimination(data.msg, id)
            return
        }
        swal({
            title: data.msg,
            icon: 'error',
        })
    }
}

function confirm_elimination(msg, id) {
    const {lang} = {...options.lang.cmp_clientes}
    swal({
        title: msg,
        icon: 'error',
        buttons: {
            confirm: {
                text: lang.si,
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
            cancel: {
                text: "No",
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },

        },
    }).then( async (willDelete) => {
        if (willDelete) {
            
            const formData = new FormData();
            formData.append('confirm',true)
            const response = await fetch(options.url + '/customer/delete_customer/' + id,{
                method: 'POST',
                body:formData
            });
            
            if (!response.ok) throw new Error(`Error al obtener datos: ${response.status} - ${response.statusText}`);
            const data = await response.json();
            if (data.success) {
                location.reload();
                return
            }
            swal({
                title: data.msg,
                icon: 'error',
            })
        }
    });
}