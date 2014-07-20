<?php
require_once(SB_CMS_LANG_PATH.'/pl_plugin_maker.h.lng.php');

function fCategs_Read()
{
    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');

    $cat_ident = preg_replace('/[^0-9a-zA-Z_]+/', '', $_GET['cat_ident']);

    $categs = new sbJustCategs($cat_ident);
    $categs->init();
}

/**
 * Проверка прав доступа пользователя системы к разделу
 *
 * @param mixed $cat_rights Идентификатор раздела или идентификаторы прав, разделенных символом ^.
 * @param bool $rights В параметре $cat_rights передан идентификатор раздела или идентификаторы прав.
 *
 * @return bool TRUE, если у пользователя системы есть необходимые права доступа, FALSE в ином случае.
 */
function fCategs_Check_Rights($cat_rights, $rights = false)
{
    if ($_SESSION['sbAuth']->isAdmin())
    {
    	return true;
    }

    if (!$rights)
    {
        $res = sql_query('SELECT cat_rights FROM sb_categs WHERE cat_id=?d', $cat_rights);
        if ($res)
        {
            $cat_rights = trim($res[0][0]);
        }
        else
        {
            return false;
        }
    }

    if ($cat_rights == '')
    {
    	return false;
    }

    $cat_rights = explode('^', $cat_rights);
    $cat_groups = array();

    foreach ($cat_rights as $right)
    {
    	if ($right[0] == 'g')
    	{
    		$cat_groups[] = intval(substr($right, 1));
    	}
    	elseif ($right[0] == 'u')
    	{
    		if (intval(substr($right, 1)) == $_SESSION['sbAuth']->getUserId())
    		{
    			return true;
    		}
    	}
    	else
    	{
    		$cat_groups[] = intval($right);
    	}
    }

    $groups = $_SESSION['sbAuth']->getUserGroups();
    if (count(array_intersect($groups, $cat_groups)) > 0)
    {
        return true;
    }

    return false;
}

/**
 * Проверка прав доступа пользователя системы к элементу.
 *
 * @param int $el_id Идентификатор элемента.
 * @param int $cat_id Идентификатор раздела.
 * @param string $cat_ident Идентификатор раздела (строка).
 * @param bool $show_msg Показывать сообщение об ошибке.
 *
 * @return bool TRUE - доступ есть, FALSE - доступа нет.
 */
function fCategs_Check_Elems_Rights($el_id, $cat_id, $cat_ident, $show_msg = true)
{
	if ($_SESSION['sbAuth']->isAdmin())
		return true;

	if ($el_id > 0)
	{
		$res = sql_query('SELECT c.cat_id FROM sb_categs c, sb_catlinks l WHERE l.link_cat_id=c.cat_id AND l.link_el_id=?d AND c.cat_ident=?', $el_id, $cat_ident);
		if (!$res)
		{
			if ($show_msg)
				sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');

			return false;
		}

		$found = false;
		foreach ($res as $value)
		{
			if ($value[0] == $cat_id)
			{
				$found = true;
				break;
			}
		}

		if (!$found)
		{
			list($cat_id) = $res[0];
		}
	}

	if (!fCategs_Check_Rights($cat_id))
    {
    	if ($show_msg)
        	sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');

        return false;
    }

    return true;
}

function fCategs_Set_Active()
{
	if(!isset($_GET['ids']) || $_GET['ids'] == '')
	{
		return;
	}

	// проверяем права пользователя
    if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'elems_edit'))
    {
        return;
    }

	$res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if (!$res)
    {
    	return;
    }

    list($cat_ident) = $res[0];

    // проверяем права на раздел
    if (!fCategs_Check_Rights($_GET['cat_id']))
    {
        return;
    }

    sql_query('LOCK TABLES ?# WRITE, sb_catchanges WRITE', $_GET['table']);
    $res = sql_query('SELECT ?#, ?#, ?# FROM ?# WHERE ?# IN ('.preg_replace('/[^0-9,]+/'.SB_PREG_MOD, '', $_GET['ids']).')', $_GET['id_field'], $_GET['title_field'], $_GET['field'], $_GET['table'], $_GET['id_field']);
	if (!$res)
	{
		return;
	}

   	$date = time();
   	foreach ($res as $value)
   	{
        list($el_id, $el_title, $el_active) = $value;

        $el_active = ($el_active == 1 ? 0 : 1);

		sql_query('UPDATE ?# SET ?# = ?d WHERE ?# = ?d', $_GET['table'], $_GET['field'], $el_active, $_GET['id_field'], $el_id, sprintf(PL_CATEGS_SET_ACTIVE_OK, $el_title, $_SESSION['sbPlugins']->getPluginTitle($_GET['plugin_ident'])));
        sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $el_id, $cat_ident, $date, $_SESSION['sbAuth']->getUserId(), 'edit');
   	}

    sql_query('UNLOCK TABLES');
    echo 'TRUE';
}

function fCategs_Add_Edit()
{
    // проверяем права пользователя
    if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'categs_edit'))
    {
        sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
        return;
    }

    if (count($_POST) == 0 && isset($_GET['cat_id']) && $_GET['cat_id'] != '')
    {
        $res = sql_query('SELECT cat_title, cat_rubrik, cat_rights, cat_closed, cat_url, cat_fields FROM sb_categs
                                WHERE cat_id=?d', $_GET['cat_id']);

        if ($res)
        {
            list($cat_title, $cat_rubrik, $cat_rights, $cat_closed, $cat_url, $cat_fields) = $res[0];
            $_GET['cat_id_p'] = '';
            if ($cat_fields != '')
				$cat_fields = unserialize($cat_fields);
        }
        else
        {
            sb_show_message(PL_CATEGS_ERROR_NO_CATEG, true, 'warning');
            return;
        }
    }
    elseif (count($_POST) > 0)
	{
		$cat_rubrik = 0;
        $cat_closed = 0;
        $cat_fields = array();

    	extract($_POST);

    	if (!isset($_GET['cat_id']))
    	    $_GET['cat_id'] = '';
	}
    else
    {
        $res = sql_query('SELECT cat_rubrik, cat_rights, cat_closed, cat_fields FROM sb_categs WHERE cat_id=?d', $_GET['cat_id_p']);
        if ($res)
        {
            list($cat_rubrik, $cat_rights, $cat_closed, $cat_fields) = $res[0];

            if ($cat_fields != '')
				$cat_fields = unserialize($cat_fields);

            $cat_title = '';
            $cat_url = '';

            $_GET['cat_id'] = '';
        }
        else
        {
            sb_show_message(PL_CATEGS_ERROR_NO_CATEG, true, 'warning');
            return;
        }
    }

    if (!fCategs_Check_Rights($cat_rights, true))
    {
        sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
        return;
    }

    $use_closed = isset($_GET['closed']) ? (bool)$_GET['closed'] : 0;
    $use_url = isset($_GET['cat_url']) ? (bool)$_GET['cat_url'] : 0;
    $use_mods = isset($_GET['cat_mods']) ? (bool)$_GET['cat_mods'] : 0;
    $cat_ident = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['cat_ident']);

    echo '<script>
	function checkValues()
	{
	    var cat_title = sbGetE("cat_title");
        if (cat_title.value == "")
		{
			alert("'.PL_CATEGS_NO_TITLE_MSG.'");
            cat_title.focus();
    		return false;
		}
    '.($use_closed ? '
        var cat_closed = sbGetE("cat_closed");
        if (cat_closed && cat_closed.checked)
        {
            var frm = sbGetE("main");
            var found = false;

            for (var i = 0; i < frm.elements.length; i++)
            {
                if (frm.elements[i].id.indexOf("group_ids") != -1 && frm.elements[i].value != "")
                {
                    found = true;
                    break;
                }
            }

            if (!found)
            {
            	alert("'.PL_CATEGS_NO_GROUPS_MSG.'");
                return false;
            }
        }' : '')
    .'
        return true;
	}
	'.($use_closed ? '
	var group_ident = "";
    function browseGroups(ident)
    {
        var el = sbGetE("group_names" + ident);
        if (!el || el.disabled)
            return;

        group_ident = ident;
        var group_ids = sbGetE("group_ids" + ident).value;

        var strPage = "'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_get_groups&sel_cat_ids="+group_ids;
        var strAttr = "resizable=1,width=500,height=500";
        sbShowModalDialog(strPage, strAttr, afterBrowseGroups);
    }
    function afterBrowseGroups()
    {
        if (sbModalDialog.returnValue)
        {
            var group_ids = sbGetE("group_ids" + group_ident);
            var group_names = sbGetE("group_names" + group_ident);

            group_ids.value = sbModalDialog.returnValue.ids;
            group_names.value = sbModalDialog.returnValue.text;
        }
        group_ident = "";
    }
    function dropGroups(ident)
    {
        var group_names = sbGetE("group_names" + ident);
        if (!group_names || group_names.disabled)
            return;

        var group_ids = sbGetE("group_ids" + ident);

        group_ids.value = "";
        group_names.value = "";
    }
    function changeType(el)
    {
        var btns = new Array();
        var ids = new Array();
        var names = new Array();
        var frm = sbGetE("main");

        for (var i = 0; i < frm.elements.length; i++)
        {
            if (frm.elements[i].id.indexOf("group_names") != -1)
            {
                if (el.checked)
                {
                    frm.elements[i].disabled = false;
                }
                else
                {
                    frm.elements[i].disabled = true;
                    frm.elements[i].value = "";
                }
            }
            else if (!el.checked && frm.elements[i].id.indexOf("group_ids") != -1)
            {
                frm.elements[i].value = "";
            }
        }
    }' : '').
	'</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_categs_add_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', '', 'enctype="multipart/form-data"');
    $layout->mTitleWidth = 200;

    $layout->addTab(PL_CATEGS_TAB1);
    $layout->addHeader($_GET['cat_id'] != '' ? PL_CATEGS_EDIT_HEADER : PL_CATEGS_ADD_HEADER);
    $layout->addField(PL_CATEGS_TITLE, new sbLayoutInput('text', $cat_title, 'cat_title', '', 'style="width:96%;"', true));

    if ($use_url)
    {
        $layout->addField(KERNEL_STATIC_URL, new sbLayoutInput('text', $cat_url, 'cat_url', '', 'style="width:96%;"'));
        $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.KERNEL_STATIC_URL_HINT.'</div>', '', '', false));
    }

    $layout->addField('', new sbLayoutInput('hidden', $cat_rubrik, 'cat_rubrik'));
    $layout->addField('', new sbLayoutInput('hidden', $cat_rights, 'cat_rights'));

    if (isset($_GET['fields']) && $_GET['fields'] == 1)
        $layout->getPluginFields($_GET['plugin_ident'], $_GET['cat_id'], '', true);

    if ($use_closed)
    {
    	$layout->addTab(PL_CATEGS_TAB2);
    	$layout->addHeader(PL_CATEGS_TAB2);

        $layout->addField(PL_CATEGS_CLOSED, new sbLayoutInput('checkbox', '1', 'cat_closed', '', 'onclick="changeType(this);"'.($cat_closed ? ' checked="checked"' : '')));

        if (isset($_GET['cat_id']) && $_GET['cat_id'] != '')
        {
        	$layout->addField(PL_CATEGS_CLOSE_SUB, new sbLayoutInput('checkbox', '1', 'cat_close_sub'));
        }

        $layout->addField('', new sbLayoutDelim());

        if (count($_SESSION['sb_categs_closed_descr'][$cat_ident]) == 0)
        {
            $_SESSION['sb_categs_closed_descr'][$cat_ident]['read'] = PL_CATEGS_GROUPS;
        }

        foreach ($_SESSION['sb_categs_closed_descr'][$cat_ident] as $ident => $name)
        {
            $ident = $cat_ident.'_'.$ident;

            if (isset($_POST['group_ids'.$ident]) && isset($_POST['group_names'.$ident]))
            {
                $group_ids = $_POST['group_ids'.$ident];
                $group_names = $_POST['group_names'.$ident];
            }
            else
            {
                $group_ids = '';
                $group_names = '';

                $res = sql_query('SELECT group_ids FROM sb_catrights
                                        WHERE cat_id=?d AND right_ident=?', (isset($_GET['cat_id']) && $_GET['cat_id'] != '' ? $_GET['cat_id'] : $_GET['cat_id_p']), $ident);

                if($res)
                {
                    list($group_ids) = $res[0];
                    $ids = explode('^', trim($group_ids, '^'));

                    $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id IN (?a)', $ids);

                    if ($res)
                    {
                        $group_names = array();
                        foreach($res as $value)
                        {
                            $group_names[] = $value[0];
                        }

                        $group_names = implode(', ', $group_names);
                    }
                    else
                    {
                        $group_ids = '';
                    }
                }
            }

            $layout->addField('', new sbLayoutInput('hidden', $group_ids, 'group_ids'.$ident));
            $layout->addField($name, new sbLayoutHTML('
                     <input id="group_names'.$ident.'" name="group_names'.$ident.'" readonly="readonly"'.(!$cat_closed ? ' disabled="disabled"' : '').' style="width:75%;" value="'.$group_names.'">&nbsp;&nbsp;
                     <img class="button" src="'.SB_CMS_IMG_URL.'/users.png" width="20" height="20" align="absmiddle" id="group_btn'.$ident.'" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\''.$ident.'\');" title="'.KERNEL_BROWSE.'" />&nbsp;&nbsp;
                     <img class="button" src="'.SB_CMS_IMG_URL.'/users_drop.png" width="20" height="20" align="absmiddle" id="group_btn_drop'.$ident.'" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="dropGroups(\''.$ident.'\');" title="'.KERNEL_CLEAR.'" />
                     '));
        }
    }

    if ($use_mods)
    {
    	$layout->addTab(PL_CATEGS_TAB3);
		$layout->addHeader(PL_CATEGS_TAB3);

		$fld = new sbLayoutInput('text', (isset($cat_fields['categs_moderate_email']) ? $cat_fields['categs_moderate_email'] : ''), 'categs_moderate_email', '', 'style="width:97%;"');
		$fld->mHTML = '<div class="hint_div">'.PL_CATEGS_EDIT_MODERATE_EMAIL_DESCR.'</div>';
		$layout->addField(PL_CATEGS_EDIT_MODERATE_EMAIL_FIELD, $fld);

		$layout->addField('', new sbLayoutDelim());

		include_once(SB_CMS_PL_PATH.'/pl_site_users/pl_site_users.inc.php');
		$layout->addField(PL_CATEGS_EDIT_MODERATE_FROM_SYSTEM_USERS, new sbLayoutHTML(fUsers_Get_Groups(isset($cat_fields['moderates_list']) ? $cat_fields['moderates_list'] : '', true)));
    }

    if ($layout->getTabCount() <= 0)
        echo '<br /><br />';

    $layout->addButton('submit', KERNEL_SAVE);
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');
    $layout->show();
}

function fCategs_Add_Edit_Submit()
{
    if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'categs_edit'))
    {
        fCategs_Add_Edit();
        return;
    }

    if (!isset($_GET['cat_id']))
    	$_GET['cat_id'] = '';

    $cat_closed = 0;
    $cat_close_sub = 0;
    $groups = array();
	$users = array();

    extract($_POST);

    if (isset($cat_rights) && !fCategs_Check_Rights($cat_rights, true))
    {
        sb_show_message(SB_ELEMS_DENY_MSG, false, 'warning');
        fCategs_Add_Edit();
        return;
    }

    $cat_ident = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['cat_ident']);

    if (isset($cat_url))
    {
    	$cat_url = sb_check_chpu($_GET['cat_id'], $cat_url, $cat_title, 'sb_categs', 'cat_url', 'cat_id');
        $_POST['cat_url'] = $cat_url;
    }

    $plugin_title = $_GET['plugin'];

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $cat_fields = $layout->checkPluginFields($_GET['cat_ident'], $_GET['cat_id'], '', true);
    if ($cat_fields === false)
    {
        $layout->deletePluginFieldsFiles();
        fCategs_Add_Edit();
        return;
    }

    if (isset($_POST['categs_moderate_email']))
    {
    	$cat_fields['categs_moderate_email'] = $_POST['categs_moderate_email'];

    	$moderates_list = '';
	    if (count($groups) > 0)
		{
			$moderates_list = 'g'.implode('^g', $groups);
		}

		if (count($users) > 0)
		{
			$moderates_list .= ($moderates_list != '' ? '^u' : 'u').implode('^u', $users);
		}

		$cat_fields['moderates_list'] = $moderates_list;
    }

    if (count($cat_fields) == 0)
    {
    	$cat_fields = null;
    }
    else
    {
    	$cat_fields = serialize($cat_fields);
    }

    $use_count = isset($_GET['cat_count']) ? (bool)$_GET['cat_count'] : 0;

    if($_GET['cat_id'] != '')
    {
        $cat_id = intval($_GET['cat_id']);

        //редактирование
        $error = true;
        $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $cat_id);
        if ($res)
        {
            sql_query('LOCK TABLES sb_categs WRITE, sb_catrights WRITE');
            $result = sql_query('UPDATE sb_categs SET cat_title=?, cat_closed=?d, cat_fields=? {, cat_url=?} WHERE cat_id=?d', $cat_title, $cat_closed, $cat_fields, (isset($_POST['cat_url']) ? $_POST['cat_url'] : SB_SQL_SKIP), $cat_id, ($plugin_title != '' ? sprintf(PL_CATEGS_SYSLOG_EDIT_OK, $cat_title, $plugin_title) : ''));
            if ($result)
            {
            	list($cat_left, $cat_right) = $res[0];

	            $cat_ids = array($cat_id);
		        if ($cat_close_sub == 1)
		        {
		        	$res_sub = sql_query('SELECT cat_id, cat_title FROM sb_categs WHERE cat_left > ?d AND cat_right < ?d AND cat_ident=?', $cat_left, $cat_right, $cat_ident);
		        	if ($res_sub)
		        	{
		        		foreach ($res_sub as $value)
		        		{
		        			$cat_ids[] = $value[0];
		        			sql_query('UPDATE sb_categs SET cat_closed=?d WHERE cat_id=?d', $cat_closed, $value[0], ($plugin_title != '' ? sprintf(PL_CATEGS_SYSLOG_RIGHTS_OK, $value[1], $plugin_title) : ''));
		        		}
		        	}
		        }

                sql_query('DELETE FROM sb_catrights WHERE cat_id IN (?a)', $cat_ids);

                if ($cat_closed == 1)
                {
                    // закрытый раздел
                    foreach ($_SESSION['sb_categs_closed_descr'][$cat_ident] as $ident => $name)
                    {
                    	$ident = $cat_ident.'_'.$ident;
                    	foreach ($cat_ids as $value)
                    	{
                        	sql_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                            	         VALUES (?d, ?, ?)', $value, $_POST['group_ids'.$ident], $ident);
                    	}
                    }
                }
                sql_query('UNLOCK TABLES');

                $count_res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND categs.cat_left >= ?d AND categs.cat_right <= ?d AND links.link_cat_id=categs.cat_id', $cat_ident, $cat_left, $cat_right);
                if ($count_res)
                {
                    $cat_count = $count_res[0][0];
                }
                else
                {
                    $cat_count = 0;
                }

                echo '<script>
        	        var res = new Object();
                    res.cat_title = "'.str_replace('"', '\\"', $cat_title).($use_count ? ' ['.$cat_count.']' : '').'";
                    res.cat_closed = '.$cat_closed.';
                    res.cat_rubrik = '.$cat_rubrik.';
                    res.cat_close_sub = '.$cat_close_sub.';
                    sbReturnValue(res);
        		  </script>';

                $error = false;
            }
            else
            {
                sql_query('UNLOCK TABLES');
            }
        }

        if ($error)
        {
            sb_show_message(PL_CATEGS_EDIT_ERROR, false, 'warning');
            if ($plugin_title != '')
            {
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_EDIT_ERROR, $cat_title, $plugin_title), SB_MSG_WARNING);
            }

            fCategs_Add_Edit();
            return;
        }
    }
    else
    {
        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        //добавление
        $tree = new sbTree($cat_ident);

        $fields = array();
        $fields['cat_title'] = $cat_title;
        $fields['cat_closed'] = $cat_closed;
        $fields['cat_rubrik'] = $cat_rubrik;
        $fields['cat_rights'] = $cat_rights;
        $fields['cat_fields'] = $cat_fields;

        if (isset($_POST['cat_url']))
            $fields['cat_url'] = $_POST['cat_url'];

        $cat_id = $tree->insertNode($_GET['cat_id_p'], $fields);
        if ($cat_id)
        {
            if ($cat_closed == 1)
            {
                // закрытый раздел
                foreach ($_SESSION['sb_categs_closed_descr'][$cat_ident] as $ident => $name)
                {
                    $ident = $cat_ident.'_'.$ident;
                    sql_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                                     VALUES (?d, ?, ?)', $cat_id, $_POST['group_ids'.$ident], $ident);
                }
            }

            if ($plugin_title != '')
            {
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_ADD_OK, $cat_title, $plugin_title));
            }

            echo '<script>
    	        var res = new Object();
        	    res.cat_id = '.$cat_id.';
                res.cat_title = "'.str_replace('"', '\\"', $cat_title).($use_count ? ' [0]' : '').'";
                res.cat_closed = '.$cat_closed.';
                res.cat_rubrik = '.$cat_rubrik.';
                sbReturnValue(res);
    		  </script>';
        }
        else
        {
            sb_show_message(PL_CATEGS_ADD_ERROR, false, 'warning');
            if ($plugin_title != '')
            {
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_ADD_ERROR, $cat_title, $plugin_title), SB_MSG_WARNING);
            }

            fCategs_Add_Edit();
            return;
        }
    }
}

