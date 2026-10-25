const hide_selects = () => {
    $('#select_options_customer').hide(200)    
    const _selector_customer = document.querySelector(".selector_customer")
    const _selector_company = document.querySelector(".selector_company")
    
    _selector_company.value = ""
    _selector_company.innerHTML = ""
    _selector_customer.innerHTML = ""
    _selector_customer.value = ""
}