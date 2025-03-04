<?

##############################################
# Создание таблицы компонента
##############################################

function CreateMessageTable($classid, $db) {
    $TableName = "Message".$classid;

    $Creat = "CREATE TABLE ".$TableName." (";
    $Creat .= " Message_ID int AUTO_INCREMENT PRIMARY KEY,";
    $Creat .= " User_ID int NOT NULL,";
    $Creat .= " Subdivision_ID int NOT NULL,";
    $Creat .= " Sub_Class_ID int NOT NULL,";
    $Creat .= " Priority int NOT NULL DEFAULT 0,";
    $Creat .= " Keyword char(255) NOT NULL,";
    $Creat .= " `ncTitle` varchar(255) default NULL,";
    $Creat .= " `ncKeywords` text default NULL,";
    $Creat .= " `ncDescription` text default NULL,";
    $Creat .= " `ncSMO_Title` varchar(255) default NULL,";
    $Creat .= " `ncSMO_Description` text,";
    $Creat .= " `ncSMO_Image` text,";
    $Creat .= " `ncFullPageMode` enum('inherit', 'template', 'custom', 'none') NOT NULL DEFAULT 'inherit',";
    $Creat .= " `ncFullPageCreated` tinyint NOT NULL DEFAULT 0,";
    $Creat .= " `ncDemoContent` tinyint NOT NULL DEFAULT 0,";
    $Creat .= " Checked tinyint NOT NULL DEFAULT 1,";
    $Creat .= " IP char(15) NULL,";
    $Creat .= " UserAgent char(255) NULL,";
    $Creat .= " Parent_Message_ID int NOT NULL DEFAULT 0,";
    $Creat .= " Created datetime NOT NULL,";
    $Creat .= " LastUpdated timestamp NOT NULL,";
    $Creat .= " LastUser_ID int NOT NULL,";
    $Creat .= " LastIP char(15) NULL,";
    $Creat .= " LastUserAgent char(255) NULL,";
    $Creat .= " index (User_ID),";
    $Creat .= " index (LastUser_ID),";
//  	$Creat .= " index (Sub_Class_ID),";
    $Creat .= " index (Subdivision_ID),";
    $Creat .= " index (Parent_Message_ID), ";
    $Creat .= " index (`Keyword`), ";
    $Creat .= " index (Priority,LastUpdated), ";
    $Creat .= " index (ncDemoContent,Subdivision_ID), ";
    $Creat .= " index (Checked), ";
    $Creat .= " index (Created), ";
    $Creat .= " unique (Sub_Class_ID,Message_ID,Keyword)";
    $Creat .= ") ENGINE=MyISAM";

    global $LinkID;
    if ((float) mysqli_get_server_info($LinkID) >= 4.1) {
        global $MYSQL_CHARSET;
        $Creat .= " DEFAULT CHARSET=$MYSQL_CHARSET";
    }

    $db->query($Creat);
}

##############################################
# Удаление таблицы компонента
##############################################

function DropMessageTable($classid, $db) {
    $TableName = "Message".$classid;
    $drop = "drop table ".$TableName;
    $db->query($drop);
}
