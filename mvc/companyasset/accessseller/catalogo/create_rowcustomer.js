const create_rowcustomer = (customer,index) => {
    const element = document.createElement("div")
    element.classList.add("panel")
    element.classList.add("panel-default")
    element.classList.add("row-customers")
    element.innerHTML = `
    <div class="panel-heading">
    <h5 class="panel-title">
            <a tabindex="${index}"
               id="octagon" class="collapsed customer" role="button" 
               data-bs-toggle="collapse" href="#faq-collapse-${customer.customer_id}" 
               aria-expanded="false" aria-controls="faq-collapse-${customer.customer_id}"> 
               <span class="button-faq"></span>${customer.business_name} (${customer.name})
            </a>
            <input hidden="" class="form-check-input" type="radio" value="${customer.customer_id}" name="customer">
        </h5>
    </div>
    <div id="faq-collapse-${customer.customer_id}" class="collapse" aria-expanded="true" role="tabpanel" data-bs-parent="#faq-five" style="">
    </div>
    `
    return element
}