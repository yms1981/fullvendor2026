$(function() {
// Obtener referencias a los elementos de los inputs
    semRojoHastaInput = $('input[name="sem_red_hasta"]')
    semAmarilloDesdeInput = $('input[name="sem_amarillo_desde"]')
    semAmarilloHastaInput = $('input[name="sem_amarillo_hasta"]')
    semVerdeDesdeInput = $('input[name="sem_verde_desde"]')
    boton_enviar_first = $('#boton_enviar_first')
    boton_enviar_second = $('#boton_enviar_second')

// Agregar un event listener al campo sem_amarillo_desde para escuchar cambios
    semRojoHastaInput.on('keyup', function(e) {

        // Obtener el valor actual de sem_amarillo_desde como número entero
        let hastaValue = parseInt($(this).val()) || 0;

        // Calcular el nuevo valor para sem_amarillo_hasta (sem_amarillo_desde + 1)
        hastaValue = hastaValue + 1;

        // Asignar el nuevo valor calculado al campo sem_amarillo_hasta
        semAmarilloDesdeInput.val( hastaValue);
        semAmarilloHastaInput.val(hastaValue + 1);
        semVerdeDesdeInput.val(parseInt(semAmarilloHastaInput.val()) + 1);
    });

    semAmarilloHastaInput.on('blur', function(e) {

        // Obtener el valor actual de sem_amarillo_desde como número entero
        let hastaValue = parseInt($(this).val()) || 0;
        const value_amarillohasta = parseInt(semAmarilloDesdeInput.val()) || 0

        // Calcular el nuevo valor para sem_amarillo_hasta (sem_amarillo_desde + 1)
        if (hastaValue<= value_amarillohasta){
            e.target.classList.add('is-invalid')
            if(boton_enviar_first)
                boton_enviar_first.disabled = true
        }else{
            e.target.classList.remove('is-invalid')
            if(boton_enviar_first)
                boton_enviar_first.disabled = false
        }

        hastaValue = hastaValue + 1;

        // Asignar el nuevo valor calculado al campo sem_amarillo_hasta
        semVerdeDesdeInput.val(hastaValue);

    });
})
