<?php
function fPluginData_Init()
{
    include_once(SB_CMS_LIB_PATH.'/sbCustomElements.inc.php');
    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    $GLOBALS['sb_cmp_sort_field'] = 'title';
    uasort($plugins, 'sb_cmp_array');

    foreach ($plugins as $key => $value)
    {
        if(isset($value['table']) && $value['table'] != '')
        {
        	if (!isset($menuObj))
            {
                $menuObj = new sbCustomElements($key);

                $menuObj->mImagePath = '/plugins/';
                $menuObj->mCategsTreeLines = false;
                $menuObj->mHeaderHiddenStr = '<script src="'.SB_CMS_JSCRIPT_URL.'/sbPluginData.js.php"></script>';
            }
            $menuObj->addItem($key, $value['title'], 'pl_plugin_data_output&ident='.$key, str_replace('_24', '_16', substr($value['icon'], strrpos($value['icon'], '/') + 1)));
        }
    }

    if (isset($menuObj))
        $menuObj->init();
    else
        sb_show_message(PL_PLUGIN_DATA_ERROR_NO_MODULES, true, 'warning');
}

function fPluginData_Output()
{
    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
        return;

    $use_categs = $plugins[$_GET['ident']]['use_categs'];

    $res = sql_param_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];

        $pd_fields = $pd_fields != '' ? unserialize($pd_fields) : array();
        $pd_categs = $pd_categs != '' ? unserialize($pd_categs) : array();
    }
    else
    {
        $pd_fields = array();
        $pd_categs = array();
    }

    $top = '<br />';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $layout->mTableWidth = '95%';

    $types = '<select name="el_type" id="el_type" onchange="sbPDChangeType(this)" onkeyup="sbPDChangeType(this)">';
    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
    {
        $types .= '<optgroup label="'.$key.'" >';

        foreach ($value as $key2 => $value2)
        {
            $types .= '<option value="'.$key2.'">'.$value2['title'].'</option>';
        }

        $types .= '</optgroup>';
    }
    $types .= '</select>';

    $body[] = array($types,
                    '<input type="text" name="el_name" id="el_name" style="width:100%" value="" onkeydown="sbPDKeyDown(event);" />',
                    '<img src="'.SB_CMS_IMG_URL.'/btn_props.png" width="20" height="20" id="btn_settings" title="'.PL_PLUGIN_DATA_PROPS.'" onclick="sbPDEditField();" style="cursor:hand;cursor:pointer;" />',
                    '<img src="'.SB_CMS_IMG_URL.'/btn_add.png" width="20" height="20" id="btn_add" title="'.PL_PLUGIN_DATA_ADD.'" onclick="sbPDAddField();" style="cursor:hand;cursor:pointer;" />');

    $table = new sbLayoutTable(array(PL_PLUGIN_DATA_TYPE, PL_PLUGIN_DATA_NAME, '', ''), $body);
    $table->mWidth = array('270', '', '30', '30');
    $table->mAlign = array('center', 'center', 'center', 'center');
    $table->mVAlign = array('middle', 'middle', 'middle', 'middle');

    $layout->addField('',  $table);
    $top .= $layout->show(false);

    $fields = '<table cellpadding="5" cellspacing="0"  width="100%" class="form" id="plugin_data_table" style="empty-cells:show;-moz-user-select: none;">
            <tr>
                <th class="header" width="30">'.PL_PLUGIN_DATA_ID.'</th>
                <th class="header" width="30">'.PL_PLUGIN_DATA_TYPE.'</th>
                <th class="header">'.PL_PLUGIN_DATA_NAME.'</th>';

    if ($_GET['ident'] != 'pl_menu')
    {
    	$fields .= '<th class="header" width="50">'.PL_PLUGIN_DATA_INFO.'</th>
                <th class="header" width="50">'.PL_PLUGIN_DATA_SORT.'</th>
                <th class="header" width="50">'.PL_PLUGIN_DATA_FILTER.'</th>';
    }

    $fields .= '<th class="header" width="50">'.PL_PLUGIN_DATA_MANDATORY.'</th>
                <th class="header" width="30">&nbsp;</th>
                <th class="header" width="30">&nbsp;</th>
            </tr>';

    // Поля элементов
    $class = ' class="even"';
    foreach($pd_fields as $value)
    {
        $id = $value['id'];
        $type = $value['type'];
        $title = $value['title'];
        $info = $value['info'];
        $sort = $value['sort'];
        $filter = $value['filter'];
        $mandatory = $value['mandatory'];

        $img_alt = '';
        $img = '';

        if (file_exists(SB_CMS_IMG_PATH.'/data_types/'.$type.'.gif'))
        {
            $img = SB_CMS_IMG_URL.'/data_types/'.$type.'.gif';

            if(isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_1][$type]))
            {
                $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_1][$type]['title'];
            }
            elseif (isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_2][$type]))
            {
                $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_2][$type]['title'];
            }
            elseif (isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_3][$type]))
            {
                $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_3][$type]['title'];
            }
        }

        if ($class == '')
            $class = ' class="even"';
        else
            $class = '';

        $fields .= '<tr'.$class.' id="'.$id.'" onmousedown="sbPDStartDrag(event, this)" style="cursor:default;">
                <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.$id.'</td>
                <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($img != '' ? '<img src="'.$img.'" alt="'.$img_alt.'" title="'.$img_alt.'" width="16" height="16" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" />' : '&nbsp;').'</td>
                <td style="vertical-align: middle; border-top: 1px solid #FFF5E0;"><span ondblclick="sbPDEditName(this)" id="name_'.$id.'">'.($title != '' ? $title : '&nbsp;').'</span></td>';

        if ($_GET['ident'] != 'pl_menu')
        {
        	$fields .= '<td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'password' && $type != 'label' && $type != 'tab' && $type != 'text' && $type != 'longtext' && $type != 'jscript' && $type != 'table' ? '<input type="checkbox"'.($info == 1 ? ' checked="checked"' : '').' onclick="sbPDChangeCheckbox(\'info\', this, '.$id.')" id="info_'.$id.'" title="'.PL_PLUGIN_DATA_INFO_TITLE.'" />' : '&nbsp;').'</td>
                <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'password' && $type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'text' && $type != 'longtext' && $type != 'jscript' && $type != 'multiselect_sprav' && $type != 'checkbox_sprav' && $type != 'select_sprav' && $type != 'radio_sprav' && $type != 'link_sprav' && $type != 'categs' && $type != 'google_coords' && $type != 'yandex_coords' && $type != 'table' && $type != 'select_plugin' && $type != 'radio_plugin' && $type != 'checkbox_plugin' && $type != 'link_plugin' && $type != 'multiselect_plugin' ? '<input type="checkbox"'.($sort == 1 ? ' checked="checked"' : '').' onclick="sbPDChangeCheckbox(\'sort\', this, '.$id.')" id="sort_'.$id.'" title="'.PL_PLUGIN_DATA_SORT_TITLE.'" />' : '&nbsp;').'</td>
                <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'password' && $type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'jscript' && $type != 'google_coords' && $type != 'yandex_coords' && $type != 'table' ? '<input type="checkbox"'.($filter == 1 ? ' checked="checked"' : '').' onclick="sbPDChangeCheckbox(\'filter\', this, '.$id.')" id="filter_'.$id.'" title="'.PL_PLUGIN_DATA_FILTER_TITLE.'" />' : '&nbsp;').'</td>';
        }

        $fields .= '<td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'checkbox' && $type != 'jscript' && $type != 'php' ? '<input type="checkbox"'.($mandatory == 1 ? ' checked="checked"' : '').' onclick="sbPDChangeCheckbox(\'mandatory\', this, '.$id.')" id="mandatory_'.$id.'" title="'.PL_PLUGIN_DATA_MANDATORY_TITLE.'" />' : '&nbsp;').'</td>
                <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'hr' ? '<img src="'.SB_CMS_IMG_URL.'/btn_props.png" width="20" height="20" border="0" title="'.PL_PLUGIN_DATA_PROPS.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="sbPDEditField(this, '.$id.');"  style="cursor:hand;cursor:pointer;" />' : '&nbsp;').'</td>
				<td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;"><img src="'.SB_CMS_IMG_URL.'/btn_delete.png" width="20" height="20" title="'.PL_PLUGIN_DATA_DELETE.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="sbPDDeleteField('.$id.');" style="cursor:hand;cursor:pointer;" /></td>
			</tr>';
    }

    if ($class == '')
        $class = ' class="even"';
    else
        $class = '';

    $fields .= '<tr'.$class.' id="null_tr">
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>';

    if ($_GET['ident'] != 'pl_menu')
    {
    	$fields .= '<td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>';
    }

    $fields .= '<td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td></tr></table>';

    $categs = '';
    if ($use_categs)
    {
        // Поля разделов
        $categs = '<table cellpadding="5" cellspacing="0"  width="100%" class="form" id="categ_data_table" style="empty-cells:show;-moz-user-select: none;">
            <tr>
                <th class="header" width="30">'.PL_PLUGIN_DATA_ID.'</th>
                <th class="header" width="30">'.PL_PLUGIN_DATA_TYPE.'</th>
                <th class="header">'.PL_PLUGIN_DATA_NAME.'</th>
                <th class="header" width="50">'.PL_PLUGIN_DATA_MANDATORY.'</th>
                <th class="header" width="30">&nbsp;</th>
                <th class="header" width="30">&nbsp;</th>
            </tr>';

        $class = ' class="even"';
        foreach($pd_categs as $value)
        {
            $id = $value['id'];
            $type = $value['type'];
            $title = $value['title'];
            $info = $value['info'];
            $sort = $value['sort'];
            $mandatory = $value['mandatory'];

            $img_alt = '';
            $img = '';

            if (file_exists(SB_CMS_IMG_PATH.'/data_types/'.$type.'.gif'))
            {
                $img = SB_CMS_IMG_URL.'/data_types/'.$type.'.gif';

                if(isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_1][$type]))
                {
                    $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_1][$type]['title'];
                }
                elseif (isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_2][$type]))
                {
                    $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_2][$type]['title'];
                }
                elseif (isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_3][$type]))
                {
                    $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_3][$type]['title'];
                }
            	elseif (isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_4][$type]))
                {
                    $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_4][$type]['title'];
                }
            	elseif (isset($GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_5][$type]))
                {
                    $img_alt = $GLOBALS['sb_plugins_fields'][PL_PLUGIN_DATA_FIELD_TYPE_5][$type]['title'];
                }
            }

            if ($class == '')
                $class = ' class="even"';
            else
                $class = '';

            $categs .= '<tr'.$class.' id="'.$id.'" onmousedown="sbPDStartDrag(event, this)" style="cursor:default;">
                    <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.$id.'</td>
                    <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($img != '' ? '<img src="'.$img.'" alt="'.$img_alt.'" title="'.$img_alt.'" width="16" height="16" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" />' : '&nbsp;').'</td>
                    <td style="vertical-align: middle; border-top: 1px solid #FFF5E0;"><span ondblclick="sbPDEditName(this)" id="name_'.$id.'">'.($title != '' ? $title : '&nbsp;').'</span></td>
                    <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'checkbox' && $type != 'jscript' && $type != 'php' ? '<input type="checkbox"'.($mandatory == 1 ? ' checked="checked"' : '').' onclick="sbPDChangeCheckbox(\'mandatory\', this, '.$id.')" id="mandatory_'.$id.'" title="'.PL_PLUGIN_DATA_MANDATORY_TITLE.'" />' : '&nbsp;').'</td>
                    <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;">'.($type != 'hr' ? '<img src="'.SB_CMS_IMG_URL.'/btn_props.png" width="20" height="20" border="0" title="'.PL_PLUGIN_DATA_PROPS.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="sbPDEditField(this, '.$id.');"  style="cursor:hand;cursor:pointer;" />' : '&nbsp;').'</td>
                    <td align="center" style="vertical-align: middle; border-top: 1px solid #FFF5E0;"><img src="'.SB_CMS_IMG_URL.'/btn_delete.png" width="20" height="20" title="'.PL_PLUGIN_DATA_DELETE.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="sbPDDeleteField('.$id.');" style="cursor:hand;cursor:pointer;" /></td>
                </tr>';
        }

        if ($class == '')
            $class = ' class="even"';
        else
            $class = '';

        $categs .= '<tr'.$class.' id="categ_null_tr">
                    <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                    <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                    <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                    <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                    <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td>
                    <td style="border-top: 1px solid #FFF5E0;">&nbsp;</td></tr></table>';
    }

    // вывод закладок
    require_once(SB_CMS_LIB_PATH.'/sbTabs.inc.php');

    $tabs = new sbTabs();
    $tabs->mOnLoad = 'sbResizeEldiv';
    $tabs->setTop($top);

    $tabs->addTab(PL_PLUGIN_DATA_TAB1, $fields);
    if ($categs != '')
        $tabs->addTab(PL_PLUGIN_DATA_TAB2, $categs);

    $tabs->show();

    echo '<script>
        sbLoadTabs();
    </script>';
}

