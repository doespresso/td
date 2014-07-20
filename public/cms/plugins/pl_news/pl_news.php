<?php

/**
 * Функции управления новостной лентой
 */
function fNews_Get($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);" id="n_title_'.$args['n_id'].'">'.strip_tags(isset($args['n_title']) ? $args['n_title'] : '').'</a></b>
		<div class="smalltext" style="margin-top: 7px;">';

	if (isset($args['n_id']))
	{
		$result .= PL_NEWS_DESIGN_EDIT_ID_TAG.': <span style="color: #33805E;">'.$args['n_id'].'</span><br />';
	}

	if (isset($args['n_user_id']) && !is_null($args['n_user_id']) && $args['n_user_id'] > 0)
	{
		$res_str = fSite_Users_Get_User_Link($args['n_user_id']);
		$result .= PL_NEWS_GET_SITE_USER.': '.($res_str != '' ? $res_str : '<br />');
	}

    $result .= ((isset($args['n_date']) && $args['n_date'] != 0) ? PL_NEWS_GET_DATE.': <span style="color: #33805E;">'.sb_date('d.m.Y H:i', $args['n_date']).'</span><br />' : '')
    .PL_NEWS_GET_SORT.': <span style="color: #33805E;">'.(isset($args['n_sort']) ? $args['n_sort'] : '').'</span>';

    $result .= fComments_Get_Count_Get($args['n_id'], 'pl_news');
    $result .= fVoting_Rating_Get($args['n_id'], 'pl_news');

    sb_get_workflow_status($result, 'pl_news', (isset($args['n_active']) ? $args['n_active'] : 0), (isset($args['n_pub_start']) ? $args['n_pub_start'] : 0), (isset($args['n_pub_end']) ? $args['n_pub_end'] : 0));

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $result .= sbLayout::getPluginFieldsInfo('pl_news', $args);
    $result .= '</div>';

    return $result;
}

