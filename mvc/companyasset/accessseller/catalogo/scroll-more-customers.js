
const  get_customerbyscroll = (e,page,collapseElementList,tabs_element) =>{

  const windowHeight = window.innerHeight;
  const scrollPosition = window.scrollY || window.pageYOffset || document.documentElement.scrollTop;

  var documentHeight = Math.max(
    document.body.scrollHeight,
    document.body.offsetHeight,
    document.documentElement.clientHeight,
    document.documentElement.scrollHeight,
    document.documentElement.offsetHeight
  );

  const scrollHeight = documentHeight
  const scrollPos =   windowHeight + scrollPosition

  //console.log(scrollHeight, scrollpos)

  if ((((scrollHeight - 300) >= scrollPos) / scrollHeight == 0) ||
    (((scrollHeight - 350) >= scrollPos) / scrollHeight == 0) ||
    (((scrollHeight - 400) >= scrollPos) / scrollHeight ==0) ||
    (((scrollHeight - 450) >= scrollPos) / scrollHeight == 0) ||
    (((scrollHeight - 500) >= scrollPos) / scrollHeight == 0) ||
    (((scrollHeight - 550) >= scrollPos) / scrollHeight ==0)) {

    page = page + 1

    console.log('Llegaste al final de la página o contenedor ' +page);
    const loader_element = document.getElementById("loader")
    enable_loader(loader_element)

     const input_search = document.querySelector('#id_search');
     const data = new FormData()
     data.append("text_tosearch",input_search.value)
     data.append("page",page)
     fetch(urlBase + '/searchCustomer',{
        method: "POST",
        body: data
     })
     .then(response => response.json())
     .then(response => {
        const {data,total_filtered} = response

        if (data.length >= 1) {

          const customers_panel = document.getElementById("customers_panel");
          const fragment = document.createDocumentFragment();

          let index = get_lasttabindex("form-create") + 1

          data.forEach(element => {
            const row_customer = create_rowcustomer(element,index)
            //console.log(row_customer)
            fragment.appendChild(row_customer);
            index++
          })
          customers_panel.appendChild(fragment)
          collapseElementList = get_collapseElementList()
          update_collapseElementList(collapseElementList,tabs_element)
          disable_loader(loader_element);

        }else{
          disable_loader(loader_element);
        }
        change_numberFiltered(total_filtered.number)

      });

    return  page
  }

  return null

}
