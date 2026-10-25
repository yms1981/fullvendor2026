const env = {
    //url: 'http://localhost:8080/fullvendor/salesman',
    url: `${UrlBase}salesman`,
	check_newadrress: document.getElementById("address"),
    btn_createneworder: document.getElementById("btn_createneworder")
}

const open_seachSidebar = () => {

    document.getElementById("seach_insidebar").click()

}