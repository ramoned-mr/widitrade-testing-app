<div class="modal fade show-entity-modal" id="show{{entity_name}}Modal" tabindex="-1" aria-labelledby="showEntityModalLabel"
     aria-hidden="true" data-backdrop="static" data-mdb-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable show-entity-dialog">
        <div class="modal-content show-entity-content">
            <div class="modal-header show-entity-header">
                <h5 class="modal-title show-entity-title" id="showEntityModalLabel">Detalle de {{entity_name}}</h5>
                <button type="button" class="btn-close show-entity-close" data-mdb-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body show-entity-body">
                <div class="table-responsive show-entity-table-container">
                    <table class="table table-bordered show-entity-table">
                        <tbody>
                        <tr>
                            <th class="table-dark show-entity-header-cell">ID</th>
                            <td class="show-entity-data-cell">{{ {{entity_var_singular}}.id }}</td>
                        </tr>
                        {{show_data}}
                        {{#has_timestamps}}
                        <tr>
                            <th class="table-dark show-entity-header-cell">Fecha de creación</th>
                            <td class="show-entity-data-cell">{{ {{entity_var_singular}}.createdAt ? {{entity_var_singular}}.createdAt|date('d/m/Y H:i:s') : '' }}</td>
                        </tr>
                        <tr>
                            <th class="table-dark show-entity-header-cell">Última actualización</th>
                            <td class="show-entity-data-cell">{{ {{entity_var_singular}}.updatedAt ? {{entity_var_singular}}.updatedAt|date('d/m/Y H:i:s') : '' }}</td>
                        </tr>
                        {{/has_timestamps}}
                        {{#has_is_active}}
                        <tr>
                            <th class="table-dark show-entity-header-cell">Status</th>
                            <td class="show-entity-data-cell">
                                <span class="badge {{ {{entity_var_singular}}.isActive ? 'bg-success' : 'bg-danger' }} show-entity-badge">
                                    {{ {{entity_var_singular}}.isActive ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                        {{/has_is_active}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer show-entity-footer">
                <button type="button" class="btn btn-close-modal" data-mdb-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<div data-mdb-ripple-init data-mdb-modal-init data-mdb-target="#show{{entity_name}}Modal" class="modalDispatcher"></div>