const delete_entity = (e,element) => {

    const msg = element.getAttribute('data-msg')   
    console.log(msg)

    e.preventDefault();
    const {lang} = {...options.lang.cmp_catalog}
    const url = e.currentTarget.href
    swal({
        title: msg ? msg : lang.delete_catalog + '?',
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
                text: 'No',
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
                console.log(data)
                if (!data.success) {
                    alert_func([data.msg, 'error'])                                
                    return
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
            delete_entity(e, element)
        });
    }
}