<?php

if (!class_exists("nc_system")) { die; }

$input_title = $this->get_input('title');
$input_weight = $this->get_input('weight'); // «тэги с весом», указанные пользователем
$input_custom = $this->get_input('custom', array()); // поля «с данными», указанные пользователем
$is_save_request = !empty($input_title) || !empty($input_weight) || !empty($input_custom);

if ($is_save_request) {
    $nc_core->token->exit_if_invalid();
}

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVE);

$fields = nc_search::load('nc_search_field', 'SELECT * FROM `%t%` ORDER BY `Weight` DESC', 'name')
          ->set_output_encoding(nc_core('NC_CHARSET'));

// СОХРАНЕНИЕ ПАРАМЕТРОВ
// входящие данные: content[], title, weight[], custom[]
// В отображении для простоты восприятия имеется несколько блоков:
// «область индексирования», «вес тэгов», «извлечение данных». Сущность, стоящая 
// за этими «подтипами» областей HTML-документов на самом деле одна — nc_search_field.
// За эту простоту пришлось заплатить запутанной процедурой сохранения данных:

$input_content = $this->get_input('content');
if ($input_content) {
    $fields["content"]->set('query', $input_content['query'])
            ->set('filter_content', $input_content['filter_content'])
            ->save();
}

// title
if ($input_title) {
    $fields["title"]->set('weight', $input_title['weight'])->save();
}

$saved_weight_fields = array(); // поля, которые были сохранены (для последующего удаления отсутствующих полей)
// произвольный набор полей с весами в weight[]
if (is_array($input_weight)) {
    // (1) объединить поля с одинаковым весом
    foreach ($input_weight as $w) {
        if (!trim($w['query'])) {
            continue;
        }
        $w_weight = (float) strtr($w["weight"], ",", "."); // decimal point
        $w_name = "w".strtr($w_weight, ".,", "__");
        if (isset($saved_weight_fields[$w_name])) {
            $saved_weight_fields[$w_name]["query"] .= " $w[query]";
        } else {
            $saved_weight_fields[$w_name] = array("query" => addslashes(trim($w["query"])),
                    "query_scope" => "content",
                    "name" => $w_name,
                    "weight" => strtr($w_weight, ",", "."),
            );
        }
    }
    // (2) сохранить изменения
    foreach ($saved_weight_fields as $w) {
        $field = new nc_search_field($w);
        $field->save();
        $fields->add($field);
    }
}

// сохранить прочие поля («извлечение данных»)
$saved_custom_fields = array(); // сохранённые поля (для последующего удаления отсутствующих полей)
foreach ($input_custom as $custom_field_settings) {
    // подготовка настроек поля к сохранению:
    $custom_field_settings['weight'] = strtr($custom_field_settings["weight"], ",", "."); // decimal point
    // чекбоксы, подстрахуемся на случай, если по умолчанию значение свойства != false
    foreach (array('is_retrievable', 'is_normalized', 'is_sortable', 'is_indexed', 'is_stored') as $k) {
        if (empty($custom_field_settings[$k])) {
            $custom_field_settings[$k] = false;
        }
    }
    $custom_field_settings['is_searchable'] = $custom_field_settings['is_indexed'];
    $custom_field_settings['remove_from_parent'] = false;
    $custom_field_settings['query_scope'] = 'document';

    $field = new nc_search_field($custom_field_settings);
    $field->save();
    $fields->add($field);
    $saved_custom_fields[$custom_field_settings['name']] = true;
}

// выбрать поля с весом ($weight_fields) и с данными ($custom_fields) — пригодятся
// позже для вывода в форме + удаление старых (отсутствующих при сохранении) полей
$weight_fields = array();
$custom_fields = array();
foreach ($fields as $name => $field) {
    if (preg_match("/^w\d+(?:_\d+)?$/", $name)) {
        if ($is_save_request && !isset($saved_weight_fields[$name])) { // cleanup
            $field->delete();
        } else {
            $weight_fields[] = array("name" => $name,
                    "query" => $field->get("query"),
                    "weight" => (float) $field->get("weight"));
        }
    } else if ($name != 'title' && $name != 'content') {
        if ($is_save_request && !isset($saved_custom_fields[$name])) {
            $field->delete();
        } else {
            $field_options = $field->to_array();
            $field_options['weight'] = (float) $field_options['weight'];
            $custom_fields[] = $field_options;
        }
    }
}

