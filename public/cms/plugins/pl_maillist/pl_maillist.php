<?php

function fMaillist_Get($args)
{
	$id = intval($args['m_id']);
    $m_name  = $args['m_name'];
    $m_conf_format = $args['m_conf_format'];
    $m_conf_email = $args['m_conf_email'];
    $m_conf_charset = $args['m_conf_charset'];
    $m_conf_images_attach = $args['m_conf_images_attach'];
    $m_conf_signed = $args['m_conf_signed'];
    $m_send = 0;

    if(isset($args['m_send']))
    {
		$m_send = intval($args['m_send']);
	}

	$res = sql_param_query('SELECT su_mail_subscription FROM sb_site_users');

	$m_subscribe_count = 0;
    if(is_array($res))
    {
        foreach ($res as $key => $value)
        {
			list($su_mail_subscription) = $res[$key];

            if (trim($su_mail_subscription) != '')
            {
            	$su_mail_subscription = explode(',', $su_mail_subscription);
            }
        	else
        	{
        		$su_mail_subscription = array();
        	}

			$tmp_su_mail = array();
			foreach($su_mail_subscription as $key => $value)
			{
				$tmp_su_mail[$value] = $value;
			}
			$su_mail_subscription = $tmp_su_mail;

            if(array_key_exists($id, $su_mail_subscription) && $su_mail_subscription[$id] == $id)
            {
                $m_subscribe_count ++;
            }
        }
    }

	if(isset($args['m_use_default']) && $args['m_use_default'] == 1)
	{
		$use_for_news = '<br /><span style="color: #CC0066;">'.PL_MAILLIST_EDIT_GET_USE_FOR_NEWS.'</span>';
	}
	else
	{
		$use_for_news = '';
	}

	$result = '<table width="100%" cellpadding="0" cellspacing="0">
    <tr><td valign="top"><b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$m_name.'</a></b>
    <div class="smalltext" style="margin-top: 7px;">'
		.PL_MAILLIST_DESIGN_EDIT_ID_TAG.': <span style="color: #33805E;">'.$id.'</span><br />'
	    .PL_MAILLIST_EDIT_MESSAGE_FORMAT.': <span style="color: #33805E;">'.(($m_conf_format == 0) ? PL_MAILLIST_MESSAGE_HTML : PL_MAILLIST_MESSAGE_TEXT).'</span><br />'
	    .PL_MAILLIST_EDIT_EMAIL.': <span style="color: #33805E;">'.$m_conf_email.'</span><br />'
	    .PL_MAILLIST_EDIT_CHARSET.': <span style="color: #33805E;">'.$m_conf_charset.'</span><br />'
	    .PL_MAILLIST_SUBSCRIBE_COUNT.': <span style="color: #33805E;">'.$m_subscribe_count.'</span>';
	sb_get_workflow_status($result, 'pl_maillist', $args['m_active'], $args['m_pub_start'], $args['m_pub_end']);
	$result .= $use_for_news;

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$result .= sbLayout::getPluginFieldsInfo('pl_maillist', $args);

	$result .= '<br /><a href="javascript:void(0);" style="font-size:11px; font-weight:bold;"  onclick="sbShowDialog(\''.SB_CMS_DIALOG_FILE.'?event=pl_maillist_send&id=\'+'.$id.', \'resizable=1,width=750,height=650\');">'.PL_MAILLIST_EDIT_CREATE_SEND_MENU_TITLE.'</a>
				<input type = "hidden" value = "'.$m_send.'" id = "element_to_send_'.$id.'"></div></td></tr></table>';
	return $result;
}

