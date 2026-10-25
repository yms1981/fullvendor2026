document.addEventListener("DOMContentLoaded", async (event) => {
    const check_newadrress = document.getElementById("address");
    check_newadrress.addEventListener("click", async (event) => {

        //event.preventDefault();
        const input = event.srcElement
        const collapseContainer = document.getElementById('collapsetwo');
        collapseContainer.classList.toggle('show');

        if (!input.checked) {
            input_validate.forEach(function (input, key, parent) {
                input.classList.remove("is-invalid")
                input.classList.remove("is-valid")
                input.nextElementSibling.style = 'display: none';
            })
        }
    })
});
