var container_working = null
var container_past = null
const leftContainer = document.getElementById('example2Left'); // categorias sin seleccionar
const rightContainer = document.getElementById('example2Right'); // categorias seleccionadas
const all_itemscategories = document.querySelectorAll('.fullvendor-select-order-categories .list-group-item')
var list_itemsfocus = []

// console.log(all_itemscategories)

const get_Containerselecteditems = () => document.querySelector('#example2Right')
const get_Containernotselecteditems = () => document.querySelector('#example2Left')
const get_selecteditems = () => document.querySelectorAll('#example2Right .list-group-item')
const get_notselecteditems = () => document.querySelectorAll('#example2Left .list-group-item')

const btn_addall = document.getElementById('btn_addall')
btn_addone = document.getElementById('btn_addone')
btn_removeone = document.getElementById('btn_removeone')
btn_removeall = document.getElementById('btn_removeall')
if (leftContainer) {

    new Sortable(leftContainer, {
        group: 'shared', // set both lists to same group    
        animation: 150,
        onEnd: ( /**Event*/ evt) => {
            
            const itemEl = evt.item.querySelector('input');  // dragged HTMLElement   
            console.log(itemEl)     
            if (!(evt.to === leftContainer) && itemEl && !itemEl.hasAttribute('name')) {
                const span_order = evt.item.querySelector('span.css-order-item')
                console.log(span_order)
                if (span_order) span_order.classList.remove('hidden-important');
                
                // agrega el atributo 'name' del elemento input
                itemEl.setAttribute('name', 'categ_selected[]');
    
                // Muestra un mensaje de confirmación en la consola
                console.log('Se agrego el atributo "name" del elemento:', itemEl);
                // console.log(evt)
    
                const categories_selected = evt.to.querySelectorAll('.list-group-item')            
                order_categories(categories_selected)
            }
        },
    });
    
}

new Sortable(rightContainer, {
    group: {
        name: 'shared',
        pull: function (to, from, dragEl) {
            // Permitir mover fuera solo si el elemento tiene data-movable="true"
            // console.log(dragEl)
            return dragEl.dataset.movable === "true";
        },        
    },
    animation: 150,
    onEnd: (/**Event*/evt) => {
        console.log("Hola retirando item")
                
        const itemEl = evt.item.querySelector('input');  // dragged HTMLElement
        if (!(evt.to === rightContainer) && itemEl && itemEl.hasAttribute('name')) {
            
            const span_order = evt.item.querySelector('span.css-order-item')
            console.log(span_order)
            if (span_order) span_order.classList.add('hidden-important');
            // Elimina el atributo 'name' del elemento input
            itemEl.removeAttribute('name');        
            // Muestra un mensaje de confirmación en la consola
            // console.log('Se eliminó el atributo "name" del elemento:', itemEl);            
        }

        const categories_selected = evt.from.querySelectorAll('.list-group-item')
        order_categories(categories_selected)
        update_ItemsnotSelected()        
    },
    
});

const update_ItemsnotSelected = () => {
    
    // Obtener todos los elementos .list-group-item dentro del contenedor #example2Left
    const listGroupItems = document.querySelectorAll('#example2Left .list-group-item');

    // Filtrar los elementos para encontrar aquellos cuyo span hijo no tenga la clase .hidden-important
    const filteredItems = Array.from(listGroupItems).filter(item => {
        const spanElement = item.querySelector('span.css-order-item');
        return spanElement && !spanElement.classList.contains('hidden-important');
    });
    
    filteredItems.forEach((el /*elemento seleccionado*/ , index) => {
        
        const itemEl = el.querySelector('input'); 
        const span_order = el.querySelector('span.css-order-item')        
        if (span_order) span_order.classList.add('hidden-important');
        itemEl.removeAttribute('name');
        // console.log(span_order)
        // fragment.appendChild(el)
    });    
    // console.log(filteredItems)
}

const order_categories = (elements) => {
    console.log("Ordenando Categorias")
    // console.log(elements)
    elements.forEach((el /*elemento seleccionado*/ , index) => {            
        index++
        // console.log(`Elemento ${index}:`, el);
        // Buscamos el span y le cambiamos el
        el.querySelector('span.css-order-item').innerText = index

    });
}

const addelements_incategoryselected = (fragments) => {

}

const addall_or_addone = (elements) => {
    const fragment = document.createDocumentFragment()
    elements.forEach((el /*elemento seleccionado*/ , index) => {

        const itemEl = el.querySelector('input'); 
        const span_order = el.querySelector('span.css-order-item')

        if (span_order) span_order.classList.remove('hidden-important');
        
        // itemEl.removeAttribute('name');
        el.classList.remove('active-selected-category');
        itemEl.setAttribute('name', 'categ_selected[]');
        fragment.appendChild(el)
    });    
    
    get_Containerselecteditems().appendChild(fragment)    
    order_categories(get_selecteditems())
    // console.log("hola")
}

