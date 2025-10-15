{% extends './layout/default' %}

{% block breadcrumbs %}
    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, 'templates') }}
{% endblock %}

{% block content %}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <span class="fa fa-plus-circle"></span>
                    {{ ('create_new_account')|_ }}
                </h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('accounts.all') }}" class="btn btn-default btn-sm">
                        <span class="fa fa-arrow-left"></span>
                        {{ ('back_to_accounts')|_ }}
                    </a>
                </div>
            </div>
            <div class="box-body">
                <p class="text-muted">
                    {{ ('choose_account_template_description')|_ }}
                </p>

                {% for categoryName, templates in templatesByCategory %}
                    <div class="row" style="margin-bottom: 30px;">
                        <div class="col-lg-12">
                            <h4 class="text-primary">
                                <span class="fa fa-{{ categoryName == 'Asset' ? 'money' : (categoryName == 'Liability' ? 'landmark' : 'chart-line') }}"></span>
                                {{ categoryName }} {{ ('accounts')|_ }}
                            </h4>
                            
                            <div class="row">
                                {% for template in templates %}
                                    <div class="col-md-6 col-lg-4" style="margin-bottom: 15px;">
                                        <div class="box template-card" style="cursor: pointer; min-height: 200px;" onclick="selectTemplate('{{ template.name }}')">
                                            <div class="box-body">
                                                <h5>
                                                    <span class="fa fa-{{ template.accountType.category.name == 'Asset' ? 'money' : (template.accountType.category.name == 'Liability' ? 'landmark' : 'chart-line') }}"></span>
                                                    {{ template.name }}
                                                </h5>
                                                <p class="text-muted small">
                                                    {{ template.description }}
                                                </p>
                                                {% if template.suggested_fields and template.suggested_fields|length > 0 %}
                                                    <div style="margin-top: 10px;">
                                                        <small class="text-info">
                                                            <span class="fa fa-info-circle"></span>
                                                            {{ ('suggested_fields')|_ }}: {{ template.suggested_fields|slice(0, 3)|join(', ') }}
                                                            {% if template.suggested_fields|length > 3 %}
                                                                +{{ template.suggested_fields|length - 3 }} {{ ('more')|_ }}
                                                            {% endif %}
                                                        </small>
                                                    </div>
                                                {% endif %}
                                            </div>
                                            <div class="box-footer">
                                                <button class="btn btn-primary btn-sm btn-block" onclick="selectTemplate('{{ template.name }}')">
                                                    <span class="fa fa-plus"></span>
                                                    {{ ('create_account')|_ }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        </div>
                    </div>
                {% endfor %}

                <!-- Custom Account Option -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h4 class="text-muted">
                        <span class="fa fa-cog"></span>
                        {{ ('custom_options')|_ }}
                    </h4>
                    <div class="row">
                        <div class="col-md-6 col-lg-4" style="margin-bottom: 15px;">
                            <div class="box text-center" style="min-height: 150px;">
                                <div class="box-body">
                                    <h5>
                                        <span class="fa fa-plus-circle"></span>
                                        {{ ('custom_asset_account')|_ }}
                                    </h5>
                                    <p class="text-muted">
                                        {{ ('create_custom_asset_description')|_ }}
                                    </p>
                                </div>
                                <div class="box-footer">
                                    <a href="{{ route('accounts.create', 'asset') }}" class="btn btn-default btn-sm btn-block">
                                        <span class="fa fa-plus"></span>
                                        {{ ('create_custom')|_ }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4" style="margin-bottom: 15px;">
                            <div class="box text-center" style="min-height: 150px;">
                                <div class="box-body">
                                    <h5>
                                        <span class="fa fa-landmark"></span>
                                        {{ ('custom_liability_account')|_ }}
                                    </h5>
                                    <p class="text-muted">
                                        {{ ('create_custom_liability_description')|_ }}
                                    </p>
                                </div>
                                <div class="box-footer">
                                    <a href="{{ route('accounts.create', 'liabilities') }}" class="btn btn-default btn-sm btn-block">
                                        <span class="fa fa-plus"></span>
                                        {{ ('create_custom')|_ }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}

<script>
function selectTemplate(templateName) {
    const baseUrl = "{{ route('accounts.templates.create', ['templateName' => 'PLACEHOLDER']) }}";
    window.location.href = baseUrl.replace('PLACEHOLDER', encodeURIComponent(templateName));
}
</script>

<style>
.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
</style>
@endsection