function fMaillist_Init()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    echo '
        <script>
            function elementEdit(el_id)
            {
                var el = sbGetE("element_to_send_"+el_id);
                if(el.value == 1)
                {
                    sbShowDialog("'.SB_CMS_DIALOG_FILE.'?event=pl_maillist_send&id="+el_id, "resizable=1,width=750,height=650");
                }
            }
        </script>
    ';

    $elems = new sbElements('sb_maillist', 'm_id', 'm_name', 'fMaillist_Get', 'pl_maillist_init', 'pl_maillist');

    $elems->addField('m_name');
    $elems->addField('m_conf_format');
    $elems->addField('m_conf_email');
    $elems->addField('m_conf_charset');
    $elems->addField('m_conf_images_attach');
    $elems->addField('m_conf_signed');
    $elems->addField('m_use_default');
    $elems->addField('m_active');
	$elems->addField('m_pub_start');
	$elems->addField('m_pub_end');

	$elems->addSorting(PL_MAILLIST_SORT_BY_ID, 'm_id');

	$elems->addFilter(PL_MAILLIST_DESIGN_EDIT_ID_TAG, 'm_id', 'number');
	$elems->addFilter(PL_MAILLIST_EDIT_NAME, 'm_name', 'string');

	$elems->addElemsMenuItem(PL_MAILLIST_EDIT_CREATE_SEND_MENU_TITLE,'sbShowDialog("'.SB_CMS_DIALOG_FILE.'?event=pl_maillist_send&id="+sbSelEl.getAttribute("el_id"), "resizable=1,width=750,height=650")',true);

    $elems->mElemsEditEvent =  'pl_maillist_edit';
    $elems->mElemsEditDlgWidth = 800;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_maillist_edit';
    $elems->mElemsAddDlgWidth = 800;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mCategsAddEvent = 'pl_maillist_cat_edit';
    $elems->mCategsAddDlgWidth = 800;
    $elems->mCategsAddDlgHeight = 730;

	$elems->mCategsEditEvent = 'pl_maillist_cat_edit';
	$elems->mCategsEditDlgWidth = 800;
	$elems->mCategsEditDlgHeight = 700;

	$elems->mElemsDeleteEvent = 'pl_maillist_delete';
	$elems->mCategsDeleteWithElementsMenu = true;
	$elems->mCategsAfterDeleteWithElementsEvent = 'pl_maillist_delete_cat_with_elements';

	$elems->mElemsAfterPasteEvent = 'pl_maillist_after_paste';
	$elems->mCategsPasteWithElementsMenu = true;
	$elems->mCategsAfterPasteWithElementsEvent = 'pl_maillist_after_paste_with_elements';

	$elems->mElemsCopyMenuTitle = PL_MAILLIST_EDIT_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle = PL_MAILLIST_EDIT_CUT_MENU_TITLE;
    $elems->mElemsDeleteMenuTitle= PL_MAILLIST_EDIT_DELETE_MENU_TITLE;
    $elems->mElemsEditMenuTitle = PL_MAILLIST_EDIT_EDIT_MENU_TITLE;
    $elems->mCategsPasteLinksMenuTitle = PL_MAILLIST_EDIT_PASTE_LINK_MENU_TITLE;
    $elems->mCategsPasteElemsMenuTitle = PL_MAILLIST_EDIT_PASTE_MENU_TITLE;
	$elems->mCategsPasteWithElementsMenuTitle = PL_MAILLIST_CATEGS_PASTE_WITH_ELEMENTS_MENU_TITLE;
	$elems->mCategsDeleteWithElementsMenuTitle = PL_MAILLIST_CATEGS_DELETE_WITH_ELEMENTS_MENU_TITLE;

    $elems->mElemsAfterEditFunc = 'elementEdit';
    $elems->mElemsUseLinks = false;
    $elems->mCategsPasteElemsMenu = 'all';
    $elems->mElemsAddMenuTitle = PL_MAILLIST_EDIT_ADD;
	$elems->mCategsClosed = true;

	if ($_SESSION['sbPlugins']->isRightAvailable('pl_maillist', 'elems_public') && (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_maillist')))
	{
		$elems->mElemsJavascriptStr .= '
            function maillistSetActive()
            {
            	var ids = "0";
                for (var i = 0; i < sbSelectedEls.length; i++)
			    {
					var el = sbGetE("el_" + sbSelectedEls[i]);
					if (el)
						ids += "," + el.getAttribute("el_id");
			    }

                var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_maillist_set_active&ids=" + ids);
                if (res != "TRUE")
                {
                    alert("'.PL_MAILLIST_SET_ACTIVE_SHOWHIDE_ERROR.'");
                    return;
                }

                var div_el = sbGetE("elems_list_div");
                var from = "";
                if (div_el)
                {
                    from = div_el.getAttribute("from");
                }
                var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + sbCatTree.getSelectedItemId() + "&page_elems=" + from;
                sbElemsShow(url);
            }';

        $elems->addElemsMenuItem(PL_MAILLIST_SET_ACTIVE_SHOWHIDE_MENU, 'maillistSetActive();');
	}
	$elems->mElemsJavascriptStr .= '
		function sbMailExport(c)
		{
			if(c && sbSelCat)
			{
				var cat_id = sbSelCat.id;
    		}
    		else
    		{
				var cat_id = sbCatTree.getSelectedItemId();
			}

			var args = new Object();
			var form = sbGetE("filter_form");
			if(form)
			{
				var els = form.elements;
				for(var i = 0; i < els.length; i++)
				{
					if(els[i] && els[i].type != "button" && els[i].value != "")
					{
						var reg_date = new RegExp("date_");
						var reg_spin = new RegExp("spin_");

						if(reg_date.test(els[i].id))
						{
							args[els[i].name] = {};
							args[els[i].name]["value"]= els[i].value;
							args[els[i].name]["type"]= "date";
						}
						else if(reg_spin.test(els[i].id))
						{
							args[els[i].name] = {};
							args[els[i].name]["value"]= els[i].value;
							args[els[i].name]["type"]= "number";
						}
						else if(els[i].nodeName == "SELECT" && els[i].multiple)
						{
							var str = "";
							for(var j = 0; j < els[i].length; j++)
							{
								if(els[i][j].selected)
									str += els[i][j].value+",";
							}
							str = str.slice(0, -1);

							args[els[i].name] = {};
							args[els[i].name]["value"]= str;
							args[els[i].name]["type"]= "multyselect";
						}
						else if(els[i].nodeName == "SELECT")
						{
							var reg_link = new RegExp("_link");
							if(els[i].value == -1 && reg_link.test(els[i].id))
							{
								var name = els[i].name.replace(/_link/, "");
								args[name] = "";
								continue;
							}
							else if(els[i].value == -1)
							{
								continue;
							}

							args[els[i].name] = {};
							args[els[i].name]["value"]= els[i].value;
							args[els[i].name]["type"]= "select";
						}
						else if(els[i].type == "ckeckbox")
						{
							args[els[i].name] = {};
							args[els[i].name]["value"]= els[i].value;
							args[els[i].name]["type"]= "checkbox";
						}
						else if(els[i].type == "text")
						{
							args[els[i].name] = {};
							args[els[i].name]["value"]= els[i].value;
							args[els[i].name]["type"]= "string";
						}
    				}
				}
			}

		var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_edit&ident=pl_maillist&cat_id="+ cat_id;
		var strAttr = "resizable=1,width=700,height=600";
		sbShowModalDialog(strPage, strAttr, null, args);
	}
	function sbMailImport(c)
	{
		if(c && sbSelCat)
		{
			var cat_id = sbSelCat.id;
    	}
    	else
    	{
			var cat_id = sbCatTree.getSelectedItemId();
		}

		var args = new Object();

		var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_import_edit&ident=pl_maillist&cat_id="+ cat_id;
		var strAttr = "resizable=1,width=650,height=370";
		sbShowModalDialog(strPage, strAttr, sbAfterMailImport, args);
	}

	function sbAfterMailImport()
	{
		window.location.href = "'.SB_CMS_CONTENT_FILE.'?event=pl_maillist_init";
	}';

	$elems->addElemsMenuItem(PL_MAILLIST_EDIT_EXPORT, 'sbMailExport()', false);
	//$elems->addElemsMenuItem(PL_MAILLIST_EDIT_IMPORT, 'sbMailImport()', false);

	$elems->addCategsMenuItem(PL_MAILLIST_EDIT_EXPORT, 'sbMailExport(true)');
	//$elems->addCategsMenuItem(PL_MAILLIST_EDIT_IMPORT, 'sbMailImport(true)');

	$elems->init();
}

function fMaillist_Edit()
{
	//	определяем групповое редактирование или нет.
	$edit_group = sbIsGroupEdit();

	// проверка прав доступа
	if($edit_group)
	{
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_maillist'))
			return;
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_maillist'))
	{
		return;
	}

	$edit_rights = $_SESSION['sbPlugins']->isRightAvailable('pl_maillist', 'elems_edit');
	require_once(SB_CMS_LIB_PATH . '/sbLayout.inc.php');

    $m_fields = array();
    $su_fields_temps = array();

	if(!$edit_group)
	{
		$m_fields['su_status'] = '{VALUE}';
		$m_fields['su_login'] = '{VALUE}';
	    $m_fields['su_email'] = '{VALUE}';
	    $m_fields['su_reg_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $m_fields['su_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $m_fields['su_name'] = '{VALUE}';
	    $m_fields['su_pers_foto'] = '<img src="{IMG_LINK}" />';
	    $m_fields['su_pers_phone'] = '{VALUE}';
	    $m_fields['su_pers_mob_phone'] = '{VALUE}';
	    $m_fields['su_pers_birth'] = '{DAY}.{MONTH}.{LONG_YEAR}';
	    $m_fields['su_pers_sex'] = '{VALUE}';
	    $m_fields['su_pers_zip'] = '{VALUE}';
	    $m_fields['su_pers_adress'] = '{VALUE}';
	    $m_fields['su_pers_addition'] = '{VALUE}';
	    $m_fields['su_work_name'] = '{VALUE}';
	    $m_fields['su_work_unit'] = '{VALUE}';
	    $m_fields['su_work_position'] = '{VALUE}';
	    $m_fields['su_work_phone'] = '{VALUE}';
	    $m_fields['su_work_phone_inner'] = '{VALUE}';
	    $m_fields['su_work_fax'] = '{VALUE}';
	    $m_fields['su_work_email'] = '{VALUE}';
	    $m_fields['su_work_office_number'] = '{VALUE}';
	    $m_fields['su_work_addition'] = '{VALUE}';
	    $m_fields['su_forum_nick'] = '{VALUE}';
		$m_fields['su_forum_text'] = '{VALUE}';
		$m_fields['su_mail_lang'] = '{VALUE}';
		$m_fields['su_mail_status'] = '{VALUE}';
	}

	echo '<script>
			function checkValues()
			{
				'.($edit_group ? '
				var ch_name = sbGetE("ch_m_name");
				var ch_email = sbGetE("ch_m_conf_email");
				var ch_charset = sbGetE("ch_m_conf_charset");
				' : '').'

				var el_title = sbGetE("m_name");
				var el_mail = sbGetE("m_conf_email");
				var el_charset = sbGetE("m_conf_charset");

                var use_default = sbGetE("m_use_default");
				var el_temp = sbGetE("m_news_temp_id[ru]");

				if (use_default.checked && !el_temp)
	            {
					alert("'.PL_MAILLIST_EDIT_LIST_NO_TEMPS_ALERT.'");
					return false;
				}

				if(el_title.value == "" '.($edit_group ? ' && ch_name.checked' : '').')
                {
                     alert("'.PL_MAILLIST_EDIT_ERROR_NO_TITLE.'");
                     return false;
                }
                if(el_mail.value == "" '.($edit_group ? ' && ch_email.checked' : '').')
                {
                    alert("'.PL_MAILLIST_EDIT_ERROR_NO_EMAIL.'");
                    return false;
                }
				if(el_charset.value == "" '.($edit_group ? ' && ch_charset.checked' : '').')
				{
					alert("'.PL_MAILLIST_EDIT_ERROR_NO_CHARSET.'");
					return false;
				}
				return true;
			}

			function ShowHide(langth)
			{
				var langth = langth.split("|");
				var display = _isIE ? "block" : "table-row";

				for(var i = 0; i < (langth.length - 1); i++)
				{
					var delim1 = sbGetE("delim1_"+langth[i]);
					var delim2 = sbGetE("delim2_"+langth[i]);
					var delim3 = sbGetE("delim3_"+langth[i]);
					var el_label = sbGetE("use_for_news_label"+langth[i]);
					var el_temp = sbGetE("use_for_news_temp"+langth[i]);
					var news_page = sbGetE("page_full_news"+langth[i]);
					var el_title = sbGetE("use_for_news_title_"+langth[i]);
					var el_text = sbGetE("use_for_news_text_"+langth[i]);

					if(el_label && el_label.style.display == "none")
					{
						delim1.style.display = display;
						delim2.style.display = display;
						delim3.style.display = display;
						el_label.style.display = display;
						el_temp.style.display = display;
						news_page.style.display = display;
						el_title.style.display = display;
						el_text.style.display = display;
					}
					else
					{
						delim1.style.display = "none";
						delim2.style.display = "none";
						delim3.style.display = "none";
						el_label.style.display = "none";
						el_temp.style.display = "none";
						news_page.style.display = "none";
						el_title.style.display = "none";
						el_text.style.display = "none";
					}
				}
			}';

	if($edit_group)
	{
		echo '
			function cancel()
            {
				sbReturnValue("refresh");
			}
			sbAddEvent(window, "close", cancel);';
	}
	echo '</script>';

    if(count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '' && !$edit_group)
    {
        $res = sql_param_query('SELECT m_name,m_conf_format,m_conf_email,m_conf_charset,
        				m_conf_images_attach,m_conf_signed,m_mail_description,m_site_users_fields, m_site_users_custom_fields, m_unsub_page, m_use_default, m_use_default, m_mail_designs, m_news_temp_id, m_active, m_pub_start, m_pub_end FROM sb_maillist WHERE m_id = ?',$_GET['id']);

        if($res != false)
        {
            list($m_name, $m_conf_format, $m_conf_email, $m_conf_charset, $m_conf_images_attach, $m_conf_signed,$m_mail_description,$m_fields,$su_fields_temps, $m_unsub_page, $m_use_default, $m_use_default, $m_mail_designs, $m_news_temp_id, $m_active, $m_pub_start, $m_pub_end) = $res[0];

            $m_conf_signed = unserialize($m_conf_signed);
            $m_fields = unserialize($m_fields);
            $su_fields_temps = unserialize($su_fields_temps);

	        if (!is_null($m_pub_start) && $m_pub_start != 0 && $m_pub_start != '')
				$m_pub_start = sb_date('d.m.Y H:i', $m_pub_start);
			else
				$m_pub_start = '';

			if (!is_null($m_pub_end) && $m_pub_end != 0 && $m_pub_end != '')
			    $m_pub_end = sb_date('d.m.Y H:i', $m_pub_end);
			else
			    $m_pub_end = '';

			$m_message = array();
			$m_title = array();
			$m_page_full_news = '';

			if($m_mail_designs != '')
			{
				$m_mail_designs = unserialize($m_mail_designs);
				foreach($m_mail_designs as $key => $value)
				{
					$m_message[$key] = isset($value['m_message']) ? $value['m_message'] : '';
					$m_title[$key] = isset($value['m_title']) ? $value['m_title'] : '';
					$m_page_full_news[$key] = isset($value['m_page_full_news']) ? $value['m_page_full_news'] : '';
				}
			}
        }
		else
        {
			sb_show_message(PL_MAILLIST_EDIT_ERROR, true, 'warning');
			return;
        }
    }
	elseif (count($_POST) > 0)
    {
    	$m_pub_start = $m_pub_end = '';
    	$m_active = 0;

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
		$m_conf_email = sbPlugins::getSetting('sb_admin_email');
		$m_conf_charset = sbPlugins::getSetting('sb_letters_charset');
		$m_conf_images_attach = sbPlugins::getSetting('sb_letters_images');
		$m_conf_signed = array();
		$m_pub_start = $m_pub_end = $m_page_full_news = $m_mail_description = $m_unsub_page = $m_name = '';
		$m_conf_format = array();
		$m_news_temp_id = $m_use_default = 0;
		$m_active = 1;

		$res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id = ?d', $_GET['cat_id']);
		if($res)
		{
			list($cat_fields) = $res[0];
			if($cat_fields != '')
			{
				$cat_fields = unserialize($cat_fields);
				foreach($cat_fields as $key => $value)
				{
					if(!is_array($value) && trim($value) != '')
					{
						${$key} = $value;
					}
					elseif(is_array($value))
					{
						foreach($value as $k => $val)
						{
							if($val != '')
								${$key}[$k] = $val;
						}
					}
				}
			}
		}

		if($m_conf_email == '')
			$m_conf_email = 'info@{DOMAIN}';

		if($m_conf_charset == '')
			$m_conf_charset = 'UTF-8';

		$_GET['id'] = '';
	}

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_maillist_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', '', 'enctype="multipart/form-data"');

	$layout->addTab(PL_MAILLIST_TAB1);
	$layout->addHeader(PL_MAILLIST_TAB1);

	$layout->addField(PL_MAILLIST_EDIT_NAME.sbGetGroupEditCheckbox('m_name', $edit_group), new sbLayoutInput('text', $m_name, 'm_name', '', 'style="width:97%;"', true) );

	$fld = new sbLayoutTextarea($m_mail_description,'m_mail_description','','style = "width:100%"');
	$fld->mShowEditorBtn = true;
	$layout->addField(PL_MAILLIST_EDIT_DESCRIPTION.sbGetGroupEditCheckbox('m_mail_description', $edit_group), $fld);

	$langths = '';
	foreach ($GLOBALS['sb_site_langs'] as $key => $value)
    {
		$langths .= $key.'|';
	}

	if(!$edit_group)
	{
		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_maillist', $_GET['id'], 'm_active', $m_active, 'm_pub_start', $m_pub_start, 'm_pub_end', $m_pub_end);
	}
	else
	{
		$states_arr = array();
		$states = sql_query('SELECT m_active FROM sb_maillist WHERE m_id IN (?a)', $_GET['ids']);
		if($states)
		{
			foreach($states as $val)
			{
				$states_arr[] = $val[0];
			}
		}
		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_maillist', $_GET['ids'], 'm_active', $states_arr, 'm_pub_start', $m_pub_start, 'm_pub_end', $m_pub_end);
	}

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_MAILLIST_EDIT_USE_DEFAULT_FOR_NEWS.sbGetGroupEditCheckbox('m_use_default', $edit_group), new sbLayoutInput('checkbox', '1', 'm_use_default', '', ($m_use_default == 1 ? 'checked="checked"' : '').' onClick="ShowHide(\''.$langths.'\');"'));

    $cat_tags = array();
    $cat_tags_values = array();
    $forum_tags = array();
    $forum_tags_labels = array();
	$layout->getPluginFieldsTags('pl_site_users', $cat_tags, $cat_tags_values);

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum') === true)
    {
        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
        $forum_tags_labels = array(PL_MAILLIST_EDIT_SU_FORUM_TAB, PL_MAILLIST_EDIT_SU_NICK, PL_MAILLIST_EDIT_SU_SIGNATURE);
	}

	$tags = array_merge(array('-', '{SU_LOGIN}', '{SU_EMAIL}',  '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_LAST_IP}', '{SU_ID}',
                                    '-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}',
                                    '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_ADDITION}'),
                                	$forum_tags, $cat_tags, array('-', '{NEWS_LIST}'));

	$values = array_merge(array(PL_MAILLIST_DESIGN_TAB1, PL_MAILLIST_EDIT_SU_LOGIN, PL_MAILLIST_EDIT_SU_EMAIL, PL_MAILLIST_EDIT_SU_GET_REG_DATE, PL_MAILLIST_EDIT_SU_GET_LAST_DATE, PL_MAILLIST_EDIT_SU_IP,
									PL_MAILLIST_EDIT_SU_ID, PL_MAILLIST_EDIT_SU_PERS_TAB, PL_MAILLIST_EDIT_SU_FIO, PL_MAILLIST_EDIT_SU_AVATAR, PL_MAILLIST_EDIT_SU_PHONE, PL_MAILLIST_EDIT_SU_MOBILE, PL_MAILLIST_EDIT_SU_BIRTH, PL_MAILLIST_EDIT_SU_SEX, PL_MAILLIST_EDIT_SU_ZIP, PL_MAILLIST_EDIT_SU_ADRESS,
									PL_MAILLIST_EDIT_SU_ADDITION, PL_MAILLIST_EDIT_SU_CORPORATE_TAB,  PL_MAILLIST_EDIT_SU_WORK_NAME, PL_MAILLIST_EDIT_SU_WORK_OTDEL, PL_MAILLIST_EDIT_SU_WORK_DOLJ,  PL_MAILLIST_EDIT_SU_WORK_PHONE, PL_MAILLIST_EDIT_SU_WORK_PHONE_INNER, PL_MAILLIST_EDIT_SU_WORK_FAX, PL_MAILLIST_EDIT_SU_WORK_EMAIL, PL_MAILLIST_EDIT_SU_WORK_OFFICE, PL_MAILLIST_EDIT_SU_WORK_ADDITION),
									$forum_tags_labels, $cat_tags_values, array(PL_MAILLIST_EDIT_NEWS_TITLE, PL_MAILLIST_EDIT_NEWS_LIST));

	if($m_use_default == 1)
		$style = '';
	else
		$style = 'style="display:none;"';

	$options = array();
	$res = sql_query('SELECT categs.cat_title, temps.ndl_id, temps.ndl_title FROM sb_categs categs, sb_catlinks links, sb_news_temps_list temps WHERE temps.ndl_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_news_list" ORDER BY categs.cat_left, temps.ndl_title');
	if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $ndl_id, $ndl_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$ndl_id] = $ndl_title;
        }
    }

	$m_news_temp_id = explode('|', $m_news_temp_id);

	foreach ($GLOBALS['sb_site_langs'] as $key => $value)
    {
		$layout->addField('', new sbLayoutDelim(), '', '', $style.'id="delim1_'.$key.'"');
		$html = '<div align="center" class="hint_div" style="margin-top:5px;">'.sprintf(PL_MAILLIST_MESSAGE_MESSAGE_LANG,$GLOBALS['sb_site_langs'][$key]).'</div>';
		$layout->addField('', new sbLayoutHTML($html, true), '', '', $style.' id="use_for_news_label'.$key.'"');
		$layout->addField('', new sbLayoutDelim(), '', '', $style.'id="delim2_'.$key.'"');

		if (count($options) > 0)
	    {
			$fld = new sbLayoutSelect($options, 'm_news_temp_id['.$key.']');
	    	foreach($m_news_temp_id as $val)
	    	{
		        if(strpos($val, $key) !== false)
		        {
					$fld->mSelOptions = array(substr($val, 4));
		        }
	    	}
	    }
	    else
	    {
			$fld = new sbLayoutLabel('<div class="hint_div">'.PL_MAILLIST_EDIT_NEWS_LIST_NO_TEMPS.'</div>', '', '', false);
		}

		$layout->addField(PL_MAILLIST_EDIT_NEWS_TEMPS.sbGetGroupEditCheckbox('m_news_temp_id['.$key.']', $edit_group), $fld, '', '', $style.' id="use_for_news_temp'.$key.'"');
		$layout->addField(PL_MAILLIST_EDIT_FULL_NEWS_PAGE.sbGetGroupEditCheckbox('m_page_full_news['.$key.']', $edit_group), new sbLayoutPage((isset($m_page_full_news[$key]) ? $m_page_full_news[$key] : ''), 'm_page_full_news['.$key.']', '', 'style="width:94%;"'), '', '', $style.'id="page_full_news'.$key.'"');

		$layout->addField('', new sbLayoutDelim(), '', '', $style.'id="delim3_'.$key.'"');
		$layout->addField(PL_MAILLIST_MESSAGES_TITLE.sbGetGroupEditCheckbox('m_title['.$key.']', $edit_group), new sbLayoutInput('text', (isset($m_title[$key]) ? $m_title[$key] : '') , 'm_title['.$key.']', '', 'style="width:97%;"', true), '', '', $style.' id="use_for_news_title_'.$key.'"');

		$fld = new sbLayoutTextarea( (isset($m_message[$key]) ? $m_message[$key] : '') , 'm_message['.$key.']', '', 'style="width:100%; height:150px"');
		$fld->mShowEditorBtn = true;
		$fld->mTags = $tags;
		$fld->mValues = $values;
		$layout->addField(PL_MAILLIST_MESSAGES_MESSAGE.sbGetGroupEditCheckbox('m_message['.$key.']', $edit_group), $fld, '', '', $style.' id="use_for_news_text_'.$key.'"');
	}

    $user_tags = array();
    $user_tags_values = array();
    $layout->getPluginFieldsTags('pl_maillist', $user_tags, $user_tags_values);

	if(count($user_tags) > 0)
	{
		$layout->addField('', new sbLayoutDelim());
		if(!$edit_group)
		{
			$layout->getPluginFields('pl_maillist', $_GET['id'], 'm_id');
		}
		else
		{
			$layout->getPluginFields('pl_maillist', '', 'm_id', false, $edit_group);
		}
	}

	$layout->addTab(PL_MAILLIST_TAB2);
	$layout->addHeader(PL_MAILLIST_TAB2);

	$field = new sbLayoutSelect(array(PL_MAILLIST_MESSAGE_HTML, PL_MAILLIST_MESSAGE_TEXT), 'm_conf_format');
	if(!$edit_group)
	{
		$field->mSelOptions = array($m_conf_format);
	}

	$layout->addField(PL_MAILLIST_EDIT_MESSAGE_FORMAT.sbGetGroupEditCheckbox('m_conf_format', $edit_group), $field);

	$layout->addField(PL_MAILLIST_EDIT_EMAIL.sbGetGroupEditCheckbox('m_conf_email', $edit_group), new sbLayoutInput('text', ($edit_group ? '' : $m_conf_email), 'm_conf_email', '', '', true) );
	$layout->addField(PL_MAILLIST_EDIT_CHARSET.sbGetGroupEditCheckbox('m_conf_charset', $edit_group), new sbLayoutInput('text', ($edit_group ? '' : $m_conf_charset), 'm_conf_charset', '', '', true) );

	$layout->addField(PL_MAILLIST_EDIT_IMAGES_ATTACH.sbGetGroupEditCheckbox('m_conf_images_attach', $edit_group), new sbLayoutInput('checkbox', '1', 'm_conf_images_attach', '', (($m_conf_images_attach == '1') ? 'checked' : '')));

	$layout->addField(PL_MAILLIST_EDIT_UNSUB_PAGE_FILED_TITLE.sbGetGroupEditCheckbox('m_unsub_page', $edit_group), new sbLayoutPage(($edit_group ? '' : $m_unsub_page), 'm_unsub_page', '', 'style="width:95%;"'));

    require_once(SB_BASEDIR . '/cms/lang/prog_' . SB_DB_CHARSET . '.lng.php');

    foreach($GLOBALS['sb_site_langs'] as $key => $value)
    {
		$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.sprintf(PL_MAILLIST_EDIT_SIGNED_LANGUAGE, $GLOBALS['sb_site_langs'][$key]).'</div>';
		$layout->addField('', new sbLayoutHTML($html, true));
		$layout->addField('', new sbLayoutDelim());

		$fld = new sbLayoutTextarea((isset($m_conf_signed[$key]) ? $m_conf_signed[$key] : ''), 'm_conf_signed['.$key.']', '', 'style = "width:100%"');
		$fld->mShowEditorBtn = true;
		$fld->mTags = array_merge(array(sprintf(PL_MAILLIST_EDIT_UNSUB_LINK_TAG_VAL, SB_COOKIE_DOMAIN)), $user_tags);
		$fld->mValues = array_merge(array(PL_MAILLIST_EDIT_UNSUB_LINK_TAG), $user_tags_values);

		$layout->addField(PL_MAILLIST_EDIT_SIGNED.sbGetGroupEditCheckbox('m_conf_signed['.$key.']', $edit_group), $fld);
	}

	$layout->addTab(PL_MAILLIST_TAB4);
	$layout->addHeader(PL_MAILLIST_TAB4);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_DESIGN_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

	$tags = array('{VALUE}', '{SU_ID}');
	$values = array(PL_MAILLIST_EDIT_FIELD_VALUE, PL_MAILLIST_EDIT_SU_ID);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_login']), 'm_fields[su_login]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_LOGIN.sbGetGroupEditCheckbox('m_fields[su_login]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_email']), 'm_fields[su_email]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_EMAIL.sbGetGroupEditCheckbox('m_fields[su_email]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_reg_date']), 'm_fields[su_reg_date]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{SU_ID}');
    $field->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_GET_REG_DATE.sbGetGroupEditCheckbox('m_fields[su_reg_date]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_last_date']), 'm_fields[su_last_date]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{SU_ID}');
    $field->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, PL_MAILLIST_EDIT_SU_ID);
	$layout->addField(PL_MAILLIST_EDIT_SU_GET_LAST_DATE.sbGetGroupEditCheckbox('m_fields[su_last_date]', $edit_group), $field);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_PERS_TAB.'</div>';
    $layout->addField('',new sbLayoutHTML($html,true));

	$layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_name']), 'm_fields[su_name]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_FIO.sbGetGroupEditCheckbox('m_fields[su_name]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_foto']), 'm_fields[su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{IMG_LINK}', '{SU_ID}');
    $field->mValues = array(PL_MAILLIST_EDIT_SU_FOTO_LINK, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_AVATAR.sbGetGroupEditCheckbox('m_fields[su_pers_foto]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_phone']), 'm_fields[su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_PHONE.sbGetGroupEditCheckbox('m_fields[su_pers_phone]', $edit_group), $field);

	$field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_mob_phone']), 'm_fields[su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
	$field->mTags = $tags;
	$field->mValues = $values;
	$layout->addField(PL_MAILLIST_EDIT_SU_MOBILE.sbGetGroupEditCheckbox('m_fields[su_pers_mob_phone]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_birth']), 'm_fields[su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{SU_ID}');
    $field->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_BIRTH.sbGetGroupEditCheckbox('m_fields[su_pers_birth]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_sex']), 'm_fields[su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_SEX.sbGetGroupEditCheckbox('m_fields[su_pers_sex]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_zip']), 'm_fields[su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_ZIP.sbGetGroupEditCheckbox('m_fields[su_pers_zip]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_adress']), 'm_fields[su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_ADRESS.sbGetGroupEditCheckbox('m_fields[su_pers_adress]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_pers_addition']), 'm_fields[su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_ADDITION.sbGetGroupEditCheckbox('m_fields[su_pers_addition]', $edit_group), $field);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_CORPORATE_TAB.'</div>';
    $layout->addField('',new sbLayoutHTML($html,true));

    $layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_name']), 'm_fields[su_work_name]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_NAME.sbGetGroupEditCheckbox('m_fields[su_work_name]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_unit']), 'm_fields[su_work_unit]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_OTDEL.sbGetGroupEditCheckbox('m_fields[su_work_unit]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_position']), 'm_fields[su_work_position]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_DOLJ.sbGetGroupEditCheckbox('m_fields[su_work_position]', $edit_group), $field);

	$field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_phone']), 'm_fields[su_work_phone]', '', 'style="width:100%;height:50px;"');
	$field->mTags = $tags;
	$field->mValues = $values;
	$layout->addField(PL_MAILLIST_EDIT_SU_WORK_PHONE.sbGetGroupEditCheckbox('m_fields[su_work_phone]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_phone_inner']), 'm_fields[su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_PHONE_INNER.sbGetGroupEditCheckbox('m_fields[su_work_phone_inner]', $edit_group), $field);

	$field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_fax']), 'm_fields[su_work_fax]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_FAX.sbGetGroupEditCheckbox('m_fields[su_work_fax]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_email']), 'm_fields[su_work_email]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_EMAIL.sbGetGroupEditCheckbox('m_fields[su_work_email]', $edit_group), $field);

    $field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_office_number']), 'm_fields[su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
	$layout->addField(PL_MAILLIST_EDIT_SU_WORK_OFFICE.sbGetGroupEditCheckbox('m_fields[su_work_office_number]', $edit_group), $field);

	$field = new sbLayoutTextarea(($edit_group ? '' : $m_fields['su_work_addition']), 'm_fields[su_work_addition]', '', 'style="width:100%;height:50px;"');
	$field->mTags = $tags;
	$field->mValues = $values;
	$layout->addField(PL_MAILLIST_EDIT_SU_WORK_ADDITION.sbGetGroupEditCheckbox('m_fields[su_work_addition] ' , $edit_group), $field);

	$forum_tags = array();
	$forum_tags_labels = array();

	if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum') === true)
    {
		$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_FORUM_TAB.'</div>';
        $layout->addField('', new sbLayoutHTML($html,true));
        $layout->addField('', new sbLayoutDelim());

        $field = new sbLayoutTextarea((isset($m_fields['su_forum_nick']) ? $m_fields['su_forum_nick'] : ''), 'm_fields[su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $field->mTags = $tags;
        $field->mValues = $values;
        $layout->addField(PL_MAILLIST_EDIT_SU_NICK.sbGetGroupEditCheckbox('m_fields[su_forum_nick]', $edit_group), $field);

        $field = new sbLayoutTextarea((isset($m_fields['su_forum_text']) ? $m_fields['su_forum_text'] : ''), 'm_fields[su_forum_text]', '', 'style="width:100%;height:50px;"');
        $field->mTags = $tags;
        $field->mValues = $values;
        $layout->addField(PL_MAILLIST_EDIT_SU_SIGNATURE.sbGetGroupEditCheckbox('m_fields[su_forum_text]', $edit_group), $field);

		$forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
		$forum_tags_labels = array(PL_MAILLIST_EDIT_SU_FORUM_TAB, PL_MAILLIST_EDIT_SU_NICK, PL_MAILLIST_EDIT_SU_SIGNATURE);
	}

    if(count($user_tags) > 0)
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_USERS_TAB.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

		$tags = array('{SU_ID}');
		$values = array(PL_MAILLIST_EDIT_SU_ID);

		$params = array();
		$params['edit_group'] = $edit_group;
		$layout->addPluginFieldsTemps('pl_maillist', $su_fields_temps, 'su_', $tags, $values, false, '', '', '', false, false, $params);
	}

	$su_tags = array();
	$su_tags_values = array();
	$layout->getPluginFieldsTags('pl_site_users', $su_tags, $su_tags_values);

	if(count($su_tags) != 0)
	{
		$tags = array('{SU_ID}');
		$values = array(PL_MAILLIST_EDIT_SU_ID);

		$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_USERS_GROUP_TAB.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

		$params = array();
		$params['edit_group'] = $edit_group;
		$layout->addPluginFieldsTemps('pl_site_users', $su_fields_temps , 'su_', $tags, $values, false, '', '', '', false, false, $params);
	}

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($edit_rights ? '' : 'disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', ($edit_group ? 'onclick="cancel()"' :'onclick="sbCloseDialog()"'));
	$layout->show();
}

