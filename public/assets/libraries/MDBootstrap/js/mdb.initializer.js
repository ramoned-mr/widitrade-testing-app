import {
    Modal,
    Input,
    Dropdown,
    Collapse,
    Tab,
    Ripple,
    Tooltip,
    Popover,
    ScrollSpy,
    Select,
    Datepicker,
    Timepicker
} from './mdb.es.min.js';

/**
 * MDBootstrap Components Initializer
 * Este script inicializa automáticamente todos los componentes de MDBootstrap
 * que requieren inicialización mediante JavaScript.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar modales
    initModals();

    // Inicializar inputs y form outlines
    initInputs();

    // Inicializar dropdowns
    initDropdowns();

    // Inicializar collapsibles
    initCollapsibles();

    // Inicializar tabs
    initTabs();

    // Inicializar ripple effect
    initRippleEffect();

    // Inicializar tooltips
    initTooltips();

    // Inicializar acordeones
    initAccordions();

    // Inicializar popovers
    initPopovers();

    // Inicializar scrollspy
    initScrollSpy();

    // Inicializar selects
    initSelects();

    // Inicializar datepickers
    initDatepickers();

    // Inicializar timepickers
    initTimepickers();
});

/**
 * Función auxiliar para inicializar componentes de forma segura
 * @param {string} selector - Selector CSS para encontrar elementos
 * @param {Function} initCallback - Función de callback para inicializar cada elemento
 */
function safeInitialize(selector, initCallback) {
    const elements = document.querySelectorAll(selector);
    if (elements && elements.length > 0) {
        elements.forEach(element => {
            try {
                initCallback(element);
            } catch (error) {
                console.warn(`Error al inicializar elemento ${selector}:`, error);
            }
        });
    }
}

/**
 * Inicializa todos los modales en la página
 */
function initModals() {
    // Inicialización automática de modales cuando se hace clic en triggers
    safeInitialize('[data-mdb-toggle="modal"]', trigger => {
        trigger.addEventListener('click', function () {
            const targetModalId = this.getAttribute('data-mdb-target');
            if (targetModalId) {
                const targetModal = document.querySelector(targetModalId);
                if (targetModal) {
                    try {
                        const modal = new Modal(targetModal);
                        modal.show();
                    } catch (error) {
                        console.warn('Error al mostrar modal:', error);
                    }
                }
            }
        });
    });

    // También inicializa modales con atributo data-mdb-modal-init
    safeInitialize('[data-mdb-modal-init]', modalElement => {
        new Modal(modalElement);
    });
}

/**
 * Inicializa todos los form inputs con el estilo outline
 */
function initInputs() {
    // Inicializar form-outline inputs
    safeInitialize('.form-outline, [data-mdb-input-init]', formOutline => {
        // Inicializar el componente Input de MDB
        const input = new Input(formOutline);

        // Utilizar el método adecuado para la inicialización según la versión
        if (typeof input.init === 'function') {
            input.init();
        }

        // Obtener los elementos del DOM
        const inputElement = formOutline.querySelector('input, textarea');

        if (inputElement) {
            // Verificar si ya tiene valor al cargar la página
            if (inputElement.value.trim() !== '') {
                // Forzar actualización utilizando los métodos de MDB
                input.update();

                // Como respaldo, también agregar la clase active directamente
                const label = formOutline.querySelector('label');
                if (label) {
                    label.classList.add('active');
                }
            }

            // Agregar listener para cuando el usuario termine de editar
            inputElement.addEventListener('blur', function () {
                if (this.value.trim() !== '') {
                    // Llamar al método update de MDB para actualizar el estado
                    if (typeof input.update === 'function') {
                        input.update();
                    }
                }
            });
        }
    });
}

/**
 * Inicializa todos los dropdowns en la página
 */
function initDropdowns() {
    // Inicializar todos los dropdowns
    safeInitialize('[data-mdb-toggle="dropdown"], [data-mdb-dropdown-init]', dropdownEl => {
        new Dropdown(dropdownEl);
    });
}

/**
 * Inicializa todos los collapsibles en la página
 */
function initCollapsibles() {
    // Inicializar todos los elementos colapsables
    safeInitialize('[data-mdb-toggle="collapse"], [data-mdb-collapse-init]', collapseEl => {
        const targetId = collapseEl.getAttribute('data-mdb-target') ||
            collapseEl.getAttribute('href');
        if (targetId) {
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                new Collapse(targetElement, {
                    toggle: false
                });
            }
        }
    });
}

/**
 * Inicializa todas las pestañas en la página
 */
function initTabs() {
    // Inicializar pestañas
    safeInitialize('[data-mdb-toggle="tab"], [data-mdb-tab-init]', tabEl => {
        new Tab(tabEl);
    });
}

/**
 * Inicializa el efecto ripple en los botones y otros elementos
 */
