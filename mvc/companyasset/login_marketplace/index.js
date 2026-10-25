
var customers = [], vendors = [], cont_blurfocus = -1, email_user = null

function changeLang(idx) {
    let ctEng = document.getElementById('langf-en');
    let ctSpa = document.getElementById('langf-spa');
    let frm = document.getElementById('frmChangeLang');
    let langinput = document.createElement('input');
    langinput.setAttribute("type", "hidden");
    langinput.setAttribute("name", "language_id");
    langinput.setAttribute("value", idx);
    console.log(langinput)
    frm.appendChild(langinput);
    let usertypeinput = document.createElement('input');
    usertypeinput.setAttribute("type", "hidden");
    usertypeinput.setAttribute("name", "usertype");
    usertypeinput.setAttribute("value", "anonymous");
    console.log(frm.getAttribute('action'))

    frm.appendChild(usertypeinput);
    frm.submit();

}

const form_sign_in = document.querySelector("form#sign_in")
const form_register = document.querySelector("form#sign_in")
const form_forgot_password = document.querySelector("form#forgot_password")
const selector_customer = document.querySelector(".selector_customer")
const selector_company = document.querySelector(".selector_company")

console.log(selector_customer)
// const card_login = document.querySelector('#card-login'),
const card_login = $('#card-login'),
    // card_register = document.querySelector('#card-register'),
    card_register = $('#card-register'),
    // card_forgotpassword = document.querySelector('#card-forgot-password')
    card_forgotpassword = $('#card-forgot-password'),
    select_options_customer = $('#select_options_customer')

const show_register = () => {

    // card_login.classList.add('animate__animated')
    // card_login.classList.add('animate__backOutLeft')

    card_login.hide();
    card_forgotpassword.hide()
    card_register.show()

}

const show_forgetpassword = () => {

    card_login.hide();
    card_register.hide();
    card_forgotpassword.show()

}

const show_login = () => {
    card_login.show();
    card_forgotpassword.hide()
    card_register.hide()

}

const inputs_form = document.querySelectorAll('form input.keyup')

inputs_form.forEach(input => {

    input.addEventListener('keyup',(e) => {

        $(e.srcElement).next().hide(100)
    })
});

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
        option.selected = customersFilter.length == 1 ? true : false
        option.value = customer.id;
        // option.setAttribute('data-icon', 'fa-solid fa-user')
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

form_sign_in.addEventListener('submit',async (e) => {

    e.preventDefault()
    email_user = document.getElementById('email').value
    const form_submit = document.querySelector("#card-form form")
    const button = e.submitter
    button.disabled = true
    const text_before = button.innerHTML
    let text_loader = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + text_before
    button.innerHTML = text_loader

    let formdata = new FormData(form_submit);

    const response = await fetch(`${url_base}login/sign_in`,{
        method: 'POST',
        body: formdata
    });


    const data = await response.json();
    button.disabled = false
    button.innerHTML = text_before

    $(`#card-form .alert`).hide()
    if (!data.success){

        alert_validation(data)
    }

    if (data.repeat_customer == false) {
        hide_selects()
    }

    // Login
    console.log(data)
    if (data.login_url) return location.href = data.login_url
})

const alert_validation = (data) => {

    $(`#card-form form .alert`).hide()
    if (data.errorvalidation) {
        return data.errors.forEach(error => {
            // console.log(`#card-login form .${error.name}`)
            const alert = $(`#card-form form .alert.${error.name}`)
            alert.text(error.msg)
            alert.show(100)
        });
    }

    if(data.select_company && data.select_customer){
        selector_company.innerHTML = ""
        selector_customer.innerHTML = ""

        const fragment = document.createDocumentFragment();
        vendors = [...data.companies]
        customers = [...data.customers]
        const rows = data.companies.length
        data.companies.forEach(company => {

            const option = document.createElement('option');
            if(rows == 1) option.setAttribute('selected', '')
            option.value = company.company_id;
            const imgSrc = company.small_image ? company.small_image : company.image;
            option.setAttribute('data-content', `
                <div class="d-flex">
                    <div>
                        <img alt="${company.company_name}" title="${company.company_name}" style="width: 30px;" src="${imgSrc}">
                    </div>
                    <div class="pl-2">
                        <h6>${company.company_name}</h6>
                    </div>
                </div>
            `)
            console.log(option)
            // option.textContent = `${company.business_name.toUpperCase()} (${company.name.toUpperCase()})`;
            fragment.appendChild(option);
        });

        selector_company.disabled = false
        selector_customer.disabled = true
        selector_company.appendChild(fragment)
        $('.selector_customer').selectpicker('refresh');
        $('.selector_company').selectpicker('refresh');

        if(rows == 1) {
            const firstValue = document.querySelectorAll('.selector_company option')[1].value;
            $('.selector_company').selectpicker('val',firstValue);
            selector_customer.disabled = false
        }

        $('#selector_company').show(200)
        select_options_customer.show(200)

    }

}
document.getElementById("email").addEventListener("keyup", async function(e) {


email_user= null

})


document.getElementById("email").addEventListener("blur", async function(e) {
    cont_blurfocus++


    if (cont_blurfocus <= 0 ) return
    // if (!email_user) return
    console.log(email_user)
    console.log(e.srcElement.value)
    if (email_user == e.srcElement.value) return

    let formdata = new FormData();
    formdata.append('username', document.getElementById('email').value)
    formdata.append('password', document.getElementById('password').value)
    formdata.append('langId', document.getElementById('langId').value)

    const response = await fetch(`${url_base}login/sign_in`,{
        method: 'POST',
        body: formdata
    });

    const data = await response.json();
    console.log(data)
    alert_validation(data)

});


const Login = async () => {




}