if ($is_save_request) {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTING_FIELDS_SAVED, 'ok', array(
            $this->hash_href('#module.search.indexing')
    ));
}

// ВЫВЕСТИ ФОРМУ
?>

<form method="POST" class="settings fields" onsubmit="return check_field_names();">
    <?= $nc_core->token->get_input() ?>
    <input type="hidden" name="view" value="fields" />

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_DOCUMENT_AREAS ?></legend>
        <div class="setting">
            <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_DOCUMENT_CONTENT ?>:</div>
            <div class="textarea">
                <textarea class="no_cm" name="content[query]"><?=htmlspecialchars($fields["content"]->get('query')) ?></textarea>
            </div>
        </div>
        <div class="setting">
            <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_DOCUMENT_NOINDEX ?>:</div>
            <div class="textarea">
                <textarea class="no_cm" name="content[filter_content]"><?=
                    htmlspecialchars($fields["content"]->get('filter_content'))
                ?></textarea>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAG_WEIGHT ?></legend>
        <div class="tag_weight">
            <?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_TITLE_TAG_HAS_WEIGHT ?>
            <input type="text" name="title[weight]" class="i4"
                   value="<?=floatval($fields["title"]->get('weight')) ?>" />
        </div>
        <div id="tag_weight_container"></div>
        <div id="add_tag_weight">
      <?=nc_admin_img("i_obj_add.gif", NETCAT_MODULE_SEARCH_ADMIN_ADD)." ".NETCAT_MODULE_SEARCH_ADMIN_ADD ?>
        </div>
    </fieldset>

    <script type="text/javascript">
        (function(fields) {
            var tpl = '<div class="tag_weight">' +
                '<?=addslashes(NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS) ?>' +
                '<input type="text" name="weight[x][query]" class="query" />' +
                '<?=addslashes(NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS_HAVE_WEIGHT) ?>' +
                '<input type="text" name="weight[x][weight]" class="i4 weight" />' +
                '<a href="#" class="del internal"><?=addslashes(NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS_DELETE) ?></a>' +
                '</div>';
            var last = 0;
      

            function add_tag_weight(index, field, focus) {
                var div = $nc(tpl.replace(/\[x\]/g, "[" + index + "]")).appendTo('#tag_weight_container'),
                input = div.find("input.query");
                input.val(field.query);
                focus && input.focus();
                div.find("input.weight").val(field.weight);
                div.find("a.del").click(function() { $nc(this).parent().remove(); return false; });
            }

            $nc('#add_tag_weight').click(function() { add_tag_weight(last++, {weight: 1}, true)});
            for (var i in fields) { add_tag_weight(last++, fields[i], false); }
      
        })(<?=json_encode($weight_fields) ?>);
    </script>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION ?></legend>
        <div id="custom_field_container"></div>
        <div id="add_custom_field">
        <?=nc_admin_img("i_obj_add.gif", NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_ADD_FIELD) . " " .
                    NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_ADD_FIELD ?>
        </div>
    </fieldset>