function fCategs_Count_Subcategs()
{
    $res = sql_query('SELECT cat_left, cat_right, cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if ($res)
    {
        list($left, $right, $ident) = $res[0];
        $res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links
                          WHERE categs.cat_left >= ?d AND categs.cat_right <= ?d AND categs.cat_ident = ?
                          AND links.link_cat_id=categs.cat_id', $left, $right, $ident);
        if ($res)
        {
            echo $res[0][0];
        }
        else
        {
            echo '0';
        }
    }
}

function fCategs_Delete()
{
	if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'categs_delete'))
    {
        return;
    }

    $plugin_title = $_GET['plugin'];
    $title = '';

    $res = sql_query('SELECT cat_left, cat_right, cat_ident, cat_title, cat_rights, cat_fields FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if ($res)
    {
        list($left, $right, $ident, $title, $cat_rights, $cat_fields) = $res[0];
        if (!fCategs_Check_Rights($cat_rights, true))
        {
            echo 'FALSE';
            return;
        }

        if(isset($_GET['with_el']) && $_GET['with_el'] == 1)
        {
			//	Если выбран пункт "Удалить с элементами", то удаляем все элементы раздела и подразделов
			$_GET['el_id'] = array();
			$_GET['links_id'] = array();
			$_GET['c_ids'] = array();

			if($ident != 'pl_menu')
			{
				$res = sql_query('SELECT links.link_el_id, links.link_src_cat_id, links.link_id FROM sb_categs categs, sb_catlinks links
							WHERE categs.cat_left >= ?d AND categs.cat_right <= ?d AND categs.cat_ident=?
								AND links.link_cat_id=categs.cat_id', $left, $right, $ident);
	        	if($res)
				{
	        		foreach($res as $value)
	        		{
						if($value[1] == 0)
	        				$_GET['el_id'][] = $value[0]; // Массив элементов
	        			else
	        				$_GET['links_id'][] = $value[2]; // Массив ссылок
	        		}

					if($ident != $_GET['plugin_ident'])
					{
						$_GET['sb_cat_plugin_ident'] = $ident;
					}

	        		//	Удаляю элементы
					if(!fCategs_Delete_Elems())
	        		{
	        			if ($plugin_title != '')
	            		{
	        				sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_ELEMENTS_ERROR, $title, $plugin_title), SB_MSG_WARNING);
	            		}
	        			echo 'FALSE';
	        	    	return;
	        		}
	        	}
			}

			if($ident == 'pl_menu' || $ident == 'pl_tester')
			{
				$res = sql_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
								AND c2.cat_right >= c.cat_right
								AND c.cat_ident = ?
								AND c2.cat_ident = ?
								AND c2.cat_id = ?d
							ORDER BY c.cat_left', $ident, $ident, $_GET['cat_id']);
	        	if($res)
	        	{
					foreach($res as $value)
	        		{
						$_GET['c_ids'][] = $value[0];	//	Массив элементов
	        		}
	        	}
			}
        }
		else
        {
			//	Если не выбран пункт "Удалить с элементами", то проверяем на наличие вложенных элементов
			$res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links
						WHERE categs.cat_left >= ?d AND categs.cat_right <= ?d AND categs.cat_ident=?
							AND links.link_cat_id=categs.cat_id', $left, $right, $ident);

	        if ($res && $res[0][0] > 0)
	        {
	            if ($plugin_title != '')
	            {
	                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_ERROR1, $title, $plugin_title), SB_MSG_WARNING);
	            }

        	    echo 'FALSE';
        	    return;
        	}
        }

        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        $tree = new sbTree($ident);
	    if (!$tree->removeNode($_GET['cat_id']))
	    {
			if($ident == 'pl_forum')
			{
				sql_query('DELETE FROM sb_forum_viewing WHERE fv_theme_id = ?d', $_GET['cat_id']);
				sql_query('DELETE FROM sb_forum_maillist WHERE sfm_theme_id = ?d', $_GET['cat_id']);

				$fields = unserialize($cat_fields);
				$fields['cat_image'] =  isset($fields['cat_image']) ? str_replace(SB_DOMAIN.'/', '', $fields['cat_image']) : '';

				if($fields['cat_image'] != '')
				{
		    		$GLOBALS['sbVfs']->mLocal = true;
		    		if($GLOBALS['sbVfs']->exists(SB_BASEDIR.'/'.$fields['cat_image']) && $GLOBALS['sbVfs']->is_file(SB_BASEDIR.'/'.$fields['cat_image']))
		    		{
		    			$GLOBALS['sbVfs']->delete(SB_BASEDIR.'/'.$fields['cat_image']);
		    		}
					$GLOBALS['sbVfs']->mLocal = false;
				}
			}

	        if ($plugin_title != '')
            {
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_ERROR2, $title, $plugin_title), SB_MSG_WARNING);
            }

	        echo 'FALSE';
            return;
	    }

	    sql_query('DELETE FROM sb_catrights WHERE cat_id=?d', $_GET['cat_id']);

	    if (isset($_SESSION['sb_categs_selected_id']) && isset($_SESSION['sb_categs_selected_id'][$ident]) && $_SESSION['sb_categs_selected_id'][$ident] == $_GET['cat_id'])
	    {
		    $_SESSION['sb_categs_selected_id'][$ident] = -1;
	    }

        if ($plugin_title != '')
        {
        	if(isset($_GET['with_el']) && $_GET['with_el'] == 1)
        		sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_WITH_ELEMENTS_OK, $title, $plugin_title));
        	else
            	sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_OK, $title, $plugin_title));
        }

        if(isset($_GET['with_el']) && $_GET['with_el'] == 1)
        {
			echo implode(',', $_GET['el_id']).'|'.implode(',', $_GET['c_ids']);
        }
        else
        {
	    	echo 'TRUE';
	    	return;
        }
    }
    else
    {
        if ($plugin_title != '')
        {
            sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_ERROR2, $title, $plugin_title), SB_MSG_WARNING);
        }

        echo 'FALSE';
        return;
    }
}

function fCategs_Check()
{
    if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'categs_edit'))
    {
        echo PL_CATEGS_CHECK_ERROR;
    }

    $res = sql_query('SELECT cat_title, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if ($res)
    {
        list($cat_title, $cat_rights) = $res[0];
        if (!fCategs_Check_Rights($cat_rights, true))
        {
            echo PL_CATEGS_CHECK_ERROR;
            return;
        }

        $plugin_title = $_GET['plugin'];

        $res = sql_query('UPDATE sb_categs SET cat_rubrik=?d WHERE cat_id=?d', $_GET['state'], $_GET['cat_id'], ($plugin_title != '' ? sprintf(PL_CATEGS_SYSLOG_CHECK_OK, $cat_title, $plugin_title):''));

        if (!$res)
        {
            if ($plugin_title != '')
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_CHECK_ERROR, $cat_title, $plugin_title), SB_MSG_WARNING);
        }
    }
    else
    {
        echo PL_CATEGS_CHECK_ERROR;
    }
}

