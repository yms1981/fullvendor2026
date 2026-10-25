

const change_status = (e)=>{
    e.preventDefault();

    const {lang} = {...options.lang.cmp_status}
    const btn = e.currentTarget
    const row_table = btn.parentElement.parentElement
    const url = btn.href
    const text_before = btn.innerHTML
    const btn_sendcatalog = row_table.querySelector('.btn-send-catalog')
    
    let text_loader = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> '
    text_loader += btn.getAttribute('data-loader');
    btn.innerHTML = text_loader 
    btn.disabled = true
    btn.classList.add('disabled')

    fetch(url, {method: 'GET'})
    .then(response => response.json())
    .then(data => {
                
        btn.classList.remove("disabled")
        if (!data.success) {
            btn.innerHTML = text_before
            alert_func([data.msg, 'error'])                                
            return
        }
        
        if (data.load) {
            location.reload()
        }

        if (data.url) {
            btn.href = data.url            
        }
        
        if (data.data_loading) {
            btn.setAttribute("data-loading",data.data_loading)
        }
        if (data.class_toremove) {
            btn.classList.remove(data.class_toremove)    
        }
        if (data.type_btn) {
            btn.classList.add(data.type_btn)    
        }

        if (data.text_btn) {
            btn.innerHTML = data.text_btn
        }
        if(data.disable){
            console.log(data.disable)
            btn_sendcatalog.classList.add('disabled')
            btn.setAttribute("title",lang.catalog_disabled)

            if (data.change_inmodal) {
                const input_status = row_table.querySelector('.previewModal .modal-body input[name="status"]')
                input_status.value = lang.innactivo
            }
        
        }else{
            
            btn_sendcatalog.classList.remove('disabled')
            btn.setAttribute("title",lang.catalog_enabled)

            if (data.change_inmodal) {
                
                const input_status = row_table.querySelector('.previewModal .modal-body input[name="status"]')
                
                input_status.value = lang.activo
            }

        }
        
    })
}

const update = () => {

    if (boton_status_general.length > 0) {
        for (const element of boton_status_general) {        
            element.addEventListener('click', (e) => {
                change_status(e)
            });
        }
    }
}

update()