function fMaillist_Edit_Submit()
{
	$edit_group = sbIsGroupEdit();

	//	проверка прав доступа
	if($edit_group)
	{
		$ch_m_fields = array();
		$ch_m_name = $ch_m_mail_description = $ch_m_active = $ch_m_use_default = $ch_m_conf_format = $ch_m_conf_email = $ch_m_conf_charset =
		$ch_m_conf_images_attach = $ch_m_unsub_page = $ch_m_fields['su_login'] = $ch_m_fields['su_email'] = $ch_m_fields['su_reg_date'] =
		$ch_m_fields['su_last_date'] = $ch_m_fields['su_name'] = $ch_m_fields['su_pers_foto'] = $ch_m_fields['su_pers_phone'] =
		$ch_m_fields['su_pers_mob_phone'] = $ch_m_fields['su_pers_birth'] = $ch_m_fields['su_pers_sex'] = $ch_m_fields['su_pers_zip'] =
		$ch_m_fields['su_pers_adress'] = $ch_m_fields['su_work_name'] = $ch_m_fields['su_work_unit'] = $ch_m_fields['su_work_position'] =
		$ch_m_fields['su_work_phone'] = $ch_m_fields['su_work_phone_inner'] = $ch_m_fields['su_work_fax'] = $ch_m_fields['su_work_email'] =
		$ch_m_fields['su_work_office_number'] = $ch_m_fields['su_work_addition'] = $ch_m_fields['su_forum_nick'] = $ch_m_fields['su_forum_text'] = 0;

		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_maillist'))
			return;
	}
	else if(!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_maillist'))
	{
		//	проверка прав доступа
		return;
	}
	if (!isset($_GET['id']))
		$_GET['id'] = '';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$layout = new sbLayout();

	$user_row = $layout->checkPluginFields('pl_maillist', ($edit_group ? '' : $_GET['id']), 'm_id', false, $edit_group);
	if($user_row === false)
	{
		$layout->deletePluginFieldsFiles();
		fMaillist_Edit();
		return;
	}

	$news_temps_ids = $m_news_temp_id = $m_page_full_news = $m_conf_images_attach = $m_unsub_page = '';
	$m_mail_designs = $su_fields_temps = $ml_fields_temps = array();
	$m_use_default = $m_active = 0;

	extract($_POST);

	$m_conf_signed = serialize($m_conf_signed);
	$m_fields = serialize($m_fields);
	$su_fields_temps = serialize(array_merge($ml_fields_temps, $su_fields_temps));

	if((!$edit_group || $edit_group && $ch_m_name == 1) && $m_name == '')
	{
		sb_show_message(PL_MAILLIST_EDIT_ERROR_NO_TITLE, false, 'warning');
		$layout->deletePluginFieldsFiles();
		fMaillist_Edit();
		return;
	}

	if((!$edit_group || $edit_group && $ch_m_conf_email == 1) && $m_conf_email == '')
	{
		sb_show_message(PL_MAILLIST_EDIT_ERROR_NO_EMAIL, false, 'warning');
		$layout->deletePluginFieldsFiles();
		fMaillist_Edit();
		return;
	}

	if((!$edit_group || $edit_group && $ch_m_conf_charset == 1) && $m_conf_charset == "")
    {
		sb_show_message(PL_MAILLIST_EDIT_ERROR_NO_CHARSET, false, 'warning');
		$layout->deletePluginFieldsFiles();
		fMaillist_Edit();

		return;
	}

	$m_mail_designs = array();
	foreach($m_message as $key => $value)
	{
		$m_mail_designs[$key]['m_message'] = $value;
		$m_mail_designs[$key]['m_title'] = $m_title[$key];
		$m_mail_designs[$key]['m_page_full_news'] = $m_page_full_news[$key];
		$news_temps_ids .= $key.'::'.$m_news_temp_id[$key].'|';
	}

	$row = array();
	if(!$edit_group || $edit_group && $ch_m_name == 1)
	{
		$row['m_name'] = $m_name;
	}
	if(!$edit_group || $edit_group && $ch_m_conf_format == 1)
	{
		$row['m_conf_format'] = $m_conf_format;
	}
	if(!$edit_group || $edit_group && $ch_m_conf_email == 1)
	{
		$row['m_conf_email'] = $m_conf_email;
	}
	if(!$edit_group || $edit_group && $ch_m_conf_charset == 1)
	{
		$row['m_conf_charset'] = $m_conf_charset;
	}
	if(!$edit_group || $edit_group && $ch_m_conf_images_attach == 1)
	{
		$row['m_conf_images_attach'] = $m_conf_images_attach;
	}
	if(!$edit_group || $edit_group && $ch_m_unsub_page == 1)
	{
		$row['m_unsub_page'] = $m_unsub_page;
	}
	if(!$edit_group || $edit_group && $ch_m_mail_description == 1)
	{
		$row['m_mail_description'] = $m_mail_description;
	}
	if(!$edit_group || $edit_group && $ch_m_use_default == 1)
	{
		$row['m_use_default'] = $m_use_default;
	}

	if(!$edit_group)
	{
		$row['m_conf_signed'] = $m_conf_signed;
		$row['m_site_users_fields'] = $m_fields;
		$row['m_site_users_custom_fields'] = $su_fields_temps;
		$row['m_mail_designs'] = serialize($m_mail_designs);
		$row['m_news_temp_id'] = $news_temps_ids;
	}

	sb_submit_workflow_status($row, 'm_active', 'm_pub_start', 'm_pub_end', $edit_group);
	$row = array_merge($row, $user_row);

	if ($_GET['id'] != '' || $edit_group)
	{
		if(!$edit_group)
		{
			$res = sql_param_query('SELECT m_name FROM sb_maillist WHERE m_id = ?d',$_GET['id']);
			if($res)
			{
				list($m_oldname) = $res[0];
	        }
	        else
	        {
				sb_show_message(PL_MAILLIST_EDIT_ERROR, false, 'warning');
	            sb_add_system_message(sprintf(PL_MAILLIST_EDIT_SYSTEMLOG_ERROR, $m_name), SB_MSG_WARNING);
				$layout->deletePluginFieldsFiles();
				fMaillist_Edit();
				return;
			}
		}

		if(!$edit_group)
		{
	        sql_query('UPDATE sb_maillist SET ?a WHERE m_id=?d', $row, $_GET['id'], sprintf(PL_MAILLIST_EDIT_SYSTEMLOG_OK,$m_oldname,$m_name));
		}
		elseif(isset($ch_m_page_full_news) || isset($ch_m_title) || isset($ch_m_message) || isset($ch_m_news_temp_id) || isset($ch_m_conf_signed) ||
					isset($ch_m_fields) || isset($ch_su_fields_temps))
		{
			if(count($row) > 0)
			{
				sql_query('UPDATE sb_maillist SET ?a WHERE m_id IN (?a)', $row, $_GET['ids'], PL_MAILLIST_EDIT_SYSTEMLOG_GROUP_OK);
			}
			$update = sql_query('SELECT m_id, m_conf_signed, m_site_users_fields, m_site_users_custom_fields, m_mail_designs, m_news_temp_id
								FROM sb_maillist WHERE m_id IN (?a)', $_GET['ids']);
			if($update)
			{
				foreach($update as $k => $v)
				{
					list($m_id, $m_conf_signed, $m_site_users_fields, $m_site_users_custom_fields, $m_mail_designs, $m_news_temp_id) = $v;

					if(isset($ch_m_news_temp_id))
					{
						$m_news_temp_id = explode('|', $m_news_temp_id);
						foreach($ch_m_news_temp_id as $kl => $zn)
						{
							if($zn != 1)
							{
								continue;
							}
							foreach($m_news_temp_id as $k => $z)
							{
								$z = explode('::', $z);
								if($z[0] == $kl && isset($_POST['m_news_temp_id'][$kl]) && $_POST['m_news_temp_id'][$kl] != '')
								{
									$m_news_temp_id[$k] = $kl.'::'.$_POST['m_news_temp_id'][$kl];
								}
							}
						}

						$m_news_temp_id = implode('|', $m_news_temp_id);
						$row['m_news_temp_id'] = $m_news_temp_id;
					}

					if(isset($ch_m_page_full_news) || isset($ch_m_title) || isset($ch_m_message))
					{
						$m_mail_designs = unserialize($m_mail_designs);
					    foreach($GLOBALS['sb_site_langs'] as $k => $v)
					    {
							if(isset($ch_m_page_full_news[$k]) && $ch_m_page_full_news[$k] == 1 && isset($_POST['m_page_full_news'][$k]))
							{
								if(!isset($m_mail_designs[$k]))
									$m_mail_designs[$k] = array();

								$m_mail_designs[$k]['m_page_full_news'] = $_POST['m_page_full_news'][$k];
							}

							if(isset($ch_m_title[$k]) && $ch_m_title[$k] == 1 && isset($_POST['m_title'][$k]))
							{
								if(!isset($m_mail_designs[$k]))
									$m_mail_designs[$k] = array();

								$m_mail_designs[$k]['m_title'] = $_POST['m_title'][$k];
							}

							if(isset($ch_m_message[$k]) && $ch_m_message[$k] == 1 && isset($_POST['m_message'][$k]))
							{
								if(!isset($m_mail_designs[$k]))
									$m_mail_designs[$k] = array();

								$m_mail_designs[$k]['m_message'] = $_POST['m_message'][$k];
							}

					    }
						$m_mail_designs = serialize($m_mail_designs);
					}

					if(isset($ch_m_conf_signed))
					{
						$m_conf_signed = unserialize($m_conf_signed);
						foreach($ch_m_conf_signed as $k => $v)
						{
							if($v == 1 && isset($_POST['m_conf_signed'][$k]))
							{
								$m_conf_signed[$k] = $_POST['m_conf_signed'][$k];
							}
						}
						$m_conf_signed = serialize($m_conf_signed);
					}

					if(isset($ch_m_fields))
					{
						$m_site_users_fields = unserialize($m_site_users_fields);
						foreach($ch_m_fields as $k => $v)
						{
							if($v == 1 && isset($_POST['m_fields'][$k]))
							{
								$m_site_users_fields[$k] = $_POST['m_fields'][$k];
							}
						}
						$m_site_users_fields = serialize($m_site_users_fields);
					}

					if(isset($ch_su_fields_temps))
					{
						$m_site_users_custom_fields = unserialize($m_site_users_custom_fields);
						foreach($ch_su_fields_temps as $k => $v)
						{
							if($v == 1 && isset($_POST['su_fields_temps'][$k]))
							{
								$m_site_users_custom_fields[$k] = $_POST['su_fields_temps'][$k];
							}
						}
						$m_site_users_custom_fields = serialize($m_site_users_custom_fields);
					}

					$row = array();
					$row['m_conf_signed'] = $m_conf_signed;
					$row['m_site_users_fields'] = $m_site_users_fields;
					$row['m_site_users_custom_fields'] = $m_site_users_custom_fields;
					$row['m_mail_designs'] = $m_mail_designs;
					$row['m_news_temp_id'] = $m_news_temp_id;

					sql_query('UPDATE sb_maillist SET ?a WHERE m_id = ?d', $row, $m_id);
				}
			}
		}

		if(!$edit_group)
		{
			$footer_ar = fCategs_Edit_Elem();
	        if (!$footer_ar)
	        {
	            sb_show_message(PL_MAILLIST_EDIT_ERROR, false, 'warning');
	            sb_add_system_message(sprintf(PL_MAILLIST_EDIT_SYSTEMLOG_ERROR, $m_name), SB_MSG_WARNING);
	            $layout->deletePluginFieldsFiles();
	            fMaillist_Edit();
	            return;
	        }

	        $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
	        $footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

	        $row['m_id'] = $_GET['id'];

	        $html_str = fMaillist_Get($row);
	        $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
	        $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

	        echo '<script>
	                var res = new Object();
	                res.html = "'.$html_str.'";
	                res.footer = "'.$footer_str.'";
	                res.footer_link = "'.$footer_link_str.'";
	                sbReturnValue(res);
	              </script>';
		}
		else
		{
	        echo '<script>
	                sbReturnValue("refresh");
	              </script>';
		}

		sb_mail_workflow_status('pl_maillist', (!$edit_group ? $_GET['id'] : $_GET['ids']), $m_name, $m_active);
    }
    else
    {
        $error = true;

        $res = sql_param_query('SELECT m_name FROM sb_maillist WHERE m_name = ?',$m_name);
        if($res)
        {
            sb_show_message(PL_MAILLIST_ADD_ERROR, false, 'warning');
            $layout->deletePluginFieldsFiles();
            fMaillist_Edit();
            return;
        }

        if (sql_param_query('INSERT INTO sb_maillist SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id))
            {
                sb_add_system_message(sprintf(PL_MAILLIST_ADD_OK, $m_name));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

				sb_mail_workflow_status('pl_maillist', $id, $m_name, $m_active);
                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_maillist WHERE m_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(PL_MAILLIST_ADD_ERROR2, false, 'warning');
            sb_add_system_message(sprintf(PL_MAILLIST_ADD_SYSTEMLOG_ERROR, $m_name), SB_MSG_WARNING);
            $layout->deletePluginFieldsFiles();
            fMaillist_Edit();
            return;
        }
	}


//	если рассылка используется для рассылки новостей, то добавляем для нее задание в планировщик заданий или удалям если не используется.
	if(!$edit_group || $edit_group && $ch_m_use_default == 1)
	{
		$arr = ($edit_group ? $_GET['ids'] : array($_GET['id']));
		if ($m_use_default == 1)
		{
			$count = 0;
			if (!isset($_SESSION['sbPlugins']->mCron['pl_maillist']))
			{
				$_SESSION['sbPlugins']->mCron['pl_maillist'] = array();
			}
			else
			{
				$count = count($_SESSION['sbPlugins']->mCron['pl_maillist']);
			}

			foreach($arr as $val)
			{
				if($edit_group)
				{
					$name = (isset($ch_m_name) && $ch_m_name == 1 && $m_name != '' ? $m_name.' ' : '').'('.$val.')';
				}
				else
				{
					$name = $m_name;
				}

				$_SESSION['sbPlugins']->mCron['pl_maillist'][$count] = array();
				$_SESSION['sbPlugins']->mCron['pl_maillist'][$count]['task_path'] = 'pl_maillist/cron/pl_maillist.php';
		       	$_SESSION['sbPlugins']->mCron['pl_maillist'][$count]['task_func'] = 'fMaillist_Cron('.($val != '' ? $val : $id).')';
				$_SESSION['sbPlugins']->mCron['pl_maillist'][$count]['task_name'] = $name;
				$_SESSION['sbPlugins']->mCron['pl_maillist'][$count]['task_descr'] = sprintf(PL_MAILLIST_SUBMIT_CRON_DESCRIPTION, $m_name);
				$count++;
			}
		}
		elseif($m_use_default == 0 && isset($_SESSION['sbPlugins']->mCron['pl_maillist']))
		{
			foreach($_SESSION['sbPlugins']->mCron['pl_maillist'] as $key => $value)
			{
				foreach($arr as $val)
				{
					if($value['task_func'] == 'fMaillist_Cron('.($val != '' ? $val : $id).')')
					{
						unset($_SESSION['sbPlugins']->mCron['pl_maillist'][$key]);
						break;
					}
				}
			}
		}
	}
}

function fMaillist_Delete()
{
//	если есть задание в планировщике для удаляемой рассылки, то ее тоже убираем.
	if(isset($_SESSION['sbPlugins']->mCron['pl_maillist']))
	{
		foreach($_SESSION['sbPlugins']->mCron['pl_maillist'] as $key => $value)
		{
			if($value['task_func'] == 'fMaillist_Cron('.$_GET['id'].')')
			{
				unset($_SESSION['sbPlugins']->mCron['pl_maillist'][$key]);
				sql_query('DELETE FROM sb_cron WHERE sc_func_name = ?', 'fMaillist_Cron('.$_GET['id'].')');
				break;
			}
		}
	}
}

function fMaillist_Delete_With_Elements()
{
//	если есть задание в планировщике для удаляемой рассылки, то его тоже убираем.
	if(isset($_SESSION['sbPlugins']->mCron['pl_maillist']) && isset($_GET['elems_id']) && $_GET['elems_id'] != '')
	{
		$elems = explode(',',$_GET['elems_id']);
		$funcs = array();
		foreach($_SESSION['sbPlugins']->mCron['pl_maillist'] as $key => $value)
		{
			foreach($elems as $value2)
			{
				if($value['task_func'] == 'fMaillist_Cron('.$value2.')')
				{
					$funcs[] = 'fMaillist_Cron('.$value2.')';
					unset($_SESSION['sbPlugins']->mCron['pl_maillist'][$key]);
				}
			}
		}

		if(count($funcs) > 0)
		{
			sql_query('DELETE FROM sb_cron WHERE sc_func_name IN (?a)', $funcs);
		}
	}
}

function fMaillist_After_Paste()
{
	if (!isset($_GET['action']) || $_GET['action'] != 'copy' || !isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['ne']) || !is_array($_GET['ne']) || count($_GET['ne']) <= 0)
		return;

	if (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_maillist'))
		return;

	$res = sql_query('SELECT m_id, m_active FROM sb_maillist WHERE m_id IN ('.implode(',', $_GET['ne']).')');

	if ($res)
	{
		foreach ($res as $value)
		{
			list($m_id, $m_active) = $value;

			if (!sb_workflow_status_available($m_active, 'pl_maillist', -1))
			{
				sql_query('UPDATE sb_maillist SET m_active=?d WHERE m_id=?d', current(sb_get_avail_workflow_status('pl_maillist')), $m_id);
			}
		}
	}
}

function fMaillist_After_Paste_Categs_With_Elements()
{
	if (!isset($_SESSION['paste_categs_with_elems_ids']) ||
	!isset($_SESSION['paste_categs_with_elems_ids']['old']) ||
	!isset($_SESSION['paste_categs_with_elems_ids']['new']))
	{
		return;
	}

	$_GET['e'] = array_values($_SESSION['paste_categs_with_elems_ids']['old']);
	$_GET['ne'] = array_values($_SESSION['paste_categs_with_elems_ids']['new']);
	$_GET['action'] = 'copy';

	fMaillist_After_Paste();
}

function fMaillist_Set_Active()
{
	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_maillist', 'elems_public'))
		return;

	sbIsGroupEdit(false);

	$date = time();
	foreach ($_GET['ids'] as $val)
    {
       	sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $val, 'pl_maillist', $date, $_SESSION['sbAuth']->getUserId(), 'edit');
    }

    $res = sql_param_query('UPDATE sb_maillist SET m_active=IF(m_active=0,1,0) WHERE m_id IN (?a)', $_GET['ids'], PL_MAILLIST_SET_ACTIVE_EDIT_OK);
    if ($res)
    	echo 'TRUE';
}

function fMaillist_Cat_Edit()
{
	$cat_fields = array();
	$cat_closed = 0;
	$cat_fields['cat_maillist_description'] = '';
	$cat_fields['m_conf_email'] = '';
	$cat_fields['m_conf_charset'] = '';
	$cat_fields['m_unsub_page'] = '';
	$cat_fields['m_fields']['su_login'] = '';
	$cat_fields['m_fields']['su_email'] = '';
    $cat_fields['m_fields']['su_reg_date'] = '';
    $cat_fields['m_fields']['su_last_date'] = '';
	$cat_fields['m_fields']['su_name'] = '';
    $cat_fields['m_fields']['su_pers_foto'] = '';
    $cat_fields['m_fields']['su_pers_phone'] = '';
    $cat_fields['m_fields']['su_pers_mob_phone'] = '';
    $cat_fields['m_fields']['su_pers_birth'] = '';
    $cat_fields['m_fields']['su_pers_sex'] = '';
    $cat_fields['m_fields']['su_pers_zip'] = '';
    $cat_fields['m_fields']['su_pers_adress'] = '';
	$cat_fields['m_fields']['su_pers_addition'] = '';
	$cat_fields['m_fields']['su_work_name'] = '';
    $cat_fields['m_fields']['su_work_unit'] = '';
    $cat_fields['m_fields']['su_work_position'] = '';
    $cat_fields['m_fields']['su_work_phone'] = '';
    $cat_fields['m_fields']['su_work_phone_inner'] = '';
    $cat_fields['m_fields']['su_work_fax'] = '';
	$cat_fields['m_fields']['su_work_email'] = '';
    $cat_fields['m_fields']['su_work_office_number'] = '';
    $cat_fields['m_fields']['su_work_addition'] = '';
	$cat_fields['su_fields_temps'] = '';

	if (isset($_GET['cat_id']))
	{
		if (!fCategs_Check_Rights($_GET['cat_id']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

		$res = sql_param_query('SELECT cat_title, cat_closed, cat_fields, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
        if ($res)
        {
            list($cat_title, $cat_closed, $p_cat_fields, $cat_rights) = $res[0];
            if ($p_cat_fields != '')
                $cat_fields = unserialize($p_cat_fields);

            $_GET['cat_id_p'] = '';
        }
        else
        {
			sb_show_message(PL_MAILLIST_EDIT_CAT_ERROR_NO_CATEG, true, 'warning');
			return;
        }
    }
	else
    {
        if (!fCategs_Check_Rights($_GET['cat_id_p']))
        {
			sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
			return;
        }

        $res = sql_param_query('SELECT cat_closed, cat_fields, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id_p']);
        if ($res)
        {
            $cat_title = '';
            list($cat_closed, $p_cat_fields, $cat_rights) = $res[0];
            if ($p_cat_fields != '')
            {
                $p_cat_fields = unserialize($p_cat_fields);

                if (isset($p_cat_fields['cat_status']))
                    $cat_fields['cat_status'] = $p_cat_fields['cat_status'];

                if(isset($p_cat_fields['m_fields']))
                	$cat_fields['m_fields'] = $p_cat_fields['m_fields'];

                if(isset($p_cat_fields['m_conf_format']))
                	$cat_fields['m_conf_format'] = $p_cat_fields['m_conf_format'];

                if(isset($p_cat_fields['m_conf_email']) && $p_cat_fields['m_conf_email'] != '')
                	$cat_fields['m_conf_email'] = $p_cat_fields['m_conf_email'];
                else
                	$cat_fields['m_conf_email'] = 'info@{DOMAIN}';

                if(isset($p_cat_fields['m_conf_charset']) && $p_cat_fields['m_conf_charset'] != '')
					$cat_fields['m_conf_charset'] = $p_cat_fields['m_conf_charset'];
				else
					$cat_fields['m_conf_charset'] = 'UTF-8';

                if(isset($p_cat_fields['m_conf_images_attach']))
                	$cat_fields['m_conf_images_attach'] = $p_cat_fields['m_conf_images_attach'];

                if(isset($p_cat_fields['m_conf_signed']))
                	$cat_fields['m_conf_signed'] = $p_cat_fields['m_conf_signed'];

                if(isset($p_cat_fields['su_fields_temps']))
					$cat_fields['su_fields_temps'] = $p_cat_fields['su_fields_temps'];

                if(isset($p_cat_fields['m_unsub_page']))
					$cat_fields['m_unsub_page'] = $p_cat_fields['m_unsub_page'];
            }

            $_GET['cat_id'] = '';
        }
        else
        {
            sb_show_message(PL_MAILLIST_EDIT_CAT_ERROR_NO_CATEG, true, 'warning');
            return;
        }
    }

	echo '<script>
        function checkValues()
        {
            var cat_title = sbGetE("cat_title");
            if (cat_title.value == "")
            {
                alert("'.PL_MAILLIST_EDIT_CAT_NO_TITLE_MSG.'");
                return false;
            }

            return true;
        }

        function browseGroups()
        {
            var group_ids = sbGetE("group_idspl_maillist_read").value;
            var group_names = sbGetE("group_namespl_maillist_read");

            if (!group_names.disabled)
            {
                strPage = "'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_get_groups&sel_cat_ids="+group_ids;
                strAttr = "resizable=1,width=500,height=500";
                sbShowModalDialog(strPage, strAttr, afterBrowseGroups);
            }
        }

        function afterBrowseGroups()
        {
            if (sbModalDialog.returnValue)
            {
                var group_ids = sbGetE("group_idspl_maillist_read");
                var group_names = sbGetE("group_namespl_maillist_read");
                group_ids.value = sbModalDialog.returnValue.ids;
                group_names.value = sbModalDialog.returnValue.text;
            }
        }

        function changeType(el)
        {
            if (el.checked)
            {
                sbGetE("group_namespl_maillist_read").disabled = false;
            }
            else
            {
                sbGetE("group_namespl_maillist_read").disabled = true;
                sbGetE("group_namespl_maillist_read").value = "";
                sbGetE("group_idspl_maillist_read").value = "";
            }
        }
    </script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_maillist_cat_edit_submit&cat_id='.$_GET['cat_id'].'&cat_id_p='.$_GET['cat_id_p'], 'thisDialog', 'post', 'checkValues()');
    $layout->mTitleWidth = 200;

    $layout->addTab(PL_MAILLIST_TAB1);
    $layout->addHeader(PL_MAILLIST_TAB1);

    $layout->addField(PL_MAILLIST_EDIT_CAT_TITLE, new sbLayoutInput('text', $cat_title, 'cat_title', '', 'style="width:400px;"', true));
    $layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutTextarea($cat_fields['cat_maillist_description'], 'cat_fields[cat_maillist_description]', '', 'style="width:100%"');
    $layout->addField(PL_MAILLIST_EDIT_CAT_DESCRIPTION,$field);

	$layout->addField('', new sbLayoutDelim());

	$field = new sbLayoutInput('checkbox', '1', 'cat_closed', '', 'onclick="changeType(this);"'.($cat_closed ? ' checked="checked"' : ''));
	$layout->addField(PL_MAILLIST_EDIT_CAT_CLOSED,$field);

	$ident = 'pl_maillist_read';

	$group_ids = '';
	$group_names = '';

	$res = sql_param_query('SELECT group_ids FROM sb_catrights WHERE cat_id=?d AND right_ident=?', $_GET['cat_id'], $ident);

	if($res)
    {
        list($group_ids) = $res[0];
        $ids = explode('^', trim($group_ids, '^'));

        $res = sql_param_query('SELECT cat_title FROM sb_categs WHERE cat_id IN (?a)', $ids);

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

	$layout->addField('', new sbLayoutInput('hidden', $group_ids, 'group_ids'.$ident));
    $layout->addField(PL_MAILLIST_EDIT_CAT_CLOSED_GROUPS, new sbLayoutHTML('
                     <input id="group_names'.$ident.'" name="group_names'.$ident.'"'.(!$cat_closed ? ' disabled="disabled"' : '').' readonly="readonly" style="width:75%;" value="'.$group_names.'">
                     &nbsp;&nbsp;<img class="button" src="'.SB_CMS_IMG_URL.'/users.png" align="absmiddle" id="group_btn'.$ident.'" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups();" width="20" height="20" title="'.KERNEL_BROWSE.'" />
                     '));

	$layout->getPluginFields('pl_maillist', $_GET['cat_id'], '', true);

    $layout->addTab(PL_MAILLIST_TAB2);
    $layout->addHeader(PL_MAILLIST_TAB2);

    $field = new sbLayoutSelect(array(PL_MAILLIST_MESSAGE_HTML , PL_MAILLIST_MESSAGE_TEXT), 'cat_fields[m_conf_format]');
    $field->mSelOptions = array(isset($cat_fields['m_conf_format']) ? $cat_fields['m_conf_format'] : '');
    $layout->addField(PL_MAILLIST_EDIT_MESSAGE_FORMAT, $field);

    $layout->addField(PL_MAILLIST_EDIT_EMAIL, new sbLayoutInput('text', (isset($cat_fields['m_conf_email']) ? $cat_fields['m_conf_email'] : ''), 'cat_fields[m_conf_email]', '', '', true) );
    $layout->addField(PL_MAILLIST_EDIT_CHARSET, new sbLayoutInput('text', (isset($cat_fields['m_conf_charset']) ? $cat_fields['m_conf_charset'] : ''), 'cat_fields[m_conf_charset]', '', '', true) );
    $layout->addField(PL_MAILLIST_EDIT_IMAGES_ATTACH, new sbLayoutInput('checkbox', '1', 'cat_fields[m_conf_images_attach]', '', ((isset($cat_fields['m_conf_images_attach']) && $cat_fields['m_conf_images_attach'] == '1') ? 'checked' : '')));

	$layout->addField(PL_MAILLIST_EDIT_UNSUB_PAGE_FILED_TITLE, new sbLayoutPage((isset($cat_fields['m_unsub_page']) ? $cat_fields['m_unsub_page'] : ''), 'cat_fields[m_unsub_page]', '', 'style="width:95%;"'));

	require_once(SB_BASEDIR . '/cms/lang/prog_'.SB_DB_CHARSET.'.lng.php');

    $user_tags = array();
    $user_tags_values = array();
    $layout->getPluginFieldsTags('pl_maillist', $user_tags, $user_tags_values);

    foreach($GLOBALS['sb_site_langs'] as $key => $value)
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.sprintf(PL_MAILLIST_EDIT_SIGNED_LANGUAGE,$GLOBALS['sb_site_langs'][$key]).'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

		$fld = new sbLayoutTextarea((isset($cat_fields['m_conf_signed'][$key]) ? $cat_fields['m_conf_signed'][$key] : ''),'cat_fields[m_conf_signed]['.$key.']','','style = "width:100%"');
		$fld->mTags = array_merge(array(sprintf(PL_MAILLIST_EDIT_UNSUB_LINK_TAG_VAL, SB_COOKIE_DOMAIN)), $user_tags);
		$fld->mValues = array_merge(array(PL_MAILLIST_EDIT_UNSUB_LINK_TAG), $user_tags_values);
		$layout->addField(PL_MAILLIST_EDIT_SIGNED, $fld);
    }

	$layout->addTab(PL_MAILLIST_TAB4);
	$layout->addHeader(PL_MAILLIST_TAB4);

//	require_once(SB_CMS_LANG_PATH.'/pl_site_users.lng.php');

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_DESIGN_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

	$tags = array('{VALUE}', '{SU_ID}');
    $values = array(PL_MAILLIST_EDIT_FIELD_VALUE, PL_MAILLIST_EDIT_SU_ID);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_login']) ? $cat_fields['m_fields']['su_login'] : ''), 'cat_fields[m_fields][su_login]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_LOGIN, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_email']) ? $cat_fields['m_fields']['su_email'] : ''), 'cat_fields[m_fields][su_email]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_EMAIL, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_reg_date']) ? $cat_fields['m_fields']['su_reg_date'] : ''), 'cat_fields[m_fields][su_reg_date]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{SU_ID}');
    $field->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_GET_REG_DATE, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_last_date']) ? $cat_fields['m_fields']['su_last_date'] : ''), 'cat_fields[m_fields][su_last_date]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{SU_ID}');
    $field->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_GET_LAST_DATE, $field);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_PERS_TAB.'</div>';
    $layout->addField('',new sbLayoutHTML($html,true));

    $layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_name']) ? $cat_fields['m_fields']['su_name'] : ''), 'cat_fields[m_fields][su_name]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_FIO, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_foto']) ? $cat_fields['m_fields']['su_pers_foto'] : ''), 'cat_fields[m_fields][su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{IMG_LINK}', '{SU_ID}');
    $field->mValues = array(PL_MAILLIST_EDIT_SU_FOTO_LINK, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_AVATAR, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_phone']) ? $cat_fields['m_fields']['su_pers_phone'] : '') , 'cat_fields[m_fields][su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_PHONE, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_mob_phone']) ? $cat_fields['m_fields']['su_pers_mob_phone'] : ''), 'cat_fields[m_fields][su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_MOBILE, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_birth']) ? $cat_fields['m_fields']['su_pers_birth'] : ''), 'cat_fields[m_fields][su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $field->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{SU_ID}');
    $field->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, PL_MAILLIST_EDIT_SU_ID);
    $layout->addField(PL_MAILLIST_EDIT_SU_BIRTH, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_sex']) ? $cat_fields['m_fields']['su_pers_sex'] : ''), 'cat_fields[m_fields][su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_SEX, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_zip']) ? $cat_fields['m_fields']['su_pers_zip'] : ''), 'cat_fields[m_fields][su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_ZIP, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_adress']) ? $cat_fields['m_fields']['su_pers_adress'] : ''), 'cat_fields[m_fields][su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_ADRESS, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_pers_addition']) ? $cat_fields['m_fields']['su_pers_addition'] : ''), 'cat_fields[m_fields][su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_ADDITION, $field);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_CORPORATE_TAB.'</div>';
    $layout->addField('',new sbLayoutHTML($html,true));

    $layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_name']) ? $cat_fields['m_fields']['su_work_name'] : ''), 'cat_fields[m_fields][su_work_name]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_NAME, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_unit']) ? $cat_fields['m_fields']['su_work_unit'] : ''), 'cat_fields[m_fields][su_work_unit]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_OTDEL, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_position']) ? $cat_fields['m_fields']['su_work_position'] : ''), 'cat_fields[m_fields][su_work_position]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_DOLJ, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_phone']) ? $cat_fields['m_fields']['su_work_phone'] : ''), 'cat_fields[m_fields][su_work_phone]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_PHONE, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_phone_inner']) ? $cat_fields['m_fields']['su_work_phone_inner'] : ''), 'cat_fields[m_fields][su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_PHONE_INNER, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_fax']) ? $cat_fields['m_fields']['su_work_fax'] : ''), 'cat_fields[m_fields][su_work_fax]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_FAX, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_email']) ? $cat_fields['m_fields']['su_work_email'] : ''), 'cat_fields[m_fields][su_work_email]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_EMAIL, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_office_number']) ? $cat_fields['m_fields']['su_work_office_number'] : ''), 'cat_fields[m_fields][su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_OFFICE, $field);

    $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_work_addition']) ? $cat_fields['m_fields']['su_work_addition'] : ''), 'cat_fields[m_fields][su_work_addition]', '', 'style="width:100%;height:50px;"');
    $field->mTags = $tags;
    $field->mValues = $values;
    $layout->addField(PL_MAILLIST_EDIT_SU_WORK_ADDITION, $field);

    $forum_tags = array();
    $forum_tags_labels = array();

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum') === true)
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_FORUM_TAB.'</div>';
        $layout->addField('', new sbLayoutHTML($html,true));
        $layout->addField('', new sbLayoutDelim());

        $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_forum_nick']) ? $cat_fields['m_fields']['su_forum_nick'] : ''), 'cat_fields[m_fields][su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $field->mTags = $tags;
        $field->mValues = $values;
        $layout->addField(PL_MAILLIST_EDIT_SU_NICK, $field);

        $field = new sbLayoutTextarea((isset($cat_fields['m_fields']['su_forum_text']) ? $cat_fields['m_fields']['su_forum_text'] : ''), 'cat_fields[m_fields][su_forum_text]', '', 'style="width:100%;height:50px;"');
        $field->mTags = $tags;
        $field->mValues = $values;
        $layout->addField(PL_MAILLIST_EDIT_SU_SIGNATURE, $field);

		$forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
		$forum_tags_labels = array(PL_MAILLIST_EDIT_SU_FORUM_TAB, PL_MAILLIST_EDIT_SU_NICK, PL_MAILLIST_EDIT_SU_SIGNATURE);
    }

    if(count($user_tags) > 0)
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_USERS_TAB.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $layout->addPluginFieldsTemps('pl_maillist', (isset($cat_fields['su_fields_temps']) ? $cat_fields['su_fields_temps'] : ''), 'su_', $tags, $values);
	}

	$su_tags = array();
	$su_tags_values = array();
	$layout->getPluginFieldsTags('pl_site_users', $su_tags, $su_tags_values);

    if(count($su_tags) != 0)
    {
        $tags = array('{SU_ID}');
        $values = array(PL_MAILLIST_EDIT_SU_ID);

        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_EDIT_SU_USERS_GROUP_TAB.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

		$layout->addPluginFieldsTemps('pl_site_users', (isset($cat_fields['su_fields_temps']) ? $cat_fields['su_fields_temps'] : ''), 'su_', $tags, $values);
    }

	$layout->addField('', new sbLayoutInput('hidden', $cat_rights, 'cat_rights'));

	$layout->addButton('submit', KERNEL_SAVE);
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');

	$layout->show();
}

function fMaillist_Cat_Edit_Submit()
{
	$cat_rights = '';
	$cat_closed = 0;
	$cat_fields = array();

	if(isset($_POST['su_fields_temps']))
	{
		$_POST['cat_fields']['su_fields_temps'] = $_POST['su_fields_temps'];
	}

	extract($_POST);
	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$user_row = $layout->checkPluginFields('pl_maillist', $_GET['cat_id'], '', true);

	if ($user_row === false)
    {
        $layout->deletePluginFieldsFiles();
        fMaillist_Cat_Edit();
        return;
    }

    $cat_fields = array_merge($cat_fields, $user_row);

    if($_GET['cat_id'] != '')
    {
        if (!fCategs_Check_Rights($_GET['cat_id']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        $cat_id = intval($_GET['cat_id']);

        //редактирование
        $res = sql_param_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $cat_id);
        if ($res)
        {
            sql_query('LOCK TABLES sb_categs WRITE, sb_catrights WRITE');
            $result = sql_param_query('UPDATE sb_categs SET cat_title=?, cat_closed=?d, cat_fields=? WHERE cat_id=?d', $cat_title, $cat_closed, serialize($cat_fields), $cat_id, sprintf(PL_MAILLIST_EDIT_CAT_SYSLOG_EDIT_OK, $cat_title));
            if ($result)
            {
                sql_param_query('DELETE FROM sb_catrights WHERE cat_id=?d', $cat_id);

                if ($cat_closed == 1)
                {
                    // закрытый раздел
                    $ident = 'pl_maillist_read';
                    sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                                     VALUES (?d, ?, ?)', $cat_id, $_POST['group_ids'.$ident], $ident);
                }
                sql_query('UNLOCK TABLES');


                list($cat_left, $cat_right) = $res[0];

                $count_res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident="pl_maillist" AND categs.cat_left >= '.$cat_left.' AND categs.cat_right <= '.$cat_right.' AND links.link_cat_id=categs.cat_id');
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
                    res.cat_title = "'.str_replace('"', '\\"', $cat_title).' ['.$cat_count.']";
                    res.cat_closed = '.intval($cat_closed).';
                    res.cat_rubrik = 0;
                    sbReturnValue(res);
                  </script>';
            }
            else
            {
                sql_query('UNLOCK TABLES');
            }
        }
        else
        {
            sb_show_message(PL_MAILLIST_EDIT_CAT_EDIT_ERROR, true, 'warning');
            sb_add_system_message(sprintf(PL_MAILLIST_EDIT_CAT_SYSLOG_EDIT_ERROR, $cat_title), SB_MSG_WARNING);
        }
    }
    else
    {
        if (!fCategs_Check_Rights($_GET['cat_id_p']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        //добавление
        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        $tree = new sbTree('pl_maillist');

        $fields = array();
        $fields['cat_title'] = $cat_title;
        $fields['cat_rubrik'] = 0;
        $fields['cat_closed'] = intval($cat_closed);
        $fields['cat_rights'] = $cat_rights; //$_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();
        $fields['cat_fields'] = serialize($cat_fields);

        $cat_id = $tree->insertNode($_GET['cat_id_p'], $fields);
        if ($cat_id)
        {
            if ($cat_closed == 1)
            {
                // закрытый раздел
                $ident = 'pl_maillist_read';
                sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                                        VALUES (?d, ?, ?)', $cat_id, $_POST['group_ids'.$ident], $ident);
            }

            sb_add_system_message(sprintf(PL_MAILLIST_EDIT_CAT_SYSLOG_ADD_OK, $cat_title));

            echo '<script>
                var res = new Object();
                res.cat_id = '.$cat_id.';
                res.cat_title = "'.str_replace('"', '\\"', $cat_title).' [0]";
                res.cat_closed = '.intval($cat_closed).';
                res.cat_rubrik = 0;
                sbReturnValue(res);
              </script>';
        }
        else
        {
            sb_show_message(PL_MAILLIST_EDIT_CAT_ADD_ERROR, true, 'warning');
            sb_add_system_message(sprintf(PL_MAILLIST_EDIT_CAT_SYSLOG_ADD_ERROR, $cat_title), SB_MSG_WARNING);
        }
    }
}

function fMaillist_Send()
{
	if (!fCategs_Check_Elems_Rights($_GET['id'], -1, 'pl_maillist'))
		return;

    $m_to_names = '';
    $m_to_ids = '';
    $m_all_cat_ids = '';

    echo '<script>
                var num_files = 1
                function addFile()
                {
                   var el = sbGetE("span_files");

                   var br = document.createElement("BR");
                   var file = document.createElement("INPUT");

                   file.type = "file";
                   file.name = "m_files[" + num_files + "]";
                   file.style.margin = "2px";
                   file.size = "55";
                   el.appendChild(br);
                   el.appendChild(file);
                   num_files++;
                }

                function checkValues()
                {
                    var el_to_ids = sbGetE("m_to_ids");
                    var el_mail = sbGetE("m_conf_email");
                    var el_charset = sbGetE("m_conf_charset");

                    if(el_to_ids.value == "" && sbGetE("test_mode_id").checked == false)
                    {
                        alert("'.PL_MAILLIST_MESSAGES_NO_USERS_MSG.'");
                        return false;
                    }';

					$tmp = '';
                    foreach ($GLOBALS['sb_site_langs'] as $key => $value)
                    {
						echo'
						if(typeof(sbCodeditor_m_title_'.$key.') != "undefined")
				        {
							var m_title'.$key.' = sbCodeditor_m_title_'.$key.'.getCode();
			        	}
			    	    else
				        {
							var m_title'.$key.' = sbGetE("m_title_'.$key.'").value;
						}

						if(typeof(sbCodeditor_m_message_'.$key.') != "undefined")
				        {
							var m_message'.$key.' = sbCodeditor_m_message_'.$key.'.getCode();
			        	}
			    	    else
				        {
							var m_message'.$key.' = sbGetE("m_message_'.$key.'").value;
						}

                        if(!m_title'.$key.' && m_message'.$key.')
                        {
                            alert("'.PL_MAILLIST_MESSAGES_NO_TITLE_OR_MSG.'");
                            return false;
                        }

                        if(m_title'.$key.' && !m_message'.$key.')
                        {
                            alert("'.PL_MAILLIST_MESSAGES_TITLE_NO_MSG.'");
                            return false;
                        }';

                        if($tmp == '')
                        {
                            $tmp = 'if(m_message'.$key.' == ""';
                        }
                        else
                        {
                            $tmp .= '&& m_message'.$key.' == ""';
                        }
					}

					$tmp .=')
                            {
                                alert("'.PL_MAILLIST_MESSAGES_NO_MSG.'");
                                return false;
                            }';

				echo 	$tmp.'
                		if(el_mail.value == "")
                        {
                            alert("'.PL_MAILLIST_EDIT_ERROR_NO_EMAIL.'");
                            return false;
                        }

                        if(el_charset.value == "")
                        {
                            alert("'.PL_MAILLIST_EDIT_ERROR_NO_CHARSET.'");
                            return false;
                        }

                        sbShowMsgDiv("'.PL_MAILLIST_SENDING_WAIT.'", "information.png", false);
                        sbShowDisDiv();
                        return true;
				}

                var signed_user_title = "";
                var signed_user_ids = "";

                function AllUsers()
				{
                    show = sbGetE("show_all_user");
                    if(show.checked)
					{
                        if(signed_user_ids == "" && signed_user_title == "")
						{
                            sbGetE("m_to_ids").value = "";
                        	sbGetE("m_all_cat_ids").value = "";
                        }
                        else
						{
                            sbGetE("m_to_ids").value = signed_user_ids;
                            sbGetE("m_all_cat_ids").value = signed_user_ids;
                        	sbGetE("m_to_names").value = signed_user_title;
                        }

						sbGetE("m_message_send_all").value = 0;
                    }
                    else
                    {
						signed_user_ids = sbGetE("m_to_ids").value;
                        	signed_user_title = sbGetE("m_to_names").value;

                        sbLoadAsync(sb_cms_empty_file + "?event=pl_maillist_all_users", afterLoadAllUsers);
                    	sbGetE("m_message_send_all").value = 1;
                	}
				}

				function afterLoadAllUsers(res)
                {
                	res = res.split("|");
                    var to = res[1].split(",");

                    var to_ids = "";
                    var all_cat_ids = "";
                    for(var i = 0; i < to.length; i++)
                    {
                    	to_ids += "^"+to[i];
                        all_cat_ids += to[i]+",";
					}

                    var to_ids = sbGetE("m_to_ids").value = to_ids;
                    var all_cat_ids = sbGetE("m_all_cat_ids").value = all_cat_ids;

                    sbGetE("m_to_names").value = "";
				}

				function selectUsers()
                {
                	var um_to_ids = sbGetE("m_to_ids");
                    var um_all_cat_ids = sbGetE("m_all_cat_ids");
                    strPage = "'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_maillist_select_user&sel_cat_ids=" + um_to_ids.value + "&all_cat_ids=" + um_all_cat_ids.value;
                    strAttr = "resizable=1,width=500,height=400";
                    sbShowModalDialog(strPage, strAttr, afterSelectUsers);
				}

                function afterSelectUsers()
                {
                	var rV = sbModalDialog.returnValue;
                	if (rV)
                    {
                    	sbGetE("m_to_names").value = rV.names;
                        sbGetE("m_to_ids").value = rV.ids;
					}
				}
		</script>';

	echo '<div  id = "sendStat" style = "display:none">
	<table  width = "100%" ><tr><td align = "center" colspan = 2><b>'.PL_MAILLIST_SEND_PROCESS.'</b>
	<tr><td>'.PL_MAILLIST_COUNT_SUBSCRIBER.':<td id = "users_count"><b>0</b><tr><td>'.PL_MAILLIST_SENT_MSG.':<td id = "sended_messages"><b>0</b><tr><td>'.PL_MAILLIST_REMAINED.':<td id = "time_need"><b>0</b></table>
	<hr><center><input type = "button" value = "'.KERNEL_CLOSE.'" onclick = "window.close();"></center></div>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_DIALOG_FILE.'?event=pl_maillist_send_submit&id='.$_GET['id'], 'thisDialog', 'post', 'checkValues()', 'sendForm', 'enctype="multipart/form-data"');
    $layout->mTableWidth = '95%';

    $layout->addTab(PL_MAILLIST_TAB3);
    $layout->addHeader(PL_MAILLIST_TAB3);

    $um_to_name = '';
    $um_to_id = '';

    /***************************************** НАСТРОЙКИ РАССЫЛКИ ***************************/

	$news_tags = array();
	$news_values = array();
    if(count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
		$res = sql_param_query('SELECT m_id, m_conf_format, m_conf_email, m_conf_charset, m_conf_images_attach, m_use_default, m_mail_designs, m_news_temp_id  FROM sb_maillist WHERE m_id = ?d',intval($_GET['id']));
        if($res)
        {
			list($m_id, $m_conf_format, $m_conf_email, $m_conf_charset, $m_conf_images_attach, $m_use_default, $m_mail_designs, $m_news_temp_id) = $res[0];

			if($m_use_default == 1 && $m_mail_designs != '')
			{
		    	$news_tags = array('-', '{NEWS_LIST}');
    			$news_values = array(PL_MAILLIST_EDIT_NEWS_TITLE, PL_MAILLIST_EDIT_NEWS_LIST);

				$m_message = array();
				$m_title = array();

				$m_mail_designs = unserialize($m_mail_designs);
				foreach($m_mail_designs as $key => $value)
				{
					$m_message[$key] = isset($value['m_message']) ? $value['m_message'] : '';
					$m_title[$key] = isset($value['m_title']) ? $value['m_title'] : '';
					$m_page_full_news[$key] = isset($value['m_page_full_news']) ? $value['m_page_full_news'] : '';
				}
			}
		}
		else
		{
			sb_show_message(PL_MAILLIST_MESSAGES_NO_MAILLIST_SELECTED, false, 'warning');
			return;
        }
    }

	if(count($_POST) == 0)
	{
		$m_to_ids = '';
		$m_to_names = '';
		$m_all_cat_ids = '';

        $link_keys = array();
        $link_keys[$m_id] = $m_id;
        $res = sql_param_query('SELECT cat.cat_title, link.link_cat_id, user.su_mail_subscription FROM sb_site_users user, sb_catlinks link, sb_categs cat WHERE user.su_id = link.link_el_id AND cat.cat_ident = "pl_site_users" AND cat.cat_id = link.link_cat_id');

        if($res)
        {
            foreach ($res as $key => $value)
            {
                list($cat_title, $link_cat_id, $su_mail_subscription) = $value;
                $su_mail_subscription = explode(',', $su_mail_subscription);

				$tmp_su_mail = array();
				foreach($su_mail_subscription as $key => $value)
				{
					$tmp_su_mail[$value] = $value;
				}
				$su_mail_subscription = $tmp_su_mail;

                if(is_array($su_mail_subscription) && is_array($link_keys) && count(array_intersect_key($su_mail_subscription, $link_keys)) > 0)
                {
                    if(strpos($m_to_ids, $link_cat_id) === false)
                    {
                        if($m_to_ids != '')
                        	$m_to_ids .= ',';
                        if($m_to_names != '')
                        	$m_to_names .= ' , ';

                        $m_to_ids .= $link_cat_id;
                        $m_all_cat_ids = $m_to_ids;
                        $m_to_names .= $cat_title;
                    }
                }
            }
        }
        else
        {
			sb_show_message(PL_MAILLIST_MESSGAES_NO_USERS_TO_SEND, false, 'warning');
			return;
		}
	}

	/*************************************** НАСТРОЙКИ РАССЫЛКИ КОНЕЦ ****************************************************/

    /************************************************* ТЕГИ **************************************************************/

    $cat_tags = array();
    $cat_tags_values = array();
    $forum_tags = array();
    $forum_tags_labels = array();
    $layout->getPluginFieldsTags('pl_site_users', $cat_tags, $cat_tags_values);

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum') === true)
    {
        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
        $forum_tags_labels = array(PL_MAILLIST_EDIT_SU_FORUM_TAB, PL_MAILLIST_EDIT_SU_NICK, PL_MAILLIST_EDIT_SU_SIGNATURE);
    }

	$tags = array_merge(array('-', '{SU_LOGIN}', '{SU_EMAIL}',  '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_LAST_IP}', '{SU_ID}',
                                      '-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}',
                                      '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_ADDITION}'),
                                $forum_tags, $cat_tags, $news_tags);

	$values = array_merge(array(PL_MAILLIST_DESIGN_TAB1, PL_MAILLIST_EDIT_SU_LOGIN, PL_MAILLIST_EDIT_SU_EMAIL, PL_MAILLIST_EDIT_SU_GET_REG_DATE, PL_MAILLIST_EDIT_SU_GET_LAST_DATE, PL_MAILLIST_EDIT_SU_IP,
									PL_MAILLIST_EDIT_SU_ID, PL_MAILLIST_EDIT_SU_PERS_TAB, PL_MAILLIST_EDIT_SU_FIO, PL_MAILLIST_EDIT_SU_AVATAR, PL_MAILLIST_EDIT_SU_PHONE, PL_MAILLIST_EDIT_SU_MOBILE, PL_MAILLIST_EDIT_SU_BIRTH, PL_MAILLIST_EDIT_SU_SEX, PL_MAILLIST_EDIT_SU_ZIP, PL_MAILLIST_EDIT_SU_ADRESS,
									PL_MAILLIST_EDIT_SU_ADDITION, PL_MAILLIST_EDIT_SU_CORPORATE_TAB,  PL_MAILLIST_EDIT_SU_WORK_NAME, PL_MAILLIST_EDIT_SU_WORK_OTDEL, PL_MAILLIST_EDIT_SU_WORK_DOLJ,  PL_MAILLIST_EDIT_SU_WORK_PHONE, PL_MAILLIST_EDIT_SU_WORK_PHONE_INNER, PL_MAILLIST_EDIT_SU_WORK_FAX, PL_MAILLIST_EDIT_SU_WORK_EMAIL, PL_MAILLIST_EDIT_SU_WORK_OFFICE, PL_MAILLIST_EDIT_SU_WORK_ADDITION),
								$forum_tags_labels, $cat_tags_values, $news_values);

    /************************************************ ТЕГИ КОНЕЦ **********************************************************/

	if(count($_POST) > 0)
        extract($_POST);

    $layout->addField(PL_MAILLIST_MESSAGES_TO_USER, new sbLayoutHTML('<nobr><input type="text" name="m_to_names" value="'.$m_to_names.'" id="m_to_names" style="width:430px;" readonly="readonly" />&nbsp;&nbsp;<img class="button" onmouseup="sbPress(this, false)" onmousedown="sbPress(this, true);selectUsers();" align="top" src="'.SB_CMS_IMG_URL.'/users.png" width="20" height="20" /></nobr>
                                                                        <input type="hidden" name="m_to_ids" value="'.$m_to_ids.'" id="m_to_ids" />
                                                                        <input type="hidden" name="m_all_cat_ids" value="'.$m_all_cat_ids.'" id="m_all_cat_ids" /><br />'));

    $layout->addField(PL_MAILLIST_MASSAGE_SHOW_ALL_USERS, new  sbLayoutInput('checkbox', '1', 'show_all_user', 'show_all_user', 'onclick="AllUsers();" checked="checked"'));
    $layout->addField('', new sbLayoutInput('hidden', '0', 'm_message_send_all', 'm_message_send_all'));

	if($m_use_default == 1)
	{
		$options = array();
		$res = sql_query('SELECT categs.cat_title, temps.ndl_id, temps.ndl_title FROM sb_categs categs, sb_catlinks links, sb_news_temps_list temps WHERE temps.ndl_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_news_list" ORDER BY categs.cat_left, temps.ndl_title');
		if ($res)
	    {
	        $old_cat_title = '';
	        foreach ($res as $value)
	        {
	            list($cat_title, $ndl_id, $ndl_title) = $value;
	            if ($old_cat_title != $cat_title)
	            {
	                $options[uniqid()] = '-'.$cat_title;
	                $old_cat_title = $cat_title;
	            }
	            $options[$ndl_id] = $ndl_title;
	        }
	    }

		if (count($_POST) == 0)
	    {
		    if($m_news_temp_id != '')
				$m_news_temp_id = explode('|', $m_news_temp_id);
			else
				$m_news_temp_id = array();
	    }
	}

    foreach ($GLOBALS['sb_site_langs'] as $key => $value)
    {
		$layout->addField('', new sbLayoutDelim());
		$html = '<div align="center" class="hint_div" style="margin-top:5px;">'.sprintf(PL_MAILLIST_MESSAGE_MESSAGE_LANG,$GLOBALS['sb_site_langs'][$key]).'</div>';
		$layout->addField('', new sbLayoutHTML($html, true));
		$layout->addField('', new sbLayoutDelim());

		if($m_use_default == 1)
		{
			if (count($options) > 0)
		    {
				$fld = new sbLayoutSelect($options, 'm_news_temp_id['.$key.']');
		    	foreach($m_news_temp_id as $k => $val)
		    	{
			        if($k == $key)
			        {
						$fld->mSelOptions = array(strpos($val, '::') ? substr($val, 4) : $val);
			        }
		    	}
			}
			else
		    {
				$fld = new sbLayoutLabel('<div class="hint_div">'.PL_MAILLIST_EDIT_NEWS_LIST_NO_TEMPS.'</div>', '', '', false);
			}

			$layout->addField(PL_MAILLIST_EDIT_NEWS_TEMPS, $fld);
			$layout->addField(PL_MAILLIST_EDIT_FULL_NEWS_PAGE, new sbLayoutPage((isset($m_page_full_news[$key]) ? $m_page_full_news[$key] : ''), 'm_page_full_news['.$key.']', '', 'style="width:94%;"'));
		}

		$layout->addField('', new sbLayoutDelim());
		$layout->addField(PL_MAILLIST_MESSAGES_TITLE, new sbLayoutInput('text', (isset($m_title[$key]) ? $m_title[$key] : '') , 'm_title['.$key.']', 'm_title_'.$key, 'style="width: 430px;"', true));

		$field = new sbLayoutTextarea( (isset($m_message[$key]) ? $m_message[$key] : '') , 'm_message['.$key.']', 'm_message_'.$key, 'style="width: 100%; height: 150px"');
		$field->mTags = $tags;
		$field->mValues = $values;
		$field->mShowEditorBtn = ($m_conf_format == 0);
		$layout->addField(PL_MAILLIST_MESSAGES_MESSAGE, $field);
	}
	$layout->addField('',new sbLayoutDelim());

    $html = '<input type="checkbox" name="m_test" id="test_mode_id" value = "1"'.((isset($m_test) && $m_test == 1) ? 'checked' : '').'><label for="test_mode_id">'.PL_MAILLIST_TEST_MODE_DESC.'</label>';
    $layout->addField(PL_MAILLIST_MESSAGES_TEST_MODE, new sbLayoutHTML($html));
    $layout->addField('',new sbLayoutDelim());
    $layout->addField(PL_MAILLIST_MESSAGES_FILES, new sbLayoutHTML('<div id="span_files"><input type="file" name="m_files[0]" size="55" style="margin:2px;">&nbsp;<img class="button" onmouseup="sbPress(this, false)" onmousedown="sbPress(this, true);addFile();" align="top" src="'.SB_CMS_IMG_URL.'/btn_add.png" width="20" height="20" /></div>'));

    $layout->addTab(PL_MAILLIST_TAB2);
    $layout->addHeader(PL_MAILLIST_TAB2);

    $field = new sbLayoutSelect(array(PL_MAILLIST_MESSAGE_HTML , PL_MAILLIST_MESSAGE_TEXT),'m_conf_format');
    $field->mSelOptions = array($m_conf_format);

    $layout->addField(PL_MAILLIST_EDIT_MESSAGE_FORMAT,$field);

    $layout->addField('', new sbLayoutInput('hidden', $m_use_default, 'm_use_default'));

    $layout->addField(PL_MAILLIST_EDIT_EMAIL, new sbLayoutInput('text',$m_conf_email,'m_conf_email','','',true));
    $layout->addField(PL_MAILLIST_EDIT_CHARSET, new sbLayoutInput('text',$m_conf_charset,'m_conf_charset','','',true));
    $layout->addField(PL_MAILLIST_EDIT_IMAGES_ATTACH, new sbLayoutInput('checkbox','1','m_conf_images_attach','',((isset($m_conf_images_attach) &&  $m_conf_images_attach == '1') ? 'checked' : '')));

	$layout->addButton('submit', PL_MAILLIST_MESSAGES_SEND_MESSAGE, '', '', '');
	$layout->addButton('button', KERNEL_CLOSE, '', '', 'onclick="window.close()"');

	$layout->show();
}

function fMaillist_Send_Submit()
{
	if (!fCategs_Check_Elems_Rights($_GET['id'], -1, 'pl_maillist'))
		return;

    $m_time = 0;
    $skip_users_check = 0;
	$m_news_temp_id = 0;
	$m_page_full_news = array();

    $timeEnd = ini_get('max_execution_time');
    if ($timeEnd > 0)
    {
		$timeEnd = time() + $timeEnd - 5;
    }
    else
    {
        $timeEnd = time() + 100000;
    }

    $m_fields = array();
    $su_fields_temps = array();

    //	 Замена тегов
    //
    //   $str  string строка текста
    //   $id  integer Ид пользователя
    //   $m_fields  array() Шаблоны стандартных полей пользователя
    //   $su_fields_temps array() Шаблоны пользовательских полей пользователя
    function replace_tags($str, $id, $m_fields, $su_fields_temps, $lang)
    {
        if(is_array($m_fields) == false)
        {
            $m_fields = array();
        }

        if(is_array($su_fields_temps) == false)
        {
            $su_fields_temps = array();
        }

		$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident = ?', 'pl_site_users');

		$elems_fields = array();
		$elems_fields_select_sql = '';
	    $tags = array();
		$values = array();

		// формируем SQL-запрос для пользовательских полей
		if ($res)
	    {
	        if($res[0][0] != '')
	        {
	            $elems_fields = unserialize($res[0][0]);
	        }

	        if ($elems_fields)
	        {
				foreach ($elems_fields as $value)
		        {
		            if (isset($value['sql']) && $value['sql'] == 1)
		            {
		                $elems_fields_select_sql .= ', user_f_'.$value['id'];

		                if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
		                {
		                	$tags[] = '{'.$value['tag'].'_LATITUDE}';
		                	$tags[] = '{'.$value['tag'].'_LONGTITUDE}';

			                if ($value['type'] == 'yandex_coords')
		                	{
		                		$tags[] = '{'.$value['tag'].'_API_KEY}';
		                	}
		                }
		                else
		                {
							$tags[] = '{'.$value['tag'].'}';
						}

	                	if($id == -1)
	                	{
							$values[] = $value['title'];
	                	}
					}
				}
			}
		}

		$tags = array_merge($tags, array('{SU_ID}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_PERS_BIRTH}', '{SU_STATUS}', '{SU_PERS_SEX}',
			'{SU_LAST_IP}', '{SU_NAME}', '{SU_EMAIL}', '{SU_LOGIN}', '{SU_PERS_FOTO}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}',
			'{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}', '{SU_WORK_NAME}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}',
			'{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_FORUM_NICK}',
			'{SU_FORUM_TEXT}', '{SU_MAIL_LANG}', '{SU_MAIL_STATUS}'));

//		Если рассылка используется в тестовом режиме ($id = -1), то подставляем названия полей вместо их значения.
		if($id == -1)
		{
			$su_id = PL_SITE_USERS_EDIT_ID;
			$su_last_ip = PL_SITE_USERS_EDIT_IP;
			$su_activation_code = PL_SITE_USERS_EDIT_ACTIVATION_CODE_LABEL;
			$su_name = PL_SITE_USERS_EDIT_FIO;
			$su_pass = PL_SITE_USERS_DESIGN_REG_EDIT_PASS;
			$su_email = PL_SITE_USERS_EDIT_EMAIL;
			$su_login = PL_SITE_USERS_EDIT_LOGIN2;
			$su_reg_date = PL_SITE_USERS_GET_REG_DATE;
			$su_status = PL_SITE_USERS_EDIT_STATUS;
			$su_pers_foto = PL_SITE_USERS_EDIT_AVATAR;
			$su_pers_phone = PL_SITE_USERS_EDIT_PHONE;
			$su_pers_mob_phone = PL_SITE_USERS_EDIT_MOBILE;
			$su_pers_birth = PL_SITE_USERS_EDIT_BIRTH;
			$su_pers_sex = PL_SITE_USERS_EDIT_SEX;
			$su_pers_zip = PL_SITE_USERS_EDIT_ZIP;
			$su_pers_adress = PL_SITE_USERS_EDIT_ADRESS;
			$su_pers_addition = PL_SITE_USERS_EDIT_ADDITION;
			$su_work_name = PL_SITE_USERS_EDIT_WORK_NAME;
			$su_work_phone = PL_SITE_USERS_EDIT_WORK_PHONE;
			$su_work_phone_inner = PL_SITE_USERS_EDIT_WORK_PHONE_INNER;
			$su_work_fax = PL_SITE_USERS_EDIT_WORK_FAX;
			$su_work_email = PL_SITE_USERS_EDIT_WORK_EMAIL;
			$su_work_addition = PL_SITE_USERS_EDIT_WORK_ADDITION;
			$su_work_office_number = PL_SITE_USERS_EDIT_WORK_OFFICE;
			$su_work_unit = PL_SITE_USERS_EDIT_OTDEL;
			$su_work_position = PL_SITE_USERS_EDIT_DOLJ;
			$su_forum_nick = PL_SITE_USERS_EDIT_NICK;
			$su_forum_text = PL_SITE_USERS_EDIT_SIGNATURE;
			$su_mail_lang = PL_SITE_USERS_EDIT_LANG;
			$su_mail_status = PL_SITE_USERS_EDIT_MAIL_STATUS;
			$su_mail_date = PL_SITE_USERS_EDIT_MAIL_STATUS_ACTIVE_DATE;
			$su_mail_subscription = PL_SITE_USERS_MAILLIST_TITLE_FILED;
			$su_last_date = PL_SITE_USERS_GET_LAST_DATE;
		}
		else
		{
			$res = sql_param_query('SELECT su_id, su_last_ip, su_activation_code, su_name, su_pass,su_email, su_login, su_reg_date,
						su_status, su_pers_foto, su_pers_phone, su_pers_mob_phone, su_pers_birth, su_pers_sex, su_pers_zip, su_pers_adress, su_pers_addition, su_work_name, su_work_phone, su_work_phone_inner, su_work_fax, su_work_email, su_work_addition, su_work_office_number, su_work_unit, su_work_position, su_forum_nick, su_forum_text, su_mail_lang, su_mail_status, su_mail_date, su_mail_subscription, su_last_date
						'.$elems_fields_select_sql.'
						FROM sb_site_users WHERE su_id = ?d',$id);

			list($su_id, $su_last_ip, $su_activation_code, $su_name, $su_pass, $su_email, $su_login,
	             $su_reg_date,$su_status,$su_pers_foto,$su_pers_phone,$su_pers_mob_phone,
	             $su_pers_birth,$su_pers_sex,$su_pers_zip,$su_pers_adress,$su_pers_addition,$su_work_name,
	             $su_work_phone,$su_work_phone_inner,$su_work_fax,$su_work_email,$su_work_addition,
	             $su_work_office_number,$su_work_unit,$su_work_position,$su_forum_nick,
	             $su_forum_text,$su_mail_lang,$su_mail_status,$su_mail_date,$su_mail_subscription, $su_last_date) = $res[0];
		}

		$dop_tags = array('{SU_ID}', '{VALUE}');
		$dop_values = array($su_id);

		$num_fields = count($res[0]);

		if ($num_fields > 33 && $id != -1)
		{
			for ($i = 33; $i < $num_fields; $i++)
            {
				$vals[] = $res[0][$i];
            }

			include_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
			$values = sbLayout::parsePluginFields($elems_fields, $vals, $su_fields_temps, $dop_tags, $dop_values, $lang);
        }

    	$values[] = $su_id;
    	if($id == -1)
        {
			$values[] = $su_reg_date;
        }
		elseif(isset($m_fields['su_reg_date']) && $m_fields['su_reg_date'] != '') // Дата регистрации
        {
			$values[] = str_replace('{SU_ID}', $su_id, sb_parse_date($su_reg_date, $m_fields['su_reg_date'], $lang));
        }
        else
        {
			$values[] = '';
        }

    	if($id == -1)
       	{
			$values[] = $su_last_date;
		}
        elseif(isset($m_fields['su_last_date']) && $m_fields['su_last_date'] != '') // Дата последней активности
        {
			$values[] = str_replace('{SU_ID}', $su_id, sb_parse_date($su_last_date, $m_fields['su_last_date'], $lang));
        }
        else
        {
			$values[] = '';
        }

    	if($id == -1)
       	{
			$values[] = $su_pers_birth;
        }
        elseif(isset($m_fields['su_pers_birth']) && $su_pers_birth != 0) // Дата рождения
        {
			$values[] = str_replace('{SU_ID}', $su_id, sb_parse_date($su_pers_birth, $m_fields['su_pers_birth'], $lang));
        }
        else
        {
			$values[] = '';
        }

    	if($id == -1)
        {
        	$values[] = $su_status;
        }
        else
        {
	        switch ($su_status) // Статус активен, не активен, заблокирован и. т. д.
	        {
	            case 0:
	            {
					$values[] = PL_MAILLIST_EDIT_SU_GET_STATUS_REG;
					break;
	            }
	            case 1:
	            {
					$values[] = PL_MAILLIST_EDIT_SU_GET_STATUS_MOD;
					break;
	            }
	            case 2:
	            {
	            	$values[] = PL_MAILLIST_EDIT_SU_GET_STATUS_EMAIL;
	                break;
	            }
	            case 3:
	            {
	            	$values[] = PL_MAILLIST_EDIT_SU_GET_STATUS_MOD_EMAIL;
					break;
	            }
	            case 4:
	            {
					$values[] = PL_MAILLIST_EDIT_SU_GET_STATUS_BLOCK;
	                break;
	            }
	            default:
		        	$values[] = '';
	        }
        }

    	if($id == -1)
        {
			$values[] = $su_pers_sex;
        }
        else
        {
			$values[] = isset($m_fields['su_pers_sex']) ? str_replace($dop_tags, array_merge($dop_values, array(isset($GLOBALS['sb_sex_arr'][SB_CMS_LANG][$su_pers_sex]) ? $GLOBALS['sb_sex_arr'][SB_CMS_LANG][$su_pers_sex] : '')), $m_fields['su_pers_sex']) : '';
        }

		$values[] = $su_last_ip;
        $values[] = isset($m_fields['su_name']) ? str_replace($dop_tags, array_merge($dop_values, array($su_name)), $m_fields['su_name']) : '';
        $values[] = isset($m_fields['su_email']) ? str_replace($dop_tags, array_merge($dop_values, array($su_email)), $m_fields['su_email']) : '';
        $values[] = isset($m_fields['su_login']) ? str_replace($dop_tags, array_merge($dop_values, array($su_login)), $m_fields['su_login']) : '';

    	if($id == -1)
        {
			$values[] = $su_pers_foto;
        }
		else if(isset($m_fields['su_pers_foto']))
		{
			$values[] = str_replace(array_merge($dop_tags, array('{IMG_LINK}')), array_merge($dop_values, array('', $su_pers_foto)), $m_fields['su_pers_foto']);
        }

        $values[] = isset($m_fields['su_pers_phone']) ? str_replace($dop_tags, array_merge($dop_values, array($su_pers_phone)), $m_fields['su_pers_phone']) : '';
        $values[] = isset($m_fields['su_pers_mob_phone']) ? str_replace($dop_tags, array_merge($dop_values, array($su_pers_mob_phone)), $m_fields['su_pers_mob_phone']) :'';
        $values[] = isset($m_fields['su_pers_zip']) ? str_replace($dop_tags, array_merge($dop_values, array($su_pers_zip)), $m_fields['su_pers_zip']) :'';
        $values[] = isset($m_fields['su_pers_adress']) ? str_replace($dop_tags, array_merge($dop_values, array($su_pers_adress)), $m_fields['su_pers_adress']) : '';
        $values[] = isset($m_fields['su_pers_addition']) ? str_replace($dop_tags, array_merge($dop_values, array($su_pers_addition)), $m_fields['su_pers_addition']) : '';
        $values[] = isset($m_fields['su_work_name']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_name)), $m_fields['su_work_name']) : '';
        $values[] = isset($m_fields['su_work_phone']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_phone)), $m_fields['su_work_phone']) : '';
        $values[] = isset($m_fields['su_work_phone_inner']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_phone_inner)), $m_fields['su_work_phone_inner']) : '';
		$values[] = isset($m_fields['su_work_fax']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_fax)), $m_fields['su_work_fax']) : '';
		$values[] = isset($m_fields['su_work_email']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_email)), $m_fields['su_work_email']) : '';
		$values[] = isset($m_fields['su_work_addition']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_addition)), $m_fields['su_work_addition']) : '';
		$values[] = isset($m_fields['su_work_office_number']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_office_number)), $m_fields['su_work_office_number']) :'';
		$values[] = isset($m_fields['su_work_unit']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_unit)), $m_fields['su_work_unit']) : '';
		$values[] = isset($m_fields['su_work_position']) ? str_replace($dop_tags, array_merge($dop_values, array($su_work_position)), $m_fields['su_work_position']) : '';

		// Пол пользователя
        if(isset($m_fields['su_forum_nick']) && $m_fields['su_forum_nick'] != '')
        {
			$values[] = str_replace($dop_tags, array_merge($dop_values, array($su_forum_nick)), $m_fields['su_forum_nick']);
        }
        else
        {
        	$values[] = '';
        }

        if(isset($m_fields['su_forum_text']) && $m_fields['su_forum_text'] != '')
        {
			$values[] = str_replace($dop_tags, array_merge($dop_values, array($su_forum_text)), $m_fields['su_forum_text']);
        }
        else
        {
        	$values[] = '';
        }

    	if($id == -1)
        {
			$values[] = $su_mail_lang;
        }
		elseif(isset($m_fields['su_mail_lang'])&& $m_fields['su_mail_lang'] != '')
        {
        	$values[] = str_replace($dop_tags, array_merge($dop_values, array((isset($GLOBALS['sb_site_langs'][$su_mail_lang]) ? $GLOBALS['sb_site_langs'][$su_mail_lang] : ''))), $m_fields['su_mail_lang']);  //  SU_MAIL_LANG
        }
        else
        {
			$values[] = '';
        }

    	if($id == -1)
        {
			$values[] = $su_mail_status;
        }
		elseif(isset($m_fields['su_mail_status']) && $m_fields['su_mail_status'] != '')   //   Статус рассылки
        {
			if($su_mail_status == 0)
				$values[] = str_replace($dop_tags, array_merge($dop_values, array(PL_MAILLIST_EDIT_SU_MAIL_STATUS_ACTIVE)), $m_fields['su_mail_lang']);
            if($su_mail_status == 1)
				$values[] = str_replace($dop_tags, array_merge($dop_values, array(PL_MAILLIST_EDIT_SU_MAIL_STATUS_NO_ACTIVE)), $m_fields['su_mail_lang']);
            if($su_mail_status == 2)
            	$values[] = str_replace($dop_tags, array_merge($dop_values, array(PL_MAILLIST_EDIT_SU_MAIL_STATUS_NO_ACTIVE_BY_DATE.' '.sb_date('d.m.Y', $su_mail_date))), $m_fields['su_mail_lang']);
        }
		else
        {
			$values[] = '';
		}

		$str = str_replace($tags, $values, $str);
			return $str;
	}

    require_once(SB_CMS_LIB_PATH.'/sbMail.inc.php');
    $mail = new sbMail();
    if(isset($_GET['uid']) && $_GET['uid'] != '')
    {
        $uid = $_GET['uid'];
    }
    else
    {
        $uid = '';
    }

    $send_count_ok = 0;
    $send_count_error = 0;

    if(isset($_SESSION[$uid]['PL_MAILLIST_SEND_RELOAD']) &&  $_SESSION[$uid]['PL_MAILLIST_SEND_RELOAD'] == 'RELOAD_MAILLIST')
    {
    	$m_use_default = $_SESSION[$uid]['PL_MAILLIST_USE_DEFAULT'];
		$m_news_temp_id = $_SESSION[$uid]['PL_MAILLIST_NEWS_TEMP_ID'];
		$m_page_full_news = $_SESSION[$uid]['PL_MAILLIST_PAGE_FULL_NEWS'];
		$maillist_id = $_SESSION[$uid]['PL_MAILLIST_MAILLIST_ID'];
        $m_test = $_SESSION[$uid]['PL_MAILLIST_SEND_TEST'];
        $m_conf_images_attach = $_SESSION[$uid]['PL_MAILLIST_SEND_IMAGES_ATTACH'];
        $m_conf_email = $_SESSION[$uid]['PL_MAILLIST_SEND_EMAIL'];
        $m_conf_charset = $_SESSION[$uid]['PL_MAILLIST_SEND_CHARSET'];
        $m_conf_signed = $_SESSION[$uid]['PL_MAILLIST_SEND_SIGNED'];
        $first_key = $_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY'];
        $first_key_signed = $_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY_SIGNED'];
        $m_fields = $_SESSION[$uid]['PL_MAILLIST_SEND_FIELDS'];
        $su_fields_temps = $_SESSION[$uid]['PL_MAILLIST_SEND_SU_FIELDS'];
        $m_to_ids = $_SESSION[$uid]['PL_MAILLIST_SEND_TO_IDS'];
        $m_to_names = $_SESSION[$uid]['PL_MAILLIST_SEND_TO_NAMES'];
        $m_message = $_SESSION[$uid]['PL_MAILLIST_SEND_MESSAGE'];
        $to_send = $_SESSION[$uid]['PL_MAILLIST_SEND_USERS'];
        $m_conf_format = $_SESSION[$uid]['PL_MAILLIST_SEND_FORMAT'];
        $send_count = $_SESSION[$uid]['PL_MAILLIST_SEND_COUNT'];
        $m_title = $_SESSION[$uid]['PL_MAILLIST_SEND_TITLE'];
        $send_count_ok = $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_OK'];
        $send_count_error = $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_ERROR'];
        $skip_users_check = $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SKIP_USERS'];
        $signed_tags = $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_TAGS'];
        $signed_values = $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_VALUES'];

        if(isset($_SESSION[$uid]['PL_MAILLIST_SEND_FILES']))
        {
            $files = $_SESSION[$uid]['PL_MAILLIST_SEND_FILES'];
        }

		unset($_SESSION[$uid]['PL_MAILLIST_USE_DEFAULT']);
		unset($_SESSION[$uid]['PL_MAILLIST_NEWS_TEMP_ID']);
		unset($_SESSION[$uid]['PL_MAILLIST_PAGE_FULL_NEWS']);
		unset($_SESSION[$uid]['PL_MAILLIST_MAILLIST_ID']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_MAIL']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_RELOAD']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_TEST']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_IMAGES_ATTACH']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_EMAIL']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_CHARSET']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_SIGNED']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY_SIGNED']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_FIELDS']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_SU_FIELDS']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_TO_IDS']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_TO_NAMES']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_MESSAGE']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_USERS']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_FORMAT']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_TITLE']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_OK']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_ERROR']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SKIP_USERS']);
		unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_TAGS']);
        unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_VALUES']);

		$mail->setFrom(sb_str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, $m_conf_email));   //   E-Mail Отправителя
    	$mail->setReturnPath(sb_str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, $m_conf_email));
		$mail->setHeadCharset($m_conf_charset); // Кодировка письма

        // Загрузка файлов
		$GLOBALS['sbVfs']->mLocal = true;
		if(isset($files))
        {
			for($i = 0; $i != count($files); $i++)
            {
				$mail->addAttachment($GLOBALS['sbVfs']->file_get_contents($files[$i]['FILE_PATH']), $files[$i]['FILE_NAME']);
            }
        }
		$GLOBALS['sbVfs']->mLocal = false;
    }
	else
    {
		$uid = uniqid(time());
        $m_test = 0;
        $m_conf_images_attach = 0;
		extract($_POST);

        if(isset($_GET['id']) && $_GET['id'] != '')
        {
            $id = intval($_GET['id']);
        }
		else
        {
            sb_show_message(PL_MAILLIST_MESSAGE_MAILLIST_ERROR, false, 'warning');
            fMaillist_Send();
            return;
        }

        if(count($m_message) == 0)
        {
            sb_show_message(PL_MAILLIST_MESSAGES_NO_MSG, false, 'warning');
            fMaillist_Send();
            return;
        }

        if(count(array_intersect_key($m_message , $m_title)) != count($m_message))
        {
            sb_show_message(PL_MAILLIST_MESSAGES_NO_TITLE_OR_MSG, false, 'warning');
            fMaillist_Send();
            return;
        }

        $counter = 0;
        $first_key = '';

		foreach ($m_message as $key => $value)
        {
            if($m_message[$key] != ''  && $m_title[$key] == '')
            {
                sb_show_message(PL_MAILLIST_MESSAGES_MSG_NO_TITLE, false, 'warning');
                fMaillist_Send();
                return;
            }

            if($m_message[$key] == ''  && $m_title[$key] != '')
            {
                sb_show_message(PL_MAILLIST_MESSAGES_TITLE_NO_MSG, false, 'warning');
                fMaillist_Send();
                return;
            }

            if($m_message[$key] == '')
            {
                $counter++ ;
            }
            else
            {
                if($first_key == '')
                {
					$first_key = $key;
                }
				$counter--;
            }
        }

        if($counter == count($m_message))
        {
            sb_show_message(PL_MAILLIST_MESSAGES_NO_MSG, false, 'warning');
            fMaillist_Send();
            return;
        }

        if($m_conf_email == '')
        {
            sb_show_message(PL_MAILLIST_EDIT_ERROR_NO_EMAIL, false, 'warning');
            fMaillist_Send();
            return;
        }

        if($m_conf_charset == '')
        {
            sb_show_message(PL_MAILLIST_EDIT_ERROR_NO_CHARSET, false, 'warning');
            fMaillist_Send();
            return;
        }

        if($m_to_names == '' || $m_to_ids == '')
        {
            if($m_test == 1)
            {
                $skip_users_check = 1;
            }
            else
            {
                sb_show_message(PL_MAILLIST_MESSAGES_NO_USERS_MSG, false, 'warning');
                fMaillist_Send();
                return;
            }
        }

        // Загрузка файлов
        $files = array();
        if(isset($_FILES['m_files']))
        {
            $_SESSION[$uid]['PL_MAILLIST_SEND_FILES'] = array();
            require_once(SB_CMS_LIB_PATH.'/sbUploader.inc.php');

            $accept_types = array();
            if (!$_SESSION['sbAuth']->isAdmin())
            {
                $accept_types = $_SESSION['sbAuth']->getUploadingExts();
                if (count($accept_types) == 0)
                {
                    sb_show_message(PL_MESSAGES_UPLOAD_FILES_NO_ACCEPT_TYPES_ERROR, false, 'warning');
                    fMessages_Send();
                    return;
                }
            }

            $uploader = new sbUploader();
            $uploader->setMaxFileSize(sbPlugins::getSetting('sb_files_max_upload_size'));
            $uploader->setMaxImageSize(sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'));

            $files = array();
            for($i = 0; $i < count($_FILES['m_files']['name']); $i++)
            {
                if (@is_uploaded_file($_FILES['m_files']['tmp_name'][$i]))
                {
                    $files['m_files'.$i] = array();
                    $files['m_files'.$i]['name'] = $_FILES['m_files']['name'][$i];
                    $files['m_files'.$i]['tmp_name'] = $_FILES['m_files']['tmp_name'][$i];
                    $files['m_files'.$i]['error'] = $_FILES['m_files']['error'][$i];
                    $files['m_files'.$i]['size'] = $_FILES['m_files']['size'][$i];
                    $files['m_files'.$i]['type'] = $_FILES['m_files']['type'][$i];
                }
            }
			$_FILES = $files;

            $GLOBALS['sbVfs']->mLocal = true;
            $files = array();

            $i = 0;
            foreach ($_FILES as $key => $value)
            {
                if($uploader->upload($key, $accept_types) == false)
                {
                    sb_show_message($uploader->getError(), false, 'warning');
                    fMaillist_Send();
                    return;
                }

                $fileName = $uid.$_FILES[$key]['name'];
                $tmp = $uploader->move(SB_CMS_USER_UPLOAD_PATH.'/maillist', $fileName);
                if($tmp == false)
                {
                    sb_show_message($uploader->getError(), false, 'warning');
                    for($i = 0; $i != count($_SESSION[$uid]['PL_MAILLIST_SEND_FILES']); $i++)
                    {
                        if ($GLOBALS['sbVfs']->is_file($_SESSION[$uid]['PL_MAILLIST_SEND_FILES'][$i]) && $_SESSION[$uid]['PL_MAILLIST_SEND_FILES'][$i] != '')
                            $GLOBALS['sbVfs']->delete($_SESSION[$uid]['PL_MAILLIST_SEND_FILES'][$i]);
                    }
                    fMaillist_Send();
                    return;
                }

                $files[$i]['FILE_PATH'] = SB_CMS_USER_UPLOAD_PATH.'/maillist/'.$tmp;
                $files[$i]['FILE_NAME'] = $_FILES[$key]['name'];
                $i++;
                $_SESSION[$uid]['PL_MAILLIST_SEND_FILES'] = $files;
            }
			$GLOBALS['sbVfs']->mLocal = false;
		}

		$mail->setFrom(sb_str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, $m_conf_email));	// E-Mail Отправителя
		$mail->setReturnPath(sb_str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, $m_conf_email));
        $mail->setHeadCharset($m_conf_charset); // Кодировка письма
        $maillist_id = $id;

        if($skip_users_check == 0)
        {
            if(strpos($m_to_ids, '^') !== false)
            {
                $m_to_ids = str_replace('^', ',', $m_to_ids);
            }

            if($m_to_ids[strlen($m_to_ids)-1] == ',')  // Переменная содержит список индексов каталогов с пользователями,
            {                                          // если последний символ запятая то удаляем его
                $m_to_ids = substr($m_to_ids, 0, strlen($m_to_ids)-1);
            }

            if($m_to_ids[0] == ',')  // Переменная содержит список индексов каталогов с пользователями,
            {                                          // если первый символ запятая то удаляем его
                $m_to_ids = substr($m_to_ids, 1);
            }

			//	Вытаскиваем пользователя из каталога
			$res = sql_query('SELECT users.su_id, users.su_login, users.su_email, users.su_mail_status, users.su_mail_date, users.su_mail_lang, users.su_mail_subscription
								FROM sb_site_users users, sb_catlinks link, sb_categs cat
								WHERE users.su_id = link.link_el_id
								AND link.link_cat_id = cat.cat_id
								AND cat.cat_ident = ?
								AND cat.cat_rubrik = 1
								AND cat.cat_id IN ('.$m_to_ids.')
								AND users.su_status = 0
								AND (users.su_active_date >= ? OR users.su_active_date = 0)
								GROUP BY users.su_id', 'pl_site_users', time());

			$to_send = array(); // Массив будет содержать e-mail адресс и язык пользователей, для кого нужно отправить письмо
			$j = 0; // Для создания массива

			if($res)
			{
				foreach ($res as $key => $value)
                {
					list($su_id, $su_login, $su_email, $su_mail_status, $su_mail_date, $su_mail_lang, $su_mail_subscription) = $res[$key];

                    // Список рассылок на которые подписан пользователь
                    $su_mail_subscription = explode(',', $su_mail_subscription);

                    // Если статус активен и имееться e-mail адресс
                    if($m_test == 1 || ($su_mail_status == 0 && $su_email != '' && is_array($su_mail_subscription)) || ($su_mail_status == 2 && $su_mail_date <= time()) )
                    {
                    	if(isset($show_all_user) && $show_all_user == 1)
                        {
							$tmp_su_mail = array();
							foreach($su_mail_subscription as $key => $value)
							{
								$tmp_su_mail[$value] = $value;
							}
							$su_mail_subscription = $tmp_su_mail;

	                    	// Проверяем подписан ли пользователь на текущую рассылку по ключу: (id_из_catlinks)
	                        if(array_key_exists($maillist_id, $su_mail_subscription) && $su_mail_subscription[$maillist_id]  == $maillist_id)
	                        {
								// Записываем e-mail и язык
	                            $to_send[$j]['email'] = $su_email;
	                            $to_send[$j]['lang'] = $su_mail_lang;
	                            $to_send[$j]['id'] = $su_id;

	                            if($m_test == 1 && strtoupper(trim($su_email)) == strtoupper( trim($_SESSION['sbAuth']->getUserEmail())))
	                            {
	                            	$to_send = array();
	                                $to_send[0]['email'] = $_SESSION['sbAuth']->getUserEmail();
	                                $to_send[0]['lang'] = $su_mail_lang;
	                                $to_send[0]['id'] = $su_id;
	                                break;
								}
	                            $j++;
							}
						}
                        elseif(!isset($show_all_user) || $show_all_user == 0)
                        {
                        	// Записываем e-mail и язык
                            $to_send[$j]['email'] = $su_email;
                            $to_send[$j]['lang'] = $su_mail_lang;
                            $to_send[$j]['id'] = $su_id;

                            if($m_test == 1 && strtoupper(trim($su_email)) == strtoupper(trim($_SESSION['sbAuth']->getUserEmail())))
                            {
                            	$to_send = array();
                                $to_send[0]['email'] = $_SESSION['sbAuth']->getUserEmail();
                                $to_send[0]['lang'] = $su_mail_lang;
                                $to_send[0]['id'] = $su_id;
                                break;
							}
                            $j++;
						}
					}
				}
			}
			else
            {
            	sb_show_message(PL_MAILLIST_MESSAGES_NO_USERS_ERROR, false, 'warning');
                fMaillist_Send();
                return;
			}

			if($m_test == 1 && count($to_send) > 0 && $to_send[0]['email'] != $_SESSION['sbAuth']->getUserEmail())
            {
				$su_mail_lang = $to_send[0]['lang'];
				$su_id = $to_send[0]['id'];

                $to_send = array();
                $to_send[0]['email'] = $_SESSION['sbAuth']->getUserEmail();
                $to_send[0]['lang'] = $su_mail_lang;
                $to_send[0]['id'] = $su_id;
                $j = 0;
			}
        }
        else
        {
            $to_send = array();
            $to_send[0]['email'] = $_SESSION['sbAuth']->getUserEmail();
            $to_send[0]['lang'] = 'ru';
            $to_send[0]['id'] = -1;
        }

//   	вытаскиваем пользовательские поля
		$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_maillist"');

		$elems_fields = array();
		$elems_fields_select_sql = '';

		$signed_tags = array('{LINK_UNSUB}');

	    // формируем SQL-запрос для пользовательских полей
	    if ($res)
	    {
	    	if($res[0][0] != '')
	    	{
	            $elems_fields = unserialize($res[0][0]);
	    	}
	    	else
	    	{
	    		$elems_fields = array();
	    	}

	        if ($elems_fields)
	        {
	            foreach ($elems_fields as $value)
	            {
	            	if (isset($value['sql']) && $value['sql'] == 1)
	                {
	                    $elems_fields_select_sql .= ', user_f_'.$value['id'];
	                    $signed_tags[] = '{'.$value['tag'].'}';
	                }
	            }
	        }
	    }

		//	Вытаскиваем массив подписей к письму
		$res = sql_param_query('SELECT m_conf_signed, m_site_users_fields, m_site_users_custom_fields, m_unsub_page'.$elems_fields_select_sql.' FROM sb_maillist WHERE m_id = ?d', $id);
        if(!$res)
        {
            sb_show_message(PL_MAILLIST_MESSAGE_MAILLIST_ERROR, false, 'warning');
            fMaillist_Send();
            return;
        }

		list($m_conf_signed, $m_fields, $su_fields_temps, $m_unsub_page) = $res[0];

        $m_conf_signed = unserialize($m_conf_signed);
        $m_fields = unserialize($m_fields);
        $su_fields_temps = unserialize($su_fields_temps);

		$first_key_signed = 'ru';
		$num = count($res[0]);

		$m_unsub_page = $m_unsub_page.'?ml_unsub_id='.$id;
		$signed_values = array($m_unsub_page);

		if ($num > 4)
        {
            for ($i = 4; $i < $num; $i++)
            {
                $signed_values[] = $res[0][$i];
            }

			include_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

			$values = sbLayout::parsePluginFields($elems_fields, $signed_values, $su_fields_temps, array(), array(), $first_key_signed);
			$signed_values  = array_merge($signed_values, $values);
        }

		foreach ($m_conf_signed as $key => $value)
        {
            if($m_conf_signed[$key] != '')
            {
                $first_key_signed = $key;
                break;
            }
		}

		$send_count = 0;

		$_SESSION[$uid]['PL_MAILLIST_USE_DEFAULT'] = $m_use_default;
		$_SESSION[$uid]['PL_MAILLIST_NEWS_TEMP_ID'] = $m_news_temp_id;
		$_SESSION[$uid]['PL_MAILLIST_PAGE_FULL_NEWS'] = $m_page_full_news;
        $_SESSION[$uid]['PL_MAILLIST_SEND_RELOAD'] = 'RELOAD_MAILLIST';
		$_SESSION[$uid]['PL_MAILLIST_MAILLIST_ID'] = $maillist_id;
        $_SESSION[$uid]['PL_MAILLIST_SEND_TEST'] = $m_test;
        $_SESSION[$uid]['PL_MAILLIST_SEND_IMAGES_ATTACH'] = $m_conf_images_attach;
        $_SESSION[$uid]['PL_MAILLIST_SEND_EMAIL'] = $m_conf_email;
        $_SESSION[$uid]['PL_MAILLIST_SEND_CHARSET'] = $m_conf_charset;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SIGNED'] = $m_conf_signed;
        $_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY'] = $first_key;
        $_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY_SIGNED'] = $first_key_signed;
        $_SESSION[$uid]['PL_MAILLIST_SEND_FIELDS'] = $m_fields;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SU_FIELDS'] = $su_fields_temps;
        $_SESSION[$uid]['PL_MAILLIST_SEND_TO_IDS'] = $m_to_ids;
        $_SESSION[$uid]['PL_MAILLIST_SEND_TO_NAMES'] = $m_to_names;
        $_SESSION[$uid]['PL_MAILLIST_SEND_MESSAGE'] = $m_message;
        $_SESSION[$uid]['PL_MAILLIST_SEND_USERS'] = $to_send;
        $_SESSION[$uid]['PL_MAILLIST_SEND_FORMAT'] = $m_conf_format;
        $_SESSION[$uid]['PL_MAILLIST_SEND_COUNT'] = $send_count;
        $_SESSION[$uid]['PL_MAILLIST_SEND_TITLE'] = $m_title;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_OK'] = 0;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_ERROR'] = 0;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SKIP_USERS'] = $skip_users_check;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_TAGS'] = $signed_tags;
        $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_VALUES'] = $signed_values;

		require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
        echo '<br />';

        $layout = new sbLayout();
        $layout->mShowTitle = false;

        $html = "<table width='100%'>
        			<tr>
        				<td>
        					<div style='border: 1px solid black; margin-left: 50px; margin-right: 50px;'>
								<div style='height:100%;' id='procent_bar'>
        							<nobr>
       									<span  id='pocent' style='color: white;'>
       										<b>0 %</b>
       									</span>
        							</nobr>
        						</div>
        					</div>
        				</td>
        			</tr>
				</table><br />";

        $layout->addField('', new sbLayoutHTML($html));
        $layout->addField('', new sbLayoutDelim());
        $html = '<table align = "center">
					<tr><td>'.PL_MAILLIST_SUBSCRIBE_COUNT.':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<td id = "users_count"><b>0</b>
					<tr><td>'.PL_MAILLIST_EDIT_MAIL_SEND_COMPLETE_OK.':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<td id = "sended_messages_ok"><b>0</b>
					<tr><td>'.PL_MAILLIST_EDIT_MAIL_SEND_COMPLETE_ERROR.':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<td id = "sended_messages_error"><b>0</b>
					<tr><td>'.PL_MAILLIST_EDIT_MAIL_SEND_NEED_COUNT.':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<td id = "time_need"><b>0</b>
				</table>';

        $layout->addField('', new sbLayoutHTML($html));
        $layout->addField('', new sbLayoutDelim());
        $layout->addButton('button', KERNEL_CLOSE, '', '', 'onclick = "window.close()"');
        $layout->show();

		echo '<script>
                function afterSendMail(res)
                {
                    var usersCount = sbGetE("users_count");
                    var sendedMessagesOk = sbGetE("sended_messages_ok");
                    var sendedMessagesError = sbGetE("sended_messages_error");
                    var timeNeed = sbGetE("time_need");

                    var arr = res.split(",");

                    usersCount.innerHTML = "<b>"+(typeof(arr[1]) == "undefined" ? 0 : arr[1])+"</b>";
                    sendedMessagesOk.innerHTML = "<b>"+(typeof(arr[2]) == "undefined" ? 0 : arr[2])+"</b>";
                    sendedMessagesError.innerHTML = "<b>"+(typeof(arr[3]) == "undefined" ? 0 : arr[3])+"</b>";
                    timeNeed.innerHTML = "<b>"+(typeof(arr[4]) == "undefined" ? 0 : arr[4])+"</b>";

                    if(typeof(arr[1]) == "undefined" || arr[1] == 0)
						arr[1] = 1;

                    var proc = Math.round(arr[2]*100/arr[1]);
                    if(isNaN(proc))
                    {
						proc = 0;
    				}

    				var procent = sbGetE("pocent");
                    procent.innerHTML = "<b>"+ proc +"%</b>";

                    var bar_el = sbGetE("procent_bar");
					bar_el.width = "100%";

					if (parseInt(arr[3]) == parseInt(arr[1]) || parseInt(arr[2]) == 0)
					{
						bar_el.style.backgroundColor = "red";
    				}
					else if(parseInt(arr[3]) == 0 && parseInt(arr[1]) == parseInt(arr[2]))
					{
						bar_el.style.backgroundColor = "green";
    				}
                    else if(parseInt(arr[3]) > 0 || parseInt(arr[2]) == 0)
                    {
						bar_el.style.backgroundColor = "#DFBE02";
					}

					if(arr[0] == "OK")
                    {
	                    procent.innerHTML = "<b>100%</b>";
                        alert("'.PL_MAILLIST_MESSAGE_OK.'");
                    }
                    else if(arr[0] == "RELOAD")
                    {
                        res = sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_maillist_send_submit&id='.$_GET['id'].'&uid='.$uid.'", afterSendMail);
                    }
					return;
                }

                window.resizeTo(450,340);
                sbGetE("pocent").innerText = "0 %";
				sbGetE("procent_bar").style.width = "0 %";

				var res = sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_maillist_send_submit&id='.$_GET['id'].'&uid='.$uid.'", afterSendMail);
			</script>';
		return;
	}

	if($m_test == 1)
	{
		include_once(SB_CMS_LANG_PATH.'/pl_site_users.lng.php');
	}

	$news_ids_all = $news_html_lang = array();
	$news_html = '';

	if($m_use_default == 1)
	{
		include_once(SB_CMS_PL_PATH.'/pl_news/prog/pl_news.php');
		$ml_news_ids = array();

//		Достаем идентификаторы новостей которые еще не отправлялись, но которые надо отправить в текущей рассылке.
		$news = sql_query('SELECT n.n_id, c.cat_id FROM sb_news n, sb_categs c WHERE CONCAT("|", n.n_ml_ids) LIKE "%|'.intval($maillist_id).'|%"
					AND (CONCAT("|", n.n_ml_ids_sent) NOT LIKE "%|'.intval($maillist_id).'|%" OR n.n_ml_ids_sent IS NULL OR n.n_ml_ids_sent = "")
					AND c.cat_ident="pl_news" AND c.cat_level=0');

		$root_cat_id = 0;
		if($news)
		{
			foreach($news as $val)
			{
				$ml_news_ids[] = $val[0];
			}
			$root_cat_id = isset($news[0][1]) ? $news[0][1] : 0;
		}
		else
		{
			sb_add_system_message(PL_MAILLIST_CRON_NO_FOUND_NEWS);
			return;
		}

//		Для каждого языка формируем список новостей.
		foreach($m_news_temp_id as $k => $v )
		{
			$news_html = '';

			$params = array();
			$params['ids'] = $root_cat_id;
			$params['filter'] = 'all';
			$params['sort1'] = 'n.n_date';
			$params['sort2'] = 'n.n_title';
			$params['sort3'] = '';
			$params['order1'] = 'DESC';
			$params['order2'] = 'DESC';
			$params['order3'] = '';
			$params['page'] = (isset($m_page_full_news[$k]) ? $m_page_full_news[$k] : '');
			$params['subcategs'] = 1;
			$params['rubrikator'] = 0;
			$params['cloud'] = 0;
			$params['calendar'] = 0;
			$params['use_filter'] = 0;
			$params['moderate'] = 1;
			$params['moderate_email'] = '';
			$params = addslashes(serialize($params));

			$news_ids = array(); // массив идентификаторов новостей которые попали в вывод новостей для рассылки.
			$news_html = fNews_Elem_List('0', (isset($m_news_temp_id[$k]) ? $m_news_temp_id[$k] : 'ru'), $params, '1', $ml_news_ids);

			if(!$news_html)
			{
				sb_add_system_message(PL_MAILLIST_CRON_NO_FOUND_NEWS, SB_MSG_WARNING);
				continue;
			}
			else
			{
				$news_html = explode('||#$', $news_html);
				$news_ids = unserialize(stripslashes($news_html[1]));
				$news_html_lang[$k] = $news_html[0];

				$news_ids_all = array_merge($news_ids_all, $news_ids);
				$news_ids_all = array_unique($news_ids_all ) ;
			}
		}
	}

//	Здесь составляется само письмо

	if (($m_use_default == 1 && $news_html) || $m_use_default != 1)
	{
    	for($i = $send_count; $i != count($to_send); $i++)
    	{
    		if(time() > $timeEnd)
    		{
    			$send_count = $i;

    			$_SESSION[$uid]['PL_MAILLIST_USE_DEFAULT'] = $m_use_default;
    			$_SESSION[$uid]['PL_MAILLIST_NEWS_TEMP_ID'] = $m_news_temp_id;
    			$_SESSION[$uid]['PL_MAILLIST_PAGE_FULL_NEWS'] = $m_page_full_news;
    			$_SESSION[$uid]['PL_MAILLIST_SEND_RELOAD'] = 'RELOAD_MAILLIST';
    			$_SESSION[$uid]['PL_MAILLIST_MAILLIST_ID'] = $maillist_id;
    			$_SESSION[$uid]['PL_MAILLIST_SEND_TEST'] = $m_test;
                $_SESSION[$uid]['PL_MAILLIST_SEND_IMAGES_ATTACH'] = $m_conf_images_attach;
                $_SESSION[$uid]['PL_MAILLIST_SEND_EMAIL'] = $m_conf_email;
                $_SESSION[$uid]['PL_MAILLIST_SEND_CHARSET'] = $m_conf_charset;
                $_SESSION[$uid]['PL_MAILLIST_SEND_SIGNED'] = $m_conf_signed;
                $_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY'] = $first_key;
                $_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY_SIGNED'] = $first_key_signed;
                $_SESSION[$uid]['PL_MAILLIST_SEND_FIELDS'] = $m_fields;
                $_SESSION[$uid]['PL_MAILLIST_SEND_SU_FIELDS'] = $su_fields_temps;
                $_SESSION[$uid]['PL_MAILLIST_SEND_TO_IDS'] = $m_to_ids;
                $_SESSION[$uid]['PL_MAILLIST_SEND_TO_NAMES'] = $m_to_names;
                $_SESSION[$uid]['PL_MAILLIST_SEND_MESSAGE'] = $m_message;
                $_SESSION[$uid]['PL_MAILLIST_SEND_USERS'] = $to_send;
                $_SESSION[$uid]['PL_MAILLIST_SEND_FORMAT'] = $m_conf_format;
                $_SESSION[$uid]['PL_MAILLIST_SEND_COUNT'] = $send_count;
                $_SESSION[$uid]['PL_MAILLIST_SEND_TITLE'] = $m_title;
                $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_OK'] = $send_count_ok;
                $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_ERROR'] = $send_count_error;
                $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SKIP_USERS'] = $skip_users_check;
        	    $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_TAGS'] = $signed_tags;
    	        $_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_VALUES'] = $signed_values;

                $need = count($to_send) - $send_count_ok - $send_count_error;

    			echo 'RELOAD,'.count($to_send).','.$send_count_ok.','.$send_count_error.','.$need;
    			return;
    		}

            if(array_key_exists($to_send[$i]['lang'], $m_message) && $m_message[$to_send[$i]['lang']] != '')
            {
                $tmp_message = $m_message[$to_send[$i]['lang']];
                $tmp_title = $m_title[$to_send[$i]['lang']];
            }
    		else
            {
    			$tmp_message = $m_message[$first_key];
    			$tmp_title = $m_title[$first_key];
            }

            $signed_values[0] = trim(preg_replace('/ml_unsub=[0-9a-z]+/i'.SB_PREG_MOD, '', $signed_values[0]), '&');
    		$signed_values[0] = $signed_values[0].'&ml_unsub='.md5('*##'.$to_send[$i]['id'].'##*');

    		if(array_key_exists($to_send[$i]['lang'], $m_conf_signed) && $m_conf_signed[$to_send[$i]['lang']] != '')
    		{
    			$tmp_signed = str_replace($signed_tags, $signed_values, $m_conf_signed[$to_send[$i]['lang']]);
            }
    		elseif(count($m_conf_signed) > 0 && array_key_exists($first_key_signed, $m_conf_signed))
            {
    			$tmp_signed = str_replace($signed_tags, $signed_values, $m_conf_signed[$first_key_signed]);
            }
            else
            {
    			$tmp_signed = '';
    		}

    		$lng = (isset($to_send[$i]['lang']) && $to_send[$i]['lang'] != '' ? $to_send[$i]['lang'] : 'ru');

            if($skip_users_check == 0)
            {
            	//чистим код от инъекций
            	$tmp_title = sb_clean_string($tmp_title);

            	ob_start();
            	eval(' ?>'.$tmp_title.'<?php ');
            	$tmp_title = ob_get_clean();

                // Формат письма 0 - HTML 1 - Текст
                if($m_conf_format == 1)
                {
    				$tmp = replace_tags($tmp_message, ($m_test == 1 ? -1 : $to_send[$i]['id']), $m_fields, $su_fields_temps, $to_send[$i]['lang'])."\n".$tmp_signed;
    				$tmp = str_replace('{NEWS_LIST}', isset($news_html_lang[$lng]) ? $news_html_lang[$lng] : '', $tmp);

    				//чистим код от инъекций
    				$tmp = sb_clean_string($tmp);

    				ob_start();
            		eval(' ?>'.$tmp.'<?php ');
            		$tmp = ob_get_clean();

                    $mail->setSubject($tmp_title);   // Тема письма
                    $mail->setText($tmp);
                    $mail->setTextCharset($m_conf_charset);
                }
                else // Тоже самое для HTML
                {
    				$tmp = replace_tags($tmp_message, ($m_test == 1 ? -1 : $to_send[$i]['id']), $m_fields , $su_fields_temps, $to_send[$i]['lang'])."<br />".$tmp_signed;
    				$tmp = str_replace('{NEWS_LIST}', isset($news_html_lang[$lng]) ? $news_html_lang[$lng] : '', $tmp);

    				//чистим код от инъекций
    				$tmp = sb_clean_string($tmp);

    				ob_start();
            		eval(' ?>'.$tmp.'<?php ');
            		$tmp = ob_get_clean();

                    $mail->setSubject($tmp_title);   // Тема письма
                    $mail->setHtml($tmp,'',$m_conf_images_attach);
                    $mail->setHtmlCharset($m_conf_charset);
                }
            }
            else
            {
    			// Формат письма 0 - HTML 1 - Текст
                if($m_conf_format == 1)
                {
    				$tmp = replace_tags($tmp_message, -1, $m_fields , $su_fields_temps, $to_send[$i]['lang'])."\n".$tmp_signed;
    				$tmp = str_replace('{NEWS_LIST}', isset($news_html_lang[$lng]) ? $news_html_lang[$lng] : '', $tmp);

    				//чистим код от инъекций
    				$tmp = sb_clean_string($tmp);

    				ob_start();
            		eval(' ?>'.$tmp.'<?php ');
            		$tmp = ob_get_clean();

                    $mail->setSubject($tmp_title);   // Тема письма
                    $mail->setText($tmp);
                    $mail->setTextCharset($m_conf_charset);
                }
    			else // Тоже самое для HTML
                {
    				$tmp = replace_tags($tmp_message, -1, $m_fields , $su_fields_temps, $to_send[$i]['lang'])."<br>".$tmp_signed;
    				$tmp = str_replace('{NEWS_LIST}', isset($news_html_lang[$lng]) ? $news_html_lang[$lng] : '', $tmp);

    				//чистим код от инъекций
    				$tmp = sb_clean_string($tmp);

    				ob_start();
            		eval(' ?>'.$tmp.'<?php ');
            		$tmp = ob_get_clean();

                    $mail->setSubject($tmp_title);   // Тема письма
                    $mail->setHtml($tmp,'',$m_conf_images_attach);
    				$mail->setHtmlCharset($m_conf_charset);
    			}
    		}

    		//	Отправляем письмо
    		if($mail->send(array($to_send[$i]['email']), false))
    		{
    			$send_count_ok ++;
    		}
    		else
    		{
    			$send_count_error ++;
    		}
    	}
	}

	if($send_count_ok > 0 && $m_use_default == 1 && $m_test == 0)
	{
		sql_param_query('UPDATE sb_news SET n_ml_ids_sent = IF (n_ml_ids_sent IS NULL, CONCAT(?, "|"), CONCAT(n_ml_ids_sent, ?, "|"))
				WHERE n_id IN (?a) AND (CONCAT("|", n_ml_ids_sent) NOT LIKE "%|'.intval($maillist_id).'|%" OR n_ml_ids_sent IS NULL)', $maillist_id, $maillist_id, $news_ids_all);
	}

	unset($_SESSION[$uid]['PL_MAILLIST_USE_DEFAULT']);
	unset($_SESSION[$uid]['PL_MAILLIST_NEWS_TEMP_ID']);
	unset($_SESSION[$uid]['PL_MAILLIST_PAGE_FULL_NEWS']);
	unset($_SESSION[$uid]['PL_MAILLIST_MAILLIST_ID']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_MAIL']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_RELOAD']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_TEST']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_IMAGES_ATTACH']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_EMAIL']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_CHARSET']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_SIGNED']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_FIRST_KEY_SIGNED']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_FIELDS']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_SU_FIELDS']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_TO_IDS']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_TO_NAMES']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_MESSAGE']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_USERS']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_FORMAT']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_TITLE']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_OK']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_ERROR']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SKIP_USERS']);
    unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_TAGS']);
	unset($_SESSION[$uid]['PL_MAILLIST_SEND_SEND_SIGNED_VALUES']);

    $GLOBALS['sbVfs']->mLocal = true;
    if(isset($_SESSION[$uid]['PL_MAILLIST_SEND_FILES']))
    {
        $files = $_SESSION[$uid]['PL_MAILLIST_SEND_FILES'];

        for($i = 0; $i < count($files); $i++)
        {
            if ($GLOBALS['sbVfs']->is_file($files[$i]['FILE_PATH']) && $files[$i]['FILE_PATH'] != '')
                $GLOBALS['sbVfs']->delete($files[$i]['FILE_PATH']);
        }
    }

    $GLOBALS['sbVfs']->mLocal = false;
    if(isset($_SESSION[$uid]['PL_MAILLIST_SEND_FILES']))
    {
		unset($_SESSION[$uid]['PL_MAILLIST_SEND_FILES']);
	}

	unset($_SESSION[$uid]);

	echo 'OK,'.count($to_send).','.$send_count_ok.','.$send_count_error.',0';
	return;
}

