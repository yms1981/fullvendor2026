
fetch(options.url + '/catalog/download/' + catalog_id,{method: 'GET',})
.then(response => response.json())
.then(data => {
    
    const fragment = document.createDocumentFragment()

    data.forEach(value => {

        let celdas = `<td>${value.pro_id}</td>
                     <td>
                        <figure class="position-relative figure-fila">
                            <img src="${value.imagen_js}"
                            style="display: block; width: 100%; height: 100%;"
                            class="rounded-0" alt="">
                        <figure>
                     </td>   
                     <td>${value.name}</td>
                     <td>${value.sku}</td>
                     `

        const fila = document.createElement('tr')
        fila.innerHTML = celdas
        fragment.appendChild(fila)

    });
    document.getElementById('tbody').append(fragment)
    window.print();
})