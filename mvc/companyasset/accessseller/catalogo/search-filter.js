

//document.getElementById("search_customer").focus();
//document.getElementById("id_search").focus();
//console.log(document.getElementById("id_search"))

let collapseElementList  = get_collapseElementList();
var page = 1;

const input_search = document.querySelector('#id_search');
const tabs_element = document.querySelectorAll('[tabindex]:not([tabindex="-1"])');

update_collapseElementList(collapseElementList,tabs_element)

// method tab
document.addEventListener("keydown", tab_index);

//method search on input
input_search.addEventListener("keyup", function(e) {
  
  page = 1
  search_customer(e,collapseElementList,tabs_element,page)
    
})

window.addEventListener('scroll', function(e){

  const numrow_customers = document.querySelectorAll(".row-customers")
  const total_rows = document.querySelector("[data-total-filtered]").getAttribute("data-total-filtered")

  console.log(numrow_customers.length, total_rows)
  if (numrow_customers.length < total_rows) {
    let page_update =  get_customerbyscroll(e,page,collapseElementList,tabs_element)
    page =  page_update?page_update:page
  }
  
});