function fMaillist_Select_User()
{
    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');

    $sel_cat_ids = array();
    $all_cat_ids = array();

    if (isset($_GET['sel_cat_ids']) && $_GET['sel_cat_ids'] != '')
    {
        if (strpos($_GET['sel_cat_ids'], '^') !== false)
        {
            $sel_cat_ids = explode('^', trim($_GET['sel_cat_ids'], '^'));
        }
        else
        {
            $sel_cat_ids = explode(',', trim($_GET['sel_cat_ids'], ','));
        }
    }

    if (isset($_GET['all_cat_ids']) && $_GET['all_cat_ids'] != '')
    {
        if (strpos($_GET['all_cat_ids'], '^') !== false)
        {
            $all_cat_ids = explode('^', trim($_GET['all_cat_ids'], '^'));
        }
        else
        {
            $all_cat_ids = explode(',', $_GET['all_cat_ids']);
        }
    }

    echo '<script>
            sbGetE("event_title").innerHTML = "'.PL_MAILLIST_EDIT_USERS.'";
          </script>';

    if(count($all_cat_ids) == 0)
    {
        sb_show_message(PL_MAILLIST_EDIT_NO_USERS,false);
        return;
    }

    $not_show_cat_ids = array();
    $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_site_users"');

    if($res)
    {
        foreach ($res as $key => $value)
        {
            list($cat_id) = $res[$key];
            if(in_array($cat_id, $all_cat_ids) == false)
            {
                $not_show_cat_ids[] = $cat_id;
            }
        }
    }

    $js_str = '
        function chooseCat()
        {
            var id_ar = sbCatTree.getAllSelected();
            var text_ar = sbCatTree.getAllSelectedText();

            if (id_ar.length > 0)
            {
                id_ar = id_ar.replace(/\,/g, "^");
                id_ar = "^" + id_ar + "^";

                text_ar = text_ar.replace(/\s\[[0-9]+\]/g, "");
            }

            var res = new Object();
            res.ids = id_ar;
            res.names = text_ar;

            sbReturnValue(res);
        }';

    $footer_str = '<table cellspacing="0" cellpadding="7" width="100%" class="form">
    <tr><td class="footer" colspan="2">

        <div class="footer">
            <button onclick="chooseCat();">'.KERNEL_CHOOSE.'</button>&nbsp;&nbsp;&nbsp;
            <button onclick="sbCloseDialog();">'.KERNEL_CANCEL.'</button>
        </div>
    </td></tr></table>';

    $categs = new sbJustCategs('pl_site_users');

    $categs->mCategsNeverShowCats = $not_show_cat_ids;
    $categs->mCategsSelectedIds = $sel_cat_ids;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsClosed = false;
    $categs->mCategsRubrikator = false;

    $categs->mCategsJavascriptStr = $js_str;

    $categs->showTree($footer_str);
    $categs->init();
}

