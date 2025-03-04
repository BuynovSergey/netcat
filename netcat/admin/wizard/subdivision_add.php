<?

$_POST["NC_HTTP_REQUEST"] = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");


if (!$nc_core->token->verify()) {
    echo NETCAT_TOKEN_INVALID;
    exit;
}


// проверка названия раздела
if (!$subdivision_name) {
    nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME, 'error');
    exit();
}

// проверка уникальности ключевого слова для текущего раздела
if (!IsAllowedSubdivisionEnglishName($english_name, $sub_id, 0, $catalogue_id)) {
    nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD, 'error');
    exit();
}

// проверка символов для ключевого слова
if (!$nc_core->subdivision->validate_english_name($english_name)) {
    nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
    exit();
}

if (!isset($subdivision_name) || !isset($english_name) || !isset($template_id) || !isset($class_id) || !isset($sub_id) || !isset($catalogue_id)) {
    trigger_error("Wrong params", E_USER_ERROR);
}

if ($sub_id) {
    $hidden_url = $db->get_var("SELECT Hidden_URL FROM Subdivision WHERE Subdivision_ID = '".$sub_id."'");
} else {
    $hidden_url = '/';
}

// Добавление раздела 
$db->query("INSERT INTO Subdivision
                    SET Catalogue_ID = '".$catalogue_id."',
                        Parent_Sub_ID = '".$sub_id."',
                        Subdivision_Name = '".$db->escape($subdivision_name)."',
                        Template_ID = '".$template_id."',
                        Checked = 1,
                        EnglishName = '".$english_name."',
                        Hidden_URL = '".$hidden_url.$english_name."/',
                        Priority = '".$db->get_var("SELECT MAX(Priority)+1 FROM Subdivision where Parent_Sub_ID=0 AND Catalogue_ID='$catalogue_id'")."'");
$inserted_sub = $db->insert_id;

// Добавление шаблона к разделу
if ($class_id) {
    $db->query("INSERT INTO Sub_Class (
                          Subdivision_ID,
                          Catalogue_ID,
                          Class_ID,
                          Sub_Class_Name,
                          Read_Access_ID,
                          Write_Access_ID,
                          Edit_Access_ID,
                          Subscribe_Access_ID,
                          Moderation_ID,
                          Checked,
                          Priority,
                          EnglishName,
                          DaysToHold,
                          AllowTags,
                          NL2BR,
                          RecordsPerPage,
                          SortBy,
                          Created,
                          DefaultAction,
                          UseCaptcha)
                     VALUES (
                          '".$inserted_sub."', 
                          '".$catalogue_id."', 
                          '".$class_id."',
                          '".$subdivision_name."',
                          '0',
                          '0',
                          '0',
                          '0',
                          '0',
                          '1',
                          '1',
                          '".$english_name."',
                          NULL,
                          '-1',
                          '-1',
                          NULL,
                          '',
                          '".date("%Y-%m-%d %H:%i:%s")."',
                          'index',
                          '-1')");
    $inserted_cc = $db->insert_id;
}

/* Вставить добавление шаблона в SubClass */

if ($inserted_sub) {
    echo $inserted_sub;
} else {
    nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR, 'error');
}
?>