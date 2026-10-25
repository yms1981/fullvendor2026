const submit_send = (e,form_submit) => {
    e.preventDefault();

    const button = e.submitter
    button.disabled = true
    const text_before = button.innerHTML
    let text_loader = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + text_before    
    button.innerHTML = text_loader 

    let formdata = new FormData(form_submit);
    
    fetch(e.srcElement.action, {
        method: 'POST',
        body: formdata
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false
        button.innerHTML = text_before
        if (!data.success) {
            return alert_func([data.msg, 'error'])
        }
        
        if (data.load) {
            location.reload()
        }

        if (data.redirect_back) {
            window.history.go(-1)            
        }
    })
}

if (form_submit) {
    form_submit.addEventListener('submit', (e)=>{
        submit_send(e, form_submit)
    })
}

if (form_submit_class.length > 0) {
    for (const element of form_submit_class) {        
        element.addEventListener('submit', (e) => {
            submit_send(e, element)
        });
    }
}