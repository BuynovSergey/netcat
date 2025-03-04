<?php

if (!class_exists('nc_core')) {
    die;
}

?>

<table class='admin_table' width='100%' id='nc_site_show_demo_content'>
    <tr>
        <th><?=CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION ?></th>
        <th><?=CONTROL_CONTENT_SUBDIVISION_CLASS ?></th>
        <th><?=CONTROL_CONTENT_SUBCLASS_TOTALOBJECTS ?></th>
    </tr>
    <?
        $result = null;
        $current_subdivision_id = -1;
        foreach ($data as $row) {  
            $result .= "<tr>\n";  
            if ($current_subdivision_id != $row['Subdivision_ID']) {
                $current_subdivision_id = $row['Subdivision_ID'];
                $result .= "<td><a href='" . nc_folder_url($row['Subdivision_ID']) . "' style='text-decoration:underline' target='_blank'>" . $row['Subdivision_Name'] . "</a></td>\n";    
            } else {
                $result .= "<td></td>\n";    
            }            
            $result .= "<td><a href='" . nc_infoblock_url($row['Sub_Class_ID']) . "' style='text-decoration:underline' target='_blank'>" . $row['Sub_Class_Name'] . "</a></td>\n";    
            $result .= "<td>". $row['ItemCount'] . "</td>\n";      
            $result .= "</tr>\n";       
        }
    ?>
    <?=$result ?>
</table>
