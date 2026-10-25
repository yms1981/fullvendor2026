<script>
var form = document.querySelector('#catalogAdd')

// Función para esperar a que select2 esté disponible
function waitForSelect2(callback, maxAttempts) {
    maxAttempts = maxAttempts || 50;
    var attempts = 0;
    
    function check() {
        attempts++;
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            callback();
        } else if (attempts < maxAttempts) {
            setTimeout(check, 100);
        } else {
            console.warn('select2 not loaded after ' + (maxAttempts * 100) + 'ms');
            // Intentar ejecutar el callback de todas formas si jQuery está disponible
            if (typeof jQuery !== 'undefined') {
                callback();
            }
        }
    }
    check();
}

$(document).ready(function() {
    // Esperar a que select2 esté disponible antes de inicializarlo
    waitForSelect2(function() {
        if (typeof jQuery.fn.select2 !== 'undefined') {
            $(".js-example").select2({
                dropdownParent: $("#myselect2")
            });
        } else {
            console.error('select2 no está disponible');
        }
    });

    const asign_ordernumner = (array) => {
        array.forEach(function(element, key, parent) {
            let cont = element.order + 1
            let clase = `.dual-listbox__item[data-id="${element.category_id}"]`
            let li = document.querySelector(clase)
            if (!li) {
                clase =
                    `.dual-listbox__item.dual-listbox__item--selected[data-id="${element.category_id}"]`
                li = document.querySelector(clase)
            }

            if (li) {

                if (li.querySelectorAll('span.css-order').length >= 1) {
                    li.querySelectorAll('span.css-order')[0].innerText = cont
                } else {
                    //console.log(li)
                    const spanItem = document.createElement("span");
                    spanItem.classList.add('css-order')
                    spanItem.innerText = cont;
                    li.prepend(spanItem)
                }
            } else {
                //console.log(li)
            }

        })
    }

    const get_ordercategory = (listelement) => {
        let array = []
        listelement.forEach(function(element, key, parent) {
            array.push({
                "order": key,
                "category_id": element.dataset.id
            })
        })
        return array
    }

    const send_form_tosave = (e) => {

        e.preventDefault()
        const datos = new FormData(e.srcElement);

        fetch(e.srcElement.action, {
                method: 'POST',
                body: datos
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert_func([data.msg, 'error'])
                    return
                }
                location.href = data.urlredirect
            })
    }

    if (form) {
        form.addEventListener('submit', send_form_tosave);
    }
    // asign_ordernumner(get_ordercategory(dualListbox.selected))
    // dualListbox.reorder()
    // console.log(dualListbox)
    

    var event = new Event('reorder');

    // Obtener la instancia de DualListbox
    
    // dualListbox.dispatchEvent(event);
    // dualListbox.reorder()
});
</script>