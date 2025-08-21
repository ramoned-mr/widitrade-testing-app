// ===== RANKING ACCORDION FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function () {

    // Inicializar accordion functionality
    initializeAccordionButtons();

    // Agregar smooth scrolling para mejor UX
    initializeSmoothScrolling();

    // Tracking de eventos para analytics (opcional)
    initializeEventTracking();

    console.log('Ranking accordion initialized successfully');
});

/**
 * Inicializa la funcionalidad de los botones accordion
 */
function initializeAccordionButtons() {
    const showMoreButtons = document.querySelectorAll('.btn-show-more');

    showMoreButtons.forEach(button => {
        // Remover listeners previos para evitar duplicados
        button.removeEventListener('click', handleAccordionToggle);

        // Agregar nuevo listener
        button.addEventListener('click', handleAccordionToggle);

        // Inicializar estado correcto del botón
        updateButtonState(button);
    });
}

/**
 * Maneja el toggle del accordion
 * @param {Event} event - Evento del click
 */
function handleAccordionToggle(event) {
    const button = event.currentTarget;
    const target = button.getAttribute('data-bs-target');
    const collapseElement = document.querySelector(target);

    if (!collapseElement) {
        console.warn('Collapse element not found:', target);
        return;
    }

    // Toggle del estado
    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
        // Cerrar
        collapseElement.classList.remove('show');
        button.setAttribute('aria-expanded', 'false');

        // Scroll suave hacia el producto después de cerrar
        setTimeout(() => {
            scrollToProduct(button);
        }, 300);
    } else {
        // Abrir
        collapseElement.classList.add('show');
        button.setAttribute('aria-expanded', 'true');

        // Scroll suave hacia el contenido expandido después de abrir
        setTimeout(() => {
            scrollToExpandedContent(collapseElement);
        }, 300);
    }

    // Actualizar estado visual del botón
    updateButtonState(button);

    // Trigger evento personalizado para tracking
    dispatchCustomEvent('accordion-toggle', {
        target: target,
        expanded: !isExpanded,
        productIndex: getProductIndex(button)
    });
}

/**
 * Actualiza el estado visual del botón
 * @param {HTMLElement} button - Botón a actualizar
 */
function updateButtonState(button) {
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    const showMoreText = button.querySelector('.show-more-text');
    const showLessText = button.querySelector('.show-less-text');
    const expandIcon = button.querySelector('.expand-icon');

    if (isExpanded) {
        showMoreText.style.display = 'none';
        showLessText.style.display = 'inline';
        expandIcon.style.transform = 'rotate(180deg)';
    } else {
        showMoreText.style.display = 'inline';
        showLessText.style.display = 'none';
        expandIcon.style.transform = 'rotate(0deg)';
    }
}

/**
 * Inicializa smooth scrolling para mejor UX
 */
function initializeSmoothScrolling() {
    // Configurar comportamiento de scroll suave
    if ('scrollBehavior' in document.documentElement.style) {
        document.documentElement.style.scrollBehavior = 'smooth';
    }
}

/**
 * Scroll suave hacia el producto
 * @param {HTMLElement} button - Botón que se clickeó
 */
function scrollToProduct(button) {
    const productCard = button.closest('.product-card');
    if (productCard) {
        const rect = productCard.getBoundingClientRect();
        const absoluteTop = window.pageYOffset + rect.top;
        const offsetTop = absoluteTop - 100; // Offset para header fijo si existe

        window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
        });
    }
}

/**
 * Scroll suave hacia el contenido expandido
 * @param {HTMLElement} collapseElement - Elemento que se expandió
 */
function scrollToExpandedContent(collapseElement) {
    // Pequeño delay para que la animación termine
    setTimeout(() => {
        const rect = collapseElement.getBoundingClientRect();
        const absoluteTop = window.pageYOffset + rect.top;
        const offsetTop = absoluteTop - 50;

        window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
        });
    }, 100);
}

/**
 * Obtiene el índice del producto
 * @param {HTMLElement} button - Botón dentro del producto
 * @returns {number} Índice del producto
 */
function getProductIndex(button) {
    const productCard = button.closest('.product-card');
    return productCard ? productCard.getAttribute('data-product-index') : null;
}

/**
 * Inicializa el tracking de eventos para analytics
 */
function initializeEventTracking() {
    // Tracking de clicks en botones de compra
    const buyButtons = document.querySelectorAll('.btn-buy-amazon, .btn-buy-secondary');
    buyButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            const productCard = this.closest('.product-card');
            const productIndex = productCard ? productCard.getAttribute('data-product-index') : null;

            dispatchCustomEvent('product-purchase-click', {
                productIndex: productIndex,
                buttonType: this.classList.contains('btn-buy-amazon') ? 'primary' : 'secondary',
                productUrl: this.href
            });
        });
    });

    // Tracking de visualización de características
    const collapseElements = document.querySelectorAll('.product-features-collapse');
    collapseElements.forEach(element => {
        // Observer para detectar cuando el elemento se hace visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.classList.contains('show')) {
                    const productIndex = getProductIndexFromCollapse(entry.target);

                    dispatchCustomEvent('product-features-viewed', {
                        productIndex: productIndex
                    });

                    // Desconectar el observer después de la primera vista
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        observer.observe(element);
    });
}

/**
 * Obtiene el índice del producto desde un elemento collapse
 * @param {HTMLElement} collapseElement - Elemento collapse
 * @returns {string|null} Índice del producto
 */
function getProductIndexFromCollapse(collapseElement) {
    const productCard = collapseElement.closest('.product-card');
    return productCard ? productCard.getAttribute('data-product-index') : null;
}

/**
 * Dispara un evento personalizado
 * @param {string} eventName - Nombre del evento
 * @param {Object} detail - Datos del evento
 */
function dispatchCustomEvent(eventName, detail) {
    const event = new CustomEvent(eventName, {
        detail: detail,
        bubbles: true
    });

    document.dispatchEvent(event);

    // Log para desarrollo
    if (window.console && console.log) {
        console.log(`Event: ${eventName}`, detail);
    }
}

/**
 * Funciones utilitarias globales
 */
window.RankingAccordion = {
    /**
     * Colapsa todos los productos expandidos
     */
    collapseAll: function () {
        const expandedButtons = document.querySelectorAll('.btn-show-more[aria-expanded="true"]');
        expandedButtons.forEach(button => {
            button.click();
        });
    },

    /**
     * Expande un producto específico por índice
     * @param {number} index - Índice del producto
     */
    expandProduct: function (index) {
        const productCard = document.querySelector(`[data-product-index="${index}"]`);
        if (productCard) {
            const button = productCard.querySelector('.btn-show-more');
            if (button && button.getAttribute('aria-expanded') === 'false') {
                button.click();
            }
        }
    },

    /**
     * Scroll hacia un producto específico
     * @param {number} index - Índice del producto
     */
    scrollToProduct: function (index) {
        const productCard = document.querySelector(`[data-product-index="${index}"]`);
        if (productCard) {
            const rect = productCard.getBoundingClientRect();
            const absoluteTop = window.pageYOffset + rect.top;
            const offsetTop = absoluteTop - 100;

            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    }
};