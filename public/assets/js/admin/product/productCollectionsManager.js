document.addEventListener('DOMContentLoaded', function () {

    let collections = {
        images: 0,
        prices: 0,
        rankings: 0,
        features: 0
    };

    function initializeProductForm() {
        initializeTabs();
        initializeCollectionButtons();
        initializeDeleteButtons();
        initializeExistingItems();
        initializeMDBComponents();
    }

    function initializeTabs() {
        document.querySelectorAll('[data-mdb-toggle="tab"]').forEach(button => {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', function (e) {
                e.preventDefault();

                const tabContainer = this.closest('.nav-tabs');
                const tabContentContainer = tabContainer.nextElementSibling;

                if (!tabContentContainer?.classList.contains('tab-content')) {
                    console.warn('No se encontró el contenedor de tabs');
                    return;
                }

                // Desactivar todos los tabs
                tabContainer.querySelectorAll('.nav-link').forEach(tab => {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });

                tabContentContainer.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });

                // Activar tab actual
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                const targetId = this.getAttribute('data-mdb-target');
                const targetPanel = document.querySelector(targetId);
                if (targetPanel) {
                    targetPanel.classList.add('show', 'active');
                }
            });
        });
    }

    function initializeCollectionButtons() {
        const buttons = [
            {id: 'add-image-btn', type: 'image'},
            {id: 'add-price-btn', type: 'price'},
            {id: 'add-ranking-btn', type: 'ranking'},
            {id: 'add-feature-btn', type: 'feature'}
        ];

        buttons.forEach(({id, type}) => {
            const btn = document.getElementById(id);
            if (btn) {
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                newBtn.addEventListener('click', () => addCollectionItem(type));
            }
        });
    }

    function addCollectionItem(type) {
        const container = document.getElementById(`${type}s-container`);
        const template = document.getElementById(`${type}-template`);

        if (!container || !template) {
            console.error(`No se encontró el contenedor o template para ${type}`);
            return;
        }

        let html = template.innerHTML;
        html = html.replace(/__INDEX__/g, collections[type + 's']);
        html = html.replace(/__TYPE__/g, type);

        container.insertAdjacentHTML('beforeend', html);
        collections[type + 's']++;

        if (type === 'image') {
            setupImagePrimaryRadios();
        }

        initializeMDBComponents();

        const newItem = container.lastElementChild;
        const deleteBtn = newItem.querySelector('.delete-item');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => removeCollectionItem(newItem));
        }
    }

    function initializeDeleteButtons() {
        document.querySelectorAll('.delete-item').forEach(button => {
            button.addEventListener('click', function () {
                const item = this.closest('.collection-item');
                removeCollectionItem(item);
            });
        });
    }

    function setupImagePrimaryRadios() {
        const container = document.getElementById('images-container');
        if (!container) return;

        const primaryInputs = container.querySelectorAll('input[name*="[isPrimary]"]');

        primaryInputs.forEach((input, index) => {
            input.type = 'radio';
            input.name = 'primaryImageIndex';
            input.value = index;

            // Marcar el primer elemento si no hay ninguno seleccionado
            if (index === 0 && !container.querySelector('input[name="primaryImageIndex"]:checked')) {
                input.checked = true;
            }
        });
    }

    function removeCollectionItem(item) {
        if (confirm('¿Estás seguro de que quieres eliminar este elemento?')) {
            const isImage = item.dataset.type === 'image';
            item.remove();

            if (isImage) {
                setupImagePrimaryRadios();
            }
        }
    }

    function initializeExistingItems() {
        collections.images = document.querySelectorAll('#images-container .collection-item').length;
        collections.prices = document.querySelectorAll('#prices-container .collection-item').length;
        collections.rankings = document.querySelectorAll('#rankings-container .collection-item').length;
        collections.features = document.querySelectorAll('#features-container .collection-item').length;

        setupImagePrimaryRadios();
    }

    function initializeMDBComponents() {
        setTimeout(() => {
            document.querySelectorAll('.form-outline').forEach(outline => {
                const input = outline.querySelector('input, select, textarea');
                const label = outline.querySelector('label');

                if (input && label) {
                    setupFormOutline(input, label);
                }
            });

            if (typeof window.initAllMDBComponents === 'function') {
                window.initAllMDBComponents();
            }
        }, 50);
    }

    function setupFormOutline(input, label) {
        input.addEventListener('focus', () => {
            label.classList.add('active');
            updateLabelStyle(label);
        });

        input.addEventListener('blur', () => {
            if (!input.value || !input.value.trim()) {
                label.classList.remove('active');
                resetLabelStyle(label);
            }
        });

        input.addEventListener('input', () => {
            if (input.value && input.value.trim()) {
                label.classList.add('active');
                updateLabelStyle(label);
            }
        });

        // Los selects siempre tienen label activo
        if (input.tagName === 'SELECT') {
            label.classList.add('active');
            updateLabelStyle(label);
        }

        // Activar label si el input ya tiene contenido
        if (input.value && input.value.trim()) {
            label.classList.add('active');
            updateLabelStyle(label);
        }
    }

    function updateLabelStyle(label) {
        label.style.transform = 'translateY(-1rem) translateY(0.1rem) scale(0.8)';
        label.style.transformOrigin = 'top left';
        label.style.color = 'var(--secondary)';
        label.style.fontWeight = '500';
        label.style.backgroundColor = 'white';
        label.style.padding = '0 0.25rem';
        label.style.zIndex = '1';
    }

    function resetLabelStyle(label) {
        label.style.transform = '';
        label.style.transformOrigin = '';
        label.style.color = '';
        label.style.fontWeight = '';
        label.style.backgroundColor = '';
        label.style.padding = '';
        label.style.zIndex = '';
    }

    // Detectar cuando se agregan modales al DOM
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === 1) {
                    if (node.querySelector && (
                        node.querySelector('#newProductModal') ||
                        node.querySelector('#editProductModal') ||
                        node.classList?.contains('modal')
                    )) {
                        setTimeout(initializeProductForm, 100);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Inicializar si ya hay modales presentes
    if (document.querySelector('#newProductModal, #editProductModal')) {
        initializeProductForm();
    }

    // Inicializar cuando se abren modales
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn.edit') || e.target.closest('.btn.btn-add')) {
            setTimeout(initializeProductForm, 500);
        }
    });
});