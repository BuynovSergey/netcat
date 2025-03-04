<?php

require_once("./function.inc.php");
$nc_core = nc_Core::get_object();

if (!empty($action) && $action == 'delete') {

    $message_id = intval($formData['messageid']);
    $component = new nc_Component($formData['classid']);
    $systemTableID = $component->get_system_table_id();
    $systemTableName = $systemTableID ? $formData['classid'] : '';
    $fields = $component->get_fields(NC_FIELDTYPE_FILE);
    if (!empty($fields)) {
        foreach ($fields as $v) {
            if ($v['id'] == $formData['fieldname'] || $v['name'] == $formData['fieldname']) {
                $rawformat = $v['format'];
                $field_id = $v['id'];
                $field_name = $v['name'];
            }
        }
    } else {
        return null; //wrong class or field
    }

    if (!$systemTableID) {
        $msg = $nc_core->db->get_row("SELECT `Sub_Class_ID`, `Subdivision_ID` FROM `Message{$formData['classid']}` WHERE `Message_ID` = '{$message_id}'", ARRAY_A);
    } else {
        $msg = $nc_core->db->get_row("SELECT COUNT(*) FROM `{$systemTableName}` WHERE `{$systemTableName}_ID` = {$message_id}", ARRAY_A);
    }
    if ($message_id && empty($msg)) {
        return null;  //wrong message
    }
    if (!$message_id) {
        return null;
    }

    DeleteFile($field_id, $field_name, $formData['classid'], $systemTableName, $message_id);


    // вместо удаления файла заменяем его прозрачной картинкой 1x1
    /*$tmp_path = explode("?", $source);
    $old_source = $tmp_path[0];
    if (!empty($old_source) && is_file($NETCAT_FOLDER . $old_source)) {
        $image = imagecreatetruecolor(1, 1);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        
        $old_path = $NETCAT_FOLDER . $old_source;
            
        unlink($old_path);

        imagepng($image, $old_path);
        var_dump($image);
    }*/
    exit;
}

if (!empty($_FILES['new_image'])) {
    $file_info = $nc_core->files->field_save_file(
        $classid, $fieldname, $messageid, $_FILES["new_image"], false, false, false, false
    );
    echo json_encode($file_info);
    exit;
}

if (!empty($action) && $action == 'crop' && !empty($source)) {
    $tmp_path = explode("?", $source);
    $source = $tmp_path[0];
    if (!empty($source) && is_file($NETCAT_FOLDER . $source)) {

        $path = $NETCAT_FOLDER . $source;
        if ($is_new == 1) {
            /*$tmp_path = explode("?", $old_source);
            $old_source = $tmp_path[0];
            $old_path = $NETCAT_FOLDER . $old_source;
            
            unlink($old_path);

            rename($path, $old_path);
            $path = $old_path;*/

            $classId = intval($formData['classid']);
            $messageId = intval($formData['messageid']);
            $fieldName = $formData['fieldname'];
            $updateString = basename($source) . ':' . nc_file_mime_type($path) . ':' . filesize($path) . ':' .
                preg_replace('/^\/.+?\//i', '', $source);

            $SQL = "UPDATE `Message" . $classId . "`
                SET `" . $fieldName . "` = '" . $updateString . "',
                    `LastUser_ID` = $AUTH_USER_ID,
                    `LastIP` = '" . $db->escape($REMOTE_ADDR) . "',
                    `LastUserAgent` = '" . $db->escape($HTTP_USER_AGENT) . "'
                    WHERE `Message_ID` = " . $messageId;

            $resMsg = $db->query($SQL);
        }

        $dst_x = 0;   // X-coordinate of destination point
        $dst_y = 0;   // Y-coordinate of destination point
        $src_x = intval($startX); // Crop Start X position in original image
        $src_y = intval($startY); // Crop Srart Y position in original image

        $src_w = intval($width); // selection width
        $src_h = intval($height); // selection height

        if (!empty($dimension)) {
            $tmp_dimension = explode("x", $dimension);
            $dst_w = intval($tmp_dimension[0]); // Thumb width
            $dst_h = intval($tmp_dimension[1]); // Thumb height
        } else {
            $dst_w = $src_w;
            $dst_h = $src_h;
        }

        $image_info = getimagesize($path);
        $image_type = $image_info[2];

        switch ($image_type) {
            case "1" :
                $img_r = imagecreatefromgif($path);
                $transparent = imagecolorallocatealpha($img_r, 0, 0, 0, 127);
                imagefill($img_r, 0, 0, $transparent);
                imagealphablending($img_r, false);
                break; /// GIF
            case "2" :
                $img_r = imagecreatefromjpeg($path);
                break; /// JPG
            case "3" :
                $img_r = imagecreatefrompng($path);
                break; /// PNG
        }
        $dst_r = imagecreatetruecolor($dst_w, $dst_h);
        if ($image_type == '3') {
            imagealphablending($dst_r, false);
            imagesavealpha($dst_r, true);
        }

        imagecopyresampled($dst_r, $img_r, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        switch ($image_type) {
            case "1" :
                imagegif($dst_r, $path);
                break; /// GIF
            case "2" :
                imagejpeg($dst_r, $path, 90);
                break; /// JPG
            case "3" :
                imagepng($dst_r, $path, 8);
                break; /// PNG
        }
        imagedestroy($img_r);
        imagedestroy($dst_r);
    }
}