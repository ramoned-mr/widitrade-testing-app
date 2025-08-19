document.addEventListener('DOMContentLoaded', initDatatables, false);

function initDatatables() {
    const tables = document.querySelectorAll('table.table-striped.table-bordered');

    tables.forEach(function (table) {
        if ($.fn.DataTable.isDataTable(table)) {
            return;
        }

        // Verificar si la tabla tiene datos o solo un mensaje de "no hay datos"
        const firstRow = table.querySelector('tbody tr');
        if (firstRow && firstRow.querySelector('td[colspan]')) {
            // Esta tabla tiene un mensaje de "no hay datos" con colspan
            // No inicializar DataTables para esta tabla
            return;
        }

        $.ajax({
            url: lang,
            type: "HEAD",
            timeout: 1000,
            success: function () {
                initializeTableWithLanguage(table);
            },
            error: function () {
                initializeTableWithoutLanguage(table);
            }
        });
    });

    function initializeTableWithLanguage(table) {
        $(table).DataTable({
            "language": {
                "url": lang
            },
            "pageLength": 25,
            "order": [[0, "asc"]], // Cambiado de "desc" a "asc"
            "responsive": true,
            "stateSave": true,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
        });
    }

    function initializeTableWithoutLanguage(table) {
        $(table).DataTable({
            "pageLength": 25,
            "order": [[0, "asc"]], // Cambiado de "desc" a "asc"
            "responsive": true,
            "stateSave": true,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
        });
    }

    function showFlashMessages() {
        const flashMessages = document.querySelectorAll('.alert.alert-dismissible');

        flashMessages.forEach(function (alert) {
            const type = alert.classList.contains('alert-success') ? 'success' : 'error';
            const title = type === 'success' ? '¡Excelente!' : '¡Error!';

            Swal.fire({
                icon: type,
                title: title,
                text: alert.textContent.trim(),
                timer: 3000,
                timerProgressBar: true,
                confirmButtonText: '¡Entendido!',
                confirmButtonColor: 'var(--secondary)',
                showCloseButton: true
            });
            alert.remove();
        });
    }

    showFlashMessages();
}

function slowInitDatatables() {
    setTimeout(function () {
        initDatatables();
    }, 1200);
}

function initTiny() {
    if (document.querySelector("textarea") != null) {
        setTimeout(function () {
            tinymce.init({
                selector: "textarea",
                plugins: 'advlist autolink lists link charmap preview anchor pagebreak code',
                toolbar_mode: 'floating',
                forced_root_block: '<br>',
                height: 250,
                language: 'es',
                branding: false,
                setup: function (editor) {
                    editor.on('change', function () {
                        tinymce.triggerSave();
                    });
                }
            });
        }, 500);
    }
}

function hideSuperAdminFunctions() {
    let createButtons = document.querySelectorAll('.btn-add');
    let editButtons = document.querySelectorAll('.edit');
    let deleteButtons = document.querySelectorAll('.delete-form');

    if (createButtons) {
        createButtons.forEach((createBtn) => {
            createBtn.classList.add("block-superadmin-btn");
        });
    }

    if (editButtons) {
        editButtons.forEach((editBtn) => {
            editBtn.classList.add("block-superadmin-btn");
        });
    }

    if (deleteButtons) {
        deleteButtons.forEach((deleteBtn) => {
            deleteBtn.classList.add("block-superadmin-btn");
        });
    }
}