function fMaillist_All_Users()
{
	$res = sql_param_query('SELECT cat.cat_title, link.link_cat_id FROM sb_site_users user, sb_catlinks link, sb_categs cat WHERE user.su_id = link.link_el_id AND cat.cat_ident = "pl_site_users" AND cat.cat_id = link.link_cat_id GROUP BY cat.cat_id');
	if($res)
	{
		$cat_title = $cat_id = '';
		$count = count($res);
		for($i = 0; $i < $count; $i++)
		{
            $cat_title .= $res[$i][0].($count == $i+1 ? '' :' , ');
            $cat_id .= $res[$i][1].',';
	    }
		echo $cat_title.'|'.$cat_id;
    }
}


// Функции редактирования макетов дизайна списка рассылок
function fMaillist_Design_List_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['smt_title'].'</a></b>
    <div class="smalltext" style="margin-top:7px;">';

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

    $id = intval($args['smt_id']);
    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_maillist_design_list" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_maillist_design_list" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

    $result .= '</div>';
    return $result;
}

function fMaillist_Design_List_Init()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_maillist_temps', 'smt_id', 'smt_title', 'fMaillist_Design_List_Get', 'pl_maillist_design_list', 'pl_maillist_design_list');

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_maillist_design_list_32.png';

    $elems->addSorting(PL_MAILLIST_DESIGN_EDIT_SORT_BY_TITLE, 'smt_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;
	$elems->mCategsDeleteWithElementsMenu = true;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_maillist_design_list_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_maillist_design_list_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_maillist_design_list_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
            function linkPages(e)
            {
                if (!sbSelEl)
                {
                    var el = sbEventTarget(e);
                    while (el.parentNode && !el.getAttribute("true_id"))
                        el = el.parentNode;

                    var el_id = el.getAttribute("el_id");
                }
                else
                {
                    var el_id = sbSelEl.getAttribute("el_id");
                }

                strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_maillist_design_list";
                strAttr = "resizable=1,width=800,height=600";
                sbShowModalDialog(strPage, strAttr, null, window);
            }

            function linkTemps(e)
            {
                if (!sbSelEl)
                {
                    var el = sbEventTarget(e);

                    while (el.parentNode && !el.getAttribute("true_id"))
                        el = el.parentNode;

                    var el_id = el.getAttribute("el_id");
                }
                else
                {
                    var el_id = sbSelEl.getAttribute("el_id");
                }

                strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_maillist_design_list";
                strAttr = "resizable=1,width=800,height=600";
                sbShowModalDialog(strPage, strAttr, null, window);
            }

            function MailllistList()
            {
                window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_maillist_init";
            }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_MAILLIST_DESIGN_LIST_DELIVERIES, 'MailllistList();', false);

    $elems->init();
}