function fPluginData_Add()
{
    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
    {
        echo 'FALSE';
        return;
    }

    $plugin_title = $plugins[$_GET['ident']]['title'];
    $plugin_table = $plugins[$_GET['ident']]['table'];

    $type_info = false;
    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
    {
        foreach ($value as $key2 => $value2)
        {
            if ($key2 == $_GET['el_type'])
            {
                $type_info = $value2;
                break;
            }
        }
    }

    if (!$type_info)
    {
        echo 'FALSE';
        return;
    }

    $settings = isset($type_info['settings']) ? $type_info['settings'] : array();
    $el_name = strip_tags($_GET['el_name']);

    $tag = preg_replace('/[^a-zA-Z'.$GLOBALS['sb_reg_upper_interval'].$GLOBALS['sb_reg_lower_interval'].'0-9_]+/'.SB_PREG_MOD, '_', $el_name);
    $tag = sb_strtolat($tag);
    $tag = sb_strtoupper($tag);

    $categs = false;
    if (isset($_GET['categs']) && $_GET['categs'] == 1)
    {
        $res = sql_param_query('SELECT pd_categs, pd_increment FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
        $categs = true;
    }
    else
    {
        $res = sql_param_query('SELECT pd_fields, pd_increment FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    }

    //Проверяем поля типа VARCHAR
    if(!$categs && ($max_length = fPluginData_Get_MaxVarchar($_GET['el_type'])) !== false)
    {
        if($max_length == 0 && ($_GET['el_type'] == 'string' || $_GET['el_type'] == 'password'))
        {
            $max_length = 255;
        }

        $res1 = sql_param_query('SHOW CREATE TABLE '.$plugins[$_GET['ident']]['table']);
        $matches = array();
        preg_match_all('/varchar\s*\(\d+\)/i'.SB_PREG_MOD, $res1[0][1], $matches);

        $factor = SB_PREG_MOD == 'u' ? 4 : 1;
        if (isset($matches[0]) && !empty($matches[0]))
        {
            $bytes = $max_length * $factor;
            $fields = preg_replace('/varchar\s*\((\d+)\)/i'.SB_PREG_MOD, '$1', $matches[0]);
            $bytes += array_sum($fields)*$factor;

            if($bytes > 65534-count($fields))
            {
                echo 'FALSE';
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_STRING_LIMIT_SYSTEM, $_GET['el_name']), SB_MSG_ERROR);
                return;
            }
        }
    }

    $num = 0;
    $id = -1;
    if ($res)
    {
        list($pd_fields, $pd_increment) = $res[0];
        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);
            $num = count($pd_fields);
        }
        else
        {
            $pd_fields = array();
        }

        $id = $pd_increment + 1;
    }
    else
    {
        $id = 1;
        $pd_fields = array();
    }

    $tag .= '_'.$id;

    $pd_fields[$num] = array();
    $pd_fields[$num]['id'] = $id;
    $pd_fields[$num]['sql'] = (isset($type_info['sql']) && $type_info['sql'] != '') ? 1 : 0;
    $pd_fields[$num]['type'] = $_GET['el_type'];
    $pd_fields[$num]['title'] = $el_name;
    $pd_fields[$num]['info'] = 0;
    $pd_fields[$num]['sort'] = 0;
    $pd_fields[$num]['rights_set'] = 0;
    $pd_fields[$num]['rights_edit_list'] = '';
    $pd_fields[$num]['rights_view_list'] = '';
    $pd_fields[$num]['filter'] = 0;
    $pd_fields[$num]['mandatory'] = 0;
    $pd_fields[$num]['mandatory_val'] = 'if (\'{VALUE}\' == \'\') $error = true;';
    $pd_fields[$num]['mandatory_err'] = '';
    $pd_fields[$num]['settings'] = $settings;
    $pd_fields[$num]['tag'] = $tag;

    if (!$categs)
    {
        // если добавляется поле для элемента, то при необходимости создаем поле в таблице элементов
        $res_alter = true;
        if (isset($type_info['sql']) && $type_info['sql'] != '')
        {
        	$sql = 'ALTER TABLE `'.$plugin_table.'` ADD `user_f_'.$id.'` '.$type_info['sql'];
	        if (isset($settings['max_length']))
            {
                $res_alter = sql_param_query($sql, $settings['max_length'], $el_name);
            }
            else
            {
                $res_alter = sql_param_query($sql, $el_name);
            }
        }

        if (!$res_alter)
        {
            echo 'FALSE';
            return;
        }
    }

    if ($res)
    {
        if ($categs)
            $result = sql_param_query('UPDATE sb_plugins_data SET pd_categs=?, pd_increment=?d WHERE pd_plugin_ident=?', serialize($pd_fields), $id, $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_ADD_CATEG_OK, $el_name, $plugin_title) : ''));
        else
            $result = sql_param_query('UPDATE sb_plugins_data SET pd_fields=?, pd_increment=?d WHERE pd_plugin_ident=?', serialize($pd_fields), $id, $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_ADD_OK, $el_name, $plugin_title) : ''));
    }
    else
    {
        if ($categs)
        {
            $result = sql_param_query('INSERT INTO sb_plugins_data SET pd_plugin_ident=?, pd_fields="", pd_categs=?, pd_increment=?d', $_GET['ident'], serialize($pd_fields), $id, ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_ADD_CATEG_OK, $el_name, $plugin_title) : ''));
        }
        else
        {
            $result = sql_param_query('INSERT INTO sb_plugins_data SET pd_plugin_ident=?, pd_fields=?, pd_categs="", pd_increment=?d', $_GET['ident'], serialize($pd_fields), $id, ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_ADD_OK, $el_name, $plugin_title) : ''));
            if (!$result)
            {
	            sql_query('ALTER TABLE `'.$plugin_table.'` DROP `user_f_'.$id.'`');
            }
        }
    }

    if (!$result)
    {
        if ($plugin_title != '')
        {
            if ($categs)
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_ADD_CATEG_ERROR, $el_name, $plugin_title), SB_MSG_WARNING);
            else
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_ADD_ERROR, $el_name, $plugin_title), SB_MSG_WARNING);
        }

        echo 'FALSE';
        return;
    }

    $img = '';

    if (file_exists(SB_CMS_IMG_PATH.'/data_types/'.$_GET['el_type'].'.gif'))
    {
        $img = SB_CMS_IMG_URL.'/data_types/'.$_GET['el_type'].'.gif';
    }

    echo $id.'|'.$img.'|'.$type_info['title'];
}

function fPluginData_Sort()
{
    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
    {
        echo PL_PLUGIN_DATA_SORT_ERROR;
        return;
    }

    $plugin_title = $plugins[$_GET['ident']]['title'];

    $categs = false;
    if (isset($_GET['categs']) && $_GET['categs'] == 1)
    {
        $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
        $categs = true;
    }
    else
    {
        $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    }

    if (!$res)
    {
        echo PL_PLUGIN_DATA_SORT_ERROR;
        return;
    }

    $pd_fields = unserialize($res[0][0]);

    // Удаляем перетаскиваемое поле
    $old_value = false;
    $old_title = false;
    foreach ($pd_fields as $key => $value)
    {
        if ($value['id'] == $_GET['id'])
        {
            $old_value = $value;
            $old_title = $value['title'];
            array_splice($pd_fields, $key, 1);
            break;
        }
    }

    if ($old_value === false)
    {
        echo PL_PLUGIN_DATA_SORT_ERROR;
        return;
    }

    $swap = false;
    foreach ($pd_fields as $key => $value)
    {
        if ($value['id'] == $_GET['prev_id'])
        {
            $swap = true;
        }

        if ($swap)
        {
            $pd_fields[$key] = $old_value;
            $old_value = $value;
        }
    }

    $pd_fields[count($pd_fields)] = $old_value;

    if ($categs)
    {
        $res = sql_param_query('UPDATE sb_plugins_data SET pd_categs=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_SORT_CATEG_OK, $old_title, $plugin_title) : ''));
    }
    else
    {
        $res = sql_param_query('UPDATE sb_plugins_data SET pd_fields=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_SORT_OK, $old_title, $plugin_title) : ''));
    }

    if (!$res)
    {
        if ($plugin_title != '')
        {
            if ($categs)
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_SORT_CATEG_ERROR, $old_title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_SORT_CATEG_ERROR, $old_title, $plugin_title);
            }
            else
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_SORT_ERROR, $old_title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_SORT_ERROR, $old_title, $plugin_title);
            }
            return;
        }
        else
        {
            echo PL_PLUGIN_DATA_SORT_ERROR;
            return;
        }
    }
}

function fPluginData_Rename()
{
    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
    {
        echo PL_PLUGIN_DATA_RENAME_ERROR;
        return;
    }

    $plugin_title = $plugins[$_GET['ident']]['title'];
    $plugin_table = $plugins[$_GET['ident']]['table'];

    $categs = false;
    if (isset($_GET['categs']) && $_GET['categs'] == 1)
    {
        $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
        $categs = true;
    }
    else
    {
        $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    }

    if (!$res)
    {
        echo PL_PLUGIN_DATA_RENAME_ERROR;
        return;
    }

    $id = intval($_GET['id']);
    $new_title = strip_tags($_GET['text']);
	$old_title = '';
	$type = '';
	$settings = array();

	$pd_fields = unserialize($res[0][0]);

	$found = false;
    foreach ($pd_fields as $key => $value)
    {
        if ($value['id'] == $id)
        {
        	$old_title = $pd_fields[$key]['title'];
            $pd_fields[$key]['title'] = $new_title;
            $type = $pd_fields[$key]['type'];
            $settings = $pd_fields[$key]['settings'];

            $found = true;
            break;
        }
    }

    if (!$found)
    {
        echo PL_PLUGIN_DATA_RENAME_ERROR;
        return;
    }

    if ($categs)
    {
        $res = sql_param_query('UPDATE sb_plugins_data SET pd_categs=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_RENAME_CATEG_OK, $new_title, $plugin_title) : ''));
    }
    else
    {
        $res = sql_param_query('UPDATE sb_plugins_data SET pd_fields=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_RENAME_OK, $new_title, $plugin_title) : ''));
        if ($old_title != $new_title)
        {
	        $type_info = false;
		    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
		    {
		        foreach ($value as $key2 => $value2)
		        {
		            if ($key2 == $type)
		            {
		                $type_info = $value2;
		                break;
		            }
		        }
		    }

		    if ($type_info && isset($type_info['sql']) && $type_info['sql'] != '')
		    {
		    	if (isset($settings['max_length']))
	        	{
	                sql_param_query('ALTER TABLE `'.$plugin_table.'` CHANGE `user_f_'.$id.'` `user_f_'.$id.'` '.$type_info['sql'], $settings['max_length'], $new_title);
	        	}
	        	else
	        	{
	        		sql_param_query('ALTER TABLE `'.$plugin_table.'` CHANGE `user_f_'.$id.'` `user_f_'.$id.'` '.$type_info['sql'], $new_title);
	        	}
		    }
        }
    }

    if (!$res)
    {
        if ($plugin_title != '')
        {
            if ($categs)
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_RENAME_CATEGS_ERROR, $new_title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_RENAME_CATEGS_ERROR, $new_title, $plugin_title);
            }
            else
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_RENAME_ERROR, $new_title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_RENAME_ERROR, $new_title, $plugin_title);
            }
            return;
        }
        else
        {
            echo PL_PLUGIN_DATA_RENAME_ERROR;
            return;
        }
    }
}

