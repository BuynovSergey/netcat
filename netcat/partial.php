<?php

/**
 * Возвращает содержимое фрагментов страницы (врезок макетов дизайна, инфоблоков,
 * областей).
 *
 * Для врезок в настройках должна быть разрешена их асинхронная загрузка.
 * 
 * Входящие параметры:
 *  — template — идентификатор или ключевое слово макета дизайна.
 *  — partial — идентификатор фрагмента (можно несколько через пробел или запятую, или в массиве):
 *    · для врезки — ключевое слово;
 *    · для инфоблока — идентификатор;
 *    · для области — '@' + ключевое слово области [не реализовано в текущей версии].
 *    Если в partial передаются данные через параметр $data, то к ключевому слову дописывается '?' и
 *    дополнительные параметры в виде query-строки (например: 'footer?mobile=1&user_name=John+Doe');
 *  — json — если 1, вернёт результат в виде JSON; если не указан, будет возвращено
 *    содержимое фрагментов «как есть» (все запрошенные фрагменты подряд).
 *  — referer — страница, для которой загружается врезка.
 *  — любые другие переданные параметры будут также доступны в соответствующих переменных.
 */

require_once __DIR__ . '/connect_io.php';
$nc_core = nc_core::get_object();

try {
    $nc_fragment_loader = new nc_partial_loader($nc_core->input->fetch_post_get() ?: array());
    $partials_content = $nc_fragment_loader->get_partials_content();
} catch (nc_partial_loader_exception $e) {
    $partials_content = array('_error' => $e->getMessage());
}

if ($nc_core->input->fetch_post_get('json')) {
    ob_clean();
    header('Content-Type: application/json');
    echo nc_array_json($partials_content ?: new stdClass());
} else {
    echo join('', $partials_content);
}
