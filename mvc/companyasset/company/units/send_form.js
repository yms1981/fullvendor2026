const submit_send = (e) => {
    e.preventDefault();

    let formdata = new FormData(form_submit);
    fetch(e.srcElement.action, {
        method: 'POST',
        body: formdata
    })
    .then(response => response.json())
    .then(data => {
        
        if (!data.success) {

            return data.question_confirm
                    ?alert_confirm(e,formdata,data.msg)
                    :alert_func([data.msg, 'error'])
                        
        }
        window.history.go(-1)
        //location.reload()
    })
}

const alert_confirm = (e,formdata,msg) =>{
    console.log(formdata)
    formdata.append("confirm_send_when_default", 1)
    const {lang} = {...options.lang.unit_languaje}

    swal({
        title: msg?msg:lang.already_exists,
        icon: 'warning',
        buttons: {
            confirm: {
                text: lang.change,
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
            }            
        },
    }).then((willDelete) => {
        
        if (willDelete) {
            console.log(e.srcElement.action)

            fetch(e.srcElement.action, {
                method: 'POST',
                body: formdata
            })
            .then(response => response.json())
            .then(data => {
                
                if (!data.success) {
        
                    return data.question_confirm
                            ?alert_confirm()
                            :alert_func([data.msg, 'error'])
                                
                }

                window.history.go(-1)
                //location.reload()               
            })
        }
    })
    
}

form_submit.addEventListener('submit', submit_send)