function fPluginData_Change()
{
    $type = $_GET['chk_type'];
    if ($type != 'sort' && $type != 'info' && $type != 'mandatory' && $type != 'filter')
    {
        echo PL_PLUGIN_DATA_CHANGE_ERROR;
        return;
    }

    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
    {
        echo PL_PLUGIN_DATA_CHANGE_ERROR;
        return;
    }

    $plugin_title = $plugins[$_GET['ident']]['title'];

    $categs = false;
    if (isset($_GET['categs']) && $_GET['categs'] == 1)
    {
        $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
        $categs = true;
    }
    else
    {
        $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    }

    if (!$res)
    {
        echo PL_PLUGIN_DATA_CHANGE_ERROR;
        return;
    }
    $pd_fields = unserialize($res[0][0]);
    $found = false;
    $title = '';
    foreach ($pd_fields as $key => $value)
    {
        if ($value['id'] == $_GET['id'])
        {
            $pd_fields[$key][$type] = intval($_GET['ch']);
            $title = $value['title'];
            if ($type == 'mandatory')
            {
                if (intval($_GET['ch']) == 0)
                {
                    $pd_fields[$key]['mandatory_err'] = '';
                    $pd_fields[$key]['mandatory_val'] = 'if (\'{VALUE}\' == \'\') $error = true;';
                }
                else
                {
                    $pd_fields[$key]['mandatory_err'] = sprintf(PL_PLUGIN_DATA_FIELD_MANDATORY_ERROR_DEF, $pd_fields[$key]['title']);
                    $pd_fields[$key]['mandatory_val'] = 'if (\'{VALUE}\' == \'\') $error = true;';
                }
            }
            $found = true;
            break;
        }
    }

    if (!$found)
    {
        echo PL_PLUGIN_DATA_CHANGE_ERROR;
        return;
    }

    if ($categs)
        $res = sql_param_query('UPDATE sb_plugins_data SET pd_categs=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_CHANGE_CATEG_OK, $title, $plugin_title) : ''));
    else
        $res = sql_param_query('UPDATE sb_plugins_data SET pd_fields=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_CHANGE_OK, $title, $plugin_title) : ''));

    if (!$res)
    {
        if ($plugin_title != '')
        {
            if ($categ)
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_CHANGE_CATEG_ERROR, $title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_CHANGE_CATEG_ERROR, $title, $plugin_title);
            }
            else
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_CHANGE_ERROR, $title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_CHANGE_ERROR, $title, $plugin_title);
            }

            return;
        }
        else
        {
            echo PL_PLUGIN_DATA_CHANGE_ERROR;
            return;
        }
    }
}

function fPluginData_Delete()
{
    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
    {
        echo PL_PLUGIN_DATA_DELETE_ERROR;
        return;
    }

    $plugin_title = $plugins[$_GET['ident']]['title'];
    $plugin_table = $plugins[$_GET['ident']]['table'];

    $categs = false;
    if (isset($_GET['categs']) && $_GET['categs'] == 1)
    {
		$res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
		$categs = true;
	}
	else
	{
		$res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
	}

    if (!$res)
    {
        echo PL_PLUGIN_DATA_DELETE_ERROR;
        return;
    }

    $pd_fields = unserialize($res[0][0]);
    $found = false;
    $title = '';
    $type = '';
    foreach ($pd_fields as $key => $value)
    {
        if ($value['id'] == $_GET['id'])
        {
            $title = $value['title'];
            $type = $value['type'];
            array_splice($pd_fields, $key, 1);
            $found = true;
            break;
        }
    }

    if (!$found)
    {
        echo PL_PLUGIN_DATA_DELETE_ERROR;
        return;
    }

    $type_info = false;
    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
    {
        foreach ($value as $key2 => $value2)
        {
            if ($key2 == $type)
            {
                $type_info = $value2;
                break;
            }
        }
    }

    if (!$type_info)
    {
        echo PL_PLUGIN_DATA_DELETE_ERROR;
        return;
    }

    $res = true;
    if(isset($type_info['sql']) && $type_info['sql'] != '' && !$categs)
    {
    	$res = sql_query('ALTER TABLE `'.$plugin_table.'` DROP `user_f_'.intval($_GET['id']).'`');
    }

    $result = false;
    if ($res)
    {
	    if ($categs)
	    {
	        $result = sql_param_query('UPDATE sb_plugins_data SET pd_categs=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_DELETE_CATEG_OK, $title, $plugin_title) : ''));
	    }
	    else
	    {
	        $result = sql_param_query('UPDATE sb_plugins_data SET pd_fields=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_DELETE_OK, $title, $plugin_title) : ''));
	    }
    }

    if (!$res || !$result)
    {
        if ($plugin_title != '')
        {
            if ($categs)
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_DELETE_CATEG_ERROR, $title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_DELETE_CATEG_ERROR, $title, $plugin_title);
            }
            else
            {
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_DELETE_ERROR, $title, $plugin_title), SB_MSG_WARNING);
                echo sprintf(PL_PLUGIN_DATA_SYSLOG_DELETE_ERROR, $title, $plugin_title);
            }
            return;
        }
        else
        {
            echo PL_PLUGIN_DATA_DELETE_ERROR;
            return;
        }
    }
}