function fCategs_Paste_Categs()
{
    if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'categs_edit'))
    {
        echo PL_CATEGS_PASTE_ERROR;
        return;
    }

    $before_cat_id = intval($_GET['before_cat_id']);
    $max_level = intval($_GET['max_level']);

    $action = preg_replace('/[^a-z]+/', '', $_GET['action']);
    $plugin_title = $_GET['plugin'];

    $res1 = sql_query('SELECT cat_ident, cat_level, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    $res2 = sql_query('SELECT cat_level, cat_left, cat_right, cat_title, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['paste_cat_id']);
    if ($res1 && $res2)
    {
        list($cat_ident, $cat_level, $cat_rights) = $res1[0];
        list($paste_start_level, $paste_cat_left, $paste_cat_right, $paste_cat_title, $paste_cat_rights) = $res2[0];

        if (!$_SESSION['sbAuth']->isAdmin())
        {
            if (!fCategs_Check_Rights($cat_rights, true))
            {
                echo PL_CATEGS_PASTE_ERROR;
                return;
            }

            if (!fCategs_Check_Rights($paste_cat_rights, true))
            {
                echo PL_CATEGS_PASTE_ERROR;
                return;
            }
        }

        $res2 = sql_query('SELECT MAX(cat_level) FROM sb_categs WHERE cat_left >= ?d AND cat_right <= ?d AND cat_ident=?', $paste_cat_left, $paste_cat_right, $cat_ident);
        list($paste_end_level) = $res2[0];
        if ($before_cat_id)
            $incr = 0;
        else
            $incr = 1;

        if ($cat_level + $incr + $paste_end_level - $paste_start_level >= $max_level)
        {
            echo sprintf(PL_CATEGS_ERROR_MAX_DEEP, $max_level);
            return;
        }

        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        $tree = new sbTree($cat_ident);
        $return_res = $tree->pasteNode($_GET['cat_id'], $_GET['paste_cat_id'], $action, $before_cat_id);
        if ($action == 'copy')
        {
            if (!$return_res)
            {
                echo PL_CATEGS_PASTE_ERROR;
                return;
            }
            else
            {
                echo $return_res;
            }

            if(isset($_GET['with_elements']) && $_GET['with_elements'] == 1)
            {
            	$cat_ids = explode('|', $return_res);
            	$cp = array();
            	foreach ($cat_ids as $key => $value)
            	{
            		$value = explode('::', $value);
            		$cp[] = array('from' => $value[0], 'to' => $value[1]);
            	}
            	// Копируем все записи из старого раздела в новый
            	fCategs_Copy_Elems($cp, $_GET['plugin_ident']);
            }

            if ($plugin_title != '')
            {
            	if(isset($_GET['with_elements']) && $_GET['with_elements'] == 1)
            		sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_COPY_WITH_ELEMENTS, $paste_cat_title, $plugin_title));
            	else
            		sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_COPY, $paste_cat_title, $plugin_title));
            }
        }
        else
        {
            if ($plugin_title != '')
            {
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_CUT, $paste_cat_title, $plugin_title));
            }
        }
    }
    else
    {
        echo PL_CATEGS_PASTE_ERROR;
    }
}

/**
 * Ф-ция копирует все записи из одного раздела в другой
 *
 * @param array $cats Ассоциативный массив 'from' => 'Из раздела', 'to' => 'В раздел'
 * @param $plugin Идентификатор модуля.
 */
function fCategs_Copy_Elems($cats, $plugin)
{
	$params = getPluginSqlParams($plugin);
	$_SESSION['paste_categs_with_elems_ids'] = array();
	$_SESSION['paste_categs_with_elems_ids']['old'] = array();
	$_SESSION['paste_categs_with_elems_ids']['new'] = array();

	foreach($cats as $value)
	{
		if (!fCategs_Check_Rights($value['to']))
	    {
	        continue;
	    }

		// вытаскиваю все записи из таблицы
		$res = sql_query('SELECT l.link_id, l.link_el_id, l.link_src_cat_id FROM sb_catlinks as l, '.$params['table'].' as e WHERE l.link_cat_id = ?d AND l.link_el_id = e.'.$params['id'].' order by e.'.$params['id'],$value['from']);
		if($res)
		{
			foreach ($res as $val)
			{
				if($val[2] == 0)
				{
					// Если элемент
					$new_id = $GLOBALS['sbSql']->duplicateRow($val[1], $params['id'], $params['table']);

					if($new_id > 0)
					{
						$_SESSION['paste_categs_with_elems_ids']['old'][] = $val[1];
						$_SESSION['paste_categs_with_elems_ids']['new'][] = $new_id;

						// Добавление связи с разделом
						sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id) values (?d, ?d, 0)', $value['to'], $new_id);
						// Добавление "Последнего изменения"
						sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_user_id, change_date, action) values(
									?d, ?, ?d, ?, "add")', $new_id, $plugin, $_SESSION['sbAuth']->getUserId(), date('U'));
					}
				}
				else
				{
					// Если ссылка
					sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id) values (?d, ?d, ?d)', $value['to'], $val[1], $val[2]);
				}
			}
		}
	}
}

function fCategs_Edit_Elem()
{
	$res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if (!$res)
    {
    	return false;
    }

    list($cat_ident) = $res[0];

    $date = time();

    sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $_GET['id'], $cat_ident, $date, $_SESSION['sbAuth']->getUserId(), 'edit');

    $res = array(SB_ELEMS_LAST_MODIFIED.': '.sb_date('d.m.Y '.KERNEL_IN.' H:i:s', $date).' ('.($_SESSION['sbAuth']->isAdmin() || $_SESSION['sbPlugins']->isRightAvailable('pl_users', 'read') ? '<a href="javascript:void(0);" onclick="sbShowDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_kernel_user_info&id='.$_SESSION['sbAuth']->getUserId().'\', \'resizable=1,width=800,height=600\');" class="small">'.$_SESSION['sbAuth']->getUserLogin().'</a>' : $_SESSION['sbAuth']->getUserLogin()).').
    			 <a href="javascript:void(0);" onclick="sbShowModalDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_categs_changes&el_id='.$_GET['id'].'&cat_ident='.$cat_ident.'\', \'resizable=1,width=500,height=700\');" class="small">'.SB_ELEMS_HISTORY.'</a>.',
                 '<a href="javascript:void(0);" onclick="sbElemsHighlight(event, \''.intval($_GET['id']).'\');" ondblclick="sbCancelEvent(event);" class="small"><img src="'.SB_CMS_IMG_URL.'/link.png" width="12" height="12" title="'.SB_ELEMS_IS_LINK.'" align="absmiddle" border="0"> '.SB_ELEMS_LINK_TEXT.'</a>&nbsp;|&nbsp;'.SB_ELEMS_LAST_MODIFIED.': '.sb_date('d.m.Y '.KERNEL_IN.' H:i:s', $date).
                 ' ('.($_SESSION['sbAuth']->isAdmin() || $_SESSION['sbPlugins']->isRightAvailable('pl_users', 'read') ? '<a href="javascript:void(0);" onclick="sbShowDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_kernel_user_info&id='.$_SESSION['sbAuth']->getUserId().'\', \'resizable=1,width=800,height=600\');" class="small">'.$_SESSION['sbAuth']->getUserLogin().'</a>' : $_SESSION['sbAuth']->getUserLogin()).').
                 <a href="javascript:void(0);" onclick="sbShowModalDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_categs_changes&el_id='.$_GET['id'].'&cat_ident='.$cat_ident.'\', \'resizable=1,width=500,height=700\');" class="small">'.SB_ELEMS_HISTORY.'</a>.');
    return $res;
}

function fCategs_Add_Elem($id)
{
	if ($id <= 0)
		return false;

	$res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if (!$res)
    {
    	return false;
    }

    list($cat_ident) = $res[0];

    $date = time();

	sql_query('LOCK TABLES sb_catlinks WRITE, sb_catchanges WRITE');
    $res = sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id) VALUES (?d, ?d, 0)', $_GET['cat_id'], $id);
    if ($res)
    {
    	sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $id, $cat_ident, $date, $_SESSION['sbAuth']->getUserId(), 'add');
    }
    sql_query('UNLOCK TABLES');

    if ($res)
    {
        $res = array(SB_ELEMS_LAST_MODIFIED.': '.sb_date('d.m.Y '.KERNEL_IN.' H:i:s', $date).' ('.($_SESSION['sbAuth']->isAdmin() || $_SESSION['sbPlugins']->isRightAvailable('pl_users', 'read') ? '<a href="javascript:void(0);" onclick="sbShowDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_kernel_user_info&id='.$_SESSION['sbAuth']->getUserId().'\', \'resizable=1,width=800,height=600\');" class="small">'.$_SESSION['sbAuth']->getUserLogin().'</a>' : $_SESSION['sbAuth']->getUserLogin()).').
        			 <a href="javascript:void(0);" onclick="sbShowModalDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_categs_changes&el_id='.$id.'&cat_ident='.$cat_ident.'\', \'resizable=1,width=500,height=700\');" class="small">'.SB_ELEMS_HISTORY.'</a>.', '');
    }
    return $res;
}

function fCategs_Set_Rights()
{
    if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'categs_rights'))
    {
        return;
    }

    $plugin_title = $_GET['plugin'];

    $res = sql_query('SELECT cat_title, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if ($res)
    {
        list($cat_title, $cat_rights) = $res[0];
        if (!fCategs_Check_Rights($cat_rights, true))
        {
            return;
        }

        $rights = trim(preg_replace('/[^0-9gu\^]+/', '', $_GET['rights']));

        sql_query('UPDATE sb_categs SET cat_rights=? WHERE cat_id=?d', $rights, $_GET['cat_id'], sprintf(PL_CATEGS_SYSLOG_RIGHTS, $cat_title, $plugin_title));

        if (isset($_GET['subcategs']) && $_GET['subcategs'] == 1)
        {
            $res = sql_query('SELECT cat_left, cat_right, cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
            if ($res)
            {
                list($cat_left, $cat_right, $cat_ident) = $res[0];

                $res = sql_query('SELECT cat_id, cat_title, cat_rights FROM sb_categs WHERE cat_left > ?d AND cat_right < ?d AND cat_ident=?', $cat_left, $cat_right, $cat_ident);
                if ($res)
                {
                	foreach ($res as $value)
                	{
                		list($sub_cat_id, $sub_cat_title, $sub_cat_rights) = $value;
	                	if (!fCategs_Check_Rights($sub_cat_rights, true))
				        {
				            continue;
				        }

						sql_query('UPDATE sb_categs SET cat_rights=? WHERE cat_id = ?d', $rights, $sub_cat_id, sprintf(PL_CATEGS_SYSLOG_RIGHTS, $sub_cat_title, $plugin_title));
                	}
                }
            }
        }
    }
    else if ($plugin_title != '')
    {
        sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_RIGHTS_ERROR, $cat_title, $plugin_title), SB_MSG_WARNING);
    }
}

function fCategs_Delete_Elems()
{
	if (!isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'elems_delete'))
    {
    	if($_GET['event'] && $_GET['event'] == 'pl_categs_delete')
    		return false;

		echo 'FALSE';
		return;
	}

    $res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    if (!$res)
    {
    	if($_GET['event'] && $_GET['event'] == 'pl_categs_delete')
    		return false;

    	echo 'FALSE';
    	return;
    }

    list($cat_ident) = $res[0];

    if (!fCategs_Check_Rights($_GET['cat_id']))
    {
    	if($_GET['event'] && $_GET['event'] == 'pl_categs_delete')
    		return false;

        echo 'FALSE';
        return;
    }

    $plugin_title = $_GET['plugin'];
    if($_GET['event'] && $_GET['event'] == 'pl_categs_delete')
    {
		//	Если ф-ция вызывается при удалении разделов с элементами
    	$elems_sql_param = getPluginSqlParams((isset($_GET['sb_cat_plugin_ident']) ? $_GET['sb_cat_plugin_ident'] : $_GET['plugin_ident']));
        $table = $elems_sql_param['table'];
        $id_field = $elems_sql_param['id'];
		$title_field = $elems_sql_param['title'];
	}
	else
	{
    	$table = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['table']);
    	$id_field = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['id_field']);
    	$title_field = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['title_field']);
	}

	if((isset($_GET['el_id']) && is_array($_GET['el_id'])) || (isset($_GET['links_id']) && is_array($_GET['links_id'])))
	{
    	// Если пришел массив элементов или ссылок
    	if(isset($_GET['el_id']) && is_array($_GET['el_id'])  && count($_GET['el_id']) > 0)
    	{
    		// Удаляем элементы
    		$res = sql_query('SELECT l.link_id FROM sb_catlinks AS l, sb_categs AS c WHERE l.link_el_id in (?a) AND l.link_cat_id = c.cat_id AND c.cat_ident = ?', $_GET['el_id'], $cat_ident);
    		if(!$res)
    			return false;

    		$link_id = array();
    		if($res)
    		{
    			foreach($res as $value)
    				$link_id[] = $value[0]; // Идентификаторы связей
    		}

    	 	sql_query('LOCK TABLES '.$table.' WRITE, sb_catlinks WRITE, sb_catchanges WRITE');
		    if (sql_query('DELETE FROM '.$table.' WHERE '.$id_field.' IN (?a)', $_GET['el_id']))
		    {
		        sql_query('DELETE FROM sb_catlinks WHERE link_id IN (?a)', $link_id);
    		    sql_query('DELETE FROM sb_catchanges WHERE el_id IN (?a) AND cat_ident=?', $_GET['el_id'], $cat_ident);
    		    sql_query('UNLOCK TABLES');
                
                //Удаляем файлы макетов дизайна
                sbQueryCache::deleteTemplate($table, $_GET['el_id']);
            }
		    sql_query('UNLOCK TABLES');
            if ($table == 'sb_site_users')
            {
                sql_param_query('DELETE FROM sb_socnet_users WHERE sbsu_uid IN (?a)', $_GET['el_id']);
            }
    	}

    	if(isset($_GET['links_id']) && is_array($_GET['links_id']) && count($_GET['links_id']) > 0)
    	{
    		// Удаляем ссылки
    	    sql_query('DELETE FROM sb_catlinks WHERE link_id IN (?a)', $_GET['links_id']);
    	}
    	return true;
    }
    else
    {
    	// Если удаляется один элемент
    	$is_link = intval($_GET['is_link']);

    	$el_title = '';
    	if ($plugin_title != '')
    	{
        	// вытаскиваем название элемента
        	$res = sql_query('SELECT ?# FROM ?# WHERE ?#=?d', $title_field, $table, $id_field, $_GET['el_id']);
       		 if ($res)
    	    {
    	        list($el_title) = $res[0];
    	        if ($table == 'sb_forum')
    	        {
    	        	require_once (SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');
    	        	$el_title = sbProgParseBBCodes($el_title, '', '', false);
    	        }
    	    }
    	}

    	if ($is_link == 0)
    	{
    	    //удаляется элемент, а не ссылка на него
    	    sql_query('LOCK TABLES '.$table.' WRITE, sb_catlinks WRITE, sb_catchanges WRITE');
		    if (sql_query('DELETE FROM '.$table.' WHERE '.$id_field.'=?d', $_GET['el_id']))
		    {
                sql_query('DELETE FROM sb_catlinks WHERE link_id=?d', $_GET['true_id']);
    		    sql_query('DELETE FROM sb_catlinks WHERE link_src_cat_id=?d AND link_el_id=?d', $_GET['cat_id'], $_GET['el_id']);
    		    sql_query('DELETE FROM sb_catchanges WHERE el_id=?d AND cat_ident=?', $_GET['el_id'], $cat_ident);
    		    sql_query('UNLOCK TABLES');
                
                //Удаляем файлы макетов дизайна
                sbQueryCache::deleteTemplate($table, $_GET['el_id']);

    		    if ($el_title != '')
    		       sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_ELEM, $el_title, $plugin_title));
		    }
		    else
		    {
		        sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_ELEM_ERROR, $el_title, $plugin_title), SB_MSG_WARNING);
		    }
            sql_query('UNLOCK TABLES'); 
            
            if ($table == 'sb_site_users')
            {
                sql_query('DELETE FROM sb_socnet_users WHERE sbsu_uid=?d', $_GET['el_id']);
            }

    	}
    	else
    	{
    		// удаляем ссылку на элемент
    	    sql_query('DELETE FROM sb_catlinks WHERE link_id=?d', $_GET['true_id']);

    	    if ($el_title != '')
    	        sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_DELETE_LINK, $el_title, $plugin_title));
    	}
    }
}