function initRippleEffect() {
    // Aplicar efecto ripple a todos los elementos con clase .btn o atributo data-mdb-ripple-init
    safeInitialize('[data-mdb-ripple-init], .btn', el => {
        if (!el.classList.contains('ripple-surface')) {
            new Ripple(el);
        }
    });
}

/**
 * Inicializa todos los tooltips en la página
 */
function initTooltips() {
    // Inicializar tooltips
    safeInitialize('[data-mdb-toggle="tooltip"], [data-mdb-tooltip-init]', tooltipEl => {
        new Tooltip(tooltipEl);
    });
}

/**
 * Inicializa todos los acordeones en la página
 */
function initAccordions() {
    // Primero, inicializar todos los elementos colapsables dentro de acordeones
    safeInitialize('.accordion-collapse', collapseItem => {
        try {
            // Crear una nueva instancia de Collapse para cada elemento colapsable
            const collapse = new Collapse(collapseItem, {
                toggle: collapseItem.classList.contains('show')
            });

            // Almacenar la instancia como un dato en el elemento para futuras referencias
            collapseItem._mdbCollapse = collapse;
        } catch (error) {
            console.warn('Error al inicializar elemento colapsable de acordeón:', error);
        }
    });

    // Luego, configurar los botones para que actúen sobre sus elementos colapsables
    safeInitialize('.accordion-button', buttonEl => {
        try {
            buttonEl.addEventListener('click', function(event) {
                event.preventDefault();

                // Obtener el target (el elemento que debe colapsar/expandir)
                const targetSelector = this.getAttribute('data-mdb-target') ||
                    this.getAttribute('href');

                if (targetSelector) {
                    const collapseElement = document.querySelector(targetSelector);

                    if (collapseElement && collapseElement._mdbCollapse) {
                        // Usar la instancia de Collapse almacenada para alternar el estado
                        collapseElement._mdbCollapse.toggle();
                    } else if (collapseElement) {
                        // Si no hay instancia almacenada, crear una nueva y alternar
                        try {
                            const collapse = new Collapse(collapseElement);
                            collapse.toggle();
                        } catch (error) {
                            console.warn('Error al alternar elemento colapsable:', error);
                        }
                    }
                }
            });
        } catch (error) {
            console.warn('Error al configurar botón de acordeón:', error);
        }
    });

    // Asegurar que los acordeones con múltiples elementos solo tengan uno abierto a la vez
    safeInitialize('.accordion', accordionEl => {
        if (accordionEl.hasAttribute('data-mdb-active-item') ||
            !accordionEl.classList.contains('accordion-flush')) {
            // Este es un acordeón que solo debe tener un elemento abierto a la vez

            const collapseItems = accordionEl.querySelectorAll('.accordion-collapse');
            collapseItems.forEach(item => {
                item.addEventListener('show.bs.collapse', function() {
                    // Cerrar otros elementos abiertos en el mismo acordeón
                    collapseItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem._mdbCollapse &&
                            otherItem.classList.contains('show')) {
                            otherItem._mdbCollapse.hide();
                        }
                    });
                });
            });
        }
    });
}

/**
 * Inicializa todos los popovers en la página
 */
function initPopovers() {
    // Inicializar popovers
    safeInitialize('[data-mdb-toggle="popover"], [data-mdb-popover-init]', popoverEl => {
        new Popover(popoverEl);
    });
}

/**
 * Inicializa ScrollSpy en la página
 */
function initScrollSpy() {
    // Inicializar scrollspy
    safeInitialize('[data-mdb-spy="scroll"], [data-mdb-scrollspy-init]', scrollSpyEl => {
        const targetId = scrollSpyEl.getAttribute('data-mdb-target');
        if (targetId) {
            new ScrollSpy(scrollSpyEl, {
                target: targetId
            });
        }
    });
}

/**
 * Inicializa todos los select mejorados
 */
