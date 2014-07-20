<?php

function fFaq_Get($args)
{
	if($args['f_author'] != '')
		$title = $args['f_author'];
	elseif($args['f_email'] != '')
		$title = $args['f_email'];
	else
		$title = '&#8212;';

	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);" id="f_title_'.$args['f_id'].'">'.$title.'</a></b>
	<div class="smalltext" style="margin-top:7px;">'.

	(isset($args['f_id']) ? PL_FAQ_DESIGN_EDIT_ID_TAG.': <span style="color: #33805E;">'.$args['f_id'].'</span><br />' : '')
	.($args['f_email'] ? PL_FAQ_GET_EMAIL.': <span style="color: #33805E;"><a href="mailto:'.$args['f_email'].'">'.$args['f_email'].'</a></span><br />':'');

	if(isset($args['f_user_id']) && !is_null($args['f_user_id']) && $args['f_user_id'] > 0)
	{
		$res_str = fSite_Users_Get_User_Link($args['f_user_id']);
		$result .= PL_FAQ_GET_SITE_USER.': '.($res_str != '' ? $res_str : '<br />');
	}
	$result .= ($args['f_date'] != 0 ? PL_FAQ_GET_DATE.': <span style="color: #33805E;">'.sb_date('d.m.Y '.KERNEL_IN.' H:i', $args['f_date']).'</span><br />' : '');

	@require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');

	$question = '';
	if(sb_strlen($args['f_question']) > 200)
	{
		$question = sbProgParseBBCodes(sb_substr(strip_tags($args['f_question']), 0, 200), '', '', true).'...';
	}
	else
	{
		 $question = sbProgParseBBCodes(strip_tags($args['f_question']), '', '', true);
	}

	$result .= PL_FAQ_GET_QUESTION.': <span id="question_'.$args['f_id'].'" style="color: #33805E;">'.$question.'</span><br />';
	$result .= PL_FAQ_GET_SORT.': <span style="color: #33805E;">'.$args['f_sort'].'</span>';
	$result .= fComments_Get_Count_Get($args['f_id'], 'pl_faq');
	$result .= fVoting_Rating_Get($args['f_id'], 'pl_faq');

	sb_get_workflow_status($result, 'pl_faq', $args['f_show'], $args['f_pub_start'], $args['f_pub_end']);

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$result .= sbLayout::getPluginFieldsInfo('pl_faq', $args);
	$result .= (trim($args['f_answer']) == '' ? '<br /><span style="color:#CC0066;">'.PL_FAQ_GET_QUESTION_NO_ANSWER.'</span>' : '');
	$result .= '</div>';
	return $result;
}

