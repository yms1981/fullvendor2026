const get_lasttabindex = (container) => {
    
    const container_selected = document.getElementById(container); // Reemplaza 'container' con el ID de tu contenedor
    const elements = container_selected.querySelectorAll('[tabindex]');

    let lastIndex = -1;
    for (let i = 0; i < elements.length; i++) {
        let tabIndex = parseInt(elements[i].getAttribute('tabindex'), 10);
        
        if (tabIndex > lastIndex) {
            lastIndex = tabIndex;
        }
    }
    
    return lastIndex
}