<?php

if (!class_exists("nc_core")) {
    die;
}


/** @var array $settings */

$form = new nc_a2f($settings, "settings");
$form->show_default_values(false);
$form->show_header(false);

echo $form->render();
