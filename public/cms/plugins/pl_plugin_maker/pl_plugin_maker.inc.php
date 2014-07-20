<?php
/********************************** функции построения диалоговых окон ***********************************/

/**
 * Вывод списка элементов
 */
function fPlugin_Maker_Get_Elem_List()
{
	if (!isset($_GET['pm_id']))
	    return;

    $pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title, pm_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings, $pm_elems_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');
    $params = unserialize($_GET['params']);

    if (!isset($params['use_sort']))
    	$params['use_sort'] = 1;

    if (!isset($params['use_filter']))
    	$params['use_filter'] = 0;

    if (!isset($params['filter']))
        $params['filter'] = 'all';

    if (!isset($params['subcategs']))
        $params['subcategs'] = 1;

    if (!isset($params['filter_compare']))
    	$params['filter_compare'] = 'IN';

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
                alert("'.PL_PLUGIN_MAKER_H_ELEM_LIST_NO_CATEGS_MSG.'");
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
                alert("'.PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_ALERT.'");
                return;
            }

            if (sbGetE("calendar").checked && sbGetE("calendar_field").value == "")
            {
            	alert("'.PL_PLUGIN_MAKER_H_ELEM_LIST_NO_CALENDAR_FIELD_ALERT.'");
            	return;
            }

            var r_filter_all = sbGetE("r_filter_all");
            var r_filter_last = sbGetE("r_filter_last");
            var r_filter_next = sbGetE("r_filter_next");
            var r_filter_from_to = sbGetE("r_filter_from_to");

            if (r_filter_all.checked)
            {
                params["filter"] = r_filter_all.value;
            }
            else if (r_filter_from_to.checked)
            {
                params["filter"] = r_filter_from_to.value;
                var el_filter_from = sbGetE("spin_filter_from");
                var el_filter_to = sbGetE("spin_filter_to");

                if (el_filter_to.value != "" && el_filter_to.value != 0 && parseInt(el_filter_from.value) > parseInt(el_filter_to.value))
                {
                    alert("'.PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_FROM_TO_ERROR.'");
                    return;
                }

                params["filter_from"] = el_filter_from.value;
                params["filter_to"] = el_filter_to.value;
            }

            var el_sort1 = sbGetE("sort1");
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

            params["page"] = sbGetE("page").value;
            params["auth_page"] = sbGetE("auth_page").value;
            params["edit_page"] = sbGetE("edit_page").value;
            params["subcategs"] = sbGetE("subcategs").checked ? 1 : 0;
            params["show_hidden"] = sbGetE("show_hidden").checked ? 1 : 0;
            params["show_selected"] = sbGetE("show_selected").checked ? 1 : 0;
            params["rubrikator"] = sbGetE("rubrikator").checked ? 1 : 0;
            params["pm_id"] = "'.$pm_id.'";
            params["cloud"] = sbGetE("cloud").checked ? 1 : 0;
			params["cloud_comp"] = sbGetE("cloud_comp").checked ? 1 : 0;
            params["calendar"] = sbGetE("calendar").checked ? 1 : 0;
			params["calendar_field"] = sbGetE("calendar_field").value;
			params["registred_users"] = sbGetE("registred_users").checked ? 1 : 0;
			params["registred_users_edit_link"] = sbGetE("registred_users_edit_link").checked ? 1 : 0;

            params["use_id_el_filter"] = sbGetE("use_id_el_filter").checked ? 1 : 0;
            params["use_filter"] = sbGetE("use_filter").checked ? 1 : 0;
            params["filter_logic"] = sbGetE("filter_logic").value;
            params["filter_text_logic"] = sbGetE("filter_text_logic").value;
            params["filter_compare"] = sbGetE("filter_compare").value;
            params["filter_morph"] = sbGetE("filter_morph").checked ? 1 : 0;
            params["allow_bbcode"] = sbGetE("allow_bbcode").checked ? 1 : 0;
            params["use_sort"] = sbGetE("use_sort").checked ? 1 : 0;

            params["moderate"] = (sbGetE("moderate").checked ? 1 : 0);
            params["moderate_email"] = sbGetE("moderate_email").value;

            params["use_component_cache"] = (sbGetE("use_component_cache").checked ? 1 : 0);
            params["cache_not_url"] = (sbGetE("cache_not_url").checked ? 1 : 0);
            params["cache_not_get"] = (sbGetE("cache_not_get").checked ? 1 : 0);
            params["cache_not_get_list"] = sbGetE("cache_not_get_list").value;';


    	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
		{
    		echo ' if(sbGetE("cena"))
                    params["cena"] = sbGetE("cena").value;';
		}

      echo 'res.temp_id = el_temp.value;
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

        function changeCalendarField(el)
        {
            sbGetE("calendar_field").disabled = !el.checked;
        }

        function changeFilterMorph(el)
        {
            sbGetE("filter_morph").disabled = (el.value != "IN");
        }

        function changeUseFilter(el)
        {
        	sbGetE("filter_morph").disabled = !el.checked || sbGetE("filter_compare").value != "IN";
        	sbGetE("filter_logic").disabled = !el.checked;
        	sbGetE("filter_text_logic").disabled = !el.checked;
        	sbGetE("filter_compare").disabled = !el.checked;
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

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_CATEGS_TAB);

    $categs = new sbJustCategs('pl_plugin_'.$pm_id);
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsSelectedIds = (isset($params['ids']) && $params['ids'] != '' ? explode('^', $params['ids']) : array());

    $layout->addField('', new sbLayoutHTML($categs->showTree('', '', '', false), true));
    $categs->init();

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);

    $options = array();
    $res = sql_query('SELECT categs.cat_title, temps.ptl_id, temps.ptl_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_list temps WHERE temps.ptl_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_design_list" ORDER BY categs.cat_left, temps.ptl_title');
    if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $ptl_id, $ptl_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$ptl_id] = $ptl_title;
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
        $fld = new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_NO_TEMPS_MSG, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST).'</div>', '', '', false);
    }

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, $fld);
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 400px;"');
    $fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_LIST_PAGE_HINT, $pm_title, isset($pm_settings['full_component_title']) ? $pm_settings['full_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FULL).'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_PAGE, $fld);

    $fld = new sbLayoutPage(isset($params['auth_page']) ? $params['auth_page'] : '', 'auth_page', '', 'style="width: 400px;"');
    $fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE_HINT, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : '').'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_AUTH_PAGE, $fld);

    $fld = new sbLayoutPage(isset($params['edit_page']) ? $params['edit_page'] : '', 'edit_page', '', 'style="width: 400px;"');
    $fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE_HINT, $pm_title, isset($pm_settings['form_component_title']) ? $pm_settings['form_component_title'] : '').'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_EDIT_PAGE, $fld);

    $fields_names = array();

    if (isset($pm_elems_settings['title_field_title']) && trim($pm_elems_settings['title_field_title']) != '')
        $fields_names['p_title'] = $pm_elems_settings['title_field_title'];

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket')  && (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1))
    {
	    if (isset($pm_elems_settings['price1_title']) && trim($pm_elems_settings['price1_title']) != '')
	        $fields_names['p_price1'] = $pm_elems_settings['price1_title'];

	    if (isset($pm_elems_settings['price2_title']) && trim($pm_elems_settings['price2_title']) != '')
	        $fields_names['p_price2'] = $pm_elems_settings['price2_title'];

	    if (isset($pm_elems_settings['price3_title']) && trim($pm_elems_settings['price3_title']) != '')
	        $fields_names['p_price3'] = $pm_elems_settings['price3_title'];

	    if (isset($pm_elems_settings['price4_title']) && trim($pm_elems_settings['price4_title']) != '')
	        $fields_names['p_price4'] = $pm_elems_settings['price4_title'];

	    if (isset($pm_elems_settings['price5_title']) && trim($pm_elems_settings['price5_title']) != '')
	        $fields_names['p_price5'] = $pm_elems_settings['price5_title'];

		$layout->addField('', new sbLayoutDelim());

		$options = array();
		$options[1] = isset($fields_names['p_price1']) ? $fields_names['p_price1'] : PL_PLUGIN_MAKER_H_CENA_1;
		$options[2] = isset($fields_names['p_price2']) ? $fields_names['p_price2'] : PL_PLUGIN_MAKER_H_CENA_2;
		$options[3] = isset($fields_names['p_price3']) ? $fields_names['p_price3'] : PL_PLUGIN_MAKER_H_CENA_3;
		$options[4] = isset($fields_names['p_price4']) ? $fields_names['p_price4'] : PL_PLUGIN_MAKER_H_CENA_4;
		$options[5] = isset($fields_names['p_price5']) ? $fields_names['p_price5'] : PL_PLUGIN_MAKER_H_CENA_5;

		$fld = new sbLayoutSelect($options, 'cena');
		$fld->mSelOptions = isset($params['cena']) ? array($params['cena']) : array();
		$layout->addfield(PL_PLUGIN_MAKER_H_ELEM_CENA_LIST_FIELD_TITLE, $fld);
	}
	$layout->addField('', new sbLayoutDelim());

	$fields = sbPlugins::getSortFields('pl_plugin_'.$pm_id, $fields_names, $pm_elems_settings);

    if (isset($pm_elems_settings['show_sort_field']) && $pm_elems_settings['show_sort_field'] == 1)
    	$fields['p.p_sort'] = PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_SORT;

	$calendar_fields = array('' => ' --- ');
   	foreach ($_SESSION['sbPlugins']->mCalendar['pl_plugin_'.$pm_id.'_list']['date_fields'] as $key => $value)
   	{
   		$calendar_fields[$key] = $value;
   	}

    $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
    if ($res)
    {
        list($pd_fields) = $res[0];

        if ($pd_fields != '')
        {
	        $pd_fields = unserialize($pd_fields);

	        foreach($pd_fields as $value)
	        {
	            if ($value['type'] == 'string' ||
	                $value['type'] == 'number' ||
	                $value['type'] == 'date' ||
	                $value['type'] == 'checkbox' ||
	                $value['type'] == 'image' ||
	                $value['type'] == 'link' ||
	                $value['type'] == 'file' ||
	                $value['type'] == 'color' ||
	                $value['type'] == 'select_sprav' ||
	                $value['type'] == 'radio_sprav' ||
	                $value['type'] == 'select_plugin' ||
	                $value['type'] == 'radio_plugin' ||
	                $value['type'] == 'elems_plugin')
	            {
	                $fields['p.user_f_'.$value['id']] = $value['title'];

	            	if ($value['type'] == 'date')
		            {
		                $calendar_fields['user_f_'.$value['id']] = $value['title'];
		            }
	            }
	        }
        }
    }

    $order = array('DESC' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_DESC,
                   'ASC'  => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_ASC);

    $fld1 = new sbLayoutSelect($fields, 'sort1', '', 'onchange="changeSort(this, 1);"');
    $fld1->mSelOptions = array((isset($params['sort1']) ? $params['sort1'] : 'p.p_title'));
    $fld2 = new sbLayoutSelect($order, 'order1', '', (isset($params['sort1']) && $params['sort1'] == 'RAND()' ? 'disabled="disabled"' : ''));
    $fld2->mSelOptions = array((isset($params['order1']) && $params['order1'] != '' ? $params['order1'] : 'ASC'));
    $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

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
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SHOW_HIDDEN, new sbLayoutInput('checkbox', '1', 'show_hidden', '', (isset($params['show_hidden']) && $params['show_hidden'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_NOT_SHOW_SELECTED, new sbLayoutInput('checkbox', '1', 'show_selected', '', (isset($params['show_selected']) && $params['show_selected'] == 1 ? 'checked="checked"' : '')));

	$fld = new sbLayoutInput('checkbox', '1', '', 'registred_users', (isset($params['registred_users']) && $params['registred_users'] == 1 ? 'checked="checked"' : '') );
	$fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_AUTH_USERS_LABEL.'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_AUTH_USERS, $fld);

	$fld = new sbLayoutInput('checkbox', '1', '', 'registred_users_edit_link', (isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 ? 'checked="checked"' : '') );
	$fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_AUTH_USERS_EDIT_LINK_LABEL.'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_AUTH_USERS_EDIT_LINK, $fld);

	$layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutInput('checkbox', '1', 'use_id_el_filter', '', (isset($params['use_id_el_filter']) && $params['use_id_el_filter'] == 1 ? 'checked="checked"' : ''));
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_LIST_USE_FILTER_ID_DESRC.'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_USE_FILTER_ID_EL, $fld);

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_RUBRIK_LINK, new sbLayoutInput('checkbox', '1', 'rubrikator', '', (isset($params['rubrikator']) && $params['rubrikator'] == 1 ? 'checked="checked"' : '')));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_CLOUD_LINK, new sbLayoutInput('checkbox', '1', 'cloud', '', (isset($params['cloud']) && $params['cloud'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_CLOUD_COMP_LINK, new sbLayoutInput('checkbox', '1', 'cloud_comp', '', (isset($params['cloud_comp']) && $params['cloud_comp'] == 1 ? 'checked="checked"' : '')));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_CALENDAR_LINK, new sbLayoutInput('checkbox', '1', 'calendar', '', (isset($params['calendar']) && $params['calendar'] == 1 ? 'checked="checked"' : '').' onclick="changeCalendarField(this)"'));

    $fld = new sbLayoutSelect($calendar_fields, 'calendar_field', '', (!isset($params['calendar']) || $params['calendar'] != 1 ? 'disabled="disabled"' : ''));
    $fld->mSelOptions = array((isset($params['calendar_field']) && $params['calendar_field'] != '' ? $params['calendar_field'] : ''));
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_LIST_CALENDAR_FIELD_HINT.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_CALENDAR_FIELD, $fld);

    $layout->addField('', new sbLayoutDelim());
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_PREMOD_COMMENTS, new sbLayoutInput('checkbox', 'moderate', '1', 'moderate', (isset($params['moderate']) && $params['moderate'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_BBCODE_ALLOW, new sbLayoutInput('checkbox', 'allow_bbcode', '0', 'allow_bbcode', (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_MODER_EMAIL, new sbLayoutInput('text', (isset($params['moderate_email']) ? $params['moderate_email'] : ''), 'moderate_email', 'moderate_email', 'style="width:400px;"'));

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TAB);

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_USE_FILTER, new sbLayoutInput('checkbox', '1', 'use_filter', '', (isset($params['use_filter']) && $params['use_filter'] == 1 ? 'checked="checked"' : '').' onclick="changeUseFilter(this)"'));

    $fld = new sbLayoutSelect(array('AND' => KERNEL_AND, 'OR' => KERNEL_OR), 'filter_logic');
    $fld->mSelOptions = array((isset($params['filter_logic']) && $params['filter_logic'] != '' ? $params['filter_logic'] : ''));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_LOGIC, $fld);

    $fld = new sbLayoutSelect(array('AND' => KERNEL_AND, 'OR' => KERNEL_OR), 'filter_text_logic');
    $fld->mSelOptions = array((isset($params['filter_text_logic']) && $params['filter_text_logic'] != '' ? $params['filter_text_logic'] : ''));

    $fld1 = new sbLayoutSelect(array('EQ' => PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TEXT_LOGIC_EQ, 'IN' => PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TEXT_LOGIC_IN), 'filter_compare', '', 'onchange="changeFilterMorph(this);"');
    $fld1->mSelOptions = array((isset($params['filter_compare']) && $params['filter_compare'] != '' ? $params['filter_compare'] : ''));
    $fld1->mHTML = '&nbsp;&nbsp;<input type="checkbox" value="1" id="filter_morph"'.(isset($params['filter_morph']) && $params['filter_morph'] == 1 ? ' checked="checked"' : '').(isset($params['filter_compare']) && $params['filter_compare'] != 'IN' ? ' disabled="disabled"' : '').' />&nbsp;&nbsp;'.PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_MORPH;
    $fld->mHTML = '&nbsp;&nbsp;'.$fld1->getField();

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TEXT_LOGIC, $fld);

    $layout->addField('', new sbLayoutDelim());

    $html = '';
    $fld1 = new sbLayoutInput('radio', 'all', 'filter', 'r_filter_all', ($params['filter'] == 'all' ? ' checked="checked"' : ''));
    $fld1->mHTML = '&nbsp;&nbsp;'.PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_ALL;
    $html .= $fld1->getField().'<br /><br />';

    $fld3 = new sbLayoutInput('radio', 'from_to', 'filter', 'r_filter_from_to', ($params['filter'] == 'from_to' ? ' checked="checked"' : ''));
    $fld3_1 = new sbLayoutInput('input', (isset($params['filter_from']) ? $params['filter_from'] : ''), 'filter_from', 'spin_filter_from', 'style="width: 50px;"');
    $fld3_2 = new sbLayoutInput('input', (isset($params['filter_to']) ? $params['filter_to'] : ''), 'filter_to', 'spin_filter_to', 'style="width: 50px;"');
    $fld3_1->mMinValue = 1;
    $html .= $fld3_1->getJavaScript().'<table cellpadding="0" cellspacing="0"><tr><td>'.$fld3->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_FROM.'&nbsp;&nbsp;</td><td>'.$fld3_1->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_TO.'&nbsp;&nbsp;</td><td>'.$fld3_2->getField().'</td></tr></table>
              <div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_FROM_TO_HINT.'</div>';

    $fld = new sbLayoutHTML($html);
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER, $fld);

    //Использовать сортировку
   	$layout->addField('', new sbLayoutDelim());
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_USE_SORT, new sbLayoutInput('checkbox', '0', '', 'use_sort', (isset($params['use_sort']) && $params['use_sort'] == 1 ? 'checked="checked"' : '')));

    //Кеширование
    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_CACHE_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_CACHE_TAB);
    
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_USE_COMPONENT_CACHE, new sbLayoutInput('checkbox', '1', 'use_component_cache', '', ($params['use_component_cache'] == 1 ? 'checked="checked"' : '').' onclick=showHideCacheParams(this.checked)'));

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_NOT_URL, new sbLayoutInput('checkbox', '1', 'cache_not_url', '', ($params['cache_not_url'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"')));
    $layout->addField(PL_PLUGIN_MAKER_LIST_NOT_GET, new sbLayoutInput('checkbox', '1', 'cache_not_get', '', ($params['cache_not_get'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"').' onclick=showHideGETList(this.checked)'));

    $fld = new sbLayoutInput('text', $params['cache_not_get_list'], 'cache_not_get_list', 'cache_not_get_list', (($params['cache_not_get'] == 0 || $params['use_component_cache'] == 1) ? 'disabled="disabled" ' : '').'style="width:400px;"');
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_LIST_NOT_GET_LIST_HINT.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_LIST_NOT_GET_LIST, $fld);

	$layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="chooseCat();"'.(count($options) > 0 ? '' : ' disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

/**
 * Вывод выбранного элемента
 *
 */
function fPlugin_Maker_Get_Elem_Full()
{
	if (!isset($_GET['pm_id']))
        return;

    $pm_id = intval($_GET['pm_id']);
    $res = sql_query('SELECT pm_title, pm_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings, $pm_elems_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    $params = unserialize($_GET['params']);

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
        function choose()
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
                alert("'.PL_PLUGIN_MAKER_H_ELEM_FULL_NO_TEMPS_ALERT.'");
                return;
            }

            var el_page = sbGetE("page");
            if (el_page)
            {
                params["page"] = el_page.value;
            }
            else
            {
                params["page"] = "";
            }
			params["auth_page"] = sbGetE("auth_page").value;
			params["edit_page"] = sbGetE("edit_page").value;

            params["moderate"] = (sbGetE("moderate").checked ? 1 : 0);
            params["moderate_email"] = sbGetE("moderate_email").value;
			params["registred_users"] = sbGetE("registred_users").checked ? 1 : 0;
			params["component"] = sbGetE("component").value;
			params["allow_bbcode"] = sbGetE("allow_bbcode").checked ? 1 : 0;
			params["registred_users_edit_link"] = sbGetE("registred_users_edit_link").checked ? 1 : 0;
            params["pm_id"] = "'.$pm_id.'";';

   	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket'))
	{
   		echo 'if(sbGetE("cena"))
                params["cena"] = sbGetE("cena").value;';
	}
            echo '

            params["use_component_cache"] = (sbGetE("use_component_cache").checked ? 1 : 0);
            params["cache_not_url"] = (sbGetE("cache_not_url").checked ? 1 : 0);
            params["cache_not_get"] = (sbGetE("cache_not_get").checked ? 1 : 0);
            params["cache_not_get_list"] = sbGetE("cache_not_get_list").value;

            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);

            sbReturnValue(res);
        }

        function getPageComponents()
        {
        	var p_url = sbGetE("page").value;
        	p_url = p_url.split("&");
            var comp_el = sbGetE("component");
			while (comp_el.childNodes.length > 1)
                comp_el.removeChild(comp_el.childNodes[1]);

            var res = sbLoadSync(sb_cms_empty_file + "?event=pl_plugin_'.$pm_id.'_elem_list_components&p_url=" + p_url[0]+"&p_id='.$pm_id.'");
			if (res)
            {
                comp_el.remove(0);

                res = eval("(" + res + ")");
                for(var i = 0; i < res.keys.length; i++)
            	{

            	    var oOption = document.createElement("OPTION");
            	    comp_el.appendChild(oOption);
                    oOption.value = res.keys[i];
                    oOption.innerHTML = res.values[i];
            	}
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
    </script><br />';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $layout->mTableWidth = '90%';
    $layout->mTitleWidth = '250';
    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_FULLTEXT);

    $res = sql_query('SELECT categs.cat_title, temps.ptf_id, temps.ptf_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_full temps WHERE temps.ptf_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_design_full" ORDER BY categs.cat_left, temps.ptf_title');
    if ($res)
    {
        $options = array();
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $ptf_id, $ptf_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$ptf_id] = $ptf_title;
        }

        $fld = new sbLayoutSelect($options, 'temp_id');
        if(isset($params['temp_id']))
            $fld->mSelOptions = array($params['temp_id']);

        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, $fld);
    }
    else
    {
        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_NO_TEMPS_MSG, $pm_title, isset($pm_settings['full_component_title']) ? $pm_settings['full_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FULL).'</div>', '', '', false));
    }

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 400px;" onchange="getPageComponents()"');
	$fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE_HINT, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FULL).'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE, $fld);

	$components = array('' => ' --- ');
    if (isset($params['page']) && $params['page'] != '')
    {
    	$components = sb_get_elem_components($params['page'], 'pl_plugin_'.$pm_id.'_list', 'php');
    }
    $fld = new sbLayoutSelect($components, 'component');
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_COMPONENT_HINT.'</div>';
    if(isset($params['component']))
    	$fld -> mSelOptions = array($params['component']);
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_COMPONENT, $fld);

    $fld = new sbLayoutPage(isset($params['auth_page']) ? $params['auth_page'] : '', 'auth_page', '', 'style="width: 400px;"');
    $fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE_HINT, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : '').'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_AUTH_PAGE, $fld);

    $fld = new sbLayoutPage(isset($params['edit_page']) ? $params['edit_page'] : '', 'edit_page', '', 'style="width: 400px;"');
    $fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE_HINT, $pm_title, isset($pm_settings['form_component_title']) ? $pm_settings['form_component_title'] : '').'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_EDIT_PAGE, $fld);

    $fields_names = array();

    if (isset($pm_elems_settings['title_field_title']) && trim($pm_elems_settings['title_field_title']) != '')
        $fields_names['p_title'] = $pm_elems_settings['title_field_title'];

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1))
    {
	    if (isset($pm_elems_settings['price1_title']) && trim($pm_elems_settings['price1_title']) != '')
	        $fields_names['p_price1'] = $pm_elems_settings['price1_title'];

	    if (isset($pm_elems_settings['price2_title']) && trim($pm_elems_settings['price2_title']) != '')
	        $fields_names['p_price2'] = $pm_elems_settings['price2_title'];

	    if (isset($pm_elems_settings['price3_title']) && trim($pm_elems_settings['price3_title']) != '')
	        $fields_names['p_price3'] = $pm_elems_settings['price3_title'];

	    if (isset($pm_elems_settings['price4_title']) && trim($pm_elems_settings['price4_title']) != '')
	        $fields_names['p_price4'] = $pm_elems_settings['price4_title'];

	    if (isset($pm_elems_settings['price5_title']) && trim($pm_elems_settings['price5_title']) != '')
	        $fields_names['p_price5'] = $pm_elems_settings['price5_title'];

        $layout->addField('', new sbLayoutDelim());

        $options = array();
        $options[1] = isset($fields_names['p_price1']) ? $fields_names['p_price1'] : PL_PLUGIN_MAKER_H_CENA_1;
        $options[2] = isset($fields_names['p_price2']) ? $fields_names['p_price2'] : PL_PLUGIN_MAKER_H_CENA_2;
        $options[3] = isset($fields_names['p_price3']) ? $fields_names['p_price3'] : PL_PLUGIN_MAKER_H_CENA_3;
        $options[4] = isset($fields_names['p_price4']) ? $fields_names['p_price4'] : PL_PLUGIN_MAKER_H_CENA_4;
        $options[5] = isset($fields_names['p_price5']) ? $fields_names['p_price5'] : PL_PLUGIN_MAKER_H_CENA_5;

        $fld = new sbLayoutSelect($options, 'cena');
        $fld->mSelOptions = isset($params['cena']) ? array($params['cena']) : array();
        $layout->addfield(PL_PLUGIN_MAKER_H_ELEM_CENA_LIST_FIELD_TITLE, $fld);
    }
    $layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutInput('checkbox', '1', '', 'registred_users', (isset($params['registred_users']) && $params['registred_users'] == 1 ? 'checked="checked"' : '') );
	$fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_AUTH_USERS_FULL_LABEL.'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_OUTPUT_USERS_ELEMS, $fld);

	$fld = new sbLayoutInput('checkbox', '1', '', 'registred_users_edit_link', (isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 ? 'checked="checked"' : '') );
	$fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_AUTH_USERS_FULL_LABEL.'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_EDIt_USERS_ELEMS, $fld);


	$layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_PREMOD_COMMENTS, new sbLayoutInput('checkbox', 'moderate', '1', 'moderate', (isset($params['moderate']) && $params['moderate'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_BBCODE_ALLOW, new sbLayoutInput('checkbox', 'allow_bbcode', '0', 'allow_bbcode', (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_MODER_EMAIL, new sbLayoutInput('text', (isset($params['moderate_email']) ? $params['moderate_email'] : ''), 'moderate_email', 'moderate_email', 'style="width:400px;"'));

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
    {
        $layout->addTab(PL_PLUGIN_MAKER_H_BASKET_TAB);
        $layout->addHeader(PL_PLUGIN_MAKER_H_BASKET_TAB);

        $options = array();
        $options[1] = PL_PLUGIN_MAKER_H_CENA_1;
        $options[2] = PL_PLUGIN_MAKER_H_CENA_2;
        $options[3] = PL_PLUGIN_MAKER_H_CENA_3;
        $options[4] = PL_PLUGIN_MAKER_H_CENA_4;
        $options[5] = PL_PLUGIN_MAKER_H_CENA_5;

        $fld = new sbLayoutSelect($options, 'cena');
        $fld->mSelOptions = isset($params['cena']) ? array($params['cena']) : array();
        $layout->addfield(PL_PLUGIN_MAKER_H_ELEM_CENA_FIELD_TITLE, $fld);
    }

    //Кеширование
    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_CACHE_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_CACHE_TAB);

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_USE_COMPONENT_CACHE, new sbLayoutInput('checkbox', '1', 'use_component_cache', '', ($params['use_component_cache'] == 1 ? 'checked="checked"' : '').' onclick=showHideCacheParams(this.checked)'));
    
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_NOT_URL, new sbLayoutInput('checkbox', '1', 'cache_not_url', '', ($params['cache_not_url'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"')));
    $layout->addField(PL_PLUGIN_MAKER_LIST_NOT_GET, new sbLayoutInput('checkbox', '1', 'cache_not_get', '', ($params['cache_not_get'] == 1 ? 'checked="checked"' : '').' '.($params['use_component_cache'] == 0 ? '' : 'disabled="disabled"').' onclick=showHideGETList(this.checked)'));

    $fld = new sbLayoutInput('text', $params['cache_not_get_list'], 'cache_not_get_list', 'cache_not_get_list', (($params['cache_not_get'] == 0 || $params['use_component_cache'] == 1) ? 'disabled="disabled" ' : '').'style="width:400px;"');
    $fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_LIST_NOT_GET_LIST_HINT.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_LIST_NOT_GET_LIST, $fld);

    $layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="choose();"'.($res ? '' : ' disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

/**
 * Вывод разделов
 *
 */
function fPlugin_Maker_Get_Elem_Categs()
{
	$pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title, pm_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    fCategs_Get_Elem_Categs('pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id.'_design_categs', sprintf(PL_PLUGIN_MAKER_H_ELEM_CATEGS_NO_TEMPS_MSG, $pm_title, isset($pm_settings['categs_component_title']) ? $pm_settings['categs_component_title'] : PL_PLUGIN_MAKER_H_ELEM_CATEGS), $pm_id);
}

/**
 * Вывод выбранного раздела
 *
 */
function fPlugin_Maker_Get_Elem_Sel_Cat()
{
	$pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title, pm_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    fCategs_Get_Elem_Sel_Cat('pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id.'_design_sel_cat', sprintf(PL_PLUGIN_MAKER_H_ELEM_SEL_CAT_NO_TEMPS_MSG, $pm_title, isset($pm_settings['sel_cat_component_title']) ? $pm_settings['sel_cat_component_title'] : PL_PLUGIN_MAKER_H_ELEM_SEL_CAT), $pm_id);
}

/**
 * Вывод формы редактирования элементов
 */
function fPlugin_Maker_Get_Elem_Form_Edit()
{
	fPlugin_Maker_Get_Elem_Form(true);
}

/**
 * Вывод формы добавления элемента
 *
 */
function fPlugin_Maker_Get_Elem_Form($edit = false)
{
	if (!isset($_GET['pm_id']))
		return;

    $pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title, pm_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings, $pm_elems_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    $params = unserialize($_GET['params']);

    if (!isset($params['subcategs']))
        $params['subcategs'] = 1;

    echo '<script>
        function choose()
        {
            var res = new Object();
            var params = new Array();
    		var el_cats = sbCatTree.getAllSelected();
    ';

    if (!$edit)
    {
    	echo '
            if (el_cats.length == 0)
            {
                alert("'.PL_PLUGIN_MAKER_H_ELEM_FORM_NO_CATEGS_MSG.'");
                return;
            }
        ';
    }
	else
	{
		echo 'params["edit"] = 1;';
	}

    echo '
        	params["ids"] = el_cats;
            var el_temp = sbGetE("temp_id");

            if (el_temp)
            {
                params["temp_id"] = el_temp.value;
            }
            else
            {
                alert("'.PL_PLUGIN_MAKER_H_ELEM_FORM_NO_TEMPS_MSG.'");
                return;
            }';

			if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
			{
		        echo '
		            params["cena"] = sbGetE("cena").value;
					var i = 0;
					el_page = true;
		            while(el_page)
					{
						i++;
						var el_page = sbGetE("full_page" + i);

						if(el_page)
							params[el_page.name] = el_page.value;
					}';
			}

            echo '
            params["page"] = sbGetE("page").value;
            params["premod_elem"] = sbGetE("premod_elem").checked ? 1 : 0;
            params["rubrik_link"] = sbGetE("rubrik_link").checked ? 1 : 0;
            params["admin_email"] = sbGetE("admin_email").value;
            params["mod_emails"] = sbGetE("mod_emails").value;
            params["user_email"] = sbGetE("user_email").value;
			params["pm_id"] = "'.$pm_id.'";

			if(sbGetE("registred_users_edit_link") != null)
            	params["registred_users_edit_link"] = sbGetE("registred_users_edit_link").checked ? 1 : 0;
            if(sbGetE("registred_users_elements_add") != null)
            	params["registred_users_elements_add"] = sbGetE("registred_users_elements_add").checked ? 1 : 0;

            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);

            sbReturnValue(res);
        }
    </script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');

    $layout = new sbLayout();
    $layout->mTableWidth = '100%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_CATEGS_TAB);

    $categs = new sbJustCategs('pl_plugin_'.$pm_id);
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsSelectedIds = (isset($params['ids']) && $params['ids'] != '' ? explode('^', $params['ids']) : array());

    $layout->addField('', new sbLayoutHTML($categs->showTree('', '', '', false), true));
    $categs->init();

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);

    $res_temps = sql_query('SELECT categs.cat_title, temps.ptf_id, temps.ptf_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_form temps WHERE temps.ptf_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_design_form" ORDER BY categs.cat_left, temps.ptf_title');
    if ($res_temps)
    {
        $options = array();
        $old_cat_title = '';
        foreach ($res_temps as $value)
        {
            list($cat_title, $ptf_id, $ptf_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$ptf_id] = $ptf_title;
        }

		$fld = new sbLayoutSelect($options, 'temp_id');
		$fld->mSelOptions = isset($params['temp_id']) ? array($params['temp_id']) : array();

		$layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, $fld);
	}
	else
    {
        $layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FORM_NO_TEMPS_DESC, $pm_title, isset($pm_settings['form_component_title']) ? $pm_settings['form_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FORM).'</div>', '', '', false));
    }

    $layout->addField('', new sbLayoutDelim());
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_FORM_PAGE, new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 470px;"'));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField($edit ? PL_PLUGIN_MAKER_H_ELEM_FORM_EDIT_PREMOD : PL_PLUGIN_MAKER_H_ELEM_FORM_PREMOD, new sbLayoutInput('checkbox', '1', 'premod_elem', 'premod_elem', (isset($params['premod_elem']) && $params['premod_elem'] == 1 ? 'checked="checked"' : '' )));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_RUBRIK_LINK, new sbLayoutInput('checkbox', '1', 'rubrik_link', 'rubrik_link', (isset($params['rubrik_link']) && $params['rubrik_link'] == 1 ? 'checked="checked"' : '' )));

    $layout->addField('', new sbLayoutDelim());

    $pd_fields = array();
    $pd_categs = array();

    $admin_mail_fields = array(-1 => ' --- ');
    $user_mail_fields = array(-1 => ' --- ');

    $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
    if ($res)
    {
    	list($pd_fields) = $res[0];

    	if ($pd_fields != '')
    		$pd_fields = unserialize($pd_fields);

    	if ($pd_fields)
    	{
	    	foreach ($pd_fields as $key => $value)
			{
				if ($value['type'] == 'string')
			    {
			    	$user_mail_fields[$value['id']] = $value['title'];
			    }
				elseif ($value['type'] == 'select_sprav' || $value['type'] == 'multiselect_sprav' ||
					$value['type'] == 'radio_sprav' || $value['type'] == 'checkbox_sprav' ||
					$value['type'] == 'link_sprav')
			    {
			    	$admin_mail_fields[$value['id']] = $value['title'];
			    }
			}
    	}
    }

    $fld = new sbLayoutSelect($admin_mail_fields, 'admin_email');
    $fld->mSelOptions = isset($params['admin_email']) ? array($params['admin_email']) : array();
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_H_ELEM_FORM_ADMIN_EMAIL_DESC.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_FORM_ADMIN_EMAIL, $fld);

		if($edit)
		{
			$fld = new sbLayoutInput('checkbox', '1', '', 'registred_users_edit_link', (isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 ? 'checked="checked"' : ''));
			$fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_EDIT_AUTH_ONLY_LABEL.'</div>';
			$layout->addField(PL_PLUGIN_MAKER_H_EDIT_LINK_USERS_NEWS, $fld);
		}
		else
		{
			$fld = new sbLayoutInput('checkbox', '1', '', 'registred_users_elements_add', (isset($params['registred_users_elements_add']) && $params['registred_users_elements_add'] == 1 ? 'checked="checked"' : ''));
			$fld->mHTML = '<div class="hint_div">'.PL_PLUGIN_MAKER_H_ADD_AUTH_ONLY_LABEL.'</div>';
			$layout->addField(PL_PLUGIN_MAKER_H_ADD_AUTH_ONLY, $fld);
		}

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_FORM_MOD_EMAILS, new sbLayoutInput('input', (isset($params['mod_emails']) ? $params['mod_emails'] : ''), 'mod_emails', '', 'style="width: 510px;"'));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($user_mail_fields, 'user_email');
    $fld->mSelOptions = isset($params['user_email']) ? array($params['user_email']) : array();
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_PLUGIN_MAKER_H_ELEM_FORM_USER_EMAIL_DESC.'</div>';
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_FORM_USER_EMAIL, $fld);

	if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
	{
		$layout->addTab(PL_PLUGIN_MAKER_H_BASKET_TAB);
		$layout->addHeader(PL_PLUGIN_MAKER_H_BASKET_TAB);

		$options = array();
		$options[1] = PL_PLUGIN_MAKER_H_CENA_1;
		$options[2] = PL_PLUGIN_MAKER_H_CENA_2;
		$options[3] = PL_PLUGIN_MAKER_H_CENA_3;
		$options[4] = PL_PLUGIN_MAKER_H_CENA_4;
		$options[5] = PL_PLUGIN_MAKER_H_CENA_5;

		$fld = new sbLayoutSelect($options, 'cena');
		$fld->mSelOptions = isset($params['cena']) ? array($params['cena']) : array();
		$layout->addfield(PL_PLUGIN_MAKER_H_ELEM_CENA_FIELD_TITLE, $fld);

		$layout->addfield('', new sbLayoutDelim());

		$res = sql_param_query('SELECT pm_id, pm_title FROM sb_plugins_maker WHERE pm_id != ?d', $pm_id);
		if($res)
		{
			for($i = 1, $y = 0; $i <= count($res); $i++, $y++)
			{
				list($pm_id, $pm_title) = $res[$y];
				$layout->addField(sprintf(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE_FOR_BASKET_LIST, $pm_title), new sbLayoutPage(isset($params['full_page'.$pm_id]) ? $params['full_page'.$pm_id] : '', 'full_page'.$pm_id, 'full_page'.$i, 'style="width:470px;"'));
			}
		}
	}

	$layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="choose();"'.($res_temps ? '' : ' disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

/**
 * Вывод облака тегов
 */
function fPlugin_Maker_Get_Elem_Cloud()
{
	if (!isset($_GET['pm_id']))
	    return;

    $pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title, pm_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings, $pm_elems_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();

    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');
    $params = unserialize($_GET['params']);

    if (!isset($params['filter']))
        $params['filter'] = 'all';

    if (!isset($params['subcategs']))
        $params['subcategs'] = 1;

    echo '<script>
        function chooseCat()
        {
            var res = new Object();
            var params = new Array();

            var el_cats = sbCatTree.getAllSelected();
            if (el_cats.length == 0)
            {
                alert("'.PL_PLUGIN_MAKER_H_ELEM_CLOUD_NO_CATEGS_MSG.'");
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
                alert("'.PL_PLUGIN_MAKER_H_ELEM_CLOUD_NO_TEMPS_ALERT.'");
                return;
            }

            var r_filter_all = sbGetE("r_filter_all");
            var r_filter_last = sbGetE("r_filter_last");
            var r_filter_next = sbGetE("r_filter_next");
            var r_filter_from_to = sbGetE("r_filter_from_to");

            if (r_filter_all.checked)
            {
                params["filter"] = r_filter_all.value;
            }
            else if (r_filter_from_to.checked)
            {
                params["filter"] = r_filter_from_to.value;
                var el_filter_from = sbGetE("spin_filter_from");
                var el_filter_to = sbGetE("spin_filter_to");

                if (el_filter_to.value != "" && el_filter_to.value != 0 && parseInt(el_filter_from.value) > parseInt(el_filter_to.value))
                {
                    alert("'.PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_FROM_TO_ERROR.'");
                    return;
                }

                params["filter_from"] = el_filter_from.value;
                params["filter_to"] = el_filter_to.value;
            }

            params["sort"] = sbGetE("sort").value;
            params["order"] = sbGetE("order").value;
            params["page"] = sbGetE("page").value;
            params["subcategs"] = sbGetE("subcategs").checked ? 1 : 0;
            params["rubrikator"] = sbGetE("rubrikator").checked ? 1 : 0;
            params["pm_id"] = "'.$pm_id.'";

            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);

            sbReturnValue(res);
        }
    </script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $layout->mTableWidth = '100%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_CATEGS_TAB);

    $categs = new sbJustCategs('pl_plugin_'.$pm_id);
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsSelectedIds = (isset($params['ids']) && $params['ids'] != '' ? explode('^', $params['ids']) : array());

    $layout->addField('', new sbLayoutHTML($categs->showTree('', '', '', false), true));
    $categs->init();

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_PROPS_TAB);

    $design_found = fClouds_Design_Get($layout, (isset($_GET['temp_id']) ? $_GET['temp_id'] : -1), 'temp_id');
	$layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 400px;"');
    $fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_CLOUD_PAGE_HINT, $pm_title, isset($pm_settings['list_component_title']) ? $pm_settings['list_component_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST).'</div>';

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_CLOUD_PAGE, $fld);
    $layout->addField('', new sbLayoutDelim());

    $fields = array('ct.ct_tag' => PL_PLUGIN_MAKER_H_ELEM_CLOUD_SORT_TAG,
    				'ct_rating' => PL_PLUGIN_MAKER_H_ELEM_CLOUD_SORT_RATING);

    $order = array('DESC' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_DESC,
                   'ASC'  => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_ASC);

    $fld1 = new sbLayoutSelect($fields, 'sort');
    $fld1->mSelOptions = array((isset($params['sort']) ? $params['sort'] : 'tag'));
    $fld2 = new sbLayoutSelect($order, 'order');
    $fld2->mSelOptions = array((isset($params['order']) && $params['order'] != '' ? $params['order'] : 'ASC'));
    $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_FIELD, $fld1);

    $layout->addField('', new sbLayoutDelim());
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_CLOUD_SUBCATEGS, new sbLayoutInput('checkbox', '1', 'subcategs', '', (isset($params['subcategs']) && $params['subcategs'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_CLOUD_RUBRIK_LINK, new sbLayoutInput('checkbox', '1', 'rubrikator', '', (isset($params['rubrikator']) && $params['rubrikator'] == 1 ? 'checked="checked"' : '')));

    $layout->addTab(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TAB);
    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_LIST_FILTER_TAB);

    $html = '';
    $fld1 = new sbLayoutInput('radio', 'all', 'filter', 'r_filter_all', ($params['filter'] == 'all' ? ' checked="checked"' : ''));
    $fld1->mHTML = '&nbsp;&nbsp;'.PL_PLUGIN_MAKER_H_ELEM_CLOUD_FILTER_ALL;
    $html .= $fld1->getField().'<br /><br />';

    $fld3 = new sbLayoutInput('radio', 'from_to', 'filter', 'r_filter_from_to', ($params['filter'] == 'from_to' ? ' checked="checked"' : ''));
    $fld3_1 = new sbLayoutInput('input', (isset($params['filter_from']) ? $params['filter_from'] : ''), 'filter_from', 'spin_filter_from', 'style="width: 50px;"');
    $fld3_2 = new sbLayoutInput('input', (isset($params['filter_to']) ? $params['filter_to'] : ''), 'filter_to', 'spin_filter_to', 'style="width: 50px;"');
    $fld3_1->mMinValue = 1;
    $html .= $fld3_1->getJavaScript().'<table cellpadding="0" cellspacing="0"><tr><td>'.$fld3->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_FROM.'&nbsp;&nbsp;</td><td>'.$fld3_1->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_TO.'&nbsp;&nbsp;</td><td>'.$fld3_2->getField().'</td></tr></table>
              <div class="hint_div">'.PL_PLUGIN_MAKER_H_ELEM_CLOUD_FILTER_FROM_TO_HINT.'</div>';

    $fld = new sbLayoutHTML($html);
    $layout->addField(PL_PLUGIN_MAKER_H_ELEM_CLOUD_FILTER, $fld);

    $layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="chooseCat();"'.($design_found ? '' : ' disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

/**
 * Вывод формы фильтра
 *
 */
function fPlugin_Maker_Get_Elem_Filter()
{
	if (!isset($_GET['pm_id']))
	    return;

    $pm_id = intval($_GET['pm_id']);

	$res = sql_query('SELECT pm_title, pm_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');

	if (!$res)
		return;

	list($pm_title, $pm_settings) = $res[0];

	if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

	$params = unserialize($_GET['params']);
	echo '<script>
		function choose()
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
				alert("'.PL_PLUGIN_MAKER_H_ELEM_FILTER_NO_TEMPS_ALERT.'");
				return;
			}
			params["page"] = sbGetE("page").value;
			params["pm_id"] = "'.$pm_id.'";

            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);

            sbReturnValue(res);
        }
	</script>';

	echo '<br /><br />';
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $layout = new sbLayout();
    $layout->mTableWidth = '90%';
    $layout->mTitleWidth = '200';

    $layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_FILTER);
	$res = sql_query('SELECT categs.cat_title, temps.ptf_id, temps.ptf_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_form temps WHERE temps.ptf_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_filter" ORDER BY categs.cat_left, temps.ptf_title');

	if ($res)
	{
		$options = array();
		$old_cat_title = '';
		foreach ($res as $value)
		{
			list($cat_title, $st_id, $st_title) = $value;
            if ($old_cat_title != $cat_title)
            {
				$options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
			$options[$st_id] = $st_title;
        }

		$fld = new sbLayoutSelect($options, 'temp_id');
		$fld->mSelOptions = isset($params['temp_id']) ? array($params['temp_id']) : array();
		$layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, $fld);
	}
	else
    {
		$layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_FILTER_NO_TEMPS_MSG, $pm_title, isset($pm_settings['filter_component_title']) ? $pm_settings['filter_component_title'] : PL_PLUGIN_MAKER_H_ELEM_FILTER).'</div>', '', '', false));
	}

	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE,  new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 450px;"'));

	$layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="choose();"'.($res ? '' : ' disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

