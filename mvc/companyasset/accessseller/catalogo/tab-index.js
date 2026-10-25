var enterCount = 0;

var enterPressed = false;
var lastEnterTime = 0;
var index_tab = 0

const tab_index = (event) => {
  
  const currentElement = event.target;
  let tabIndex = parseInt(currentElement.getAttribute("tabindex"));
  
  if (event.key === "Tab" && event.shiftKey === true) {
      //console.log("Tab + shift " + tabIndex)
      
      let nextElement = document.querySelector('[tabindex="' + (tabIndex - 1) + '"]');
      const tabs_element = document.querySelectorAll('[tabindex]');
      tabs_element.forEach((element)=>{
        element.classList.remove('border-dark-tab')
      })
        
//      console.log(nextElement)
      if (nextElement && nextElement.id!="last-index") {
        //console.log(nextElement)
        nextElement.classList.add('border-dark-tab')
        
        if (nextElement.classList.contains("collapsed")){
          nextElement.click()
          document.getElementById("id_search").value = nextElement.innerText

        }
        
        //console.log(nextElement)
        nextElement.focus();
        event.preventDefault();
        return
      }

      unchecked_input(get_collapseElementList())
      return;
  }

  if (event.key == "Tab") {
    //console.log("Tab " + tabIndex)
    let nextElement = document.querySelector('[tabindex="' + (tabIndex + 1) + '"]');
    const tabs_element = document.querySelectorAll('[tabindex]');
    tabs_element.forEach((element)=>{
      element.classList.remove('border-dark-tab')
    })  
        
    nextElement = (nextElement==null)?document.querySelector('[tabindex="4"]'):nextElement;
    console.log(nextElement)
    if (nextElement && nextElement.id!="last-index") {

      nextElement.classList.add('border-dark-tab')
      nextElement.focus();
      if (nextElement.classList.contains("collapsed")){
          nextElement.click()
          document.getElementById("id_search").value = nextElement.innerText
      }
      event.preventDefault();
      return
    }
        
    unchecked_input(get_collapseElementList())  
    return;
  }

  // Verificar si la tecla presionada es el Enter
  if (event.keyCode === 13) {
    
    if (event.srcElement.classList.contains("customer")) {
      document.getElementById("btn-aceptar").click()
    }

    // var currentTime = new Date().getTime();
        
    // if (enterPressed && currentTime - lastEnterTime < 300) {
      
    //   if (event.srcElement.classList.contains("customer")) {
    //     console.log("DOBLE ENTER EN CUSTOMER")
    //     document.getElementById("btn-aceptar").click()
    //   }     
    // }
    
    // enterPressed = true;
    // lastEnterTime = currentTime;
  // } else {
    
  //   enterPressed = false;
  //   lastEnterTime = 0;
  // }
  }
  
  // Verificar si se presionó una tecla de flecha
  if (event.keyCode >= 37 && event.keyCode <= 40) {
    
    index_tab = index_tab<0?0:index_tab

    // if (index_tab > 0) {
    //   return
    // }
    
    if (event.keyCode === 37 || event.keyCode === 38) {  // Flecha izquierda o flecha arriba
      const index = isNaN(tabIndex)?index_tab:tabIndex;
      let nextElement = document.querySelector('[tabindex="' + (index - 1) + '"]');

      index_tab = index_tab - 1
            
      //nextElement = (!nextElement)?document.querySelector('[tabindex="' + (index_tab = index_tab - 1) + '"]'):nextElement
      
      if (focus_element(event,nextElement)) {
        return
      }
      
      unchecked_input(get_collapseElementList())
      return;

    } else if (event.keyCode === 39 || event.keyCode === 40) {  // Flecha derecha o flecha abajo
      
      tabIndex = tabIndex<=0?1:tabIndex
      const index = isNaN(tabIndex)?index_tab:tabIndex;
      //console.log("Tab Index Flecha derecha o flecha abajo " + index_tab +  " Tab index "+ tabIndex);
      
      let nextElement = document.querySelector('[tabindex="' + (index + 1) + '"]');
      
      //nextElement = (!nextElement)?document.querySelector('[tabindex="' + (index_tab = index_tab + 1) + '"]'):nextElement
      index_tab = index_tab + 1 
      //console.log("Tab Index Flecha derecha o flecha abajo " + index_tab)
      if (focus_element(event,nextElement)) {
        return
      }
      
      unchecked_input(get_collapseElementList())
      return;
      //console.log(nextElement)
     
    }    
  }

}

const unchecked_input = (collapseElementList) => {
  collapseElementList.forEach(function (element, key, parent) {
    const contain = element.parentElement.querySelector('.collapsed')
    const input_cheked = element.parentElement.querySelector('.form-check-input')
    contain.setAttribute('aria-expanded','false')
    input_cheked.checked =true
    
  })
}

const focus_element = (event,nextElement) => {
  const tabs_element = document.querySelectorAll('[tabindex]');

  tabs_element.forEach((element)=>{
    element.classList.remove('border-dark-tab')
  })

   if (index_tab<=0) {
    index_tab = 0
    const index = 0
    nextElement = document.querySelector('[tabindex="' + (index + 1) + '"]');
    //console.log("Tab Index --" + index_tab)
   }
  //index_tab = index_tab<=0?1:index_tab
  //nextElement = document.querySelector('[tabindex="' + index_tab + '"]')
   
  //console.log("Tab Index " + index_tab)
  if (nextElement && nextElement.id!="last-index") {
        
    nextElement.classList.add('border-dark-tab')
    
    if (nextElement.classList.contains("collapsed")){
      nextElement.click()
      document.getElementById("id_search").value = nextElement.innerText
    }
        
    nextElement.focus();
    event.preventDefault();
    return true
  }
    
  if (nextElement) nextElement.focus();
    
  return false
}