function fFaq_Init(&$elems = '', $external = false)
{
	if(isset($_GET['sel_c']) && $_GET['sel_c'] != '')
	{
		$_SESSION['sb_categs_selected_id']['pl_faq'] = $_GET['sel_c'];
	}
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

	$elems = new sbElements('sb_faq', 'f_id', 'f_author', 'fFaq_Get', 'pl_faq_init', 'pl_faq', 'f_url');
	$elems->mCategsRootName = PL_FAQ_ROOT_NAME;

    $elems->addField('f_author');	// автор вопроса
	$elems->addField('f_date');		// дата
	$elems->addField('f_question');	// вопрос
	$elems->addField('f_answer');	// ответ
	$elems->addField('f_sort');		// индекс сортировки
	$elems->addField('f_show');		// показывать на сайте
	$elems->addField('f_email');	// e-mail
	$elems->addField('f_user_id');	// зарегистрированные авторы
	$elems->addField('f_pub_start');// Дата начала публикации
	$elems->addField('f_pub_end');	// Дата окончания публикации

	$elems->addCategsClosedDescr('read', PL_FAQ_GROUP_READ);
    $elems->addCategsClosedDescr('edit', PL_FAQ_GROUP_WRITE);
    $elems->addCategsClosedDescr('comments_read', PL_FAQ_GROUP_COMMENTS_READ);
    $elems->addCategsClosedDescr('comments_edit', PL_FAQ_GROUP_COMMENTS_EDIT);
    $elems->addCategsClosedDescr('vote', PL_FAQ_GROUP_VOTE);

	$elems->addFilter(PL_FAQ_DESIGN_EDIT_ID_TAG, 'f_id', 'number');
	$elems->addFilter(PL_FAQ_AUTHOR, 'f_author', 'string');
	$elems->addFilter(PL_FAQ_DATE, 'f_date', 'date');
	$elems->addFilter(PL_FAQ_EMAIL, 'f_email', 'string');
	$elems->addFilter(PL_FAQ_QUESTION, 'f_question', 'string');
	sb_add_workflow_filter($elems, 'pl_faq', 'f_show');
	$elems->addFilter(PL_FAQ_ANSWER, 'f_answer', 'string');
    $elems->addFilter(PL_FAQ_PUB_START, 'f_pub_start', 'date');
    $elems->addFilter(PL_FAQ_PUB_END, 'f_pub_end', 'date');

	$elems->addSorting(PL_FAQ_SORT_BY_ID, 'f_id');
	$elems->addSorting(PL_FAQ_SORT_BY_AUTHOR, 'f_author');
    $elems->addSorting(PL_FAQ_SORT_BY_EMAIL, 'f_email');
	$elems->addSorting(PL_FAQ_SORT_BY_DATE, 'f_date');
    $elems->addSorting(PL_FAQ_SORT_BY_SORT, 'f_sort');
    $elems->addSorting(PL_FAQ_SORT_BY_SHOW, 'f_show');
    $elems->addSorting(PL_FAQ_SORT_BY_PUB_START, 'f_pub_start');
    $elems->addSorting(PL_FAQ_SORT_BY_PUB_END, 'f_pub_end');

	$elems->mCategsDeleteWithElementsMenuTitle      = PL_FAQ_CATEGS_DELETE_WITH_ELEMENTS_MENU_TITLE;
    $elems->mCategsPasteLinksMenuTitle = PL_FAQ_CATEGS_PASTE_LINKS_MENU_TITLE;
	$elems->mCategsPasteElemsMenuTitle = PL_FAQ_CATEGS_PASTE_ELEMS_MENU_TITLE;
	$elems->mCategsPasteWithElementsMenuTitle = PL_FAQ_CATEGS_PASTE_WITH_ELEMENTS_MENU_TITLE;

	$elems->mElemsAddMenuTitle  = PL_FAQ_ELEMS_ADD_MENU_TITLE;
	$elems->mElemsEditMenuTitle = PL_FAQ_ELEMS_EDIT_MENU_TITLE;
	$elems->mElemsCopyMenuTitle = PL_FAQ_ELEMS_COPY_MENU_TITLE;
	$elems->mElemsCutMenuTitle  = PL_FAQ_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_edit';
    $elems->mElemsEditDlgWidth = 800;
	$elems->mElemsEditDlgHeight = 730;

	$elems->mElemsAddEvent =  'pl_faq_edit';
    $elems->mElemsAddDlgWidth = 800;
	$elems->mElemsAddDlgHeight = 730;

	$elems->mElemsDeleteEvent = 'pl_faq_delete';
	$elems->mCategsDeleteWithElementsMenu = true;
	$elems->mCategsAfterDeleteWithElementsEvent = 'pl_faq_delete_cat_with_elements';

	$elems->mElemsAfterPasteEvent = 'pl_faq_paste';
	$elems->mCategsPasteWithElementsMenu = true;
	$elems->mCategsAfterPasteWithElementsEvent = 'pl_faq_after_paste_with_elements';

	$elems->mCategsAddEvent = 'pl_faq_categs_edit';
    $elems->mCategsAddDlgWidth = 800;
    $elems->mCategsAddDlgHeight = 550;

	$elems->mCategsEditEvent = 'pl_faq_categs_edit';
    $elems->mCategsEditDlgWidth = 800;
    $elems->mCategsEditDlgHeight = 550;

	$elems->mCategsUrl = true;
	$elems->mCategsFields = true;
	$elems->mElemsUseLinks = true;

	$elems->mElemsJavascriptStr = '
    	var timer = "";
        function showComments(f_id)
        {
            if (typeof(f_id) == "undefined")
            {
				f_id = sbSelEl.getAttribute("el_id");
            }
			var f_question = sbGetE("question_" + f_id);
			if (f_question)
            {
				if(timer != "undefined" && timer != "")
					clearInterval(timer);

				f_question = f_question.innerHTML;

				if(f_question.length > 70)
					f_question = f_question.substr(0, 70)+"...";

				sbShowCommentaryWindow("pl_faq", f_id, f_question);
			}
		}';

	if ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'elems_public') && (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_faq')))
	{
		$elems->mElemsJavascriptStr .= '
			function faqSetActive()
			{
				var ids = "0";
				for (var i = 0; i < sbSelectedEls.length; i++)
                {
                    var el = sbGetE("el_" + sbSelectedEls[i]);
                    if (el)
                        ids += "," + el.getAttribute("el_id");
                }

            	var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_faq_set_active&ids=" + ids);
            	if (res != "TRUE")
                {
					alert("'.PL_FAQ_SHOWHIDE_ERROR.'");
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
		$elems->addElemsMenuItem (PL_FAQ_SHOWHIDE_MENU, 'faqSetActive();');
	}
	$elems->addElemsMenuItem(PL_FAQ_EDIT_SHOW_COMMENT, 'showComments()', false);
	$elems->mElemsJavascriptStr .= '
		function sbFaqExport(c)
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

			var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_edit&ident=pl_faq&cat_id="+ cat_id;
			var strAttr = "resizable=1,width=700,height=600";
			sbShowModalDialog(strPage, strAttr, null, args);
		}
		function sbFaqImport(c)
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

			var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_import_edit&ident=pl_faq&cat_id="+ cat_id;
			var strAttr = "resizable=1,width=650,height=370";
			sbShowModalDialog(strPage, strAttr, sbAfterFaqImport, args);
		}

		function sbAfterFaqImport()
		{
			window.location.href = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
		}';

	$elems->addElemsMenuItem(PL_FAQ_EDIT_EXPORT, 'sbFaqExport()', false);
	//$elems->addElemsMenuItem(PL_FAQ_EDIT_IMPORT, 'sbFaqImport()', false);

	$elems->addCategsMenuItem(PL_FAQ_EDIT_EXPORT, 'sbFaqExport(true)');
	//$elems->addCategsMenuItem(PL_FAQ_EDIT_IMPORT, 'sbFaqImport(true)');

	if(isset($_GET['sb_sel_id']) && $_GET['sb_sel_id'] != '' && isset($_GET['show_answer']))
    {
		$res = sql_param_query('SELECT l.link_cat_id FROM sb_catlinks l INNER JOIN sb_categs c ON c.cat_id = l.link_cat_id
										WHERE l.link_el_id = ?d AND c.cat_ident="pl_faq" ', $_GET['sb_sel_id']);
		$elems->mFooterStr = '
				<script>
					var strPage = sb_cms_modal_dialog_file + "?event=pl_faq_edit&id='.intval($_GET['sb_sel_id']).'&plugin_ident=pl_faq&cat_id='.$res[0][0].'&tab_sel=1";
					var strAttr = "resizable=1,width=800,height=830";
					sbShowModalDialog(strPage, strAttr, "");
				</script>';
	}
	elseif(isset($_GET['sb_sel_id']) && $_GET['sb_sel_id'] != '' && isset($_GET['show_comments']))
	{
		$elems->mFooterStr = '
			<script>
				var timer = setInterval("showComments('.$_GET['sb_sel_id'].')", 300);
			</script>';
	}
	if(!$external)
		$elems->init();
}

function fFaq_Edit($htmlStr = '', $footerStr = '', $footerLinkStr = '')
{
	//	определяем групповое редактирование или нет.
	$edit_group = sbIsGroupEdit();
	if($edit_group)
	{
		//	проверка прав доступа
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_faq'))
			return;
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq'))
	{
		return;
	}

	$edit_rights = $_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'elems_edit');
	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '' && !$edit_group)
	{
		$result = sql_param_query('SELECT f_author, f_email, f_phone, f_date, f_question, f_answer, f_sort, f_notify, f_show, f_url, f_user_id, f_pub_start, f_pub_end
		   		                          FROM sb_faq WHERE f_id=?d', $_GET['id']);
		if ($result)
		{
			list($f_author, $f_email, $f_phone, $f_date, $f_question, $f_answer, $f_sort, $f_notify, $f_show, $f_url, $f_user_id, $f_pub_start, $f_pub_end) = $result[0];
		}
		else
		{
		    sb_show_message(PL_FAQ_EDIT_ERROR, true, 'warning');
			return;
		}

		if ($f_date != 0)
		    $f_date = sb_date('d.m.Y H:i', $f_date);
		else
		    $f_date = '';

        if (!is_null($f_pub_start) && $f_pub_start != 0 && $f_pub_start != '')
		    $f_pub_start = sb_date('d.m.Y H:i', $f_pub_start);
		else
		    $f_pub_start = '';

        if (!is_null($f_pub_end) && $f_pub_end != 0 && $f_pub_end != '')
		    $f_pub_end = sb_date('d.m.Y H:i', $f_pub_end);
		else
		    $f_pub_end = '';

		$f_notify_temp_id = 0;
	}
	elseif (count($_POST) > 0)
	{
		$f_pub_start = $f_pub_end = '';
		$f_user_id = null;
		$f_notify = 0;
		$f_show = 0;

		extract($_POST);

    	if (!isset($_GET['id']))
            $_GET['id'] = '';
	}
	else
	{
		$f_pub_start = $f_pub_end = $f_url = $f_author = $f_email = $f_phone = $f_date = $f_question = $f_answer = '';
		$f_notify_temp_id = 0;

		$f_date = sb_date('d.m.Y H:i');
	    $f_notify = 0;
	    $f_user_id = null;
	    $f_show = 1;

		$res = sql_query('SELECT MAX(f_sort) FROM sb_faq');
	    if ($res)
	    {
	        list($f_sort) = $res[0];
	        $f_sort += 10;
	    }
	    else
	    {
			$f_sort = 0;
	    }

		$_GET['id'] = '';
	}

	if(!isset($_POST['f_notify_temp_id']) || $_POST['f_notify_temp_id'] == '')
	{
		$cat_fields = array();
		$res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id = ?d', $_GET['cat_id']);
		if($res)
		{
			list($cat_fields) = $res[0];
			$cat_fields = unserialize($cat_fields);
			$f_notify_temp_id = isset($cat_fields['answer_temp_id']) ? $cat_fields['answer_temp_id'] : '';
		}
	}

	echo '<script>
			function checkValues()
            {
            	'.($edit_group ? '
            	var ch_q = sbGetE("ch_f_question");
            	var ch_d = sbGetE("ch_f_date");
            	var ch_e = sbGetE("ch_f_email");
            	' : '').'

            	if (window.sbCodeditor_f_question)
            	{
            		var question = sbCodeditor_f_question.getCode();
            	}
            	else
            	{
            		var question = sbGetE("f_question").value;
            	}

                if (!question '.($edit_group ? ' && ch_q.checked' : '').')
                {
                    alert("'.PL_FAQ_EDIT_NO_QUESTION_MSG.'");
                    return false;
                }

                var el_date = sbGetE("f_date");
                if (el_date.value == "" '.($edit_group ? ' && ch_d.checked' : '').')
                {
                    alert("'.PL_FAQ_EDIT_NO_DATE_MSG.'");
                    return false;
                }

				var email = sbGetE("f_email");
				var myReg = /^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/;
				if(email.value != "" && !myReg.test(email.value) '.($edit_group ? ' && ch_e.checked' : '').')
                {
					alert("'.PL_FAQ_EDIT_NO_EMAIL_MSG.'");
					return false;
				}
			}';

	if ($htmlStr != '')
	{
		echo '
			function cancel()
            {';
			if($edit_group)
			{
				echo 'sbReturnValue("refresh");';
			}
			else
			{
				echo 'var res = new Object();
			        res.html = "'.$htmlStr.'";
			        res.footer = "'.$footerStr.'";
			        res.footer_link = "'.$footerLinkStr.'";
					sbReturnValue(res);';
			}
		echo '}
			sbAddEvent(window, "close", cancel);';
	}
	elseif($edit_group)
	{
		echo '
			function cancel()
            {
            	sbReturnValue("refresh");
            }
			sbAddEvent(window, "close", cancel);';
	}
	echo '</script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_faq_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main', 'enctype="multipart/form-data"');

	$layout->mTableWidth = '100%';
	$layout->mTitleWidth = '200';
	$layout->addTab(PL_FAQ_EDIT_TAB1);
	$layout->addHeader(PL_FAQ_EDIT_TAB1);

	$layout->addField (PL_FAQ_EDIT_AUTHOR.sbGetGroupEditCheckbox('f_author', $edit_group), new sbLayoutInput('text', $f_author, 'f_author', '', 'style="width:95%;"', false));
	$layout->addField (PL_FAQ_EDIT_EMAIL.sbGetGroupEditCheckbox('f_email', $edit_group), new sbLayoutInput('text', $f_email, 'f_email', '', 'style="width:200px;"', false));
	$layout->addField (PL_FAQ_EDIT_PHONE.sbGetGroupEditCheckbox('f_phone', $edit_group), new sbLayoutInput('text', $f_phone, 'f_phone', '', 'style="width:200px;"', false));
	$layout->addField (PL_FAQ_EDIT_DATE.sbGetGroupEditCheckbox('f_date', $edit_group), new sbLayoutDate($f_date, 'f_date'));

	$layout->addField ('', new sbLayoutDelim());
	if(!$edit_group)
	{
		$layout->addField (KERNEL_STATIC_URL, new sbLayoutInput('text', $f_url, 'f_url', '', 'style="width:450px;"', false));
		$layout->addField('', new sbLayoutLabel('<div class="hint_div">'.KERNEL_STATIC_URL_HINT.'</div>', '', '', false));
	}
	$layout->addField('', new sbLayoutLabel('<div class="hint_div">'.KERNEL_STATIC_URL_HINT.'</div>', '', '', false));

//	тематические теги
	fClouds_Get_Field($layout, 'f_tags', ($edit_group ? $_GET['ids'] : $_GET['id']), 'pl_faq', '440px', $edit_group);

	$layout->addField (PL_FAQ_EDIT_SORT.sbGetGroupEditCheckbox('f_sort', $edit_group), new sbLayoutInput('text', $f_sort, 'f_sort', 'spin_f_sort', 'style="width:60px;"', false));
	$layout->addField ('', new sbLayoutDelim());
	$layout->addField (PL_FAQ_EDIT_NOTIFY.sbGetGroupEditCheckbox('f_notify', $edit_group), new sbLayoutInput('checkbox', '1', 'f_notify', '', (isset($f_notify) && $f_notify == 1 ? 'checked="checked"' : '' ), false));

	if(!$edit_group)
	{
		$res = sql_query('SELECT categs.cat_title, temps.sftf_id, temps.sftf_title
						FROM sb_categs categs, sb_catlinks links, sb_faq_temps_form temps
						WHERE temps.sftf_id=links.link_el_id
						AND categs.cat_id=links.link_cat_id
						AND categs.cat_ident="pl_faq_form"
						ORDER BY categs.cat_left, temps.sftf_title');

		$options = array();
		if ($res)
		{
			$old_cat_title = '';
			foreach ($res as $value)
	        {
	            list($cat_title, $fdl_id, $fdl_title) = $value;
	            if ($old_cat_title != $cat_title)
	            {
	                $options[uniqid()] = '-'.$cat_title;
	                $old_cat_title = $cat_title;
				}
				$options[$fdl_id] = $fdl_title;
			}
		}

		if(count($options) > 0)
		{
			$fld = new sbLayoutSelect($options, 'f_notify_temp_id');

			if (isset($f_notify_temp_id) && $f_notify_temp_id > 0)
			{
				$fld->mSelOptions = array($f_notify_temp_id);
			}
		}
		else
		{
			$fld = new sbLayoutLabel('<div class="hint_div">'.PL_FAQ_CATEGS_EDIT_FORM_ANSWER_NO_TEMPS_MSG.'</div>', '', '', false);
		}
		$layout->addField(PL_FAQ_CATEGS_EDIT_TEMP_ANSWER_LETTER, $fld);
	}

	if(!$edit_group)
	{
		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_faq', $_GET['id'], 'f_show', $f_show, 'f_pub_start', $f_pub_start, 'f_pub_end', $f_pub_end);
		$layout->getPluginFields('pl_faq', $_GET['id'], 'f_id');
	}
	else
	{
		$states_arr = array();
		$states = sql_query('SELECT f_show FROM sb_faq WHERE f_id IN (?a)', $_GET['ids']);
		if($states)
		{
			foreach($states as $val)
			{
				$states_arr[] = $val[0];
			}
		}

		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_faq', $_GET['ids'], 'f_show', $states_arr, 'f_pub_start', $f_pub_start, 'f_pub_end', $f_pub_end);
		$layout->getPluginFields('pl_faq', '', 'f_id', false, $edit_group);
	}

	$layout->addTab(PL_FAQ_EDIT_TAB2);
	$layout->addHeader(PL_FAQ_EDIT_TAB2);

	$fld = new sbLayoutTextarea($f_question, 'f_question', '', 'style="width:100%; height:180px;"', true);
	$fld->mShowEditorBtn = true;
	$layout->addField(PL_FAQ_EDIT_QUESTION.sbGetGroupEditCheckbox('f_question', $edit_group), $fld);

	$layout->addField ('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($f_answer, 'f_answer', '', 'style="width:100%; height:180px;"', false);
    $fld->mShowEditorBtn = true;
    $layout->addField(PL_FAQ_EDIT_ANSWER.sbGetGroupEditCheckbox('f_answer', $edit_group), $fld);

	if (!is_null($f_user_id) && $f_user_id > 0)
	{
		$layout->addField('', new sbLayoutInput('hidden', $f_user_id, 'f_user_id'));
		fSite_Users_Get_Author_Tab($layout, $f_user_id);
	}

	if(!$edit_group)
	{
		fVoting_Rating_Edit($layout, $_GET['id'], 'pl_faq');
	}

	$layout->addButton('submit', KERNEL_SAVE, 'btf_save', '', ($edit_rights ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '' && !$edit_group)
    {
		$layout->addButton('submit', KERNEL_APPLY, 'btf_apply', '', ($edit_rights ? '' : 'disabled="disabled"'));
	}
	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' || $edit_group ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fFaq_Edit_Submit()
{
	$edit_group = sbIsGroupEdit();
	if($edit_group)
	{
		//	проверка прав доступа
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_faq'))
			return;
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq'))
	{
		//	проверка прав доступа
		return;
	}

	if (!isset($_GET['id']))
		$_GET['id'] = '';

	$f_author = $f_email = $f_phone = $f_date = $f_question = $f_answer = $f_sort = $f_url = '';
	$ch_clouds = $ch_f_author = $ch_f_email = $ch_f_phone = $ch_f_date = $ch_f_sort = $ch_f_notify = $ch_f_question = $ch_f_answer = $f_notify_temp_id = $f_notify = $f_show = 0;

	extract($_POST);

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$row = $layout->checkPluginFields('pl_faq', ($edit_group ? '' : $_GET['id']), 'f_id', false, $edit_group);
    if ($row === false)
    {
		$layout->deletePluginFieldsFiles();
		fFaq_Edit();
		return;
	}
	if(!$edit_group)
	{
		$f_url = sb_check_chpu($_GET['id'], $f_url, $f_question, 'sb_faq', 'f_url', 'f_id');
		$_POST['f_url'] = $f_url;
	}
	$question = (sb_strlen($f_question) > 150) ? sb_substr($f_question, 0, 150).'...' : $f_question;

	sb_submit_workflow_status($row, 'f_show', 'f_pub_start', 'f_pub_end', $edit_group);

	if(!$edit_group || $edit_group && $ch_f_author == 1)
	{
	    $row['f_author'] = $f_author;
	}
	if(!$edit_group || $edit_group && $ch_f_email == 1)
	{
		$row['f_email'] = $f_email;
	}
	if(!$edit_group || $edit_group && $ch_f_phone == 1)
	{
		$row['f_phone'] = $f_phone;
	}
	if(!$edit_group || $edit_group && $ch_f_date == 1)
	{
		$row['f_date'] = sb_datetoint($f_date);
	}
	if(!$edit_group || $edit_group && $ch_f_question == 1)
	{
		$row['f_question'] = $f_question;
	}
	if(!$edit_group || $edit_group && $ch_f_answer == 1)
	{
	    $row['f_answer'] = $f_answer;
	}
	if(!$edit_group || $edit_group && $ch_f_notify == 1)
	{
		$row['f_notify'] = $f_notify;
	}
	if(!$edit_group || $edit_group && $ch_f_sort == 1)
	{
		$row['f_sort'] = $f_sort;
	}
	if(!$edit_group)
	{
		$row['f_url'] = $f_url;
	}

	if($_GET['id'] != '' || $edit_group)
	{
		if(!$edit_group)
		{
			$res = sql_param_query('SELECT f_question, f_user_id FROM sb_faq WHERE f_id=?d', $_GET['id']);
		}
		else
    	{
			$res = true;//просто чтобы пройти условие ниже.
		}

		if($res)
		{
			//	редактирование
        	if(!$edit_group)
        	{
				list($old_question, $f_user_id) = $res[0];
				$old_question = (sb_strlen($old_question) > 150) ? sb_substr($old_question, 0, 150).'...' : $old_question;

        		sql_query('UPDATE sb_faq SET ?a WHERE f_id=?d', $row, $_GET['id'], sprintf(PL_FAQ_EDIT_OK, $old_question));
        	}
        	else
        	{
        		if(count($row) > 0)
	        	{
					sql_query('UPDATE sb_faq SET ?a WHERE f_id IN (?a)', $row, $_GET['ids'], PL_FAQ_EDIT_GROUP_OK);
	        	}
        	}

			if(!$edit_group)
			{
				$footer_ar = fCategs_Edit_Elem();
				if (!$footer_ar)
	    	    {
					sb_show_message(PL_FAQ_EDIT_ERROR, false, 'warning');
	                sb_add_system_message(sprintf(PL_FAQ_EDIT_SYSTEMLOG_ERROR, $old_question), SB_MSG_WARNING);

	                $layout->deletePluginFieldsFiles();
	                fFaq_Edit();
					return;
	    	    }

				$footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
				$footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

				$row['f_id'] = intval($_GET['id']);

	            fVoting_Rating_Edit_Submit($_GET['id'], 'pl_faq');

				$row['f_user_id'] = $f_user_id;

	    	    $html_str = fFaq_Get($row);
	    	    $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
				$html_str = $GLOBALS['sbSql']->escape($html_str, false, false);
			}

			if (!$edit_group || $edit_group && $ch_clouds == 1)
			{
				fClouds_Set_Field(($edit_group ? $_GET['ids'] : $_GET['id']), 'pl_faq', $f_tags, $edit_group);
			}

	        if($f_notify == 1 && (trim($f_email) != '' || $edit_group) && $f_notify_temp_id != 0 && trim($f_answer) != '')
	        {
	        	if($edit_group)
	        	{
					$f_email = array();
		    		$res = sql_param_query('SELECT f_mail FROM sb_faq WHERE f_id IN (?a)', $_GET['ids']);
		    		if($res)
		    		{
						foreach($res as $key => $value)
						{
							$f_email[] = $value[0];
						}
		    		}
	        	}

				$res = sql_param_query('SELECT sftf_lang, sftf_fields_temps, sftf_categs_temps, sftf_messages FROM  sb_faq_temps_form WHERE sftf_id=?d', $f_notify_temp_id);
				if($res)
				{
					list($sftf_lang, $sftf_fields_temps, $sftf_categs_temps, $sftf_messages) = $res[0];

					$sftf_messages = unserialize($sftf_messages);
					$sftf_fields_temps = unserialize($sftf_fields_temps);
					$sftf_categs_temps = unserialize($sftf_categs_temps);

					if((isset($sftf_messages['user_text_answer']) && trim($sftf_messages['user_text_answer']) != '') || (isset($sftf_messages['user_subj_answer']) && trim($sftf_messages['user_subj_answer']) != ''))
					{
						require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
						require_once SB_CMS_PL_PATH.'/pl_faq/prog/pl_faq.php';

						$mail = new sbMail();

						$type = sbPlugins::getSetting('sb_letters_type');

			            // отправляем письмо
		                $email_subj = fFaq_Parse($sftf_messages['user_subj_answer'], $sftf_fields_temps, ($edit_group ? $_GET['ids'][0] : $_GET['id']), $sftf_lang, '', '_val', $sftf_categs_temps);

		                //чистим код от инъекций
		                $email_subj = sb_clean_string($email_subj);

		                ob_start();
		                eval(' ?>'.$email_subj.'<?php ');
		                $email_subj = trim(ob_get_clean());

		                $email_text = fFaq_Parse($sftf_messages['user_text_answer'], $sftf_fields_temps, ($edit_group ? $_GET['ids'][0] : $_GET['id']), $sftf_lang, '', '_val', $sftf_categs_temps);

		                //чистим код от инъекций
		                $email_text = sb_clean_string($email_text);

		                ob_start();
		                eval(' ?>'.$email_text.'<?php ');
		                $email_text = trim(ob_get_clean());

		                if ($email_subj != '' && $email_text != '')
		                {
							$mail->setSubject($email_subj);
			                if ($type == 'html')
			                {
			                    $mail->setHtml($email_text);
			                }
			                else
			                {
			                    $mail->setText(strip_tags(preg_replace('=<br.*?/?>=i', '', $email_text)));
			                }

			                if($mail->send((is_array($f_email) ? $f_email : array($f_email)), false))
			                {
								if(!$edit_group)
								{
									sql_param_query('UPDATE sb_faq SET f_notify = 0 WHERE f_id=?d', $_GET['id']);
								}
								else
								{
									sql_param_query('UPDATE sb_faq SET f_notify = 0 WHERE f_id IN (?a)', $_GET['ids']);
								}
			                }
		                }
					}
				}
			}

			if(!$edit_group)
			{
	    	    if (!isset($_POST['btf_apply']))
	    	    {
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
					fFaq_Edit($html_str, $footer_str, $footer_link_str);
	    	    }
			}
			else
			{
				echo '<script>
						sbReturnValue("refresh");
					</script>';
			}
			sb_mail_workflow_status('pl_faq', (!$edit_group ? $_GET['id'] : $_GET['ids']), $f_question, $f_show);
	    }
	    else
	    {
			$f_question = (sb_strlen($f_question) > 150) ? sb_substr($f_question, 0, 150).'...' : $f_question;
	    	sb_show_message(PL_FAQ_EDIT_ERROR, false, 'warning');
	    	sb_add_system_message(sprintf(PL_FAQ_EDIT_SYSTEMLOG_ERROR, $f_question), SB_MSG_WARNING);

	    	$layout->deletePluginFieldsFiles();
	    	fFaq_Edit();
			return;
	    }
	}
	else
	{
		$row['f_user_id'] = null;

		$f_question = (sb_strlen($f_question) > 150) ? sb_substr($f_question, 0, 150).'...' : $f_question;

		$error = true;
	    if (sql_param_query('INSERT INTO sb_faq SET ?a', $row))
	    {
    		$id = sql_insert_id();
    		if (fCategs_Add_Elem($id))
    		{
        		sb_add_system_message(sprintf(PL_FAQ_ADD_OK, $f_question));
				echo '<script>
						sbReturnValue('.$id.');
        			  </script>';
				$error = false;
    		}
    		else
    		{
    			sql_query('DELETE FROM sb_faq WHERE f_id="'.$id.'"');
    		}
	    }

	    if ($error)
	    {
	    	sb_show_message(PL_FAQ_ADD_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_ADD_SYSTEMLOG_ERROR, $f_question), SB_MSG_WARNING);

			$layout->deletePluginFieldsFiles();
            fFaq_Edit();
		    return;
	    }
	    else
        {
			fVoting_Rating_Edit_Submit($id, 'pl_faq');
			fClouds_Set_Field($id, 'pl_faq', $f_tags);

			sb_mail_workflow_status('pl_faq', $id, $f_question, $f_show);
        }
	}
}

function fFaq_Set_Active()
{
	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'elems_public'))
		return;

	sbIsGroupEdit(false);

	$date = time();
	foreach ($_GET['ids'] as $val)
    {
       	sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $val, 'pl_faq', $date, $_SESSION['sbAuth']->getUserId(), 'edit');
    }

    $res = sql_param_query('UPDATE sb_faq SET f_show=IF(f_show=0,1,0) WHERE f_id IN (?a)', $_GET['ids'], PL_FAQ_EDIT_ACTIVE_STATUS);
    if ($res)
    	echo 'TRUE';
}

function fFaq_Delete()
{
    fVoting_Delete($_GET['id'], 'pl_faq');
    fComments_Delete_Comment($_GET['id'], 'pl_faq');
    fClouds_Delete($_GET['id'], 'pl_faq');
}

function fFaq_Delete_With_Elements()
{
	if(isset($_GET['elems_id']) && $_GET['elems_id'] != '')
	{
		$elems = explode(',',$_GET['elems_id']);
		fVoting_Delete($elems, 'pl_faq');
    	fComments_Delete_Comment($elems, 'pl_faq');
    	fClouds_Delete($elems, 'pl_faq');
	}
}

function fFaq_Paste()
{
    if (!isset($_GET['action']) || $_GET['action'] != 'copy' || !isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['ne']) || !is_array($_GET['ne']) || count($_GET['ne']) <= 0)
        return;

	$workflow = $_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow('pl_faq');
    $res = sql_query('SELECT f_id, f_question, f_show FROM sb_faq WHERE f_id IN ('.implode(',', $_GET['ne']).')');

	$els = array();
    foreach ($_GET['e'] as $key => $value)
    {
        $els[intval($value)] = intval($_GET['ne'][$key]);
    }

    foreach ($res as $value)
    {
        list($f_id, $f_question, $f_show) = $value;

        $f_url = sb_check_chpu($f_id, '', $f_question, 'sb_faq', 'f_url', 'f_id');

        if ($workflow)
        {
	       	if (!sb_workflow_status_available($f_show, 'pl_faq', -1))
			{
				$f_show = current(sb_get_avail_workflow_status('pl_faq'));
			}
        }

        sql_param_query('UPDATE sb_faq SET f_url=?, f_show=?d WHERE f_id=?d', $f_url, $f_show, $f_id);
    }

    fClouds_Copy($els, 'pl_faq');
}

function fFaq_After_Paste_Categs_With_Elements()
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

	fFaq_Paste();
}

