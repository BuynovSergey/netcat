<?php
/*$Id$*/
/* Deprecated-функции */

/* Список функций:
 * nc_gzip_check
 * LoadEnv
 * InheritCatalogueEnv
 * LoadUserEnv
 * LoadModuleEnv
 * InheritTemplateEnv
 * InheritSubdivisionEnv
 * InheritSubClassEnv
 * InheritSystemFields
 * ConvertSystemVars
 * DeleteMessage
 * IsCatalogueExist
 * GetDomainBySubdivisionID
 * GetDomainByCatalogueID
 * GetCatalogueBySubdivision
 * GetCatalogueNameByID
 * GetSubdivisionNameByID
 * GetCatalogueNameBySubdivisionID
 * GetSubdivisionIDByMessageID
 * DeleteAllPermission
 * DeleteInSubscribe
 * GetEmailByUserID
 * SendEmail
 * SendEmailFromEmail
 * GetCatalogueByHostName
 * GetCatalogueID
 * ListClassVars
 * GetSubdivisionByID
 * GetSubdivisionID
 * LoadSystemSettings
 * LoadSystemTableClass
 * GetCatalougeLanguge
 */


function nc_gzip_check() {
  global $nc_core;

  return $nc_core->gzip->check();
}

function LoadEnv() {
  global $nc_core;

  $nc_core->load_env();
}

function InheritCatalogueEnv ($catalogue) {
  global $nc_core;

  return $nc_core->catalogue->get_by_id($catalogue);
}

function LoadUserEnv () {
  global $db, $AUTH_USER_ID, $current_user;

  // we've got already user info
  if (!$current_user["User_ID"]) {
    $current_user =$db->get_row("SELECT * FROM `User` WHERE `User_ID` = '".$AUTH_USER_ID."'", ARRAY_A);
  }

  $current_user = ConvertSystemVars($current_user, "User");
}

function LoadModuleEnv () {
  // нужны все переменные из vars.inc.php, т.к. в модуле они могут быть не доступны из-зи include_once!
  global $nc_core, $db, $Catalouge_ID, $catalogue, $MODULE_VARS, $DOMAIN_NAME, $use_gzip_compression;
  global $ADMIN_AUTHTYPE, $PHP_TYPE, $REDIRECT_STATUS, $ADMIN_LANGUAGE, $FILECHMOD, $DIRCHMOD, $SHOW_MYSQL_ERRORS;
  global $SUB_FOLDER, $ROOT_FOLDER, $FILES_FOLDER, $DUMP_FOLDER, $INCLUDE_FOLDER, $TMP_FOLDER, $MODULE_FOLDER, $ADMIN_FOLDER;
  global $HTTP_IMAGES_PATH, $HTTP_ROOT_PATH, $HTTP_FILES_PATH, $HTTP_DUMP_PATH;
  global $ADMIN_PATH, $ADMIN_TEMPLATE, $ADMIN_TEMPLATE_FOLDER;
  global $AUTHORIZE_BY, $AUTHORIZATION_TYPE;

  // return $MODULE_VARS
  return $nc_core->modules->load_env();

}

