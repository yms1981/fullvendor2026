document.addEventListener("visibilitychange", function() {
    if (document.visibilityState === 'visible') {
        // La página se hizo visible (se cambió a esta pestaña)
        console.log('Cambiaste a esta pestaña');
        // Puedes agregar aquí cualquier acción que desees realizar al cambiar de pestaña
        //location.reload()
    }
});