<?php

function CalendarShowSettings() {
    global $db, $UI_CONFIG, $nc_core;

    $CalendarTheme = '';

    if (isset($_REQUEST['CalendarTheme'])) {
        if ($_REQUEST['CalendarTheme'] == "new") {
            $CalendarTheme = $_REQUEST['CalendarTheme'];
        } elseif (preg_match("/^[[:digit:]]+$/is", $_REQUEST['CalendarTheme'])) {
            $CalendarTheme = (int) $_REQUEST['CalendarTheme'];
        }
    }

    $themes = $db->get_results("SELECT * FROM Calendar_Settings ORDER BY ID", ARRAY_A);
    unset($settings);

    echo "
	<fieldset>
	<legend>
		".NETCAT_MODULE_CALENDAR_LEGEND."
	</legend>

	<form method='post' action='admin.php' style='padding:0; margin:0;'>";

    // вывод темы "по умолчанию"
    if ($themes) {
        $nodef = true;
        foreach ($themes AS $key => $value) {
            if ($themes[$key]['DefaultTheme']) $nodef = false;
            if (empty($CalendarTheme)) {
                if ($themes[$key]['DefaultTheme'] == 1)
                        $settings = $themes[$key];
            }
            else {
                if ($themes[$key]['ID'] == $CalendarTheme)
                        $settings = $themes[$key];
            }
        }
    }

    // если нет темы по-умолчанию
    if (!$settings && $themes && isset($CalendarTheme) && $CalendarTheme != 'new') {
        $settings = $themes[0];
        $nodef = true;
    } else if (!$nodef) $nodef = false;

    // названия дней недели 
    $DaysName = explode(",", $settings['DaysName']);

    echo "
	<div style='margin:10px 0; _padding:0;'>
		<input type='hidden' name='CalendarTheme' value='".(isset($CalendarTheme) ? (int) $CalendarTheme : $settings['ID'])."'>
		<select name='CalendarStyle' onchange='this.form.CalendarTheme.value=value; this.form.submit();' style='width:340px'>";
    foreach ($themes AS $key => $value) {
        echo "<option value='".$value['ID']."' ".($CalendarTheme && $value['ID'] == $CalendarTheme ? "selected" : (!$CalendarTheme && $value['DefaultTheme'] == 1 ? "selected" : ""))." ".($value['DefaultTheme'] == 1 ? "style='font-weight:bold'" : "").">".$value['ID'].": ".$value['ThemeName']."</option>";
    }
    echo "<option value='new' ".($CalendarTheme == 'new' ? "selected" : "").">".NETCAT_MODULE_CALENDAR_THEME_NEW."</option>";
    echo "
		</select>
		".($CalendarTheme != 'new' ? "<input type='button' title='".NETCAT_MODULE_CALENDAR_VIEW."' value='".NETCAT_MODULE_CALENDAR_VIEW."' onclick=\"caendar=window.open('" . nc_module_path('calendar') . "showpreview.php?id=$settings[ID]','','top=50, left=100,directories=no,height=320,location=no,menubar=no,resizable=yes,scrollbars=no,status=no,toolbar=no,width=320');\">" : "")."
	</div>
	<div style='margin:10px 0 0; _padding:0;'>
		<div style='padding-bottom:10px; font-weight:bold; color:gray'>
		".NETCAT_MODULE_CALENDAR_SCHEME.":
		</div>
	<table cellpadding='0' cellspacing='1' border='0' style='width:100%; height:200px; background:#FFFFFF;'>
	<tr valign='top'>
	<td style='width:220px;'>
		<table cellpadding='0' cellspacing='1' border='0' style='width:220px; height:200px; background:#FFFFFF;'>
			<tr>
				<td colspan='9' style='background:#D89292; height:5px' title='".NETCAT_MODULE_CALENDAR_LEG_02."'></td>
			</tr>
			<tr>
				<td colspan='9' align='center' style='background:#FFD3D3; height:25px' title='".NETCAT_MODULE_CALENDAR_LEG_01."'>".NETCAT_MODULE_CALENDAR_LEG_01."</td>
			</tr>
			<tr>
				<td colspan='9' style='background:#FDEBB5; height:5px' title='".NETCAT_MODULE_CALENDAR_LEG_03."'></td>
			</tr>
			<tr>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_04."'></td>
				<td align='center' style='background:#D3FFD3; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_05."'>".$DaysName[0]."</td>
				<td align='center' style='background:#D3FFD3; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_05."'>".$DaysName[1]."</td>
				<td align='center' style='background:#D3FFD3; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_05."'>".$DaysName[2]."</td>
				<td align='center' style='background:#D3FFD3; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_05."'>".$DaysName[3]."</td>
				<td align='center' style='background:#D3FFD3; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_05."'>".$DaysName[4]."</td>
				<td align='center' style='background:#A5DEA5; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_06."'>".$DaysName[5]."</td>
				<td align='center' style='background:#A5DEA5; width:30px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_07."'>".$DaysName[6]."</td>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_15."'></td>
			</tr>
			<tr>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_04."'></td>
				<td align='center' style='color:#FFFFFF; background:#E6E6FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_12."'>&bull;</td>
				<td align='center' style='color:#FFFFFF; background:#E6E6FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_12."'>&bull;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_10."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_11."'>&nbsp;</td>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_15."'></td>
			</tr>
			<tr>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_04."'></td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_10."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_11."'>&nbsp;</td>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_15."'></td>
			</tr>
			<tr>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_04."'></td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_10."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_11."'>&nbsp;</td>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_15."'></td>
			</tr>
			<tr>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_04."'></td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_10."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_11."'>&nbsp;</td>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_15."'></td>
			</tr>
			<tr>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_04."'></td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#D3D3FF; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_08."'>&nbsp;</td>
				<td style='background:#A8A8F7; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_10."'>&nbsp;</td>
				<td align='center' style='color:#FFFFFF; background:#BFBFF9; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_14."'>&bull;</td>
				<td align='center' style='background:#7171D8; width:5px; height:20px' title='".NETCAT_MODULE_CALENDAR_LEG_15."'></td>
			</tr>
			<tr>
				<td colspan='9' style='background:#FDEBB5; height:5px' title='".NETCAT_MODULE_CALENDAR_LEG_16."'></td>
			</tr>
			<tr>
				<td colspan='9' style='background:#D89292; height:5px' title='".NETCAT_MODULE_CALENDAR_LEG_17."'></td>
			</tr>
		</table>
	</td>
	<td style='padding:0 0 0 20px'>
		<table cellspacing='1' cellpadding='0' border='0' style='background:#FFFFFF; border:1px solid #999999; width:100%; height:200px'>
		<tr style='background:#EEEEEE;'>
			<td width='30%'>&nbsp;&nbsp;%SELECT_YEAR</td>
			<td width='70%'>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_YS."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%SELECT_MONTH</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_MS."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%IMG_STATUS</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_PL."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%IMG_PREV_MONTH</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_PREV_M."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%IMG_NEXT_MONTH</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_NEXT_M."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%YEAR_LINK</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_CURR_Y."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%MONTH_LINK</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_CURR_M."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%NAME_DAY</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_DAYS_NAME."</td>
		</tr>
		<tr style='background:#EEEEEE;'>
			<td>&nbsp;&nbsp;%DAY</td>
			<td>&nbsp;&nbsp;".NETCAT_MODULE_CALENDAR_HELP_DATE."</td>
		</tr>
		</table>
	</td>
	</tr>
	</table>
	</div>
	</form>
	
	<form method='post' action='admin.php' id='SetCalendarSettings' style='padding:0; margin:0;'>
	<input type='hidden' name='ID' value='".($CalendarTheme ?: $settings['ID'])."'>
	<div style='margin:10px 0; _padding:0; color:grey; vertical-align:middle;'>
		".($settings['DefaultTheme'] ? "<b>".NETCAT_MODULE_CALENDAR_THEME_DEF."</b>" : ($nodef ? "<b style='color:#FF3300'>" : "").NETCAT_MODULE_CALENDAR_SET_DEFAULT_THEME.($nodef ? "</b>" : "")." &nbsp; <input type='checkbox' name='SetDefault' style='padding:0; margin:0;' ".($nodef ? "checked" : "").">")."
	</div>
	<div style='padding-bottom:5px'>
		<font color='gray'>*".NETCAT_MODULE_CALENDAR_THEME_NAME.":</font><br>
		<input type='text' name='ThemeName' value='".htmlspecialchars($settings['ThemeName'])."' style='width:428px'>
	</div>
        
        <div style='padding-bottom:5px'>
            <font color='gray'>".NETCAT_MODULE_CALENDAR_PARALLAX_MESSAGE.":</font><br />            
            <input type='text' size='5' value='".($settings['parallax_year_backward'] ? $settings['parallax_year_backward'] : 10)."' name='parallax_year_backward'>&nbsp;
            <input type='text' size='5' value='".($settings['parallax_year_forward'] ? $settings['parallax_year_forward'] : 10)."' name='parallax_year_forward'>
        </div>
        
	<div style='padding-bottom:5px'>
		<font color='gray'>".NETCAT_MODULE_CALENDAR_DAYS_NAME.":<br>
			<input type='text' size='5' value='".$DaysName[0]."' name='DayName1'>&nbsp;
			<input type='text' size='5' value='".$DaysName[1]."' name='DayName2'>&nbsp;
			<input type='text' size='5' value='".$DaysName[2]."' name='DayName3'>&nbsp;
			<input type='text' size='5' value='".$DaysName[3]."' name='DayName4'>&nbsp;
			<input type='text' size='5' value='".$DaysName[4]."' name='DayName5'>&nbsp;
			<input type='text' size='5' value='".$DaysName[5]."' name='DayName6'>&nbsp;
			<input type='text' size='5' value='".$DaysName[6]."' name='DayName7'>
	</div>
	
	<div style='padding-bottom:5px'>
		<span style='padding:0 7px; line-height:20px; background:#D89292;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_02.":</font><br>
		<textarea name='CalendarPrefix' style='height:3em'>".htmlspecialchars($settings['CalendarPrefix'])."</textarea>
	</div>
	<div style='padding-bottom:5px'>
		<span style='padding:0 7px; line-height:20px; background:#FFD3D3;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_01.":<br>
		<textarea name='CalendarHeader' style='height:10em'>".htmlspecialchars($settings['CalendarHeader'])."</textarea>
	</div>
		<div style='padding-bottom:5px'>
			<span style='padding:0 7px; line-height:20px; background:#FDEBB5;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_03.":<br>
			<textarea name='dayTemplatePrefix' style='height:3em'>".htmlspecialchars($settings['dayTemplatePrefix'])."</textarea>
		</div>
			<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#7171D8;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_04.":<br>
				<textarea name='dayTemplateBegin' style='height:3em'>".htmlspecialchars($settings['dayTemplateBegin'])."</textarea>
			</div> 
				
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#D3FFD3;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_05.":<br>
				<textarea name='dayTemplateHeader' style='height:3em'>".htmlspecialchars($settings['dayTemplateHeader'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#A5DEA5;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_06.":<br>
				<textarea name='daySETTemplateHeader' style='height:3em'>".htmlspecialchars($settings['daySETTemplateHeader'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#A5DEA5;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_07.":<br>
				<textarea name='daySUNTemplateHeader' style='height:3em'>".htmlspecialchars($settings['daySUNTemplateHeader'])."</textarea>
				</div>
				
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#D3D3FF;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_08.":<br>
				<textarea name='dayTemplate' style='height:3em'>".htmlspecialchars($settings['dayTemplate'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#D3D3FF;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_09.":<br>
				<textarea name='dayCurTemplate' style='height:3em'>".htmlspecialchars($settings['dayCurTemplate'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#A8A8F7;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_10.":<br>
				<textarea name='daySETTemplate' style='height:3em'>".htmlspecialchars($settings['daySETTemplate'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#A8A8F7;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_11.":<br>
				<textarea name='daySUNTemplate' style='height:3em'>".htmlspecialchars($settings['daySUNTemplate'])."</textarea>
				</div>
				
				<div style='padding-bottom:5px'>
				<span align='center' style='padding:0 7px; line-height:20px; background:#E6E6FF; color:#FFFFFF;'>&bull;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_12.":<br>
				<textarea name='nodayTemplate' style='height:3em'>".htmlspecialchars($settings['nodayTemplate'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span align='center' style='padding:0 7px; line-height:20px; background:#BFBFF9; color:#FFFFFF;'>&bull;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_13.":<br>
				<textarea name='nodaySETTemplate' style='height:3em'>".htmlspecialchars($settings['nodaySETTemplate'])."</textarea>
				</div>
				<div style='padding-bottom:5px'>
				<span align='center' style='padding:0 7px; line-height:20px; background:#BFBFF9; color:#FFFFFF;'>&bull;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_14.":<br>
				<textarea name='nodaySUNTemplate' style='height:3em'>".htmlspecialchars($settings['nodaySUNTemplate'])."</textarea>
				</div>
				
			<div style='padding-bottom:5px'>
				<span style='padding:0 7px; line-height:20px; background:#7171D8;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_15.":<br>
				<textarea name='dayTemplateEnd' style='height:3em'>".htmlspecialchars($settings['dayTemplateEnd'])."</textarea>
			</div>
		<div style='padding-bottom:5px'>
			<span style='padding:0 7px; line-height:20px; background:#FDEBB5;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_16.":<br>
			<textarea name='dayTemplateSuffix' style='height:3em'>".htmlspecialchars($settings['dayTemplateSuffix'])."</textarea>
		</div>
	<div style='padding-bottom:5px'>
		<span style='padding:0 7px; line-height:20px; background:#D89292;'>&nbsp;</span> <font color='gray'>".NETCAT_MODULE_CALENDAR_LEG_17.":<br>
		<textarea name='CalendarSuffix' style='height:3em'>".htmlspecialchars($settings['CalendarSuffix'])."</textarea>
	</div>
	<div style='padding-bottom:5px'>
		<font color='gray'>".NETCAT_MODULE_CALENDAR_PIC_LOAD."".($settings['StatusImage'] ? " <a href='" . nc_module_path('calendar') . "images/".$settings['StatusImage']."' target='_blank'>".NETCAT_MODULE_CALENDAR_VIEW."</a>" : "").":<br>
		<input type='text' name='StatusImage' value='".htmlspecialchars($settings['StatusImage'])."' style='width:428px'>
	</div>
	<div style='padding-bottom:5px'>
		<font color='gray'>".NETCAT_MODULE_CALENDAR_PIC_PREV."".($settings['PrevImage'] ? " <a href='" . nc_module_path('calendar') . "images/".$settings['PrevImage']."' target='_blank'>".NETCAT_MODULE_CALENDAR_VIEW."</a>" : "").":<br>
		<input type='text' name='PrevImage' value='".htmlspecialchars($settings['PrevImage'])."' style='width:428px'>
	</div>
	<div style='padding-bottom:5px'>
		<font color='gray'>".NETCAT_MODULE_CALENDAR_PIC_NEXT."".($settings['NextImage'] ? " <a href='" . nc_module_path('calendar') . "images/".$settings['NextImage']."' target='_blank'>".NETCAT_MODULE_CALENDAR_VIEW."</a>" : "").":<br>
		<input type='text' name='NextImage' value='".htmlspecialchars($settings['NextImage'])."' style='width:428px'>
	</div>
  <div style='padding-bottom:5px'>
		<font color='gray'>".NETCAT_MODULE_CALENDAR_PIC_CLOSE."".($settings['CloseImage'] ? " <a href='" . nc_module_path('calendar') . "images/".$settings['CloseImage']."' target='_blank'>".NETCAT_MODULE_CALENDAR_VIEW."</a>" : "").":<br>
		<input type='text' name='CloseImage' value='".htmlspecialchars($settings['CloseImage'])."' style='width:428px'>
	</div>
	<div style='padding-bottom:5px'>
		<font color='gray'>".NETCAT_MODULE_CALENDAR_PIC_CSS.":<br>
		<textarea name='CalendarCSS' style='height:10em'>".htmlspecialchars($settings['CalendarCSS'])."</textarea>
	</div>";

    // кнопки в панели администрирования
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => NETCAT_MODULE_CALENDAR_SAVE_BUTTON,
            "action" => "mainView.submitIframeForm('SetCalendarSettings')");
    echo
    "<input type='hidden' name='phase' value='2'>
  ".$nc_core->token->get_input()."
	</form>";

    echo "
	<form method='post' action='admin.php' id='DeleteCalendar'>
	<input type='hidden' name='ID' value='".(isset($CalendarTheme) ? (int) $CalendarTheme : $settings['ID'])."'>
	<input type='hidden' name='ThemeName' value='".htmlspecialchars($settings['ThemeName'])."'>";
    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_MODULE_CALENDAR_DELETE_BUTTON,
        "action" => "mainView.submitIframeForm('DeleteCalendar')",
        "align" => "left",
        "red_border" => true,
    );
    echo "
  ".$nc_core->token->get_input()."
	<input type='hidden' name='phase' value='3'>
	</form>";

    echo "
	</fieldset>";
}