function InheritTemplateEnv ($template) {
  global $nc_core, $db, $AUTH_USER_ID, $templatePreview;

  $template += 0;
  $table_name = 'Template';### essence

  $template_env = $db->get_row("SELECT * FROM `Template` WHERE `Template_ID` = '".intval($template)."'", ARRAY_A);

  $template_env = ConvertSystemVars($template_env, "Template");

  // Блок для предпросмотра макетов дизайна
  $magic_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
  if ( $template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview]) ) {
    foreach($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
      $template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
    }
  }

  $parent_template = $template_env["Parent_Template_ID"];

  while ($parent_template) {
    $parent_template_env = $db->get_row("SELECT * FROM `Template` WHERE `Template_ID` = '".intval($parent_template)."'",ARRAY_A);

    // Если мы вызываем предпросмотр для макета, а он используется в качестве родительского.
    if ( $parent_template_env["Template_ID"]==$templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview]) ) {
      foreach($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
        $parent_template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
      }
    }

    $parent_template = $template_env["Parent_Template_ID"];

    if (!$template_env["Header"]) {
      $template_env["Header"] = $parent_template_env["Header"];
    }
    else {
      if ($parent_template_env["Header"]) { $template_env["Header"] = str_replace ("%Header", $parent_template_env["Header"], $template_env["Header"]); }
    }
    if (!$template_env["Footer"]) {
      $template_env["Footer"] = $parent_template_env["Footer"];
    }
    else {
      if ($parent_template_env["Footer"]) { $template_env["Footer"] = str_replace ("%Footer", $parent_template_env["Footer"], $template_env["Footer"]); }
    }
    $template_env["Settings"] = $parent_template_env["Settings"].$template_env["Settings"];

    $parent_template_env = ConvertSystemVars($parent_template_env, "Template");

    $template_env = InheritSystemFields("Template",$parent_template_env,$template_env);
    $parent_template = $parent_template_env["Parent_Template_ID"];
  }

  // load system table fields
  $table_fields = $nc_core->get_system_table_fields($table_name);
  // count
  $counted_fileds = count( $table_fields );

  for ($i=0; $i < $counted_fileds; $i++) {
    $template_env["Header"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Header"]);
    $template_env["Footer"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Footer"]);
  }

  // add system CSS styles in admin mode
  if ($nc_core->admin_mode && !$nc_core->inside_admin && $template_env["Header"]) {
    // addon css
    $template_admin_css = "<link type='text/css' rel='stylesheet' href='" .
        nc_add_revision_to_url($nc_core->ADMIN_TEMPLATE . 'css/admin_pages.css') . "'/>";
    // pattern and replacement for preg_replace()
    switch (true) {
      case nc_preg_match("/\<\s*?\/head\s*?\>/im", $template_env["Header"]):
        $preg_pattern = "/(\<\s*?\/head\s*?\>){1}/im";
        $preg_replacement = $template_admin_css."\n\$1";
      break;
      case nc_preg_match("/\<\s*?html\s*?\>/im", $template_env["Header"]):
        $preg_pattern = "/(\<\s*?html\s*?\>){1}/im";
        $preg_replacement = "\$1\n<head>".$template_admin_css."</head>";
      break;
      default:
        $preg_pattern = "/(\A)/im";
        $preg_replacement = $template_admin_css."\n\$1";
    }
    $template_env["Header"] = nc_preg_replace($preg_pattern, $preg_replacement, $template_env["Header"]);
  }

    if ($AUTH_USER_ID && $nc_core->get_settings('QuickBar')) {
        require_once($nc_core->INCLUDE_FOLDER . "quickbar.inc.php");
        $template_env["Header"] = nc_quickbar_in_template_header($template_env["Header"]);
    }

  return $template_env;
}

function InheritSubdivisionEnv ($sub) {
  global $nc_core;
  // this global variables change in this function
  global $parent_sub_tree, $sub_level_count;

  // get subdivision data and convert system fields
  $sub_env = $nc_core->subdivision->get_by_id($sub);

  $sub_level_count = 0;
  $parent_sub_tree[] = "";

  $parent_sub_tree[$sub_level_count] = $sub_env;
  $sub_level_count++;

  $parent_sub = $sub_env["Parent_Sub_ID"];

  while ($parent_sub) {
    $parent_sub_env = $nc_core->subdivision->get_by_id($parent_sub);

    if (!$sub_env["Template_ID"]) {
      $sub_env["Template_ID"] = $parent_sub_env["Template_ID"];
      $sub_env["TemplateSettings"] = $parent_sub_env["TemplateSettings"];
    }
    if (!$sub_env["Read_Access_ID"]) $sub_env["Read_Access_ID"] = $parent_sub_env["Read_Access_ID"];
    if (!$sub_env["Write_Access_ID"]) $sub_env["Write_Access_ID"] = $parent_sub_env["Write_Access_ID"];
    if (!$sub_env["Edit_Access_ID"]) $sub_env["Edit_Access_ID"] = $parent_sub_env["Edit_Access_ID"];
    if (!$sub_env["Subscribe_Access_ID"]) $sub_env["Subscribe_Access_ID"] = $parent_sub_env["Subscribe_Access_ID"];
    if ( $nc_core->modules->get_by_keyword("cache") ) {
      if (!$sub_env["Cache_Access_ID"]) $sub_env["Cache_Access_ID"] = $parent_sub_env["Cache_Access_ID"];
      if (!$sub_env["Cache_Lifetime"]) $sub_env["Cache_Lifetime"] = $parent_sub_env["Cache_Lifetime"];
    }
    if (!$sub_env["Moderation_ID"]) $sub_env["Moderation_ID"] = $parent_sub_env["Moderation_ID"];

    $parent_sub_tree[$sub_level_count] = $parent_sub_env;
    $sub_env = InheritSystemFields ("Subdivision",$parent_sub_tree[$sub_level_count],$sub_env);

    $sub_level_count++;

    $parent_sub = $parent_sub_env["Parent_Sub_ID"];
  }

  $current_catalogue = $nc_core->catalogue->get_by_id($sub_env["Catalogue_ID"]);
  $catalogue_env = $current_catalogue;

  $sub_env = InheritSystemFields ("Catalogue",$catalogue_env,$sub_env);

  if (!$sub_env["Template_ID"]) {
   $sub_env["Template_ID"] = $catalogue_env["Template_ID"];
   $sub_env["TemplateSettings"] = $catalogue_env["TemplateSettings"];
  }

  if (!$sub_env["Read_Access_ID"]) $sub_env["Read_Access_ID"] = $catalogue_env["Read_Access_ID"];
  if (!$sub_env["Write_Access_ID"]) $sub_env["Write_Access_ID"] = $catalogue_env["Write_Access_ID"];
  if (!$sub_env["Edit_Access_ID"]) $sub_env["Edit_Access_ID"] = $catalogue_env["Edit_Access_ID"];
  if (!$sub_env["Subscribe_Access_ID"]) $sub_env["Subscribe_Access_ID"] = $catalogue_env["Subscribe_Access_ID"];
  if ( $nc_core->modules->get_by_keyword("cache") ) {
    if (!$sub_env["Cache_Access_ID"]) $sub_env["Cache_Access_ID"] = $catalogue_env["Cache_Access_ID"];
    if (!$sub_env["Cache_Lifetime"]) $sub_env["Cache_Lifetime"] = $catalogue_env["Cache_Lifetime"];
  }
  if (!$sub_env["Moderation_ID"]) $sub_env["Moderation_ID"] = $catalogue_env["Moderation_ID"];

  $parent_sub_tree[$sub_level_count] = $catalogue_env;

  return $sub_env;
}

function InheritSubClassEnv ($cc) {
  global $nc_core;

  if ($cc) {
    // get cc data associative array
    $cc_env = $nc_core->sub_class->get_by_id($cc);

    if ($cc_env) {
      // inherit from Class
      $class_env = $nc_core->component->get_by_id($cc_env["Class_ID"]);

      foreach ($class_env AS $key => $val) {
        if ($cc_env[$key]=="") $cc_env[$key] = $val;
      }

      if ($cc_env["NL2BR"] == -1) $cc_env["NL2BR"] = $class_env["NL2BR"];
      if ($cc_env["AllowTags"] == -1) $cc_env["AllowTags"] = $class_env["AllowTags"];
      if ($cc_env["UseCaptcha"] == -1) $cc_env["UseCaptcha"] = $class_env["UseCaptcha"];
    }
  }

  // inherit from Subdivision
  $sub_env = $nc_core->subdivision->get_current();

  if (!$cc_env["Read_Access_ID"]) $cc_env["Read_Access_ID"] = $sub_env["Read_Access_ID"];
  if (!$cc_env["Write_Access_ID"]) $cc_env["Write_Access_ID"] = $sub_env["Write_Access_ID"];
  if (!$cc_env["Edit_Access_ID"]) $cc_env["Edit_Access_ID"] = $sub_env["Edit_Access_ID"];
  if (!$cc_env["Subscribe_Access_ID"]) $cc_env["Subscribe_Access_ID"] = $sub_env["Subscribe_Access_ID"];
  if ( $nc_core->modules->get_by_keyword("cache") ) {
    if (!$cc_env["Cache_Access_ID"]) $cc_env["Cache_Access_ID"] = $sub_env["Cache_Access_ID"];
    if (!$cc_env["Cache_Lifetime"]) $cc_env["Cache_Lifetime"] = $sub_env["Cache_Lifetime"];
  }
  if (!$cc_env["Moderation_ID"]) $cc_env["Moderation_ID"] = $sub_env["Moderation_ID"];

  return $cc_env;
}

function InheritSystemFields ($system_table_name, $parent_array, $child_array) {
  global $nc_core;

  // load system table fields
  $table_fields = $nc_core->get_system_table_fields($system_table_name);
  // count
  $counted_fileds = count( $table_fields );

  for ($i=0; $i < $counted_fileds; $i++) {
    if (!$table_fields[$i]['inheritance']) continue;

    $field_name = $table_fields[$i]['name'];

    if ($child_array[$field_name] === "" || $child_array[$field_name] == NULL) {
      switch ($table_fields[$i]['type']) {
        case NC_FIELDTYPE_FILE:
          $child_array[$field_name] = $parent_array[$field_name];
          $child_array[$field_name.'_name'] = $parent_array[$field_name.'_name'];
          $child_array[$field_name.'_size'] = $parent_array[$field_name.'_size'];
          $child_array[$field_name.'_type'] = $parent_array[$field_name.'_type'];
          $child_array[$field_name.'_url'] = $parent_array[$field_name.'_url'];
          break;
        case NC_FIELDTYPE_DATETIME:
          $child_array[$field_name] = $parent_array[$field_name];
          $child_array[$field_name.'_day'] = $parent_array[$field_name.'_day'];
          $child_array[$field_name.'_month'] = $parent_array[$field_name.'_month'];
          $child_array[$field_name.'_year'] = $parent_array[$field_name.'_year'];
          $child_array[$field_name.'_hours'] = $parent_array[$field_name.'_hours'];
          $child_array[$field_name.'_minutes'] = $parent_array[$field_name.'_minutes'];
          $child_array[$field_name.'_seconds'] = $parent_array[$field_name.'_seconds'];
          break;
        case NC_FIELDTYPE_MULTISELECT:
          $child_array[$field_name] = $parent_array[$field_name];
          $child_array[$field_name.'_id'] = $parent_array[$field_name.'_id'];
          break;
        default:
          $child_array[$field_name] = $parent_array[$field_name];
          break;
      }
    }
    else if ( $child_array[$field_name] == 0){ // list, наследуется - если элемент = 0
      if ( $table_fields[$i]['type'] == NC_FIELDTYPE_SELECT ) {
        $child_array[$field_name] = $parent_array[$field_name];
        $child_array[$field_name.'_id'] = $parent_array[$field_name.'_id'];
      }
    }

  }

  return $child_array;
}

function ConvertSystemVars ($env_array, $table_name) {
  global $nc_core;

  // load system table fields
  $table_fields = $nc_core->get_system_table_fields($table_name);
  // count
  $counted_fileds = count( $table_fields );
  // поля типа файл
  $file_field = array();
  $filetable = array ();
  // найдем все поля типа файл
  for ( $i = 0; $i < $counted_fileds; $i++ ) {
    if ( $table_fields[$i]['type'] == NC_FIELDTYPE_FILE ) {
      $file_field[$table_fields[$i]['id']] = $table_fields[$i]['id'];
    }
  }

  // если есть поля типа файл, то выполним запрос к Filetable
  if ( !empty($file_field) ) {
    $file_in_table = $nc_core->db->get_results("SELECT `Virt_Name`, `File_Path`, `Message_ID`, `Field_ID`
                               FROM `Filetable`
                               WHERE `Field_ID` IN (".join(',', $file_field).")", ARRAY_A);
    if ( !empty($file_in_table) ) {
      foreach ( $file_in_table as $v ) {
        $filetable[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['File_Path']);
      }
    }
  }

  // Проход по всем полям
  for ($i=0; $i < $counted_fileds; $i++) {
    $field_id = $table_fields[$i]['id'];
    $field_name = $table_fields[$i]['name'];
    $field_type = $table_fields[$i]['type'];
    $field_format = $nc_core->db->escape( $table_fields[$i]['format'] );

    if ($env_array[$field_name]) {
      switch ($field_type) {
        case NC_FIELDTYPE_SELECT:
          $listname = $nc_core->db->get_var("SELECT `".$field_format."_Name` FROM `Classificator_".$field_format."` WHERE `".$field_format."_ID` = '".$env_array[$field_name]."'");

          $env_array[$field_name."_id"] = $env_array[$field_name];
          $env_array[$field_name] = $listname;
          break;
        case NC_FIELDTYPE_FILE:
          //file_data - массив с ориг.названием, типом, размером, [именем_файла_на_диске]
          $file_data = explode(':', $env_array[$field_name]);

          $env_array[$field_name."_name"] = $file_data[0]; // оригинальное имя
          $env_array[$field_name."_type"] = $file_data[1]; // тип
          $env_array[$field_name."_size"] = $file_data[2]; // размер
          $ext = substr( $file_data[0], strrpos($file_data[0], ".") );  // расширение

          // запись в таблице Filetable
          $row = $filetable[$env_array[$table_name."_ID"]][$field_id];
          if (  $row ) { // Proteced FileSystem
            $env_array[$field_name] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.ltrim($row[1], '/')."h_".$row[0];
            $env_array[$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.ltrim($row[1], '/').$row[0];
          }
          else {
            if ( $file_data[3]) { // Original FileSystem
              $env_array[$field_name] = $env_array[$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.$file_data[3];
            }
            else { // Simple FileSysytem
              $env_array[$field_name] = $env_array[$field_name."_url"] = $nc_core->SUB_FOLDER.$nc_core->HTTP_FILES_PATH.$field_id."_".$env_array[$table_name."_ID"].$ext;
            }
          }
          break;
        case NC_FIELDTYPE_DATETIME:
          $env_array[$field_name."_year"] = substr($env_array[$field_name], 0, 4);
          $env_array[$field_name."_month"] = substr($env_array[$field_name], 5, 2);
          $env_array[$field_name."_day"] = substr($env_array[$field_name], 8, 2);
          $env_array[$field_name."_hours"] = substr($env_array[$field_name], 11, 2);
          $env_array[$field_name."_minutes"] = substr($env_array[$field_name], 14, 2);
          $env_array[$field_name."_seconds"] = substr($env_array[$field_name], 17, 2);
          break;
        case NC_FIELDTYPE_MULTISELECT:
          $array_with_id = explode(',' , $env_array[$field_name] );

          if (!$array_with_id[0]) unset($array_with_id[0]);
          if (!$array_with_id[count($array_with_id)]) unset($array_with_id[count($array_with_id)]);
          if (empty($array_with_id)) break;

          $listname = $nc_core->db->get_col("SELECT `".strtok($field_format, ':')."_Name`
            FROM `Classificator_".strtok($field_format, ':')."`
            WHERE `".strtok($field_format, ':')."_ID` IN(".join(',', $array_with_id).")");

          $env_array[$field_name."_id"] = (array)$array_with_id;
          $env_array[$field_name] = (array)$listname;
          break;
      }
    }
  }

  return $env_array;
}

function DeleteMessage ($MessageID, $ClassID) {
  global $db;

  $MessageID = intval($MessageID);
  $ClassID = intval($ClassID);

  $delete = "delete from Message".$ClassID;
  $delete .= " where Message_ID='".$MessageID."'";
  $Result = $db->query ($delete);

  $DeleteMessageParent = "delete from Message".$ClassID." where Parent_Message_ID='".$MessageID."'";
  $db->query ($DeleteMessageParent);

  if ($Result) return 1;
  return 0;
}

function IsCatalogueExist ($CatalogueID) {
  global $db;

  return $db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Catalogue_ID` = '".intval($CatalogueID)."'");
}

function GetDomainBySubdivisionID ($SubdivisionID) {
  global $db;

  return $db->get_var("SELECT `Domain` FROM `Catalogue` AS c, `Subdivision` AS s
    WHERE s.`Subdivision_ID` = '".intval($SubdivisionID)."'
    AND s.`Catalogue_ID` = c.`Catalogue_ID`");
}

function GetDomainByCatalogueID ($CatalogueID) {
  global $db;

  return $db->get_var("SELECT `Domain` FROM `Catalogue` WHERE `Catalogue_ID` = '".intval($CatalogueID)."'");
}

function GetCatalogueBySubdivision ($SubdivisionID) {
  global $nc_core;

  return $nc_core->subdivision->get_by_id($SubdivisionID, "Catalogue_ID");
}

function GetCatalogueNameByID ($CatalogueID) {
  global $nc_core;

  return $nc_core->catalogue->get_by_id($CatalogueID, "Catalogue_Name");
}

function GetSubdivisionNameByID ($SubdivisionID) {
  global $nc_core;

  return $nc_core->subdivision->get_by_id($SubdivisionID, "Subdivision_Name");
}

function GetCatalogueNameBySubdivisionID ($SubdivisionID) {
  global $db;

  return $db->get_var("SELECT c.`Catalogue_Name` FROM `Catalogue` AS c
    LEFT JOIN `Subdivision` AS s ON s.`Catalogue_ID` = c.`Catalogue_ID`
    WHERE s.`Subdivision_ID` = '".intval($SubdivisionID)."'");
}

function GetSubdivisionIDByMessageID ($MessageID, $ClassID) {
  global $db;

  return $db->get_var("SELECT `Subdivision_ID` FROM `Message".intval($ClassID)."`
    WHERE `Message_ID` = '".intval($MessageID)."'");
}

function DeleteAllPermission ($UserID) {
  global $db;

  $db->query("DELETE FROM `Permission` WHERE `User_ID` = '".intval($UserID)."'");

  return $db->rows_affected;
}

function DeleteInSubscribe ($UserID) {
  global $nc_core, $db;

  // is 'subscriber' module installed?
  if ( !$nc_core->modules->get_by_keyword('subscriber') ) return 1;
  $db->query("DELETE FROM `Subscriber` WHERE `User_ID` = '".intval($UserID)."'");


  return $db->rows_affected;
}

function GetEmailByUserID ($UserID) {
  global $nc_core;

  return $nc_core->user->get_by_id($UserID, "Email");
}

function SendEmail ($UserID, $Subject, $Message, $From) {
  global $db;

  $Email = GetEmailByUserID($UserID);

  mail($Email, $Subject, $Message, "From: ".$From."\nX-Mailer: PHP/" . phpversion()."\nContent-Type: text/plain\nContent-Transfer-Encoding: 8bit\n" );
}

function SendEmailFromEmail ($Email, $Subject, $Message, $From) {
  global $db;

  mail($Email, $Subject, $Message, "From: ".$From."\nX-Mailer: PHP/" . phpversion()."\nContent-Type: text/plain\nContent-Transfer-Encoding: 8bit\nReturn-Path: <".$From.">");
}

function GetCatalogueByHostName($host) {
  global $db;

  $res = $db->get_row("(SELECT * FROM `Catalogue`
    WHERE Domain = '".$db->escape($host)."'
    OR (CONCAT('|', REPLACE(Mirrors, '\\n', '|')) LIKE '%|".$db->escape($host)."%') LIMIT 1)
    UNION (SELECT * FROM `Catalogue` ORDER BY `Checked` = 1, `Priority`, `Catalogue_ID` LIMIT 1)
    LIMIT 1", ARRAY_A);

  return $res;
}

function GetCatalogueID ($host) {
    $catalogue_info = nc_Core::get_object()->catalogue->get_by_host_name($host);

    if (!$catalogue_info) {
        return 0;
    }

    return $catalogue_info['Catalogue_ID'] ?: 0;
}

function ListClassVars ($cc, $user_table_mode, $nc_ctpl = 0) {
  global $db, $perm;
  global $MODULE_VARS;
  global $cc_in_sub, $nc_core;
  global $current_cc;

  $nc_ctpl = intval($nc_ctpl);

  // cache section
  if ( 0 && $nc_core->modules->get_by_keyword("cache") ) {
    try {
      $nc_cache_function = nc_cache_function::getObject();
      $cc_data = $db->get_row("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`
        FROM `Sub_Class` WHERE `Sub_Class_ID` = '".intval($cc)."'", ARRAY_A);
      // make uniqe string concat parameters for this function
      $data_string = $user_table_mode . "-" . serialize($MODULE_VARS['auth']) . "-" . $_SERVER['HTTP_HOST'] . "-" . $nc_ctpl;
      // check cached data
      $cached_data = $nc_cache_function->read($cc_data, $data_string, $MODULE_VARS['cache']['DEFAULT_LIFETIME']);
      if ($cached_data!=-1) {
        // debug info
        $cache_debug_info = "Readed, catalogue[".$cc_data['Catalogue_ID']."], sub[".$cc_data['Subdivision_ID']."], cc[".$cc_data['Sub_Class_ID']."], Access_ID[-], Lifetime[-], bytes[".strlen($cached_data)."]";
        $nc_cache_function->debugMessage($cache_debug_info, __FILE__, __LINE__);
        // return cache if not eval flag setted
        $Array = unserialize($cached_data);
        return $Array;
      }
    }
    catch (Exception $e) {
      // for debug
      $nc_cache_function->errorMessage($e);
    }
  }

  if (!$user_table_mode) {
    $Array = array();

    if ($cc_in_sub) {
      foreach ($cc_in_sub as $row) {
        if ($row["Sub_Class_ID"]==$cc) {
          $Array = $row;
          break;
        }
      }
    }

    if ( empty($Array) ) {
      $Array = $db->get_row("SELECT * FROM `Sub_Class` WHERE `Sub_Class_ID` = '".intval($cc)."'", ARRAY_A);
    }


    if ( $current_cc['Sub_Class_ID'] == $cc && $nc_core->get_page_type() == 'rss' ) {
      $class_cond = "`Type` = 'rss' AND `ClassTemplate` = '".$Array["Class_ID"]."' ";
    }
    else
    if ($nc_ctpl) {
      $class_cond = "`Class_ID` = '".$nc_ctpl."'";
    }
    else {
      // use class template or not?
      $class_cond = "`Class_ID` = '".($Array["Class_Template_ID"] ? $Array["Class_Template_ID"] : $Array["Class_ID"])."'";
    }

    if ( $nc_core->inside_admin ) {
      $class_env = $db->get_row("SELECT * FROM Class WHERE `Type` = 'inside_admin' AND `ClassTemplate` = '".$Array["Class_ID"]."' LIMIT 1 ", ARRAY_A);
    }

    if ( !$nc_core->inside_admin &&  $nc_core->admin_mode ) {
      $class_env = $db->get_row("SELECT * FROM Class WHERE `Type` = 'admin_mode' AND `ClassTemplate` = '".$Array["Class_ID"]."' LIMIT 1 ", ARRAY_A);
    }
  }
  else {
    $user_table_id = $db->get_var("SELECT `System_Table_ID` FROM `System_Table` WHERE `System_Table_Name` = 'User'");
    $class_cond = "`System_Table_ID` = '".$user_table_id."'";
    $Array["System_Table_ID"] = $user_table_id;
    $Array["Subdivision_ID"] = $MODULE_VARS['auth']['USER_LIST_SUB'];
    $Array["EnglishName"] = $MODULE_VARS['auth']['USER_KEYWORD'];
  }


  //выйдем из функции, если $cc указан неверно
  if (!$Array) {
    // error message
    if ($perm instanceof Permission &&  $perm->isSupervisor()) {
      // backtrace info
      $debug_backtrace_info = debug_backtrace();
      // choose error
      if ( isset($debug_backtrace_info[1]['function']) && $debug_backtrace_info[1]['function']=="nc_objects_list" ) {
        // error info for the supervisor
        trigger_error( sprintf( NETCAT_FUNCTION_OBJECTS_LIST_CC_ERROR, $cc), E_USER_WARNING );
      }
      else {
        // error info for the supervisor
        trigger_error( sprintf( NETCAT_FUNCTION_LISTCLASSVARS_ERROR_SUPERVISOR, $cc), E_USER_WARNING );
      }
    }
    return NULL;
  }

  // inherit from Class
  if ( !$class_env ) $class_env = $db->get_row("SELECT * FROM `Class` WHERE ".$class_cond, ARRAY_A);

  if ($class_env['CustomSettingsTemplate']) {
    $a2f = new nc_a2f($class_env['CustomSettingsTemplate'], $Array['CustomSettings'], 'CustomSettings');
    $Array["Sub_Class_Settings"] = $a2f->get_values_as_array();
  }

  foreach ($class_env AS $key => $val) {
    if ($Array[$key]=="") $Array[$key] = $val;
  }

  if ($Array["NL2BR"] == -1) $Array["NL2BR"] = $class_env["NL2BR"];
  if ($Array["AllowTags"] == -1) $Array["AllowTags"] = $class_env["AllowTags"];
  if ($Array["UseCaptcha"] == -1) $Array["UseCaptcha"] = $class_env["UseCaptcha"];
  if ( $nc_core->modules->get_by_keyword("cache") ) {
    if ($Array["CacheForUser"] == -1) $Array["CacheForUser"] = $class_env["CacheForUser"];
  }

  if ($Array["Subdivision_ID"]) {
    $parent_sub = $Array["Subdivision_ID"];
    $Domain = $db->get_var(
        "SELECT Catalogue.Domain
         FROM `Catalogue`
         LEFT JOIN `Subdivision` ON Subdivision.Catalogue_ID = Catalogue.Catalogue_ID
         WHERE Subdivision.Subdivision_ID = '$parent_sub'"
    );
    $HiddenHost = $Domain ?: $_SERVER['HTTP_HOST'];

    while ($parent_sub) {
      $ParentArray = GetSubdivisionByID($parent_sub);

      if ($Array["Subdivision_ID"]==$parent_sub) {
        $Array["Subdivision_Name"] = $ParentArray["Subdivision_Name"];
        $Array["Hidden_URL"] = $ParentArray["Hidden_URL"];
        $Array["Hidden_Host"] = $HiddenHost;
      }

      if (!$Array["Read_Access_ID"]) $Array["Read_Access_ID"] = $ParentArray["Read_Access_ID"];
      if (!$Array["Write_Access_ID"]) $Array["Write_Access_ID"] = $ParentArray["Write_Access_ID"];
      if (!$Array["Edit_Access_ID"]) $Array["Edit_Access_ID"] = $ParentArray["Edit_Access_ID"];
      if (!$Array["Delete_Access_ID"]) $Array["Delete_Access_ID"] = $ParentArray["Delete_Access_ID"];
      if (!$Array["Checked_Access_ID"]) $Array["Checked_Access_ID"] = $ParentArray["Checked_Access_ID"];
      if (!$Array["Subscribe_Access_ID"]) $Array["Subscribe_Access_ID"] = $ParentArray["Subscribe_Access_ID"];
      if ( $nc_core->modules->get_by_keyword("cache") ) {
        if (!$Array["Cache_Access_ID"]) $Array["Cache_Access_ID"] = $ParentArray["Cache_Access_ID"];
        if (!$Array["Cache_Lifetime"]) $Array["Cache_Lifetime"] = $ParentArray["Cache_Lifetime"];
      }
      if (!$Array["Moderation_ID"]) $Array["Moderation_ID"] = $ParentArray["Moderation_ID"];

      $parent_sub = $ParentArray["Parent_Sub_ID"];
    }

    if (!$Array["Catalogue_ID"]) $Array["Catalogue_ID"] = $ParentArray["Catalogue_ID"];
  }

  if ($Array["Catalogue_ID"]==$GLOBALS["catalogue"]) {
    $ParentArray = $GLOBALS["current_catalogue"];
  }
  else {
    $ParentArray = $db->get_row("SELECT * FROM `Catalogue` WHERE `Catalogue_ID` = '".$Array["Catalogue_ID"]."'", ARRAY_A);
  }

  if (!$Array["Read_Access_ID"]) $Array["Read_Access_ID"] = $ParentArray["Read_Access_ID"];
  if (!$Array["Write_Access_ID"]) $Array["Write_Access_ID"] = $ParentArray["Write_Access_ID"];
  if (!$Array["Edit_Access_ID"]) $Array["Edit_Access_ID"] = $ParentArray["Edit_Access_ID"];
  if (!$Array["Subscribe_Access_ID"]) $Array["Subscribe_Access_ID"] = $ParentArray["Subscribe_Access_ID"];
  if (!$Array["Delete_Access_ID"]) $Array["Delete_Access_ID"] = $ParentArray["Delete_Access_ID"];
      if (!$Array["Checked_Access_ID"]) $Array["Checked_Access_ID"] = $ParentArray["Checked_Access_ID"];
  if ( $nc_core->modules->get_by_keyword("cache") ) {
    if (!$Array["Cache_Access_ID"]) $Array["Cache_Access_ID"] = $ParentArray["Cache_Access_ID"];
    if (!$Array["Cache_Lifetime"]) $Array["Cache_Lifetime"] = $ParentArray["Cache_Lifetime"];
  }
  if (!$Array["Moderation_ID"]) $Array["Moderation_ID"] = $ParentArray["Moderation_ID"];

  // cache section
  if ( $nc_core->modules->get_by_keyword("cache") && $Array['Cache_Access_ID']==1 && is_object($nc_cache_function) ) {
    try {
      $bytes = $nc_cache_function->add($Array, $data_string, serialize($Array) );
      // debug info
      if ($bytes) {
        $cache_debug_info = "Writed, catalogue[".$Array['Catalogue_ID']."], sub[".$Array['Subdivision_ID']."], cc[".$Array['Sub_Class_ID']."], Access_ID[-], Lifetime[-], bytes[".$bytes."]";
        $nc_cache_function->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
      }
    }
    catch (Exception $e) {
      // for debug
      $nc_cache_function->errorMessage($e);
    }
  }
  $Array['sysTbl'] += 0;
  return $Array;
}

function GetSubdivisionByID($sub) {
  global $_cache, $db;
  $sub = (int)$sub;

  if (!$_cache["sub"][$sub]) {
    $_cache["sub"][$sub] = $db->get_row("SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = '".intval($sub)."'", ARRAY_A);
  }

  return $_cache["sub"][$sub];
}

function GetSubdivisionID ($catalogue, $path) {
  global $db, $_cache, $nc_core;

  // validate Hidden_URL
  if ( !$nc_core->subdivision->validate_hidden_url($path) ) return false;

  $row = $db->get_row("SELECT * FROM `Subdivision` WHERE `Catalogue_ID` = '".intval($catalogue)."' AND `Hidden_URL` = '".$db->escape($path)."'", ARRAY_A);

  $_cache["sub"][$row["Subdivision_ID"]] = $row;

  return $row["Subdivision_ID"];
}

function LoadSystemSettings () {
  global $db, $system_env;

  $system_env = $db->get_row("SELECT settings.*, field.`Field_Name` AS UserEmail FROM `Settings` AS settings LEFT JOIN `Field` AS field ON settings.`UserEmailField` = field.`Field_ID` ORDER BY `Settings_ID` LIMIT 1", ARRAY_A);

  Header("X-Powered-By: ".$system_env['Powered']);

  return;
}

function LoadSystemTableClass ($SystemTableName) {
  global $db;

  $table_env = $db->get_row("SELECT * FROM `System_Table` AS a, `Class` AS b WHERE a.`System_Table_ID` = b.`System_Table_ID` AND a.`System_Table_Name` = '".$SystemTableName."'", ARRAY_A);

  return $table_env;
}

function GetCatalougeLanguge($catid = 0) {
  global $db, $catalogue, $current_catalogue;

  if (!$catalogue) $catalogue = $catid;

  // return current catalogue language
  if (is_array($current_catalogue) && ($current_catalogue["Catalogue_ID"]==$catid || $catid===0)) {
    return $current_catalogue["Language"];
  }

  $res = $db->get_var("SELECT `Language` FROM `Catalogue` WHERE `Catalogue_ID` = '".intval($catalogue)."' LIMIT 1");

  if ($res) {
    return($res);
  }
  else {
    return(false);
  }
}