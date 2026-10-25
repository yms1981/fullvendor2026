const selector_group = document.getElementsByClassName("selector_group")

const change_group = (e) => {
    const selector = e.srcElement;
    const selector_customer = selector.parentElement.nextElementSibling.querySelector("select");
    const spinner = selector.parentElement.nextElementSibling.querySelector(".spinner-border");

    spinner.classList.remove("d-none")
    
    const id = selector_customer.getAttribute("id")
       
    const group_id = selector.value; // Obtener el valor seleccionado del selector actual
    
    // Obtener la URL base - intentar diferentes formas
    let baseUrl = '';
    if (typeof companyUrl !== 'undefined') {
        baseUrl = companyUrl;
    } else if (typeof options !== 'undefined' && options.url) {
        baseUrl = options.url;
    } else {
        // Usar la URL relativa directamente desde la ubicación actual
        const pathParts = window.location.pathname.split('/').filter(p => p);
        const companyIndex = pathParts.indexOf('company');
        if (companyIndex !== -1) {
            baseUrl = window.location.origin + '/' + pathParts.slice(0, companyIndex + 1).join('/');
        } else {
            baseUrl = window.location.origin;
        }
    }
    
    const url = baseUrl + '/catalog/filterbygroup/' + group_id;
    
    fetch(url, {
        method: 'GET',
    })
    .then(response => response.json())
    .then(data => {
        
        spinner.classList.add("d-none")    
        if (!data.success) {
            alert_func([data.msg, 'error'])
            return       
        }

        selector_customer.innerHTML = ""

        let fragment = document.createDocumentFragment();
        const list = data.customers || data.groups || [];
        list.forEach(function(element) {
            const option = document.createElement('option');
            option.value = element.id;
            var bn = (element.business_name || '').toUpperCase();
            var nm = (element.name || '').toUpperCase();
            option.textContent = bn + ' (' + nm + ')';
            fragment.appendChild(option);
        });

        selector_customer.appendChild(fragment)
        $("#"+id).selectpicker('refresh');
        //window.history.go(-1)
        //location.reload()
    })
    
}

if (selector_group.length > 0) {
    for (const element of selector_group) {        
        element.addEventListener('change', change_group);
    }
}