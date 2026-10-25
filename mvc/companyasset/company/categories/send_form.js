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
            return alert_func([data.msg, 'error'])
        }
        window.history.go(-1)
    })
}

form_submit.addEventListener('submit', submit_send)