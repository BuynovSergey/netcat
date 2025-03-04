<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
require_once($NETCAT_FOLDER . "vars.inc.php");

// for IE
if (!isset($NC_CHARSET)) {
    $NC_CHARSET = "windows-1251";
}

// header with correct charset
header("Content-type: text/plain; charset=" . $NC_CHARSET);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// esoteric method...
ob_start("ob_gzhandler");

$res = "{'id':'0', 'parent_id':'0', 'commentHTML':'', 'error':''}";

if ($_POST['message_cc'] && $_POST['message_id']) {

    // disable auth screen
    define("NC_AUTH_IN_PROGRESS", 1);
    define("NC_ADDED_BY_AJAX", 1);

    // include system
    $passed_thru_404 = true;
    require($INCLUDE_FOLDER . "index.php");
    global $db, $AUTH_USER_ID;

    // component id must be different as $cc for example $needcc
    $message_cc = (int)$nc_core->input->fetch_post('message_cc');
    $message_id = (int)$nc_core->input->fetch_post('message_id');
    $parent_mess_id = (int)$nc_core->input->fetch_post('parent_mess_id');
    $template_id = (int)$nc_core->input->fetch_post('template_id');
    $last_updated = (int)$nc_core->input->fetch_post('last_updated');
    $comment_edit = (int)$nc_core->input->fetch_post('comment_edit');
    $comment = $nc_core->input->fetch_post('nc_commentTextArea');
    $nc_comments_guest_name = $nc_core->input->fetch_post('nc_comments_guest_name');
    $nc_comments_guest_email = $nc_core->input->fetch_post('nc_comments_guest_email');
    $settings = $nc_core->get_settings('', 'comments');


    // CAPTCHA
    if ($nc_core->modules->get_by_keyword("captcha")) {
        $nc_captcha_code = $nc_core->input->fetch_post('nc_captcha_code');
    }

    if ($comment && !$NC_UNICODE) {
        $comment = $nc_core->utf8->utf2win($comment);
        //$nc_comments_guest_name = $nc_core->utf8->utf2win($nc_comments_guest_name);
    }

    $user_id = $AUTH_USER_ID ? $AUTH_USER_ID : 0;

    // initialize nc_comments
    $nc_comments = new nc_comments($message_cc);

    // get template data
    $templateData = $nc_comments->_getTemplate($template_id);

    // CAPTCHA
    if ($nc_core->modules->get_by_keyword("captcha") && nc_array_value($settings, 'UseCaptcha')) {
        if (!$user_id && !nc_captcha_verify_code($nc_captcha_code, null, false)) {
            $new_hash = nc_captcha_generate_hash();
            nc_captcha_generate_code($new_hash);
            $templateData['Warn_Text'] = str_replace("%WARNTEXT", NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_CAPTCHA, $templateData['Warn_Text']);
            die("{'error':'enter', 'captchawrong':escape(\"" . $nc_comments->commentValidateShow($templateData['Warn_Text'], $template_id, 0, 1) . "\"), 'hash' : '" . $new_hash . "'}");
        }
    }


    //warnText
    if (!$AUTH_USER_ID) {
        if ($settings['GuestNameForced'] && !$nc_comments_guest_name && (!$settings['GuestEmailForced'] || $settings['GuestEmailForced'] && $nc_comments_guest_email)) {
            $templateData['Warn_Text'] = str_replace("%WARNTEXT", NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_NAME, $templateData['Warn_Text']);
            die("{'error':'enter', 'unset':escape(\"" . $nc_comments->commentValidateShow($templateData['Warn_Text'], $template_id, 0, 1) . "\")}");
        }
        if ($settings['GuestEmailForced'] && !$nc_comments_guest_email && (!$settings['GuestNameForced'] || $settings['GuestNameForced'] && $nc_comments_guest_name) || $nc_comments_guest_email && !nc_check_email($nc_comments_guest_email)) {
            $templateData['Warn_Text'] = str_replace("%WARNTEXT", NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_EMAIL, $templateData['Warn_Text']);
            die("{'error':'enter', 'unset':escape(\"" . $nc_comments->commentValidateShow($templateData['Warn_Text'], $template_id, 0, 1) . "\")}");
        }
        if ($settings['GuestNameForced'] && !$nc_comments_guest_name && $settings['GuestEmailForced'] && !$nc_comments_guest_email) {
            $templateData['Warn_Text'] = str_replace("%WARNTEXT", NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_EMAIL, $templateData['Warn_Text']);
            die("{'error':'enter', 'unset':escape(\"" . $nc_comments->commentValidateShow($templateData['Warn_Text'], $template_id, 0, 1) . "\")}");
        }
    }


    if (!$comment_edit && $comment) {
        // append comment into the base
        try {
            $comment_id = $nc_comments->addComment($message_id, $parent_mess_id, $comment, $user_id, $nc_comments_guest_name, $nc_comments_guest_email);
            require_once nc_module_folder('comments') . 'nc_commsubs.class.php';
            $nc_commsubs = new nc_commsubs();
            $nc_commsubs->new_comment($comment_id, $nc_comments->getMailTemplate(), $nc_comments->getMailSubject());
            //подписать комментатора если имеет право и еще не подписан
            if ($settings['Subscribe_Auto'] && $nc_comments->isRightsToSubscribe(0, $message_id) && !$nc_commsubs->is_subscribe($user_id, $message_cc, $message_id, 0)) {
                $nc_commsubs->subscribe($user_id, $message_cc, $message_id, 0);
            }
        } catch (Exception $e) {
            die("{'error':'" . $e->getMessage() . "'}");
        }
    } else {
        // load array for update
        $nc_comments->loadArrays($message_id);
        $comment_id = $parent_mess_id;
    }

    if ($comment_id) {
        // get need comments
        $data = $nc_comments->getNewComments($message_id, $last_updated, (!$comment_edit ? $comment_id : 0));
        // compile json result
        if (!empty($data)) {
            foreach ($data as $value) {
                // json string put in array
                if ($value['Updated'] > $last_updated) {
                    // if children exist - update block
                    if (!$nc_comments->getChildren($value['id'])) {
                        $CommentData = $nc_comments->getCommentFromArray($value['id']);
                        // drop from DOM
                        $resArr[] = "{'id':'" . $value['id'] . "', 'update':'-1', 'error':'0'}";
                        // get parent refreshaed block
                        $commentHTML = $nc_comments->getComment($message_id, $CommentData, $template_id, false);
                        // past parent comment block
                        $commentHTML = str_replace("%COMMENT_" . $message_cc . "_" . $message_id . "_" . $CommentData['id'] . "%", nl2br($CommentData['Comment']), $commentHTML);
                        // bbcode processing
                        if ($nc_comments->isBBcodes()) {
                            $commentHTML = nc_bbcode($commentHTML);
                        }
                        $resArr[] = "{'id':'" . $CommentData['id'] . "', 'parent_id':'" . $CommentData['Parent_Comment_ID'] . "', 'commentHTML':escape(\"" . $nc_comments->commentValidateShow($commentHTML, $template_id) . "\"), 'updated':'" . $CommentData['LastUpdated'] . "', 'edit_rule':'" . $nc_comments->getEditRule() . "', 'delete_rule':'" . $nc_comments->getDeleteRule() . "', 'error':''}";
                    } else {
                        // update text only
                        // bbcode processing
                        if ($nc_comments->isBBcodes()) {
                            $commentText = nc_bbcode($value['Comment']);
                        }
                        $resArr[] = "{'id':'" . $value['id'] . "', 'parent_id':'" . $value['Parent_Comment_ID'] . "', 'commentHTML':escape(\"" . nl2br(htmlspecialchars_decode($commentText)) . "\"), 'update':'1', 'updated':'" . $value['LastUpdated'] . "', 'error':''}";
                    }
                } else {
                    $commentHTML = $nc_comments->getComment($message_id, $value, $template_id, false);
                    // past comment text
                    $commentHTML = str_replace("%COMMENT_" . $message_cc . "_" . $message_id . "_" . $value['id'] . "%", nl2br($value['Comment']), $commentHTML);
                    // bbcode processing
                    if ($nc_comments->isBBcodes()) {
                        $commentHTML = nc_bbcode($commentHTML);
                    }
                    $resArr[] = "{'id':'" . $value['id'] . "', 'parent_id':'" . $value['Parent_Comment_ID'] . "', 'commentHTML':escape(\"" . $nc_comments->commentValidateShow($commentHTML, $template_id) . "\"), 'updated':'" . $value['LastUpdated'] . "', 'edit_rule':'" . $nc_comments->getEditRule() . "', 'delete_rule':'" . $nc_comments->getDeleteRule() . "', 'error':''}";
                }
            }
        }
        // edit
        if ($comment_edit == 1) {
            // check update possibility
            try {
                $nc_comments->updateComment($comment_id, $comment);
                $commentData = $nc_comments->getCommentFromArray($comment_id);
                $LastUpdated = $commentData['Updated'] > $commentData['Data'] ? $commentData['Updated'] : $commentData['Data'];
                //echo nl2br($comment);
                // bbcode processing
                if ($nc_comments->isBBcodes()) {
                    $commentData['Comment'] = nc_bbcode($commentData['Comment']);
                }
                $resArr[] = "{'id':'" . $comment_id . "', 'parent_id':'" . $parent_mess_id . "', 'commentHTML':escape(\"" . $nc_comments->commentValidateShow(nl2br($commentData['Comment']), $template_id) . "\"), 'update':'1', 'updated':'" . $LastUpdated . "', 'error':'0'}";
            } catch (Exception $e) {
                die("{'error':'" . $e->getMessage() . "'}");
            }
        }
        // edit get info
        if ($comment_edit == 2) {
            // check update possibility
            try {
                $comment = $nc_comments->getCommentFromArray($comment_id);
                $resArr[] = "{'id':'" . $comment_id . "', 'parent_id':'" . $parent_mess_id . "', 'commentHTML':escape(\"" . $nc_comments->commentValidateShow(htmlspecialchars_decode($comment['Comment']), $template_id) . "\"), 'update':'2', 'updated':'" . $comment['Updated'] . "', 'error':'0'}";
            } catch (Exception $e) {
                die("{'error':'" . $e->getMessage() . "'}");
            }
        }
        // delete
        if ($comment_edit == -1) {
            // check delete possibility
            try {
                // get comment data from array
                $commentData = $nc_comments->getCommentFromArray($comment_id);
                // drop from base
                $nc_comments->deleteComment($comment_id);
                // drop from DOM
                $resArr[] = "{'id':'" . $comment_id . "', 'update':'-1', 'error':'0'}";

                // refresh parent block
                // not need it for moderators, they may see all type of links
                if (($nc_comments->getEditRule() == "unreplied" || $nc_comments->getDeleteRule() == "unreplied") && !$nc_comments->isModerator()) {
                    // if children exist not need to refresh
                    if (!$nc_comments->getChildren($commentData['Parent_Comment_ID'])) {
                        // get parent comment data from array
                        $parentCommentData = $nc_comments->getCommentFromArray($commentData['Parent_Comment_ID']);
                        // drop from DOM
                        $resArr[] = "{'id':'" . $commentData['Parent_Comment_ID'] . "', 'update':'-1', 'error':'0'}";
                        // get parent refreshaed block
                        $commentHTML = $nc_comments->getComment($message_id, $parentCommentData, $template_id, false);
                        // past parent comment block
                        $commentHTML = str_replace("%COMMENT_" . $message_cc . "_" . $message_id . "_" . $parentCommentData['id'] . "%", nl2br($parentCommentData['Comment']), $commentHTML);
                        // bbcode processing
                        if ($nc_comments->isBBcodes()) {
                            $commentHTML = nc_bbcode($commentHTML);
                        }
                        $resArr[] = "{'id':'" . $parentCommentData['id'] . "', 'parent_id':'" . $parentCommentData['Parent_Comment_ID'] . "', 'commentHTML':escape(\"" . $nc_comments->commentValidateShow($commentHTML, $template_id) . "\"), 'updated':'" . $parentCommentData['LastUpdated'] . "', 'edit_rule':'" . $nc_comments->getEditRule() . "', 'delete_rule':'" . $nc_comments->getDeleteRule() . "', 'error':''}";
                    }
                }
            } catch (Exception $e) {
                die("{'error':'" . $e->getMessage() . "'}");
            }
        }

        $all_comments = $nc_comments->getCommentFromArray();
        if (!empty($all_comments)) {
            foreach ($all_comments as $comment_data) {
                $all_comments_id[] = $comment_data['id'];
            }
            // all comments IDs in this wall
            $resArr[] = "{'all_comments_id':[" . join(", ", $all_comments_id) . "]}";
        }

        // json result
        if (!empty($resArr)) {
            $res = "[" . join(",", $resArr) . "]";
        }

    } else {
        $templateData['Warn_Text'] = str_replace("%WARNTEXT", NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_PARENT, $templateData['Warn_Text']);
        die("{'error':'enter', 'unset':escape(\"" . $nc_comments->commentValidateShow($templateData['Warn_Text'], $template_id, 0, 1) . "\")}");
    }

}
if (isset($_POST['redirect_url'])) {
    nc_check_redirect($_POST['redirect_url']);
    exit();
}

// return json result from ajax
echo $res;
