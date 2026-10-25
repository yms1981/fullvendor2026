const change_status = (e,element) => {

    e.preventDefault();
    const {lang} = {...options.lang.cmp_catalog}
    const url = e.currentTarget.href
    swal({
        title: lang.delete_catalog,
        icon: 'warning',
        buttons: {
            
            confirm: {
                text: lang.si,
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
            cancel: {
                text: lang.cancel,
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },
        },
    }).then((willDelete) => {
        
        if (willDelete) {
            
            fetch(url, {method: 'GET'})
            .then(response => response.json())
            .then(data => {
                
                if (!data.success) {
                    alert_func([data.msg, 'error'])                                
                }
                //window.history.go(-1)
                location.reload()               
            })
        }
    })

    
}

if (bnt_delete_general.length > 0) {
    for (const element of bnt_delete_general) {
        
        element.addEventListener('click', (e) => {
            change_status(e, element)
        });
    }
}