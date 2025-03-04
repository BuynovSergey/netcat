<?php
/**
 * Для всех остальных языков.
 */
$php_as_cgi     = substr(PHP_SAPI, 0, 3) == 'cgi';
$step           = 0;
$for_activation = isset($for_activation) ? $for_activation : false;
?>

<style>
.nc-tools-path-info h2 {margin:20px 0 10px 0; padding:0; font-weight:normal; font-size:21px; color:#333;}
.nc-tools-path-info h2 span {display:inline-block; background:#1a87c2; width:30px; padding:5px 0; line-height:20px; text-align:center; color:#FFF; margin-right:10px}
.nc-tools-path-info em {color:#d62c1a; font-style: normal;}
code {font-family: }
</style>

<div class="nc-tools-path-info">

    <? if ( ! $for_activation): ?>
        <h2><span><?=++$step ?></span> Бэкап</h2>
        <p>Необходимо в обязательном порядке сделать бекап как файлов вашей системы, так и базы данных. Бекап базы, модулей и пользовательских файлов можно сделать при помощи стандартных средств системы – <a href='<?=$ADMIN_PATH ?>dump.php?phase='>Инструменты / Архивы проекта</a>. Полный бекап можно сделать путем скачивания всех файлов системы через FTP и копирования дампа базы данных через панель управления хостингом (например phpMyAdmin).</p>
        <hr>
    <? endif ?>

    <? if ( ! $php_as_cgi): ?>
        <h2><span><?=++$step ?></span> Права на файлы и папки</h2>
        <p>Перед установкой патча необходимо поставить на все директории и файлы сайта права <code class='nc-code'>0777</code>. Для рекурсивного (обработка с учетом вложенных объектов) изменения прав на файлы и папки по FTP можно воспользоваться бесплатным FTP-клиентом <a href="http://filezilla-project.org/download.php?type=client" target="_blank">FileZilla</a></p>
        Более надежным, но немного более сложным для пользователей, является метод доступа на основе SSH (Secure Shell). Ниже приведен пример для доступа через SSH (не для FTP):
        <pre class='nc-code'>chmod -R 0777 ./папка</pre>
        <p>"папка" – директория, где находятся файлы</p>
        <p>Для доступа к сайту по SSH следует получить соответствующие авторизационные данные к хостингу (логин, пароль и адрес сервера). Для ОС Windows можно использовать бесплатную программу SSH-клиент <a href="http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html" target="_blank">PuTTY</a></p>
        <hr>
    <? endif ?>

    <? if ($for_activation): ?>
        <h2><span><?=++$step ?></span> Введение регистрационных данных</h2>
        <p>Для активации системы Вам нужно ввести свой регистрационный код и ключ активации, которые Вы получите после покупки
.</p>
        <hr>
    <? else: ?>
        <h2><span><?=++$step ?></span> Применить патч</h2>
        <p>В разделе <a href="<?=$ADMIN_PATH ?>patch/index.php?phase=1">Инструменты / Обновление системы</a> выбрать файл с локального диска посредствам кнопки "Обзор", после чего нажать кнопку "Закачать".</p>
        <hr>
    <? endif ?>

    <? if ( ! $php_as_cgi): ?>
        <h2><span><?=++$step ?></span> Восстановление прав на файлы и папки</h2>
        <p>Необходимо восстановить права на все файлы и папки (рекомендуется <code class='nc-code'>0755</code> для папок и <code class='nc-code'>0644</code> или <code class='nc-code'>0666</code> для файлов, подробности по правам на файлы скриптов следует уточнить у службы поддержки хостинг-провайдера).</p>
        <p>На папки <code class='nc-code'><?= nc_core('HTTP_ROOT_PATH') ?>tmp/</code>, <code class='nc-code'><?= nc_core('HTTP_DUMP_PATH') ?></code>, <code class='nc-code'><?= nc_core('HTTP_FILES_PATH') ?></code>, <code class='nc-code'><?= nc_core('HTTP_TRASH_PATH') ?></code>, <code class='nc-code'><?= nc_core('HTTP_TEMPLATE_PATH') ?></code>, <code class='nc-code'><?= nc_core('HTTP_CACHE_PATH') ?></code> следует оставить права – <b>0777</b>.</p>
        <p>Права на файл <code class='nc-code'>/netcat/admin/crontab.php</code> – <b>0755</b>.</p>
        Запросы для возврата прежних прав по SSH:
        <pre class='nc-code'>cd <?=$DOCUMENT_ROOT ?>

find ./ -type f -exec chmod 0644 {} \;
find ./ -type d -exec chmod 0755 {} \;
chmod -R 0777 .<?= nc_core('HTTP_ROOT_PATH') ?>tmp/ .<?= nc_core('HTTP_DUMP_PATH') ?> .<?= nc_core('HTTP_FILES_PATH') ?> .<?= nc_core('HTTP_TRASH_PATH') ?> .<?= nc_core('HTTP_TEMPLATE_PATH') ?> .<?= nc_core('HTTP_CACHE_PATH') ?> 
chmod 0755 .<?= nc_core('HTTP_ROOT_PATH') ?>admin/crontab.php</pre>
        <hr>
    <? endif ?>

</div>