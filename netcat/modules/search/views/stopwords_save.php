<?php

/**
 * Проверка стоп-слова перед сохранением; пользователь должен выбрать
 * одну из базовых форм
 */
if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_lists_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);
$ui->add_back_button();

$stopword = $this->data_form('nc_search_language_stopword', 'stopwords');
$stopword->set_values($this->get_input('data'), true);

$word = $stopword->get('word');
$lang = $stopword->get('language');
$input = (array) $word;

$context = new nc_search_context(array('language' => $lang));
$filtered = nc_search_extension_manager::get('nc_search_language_filter', $context)
                ->until_first('nc_search_language_filter_stopwords')
                ->apply('filter', $input);

if ($filtered == $input) { // на входе базовая форма — OK!
    $params = array(
        'view' => 'stopwords',
        'action' => 'save',
        'data_class' => 'nc_search_language_stopword',
        'id' => $stopword->get_id(),
        'nc_token' => $nc_core->input->fetch_post('nc_token'),
        'data[language]' => $stopword->get('language'),
        'data[word]' => $word,
    );

    // !! на входе в cp-1251 всегда кодировка windows-1251, но сейчас в $params - в utf-8
    if (!$nc_core->NC_UNICODE) {
        $params = $nc_core->utf8->array_utf2win($params);
    }

    // $this->redirect("?".http_build_query($params, null, '&'));
    foreach ($params as $key => $value) {
        echo $this->hidden($key, $value);
    }
    echo "</form>";
    echo '<script>document.forms[0].submit()</script>';
    exit;
}

echo "<fieldset><legend>", NETCAT_MODULE_SEARCH_ADMIN_STOPWORD, "</legend><div class='stopword_variants'>";

$num_variants = sizeof($filtered);
if ($num_variants == 0) {
    printf(NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_HAS_NO_BASEFORM, $word);
    print $this->hidden('data[word]', $word);
} elseif ($num_variants == 1) {
    printf(NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_HAS_ONE_BASEFORM, $word, $filtered[0]);
} else {
    printf(NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_HAS_SEVERAL_BASEFORMS, $word);
}

if ($num_variants) {
    print "<p>".NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_BASEFORM_QUESTION."</p>";
    foreach ($filtered as $i => $w) {
        echo "<input type='radio' name='data[word]' value='$w' id='w$i'", ($i ? "" : " checked='checked'"), " /> ",
             "<label for='w$i'><code>$w</code></label><br />";
    }
    if (!in_array($word, $filtered)) {
        echo "<input type='radio' name='data[word]' value='$word' id='w_original'> ",
             "<label for='w_original'><code>$word</code> ", NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_AS_ENTERED, "</label>";
    }
    echo $this->hidden('data[language]', $stopword->get('language'));
}


echo "</div></fieldset>";
