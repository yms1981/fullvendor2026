
const update_collapseElementList = (collapseElementList,tabs_element) => {

    collapseElementList.forEach(function (element, key, parent) {

        element.addEventListener('show.bs.collapse', (event) => {
            event.preventDefault()
            tabs_element.forEach((element)=>{
                element.classList.remove('border-dark-tab')
            })
    
            const id = event.target.id
            collapseElementList.forEach(function (element, key, parent) {
                const contain = element.parentElement.querySelector('.collapsed')
                const input_cheked = element.parentElement.querySelector('.form-check-input')
    
                if (id === element.id){
                    contain.setAttribute('aria-expanded','true')
                    input_cheked.checked =true
    
                }else{
                    
                    contain.setAttribute('aria-expanded','false')
                }
            })


        })
        
        element.parentElement.addEventListener('dblclick', (event) => {
            
            remove_border(collapseElementList)
            document.getElementById("btn-aceptar").click()
            //doubleclick_customer()
            
        })

        element.parentElement.addEventListener('click', (event) => {
            //click_customer()
            document.getElementById("id_search").value = event.srcElement.innerText
        })
    })

    let index_last = get_lasttabindex("form-create") + 1
    const element_lasindex = document.getElementById("last-index")
    element_lasindex.setAttribute("tabindex",index_last)

    index_last = index_last + 1
    // let index_last2 = get_lasttabindex("form-create") + 1
    const btn_aceptar = document.getElementById("btn-aceptar")
    btn_aceptar.setAttribute("data-tabindex",index_last)
    //document.getElementById("btn-aceptar").setAttribute("tabindex",index_last + 1)
}

const remove_border = (collapseElementList) => {
    collapseElementList.forEach(function (element, key, parent) {
        const contain = element.parentElement.querySelector('.collapsed')
        contain.classList.remove('border-dark-tab')

    })
}