<?php

/**
 * Обрабатывает D&D:
 *  - subdivision to subdivision
 *  - subdivision to site
 *  - site to site
 */

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = join(strstr(__FILE__, '/') ? '/' : '\\', array_slice(preg_split('/[\/\\\]+/', __FILE__), 0, -4)) . ( strstr(__FILE__, '/') ? '/' : '\\' );
include_once ($NETCAT_FOLDER . 'vars.inc.php');
require_once ($ADMIN_FOLDER . 'function.inc.php');
require_once ($ADMIN_FOLDER . 'subdivision/function.inc.php');

/**
 * @var Permission $perm
 */

$dragged_id = (int) $dragged_id;
$target_id = (int) $target_id;
if (!$dragged_id || !$target_id) {
    die('0 /* Wrong parameters */');
}

if ($dragged_type == $target_type && $dragged_id == $target_id) {
    die('0 /* dragged==target */');
}

// INPUT: $dragged_type, $dragged_id, $target_type, $target_id, $position [inside|below]
// dropped subdivision on another subdivision or to the site
if ($dragged_type == 'sub' && ($target_type == 'sub' || $target_type == 'site')) {

    try {
        // dragged sub info
        $dragged = $nc_core->subdivision->get_by_id($dragged_id);

        // target (sub or site) info
        if ($target_type == 'sub') {
            $target = $nc_core->subdivision->get_by_id($target_id);
        } elseif ($target_type == 'site') {
            $target = array(
              'Catalogue_ID' => $target_id,
              'Hidden_URL' => '/',
              'Parent_Sub_ID' => 0,
              'Subdivision_ID' => 0
            );
        }
    } catch (Exception $e) {
        die('0 /* Wrong IDs */');
    }

    // check rights for dragged object
    if (!$perm->isSubdivisionAdmin($dragged['Subdivision_ID'])) {
        die('0 /* No sufficient rights for dragged ' . $dragged_type . ' ' . $dragged_id . ' */');
    }

    // check rights for target object
    if ($target_type === 'sub' && !$perm->isSubdivisionAdmin($target['Subdivision_ID'])) {
        die('0 /* No sufficient rights for target ' . $target_type . ' ' . $target_id . ' */');
    }

    if ($target_type === 'site' && !$perm->isCatalogueAdmin($target_id)) {
        die('0 /* No sufficient rights for ' . $target_type . ' ' . $target_id . ' */');
    }

    if ($target_type === 'sub' && $nc_core->subdivision->is_disallow_sub_class_subdivision_id($dragged['Subdivision_ID'])) {
        die('-1 /* Disallow move subdivision */');
    }

    if ($position == 'inside') {
        $parent_sub = $target['Subdivision_ID'];
        $priority = $db->get_var("SELECT MIN(`Priority`) FROM `Subdivision`
        WHERE `Parent_Sub_ID` = '" . $parent_sub . "'
        AND `Subdivision_ID` != '" . $dragged['Subdivision_ID'] . "'");
    } elseif ($position == 'below') {
        $priority = $target['Priority'];
        if ($target['Parent_Sub_ID'] != $dragged['Parent_Sub_ID']) {
            $priority++;
        }
        $parent_sub = $target['Parent_Sub_ID'];
    } else {
        die('0 /* Wrong parameter $position = ' . $position . ' */');
    }

    $nc_core->version->init_changeset(NETCAT_VERSION_ACTION_MOVED . ' ' . sprintf(NETCAT_VERSION_OF_SUBDIVISION, $dragged['Subdivision_Name']));

    // if drag sub in current parent sub
    if ($dragged['Parent_Sub_ID'] == $parent_sub) {
        // changing priority, but not the parent
        if ($target['Priority'] > $dragged['Priority'] && $position != 'inside') {
            // moving item downwards

            $subdivisions_arr = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision`
        WHERE `Parent_Sub_ID` = '" . $parent_sub . "'
        AND `Priority` BETWEEN '" . $dragged['Priority'] . "' AND '" . $priority . "'
        AND `Subdivision_ID` != '" . $dragged['Subdivision_ID'] . "' AND Catalogue_ID='" . $dragged['Catalogue_ID'] . "' ");

            if (!empty($subdivisions_arr)) {
                // execute core action
                $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);

                $db->query("UPDATE `Subdivision`
          SET `Priority` = `Priority` - 1
          WHERE `Subdivision_ID` IN (" . join(', ', $subdivisions_arr) . ")");
                // execute core action
                $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);
            }
        } else {
            // moving item upwards
            if ($position == 'inside' || $position == 'below') {
                if ($priority < 1) { //fixing negative priorities
                    $db->query("UPDATE `Subdivision`
                       SET `Priority` = `Priority` + " . abs($priority) .
                      " WHERE `Parent_Sub_ID` = '" . $parent_sub . "' AND Catalogue_ID='" . $dragged['Catalogue_ID'] . "' ");
                    $priority = 0;
                    $dragged['Priority'] = $dragged['Priority'] + abs($priority);
                }
            }
            if ($position == 'below') {
                $priority++;
            }
            $subdivisions_arr = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision`
        WHERE `Parent_Sub_ID` = '" . $parent_sub . "'
        AND `Priority` BETWEEN '" . $priority . "' AND '" . $dragged['Priority'] . "'
        AND `Subdivision_ID` != '" . $dragged['Subdivision_ID'] . "' AND Catalogue_ID='" . $dragged['Catalogue_ID'] . "'");

            if (!empty($subdivisions_arr)) {
                // execute core action
                $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);

                $db->query("UPDATE `Subdivision`
          SET `Priority` = `Priority` + 1
          WHERE `Subdivision_ID` IN (" . join(", ", $subdivisions_arr) . ")");
                // execute core action
                $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);
            }
        }
    } else {
        // parent has changed
        // make room for inserted/moved node at the new parent

        $subdivisions_arr = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision`
      WHERE `Parent_Sub_ID` = '" . $parent_sub . "' AND `Priority` >= '" . $priority . "' AND Catalogue_ID='" . $dragged['Catalogue_ID'] . "'");

        if (!empty($subdivisions_arr)) {
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);

            $db->query("UPDATE `Subdivision` SET `Priority` = `Priority` + 1
        WHERE `Subdivision_ID` IN (" . join(", ", $subdivisions_arr) . ")");
            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);
        }

        $subdivisions_arr = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision`
      WHERE `Parent_Sub_ID` = '" . $dragged['Parent_Sub_ID'] . "'
      AND `Priority` > '" . $dragged['Priority'] . "'
      AND `Subdivision_ID` != '" . $dragged['Subdivision_ID'] . "' AND Catalogue_ID='" . $dragged['Catalogue_ID'] . "'");

        // collapse gap at the old parent subdivision
        if (!empty($subdivisions_arr)) {
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);

            $db->query("UPDATE `Subdivision`
        SET `Priority` = `Priority` - 1
        WHERE `Subdivision_ID` IN (" . join(", ", $subdivisions_arr) . ")");
            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);
        }

        // update hidden url
        // REPLACE() was not used because there's no way to match from the first symbol
        $old_length = strlen(nc_preg_replace('#[^/]+/$#', '', $dragged['Hidden_URL'])) + 1;
        $new_parent_url = $target['Hidden_URL'];
        if ($position == 'below') { // target's parent
            $new_parent_url = nc_preg_replace('#[^/]+/$#', '', $new_parent_url);
        }

        $subdivisions_arr = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision`
            WHERE `Hidden_URL` LIKE '" . $dragged['Hidden_URL'] . "%' AND `Catalogue_ID` = '" . $dragged['Catalogue_ID'] . "' ");

        $subdivision_arr_engname = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision`
                WHERE `EnglishName` = '" . $dragged['EnglishName'] . "' AND `Parent_Sub_ID` = '" . $parent_sub . "' AND `Catalogue_ID` = '" . $dragged['Catalogue_ID'] . "' ");

        if (!empty($subdivision_arr_engname)) {
            die('0 /* EnglishName of dragged sub (' . $dragged['EnglishName'] . ') is already among the subs of appointed sub (' . $target['Subdivision_ID'] . ') */');
        }


        //die("0 /* EnglishName of dragged sub (".$dragged['EnglishName'].") is already among the subs of appointed sub (".$target['Subdivision_ID'].") */");
        if (!empty($subdivisions_arr)) {
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);

            $db->query("UPDATE `Subdivision`
        SET `Hidden_URL` = CONCAT( '" . $new_parent_url . "', SUBSTRING(`Hidden_URL` FROM " . $old_length . ") )
        WHERE `Subdivision_ID` IN (" . join(", ", $subdivisions_arr) . ")");
            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $subdivisions_arr);
        }
    }
    // нельзя перемещать в самого себя
    if ($parent_sub == $dragged['Subdivision_ID']) {
        die('0 /*Movement in itself*/');
    }

    // get children data
    $chil_sub_id = GetChildrenSub($dragged['Subdivision_ID']);
    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $dragged['Subdivision_ID']);

    // move subdivision
    $db->query("UPDATE `Subdivision`
    SET `Parent_Sub_ID` = '" . $parent_sub . "', `Catalogue_ID` = '" . $target['Catalogue_ID'] . "', `Priority` = '" . $priority . "'
    WHERE `Subdivision_ID` = '" . $dragged['Subdivision_ID'] . "'");
    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $dragged['Catalogue_ID'], $dragged['Subdivision_ID']);

    if (count($chil_sub_id)) {
        foreach ($chil_sub_id as $v) {
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $target['Catalogue_ID'], $v);

            $db->query("UPDATE `Subdivision` SET `Catalogue_ID` = '" . $target['Catalogue_ID'] . "' WHERE `Subdivision_ID` = '" . $v . "'");
            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $target['Catalogue_ID'], $v);
            $subclasses = $db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $v . "'");
            if (!empty($subclasses)) {
                // execute core action
                $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $target['Catalogue_ID'], $v, $subclasses);

                $db->query("UPDATE `Sub_Class` SET `Catalogue_ID` = '" . $target['Catalogue_ID'] . "' WHERE `Subdivision_ID` = '" . $v . "'");
                // execute core action
                $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $target['Catalogue_ID'], $v, $subclasses);
            }
        }
    }

    $subclasses = $db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $dragged['Subdivision_ID'] . "'");

    if (!empty($subclasses)) {
        // execute core action
        $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $target['Catalogue_ID'], $dragged['Subdivision_ID'], $subclasses);

        $db->query("UPDATE `Sub_Class`
    SET `Catalogue_ID` = '" . $target['Catalogue_ID'] . "'
    WHERE `Subdivision_ID` = '" . $dragged['Subdivision_ID'] . "'");
        // execute core action
        $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $target['Catalogue_ID'], $dragged['Subdivision_ID'], $subclasses);
    }


    if ($target_type == 'site' && ($target['Catalogue_ID'] != $dragged['Catalogue_ID'])) {

        $parent_subs_array = GetChildrenSub($dragged['Subdivision_ID']);

        if (!empty($parent_subs_array)) {
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $target['Catalogue_ID'], $parent_subs_array);

            $db->query("UPDATE `Subdivision` SET `Catalogue_ID` = '" . $target['Catalogue_ID'] . "' WHERE `Subdivision_ID` IN (" . join(', ', $parent_subs_array) . ")");
            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $target['Catalogue_ID'], $parent_subs_array);

            foreach ($parent_subs_array as $value) {
                // get subclass
                $subclasses = $db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $value . "'");

                if (!empty($subclasses)) {
                    // execute core action
                    $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $target['Catalogue_ID'], $value, $subclasses);

                    $db->query("UPDATE `Sub_Class` SET `Catalogue_ID` = '" . $target['Catalogue_ID'] . "' WHERE `Sub_Class_ID` IN (" . join(", ", $subclasses) . ")");
                    // execute core action
                    $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $target['Catalogue_ID'], $value, $subclasses);
                }
            }
        }
    }

    nc_tpl_mixin_cache::generate_subdivision_files($dragged['Subdivision_ID'], true);

    $nc_core->version->commit_changeset();

    die('1 /* OK */');
}

