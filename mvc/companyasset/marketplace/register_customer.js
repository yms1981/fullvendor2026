var customers = [], vendors = []
const form_register = document.querySelector("form#register")
const selector_customer = document.querySelector(".selector_customer")
const selector_company = document.querySelector(".selector_company")
const select_options_customer = $('#select_options_customer')

$('.selector_customer').on('changed.bs.select', function (e) {
    $('.alert.customer').hide(200)
}); 

$('.selector_company').on('changed.bs.select', function (e) {
        
    const company_id = $(this).val()
    const customersFilter = customers.filter(customer => customer.id_comp === company_id);

    
    const fragment = document.createDocumentFragment();
    selector_customer.innerHTML = ""
    
    customersFilter.forEach(customer => {
        const option = document.createElement('option');
        option.value = customer.id;
        option.setAttribute('data-icon', 'fa-solid fa-user')
        option.textContent = `${customer.business_name.toUpperCase()} (${customer.name.toUpperCase()})`;
        fragment.appendChild(option);
    });

    selector_customer.appendChild(fragment)

    $('#selector_customer').show(200)
    selector_customer.disabled = false
    $('.selector_customer').selectpicker('refresh');
    
    console.log($('.alert.company'))
    $('.alert.company').hide(200)
});


form_register.addEventListener('submit',async (e) => {

    e.preventDefault()
    const form_submit = document.querySelector("#card-form #register")
    let formdata = new FormData(e.srcElement);

    const button = e.submitter       
    button.disabled = true 
    const text_before = button.innerHTML
    let text_loader = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + text_before
    button.innerHTML = text_loader 
   
    const response = await fetch(`${url_base}login/register`,{
        method: 'POST',
        body: formdata
    });
    
    // limpiamos los select
    // select_options_customer.hide(200)
    // selector_company.value = ""
    // selector_company.innerHTML = ""
    // selector_customer.innerHTML = ""
    // selector_customer.value = ""
    
    const data = await response.json();    
    
    button.disabled = false
    button.innerHTML = text_before
    $(`#card-form .alert`).hide()

    if (!data.success){
        if (data.errorvalidation) {
            
            return data.errors.forEach(error => {            
                // console.log(`#card-login form .${error.name}`)
                const alert = $(`#card-form .alert.${error.name}`)
                alert.text(error.msg)
                alert.show(100)
            });
        }

        if(data.select_company && data.select_customer){
            selector_company.innerHTML = ""
            const fragment = document.createDocumentFragment();
            vendors = [...data.companies]
            customers = [...data.customers]

            data.companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.company_id;
                option.setAttribute('data-content', `
                    <div class="d-flex">
                        <div>
                            <img alt="${company.company_name}" title="${company.company_name}" style="width: 30px;" src="${company.image}">
                        </div>
                        <div class="pl-2">
                            <h6>${company.company_name}</h6>                            
                        </div>
                    </div>
                `)
                // option.textContent = `${company.business_name.toUpperCase()} (${company.name.toUpperCase()})`;
                fragment.appendChild(option);               

            });
            selector_company.appendChild(fragment)
            
            // console.log($('#selector_company'))
            $('#selector_company').show(200)
            selector_company.disabled = false
            $('.selector_company').selectpicker('refresh');
            select_options_customer.show(200)
            
        }   
    }

    if (data.repeat_customer == false) {
        hide_selects()
    }
    
    if (data.login_url) return location.reload()// return location.href = data.login_url
})


