const search_customer = (e,collapseElementList,tabs_element) => {

    const text = e.target.value
    const url = e.target.getAttribute('data-url')
    const data = new FormData()
    const total_rows = document.querySelector("[data-total-filtered]")

    data.append("text_tosearch",text)
    fetch(url,{
      method: "POST",
      body: data

    })
    .then(response => response.json())
    .then(response => {
      let index = 3
      clean_customers_panel()
      const {data,total_filtered} = response
      data.forEach(element => {
        const row_customer = create_rowcustomer(element,index)
        customers_panel.append(row_customer)
        index++
      })

      if (data.length<=0) {
        // customers_panel.querySelector('#text-emtpy').hidden = falseç
        document.querySelector('#text-emtpy').hidden = false

      }else{
        document.querySelector('#text-emtpy').hidden = true
      }
      collapseElementList = get_collapseElementList()
      update_collapseElementList(collapseElementList,tabs_element)
      change_numberFiltered(total_filtered)

    });

  }
