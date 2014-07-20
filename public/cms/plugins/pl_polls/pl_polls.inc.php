<?php

function fPolls_Get_Elem_List()
{
	$params = unserialize($_GET['params']);

    if (!isset($params['filter']))
        $params['filter'] = 'all';

    echo '<script>
        function chooseCat()
        {
            var res = new Object();
            var params = new Array();
            
            var el_cats = sbCatTree.getAllSelected();
            if (el_cats.length == 0)
            {
                alert("'.PL_POLLS_H_ELEM_LIST_NO_CATEGS_MSG.'");
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
                alert("'.PL_POLLS_H_ELEM_LIST_NO_TEMPS_ALERT.'");
                return;
            }

            var r_filter_all = sbGetE("r_filter_all");
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
					alert("'.PL_POLLS_H_ELEM_LIST_FILTER_FROM_TO_ERROR.'");
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
            params["subcategs"] = sbGetE("subcategs").checked ? 1 : 0;
            params["show_hidden"] = sbGetE("show_hidden").checked ? 1 : 0;
            params["rubrikator"] = sbGetE("rubrikator").checked ? 1 : 0;

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
    </script>';
    
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');
    
    $layout = new sbLayout();
    $layout->mTableWidth = '100%';
    $layout->mTitleWidth = '200';
    
    $layout->addTab(PL_POLLS_H_ELEM_LIST_CATEGS_TAB);
    
    $categs = new sbJustCategs('pl_polls');
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsSelectedIds = (isset($params['ids']) && $params['ids'] != '' ? explode('^', $params['ids']) : array());
    
    $layout->addField('', new sbLayoutHTML($categs->showTree('', '', '', false), true));
    $categs->init();
    
    $layout->addTab(PL_POLLS_H_ELEM_LIST_PROPS_TAB);
    $layout->addHeader(PL_POLLS_H_ELEM_LIST_PROPS_TAB);
    
    $options = array();
    $res = sql_query('SELECT categs.cat_title, temps.spt_id, temps.spt_title FROM sb_categs categs, sb_catlinks links, sb_polls_temps temps WHERE temps.spt_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_polls_design_list" ORDER BY categs.cat_left, temps.spt_title');
    if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $spt_id, $spt_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$spt_id] = $spt_title;
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
		$fld = new sbLayoutLabel('<div class="hint_div">'.PL_POLLS_H_ELEM_LIST_NO_TEMPS_MSG.'</div>', '', '', false);
	}

	$layout->addField(PL_POLLS_H_ELEM_TEMP, $fld);
	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutPage(isset($params['page']) ? $params['page'] : '', 'page', '', 'style="width: 450px;"');
	$fld->mHTML = '<div class="hint_div">'.PL_POLLS_H_ELEM_RESULT_PAGE_HINT.'</div>';

	$layout->addField(PL_POLLS_H_ELEM_RESULT_PAGE, $fld);
	$layout->addField('', new sbLayoutDelim());

    $fields = sbPlugins::getSortFields('pl_polls');
    
    $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_polls"');
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
				}
			}
		}
	}

	$order = array('DESC' => PL_POLLS_H_ELEM_LIST_SORT_DESC, 
					'ASC'  => PL_POLLS_H_ELEM_LIST_SORT_ASC);

    $fld1 = new sbLayoutSelect($fields, 'sort1', '', 'onchange="changeSort(this, 1);"');
    $fld1->mSelOptions = array((isset($params['sort1']) ? $params['sort1'] : ''));
    $fld2 = new sbLayoutSelect($order, 'order1');
    $fld2->mSelOptions = array((isset($params['order1']) && $params['order1'] != '' ? $params['order1'] : 'DESC'));
    $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

    $layout->addField(PL_POLLS_H_ELEM_LIST_SORT_FIELD.' 1', $fld1);

    $fld1 = new sbLayoutSelect($fields, 'sort2', '', 'onchange="changeSort(this, 2);"');
    $fld1->mSelOptions = array((isset($params['sort2']) && $params['sort1'] != '' ? $params['sort2'] : ''));
    $fld2 = new sbLayoutSelect($order, 'order2');
    $fld2->mSelOptions = array((isset($params['order2']) && $params['order1'] != '' ? $params['order2'] : ''));
    $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

    $layout->addField(PL_POLLS_H_ELEM_LIST_SORT_FIELD.' 2', $fld1);

	$fld1 = new sbLayoutSelect($fields, 'sort3', '', 'onchange="changeSort(this, 3);"');
	$fld1->mSelOptions = array((isset($params['sort3']) && $params['sort1'] != '' ? $params['sort3'] : ''));
	$fld2 = new sbLayoutSelect($order, 'order3');
	$fld2->mSelOptions = array((isset($params['order3']) && $params['order3'] != '' ? $params['order3'] : ''));
	$fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

	$layout->addField(PL_POLLS_H_ELEM_LIST_SORT_FIELD.' 3', $fld1);

	$layout->addField('', new sbLayoutDelim());
	$layout->addField(PL_POLLS_H_ELEM_LIST_SUBCATEGS, new sbLayoutInput('checkbox', '1', 'subcategs', '', (isset($params['subcategs']) && $params['subcategs'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_POLLS_H_ELEM_LIST_SHOW_HIDDEN, new sbLayoutInput('checkbox', '1', 'show_hidden', '', (isset($params['show_hidden']) && $params['show_hidden'] == 1 ? 'checked="checked"' : '')));
	$layout->addField(PL_POLLS_H_ELEM_LIST_RUBRIK_LINK, new sbLayoutInput('checkbox', '1', 'rubrikator', '', (isset($params['rubrikator']) && $params['rubrikator'] == 1 ? 'checked="checked"' : '')));

	$layout->addTab(PL_POLLS_H_ELEM_LIST_FILTER_TAB);
	$layout->addHeader(PL_POLLS_H_ELEM_LIST_FILTER_TAB);

	$html = '';
	$fld1 = new sbLayoutInput('radio', 'all', 'filter', 'r_filter_all', ($params['filter'] == 'all' ? ' checked="checked"' : ''));
	$fld1->mHTML = '&nbsp;&nbsp;'.PL_POLLS_H_ELEM_LIST_FILTER_ALL;
	$html .= $fld1->getField().'<br /><br />';

    $fld3 = new sbLayoutInput('radio', 'from_to', 'filter', 'r_filter_from_to', ($params['filter'] == 'from_to' ? ' checked="checked"' : ''));
    $fld3_1 = new sbLayoutInput('input', (isset($params['filter_from']) ? $params['filter_from'] : ''), 'filter_from', 'spin_filter_from', 'style="width: 50px;"');
    $fld3_2 = new sbLayoutInput('input', (isset($params['filter_to']) ? $params['filter_to'] : ''), 'filter_to', 'spin_filter_to', 'style="width: 50px;"');
    $fld3_1->mMinValue = 1;
    $html .= '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld3_1->getJavaScript().$fld3->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_FROM.'&nbsp;&nbsp;</td><td>'.$fld3_1->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_TO.'&nbsp;&nbsp;</td><td>'.$fld3_2->getField().'</td></tr></table>
              <div class="hint_div">'.PL_POLLS_H_ELEM_LIST_FILTER_FROM_TO_HINT.'</div>';

    $fld = new sbLayoutHTML($html);
    $layout->addField(PL_POLLS_H_ELEM_LIST_FILTER, $fld);

    $layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="chooseCat();"'.(count($options) > 0 ? '' : ' disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');
    
    $layout->show();
}

function fPolls_Get_Elem_Results()
{
	$params = unserialize($_GET['params']);

    if (!isset($params['filter']))
		$params['filter'] = 'all';

	echo '<script>
        function chooseCat()
        {
               
            var res = new Object();
            var params = new Array();
            
            var el_cats = sbCatTree.getAllSelected();
            if (el_cats.length == 0)
            {
                alert("'.PL_POLLS_H_ELEM_LIST_NO_CATEGS_RESULT_MSG.'");
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
				alert("'.PL_POLLS_H_ELEM_LIST_NO_TEMPS_RESULT_ALERT.'");
				return;
            }

            var r_filter_all = sbGetE("r_filter_all");
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
					alert("'.PL_POLLS_H_ELEM_LIST_FILTER_FROM_TO_ERROR.'");
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
            
            params["subcategs"] = sbGetE("subcategs").checked ? 1 : 0;
            params["show_hidden"] = sbGetE("show_hidden").checked ? 1 : 0;
            params["rubrikator"] = sbGetE("rubrikator").checked ? 1 : 0;
            params["polls_list"] = sbGetE("polls_list").checked ? 1 : 0;

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
    </script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');
    
	$layout = new sbLayout();
	$layout->mTableWidth = '100%';
	$layout->mTitleWidth = '200';

	$layout->addTab(PL_POLLS_H_ELEM_LIST_CATEGS_TAB);

	$categs = new sbJustCategs('pl_polls');
    $categs->mCategsUseRights = false;
    $categs->mCategsMenu = false;
    $categs->mCategsMultiSelect = true;
    $categs->mCategsSelectedIds = (isset($params['ids']) && $params['ids'] != '' ? explode('^', $params['ids']) : array());

	$layout->addField('', new sbLayoutHTML($categs->showTree('', '', '', false), true));
	$categs->init();

    $layout->addTab(PL_POLLS_H_ELEM_LIST_PROPS_TAB);
    $layout->addHeader(PL_POLLS_H_ELEM_LIST_PROPS_TAB);

	$options = array();
	$res = sql_query('SELECT categs.cat_title, temps.sptr_id, temps.sptr_title FROM sb_categs categs, sb_catlinks links, sb_polls_temps_results temps WHERE temps.sptr_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_polls_design_result" ORDER BY categs.cat_left, temps.sptr_title');
    if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $sptr_id, $sptr_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$sptr_id] = $sptr_title;
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
		$fld = new sbLayoutLabel('<div class="hint_div">'.PL_POLLS_H_ELEM_LIST_NO_TEMPS_RESULT_MSG.'</div>', '', '', false);
	}

	$layout->addField(PL_POLLS_H_ELEM_TEMP, $fld);
	$layout->addField('', new sbLayoutDelim());


	$fields = sbPlugins::getSortFields('pl_polls');

	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_polls"');
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
	                $value['type'] == 'radio_sprav' )
				{
					$fields['p.user_f_'.$value['id']] = $value['title'];
				}
			}
		}
	}

	$order = array('DESC' => PL_POLLS_H_ELEM_LIST_SORT_DESC, 
					'ASC'  => PL_POLLS_H_ELEM_LIST_SORT_ASC);

    $fld1 = new sbLayoutSelect($fields, 'sort1', '', 'onchange="changeSort(this, 1);"');
    $fld1->mSelOptions = array((isset($params['sort1']) ? $params['sort1'] : ''));
    $fld2 = new sbLayoutSelect($order, 'order1');
    $fld2->mSelOptions = array((isset($params['order1']) && $params['order1'] != '' ? $params['order1'] : 'DESC'));
    $fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

    $layout->addField(PL_POLLS_H_ELEM_LIST_SORT_FIELD.' 1', $fld1);

	$fld1 = new sbLayoutSelect($fields, 'sort2', '', 'onchange="changeSort(this, 2);"');
	$fld1->mSelOptions = array((isset($params['sort2']) && $params['sort1'] != '' ? $params['sort2'] : ''));
	$fld2 = new sbLayoutSelect($order, 'order2');
	$fld2->mSelOptions = array((isset($params['order2']) && $params['order1'] != '' ? $params['order2'] : ''));
	$fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

	$layout->addField(PL_POLLS_H_ELEM_LIST_SORT_FIELD.' 2', $fld1);

	$fld1 = new sbLayoutSelect($fields, 'sort3', '', 'onchange="changeSort(this, 3);"');
	$fld1->mSelOptions = array((isset($params['sort3']) && $params['sort1'] != '' ? $params['sort3'] : ''));
	$fld2 = new sbLayoutSelect($order, 'order3');
	$fld2->mSelOptions = array((isset($params['order3']) && $params['order3'] != '' ? $params['order3'] : ''));
	$fld1->mHTML = '&nbsp;&nbsp;'.$fld2->getField();

	$layout->addField(PL_POLLS_H_ELEM_LIST_SORT_FIELD.' 3', $fld1);

	$layout->addField('', new sbLayoutDelim());
	$layout->addField(PL_POLLS_H_ELEM_LIST_RESULT_SUBCATEGS, new sbLayoutInput('checkbox', '1', 'subcategs', '', (isset($params['subcategs']) && $params['subcategs'] == 1 ? 'checked="checked"' : '')));
    $layout->addField(PL_POLLS_H_ELEM_LIST_RESULT_SHOW_HIDDEN, new sbLayoutInput('checkbox', '1', 'show_hidden', '', (isset($params['show_hidden']) && $params['show_hidden'] == 1 ? 'checked="checked"' : '')));
	$layout->addField(PL_POLLS_H_ELEM_LIST_RUBRIK_LINK, new sbLayoutInput('checkbox', '1', 'rubrikator', '', (isset($params['rubrikator']) && $params['rubrikator'] == 1 ? 'checked="checked"' : '')));
	$layout->addField(PL_POLLS_H_ELEM_LIST_RUBRIK_RESULT_LINK, new sbLayoutInput('checkbox', '1', 'polls_list', '', (isset($params['polls_list']) && $params['polls_list'] == 1 ? 'checked="checked"' : '')));

	$layout->addTab(PL_POLLS_H_ELEM_LIST_FILTER_TAB);
	$layout->addHeader(PL_POLLS_H_ELEM_LIST_FILTER_TAB);

	$html = '';
	$fld1 = new sbLayoutInput('radio', 'all', 'filter', 'r_filter_all', ($params['filter'] == 'all' ? ' checked="checked"' : ''));
	$fld1->mHTML = '&nbsp;&nbsp;'.PL_POLLS_H_ELEM_LIST_FILTER_ALL;
	$html .= $fld1->getField().'<br /><br />';

	$fld3 = new sbLayoutInput('radio', 'from_to', 'filter', 'r_filter_from_to', ($params['filter'] == 'from_to' ? ' checked="checked"' : ''));
	$fld3_1 = new sbLayoutInput('input', (isset($params['filter_from']) ? $params['filter_from'] : ''), 'filter_from', 'spin_filter_from', 'style="width: 50px;"');
	$fld3_2 = new sbLayoutInput('input', (isset($params['filter_to']) ? $params['filter_to'] : ''), 'filter_to', 'spin_filter_to', 'style="width: 50px;"');
	$fld3_1->mMinValue = 1;
	$html .= $fld3_1->getJavaScript().'<table cellpadding="0" cellspacing="0"><tr><td>'.$fld3->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_FROM.'&nbsp;&nbsp;</td><td>'.$fld3_1->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_TO.'&nbsp;&nbsp;</td><td>'.$fld3_2->getField().'</td></tr></table>
				<div class="hint_div">'.PL_POLLS_H_ELEM_LIST_FILTER_FROM_TO_HINT.'</div>';

	$fld = new sbLayoutHTML($html);
	$layout->addField(PL_POLLS_H_ELEM_LIST_RESULT_FILTER, $fld);

	$layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="chooseCat();"'.(count($options) > 0 ? '' : ' disabled="disabled"'));
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fPolls_Get_Elem_Categs()
{
    fCategs_Get_Elem_Categs('pl_polls', 'pl_polls_design_categs', PL_POLLS_H_ELEM_CATEGS_NO_TEMPS_MSG);
}