function fMaillist_Design_List_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_maillist_design_list'))
		return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT smt_title, smt_lang, smt_checked, smt_count, smt_empty, smt_delim, smt_no_delivery, smt_fields_temps,
        				smt_categs_temps, smt_top, smt_categ_top, smt_elem, smt_categ_bottom, smt_bottom
                                   FROM sb_maillist_temps WHERE smt_id=?d ', $_GET['id'] );
        if ($result)
        {
            list($smt_title, $smt_lang, $smt_checked, $smt_count, $smt_empty, $smt_delim, $smt_no_delivery, $smt_fields_temps, $smt_categs_temps, $smt_top,
            $smt_categ_top, $smt_elem, $smt_categ_bottom, $smt_bottom) = $result[0];
        }
        else
        {
            sb_show_message(PL_MAILLIST_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($smt_fields_temps != '')
            $smt_fields_temps = unserialize($smt_fields_temps);
        else
            $smt_fields_temps = array();

        if ($smt_categs_temps != '')
            $smt_categs_temps = unserialize($smt_categs_temps);
        else
            $smt_categs_temps = array();

        if ($smt_checked != '')
            $smt_checked = explode(' ', $smt_checked);
        else
            $smt_checked = array();
        if(!isset($smt_fields_temps['m_change_date']))
        	$smt_fields_temps['m_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
    }
    elseif (count($_POST) > 0)
    {
		$smt_checked = array();

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $smt_title = $smt_empty = $smt_delim = $smt_no_delivery = $smt_fields_temps =
        $smt_categs_temps = $smt_top = $smt_categ_top = $smt_elem = $smt_categ_bottom = $smt_bottom = '';
        $smt_count = 0;

	    $smt_lang = SB_CMS_LANG;
		$smt_checked = array();

        $smt_fields_temps['m_mail_description'] = '{VALUE}';
		$smt_fields_temps['m_podpiska'] = '<input type=\'checkbox\' name=\'su_mail[]\' value=\'{VALUE}\' {SELECTED}/>';
		$smt_fields_temps['m_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';

		$_GET['id'] = '';
    }

	echo '<script>
			function checkValues()
            {
                var el_title = sbGetE("smt_title");
                if (el_title.value == "")
                {
                    alert("'.PL_MAILLIST_DESIGN_NO_TITLE_MSG.'");
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
	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_maillist_design_list_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');

	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_MAILLIST_DESIGN_TAB1);
	$layout->addHeader(PL_MAILLIST_DESIGN_TAB1);

	$layout->addField(PL_MAILLIST_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $smt_title, 'smt_title', '', 'style="width:97%;"', true));

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'smt_lang');
	$fld->mSelOptions = array($smt_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_LANG, $fld);

	$layout->addPluginFieldsTempsCheckboxes('pl_maillist', $smt_checked, 'smt_checked');

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_MAILLIST_DESIGN_EDIT_NO_DELIVERY, new sbLayoutTextarea($smt_no_delivery, 'smt_no_delivery', '', 'style="width:100%;height:50px;"'));

    $layout->addTab(PL_MAILLIST_DESIGN_TAB2);
    $layout->addHeader(PL_MAILLIST_DESIGN_TAB2);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_DESIGN_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $tags = array('{ID}', '{NAME}', '{CAT_ID}', '{CAT_TITLE}');
	$tags_values = array(PL_MAILLIST_DESIGN_EDIT_ID_TAG, PL_MAILLIST_EDIT_NAME, PL_MAILLIST_DESIGN_EDIT_CAT_ID_TAG, PL_MAILLIST_DESIGN_EDIT_CAT_TITLE_TAG);

	$fld = new sbLayoutTextarea($smt_fields_temps['m_mail_description'], 'smt_fields_temps[m_mail_description]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{VALUE}'), $tags);
	$fld->mValues = array_merge(array(PL_MAILLIST_EDIT_DESCRIPTION), $tags_values);
	$layout->addField(PL_MAILLIST_EDIT_DESCRIPTION, $fld);

	$fld = new sbLayoutTextarea($smt_fields_temps['m_podpiska'], 'smt_fields_temps[m_podpiska]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('<input type=\'checkbox\' name=\'su_mail[]\' value=\'{VALUE}\' {SELECTED}/>'), $tags);
	$fld->mValues = array_merge(array(PL_MAILLIST_DESIGN_EDIT_PODPISKA_FIELD), $tags_values);
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_PODPISKA_FIELD, $fld);

	// Дата последнего изменения
	$fld = new sbLayoutTextarea($smt_fields_temps['m_change_date'], 'smt_fields_temps[m_change_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_CHANGE_DATE_FIELD, $fld);

	$fields_tags = array('{VALUE}');
	$fields_values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

	$mail_tags = array();
    $mail_tags_values = array();
	$layout->getPluginFieldsTags('pl_maillist', $mail_tags, $mail_tags_values);

    if(count($mail_tags) > 0)
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_MAILLIST_DESIGN_USER_FILEDS.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $layout->addPluginFieldsTemps('pl_maillist', $smt_fields_temps, 'smt_', $tags, $tags_values);
    }

    $cat_tags = array();
    $cat_tags_values = array();
    $layout->getPluginFieldsTags('pl_maillist', $cat_tags, $cat_tags_values, true);

    if (count($cat_tags) != 0)
    {
        $layout->addTab(PL_MAILLIST_DESIGN_TAB3);
        $layout->addHeader(PL_MAILLIST_DESIGN_TAB3);
        $layout->addPluginFieldsTemps('pl_maillist', $smt_categs_temps, 'smt_', $tags, $tags_values, true);
	}

	$layout->addTab(PL_MAILLIST_DESIGN_TAB4);
	$layout->addHeader(PL_MAILLIST_DESIGN_TAB4);

	$fld = new sbLayoutInput('text', $smt_count, 'smt_count', 'spin_smt_count', 'style="width:100px;"');
	$fld->mMinValue = 1;
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_COUNT, $fld);

	$fld = new sbLayoutTextarea($smt_top, 'smt_top', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array('{ALL_COUNT}');
	$fld->mValues = array(PL_MAILLIST_DESIGN_EDIT_ALLNUM_TAG);
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_TOP, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($smt_categ_top, 'smt_categ_top', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge(array('-',  '{CAT_ID}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);
	$fld->mValues = array_merge(array(PL_MAILLIST_DESIGN_EDIT_CATEG_GROUP_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_ID_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_TITLE_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_NUM_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_CAT_TOP, $fld);

	$fld = new sbLayoutTextarea($smt_elem, 'smt_elem', '', 'style="width:100%;height:250px;"');
	$fld->mTags = array_merge(array('-', '{ELEM_NUMBER}', '{ID}', '{CHANGE_DATE}', '{ELEM_TITLE}', '{DESCRIPTION}', '{MAILLISTS}'), $mail_tags,
						array('-', '{CAT_ID}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);
	$fld->mValues = array_merge(array(PL_MAILLIST_DESIGN_TAB1, PL_MAILLIST_DESIGN_EDIT_ELEM_NUMBER_TAG, PL_MAILLIST_DESIGN_EDIT_ID_TAG, PL_MAILLIST_DESIGN_EDIT_CHANGE_DATE_TAG, PL_MAILLIST_EDIT_NAME, PL_MAILLIST_EDIT_DESCRIPTION, PL_MAILLIST_DESIGN_EDIT_PODPISKA_FIELD), $mail_tags_values,
						array(PL_MAILLIST_DESIGN_EDIT_CATEG_GROUP_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_ID_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_TITLE_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_NUM_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_ELEMENT, $fld);

    $fld = new sbLayoutTextarea($smt_empty, 'smt_empty', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_MAILLIST_DESIGN_EDIT_EMPTY, $fld);

    $fld = new sbLayoutTextarea($smt_delim, 'smt_delim', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_MAILLIST_DESIGN_EDIT_DELIM, $fld);

    $fld = new sbLayoutTextarea($smt_categ_bottom, 'smt_categ_bottom', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(array('-',  '{CAT_ID}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);
    $fld->mValues = array_merge(array(PL_MAILLIST_DESIGN_EDIT_CATEG_GROUP_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_ID_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_TITLE_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_NUM_TAG, PL_MAILLIST_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
    $layout->addField(PL_MAILLIST_DESIGN_EDIT_CAT_BOTTOM, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($smt_bottom, 'smt_bottom', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array('{ALL_COUNT}');
	$fld->mValues = array(PL_MAILLIST_DESIGN_EDIT_ALLNUM_TAG);
	$layout->addField(PL_MAILLIST_DESIGN_EDIT_BOTTOM, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btf_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_maillist', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btf_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_maillist', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
    $layout->show();
}

function fMaillist_Design_List_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_maillist_design_list'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $smt_checked = array();
    $smt_fields_temps = array();
    $smt_categs_temps = array();

    $smt_count = 0;
    $smt_title = $smt_no_delivery = $smt_top = $smt_categ_top = $smt_elem = $smt_empty = $smt_delim =
    $smt_lang = $smt_categ_bottom = $smt_bottom = '';

    extract($_POST);

    if ($smt_title == '')
    {
        sb_show_message(PL_MAILLIST_DESIGN_EDIT_NO_TITLE_MSG, false, 'warning');
        fMaillist_Design_List_Edit();
        return;
    }

	$row = array();
    $row['smt_title'] = $smt_title;
	$row['smt_lang'] = $smt_lang;
    $row['smt_checked'] = implode(' ', $smt_checked);
	$row['smt_no_delivery'] = $smt_no_delivery;
    $row['smt_bottom'] = $smt_bottom;
	$row['smt_categ_bottom'] = $smt_categ_bottom;
    $row['smt_delim'] = $smt_delim;
	$row['smt_empty'] = $smt_empty;
	$row['smt_elem'] = $smt_elem;
	$row['smt_categ_top'] = $smt_categ_top;
	$row['smt_top'] = $smt_top;
	$row['smt_count'] = $smt_count;
	$row['smt_categs_temps'] = serialize($smt_categs_temps);
	$row['smt_fields_temps'] = serialize($smt_fields_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT smt_title FROM sb_maillist_temps WHERE smt_id=?d', $_GET['id']);
        if ($res)
        {
            //редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_maillist_temps SET ?a WHERE smt_id=?d', $row, $_GET['id'], sprintf(PL_MAILLIST_DESIGN_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_MAILLIST_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_MAILLIST_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);
                fMaillist_Design_List_Edit();
                return;
            }

            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
            $row['smt_id'] = intval($_GET['id']);
            $html_str = fMaillist_Design_List_Get($row);

            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btf_apply']))
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
                fMaillist_Design_List_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_MAILLIST_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_MAILLIST_DESIGN_EDIT_SYSTEMLOG_ERROR, $smt_title), SB_MSG_WARNING);

            fMaillist_Design_List_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_maillist_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sb_add_system_message(sprintf(PL_MAILLIST_DESIGN_ADD_OK, $smt_title));
                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';
				$error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_maillist_temps WHERE smt_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(PL_MAILLIST_DESIGN_ADD_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_MAILLIST_DESIGN_ADD_SYSTEMLOG_ERROR, $smt_title), SB_MSG_WARNING);
            fMaillist_Design_List_Edit();
            return;
        }
    }
}
function fMaillist_Design_List_Delete()
{
    $id = intval($_GET['id']);
    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_maillist_design_list" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_maillist_design_list" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_FAQ_DESIGN_DELETE_ERROR;
    }
}

?>