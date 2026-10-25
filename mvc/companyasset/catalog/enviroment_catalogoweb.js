if (typeof env === 'undefined') {
    var env = {
        url: `${UrlBase}`,
        base_url: `${UrlBase}`,
        input_validate: document.querySelectorAll("form .validate"),
        check_newadrress: document.getElementById("address")
    };
} else {
    env.input_validate = document.querySelectorAll("form .validate");
    env.check_newadrress = document.getElementById("address");
}
const all_products = []
const open_seachSidebar = () => {

    document.getElementById("seach_insidebar").click()

}