function fCategs_Paste_Elems()
{
    if (((!isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0) &&
         (!isset($_GET['l']) || !is_array($_GET['l']) || count($_GET['l']) <= 0)) ||
          !isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'elems_edit'))
    {
        return;
    }

    if (!fCategs_Check_Rights($_GET['new_cat_id']))
    {
        return;
    }

    $res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_id=?d', $_GET['new_cat_id']);
    if (!$res)
    {
    	return;
    }

    list($cat_ident) = $res[0];

    $link_id_in_str = '(0';
	$link_el_id_in_str = '(0';

	if (isset($_GET['e']))
	{
    	foreach($_GET['e'] as $link_id => $el_id)
    	{
            $link_id_in_str .= ','.intval($link_id);
            $link_el_id_in_str .= ','.intval($el_id);
    	}
	}
	if (isset($_GET['l']))
	{
	    foreach($_GET['l'] as $link_id => $el_id)
    	{
            $link_id_in_str .= ','.intval($link_id);
            $link_el_id_in_str .= ','.intval($el_id);
    	}
	}
    $link_id_in_str .= ')';
    $link_el_id_in_str .= ')';

    $date = time();
    $plugin_title = $_GET['plugin'];

    $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['table']);
    $id_field = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['id_field']);
    $title_field = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['title_field']);

	$new_cat_title = '';
	$old_cat_title = '';

	if ($plugin_title != '')
    {
        $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $_GET['new_cat_id']);
        if ($res)
        {
    	   list($new_cat_title) = $res[0];
        }
        else
        {
            sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_PASTE_ERROR, $plugin_title), SB_MSG_WARNING);
            return;
        }
    	$res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $_GET['old_cat_id']);
        if ($res)
        {
            list($old_cat_title) = $res[0];
        }
        else
        {
            sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_PASTE_ERROR, $plugin_title), SB_MSG_WARNING);
            return;
        }
    }

    $els_title = array();
    $res = sql_query('SELECT ?#, ?# FROM ?# WHERE ?# IN '.$link_el_id_in_str, $id_field, $title_field, $table, $id_field);
    if ($res)
    {
        foreach ($res as $value)
        {
            list($id, $title) = $value;
            $els_title[$id] = $title;
        }
    }

	if ($_GET['action'] == 'cut')
	{
	    $res = sql_query('SELECT link_id, link_src_cat_id, link_el_id FROM sb_catlinks WHERE link_id IN '.$link_id_in_str);
	    if ($res)
	    {
    		sql_query('LOCK TABLES sb_catlinks WRITE, sb_system_log WRITE, sb_catchanges WRITE');
    	    foreach ($res as $value)
    		{
    		    list($link_id, $link_src_cat_id, $link_el_id) = $value;

    		    if ($link_src_cat_id == 0)
    		    {
    		        // не ссылка, обновляем связи ссылок на элемент
    		        sql_query('UPDATE sb_catlinks SET link_src_cat_id=?d WHERE link_el_id=?d AND link_src_cat_id=?d', $_GET['new_cat_id'], $link_el_id, $_GET['old_cat_id']);
    				sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $link_el_id, $cat_ident, $date, $_SESSION['sbAuth']->getUserId(), 'cut');
    		    }

    		    // переносим элемент из одного раздела в другой
    		    sql_query('UPDATE sb_catlinks SET link_cat_id=?d WHERE link_id=?d', $_GET['new_cat_id'], $link_id);

    		    if ($plugin_title != '' && isset($els_title[$link_el_id]))
    		    {
                    // запись в системный журнал
                    sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_CUT_ELEM, $els_title[$link_el_id], $old_cat_title, $new_cat_title, $plugin_title));
    		    }
    		}
    		sql_query('UNLOCK TABLES');

    		echo 'TRUE';
	    }
	}
	else if ($_GET['action'] == 'copy')
	{
	    $elems = '';
		$res = sql_query('SELECT link_id, link_src_cat_id, link_el_id FROM sb_catlinks WHERE link_id IN '.$link_id_in_str);
		if ($res)
		{
    		sql_query('LOCK TABLES '.$table.' WRITE, sb_catlinks WRITE, sb_system_log WRITE, sb_catchanges WRITE');
    	    foreach ($res as $value)
    		{
    		    list($link_id, $link_src_cat_id, $link_el_id) = $value;

    		    if ($link_src_cat_id == 0)
    	        {
    	            // не ссылка, создаем копию элемента
    			    $new_el_id = $GLOBALS['sbSql']->duplicateRow($link_el_id, $id_field, $table, isset($_GET['null_fields']) ? $_GET['null_fields'] : '');
    			    if (!$new_el_id)
    			    {
    			        if ($plugin_title != '' && isset($els_title[$link_el_id]))
    			        {
    			            sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_COPY_ELEM_ERROR, $els_title[$link_el_id], $old_cat_title, $plugin_title), SB_MSG_WARNING);
    			        }
    			        continue;
    			    }
    			    $elems .= '&ne['.$link_id.']='.$new_el_id;
    	        }
    			else
    			{
    			    $new_el_id = $link_el_id;
    			    $elems .= '&nl['.$link_id.']='.$new_el_id;
    			}

    			sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id)
    			                            VALUES (?d, ?d, ?d)',
    			                            $_GET['new_cat_id'], $new_el_id, $link_src_cat_id);

    			sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $new_el_id, $cat_ident, $date, $_SESSION['sbAuth']->getUserId(), 'add');

    			if ($plugin_title != '' && isset($els_title[$link_el_id]))
    		    {
                    sb_add_system_message(sprintf($link_src_cat_id == 0 ? PL_CATEGS_SYSLOG_COPY_ELEM : PL_CATEGS_SYSLOG_COPY_LINK, $els_title[$link_el_id], $old_cat_title, $new_cat_title, $plugin_title));
    		    }
    		}
    		sql_query('UNLOCK TABLES');

    		echo $elems;
		}
	}
}

function fCategs_Paste_Links()
{
    if (!isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['plugin_ident']) || !$_SESSION['sbPlugins']->isRightAvailable($_GET['plugin_ident'], 'elems_edit'))
    {
        echo 'FALSE';
        return;
    }

    if (!fCategs_Check_Rights($_GET['cat_id']))
    {
        echo 'FALSE';
        return;
    }

    $date = time();

	$link_id_in_str = '(0';
	$link_el_id_in_str = '(0';
	foreach($_GET['e'] as $link_id => $el_id)
	{
        $link_id_in_str .= ','.intval($link_id);
        $link_el_id_in_str .= ','.intval($el_id);
	}
    $link_id_in_str .= ')';
    $link_el_id_in_str .= ')';

	$plugin_title = $_GET['plugin'];
    $table = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['table']);
    $id_field = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['id_field']);
    $title_field = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['title_field']);

    $cat_title = '';
    if ($plugin_title != '')
    {
        $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
        if ($res)
        {
    	   list($cat_title) = $res[0];
        }
        else
        {
            echo 'FALSE';
            sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_PASTE_ERROR, $plugin_title), SB_MSG_WARNING);
            return;
        }
    }

    $els_title = array();
    $res = sql_query('SELECT ?#, ?# FROM ?# WHERE ?# IN '.$link_el_id_in_str, $id_field, $title_field, $table, $id_field);
    if ($res)
    {
        foreach ($res as $value)
        {
            list($id, $title) = $value;
            $els_title[$id] = $title;
        }
    }

    $res = sql_query('SELECT link_id, link_cat_id, link_el_id, link_src_cat_id FROM sb_catlinks WHERE link_id IN '.$link_id_in_str);
    if ($res)
    {
        foreach ($res as $value)
        {
            list($link_id, $link_cat_id, $link_el_id, $link_src_cat_id) = $value;

            $src_cat_id = $link_src_cat_id == 0 ? $link_cat_id : $link_src_cat_id;

            sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id)
    			                            VALUES (?d, ?d, ?d)',
    			                            $_GET['cat_id'], $link_el_id, $src_cat_id);

    	    if ($plugin_title != '' && isset($els_title[$link_el_id]))
    	    {
                sb_add_system_message(sprintf(PL_CATEGS_SYSLOG_CREATE_LINK, $els_title[$link_el_id], $cat_title, $plugin_title));
    	    }
        }
    }
}

function fCategs_Get_Categs()
{
    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');

    $categs = new sbJustCategs($_GET['ident']);

    if (isset($_GET['multi']) && $_GET['multi'] == 1)
    {
    	$ids = array();
	    if (isset($_GET['id']) && $_GET['id'] != '')
	    {
	        $ids = explode('^', trim($_GET['id'], '^'));
	    }

	    $js_str = '
        function chooseCat()
        {
            var res = new Object();
            res.id = sbCatTree.getAllSelected();
            res.text = sbCatTree.getAllSelectedText();

            sbReturnValue(res);
        }';

	    $categs->mCategsSelectedIds = $ids;
	    $categs->mCategsMultiSelect = true;
    }
    else
    {
    	$js_str = '
        function chooseCat()
        {
            var res = new Object();
            res.id = sbCatTree.getSelectedItemId();
            res.text = sbCatTree.getItemText(res.id);

            sbReturnValue(res);
        }';

    	$categs->mCategsSelectedId = intval($_GET['id']);
    }

    $footer_str = '<table cellspacing="0" cellpadding="7" width="100%" class="form">
    <tr><td class="footer" style="padding-top: 2px;">
        <div class="footer" style="margin: 0;">
            <button onclick="chooseCat();">'.KERNEL_CHOOSE.'</button>&nbsp;&nbsp;&nbsp;
            <button onclick="sbCloseDialog();">'.KERNEL_CANCEL.'</button>
        </div>
    </td></tr></table>';

    if (isset($_GET['cat_id']) && $_GET['cat_id'] != '')
    {
        $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
        if ($res)
        {
            list($left, $right) = $res[0];
            $res = sql_query('SELECT cat_id FROM sb_categs WHERE (cat_left < ?d OR cat_left > ?d AND cat_right > ?d) AND cat_ident = ?', $left, $left, $right, $_GET['ident']);
            if ($res)
            {
                $not_show = array();
                foreach ($res as $value)
                {
                    $not_show[] = $value[0];
                }
                $categs->mCategsNeverShowCats = $not_show;
            }
        }
    }
    elseif ($_GET['ident'] == 'pl_pages')
    {
        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident=? AND cat_level=0', 'pl_pages');
        if ($res)
        {
            $categs->mCategsNeverShowCats = array($res[0][0]);
        }
    }

    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsCount = false;

    $categs->mCategsJavascriptStr = $js_str;

    $categs->showTree($footer_str);
    $categs->init();
}

