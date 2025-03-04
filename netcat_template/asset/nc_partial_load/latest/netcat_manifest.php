<?php

// Данные о расположении страниц, загруженных через POST (add.php, message.php):
if ($_POST && ($GLOBALS['action'] === 'add' || $GLOBALS['action'] === 'change')) {
    $nc_core = nc_core::get_object();
    $_page_params = http_build_query(array(
        'catalogue' => $nc_core->input->fetch_post('catalogue'),
        'sub' => $nc_core->input->fetch_post('sub'),
        'cc' => $nc_core->input->fetch_post('cc'),
        'admin_mode' => $nc_core->input->fetch_post('admin_mode'),
        'posting' => $nc_core->input->fetch_post('posting'),
    ), null, '&');
    // nc_partial_data: см. формирование url в reloadPartials() в nc_partial_load.js
    $nc_core->page->add_javascript("nc_partial_data = '?$_page_params';\n", false);
}

// Скрипт загрузки фрагментов страницы
return array(
    'require' => array('nc_event_dispatch@latest'),
    'js' => array('nc_partial_load.min.js'),
);