function fNews_Init(&$elems = '', $external = false)
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_news', 'n_id', 'n_title', 'fNews_Get', 'pl_news_init', 'pl_news', 'n_url');
    $elems->mCategsRootName = PL_NEWS_ROOT_NAME;

	$elems->addField('n_date');       // дата новости
	$elems->addField('n_active');     // новость активна или нет
	$elems->addField('n_pub_start');  // дата начала публикации
	$elems->addField('n_pub_end');    // дата окончания публикации
	$elems->addField('n_sort');       // индекс сортировки
	$elems->addField('n_user_id');    // индекс сортировки

	$elems->addCategsClosedDescr('read',          PL_NEWS_GROUP_READ);
	$elems->addCategsClosedDescr('edit',          PL_NEWS_GROUP_EDIT);
	$elems->addCategsClosedDescr('comments_read', PL_NEWS_GROUP_COMMENTS_READ);
	$elems->addCategsClosedDescr('comments_edit', PL_NEWS_GROUP_COMMENTS_EDIT);
	$elems->addCategsClosedDescr('vote',          PL_NEWS_GROUP_VOTE);

	$elems->addFilter(PL_NEWS_DESIGN_EDIT_ID_TAG, 'n_id',    'number');
	$elems->addFilter(PL_NEWS_TITLE,              'n_title', 'string');
	$elems->addFilter(PL_NEWS_DATE,               'n_date',  'date');

	sb_add_workflow_filter($elems, 'pl_news', 'n_active');

    $elems->addFilter(PL_NEWS_SORT,      'n_sort',      'number');
    $elems->addFilter(PL_NEWS_PUB_START, 'n_pub_start', 'date');
    $elems->addFilter(PL_NEWS_PUB_END,   'n_pub_end',   'date');

    $elems->addSorting(PL_NEWS_SORT_BY_ID,		  'n_id');
    $elems->addSorting(PL_NEWS_SORT_BY_TITLE,     'n_title');
    $elems->addSorting(PL_NEWS_SORT_BY_DATE,      'n_date');
    $elems->addSorting(PL_NEWS_SORT_BY_ACTIVE,    'n_active');
    $elems->addSorting(PL_NEWS_SORT_BY_SORT,      'n_sort');
    $elems->addSorting(PL_NEWS_SORT_BY_PUB_START, 'n_pub_start');
	$elems->addSorting(PL_NEWS_SORT_BY_PUB_END,   'n_pub_end');

	$elems->mCategsDeleteWithElementsMenuTitle = PL_NEWS_CATEGS_DELETE_WITH_ELEMENTS_MENU_TITLE;
    $elems->mCategsPasteWithElementsMenuTitle = PL_NEWS_CATEGS_PASTE_WITH_ELEMENTS_MENU_TITLE;
    $elems->mCategsPasteLinksMenuTitle = PL_NEWS_CATEGS_PASTE_LINKS_MENU_TITLE;
    $elems->mCategsPasteElemsMenuTitle = PL_NEWS_CATEGS_PASTE_ELEMS_MENU_TITLE;
    $elems->mElemsAddMenuTitle  = PL_NEWS_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = PL_NEWS_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = PL_NEWS_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = PL_NEWS_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent     = 'pl_news_edit';
    $elems->mElemsEditDlgWidth  = 800;
	$elems->mElemsEditDlgHeight = 700;

	$elems->mElemsAddEvent     = 'pl_news_edit';
    $elems->mElemsAddDlgWidth  = 800;
	$elems->mElemsAddDlgHeight = 700;

	$elems->mElemsDeleteEvent     = 'pl_news_delete';
	$elems->mCategsDeleteWithElementsMenu = true;
	$elems->mCategsAfterDeleteWithElementsEvent = 'pl_news_delete_cat_with_elements';

	$elems->mElemsAfterPasteEvent = 'pl_news_paste';
	$elems->mCategsPasteWithElementsMenu = true;
	$elems->mCategsAfterPasteWithElementsEvent = 'pl_news_after_paste_with_elements';

	$elems->mCategsModerators = true;
	$elems->mCategsUrl        = true;
	$elems->mCategsFields     = true;
	$elems->mElemsUseLinks    = true;

	$elems->mElemsJavascriptStr = '
		var timer = "";
   	    function showComments(n_id)
        {
            if (typeof(n_id) == "undefined")
            {
                n_id = sbSelEl.getAttribute("el_id");
            }

            var n_title = sbGetE("n_title_" + n_id);

            if (n_title)
            {
            	if (timer != "undefined" && timer != "")
            	{
					clearInterval(timer);
				}

                sbShowCommentaryWindow("pl_news", n_id, n_title.innerHTML);
            }
        }';

	if ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'elems_public') && (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_news')))
	{
		$elems->mElemsJavascriptStr .= '
            function newsSetActive()
            {
            	var ids = "0";

                for (var i = 0; i < sbSelectedEls.length; i++)
			    {
			        var el = sbGetE("el_" + sbSelectedEls[i]);
			        if (el)
			        {
			            ids += "," + el.getAttribute("el_id");
			        }
			    }

                var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_news_set_active&ids=" + ids);
                if (res != "TRUE")
                {
                    alert("'.PL_NEWS_SHOWHIDE_ERROR.'");
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

        $elems->addElemsMenuItem(PL_NEWS_SHOWHIDE_MENU, 'newsSetActive();');
	}

	if (isset($_GET['sb_sel_id']) && $_GET['sb_sel_id'] != '' && isset($_GET['show_comments']))
	{
		$elems->mFooterStr = '
			<script>
				var timer = setInterval("showComments('.$_GET['sb_sel_id'].')", 300);
			</script>';
	}

	$elems->addElemsMenuItem(PL_NEWS_EDIT_SHOW_COMMENT, 'showComments()', true);

	$elems->mElemsJavascriptStr .= '
		function sbNewsExport(c)
		{
			if (c && sbSelCat)
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
						else if (els[i].nodeName == "SELECT" && els[i].multiple)
						{
							var str = "";
							for(var j = 0; j < els[i].length; j++)
							{
								if (els[i][j].selected)
								{
									str += els[i][j].value+",";
								}
							}
							str = str.slice(0, -1);

							args[els[i].name] = {};
							args[els[i].name]["value"]= str;
							args[els[i].name]["type"]= "multyselect";
						}
						else if (els[i].nodeName == "SELECT")
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

			var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_edit&ident=pl_news&cat_id="+ cat_id;
			var strAttr = "resizable=1,width=700,height=600";
			sbShowModalDialog(strPage, strAttr, null, args);
		}

		function sbNewsImport(c)
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

			var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_import_edit&ident=pl_news&cat_id="+ cat_id;
			var strAttr = "resizable=1,width=650,height=370";
			sbShowModalDialog(strPage, strAttr, sbAfterNewsImport, args);
		}

		function sbAfterNewsImport()
		{
			window.location.href = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
		}';

	$elems->addElemsMenuItem(PL_NEWS_EDIT_EXPORT, 'sbNewsExport()', false);
	//$elems->addElemsMenuItem(PL_NEWS_EDIT_IMPORT, 'sbNewsImport()', false);

	$elems->addCategsMenuItem(PL_NEWS_EDIT_EXPORT, 'sbNewsExport(true)');
	//$elems->addCategsMenuItem(PL_NEWS_EDIT_IMPORT, 'sbNewsImport(true)');

	if (!$external)
	{
		$elems->init();
	}
}

function fNews_Edit($htmlStr = '', $footerStr = '', $footerLinkStr = '')
{
	//	определяем групповое редактирование или нет.
	$edit_group = sbIsGroupEdit();

	if ($edit_group)
	{
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_news'))
			return;
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news'))
	{
		return;
	}

	$edit_rights = $_SESSION['sbPlugins']->isRightAvailable('pl_news', 'elems_edit');
	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '' && !$edit_group)
	{
		$result = sql_query('SELECT n_title, n_date, n_short, n_short_foto, n_full, n_full_foto, n_pub_start, n_pub_end, n_active, n_url, n_sort, n_ml_ids , n_ml_ids_sent, n_user_id
   		                     FROM sb_news WHERE n_id=?d', $_GET['id']);

   		if ($result)
	    {
			list($n_title, $n_date, $n_short, $n_short_foto, $n_full, $n_full_foto, $n_pub_start, $n_pub_end, $n_active, $n_url, $n_sort, $n_ml_ids, $n_ml_ids_sent, $n_user_id) = $result[0];
		}
		else
	    {
			sb_show_message(PL_NEWS_EDIT_ERROR, true, 'warning');
			return;
	    }

	    if ($n_date != 0)
	    {
		    $n_date = sb_date('d.m.Y H:i', $n_date);
	    }
		else
		{
		    $n_date = '';
		}

        if (!is_null($n_pub_start) && $n_pub_start != 0 && $n_pub_start != '')
        {
		    $n_pub_start = sb_date('d.m.Y H:i', $n_pub_start);
        }
		else
		{
		    $n_pub_start = '';
		}

        if (!is_null($n_pub_end) && $n_pub_end != 0 && $n_pub_end != '')
        {
		    $n_pub_end = sb_date('d.m.Y H:i', $n_pub_end);
        }
		else
		{
		    $n_pub_end = '';
		}

		$tw_checked = '';
		$fb_checked = '';
		$lj_checked = '';
	}
	elseif (count($_POST) > 0)
	{
		$n_active = 0;
		$n_pub_start = '';
    	$n_pub_end = '';
		$n_ml_ids = array();

		$send_twitter_msg = '0';
		$send_facebook_msg = '0';
		$send_lj_msg = '0';

		$n_user_id = null;

		extract($_POST);

		if ($htmlStr != '')
		{
			// Применить
			$tw_checked = '';
			$fb_checked = '';
			$lj_checked = '';
		}
		else
		{
	    	if (intval($send_twitter_msg) > 0)
	    	{
				$tw_checked = 'checked="checked"';
	    	}
	    	else
	    	{
	    		$tw_checked = '';
	    	}

	    	if (intval($send_facebook_msg) > 0)
	    	{
				$fb_checked = 'checked="checked"';
	    	}
	    	else
	    	{
	    		$fb_checked = '';
	    	}

	    	if (intval($send_lj_msg) > 0)
	    	{
				$lj_checked = 'checked="checked"';
	    	}
	    	else
	    	{
	    		$lj_checked = '';
	    	}
		}

    	$maillist_str = '';
		$new_n_ml_ids_sent = '';
	    if(count($n_ml_ids) > 0)
	    {
			foreach($n_ml_ids as $value)
			{
				$maillist_str .= $value.'|';
				if(strpos($n_ml_ids_sent, $value.'|') !== false)
				{
					$new_n_ml_ids_sent .= $value.'|';
				}
			}
	    }

	    $n_ml_ids_sent = $new_n_ml_ids_sent;
		$n_ml_ids = $maillist_str;

    	if (!isset($_GET['id']))
    	{
            $_GET['id'] = '';
    	}
	}
	else
	{
		$tw_checked = 'checked="checked"';
		$fb_checked = 'checked="checked"';
		$lj_checked = 'checked="checked"';

	    $n_title = $n_short = $n_short_foto = $n_full = $n_full_foto = $n_pub_start = $n_pub_end = $n_url = '';
	    $n_date = sb_date('d.m.Y H:i');
	    $n_active = 1;
	    $n_ml_ids_sent = $n_ml_ids = '';
		$n_user_id = null;
		$res = sql_query('SELECT MAX(n_sort) FROM sb_news');

		if ($res)
	    {
	        list($n_sort) = $res[0];
	        $n_sort += 10;
	    }
	    else
	    {
	        $n_sort = 0;
	    }

		$_GET['id'] = '';
	}

	if (!$edit_group)
	{
		// Twitter
		$use_twitter = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_use');

		$consumer_key = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_consumer_key'));
		$consumer_secret = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_consumer_secret'));
		$access_token = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_access_token'));
		$token_secret = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_access_token_secret'));

		$tw_exists_access = ($consumer_key != '' && $consumer_secret != '' && $access_token != '' && $token_secret != '');


		// Facebook
		$use_facebook = $_SESSION['sbPlugins']->getSetting('sb_news_facebook_use');

		$fb_application_id = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_application_id'));
		$fb_application_secret = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_application_secret'));
		$fb_profile_id = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_profile_id'));

		$fb_exists_access = ($fb_application_id != '' && $fb_application_secret != '' && $fb_profile_id != '');
		$fb_exists_perm = false;

		if ($fb_exists_access)
		{
			$fb_exists_perm = fNews_Facebook_Is_Permission();
		}

		//LiveJournal
		$use_lj = $_SESSION['sbPlugins']->getSetting('sb_news_lj_use');

		$lj_login = trim($_SESSION['sbPlugins']->getSetting('sb_news_lj_login'));
		$lj_password = trim($_SESSION['sbPlugins']->getSetting('sb_news_lj_password'));

		$lj_exists_access = ($lj_login != '' && $lj_password != '');

		if ($lj_exists_access)
		{
			$lj_exists_access = fNews_LJ_Is_Permission($lj_login, $lj_password);
		}

		$bit_ly_login = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_bit_ly_login');
		$bit_ly_key = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_bit_ly_key');

		$bit_ly_need = false;

		if ($bit_ly_login != '' && $bit_ly_key != '' && (($tw_exists_access && !is_null($use_twitter) && $use_twitter == '0') || ($fb_exists_access && !is_null($use_facebook) && $use_facebook == 0)))
		{
			echo '<script src="http://bit.ly/javascript-api.js?version=latest&login='.$bit_ly_login.'&apiKey='.$bit_ly_key.'"></script>
			<script>
				var arr_index = 0;
	            var long_urls = new Array();
	            BitlyCB.id = "";

				function shortenUrl(e, id)
				{
					if (e != "blur")
					{
						var keystroke = e.keyCode || e.which;

						if (keystroke != 32)
						{
							return;
						}
					}

					BitlyCB.id = id;
					arr_index = 0;
					var el = sbGetE(id);

					var reg_exp = /(?:https?|ftp):\/\/(?!bit\.ly)[\w\d:;#@%\/$()~_?\+\=\\\.\-&]*/gi;
	                var arr = el.value.match(reg_exp);

	                if (!arr)
	                {
	                	return;
	                }

	                for (var i = 0; i < arr.length; i++)
	                {
	                    long_urls[i] = arr[i];
	                    BitlyClient.shorten(arr[i], "BitlyCB.shortenResponse");
	                }

	                var tw_msg = sbGetE(id);

	                if (tw_msg)
	                {
						sbCharCount(tw_msg, "twitter_msg", "140");
					}
	            }

				function replace(search, replace, subject)
				{
					var ra = replace instanceof Array;
					var l = (search = [].concat(search)).length
					var replace = [].concat(replace);
					var i = (subject = [].concat(subject)).length;

					while(j = 0, i--)
					{
						while(subject[i] = subject[i].split(search[j]).join(ra ? replace[j] || "" : replace[0]), ++j < l );
					}
					return subject[0];
				}

				BitlyCB.shortenResponse = function(data)
				{
	            	var str = "";
	                var first_result;

	                for (var r in data.results)
	                {
						first_result = data.results[r];
	                    break;
					}

					for (var key in first_result)
					{
	                	if (key == "shortUrl")
	                    {
							var el = sbGetE(BitlyCB.id);
							el.value = replace([long_urls[arr_index]], first_result[key].toString(), el.value);

							arr_index++;
							break;
						}
					}
				}
			</script>';

			$bit_ly_need = true;
		}
	}

	echo '<script>
	        function checkValues()
            {
                var el_title = sbGetE("n_title");
				'.($edit_group ? '
            	var ch_t = sbGetE("ch_n_title");
            	var ch_d = sbGetE("ch_n_date");
				' : '').'

                if (el_title.value == "" '.($edit_group ? ' && ch_t.checked' : '').')
                {
          	         alert("'.PL_NEWS_EDIT_NO_TITLE_MSG.'");
          	         return false;
                }

                var el_date = sbGetE("n_date");

                if (el_date.value == "" '.($edit_group ? ' && ch_d.checked' : '').')
                {
                    alert("'.PL_NEWS_EDIT_NO_DATE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_news_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main', 'enctype="multipart/form-data"');
	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_NEWS_EDIT_TAB1);
	$layout->addHeader(PL_NEWS_EDIT_TAB1);

	$layout->addField(PL_NEWS_EDIT_TITLE.sbGetGroupEditCheckbox('n_title', $edit_group), new sbLayoutInput('text', $n_title, 'n_title', '', 'maxlength="255" style="width:440px;"', true), 'id="n_title_th"', 'id="n_title_td"', 'id="n_title_tr"');

	if (!$edit_group)
	{
		if ($use_twitter == '3' && $tw_exists_access)
		{
			$layout->addField(PL_NEWS_EDIT_TWITTER_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_twitter, 'send_twitter_msg', '', $tw_checked));
		}

		if ($use_facebook == '3' && $fb_exists_access && $fb_exists_perm)
		{
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_facebook, 'send_facebook_msg', '', $fb_checked));
		}
		else if ($use_facebook == '3' && $fb_exists_access && !$fb_exists_perm)
		{
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_SEND_FIELD_TITLE, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_NEWS_EDIT_FACEBOOK_FILED_PERMISSIONS, $fb_application_id, SB_CMS_EMPTY_FILE.'?event=pl_news_fb_get_access_token').'</div>', '', '', false));
		}

		if ($use_lj == '3' && $lj_exists_access)
		{
			$layout->addField(PL_NEWS_EDIT_LJ_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_lj, 'send_lj_msg', '', $lj_checked));
		}
	}

	$fld = new sbLayoutDate($n_date, 'n_date');
	$fld->mDropButton = true;
	$layout->addField(PL_NEWS_EDIT_DATE.sbGetGroupEditCheckbox('n_date', $edit_group), $fld, 'id="n_date_th"', 'id="n_date_td"', 'id="n_date_tr"');

	$layout->addField('', new sbLayoutDelim(), 'id="n_date_del_th"', 'id="n_date_del_td"', 'id="n_date_del_tr"');

	if (!$edit_group)
	{
		$layout->addField(KERNEL_STATIC_URL, new sbLayoutInput('text', $n_url, 'n_url', '', 'style="width:440px;"'), 'id="n_url_th"', 'id="n_url_td"', 'id="n_url_tr"');
		$layout->addField('', new sbLayoutLabel('<div class="hint_div">'.KERNEL_STATIC_URL_HINT.'</div>', '', '', false), 'id="n_url_hint_th"', 'id="n_url_hint_td"', 'id="n_url_hint_tr"');
	}

	fClouds_Get_Field($layout, 'n_tags', ($edit_group ? $_GET['ids'] : $_GET['id']), 'pl_news', '440px', $edit_group);

	$layout->addField(PL_NEWS_EDIT_SORT.sbGetGroupEditCheckbox('n_sort', $edit_group), new sbLayoutInput('text', $n_sort, 'n_sort', 'spin_n_sort', 'style="width:80px;"'), 'id="n_sort_th"', 'id="n_sort_td"', 'id="n_sort_tr"');

	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_maillist'))
	{
		$res = sql_query('SELECT m.m_id, m.m_name, c.cat_id FROM sb_categs c, sb_catlinks l, sb_maillist m WHERE m.m_use_default = 1 AND m.m_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident="pl_maillist" ORDER BY m.m_name');
		$html = '';
		if($res)
	    {
			foreach ($res as $value)
	        {
				list($m_id, $m_name, $cat_id) = $value;
				if(!fCategs_Check_Rights($cat_id))
				{
					continue;
				}

				$fld = new sbLayoutInput('checkbox', $m_id, 'n_ml_ids['.$m_id.']', '', (strpos('|'.$n_ml_ids, '|'.$m_id.'|') !== false ? 'checked="checked"' : ''));
				$html .= $fld->getField().' <label for="n_ml_ids['.$m_id.']">'.$m_name.' '.($n_ml_ids_sent != '' && strpos('|'.$n_ml_ids_sent, '|'.$m_id.'|') !== false ? '(<span style="font-sixe:12px; color:#33805E;">'.PL_NEWS_EDIT_SUCCESS_SENT.'</span>)' : '').'</label> <br />';
	        }
	    }

	    if ($html == '')
	    {
			$fld = new sbLayoutLabel('<div class="hint_div">'.PL_NEWS_H_ELEM_NO_MAILLIST_TEMPS_MSG.'</div>', '', '', false);
			$html .= $fld->getField();
		}

		$fld = new sbLayoutHTML($html);

		if ($edit_group)
		{
			$fld->mShowColon = false;
		}

		$layout->addField(PL_NEWS_H_ELEM_ADD_NEWS_MAILLIST.sbGetGroupEditCheckbox('n_maillist', $edit_group), $fld, 'id="n_ml_ids_th"', 'id="n_ml_ids_td"', 'id="n_ml_ids_tr"');
		$layout->addField('', new sbLayoutInput('hidden', $n_ml_ids_sent, 'n_ml_ids_sent'));
	}

	if (!$edit_group)
	{
		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_news', $_GET['id'], 'n_active', $n_active, 'n_pub_start', $n_pub_start, 'n_pub_end', $n_pub_end);
	}
	else
	{
		$states_arr = array();
		$states = sql_query('SELECT n_active FROM sb_news WHERE n_id IN (?a)', $_GET['ids']);

		if ($states)
		{
			foreach($states as $val)
			{
				$states_arr[] = $val[0];
			}
		}

		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_news', $_GET['ids'], 'n_active', $states_arr, 'n_pub_start', $n_pub_start, 'n_pub_end', $n_pub_end);
	}

	$layout->addTab(PL_NEWS_EDIT_TAB2);
	$layout->addHeader(PL_NEWS_EDIT_TAB2);

	$fld = new sbLayoutTextarea($n_short, 'n_short', 'n_short', 'style="width:100%;height:300px;"');
	$fld->mShowEditorBtn = true;
	$layout->addField(PL_NEWS_EDIT_ANONS.sbGetGroupEditCheckbox('n_short', $edit_group), $fld, 'id="n_short_th"', 'id="n_short_td"', 'id="n_short_tr"');

	if (!$edit_group)
	{
		if ($use_twitter == '1' && $tw_exists_access)
		{
			$layout->addField(PL_NEWS_EDIT_TWITTER_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_twitter, 'send_twitter_msg', '', $tw_checked));
		}
		else if (!is_null($use_twitter) && $use_twitter == '0' && $tw_exists_access)
		{
			$fld = new sbLayoutInput('input', '', 'twitter_msg', 'twitter_msg', 'style="width:100%;" '.($bit_ly_need ? 'onblur="shortenUrl(\'blur\', \'twitter_msg\');" onkeydown="shortenUrl(event,\'twitter_msg\');"' : ''));
			$fld->mShowCharCount = true;
			$fld->mMaxValue = '140';
			$layout->addField(PL_NEWS_EDIT_TWITTER_FILED_TITLE, $fld);
		}

		if ($fb_exists_access && $use_facebook == '1' && $fb_exists_perm)
		{
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_facebook, 'send_facebook_msg', '', $fb_checked));
		}
		else if ($fb_exists_access && !is_null($use_facebook) && $use_facebook == '0' && $fb_exists_perm)
		{
			$fld = new sbLayoutTextarea('', 'facebook_msg', 'facebook_msg', 'style="width:100%;height:100px;" '.($bit_ly_need ? 'onblur="shortenUrl(\'blur\',\'facebook_msg\');" onkeydown="shortenUrl(event,\'facebook_msg\');"' : ''));
			$fld->mShowCharCount = true;
			$fld->mMaxValue = '420';
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_FILED_TITLE, $fld);

            $include_photo = array(
                'none' => PL_NEWS_EDIT_FACEBOOK_IMAGE_NONE,
                'short' => PL_NEWS_EDIT_FACEBOOK_IMAGE_SHORT,
                'big' => PL_NEWS_EDIT_FACEBOOK_IMAGE_BIG
            );
            $fld = new sbLayoutSelect($include_photo, 'sb_facebook_photo');
            $layout->addField(PL_NEWS_EDIT_H_FACEBOOK_IMAGE, $fld);
		}
		else if ($fb_exists_access && ((!is_null($use_facebook) && $use_facebook == '0') || $use_facebook == '1') && !$fb_exists_perm)
		{
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_SEND_FIELD_TITLE, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_NEWS_EDIT_FACEBOOK_FILED_PERMISSIONS, $fb_application_id, SB_CMS_EMPTY_FILE.'?event=pl_news_fb_get_access_token').'</div>', '', '', false));
		}

		if ($lj_exists_access && $use_lj == '1')
		{
			$layout->addField(PL_NEWS_EDIT_LJ_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_lj, 'send_lj_msg', '', $lj_checked));
		}
		else if (!is_null($use_lj) && $use_lj == '0' && $lj_exists_access)
		{
			$fld = new sbLayoutTextarea('', 'lj_msg', 'lj_msg', 'style="width:100%;height:100px;"');
			$fld->mShowEditorBtn = true;
			$layout->addField(PL_NEWS_EDIT_LJ_FILED_TITLE, $fld);
		}
	}

	$layout->addField('', new sbLayoutDelim(), 'id="n_short_del_th"', 'id="n_short_del_td"', 'id="n_short_del_tr"');

	$layout->addField(PL_NEWS_EDIT_ANONS_FOTO.sbGetGroupEditCheckbox('n_short_foto', $edit_group), new sbLayoutImage($n_short_foto, 'n_short_foto', '', 'style="width:350px;"'), 'id="n_short_foto_th"', 'id="n_short_foto_td"', 'id="n_short_foto_tr"');

	$layout->addTab(PL_NEWS_EDIT_TAB3);
	$layout->addHeader(PL_NEWS_EDIT_TAB3);

	$fld = new sbLayoutTextarea($n_full, 'n_full', '', 'style="width:100%;height:300px;"');
	$fld->mShowEditorBtn = true;
	$layout->addField(PL_NEWS_EDIT_FULL.sbGetGroupEditCheckbox('n_full', $edit_group), $fld, 'id="n_full_th"', 'id="n_full_td"', 'id="n_full_tr"');

	if (!$edit_group)
	{
		if ($use_twitter == '2' && $tw_exists_access)
		{
			$layout->addField(PL_NEWS_EDIT_TWITTER_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_twitter, 'send_twitter_msg', '', $tw_checked));
		}

		if ($fb_exists_access && $use_facebook == '2' && $fb_exists_perm)
		{
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_facebook, 'send_facebook_msg', '', $fb_checked));
		}
		else if ($fb_exists_access && $use_facebook == '2' && !$fb_exists_perm)
		{
			$layout->addField(PL_NEWS_EDIT_FACEBOOK_SEND_FIELD_TITLE, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_NEWS_EDIT_FACEBOOK_FILED_PERMISSIONS, $fb_application_id, SB_CMS_EMPTY_FILE.'?event=pl_news_fb_get_access_token').'</div>', '', '', false));
		}

		if ($use_lj == '2' && $lj_exists_access)
		{
			$layout->addField(PL_NEWS_EDIT_LJ_SEND_FIELD_TITLE, new sbLayoutInput('checkbox', $use_lj, 'send_lj_msg', '', $lj_checked));
		}
	}

	$layout->addField('', new sbLayoutDelim(), 'id="n_full_del_th"', 'id="n_full_del_td"', 'id="n_full_del_tr"');

	$layout->addField(PL_NEWS_EDIT_FULL_FOTO.sbGetGroupEditCheckbox('n_full_foto', $edit_group), new sbLayoutImage($n_full_foto, 'n_full_foto', '', 'style="width:350px;"'), 'id="n_full_foto_th"', 'id="n_full_foto_td"', 'id="n_full_foto_tr"');

	if (!$edit_group)
	{
		$layout->getPluginFields('pl_news', $_GET['id'], 'n_id');
	}
	else
	{
		$layout->getPluginFields('pl_news', '', 'n_id', false, $edit_group);
	}

	if (!$edit_group && !is_null($n_user_id) && $n_user_id > 0)
	{
		$layout->addField('', new sbLayoutInput('hidden', $n_user_id, 'n_user_id'));
		fSite_Users_Get_Author_Tab($layout, $n_user_id);
	}

	if (!$edit_group)
	{
		fVoting_Rating_Edit($layout, $_GET['id'], 'pl_news');
	}

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($edit_rights ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '' && !$edit_group)
    {
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($edit_rights ? '' : 'disabled="disabled"'));
	}
	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' || $edit_group ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fNews_Edit_Submit()
{
	$edit_group = sbIsGroupEdit();

	//	проверка прав доступа
	if ($edit_group)
	{
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_news'))
			return;
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news'))
	{
		return;
	}

    if (!isset($_GET['id']))
    {
    	$_GET['id'] = '';
    }

	$twitter_msg = '';
	$facebook_msg = '';
	$lj_msg = '';

	$send_twitter_msg = '0';
	$send_facebook_msg = '0';
	$send_lj_msg = '0';

	$n_active = 0;
    $n_ml_ids = array();

    $ch_n_title = 0;

    $ch_n_title = $ch_n_date = $ch_clouds = $ch_n_sort = $ch_n_maillist = $ch_n_active = $ch_pub_start_end = $ch_n_short = $ch_n_short_foto =
    $ch_n_full = $ch_n_full_foto = $n_title = $n_date = $n_url = $n_tags = $n_sort = $n_ml_ids_sent = $n_pub_start = $n_pub_end = $n_short = $n_short_foto = $n_full =
    $n_full_foto = $v_sum = $tabidx = $btn_save = '';

    extract($_POST);

    if ((!$edit_group || $edit_group && $ch_n_title == 1) && $n_title == '')
    {
		sb_show_message(PL_NEWS_EDIT_NO_TITLE_MSG, false, 'warning');
		fNews_Edit();
		return;
	}

    if ((!$edit_group || $edit_group && $ch_n_date == 1) && $n_date == '')
    {
        sb_show_message(PL_NEWS_EDIT_NO_DATE_MSG, false, 'warning');
        fNews_Edit();
        return;
    }
    else
    {
		$n_date = sb_datetoint($n_date);
	}

    if (!$edit_group)
    {
		$n_url = sb_check_chpu($_GET['id'], $n_url, $n_title, 'sb_news', 'n_url', 'n_id');
		$_POST['n_url'] = $n_url;

	}

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $row = $layout->checkPluginFields('pl_news', ($edit_group ? '' : $_GET['id']), 'n_id', false, $edit_group);

	if ($row === false)
    {
        $layout->deletePluginFieldsFiles();
        fNews_Edit();
        return;
    }

	$new_n_ml_ids_sent = '';
	$maillist_str = '';

	sb_submit_workflow_status($row, 'n_active', 'n_pub_start', 'n_pub_end', $edit_group);

	if (!$edit_group)
	{
		$row['n_url'] = $n_url;
	}

	if (!$edit_group || $edit_group && $ch_n_title == 1)
	{
		$row['n_title'] = $n_title;
	}

	if (!$edit_group || $edit_group && $ch_n_date == 1)
	{
		$row['n_date'] = $n_date;
	}

    if (!$edit_group || $edit_group && $ch_n_sort == 1)
    {
    	$row['n_sort'] = $n_sort;
    }

    if (!$edit_group || $edit_group && $ch_n_short == 1)
    {
    	$row['n_short'] = $n_short;
    }

	if (!$edit_group || $edit_group && $ch_n_short_foto == 1)
	{
		$row['n_short_foto'] = $n_short_foto;
	}

	if (!$edit_group || $edit_group && $ch_n_full == 1)
	{
		$row['n_full'] = $n_full;
	}

	if (!$edit_group || $edit_group && $ch_n_full_foto == 1)
	{
		$row['n_full_foto'] = $n_full_foto;
	}

	if (!$edit_group || $edit_group && $ch_n_maillist == 1)
	{
	    if (count($n_ml_ids) > 0)
	    {
			foreach($n_ml_ids as $key => $value)
			{
				$maillist_str .= $value.'|';

				if (strpos($n_ml_ids_sent, $value.'|') !== false)
				{
					$new_n_ml_ids_sent .= $value.'|';
				}
			}
		}
		$row['n_ml_ids'] = $maillist_str;
		$row['n_ml_ids_sent'] = $new_n_ml_ids_sent;
	}

	if ($edit_group || $_GET['id'] != '')
	{
		if (!$edit_group)
		{
			$res = sql_query('SELECT n_title, n_user_id FROM sb_news WHERE n_id=?d', $_GET['id']);
		}
		else
		{
			//Обяъвляем в true просто для того чтобы пройти проверку ниже
			$res = true;
		}

	    if ($res)
	    {
			// редактирование
	        if (!$edit_group)
	        {
				list($old_title, $n_user_id) = $res[0];
   	    		sql_query('UPDATE sb_news SET ?a WHERE n_id=?d', $row, $_GET['id'], sprintf(PL_NEWS_EDIT_OK, $old_title));
	        }
	        else if(count($row) > 0)
	        {
        		sql_query('UPDATE sb_news SET ?a WHERE n_id IN (?a)', $row, $_GET['ids'], PL_NEWS_EDIT_GROUP_OK);
	        }

            if (!$edit_group)
            {
	            $footer_ar = fCategs_Edit_Elem();

	    	    if (!$footer_ar)
	    	    {
	    	        sb_show_message(PL_NEWS_EDIT_ERROR, false, 'warning');
	                sb_add_system_message(sprintf(PL_NEWS_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

	                $layout->deletePluginFieldsFiles();
	                fNews_Edit();
	    		    return;
	    	    }

	    	    $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
	    	    $footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

				$row['n_id'] = intval($_GET['id']);

	        	if (isset($v_sum))
	        	{
		    		fVoting_Rating_Edit_Submit($_GET['id'], 'pl_news');
	        	}

	    		$row['n_user_id'] = $n_user_id;

	            $html_str = fNews_Get($row);
	    	    $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
	            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);
            }

            if (!$edit_group || $edit_group && $ch_clouds == 1)
			{
   				fClouds_Set_Field(($edit_group ? $_GET['ids'] : $_GET['id']), 'pl_news', $n_tags, $edit_group);
			}

    		if (!isset($_POST['btn_apply']) && !$edit_group)
    	    {
        	    echo '<script>
        		        var res = new Object();
        		        res.html = "'.$html_str.'";
        		        res.footer = "'.$footer_str.'";
        		        res.footer_link = "'.$footer_link_str.'";
        				sbReturnValue(res);
        			  </script>';
    	    }
    	    else if (!$edit_group)
    	    {
				fNews_Edit($html_str, $footer_str, $footer_link_str);
    	    }
    		else
    		{
    			echo '<script>
						sbReturnValue("refresh");
					</script>';
			}
			sb_mail_workflow_status('pl_news', (!$edit_group ? $_GET['id'] : $_GET['ids']), $n_title, $n_active);
	    }
	    else
	    {
	        sb_show_message(PL_NEWS_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_EDIT_SYSTEMLOG_ERROR, $n_title), SB_MSG_WARNING);

            $layout->deletePluginFieldsFiles();
            fNews_Edit();
		    return;
	    }
	}
	else
	{
		$row['n_user_id'] = null;
	    $error = true;

	    foreach ($row as $key => $value)
	    {
	    	if ($value == '') unset($row[$key]);
	    }

	    if (sql_query('INSERT INTO sb_news (?#) VALUES (?a)', array_keys($row), array_values($row)))
	    {
    		$id = sql_insert_id();

    		if (fCategs_Add_Elem($id))
    		{
        		sb_add_system_message(sprintf(PL_NEWS_ADD_OK, $n_title));

        		echo '<script>
        				sbReturnValue('.$id.');
        			  </script>';

        		$error = false;
    		}
    		else
    		{
    		    sql_query('DELETE FROM sb_news WHERE n_id=?d', $id);
    		}
	    }

	    if ($error)
	    {
	        sb_show_message(PL_NEWS_ADD_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_ADD_SYSTEMLOG_ERROR, $n_title), SB_MSG_WARNING);

            $layout->deletePluginFieldsFiles();
            fNews_Edit();
		    return;
	    }
	    else
	    {
        	if (isset($v_sum))
        	{
    			fVoting_Rating_Edit_Submit($id, 'pl_news');
        	}

    		if (isset($n_tags))
    		{
    			fClouds_Set_Field($id, 'pl_news', $n_tags);
    		}

			sb_mail_workflow_status('pl_news', $id, $n_title, $n_active);
	    }
	}

	if (!$edit_group)
	{
		$twitter_msg = trim($twitter_msg);

		// Отправляем сообщение в twitter
		if (intval($send_twitter_msg) > 0 || $twitter_msg != '')
		{
			$tw_message = '';

			if ($n_short != '' && $send_twitter_msg == '1')
			{
				$tw_message = $n_short;
			}
			else if ($n_full != '' && $send_twitter_msg == '2')
			{
				$tw_message = $n_full;
			}
			else if ($n_title != '' && $send_twitter_msg == '3')
			{
				$tw_message = $n_title;
			}
			else if ($twitter_msg != '')
			{
				$tw_message = $twitter_msg;
			}

			fNews_Send_Tweet($tw_message, $_GET['cat_id'], ($_GET['id'] == '' ? $id : $_GET['id']), $n_url);
		}

		//Сообщение в facebook
		$facebook_msg = trim($facebook_msg);

		if (intval($send_facebook_msg) > 0 || $facebook_msg != '')
		{
			$fb_message = '';

			if ($n_short != '' && $send_facebook_msg == '1')
			{
				$fb_message = $n_short;
			}
			else if ($n_full != '' && $send_facebook_msg == '2')
			{
				$fb_message = $n_full;
			}
			else if ($n_title != '' && $send_facebook_msg == '3')
			{
				$fb_message = $n_title;
			}
			else if ($facebook_msg != '')
			{
				$fb_message = $facebook_msg;
			}

			fNews_Send_Facebook($fb_message, $_GET['cat_id'], ($_GET['id'] == '' ? $id : $_GET['id']), $n_url);
		}

		//Соощение в LiveJournal
		$lj_msg = trim($lj_msg);

		if (intval($send_lj_msg) > 0 || $lj_msg)
		{
			$lj_message = '';

			if ($n_short != '' && $send_lj_msg == '1')
			{
				$lj_message = $n_short;
			}
			else if ($n_full != '' && $send_lj_msg == '2')
			{
				$lj_message = $n_full;
			}
			else if ($n_title != '' && $send_lj_msg == '3')
			{
				$lj_message = $n_title;
			}
			else if ($lj_msg != '')
			{
				$lj_message = $lj_msg;
			}

			if ($send_lj_msg == '3')
			{
				fNews_Send_LJ($lj_message, '', $_GET['cat_id'], ($_GET['id'] == '' ? $id : $_GET['id']), $n_url);
			}
			else
			{
				fNews_Send_LJ($lj_message, $n_title, $_GET['cat_id'], ($_GET['id'] == '' ? $id : $_GET['id']), $n_url);
			}
		}
	}
}