function fCategs_Changes()
{
	require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
	$total = true;
    $pager = new sbDBPager('ch', 7, 20, 'thisDialog');

	$res = $pager->init($total, 'SELECT ch.change_user_id, ch.change_date, ch.action, u.u_login FROM sb_catchanges ch LEFT JOIN sb_users u ON u.u_id=ch.change_user_id WHERE ch.el_id=?d AND ch.cat_ident=? ORDER BY ch.change_date DESC', $_GET['el_id'], $_GET['cat_ident']);
	if (!$res)
	{
		sb_show_message(PL_CATEGS_CHANGES_NO_HISTORY, true, 'warning');
		echo '<br /><br /><a href="javascript:window.close();">[ '.KERNEL_CLOSE.' ]</a><br /><br />';

		return;
	}

	$num_list = $pager->show();

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
    $layout->mTableWidth = '95%';

    $view_info = $_SESSION['sbPlugins']->isRightAvailable('pl_users', 'read');

    $values = array();
	foreach ($res as $value)
	{
		list($user_id, $change_date, $action, $login) = $value;

		if (is_null($login))
			$login = '-';

		if (($_SESSION['sbAuth']->isAdmin() || $view_info) && $login != '-')
			$login = '<a href="javascript:void(0);" onclick="sbShowModalDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_kernel_user_info&id='.$user_id.'\', \'resizable=1,width=800,height=600\');">'.$login.'</a>';

		switch ($action)
		{
			case 'add':
				$action = PL_CATEGS_CHANGES_ACTION_ADD;
				break;

			case 'edit':
				$action = PL_CATEGS_CHANGES_ACTION_EDIT;
				break;

			case 'cut':
				$action = PL_CATEGS_CHANGES_ACTION_CUT;
				break;
		}

		$values[] = array($login, date('d.m.Y '.KERNEL_IN.' H:i:s', $change_date), $action);
	}

    $labels = array(PL_CATEGS_CHANGES_USER, PL_CATEGS_CHANGES_DATE, PL_CATEGS_CHANGES_ACTION);
    $fld = new sbLayoutTable($labels, $values);
    $fld->mAlign = array('center', 'center', 'center');
    $layout->addField('', $fld);

    echo '<br /><br />';

    $layout->show();

    echo '<br />'.$num_list.'<br /><br /><a href="javascript:window.close();">[ '.KERNEL_CLOSE.' ]</a><br /><br />';
}

/**
 * Диалоговое окно связи компонента "Вывод разделов" со страницей
 *
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $temps_ident Идентификатор разделов макета дизайна.
 * @param string $temps_menu_item Название пункта меню, где происходит управление макетами
 * 				 дизайна (например, "Новостная лента - Вывод разделов").
 * @param int $pm_id Идентификатор модуля (для конструктора модулей только).
 */
