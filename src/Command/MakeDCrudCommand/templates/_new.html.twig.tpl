<div class="modal fade" id="new{{entity_name}}Modal" tabindex="-1" aria-labelledby="new{{entity_name}}ModalLabel" aria-hidden="true"
     data-backdrop="static" data-mdb-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable new-entity-dialog">
        <div class="modal-content new-entity-content">
            {{ form_start(form, {'attr': {'class': 'needs-validation new-{{entity_var_singular}}-form','action': path('{{route_prefix}}_new')}}) }}
            <div class="modal-header new-entity-header">
                <h5 class="modal-title" id="new{{entity_name}}ModalLabel">Crear nuevo {{entity_name}}</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body new-entity-body">
                {% if error_new is not null %}
                    <div class="alert alert-danger">
                        {{ error_new }}
                    </div>
                {% endif %}
                <div class="d-row">
                    {{ form_errors(form) }}

                    {{#form_fields}}
                    <div class="col-md-6 mb-4 px-1">
                        <div class="form-outline">
                            {{ form_widget(form.{{{field_name}}}, {
                                'attr': {
                                    'class': 'form-control',
                                    'id': '{{entity_var_singular}}_{{{field_name}}}'
                                }
                            }) }}
                            {{ form_label(form.{{{field_name}}}, null, {
                                'label_attr': {
                                    'class': 'form-label',
                                    'for': '{{entity_var_singular}}_{{{field_name}}}'
                                }
                            }) }}
                            <div class="form-notch">
                                <div class="form-notch-leading"></div>
                                <div class="form-notch-middle" style="width: 70px;"></div>
                                <div class="form-notch-trailing"></div>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            {{ form_errors(form.{{{field_name}}}) }}
                        </div>
                    </div>
                    {{/form_fields}}
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-close-modal" data-mdb-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-save" form="{{ form.vars.id }}">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
            {{ form_end(form) }}
        </div>
    </div>
</div>
<div data-mdb-ripple-init data-mdb-modal-init data-mdb-target="#new{{entity_name}}Modal" class="modalDispatcher"></div>