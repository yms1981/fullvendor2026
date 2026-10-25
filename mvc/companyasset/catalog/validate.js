input_validate = document.querySelectorAll("form .validate");
input_validate.forEach(function (element, key, parent) {
    element.addEventListener("keyup", async (event) => {
        const input = event.target

        if (!input.value) {

            input.classList.remove("is-valid")
            //input.classList.add('is-invalid');
            //input.nextElementSibling.innerText = ".."
            //input.nextElementSibling.style = 'display: block';
            return
        }
        input.classList.remove("is-invalid")
        input.classList.add("is-valid")
        input.nextElementSibling.style = 'display: none';
    })
})