function fFaq_Categs_Edit()
{
	$cat_fields = array();
	$cat_closed = 0;
	$cat_fields['moderates_list'] = '';
	$cat_fields['categs_moderate_email'] = '';

	if (isset($_GET['cat_id']) && $_GET['cat_id'] != '')
    {
        if (!fCategs_Check_Rights($_GET['cat_id']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        $res = sql_param_query('SELECT cat_title, cat_closed, cat_fields, cat_level, cat_url, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
        if ($res)
        {
			list($cat_title, $cat_closed, $p_cat_fields, $cat_level, $cat_url, $cat_rights) = $res[0];
            $cat_level = $cat_level - 1;

            if ($p_cat_fields != '')
                $cat_fields = unserialize($p_cat_fields);

            $_GET['cat_id_p'] = '';
        }
        else
        {
            sb_show_message(PL_FAQ_CATEGS_EDIT_ERROR_NO_CATEG, true, 'warning');
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
		$cat_url = '';

		$res = sql_param_query('SELECT cat_closed, cat_level, cat_fields, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id_p']);
		if ($res)
		{
			$cat_title = '';
			list($cat_closed, $cat_level, $cat_fields, $cat_rights) = $res[0];
			$cat_fields = unserialize($cat_fields);

	    	$_GET['cat_id'] = '';
	    }
	    else
		{
			sb_show_message(PL_FAQ_CATEGS_EDIT_ERROR_NO_CATEG, true, 'warning');
	    	return;
		}
    }

    echo '<script>
    	function checkValues()
    	{
    	    var cat_title = sbGetE("cat_title");
            if (cat_title.value == "")
    		{
    			alert("'.PL_FAQ_CATEGS_EDIT_NO_TITLE_CAT_MSG.'");
        		return false;
    		}

    		var closed = sbGetE("cat_closed");
			var read = sbGetE("group_idspl_faq_read");
			var edit = sbGetE("group_idspl_faq_edit");
			var comments_read = sbGetE("group_idspl_faq_comments_read");
			var comments_edit = sbGetE("group_idspl_faq_comments_edit");
			var vote = sbGetE("group_idspl_faq_vote");

			if(closed.checked && read.value == "" && edit.value == "" && comments_read.value == "" && comments_edit.value == "" && vote.value == "")
			{
				alert("'.PL_FAQ_CATEGS_EDIT_NO_GROUPS_MSG.'");
				return false;
			}
			return true;
    	}
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
		}
	</script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_faq_categs_submit&cat_id='.$_GET['cat_id'].'&cat_id_p='.$_GET['cat_id_p'], 'thisDialog', 'post', 'checkValues()', 'main');
	$layout->mTitleWidth = 200;

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB1);

	$layout->addField(PL_FAQ_DESIGN_EDIT_CAT_TITLE_TAG, new sbLayoutInput('text', $cat_title, 'cat_title', '', 'style="width:97%;"', true));

	$layout->addField(KERNEL_STATIC_URL, new sbLayoutInput('text', $cat_url, 'cat_url', '', 'style="width:97%"'));
	$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.KERNEL_STATIC_URL_HINT.'</div>'));

	$layout->addField('', new sbLayoutDelim());

	$res = sql_query('SELECT categs.cat_title, temps.sftf_id, temps.sftf_title
                      FROM sb_categs categs, sb_catlinks links, sb_faq_temps_form temps
                      WHERE temps.sftf_id=links.link_el_id
                      AND categs.cat_id=links.link_cat_id
                      AND categs.cat_ident="pl_faq_form"
                      ORDER BY categs.cat_left, temps.sftf_title');
	$options = array();
    if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $fdl_id, $fdl_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$fdl_id] = $fdl_title;
        }
    }

    if (count($options) > 0)
    {
		$fld = new sbLayoutSelect($options, 'cat_fields[answer_temp_id]');
        if (isset($cat_fields['answer_temp_id']))
        {
            $fld->mSelOptions = array($cat_fields['answer_temp_id']);
        }
    }
    else
    {
		$fld = new sbLayoutLabel('<div class="hint_div">'.PL_FAQ_CATEGS_EDIT_FORM_ANSWER_NO_TEMPS_MSG.'</div>', '', '', false);
	}

	$layout->addField(PL_FAQ_CATEGS_EDIT_TEMP_ANSWER_LETTER, $fld);

	$layout->getPluginFields('pl_faq', $_GET['cat_id'], '', true);

	$layout->addTab(PL_FAQ_CATEGS_EDIT_RIGHTS_TAB);
	$layout->addHeader(PL_FAQ_CATEGS_EDIT_RIGHTS_TAB);

	$layout->addField(PL_FAQ_CATEGS_EDIT_CAT_CLOSED,  new sbLayoutInput('checkbox', '1', 'cat_closed', '', 'onclick="changeType(this);"'.($cat_closed ? ' checked="checked"' : '')));

	if (isset($_GET['cat_id']) && $_GET['cat_id'] != '')
    {
        $layout->addField(PL_FAQ_CATEGS_EDIT_CAT_CLOSE_SUB, new sbLayoutInput('checkbox', '1', 'cat_close_sub'));
    }

	$rights_idents = array();
	$rights_idents[] = 'pl_faq_read';
	$rights_idents[] = 'pl_faq_edit';
	$rights_idents[] = 'pl_faq_comments_read';
	$rights_idents[] = 'pl_faq_comments_edit';
	$rights_idents[] = 'pl_faq_vote';

	$ids_pl_faq_read = '';
	$names_pl_faq_read = '';
	$ids_pl_faq_edit = '';
	$names_pl_faq_edit = '';
	$ids_pl_faq_comments_read = '';
	$names_pl_faq_comments_read = '';
	$ids_pl_faq_comments_edit = '';
	$names_pl_faq_comments_edit = '';
	$ids_pl_faq_vote = '';
	$names_pl_faq_vote = '';

	if(!isset($_GET['cat_id_p']) || $_GET['cat_id_p'] == '')
		$cat_id = $_GET['cat_id'];
	else
		$cat_id = $_GET['cat_id_p'];

	$res = sql_param_query('SELECT group_ids, right_ident FROM sb_catrights WHERE cat_id=?d AND right_ident IN (?a)', $cat_id, $rights_idents);
	if($res)
	{
		foreach($res as $key => $value)
		{
			list($group_ids, $right_ident) = $value;

			${'ids_'.$right_ident} = $group_ids;
			$ids = explode('^', trim($group_ids, '^'));

			$res_titles = sql_param_query('SELECT cat_title FROM sb_categs WHERE cat_id IN (?a)', $ids);
			if ($res_titles)
		   	{
		   		${'names_'.$right_ident} = array();
		   		foreach($res_titles as $val)
		   		{
			   		${'names_'.$right_ident}[] = $val[0];
		   		}
			   	${'names_'.$right_ident} = implode(', ', ${'names_'.$right_ident});
		    }
		    else
		    {
				${'ids_'.$right_ident} = '';
		    }
		}
	}

	$layout->addField('', new sbLayoutInput('hidden', $ids_pl_faq_read, 'group_idspl_faq_read'));
	$layout->addField(PL_FAQ_GROUP_READ, new sbLayoutHTML('<input id="group_namespl_faq_read" name="group_namespl_faq_read"'.(!$cat_closed ? ' disabled="disabled"' : '').' readonly="readonly" style="width:75%;" value="'.$names_pl_faq_read.'">&nbsp;&nbsp;
							 <img class="button" src="'.SB_CMS_IMG_URL.'/users.png" align="absmiddle" id="group_btnpl_faq_read" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\'pl_faq_read\');" width="20" height="20" title="'.KERNEL_BROWSE.'" />
							 &nbsp;&nbsp;<img height="20" width="20" align="absmiddle" title="'.KERNEL_CLEAR.'" onclick="dropGroups(\'pl_faq_read\');" onmousedown="sbPress(this, true);" onmouseup="sbPress(this, false);" id="group_btn_droppl_faq_read" src="'.SB_CMS_IMG_URL.'/users_drop.png" class="button"/>'));

	$layout->addField('', new sbLayoutInput('hidden', $ids_pl_faq_edit, 'group_idspl_faq_edit'));
	$layout->addField(PL_FAQ_GROUP_WRITE, new sbLayoutHTML('<input id="group_namespl_faq_edit" name="group_namespl_faq_edit"'.(!$cat_closed ? ' disabled="disabled"' : '').' readonly="readonly" style="width:75%;" value="'.$names_pl_faq_edit.'">
			                 &nbsp;&nbsp;<img class="button" src="'.SB_CMS_IMG_URL.'/users.png" align="absmiddle" id="group_btnpl_faq_edit" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\'pl_faq_edit\');" width="20" height="20" title="'.KERNEL_BROWSE.'" />
 							 &nbsp;&nbsp;<img height="20" width="20" align="absmiddle" title="'.KERNEL_CLEAR.'" onclick="dropGroups(\'pl_faq_edit\');" onmousedown="sbPress(this, true);" onmouseup="sbPress(this, false);" id="group_btn_droppl_faq_edit" src="'.SB_CMS_IMG_URL.'/users_drop.png" class="button"/>'));

	$layout->addField('', new sbLayoutInput('hidden', $ids_pl_faq_comments_read, 'group_idspl_faq_comments_read'));
	$layout->addField(PL_FAQ_GROUP_COMMENTS_READ, new sbLayoutHTML('<input id="group_namespl_faq_comments_read" name="group_namespl_faq_comments_read"'.(!$cat_closed ? ' disabled="disabled"' : '').' readonly="readonly" style="width:75%;" value="'.$names_pl_faq_comments_read.'">
			                 &nbsp;&nbsp;<img class="button" src="'.SB_CMS_IMG_URL.'/users.png" align="absmiddle" id="group_btnpl_faq_comments_read" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\'pl_faq_comments_read\');" width="20" height="20" title="'.KERNEL_BROWSE.'" />
							 &nbsp;&nbsp;<img height="20" width="20" align="absmiddle" title="'.KERNEL_CLEAR.'" onclick="dropGroups(\'pl_faq_comments_read\');" onmousedown="sbPress(this, true);" onmouseup="sbPress(this, false);" id="group_btn_droppl_faq_comments_read" src="'.SB_CMS_IMG_URL.'/users_drop.png" class="button"/>'));

	$layout->addField('', new sbLayoutInput('hidden', $ids_pl_faq_comments_edit, 'group_idspl_faq_comments_edit'));
	$layout->addField(PL_FAQ_GROUP_COMMENTS_EDIT, new sbLayoutHTML('<input id="group_namespl_faq_comments_edit" name="group_namespl_faq_comments_edit"'.(!$cat_closed ? ' disabled="disabled"' : '').' readonly="readonly" style="width:75%;" value="'.$names_pl_faq_comments_edit.'">
			                 &nbsp;&nbsp;<img class="button" src="'.SB_CMS_IMG_URL.'/users.png" align="absmiddle" id="group_btnpl_faq_comments_edit" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\'pl_faq_comments_edit\');" width="20" height="20" title="'.KERNEL_BROWSE.'" />
							 &nbsp;&nbsp;<img height="20" width="20" align="absmiddle" title="'.KERNEL_CLEAR.'" onclick="dropGroups(\'pl_faq_comments_edit\');" onmousedown="sbPress(this, true);" onmouseup="sbPress(this, false);" id="group_btn_droppl_faq_comments_edit" src="'.SB_CMS_IMG_URL.'/users_drop.png" class="button"/>'));

	$layout->addField('', new sbLayoutInput('hidden', $ids_pl_faq_vote, 'group_idspl_faq_vote'));
	$layout->addField(PL_FAQ_GROUP_VOTE, new sbLayoutHTML('<input id="group_namespl_faq_vote" name="group_namespl_faq_vote"'.(!$cat_closed ? ' disabled="disabled"' : '').' readonly="readonly" style="width:75%;" value="'.$names_pl_faq_vote.'">
							&nbsp;&nbsp;<img class="button" src="'.SB_CMS_IMG_URL.'/users.png" align="absmiddle" id="group_btnpl_faq_vote" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\'pl_faq_vote\');" width="20" height="20" title="'.KERNEL_BROWSE.'" />
							&nbsp;&nbsp;<img height="20" width="20" align="absmiddle" title="'.KERNEL_CLEAR.'" onclick="dropGroups(\'pl_faq_vote\');" onmousedown="sbPress(this, true);" onmouseup="sbPress(this, false);" id="group_btn_droppl_faq_vote" src="'.SB_CMS_IMG_URL.'/users_drop.png" class="button"/>'));

	$layout->addField('', new sbLayoutInput('hidden', $cat_rights, 'cat_rights'));

	$layout->addTab(PL_FAQ_CATEGS_EDIT_MODERATE_TAB);
	$layout->addHeader(PL_FAQ_CATEGS_EDIT_MODERATE_TAB);

	$fld = new sbLayoutInput('text', (isset($cat_fields['categs_moderate_email']) ? $cat_fields['categs_moderate_email'] : ''), 'categs_moderate_email', '', 'style="width:97%;"');
	$fld->mHTML = '<div class="hint_div">'.PL_FAQ_CATEGS_EDIT_MODERATE_EMAIL_FIELD_DESCR.'</div>';
	$layout->addField(PL_FAQ_CATEGS_EDIT_MODERATE_EMAIL_FIELD, $fld);

	$layout->addField('', new sbLayoutDelim());

	include_once(SB_CMS_PL_PATH.'/pl_site_users/pl_site_users.inc.php');
	$layout->addField(PL_FAQ_CATEGS_EDIT_MODERATE_FROM_SYSTEM_USERS, new sbLayoutHTML(fUsers_Get_Groups(isset($cat_fields['moderates_list']) ? $cat_fields['moderates_list'] : '', true)));

	$layout->addButton('submit', KERNEL_SAVE);
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');
	$layout->show();

}

function fFaq_Categs_Submit()
{
	$categs_moderate_email = $cat_rights = '';
	$cat_closed = 0;
	$cat_close_sub = 0;
	$cat_fields = array();
    $cat_fields['answer_temp_id'] = '';
	$groups = array();
	$users = array();

	extract($_POST);
	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $user_row = $layout->checkPluginFields('pl_faq', $_GET['cat_id'], '', true);

    if ($user_row === false)
    {
        $layout->deletePluginFieldsFiles();
        fFaq_Categs_Edit();
        return;
    }

    $cat_url = sb_check_chpu($_GET['cat_id'], $cat_url, $cat_title, 'sb_categs', 'cat_url', 'cat_id');

	$moderates_list = '';
	if (count($groups) > 0)
	{
		$moderates_list = 'g'.implode('^g', $groups);
	}

	if (count($users) > 0)
	{
		$moderates_list .= ($moderates_list != '' ? '^u' : 'u').implode('^u', $users);
	}

    $_POST['cat_url'] = $cat_url;

    $cat_fields = array_merge($cat_fields, $user_row);
	$cat_fields['moderates_list'] = $moderates_list;
	$cat_fields['categs_moderate_email'] = $categs_moderate_email;

    if($_GET['cat_id'] != '')
    {
        if (!fCategs_Check_Rights($_GET['cat_id']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
		}
		$cat_id = intval($_GET['cat_id']);

        //редактирование
        $res = sql_param_query('SELECT cat_left, cat_right, cat_rubrik, cat_level FROM sb_categs WHERE cat_id=?d', $cat_id);
        if ($res)
        {
        	sql_query('LOCK TABLES sb_categs WRITE, sb_catrights WRITE');
            $result = sql_param_query('UPDATE sb_categs SET cat_title=?, cat_closed=?d, cat_fields=?, cat_url=? WHERE cat_id=?d', $cat_title, $cat_closed, serialize($cat_fields), $cat_url, $cat_id, sprintf(PL_FAQ_CATEGS_SUBMIT_CAT_SYSLOG_EDIT_OK, $cat_title));
            if ($result)
            {
				list($cat_left, $cat_right, $cat_rubrik) = $res[0];

            	$cat_ids = array($cat_id);
		        if ($cat_close_sub == 1)
		        {
		        	$res_sub = sql_query('SELECT cat_id, cat_title FROM sb_categs WHERE cat_left > '.$cat_left.' AND cat_right < '.$cat_right.' AND cat_ident="pl_faq"');
		        	if ($res_sub)
		        	{
		        		foreach ($res_sub as $value)
		        		{
		        			$cat_ids[] = $value[0];
		        			sql_param_query('UPDATE sb_categs SET cat_closed=?d WHERE cat_id=?d', $cat_closed, $value[0], sprintf(PL_FAQ_CATEGS_SUBMIT_CAT_SYSLOG_RIGHTS_OK, $value[1]));
		        		}
		        	}
		        }

				sql_param_query('DELETE FROM sb_catrights WHERE cat_id IN (?a)', $cat_ids);
				if($cat_closed == 1)
                {
                	$rights = array('pl_faq_read', 'pl_faq_edit', 'pl_faq_comments_read', 'pl_faq_comments_edit', 'pl_faq_vote');

                	// закрытый раздел
                    foreach ($rights as $ident)
                    {
                    	foreach ($cat_ids as $value)
                    	{
                        	sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                            	         VALUES (?d, ?, ?)', $value, $_POST['group_ids'.$ident], $ident);
                    	}
                    }
				}
				sql_query('UNLOCK TABLES');

				$count_res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident="pl_faq" AND categs.cat_left >= '.$cat_left.' AND categs.cat_right <= '.$cat_right.' AND links.link_cat_id=categs.cat_id');
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
						res.cat_close_sub = '.intval($cat_close_sub).';
						res.cat_rubrik = "'.$cat_rubrik.'";
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
			sb_show_message(PL_FAQ_CATEGS_SUBMIT_CAT_EDIT_ERROR, true, 'warning');
			sb_add_system_message(sprintf(PL_FAQ_CATEGS_SUBMIT_CAT_SYSLOG_EDIT_ERROR, $cat_title), SB_MSG_WARNING);
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
        $tree = new sbTree('pl_faq');

		$cat_rub = sql_param_query('SELECT cat_rubrik FROM sb_categs WHERE cat_id=?d', $_GET['cat_id_p']);

        $fields = array();
        $fields['cat_title'] = $cat_title;
        $fields['cat_rubrik'] = $cat_rub[0][0];
        $fields['cat_closed'] = intval($cat_closed);
        $fields['cat_rights'] = $cat_rights;//$_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();
		$fields['cat_fields'] = serialize($cat_fields);
        $fields['cat_url'] = $cat_url;

        $cat_id = $tree->insertNode($_GET['cat_id_p'], $fields);

        if ($cat_id)
        {
        	if ($cat_closed == 1)
            {
				// закрытый раздел
                if(isset($_POST['group_idspl_faq_read']) && $_POST['group_idspl_faq_read'] != '')
				{
					sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
										VALUES (?d, ?, ?)', $cat_id, $_POST['group_idspl_faq_read'], 'pl_faq_read');
                }

                if(isset($_POST['group_idspl_faq_edit']) && $_POST['group_idspl_faq_edit'] != '')
                {
					sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
									VALUES (?d, ?, ?)', $cat_id, $_POST['group_idspl_faq_edit'], 'pl_faq_edit');
                }

                if(isset($_POST['group_idspl_faq_comments_read']) && $_POST['group_idspl_faq_comments_read'] != '')
                {
					sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
									VALUES (?d, ?, ?)', $cat_id, $_POST['group_idspl_faq_comments_read'], 'pl_faq_comments_read');
                }

                if(isset($_POST['group_idspl_faq_comments_edit']) && $_POST['group_idspl_faq_comments_edit'] != '')
                {
					sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                                        VALUES (?d, ?, ?)', $cat_id, $_POST['group_idspl_faq_comments_edit'], 'pl_faq_comments_edit');
                }

                if(isset($_POST['group_idspl_faq_vote']) && $_POST['group_idspl_faq_vote'] != '')
                {
	                sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
                                        VALUES (?d, ?, ?)', $cat_id, $_POST['group_idspl_faq_vote'], 'pl_faq_vote');
				}
			}

			sb_add_system_message(sprintf(PL_FAQ_CATEGS_SUBMIT_CAT_SYSLOG_ADD_OK, $cat_title));
            echo '<script>
						var res = new Object();
						res.cat_id = '.$cat_id.';
						res.cat_title = "'.str_replace('"', '\\"', $cat_title).' [0]";
						res.cat_closed = '.intval($cat_closed).';
						res.cat_rubrik = '.$cat_rub[0][0].';
						sbReturnValue(res);
	    		  </script>';
		}
		else
		{
			sb_show_message(PL_FAQ_CATEGS_SUBMIT_CAT_ADD_ERROR, true, 'warning');
			sb_add_system_message(sprintf(PL_FAQ_CATEGS_SUBMIT_CAT_SYSLOG_ADD_ERROR, $cat_title), SB_MSG_WARNING);
		}
	}
}


/**
 * Функции управления макетами дизайна вопрос-ответов
 */
function fFaq_Design_List_Get($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['fdl_title'].'</a></b>
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

    $id = intval($args['fdl_id']);
    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_list" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_list" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fFaq_Design_List ()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
	$elems = new sbElements('sb_faq_temps_list', 'fdl_id', 'fdl_title', 'fFaq_Design_List_Get', 'pl_faq_design_list', 'pl_faq_list');

	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_faq_list_32.png';
	$elems->mCategsDeleteWithElementsMenu = true;

    $elems->addSorting(PL_FAQ_DESIGN_EDIT_SORT_BY_TITLE, 'fdl_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_design_list_edit';
    $elems->mElemsEditDlgWidth = 900;
	$elems->mElemsEditDlgHeight = 700;

	$elems->mElemsAddEvent =  'pl_faq_design_list_edit';
    $elems->mElemsAddDlgWidth = 900;
	$elems->mElemsAddDlgHeight = 700;

	$elems->mElemsDeleteEvent = 'pl_faq_design_list_delete';

	$elems->mCategsClosed = false;
	$elems->mCategsRubrikator = false;

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
				  var el_id = sbSelEl.getAttribute("el_id");

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_faq_list";
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
	              var el_id = sbSelEl.getAttribute("el_id");

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_faq_list";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
	      }
	      function faqList()
	      {
	          window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
	      }';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_FAQ_FAQFEED_MENU, 'faqList();', false);

	$elems->init();

}

function fFaq_Design_List_Edit ($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_list'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
		$result =sql_param_query ('SELECT fdl_title, fdl_lang, fdl_checked, fdl_count, fdl_top, fdl_categ_top, fdl_element, fdl_empty, fdl_delim, fdl_categ_bottom, fdl_bottom, fdl_perpage, fdl_fields_temps, fdl_categs_temps, fdl_pagelist_id, fdl_no_questions, fdl_votes_id, fdl_comments_id, fdl_user_data_id, fdl_tags_list_id
									FROM sb_faq_temps_list WHERE fdl_id=?d ', $_GET['id'] );
   		if ($result)
	    {
            list($fdl_title, $fdl_lang, $fdl_checked, $fdl_count, $fdl_top, $fdl_categ_top, $fdl_element, $fdl_empty, $fdl_delim, $fdl_categ_bottom, $fdl_bottom, $fdl_perpage, $fdl_fields_temps, $fdl_categs_temps, $fdl_pagelist_id, $fdl_no_questions, $fdl_votes_id, $fdl_comments_id, $fdl_user_data_id, $fdl_tags_list_id) = $result[0];
	    }
	    else
	    {
	        sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, true, 'warning');
	        return;
	    }

	    if ($fdl_fields_temps != '')
	        $fdl_fields_temps = unserialize($fdl_fields_temps);
	    else
	        $fdl_fields_temps = array();

	    if ($fdl_categs_temps != '')
	        $fdl_categs_temps = unserialize($fdl_categs_temps);
	    else
	        $fdl_categs_temps = array();

	    if ($fdl_checked != '')
		    $fdl_checked = explode(' ', $fdl_checked);
		else
			$fdl_checked = array();

		if (!isset($fdl_fields_temps['f_registred_users']))
			$fdl_fields_temps['f_registred_users'] = PL_FAQ_DESIGN_EDIT_USER_LINK_TAG_VAL;

		if (!isset($fdl_fields_temps['f_change_date']))
			$fdl_fields_temps['f_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	}
	elseif (count($_POST) > 0)
	{
		$fdl_votes_id = $fdl_comments_id = $fdl_user_data_id = $fdl_pagelist_id = $fdl_tags_list_id = -1;
		$fdl_checked = array();

		extract($_POST);
		if (!isset($_GET['id']))
            $_GET['id'] = '';
	}
	else
	{
  		$fdl_title = $fdl_top = $fdl_categ_top = $fdl_element = $fdl_empty = $fdl_delim = $fdl_categ_bottom = $fdl_bottom = '';

        $fdl_comments_id = 0;
	    $fdl_pagelist_id = $fdl_user_data_id =$fdl_votes_id = $fdl_tags_list_id = -1;
	    $fdl_perpage = 10;
	    $fdl_count = 1;
	    $fdl_checked = array();
	    $fdl_lang = SB_CMS_LANG;
	    $fdl_no_questions = '<div style="text-align:center;font-weight:bold;">'.PL_FAQ_DESIGN_EDIT_NO_QUESTION_TEMP.'</div>';

	    $fdl_fields_temps = array();
	    $fdl_fields_temps['f_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $fdl_fields_temps['f_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $fdl_fields_temps['f_author'] = '{AUTHOR}';
	    $fdl_fields_temps['f_email'] = '{EMAIL}';
	    $fdl_fields_temps['f_phone'] = '{PHONE}';
	    $fdl_fields_temps['f_question'] = '{QUESTION}';
	    $fdl_fields_temps['f_answer'] = '{ANSWER}';
	    $fdl_fields_temps['f_more'] = PL_FAQ_DESIGN_EDIT_MORE_DEFAULT;
        $fdl_fields_temps['f_count_char'] = 200;
		$fdl_fields_temps['f_registred_users'] = PL_FAQ_DESIGN_EDIT_USER_LINK_TAG_VAL;
		$fdl_categs_temps = array();

		$_GET['id'] = '';
	}
		echo '<script>
	        function checkValues()
            {
                var el_title = sbGetE("fdl_title");
                if (el_title.value == "")
                {
          	         alert("'.PL_FAQ_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_faq_design_list_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB1);

    $layout->addField(PL_FAQ_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $fdl_title, 'fdl_title', '', 'style="width:97%;"', true));
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'fdl_lang');
	$fld->mSelOptions = array($fdl_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_FAQ_DESIGN_EDIT_LANG, $fld);

	$layout->addPluginFieldsTempsCheckboxes('pl_faq', $fdl_checked, 'fdl_checked');

    fVoting_Design_Get($layout, $fdl_votes_id, 'fdl_votes_id');
    fComments_Design_Get($layout, $fdl_comments_id, 'fdl_comments_id');
    fSite_Users_Design_Get($layout, $fdl_user_data_id, 'fdl_user_data_id');
    fClouds_Design_Get($layout, $fdl_tags_list_id, 'fdl_tags_list_id', 'element');
    fPager_Design_Get($layout, $fdl_pagelist_id, 'fdl_pagelist_id', $fdl_perpage, 'fdl_perpage');

    $layout->addField(PL_FAQ_DESIGN_COUNT_CHAR, new sbLayoutInput('text', $fdl_fields_temps['f_count_char'], 'fdl_fields_temps[f_count_char]', 'spin_fdl_count_char', 'style="width:100px;"'));
    $layout->addField('', new sbLayoutLabel('<div class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_MORE_LABEL.'</div>', '', '', false));

	$layout->addField('', new sbLayoutDelim());
	$layout->addField(PL_FAQ_DESIGN_EDIT_NO_QUESTIONS, new sbLayoutTextarea($fdl_no_questions, 'fdl_no_questions', '', 'style="width:100%; height:50px;"'));

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB2);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB2);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_TAB1.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));
	$layout->addField('', new sbLayoutDelim());

	$tags = array('{ID}', '{ELEM_URL}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');
	$tags_values = array(PL_FAQ_DESIGN_EDIT_ID_TAG, PL_FAQ_DESIGN_EDIT_ELEM_URL_TAG, PL_FAQ_DESIGN_EDIT_CAT_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG, PL_FAQ_DESIGN_EDIT_CAT_TITLE_TAG, PL_FAQ_DESIGN_EDIT_LINK_TAG);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_date'], 'fdl_fields_temps[f_date]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_FAQ_EDIT_DATE, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_change_date'], 'fdl_fields_temps[f_change_date]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_FAQ_EDIT_CHANGE_DATE, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_author'], 'fdl_fields_temps[f_author]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{AUTHOR}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_AUTHOR), $tags_values);
	$layout->addField(PL_FAQ_EDIT_AUTHOR_QUESTION, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_email'], 'fdl_fields_temps[f_email]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{EMAIL}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_EMAIL), $tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_EMAIL, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_phone'], 'fdl_fields_temps[f_phone]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{PHONE}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_PHONE), $tags_values);
	$layout->addField(PL_FAQ_EDIT_PHONE, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_question'], 'fdl_fields_temps[f_question]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{QUESTION}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_QUESTION), $tags_values);
	$layout->addField(PL_FAQ_EDIT_QUESTION, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_answer'], 'fdl_fields_temps[f_answer]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{ANSWER}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_ANSWER), $tags_values);
	$layout->addField(PL_FAQ_EDIT_ANSWER, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_more'], 'fdl_fields_temps[f_more]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = $tags;
    $fld->mValues = $tags_values;
    $layout->addField(PL_FAQ_DESIGN_MORE, $fld);

	$fld = new sbLayoutTextarea($fdl_fields_temps['f_registred_users'], 'fdl_fields_temps[f_registred_users]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_EDIT_USER_LINK_TAG_VAL), $tags);
    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_USER_LINK_TAG), $tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_USER_LINK_TAG, $fld);

	$faq_tags = array();
	$faq_tags_values = array();
	$layout->getPluginFieldsTags('pl_faq', $faq_tags, $faq_tags_values);

	if(count($faq_tags) > 0)
    {
        $layout->addField('', new sbLayoutDelim());
        $layout->addPluginFieldsTemps('pl_faq', $fdl_fields_temps, 'fdl_', $tags, $tags_values);
    }

	$cat_tags = array();
	$cat_tags_values = array();
	$layout->getPluginFieldsTags('pl_faq', $cat_tags, $cat_tags_values, true);

	if (count($cat_tags) > 0)
	{
    	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB3);
    	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB3);
    	$layout->addPluginFieldsTemps('pl_faq', $fdl_categs_temps, 'fdl_', $tags, $tags_values, true);
	}

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB4);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB4);

	$layout->addField(PL_FAQ_DESIGN_EDIT_COUNT, new sbLayoutInput('text', $fdl_count, 'fdl_count', 'spin_fdl_count', 'style="width:100px;"'));

	//Верх вывода
		//Вытаскиваю пользовательские поля
 	$user_flds_tags = array();
    $user_flds_vals = array();
    $fld = new sbLayoutTextarea($fdl_top, 'fdl_top', '', 'style="width:100%; height:100px;"');
	$user_flds_tags = array(PL_FAQ_DESIGN_FORM_NEWS_INPAGE_SELECT,'<a href=\'{SORT_ID_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_ID_ASC.'</a>',
							'<a href=\'{SORT_ID_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_ID_DESC.'</a>', '<a href=\'{SORT_AUTHOR_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_AUTHOR_ASC.'</a>',
							'<a href=\'{SORT_AUTHOR_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_AUTHOR_DESC.'</a>', '<a href=\'{SORT_EMAIL_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_EMAIL_ASC.'</a>',
							'<a href=\'{SORT_EMAIL_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_EMAIL_DESC.'</a>', '<a href=\'{SORT_PHONE_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_PHONE_ASC.'</a>',
							'<a href=\'{SORT_PHONE_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_PHONE_DESC.'</a>','<a href=\'{SORT_DATE_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_DATE_ASC.'</a>',
							'<a href=\'{SORT_DATE_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_DATE_DESC.'</a>', '<a href=\'{SORT_QUESTION_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_QUESTION_ASC.'</a>',
							'<a href=\'{SORT_QUESTION_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_QUESTION_DESC.'</a>','<a href=\'{SORT_ANSWER_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_ANSWER_ASC.'</a>',
							'<a href=\'{SORT_ANSWER_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_ANSWER_DESC.'</a>','<a href=\'{SORT_SORT_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_SORT_ASC.'</a>',
							'<a href=\'{SORT_SORT_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_SORT_DESC.'</a>','<a href=\'{SORT_SHOW_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_SHOW_ASC.'</a>',
							'<a href=\'{SORT_SHOW_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_SHOW_DESC.'</a>','<a href=\'{SORT_USER_ID_ASC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_USER_ID_ASC.'</a>',
							'<a href=\'{SORT_USER_ID_DESC}\'>'.PL_FAQ_FORM_EDIT_SORT_FIELDS_USER_ID_DESC.'</a>');
	$user_flds_vals = array(PL_FAQ_DESIGN_EDIT_INPAGENUM_TAG, PL_FAQ_FORM_EDIT_SORT_FIELDS_ID_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_ID_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_AUTHOR_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_AUTHOR_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_EMAIL_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_EMAIL_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_PHONE_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_PHONE_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_DATE_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_DATE_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_QUESTION_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_QUESTION_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_ANSWER_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_ANSWER_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_SORT_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_SORT_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_SHOW_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_SHOW_DESC, PL_FAQ_FORM_EDIT_SORT_FIELDS_USER_ID_ASC,
							PL_FAQ_FORM_EDIT_SORT_FIELDS_USER_ID_DESC);

	$layout->getPluginFieldsTagsSort('faq', $user_flds_tags, $user_flds_vals);
	$fld->mTags = array_merge(array('{NUM_LIST}', '{ALL_COUNT}'),$user_flds_tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_PAGELIST_TAG, PL_FAQ_DESIGN_EDIT_ALLNUM_TAG),$user_flds_vals);
    $layout->addField(PL_FAQ_DESIGN_EDIT_TOP, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($fdl_categ_top, 'fdl_categ_top', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge(array('-',  '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_CATEG_GROUP_TAG, PL_FAQ_DESIGN_EDIT_CATEG_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG, PL_FAQ_DESIGN_EDIT_CATEG_TITLE_TAG, PL_FAQ_DESIGN_EDIT_CATEG_NUM_TAG, PL_FAQ_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_CAT_TOP, $fld);

	$fld = new sbLayoutTextarea($fdl_element, 'fdl_element', '', 'style="width:100%;height:250px;"');
	$fld->mTags = array_merge(array('-', '{ELEM_NUMBER}', '{QUESTION}', '{ANSWER}', '{DATE}', '{CHANGE_DATE}', '{AUTHOR}', '{EMAIL}', '{PHONE}', '{ID}', '{ELEM_URL}', '{MORE}', '{LINK}', '{USER_DATA}', '{ELEM_USER_LINK}', '{TAGS}'), $faq_tags,
                              array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}', '-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}'),
	                          array('-', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);

    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_FAQ_GROUP_TAG, PL_FAQ_DESIGN_EDIT_ELEM_NUMBER_TAG, PL_FAQ_DESIGN_EDIT_QUESTION_TAG, PL_FAQ_DESIGN_EDIT_ANSWER_TAG, PL_FAQ_DESIGN_EDIT_DATE_TAG, PL_FAQ_DESIGN_EDIT_CHANGE_DATE_TAG, PL_FAQ_DESIGN_EDIT_AUTHOR_TAG, PL_FAQ_DESIGN_EDIT_EMAIL_TAG, PL_FAQ_DESIGN_EDIT_PHONE_TAG, PL_FAQ_DESIGN_EDIT_ID_TAG, PL_FAQ_DESIGN_EDIT_ELEM_URL_TAG, PL_FAQ_DESIGN_LINK_MORE, PL_FAQ_DESIGN_EDIT_LINK_TAG, PL_FAQ_DESIGN_EDIT_USER_TAG, PL_FAQ_DESIGN_EDIT_USER_LINK_TAG, PL_FAQ_FORM_EDIT_TAGS_LIST_TAG), $faq_tags_values,
                                array(PL_FAQ_DESIGN_COMMENTS_TAG, PL_FAQ_DESIGN_COUNT_COMMENTS_TAG, PL_FAQ_DESIGN_LIST_COMMENTS_TAG, PL_FAQ_DESIGN_FORM_COMMENTS_TAG, PL_FAQ_DESIGN_EDIT_FAQ_RATING,  PL_FAQ_DESIGN_EDIT_FAQ_RATING_TAG, PL_FAQ_DESIGN_EDIT_FAQ_VOTES_COUNT_TAG, PL_FAQ_DESIGN_EDIT_FAQ_VOTES_SUM_TAG, PL_FAQ_DESIGN_EDIT_FAQ_FORM_VOTING_TAG),
								array(PL_FAQ_DESIGN_EDIT_CATEG_GROUP_TAG, PL_FAQ_DESIGN_EDIT_CATEG_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG, PL_FAQ_DESIGN_EDIT_CATEG_TITLE_TAG, PL_FAQ_DESIGN_EDIT_CATEG_NUM_TAG, PL_FAQ_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
    $layout->addField(PL_FAQ_DESIGN_EDIT_ELEMENT, $fld);

	$fld = new sbLayoutTextarea($fdl_empty, 'fdl_empty', '', 'style="width:100%;height:100px;"');
	$layout->addField(PL_FAQ_DESIGN_EDIT_EMPTY, $fld);

	$fld = new sbLayoutTextarea($fdl_delim, 'fdl_delim', '', 'style="width:100%;height:100px;"');
	$layout->addField(PL_FAQ_DESIGN_EDIT_DELIM, $fld);

	$fld = new sbLayoutTextarea($fdl_categ_bottom, 'fdl_categ_bottom', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge(array('-', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_CATEG_GROUP_TAG, PL_FAQ_DESIGN_EDIT_CATEG_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG, PL_FAQ_DESIGN_EDIT_CATEG_TITLE_TAG, PL_FAQ_DESIGN_EDIT_CATEG_NUM_TAG, PL_FAQ_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_CAT_BOTTOM, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($fdl_bottom, 'fdl_bottom', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge(array('{NUM_LIST}', '{ALL_COUNT}'),$user_flds_tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_PAGELIST_TAG, PL_FAQ_DESIGN_EDIT_ALLNUM_TAG),$user_flds_vals);

	$layout->addField(PL_FAQ_DESIGN_EDIT_BOTTOM, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btf_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btf_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fFaq_Design_List_Edit_Submit ()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_list'))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';

	$fdl_user_data_id = $fdl_comments_id = $fdl_votes_id = $fdl_tags_list_id = 0;

    $fdl_pagelist_id = -1;
   	$fdl_lang = SB_CMS_LANG;
    $fdl_checked = array();
    $fdl_fields_temps = array();
    $fdl_categs_temps = array();

    $fdl_title = $fdl_empty = $fdl_count = $fdl_element = $fdl_delim = $fdl_perpage = $fdl_bottom = $fdl_no_questions = $fdl_categ_bottom =
    $fdl_categ_top = $fdl_top = '';

    extract($_POST);

    if ($fdl_title == '')
    {
        sb_show_message(PL_FAQ_DESIGN_EDIT_NO_TITLE_MSG, false, 'warning');
        fFaq_Design_List_Edit();
        return;
    }

    $row = array();
    $row['fdl_title'] = $fdl_title;
    $row['fdl_lang'] = $fdl_lang;
    $row['fdl_checked'] = implode(' ', $fdl_checked);
    $row['fdl_count'] = $fdl_count;
    $row['fdl_top'] = $fdl_top;
    $row['fdl_categ_top'] = $fdl_categ_top;
    $row['fdl_element'] = $fdl_element;
    $row['fdl_empty'] = $fdl_empty;
    $row['fdl_delim'] = $fdl_delim;
    $row['fdl_categ_bottom'] = $fdl_categ_bottom;
    $row['fdl_bottom'] = $fdl_bottom;
    $row['fdl_pagelist_id'] = $fdl_pagelist_id;
    $row['fdl_perpage'] = $fdl_perpage;
    $row['fdl_no_questions'] = $fdl_no_questions;
    $row['fdl_fields_temps'] = serialize($fdl_fields_temps);
    $row['fdl_categs_temps'] = serialize($fdl_categs_temps);
    $row['fdl_votes_id'] = $fdl_votes_id;
    $row['fdl_comments_id'] = $fdl_comments_id;
    $row['fdl_user_data_id'] = $fdl_user_data_id;
    $row['fdl_tags_list_id'] = $fdl_tags_list_id;

	if ($_GET['id'] != '')
	{
		$res = sql_param_query('SELECT fdl_title FROM sb_faq_temps_list WHERE fdl_id=?d', $_GET['id']);
        sbQueryCache::updateTemplate('sb_faq_temps_list', $_GET['id']);
		if ($res)
		{
	        //редактирование
	        list($old_title) = $res[0];

	        sql_param_query('UPDATE sb_faq_temps_list SET ?a WHERE fdl_id=?d', $row, $_GET['id'], sprintf(PL_FAQ_DESIGN_EDIT_OK, $old_title));

			$footer_ar = fCategs_Edit_Elem('design_edit');
			if (!$footer_ar)
    	    {
    	    	sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_FAQ_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fFaq_Design_List_Edit();
    		    return;
    	    }
    	    $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

    	    $row['fdl_id'] = intval($_GET['id']);

            $html_str = fFaq_Design_List_Get($row);
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
    	    	fFaq_Design_List_Edit($html_str, $footer_str);
    	    }
	    }
	    else
	    {
	        sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_EDIT_SYSTEMLOG_ERROR, $fdl_title), SB_MSG_WARNING);

            fFaq_Design_List_Edit();
		    return;
	    }
	}
	else
	{
		$error = true;

	    if (sql_param_query('INSERT INTO sb_faq_temps_list SET ?a', $row))
	    {
    		$id = sql_insert_id();
    		if (fCategs_Add_Elem($id, 'design_edit'))
    		{
                sbQueryCache::updateTemplate('sb_faq_temps_list', $id);
        		sb_add_system_message(sprintf(PL_FAQ_DESIGN_ADD_OK, $fdl_title));
        		echo '<script>
        				sbReturnValue('.$id.');
        			  </script>';
        		$error = false;
    		}
    		else
    		{
				sql_query('DELETE FROM sb_faq_temps_list WHERE fdl_id="'.$id.'"');
    		}
	    }

	    if ($error)
	    {
	        sb_show_message(PL_FAQ_DESIGN_ADD_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_ADD_SYSTEMLOG_ERROR, $fdl_title), SB_MSG_WARNING);

            fFaq_Design_List_Edit();
		    return;
	    }
	}
}

function fFaq_Design_List_Delete ()
{
	$id = intval($_GET['id']);
	$pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_list" AND pages.p_id=elems.e_p_id LIMIT 1');
	$temps = false;
	if (!$pages)
	{
    	$temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
    					WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_list" AND temps.t_id=elems.e_p_id LIMIT 1');
	}

    if ($pages || $temps)
    {
        echo PL_FAQ_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна полного ответа
 */
function fFaq_Design_Full_Get ($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ftf_title'].'</a></b>
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

    $id = intval($args['ftf_id']);

	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_full" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_full" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fFaq_Design_Full ()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

	$elems = new sbElements('sb_faq_temps_full', 'ftf_id', 'ftf_title', 'fFaq_Design_Full_Get', 'pl_faq_design_full', 'pl_faq_full');

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_faq_full_32.png';
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->addField('ftf_title');      // название макета дизайна

    $elems->addSorting(PL_FAQ_DESIGN_EDIT_SORT_BY_TITLE, 'ftf_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_design_full_edit';
    $elems->mElemsEditDlgWidth = 900;
	$elems->mElemsEditDlgHeight = 700;

	$elems->mElemsAddEvent =  'pl_faq_design_full_edit';
    $elems->mElemsAddDlgWidth = 900;
	$elems->mElemsAddDlgHeight = 700;

	$elems->mElemsDeleteEvent = 'pl_faq_design_full_delete';

	$elems->mCategsClosed = false;
	$elems->mCategsRubrikator = false;

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
	              var el_id = sbSelEl.getAttribute("el_id");

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_faq_full";
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
	              var el_id = sbSelEl.getAttribute("el_id");

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_faq_full";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
	      }
	      function faqList()
	      {
	          window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
	      }';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_FAQ_FAQFEED_MENU, 'faqList();', false);

	$elems->init();
}

function fFaq_Design_Full_Edit ($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_full'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
   		$result = sql_param_query('SELECT ftf_title, ftf_lang, ftf_fields_temps, ftf_categs_temps, ftf_fullelement, ftf_checked, ftf_votes_id, ftf_comments_id, ftf_user_data_id, ftf_tags_list_id
   									FROM sb_faq_temps_full WHERE ftf_id=?d', $_GET['id']);
   		if ($result)
	    {
            list($ftf_title, $ftf_lang, $ftf_fields_temps, $ftf_categs_temps, $ftf_fullelement, $ftf_checked, $ftf_votes_id, $ftf_comments_id, $ftf_user_data_id, $ftf_tags_list_id)  = $result[0];
	    }
	    else
	    {
	    	sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, true, 'warning');
	    	return;
	    }

	    if ($ftf_fields_temps != '')
	        $ftf_fields_temps = unserialize($ftf_fields_temps);
	    else
	        $ftf_fields_temps = array();

	    if ($ftf_categs_temps != '')
	        $ftf_categs_temps = unserialize($ftf_categs_temps);
	    else
	        $ftf_categs_temps = array();

	    if ($ftf_checked != '')
		    $ftf_checked = explode(' ', $ftf_checked);
		else
			$ftf_checked = array();

		if(!isset($ftf_fields_temps['f_registred_users']))
			$ftf_fields_temps['f_registred_users'] = PL_FAQ_DESIGN_EDIT_USER_LINK_TAG_VAL;

		if(!isset($ftf_fields_temps['f_change_date']))
			$ftf_fields_temps['f_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';

		if(!isset($ftf_fields_temps['f_prev_link']))
			$ftf_fields_temps['f_prev_link'] = PL_FAQ_DESIGN_EDIT_PREV_LINK_TAG_VAL;

		if(!isset($ftf_fields_temps['f_next_link']))
			$ftf_fields_temps['f_next_link'] = PL_FAQ_DESIGN_EDIT_NEXT_LINK_TAG_VAL;
	}
	elseif (count($_POST) > 0)
	{
		$ftf_user_data_id = $ftf_comments_id = $ftf_votes_id = -1;
		$ftf_checked = array();

    	extract($_POST);
    	if (!isset($_GET['id']))
            $_GET['id'] = '';
	}
	else
	{
		$ftf_tags_list_id = $ftf_title = $ftf_fullelement ='';
		$ftf_comments_id = $ftf_votes_id = $ftf_user_data_id = -1;

		$ftf_checked = array();
		$ftf_lang = SB_CMS_LANG;

	    $ftf_fields_temps = array();
	    $ftf_fields_temps['f_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $ftf_fields_temps['f_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
		$ftf_fields_temps['f_author'] = '{AUTHOR}';
	    $ftf_fields_temps['f_email'] = '{EMAIL}';
		$ftf_fields_temps['f_phone'] = '{PHONE}';
		$ftf_fields_temps['f_question'] = '{QUESTION}';
		$ftf_fields_temps['f_answer'] = '{ANSWER}';
        $ftf_fields_temps['f_rating'] = '{VALUE}';
        $ftf_fields_temps['f_votes_count'] = '{VALUE}';
        $ftf_fields_temps['f_votes_sum'] = '{VALUE}';
		$ftf_fields_temps['f_registred_users'] = PL_FAQ_DESIGN_EDIT_USER_LINK_TAG_VAL;
		$ftf_fields_temps['f_prev_link'] = PL_FAQ_DESIGN_EDIT_PREV_LINK_TAG_VAL;
		$ftf_fields_temps['f_next_link'] = PL_FAQ_DESIGN_EDIT_NEXT_LINK_TAG_VAL;

		$ftf_categs_temps = array();
		$_GET['id'] = '';
	}
	echo '<script>
	        function checkValues()
            {
                var el_title = sbGetE("ftf_title");
                if (el_title.value == "")
                {
          	         alert("'.PL_FAQ_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_faq_design_full_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB1);

	$layout->addField(PL_FAQ_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ftf_title, 'ftf_title', '', 'style="width:450px;"', true));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ftf_lang');
	$fld->mSelOptions = array($ftf_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_FAQ_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes('pl_faq', $ftf_checked, 'ftf_checked');

    fVoting_Design_Get($layout, $ftf_votes_id, 'ftf_votes_id');
    fComments_Design_Get($layout, $ftf_comments_id, 'ftf_comments_id');
    fSite_Users_Design_Get($layout, $ftf_user_data_id, 'ftf_user_data_id');
    fClouds_Design_Get($layout, $ftf_tags_list_id, 'ftf_tags_list_id', 'element');

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB2);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB2);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $tags = array('{ID}', '{ELEM_URL}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
	$tags_values = array(PL_FAQ_DESIGN_EDIT_ID_TAG, PL_FAQ_DESIGN_EDIT_ELEM_URL_TAG, PL_FAQ_DESIGN_EDIT_CAT_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG, PL_FAQ_DESIGN_EDIT_CAT_TITLE_TAG);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_date'], 'ftf_fields_temps[f_date]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_FAQ_EDIT_DATE, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_change_date'], 'ftf_fields_temps[f_change_date]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_FAQ_EDIT_CHANGE_DATE, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_author'], 'ftf_fields_temps[f_author]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array('{AUTHOR}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_AUTHOR), $tags_values);
	$layout->addField(PL_FAQ_EDIT_AUTHOR_QUESTION, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_email'], 'ftf_fields_temps[f_email]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array('{EMAIL}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_EMAIL), $tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_EMAIL, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_phone'], 'ftf_fields_temps[f_phone]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array('{PHONE}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_PHONE), $tags_values);
	$layout->addField(PL_FAQ_EDIT_PHONE, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_question'], 'ftf_fields_temps[f_question]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array('{QUESTION}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_QUESTION), $tags_values);
	$layout->addField(PL_FAQ_EDIT_QUESTION, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_answer'], 'ftf_fields_temps[f_answer]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{ANSWER}'), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_ANSWER), $tags_values);
	$layout->addField(PL_FAQ_EDIT_ANSWER, $fld);

	$fld = new sbLayoutTextarea($ftf_fields_temps['f_registred_users'], 'ftf_fields_temps[f_registred_users]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_EDIT_USER_LINK_TAG_VAL), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_USER_LINK_TAG), $tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_USER_LINK_TAG, $fld);

	// Ссылка на предыдущий вопрос:
	$fld = new sbLayoutTextarea($ftf_fields_temps['f_prev_link'], 'ftf_fields_temps[f_prev_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_EDIT_PREV_LINK_TAG_VAL), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_PREV_LINK_TAG), $tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_PREV_LINK_TAG, $fld);

	// Ссылка на следующий вопрос:
	$fld = new sbLayoutTextarea($ftf_fields_temps['f_next_link'], 'ftf_fields_temps[f_next_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_EDIT_NEXT_LINK_TAG_VAL), $tags);
	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_NEXT_LINK_TAG), $tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_NEXT_LINK_TAG, $fld);

	$layout->addField('', new sbLayoutDelim());

	$layout->addPluginFieldsTemps('pl_faq', $ftf_fields_temps, 'ftf_', $tags, $tags_values);

	$cat_tags = array();
	$cat_tags_values = array();
	$layout->getPluginFieldsTags('pl_faq', $cat_tags, $cat_tags_values, true);

	$faq_tags = array();
	$faq_tags_values = array();
	$layout->getPluginFieldsTags('pl_faq', $faq_tags, $faq_tags_values);

	if (count($cat_tags) != 0)
	{
    	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB3);
    	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB3);

    	$layout->addPluginFieldsTemps('pl_faq', $ftf_categs_temps, 'ftf_', $tags, $tags_values, true);
	}

	$layout->addTab(PL_FAQ_DESIGN_EDIT_FULL_TAG);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_FULL_TAG);

	$fld = new sbLayoutTextarea($ftf_fullelement, 'ftf_fullelement', '', 'style="width:100%;height:250px;"');
	$fld->mTags = array_merge(array('-', '{ID}', '{ELEM_URL}', '{QUESTION}', '{ANSWER}', '{DATE}', '{CHANGE_DATE}', '{AUTHOR}', '{EMAIL}', '{PHONE}', '{USER_DATA}', '{ELEM_USER_LINK}', '{ELEM_PREV}', '{ELEM_NEXT}', '{TAGS}'), $faq_tags,
                                array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}', '-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}'),
                                array('-', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}'), $cat_tags);

    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_FAQ_GROUP_TAG, PL_FAQ_DESIGN_EDIT_ID_TAG, PL_FAQ_DESIGN_EDIT_ELEM_URL_TAG, PL_FAQ_DESIGN_EDIT_QUESTION_TAG, PL_FAQ_DESIGN_EDIT_ANSWER_TAG, PL_FAQ_DESIGN_EDIT_DATE_TAG, PL_FAQ_DESIGN_EDIT_CHANGE_DATE_TAG, PL_FAQ_DESIGN_EDIT_AUTHOR_TAG, PL_FAQ_DESIGN_EDIT_EMAIL_TAG, PL_FAQ_DESIGN_EDIT_PHONE_TAG, PL_FAQ_DESIGN_EDIT_USER_TAG, PL_FAQ_DESIGN_EDIT_USER_LINK_TAG, PL_FAQ_DESIGN_EDIT_PREV_LINK_TAG, PL_FAQ_DESIGN_EDIT_NEXT_LINK_TAG, PL_FAQ_FORM_EDIT_TAGS_LIST_TAG), $faq_tags_values,
                                array(PL_FAQ_DESIGN_COMMENTS_TAG, PL_FAQ_DESIGN_COUNT_COMMENTS_TAG, PL_FAQ_DESIGN_LIST_COMMENTS_TAG, PL_FAQ_DESIGN_FORM_COMMENTS_TAG, PL_FAQ_DESIGN_EDIT_FAQ_RATING,  PL_FAQ_DESIGN_EDIT_FAQ_RATING_TAG, PL_FAQ_DESIGN_EDIT_FAQ_VOTES_COUNT_TAG, PL_FAQ_DESIGN_EDIT_FAQ_VOTES_SUM_TAG, PL_FAQ_DESIGN_EDIT_FAQ_FORM_VOTING_TAG),
                                array(PL_FAQ_DESIGN_EDIT_CATEG_GROUP_TAG, PL_FAQ_DESIGN_EDIT_CATEG_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG, PL_FAQ_DESIGN_EDIT_CATEG_TITLE_TAG, PL_FAQ_DESIGN_EDIT_CATEG_NUM_TAG, PL_FAQ_DESIGN_EDIT_CATEG_LEVEL_TAG), $cat_tags_values);
	$layout->addField(PL_FAQ_DESIGN_EDIT_ELEMENT, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btf_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btf_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
    $layout->show();
}

function fFaq_Design_Full_Edit_Submit ()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_full'))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ftf_votes_id = $ftf_comments_id = $ftf_user_data_id = $ftf_tags_list_id = 0;
    $ftf_checked = array();
    $ftf_lang = SB_CMS_LANG;
    $ftf_fields_temps = array();
    $ftf_categs_temps = array();
    $ftf_title = '';
    $ftf_fullelement = '';

    extract($_POST);

    if ($ftf_title == '')
    {
    	sb_show_message(PL_FAQ_DESIGN_EDIT_NO_TITLE_MSG, false, 'warning');
        fFaq_Design_Full_Edit();
        return;
    }

    $row['ftf_title'] = $ftf_title;
    $row['ftf_lang'] = $ftf_lang;
    $row['ftf_checked'] = implode(' ', $ftf_checked);
    $row['ftf_fullelement'] = $ftf_fullelement;
    $row['ftf_fields_temps'] = serialize($ftf_fields_temps);
    $row['ftf_categs_temps'] = serialize($ftf_categs_temps);
    $row['ftf_votes_id'] = $ftf_votes_id;
    $row['ftf_comments_id'] = $ftf_comments_id;
    $row['ftf_user_data_id'] = $ftf_user_data_id;
    $row['ftf_tags_list_id'] = $ftf_tags_list_id;

    if ($_GET['id'] != '')
	{
	    $res = sql_param_query('SELECT ftf_title FROM sb_faq_temps_full WHERE ftf_id=?d', $_GET['id']);
        sbQueryCache::updateTemplate('sb_faq_temps_full', $_GET['id']);
	    if ($res)
	    {
	        // редактирование
	        list($old_title) = $res[0];

    	    sql_param_query('UPDATE sb_faq_temps_full SET ?a WHERE ftf_id=?d', $row, $_GET['id'], sprintf(PL_FAQ_DESIGN_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
    	    if (!$footer_ar)
    	    {
    	        sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_FAQ_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fFaq_Design_Full_Edit();
    		    return;
    	    }
    	    $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

    	    $row['ftf_id'] = intval($_GET['id']);

            $html_str = fFaq_Design_Full_Get($row);
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
    	        fFaq_Design_Full_Edit($html_str, $footer_str);
    	    }
	    }
	    else
	    {
	        sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_EDIT_SYSTEMLOG_ERROR, $ftf_title), SB_MSG_WARNING);

           	fFaq_Design_Full_Edit();
		    return;
	    }
	}
	else
	{
	    $error = true;
	    if (sql_param_query('INSERT INTO sb_faq_temps_full SET ?a', $row))
	    {
	    	$id = sql_insert_id();

    		if (fCategs_Add_Elem($id, 'design_edit'))
    		{
                sbQueryCache::updateTemplate('sb_faq_temps_full', $id);
    			sb_add_system_message(sprintf(PL_FAQ_DESIGN_ADD_OK, $ftf_title));

    			echo '<script>
        				sbReturnValue('.$id.');
        			  </script>';
    			$error = false;
    		}
    		else
    		{
    		    sql_query('DELETE FROM sb_faq_temps_full WHERE ftf_id="'.$id.'"');
    		}
	    }

	    if ($error)
	    {
	        sb_show_message(sprintf(PL_FAQ_DESIGN_ADD_ERROR, $ftf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_ADD_SYSTEMLOG_ERROR, $ftf_title), SB_MSG_WARNING);

            fFaq_Design_Full_Edit();
		    return;
	    }
	}
}

function fFaq_Design_Full_Delete ()
{
	$id = intval($_GET['id']);
    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_full" AND pages.p_id=elems.e_p_id LIMIT 1');
	$temps = false;
	if (!$pages)
	{
	    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_full" AND temps.t_id=elems.e_p_id LIMIT 1');
	}

    if ($pages || $temps)
    {
        echo PL_FAQ_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна вывода формы добавления
 */
function fFaq_Design_Form_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sftf_title'].'</a></b>
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

    $id = intval($args['sftf_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_form" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_form" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fFaq_Design_Form()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
    $elems = new sbElements('sb_faq_temps_form', 'sftf_id', 'sftf_title', 'fFaq_Design_Form_Get', 'pl_faq_design_form', 'pl_faq_form');

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->addField('sftf_title');      // название макета дизайна
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_faq_form_32.png';

    $elems->addSorting(PL_FAQ_DESIGN_EDIT_SORT_BY_TITLE, 'sftf_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_design_form_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_faq_design_form_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_faq_design_form_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;

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
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_faq_form";
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
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_faq_form";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function faqList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_FAQ_FAQFEED_MENU, 'faqList();', false);

    $elems->init();
}

function fFaq_Design_Form_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_form'))
		return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT sftf_title, sftf_lang, sftf_form, sftf_fields_temps, sftf_categs_temps, sftf_messages
                                    FROM sb_faq_temps_form WHERE sftf_id=?d', $_GET['id']);
        if ($result)
        {
            list($sftf_title, $sftf_lang, $sftf_form, $sftf_fields_temps, $sftf_categs_temps, $sftf_messages) = $result[0];
        }
        else
        {
            sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sftf_fields_temps != '')
            $sftf_fields_temps = unserialize($sftf_fields_temps);
        else
            $sftf_fields_temps = array();

        if ($sftf_categs_temps != '')
            $sftf_categs_temps = unserialize($sftf_categs_temps);
        else
            $sftf_categs_temps = array();

        if ($sftf_messages != '')
            $sftf_messages = unserialize($sftf_messages);
        else
            $sftf_messages = array();

		if(!isset($sftf_messages['user_subj_answer']))
			$sftf_messages['user_subj_answer'] = '';

		if(!isset($sftf_messages['user_text_answer']))
    		$sftf_messages['user_text_answer'] = '';
    }
    elseif (count($_POST) > 0)
    {
        $sftf_categs_temps = array();

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sftf_lang = SB_CMS_LANG;
        $sftf_title = $sftf_form = '';
        $sftf_categs_temps = array();
        $sftf_fields_temps = array();
        $sftf_fields_temps['date_temps'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sftf_fields_temps['question'] = '<textarea name=\'f_question\' rows=\'10\' cols=\'50\'>{VALUE}</textarea>';
        $sftf_fields_temps['author'] = '<input type=\'text\' name=\'f_author\' value=\'{VALUE}\'>';
        $sftf_fields_temps['email'] = '<input type=\'text\' name=\'f_email\' value=\'{VALUE}\'>';
        $sftf_fields_temps['phone'] = '<input type=\'text\' name=\'f_phone\' value=\'{VALUE}\'>';
        $sftf_fields_temps['captcha'] = '<input type=\'text\' name=\'f_captcha\' value=\'\'>';
        $sftf_fields_temps['tags'] = '<input type=\'text\' name=\'f_tags\' value=\'\'>';
        $sftf_fields_temps['img_captcha'] = '<img src=\'{CAPTCHA_IMAGE}\' />
<input type=\'hidden\' name=\'captcha_code\' value=\'{CAPTCHA_IMAGE_HID}\' />';
        $sftf_fields_temps['select_start'] = '<span style="color:red;">';
        $sftf_fields_temps['select_end'] = '</span>';
        $sftf_fields_temps['f_date_val'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sftf_fields_temps['f_question_val'] = '{VALUE}';
        $sftf_fields_temps['f_author_val'] = '{VALUE}';
        $sftf_fields_temps['f_email_val'] = '{VALUE}';
        $sftf_fields_temps['f_phone_val'] = '{VALUE}';
        $sftf_fields_temps['categs_list'] = '<select name=\'f_categ\'>{OPTIONS}</select>';
        $sftf_fields_temps['categs_list_options'] = '<option value=\'{OPT_VALUE}\' {OPT_SELECTED}>{OPT_TEXT}</option>';
        $sftf_fields_temps['notify_email'] = PL_FAQ_DESIGN_FORM_NOTIFY_OPTIONS;

        $sftf_messages = array();
        $sftf_messages['success_add_question'] = PL_FAQ_DESIGN_FORM_EDIT_SUCCESS_ADD_MSG;
        $sftf_messages['err_add_question'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_SEND_MSG;
        $sftf_messages['err_add_necessary_field'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_FIELDS_MSG;
        $sftf_messages['err_save_file'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_SAVE_MSG;
        $sftf_messages['not_have_rights_add'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_RIGHTS_MSG;
        $sftf_messages['f_size_too_large'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_SIZE_FILE_MSG;
        $sftf_messages['err_captcha_code'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_CODE_MSG;
        $sftf_messages['err_type_file'] = PL_FAQ_DESIGN_FORM_EDIT_ERR_TYPE_FILE_MSG;
        $sftf_messages['err_img_size'] = PL_FAQ_DESIGN_FORM_ERR_IMG_SIZE;
        $sftf_messages['user_subj'] = '';
        $sftf_messages['user_text'] = '';
        $sftf_messages['admin_subj'] = '';
        $sftf_messages['admin_text'] = '';

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sftf_title");
                if (el_title.value == "")
                {
                     alert("'.PL_FAQ_DESIGN_NO_TITLE_MSG.'");
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

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_faq_design_form_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_FAQ_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB1);

    $layout->addField(PL_FAQ_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sftf_title, 'sftf_title', '', 'style="width:97%;"', true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sftf_fields_temps['date_temps'], 'sftf_fields_temps[date_temps]', '', 'style="width:100%; height:70px;"');
    $fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_FAQ_DESIGN_EDIT_DATE, $fld);

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sftf_lang');
    $fld->mSelOptions = array($sftf_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_FAQ_DESIGN_EDIT_LANG, $fld);

    $layout->addTab(PL_FAQ_DESIGN_EDIT_FORM_TAB2);
    $layout->addHeader(PL_FAQ_DESIGN_EDIT_FORM_TAB2);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $tags = array('{VALUE}');
    $values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

    // Вопрос
    $fld = new sbLayoutTextarea($sftf_fields_temps['question'], 'sftf_fields_temps[question]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_QUESTION), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_EDIT_QUESTION, $fld);

    // Автор
    $fld = new sbLayoutTextarea($sftf_fields_temps['author'], 'sftf_fields_temps[author]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_AUTHOR), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_AUTHOR.'<br><input type="checkbox" value="1" name="sftf_fields_temps[author_need]"'.(isset($sftf_fields_temps['author_need']) && $sftf_fields_temps['author_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    // E-mail
    $fld = new sbLayoutTextarea($sftf_fields_temps['email'], 'sftf_fields_temps[email]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_EMAIL), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_EDIT_EMAIL.'<br><input type="checkbox" value="1" name="sftf_fields_temps[email_need]"'.(isset($sftf_fields_temps['email_need']) && $sftf_fields_temps['email_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    // Телефон
    $fld = new sbLayoutTextarea($sftf_fields_temps['phone'], 'sftf_fields_temps[phone]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_PHONE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_EDIT_PHONE.'<br><input type="checkbox" value="1" name="sftf_fields_temps[phone_need]"'.(isset($sftf_fields_temps['phone_need']) && $sftf_fields_temps['phone_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    // Тематические теги
    $fld = new sbLayoutTextarea($sftf_fields_temps['tags'], 'sftf_fields_temps[tags]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_TAGS), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(KERNEL_THEME_TAGS.'<br><input type="checkbox" value="1" name="sftf_fields_temps[tags_need]"'.(isset($sftf_fields_temps['tags_need']) && $sftf_fields_temps['tags_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    // Список разделов
    $fld = new sbLayoutTextarea($sftf_fields_temps['categs_list'], 'sftf_fields_temps[categs_list]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_FAQ_DESIGN_FORM_EDIT_CATEGS_LIST, '{OPTIONS}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
    $layout->addField(PL_FAQ_DESIGN_FORM_CATEGS_LIST_TAG.'<br><input type="checkbox" value="1" name="sftf_fields_temps[cat_list_need]" '.(isset($sftf_fields_temps['cat_list_need']) && $sftf_fields_temps['cat_list_need'] == 1 ? 'checked="checked"' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    $fld = new sbLayoutTextarea($sftf_fields_temps['categs_list_options'], 'sftf_fields_temps[categs_list_options]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_FAQ_DESIGN_FORM_EDIT_CATEGS_OPTIONS, PL_FAQ_DESIGN_FORM_EDIT_CATEGS_CHECKBOXES, '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_SELECTED}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_SELECT, SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_CHECKBOX, PL_FAQ_DESIGN_EDIT_CAT_TITLE_TAG, PL_FAQ_DESIGN_FORM_SELECT_VALUE, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
    $layout->addField('', $fld);

    // уведомлять об ответе на email
    $fld = new sbLayoutTextarea((isset($sftf_fields_temps['notify_email']) ? $sftf_fields_temps['notify_email'] : ''), 'sftf_fields_temps[notify_email]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FORM_NOTIFY_OPTIONS), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_DESIGN_FORM_NOTIFY_FIELD, $fld);

    // Поле ввода кода с картинки (CAPTCHA)
    $fld = new sbLayoutTextarea($sftf_fields_temps['captcha'], 'sftf_fields_temps[captcha]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array(PL_FAQ_DESIGN_FORM_EDIT_CAPTCHA);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_FAQ_DESIGN_FORM_CAPTCHA, $fld);

    // Картинка с кодом (CAPTCHA)
    $fld = new sbLayoutTextarea($sftf_fields_temps['img_captcha'], 'sftf_fields_temps[img_captcha]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array(PL_FAQ_DESIGN_FORM_EDIT_IMG_CAPTCHA);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_FAQ_DESIGN_FORM_IMGCAPTCHA, $fld);

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_faq"');
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];
        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);
            if (isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
            {
                $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_FORM_USER_TAB.'</div>';
                $layout->addField('', new sbLayoutHTML($html, true));
                $layout->addField('', new sbLayoutDelim());
            }

            // Пользовательские поля
            $layout->addPluginInputFieldsTemps('pl_faq', $sftf_fields_temps, 'sftf_', '', array(), array(), false, true, '', '', false, true);
        }
    }

    $layout->addTab(PL_FAQ_DESIGN_EDIT_FORM_TAB3);
    $layout->addHeader(PL_FAQ_DESIGN_EDIT_FORM_TAB3);

    $user_tags = array();
    $user_tags_values = array();
    $layout->getPluginFieldsTags('pl_faq', $user_tags, $user_tags_values, false, true, true, false, true);

    $fld = new sbLayoutTextarea($sftf_form, 'sftf_form', '', 'style="width:100%; height:250px;"');
    $fld->mTags = array_merge(array('-', '{MESSAGES}', PL_FAQ_DESIGN_FORM_HTML_FORM_TAG_VALUE, PL_FAQ_DESIGN_FORM_QUESTION_TAG,
									    PL_FAQ_DESIGN_FORM_AUTHOR_TAG,
									    PL_FAQ_DESIGN_FORM_EMAIL_TAG,
									    PL_FAQ_DESIGN_FORM_PHONE_TAG,
									    PL_FAQ_DESIGN_FORM_TAGS_TAG,
									    PL_FAQ_DESIGN_FORM_CAPTCHA_TAG,
									    '{CAPTCHA_IMG}',
									    PL_FAQ_DESIGN_FORM_CATEGS_TAG,
									    PL_FAQ_DESIGN_FORM_NORIFY_TAG), $user_tags);

    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_TAB1, PL_FAQ_DESIGN_FROM_SYSTEM_MESSAGES, PL_FAQ_DESIGN_FORM_HTML_FORM_TAG,
    		PL_FAQ_DESIGN_FORM_QUESTION_FIELD,
            PL_FAQ_DESIGN_FORM_AUTHOR_FIELD, PL_FAQ_DESIGN_FORM_EMAIL_FIELD, PL_FAQ_DESIGN_FORM_PHONE_FIELD, PL_FAQ_DESIGN_EDIT_TAGS_TAG, PL_FAQ_DESIGN_FORM_CAPTCHA_FIELD, PL_FAQ_DESIGN_FORM_IMGCAPTCHA,
			PL_FAQ_DESIGN_FORM_CATEGS_LIST_FIELD, PL_FAQ_DESIGN_FROM_NOTIFY_FIELD), $user_tags_values);
    $layout->addField(PL_FAQ_DESIGN_EDIT_FORM_TAB3 , $fld);

    $layout->addField(PL_FAQ_DESIGN_FORM_SELECT_START, new sbLayoutTextarea($sftf_fields_temps['select_start'], 'sftf_fields_temps[select_start]', '', 'style="width:100%; height:60px;"'));
    $layout->addField(PL_FAQ_DESIGN_FORM_SELECT_END, new sbLayoutTextarea($sftf_fields_temps['select_end'], 'sftf_fields_temps[select_end]', '', 'style="width:100%; height:60px;"'));

	$layout->addTab(PL_FAQ_DESIGN_EDIT_FORM_TAB4);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_FORM_TAB4);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_TAB1.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($sftf_fields_temps['f_date_val'], 'sftf_fields_temps[f_date_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_FAQ_EDIT_DATE, $fld);

    $tags_fileds = array('{VALUE}');
    $values_fields = array(PL_FAQ_DESIGN_FORM_EDIT_VALUE_FIELD);

	$fld = new sbLayoutTextarea($sftf_fields_temps['f_question_val'], 'sftf_fields_temps[f_question_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
	$layout->addField(PL_FAQ_EDIT_QUESTION, $fld);

    $fld = new sbLayoutTextarea($sftf_fields_temps['f_author_val'], 'sftf_fields_temps[f_author_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
    $layout->addField(PL_FAQ_EDIT_AUTHOR, $fld);

    $fld = new sbLayoutTextarea($sftf_fields_temps['f_email_val'], 'sftf_fields_temps[f_email_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
    $layout->addField(PL_FAQ_EDIT_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sftf_fields_temps['f_phone_val'], 'sftf_fields_temps[f_phone_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
    $layout->addField(PL_FAQ_EDIT_PHONE, $fld);

    if (isset($pd_fields))
    {
        if (isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
        {
            $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_FORM_USER_TAB.'</div>';
            $layout->addField('', new sbLayoutHTML($html, true));
            $layout->addField('', new sbLayoutDelim());
        }

        $layout->addPluginFieldsTemps('pl_faq', $sftf_fields_temps, 'sftf_', array(), array(), false, '', '', '_val', false, true);

    }

    if(isset($pd_categs))
    {
        $layout->addPluginFieldsTemps('pl_faq', $sftf_categs_temps, 'sftf_', array(), array(), true, '', '', '_cat_val', false, true);
    }

	$layout->addTab(PL_FAQ_DESIGN_FORM_EDIT_TAB5);
	$layout->addHeader(PL_FAQ_DESIGN_FORM_EDIT_TAB5);

	$tags = array('{DATE}', '{QUESTION}', '{AUTHOR}', '{EMAIL}', '{PHONE}');
	$values = array(PL_FAQ_EDIT_DATE, PL_FAQ_EDIT_QUESTION, PL_FAQ_EDIT_AUTHOR, PL_FAQ_EDIT_EMAIL, PL_FAQ_EDIT_PHONE);

	$users_tags = array();
	$users_tags_value = array();
	$layout->getPluginFieldsTags('pl_faq', $users_tags, $users_tags_value, false, false, false, false, true);

    $users_cat_tags = array();
    $users_cat_tags_value = array();
    $layout->getPluginFieldsTags('pl_faq', $users_cat_tags, $users_cat_tags_value, true, false, false, false, true);

    $fld = new sbLayoutTextarea($sftf_messages['success_add_question'], 'sftf_messages[success_add_question]', '', 'style="width:100%; height:70px;"');
    $fld->mTags = array_merge($tags, $users_tags);
    $fld->mValues = array_merge($values, $users_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_SECCESS_ADD, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sftf_messages['err_add_question'], 'sftf_messages[err_add_question]', '', 'style="width:100%; height:60px;"');
    $fld->mTags = $tags;
    $fld->mValues = $values;
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_ERR_MESSAGES, $fld);

    $fld = new sbLayoutTextarea($sftf_messages['err_add_necessary_field'], 'sftf_messages[err_add_necessary_field]', '', 'style="width:100%; height:60px;"');
    $fld->mTags = $tags;
    $fld->mValues = $values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sftf_messages['err_save_file'], 'sftf_messages[err_save_file]', '', 'style="width:100%; height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}'), $tags);
    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_FILE_NAME), $values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sftf_messages['not_have_rights_add'], 'sftf_messages[not_have_rights_add]', '', 'style="width:100%; height:60px;"');
    $fld->mTags = $tags;
    $fld->mValues = $values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sftf_messages['f_size_too_large'], 'sftf_messages[f_size_too_large]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}', '{FILE_SIZE}'), $tags);
    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_FILE_NAME, PL_FAQ_DESIGN_FORM_EDIT_FILE_SIZE), $values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sftf_messages['err_captcha_code'], 'sftf_messages[err_captcha_code]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $tags;
    $fld->mValues = $values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sftf_messages['err_type_file'], 'sftf_messages[err_type_file]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}', '{FILES_TYPES}'), $tags);
    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_FILE_NAME, PL_FAQ_DESIGN_FORM_EDIT_FILES_TYPES), $values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sftf_messages['err_img_size'], 'sftf_messages[err_img_size]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}'), $tags);
    $fld->mValues = array_merge(array(PL_FAQ_DESIGN_FORM_EDIT_FILE_NAME, PL_FAQ_DESIGN_FORM_EDIT_IMG_WIDTH, PL_FAQ_DESIGN_FORM_EDIT_IMG_HEIGHT), $values);
    $layout->addField('', $fld);

    $layout->addTab(PL_FAQ_DESIGN_FORM_EDIT_LETTERS);
    $layout->addHeader(PL_FAQ_DESIGN_FORM_EDIT_LETTERS);

	$el_tags = array('-', '{ID}', '{LINK}', '{QUESTION}', '{DATE}', '{AUTHOR}', '{EMAIL}', '{PHONE}');
	$el_tags_value = array(PL_FAQ_DESIGN_EDIT_TAB1, PL_FAQ_DESIGN_FORM_ID_QUESTION_TAG, KERNEL_STATIC_URL,
				PL_FAQ_EDIT_QUESTION, PL_FAQ_EDIT_DATE, PL_FAQ_EDIT_AUTHOR, PL_FAQ_EDIT_EMAIL, PL_FAQ_EDIT_PHONE);

	$email_tags = array_merge($users_tags, array('-', '{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}'));
	$email_tags_value = array_merge($users_tags_value, array(PL_FAQ_DESIGN_EDIT_CATEG_GROUP_TAG, PL_FAQ_DESIGN_EDIT_CAT_TITLE_TAG, PL_FAQ_DESIGN_EDIT_CAT_ID_TAG, PL_FAQ_DESIGN_EDIT_CATEG_URL_TAG));

	$fld = new sbLayoutTextarea(isset($sftf_messages['user_subj']) ? $sftf_messages['user_subj'] : '', 'sftf_messages[user_subj]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge($el_tags, $email_tags, $users_cat_tags);
    $fld->mValues = array_merge($el_tags_value, $email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_USER_SUBJ, $fld);

    $fld = new sbLayoutTextarea(isset($sftf_messages['user_text']) ? $sftf_messages['user_text'] : '', 'sftf_messages[user_text]', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array_merge($el_tags, $email_tags, $users_cat_tags);
    $fld->mValues = array_merge($el_tags_value, $email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_USER_TEXT, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea(isset($sftf_messages['user_subj_answer']) ? $sftf_messages['user_subj_answer'] : '', 'sftf_messages[user_subj_answer]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge($el_tags, array('{ANSWER}'), $email_tags, $users_cat_tags);
    $fld->mValues = array_merge($el_tags_value, array(PL_FAQ_EDIT_ANSWER), $email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_USER_SUBJ_ANSW, $fld);

    $fld = new sbLayoutTextarea(isset($sftf_messages['user_text_answer']) ? $sftf_messages['user_text_answer'] : '', 'sftf_messages[user_text_answer]', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array_merge($el_tags, array('{ANSWER}'), $email_tags, $users_cat_tags);
    $fld->mValues = array_merge($el_tags_value, array(PL_FAQ_EDIT_ANSWER), $email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_USER_TEXT_ANSW, $fld);

	$layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea((isset($sftf_messages['admin_subj']) ? $sftf_messages['admin_subj'] : ''), 'sftf_messages[admin_subj]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge($el_tags, $email_tags, $users_cat_tags);
    $fld->mValues = array_merge($el_tags_value, $email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_ADMIN_SUBJ, $fld);

    $fld = new sbLayoutTextarea($sftf_messages['admin_text'], 'sftf_messages[admin_text]', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array_merge($el_tags, $email_tags, $users_cat_tags);
    $fld->mValues = array_merge($el_tags_value, $email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_FAQ_DESIGN_FORM_EDIT_ADMIN_TEXT, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '')
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

	$layout->show();
}

function fFaq_Design_Form_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_form'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sftf_title = $sftf_form = '';
    $sftf_lang = SB_CMS_LANG;
    $sftf_fields_temps = array();
    $sftf_categs_temps = array();
    $sftf_messages = array();

    extract($_POST);

    if ($sftf_title == '')
    {
        sb_show_message(PL_FAQ_DESIGN_NO_TITLE_MSG, false, 'warning');
        fFaq_Design_Form_Edit();
        return;
    }

    $row = array();
    $row['sftf_title'] = $sftf_title;
    $row['sftf_lang'] = $sftf_lang;
    $row['sftf_form'] = $sftf_form;
    $row['sftf_fields_temps'] = serialize($sftf_fields_temps);
    $row['sftf_categs_temps'] = serialize($sftf_categs_temps);
    $row['sftf_messages'] = serialize($sftf_messages);


    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sftf_title FROM sb_faq_temps_form WHERE sftf_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_faq_temps_form SET ?a WHERE sftf_id=?d', $row, $_GET['id'], sprintf(PL_FAQ_DESIGN_FORM_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_FAQ_DESIGN_FORM_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fFaq_Design_Form_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sftf_id'] = intval($_GET['id']);
            $html_str = fFaq_Design_Form_Get($row);
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
                fFaq_Design_Form_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_FORM_EDIT_SYSTEMLOG_ERROR, $sftf_title), SB_MSG_WARNING);

            fFaq_Design_Form_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_faq_temps_form SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sb_add_system_message(sprintf(PL_FAQ_DESIGN_FORM_ADD_OK, $sftf_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_faq_temps_form WHERE sftf_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_FAQ_DESIGN_FORM_ADD_ERROR, $sftf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_FORM_ADD_SYSTEMLOG_ERROR, $sftf_title), SB_MSG_WARNING);

            fFaq_Design_Form_Edit();
            return;
        }
    }
}

function fFaq_Design_Form_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_form" AND pages.p_id=elems.e_p_id LIMIT 1');

	$temps = false;
	if (!$pages)
	{
    	$temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_form" AND temps.t_id=elems.e_p_id LIMIT 1');
	}

    if ($pages || $temps)
    {
        echo PL_FAQ_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна вывода разделов
 */
function fFag_Design_Categs_Get($args)
{
    return fCategs_Design_Get($args, 'pl_faq_categs', 'pl_faq');
}

function fFaq_Design_Categs()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_list', 'ctl_id', 'ctl_title', 'fFag_Design_Categs_Get', 'pl_faq_design_categs', 'pl_faq_design_categs');

    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_faq_categs_32.png';

    $elems->addSorting(PL_FAQ_DESIGN_CATEGS_SORT_BY_TITLE, 'ctl_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_design_categs_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_faq_design_categs_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_faq_design_categs_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;

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
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_faq_categs";
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
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_faq_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function menuList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_FAQ_FAQFEED_MENU, 'menuList();', false);

    $elems->init();
}

function fFag_Design_Categs_Edit()
{
    fCategs_Design_Edit('pl_faq_design_categs', 'pl_faq', 'pl_faq_design_categs_edit_submit');
}

function fFaq_Design_Categs_Edit_Submit()
{
    fCategs_Design_Edit_Submit('pl_faq_design_categs', 'pl_faq', 'pl_faq_design_categs_edit_submit', 'pl_faq_categs', 'pl_faq');
}

function fFaq_Design_Categs_Delete ()
{
    fCategs_Design_Delete('pl_faq_categs', 'pl_faq');
}


/**
 * Функции управления макетами дизайна вывода выбранного раздела
 */
function fFag_Design_Selcat_Get($args)
{
    return fCategs_Design_Get($args, 'pl_faq_selcat', 'pl_faq', true);
}

function fFaq_Design_Selcat()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_full', 'ctf_id', 'ctf_title', 'fFag_Design_Selcat_Get', 'pl_faq_design_selcat', 'pl_faq_design_sel_cat');

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_faq_sel_cat_32.png';
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->addSorting(PL_FAQ_DESIGN_EDIT_SORT_BY_TITLE, 'ctf_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_design_selcat_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_faq_design_selcat_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_faq_design_selcat_delete';

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
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_faq_selcat";
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
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_faq_selcat";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }

          function menuList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_FAQ_FAQFEED_MENU, 'menuList();', false);

    $elems->init();
}

function fFag_Design_Selcat_Edit($htmlStr = '', $footerStr = '')
{
    fCategs_Design_Sel_Cat_Edit('pl_faq_design_sel_cat', 'pl_faq', 'pl_faq_design_selcat_edit_submit');
}

function fFaq_Design_Selcat_Edit_Submit()
{
    fCategs_Design_Sel_Cat_Edit_Submit('pl_faq_design_sel_cat', 'pl_faq', 'pl_faq_design_selcat_edit_submit', 'pl_faq_selcat', 'pl_faq');
}

function fFaq_Design_Selcat_Delete()
{
    fCategs_Design_Delete('pl_faq_selcat', 'pl_faq');
}


/**
 * Функции управления макетами дизайна формы фильтра
 *
 */
function fFaq_Design_Filter_Form_Get($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sftf_title'].'</a></b>
	<div class="smalltext" style="margin-top: 7px;">';

	static $view_info_pages = null;
	static $view_info_temps = null;

	if(is_null($view_info_pages))
	{
		$view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
	}

	if (is_null($view_info_temps))
	{
		$view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
	}

	$id = intval($args['sftf_id']);
	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_filter" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_filter" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');
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

function fFaq_Design_Filter_Form()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
	$elems = new sbElements('sb_faq_temps_form', 'sftf_id', 'sftf_title', 'fFaq_Design_Filter_Form_Get', 'pl_faq_design_filter_form', 'pl_faq_filter');

	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->addField('sftf_title');      // название макета дизайна

    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_faq_filter_form_32.png';

	$elems->addSorting(PL_FAQ_DESIGN_EDIT_SORT_BY_TITLE, 'sftf_title');
	$elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

	$elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
	$elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
	$elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
	$elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_faq_design_filter_form_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_faq_design_filter_form_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_faq_design_filter_form_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;

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
				var el_id = sbSelEl.getAttribute("el_id");

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_faq_filter";
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
				var el_id = sbSelEl.getAttribute("el_id");

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_faq_filter";
			strAttr = "resizable=1,width=800,height=600";
			sbShowModalDialog(strPage, strAttr, null, window);
		}

		function faqList()
        {
        	window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_faq_init";
		}';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_FAQ_FAQFEED_MENU, 'faqList();', false);

	$elems->init();
}

function fFag_Design_Filter_Form_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_filter'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
		$result = sql_param_query('SELECT sftf_title, sftf_lang, sftf_form, sftf_fields_temps
									FROM sb_faq_temps_form WHERE sftf_id=?d', $_GET['id']);
		if ($result)
		{
			list($sftf_title, $sftf_lang, $sftf_form, $sftf_fields_temps) = $result[0];
		}
        else
        {
			sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, true, 'warning');
			return;
        }

		if ($sftf_fields_temps != '')
            $sftf_fields_temps = unserialize($sftf_fields_temps);
        else
            $sftf_fields_temps = array();
         if(!isset($sftf_fields_temps['faq_sort_select']))
         	$sftf_fields_temps['faq_sort_select'] = PL_FAQ_FILTER_FORM_EDIT_SORT_SELECT_FIELD;
    }
	elseif (count($_POST) > 0)
    {
		extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
		$sftf_lang = SB_CMS_LANG;
		$sftf_title = $sftf_form = '';

		$sftf_fields_temps = array();
		$sftf_fields_temps['date_temps'] = '{DAY}.{MONTH}.{LONG_YEAR}';
		$sftf_fields_temps['faq_id'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_FIELD;
		$sftf_fields_temps['faq_id_lo'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_LO_FIELD;
		$sftf_fields_temps['faq_id_hi'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_HI_FIELD;
		$sftf_fields_temps['faq_date'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_FIELD;
		$sftf_fields_temps['faq_date_lo'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_LO_FIELD;
	    $sftf_fields_temps['faq_date_hi'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_HI_FIELD;
		$sftf_fields_temps['faq_author'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_AUTHOR_FIELD;
		$sftf_fields_temps['faq_email'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_EMAIL_FIELD;
		$sftf_fields_temps['faq_phone'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_PHONE_FIELD;
		$sftf_fields_temps['faq_question'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_QUESTION_FIELD;
		$sftf_fields_temps['faq_answer'] = PL_FAQ_DESIGN_FILTER_FORM_EDIT_ANSWER_FIELD;
		$sftf_fields_temps['faq_sort_select'] = PL_FAQ_FILTER_FORM_EDIT_SORT_SELECT_FIELD;

		$_GET['id'] = '';
	}

	echo '<script>
			function checkValues()
            {
				var el_title = sbGetE("sftf_title");
				if (el_title.value == "")
                {
                     alert("'.PL_FAQ_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_faq_design_filter_form_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');

	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_FAQ_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_TAB1);

	$layout->addField(PL_FAQ_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sftf_title, 'sftf_title', '', 'style="width:97%;"', true));
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($sftf_fields_temps['date_temps'], 'sftf_fields_temps[date_temps]', '', 'style="width:100%; height:70px;"');
	$fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_FAQ_DESIGN_EDIT_DATE, $fld);

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sftf_lang');
	$fld->mSelOptions = array($sftf_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_FAQ_DESIGN_EDIT_LANG, $fld);

	$layout->addTab(PL_FAQ_DESIGN_EDIT_FORM_TAB2);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_FORM_TAB2);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_EDIT_TAB1.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));

	$layout->addField('', new sbLayoutDelim());

	$tags = array('{VALUE}');
	$values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

	// Идентификатор
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_id'], 'sftf_fields_temps[faq_id]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_FIELD_LABEL, $fld);

	// Начало интервала идентификаторов вопросов
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_id_lo'], 'sftf_fields_temps[faq_id_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_LO_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_LO_FIELD_LABEL, $fld);

	// Конец интервала идентификаторов вопросов
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_id_hi'], 'sftf_fields_temps[faq_id_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_HI_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_HI_FIELD_LABEL, $fld);

    // Дата
    $fld = new sbLayoutTextarea($sftf_fields_temps['faq_date'], 'sftf_fields_temps[faq_date]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_DATE, $fld);

    // Начало интервала даты
    $fld = new sbLayoutTextarea($sftf_fields_temps['faq_date_lo'], 'sftf_fields_temps[faq_date_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_LO_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_LO_FIELD_LABEL, $fld);

	// Конец интервала даты
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_date_hi'], 'sftf_fields_temps[faq_date_hi]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_HI_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_HI_FIELD_LABEL, $fld);

	//	Автор вопроса
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_author'], 'sftf_fields_temps[faq_author]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_AUTHOR_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_EDIT_AUTHOR, $fld);

	// Email автора вопроса
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_email'], 'sftf_fields_temps[faq_email]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_EMAIL_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_FAQ_EDIT_EMAIL, $fld);

	//	Телефон автора
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_phone'], 'sftf_fields_temps[faq_phone]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_PHONE_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_EDIT_PHONE, $fld);

	//	Вопрос автора
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_question'], 'sftf_fields_temps[faq_question]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_QUESTION_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_EDIT_QUESTION, $fld);

	//	Ответ на вопрос
	$fld = new sbLayoutTextarea($sftf_fields_temps['faq_answer'], 'sftf_fields_temps[faq_answer]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_FAQ_DESIGN_FILTER_FORM_EDIT_ANSWER_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_FAQ_EDIT_ANSWER, $fld);

	//  Поля сортировки
    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_FORM_SORT_FIELDS.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));
	$layout->addField('', new sbLayoutDelim());

	// Макет полей сортировки
    $fld = new sbLayoutTextarea($sftf_fields_temps['faq_sort_select'], 'sftf_fields_temps[faq_sort_select]', '', 'style="width:100%;height:50px;"');
    	//Вытаскиваю пользовательские поля
    $user_flds_tags = array();
    $user_flds_vals = array();
    $user_flds = $layout->getPluginFieldsTagsSort('faq', $user_flds_tags, $user_flds_vals, 'option');

    $fld->mTags = array_merge(array(PL_FAQ_FILTER_FORM_EDIT_SORT_SELECT_FIELD,
										PL_FAQ_FILTER_FORM_EDIT_SORT_ID_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_ID_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_AUTHOR_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_AUTHOR_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_EMAIL_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_EMAIL_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_PHONE_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_PHONE_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_DATE_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_DATE_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_QUESTION_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_QUESTION_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_ANSWER_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_ANSWER_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_SORT_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_SORT_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_SHOW_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_SHOW_FIELD_DESC,
										PL_FAQ_FILTER_FORM_EDIT_SORT_USER_ID_FIELD_ASC, PL_FAQ_FILTER_FORM_EDIT_SORT_USER_ID_FIELD_DESC), $user_flds_tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_ID_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_ID_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_AUTHOR_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_AUTHOR_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_EMAIL_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_EMAIL_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_PHONE_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_PHONE_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_DATE_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_DATE_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_QUESTION_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_QUESTION_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_ANSWER_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_ANSWER_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_SORT_ASC,PL_FAQ_FORM_EDIT_SORT_FIELDS_SORT_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_SHOW_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_SHOW_DESC,
										PL_FAQ_FORM_EDIT_SORT_FIELDS_USER_ID_ASC, PL_FAQ_FORM_EDIT_SORT_FIELDS_USER_ID_DESC),$user_flds_vals);
	$layout->addField(PL_FAQ_DESIGN_FORM_SORT_SELECT_FIELDS, $fld);

	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_faq"');
	if ($res)
	{
		list ($pd_fields) = $res[0];
		if ($pd_fields != '')
		{
			$pd_fields = unserialize($pd_fields);
			if (isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
			{
				$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_FAQ_DESIGN_FORM_USER_TAB.'</div>';
				$layout->addField('', new sbLayoutHTML($html, true));
				$layout->addField('', new sbLayoutDelim());
			}

			// Пользовательские поля
			$layout->addPluginInputFieldsTemps('pl_faq', $sftf_fields_temps, 'sftf_', '', array(), array(), false, false, 'f_f', '', true);
		}
	}

	$layout->addTab(PL_FAQ_DESIGN_EDIT_FORM_TAB3);
	$layout->addHeader(PL_FAQ_DESIGN_EDIT_FORM_TAB3);

	$user_tags = array();
	$user_tags_values = array();
	$layout->getPluginFieldsTags('pl_faq', $user_tags, $user_tags_values, false, true, false, true);

	$fld = new sbLayoutTextarea($sftf_form, 'sftf_form', '', 'style="width:100%; height:250px;"');

	$fld->mTags = array_merge(array('-', PL_FAQ_DESIGN_FILTER_FORM_EDIT_HTML_FORM_TAG,
									'{AUTHOR}',
									'{EMAIL}',
									'{PHONE}',
									'{QUESTION}',
									'{ANSWER}',
									'{ID}',
									'{ID_LO}',
									'{ID_HI}',
									'{DATE}',
									'{DATE_LO}',
									'{DATE_HI}',
									'{SORT_SELECT}'), $user_tags);

	$fld->mValues = array_merge(array(PL_FAQ_DESIGN_EDIT_TAB1,
									PL_FAQ_DESIGN_FORM_HTML_FORM_TAG,
									PL_FAQ_DESIGN_FORM_AUTHOR_FIELD,
									PL_FAQ_DESIGN_FORM_EMAIL_FIELD,
									PL_FAQ_DESIGN_FORM_PHONE_FIELD,
									PL_FAQ_DESIGN_FORM_QUESTION_FIELD,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_ANSWER_FIELD_TAG,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_FIELD_TAG,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_LO_FIELD_TAG,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_ID_HI_FIELD_TAG,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_FIELD_TAG,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_LO_FIELD_TAG,
									PL_FAQ_DESIGN_FILTER_FORM_EDIT_DATE_HI_FIELD_TAG,
									PL_FAQ_DESIGN_FORM_SORT_SELECT_TAG_VALUE), $user_tags_values);

	$layout->addField(PL_FAQ_DESIGN_EDIT_FORM_TAB3 , $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '')
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_faq', 'design_edit') ? '' : 'disabled="disabled"'));

	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fFaq_Design_Filter_Form_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_faq_filter'))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';

	$sftf_title = $sftf_form = '';
	$sftf_lang = SB_CMS_LANG;
	$sftf_fields_temps = array();

	extract($_POST);

	if ($sftf_title == '')
	{
		sb_show_message(PL_FAQ_DESIGN_NO_TITLE_MSG, false, 'warning');
		fFag_Design_Filter_Form_Edit();
		return;
    }

	$row = array();
    $row['sftf_title'] = $sftf_title;
    $row['sftf_lang'] = $sftf_lang;
    $row['sftf_form'] = $sftf_form;
    $row['sftf_fields_temps'] = serialize($sftf_fields_temps);

	if ($_GET['id'] != '')
	{
		$res = sql_param_query('SELECT sftf_title FROM sb_faq_temps_form WHERE sftf_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

			sql_param_query('UPDATE sb_faq_temps_form SET ?a WHERE sftf_id=?d', $row, $_GET['id'], sprintf(PL_FAQ_DESIGN_FILTER_FORM_SUBMIT_EDIT_OK, $old_title));

			$footer_ar = fCategs_Edit_Elem('design_edit');
			if (!$footer_ar)
			{
				sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_FAQ_DESIGN_FILTER_FORM_SUBMIT_ERR_EDIT, $old_title), SB_MSG_WARNING);

				fFag_Design_Filter_Form_Edit();
				return;
			}

			$footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sftf_id'] = intval($_GET['id']);
            $html_str = fFaq_Design_Filter_Form_Get($row);

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
				fFag_Design_Filter_Form_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_FAQ_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_FAQ_DESIGN_FILTER_FORM_SUBMIT_ERR_EDIT, $sftf_title), SB_MSG_WARNING);

            fFag_Design_Filter_Form_Edit();
            return;
        }
    }
    else
    {
		$error = true;
		if (sql_param_query('INSERT INTO sb_faq_temps_form SET ?a', $row))
		{
			$id = sql_insert_id();

			if (fCategs_Add_Elem($id, 'design_edit'))
            {
				sb_add_system_message(sprintf(PL_FAQ_DESIGN_FILTER_FORM_SUBMIT_ADD_OK, $sftf_title));
				echo '<script>
                        sbReturnValue('.$id.');
					</script>';
				$error = false;
			}
			else
			{
				sql_query('DELETE FROM sb_faq_temps_form WHERE sftf_id="'.$id.'"');
			}
		}

		if ($error)
		{
			sb_show_message(sprintf(PL_FAQ_DESIGN_FILTER_FORM_SUBMIT_ADD_ERROR, $sftf_title), false, 'warning');
			sb_add_system_message(sprintf(PL_FAQ_DESIGN_FILTER_FORM_SUBMIT_ADD_SYS_ERROR, $sftf_title), SB_MSG_WARNING);

			fFag_Design_Filter_Form_Edit();
			return;
		}
	}
}

function fFaq_Design_Filter_Form_Delete()
{
	$id = intval($_GET['id']);
	$pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_filter" AND pages.p_id=elems.e_p_id LIMIT 1');
	$temps = false;
	if (!$pages)
	{
		$temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_faq_filter" AND temps.t_id=elems.e_p_id LIMIT 1');
	}

	if ($pages || $temps)
	{
		echo PL_FAQ_DESIGN_DELETE_ERROR;
	}
}

?>