function fPluginData_Edit()
{
    $title = '';
    $type = '';

    $categs = isset($_GET['categs']) && $_GET['categs'] == 1;

    if (isset($_GET['id']))
    {
        // редактирование настроек существующего поля
        if ($categs)
        {
            $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
        }
        else
        {
            $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
        }

        if (!$res)
        {
            sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR, true, 'warning');
            return;
        }

        $pd_fields = unserialize($res[0][0]);
        $found = false;
        foreach ($pd_fields as $key => $value)
        {
            if ($value['id'] == $_GET['id'])
            {
                $title = $value['title'];
                $type = $value['type'];
                if (isset($value['settings']))
                	$settings = $value['settings'];
                else
                	$settings = array();

                if(isset($value['rights_set']))
                	$rights_set = $value['rights_set'];
                else
                	$rights_set = 0;

                 if(isset($value['rights_edit_list']))
                	$rights_edit_list = $value['rights_edit_list'];
                else
                	$rights_edit_list = '';

                if(isset($value['rights_view_list']))
                	$rights_view_list = $value['rights_view_list'];
                else
                	$rights_view_list = '';

                $info = $value['info'];
                $sort = $value['sort'];
                $filter = $value['filter'];
                $active = isset($value['active']) ? $value['active'] : 0;
                $mandatory = $value['mandatory'];
                $mandatory_val = $value['mandatory_val'];
                $mandatory_err = $value['mandatory_err'];

                if (isset($value['attribs']))
                	$attribs = $value['attribs'];
                else
                	$attribs = '';

                $multiselect = isset($value['multiselect']) ? $value['multiselect'] : 0;

                $found = true;
                break;
            }
        }

        if (!$found)
        {
            sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR, true, 'warning');
            return;
        }
    }
    else
    {
        // редактирование настроек нового поля
        $type = $_GET['type'];
        $title = isset($_POST['el_name']) ? strip_tags($_POST['el_name']) : $_GET['title'];
        $settings = isset($_POST['settings'])? $_POST['settings'] : array();
        $info = isset($_POST['info']) ? 1 : 0;
        $sort = isset($_POST['sort']) ? 1 : 0;
        $filter = isset($_POST['filter']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        $mandatory = isset($_POST['mandatory']) ? 1 : 0;
        $mandatory_val = 'if (\'{VALUE}\' == \'\') $error = true;';
        $mandatory_err = '';
        $attribs = isset($_POST['attribs']) ? $_POST['attribs'] : '';
        $rights_set = isset($_POST['rights_set']) ? 1 : 0;
        $rights_edit_list = isset($_POST['rights_edit_list_ids']) ? $_POST['rights_edit_list_ids'] : '';
        $rights_view_list = isset($_POST['rights_view_list_ids']) ? $_POST['rights_view_list_ids'] : '';
        $multiselect = isset($_POST['multiselect']) ? $_POST['multiselect'] : '';
    }

    $type_info = false;
    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
    {
        foreach ($value as $key2 => $value2)
        {
            if ($key2 == $type)
            {
                $type_info = $value2;
                break;
            }
        }
    }
    if (!$type_info)
    {
        sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR2, true, 'warning');
        return;
    }

    $type_settings = isset($type_info['settings']) ? $type_info['settings'] : array();
    if (isset($_GET['id']))
    {
        foreach ($type_settings as $key => $value)
        {
            if (!isset($settings[$key]))
            {
                $settings[$key] = $value;
            }
        }
    }
    else
    {
        if(empty($settings))
        {
            $settings = $type_settings;
        }
    }

    $javascript_str = '
    <script src="'.SB_CMS_JSCRIPT_URL.'/sbPluginDataEdit.js.php"></script>
    <script>
    function checkValues()
    {
        ';

    if ($type != 'label')
    {
        $javascript_str .= '
        var el_name = sbGetE("el_name");
        if (!el_name || el_name.value == "")
        {
            sbShowMsgDiv("'.PL_PLUGIN_DATA_NONAME_MSG.'");
            return false;
        }
        ';
    }
    if ($type == 'table')
    {
    	$javascript_str .= '
			var rows = sbGetE("spin_table_rows");
        	var colls = sbGetE("spin_table_colls");

        	if(!rows || rows.value < 1 || !colls || colls.value < 1)
        	{
        		sbShowMsgDiv("'.PL_PLUGIN_DATA_FIELD_TABLE_ERROR.'");
        		return false;
			}';
    }
    
    if($type == 'image')
    {
        $javascript_str .= '
			var img_path = sbGetE("path");

        	if(!img_path || img_path.value == "")
        	{
        		sbShowMsgDiv("'.PL_PLUGIN_DATA_FIELD_IMG_PATH.'");
        		return false;
			}';
    }

	$javascript_str .= '
        var min_length = sbGetE("spin_min_length");
        var max_length = sbGetE("spin_max_length");
        var char_count = sbGetE("char_count");
        if (min_length)
        {
            if (min_length.value == "" || parseInt(min_length.value) < 0 || (max_length && parseInt(min_length.value) > parseInt(max_length.value)))
            {
                sbShowMsgDiv("'.PL_PLUGIN_DATA_MIN_MAX_LENGTH_MSG.'");
                return false;
            }
        }

        if (max_length && !char_count)
        {
            if (max_length.value == "" || parseInt(max_length.value) <= 0)
            {
                sbShowMsgDiv("'.PL_PLUGIN_DATA_MIN_MAX_LENGTH_MSG.'");
                return false;
            }
        }
        else if((max_length && char_count))
        {
        	if(char_count.checked && (max_length.value == "" || parseInt(max_length.value) <= 0))
        	{
        		sbShowMsgDiv("'.PL_PLUGIN_DATA_MIN_MAX_LENGTH_MSG.'");
                return false;
        	}
        }

        var min_value = sbGetE("spin_min_value");
        var max_value = sbGetE("spin_max_value");
        if (min_value && max_value)
        {
            if (min_value.value != "" && max_value.value != "" && parseInt(min_value.value) > parseInt(max_value.value))
            {
                sbShowMsgDiv("'.PL_PLUGIN_DATA_MIN_MAX_VALUE_MSG.'");
                return false;
            }
        }

        var img_chk = sbGetE("resize");
        var img_width = sbGetE("spin_img_width");
        var img_height = sbGetE("spin_img_height");
        if (img_chk && img_chk.checked && img_width && img_height && img_width.value == "" && img_height.value == "")
        {
            sbShowMsgDiv("'.PL_PLUGIN_DATA_WIDTH_HEIGHT_VALUE_MSG.'");
            return false;
        }

        var sprav = sbGetE("sprav_id");
        var spravs = sbGetE("sprav_ids");
        if (sprav && sprav.value == "" || spravs && spravs.value == "")
        {
            sbShowMsgDiv("'.PL_PLUGIN_DATA_SPRAV_MSG.'");
            return false;
        }

        var modules_cats = sbGetE("modules_cat_id");
        if (modules_cats && modules_cats.value == "")
        {
            sbShowMsgDiv("'.PL_PLUGIN_DATA_PLUGIN_CATS_MSG.'");
            return false;
        }

    }
        ';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    if (isset($_GET['id']))
    {
        $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_data_edit_submit&ident='.$_GET['ident'].'&type='.$type.'&categs='.($categs ? '1' : '0').'&id='.$_GET['id']);
    }
    else
    {
        $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_data_edit_submit&ident='.$_GET['ident'].'&type='.$type.'&categs='.($categs ? '1' : '0'));
    }

    $layout->mTableWidth = '98%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_PLUGIN_DATA_EDIT_TAB1);
	$layout->addHeader(PL_PLUGIN_DATA_EDIT_TITLE);
	$layout->addField(PL_PLUGIN_DATA_NAME, new sbLayoutInput('text', $title, 'el_name', '', 'style="width: 480px;"', $type != 'label'));

	//	поля строятся в зависимости от типа
	foreach ($settings as $key => $value)
	{
        switch ($key)
        {
            case 'width':
                $layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_WIDTH, new sbLayoutInput('text', $value, $key, '', 'style="width:65px;"'));
                break;

            case 'widths':
                $layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_WIDTHS, new sbLayoutInput('text', $value, $key, '', 'style="width:65px;"'));
                break;

            case 'width_link':
                $layout->addField(PL_PLUGIN_DATA_FIELD_WIDTH_LINK, new sbLayoutInput('text', $value, $key, '', 'style="width:65px;"'));
                break;

            case 'height':
                $layout->addField(PL_PLUGIN_DATA_FIELD_HEIGHT, new sbLayoutInput('text', $value, $key, '', 'style="width:65px;"'));
                $layout->addField('', new sbLayoutDelim());
                break;

            case 'map_width':
                $layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_MAP_WIDTH, new sbLayoutInput('text', $value, $key, '', 'style="width:65px;"'));
                break;

            case 'map_height':
                $layout->addField(PL_PLUGIN_DATA_FIELD_MAP_HEIGHT, new sbLayoutInput('text', $value, $key, '', 'style="width:65px;"'));
                break;

            case 'max_value':
                $layout->addField(PL_PLUGIN_DATA_FIELD_MAX_VALUE, new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"'));
                $layout->addField('', new sbLayoutDelim());
                break;

            case 'min_value':
                $layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_MIN_VALUE, new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"'));
                break;

            case 'max_length':
                $layout->addField(PL_PLUGIN_DATA_FIELD_MAX_LENGTH, new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"'));
                $layout->addField('', new sbLayoutDelim());
                break;

            case 'min_length':
                $layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_MIN_LENGTH, new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"'));
                break;

            case 'checked_text':
                $layout->addField(PL_PLUGIN_DATA_FIELD_CHECKED_TEXT, new sbLayoutInput('text', $value, $key, '', 'style="width:480px;"'));
                break;

            case 'not_checked_text':
                $layout->addField(PL_PLUGIN_DATA_FIELD_NOT_CHECKED_TEXT, new sbLayoutInput('text', $value, $key, '', 'style="width:480px;"'));
                break;

            case 'count':
            	$fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"');
            	$fld -> mMinValue = 1;
                $layout->addField(PL_PLUGIN_DATA_FIELD_COUNT, $fld);
                break;

            case 'rows':
            	$fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"');
            	$fld -> mMinValue = 1;
                $layout->addField(PL_PLUGIN_DATA_FIELD_ROWS, $fld);
                break;

            case 'rows_link':
            	$fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"');
            	$fld -> mMinValue = 1;
                $layout->addField(PL_PLUGIN_DATA_FIELD_ROWS_LINK, $fld);
                break;

            case 'code':
				$layout->addField('', new sbLayoutDelim());
				$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.($type == 'jscript' ? PL_PLUGIN_DATA_FIELD_JSCRIPT_OPEN_HINT : PL_PLUGIN_DATA_FIELD_PHP_HINT).'</div>', true));
            	$layout->addField($type == 'jscript' ? PL_PLUGIN_DATA_FIELD_JSCRIPT_OPEN : PL_PLUGIN_DATA_FIELD_PHP, new sbLayoutTextarea($value, $key, '', 'style="width:100%;height:150px;"'));
            	break;

            case 'submit_code':
				$layout->addField('', new sbLayoutDelim());
				$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_PHP_SUBMIT_HINT.'</div>', true));
            	$layout->addField(PL_PLUGIN_DATA_FIELD_PHP_SUBMIT, new sbLayoutTextarea($value, $key, '', 'style="width:100%;height:150px;"'));
            	break;

            case 'site_code':
            	if ($categs)
            		break;

				$layout->addField('', new sbLayoutDelim());
				$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_PHP_SITE_HINT.'</div>', true));
            	$layout->addField(PL_PLUGIN_DATA_FIELD_PHP_SITE, new sbLayoutTextarea($value, $key, '', 'style="width:100%;height:150px;"'));
            	break;

            case 'default':
            	if ($type == 'php')
            	{
            		break;
            	}

            	if ($type == 'jscript')
            	{
            		$layout->addField('', new sbLayoutDelim());
            		$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_JSCRIPT_HINT.'</div>', true));
            		$layout->addField(PL_PLUGIN_DATA_FIELD_JSCRIPT, new sbLayoutTextarea($value, $key, '', 'style="width:100%;height:150px;"'));

					break;
				}

            	if ($type == 'date')
                {
                    $fld = new sbLayoutDate(($value != 'current' ? $value : ''), $key);
                    $fld->mDropButton = true;
                    $fld->mHTML = '<div></div>
                        <input type="checkbox" value="1" name="default_chk" id="default_chk" onclick="sbPDESetDateCurrent(this);" style="margin:0px;"'.($value == 'current' ? ' checked="checked"' : '').' /> - '.PL_PLUGIN_DATA_FIELD_CURRENT_DATE.'
                        <script>
                            sbPDESetDateCurrent(sbGetE("default_chk"));
                        </script>';

                    $layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, $fld);
					break;
				}

                if ($type == 'color')
                {
                    $fld = new sbLayoutColor($value, $key);
                    $fld->mDropButton = true;
                    $layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, $fld);

                    break;
                }

                if ($type == 'text' || $type == 'longtext' || $type == 'label')
                {
                    if ($type == 'label')
                        $layout->addField(PL_PLUGIN_DATA_FIELD_VALUE, new sbLayoutTextarea($value, $key, '', 'style="width:100%;height:150px;"'));
                    else
                        $layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutTextarea($value, $key, '', 'style="width:100%;height:150px;"'));

                    break;
                }

                if ($type == 'checkbox')
                {
                    $layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                    break;
                }

                if ($type == 'number')
                {
                    $layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;"'));
                    break;
                }

                $layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutInput('text', $value, $key, '', 'style="width:480px;"'));
                break;

            case 'default_latitude':
            	$fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:100px;"');
            	$fld->mIncrement = '0.0000000000001';

            	$layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT_LATITUDE, $fld);
            	break;

            case 'default_longtitude':
            	$fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:100px;"');
            	$fld->mIncrement = '0.0000000000001';

            	$layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT_LONGTITUDE, $fld);
            	break;

            case 'geocoding_fld':
                if ($categs)
                {
                    $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
                }
                else
                {
                    $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
                }

                $fld = new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_USE_GEOCODING_TITLE_ERROR.'</div>');
                $show_hint = false;
                if($res)
                {
                    $flds = unserialize($res[0][0]);
                    $options = array('-1' => PL_PLUGIN_DATA_FIELD_NOT_USE);
                    $selected = '-1';
                    $checkedLoadAddress = false;
                    if(!empty($flds))
                    {
                        foreach($flds as $item)
                        {
                            if($item['type'] == 'string')
                            {
                                $options['user_f_'.$item['id']] = $item['title'];
                            }

                            if(isset($_GET['id']) && isset($settings['geocoding_fld']) && $settings['geocoding_fld'] == 'user_f_'.$item['id'])
                            {
                                $selected = $settings['geocoding_fld'];
                            }
                            
                            if(isset($settings['load_address']) && $settings['load_address'] == 1)
                            {
                                $checkedLoadAddress = true;
                            }
                        }
                    }

                    if(sizeof($options) > 1)
                    {
                        $fld = new sbLayoutSelect($options, 'geocoding_fld', 'geocoding_fld', 'onchange="checkAddress()"');
                        $fld->mSelOptions = array($selected);
                        $show_hint = true;
                        $fld->mHTML = '<script type="text/javascript">
                            function checkAddress(){
                                var fld = sbGetE("geocoding_fld");
                                var div = sbGetE("load_address");
                                if(fld)
                                {
                                    if(fld.value != -1)
                                    {
                                        div.disabled = false;
                                    }
                                    else
                                    {
                                        div.disabled = true;
                                        div.checked = false;
                                    }
                                }
                            }
                         </script>
                        ';
                        
                        
                    }
                    
                    $dopStr = '';
                    $dopStr .= ($selected != -1)? '' : 'disabled="disabled" ';
                    $dopStr .= ($checkedLoadAddress)? 'checked="checked"' : '';
                    
                    $fld2 = new sbLayoutInput('checkbox', '1', 'load_address', 'load_address', $dopStr);
                
                }
                $layout->addField(PL_PLUGIN_DATA_FIELD_POST_ADDRESS_TITLE_FLD, $fld);
                if($show_hint)
                {
                    $layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_POST_ADDRESS_TITLE_FLD_DESC.'</div>'));
                }
                
                if(isset($fld2))
                {
                    $layout->addField(PL_PLUGIN_DATA_FIELD_GET_ADDRESS, $fld2);
                }
                break;

            case 'html':
                $layout->addField(PL_PLUGIN_DATA_FIELD_HTML, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'char_count':
            	 $fld = new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked" onchange="sbPDEMaxLengthToggle(this);"' : ' onchange="sbPDEMaxLengthToggle(this);"'));
            	 $fld->mHTML = '
                        <script>
                        sbAddEvent(window, "load", sbPDEMaxLengthToggle);
                        </script>';
                $layout->addField(PL_PLUGIN_DATA_FIELD_CHAR_COUNT, $fld);
                break;

            case 'hidden':
                $layout->addField(PL_PLUGIN_DATA_FIELD_HIDDEN, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'show_map':
                $layout->addField(PL_PLUGIN_DATA_FIELD_SHOW_MAP, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'show_titude':
            	$layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_SHOW_TITUDE, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'drop_btn':
                $layout->addField(PL_PLUGIN_DATA_FIELD_DROP_BTN, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'separator':
                $layout->addField(PL_PLUGIN_DATA_FIELD_SEPARATOR, new sbLayoutInput('text', $value, $key, '', 'style="width:200px;"'));
                break;

            case 'sprav_ids':
            	$layout->addField('', new sbLayoutDelim());
                $layout->addField(PL_PLUGIN_DATA_FIELD_SPRAVS, new sbLayoutSprav($value, $key, '', 'style="width:470px;"', true));
                break;

            case 'sprav_id':
                $layout->addField('', new sbLayoutDelim());
                $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_SPRAV_HINT.'</div>', '', '', false));

                $fld = new sbLayoutSprav($value, $key, '', 'style="width:320px;"', true);
                $fld->mMultiSelect = false;
                $layout->addField(PL_PLUGIN_DATA_FIELD_SPRAV, $fld);
                break;

            case 'sprav_title':
            case 'modules_link_title':
                $layout->addField(PL_PLUGIN_DATA_FIELD_SPRAV_TITLE, new sbLayoutInput('text', $value, $key, '', 'style="width:480px;"'));
                break;

            case 'sprav_title_fld':
        		$options = array('s_title' => PL_PLUGIN_DATA_FIELD_SPRAV_FLD_TITLE,
        			's_prop1' => sprintf(PL_PLUGIN_DATA_FIELD_SPRAV_FLD_PROP, '1'),
        			's_prop2' => sprintf(PL_PLUGIN_DATA_FIELD_SPRAV_FLD_PROP, '2'),
        			's_prop3' => sprintf(PL_PLUGIN_DATA_FIELD_SPRAV_FLD_PROP, '3'));

                $fld = new sbLayoutSelect($options, $key);
                if ($value)
                {
                    $fld->mSelOptions[] = $value;
                }

            	$layout->addField(PL_PLUGIN_DATA_FIELD_SPRAV_TITLE_FLD, $fld);
            	break;

            case 'subcategs':
                $layout->addField(PL_PLUGIN_DATA_FIELD_SUBCATEGS, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'modules_subcategs':
                $layout->addField(PL_PLUGIN_DATA_FIELD_SUBCATEGS_MODULES, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'sprav_ajax':
                $layout->addField(PL_PLUGIN_DATA_FIELD_AJAX, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'modules_ajax':
                $layout->addField(PL_PLUGIN_DATA_PLUGINS_FIELD_AJAX, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'file_types':
                $layout->addField(PL_PLUGIN_DATA_FIELD_FILETYPES, new sbLayoutInput('text', $value, $key, '', 'style="width:480px;"'));
                break;

            case 'hint':
                $layout->addField(PL_PLUGIN_DATA_FIELD_HINT, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'show':
            	$f_title = ($type == 'file' ? PL_PLUGIN_DATA_FIELD_SHOW_FILE : PL_PLUGIN_DATA_FIELD_SHOW);
                $layout->addField($f_title, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'time':
                $layout->addField(PL_PLUGIN_DATA_FIELD_TIME, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;

            case 'increment':
                $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_INCREMENT_HINT.'</div>', '', '', false));
                $layout->addField(PL_PLUGIN_DATA_FIELD_INCREMENT, new sbLayoutInput('text', $value, $key, '', 'style="width:50px;"'));
                break;

            case 'ident':
            	if($type == 'select_plugin' || $type == 'radio_plugin' || $type == 'checkbox_plugin' || $type == 'link_plugin')
            		$layout->addField('', new sbLayoutDelim());

                $options = array();
                foreach ($_SESSION['sbPlugins']->mRubrikator as $key2 => $value2)
                {
                	if($key2 != 'pl_pages')
                    	$options[$key2] = $value2['title'];
                }

                $fld = new sbLayoutSelect($options, $key, '', 'onchange="sbPDEChangeIdent(this);"');
                if ($value)
                {
                    $fld->mSelOptions[] = $value;
                    $ident = $value;
                }
                if($type == 'elems_plugin')
                	$layout->addField(PL_PLUGIN_DATA_FIELD_IDENT_ELEMENTS, $fld);
                else
                	$layout->addField(PL_PLUGIN_DATA_FIELD_IDENT, $fld);
                break;

            case 'cat_id':
            case 'modules_cat_id':
                if (!isset($ident))
                {
                	$modules = $_SESSION['sbPlugins']->mRubrikator;
                	unset($modules['pl_pages']);
                    reset($modules);
                    
                    $ident = each($modules);
                    if (!$ident)
                        $ident = '';
                    else
                        $ident = $ident['key'];
                }

                if($key == 'cat_id')
                {
                	$fld = new sbLayoutCategs($ident, '', $value, $key, '');
                }
                elseif($key == 'modules_cat_id')
                {
                	$fld = new sbLayoutCategs($ident, '', $value, $key, '', '', true);
                	$fld->mMultiSelect = ($type == 'link_plugin' ? false : true);
                }
                
                $fld->mHTML = '<br />
                        <script>
                            var sbCatIdFieldId = "'.$key.'";
                        </script>';
                if($key == 'cat_id')
                {
                	$layout->addField(PL_PLUGIN_DATA_FIELD_CAT_ID, $fld);
                    $layout->addField(PL_PLUGIN_DATA_FIELD_MULTISELECT, new sbLayoutInput('checkbox','1', 'multiselect', '', ($multiselect == 1 ? 'checked="checked"' : '')));
                }
                elseif($key == 'modules_cat_id')
                {
                	$layout->addField(PL_PLUGIN_DATA_FIELD_CAT_ID_MODULES, $fld);
                }
                
                $layout->addField('', new sbLayoutDelim());

                break;

            case 'modules_title_fld':
        		$fld = new sbLayoutSelect(array('0' => '---'), $key);

        		if ($value)
                {
                    $fld->mSelOptions[] = $value;
                }
            	$layout->addField(PL_PLUGIN_DATA_FIELD_SPRAV_TITLE_MODULES_FLD, $fld);
            	$fld->mHTML = '<script>sbAddEvent(sbGetE("select_plugin"), "load", sbPDEChangeIdentPluginsTitle("'.$value.'"));</script>';
            	break;

            case 'table_rows':
         		$layout->addField('', new sbLayoutDelim());
         		$fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;" onchange="sbPDEChangeTable()"');
         		$fld -> mMinValue = '0';
                $layout->addField(PL_PLUGIN_DATA_FIELD_TABLE_ROWS, $fld);
                break;
            case 'table_cell_caption':
                $layout->addField(PL_PLUGIN_DATA_FIELD_TABLE_CAP, new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : '')));
                break;
            case 'table_cell_type':
          		 $options = array('string' => PL_PLUGIN_DATA_FIELD_TABLE_CELL_TYPE_STRING,
        			'num' => sprintf(PL_PLUGIN_DATA_FIELD_TABLE_CELL_TYPE_NUM, '2'),
        			'date' => sprintf(PL_PLUGIN_DATA_FIELD_TABLE_CELL_TYPE_DATE, '3'),
        			'check' => sprintf(PL_PLUGIN_DATA_FIELD_TABLE_CELL_TYPE_CHECK, '4'),
          		 	'color' => sprintf(PL_PLUGIN_DATA_FIELD_TABLE_CELL_TYPE_COLOR, '5')
          		 );

                $fld = new sbLayoutSelect($options, $key);
                $fld -> mHTML = '<script>sbAddEvent(window, "load", sbPDETypeChengeInit);</script>';
                if ($value)
                {
                    $fld->mSelOptions[] = $value;
                }

            	$layout->addField(PL_PLUGIN_DATA_FIELD_TABLE_CELL_TYPE, $fld);
            	break;
            case 'table_cell_settings':
         		$layout->addField('', new sbLayoutDelim());
         		//Подгружаю скрытые настройки для типов полей
            	if(!isset($value['string_min']))
            		$value['string_min'] = '';
            	if(!isset($value['string_max']))
            		$value['string_max'] = '';
            	if(!isset($value['num_min']))
            		$value['num_min'] = '';
            	if(!isset($value['num_max']))
            		$value['num_max'] = '';
            	if(!isset($value['num_default']))
            		$value['num_default'] = '';
            	if(!isset($value['num_increment']))
            		$value['num_increment'] = '';
            	if(!isset($value['date_default']))
            		$value['date_default'] = '';
            	if(!isset($value['date_default_now']))
            		$value['date_default_now'] = '';
            	if(!isset($value['date_allow_reset']))
            		$value['date_allow_reset'] = '';
            	if(!isset($value['date_show_time']))
            		$value['date_show_time'] = '';
            	if(!isset($value['check_default']))
            		$value['check_default'] = '';
            	if(!isset($value['color_default']))
            		$value['color_default'] = '';
            	if(!isset($value['color_allow_reset']))
            		$value['color_allow_reset'] = '';
            	//Для строк
            	$layout->addField(PL_PLUGIN_DATA_FIELD_WIDTH, new sbLayoutInput('text', $value['string_width'], $key.'[string_width]', 'string_width_'.$key, 'style="width:65px;"'),'','','id="table_cell_string_width"');
                $fld = new sbLayoutInput('text', $value['string_min'], $key.'[string_min]', 'spin_string_min_'.$key, 'style="width:50px;"');
            	$fld -> mMinValue = '0';
         		$layout->addField(PL_PLUGIN_DATA_FIELD_MIN_LENGTH, $fld,'','','id="table_cell_string_min"');
            	$fld = new sbLayoutInput('text', $value['string_max'], $key.'[string_max]', 'spin_string_max'.$key, 'style="width:50px;"');
         		$fld -> mMinValue = '1';
         		$layout->addField(PL_PLUGIN_DATA_FIELD_MAX_LENGTH, $fld,'','','id="table_cell_string_max"');
         		//Для чисел
            	$layout->addField(PL_PLUGIN_DATA_FIELD_WIDTH, new sbLayoutInput('text', $value['num_width'], $key.'[num_width]', 'mun_width_'.$key, 'style="width:65px;"'),'','','id="table_cell_mum_width"');
                $layout->addField(PL_PLUGIN_DATA_FIELD_MIN_VALUE, new sbLayoutInput('text', $value['num_min'], $key.'[num_min]', 'spin_num_min'.$key, 'style="width:50px;"'),'','','id="table_cell_num_min" style="display:none;"');
            	$layout->addField(PL_PLUGIN_DATA_FIELD_MAX_VALUE, new sbLayoutInput('text', $value['num_max'], $key.'[num_max]', 'spin_num_max'.$key, 'style="width:50px;"'),'','','id="table_cell_num_max" style="display:none;"');
            	$layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutInput('text', $value['num_default'], $key.'[num_default]', 'spin_num_default'.$key, 'style="width:50px;"'),'','','id="table_cell_num_def" style="display:none;"');
            	$layout->addField('', new sbLayoutLabel('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_INCREMENT_HINT.'</div>', '', '', false),'','','id="table_cell_num_inc_hint"');
            	$layout->addField(PL_PLUGIN_DATA_FIELD_INCREMENT, new sbLayoutInput('text', $value['num_increment'], $key.'[num_increment]', '', 'style="width:50px;"'),'','','id="table_cell_num_inc" style="display:none;"');
                //Для цвета
            	$layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutColor($value['color_default'], $key.'[color_default]', $key.'_color_default', '', false),'','','id="table_cell_color_def" style="display:none;"');
            	$layout->addField(PL_PLUGIN_DATA_FIELD_DROP_BTN, new sbLayoutInput('checkbox', '1', $key.'[color_allow_reset]', $key.'_color_allow_reset', ($value['color_allow_reset'] == 1 ? 'checked="checked"' : '')),'','','id="table_cell_color_allow" style="display:none;"');
            	//Флажок
            	$layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT, new sbLayoutInput('checkbox', '1', $key.'[check_default]', $key.'_check_default', ($value['check_default'] == 1 ? 'checked="checked"' : '')),'','','id="table_cell_check_def" style="display:none;"');
            	//Дата
            	$fld = new sbLayoutDate($value['date_default'], $key.'[date_default]', $key.'_date_default', '', false);
            	$fld->mDropButton = true;
                    $fld->mHTML = '<div></div>
                        <input type="checkbox" value="1" name="'.$key.'[date_default_now]" id="'.$key.'_date_default_now" onclick="sbPDESetTableDateCurrent(this,\''.$key.'\');" style="margin:0px;"'.($value['date_default_now'] == '1' ? ' checked="checked"' : '').' /> - '.PL_PLUGIN_DATA_FIELD_CURRENT_DATE.'
                        <script>
                           sbPDESetTableDateCurrent(sbGetE("'.$key.'_date_default_now"),"'.$key.'");
                        </script>';

            	$layout->addField(PL_PLUGIN_DATA_FIELD_DEFAULT,$fld,'','','id="table_cell_date_def" style="display:none;"');
            	$layout->addField(PL_PLUGIN_DATA_FIELD_DROP_BTN, new sbLayoutInput('checkbox', '1', $key.'[date_allow_reset]', $key.'_date_allow_reset', ($value['date_allow_reset'] == 1 ? 'checked="checked"' : '')),'','','id="table_cell_date_allow" style="display:none;"');
            	$layout->addField(PL_PLUGIN_DATA_FIELD_TIME, new sbLayoutInput('checkbox', '1', $key.'[date_show_time]', $key.'_date_show_time', ($value['date_show_time'] == 1 ? 'checked="checked"' : '')),'','','id="table_cell_date_show_time" style="display:none;"');

               break;
            case 'table_colls':
                $fld = new sbLayoutInput('text', $value, $key, 'spin_'.$key, 'style="width:50px;" onchange="sbPDEChangeTable()"');
         		$fld -> mMinValue = '0';
         		$layout->addField(PL_PLUGIN_DATA_FIELD_TABLE_COLLS, $fld);
                $layout->addField('', new sbLayoutDelim(),'','','id="last_tr" style="display:none;"');
                break;
            case 'table_rows_name':
         		$layout->addField('', new sbLayoutDelim(), '', '', 'id="rows_delim"');
         		$i = 1;
         		foreach($value as $value2)
         		{
         			$layout->addField(PL_PLUGIN_DATA_FIELD_TABLE_ROWS_NAMES.$i, new sbLayoutInput('text', $value2, $key.'[]', '', 'style="width:480px;"'),'','','class="custom_tr"');
         			$i++;
         		}
         		if(count($value) > 0)
         			$layout->addField('', new sbLayoutDelim(),'','','id="custom_delim"');
         		break;
            case 'table_colls_name':
         		$i = 1;
         		foreach($value as $value2)
         		{
         			$layout->addField(PL_PLUGIN_DATA_FIELD_TABLE_COLLS_NAMES.$i, new sbLayoutInput('text', $value2, $key.'[]', '', 'style="width:480px;"'),'','','class="custom_tr"');
         			$i++;
				}
				break;
            case 'editable':
                $fld = new sbLayoutInput('checkbox', '1', $key, $key, ($value == 1)? 'checked="checked"' : '');
                $layout->addField(PL_PLUGIN_DATA_FIELD_SPRAV_EDITABLE_TITLE, $fld);
                break;
            case 'save_status':
                $fld = new sbLayoutInput('checkbox', '1', $key, '', ($value == 1 ? 'checked="checked"' : ''));
                $layout->addField(PL_PLUGIN_DATA_FIELD_SAVE_STATUS_TITLE, $fld);
                break;
		}
	}

	if($type == 'file')
	{
		$javascript_str .= 'sbAddEvent(window, "load", function(){sbPDEChangeLocal(true)});';

		$layout->addField('', new sbLayoutDelim());
		$layout->addField(PL_PLUGIN_DATA_FIELD_LOCAL_FILE, new sbLayoutInput('checkbox', '1', 'local', 'local', ($settings['local'] == 1 ? ' checked="checked"' : '').' onclick="sbPDEChangeLocal(true);"'));

		$layout->addField(PL_PLUGIN_DATA_FIELD_PATH, new sbLayoutFolder($settings['path'] == '' ? '/upload/'.$_GET['ident'] : $settings['path'], 'path', '', 'style="width:440px;"', true));
	}

	if ($type == 'image')
	{
        $defaultPath = isset($_GET['id']) ? '' : '/upload/'.$_GET['ident'];
		$javascript_str .= 'sbAddEvent(window, "load", sbPDEChangeLocal);';

        $layout->addField(PL_PLUGIN_DATA_FIELD_PATH, new sbLayoutFolder($settings['path'] == '' ? $defaultPath : $settings['path'], 'path', '', 'style="width:440px;"', true));
        $layout->addField('', new sbLayoutDelim());

        $layout->addField(PL_PLUGIN_DATA_FIELD_RESIZE, new sbLayoutInput('checkbox', '1', 'resize', '', 'onclick="sbPDEChangeResize(this);"'.($settings['resize'] == 1 ? ' checked="checked"' : '')));
        $layout->addField(PL_PLUGIN_DATA_FIELD_IMG_WIDTH, new sbLayoutInput('text', $settings['img_width'], 'img_width', 'spin_img_width', 'style="width:50px;" '.($settings['resize'] == 1 ? '' : 'disabled="dizabled"')));
        $layout->addField(PL_PLUGIN_DATA_FIELD_IMG_HEIGHT, new sbLayoutInput('text', $settings['img_height'], 'img_height', 'spin_img_height', 'style="width:50px;" '.($settings['resize'] == 1 ? '' : 'disabled="dizabled"')));
        $layout->addField(PL_PLUGIN_DATA_FIELD_IMG_QUALITY, new sbLayoutInput('text', $settings['img_quality'], 'img_quality', 'spin_img_quality', 'style="width:50px;" '.($settings['resize'] == 1 ? '' : 'disabled="dizabled"')));
        
        //Миниатюра на основе изображения
        $flds = '';
        if(isset($_GET['id']))
        {
            $flds = $pd_fields;
        }
        else
        {
            $res = sql_param_query('SELECT '.($categs? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
            if ($res && $res[0][0] != '')
            {
                $flds = unserialize($res[0][0]);
            }
        }
        
        if(!empty($flds))
        {
            $fields = array('-1' => PL_PLUGIN_DATA_FIELD_NOT_USE);
            foreach($flds as $item)
            {
                if($item['type'] != 'image' || (isset($_GET['id']) && $_GET['id'] == $item['id']))
                {
                    continue;
                }
                $fields['user_f_'.$item['id']] = $item['title'];
            }
            if(count($fields) > 1)
            {
                $fld = new sbLayoutSelect($fields, 'parent_field');
                $fld->mSelOptions = isset($settings['parent_field'])? array($settings['parent_field']) : array();
                $layout->addField(PL_PLUGIN_DATA_FIELD_PARENT_IMG, $fld);
            }
        }
        $layout->addField('', new sbLayoutDelim());

        $layout->addField(PL_PLUGIN_DATA_FIELD_WATERMARK, new sbLayoutInput('checkbox', '1', 'watermark', '', 'onclick="sbPDEChangeWatermark(this);"'.($settings['watermark'] == 1 ? ' checked="checked"' : '')));
        $fld = new sbLayoutSelect($GLOBALS['sb_watermark_positions'], 'watermark_position');
        $fld->mSelOptions = array($settings['watermark_position']);
        $layout->addField(PL_PLUGIN_DATA_FIELD_WATERMARK_POSITION, $fld);
        $layout->addField(PL_PLUGIN_DATA_FIELD_WATERMARK_OPACITY, new sbLayoutInput('text', $settings['watermark_opacity'], 'watermark_opacity', 'spin_watermark_opacity', 'style="width:50px;"'));
        $layout->addField(PL_PLUGIN_DATA_FIELD_WATERMARK_MARGIN, new sbLayoutInput('text', $settings['watermark_margin'], 'watermark_margin', 'spin_watermark_margin', 'style="width:50px;"'));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutFile($settings['watermark_file'], 'watermark_file', '', 'style="width:445px;"');
        $fld->mFileTypes = 'jpg gif';
        $layout->addField(PL_PLUGIN_DATA_FIELD_WATERMARK_FILE, $fld);
        $layout->addField('', new sbLayoutDelim());

        $layout->addField(PL_PLUGIN_DATA_FIELD_COPYRIGHT, new sbLayoutInput('text', $settings['copyright'], 'copyright', '', 'style="width:480px;"'));
        $fld = new sbLayoutColor($settings['copyright_color'], 'copyright_color', '', 'disabled="disabled"');
        $fld->mDropButton = true;
        $layout->addField(PL_PLUGIN_DATA_FIELD_COPYRIGHT_COLOR, $fld);
        $fld = new sbLayoutSelect($GLOBALS['sb_watermark_fonts'], 'copyright_font');
        $fld->mSelOptions = array($settings['copyright_font']);
        $layout->addField(PL_PLUGIN_DATA_FIELD_COPYRIGHT_FONT, $fld);
        $fld = new sbLayoutSelect($GLOBALS['sb_watermark_sizes'], 'copyright_size');
        $fld->mSelOptions = array($settings['copyright_size']);
        $layout->addField(PL_PLUGIN_DATA_FIELD_COPYRIGHT_SIZE, $fld);
    }
    if ($type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'jscript')
    {
        $layout->addTab(PL_PLUGIN_DATA_EDIT_TAB2);
        $layout->addHeader(PL_PLUGIN_DATA_EDIT_TITLE);
    }

    $show_delim = false;
    if (!$categs && $_GET['ident'] != 'pl_menu' && $type != 'password' && $type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'text' && $type != 'longtext' && $type != 'jscript' && $type != 'table')
    {
        $layout->addField(PL_PLUGIN_DATA_FIELD_INFO, new sbLayoutInput('checkbox', '1', 'info', '', ($info == 1 ? ' checked="checked"' : '')));
        $show_delim = true;
    }

	if (!$categs && $_GET['ident'] != 'pl_menu' && $type == 'checkbox')
    {
        $layout->addField(PL_PLUGIN_DATA_FIELD_ACTIVE, new sbLayoutInput('checkbox', '1', 'active', '', ($active == 1 ? ' checked="checked"' : '')));
        $show_delim = true;
    }

    if (!$categs && $_GET['ident'] != 'pl_menu' && $type != 'password' && $type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'text' && $type != 'longtext' && $type != 'jscript' && $type != 'multiselect_sprav' && $type != 'checkbox_sprav' && $type != 'select_sprav' && $type != 'radio_sprav' && $type != 'link_sprav' && $type != 'categs' && $type != 'google_coords' && $type != 'yandex_coords' && $type != 'table' && $type != 'select_plugin' && $type != 'radio_plugin' && $type != 'checkbox_plugin' && $type != 'multiselect_plugin')
    {
        $layout->addField(PL_PLUGIN_DATA_FIELD_SORT, new sbLayoutInput('checkbox', '1', 'sort', '', ($sort == 1 ? ' checked="checked"' : '')));
        $show_delim = true;
    }

    if (!$categs && $_GET['ident'] != 'pl_menu' && $type != 'password' && $type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'jscript' && $type != 'google_coords' && $type != 'yandex_coords' && $type != 'table')
    {
        $layout->addField(PL_PLUGIN_DATA_FIELD_FILTER, new sbLayoutInput('checkbox', '1', 'filter', '', ($filter == 1 ? ' checked="checked"' : '')));
        $show_delim = true;
    }

    if ($type != 'tab' && $type != 'categs' && $type != 'jscript' && $type != 'php' && $type != 'multiselect_sprav' && $type != 'hr' && $type != 'multiselect_plugin')
    {
    	$layout->addField(PL_PLUGIN_DATA_FIELD_ATTRIBS_TITLE, new sbLayoutInput('text', $attribs, 'attribs', '', 'style="width:480px;"'));
    	$show_delim = true;
    }

    if ($type != 'label' && $type != 'tab' && $type != 'hr' && $type != 'checkbox' && $type != 'jscript' && $type != 'php')
    {
        if ($show_delim)
        {
            $layout->addField('', new sbLayoutDelim());
        }

        $layout->addField(PL_PLUGIN_DATA_FIELD_MANDATORY, new sbLayoutInput('checkbox', '1', 'mandatory', '', 'onclick="sbPDEChangeMandatory(this);"'.($mandatory == 1 ? ' checked="checked"' : '')));

        if ($type == 'number' || $type == 'date' || $type == 'string' || $type == 'color' || $type == 'text' || $type == 'longtext' ||
            $type == 'image' || $type == 'link' || $type == 'file')
        {
            $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.PL_PLUGIN_DATA_FIELD_MANDATORY_LABEL.'</div>', '', '', false));
            $fld = new sbLayoutTextarea($mandatory_val, 'mandatory_val', '', 'style="width:100%"'.($mandatory != 1 ? ' disabled="disabled"' : ''));
            $fld->mTags[] = '{VALUE}';
            $fld->mValues[] = PL_PLUGIN_DATA_FIELD_MANDATORY_SIGN_TAG;

            $layout->addField(PL_PLUGIN_DATA_FIELD_MANDATORY_SIGN, $fld);
        }

        $layout->addField(PL_PLUGIN_DATA_FIELD_MANDATORY_ERROR, new sbLayoutInput('text', $mandatory_err, 'mandatory_err', '', 'style="width:480px;"'.($mandatory != 1 ? ' disabled="disabled"' : '')));
    }

	$layout->addButton('submit', KERNEL_SAVE);
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');

    echo $javascript_str.'</script>';

    $layout->addTab(PL_PLUGIN_DATA_EDIT_TAB3);
    $layout->addHeader(PL_PLUGIN_DATA_EDIT_TITLE);

    $layout->addField(PL_PLUGIN_DATA_FIELD_RIGHT_SET, new sbLayoutInput('checkbox', '1', 'rights_set', '', 'onclick="sbPDEChangeType(this)" '.($rights_set == 1 ? ' checked="checked"' : '')));

    $layout->addField('', new sbLayoutDelim());

    $ident = $_GET['ident'];
    $view_now_users_text = '';
    if($rights_view_list != '')
    {
        $view_now_users = explode('^',$rights_view_list);
        foreach($view_now_users as $value3)
        {
        	$id = sb_substr($value3, 1);
        	if($value3[0] == 'u')
        	{
        		$res3 = sql_query('SELECT u_login from sb_users where u_id = ?d', $id);
        	}
        	elseif($value3[0] == 'g')
        	{
        		$res3 = sql_query('SELECT cat_title from sb_categs where cat_id = ?d AND cat_ident = ?s AND cat_level = 1', $id, 'pl_users');
        	}
        	if($res3 && $res3[0][0] != '')
			{
				$view_now_users_text .= $res3[0][0].', ';
			}
        }
        $view_now_users_text = sb_substr($view_now_users_text, 0, -2);
    }

    $edit_now_users_text = '';
    if($rights_edit_list != '')
    {
        $edit_now_users = explode('^',$rights_edit_list);
        foreach($edit_now_users as $value3)
        {
        	$id = sb_substr($value3, 1);
        	if($value3[0] == 'u')
        	{
        		$res3 = sql_query('SELECT u_login from sb_users where u_id = ?d', $id);
        	}
        	elseif($value3[0] == 'g')
        	{
        		$res3 = sql_query('SELECT cat_title from sb_categs where cat_id = ?d AND cat_ident = ?s AND cat_level = 1', $id, 'pl_users');
        	}
        	if($res3 && $res3[0][0] != '')
			{
				$edit_now_users_text .= $res3[0][0].', ';
			}
        }
        $edit_now_users_text = sb_substr($edit_now_users_text, 0, -2);
    }

    $layout->addField('', new sbLayoutInput('hidden', $rights_view_list, 'rights_view_list_ids'));
    $layout->addField(PL_PLUGIN_DATA_FIELD_RIGHT_USERS_VIEW, new sbLayoutHTML('
                     <input id="rights_view_list" name="rights_view_list" readonly="readonly" '.($rights_set != 1 ? 'disabled' : '').' style="width:75%;" value="'.$view_now_users_text.'">&nbsp;&nbsp;
                     <img class="button" src="'.SB_CMS_IMG_URL.'/users.png" width="20" height="20" align="absmiddle" id="rights_view_list_btn" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="sbPDEBrowseGroups(\'rights_view_list_ids\',\''.$ident.'\');" title="'.KERNEL_BROWSE.'" />&nbsp;&nbsp;
                     <img class="button" src="'.SB_CMS_IMG_URL.'/users_drop.png" width="20" height="20" align="absmiddle" id="rights_view_list_btn_drop" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="sbPDEDropGroups(\'rights_view_list\',\''.$ident.'\');" title="'.KERNEL_CLEAR.'" />
                     '));

    if ($type != 'tab' && $type != 'label')
    {
        $layout->addField('', new sbLayoutInput('hidden', $rights_edit_list, 'rights_edit_list_ids'));
        $layout->addField(PL_PLUGIN_DATA_FIELD_RIGHT_USERS_EDIT, new sbLayoutHTML('
                     <input id="rights_edit_list" name="rights_edit_list" readonly="readonly" '.($rights_set != 1 ? 'disabled' : '').' style="width:75%;" value="'.$edit_now_users_text.'">&nbsp;&nbsp;
                     <img class="button" src="'.SB_CMS_IMG_URL.'/users.png" width="20" height="20" align="absmiddle" id="rights_edit_list_btn" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="sbPDEBrowseGroups(\'rights_edit_list_ids\',\''.$ident.'\');" title="'.KERNEL_BROWSE.'" />&nbsp;&nbsp;
                     <img class="button" src="'.SB_CMS_IMG_URL.'/users_drop.png" width="20" height="20" align="absmiddle" id="rights_edit_list_btn_drop" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="sbPDEDropGroups(\'rights_edit_list\',\''.$ident.'\');" title="'.KERNEL_CLEAR.'" />
                     '));
    }

    $layout->show();
}

function fPluginData_Edit_Submit()
{
	$plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$_GET['ident']]) || !isset($plugins[$_GET['ident']]['table']) || $plugins[$_GET['ident']]['table'] == '')
    {
        sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR, true, 'warning');
        return;
    }

    $plugin_title = $plugins[$_GET['ident']]['title'];
    $plugin_table = $plugins[$_GET['ident']]['table'];
    $type_info = false;

    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
    {
        foreach ($value as $key2 => $value2)
        {
            if ($key2 == $_GET['type'])
            {
                $type_info = $value2;
                break;
            }
        }
    }


    if (!$type_info)
    {
        sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR2, true, 'warning');
        return;
    }

	$categs = isset($_GET['categs']) && $_GET['categs'] == 1;

	if (isset($type_info['settings']))
		$settings = $type_info['settings'];
	else
		$settings = array();

    foreach($settings as $key => $value)
    {
        if ($key == 'default')
        {
            if ($_GET['type'] == 'date')
            {
                if (isset($_POST['default_chk']))
                {
                    $settings[$key] = 'current';
                }
                else
                {
                    $settings[$key] = $_POST['default'] != '' ? sb_datetoint($_POST['default']) : '';
                }
                continue;
            }
            elseif($_GET['type'] == 'checkbox')
            {
                $settings[$key] = isset($_POST['default']) ? 1 : 0;
                continue;
            }
        }

		if ($key == 'html' || $key == 'hidden' || $key == 'drop_btn' || $key == 'subcategs'
        || $key == 'hint' || $key == 'show' || $key == 'local' || $key == 'resize' || $key == 'time'
        || $key == 'show_titude' || $key == 'show_map' || $key == 'modules_subcategs' || $key == 'editable')
		{
			$settings[$key] = isset($_POST[$key]) ? 1 : 0;
        }
		elseif ($key == 'increment')
		{
			$settings[$key] = isset($_POST[$key]) ? floatval($_POST[$key]) : 0;
		}
		elseif (isset($_POST[$key]))
		{
			$settings[$key] = $_POST[$key];
		}
	}
    
    if(isset($_POST['load_address']) && $_POST['load_address'] == 1)
    {
        $settings['load_address'] = 1;
    }

    //Проверка количества полей типа "файл" и "image", и максимального объема VARCHAR
    $user_file_count = 0;
    $_POST['local'] = isset($_POST['local'])? $_POST['local'] : 0;
    if(($_GET['type'] == 'file' || $_GET['type'] == 'image') && $_POST['local'] == 1)
    {
        $res = sql_param_query('SELECT '.($_GET['categs'] == 1 ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?s', $_GET['ident']);
        if($res)
        {
            $res = unserialize($res[0][0]);
            foreach($res as $row)
            {
                if(($row['type'] == 'file' || $row['type'] == 'image')&& isset($row['settings']['local']) && $row['settings']['local'] == 1)
                {
                    $user_file_count++;
                }
            }
        }

        
        if(ini_get('max_file_uploads') !== false && ini_get('max_file_uploads') !== '' && $user_file_count >= intval(ini_get('max_file_uploads')))
        {
            sb_show_message(PL_PLUGIN_DATA_FILE_LIMIT, false, 'warning');
            fPluginData_Edit();
            return;
        }
    }

    if((!isset($_GET['id']) || $_GET['id'] == '') && !$categs && ($max_length = fPluginData_Get_MaxVarchar($_GET['type'])) !== false)
    {
        $res = sql_param_query('SHOW CREATE TABLE '.$plugins[$_GET['ident']]['table']);
        $matches = array();
        preg_match_all('/varchar\s*\(\d+\)/i'.SB_PREG_MOD, $res[0][1], $matches);

        $factor = SB_PREG_MOD == 'u' ? 4 : 1;
        if (isset($matches[0]) && !empty($matches[0]))
        {
            //Новое поле
            $bytes = isset($_POST['max_length'])? $_POST['max_length'] * $factor : $max_length * $factor;
            $fields = preg_replace('/varchar\s*\((\d+)\)/i'.SB_PREG_MOD, '$1', $matches[0]);
            $bytes += array_sum($fields)*$factor;

            if($bytes > 65534-count($fields))
            {
                sb_show_message(PL_PLUGIN_DATA_STRING_LIMIT, false, 'warning');
                $_POST['settings'] = $settings;
                fPluginData_Edit();
                return;
            }
        }
    }

	$title = isset($_POST['el_name']) ? strip_tags($_POST['el_name']) : '';
    $info = isset($_POST['info']) ? 1 : 0;
    $sort = isset($_POST['sort']) ? 1 : 0;
    $filter = isset($_POST['filter']) ? 1 : 0;
    $active = isset($_POST['active']) ? 1 : 0;
    $mandatory = isset($_POST['mandatory']) ? 1 : 0;
    $attribs = isset($_POST['attribs']) ? $_POST['attribs'] : '';
    $rights_set = isset($_POST['rights_set']) ? 1 : 0;
    $rights_edit_list = isset($_POST['rights_edit_list_ids']) ? $_POST['rights_edit_list_ids'] : '';
    $rights_view_list = isset($_POST['rights_view_list_ids']) ? $_POST['rights_view_list_ids'] : '';
    $multiselect = isset($_POST['multiselect']) ? $_POST['multiselect'] : '';
    $parent_field = (isset($_POST['parent_field']) && $_POST['parent_field'] != -1)? $_POST['parent_field'] : '';

    if ($mandatory == 1)
    {
        $mandatory_val = isset($_POST['mandatory_val']) ? $_POST['mandatory_val'] : 'if (\'{VALUE}\' == \'\') $error = true;';
        $mandatory_err = isset($_POST['mandatory_err']) && $_POST['mandatory_err'] != '' ? strip_tags($_POST['mandatory_err']) : sprintf(PL_PLUGIN_DATA_FIELD_MANDATORY_ERROR_DEF, $title);
    }
    else
    {
        $mandatory_val = 'if (\'{VALUE}\' == \'\') $error = true;';
        $mandatory_err = '';
    }

    $tag = preg_replace('/[^a-zA-Z'.$GLOBALS['sb_reg_upper_interval'].$GLOBALS['sb_reg_lower_interval'].'0-9_]+/'.SB_PREG_MOD, '_', $title);
    $tag = sb_strtolat($tag);
    $tag = sb_strtoupper($tag);

    if ($categs)
    {
        $res = sql_param_query('SELECT pd_categs, pd_increment FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    }
    else
    {
        $res = sql_param_query('SELECT pd_fields, pd_increment FROM sb_plugins_data WHERE pd_plugin_ident=?', $_GET['ident']);
    }

    if (!isset($_GET['id']) || $_GET['id'] == '')
    {
        // добавляем новое поле
        $num = 0;
        $id = -1;
        if ($res)
        {
            list($pd_fields, $pd_increment) = $res[0];
            if ($pd_fields != '')
            {
                $pd_fields = unserialize($pd_fields);
                $num = count($pd_fields);
            }
            else
            {
                $pd_fields = array();
            }

            $id = $pd_increment + 1;
        }
        else
        {
            $id = 1;
            $pd_fields = array();
        }

        $tag .= '_'.$id;

        $pd_fields[$num] = array();
        $pd_fields[$num]['id'] = $id;
        $pd_fields[$num]['type'] = $_GET['type'];
        $pd_fields[$num]['tag'] = $tag;
    }
    else
    {
        // редактирование существующего поля
        if ($res)
        {
            $pd_fields = unserialize($res[0][0]);
            //проверяем суммарный объем данных для полей string
            if(!$categs && ($max_length = fPluginData_Get_MaxVarchar($_GET['type'])) !== false)
            {
                $factor = SB_PREG_MOD == 'u' ? 4 : 1;
                $bytes = 0;
                foreach($pd_fields as $field)
                {
                    if($field['id'] == $_GET['id'])
                    {
                        if(isset($field['settings']['max_length']))
                        {
                            $bytes -= $field['settings']['max_length'] * $factor;
                        }
                    }
                }
                $res = sql_param_query('SHOW CREATE TABLE ' . $plugins[$_GET['ident']]['table']);
                $matches = array();
                preg_match_all('/varchar\s*\(\d+\)/i' . SB_PREG_MOD, $res[0][1], $matches);

                if (isset($matches[0]) && !empty($matches[0]))
                {
                    $fields = preg_replace('/varchar\s*\((\d+)\)/i' . SB_PREG_MOD, '$1', $matches[0]);
                    $bytes += array_sum($fields) * $factor;
                    $bytes += isset($_POST['max_length'])? $_POST['max_length'] * $factor : 0;

                    if ($bytes > 65535 - count($fields) * 2)
                    {
                        sb_show_message(PL_PLUGIN_DATA_STRING_LIMIT, false, 'warning');
                        $_POST['settings'] = $settings;
                        fPluginData_Edit();
                        return;
                    }
                }
            }
        }
        else
        {
            sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR, true, 'warning');
            return;
        }

        $num = -1;
        foreach ($pd_fields as $key => $value)
        {
            if ($value['id'] == $_GET['id'] && $value['type'] == $_GET['type'])
            {
                $num = $key;
                break;
            }
        }

        if ($num == -1)
        {
            sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR, true, 'warning');
            return;
        }

        $id = intval($_GET['id']);
        $old_settings = $pd_fields[$num]['settings'];
        $old_title = $pd_fields[$num]['title'];
    }

    $pd_fields[$num]['sql'] = (isset($type_info['sql']) && $type_info['sql'] != '') ? 1 : 0;
    $pd_fields[$num]['title'] = $title;
    $pd_fields[$num]['info'] = $info;
    $pd_fields[$num]['sort'] = $sort;
    $pd_fields[$num]['filter'] = $filter;
    $pd_fields[$num]['active'] = $active;
    $pd_fields[$num]['mandatory'] = $mandatory;
    $pd_fields[$num]['mandatory_val'] = $mandatory_val;
    $pd_fields[$num]['mandatory_err'] = $mandatory_err;
    
    if($parent_field != '')
    {
        $settings['parent_field'] = $parent_field;
    }
    
    $pd_fields[$num]['settings'] = $settings;
    $pd_fields[$num]['attribs'] = $attribs;
    $pd_fields[$num]['rights_set'] = $rights_set;
    $pd_fields[$num]['rights_view_list'] = $rights_view_list;
    $pd_fields[$num]['rights_edit_list'] = $rights_edit_list;
    $pd_fields[$num]['multiselect'] = $multiselect;
    

    // меняем тип поля для множественного выбора
    if($multiselect && isset($type_info['sql']) && $type_info['sql'] != '')
    {
        $type_info['sql'] = sb_str_replace('INT', 'VARCHAR(255)', $type_info['sql']);
    }

    $res_alter = true;
    if (!isset($_GET['id']) || $_GET['id'] == '')
    {
        if (!$categs)
        {
            // если добавляется поле для элемента, то при необходимости создаем поле в таблице элементов
            if (isset($type_info['sql']) && $type_info['sql'] != '')
            {
	            $sql = 'ALTER TABLE `'.$plugin_table.'` ADD `user_f_'.$id.'` '.$type_info['sql'];
                if (isset($settings['max_length']) && $_GET['type'] != 'text' && $_GET['type'] != 'longtext')
                {
                    $res_alter = sql_param_query($sql, $settings['max_length'], $title);
                }
                else
                {
                    $res_alter = sql_param_query($sql, $title);
                }
                // если нужно - создаем индекс
                if (isset($type_info['sql_index']) && $type_info['sql_index'] == 1) {
                    $res_index = sql_param_query('CREATE INDEX user_f_'.$id.' ON `'.$plugin_table.'` (user_f_'.$id.')');
                }
            }
        }
    }
    elseif (!$categs)
    {
        if (isset($type_info['sql']) && $type_info['sql'] != '')
        {
        	if (isset($settings['max_length']) && $_GET['type'] != 'text' && $_GET['type'] != 'longtext' && ($settings['max_length'] != $old_settings['max_length']  || $title != $old_title))
        	{
                $res_alter = sql_param_query('ALTER TABLE `'.$plugin_table.'` CHANGE `user_f_'.$id.'` `user_f_'.$id.'` '.$type_info['sql'], $settings['max_length'], $title);
        	}
        	elseif ($title != $old_title)
        	{
        		$res_alter = sql_param_query('ALTER TABLE `'.$plugin_table.'` CHANGE `user_f_'.$id.'` `user_f_'.$id.'` '.$type_info['sql'], $title);
        	}
            // Если нет индекса - добавляем
            if (isset($type_info['sql_index']) && $type_info['sql_index'] == 1) {
                $res_index = sql_param_query('SHOW INDEX FROM `'.$plugin_table.'` WHERE column_name = "user_f_'.$id.'"');
                if (!$res_index) {
                    $res_index = sql_param_query('CREATE INDEX user_f_'.$id.' ON `'.$plugin_table.'` (user_f_'.$id.')');
                }
            }
        }
    }

    if (!$res_alter)
    {
        sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR3, true, 'warning');
        return;
    }

    if (!isset($_GET['id']) || $_GET['id'] == '')
    {
        if ($res)
        {
            if ($categs)
                $result = sql_param_query('UPDATE sb_plugins_data SET pd_categs=?, pd_increment=?d WHERE pd_plugin_ident=?', serialize($pd_fields), $id, $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_CATEG_OK, $title, $plugin_title) : ''));
            else
                $result = sql_param_query('UPDATE sb_plugins_data SET pd_fields=?, pd_increment=?d WHERE pd_plugin_ident=?', serialize($pd_fields), $id, $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_OK, $title, $plugin_title) : ''));
        }
        else
        {
            if ($categs)
            {
                $result = sql_param_query('INSERT INTO sb_plugins_data SET pd_plugin_ident=?, pd_fields="", pd_categs=?, pd_increment=?d', $_GET['ident'], serialize($pd_fields), $id, ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_CATEG_OK, $title, $plugin_title) : ''));
            }
            else
            {
                $result = sql_param_query('INSERT INTO sb_plugins_data SET pd_plugin_ident=?, pd_fields=?, pd_categs="", pd_increment=?d', $_GET['ident'], serialize($pd_fields), $id, ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_OK, $title, $plugin_title) : ''));
                if (!$result)
                {
                    sql_query('ALTER TABLE `'.$plugin_table.'` DROP `user_f_'.$id.'`');
                }
            }
        }
	}
	else
	{
        if ($categs)
            $result = sql_param_query('UPDATE sb_plugins_data SET pd_categs=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_CATEG_OK, $title, $plugin_title) : ''));
        else
            $result = sql_param_query('UPDATE sb_plugins_data SET pd_fields=? WHERE pd_plugin_ident=?', serialize($pd_fields), $_GET['ident'], ($plugin_title != '' ? sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_OK, $title, $plugin_title) : ''));
    }

    if (!$result)
    {
        if ($plugin_title != '')
        {
            if ($categs)
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_CATEG_ERROR, $title, $plugin_title), SB_MSG_WARNING);
            else
                sb_add_system_message(sprintf(PL_PLUGIN_DATA_SYSLOG_EDIT_ERROR, $title, $plugin_title), SB_MSG_WARNING);
        }

        sb_show_message(PL_PLUGIN_DATA_EDIT_ERROR3, true, 'warning');
        return;
    }

    $img = '';

    if ((!isset($_GET['id']) || $_GET['id'] == '') && file_exists(SB_CMS_IMG_PATH.'/data_types/'.$_GET['type'].'.gif'))
    {
        $img = SB_CMS_IMG_URL.'/data_types/'.$_GET['type'].'.gif';
    }

    echo '<script>
            var res = new Object();
            res.new_f = '.(isset($_GET['id']) && $_GET['id'] != '' ? 'false' : 'true').';
            res.id = '.$id.';
            res.type_title = "'.$type_info['title'].'";
            res.title = "'.str_replace('"', '\\"', $title).'";
            res.type = "'.$_GET['type'].'";
            res.img = "'.$img.'";
            res.info = "'.$info.'";
            res.sort = "'.$sort.'";
            res.filter = "'.$filter.'";
            res.mandatory = "'.$mandatory.'";
            sbReturnValue(res);
        </script>';
}

