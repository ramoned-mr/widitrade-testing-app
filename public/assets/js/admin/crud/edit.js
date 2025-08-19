document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', event => {
        const editButton = event.target.closest('.btn.edit');
        if (!editButton) return;
        event.preventDefault();

        const url = editButton.getAttribute('href');
        if (!url) {
            console.error('El botón no tiene un atributo href');
            return;
        }

        Swal.fire({
            title: 'Cargando...',
            html: 'Preparando formulario de edición.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(response => {
                if (!response.ok) throw new Error('Error al cargar el formulario: ' + response.status);
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

                    setupFormSubmission(modalElement);

                    let modalDispatcher = document.querySelector('.modalDispatcher');
                    if (modalDispatcher) {
                        modalDispatcher.click();
                    }
                    initTiny();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el formulario de edición: ' + error.message,
                    confirmButtonColor: 'var(--secondary)',
                });
            });
    });

    function setupFormSubmission(modalElement) {
        const saveButton = modalElement.querySelector('button[type="submit"]');
        const form = modalElement.querySelector('form');

        if (saveButton && saveButton._submitHandler) {
            saveButton.removeEventListener('click', saveButton._submitHandler);
        }

        if (saveButton && form) {
            const submitHandler = function (event) {
                event.preventDefault();
                handleFormSubmit(form);
            };

            saveButton._submitHandler = submitHandler;
            saveButton.addEventListener('click', submitHandler);
        }
    }

    function handleFormSubmit(form) {
        const formData = new FormData(form);

        Swal.fire({
            title: 'Guardando...',
            html: 'Actualizando datos.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(response => response.text())
            .then(html => {
                Swal.close();

                const modalContainer = document.getElementById('modalContainer');
                modalContainer.innerHTML = html;

                const errorAlert = modalContainer.querySelector('.alert.alert-danger');
                const modalElement = modalContainer.querySelector('.modal.fade');

                if (errorAlert || modalElement) {
                    if (typeof window.initInputs === 'function') {
                        window.initInputs();
                    }

                    if (typeof window.initAllMDBComponents === 'function') {
                        window.initAllMDBComponents();
                    }

                    setupFormSubmission(modalElement);

                    let modalDispatcher = document.querySelector('.modalDispatcher');
                    if (modalDispatcher) {
                        modalDispatcher.click();
                    }
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Excelente!',
                        text: "Registro actualizado correctamente.",
                        confirmButtonText: "¡Entendido!",
                        confirmButtonColor: "var(--secondary)",
                        showCloseButton: true,
                        didClose: () => {
                            window.location.reload();
                        }
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al enviar el formulario de edición.',
                    confirmButtonColor: 'var(--secondary)',
                });
            });
    }
});