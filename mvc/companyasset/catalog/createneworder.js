
let span_total_qtypro = document.getElementById('total_qtypro')

const createneworder = (e) =>{
    e.preventDefault();
    const qty_cart = parseInt(span_total_qtypro.innerText);
    const {lang} = env.lang.cmp_catalog

    if (qty_cart > 0) {
        Swal.fire({

            text: lang.clean_cart,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28A745',
            confirmButtonText: lang.yes,
            cancelButtonColor: '#d33',
            cancelButtonText: 'No',
            reverseButtons: true
          }).then((result) => {
            if (result.isConfirmed) {
              send_formdata(e,token)
            }
          })

    }else{
        send_formdata(e,token)
    }

}

const send_formdata = (e,token) => {
    const form = new FormData()
    form.append("token",token)
    fetch(url_base + "catalogo/producto/create_neworder",{
        method: "POST",
        body: form
    })
    .then(response => response.json())
    .then(data => {

        if (!data.success) {
            swalalert.fire('',data.msg,'error')
            return
        }

        location.href = e.srcElement.href
    });
}
console.log(env.btn_createneworder)
env.btn_createneworder.addEventListener("click",createneworder)