function fNews_Set_Active()
{
	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_news', 'elems_public'))
	{
		return;
	}

	sbIsGroupEdit(false);

	$date = time();
	foreach ($_GET['ids'] as $val)
    {
       	sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $val, 'pl_news', $date, $_SESSION['sbAuth']->getUserId(), 'edit');
    }

    $res = sql_param_query('UPDATE sb_news SET n_active=IF(n_active=0,1,0) WHERE n_id IN (?a)', $_GET['ids'], PL_NEWS_SET_ACTIVE);
    if ($res)
    	echo 'TRUE';
}

function fNews_Delete()
{
    fVoting_Delete($_GET['id'], 'pl_news');
    fComments_Delete_Comment($_GET['id'], 'pl_news');
    fClouds_Delete($_GET['id'], 'pl_news');
}

function fNews_Delete_With_Elements()
{
	if(isset($_GET['elems_id']) && $_GET['elems_id'] != '')
	{
		$elems = explode(',',$_GET['elems_id']);
		fVoting_Delete($elems, 'pl_news');
    	fComments_Delete_Comment($elems, 'pl_news');
    	fClouds_Delete($elems, 'pl_news');
	}
}

function fNews_Paste()
{
	if (!isset($_GET['action']) || $_GET['action'] != 'copy' || !isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['ne']) || !is_array($_GET['ne']) || count($_GET['ne']) <= 0)
        return;

    $workflow = $_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow('pl_faq');
    $res = sql_query('SELECT n_id, n_title, n_active FROM sb_news WHERE n_id IN ('.implode(',', $_GET['ne']).')');

    $els = array();
    foreach ($_GET['e'] as $key => $value)
    {
        $els[intval($value)] = intval($_GET['ne'][$key]);
    }

	foreach ($res as $value)
    {
        list($n_id, $n_title, $n_active) = $value;

        $n_url = sb_check_chpu($n_id, '', $n_title, 'sb_news', 'n_url', 'n_id');

        if ($workflow)
        {
	       	if (!sb_workflow_status_available($n_active, 'pl_news', -1))
			{
				$n_active = current(sb_get_avail_workflow_status('pl_news'));
			}
        }

        sql_param_query('UPDATE sb_news SET n_url=?, n_active=?d WHERE n_id=?d', $n_url, $n_active, $n_id);
    }

    fClouds_Copy($els, 'pl_news');
}

function fNews_After_Paste_Categs_With_elements()
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

	fNews_Paste();
}

function fNews_Send_Tweet($msg, $cat_id, $n_id, $n_url)
{
	require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');
	$msg = sb_html_entity_decode(strip_tags(sbProgParseBBCodes(trim($msg), '', '', true)));

	$consumer_key = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_consumer_key'));
	$consumer_secret = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_consumer_secret'));
	$access_token = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_access_token'));
	$token_secret = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_access_token_secret'));

	if ($msg == '' || $consumer_key == '' || $consumer_secret == '' || $access_token == '' || $token_secret == '')
	{
		return;
	}

	$tw_url = trim($_SESSION['sbPlugins']->getSetting('sb_news_twitter_url'));

	if ($tw_url != '')
	{
		if (sb_strpos($tw_url, 'http') === false)
		{
			$tw_url = SB_DOMAIN.'/'.trim($tw_url, ' /');
		}

		list($tw_url, $more_ext) = sbGetMorePage($tw_url);

		if (sbPlugins::getSetting('sb_static_urls') == 1)
        {
			//	ЧПУ
			$cat_url = sql_query('SELECT cat_url FROM sb_categs WHERE cat_id = ?d', $cat_id);
			$tw_url .= (isset($cat_url[0][0]) && $cat_url[0][0] != '' ? urlencode($cat_url[0][0]).'/' : $cat_id.'/').
					($n_url != '' ? urlencode($n_url) : $n_id).($more_ext != 'php' ? '.'.$more_ext : '/');
		}
		else
		{
			$tw_url .= '?news_cid='.$cat_id.'&news_id='.$n_id;
		}
	}

	$login = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_bit_ly_login');
	$key = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_bit_ly_key');

	if ($login != '' && $key != '')
	{
		$matches = array();
		preg_match_all('/(?:https?|ftp):\/\/(?!bit\.ly)[\w\d:;#@%\/$()~_?\+\=\\\.\-&]*/si', $msg, $matches);

		$replacement = array();

		foreach($matches[0] as $k => $v)
		{
			$s_url = fNews_Get_Short_Url($v, $login, $key);
			if ($s_url != '')
			{
				$replacement[$k] = $s_url;
			}
			else
			{
				$replacement[$k] = $v;
			}
		}

		if (count($replacement) > 0)
		{
			$msg = sb_str_replace($matches[0], $replacement, $msg);
		}

		if ($tw_url != '')
		{
			$s_url = fNews_Get_Short_Url($tw_url, $login, $key);
			if ($s_url != '')
			{
				$tw_url = $s_url;
			}
		}
	}

	$m_len = sb_strlen($msg);

	if ($tw_url != '')
	{
		$u_len = sb_strlen($tw_url) + 1;
	}
	else
	{
		$u_len = 0;
	}

	if ($m_len + $u_len > 140)
	{
		if ($u_len > 0)
		{
			$msg = sb_substr($msg, 0, 140 - $u_len).' '.$tw_url;
		}
		else
		{
			$msg = sb_substr($msg, 0, 136).' ...';
		}
	}
	else if ($u_len > 0)
	{
		$msg .= ' '.$tw_url;
	}

	$params = array('status' => $msg);
	$url = 'https://api.twitter.com/1.1/statuses/update.json';

	$oauth['oauth_consumer_key'] = $consumer_key;
	$oauth['oauth_token'] = $access_token;
	$oauth['oauth_nonce'] = md5(uniqid(rand(), true));
	$oauth['oauth_timestamp'] = time();
	$oauth['oauth_signature_method'] = 'HMAC-SHA1';
	$oauth['oauth_version'] = '1.0';

	foreach($oauth as $k => $v)
	{
		$oauth[$k] = sb_encode_rfc3986($v);
	}

    $sigParams = array();
    if(is_array($params))
    {
		foreach($params as $k => $v)
		{
			if (strncmp('@', $k, 1) !== 0)
			{
				$sigParams[$k] = sb_encode_rfc3986($v);
				$params[$k] = sb_encode_rfc3986($v);
			}
		}
	}

	$sigParams = array_merge($oauth, $sigParams);

    ksort($sigParams);

	$retval = '';
	foreach($sigParams as $key => $value)
	{
		$retval .= $key.'='.$value.'&';
	}
	$retval = sb_substr($retval, 0, -1);

	$concatenatedParams = sb_encode_rfc3986($retval);

	$signatureBaseString = 'POST&'.sb_encode_rfc3986($url).'&'.$concatenatedParams;

	$key = sb_encode_rfc3986($consumer_secret).'&'.sb_encode_rfc3986($token_secret);
   	$retval = base64_encode(hash_hmac('sha1', $signatureBaseString, $key, true));

	$oauth['oauth_signature'] = sb_encode_rfc3986($retval);

	$params = array('request' => $params, 'oauth' => $oauth);

	$_h = array('Expect:');

	$urlParts = parse_url($url);
	$oauth = 'Authorization: OAuth realm="'.$urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'",';
	foreach($params['oauth'] as $name => $value)
	{
		$oauth .= $name.'="'.$value.'",';
	}

	$_h[] = sb_substr($oauth, 0, -1);
	$_h[] = 'User-Agent: '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

	if (function_exists('curl_init'))
	{
		$ch = curl_init($url);

		if ($ch !== false)
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $_h);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_POST, 1);

			$retval = '';
			foreach($params['request'] as $key => $value)
			{
				$retval .= $key.'='.$value.'&';
			}

			$retval = sb_substr($retval, 0, -1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $retval);
			$res = curl_exec($ch);

			if(curl_errno($ch) > 0)
			{
				sb_add_system_message(PL_NEWS_TWITTER_ADD_MSG_ERR, SB_MSG_WARNING);
				curl_close($ch);
				return;
			}

			curl_close($ch);
		}
	}
	elseif(function_exists('fsockopen'))
	{
		require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');
		$httpRequest = new sbSocket($url);
		$res = $httpRequest -> request('POST', array(), $params['request'], true, $_h);
	}
	else
	{
		sb_add_system_message(PL_NEWS_EDIT_CURL_ERROR, SB_MSG_WARNING);
	}

	if (!isset($res) || sb_strpos($res, 'error') !== false || $res == '')
	{
		sb_add_system_message(PL_NEWS_TWITTER_ADD_MSG_ERR, SB_MSG_WARNING);
	}
}