function fCategs_Get_Elem_Categs($plugin_ident, $temps_ident, $temps_menu_item, $pm_id = 0)
{
    $pm_settings = array();
    $pm_elems_settings = array();

    if ($pm_id > 0)
    {
        $res = sql_query('SELECT pm_title, pm_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);

        if ($res)
        {
            list($pm_title, $pm_settings, $pm_elems_settings) = $res[0];

            $pm_settings = unserialize($pm_settings);
            $pm_elems_settings = unserialize($pm_elems_settings);
        }
    }

	require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');
    $params = unserialize($_GET['params']);

    if (!isset($params['parent_selection']))
    	$params['parent_selection'] = 1;

    if (isset($params['count']) && $params['count'] <= 0)
    	$params['count'] = '';

    if (!isset($params['query_string']))
    	$params['query_string'] = 1;
    
    if(!isset($params['cache_not_url']))
    {
        $params['cache_not_url'] = 0;
    }
    
    if(!isset($params['cache_not_get']))
    {
        $params['cache_not_get'] = 0;
    }
    
    if(!isset($params['cache_not_get_list']))
    {
        $params['cache_not_get_list'] = '';
    }
    
    if(!isset($params['use_component_cache']))
    {
        $params['use_component_cache'] = 0;
    }

    echo '<script>
        function chooseCat()
        {
            var res = new Object();
            var params = new Array();

            var el_cats = sbCatTree.getAllSelected();
            if (el_cats.length == 0)
            {
                alert("'.PL_CATEGS_ELEM_CATEGS_NO_CATEGS_MSG.'");
                return;
            }

            params["ids"] = el_cats;
            var el_temp = sbGetE("temp_id");
            if (el_temp)
            {
                params["temp_id"] = el_temp.value;
            }
            else
            {
                alert("'.PL_CATEGS_ELEM_CATEGS_NO_TEMPS_MSG.'");
                return;
            }

            var el_from = sbGetE("spin_from");
            var el_to = sbGetE("spin_to");
            if (el_to.value <= 0) el_to.value = "";

            if (el_to.value != "" && parseInt(el_from.value) > parseInt(el_to.value))
            {
                alert("'.PL_CATEGS_ELEM_CATEGS_FROM_TO_ERROR.'");
                return;
            }

            params["from"] = el_from.value;
            params["to"] = el_to.value;
            params["count"] = sbGetE("spin_count").value > 0 && sbGetE("spin_count").value != "" ? sbGetE("spin_count").value : "0";

            params["parent_selection"] = sbGetE("parent_selection").checked ? 1 : 0;
            params["parent_link"] = sbGetE("parent_link").checked ? 1 : 0;
            params["query_string"] = sbGetE("query_string").checked ? 1 : 0;
            params["show_closed"] = sbGetE("show_closed").checked ? 1 : 0;
            params["show_hidden"] = sbGetE("show_hidden").checked ? 1 : 0;
            params["allow_bbcode"] = sbGetE("allow_bbcode").checked ? 1 : 0;

            var el_page = sbGetE("page");
            if (el_page)
              	params["page"] = el_page.value;
            else
              	params["page"] = "pl_pages";

            var el_sort1 = sbGetE("sort1");
            if (el_sort1)
            {
                var el_order1 = sbGetE("order1");
                var el_sort2 = sbGetE("sort2");
                var el_order2 = sbGetE("order2");
                var el_sort3 = sbGetE("sort3");
                var el_order3 = sbGetE("order3");

                params["sort1"] = el_sort1.value;
                params["sort2"] = el_sort2.value;
                params["sort3"] = el_sort3.value;

                params["order1"] = el_order1.disabled ? "" : el_order1.value;
                params["order2"] = el_order2.disabled ? "" : el_order2.value;
                params["order3"] = el_order3.disabled ? "" : el_order3.value;

                params["subcategs"] = sbGetE("subcategs").checked ? 1 : 0;
                params["elems_show_hidden"] = sbGetE("elems_show_hidden").checked ? 1 : 0;

                var el_cena = sbGetE("cena");
                if (el_cena)
                    params["cena"] = el_cena.value;

                var elem_page = sbGetE("elem_page");
                if(elem_page)
                    params["elem_page"] = elem_page.value;
                else
                    params["elem_page"] = "";
            }

            '.($pm_id > 0 ? 'params["pm_id"] = "'.$pm_id.'";' : '').'

            params["use_component_cache"] = (sbGetE("use_component_cache").checked ? 1 : 0);
            params["cache_not_url"] = (sbGetE("cache_not_url").checked ? 1 : 0);
            params["cache_not_get"] = (sbGetE("cache_not_get").checked ? 1 : 0);
            params["cache_not_get_list"] = sbGetE("cache_not_get_list").value;
            
            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);

            sbReturnValue(res);
        }

        function changeSort(el, el_id)
        {
            var order_el = sbGetE("order" + el_id);
            if (el.value == "RAND()" || el.value == "")
            {
                order_el.disabled = true;
            }
            else
            {
                order_el.disabled = false;
            }
        }
        
        function showHideGETList(checked)
        {
            if(checked)
            {
                sbGetE("cache_not_get_list").disabled = false;
            }
            else
            {
                sbGetE("cache_not_get_list").disabled = true;
            }
        }
        
        function showHideCacheParams(checked)
        {
            if(!checked)
            {
                sbGetE("cache_not_url").disabled = false;
                var get = sbGetE("cache_not_get");
                get.disabled = false;
                if(get.checked)
                    sbGetE("cache_not_get_list").disabled = false;
            }
            else
            {
                sbGetE("cache_not_url").disabled = true;
                sbGetE("cache_not_get").disabled = true;
                sbGetE("cache_not_get_list").disabled = true;
            }
        }
    </script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $layout->mTableWidth = '100%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_CATEGS_ELEM_CATEG_CATEGS_TAB);

    $categs = new sbJustCategs($plugin_ident);
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsCount = false;
    $categs->mCategsSelectedIds = (isset($params['ids']) && $params['ids'] != '' ? explode('^', $params['ids']) : array());

    $layout->addField('', new sbLayoutHTML($categs->showTree('', '', '', false), true));
    $categs->init();

    $layout->addTab(PL_CATEGS_ELEM_CATEG_PROPS_TAB);
    $layout->addHeader(PL_CATEGS_ELEM_CATEG_PROPS_TAB);

    $options = array();
    if ($plugin_ident != 'pl_pages')
    {
	    $res = sql_query('SELECT categs.cat_title, temps.ctl_id, temps.ctl_title FROM sb_categs categs, sb_catlinks links, sb_categs_temps_list temps WHERE temps.ctl_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, temps.ctl_title', $temps_ident);
	    if ($res)
	    {
	        $old_cat_title = '';
	        foreach ($res as $value)
	        {
	            list($cat_title, $ctl_id, $ctl_title) = $value;
	            if ($old_cat_title != $cat_title)
	            {
	                $options[uniqid()] = '-'.$cat_title;
	                $old_cat_title = $cat_title;
	            }
	            $options[$ctl_id] = $ctl_title;
	        }
	    }

	    if (count($options) > 0)
	    {
	    	$fld = new sbLayoutSelect($options, 'temp_id');
	        if (isset($_GET['temp_id']))
	        {
	            $fld->mSelOptions = array($_GET['temp_id']);
	        }
	    }
	    else
	    {
	        $fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_CATEGS_ELEM_CATEG_NO_TEMPS_MSG, $temps_menu_item).'</div>', '', '', false);
	    }
    }
    else
    {
	    $res = sql_query('SELECT categs.cat_title, temps.mt_id, temps.mt_title FROM sb_categs categs, sb_catlinks links, sb_menu_temps temps WHERE temps.mt_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, temps.mt_title', $temps_ident);
	    if ($res)
	    {
	        $old_cat_title = '';
	        foreach ($res as $value)
	        {
	            list($cat_title, $mt_id, $mt_title) = $value;
	            if ($old_cat_title != $cat_title)
	            {
	                $options[uniqid()] = '-'.$cat_title;
	                $old_cat_title = $cat_title;
	            }
	            $options[$mt_id] = $mt_title;
	        }
	    }

	    if (count($options) > 0)
	    {
	    	$fld = new sbLayoutSelect($options, 'temp_id');
	        if (isset($_GET['temp_id']))
	        {
	            $fld->mSelOptions = array($_GET['temp_id']);
	        }
	    }
	    else
	    {
	        $fld = new sbLayoutLabel('<div class="hint_div">'.$temps_menu_item.'</div>', '', '', false);
	    }
    }

    $layout->addField(PL_CATEGS_ELEM_TEMP, $fld);
    $layout->addField('', new sbLayoutDelim());

    $fld1 = new sbLayoutInput('input', (isset($params['from']) ? $params['from'] : '2'), 'from', 'spin_from', 'style="width: 50px;"');
    $fld1->mMinValue = 1;
    $fld2 = new sbLayoutInput('input', (isset($params['to']) ? $params['to'] : ''), 'to', 'spin_to', 'style="width: 50px;"');

    $html = $fld1->getJavaScript().'<table cellpadding="0" cellspacing="0"><tr><td>'.$fld1->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_TO.'&nbsp;&nbsp;</td><td>'.$fld2->getField().'</td></tr></table>
              <div class="hint_div">'.PL_CATEGS_ELEM_CATEGS_FROM_TO_HINT.'</div>';

    $fld = new sbLayoutHTML($html);
    $layout->addField(PL_CATEGS_ELEM_CATEGS_FROM_TO, $fld);

    $fld = new sbLayoutInput('input', (isset($params['count']) ? $params['count'] : ''), 'count', 'spin_count', 'style="width: 50px;"');
    $layout->addField(PL_CATEGS_ELEM_CATEGS_COUNT, $fld);

    $fld = new sbLayoutInput('checkbox', '1', 'parent_link', '', (isset($params['parent_link']) && $params['parent_link'] == 1 ? 'checked="checked"' : ''));
    $fld->mHTML = '<div class="hint_div">'.PL_CATEGS_ELEM_CATEGS_LINK_DESC.'</div>';
    $layout->addField(PL_CATEGS_ELEM_CATEGS_LINK, $fld);

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_CATEGS_ELEM_CATEGS_PARENT_SELECTION, new sbLayoutInput('checkbox', '1', 'parent_selection', '', (isset($params['parent_selection']) && $params['parent_selection'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_CATEGS_ELEM_CATEGS_QUERY_STRING, new sbLayoutInput('checkbox', '1', 'query_string', '', (isset($params['query_string']) && $params['query_string'] == 1 ? 'checked="checked"' : '')));

    $fld = new sbLayoutInput('checkbox', '1', 'show_closed', '', (isset($params['show_closed']) && $params['show_closed'] == 1 ? 'checked="checked"' : ''));
    $fld->mHTML = '<div class="hint_div">'.PL_CATEGS_ELEM_CATEGS_SHOW_CLOSED_DESC.'</div>';
    $layout->addField(PL_CATEGS_ELEM_CATEGS_SHOW_CLOSED, $fld);

    $layout->addField(PL_CATEGS_ELEM_CATEGS_SHOW_HIDDEN, new sbLayoutInput('checkbox', '1', 'show_hidden', '', (isset($params['show_hidden']) && $params['show_hidden'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_CATEGS_ELEM_CATEGS_BBCODE_ALLOW, new sbLayoutInput('checkbox', '1', 'allow_bbcode', '', (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1 ? 'checked="checked"' : '')));

    if ($plugin_ident != 'pl_pages')
    {
    	$layout->addField('', new sbLayoutDelim());
    	$layout->addField(PL_CATEGS_ELEM_CATEGS_PAGE, new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 450px;"'));

        // Настройки вывода элементов
        $layout->addTab(PL_CATEGS_ELEM_CATEG_ELEMS_TAB);
        $layout->addHeader(PL_CATEGS_ELEM_CATEG_ELEMS_TAB);

        $fld = new sbLayoutPage(isset($params['elem_page']) ? $params['elem_page'] : '', 'elem_page', '', 'style="width: 400px;"');
        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_PAGE, $fld);

        if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && $pm_id > 0)
    	{
    		$layout->addField('', new sbLayoutDelim());

    		$options = array();
    		$options[1] = PL_PLUGIN_MAKER_H_CENA_1;
    		$options[2] = PL_PLUGIN_MAKER_H_CENA_2;
    		$options[3] = PL_PLUGIN_MAKER_H_CENA_3;
    		$options[4] = PL_PLUGIN_MAKER_H_CENA_4;
    		$options[5] = PL_PLUGIN_MAKER_H_CENA_5;

    		$fld = new sbLayoutSelect($options, 'cena');
    		$fld->mSelOptions = isset($params['cena']) ? array($params['cena']) : array();
    		$layout->addfield(PL_PLUGIN_MAKER_H_ELEM_CENA_LIST_FIELD_TITLE, $fld);
    	}

        $fields = sbPlugins::getSortFields($plugin_ident);

        if($pm_id > 0)
        {
            if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
                $fields['p.p_sort'] = PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_SORT;
        }

        $order = array('DESC' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_DESC,
                       'ASC'  => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_ASC);

        $fld1 = new sbLayoutSelect($fields, 'sort1', '', 'onchange="changeSort(this, 1);"');
        $fld1->mSelOptions = array((isset($params['sort1']) ? $params['sort1'] : 'p.p_title'));
        $fld2 = new sbLayoutSelect($order, 'order1', '', (isset($params['sort1']) && $params['sort1'] == 'RAND()' ? 'disabled="disabled"' : ''));
        $fld2->mSelOptions = array((isset($params['order1']) && $params['order1'] != '' ? $params['order1'] : 'ASC'));
        $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

        $layout->addField('', new sbLayoutDelim());
        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_FIELD.' 1', $fld1);

        $fld1 = new sbLayoutSelect($fields, 'sort2', '', 'onchange="changeSort(this, 2);"');
        $fld1->mSelOptions = array((isset($params['sort2']) && $params['sort1'] != '' ? $params['sort2'] : ''));
        $fld2 = new sbLayoutSelect($order, 'order2', '', (isset($params['sort1']) && $params['sort1'] == 'RAND()' ? 'disabled="disabled"' : ''));
        $fld2->mSelOptions = array((isset($params['order2']) && $params['order1'] != '' ? $params['order2'] : ''));
        $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_FIELD.' 2', $fld1);

        $fld1 = new sbLayoutSelect($fields, 'sort3', '', 'onchange="changeSort(this, 3);"');
        $fld1->mSelOptions = array((isset($params['sort3']) && $params['sort1'] != '' ? $params['sort3'] : ''));
        $fld2 = new sbLayoutSelect($order, 'order3', '', (isset($params['sort1']) && $params['sort1'] == 'RAND()' ? 'disabled="disabled"' : ''));
        $fld2->mSelOptions = array((isset($params['order3']) && $params['order3'] != '' ? $params['order3'] : ''));
        $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_FIELD.' 3', $fld1);

        $layout->addField('', new sbLayoutDelim());
        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SUBCATEGS, new sbLayoutInput('checkbox', '1', 'subcategs', '', (isset($params['subcategs']) && $params['subcategs'] == 1 ? 'checked="checked"' : '')));
        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SHOW_HIDDEN, new sbLayoutInput('checkbox', '1', 'elems_show_hidden', '', (isset($params['elems_show_hidden']) && $params['elems_show_hidden'] == 1 ? 'checked="checked"' : '')));
    }
    
    //Кеширование
    $layout->addTab(PL_CATEGS_CACHE_TAB);
    $layout->addHeader(PL_CATEGS_CACHE_TAB);
    
    $layout->addField(PL_CATEGS_USE_COMPONENT_CACHE, new sbLayoutInput('checkbox', '1', 'use_component_cache', '', ($params['use_component_cache'] == 1 ? 'checked="checked"' : '').' onclick=showHideCacheParams(this.checked)'));
    
    $layout->addField(PL_CATEGS_CACHE_NOT_URL, new sbLayoutInput('checkbox', '1', 'cache_not_url', '', ($params['cache_not_url'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"')));
    $layout->addField(PL_CATEGS_CACHE_NOT_GET, new sbLayoutInput('checkbox', '1', 'cache_not_get', '', ($params['cache_not_get'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"').' onclick=showHideGETList(this.checked)'));
    
    $fld = new sbLayoutInput('text', $params['cache_not_get_list'], 'cache_not_get_list', 'cache_not_get_list', (($params['cache_not_get'] == 0 || $params['use_component_cache'] == 1) ? 'disabled="disabled" ' : '').'style="width:400px;"');
    $fld->mHTML = '<div class="hint_div">'.PL_CATEGS_CACHE_NOT_GET_LIST_HINT.'</div>';
    $layout->addField(PL_CATEGS_CACHE_NOT_GET_LIST, $fld);

    $layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="chooseCat();"'.(count($options) > 0 ? '' : ' disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

/**
 * Выводит информацию о компоненте "Вывод разделов"
 *
 * @param string $params Параметры компонента.
 * @param string $temps_ident Идентификатор разделов макета дизайна.
 */
function fCategs_Elem_Categs_Info($params, $temps_ident)
{
	$in_str = implode(',', explode('^', $params['ids']));
    $in_str = '(0,'.$in_str.')';

    $categs_str = '';
    $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id IN '.$in_str.' ORDER BY cat_left');
    if ($res)
    {
        foreach ($res as $value)
        {
            $categs_str .= $value[0].', ';
        }
        $categs_str = substr($categs_str, 0, -2);
    }

    if (isset($params['page']) && $params['page'] == 'pl_pages')
    {
    	$res = sql_query('SELECT categs.cat_title, temps.mt_title FROM sb_categs categs, sb_catlinks links, sb_menu_temps temps
    	                  WHERE temps.mt_id=?d AND links.link_el_id=temps.mt_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=?', $params['temp_id'], $temps_ident);
    }
    else
    {
    	$res = sql_query('SELECT categs.cat_title, temps.ctl_title FROM sb_categs categs, sb_catlinks links, sb_categs_temps_list temps
								WHERE temps.ctl_id=?d AND links.link_el_id=temps.ctl_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=?', $params['temp_id'], $temps_ident);
    }

    echo '<div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEG_CATEGS_TAB.':</b> '.($categs_str != '' ? $categs_str : '<span style="color:red;">'.PL_CATEGS_ELEM_CATEGS_INFO_CATEGS_WAS_DELETED_MSG.'</span>').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_CATEGS_ELEM_CATEGS_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' && $params['page'] != 'pl_pages' ? '<div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_INFO_FROM_TO.':</b> '.KERNEL_FROM.' '.$params['from'].' '.KERNEL_TO.' '.$params['to'].'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_COUNT.':</b> '.($params['count'] > 0 ? $params['count'] : '').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_INFO_LINK.':</b> '.(isset($params['parent_link']) && $params['parent_link'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_INFO_PARENT_SELECTION.':</b> '.(isset($params['parent_selection']) && $params['parent_selection'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_SHOW_CLOSED.':</b> '.(isset($params['show_closed']) && $params['show_closed'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_SHOW_HIDDEN.':</b> '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>';
}

/**
 * Диалоговое окно связи компонента "Вывод выбранного раздела" со страницей
 *
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $temps_ident Идентификатор разделов макета дизайна.
 * @param string $temps_menu_item Название пункта меню, где происходит управление макетами
 * 				 дизайна (например, "Новостная лента - Вывод выбранного раздела").
 * @param int $pm_id Идентификатор модуля (для конструктора модулей только).
 */
function fCategs_Get_Elem_Sel_Cat($plugin_ident, $temps_ident, $temps_menu_item, $pm_id = 0)
{
	require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');
    $params = unserialize($_GET['params']);

    if (!isset($params['query_string']))
    	$params['query_string'] = 1;
    
    if(!isset($params['cache_not_url']))
    {
        $params['cache_not_url'] = 0;
    }
    
    if(!isset($params['cache_not_get']))
    {
        $params['cache_not_get'] = 0;
    }
    
    if(!isset($params['cache_not_get_list']))
    {
        $params['cache_not_get_list'] = '';
    }
    
    if(!isset($params['use_component_cache']))
    {
        $params['use_component_cache'] = 0;
    }

    echo '<script>
        function chooseCat()
        {
            var res = new Object();
            var params = new Array();

            var el_temp = sbGetE("temp_id");
            if (el_temp)
            {
                params["temp_id"] = el_temp.value;
            }
            else
            {
                alert("'.PL_CATEGS_ELEM_SEL_CAT_NO_TEMPS_ALERT.'");
                return;
            }

            params["query_string"] = sbGetE("query_string").checked ? 1 : 0;
			params["allow_bbcode"] = sbGetE("allow_bbcode").checked ? 1 : 0;
            params["page"] = sbGetE("page").value;

            '.($pm_id > 0 ? 'params["pm_id"] = "'.$pm_id.'";' : '').'
                
            params["use_component_cache"] = (sbGetE("use_component_cache").checked ? 1 : 0);
            params["cache_not_url"] = (sbGetE("cache_not_url").checked ? 1 : 0);
            params["cache_not_get"] = (sbGetE("cache_not_get").checked ? 1 : 0);
            params["cache_not_get_list"] = sbGetE("cache_not_get_list").value;

            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);

            sbReturnValue(res);
        }
        
        function showHideGETList(checked)
        {
            if(checked)
            {
                sbGetE("cache_not_get_list").disabled = false;
            }
            else
            {
                sbGetE("cache_not_get_list").disabled = true;
            }
        }
        
        function showHideCacheParams(checked)
        {
            if(!checked)
            {
                sbGetE("cache_not_url").disabled = false;
                var get = sbGetE("cache_not_get");
                get.disabled = false;
                if(get.checked)
                    sbGetE("cache_not_get_list").disabled = false;
            }
            else
            {
                sbGetE("cache_not_url").disabled = true;
                sbGetE("cache_not_get").disabled = true;
                sbGetE("cache_not_get_list").disabled = true;
            }
        }
    </script>
    <br />';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_CATEGS_ELEM_CATEG_PROPS_TAB);
    $layout->addHeader(PL_CATEGS_ELEM_CATEG_PROPS_TAB);

    $options = array();
    $res = sql_query('SELECT categs.cat_title, temps.ctf_id, temps.ctf_title FROM sb_categs categs, sb_catlinks links, sb_categs_temps_full temps WHERE temps.ctf_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, temps.ctf_title', $temps_ident);
    if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $ctf_id, $ctf_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$ctf_id] = $ctf_title;
        }
    }

    if (count($options) > 0)
    {
    	$fld = new sbLayoutSelect($options, 'temp_id');
        if (isset($_GET['temp_id']))
        {
            $fld->mSelOptions = array($_GET['temp_id']);
        }
    }
    else
    {
        $fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_CATEGS_ELEM_SEL_CAT_NO_TEMPS_MSG, $temps_menu_item).'</div>', '', '', false);
    }

    $layout->addField(PL_CATEGS_ELEM_TEMP, $fld);
    $layout->addField(PL_CATEGS_ELEM_CATEGS_PAGE, new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 450px;"'));
    $layout->addField(PL_CATEGS_ELEM_CATEGS_QUERY_STRING, new sbLayoutInput('checkbox', '1', 'query_string', '', (isset($params['query_string']) && $params['query_string'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_CATEGS_ELEM_CATEGS_BBCODE_ALLOW, new sbLayoutInput('checkbox', '1', 'allow_bbcode', '', (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1 ? 'checked="checked"' : '')));

    //Кеширование
    $layout->addTab(PL_CATEGS_CACHE_TAB);
    $layout->addHeader(PL_CATEGS_CACHE_TAB);
    
    $layout->addField(PL_CATEGS_USE_COMPONENT_CACHE, new sbLayoutInput('checkbox', '1', 'use_component_cache', '', ($params['use_component_cache'] == 1 ? 'checked="checked"' : '').' onclick=showHideCacheParams(this.checked)'));
    
    $layout->addField(PL_CATEGS_CACHE_NOT_URL, new sbLayoutInput('checkbox', '1', 'cache_not_url', '', ($params['cache_not_url'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"')));
    $layout->addField(PL_CATEGS_CACHE_NOT_GET, new sbLayoutInput('checkbox', '1', 'cache_not_get', '', ($params['cache_not_get'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"').' onclick=showHideGETList(this.checked)'));
    
    $fld = new sbLayoutInput('text', $params['cache_not_get_list'], 'cache_not_get_list', 'cache_not_get_list', (($params['cache_not_get'] == 0 || $params['use_component_cache'] == 1) ? 'disabled="disabled" ' : '').'style="width:400px;"');
    $fld->mHTML = '<div class="hint_div">'.PL_CATEGS_CACHE_NOT_GET_LIST_HINT.'</div>';
    $layout->addField(PL_CATEGS_CACHE_NOT_GET_LIST, $fld);
    
    $layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="chooseCat();"'.(count($options) > 0 ? '' : ' disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

/**
 * Выводит информацию о компоненте "Вывод выбранного раздела"
 *
 * @param string $params Параметры компонента.
 * @param string $temps_ident Идентификатор разделов макета дизайна.
 */
function fCategs_Elem_Sel_Cat_Info($params, $temps_ident)
{
	$res = sql_query('SELECT categs.cat_title, temps.ctf_title FROM sb_categs categs, sb_catlinks links, sb_categs_temps_full temps
							WHERE temps.ctf_id=?d AND links.link_el_id=temps.ctf_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=?', $params['temp_id'], $temps_ident);

    echo '<div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_CATEGS_ELEM_CATEGS_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_CATEGS_ELEM_CATEGS_PAGE.':</b> '.$params['page'].'</div>' : '');
}

/**
 * Функция получения информации об элементе
 *
 * @param array $args Параметры элемента.
 * @param string $el_ident Идентификатор компонента, для вывода связанных страниц и макетов дизайна.
 * @param string $cat_ident Идентификатор разделов модуля, для отслеживания связи макета дизайна с меню.
 * @param bool $sel Получаем информацию для макета дизайна выбранного раздела (TRUE) или дерева разделов (FALSE).
 *
 * @return string Строка информации об элементе.
 */
function fCategs_Design_Get($args, $el_ident, $cat_ident, $sel=false)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.($sel ? $args['ctf_title'] : $args['ctl_title']).'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = $sel ? intval($args['ctf_id']) : intval($args['ctl_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id ORDER BY pages.p_name LIMIT 4', 'page', $id, $el_ident);

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id ORDER BY temps.t_name LIMIT 4', 'temp', $id, $el_ident);

    $menu = false;
    if (!$sel)
    {
    	$menu = sql_query('SELECT COUNT(*) FROM sb_menu WHERE m_type=? AND m_props LIKE \'%s:5:"ident";s:'.sb_strlen($cat_ident).':"'.preg_replace('/[^a-zA-Z0-9_\-]+/', '', $cat_ident).'";%\' AND m_props LIKE \'%s:7:"temp_id";s:'.sb_strlen($args['ctl_id']).':"'.intval($args['ctl_id']).'";%\'', 'categs');
    }

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if (!$sel)
    {
	    if ($menu && $menu[0][0] > 0)
	    {
	    	$result .= KERNEL_USED_IN_MENU.': <span style="color: green;">'.KERNEL_USED.'</span><br />';
	    }
	    else
	    {
	    	$result .= KERNEL_USED_IN_MENU.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
	    }
    }

    $result .= '</div>';
    return $result;
}

/**
 * Строит окно редактирования макета дизайна вывода разделов
 *
 * @param string $cat_ident Идентификатор раздела.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $submit_event Идентификатор события, отвечающего за отправку формы.
 * @param string $htmlStr Возвращается из функции fCategs_Design_Edit_Submit.
 * @param string $footerStr Возвращается из функции fCategs_Design_Edit_Submit.
 * @param bool $add_level Возвращается из функции fCategs_Design_Edit_Submit (TRUE, если следует добавить еще один уровень).
 *
 */
function fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event, $htmlStr = '', $footerStr = '', $add_level=false)
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], $cat_ident))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_query('SELECT ctl_title, ctl_lang, ctl_levels, ctl_categs_temps, ctl_checked, ctl_perpage, ctl_pagelist_id FROM sb_categs_temps_list WHERE ctl_id=?d', $_GET['id']);

        if ($result)
        {
            list($ctl_title, $ctl_lang, $ctl_levels, $ctl_categs_temps, $ctl_checked, $ctl_perpage, $ctl_pagelist_id) = $result[0];
        }
        else
        {
            sb_show_message(PL_CATEGS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($ctl_levels != '')
            $ctl_levels = unserialize($ctl_levels);
        else
            $ctl_levels = array();

        if ($ctl_categs_temps != '')
            $ctl_categs_temps = unserialize($ctl_categs_temps);
        else
            $ctl_categs_temps = array();

        if ($ctl_checked != '')
            $ctl_checked = explode(' ', $ctl_checked);
        else
            $ctl_checked = array();
    }
    elseif (count($_POST) > 0)
    {
    	$ctl_checked = array();
    	$ctl_pagelist_id = -1;

        extract($_POST);

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $ctl_title = '';
        $ctl_lang = SB_CMS_LANG;
        $ctl_levels = array();
        $ctl_levels[0] = array();
        $ctl_levels[0]['top'] = '';
        $ctl_levels[0]['sub'] = '';
        $ctl_levels[0]['sub_sel'] = '';
        $ctl_levels[0]['item'] = '';
        $ctl_levels[0]['item_sel'] = '';
        $ctl_levels[0]['bottom'] = '';
        $ctl_levels[0]['delim'] = '';

        $ctl_categs_temps = array();
        $ctl_checked = array();
        $ctl_perpage = 0;
        $ctl_pagelist_id = -1;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("ctl_title");
                if (el_title.value == "")
                {
                     alert("'.PL_CATEGS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }
            function copyTab(tabFrom, tabTo)
            {
            	var tabFrom = sbGetE("sb_tab" + tabFrom);
            	var tabTo = sbGetE("sb_tab" + tabTo);

            	if (tabFrom && tabTo)
            	{
            		var elsFrom = tabFrom.getElementsByTagName("TEXTAREA");
            		var elsTo = tabTo.getElementsByTagName("TEXTAREA");

            		for (var i = 0; i < elsFrom.length; i++)
            		{
            			var editorFrom = eval("window.sbCodeditor_" + elsFrom[i].id.replace(/[^a-zA-Z0-9_\-]+/g, ""));
            			if (editorFrom)
            			{
            				var editorTo = eval("window.sbCodeditor_" + elsTo[i].id.replace(/[^a-zA-Z0-9_\-]+/g, ""));
            				editorTo.setCode(editorFrom.getCode());
            			}
            			else
            			{
            				elsTo[i].value = elsFrom[i].value;
            			}
            		}
            	}
            }
            function sbTabCheck(tab)
            {
                if (window.tabs.getTitle(tab) == "+")
                {
                    main_form.action += "&add_level=1";
                    main_form.onsubmit(null, true);
                    main_form.submit();
                    return false;
                }

                return true;
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event='.$submit_event.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main_form');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '170';

    $layout->addTab(PL_CATEGS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_CATEGS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_CATEGS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ctl_title, 'ctl_title', '', 'style="width:530px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ctl_lang');
    $fld->mSelOptions = array($ctl_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_CATEGS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_CATEGS_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes($plugin_ident, $ctl_checked, 'ctl_checked', true);

    fPager_Design_Get($layout, $ctl_pagelist_id, 'ctl_pagelist_id', $ctl_perpage, 'ctl_perpage', true);

    $tags = array(
        '-',
        '{ID}',
        '{CAT_URL}',
        '{PARENT_ID}',
        '{PARENT_URL}',
        '{TEXT}',
        '{COUNT}',
        '{SUB_COUNT}',
        '{URL}',
        '{CLOSED_ICON}',
        '{CAT_ELEMS}'
    );

    $values = array(
        PL_CATEGS_DESIGN_EDIT_TAB1,
        PL_CATEGS_DESIGN_EDIT_CAT_ID_TAG,
        PL_CATEGS_DESIGN_EDIT_CAT_URL_TAG,
        PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG,
        PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG,
        PL_CATEGS_DESIGN_EDIT_CAT_TITLE_TAG,
        PL_CATEGS_DESIGN_EDIT_CAT_COUNT_TAG,
        PL_CATEGS_DESIGN_EDIT_CAT_SUB_COUNT_TAG,
        PL_CATEGS_DESIGN_EDIT_LINK_TAG,
        PL_CATEGS_DESIGN_EDIT_CLOSED_ICON,
        PL_CATEGS_DESIGN_EDIT_CAT_ELEMS
    );

    $categs_tags = array();
    $categs_tags_values = array();

    $pd_fields = array();
    $res = sql_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);
	if ($res && $res[0][0] != '')
	{
        $pd_fields = unserialize($res[0][0]);
        $layout->getPluginFieldsTags($plugin_ident, $categs_tags, $categs_tags_values, true);
	}

    foreach ($ctl_levels as $key => $value)
    {
        $layout->addTab(PL_CATEGS_DESIGN_EDIT_TAB_FIELD);
        $layout->addHeader(PL_CATEGS_DESIGN_EDIT_TAB_FIELD);

   		if ($key != 0)
		{
			$fld = new sbLayoutHTML('<a href="#" onclick="copyTab('.($key * 2 - 1).', '.($key * 2 + 1).')">'.PL_CATEGS_DESIGN_EDIT_COPY_FROM.'</a>');
			$layout->addField('', $fld);
			$layout->addField('', new sbLayoutDelim());
		}

        $fld = new sbLayoutTextarea(isset($ctl_categs_temps[$key]['closed_icon']) ? $ctl_categs_temps[$key]['closed_icon'] : '', 'ctl_categs_temps['.$key.'][closed_icon]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = array('{ID}', '{CAT_URL}', '{PARENT_ID}', '{PARENT_URL}', '{TEXT}', '{URL}');
		$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_CAT_ID_TAG, PL_CATEGS_DESIGN_EDIT_CAT_URL_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG, PL_CATEGS_DESIGN_EDIT_CAT_TITLE_TAG, PL_CATEGS_DESIGN_EDIT_LINK_TAG);
		$layout->addField(PL_CATEGS_DESIGN_EDIT_CLOSED_ICON, $fld);

		//Выбор макета вывода элементов раздела
        sbPlugins::getPluginDesignTemps($plugin_ident, 'list', $layout, isset($ctl_categs_temps[$key]['elems_temp']) ? $ctl_categs_temps[$key]['elems_temp'] : -1, 'ctl_categs_temps[' . $key . '][elems_temp]', PL_CATEGS_DESIGN_EDIT_CAT_ELEMS_TITLE);

        if (count($pd_fields) > 0)
        {
            $layout->addField('', new sbLayoutDelim());

            $layout->addPluginFieldsTemps($plugin_ident, isset($ctl_categs_temps[$key]) ? $ctl_categs_temps[$key] : array(), 'ctl_', $tags, $values, true, '['.$key.']');
        }

        $layout->addTab(sprintf(PL_CATEGS_DESIGN_EDIT_TAB_LEVEL, $key + 1));
        $layout->addHeader(sprintf(PL_CATEGS_DESIGN_EDIT_TAB_LEVEL, $key + 1));

    	if ($key != 0)
		{
			$fld = new sbLayoutHTML('<a href="#" onclick="copyTab('.($key * 2).', '.($key * 2 + 2).')">'.PL_CATEGS_DESIGN_EDIT_COPY_FROM.'</a>');
			$layout->addField('', $fld);
			$layout->addField('', new sbLayoutDelim());
		}

        $fld = new sbLayoutTextarea($value['top'], 'ctl_levels['.$key.'][top]', '', 'style="width:100%;height:50px;"');
        if ($key == 0)
        {
        	$fld->mTags = array('{PARENT_ID}', '{PARENT_URL}', '{NUM_LIST}');
        	$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG, PL_CATEGS_DESIGN_EDIT_PAGELIST_TAG);
        }
        else
        {
        	$fld->mTags = array('{PARENT_ID}', '{PARENT_URL}');
        	$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG);
        }
        $layout->addField(PL_CATEGS_DESIGN_EDIT_TOP, $fld);

        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($value['sub'], 'ctl_levels['.$key.'][sub]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, array('{SUB_ITEMS}'), $categs_tags);
        $fld->mValues = array_merge($values, array(PL_CATEGS_DESIGN_EDIT_SUBITEMS_TAG), $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_SUB, $fld);

        $fld = new sbLayoutTextarea($value['sub_sel'], 'ctl_levels['.$key.'][sub_sel]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, array('{SUB_ITEMS}'), $categs_tags);
        $fld->mValues = array_merge($values, array(PL_CATEGS_DESIGN_EDIT_SUBITEMS_TAG), $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_SUB_SEL, $fld);

        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($value['item'], 'ctl_levels['.$key.'][item]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, $categs_tags);
        $fld->mValues = array_merge($values, $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_ITEM, $fld);

        $fld = new sbLayoutTextarea($value['item_sel'], 'ctl_levels['.$key.'][item_sel]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, $categs_tags);
        $fld->mValues = array_merge($values, $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_ITEM_SEL, $fld);

        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($value['bottom'], 'ctl_levels['.$key.'][bottom]', '', 'style="width:100%;height:50px;"');
    	if ($key == 0)
        {
        	$fld->mTags = array('{PARENT_ID}', '{PARENT_URL}', '{NUM_LIST}');
        	$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG, PL_CATEGS_DESIGN_EDIT_PAGELIST_TAG);
        }
        else
        {
        	$fld->mTags = array('{PARENT_ID}', '{PARENT_URL}');
        	$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG);
        }
        $layout->addField(PL_CATEGS_DESIGN_EDIT_BOTTOM, $fld);
    }

    if ($add_level)
    {
        $key++;

        $layout->addTab(PL_CATEGS_DESIGN_EDIT_TAB_FIELD);
        $layout->addHeader(PL_CATEGS_DESIGN_EDIT_TAB_FIELD);

        $fld = new sbLayoutHTML('<a href="#" onclick="copyTab('.($key * 2 - 1).', '.($key * 2 + 1).')">'.PL_CATEGS_DESIGN_EDIT_COPY_FROM.'</a>');
		$layout->addField('', $fld);
		$layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea(isset($ctl_categs_temps[$key]['closed_icon']) ? $ctl_categs_temps[$key]['closed_icon'] : '', 'ctl_categs_temps['.$key.'][closed_icon]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = array('{ID}', '{CAT_URL}', '{PARENT_ID}', '{PARENT_URL}', '{TEXT}', '{URL}');
		$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_CAT_ID_TAG, PL_CATEGS_DESIGN_EDIT_CAT_URL_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG, PL_CATEGS_DESIGN_EDIT_CAT_TITLE_TAG, PL_CATEGS_DESIGN_EDIT_LINK_TAG);
		$layout->addField(PL_CATEGS_DESIGN_EDIT_CLOSED_ICON, $fld);

        if (count($pd_fields) > 0)
        {
            $layout->addField('', new sbLayoutDelim());

            $layout->addPluginFieldsTemps($plugin_ident, array(), 'ctl_', $tags, $values, true, '['.$key.']');
        }

        $layout->addTab(sprintf(PL_CATEGS_DESIGN_EDIT_TAB_LEVEL, $key + 1));
        $layout->addHeader(sprintf(PL_CATEGS_DESIGN_EDIT_TAB_LEVEL, $key + 1));

        $fld = new sbLayoutHTML('<a href="#" onclick="copyTab('.($key * 2).', '.($key * 2 + 2).')">'.PL_CATEGS_DESIGN_EDIT_COPY_FROM.'</a>');
		$layout->addField('', $fld);
		$layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea('', 'ctl_levels['.$key.'][top]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array('{PARENT_ID}', '{PARENT_URL}');
        $fld->mValues = array(PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_TOP, $fld);

        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea('', 'ctl_levels['.$key.'][sub]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, array('{SUB_ITEMS}'), $categs_tags);
        $fld->mValues = array_merge($values, array(PL_CATEGS_DESIGN_EDIT_SUBITEMS_TAG), $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_SUB, $fld);

        $fld = new sbLayoutTextarea('', 'ctl_levels['.$key.'][sub_sel]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, array('{SUB_ITEMS}'), $categs_tags);
        $fld->mValues = array_merge($values, array(PL_CATEGS_DESIGN_EDIT_SUBITEMS_TAG), $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_SUB_SEL, $fld);

        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea('', 'ctl_levels['.$key.'][item]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, $categs_tags);
        $fld->mValues = array_merge($values, $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_ITEM, $fld);

        $fld = new sbLayoutTextarea('', 'ctl_levels['.$key.'][item_sel]', '', 'style="width:100%;height:100px;"');
        $fld->mTags = array_merge($tags, $categs_tags);
        $fld->mValues = array_merge($values, $categs_tags_values);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_ITEM_SEL, $fld);

        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea('', 'ctl_levels['.$key.'][bottom]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array('{PARENT_ID}', '{PARENT_URL}');
        $fld->mValues = array(PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG);
        $layout->addField(PL_CATEGS_DESIGN_EDIT_BOTTOM, $fld);
    }

    $layout->addTab('+');

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable($plugin_ident, 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable($plugin_ident, 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

/**
 * Сохраняет макет дизайна вывода разделов
 *
 * @param string $cat_ident Идентификатор раздела.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $submit_event Идентификатор события, отвечающего за отправку формы.
 * @param string $el_ident Идентификатор компонента для передачи в функцию fCategs_Design_Get.
 * @param string $cat_ident Идентификатор разделов модуля для передачи в функцию fCategs_Design_Get.
 *
 */
function fCategs_Design_Edit_Submit($cat_ident, $plugin_ident, $submit_event, $el_ident, $cat_ident)
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], $cat_ident))
		return;

	if (isset($_GET['add_level']) && $_GET['add_level'] == 1)
    {
        // добавляем уровень
        fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event, '', '', true);
        return;
    }

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ctl_checked = array();
    $ctl_levels = array();
    $ctl_pagelist_id = 0;

    extract($_POST);

    if ($ctl_title == '')
    {
        sb_show_message(PL_CATEGS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event);
        return;
    }

    // удаляем пустые уровни меню
    $num = count($ctl_levels);
    $num_full = $num;
    for ($i = $num - 1; $i > 0; $i--)
    {
        if ($ctl_levels[$i]['top'] == '' && $ctl_levels[$i]['sub'] == '' && $ctl_levels[$i]['sub_sel'] == '' &&
            $ctl_levels[$i]['item'] == '' && $ctl_levels[$i]['item_sel'] == '' && $ctl_levels[$i]['bottom'] == '')
        {
            $num_full--;
        }
        else
        {
            break;
        }
    }

    $ctl_full_levels = array();
    $ctl_full_categs_temps = array();
    for ($i = 0; $i < $num_full; $i++)
    {
        $ctl_full_levels[$i] = $ctl_levels[$i];
        $ctl_full_categs_temps[$i] = isset($ctl_categs_temps[$i]) ? $ctl_categs_temps[$i] : '';
    }

    $_POST['ctl_levels'] = $ctl_full_levels;
    $_POST['ctl_categs_temps'] = $ctl_full_categs_temps;

    $row = array();
    $row['ctl_title'] = $ctl_title;
    $row['ctl_lang'] = $ctl_lang;
    $row['ctl_checked'] = implode(' ', $ctl_checked);
    $row['ctl_levels'] = serialize($ctl_full_levels);
    $row['ctl_categs_temps'] = serialize($ctl_full_categs_temps);
    $row['ctl_perpage'] = $ctl_perpage;
    $row['ctl_pagelist_id'] = $ctl_pagelist_id;

    if ($_GET['id'] != '')
    {
        $res = sql_query('SELECT ctl_title FROM sb_categs_temps_list WHERE ctl_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_query('UPDATE sb_categs_temps_list SET ?a WHERE ctl_id=?d', $row, $_GET['id'], sprintf(PL_CATEGS_DESIGN_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_categs_temps_list', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_CATEGS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_CATEGS_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event);
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['ctl_id'] = intval($_GET['id']);

            $html_str = fCategs_Design_Get($row, $el_ident, $cat_ident);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event, $html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_CATEGS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_CATEGS_DESIGN_EDIT_SYSTEMLOG_ERROR, $ctl_title), SB_MSG_WARNING);

            fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event);
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_query('INSERT INTO sb_categs_temps_list (?#) VALUES (?a)', array_keys($row), array_values($row)))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_categs_temps_list', $id);
                sb_add_system_message(sprintf(PL_CATEGS_DESIGN_EDIT_ADD_OK, $ctl_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_categs_temps_list WHERE ctl_id=?d', $id);
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_CATEGS_DESIGN_EDIT_ADD_ERROR, $ctl_title), false, 'warning');
            sb_add_system_message(sprintf(PL_CATEGS_DESIGN_EDIT_ADD_SYSTEMLOG_ERROR, $ctl_title), SB_MSG_WARNING);

            fCategs_Design_Edit($cat_ident, $plugin_ident, $submit_event);
            return;
        }
    }
}

/**
 * Строит окно редактирования макета дизайна выбранного раздела
 *
 * @param string $cat_ident Идентификатор раздела.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $submit_event Идентификатор события, отвечающего за отправку формы.
 * @param string $htmlStr Возвращается из функции fCategs_Design_Sel_Cat_Edit_Submit.
 * @param string $footerStr Возвращается из функции fCategs_Design_Sel_Cat_Edit_Submit.
 *
 */
function fCategs_Design_Sel_Cat_Edit($cat_ident, $plugin_ident, $submit_event, $htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], $cat_ident))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
    	$result = sql_query('SELECT ctf_title, ctf_lang, ctf_temp, ctf_categs_temps, ctf_checked FROM sb_categs_temps_full WHERE ctf_id=?d', $_GET['id']);

        if ($result)
        {
            list($ctf_title, $ctf_lang, $ctf_temp, $ctf_categs_temps, $ctf_checked) = $result[0];
        }
        else
        {
            sb_show_message(PL_CATEGS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($ctf_categs_temps != '')
            $ctf_categs_temps = unserialize($ctf_categs_temps);
        else
            $ctf_categs_temps = array();

        if ($ctf_checked != '')
            $ctf_checked = explode(' ', $ctf_checked);
        else
            $ctf_checked = array();
    }
    elseif (count($_POST) > 0)
    {
    	$ctf_checked = array();

        extract($_POST);

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $ctf_title = '';
        $ctf_lang = SB_CMS_LANG;
        $ctf_temp = '';
        $ctf_categs_temps = array();
        $ctf_checked = array();

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("ctf_title");
                if (el_title.value == "")
                {
                     alert("'.PL_CATEGS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event='.$submit_event.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main_form');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_CATEGS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_CATEGS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_CATEGS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ctf_title, 'ctf_title', '', 'style="width:530px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ctf_lang');
    $fld->mSelOptions = array($ctf_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_CATEGS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_CATEGS_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes($plugin_ident, $ctf_checked, 'ctf_checked', true);

    $tags = array('-', '{ID}', '{CAT_URL}', '{PARENT_ID}', '{PARENT_URL}', '{TEXT}', '{COUNT}', '{URL}');
    $values = array(PL_CATEGS_DESIGN_EDIT_TAB1, PL_CATEGS_DESIGN_EDIT_CAT_ID_TAG, PL_CATEGS_DESIGN_EDIT_CAT_URL_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG, PL_CATEGS_DESIGN_EDIT_CAT_TITLE_TAG, PL_CATEGS_DESIGN_EDIT_CAT_COUNT_TAG, PL_CATEGS_DESIGN_EDIT_LINK_TAG);

    $categs_tags = array();
    $categs_tags_values = array();

    $pd_fields = array();
    $res = sql_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);
	if ($res && $res[0][0] != '')
	{
        $pd_fields = unserialize($res[0][0]);
        $layout->getPluginFieldsTags($plugin_ident, $categs_tags, $categs_tags_values, true);
	}

    $layout->addTab(PL_CATEGS_DESIGN_EDIT_TAB_FIELD);
    $layout->addHeader(PL_CATEGS_DESIGN_EDIT_TAB_FIELD);

   	$fld = new sbLayoutTextarea(isset($ctf_categs_temps['closed_icon']) ? $ctf_categs_temps['closed_icon'] : '', 'ctf_categs_temps[closed_icon]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{ID}', '{CAT_URL}', '{PARENT_ID}', '{PARENT_URL}', '{TEXT}', '{URL}');
	$fld->mValues = array(PL_CATEGS_DESIGN_EDIT_CAT_ID_TAG, PL_CATEGS_DESIGN_EDIT_CAT_URL_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_ID_TAG, PL_CATEGS_DESIGN_EDIT_PARENT_URL_TAG, PL_CATEGS_DESIGN_EDIT_CAT_TITLE_TAG, PL_CATEGS_DESIGN_EDIT_LINK_TAG);
	$layout->addField(PL_CATEGS_DESIGN_EDIT_CLOSED_ICON, $fld);

    if (count($pd_fields) > 0)
    {
        $layout->addField('', new sbLayoutDelim());

        $layout->addPluginFieldsTemps($plugin_ident, $ctf_categs_temps, 'ctf_', $tags, $values, true);
    }

    $layout->addTab(PL_CATEGS_DESIGN_EDIT_TAB_SEL_CAT);
    $layout->addHeader(PL_CATEGS_DESIGN_EDIT_TAB_SEL_CAT);

    $fld = new sbLayoutTextarea($ctf_temp, 'ctf_temp', '', 'style="width:100%;height:450px;"');
    $fld->mTags = array_merge($tags, $categs_tags);
    $fld->mValues = array_merge($values, $categs_tags_values);
    $layout->addField(PL_CATEGS_DESIGN_EDIT_TAB_SEL_CAT, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable($plugin_ident, 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable($plugin_ident, 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

/**
 * Сохраняет макет дизайна вывода разделов
 *
 * @param string $cat_ident Идентификатор раздела.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $submit_event Идентификатор события, отвечающего за отправку формы.
 * @param string $el_ident Идентификатор компонента для передачи в функцию fCategs_Design_Get.
 * @param string $cat_ident Идентификатор разделов модуля для передачи в функцию fCategs_Design_Get.
 *
 */
function fCategs_Design_Sel_Cat_Edit_Submit($cat_ident, $plugin_ident, $submit_event, $el_ident, $cat_ident)
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], $cat_ident))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ctf_checked = array();

    extract($_POST);

    if ($ctf_title == '')
    {
        sb_show_message(PL_CATEGS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fCategs_Design_Sel_Cat_Edit($cat_ident, $plugin_ident, $submit_event);
        return;
    }

    $row = array();
    $row['ctf_title'] = $ctf_title;
    $row['ctf_lang'] = $ctf_lang;
    $row['ctf_checked'] = implode(' ', $ctf_checked);
    $row['ctf_temp'] = $ctf_temp;
    $row['ctf_categs_temps'] = serialize($ctf_categs_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_query('SELECT ctf_title FROM sb_categs_temps_full WHERE ctf_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_query('UPDATE sb_categs_temps_full SET ?a WHERE ctf_id=?d', $row, $_GET['id'], sprintf(PL_CATEGS_DESIGN_SELCAT_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_categs_temps_full', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_CATEGS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_CATEGS_DESIGN_SELCAT_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fCategs_Design_Sel_Cat_Edit($cat_ident, $plugin_ident, $submit_event);
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['ctf_id'] = intval($_GET['id']);

            $html_str = fCategs_Design_Get($row, $el_ident, $cat_ident, true);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fCategs_Design_Sel_Cat_Edit($cat_ident, $plugin_ident, $submit_event, $html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_CATEGS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_CATEGS_DESIGN_SELCAT_EDIT_SYSTEMLOG_ERROR, $ctf_title), SB_MSG_WARNING);

            fCategs_Design_Sel_Cat_Edit($cat_ident, $plugin_ident, $submit_event);
            return;
        }
    }
    else
    {
    	$error = true;
        if (sql_query('INSERT INTO sb_categs_temps_full (?#) VALUES (?a)', array_keys($row), array_values($row)))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_categs_temps_full', $id);
                sb_add_system_message(sprintf(PL_CATEGS_DESIGN_SELCAT_EDIT_ADD_OK, $ctf_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_categs_temps_full WHERE ctf_id=?d', $id);
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_CATEGS_DESIGN_SELCAT_EDIT_ADD_ERROR, $ctf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_CATEGS_DESIGN_SELCAT_EDIT_ADD_SYSTEMLOG_ERROR, $ctf_title), SB_MSG_WARNING);

            fCategs_Design_Sel_Cat_Edit($cat_ident, $plugin_ident, $submit_event);
            return;
        }
    }
}

/**
 * Функция проверки возможности удаления элемента
 *
 * @param string $el_ident Идентификатор компонента, для проверки связи со страницами и макетами дизайна.
 * @param string $cat_ident Идентификатор разделов модуля.
 * @param bool $sel Получаем информацию для макета дизайна выбранного раздела (TRUE) или дерева разделов (FALSE).
 */
function fCategs_Design_Delete($el_ident, $cat_ident, $sel=false)
{
	$found = false;
	$res = sql_query('SELECT COUNT(*) FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id LIMIT 1', 'page', $_GET['id'], $el_ident);

    if (!$res || $res[0][0] == 0)
    {
        $res = sql_query('SELECT COUNT(*) FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id LIMIT 1', 'temp', $_GET['id'], $el_ident);

        if (!$sel)
        {
	        if (!$res || $res[0][0] == 0)
	        {
	        	$res = sql_query('SELECT COUNT(*) FROM sb_menu WHERE m_type=? AND m_props LIKE \'%s:5:"ident";s:'.sb_strlen($cat_ident).':"'.preg_replace('/[^a-zA-Z0-9_\-]+/', '', $cat_ident).'";%\' AND m_props LIKE \'%s:7:"temp_id";s:'.sb_strlen($_GET['id']).':"'.intval($_GET['id']).'";%\'', 'categs');
	        	if ($res && $res[0][0] > 0)
	        	{
	        		$found = true;
	        	}
	        }
	        else
	        {
	        	$found = true;
	        }
        }
    }
    else
    {
    	$found = true;
    }

    if ($found)
    {
        echo PL_CATEGS_DESIGN_DELETE_ERROR;
    }
}

?>