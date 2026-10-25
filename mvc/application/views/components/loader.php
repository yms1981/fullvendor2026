<?php
/**
 * Componente de loader elegante y reutilizable para todo el proyecto
 * 
 * Uso:
 * - Mostrar: AppLoader.show()
 * - Ocultar: AppLoader.hide()
 * - Con mensaje: AppLoader.show('Cargando...')
 */
?>
<style>
.app-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(250, 250, 250, 0.95) 100%);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.app-loader-overlay.show {
    display: flex !important;
    opacity: 1;
}

.app-loader-overlay.hiding {
    opacity: 0;
}

.app-loader-content {
    text-align: center;
    padding: 2rem;
}

/* Animación elegante con logo */
.app-loader-animation {
    width: 150px;
    height: 150px;
    position: relative;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.app-loader-logo {
    width: 120px;
    height: 120px;
    object-fit: contain;
    animation: app-loader-pulse-rotate 2s ease-in-out infinite;
    filter: drop-shadow(0 0 20px rgba(220, 53, 69, 0.5)) drop-shadow(0 0 40px rgba(0, 0, 0, 0.3));
}

.app-loader-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top-color: #dc3545;
    border-right-color: #dc3545;
    border-radius: 50%;
    animation: app-loader-spin 1.5s linear infinite;
}

.app-loader-ring::before {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border: 3px solid transparent;
    border-bottom-color: #000000;
    border-left-color: #000000;
    border-radius: 50%;
    animation: app-loader-spin-reverse 2s linear infinite;
}

@keyframes app-loader-spin {
    0% { 
        transform: rotate(0deg);
    }
    100% { 
        transform: rotate(360deg);
    }
}

@keyframes app-loader-spin-reverse {
    0% { 
        transform: rotate(0deg);
    }
    100% { 
        transform: rotate(-360deg);
    }
}

@keyframes app-loader-pulse-rotate {
    0%, 100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
        filter: drop-shadow(0 0 20px rgba(220, 53, 69, 0.5)) drop-shadow(0 0 40px rgba(0, 0, 0, 0.3));
    }
    25% {
        transform: scale(1.05) rotate(5deg);
        opacity: 0.95;
        filter: drop-shadow(0 0 25px rgba(220, 53, 69, 0.7)) drop-shadow(0 0 50px rgba(0, 0, 0, 0.4));
    }
    50% {
        transform: scale(1.1) rotate(0deg);
        opacity: 0.9;
        filter: drop-shadow(0 0 30px rgba(220, 53, 69, 0.8)) drop-shadow(0 0 60px rgba(0, 0, 0, 0.5));
    }
    75% {
        transform: scale(1.05) rotate(-5deg);
        opacity: 0.95;
        filter: drop-shadow(0 0 25px rgba(220, 53, 69, 0.7)) drop-shadow(0 0 50px rgba(0, 0, 0, 0.4));
    }
}
</style>

<div id="app-loader" class="app-loader-overlay">
    <div class="app-loader-content">
        <div class="app-loader-animation">
            <div class="app-loader-ring"></div>
            <img src="<?=base_url('images/logos/logo.png')?>" alt="Loading" class="app-loader-logo">
        </div>
    </div>
</div>

<script>
/**
 * AppLoader - Sistema de loader elegante y reutilizable
 */
var AppLoader = {
    element: null,
    hideTimeout: null,
    
    init: function() {
        this.element = document.getElementById('app-loader');
        
        if (!this.element) {
            console.warn('AppLoader: Elemento no encontrado');
            return;
        }
    },
    
    show: function(message) {
        if (!this.element) this.init();
        if (!this.element) return;
        
        // Limpiar timeout anterior si existe
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
        
        // Mostrar loader
        this.element.classList.remove('hiding');
        this.element.classList.add('show');
        this.element.style.display = 'flex';
        
        // Forzar reflow para asegurar que la animación funcione
        this.element.offsetHeight;
    },
    
    hide: function() {
        if (!this.element) this.init();
        if (!this.element) return;
        
        // Agregar clase de ocultación
        this.element.classList.add('hiding');
        
        // Ocultar después de la animación
        this.hideTimeout = setTimeout(function() {
            if (AppLoader.element) {
                AppLoader.element.classList.remove('show', 'hiding');
                AppLoader.element.style.display = 'none';
            }
        }, 300);
    },
    
    isVisible: function() {
        if (!this.element) this.init();
        return this.element && this.element.classList.contains('show');
    }
};

// Inicializar cuando el DOM esté listo
(function() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            AppLoader.init();
        });
    } else {
        AppLoader.init();
    }
})();
</script>