function fNews_Send_Facebook($msg, $cat_id, $n_id, $n_url)
{
	require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');

	$msg = sbProgParseBBCodes($msg);
	$msg = preg_replace('/(<[\/]?(?:p|ul|li|br|div|ol|hr|blockquote).*?>)/im'.SB_PREG_MOD, "\r\n", $msg);
	$msg = sb_str_replace("\r\n\r\n","\r\n",$msg);
	$msg = trim(sb_html_entity_decode(strip_tags($msg)));

	$appid = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_application_id'));
	$appsecret = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_application_secret'));
	$old_id = $uid = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_profile_id'));
	$pageid = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_page_id'));
	$access_token = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_access_token'));

	if ($msg == '' || $appid == '' || $appsecret == '' || $uid == '')
	{
		return;
	}

    if ($access_token == '')
    {
        sb_add_system_message(PL_NEWS_FACEBOOK_ADD_MSG_ERR, SB_MSG_WARNING);
        return;
    }

    //Если задана страница (не хроники), то получаем токен этой страницы
    if($pageid != '')
    {
        $url = 'https://graph.facebook.com/'.$uid.'/accounts/?access_token='.$access_token;
        if(function_exists('curl_init'))
        {
            $ch = curl_init($url);
            if($ch != false)
            {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);

                $res = curl_exec($ch);
                if(curl_errno($ch) <= 0)
                {
                    $res = json_decode($res);
                    if(isset($res->data))
                    {
                        foreach($res->data as $account)
                        {
                            if($account->name == $pageid)
                            {
                                $access_token = $account->access_token;
                                $uid = $account->id;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

	//Укорачиваю ссылки
	$login = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_bit_ly_login');
	$key = $_SESSION['sbPlugins']->getSetting('sb_news_twitter_bit_ly_key');

	if ($login != '' && $key != '')
	{
		$matches = array();
		preg_match_all('/(?:https?|ftp):\/\/(?!bit\.ly)[\w\d:;#@%\/$()~_?\+\=\\\.\-&]*/si', $msg, $matches);

		$replacement = array();
		foreach($matches[0] as $k => $v)
		{
			$s_url = fNews_Get_Short_Url($v, $login, $key);
			if ($s_url != '')
			{
				$replacement[$k] = $s_url;
			}
			else
			{
				$replacement[$k] = $v;
			}
		}

		if (count($replacement) > 0)
		{
			$msg = sb_str_replace($matches[0], $replacement, $msg);
		}
	}

	$fb_url = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_url'));

	if ($fb_url != '')
	{
		if (sb_strpos($fb_url, 'http') === false)
		{
			$fb_url = SB_DOMAIN.'/'.trim($fb_url, ' /');
		}

		list($fb_url, $more_ext) = sbGetMorePage($fb_url);

		if (sbPlugins::getSetting('sb_static_urls') == 1)
        {
			//	ЧПУ
			$cat_url = sql_query('SELECT cat_url FROM sb_categs WHERE cat_id = ?d', $cat_id);
			$fb_url .= (isset($cat_url[0][0]) && $cat_url[0][0] != '' ? urlencode($cat_url[0][0]).'/' : $cat_id.'/').
					($n_url != '' ? urlencode($n_url) : $n_id).($more_ext != 'php' ? '.'.$more_ext : '/');
		}
		else
		{
			$fb_url .= '?news_cid='.$cat_id.'&news_id='.$n_id;
		}
	}

	$m_len = sb_strlen($msg);

	if ($m_len > 420)
	{
		$msg = sb_short_text($msg, 420);
	}


    $url = 'https://graph.facebook.com/'.$uid.'/feed';

	$pst = array(
		'message'		=> $_POST['n_title'],
		'access_token'	=> $access_token,
        'type' => 'status'
	);

    $use_facebook = $_SESSION['sbPlugins']->getSetting('sb_news_facebook_use');

    switch ($use_facebook)
    {
        case 0:
            if(isset($_POST['sb_facebook_photo']) && $_POST['sb_facebook_photo'] == 'short')
            {
                if(sb_stripos($_POST['n_short_foto'], SB_DOMAIN) == false)
                {
                    $_POST['n_short_foto'] = sb_str_replace('//', '/', '/'.$_POST['n_short_foto']);
                    $_POST['n_short_foto'] = SB_DOMAIN .  $_POST['n_short_foto'];
                    $_POST['n_short_foto'] = sb_str_replace('\\', '/', $_POST['n_short_foto']);
                }
                $pst['picture'] = $_POST['n_short_foto'];
            }
            elseif(isset($_POST['sb_facebook_photo']) && $_POST['sb_facebook_photo'] == 'big')
            {
                if(sb_stripos($_POST['n_full_foto'], SB_DOMAIN) == false)
                {
                    $_POST['n_full_foto'] = sb_str_replace('//', '/', '/'.$_POST['n_full_foto']);
                    $_POST['n_full_foto'] = SB_DOMAIN . $_POST['n_full_foto'];
                    $_POST['n_full_foto'] = sb_str_replace('\\', '/', $_POST['n_full_foto']);
                }
                $pst['picture'] = $_POST['n_full_foto'];
            }
            $pst['name'] = ' ';
            $pst['link'] = $fb_url;
            $pst['description'] = $msg;
            $pst['caption'] = ' ';
            break;

        case 1:
            if($_POST['n_short_foto'] != '')
            {
                if(sb_stripos($_POST['n_short_foto'], SB_DOMAIN) == false)
                {
                    $_POST['n_short_foto'] = sb_str_replace('//', '/', '/'.$_POST['n_short_foto']);
                    $_POST['n_short_foto'] = SB_DOMAIN .  $_POST['n_short_foto'];
                    $_POST['n_short_foto'] = sb_str_replace('\\', '/', $_POST['n_short_foto']);
                }
                $pst['picture'] = $_POST['n_short_foto'];
                $pst['name'] = ' ';
                $pst['link'] = $fb_url;
                $pst['description'] = $msg;
                $pst['caption'] = ' ';
            }
            else
            {
                $pst['message'] .= "\r\n".$msg;
            }
            break;

        case 2:
            if($_POST['n_full_foto'] != '')
            {
                if(sb_stripos($_POST['n_full_foto'], SB_DOMAIN) == false)
                {
                    $_POST['n_full_foto'] = sb_str_replace('//', '/', '/'.$_POST['n_full_foto']);
                    $_POST['n_full_foto'] = SB_DOMAIN . $_POST['n_full_foto'];
                    $_POST['n_full_foto'] = sb_str_replace('\\', '/', $_POST['n_full_foto']);
                }
                $pst['picture'] = $_POST['n_full_foto'];
                $pst['name'] = ' ';
                $pst['link'] = $fb_url;
                $pst['description'] = $msg;
                $pst['caption'] = ' ';
            }
            else
            {
                $pst['message'] .= "\r\n".$msg;
            }
            break;
    }

	if ($fb_url != '')
	{
		$pst['actions'] = '[{"name":"'.PL_NEWS_EDIT_FACEBOOK_MORE.'","link":"'.$fb_url.'"}]';
	}

	if (function_exists('curl_init'))
	{
		$ch = curl_init($url);

		if ($ch !== false)
		{
			curl_setopt($ch, CURLOPT_POST, 1);
		 	curl_setopt($ch, CURLOPT_POSTFIELDS, $pst);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);

			$result = curl_exec($ch);
			if (curl_errno($ch) > 0)
			{
				sb_add_system_message(PL_NEWS_FACEBOOK_ADD_MSG_ERR, SB_MSG_WARNING);
				curl_close($ch);
				return;
			}

			curl_close($ch);

            $result = sb_json_decode($result);

            if(isset($result->error))
            {
                sb_add_system_message(PL_NEWS_FACEBOOK_ADD_MSG_ERR . ' <i>' . $result->error->message . '</i>', SB_MSG_WARNING);
            }
		}
	}
	else if (function_exists('fsockopen'))
	{
		require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');
		$httpRequest = new sbSocket($url);
		$result = $httpRequest -> request('POST', array(), $pst, true);
	}
	else
	{
		sb_add_system_message(PL_NEWS_EDIT_CURL_ERROR, SB_MSG_WARNING);
	}

	if (!isset($result) || (is_object($result) && isset($result->error)) || (!is_object($result) && sb_strpos($result, 'error_code') !== false) || $result == '')
	{
		sb_add_system_message(PL_NEWS_FACEBOOK_ADD_MSG_ERR, SB_MSG_WARNING);
	}
}

function fNews_Facebook_Is_Permission()
{
	require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');

	$access_token = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_access_token'));
    $client_id = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_application_id'));
    $client_secret = trim($_SESSION['sbPlugins']->getSetting('sb_news_facebook_application_secret'));

    //проверяем срок действия access_token
    $httpRequest = new sbDownload('https://graph.facebook.com/oauth/access_token?client_id='.$client_id.'&client_secret='.$client_secret.'&grant_type=fb_exchange_token&fb_exchange_token='.$access_token);
    $result = $httpRequest->download();

    if($result && sb_strpos($result, 'expires') != false)
    {
        $expires = preg_replace('/.*expires=(\d+)/', '$1', $result);
        if(intval($expires) <= 0)
        {
            return false;
        }
    }


    $httpRequest = new sbDownload('https://graph.facebook.com/me/permissions/?&access_token='.$access_token);
    $result = $httpRequest->download();

    if ($result)
    {
        $result = sb_json_decode($result);

        if (isset($result->data[0]->publish_stream) && $result->data[0]->publish_stream == 1 && isset($result->data[0]->manage_pages) && $result->data[0]->manage_pages == 1)
        {
            return true;
        }
    }
	return false;
}

function fNews_LJ_Is_Permission($lj_login, $lj_password)
{
	if (function_exists('xmlrpc_encode_request'))
	{
		$pars = array('username' => $lj_login, 'hpassword' => md5($lj_password));

		$request = xmlrpc_encode_request('LJ.XMLRPC.login', $pars);
		$context = stream_context_create(array('http' => array(
		    'method' => 'POST',
		    'header' => 'Content-Type: text/xml',
		    'content' => $request
		)));

		$file = file_get_contents('http://www.livejournal.com/interface/xmlrpc/', false, $context);

		if ($file)
		{
			$response = xmlrpc_decode($file);
			if (isset($response['userid']))
			{
				return true;
			}
		}

		sb_show_message(PL_NEWS_EDIT_LJ_LOGIN_ERROR, false, 'warning');
		return false;
	}
	else if (function_exists('fsockopen'))
	{
		require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');
	    $httpRequest = new sbSocket('http://www.livejournal.com/interface/flat');

	    $pars = array(
			'mode'			=>	'login',
			'user' 			=>	$lj_login,
			'hpassword'		=>	md5($lj_password),
			'auth_method'	=>	'clear',
			'ver'			=>	'1'
		);

	    $result = $httpRequest -> request('POST', array(), $pars);
	    if (!isset($result) || sb_strpos($result, 'FAIL') !== false)
	    {
	    	sb_show_message(PL_NEWS_EDIT_LJ_LOGIN_ERROR, false, 'warning');
			return false;
	    }

	    return true;
	}

	sb_show_message(PL_NEWS_EDIT_LJ_XMLRPC_ERROR, false, 'warning');
	sb_add_system_message(PL_NEWS_EDIT_LJ_XMLRPC_ERROR, SB_MSG_WARNING);
	return false;
}

function fNews_Send_LJ($lj_message, $lj_title, $cat_id, $n_id, $n_url)
{
	require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');
	$lj_message = str_replace(array("\r\n", "\n", "\r"), ' ', sbProgParseBBCodes(trim($lj_message)));
	$lj_title = str_replace(array("\r\n", "\n", "\r"), '', trim($lj_title));

	$lj_login = trim($_SESSION['sbPlugins']->getSetting('sb_news_lj_login'));
	$lj_password = trim($_SESSION['sbPlugins']->getSetting('sb_news_lj_password'));

	if($lj_message == '' || $lj_login == '' || $lj_password == '')
		return;

	$lj_url = trim($_SESSION['sbPlugins']->getSetting('sb_news_lj_url'));
	if ($lj_url != '')
	{
		if (sb_strpos($lj_url, 'http') === false)
		{
			$lj_url = SB_DOMAIN.'/'.trim($lj_url, ' /');
		}
		list($lj_url, $more_ext) = sbGetMorePage($lj_url);

		if (sbPlugins::getSetting('sb_static_urls') == 1)
        {
			//	ЧПУ
			$cat_url = sql_query('SELECT cat_url FROM sb_categs WHERE cat_id = ?d', $cat_id);
			$lj_url .= (isset($cat_url[0][0]) && $cat_url[0][0] != '' ? urlencode($cat_url[0][0]).'/' : $cat_id.'/').
					($n_url != '' ? urlencode($n_url) : $n_id).($more_ext != 'php' ? '.'.$more_ext : '/');
		}
		else
		{
			$lj_url .= '?news_cid='.$cat_id.'&news_id='.$n_id;
		}

		$lj_message .= '<p><a target="_blank" href="'.$lj_url.'">'.PL_NEWS_EDIT_LJ_MORE_LINK.'</a></p>';
	}

	$pars = array(
		'hpassword'		=>	md5($lj_password),
		'auth_method'	=>	'clear',
		'event'			=>	$lj_message,
		'lineendings'	=>	'pc',
		'ver'			=>	'1',
		'subject'		=>	$lj_title,
		'year'			=>	sb_date('Y'),
		'mon'			=>	sb_date('m'),
		'day'			=>	sb_date('d'),
		'hour'			=>	sb_date('H'),
		'min'			=>	sb_date('i')
	);

	if (function_exists('xmlrpc_encode_request'))
	{
		$pars['username'] = $lj_login;

		$request = xmlrpc_encode_request('LJ.XMLRPC.postevent', $pars, array('escaping' => 'markup', 'encoding' => 'utf-8'));
		$context = stream_context_create(array('http' => array(
		    'method' => 'POST',
		    'header' => 'Content-Type: text/xml',
		    'content' => $request
		)));

		$file = file_get_contents('http://www.livejournal.com/interface/xmlrpc/', false, $context);

		if ($file)
		{
			$response = xmlrpc_decode($file);
			if (isset($response['userid']))
			{
				return true;
			}
		}

		sb_show_message(PL_NEWS_EDIT_LJ_LOGIN_ERROR, false, 'warning');
		return false;
	}
	else if (function_exists('curl_init'))
	{
		$pars['user'] = $lj_login;
		$pars['mode'] = 'postevent';

		$ch = curl_init('http://www.livejournal.com/interface/flat');
		if ($ch)
		{
			curl_setopt($ch, CURLOPT_POST, 1);
		 	curl_setopt($ch, CURLOPT_POSTFIELDS, $pars);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);

	 		$result = curl_exec($ch);
			if (curl_errno($ch) > 0)
			{
				sb_add_system_message(PL_NEWS_LJ_ADD_MSG_ERR, SB_MSG_WARNING);
				curl_close($ch);
				return;
			}
	 		curl_close($ch);
		}
	}
	elseif(function_exists('fsockopen'))
	{
		$pars['user'] = $lj_login;
		$pars['mode'] = 'postevent';
		$pars['event'] = rawurlencode($pars['event']);
		$pars['subject'] = rawurlencode($pars['subject']);

		include_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');
	    $httpRequest = new sbSocket('http://www.livejournal.com/interface/flat');
	    $result = $httpRequest -> request('POST', array(), $pars);
	}
	else
	{
		sb_add_system_message(PL_NEWS_EDIT_CURL_ERROR, SB_MSG_WARNING);
		return;
	}

	if (!isset($result) || sb_strpos($result, 'FAIL') !== false || $result == '')
	{
		sb_add_system_message(PL_NEWS_LJ_ADD_MSG_ERR, SB_MSG_WARNING);
	}
}

function fNews_Get_Short_Url($url, $login, $key)
{
	if(trim($url) == '')
	{
		return false;
	}

	if (trim($key) == '' || trim($login) == '')
	{
		return false;
	}

	$res = false;
	if (function_exists('curl_init'))
	{
		$ch = curl_init();
		if ($ch !== false)
		{
			curl_setopt($ch, CURLOPT_URL, 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$key.'&longUrl='.urlencode($url).'&format=xml');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$res = curl_exec($ch);
			curl_close($ch);
		}
	}
	else if (function_exists('fsockopen'))
	{
		require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');

		$pars = array(
			'login'		=>	$login,
			'apiKey'	=>	$key,
			'longUrl'	=>	urlencode($url),
			'format'	=>	'xml'
		);

		$httpRequest = new sbSocket('http://api.bit.ly/v3/shorten');
	    $res = $httpRequest -> request('POST', array(), $pars);
	}
	else
	{
		sb_add_system_message(PL_NEWS_EDIT_CURL_ERROR, SB_MSG_WARNING);
		return false;
	}

	if ($res !== false)
	{
		$xml = simplexml_load_string($res);
		if ($xml)
		{
			if ($xml->status_code == 200 && $xml->status_txt == 'OK' && $xml->data->url != '')
			{
				return (string) $xml->data->url;
			}
		}
	}

	sb_add_system_message(PL_NEWS_EDIT_BITLY_ERROR, SB_MSG_WARNING);
	return false;
}


/**
 * Функции управления макетами дизайна новостной ленты
 */
function fNews_Design_List_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ndl_title'].'</a></b>
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

    $id = intval($args['ndl_id']);

	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id ORDER BY pages.p_name LIMIT 4', 'page', $id, 'pl_news_list');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id ORDER BY temps.t_name LIMIT 4', 'temp', $id, 'pl_news_list');

	if($pages)
	{
	    $result .= '<table cellpadding="0" cellspacing="0">
					<tr>
						<td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
						<td class="smalltext"><span style="color:green;">';

		$num = min(3, count($pages));
		for($i = 0; $i < $num; $i++)
		{
			list($p_id, $p_name) = $pages[$i];
			$result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
		}
		if($num < count($pages))
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

function fNews_Design_List()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_news_temps_list', 'ndl_id', 'ndl_title', 'fNews_Design_List_Get', 'pl_news_design_list', 'pl_news_list');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_news_list_32.png';

    $elems->addSorting(PL_NEWS_DESIGN_EDIT_SORT_BY_TITLE, 'ndl_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_news_design_list_edit';
    $elems->mElemsEditDlgWidth = 900;
	$elems->mElemsEditDlgHeight = 700;

	$elems->mElemsAddEvent =  'pl_news_design_list_edit';
    $elems->mElemsAddDlgWidth = 900;
	$elems->mElemsAddDlgHeight = 700;


	$elems->mElemsDeleteEvent = 'pl_news_design_list_delete';

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

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_news_list";
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

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_news_list";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
	      }
	      function newsList()
	      {
	          window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
	      }';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_NEWS_NEWSFEED_MENU, 'newsList();', false);

    $elems->init();
}

function fNews_Design_List_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_list'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
   		$result = sql_query('SELECT ndl_title, ndl_lang, ndl_checked, ndl_count, ndl_top, ndl_categ_top, ndl_element, ndl_empty, ndl_delim, ndl_categ_bottom, ndl_bottom, ndl_pagelist_id, ndl_perpage, ndl_no_news, ndl_fields_temps, ndl_categs_temps, ndl_votes_id, ndl_comments_id, ndl_user_data_id, ndl_tags_list_id
   		                           FROM sb_news_temps_list WHERE ndl_id=?d', $_GET['id']);
   		if ($result)
	    {
        	list($ndl_title, $ndl_lang, $ndl_checked, $ndl_count, $ndl_top, $ndl_categ_top, $ndl_element, $ndl_empty, $ndl_delim, $ndl_categ_bottom, $ndl_bottom, $ndl_pagelist_id, $ndl_perpage, $ndl_no_news, $ndl_fields_temps, $ndl_categs_temps, $ndl_votes_id, $ndl_comments_id, $ndl_user_data_id, $ndl_tags_list_id) = $result[0];
	    }
	    else
	    {
	        sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, true, 'warning');
	        return;
	    }

	    if (trim($ndl_fields_temps) != '')
	        $ndl_fields_temps = unserialize($ndl_fields_temps);
	    else
	        $ndl_fields_temps = array();

	    if (trim($ndl_categs_temps) != '')
	        $ndl_categs_temps = unserialize($ndl_categs_temps);
	    else
	        $ndl_categs_temps = array();

	    if (trim($ndl_checked) != '')
		    $ndl_checked = explode(' ', $ndl_checked);
		else
		    $ndl_checked = array();

		if (!isset($ndl_fields_temps['n_registred_users']))
			$ndl_fields_temps['n_registred_users'] = PL_NEWS_DESIGN_EDIT_USER_LINK_TAG_VAL;
		if (!isset($ndl_fields_temps['n_last_date']))
			$ndl_fields_temps['n_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
		if (!isset($ndl_fields_temps['n_edit_link']))
			$ndl_fields_temps['n_edit_link'] = PL_NEWS_DESIGN_EDIT_EDIT_DEFAULT;
	}
	else if (count($_POST) > 0)
	{
		$ndl_checked = array();
		$ndl_pagelist_id = -1;
	    $ndl_votes_id = -1;
	    $ndl_tags_list_id = -1;
	    $ndl_comments_id = -1;
		$ndl_user_data_id = -1;

		extract($_POST);

		if (!isset($_GET['id']))
            $_GET['id'] = '';
	}
	else
	{
	    $ndl_title = $ndl_categ_top = $ndl_empty = $ndl_delim = $ndl_categ_bottom = '';

	    $ndl_top = PL_NEWS_DESIGN_EDIT_TOP_DEFAULT;
	    $ndl_element = PL_NEWS_DESIGN_EDIT_ELEMENT_DEFAULT;
	    $ndl_bottom = PL_NEWS_DESIGN_EDIT_BOTTOM_DEFAULT;

		$ndl_user_data_id = -1;
	    $ndl_pagelist_id = -1;
	    $ndl_votes_id = -1;
	    $ndl_comments_id = -1;
	    $ndl_tags_list_id = -1;
	    $ndl_perpage = 10;
	    $ndl_count = 1;
	    $ndl_checked = array();
	    $ndl_lang = SB_CMS_LANG;
	    $ndl_no_news = PL_NEWS_DESIGN_EDIT_NO_NEWS_TEMP;

	    $ndl_fields_temps = array();
	    $ndl_fields_temps['n_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $ndl_fields_temps['n_more'] = PL_NEWS_DESIGN_EDIT_MORE_DEFAULT;
	    $ndl_fields_temps['n_edit_link'] = PL_NEWS_DESIGN_EDIT_EDIT_DEFAULT;
	    $ndl_fields_temps['n_short_foto'] = '<img src="{IMG_LINK}" alt="{TITLE}" />';
	    $ndl_fields_temps['n_full_foto'] = '<img src="{IMG_LINK}" alt="{TITLE}" />';
		$ndl_fields_temps['n_registred_users'] = PL_NEWS_DESIGN_EDIT_USER_LINK_TAG_VAL;
		$ndl_fields_temps['n_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';

	    $ndl_categs_temps = array();

	    $_GET['id'] = '';
	}

	echo '<script>
	        function checkValues()
            {
                var el_title = sbGetE("ndl_title");
                if (el_title.value == "")
                {
          	         alert("'.PL_NEWS_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_news_design_list_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB1);

	$layout->addField(PL_NEWS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ndl_title, 'ndl_title', '', 'style="width:450px;"', true));
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ndl_lang');
	$fld->mSelOptions = array($ndl_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_NEWS_DESIGN_EDIT_LANG, $fld);

	$layout->addPluginFieldsTempsCheckboxes('pl_news', $ndl_checked, 'ndl_checked');

    fVoting_Design_Get($layout, $ndl_votes_id, 'ndl_votes_id');

    fComments_Design_Get($layout, $ndl_comments_id, 'ndl_comments_id');

    fSite_Users_Design_Get($layout, $ndl_user_data_id, 'ndl_user_data_id');

    fClouds_Design_Get($layout, $ndl_tags_list_id, 'ndl_tags_list_id', 'element');

    fPager_Design_Get($layout, $ndl_pagelist_id, 'ndl_pagelist_id', $ndl_perpage, 'ndl_perpage');

	$layout->addField('', new sbLayoutDelim());
	$layout->addField(PL_NEWS_DESIGN_EDIT_NO_NEWS, new sbLayoutTextarea($ndl_no_news, 'ndl_no_news', '', 'style="width:100%;height:50px;"'));

	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB2);
	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB2);

	$tags = array('{LINK}', '{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
	$tags_values = array(PL_NEWS_DESIGN_EDIT_LINK_TAG, PL_NEWS_DESIGN_EDIT_ID_TAG, PL_NEWS_DESIGN_EDIT_ELEM_URL_TAG, PL_NEWS_DESIGN_EDIT_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CAT_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG, PL_NEWS_DESIGN_EDIT_CAT_TITLE_TAG);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_EDIT_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ndl_fields_temps['n_date'], 'ndl_fields_temps[n_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_NEWS_EDIT_DATE, $fld);

	$fld = new sbLayoutTextarea($ndl_fields_temps['n_last_date'], 'ndl_fields_temps[n_last_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_NEWS_EDIT_LAST_DATE, $fld);

	$fld = new sbLayoutTextarea($ndl_fields_temps['n_more'], 'ndl_fields_temps[n_more]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = $tags;
	$fld->mValues = $tags_values;
	$layout->addField(PL_NEWS_DESIGN_EDIT_MORE, $fld);

	//Макет ссылки "Редактировать"
	$fld = new sbLayoutTextarea($ndl_fields_temps['n_edit_link'], 'ndl_fields_temps[n_edit_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{EDIT_LINK}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_EDIT_TAG), $tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_EDIT, $fld);

	$fld = new sbLayoutTextarea($ndl_fields_temps['n_short_foto'], 'ndl_fields_temps[n_short_foto]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{IMG_LINK}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_FOTO_TAG), $tags_values);
	$layout->addField(PL_NEWS_EDIT_ANONS_FOTO, $fld);

	$fld = new sbLayoutTextarea($ndl_fields_temps['n_full_foto'], 'ndl_fields_temps[n_full_foto]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{IMG_LINK}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_FOTO_TAG), $tags_values);
	$layout->addField(PL_NEWS_EDIT_FULL_FOTO, $fld);

	$fld = new sbLayoutTextarea($ndl_fields_temps['n_registred_users'], 'ndl_fields_temps[n_registred_users]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_EDIT_USER_LINK_TAG_VAL), $tags);
    $fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_USER_LINK_TAG), $tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_USER_LINK_TAG, $fld);

	$layout->addPluginFieldsTemps('pl_news', $ndl_fields_temps, 'ndl_', $tags, $tags_values);

	$cat_tags = array();
	$cat_tags_values = array();
	$layout->getPluginFieldsTags('pl_news', $cat_tags, $cat_tags_values, true);

	$news_tags = array();
	$news_tags_values = array();
	$layout->getPluginFieldsTags('pl_news', $news_tags, $news_tags_values);

	if (count($cat_tags) != 0)
	{
    	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB3);
    	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB3);

    	$layout->addPluginFieldsTemps('pl_news', $ndl_categs_temps, 'ndl_', $tags, $tags_values, true);
	}

	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB4);
	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB4);

	$fld = new sbLayoutInput('text', $ndl_count, 'ndl_count', 'spin_ndl_count', 'style="width:100px;"');
	$fld->mMinValue = 1;

	$layout->addField(PL_NEWS_DESIGN_EDIT_COUNT, $fld);
	//Верх вывода
 	$flds_tags = array('{NUM_LIST}', '{ALL_COUNT}', PL_NEWS_DESIGN_FORM_NEWS_INPAGE_SELECT,
						'<a href=\'{SORT_ID_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_ID_ASC_TAG.'</a>','<a href=\'{SORT_ID_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_ID_DESC_TAG.'</a>',
						'<a href=\'{SORT_TITLE_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_TITLE_ASC_TAG.'</a>','<a href=\'{SORT_TITLE_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_TITLE_DESC_TAG.'</a>',
						'<a href=\'{SORT_DATE_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_DATE_ASC_TAG.'</a>','<a href=\'{SORT_DATE_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_DATE_DESC_TAG.'</a>',
						'<a href=\'{SORT_SHORT_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_SHORT_ASC_TAG.'</a>','<a href=\'{SORT_SHORT_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_SHORT_DESC_TAG.'</a>',
						'<a href=\'{SORT_FULL_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_FULL_ASC_TAG.'</a>','<a href=\'{SORT_FULL_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_FULL_DESC_TAG.'</a>',
						'<a href=\'{SORT_SORT_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_SORT_ASC_TAG.'</a>','<a href=\'{SORT_SORT_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_SORT_DESC_TAG.'</a>',
						'<a href=\'{SORT_USER_ID_ASC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_USER_ID_ASC_TAG.'</a>','<a href=\'{SORT_USER_ID_DESC}\'>'.PL_NEWS_DESIGN_EDIT_SORT_USER_ID_DESC_TAG.'</a>');
    $flds_vals = array(PL_NEWS_DESIGN_EDIT_PAGELIST_TAG, PL_NEWS_DESIGN_EDIT_ALLNUM_TAG, PL_NEWS_DESIGN_EDIT_INPAGENUM_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_ID_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_ID_DESC_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_TITLE_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_TITLE_DESC_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_DATE_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_DATE_DESC_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_SHORT_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_SHORT_DESC_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_FULL_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_FULL_DESC_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_SORT_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_SORT_DESC_TAG,
							PL_NEWS_DESIGN_EDIT_SORT_USER_ID_ASC_TAG, PL_NEWS_DESIGN_EDIT_SORT_USER_ID_DESC_TAG);

    $layout->getPluginFieldsTagsSort('news', $flds_tags, $flds_vals);
	$fld = new sbLayoutTextarea($ndl_top, 'ndl_top', '', 'style="width:100%;height:100px;"');
	$fld->mTags = $flds_tags;
	$fld->mValues = $flds_vals;
	$layout->addField(PL_NEWS_DESIGN_EDIT_TOP, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ndl_categ_top, 'ndl_categ_top', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge(array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_NEWS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CATEG_NUM_TAG, PL_NEWS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_NEWS_DESIGN_EDIT_CATEG_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_CAT_TOP, $fld);

	// Анонс новости
	$fld = new sbLayoutTextarea($ndl_element, 'ndl_element', '', 'style="width:100%;height:250px;"');
	$fld->mTags = array_merge(array('-', '{ELEM_NUMBER}', '{ID}', '{ELEM_URL}',  '{TITLE}', '{LINK}', '{MORE}', '{EDIT_LINK}', '{DATE}', '{CHANGE_DATE}', '{SHORT}', '{SHORT_FOTO}', '{FULL}', '{FULL_FOTO}', '{TAGS}','{USER_DATA}', '{ELEM_USER_LINK}'),
							  $news_tags, array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}', '-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}'),
	                          array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);

	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_NEWS_GROUP_TAG, PL_NEWS_DESIGN_EDIT_ELEM_NUMBER_TAG, PL_NEWS_DESIGN_EDIT_ID_TAG, PL_NEWS_DESIGN_EDIT_ELEM_URL_TAG, PL_NEWS_DESIGN_EDIT_TITLE_TAG, PL_NEWS_DESIGN_EDIT_LINK_TAG, PL_NEWS_DESIGN_EDIT_MORE_TAG,
	                                  PL_NEWS_DESIGN_EDIT_RDIT_TAG, PL_NEWS_DESIGN_EDIT_DATE_TAG, PL_NEWS_DESIGN_EDIT_CHANGE_DATE_TAG, PL_NEWS_DESIGN_EDIT_SHORT_TAG, PL_NEWS_DESIGN_EDIT_SHORT_FOTO_TAG, PL_NEWS_DESIGN_EDIT_FULL_TAG, PL_NEWS_DESIGN_EDIT_FULL_FOTO_TAG,
	                                  PL_NEWS_DESIGN_EDIT_TAGS_LIST_TAG, PL_NEWS_DESIGN_EDIT_USER_DATA_TAG, PL_NEWS_DESIGN_EDIT_USER_LINK_TAG),
								$news_tags_values,
								array(PL_NEWS_DESIGN_COMMENTS_TAG, PL_NEWS_DESIGN_COUNT_COMMENTS_TAG, PL_NEWS_DESIGN_LIST_COMMENTS_TAG, PL_NEWS_DESIGN_FORM_COMMENTS_TAG, PL_NEWS_DESIGN_EDIT_RATING_GROUP, PL_NEWS_DESIGN_EDIT_RATING, PL_NEWS_DESIGN_EDIT_VOTES_COUNT,
								PL_NEWS_DESIGN_EDIT_VOTES_SUM, PL_NEWS_DESIGN_EDIT_VOTES_FORM),
                                array(PL_NEWS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_NEWS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CATEG_NUM_TAG, PL_NEWS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_NEWS_DESIGN_EDIT_CATEG_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_ELEMENT, $fld);

	$fld = new sbLayoutTextarea($ndl_empty, 'ndl_empty', '', 'style="width:100%;height:100px;"');
	$layout->addField(PL_NEWS_DESIGN_EDIT_EMPTY, $fld);

	$fld = new sbLayoutTextarea($ndl_delim, 'ndl_delim', '', 'style="width:100%;height:100px;"');
	$layout->addField(PL_NEWS_DESIGN_EDIT_DELIM, $fld);

	$fld = new sbLayoutTextarea($ndl_categ_bottom, 'ndl_categ_bottom', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge(array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_NEWS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CATEG_NUM_TAG, PL_NEWS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_NEWS_DESIGN_EDIT_CATEG_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_CAT_BOTTOM, $fld);

	$layout->addField('', new sbLayoutDelim());

	//Низ вывода
	$fld = new sbLayoutTextarea($ndl_bottom, 'ndl_bottom', '', 'style="width:100%;height:100px;"');
	$fld->mTags = $flds_tags;
	$fld->mValues = $flds_vals;
	$layout->addField(PL_NEWS_DESIGN_EDIT_BOTTOM, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fNews_Design_List_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_list'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

	$ndl_user_data_id = 0;
	$ndl_tags_list_id = 0;
    $ndl_pagelist_id = 0;
    $ndl_votes_id = 0;
    $ndl_comments_id = 0;
    $ndl_checked = array();
    $ndl_lang = SB_CMS_LANG;
    $ndl_fields_temps = array();
    $ndl_categs_temps = array();

    extract($_POST);

    if ($ndl_title == '')
    {
        sb_show_message(PL_NEWS_DESIGN_EDIT_NO_TITLE_MSG, false, 'warning');
        fNews_Design_List_Edit();
        return;
    }

    $row = array();
    $row['ndl_title'] = $ndl_title;
    $row['ndl_lang'] = $ndl_lang;
    $row['ndl_checked'] = implode(' ', $ndl_checked);
    $row['ndl_count'] = $ndl_count;
    $row['ndl_top'] = $ndl_top;
    $row['ndl_categ_top'] = $ndl_categ_top;
    $row['ndl_element'] = $ndl_element;
    $row['ndl_empty'] = $ndl_empty;
    $row['ndl_delim'] = $ndl_delim;
    $row['ndl_categ_bottom'] = $ndl_categ_bottom;
    $row['ndl_bottom'] = $ndl_bottom;
    $row['ndl_pagelist_id'] = $ndl_pagelist_id;
    $row['ndl_perpage'] = $ndl_perpage;
    $row['ndl_no_news'] = $ndl_no_news;
    $row['ndl_fields_temps'] = serialize($ndl_fields_temps);
    $row['ndl_categs_temps'] = serialize($ndl_categs_temps);
    $row['ndl_votes_id'] = $ndl_votes_id;
    $row['ndl_comments_id'] = $ndl_comments_id;
    $row['ndl_user_data_id'] = $ndl_user_data_id;
    $row['ndl_tags_list_id'] = $ndl_tags_list_id;

    if ($_GET['id'] != '')
	{
	    $res = sql_query('SELECT ndl_title FROM sb_news_temps_list WHERE ndl_id=?d', $_GET['id']);
	    if ($res)
	    {
	        // редактирование
	        list($old_title) = $res[0];

    	    sql_query('UPDATE sb_news_temps_list SET ?a WHERE ndl_id=?d', $row, $_GET['id'], sprintf(PL_NEWS_DESIGN_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_news_temps_list', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
    	    if (!$footer_ar)
    	    {
    	        sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_NEWS_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fNews_Design_List_Edit();
    		    return;
    	    }
    	    $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

    	    $row['ndl_id'] = intval($_GET['id']);

            $html_str = fNews_Design_List_Get($row);
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
    	        fNews_Design_List_Edit($html_str, $footer_str);
    	    }
	    }
	    else
	    {
	        sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_DESIGN_EDIT_SYSTEMLOG_ERROR, $ndl_title), SB_MSG_WARNING);

            fNews_Design_List_Edit();
		    return;
	    }
	}
	else
	{
	    $error = true;
	    if (sql_query('INSERT INTO sb_news_temps_list (?#) VALUES (?a)', array_keys($row), array_values($row)))
	    {
    		$id = sql_insert_id();

    		if (fCategs_Add_Elem($id, 'design_edit'))
    		{
        		sb_add_system_message(sprintf(PL_NEWS_DESIGN_ADD_OK, $ndl_title));
                sbQueryCache::updateTemplate('sb_news_temps_list', $id);

        		echo '<script>
        				sbReturnValue('.$id.');
        			  </script>';

        		$error = false;
    		}
    		else
    		{
    		    sql_query('DELETE FROM sb_news_temps_list WHERE ndl_id=?d', $id);
    		}
	    }

	    if ($error)
	    {
	        sb_show_message(sprintf(PL_NEWS_DESIGN_ADD_ERROR, $ndl_title), false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_DESIGN_ADD_SYSTEMLOG_ERROR, $ndl_title), SB_MSG_WARNING);

            fNews_Design_List_Edit();
		    return;
	    }
	}
}

function fNews_Design_List_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id LIMIT 1', 'page', $id, 'pl_news_list');

    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id LIMIT 1', 'temp', $id, 'pl_news_list');
    }

    if ($pages || $temps)
    {
        echo PL_NEWS_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна полной новости
 */
function fNews_Design_Full_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ntf_title'].'</a></b>
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

    $id = intval($args['ntf_id']);

	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id ORDER BY pages.p_name LIMIT 4', 'page', $id, 'pl_news_full');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id ORDER BY temps.t_name LIMIT 4', 'temp', $id, 'pl_news_full');

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

function fNews_Design_Full()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_news_temps_full', 'ntf_id', 'ntf_title', 'fNews_Design_Full_Get', 'pl_news_design_full', 'pl_news_full');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_news_full_32.png';

    $elems->addField('ntf_title');      // название макета дизайна

    $elems->addSorting(PL_NEWS_DESIGN_EDIT_SORT_BY_TITLE, 'ntf_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_news_design_full_edit';
    $elems->mElemsEditDlgWidth = 900;
	$elems->mElemsEditDlgHeight = 700;

	$elems->mElemsAddEvent =  'pl_news_design_full_edit';
    $elems->mElemsAddDlgWidth = 900;
	$elems->mElemsAddDlgHeight = 700;

	$elems->mElemsDeleteEvent = 'pl_news_design_full_delete';

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

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_news_full";
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

	          strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_news_full";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
	      }
	      function newsList()
	      {
	          window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
	      }';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_NEWS_NEWSFEED_MENU, 'newsList();', false);

    $elems->init();
}

function fNews_Design_Full_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_full'))
		return;

	#TODO: Сделать вывод таймстемп во всех полях типа Дата
    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
   		$result = sql_query('SELECT ntf_title, ntf_lang, ntf_fields_temps, ntf_categs_temps, ntf_element, ntf_checked, ntf_comments_id, ntf_votes_id, ntf_user_data_id, ntf_tags_list_id
   		                           FROM sb_news_temps_full WHERE ntf_id=?d', $_GET['id']);

   		if ($result)
	    {
        	list($ntf_title, $ntf_lang, $ntf_fields_temps, $ntf_categs_temps, $ntf_element, $ntf_checked, $ntf_comments_id, $ntf_votes_id, $ntf_user_data_id, $ntf_tags_list_id) = $result[0];
	    }
	    else
	    {
	        sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, true, 'warning');
	        return;
	    }

	    if (trim($ntf_fields_temps) != '')
	        $ntf_fields_temps = unserialize($ntf_fields_temps);
	    else
	        $ntf_fields_temps = array();

	    if (trim($ntf_categs_temps) != '')
	        $ntf_categs_temps = unserialize($ntf_categs_temps);
	    else
	        $ntf_categs_temps = array();

	    if (trim($ntf_checked) != '')
		    $ntf_checked = explode(' ', $ntf_checked);
		else
		    $ntf_checked = array();

		if (!isset($ntf_fields_temps['n_registred_users']))
			$ntf_fields_temps['n_registred_users'] = PL_NEWS_DESIGN_EDIT_USER_LINK_TAG_VAL;

		if (!isset($ntf_fields_temps['n_last_date']))
			$ntf_fields_temps['n_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
		if (!isset($ntf_fields_temps['n_edit_link']))
			$ntf_fields_temps['n_edit_link'] = PL_NEWS_DESIGN_EDIT_EDIT_DEFAULT;
		if (!isset($ntf_fields_temps['n_prev_link']))
			$ntf_fields_temps['n_prev_link'] = PL_NEWS_DESIGN_EDIT_PREV_LINK_TAG_VAL;
		if (!isset($ntf_fields_temps['n_next_link']))
			$ntf_fields_temps['n_next_link'] = PL_NEWS_DESIGN_EDIT_NEXT_LINK_TAG_VAL;
	}
	else if (count($_POST) > 0)
	{
		$ntf_checked = array();
		$ntf_comments_id = -1;
		$ntf_votes_id = -1;
		$ntf_user_data_id = -1;
		$ntf_tags_list_id = -1;

    	extract($_POST);

    	if (!isset($_GET['id']))
            $_GET['id'] = '';
	}
	else
	{
		$ntf_title = $ntf_element = '';

		$ntf_user_data_id = -1;
		$ntf_comments_id = -1;
		$ntf_votes_id = -1;
		$ntf_tags_list_id = -1;
	    $ntf_checked = array();
	    $ntf_lang = SB_CMS_LANG;

	    $ntf_fields_temps = array();
	    $ntf_fields_temps['n_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $ntf_fields_temps['n_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
	    $ntf_fields_temps['n_short_foto'] = '<img src="{IMG_LINK}" alt="{TITLE}" />';
	    $ntf_fields_temps['n_full_foto'] = '<img src="{IMG_LINK}" alt="{TITLE}" />';
		$ntf_fields_temps['n_registred_users'] = PL_NEWS_DESIGN_EDIT_USER_LINK_TAG_VAL;
		$ntf_fields_temps['n_edit_link'] = PL_NEWS_DESIGN_EDIT_EDIT_DEFAULT;
		$ntf_fields_temps['n_prev_link'] = PL_NEWS_DESIGN_EDIT_PREV_LINK_TAG_VAL;
		$ntf_fields_temps['n_next_link'] = PL_NEWS_DESIGN_EDIT_NEXT_LINK_TAG_VAL;

	    $ntf_categs_temps = array();

	    $_GET['id'] = '';
	}

    echo '<script>
	        function checkValues()
            {
                var el_title = sbGetE("ntf_title");
                if (el_title.value == "")
                {
          	         alert("'.PL_NEWS_DESIGN_NO_TITLE_MSG.'");
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

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_news_design_full_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB1);

	$layout->addField(PL_NEWS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ntf_title, 'ntf_title', '', 'style="width:450px;"', true));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ntf_lang');
	$fld->mSelOptions = array($ntf_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_NEWS_DESIGN_EDIT_LANG, $fld);

	$layout->addPluginFieldsTempsCheckboxes('pl_news', $ntf_checked, 'ntf_checked');

    fVoting_Design_Get($layout, $ntf_votes_id, 'ntf_votes_id');

    fComments_Design_Get($layout, $ntf_comments_id, 'ntf_comments_id');

    fClouds_Design_Get($layout, $ntf_tags_list_id, 'ntf_tags_list_id', 'element');

    fSite_Users_Design_Get($layout, $ntf_user_data_id, 'ntf_user_data_id');

	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB2);
	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB2);

	$tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
	$tags_values = array(PL_NEWS_DESIGN_EDIT_ID_TAG, PL_NEWS_DESIGN_EDIT_ELEM_URL_TAG, PL_NEWS_DESIGN_EDIT_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CAT_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG, PL_NEWS_DESIGN_EDIT_CAT_TITLE_TAG);

	$fld = new sbLayoutTextarea($ntf_fields_temps['n_date'], 'ntf_fields_temps[n_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_NEWS_EDIT_DATE, $fld);

	$fld = new sbLayoutTextarea($ntf_fields_temps['n_last_date'], 'ntf_fields_temps[n_last_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_NEWS_EDIT_LAST_DATE, $fld);

	//Макет ссылки "Редактировать"
	$fld = new sbLayoutTextarea($ntf_fields_temps['n_edit_link'], 'ntf_fields_temps[n_edit_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{EDIT_LINK}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_EDIT_TAG), $tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_EDIT, $fld);

	$fld = new sbLayoutTextarea($ntf_fields_temps['n_short_foto'], 'ntf_fields_temps[n_short_foto]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{IMG_LINK}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_FOTO_TAG), $tags_values);
	$layout->addField(PL_NEWS_EDIT_ANONS_FOTO, $fld);

	$fld = new sbLayoutTextarea($ntf_fields_temps['n_full_foto'], 'ntf_fields_temps[n_full_foto]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{IMG_LINK}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_FOTO_TAG), $tags_values);
	$layout->addField(PL_NEWS_EDIT_FULL_FOTO, $fld);

	// Ссылка на предыдущую новость:
	$fld = new sbLayoutTextarea($ntf_fields_temps['n_prev_link'], 'ntf_fields_temps[n_prev_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_EDIT_PREV_LINK_TAG_VAL, '{PREV_TITLE}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_PREV_LINK_TAG, PL_NEWS_DESIGN_EDIT_PREV_TITLE_TAG), $tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_PREV_LINK_TAG, $fld);

	// Ссылка на следующую новость:
	$fld = new sbLayoutTextarea($ntf_fields_temps['n_next_link'], 'ntf_fields_temps[n_next_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_EDIT_NEXT_LINK_TAG_VAL, '{NEXT_TITLE}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_NEXT_LINK_TAG, PL_NEWS_DESIGN_EDIT_NEXT_TITLE_TAG), $tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_NEXT_LINK_TAG, $fld);

	// Ссылка для вывода новостей пользователя
	$fld = new sbLayoutTextarea($ntf_fields_temps['n_registred_users'], 'ntf_fields_temps[n_registred_users]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_EDIT_USER_LINK_TAG_VAL), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_USER_LINK_TAG), $tags_values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_USER_LINK_TAG, $fld);

    $layout->addPluginFieldsTemps('pl_news', $ntf_fields_temps, 'ntf_', $tags, $tags_values);

	$cat_tags = array();
	$cat_tags_values = array();
	$layout->getPluginFieldsTags('pl_news', $cat_tags, $cat_tags_values, true);

	$news_tags = array();
	$news_tags_values = array();
	$layout->getPluginFieldsTags('pl_news', $news_tags, $news_tags_values);

	if (count($cat_tags) != 0)
	{
    	$layout->addTab(PL_NEWS_DESIGN_EDIT_TAB3);
    	$layout->addHeader(PL_NEWS_DESIGN_EDIT_TAB3);

    	$layout->addPluginFieldsTemps('pl_news', $ntf_categs_temps, 'ntf_', $tags, $tags_values, true);
	}

	$layout->addTab(PL_NEWS_DESIGN_EDIT_FULL_TAG);
	$layout->addHeader(PL_NEWS_DESIGN_EDIT_FULL_TAG);

	$fld = new sbLayoutTextarea($ntf_element, 'ntf_element', '', 'style="width:100%;height:400px;"');
	$fld->mTags = array_merge(array('-', '{ID}', '{ELEM_URL}', '{TITLE}', '{DATE}', '{CHANGE_DATE}', '{EDIT_LINK}', '{SHORT}', '{SHORT_FOTO}', '{FULL}', '{FULL_FOTO}', '{TAGS}', '{USER_DATA}', '{ELEM_USER_LINK}', '{NEWS_PREV}', '{NEWS_NEXT}'),
							  $news_tags, array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}', '-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}'),
							  array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);

    $fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_NEWS_GROUP_TAG, PL_NEWS_DESIGN_EDIT_ID_TAG, PL_NEWS_DESIGN_EDIT_ELEM_URL_TAG, PL_NEWS_DESIGN_EDIT_TITLE_TAG, PL_NEWS_DESIGN_EDIT_DATE_TAG, PL_NEWS_DESIGN_EDIT_CHANGE_DATE_TAG,
                                      PL_NEWS_DESIGN_EDIT_EDIT, PL_NEWS_DESIGN_EDIT_SHORT_TAG, PL_NEWS_DESIGN_EDIT_SHORT_FOTO_TAG, PL_NEWS_DESIGN_EDIT_FULL_TAG, PL_NEWS_DESIGN_EDIT_FULL_FOTO_TAG, PL_NEWS_DESIGN_EDIT_TAGS_LIST_TAG,
                                      PL_NEWS_DESIGN_EDIT_USER_DATA_TAG, PL_NEWS_DESIGN_EDIT_USER_LINK_TAG, PL_NEWS_DESIGN_EDIT_PREV_LINK_TAG, PL_NEWS_DESIGN_EDIT_NEXT_LINK_TAG),
    							$news_tags_values,
    							array(PL_NEWS_DESIGN_COMMENTS_TAG, PL_NEWS_DESIGN_COUNT_COMMENTS_TAG, PL_NEWS_DESIGN_LIST_COMMENTS_TAG, PL_NEWS_DESIGN_FORM_COMMENTS_TAG, PL_NEWS_DESIGN_EDIT_RATING_GROUP, PL_NEWS_DESIGN_EDIT_RATING,
    							      PL_NEWS_DESIGN_EDIT_VOTES_COUNT, PL_NEWS_DESIGN_EDIT_VOTES_SUM, PL_NEWS_DESIGN_EDIT_VOTES_FORM),
    							array(PL_NEWS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_NEWS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CATEG_NUM_TAG, PL_NEWS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_NEWS_DESIGN_EDIT_CATEG_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG),
    							$cat_tags_values);

	$layout->addField(PL_NEWS_DESIGN_EDIT_FULL_TAG, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fNews_Design_Full_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_full'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ntf_checked = array();
    $ntf_lang = SB_CMS_LANG;
    $ntf_fields_temps = array();
    $ntf_categs_temps = array();
    $ntf_votes_id = 0;
    $ntf_comments_id = 0;
	$ntf_user_data_id = -1;
	$ntf_tags_list_id = -1;

    extract($_POST);

    if ($ntf_title == '')
    {
        sb_show_message(PL_NEWS_DESIGN_EDIT_NO_TITLE_MSG, false, 'warning');
        fNews_Design_Full_Edit();
        return;
    }

    $row = array();
    $row['ntf_title'] = $ntf_title;
    $row['ntf_lang'] = $ntf_lang;
    $row['ntf_checked'] = implode(' ', $ntf_checked);
    $row['ntf_element'] = $ntf_element;
    $row['ntf_fields_temps'] = serialize($ntf_fields_temps);
    $row['ntf_categs_temps'] = serialize($ntf_categs_temps);
    $row['ntf_comments_id'] = $ntf_comments_id;
    $row['ntf_votes_id'] = $ntf_votes_id;
    $row['ntf_user_data_id'] = $ntf_user_data_id;
    $row['ntf_tags_list_id'] = $ntf_tags_list_id;

    if ($_GET['id'] != '')
	{
	    $res = sql_query('SELECT ntf_title FROM sb_news_temps_full WHERE ntf_id=?d', $_GET['id']);
	    if ($res)
	    {
	        // редактирование
	        list($old_title) = $res[0];

    	    sql_query('UPDATE sb_news_temps_full SET ?a WHERE ntf_id=?d', $row, $_GET['id'], sprintf(PL_NEWS_DESIGN_EDIT_OK, $old_title));
    	    sbQueryCache::updateTemplate('sb_news_temps_full', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
    	    if (!$footer_ar)
    	    {
    	        sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_NEWS_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fNews_Design_Full_Edit();
    		    return;
    	    }

    	    $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

    	    $row['ntf_id'] = intval($_GET['id']);

            $html_str = fNews_Design_Full_Get($row);
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
    	        fNews_Design_Full_Edit($html_str, $footer_str);
    	    }
	    }
	    else
	    {
	        sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_DESIGN_EDIT_SYSTEMLOG_ERROR, $ntf_title), SB_MSG_WARNING);

            fNews_Design_Full_Edit();
		    return;
	    }
	}
	else
	{
	    $error = true;
	    if (sql_query('INSERT INTO sb_news_temps_full (?#) VALUES (?a)', array_keys($row), array_values($row)))
	    {
    		$id = sql_insert_id();
    		sbQueryCache::updateTemplate('sb_news_temps_full', $id);

    		if (fCategs_Add_Elem($id, 'design_edit'))
    		{

                sb_add_system_message(sprintf(PL_NEWS_DESIGN_FULL_ADD_OK, $ntf_title));

                echo '<script>
        				sbReturnValue('.$id.');
        			  </script>';

        		$error = false;
    		}
    		else
    		{
    		    sql_query('DELETE FROM sb_news_temps_full WHERE ntf_id=?d', $id);
    		}
	    }

	    if ($error)
	    {
	        sb_show_message(sprintf(PL_NEWS_DESIGN_ADD_ERROR, $ntf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_DESIGN_ADD_SYSTEMLOG_ERROR, $ntf_title), SB_MSG_WARNING);

            fNews_Design_Full_Edit();
		    return;
	    }
	}
}

