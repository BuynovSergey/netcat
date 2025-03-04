<?php if (!class_exists('nc_core')) { die; } ?>

<style>
    .nc-routing-parameter { display: none; }
</style>
<?php

/** @var nc_routing_route $route */
/** @var int $site_id */
/** @var nc_ui $ui */

if (isset($error_message)) {
    echo $ui->alert->error($error_message);
}

$form = $ui->form("?controller=route&action=save")->vertical();
$form->add()->input('hidden', 'nc_token', $nc_core->token->get());
$form->add()->input('hidden', 'site_id', $site_id);
$form->add()->input('hidden', 'data[site_id]', $site_id);
$form->add()->input('hidden', 'data[id]', $route->get_id());

$row = $form->add_row();
$row->input('hidden', 'data[enabled]', '0');
$row->checkbox('data[enabled]', $route->get('enabled'), NETCAT_MODULE_ROUTING_ROUTE_IS_ENABLED)
    ->value('1');

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_PATTERN)
     ->string('data[pattern]', $route->get('pattern'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_DESCRIPTION)
     ->string('data[description]', $route->get('description'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_RESOURCE_TYPE)
     ->id('nc_routing_resource_type')
     ->select('data[resource_type]',
               array(
                   'folder' => NETCAT_MODULE_ROUTING_RESOURCE_FOLDER,
                   'infoblock' => NETCAT_MODULE_ROUTING_RESOURCE_INFOBLOCK,
                   'object' => NETCAT_MODULE_ROUTING_RESOURCE_OBJECT,
                   'script' => NETCAT_MODULE_ROUTING_RESOURCE_SCRIPT,
               ),
               $route->get('resource_type'));

// ---- Настройки маршрутизации для конкретных типов ресурсов ---
$params = $route->get('resource_parameters');
// ТИП РЕСУРСА: РАЗДЕЛ
$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_FOLDER)
     ->class_name('nc-routing-parameter nc-routing-folder')
     ->string('folder[folder_id]', nc_array_value($params, 'folder_id'))
     ->xlarge();

// ТИП РЕСУРСА: ИНФОБЛОК
$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_FOLDER)
     ->class_name('nc-routing-parameter nc-routing-infoblock')
     ->string('infoblock[folder_id]', nc_array_value($params, 'folder_id'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_RESOURCE_INFOBLOCK)
     ->class_name('nc-routing-parameter nc-routing-infoblock')
     ->string('infoblock[infoblock_id]', nc_array_value($params, 'infoblock_id'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_ACTION)
     ->class_name('nc-routing-parameter nc-routing-infoblock')
     ->select('infoblock[action]',
              array(
                  '' => NETCAT_MODULE_ROUTING_INFOBLOCK_ACTION_DEFAULT,
                  'index' => NETCAT_MODULE_ROUTING_INFOBLOCK_ACTION_INDEX,
                  'add' => NETCAT_MODULE_ROUTING_INFOBLOCK_ACTION_ADD,
                  'search' => NETCAT_MODULE_ROUTING_INFOBLOCK_ACTION_SEARCH,
                  'subscribe' => NETCAT_MODULE_ROUTING_INFOBLOCK_ACTION_SUBSCRIBE,
              ),
              nc_array_value($params, 'action', 'index'));

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_FORMAT)
     ->class_name('nc-routing-parameter nc-routing-infoblock')
     ->select('infoblock[format]',
              array('html' => 'HTML', 'xml' => 'XML', 'rss' => 'RSS'),
              nc_array_value($params, 'format', 'html'));

// ТИП РЕСУРСА: ОБЪЕКТ
$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_FOLDER)
     ->class_name('nc-routing-parameter nc-routing-object')
     ->string('object[folder_id]', nc_array_value($params, 'folder_id'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_RESOURCE_INFOBLOCK)
     ->class_name('nc-routing-parameter nc-routing-object')
     ->string('object[infoblock_id]', nc_array_value($params, 'infoblock_id'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_RESOURCE_OBJECT)
     ->class_name('nc-routing-parameter nc-routing-object')
     ->string('object[object_id]', nc_array_value($params, 'object_id'))
     ->xlarge();

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_ACTION)
     ->class_name('nc-routing-parameter nc-routing-object')
     ->select('object[action]',
              array(
                  'full' => NETCAT_MODULE_ROUTING_OBJECT_ACTION_FULL,
                  'edit' => NETCAT_MODULE_ROUTING_OBJECT_ACTION_EDIT,
                  'delete' => NETCAT_MODULE_ROUTING_OBJECT_ACTION_DELETE,
                  'drop' => NETCAT_MODULE_ROUTING_OBJECT_ACTION_DROP,
                  'checked' => NETCAT_MODULE_ROUTING_OBJECT_ACTION_CHECKED,
                  'subscribe' => NETCAT_MODULE_ROUTING_OBJECT_ACTION_SUBSCRIBE,
              ),
              nc_array_value($params, 'action', 'full'));

$form->add_row(NETCAT_MODULE_ROUTING_ROUTE_FORMAT)
     ->class_name('nc-routing-parameter nc-routing-object')
     ->select('object[format]',
              array('html' => 'HTML', 'xml' => 'XML', 'rss' => 'RSS'),
              nc_array_value($params, 'format', 'html'));

// ТИП РЕСУРСА: СКРИПТ
$form->add_row(NETCAT_MODULE_ROUTING_SCRIPT_PATH)
     ->class_name('nc-routing-parameter nc-routing-script')
     ->string('script[script_path]', nc_array_value($params, 'script_path'))
     ->xlarge();

// --- Переменные ---
$form->add_row(NETCAT_MODULE_ROUTING_ADDITIONAL_VARIABLES)
     ->string('query_variables',
              $route->get('query_variables')
                  ? http_build_query($route->get('query_variables'), null, '&')
                  : '')
     ->xlarge();

/** @var nc_ui_html $row */
$row = $form->add_row();
$row->input('hidden', 'data[query_variables_required_for_canonical]', '0');
$row->checkbox('data[query_variables_required_for_canonical]',
               $route->get('query_variables_required_for_canonical'),
               NETCAT_MODULE_ROUTING_ADDITIONAL_VARIABLES_FOR_CANONICAL)
    ->value('1');

echo $form;

?>
<script>
(function() {
    var select = $nc('#nc_routing_resource_type');
    function show_parameters() {
        $nc('.nc-routing-parameter').hide()
            .filter('.nc-routing-' + select.find('option:selected').val()).show();
    }
    select.change(show_parameters);
    show_parameters();
})();
</script>
