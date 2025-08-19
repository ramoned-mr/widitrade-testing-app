document.addEventListener('DOMContentLoaded', () => {
    let deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            console.log(form);
            event.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ab0404',
                cancelButtonColor: '#e0ac02',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                showCloseButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});