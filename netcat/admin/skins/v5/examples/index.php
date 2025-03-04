<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Netcat 5 Admin - UI</title>

    <!-- CSS -->
    <link href="../css/netcat.css" rel="stylesheet">
    <link href="examples.css" rel="stylesheet">

    <!-- JavaScript -->
    <script type="text/javascript" src="/netcat_template/jquery/jquery.min.js"></script>
    <script>$nc = jQuery</script>
    <script type="text/javascript" src="/netcat/admin/js/nc/nc.min.js"></script>
    <script type="text/javascript" src="/netcat/admin/js/nc/ui/modal_dialog.min.js"></script>
    <script type="text/javascript" src="/netcat/admin/js/nc/ui/popover.min.js"></script>
    <script type="text/javascript" src="/netcat/admin/js/nc/ui/help_overlay.min.js"></script>

    <!-- Favicons -->
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
</head>
<body class="nc-admin">

<!--canvas id='canvas' width='500' height='500' style='position: absolute; top: 0; left: 0; z-index:99'></canvas>
<script>
    $(function(){
        var $body   = $('body');
        var $canvas = $('canvas');//.width($body.width()).height($body.height());
        $canvas.attr('width', $body.width()).attr('height', $body.height());
        var ctx     = $canvas[0].getContext('2d');
        // ctx.fillStyle="red";
        // ctx.fillRect(20,20,75,50);
        // // Turn transparency on
        // ctx.globalAlpha=0.2;
        // ctx.fillStyle="blue";
        // ctx.fillRect(50,50,75,50);
        // ctx.fillStyle="green";
        // ctx.fillRect(80,80,75,50);
        ctx.fillStyle = "rgba(0, 0, 0, 0.5)";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.clearRect(80,80,75,50);
    });
</script-->

<?php
//--------------------------------------------------------------------------
$run_time = microtime(TRUE);
//--------------------------------------------------------------------------

$navbar_menu = array(
    'базовые элементы' => array(
        'buttons'      => 'Кнопки',
        'icons'        => 'Иконки',
        'labels'       => 'Лэйблы',
        'alerts'       => 'Информационные сообщения',
        'tooltip'      => 'Всплывающие подсказки',
        '---',
        'common'   => 'Базовые стили',
        'typo'     => 'Типография',
        'progress' => 'Прогресс бар',
        '---',
        'helpers'  => 'Вспомогательные классы',
    ),

    'элементы навигации' => array(
        'path'        => 'Хлебные крошки',
        'navbar'      => 'Панель навигации',
        'tree'        => 'Дерево',
        'tabs'        => 'Вкладки',
        'toolbar'     => 'Панель инструментов',
        'pagination'  => 'Постраничная разбивка',
    ),

    'работа с данными' => array(
        'tables'    => 'Таблицы',
        'forms'     => 'HTML-Формы',
        'dashboard' => 'Виджеты главной страницы',
    ),
);

$components = array();
foreach ($navbar_menu as $menu_components) {
    foreach ($menu_components as $com => $title) {
        if ($title[0] == '-') continue;
        $components[$com] = $title;
    }
}

$bw_colors     = array('black', 'darken', 'dark', 'grey', 'light', 'lighten', 'white');
$accent_colors = array('blue', 'red', 'green', 'yellow', 'orange', 'purple', 'cyan', 'olive');

//--------------------------------------------------------------------------

define('NC', TRUE);

require_once('../../../../system/admin/ui/nc_ui.class.php');
$nc_core     = new stdClass();
$nc_core->ui = $ui = nc_ui::get_instance();


$com_key  = FALSE;
$com_path = dirname(__FILE__) . "/components/";

//--------------------------------------------------------------------------

function example($title = NULL)
{
    static $old_title;
    global $components, $com_key;
    if (ob_get_level() && $old_title) $components[$com_key]->examples[$old_title] = ob_get_clean();
    $old_title = $title;
    ob_start();
}

//--------------------------------------------------------------------------

