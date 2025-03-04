<?
header('Content-Type: application/json');

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR;
include_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';

$result = array(
    'result' => false
);

$arData = array();
$postData = file_get_contents('php://input');

if(!empty($postData)) {
    $postData = json_decode($postData, true);
}

$type = htmlspecialchars($postData['type']);

if(!empty($type)){
    $nc_core = nc_Core::get_object();

    /*{
    "user": "senseyr@kproject.su",
    "password": "1",
    "type": "create_user",
    "name": "имя"
}*/
    if($type == 'create_user'){
        if(!empty($postData['user']) && !empty($postData['password']) && !empty($postData['name'])) {
            if(empty($nc_core->user->check_login(htmlspecialchars($postData['user'])))) {
                //$userID = $nc_core->user->add(array('Login' => htmlspecialchars($postData['user'])), '2', htmlspecialchars($postData['user']), array('Checked' => '1'));
                $query = "insert into User (`Password`, `PermissionGroup_ID`, `Checked`, `Language`, `Created`, `LastUpdated`, `Confirmed`, `UserType`, `Email`, `Name`)
            values ('" . md5(htmlspecialchars($postData['password'])) . "', 2, 1, 'Russian', NOW(), NOW(), 1, 'normal', '" . htmlspecialchars($postData['user']) . "', '" . htmlspecialchars($postData['name']) . "')";

                $nc_core->db->query($query);

                $userID = $nc_core->db->insert_id;

                $nc_core->db->query("insert into User_Group (User_ID,PermissionGroup_ID) values (" . $nc_core->db->insert_id . ",2)");
                if (!empty($userID)) {
                    $result['result'] = true;
                    $result['user_id'] = $userID;
                }
            } else {
                $result['message'] = "Некорректный логин";
            }
        }
    }

    /*{
    "user": "admin",
    "password": "123",
    "type": "change_user",
    "name": "Имя"
}*/
    if($type == 'change_user'){
        if(!empty($postData['user']) && !empty($postData['password'])) {
            $result = $nc_core->db->get_results("SELECT User_ID FROM User WHERE Email = '" . htmlspecialchars($postData['user']) . "' AND Password = '" . md5(htmlspecialchars($postData['password'])) . "'");
            if($result) {
                if(!empty($result[0]->User_ID)) {
                    $nc_core->db->query("UPDATE User set `Name`='".htmlspecialchars($postData['name'])."' where `User_ID`=".$result[0]->User_ID);
                    $result['result'] = true;
                    $result['user_id'] = $result[0]->User_ID;
                }
            }
        }
    }

    /*{
    "user": "admin",
    "password": "123",
    "type": "authorized_user"
}*/
    if($type == 'authorized_user') {
        if(!empty($postData['user']) && !empty($postData['password'])) {
            $rsAuth = $nc_core->user->authorize_by_pass(htmlspecialchars($postData['user']), htmlspecialchars($postData['password']));
            if(!empty($rsAuth)){
                $result['result'] = true;
                $result['user_id'] = $rsAuth;
            }
        }
    }

    /*{
    "user": "senseyr@kproject.su",
    "password": "1",
    "type": "get_user",
    "id": "3"
}*/
    if($type == 'get_user') {
        if(!empty($postData['id']) && !empty($postData['user']) && !empty($postData['password'])) {
            $rsAuth = $nc_core->user->authorize_by_pass(htmlspecialchars($postData['user']), htmlspecialchars($postData['password']));
            if(!empty($rsAuth)){
                $arrResult = $nc_core->user->get_by_id( htmlspecialchars($postData['id']) );
                unset($arrResult['Password']);

                $result['result'] = true;
                $result['data'] = $arrResult;
            }
        }
    }

    /*{
    "user": "senseyr@kproject.su",
    "password": "1",
    "type": "delete_user",
    "id": "3"
}*/
    if($type == 'delete_user') {
        if(!empty($postData['id']) && !empty($postData['user']) && !empty($postData['password'])) {
            $rsAuth = $nc_core->user->authorize_by_pass(htmlspecialchars($postData['user']), htmlspecialchars($postData['password']));
            if(!empty($rsAuth)){
                $nc_core->user->delete_by_id(htmlspecialchars($postData['id']));
                $result['result'] = true;
            }
        }
    }
}

echo json_encode($result);
?>