if ($dragged_type == 'site' && $target_type == 'site') {
    // dragged site info
    $dragged = $nc_core->catalogue->get_by_id($dragged_id, 'Priority');
    // target site info
    $target = $nc_core->catalogue->get_by_id($target_id, 'Priority');

    if ($perm->isCatalogueAdmin($dragged_id) && $perm->isCatalogueAdmin($target_id)) {

        $nc_core->event->execute(nc_Event::BEFORE_SITE_UPDATED, $catalogues);

        if ($dragged_id <= $target_id) {
            $catalogues = $db->get_col("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Priority` > '" . $dragged . "' AND `Priority` <= '" . $target . "' OR `Catalogue_ID` = '" . $target_id . "'");
            if (!empty($catalogues)) {
                $db->query("UPDATE `Catalogue` SET `Priority` = `Priority` - 1
          WHERE `Catalogue_ID` IN (" . join(", ", $catalogues) . ")");
            }
            $db->query("UPDATE `Catalogue` SET `Priority` = '" . $target . "'
        WHERE `Catalogue_ID` = '" . $dragged_id . "'");
        } else {
            $catalogues = $db->get_col("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Priority` > '" . $target . "' AND `Priority` < '" . $dragged . "'");
            if (!empty($catalogues)) {
                $db->query("UPDATE `Catalogue` SET `Priority` = `Priority` + 1
          WHERE `Catalogue_ID` IN (" . join(", ", $catalogues) . ")");
            }
            $db->query("UPDATE `Catalogue` SET `Priority` = '" . $target . "' + 1
        WHERE `Catalogue_ID` = '" . $dragged_id . "'");
        }
        // execute core action
        $catalogues[] = $dragged_id;
        $nc_core->event->execute(nc_Event::AFTER_SITE_UPDATED, $catalogues);
    }

    die('1 /* OK */');
}

// dropped subclass on subdivision
if ($dragged_type == 'subclass' && $target_type == 'sub') {
    $ret = nc_move_subclass($dragged_id, $target_id);
    die((int) $ret . '/**/');
}

die('0 /* Wrong request */');