foreach ($components as $key => $title)
{
    $com_key = $key;
    $components[$key] = new stdClass;
    $components[$key]->title = $title;
    $components[$key]->examples = array();
    $components[$key]->sources  = array();

    $source = file_get_contents($com_path . $key . '.php');
    $source = explode('<? example(', $source);
    foreach ($source as $i=>$row) {
        if ( ! $i) continue;
        $strpos = strpos($row, ')');
        $skey = substr($row, 1, $strpos - 2);
        $components[$key]->sources[$skey] = trim(substr($row, $strpos + 4));
    }

ob_start();
include $com_path . $key . '.php';
example();

}

//--------------------------------------------------------------------------

$replace = array(
    '@\t@i'                => '    ',
    '@&@i'                 => '&amp;',
    '@<([^! >]+)([^>]*)>@i' => '&lt;<b>$1<i>$2</i></b>&gt;',
    '@(=?)("[^"]*")@i'     => '<a>$1</a><em>$2</em>',
    '@<(!--.*--)>@i'       => '<p>&lt;$1&gt;</p>',
);
$replacePhp = array(
    '@\t@i'              => '    ',
    '@&@i'               => '&amp;',
    '@\n\n@i'            => "\n",
    '@<@i'               => '&lt;',
    '@((->)|(>))@i'      => '<a>$2</a>$3',
    '@(\'[^\']*\')@i'    => '<em>$1</em>',
    '@([=;>\n\s])([a-z_$]+)@i' => '$1<b>$2</b>',
    // '@<(!--.*--)>@i'  => '<p>&lt;$1&gt;</p>',
);

//--------------------------------------------------------------------------

$navbar = $nc_core->ui->navbar()->fixed()->red()->small()->style('box-shadow:0 0 12px rgba(0,0,0,.7) !important');
$menu = $navbar->menu();
$menu->add_btn('#')->icon_large('logo-white');

foreach ($navbar_menu as $title => $subs) {
    $submenu = $menu->add_btn('#', $title)->submenu();

    foreach ($subs as $com => $sub_title) {
        if ($sub_title[0] == '-') {
            $submenu->add_divider();
        } else {
            $submenu->add_btn('#' . $com,  $sub_title);
        }
    }
}

echo $navbar;
//--------------------------------------------------------------------------
?>



<script type="text/javascript">
// type
// 0: preview
// 1: php
// 2: html
function example(ob, type)
{
    type = type || 0;

    $link = $nc(ob);
    $link.parent().find('a.selected').removeClass('selected');
    $link.addClass('selected');

    $sub  = $link.parents('div.sub');
    switch (type)
    {
        case 0:
            $sub.find('div.php, div.html').slideUp(200, function(){
                $sub.find('div.example').fadeIn(500);
            });
            break;
        case 1:
            $sub.find('div.example, div.html').fadeOut(200,function(){
                $sub.find('div.php').slideDown(300);
            });
            break;
        case 2:
            $sub.find('div.example, div.php').fadeOut(200,function(){
                $sub.find('div.html').slideDown(300);
            });
            break;
    }
    return false;
}
</script>



<? foreach ($components as $key => $com): ?>
    <div class="sectin_header_anchor" id="<?=$key ?>">
        <div class="sectin_header_w">
            <div class="container"><?=$com->title ?> <small>netcat/components/_<?=$key ?>.scss</small></div>
        </div>
        <div class="section">
            <? foreach ($com->examples as $subtitle => $html): ?>
                <div class="sub">
                    <div class="h2">
                        <div class="container">
                            <?=$subtitle ?>
                            <div class="tabs">
                                <a href="#" onclick="return example(this,0)" class="selected">Example</a>
                                <a href="#" onclick="return example(this,1)">PHP</a>
                                <a href="#" onclick="return example(this,2)">HTML</a>
                            </div>
                        </div>
                    </div>
                    <div class="container">
                        <div class="example"><?=$html ?></div>
                        <div class="source html"><pre><?=preg_replace(array_keys($replace), $replace, trim($html)) ?></pre><div></div></div>
                        <div class="source php"><pre><?=preg_replace(array_keys($replacePhp), $replacePhp, trim($com->sources[$subtitle])) ?></pre><div></div></div>
                    </div>
                </div>
            <? endforeach ?>
        </div>
    </div>
<? endforeach ?>


<? /*=number_format(microtime(TRUE) - $run_time, 3) */ ?>

</body>
</html>