const removeall_or_addone = (elements) => {

    const fragment = document.createDocumentFragment()
    elements.forEach((el /*elemento seleccionado*/ , index) => {
        
        if (el.dataset.movable) {

            const itemEl = el.querySelector('input'); 
            const span_order = el.querySelector('span.css-order-item')    
            
            if (span_order) span_order.classList.remove('hidden-important');            
            el.classList.remove('active-selected-category');
            itemEl.removeAttribute('name');
            fragment.appendChild(el)
        }
    });
    
    get_Containernotselecteditems().appendChild(fragment)    
    order_categories(get_selecteditems())
}

btn_addall.addEventListener("click", (e)=>{

    console.log("CLICK ALL")
    const categories_notselected = document.querySelectorAll('#example2Left .list-group-item')
    // console.log(categories_notselected)
    addall_or_addone(categories_notselected)
})

btn_addone.addEventListener("click", (e)=>{

    btn_addone.disabled = true
    btn_removeone.disabled = true
    // const categories_notselected = document.querySelectorAll('#example2Left .list-group-item .active-selected-category')    
    addall_or_addone(list_itemsfocus)
    list_itemsfocus = []

})

btn_removeone.addEventListener("click", (e)=>{    
    console.log("CLICK REMOVE ONE")
    // console.log(list_itemsfocus)
    removeall_or_addone(list_itemsfocus)
    update_ItemsnotSelected()
    list_itemsfocus = []

    btn_addone.disabled = true
    btn_removeone.disabled = true
})

btn_removeall.addEventListener("click", (e)=>{

    const categories_notselected = document.querySelectorAll('#example2Right .list-group-item')
    console.log("CLICK REMOVE ALL ", categories_notselected)
    removeall_or_addone(categories_notselected)
    update_ItemsnotSelected()
})

const delete_fromlist = (item) => {
    const index = list_itemsfocus.indexOf(item);
        // Si el elemento está en el array
        if (index !== -1) {
            // Elimina el elemento del array
            list_itemsfocus.splice(index, 1);
            // console.log("Elemento eliminado de la lista de elementos enfocados:", item);
        }
}

const add_tolist = (item) => {
    if (!list_itemsfocus.includes(item)) {        
        list_itemsfocus.push(item);
        // console.log("Elemento agregado a la lista de elementos enfocados:", item);
    }
}

// Agregar eventos (focus, blur)
all_itemscategories.forEach((item,index) => {
    
    //console.log(item)
    // item.addEventListener('focus', (e) => {
    //     console.log("focus")        
    //     item.classList.add('active-selected-category');
    //     if (!list_itemsfocus.includes(item)) {            
    //         list_itemsfocus.push(item);
    //         console.log("Elemento agregado a la lista de elementos enfocados:", item);
    //     }
    // });
    
    
    item.addEventListener('click', (e) => {                
        
        const all_items = document.querySelectorAll(`.fullvendor-select-order-categories .list-group-item.active-selected-category`)            
        all_items.forEach((item,index) => {
            item.classList.remove('active-selected-category');
            delete_fromlist(item)
        });
                
        container_past = container_working
        container_working = item.parentElement.id
        
        console.log("Contenedor Anterior " + container_past )
        console.log("Contenedor Actual " + container_working )

        // si contenedor pasado no es igual al contenedor actual eliminamos los items seleccionados en el contenedor anterior
        if (container_past && container_past!=container_working) {
            const items = document.querySelectorAll(`#${container_past} .list-group-item.active-selected-category`)            
            items.forEach((item,index) => {
                item.classList.remove('active-selected-category');
                delete_fromlist(item)
            });
            console.log(list_itemsfocus)
        }
        
        if (!item.getAttribute('no_removed')){
            
            if (container_working == 'example2Left') {
                btn_addone.disabled = false
                btn_removeone.disabled = true
                
            }else if(container_working == 'example2Right'){
                
                btn_addone.disabled = true
                btn_removeone.disabled = false
    
            }
        }


        if(!item.classList.contains('active-selected-category')){
            item.classList.add('active-selected-category');
            add_tolist(item)
        }else{
            item.classList.remove('active-selected-category');
            delete_fromlist(item)
        }
    });
    
      
    item.addEventListener('blur', (e) => {
        
        // console.log(e)                    
        if (e.relatedTarget == null) {         
            // console.log("hola no es")
            list_itemsfocus.forEach((item,index) => {
                item.classList.remove('active-selected-category');
            });
            list_itemsfocus = []
            return
        }
        
        if (!(e.relatedTarget.id && e.relatedTarget.id === 'btn_addone')) {            
            // console.log("hola no es +")  
        }else{
            // console.log("hola si es")
            list_itemsfocus.forEach((item,index) => {
                item.classList.remove('active-selected-category');
            });            
            return
            
        }
        //console.log(list_itemsfocus)
        
        // e.srcElement.classList.add('active-selected-category')
        // item.classList.remove('active-selected-category');
    });
});