</form>
<script type="text/javascript">
    (function(fields) {
        var tpl = '<div class="custom_field">' +
            '<div class="settings"><div class="caption">' +
                '<?=addslashes(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_NAME) ?>:' +
            '</div><div class="input">'+
                '<input type="text" name="custom[x][name]" class="custom_field_name" />' +
            '</div></div>' +

            '<div class="settings"><div class="caption">' +
                '<?=addslashes(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_QUERY) ?>:' +
            '</div><div class="textarea">'+
                '<textarea name="custom[x][query]"  class="no_cm" />' +
            '</div></div>' +

            '<div class="settings"><div class="caption">' +
                '<?=addslashes(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_TYPE) ?>:' +
            '</div><div class="input">'+
                '<select name="custom[x][type]">' +
                '<?=addslashes(
                        '<option value="'.nc_search_field::TYPE_STRING.'">'.NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_TYPE_STRING.'</opion>'.
                        '<option value="'.nc_search_field::TYPE_INTEGER.'">'.NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_TYPE_INTEGER.'</opion>'
                ) ?>' +
                 '</select>' +
             '</div></div>' +

             '<div class="settings"><div class="caption">' +
                '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_WEIGHT) ?>:' +
             '</div><div class="input">'+
                '<input type="text" name="custom[x][weight]" />' +
             '</div></div>' +

             '<div class="custom_field_checkboxes">' +

                 '<div class="settings_cb">' +
                     '<input id="cb_indexed_x" name="custom[x][is_indexed]" type="checkbox" value="1" /> ' +
                     '<label for="cb_indexed_x">' +
                         '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_INDEXED) ?>' +
                     '</label>' +
                '</div>' +

                 '<div class="settings_cb">' +
                     '<input id="cb_retrievable_x" name="custom[x][is_retrievable]" type="checkbox" value="1" /> ' +
                     '<label for="cb_retrievable_x">' +
                         '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_RETRIEVABLE) ?>' +
                     '</label>' +
                 '</div>' +

                 '<div class="settings_cb">' +
                     '<input id="cb_tokenized_x" name="custom[x][is_normalized]" type="checkbox" value="1" /> ' +
                     '<label for="cb_tokenized_x">' +
                         '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_NORMALIZED) ?>' +
                     '</label>' +
                 '</div>' +

                 '<div class="settings_cb">' +
                     '<input id="cb_sortable_x" name="custom[x][is_sortable]" type="checkbox" value="1" /> ' +
                     '<label for="cb_sortable_x">' +
                         '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_SORTABLE) ?>' +
                     '</label>' +
                 '</div>' +

             "</div>" + // custom_field_checkboxes

         '<div class="remove_custom_field">' +
             '<a href="#" class="del internal"><?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS_DELETE) ?></a>' +
             '</div>' +
             '</div>';
         var last = 0;

         function add_custom_field(index, field, focus) {
             var div = $nc(tpl.replace(/\[x\]|_x\b/g, "[" + index + "]")).appendTo('#custom_field_container');

             $nc.each(field, function(name, value) { // set values
                 var el = div.find("[name='custom[" + index + "][" + name + "]']").first();
                 if (el.attr('type') == 'checkbox') { el.prop('checked', (value == true ? 'checked' : '')); } // sic!
                 else { el.val(value); }
             });

             var name_input = div.find("input.custom_field_name");
             focus && name_input.focus();

             div.find("a.del").click(function() { $nc(this).parent().parent().remove(); return false; })
             .hover(function() { $nc(this).parent().parent().addClass('custom_field_delete_hover')},
             function() { $nc(this).parent().parent().removeClass('custom_field_delete_hover')});
         }

         $nc('#add_custom_field').click(function(e) {
             add_custom_field(last++, {weight: 1, is_indexed: 1}, true);
             return false;
         });

         for (var i in fields) { add_custom_field(last++, fields[i], false); }

     })(<?=json_encode($custom_fields)
    ?>);

     // проверка имён полей
     function check_field_names() {
         var names_are_valid = true;
         $nc('input.custom_field_name').each(function() {
             var input = $nc(this)
                 nameRegexp = /(\W+)|^(title|content|doc_id|site_id|sub_id|ancestor|language|last_modified|access_level|w[\d_]+)$/,
                 is_invalid = nameRegexp.test(input.val()) || !input.val();

             if (is_invalid) {
                 input.focus();
                 alert("<?=addcslashes(htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FIELD_INVALID_NAME), "\n") ?>");
                 names_are_valid = false;
                 return false;
             }
         });
         return names_are_valid;
     }

</script>