function fNews_Design_Full_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id LIMIT 1', 'page', $id, 'pl_news_full');

    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id LIMIT 1', 'temp', $id, 'pl_news_full');
    }

    if ($pages || $temps)
    {
        echo PL_NEWS_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна вывода разделов
 */
function fNews_Design_Categs_Get($args)
{
    return fCategs_Design_Get($args, 'pl_news_categs', 'pl_news');
}

function fNews_Design_Categs()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_list', 'ctl_id', 'ctl_title', 'fNews_Design_Categs_Get', 'pl_news_design_categs', 'pl_news_design_categs');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_news_categs_32.png';

    $elems->addSorting(PL_NEWS_DESIGN_CATEGS_SORT_BY_TITLE, 'ctl_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_news_design_categs_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_news_design_categs_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_news_design_categs_delete';

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_news_categs";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_news_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function newsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_NEWS_DESIGN_CATEGS_NEWSLIST_ITEM, 'newsList();', false);

    $elems->init();
}

function fNews_Design_Categs_Edit()
{
	fCategs_Design_Edit('pl_news_design_categs', 'pl_news', 'pl_news_design_categs_edit_submit');
}

function fNews_Design_Categs_Edit_Submit()
{
	fCategs_Design_Edit_Submit('pl_news_design_categs', 'pl_news', 'pl_news_design_categs_edit_submit', 'pl_news_categs', 'pl_news');
}

function fNews_Design_Categs_Delete()
{
    fCategs_Design_Delete('pl_news_categs', 'pl_news');
}


/**
 * Функции управления макетами дизайна вывода выбранного раздела
 */
function fNews_Design_Selcat_Get($args)
{
	return fCategs_Design_Get($args, 'pl_news_sel_cat', 'pl_news', true);
}

function fNews_Design_Selcat()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_full', 'ctf_id', 'ctf_title', 'fNews_Design_Selcat_Get', 'pl_news_design_sel_cat', 'pl_news_design_sel_cat');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_news_sel_cat_32.png';

    $elems->addSorting(PL_NEWS_DESIGN_CATEGS_SORT_BY_TITLE, 'ctf_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_news_design_sel_cat_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_news_design_sel_cat_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_news_design_sel_cat_delete';

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_news_sel_cat";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_news_sel_cat";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function newsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_NEWS_DESIGN_CATEGS_NEWSLIST_ITEM, 'newsList();', false);

    $elems->init();
}