function SaveFunctional($settings) {
    global $db;
    $settings['SetDefault'] = !empty($settings['SetDefault']) ? 1 : 0;

    // названия дней недели
    for ($i = 1; $i <= 7; $i++) {
        $DayName[] = $settings["DayName$i"];
    }
    $DaysName = join(",", $DayName);

    // есть ли тема "по умолчанию" в БД
    $existTheme = $db->get_results("SELECT ID, DefaultTheme FROM Calendar_Settings", ARRAY_A);

    $existDef = false;
    foreach ($existTheme AS $key => $value) {
        if ($existTheme[$key]['DefaultTheme']) $existDef = true;
    }

    // установка предыдущих тем "НЕ по умолчанию", если обновляемое значение помечено как "по умолчанию"
    if ($settings['SetDefault'])
            $db->query("UPDATE Calendar_Settings SET DefaultTheme=0");

    // запись новой строки
    if (!$existTheme || $settings['ID'] == 'new')
            $db->query("INSERT INTO Calendar_Settings
				(ThemeName,
				DefaultTheme,
				CalendarPrefix,
				CalendarHeader,
				  dayTemplatePrefix,
				    dayTemplateBegin,
				      dayTemplateHeader,
					  daySETTemplateHeader,
					  daySUNTemplateHeader,
					  dayTemplate,
					  dayCurTemplate,
					  daySETTemplate,
					  daySUNTemplate,
					  nodayTemplate,
					  nodaySETTemplate,
					  nodaySUNTemplate,
					dayTemplateEnd,
				  dayTemplateSuffix,
				CalendarSuffix,
				DaysName,
				CalendarCSS,
				StatusImage,
				PrevImage,
				NextImage,
                                CloseImage, 
                                parallax_year_forward, 
                                parallax_year_backward)                                
				VALUES
				('".$db->escape($settings['ThemeName'])."',
				 ".($settings['ID'] == 'new' ? ($existDef && !$settings['SetDefault'] ? 0 : ($settings['SetDefault'] ? 1 : 0)) : (!$settings['SetDefault'] ? 0 : 1)).",
				 '".$db->escape($settings['CalendarPrefix'])."',
				 '".$db->escape($settings['CalendarHeader'])."',
				   '".$db->escape($settings['dayTemplatePrefix'])."',
				     '".$db->escape($settings['dayTemplateBegin'])."',
				       '".$db->escape($settings['dayTemplateHeader'])."',
					   '".$db->escape($settings['daySETTemplateHeader'])."',
					   '".$db->escape($settings['daySUNTemplateHeader'])."',
					   '".$db->escape($settings['dayTemplate'])."',
					   '".$db->escape($settings['dayCurTemplate'])."',
					   '".$db->escape($settings['daySETTemplate'])."',
					   '".$db->escape($settings['daySUNTemplate'])."',
					   '".$db->escape($settings['nodayTemplate'])."',
					   '".$db->escape($settings['nodaySETTemplate'])."',
					   '".$db->escape($settings['nodaySUNTemplate'])."',
					 '".$db->escape($settings['dayTemplateEnd'])."',
				   '".$db->escape($settings['dayTemplateSuffix'])."',
				 '".$db->escape($settings['CalendarSuffix'])."',
				 '".$db->escape($DaysName)."',
				 '".$db->escape($settings['CalendarCSS'])."',
				 '".$db->escape($settings['StatusImage'])."',
				 '".$db->escape($settings['PrevImage'])."',
				 '".$db->escape($settings['NextImage'])."',
                                 '".$db->escape($settings['CloseImage'])."',
                                 ".intval($settings['parallax_year_forward']).",
                                 ".intval($settings['parallax_year_backward']).")");
    else
            $db->query("UPDATE Calendar_Settings
				SET ThemeName='".$db->escape($settings['ThemeName'])."',
				".($settings['SetDefault'] ? "DefaultTheme=1," : "")."
				CalendarPrefix='".$db->escape($settings['CalendarPrefix'])."',
				CalendarHeader='".$db->escape($settings['CalendarHeader'])."',
				  dayTemplatePrefix='".$db->escape($settings['dayTemplatePrefix'])."',
				    dayTemplateBegin='".$db->escape($settings['dayTemplateBegin'])."',
				      dayTemplateHeader='".$db->escape($settings['dayTemplateHeader'])."',
					  daySETTemplateHeader='".$db->escape($settings['daySETTemplateHeader'])."',
					  daySUNTemplateHeader='".$db->escape($settings['daySUNTemplateHeader'])."',
					  dayTemplate='".$db->escape($settings['dayTemplate'])."',
					  dayCurTemplate='".$db->escape($settings['dayCurTemplate'])."',
					  daySETTemplate='".$db->escape($settings['daySETTemplate'])."',
					  daySUNTemplate='".$db->escape($settings['daySUNTemplate'])."',
					  nodayTemplate='".$db->escape($settings['nodayTemplate'])."',
					  nodaySETTemplate='".$db->escape($settings['nodaySETTemplate'])."',
					  nodaySUNTemplate='".$db->escape($settings['nodaySUNTemplate'])."',
					dayTemplateEnd='".$db->escape($settings['dayTemplateEnd'])."',
				  dayTemplateSuffix='".$db->escape($settings['dayTemplateSuffix'])."',
				CalendarSuffix='".$db->escape($settings['CalendarSuffix'])."',
				DaysName='".$db->escape($DaysName)."',
				CalendarCSS='".$db->escape($settings['CalendarCSS'])."',
				StatusImage='".$db->escape($settings['StatusImage'])."',
				PrevImage='".$db->escape($settings['PrevImage'])."',
				NextImage='".$db->escape($settings['NextImage'])."',
                                CloseImage='".$db->escape($settings['CloseImage'])."',
                                parallax_year_forward = ".intval($settings['parallax_year_forward']).", 
                                parallax_year_backward = ".intval($settings['parallax_year_backward'])." 
				WHERE ID='".$db->escape($settings['ID'])."'");

    return true;
}

function DeleteFunctional($id) {
    global $db;

    $db->query("DELETE FROM Calendar_Settings WHERE ID=" . intval($id) . "");

    return true;
}
?>