function initSelects() {
    // Corrección para selects generados por Symfony
    safeInitialize('.form-outline select', selectEl => {
        // Asegurarse de que el label se active correctamente
        const formOutline = selectEl.closest('.form-outline');
        if (formOutline) {
            const label = formOutline.querySelector('label');
            if (label) {
                // Forzar la clase active en el label para que siempre sea visible
                label.classList.add('active');

                // También manipular el estilo para asegurar que esté visible
                label.style.transform = 'translateY(-1rem) translateY(0.1rem) scale(0.8)';
                label.style.backgroundColor = 'white';
                label.style.padding = '0 0.2rem';
                label.style.zIndex = '1';
            }

            // Ajustar el estilo del contenedor select para que se muestre correctamente
            selectEl.style.paddingTop = '0.33rem';
            selectEl.style.paddingBottom = '0.33rem';
        }
    });

    // Inicializar selects mejorados (versión original)
    safeInitialize('.select, [data-mdb-select-init], select.form-control', selectEl => {
        try {
            // Evitar re-inicializar selects que ya han sido tratados
            if (selectEl.closest('.form-outline') && !selectEl.hasAttribute('data-mdb-select-init')) {
                return;
            }

            // Inicializar el componente Select de MDB si aún no se ha inicializado
            let select;
            try {
                select = new Select(selectEl);
            } catch (e) {
                console.warn('Error al inicializar select, podría estar ya inicializado:', e);
            }

            // Buscar el elemento contenedor (form-outline)
            const formOutline = selectEl.closest('.form-outline');

            if (formOutline) {
                // Obtener el label asociado
                const label = formOutline.querySelector('label');

                if (label) {
                    // Forzar la clase active en el label inmediatamente
                    label.classList.add('active');

                    // Timeout para asegurar que el label se mantiene activo después de la inicialización
                    setTimeout(() => {
                        label.classList.add('active');
                    }, 100);
                }

                // Agregar listeners para múltiples eventos que podrían cambiar el estado del select
                selectEl.addEventListener('focus', function () {
                    if (label) label.classList.add('active');
                });

                selectEl.addEventListener('blur', function () {
                    // Siempre mantener la clase active en el label, incluso después de perder el foco
                    if (label) label.classList.add('active');
                });

                selectEl.addEventListener('change', function () {
                    if (label) label.classList.add('active');
                });

                // También manejar cuando se abre la lista desplegable
                selectEl.addEventListener('click', function () {
                    if (label) label.classList.add('active');
                });

                // Forzar la actualización del select si tiene un método update
                if (select && typeof select.update === 'function') {
                    select.update();

                    // También programar una actualización retrasada para asegurar que se aplica correctamente
                    setTimeout(() => {
                        try {
                            select.update();
                        } catch (e) {
                            console.warn('Error al actualizar select:', e);
                        }
                    }, 200);
                }
            }
        } catch (error) {
            console.warn('Error en la inicialización personalizada de select:', error);
        }
    });

    // Buscar específicamente cualquier select dentro de un modal para asegurar que se inicialice correctamente
    document.addEventListener('shown.bs.modal', function (e) {
        const modal = e.target;
        const selects = modal.querySelectorAll('.select, [data-mdb-select-init], select.form-control, .form-outline select');

        selects.forEach(selectEl => {
            const formOutline = selectEl.closest('.form-outline');
            if (formOutline) {
                const label = formOutline.querySelector('label');
                if (label) {
                    // Forzar la clase active en labels dentro de modales
                    label.classList.add('active');

                    // Aplicar el mismo estilo para los selects en modales
                    label.style.transform = 'translateY(-1rem) translateY(0.1rem) scale(0.8)';
                    label.style.backgroundColor = 'white';
                    label.style.padding = '0 0.2rem';
                    label.style.zIndex = '1';
                }

                // Ajustar el estilo del select
                selectEl.style.paddingTop = '0.33rem';
                selectEl.style.paddingBottom = '0.33rem';
            }
        });
    });

    // Observador de mutaciones para detectar cuando se agregan nuevos selects al DOM
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Elemento DOM
                        // Buscar nuevos selects en los nodos agregados
                        const newSelects = node.querySelectorAll ?
                            node.querySelectorAll('.select, [data-mdb-select-init], select.form-control, .form-outline select') : [];

                        if (newSelects.length > 0) {
                            newSelects.forEach(selectEl => {
                                const formOutline = selectEl.closest('.form-outline');
                                if (formOutline) {
                                    const label = formOutline.querySelector('label');
                                    if (label) {
                                        // Forzar la clase active en labels de nuevos selects
                                        label.classList.add('active');

                                        // Aplicar el mismo estilo para los nuevos selects
                                        label.style.transform = 'translateY(-1rem) translateY(0.1rem) scale(0.8)';
                                        label.style.backgroundColor = 'white';
                                        label.style.padding = '0 0.2rem';
                                        label.style.zIndex = '1';
                                    }

                                    // Ajustar el estilo del select
                                    selectEl.style.paddingTop = '0.33rem';
                                    selectEl.style.paddingBottom = '0.33rem';
                                }
                            });
                        }
                    }
                });
            }
        });
    });

    // Iniciar la observación del documento
    observer.observe(document.body, {childList: true, subtree: true});
}

/**
 * Inicializa todos los datepickers
 */
function initDatepickers() {
    // Inicializar datepickers
    safeInitialize('[data-mdb-datepicker-init]', dateEl => {
        new Datepicker(dateEl);
    });
}

/**
 * Inicializa todos los timepickers
 */
function initTimepickers() {
    // Inicializar timepickers
    safeInitialize('[data-mdb-timepicker-init]', timeEl => {
        new Timepicker(timeEl);
    });
}

// Hacer la función disponible globalmente
window.initInputs = initInputs;
window.initAllMDBComponents = function () {
    initModals();
    initInputs();
    initDropdowns();
    initCollapsibles();
    initTabs();
    initRippleEffect();
    initTooltips();
    initAccordions();
    initPopovers();
    initScrollSpy();
    initSelects();
    initDatepickers();
    initTimepickers();
};