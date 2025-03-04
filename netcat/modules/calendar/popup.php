<?php

/* $Id$ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($INCLUDE_FOLDER."index.php");

$id = intval($nc_core->input->fetch_get_post('theme'));
$day = intval($nc_core->input->fetch_get_post('day'));
$month = intval($nc_core->input->fetch_get_post('month'));
$year = intval($nc_core->input->fetch_get_post('year'));
$field_day = htmlspecialchars($nc_core->input->fetch_get_post('field_day'), ENT_QUOTES);
$field_month = htmlspecialchars($nc_core->input->fetch_get_post('field_month'), ENT_QUOTES);
$field_year = htmlspecialchars($nc_core->input->fetch_get_post('field_year'), ENT_QUOTES);

if (!$day) $day = date("d");
if (!$month) $month = date("m");
if (!$year) $year = date("Y");


echo
nc_set_calendar($id).
nc_show_calendar($id, 0, $year."-".$month."-".$day, '', 1, $year."-".$month."-".$day, 1, array($field_day, $field_month, $field_year));