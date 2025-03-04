<?php

const NETCAT_MODULE_TAGSCLOUD_DESCRIPTION = 'This module is designed to display the tag cloud.';

const NETCAT_MODULE_TAGSCLOUD_LEGEND = 'Adding tags functional in templates';
const NETCAT_MODULE_TAGSCLOUD_SELECT_CLASS = 'Select Template';
const NETCAT_MODULE_TAGSCLOUD_ADD_BUTTON = 'Add functionality';
const NETCAT_MODULE_TAGSCLOUD_DROP_BUTTON = 'Remove functional';
const NETCAT_MODULE_TAGSCLOUD_TAGS = 'Tags';

const NETCAT_MODULE_TAGSCLOUD_ADD_FIELD = '<li>Tags field already exists in the template!';
const NETCAT_MODULE_TAGSCLOUD_ADD_ADDACTION = '<li>In Action pattern ("After adding") contains the desired functionality, check the template and fix it!';
const NETCAT_MODULE_TAGSCLOUD_ADD_EDITACTION = '<li>In Action pattern ("After changing") contains the desired functionality, check the template and fix it!';
const NETCAT_MODULE_TAGSCLOUD_ADD_DROPACTION = '<li>In Action pattern ("After deleting") contains the desired functionality, check the template and fix it!';
const NETCAT_MODULE_TAGSCLOUD_ADD_SETTINGS = "<li>In the system settings template contains the desired functionality, check the template and fix it!";
const NETCAT_MODULE_TAGSCLOUD_ADD_SOME = "<b>Made all actions, except:</b>\r\n<ul>";
const NETCAT_MODULE_TAGSCLOUD_ADD_ALL = "<b>Adding functionality to the template \"%ClassName\" impossible, reason:</b>\r\n<ul>";
const NETCAT_MODULE_TAGSCLOUD_ADD_DONE = 'Functional successfully added.';

const NETCAT_MODULE_TAGSCLOUD_DROP_FIELD = '<li>Field Tags is not in the template!';
const NETCAT_MODULE_TAGSCLOUD_DROP_ADDACTION = '<li>In Action pattern ("After adding") is missing necessary functional or values differ from the standard, check the template and fix it by removing the function call nc_tag_add()!';
const NETCAT_MODULE_TAGSCLOUD_DROP_EDITACTION = '<li>In Action pattern ("After removal") is missing necessary functional or values differ from the standard, check the template and fix it by removing the function call nc_tag_add() and nc_tag_add()!';
const NETCAT_MODULE_TAGSCLOUD_DROP_DROPACTION = '<li>In Action pattern ("After deleting") is missing necessary functional or values differ from the standard, check the template and fix it by removing the function call nc_tag_drop()!';
const NETCAT_MODULE_TAGSCLOUD_DROP_SETTINGS = '<li>In the system settings template desired functional or missing values differ from the standard, check the template and fix it by removing the condition if($tags_messages) ...!';
const NETCAT_MODULE_TAGSCLOUD_DROP_SOME = "<b>Made all actions, except:</b>\r\n<ul>";
const NETCAT_MODULE_TAGSCLOUD_DROP_ALL = "<b>Removing the functionality from the template \"%ClassName\" impossible, reason:</b>\r\n<ul>";
const NETCAT_MODULE_TAGSCLOUD_DROP_DONE = 'Functional successfully removed.';