/**
 * Вывод информера сравнения элементов
 *
 */
function fPlugin_Maker_Get_Informer_Elem()
{
	if (!isset($_GET['pm_id']))
	    return;

    $pm_id = intval($_GET['pm_id']);
	$res = sql_query('SELECT pm_title, pm_settings, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
    if (!$res)
        return;

    list($pm_title, $pm_settings, $pm_elems_settings) = $res[0];

    if ($pm_settings != '')
        $pm_settings = unserialize($pm_settings);
    else
        $pm_settings = array();

    if ($pm_elems_settings != '')
        $pm_elems_settings = unserialize($pm_elems_settings);
    else
        $pm_elems_settings = array();


	$params = unserialize($_GET['params']);
	echo '<script>
		function choose()
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
				alert("'.PL_PLUGIN_MAKER_H_ELEM_INFORMER_NO_TEMPS_ALERT.'");
				return;
			}

			params["pm_id"] = "'.$pm_id.'";
			params["page"] = sbGetE("page").value;

            res.temp_id = el_temp.value;
            res.params = sb_serialize(params);
			sbReturnValue(res);
		}
	</script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');

	$layout = new sbLayout();
	$layout->mTableWidth = '95%';
	$layout->mTitleWidth = '200';

	echo '<br /><br />';
	$layout->addHeader(PL_PLUGIN_MAKER_H_ELEM_INFORMER);

	$res = sql_query('SELECT categs.cat_title, temps.ptf_id, temps.ptf_title
			FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_form temps
			WHERE temps.ptf_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_maker_informer"
			ORDER BY categs.cat_left, temps.ptf_title');

	if ($res)
	{
		$options = array();

		$old_cat_title = '';
		foreach ($res as $value)
		{
			list($cat_title, $st_id, $st_title) = $value;

			if ($old_cat_title != $cat_title)
			{
				$options[uniqid()] = '-'.$cat_title;
				$old_cat_title = $cat_title;
			}
			$options[$st_id] = $st_title;
		}

		$fld = new sbLayoutSelect($options, 'temp_id');
		$fld->mSelOptions = isset($params['temp_id']) ? array($params['temp_id']) : array();
		$layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, $fld);
	}
	else
	{
		$layout->addField(PL_PLUGIN_MAKER_H_ELEM_TEMP, new sbLayoutLabel('<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_INFORMER_NO_TEMPS_MSG, $pm_title, isset($pm_settings['informer_component_title']) ? $pm_settings['informer_component_title'] : PL_PLUGIN_MAKER_H_ELEM_INFORMER).'</div>', '', '', false));
	}

	$layout->addField('', new sbLayoutDelim());
	$fld = new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 480px;"');
	$fld->mHTML = '<div class="hint_div">'.sprintf(PL_PLUGIN_MAKER_H_ELEM_INFORMER_DESCR, $pm_title, isset($pm_settings['informer_component_title']) ? $pm_settings['informer_component_title'] : PL_PLUGIN_MAKER_H_ELEM_INFORMER).'</div>';
	$layout->addField(PL_PLUGIN_MAKER_H_ELEM_INFORMER_PAGE, $fld);

	$layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="choose();"'.($res ? '' : ' disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

/********************************** функции получения информации по элементам модуля ***********************************/

/**
 * Вывод списка элементов
 */
function fPlugin_Maker_Elem_List_Info($params)
{
	$pm_id = intval($params['pm_id']);

    $in_str = implode(',', explode('^', $params['ids']));
    $in_str = '(0,'.$in_str.')';

    $categs_str = '';
    $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id IN '.$in_str.' ORDER BY cat_left');
    if ($res)
    {
        $categs_str = '';
        foreach ($res as $value)
        {
            $categs_str .= $value[0].', ';
        }
        $categs_str = substr($categs_str, 0, -2);
    }

    $res = sql_param_query('SELECT categs.cat_title, temps.ptl_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_list temps
                            WHERE temps.ptl_id=?d AND links.link_el_id=temps.ptl_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_design_list"', $params['temp_id']);

    echo '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_CATEGS.':</b> '.($categs_str != '' ? $categs_str : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_CATEGS_WAS_DELETED_MSG.'</span>').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_SUBCATEGS.':</b> '.(isset($params['subcategs']) && $params['subcategs'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_RUBRIK_LINK.':</b> '.(isset($params['rubrikator']) && $params['rubrikator'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_CLOUD_LINK.':</b> '.(isset($params['cloud']) && $params['cloud'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_CALENDAR_LINK.':</b> '.(isset($params['calendar']) && $params['calendar'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_FILTER_LINK.':</b> '.(isset($params['use_filter']) && $params['use_filter'] == 1 || !isset($params['use_filter']) ? KERNEL_YES : KERNEL_NO).'</div>
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_PREMOD_COMMENTS.':</b> '.(isset($params['moderate']) && $params['moderate'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
    	  <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_MODERATE_EMAILS.':</b> '.(isset($params['moderate_email']) ? $params['moderate_email'] : '').'</div>';
}

/**
 * Вывод выбранного элемента
 *
 */
function fPlugin_Maker_Elem_Full_Info($params)
{
	$pm_id = intval($params['pm_id']);

    $res = sql_param_query('SELECT categs.cat_title, temps.ptf_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_full temps
                          WHERE temps.ptf_id=?d AND links.link_el_id=temps.ptf_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_design_full"', $params['temp_id']);

    echo '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_FULL_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_PREMOD_COMMENTS.':</b> '.(isset($params['moderate']) && $params['moderate'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
    	  <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_MODERATE_EMAILS.':</b> '.(isset($params['moderate_email']) ? $params['moderate_email'] : '').'</div>';
}

/**
 * Вывод разделов
 *
 */
function fPlugin_Maker_Elem_Categs_Info($params)
{
	$pm_id = intval($params['pm_id']);
    fCategs_Elem_Categs_Info($params, 'pl_plugin_'.$pm_id.'_design_categs');
}

/**
 * Вывод выбранного раздела
 *
 */
function fPlugin_Maker_Elem_Sel_Cat_Info($params)
{
    $pm_id = intval($params['pm_id']);
    fCategs_Elem_Sel_Cat_Info($params, 'pl_plugin_'.$pm_id.'_design_sel_cat');
}

/**
 * Вывод формы добавления элементов
 *
 */
function fPlugin_Maker_Elem_Form_Info($params)
{
    $pm_id = intval($params['pm_id']);

    $in_str = implode(',', explode('^', $params['ids']));
    $in_str = '(0,'.$in_str.')';

    $categs_str = '';
    $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id IN '.$in_str.' ORDER BY cat_left');
    if ($res)
    {
        $categs_str = '';
        foreach ($res as $value)
        {
            $categs_str .= $value[0].', ';
        }
        $categs_str = substr($categs_str, 0, -2);
    }

    $res = sql_param_query('SELECT categs.cat_title, temps.ptf_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_form temps
                            WHERE temps.ptf_id=?d AND links.link_el_id=temps.ptf_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_plugin_'.$pm_id.'_design_form"', $params['temp_id']);

    echo '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_CATEGS.':</b> '.($categs_str != '' ? $categs_str : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_CATEGS_WAS_DELETED_MSG.'</span>').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_FORM_INFO_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_FORM_INFO_PREMOD.':</b> '.(isset($params['premod_elem']) && $params['premod_elem'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_RUBRIK_LINK.':</b> '.(isset($params['rubrik_link']) && $params['rubrik_link'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_MODERATE_EMAILS.':</b> '.(isset($params['mod_emails']) ? $params['mod_emails'] : '').'</div>';
}

/**
 * Вывод облака тегов
 */
function fPlugin_Maker_Elem_Cloud_Info($params)
{
	$pm_id = intval($params['pm_id']);

    $in_str = implode(',', explode('^', $params['ids']));
    $in_str = '(0,'.$in_str.')';

    $categs_str = '';
    $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id IN '.$in_str.' ORDER BY cat_left');
    if ($res)
    {
        $categs_str = '';
        foreach ($res as $value)
        {
            $categs_str .= $value[0].', ';
        }
        $categs_str = substr($categs_str, 0, -2);
    }

    $res = sql_param_query('SELECT categs.cat_title, temps.ct_title FROM sb_categs categs, sb_catlinks links, sb_clouds_temps temps
							WHERE temps.ct_id=?d AND links.link_el_id=temps.ct_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_clouds"', $params['temp_id']);

    echo '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_CATEGS.':</b> '.($categs_str != '' ? $categs_str : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_CATEGS_WAS_DELETED_MSG.'</span>').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_CLOUD_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_SUBCATEGS.':</b> '.(isset($params['subcategs']) && $params['subcategs'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_INFO_RUBRIK_LINK.':</b> '.(isset($params['rubrikator']) && $params['rubrikator'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>';
}

/**
 * Вывод формы фильтра
 *
 */
function fPlugin_Maker_Elem_Flter_Info($params)
{
	$pm_id = intval($params['pm_id']);

	$res = sql_param_query('SELECT categs.cat_title, temps.ptf_title FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_form temps
			WHERE temps.ptf_id=?d AND links.link_el_id=temps.ptf_id AND categs.cat_id=links.link_cat_id
			AND categs.cat_ident="pl_plugin_'.$pm_id.'_filter"', $params['temp_id']);

	echo '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
			<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_FULL_PAGE.':</b> '.($params['page'] != '' ? $params['page'] : '').'</div>';
}


function fPlugin_Maker_Elem_Informer_Info($params)
{
	$pm_id = intval($params['pm_id']);

	$res = sql_param_query('SELECT categs.cat_title, temps.ptf_title
                    FROM sb_categs categs, sb_catlinks links, sb_plugins_temps_form temps
					WHERE temps.ptf_id=?d
					AND links.link_el_id=temps.ptf_id
					AND categs.cat_id=links.link_cat_id
					AND categs.cat_ident="pl_plugin_'.$pm_id.'_maker_informer"', $params['temp_id']);

	echo '<div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_PLUGIN_MAKER_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_PLUGIN_MAKER_H_ELEM_INFORMER_PAGE.':</b> '.(isset($params['page']) && $params['page'] != '' ? $params['page'] : '').'</div>';
}


/********************************** функции получения элементов модуля ***********************************/

/**
 * Вывод списка элементов
 */
function fPlugin_Maker_Elem_List_Com($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_List('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

/**
 * Вывод выбранного элемента
 *
 */
function fPlugin_Maker_Elem_Full_Com($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Full('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

/**
 * Вывод названия элемента (HTML)
 *
 */
function fPlugin_Maker_Elem_Header_Html($el_id, $temp_id, $params, $tag_id, $e_ident)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Header_Html('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id', '$e_ident');\n
        ?>";
}

/**
 * Вывод названия элемента (без форматирования)
 *
 */
function fPlugin_Maker_Elem_Header_Plain($el_id, $temp_id, $params, $tag_id, $e_ident)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Header_Plain('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id', '$e_ident');\n
        ?>";
}

/**
 * Вывод разделов
 *
 */
function fPlugin_Maker_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Categs('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

/**
 * Вывод выбранного раздела
 *
 */
function fPlugin_Maker_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Sel_Cat('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

/**
 * Вывод формы
 *
 */
function fPlugin_Maker_Elem_Form($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Form('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

/**
 * Вывод облака тегов
 *
 */
function fPlugin_Maker_Elem_Cloud($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
        fPlugin_Maker_Elem_Cloud('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

/**
 * Вывод формы фильтра
 *
 */
function fPlugin_Maker_Elem_Filter($el_id, $temp_id, $params, $tag_id)
{
	return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
		fPlugin_Maker_Elem_Filter('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
		?>";
}

function fPlugin_Maker_Elem_Informer_Prog($el_id, $temp_id, $params, $tag_id)
{
	return "<?php include_once SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php';\n
		fPlugin_Maker_Elem_Informer('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
		?>";
}

function fPlugin_Maker_Elem_Components()
{
	if (isset($_GET['p_url']) && isset($_GET['p_id']))
	{
		echo sb_get_elem_components($_GET['p_url'],'pl_plugin_'.(int) $_GET['p_id'].'_list');
	}
}

?>