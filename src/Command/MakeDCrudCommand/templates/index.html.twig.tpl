{% extends 'base_admin.html.twig' %}

{% block title %}{{entity_name}} index{% endblock %}

{% block body %}
    <div class="container-fluid page">
        <div class="card">
            <div class="card-header d-row justify-content-between align-items-center">
                <div class="col-12 col-md-6 col-xxl-9 text-center text-md-start mb-3 mb-md-0">
                    <h1>Listado de {{entity_plural_name}}</h1>
                </div>
                <div class="col-12 col-md-6 col-xxl-3">
                    <div class="d-row">
                        <div class="col-6 text-center text-md-end">
                            <a href="{{ path('{{route_prefix}}_new') }}" class="btn btn-add">
                                <i class="fas fa-plus"></i> Crear {{entity_name}}
                            </a>
                        </div>
                        <div class="col-6 text-center text-md-end">
                            <a href="{{ path('{{route_prefix}}_excel') }}" class="btn btn-excel">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla-{{entity_var_plural}}" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                {{table_headers}}
                                {{#has_timestamps}}
                                <th>Creado</th>
                                <th>Actualizado</th>
                                {{/has_timestamps}}
                                {{#has_is_active}}
                                <th>Status</th>
                                {{/has_is_active}}
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        {% for {{entity_var_singular}} in {{entity_var_plural}} %}
                            <tr>
                                <td>{{ {{entity_var_singular}}.id }}</td>
                                {{table_data}}
                                {{#has_timestamps}}
                                <td>{{ {{entity_var_singular}}.createdAt ? {{entity_var_singular}}.createdAt|date('d/m/Y H:i') : '' }}</td>
                                <td>{{ {{entity_var_singular}}.updatedAt ? {{entity_var_singular}}.updatedAt|date('d/m/Y H:i') : '' }}</td>
                                {{/has_timestamps}}
                                {{#has_is_active}}
                                <td>
                                    {% if {{entity_var_singular}}.isActive %}
                                        <a href="{{ path('{{route_prefix}}_set_status', {'id': {{entity_var_singular}}.id}) }}"
                                           class="entity-active">
                                            <i class="fa-solid fa-circle-check"></i> Activo
                                        </a>
                                    {% else %}
                                        <a href="{{ path('{{route_prefix}}_set_status', {'id': {{entity_var_singular}}.id}) }}"
                                           class="entity-inactive">
                                            <i class="fa-solid fa-circle-xmark"></i> Inactivo
                                        </a>
                                    {% endif %}
                                </td>
                                {{/has_is_active}}
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ path('{{route_prefix}}_show', {'id': {{entity_var_singular}}.id}) }}" class="btn show"
                                           title="Ver" data-action="show-entity">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ path('{{route_prefix}}_edit', {'id': {{entity_var_singular}}.id}) }}" class="btn edit"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        {{ include('{{template_path}}/_delete.html.twig', {'{{entity_var_singular}}': {{entity_var_singular}}, 'show_label': false}) }}
                                    </div>
                                </td>
                            </tr>
                        {% endfor %}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="modalContainer"></div>
{% endblock %}