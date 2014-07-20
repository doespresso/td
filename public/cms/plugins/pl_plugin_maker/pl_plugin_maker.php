<?php

/**
 * Функции конструирования модулей
 **/
function fPlugin_Maker_Get($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['pm_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

	$result .= PL_PLUGIN_MAKER_ID_MODUL.': <span style="color: #33805E;">'.$args['pm_id'].'</span><br />';

	$res = sql_query('SELECT COUNT(*) FROM sb_plugins_'.intval($args['pm_id']));
	if ($res)
	{
	    $count = ($res ? $res[0][0] : 0);
	    $result .= PL_PLUGIN_MAKER_GET_ELEMS_COUNT.': <span style="color: '.($count == 0 ? 'red' : '#33805E').';">'.$count.'</span><br />';
	}
	else
	{
	    $result .= PL_PLUGIN_MAKER_GET_ELEMS_COUNT.': <span style="color:red;">'.PL_PLUGIN_MAKER_GET_TABLE_ERROR.'</span><br />';
	}

	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_ident="pl_plugin_'.intval($args['pm_id']).'"');
	if ($res)
	{
  	    $count = ($res ? $res[0][0] : 0);
        $result .= PL_PLUGIN_MAKER_GET_CATEGS_COUNT.': <span style="color: '.($count == 0 ? 'red' : '#33805E').';">'.$count.'</span><br />';
	}
    else
    {
        $result .= PL_PLUGIN_MAKER_GET_CATEGS_COUNT.': <span style="color:red;">'.PL_PLUGIN_MAKER_GET_TABLE_ERROR.'</span>';
    }

    $result .= '</div>';

    return $result;
}

function fPlugin_Maker_Create_System_Categs()
{
    // проверяем, есть ли системные разделы модулей
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_maker" AND cat_level < 2 ORDER BY cat_left');
    if (!$res)
    {
        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        $tree = new sbTree('pl_plugin_maker');

        $fields = array();
        $fields['cat_title'] = PL_PLUGIN_MAKER_ROOT_NAME;
        $fields['cat_closed'] = 0;
        $fields['cat_rubrik'] = 0;
        $fields['cat_rights'] = $_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();

        $root_id = $tree->insertNode(0, $fields);

        if (!$root_id)
            return false;

        $fields['cat_closed'] = 0;
        $fields['cat_rubrik'] = 0;
        $fields['cat_title'] = PL_PLUGIN_MAKER_USER_CATEG_NAME;
        $fields['cat_rights'] = $_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();

        $user_id = $tree->insertNode($root_id, $fields);
        if (!$user_id)
            return false;

        $fields['cat_closed'] = 0;
        $fields['cat_rubrik'] = 0;
        $fields['cat_title'] = PL_PLUGIN_MAKER_DEVELOP_CATEG_NAME;
        $fields['cat_rights'] = $_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();

        $develop_id = $tree->insertNode($root_id, $fields);
        if (!$user_id)
            return false;

        $fields['cat_closed'] = 0;
        $fields['cat_rubrik'] = 0;
        $fields['cat_title'] = PL_PLUGIN_MAKER_ADMIN_CATEG_NAME;
        $fields['cat_rights'] = $_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();

        $admin_id = $tree->insertNode($root_id, $fields);
        if (!$user_id)
            return false;

        return array($root_id, $user_id, $develop_id, $admin_id);
    }
    else
    {
        $result = array();
        for ($i = 0; $i < count($res); $i++)
        {
            list($cat_id) = $res[$i];
            $result[] = $cat_id;
        }

        return $result;
    }
}

function fPlugin_Maker_Init()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $system_cat_ids = fPlugin_Maker_Create_System_Categs();
    if (!$system_cat_ids)
    {
        sb_show_message(PL_PLUGIN_MAKER_CREATE_CATEGS_ERROR, true, 'warning');
        return;
    }

    $elems = new sbElements('sb_plugins_maker', 'pm_id', 'pm_title', 'fPlugin_Maker_Get', 'pl_plugin_maker_init', 'pl_plugin_maker');

	$elems->mCategsRootName = PL_PLUGIN_MAKER_ROOT_NAME;
	$elems->addSorting(PL_PLUGIN_MAKER_SORT_BY_ID, 'pm_id');
	$elems->addSorting(PL_PLUGIN_MAKER_SORT_BY_TITLE, 'pm_title');

	$elems->mCategsAddMenuTitle        = PL_PLUGIN_MAKER_CATEGS_ADD_MENU_TITLE;
	$elems->mCategsEditMenuTitle       = PL_PLUGIN_MAKER_CATEGS_EDIT_MENU_TITLE;
	$elems->mCategsDeleteMenuTitle     = PL_PLUGIN_MAKER_CATEGS_DELETE_MENU_TITLE;
	$elems->mCategsPasteMenuTitle      = PL_PLUGIN_MAKER_CATEGS_PASTE_MENU_TITLE;
	$elems->mCategsCopyMenuTitle       = PL_PLUGIN_MAKER_CATEGS_COPY_MENU_TITLE;
	$elems->mCategsCutMenuTitle        = PL_PLUGIN_MAKER_CATEGS_CUT_MENU_TITLE;
	$elems->mCategsPasteElemsMenuTitle = PL_PLUGIN_MAKER_CATEGS_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = PL_PLUGIN_MAKER_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = PL_PLUGIN_MAKER_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = PL_PLUGIN_MAKER_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = PL_PLUGIN_MAKER_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_maker_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_maker_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent =  'pl_plugin_maker_delete';
    $elems->mElemsAfterPasteEvent  =  'pl_plugin_maker_paste';

    $elems->mElemsJavascriptStr = '
    function beforeDeletePlugins()
    {
        if (confirm("'.PL_PLUGIN_MAKER_CONFIRM_DELETE.'"))
            return true;

        return false;
    }
    function afterDeletePlugins()
    {
    	window.parent.location.reload();
    }
    function afterAddEditPlugin()
    {
    	window.parent.location.reload();
    }';

    $elems->mElemsBeforeDeleteFunc = 'beforeDeletePlugins';
    $elems->mElemsAfterDeleteFunc = 'afterDeletePlugins';
    $elems->mElemsAfterAddFunc = 'afterAddEditPlugin';

    $elems->mCategsNeverShowCats = array($system_cat_ids[0]);

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mCategsDadStartLevel = 3;
    $elems->mCategsMaxDeep = 4;
    $elems->mCategsPasteElemsMenuLevel = 2;
    $elems->mCategsPasteCategsMenuLevel = 2;
    $elems->mCategsAddMenuLevel = 2;
    $elems->mCategsEditMenuLevel = 3;
    $elems->mCategsDeleteMenuLevel = 3;
    $elems->mCategsCopyMenuLevel = 3;
    $elems->mCategsCutMenuLevel = 3;
    $elems->mElemsMenuLevel = 2;

    $elems->init();
}

function fPlugin_Maker_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_maker'))
		return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT pm_title, pm_settings, pm_categs_settings, pm_elems_settings
                             FROM sb_plugins_maker WHERE pm_id=?d', $_GET['id']);

        if ($result)
        {
			list($pm_title, $pm_settings, $pm_categs_settings, $pm_elems_settings) = $result[0];
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($pm_settings != '')
            $pm_settings = unserialize($pm_settings);
        else
            $pm_settings = array();

        if ($pm_categs_settings != '')
            $pm_categs_settings = unserialize($pm_categs_settings);
        else
            $pm_categs_settings = array();

        if ($pm_elems_settings != '')
            $pm_elems_settings = unserialize($pm_elems_settings);
        else
            $pm_elems_settings = array();
    }
    elseif (count($_POST) > 0)
    {
    	$categs_settings = array();
    	$categs_settings['use_rubrikator'] = 0;
    	$categs_settings['site_users_rights'] = 0;
		$categs_settings['show_moderate'] = 0;

    	$elems_settings = array();
        $elems_settings['use_links'] = 0;
        $elems_settings['show_icons'] = 0;
        $elems_settings['show_chpu_field'] = 0;
        $elems_settings['show_tags_field'] = 0;
        $elems_settings['show_sort_field'] = 0;
        $elems_settings['show_active_field'] = 0;
        $elems_settings['show_comments'] = 0;
        $elems_settings['show_voting'] = 0;

    	$elems_settings['show_price1'] = 0;
	    $elems_settings['show_price1_in_list'] = 0;
	    $elems_settings['sort_price1_in_list'] = 0;
	    $elems_settings['filter_price1_in_list'] = 0;
	    $elems_settings['price1_type'] = 0;
	    $elems_settings['show_price2'] = 0;
	    $elems_settings['show_price2_in_list'] = 0;
	    $elems_settings['sort_price2_in_list'] = 0;
	    $elems_settings['filter_price2_in_list'] = 0;
	    $elems_settings['price2_type'] = 0;
	    $elems_settings['show_price3'] = 0;
	    $elems_settings['show_price3_in_list'] = 0;
	    $elems_settings['sort_price3_in_list'] = 0;
	    $elems_settings['filter_price3_in_list'] = 0;
	    $elems_settings['price3_type'] = 0;
	    $elems_settings['show_price4'] = 0;
	    $elems_settings['show_price4_in_list'] = 0;
	    $elems_settings['sort_price4_in_list'] = 0;
	    $elems_settings['filter_price4_in_list'] = 0;
	    $elems_settings['price4_type'] = 0;
	    $elems_settings['show_price5'] = 0;
	    $elems_settings['show_price5_in_list'] = 0;
	    $elems_settings['sort_price5_in_list'] = 0;
	    $elems_settings['filter_price5_in_list'] = 0;
	    $elems_settings['price5_type'] = 0;

        extract($_POST);

        $pm_elems_settings = array_merge($elems_settings, $pm_elems_settings);
        $pm_categs_settings = array_merge($categs_settings, $pm_categs_settings);

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $pm_title = '';

        $pm_settings = array();

        $pm_categs_settings = array();
        $pm_categs_settings['max_deep'] = 0;
        $pm_categs_settings['use_rubrikator'] = 1;
        $pm_categs_settings['site_users_rights'] = 1;
		$pm_categs_settings['show_moderate'] = 1;

        $pm_elems_settings = array();
        $pm_elems_settings['add_level'] = 0;
        $pm_elems_settings['show_chpu_field'] = 1;
        $pm_elems_settings['show_tags_field'] = 1;
        $pm_elems_settings['show_sort_field'] = 1;
        $pm_elems_settings['show_active_field'] = 1;
        $pm_elems_settings['show_comments'] = 1;
        $pm_elems_settings['show_voting'] = 1;
        $pm_elems_settings['use_links'] = 1;
        $pm_elems_settings['show_icons'] = 1;
        $pm_elems_settings['icon'] = SB_CMS_IMG_URL.'/plugins/pl_default_24.png';

		$_GET['id'] = '';
	}
	echo '<script>
    		function showGoods()
    		{
    			var check = sbGetE("show_goods").checked;
				var i = 1;
				var el = true;

    			while(el)
    			{
					var el = sbGetE("pl_"+i);
					if(el)
						el.style.display = check ? "table-row" : "none";
					else
						break;
					i++;
				}

				var temp = sbGetE("orders_list_temp");
				if(temp)
					temp.style.display = check ? "table-row" : "none";

				var el = sbGetE("hint_1");
				if(el)
					el.style.display = check ? "table-row" : "none";

				var el = sbGetE("hint_2");
				if(el)
					el.style.display = check ? "table-row" : "none";

	            var el = sbGetE("hint_3");
				if(el)
					el.style.display = check ? "table-row" : "none";

				var el = sbGetE("delim_1");
				if(el)
					el.style.display = check ? "table-row" : "none";

				var el = sbGetE("delim_2");
				if(el)
					el.style.display = check ? "table-row" : "none";

	            var el = sbGetE("delim_3");
				if(el)
					el.style.display = check ? "table-row" : "none";

				var el = sbGetE("cena_tr");
				if(el)
					el.style.display = check ? "table-row" : "none";

	            var el = sbGetE("edit_php_tr");
				if(el)
					el.style.display = check ? "table-row" : "none";
			}

            function checkValues()
            {
                var el_title = sbGetE("pm_title");
                if (el_title.value == "")
                {
                     alert("'.PL_PLUGIN_MAKER_EDIT_NO_TITLE_MSG.'");
                     sbSelectField("pm_title");
                     return false;
                }
            }

            function changePriceType(el, price_id)
            {
            	var row = sbGetE("price" + price_id + "_formula");
            	if (!row)
            		return;

            	if (el.checked)
            		row.style.display = "none";
            	else
            		row.style.display = _isIE ? "block" : "table-row";

            	sbGetE("pm_elems_settings[sort_price" + price_id + "_in_list]").disabled = !el.checked;
            	sbGetE("pm_elems_settings[filter_price" + price_id + "_in_list]").disabled = !el.checked;
            }

            var previous_hidden = ['.
                        (!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] != 0 ? '"price1_formula",' : '').
                        (!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] != 0 ? '"price2_formula",' : '').
                        (!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] != 0 ? '"price3_formula",' : '').
                        (!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] != 0 ? '"price4_formula",' : '').
                        (!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] != 0 ? '"price5_formula",' : '')
            .'];
            function changeBasket(el)
            {
                var rows = document.getElementsByName("advanced");
                if(!rows)
                {
                    return;
                }

                if(el.checked)
                {
                    for(var i=0; i<rows.length; i++)
                    {
                        rows[i].style.display = _isIE ? "block" : "table-row";
                        var id = rows[i].getAttribute("id");
                        if(id != "" && previous_hidden.indexOf(id) > -1)
                        {
                            rows[i].style.display = "none";
                        }
                    }
                    previous_hidden = [];
                }
                else
                {
                    for(var i=0; i<rows.length; i++)
                    {
                        var id = rows[i].getAttribute("id");
                        if(id != "" && rows[i].style.display == "none")
                        {
                            previous_hidden.push(id);
                        }
                        rows[i].style.display = "none";
                    }
                }
            }
            ';

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

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_maker_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main', 'enctype="multipart/form-data"');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '220';

    $layout->addTab(PL_PLUGIN_MAKER_EDIT_TAB1);
    $layout->addHeader(PL_PLUGIN_MAKER_EDIT_TAB1);

    $layout->addField(PL_PLUGIN_MAKER_EDIT_TITLE, new sbLayoutInput('text', $pm_title, 'pm_title', '', 'style="width:440px;"', true));

    $layout->addField(PL_PLUGIN_MAKER_EDIT_COMPONENTS_TITLE, new sbLayoutInput('text', isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST, 'pm_settings[list_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['full_component_title']) ? $pm_settings['full_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FULL, 'pm_settings[full_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['title_html_component_title']) ? $pm_settings['title_html_component_title'] : PL_PLUGIN_MAKER_H_ELEM_TITLE_HTML, 'pm_settings[title_html_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['title_plain_component_title']) ? $pm_settings['title_plain_component_title'] : PL_PLUGIN_MAKER_H_ELEM_TITLE_PLAIN, 'pm_settings[title_plain_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['categs_component_title']) ? $pm_settings['categs_component_title'] : PL_PLUGIN_MAKER_H_ELEM_CATEGS, 'pm_settings[categs_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['sel_cat_component_title']) ? $pm_settings['sel_cat_component_title'] : PL_PLUGIN_MAKER_H_ELEM_SEL_CAT, 'pm_settings[sel_cat_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['form_component_title']) ? $pm_settings['form_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FORM, 'pm_settings[form_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['form_edit_component_title']) ? $pm_settings['form_edit_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FORM_EDIT, 'pm_settings[form_edit_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['filter_component_title']) ? $pm_settings['filter_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FILTER, 'pm_settings[filter_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['cloud_component_title']) ? $pm_settings['cloud_component_title'] : PL_PLUGIN_MAKER_H_ELEM_CLOUD, 'pm_settings[cloud_component_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_settings['informer_component_title']) ? $pm_settings['informer_component_title'] : PL_PLUGIN_MAKER_H_ELEM_INFORMER, 'pm_settings[informer_component_title]', '', 'style="width:440px;"'));

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ALLOW_EXPORT, new sbLayoutInput('checkbox', '1', 'pm_settings[export_allow]', '', (isset($pm_settings['export_allow']) && $pm_settings['export_allow']==0)?'':'checked="checked"'));
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ALLOW_IMPORT, new sbLayoutInput('checkbox', '1', 'pm_settings[import_allow]', '', (isset($pm_settings['import_allow']) && $pm_settings['import_allow']==0)?'':'checked="checked"'));

    $layout->addTab(PL_PLUGIN_MAKER_EDIT_TAB2);
    $layout->addHeader(PL_PLUGIN_MAKER_EDIT_TAB2);

    $fld = new sbLayoutInput('text', isset($pm_categs_settings['max_deep']) ? $pm_categs_settings['max_deep'] : '', 'pm_categs_settings[max_deep]', 'spin_pm_categs_settings[max_deep]', 'style="width:80px;"');
    $fld->mMinValue = 0;
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_CATEGS_MAX_DEEP_DESC.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_EDIT_CATEGS_MAX_DEEP_TITLE, $fld);

    $layout->addField(PL_PLUGIN_MAKER_EDIT_CATEGS_USE_RUBRIK_TITLE, new sbLayoutInput('checkbox', '1', 'pm_categs_settings[use_rubrikator]', '', (isset($pm_categs_settings['use_rubrikator']) && $pm_categs_settings['use_rubrikator'] != 0 ? 'checked="checked"' : '')));
    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_EDIT_CATEGS_SITE_USERS_RIGHTS_TITLE, new sbLayoutInput('checkbox', '1', 'pm_categs_settings[site_users_rights]', '', (isset($pm_categs_settings['site_users_rights']) && $pm_categs_settings['site_users_rights'] != 0 ? 'checked="checked"' : '')));
    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_EDIT_CATEGS_SHOW_MODERATE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_categs_settings[show_moderate]', '', (isset($pm_categs_settings['show_moderate']) && $pm_categs_settings['show_moderate'] != 0 ? 'checked="checked"' : '')));
    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_EDIT_CATEGS_MENU_ITEMS_TITLE, new sbLayoutInput('text', isset($pm_categs_settings['add_item_title']) ? $pm_categs_settings['add_item_title'] : SB_CATEGS_ADD_MENU, 'pm_categs_settings[add_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['edit_item_title']) ? $pm_categs_settings['edit_item_title'] : SB_CATEGS_EDIT_MENU, 'pm_categs_settings[edit_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['delete_item_title']) ? $pm_categs_settings['delete_item_title'] : SB_CATEGS_DELETE_MENU, 'pm_categs_settings[delete_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['delete_we_item_title']) ? $pm_categs_settings['delete_we_item_title'] : SB_CATEGS_DELETE_WITH_ELEMENT_MENU, 'pm_categs_settings[delete_we_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['copy_item_title']) ? $pm_categs_settings['copy_item_title'] : SB_CATEGS_COPY_MENU, 'pm_categs_settings[copy_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['cut_item_title']) ? $pm_categs_settings['cut_item_title'] : SB_CATEGS_CUT_MENU, 'pm_categs_settings[cut_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['paste_item_title']) ? $pm_categs_settings['paste_item_title'] : SB_CATEGS_PASTE_CATEGS_MENU, 'pm_categs_settings[paste_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_categs_settings['paste_with_elements_item_title']) ? $pm_categs_settings['paste_with_elements_item_title'] : SB_CATEGS_ELEMENTS_PASTE_CATEGS_MENU, 'pm_categs_settings[paste_with_elements_item_title]', '', 'style="width:440px;"'));

    $layout->addTab(PL_PLUGIN_MAKER_EDIT_TAB3);
    $layout->addHeader(PL_PLUGIN_MAKER_EDIT_TAB3);

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_TITLE_FIELD_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['title_field_title']) ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_TITLE, 'pm_elems_settings[title_field_title]', '', 'style="width:440px;"'));

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_CHPU_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_chpu_field]', '', (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] != 0 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_TAGS_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_tags_field]', '', (isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] != 0 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_SORT_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_sort_field]', '', (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] != 0 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_ACTIVE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_active_field]', '', (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] != 0 ? 'checked="checked"' : '')));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_COMMENTS_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_comments]', '', (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] != 0 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_VOTING_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_voting]', '', (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] != 0 ? 'checked="checked"' : '')));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutInput('text', isset($pm_elems_settings['add_level']) ? $pm_elems_settings['add_level'] : '', 'pm_elems_settings[add_level]', 'spin_pm_elems_settings[add_level]', 'style="width:80px;"');
    $fld->mMinValue = 0;
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_LEVEL_DESC.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_LEVEL_TITLE, $fld);

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_USE_LINKS_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[use_links]', '', (isset($pm_elems_settings['use_links']) && $pm_elems_settings['use_links'] != 0 ? 'checked="checked"' : '')));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_ICONS_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_icons]', '', (isset($pm_elems_settings['show_icons']) && $pm_elems_settings['show_icons'] != 0 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_ICON_TITLE, new sbLayoutImage((isset($pm_elems_settings['icon']) ? $pm_elems_settings['icon'] : ''), 'pm_elems_settings[icon]', '', 'style="width:430px;"'));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_MENU_ITEMS_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['add_item_title']) ? $pm_elems_settings['add_item_title'] : SB_ELEMS_ADD_MENU, 'pm_elems_settings[add_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_elems_settings['edit_item_title']) ? $pm_elems_settings['edit_item_title'] : SB_ELEMS_EDIT_MENU, 'pm_elems_settings[edit_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_elems_settings['delete_item_title']) ? $pm_elems_settings['delete_item_title'] : SB_ELEMS_DELETE_MENU, 'pm_elems_settings[delete_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_elems_settings['copy_item_title']) ? $pm_elems_settings['copy_item_title'] : SB_ELEMS_COPY_MENU, 'pm_elems_settings[copy_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_elems_settings['cut_item_title']) ? $pm_elems_settings['cut_item_title'] : SB_ELEMS_CUT_MENU, 'pm_elems_settings[cut_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_elems_settings['paste_item_title']) ? $pm_elems_settings['paste_item_title'] : SB_CATEGS_PASTE_MENU, 'pm_elems_settings[paste_item_title]', '', 'style="width:440px;"'));
    $layout->addField('', new sbLayoutInput('text', isset($pm_elems_settings['paste_links_item_title']) ? $pm_elems_settings['paste_links_item_title'] : SB_CATEGS_PASTE_LINKS_MENU, 'pm_elems_settings[paste_links_item_title]', '', 'style="width:440px;"'));

	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
    {
    	// Интернет-магазин
    	$layout->addTab(PL_PLUGIN_MAKER_EDIT_TAB4);
    	$layout->addHeader(PL_PLUGIN_MAKER_EDIT_TAB4);

        $hidden = !isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] != 0 ? '' : ' style="display: none"';

        //Корзина для модуля
        $layout->addField(PL_PLUGIN_MAKER_EDIT_BASKET_NEED, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[need_basket]', '', (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] != 0 ? 'checked="checked"' : '').' onclick="changeBasket(this)"'));
        $layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_BASKET_NEED_DESC.'</div>', false));
        $layout->addField('', new sbLayoutDelim());

        // Цена 1
    	$layout->addField('', new sbLayoutHTML('<div class="hint_div" style="text-align:center;">'.sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1').'</div>', true), '', '', 'name="advanced"'.$hidden);
    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price1]', '', (!isset($pm_elems_settings['show_price1']) || $pm_elems_settings['show_price1'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['price1_title']) ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1'), 'pm_elems_settings[price1_title]', '', 'style="width:440px;"'), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price1_in_list]', '', (!isset($pm_elems_settings['show_price1_in_list']) || $pm_elems_settings['show_price1_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SORT_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[sort_price1_in_list]', '', (!isset($pm_elems_settings['sort_price1_in_list']) || $pm_elems_settings['sort_price1_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_FILTER_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[filter_price1_in_list]', '', (!isset($pm_elems_settings['filter_price1_in_list']) || $pm_elems_settings['filter_price1_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TYPE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[price1_type]', '', (!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] != 0 ? 'checked="checked" ' : '').'onclick="changePriceType(this, 1)"'), '', '', 'name="advanced"'.$hidden);

    	$fld = new sbLayoutTextarea(isset($pm_elems_settings['price1_formula']) ? $pm_elems_settings['price1_formula'] : '$GLOBALS[\'sb_value\'] = null;', 'pm_elems_settings[price1_formula]', '', 'style="width:100%;height:50px;"');
    	$tags = array('-',
    				  '{PRICE1}',
    				  '{PRICE2}',
    				  '{PRICE3}',
    				  '{PRICE4}',
    				  '{PRICE5}',
    	              '{DISCOUNT}');

    	$values = array(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICES,
    					sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1'),
    					sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2'),
    					sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3'),
    					sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4'),
    					sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5'),
    	                PL_PLUGIN_MAKER_EDIT_ELEMS_DISCOUNT_TITLE);

    	if ($_GET['id'] != '')
    	{
    		$standart_tags = array('-', '{ID}', '{TITLE}');
			$standart_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEMS_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ID_TAG, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG);

		    if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1)
    		{
    			$standart_tags[] = '{ELEM_URL}';
    			$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM_URL_TAG;
    		}

			if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
			{
				$standart_tags[] = '{SORT}';
				$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_TAG;
			}

			$layout->getPluginFieldsTags('pl_plugin_'.intval($_GET['id']), $standart_tags, $standart_values);

    		$standart_tags = array_merge($standart_tags,
                              array('-', '{CAT_TITLE}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'));

    		$standart_values = array_merge($standart_values,
                              array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG));

    		$layout->getPluginFieldsTags('pl_plugin_'.intval($_GET['id']), $standart_tags, $standart_values, true);

    		$tags = array_merge($tags, $standart_tags);
    		$values = array_merge($values, $standart_values);
    	}

    	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_services_cb'))
    	{
	    	$xml_kurs = simplexml_load_file('http://www.cbr.ru/scripts/XML_daily.asp');
			if (isset($xml_kurs->Valute))
			{
				$tags[] = '-';
				$values[] = PL_PLUGIN_MAKER_EDIT_ELEMS_CUR;

	    		foreach($xml_kurs->Valute as $valute)
	    		{
	    			$tags[] = '{VALUE_'.$valute->CharCode.'}';
	    			$values[] = SB_CHARSET != 'UTF-8' ? iconv('UTF-8', SB_CHARSET.'//IGNORE', $valute->Name) : $valute->Name;
	    		}
			}
    	}

    	$fld->mTags = $tags;
    	$fld->mValues = $values;
    	unset($fld->mTags[1]);
    	unset($fld->mValues[1]);
    	$fld->mBottomHtml = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_DESCR.'</div>';

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_TITLE, $fld, '', '', (!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] != 0 ? 'style="display:none"' : '').' id="price1_formula" name="advanced"'.$hidden);

    	// Цена 2
    	$layout->addField('', new sbLayoutHTML('<div class="hint_div" style="text-align:center;">'.sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2').'</div>', true), '', '', 'name="advanced"'.$hidden);
    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price2]', '', (isset($pm_elems_settings['show_price2']) && $pm_elems_settings['show_price2'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['price2_title']) ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2'), 'pm_elems_settings[price2_title]', '', 'style="width:440px;"'), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price2_in_list]', '', (isset($pm_elems_settings['show_price2_in_list']) && $pm_elems_settings['show_price2_in_list'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SORT_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[sort_price2_in_list]', '', (isset($pm_elems_settings['sort_price2_in_list']) && $pm_elems_settings['sort_price2_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price2_type']) && $pm_elems_settings['price2_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_FILTER_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[filter_price2_in_list]', '', (isset($pm_elems_settings['filter_price2_in_list']) && $pm_elems_settings['filter_price2_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price2_type']) && $pm_elems_settings['price2_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TYPE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[price2_type]', '', (!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] != 0 ? 'checked="checked" ' : '').'onclick="changePriceType(this, 2)"'), '', '', 'name="advanced"'.$hidden);

    	$fld = new sbLayoutTextarea(isset($pm_elems_settings['price2_formula']) ? $pm_elems_settings['price2_formula'] : '$GLOBALS[\'sb_value\'] = null;', 'pm_elems_settings[price2_formula]', '', 'style="width:100%;height:50px;"');
    	$fld->mTags = $tags;
    	$fld->mValues = $values;
    	unset($fld->mTags[2]);
    	unset($fld->mValues[2]);
    	$fld->mBottomHtml = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_DESCR.'</div>';

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_TITLE, $fld, '', '', (!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] != 0 ? 'style="display:none"' : '').' id="price2_formula" name="advanced"'.$hidden);

    	// Цена 3
    	$layout->addField('', new sbLayoutHTML('<div class="hint_div" style="text-align:center;">'.sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3').'</div>', true), '', '', 'name="advanced"'.$hidden);
    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price3]', '', (isset($pm_elems_settings['show_price3']) && $pm_elems_settings['show_price3'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['price3_title']) ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3'), 'pm_elems_settings[price3_title]', '', 'style="width:440px;"'), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price3_in_list]', '', (isset($pm_elems_settings['show_price3_in_list']) && $pm_elems_settings['show_price3_in_list'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SORT_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[sort_price3_in_list]', '', (isset($pm_elems_settings['sort_price3_in_list']) && $pm_elems_settings['sort_price3_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price3_type']) && $pm_elems_settings['price3_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_FILTER_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[filter_price3_in_list]', '', (isset($pm_elems_settings['filter_price3_in_list']) && $pm_elems_settings['filter_price3_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price3_type']) && $pm_elems_settings['price3_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TYPE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[price3_type]', '', (!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] != 0 ? 'checked="checked" ' : '').'onclick="changePriceType(this, 3)"'), '', '', 'name="advanced"'.$hidden);

    	$fld = new sbLayoutTextarea(isset($pm_elems_settings['price3_formula']) ? $pm_elems_settings['price3_formula'] : '$GLOBALS[\'sb_value\'] = null;', 'pm_elems_settings[price3_formula]', '', 'style="width:100%;height:50px;"');
    	$fld->mTags = $tags;
    	$fld->mValues = $values;
    	unset($fld->mTags[3]);
    	unset($fld->mValues[3]);
    	$fld->mBottomHtml = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_DESCR.'</div>';

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_TITLE, $fld, '', '', (!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] != 0 ? 'style="display:none"' : '').' id="price3_formula" name="advanced"'.$hidden);

    	// Цена 4
    	$layout->addField('', new sbLayoutHTML('<div class="hint_div" style="text-align:center;">'.sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4').'</div>', true), '', '', 'name="advanced"'.$hidden);
    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price4]', '', (isset($pm_elems_settings['show_price4']) && $pm_elems_settings['show_price4'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['price4_title']) ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4'), 'pm_elems_settings[price4_title]', '', 'style="width:440px;"'), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price4_in_list]', '', (isset($pm_elems_settings['show_price4_in_list']) && $pm_elems_settings['show_price4_in_list'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SORT_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[sort_price4_in_list]', '', (isset($pm_elems_settings['sort_price4_in_list']) && $pm_elems_settings['sort_price4_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price4_type']) && $pm_elems_settings['price4_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_FILTER_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[filter_price4_in_list]', '', (isset($pm_elems_settings['filter_price4_in_list']) && $pm_elems_settings['filter_price4_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price4_type']) && $pm_elems_settings['price4_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TYPE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[price4_type]', '', (!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] != 0 ? 'checked="checked" ' : '').'onclick="changePriceType(this, 4)"'), '', '', 'name="advanced"'.$hidden);

    	$fld = new sbLayoutTextarea(isset($pm_elems_settings['price4_formula']) ? $pm_elems_settings['price4_formula'] : '$GLOBALS[\'sb_value\'] = null;', 'pm_elems_settings[price4_formula]', '', 'style="width:100%;height:50px;"');
    	$fld->mTags = $tags;
    	$fld->mValues = $values;
    	unset($fld->mTags[4]);
    	unset($fld->mValues[4]);
    	$fld->mBottomHtml = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_DESCR.'</div>';

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_TITLE, $fld, '', '', (!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] != 0 ? 'style="display:none"' : '').' id="price4_formula" name="advanced"'.$hidden);

    	// Цена 5
    	$layout->addField('', new sbLayoutHTML('<div class="hint_div" style="text-align:center;">'.sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5').'</div>', true), '', '', 'name="advanced"'.$hidden);
    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price5]', '', (isset($pm_elems_settings['show_price5']) && $pm_elems_settings['show_price5'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE, new sbLayoutInput('text', isset($pm_elems_settings['price5_title']) ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5'), 'pm_elems_settings[price5_title]', '', 'style="width:440px;"'), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"');

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_price5_in_list]', '', (isset($pm_elems_settings['show_price5_in_list']) && $pm_elems_settings['show_price5_in_list'] != 0 ? 'checked="checked"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SORT_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[sort_price5_in_list]', '', (isset($pm_elems_settings['sort_price5_in_list']) && $pm_elems_settings['sort_price5_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price5_type']) && $pm_elems_settings['price5_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_FILTER_PRICE_IN_LIST_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[filter_price5_in_list]', '', (isset($pm_elems_settings['filter_price5_in_list']) && $pm_elems_settings['filter_price5_in_list'] != 0 ? 'checked="checked"' : '').(isset($pm_elems_settings['price5_type']) && $pm_elems_settings['price5_type'] == 0 ? ' disabled="disabled"' : '')), '', '', 'name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutDelim(), '', '', 'name="advanced"'.$hidden);

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TYPE_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[price5_type]', '', (!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] != 0 ? 'checked="checked" ' : '').'onclick="changePriceType(this, 5)"'), '', '', 'name="advanced"'.$hidden);

    	$fld = new sbLayoutTextarea(isset($pm_elems_settings['price5_formula']) ? $pm_elems_settings['price5_formula'] : '$GLOBALS[\'sb_value\'] = null;', 'pm_elems_settings[price5_formula]', '', 'style="width:100%;height:50px;"');
    	$fld->mTags = $tags;
    	$fld->mValues = $values;
    	unset($fld->mTags[5]);
    	unset($fld->mValues[5]);
    	$fld->mBottomHtml = '<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_DESCR.'</div>';

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_FORMULA_TITLE, $fld, '', '', (!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] != 0 ? 'style="display:none"' : '').' id="price5_formula" name="advanced"'.$hidden);

    	$layout->addField('', new sbLayoutHTML('<div class="hint_div" style="text-align:center;">'.PL_PLUGIN_MAKER_EDIT_ELEMS_DISCOUNT_AND_BONUS_TITLE.'</div>', true), '', '', 'style="display: none"');
    	$layout->addField('', new sbLayoutDelim());

    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_DISCOUNT_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_discount]', '', (!isset($pm_elems_settings['show_discount']) || $pm_elems_settings['show_discount'] != 0 ? 'checked="checked"' : '')), '', '', 'style="display: none"');
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_SUM_DISCOUNT_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_sum_discount]', '', (!isset($pm_elems_settings['show_sum_discount']) || $pm_elems_settings['show_sum_discount'] != 0 ? 'checked="checked"' : '')), '', '', 'style="display: none"');
    	$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_BONUS_TITLE, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_bonus]', '', (!isset($pm_elems_settings['show_bonus']) || $pm_elems_settings['show_bonus'] != 0 ? 'checked="checked"' : '')), '', '', 'style="display: none"');

    	// Корзина
		$layout->addTab(PL_PLUGIN_MAKER_EDIT_TAB5);
		$layout->addHeader(PL_PLUGIN_MAKER_EDIT_TAB5);

		$layout->addField(PL_PLUGIN_MAKER_EDIT_ELEMS_SHOW_BOOKED_GOODS, new sbLayoutInput('checkbox', '1', 'pm_elems_settings[show_goods]', 'show_goods', (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1 ? 'checked="checked"' : '').'onclick="showGoods();"'));

		$style = '';
		if(!isset($pm_elems_settings['show_goods']) || $pm_elems_settings['show_goods'] == 0)
			$style = 'style="display:none;"';

		$layout->addField('', new sbLayoutDelim(), '', '', 'id="delim_1" '.$style);

		$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_DESIGNS_HINT_DIV.'</div>', true), '', '', 'id="hint_1" '.$style);

		$res = sql_param_query('SELECT pm.pm_id, pm.pm_title, categs.cat_title, temps.ptl_id, temps.ptl_title
				FROM sb_plugins_maker pm
				LEFT JOIN sb_categs categs ON categs.cat_ident = CONCAT("pl_plugin_", pm.pm_id, "_design_list")
				LEFT JOIN sb_catlinks links ON categs.cat_id=links.link_cat_id
				LEFT JOIN sb_plugins_temps_list temps ON temps.ptl_id=links.link_el_id
				WHERE pm.pm_id != ?d
				ORDER BY pm.pm_id, categs.cat_left, temps.ptl_title', $_GET['id']);

		if ($res)
		{
			$options = array(' --- ');
	        $pm_title = $old_cat_title = '';
			$count = count($res);
			$old_pm_id = $pm_id = $i = $y = 0;

			foreach($res as $key => $value)
			{
				$old_pm_title = $pm_title;
				$old_pm_id = $pm_id;

				list($pm_id, $pm_title, $cat_title, $ptl_id, $ptl_title) = $value;

				if($old_pm_id == 0)
				{
					$old_pm_id = $pm_id;
					$old_pm_title = $pm_title;
					$old_pm_id = $pm_id;
				}

				if($old_pm_id != $pm_id)
				{
					$y++;
					if(count($options) > 1)
					{
						$fld = new sbLayoutSelect($options, 'pm_elems_settings[basket_temp_id'.$old_pm_id.']');
						if (isset($pm_elems_settings['basket_temp_id'.$old_pm_id]))
				        {
							$fld->mSelOptions = array($pm_elems_settings['basket_temp_id'.$old_pm_id]);
				        }
					}
					else
					{
						$fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $old_pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST).'</div>', '', '', false);
					}
					$layout->addField($old_pm_title, $fld, '', '', 'id="pl_'.$y.'" '.$style);

					$old_pm_id = $pm_id;
					$options = array(' --- ');
			        $old_cat_title = '';
				}

	            if ($old_cat_title != $cat_title)
	            {
	                $options[uniqid()] = '-'.$cat_title;
	                $old_cat_title = $cat_title;
	            }

				if($ptl_id != '')
				{
					$options[$ptl_id] = $ptl_title;
				}

				$i++;
				if($count == $i)
				{
					$y++;
					if (count($options) > 1)
				    {
						$fld = new sbLayoutSelect($options, 'pm_elems_settings[basket_temp_id'.$pm_id.']');
				        if (isset($pm_elems_settings['basket_temp_id'.$pm_id]))
				        {
							$fld->mSelOptions = array($pm_elems_settings['basket_temp_id'.$pm_id]);
				        }
				    }
					else
				    {
						$fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST).'</div>', '', '', false);
					}
					$layout->addField($pm_title, $fld, '', '', 'id="pl_'.$y.'" '.$style);
				}
			}
		}

		$fld = new sbLayoutTextarea(isset($pm_elems_settings['basket_temp']) ? $pm_elems_settings['basket_temp'] : '', 'pm_elems_settings[basket_temp]', '', 'style="width:100%;height:100px;"');
		$fld->mTags = array('-', '{ORDER_LIST}', '{COUNT_POSITIONS}', '{COUNT_GOODS}', '{TOVAR_SUM}', '{TOVAR_SUM_DISCOUNT}');
		$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_BOOKED_LIST_FIELD,
					PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_POS_FIELD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_GOODS_FIELD,
					PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITHOUT_DISCOUNT_FIELD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITH_DISCOUNT_FIELD);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_LIST_ORDERS, $fld, '', '', 'id="orders_list_temp" '.$style);

		$layout->addField('', new sbLayoutDelim(), '', '', 'id="delim_2" '.$style);
		$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_PRICE_HINT_DIV.'</div>', true), '', '', 'id="hint_2" '.$style);

		$options = array();
		$options[1] = PL_PLUGIN_MAKER_H_CENA_1;
		$options[2] = PL_PLUGIN_MAKER_H_CENA_2;
		$options[3] = PL_PLUGIN_MAKER_H_CENA_3;
		$options[4] = PL_PLUGIN_MAKER_H_CENA_4;
		$options[5] = PL_PLUGIN_MAKER_H_CENA_5;

		$fld = new sbLayoutSelect($options, 'pm_elems_settings[cena]');
		$fld->mSelOptions = isset($pm_elems_settings['cena']) ? array($pm_elems_settings['cena']) : array();
		$layout->addfield(PL_PLUGIN_MAKER_EDIT_CENA_LIST_FIELD_TITLE, $fld, '', '', 'id="cena_tr" '.$style);

		$layout->addField('', new sbLayoutDelim(), '', '', 'id="delim_3" '.$style);
		$layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_MAKER_EDIT_ORDER_EDIT_PHP_DESC.'</div>', true), '', '', 'id="hint_3" '.$style);

		$fld = new sbLayoutTextarea(isset($pm_elems_settings['order_edit_php']) ? $pm_elems_settings['order_edit_php'] : '', 'pm_elems_settings[order_edit_php]', '', 'style="width:100%;height:100px;"');
		$layout->addfield(PL_PLUGIN_MAKER_EDIT_ORDER_EDIT_PHP_TITLE, $fld, '', '', 'id="edit_php_tr" '.$style);
	}

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_maker', 'elems_edit') ? '' : 'disabled="disabled"'));
	if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_maker', 'elems_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

	$layout->show();
}

function fPlugin_Maker_Edit_Submit()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_maker'))
		return;

	if (!isset($_GET['id']))
        $_GET['id'] = '';


    $pm_settings = array();
    $plugin_settings = array(
        'export_allow' => 0,
        'import_allow' => 0
    );

    $categs_settings = array();
    $categs_settings['max_deep'] = 0;
    $categs_settings['use_rubrikator'] = 0;
    $categs_settings['site_users_rights'] = 0;

    $elems_settings = array();
    $elems_settings['add_level'] = 0;
    $elems_settings['use_links'] = 0;
    $elems_settings['show_icons'] = 0;
    $elems_settings['show_chpu_field'] = 0;
    $elems_settings['show_tags_field'] = 0;
    $elems_settings['show_sort_field'] = 0;
    $elems_settings['show_active_field'] = 0;
    $elems_settings['show_comments'] = 0;
    $elems_settings['show_voting'] = 0;
    $elems_settings['show_price1'] = 0;
    $elems_settings['show_price1_in_list'] = 0;
    $elems_settings['sort_price1_in_list'] = 0;
    $elems_settings['filter_price1_in_list'] = 0;
    $elems_settings['price1_type'] = 0;
    $elems_settings['show_price2'] = 0;
    $elems_settings['show_price2_in_list'] = 0;
    $elems_settings['sort_price2_in_list'] = 0;
    $elems_settings['filter_price2_in_list'] = 0;
    $elems_settings['price2_type'] = 0;
    $elems_settings['show_price3'] = 0;
    $elems_settings['show_price3_in_list'] = 0;
    $elems_settings['sort_price3_in_list'] = 0;
    $elems_settings['filter_price3_in_list'] = 0;
    $elems_settings['price3_type'] = 0;
    $elems_settings['show_price4'] = 0;
    $elems_settings['show_price4_in_list'] = 0;
    $elems_settings['sort_price4_in_list'] = 0;
    $elems_settings['filter_price4_in_list'] = 0;
    $elems_settings['price4_type'] = 0;
    $elems_settings['show_price5'] = 0;
    $elems_settings['show_price5_in_list'] = 0;
    $elems_settings['sort_price5_in_list'] = 0;
    $elems_settings['filter_price5_in_list'] = 0;
    $elems_settings['price5_type'] = 0;
    $elems_settings['need_basket'] = 0;

    extract($_POST);

    $pm_elems_settings = array_merge($elems_settings, $pm_elems_settings);
    $pm_categs_settings = array_merge($categs_settings, $pm_categs_settings);
    $pm_settings = array_merge($plugin_settings, $pm_settings);

    $pm_title = trim(strip_tags($pm_title));

    if ($pm_title == '')
    {
    	$_POST['pm_title'] = $pm_title;
        sb_show_message(PL_PLUGIN_MAKER_EDIT_NO_TITLE_MSG, false, 'warning');
        fPlugin_Maker_Edit();
        return;
    }

    if (trim($pm_elems_settings['icon']) == '')
        $pm_elems_settings['icon'] = SB_CMS_IMG_URL.'/plugins/pl_default_24.png';

    $row['pm_title'] = $pm_title;
    $row['pm_settings'] = serialize($pm_settings);
    $row['pm_categs_settings'] = serialize($pm_categs_settings);
    $row['pm_elems_settings'] = serialize($pm_elems_settings);

    if ($_GET['id'] != '')
    {
        //Меняем настройки доступности модуля для экспорта/импорта
        $_SESSION['sbPlugins']->setExportImport('pl_plugin_'.$_GET['id'], $pm_settings['export_allow'], $pm_settings['import_allow']);
    	#TODO: При изменении названия модуля менять в меню и пр.
        $res = sql_param_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_plugins_maker SET ?a WHERE pm_id=?d', $row, $_GET['id'], sprintf(PL_PLUGIN_MAKER_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem();

            if (!$footer_ar)
            {
                sb_show_message(PL_PLUGIN_MAKER_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);
                fPlugin_Maker_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['pm_id'] = intval($_GET['id']);

            $html_str = fPlugin_Maker_Get($row);
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
                fPlugin_Maker_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_EDIT_SYSTEMLOG_ERROR, $pm_title), SB_MSG_WARNING);

            fPlugin_Maker_Edit();
            return;
        }

        #TODO: Менять рубрикатор и пр.
    }
    else
    {
        $error = 1;

        if (sql_param_query('INSERT INTO sb_plugins_maker SET ?a', $row))
        {
			$id = sql_insert_id();

			// Создаем таблицу БД для модуля
	        $sql = 'CREATE TABLE `sb_plugins_'.$id.'` (
                 `p_id` int(10) unsigned NOT NULL auto_increment COMMENT "'.PL_PLUGIN_MAKER_ADD_ID_COMMENT.'",
                 `p_title` varchar(255) character set '.SB_DB_CHARSET.' NOT NULL COMMENT "'.PL_PLUGIN_MAKER_ADD_TITLE_COMMENT.'",
                 `p_url` varchar(255) character set '.SB_DB_CHARSET.' default NULL COMMENT "'.PL_PLUGIN_MAKER_ADD_URL_COMMENT.'",
                 `p_pub_start` int(11) unsigned default NULL COMMENT "'.PL_PLUGIN_MAKER_ADD_PUB_START_COMMENT.'",
                 `p_pub_end` int(11) unsigned default NULL COMMENT "'.PL_PLUGIN_MAKER_ADD_PUB_END_COMMENT.'",
                 `p_sort` int(11) unsigned NOT NULL default "0" COMMENT "'.PL_PLUGIN_MAKER_ADD_SORT_COMMENT.'",
                 `p_active` smallint(5) unsigned NOT NULL default "0" COMMENT "'.PL_PLUGIN_MAKER_ACTIVE_COMMENT.'",
                 `p_ext_id` varchar(100) default NULL COMMENT "'.PL_PLUGIN_MAKER_EXT_ID_COMMENT.'",
                 `p_user_id` int(11) unsigned NULL COMMENT "'.PL_PLUGIN_MAKER_USER_ID_COMMENT.'",
                 `p_order` longtext character set '.SB_DB_CHARSET.' default NULL COMMENT "'.PL_PLUGIN_MAKER_ORDER_COMMENT.'",
                 `p_demo_id` int(10) unsigned NULL default "0" COMMENT "'.PL_PLUGIN_MAKER_P_DEMO_ID_COMMENT.'",
                 `p_price1` decimal(15,2) unsigned NULL default NULL COMMENT "'.sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '1').'",
                 `p_price2` decimal(15,2) unsigned NULL default NULL COMMENT "'.sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '2').'",
                 `p_price3` decimal(15,2) unsigned NULL default NULL COMMENT "'.sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '3').'",
                 `p_price4` decimal(15,2) unsigned NULL default NULL COMMENT "'.sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '4').'",
                 `p_price5` decimal(15,2) unsigned NULL default NULL COMMENT "'.sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '5').'",
                PRIMARY KEY  (`p_id`),
                KEY `p_url` (`p_url`),
                KEY `p_pub_start` (`p_pub_start`,`p_pub_end`),
                KEY `p_sort` (`p_sort`),
                KEY `p_active` (`p_active`),
                KEY `p_ext_id` (`p_ext_id`),
                KEY `p_user_id` (`p_user_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET='.SB_DB_CHARSET;

	        if (sql_query($sql))
	        {
		        if (fCategs_Add_Elem($id))
	            {
	                $error = 0;
	            }
	            else
	            {
	            	$error = 3;
	            }
	        }
        	else
        	{
        		$error = 2;
        	}
        }

        if ($error == 0)
    	{
    		sb_add_system_message(sprintf(PL_PLUGIN_MAKER_ADD_OK, $pm_title));

    		echo '<script>
	        	sbReturnValue('.$id.');
	        </script>';

    		// Инициализируем модуль
    		$elem_list = isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST;
	    	$elem_full = isset($pm_settings['full_component_title']) ? $pm_settings['full_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FULL;
	    	$elem_categs = isset($pm_settings['categs_component_title']) ? $pm_settings['categs_component_title'] : PL_PLUGIN_MAKER_H_ELEM_CATEGS;
	    	$elem_sel_cat = isset($pm_settings['sel_cat_component_title']) ? $pm_settings['sel_cat_component_title'] : PL_PLUGIN_MAKER_H_ELEM_SEL_CAT;
	    	$elem_form = isset($pm_settings['form_component_title']) ? $pm_settings['form_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FORM;
			$elem_filter = isset($pm_settings['filter_component_title']) ? $pm_settings['filter_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FILTER;
	    	$elem_title_html = isset($pm_settings['title_html_component_title']) ? $pm_settings['title_html_component_title'] : PL_PLUGIN_MAKER_H_ELEM_TITLE_HTML;
	    	$elem_title_plain = isset($pm_settings['title_plain_component_title']) ? $pm_settings['title_plain_component_title'] : PL_PLUGIN_MAKER_H_ELEM_TITLE_PLAIN;
	    	$elem_cloud = isset($pm_settings['cloud_component_title']) ? $pm_settings['cloud_component_title'] : PL_PLUGIN_MAKER_H_ELEM_CLOUD;

	    	$_SESSION['sbPlugins']->mIcon = (isset($pm_elems_settings['icon']) ? $pm_elems_settings['icon'] : '');
    		$_SESSION['sbPlugins']->mFile = SB_CMS_PL_PATH.'/pl_plugin_maker/pl_plugin_maker.php';
    		$_SESSION['sbPlugins']->mLangFile = SB_CMS_LANG_PATH.'/pl_plugin_maker.lng.php';

    		if ($_SESSION['sbPlugins']->register('pl_plugin_'.$id, $pm_title, ''))
    		{
				$menu = '';
    			$res_cat = sql_param_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
    			if ($res_cat)
    			{
    				list($cat_title) = $res_cat[0];

			    	if ($_GET['cat_level'] > 1)
			    	{
			    		$res_cat = sql_param_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
			    		if ($res_cat)
			    		{
			    			list($cat_left, $cat_right) = $res_cat[0];
				    		$res_cat = sql_query('SELECT cat_title FROM sb_categs WHERE cat_left < '.$cat_left.' AND cat_right > '.$cat_right.' AND cat_ident="pl_plugin_maker" AND cat_level > 0 ORDER BY cat_left');
				    		if ($res_cat)
				    		{
				    			foreach($res_cat as $val_cat)
				    			{
				    				$menu .= $val_cat[0].'>';
				    			}
				    		}
			    		}

			    		$menu .= $cat_title;
			    	}
			    	else
			    	{
			    		$menu = $cat_title;
			    	}
    			}

		    	$menu .= '>'.$pm_title;

    			$_SESSION['sbPlugins']->addToMenu($menu, 'init&pm_id='.$id);

    			$_SESSION['sbPlugins']->setMainEvent('init&pm_id='.$id);

	    	    if ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$id, 'design_read'))
				{
				    if ($elem_list != '')
				        $_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_DEVELOP.'>'.KERNEL_MENU_DEVELOP_DESIGN.'>'.$pm_title.'>'.$elem_list, 'design_list&pm_id='.$id, 'pl_default_list_24.png');
				    if ($elem_full != '')
				        $_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_DEVELOP.'>'.KERNEL_MENU_DEVELOP_DESIGN.'>'.$pm_title.'>'.$elem_full, 'design_full&pm_id='.$id, 'pl_default_full_24.png');

				    if (isset($pm_categs_settings['use_rubrikator']) && $pm_categs_settings['use_rubrikator'] != 0)
				    {
				        if ($elem_categs != '')
				            $_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_DEVELOP.'>'.KERNEL_MENU_DEVELOP_DESIGN.'>'.$pm_title.'>'.$elem_categs, 'design_categs&pm_id='.$id, 'pl_default_categs_24.png');
				        if ($elem_sel_cat != '')
				            $_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_DEVELOP.'>'.KERNEL_MENU_DEVELOP_DESIGN.'>'.$pm_title.'>'.$elem_sel_cat, 'design_sel_cat&pm_id='.$id, 'pl_default_sel_cat_24.png');
				    }

				    if ($elem_form != '')
				        $_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_DEVELOP.'>'.KERNEL_MENU_DEVELOP_DESIGN.'>'.$pm_title.'>'.$elem_form, 'design_form&pm_id='.$id, 'pl_default_form_24.png');
				    if ($elem_filter != '')
				        $_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_DEVELOP.'>'.KERNEL_MENU_DEVELOP_DESIGN.'>'.$pm_title.'>'.$elem_filter, 'design_filter&pm_id='.$id, 'pl_default_filter_form_24.png');
				}

				$_SESSION['sbPlugins']->addUserEvent('init', 'fPlugin_Maker_Plugins_Init', 'read', $pm_title);
			    $_SESSION['sbPlugins']->addUserEvent('edit', 'fPlugin_Maker_Plugins_Edit','read', isset($pm_elems_settings['edit_item_title']) ? $pm_elems_settings['edit_item_title'] : SB_ELEMS_EDIT_MENU);
			    $_SESSION['sbPlugins']->addUserEvent('edit_submit', 'fPlugin_Maker_Plugins_Edit_Submit', 'elems_edit', isset($pm_elems_settings['edit_item_title']) ? $pm_elems_settings['edit_item_title'] : SB_ELEMS_EDIT_MENU);
			    $_SESSION['sbPlugins']->addUserEvent('set_active', 'fPlugin_Maker_Plugins_Set_Active', 'elems_edit');
			    $_SESSION['sbPlugins']->addUserEvent('delete', 'fPlugin_Maker_Plugins_Delete', 'elems_delete');
			    $_SESSION['sbPlugins']->addUserEvent('paste', 'fPlugin_Maker_Plugins_Paste', 'elems_edit');

			    $_SESSION['sbPlugins']->addUserEvent('design_list', 'fPlugin_Maker_Plugins_Design_List', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_LIST_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_list_edit', 'fPlugin_Maker_Plugins_Design_List_Edit', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_list_edit_submit', 'fPlugin_Maker_Plugins_Design_List_Edit_Submit', 'design_edit', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_list_delete', 'fPlugin_Maker_Plugins_Design_List_Delete', 'design_edit');

				$_SESSION['sbPlugins']->addUserEvent('design_full', 'fPlugin_Maker_Plugins_Design_Full', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_FULL_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_full_edit', 'fPlugin_Maker_Plugins_Design_Full_Edit', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_full_edit_submit', 'fPlugin_Maker_Plugins_Design_Full_Edit_Submit', 'design_edit', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_full_delete', 'fPlugin_Maker_Plugins_Design_Full_Delete', 'design_edit');

				$_SESSION['sbPlugins']->addUserEvent('design_form', 'fPlugin_Maker_Plugins_Design_Form', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_FORM_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_form_edit', 'fPlugin_Maker_Plugins_Design_Form_Edit', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_form_edit_submit', 'fPlugin_Maker_Plugins_Design_Form_Edit_Submit', 'design_edit', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_form_delete', 'fPlugin_Maker_Plugins_Design_Form_Delete', 'design_edit');

				$_SESSION['sbPlugins']->addUserEvent('design_filter', 'fPlugin_Maker_Plugins_Design_Filter', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_FILTER_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_filter_edit', 'fPlugin_Maker_Plugins_Design_Filter_Edit', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_filter_edit_submit', 'fPlugin_Maker_Plugins_Design_Filter_Edit_Submit', 'design_edit', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
				$_SESSION['sbPlugins']->addUserEvent('design_filter_delete', 'fPlugin_Maker_Plugins_Design_Filter_Delete', 'design_edit');
				$_SESSION['sbPlugins']->addSystemEvent('elem_list_components', 'fPlugin_Maker_Elem_Components');

				if (isset($pm_categs_settings['use_rubrikator']) && $pm_categs_settings['use_rubrikator'] != 0)
				{
					$_SESSION['sbPlugins']->addUserEvent('design_categs', 'fPlugin_Maker_Design_Categs', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_CATEGS_TITLE);
	                $_SESSION['sbPlugins']->addUserEvent('design_categs_edit', 'fPlugin_Maker_Design_Categs_Edit', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
	                $_SESSION['sbPlugins']->addUserEvent('design_categs_edit_submit', 'fPlugin_Maker_Design_Categs_Edit_Submit', 'design_edit', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
	                $_SESSION['sbPlugins']->addUserEvent('design_categs_delete', 'fPlugin_Maker_Design_Categs_Delete', 'design_edit');

	                $_SESSION['sbPlugins']->addUserEvent('design_sel_cat', 'fPlugin_Maker_Design_Selcat', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_SEL_CAT_TITLE);
	                $_SESSION['sbPlugins']->addUserEvent('design_sel_cat_edit', 'fPlugin_Maker_Design_Selcat_Edit', 'design_read', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
	                $_SESSION['sbPlugins']->addUserEvent('design_sel_cat_edit_submit', 'fPlugin_Maker_Design_Selcat_Edit_Submit', 'design_edit', PL_PLUGIN_MAKER_H_DESIGN_EDIT_TITLE);
	                $_SESSION['sbPlugins']->addUserEvent('design_sel_cat_delete', 'fPlugin_Maker_Design_Selcat_Delete', 'design_edit');
				}

				$fields = array();
				$fields['p_id'] = array('title' => PL_PLUGIN_H_ID_LABEL, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_title'] = array('title' => (isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_H_TITLE_LABEL), 'flags' => '4', 'link_title' => '1', 'tag' => '{OPT_STD_P_TITLE}');
				$fields['p_url'] = array('title' => PL_PLUGIN_H_URL_LABEL, 'flags' => '4', 'link_title' => '1', 'tag' => '{OPT_STD_P_URL}');
				$fields['p_pub_start'] = array('title' => PL_PLUGIN_H_PUB_START_LABEL, 'flags' => '9', 'link_title' => '0', 'tag' => '');
				$fields['p_pub_end'] = array('title' => PL_PLUGIN_H_PUB_END_LABEL, 'flags' => '9', 'link_title' => '0', 'tag' => '');
				$fields['p_sort'] = array('title' => PL_PLUGIN_H_SORT_LABEL, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_active'] = array('title' => PL_PLUGIN_H_ACTIVE_LABEL, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_ext_id'] = array('title' => PL_PLUGIN_H_EXT_ID_LABEL, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_user_id'] = array('title' => PL_PLUGIN_H_USER_ID_LABEL, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_order'] = array('title' => PL_PLUGIN_H_ORDER_LABEL, 'flags' => '7', 'link_title' => '0', 'tag' => '');
				$fields['p_price1'] = array('title' => PL_PLUGIN_MAKER_H_CENA_1, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_price2'] = array('title' => PL_PLUGIN_MAKER_H_CENA_2, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_price3'] = array('title' => PL_PLUGIN_MAKER_H_CENA_3, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_price4'] = array('title' => PL_PLUGIN_MAKER_H_CENA_4, 'flags' => '8', 'link_title' => '0', 'tag' => '');
				$fields['p_price5'] = array('title' => PL_PLUGIN_MAKER_H_CENA_5, 'flags' => '8', 'link_title' => '0', 'tag' => '');

				$cat_fields = array();
				$cat_fields['moderates_list'] = array('title' => PL_PLUGIN_MAKER_H_MODERATES_LIST, 'flags' => '20');
				$cat_fields['categs_moderate_email'] = array('title' => PL_PLUGIN_MAKER_H_MODERATES_EMAILS, 'flags' => '4');

				$_SESSION['sbPlugins']->addFieldsInfo('sb_plugins_'.$id, 'p_id', 'p_title', $fields, $cat_fields);

				$_SESSION['sbPlugins']->addToConstructor('sb_plugins_'.$id);
				$_SESSION['sbPlugins']->addToCache();
	    	}

		    if (isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] != 0)
			{
				$_SESSION['sbPlugins']->addSystemEvent('elem_cloud', 'fPlugin_Maker_Get_Elem_Cloud', $elem_cloud);

				if ($elem_cloud != '')
				    $_SESSION['sbPlugins']->addElem($elem_cloud, 'cloud', 'fPlugin_Maker_Elem_Cloud', 'fPlugin_Maker_Elem_Cloud_Info', 'elem_cloud&pm_id='.$id, 800, 700);
			}

	    	if (isset($pm_categs_settings['use_rubrikator']) && $pm_categs_settings['use_rubrikator'] != 0)
	    	{
	    		$_SESSION['sbPlugins']->addSystemEvent('elem_categs', 'fPlugin_Maker_Get_Elem_Categs', $elem_categs);
			    $_SESSION['sbPlugins']->addSystemEvent('elem_sel_cat', 'fPlugin_Maker_Get_Elem_Sel_Cat', $elem_sel_cat);

			    $_SESSION['sbPlugins']->addToRubrikator('pl_plugin_'.$id, $pm_title, 'pl'.$id, 'pl_plugin_'.$id.'_design_categs', 'pl_plugin_'.$id.'_design_sel_cat');

			    if ($elem_categs != '')
			        $_SESSION['sbPlugins']->addElem($elem_categs, 'categs', 'fPlugin_Maker_Elem_Categs', 'fPlugin_Maker_Elem_Categs_Info', 'elem_categs&pm_id='.$id, 800, 700, 'design_categs_edit&pm_id='.$id, 'pl_plugin_'.$id.'_design_categs');
			    if ($elem_sel_cat != '')
			        $_SESSION['sbPlugins']->addElem($elem_sel_cat, 'sel_cat', 'fPlugin_Maker_Elem_Sel_Cat', 'fPlugin_Maker_Elem_Sel_Cat_Info', 'elem_sel_cat&pm_id='.$id, 800, 700, 'design_sel_cat_edit&pm_id='.$id, 'pl_plugin_'.$id.'_design_sel_cat');
	    	}

	    	$_SESSION['sbPlugins']->addToCalendar('list', 'fPlugin_Maker_Get_Calendar', 'pl_plugin_maker/prog/pl_plugin_maker.php', array());

	    	$_SESSION['sbPlugins']->addSystemEvent('elem_list', 'fPlugin_Maker_Get_Elem_List', $elem_list);
			$_SESSION['sbPlugins']->addSystemEvent('elem_full', 'fPlugin_Maker_Get_Elem_Full', $elem_full);
			$_SESSION['sbPlugins']->addSystemEvent('elem_form', 'fPlugin_Maker_Get_Elem_Form', $elem_form);
			$_SESSION['sbPlugins']->addSystemEvent('elem_filter', 'fPlugin_Maker_Get_Elem_Filter', $elem_form);

			if ($elem_list != '')
			    $_SESSION['sbPlugins']->addElem($elem_list, 'list', 'fPlugin_Maker_Elem_List_Com', 'fPlugin_Maker_Elem_List_Info', 'elem_list&pm_id='.$id, 800, 700, 'design_list_edit&pm_id='.$id, 'pl_plugin_'.$id.'_design_list');
			if ($elem_full != '')
			    $_SESSION['sbPlugins']->addElem($elem_full, 'full', 'fPlugin_Maker_Elem_Full_Com', 'fPlugin_Maker_Elem_Full_Info', 'elem_full&pm_id='.$id, 800, 720, 'design_full_edit&pm_id='.$id, 'pl_plugin_'.$id.'_design_full');
			if ($elem_title_html != '')
			    $_SESSION['sbPlugins']->addElem($elem_title_html, 'header_html', 'fPlugin_Maker_Elem_Header_Html');
			if ($elem_title_plain != '')
			    $_SESSION['sbPlugins']->addElem($elem_title_plain, 'header_plain', 'fPlugin_Maker_Elem_Header_Plain');
			if ($elem_form != '')
			    $_SESSION['sbPlugins']->addElem($elem_form, 'form', 'fPlugin_Maker_Elem_Form', 'fPlugin_Maker_Elem_Form_Info', 'elem_form&pm_id='.$id, 800, 700, 'design_form_edit&pm_id='.$id, 'pl_plugin_'.$id.'_design_form');
			if ($elem_filter != '')
			    $_SESSION['sbPlugins']->addElem($elem_filter, 'filter', 'fPlugin_Maker_Elem_Filter', 'fPlugin_Maker_Elem_Flter_Info', 'elem_filter&pm_id='.$id, 800, 300, 'design_filter_edit&pm_id='.$id, 'pl_plugin_'.$id.'_filter');

			sb_add_rights();

			$_SESSION['sbPlugins']->addEventsRights('elems_public', RIGHTS_H_ELEMS_PUBLIC_RIGHT);
			$_SESSION['sbPlugins']->addEventsRights('design_read', RIGHTS_H_DESIGN_READ_RIGHT);
			$_SESSION['sbPlugins']->addEventsRights('design_edit', RIGHTS_H_DESIGN_EDIT_RIGHT);
    	}
	    else
	    {
	    	if ($error > 1)
	    	{
	        	sql_query('DELETE FROM sb_plugins_maker WHERE pm_id="'.$id.'"');
	    	}

	    	if ($error > 2)
	    	{
	    		sql_query('DROP TABLE `sb_plugins_'.$id.'`');
	    	}

	    	if ($error == 2)
	    	{
	    		sb_show_message(sprintf(PL_PLUGIN_MAKER_CREATE_TABLE_SYSTEMLOG_ERROR, $pm_title), false, 'warning');
	            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_CREATE_TABLE_SYSTEMLOG_ERROR, $pm_title), SB_MSG_WARNING);
	    	}
	    	else
	    	{
	    		sb_show_message(sprintf(PL_PLUGIN_MAKER_ADD_ERROR, $pm_title), false, 'warning');
            	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_ADD_SYSTEMLOG_ERROR, $pm_title), SB_MSG_WARNING);
	    	}

	    	fPlugin_Maker_Edit();
            return;
	    }
    }
}

function fPlugin_Maker_Delete()
{
	if (!fCategs_Check_Rights($_GET['cat_id']))
    {
        return;
    }

    $id = intval($_GET['id']);
    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$id.'"');
    if (!$res)
    {
    	echo PL_PLUGIN_MAKER_DELETE_ERROR;
    	return;
    }

    list($pm_title) = $res[0];

    // Удаляем таблицу модуля
    // sb_plugin_$id
    if (!sql_query('DROP TABLE `sb_plugins_'.$id.'`'))
    {
    	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR1, $pm_title), SB_MSG_WARNING);
        echo PL_PLUGIN_MAKER_DELETE_ERROR;
    }

    $error = false;
    // Удаляем макеты данных модуля
    // sb_plugins_data
    if (!sql_query('DELETE FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$id.'"'))
    {
    	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR2, $pm_title), SB_MSG_WARNING);
    	$error = true;
    }

    // Удаляем макеты дизайна модуля
    // sb_categs_temps_list, sb_categs_temps_full, sb_plugins_temps_list, sb_plugins_temps_full, sb_plugins_temps_form

    // Макеты дизайна вывода разделов
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_'.$id.'_design_categs"');
    if ($res)
    {
    	$in_str = '(0';
    	foreach ($res as $value)
    	{
    		$in_str .= ','.$value[0];
    	}
    	$in_str .= ')';

    	$res = sql_query('SELECT link_el_id FROM sb_catlinks WHERE link_cat_id IN '.$in_str);
    	if ($res)
    	{
    		$el_in_str = '(0';
	        foreach ($res as $value)
	        {
	            $el_in_str .= ','.$value[0];
	        }
	        $el_in_str .= ')';

	    	if (!sql_query('DELETE FROM sb_categs_temps_list WHERE ctl_id IN '.$el_in_str))
	    	{
	    		sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
	            $error = true;
	    	}
	    	else if (!sql_query('DELETE FROM sb_catlinks WHERE link_cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
	    	else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
	        {
	            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
	            $error = true;
	        }
    	}
    	else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
    	{
    		sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
            $error = true;
    	}
    }

    // Макеты дизайна вывода выбранного раздела
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_'.$id.'_design_sel_cat"');
    if ($res)
    {
        $in_str = '(0';
        foreach ($res as $value)
        {
            $in_str .= ','.$value[0];
        }
        $in_str .= ')';

        $res = sql_query('SELECT link_el_id FROM sb_catlinks WHERE link_cat_id IN '.$in_str);
        if ($res)
        {
            $el_in_str = '(0';
            foreach ($res as $value)
            {
                $el_in_str .= ','.$value[0];
            }
            $el_in_str .= ')';

            if (!sql_query('DELETE FROM sb_categs_temps_full WHERE ctf_id IN '.$el_in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_catlinks WHERE link_cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
        }
        else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
    }

    // Макеты дизайна вывода списка элементов
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_'.$id.'_design_list"');
    if ($res)
    {
        $in_str = '(0';
        foreach ($res as $value)
        {
            $in_str .= ','.$value[0];
        }
        $in_str .= ')';

        $res = sql_query('SELECT link_el_id FROM sb_catlinks WHERE link_cat_id IN '.$in_str);
        if ($res)
        {
            $el_in_str = '(0';
            foreach ($res as $value)
            {
                $el_in_str .= ','.$value[0];
            }
            $el_in_str .= ')';

            if (!sql_query('DELETE FROM sb_plugins_temps_list WHERE ptl_id IN '.$el_in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_catlinks WHERE link_cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
        }
        else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
    }

    // Макеты дизайна вывода выбранного элементов
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_'.$id.'_design_full"');
    if ($res)
    {
        $in_str = '(0';
        foreach ($res as $value)
        {
            $in_str .= ','.$value[0];
        }
        $in_str .= ')';

        $res = sql_query('SELECT link_el_id FROM sb_catlinks WHERE link_cat_id IN '.$in_str);
        if ($res)
        {
            $el_in_str = '(0';
            foreach ($res as $value)
            {
                $el_in_str .= ','.$value[0];
            }
            $el_in_str .= ')';

            if (!sql_query('DELETE FROM sb_plugins_temps_full WHERE ptf_id IN '.$el_in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_catlinks WHERE link_cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
        }
        else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
    }

    // Макеты дизайна вывода формы добавления элементов и формы фильтра
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_'.$id.'_design_form" OR cat_ident="pl_plugin_'.$id.'_filter" OR cat_ident="pl_plugin_'.$id.'_maker_informer"');
    if ($res)
    {
        $in_str = '(0';
        foreach ($res as $value)
        {
            $in_str .= ','.$value[0];
        }
        $in_str .= ')';

        $res = sql_query('SELECT link_el_id FROM sb_catlinks WHERE link_cat_id IN '.$in_str);
        if ($res)
        {
            $el_in_str = '(0';
            foreach ($res as $value)
            {
                $el_in_str .= ','.$value[0];
            }
            $el_in_str .= ')';

            if (!sql_query('DELETE FROM sb_plugins_temps_form WHERE ptf_id IN '.$el_in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_catlinks WHERE link_cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
            else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
                $error = true;
            }
        }
        else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR3, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
    }

    // Удаляем разделы модуля
    // sb_categs, sb_catlinks, sb_catrights
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_'.$id.'"');
    if ($res)
    {
        $in_str = '(0';
        foreach ($res as $value)
        {
            $in_str .= ','.$value[0];
        }
        $in_str .= ')';

        if (!sql_query('DELETE FROM sb_catlinks WHERE link_cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR4, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
        else if (!sql_query('DELETE FROM sb_catrights WHERE cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR4, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
        else if (!sql_query('DELETE FROM sb_categs WHERE cat_id IN '.$in_str))
        {
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR4, $pm_title), SB_MSG_WARNING);
            $error = true;
        }
    }

    // Удаляем комментарии, связанные с элементами модуля
    // sb_comments
    if (!sql_query('DELETE FROM sb_comments WHERE c_plugin="pl_plugin_'.$id.'"'))
    {
    	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR5, $pm_title), SB_MSG_WARNING);
        $error = true;
    }

    // Удаляем голосования, связанные с элементами модуля
    // sb_vote_results, sb_vote_ips
    if (!sql_query('DELETE sb_vote_results, sb_vote_ips FROM sb_vote_results, sb_vote_ips
        WHERE sb_vote_results.vr_plugin="pl_plugin_'.$id.'" AND sb_vote_ips.vi_vr_id=sb_vote_results.vr_id'))
    {
    	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR6, $pm_title), SB_MSG_WARNING);
        $error = true;
    }

    // Удаляем связи с тематическими тегами
    // sb_clouds_links
    if (!sql_query('DELETE FROM sb_clouds_links WHERE cl_ident="pl_plugin_'.$id.'"'))
    {
    	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR8, $pm_title), SB_MSG_WARNING);
        $error = true;
    }

    // Удаляем компоненты модуля, вытаскиваем страницы и перегенерируем их
    // sb_elems
    $res = sql_query('SELECT e_link, e_p_id FROM sb_elems WHERE e_ident="pl_plugin_'.$id.'_list"
            OR e_ident="pl_plugin_'.$id.'_full" OR e_ident="pl_plugin_'.$id.'_categs"
            OR e_ident="pl_plugin_'.$id.'_sel_cat" OR e_ident="pl_plugin_'.$id.'_form"
            OR e_ident="pl_plugin_'.$id.'_cloud" OR e_ident="pl_plugin_'.$id.'_filter"
            OR e_ident="pl_plugin_'.$id.'_header_html" OR e_ident="pl_plugin_'.$id.'_header_plain"');

    if ($res)
    {
    	if (!sql_query('DELETE FROM sb_elems WHERE e_ident="pl_plugin_'.$id.'_list"
            OR e_ident="pl_plugin_'.$id.'_full" OR e_ident="pl_plugin_'.$id.'_categs"
            OR e_ident="pl_plugin_'.$id.'_sel_cat" OR e_ident="pl_plugin_'.$id.'_form"
            OR e_ident="pl_plugin_'.$id.'_cloud" OR e_ident="pl_plugin_'.$id.'_filter"
            OR e_ident="pl_plugin_'.$id.'_header_html" OR e_ident="pl_plugin_'.$id.'_header_plain"'))
    	{
    		sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DELETE_SYSTEMLOG_ERROR7, $pm_title), SB_MSG_WARNING);
            $error = true;
    	}
    	else
    	{
    		$pages = array();
    		foreach ($res as $value)
    		{
    			list($e_link, $e_p_id) = $value;

    			if ($e_link == 'page')
    			{
    				if (!in_array($e_p_id, $pages))
    				{
    					fPages_Gen_Page($e_p_id);
    					$pages[] = $e_p_id;
    				}
    			}
    			else
    			{
                    $res_pages = sql_query('SELECT p_id FROM sb_pages WHERE p_temp_id="'.$e_p_id.'"');
                    if ($res_pages)
                    {
                    	foreach ($res_pages as $p_id)
                    	{
	                    	if (!in_array($p_id[0], $pages))
		                    {
		                        fPages_Gen_Page($p_id[0]);
		                        $pages[] = $p_id[0];
		                    }
                    	}
                    }
    			}
    		}
    	}
    }

    if ($error)
    {
    	echo PL_PLUGIN_MAKER_DELETE_ERROR;
    }
    else
    {
    	$_SESSION['sbPlugins']->unregister('pl_plugin_'.$id);
    }
}

function fPlugin_Maker_Paste()
{
	#TODO: Перегружать систему после копирования
	if ($_GET['action'] == 'copy')
    {
        if (!isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['ne']) || !is_array($_GET['ne']) || count($_GET['ne']) <= 0)
        {
            return;
        }

        $error = false;
        $temps_ident = array(
                'design_categs',
                'design_sel_cat',
                'design_list',
                'design_full',
                'design_form',
                'filter',
                'maker_informer',
            );

        foreach ($_GET['e'] as $key => $old_id)
        {
            $new_id = intval($_GET['ne'][$key]);

            $res = sql_query('SHOW CREATE TABLE `sb_plugins_'.intval($old_id).'`');
            if ($res)
            {
            	$sql = str_replace('sb_plugins_'.$old_id, 'sb_plugins_'.$new_id, $res[0][1]);
            	if (sql_query($sql))
            	{
            		$res = sql_query('SELECT pd_fields, pd_categs, pd_increment FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$old_id.'"');
            		if ($res)
            		{
            			foreach ($res as $value)
            			{
            				list($pd_fields, $pd_categs, $pd_increment) = $value;

            				$row = array();
            				$row['pd_fields'] = $pd_fields;
            				$row['pd_categs'] = $pd_categs;
            				$row['pd_increment'] = $pd_increment;
            				$row['pd_plugin_ident'] = 'pl_plugin_'.$new_id;

            				sql_param_query('INSERT INTO sb_plugins_data SET ?a', $row);
            			}
            		}
            	}
            	else
            	{
            		sb_add_system_message(sprintf(PL_PLUGIN_MAKER_COPY_SYSTEMLOG_ERROR, 'sb_plugins_'.$new_id), SB_MSG_WARNING);
            		$error = true;
            	}
            }
            else
            {
            	sb_add_system_message(sprintf(PL_PLUGIN_MAKER_COPY_SYSTEMLOG_ERROR, 'sb_plugins_'.$new_id), SB_MSG_WARNING);
            	$error = true;
            }

            //Копируем макеты дизайна модуля
            fPlugin_Maker_Temps_Copy($old_id, $new_id, $temps_ident);
        }

        if ($error)
        {
        	echo PL_PLUGIN_MAKER_COPY_ERROR;
        }
    }
}

/**
 * Функция копирования макетов дизайна модулей. Используется при копировании модулей
 * @param int $old_id       ID копируемого модуля
 * @param int $new_id       ID копии
 * @param array $idents     Массив идентификаторов макетов (без префикса pl_plugin_N_)
 */
function fPlugin_Maker_Temps_Copy($old_id, $new_id, $idents=array())
{
    if(empty($idents))
    {
        return;
    }

    //Массив соответствия идентификаторов макетов и таблиц
    $temps_tables = array(
        'maker_informer' => array('table' => 'sb_plugins_temps_form', 'pkey' => 'ptf_id'),
        'filter' => array('table' => 'sb_plugins_temps_form', 'pkey' => 'ptf_id'),
        'design_form' => array('table' => 'sb_plugins_temps_form', 'pkey' => 'ptf_id'),
        'design_full' => array('table' => 'sb_plugins_temps_full', 'pkey' => 'ptf_id'),
        'design_list' => array('table' => 'sb_plugins_temps_list', 'pkey' => 'ptl_id'),
        'design_sel_cat' => array('table' => 'sb_categs_temps_full', 'pkey' => 'ctf_id'),
        'design_categs' => array('table' => 'sb_categs_temps_list', 'pkey' => 'ctl_id'),
    );

    foreach($idents as $temp)
    {
        if(!array_key_exists($temp, $temps_tables))
        {
            continue;
        }

        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_plugin_' . $old_id . '_' . $temp .'"');

        if ($res)
        {
            foreach ($res as $row)
            {
                $new_cat_id = $GLOBALS['sbSql']->duplicateRow($row[0], 'cat_id', 'sb_categs', '', array('cat_ident' => "pl_plugin_{$new_id}_$temp"));
                if ($new_cat_id <= 0)
                {
                    continue;
                }

                //копируем элементы данного раздела
                $res1 = sql_param_query('SELECT link_el_id FROM sb_catlinks WHERE link_cat_id=?d', $row[0]);
                if ($res1)
                {
                    foreach ($res1 as $row1)
                    {
                        $new_temps_id = $GLOBALS['sbSql']->duplicateRow($row1[0], $temps_tables[$temp]['pkey'], $temps_tables[$temp]['table']);

                        if ($new_temps_id > 0)
                        {
                            $insert_row = array(
                                'link_cat_id' => $new_cat_id,
                                'link_el_id' => $new_temps_id
                            );
                            sql_param_query('INSERT INTO sb_catlinks SET ?a', $insert_row);
                            sql_param_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_user_id, change_date, action) values(
									?d, ?, ?d, ?, "add")', $new_temps_id, "pl_plugin_{$new_id}_$temp", $_SESSION['sbAuth']->getUserId(), date('U'));
                        }
                    }
                }
            }
        }
    }
}

/**
 * Функции управления модулями
 **/

/**
 * Формирование вывода элемента в списке
 *
 * @param array $args Массив значений выводимых полей (ключ массива - название поля в таблице БД)
 * @param int $real_id Если больше 0, значит выводим публикуемые на сайте значения
 *
 * @return string Отформатированный вывод значений элемента в списке.
 */
function fPlugin_Maker_Plugins_Get($args, $real_id = 0)
{
	static $pm_settings = false;

	if (!$pm_settings)
	{
        $res = sql_param_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $_GET['pm_id']);
        if (!$res)
            return '';

        list($pm_settings) = $res[0];

        if ($pm_settings != '')
            $pm_settings = unserialize($pm_settings);
        else
            $pm_settings = array();
	}

	$real_args = $args;
	if (isset($args['p_demo_id']) && $args['p_demo_id'] > 0 && $real_id <= 0)
	{
		$fields = array_keys($args);

		$res = sql_query('SELECT '.implode(',', $fields).' FROM sb_plugins_'.intval($_GET['pm_id']).' WHERE p_id=?d', $args['p_demo_id']);
		if ($res)
		{
			foreach ($res[0] as $key => $value)
			{
				$args[$fields[$key]] = $value;
			}
		}

		$args['p_demo_id'] = $real_args['p_demo_id'];
	}

	if ($real_id > 0)
	{
		$result = strip_tags($args['p_title'])
			.'<br /><div class="smalltext" style="margin-top: 7px;">';
	}
	else
	{
		$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);" id="p_title_'.$real_args['p_id'].'">'
			.strip_tags($args['p_title'])
			.'</a></b>'
			.'<div class="smalltext" style="margin-top: 7px;">';
	}

	if ($real_id <= 0)
	{
		$result .= PL_PLUGIN_MAKER_ADD_ID_COMMENT.': <span style="color: #33805E;">'.$real_args['p_id'].'</span><br />';
	}

	if(isset($args['p_user_id']) && !is_null($args['p_user_id']) && $args['p_user_id'] > 0)
	{
		$res_str = fSite_Users_Get_User_Link($args['p_user_id']);
		if($res_str != '')
		{
			$result .= PL_PLUGIN_MAKER_PLUGINS_GET_SITE_USER.': '.$res_str;
		}
	}

	require_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';

	$p_price1 = $args['p_price1'];
	$p_price2 = $args['p_price2'];
	$p_price3 = $args['p_price3'];
	$p_price4 = $args['p_price4'];
	$p_price5 = $args['p_price5'];

	if ((!isset($pm_settings['price1_type']) || $pm_settings['price1_type'] == 0) && isset($pm_settings['price1_formula']))
	{
		// рассчитываем цену
		$p_price1 = fPlugin_Maker_Quoting($_GET['pm_id'], $args['p_id'], $pm_settings['price1_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
	}

	if ((!isset($pm_settings['price2_type']) || $pm_settings['price2_type'] == 0) && isset($pm_settings['price2_formula']))
	{
		// рассчитываем цену
		$p_price2 = fPlugin_Maker_Quoting($_GET['pm_id'], $args['p_id'], $pm_settings['price2_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
	}

	if ((!isset($pm_settings['price3_type']) || $pm_settings['price3_type'] == 0) && isset($pm_settings['price3_formula']))
	{
		// рассчитываем цену
		$p_price3 = fPlugin_Maker_Quoting($_GET['pm_id'], $args['p_id'], $pm_settings['price3_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
	}

	if ((!isset($pm_settings['price4_type']) || $pm_settings['price4_type'] == 0) && isset($pm_settings['price4_formula']))
	{
		// рассчитываем цену
		$p_price4 = fPlugin_Maker_Quoting($_GET['pm_id'], $args['p_id'], $pm_settings['price4_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
	}

	if ((!isset($pm_settings['price5_type']) || $pm_settings['price5_type'] == 0) && isset($pm_settings['price5_formula']))
	{
		// рассчитываем цену
		$p_price5 = fPlugin_Maker_Quoting($_GET['pm_id'], $args['p_id'], $pm_settings['price5_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
	}

	if ($p_price1 != '' && !is_null($p_price1) && isset($pm_settings['show_price1_in_list']) && $pm_settings['show_price1_in_list'] == 1)
	{
        $result .= '<br />'.(isset($pm_settings['price1_title']) && $pm_settings['price1_title'] != '' ? $pm_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '1')).': <span style="color: #33805E;">'.$p_price1.'</span>';
	}

	if ($p_price2 != '' && !is_null($p_price2) && isset($pm_settings['show_price2_in_list']) && $pm_settings['show_price2_in_list'] == 1)
	{
        $result .= '<br />'.(isset($pm_settings['price2_title']) && $pm_settings['price2_title'] != '' ? $pm_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '2')).': <span style="color: #33805E;">'.$p_price2.'</span>';
	}

	if ($p_price3 != '' && !is_null($p_price3) && isset($pm_settings['show_price3_in_list']) && $pm_settings['show_price3_in_list'] == 1)
	{
        $result .= '<br />'.(isset($pm_settings['price3_title']) && $pm_settings['price3_title'] != '' ? $pm_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '3')).': <span style="color: #33805E;">'.$p_price3.'</span>';
	}

	if ($p_price4 != '' && !is_null($p_price4) && isset($pm_settings['show_price4_in_list']) && $pm_settings['show_price4_in_list'] == 1)
	{
        $result .= '<br />'.(isset($pm_settings['price4_title']) && $pm_settings['price4_title'] != '' ? $pm_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '4')).': <span style="color: #33805E;">'.$p_price4.'</span>';
	}

	if ($p_price5 != '' && !is_null($p_price5) && isset($pm_settings['show_price5_in_list']) && $pm_settings['show_price5_in_list'] == 1)
	{
        $result .= '<br />'.(isset($pm_settings['price5_title']) && $pm_settings['price5_title'] != '' ? $pm_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '5')).': <span style="color: #33805E;">'.$p_price5.'</span>';
	}

	if (isset($pm_settings['show_sort_field']) && $pm_settings['show_sort_field'] == 1)
	{
        $result .= '<br />'.PL_PLUGIN_MAKER_PLUGINS_GET_SORT.': <span style="color: #33805E;">'.$args['p_sort'].'</span>';
	}

	if ($real_id <= 0)
	{
		if (isset($pm_settings['show_comments']) && $pm_settings['show_comments'] == 1 && $_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.intval($_GET['pm_id']), 'elems_com_show'))
	    {
	    	$result .= fComments_Get_Count_Get($real_args['p_id'], 'pl_plugin_'.intval($_GET['pm_id']));
	    }

	    if (isset($pm_settings['show_voting']) && $pm_settings['show_voting'] == 1)
	    {
	    	$result .= fVoting_Rating_Get($real_args['p_id'], 'pl_plugin_'.intval($_GET['pm_id']));
	    }
	}

	if (isset($pm_settings['show_active_field']) && $pm_settings['show_active_field'] == 1)
	{
		sb_get_workflow_status($result, 'pl_plugin_'.intval($_GET['pm_id']), $args['p_active'], $args['p_pub_start'], $args['p_pub_end']);
	}

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $result .= sbLayout::getPluginFieldsInfo('pl_plugin_'.intval($_GET['pm_id']), $args);

    if (isset($args['p_demo_id']) && $args['p_demo_id'] > 0 && $real_id <= 0)
    {
    	$result .= '<br /><br /><br /><b>'.PL_PLUGIN_MAKER_DEMO_VALUES.':</b><div style="color: #808080; padding-top: 7px; margin-top: 7px; border-top: 1px solid #d0d0d0">'.fPlugin_Maker_Plugins_Get($real_args, $real_args['p_id']).'</div>';
    }

    $result .= '</div>';
    $result = str_replace(array('<div class="smalltext" style="margin-top: 7px;"><br />', '<br /><br />'), array('<div class="smalltext" style="margin-top: 7px;">', '<br />'), $result);
    return $result;
}

function fPlugin_Maker_Plugins_Init(&$elems = '', $external = false)
{
    if (!isset($_GET['pm_id']))
        return;

    $id = intval($_GET['pm_id']);

	$res = sql_query('SELECT pm_categs_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$id.'"');
	if (!$res)
		return;

	list($pm_categs_settings, $pm_elems_settings) = $res[0];

	if ($pm_categs_settings != '')
		$pm_categs_settings = unserialize($pm_categs_settings);
	else
		$pm_categs_settings = array();

	if ($pm_elems_settings != '')
		$pm_elems_settings = unserialize($pm_elems_settings);
	else
		$pm_elems_settings = array();

	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

	$elems = new sbElements('sb_plugins_'.$id, 'p_id', 'p_title', 'fPlugin_Maker_Plugins_Get', 'pl_plugin_'.$id.'_init&pm_id='.$id, 'pl_plugin_'.$id, 'p_url');

    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_32.png';
    $elems->mShowIcons = isset($pm_elems_settings['show_icons']) && $pm_elems_settings['show_icons'] == 1;

    $elems->addField('p_active');     // элемент активен или нет
    $elems->addField('p_pub_start');  // дата начала публикации
    $elems->addField('p_pub_end');    // дата окончания публикации
    $elems->addField('p_sort');       // индекс сортировки
    $elems->addField('p_price1');     // цена 1
    $elems->addField('p_price2');     // цена 2
    $elems->addField('p_price3');     // цена 3
    $elems->addField('p_price4');     // цена 4
    $elems->addField('p_price5');     // цена 5
    $elems->addField('p_user_id');    // Идентификатор пользователся сайта
    $elems->addField('p_demo_id');    // Идентификатор реального элемента

    $elems->addCategsClosedDescr('read', PL_PLUGIN_MAKER_PLUGINS_GROUP_READ);
	$elems->addCategsClosedDescr('edit', PL_PLUGIN_MAKER_PLUGINS_GROUP_EDIT);

	$elems->mCategsPasteWithElementsMenu = true;

	if (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1)
	{
		$elems->addCategsClosedDescr('comments_read', PL_PLUGIN_MAKER_PLUGINS_GROUP_COMMENTS_READ);
		$elems->addCategsClosedDescr('comments_edit', PL_PLUGIN_MAKER_PLUGINS_GROUP_COMMENTS_EDIT);
	}

	if (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] == 1)
	{
		$elems->addCategsClosedDescr('vote', PL_PLUGIN_MAKER_PLUGINS_GROUP_VOTE);
	}

	$elems->addFilter(PL_PLUGIN_MAKER_ADD_ID_COMMENT, 'p_id', 'number');
    $elems->addFilter(isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_EDIT_TITLE, 'p_title', 'string');

	$elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_ID, 'p_id');
	$elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_TITLE, 'p_title');

	if (isset($pm_elems_settings['sort_price1_in_list']) && $pm_elems_settings['sort_price1_in_list'] == 1)
	{
		$elems->addSorting(isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY, $pm_elems_settings['price1_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PRICE, '1'), 'p_price1');
	}

	if (isset($pm_elems_settings['sort_price2_in_list']) && $pm_elems_settings['sort_price2_in_list'] == 1)
    {
    	$elems->addSorting(isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY, $pm_elems_settings['price2_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PRICE, '2'), 'p_price2');
    }

	if (isset($pm_elems_settings['sort_price3_in_list']) && $pm_elems_settings['sort_price3_in_list'] == 1)
    {
    	$elems->addSorting(isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY, $pm_elems_settings['price3_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PRICE, '3'), 'p_price3');
    }

	if (isset($pm_elems_settings['sort_price4_in_list']) && $pm_elems_settings['sort_price4_in_list'] == 1)
    {
    	$elems->addSorting(isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY, $pm_elems_settings['price4_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PRICE, '4'), 'p_price4');
    }

	if (isset($pm_elems_settings['sort_price5_in_list']) && $pm_elems_settings['sort_price5_in_list'] == 1)
    {
    	$elems->addSorting(isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY, $pm_elems_settings['price5_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PRICE, '5'), 'p_price5');
    }

	if (isset($pm_elems_settings['filter_price1_in_list']) && $pm_elems_settings['filter_price1_in_list'] == 1)
    {
    	$elems->addFilter(isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '1'), 'p_price1', 'number');
    }

	if (isset($pm_elems_settings['filter_price2_in_list']) && $pm_elems_settings['filter_price2_in_list'] == 1)
    {
    	$elems->addFilter(isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '2'), 'p_price2', 'number');
    }

	if (isset($pm_elems_settings['filter_price3_in_list']) && $pm_elems_settings['filter_price3_in_list'] == 1)
    {
    	$elems->addFilter(isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '3'), 'p_price3', 'number');
    }

	if (isset($pm_elems_settings['filter_price4_in_list']) && $pm_elems_settings['filter_price4_in_list'] == 1)
    {
    	$elems->addFilter(isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '4'), 'p_price4', 'number');
    }

	if (isset($pm_elems_settings['filter_price5_in_list']) && $pm_elems_settings['filter_price5_in_list'] == 1)
    {
    	$elems->addFilter(isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_GET_PRICE, '5'), 'p_price5', 'number');
    }

    if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
    {
    	$elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_SORT, 'p_sort');
    	$elems->addFilter(PL_PLUGIN_MAKER_PLUGINS_SORT, 'p_sort', 'number');
    }

    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
    {
    	$elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_ACTIVE, 'p_active');
        $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PUB_START, 'p_pub_start');
        $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_SORT_BY_PUB_END, 'p_pub_end');

		sb_add_workflow_filter($elems, 'pl_plugin_'.$id, 'p_active');

	    $elems->addFilter(PL_PLUGIN_MAKER_PLUGINS_PUB_START, 'p_pub_start', 'date');
	    $elems->addFilter(PL_PLUGIN_MAKER_PLUGINS_PUB_END, 'p_pub_end', 'date');
    }

    if (isset($pm_categs_settings['add_item_title']))
        $elems->mCategsAddMenuTitle = $pm_categs_settings['add_item_title'];
    if (isset($pm_categs_settings['edit_item_title']))
        $elems->mCategsEditMenuTitle = $pm_categs_settings['edit_item_title'];
    if (isset($pm_categs_settings['delete_item_title']))
        $elems->mCategsDeleteMenuTitle = $pm_categs_settings['delete_item_title'];
    if (isset($pm_categs_settings['delete_we_item_title']))
        $elems->mCategsDeleteWithElementsMenuTitle = $pm_categs_settings['delete_we_item_title'];
    if (isset($pm_categs_settings['paste_item_title']))
        $elems->mCategsPasteMenuTitle = $pm_categs_settings['paste_item_title'];
    if (isset($pm_categs_settings['paste_with_elements_item_title']))
        $elems->mCategsPasteWithElementsMenuTitle = $pm_categs_settings['paste_with_elements_item_title'];
    if (isset($pm_categs_settings['copy_item_title']))
        $elems->mCategsCopyMenuTitle = $pm_categs_settings['copy_item_title'];
    if (isset($pm_categs_settings['cut_item_title']))
        $elems->mCategsCutMenuTitle = $pm_categs_settings['cut_item_title'];

    if (isset($pm_elems_settings['paste_links_item_title']))
        $elems->mCategsPasteLinksMenuTitle = $pm_elems_settings['paste_links_item_title'];
    if (isset($pm_elems_settings['paste_item_title']))
        $elems->mCategsPasteElemsMenuTitle = $pm_elems_settings['paste_item_title'];
    if (isset($pm_elems_settings['add_item_title']))
        $elems->mElemsAddMenuTitle = $pm_elems_settings['add_item_title'];
    if (isset($pm_elems_settings['edit_item_title']))
        $elems->mElemsEditMenuTitle = $pm_elems_settings['edit_item_title'];
    if (isset($pm_elems_settings['copy_item_title']))
        $elems->mElemsCopyMenuTitle = $pm_elems_settings['copy_item_title'];
    if (isset($pm_elems_settings['cut_item_title']))
        $elems->mElemsCutMenuTitle = $pm_elems_settings['cut_item_title'];

    $elems->mElemsEditEvent = 'pl_plugin_'.$id.'_edit&pm_id='.$id;
    $elems->mElemsEditDlgWidth = 800;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent = 'pl_plugin_'.$id.'_edit&pm_id='.$id;
    $elems->mElemsAddDlgWidth = 800;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$id.'_delete&pm_id='.$id;
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsBeforeDeleteWithElementsEvent = 'pl_plugin_'.$id.'_before_delete_cat_with_elements&pm_id='.$id;

    $elems->mElemsAfterPasteEvent = 'pl_plugin_'.$id.'_paste&pm_id='.$id;
    $elems->mCategsAfterPasteWithElementsEvent = 'pl_plugin_'.$id.'_after_paste_with_elements&pm_id='.$id;

    if(isset($pm_categs_settings['show_moderate']) && $pm_categs_settings['show_moderate'] == 1)
    {
    	$elems->mCategsModerators = true;
    }

    $elems->mCategsUrl = true;
    $elems->mCategsFields = true;
    $elems->mCategsClosed = isset($pm_categs_settings['site_users_rights']) && $pm_categs_settings['site_users_rights'] == 1;
    $elems->mCategsRubrikator = isset($pm_categs_settings['use_rubrikator']) && $pm_categs_settings['use_rubrikator'] == 1;
    $elems->mElemsUseLinks = isset($pm_elems_settings['use_links']) && $pm_elems_settings['use_links'] == 1;

    if (isset($pm_elems_settings['show_icon']) && $pm_elems_settings['show_icon'] == 1)
        $elems->mElemsIcon = '';

    if (isset($pm_categs_settings['max_deep']) && $pm_categs_settings['max_deep'] != 0)
    {
        $elems->mCategsMaxDeep = intval($pm_categs_settings['max_deep']);
    }

    if (isset($pm_elems_settings['add_level']) && $pm_elems_settings['add_level'] != 0)
    {
    	$elems->mElemsMenuLevel = intval($pm_elems_settings['add_level']);
    	$elems->mElemsCopyMenuLevel = intval($pm_elems_settings['add_level']);
    	$elems->mElemsCutMenuLevel = intval($pm_elems_settings['add_level']);
    	$elems->mElemsDeleteMenuLevel = intval($pm_elems_settings['add_level']);
    	$elems->mCategsPasteElemsMenuLevel = intval($pm_elems_settings['add_level']);
    }

    $elems->mElemsJavascriptStr = '';

    if (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1 && $_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$id, 'elems_com_show'))
    {
    	$elems->mElemsJavascriptStr .= '
    	var timer = "";
        function showComments(p_id)
        {
            if (typeof(p_id) == "undefined")
                p_id = sbSelEl.getAttribute("el_id");

            var p_title = sbGetE("p_title_" + p_id);

            if (p_title)
            {
            	if(timer != "undefined")
					clearInterval(timer);

                sbShowCommentaryWindow("pl_plugin_'.$id.'", p_id, p_title.innerHTML);
            }
        }';

    	$elems->addElemsMenuItem(PL_PLUGIN_MAKER_PLUGINS_SHOW_COMMENT_ITEM, 'showComments()', false);
    }

    if ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$id, 'elems_public') && isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1
		&& (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_plugin_'.$id)))
    {
        $elems->mElemsJavascriptStr .= '
            function elemsSetActive()
            {
                var ids = "0";
                for (var i = 0; i < sbSelectedEls.length; i++)
			    {
			        var el = sbGetE("el_" + sbSelectedEls[i]);
			        if (el)
			            ids += "," + el.getAttribute("el_id");
			    }

                var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_plugin_'.$id.'_set_active&pm_id='.$id.'&ids=" + ids);
                if (res != "TRUE")
                {
                    alert("'.PL_PLUGIN_MAKER_PLUGINS_SHOWHIDE_ERROR.'");
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

		$elems->addElemsMenuItem(PL_PLUGIN_MAKER_PLUGINS_SHOWHIDE_MENU, 'elemsSetActive();');
	}

	$elems->mElemsJavascriptStr .= '
		function sbPluginExport(c)
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

			var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_edit&ident=pl_plugin_'.$id.'&cat_id="+ cat_id;
			var strAttr = "resizable=1,width=700,height=600";
			sbShowModalDialog(strPage, strAttr, null, args);
		}

		function sbPluginImport(c)
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

			var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_import_edit&ident=pl_plugin_'.$id.'&cat_id="+ cat_id;
			var strAttr = "resizable=1,width=650,height=370";
			sbShowModalDialog(strPage, strAttr, sbAfterPluginImport, args);
		}

		function sbAfterPluginImport()
		{
			window.location.href = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$id.'_init&pm_id='.$id.'";
		}';

	$elems->addElemsMenuItem(PL_PLUGIN_MAKER_EDIT_EXPORT, 'sbPluginExport()', false);
	//$elems->addElemsMenuItem(PL_PLUGIN_MAKER_EDIT_IMPORT, 'sbPluginImport()', false);
	$elems->addCategsMenuItem(PL_PLUGIN_MAKER_EDIT_EXPORT, 'sbPluginExport(true)');
	//$elems->addCategsMenuItem(PL_PLUGIN_MAKER_EDIT_IMPORT, 'sbPluginImport(true)');

	if(isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1 && isset($_GET['sb_sel_id']) && $_GET['sb_sel_id'] != '' && isset($_GET['show_comments']))
	{
		$elems->mFooterStr = '
			<script>
				var timer = setInterval("showComments('.$_GET['sb_sel_id'].')", 300);
			</script>';
	}

	if(!$external)
		$elems->init();
}

function fPlugin_Maker_Plugins_Edit($htmlStr = '', $footerStr = '', $footerLinkStr = '')
{
	$edit_group = sbIsGroupEdit();

	if (!isset($_GET['pm_id']))
		return;

	$pm_id = intval($_GET['pm_id']);

	if ($edit_group)
    {
    	// проверка прав доступа
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_plugin_'.$pm_id))
			return;
    }
    else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id))
    {
   		// проверка прав доступа
		return;
    }

	$edit_rights = $_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'elems_edit');

    $res = sql_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);
    if (!$res)
        return;

    list($pm_elems_settings) = $res[0];

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '' && !$edit_group)
    {
		$p_demo_id = 0;
		$p_id = intval($_GET['id']);

		//если есть демонстрационная версия элемента, то работаем с ней.
		$result = sql_query('SELECT p_title, p_url, p_pub_start, p_pub_end, p_sort, p_active, p_user_id, p_price1, p_price2, p_price3, p_price4, p_price5, p_order, p_demo_id FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_id);
        if ($result)
		{
			list($p_title, $p_url, $p_pub_start, $p_pub_end, $p_sort, $p_active, $p_user_id, $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $p_order, $p_demo_id) = $result[0];
			if ($p_demo_id > 0)
			{
				$result = sql_query('SELECT p_title, p_url, p_pub_start, p_pub_end, p_sort, p_active, p_user_id, p_price1, p_price2, p_price3, p_price4, p_price5 FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_demo_id);
				list($p_title, $p_url, $p_pub_start, $p_pub_end, $p_sort, $p_active, $p_user_id, $p_price1, $p_price2, $p_price3, $p_price4, $p_price5) = $result[0];
			}
        }
        else
        {
   	        sb_show_message(PL_PLUGIN_MAKER_PLUGINS_EDIT_ERROR, true, 'warning');
            return;
        }

        if (!is_null($p_pub_start) && $p_pub_start != 0 && $p_pub_start != '')
            $p_pub_start = sb_date('d.m.Y H:i', $p_pub_start);
        else
            $p_pub_start = '';

        if (!is_null($p_pub_end) && $p_pub_end != 0 && $p_pub_end != '')
            $p_pub_end = sb_date('d.m.Y H:i', $p_pub_end);
        else
            $p_pub_end = '';
    }
    elseif (count($_POST) > 0)
    {
    	if (isset($_GET['id']))
    		$p_id = intval($_GET['id']);
    	else
    		$p_id = 0;

    	$p_order = '';
		$p_demo_id = $p_active = 0;
        $p_pub_start = '';
        $p_pub_end = '';
        $p_price1 = null;
        $p_price2 = null;
        $p_price3 = null;
        $p_price4 = null;
        $p_price5 = null;
        $p_user_id = null;

		extract($_POST);

        if (!isset($_GET['id']))
            $_GET['id'] = '';

        //если есть демонстрационная версия элемента, то работаем с ней.
		$result = sql_query('SELECT p_order, p_demo_id FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_id);
        if ($result)
		{
			list($p_order, $p_demo_id) = $result[0];
			if ($p_demo_id > 0)
			{
				$result = sql_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_demo_id);
				list($p_order) = $result[0];
			}
        }
    }
    else
    {
		$p_demo_id = $p_id = 0;
		$p_order = $p_title = $p_pub_start = $p_pub_end = $p_url = '';
		$p_price1 = $p_price2 = $p_price3 = $p_price4 = $p_price5 = null;
		$p_active = 1;
		$p_user_id = null;

        $res = sql_query('SELECT MAX(p_sort) FROM sb_plugins_'.$pm_id);
        if ($res)
        {
            list($p_sort) = $res[0];
            $p_sort += 10;
        }
        else
        {
            $p_sort = 0;
        }

		$_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("p_title");
                '.($edit_group ? '
            	var ch_t = sbGetE("ch_p_title");
            	' : '').'
                if (el_title.value == "" '.($edit_group ? ' && ch_t.checked' : '').')
                {
                     alert("'.PL_PLUGIN_MAKER_PLUGINS_EDIT_NO_TITLE_MSG.'");
                     return false;
                }
            }

            function changeActive(el)
            {
                var pub_s = sbGetE("p_pub_start");
                var pub_e = sbGetE("p_pub_end");

                pub_s.disabled = !el.checked;
                pub_e.disabled = !el.checked;

                pub_s.value = "";
                pub_e.value = "";
            }';

	if ($htmlStr != '')
	{
		echo '
			function cancel()
            {';
			if($edit_group || $p_demo_id > 0)
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
	elseif ($edit_group)
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main', 'enctype="multipart/form-data"');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_EDIT_TAB1);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_EDIT_TAB1);

    $layout->addField('', new sbLayoutInput('hidden', $p_demo_id, 'p_demo_id'));

	$layout->addField((isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_EDIT_TITLE).sbGetGroupEditCheckbox('p_title', $edit_group), new sbLayoutInput('text', $p_title, 'p_title', '', 'style="width:440px;"', true));
	if (isset($pm_elems_settings['show_price1']) && $pm_elems_settings['show_price1'] == 1 ||
		isset($pm_elems_settings['show_price2']) && $pm_elems_settings['show_price2'] == 1 ||
		isset($pm_elems_settings['show_price3']) && $pm_elems_settings['show_price3'] == 1 ||
		isset($pm_elems_settings['show_price4']) && $pm_elems_settings['show_price4'] == 1 ||
		isset($pm_elems_settings['show_price5']) && $pm_elems_settings['show_price5'] == 1)
	{
		$layout->addField('', new sbLayoutDelim());

    	// получаем курсы валют ЦБ
    	require_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';

    	if (isset($pm_elems_settings['show_price1']) && $pm_elems_settings['show_price1'] == 1)
    	{
    		if ((!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] == 0) && isset($pm_elems_settings['price1_formula']))
    		{
				//	рассчитываем цену
    			$p_price1 = fPlugin_Maker_Quoting($pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), $pm_elems_settings['price1_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
               	$fld = new sbLayoutInput('text', $p_price1, 'p_price1', 'spin_p_price1', 'style="width:150px;" disabled="disabled"');
    		}
    		else
    		{
    			$fld = new sbLayoutInput('text', $p_price1, 'p_price1', 'spin_p_price1', 'style="width:150px;"');
    		}

    		$fld->mIncrement = 0.01;
    		$layout->addField((isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '1')).sbGetGroupEditCheckbox('p_price1', $edit_group), $fld);
    	}

    	if (isset($pm_elems_settings['show_price2']) && $pm_elems_settings['show_price2'] == 1)
    	{
    		if ((!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] == 0) && isset($pm_elems_settings['price2_formula']))
    		{
    			// рассчитываем цену
    			$p_price2 = fPlugin_Maker_Quoting($pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), $pm_elems_settings['price2_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
               	$fld = new sbLayoutInput('text', $p_price2, 'p_price2', 'spin_p_price2', 'style="width:150px;" disabled="disabled"');
    		}
    		else
    		{
    			$fld = new sbLayoutInput('text', $p_price2, 'p_price2', 'spin_p_price2', 'style="width:150px;"');
    		}

    		$fld->mIncrement = 0.01;
    		$layout->addField((isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '2')).sbGetGroupEditCheckbox('p_price2', $edit_group), $fld);
    	}

    	if (isset($pm_elems_settings['show_price3']) && $pm_elems_settings['show_price3'] == 1)
    	{
    		if ((!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] == 0) && isset($pm_elems_settings['price3_formula']))
    		{
    			// рассчитываем цену
    			$p_price3 = fPlugin_Maker_Quoting($pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), $pm_elems_settings['price3_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    			$fld = new sbLayoutInput('text', $p_price3, 'p_price3', 'spin_p_price3', 'style="width:150px;" disabled="disabled"');
    		}
    		else
    		{
    			$fld = new sbLayoutInput('text', $p_price3, 'p_price3', 'spin_p_price3', 'style="width:150px;"');
    		}

    		$fld->mIncrement = 0.01;
    		$layout->addField((isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '3')).sbGetGroupEditCheckbox('p_price3', $edit_group), $fld);
    	}

    	if (isset($pm_elems_settings['show_price4']) && $pm_elems_settings['show_price4'] == 1)
    	{
    		if ((!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] == 0) && isset($pm_elems_settings['price4_formula']))
    		{
    			// рассчитываем цену
    			$p_price4 = fPlugin_Maker_Quoting($pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), $pm_elems_settings['price4_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    			$fld = new sbLayoutInput('text', $p_price4, 'p_price4', 'spin_p_price4', 'style="width:150px;" disabled="disabled"');
    		}
    		else
    		{
    			$fld = new sbLayoutInput('text', $p_price4, 'p_price4', 'spin_p_price4', 'style="width:150px;"');
    		}

    		$fld->mIncrement = 0.01;
    		$layout->addField((isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '4')).sbGetGroupEditCheckbox('p_price4', $edit_group), $fld);
    	}

    	if (isset($pm_elems_settings['show_price5']) && $pm_elems_settings['show_price5'] == 1)
    	{
    		if ((!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] == 0) && isset($pm_elems_settings['price5_formula']))
    		{
    			// рассчитываем цену
    			$p_price5 = fPlugin_Maker_Quoting($pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), $pm_elems_settings['price5_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    			$fld = new sbLayoutInput('text', $p_price5, 'p_price5', 'spin_p_price5', 'style="width:150px;" disabled="disabled"');
    		}
    		else
    		{
    			$fld = new sbLayoutInput('text', $p_price5, 'p_price5', 'spin_p_price5', 'style="width:150px;"');
    		}

    		$fld->mIncrement = 0.01;
    		$layout->addField((isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PRICE_COMMENT, '5')).sbGetGroupEditCheckbox('p_price5', $edit_group), $fld);
    	}
    }

    if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1 ||
        isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1 ||
        isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
    {
		$layout->addField('', new sbLayoutDelim());
	}

    if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1 && !$edit_group)
    {
    	$layout->addField(KERNEL_STATIC_URL, new sbLayoutInput('text', $p_url, 'p_url', '', 'style="width:440px;"'));
        $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.KERNEL_STATIC_URL_HINT.'</div>', '', '', false));
    }
    elseif(!$edit_group)
    {
    	$layout->addField('', new sbLayoutInput('hidden', $p_url, 'p_url'));
    }

    if (isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
    {
		fClouds_Get_Field($layout, 'p_tags', ($edit_group ? $_GET['ids'] : $p_id), 'pl_plugin_'.$pm_id, '440px', $edit_group);
	}
	else
	{
		$layout->addField('', new sbLayoutInput('hidden', '', 'p_tags'));
	}

	if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
	{
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_EDIT_SORT.sbGetGroupEditCheckbox('p_sort', $edit_group), new sbLayoutInput('text', $p_sort, 'p_sort', 'spin_p_sort', 'style="width:80px;"'));
	}
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', $p_sort, 'p_sort'));
    }

    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
    {
		if (!$edit_group)
		{
			$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_plugin_'.$pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), 'p_active', $p_active, 'p_pub_start', $p_pub_start, 'p_pub_end', $p_pub_end);
		}
		else
		{
			$states_arr = array();
			$states = sql_query('SELECT p_active FROM sb_plugins_'.$pm_id.' WHERE p_id IN (?a)', $_GET['ids']);

			if ($states)
			{
				foreach($states as $val)
				{
					$states_arr[] = $val[0];
				}
			}

			$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_plugin_'.$pm_id, $_GET['ids'], 'p_active', $states_arr, 'p_pub_start', $p_pub_start, 'p_pub_end', $p_pub_end);
		}
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', 1, 'p_active'));
    }

	$layout->getPluginFields('pl_plugin_'.$pm_id, ($p_demo_id > 0 ? $p_demo_id : $p_id), 'p_id', false, $edit_group);

	if (!is_null($p_user_id) && $p_user_id > 0 && !$edit_group)
	{
		$layout->addField('', new sbLayoutInput('hidden', $p_user_id, 'p_user_id'));
		fSite_Users_Get_Author_Tab($layout, $p_user_id);
	}

    if (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] == 1 && !$edit_group)
    {
	    fVoting_Rating_Edit($layout, $p_id, 'pl_plugin_'.$pm_id);
    }

	// Вкладка "Заказанные товары"
	$edit_button = false;
	if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1 && !$edit_group)
	{
		if($_GET['id'] != '')
        {
            $sb_modal_dialog = SB_CMS_MODAL_DIALOG_FILE;
            $sb_empty_file = SB_CMS_EMPTY_FILE;
            $last_tab = $layout->getTabCount();
            echo <<<EOF
                <script type="text/javascript">
                function afterEditList()
                {
                    var res = sbLoadSync("$sb_empty_file?event=pl_plugin_{$pm_id}_list_reload&pm_id=$pm_id&cat_id={$_GET['cat_id']}&elem_id={$_GET['id']}");
                    sbGetE('sb_tab$last_tab').innerHTML = res;
                }

                function sbEditList()
                {
                    var strPage = "$sb_modal_dialog?event=pl_plugin_{$pm_id}_edit_list&pm_id={$pm_id}&elem_id={$_GET['id']}&cat_id={$_GET['cat_id']}";
                    var strAttr = "resizable=1,width=950,height=500";
                    sbShowModalDialog(strPage, strAttr, afterEditList);
                }

                function showAddForm()
                {
                    var strPage = "$sb_modal_dialog?event=pl_plugin_{$pm_id}_add_goods&pm_id={$pm_id}&cat_id={$_GET['cat_id']}&elem_id={$_GET['id']}";
                    var strAttr = "resizable=1,width=700,height=350";
                    sbShowModalDialog(strPage, strAttr, afterEditList);
                }
                </script>
EOF;
        }

		$layout->addTab(PL_PLUGIN_MAKER_EDIT_TAB6);
		$res_html = fPlugin_Maker_Get_Orders_List($pm_elems_settings, $p_order);

		if(!$res_html)
		{
			$res_html = sb_show_message(PL_PLUGIN_MAKER_NO_ORDERS_MSG, true, 'information', true);
		}
		else
		{
			if(!isset($pm_elems_settings['basket_temp']))
			{
				$pm_elems_settings['basket_temp'] = '{ORDER_LIST}';
			}

			$str = sb_str_replace(array('{ORDER_LIST}', '{COUNT_POSITIONS}', '{COUNT_GOODS}', '{TOVAR_SUM}', '{TOVAR_SUM_DISCOUNT}'),
					array($res_html['order_list'], $res_html['count_pos'], $res_html['count_goods'], $res_html['tovar_sum'], $res_html['tovar_sum_discount']),
					$pm_elems_settings['basket_temp']);

			//чистим код от инъекций
			$str = sb_clean_string($str);

			ob_start();
			eval(' ?>'.$str.'<?php ');

			$res_html = ob_get_clean();

            if ($_GET['id'] != '' && !$edit_group)
            {
                $edit_button = true;
            }
		}

		$layout->addField('', new sbLayoutHTML($res_html, true));

        $res = sql_param_query('SELECT count(*) FROM sb_plugins_maker WHERE pm_elems_settings REGEXP "show_price[[:digit:]]{1}\";s:1:\"1\""');
        if ($_GET['id'] != '' && $res[0][0] > 0)
        {
            $layout->addButton('button', PL_PLUGIN_MAKER_ADD_GOOD_BUTTON, 'btn_add', '', 'onclick="showAddForm()"');
        }
        elseif($_GET['id'] == '')
        {
            $layout->addField('', new sbLayoutHTML('<div class="hint_div">'.PL_PLUGIN_MAKER_NO_ITEM_HELP_MSG.'</div>', true));
        }
	}


    if ($edit_button)
	{
	    $layout->addButton('button', PL_PLUGIN_MAKER_EDIT_BUTTON, 'btn_edit', '', ($edit_rights ? 'onclick="sbEditList()"' : 'disabled="disabled"'));
	}

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($edit_rights ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '' && !$edit_group)
	{
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($edit_rights ? '' : 'disabled="disabled"'));
	}

    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' || $edit_group ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

	$layout->show();
}

function fPlugin_Maker_Plugins_Edit_Submit()
{
	$edit_group = sbIsGroupEdit();

	if (!isset($_GET['pm_id']))
		return;

	$pm_id = intval($_GET['pm_id']);

	//	проверка прав доступа
	if($edit_group)
	{
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_plugin_'.$pm_id))
		{
			return;
		}
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id))
	{
		return;
	}

	$res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
       	return;

    list($pm_title) = $res[0];

	if (!$edit_group)
	{
	    if (!isset($_GET['id']))
    	    $_GET['id'] = '';
	}

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$ch_p_title = $ch_p_price1 = $ch_p_price2 = $ch_p_price3 = $ch_p_price4 = $ch_p_price5 = $ch_clouds = $ch_p_sort = $ch_p_active = 0;

	$p_active = 0;
	$p_demo_id = 0;

	extract($_POST);

    $layout = new sbLayout();
    $row = $layout->checkPluginFields('pl_plugin_'.$pm_id, ($edit_group ? $_GET['ids'] : ($p_demo_id > 0 ? $p_demo_id : $_GET['id'])), 'p_id', false, $edit_group);
    if ($row === false)
    {
		$layout->deletePluginFieldsFiles();
		fPlugin_Maker_Plugins_Edit();
		return;
	}

	if ((!$edit_group || $edit_group && $ch_p_title == 1) && $p_title == '')
	{
		sb_show_message(PL_PLUGIN_MAKER_PLUGINS_EDIT_NO_TITLE_MSG, false, 'warning');
		fPlugin_Maker_Plugins_Edit();
		return;
	}

    if (!$edit_group)
    {
    	$p_url = sb_check_chpu(($p_demo_id > 0 ? $p_demo_id : $_GET['id']), $p_url, $p_title, 'sb_plugins_'.$pm_id, 'p_url', 'p_id', ($p_demo_id > 0 ? 'p_demo_id != '.intval($p_demo_id) : ''));
    	$_POST['p_url'] = $p_url;
    }

    if (!$edit_group || $edit_group && $ch_p_active == 1)
    {
    	$p_active = intval($p_active);
		sb_submit_workflow_status($row, 'p_active', 'p_pub_start', 'p_pub_end', $edit_group);
    }
    else
    {
    	$p_active = null;
    }

	if (!$edit_group || $edit_group && $ch_p_title == 1)
    	$row['p_title'] = $p_title;

    if (!$edit_group || $edit_group && $ch_p_sort == 1)
        $row['p_sort'] = $p_sort;

    if (!$edit_group || $edit_group && $ch_p_price1 == 1)
    	$row['p_price1'] = isset($p_price1) && trim($p_price1) != '' ? floatval($p_price1) : null;

    if (!$edit_group || $edit_group && $ch_p_price2 == 1)
    	$row['p_price2'] = isset($p_price2) && trim($p_price2) != '' ? floatval($p_price2) : null;

    if (!$edit_group || $edit_group && $ch_p_price3 == 1)
    	$row['p_price3'] = isset($p_price3) && trim($p_price3) != '' ? floatval($p_price3) : null;

    if (!$edit_group || $edit_group && $ch_p_price4 == 1)
    	$row['p_price4'] = isset($p_price4) && trim($p_price4) != '' ? floatval($p_price4) : null;

	if (!$edit_group || $edit_group && $ch_p_price5 == 1)
		$row['p_price5'] = isset($p_price5) && trim($p_price5) != '' ? floatval($p_price5) : null;

	if (!$edit_group)
		$row['p_url'] = $p_url;

	if($edit_group || $_GET['id'] != '')
	{
		// редактирование элементов
		if(!$edit_group)
    	{
			$res = sql_param_query('SELECT p_title, p_user_id, p_active FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', ($p_demo_id > 0 ? $p_demo_id : $_GET['id']));
    	}
    	else
    	{
			$res = true;
    	}

        if ($res)
        {
            // редактирование
            if (!$edit_group)
            {
            	list($old_title, $p_user_id, $old_active) = $res[0];

            	if ($p_active <= 1 || ($old_active != 1 && $p_demo_id == 0))
            	{
            		$row['p_demo_id'] = 0;

            		// зашитый статус публикации, удаляем демо-элемент и устанавливаем поля для основного элемента
            		sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET ?a WHERE p_id=?d', $row, $_GET['id'], sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_OK, $old_title, $pm_title));
            		if($p_demo_id > 0)
            		{
						sql_query('DELETE FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_demo_id);
						$_POST['p_demo_id'] = $p_demo_id = 0;
					}
            	}
            	else
            	{
            		// пользовательский статус
            		if ($p_demo_id <= 0 && $old_active == 1)
            		{
            			// создаем демо-элемент
            			$_POST['p_demo_id'] = $p_demo_id = $GLOBALS['sbSql']->duplicateRow($_GET['id'], 'p_id', 'sb_plugins_'.$pm_id);
            			sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_demo_id=?d {, p_url=?} WHERE p_id=?d', $p_demo_id, isset($row['p_url']) ? $row['p_url'] : SB_SQL_SKIP, $_GET['id']);
            		}
            		elseif (isset($row['p_url']))
            		{
            			sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_url=? WHERE p_id=?d', $row['p_url'], $_GET['id']);
            		}

            		sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET ?a WHERE p_id=?d', $row, $p_demo_id, sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_OK, $old_title, $pm_title));
            	}
			}
            else
            {
            	if (count($row) > 0)
            	{
	            	// групповое редактирование
					$res = sql_param_query('SELECT p_id, p_demo_id, p_active, p_title FROM sb_plugins_'.$pm_id.' WHERE p_id IN (?a)', $_GET['ids']);
					if ($res)
					{
						foreach ($res as $value)
						{
							list($p_id, $p_demo_id, $old_active, $old_title) = $value;

							if (is_null($p_active))
							{
								// статус публикации остался прежним
								sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET ?a WHERE p_id=?d', $row, ($p_demo_id > 0 ? $p_demo_id : $p_id), sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_OK, $old_title, $pm_title));
							}
			            	elseif ($p_active <= 1 || ($old_active != 1 && $p_demo_id == 0))
			            	{
			            		$row['p_demo_id'] = 0;
			            		$real_row = $row;

			            		// зашитый статус публикации, удаляем демо-элемент и устанавливаем поля для основного элемента
			            		if($p_demo_id > 0)
			            		{
			            			$demo_res = sql_assoc('SELECT * FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_demo_id);
			            			foreach ($demo_res[0] as $f => $v)
			            			{
			            				if (!isset($row[$f]) && $f != 'p_id' && $f != 'p_demo_id' && $f != 'p_order')
			            				{
			            					$real_row[$f] = $v;
			            				}
			            			}

									sql_query('DELETE FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_demo_id);
								}

								sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET ?a WHERE p_id=?d', $real_row, $p_id, sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_OK, $old_title, $pm_title));
			            	}
			            	else
			            	{
			            		// пользовательский статус
			            		if ($p_demo_id <= 0 && $old_active == 1)
			            		{
			            			// создаем демо-элемент
			            			$p_demo_id = $GLOBALS['sbSql']->duplicateRow($p_id, 'p_id', 'sb_plugins_'.$pm_id);
			            			sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_demo_id=?d WHERE p_id=?d', $p_demo_id, $p_id);
			            		}

			            		sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET ?a WHERE p_id=?d', $row, $p_demo_id, sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_OK, $old_title, $pm_title));
			            	}
						}
					}
				}
			}

            if (!$edit_group)
            {
	            $footer_ar = fCategs_Edit_Elem();

	            if (!$footer_ar)
	            {
	                sb_show_message(PL_PLUGIN_MAKER_PLUGINS_EDIT_ERROR, false, 'warning');
	                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_SYSTEMLOG_ERROR, $old_title, $pm_title), SB_MSG_WARNING);

	                $layout->deletePluginFieldsFiles();
	                fPlugin_Maker_Plugins_Edit();
	                return;
	            }
	            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
	            $footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

	            $row['p_id'] = intval($_GET['id']);

	            if (isset($v_sum))
	            {
	    			fVoting_Rating_Edit_Submit($_GET['id'], 'pl_plugin_'.$pm_id);
	            }
			}

			if (!$edit_group || $edit_group && $ch_clouds == 1)
			{
    			fClouds_Set_Field(($edit_group ? $cloud_ids : $_GET['id']), 'pl_plugin_'.$pm_id, $p_tags, $edit_group);
			}

    		if (!$edit_group)
    		{
				$row['p_user_id'] = $p_user_id;
				$row['p_demo_id'] = $p_demo_id;

	            $html_str = fPlugin_Maker_Plugins_Get($row);
	            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
	            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);
    		}

            if (!isset($_POST['btn_apply']) && !$edit_group)
            {
				if($p_demo_id > 0)
            	{
	    			echo '<script>
						sbReturnValue("refresh");
					</script>';
            	}
            	else
            	{
                	echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "'.$footer_link_str.'";
                        sbReturnValue(res);
                      </script>';
            	}
            }
            elseif (!$edit_group)
            {
                fPlugin_Maker_Plugins_Edit($html_str, $footer_str, $footer_link_str);
            }
        	else
    		{
    			echo '<script>
						sbReturnValue("refresh");
					</script>';
			}

			sb_mail_workflow_status('pl_plugin_'.$pm_id, ($edit_group ? $_GET['ids'] : $_GET['id']), $p_title, $p_active);
		}
		else
		{
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_EDIT_SYSTEMLOG_ERROR, $p_title, $pm_title), SB_MSG_WARNING);

            $layout->deletePluginFieldsFiles();
            fPlugin_Maker_Plugins_Edit();
            return;
        }
    }
    else
    {
    	// добавление элемента
		$row['p_user_id'] = null;
		$row['p_demo_id'] = 0;

		$error = true;
        if (sql_param_query('INSERT INTO sb_plugins_'.$pm_id.' SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id))
            {
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_ADD_OK, $p_title, $pm_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

				sb_mail_workflow_status('pl_plugin_'.$pm_id, $id, $p_title, $p_active);

				$error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_plugins_'.$pm_id.' WHERE p_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_ADD_ERROR, $p_title, $pm_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_ADD_SYSTEMLOG_ERROR, $p_title, $pm_title), SB_MSG_WARNING);

            $layout->deletePluginFieldsFiles();
            fPlugin_Maker_Plugins_Edit();
            return;
        }
        else
        {
        	if (isset($v_sum))
        	{
    			fVoting_Rating_Edit_Submit($id, 'pl_plugin_'.$pm_id);
        	}

    		if (isset($p_tags))
    		{
    			fClouds_Set_Field($id, 'pl_plugin_'.$pm_id, $p_tags);
    		}
        }
    }
}

function fPlugin_Maker_Plugins_Set_Active()
{
	if (!isset($_GET['pm_id']) || !isset($_GET['ids']) || $_GET['ids'] == '')
        return;

	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$_GET['pm_id'], 'elems_public'))
		return;

    $pm_id = intval($_GET['pm_id']);
    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);
    if (!$res)
        return;

    list($pm_title) = $res[0];

    sbIsGroupEdit(false);

	$date = time();
	foreach ($_GET['ids'] as $val)
    {
       	sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $val, 'pl_plugin_'.$pm_id, $date, $_SESSION['sbAuth']->getUserId(), 'edit');
    }

    $res = sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_active=IF(p_active=0,1,0) WHERE p_id IN (?a)', $_GET['ids'], sprintf(PL_PLUGIN_MAKER_PLUGINS_SET_ACTIVE, $pm_title));
    if ($res)
    	echo 'TRUE';
}

function fPlugin_Maker_Plugins_Delete()
{
	if (!isset($_GET['pm_id']) || !isset($_GET['id']))
		return;

	$pm_id = intval($_GET['pm_id']);

	$res = sql_query('SELECT p_demo_id FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $_GET['id']);
	if ($res && $res[0][0] > 0)
	{
		sql_query('DELETE FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $res[0][0]);
	}

	fVoting_Delete($_GET['id'], 'pl_plugin_'.$pm_id);
	fComments_Delete_Comment($_GET['id'], 'pl_plugin_'.$pm_id);
	fClouds_Delete($_GET['id'], 'pl_plugin_'.$pm_id);
}

function fPlugin_Maker_Plugins_Before_Delete_With_Elements()
{
	if (!isset($_GET['pm_id']) || !isset($_GET['cat_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
	if(!$res)
		return;

	list($left, $right) = $res[0];

   	$res = sql_query('SELECT p.p_id FROM sb_categs categs, sb_catlinks links, sb_plugins_'.$pm_id.' p
                          WHERE categs.cat_left >= ?d
                          AND categs.cat_right <= ?d
                          AND categs.cat_ident="pl_plugin_'.$pm_id.'"
                          AND links.link_cat_id=categs.cat_id
                          AND p.p_id = links.link_el_id', $left, $right);
    if(!$res)
    	return;

    $elems = array();
    foreach ($res as $value)
    {
    	$elems[] = $value[0];
    }

	$res = sql_query('SELECT p_demo_id FROM sb_plugins_'.$pm_id.' WHERE p_id IN (?a) AND p_demo_id > 0', $elems);
	if ($res)
	{
		foreach ($res as $value)
		{
			sql_query('DELETE FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $value[0]);
		}
	}

    fVoting_Delete($elems, 'pl_plugin_'.$pm_id);
    fComments_Delete_Comment($elems, 'pl_plugin_'.$pm_id);
    fClouds_Delete($elems, 'pl_plugin_'.$pm_id);
}

function fPlugin_Maker_Plugins_Paste()
{
	if (!isset($_GET['pm_id']) || !isset($_GET['action']) || $_GET['action'] != 'copy' || !isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['ne']) || !is_array($_GET['ne']) || count($_GET['ne']) <= 0)
        return;

    $pm_id = intval($_GET['pm_id']);
    $workflow = $_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow('pl_plugin_'.$pm_id);
    $res = sql_query('SELECT p_id, p_title, p_active, p_demo_id FROM sb_plugins_'.$pm_id.' WHERE p_id IN ('.implode(',', $_GET['ne']).')');

	$els = array();
    foreach ($_GET['e'] as $key => $value)
    {
        $els[intval($value)] = intval($_GET['ne'][$key]);
    }

    foreach ($res as $value)
    {
        list($p_id, $p_title, $p_active, $p_demo_id) = $value;

        if ($p_demo_id > 0)
        {
        	$new_el_id = $GLOBALS['sbSql']->duplicateRow($p_demo_id, 'p_id', 'sb_plugins_'.$pm_id);
        	$res = sql_param_query('SELECT p_title, p_active FROM ?# WHERE p_id=?d', 'sb_plugins_'.$pm_id, $p_demo_id);
			if ($res)
			{
				list($p_title, $p_active) = $res[0];
			}

			$p_url = sb_check_chpu($new_el_id, '', $p_title, 'sb_plugins_'.$pm_id, 'p_url', 'p_id');

			sql_query('DELETE FROM ?# WHERE p_id=?d', 'sb_plugins_'.$pm_id, $p_id);
        }
        else
        {
        	$p_url = sb_check_chpu($p_id, '', $p_title, 'sb_plugins_'.$pm_id, 'p_url', 'p_id');
        }

        if ($workflow)
        {
	       	if (!sb_workflow_status_available($p_active, 'pl_plugin_'.$pm_id, -1))
			{
				$p_active = current(sb_get_avail_workflow_status('pl_plugin_'.$pm_id));
			}
        }

        if ($p_demo_id > 0)
        {
        	sql_param_query('UPDATE ?# SET p_url=?, p_active=?d, p_demo_id=0, p_id=?d WHERE p_id=?d', 'sb_plugins_'.$pm_id, $p_url, $p_active, $p_id, $new_el_id);
        }
        else
        {
        	sql_param_query('UPDATE ?# SET p_url=?, p_active=?d WHERE p_id=?d', 'sb_plugins_'.$pm_id, $p_url, $p_active, $p_id);
        }
    }

    fClouds_Copy($els, 'pl_plugin_'.$pm_id);
}

/**
 * Ф-ция вызывается после вставки скопированных разделов с элементами
 *
 * Массив $_GET:
 *
 * cat_id - ID раздела, в который производится вставка скопированного раздела
 * paste_cat_id - ID копируемого раздела
 * new_cat_id - ID скопированного раздела
 * before_cat_id - ID раздела, находящегося в иерархии дерева непосредственно над разделом, в который производится вставка
 */
function fPlugin_Maker_After_Paste_Categs_With_Elements()
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

	fPlugin_Maker_Plugins_Paste();
}

/**
 * Функция выводит список товаров для заказа.
 *
 * @param array() $pm_settings настройки модуля
 * @param string $root_xml данные о заказанных товарах в формате xml
 * @return Распарсенный макет или false в случае неудачи.
 */
function fPlugin_Maker_Get_Orders_List($pm_settings, $root_xml)
{
	if(!is_array($pm_settings) || empty($pm_settings) || $root_xml == '')
	{
		return false;
	}

	$root_xml = simplexml_load_string($root_xml);
	if(!$root_xml)
	{
		return false;
	}

	$xml = $root_xml->xpath('good/@pm_id');

	$plugin_ids = array();//уникальные id-шники модулей, товары которых есть в заказе.
	foreach($xml as $value)
	{
		if(!in_array((string) $value, $plugin_ids))
		{
			$plugin_ids[] = (string) $value;
		}
	}

	if (count($plugin_ids) <= 0)
	{
// 	    в заказе нет товаров
	    return false;
	}

//	Массив идентификаторов макетов дизайнов
	$temps_ids = array();
	foreach($pm_settings as $key => $value)
	{
//		вносим в массив только те идентификаторы макетов, товары модулей которых присутствуют в заказе.
		if(strpos($key, 'basket_temp_id') !== false && intval($value) > 0 && in_array(intval(sb_str_replace('basket_temp_id', '', $key)), $plugin_ids))
		{
			$temps_ids[] = $value;
		}
	}

	if(count($temps_ids) == 0)
	{
		sb_add_system_message(PL_PLUGIN_MAKER_PLUGINS_ORDER_LIST_TEMP_ERROR, SB_MSG_WARNING);
		return false;
	}

	// вытаскиваем макет дизайна для тех модулей у которых есть товары в заказе
	$res = sql_param_query('SELECT ptl_id, ptl_lang, ptl_count, ptl_top, ptl_categ_top, ptl_element, ptl_empty, ptl_delim, ptl_categ_bottom,
			ptl_bottom, ptl_fields_temps, ptl_categs_temps
			FROM sb_plugins_temps_list WHERE ptl_id IN (?a)', $temps_ids);

	if(!$res)
    {
		sb_add_system_message(PL_PLUGIN_MAKER_PLUGINS_ORDER_LIST_TEMP_ERROR, SB_MSG_WARNING);
		return false;
	}

	//	$temps массив макетов дизайна
	$temps = array();
	foreach ($res as $key => $value)
	{
		list($ptl_id, $ptl_lang, $ptl_count, $ptl_top, $ptl_categ_top, $ptl_element, $ptl_empty, $ptl_delim, $ptl_categ_bottom, $ptl_bottom,
				$ptl_fields_temps, $ptl_categs_temps) = $value;

		$temps[$ptl_id]['ptl_lang'] = $ptl_lang;
		$temps[$ptl_id]['ptl_count'] = $ptl_count;
		$temps[$ptl_id]['ptl_top'] = $ptl_top;
		$temps[$ptl_id]['ptl_categ_top'] = $ptl_categ_top;
		$temps[$ptl_id]['ptl_element'] = $ptl_element;
		$temps[$ptl_id]['ptl_empty'] = $ptl_empty;
		$temps[$ptl_id]['ptl_delim'] = $ptl_delim;
		$temps[$ptl_id]['ptl_categ_bottom'] = $ptl_categ_bottom;
		$temps[$ptl_id]['ptl_bottom'] = $ptl_bottom;
		$temps[$ptl_id]['ptl_fields_temps'] = unserialize($ptl_fields_temps);
		$temps[$ptl_id]['ptl_categs_temps'] = unserialize($ptl_categs_temps);
	}

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$general_res = array();
	$general_res['order_list'] = '';
	$general_res['count_pos'] = '';
	$general_res['count_goods'] = '';
	$general_res['tovar_sum'] = '';
	$general_res['tovar_sum_discount'] = '';

	$sum_order_overall = $count_overall = $count_pos = 0;
	$dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');

	foreach($plugin_ids as $pl_value)
	{
		// все товары данного модуля
		$xml = $root_xml->xpath('good[@pm_id="'.$pl_value.'"]');

		$values = $cat_values = array();
		$cur_cat_id = $y = $col = 0;
		$temp_id = -1;
		$result = '';
		$all_count = count($xml);

//		$temp_id - Идентификатор макета дизайна который выбран для элементов данного модуля
		if(isset($pm_settings['basket_temp_id'.$pl_value]))
		{
			$temp_id = $pm_settings['basket_temp_id'.$pl_value];
		}

		if(!isset($pm_settings['basket_temp_id'.$pl_value]) || intval($temp_id) <= 0)
		{
			sb_add_system_message(PL_PLUGIN_MAKER_PLUGINS_ORDER_LIST_TEMP_ERROR, SB_MSG_WARNING);
			continue;
		}

		foreach($xml as $key => $value)
		{
			$old_values = $values;
			$values = $el_fields = array();
			$y++;
			$count_pos++;

			$el = $value->children();

			$pm_id = $pl_value;
			$cat_id = (string) $value['cat_id'];
			$id = (string) $value['id'];
			$el_url = (string) $el->p_url;

			if($result == '')
			{
				$result = str_replace(array('{NUM_LIST}', '{ALL_COUNT}', '{COMPARE_ACTION}', '{BASKET_ACTION}'), array('', $all_count, '', ''), $temps[$temp_id]['ptl_top']);
			}

			$dop_cat_url = $root_xml->xpath('cat[cat_id="'.$cat_id.'"]/cat_url');
			$dop_cat_title = $root_xml->xpath('cat[cat_id="'.$cat_id.'"]/cat_title');

			$dop_values = array($id, $el_url, (string) $el->p_title, $cat_id, (string) $dop_cat_url[0], (string) $dop_cat_title[0], '');

			$values[] = $y; // 	ELEM_NUMBER
			$values[] = $id; // ID
			$values[] = $el_url;  //	ELEM_URL
			$values[] = (string) $el->p_title;
			$values[] = '';  //	LINK
			$values[] = '';  //	MORE
			$values[] = '';  //	USER_DATA
			$values[] = '';  //	ELEM_USER_LINK
			$values[] = '';  //	SORT
			$values[] = (string) $el->p_price1 != '' && isset($temps[$temp_id]['ptl_fields_temps']['p_price1']) && trim($temps[$temp_id]['ptl_fields_temps']['p_price1']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array((string) $el->p_price1)), $temps[$temp_id]['ptl_fields_temps']['p_price1']) : '';	//	PRICE_1
			$values[] = (string) $el->p_price2 != '' && isset($temps[$temp_id]['ptl_fields_temps']['p_price2']) && trim($temps[$temp_id]['ptl_fields_temps']['p_price2']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array((string) $el->p_price2)), $temps[$temp_id]['ptl_fields_temps']['p_price2']) : '';	//	PRICE_2
			$values[] = (string) $el->p_price3 != '' && isset($temps[$temp_id]['ptl_fields_temps']['p_price3']) && trim($temps[$temp_id]['ptl_fields_temps']['p_price3']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array((string) $el->p_price3)), $temps[$temp_id]['ptl_fields_temps']['p_price3']) : '';	//	PRICE_3
			$values[] = (string) $el->p_price4 != '' && isset($temps[$temp_id]['ptl_fields_temps']['p_price4']) && trim($temps[$temp_id]['ptl_fields_temps']['p_price4']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array((string) $el->p_price4)), $temps[$temp_id]['ptl_fields_temps']['p_price4']) : '';	//	PRICE_4
			$values[] = (string) $el->p_price5 != '' && isset($temps[$temp_id]['ptl_fields_temps']['p_price5']) && trim($temps[$temp_id]['ptl_fields_temps']['p_price5']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array((string) $el->p_price5)), $temps[$temp_id]['ptl_fields_temps']['p_price5']) : '';	//	PRICE_5

			$sum_order = (string) $el->p_count * (isset($el->{'p_price'.$pm_settings['cena']}) ? (string) $el->{'p_price'.$pm_settings['cena']} : 0);
			$values[] = $sum_order > 0 && isset($temps[$temp_id]['ptl_fields_temps']['p_sum_order']) && trim($temps[$temp_id]['ptl_fields_temps']['p_sum_order']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($sum_order)), $temps[$temp_id]['ptl_fields_temps']['p_sum_order']) : '';	//	PRICE_5
			$values[] = intval((string) $el->p_count) > 0 && isset($temps[$temp_id]['ptl_fields_temps']['p_goods_count']) && trim($temps[$temp_id]['ptl_fields_temps']['p_goods_count']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array((string) $el->p_count)), $temps[$temp_id]['ptl_fields_temps']['p_goods_count']) : '';	 //	GOODS_COUNT
			$values[] = ''; //	ORDER
			$values[] = ''; //	DEL_ALL_ORDERS
			$values[] = ''; //	COMPARE
			$values[] = ''; //	DEL_COMPARE
			$values[] = ''; //	RESERVING
			$values[] = ''; //	VOTES_SUM
			$values[] = ''; //	VOTES_COUNT
			$values[] = ''; //	RATING
			$values[] = ''; //	VOTES_FORM
			$values[] = ''; //	COUNT_COMMENTS
			$values[] = ''; //	FORM_COMMENTS
			$values[] = ''; //	LIST_COMMENTS

			$count_overall += (string) $el->p_count;
			$sum_order_overall += $sum_order;

			$tags = array('{ELEM_NUMBER}',
						'{ID}',
						'{ELEM_URL}',
						'{TITLE}',
						'{LINK}',
						'{MORE}',
						'{USER_DATA}',
						'{ELEM_USER_LINK}',
	    				'{SORT}',
	    				'{PRICE_1}',
	    				'{PRICE_2}',
	    				'{PRICE_3}',
	    				'{PRICE_4}',
						'{PRICE_5}',
						'{SUM_ORDER}',
						'{GOODS_COUNT}',
						'{ORDER}',
	                    '{DEL_ALL_ORDERS}',
						'{COMPARE}',
	    				'{DEL_COMPARE}',
						'{RESERVING}',
	                    '{VOTES_SUM}',
	                    '{VOTES_COUNT}',
	                    '{RATING}',
	                    '{VOTES_FORM}',
	                    '{COUNT_COMMENTS}',
	                    '{FORM_COMMENTS}',
						'{LIST_COMMENTS}');

			$i = 0;
			$el_values = array();
			foreach($el as $ke => $val)
			{
				if(strpos($ke, 'user_f_') !== false)
				{
					$el_values[] = isset($val[0]) ? (string) $val[0] : '';
                    $id_f = intval(preg_replace('/^user_f_(\d+)(_\d+_[a-z0-9]+)?$/', '$1', $ke));

					foreach($val->attributes() as $k => $v)
					{
						if($k == 'checked_text' || $k == 'not_checked_text' || $k == 'ident' || $k == 'cat_id' || $k == 'sprav_ids' ||
							$k == 'subcategs' || $k == 'sprav_title_fld' || $k == 'separator' || $k == 'count' ||
							$k == 'sprav_title' || $k == 'sprav_id' || $k == 'modules_cat_id' || $k == 'modules_title_fld' || $k == 'modules_link_title')
						{
							$el_fields[$i]['settings'][$k] = (string) $v;
						}
						else
						{
							$el_fields[$i][$k] = (string) $v;
						}
					}

                    if ($val['type'] == 'google_coords' || $val['type'] == 'yandex_coords')
	                {
						$tags[] = '{'.$val['tag'].'_LATITUDE}';
						$tags[] = '{'.$val['tag'].'_LONGTITUDE}';

	                	if ($val['type'] == 'yandex_coords')
	                	{
							$tags[] = '{'.$val['tag'].'_API_KEY}';
	                	}
	                }
	                else
	                {
						$tags[] = '{'.$val['tag'].'}';
	                }

					$el_fields[$i]['sql'] = 1;
					$el_fields[$i]['id'] = $id_f;
					$i++;
				}
			}

			$values = array_merge($values, sbLayout::parsePluginFields($el_fields, $el_values, $temps[$temp_id]['ptl_fields_temps'], $dop_tags, $dop_values, $temps[$temp_id]['ptl_lang'], '', '' , 0, 0, $temps[$temp_id]['ptl_element'], array('from_list_order' => '1')));

//			Если для этого раздела у нас уже есть сформированные данные то искользуем их, с xml не работаем.
			if(array_key_exists($cat_id, $cat_values))
			{
				$values = array_merge($values, $cat_values[$cat_id]);

				$tags = array_merge($tags, array('{CAT_TITLE}',
								'{CAT_LEVEL}',
		                        '{CAT_COUNT}',
		                        '{CAT_ID}',
		                        '{CAT_URL}'));
				$tags = array_merge($tags, isset($cat_tags[$cat_id]) ? $cat_tags[$cat_id] : array());
			}
			else
			{
				$cat_xml = $root_xml->xpath('cat[cat_id="'.$cat_id.'"]');
				$i = 0;
				$cat_fields = $cat_vals = array();

				$cat_values[$cat_id][] = (string) $cat_xml[0]->cat_title; // 	CAT_TITLE
				$cat_values[$cat_id][] = (string) $cat_xml[0]->cat_level; // CAT_LEVEL
				$cat_values[$cat_id][] = count($root_xml->xpath('good[@cat_id="'.$cat_id.'"]'));  //	CAT_COUNT
				$cat_values[$cat_id][] = (string) $cat_xml[0]->cat_id;  //	CAT_ID
				$cat_values[$cat_id][] = (string) $cat_xml[0]->cat_url;  //	CAT_URL

				foreach($cat_xml[0] as $ke => $val)
				{
					if(strpos($ke, 'user_f_') !== false)
					{
						$cat_vals[$cat_id][] = isset($val[0]) ? (string) $val[0] : '';
						//$id_f = intval(sb_str_replace('user_f_', '', $ke));
                        $id_f = intval(preg_replace('/^user_f_(\d+)(_\d+_[a-z0-9]+)?$/', '$1', $ke));

						foreach($cat_xml[0]->$ke->attributes() as $k => $v)
						{
							if($k == 'checked_text' || $k == 'not_checked_text' || $k == 'ident' || $k == 'cat_id' || $k == 'sprav_ids' ||
								$k == 'subcategs' || $k == 'sprav_title_fld' || $k == 'sprav_ids' || $k == 'separator' || $k == 'count' ||
								$k == 'sprav_title' || $k == 'sprav_id')
							{
								$cat_fields[$i]['settings'][$k] = (string) $v;
							}
							else
							{
								$cat_fields[$i][$k] = (string) $v;
							}
						}

		                if ($val['type'] == 'google_coords' || $val['type'] == 'yandex_coords')
			            {
							$cat_tags[$cat_id][] = '{'.$val['tag'].'_LATITUDE}';
							$cat_tags[$cat_id][] = '{'.$val['tag'].'_LONGTITUDE}';

			                if ($val['type'] == 'yandex_coords')
			                {
								$cat_tags[$cat_id][] = '{'.$val['tag'].'_API_KEY}';
							}
						}
			            else
			            {
							$cat_tags[$cat_id][] = '{'.$val['tag'].'}';
						}

						$cat_fields[$i]['sql'] = 1;
						$cat_fields[$i]['id'] = $id_f;
						$i++;
					}
				}
				$cat_user_fields = sbLayout::parsePluginFields($cat_fields, (isset($cat_vals[$cat_id]) ? $cat_vals[$cat_id] : array()), $temps[$temp_id]['ptl_categs_temps'], $dop_tags, $dop_values, $temps[$temp_id]['ptl_lang']);

				foreach($cat_user_fields as $v)
				{
					$cat_values[$cat_id][] = $v;
				}

				$tags = array_merge($tags, array('{CAT_TITLE}',
								'{CAT_LEVEL}',
		                        '{CAT_COUNT}',
		                        '{CAT_ID}',
		                        '{CAT_URL}'));

				$values = array_merge($values, $cat_values[$cat_id]);
				$tags = array_merge($tags, (isset($cat_tags[$cat_id]) ? $cat_tags[$cat_id] : array()));
			}

			if ($temps[$temp_id]['ptl_categ_top'] != '' || $temps[$temp_id]['ptl_categ_bottom'] != '')
			{
				$categs_output = true;
			}
			else
			{
				$categs_output = false;
			}

			if ($categs_output && $cat_id != $cur_cat_id)
			{
		    	if ($cur_cat_id != 0)
		        {
		        	// низ вывода раздела
		            while ($col < $temps[$temp_id]['ptl_count'])
		            {
		            	$result .= $temps[$temp_id]['ptl_empty'];
		                $col++;
					}

		            $result .= str_replace($tags, $old_values, $temps[$temp_id]['ptl_categ_bottom']);
				}

				// верх вывода раздела
		        $result .= str_replace($tags, $values, $temps[$temp_id]['ptl_categ_top']);
				$col = 0;
			}

			if ($col >= $temps[$temp_id]['ptl_count'])
		    {
				$result .= $temps[$temp_id]['ptl_delim'];
		        $col = 0;
			}

			$result .= str_replace($tags, $values, $temps[$temp_id]['ptl_element']);

			$cur_cat_id = $cat_id;
			$col++;
		}

	    while ($col < $temps[$temp_id]['ptl_count'])
	    {
			$result .= $temps[$temp_id]['ptl_empty'];
			$col++;
	    }

	    if ($categs_output)
	    {
			// низ вывода раздела
			$result .= str_replace($tags, $values, $temps[$temp_id]['ptl_categ_bottom']);
	    }

		// низ вывода списка
		$result .= str_replace(array('{NUM_LIST}', '{ALL_COUNT}'), array('', $all_count), $temps[$temp_id]['ptl_bottom']);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		$general_res['order_list'] .= $result;
	}

	$general_res['count_pos'] = $count_pos;
	$general_res['count_goods'] = $count_overall;
	$general_res['tovar_sum'] = $sum_order_overall;
	$general_res['tovar_sum_discount'] = '0';

	return $general_res;
}


/**
 * Функции управления макетами дизайна вывода списка элементов
 */
function fPlugin_Maker_Plugins_Design_List_Get($args)
{
	$pm_id = intval($_GET['pm_id']);

    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ptl_title'].'</a></b>
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

    $id = intval($args['ptl_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_list" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_list" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fPlugin_Maker_Plugins_Design_List()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_plugins_temps_list', 'ptl_id', 'ptl_title', 'fPlugin_Maker_Plugins_Design_List_Get', 'pl_plugin_'.$pm_id.'_design_list&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_design_list');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_list_32.png';

    $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_BY_TITLE, 'ptl_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_design_list_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_design_list_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_design_list_delete&pm_id='.$pm_id;

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_list";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_list";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function elemsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem($pm_title, 'elemsList();', false);

    $elems->init();
}

function fPlugin_Maker_Plugins_Design_List_Edit($htmlStr = '', $footerStr = '')
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_design_list'))
		return;

    $res = sql_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_elems_settings) = $res[0];

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT ptl_title, ptl_lang, ptl_checked, ptl_count, ptl_top, ptl_categ_top, ptl_element, ptl_empty, ptl_delim, ptl_categ_bottom, ptl_bottom, ptl_pagelist_id, ptl_perpage, ptl_no_elems, ptl_fields_temps, ptl_categs_temps, ptl_votes_id, ptl_comments_id, ptl_user_data_id, ptl_tags_list_id
                                   FROM sb_plugins_temps_list WHERE ptl_id=?d', $_GET['id']);
        if ($result)
        {
            list($ptl_title, $ptl_lang, $ptl_checked, $ptl_count, $ptl_top, $ptl_categ_top, $ptl_element, $ptl_empty, $ptl_delim, $ptl_categ_bottom, $ptl_bottom, $ptl_pagelist_id, $ptl_perpage, $ptl_no_elems, $ptl_fields_temps, $ptl_categs_temps, $ptl_votes_id, $ptl_comments_id, $ptl_user_data_id, $ptl_tags_list_id) = $result[0];
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($ptl_fields_temps != '')
            $ptl_fields_temps = unserialize($ptl_fields_temps);
        else
            $ptl_fields_temps = array();

        if ($ptl_categs_temps != '')
            $ptl_categs_temps = unserialize($ptl_categs_temps);
        else
            $ptl_categs_temps = array();

        if ($ptl_checked != '')
            $ptl_checked = explode(' ', $ptl_checked);
        else
            $ptl_checked = array();

		if(!isset($ptl_fields_temps['f_registred_users']))
			$ptl_fields_temps['f_registred_users'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG_VAL;
		if(!isset($ptl_fields_temps['p_date']))
			$ptl_fields_temps['p_date'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG_VAL;
		if(!isset($ptl_fields_temps['p_edit_link']))
			$ptl_fields_temps['p_edit_link'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_DEFAULT;
	}
	elseif (count($_POST) > 0)
    {
        $ptl_checked = array();
        $ptl_pagelist_id = 0;
        $ptl_tags_list_id = 0;
        $ptl_votes_id = 0;
        $ptl_comments_id = 0;
        $ptl_user_data_id = 0;

        extract($_POST);

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $ptl_title = $ptl_categ_top = $ptl_top = $ptl_element = $ptl_bottom = $ptl_empty = $ptl_delim = $ptl_categ_bottom = '';

        $ptl_pagelist_id = 0;
        $ptl_votes_id = 0;
        $ptl_tags_list_id = 0;
        $ptl_comments_id = 0;
        $ptl_user_data_id = 0;
        $ptl_perpage = 10;
        $ptl_count = 1;
        $ptl_checked = array();
        $ptl_lang = SB_CMS_LANG;
        $ptl_no_elems = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_NO_ELEMS_TEMP;

        $ptl_fields_temps = array();
        $ptl_fields_temps['p_more'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_MORE_DEFAULT;
        $ptl_fields_temps['p_edit_link'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_DEFAULT;
		$ptl_fields_temps['f_registred_users'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG_VAL;
		$ptl_fields_temps['p_date'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG_VAL;
        // добавление полей для корзины
        $ptl_fields_temps['p_prod'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEFAULT;

        $ptl_categs_temps = array();

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("ptl_title");
                if (el_title.value == "")
                {
                     alert("'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG.'");
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

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_design_list_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ptl_title, 'ptl_title', '', 'style="width:530px;"', true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ptl_lang');
    $fld->mSelOptions = array($ptl_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes('pl_plugin_'.$pm_id, $ptl_checked, 'ptl_checked');

    if (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] == 1)
    {
    	fVoting_Design_Get($layout, $ptl_votes_id, 'ptl_votes_id');
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', '0', 'ptl_votes_id'));
    }


    if (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1)
    {
    	fComments_Design_Get($layout, $ptl_comments_id, 'ptl_comments_id');
    }
    else
    {
		$layout->addField('', new sbLayoutInput('hidden', '0', 'ptl_comments_id'));
	}

	fSite_Users_Design_Get($layout, $ptl_user_data_id, 'ptl_user_data_id');

	if(isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
	{
		fClouds_Design_Get($layout, $ptl_tags_list_id, 'ptl_tags_list_id', 'element');
	}
	else
	{
		$layout->addField('', new sbLayoutInput('hidden', '0', 'ptl_tags_list_id'));
	}

	fPager_Design_Get($layout, $ptl_pagelist_id, 'ptl_pagelist_id', $ptl_perpage, 'ptl_perpage');

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_NO_NEWS, new sbLayoutTextarea($ptl_no_elems, 'ptl_no_elems', '', 'style="width:100%;height:50px;"'));

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2);

    $tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
    $tags_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM_URL_TAG, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_TITLE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($ptl_fields_temps['p_date'], 'ptl_fields_temps[p_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CHANGE_DATE, $fld);

	// Ссылка "Подробнее..."
    $fld = new sbLayoutTextarea($ptl_fields_temps['p_more'], 'ptl_fields_temps[p_more]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{LINK}') ,$tags);
    $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_TAG), $tags_values);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_MORE, $fld);

    // Ссылка "Редактировать..."
    $fld = new sbLayoutTextarea($ptl_fields_temps['p_edit_link'], 'ptl_fields_temps[p_edit_link]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{LINK}'), $tags);
    $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_EDIT_TAG), $tags_values);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK, $fld);

	$fld = new sbLayoutTextarea($ptl_fields_temps['f_registred_users'], 'ptl_fields_temps[f_registred_users]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG_VAL), $tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG), $tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG, $fld);

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
    {
    	if (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    	{
	        // поля для интернет-магазина
	        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_DELIM.'</div>';
	        $layout->addField('', new sbLayoutHTML($html, true));
	        $layout->addField('', new sbLayoutDelim());

	        // Цена 1
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_price1']) ? $ptl_fields_temps['p_price1'] : '{VALUE}', 'ptl_fields_temps[p_price1]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
	        $layout->addField(isset($pm_elems_settings['price1_title']) ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1'), $fld);

			// Цена 2
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_price2']) ? $ptl_fields_temps['p_price2'] : '{VALUE}', 'ptl_fields_temps[p_price2]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price2_title']) ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2'), $fld);

	        // Цена 3
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_price3']) ? $ptl_fields_temps['p_price3'] : '{VALUE}', 'ptl_fields_temps[p_price3]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price3_title']) ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3'), $fld);

	        // Цена 4
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_price4']) ? $ptl_fields_temps['p_price4'] : '{VALUE}', 'ptl_fields_temps[p_price4]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price4_title']) ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4'), $fld);

	        // Цена 5
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_price5']) ? $ptl_fields_temps['p_price5'] : '{VALUE}', 'ptl_fields_temps[p_price5]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price5_title']) ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5'), $fld);

			// Сумма по товару
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_sum_order']) ? $ptl_fields_temps['p_sum_order'] : '{VALUE}', 'ptl_fields_temps[p_sum_order]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array('{VALUE}'), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SUM_ORDER, $fld);

			// Кол-во товара в корзине
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_goods_count']) ? $ptl_fields_temps['p_goods_count'] : '{VALUE}', 'ptl_fields_temps[p_goods_count]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array('{VALUE}'), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_GOODS_COUNT, $fld);

			// Ссылка "Добавить в корзину"
			$cart_user_tags = array();
			$cart_user_values = array();
			$cart_fields_options = array();
			fPlugin_Maker_Plugins_Design_Cart_Fields_Get($pm_id, $cart_user_tags, $cart_user_values, $cart_fields_options);
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_prod_add']) ? $ptl_fields_temps['p_prod_add'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEFAULT, 'ptl_fields_temps[p_prod_add]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ADD_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEFAULT, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_COUNT_ELEMS_FILED), $cart_user_tags);
			$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_TEXT, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_ADD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ADD_BASKET), $cart_user_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK, $fld);

	    	if(count($cart_fields_options) > 0)
			{
				foreach($cart_fields_options as $value)
				{
					if($value['type'] == 'multiselect_sprav' || $value['type'] == 'checkbox_sprav')
					{
						$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_cart_field_'.$value['id']]) ? $ptl_fields_temps['p_cart_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>', 'ptl_fields_temps[p_cart_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_ID, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_TITLE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP1_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP2_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP3_TAG);
						$fld->mTags = array('<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>','{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}');
					}
					elseif($value['type'] == 'checkbox_plugin' || $value['type'] == 'multiselect_plugin')
					{
						$elems_fields_tags = array();
						$elems_fields_vals = array();
						$elems_fields = getPluginTitleFields($value['ident']);
						foreach($elems_fields as $value1)
						{
							$elems_fields_tags[] = $value1['tag'];
							$elems_fields_vals[] = $value1['title'];
						}
						$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_cart_field_'.$value['id']]) ? $ptl_fields_temps['p_cart_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>', 'ptl_fields_temps[p_cart_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_ELEM_ID), $elems_fields_vals);
						$fld->mTags = array_merge(array('<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>','{ID}'), $elems_fields_tags);
					}
					$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS.$value['title'], $fld);
				}
				$layout->addField('', new sbLayoutDelim());
			}

			// Ссылка "Удалить из карзины"
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_prod_del']) ? $ptl_fields_temps['p_prod_del'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEL_DEFAULT, 'ptl_fields_temps[p_prod_del]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEL_DEFAULT);
			$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_TEXT_DEL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEL);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL, $fld);

			$layout->addField('', new sbLayoutDelim());

			//	Добавить в резерв
			$cart_user_tags = array();
			$cart_user_values = array();
			$cart_fields_options = array();
			fPlugin_Maker_Plugins_Design_Cart_Fields_Get($pm_id, $cart_user_tags, $cart_user_values, $cart_fields_options, true);
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_reserved_add']) ? $ptl_fields_temps['p_reserved_add'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_ADD, 'ptl_fields_temps[p_reserved_add]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_ADD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_FORM_ADD), $cart_user_tags);
			$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_ADD_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_CHECK_ADD),$cart_user_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_BTN_ADD_TAG, $fld);

	    	if(count($cart_fields_options) > 0)
			{
				foreach($cart_fields_options as $value)
				{
					if($value['type'] == 'multiselect_sprav' || $value['type'] == 'checkbox_sprav')
					{
						$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_reserve_field_'.$value['id']]) ? $ptl_fields_temps['p_reserve_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>', 'ptl_fields_temps[p_reserve_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_ID, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_TITLE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP1_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP2_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP3_TAG);
						$fld->mTags = array('<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>','{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}');
					}
					elseif($value['type'] == 'checkbox_plugin' || $value['type'] == 'multiselect_plugin')
					{
						$elems_fields_tags = array();
						$elems_fields_vals = array();
						$elems_fields = getPluginTitleFields($value['ident']);
						foreach($elems_fields as $value1)
						{
							$elems_fields_tags[] = $value1['tag'];
							$elems_fields_vals[] = $value1['title'];
						}
						$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_reserve_field_'.$value['id']]) ? $ptl_fields_temps['p_reserve_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTE}>'.$elems_fields_tags[0].'</option>', 'ptl_fields_temps[p_reserve_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_ELEM_ID), $elems_fields_vals);
						$fld->mTags = array_merge(array('<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>','{ID}'), $elems_fields_tags);
					}
					$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_RESERV_FIELDS.$value['title'], $fld);
				}
				$layout->addField('', new sbLayoutDelim());
			}

			//	Убрать из резерва
			$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_reserved_del']) ? $ptl_fields_temps['p_reserved_del'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_DEL, 'ptl_fields_temps[p_reserved_del]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_DEL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_FORM_DEL);
			$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_DEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_CHECK_DEL);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_BTN_DEL_TAG, $fld);
    	}

        //Макеты списка элементов
        if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
        {
            $layout->addField('', new sbLayoutHTML('<div class="hint_div" align="center" style="margin-top: 5px;">' . PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_GOODS_TEMP_DESCR . '</div>', true));

            $res = sql_param_query('SELECT pm.pm_id, pm.pm_title, categs.cat_title, temps.ptl_id, temps.ptl_title
				FROM sb_plugins_maker pm
				LEFT JOIN sb_categs categs ON categs.cat_ident = CONCAT("pl_plugin_", pm.pm_id, "_design_list")
				LEFT JOIN sb_catlinks links ON categs.cat_id=links.link_cat_id
				LEFT JOIN sb_plugins_temps_list temps ON temps.ptl_id=links.link_el_id
				WHERE pm.pm_id != ?d
				ORDER BY pm.pm_id, categs.cat_left, temps.ptl_title', $pm_id);

            if ($res)
            {
                $options = array(' --- ');
                $pm_title = $old_cat_title = '';
                $count = count($res);
                $temp_pm_id = $old_temp_pm_id = $i = 0;

                foreach ($res as $key => $value)
                {
                    $i++;
                    $old_pm_title = $pm_title;
                    $old_temp_pm_id = $temp_pm_id;

                    list($temp_pm_id, $pm_title, $cat_title, $ptl_id, $ptl_title) = $value;

                    if ($old_temp_pm_id == 0)
                    {
                        $old_temp_pm_id = $temp_pm_id;
                        $old_pm_title = $pm_title;
                        $old_temp_pm_id = $temp_pm_id;
                    }

                    if ($old_temp_pm_id != $temp_pm_id)
                    {
                        if (count($options) > 1)
                        {
                            $fld = new sbLayoutSelect($options, 'ptl_fields_temps[basket_temp_id' . $old_temp_pm_id . ']');
                            if (isset($ptl_fields_temps['basket_temp_id' . $old_temp_pm_id]))
                            {
                                $fld->mSelOptions = array($ptl_fields_temps['basket_temp_id' . $old_temp_pm_id]);
                            }

                            $fld1 = new sbLayoutSelect($options, 'ptl_fields_temps[mess_basket_temp_id' . $old_temp_pm_id . ']');
                            if (isset($ptl_fields_temps['mess_basket_temp_id' . $old_temp_pm_id]))
                            {
                                $fld1->mSelOptions = array($ptl_fields_temps['mess_basket_temp_id' . $old_temp_pm_id]);
                            }
                        }
                        else
                        {
                            $fld1 = $fld = new sbLayoutLabel('<div class="hint_div">' . sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $old_pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST) . '</div>', '', '', false);
                        }
                        $layout->addField($old_pm_title, $fld);

                        $temps_for_basket[$old_pm_title] = $fld1;

                        $old_temp_pm_id = $temp_pm_id;
                        $options = array(' --- ');
                        $old_cat_title = '';
                    }

                    if ($old_cat_title != $cat_title)
                    {
                        $options[uniqid()] = '-' . $cat_title;
                        $old_cat_title = $cat_title;
                    }

                    if ($ptl_id != '')
                        $options[$ptl_id] = $ptl_title;

                    if ($count == $i)
                    {
                        if (count($options) > 1)
                        {
                            $fld = new sbLayoutSelect($options, 'ptl_fields_temps[basket_temp_id' . $temp_pm_id . ']');
                            if (isset($ptl_fields_temps['basket_temp_id' . $temp_pm_id]))
                            {
                                $fld->mSelOptions = array($ptl_fields_temps['basket_temp_id' . $temp_pm_id]);
                            }

                            $fld1 = new sbLayoutSelect($options, 'ptl_fields_temps[mess_basket_temp_id' . $temp_pm_id . ']');
                            if (isset($ptl_fields_temps['mess_basket_temp_id' . $temp_pm_id]))
                            {
                                $fld1->mSelOptions = array($ptl_fields_temps['mess_basket_temp_id' . $temp_pm_id]);
                            }
                        }
                        else
                        {
                            $fld1 = $fld = new sbLayoutLabel('<div class="hint_div">' . sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST) . '</div>', '', '', false);
                        }
                        $layout->addField($pm_title, $fld);
                        $temps_for_basket[$pm_title] = $fld1;
                    }
                }
            }
        }
	}

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_GROUP.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));

	$layout->addField('', new sbLayoutDelim());

	//	поля для "Сравнения"
	$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_compare_add']) ? $ptl_fields_temps['p_compare_add'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_ADD, 'ptl_fields_temps[p_compare_add]', '', 'style="width:100%;height:80px;"');
	$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_ADD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_ADD);
	$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_ADD_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_CHECK_ADD);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_BTN_ADD_TAG, $fld);

	//	поля для "Сравнения"
	$fld = new sbLayoutTextarea(isset($ptl_fields_temps['p_compare_del']) ? $ptl_fields_temps['p_compare_del'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_DEL, 'ptl_fields_temps[p_compare_del]', '', 'style="width:100%;height:80px;"');
	$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_DEL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_DEL);
	$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_DEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_CHECK_DEL);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_BTN_DEL_TAG, $fld);

    $layout->addPluginFieldsTemps('pl_plugin_'.$pm_id, $ptl_fields_temps, 'ptl_', $tags, $tags_values);

    $cat_tags = array();
    $cat_tags_values = array();
    $layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $cat_tags, $cat_tags_values, true);

    $elems_tags = array();
    $elems_tags_values = array();
    $layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $elems_tags, $elems_tags_values);

    if (count($cat_tags) != 0)
    {
        $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB3);
        $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB3);

        $layout->addPluginFieldsTemps('pl_plugin_'.$pm_id, $ptl_categs_temps, 'ptl_', $tags, $tags_values, true);
    }

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB4);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB4);

	$fld = new sbLayoutInput('text', $ptl_count, 'ptl_count', 'spin_ptl_count', 'style="width:80px;"');
	$fld->mMinValue = 1;

	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COUNT, $fld);

	//Верх вывода
	$fld = new sbLayoutTextarea($ptl_top, 'ptl_top', '', 'style="width:100%;height:100px;"');
	$flds_tags = array( PL_PLUGIN_MAKER_DESIGN_EDIT_INPAGENUM_SELECT,
						'<a href=\'{SORT_ID_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC.'</a>','<a href=\'{SORT_ID_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC.'</a>',
						'<a href=\'{SORT_TITLE_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_ASC.'</a>','<a href=\'{SORT_TITLE_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_DESC.'</a>',
						'<a href=\'{SORT_SORT_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_SORT_ASC.'</a>','<a href=\'{SORT_SORT_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_SORT_DESC.'</a>',
						'<a href=\'{SORT_ACTIVE_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ACTIVE_ASC.'</a>','<a href=\'{SORT_ACTIVE_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ACTIVE_DESC.'</a>',
						'<a href=\'{SORT_USER_ID_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_ASC.'</a>','<a href=\'{SORT_USER_ID_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_DESC.'</a>');

    if(!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    {
        $tmp = array(
            '<a href=\'{SORT_PRICE1_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE1_ASC.'</a>','<a href=\'{SORT_PRICE1_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE1_DESC.'</a>',
			'<a href=\'{SORT_PRICE2_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE2_ASC.'</a>','<a href=\'{SORT_PRICE2_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE2_DESC.'</a>',
			'<a href=\'{SORT_PRICE3_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE3_ASC.'</a>','<a href=\'{SORT_PRICE3_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE3_DESC.'</a>',
			'<a href=\'{SORT_PRICE4_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE4_ASC.'</a>','<a href=\'{SORT_PRICE4_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE4_DESC.'</a>',
			'<a href=\'{SORT_PRICE5_ASC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE5_ASC.'</a>','<a href=\'{SORT_PRICE5_DESC}\'>'.PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE5_DESC.'</a>'
        );
        $flds_tags = array_merge($flds_tags, $tmp);
    }

	$flds_vals = array(	PL_PLUGIN_MAKER_DESIGN_EDIT_INPAGENUM_TAG,
						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC,
						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_DESC,
						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_SORT_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_SORT_DESC,
						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ACTIVE_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ACTIVE_DESC,
						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_DESC);

    if(!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    {
        $tmp = array(
            PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE1_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE1_DESC,
			PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE2_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE2_DESC,
			PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE3_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE3_DESC,
			PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE4_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE4_DESC,
			PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE5_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE5_DESC
        );
        $flds_vals = array_merge($flds_vals, $tmp);
    }


	$layout->getPluginFieldsTagsSort($pm_id, $flds_tags, $flds_vals);
    if(isset($pm_elems_settings['need_basket']) && $pm_elems_settings['need_basket'] == 1)
    {
        $fld->mTags = array_merge(array('{NUM_LIST}', '{ALL_COUNT}', PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_FORM_TAG_VAL_START, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_VAL_START),$flds_tags);
        $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PAGELIST_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ALLNUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_FORM_TAG_START, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_START),$flds_vals);
    }
    else
    {
        $fld->mTags = array_merge(array('{NUM_LIST}', '{ALL_COUNT}', PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_VAL_START),$flds_tags);
        $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PAGELIST_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ALLNUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_START),$flds_vals);
    }
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TOP, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ptl_categ_top, 'ptl_categ_top', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);
    $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_NUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_TOP, $fld);

    $fld = new sbLayoutTextarea($ptl_element, 'ptl_element', '', 'style="width:100%;height:250px;"');

    $arr_basket_tags = array();
    $arr_basket_vals = array();

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
    {
    	if (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    	{
			$arr_basket_tags = array('-',
	        	'{PRICE_1}',
	        	'{PRICE_2}',
	        	'{PRICE_3}',
	        	'{PRICE_4}',
	        	'{PRICE_5}',
				'{SUM_ORDER}',
				'{GOODS_COUNT}',
				'{ORDER}',
				PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_ALL_ORDER_VAL,
				'{RESERVING}');

			$arr_basket_vals = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_TAG,
	        	isset($pm_elems_settings['price1_title']) ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1'),
	        	isset($pm_elems_settings['price2_title']) ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2'),
	        	isset($pm_elems_settings['price3_title']) ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3'),
	        	isset($pm_elems_settings['price4_title']) ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4'),
	        	isset($pm_elems_settings['price5_title']) ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5'),
			PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SUM_ORDER,
			PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_GOODS_COUNT,
	        PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ORDER,
			PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_ALL_ORDER,
			PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_BTN_ADD_TAG);
    	}

        if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
        {
            $arr_basket_tags = array_merge($arr_basket_tags, array('{ORDER_LIST}', '{COUNT_POS}', '{COUNT_GOODS}', '{TOVAR_SUM}', '{TOVAR_SUM_DISCOUNT}'));
            $arr_basket_vals = array_merge($arr_basket_vals, array(
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_BOOKED_LIST_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_POS_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_GOODS_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITHOUT_DISCOUNT_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITH_DISCOUNT_FIELD));
        }
	}

	$arr_compare_tags = array('-', '{COMPARE}', PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DEL_COMPARE_LINK_TAG_VALUE);
	$arr_compare_vals = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_GROUP, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DEL_COMPARE_LINK_TAG);

    $comments_tags = array();
    $comments_values = array();
    if (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1)
    {
    	$comments_tags = array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}');
    	$comments_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_COMMENTS_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_COUNT_COMMENTS_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_LIST_COMMENTS_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_COMMENTS_TAG);
    }

    $voting_tags = array();
    $voting_values = array();
    if (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] == 1)
    {
    	$voting_tags = array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}');
    	$voting_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RATING_GROUP, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RATING, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_VOTES_COUNT, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_VOTES_SUM, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_VOTES_FORM);
    }

    $tags_list_tag = array();
    $tags_list_val = array();
    if(isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
    {
    	$tags_list_tag[] = '{TAGS}';
    	$tags_list_val[] = PL_PLUGIN_MAKER_FORM_EDIT_TAGS_LIST_TAG;
    }

    $standart_tags = array_merge(array('-', '{ELEM_NUMBER}', '{ID}', '{TITLE}', '{MORE}', '{EDIT_LINK}', '{LINK}', '{USER_DATA}', '{CHANGE_DATE}', '{ELEM_USER_LINK}'), $tags_list_tag);
   	$standart_values = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEMS_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM_NUMBER_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ID_TAG, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_MORE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_DATA_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG),$tags_list_val);

   	if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1)
    {
    	$standart_tags[] = '{ELEM_URL}';
    	$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM_URL_TAG;
    }

	if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
	{
		$standart_tags[] = '{SORT}';
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_TAG;
	}

	$fld->mTags = array_merge($standart_tags,
                              $elems_tags,
                              $arr_basket_tags,
                              $arr_compare_tags,
                              $comments_tags,
                              $voting_tags,
                              array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);

    $fld->mValues = array_merge($standart_values,
                                $elems_tags_values,
                                $arr_basket_vals,
                                $arr_compare_vals,
                                $comments_values,
                                $voting_values,
                                array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_NUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);

    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEMENT, $fld);

    $fld = new sbLayoutTextarea($ptl_empty, 'ptl_empty', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_EMPTY, $fld);

    $fld = new sbLayoutTextarea($ptl_delim, 'ptl_delim', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DELIM, $fld);

    $fld = new sbLayoutTextarea($ptl_categ_bottom, 'ptl_categ_bottom', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);
    $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_NUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_BOTTOM, $fld);

    $layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ptl_bottom, 'ptl_bottom', '', 'style="width:100%;height:100px;"');
    if(!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    {
        $fld->mTags = array_merge(array('{NUM_LIST}', '{ALL_COUNT}', PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_FORM_TAG_VAL_END, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_VAL_END),$flds_tags);
        $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PAGELIST_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ALLNUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_FORM_TAG_END, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_END),$flds_vals);
    }
    else
    {
        $fld->mTags = array_merge(array('{NUM_LIST}', '{ALL_COUNT}', PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_VAL_END),$flds_tags);
        $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PAGELIST_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ALLNUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_TAG_END),$flds_vals);
    }
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BOTTOM, $fld);

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fPlugin_Maker_Plugins_Design_Cart_Fields_Get($pm_id, &$tags, &$values, &$cart_fields_options, $reserv = false)
{
	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident = ?','pl_plugin_'.$pm_id);
	if(!$res)
		return;

	$settings = unserialize($res[0][0]);
	if(!is_array($settings))
		return;

	foreach($settings as $value)
	{
		// Если в качестве параметров товара выводится не элемент каталога (а новость, вопрос-ответ и т д)
		// - пропускаем

		if(isset($value['settings']['ident']) && strpos($value['settings']['ident'], 'pl_plugin_') === false)
			continue;

		if($value['type'] == 'multiselect_sprav' || $value['type'] == 'checkbox_sprav' || $value['type'] == 'checkbox_plugin' || $value['type'] == 'multiselect_plugin')
		{
			$values[] = SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE.' '.$value['title'].'';
			$tags[] = '<select name=\'user_'.($reserv ? 'res_' : 'eo_').$pm_id.'_'.$value['id'].'_{ID}\'>{OPTIONS_'.$value['id'].'}</select>';
			$cart_fields_options[] = array(
				'id'		=>	$value['id'],
				'title'		=>	$value['title'],
				'type'		=>	$value['type'],
				'ident'		=>	(isset($value['settings']['ident']) ? $value['settings']['ident'] : '')
			);
		}
	}
}

function fPlugin_Maker_Plugins_Design_List_Edit_Submit()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_design_list'))
		return;

    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    if (!isset($_GET['id']))
        $_GET['id'] = '';

	$ptl_user_data_id = 0;
    $ptl_pagelist_id = 0;
    $ptl_tags_list_id = 0;
    $ptl_comments_id = 0;
    $ptl_votes_id = 0;
    $ptl_checked = array();
    $ptl_lang = SB_CMS_LANG;
    $ptl_fields_temps = array();
    $ptl_categs_temps = array();

    extract($_POST);

    if ($ptl_title == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fPlugin_Maker_Plugins_Design_List_Edit();
        return;
    }

    $row = array();
    $row['ptl_title'] = $ptl_title;
    $row['ptl_lang'] = $ptl_lang;
    $row['ptl_checked'] = implode(' ', $ptl_checked);
    $row['ptl_count'] = $ptl_count;
    $row['ptl_top'] = $ptl_top;
    $row['ptl_categ_top'] = $ptl_categ_top;
    $row['ptl_element'] = $ptl_element;
    $row['ptl_empty'] = $ptl_empty;
    $row['ptl_delim'] = $ptl_delim;
    $row['ptl_categ_bottom'] = $ptl_categ_bottom;
    $row['ptl_bottom'] = $ptl_bottom;
    $row['ptl_pagelist_id'] = $ptl_pagelist_id;
    $row['ptl_perpage'] = $ptl_perpage;
    $row['ptl_no_elems'] = $ptl_no_elems;
    $row['ptl_fields_temps'] = serialize($ptl_fields_temps);
    $row['ptl_categs_temps'] = serialize($ptl_categs_temps);
	$row['ptl_votes_id'] = $ptl_votes_id;
	$row['ptl_comments_id'] = $ptl_comments_id;
	$row['ptl_user_data_id'] = $ptl_user_data_id;
	$row['ptl_tags_list_id'] = $ptl_tags_list_id;

	if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT ptl_title FROM sb_plugins_temps_list WHERE ptl_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_plugins_temps_list SET ?a WHERE ptl_id=?d', $row, $_GET['id'], sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_OK, $old_title, $pm_title));
            sbQueryCache::updateTemplate('sb_plugins_temps_list', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title, $pm_title), SB_MSG_WARNING);

                fPlugin_Maker_Plugins_Design_List_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['ptl_id'] = intval($_GET['id']);

            $html_str = fPlugin_Maker_Plugins_Design_List_Get($row);
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
                fPlugin_Maker_Plugins_Design_List_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SYSTEMLOG_ERROR, $ptl_title, $pm_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_List_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_plugins_temps_list SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_plugins_temps_list', $id);
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_ADD_OK, $ptl_title, $pm_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_plugins_temps_list WHERE ptl_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_ADD_ERROR, $ptl_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_ADD_SYSTEMLOG_ERROR, $ptl_title, $pm_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_List_Edit();
            return;
        }
    }
}

function fPlugin_Maker_Plugins_Design_List_Delete()
{
    $pm_id = intval($_GET['pm_id']);
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_list" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_list" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_PLUGIN_MAKER_PLUGINS_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна выбранного элемента
 */
function fPlugin_Maker_Plugins_Design_Full_Get($args)
{
    $pm_id = intval($_GET['pm_id']);

    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ptf_title'].'</a></b>
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

    $id = intval($args['ptf_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_full" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_full" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fPlugin_Maker_Plugins_Design_Full()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_plugins_temps_full', 'ptf_id', 'ptf_title', 'fPlugin_Maker_Plugins_Design_Full_Get', 'pl_plugin_'.$pm_id.'_design_full&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_design_full');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_full_32.png';

    $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_BY_TITLE, 'ptf_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_design_full_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_design_full_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_design_full_delete&pm_id='.$pm_id;

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_full";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_full";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function elemsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem($pm_title, 'elemsList();', false);

    $elems->init();
}

// редактирование выбранного элемента
function fPlugin_Maker_Plugins_Design_Full_Edit($htmlStr = '', $footerStr = '')
{
	if (!isset($_GET['pm_id']))
		return;

	$pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_design_full'))
		return;

    $res = sql_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_elems_settings) = $res[0];

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT ptf_title, ptf_lang, ptf_fields_temps, ptf_categs_temps, ptf_element, ptf_checked, ptf_votes_id, ptf_comments_id, ptf_user_data_id, ptf_tags_list_id
                                   FROM sb_plugins_temps_full WHERE ptf_id=?d', $_GET['id']);

        if ($result)
        {
            list($ptf_title, $ptf_lang, $ptf_fields_temps, $ptf_categs_temps, $ptf_element, $ptf_checked, $ptf_votes_id, $ptf_comments_id, $ptf_user_data_id, $ptf_tags_list_id) = $result[0];
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($ptf_fields_temps != '')
            $ptf_fields_temps = unserialize($ptf_fields_temps);
        else
            $ptf_fields_temps = array();

        if ($ptf_categs_temps != '')
            $ptf_categs_temps = unserialize($ptf_categs_temps);
        else
            $ptf_categs_temps = array();

        if ($ptf_checked != '')
            $ptf_checked = explode(' ', $ptf_checked);
        else
			$ptf_checked = array();

		if(!isset($ptf_fields_temps['f_registred_users']))
			$ptf_fields_temps['f_registred_users'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG_VAL;
		if(!isset($ptf_fields_temps['p_date']))
			$ptf_fields_temps['p_date'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG_VAL;
		if(!isset($ptf_fields_temps['p_edit_link']))
			$ptf_fields_temps['p_edit_link'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_DEFAULT;
		if(!isset($ptf_fields_temps['p_prev_link']))
			$ptf_fields_temps['p_prev_link'] = PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_LINK_TAG_VAL;
		if(!isset($ptf_fields_temps['p_next_link']))
			$ptf_fields_temps['p_next_link'] = PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_LINK_TAG_VAL;
    }
    elseif (count($_POST) > 0)
    {
        $ptf_checked = array();
        $ptf_votes_id = 0;
        $ptf_comments_id = 0;
		$ptf_user_data_id = 0;
		$ptf_tags_list_id = 0;

        extract($_POST);

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $ptf_title = $ptf_element = '';

    	$ptf_user_data_id = 0;
        $ptf_votes_id = 0;
        $ptf_comments_id = 0;
        $ptf_tags_list_id = 0;
        $ptf_checked = array();
        $ptf_lang = SB_CMS_LANG;
        $ptf_fields_temps = array();
        $ptf_categs_temps = array();
        $ptf_fields_temps['p_date'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG_VAL;
        $ptf_fields_temps['p_edit_link'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_DEFAULT;
        $ptf_fields_temps['p_prev_link'] = PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_LINK_TAG_VAL;
        $ptf_fields_temps['p_next_link'] = PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_LINK_TAG_VAL;

        // добавление полей для корзины
        $ptf_fields_temps['p_prod'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEFAULT;
		$ptf_fields_temps['f_registred_users'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG_VAL;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("ptf_title");
                if (el_title.value == "")
                {
                     alert("'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG.'");
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

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_design_full_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ptf_title, 'ptf_title', '', 'style="width:450px;"', true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ptf_lang');
    $fld->mSelOptions = array($ptf_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes('pl_plugin_'.$pm_id, $ptf_checked, 'ptf_checked');

    if (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] == 1)
    {
    	fVoting_Design_Get($layout, $ptf_votes_id, 'ptf_votes_id');
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', '0', 'ptf_votes_id'));
    }

    if (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1)
    {
    	fComments_Design_Get($layout, $ptf_comments_id, 'ptf_comments_id');
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', '0', 'ptf_comments_id'));
    }

    if(isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
	{
		fClouds_Design_Get($layout, $ptf_tags_list_id, 'ptf_tags_list_id', 'element');
	}
	else
	{
		$layout->addField('', new sbLayoutInput('hidden', '0', 'ptf_tags_list_id'));
	}

	fSite_Users_Design_Get($layout, $ptf_user_data_id, 'ptf_user_data_id');

    $tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
    $tags_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM_URL_TAG, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_TITLE_TAG);

    $elems_tags = array();
    $elems_tags_values = array();
    $layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $elems_tags, $elems_tags_values);

    if (count($elems_tags) != 0 || $_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
    {
        $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2);
        $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2);
    }

	$fld = new sbLayoutTextarea($ptf_fields_temps['f_registred_users'], 'ptf_fields_temps[f_registred_users]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG_VAL), $tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG), $tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG, $fld);

	$fld = new sbLayoutTextarea($ptf_fields_temps['p_date'], 'ptf_fields_temps[p_date]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG, $fld);

	$fld = new sbLayoutTextarea($ptf_fields_temps['p_edit_link'], 'ptf_fields_temps[p_edit_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array('{LINK}'), $tags);
    $fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LINK_EDIT_TAG), $tags_values);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_EDIT_LINK_TAG, $fld);

    // Ссылка на предыдущий элемент
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_prev_link'], 'ptf_fields_temps[p_prev_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_LINK_TAG_VAL, '{PREV_TITLE}'), $tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_LINK_TAG, PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_TITLE_TAG), $tags_values);
	$layout->addField(PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_LINK_TAG, $fld);

	// Ссылка на следующий элемент
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_next_link'], 'ptf_fields_temps[p_next_link]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_LINK_TAG_VAL, '{NEXT_TITLE}'), $tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_LINK_TAG, PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_TITLE_TAG), $tags_values);
	$layout->addField(PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_LINK_TAG, $fld);

	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
    {
    	if (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    	{
			// поля для "Корзины"
	        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_DELIM.'</div>';
	        $layout->addField('', new sbLayoutHTML($html, true));
			$layout->addField('', new sbLayoutDelim());

			// Цена 1
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price1']) ? $ptf_fields_temps['p_price1'] : '{VALUE}', 'ptf_fields_temps[p_price1]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
	        $layout->addField(isset($pm_elems_settings['price1_title']) ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1'), $fld);

			// Цена 2
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price2']) ? $ptf_fields_temps['p_price2'] : '{VALUE}', 'ptf_fields_temps[p_price2]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price2_title']) ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2'), $fld);

	        // Цена 3
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price3']) ? $ptf_fields_temps['p_price3'] : '{VALUE}', 'ptf_fields_temps[p_price3]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price3_title']) ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3'), $fld);

	        // Цена 4
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price4']) ? $ptf_fields_temps['p_price4'] : '{VALUE}', 'ptf_fields_temps[p_price4]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price4_title']) ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4'), $fld);

	        // Цена 5
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price5']) ? $ptf_fields_temps['p_price5'] : '{VALUE}', 'ptf_fields_temps[p_price5]', '', 'style="width:100%;height:80px;"');
	        $fld->mTags = array_merge(array('{VALUE}'), $tags);
	        $fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(isset($pm_elems_settings['price5_title']) ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5'), $fld);

			// Сумма по товару
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_sum_order']) ? $ptf_fields_temps['p_sum_order'] : '{VALUE}', 'ptf_fields_temps[p_sum_order]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array('{VALUE}'), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SUM_ORDER, $fld);

			// Кол-во товара в корзине
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_goods_count']) ? $ptf_fields_temps['p_goods_count'] : '{VALUE}', 'ptf_fields_temps[p_goods_count]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array('{VALUE}'), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_VALUE), $tags_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_GOODS_COUNT, $fld);

			// Добавить в корзину
			$cart_user_tags = array();
			$cart_user_values = array();
			$cart_fields_options = array();
			fPlugin_Maker_Plugins_Design_Cart_Fields_Get($pm_id, $cart_user_tags, $cart_user_values, $cart_fields_options);

			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_prod_add']) ? $ptf_fields_temps['p_prod_add'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ADD_LINK, 'ptf_fields_temps[p_prod_add]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ADD_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEFAULT, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_COUNT_ELEMS_FILED),$cart_user_tags);
			$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_TEXT, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_ADD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ADD_BASKET), $cart_user_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK, $fld);

			if(count($cart_fields_options) > 0)
			{
				foreach($cart_fields_options as $value)
				{
					if($value['type'] == 'multiselect_sprav' || $value['type'] == 'checkbox_sprav')
					{
						$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_cart_field_'.$value['id']]) ? $ptf_fields_temps['p_cart_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>', 'ptf_fields_temps[p_cart_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_ID, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_TITLE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP1_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP2_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP3_TAG);
						$fld->mTags = array('<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>','{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}');
					}
					elseif($value['type'] == 'checkbox_plugin' || $value['type'] == 'multiselect_plugin')
					{
						$elems_fields_tags = array();
						$elems_fields_vals = array();
						$elems_fields = getPluginTitleFields($value['ident']);
						foreach($elems_fields as $value1)
						{
							$elems_fields_tags[] = $value1['tag'];
							$elems_fields_vals[] = $value1['title'];
						}
						$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_cart_field_'.$value['id']]) ? $ptf_fields_temps['p_cart_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>', 'ptf_fields_temps[p_cart_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_ELEM_ID), $elems_fields_vals);
						$fld->mTags = array_merge(array('<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>','{ID}'), $elems_fields_tags);

					}
					$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS.$value['title'], $fld);
				}
				$layout->addField('', new sbLayoutDelim());
			}

			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_prod_del']) ? $ptf_fields_temps['p_prod_del'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_LINK, 'ptf_fields_temps[p_prod_del]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEL_DEFAULT);
			$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_TEXT_DEL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_CHECK_DEL);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL, $fld);

			$layout->addField('', new sbLayoutDelim());

			//	Добавить в резерв
			$cart_user_tags = array();
			$cart_user_values = array();
			$cart_fields_options = array();
			fPlugin_Maker_Plugins_Design_Cart_Fields_Get($pm_id, $cart_user_tags, $cart_user_values, $cart_fields_options, true);

			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_reserved_add']) ? $ptf_fields_temps['p_reserved_add'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_ADD, 'ptf_fields_temps[p_reserved_add]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_ADD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_FORM_ADD), $cart_user_tags);
			$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_ADD_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_CHECK_ADD),$cart_user_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_BTN_ADD_TAG, $fld);

	    	if(count($cart_fields_options) > 0)
			{
				foreach($cart_fields_options as $value)
				{
					if($value['type'] == 'multiselect_sprav' || $value['type'] == 'checkbox_sprav')
					{
						$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_reserved_field_'.$value['id']]) ? $ptf_fields_temps['p_reserved_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>', 'ptf_fields_temps[p_reserved_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_ID, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_SPRAV_TITLE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP1_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP2_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_PROP3_TAG);
						$fld->mTags = array('<option value = \'{ID}\'{SELECTED}>{SPR_TITLE}</option>','{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}');
					}
					elseif($value['type'] == 'checkbox_plugin' || $value['type'] == 'multiselect_plugin')
					{
						$elems_fields_tags = array();
						$elems_fields_vals = array();
						$elems_fields = getPluginTitleFields($value['ident']);
						foreach($elems_fields as $value1)
						{
							$elems_fields_tags[] = $value1['tag'];
							$elems_fields_vals[] = $value1['title'];
						}
						$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_reserved_field_'.$value['id']]) ? $ptf_fields_temps['p_reserved_field_'.$value['id']] : '<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>', 'ptf_fields_temps[p_reserved_field_'.$value['id'].']', '', 'style="width:100%;height:80px;"');
						$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_OPTION, PL_PLUGIN_MAKER_PLUGINS_DESIGN_CART_FIELDS_ELEM_ID), $elems_fields_vals);
						$fld->mTags = array_merge(array('<option value = \'{ID}\'{SELECTED}>'.$elems_fields_tags[0].'</option>','{ID}'), $elems_fields_tags);

					}
					$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_RESERV_FIELDS.$value['title'], $fld);
				}
				$layout->addField('', new sbLayoutDelim());
			}

			// Убрать из резерва
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_reserved_del']) ? $ptf_fields_temps['p_reserved_del'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_DEL, 'ptf_fields_temps[p_reserved_del]', '', 'style="width:100%;height:80px;"');
			$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_DEL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_FORM_DEL);
			$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_LINK_DEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_CHECK_DEL);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_BTN_DEL_TAG, $fld);
    	}

        // Макеты списка элементов
        if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
        {
            $layout->addField('', new sbLayoutHTML('<div class="hint_div" align="center" style="margin-top: 5px;">' . PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_GOODS_TEMP_DESCR . '</div>', true));

            $res = sql_param_query('SELECT pm.pm_id, pm.pm_title, categs.cat_title, temps.ptl_id, temps.ptl_title
				FROM sb_plugins_maker pm
				LEFT JOIN sb_categs categs ON categs.cat_ident = CONCAT("pl_plugin_", pm.pm_id, "_design_list")
				LEFT JOIN sb_catlinks links ON categs.cat_id=links.link_cat_id
				LEFT JOIN sb_plugins_temps_list temps ON temps.ptl_id=links.link_el_id
				WHERE pm.pm_id != ?d
				ORDER BY pm.pm_id, categs.cat_left, temps.ptl_title', $pm_id);

            if ($res)
            {
                $options = array(' --- ');
                $pm_title = $old_cat_title = '';
                $count = count($res);
                $temp_pm_id = $old_temp_pm_id = $i = 0;

                foreach ($res as $key => $value)
                {
                    $i++;
                    $old_pm_title = $pm_title;
                    $old_temp_pm_id = $temp_pm_id;

                    list($temp_pm_id, $pm_title, $cat_title, $ptl_id, $ptl_title) = $value;

                    if ($old_temp_pm_id == 0)
                    {
                        $old_temp_pm_id = $temp_pm_id;
                        $old_pm_title = $pm_title;
                        $old_temp_pm_id = $temp_pm_id;
                    }

                    if ($old_temp_pm_id != $temp_pm_id)
                    {
                        if (count($options) > 1)
                        {
                            $fld = new sbLayoutSelect($options, 'ptf_fields_temps[basket_temp_id' . $old_temp_pm_id . ']');
                            if (isset($ptf_fields_temps['basket_temp_id' . $old_temp_pm_id]))
                            {
                                $fld->mSelOptions = array($ptf_fields_temps['basket_temp_id' . $old_temp_pm_id]);
                            }

                            $fld1 = new sbLayoutSelect($options, 'ptf_fields_temps[mess_basket_temp_id' . $old_temp_pm_id . ']');
                            if (isset($ptf_fields_temps['mess_basket_temp_id' . $old_temp_pm_id]))
                            {
                                $fld1->mSelOptions = array($ptf_fields_temps['mess_basket_temp_id' . $old_temp_pm_id]);
                            }
                        }
                        else
                        {
                            $fld1 = $fld = new sbLayoutLabel('<div class="hint_div">' . sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $old_pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST) . '</div>', '', '', false);
                        }
                        $layout->addField($old_pm_title, $fld);

                        $temps_for_basket[$old_pm_title] = $fld1;

                        $old_temp_pm_id = $temp_pm_id;
                        $options = array(' --- ');
                        $old_cat_title = '';
                    }

                    if ($old_cat_title != $cat_title)
                    {
                        $options[uniqid()] = '-' . $cat_title;
                        $old_cat_title = $cat_title;
                    }

                    if ($ptl_id != '')
                        $options[$ptl_id] = $ptl_title;

                    if ($count == $i)
                    {
                        if (count($options) > 1)
                        {
                            $fld = new sbLayoutSelect($options, 'ptf_fields_temps[basket_temp_id' . $temp_pm_id . ']');
                            if (isset($ptf_fields_temps['basket_temp_id' . $temp_pm_id]))
                            {
                                $fld->mSelOptions = array($ptf_fields_temps['basket_temp_id' . $temp_pm_id]);
                            }

                            $fld1 = new sbLayoutSelect($options, 'ptf_fields_temps[mess_basket_temp_id' . $temp_pm_id . ']');
                            if (isset($ptf_fields_temps['mess_basket_temp_id' . $temp_pm_id]))
                            {
                                $fld1->mSelOptions = array($ptf_fields_temps['mess_basket_temp_id' . $temp_pm_id]);
                            }
                        }
                        else
                        {
                            $fld1 = $fld = new sbLayoutLabel('<div class="hint_div">' . sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST) . '</div>', '', '', false);
                        }
                        $layout->addField($pm_title, $fld);
                        $temps_for_basket[$pm_title] = $fld1;
                    }
                }
            }
        }
    }

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_GROUP.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
	$layout->addField('', new sbLayoutDelim());

	//	поля для "Сравнения"
	$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_compare_add']) ? $ptf_fields_temps['p_compare_add'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_ADD, 'ptf_fields_temps[p_compare_add]', '', 'style="width:100%;height:80px;"');
	$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_ADD, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_ADD);
	$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_ADD_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_CHECK_ADD);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_BTN_ADD_TAG, $fld);

	//	поля для "Сравнения"
	$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_compare_del']) ? $ptf_fields_temps['p_compare_del'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_DEL, 'ptf_fields_temps[p_compare_del]', '', 'style="width:100%;height:80px;"');
	$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_DEL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_FORM_DEL);
	$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK_DEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_CHECK_DEL);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_BTN_DEL_TAG, $fld);

    if (count($elems_tags) != 0)
    {
        $layout->addPluginFieldsTemps('pl_plugin_'.$pm_id, $ptf_fields_temps, 'ptf_', $tags, $tags_values);
    }

    $cat_tags = array();
    $cat_tags_values = array();
    $layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $cat_tags, $cat_tags_values, true);

    if (count($cat_tags) != 0)
    {
        $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB3);
        $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB3);

        $layout->addPluginFieldsTemps('pl_plugin_'.$pm_id, $ptf_categs_temps, 'ptf_', $tags, $tags_values, true);
    }

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM);

    $fld = new sbLayoutTextarea($ptf_element, 'ptf_element', '', 'style="width:100%;height:400px;"');

    $arr_basket_tags = array();
    $arr_basket_vals = array();
	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
    {
    	if (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    	{
	        $arr_basket_tags = array('-',
	        	'{PRICE_1}',
	        	'{PRICE_2}',
	        	'{PRICE_3}',
	        	'{PRICE_4}',
	        	'{PRICE_5}',
				'{SUM_ORDER}',
				'{GOODS_COUNT}',
	        	'{ORDER}',
	    		PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_ALL_ORDER_VAL,
				'{RESERVING}');

	        $arr_basket_vals = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_TAG,
	        	isset($pm_elems_settings['price1_title']) ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '1'),
	        	isset($pm_elems_settings['price2_title']) ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '2'),
	        	isset($pm_elems_settings['price3_title']) ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '3'),
	        	isset($pm_elems_settings['price4_title']) ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '4'),
	        	isset($pm_elems_settings['price5_title']) ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, '5'),
				PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SUM_ORDER,
				PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_GOODS_COUNT,
	        	PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_ORDER,
				PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_PROD_DEL_ALL_ORDER,
				PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RESERVED_BTN_ADD_TAG);
    	}

        if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
        {
            $arr_basket_tags = array_merge($arr_basket_tags, array('-', '{ORDER_LIST}', '{COUNT_POS}', '{COUNT_GOODS}', '{TOVAR_SUM}', '{TOVAR_SUM_DISCOUNT}'));
            $arr_basket_vals = array_merge($arr_basket_vals, array(
            	PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ORDER_TAG,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_BOOKED_LIST_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_POS_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_GOODS_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITHOUT_DISCOUNT_FIELD,
                PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITH_DISCOUNT_FIELD));
        }
    }

	$arr_compare_tags = array('-', '{COMPARE}', PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DEL_COMPARE_LINK_TAG_VALUE);
	$arr_compare_vals = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_GROUP, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_COMPARE_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DEL_COMPARE_LINK_TAG);

	$comments_tags = array();
    $comments_values = array();
    if (isset($pm_elems_settings['show_comments']) && $pm_elems_settings['show_comments'] == 1)
    {
    	$comments_tags = array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}');
    	$comments_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_COMMENTS_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_COUNT_COMMENTS_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_LIST_COMMENTS_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_COMMENTS_TAG);
    }

    $voting_tags = array();
    $voting_values = array();
    if (isset($pm_elems_settings['show_voting']) && $pm_elems_settings['show_voting'] == 1)
    {
    	$voting_tags = array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}');
    	$voting_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RATING_GROUP, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_RATING, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_VOTES_COUNT, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_VOTES_SUM, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_VOTES_FORM);
    }

    $tags_list_tag = array();
    $tags_list_val = array();
	if(isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
    {
    	$tags_list_tag[] = '{TAGS}';
    	$tags_list_val[] = PL_PLUGIN_MAKER_FORM_EDIT_TAGS_LIST_TAG;
    }

	$standart_tags = array_merge(array('-', '{ID}', '{TITLE}', '{USER_DATA}', '{CHANGE_DATE}', '{EDIT_LINK}', '{ELEM_PREV}', '{ELEM_NEXT}', '{ELEM_USER_LINK}'),$tags_list_tag);
	$standart_values = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEMS_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ID_TAG, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_DATA_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_DATE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CHANGE_EDIT_LINK_TAG, PL_PLUGIN_MAKER_DESIGN_EDIT_PREV_LINK_TAG, PL_PLUGIN_MAKER_DESIGN_EDIT_NEXT_LINK_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_LINK_TAG),$tags_list_val);

    if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1)
    {
    	$standart_tags[] = '{ELEM_URL}';
    	$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM_URL_TAG;
    }

	if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
	{
		$standart_tags[] = '{SORT}';
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_TAG;
	}

    $fld->mTags = array_merge($standart_tags,
                              $elems_tags,
                              $arr_basket_tags,
                              $arr_compare_tags,
                              $comments_tags,
                              $voting_tags,
                              array('-', '{CAT_TITLE}', '{CAT_COUNT}', '{CAT_LEVEL}', '{CAT_ID}', '{CAT_URL}'), $cat_tags);

    $fld->mValues = array_merge($standart_values,
                                $elems_tags_values,
                                $arr_basket_vals,
                                $arr_compare_vals,
                                $comments_values,
                                $voting_values,
                                array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_GROUP_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_NUM_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_LEVEL_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CAT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_CATEG_URL_TAG), $cat_tags_values);

    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ELEM, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fPlugin_Maker_Plugins_Design_Full_Edit_Submit()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_design_full'))
		return;

    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ptf_user_data_id = 0;
    $ptf_votes_id = 0;
    $ptf_comments_id = 0;
    $ptf_tags_list_id = 0;
    $ptf_checked = array();
    $ptf_lang = SB_CMS_LANG;
    $ptf_fields_temps = array();
    $ptf_categs_temps = array();

    extract($_POST);

    if ($ptf_title == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fPlugin_Maker_Plugins_Design_Full_Edit();
        return;
    }

    $row['ptf_title'] = $ptf_title;
    $row['ptf_lang'] = $ptf_lang;
    $row['ptf_checked'] = implode(' ', $ptf_checked);
    $row['ptf_element'] = $ptf_element;
    $row['ptf_fields_temps'] = serialize($ptf_fields_temps);
    $row['ptf_categs_temps'] = serialize($ptf_categs_temps);
    $row['ptf_votes_id'] = $ptf_votes_id;
    $row['ptf_comments_id'] = $ptf_comments_id;
	$row['ptf_user_data_id'] = $ptf_user_data_id;
	$row['ptf_tags_list_id'] = $ptf_tags_list_id;

	if ($_GET['id'] != '')
	{
		$res = sql_param_query('SELECT ptf_title FROM sb_plugins_temps_full WHERE ptf_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_plugins_temps_full SET ?a WHERE ptf_id=?d', $row, $_GET['id'], sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FULL_EDIT_OK, $old_title, $pm_title));
            sbQueryCache::updateTemplate('sb_plugins_temps_full', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FULL_EDIT_SYSTEMLOG_ERROR, $old_title, $pm_title), SB_MSG_WARNING);

                fPlugin_Maker_Plugins_Design_Full_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['ptf_id'] = intval($_GET['id']);

            $html_str = fPlugin_Maker_Plugins_Design_Full_Get($row);
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
                fPlugin_Maker_Plugins_Design_Full_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FULL_EDIT_SYSTEMLOG_ERROR, $ptf_title, $pm_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_Full_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_plugins_temps_full SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_plugins_temps_full', $id);
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FULL_ADD_OK, $ptf_title, $pm_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_plugins_temps_full WHERE ptf_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FULL_ADD_ERROR, $ptf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FULL_ADD_SYSTEMLOG_ERROR, $ptf_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_Full_Edit();
            return;
        }
    }
}

function fPlugin_Maker_Plugins_Design_Full_Delete()
{
	$pm_id = intval($_GET['pm_id']);
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_full" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_full" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_PLUGIN_MAKER_PLUGINS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна формы добавления элемента
 */
function fPlugin_Maker_Plugins_Design_Form_Get($args)
{
    $pm_id = intval($_GET['pm_id']);

    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ptf_title'].'</a></b>
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

    $id = intval($args['ptf_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND (elems.e_ident="pl_plugin_'.$pm_id.'_form" OR elems.e_ident="pl_plugin_'.$pm_id.'_form_edit") AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND (elems.e_ident="pl_plugin_'.$pm_id.'_form" OR elems.e_ident="pl_plugin_'.$pm_id.'_form_edit")AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fPlugin_Maker_Plugins_Design_Form()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_plugins_temps_form', 'ptf_id', 'ptf_title', 'fPlugin_Maker_Plugins_Design_Form_Get', 'pl_plugin_'.$pm_id.'_design_form&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_design_form');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_form_32.png';

    $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_BY_TITLE, 'ptf_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_design_form_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_design_form_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_design_form_delete&pm_id='.$pm_id;

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_form";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_form";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function elemsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem($pm_title, 'elemsList();', false);

    $elems->init();
}

function fPlugin_Maker_Plugins_Design_Form_Edit($htmlStr = '', $footerStr = '')
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_design_form'))
		return;

    $res = sql_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_elems_settings) = $res[0];

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT ptf_title, ptf_lang, ptf_form, ptf_fields_temps, ptf_categs_temps, ptf_messages, ptf_user_data_id
                                   FROM sb_plugins_temps_form WHERE ptf_id=?d', $_GET['id']);

        if ($result)
        {
            list($ptf_title, $ptf_lang, $ptf_form, $ptf_fields_temps, $ptf_categs_temps, $ptf_messages, $ptf_user_data_id) = $result[0];
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($ptf_fields_temps != '')
            $ptf_fields_temps = unserialize($ptf_fields_temps);
        else
            $ptf_fields_temps = array();

        if ($ptf_categs_temps != '')
            $ptf_categs_temps = unserialize($ptf_categs_temps);
        else
            $ptf_categs_temps = array();

        if ($ptf_messages != '')
            $ptf_messages = unserialize($ptf_messages);
        else
            $ptf_messages = array();
        if(!isset($ptf_messages['edit_ok']))
        	 $ptf_messages['edit_ok'] = '';
        if(!isset($ptf_messages['edit_error']))
        	$ptf_messages['edit_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_EDIT_ERROR;
        if(!isset($ptf_messages['rights_error_edit']))
        	$ptf_messages['rights_error_edit'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_RIGHTS_EDIT_ERROR;
        if(!isset($ptf_messages['auth_error']))
        	$ptf_messages['auth_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_AUTH_ERROR;
        if(!isset($ptf_messages['admin_subj_edit']))
        	$ptf_messages['admin_subj_edit'] = '';
        if(!isset($ptf_messages['admin_text_edit']))
        	$ptf_messages['admin_text_edit'] = '';
    }
    elseif (count($_POST) > 0)
    {
		$ptf_user_data_id = 0;

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
		$ptf_user_data_id = 0;

        $ptf_title = $ptf_form = '';
        $ptf_lang = SB_CMS_LANG;

        $ptf_fields_temps = array();
        $ptf_fields_temps['p_date_format'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $ptf_fields_temps['p_title'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD_VALUE;
        $ptf_fields_temps['p_title_need'] = 1;
        $ptf_fields_temps['p_url'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_URL_FIELD_VALUE;
        $ptf_fields_temps['p_sort'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SORT_FIELD_VALUE;
        $ptf_fields_temps['p_active'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ACTIVE_FIELD_VALUE;
        $ptf_fields_temps['p_tags'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAGS_FIELD_VALUE;
        $ptf_fields_temps['p_captcha'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_FIELD_VALUE;
		$ptf_fields_temps['p_captcha_img'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_IMG_FIELD_VALUE;
		$ptf_fields_temps['p_categ'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEGS_LIST_FIELD_VALUE;
        $ptf_fields_temps['p_categ_options'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEGS_LIST_OPTION_FIELD_VALUE;
        $ptf_fields_temps['p_price1'] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '1');
        $ptf_fields_temps['p_price2'] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '2');
        $ptf_fields_temps['p_price3'] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '3');
        $ptf_fields_temps['p_price4'] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '4');
        $ptf_fields_temps['p_price5'] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '5');

		$ptf_fields_temps['p_select_start'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SELECT_START_FIELD_VALUE;
		$ptf_fields_temps['p_select_end'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SELECT_END_FIELD_VALUE;

        $ptf_fields_temps['p_price1_val'] = '{VALUE}';
        $ptf_fields_temps['p_price2_val'] = '{VALUE}';
        $ptf_fields_temps['p_price3_val'] = '{VALUE}';
        $ptf_fields_temps['p_price4_val'] = '{VALUE}';
        $ptf_fields_temps['p_price5_val'] = '{VALUE}';

		$ptf_categs_temps = array();

        $ptf_messages = array();
        $ptf_messages['add_ok'] = '';
        $ptf_messages['edit_ok'] = '';
        $ptf_messages['add_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_ADD_ERROR;
        $ptf_messages['edit_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_EDIT_ERROR;
        $ptf_messages['fields_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_FIELDS_ERROR;
        $ptf_messages['file_ext_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_FILE_EXT_ERROR;
	    $ptf_messages['file_size_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_FILE_SIZE_ERROR;
	    $ptf_messages['image_size_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_IMAGE_SIZE_ERROR;
	    $ptf_messages['file_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_FILE_ERROR;
	    $ptf_messages['captcha_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_CAPTCHA_ERROR;
	    $ptf_messages['rights_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_RIGHTS_ERROR;
		$ptf_messages['rights_error_edit'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_RIGHTS_EDIT_ERROR;
		$ptf_messages['auth_error'] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_AUTH_ERROR;


	    $ptf_messages['user_subj'] = '';
	    $ptf_messages['user_text'] = '';
	    $ptf_messages['admin_subj'] = '';
	    $ptf_messages['admin_text'] = '';
	    $ptf_messages['admin_subj_edit'] = '';
	    $ptf_messages['admin_text_edit'] = '';

		$_GET['id'] = '';
	}
	echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("ptf_title");
                if (el_title.value == "")
                {
                     alert("'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }

            function addFile(type)
            {
            	var frm = sbGetE("main_form");
            	var add = sbGetE("ptf_add_file");

            	add.value = type;

				frm.onsubmit(null, true);
            	frm.submit();
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

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_design_form_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main_form');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ptf_title, 'ptf_title', '', 'style="width:450px;"', true));
    $layout->addField('', new sbLayoutDelim());

    // Формат даты
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_date_format'], 'ptf_fields_temps[p_date_format]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_DATE_FORMAT, $fld);

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ptf_lang');
    $fld->mSelOptions = array($ptf_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG, $fld);

	fSite_Users_Design_Get($layout, $ptf_user_data_id, 'ptf_user_data_id');

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB2);
    $layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB2);

    $tags = array('{VALUE}');
	$values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    // Название
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_title'], 'ptf_fields_temps[p_title]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD_VALUE), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField((isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD).'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_title_need]" id="p_title_need"'.(isset($ptf_fields_temps['p_title_need']) && $ptf_fields_temps['p_title_need'] == 1 ? ' checked="checked"' : '').'><label for="p_title_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

	// Псевдостатический адрес
    if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1)
    {
		$fld = new sbLayoutTextarea($ptf_fields_temps['p_url'], 'ptf_fields_temps[p_url]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_URL_FIELD_VALUE), $tags);
		$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_URL_FIELD.'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_url_need]" id="p_url_need"'.(isset($ptf_fields_temps['p_url_need']) && $ptf_fields_temps['p_url_need'] == 1 ? ' checked="checked"' : '').'><label for="p_url_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', $ptf_fields_temps['p_url'], 'ptf_fields_temps[p_url]'));
    }

    // Тематические теги
    if (isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
    {
		$fld = new sbLayoutTextarea($ptf_fields_temps['p_tags'], 'ptf_fields_temps[p_tags]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAGS_FIELD_VALUE), $tags);
		$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAGS_FIELD.'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_tags_need]" id="p_tags_need"'.(isset($ptf_fields_temps['p_tags_need']) && $ptf_fields_temps['p_tags_need'] == 1 ? ' checked="checked"' : '').'><label for="p_tags_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', $ptf_fields_temps['p_tags'], 'ptf_fields_temps[p_tags]'));
    }

    // Индекс сортировки
    if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
    {
		$fld = new sbLayoutTextarea($ptf_fields_temps['p_sort'], 'ptf_fields_temps[p_sort]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SORT_FIELD_VALUE), $tags);
		$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SORT_FIELD.'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_sort_need]" id="p_sort_need"'.(isset($ptf_fields_temps['p_sort_need']) && $ptf_fields_temps['p_sort_need'] == 1 ? ' checked="checked"' : '').'><label for="p_sort_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', $ptf_fields_temps['p_sort'], 'ptf_fields_temps[p_sort]'));
    }

    // Публиковать на сайте
    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
    {
		$fld = new sbLayoutTextarea($ptf_fields_temps['p_active'], 'ptf_fields_temps[p_active]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ACTIVE_FIELD_VALUE), $tags);
		$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ACTIVE_FIELD, $fld);
    }
    else
    {
    	$layout->addField('', new sbLayoutInput('hidden', $ptf_fields_temps['p_active'], 'ptf_fields_temps[p_active]'));
    }

	// Список разделов
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_categ'], 'ptf_fields_temps[p_categ]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEGS_LIST_FIELD_VALUE, '{OPTIONS}');
	$fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEG_FIELD.'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_categ_need]"'.(isset($ptf_fields_temps['p_categ_need']) && $ptf_fields_temps['p_categ_need'] == 1 ? ' checked="checked"' : '').'><label for="p_categ_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    $fld = new sbLayoutTextarea($ptf_fields_temps['p_categ_options'], 'ptf_fields_temps[p_categ_options]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEGS_LIST_OPTION_FIELD_VALUE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEGS_LIST_CHECKBOX_FIELD_VALUE, '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_SELECTED}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_SELECT, SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_CHECKBOX, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAT_ID_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
    $layout->addField('', $fld);

    // Поле ввода кода с картинки (CAPTCHA)
    $fld = new sbLayoutTextarea($ptf_fields_temps['p_captcha'], 'ptf_fields_temps[p_captcha]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_FIELD_VALUE);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_FIELD, $fld);

    // Картинка с кодом (CAPTCHA)
    $fld = new sbLayoutTextarea($ptf_fields_temps['p_captcha_img'], 'ptf_fields_temps[p_captcha_img]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_IMG_FIELD_VALUE);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_IMG_FIELD, $fld);

	$basket_tags = array();
	$basket_values = array();
	$temps_for_basket = array(); // массив, в котором хранятся макеты "вывода списка товаров" для "вывода корзины", для закладки "поля сообщений". Создан чтоб не дублировать ниже следующий код.

	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') &&
        (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1) &&
		(isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 1 ||
		 isset($pm_elems_settings['price2_type']) && $pm_elems_settings['price2_type'] == 1 ||
		 isset($pm_elems_settings['price3_type']) && $pm_elems_settings['price3_type'] == 1 ||
		 isset($pm_elems_settings['price4_type']) && $pm_elems_settings['price4_type'] == 1 ||
		 isset($pm_elems_settings['price5_type']) && $pm_elems_settings['price5_type'] == 1))
	{
		// поля для интернет-магазина
		$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_DELIM.'</div>';
		$layout->addField('', new sbLayoutHTML($html, true));
		$layout->addField('', new sbLayoutDelim());

		$basket_tags = array('-');
		$basket_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_TAG);

		// Цена 1
		if (isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 1)
	    {
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price1']) ? $ptf_fields_temps['p_price1'] : '', 'ptf_fields_temps[p_price1]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '1')), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')).'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_price1_need]" id="p_price1_need"'.(isset($ptf_fields_temps['p_price1_need']) && $ptf_fields_temps['p_price1_need'] == 1 ? ' checked="checked"' : '').'><label for="p_price1_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

	    	if (isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '')
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '1', $pm_elems_settings['price1_title'], '1', '1');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price1_title']);
			}
			else
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '1', sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1'), '1', '1');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1'));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price1']) ? $ptf_fields_temps['p_price1'] : '', 'ptf_fields_temps[p_price1]'));
	    }

		// Цена 2
		if (isset($pm_elems_settings['price2_type']) && $pm_elems_settings['price2_type'] == 1)
	    {
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price2']) ? $ptf_fields_temps['p_price2'] : '', 'ptf_fields_temps[p_price2]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '2')), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')).'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_price2_need]" id="p_price2_need"'.(isset($ptf_fields_temps['p_price2_need']) && $ptf_fields_temps['p_price2_need'] == 1 ? ' checked="checked"' : '').'><label for="p_price2_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

	    	if (isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '')
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '2', $pm_elems_settings['price2_title'], '2', '2');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price2_title']);
			}
			else
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '2', sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2'), '2', '2');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2'));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price2']) ? $ptf_fields_temps['p_price2'] : '', 'ptf_fields_temps[p_price2]'));
	    }

		// Цена 3
		if (isset($pm_elems_settings['price3_type']) && $pm_elems_settings['price3_type'] == 1)
	    {
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price3']) ? $ptf_fields_temps['p_price3'] : '', 'ptf_fields_temps[p_price3]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '3')), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')).'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_price3_need]" id="p_price3_need"'.(isset($ptf_fields_temps['p_price3_need']) && $ptf_fields_temps['p_price3_need'] == 1 ? ' checked="checked"' : '').'><label for="p_price3_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

	    	if (isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '')
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '3', $pm_elems_settings['price3_title'], '3', '3');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price3_title']);
			}
			else
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '3', sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3'), '3', '3');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3'));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price3']) ? $ptf_fields_temps['p_price3'] : '', 'ptf_fields_temps[p_price3]'));
	    }

		// Цена 4
		if (isset($pm_elems_settings['price4_type']) && $pm_elems_settings['price4_type'] == 1)
	    {
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price4']) ? $ptf_fields_temps['p_price4'] : '', 'ptf_fields_temps[p_price4]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '4')), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')).'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_price4_need]" id="p_price4_need"'.(isset($ptf_fields_temps['p_price4_need']) && $ptf_fields_temps['p_price4_need'] == 1 ? ' checked="checked"' : '').'><label for="p_price4_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

	    	if (isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '')
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '4', $pm_elems_settings['price4_title'], '4', '4');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price4_title']);
			}
			else
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '4', sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4'), '4', '4');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4'));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price4']) ? $ptf_fields_temps['p_price4'] : '', 'ptf_fields_temps[p_price4]'));
	    }

		// Цена 5
		if (isset($pm_elems_settings['price5_type']) && $pm_elems_settings['price5_type'] == 1)
	    {
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price5']) ? $ptf_fields_temps['p_price5'] : '', 'ptf_fields_temps[p_price5]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_VALUE, '5')), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')).'<br><input type="checkbox" value="1" name="ptf_fields_temps[p_price5_need]" id="p_price5_need"'.(isset($ptf_fields_temps['p_price5_need']) && $ptf_fields_temps['p_price5_need'] == 1 ? ' checked="checked"' : '').'><label for="p_price5_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

	    	if (isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '')
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '5', $pm_elems_settings['price5_title'], '5', '5');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price5_title']);
			}
			else
			{
				$basket_tags[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD_TAG, '5', sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5'), '5', '5');
				$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5'));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price5']) ? $ptf_fields_temps['p_price5'] : '', 'ptf_fields_temps[p_price5]'));
	    }
	}

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') &&
    isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
    {
		$layout->addField('', new sbLayoutHTML('<div class="hint_div" align="center" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_GOODS_TEMP_DESCR.'</div>', true));

		$res = sql_param_query('SELECT pm.pm_id, pm.pm_title, categs.cat_title, temps.ptl_id, temps.ptl_title
				FROM sb_plugins_maker pm
				LEFT JOIN sb_categs categs ON categs.cat_ident = CONCAT("pl_plugin_", pm.pm_id, "_design_list")
				LEFT JOIN sb_catlinks links ON categs.cat_id=links.link_cat_id
				LEFT JOIN sb_plugins_temps_list temps ON temps.ptl_id=links.link_el_id
				WHERE pm.pm_id != ?d
				ORDER BY pm.pm_id, categs.cat_left, temps.ptl_title', $pm_id);

		if($res)
		{
			$options = array(' --- ');
	        $pm_title = $old_cat_title = '';
			$count = count($res);
			$temp_pm_id = $old_temp_pm_id = $i = 0;

			foreach($res as $key => $value)
			{
				$i++;
				$old_pm_title = $pm_title;
				$old_temp_pm_id = $temp_pm_id;

				list($temp_pm_id, $pm_title, $cat_title, $ptl_id, $ptl_title) = $value;

				if($old_temp_pm_id == 0)
				{
					$old_temp_pm_id = $temp_pm_id;
					$old_pm_title = $pm_title;
					$old_temp_pm_id = $temp_pm_id;
				}

				if($old_temp_pm_id != $temp_pm_id)
				{
					if(count($options) > 1)
					{
						$fld = new sbLayoutSelect($options, 'ptf_fields_temps[basket_temp_id'.$old_temp_pm_id.']');
						if (isset($ptf_fields_temps['basket_temp_id'.$old_temp_pm_id]))
				        {
							$fld->mSelOptions = array($ptf_fields_temps['basket_temp_id'.$old_temp_pm_id]);
				        }

						$fld1 = new sbLayoutSelect($options, 'ptf_fields_temps[mess_basket_temp_id'.$old_temp_pm_id.']');
						if (isset($ptf_fields_temps['mess_basket_temp_id'.$old_temp_pm_id]))
				        {
							$fld1->mSelOptions = array($ptf_fields_temps['mess_basket_temp_id'.$old_temp_pm_id]);
				        }
					}
					else
					{
						$fld1 = $fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $old_pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST).'</div>', '', '', false);
					}
					$layout->addField($old_pm_title, $fld);

					$temps_for_basket[$old_pm_title] = $fld1;

					$old_temp_pm_id = $temp_pm_id;
					$options = array(' --- ');
			        $old_cat_title = '';
				}

	            if ($old_cat_title != $cat_title)
	            {
	                $options[uniqid()] = '-'.$cat_title;
	                $old_cat_title = $cat_title;
	            }

				if($ptl_id != '')
					$options[$ptl_id] = $ptl_title;

				if($count == $i)
				{
				    if (count($options) > 1)
				    {
						$fld = new sbLayoutSelect($options, 'ptf_fields_temps[basket_temp_id'.$temp_pm_id.']');
				        if (isset($ptf_fields_temps['basket_temp_id'.$temp_pm_id]))
				        {
							$fld->mSelOptions = array($ptf_fields_temps['basket_temp_id'.$temp_pm_id]);
				        }

						$fld1 = new sbLayoutSelect($options, 'ptf_fields_temps[mess_basket_temp_id'.$temp_pm_id.']');
				        if (isset($ptf_fields_temps['mess_basket_temp_id'.$temp_pm_id]))
				        {
							$fld1->mSelOptions = array($ptf_fields_temps['mess_basket_temp_id'.$temp_pm_id]);
				        }
				    }
					else
				    {
						$fld1 = $fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST).'</div>', '', '', false);
					}
					$layout->addField($pm_title, $fld);
					$temps_for_basket[$pm_title] = $fld1;
				}
			}
		}
	}

	$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
	if ($res)
	{
		list($pd_fields, $pd_categs) = $res[0];
    	if ($pd_fields != '')
    	{
    		$pd_fields = unserialize($pd_fields);
    		if ($pd_fields[0]['type'] != 'tab')
    		{
				$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2.'</div>';
	    		$layout->addField('', new sbLayoutHTML($html, true));
	    		$layout->addField('', new sbLayoutDelim());
    		}
	    	// Пользовательские поля
			$layout->addPluginInputFieldsTemps('pl_plugin_'.$pm_id, $ptf_fields_temps, 'ptf_', '', array(), array(), false, true, '', '', false, true);
    	}
    	else
    	{
    		$pd_fields = array();
    	}

    	if ($pd_categs != '')
    	{
    		$pd_categs = unserialize($pd_categs);
    	}
    	else
    	{
    		$pd_categs = array();
    	}
    }

	$layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB3);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB3);

	$user_tags = array();
	$user_tags_values = array();
	$layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $user_tags, $user_tags_values, false, true, true, false, true, true);

	$standart_tags = array('-',
    					'{MESSAGE}',
	(isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1 ?
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FORM_FIELD_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FORM_RECALC_FIELD_TAG) :
   					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FORM_FIELD_TAG, '')),
    					isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD_TAG, $pm_elems_settings['title_field_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD));

    $standart_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1,
						PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGE_TAG,
						PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FORM_TAG,
						isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['title_field_title']) : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD));

	if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1)
	{
		$standart_tags[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_URL_FIELD_TAG;
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_URL_TAG;
	}

	if (isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
	{
		$standart_tags[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAGS_FIELD_TAG;
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAGS_TAG;
	}

	if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
	{
		$standart_tags[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SORT_FIELD_TAG;
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SORT_TAG;
	}

	if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	{
		$standart_tags[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ACTIVE_FIELD_TAG;
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ACTIVE_TAG;
	}

	if(isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
    {
		$basket_tags[] = '{ORDER_LIST}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_BOOKED_LIST_FIELD;

		$basket_tags[] = '{COUNT_POSITIONS}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_POS_FIELD;

		$basket_tags[] = '{COUNT_GOODS}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_GOODS_FIELD;

		$basket_tags[] = '{TOVAR_SUM}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITHOUT_DISCOUNT_FIELD;

		$basket_tags[] = '{TOVAR_SUM_DISCOUNT}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITH_DISCOUNT_FIELD;

		$basket_tags[] =  PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_DELETE_BASKET_FIELD_VAL;
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_DELETE_BASKET_FIELD;
	}

	$standart_tags = array_merge($standart_tags,
						array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEG_FIELD_TAG,
    						  PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_FIELD_TAG,
    						  '{P_CAPTCHA_IMG}'));

    $standart_values = array_merge($standart_values,
    					array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEG_TAG,
							  PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_TAG,
						      PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAPTCHA_IMG_TAG));

	$standart_tags = array_merge($standart_tags, $basket_tags);
	$standart_values = array_merge($standart_values, $basket_values);

	// Форма
	$fld = new sbLayoutTextarea($ptf_form, 'ptf_form', '', 'style="width:100%;height:250px;"');
    $fld->mTags = array_merge($standart_tags, $user_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB3, $fld);

	// Конец выделения обязательных полей
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_select_start'], 'ptf_fields_temps[p_select_start]', '', 'style="width:100%;height:50px;"');
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SELECT_START, $fld);

	// Начало выделения обязательных полей
	$fld = new sbLayoutTextarea($ptf_fields_temps['p_select_end'], 'ptf_fields_temps[p_select_end]', '', 'style="width:100%;height:50px;"');
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SELECT_END, $fld);

	$layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB4);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB4);

	$tags = array('{VALUE}', '{P_ID}', '{P_TITLE}');
	$values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_VALUE, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ID_FIELD, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG);

	$basket_tags = array();
	$basket_values = array();

	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1))
	{
		$basket_tags = array('-');
		$basket_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_TAG);

		// поля для интернет-магазина
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_DELIM.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        // Цена 1
		$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price1_val']) ? $ptf_fields_temps['p_price1_val'] : '', 'ptf_fields_temps[p_price1_val]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = $tags;
		$fld->mValues = $values;
		$layout->addField(isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1'), $fld);

		// Цена 2
		$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price2_val']) ? $ptf_fields_temps['p_price2_val'] : '', 'ptf_fields_temps[p_price2_val]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = $tags;
		$fld->mValues = $values;
		$layout->addField(isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2'), $fld);

		// Цена 3
		$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price3_val']) ? $ptf_fields_temps['p_price3_val'] : '', 'ptf_fields_temps[p_price3_val]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = $tags;
		$fld->mValues = $values;
		$layout->addField(isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3'), $fld);

		// Цена 4
		$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price4_val']) ? $ptf_fields_temps['p_price4_val'] : '', 'ptf_fields_temps[p_price4_val]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = $tags;
		$fld->mValues = $values;
		$layout->addField(isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4'), $fld);

		// Цена 5
		$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price5_val']) ? $ptf_fields_temps['p_price5_val'] : '', 'ptf_fields_temps[p_price5_val]', '', 'style="width:100%;height:50px;"');
		$fld->mTags = $tags;
		$fld->mValues = $values;
		$layout->addField(isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5'), $fld);

		if($temps_for_basket)
		{
			$layout->addField('', new sbLayoutHTML('<div class="hint_div" align="center" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_GOODS_TEMP_DESCR.'</div>', true));

			foreach($temps_for_basket as $key => $value)
			{
				$layout->addField($key, $value);
			}
		}

		// Цена 1
		if (isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '')
		{
			$basket_tags[] = '{P_PRICE_1}';
			$basket_values[] = $pm_elems_settings['price1_title'];
		}
		else
		{
			$basket_tags[] = '{P_PRICE_1}';
			$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1');
		}

		// Цена 2
		if (isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '')
		{
			$basket_tags[] = '{P_PRICE_2}';
			$basket_values[] = $pm_elems_settings['price2_title'];
		}
		else
		{
			$basket_tags[] = '{P_PRICE_2}';
			$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2');
		}

		// Цена 3
		if (isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '')
		{
			$basket_tags[] = '{P_PRICE_3}';
			$basket_values[] = $pm_elems_settings['price3_title'];
		}
		else
		{
			$basket_tags[] = '{P_PRICE_3}';
			$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3');
		}

		// Цена 4
		if (isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '')
		{
			$basket_tags[] = '{P_PRICE_4}';
			$basket_values[] = $pm_elems_settings['price4_title'];
		}
		else
		{
			$basket_tags[] = '{P_PRICE_4}';
			$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4');
		}

		// Цена 5
		if (isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '')
		{
			$basket_tags[] = '{P_PRICE_5}';
			$basket_values[] = $pm_elems_settings['price5_title'];
		}
		else
		{
			$basket_tags[] = '{P_PRICE_5}';
			$basket_values[] = sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5');
		}

		$basket_tags[] = '{ORDER_LIST}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_BOOKED_LIST_FIELD;

		$basket_tags[] = '{COUNT_POSITIONS}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_POS_FIELD;

		$basket_tags[] = '{COUNT_GOODS}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_COUNT_GOODS_FIELD;

		$basket_tags[] = '{TOVAR_SUM}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITHOUT_DISCOUNT_FIELD;

		$basket_tags[] = '{TOVAR_SUM_DISCOUNT}';
		$basket_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_WITH_DISCOUNT_FIELD;
	}

	if (isset($pd_fields) && count($pd_fields) > 0)
    {
    	if ($pd_fields[0]['type'] != 'tab')
    	{
			$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2.'</div>';
	    	$layout->addField('', new sbLayoutHTML($html, true));
	    	$layout->addField('', new sbLayoutDelim());
    	}

		$layout->addPluginFieldsTemps('pl_plugin_'.$pm_id, $ptf_fields_temps, 'ptf_', array('{P_ID}', '{P_TITLE}'), array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ID_FIELD, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG), false, '', '', '_val', false, true);
    }

    if (isset($pd_categs) && count($pd_categs) > 0)
    {
    	if ($pd_categs[0]['type'] != 'tab')
    	{
    		$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB3.'</div>';
			$layout->addField('', new sbLayoutHTML($html, true));
			$layout->addField('', new sbLayoutDelim());
    	}

		$layout->addPluginFieldsTemps('pl_plugin_'.$pm_id, $ptf_categs_temps, 'ptf_', array('{P_ID}', '{P_TITLE}'), array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ID_FIELD, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG), true, '', '', '_val', false, true);
    }

    $layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB5);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB5);

	$user_tags = array();
	$user_tags_values = array();
	$layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $user_tags, $user_tags_values, false, false, false, false, true);

	$user_categ_tags = array('-', '{P_CAT_TITLE}', '{P_CAT_ID}', '{P_CAT_URL}');
	$user_categ_tags_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CATEG_VAL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAT_ID_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_CAT_URL_TAG);
	$layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $user_categ_tags, $user_categ_tags_values, true, false, false, false, true);

	$standart_tags = array('-', '{P_TITLE}', '{P_ID}');
	$standart_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1, isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ID_FIELD);

	if (isset($pm_elems_settings['show_chpu_field']) && $pm_elems_settings['show_chpu_field'] == 1)
	{
		$standart_tags[] = '{P_URL}';
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_URL_FIELD;
	}

	if (isset($pm_elems_settings['show_tags_field']) && $pm_elems_settings['show_tags_field'] == 1)
	{
		$standart_tags[] = '{P_TAGS}';
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAGS_FIELD;
	}

	if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
	{
		$standart_tags[] = '{P_SORT}';
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SORT_FIELD;
	}

	if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	{
		$standart_tags[] = '{P_ACTIVE}';
		$standart_values[] = PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ACTIVE_FIELD;
	}

	$standart_tags = array_merge($standart_tags, array('{USER_DATA}'), $basket_tags);
	$standart_values = array_merge($standart_values, array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_DATA_TAG), $basket_values);

	// Элемент успешно добавлен
	$fld = new sbLayoutTextarea($ptf_messages['add_ok'], 'ptf_messages[add_ok]', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_ADD_OK_TITLE, $fld);

	// Элемент успешно изменен
	$fld = new sbLayoutTextarea($ptf_messages['edit_ok'], 'ptf_messages[edit_ok]', '', 'style="width:100%;height:100px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_EDIT_OK_TITLE, $fld);

	$users_tags = array('{P_TITLE}', '{USER_DATA}');
	$users_tags_values = array(isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_USER_DATA_TAG);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ptf_messages['add_error'], 'ptf_messages[add_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MESSAGES_ERRORS, $fld);

	$fld = new sbLayoutTextarea($ptf_messages['edit_error'], 'ptf_messages[edit_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['fields_error'], 'ptf_messages[fields_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['file_ext_error'], 'ptf_messages[file_ext_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = array_merge(array('{P_FILE_EXT}', '{P_FILE}'), $users_tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FILE_EXT_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FILE_TAG), $users_tags_values);
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['file_size_error'], 'ptf_messages[file_size_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = array_merge(array('{P_FILE_SIZE}', '{P_FILE}'), $users_tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FILE_SIZE_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FILE_TAG), $users_tags_values);
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['image_size_error'], 'ptf_messages[image_size_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = array_merge(array('{P_IMAGE_WIDTH}', '{P_IMAGE_HEIGHT}', '{P_FILE}'), $users_tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_IMAGE_WIDTH_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_IMAGE_HEIGHT_TAG, PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FILE_TAG), $users_tags_values);
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['file_error'], 'ptf_messages[file_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = array_merge(array('{P_FILE}'), $users_tags);
	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FILE_TAG), $users_tags_values);
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['captcha_error'], 'ptf_messages[captcha_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['auth_error'], 'ptf_messages[auth_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['rights_error'], 'ptf_messages[rights_error]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField('', $fld);

	$fld = new sbLayoutTextarea($ptf_messages['rights_error_edit'], 'ptf_messages[rights_error_edit]', '', 'style="width:100%;height:60px;"');
	$fld->mTags = $users_tags;
	$fld->mValues = $users_tags_values;
	$layout->addField('', $fld);

	$layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_TAB);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_TAB);

	$fld = new sbLayoutTextarea($ptf_messages['user_subj'], 'ptf_messages[user_subj]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_USER_SUBJ_TITLE, $fld);

	$fld = new sbLayoutTextarea($ptf_messages['user_text'], 'ptf_messages[user_text]', '', 'style="width:100%;height:200px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_USER_TEXT_TITLE, $fld);

	$i = 0;
	if (isset($ptf_messages['user_file']) && is_array($ptf_messages['user_file']) && isset($ptf_messages['user_file_name']) && is_array($ptf_messages['user_file_name']))
	{
		foreach ($ptf_messages['user_file'] as $value)
		{
			if (trim($value) == '')
				continue;

			$layout->addField('', new sbLayoutDelim());

			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE_NAME, new sbLayoutInput('text', $ptf_messages['user_file_name'][$i], 'ptf_messages[user_file_name]['.$i.']', '', 'style="width: 300px;"'));

			$fld = new sbLayoutTextarea($value, 'ptf_messages[user_file]['.$i.']', '', 'style="width:100%;height:200px;"');
			$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
			$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE, $fld);
			$i++;
		}
	}

	if (isset($_POST['ptf_add_file']) && $_POST['ptf_add_file'] == 'user')
	{
		$layout->addField('', new sbLayoutDelim());

		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE_NAME, new sbLayoutInput('text', '', 'ptf_messages[user_file_name]['.$i.']', '', 'style="width: 300px;"'));

		$fld = new sbLayoutTextarea('', 'ptf_messages[user_file]['.$i.']', '', 'style="width:100%;height:200px;"');
		$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
		$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE, $fld);
	}

	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADD_FILE, new sbLayoutHTML('&nbsp;<img class="button" onmouseup="sbPress(this, false)" onmousedown="sbPress(this, true);addFile(\'user\');" align="top" src="'.SB_CMS_IMG_URL.'/btn_add.png" width="20" height="20" alt="'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADD_FILE.'" title="'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADD_FILE.'" />'));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ptf_messages['admin_subj'], 'ptf_messages[admin_subj]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADMIN_SUBJ_TITLE, $fld);

	$fld = new sbLayoutTextarea($ptf_messages['admin_text'], 'ptf_messages[admin_text]', '', 'style="width:100%;height:200px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADMIN_TEXT_TITLE, $fld);

	$fld = new sbLayoutTextarea($ptf_messages['admin_subj_edit'], 'ptf_messages[admin_subj_edit]', '', 'style="width:100%;height:50px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADMIN_SUBJ_EDIT_TITLE, $fld);

	$fld = new sbLayoutTextarea($ptf_messages['admin_text_edit'], 'ptf_messages[admin_text_edit]', '', 'style="width:100%;height:200px;"');
	$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
	$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADMIN_TEXT_EDIT_TITLE, $fld);

	$i = 0;
	if (isset($ptf_messages['admin_file']) && is_array($ptf_messages['admin_file']) && isset($ptf_messages['admin_file_name']) && is_array($ptf_messages['admin_file_name']))
	{
		foreach ($ptf_messages['admin_file'] as $value)
		{
			if (trim($value) == '')
				continue;

			$layout->addField('', new sbLayoutDelim());

			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE_NAME, new sbLayoutInput('text', $ptf_messages['admin_file_name'][$i], 'ptf_messages[admin_file_name]['.$i.']', '', 'style="width: 300px;"'));

			$fld = new sbLayoutTextarea($value, 'ptf_messages[admin_file]['.$i.']', '', 'style="width:100%;height:200px;"');
			$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
			$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
			$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE, $fld);
			$i++;
		}
	}

	if (isset($_POST['ptf_add_file']) && $_POST['ptf_add_file'] == 'admin')
	{
		$layout->addField('', new sbLayoutDelim());

		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE_NAME, new sbLayoutInput('text', '', 'ptf_messages[admin_file_name]['.$i.']', '', 'style="width: 300px;"'));

		$fld = new sbLayoutTextarea('', 'ptf_messages[admin_file]['.$i.']', '', 'style="width:100%;height:200px;"');
		$fld->mTags = array_merge($standart_tags, $user_tags, $user_categ_tags);
		$fld->mValues = array_merge($standart_values, $user_tags_values, $user_categ_tags_values);
		$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_FILE, $fld);
	}

	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADD_FILE, new sbLayoutHTML('&nbsp;<img class="button" onmouseup="sbPress(this, false)" onmousedown="sbPress(this, true);addFile(\'admin\');" align="top" src="'.SB_CMS_IMG_URL.'/btn_add.png" width="20" height="20" alt="'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADD_FILE.'" title="'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_MAIL_ADD_FILE.'" />'));
	$layout->addField('', new sbLayoutInput('hidden', '', 'ptf_add_file'));

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fPlugin_Maker_Plugins_Design_Form_Edit_Submit()
{
	if (!isset($_GET['pm_id']))
        return;

    if (isset($_POST['ptf_add_file']) && $_POST['ptf_add_file'] != '')
    {
    	fPlugin_Maker_Plugins_Design_Form_Edit();
    	return;
    }

    $pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_design_form'))
		return;

    $res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ptf_lang = SB_CMS_LANG;
    $ptf_fields_temps = array();
    $ptf_categs_temps = array();
    $ptf_messages = array();
    $ptf_user_data_id = 0;

    extract($_POST);

    if ($ptf_title == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fPlugin_Maker_Plugins_Design_Form_Edit();
        return;
    }

    $row['ptf_title'] = $ptf_title;
    $row['ptf_lang'] = $ptf_lang;
    $row['ptf_form'] = $ptf_form;
    $row['ptf_fields_temps'] = serialize($ptf_fields_temps);
    $row['ptf_categs_temps'] = serialize($ptf_categs_temps);
    $row['ptf_messages'] = serialize($ptf_messages);
	$row['ptf_user_data_id'] = $ptf_user_data_id;

	if ($_GET['id'] != '')
	{
		$res = sql_param_query('SELECT ptf_title FROM sb_plugins_temps_form WHERE ptf_id=?d', $_GET['id']);
		if ($res)
		{
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_plugins_temps_form SET ?a WHERE ptf_id=?d', $row, $_GET['id'], sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_OK, $old_title, $pm_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SYSTEMLOG_ERROR, $old_title, $pm_title), SB_MSG_WARNING);

                fPlugin_Maker_Plugins_Design_Form_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['ptf_id'] = intval($_GET['id']);

            $html_str = fPlugin_Maker_Plugins_Design_Form_Get($row);
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
                fPlugin_Maker_Plugins_Design_Form_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_SYSTEMLOG_ERROR, $ptf_title, $pm_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_Form_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_plugins_temps_form SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {

                sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_ADD_OK, $ptf_title, $pm_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_plugins_temps_form WHERE ptf_id="'.$id.'"');
            }
		}

        if ($error)
        {
            sb_show_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_ADD_ERROR, $ptf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_ADD_SYSTEMLOG_ERROR, $ptf_title, $pm_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_Form_Edit();
            return;
        }
    }
}

function fPlugin_Maker_Plugins_Design_Form_Delete()
{
	$pm_id = intval($_GET['pm_id']);
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_form" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_form" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_PLUGIN_MAKER_PLUGINS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна вывода разделов
 */

function fPlugin_Maker_Design_Categs_Get($args)
{
	$pm_id = intval($_GET['pm_id']);
	return fCategs_Design_Get($args, 'pl_plugin_'.$pm_id.'_categs', 'pl_plugin_'.$pm_id);
}

function fPlugin_Maker_Design_Categs()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_list', 'ctl_id', 'ctl_title', 'fPlugin_Maker_Design_Categs_Get', 'pl_plugin_'.$pm_id.'_design_categs&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_design_categs');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_categs_32.png';

    $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_BY_TITLE, 'ctl_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_design_categs_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_design_categs_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_design_categs_delete&pm_id='.$pm_id;

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_categs";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function elemsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem($pm_title, 'elemsList();', false);

    $elems->init();
}

function fPlugin_Maker_Design_Categs_Edit()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    fCategs_Design_Edit('pl_plugin_'.$pm_id.'_design_categs', 'pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id.'_design_categs_edit_submit');
}

function fPlugin_Maker_Design_Categs_Edit_Submit()
{
    if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    fCategs_Design_Edit_Submit('pl_plugin_'.$pm_id.'_design_categs', 'pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id.'_design_categs_edit_submit', 'pl_plugin_'.$pm_id.'_categs', 'pl_plugin_'.$pm_id);
}

function fPlugin_Maker_Design_Categs_Delete()
{
	if (!isset($_GET['pm_id']))
        return;

	$pm_id = intval($_GET['pm_id']);
	fCategs_Design_Delete('pl_plugin_'.$pm_id.'_categs', 'pl_plugin_'.$pm_id);
}

/**
 * Функции управления макетами дизайна вывода выбранного раздела
 */

function fPlugin_Maker_Design_Selcat_Get($args)
{
	$pm_id = intval($_GET['pm_id']);

	return fCategs_Design_Get($args, 'pl_plugin_'.$pm_id.'_sel_cat', 'pl_plugin_'.$pm_id, true);
}

function fPlugin_Maker_Design_Selcat()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title) = $res[0];

    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_full', 'ctf_id', 'ctf_title', 'fPlugin_Maker_Design_Selcat_Get', 'pl_plugin_'.$pm_id.'_design_sel_cat&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_design_sel_cat');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_sel_cat_32.png';

    $elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_BY_TITLE, 'ctf_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_design_sel_cat_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_design_sel_cat_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_design_sel_cat_delete&pm_id='.$pm_id;

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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_sel_cat";
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

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_sel_cat";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function elemsList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem($pm_title, 'elemsList();', false);

    $elems->init();
}

function fPlugin_Maker_Design_Selcat_Edit()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
    fCategs_Design_Sel_Cat_Edit('pl_plugin_'.$pm_id.'_design_sel_cat', 'pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id.'_design_sel_cat_edit_submit');
}

function fPlugin_Maker_Design_Selcat_Edit_Submit()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);

    fCategs_Design_Sel_Cat_Edit_Submit('pl_plugin_'.$pm_id.'_design_sel_cat', 'pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id.'_design_sel_cat_edit_submit', 'pl_plugin_'.$pm_id.'_sel_cat', 'pl_plugin_'.$pm_id);
}

function fPlugin_Maker_Design_Selcat_Delete()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
    fCategs_Design_Delete('pl_plugin_'.$pm_id.'_sel_cat', 'pl_plugin_'.$pm_id);
}

/**
 * Функции управления макетами дизайна формы фильтра
 *
 */

function fPlugin_Maker_Plugins_Design_Filter_Get($args)
{
	$pm_id = intval($_GET['pm_id']);

	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ptf_title'].'</a></b>
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

	$id = intval($args['ptf_id']);
	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_filter" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_filter" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');
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

function fPlugin_Maker_Plugins_Design_Filter()
{
	if (!isset($_GET['pm_id']))
        return;

	$pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
		return;

	list($pm_title) = $res[0];

	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
	$elems = new sbElements('sb_plugins_temps_form', 'ptf_id', 'ptf_title', 'fPlugin_Maker_Plugins_Design_Filter_Get', 'pl_plugin_'.$pm_id.'_design_filter&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_filter');
    $elems->mCategsDeleteWithElementsMenu = true;

	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->addField('ptf_title');      // название макета дизайна
	$elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_filter_form_32.png';

	$elems->addSorting(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_SORT_BY_TITLE, 'ptf_title');
	$elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_design_filter_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_design_filter_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_design_filter_delete&pm_id='.$pm_id;

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

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin'.$pm_id.'_filter";
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

			strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin'.$pm_id.'_filter";
			strAttr = "resizable=1,width=800,height=600";
			sbShowModalDialog(strPage, strAttr, null, window);
		}


		function elemsList()
		{
			window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
		}';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem($pm_title, 'elemsList();', false);

	$elems->init();
}

function fPlugin_Maker_Plugins_Design_Filter_Edit($htmlStr = '', $footerStr = '')
{
	if (!isset($_GET['pm_id']))
		return;

    $pm_id = intval($_GET['pm_id']);

    // проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_filter'))
		return;

	$res = sql_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_elems_settings) = $res[0];

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

	$elem_id = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD, $pm_id);
	$elem_id_lo = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_LO_FIELD, $pm_id);
	$elem_id_hi = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_HI_FIELD, $pm_id);

	$elem_title = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_TITLE_FIELD, $pm_id);

	$price1 = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_FIELD, $pm_id, '1');
	$price1_lo = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_LO_FIELD, $pm_id, '1');
	$price1_hi = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_HI_FIELD, $pm_id, '1');

	$price2 = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_FIELD, $pm_id, '2');
	$price2_lo = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_LO_FIELD, $pm_id, '2');
	$price2_hi = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_HI_FIELD, $pm_id, '2');

	$price3 = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_FIELD, $pm_id, '3');
	$price3_lo = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_LO_FIELD, $pm_id, '3');
	$price3_hi = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_HI_FIELD, $pm_id, '3');

	$price4 = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_FIELD, $pm_id, '4');
	$price4_lo = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_LO_FIELD, $pm_id, '4');
	$price4_hi = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_HI_FIELD, $pm_id, '4');

	$price5 = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_FIELD, $pm_id, '5');
	$price5_lo = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_LO_FIELD, $pm_id, '5');
	$price5_hi = sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_PRICE_HI_FIELD, $pm_id, '5');

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
		$result = sql_param_query('SELECT ptf_title, ptf_lang, ptf_form, ptf_fields_temps
									FROM sb_plugins_temps_form WHERE ptf_id=?d', $_GET['id']);
        if ($result)
        {
			list($ptf_title, $ptf_lang, $ptf_form, $ptf_fields_temps) = $result[0];
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

		if ($ptf_fields_temps != '')
            $ptf_fields_temps = unserialize($ptf_fields_temps);
        else
            $ptf_fields_temps = array();
        if(!isset($ptf_fields_temps['elem_sort_select']))
         	$ptf_fields_temps['elem_sort_select'] = sprintf(PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_SELECT_FIELD,$pm_id);
	}
	elseif (count($_POST) > 0)
	{
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
		$ptf_lang = SB_CMS_LANG;
		$ptf_title = $ptf_form = '';

		$ptf_fields_temps = array();
		$ptf_fields_temps['date_temps'] = '{DAY}.{MONTH}.{LONG_YEAR}';

		$ptf_fields_temps['elem_id'] = $elem_id;
		$ptf_fields_temps['elem_id_lo'] = $elem_id_lo;
		$ptf_fields_temps['elem_id_hi'] = $elem_id_hi;

		$ptf_fields_temps['elem_title'] = $elem_title;

		$ptf_fields_temps['p_price1'] = $price1;
		$ptf_fields_temps['p_price1_lo'] = $price1_lo;
		$ptf_fields_temps['p_price1_hi'] = $price1_hi;

		$ptf_fields_temps['p_price2'] = $price2;
		$ptf_fields_temps['p_price2_lo'] = $price2_lo;
		$ptf_fields_temps['p_price2_hi'] = $price2_hi;

		$ptf_fields_temps['p_price3'] = $price3;
		$ptf_fields_temps['p_price3_lo'] = $price3_lo;
		$ptf_fields_temps['p_price3_hi'] = $price3_hi;

		$ptf_fields_temps['p_price4'] = $price4;
		$ptf_fields_temps['p_price4_lo'] = $price4_lo;
		$ptf_fields_temps['p_price4_hi'] = $price4_hi;

		$ptf_fields_temps['p_price5'] = $price5;
		$ptf_fields_temps['p_price5_lo'] = $price5_lo;
		$ptf_fields_temps['p_price5_hi'] = $price5_hi;
		$ptf_fields_temps['elem_sort_select'] = sprintf(PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_SELECT_FIELD,$pm_id);

		$_GET['id'] = '';
	}

	echo '<script>
            function checkValues()
            {
				var el_title = sbGetE("ptf_title");
				if (el_title.value == "")
                {
                     alert("'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_design_filter_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');

	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);

	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ptf_title, 'ptf_title', '', 'style="width:97%;"', true));
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutTextarea($ptf_fields_temps['date_temps'], 'ptf_fields_temps[date_temps]', '', 'style="width:100%; height:70px;"');
	$fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
	$fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_DATE_FORMAT, $fld);

	$fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'ptf_lang');
	$fld->mSelOptions = array($ptf_lang);
	$fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG_LABEL.'</div>';
	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_LANG, $fld);

	$layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB2);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB2);

	$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));

	$layout->addField('', new sbLayoutDelim());

	$tags = array('{VALUE}');
	$values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

	// идентификаторов элемента (для полного совпадения)
	$fld = new sbLayoutTextarea($ptf_fields_temps['elem_id'], 'ptf_fields_temps[elem_id]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array($elem_id), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD_LABEL, $fld);

	// Начало интервала идентификаторов элементов
	$fld = new sbLayoutTextarea($ptf_fields_temps['elem_id_lo'], 'ptf_fields_temps[elem_id_lo]', '', 'style="width:100%; height:50px;"');
	$fld->mTags = array_merge(array($elem_id_lo), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD_LABEL.'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')</span>', $fld);

	// Конец интервала идентификаторов элементов
	$fld = new sbLayoutTextarea($ptf_fields_temps['elem_id_hi'], 'ptf_fields_temps[elem_id_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array($elem_id_hi), $tags);
	$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
	$layout->addField(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD_LABEL.'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')</span>', $fld);

	$layout->addField('', new sbLayoutDelim());

    // Название элемента
    $fld = new sbLayoutTextarea($ptf_fields_temps['elem_title'], 'ptf_fields_temps[elem_title]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array($elem_title), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_PLUGIN_MAKER_ADD_TITLE_COMMENT, $fld);

    $basket_tags = array();
	$basket_values = array();

	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') &&
        (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1) &&
		(isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 1 ||
		 isset($pm_elems_settings['price2_type']) && $pm_elems_settings['price2_type'] == 1 ||
		 isset($pm_elems_settings['price3_type']) && $pm_elems_settings['price3_type'] == 1 ||
		 isset($pm_elems_settings['price4_type']) && $pm_elems_settings['price4_type'] == 1 ||
		 isset($pm_elems_settings['price5_type']) && $pm_elems_settings['price5_type'] == 1))
	{
		// поля для интернет-магазина
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_DELIM.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $basket_tags = array('-');
		$basket_values = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_BASKET_TAG);

		// Цена 1
		if (isset($pm_elems_settings['price1_type']) && $pm_elems_settings['price1_type'] == 1)
	    {
	    	// Цена 1 (для полного совпадения)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price1']) ? $ptf_fields_temps['p_price1'] : '', 'ptf_fields_temps[p_price1]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price1), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')), $fld);

			// Цена 1 (начало интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price1_lo']) ? $ptf_fields_temps['p_price1_lo'] : '', 'ptf_fields_temps[p_price1_lo]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price1_lo), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')</span>', $fld);

			// Цена 1 (конец интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price1_hi']) ? $ptf_fields_temps['p_price1_hi'] : '', 'ptf_fields_temps[p_price1_hi]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price1_hi), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '' ? $pm_elems_settings['price1_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')</span>', $fld);

			$layout->addField('', new sbLayoutDelim());

			$basket_tags = array_merge($basket_tags, array('{PRICE1}', '{PRICE1_LO}', '{PRICE1_HI}'));

	    	if (isset($pm_elems_settings['price1_title']) && $pm_elems_settings['price1_title'] != '')
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price1_title']),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price1_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price1_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
			else
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '1')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price1']) ? $ptf_fields_temps['p_price1'] : '', 'ptf_fields_temps[p_price1]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price1_lo']) ? $ptf_fields_temps['p_price1_lo'] : '', 'ptf_fields_temps[p_price1_lo]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price1_hi']) ? $ptf_fields_temps['p_price1_hi'] : '', 'ptf_fields_temps[p_price1_hi]'));
	    }

		// Цена 2
		if (isset($pm_elems_settings['price2_type']) && $pm_elems_settings['price2_type'] == 1)
	    {
	    	// Цена 2 (для полного совпадения)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price2']) ? $ptf_fields_temps['p_price2'] : '', 'ptf_fields_temps[p_price2]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price2), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')), $fld);

			// Цена 2 (начало интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price2_lo']) ? $ptf_fields_temps['p_price2_lo'] : '', 'ptf_fields_temps[p_price2_lo]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price2_lo), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')</span>', $fld);

			// Цена 2 (конец интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price2_hi']) ? $ptf_fields_temps['p_price2_hi'] : '', 'ptf_fields_temps[p_price2_hi]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price2_hi), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '' ? $pm_elems_settings['price2_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')</span>', $fld);

			$layout->addField('', new sbLayoutDelim());

	    	$basket_tags = array_merge($basket_tags, array('{PRICE2}', '{PRICE2_LO}', '{PRICE2_HI}'));

	    	if (isset($pm_elems_settings['price2_title']) && $pm_elems_settings['price2_title'] != '')
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price2_title']),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price2_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price2_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
			else
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '2')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price2']) ? $ptf_fields_temps['p_price2'] : '', 'ptf_fields_temps[p_price2]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price2_lo']) ? $ptf_fields_temps['p_price2_lo'] : '', 'ptf_fields_temps[p_price2_lo]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price2_hi']) ? $ptf_fields_temps['p_price2_hi'] : '', 'ptf_fields_temps[p_price2_hi]'));
	    }

		// Цена 3
		if (isset($pm_elems_settings['price3_type']) && $pm_elems_settings['price3_type'] == 1)
	    {
	    	// Цена 3 (для полного совпадения)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price3']) ? $ptf_fields_temps['p_price3'] : '', 'ptf_fields_temps[p_price3]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price3), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')), $fld);

			// Цена 3 (начало интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price3_lo']) ? $ptf_fields_temps['p_price3_lo'] : '', 'ptf_fields_temps[p_price3_lo]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price3_lo), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')</span>', $fld);

			// Цена 3 (конец интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price3_hi']) ? $ptf_fields_temps['p_price3_hi'] : '', 'ptf_fields_temps[p_price3_hi]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price3_hi), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '' ? $pm_elems_settings['price3_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')</span>', $fld);

			$layout->addField('', new sbLayoutDelim());

	    	$basket_tags = array_merge($basket_tags, array('{PRICE3}', '{PRICE3_LO}', '{PRICE3_HI}'));

	    	if (isset($pm_elems_settings['price3_title']) && $pm_elems_settings['price3_title'] != '')
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price3_title']),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price3_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price3_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
			else
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '3')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price3']) ? $ptf_fields_temps['p_price3'] : '', 'ptf_fields_temps[p_price3]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price3_lo']) ? $ptf_fields_temps['p_price3_lo'] : '', 'ptf_fields_temps[p_price3_lo]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price3_hi']) ? $ptf_fields_temps['p_price3_hi'] : '', 'ptf_fields_temps[p_price3_hi]'));
	    }

		// Цена 4
		if (isset($pm_elems_settings['price4_type']) && $pm_elems_settings['price4_type'] == 1)
	    {
	    	// Цена 4 (для полного совпадения)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price4']) ? $ptf_fields_temps['p_price4'] : '', 'ptf_fields_temps[p_price4]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price4), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')), $fld);

			// Цена 4 (начало интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price4_lo']) ? $ptf_fields_temps['p_price4_lo'] : '', 'ptf_fields_temps[p_price4_lo]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price4_lo), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')</span>', $fld);

			// Цена 4 (конец интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price4_hi']) ? $ptf_fields_temps['p_price4_hi'] : '', 'ptf_fields_temps[p_price4_hi]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price4_hi), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '' ? $pm_elems_settings['price4_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')</span>', $fld);

			$layout->addField('', new sbLayoutDelim());

	    	$basket_tags = array_merge($basket_tags, array('{PRICE4}', '{PRICE4_LO}', '{PRICE4_HI}'));

	    	if (isset($pm_elems_settings['price4_title']) && $pm_elems_settings['price4_title'] != '')
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price4_title']),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price4_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price4_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
			else
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '4')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price4']) ? $ptf_fields_temps['p_price4'] : '', 'ptf_fields_temps[p_price4]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price4_lo']) ? $ptf_fields_temps['p_price4_lo'] : '', 'ptf_fields_temps[p_price4_lo]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price4_hi']) ? $ptf_fields_temps['p_price4_hi'] : '', 'ptf_fields_temps[p_price4_hi]'));
	    }

		// Цена 5
		if (isset($pm_elems_settings['price5_type']) && $pm_elems_settings['price5_type'] == 1)
	    {
	    	// Цена 5 (для полного совпадения)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price5']) ? $ptf_fields_temps['p_price5'] : '', 'ptf_fields_temps[p_price5]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price5), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')), $fld);

			// Цена 5 (начало интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price5_lo']) ? $ptf_fields_temps['p_price5_lo'] : '', 'ptf_fields_temps[p_price5_lo]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price5_lo), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')</span>', $fld);

			// Цена 5 (конец интервала)
			$fld = new sbLayoutTextarea(isset($ptf_fields_temps['p_price5_hi']) ? $ptf_fields_temps['p_price5_hi'] : '', 'ptf_fields_temps[p_price5_hi]', '', 'style="width:100%;height:50px;"');
			$fld->mTags = array_merge(array($price5_hi), $tags);
			$fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
			$layout->addField((isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '' ? $pm_elems_settings['price5_title'] : sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')).'<br /><span style="font-weight: normal;">('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')</span>', $fld);

			$layout->addField('', new sbLayoutDelim());

	    	$basket_tags = array_merge($basket_tags, array('{PRICE5}', '{PRICE5_LO}', '{PRICE5_HI}'));

	    	if (isset($pm_elems_settings['price5_title']) && $pm_elems_settings['price5_title'] != '')
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price5_title']),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price5_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, $pm_elems_settings['price5_title']).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
			else
			{
				$basket_values = array_merge($basket_values, array(
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')),
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')',
					sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_PRICE_FIELD, '5')).' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'
					));
			}
	    }
	    else
	    {
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price5']) ? $ptf_fields_temps['p_price5'] : '', 'ptf_fields_temps[p_price5]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price5_lo']) ? $ptf_fields_temps['p_price5_lo'] : '', 'ptf_fields_temps[p_price5_lo]'));
	    	$layout->addField('', new sbLayoutInput('hidden', isset($ptf_fields_temps['p_price5_hi']) ? $ptf_fields_temps['p_price5_hi'] : '', 'ptf_fields_temps[p_price5_hi]'));
	    }
	}
	 //  Поля сортировки
    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_DESIGN_FORM_SORT_FIELDS.'</div>';
	$layout->addField('', new sbLayoutHTML($html, true));
	$layout->addField('', new sbLayoutDelim());
	// Макет полей сортировки
    $fld = new sbLayoutTextarea($ptf_fields_temps['elem_sort_select'], 'ptf_fields_temps[elem_sort_select]', '', 'style="width:100%;height:50px;"');
    $flds_tags = array(sprintf(PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_SELECT_FIELD,$pm_id),
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_ID_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_ID_FIELD_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_TITLE_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_TITLE_FIELD_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_SORT_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_SORT_FIELD_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_ACTIVE_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_ACTIVE_FIELD_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_USER_ID_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_USER_ID_FIELD_DESC);
    if(!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    {
        $tmp = array(
            PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE1_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE1_FIELD_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE2_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE2_FIELD_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE3_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE3_FIELD_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE4_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE4_FIELD_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE5_FIELD_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_PRICE5_FIELD_DESC
        );
        $flds_tags = array_merge($flds_tags, $tmp);
    }

    $flds_vals = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_TITLE_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_SORT_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_SORT_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ACTIVE_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_ACTIVE_DESC,
    						PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_USER_ID_DESC);
    if(!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1)
    {
        $tmp = array(
            PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE1_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE1_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE2_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE2_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE3_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE3_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE4_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE4_DESC,
    		PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE5_ASC, PL_PLUGIN_MAKER_FILTER_FORM_EDIT_SORT_FIELDS_PRICE5_DESC
        );
        $flds_vals = array_merge($flds_vals, $tmp);
    }

    $layout->getPluginFieldsTagsSort($pm_id, $flds_tags, $flds_vals, 'option');

    $fld->mTags = $flds_tags;
   	$fld->mValues = $flds_vals;
   	$layout->addField(PL_PLUGIN_MAKER_DESIGN_FORM_SORT_SELECT_FIELDS, $fld);



	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
	if ($res)
    {
		list($pd_fields) = $res[0];
		if ($pd_fields != '')
		{
			$pd_fields = unserialize($pd_fields);
			if(isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
            {
				$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB2.'</div>';
				$layout->addField('', new sbLayoutHTML($html, true));
				$layout->addField('', new sbLayoutDelim());
            }

			// Пользовательские поля
			$layout->addPluginInputFieldsTemps('pl_plugin_'.$pm_id, $ptf_fields_temps, 'ptf_', '', array(), array(), false, false, 'p_f_'.$pm_id, '', true);
		}
	}

	$layout->addTab(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB3);
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB3);

	$user_tags = array();
	$user_tags_values = array();
	$layout->getPluginFieldsTags('pl_plugin_'.$pm_id, $user_tags, $user_tags_values, false, true, false, true);

	$fld = new sbLayoutTextarea($ptf_form, 'ptf_form', '', 'style="width:100%; height:400px;"');
	$fld->mTags = array_merge(array('-', sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HTML_FORM_TAG, $pm_id),
									'{ID}',
									'{ID_LO}',
									'{ID_HI}',
									'{TITLE}',
									'{SORT_SELECT}'), $basket_tags, $user_tags);

	$fld->mValues = array_merge(array(PL_PLUGIN_MAKER_PLUGINS_EDIT_TAB1,
									PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_FORM_TAG,
									sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD_TAG, ''),
									sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD_TAG, ' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_LO.')'),
									sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_ID_FIELD_TAG, ' ('.PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_HI.')'),
									sprintf(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_TAG, (isset($pm_elems_settings['title_field_title']) && $pm_elems_settings['title_field_title'] != '' ? $pm_elems_settings['title_field_title'] : PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TITLE_FIELD)),
									PL_PLUGIN_MAKER_FORM_SORT_SELECT_TAG_VALUE),
									$basket_values, $user_tags_values);

	$layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_FORM_EDIT_TAB3 , $fld);
	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));

	if ($_GET['id'] != '')
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'design_edit') ? '' : 'disabled="disabled"'));

	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fPlugin_Maker_Plugins_Design_Filter_Edit_Submit()
{
	if (!isset($_GET['pm_id']))
		return;

	$pm_id = intval($_GET['pm_id']);

	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_filter'))
		return;

	$res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	if (!$res)
		return;

	list($pm_title) = $res[0];

	if (!isset($_GET['id']))
        $_GET['id'] = '';

    $ptf_title = $ptf_form = '';
    $ptf_lang = SB_CMS_LANG;
    $ptf_fields_temps = array();

	extract($_POST);

	if ($ptf_title == '')
	{
		sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG, false, 'warning');
		fPlugin_Maker_Plugins_Design_Filter_Edit();
		return;
	}

	$row = array();
    $row['ptf_title'] = $ptf_title;
    $row['ptf_lang'] = $ptf_lang;
    $row['ptf_form'] = $ptf_form;
    $row['ptf_fields_temps'] = serialize($ptf_fields_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT ptf_title FROM sb_plugins_temps_form WHERE ptf_id=?d', $_GET['id']);
        if ($res)
        {
			// редактирование
			list($old_title) = $res[0];

			sql_param_query('UPDATE sb_plugins_temps_form SET ?a WHERE ptf_id=?d', $row, $_GET['id'], sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_SUBMIT_EDIT_OK, $old_title));

			$footer_ar = fCategs_Edit_Elem('design_edit');
			if (!$footer_ar)
			{
				sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
				sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_SUBMIT_ERR_EDIT, $old_title), SB_MSG_WARNING);

				fPlugin_Maker_Plugins_Design_Filter_Edit();
				return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['ptf_id'] = intval($_GET['id']);
            $html_str = fPlugin_Maker_Plugins_Design_Filter_Get($row);

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
				fPlugin_Maker_Plugins_Design_Filter_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_SUBMIT_ERR_EDIT, $ptf_title), SB_MSG_WARNING);

            fPlugin_Maker_Plugins_Design_Filter_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_plugins_temps_form SET ?a', $row))
        {
			$id = sql_insert_id();

			if (fCategs_Add_Elem($id, 'design_edit'))
            {
				sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_SUBMIT_ADD_OK, $ptf_title));
				echo '<script>
                        sbReturnValue('.$id.');
					</script>';
				$error = false;
            }
            else
			{
				sql_query('DELETE FROM sb_plugins_temps_form WHERE ptf_id="'.$id.'"');
            }
        }

        if ($error)
        {
			sb_show_message(sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_SUBMIT_ADD_ERROR, $ptf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_DESIGN_FILTER_EDIT_SUBMIT_ADD_SYS_ERROR, $ptf_title), SB_MSG_WARNING);

			fPlugin_Maker_Plugins_Design_Filter_Edit();
			return;
		}
	}
}

function fPlugin_Maker_Plugins_Design_Filter_Delete()
{
	$pm_id = intval($_GET['pm_id']);

	$id = intval($_GET['id']);
	$pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
						WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_filter" AND pages.p_id=elems.e_p_id LIMIT 1');

	$temps = false;
	if (!$pages)
	{
		$temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
						WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_filter" AND temps.t_id=elems.e_p_id LIMIT 1');
	}

	if ($pages || $temps)
	{
		echo PL_PLUGIN_MAKER_PLUGINS_DESIGN_DELETE_ERROR;
	}
}

/**
 * Вывод информера
 */
function fPlugin_Maker_Informer_Design_Get($args)
{
	$pm_id = intval($_GET['pm_id']);

	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['ptf_title'].'</a></b>
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

	$id = intval($args['ptf_id']);
	$pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
	                    WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_informer" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

	$temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
	                    WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_informer" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

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

function fPlugin_Maker_Informer_Design_Init()
{
	if (!isset($_GET['pm_id']))
		return;

	$pm_id = intval($_GET['pm_id']);

	$res = sql_query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');

	if (!$res)
		return;

	list($pm_title) = $res[0];

	require_once(SB_CMS_LIB_PATH . '/sbElements.inc.php');
	$elems = new sbElements('sb_plugins_temps_form', 'ptf_id', 'ptf_title', 'fPlugin_Maker_Informer_Design_Get', 'pl_plugin_'.$pm_id.'_informer_init&pm_id='.$pm_id, 'pl_plugin_'.$pm_id.'_maker_informer');
    $elems->mCategsDeleteWithElementsMenu = true;
	$elems->addField('ptf_id');
	$elems->addField('ptf_title');

	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_default_informer_32.png';

    $elems->mElemsEditEvent =  'pl_plugin_'.$pm_id.'_informer_edit&pm_id='.$pm_id;
    $elems->mElemsEditDlgWidth = 800;
	$elems->mElemsEditDlgHeight = 660;

	$elems->mElemsAddEvent =  'pl_plugin_'.$pm_id.'_informer_edit&pm_id='.$pm_id;
    $elems->mElemsAddDlgWidth = 800;
	$elems->mElemsAddDlgHeight = 660;

	$elems->mElemsDeleteEvent = 'pl_plugin_'.$pm_id.'_informer_delete&pm_id='.$pm_id;

	$elems->mElemsUseLinks = false;
	$elems->mCategsPasteElemsMenu = 'all';
	$elems->mCategsClosed = false;

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

				strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_informer";
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

				strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_plugin_'.$pm_id.'_informer";
				strAttr = "resizable=1,width=800,height=600";
				sbShowModalDialog(strPage, strAttr, null, window);
			}

			function elemsList()
			{
				window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_plugin_'.$pm_id.'_init&pm_id='.$pm_id.'";
			}';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem($pm_title, 'elemsList();', false);
	$elems->init();
}

function fPlugin_Maker_Informer_Design_Edit($htmlStr = '', $footerStr = '')
{
	if (!isset($_GET['pm_id']))
		return;

    $pm_id = intval($_GET['pm_id']);

	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_maker_informer'))
		return;

    if(count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
	{
		$res = sql_param_query('SELECT ptf_id, ptf_title, ptf_form, ptf_messages FROM sb_plugins_temps_form WHERE ptf_id=?d', intval($_GET['id']));
		if(!$res)
		{
            sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_ERROR, false, 'warning');
            return;
		}
		list($ptf_id, $ptf_title, $ptf_form, $ptf_messages) = $res[0];
		$ptf_messages = unserialize($ptf_messages);
	}
	elseif(count($_POST) > 0)
	{
		extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
	{
		$ptf_messages = array();
		$ptf_messages['no_elems'] = PL_PLUGIN_MAKER_INFORMER_EDIT_NO_ELEMS_DEFAULT;

		$ptf_title = $ptf_form = '';
		$_GET['id'] = '';
	}

	echo '<script>
	            function checkValues()
	            {
	                var el_title = sbGetE("ptf_title");
	                if (el_title.value == "")
	                {
	                     alert("'.PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG.'");
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

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_informer_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTitleWidth = '160';
    $layout->mTableWidth = '95%';

	echo '<br /><br />';
	$layout->addHeader(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1);
    $layout->addField(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $ptf_title, 'ptf_title', '', 'style="width:100%;"'));

	$layout->addField('', new sbLayoutDelim());

	//	Вывод миникорзины
	$fld = new sbLayoutTextarea($ptf_form, 'ptf_form', '', 'style="width:100%;height:300px;"');
	$fld->mTags = array('-', '{COUNT_ELEMS}', PL_PLUGIN_MAKER_INFORMER_EDIT_COMPARE_LINK_VAL, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DEL_COMPARE_LINK_TAG_VAL);
	$fld->mValues = array(PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_TAB1, PL_PLUGIN_MAKER_INFORMER_EDIT_EL_COUNT, PL_PLUGIN_MAKER_INFORMER_EDIT_COMPARE_LINK, PL_PLUGIN_MAKER_PLUGINS_DESIGN_EDIT_DEL_COMPARE_LINK_TAG);
	$layout->addField(PL_PLUGIN_MAKER_INFORMER_EDIT_TEMP, $fld);
	$layout->addField('', new sbLayoutDelim());

//  Сообщение "Нет товаров для сравнения"
    $layout->addField(PL_PLUGIN_MAKER_INFORMER_EDIT_NO_ELEMS, new sbLayoutTextarea($ptf_messages['no_elems'], 'ptf_messages[no_elems]', '', 'style="width:100%;height:70px;"'));

	$layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id, 'elems_edit') ? '' : 'disabled="disabled"'));
	if ($_GET['id'] != '')
		$layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_plugin_'.$pm_id , 'elems_edit') ? '' : 'disabled="disabled"'));

	$layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
	$layout->show();
}

function fPlugin_Maker_Informer_Design_Submit()
{
	if (!isset($_GET['pm_id']))
		return;

	$pm_id = intval($_GET['pm_id']);

	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id.'_maker_informer'))
		return;

	if (!isset($_GET['id']))
		$_GET['id'] = '';

	$ptf_form = '';
	$ptf_messages = array();

	extract($_POST);

	if($ptf_title == '')
	{
		sb_show_message(PL_PLUGIN_MAKER_PLUGINS_DESIGN_NO_TITLE_MSG, false, 'warning');
		fPlugin_Maker_Informer_Design_Edit();
		return;
	}

	$row = array();
	$row['ptf_title'] = $ptf_title;
	$row['ptf_lang'] = '';
	$row['ptf_form'] = $ptf_form;
	$row['ptf_fields_temps'] = '';
	$row['ptf_categs_temps'] = '';
	$row['ptf_messages'] = serialize($ptf_messages);

	if ($_GET['id'] != '')
	{
	    sql_param_query('UPDATE sb_plugins_temps_form SET ?a WHERE ptf_id=?d', $row, $_GET['id']);

        $footer_ar = fCategs_Edit_Elem();
	    if (!$footer_ar)
	    {
	        sb_show_message(sprintf(PL_PLUGIN_MAKER_INFORMER_SUBMIT_SYSTEMLOG_ERROR, $ptf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_INFORMER_SUBMIT_SYSTEMLOG_ERROR, $ptf_title), SB_MSG_WARNING);
            fPlugin_Maker_Informer_Design_Edit();
		    return;
	    }

		$footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
		$footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

		$row['ptf_id'] = $_GET['id'];

        $html_str = fPlugin_Maker_Informer_Design_Get($row);
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
			fPlugin_Maker_Informer_Design_Edit($html_str, $footer_str);
        }
	}
	else
	{
	    $error = true;

	    if (sql_param_query('INSERT INTO sb_plugins_temps_form SET ?a', $row))
	    {
			$id = sql_insert_id();

    		if (fCategs_Add_Elem($id))
    		{
				sb_add_system_message(sprintf(PL_PLUGIN_MAKER_INFORMER_SUBMIT_OK, $ptf_title));
       			echo '<script>
        				 sbReturnValue('.$id.');
        			    </script>';

        		$error = false;
    		}
    		else
    		{
				sql_query('DELETE FROM sb_plugins_temps_form WHERE ptf_id="'.$id.'"');
    		}
	    }

	    if ($error)
	    {
	        sb_show_message(sprintf(PL_PLUGIN_MAKER_INFORMER_SUBMIT_ADD_SYSTEMLOG_ERROR, $ptf_title), false, 'warning');
            sb_add_system_message(sprintf(PL_PLUGIN_MAKER_INFORMER_SUBMIT_ADD_SYSTEMLOG_ERROR, $ptf_title), SB_MSG_WARNING);

            fPlugin_Maker_Informer_Design_Edit();
		    return;
	    }
	}
}

function fPlugin_Maker_Informer_Design_Delete()
{
	$pm_id = intval($_GET['pm_id']);

	$id = intval($_GET['id']);
	$pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
					WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_informer" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
					WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_plugin_'.$pm_id.'_informer" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
		echo PL_PLUGIN_MAKER_PLUGINS_DESIGN_DELETE_ERROR;
    }
}

//редактирование товаров заказа
function fPlugin_Maker_Goods_Edit()
{
    if (!isset($_GET['pm_id']) || !isset($_GET['elem_id']))
		return;

    $pm_id = intval($_GET['pm_id']);
    $elem_id = intval($_GET['elem_id']);

    $result = sql_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $elem_id);

    if(!$result || ($p_order = $result[0][0]) == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_NO_ORDERS_MSG, true, 'information');
        return;
    }


    $xml = simplexml_load_string($p_order);

    //Проверяем наличие уникальных маркеров. Если их нет, то добавляем
    $goods = $xml->xpath('good');
    $need_save = false;
    foreach($goods as $good)
    {
        $unique_id = $good->xpath('@unique');
        if(empty($unique_id))
        {
            $good->addAttribute('unique', uniqid());
            $need_save = true;
        }
    }

    if($need_save)
    {
        sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_order=?s WHERE p_id=?d', $xml->asXML(), $elem_id);
    }

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $confirm_msg = PL_PLUGIN_MAKER_DELETE_GOODS_CONFIRM;

    echo <<<EOF
    <script type="text/javascript">
    function deleteItem(elem)
    {
        if(elem.checked && !confirm('$confirm_msg'))
        {
            elem.checked = false;
        }
    }

    function hidePrices(id)
    {
        var elem = sbGetE('recalc_price_'+id);
        if(elem.checked)
        {
            var hide = true;
        }
        else
        {
            var hide = false;
        }

        var inputs = document.getElementsByTagName("input");
        var reg = new RegExp('^.+_'+id+'$');
        for(var i in inputs)
        {
            if(reg.test(inputs[i].name) && inputs[i].getAttribute('recalc') == 'recalc')
            {
                inputs[i].disabled = hide;
            }
        }
    }

    </script>
EOF;

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_edit_list_submit&pm_id='.$pm_id.'&elem_id='.$elem_id.'&cat_id='.$_GET['cat_id'], 'thisDialog', 'post', '', 'main', 'enctype="multipart/form-data" style="margin-top: 15px;"');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $items_count = count($xml->xpath('good'));

    if($items_count == 0)
    {
        sb_show_message(PL_PLUGIN_MAKER_NO_ORDERS_MSG, true, 'information');
        return;
    }

    //Выбираем ID модулей, товары которых есть в заказе
    $p_ids = $xml->xpath('good/@pm_id');
    $tmp = array();
    foreach($p_ids as $p_id)
    {
        if(!in_array((string)$p_id, $tmp))
        {
            $tmp[] = (string)$p_id;
        }
    }
    $p_ids = $tmp;

    $show_price = array();

    $res = sql_query('SELECT pm_id, pm_elems_settings FROM sb_plugins_maker WHERE pm_id IN (?a)', $p_ids);
    if (!$res)
        return;

    $max_num_price = 0;
    foreach($res as $row)
    {
        list($p_id, $pm_elems_settings) = $row;

        if ($pm_elems_settings != '')
        {
            $pm_elems_settings = unserialize($pm_elems_settings);

            $show_price[$p_id] = array();
            for($i=1; $i<=5; $i++)
            {
                $show_price[$p_id]['show_price'.$i] = isset($pm_elems_settings['show_price'.$i]) && $pm_elems_settings['show_price'.$i] == 1;
                $show_price[$p_id]['price'.$i.'_type'] = isset($pm_elems_settings['price'.$i.'_type']) && $pm_elems_settings['price'.$i.'_type'] == 1;
                if(isset($pm_elems_settings['price'.$i.'_title']) && $pm_elems_settings['price'.$i.'_title'] != '')
                {
                    $show_price[$p_id]['price'.$i.'_title'] = $pm_elems_settings['price'.$i.'_title'];
                }
                else
                {
                    $show_price[$p_id]['price'.$i.'_title'] = sprintf(PL_PLUGIN_MAKER_EDIT_ELEMS_PRICE_TITLE_DEFAULT, $i);
                }

                if($show_price[$p_id]['show_price'.$i] && $max_num_price < $i)
                {
                    $max_num_price = $i;
                }
            }
        }
    }

    for($i=1; $i<=$items_count; $i++)
    {
        //Идентификатор товара
        $p_id = $xml->xpath("good[$i]/@id");
        $p_id = (string)$p_id[0];

        //Идентификатор плагина
        $pl_id =  $xml->xpath("good[$i]/@pm_id");
        $pl_id = (string)$pl_id[0];

        //Уникальный маркер товара в заказе
        $unique = $xml->xpath("good[$i]/@unique");
        $unique = (string)$unique[0];

        $title = $xml->xpath("good[$i]/p_title");
        $title = (string)$title[0];

        $layout->addTab(sb_wordwrap($title, 30));
        $layout->addHeader($title);

        //Количество товара
        $count = $xml->xpath("good[$i]/p_count");
        $fld = new sbLayoutInput('text', intval((string)$count[0]), 'spin_count_'.$p_id.'_'.$unique, 'spin_count_'.$p_id.'_'.$unique, 'style="width:50px;"');
        $fld->mMinValue = 1;
        $layout->addField(PL_PLUGIN_MAKER_EDIT_GOODS_COUNT, $fld);

        //Цены
        $recalc_prices_showed = false;
        for ($j = 1; $j <= $max_num_price; $j++)
        {
            if ($show_price[$pl_id]['show_price'.$j])
            {
                $price = $xml->xpath("good[$i]/p_price$j");
                $recalc = (isset($show_price[$pl_id]['price'.$j.'_type']) && $show_price[$pl_id]['price'.$j.'_type'] == 0)? 'recalc="recalc"' : '';
                $fld = new sbLayoutInput('text', floatval((string) $price[0]), 'spin_price' . $j . '_' . $p_id.'_'.$unique, 'spin_price' . $j . '_' . $p_id.'_'.$unique, $recalc.' style="width:80px;"');
                $fld->mMinValue = 0;
                $fld->mIncrement = 0.01;
                $layout->addField($show_price[$pl_id]['price'.$j.'_title'], $fld);
            }
        }

        if(!$recalc_prices_showed && $max_num_price > 0)
        {
            $fld = new sbLayoutInput('checkbox', '1', 'recalc_price_'.$p_id.'_'.$unique, '', 'onclick="hidePrices(\''.$p_id.'_'.$unique.'\')"');
            $layout->addField(PL_PLUGIN_MAKER_EDIT_GOODS_PRICE, $fld);
        }

        //Свойства товара
        $settings = $xml->xpath("good[$i]/*[@type='multiselect_sprav']");
        if(count($settings) > 0)
        {
            foreach($settings as $setting)
            {
                //выбираем свойства юзер-поля
                $attr = $setting->attributes();
                $tag = $setting->getName();
                $name = (string)$attr['name'];
                $sprav_ids = (string)$attr['sprav_ids'];
                $subcategs = (int)$attr['subcategs'];
                $cat_ids = array($sprav_ids);
                if($subcategs == 1)
                {
                    //Выберем подразделы
                    $res = sql_param_assoc('SELECT cat_left, cat_right, cat_level FROM sb_categs WHERE cat_id=?d', $sprav_ids);
                    if($res)
                    {
                        $res1 = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_sprav" AND cat_left > ?d AND cat_right < ?d AND cat_level > ?d', $res[0]['cat_left'], $res[0]['cat_right'], $res[0]['cat_level']);
                        if($res1)
                        {
                            foreach($res1 as $row)
                            {
                                $cat_ids[] = $row[0];
                            }
                        }
                    }
                }

                //вытаскиваем установленные значения справочника для данного элемента
                $res = sql_param_query('SELECT ?# FROM sb_plugins_'.$pl_id.' WHERE p_id=?d', $tag, $p_id);

                $f_name = $tag.'_'.$p_id.'_'.$unique;
                $fld = new sbLayoutSpravData('select', implode(',', $cat_ids), (string)$setting, $f_name);
                if($res && $res[0][0] != '')
                {
                    $fld->mAllowItems = explode(',', $res[0][0]);
                }

                $layout->addField($name, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
            }
        }

        //Удалить товар
        $fld = new sbLayoutInput('checkbox', '1', 'delete_item_'.$p_id.'_'.$unique, '', 'onclick="deleteItem(this)"');
        $layout->addField(KERNEL_DELETE, $fld);
    }


    $layout->addButton('submit', KERNEL_SAVE, 'btn_save');
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');
    $layout->show();
}


function fPlugin_Maker_Goods_Edit_Submit()
{
    $pm_id = $_GET['pm_id'];
    $p_id = (isset($_GET['elem_id']) && $_GET['elem_id'] != '')? intval($_GET['elem_id']) : 0;

    if (!fCategs_Check_Elems_Rights($p_id, $_GET['cat_id'], 'pl_plugin_'.$pm_id))
	{
		return;
	}

    //Удаляем помеченные товары
    $delete_items = array();
    foreach($_POST as $param=>$val)
    {
        if(preg_match('/delete_item_\d/', $param))
        {
            $id = preg_replace('/delete_item_(\d+)_[a-z0-9]+$/', '$1', $param);
            $unique_id = preg_replace('/delete_item_\d+_([a-z0-9]+)$/', '$1', $param);
            $delete_items[$unique_id] = intval($id);
        }
    }

    if(!empty($delete_items) && ($res = fPlugin_Maker_Goods_Delete($pm_id, $p_id, $delete_items)) != true)
    {
        sb_show_message($res, true, 'warning');
        return;
    }

    //Удаляем из поста все, что связано с удаляемым товаром
    if (!empty($delete_items))
    {
        foreach ($_POST as $param => $val)
        {
            $id = preg_replace('/^.+_(\d+)_\d+/', '$1', $param);
            $pl_id = preg_replace('/^.+_\d+_(\d+)$/', '$1', $param);
            if (isset($delete_items[intval($pl_id)]) && $delete_items[intval($pl_id)] == intval($id))
            {
                unset($_POST[$param]);
            }
        }
    }

    $result = sql_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_id);

    if(!$result || ($p_order = $result[0][0]) == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_PLUGINS_EDIT_ERROR, true, 'warning');
        return;
    }

    $pm_elems_settings = '';

    $result = sql_param_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);
    if ($result)
    {
        list($pm_elems_settings) = $result[0];
    }

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    $xml = $p_order;

    if (isset($pm_elems_settings['order_edit_php']) && trim($pm_elems_settings['order_edit_php']) != '')
    {
        eval($pm_elems_settings['order_edit_php']);
    }

    $xml = simplexml_load_string($xml);

    //Выбираем ID модулей, товары которых есть в заказе
    $p_ids = $xml->xpath('good/@pm_id');
    $tmp = array();
    foreach($p_ids as $pid)
    {
        if(!in_array((string)$pid, $tmp))
        {
            $tmp[] = (string)$pid;
        }
    }
    $p_ids = $tmp;

    if(empty($p_ids))
    {
        echo '<script>sbReturnValue("refresh");</script>';
        return;
    }

    $res = sql_query('SELECT pm_id, pm_elems_settings FROM sb_plugins_maker WHERE pm_id IN (?a)', $p_ids);
    if (!$res)
        return;

    $show_price = array();

    foreach($res as $row)
    {
        list($pid, $pm_elems_settings) = $row;

        if ($pm_elems_settings != '')
        {
            $pm_elems_settings = unserialize($pm_elems_settings);

            $show_price[$pid] = array();
            for($i=1; $i<=5; $i++)
            {
                $show_price[$pid]['price'.$i.'_type'] = isset($pm_elems_settings['price'.$i.'_type']) && $pm_elems_settings['price'.$i.'_type'] == 0;
                $show_price[$pid]['price'.$i.'_formula'] = isset($pm_elems_settings['price'.$i.'_formula'])? $pm_elems_settings['price'.$i.'_formula'] : '$GLOBALS["sb_value"] = null;';
            }
        }
    }

    //вытаскиваем цены из xml и из переданных данных
    $p_prices = array();
    $old_prices = array(); // запомним цены из xml
    $discounts = array(); // размеры скидок/наценок из xml для пересчета цен
    $tmp = $xml->xpath('good[@id]');
    foreach($tmp as $elem)
    {
        $old_prices[(int)$elem['id'].'_'.(string)$elem['unique']] = array(
            'p_price1' => (string)($elem->p_price1),
            'p_price2' => (string)($elem->p_price2),
            'p_price3' => (string)($elem->p_price3),
            'p_price4' => (string)($elem->p_price4),
            'p_price5' => (string)($elem->p_price5),
        );

        $discounts[(int)$elem['id'].'_'.(string)$elem['unique']] = (string)$elem->b_discount;

        $p_prices[(int)$elem['id'].'_'.(string)$elem['unique']] = array(
            'p_price1' => isset($_POST['spin_price1_'.(int)$elem['id'].'_'.(string)$elem['unique']]) ? $_POST['spin_price1_'.(int)$elem['id'].'_'.(string)$elem['unique']] : (string)($elem->p_price1),
            'p_price2' => isset($_POST['spin_price2_'.(int)$elem['id'].'_'.(string)$elem['unique']]) ? $_POST['spin_price2_'.(int)$elem['id'].'_'.(string)$elem['unique']] : (string)($elem->p_price2),
            'p_price3' => isset($_POST['spin_price3_'.(int)$elem['id'].'_'.(string)$elem['unique']]) ? $_POST['spin_price3_'.(int)$elem['id'].'_'.(string)$elem['unique']] : (string)($elem->p_price3),
            'p_price4' => isset($_POST['spin_price4_'.(int)$elem['id'].'_'.(string)$elem['unique']]) ? $_POST['spin_price4_'.(int)$elem['id'].'_'.(string)$elem['unique']] : (string)($elem->p_price4),
            'p_price5' => isset($_POST['spin_price5_'.(int)$elem['id'].'_'.(string)$elem['unique']]) ? $_POST['spin_price5_'.(int)$elem['id'].'_'.(string)$elem['unique']] : (string)($elem->p_price5),
        );
    }

    // получаем курсы валют ЦБ
    require_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';

    foreach($_POST as $param => $val)
    {
        $param = sb_str_replace('spin_', '', $param);
        $tag = explode('_', $param);
        if($tag[0] == 'count')
        {
            $obj = $xml->xpath('good[@unique="'.$tag[2].'"]');
            if($val == 0)
            {
                $val = 1;
            }
            $obj[0]->p_count = $val;
            continue;
        }

        if(preg_match('/price\d/', $tag[0]))
        {
            //Идентификатор плагина
            $pl_id =  $xml->xpath('good[@unique="'.$tag[2].'"]/@pm_id');
            $pl_id = !empty($pl_id[0]) ? (string)$pl_id[0] : ''; //TODO Проверить на пустоту

			if ($pl_id != '') {
				$p_price1 = $p_prices[$tag[1] . '_' . $tag[2]]['p_price1'] != $old_prices[$tag[1] . '_' . $tag[2]]['p_price1'] ? $p_prices[$tag[1] . '_' . $tag[2]]['p_price1'] : $old_prices[$tag[1] . '_' . $tag[2]]['p_price1'];
				$p_price2 = $p_prices[$tag[1] . '_' . $tag[2]]['p_price2'] != $old_prices[$tag[1] . '_' . $tag[2]]['p_price2'] ? $p_prices[$tag[1] . '_' . $tag[2]]['p_price2'] : $old_prices[$tag[1] . '_' . $tag[2]]['p_price2'];
				$p_price3 = $p_prices[$tag[1] . '_' . $tag[2]]['p_price3'] != $old_prices[$tag[1] . '_' . $tag[2]]['p_price3'] ? $p_prices[$tag[1] . '_' . $tag[2]]['p_price3'] : $old_prices[$tag[1] . '_' . $tag[2]]['p_price3'];
				$p_price4 = $p_prices[$tag[1] . '_' . $tag[2]]['p_price4'] != $old_prices[$tag[1] . '_' . $tag[2]]['p_price4'] ? $p_prices[$tag[1] . '_' . $tag[2]]['p_price4'] : $old_prices[$tag[1] . '_' . $tag[2]]['p_price4'];
				$p_price5 = $p_prices[$tag[1] . '_' . $tag[2]]['p_price5'] != $old_prices[$tag[1] . '_' . $tag[2]]['p_price5'] ? $p_prices[$tag[1] . '_' . $tag[2]]['p_price5'] : $old_prices[$tag[1] . '_' . $tag[2]]['p_price5'];

				$price_id = sb_str_replace('price', '', $tag[0]);
				$price_name = 'p_' . $tag[0];
				$$price_name = $val;

				if ($show_price[$pl_id]['price' . $price_id . '_type'] && isset($_POST['recalc_price_' . $tag[1] . '_' . $tag[2]]) && $_POST['recalc_price_' . $tag[1] . '_' . $tag[2]] == 1) {
					//	рассчитываем цену
					$$price_name = fPlugin_Maker_Quoting($pl_id, $tag[1], $show_price[$pl_id]['price' . $price_id . '_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $discounts[$tag[1] . '_' . $tag[2]]);
				}

				$obj = $xml -> xpath('good[@unique="' . $tag[2] . '"]');
				$obj[0] -> $price_name = $$price_name;
				continue;
			}
		}

		if(preg_match('/user_f_\d+/', $param))
        {
            $uid = preg_replace('/user_f_\d+_\d+_([a-z0-9]+)$/', '$1', $param);
            $param_name = preg_replace('/(user_f_\d+)_\d+_[a-z0-9]+$/', '$1', $param);
            $obj = $xml->xpath('good[@unique="'.$uid.'"]');
            $obj[0]->$param_name = $val;
            continue;
        }
    }

    $new_xml = $xml->asXML();

    $res = sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_order=?s WHERE p_id = ?d', $new_xml, $p_id);

    echo '<script>sbReturnValue("refresh");</script>';
}

function fPlugin_Maker_Goods_Reload()
{
    $pm_id = $_GET['pm_id'];

    if (!fCategs_Check_Elems_Rights((isset($_GET['elem_id']) && $_GET['elem_id'] != '' ? $_GET['elem_id'] : 0), $_GET['cat_id'], 'pl_plugin_'.$pm_id))
	{
		return;
	}


    $result = sql_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $_GET['elem_id']);

    if(!$result || ($p_order = $result[0][0]) == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_PLUGINS_EDIT_ERROR, true, 'warning');
        return;
    }

    $res = sql_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);
    if (!$res)
        return;

    list($pm_elems_settings) = $res[0];

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
	{
		$res_html = fPlugin_Maker_Get_Orders_List($pm_elems_settings, $p_order);

		if(!$res_html)
		{
			$res_html = sb_show_message(PL_PLUGIN_MAKER_NO_ORDERS_MSG, true, 'information', true);
		}
		else
		{
			if(!isset($pm_elems_settings['basket_temp']))
			{
				$pm_elems_settings['basket_temp'] = '{ORDER_LIST}';
			}

			$str = sb_str_replace(array('{ORDER_LIST}', '{COUNT_POSITIONS}', '{COUNT_GOODS}', '{TOVAR_SUM}', '{TOVAR_SUM_DISCOUNT}'),
					array($res_html['order_list'], $res_html['count_pos'], $res_html['count_goods'], $res_html['tovar_sum'], $res_html['tovar_sum_discount']),
					$pm_elems_settings['basket_temp']);

			//чистим код от инъекций
			$str = sb_clean_string($str);

			ob_start();
			eval(' ?>'.$str.'<?php ');

			$res_html = ob_get_clean();
		}

        echo '<table width="95%" cellspacing="0" cellpadding="5" class="form" align="center"><tbody><tr>
        			  <td width="100%" colspan="2">'.$res_html.'</td></tr></tbody></table>';
	}
}

/**
 * Удаление товара из заказа
 * @param int $pm_id   ID модуля
 * @param int $p_id    ID заказа
 * @param array $ids   ID товара
 * @return boolean
 */
function fPlugin_Maker_Goods_Delete($pm_id, $p_id, $ids=array())
{

    if(!is_array($ids))
    {
        $ids = array($ids);
    }

    $result = sql_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $p_id);

    if(!$result || ($p_order = $result[0][0]) == '')
    {
        return PL_PLUGIN_MAKER_PLUGINS_EDIT_ERROR;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($p_order);
    $dom->normalizeDocument();
    $goods = $dom->documentElement;

    $elems = $goods->getElementsByTagName('good');

    if (count($elems) > 0)
    {
        foreach($ids as $unique=>$id)
        {
            foreach ($elems as $elem)
            {
                $key = $elem->getAttribute('unique');
                $id1 = $elem->getAttribute('id');
                if ($key == $unique && $id1 == $id)
                {
                    $goods->removeChild($elem);
                    break;
                }
            }
        }



        //удаляем лишние данные о разделах
        $cats = $goods->getElementsByTagName('cat');
        $i = 0;
        $cat_ids_delete = array();
        foreach($cats as $cat)
        {
            $need_remove = true;
            $cat_id = $cat->getElementsByTagName('cat_id')->item(0)->nodeValue;
            if($elems->length > 0)
            {
                foreach($elems as $elem)
                {
                    $elem_cat_id = $elem->getAttribute('cat_id');
                    if($elem_cat_id == $cat_id)
                    {
                        $need_remove = false;
                        break;
                    }
                }
            }

            if($need_remove)
            {
                //$goods->removeChild($cats->item($i));
                $cat_ids_delete[] = $i;
                continue;
            }
            $i++;
        }

        if(count($cat_ids_delete) > 0)
        {
            foreach ($cat_ids_delete as $val)
            {
                $goods->removeChild($cats->item($val));
            }
        }
    }

    sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_order=?s WHERE p_id=?d', $dom->saveXML(), $p_id );
    return true;
}

function fPlugin_Maker_Goods_Add()
{
    if (!isset($_GET['pm_id']) || !isset($_GET['elem_id']))
		return;

    $pm_id = intval($_GET['pm_id']);
    $elem_id = intval($_GET['elem_id']);
    $cat_id = intval($_GET['cat_id']);
    $empty_file = SB_CMS_EMPTY_FILE;

    echo <<<EOF
        <script type="text/javascript">
            function selectElem(el_id, pl_id, pm_id)
            {
                var module = sbGetE('g_module');
                if(module)
                {
                    pm_id = /^pl_plugin_/.test(module.value)? '&pm_id='+module.value.replace('pl_plugin_','') : '';
                    sbGetElement(el_id, module.value, pm_id);
                }
            }

            function showSettings()
            {
                sbGetE("btn_save").disabled = false;
                var el_id = sbModalDialog.returnValue.id;
                var pl_ident = sbGetE('g_module').value;

                var res = sbLoadSync("$empty_file?event=pl_plugin_{$pm_id}_good_settings&elem_id="+el_id+"&pl_ident="+pl_ident);

                if(typeof sbTabsAr != 'undefined' && typeof sbTabsAr[0].tabItems != 'undefined' && res != '')
                {
                    var tab = sbGetE(sbTabsAr[0].tabItems[1].id);
                    tab.style.display = 'block';
                    var content = sbGetE("sb_tab1");
                    content.innerHTML = res;
                }
                else if(res == '')
                {
                    var tab = sbGetE(sbTabsAr[0].tabItems[1].id);
                    tab.style.display = 'none';
                }

            }

            function clearElement()
            {
                sbGetE("g_elem").value = '';
                sbGetE("g_elem_title").value = '';
                var tab = sbGetE(sbTabsAr[0].tabItems[1].id);
                tab.style.display = 'none';
                sbGetE('btn_save').disabled = true;
            }
        </script>
EOF;

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_plugin_'.$pm_id.'_add_goods_submit&pm_id='.$pm_id.'&elem_id='.$elem_id.'&cat_id='.$cat_id, 'thisDialog', 'post', '', 'main', 'enctype="multipart/form-data" style="margin-top: 15px;"');

    $layout->addTab(PL_PLUGIN_MAKER_TAB_GOOD);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ADD_GOOD);

    //Выбор модуля
    $res = sql_param_assoc('SELECT pm_id, pm_title FROM sb_plugins_maker WHERE pm_elems_settings REGEXP "show_price[[:digit:]]{1}\";s:1:\"1\""');

    if(!$res)
    {
        sb_show_message(PL_PLUGIN_MAKER_NO_MODULES_MSG, true, 'warning');
        return;
    }

    $modules = array();
    foreach($res as $row)
    {
        //$modules[$row['pm_id']] = $row['pm_title'];
        foreach($_SESSION['sbPlugins']->mRubrikator as $key=>$module)
        {
            if($module['title'] == $row['pm_title'] && $_SESSION['sbPlugins']->checkForUser($key))
            {
                $modules[$key] = $row['pm_title'];
                break;
            }
        }
    }

    $fld = new sbLayoutSelect($modules, 'g_module', 'g_module', 'onchange="clearElement()"');
    $layout->addField(PL_PLUGIN_MAKER_MODULES_TITLE, $fld);

    $fld = new sbLayoutElement('', '', 'g_elem', '', 'style="width: 350px" onchange="showSettings()"');
    $fld->mReadOnly = false;
    $fld->mCustomJS = 'selectElem';
    $layout->addField(PL_PLUGIN_MAKER_ELEMENT_TITLE, $fld);

    $fld = new sbLayoutInput('text', '1', 'spin_g_count', 'spin_g_count', 'style="width: 50px"');
    $fld->mMinValue = 1;
    $layout->addField(PL_PLUGIN_MAKER_COUNT_TITLE, $fld);

    //Характеристики товара
    $layout->addTab(PL_PLUGIN_MAKER_TAB_GOOD_SETTINGS, false);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', 'btn_save', 'disabled="disabled"');
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');
    $layout->show();
}

function fPlugin_Maker_Goods_Add_Submit()
{
    if(!isset($_POST['g_elem']) || $_POST['g_elem'] == '')
    {
        sb_show_message(PL_PLUGIN_MAKER_NO_ITEM_MSG, false, 'warning');
        fPlugin_Maker_Goods_Add();
        return;
    }

    $pl_ident = $_POST['g_module'];
    $pl_id = intval(sb_str_replace('pl_plugin_', '', $pl_ident));
    $elem_id = intval($_POST['g_elem']);

    //Защита от подмены данных
    $res = sql_param_query('SELECT COUNT(*) FROM sb_plugins_'.$pl_id.' WHERE p_id=? AND p_title=?s', $elem_id, $_POST['g_elem_title']);

    if($res[0][0] == 0)
    {
        sb_show_message(PL_PLUGIN_MAKER_NO_ITEM_MATCH_MSG, false, 'warning');
        fPlugin_Maker_Goods_Add();
        return;
    }

    //Вытаскиваем XML заказа
    $res = sql_param_query('SELECT p_order FROM sb_plugins_'.intval($_GET['pm_id']).' WHERE p_id = ?d', intval($_GET['elem_id']));

    if($res && $res[0][0] != '')
    {
        $xml = simplexml_load_string($res[0][0]);
    }
    else
    {
        $xml = new SimpleXMLElement('<goods></goods>');
    }

    //Вытаскиваем пользовательские поля
    $res = sql_param_assoc('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?s', $pl_ident);

	$categs_sql_fields = $elems_ids_fields = $categs_fields = $cat_attr = $attributes = $elems_fields = array();
	$elems_fields_select_sql = '';

    // формируем SQL-запрос для пользовательских полей
    require_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';
    if ($res)
    {
        if ($res[0]['pd_categs'] != '')
        {
            $categs_fields = unserialize($res[0]['pd_categs']);
        }
        else
        {
            $categs_fields = array();
        }

        if ($res[0]['pd_fields'] != '')
        {
            $elems_fields = unserialize($res[0]['pd_fields']);
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
                    $elems_fields_select_sql .= ', p.user_f_' . $value['id'];
                    $elems_ids_fields[] = $value['id'];
                }
            }

            fPlugin_Maker_Goods_Gen_Xml_Fields($elems_fields, $attributes);
        }

        if ($categs_fields)
        {
            foreach ($categs_fields as $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $categs_sql_fields[] = $value['id'];
                }
            }
            fPlugin_Maker_Goods_Gen_Xml_Fields($categs_fields, $cat_attr);
        }
    }

    // достаем данные товара для того чтобы вычислить цены, общую сумму со скидкой без скидки и т.д....
    $price_res = sql_param_query('SELECT p.p_title, p.p_url, p.p_ext_id, p.p_user_id, p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5,
									(
										SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="' . $pl_id . '"
									) as pm_elems_settings, c.cat_id, c.cat_ident, c.cat_title, c.cat_left, c.cat_right,
									c.cat_level, c.cat_ext_id, c.cat_rubrik, c.cat_closed, c.cat_fields, c.cat_url
									' . $elems_fields_select_sql . '
									FROM sb_plugins_' . $pl_id . ' p, sb_catlinks l, sb_categs c
									WHERE p.p_id = ?d
									AND l.link_el_id=p.p_id AND l.link_cat_id=c.cat_id AND c.cat_ident="pl_plugin_' . $pl_id . '"
									', $elem_id);

    if(!$price_res)
    {
        sb_show_message(PL_PLUGIN_MAKER_NO_ITEM_DATA_MSG, false, 'warning');
        fPlugin_Maker_Goods_Add();
    }

    list($ord_p_title, $ord_p_url, $ord_p_ext_id, $ord_p_user_id, $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $ord_pm_elems_settings,
		$cat_id, $cat_ident, $cat_title, $cat_left, $cat_right, $cat_level, $cat_ext_id, $cat_rubrik, $cat_closed, $cat_fields, $cat_url) = $price_res[0];

	$num_fields = count($price_res[0]);

    if ($ord_pm_elems_settings != '')
        $ord_pm_elems_settings = unserialize($ord_pm_elems_settings);
    else
        $ord_pm_elems_settings = array();

    // если цена расчетная расчитываем ее.
    if (!isset($ord_pm_elems_settings['price1_type']) || $ord_pm_elems_settings['price1_type'] == 0)
    {
        $ord_p_price1 = fPlugin_Maker_Quoting($pl_id, $elem_id, $ord_pm_elems_settings['price1_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5);
    }
    if (!isset($ord_pm_elems_settings['price2_type']) || $ord_pm_elems_settings['price2_type'] == 0)
    {
        $ord_p_price2 = fPlugin_Maker_Quoting($pl_id, $elem_id, $ord_pm_elems_settings['price2_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5);
    }
    if (!isset($ord_pm_elems_settings['price3_type']) || $ord_pm_elems_settings['price3_type'] == 0)
    {
        $ord_p_price3 = fPlugin_Maker_Quoting($pl_id, $elem_id, $ord_pm_elems_settings['price3_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5);
    }
    if (!isset($ord_pm_elems_settings['price4_type']) || $ord_pm_elems_settings['price4_type'] == 0)
    {
        $ord_p_price4 = fPlugin_Maker_Quoting($pl_id, $elem_id, $ord_pm_elems_settings['price4_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5);
    }
    if (!isset($ord_pm_elems_settings['price5_type']) || $ord_pm_elems_settings['price5_type'] == 0)
    {
        $ord_p_price5 = fPlugin_Maker_Quoting($pl_id, $elem_id, $ord_pm_elems_settings['price5_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5);
    }

    //Добавляем товар в заказ
    $good = $xml->addChild('good');
    $good->addAttribute('pm_id', $pl_id);
    $good->addAttribute('id', $elem_id);
    $good->addAttribute('cat_id', $cat_id);
    $good->addAttribute('unique', uniqid());
    //$good->addChild('p_title', '<![CDATA['.$ord_p_title.']]>');

    $title = $good->addChild('p_title');
    $node = dom_import_simplexml($title);
    $no = $node->ownerDocument;
    $node->appendChild($no->createCDATASection($ord_p_title));

    $good->addChild('p_count', intval($_POST['spin_g_count']));
    $good->addChild('p_ext_id', $ord_p_ext_id);
    $good->addChild('p_url', $ord_p_url);
    $good->addChild('p_user_id', $ord_p_user_id);
    $good->addChild('p_price1', $ord_p_price1);
    $good->addChild('p_price2', $ord_p_price2);
    $good->addChild('p_price3', $ord_p_price3);
    $good->addChild('p_price4', $ord_p_price4);
    $good->addChild('p_price5', $ord_p_price5);

    if ($num_fields > 21)
    {
        $i = 21;
        $cdata = array(
            'string',
            'text',
			'longtext',
			'image',
			'link',
			'file',
			'color',
			'password',
			'google_coords',
			'yandex_coords',
			'table'
        );

        foreach ($elems_ids_fields as $val)
        {
            if (isset($attributes[$val]) && is_array($attributes[$val]))
            {
                if(in_array($attributes[$val]['type'], $cdata))
                {
                    $user_node = $good->addChild($attributes[$val]['node']);
                    $node = dom_import_simplexml($user_node);
                    $no = $node->ownerDocument;

                    if(isset($_POST[$attributes[$val]['node']]))
                    {
                        $node->appendChild($no->createCDATASection($_POST[$attributes[$val]['node']]));
                    }
                    else
                    {
                        $node->appendChild($no->createCDATASection($price_res[0][$i]));
                    }
                }
                else
                {
                    if(isset($_POST[$attributes[$val]['node']]))
                    {
                        $user_node = $good->addChild($attributes[$val]['node'], $_POST[$attributes[$val]['node']]);
                    }
                    else
                    {
                        $user_node = $good->addChild($attributes[$val]['node'], $price_res[0][$i]);
                    }
                }

                foreach($attributes[$val] as $key=>$attr)
                {
                    if($key == 'node')
                    {
                        continue;
                    }

                    $user_node->addAttribute($key, $attr);
                }
            }
            $i++;
        }
    }

    //Добавляем в XML категорию, если ее там нет
    $cat_in_xml = false;
    $cats = $xml->xpath('cat/cat_id');

    foreach($cats as $cat)
    {
        if($cat_id == (int)$cat[0])
        {
            $cat_in_xml = true;
            break;
        }
    }

    if(!$cat_in_xml)
    {
        $res = sql_param_assoc('SELECT * FROM sb_categs WHERE cat_id=?d', $cat_id);
        if($res)
        {
            $cat = $xml->addChild('cat');
            $cat->addChild('cat_id', $cat_id);
            $cat->addChild('cat_ident', $res[0]['cat_ident']);
            $cat->addChild('cat_title', $res[0]['cat_title']);
            $cat->addChild('cat_left', $res[0]['cat_left']);
            $cat->addChild('cat_right', $res[0]['cat_right']);
            $cat->addChild('cat_level', $res[0]['cat_level']);
            $cat->addChild('cat_ext_id', $res[0]['cat_ext_id']);
            $cat->addChild('cat_rubrik', $res[0]['cat_rubrik']);
            $cat->addChild('cat_closed', $res[0]['cat_closed']);
            $cat->addChild('cat_url', $res[0]['cat_url']);

            if ($cat_fields != '')
                $cat_fields = unserialize($cat_fields);
            else
                $cat_fields = array();


            $num_cat_fields = count($categs_sql_fields);
            if ($num_cat_fields > 0)
            {
                $cdata = array(
                    'string',
                    'text',
                    'longtext',
                    'image',
                    'link',
                    'file',
                    'color',
                    'password',
                    'google_coords',
                    'yandex_coords',
                    'table'
                );
                foreach ($categs_sql_fields as $val)
                {
                    if (isset($cat_attr[$val]) && is_array($cat_attr[$val]))
                    {
                        $v = (isset($cat_fields['user_f_'.$val]) ? $cat_fields['user_f_'.$val] : '');
                        if (in_array($cat_attr[$val]['type'], $cdata))
                        {
                            $user_node = $cat->addChild($cat_attr[$val]['node']);
                            $node = dom_import_simplexml($user_node);
                            $no = $node->ownerDocument;
                            $node->appendChild($no->createCDATASection($v));
                        }
                        else
                        {
                            $user_node = $cat->addChild($cat_attr[$val]['node'], $v);
                        }

                        foreach ($cat_attr[$val] as $key => $attr)
                        {
                            if ($key == 'node')
                            {
                                continue;
                            }

                            $user_node->addAttribute($key, $attr);
                        }
                    }
                }
            }
        }

    }

    sql_param_query('UPDATE sb_plugins_'.intval($_GET['pm_id']).' SET p_order=?s WHERE p_id=?d', $xml->asXML(), intval($_GET['elem_id']));
    echo '<script>sbReturnValue("refresh");</script>';
}

/**
 * Функция создает массив пользовательских полей элементов/разделов.
 * Значением элемента массива является массив с характеристиками полей
 *
 * @param array $fields Массив данных пользовательских полей
 * @param array $array  Сгененрированный массив с характеристиками полей
 */
function fPlugin_Maker_Goods_Gen_Xml_Fields($fields, &$array)
{
    static $settings;

    if(empty($settings))
    {
        require_once SB_CMS_LANG_PATH . '/pl_plugin_data.lng.php';
        $settings = array();
        foreach($GLOBALS['sb_plugins_fields'] as $arr)
        {
            foreach($arr as $type=>$data)
            {
                if(isset($data['settings']))
                {
                    $settings[$type] = $data['settings'];
                }
            }
        }
    }

	foreach ($fields as $value)
	{
		if (isset($value['sql']) && $value['sql'] == 1)
		{
            $array[$value['id']] = array(
                        'node'                  =>  'user_f_'.$value['id'],
                        'tag'                   =>  $value['tag'],
                        'name'                  => sb_htmlspecialchars($value['title']),
                        'type'                  =>  $value['type']
                    );

			switch($value['type'])
			{
				case 'checkbox':
                case 'categs':
                case 'select_sprav':
                case 'multiselect_sprav':
                case 'radio_sprav':
                case 'checkbox_sprav':
                case 'link_sprav':
                case 'select_plugin':
                case 'elems_plugin':
                case 'multiselect_plugin':
                case 'radio_plugin':
                case 'checkbox_plugin':
                case 'link_plugin':
                    foreach($settings[$value['type']] as $key=>$val)
                    {
                        $array[$value['id']][$key] = (isset($value['settings'][$key]))? sb_htmlspecialchars($value['settings'][$key]) : sb_htmlspecialchars($val);
                    }
		    	    break;
			}
        }
    }
}

function fPlugin_Maker_Goods_Settings()
{
    if(!isset($_GET['pl_ident']) || $_GET['pl_ident'] == '' || !isset($_GET['elem_id']) || $_GET['elem_id'] == '')
    {
        return '';
    }
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $layout = new sbLayout();

    $pl_ident = $_GET['pl_ident'];
    $elem_id = $_GET['elem_id'];

    $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
    if (!isset($plugins[$pl_ident]))
        return '';

    $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $pl_ident);
    if (!$res || $res[0][0] == '')
    {
        return '';
    }

    $fields = unserialize($res[0][0]);
    if (!$fields || count($fields) <= 0)
    {
        return '';
    }

    $values = array();
    $keys = array();
    $sql = '';

    foreach($fields as $value)
    {
        if($value['type'] != 'multiselect_sprav')
        {
            continue;
        }

        if (!isset($values['user_f_' . $value['id']]))
        {
            $values['user_f_' . $value['id']] = '';
        }

        if (isset($value['sql']) && $value['sql'] == 1)
        {
            $keys[] = $value['id'];
            $sql .= 'user_f_' . $value['id'] . ',';
        }
    }

    if ($sql != '')
    {
        $sql = substr($sql, 0, -1);

        $res = sql_query('SELECT ' . $sql . ' FROM ' . $plugins[$pl_ident]['table'] . ' WHERE p_id=?d', $elem_id);
        if ($res)
        {
            for ($i = 0; $i < count($res[0]); $i++)
            {
                if (!is_null($res[0][$i]))
                    $values['user_f_' . $keys[$i]] = $res[0][$i];
                else
                    $values['user_f_' . $keys[$i]] = '';
            }
        }
    }

    foreach ($fields as $value)
    {
        $type = $value['type'];
        $settings = array();

        $f_name = 'user_f_' . $value['id'];
        $f_value = isset($values[$f_name]) ? $values[$f_name] : '';
        $f_mandatory = isset($value['mandatory']) && $value['mandatory'] == 1 ? true : false;
        $f_title = isset($value['title']) ? $value['title'] : '';
        $f_style = '';
        $f_attribs = isset($value['attribs']) ? $value['attribs'] : '';

        if (isset($value['settings']) && $value['settings'] != '')
        {
            $settings = $value['settings'];

            $field_right = 'edit';
            $f_right = $_SESSION['sbAuth']->getFieldRight($pl_ident, array($f_name), array('view', 'edit'), false, $fields);

            if (isset($f_right[$f_name]['view']) && $f_right[$f_name]['view'] == 0 &&
            isset($f_right[$f_name]['edit']) && $f_right[$f_name]['edit'] == 0)
            {
                continue;
            }
            elseif (isset($f_right[$f_name]['view']) && $f_right[$f_name]['view'] == 1 &&
            isset($f_right[$f_name]['edit']) && $f_right[$f_name]['edit'] == 0)
            {
                $f_attribs .= ' disabled="disabled"';
                $field_right = 'view';
            }

            if (isset($settings['width']) && $settings['width'] != '')
            {
                $f_style .= 'width: ' . $settings['width'] . ';';
            }

            if (isset($settings['widths']) && $settings['widths'] != '')
            {
                $f_style .= 'width: ' . $settings['widths'] . ';';
            }

            if (isset($settings['height']) && $settings['height'] != '')
            {
                $f_style .= 'height: ' . $settings['height'] . ';';
            }

            $matches = array();
            if (preg_match_all('/style[\s]*=[\s]*["\']([^"\'].*?)["\']/si' . SB_PREG_MOD, $f_attribs, $matches))
            {
                foreach ($matches[1] as $style)
                {
                    $f_style .= $style;
                }

                $f_attribs = trim(preg_replace('/style[\s]*=[\s]*["\']([^"\'].*?)["\']/si' . SB_PREG_MOD, '', $f_attribs));
            }

            if ($f_style != '')
                $f_style = 'style="' . $f_style . '"';

            $f_style .= ' ' . $f_attribs;
        }

        switch ($type)
        {
            case 'multiselect_sprav':
                $fld = new sbLayoutSpravData('select', $settings['sprav_ids'], $f_value, $f_name, '', $f_style, $f_mandatory);
                $fld->mSubCategs = $settings['subcategs'] == 1;
                $fld->mAJAX = isset($settings['sprav_ajax']) && $settings['sprav_ajax'] == 1;
                $fld->mSeparator = isset($settings['separator']) ? $settings['separator'] : ',&nbsp;';
                $fld->mReadOnly = ($field_right != 'edit');

                //вытаскиваем установленные значения справочника для данного элемента
                $res = sql_param_query('SELECT ?# FROM '.$plugins[$pl_ident]['table'].' WHERE p_id=?d', $f_name, $elem_id);
                if($res && $res[0][0] != '')
                {
                    $fld->mAllowItems = explode(',', $res[0][0]);
                }

                if (isset($settings['sprav_title_fld']))
                {
                    $fld->mTitleFld = $settings['sprav_title_fld'];
                }

                $layout->addField($f_title, $fld, 'id="' . $f_name . '_th"', 'id="' . $f_name . '_td"', 'id="' . $f_name . '_tr"');
                break;
        }
    }

    $layout->show();
}
?>