function fPolls_Get_Elem_Sel_Cat()
{
    fCategs_Get_Elem_Sel_Cat('pl_polls', 'pl_polls_design_selcat', PL_POLLS_H_ELEM_SELCAT_NO_TEMPS_MSG);
}

/***********************************************************/

function fPolls_Elem_List_Info($params)
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
    $res = sql_param_query('SELECT categs.cat_title, temps.spt_title FROM sb_categs categs, sb_catlinks links, sb_polls_temps temps
							WHERE temps.spt_id=?d AND links.link_el_id=temps.spt_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_polls_design_list"', $params['temp_id']);

    echo '<div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_CATEGS.':</b> '.($categs_str != '' ? $categs_str : '<span style="color:red;">'.PL_POLLS_H_INFO_CATEGS_WAS_DELETED_MSG.'</span>').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_POLLS_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_SUBCATEGS.':</b> '.(isset($params['subcategs']) && $params['subcategs'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_RUBRIK_LINK.':</b> '.(isset($params['rubrikator']) && $params['rubrikator'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>';
}

function fPolls_Elem_Results_Info($params)
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

    $res = sql_param_query('SELECT categs.cat_title, temps.sptr_title FROM sb_categs categs, sb_catlinks links, sb_polls_temps_results temps
							WHERE temps.sptr_id=?d AND links.link_el_id=temps.sptr_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident="pl_polls_design_result"', $params['temp_id']);
        
    echo '<div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_CATEGS.':</b> '.($categs_str != '' ? $categs_str : '<span style="color:red;">'.PL_POLLS_H_INFO_CATEGS_WAS_DELETED_MSG.'</span>').'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_ELEM_TEMP.':</b> '.($res ? $res[0][0].' -&gt; '.$res[0][1] : '<span style="color:red;">'.PL_POLLS_H_INFO_TEMP_WAS_DELETED_MSG.'</span>').'</div>
          '.(isset($params['page']) && $params['page'] != '' ? '<div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_PAGE.':</b> '.$params['page'].'</div>' : '').'
          <div class="delim"></div>
          <div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_SUBCATEGS.':</b> '.(isset($params['subcategs']) && $params['subcategs'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>
          <div style="padding-bottom: 5px;"><b>'.PL_POLLS_H_INFO_RUBRIK_RESULT_LINK.':</b> '.(isset($params['rubrikator']) && $params['rubrikator'] == 1 ? KERNEL_YES : KERNEL_NO).'</div>';

}

function fPolls_Elem_Categs_Info($params)
{
	fCategs_Elem_Categs_Info($params, 'pl_polls_design_categs');
}

function fPolls_Elem_Sel_Cat_Info($params)
{
    fCategs_Elem_Sel_Cat_Info($params, 'pl_polls_design_selcat');
}

/***********************************************************/

function fPolls_Elem_List($el_id, $temp_id, $params, $tag_id)
{
	return "<?php include_once SB_CMS_PL_PATH.'/pl_polls/prog/pl_polls.php'; \n;
			fPolls_Elem_List('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id'); \n;
		?>";
}

function fPolls_Elem_Results($el_id, $temp_id, $params, $tag_id)
{
	return "<?php include_once SB_CMS_PL_PATH.'/pl_polls/prog/pl_polls.php'; \n;
			fPolls_Elem_Results('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id'); \n;
		?>";
}

function fPolls_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_polls/prog/pl_polls.php';\n
        fPolls_Elem_Categs('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

function fPolls_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
{
    return "<?php include_once SB_CMS_PL_PATH.'/pl_polls/prog/pl_polls.php';\n
        fPolls_Elem_Sel_Cat('$el_id', '$temp_id', '".addslashes(serialize($params))."', '$tag_id');\n
        ?>";
}

?>