function fPluginData_Get_Module_Fields()
{
	echo sb_json_encode(getPluginTitleFields($_GET['ident']));
}

function fPluginData_Get_Options()
{
	$subcategs = $_GET['subcategs'] == 1 ? true : false;
	if (isset($_GET['fld']) && trim($_GET['fld']) != '')
    {
    	$fld = preg_replace('/[^A-Za-z_0-9]/', '', $_GET['fld']);
    	$ident = preg_replace('/[^A-Za-z_0-9]/', '', $_GET['ident']);
    }
	else
	{
		echo '';
		return;
	}
    $sql_params = getPluginSqlParams($ident);

	$is_show_sql = '';
    if(isset($sql_params['show']) && $sql_params['show'] != '')
    {
    	$is_show_sql = ' AND '.$sql_params['show'].' > 0 ';
    }

    if ($subcategs)
    {
        $res = sql_param_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
        if ($res)
        {
            list($left, $right) = $res[0];
            $res = sql_query('SELECT DISTINCT s.'.$sql_params['id'].', s.'.$fld.' FROM '.$sql_params['table'].' s, sb_catlinks l, sb_categs c WHERE c.cat_ident="'.$ident.'" AND c.cat_left >= '.$left.' AND c.cat_right <= '.$right.' AND l.link_cat_id=c.cat_id AND l.link_el_id=s.'.$sql_params['id'].$is_show_sql.' ORDER BY s.'.$sql_params['sort'].', s.'.$fld);
        }
    }
    else
    {
        $res = sql_param_query('SELECT DISTINCT s.'.$sql_params['id'].', s.'.$fld.' FROM '.$sql_params['table'].' s, sb_catlinks l WHERE l.link_cat_id = ?d AND l.link_el_id=s.'.$sql_params['id'].$is_show_sql.' ORDER BY s.'.$sql_params['sort'].', s.'.$fld, $_GET['cat_id']);
    }

    $result = '';
    if ($res)
    {
        $num = count($res);
        foreach($res as $key => $value)
        {
            list($s_id, $s_title) = $value;
            if(isset($sql_params['date']) && $sql_params['date'] == $fld)
            	$s_title = date('d.m.Y h:i', $s_title);

            $result .= $s_id.'^^'.$s_title.($key != $num - 1 ? '::' : '');
        }
    }

    echo $result;
}

function fPluginData_Get_MaxVarchar($type)
{
    foreach ($GLOBALS['sb_plugins_fields'] as $key => $value)
    {
        foreach ($value as $key2 => $value2)
        {
            if ($key2 == $type)
            {
                if (!isset($value2['sql']))
                    return false;

                $matches = array();
                preg_match_all('/varchar\((\d+|\?d)\)/i'.SB_PREG_MOD, $value2['sql'], $matches);
                if(!empty($matches[1]))
                {
                    return intval($matches[1][0]);
                }

                return false;
            }
        }
    }

    return false;
}
?>