function fNews_Design_Selcat_Edit()
{
    fCategs_Design_Sel_Cat_Edit('pl_news_design_sel_cat', 'pl_news', 'pl_news_design_sel_cat_edit_submit');
}

function fNews_Design_Selcat_Edit_Submit()
{
    fCategs_Design_Sel_Cat_Edit_Submit('pl_news_design_sel_cat', 'pl_news', 'pl_news_design_sel_cat_edit_submit', 'pl_news_sel_cat', 'pl_news');
}

function fNews_Design_Selcat_Delete()
{
    fCategs_Design_Delete('pl_news_sel_cat', 'pl_news');
}


/**
 *
 * Функции управления макетами дизайна вывода формы добавления
 */
function fNews_Design_Form_Get($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sntf_title'].'</a></b>
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

	$id = intval($args['sntf_id']);
	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND (elems.e_ident=? OR elems.e_ident=?) AND pages.p_id=elems.e_p_id ORDER BY pages.p_name LIMIT 4', 'page', $id, 'pl_news_form', 'pl_news_form_edit');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND (elems.e_ident=? OR elems.e_ident=?) AND temps.t_id=elems.e_p_id ORDER BY temps.t_name LIMIT 4', 'temp', $id, 'pl_news_form', 'pl_news_form_edit');
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

function fNews_Design_Form()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
	$elems = new sbElements('sb_news_temps_form', 'sntf_id', 'sntf_title', 'fNews_Design_Form_Get', 'pl_news_design_form', 'pl_news_form');
    $elems->mCategsDeleteWithElementsMenu = true;

	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->addField('sntf_title');      // название макета дизайна
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_news_form_32.png';

    $elems->addSorting(PL_NEWS_DESIGN_FORM_SORT_BY_TITLE, 'sntf_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_news_design_form_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_news_design_form_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_news_design_form_delete';

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

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_news_form";
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

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_news_form";
			strAttr = "resizable=1,width=800,height=600";
			sbShowModalDialog(strPage, strAttr, null, window);
		}

		function newsList()
        {
        	window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
		}';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_NEWS_NEWSFEED_MENU, 'newsList();', false);

	$elems->init();
}

