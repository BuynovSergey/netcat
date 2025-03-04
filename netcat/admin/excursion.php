<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR;
include_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ROOT_FOLDER . "connect_io.php";
$nc_core = nc_Core::get_object();
//подтребовалось для совместимости с PHP 8
if ((int) PHP_VERSION == 8) {
    $nc_core->user->attempt_to_authorize();
}
//инициализируем путь к папке CMS
$path_to_netcat = '';
if ($nc_core->SUB_FOLDER != '') {$path_to_netcat = '/'.$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH;}
    else {$path_to_netcat = $nc_core->HTTP_ROOT_PATH;}

//проверяем, есть ли уже записи с текущим пользователем: потребовалось для совместимости с PHP 8 
$usr_exist = $nc_core->db->get_results("SELECT `User_ID` FROM `Excursion` WHERE `User_ID` = $AUTH_USER_ID ");
//заносим пользователя в таблицу учета посещений для показа экскурсии 
if (!$usr_exist) {
    $nc_core->db->query("REPLACE INTO
                        `Excursion`
                    SET
                        `User_ID` = $AUTH_USER_ID
                    ");
}
//если пришел аякс-запрос из настроек показа экскурсии
$set_usr_id = (int)$nc_core->input->fetch_post('set_user_id');
$set_next = (int)$nc_core->input->fetch_post('set_next');

if ($set_usr_id)
{
    $nc_core->db->query("UPDATE
                        `Excursion`
                    SET
                        `User_ID` = $set_usr_id,
                        `ShowNext` = $set_next    
                    ");
    exit();
}

if (file_exists(nc_module_folder('netshop') . 'nc_netshop.class.php')) {
    include_once nc_module_folder('netshop') . 'nc_netshop.class.php';
}

if (file_exists(nc_module_folder('landing') . 'nc_landing.class.php')) {
    include_once nc_module_folder('landing') . 'nc_landing.class.php';
}

$user_id = (int)$nc_core->input->fetch_get_post('user_id');
$show_next = (int)$nc_core->input->fetch_get_post('shownext');
$slide_id = (int)$nc_core->input->fetch_get_post('slide_id');
$get_cookie = (int)$nc_core->input->fetch_get_post('get_cookie');

//если пришел аякс-запрос на отмену экскурсии (клиент нажал "нет")
if (($user_id && $show_next == 0) || $slide_id || $get_cookie == 1)
{
    if ($user_id && $show_next == 0) {
	$nc_core->db->query("REPLACE INTO `Excursion` SET `User_ID` = $user_id, `ShowNext` = 0");
    }
    if ($slide_id) {
        setcookie("slide_id", $slide_id);
    }
    if ($get_cookie == 1 && isset($_COOKIE['slide_id'])) {
        echo (int)$_COOKIE['slide_id'];
    }
    exit;
}

//инициализируем массив модулей
$module_list = array();
//заносим список имеющихся модулей в массив
foreach ($nc_core->modules->get_module_vars() as $name => $row)
{
    array_push($module_list, $name);
}



//определение первого раздела (и сайта, содержащего этот раздел) со списком или с одним объектом переданного компонента
function getTargets($class_id)
{
    global $nc_core;
    $target_sub = 0;
    $target_cat = 0;
    $nc_core->db->query("SELECT `Subdivision_ID`,`Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` = $class_id");
    $arr = $nc_core->db->last_result;
    if (!empty($arr)) {
        //ищем первый включенный раздел со списком товаров или одним товаром
        foreach ($arr as $row) {
            $nc_core->db->query("SELECT `Message_ID` FROM `Message$class_id` WHERE `Subdivision_ID` = $row->Subdivision_ID AND `Sub_Class_ID` = $row->Sub_Class_ID");
            $arr2 = $nc_core->db->last_result;
            if (is_array($arr2) && count($arr2) >= 1 && $nc_core->subdivision->get_by_id($row->Subdivision_ID, "Checked" ))
            {
                $target_sub = $row->Subdivision_ID;
                $target_cat = $nc_core->subdivision->get_by_id($row->Subdivision_ID, "Catalogue_ID" );
                break;
            }
        }
        //в случае неудачи ищем первый выключенный раздел со списком товаров или одним товаром
        foreach ($arr as $row) {
            $nc_core->db->query("SELECT `Message_ID` FROM `Message$class_id` WHERE `Subdivision_ID` = $row->Subdivision_ID AND `Sub_Class_ID` = $row->Sub_Class_ID");
            $arr2 = $nc_core->db->last_result;
            if (is_array($arr2) && count($arr2) >= 1)
            {
                $target_sub = $row->Subdivision_ID;
                $target_cat = $nc_core->subdivision->get_by_id($row->Subdivision_ID, "Catalogue_ID" );
                break;
            }
        }
    }
    $result = array();
    $result['target_sub'] =  $target_sub;
    $result['target_cat'] =  $target_cat;
    return $result;
}

//поиск нужных разделов для демонстрации экскурсии (в приоритете разделы с товарами или просто разделы со списом объектов)
$target_sub = 0; //инициализируем целевой раздел
$target_cat = 0; //инициализируем целевой сайт
$target_template = 0; //инициализируем целевой макетsetcookie ("TestCookie", $value);
$target_info = 0; //инициализируем целевой инфоблок
$target_class = 0;//инициализируем целевой компонент
$no_objects = 0;

$targets = array();

if (!nc_module_check_by_keyword('netshop')) {
    unset($module_list[array_search('bills', $module_list)]);
}

if (nc_module_check_by_keyword('netshop')) {
    $netshop = nc_netshop::get_instance();
    $goods = $netshop->get_goods_components_data();

    if ($goods) {
        foreach ($goods as $good) {
            //берем номер компонента и ищем, если ли какой-либо раздел со списком объектов этого компонента
            $targets = getTargets((int)$good['Class_ID']);

            $target_sub = $targets['target_sub'];
            $target_cat = $targets['target_cat'];
            $target_class = (int)$good['Class_ID'];
            if ($target_sub && $target_cat) {break;}
        }
    }
}

// в случае неудачи найти раздел с товаром, ищем обычный раздел
if (!($target_sub && $target_cat))
{
    $nc_core->db->query("SELECT `Class_ID` FROM `Class`");
    $arr = $nc_core->db->last_result;
    if (!empty($arr)) {
        //ищем первый включенный раздел со списком товаров или одним товаром
        foreach ($arr as $row) {
            //берем номер компонента и ищем, если ли какой-либо раздел со списком объектов этого компонента
            $targets = getTargets((int)$row->Class_ID);
            $target_sub = $targets['target_sub'];
            $target_cat = $targets['target_cat'];
            $target_class = (int)$row->Class_ID;
            if ($target_sub && $target_cat) {break;}
        }
    }
}

if (!($target_sub && $target_cat)){

    $res = $nc_core->db->get_row("SELECT `Subdivision_ID`, `Catalogue_ID` FROM `Subdivision`");
    if (!empty($res)) {
        $target_sub_candidate = (int)$res->Subdivision_ID;
        $target_cat = (int)$res->Catalogue_ID;


        //смотрим, есть ли разделы, у которых есть объекты
        $nc_core->db->query("SELECT `Sub_Class_ID` FROM `Sub_Class' WHERE `Subdivision_ID`=$target_sub");
        $arr = $nc_core->db->last_result;
        if (!empty($arr)) {
            foreach ($arr as $row) {
                if (!nc_objects_list($target_sub, (int)$row->Sub_Class_ID)) {$target_sub = 0;}
                else $target_sub = $target_sub_candidate;
            }
        }
        else
        {
            $no_objects = 1;

        }
    }
    else {

        $res = $nc_core->db->get_row("SELECT `Catalogue_ID` FROM `Catalogue`");
        if (!empty($res)) {$target_cat = (int)$res->Catalogue_ID;}
    }
}

//переменные-индикаторы перехода на добавление сайта или раздела при начале экскурсии

$add_site = 0;
$add_sub = 0;


//опишем действия, если разделы с объектами вообще не найдены
if (!$target_cat)
{
    $add_site = 1;

    //слайд с добавлением сайта и перезапуск экскурсии, пока что заблокировано
   /* $data = array(
         array('ID' => 2, 'Module' => '', 'Header' => 'Давайте добавим сайт', 'Text' => 'В данный момент в вашей CMS нет ни одного сайта, предлагаем вам добавить сайт для начала экскурсии по административной панели. Введите название сайта и его домен (домен должен быть активным) и нажмите на кнопку «Добавить сайт». Если вы добавили пустой сайт, то рекомендуем сразу выбрать для него макет во вкладке «Оформление» (если он есть у вас в CMS). После добавления сайта нажмите на  <a href="'.$path_to_netcat.'admin/?is_new_step=1">эту ссылку </a>, после чего должна запуститься экскурсия по системе.', 'url' => $path_to_netcat.'admin/', 'ext_url' => '', 'reboot' => 1),
    ); */


}
else if(!$target_sub)
{
    exit(); //заглушка для случая пустого сайта
    $add_sub = 1;
    //слайд с добавлением раздела и перезапуск экскурсии, пока что заблокировано
   /* $data = array(
        array('ID' => 2, 'Module' => '', 'Header' => 'Давайте добавим раздел и несколько объектов', 'Text' => 'Для начала работы экскурсии нужно добавить раздел с объектами. Введите название и ключевое слово раздела, а также выберите нужный вам компонент (можно любой) из списка, после чего нажмите на кнопку «Добавить раздел». После этого в созданный раздел добавьте один или несколько объектов любого содержания с помощью кнопки «Добавить», после чего можно будет начинать экскурсию. Возможно, у вас уже есть раздел с инфоблоком, тогда нужно просто добавить в него хотя бы один объект. После добавления раздела нажмите на  <a href="'.$path_to_netcat.'admin/?is_new_step=1">эту ссылку</a> , после чего должна запуститься экскурсия по системе.', 'url' => $path_to_netcat.'admin/', 'ext_url' => '', 'reboot' => 1),
    ); */

}
else if (($target_sub && $target_cat)){

    $target_template = $nc_core->catalogue->get_by_id($target_cat, "Template_ID");

    $res_tmp = $nc_core->db->get_row("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` = $target_class AND `Subdivision_ID` = $target_sub", ARRAY_A);
    $target_info = $res_tmp['Sub_Class_ID'];

    $landings_subclass_id = 0;
    if (nc_module_check_by_keyword('landing')) {

        $landings_subclass_id = nc_landing::get_instance($target_cat)->get_landings_list_infoblock_id();

        if (!$landings_subclass_id) {
            unset($module_list[array_search('landing', $module_list)]);
        }

    }
    //массив данных экскурсии
    $data = array(
        array('ID' => 2, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Управление контентом', 'Text' => 'Базовая функция системы — управление контентом. Делать это вы можете в режиме администрирования, а также на <a href="'.$path_to_netcat.'index.php?catalogue='.$target_cat.'&sub='.$target_sub.'&cc='.$target_info.'" target="_blank" >самом сайте</a>. На всех его страницах сверху вы увидите панель переключения режимов: просмотр или редактирование. Также в меню «еще» вы можете посмотреть/изменить настройки страницы (оформление, SEO и пр.), открыть макет дизайна и перейти в режим администрирования.', 'url' => '', 'ext_url' => '', 'reboot' => 0),
        array('ID' => 3, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Инфоблоки', 'Text' => 'Каждая страница состоит из инфоблоков. Это может быть блок текста, новости, товары, фотографии и т.д. В <a href="'.$path_to_netcat.'index.php?catalogue='.$target_cat.'&sub='.$target_sub.'&cc='.$target_info.'" target="_blank">интерфейсе сайта</a> вы увидите панельку, при помощи которой можно включить/выключить инфоблок, удалить, изменить настройки и добавить в него объект (новость, товар...). А для добавления инфоблока перейдем в соседнюю вкладку.', 'url' => $path_to_netcat.'admin/?is_excursion=1#subclass.list(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 4, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Управление инфоблоками', 'Text' => 'Здесь вы видите список всех инфоблоков. Их точно так же можно настраивать, включать-выключать, удалять, а также менять их последовательность на странице, перейти в программный код компонента, отвечающего за вывод информации. Кроме того, инфоблоки могут быть выведены на странице в столбец или в виде вкладок. И здесь же инфоблок можно добавить (кнопка на нижней панели).', 'url' => $path_to_netcat.'admin/?is_excursion=1#subclass.add(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 5, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Добавление инфоблока', 'Text' => 'Для инфоблока самое главное — выбрать компонент (тип данных инфоблока). Компонент определяет структуру данных (например, для товара это название, цена, фото, описание и пр.) и шаблон отображения. У некоторых компонентов может быть несколько шаблонов отображения (полный, короткий, мобильный и пр.), а также настройки отображения (во сколько колонок показывать, скрывать ли второстепенные данные).', 'url' => $path_to_netcat.'admin/?is_excursion=1#subclass.add(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 6, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Добавление инфоблока', 'Text' => 'Кроме того, вы можете настроить параметры доступа к инфоблоку: какие именно пользователи могут видеть его, добавлять или изменять данные в нем. Еще инфоблок может быть <a href="https://netcat.ru/developers/docs/components/mirror-infoblock/" target="_blank">зеркальным</a>: отображать информацию из другого инфоблока. Например, в разделе «О компании» можно показать данные из подраздела «Менеджеры».', 'url' => $path_to_netcat.'admin/?is_excursion=1#object.list(' . $target_info . ')', 'reboot' => 0),
        array('ID' => 7, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Редактирование', 'Text' => 'Здесь вам доступны те же возможности, но в более компактном виде. Кнопка добавления располагается внизу. Удаление элемента перемещает его в корзину. Если вы удалили его по ошибке, его можно восстановить: смотрите вкладку «Удаленные объекты». Все удаленные на сайте элементы также можно увидеть в меню «Инструменты → Корзина удаленных объектов».', 'url' => $path_to_netcat.'admin/?is_excursion=1#subdivision.design(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 8, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Контент, используемый в оформлении', 'Text' => 'Помимо содержательной части страницы вы можете менять другой контент: сквозной (элементы, используемые в оформлении — логотип, подписи внизу страницы и пр.) и динамические блоки на странице. Вы можете поменять это на всем сайте (в его настройках), а также на любом уровне структуры (нажмите на любой раздел в левой части экрана и затем на «Настройки»).', 'url' => $path_to_netcat.'admin/?is_excursion=1#site.map(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 9, 'Module' => '',  'Edition' => '', 'Demo' => 0,'Header' => 'Управление структурой сайта', 'Text' => 'Структура вашего сайта древовидная, каждый раздел может содержать несколько подразделов. Управлять ими можно как на этой странице, так и в левой колонке. Операции с разделом на этой странице и в левой части экрана (при наведении мышки на его название): изменить настройки раздела (иконка шестеренки), удалить его (крестик). В левой части экрана разделы можно также переносить в другое место.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.add(0,' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 10, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Добавление раздела', 'Text' => 'Для добавления подраздела в левой колонке наведите мышкой на будущий родительский раздел (а если вы добавляете раздел в корень сайта — на название сайта) и нажмите на иконку папки с плюсом. Также добавить подраздел можно на карте сайта (предыдущий шаг экскурсии, такая же же иконка) и во вкладке «Информация → Список разделов».', 'url' => $path_to_netcat.'admin/?is_excursion=1#subdivision.add(0,' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 11, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Добавление раздела', 'Text' => 'Основные поля формы — название раздела (будет выводиться в навигации по сайту и на странице раздела) и ключевое слово (будет использовано для адресации страниц раздела: например, для страницы «О компании» адрес может быть ВАШСАЙТ.ru/o-kompanii/). Также выберите компонент, при помощи которого будет показана информация на странице (новости, товары, вакансии...).', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.add(0,' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 12, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Добавление раздела', 'Text' => 'Если галочка «включен» не нажата, ссылки на раздел не будут видны в блоках навигации по сайту (меню), а в системе администрирования они будут отображены серым цветом. Совет: не включайте раздел, пока не внесете туда информацию. Включить раздел вы сможете потом в его настройках. А об остальных полях формы речь пойдет дальше. После добавления раздела вы сможете добавить в него контент.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.edit(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 13, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Изменение настроек раздела', 'Text' => 'Форма изменения настроек состоит из пяти вкладок. На второй вы можете поменять название раздела, включить/выключить его. Также, если макет дизайна подразумевает настройки (логотип, подпись внизу и пр.), их вы можете увидеть во вкладке «Дополнительные настройки». По умолчанию их значения унаследованы с верхнего уровня структуры. Менять эти настройки стоит лишь в том случае, если на разных страницах сайта эти области должны различаться.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.system(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 14, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Изменение настроек раздела', 'Text' => 'В системных настройках вы можете поменять права доступа к странице (об этом речь пойдет позже), внешнюю ссылку для адресации (ее менять нежелательно, т.к. поисковые машины могли уже проиндексировать страницу по этому адресу — перешедший на нее посетитель увидит «Страница не найдена»). Остальные настройки специфичны, прочитать про них можно в <a target="_blank" href="https://netcat.ru/developers/docs/structure/partition-management/">документации</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.seo(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 15, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поисковая оптимизация', 'Text' => 'Для каждой страницы вы можете указать все важные для поисковой оптимизации теги, а также настройки для файла sitemap.xml и правило отправки времени генерации страницы. Если это страница раздела, сделать это можно во вкладке «Настройки → SEO/SMO» или на самом сайте («Еще → Настройки страницы»). Обратите внимание: если на нижнем уровне структуры сайта мета-поля не заполнены, они будут наследоваться с верхнего уровня.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#object.list(' . $target_info . ')', 'reboot' => 0),
        array('ID' => 16, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поисковая оптимизация', 'Text' => 'Если же надо указать эти настройки для страницы объекта (товар, новость, статья), в режиме редактирования в списке объектов нажмите иконку карандаша и выберите вкладку «Дополнительно». Также это можно сделать на самой странице объекта («Еще → Настройки страницы»). Здесь же можно отредактировать контент для виджета «поделиться» в социальных сетях.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#catalogue.edit(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 17, 'Module' => 'routing', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поисковая оптимизация', 'Text' => 'Кроме этого, Неткэт позволяет настроить файл robots.txt, который используют поисковые машины. Еще один полезный инструмент — отслеживание <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.search.brokenlinks">неправильных (битых) ссылок</a> внутри сайта. Также оптимизатору будут полезны инструменты настройки <a href="'.$path_to_netcat.'/admin/?is_excursion=1#redirect.list(' . $target_cat . ')">переадресаций</a> (<a target="_blank" href="https://netcat.ru/developers/docs/system-tools/forwarding/">документация</a>) и <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.routing.route.list(' . $target_cat . ')">маршрутизации</a> — управления форматами URL-ов страницы (<a target="_blank" href="https://netcat.ru/developers/docs/modules/module-routing/">документация</a>).', 'url' => $path_to_netcat.'admin/?is_excursion=1#object.list('.$landings_subclass_id.')', 'reboot' => 0),
        array('ID' => 18, 'Module' => 'landing', 'Edition' => '', 'Demo' => 0, 'Header' => 'Промо-страницы', 'Text' => 'Здесь вы можете управлять своими промо-страницами (посадочные страницы, landing-page): создавать, редактировать, удалять. Также вы можете создавать шаблоны на основе созданных Вами промо-страниц, чтобы использовать их как основу для новых страниц.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#user.list()', 'reboot' => 0),
        array('ID' => 19, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Пользователи', 'Text' => 'Неткэт поддерживает неограниченное количество пользователей. Они могут быть как внешними (зарегистрированными на сайте и не имеющими доступ в систему администрирования), так и обладающие какими-либо административными правами. Зарегистрировать пользователя можно, например, через <a href="'.$path_to_netcat.'admin/?is_excursion=1#user.add()">систему администрирования</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#user.add()', 'reboot' => 0),
        array('ID' => 20, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Регистрация пользователя', 'Text' => 'При регистрации пользователя вам надо заполнить необходимые поля: логин, пароль, будет ли у него доступ в административную часть, группу (о группах речь пойдет дальше) и т. д. Вы можете самостоятельно управлять составом полей регистрационной формы: добавить туда аватар, телефон и т.д. Делается это на странице управления системной таблицей <a href="'.$path_to_netcat.'admin/?is_excursion=1#systemclass_fs.edit(3)">«Пользователи»</a> («Разработка → Системные таблицы»).', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#user.rights(1)', 'reboot' => 0),
        array('ID' => 21, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Права пользователя', 'Text' => 'У каждого пользователя может быть несколько экземпляров прав. Например, вы можете назначить его администратором определенных разделов (новости, вакансии, товары...), указав, на какие именно операции вы даете права: добавление, просмотр, модерирование. изменение настроек. Редактор сайта получает права на весь сайт, а супервизор и директор имеют полные права в системе.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.system(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 22, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Ограничения доступа', 'Text' => 'Каждый раздел имеет определенные настройки доступа на просмотр, добавление и редактирование контента. По умолчанию смотреть могут все, а остальное — только те пользователи, у которых есть соответствующие права: директор, супервизор, редактор сайта/раздела. Настройки доступа можно менять, при этом, они поменяются и для всех подразделов данного раздела.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#subdivision.system(' . $target_sub . ')', 'reboot' => 0),
        array('ID' => 23, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Ограничения доступа', 'Text' => 'Например, вам надо сделать форму регистрации на мероприятие. Добавляем на страницу с описанием инфоблок «Регистрация на мероприятие» с действием по умолчанию «добавление», и указываем права на добавление «всем», а на просмотр — «уполномоченным». Теперь посетители увидят форму и смогут заполнить её, а администраторы (те, у кого есть права на просмотр в этом разделе) — увидят заявки.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#usergroup.list()', 'reboot' => 0),
        array('ID' => 24, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Группы пользователей', 'Text' => 'При регистрации пользователь попадает в определенную группу (это можно настроить), и у вас есть возможность задать этой группе любой набор прав так же, как и пользователю. Все пользователи группы будут иметь те же права в довесок к своим правам. Пользователи с правами директора и супервизора могут изменить группу любого пользователя. Один пользователь может входить в несколько групп.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.auth.general', 'reboot' => 0),
        array('ID' => 25, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Внешние пользователи', 'Text' => 'Неткэт включает модуль «Личный кабинет», который поддерживает весь цикл регистрации на сайте, включая вход через социальные сети, смену настроек и пароля, организацию доступа к собственным материалам и заказам, функции UGC и т. д. На этой странице вы можете настроить правила регистрации, шаблоны писем и другие шаблоны для разработчиков.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#object.list(' . $target_info . ')', 'reboot' => 0),
        array('ID' => 26, 'Module' => 'netshop', 'Edition' => '', 'Demo' => 0, 'Header' => 'Средства электронной коммерции', 'Text' => 'Начнем с основ: управление товарами. Редактировать их можно так же, как и остальные виды контента — на сайте в режиме редактирования и в системе администрирования. Разница в том, что у товара есть <a target="_blank" href="https://netcat.ru/developers/docs/modules/module-netshop/options-cc/" >варианты</a> — размер, цвет, объем и т. д. Список полей для каждого типа товара и шаблон его отображения можно менять (эта функция относится к инструментам разработчика).', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.1c.sources', 'reboot' => 0),
        array('ID' => 27, 'Module' => 'netshop', 'Edition' => 'full', 'Demo' => 0, 'Header' => 'Пакетная работа с товарами', 'Text' => 'Для магазинов с большим ассортиментом удобнее будет другой способ работы с товарами: выгрузка номенклатуры и цен из программ 1С. А в 1С могут поступать заказы из магазина. Синхронизация может происходить автоматически или по действию оператора. Настроить синхронизацию между Netcat и 1С вам поможет <a href="https://netcat.ru/developers/docs/#all" target="_blank">документация</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.order(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 28, 'Module' => 'netshop', 'Edition' => '', 'Demo' => 0, 'Header' => 'Обработка заказов', 'Text' => 'Все заказы поступают в кабинет оператора («Магазин → Продажи»), где поддерживаются все необходимые операции с заказами: отгрузка, изменения статуса, печать бланков (бухгалтерских, на доставку), редактирование состава и данных покупателя, дублирование, объединение и т.д.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.promotion.discount.item.add(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 29, 'Module' => 'netshop', 'Edition' => 'full', 'Demo' => 0, 'Header' => 'Управление скидками', 'Text' => 'В Неткэте внедрена мощная и гибкая система скидок. Вы можете назначать скидки практически на любые условия: типы товаров, параметры пользователя, время, состав корзины, историю заказов и т.д. Условия можно комбинировать друг с другом. Скидки можно применять и в каталоге, и в самой <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.netshop.promotion.discount.cart(' . $target_cat . ')">корзине</a>. Посмотрите несколько наиболее <a href="https://netcat.ru/developers/docs/modules/module-netshop/discounts/" target="_blank">показательных примеров</a> применения скидок.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.pricerule.add(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 30, 'Module' => 'netshop', 'Edition' => 'full', 'Demo' => 0, 'Header' => 'Ценовые колонки', 'Text' => 'Неткэт поддерживает не только скидки, но и ценовые колонки (например, опт, мелкий опт, розница...). Для этого вам надо добавить эти колонки в товарные компоненты и настроить условия. Это могут быть как простые признаки (например, принадлежность пользователя к некоторой группе), так и более сложные. Условия применения колонок конструируются таким же инструментом, как и скидки.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.delivery(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 31, 'Module' => 'netshop', 'Edition' => '', 'Demo' => 0, 'Header' => 'Условия доставки', 'Text' => 'В этом разделе могут находиться настроенные по умолчанию способы доставки, но вы можете добавить свои и удалить неиспользуемые. Каждый вид доставки можно <a href="https://netcat.ru/developers/docs/modules/module-netshop/shipping-options/" target="_blank">настроить</a>, указав, при каких условиях он применим. Условия доставки настраиваются также очень гибко.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.promotion.discount.cart.add(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 32, 'Module' => 'netshop', 'Edition' => 'full', 'Demo' => 0, 'Header' => 'Купоны и промо-коды', 'Text' => 'Неткэт поддерживает купоны как на корзину, так и на доставку. Купон может быть номинирован как в валюте (столько-то рублей), так и в процентах. При добавлении условия на скидку поставьте галочку «применять по купону», после чего система предложит вам сгенерировать указанное вами число купонов с выбранным вами количеством применений и при желании сразу разослать их.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.statistics(' . $target_cat . '))', 'reboot' => 0),
        array('ID' => 33, 'Module' => 'netshop', 'Edition' => '', 'Demo' => 0, 'Header' => 'Статистика продаж', 'Text' => 'На этой странице вы можете увидеть статистику продаж: по обороту и заказам за выбранный период времени, по самым продаваемым товарам, самым активным покупателям.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.payment(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 34, 'Module' => 'payment',  'Edition' => '', 'Demo' => 0, 'Header' => 'Способы оплаты', 'Text' => 'Вы можете настроить правила применения способов оплаты (наличными, электронными деньгами, картами, квитанцией, по счету) в зависимости от любых условий, примерно так же, как и условия применения скидок. Перед этим необходимо настроить модуль <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.settings(payment)">«Прием платежей»</a> согласно <a target="_blank" href="https://netcat.ru/developers/docs/module-payment/">документации</a>. Обратите внимание: для приема электронных денег и карт вам понадобится заключить договор с операторами (Робокасса, Яндекс.Деньги и пр.).', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.bills.information', 'reboot' => 0),
        array('ID' => 35, 'Module' => 'bills', 'Edition' => '', 'Demo' => 0, 'Header' => 'Выставление первичных документов', 'Text' => 'Модуль предназначен для создания счетов, актов к ним, отправки готовых документов на эл. почту либо распечатки. Модуль может работать как самостоятельно, так и в паре с модулем <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.payment">«Приём платежей»</a>. Подробнее о нем можно узнать в <a target="_blank" href="https://netcat.ru/developers/docs/modules/module-bills/">документации</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.netshop.currency(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 36, 'Module' => 'netshop', 'Edition' => 'full', 'Demo' => 0, 'Header' => 'Другие настройки магазина', 'Text' => 'В рамках магазина вы можете также управлять курсами валют, если ваш ассортимент мультивалютный (в т.ч. автоматически подгружать курсы с сайта ЦБ), настраивать <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.netshop.mailer.template(' . $target_cat . ')">шаблоны автоматических писем клиентам и менеджерам</a>, а также <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.netshop.forms">бланки</a> бухгалтерских документов и служб доставки. Также не забудьте настроить выгрузку ассортимента в <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.netshop.market.yandex('.$target_cat.')">Яндекс.Маркет</a> и другие товарные агрегаторы.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.search.generalsettings', 'reboot' => 0),
        array('ID' => 37, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поиск на сайте', 'Text' => 'В Неткэте встроен мощный и удобный модуль поиска с подсказками запроса, обработкой опечаток, синонимами (Netcat → Неткэт), заданием правил индексации разных разделов, информативными отчетами, настройкой шаблонов отображения и многое другое. На многих типах сайтов (например, магазинах) поиск играет важнейшую роль, и Неткэт позволяет вам настроить его на максимальную полезность.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#tools.patch(1)', 'reboot' => 0),
        array('ID' => 38, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Обновление Netcat', 'Text' => 'На весь период действия технической поддержки (год с момента покупки, дальше можно продлить) вы можете обновлять свою систему до новых версия совершенно бесплатно. Для этого ваша лицензия должна быть <a target="_blank" href="https://netcat.ru/registration/">зарегистрирована</a> на сайте netcat.ru.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#module.logging', 'reboot' => 0),
        array('ID' => 39, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Другие полезные инструменты', 'Text' => 'Также в работе вам будут полезны инструмент логирования (сохранение данных о действиях пользователей на сайте и в административной панели), создание <a href="'.$path_to_netcat.'admin/?is_excursion=1#tools.backup">архива проекта</a> и его восстановление, <a href="'.$path_to_netcat.'admin/?is_excursion=1#user.mail()">рассылки по базе пользователей</a>, <a href="'.$path_to_netcat.'admin/?is_excursion=1#tools.csv.export">экспорт данных с сайта</a> в формат CSV (например, для пакетной обработки в MS Excel), настройка <a href="'.$path_to_netcat.'admin/?is_excursion=1#module.comments.settings">комментирования материалов</a>'.( array_search("banner", $module_list) ? ", <a href=\"'.$path_to_netcat.'admin/?is_excursion=1#module.banner\">отчеты по внутренней рекламе</a> (если она используется на вашем сайте)" : "").'.', 'url' => '', 'reboot' => 0),
        array('ID' => 40, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Модули сторонних разработчиков', 'Text' => 'На <a target="_blank" href="https://netcat.ru/products/modules/?catstore">нашем сайте</a> вы можете скачать (или купить) десятки модулей наших партнеров, расширяющих функциональность сайта: формы, плагины для интеграции с другими сервисами, калькуляторы стоимости и многое другое.', 'url' => '', 'reboot' => 0),
        array('ID' => 41, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поддержка и развитие', 'Text' => 'Все пользователи Неткэта могут пользоваться <a target="_blank" href="https://netcat.ru/forclients/support/newticket/">технической поддержкой</a> разработчика платформы (её стоимость в течение первого года входит в стоимость, после этого её можно продлить). Для получения поддержки вам надо <a target="_blank" href="https://netcat.ru/registration/">зарегистрироваться</a> на сайте netcat.ru и <a target="_blank" href="https://netcat.ru/forclients/copies/add_copies.html">зарегистрировать вашу лицензию</a>. Если с этим у вас возникнут проблемы — <a target="_blank" href="mailto:info@netcat.ru">напишите нам</a>, мы поможем.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.list()', 'reboot' => 0),
        array('ID' => 42, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Инструменты разработчика', 'Text' => 'Инструменты разработчика Неткэт дают пользователю полный контроль над версткой, оформлением, навигацией, бизнес-логикой проекта, позволяют интегрировать сайт со сторонним ПО и сервисами, разрабатывать дополнительные модули. В освоении инструментов разработчика сам поможет подробная документация и служба поддержки. Начнем краткую экскурсию с компонентов.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.edit(' . $target_class . ')', 'reboot' => 0),
        array('ID' => 43, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Компоненты', 'Text' => 'Каждый компонент отвечает за какой-либо тип контента: товары, фотографии, люди. Самый простой компонент — обычный HTML-текст. Компонент отвечает за структуру контента (набор полей), формат вывода контента на сайте, шаблоны форм добавления/редактирования и поиска, логику действий при добавлении/изменении/удалении, обработку условий действий с контентом.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.fields(' . $target_class . ')', 'reboot' => 0),
        array('ID' => 44, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поля компонента', 'Text' => 'Каждому компоненту соответствует таблица с данными, структура которой определяется полями компонента. Если номер компонента 100, эта таблица будет называться Message100. Она содержит несколько служебных полей и те поля, которые вы видите на этой странице. Чтобы посмотреть её структуру, вы можете сделать запрос «explain MessageНОМЕР» в <a href="'.$path_to_netcat.'admin/?is_excursion=1#tools.sql">SQL-консоли</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.fields('.$target_class .')', 'reboot' => 0),
        array('ID' => 45, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поля компонента', 'Text' => 'Нажмите на название любого поля. Главные его свойства: название (колонка с этим именем будет создана в таблице с контентом, а также это название можно использовать в шаблоне вывода), описание (подпись к полю в формах добавления/изменения) и тип (строка, число, логическая, файл, список и пр.). Подробнее о свойствах полей — в <a target="_blank" href="https://netcat.ru/developers/docs/components/types-of-fields/">документации</a>.', 'url' => $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.edit('.$target_class.')', 'reboot' => 0),
        array('ID' => 46, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Шаблоны компонента', 'Text' => 'За вывод контента отвечает шаблон. Их может быть несколько, но как минимум один быть обязателен. Шаблон состоит из двух частей: объект в списке и (если тип контента подразумевает отдельную страницу для элемента) страница полного вывода объекта, в них можно использовать HTML и PHP. Шаблон объекта в списке в свою очередь состоит их префикса списка, тела записи и суффикса.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.edit('.$target_class.')', 'reboot' => 0),
        array('ID' => 47, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Шаблоны компонента', 'Text' => 'В шаблоне вывода объекта в списке и полного вывода можно использовать названия полей компонента и другие переменные, функции и методы Неткэта и PHP (см. <a target="_blank" href="https://netcat.ru/developers/docs/components/creating-cc/">документацию</a>). Практически любая задача, связанная со списками, легко реализуема в системе. Кроме полей шаблона важное поле здесь — PHP-консоль (код, который выполняется перед выводом контента).', 'url' => $path_to_netcat.'admin/?is_excursion=1#classtemplates_fs.edit(' . $target_class . ')', 'reboot' => 0),
        array('ID' => 48, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Шаблоны компонента', 'Text' => 'Компонент может иметь несколько шаблонов на усмотрение разработчика. Это и служебные шаблоны (для вывода контента в интерфейсе администратора или в корзине), и внешний (при добавлении инфоблока на страницу пользоватль сможет выбрать шаблон, при помощи которого показывать контент). Однако набор полей компонента для всех шаблонов одинаков.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.custom('.$target_class.')', 'reboot' => 0),
        array('ID' => 49, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Пользовательские настройки инфоблока', 'Text' => 'Каждый шаблон компонента вы можете кастомизировать: дать пользователю возможность настроить внешний вид контента. Например, это могут быть количество колонок в таблице, выводить ли какое-то поле, цвет какого-то элемента и пр. Вы добавляете настройку и в шаблоне компонента обращаетесь к ней в обычном синтаксисе PHP. Подробнее о настройках инфоблока — в <a target="_blank" href="https://netcat.ru/developers/docs/components/user-settings/">документации</a>.', 'url' => $path_to_netcat.'admin/?is_excursion=1#dataclass_fs.custom(' . $target_class . ')', 'reboot' => 0),
        array('ID' => 50, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Еще немного о компонентах', 'Text' => 'Компоненты — очень мощный и гибкий инструмент разработчика. При помощи этого инструмента вы можете создавать многоуровневые взаимосвязанные структуры данных, архивы материалов, системы трекинга и многое другое. Почитайте также про <a target="_blank" href="https://netcat.ru/developers/docs/components/mirror-infoblock/">зеркальные инфоблоки</a>, инлайн-редактирование <a target="_blank" href="https://netcat.ru/developers/docs/components/edit-in-place/">текста</a> и <a target="_blank" href="https://netcat.ru/developers/docs/components/edit-in-place-images/" >изображений</a>, <a target="_blank" href="https://netcat.ru/developers/docs/components/searching-and-selection/">поиск и фильтрацию контента</a>, <a target="_blank" href="https://netcat.ru/developers/docs/components/agregator/">агрегацию данных</a> из разных компонентов и <a target="_blank"  href="https://netcat.ru/developers/docs/components/api/">API</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#template_fs.list', 'reboot' => 0),
        array('ID' => 51, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Макеты дизайна', 'Text' => 'Обычно страница сайта под управлением CMS разделяется на три части: верхняя часть страницы (хедер), контентная часть и нижняя (футер). Неткэт поддерживает такой подход, но и позволяет сделать его более гибким и расширяемым (например, контентных частей может быть несколько). Принцип организации макета не накладывает практически никаких ограничений на внешний вид и применение современных технологий.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#template_fs.list', 'reboot' => 0),
        array('ID' => 52, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Макеты дизайна', 'Text' => 'Помимо оформительской роли с утилитарной точки зрения макет выполняет две основных функции: вывод навигации по сайту и вывод виджетов сайта (формы авторизации и поиска, последние новости, баннеры, виджет корзины и пр.). Макетов в системе может быть сколько угодно, они поддерживают иерархическую структуру («дочки» наследуют некоторые свойства родителя).', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#catalogue.design(' . $target_cat . ')', 'reboot' => 0),
        array('ID' => 53, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Использование макетов на сайте', 'Text' => 'На уровне сайта вы можете определить, каким макетом выводить страницы сайта. При этом на каждом уровне иерархии структуры сайта вы можете переопределить макет. Самый простой пример — на главной странице вы указываете макет другой макет, созданный именно для главной страницы. Или часть страниц могут выводиться при помощи отличающегося от «главного» макета.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#template_fs.edit(' . $target_template . ')', 'reboot' => 0),
        array('ID' => 54, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Макеты дизайна', 'Text' => 'В базовом случае макет состоит из шаблонов навигации (PHP-код, который выполняется перед отрисовкой макета; в первую очередь используется для определения шаблонов вывода меню на сайте), хедера и футера. В них в свою очередь выводится HTML-код страницы со вставками API: функции и методы вывода меню, контента из других разделов сайта (последние новости на главной), виджетов и т.д.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#template_fs.edit(' . $target_template . ')', 'reboot' => 0),
        array('ID' => 55, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Макеты дизайна', 'Text' => 'Поскольку макет исполняется как PHP-файл, вы полностью контролируете и логику, и верстку страниц сайта в зависимости от любых условий: текущий раздел, параметры зашедшего пользователя, содержимое cookie, операционная система, время суток и т.д. Посмотрите документацию по <a target="_blank"  href="https://netcat.ru/developers/docs/design/preparation-and-integration/">подготовке HTML-файла</a> к внедрению, <a target="_blank"  href="https://netcat.ru/developers/docs/navigation/functions/">внедрению навигации</a>, <a target="_blank"  href="https://netcat.ru/developers/docs/design/headers-and-meta-tags/">заголовкам и мета-тегам</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#template_fs.custom(' . $target_class . ')', 'reboot' => 0),
        array('ID' => 56, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Пользовательские настройки', 'Text' => 'Как и в шаблонах компонента, в макетах можно вводить настройки: логотип, откуда брать контент для боков-врезок, цветовая схема и т.д. Пользователь сможет переопределить эти настройки для всего сайта, ветки структуры или отдельного раздела. Подробно о пользовательских настройках см. <a target="_blank"  href="https://netcat.ru/developers/docs/design/user-settings/">документацию</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#template_fs.list', 'reboot' => 0),
        array('ID' => 57, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Наследование макетов', 'Text' => 'Если часть страниц сайта должна отличаться внешним видом от других страниц (как минимум, титульная страница должна отличаться от внутренних), чтобы не поддерживать несколько экземпляров кода макета, применяется <a target="_blank"  href="https://netcat.ru/developers/docs/design/inheritance/">наследование</a>. В дочернем макете можно использовать содержимое полей родителя (макропеременные %CSS, %Footer) и переопределять их. Также в макетах можно использовать <a target="_blank"  href="https://netcat.ru/developers/docs/design/additional-templates/">сниппеты</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#systemclass_fs.list', 'reboot' => 0),
        array('ID' => 58, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Системные таблицы', 'Text' => 'Здесь разработчик может управлять структурой системных таблиц. Самым очевидным примером является таблица «Пользователи»: вы можете добавить поля, которые он должен будет заполнить при регистрации, а также настроить внешний вид списка пользователей (если он будет на сайте) и условия/шаблоны удаления, добавления и изменения пользователя. Зачем здесь нужны остальные системные таблицы — секрет.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#tools.installmodule', 'reboot' => 0),
        array('ID' => 59, 'Module' => '', 'Edition' => '', 'Demo' => 1, 'Header' => 'Сторонние модули', 'Text' => 'Вы можете установить на ваш Неткэт любой сторонний модуль из <a target="_blank"  href="https://netcat.ru/products/modules/?catstore">каталога</a> разработок наших партнеров. Также вы можете разработать собственный модуль, прочитав перед этим <a target="_blank"  href="https://netcat.ru/developers/docs/module-development/">документацию</a>.', 'url' =>  $path_to_netcat.'admin/?is_excursion=1#cron.settings', 'reboot' => 0),
        array('ID' => 60, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Другие инструменты', 'Text' => 'Оставшиеся инструменты разработчика: управление задачами (запуск скриптов по расписанию — не забудьте <a target="_blank"  href="https://netcat.ru/developers/docs/system-tools/task-management/">настроить cron</a> на вашем сервере), <a href="'.$path_to_netcat.'admin/?is_excursion=1#tools.sql">SQL-консоль</a>, <a href="'.$path_to_netcat.'admin/?is_excursion=1#tools.databackup.export">инструменты экспорта-импорта</a> данных, <a href="'.$path_to_netcat.'admin/?is_excursion=1#redirect.list(' . $target_cat . ')">настройка переадресаций</a> (особенно если вы переводите сайт с другой системы на Неткэт). И еще несколько полезных инструментов разработчика описано в <a target="_blank"  href="https://netcat.ru/developers/docs/tools/multi-lingual/">документации</a>.', 'url' => '', 'reboot' => 0),
        array('ID' => 61, 'Module' => '', 'Edition' => '', 'Demo' => 0, 'Header' => 'Поддержка', 'Text' => 'Если же в чем-то вы не разберетесь, не стесняйтесь спрашивать <a target="_blank" href="https://netcat.ru/forclients/tech-support/">службу поддержки</a>! :-) ', 'url' =>  $path_to_netcat.'admin/?is_excursion=0', 'reboot' => 0),
    );

    //модификация массива слайдов в зависимости от набора модулей и редакции
    $iter = 0;
    $iter_prev = 0;
    $edition_type = (int) $nc_core->db->get_var(" SELECT `Value` FROM `Settings` WHERE `Key` = 'SystemID' ");
    if ($edition_type == 3 || $edition_type == 6)  {$edition_type = 'full';} else {$edition_type = '';}

    $edition_is_demo = (int) $nc_core->db->get_var(" SELECT `Value` FROM `Settings` WHERE `Key` = 'ProductNumber' ");
    if (!$edition_is_demo) {$edition_is_demo = 1;}

    foreach ($data as $row) {

        if ( ($data[$iter]['Module'] != '') && (!(array_search($data[$iter]['Module'], $module_list))) ){
            $data[$iter_prev - 1]['url'] = $data[$iter]['url'];
            unset($data[$iter]);
            if ($iter_prev) {$iter_prev--;}
        }
        else if (($data[$iter]['Edition']) && ($data[$iter]['Edition'] != $edition_type))
        {
            $data[$iter_prev - 1]['url'] = $data[$iter]['url'];
            unset($data[$iter]);
            if ($iter_prev) {$iter_prev--;}
        }
        else if (($data[$iter]['Demo']) && ($data[$iter]['Demo'] == $edition_is_demo))
        {
            $data[$iter_prev - 1]['url'] = $data[$iter]['url'];
            unset($data[$iter]);
            if ($iter_prev) {$iter_prev--;}
        }
        else{
            $iter_prev = $iter;
        }

        $iter++;
        $iter_prev++;
    }
    sort($data);
}
?>

<script>
    //сворачивание/разворачивание блока экскурсии
    function hide_show_excursion(flag_close) {
        if ($nc('.wrap_excursion').css("display") != 'none') {
            $nc('.wrap_excursion').css("display", 'none');
            $nc("#but_on_off").removeClass("nc-icon-arrow-circle-down").addClass("nc-icon-arrow-circle-up");
        }
        else {
            $nc('.wrap_excursion').css("display", 'block');
            $nc("#but_on_off").removeClass("nc-icon-arrow-circle-up").addClass("nc-icon-arrow-circle-down");
        }
        if (flag_close) {
            $nc('.excursion_hide').css("display", 'none');
        }
    }
</script>
<?
if (!empty($data) && !empty($_COOKIE['slide_id'])) {
?>
<div class="excursion_hide"
     style='position: fixed; z-index: 90000; width: 160px; height: 50px; top: 5px; right: 120px; cursor:pointer;  background: #1a87c2; background: rgba(0, 0, 0, 0) -moz-linear-gradient(left center , rgba(26, 135, 194, 0), #1a87c2 10%) repeat scroll 0 0; background: -webkit-gradient(linear, 0% 50%, 100% 50%, color-stop(0%, rgba(26, 135, 194, 0)), color-stop(10%, #1a87c2)); background: linear-gradient(to center right, rgba(26, 135, 194, 0), #1a87c2 10%);'  onclick="hide_show_excursion(0);" >
        <a style='color:white; font-size: 18px; position:relative; top:7px; left:20px;'>помощник</a><span id="but_on_off"
                                                                style="cursor:pointer; padding-left: 3px;color: #fff; position:relative; top:10px; left:20px;"
                                                                class="nc-icon-arrow-circle-down"
                                                                title="Свернуть/развернуть экскурсию"></span></div>

<? } ?>

<style type="text/css">
    .wrap_excursion {
        display: block;
        position: absolute;
        width: 200px;
        margin-top: 30px;
    }
</style>
<div class="wrap_excursion">
    <link href="<?= nc_add_revision_to_url('excursion/css/popup.css') ?>" rel="stylesheet" type="text/css"/>
    <?php
    $js_files = array(
        $nc_core->ADMIN_PATH . 'excursion/js/popup.js',
        $nc_core->ADMIN_PATH . 'excursion/scrollpane/intent.js',
        $nc_core->ADMIN_PATH . 'excursion/scrollpane/mousewheel.js',
        $nc_core->ADMIN_PATH . 'excursion/scrollpane/scrollpane.js',
        $nc_core->ADMIN_PATH . 'excursion/js/sidebar.js',
    );

    foreach (nc_minify_file($js_files, 'js') as $file) {
        echo "<script src='" . $file . "'></script>\n";
    }
    ?>

    <link href="<?= nc_add_revision_to_url('excursion/scrollpane/scrollpane.css') ?>" rel="stylesheet" type="text/css"/>
    <link href="<?= nc_add_revision_to_url('excursion/css/sidebar.css') ?>" rel="stylesheet" type="text/css"/>

    <?php
    //вывод слайдов экскурсии
    if (!empty($data) ) {
        ?>

        <div class="excursion" id="excursion_block" style='display:none; width: 200px;' >

            <div id='slide1' style="background: #666; display:none;">
                <div id='but1' style='display:none;' class="nc_ab_popupWindow">
                    <div class="nc_ab_popupWindow-overlay"></div>
                    <div class="nc_ab_popupWindow-content">
                        <div class="nc_ab_popupWindow-image"><img src="<?= $ADMIN_PATH ?>dashboard/img/netcat-logo-large.png" alt="netcat-logo"></div>

                        <div class="nc_ab_popupWindow-text">
                            <div class="nc_ab_popupWindow-title">Не знаете с чего начать?<br />Поможет экскурсия по Неткэт.</div>
                            <? if ($add_site == 0 && $add_sub == 0): ?>
                                <div class="nc_ab_popupWindow-about">Познакомьтесь с системой управления сайтами Неткэт за <? echo count($data);?> шагов. Помощник проведёт вас по нескольким экранам и покажет, как работают основные функции.</div>
                            <? elseif($add_site == 1):?>
                               <!-- временно заблокировано <div class="nc_ab_popupWindow-about">Познакомьтесь с системой управления сайтами Неткэт. Для возможности запуска экскурсии предлагаем выполнить инструкции по добавлению сайта.</div> -->
                            <? elseif($add_sub == 1):?>
                               <!-- временно заблокировано <div class="nc_ab_popupWindow-about">Познакомьтесь с системой управления сайтами Неткэт. Для возможности запуска экскурсии предлагаем выполнить инструкции по добавлению раздела с объектами. Если вы уже добавляли раздел, то добавьте в него <b>хотя бы один объект</b></div> -->
                            <? endif;?>

                        </div>

                        <div class="nc_ab_popupWindow-bottom">
                            <? if ($add_site == 0 && $add_sub == 0) :?>
                                <div class="nc_ab_popupWindow-row"><a style="color:#fff;"  onclick="next_stage('<? echo $path_to_netcat;?>'+'admin/?is_excursion=1#object.list(<?php echo $target_info; ?>)', 1, 1, '', 0)" class="nc_ab_popupWindow-button">Начать экскурсию</a></div>
                                <!-- временно заблокировано <a id="extr_url" href="<? echo $path_to_netcat;?>index.php?catalogue=<?php echo $target_cat; ?>&sub=<?php echo $target_sub; ?>&cc=<?php echo $target_info; ?>" target="_blank" hidden="true"></a> -->
                            <? elseif($add_site == 1): ?>
                                <div class="nc_ab_popupWindow-row"><a style="color:#fff;"  onclick="next_stage('<? echo $path_to_netcat;?>'+'admin/?is_excursion=1#site.add(0)', 1, 1, '', 0)" class="nc_ab_popupWindow-button">Добавить сайт</a></div>
                            <? elseif($add_sub == 1): ?>
                            	<? if ($no_objects == 1):?>
                            	 	<div class="nc_ab_popupWindow-row"><a style="color:#fff;"  onclick="next_stage('<? echo $path_to_netcat;?>'+'admin/?is_excursion=1#subdivision.add(0,<?php echo $target_cat; ?>)', 1, 1, '', 0)" class="nc_ab_popupWindow-button">Добавить объекты</a></div>
                            	<? else: ?>
                                	<div class="nc_ab_popupWindow-row"><a style="color:#fff;"  onclick="next_stage('<? echo $path_to_netcat;?>'+'admin/?is_excursion=1#subdivision.add(0,<?php echo $target_cat; ?>)', 1, 1, '', 0)" class="nc_ab_popupWindow-button">Добавить раздел</a></div>
                           	<? endif;?>
                            <? endif;?>
                            <div class="nc_ab_popupWindow-row"><a  onclick="hide_show_excursion(1)" class="nc_ab_popupWindow-link">Пройти позже</a></div>
                            <div class="nc_ab_popupWindow-row"><a  onclick="no_excursion(1)" class="nc_ab_popupWindow-link">Не показывать</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php

            $max_row_id = 0;
            foreach ($data as $row) {
                if ($row['ID']>$max_row_id)  $max_row_id = $row['ID'];
            }
            $iter = 0;
            foreach ($data as $row) {
                ?>
                <div  id='slide<?php echo $row['ID']?>' style="display:none; background: #666;">
                    <? if ($iter == 0 || $row['ID'] == 2): ?>
                        <!-- временно заблокировано <div id='helper_start<? echo $row['ID'];?>' onmouseover="this.style.cursor='pointer';" onclick="this.style.display='none';" style =" opacity: 0.9; padding:5px; background: white; z-index: 2000; position: fixed;right: 260px; width: 260px; top: 55px; border-radius: 2px; border: 1px solid #000;" >С помощью этой  кнопки «Помощник» <span class="ti-arrow-circle-down" ></span>, которая расположена в меню над блоком экскурсии, можно в любой момент скрыть или развернуть экскурсию</div>
                        <script>$nc('#helper_start<? echo $row['ID'];?>').delay(30000).fadeOut();</script> -->
                    <? endif;?>
                    <div class="nc_ab_sidebarWindow" style="opacity: 0.7;" onmouseover="this.style.opacity='1';" onmouseout="this.style.opacity='0.7';">
                        <div class="nc_ab_sidebarWindow-header">

                            <div class="nc_ab_sidebarWindow-counter"><?php echo ($row['ID'] == 2 ? 1 : $iter + 2);?> / <? echo count($data);?></div>

                            <div class="nc_ab_sidebarWindow-btns">
                                <!-- временно заблокировано <a onclick="show_contents('<?php echo $row['ID']; ?>','<?php echo $row['url'];?>','<? echo count($data);?>')" class="nc_ab_sidebarWindow-btn nc_ab_sidebarWindow-btn--list"></a>
						<a class="nc_ab_sidebarWindow-btn nc_ab_sidebarWindow-btn--prev nc_ab_sidebarWindow-btn--disabled" disabled></a>-->
                                <a class="nc_ab_sidebarWindow-btn nc_ab_sidebarWindow-btn--close" title="Завершить экскурсию" onclick="no_excursion(1)" ></a>
                                <a style='display:none;' target="_blank" href="<?php if (!empty($row['ext_url'])) echo $row['ext_url']; else echo '';?>"><span id="ext_url_href<?php echo $row['ID'];?>"></span></a>
                                <? if ($row['ID'] != $max_row_id): ?>
                                    <a title="Далее" <? if(count($data) == 1) echo 'class="nc_ab_sidebarWindow-btn--disabled" disabled';?> onclick="next_stage('<?php echo $row['url'];?>', <?php echo $row['ID'];?>, <?php if((int)$row['ID'] == 2 ) echo 1; else echo ((int)$data[$iter+1]['ID'] - (int)$row['ID']);?>, '<?php if (!empty($row['ext_url'])) echo $row['ext_url']; else echo '';?>', <? echo $row['reboot']; ?>)" class="nc_ab_sidebarWindow-btn nc_ab_sidebarWindow-btn--next"></a>
                                <? else:?>
                                    <a title="Далее" <? if(count($data) == 1) echo 'class="nc_ab_sidebarWindow-btn--disabled" disabled';?> onclick="no_excursion(1)" class="nc_ab_sidebarWindow-btn nc_ab_sidebarWindow-btn--next"></a>
                                <? endif;?>

                            </div>
                        </div>

                        <div class="nc_ab_sidebarWindow-content scrollpane">
                            <div class="nc_ab_sidebarWindow-text">
                                <span class="nc_ab_sidebarWindow-title"><?php echo $row['Header'];?></span>
                                <p><?php echo $row['Text'];?></p>
                            </div>
                        </div>
                    </div>

                </div>
                <?php
                $iter++;
            }
            ?>
        </div>
        <?php
    }
    else
    {
        ?>
        <div id='slide1' class='no_site' style="background: #666; display:none;">
            <div id='but1' style='display:none;' class="nc_ab_popupWindow">
                <div class="nc_ab_popupWindow-overlay"></div>
                <div class="nc_ab_popupWindow-content">
                    <div class="nc_ab_popupWindow-image"><img src="<?= $ADMIN_PATH ?>dashboard/img/netcat-logo-large.png" alt="netcat-logo"></div>

                    <div class="nc_ab_popupWindow-text">
                        <div class="nc_ab_popupWindow-title">Не знаете с чего начать?<br /></div>
                        <? if ($add_site == 1): ?>
                            <div class="nc_ab_popupWindow-about">Попробуйте для начала добавить в систему сайт.</div>
                        <? elseif($add_site == 1):?>
                            <!-- временно заблокировано <div class="nc_ab_popupWindow-about">Познакомьтесь с системой управления сайтами Неткэт. Для возможности запуска экскурсии предлагаем выполнить инструкции по добавлению сайта.</div> -->
                        <? elseif($add_sub == 1):?>
                            <!-- временно заблокировано <div class="nc_ab_popupWindow-about">Познакомьтесь с системой управления сайтами Неткэт. Для возможности запуска экскурсии предлагаем выполнить инструкции по добавлению раздела с объектами. Если вы уже добавляли раздел, то добавьте в него <b>хотя бы один объект</b></div> -->
                        <? endif;?>
                    </div>
                    <div class="nc_ab_popupWindow-bottom">
                        <div class="nc_ab_popupWindow-row"><a style="color:#fff;"  onclick="document.location.href ='<? echo $path_to_netcat;?>' + 'admin/#site.add(0)'; $nc('#slide1').css('display','none'); $nc('#but1').css('display','none');" class="nc_ab_popupWindow-button">Добавить сайт</a></div>
                        <div class="nc_ab_popupWindow-row"><a  onclick="hide_show_excursion(1)" class="nc_ab_popupWindow-link">Добавить позже</a></div>
                    </div>
                </div>
            </div>
        </div>
        <?
    }
    ?>

    <script>

        // переход по слайдам экскурсии
        function next_stage(url, step, next_step, extr_url, reboot){
            i = step;
            $nc('#slide'+i).css('display','none');
            $nc('#but'+i).css('display','none');
            next_slide = i + parseInt(next_step);
            $nc('#slide'+next_slide).css('display','block');
            $nc('#but'+next_slide).css('display','block');
            $nc.post("<? echo $path_to_netcat;?>" + "admin/excursion.php",{slide_id: step});
            document.cookie = "slide_id="+step;
            if (url) {document.location.href = url;}
            if (reboot) {
                location.href = url;
                location.reload();
            }
            if (extr_url) {

                /* временно заблокировано
                 setTimeout(function() {
                 	document.getElementById("extr_url").click();
                 }, 40);
                 */
                window.open(extr_url);
                window.focus();
            }
            $nc('.nc_ab_sidebarWindow-content').jScrollPane();
            $nc('#slide1').css('display','none');
            $nc('#but1').css('display','none');
        }

        // выход из  экскурсии
        function no_excursion(step){
            i = step;
            $nc('#slide'+i).css('display','none');
            $nc('#but'+i).css('display','none');
            $nc('.excursion_hide').css('display','none');
            $nc.post("<? echo $path_to_netcat;?>" + "admin/excursion.php",{user_id: "<?php echo $AUTH_USER_ID; ?>", shownext: 0});
            setTimeout(function() {
                ;
            }, 40);
            $nc.post("<? echo $path_to_netcat;?>" + "admin/excursion.php",{slide_id: 0});
            document.cookie = "slide_id=0";
            if ($nc('.wrap_excursion').css("display") != 'none') {
                $nc('.wrap_excursion').css("display", 'none');
            }
            //убран переход на главную админки при отказе от экскурсии
        }
	//получает куки по имени
        function get_cookie(cookie_name)
        {
            $nc.post("<? echo $path_to_netcat;?>" + "admin/excursion.php",{get_cookie: 1})
                .done(function(data) {
                    if (data){return parseInt(data);}
                });

            var results = document.cookie.match ( '(^|;) ?' + cookie_name + '=([^;]*)(;|$)' );
            if ( results ){
                return (decodeURI(results[2]));
            }
            else {
                return null;
            }
        }

    </script>

    <script>
        var cur_slide_id = get_cookie("slide_id");
        if (getUrlVars()["is_excursion"] || cur_slide_id)
        {
            if (getUrlVars()["is_excursion"] && !cur_slide_id) {$nc(window).unload(setStartSlide(1 + parseInt(getUrlVars()["is_excursion"])));}
            if (cur_slide_id && parseInt(cur_slide_id) == 1)  {$nc(window).unload(setStartSlide(1 + parseInt(cur_slide_id)));}
                else if (cur_slide_id) { $nc(window).unload(setStartSlide(parseInt(cur_slide_id)));}
        }
        else {$nc(window).unload(setStartSlide(1));}

        // Считывает GET переменные из URL страницы и возвращает их как ассоциативный массив.
        function getUrlVars()
        {
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        }

	//устанавливает начальный слайд при запуске(если нет куки, то первый)
        function setStartSlide(id)
        {
            var id_show = 1;
            $nc('#excursion_block').show();
            if (id)
            {
                id_show = id;
            }

            $nc('#slide'+id_show).css('display','block');
            $nc('#but'+id_show).css('display','block');

	    if (!$nc('#slide'+id_show).hasClass('no_site'))
            {
            	$nc.post("<? echo $path_to_netcat;?>" + "admin/excursion.php",{slide_id: id});
            	document.cookie = "slide_id="+id;
            }

            if (getUrlVars()["is_excursion"])
            {
                var width_main = parseInt($nc('.middle').css("width"));
                // $nc('.excursion_hide').css("left", width_main - 250 + 'px');"this.
                // $nc('.excursion_hide').css("display", "block");

                $nc('#slide1').css('display','none');
                $nc('#but1').css('display','none');
                $nc('#excursion_block').show();

            }
        }

        // TO DO - временно выключена, будет дополняться функционал - показывать содержание экскурсии
        function show_contents(id, url, count)
        {
            //$nc('#slide'+id).css('display','none');
            //$nc('#slide_contents').css('display','block');
            //$nc('.nc_ab_sidebarWindow-content').jScrollPane();
        }
    </script>

</div>
