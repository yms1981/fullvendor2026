let lastScrollTop = 0;

document.addEventListener('scroll', function() {
    
    var element = document.querySelector('#header-marketplace');
    let currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (currentScrollTop > lastScrollTop) {
        // Scroll hacia abajo
        if (!element.classList.contains('align-items-center')) {
            element.classList.add('align-items-center');
        }
    } else {
        // Scroll hacia arriba
        if (element.classList.contains('align-items-center')) {
            element.classList.remove('align-items-center');
        }
    }
    lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop; // Para evitar valores negativos
});
