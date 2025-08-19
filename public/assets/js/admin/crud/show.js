document.addEventListener('DOMContentLoaded', function () {
    function closeAllModals() {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach((modal) => {
            if (window.mdb) {
                const modalInstance = mdb.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                modal.setAttribute('style', 'display: none');

                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }

                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        });
    }

    document.addEventListener('click', function (event) {
        const showButton = event.target.closest('.btn.show');
        if (!showButton) return;
        event.preventDefault();

        closeAllModals();

        const url = showButton.getAttribute('href');
        if (!url) {
            console.error('El botón no tiene un atributo href');
            return;
        }

        Swal.fire({
            title: 'Cargando...',
            html: 'Obteniendo información de la base de datos.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar los datos de la base de datos.');
                }
                return response.text();
            })
            .then(html => {
                Swal.close();
                const modalContainer = document.getElementById('modalContainer');
                if (!modalContainer) throw new Error('No se encontró el contenedor #modalContainer');

                modalContainer.innerHTML = html;

                const modalElement = modalContainer.querySelector('.modal.fade');
                if (modalElement) {
                    if (typeof window.initAllMDBComponents === 'function') {
                        window.initAllMDBComponents();
                    }

                    let modalDispatcher = document.querySelector('.modalDispatcher');
                    if (modalDispatcher) {
                        modalDispatcher.click();
                    }
                    slowInitDatatables();
                }

                let modals = document.querySelectorAll('.modal.fade');
                modals.forEach((modal) => {
                    modal.addEventListener('hidden.bs.modal', function (event) {
                        if (typeof modal.close === 'function') {
                            modal.close();
                        }
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos: ' + error.message,
                    confirmButtonColor: 'var(--secondary)',
                });
            });
    });
});