function fNews_Design_Form_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_form'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
        $result = sql_query('SELECT sntf_title, sntf_lang, sntf_form, sntf_fields_temps, sntf_categs_temps, sntf_messages
                                    FROM sb_news_temps_form WHERE sntf_id=?d', $_GET['id']);
        if ($result)
        {
            list($sntf_title, $sntf_lang, $sntf_form, $sntf_fields_temps, $sntf_categs_temps, $sntf_messages) = $result[0];
        }
        else
        {
            sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sntf_fields_temps != '')
            $sntf_fields_temps = unserialize($sntf_fields_temps);
        else
            $sntf_fields_temps = array();

        if ($sntf_categs_temps != '')
            $sntf_categs_temps = unserialize($sntf_categs_temps);
        else
            $sntf_categs_temps = array();

        if ($sntf_messages != '')
            $sntf_messages = unserialize($sntf_messages);
        else
            $sntf_messages = array();
        if (!isset($sntf_messages['success_edit_news']))
        	$sntf_messages['success_edit_news'] = PL_NEWS_DESIGN_FORM_SUCCESS_EDIT_MSG;
        if (!isset($sntf_fields_temps['news_short_foto_now']))
        	$sntf_fields_temps['news_short_foto_now'] = SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG;
        if (!isset($sntf_fields_temps['news_full_foto_now']))
        	$sntf_fields_temps['news_full_foto_now'] = SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG;
        if (!isset($sntf_fields_temps['news_date']))
        	$sntf_fields_temps['news_date'] = PL_NEWS_DESIGN_FORM_NEWS_DATE_FIELD;
        if (!isset($sntf_fields_temps['news_url']))
        	$sntf_fields_temps['news_url'] = PL_NEWS_DESIGN_FORM_NEWS_URL_FIELD;
        if (!isset($sntf_fields_temps['news_sort']))
        	$sntf_fields_temps['news_sort'] = PL_NEWS_DESIGN_FORM_NEWS_SORT_FIELD;
        if (!isset($sntf_messages['err_edit_news']))
        	$sntf_messages['err_edit_news'] = PL_NEWS_DESIGN_FORM_ERR_EDIT_MSG;
        if (!isset($sntf_messages['err_edit_user_field']))
        	$sntf_messages['err_edit_user_field'] = PL_NEWS_DESIGN_FORM_ERR_USER_ERROR;
        if (!isset($sntf_messages['err_add_user_field']))
        	$sntf_messages['err_add_user_field'] = PL_NEWS_DESIGN_FORM_ERR_USER_ERROR_ADD;
    }
    else if (count($_POST) > 0)
    {
        $sntf_categs_temps = array();

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
		$sntf_lang = SB_CMS_LANG;
        $sntf_title = $sntf_form = '';

        $sntf_categs_temps = array();
        $sntf_fields_temps = array();
		$sntf_fields_temps['date_temps'] = '{DAY}.{MONTH}.{LONG_YEAR}';
 		$sntf_fields_temps['news_title'] = PL_NEWS_DESIGN_FORM_NEWS_TITLE_FIELD;
		$sntf_fields_temps['news_short'] = PL_NEWS_DESIGN_FORM_NEWS_SHORT_FIELD;
		$sntf_fields_temps['news_short_foto'] = PL_NEWS_DESIGN_FORM_NEWS_SHORT_FOTO_FIELD;
		$sntf_fields_temps['news_full'] = PL_NEWS_DESIGN_FORM_NEWS_FULL_FIELD;
		$sntf_fields_temps['news_full_foto'] = PL_NEWS_DESIGN_FORM_NEWS_FULL_FOTO_FIELD;
		$sntf_fields_temps['news_categs_list'] = PL_NEWS_DESIGN_FORM_NEWS_CATEGS_LIST_FIELD;
		$sntf_fields_temps['news_categs_list_options'] = PL_NEWS_DESIGN_FORM_NEWS_CATEGS_OPTIONS;
		$sntf_fields_temps['tags'] = PL_NEWS_DESIGN_FORM_NEWS_TAGS_FIELD;
		$sntf_fields_temps['captcha'] = PL_NEWS_DESIGN_FORM_NEWS_CAPTCHA;
		$sntf_fields_temps['img_captcha'] = PL_NEWS_DESIGN_FORM_IMG_CAPTCHA_FIELD;
		$sntf_fields_temps['select_start'] = '<span style="color:red;">';
		$sntf_fields_temps['select_end'] = '</span>';
		$sntf_fields_temps['news_date_val'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
		$sntf_fields_temps['news_title_val'] = '{VALUE}';
		$sntf_fields_temps['news_short_val'] = '{VALUE}';
		$sntf_fields_temps['news_short_foto_val'] = '{VALUE}';
		$sntf_fields_temps['news_full_val'] = '{VALUE}';
		$sntf_fields_temps['news_full_foto_val'] = '{VALUE}';
		$sntf_fields_temps['news_pub_start_val'] = '{VALUE}';
		$sntf_fields_temps['news_pub_end_val'] = '{VALUE}';
		$sntf_fields_temps['news_tags_val'] = '{VALUE}';
		$sntf_fields_temps['news_full_foto_now'] = SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG;
		$sntf_fields_temps['news_short_foto_now'] = SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG;
		$sntf_fields_temps['news_date'] = PL_NEWS_DESIGN_FORM_NEWS_DATE_FIELD;
		$sntf_fields_temps['news_url'] = PL_NEWS_DESIGN_FORM_NEWS_URL_FIELD;
		$sntf_fields_temps['news_sort'] = PL_NEWS_DESIGN_FORM_NEWS_SORT_FIELD;

		$sntf_messages = array();
		$sntf_messages['success_add_news'] = PL_NEWS_DESIGN_FORM_SUCCESS_ADD_MSG;
		$sntf_messages['success_edit_news'] = PL_NEWS_DESIGN_FORM_SUCCESS_EDIT_MSG;
		$sntf_messages['err_add_news'] = PL_NEWS_DESIGN_FORM_ERR_ADD_MSG;
		$sntf_messages['err_edit_news'] = PL_NEWS_DESIGN_FORM_ERR_EDIT_MSG;
		$sntf_messages['err_add_necessary_field'] = PL_NEWS_DESIGN_FORM_ERR_FIELDS_MSG;
		$sntf_messages['err_save_file'] = PL_NEWS_DESIGN_FORM_ERR_SAVE_MSG;
		$sntf_messages['not_have_rights_add'] = PL_NEWS_DESIGN_FORM_ERR_RIGHTS_MSG;
		$sntf_messages['err_size_too_large'] = PL_NEWS_DESIGN_FORM_ERR_SIZE_FILE_MSG;
		$sntf_messages['err_captcha_code'] = PL_NEWS_DESIGN_FORM_ERR_CODE_MSG;
		$sntf_messages['err_type_file'] = PL_NEWS_DESIGN_FORM_ERR_TYPE_FILE_MSG;
		$sntf_messages['err_img_size'] = PL_NEWS_DESIGN_FORM_ERR_SIZE_IMG;
		$sntf_messages['err_edit_user_field'] = PL_NEWS_DESIGN_FORM_ERR_USER_ERROR;
		$sntf_messages['err_add_user_field'] = PL_NEWS_DESIGN_FORM_ERR_USER_ERROR_ADD;

		$_GET['id'] = '';
	}

	echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sntf_title");
                if (el_title.value == "")
                {
                     alert("'.PL_NEWS_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_news_design_form_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_NEWS_EDIT_TAB1);
    $layout->addHeader(PL_NEWS_EDIT_TAB1);

    $layout->addField(PL_NEWS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sntf_title, 'sntf_title', '', 'style="width:97%;"', true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sntf_fields_temps['date_temps'], 'sntf_fields_temps[date_temps]', '', 'style="width:100%; height:70px;"');
    $fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_NEWS_DESIGN_FORM_DATE_TEMP, $fld);

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sntf_lang');
    $fld->mSelOptions = array($sntf_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_NEWS_DESIGN_EDIT_LANG, $fld);

    $layout->addTab(PL_NEWS_DESIGN_FORM_FIELDS_TAB);
    $layout->addHeader(PL_NEWS_DESIGN_FORM_FIELDS_TAB);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_EDIT_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));

	$layout->addField('', new sbLayoutDelim());

    $tags = array('{VALUE}');
    $values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

	//	Заголовок новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_title'], 'sntf_fields_temps[news_title]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_TITLE_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_EDIT_TITLE.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_title_need]"'.(isset($sntf_fields_temps['news_title_need']) && $sntf_fields_temps['news_title_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// Дата новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_date'], 'sntf_fields_temps[news_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_DATE_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_EDIT_DATE.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_date_need]"'.(isset($sntf_fields_temps['news_date_need']) && $sntf_fields_temps['news_date_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// ЧПУ
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_url'], 'sntf_fields_temps[news_url]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_URL_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_DESIGN_EDIT_ELEM_URL_TAG.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_url_need]"'.(isset($sntf_fields_temps['news_url_need']) && $sntf_fields_temps['news_url_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// Индекс сортировки
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_sort'], 'sntf_fields_temps[news_sort]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_SORT_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_EDIT_SORT.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_sort_need]"'.(isset($sntf_fields_temps['news_sort_need']) && $sntf_fields_temps['news_sort_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// Анонс новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_short'], 'sntf_fields_temps[news_short]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_SHORT_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_DESIGN_EDIT_ELEMENT.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_short_need]"'.(isset($sntf_fields_temps['news_short_need']) && $sntf_fields_temps['news_short_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// Фото для анонса новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_short_foto'], 'sntf_fields_temps[news_short_foto]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_SHORT_FOTO_FIELD, PL_NEWS_DESIGN_FORM_NEWS_SHORT_FOTO_DELETE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, PL_NEWS_DESIGN_FORM_NEWS_SHORT_PHOTO_DELETE_TAG), $values);
    $layout->addField(PL_NEWS_DESIGN_EDIT_SHORT_FOTO_TAG.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_short_foto_need]"'.(isset($sntf_fields_temps['news_short_foto_need']) && $sntf_fields_temps['news_short_foto_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    // Отображение существующего фото для анонса новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_short_foto_now'], 'sntf_fields_temps[news_short_foto_now]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_FIELD);
    $layout->addField(PL_NEWS_DESIGN_EDIT_SHORT_FOTO_NOW_TAG, $fld);

	//	Полный текст новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_full'], 'sntf_fields_temps[news_full]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_FULL_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_EDIT_FULL.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_full_need]"'.(isset($sntf_fields_temps['news_full_need']) && $sntf_fields_temps['news_full_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// Фото для полного текста новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_full_foto'], 'sntf_fields_temps[news_full_foto]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_FULL_FOTO_FIELD, PL_NEWS_DESIGN_FORM_NEWS_FULL_FOTO_DELETE_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, PL_NEWS_DESIGN_FORM_NEWS_FULL_PHOTO_DELETE_TAG), $values);
	$layout->addField(PL_NEWS_EDIT_FULL_FOTO.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_full_foto_need]"'.(isset($sntf_fields_temps['news_full_foto_need']) && $sntf_fields_temps['news_full_foto_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	// Отображение существующего фото для полного текста новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_full_foto_now'], 'sntf_fields_temps[news_full_foto_now]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG);
	$fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_FIELD);
	$layout->addField(PL_NEWS_EDIT_FULL_FOTO_NOW, $fld);

    // Тематические теги
    $fld = new sbLayoutTextarea($sntf_fields_temps['tags'], 'sntf_fields_temps[tags]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_DESIGN_FORM_NEWS_TAGS_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(KERNEL_THEME_TAGS.'<br><input type="checkbox" value="1" name="sntf_fields_temps[tags_need]"'.(isset($sntf_fields_temps['tags_need']) && $sntf_fields_temps['tags_need'] == 1 ? 'checked' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

    // Список разделов
    $fld = new sbLayoutTextarea($sntf_fields_temps['news_categs_list'], 'sntf_fields_temps[news_categs_list]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_NEWS_DESIGN_FORM_NEWS_CATEGS_LIST_FIELD, '{OPTIONS}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
    $layout->addField(PL_NEWS_DESIGN_FORM_NEWS_CATEGS_LIST_TAG.'<br><input type="checkbox" value="1" name="sntf_fields_temps[news_categs_list_need]" '.(isset($sntf_fields_temps['news_categs_list_need']) && $sntf_fields_temps['news_categs_list_need'] == 1 ? 'checked="checked"' : '').'>'.SB_LAYOUT_PLUGIN_FIELDS_REQ, $fld);

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_categs_list_options'], 'sntf_fields_temps[news_categs_list_options]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array(PL_NEWS_DESIGN_FORM_NEWS_CATEGS_OPTIONS, PL_NEWS_DESIGN_FORM_NEWS_CATEGS_CHECKBOXES, '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_SELECTED}');
	$fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_SELECT, SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_CHECKBOX, PL_NEWS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CATEG_ID_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
	$layout->addField('', $fld);

	// Поле ввода кода с картинки (CAPTCHA)
    $fld = new sbLayoutTextarea($sntf_fields_temps['captcha'], 'sntf_fields_temps[captcha]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array(PL_NEWS_DESIGN_FORM_NEWS_CAPTCHA);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_NEWS_DESIGN_FORM_CAPTCHA_FIELD_LABEL, $fld);

    // Картинка с кодом (CAPTCHA)
    $fld = new sbLayoutTextarea($sntf_fields_temps['img_captcha'], 'sntf_fields_temps[img_captcha]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array(PL_NEWS_DESIGN_FORM_IMG_CAPTCHA_FIELD);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_NEWS_DESIGN_FORM_IMG_CAPTCHA_FIELD_LABEL, $fld);

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_news');
    if ($res)
    {
		list($pd_fields, $pd_categs) = $res[0];
		if ($pd_fields != '')
		{
			$pd_fields = unserialize($pd_fields);
			if (isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
            {
				$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_FORM_USERS_FIELDS.'</div>';
				$layout->addField('', new sbLayoutHTML($html, true));
				$layout->addField('', new sbLayoutDelim());
            }

			// Пользовательские поля
			$layout->addPluginInputFieldsTemps('pl_news', $sntf_fields_temps, 'sntf_', '', array(), array(), false, true, '', '', false, true);
		}
	}

	$layout->addTab(PL_NEWS_DESIGN_FORM_FORM_LABEL);
	$layout->addHeader(PL_NEWS_DESIGN_FORM_FORM_LABEL);

	$user_tags = array();
	$user_tags_values = array();
	$layout->getPluginFieldsTags('pl_news', $user_tags, $user_tags_values, false, true, true, false, true, true);

	$fld = new sbLayoutTextarea($sntf_form, 'sntf_form', '', 'style="width:100%; height:250px;"');
	$fld->mTags = array_merge(array('-', '{MESSAGES}', PL_NEWS_DESIGN_FORM_HTML_FORM_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_TITLE_TAG, PL_NEWS_DESIGN_FORM_NEWS_DATE_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_URL_TAG, PL_NEWS_DESIGN_FORM_NEWS_SORT_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_SHORT_TAG,PL_NEWS_DESIGN_FORM_NEWS_SHORT_FOTO_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_SHORT_FOTO_NOW_TAG, PL_NEWS_DESIGN_FORM_NEWS_FULL_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_FULL_FOTO_TAG, PL_NEWS_DESIGN_FORM_NEWS_FULL_FOTO_NOW_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_CATEGS_TAG,PL_NEWS_DESIGN_FORM_NEWS_TAGS_TAG,
			PL_NEWS_DESIGN_FORM_NEWS_CAPTCHA_TAG, '{CAPTCHA_IMG}'), $user_tags);

	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_TAB1,
				    		PL_NEWS_DESIGN_FORM_SYS_MESSAGES_TAG_VALUE,
				    		PL_NEWS_DESIGN_FORM_HTML_FORM_TAG_VALUE,
				    		PL_NEWS_DESIGN_FORM_TITLE_TAG_VALUE,
				    		PL_NEWS_DESIGN_FORM_DATE_TAG_VALUE,
				    		PL_NEWS_DESIGN_FORM_URL_TAG_VALUE,
				    		PL_NEWS_DESIGN_FORM_SORT_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_SHORT_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_SHORT_FOTO_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_SHORT_FOTO_NOW_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_FULL_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_FULL_FOTO_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_FULL_FOTO_NOW_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_CATEGS_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_TAGS_TAG_VALUE,
							PL_NEWS_DESIGN_FORM_CAPTCHA_TAG_VALUE,
				            PL_NEWS_DESIGN_FORM_CAPTCHA_IMG_TAG_VALUE), $user_tags_values);
	$layout->addField(PL_NEWS_DESIGN_FORM_FORM_LABEL , $fld);

	$layout->addField(PL_NEWS_DESIGN_FORM_SELECT_START, new sbLayoutTextarea($sntf_fields_temps['select_start'], 'sntf_fields_temps[select_start]', '', 'style="width:100%; height:60px;"'));
	$layout->addField(PL_NEWS_DESIGN_FORM_SELECT_END, new sbLayoutTextarea($sntf_fields_temps['select_end'], 'sntf_fields_temps[select_end]', '', 'style="width:100%; height:60px;"'));

	$layout->addTab(PL_NEWS_DESIGN_FORM_MESSAGES_FIEDLS_TAB);
	$layout->addHeader(PL_NEWS_DESIGN_FORM_MESSAGES_FIEDLS_TAB);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_EDIT_TAB1.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_date_val'], 'sntf_fields_temps[news_date_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_NEWS_DATE, $fld);

	$tags_fileds = array('{VALUE}');
	$values_fields = array(PL_NEWS_DESIGN_FORM_VALUE_FIELD);

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_title_val'], 'sntf_fields_temps[news_title_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
	$layout->addField(PL_NEWS_EDIT_TITLE, $fld);

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_short_val'], 'sntf_fields_temps[news_short_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
    $layout->addField(PL_NEWS_DESIGN_EDIT_ELEMENT, $fld);

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_short_foto_val'], 'sntf_fields_temps[news_short_foto_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
	$layout->addField(PL_NEWS_EDIT_ANONS_FOTO, $fld);

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_full_val'], 'sntf_fields_temps[news_full_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
	$layout->addField(PL_NEWS_EDIT_FULL, $fld);

	$fld = new sbLayoutTextarea($sntf_fields_temps['news_full_foto_val'], 'sntf_fields_temps[news_full_foto_val]', '', 'style="width:100%; height:40px;"');
	$fld->mTags = $tags_fileds;
	$fld->mValues = $values_fields;
	$layout->addField(PL_NEWS_EDIT_FULL_FOTO, $fld);

	if (isset($pd_fields))
	{
		if (isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
		{
			$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_FORM_USERS_FIELDS.'</div>';
			$layout->addField('', new sbLayoutHTML($html, true));
			$layout->addField('', new sbLayoutDelim());
		}
		$layout->addPluginFieldsTemps('pl_news', $sntf_fields_temps, 'sntf_', array(), array(), false, '', '', '_val', false, true);
    }

	if (isset($pd_categs))
	{
		$layout->addPluginFieldsTemps('pl_news', $sntf_categs_temps, 'sntf_', array(), array(), true, '', '', '_cat_val', false, true);
	}

	$layout->addTab(PL_NEWS_DESIGN_FORM_MESSAGES_TAB);
	$layout->addHeader(PL_NEWS_DESIGN_FORM_MESSAGES_TAB);

	$tags = array('{TITLE}', '{DATE}', '{SHORT}', '{SHORT_FOTO}', '{FULL}', '{FULL_FOTO}');
	$values = array(PL_NEWS_EDIT_TITLE, PL_NEWS_EDIT_DATE, PL_NEWS_EDIT_ANONS, PL_NEWS_EDIT_ANONS_FOTO, PL_NEWS_EDIT_FULL, PL_NEWS_EDIT_FULL_FOTO);

	$users_tags = array();
	$users_tags_value = array();
	$layout->getPluginFieldsTags('pl_news', $users_tags, $users_tags_value, false, false, false, false, true);

	$users_cat_tags = array();
	$users_cat_tags_value = array();
	$layout->getPluginFieldsTags('pl_news', $users_cat_tags, $users_cat_tags_value, true, false, false, false, true);

	$fld = new sbLayoutTextarea($sntf_messages['success_add_news'], 'sntf_messages[success_add_news]', '', 'style="width:100%; height:70px;"');
	$fld->mTags = array_merge($tags, $users_tags);
	$fld->mValues = array_merge($values, $users_tags_value);
	$layout->addField(PL_NEWS_DESIGN_FORM_MSG_SUCCESS_ADD, $fld);

	//Сообщение об успешном редактировании
	$fld = new sbLayoutTextarea($sntf_messages['success_edit_news'], 'sntf_messages[success_edit_news]', '', 'style="width:100%; height:70px;"');
	$fld->mTags = array_merge($tags, $users_tags);
	$fld->mValues = array_merge($values, $users_tags_value);
	$layout->addField(PL_NEWS_DESIGN_FORM_MSG_SUCCESS_EDIT, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($sntf_messages['err_add_news'], 'sntf_messages[err_add_news]', '', 'style="width:100%; height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
	$layout->addField(PL_NEWS_DESIGN_FORM_MSG_ERR, $fld);

	$fld = new sbLayoutTextarea($sntf_messages['err_edit_news'], 'sntf_messages[err_edit_news]', '', 'style="width:100%; height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($sntf_messages['err_add_user_field'], 'sntf_messages[err_add_user_field]', '', 'style="width:100%; height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
    $layout->addField('', $fld);

	$fld = new sbLayoutTextarea($sntf_messages['err_edit_user_field'], 'sntf_messages[err_edit_user_field]', '', 'style="width:100%; height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sntf_messages['err_add_necessary_field'], 'sntf_messages[err_add_necessary_field]', '', 'style="width:100%; height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sntf_messages['err_save_file'], 'sntf_messages[err_save_file]', '', 'style="width:100%; height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}'), $tags);
    $fld->mValues = array_merge(array(PL_NEWS_DESIGN_FORM_FILE_NAME_TAG), $values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sntf_messages['not_have_rights_add'], 'sntf_messages[not_have_rights_add]', '', 'style="width:100%; height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sntf_messages['err_size_too_large'], 'sntf_messages[err_size_too_large]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}', '{FILE_SIZE}'), $tags);
    $fld->mValues = array_merge(array(PL_NEWS_DESIGN_FORM_FILE_NAME_TAG, PL_NEWS_DESIGN_FORM_FILE_SIZE_TAG), $values);
	$layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sntf_messages['err_captcha_code'], 'sntf_messages[err_captcha_code]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $tags;
	$fld->mValues = $values;
    $layout->addField('', $fld);

	$fld = new sbLayoutTextarea($sntf_messages['err_type_file'], 'sntf_messages[err_type_file]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = array_merge(array('{FILE_NAME}', '{FILES_TYPES}'), $tags);
	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_FORM_FILE_NAME_TAG, PL_NEWS_DESIGN_FORM_FILES_TYPES_TAG), $values);
	$layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sntf_messages['err_img_size'], 'sntf_messages[err_img_size]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}'), $tags);
    $fld->mValues = array_merge(array(PL_NEWS_DESIGN_FORM_FILE_NAME_TAG, PL_NEWS_DESIGN_FORM_IMG_WIDTH_TAG, PL_NEWS_DESIGN_FORM_IMG_HEIGHT_TAG),$values);
	$layout->addField('', $fld);

	$layout->addTab(PL_NEWS_DESIGN_FORM_LETTERS_TAB);
	$layout->addHeader(PL_NEWS_DESIGN_FORM_LETTERS_TAB);

	$email_tags = array_merge(array('-', '{ID}', '{URL}'), $tags, $users_tags, array('-', '{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}'));
	$email_tags_value = array_merge(array(PL_NEWS_DESIGN_EDIT_TAB1, PL_NEWS_DESIGN_EDIT_ID_TAG, PL_NEWS_DESIGN_EDIT_ELEM_URL_TAG), $values, $users_tags_value, array(PL_NEWS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_NEWS_DESIGN_EDIT_CAT_TITLE_TAG, PL_NEWS_DESIGN_EDIT_CAT_ID_TAG, PL_NEWS_DESIGN_EDIT_CATEG_URL_TAG));

	//тема письма для модераторов (при добавлении)
    $fld = new sbLayoutTextarea((isset($sntf_messages['admin_subj']) ? $sntf_messages['admin_subj'] : ''), 'sntf_messages[admin_subj]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge($email_tags, $users_cat_tags);
    $fld->mValues = array_merge($email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_NEWS_DESIGN_FORM_EDIT_ADMIN_SUBJ, $fld);

    //текст пиьсма для модераторов (при добавлении)
    $fld = new sbLayoutTextarea((isset($sntf_messages['admin_text']) ? $sntf_messages['admin_text'] : ''), 'sntf_messages[admin_text]', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array_merge($email_tags, $users_cat_tags);
    $fld->mValues = array_merge($email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_NEWS_DESIGN_FORM_EDIT_ADMIN_TEXT, $fld);

    //тема письма для пользователя (при редактировании)
    $fld = new sbLayoutTextarea((isset($sntf_messages['admin_subj_edit']) ? $sntf_messages['admin_subj_edit'] : ''), 'sntf_messages[admin_subj_edit]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge($email_tags, $users_cat_tags);
    $fld->mValues = array_merge($email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_NEWS_DESIGN_FORM_EDIT_ADMIN_SUBJ_EDIT, $fld);

    //текст пиьсма для пользователя (при редактировании)
    $fld = new sbLayoutTextarea((isset($sntf_messages['admin_text_edit']) ? $sntf_messages['admin_text_edit'] : ''), 'sntf_messages[admin_text_edit]', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array_merge($email_tags, $users_cat_tags);
    $fld->mValues = array_merge($email_tags_value, $users_cat_tags_value);
    $layout->addField(PL_NEWS_DESIGN_FORM_EDIT_ADMIN_TEXT_EDIT, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '')
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fNews_Design_Form_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_form'))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sntf_title = $sntf_form = '';
    $sntf_lang = SB_CMS_LANG;
    $sntf_fields_temps = array();
    $sntf_categs_temps = array();
    $sntf_messages = array();

	extract($_POST);

	if ($sntf_title == '')
	{
        sb_show_message(PL_NEWS_DESIGN_FORM_SUBMIT_NO_TITLE_MSG, false, 'warning');
        fNews_Design_Form_Edit();
        return;
    }

	$row = array();
    $row['sntf_title'] = $sntf_title;
    $row['sntf_lang'] = $sntf_lang;
    $row['sntf_form'] = $sntf_form;
    $row['sntf_fields_temps'] = serialize($sntf_fields_temps);
    $row['sntf_categs_temps'] = serialize($sntf_categs_temps);
    $row['sntf_messages'] = serialize($sntf_messages);

    if ($_GET['id'] != '')
    {
        $res = sql_query('SELECT sntf_title FROM sb_news_temps_form WHERE sntf_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_query('UPDATE sb_news_temps_form SET ?a WHERE sntf_id=?d', $row, $_GET['id'], sprintf(PL_NEWS_DESIGN_FORM_SUBMIT_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_NEWS_DESIGN_FORM_SUBMIT_ERR_EDIT, $old_title), SB_MSG_WARNING);

                fNews_Design_Form_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sntf_id'] = intval($_GET['id']);
            $html_str = fNews_Design_Form_Get($row);
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
                fNews_Design_Form_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_DESIGN_FORM_SUBMIT_ERR_EDIT, $sntf_title), SB_MSG_WARNING);

            fNews_Design_Form_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_query('INSERT INTO sb_news_temps_form (?#) VALUES (?a)', array_keys($row), array_values($row)))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
				sb_add_system_message(sprintf(PL_NEWS_DESIGN_FORM_SUBMIT_ADD_OK, $sntf_title));
				echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

				$error = false;
            }
            else
			{
				sql_query('DELETE FROM sb_news_temps_form WHERE sntf_id=?', $id);
            }
        }

        if ($error)
        {
			sb_show_message(sprintf(PL_NEWS_DESIGN_FORM_SUBMIT_ADD_ERROR, $sntf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_DESIGN_FORM_SUBMIT_ADD_SYS_ERROR, $sntf_title), SB_MSG_WARNING);

			fNews_Design_Form_Edit();
			return;
		}
	}
}

function fNews_Design_Form_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id LIMIT 1', 'page', $id, 'pl_news_form');

	$temps = false;
	if (!$pages)
	{
    	$temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id LIMIT 1', 'temp', $id, 'pl_news_form');
	}

	if ($pages || $temps)
	{
		echo PL_NEWS_DESIGN_FORM_DELETE_ERROR;
	}
}


/**
 * Функции управления макетами дизайна формы фильтра
 *
 */
function fNews_Design_Filter_Form_Get($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sntf_title'].'</a></b>
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

	$id = intval($args['sntf_id']);
	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id ORDER BY pages.p_name LIMIT 4', 'page', $id, 'pl_news_filter');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id ORDER BY temps.t_name LIMIT 4', 'temp', $id, 'pl_news_filter');
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

function fNews_Design_Filter_Form()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
	$elems = new sbElements('sb_news_temps_form', 'sntf_id', 'sntf_title', 'fNews_Design_Filter_Form_Get', 'pl_news_filter_form', 'pl_news_filter');
    $elems->mCategsDeleteWithElementsMenu = true;

	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->addField('sntf_title');      // название макета дизайна
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_news_filter_form_32.png';

    $elems->addSorting(PL_NEWS_DESIGN_FORM_SORT_BY_TITLE, 'sntf_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_news_filter_form_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_news_filter_form_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_news_filter_form_delete';

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

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_news_filter";
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

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_news_filter";
			strAttr = "resizable=1,width=800,height=600";
			sbShowModalDialog(strPage, strAttr, null, window);
		}

		function newsList()
        {
        	window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_news_init";
		}';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_NEWS_NEWSFEED_MENU, 'newsList();', false);

	$elems->init();
}

function fNews_Design_Filter_Form_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_filter'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
		$result = sql_query('SELECT sntf_title, sntf_lang, sntf_form, sntf_fields_temps
									FROM sb_news_temps_form WHERE sntf_id=?d', $_GET['id']);
        if ($result)
        {
			list($sntf_title, $sntf_lang, $sntf_form, $sntf_fields_temps) = $result[0];
        }
        else
        {
            sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

		if ($sntf_fields_temps != '')
            $sntf_fields_temps = unserialize($sntf_fields_temps);
        else
            $sntf_fields_temps = array();
        if (!isset($sntf_fields_temps['news_sort_select']))
         	$sntf_fields_temps['news_sort_select'] = PL_NEWS_FILTER_FORM_EDIT_SORT_SELECT_FIELD;
    }
    else if (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
		$sntf_lang = SB_CMS_LANG;
		$sntf_title = $sntf_form = '';

		$sntf_fields_temps = array();
		$sntf_fields_temps['date_temps'] = '{DAY}.{MONTH}.{LONG_YEAR}';

		$sntf_fields_temps['news_id'] = PL_NEWS_FILTER_FORM_EDIT_ID_FIELD;
		$sntf_fields_temps['news_id_lo'] = PL_NEWS_FILTER_FORM_EDIT_ID_LO_FIELD;
		$sntf_fields_temps['news_id_hi'] = PL_NEWS_FILTER_FORM_EDIT_ID_HI_FIELD;
		$sntf_fields_temps['news_title'] = PL_NEWS_FILTER_FORM_EDIT_TITLE_FIELD;
		$sntf_fields_temps['news_short'] = PL_NEWS_FILTER_FORM_EDIT_SHORT_FIELD;
		$sntf_fields_temps['news_full'] = PL_NEWS_FILTER_FORM_EDIT_FULL_FIELD;
		$sntf_fields_temps['news_date'] = PL_NEWS_FILTER_FORM_EDIT_DATE_FIELD;
		$sntf_fields_temps['news_date_lo'] = PL_NEWS_FILTER_FORM_EDIT_DATE_LO_FIELD;
		$sntf_fields_temps['news_date_hi'] = PL_NEWS_FILTER_FORM_EDIT_DATE_HI_FIELD;
		$sntf_fields_temps['news_sort_select'] = PL_NEWS_FILTER_FORM_EDIT_SORT_SELECT_FIELD;

		$_GET['id'] = '';
	}

	echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sntf_title");
                if (el_title.value == "")
                {
                     alert("'.PL_NEWS_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_news_filter_form_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');

	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_NEWS_EDIT_TAB1);
	$layout->addHeader(PL_NEWS_EDIT_TAB1);

	$layout->addField(PL_NEWS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sntf_title, 'sntf_title', '', 'style="width:97%;"', true));
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($sntf_fields_temps['date_temps'], 'sntf_fields_temps[date_temps]', '', 'style="width:100%; height:70px;"');
	$fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_NEWS_DESIGN_FORM_DATE_TEMP, $fld);

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sntf_lang');
	$fld->mSelOptions = array($sntf_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_NEWS_DESIGN_EDIT_LANG, $fld);

	$layout->addTab(PL_NEWS_DESIGN_FORM_FIELDS_TAB);
	$layout->addHeader(PL_NEWS_DESIGN_FORM_FIELDS_TAB);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_EDIT_TAB1.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));

	$layout->addField('', new sbLayoutDelim());

	$tags = array('{VALUE}');
	$values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

	// идентификаторов новостей (для полного совпадения)
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_id'], 'sntf_fields_temps[news_id]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_ID_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_FILTER_FORM_EDIT_ID_FIELD_LABEL, $fld);

	// Начало интервала идентификаторов новостей
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_id_lo'], 'sntf_fields_temps[news_id_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_ID_LO_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_FILTER_FORM_EDIT_ID_LO_FIELD_LABEL, $fld);

	// Конец интервала идентификаторов новостей
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_id_hi'], 'sntf_fields_temps[news_id_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_ID_HI_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_FILTER_FORM_EDIT_ID_HI_FIELD_LABEL, $fld);

    // Дата
    $fld = new sbLayoutTextarea($sntf_fields_temps['news_date'], 'sntf_fields_temps[news_date]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_DATE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_FILTER_FORM_EDIT_DATE_FIELD_LABEL, $fld);

    // Начало интервала даты
    $fld = new sbLayoutTextarea($sntf_fields_temps['news_date_lo'], 'sntf_fields_temps[news_date_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_DATE_LO_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_FILTER_FORM_EDIT_DATE_LO_FIELD_LABEL, $fld);

	// Конец интервала даты
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_date_hi'], 'sntf_fields_temps[news_date_hi]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_DATE_HI_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_FILTER_FORM_EDIT_DATE_HI_FIELD_LABEL, $fld);

	//	Заголовок новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_title'], 'sntf_fields_temps[news_title]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_TITLE_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_NEWS_EDIT_TITLE, $fld);

	// Анонс новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_short'], 'sntf_fields_temps[news_short]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_SHORT_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_DESIGN_EDIT_ELEMENT, $fld);

	//	Полный текст новости
	$fld = new sbLayoutTextarea($sntf_fields_temps['news_full'], 'sntf_fields_temps[news_full]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_FULL_FIELD), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_NEWS_EDIT_FULL, $fld);

    //  Поля сортировки
    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_FORM_SORT_FIELDS.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));
	$layout->addField('', new sbLayoutDelim());

	// Макет полей сортировки
    $fld = new sbLayoutTextarea($sntf_fields_temps['news_sort_select'], 'sntf_fields_temps[news_sort_select]', '', 'style="width:100%;height:50px;"');
    	//Вытаскиваю пользовательские поля
    $user_flds_tags = array();
    $user_flds_vals = array();
    $layout->getPluginFieldsTagsSort('news', $user_flds_tags, $user_flds_vals, 'option');

    $fld->mTags = array_merge(array(PL_NEWS_FILTER_FORM_EDIT_SORT_SELECT_FIELD,
										PL_NEWS_FILTER_FORM_EDIT_SORT_ID_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_ID_FIELD_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_TITLE_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_TITLE_FIELD_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_DATE_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_DATE_FIELD_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_SHORT_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_SHORT_FIELD_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FULL_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FULL_FIELD_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_SORT_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_SORT_FIELD_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_USER_ID_FIELD_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_USER_ID_FIELD_DESC), $user_flds_tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_DATE_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_DATE_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_SHORT_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_SHORT_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_FULL_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_FULL_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_SORT_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_SORT_DESC,
										PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_ASC, PL_NEWS_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_DESC),$user_flds_vals);
	$layout->addField(PL_NEWS_DESIGN_FORM_SORT_SELECT_FIELDS, $fld);

	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_news');

	if ($res)
    {
		list($pd_fields) = $res[0];
		if ($pd_fields != '')
		{
			$pd_fields = unserialize($pd_fields);
			if (isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
            {
				$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_NEWS_DESIGN_FORM_USERS_FIELDS.'</div>';
				$layout->addField('', new sbLayoutHTML($html, true));
				$layout->addField('', new sbLayoutDelim());
            }

			// Пользовательские поля
			$layout->addPluginInputFieldsTemps('pl_news', $sntf_fields_temps, 'sntf_', '', array(), array(), false, false, 'n_f', '', true);
		}
	}

	$layout->addTab(PL_NEWS_DESIGN_FORM_FORM_LABEL);
	$layout->addHeader(PL_NEWS_DESIGN_FORM_FORM_LABEL);

	$user_tags = array();
	$user_tags_values = array();
	$layout->getPluginFieldsTags('pl_news', $user_tags, $user_tags_values, false, true, false, true);

	$fld = new sbLayoutTextarea($sntf_form, 'sntf_form', '', 'style="width:100%; height:450px;"');
	$fld->mTags = array_merge(array('-', PL_NEWS_FILTER_FORM_EDIT_HTML_FORM_TAG,
									'{ID}',
									'{ID_LO}',
									'{ID_HI}',
									'{DATE}',
									'{DATE_LO}',
									'{DATE_HI}',
									'{TITLE}',
									'{SHORT}',
									'{FULL}',
									'{SORT_SELECT}'), $user_tags);

	$fld->mValues = array_merge(array(PL_NEWS_DESIGN_EDIT_TAB1,
									PL_NEWS_DESIGN_FORM_HTML_FORM_TAG_VALUE,
									PL_NEWS_FILTER_FORM_EDIT_ID_FIELD_TAG,
									PL_NEWS_FILTER_FORM_EDIT_ID_LO_FIELD_TAG,
									PL_NEWS_FILTER_FORM_EDIT_ID_HI_FIELD_TAG,
									PL_NEWS_FILTER_FORM_EDIT_DATE_FIELD_TAG,
									PL_NEWS_FILTER_FORM_EDIT_DATE_LO_FIELD_TAG,
									PL_NEWS_FILTER_FORM_EDIT_DATE_HI_FIELD_TAG,
									PL_NEWS_DESIGN_FORM_TITLE_TAG_VALUE,
									PL_NEWS_DESIGN_FORM_SHORT_TAG_VALUE,
									PL_NEWS_DESIGN_FORM_FULL_TAG_VALUE,
									PL_NEWS_DESIGN_FORM_SORT_SELECT_TAG_VALUE), $user_tags_values);
	$layout->addField(PL_NEWS_DESIGN_FORM_FORM_LABEL , $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '')
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_news', 'design_edit') ? '' : 'disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fNews_Design_Filter_Form_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_news_filter'))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sntf_title = $sntf_form = '';
    $sntf_lang = SB_CMS_LANG;
    $sntf_fields_temps = array();

	extract($_POST);

	if ($sntf_title == '')
	{
        sb_show_message(PL_NEWS_DESIGN_FORM_SUBMIT_NO_TITLE_MSG, false, 'warning');
        fNews_Design_Filter_Form_Edit();
        return;
    }

	$row = array();
    $row['sntf_title'] = $sntf_title;
    $row['sntf_lang'] = $sntf_lang;
    $row['sntf_form'] = $sntf_form;
    $row['sntf_fields_temps'] = serialize($sntf_fields_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_query('SELECT sntf_title FROM sb_news_temps_form WHERE sntf_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

			sql_query('UPDATE sb_news_temps_form SET ?a WHERE sntf_id=?d', $row, $_GET['id'], sprintf(PL_NEWS_FILTER_FORM_SUBMIT_EDIT_OK, $old_title));

			$footer_ar = fCategs_Edit_Elem('design_edit');
			if (!$footer_ar)
			{
				sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_NEWS_FILTER_FORM_SUBMIT_ERR_EDIT, $old_title), SB_MSG_WARNING);

				fNews_Design_Filter_Form_Edit();
				return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sntf_id'] = intval($_GET['id']);
            $html_str = fNews_Design_Filter_Form_Get($row);

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
				fNews_Design_Filter_Form_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_NEWS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_FILTER_FORM_SUBMIT_ERR_EDIT, $sntf_title), SB_MSG_WARNING);

            fNews_Design_Filter_Form_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_query('INSERT INTO sb_news_temps_form (?#) VALUES (?a)', array_keys($row), array_values($row)))
        {
			$id = sql_insert_id();

			if (fCategs_Add_Elem($id, 'design_edit'))
            {
				sb_add_system_message(sprintf(PL_NEWS_FILTER_FORM_SUBMIT_ADD_OK, $sntf_title));
				echo '<script>
                        sbReturnValue('.$id.');
					</script>';
				$error = false;
            }
            else
			{
				sql_query('DELETE FROM sb_news_temps_form WHERE sntf_id=?d', $id);
            }
        }

        if ($error)
        {
			sb_show_message(sprintf(PL_NEWS_FILTER_FORM_SUBMIT_ADD_ERROR, $sntf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_NEWS_FILTER_FORM_SUBMIT_ADD_SYS_ERROR, $sntf_title), SB_MSG_WARNING);

			fNews_Design_Filter_Form_Edit();
			return;
		}
	}
}

function fNews_Design_Filter_Form_Delete()
{
	$id = intval($_GET['id']);
	$pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND pages.p_id=elems.e_p_id LIMIT 1', 'page', $id, 'pl_news_filter');
	$temps = false;
	if (!$pages)
	{
		$temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link=? AND elems.e_temp_id=?d AND elems.e_ident=? AND temps.t_id=elems.e_p_id LIMIT 1', 'temp', $id, 'pl_news_filter');
	}

	if ($pages || $temps)
	{
		echo PL_NEWS_DESIGN_FORM_DELETE_ERROR;
	}
}
?>