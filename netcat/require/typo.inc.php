<?php
/*
Auto typograf by Maxim Popov http://ecto.ru/
version 1.02
dont forget set mb_internal_encoding('UTF-8');
*/

function nc_format_typo($text) {
    $nc_core = nc_Core::get_object();
    if (!$nc_core->NC_UNICODE) {
        $text = $nc_core->utf8->win2utf($text);
    }

    $text = nc_typo($text, array('cleen_utf' => true));

    if (!$nc_core->NC_UNICODE) {
        $text = $nc_core->utf8->utf2win($text);
    }

    return $text;
}

function nc_typo_tag_encode($match) {
    return '<' . base64_encode($match[1]) . '>';
}

function nc_typo_tag_decode($match) {
    return '<' . base64_decode($match[1]) . '>';
}

function nc_typo_savetag_encode($match) {
    return '<%' . base64_encode('%' . $match[1]) . '>';
}

function nc_typo_savetag_decode($match) {
    $t = base64_decode($match[1]);
    if ($t[0] != '%') return '<%' . $match[1] . '>';
    return '<' . substr($t, 1) . '>';
}

function nc_typo_nbsp($match) {
    $match_t = trim(preg_replace('/<[^>]+>/u', '', $match[0]));
    //if(substr($match_t,-1,1)=='.')return $match[0];
    $match_t = preg_replace('/[\s\()-]/u', '', $match_t);

    $t = mb_strlen($match_t);
    if ($t > 0 && $t < 4) $match[0] = $match[1] . '&nbsp;';

    return $match[0];
}

function nc_typo($text, $settings = 'none') {

    if ($text == '') return '';

    $config = array(
        'cleen_utf' => true,
    );

    if ($settings != 'none') $config = $settings + $config;

    $spec_chars_normalaize = array(
        '&quot;' => '"',
        '&#34;' => '"',
        '&#034;' => '"',

        '&#39;' => "'",
        '&#039;' => "'",

        '&#160;' => '&nbsp;',
        '&#xA0;' => '&nbsp;',
        chr(194) . chr(160) => '&nbsp;',

        '&mdash;' => '&#151;',
        chr(226) . chr(128) . chr(148) => '&#151;',

        '«' => '&laquo;',
        '»' => '&raquo;',
        '„' => '&bdquo;',
        '”' => '&rdquo;',
        '“' => '&ldquo;',
        "‘" => '&lsquo;',
        "’" => '&rsquo;',
    );

    $spec_chars_good = array(
        '&quot;' => '"',
        '&#34;' => '"',
        '&#034;' => '"',

        '&#39;' => "'",
        '&#039;' => "'",

        '&lsquo;' => "‘",
        '&rsquo;' => "’",

        '&ldquo;' => '“',
        '&#147;' => '“',
        '&#x93;' => '“',

        '&rdquo;' => '”',
        '&#148;' => '”',
        '&#x94;' => '”',

        '&bdquo;' => '„',

        '&mdash;' => chr(226) . chr(128) . chr(148),
        '&#151;' => chr(226) . chr(128) . chr(148),


        '&laquo;' => '«',
        '&#171;' => '«',
        '&#xAB;' => '«',


        '&raquo;' => '»',
        '&#187;' => '»',
        '&#xBB;' => '»',

        '&nbsp;' => chr(194) . chr(160),
        '&#160;' => chr(194) . chr(160),
        '&#xA0;' => chr(194) . chr(160),
        '&#x202f;' => ' ',

        //'&#8209;'=>chr(226).chr(128).chr(145),
        //'-'=>chr(226).chr(128).chr(145),

        '&copy;' => '©',
        '&#169;' => '©',
        '&reg;' => '®',
        '&#174;' => '®',
        '&trade;' => '™',
        '&#153;' => '™',
        '&hellip;' => '…',
    );


    $symbols = array(
        '(c)' => '&#169;',
        '(r)' => '&#174;',
        '(tm)' => '&#153;',
        '(C)' => '&#169;',
        '(R)' => '&#174;',
        '(TM)' => '&#153;',
        '...' => '&hellip;'
    );

    //Сохраняем нужное
    $text = preg_replace_callback('/<((script|style|code|save)[^>]*>.+<\/\2)>/Uus', 'nc_typo_savetag_encode', $text);
    $text = preg_replace_callback('/<([^%][^>]*)>/us', 'nc_typo_tag_encode', $text);
    $text = strtr($text, $symbols);
    $text = strtr($text, $spec_chars_normalaize);

    //Кавычки


    $text = preg_replace('/([^\w])"([^"]*[^\d])"([^\w])/Usu', '\1&laquo;\2&raquo;\3', ' ' . $text . ' '); //russian
    $text = preg_replace('/([^\w])"([^"]*\d"[^"]+)"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian
    $text = preg_replace('/([^\w])"([^"]*[^\d])"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian
    $text = preg_replace('/([^\w])"([^"]*)"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian

    $text = preg_replace('/(&laquo;)\s+/Uus', '\1', $text);
    $text = preg_replace('/\s+(&raquo;)/Uus', '\1', $text);

    //$text=preg_replace('/&laquo;(.*)&laquo;(.*)&raquo;(.*)&raquo;/Usu', '&laquo;\1&bdquo;\2&ldquo;\3&raquo;', $text); //russian

    $text = preg_replace('/([^\w])\'([^\']*)\'([^\w])/Usu', '\1&lsquo;\2&rsquo;\3', $text);


    //Пробелы у пунктуации - иногда лучше отключать
    $text = preg_replace('/\s+([\.,;:\!\?])(\s+)/u', '\1\2', $text);

    $text = trim($text);


    //Много тире
    $text = preg_replace('/\s*-{2,3}\s*/us', '&nbsp;&#151; ', $text);


    //Длинное тире
    $text = preg_replace('/\s+-\s+/us', '&nbsp;&#151; ', $text);
    if ($text[0] == '-' && $text[1] == ' ') $text = '&#151;' . substr($text, 1);


    //Короткие слова
    //$text=preg_replace('/\s+(\w{1,3}($|\.))/u', '&nbsp;\1', $text);
    $text = preg_replace('/(\s|^|>|&nbsp;|\(|«|&laquo;|\t)((ни|не),?),?\s+/ui', '\1\2&nbsp;', $text);
    $text = preg_replace('/(\s|^|>|&nbsp;|\(|«|&laquo;|\t)((и|но|а|или|да),?),?\s+/ui', '\1\2&nbsp;', $text);
    $text = preg_replace('/(\s|^|>|&nbsp;|\(|«|&laquo;|\t)((как),?),?\s+/ui', '\1\2&nbsp;', $text);
    $text = preg_replace('/(\s|^|>|&nbsp;|\(|«|&laquo;|\t)((из-за|про|по|за|для|на|до|при|меж|о|у),?),?\s+/ui', '\1\2&nbsp;', $text);
    $text = preg_replace('/(\s|^|>|&nbsp;|\(|«|&laquo;|\t)((в|с|от|из|без|к|об|под|над|перед)о?,?)\s+/ui', '\1\2&nbsp;', $text);

    // Т._к., т._д., т._е.
    $text = preg_replace('/([а-яёА-ЯЁ])\.\s*([а-яё])\.(\s*)(,?)(\s*)(\S+|$)/u', '\1.&nbsp;\2.\3\4 \6', $text);
    // Т._к._, т._е._, т._о._,
    $text = preg_replace('/([Тт]\.&nbsp;[кео]\.)\s*?(,?)(\s| |&nbsp;)*/u', '\1\2&nbsp;', $text);
    $text = preg_replace('/([^ \s;])\s*(и&nbsp;т\.&nbsp;[пд].)/us', '\1&nbsp;\2', $text);

    //$text=preg_replace('/([а-яёА-ЯЁ])\.([а-яёА-ЯЁ])\.\s+([а-яёА-ЯЁa-zA-Z0-9]+)/u', '\1.к.&nbsp;\2', $text);
    // А. Б. Шеин
    $text = preg_replace('/(\W)([А-ЯЁA-Z]\.)\s*([А-ЯЁA-Z]\.)\s*([А-ЯЁA-Z][а-яёa-z]+)/u', '\1\2&nbsp;\3&nbsp;\4', $text);
    // А. Шеин
    $text = preg_replace('/(\W)([А-ЯЁA-Z]\.)\s*([А-ЯЁA-Z][а-яёa-z]+)/u', '\1\2&nbsp;\3', $text);
    // Шеин А. Б.
    $text = preg_replace('/(\W)([А-ЯЁA-Z][а-яёa-z]+)\s+([А-ЯЁA-Z]\.)(\s*([А-ЯЁA-Z]\.))?/u', '\1\2&nbsp;\3&nbsp;\5', $text);
    // Потому_что
    $text = preg_replace('/([а-яёА-ЯЁ]+)\s+что/u', '\1&nbsp;что', $text);
    // он скзаал, что_мы пилоты
    $text = preg_replace('/,\s*что\s+/ui', ', что&nbsp;', $text);


    //Удаляем лишние пробелы
    $text = preg_replace('/\s*&nbsp;\s*/u', '&nbsp;', $text);
    $text = str_replace(' &#151;', '&nbsp;&#151;', $text);
    $text = str_replace(' —', '&nbsp;—', $text);
    $text = str_replace(' ―', '&nbsp;―', $text);

    //language part
    //back nbsp
    $text = preg_replace('/\s+(бы|ли|же)([\s\W])/u', '&nbsp;\1\2', $text);

    if ($config['cleen_utf'])
        $text = strtr($text, $spec_chars_good);

    if ($config['cleen_utf'])
        $text = str_replace('&nbsp;', chr(194) . chr(160), $text);


    //------------------------------------------------------------------------------
    //Восстанавливаем нужное1
    $text = preg_replace_callback('/<([^%][^>]*)>/u', 'nc_typo_tag_decode', $text);

    //вынос кавычек из ссылок
    $text = preg_replace('/<a([^>]+)>«([^<]+)»<\/a>/usi', '«<a\1>\2</a>»', $text);

    //Восстанавливаем нужное2
    $text = preg_replace_callback('/<%([^>]+)>/u', 'nc_typo_savetag_decode', $text);

    return $text;
}


function nc_post_typo($text) {
    //Сохраняем нужное
    $text = preg_replace_callback('/<((script|style|code|save|nobr)[^>]*>.+<\/\2)>/Uus', 'nc_typo_savetag_encode', $text);
    $text = preg_replace_callback('/<([^%][^>]*)>/su', 'nc_typo_tag_encode', $text);
    //$text=preg_replace('/(:-\)|:-\(|:-\|)/u', '<nobr class="typo">$1</nobr>', $text);
    //непеносимый дефиз
    $text = preg_replace('/[A-zА-яЁё0-9:;&]+-[A-zА-яЁё0-9)\\(\/]+/u', '<nobr class="typo">\0</nobr>', $text);
    $text = preg_replace_callback('/<((nobr)[^>]*>.+<\/\2)>/Uus', 'nc_typo_savetag_encode', $text);
    // Удалить лишние пробелы между кавычкой и скобкой
    $text = preg_replace('/\(\s*(["«\'„])/u', '(\1', $text);
    //вынос
    $text = preg_replace('/((>|^)|\s)\s*(«|&laquo;)/su', '\2<d2JyIGNsYXNzPSJ0eXBvIg==><span class="slaquo-s typo"> </span> <span class="hlaquo-s typo">\3</span>', $text);
    $text = preg_replace('/(\s|&nbsp;|' . chr(194) . chr(160) . ')*(„|&bdquo;)/su', '<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbdquo typo"> </span> <span class="hbdquo typo">\2</span>', $text);
    $text = preg_replace('/(\s|&nbsp;|' . chr(194) . chr(160) . ')+\(/su', '<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbrace typo"> </span> <span class="hbrace typo">(</span>', $text);
    if (substr($text, 0, 63) == '<d2JyIGNsYXNzPSJ0eXBvIg==><span class="slaquo-s typo"> </span> ') $text = substr($text, 63);
    if (substr($text, 0, 61) == '<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbrace typo"> </span> ') $text = substr($text, 61);
    if (substr($text, 0, 61) == '<d2JyIGNsYXNzPSJ0ebuXBvIg==><span class="sbdquo typo"> </span> ') $text = substr($text, 61);
    $text = preg_replace_callback('/<(\/?span[^>]*)>/su', 'nc_typo_tag_encode', $text);
    //Восстанавливаем нужное
    $text = preg_replace_callback('/<([^%][^>]*)>/u', 'nc_typo_tag_decode', $text);
    $text = preg_replace_callback('/<%([^>]+)>/u', 'nc_typo_savetag_decode', $text);
    $text = preg_replace('/(<(p|br|li)[^>]*>(\s|&nbsp;|' . chr(194) . chr(160) . ')*)<wbr class="typo"><span class="slaquo-s typo">(\s|&nbsp;)<\/span> /ius', '\1', $text);
    $text = preg_replace('/(<(p|br|li)[^>]*>(\s|&nbsp;|' . chr(194) . chr(160) . ')*)<wbr class="typo"><span class="sbrace typo">(\s|&nbsp;)<\/span> /ius', '\1', $text);
    $text = preg_replace('/(<(p|br|li)[^>]*>(\s|&nbsp;|' . chr(194) . chr(160) . ')*)<wbr class="typo"><span class="sbdquo typo">(\s|&nbsp;)<\/span> /ius', '\1', $text);
    /**/
    return $text;
}


function nc_unpost_typo($text) {
    $text = preg_replace('/<nobr class="typo">(.+)<\/nobr>/Uu', '\1', $text);

    $text = str_replace('<span class="hbrace typo">(</span>', '(', $text);

    $text = str_replace('<span class="hlaquo-s typo">«</span>', '«', $text);
    $text = str_replace('<span class="hlaquo-s typo">&laquo;</span>', '&laquo;', $text);

    $text = str_replace('<span class="hbdquo typo">„</span>', '„', $text);
    $text = str_replace('<span class="hbdquo typo">&bdquo;</span>', '&bdquo;', $text);

    $text = str_replace('<wbr class="typo"><span class="slaquo-s typo"> </span> ', ' ', $text);
    $text = str_replace('<wbr class="typo"><span class="sbrace typo"> </span> ', ' ', $text);
    $text = str_replace('<wbr class="typo"><span class="sbdquo typo"> </span> ', ' ', $text);
    /**/
    return $text;
}