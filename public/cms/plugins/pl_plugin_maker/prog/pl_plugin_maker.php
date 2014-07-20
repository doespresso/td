<?php

if (!function_exists('fPlugin_Maker_Elem_List'))
{
	/**
	 * Вывод списка элементов
	 *
	 * @param array $ids_elems Массив идентификаторов каталога, которые нужно вывести.
	 */
	function fPlugin_Maker_Elem_List($el_id, $temp_id, $params, $tag_id, $ids_elems = null, $link_level = 0)
	{
		$params = unserialize(stripslashes($params));
		if(!isset($params['filter_text_logic']))
			$params['filter_text_logic'] = 'AND';

		if(!isset($params['filter_logic']))
			$params['filter_logic'] = 'AND';

		if(!isset($params['filter_compare']))
			$params['filter_compare'] = 'IN';

		if(!isset($params['filter_morph']))
			$params['filter_morph'] = 1;

		if(!isset($params['use_filter']))
			$params['use_filter'] = 1;

		if(!isset($params['edit_page']))
			$params['edit_page'] = '';

		if(!isset($params['page']))
			$params['page'] = '';

		$pm_id = intval($params['pm_id']);
	    if(is_null($ids_elems) && (!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1))
		{
			if ($GLOBALS['sbCache']->check('pl_plugin_'.$pm_id, $tag_id, array($el_id, $temp_id, $params)))
				return;
		}

	    $res = sbQueryCache::query('SELECT pm_title, pm_elems_settings, pm_categs_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	    if (!$res)
	    {
			if(is_null($ids_elems) && (!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1))
			{
		        // 	указанный модуль был удален
		        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	    	    return;
		    }
		    else
		    {
				return false;
		    }
	    }

	    if (isset($_GET['pl'.$pm_id.'_sid']))
	    {
			$res_full = sbQueryCache::query('SELECT COUNT(*) FROM sb_plugins_'.$pm_id.' WHERE p_url=? OR p_id=?d', $_GET['pl'.$pm_id.'_sid'], $_GET['pl'.$pm_id.'_sid']);
			if (!$res_full || $res_full[0][0] == 0)
		    	sb_404();
	    }

	    list($pm_title, $pm_elems_settings, $pm_categs_settings) = $res[0];

	    if ($pm_elems_settings != '')
	        $pm_elems_settings = unserialize($pm_elems_settings);
	    else
	        $pm_elems_settings = array();

	    if ($pm_categs_settings != '')
	        $pm_categs_settings = unserialize($pm_categs_settings);
	    else
	        $pm_categs_settings = array();

	    $cat_ids = array();

	    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['pl'.$pm_id.'_scid']) || isset($_GET['pl'.$pm_id.'_cid'])))
	    {
	        // используется связь с выводом разделов и выводить следует элементы из соотв. раздела
	        if (isset($_GET['pl'.$pm_id.'_cid']))
	        {
	        	$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND cat_rubrik=1' : ''), $_GET['pl'.$pm_id.'_cid']);
        		if ($res[0][0] > 0)
	            	$cat_ids[] = intval($_GET['pl'.$pm_id.'_cid']);
	        }
	        else
	        {
	            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_plugin_'.$pm_id.'"'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND cat_rubrik=1' : ''), $_GET['pl'.$pm_id.'_scid']);
	            if ($res)
	            {
	                $cat_ids[] = $res[0][0];
	            }
	            else
	            {
	            	$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND cat_rubrik=1' : ''), $_GET['pl'.$pm_id.'_scid']);
        			if ($res[0][0] > 0)
	                	$cat_ids[] = intval($_GET['pl'.$pm_id.'_scid']);
	            }
	        }

		    if (count($cat_ids) == 0)
		    {
		       	sb_404();
		    }
	    }
	    elseif(isset($params['ids_from']) && $params['ids_from'] == 'id')
	    {
	    	// Если нужно выводить по идентификаторам, все равно достаем разделы, что бы проверить права
	   		$el_ids = array();

	   		// Разбиваю значение на массив
	   		if(sb_strpos($params['ids'], '^') !== false)
	   			$el_ids = explode('^', $params['ids']);
	   		elseif(sb_strpos($params['ids'], ',') !== false)
	   			$el_ids = explode(',', $params['ids']);
	   		else
	   			$el_ids[] = intval($params['ids']);

	    	$res = sbQueryCache::query('SELECT DISTINCT l.link_cat_id FROM sb_catlinks AS l, sb_categs AS c WHERE l.link_el_id IN (?a) AND c.cat_id = l.link_cat_id AND c.cat_ident = ?', $el_ids, 'pl_plugin_'.$pm_id);
	    	if($res)
	    	{
	    		foreach($res as $value)
					$cat_ids[] = $value[0];
	    	}
	    }
	    else
	    {
	    	// Если нужно выводить по разделам
	        $cat_ids = explode('^', $params['ids']);
	    }

	    $num = count($cat_ids);
	    $root_cat_level = -1;

	    if ($num == 0)
	    {
		    return false;
	    }
	    elseif ($num == 1)
	    {
	        $res = sbQueryCache::query('SELECT cat_level FROM sb_categs WHERE cat_id=?d', $cat_ids[0]);
	        if ($res)
	        {
	            $root_cat_level = $res[0][0];
	        }
	    }

	    // если следует выводить подразделы, то вытаскиваем их ID
	    if (isset($params['subcategs']) && $params['subcategs'] == 1)
	    {
			$res = sbQueryCache::query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
								WHERE c2.cat_left <= c.cat_left
								AND c2.cat_right >= c.cat_right
								AND c.cat_ident="pl_plugin_'.$pm_id.'"
								AND c2.cat_ident = "pl_plugin_'.$pm_id.'"
								AND c2.cat_id IN (?a)
								ORDER BY c.cat_left', $cat_ids);
			$cat_ids = array();
	        if ($res)
	        {
	            foreach ($res as $value)
	            {
	                $cat_ids[] = $value[0];
	            }
	        }
	        else
	        {
	        	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['pl'.$pm_id.'_scid']) || isset($_GET['pl'.$pm_id.'_cid'])))
	        	{
	        		sb_404();
	        	}

				//	указанные разделы были удалены
				if(!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1)
				{
	            	$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	            }
	            return false;
	        }
	    }
	    else
	    {
	        $root_cat_level = -1;
	    }

	    // проверяем, есть ли закрытые разделы среди тех, которые надо выводить

	    $comments_read_cat_ids = $cat_ids; // разделы, для которых есть права comments_read
	    $comments_edit_cat_ids = $cat_ids; // разделы, для которых есть права comments_edit
	    $vote_cat_ids = $cat_ids; // разделы, для которых есть права vote

	    if (!isset($pm_categs_settings['site_users_rights']) || isset($pm_categs_settings['site_users_rights']) && $pm_categs_settings['site_users_rights'] == 1)
	    {
    	    $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    	    if ($res)
    	    {
    	        // проверяем права на закрытые разделы и исключаем их из вывода
    	        $closed_ids = array();
    	        foreach ($res as $value)
    	        {
    	            $closed_ids[] = $value[0];
    	        }

    	        $old_num = count($cat_ids);

    	        if(is_null($ids_elems))
    	        {
    				$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_plugin_'.$pm_id.'_read');

    				if ($old_num != count($cat_ids))
    				    $root_cat_level = -1;

    		        $comments_read_cat_ids = sbAuth::checkRights($closed_ids, $comments_read_cat_ids, 'pl_plugin_'.$pm_id.'_comments_read');
    		        $comments_edit_cat_ids = sbAuth::checkRights($closed_ids, $comments_edit_cat_ids, 'pl_plugin_'.$pm_id.'_comments_edit');
    		        $vote_cat_ids = sbAuth::checkRights($closed_ids, $vote_cat_ids, 'pl_plugin_'.$pm_id.'_vote');
    	        }
    	    }
	    }

	    if (count($cat_ids) == 0)
	    {
		    if(is_null($ids_elems) && (!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1))
		    {
		        // нет прав доступа к выбранным разделам
		        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	    	    return;
		    }
			else
			{
				return false;
			}
	    }

		//	вытаскиваем макет дизайна
		//$res = sql_param_query('SELECT ptl_lang, ptl_checked, ptl_count, ptl_top, ptl_categ_top, ptl_element, ptl_empty, ptl_delim,
		//		ptl_categ_bottom, ptl_bottom, ptl_pagelist_id, ptl_perpage, ptl_no_elems, ptl_fields_temps, ptl_categs_temps,
		//		ptl_votes_id, ptl_comments_id, ptl_user_data_id, ptl_tags_list_id
		//		FROM sb_plugins_temps_list WHERE ptl_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_plugins_temps_list', $temp_id);

		if (!$res && is_null($ids_elems) && (!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1))
	    {
	        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], $pm_title), SB_MSG_WARNING);
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }
	    elseif(!$res && !is_null($ids_elems))
	    {
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], $pm_title), SB_MSG_WARNING);
			return false;
		}

		list($ptl_lang, $ptl_checked, $ptl_count, $ptl_top, $ptl_categ_top, $ptl_element, $ptl_empty, $ptl_delim,
	         $ptl_categ_bottom, $ptl_bottom, $ptl_pagelist_id, $ptl_perpage, $ptl_no_elems, $ptl_fields_temps,
	         $ptl_categs_temps, $ptl_votes_id, $ptl_comments_id, $ptl_user_data_id, $ptl_tags_list_id) = $res[0];

	    $ptl_fields_temps = unserialize($ptl_fields_temps);
	    $ptl_categs_temps = unserialize($ptl_categs_temps);

	    $use_pagelist = (sb_substr_count($ptl_top, '{NUM_LIST}') > 0 || sb_substr_count($ptl_bottom, '{NUM_LIST}') > 0);
	    $pt_page_list = '';
	    $pt_perstage = 1;

		if ($use_pagelist && (is_null($ids_elems) || is_array($ids_elems) && $link_level > 0))
		{
	        //	вытаскиваем макет дизайна постраничного вывода
	        $res = sbQueryCache::getTemplate('sb_pager_temps', $ptl_pagelist_id);

	        if ($res)
	        {
	            list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
	        }
	    }

		$user_link_id_sql = '';
		if(isset($params['registred_users']) && $params['registred_users'] == 1)
	    {
			if(isset($_SESSION['sbAuth']))
			{
				$user_link_id_sql = ' AND p.p_user_id = '.intval($_SESSION['sbAuth']->getUserId());
			}
			else
			{
				if(!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1)
				{
					$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $ptl_no_elems);
				}
				return;
			}
		}
		else
		{
			if(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] == -1 && isset($params['use_filter']) && $params['use_filter'] == 1 && (!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1))
			{
				$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $ptl_no_elems);
				return;
			}
			elseif(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] > 0 && isset($params['use_filter']) && $params['use_filter'] == 1)
			{
				$user_link_id_sql = ' AND p.p_user_id = '.intval($_REQUEST['pl'.$pm_id.'_uid']);
			}
		}

		// вытаскиваем пользовательские поля новости и раздела
	    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
        $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_plugin_'.$pm_id);

	    $elems_fields = array();
	    $categs_fields = array();

	    $categs_sql_fields = array();
	    $elems_fields_select_sql = '';

	    $tags = array();

		if ($ptl_checked != '')
	    {
	        $ptl_checked = explode(' ', $ptl_checked);
	    }
	    else
	    {
	    	$ptl_checked = array();
	    }

	    // формируем SQL-запрос для пользовательских полей
	    if ($res)
	    {
	        if($res[0][0] != '')
	        {
	            $elems_fields = unserialize($res[0][0]);
	        }

	        if($res[0][1] != '')
	        {
	            $categs_fields = unserialize($res[0][1]);
	        }

	        if ($elems_fields)
	        {
	        	$new_ptl_checked = array();
	            foreach ($elems_fields as $value)
	            {
	                if (isset($value['sql']) && $value['sql'] == 1)
	                {
	                	if (in_array($value['id'], $ptl_checked))
	                		$new_ptl_checked[] = $value['id'];

	                    $elems_fields_select_sql .= ', p.user_f_'.$value['id'];

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
	                }
	            }

	            $ptl_checked = $new_ptl_checked;
	        }

	        if ($categs_fields)
	        {
	            foreach ($categs_fields as $value)
	            {
	                if (isset($value['sql']) && $value['sql'] == 1)
	                {
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

	                    $categs_sql_fields[] = 'user_f_'.$value['id'];
	                }
	            }
	        }
	    }

	    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
	    $elems_fields_where_sql = '';
		foreach ($ptl_checked as $value)
        {
            $elems_fields_where_sql .= ' AND p.user_f_'.$value.'=1';
		}

		// Связь с календарем
	    if (isset($_GET['sb_year']) && isset($params['calendar']) && $params['calendar'] == 1 && isset($params['calendar_field']) && $params['calendar_field'] != '')
	    {
	    	$year = intval($_GET['sb_year']);

	    	if (isset($_GET['sb_month']))
	    	{
	    		$month_from = intval($_GET['sb_month']);
	    		$month_to = intval($_GET['sb_month']);
	    	}
	    	else
	    	{
	    		$month_from = 1;
	    		$month_to = 12;
	    	}

	    	if (isset($_GET['sb_day']))
	    	{
	    		$day_from = intval($_GET['sb_day']);
	    		$day_to = intval($_GET['sb_day']);
	    	}
	    	else
	    	{
	    		$day_from = 1;
	    		$day_to = sb_get_last_day($month_to, $year);
	    	}

	    	if (SB_DEMO_SITE)
	    	{
	    		$elems_fields_where_sql .= ' AND (
	    				(p.p_demo_id = 0
	    				AND p.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'"
	    				AND p.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'")
	    				OR (p.p_demo_id > 0 AND (SELECT COUNT(*) FROM sb_plugins_'.$pm_id.' p2 WHERE
	    					p2.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'"
	    					AND p2.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'"
	    					AND p2.p_id=p.p_demo_id
	    					AND p2.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	        AND (p2.p_pub_start IS NULL OR p2.p_pub_start <= '.time().')
	                	    AND (p2.p_pub_end IS NULL OR p2.p_pub_end >= '.time().')) > 0
	    				))';
	    	}
	    	else
	    	{
	    		$elems_fields_where_sql .= ' AND p.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'" AND p.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'"';
	    	}
	    }

		// Отключаем выводимый элемент, если выводится подробный элемент
		if (isset($params['show_selected']) && $params['show_selected'] == 1 && (isset($_GET['pl'.$pm_id.'_sid']) || isset($_GET['pl'.$pm_id.'_id'])))
		{
			if (isset($_GET['pl'.$pm_id.'_id']))
			{
				$elems_fields_where_sql .= ' AND p.p_id != "'.intval($_GET['pl'.$pm_id.'_id']).'"';
			}
			else
			{
				$res = sbQueryCache::query('SELECT p_id FROM sb_plugins_'.$pm_id.' WHERE p_url=?', $_GET['pl'.$pm_id.'_sid']);
				if ($res)
				{
					$elems_fields_where_sql .= ' AND p.p_id != "'.$res[0][0].'"';
				}
				else
				{
					$elems_fields_where_sql .= ' AND p.p_id != "'.intval($_GET['pl'.$pm_id.'_sid']).'"';
				}
			}
		}
		require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		// формируем SQL-запрос для фильтра
		$elems_fields_filter_sql = '';

	    if (!isset($params['use_filter']) || $params['use_filter'] == 1)
	    {
	    	$date_temp = '';
			if(isset($_REQUEST['p_f_'.$pm_id.'_temp_id']))
	    	{
				$date = sbQueryCache::query('SELECT ptf_fields_temps FROM sb_plugins_temps_form WHERE ptf_id = ?d', $_REQUEST['p_f_'.$pm_id.'_temp_id']);
				if($date)
				{
					list($ptf_fields_temps) = $date[0];

					if (trim($ptf_fields_temps) != '')
					{
						$ptf_fields_temps = unserialize($ptf_fields_temps);

						if (isset($ptf_fields_temps['date_temps']))
						{
							$date_temp = $ptf_fields_temps['date_temps'];
						}
					}
					else
					{
						$date_temp = '';
					}
				}
			}

		    $morph_db = false;
		    if ($params['filter_morph'] == 1)
		    {
			    require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
				$morph_db = new sbSearch();
			}

			require_once SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php';

			$elems_fields_filter_sql = '(';

	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_id', 'p_f_'.$pm_id.'_id', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_user_id', 'p_f_'.$pm_id.'_user_id', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_price1', 'p_f_'.$pm_id.'_price1', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_price2', 'p_f_'.$pm_id.'_price2', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_price3', 'p_f_'.$pm_id.'_price3', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_price4', 'p_f_'.$pm_id.'_price4', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_price5', 'p_f_'.$pm_id.'_price5', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterTextSql('p.p_title', 'p_f_'.$pm_id.'_title', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

			$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'p', 'p_f_'.$pm_id, $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
		}

		if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
	    	$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
		else
			$elems_fields_filter_sql = '';

	    if (isset($params['use_id_el_filter']) && $params['use_id_el_filter'] == 1)
	    {
			$elems_fields_filter_sql .= sbGetFilterCookieSql('p.p_id', 'sb_compare', $pm_id);
		}

		// связь с выводом облака тегов
	    $cloud_where_sql = '';
	    $p_tag = '';

	    if (isset($params['cloud']) && $params['cloud'] == 1 && isset($_REQUEST['p_'.$pm_id.'_tag']) && trim($_REQUEST['p_'.$pm_id.'_tag']) != '')
	    {
    		$tag = trim(preg_replace('/[^0-9\,\s]+/', '', $_REQUEST['p_'.$pm_id.'_tag']));
    		if ($tag != '')
				$p_tag .= $tag;
	    }

	    if (isset($params['cloud_comp']) && $params['cloud_comp'] == 1)
	    {
	    	if (isset($_REQUEST['p_'.$pm_id.'_tag_comp']) && trim($_REQUEST['p_'.$pm_id.'_tag_comp']) != '')
	    	{
	    		$tag = trim(preg_replace('/[^0-9\,\s\-]+/', '', $_REQUEST['p_'.$pm_id.'_tag_comp']));
	    		if ($tag != '')
	        		$p_tag .= ($p_tag != '' ? ',' : '').$tag;
	    	}
	    	else
	    	{
	    		$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $ptl_no_elems);
	    		return;
	    	}
	    }

	    if ($p_tag != '')
		{
			$cloud_where_sql = ' AND cl.cl_ident="pl_plugin_'.$pm_id.'" AND p.p_id=cl.cl_el_id AND cl.cl_tag_id IN ('.$p_tag.')';
	    }

		$now = time();
		$active_sql = '';
	    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	    {
	    	if (SB_DEMO_SITE)
			{
				$active_sql = ' AND (p.p_demo_id > 0 OR (p.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.'))) ';
			}
			else
			{
				$active_sql = ' AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.') ';
			}
	    }

	    // формируем SQL-запрос для сортировки
	    $elems_fields_sort_sql = '';
		$votes_apply = $comments_sorting = false;
		if(isset($params['use_sort']) && $params['use_sort'] == '1' && isset($_REQUEST['s_f_'.$pm_id]) && (is_array($_REQUEST['s_f_'.$pm_id]) || trim($_REQUEST['s_f_'.$pm_id]) != ''))
		{
			$elems_fields_sort_sql .= sbLayout::getPluginFieldsSortSql($pm_id, 'p');
		}
		else
		{
		    if (isset($params['sort1']) && $params['sort1'] != '')
		    {
				if ($params['sort1'] == 'com_count' || $params['sort1'] == 'com_date')
				{
					$comments_sorting = true;
				}

				if ($params['sort1'] == 'p_rating' || $params['sort1'] == 'v.vr_num' || $params['sort1'] == 'v.vr_count')
				{
					$votes_apply = true;
				}

		        $elems_fields_sort_sql .= ', '.$params['sort1'];

		    	if ($params['sort1'] == 'RAND()' && is_null($ids_elems))
		    	{
		    		$GLOBALS['sbCache']->mCacheOff = true;
		    	}

		        if (isset($params['order1']) && $params['order1'] != '')
		        {
		            $elems_fields_sort_sql .= ' '.$params['order1'];
		        }
		    }

		    if (isset($params['sort2']) && $params['sort2'] != '')
		    {
		    	if ($params['sort2'] == 'com_count' || $params['sort2'] == 'com_date')
				{
					$comments_sorting = true;
				}
				if ($params['sort2'] == 'p_rating' || $params['sort2'] == 'v.vr_num' || $params['sort2'] == 'v.vr_count')
				{
					$votes_apply = true;
				}

		        $elems_fields_sort_sql .= ', '.$params['sort2'];

		    	if ($params['sort2'] == 'RAND()' && is_null($ids_elems))
		    	{
		    		$GLOBALS['sbCache']->mCacheOff = true;
		    	}

		        if (isset($params['order2']) && $params['order2'] != '')
		        {
		            $elems_fields_sort_sql .= ' '.$params['order2'];
		        }
		    }

		    if (isset($params['sort3']) && $params['sort3'] != '')
		    {
		    	if ($params['sort3'] == 'com_count' || $params['sort3'] == 'com_date')
				{
					$comments_sorting = true;
				}
				if ($params['sort3'] == 'p_rating' || $params['sort3'] == 'v.vr_num' || $params['sort3'] == 'v.vr_count')
				{
					$votes_apply = true;
				}

		        $elems_fields_sort_sql .= ', '.$params['sort3'];

		    	if ($params['sort3'] == 'RAND()' && is_null($ids_elems))
		    	{
		    		$GLOBALS['sbCache']->mCacheOff = true;
		    	}

		        if (isset($params['order3']) && $params['order3'] != '')
		        {
		            $elems_fields_sort_sql .= ' '.$params['order3'];
		        }
		    }
		}
		$num_cookie_name = 'pl_plugin_'.$pm_id.'_'.$temp_id.'_'.$tag_id;

		require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
	    $pager = new sbDBPager($tag_id, $pt_perstage, $ptl_perpage, '', $num_cookie_name);
	    if (isset($params['filter']) && $params['filter'] == 'from_to')
	    {
	        $pager->mFrom = intval($params['filter_from']);
	        $pager->mTo = intval($params['filter_to']);
	    }

		// используется ли группировка по разделам
	    if ($ptl_categ_top != '' || $ptl_categ_bottom != '')
	    {
	        $categs_output = true;
	    }
	    else
	    {
	        $categs_output = false;
	    }

	    // выборка элементов, которые следует выводить
	    $elems_total = true;

		if(isset($_SESSION['sbAuth']))
		{
			$basket_where = ' AND b.b_id_user = '.intval($_SESSION['sbAuth']->getUserId());
		}
		elseif(isset($_COOKIE['pl_basket_user_id']))
		{
			$basket_where = ' AND b.b_hash = "'.preg_replace('/[^A-Za-z0-9]+/', '', $_COOKIE['pl_basket_user_id']).'"';
		}
		else
		{
			$basket_where = ' AND b.b_hash = "XXX"'; // ничего не значит. просто заведомо не реальное значение
		}

		$only_sql = '';
	    if(is_array($ids_elems))
	    {
	    	foreach($ids_elems as $value)
	    	{
				$only_sql .= intval($value).',';
	    	}
			$only_sql = ' AND p.p_id IN ('.substr($only_sql, 0, -1).')';
		}

    	if($comments_sorting)
    	{
    		$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
			$com_sort_sql = 'LEFT JOIN sb_comments com ON (com.c_el_id=p.p_id AND com.c_plugin="pl_plugin_'.$pm_id.'" AND com.c_show=1)';
		}
		else
    	{
			$com_sort_fields = 'NULL, NULL';
			$com_sort_sql = '';
    	}

	    $group_str = '';

	    if (!isset($pm_elems_settings['use_links']) || isset($pm_elems_settings['use_links']) && $pm_elems_settings['use_links'] == 1)
	    {
    	    $group_res = sbQueryCache::query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
    	    if ($group_res && $group_res[0][0] > 0 || $comments_sorting || $cloud_where_sql != '')
    	    {
    			$group_str = ' GROUP BY p.p_id';
    		}
	    }

    	if($categs_output)
    	{
    		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', p.p_sort');
		}
		else
		{
			$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' p.p_sort');
    	}

		$votes_sql = '';
		$votes_fields = ' NULL, NULL, NULL,';
    	if($votes_apply ||
			sb_strpos($ptl_element, '{RATING}') !== false ||
			sb_strpos($ptl_element, '{VOTES_COUNT}') !== false ||
			sb_strpos($ptl_element, '{VOTES_SUM}') !== false ||
			sb_strpos($ptl_element, '{VOTES_FORM}') !== false)
		{
			$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=p.p_id AND v.vr_plugin="pl_plugin_'.$pm_id.'" ';
			$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS p_rating, ';
		}

		$basket_sql = '';
		$basket_fields = ' NULL, NULL, NULL, 0, ';

		if(isset($params['from_add_form']) && $params['from_add_form'])
		{
		    // вывод в корзине
		    $group_str .= ($group_str == '' ? ' GROUP BY p.p_id ' : '').', b.b_prop';
    		$basket_fields = ' b.b_count_el, b.b_reserved, b.b_prop, b.b_discount, ';
	    	$basket_sql = ' LEFT JOIN sb_basket b ON p.p_id = b.b_id_el AND b.b_id_mod = '.intval($pm_id).' AND b.b_reserved = 0 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where;
		}
		elseif(sb_strpos($ptl_element, '{SUM_ORDER}') !== false ||
			sb_strpos($ptl_element, '{GOODS_COUNT}') !== false ||
			sb_strpos($ptl_element, '{ORDER}') !== false)
		{
			$group_str .= ($group_str == '' ? ' GROUP BY p.p_id ' : '').', b.b_id_el';
			$basket_fields = ' SUM(b.b_count_el), b.b_reserved, NULL, b.b_discount, ';
			$basket_sql = ' LEFT JOIN sb_basket b ON p.p_id = b.b_id_el AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") AND b.b_id_mod = '.intval($pm_id).$basket_where;
		}

		//	Если идет вывод корзины, и нужно выводить выбранные элементы
		$only_ids = '';
		if(isset($params['ids_from']) && $params['ids_from'] == 'id')
		{
			$only_ids = ' AND p.p_id IN ('.sb_str_replace('^', ',', $params['ids']).') ';
		}

		if ($root_cat_level == 0)
		{
		    $res = $pager->init($elems_total, 'SELECT l.link_cat_id, p.p_id, p.p_title, p.p_url, p.p_sort,
            			p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5,
            			'.$votes_fields
            			.$basket_fields.'
    					p.p_user_id,
    					'.$com_sort_fields.'
    					, p.p_demo_id
    					'.$elems_fields_select_sql.'
    				FROM sb_plugins_'.$pm_id.' p
    					'.$votes_sql
    					.$com_sort_sql
    					.$basket_sql.', sb_catlinks l, sb_categs c'.
    					($cloud_where_sql != '' ? ', sb_clouds_links cl' : '').'
    				WHERE c.cat_ident="pl_plugin_'.$pm_id.'" AND c.cat_id=l.link_cat_id'.
    					(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=p.p_id
    					'.$elems_fields_where_sql.' '.
    					$user_link_id_sql.' '.
    					$elems_fields_filter_sql.' '.
    					$active_sql.' '.
    					$cloud_where_sql.' '.
    					$only_sql.' '.
    					$group_str.' '.
    					$only_ids.' '.
    					($categs_output ? 'ORDER BY c.cat_left '.$elems_fields_sort_sql : 'ORDER BY '.$elems_fields_sort_sql));

		}
		else
		{
		    $res = $pager->init($elems_total, 'SELECT l.link_cat_id, p.p_id, p.p_title, p.p_url, p.p_sort,
        			p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5,
        			'.$votes_fields
		            .$basket_fields.'
					p.p_user_id,
					'.$com_sort_fields.'
					,p.p_demo_id
					'.$elems_fields_select_sql.'
				FROM sb_plugins_'.$pm_id.' p
					'.$votes_sql
		            .$com_sort_sql
		            .$basket_sql.', sb_catlinks l'.
		            (isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').
		            ($cloud_where_sql != '' ? ', sb_clouds_links cl' : '').'
				WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
		            (isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=p.p_id
					'.$elems_fields_where_sql.' '.
		            $user_link_id_sql.' '.
		            $elems_fields_filter_sql.' '.
		            $active_sql.' '.
		            $cloud_where_sql.' '.
		            $only_sql.' '.
		            $group_str.' '.
		            $only_ids.' '.
		            ($categs_output ? 'ORDER BY c.cat_left '.$elems_fields_sort_sql : 'ORDER BY '.$elems_fields_sort_sql), $cat_ids);
		}

		if (!$res && is_null($ids_elems))
		{
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $ptl_no_elems);
			return;
		}
		elseif(!$res && !is_null($ids_elems))
	    {
			return false;
	    }
		elseif(!$res)
	    {
			return false;
	    }

		$count_elems = $pager->mFrom + 1;

		$comments_count = array();
		if(sb_strpos($ptl_element, '{COUNT_COMMENTS}') !== false)
	    {
		    if ($comments_sorting)
		    {
		    	for($i = 0; $i < count($res); $i++)
		        {
					$comments_count[$res[$i][1]] = $res[$i][16];
				}
			}
			else
			{
				$ids_arr = array();
				for($i = 0; $i < count($res); $i++)
				{
					$ids_arr[] = $res[$i][1];
		        }

				require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
				$comments_count = fComments_Get_Count($ids_arr, 'pl_plugin_'.$pm_id);
			}
		}

	    $categs = array();
	    if (sb_substr_count($ptl_categ_top, '{CAT_COUNT}') > 0 ||
	        sb_substr_count($ptl_categ_bottom, '{CAT_COUNT}') > 0 ||
	        sb_substr_count($ptl_element, '{CAT_COUNT}') > 0
	       )
	    {
	        if (!isset($pm_elems_settings['use_links']) || isset($pm_elems_settings['use_links']) && $pm_elems_settings['use_links'] == 1)
	        {
	            $res_cat = sbQueryCache::query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
	                (

	                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_plugins_'.$pm_id.' p
	                WHERE c.cat_id = c1.cat_id
	                AND l.link_el_id=p.p_id
	                '.$active_sql.'
	                AND l.link_src_cat_id NOT IN (?a)

	                ) AS cat_count, c1.cat_closed
					FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids);
	        }
	        else
	        {
	            if ($root_cat_level == 0)
	            {
	                $res_cat = sbQueryCache::query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
    	                (

    	                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_plugins_'.$pm_id.' p
    	                WHERE c.cat_id = c1.cat_id
    	                AND l.link_el_id=p.p_id
    	                '.$active_sql.'

    	                ) AS cat_count, c1.cat_closed
    					FROM sb_categs c1 WHERE c1.cat_ident="pl_plugin_'.$pm_id.'"');
	            }
	            else
	            {
	                $res_cat = sbQueryCache::query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
    	                (

    	                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_plugins_'.$pm_id.' p
    	                WHERE c.cat_id = c1.cat_id
    	                AND l.link_el_id=p.p_id
    	                '.$active_sql.'

    	                ) AS cat_count, c1.cat_closed
    					FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids);
	            }
	        }
		}
		else
	    {
	        if ($root_cat_level == 0)
	        {
	            // выводим корневой раздел и его подразделы, т.е. все дерево разделов
			    $res_cat = sbQueryCache::query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, cat_closed
				    	FROM sb_categs WHERE cat_ident="pl_plugin_'.$pm_id.'"');
	        }
	        else
	        {
	            $res_cat = sbQueryCache::query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, cat_closed
				    	FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
	        }
	    }

	    if ($res_cat)
	    {
			foreach ($res_cat as $value)
			{
	            $categs[$value[0]] = array();
	            $categs[$value[0]]['title'] = $value[1];
	            $categs[$value[0]]['level'] = $value[2] + 1;
				$categs[$value[0]]['fields'] =  (trim($value[3]) != '' ? unserialize($value[3]) : array());
	            $categs[$value[0]]['url'] = urlencode($value[4]);
	            $categs[$value[0]]['count'] = $value[5];
	            $categs[$value[0]]['closed'] = $value[6];
			}
		}

		//	строим список номеров страниц
	    if ($pt_page_list != '' && (is_null($ids_elems) || is_array($ids_elems) && $link_level > 0))
	    {
			$pager->mBeginTemp = $pt_begin;
			$pager->mBeginTempDisabled = '';
			$pager->mNextTemp = $pt_next;
			$pager->mNextTempDisabled = '';

	        $pager->mPrevTemp = $pt_previous;
	        $pager->mPrevTempDisabled = '';
	        $pager->mEndTemp = $pt_end;
	        $pager->mEndTempDisabled = '';

	        $pager->mNumberTemp = $pt_number;
	        $pager->mCurNumberTemp = $pt_sel_number;
	        $pager->mDelimTemp = $pt_delim;
	        $pager->mListTemp = $pt_page_list;

	        $pt_page_list = $pager->show();
	    }

		$compare_action = '/cms/admin/compare.php';
		$basket_action = '/cms/admin/basket.php';

		// верх вывода
		$flds_tags = array( '{SORT_ID_ASC}','{SORT_ID_DESC}',
							'{SORT_TITLE_ASC}','{SORT_TITLE_DESC}',
	    					'{SORT_SORT_ASC}','{SORT_SORT_DESC}',
							'{SORT_ACTIVE_ASC}','{SORT_ACTIVE_DESC}',
							'{SORT_USER_ID_ASC}','{SORT_USER_ID_DESC}',
							'{SORT_PRICE1_ASC}','{SORT_PRICE1_DESC}',
							'{SORT_PRICE2_ASC}','{SORT_PRICE2_DESC}',
							'{SORT_PRICE3_ASC}','{SORT_PRICE3_DESC}',
							'{SORT_PRICE4_ASC}','{SORT_PRICE4_DESC}',
							'{SORT_PRICE5_ASC}','{SORT_PRICE5_DESC}');

	    $query_str = $_SERVER['QUERY_STRING'];
	    if(isset($_GET['s_f_'.$pm_id]))
	    {
			$query_str = preg_replace('/[?&]?s_f_'.$pm_id.'['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
		}

		$flds_href = (isset($GLOBALS['PHP_SELF']) ? $GLOBALS['PHP_SELF'] : '').(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_'.$pm_id.'=';
		$flds_vals = array( $flds_href.urlencode('p_id=ASC'),
	    					$flds_href.urlencode('p_id=DESC'),
	    					$flds_href.urlencode('p_title=ASC'),
	    					$flds_href.urlencode('p_title=DESC'),
	    					$flds_href.urlencode('p_sort=ASC'),
	    					$flds_href.urlencode('p_sort=DESC'),
	    					$flds_href.urlencode('p_active=ASC'),
	    					$flds_href.urlencode('p_active=DESC'),
	    					$flds_href.urlencode('p_user_id=ASC'),
	    					$flds_href.urlencode('p_user_id=DESC'),
	    					$flds_href.urlencode('p_price1=ASC'),
	    					$flds_href.urlencode('p_price1=DESC'),
	    					$flds_href.urlencode('p_price2=ASC'),
	    					$flds_href.urlencode('p_price2=DESC'),
	    					$flds_href.urlencode('p_price3=ASC'),
	    					$flds_href.urlencode('p_price3=DESC'),
	    					$flds_href.urlencode('p_price4=ASC'),
	    					$flds_href.urlencode('p_price4=DESC'),
	    					$flds_href.urlencode('p_price5=ASC'),
	    					$flds_href.urlencode('p_price5=DESC'));

		sbLayout::getPluginFieldsTagsSort($pm_id, $flds_tags, $flds_vals, 'href_replace', $elems_fields);
		// Заменяем значение селекта "Кол-во на странице" селектед
		if(isset($_REQUEST['num_'.$tag_id]))
	    {
			$ptl_top = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $ptl_top);
	    }
	    elseif(isset($_COOKIE[$num_cookie_name]))
	    {
	    	$ptl_top = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $ptl_top);
	    }

		$result = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{COMPARE_ACTION}', '{BASKET_ACTION}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $elems_total, $compare_action, $basket_action, 'num_'.$tag_id),$flds_vals), $ptl_top);
	    $tags = array_merge($tags, array('{CAT_TITLE}',
	                                     '{CAT_LEVEL}',
	                                     '{CAT_COUNT}',
	                                     '{CAT_ID}',
	                                     '{CAT_URL}',
										 '{ELEM_NUMBER}',
	                                     '{ID}',
	                                     '{ELEM_URL}',
	                                     '{TITLE}',
	                                     '{LINK}',
	                                     '{MORE}',
	    								 '{EDIT_LINK}',
										 '{USER_DATA}',
	    								 '{CHANGE_DATE}',
										 '{ELEM_USER_LINK}',
	    								 '{TAGS}',
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
	                                     '{LIST_COMMENTS}'));
		$cur_cat_id = 0;
	    $values = array();
	    $num_fields = count($res[0]);
	    $num_cat_fields = count($categs_sql_fields);
		$col = 0;

		$dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');

		list($more_page, $more_ext) = sbGetMorePage($params['page']);
		list($edit_page, $edit_ext) = sbGetMorePage($params['edit_page']);

		$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
		if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
		{
			$auth_page = '/'.$auth_page;
		}

	    $view_rating_form = (sb_strpos($ptl_element, '{VOTES_FORM}') !== false && $ptl_votes_id > 0);
	    $view_comments_list = (sb_strpos($ptl_element, '{LIST_COMMENTS}') !== false && $ptl_comments_id > 0);
	    $view_comments_form = (sb_strpos($ptl_element, '{FORM_COMMENTS}') !== false && $ptl_comments_id > 0);

	    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
	    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');

	    $tags_template_error = true;
		if(sb_strpos($ptl_element, '{TAGS}') !== false)
        {
		    // Достаю макеты для вывода списка тегов элементов
	     	$res_tags = sbQueryCache::query('SELECT ct_pagelist_id, ct_perpage
	                FROM sb_clouds_temps WHERE ct_id=?d', $ptl_tags_list_id);

	    	if ($res_tags)
	    	{
	    		$tags_template_error = false;

	    		list($ct_pagelist_id, $ct_perpage) = $res_tags[0];

	        	// Вытаскиваем макет дизайна постраничного вывода
	        	$res_tags_pages = sbQueryCache::getTemplate('sb_pager_temps', $ct_pagelist_id);

		     	if ($res_tags_pages)
		     	{
		        	list($pt_perstage_tags, $pt_begin_tags, $pt_next_tags, $pt_previous_tags, $pt_end_tags, $pt_number_tags, $pt_sel_number_tags, $pt_page_list_tags, $pt_delim_tags) = $res_tags_pages[0];
		     	}
		     	else
		     	{
		        	$pt_page_list_tags = '';
		        	$pt_perstage_tags = 1;
		     	}
	    	}
        }

        $allow_bb = 0;
		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
			$allow_bb = 1;

	    // цикл по элементам
	    foreach ($res as $value)
	    {
	    	$value[3] = urlencode($value[3]); //ELEM_URL

	    	if (SB_DEMO_SITE)
	    	{
	    		$p_demo_id = $value[20];

	    		$demo_res = sbQueryCache::query('SELECT p.p_title, p.p_sort,
        			p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5
					'.$elems_fields_select_sql.'
					FROM sb_plugins_'.$pm_id.' p WHERE p_id=?d', $p_demo_id);

	    		if ($demo_res)
	    		{
	    			$value[2] = $demo_res[0][0];
	    			$value[4] = $demo_res[0][1];
	    			$value[5] = $demo_res[0][2];
	    			$value[6] = $demo_res[0][3];
	    			$value[7] = $demo_res[0][4];
	    			$value[8] = $demo_res[0][5];
	    			$value[9] = $demo_res[0][6];

		    		for($i = 21; $i < $num_fields; $i++)
		            {
			            $value[$i] = $demo_res[0][$i - 14];
					}
	    		}
	    	}

	    	$old_values = $values;
	        $values = array();
	        $more = '';
	        $edit = '';

	        if ($value[0] != $cur_cat_id)
	        {
	            $cat_values = array();
	        }

	        if ($more_page == '')
	        {
	            $href = 'javascript: void(0);';
	        }
	        else
	        {
	            $href = $more_page;

	            if (sbPlugins::getSetting('sb_static_urls') == 1)
	            {
	                // ЧПУ
	                $href .= ($categs[$value[0]]['url'] != '' ? $categs[$value[0]]['url'].'/' : $value[0].'/').
	                         ($value[3] != '' ? $value[3] : $value[1]).($more_ext != 'php' ? '.'.$more_ext : '/');
	            }
	            else
	            {
	                $href .= '?pl'.$pm_id.'_cid='.$value[0].'&pl'.$pm_id.'_id='.$value[1];
	            }
	        }

	        $dop_values = array($value[1], strip_tags($value[3]), strip_tags($value[2]), $value[0], $categs[$value[0]]['url'], strip_tags($categs[$value[0]]['title']), $href);
	        // ссылка "Подробнее..."
	        $more = ($more_page != '' ? str_replace($dop_tags, $dop_values, $ptl_fields_temps['p_more']) : '');

	        if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($value[17] != $_SESSION['sbAuth']->getUserId())))
	        {
	        	$edit = '';
	        }
	        else
	        {
		        if ($edit_page == '')
		        {
		            $edit_href = 'javascript: void(0);';
		        }
		    	else
		        {
		            $edit_href = $edit_page;
		            if (sbPlugins::getSetting('sb_static_urls') == 1)
		            {
		                // ЧПУ
		                $edit_href .= ($categs[$value[0]]['url'] != '' ? $categs[$value[0]]['url'].'/' : $value[0].'/').
		                         ($value[3] != '' ? $value[3] : $value[1]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
		            }
		            else
		            {
		                $edit_href .= '?pl'.$pm_id.'_cid='.$value[0].'&pl'.$pm_id.'_id='.$value[1];
		            }
		        }
		    	if ($edit_page != '' && isset($ptl_fields_temps['p_edit_link']))
		        {
		        	// Ссылка "Редактировать..."
		        	$edit = str_replace(array_merge(array('{LINK}'), $dop_tags), array_merge(array($edit_href), $dop_values), $ptl_fields_temps['p_edit_link']);
		        }
	    	}

	        //	проходим по полям элемента
			if ($num_fields > 21)
			{
				for($i = 21; $i < $num_fields; $i++)
	            {
		            $values[] = $value[$i];
				}

				$func_params = array();
				if(isset($value[15]) && $value[15] != '')
					$func_params['from_cart'] = $value[15];

	            $values = sbLayout::parsePluginFields($elems_fields, $values, $ptl_fields_temps, $dop_tags, $dop_values, $ptl_lang, '', '', $allow_bb, $link_level, $ptl_element, $func_params);
	        }

	        if ($num_cat_fields > 0)
	        {
	            if (count($cat_values) == 0)
	            {
	                foreach ($categs_sql_fields as $cat_field)
	                {
	                    if (isset($categs[$value[0]]['fields'][$cat_field]))
	                    {
							$cat_values[] = $categs[$value[0]]['fields'][$cat_field];
						}
	                    else
	                        $cat_values[] = null;
	                }
					$allow_bb = 0;
					if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
						$allow_bb = 1;

					$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ptl_categs_temps, $dop_tags, $dop_values, $ptl_lang, '', '', $allow_bb, $link_level, $ptl_categ_top.$ptl_element.$ptl_categ_bottom);
				}

				$values = array_merge($values, $cat_values);
	        }

	        $values[] = $categs[$value[0]]['title']; // CAT_TITLE
	        $values[] = $categs[$value[0]]['level']; // CAT_LEVEL
	        $values[] = $categs[$value[0]]['count']; // CAT_COUNT
	        $values[] = $value[0];                   // CAT_ID
	        $values[] = $categs[$value[0]]['url'];   // CAT_URL
	        $values[] = $count_elems++;				 // ELEM_NUMBER
	        $values[] = $value[1];                   // ID
	        $values[] = $value[3];                   // ELEM_URL
	        $values[] = $value[2];                   // TITLE
	        $values[] = $href;                       // LINK
	        $values[] = $more;                       // MORE
	        $values[] = $edit;	 					//EDIT_LINK
			if($ptl_user_data_id > 0 && isset($value[17]) && $value[17] != '' &&  sb_strpos($ptl_element, '{USER_DATA}') !== false)
		    {
				require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
				$values[] = fSite_Users_Get_Data($ptl_user_data_id, $value[17]); //     USER_DATA
		    }
		    else
		    {
				$values[] = '';   //   USER_DATA
		    }
			// Дата последнего изменения
	        if(sb_strpos($ptl_element, '{CHANGE_DATE}') !== false)
	        {
	        	$res1 = sbQueryCache::query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_plugin_'.$pm_id);
	        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ptl_fields_temps['p_date'], $ptl_lang) : ''; //   CHANGE_DATE
	        }
	        else
	       	{
	        	$values[] = '';
	       	}

			if(isset($value[17]) && $value[17] > 0 && isset($ptl_fields_temps['f_registred_users']) && $ptl_fields_temps['f_registred_users'] != '')
	        {
				$action = $auth_page.'?pl'.$pm_id.'_uid='.$value[17].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');   //   ELEM_USER_LINK
				$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ptl_fields_temps['f_registred_users']);
	        }
			else
	        {
				$values[] = '';
	        }

	        if(!$tags_template_error)
	        {
			    // Вывод тематических тегов
			    $pager_tags = new sbDBPager('t_'.$value[1], $pt_perstage_tags, $ct_perpage);

			    // Вытаскиваю теги
				$tags_total = true;

				$res_tags = $pager_tags->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_clouds_links cl
	                            WHERE cl.cl_tag_id IN
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_plugin_'.$pm_id.'" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_plugin_'.$pm_id.'"
	                                AND ct.ct_id=cl.cl_tag_id
	                            GROUP BY cl.cl_tag_id', $value[1]);

		    	if($res_tags)
		    	{
		    		$page_list_tags = '';

		    		// строим список номеров страниц
			    	if ($pt_page_list_tags != '')
			    	{
			    	    $pager_tags->mBeginTemp = $pt_begin_tags;
			    	    $pager_tags->mBeginTempDisabled = '';
			    	    $pager_tags->mNextTemp = $pt_next_tags;
			    	    $pager_tags->mNextTempDisabled = '';

			    	    $pager_tags->mPrevTemp = $pt_previous_tags;
			       		$pager_tags->mPrevTempDisabled = '';
			       		$pager_tags->mEndTemp = $pt_end_tags;
			        	$pager_tags->mEndTempDisabled = '';

			        	$pager_tags->mNumberTemp = $pt_number_tags;
			        	$pager_tags->mCurNumberTemp = $pt_sel_number_tags;
			        	$pager_tags->mDelimTemp = $pt_delim_tags;
			        	$pager_tags->mListTemp = $pt_page_list_tags;

			        	$page_list_tags = $pager_tags->show();
			        }

		    		require_once (SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php');
					$values[] = fClouds_Show($res_tags, $ptl_tags_list_id,  $page_list_tags, $tags_total, '', 'p_'.$pm_id.'_tag'); //     TAGS
		    	}
				else
		        {
		     		$values[] = '';   //   TAGS
		        }
	        }
	    	else
	        {
				$values[] = '';   //   TAGS
	        }

	        $values[] = $value[4];                   // SORT

	        $p_price1 = $value[5];
	        $p_price2 = $value[6];
	        $p_price3 = $value[7];
	        $p_price4 = $value[8];
	        $p_price5 = $value[9];

	        if (isset($params['cena']) && isset(${'p_price'.$params['cena']}))
	            $price = ${'p_price'.$params['cena']};
	        else
	            $price = 0;

	    	if ((!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] == 0) && isset($pm_elems_settings['price1_formula']))
	    	{
	    		// рассчитываем цену
	    		$p_price1 = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price1_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, isset($params['from_add_form']) && $params['from_add_form'] ? $value[16] : 0);
	    		if (isset($params['cena']) && $params['cena'] == 1)
    	    		$price = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price1_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $value[16]);
	    	}

	    	if ((!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] == 0) && isset($pm_elems_settings['price2_formula']))
	    	{
	    		// рассчитываем цену
	    		$p_price2 = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price2_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, isset($params['from_add_form']) && $params['from_add_form'] ? $value[16] : 0);
	    		if (isset($params['cena']) && $params['cena'] == 2)
	    		    $price = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price2_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $value[16]);
	    	}

	    	if ((!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] == 0) && isset($pm_elems_settings['price3_formula']))
	    	{
	    		// рассчитываем цену
	    		$p_price3 = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price3_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, isset($params['from_add_form']) && $params['from_add_form'] ? $value[16] : 0);
	    		if (isset($params['cena']) && $params['cena'] == 3)
	    		    $price = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price3_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $value[16]);
	    	}

	    	if ((!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] == 0) && isset($pm_elems_settings['price4_formula']))
	    	{
	    		// рассчитываем цену
	    		$p_price4 = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price4_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, isset($params['from_add_form']) && $params['from_add_form'] ? $value[16] : 0);
	    		if (isset($params['cena']) && $params['cena'] == 4)
	    		    $price = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price4_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $value[16]);
	    	}

	    	if ((!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] == 0) && isset($pm_elems_settings['price5_formula']))
	    	{
	    		// рассчитываем цену
	    		$p_price5 = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price5_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, isset($params['from_add_form']) && $params['from_add_form'] ? $value[16] : 0);
	    		if (isset($params['cena']) && $params['cena'] == 5)
	    		    $price = fPlugin_Maker_Quoting($pm_id, $value[1], $pm_elems_settings['price5_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $value[16]);
	    	}

	        $values[] = !is_null($p_price1) && isset($ptl_fields_temps['p_price1']) && trim($ptl_fields_temps['p_price1']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price1)), $ptl_fields_temps['p_price1']) : '';	 // PRICE_1
	        $values[] = !is_null($p_price2) && isset($ptl_fields_temps['p_price2']) && trim($ptl_fields_temps['p_price2']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price2)), $ptl_fields_temps['p_price2']) : '';	 // PRICE_2
	        $values[] = !is_null($p_price3) && isset($ptl_fields_temps['p_price3']) && trim($ptl_fields_temps['p_price3']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price3)), $ptl_fields_temps['p_price3']) : '';	 // PRICE_3
	        $values[] = !is_null($p_price4) && isset($ptl_fields_temps['p_price4']) && trim($ptl_fields_temps['p_price4']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price4)), $ptl_fields_temps['p_price4']) : '';	 // PRICE_4
	        $values[] = !is_null($p_price5) && isset($ptl_fields_temps['p_price5']) && trim($ptl_fields_temps['p_price5']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price5)), $ptl_fields_temps['p_price5']) : '';	 // PRICE_5
			$values[] = isset($value[13]) && $price > 0 && isset($ptl_fields_temps['p_sum_order']) && trim($ptl_fields_temps['p_sum_order']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($price * $value[13])), $ptl_fields_temps['p_sum_order']) : '';
			$values[] = isset($value[13]) && isset($ptl_fields_temps['p_goods_count']) && trim($ptl_fields_temps['p_goods_count']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($value[13] != '' ? $value[13] : '0')), $ptl_fields_temps['p_goods_count']) : '';

			//	ORDER
			// если кол-во текущего товара в корзине больше нуля
			// Если товар заказан, он все равно выводится в корзине
			if (isset($ptl_fields_temps['p_prod_add']))
			{
				if(!(isset($params['from_add_form']) || (isset($params['from_add_form']) && $params['from_add_form'] == '')) && sb_strpos($ptl_fields_temps['p_prod_add'], '{OPTIONS_'))
				{
					$value[13] = 0;
				}

				if (strpos($ptl_fields_temps['p_prod_add'], '{VALUE}') !== false || strpos($ptl_fields_temps['p_prod_add'], 'OPTIONS_') !== false || !isset($value[13]) || $value[13] == 0)
				{
					// Макет корзины
					$p_prod_add = isset($ptl_fields_temps['p_prod_add']) ? trim($ptl_fields_temps['p_prod_add']) : '';

					if ($p_prod_add != '')
					{
						if(sb_strpos($p_prod_add, '{ID}') !== false)
						{
							// Идентификаторы пользовательских полей
							$charact = '';
							if(isset($params['from_add_form']) && $params['from_add_form'] != '' && isset($value[15]) && $value[15] != '')
							{
								$charact = '_'.$value[15];
							}

							$p_prod_add = str_replace('{ID}', $value[1].$charact, $p_prod_add);
						}

						if(sb_strpos($p_prod_add, '{OPTIONS_') !== false)
						{
							$matches = array();
							if(preg_match_all('/\{OPTIONS_([0-9]+)\}/', $p_prod_add, $matches))
							{
								$selected = array();

								if(isset($params['from_add_form']) && $params['from_add_form'] != '' && isset($value[15]) && $value[15] != '')
								{
									$selected_arr = explode('||', $value[15]);
									foreach($selected_arr as $v)
									{
										$v = explode('::', $v);
										$v[1] = explode(',', $v[1]);
										$selected[$v[0]] = $v[1];
									}
								}

								foreach($elems_fields as $value1)
								{
									if(in_array($value1['id'], $matches[1]))
									{
										// Если поле нужно выводить
										$f_matches = array();

										// Определяю макет поля
										$cart_field_tpl = $ptl_fields_temps['p_cart_field_'.$value1['id']];

										// Достаю идентификаторы элементов
										$res_fields = sbQueryCache::query('SELECT user_f_'.$value1['id'].' FROM  sb_plugins_'.$pm_id.' WHERE p_id = ?', (SB_DEMO_SITE && isset($p_demo_id) && $p_demo_id > 0 ? $p_demo_id : $value[1]));
										$elems = '0';
										if($res_fields && $res_fields[0][0] != '')
											$elems .= ','.$res_fields[0][0];

										// Определяю справочник или модуль
										if(preg_match('/\{PROP([0-9])\}/', $cart_field_tpl) || sb_strpos($cart_field_tpl, '{SPR_TITLE}') !== false)
										{
											// Достаю элементы справочника
											$res_sprav = sbQueryCache::query('SELECT s_id, s_title, s_prop1, s_prop2, s_prop3 FROM sb_sprav WHERE s_id IN ('.$elems.') AND s_active=1 ORDER BY s_sort, s_title');
								            if($res_sprav)
								            {
								            	$p_prod_add_options = '';
								            	// Если есть справочники
								        		foreach($res_sprav as $sprav_value)
								            	{
								            		$is_selected = '';
								            		if(isset($params['from_add_form']) && $params['from_add_form'] != '' && isset($value[15]) && $value[15] != ''
													&& isset($selected[$value1['id']]) && in_array($sprav_value[0], $selected[$value1['id']]))
													{
														$is_selected = sb_stripos($cart_field_tpl, 'option') !== false ? ' selected="selected"' : ' checked="checked"';
													}

								            		$p_prod_add_options .= str_replace(array('{SELECTED}', '{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}'), array_merge(array($is_selected), $sprav_value), $cart_field_tpl);
								            	}
								            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
								            }
										}
										elseif(preg_match_all('/OPT_([^}]+)/',$cart_field_tpl, $f_matches))
										{
											// Узнаю нужные поля элемента
								            $elems_tags = array();
								            $sql_fields = '';
								            foreach($f_matches[1] as $el_fl)
								            {
								            	$field_name = sb_strtolower(sb_str_replace('STD_', '', $el_fl));
								            	$sql_fields .= ', '.$field_name;
								            	$elems_tags[] = '{OPT_'.$el_fl.'}';
								            }

											$res_elems = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id IN ('.$elems.') ORDER BY p_sort, p_title');
											if($res_elems)
								            {
								            	$p_prod_add_options = '';

								            	// Если есть элементы
								            	foreach($res_elems as $elems_value)
								            	{
								            		if (SB_DEMO_SITE)
								            		{
									            		$elems_demo_id = $elems_value[0];

									            		if ($elems_demo_id > 0)
									            		{
									            			$res_demo = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id=?d', $elems_demo_id);
									            			if ($res_demo)
									            			{
									            				$res_demo[0][0] = $elems_value[0];
									            				$res_demo[0][1] = $elems_value[1];
									            				$elems_value = $res_demo[0];
									            			}
									            		}
								            		}

								            		$is_selected = '';
								            		if(isset($params['from_add_form']) && $params['from_add_form'] != '' && isset($value[15]) && $value[15] != ''
													&& isset($selected[$value1['id']]) && in_array($elems_value[1], $selected[$value1['id']]))
													{
														$is_selected = sb_stripos($cart_field_tpl, 'option') !== false ? ' selected="selected"' : ' checked="checked"';
													}

								            		$p_prod_add_options .= str_replace(array_merge(array('{SELECTED}', '{DEMO_ID}', '{ID}'), $elems_tags), array_merge(array($is_selected),$elems_value), $cart_field_tpl);
								            	}

								            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
								            }
										}
									}
								}
							}
						}
					}

					$element_id = $pm_id.'_'.$value[1];
					if(isset($params['from_add_form']) && $params['from_add_form'] != '' && isset($value[15]) && $value[15] != '')
					{
						$element_id .= '_'.$value[15];
					}
					$values[] = $p_prod_add != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}', '{VALUE}'), array($basket_action.'?pl_plugin_order['.$pm_id.'_'.$value[1].']= 1', $basket_action, $element_id, isset($value[13]) && $value[13] > 0 ? $value[13] : 0), $p_prod_add) : '';
				}
				else
				{
					$del_params = '';
					if(isset($value[15]) && $value[15] != '')
					{
						// Если элемент добавлен в корзину с параметрами -
						// Выводим в форме удаления эти параметры
						$del_params = '_'.$value[15];
					}
					$values[] = isset($ptl_fields_temps['p_prod_del']) && $ptl_fields_temps['p_prod_del'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($basket_action.'?pl_plugin_order['.$pm_id.'_'.$value[1].$del_params.']=0', $basket_action, $pm_id.'_'.$value[1].$del_params), $ptl_fields_temps['p_prod_del']) : '';
				}
			}
			else
			{
				$values[] = '';
			}

			//	DEL_ALL_ORDERS
			$values[] = $basket_action.'?pl_plugin_order['.$pm_id.'_'.$value[1].']=del_orders';

			//	COMPARE
			if(isset($_COOKIE['sb_compare'][$pm_id.'_'.$value[1]]))
			{
				$values[] = isset($ptl_fields_temps['p_compare_del']) && $ptl_fields_temps['p_compare_del'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($compare_action.'?pl_plugin_compare['.$pm_id.'_'.$value[1].']=0', $compare_action, $pm_id.'_'.$value[1]), $ptl_fields_temps['p_compare_del']) : '';
			}
			else
			{
				$values[] = isset($ptl_fields_temps['p_compare_add']) && $ptl_fields_temps['p_compare_add'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($compare_action.'?pl_plugin_compare['.$pm_id.'_'.$value[1].']=1', $compare_action, $pm_id.'_'.$value[1]), $ptl_fields_temps['p_compare_add']) : '';
			}

			//  DEL_COMPARE
			$values[] = $compare_action.'?plugin_id='.$pm_id.'&del_compare=1';

			//	RESERVED
			if(isset($ptl_fields_temps['p_reserved_add']))
			{
				if(isset($ptl_fields_temps['p_reserved_add']) && sb_strpos($ptl_fields_temps['p_reserved_add'], '{OPTIONS_') !== false)
				{
					$value[14] = 0;
				}
				elseif(sb_strpos($ptl_element, '{RESERVING}') !== false)
				{
					$reserved = sql_query('SELECT b.b_reserved FROM sb_basket b WHERE b.b_id_mod = '.intval($pm_id).' AND b.b_id_el = ?d AND b.b_prop = "" AND b.b_reserved = 1 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where, $value[1]);
					if($reserved)
					{
						$value[14] = 1;
					}
				}

				if(isset($value[14]) && $value[14] == 1)
				{
					// Если товар добавлен в резерв
					$values[] = isset($ptl_fields_temps['p_reserved_del']) && $ptl_fields_temps['p_reserved_del'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($basket_action.'?pl_plugin_reserving['.$pm_id.'_'.$value[1].']=0', $basket_action, $pm_id.'_'.$value[1]), $ptl_fields_temps['p_reserved_del']) : '';
				}
				else
				{
					// Макет резерва
					$p_prod_add = isset($ptl_fields_temps['p_reserved_add']) ? trim($ptl_fields_temps['p_reserved_add']) : '';
					if($p_prod_add != '')
					{
						if(sb_strpos($p_prod_add, '{ID}') !== false)
						{
							// Идентификаторы пользовательских полей
							$p_prod_add = str_replace('{ID}', $value[1], $p_prod_add);
						}

						if(sb_strpos($p_prod_add, '{OPTIONS_') !== false)
						{
							$matches = array();
							if(preg_match_all('/{OPTIONS_([0-9]+)}/',$p_prod_add, $matches))
							{
								foreach($elems_fields as $value1)
								{
									if(in_array($value1['id'], $matches[1]))
									{
										// Если поле нужно выводить
										$f_matches = array();

										// Определяю макет поля
										$reserve_field_tpl = $ptl_fields_temps['p_reserve_field_'.$value1['id']];

										// Достаю идентификаторы элементов
										$res_fields = sql_query('SELECT user_f_'.$value1['id'].' FROM  sb_plugins_'.$pm_id.' WHERE p_id = ?', (SB_DEMO_SITE && isset($p_demo_id) && $p_demo_id > 0 ? $p_demo_id : $value[1]));
										$elems = '0';
										if($res_fields && $res_fields[0][0] != '')
											$elems .= ','.$res_fields[0][0];

										// Определяю справочник или модуль
										if(preg_match('/{PROP([0-9])}/',$reserve_field_tpl) || sb_strpos($reserve_field_tpl, '{SPR_TITLE}') !== false)
										{
											// Достаю элементы справочника
											$res_sprav = sql_query('SELECT s_id, s_title, s_prop1, s_prop2, s_prop3 FROM sb_sprav WHERE s_id IN ('.$elems.') AND s_active=1 ORDER BY s_sort, s_title');
								            if($res_sprav)
								            {
								            	$p_prod_add_options = '';
								            	// Если есть справочники
								        		foreach($res_sprav as $sprav_value)
								            	{
								            		$p_prod_add_options .= str_replace(array('{SELECTED}', '{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}'), array_merge(array(''), $sprav_value), $reserve_field_tpl);
								            	}
								            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
								            }
										}
										elseif(preg_match_all('/OPT_([^}]+)/',$reserve_field_tpl, $f_matches))
										{
											// Узнаю нужные поля элемента
								            $elems_tags = array();
								            $sql_fields = '';
								            foreach($f_matches[1] as $el_fl)
								            {
								            	$field_name = sb_strtolower(sb_str_replace('STD_', '', $el_fl));
								            	$sql_fields .= ','.$field_name;
								            	$elems_tags[] = '{OPT_'.$el_fl.'}';
								            }

											$res_elems = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id IN ('.$elems.') ORDER BY p_sort, p_title');
											if($res_elems)
								            {
								            	$p_prod_add_options = '';
								            	// Если есть элементы
								            	foreach($res_elems as $elems_value)
								            	{
								            		if (SB_DEMO_SITE)
								            		{
									            		$elems_demo_id = $elems_value[0];

									            		if ($elems_demo_id > 0)
									            		{
									            			$res_demo = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id=?d', $elems_demo_id);
									            			if ($res_demo)
									            			{
									            				$res_demo[0][0] = $elems_value[0];
									            				$res_demo[0][1] = $elems_value[1];
									            				$elems_value = $res_demo[0];
									            			}
									            		}
								            		}

								            		$p_prod_add_options .= str_replace(array_merge(array('{SELECTED}', '{DEMO_ID}', '{ID}'), $elems_tags), array_merge(array(''), $elems_value), $reserve_field_tpl);
								            	}

								            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
								            }
										}
									}
								}
							}
						}
					}
					$element_id = $pm_id.'_'.$value[1];
					$values[] = isset($p_prod_add) && $p_prod_add != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($basket_action.'?pl_plugin_reserving['.$pm_id.'_'.$value[1].']=1', $basket_action, $pm_id.'_'.$value[1]), $p_prod_add) : '';
				}
			}
			else
			{
				$values[] = '';
			}

			$votes_sum = ($value[10] != '' && !is_null($value[10]) ? $value[10] : 0); // VOTES_SUM
	        $votes_count = ($value[11] != '' && !is_null($value[11]) ? $value[11] : 0); // VOTES_COUNT
	        $votes_rating = ($value[12] != '' && !is_null($value[12]) ? sprintf('%.2f', $value[12]) : 0); // RATING

	        // VOTES_FORM
	        if($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1)
	        {
				$res_vote = fVoting_Form_Submit($ptl_votes_id, 'pl_plugin_'.$pm_id, $value[1], $votes_sum, $votes_count, $votes_rating);
	        }

	        $values[] = $votes_sum; // VOTES_SUM
	        $values[] = $votes_count; // VOTES_COUNT
	        $values[] = $votes_rating; // RATING

	        if($view_rating_form && ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1))
	        {
	            $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ptl_votes_id, 'pl_plugin_'.$pm_id, $value[1])); // VOTES_FORM
	        }
	        else
	        {
	            $values[] = ''; // VOTES_FORM
	        }

	        $c_count = (isset($comments_count[$value[1]]) ? $comments_count[$value[1]] : 0);

	        $add_comments = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_edit_cat_ids) || $categs[$value[0]]['closed'] != 1);
	        if ($add_comments)
	        {
     			$mod_emails = array();

				$mod_params_emails = isset($params['moderate_email']) ? explode(' ', trim(str_replace(',', ' ', $params['moderate_email']))) : array();
				$mod_categs_emails = array();
				$mod_users_emails = array();

				if(isset($categs[$value[0]]['fields']['categs_moderate_email']) && $categs[$value[0]]['fields']['categs_moderate_email'] != '')
				{
					$mod_categs_emails = array_merge($mod_categs_emails, explode(' ', trim(str_replace(',', ' ', $categs[$value[0]]['fields']['categs_moderate_email']))));
				}

				$moderates_list = array();

				if(isset($categs[$value[0]]['fields']['moderates_list']) && trim($categs[$value[0]]['fields']['moderates_list']) != '')
				{
					$moderates_list = explode('^', $categs[$value[0]]['fields']['moderates_list']);
				}

				$u_ids = $cat_mod_ids = array();
				foreach($moderates_list as $val)
				{
					if($val[0] == 'g')
					{
						$cat_mod_ids[] = intval(substr($val, 1));
					}
					elseif($val[0] == 'u')
					{
						$u_ids[] = intval(substr($val, 1));
					}
				}

				$res1 = $res2 = array();
				if(count($u_ids) > 0)
				{
					$res1 = sql_param_query('SELECT u_email FROM sb_users WHERE u_id IN (?a)', $u_ids);
					if(!$res1)
						$res1 = array();
				}

				if(count($cat_mod_ids) > 0 )
				{
					$res2 = sql_param_query('SELECT u.u_email FROM sb_users u, sb_catlinks l
							WHERE l.link_cat_id IN (?a) AND l.link_el_id = u.u_id', $cat_mod_ids);
					if(!$res2)
						$res2 = array();
				}

				$res_mail = array_merge($res1, $res2);
				if($res_mail)
				{
					foreach($res_mail as $val)
					{
						$mod_users_emails[] = trim($val[0]);
					}
				}

				foreach ($mod_params_emails as $email)
				{
					$email = trim($email);
					if ($email != '' && !in_array($email, $mod_emails))
					{
						$mod_emails[] = $email;
					}
				}

	        	foreach ($mod_categs_emails as $email)
				{
					$email = trim($email);
					if ($email != '' && !in_array($email, $mod_emails))
					{
						$mod_emails[] = $email;
					}
				}

	        	foreach ($mod_users_emails as $email)
				{
					$email = trim($email);
					if ($email != '' && !in_array($email, $mod_emails))
					{
						$mod_emails[] = $email;
					}
				}

				$str_emails = implode(' ', $mod_emails);
	            if (fComments_Add_Comment($ptl_comments_id, 'pl_plugin_'.$pm_id, $value[1], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
	                $c_count++;
	        }

	        $values[] = $c_count; // COUNT_COMMENTS

	        if ($view_comments_form)
	        {
	            $values[] = fComments_Get_Form($ptl_comments_id, 'pl_plugin_'.$pm_id, $value[1], $add_comments); // FORM_COMMENTS
	        }
	        else
	        {
	            $values[] = ''; // FORM_COMMENTS
	        }

	        if ($view_comments_list)
	        {
				$exists_rights = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_read_cat_ids) || $categs[$value[0]]['closed'] != 1);
				$values[] = fComments_Get_List($ptl_comments_id, 'pl_plugin_'.$pm_id, $value[1], $add_comments, '', 0, $exists_rights); // LIST_COMMENTS
	        }
	        else
	        {
	            $values[] = ''; // LIST_COMMENTS
	        }

	        if ($categs_output && $value[0] != $cur_cat_id)
	        {
	            if ($cur_cat_id != 0)
	            {
	                // низ вывода раздела
	                while ($col < $ptl_count)
	                {
	                    $result .= $ptl_empty;
	                    $col++;
	                }

	                $result .= str_replace($tags, $old_values, $ptl_categ_bottom);
	            }

	            // верх вывода раздела
	            $result .= str_replace($tags, $values, $ptl_categ_top);
	            $col = 0;
	        }

	        if ($col >= $ptl_count)
	        {
	            $result .= $ptl_delim;
	            $col = 0;
	        }

            //Вывод списка товаров заказа
            if (sb_strpos($ptl_element, '{ORDER_LIST}') !== false
            || sb_strpos($ptl_element, '{COUNT_POS}') !== false
            || sb_strpos($ptl_element, '{COUNT_GOODS}') !== false
            || sb_strpos($ptl_element, '{TOVAR_SUM}') !== false
            || sb_strpos($ptl_element, '{TOVAR_SUM_DISCOUNT}') !== false
            )
            {
                $order_list = fPlugin_Maker_Elem_List_FromXML($pm_id, $ptl_fields_temps, $params, $value[1]);
                if ($order_list)
                {
                    foreach ($order_list as $key => $data)
                    {
                        $tags[] = '{' . strtoupper($key) . '}';
                        $values[] = $data;
                    }
                }
            }

	        $result .= str_replace($tags, $values, $ptl_element);

	        $cur_cat_id = $value[0];
	        $col++;
	    }

	    while ($col < $ptl_count)
	    {
	        $result .= $ptl_empty;
	        $col++;
	    }

	    if ($categs_output)
	    {
	        // низ вывода раздела
	        $result .= str_replace($tags, $values, $ptl_categ_bottom);
	    }

	    // низ вывода списка элементов
			// Заменяем значение селекта "Кол-во на странице" селектед
		if(isset($_REQUEST['num_'.$tag_id]))
	    {
	    	$ptl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $ptl_bottom);
	    }
	    elseif(isset($_COOKIE[$num_cookie_name]))
	    {
	    	$ptl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $ptl_bottom);
	    }

	    $result .= str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $elems_total, 'num_'.$tag_id),$flds_vals), $ptl_bottom);
	    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	    if (is_null($ids_elems) && (!isset($params['use_id_el_filter']) || $params['use_id_el_filter'] != 1))
		{
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $result);
	    }
	    elseif(is_null($ids_elems))
	    {
	    	//чистим код от инъекций
            $result = sb_clean_string($result);

			eval(' ?>'.$result.'<?php ');
			$GLOBALS['sbCache']->setLastModified(time());
	    }
	    else
	    {
			return $result;
	    }
	}
}

if (!function_exists('fPlugin_Maker_Elem_Full'))
{
	/**
	 * Вывод выбранного элемента
	 *
	 */
	function fPlugin_Maker_Elem_Full($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
	{
		$params = unserialize(stripslashes($params));
		$pm_id = intval($params['pm_id']);

		$p_id = $GLOBALS['sbCache']->check('pl_plugin_'.$pm_id, $tag_id, array($el_id, $temp_id, $params));
		if($p_id)
		{
			@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
			fClouds_Init_Tags('pl_plugin_'.$pm_id, array($p_id));
			return;
		}

		if(!isset($params['cena']))
			$params['cena'] = '';

	    $res = sbQueryCache::query('SELECT pm_title, pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);
	    if (!$res)
	    {
	        // указанный модуль был удален
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    list($pm_title, $pm_elems_settings) = $res[0];

	    if ($pm_elems_settings != '')
	        $pm_elems_settings = unserialize($pm_elems_settings);
	    else
	        $pm_elems_settings = array();

	    if($linked > 0)
			$_GET['pl'.$pm_id.'_id'] = $linked;

	    if (!isset($_GET['pl'.$pm_id.'_sid']) && !isset($_GET['pl'.$pm_id.'_id']))
	    {
	        if($linked > 0)
	            unset($_GET['pl'.$pm_id.'_id']);
			return;
	    }

	    $cat_id = -1;
	    if (isset($_GET['pl'.$pm_id.'_scid']) || isset($_REQUEST['pl'.$pm_id.'_cid']))
	    {
	        // используется связь с выводом разделов и выводить следует элементы из соотв. раздела
	        if (isset($_REQUEST['pl'.$pm_id.'_cid']))
	        {
	            $cat_id = intval($_REQUEST['pl'.$pm_id.'_cid']);
	        }
	        else
	        {
	            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_scid']);
	            if ($res)
	            {
					$cat_id = $res[0][0];
	            }
				else
	            {
	                $cat_id = intval($_GET['pl'.$pm_id.'_scid']);
	            }

	            $_REQUEST['pl'.$pm_id.'_cid'] = $cat_id;
	        }
	    }

		// вытаскиваем макет дизайна
		//$res = sql_param_query('SELECT ptf_lang, ptf_element, ptf_fields_temps, ptf_categs_temps, ptf_checked, ptf_votes_id, ptf_comments_id, ptf_user_data_id, ptf_tags_list_id
	    //            FROM sb_plugins_temps_full WHERE ptf_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_plugins_temps_full', $temp_id);

		if (!$res)
	    {
	        if($linked > 0)
	            unset($_GET['pl'.$pm_id.'_id']);

	        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], $pm_title), SB_MSG_WARNING);
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    list($ptf_lang, $ptf_element, $ptf_fields_temps, $ptf_categs_temps, $ptf_checked, $ptf_votes_id, $ptf_comments_id, $ptf_user_data_id, $ptf_tags_list_id) = $res[0];

	    $ptf_fields_temps = unserialize($ptf_fields_temps);
	    $ptf_categs_temps = unserialize($ptf_categs_temps);

	    // вытаскиваем пользовательские поля новости и раздела
	    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
        $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_plugin_'.$pm_id);

	    $elems_fields = array();
	    $categs_fields = array();

	    $categs_sql_fields = array();
	    $elems_fields_select_sql = '';

	    $tags = array();

		if ($ptf_checked != '')
	    {
	        $ptf_checked = explode(' ', $ptf_checked);
	    }
	    else
	    {
	    	$ptf_checked = array();
	    }

	    // формируем SQL-запрос для пользовательских полей
	    if ($res)
	    {
	        if($res[0][0] != '')
	        {
	            $elems_fields = unserialize($res[0][0]);
	        }
	        if($res[0][1] != '')
	        {
	            $categs_fields = unserialize($res[0][1]);
	        }

	        if ($elems_fields)
	        {
	        	$new_ptf_checked = array();

	            foreach ($elems_fields as $value)
	            {
	                if (isset($value['sql']) && $value['sql'] == 1)
	                {
	                	if (in_array($value['id'], $ptf_checked))
	                	{
	                		$new_ptf_checked[] = $value['id'];
	                	}

	                    $elems_fields_select_sql .= ', p.user_f_'.$value['id'];

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
	                }
	            }

	            $ptf_checked = $new_ptf_checked;
	        }

	        if ($categs_fields)
	        {
	            foreach ($categs_fields as $value)
	            {
	                if (isset($value['sql']) && $value['sql'] == 1)
	                {
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

	                    $categs_sql_fields[] = 'user_f_'.$value['id'];
	                }
	            }
	        }
	    }

	    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
	    $elems_fields_where_sql = '';
		foreach ($ptf_checked as $value)
	    {
	        $elems_fields_where_sql .= ' AND p.user_f_'.$value.'=1';
	    }

		$user_link_id_sql = '';
	    if(isset($params['registred_users']) && $params['registred_users'] == 1)
	    {
			if(isset($_SESSION['sbAuth']))
			{
				$user_link_id_sql = ' AND p.p_user_id = '.intval($_SESSION['sbAuth']->getUserId());
			}
			else
			{
				if($linked > 0)
	    		{
	    		    unset($_GET['pl'.$pm_id.'_id']);
					sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT_PLUGIN_MAKER, $_SERVER['PHP_SELF']), SB_MSG_WARNING);
	    			return;
	    		}
	    		else
	    		{
					sb_404();
					return;
	    		}
			}
		}

		$active_sql = '';
	    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	    {
			$now = time();

			if (SB_DEMO_SITE)
			{
				$active_sql = ' AND (p.p_demo_id > 0 OR (p.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.'))) ';
			}
			else
			{
				$active_sql = ' AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.') ';
			}
	    }

		if ($cat_id != -1  && $linked < 1)
	    {
			$cat_dop_sql = 'AND c.cat_id="'.$cat_id.'"';
	    }
	    else
	    {
	        $cat_dop_sql = 'AND c.cat_ident="pl_plugin_'.$pm_id.'"';
	    }

		$basket_arg = ' ';
		$basket_where = ' AND b_hash = ?';

		if(isset($_SESSION['sbAuth']))
		{
			$basket_arg = $_SESSION['sbAuth']->getUserId();
			$basket_where = ' AND b_id_user = ?d';
		}
		elseif(isset($_COOKIE['pl_basket_user_id']))
		{
			$basket_arg = preg_replace('/[^A-Za-z0-9]+/', '', $_COOKIE['pl_basket_user_id']);
		}

	    if (isset($_GET['pl'.$pm_id.'_id']))
	    {
	        $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
	                          p.p_id, p.p_title, p.p_url, p.p_sort,
	                          p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5,
	                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS p_rating,
	                          b.b_count_el, b.b_reserved,
	                          p.p_user_id, p.p_demo_id
	                          '.$elems_fields_select_sql.'
	                          FROM sb_plugins_'.$pm_id.' p LEFT JOIN sb_vote_results v ON v.vr_el_id=?d AND v.vr_plugin="pl_plugin_'.$pm_id.'"
							  LEFT JOIN  sb_basket b ON p.p_id = b.b_id_el AND b.b_id_mod = ?d AND b.b_reserved = 0 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where.',
	                          sb_categs c, sb_catlinks l
	                          WHERE p.p_id=?d AND l.link_el_id=p.p_id AND c.cat_id=l.link_cat_id '.
							  $cat_dop_sql.$active_sql.$elems_fields_where_sql.$user_link_id_sql, $_GET['pl'.$pm_id.'_id'], $pm_id, $basket_arg, $_GET['pl'.$pm_id.'_id']);
	    }
	    else
	    {
	        $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
	                          p.p_id, p.p_title, p.p_url, p.p_sort,
	                          p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5,
	                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS p_rating,
	                          b.b_count_el, b.b_reserved,
	                          p.p_user_id, p.p_demo_id
	                          '.$elems_fields_select_sql.'
	                          FROM sb_plugins_'.$pm_id.' p LEFT JOIN sb_vote_results v ON v.vr_el_id=p.p_id AND v.vr_plugin="pl_plugin_'.$pm_id.'"
  							  LEFT JOIN  sb_basket b ON p.p_id = b.b_id_el AND b.b_id_mod = ?d AND b.b_reserved = 0 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where.',
	                          sb_categs c, sb_catlinks l
	                          WHERE p.p_url=? AND l.link_el_id=p.p_id AND c.cat_id=l.link_cat_id '.
	        				  $cat_dop_sql.$active_sql.$elems_fields_where_sql.$user_link_id_sql, $pm_id, $basket_arg, $_GET['pl'.$pm_id.'_sid']);
			if (!$res)
			{
				$res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
	                          p.p_id, p.p_title, p.p_url, p.p_sort,
	                          p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5,
	                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS p_rating,
	                          b.b_count_el, b.b_reserved,
	                          p.p_user_id, p.p_demo_id
	                          '.$elems_fields_select_sql.'
	                          FROM sb_plugins_'.$pm_id.' p LEFT JOIN sb_vote_results v ON v.vr_el_id=?d AND v.vr_plugin="pl_plugin_'.$pm_id.'"
							  LEFT JOIN  sb_basket b ON p.p_id = b.b_id_el AND b.b_id_mod = ?d AND b.b_reserved = 0 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where.',
							  sb_categs c, sb_catlinks l
	                          WHERE p.p_id=?d AND l.link_el_id=p.p_id AND c.cat_id=l.link_cat_id '.
							  $cat_dop_sql.$active_sql.$elems_fields_where_sql.$user_link_id_sql, $_GET['pl'.$pm_id.'_sid'], $pm_id, $basket_arg, $_GET['pl'.$pm_id.'_sid']);
			}
		}

		if (!$res)
		{
	    	if($linked > 0)
	    	{
	    	    unset($_GET['pl'.$pm_id.'_id']);
	    		return;
	    	}
	    	else
	    	{
				sb_404();
	    	}
	    }

	    $view_rating_form = (sb_strpos($ptf_element, '{VOTES_FORM}') !== false && $ptf_votes_id > 0);
	    $view_comments_list = (sb_strpos($ptf_element, '{LIST_COMMENTS}') !== false && $ptf_comments_id > 0);
	    $view_comments_form = (sb_strpos($ptf_element, '{FORM_COMMENTS}') !== false && $ptf_comments_id > 0);
	    $add_rating = true;
	    $add_comments = true;

	    $res[0][5] = urlencode($res[0][5]); // CAT_URL
	    $res[0][8] = urlencode($res[0][8]); // ELEM_URL

	    if ($res[0][4])
	    {
	        $cat_ids = sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_plugin_'.$pm_id.'_read');
	        if (count($cat_ids) == 0)
	        {
	            if($linked > 0)
	                unset($_GET['pl'.$pm_id.'_id']);

	            $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	            return;
	        }

	        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_plugin_'.$pm_id.'_vote')) == 0)
	        {
	            $view_rating_form = false;
	            $add_rating = false;
	        }

	        if ($view_comments_list && count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_plugin_'.$pm_id.'_comments_read')) == 0)
	        {
	            $view_comments_list = false;
	        }

	        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_plugin_'.$pm_id.'_comments_edit')) == 0)
	        {
				$add_comments = false;
	        }
	    }

	    $num_fields = count($res[0]);

	    if (SB_DEMO_SITE)
	    {
	    	// вывод не демо-сайте, проверяем статус публикации и p_demo_id
	    	$p_demo_id = $res[0][21];

	    	if ($p_demo_id > 0)
	    	{
	    		$demo_res = sbQueryCache::query('SELECT p.p_active, p.p_pub_start, p.p_pub_end, p.p_title, p.p_sort, p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5
	                          '.$elems_fields_select_sql.'
	                          FROM sb_plugins_'.$pm_id.' p
	                          WHERE p.p_id=?d', $p_demo_id);

	    		if (!$demo_res)
	    		{
	    			if($linked > 0)
			    	{
			    	    unset($_GET['pl'.$pm_id.'_id']);
			    		return;
			    	}
			    	else
			    	{
						sb_404();
			    	}
	    		}

	    		// есть демо-элемент
	    		$p_active = $demo_res[0][0];
	    		$p_pub_start = $demo_res[0][1];
	    		$p_pub_end = $demo_res[0][2];

		    	$demo_pub_status = explode(',', sb_get_workflow_demo_statuses());

		    	if (!in_array($p_active, $demo_pub_status) || $p_pub_start && $p_pub_start > time() || $p_pub_end && $p_pub_end < time())
		    	{
		    		// элемент снят с публикации на демо-сайте
			    	if($linked > 0)
			    	{
			    	    unset($_GET['pl'.$pm_id.'_id']);
			    		return;
			    	}
			    	else
			    	{
						sb_404();
			    	}
		    	}

		    	// подменяем поля выводимого элемента полями демо-элемента на демо-сайте
	            $res[0][7] = $demo_res[0][3];
	            $res[0][9] = $demo_res[0][4];
	            $res[0][10] = $demo_res[0][5];
	            $res[0][11] = $demo_res[0][6];
	            $res[0][12] = $demo_res[0][7];
	            $res[0][13] = $demo_res[0][8];
	            $res[0][14] = $demo_res[0][9];

				if ($num_fields > 22)
			    {
			        for ($i = 22; $i < $num_fields; $i++)
			        {
				       $res[0][$i] = $demo_res[0][$i - 12];
			        }
				}
	    	}
	    }

	    $cat_count = '';
	    if (sb_substr_count($ptf_element, '{CAT_COUNT}') > 0)
	    {
	        $res_cat = sql_param_query('SELECT COUNT(link_el_id) FROM sb_catlinks
	                WHERE link_cat_id=?d AND link_src_cat_id != ?d', $res[0][0], $res[0][0]);

	        if ($res_cat)
	        {
	            $cat_count = $res_cat[0][0];
	        }
	    }

	    $comments_count = array();
	    if(sb_strpos($ptf_element, '{COUNT_COMMENTS}') !== false)
	    {
			require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
			$comments_count = fComments_Get_Count(array($res[0][6]), 'pl_plugin_'.$pm_id);
		}

		$tags = array_merge($tags, array('{CAT_TITLE}',
	                                     '{CAT_LEVEL}',
	                                     '{CAT_COUNT}',
	                                     '{CAT_ID}',
	                                     '{CAT_URL}',
	                                     '{ID}',
	                                     '{ELEM_URL}',
	                                     '{TITLE}',
										 '{EDIT_LINK}',
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
	                                     '{LIST_COMMENTS}',
									 	 '{ELEM_USER_LINK}',
										 '{TAGS}',
										 '{USER_DATA}',
										 '{CHANGE_DATE}',
										 '{ELEM_PREV}',
										 '{ELEM_NEXT}',
										 ));

	    $num_cat_fields = count($categs_sql_fields);

		require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		$dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
		$dop_values = array($res[0][6], strip_tags($res[0][8]), strip_tags($res[0][7]), $res[0][0], strip_tags($res[0][5]), strip_tags($res[0][1]));

		$values = array();
		if ($num_fields > 22)
	    {
	        for ($i = 22; $i < $num_fields; $i++)
	        {
		       $values[] = $res[0][$i];
	        }

			$allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;

			$values = sbLayout::parsePluginFields($elems_fields, $values, $ptf_fields_temps, $dop_tags, $dop_values, $ptf_lang, '', '', $allow_bb, $link_level, $ptf_element);
		}

		$res[0][3] = isset($res[0][3]) && $res[0][3] != '' ? unserialize($res[0][3]) : array();
	    if ($num_cat_fields > 0)
	    {
	        $cat_values = array();
	        foreach ($categs_sql_fields as $cat_field)
	        {
	            if (isset($res[0][3][$cat_field]))
	                $cat_values[] = $res[0][3][$cat_field];
	            else
	                $cat_values[] = null;
	        }

	        $allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;

			$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ptf_categs_temps, $dop_tags, $dop_values, $ptf_lang, '', '', $allow_bb, $link_level, $ptf_element);
			$values = array_merge($values, $cat_values);
	    }

		 //Ссылка "редактировать"
		if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 &&(!isset($_SESSION['sbAuth']) || ($res[0][17] != $_SESSION['sbAuth']->getUserId())))
	    {
	        $edit = '';
	    }
	    else
	    {
	        $edit = '';
		    $edit_page = '';
		    $edit_ext = '';
		    if(isset($params['edit_page']))
		    {
		    	list($edit_page, $edit_ext) = sbGetMorePage($params['edit_page']);
		    }

			if ($edit_page == '')
		    {
		    	$edit_href = 'javascript: void(0);';
		    }
			else
		    {
		    	//Достаю информацию о разделах
		    	$categs = sbQueryCache::query("SELECT cat_url, cat_title FROM sb_categs WHERE cat_id = ?d",$res[0][0]);
		    	$edit_href = $edit_page;
		        if (sbPlugins::getSetting('sb_static_urls') == 1)
		        {
		        	// ЧПУ
					$edit_href .= ($categs[0][0] != '' ? urlencode($categs[0][0]).'/' : $res[0][0].'/').
		            ($res[0][8] != '' ? $res[0][8] : $res[0][6]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
				}
		        else
		        {
		        	$edit_href .= '?pl'.$pm_id.'_cid='.$res[0][0].'&pl'.$pm_id.'_id='.$res[0][6];
				}
			}

			if ($edit_page != '' && isset($ptf_fields_temps['p_edit_link']))
			{
				$edit = str_replace(array_merge(array('{LINK}'),$dop_tags), array_merge(array($edit_href),$dop_values), $ptf_fields_temps['p_edit_link']);
			}
		}

	    $values[] = $res[0][1]; // CAT_TITLE
	    $values[] = $res[0][2] + 1; // CAT_LEVEL
	    $values[] = $cat_count; // CAT_COUNT
	    $values[] = $res[0][0]; // CAT_ID
	    $values[] = $res[0][5]; // CAT_URL
	    $values[] = $res[0][6]; // ID
	    $values[] = $res[0][8]; // ELEM_URL
	    $values[] = $res[0][7]; // TITLE
	    $values[] = $edit;
	    $values[] = $res[0][9]; // SORT

	    $p_price1 = $res[0][10];
	    $p_price2 = $res[0][11];
	    $p_price3 = $res[0][12];
	    $p_price4 = $res[0][13];
	    $p_price5 = $res[0][14];

    	if ((!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] == 0) && isset($pm_elems_settings['price1_formula']))
    	{
    		// рассчитываем цену
    		$p_price1 = fPlugin_Maker_Quoting($pm_id, $res[0][6], $pm_elems_settings['price1_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    	}

    	if ((!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] == 0) && isset($pm_elems_settings['price2_formula']))
    	{
    		// рассчитываем цену
    		$p_price2 = fPlugin_Maker_Quoting($pm_id, $res[0][6], $pm_elems_settings['price2_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    	}

    	if ((!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] == 0) && isset($pm_elems_settings['price3_formula']))
    	{
    		// рассчитываем цену
    		$p_price3 = fPlugin_Maker_Quoting($pm_id, $res[0][6], $pm_elems_settings['price3_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    	}

    	if ((!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] == 0) && isset($pm_elems_settings['price4_formula']))
    	{
    		// рассчитываем цену
    		$p_price4 = fPlugin_Maker_Quoting($pm_id, $res[0][6], $pm_elems_settings['price4_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    	}

    	if ((!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] == 0) && isset($pm_elems_settings['price5_formula']))
    	{
    		// рассчитываем цену
    		$p_price5 = fPlugin_Maker_Quoting($pm_id, $res[0][6], $pm_elems_settings['price5_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    	}

        $values[] = !is_null($p_price1) && isset($ptf_fields_temps['p_price1']) && trim($ptf_fields_temps['p_price1']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price1)), $ptf_fields_temps['p_price1']) : '';	 // PRICE_1
        $values[] = !is_null($p_price2) && isset($ptf_fields_temps['p_price2']) && trim($ptf_fields_temps['p_price2']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price2)), $ptf_fields_temps['p_price2']) : '';	 // PRICE_2
        $values[] = !is_null($p_price3) && isset($ptf_fields_temps['p_price3']) && trim($ptf_fields_temps['p_price3']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price3)), $ptf_fields_temps['p_price3']) : '';	 // PRICE_3
        $values[] = !is_null($p_price4) && isset($ptf_fields_temps['p_price4']) && trim($ptf_fields_temps['p_price4']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price4)), $ptf_fields_temps['p_price4']) : '';	 // PRICE_4
        $values[] = !is_null($p_price5) && isset($ptf_fields_temps['p_price5']) && trim($ptf_fields_temps['p_price5']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($p_price5)), $ptf_fields_temps['p_price5']) : '';	 // PRICE_5

		$values[] = isset($res[0][18]) && isset(${'p_price'.$params['cena']}) && isset($ptf_fields_temps['p_sum_order']) && trim($ptf_fields_temps['p_sum_order']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array(${'p_price'.$params['cena']} * $res[0][18])), $ptf_fields_temps['p_sum_order']) : '';	 // PRICE_5
		$values[] = isset($res[0][18]) && isset($ptf_fields_temps['p_goods_count']) && trim($ptf_fields_temps['p_goods_count']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($res[0][18] != '' ? $res[0][18] : '0')), $ptf_fields_temps['p_goods_count']) : '';	//	PRICE_5

		$basket_action = '/cms/admin/basket.php';

        //	ORDER
		if(isset($ptf_fields_temps['p_prod_add']) && sb_strpos($ptf_fields_temps['p_prod_add'], '{OPTIONS_'))
			$res[0][18] = 0;

		if (isset($ptf_fields_temps['p_prod_add']) && strpos($ptf_fields_temps['p_prod_add'], '{VALUE}') !== false || !isset($res[0][18]) || $res[0][18] == 0)
		{
			$p_prod_add = (isset($ptf_fields_temps['p_prod_add']) ? trim($ptf_fields_temps['p_prod_add']) : '');

			if ($p_prod_add)
			{
				if(sb_strpos($p_prod_add, '{ID}') !== false)
				{
					// Идентификаторы пользовательских полей
					$p_prod_add = str_replace('{ID}', $res[0][6], $p_prod_add);
				}

				if(sb_strpos($p_prod_add, '{OPTIONS_') !== false)
				{
					if(preg_match_all('/{OPTIONS_([0-9]+)}/', $p_prod_add, $matches))
					{
						foreach($elems_fields as $value1)
						{
							if(isset($value1['id']) && in_array($value1['id'], $matches[1]))
							{
								// Определяю макет поля
								$cart_field_tpl = $ptf_fields_temps['p_cart_field_'.$value1['id']];
								if (trim($cart_field_tpl) == '')
									continue;

								// Если поле нужно выводить
								$f_matches = array();

								// Достаю идентификаторы элементов
								$res_fields = sql_query('SELECT user_f_'.$value1['id'].' FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', (SB_DEMO_SITE && isset($p_demo_id) && $p_demo_id > 0 ? $p_demo_id : $res[0][6]));
								$elems = '0';

								if($res_fields && $res_fields[0][0] != '')
									$elems .= ','.$res_fields[0][0];

								// Определяю справочник или модуль
								if(preg_match('/{PROP([0-9])}/', $cart_field_tpl) || sb_strpos($cart_field_tpl, '{SPR_TITLE}') !== false)
								{
									// Достаю элементы справочника
									$res_sprav = sql_query('SELECT s_id, s_title, s_prop1, s_prop2, s_prop3 FROM sb_sprav WHERE s_id IN ('.$elems.') AND s_active=1 ORDER BY s_sort, s_title');
						            if($res_sprav)
						            {
						            	$p_prod_add_options = '';
						            	// Если есть справочники
						            	foreach($res_sprav as $sprav_value)
						            	{
						            		$p_prod_add_options .= str_replace(array('{SELECTED}', '{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}'), array_merge(array(''), $sprav_value), $cart_field_tpl);
						            	}
						            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
						            }
								}
								elseif(preg_match_all('/OPT_([^}]+)/', $cart_field_tpl, $f_matches))
								{
									// Узнаю нужные поля элемента
						            $elems_tags = array();
						            $sql_fields = '';
						            foreach($f_matches[1] as $el_fl)
						            {
						            	$field_name = sb_strtolower(sb_str_replace('STD_', '', $el_fl));
						            	$sql_fields .= ', '.$field_name;
						            	$elems_tags[] = '{OPT_'.$el_fl.'}';
						            }

									$res_elems = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id IN ('.$elems.') ORDER BY p_sort, p_title');
									if($res_elems)
						            {
						            	$p_prod_add_options = '';

						            	// Если есть элементы
						            	foreach($res_elems as $elems_value)
						            	{
						            		if (SB_DEMO_SITE)
						            		{
							            		$elems_demo_id = $elems_value[0];

							            		if ($elems_demo_id > 0)
							            		{
							            			$res_demo = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id=?d', $elems_demo_id);
							            			if ($res_demo)
							            			{
							            				$res_demo[0][0] = $elems_value[0];
							            				$res_demo[0][1] = $elems_value[1];
							            				$elems_value = $res_demo[0];
							            			}
							            		}
						            		}

						            		$p_prod_add_options .= str_replace(array_merge(array('{DEMO_ID}', '{ID}'), $elems_tags), $elems_value, $cart_field_tpl);
						            	}

						            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
						            }
								}
							}
						}
					}
				}
			}

			$values[] = $p_prod_add != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}', '{VALUE}'), array($basket_action.'?pl_plugin_order['.$pm_id.'_'.$res[0][6].']= 1', $basket_action, $pm_id.'_'.$res[0][6], isset($value[18]) && $value[18] > 0 ? $value[18] : 0), $p_prod_add) : '';
		}
		else
		{
			$values[] = isset($ptf_fields_temps['p_prod_del']) && $ptf_fields_temps['p_prod_del'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($basket_action.'?pl_plugin_order['.$pm_id.'_'.$res[0][6].']=0', $basket_action, $pm_id.'_'.$res[0][6]), $ptf_fields_temps['p_prod_del']) : '';
		}

		// DEL_ALL_ORDERS
		$values[] = '/cms/admin/basket.php?pl_plugin_order['.$pm_id.'_'.$res[0][6].']=del_orders';

		// COMPARE
	    if(isset($_COOKIE['sb_compare'][$pm_id.'_'.$res[0][6]]))
		{
			$values[] = isset($ptf_fields_temps['p_compare_del']) && $ptf_fields_temps['p_compare_del'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array('/cms/admin/compare.php?pl_plugin_compare['.$pm_id.'_'.$res[0][6].']=0', '/cms/admin/compare.php', $pm_id.'_'.$res[0][6]), $ptf_fields_temps['p_compare_del']) : '';
		}
		else
		{
			$values[] = isset($ptf_fields_temps['p_compare_add']) && $ptf_fields_temps['p_compare_add'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array('/cms/admin/compare.php?pl_plugin_compare['.$pm_id.'_'.$res[0][6].']=1', '/cms/admin/compare.php', $pm_id.'_'.$res[0][6]), $ptf_fields_temps['p_compare_add']) : '';
		}

		//  DEL_COMPARE
		$values[] =  '/cms/admin/compare.php?plugin_id='.$pm_id.'&del_compare=1';

		//	RESERVING
		if (isset($ptf_fields_temps['p_reserved_add']))
		{
			if(isset($ptf_fields_temps['p_reserved_add']) && sb_strpos($ptf_fields_temps['p_reserved_add'], '{OPTIONS_') !== false)
			{
				$res[0][19] = 0;
			}
			elseif(sb_strpos($ptf_element,  '{RESERVING}') !== false)
			{
				$reserved = sql_query('SELECT b.b_reserved FROM sb_basket b WHERE b.b_id_mod = '.intval($pm_id).' AND b.b_id_el = ?d AND b.b_prop = "" AND b.b_reserved = 1 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where, $res[0][6], $basket_arg);
				if($reserved)
				{
					$res[0][19] = 1;
				}
			}

			if(isset($res[0][19]) && $res[0][19] == 1)
			{
				$values[] = isset($ptf_fields_temps['p_reserved_del']) && $ptf_fields_temps['p_reserved_del'] != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($basket_action.'?pl_plugin_reserving['.$pm_id.'_'.$res[0][6].']=0', $basket_action, $pm_id.'_'.$res[0][6]), $ptf_fields_temps['p_reserved_del']) : '';
			}
			else
			{
				// Макет резерва
				$p_prod_add = isset($ptf_fields_temps['p_reserved_add']) ? trim($ptf_fields_temps['p_reserved_add']) : '';

				if ($p_prod_add != '')
				{
					if(sb_strpos($p_prod_add, '{ID}') !== false)
					{
						// Идентификаторы пользовательских полей
						$p_prod_add = str_replace('{ID}', $res[0][6], $p_prod_add);
					}

					if(sb_strpos($p_prod_add, '{OPTIONS_') !== false)
					{
						$matches = array();
						if(preg_match_all('/{OPTIONS_([0-9]+)}/',$p_prod_add, $matches))
						{
							foreach($elems_fields as $value1)
							{
								if(isset($value1['id']) && in_array($value1['id'], $matches[1]))
								{
									// Определяю макет поля
									$reserve_field_tpl = $ptf_fields_temps['p_reserved_field_'.$value1['id']];
									if (trim($reserve_field_tpl) == '')
										continue;

									// Если поле нужно выводить
									$f_matches = array();

									// Достаю идентификаторы элементов
									$res_fields = sbQueryCache::query('SELECT user_f_'.$value1['id'].' FROM  sb_plugins_'.$pm_id.' WHERE p_id = ?', (SB_DEMO_SITE && isset($p_demo_id) && $p_demo_id > 0 ? $p_demo_id : $res[0][6]));
									$elems = '0';

									if($res_fields && $res_fields[0][0] != '')
										$elems .= ','.$res_fields[0][0];

									// Определяю справочник или модуль
									if(preg_match('/{PROP([0-9])}/',$reserve_field_tpl) || sb_strpos($reserve_field_tpl, '{SPR_TITLE}') !== false)
									{
										// Достаю элементы справочника
										$res_sprav = sbQueryCache::query('SELECT s_id, s_title, s_prop1, s_prop2, s_prop3 FROM sb_sprav WHERE s_id IN ('.$elems.') AND s_active=1 ORDER BY s_sort, s_title');
								        if($res_sprav)
								        {
								        	$p_prod_add_options = '';
								            // Если есть справочники
								        	foreach($res_sprav as $sprav_value)
								            {
								            	$p_prod_add_options .= str_replace(array('{SELECTED}', '{ID}', '{SPR_TITLE}', '{PROP1}', '{PROP2}', '{PROP3}'), array_merge(array(''), $sprav_value), $reserve_field_tpl);
								            }
								            $p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
								        }
									}
									elseif(preg_match_all('/OPT_([^}]+)/', $reserve_field_tpl, $f_matches))
									{
										// Узнаю нужные поля элемента
								        $elems_tags = array();
								        $sql_fields = '';
								        foreach($f_matches[1] as $el_fl)
								        {
								        	$field_name = sb_strtolower(sb_str_replace('STD_', '', $el_fl));
								            $sql_fields .= ', '.$field_name;
								            $elems_tags[] = '{OPT_'.$el_fl.'}';
								        }

										$res_elems = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id IN ('.$elems.') ORDER BY p_sort, p_title');
										if($res_elems)
							            {
							            	$p_prod_add_options = '';

							            	// Если есть элементы
							            	foreach($res_elems as $elems_value)
							            	{
							            		if (SB_DEMO_SITE)
							            		{
								            		$elems_demo_id = $elems_value[0];

								            		if ($elems_demo_id > 0)
								            		{
								            			$res_demo = sbQueryCache::query('SELECT p_demo_id, p_id '.$sql_fields.' FROM sb_plugins_'.str_replace('pl_plugin_', '', $value1['settings']['ident']).' WHERE p_id=?d', $elems_demo_id);
								            			if ($res_demo)
								            			{
								            				$res_demo[0][0] = $elems_value[0];
								            				$res_demo[0][1] = $elems_value[1];
								            				$elems_value = $res_demo[0];
								            			}
								            		}
							            		}

							            		$p_prod_add_options .= str_replace(array_merge(array('{DEMO_ID}', '{ID}'), $elems_tags), $elems_value, $cart_field_tpl);
							            	}

							            	$p_prod_add = str_replace('{OPTIONS_'.$value1['id'].'}', $p_prod_add_options, $p_prod_add);
							            }
									}
								}
							}
						}
					}
				}

				/***/
				$values[] = $p_prod_add != '' ? str_replace(array('{LINK}', '{ACTION}', '{ELEMENT_ID}'), array($basket_action.'?pl_plugin_reserving['.$pm_id.'_'.$res[0][6].']=1', $basket_action, $pm_id.'_'.$res[0][6]), $p_prod_add) : '';
			}
		}
		else
		{
			$values[] = '';
		}

	    $votes_sum = ($res[0][15] != '' && !is_null($res[0][15]) ? $res[0][15] : 0); // VOTES_SUM
	    $votes_count = ($res[0][16] != '' && !is_null($res[0][16]) ? $res[0][16] : 0); // VOTES_COUNT
	    $votes_rating = ($res[0][17] != '' && !is_null($res[0][17]) ? sprintf('%.2f', $res[0][17]) : 0); // RATING

	    if ($add_rating)
	    {
	        // VOTES_FORM
	        require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
	        $res_vote = fVoting_Form_Submit($ptf_votes_id, 'pl_plugin_'.$pm_id, $res[0][6], $votes_sum, $votes_count, $votes_rating);
	    }

	    $values[] = $votes_sum; // VOTES_SUM
	    $values[] = $votes_count; // VOTES_COUNT
	    $values[] = $votes_rating; // RATING

	    if ($add_rating && $view_rating_form)
	    {
	        $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ptf_votes_id, 'pl_plugin_'.$pm_id, $res[0][6]));  // VOTES_FORM
	    }
	    else
	    {
	        $values[] = ''; // VOTES_FORM
	    }

	    $c_count = (isset($comments_count[$res[0][6]]) ? $comments_count[$res[0][6]] : 0); // COUNT_COMMENTS

	    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');

	    if ($add_comments)
	    {
			$mod_emails = array();

			$mod_params_emails = isset($params['moderate_email']) ? explode(' ', trim(str_replace(',', ' ', $params['moderate_email']))) : array();
			$mod_categs_emails = array();
			$mod_users_emails = array();

			if(isset($res[0][3]['categs_moderate_email']) && $res[0][3]['categs_moderate_email'] != '')
			{
				$mod_categs_emails = array_merge($mod_categs_emails, explode(' ', trim(str_replace(',', ' ', $res[0][3]['categs_moderate_email']))));
			}

			$moderates_list = array();
			if(isset($res[0][3]['moderates_list']) && trim($res[0][3]['moderates_list']) != '')
			{
				$moderates_list = explode('^', $res[0][3]['moderates_list']);
			}

			$u_ids = $cat_mod_ids = array();
			foreach($moderates_list as $val)
			{
				if($val[0] == 'g')
				{
					$cat_mod_ids[] = intval(substr($val, 1));
				}
				elseif($val[0] == 'u')
				{
					$u_ids[] = intval(substr($val, 1));
				}
			}

			$res1 = $res2 = array();
			if(count($u_ids) > 0)
			{
				$res1 = sql_param_query('SELECT u_email FROM sb_users WHERE u_id IN (?a)', $u_ids);
				if(!$res1)
					$res1 = array();
			}

			if(count($cat_mod_ids) > 0 )
			{
				$res2 = sql_param_query('SELECT u.u_email FROM sb_users u, sb_catlinks l
						WHERE l.link_cat_id IN (?a) AND l.link_el_id = u.u_id', $cat_mod_ids);
				if(!$res2)
					$res2 = array();
			}

			$res_emails = array_merge($res1, $res2);
			if($res_emails)
			{
				foreach($res_emails as $val)
				{
					$mod_users_emails[] = trim($val[0]);
				}
			}

			foreach ($mod_params_emails as $email)
			{
				$email = trim($email);
				if ($email != '' && !in_array($email, $mod_emails))
				{
					$mod_emails[] = $email;
				}
			}

	        foreach ($mod_categs_emails as $email)
			{
				$email = trim($email);
				if ($email != '' && !in_array($email, $mod_emails))
				{
					$mod_emails[] = $email;
				}
			}

	        foreach ($mod_users_emails as $email)
			{
				$email = trim($email);
				if ($email != '' && !in_array($email, $mod_emails))
				{
					$mod_emails[] = $email;
				}
			}

			$str_emails = implode(' ', $mod_emails);

	        if (fComments_Add_Comment($ptf_comments_id, 'pl_plugin_'.$pm_id, $res[0][6], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
	            $c_count++;
	    }

	    $values[] = $c_count;

	    if ($view_comments_form)
	    {
	        $values[] = fComments_Get_Form($ptf_comments_id, 'pl_plugin_'.$pm_id, $res[0][6], $add_comments);	//	FORM_COMMENTS
	    }
	    else
	    {
	        $values[] = ''; // FORM_COMMENTS
	    }

	    if ($view_comments_list)
	    {
			$values[] = fComments_Get_List($ptf_comments_id, 'pl_plugin_'.$pm_id, $res[0][6], $add_comments); // LIST_COMMENTS
	    }
	    else
	    {
	        $values[] = ''; // LIST_COMMENTS
	    }

	    if(isset($res[0][20]) && $res[0][20] > 0 && isset($ptf_fields_temps['f_registred_users']) && $ptf_fields_temps['f_registred_users'] != '')
		{
			$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
			if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
			{
				$auth_page = '/'.$auth_page;
			}

			$action = $auth_page.'?pl'.$pm_id.'_uid='.$res[0][20].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');
			$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ptf_fields_temps['f_registred_users']);	//	ELEM_USER_LINK
	    }
		else
	    {
			$values[] = ''; //	ELEM_USER_LINK
	    }

	    if(sb_strpos($ptf_element, '{TAGS}') !== false)
	    {
		    // Вывод тематических тегов
			$tags_error = false;
		    // вытаскиваем макет дизайна тэгов
		    $res_tags = sql_param_query('SELECT ct_pagelist_id, ct_perpage
		                FROM sb_clouds_temps WHERE ct_id=?d', $ptf_tags_list_id);

		    if (!$res_tags)
		    	$tags_error = true;

		    list($ct_pagelist_id, $ct_perpage) = $res_tags[0];

		    // Вытаскиваем макет дизайна постраничного вывода
		    $res_tags = sbQueryCache::getTemplate('sb_pager_temps', $ct_pagelist_id);

		    if ($res_tags)
		    {
		    	list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list_tags, $pt_delim) = $res_tags[0];
		    }
		    else
		    {
		    	$pt_page_list_tags = '';
		    	$pt_perstage = 1;
		    }

			@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
			$pager = new sbDBPager('t_'.$res[0][6], $pt_perstage, $ct_perpage);

			// Вытаскиваю теги
			$tags_total = true;
			$res_tags = $pager->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_clouds_links cl
	                            WHERE cl.cl_tag_id IN
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_plugin_'.$pm_id.'" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_plugin_'.$pm_id.'"
	                                AND ct.ct_id=cl.cl_tag_id
	                            GROUP BY cl.cl_tag_id', (SB_DEMO_SITE && isset($p_demo_id) && $p_demo_id > 0) ? $p_demo_id : $res[0][6]);

		    if (!$res_tags)
		    {
		    	$tags_error = true;
		    }

		    if(!$tags_error && isset($params['page']))
		    {
			    // строим список номеров страниц
			    if ($pt_page_list_tags != '')
			    {
			    	$pager->mBeginTemp = $pt_begin;
			    	$pager->mBeginTempDisabled = '';
			    	$pager->mNextTemp = $pt_next;
			    	$pager->mNextTempDisabled = '';

			    	$pager->mPrevTemp = $pt_previous;
			       	$pager->mPrevTempDisabled = '';
			       	$pager->mEndTemp = $pt_end;
			        $pager->mEndTempDisabled = '';

			        $pager->mNumberTemp = $pt_number;
			        $pager->mCurNumberTemp = $pt_sel_number;
			        $pager->mDelimTemp = $pt_delim;
			        $pager->mListTemp = $pt_page_list_tags;

			        $pt_page_list_tags = $pager->show();
			    }
		    	require_once (SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php');
				$values[] = fClouds_Show($res_tags, $ptf_tags_list_id,  $pt_page_list_tags, $tags_total, $params['page'], 'p_'.$pm_id.'_tag'); //     TAGS
		    }
			else
		    {
				$values[] = '';   //   TAGS
		    }
	    }
	    else
	    {
			$values[] = '';   //   TAGS
	    }

	    if($ptf_user_data_id > 0 && isset($res[0][20]) && $res[0][20] > 0 &&  sb_strpos($ptf_element, '{USER_DATA}') !== false)
	    {
			require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
	        $values[] = fSite_Users_Get_Data($ptf_user_data_id, $res[0][20]); //     USER_DATA
	    }
	    else
	    {
			$values[] = '';   //   USER_DATA
	    }
		// Дата последнего изменения
        if(sb_strpos($ptf_element, '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $res[0][6], 'pl_plugin_'.$pm_id);
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ptf_fields_temps['p_date'], $ptf_lang) : ''; //   CHANGE_DATE
        }
        else
       	{
        	$values[] = '';
       	}

       	if (sb_strpos($ptf_element, '{ELEM_PREV}') !== false || sb_strpos($ptf_element, '{ELEM_NEXT}') !== false)
       	{
           	$href_prev = $href_next = $title_prev = $title_next = '';

            if(isset($params['page']) && $params['page'] != '')
    		{
    			$im_page = $params['page'];
    		}
            else
            {
    			$im_page = $GLOBALS['PHP_SELF'];
            }

            $im_page = str_replace(array('http://', 'www.', SB_COOKIE_DOMAIN.'/'), '', $im_page);
            $im_page = explode('/', $im_page);

    		$im_file_path = '';
    		for($i = 0; $i < count($im_page) - 1; $i++)
    		{
    			if($im_file_path != '')
    			{
    				$im_file_path .= '/' . $im_page[$i];
    			}
    			else
    			{
    				$im_file_path .= $im_page[$i];
    			}
    		}

    		$im_page = $im_page[count($im_page) - 1];

            $domain = SB_COOKIE_DOMAIN;

            //алиас, нужно получить основной домен для запроса
            if(!array_key_exists($domain, $GLOBALS['sb_domains'])){
                foreach($GLOBALS['sb_domains'] as $key => $value){
                    if(!isset($value['pointers']) || count($value['pointers']) == 0){
                        continue;
                    }
                    foreach($value['pointers'] as $val){
                        if(SB_COOKIE_DOMAIN == $val){
                            $domain = $key;
                        }
                    }
                }
            }


    		$res_cat_ids = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_pages"
    	        AND cat_left >= (SELECT cat_left FROM sb_categs WHERE cat_ident="pl_pages" AND cat_title=?)
    	        AND cat_right <= (SELECT cat_right FROM sb_categs WHERE cat_ident="pl_pages" AND cat_title=?)',
    		        $domain, $domain);

    		$res_tmp = false;

    		if($res_cat_ids)
    		{
    		    $cats_ids = array();
    		    foreach($res_cat_ids as $key => $value)
    		    {
    		        $cats_ids[] = $value[0];
    		    }

    		    $e_tag = '';
    		    if(isset($params['component']))
    		    {
    		        $e_tag = '{'.$params['component'].'}';
    		    }

    		    $res_tmp = sbQueryCache::query('SELECT elem.e_temp_id, elem.e_params
    					FROM sb_elems elem, sb_pages page LEFT JOIN sb_catlinks l ON l.link_el_id = page.p_id
    					WHERE l.link_cat_id IN (?a) AND page.p_filename = ? AND page.p_filepath = ? AND elem.e_p_id = page.p_id
    					AND elem.e_ident = "pl_plugin_'.$pm_id.'_list" AND elem.e_tag = ? LIMIT 1', $cats_ids,  $im_page, $im_file_path, $e_tag);
    		}

    		if($res_tmp != false)
    		{
    		    list($e_temp_id, $e_params) = $res_tmp[0];

    		    $next_prev_result = array();

    		    fPlugin_Maker_Get_Next_Prev($e_temp_id, $e_params, $next_prev_result, $_SERVER['PHP_SELF'], $res[0][6], $pm_id, $pm_elems_settings);

    		    $href_prev = $next_prev_result['href_prev'];
    		    $href_next = $next_prev_result['href_next'];
    		    $title_prev = $next_prev_result['title_prev'];
    		    $title_next = $next_prev_result['title_next'];
    		}

    		if($href_prev != '' && isset($ptf_fields_temps['p_prev_link']))
        	{
        		$href_prev = str_replace(array_merge(array('{PREV_HREF}', '{PREV_TITLE}'), $dop_tags), array_merge(array($href_prev, $title_prev), $dop_values), $ptf_fields_temps['p_prev_link']);
        	}

        	if($href_next != '' && isset($ptf_fields_temps['p_next_link']))
        	{
        		$href_next = str_replace(array_merge(array('{NEXT_HREF}', '{NEXT_TITLE}'), $dop_tags), array_merge(array($href_next, $title_next), $dop_values), $ptf_fields_temps['p_next_link']);
        	}

        	$values[] = $href_prev; // PREV
        	$values[] = $href_next;  // NEXT
       	}
       	else
       	{
       	    $values[] = ''; // PREV
       	    $values[] = '';  // NEXT
       	}

        //Вывод списка заказов
        if (sb_strpos($ptf_element, '{ORDER_LIST}') !== false
        || sb_strpos($ptf_element, '{COUNT_POS}') !== false
        || sb_strpos($ptf_element, '{COUNT_GOODS}') !== false
        || sb_strpos($ptf_element, '{TOVAR_SUM}') !== false
        || sb_strpos($ptf_element, '{TOVAR_SUM_DISCOUNT}') !== false
        )
        {
            $order_list = fPlugin_Maker_Elem_List_FromXML($pm_id, $ptf_fields_temps, $params);
            if ($order_list)
            {
                foreach ($order_list as $key => $data)
                {
                    $tags[] = '{' . strtoupper($key) . '}';
                    $values[] = $data;
                }
            }
        }

		$result = str_replace($tags, $values, $ptf_element);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
		if($linked < 1)
    	{
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $result, $res[0][6]);
	    	@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
	    	fClouds_Init_Tags('pl_plugin_'.$pm_id, array($res[0][6]));
    	}
 		else
    	{
    	    if($linked > 0)
    	        unset($_GET['pl'.$pm_id.'_id']);

    		 return $result;
    	}
	}
}

if(!function_exists('fPlugin_Maker_Elem_List_FromXML'))
{
    function fPlugin_Maker_Elem_List_FromXML($pm_id, $temps, $params, $elem=null)
    {
        //Вытаскиваем заказ
        if (isset($_GET['pl' . $pm_id . '_id']))
        {
            $res = sql_param_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $_GET['pl' . $pm_id . '_id']);
        }
        elseif (isset($_GET['pl' . $pm_id . '_sid']))
        {
            $res = sql_param_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_url=?', $_GET['pl' . $pm_id . '_sid']);

            if(!$res)
            {
                $res = sql_param_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $_GET['pl' . $pm_id . '_sid']);
            }
        }
        elseif ($elem !== null && intval($elem) > 0)
        {
            $res = sql_param_query('SELECT p_order FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', intval($elem));
        }

        if(!$res)
        {
            return false;
        }

        $res1 = sql_param_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id=?d', $pm_id);
        $pm_elems_settings = unserialize($res1[0][0]);

        if(!isset($pm_elems_settings['show_goods']) || $pm_elems_settings['show_goods'] != 1)
        {
            return false;
        }

        require_once SB_CMS_PL_PATH . '/pl_plugin_maker/pl_plugin_maker.php';
        require_once(SB_CMS_LANG_PATH.'/pl_plugin_maker.lng.php');

        foreach ($temps as $key => $val)
        {
            if(sb_stripos($key, 'basket_temp_id') === false)
            {
                continue;
            }

            if($val == 0)
            {
                continue;
            }

            $pm_elems_settings[$key] = $val;
        }

        if (isset($params['cena']))
            $pm_elems_settings['cena'] = $params['cena'];

        return fPlugin_Maker_Get_Orders_List($pm_elems_settings, $res[0][0]);
    }
}

if (!function_exists('fPlugin_Maker_Elem_Header_Html'))
{
	/**
	 * Вывод названия элемента (HTML)
	 *
	 */
	function fPlugin_Maker_Elem_Header_Html($el_id, $temp_id, $params, $tag_id, $e_ident, $strip_tags=false)
	{
	    $pm_id = substr($e_ident, 10);
	    $pm_id = substr($pm_id, 0, strpos($pm_id, '_'));

	    if (!isset($_GET['pl'.$pm_id.'_sid']) && !isset($_GET['pl'.$pm_id.'_id']))
	    {
	        return;
	    }

	    if ($GLOBALS['sbCache']->check('pl_plugin_'.$pm_id, $tag_id, array($el_id, $temp_id, $params)))
	    {
	        return;
	    }

	    if (isset($_GET['pl'.$pm_id.'_id']))
	    {
	        $res = sql_param_query('SELECT c.cat_id, c.cat_closed, p.p_id, p.p_title, p.p_demo_id FROM sb_categs c, sb_catlinks l, sb_plugins_'.$pm_id.' p
	                          WHERE p.p_id=?d AND l.link_el_id=p.p_id AND c.cat_id=l.link_cat_id AND c.cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_id']);
	    }
	    else
	    {
	        $res = sql_param_query('SELECT c.cat_id, c.cat_closed, p.p_id, p.p_title, p.p_demo_id FROM sb_categs c, sb_catlinks l, sb_plugins_'.$pm_id.' p
	                          WHERE p.p_url=? AND l.link_el_id=p.p_id AND c.cat_id=l.link_cat_id AND c.cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_sid']);

	        if (!$res)
	        {
	            $res = sql_param_query('SELECT c.cat_id, c.cat_closed, p.p_id, p.p_title, p.p_demo_id FROM sb_categs c, sb_catlinks l, sb_plugins_'.$pm_id.' p
	                          WHERE p.p_id=?d AND l.link_el_id=p.p_id AND c.cat_id=l.link_cat_id AND c.cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_sid']);
	        }
	    }

	    if (!$res)
	    {
	        return;
	    }

	    list($cat_id, $cat_closed, $p_id, $p_title, $p_demo_id) = $res[0];

	    if ($cat_closed)
	    {
	        $cat_ids = sbAuth::checkRights(array($cat_id), array($cat_id), 'pl_plugin_'.$pm_id.'_read');
	        if (count($cat_ids) == 0)
	        {
	            $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	            return;
	        }
	    }

	    if (SB_DEMO_SITE && $p_demo_id > 0)
	    {
	    	$res = sql_query('SELECT p_title FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $p_demo_id);
	    	if ($res)
	    		$p_title = $res[0][0];
	    }

	    if ($strip_tags)
	        $p_title = sb_htmlspecialchars(strip_tags($p_title));

	    $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $p_title);
	    $_GET['pl'.$pm_id.'_id'] = $p_id;
	}
}

if (!function_exists('fPlugin_Maker_Elem_Header_Plain'))
{

	/**
	 * Вывод названия элемента (без форматирования)
	 *
	 */
	function fPlugin_Maker_Elem_Header_Plain($el_id, $temp_id, $params, $tag_id, $e_ident)
	{
	    fPlugin_Maker_Elem_Header_Html($el_id, $temp_id, $params, $tag_id, $e_ident, true);
	}
}

if (!function_exists('fPlugin_Maker_Elem_Categs'))
{
	/**
	 * Вывод разделов
	 *
	 */
	function fPlugin_Maker_Elem_Categs($el_id, $temp_id, $params, $tag_id)
	{
	    $pm = unserialize(stripslashes($params));
	    $pm_id = intval($pm['pm_id']);

	    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
	    $num_sub = 0;
	    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id, 'pl'.$pm_id, $num_sub);
	}
}

if (!function_exists('fPlugin_Maker_Elem_Sel_Cat'))
{
	/**
	 * Вывод выбранного раздела
	 *
	 */
	function fPlugin_Maker_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
	{
	    $pm = unserialize(stripslashes($params));
	    $pm_id = intval($pm['pm_id']);

	    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
	    fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_plugin_'.$pm_id, 'pl_plugin_'.$pm_id, 'pl'.$pm_id);
	}
}

if (!function_exists('fPlugin_Maker_Elem_Cloud'))
{
	/**
	 * Вывод облака тегов
	 *
	 */
	function fPlugin_Maker_Elem_Cloud($el_id, $temp_id, $params, $tag_id)
	{
	    $params = unserialize(stripslashes($params));
	    $pm_id = intval($params['pm_id']);

	    if ($GLOBALS['sbCache']->check('pl_plugin_'.$pm_id, $tag_id, array($el_id, $temp_id, $params)))
	        return;

	    $res = sbQueryCache::query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	    if (!$res)
	    {
	        // указанный модуль был удален
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    list($pm_elems_settings) = $res[0];

	    if ($pm_elems_settings != '')
	        $pm_elems_settings = unserialize($pm_elems_settings);
	    else
	        $pm_elems_settings = array();

	    $cat_ids = array();

	    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['pl'.$pm_id.'_scid']) || isset($_GET['pl'.$pm_id.'_cid'])))
	    {
	        // используется связь с выводом разделов и выводить следует элементы из соотв. раздела
	        if (isset($_GET['pl'.$pm_id.'_cid']))
	        {
	            $cat_ids[] = intval($_GET['pl'.$pm_id.'_cid']);
	        }
	        else
	        {
	            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_scid']);
	            if ($res)
	            {
	                $cat_ids[] = $res[0][0];
	            }
	            else
	            {
	                $cat_ids[] = intval($_GET['pl'.$pm_id.'_scid']);
	            }
	        }
	    }
	    else
	    {
	        $cat_ids = explode('^', $params['ids']);
	    }

	    // если следует выводить подразделы, то вытаскиваем их ID
	    if (isset($params['subcategs']) && $params['subcategs'] == 1)
	    {
			$res = sbQueryCache::query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
								WHERE c2.cat_left <= c.cat_left
								AND c2.cat_right >= c.cat_right
								AND c.cat_ident="pl_plugin_'.$pm_id.'"
								AND c2.cat_ident = "pl_plugin_'.$pm_id.'"
								AND c2.cat_id IN (?a)
								ORDER BY c.cat_left', $cat_ids);

			$cat_ids = array();
	        if ($res)
	        {
	            foreach ($res as $value)
	            {
	                $cat_ids[] = $value[0];
	            }
	        }
	        else
	        {
	            // указанные разделы были удалены
	            return;
	        }
	    }

	    $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
	    if ($res)
	    {
	        // проверяем права на закрытые разделы и исключаем их из вывода
	        $closed_ids = array();
	        foreach ($res as $value)
	        {
	            $closed_ids[] = $value[0];
	        }

	        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_plugin_'.$pm_id.'_read');
	    }

	    if (count($cat_ids) == 0)
	    {
	        // нет прав доступа к выбранным разделам
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    // вытаскиваем макет дизайна
	    $res = sql_param_query('SELECT ct_pagelist_id, ct_perpage, ct_size_from, ct_size_to
	                FROM sb_clouds_temps WHERE ct_id=?d', $temp_id);

	    if (!$res)
	    {
	        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CLOUDS_PLUGIN), SB_MSG_WARNING);
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    list($ct_pagelist_id, $ct_perpage, $ct_size_from, $ct_size_to) = $res[0];

	    // вытаскиваем макет дизайна постраничного вывода
	    $res = sbQueryCache::getTemplate('sb_pager_temps', $ct_pagelist_id);

	    if ($res)
	    {
	        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
	    }
	    else
	    {
	        $pt_page_list = '';
	        $pt_perstage = 1;
	    }

	    // формируем SQL-запрос для сортировки
	    $sort_sql = '';
	    if (isset($params['sort']) && $params['sort'] != '')
	    {
	        $sort_sql .=  $params['sort'];
	        if (isset($params['order']) && $params['order'] != '')
	        {
	            $sort_sql .= ' '.$params['order'];
	        }
	    }

	    @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

	    $pager = new sbDBPager($tag_id, $pt_perstage, $ct_perpage);
	    if (isset($params['filter']) && $params['filter'] == 'from_to')
	    {
	        $pager->mFrom = intval($params['filter_from']);
	        $pager->mTo = intval($params['filter_to']);
	    }

	    $tags_total = true;
	    $active_sql = '';
	    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	    {
			$now = time();
			$active_sql = 'AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
							AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
							AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.')';
	    }

	    $res_tags = $pager->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_plugins_'.$pm_id.' p, sb_clouds_links cl, sb_catlinks l, sb_categs c
	                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=p.p_id
	                            AND cl.cl_ident="pl_plugin_'.$pm_id.'" AND cl.cl_el_id=p.p_id AND ct.ct_id=cl.cl_tag_id
	                            '.$active_sql.'
	                            AND LENGTH(ct.ct_tag) >= ?d AND LENGTH(ct.ct_tag) <= ?d
	                            GROUP BY cl.cl_tag_id '
	                            .($sort_sql != '' ? 'ORDER BY '.$sort_sql : 'ORDER BY ct.ct_tag'),
	                            $cat_ids, $ct_size_from, $ct_size_to);
	    if (!$res_tags)
	    {
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    // строим список номеров страниц
	    if ($pt_page_list != '')
	    {
	        $pager->mBeginTemp = $pt_begin;
	        $pager->mBeginTempDisabled = '';
	        $pager->mNextTemp = $pt_next;
	        $pager->mNextTempDisabled = '';

	        $pager->mPrevTemp = $pt_previous;
	        $pager->mPrevTempDisabled = '';
	        $pager->mEndTemp = $pt_end;
	        $pager->mEndTempDisabled = '';

	        $pager->mNumberTemp = $pt_number;
	        $pager->mCurNumberTemp = $pt_sel_number;
	        $pager->mDelimTemp = $pt_delim;
	        $pager->mListTemp = $pt_page_list;

	        $pt_page_list = $pager->show();
	    }

	    @require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
	    $result = fClouds_Show($res_tags, $temp_id, $pt_page_list, $tags_total, $params['page'], 'p_'.$pm_id.'_tag');

	    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
	    $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $result);
	}
}

if (!function_exists('fPlugin_Maker_Elem_Form'))
{
	/**
	 * Вывод формы добавления
	 *
	 */
	function fPlugin_Maker_Elem_Form($el_id, $temp_id, $params, $tag_id)
	{
		$params = unserialize(stripslashes($params));
		$pl_form_ident = md5($params['pm_id'].$params['ids']);
		$pm_id = intval($params['pm_id']);

	    if (!isset($_REQUEST['pl_plugin_ident']) || $_REQUEST['pl_plugin_ident'] != $pl_form_ident)
	    {
	        // просто вывод формы, данные пока не пришли
	        if ($GLOBALS['sbCache']->check('pl_plugin_'.$pm_id, $tag_id, array($el_id, $temp_id, $params)))
	            return;
		}
		elseif(isset($_REQUEST['recalc']) && $_REQUEST['recalc'] != '')
		{
			// пересчет количества товаров в корзине
			require_once(SB_CMS_PL_PATH.'/pl_basket/pl_basket.inc.php');
			fBasket_Elems_Add();
		}

		$res = sql_query('SELECT pm_title, pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	    if (!$res)
	    {
			//	указанный модуль был удален
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
			return;
		}

		list($pm_title, $pm_elems_settings) = $res[0];

	    if ($pm_elems_settings != '')
			$pm_elems_settings = unserialize($pm_elems_settings);
		else
			$pm_elems_settings = array();

	    $res = sbQueryCache::query('SELECT ptf_lang, ptf_form, ptf_fields_temps, ptf_categs_temps, ptf_messages, ptf_user_data_id FROM sb_plugins_temps_form  WHERE ptf_id=?d', $temp_id);
	    if (!$res)
	    {
	        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], $pm_title), SB_MSG_WARNING);
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    list($ptf_lang, $ptf_form, $ptf_fields_temps, $ptf_categs_temps, $ptf_messages, $ptf_user_data_id) = $res[0];

	    $ptf_form = str_replace('name=\'pl_plugin_ident\' value=\''.$pm_id.'\'', 'name=\'pl_plugin_ident\' value=\''.$pl_form_ident.'\'', $ptf_form);
	    $ptf_fields_temps = ($ptf_fields_temps != '' ? unserialize($ptf_fields_temps) : array());
	    $ptf_categs_temps = ($ptf_categs_temps != '' ? unserialize($ptf_categs_temps) : array());
	    $ptf_messages = unserialize($ptf_messages);

	    $result = '';
	    $message = '';

	    if ((!isset($_REQUEST['pl_plugin_ident']) || $_REQUEST['pl_plugin_ident'] != $pl_form_ident) && trim($ptf_form) == '')
	    {
	        // вывод формы
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

		$edit_id = -1;
		$now_cat = -1;

		if (isset($params['edit']) && $params['edit'] == 1)
		{
			//Если редактирование - узнаю id элемента
			if(isset($_GET['pl'.$pm_id.'_id']))
			{
				$edit_id = intval($_GET['pl'.$pm_id.'_id']);
			}
			elseif(isset($_GET['pl'.$pm_id.'_sid']))
			{
				$res = sbQueryCache::query('SELECT p_id FROM sb_plugins_'.$pm_id.' WHERE p_url = ?', $_GET['pl'.$pm_id.'_sid']);
				if ($res)
				{
					$edit_id = $res[0][0];
				}
				else
				{
					$res = sbQueryCache::query('SELECT p_id FROM sb_plugins_'.$pm_id.' WHERE p_id = ?', $_GET['pl'.$pm_id.'_sid']);
					if ($res)
					{
						$edit_id = $res[0][0];
					}
				}
			}

			//Если редактирование - узнаю id текущего раздела

			if(isset($_GET['pl'.$pm_id.'_cid']))
			{
				$now_cat = intval($_GET['pl'.$pm_id.'_cid']);
			}
			elseif(isset($_GET['pl'.$pm_id.'_scid']))
			{
				$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url = ? AND cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_scid']);
				if ($res)
				{
					$now_cat = $res[0][0];
				}
				else
				{
					$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id = ?', $_GET['pl'.$pm_id.'_scid']);
					if ($res)
					{
						$now_cat = $res[0][0];
					}
					else
					{
						$edit_id = -1;
					}
				}
			}
		}

		//Получаю поля формы
		if($edit_id > 0)
		{
			$news_fields = sql_query('SELECT p_title, p_url, p_sort, p_user_id, p_price1, p_price2, p_price3, p_price4, p_price5, p_ext_id FROM sb_plugins_'.$pm_id.' WHERE p_id = ?d', $edit_id);

			$res = sql_query('SELECT t.ct_tag FROM sb_clouds_links l, sb_clouds_tags t WHERE l.cl_ident="pl_plugin_'.$pm_id.'" AND l.cl_el_id = ?d AND t.ct_id = l.cl_tag_id', $edit_id);
			$news_fields_tags = '';
			if($res && count($res) > 0)
			{
				foreach($res as $value)
				{
					$news_fields_tags .= ', '.$value[0];
				}
				$news_fields_tags = sb_substr($news_fields_tags, 2);
			}
		}

		if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($news_fields[0][3] != $_SESSION['sbAuth']->getUserId())))
		{
			//	Можно редактировать только свои элементы
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $ptf_messages['edit_error']);
	        return;
		}
		elseif(isset($params['registred_users_elements_add']) && $params['registred_users_elements_add'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
		{

			//Только зарегистрированный пользователь может редактировать новость
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $ptf_messages['add_error']);
			return;
		}

		if(isset($_GET['pl'.$pm_id.'_id']) || isset($_REQUEST['pl_plugin_ident']))
	    {
	        // вывод данных пользователя, добавившего элемент
			$exist_ud = false;
			if(isset($ptf_messages['user_file']) && is_array($ptf_messages['user_file']))
		    {
		    	foreach($ptf_messages['user_file'] as $val)
		    	{
		    		if(sb_strpos($val, '{USER_DATA}') != false)
		    		{
						$exist_ud = true;
						break;
		    		}
		    	}
			}

			if(isset($ptf_messages['admin_file']) && is_array($ptf_messages['admin_file']) && !$exist_ud)
			{
				foreach($ptf_messages['admin_file'] as $val)
		    	{
					if (sb_strpos($val, '{USER_DATA}') != false)
		    		{
						$exist_ud = true;
						break;
		    		}
				}
			}

			$user_data = '';
			if ((sb_strpos($ptf_messages['add_ok'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['add_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['fields_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['file_ext_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['file_size_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['image_size_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['file_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['captcha_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['rights_error'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['user_subj'], '{USER_DATA}') !== false ||
		    	sb_strpos($ptf_messages['user_text'], '{USER_DATA}') !== false ||
				sb_strpos($ptf_messages['admin_subj'], '{USER_DATA}') !== false ||
				sb_strpos($ptf_messages['admin_text'], '{USER_DATA}') !== false ||
				$exist_ud) && $ptf_user_data_id > 0 && isset($_SESSION['sbAuth']) && $_SESSION['sbAuth']->getUserId() > 0)
	    	{
				require_once(SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
				$user_data = fSite_Users_Get_Data($ptf_user_data_id, $_SESSION['sbAuth']->getUserId());	//	USER_DATA
			}
		}

		if (isset($_GET['pl'.$pm_id.'_id']))
	    {
	        $res = sql_param_query('SELECT p_ext_id FROM sb_plugins_'.$pm_id.' WHERE p_id=?d', $_GET['pl'.$pm_id.'_id']);
	        if ($res)
	        {
	            // вывод сообщения о добавлении / редактировании элемента
	        	$res = explode('**^^**', $res[0][0]);
	        	if (isset($res[1]) && $res[1] == 'sb_new')
	        	{
	        		if ($res[0] != '')
	        		{
						sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_ext_id=? WHERE p_id=?d', $res[0], $_GET['pl'.$pm_id.'_id']);
	        		}
	        		else
	        		{
	        			sql_param_query('UPDATE sb_plugins_'.$pm_id.' SET p_ext_id=NULL WHERE p_id=?d', $_GET['pl'.$pm_id.'_id']);
	        		}

		         	if (isset($params['edit']) && $params['edit'] == 1 && isset($ptf_messages['edit_ok']) && trim($ptf_messages['edit_ok']) != '')
		            {
                        // сообщение о редактировании
						$result = fPlugin_Maker_Parse($ptf_messages['edit_ok'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $_GET['pl'.$pm_id.'_id'], $ptf_lang, '', '_val', $params, $user_data);
						$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

						//чистим код от инъекций
                        $result = sb_clean_string($result);

						eval(' ?>'.$result.'<?php ');
		            }
					elseif (isset($ptf_messages['add_ok']) && trim($ptf_messages['add_ok']) != '')
		            {
		                // добавление элемента
						$result = fPlugin_Maker_Parse($ptf_messages['add_ok'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $_GET['pl'.$pm_id.'_id'], $ptf_lang, '', '_val', $params, $user_data);
						$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

						//чистим код от инъекций
                        $result = sb_clean_string($result);

						eval(' ?>'.$result.'<?php ');

						if(isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1)
						{
							// Форма заказа. Обнуляем корзину.
							require_once(SB_CMS_PL_PATH.'/pl_basket/pl_basket.inc.php');

							$_REQUEST['pl_plugin_order'] = array();
							$_REQUEST['pl_plugin_order'][0] = 'del_orders';
							fBasket_Elems_Add();
						}
		            }

		            return;
	        	}
	        }
	    }

	    $p_id = -1;

		//Определяю значение инпутов
		$p_title = '';
	    $p_url = '';
	    $p_tags = '';
	    $p_sort = '';
	    $p_price1 = '0';
	    $p_price2 = '0';
	    $p_price3 = '0';
	    $p_price4 = '0';
	    $p_price5 = '0';

		//Название элемента
		if(isset($_POST['p_title'])) // Заголовок элемента
			$p_title = $_POST['p_title'];
		elseif($edit_id > 0 && isset($news_fields[0][0]))
			$p_title = $news_fields[0][0];

		if(isset($_POST['p_url'])) // ЧПУ элемента
			$p_url = $_POST['p_url'];
		elseif($edit_id > 0 && isset($news_fields[0][1]))
			$p_url = $news_fields[0][1];

		if(isset($_POST['p_sort'])) // Индекс сортировки
			$p_sort = $_POST['p_sort'];
		elseif($edit_id > 0 && isset($news_fields[0][2]))
			$p_sort = $news_fields[0][2];

		if(isset($_POST['p_tags'])) // Тэги
			$p_tags = $_POST['p_tags'];
		elseif($edit_id > 0 && isset($news_fields_tags))
			$p_tags = $news_fields_tags;

		if(isset($_POST['p_price1'])) // Цена 1
			$p_price1 = $_POST['p_price1'];
		elseif($edit_id > 0 && isset($news_fields[0][4]))
			$p_price1 = $news_fields[0][4];

		if(isset($_POST['p_price2'])) // Цена 2
			$p_price2 = $_POST['p_price2'];
		elseif($edit_id > 0 && isset($news_fields[0][5]))
			$p_price2 = $news_fields[0][5];

		if(isset($_POST['p_price3'])) // Цена 3
			$p_price3 = $_POST['p_price3'];
		elseif($edit_id > 0 && isset($news_fields[0][6]))
			$p_price3 = $news_fields[0][6];

		if(isset($_POST['p_price4'])) // Цена 4
			$p_price4 = $_POST['p_price4'];
		elseif($edit_id > 0 && isset($news_fields[0][7]))
			$p_price4 = $news_fields[0][7];

		if(isset($_POST['p_price5'])) // Цена 5
			$p_price5 = $_POST['p_price5'];
		elseif($edit_id > 0 && isset($news_fields[0][8]))
			$p_price5 = $news_fields[0][8];

		if(isset($_POST['p_ext_id'])) // p_ext_id
			$p_ext_id = $_POST['p_ext_id'];
		elseif($edit_id > 0 && isset($news_fields[0][9]))
			$p_ext_id = $news_fields[0][9];

	    $p_active = (isset($_POST['p_active']) ? intval($_POST['p_active']) : (isset($params['premod_elem'])) ? $params['premod_elem'] : 0);

	    $p_categ = array();
	    if (isset($_POST['p_categ']))
	    {
	    	if (is_array($_POST['p_categ']))
	    	{
	    		$p_categ = $_POST['p_categ'];
	    	}
	    	else
	    	{
	        	$p_categ[] = intval($_POST['p_categ']);
	    	}
	    }
		elseif($edit_id > 0)
		{
			$p_categ = null;
		}
	    elseif (isset($params['rubrik_link']) && $params['rubrik_link'] == 1)
	    {
	    	if (isset($_GET['pl'.$pm_id.'_cid']))
		    {
		        $p_categ[] = intval($_GET['pl'.$pm_id.'_cid']);
		    }
		    elseif (isset($_GET['pl'.$pm_id.'_scid']))
		    {
		        $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_scid']);
		        if ($res)
		        {
		            $p_categ[] = $res[0][0];
		        }
		        else
		        {
			        $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_id=?d', $_GET['pl'.$pm_id.'_scid']);
					if ($res)
			        {
						$p_categ[] = $res[0][0];
			        }
		        }
		    }
	    }

		$res_basket = false;
		// проверяем, форма является формой заказа или нет
		if(strpos($ptf_form, '{ORDER_LIST}') !== false ||
			strpos($ptf_form, '{COUNT_POSITIONS}') !== false ||
			strpos($ptf_form, '{COUNT_GOODS}') !== false ||
			strpos($ptf_form, '{TOVAR_SUM}') !== false ||
			strpos($ptf_form, '{TOVAR_SUM_DISCOUNT}') !== false || isset($_POST['order_goods']) && intval($_POST['order_goods']) == 1)
	    {

			if (isset($_POST['order_goods']) && intval($_POST['order_goods']) == 1 && !isset($_REQUEST['recalc']))
	    	{
	    		// пересчет корзины
	    		require_once(SB_CMS_PL_PATH.'/pl_basket/pl_basket.inc.php');
	    		fBasket_Elems_Add();
	    	}

			$basket_arg = ' ';
			$basket_where = ' AND b_hash = ?';

			if(isset($_SESSION['sbAuth']))
			{
				$basket_arg = $_SESSION['sbAuth']->getUserId();
				$basket_where = ' AND b_id_user = ?d';
			}
			elseif(isset($_COOKIE['pl_basket_user_id']))
			{
				$basket_arg = preg_replace('/[^A-Za-z0-9]+/', '', $_COOKIE['pl_basket_user_id']);
			}

			//	достаем товары из корзины для текущего пользователя
			$res_basket = sbQueryCache::query('SELECT b_id_user, b_id_mod, b_id_el, b_count_el, b_prop, b_discount
							FROM sb_basket WHERE b_reserved = 0 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where, $basket_arg);
		}

		$tags = array();
		$values = array();

		if (isset($_REQUEST['pl_plugin_ident']) && $_REQUEST['pl_plugin_ident'] == $pl_form_ident && !isset($_REQUEST['recalc']))
		{
			//	добавление элемента
			$message_tags = array('{P_TITLE}', '{USER_DATA}');
			$message_values = array($p_title, $user_data);

	        $error = false;
	        $fields_message = '';

			if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($news_fields[0][3] != $_SESSION['sbAuth']->getUserId())))
	        {
	        	//Можно редактировать только свои новости
	        	$error = true;
	        	$fields_message = $ptf_messages['rights_error_edit'];
	        }
	        elseif(isset($params['registred_users_elements_add']) && $params['registred_users_elements_add'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
	        {
	        	//Только зарегистрированный пользователь может добавлять новость
	        	$error = true;
	        	$fields_message = $ptf_messages['add_error'];
	        }
		    if (isset($ptf_fields_temps['p_title_need']) && (!isset($_POST['p_title']) || $p_title == ''))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_TITLE_SELECT_START}', '{P_TITLE_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        // Цена 1
	    	if (isset($ptf_fields_temps['p_price1_need']) && is_null($p_price1))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_PRICE_1_SELECT_START}', '{P_PRICE_1_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        // Цена 2
	    	if (isset($ptf_fields_temps['p_price2_need']) && is_null($p_price2))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_PRICE_2_SELECT_START}', '{P_PRICE_2_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        // Цена 3
	    	if (isset($ptf_fields_temps['p_price3_need']) && is_null($p_price3))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_PRICE_3_SELECT_START}', '{P_PRICE_3_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        // Цена 4
	    	if (isset($ptf_fields_temps['p_price4_need']) && is_null($p_price4))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_PRICE_4_SELECT_START}', '{P_PRICE_4_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        // Цена 5
	    	if (isset($ptf_fields_temps['p_price5_need']) && is_null($p_price5))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_PRICE_5_SELECT_START}', '{P_PRICE_5_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        if (isset($ptf_fields_temps['p_url_need']) && (!isset($_POST['p_url']) || $p_url == ''))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_URL_SELECT_START}', '{P_URL_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        if (isset($ptf_fields_temps['p_tags_need']) && (!isset($_POST['p_tags']) || $p_tags == ''))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_TAGS_SELECT_START}', '{P_TAGS_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        if (isset($ptf_fields_temps['p_sort_need']) && (!isset($_POST['p_sort']) || $p_sort == ''))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_SORT_SELECT_START}', '{P_SORT_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	        }

	        if (isset($ptf_fields_temps['p_categ_need']) && (!isset($_POST['p_categ']) || count($p_categ) == 0))
	        {
	            $error = true;

	            $tags = array_merge($tags, array('{P_CATEG_SELECT_START}', '{P_CATEG_SELECT_END}'));
	            $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	            $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
			}

			require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	        $users_error = false;
	        $layout = new sbLayout();
	        $row = $layout->checkPluginInputFields('pl_plugin_'.$pm_id, $users_error, $ptf_fields_temps, $edit_id, 'sb_plugins_'.$pm_id, 'p_id', false, $ptf_fields_temps['p_date_format']);

			if ($users_error)
	        {
	            foreach ($row as $f_name => $f_array)
	            {
	                $f_error = $f_array['error'];
	                $f_tag = $f_array['tag'];

	                $tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
	                $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));
	                switch($f_error)
	                {
	                    case 2:
	                        $message .= isset($ptf_messages['file_error']) ? str_replace(array_merge($message_tags, array('{P_FILE}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $ptf_messages['file_error']) : '';
	                        break;

	                    case 3:
	                        $message .= isset($ptf_messages['file_ext_error']) ? str_replace(array_merge($message_tags, array('{P_FILE}', '{P_FILE_EXT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $ptf_messages['file_ext_error']) : '';
	                        break;

	                    case 4:
	                        $message .= isset($ptf_messages['file_size_error']) ? str_replace(array_merge($message_tags, array('{P_FILE}', '{P_FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $ptf_messages['file_size_error']) : '';
	                        break;

	                    case 5:
	                        $message .= isset($ptf_messages['image_size_error']) ? str_replace(array_merge($message_tags, array('{P_FILE}', '{P_IMAGE_WIDTH}', '{P_IMAGE_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $ptf_messages['image_size_error']) : '';
	                        break;

	                    default:
	                        $fields_message = isset($ptf_messages['fields_error']) ? str_replace($message_tags, $message_values, $ptf_messages['fields_error']) : '';
	                        break;
	                }
	            }
			}

			$message .= $fields_message;
			$error = $error || $users_error;

			if (sb_strpos($ptf_form, '{P_CAPTCHA}') !== false || sb_strpos($ptf_form, '{P_CAPTCHA_IMG}') !== false)
			{
	            if (!sbProgCheckTuring('p_captcha', 'p_captcha_code'))
	            {
	                $error = true;

	                $tags = array_merge($tags, array('{P_CAPTCHA_SELECT_START}', '{P_CAPTCHA_SELECT_END}'));
	                $values = array_merge($values, array($ptf_fields_temps['p_select_start'], $ptf_fields_temps['p_select_end']));

	                $message .= isset($ptf_messages['captcha_error']) ? str_replace($message_tags, $message_values, $ptf_messages['captcha_error']) : '';
	            }
	        }

	    	$cat_ids = array();
	        if (count($p_categ) == 0)
	        {
	        	if (trim($params['ids']) != '')
	        	{
	            	$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', explode('^', $params['ids']));
	        	}
	        	elseif ($edit_id > 0)
	        	{
	        		$res = array(array($now_cat));
	        	}
	        }
	        else
	        {
	            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', $p_categ);
	        }

		    if ($res)
            {
                foreach ($res as $val)
                {
                    list($cat_id) = $val;
                    $cat_ids[] = $cat_id;
                }
            }

	        if (count($cat_ids) < 1)
	        {
				$error = true;
				$message .= isset($ptf_messages['add_error']) ? str_replace($message_tags, $message_values, $ptf_messages['add_error']) : '';
			}

	        if (count($cat_ids) > 0)
	        {
		    	// проверяем права на добавление
		        $closed_cats = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
		        if($closed_cats)
		        {
		        	$closed_ids = array();
		            foreach($closed_cats as $value)
		            {
		                $closed_ids[] = $value[0];
		            }
		            $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_plugin_'.$pm_id.'_edit');

		            if(count($cat_ids) < 1)
		            {
						$error = true;
						$message .= isset($ptf_messages['rights_error']) ? str_replace($message_tags, $message_values, $ptf_messages['rights_error']) : '';
		            }
		        }
	        }
	        else
	        {
				$cat_ids = null;
	        }

			if ($error)
	        {
	            $layout->deletePluginFieldsFiles();
	        }
	        else
	        {
	            $row['p_title'] = ($p_title != '' ? $p_title : date('d.m.Y H:i:s', time()));
	            $row['p_price1'] = $p_price1;
	            $row['p_price2'] = $p_price2;
	            $row['p_price3'] = $p_price3;
	            $row['p_price4'] = $p_price4;
	            $row['p_price5'] = $p_price5;

	            $url = $p_url;
	            if (trim($url) == '')
	            {
	                $url = sb_check_chpu('', $url, ($p_title != '' ? $p_title : date('d.m.Y H:i:s', time())), 'sb_plugins_'.$pm_id, 'p_url', 'p_id');
	            }

	            $row['p_url'] = $url;
	            $row['p_pub_start'] = null;
	            $row['p_pub_end'] = null;

	            $sort = $p_sort;
	            if ($sort == '')
	            {
	                $res = sql_query('SELECT MAX(p_sort) FROM sb_plugins_'.$pm_id);
	                if ($res)
	                {
	                    list($sort) = $res[0];
	                    $sort += 10;
	                }
	                else
	                {
	                    $sort = 0;
	                }
	            }

	            $row['p_sort'] = intval($sort);
	            $row['p_active'] = $p_active;
	            $row['p_ext_id'] = (isset($p_ext_id) ? $p_ext_id : '').'**^^**sb_new';
	            $row['p_user_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : null;

				// Генерация и добавление XML заказа
	            if(isset($_REQUEST['order_goods']) && $_REQUEST['order_goods'] == 1)
	            {
	                $xml = $cat_xml = '';

					if($res_basket && is_array($res_basket))
					{
						$cats_in_xml = array();

						foreach($res_basket as $key => $value)
						{
							list($b_id_user, $b_id_mod, $b_id_el, $b_count_el, $b_prop, $b_discount) = $value;

							//	вытаскиваем пользовательские поля
							$res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$b_id_mod.'"');

							$user_eo = $user_co = array();
						    $categs_sql_fields = $elems_ids_fields = $categs_fields = $cat_attr = $attributes = $elems_fields = array();
						    $elems_fields_select_sql = '';

						    // формируем SQL-запрос для пользовательских полей
							if($res)
						    {
								if($res[0][1] != '')
						    	{
									$categs_fields = unserialize($res[0][1]);
								}
						    	else
								{
									$categs_fields = array();
						    	}

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
											$elems_fields_select_sql .= ', p.user_f_'.$value['id'];
											$elems_ids_fields[] = $value['id'];
											if (isset($_REQUEST['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el]))
											{
												$user_eo['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el] = $_REQUEST['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el];
											}
											elseif (isset($_REQUEST['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop]))
											{
												$user_eo['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop] = is_array($_REQUEST['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop]) ? implode(',', $_REQUEST['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop]) : $_REQUEST['user_eo_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop];
											}
						                }
						            }

									fPlugin_Maker_Gen_Xml_Fields($elems_fields, $attributes, $user_eo, $b_id_mod, $b_id_el, $ptf_fields_temps['p_date_format']);
						        }

						       	if ($categs_fields)
						        {
						        	foreach ($categs_fields as $value)
									{
										if (isset($value['sql']) && $value['sql'] == 1)
						                {
						                    $categs_sql_fields[] = $value['id'];
											if (isset($_REQUEST['user_co_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el]))
											{
												$user_co['user_co_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el] = $_REQUEST['user_co_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el];
											}
											elseif (isset($_REQUEST['user_co_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop]))
											{
												$user_co['user_co_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop] = implode(',', $_REQUEST['user_co_'.$b_id_mod.'_'.$value['id'].'_'.$b_id_el.'_'.$b_prop]);
											}
										}
									}
									fPlugin_Maker_Gen_Xml_Fields($categs_fields, $cat_attr, $user_co, $b_id_mod, $b_id_el, $ptf_fields_temps['p_date_format']);
						        }
						    }

							$now = time();
							$active_sql = '';
						    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
						    {
							    if (SB_DEMO_SITE)
								{
									$active_sql = ' AND (p.p_demo_id > 0 OR (p.p_active IN ('.sb_get_workflow_demo_statuses().')
						            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
						                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.'))) ';
								}
								else
								{
									$active_sql = ' AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
						            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
						                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.') ';
								}
						    }

							// достаем данные товара для того чтобы вычислить цены, общую сумму со скидкой без скидки и т.д....
							$price_res = sql_param_query('SELECT p.p_title, p.p_url, p.p_ext_id, p.p_user_id, p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5, p.p_demo_id,
												(
													SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$b_id_mod.'"
												) as pm_elems_settings, c.cat_id, c.cat_ident, c.cat_title, c.cat_left, c.cat_right,
												c.cat_level, c.cat_ext_id, c.cat_rubrik, c.cat_closed, c.cat_fields, c.cat_url
												'.$elems_fields_select_sql.'
												FROM sb_plugins_'.$b_id_mod.' p, sb_catlinks l, sb_categs c
												WHERE p.p_id = ?d
												AND l.link_el_id=p.p_id AND l.link_cat_id=c.cat_id AND c.cat_ident="pl_plugin_'.intval($b_id_mod).'"
												'.$active_sql, $b_id_el);

							// 	если для данного товара из данного модуля нет результата значит не считаем его в корзине вообще.
							if(!$price_res)
								continue;

							list($ord_p_title, $ord_p_url, $ord_p_ext_id, $ord_p_user_id, $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $p_demo_id, $ord_pm_elems_settings,
									$cat_id, $cat_ident, $cat_title, $cat_left, $cat_right, $cat_level, $cat_ext_id, $cat_rubrik, $cat_closed, $cat_fields, $cat_url) = $price_res[0];

							$num_fields = count($price_res[0]);

							if (SB_DEMO_SITE && $p_demo_id > 0)
							{
								$demo_res = sbQueryCache::query('SELECT p.p_title, p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5
												'.$elems_fields_select_sql.'
												FROM sb_plugins_'.$b_id_mod.' p
												WHERE p.p_id = ?d
												', $p_demo_id);

								if ($demo_res)
								{
									$ord_p_title = $demo_res[0][0];
									$ord_p_price1 = $demo_res[0][1];
									$ord_p_price2 = $demo_res[0][2];
									$ord_p_price3 = $demo_res[0][3];
									$ord_p_price4 = $demo_res[0][4];
									$ord_p_price5 = $demo_res[0][5];

									for ($i = 22; $i < $num_fields; $i++)
									{
										$price_res[0][$i] = $demo_res[0][$i - 16];
									}
								}
							}

							if ($ord_pm_elems_settings != '')
								$ord_pm_elems_settings = unserialize($ord_pm_elems_settings);
							else
								$ord_pm_elems_settings = array();

							// если цена расчетная расчитываем ее.
							if(!isset($ord_pm_elems_settings['price1_type']) || $ord_pm_elems_settings['price1_type'] == 0)
					    	{
								$ord_p_price1 = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $ord_pm_elems_settings['price1_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $b_discount);
				    		}
							if(!isset($ord_pm_elems_settings['price2_type']) || $ord_pm_elems_settings['price2_type'] == 0)
					    	{
								$ord_p_price2 = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $ord_pm_elems_settings['price2_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $b_discount);
				    		}
							if(!isset($ord_pm_elems_settings['price3_type']) || $ord_pm_elems_settings['price3_type'] == 0)
					    	{
								$ord_p_price3 = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $ord_pm_elems_settings['price3_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $b_discount);
				    		}
				    		if(!isset($ord_pm_elems_settings['price4_type']) || $ord_pm_elems_settings['price4_type'] == 0)
					    	{
								$ord_p_price4 = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $ord_pm_elems_settings['price4_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $b_discount);
				    		}
				    		if(!isset($ord_pm_elems_settings['price5_type']) || $ord_pm_elems_settings['price5_type'] == 0)
					    	{
								$ord_p_price5 = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $ord_pm_elems_settings['price5_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $b_discount);
							}

				    		$xml .= '<good pm_id=\''.$b_id_mod.'\' id=\''.$b_id_el.'\' cat_id=\''.$cat_id.'\'>
	<p_title><![CDATA['.$ord_p_title.']]></p_title>
	<p_count>'.$b_count_el.'</p_count>
	<p_ext_id>'.$ord_p_ext_id.'</p_ext_id>
	<p_url>'.$ord_p_url.'</p_url>
	<p_user_id>'.$ord_p_user_id.'</p_user_id>
	<p_price1>'.$ord_p_price1.'</p_price1>
	<p_price2>'.$ord_p_price2.'</p_price2>
	<p_price3>'.$ord_p_price3.'</p_price3>
	<p_price4>'.$ord_p_price4.'</p_price4>
	<p_price5>'.$ord_p_price5.'</p_price5>
	<b_discount>'.$b_discount.'</b_discount>';

							if ($num_fields > 22)
					        {
								$i = 22;
								$from_cart_fields = array();

								if($b_prop != '')
								{
									// Если в корзине есть значения для дополнительных свойств товара - беру и парсю
									$from_cart_fields_one = explode('||', $b_prop);
									foreach($from_cart_fields_one as $v)
									{
										$from_cart_fields_two = explode('::', $v);
										$from_cart_fields[$from_cart_fields_two[0]] = $from_cart_fields_two[1];
									}
								}

								foreach ($elems_ids_fields as $val)
								{
									if(isset($attributes[$val]) && $attributes[$val] != '')
									{
										if(isset($user_eo['user_eo_'.$b_id_mod.'_'.$val.'_'.$b_id_el.'_'.$b_prop]))
										{
											// Если доп. свойство пришло из реквест
											$xml .= $attributes[$val].$user_eo['user_eo_'.$b_id_mod.'_'.$val.'_'.$b_id_el.'_'.$b_prop];
										}
										elseif(isset($user_eo['user_eo_'.$b_id_mod.'_'.$val.'_'.$b_id_el]))
										{
											// Если доп. свойство пришло из реквест
											$xml .= $attributes[$val].$user_eo['user_eo_'.$b_id_mod.'_'.$val.'_'.$b_id_el];
										}
										elseif(isset($from_cart_fields[$val]))
										{
											// Если оно есть в корзине
											$xml .= $attributes[$val].$from_cart_fields[$val];
										}
										else
										{
											// Если его нет, берем значение, сохраненное в таблице элементов
											$xml .= $attributes[$val].$price_res[0][$i];
										}
										$xml .= (sb_strpos($attributes[$val], 'CDATA') !== false ? ']]>' : '').'</user_f_'.$val.'>';
									}

									$i++;
					            }
							}
							$xml .= '</good>';

//							Если этого раздела еще нет в xml то добавляем его.
							if(!in_array($cat_id, $cats_in_xml))
							{
								$cat_xml .= '<cat>
	<cat_id>'.$cat_id.'</cat_id>
	<cat_ident>'.$cat_ident.'</cat_ident>
	<cat_title><![CDATA['.$cat_title.']]></cat_title>
	<cat_left>'.$cat_left.'</cat_left>
	<cat_right>'.$cat_right.'</cat_right>
	<cat_level>'.$cat_level.'</cat_level>
	<cat_ext_id>'.$cat_ext_id.'</cat_ext_id>
	<cat_rubrik>'.$cat_rubrik.'</cat_rubrik>
	<cat_closed>'.$cat_closed.'</cat_closed>
	<cat_url>'.$cat_url.'</cat_url>';

								if($cat_fields != '')
									$cat_fields = unserialize($cat_fields);
								else
									$cat_fields = array();

								$num_cat_fields = count($categs_sql_fields);
								if($num_cat_fields > 0)
								{
									foreach ($categs_sql_fields as $val)
									{
										if(isset($cat_attr[$val]) && $cat_attr[$val] != '')
										{
											if(isset($user_co['user_co_'.$b_id_mod.'_'.$val.'_'.$b_id_el]))
											{
												$v = $user_co['user_co_'.$b_id_mod.'_'.$val.'_'.$b_id_el];
											}
											else
											{
												$v = (isset($cat_fields['user_f_'.$val]) ? $cat_fields['user_f_'.$val] : '');
											}
											$cat_xml .= $cat_attr[$val].$v.(sb_strpos($cat_attr[$val], 'CDATA') !== false ? ']]>' : '').'</user_f_'.$val.'>';
										}
									}
								}
								$cat_xml .= '</cat>';
								$cats_in_xml[] = $cat_id;
							}
						}
					}

					if ($xml != '')
					{
					    $row['p_order'] = '<?xml version="1.0"?><goods>'.$xml.$cat_xml.'</goods>';
					}
					else
					{
					    $error = true;
					    $message .= isset($ptf_messages['add_error']) ? str_replace($message_tags, $message_values, $ptf_messages['add_error']) : '';
					}
				}

				if (!$error)
				{
    				$p_id = sbProgAddElement('sb_plugins_'.$pm_id, 'p_id', $row, $cat_ids, $edit_id, $now_cat);
    				if (!$p_id)
    	            {
    					$error = true;
    					$message .= isset($ptf_messages['add_error']) ? str_replace($message_tags, $message_values, $ptf_messages['add_error']) : '';
    					sb_add_system_message(sprintf($edit_id > 0 ? KERNEL_PROG_PLUGINS_EDIT_ERROR : KERNEL_PROG_PLUGINS_ADD_ERROR, $p_title, $pm_title), SB_MSG_WARNING);
    	            }
    				else
    	            {
    					if(trim($p_tags) != '')
    	                {
    	                    include_once(SB_CMS_PL_PATH.'/pl_clouds/pl_clouds.inc.php');
    	                    fClouds_Set_Field($p_id, 'pl_plugin_'.$pm_id, $p_tags);
    	                }

    					sb_add_system_message(sprintf($edit_id > 0 ? KERNEL_PROG_PLUGINS_EDIT_OK : KERNEL_PROG_PLUGINS_ADD_OK, $p_title, $pm_title), SB_MSG_INFORMATION);
    	            }
				}
	        }

	        if (!$error)
	        {
	        	require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';

	            $mail = new sbMail();
	            $type = sbPlugins::getSetting('sb_letters_type');

	            // отправляем письма и делаем переадресацию
	            if (trim($ptf_messages['user_subj']) != '' && trim($ptf_messages['user_text']) != '' &&
	            	isset($params['user_email']) && $params['user_email'] != -1 &&
	            	isset($_POST['user_f_'.intval($params['user_email'])]) && $_POST['user_f_'.intval($params['user_email'])] != '')
	            {
	                $su_email = $_POST['user_f_'.intval($params['user_email'])];
	                if (preg_match('/^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/is'.SB_PREG_MOD, $su_email))
	                {
	                	$email_subj = fPlugin_Maker_Parse($ptf_messages['user_subj'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);

	                	//чистим код от инъекций
	                	$email_subj = sb_clean_string($email_subj);

	                	ob_start();
	                    eval(' ?>'.$email_subj.'<?php ');
	                    $email_subj = trim(ob_get_clean());

	                    $email_text = fPlugin_Maker_Parse($ptf_messages['user_text'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);

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

		                    if (isset($ptf_messages['user_file']) && is_array($ptf_messages['user_file']) && isset($ptf_messages['user_file_name']) && is_array($ptf_messages['user_file_name']))
		                    {
		                    	foreach ($ptf_messages['user_file'] as $file_key => $file_text)
		                    	{
		                    		$result = fPlugin_Maker_Parse($file_text, $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);

		                    		//чистим код от инъекций
                                    $result = sb_clean_string($result);

		                    		ob_start();
									eval(' ?>'.$result.'<?php ');
									$file_text = trim(ob_get_clean());

									if ($file_text != '')
									{
									    $file_name = trim($ptf_messages['user_file_name'][$file_key]) != '' ? trim($ptf_messages['user_file_name'][$file_key]) : uniqid();
									    $mail->addAttachment($file_text, $file_name);
									}
								}
							}
							$mail->send(array($su_email));
	                    }
	                }
	            }

				$mod_emails = array();
		       	if (isset($params['admin_email']) && $params['admin_email'] != -1 && isset($_POST['user_f_'.intval($params['admin_email'])]))
	            {
	            	$res = sbQueryCache::query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
				    if ($res)
				    {
				    	list($pd_fields) = $res[0];

				    	if ($pd_fields != '')
				    		$pd_fields = unserialize($pd_fields);

				    	if ($pd_fields)
				    	{
					    	foreach ($pd_fields as $value)
							{
								if ($value['id'] == intval($params['admin_email']))
							    {
							    	$res_sprav = false;

							    	switch($value['type'])
							    	{
							    		case 'select_sprav':
							    		case 'radio_sprav':
							    			if (intval($_POST['user_f_'.intval($params['admin_email'])]) > 0)
							    			{
								    			$res_sprav = sbQueryCache::query('SELECT s_title FROM sb_sprav WHERE s_id=?d AND s_active=1', $_POST['user_f_'.intval($params['admin_email'])]);
							    			}
							    			break;

							    		case 'multiselect_sprav':
							    		case 'checkbox_sprav':
							    			if (is_array($_POST['user_f_'.intval($params['admin_email'])]) && count($_POST['user_f_'.intval($params['admin_email'])]) > 0)
							    			{
							    				$res_sprav = sbQueryCache::query('SELECT s_title FROM sb_sprav WHERE s_id IN (?a) AND s_active=1', $_POST['user_f_'.intval($params['admin_email'])]);
							    			}
							    			break;

							    		case 'link_sprav':
							    			if (isset($_POST['user_f_'.intval($params['admin_email']).'_link']) &&
							    				intval($_POST['user_f_'.intval($params['admin_email']).'_link']) > 0)
	                        				{
	                        					$res_sprav = sbQueryCache::query('SELECT s_title FROM sb_sprav WHERE s_id=?d AND s_active=1', $_POST['user_f_'.intval($params['admin_email']).'_link']);
					                        }
							    			break;
							    	}

							    	if ($res_sprav)
					    			{
					    				foreach ($res_sprav as $value)
					    				{
											$value[0] = trim($value[0]);
											if ($value[0] != '' && !in_array($value[0], $mod_emails))
											{
												$mod_emails[] = $value[0];
											}
					    				}
					    			}
							    }
							}
				    	}
				    }
	            }

				$mod_params_emails = explode(' ', trim(str_replace(',', ' ', $params['mod_emails'])));
				$mod_categs_emails = array();
				$mod_users_emails = array();

				$res = sbQueryCache::query('SELECT cat_fields FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
				if($res)
				{
					$cat_mod_ids = $u_ids = array();
					foreach($res as $key => $value)
					{
						if(trim($value[0]) == '')
							continue;

						$value = unserialize($value[0]);

						if(isset($value['categs_moderate_email']) && $value['categs_moderate_email'] != '')
						{
							$mod_categs_emails = array_merge($mod_categs_emails, explode(' ', trim(str_replace(',', ' ', $value['categs_moderate_email']))));
						}

						if(isset($value['moderates_list']) && trim($value['moderates_list']) != '')
						{
							$value['moderates_list'] = explode('^', $value['moderates_list']);
						}
						else
						{
							continue;
						}

						foreach($value['moderates_list'] as $val)
						{
							if($val[0] == 'g')
							{
								$cat_mod_ids[] = intval(substr($val, 1));
							}
							elseif($val[0] == 'u')
							{
								$u_ids[] = intval(substr($val, 1));
							}
						}
					}

					$res1 = $res2 = array();
					if(count($u_ids) > 0)
					{
						$res1 = sql_param_query('SELECT u_email FROM sb_users WHERE u_id IN (?a)', $u_ids);
						if(!$res1)
							$res1 = array();
					}

					if(count($cat_mod_ids) > 0)
					{
						$res2 = sql_param_query('SELECT u.u_email FROM sb_users u, sb_catlinks l
								WHERE l.link_cat_id IN (?a) AND l.link_el_id = u.u_id', $cat_mod_ids);
						if(!$res2)
							$res2 = array();
					}

					$res_mail = array_merge($res1, $res2);
					if($res_mail)
					{
						foreach($res_mail as $val)
						{
							$mod_users_emails[] = trim($val[0]);
						}
					}
				}

				foreach ($mod_params_emails as $email)
				{
					$email = trim($email);
					if ($email != '' && !in_array($email, $mod_emails))
					{
						$mod_emails[] = $email;
					}
				}

	        	foreach ($mod_categs_emails as $email)
				{
					$email = trim($email);
					if ($email != '' && !in_array($email, $mod_emails))
					{
						$mod_emails[] = $email;
					}
				}

	        	foreach ($mod_users_emails as $email)
				{
					$email = trim($email);
					if ($email != '' && !in_array($email, $mod_emails))
					{
						$mod_emails[] = $email;
					}
				}

	            if (count($mod_emails) > 0 &&
					(($edit_id <= 0 && trim($ptf_messages['admin_subj']) != '' && trim($ptf_messages['admin_text']) != '') ||
					($edit_id > 0 && isset($ptf_messages['admin_subj_edit']) && trim($ptf_messages['admin_subj_edit']) != '' && isset($ptf_messages['admin_text_edit']) && trim($ptf_messages['admin_text_edit']) != '')))
				{
	                if($edit_id > 0)
	                	$email_subj = fPlugin_Maker_Parse($ptf_messages['admin_subj_edit'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);
					else
						$email_subj = fPlugin_Maker_Parse($ptf_messages['admin_subj'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);

					//чистим код от инъекций
					$email_subj = sb_clean_string($email_subj);

					ob_start();
	                eval(' ?>'.$email_subj.'<?php ');
	                $email_subj = trim(ob_get_clean());

	                if($edit_id > 0)
	                	$email_text = fPlugin_Maker_Parse($ptf_messages['admin_text_edit'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);
					else
						$email_text = fPlugin_Maker_Parse($ptf_messages['admin_text'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);

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

		                $mail->clearAttachments();

		            	if (isset($ptf_messages['admin_file']) && is_array($ptf_messages['admin_file']) && isset($ptf_messages['admin_file_name']) && is_array($ptf_messages['admin_file_name']))
	                    {
	                    	foreach ($ptf_messages['admin_file'] as $file_key => $file_text)
	                    	{
	                    		$result = fPlugin_Maker_Parse($file_text, $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);

	                    		//чистим код от инъекций
                                $result = sb_clean_string($result);

	                    		ob_start();
	                    		eval(' ?>'.$result.'<?php ');
	                    		$file_text = trim(ob_get_clean());

	                    		if ($file_text != '')
	                    		{
	                    		    $file_name = trim($ptf_messages['admin_file_name'][$file_key]) != '' ? trim($ptf_messages['admin_file_name'][$file_key]) : uniqid();
	                    		    $mail->addAttachment($file_text, $file_name);
	                    		}
	                    	}
	                    }
						$mail->send($mod_emails, false);
	                }
	            }

	            $basket_del = true;
	            if (isset($params['page']) && trim($params['page']) != '')
	            {
	                header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'pl'.$pm_id.'_id='.$p_id));
	            }
	            elseif (!isset($_GET['noredir']))
	            {
	                $basket_del = false;
	                header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].'?pl'.$pm_id.'_id='.$p_id.(isset($_GET['pl'.$pm_id.'_cid']) ? '&pl'.$pm_id.'_cid='.$_GET['pl'.$pm_id.'_cid'] : '')));
	            }
	            else
	            {
	            	if ($edit_id > 0 && isset($ptf_messages['edit_ok']) && trim($ptf_messages['edit_ok']) != '')
		            {
		            	$result = fPlugin_Maker_Parse($ptf_messages['edit_ok'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);
		                $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		                //чистим код от инъекций
                        $result = sb_clean_string($result);

		                eval(' ?>'.$result.'<?php ');
		            }
	            	elseif (isset($ptf_messages['add_ok']) && trim($ptf_messages['add_ok']) != '')
		            {
		            	$result = fPlugin_Maker_Parse($ptf_messages['add_ok'], $pm_elems_settings, $ptf_fields_temps, $ptf_categs_temps, $pm_id, $p_id, $ptf_lang, '', '_val', $params, $user_data);
		                $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		                //чистим код от инъекций
                        $result = sb_clean_string($result);

		                eval(' ?>'.$result.'<?php ');
		            }
	            }

	            if($basket_del && isset($_POST['order_goods']))
	            {
	                // Заказ добавлен. Обнуляем корзину.
	                require_once(SB_CMS_PL_PATH.'/pl_basket/pl_basket.inc.php');

	                $_REQUEST['pl_plugin_order'] = array();
	                $_REQUEST['pl_plugin_order'][0] = 'del_orders';
	                fBasket_Elems_Add();
	            }

	            exit (0);
	        }
	        else
	        {
				$layout->deletePluginFieldsFiles();
			}
		}

		$tags = array_merge($tags, array('{MESSAGE}',
	                '{P_ACTION}',
	                '{P_TITLE}',
		    		'{P_PRICE_1}',
	    			'{P_PRICE_2}',
	    			'{P_PRICE_3}',
	    			'{P_PRICE_4}',
	    			'{P_PRICE_5}',
	                '{P_URL}',
	                '{P_TAGS}',
	                '{P_SORT}',
	                '{P_ACTIVE}',
	                '{P_CATEG}',
	                '{P_CAPTCHA}',
	                '{P_CAPTCHA_IMG}',
				    '{ORDER_LIST}',
					'{COUNT_POSITIONS}',
					'{COUNT_GOODS}',
					'{TOVAR_SUM}',
					'{TOVAR_SUM_DISCOUNT}',
					'{DELETE_BASKET}'));
		$values[] = $message;

		$edit_param = '';
		if (isset($params['edit']) && $params['edit'] == 1 && isset($_GET['pl'.$pm_id.'_id']) && $_GET['pl'.$pm_id.'_id'] != '' &&
			isset($_GET['pl'.$pm_id.'_cid']) && $_GET['pl'.$pm_id.'_cid'] != '')
		{
			$edit_param = 'pl'.$pm_id.'_cid='.$_GET['pl'.$pm_id.'_cid'].'&pl'.$pm_id.'_id='.$_GET['pl'.$pm_id.'_id'];
		}

		if($edit_param != '')
		{
			$edit_param = '?'.$edit_param;
		}

		$values[] = $GLOBALS['PHP_SELF'].(trim($_SERVER['QUERY_STRING']) != '' ? '?'.$_SERVER['QUERY_STRING'].'&'.$edit_param : $edit_param);
		$values[] = (isset($ptf_fields_temps['p_title']) && trim($ptf_fields_temps['p_title']) != '' ? str_replace('{VALUE}', $p_title, $ptf_fields_temps['p_title']) : '');

	    if ((!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] == 0) && isset($pm_elems_settings['price1_formula']))
		{
	    	// рассчитываем цену
	    	$values[] = '';
	    }
		else
		{
			$values[] = isset($ptf_fields_temps['p_price1']) && trim($ptf_fields_temps['p_price1']) != '' ? str_replace('{VALUE}', (isset($p_price1) ? $p_price1 : ''), $ptf_fields_temps['p_price1']) : '';	 // PRICE_1
		}

	    if ((!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] == 0) && isset($pm_elems_settings['price2_formula']))
	    {
	    	// рассчитываем цену
	    	$values[] = '';
	    }
	    else
	    {
	    	$values[] = isset($ptf_fields_temps['p_price2']) && trim($ptf_fields_temps['p_price2']) != '' ? str_replace('{VALUE}', (isset($p_price2) ? $p_price2 : ''), $ptf_fields_temps['p_price2']) : '';	 // PRICE_2
	    }

	    if ((!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] == 0) && isset($pm_elems_settings['price3_formula']))
	    {
	    	// рассчитываем цену
	    	$values[] = '';
	    }
	    else
	    {
			$values[] = isset($ptf_fields_temps['p_price3']) && trim($ptf_fields_temps['p_price3']) != '' ? str_replace('{VALUE}', (isset($p_price3) ? $p_price3 : ''), $ptf_fields_temps['p_price3']) : '';	 // PRICE_3
	    }

	    if ((!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] == 0) && isset($pm_elems_settings['price4_formula']))
	    {
	    	// рассчитываем цену
	    	$values[] = '';
	    }
	    else
	    {
	    	$values[] = isset($ptf_fields_temps['p_price4']) && trim($ptf_fields_temps['p_price4']) != '' ? str_replace('{VALUE}', (isset($p_price4) ? $p_price4 : ''), $ptf_fields_temps['p_price4']) : '';	 // PRICE_4
	    }

	    if ((!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] == 0) && isset($pm_elems_settings['price5_formula']))
	    {
	    	// рассчитываем цену
	    	$values[] = '';
	    }
		else
	    {
	    	$values[] = isset($ptf_fields_temps['p_price5']) && trim($ptf_fields_temps['p_price5']) != '' ? str_replace('{VALUE}', (isset($p_price5) ? $p_price5 : ''), $ptf_fields_temps['p_price5']) : '';	 // PRICE_5
	    }

	    $values[] = (isset($ptf_fields_temps['p_url']) && trim($ptf_fields_temps['p_url']) != '' ? str_replace('{VALUE}', $p_url, $ptf_fields_temps['p_url']) : '');
	    $values[] = (isset($ptf_fields_temps['p_tags']) && trim($ptf_fields_temps['p_tags']) != '' ? str_replace('{VALUE}', $p_tags, $ptf_fields_temps['p_tags']) : '');
	    $values[] = (isset($ptf_fields_temps['p_sort']) && trim($ptf_fields_temps['p_sort']) != '' ? str_replace('{VALUE}', $p_sort, $ptf_fields_temps['p_sort']) : '');

	    if (isset($ptf_fields_temps['p_active']) && trim($ptf_fields_temps['p_active']) != '')
	    {
	        $selected_str = sb_stripos($ptf_fields_temps['p_active'], 'option') !== false ? ' selected="selected"' : ' checked="checked"';
	        $values[] = str_replace('{OPT_SELECTED}', ($p_active == 1 ? $selected_str : ''), $ptf_fields_temps['p_active']);
	    }
	    else
	    {
	        $values[] = '';
	    }

	    if (sb_strpos($ptf_form, '{P_CATEG}') !== false)
		{
			$cat_ids = explode('^', $params['ids']);
			if (!is_array($p_categ) && $edit_id > 0)
			{
				$res = sql_query('SELECT c.cat_id FROM sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND l.link_el_id = ?d AND l.link_cat_id = c.cat_id', 'pl_plugin_'.$pm_id, $edit_id);
				if ($res)
				{
					$p_categ = array();
					foreach ($res as $value)
					{
						$p_categ[] = $value[0];
					}
				}
			}
			$values[] = sbProgGetCategsList($cat_ids, 'pl_plugin_'.$pm_id, $p_categ, $ptf_fields_temps['p_categ_options'], $ptf_fields_temps['p_categ'], 'pl_plugin_'.$pm_id.'_edit');
		}
		else
		{
			$values[] = '';
		}

	    // Вывод КАПЧИ
	    if ((sb_strpos($ptf_form, '{P_CAPTCHA}') !== false || sb_strpos($ptf_form, '{P_CAPTCHA_IMG}') !== false) &&
	    	isset($ptf_fields_temps['p_captcha']) && trim($ptf_fields_temps['p_captcha']) != '' &&
	        isset($ptf_fields_temps['p_captcha_img']) && trim($ptf_fields_temps['p_captcha_img']) != '')
	    {
	        $turing = sbProgGetTuring();
	        if ($turing)
	        {
	            $values[] = $ptf_fields_temps['p_captcha'];
	            $values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $ptf_fields_temps['p_captcha_img']);
	        }
	        else
	        {
	            $values[] = $ptf_fields_temps['p_captcha'];
	            $values[] = '';
	        }
	    }
	    else
	    {
			$values[] = '';
			$values[] = '';
	    }

		//	ORDER_LIST
		if($res_basket)
		{
			$sum = $sum_discount = $count_overall = $i = 0;
			$ids_elems = array(); // массив идентификаторов товаров для которых надо построить список
			$res_goods = '';      // html код списка товаров находящихся в корзине

			foreach($res_basket as $key => $value)
			{
				list($b_id_user, $b_id_mod, $b_id_el, $b_count_el, $b_prop, $b_discount) = $value;

				// достаем данные товара для того чтобы вычислить цены, общую сумму? со скидкой без скидки и т.д....
				$price_res = sql_param_query('SELECT p_price1, p_price2, p_price3, p_price4, p_price5,
									(
										SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$b_id_mod.'"
									) as pm_elems_settings, p_active, p_demo_id, p_pub_start, p_pub_end

									FROM sb_plugins_'.$b_id_mod.'
									WHERE p_id = ?d', $b_id_el);

				// 	если для данного товара из данного модуля нет результата значит не считаем его в корзине вообще.
				if(!$price_res)
					continue;

				list($p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $pm_elems_settings, $p_active, $p_demo_id, $p_pub_start, $p_pub_end) = $price_res[0];

				if (SB_DEMO_SITE)
				{
    				if ($p_demo_id <= 0 && ($p_active != 1 || $p_pub_start && $p_pub_start > time() || $p_pub_end && $p_pub_end < time()))
    				{
    				    continue;
    				}
    				elseif ($p_demo_id > 0)
    				{
    				    $demo_res = sql_query('SELECT p_active, p_price1, p_price2, p_price3, p_price4, p_price5, p_pub_start, p_pub_end
    	                          FROM sb_plugins_'.$b_id_mod.'
    	                          WHERE p_id=?d', $p_demo_id);

    				    if (!$demo_res)
    				        continue;

    			        // есть демо-элемент
    			        $p_active = $demo_res[0][0];
    			        $p_pub_start = $demo_res[0][6];
    			        $p_pub_end = $demo_res[0][7];

    			        $demo_pub_status = explode(',', sb_get_workflow_demo_statuses());

    				    if (!in_array($p_active, $demo_pub_status) || $p_pub_start && $p_pub_start > time() || $p_pub_end && $p_pub_end < time())
    				        continue;

    			        // подменяем поля выводимого элемента полями демо-элемента на демо-сайте
    			        $p_price1 = $demo_res[0][1];
    			        $p_price2 = $demo_res[0][2];
    			        $p_price3 = $demo_res[0][3];
    			        $p_price4 = $demo_res[0][4];
    			        $p_price5 = $demo_res[0][5];
    				}
				}

				$i++;

				if ($pm_elems_settings != '')
					$pm_elems_settings = unserialize($pm_elems_settings);
				else
					$pm_elems_settings = array();

				$params['cena'] = isset($params['cena']) ? intval($params['cena']) : '';

				// если цена расчетная расчитываем ее.
				if($params['cena'] != '' && isset($pm_elems_settings['price'.$params['cena'].'_type']) && $pm_elems_settings['price'.$params['cena'].'_type'] == 0)
		    	{
					${'p_price'.$params['cena']} = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $pm_elems_settings['price'.$params['cena'].'_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $b_discount);
	    		}

				$count_overall += $b_count_el;
				if(isset($params['cena']) && intval($params['cena']) > 0)
				{
					$sum += ${'p_price'.$params['cena']} * $b_count_el;
				}
				$ids_elems[$b_id_mod][] = $b_id_el;
			}

			if(strpos($ptf_form, '{ORDER_LIST}') !== false)
			{
				foreach($ids_elems as $key => $value)
				{
					if(isset($ptf_fields_temps['basket_temp_id'.$key]) && $ptf_fields_temps['basket_temp_id'.$key] > 0)
					{
						$root_cat_id = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_ident = "pl_plugin_'.intval($key).'" AND cat_level=0');

						$goods_params = array();
						$goods_params['ids'] = isset($root_cat_id[0][0]) ? $root_cat_id[0][0] : 0;
						$goods_params['filter'] = 'all';
						$goods_params['sort1'] = 'p.p_title';
						$goods_params['sort2'] = '';
						$goods_params['sort3'] = '';
						$goods_params['order1'] = 'DESC';
						$goods_params['order2'] = '';
						$goods_params['order3'] = '';
						$goods_params['cena'] = isset($params['cena']) ? $params['cena'] : '';
						$goods_params['page'] = isset($params['full_page'.$key]) ? $params['full_page'.$key] : '';
			 			$goods_params['subcategs'] = 1;
						$goods_params['rubrikator'] = 0;
						$goods_params['cloud'] = 0;
						$goods_params['calendar'] = 0;
						$goods_params['use_filter'] = 0;
						$goods_params['moderate'] = 1;
						$goods_params['moderate_email'] = '';
						$goods_params['use_id_el_filter'] = 0;
				    	$goods_params['pm_id'] = $key;
				    	$goods_params['from_add_form'] = true;
						$goods_params = addslashes(serialize($goods_params));

						$res = fPlugin_Maker_Elem_List('0', $ptf_fields_temps['basket_temp_id'.$key], $goods_params, '1', $ids_elems[$key]);

						if($res)
						{
							$res_goods .= $res;
						}
					}
				}
			}

			// если в выводе списка товаров, в корзине, что-то есть, то добавляем поле для заказа.
			if($res_goods != '')
			{
				$res_goods .= '<input type="hidden" name="order_goods" value="1">';
			}

			$values[] = $res_goods;  		// ORDER_LIST
			$values[] = $i;		  			// COUNT_POSITIONS
			$values[] = $count_overall; 	// COUNT_GOODS
			$values[] = $sum;  				// TOVAR_SUM
			$values[] = $sum_discount;  	// TOVAR_SUM_DISCOUNT
			$values[] = '/cms/admin/basket.php?pl_plugin_order[0]=del_orders';  		// DELETE_BASKET
		}
		else
	    {
			$values[] = '';
			$values[] = '0';
			$values[] = '0';
			$values[] = '0';
			$values[] = '0';
			$values[] = '/cms/admin/basket.php?pl_plugin_order[0]=del_orders';
		}

		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		sbLayout::parsePluginInputFields('pl_plugin_'.$pm_id, $ptf_fields_temps, $ptf_fields_temps['p_date_format'], $tags, $values, $edit_id, 'sb_plugins_'.$pm_id, 'p_id');

	    $tags[] = '{P_PLUGIN_IDENT}';
	    $values[] = $pl_form_ident;
	    $result = str_replace($tags, $values, $ptf_form);
	    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	    if (!isset($_REQUEST['pl_plugin_ident']) || $_REQUEST['pl_plugin_ident'] != $pl_form_ident)
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $result);
	    else
		{
			if(isset($_REQUEST['order_goods']) && $_REQUEST['order_goods'] == 1)
			{
				$GLOBALS['sbCache']->setLastModified(time());
			}

			//чистим код от инъекций
            $result = sb_clean_string($result);

			eval(' ?>'.$result.'<?php ');
		}

	}
}

if (!function_exists('fPlugin_Maker_Get_Calendar'))
{
	/**
	 * Вывод календаря
	 *
	 * @param int $year Год, за который необходимо сделать выборку дней.
	 * @param int $month Месяц, за который необходимо сделать выборку дней.
	 * @param string $params Параметры компонента.
	 * @param int $rubrikator Учитывать вывод разделов.
	 * @param int $filter Учитывать фильтр.
	 *
	 * @return array Массив дней, за которые есть элементы.
	 */
	function fPlugin_Maker_Get_Calendar($year, $month, $params, $rubrikator, $filter)
	{
		$result = array();

		$params = unserialize(stripslashes($params));
		if (!isset($params['calendar']) || $params['calendar'] != 1 || !isset($params['calendar_field']) || $params['calendar_field'] == '')
		{
			return $result;
		}

	    $pm_id = intval($params['pm_id']);

	    $res = sbQueryCache::query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	    if (!$res)
	    {
	        // указанный модуль был удален
	        return $result;
	    }

	    list($pm_elems_settings) = $res[0];

	    if ($pm_elems_settings != '')
	        $pm_elems_settings = unserialize($pm_elems_settings);
	    else
	        $pm_elems_settings = array();

	    $field = $params['calendar_field'];

		$params['rubrikator'] = $rubrikator;
		$params['use_filter'] = $filter;

	    $cat_ids = array();

	    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['pl'.$pm_id.'_scid']) || isset($_GET['pl'.$pm_id.'_cid'])))
	    {
	        // используется связь с выводом разделов и выводить следует элементы из соотв. раздела
	        if (isset($_GET['pl'.$pm_id.'_cid']))
	        {
	            $cat_ids[] = intval($_GET['pl'.$pm_id.'_cid']);
	        }
	        else
	        {
	            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_plugin_'.$pm_id.'"', $_GET['pl'.$pm_id.'_scid']);
	            if ($res)
	            {
	                $cat_ids[] = $res[0][0];
	            }
	            else
	            {
	                $cat_ids[] = intval($_GET['pl'.$pm_id.'_scid']);
	            }
	        }
	    }
	    else
	    {
	        $cat_ids = explode('^', $params['ids']);
	    }

	    // если следует выводить подразделы, то вытаскиваем их ID
	    if (isset($params['subcategs']) && $params['subcategs'] == 1)
	    {
			$res = sbQueryCache::query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
								WHERE c2.cat_left <= c.cat_left
								AND c2.cat_right >= c.cat_right
								AND c.cat_ident="pl_plugin_'.$pm_id.'"
								AND c2.cat_ident = "pl_plugin_'.$pm_id.'"
								AND c2.cat_id IN (?a)
								ORDER BY c.cat_left', $cat_ids);

	        $cat_ids = array();
	        if ($res)
	        {
	            foreach ($res as $value)
	            {
	                $cat_ids[] = $value[0];
	            }
	        }
	        else
	        {
	            // указанные разделы были удалены
	            return $result;
	        }
	    }

	    // проверяем, есть ли закрытые разделы среди тех, которые надо выводить
	    $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
	    if ($res)
	    {
	        // проверяем права на закрытые разделы и исключаем их из вывода
	        $closed_ids = array();
	        foreach ($res as $value)
	        {
	            $closed_ids[] = $value[0];
	        }

	        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_plugin_'.$pm_id.'_read');
	    }

	    if (count($cat_ids) == 0)
	    {
	        // нет прав доступа к выбранным разделам
	        return $result;
	    }

	    // вытаскиваем макет дизайна
	    $res = sql_param_query('SELECT ptl_checked, ptl_categ_top, ptl_categ_bottom FROM sb_plugins_temps_list WHERE ptl_id=?d', $params['temp_id']);

	    if (!$res)
	    {
	        $ptl_checked = array();
	        $ptl_categ_top = '';
	        $ptl_categ_bottom = '';
	    }
	    else
	    {
	    	list($ptl_checked, $ptl_categ_top, $ptl_categ_bottom) = $res[0];
	    	if (trim($ptl_checked) != '')
	    	{
	        	$ptl_checked = explode(' ', $ptl_checked);
	    	}
	    	else
	    	{
	    		$ptl_checked = array();
	    	}
	    }

	    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
	    $elems_fields_where_sql = '';

	    foreach ($ptl_checked as $value)
	    {
	        $elems_fields_where_sql .= ' AND p.user_f_'.$value.'=1';
	    }

	    // формируем SQL-запрос для фильтра
	    $elems_fields_filter_sql = '';
	    if (!isset($params['use_filter']) || $params['use_filter'] == 1)
	    {
	    	$morph_db = false;
		    if ($params['filter_morph'] == 1)
		    {
		    	require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
				$morph_db = new sbSearch();
		    }

	    	$elems_fields_filter_sql = '(';

	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('p.p_id', 'p_f_'.$pm_id.'_id', $params['filter_logic']);
	    	$elems_fields_filter_sql .= sbGetFilterTextSql('p.p_title', 'p_f_'.$pm_id.'_title', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

		    // вытаскиваем пользовательские поля новости и раздела
		    $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');

		    $elems_fields = array();

		    if ($res && $res[0][0] != '')
		    {
	            $elems_fields = unserialize($res[0][0]);

	            require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		        $elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'p', 'p_f_'.$pm_id, $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		    }
	    }

	    if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
	    	$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
		else
			$elems_fields_filter_sql = '';

		$from_date = mktime(0, 0, 0, $month, 1, $year);
	    $to_date = mktime(23, 59, 59, $month, sb_get_last_day($month, $year), $year);

	    if ($from_date <= 0 || $to_date <= 0)
	    {
	    	return $result;
	    }
	    $elems_fields_where_sql .= ' AND p.'.$field.' >= "'.$from_date.'" AND p.'.$field.' <= "'.$to_date.'"';

		// формируем SQL-запрос для сортировки
	    $elems_fields_sort_sql = '';
	    if (isset($params['sort1']) && $params['sort1'] != '')
	    {
	        $elems_fields_sort_sql .= ', '.$params['sort1'];
	        if (isset($params['order1']) && $params['order1'] != '')
	        {
	            $elems_fields_sort_sql .= ' '.$params['order1'];
	        }
	    }

	    if (isset($params['sort2']) && $params['sort2'] != '')
	    {
	        $elems_fields_sort_sql .= ', '.$params['sort2'];
	        if (isset($params['order2']) && $params['order2'] != '')
	        {
	            $elems_fields_sort_sql .= ' '.$params['order2'];
	        }
	    }

	    if (isset($params['sort3']) && $params['sort3'] != '')
	    {
	        $elems_fields_sort_sql .= ', '.$params['sort3'];
	        if (isset($params['order3']) && $params['order3'] != '')
	        {
	            $elems_fields_sort_sql .= ' '.$params['order3'];
	        }
	    }

		// используется ли группировка по разделам
	    if ($ptl_categ_top != '' || $ptl_categ_bottom != '')
	    {
	        $categs_output = true;
	    }
	    else
	    {
	        $categs_output = false;
	    }

	    $active_sql = '';
	    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	    {
			$now = time();
			if (SB_DEMO_SITE)
			{
			    $active_sql = ' AND (p.p_demo_id > 0 OR (p.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.'))) ';
			}
			else
			{
			    $active_sql = ' AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
	            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.') ';
			}
	    }

		if ($categs_output)
	    {
	        $res = sbQueryCache::query('SELECT p.'.$field.', p.p_demo_id
	                            FROM sb_plugins_'.$pm_id.' p, sb_catlinks l, sb_categs c
	                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=p.p_id
	                            '.$elems_fields_where_sql.$elems_fields_filter_sql.' '.$active_sql.'
	                            GROUP BY p.p_id
	                            ORDER BY c.cat_left'.($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', p.p_sort').
						        ($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
	    }
	    else
	    {
	        $res = sbQueryCache::query('SELECT p.'.$field.', p.p_demo_id
	                            FROM sb_plugins_'.$pm_id.' p, sb_catlinks l
	                            WHERE l.link_cat_id IN (?a) AND l.link_el_id=p.p_id
	                            '.$elems_fields_where_sql.$elems_fields_filter_sql.' '.$active_sql.'
	                            GROUP BY p.p_id
	                            '.($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY p.p_sort').
	        					($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
	    }

		if($res)
	    {
	    	foreach ($res as $value)
	    	{
	    	    if (SB_DEMO_SITE && $value[1] > 0)
	    	    {
	    	        $res_demo = sbQueryCache::query('SELECT p.'.$field.' FROM sb_plugins_'.$pm_id.' p WHERE p.p_id=?d
	    	                '.$elems_fields_where_sql.$elems_fields_filter_sql, $value[1]);

	    	        if ($res_demo)
	    	        {
	    	            $value[0] = $res_demo[0][0];
	    	        }
	    	        else
	    	        {
	    	            continue;
	    	        }
	    	    }

	    		$day = date('j', $value[0]);
	    		if (!in_array($day, $result))
	    		{
	    			$result[] = $day;
	    		}
	    	}
	    }

	    return $result;
	}
}

if (!function_exists('fPlugin_Maker_Elem_Filter'))
{
	/**
	 * Вывод формы фильтра
	 *
	 */
	function fPlugin_Maker_Elem_Filter($el_id, $temp_id, $params, $tag_id)
	{
	    $params = unserialize(stripslashes($params));
	    $pm_id = intval($params['pm_id']);

	    $res = sbQueryCache::query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	    if (!$res)
	    {
	        // указанный модуль был удален
	        $GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
	        return;
	    }

	    list($pm_title) = $res[0];

		if ($GLOBALS['sbCache']->check('pl_plugin_'.$pm_id, $tag_id, array($el_id, $temp_id, $params)))
			return;

		$res = sql_param_query('SELECT ptf_id, ptf_title, ptf_lang, ptf_form, ptf_fields_temps
								FROM sb_plugins_temps_form WHERE ptf_id=?d', $temp_id);
		if (!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], $pm_title), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
			return;
		}

		list($ptf_id, $ptf_title, $ptf_lang, $ptf_form, $ptf_fields_temps) = $res[0];
		$ptf_fields_temps = unserialize($ptf_fields_temps);

		$result = '';
		if (trim($ptf_form) == '')
		{
			$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, '');
			return;
		}

		$tags = array('{ACTION}',
					  '{TEMP_ID}',
					  '{ID}',
					  '{ID_LO}',
					  '{ID_HI}',
					  '{TITLE}',
					  '{PRICE1}',
					  '{PRICE1_LO}',
					  '{PRICE1_HI}',
					  '{PRICE2}',
					  '{PRICE2_LO}',
					  '{PRICE2_HI}',
					  '{PRICE3}',
					  '{PRICE3_LO}',
					  '{PRICE3_HI}',
					  '{PRICE4}',
					  '{PRICE4_LO}',
					  '{PRICE4_HI}',
					  '{PRICE5}',
					  '{PRICE5_LO}',
					  '{PRICE5_HI}',
					  '{SORT_SELECT}');

		if (isset($params['page']) && trim($params['page']) != '')
		{
			$action = $params['page'];
		}
		else
		{
			$action = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
		}

		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		//	вывод полей формы input
		$values[] = $action;
		$values[] = $ptf_id;
		$values[] = (isset($ptf_fields_temps['elem_id']) && $ptf_fields_temps['elem_id'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_id']) && $_REQUEST['p_f_'.$pm_id.'_id'] != '' ? $_REQUEST['p_f_'.$pm_id.'_id'] : ''), $ptf_fields_temps['elem_id']) : '');
		$values[] = (isset($ptf_fields_temps['elem_id_lo']) && $ptf_fields_temps['elem_id_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_id_lo']) && $_REQUEST['p_f_'.$pm_id.'_id_lo'] != '' ? $_REQUEST['p_f_'.$pm_id.'_id_lo'] : ''), $ptf_fields_temps['elem_id_lo']) : '');
		$values[] = (isset($ptf_fields_temps['elem_id_hi']) && $ptf_fields_temps['elem_id_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_id_hi']) && $_REQUEST['p_f_'.$pm_id.'_id_hi'] != '' ? $_REQUEST['p_f_'.$pm_id.'_id_hi'] : ''), $ptf_fields_temps['elem_id_hi']) : '');
		$values[] = (isset($ptf_fields_temps['elem_title']) && $ptf_fields_temps['elem_title'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_title']) && $_REQUEST['p_f_'.$pm_id.'_title'] != '' ? $_REQUEST['p_f_'.$pm_id.'_title'] : ''), $ptf_fields_temps['elem_title']) : '');
		$values[] = (isset($ptf_fields_temps['p_price1']) && $ptf_fields_temps['p_price1'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price1']) && $_REQUEST['p_f_'.$pm_id.'_price1'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price1'] : ''), $ptf_fields_temps['p_price1']) : '');
		$values[] = (isset($ptf_fields_temps['p_price1_lo']) && $ptf_fields_temps['p_price1_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price1_lo']) && $_REQUEST['p_f_'.$pm_id.'_price1_lo'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price1_lo'] : ''), $ptf_fields_temps['p_price1_lo']) : '');
		$values[] = (isset($ptf_fields_temps['p_price1_hi']) && $ptf_fields_temps['p_price1_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price1_hi']) && $_REQUEST['p_f_'.$pm_id.'_price1_hi'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price1_hi'] : ''), $ptf_fields_temps['p_price1_hi']) : '');
		$values[] = (isset($ptf_fields_temps['p_price2']) && $ptf_fields_temps['p_price2'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price2']) && $_REQUEST['p_f_'.$pm_id.'_price2'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price2'] : ''), $ptf_fields_temps['p_price2']) : '');
		$values[] = (isset($ptf_fields_temps['p_price2_lo']) && $ptf_fields_temps['p_price2_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price2_lo']) && $_REQUEST['p_f_'.$pm_id.'_price2_lo'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price2_lo'] : ''), $ptf_fields_temps['p_price2_lo']) : '');
		$values[] = (isset($ptf_fields_temps['p_price2_hi']) && $ptf_fields_temps['p_price2_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price2_hi']) && $_REQUEST['p_f_'.$pm_id.'_price2_hi'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price2_hi'] : ''), $ptf_fields_temps['p_price2_hi']) : '');
		$values[] = (isset($ptf_fields_temps['p_price3']) && $ptf_fields_temps['p_price3'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price3']) && $_REQUEST['p_f_'.$pm_id.'_price3'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price3'] : ''), $ptf_fields_temps['p_price3']) : '');
		$values[] = (isset($ptf_fields_temps['p_price3_lo']) && $ptf_fields_temps['p_price3_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price3_lo']) && $_REQUEST['p_f_'.$pm_id.'_price3_lo'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price3_lo'] : ''), $ptf_fields_temps['p_price3_lo']) : '');
		$values[] = (isset($ptf_fields_temps['p_price3_hi']) && $ptf_fields_temps['p_price3_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price3_hi']) && $_REQUEST['p_f_'.$pm_id.'_price3_hi'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price3_hi'] : ''), $ptf_fields_temps['p_price3_hi']) : '');
		$values[] = (isset($ptf_fields_temps['p_price4']) && $ptf_fields_temps['p_price4'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price4']) && $_REQUEST['p_f_'.$pm_id.'_price4'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price4'] : ''), $ptf_fields_temps['p_price4']) : '');
		$values[] = (isset($ptf_fields_temps['p_price4_lo']) && $ptf_fields_temps['p_price4_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price4_lo']) && $_REQUEST['p_f_'.$pm_id.'_price4_lo'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price4_lo'] : ''), $ptf_fields_temps['p_price4_lo']) : '');
		$values[] = (isset($ptf_fields_temps['p_price4_hi']) && $ptf_fields_temps['p_price4_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price4_hi']) && $_REQUEST['p_f_'.$pm_id.'_price4_hi'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price4_hi'] : ''), $ptf_fields_temps['p_price4_hi']) : '');
		$values[] = (isset($ptf_fields_temps['p_price5']) && $ptf_fields_temps['p_price5'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price5']) && $_REQUEST['p_f_'.$pm_id.'_price5'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price5'] : ''), $ptf_fields_temps['p_price5']) : '');
		$values[] = (isset($ptf_fields_temps['p_price5_lo']) && $ptf_fields_temps['p_price5_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price5_lo']) && $_REQUEST['p_f_'.$pm_id.'_price5_lo'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price5_lo'] : ''), $ptf_fields_temps['p_price5_lo']) : '');
		$values[] = (isset($ptf_fields_temps['p_price5_hi']) && $ptf_fields_temps['p_price5_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['p_f_'.$pm_id.'_price5_hi']) && $_REQUEST['p_f_'.$pm_id.'_price5_hi'] != '' ? $_REQUEST['p_f_'.$pm_id.'_price5_hi'] : ''), $ptf_fields_temps['p_price5_hi']) : '');
		if(!isset($ptf_fields_temps['elem_sort_select']))
			$ptf_fields_temps['elem_sort_select'] = '';

		$values[] = sbLayout::replacePluginFieldsTagsFilterSelect($pm_id, $ptf_fields_temps['elem_sort_select'], $ptf_form);

		sbLayout::parsePluginInputFields('pl_plugin_'.$pm_id, $ptf_fields_temps, $ptf_fields_temps['date_temps'], $tags, $values, -1, '', '', array(), array(), false, 'p_f_'.$pm_id, '', true);

		$result = str_replace($tags, $values, $ptf_form);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		$GLOBALS['sbCache']->save('pl_plugin_'.$pm_id, $result);
	}
}

if(!function_exists('fPlugin_Maker_Elem_Informer'))
{
	function fPlugin_Maker_Elem_Informer($el_id, $temp_id, $params, $tag_id)
	{
		$params = unserialize(stripslashes($params));
	    $pm_id = intval($params['pm_id']);

	    $res = sbQueryCache::query('SELECT pm_title FROM sb_plugins_maker WHERE pm_id="'.$pm_id.'"');
	    if (!$res)
	    {
	        // указанный модуль был удален
	        return;
	    }

	    list($pm_title) = $res[0];

		//	достаем макеты дизайна
		$res = sql_param_query('SELECT ptf_title, ptf_form, ptf_messages FROM sb_plugins_temps_form WHERE ptf_id =?d', $temp_id);
		if(!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], $pm_title), SB_MSG_WARNING);
			return;
		}

		list($ptf_title, $ptf_form, $ptf_messages) = $res[0];
		$ptf_messages = unserialize($ptf_messages);

		$count = 0;
		if(isset($_COOKIE['sb_compare']) && is_array($_COOKIE['sb_compare']))
		{
			foreach($_COOKIE['sb_compare'] as $key => $value)
			{
				$plugin_elem = explode('_', $key);

				if(intval($plugin_elem[0]) == $pm_id)
				{
					$count++;
				}
			}
		}

		$tags = array('{COUNT_ELEMS}', '{COMPARE_LINK}', '{DELETE_LINK}');

		$values = array();
		$values[] = $count;		//COUNT_ELEMS
		$values[] = isset($params['page']) ? $params['page'] : ''; 	//COMPARE_LINK
		$values[] = '/cms/admin/compare.php?plugin_id='.$pm_id.'&del_compare=1';	//DELETE_LINK

		if($count == 0)
		{
		    // указанный модуль был удален или нет товаров для сравнения
		    eval(' ?>'.$ptf_messages['no_elems'].'<?php ');
	        return;
		}

		$result = str_replace($tags, $values, $ptf_form);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		//чистим код от инъекций
        $result = sb_clean_string($result);

		eval(' ?>'.$result.'<?php ');

    	$GLOBALS['sbCache']->setLastModified(time());
	}
}

/**
 * Вывод информации об элементе
 *
 * @param string $temp Макет дизайна.
 * @param array $field_temps Макеты дизайна полей элемента.
 * @param array $categs_temps Макеты дизайна полей разделов.
 * @param int $pm_id Идентификатор модуля.
 * @param int $id Идентификатор элемента.
 * @param string $lang Язык макета дизайна.
 * @param string $prefix Префикс имени поля в макете дизайна полей.
 * @param string $sufix Суффикс имени поля в макете дизайна полей.
 * @param string $user_data Данные пользователя добавившего элемент
 *
 * @return string Отпарсенный макет дизайна вывода элемента.
 */
function fPlugin_Maker_Parse($temp, &$pm_elems_settings, &$fields_temps, &$categs_temps, $pm_id, $id, $lang = 'ru', $prefix = '', $sufix = '', $params = null, $user_data = '')
{
    if (trim($temp) == '')
        return '';

    $pm_id = intval($pm_id);
    $id = intval($id);

    // вытаскиваем пользовательские поля
    $res = sbQueryCache::query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');

    $users_fields = array();
    $users_fields_select_sql = '';

    $tags = array();

    // формируем SQL-запрос для пользовательских полей
    if ($res && $res[0][0] != '')
    {
        $users_fields = unserialize($res[0][0]);

        if ($users_fields)
        {
            foreach ($users_fields as $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $users_fields_select_sql .= ', p.user_f_'.intval($value['id']);

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
                }
            }
        }
    }

    $res = sbQueryCache::query('SELECT p.p_title, p.p_url, p.p_sort, p.p_active, c.cat_id, c.cat_title, c.cat_url, c.cat_fields,
    						p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5, p.p_demo_id
                            '.$users_fields_select_sql.'
                            FROM sb_plugins_'.$pm_id.' p, sb_catlinks l, sb_categs c
                            WHERE p.p_id='.$id.' AND l.link_el_id=p.p_id AND l.link_src_cat_id=0 AND c.cat_id=l.link_cat_id
                            AND c.cat_ident="pl_plugin_'.$pm_id.'"');

    if (!$res)
    {
        return '';
    }

    list ($p_title, $p_url, $p_sort, $p_active, $cat_id, $cat_title, $cat_url, $cat_fields,
    	  $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $p_demo_id) = $res[0];

    $num_fields = count($res[0]);
    if (SB_DEMO_SITE && $p_demo_id > 0)
    {
    	$res_demo = sbQueryCache::query('SELECT p.p_title, p.p_sort, p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5
                            '.$users_fields_select_sql.'
                            FROM sb_plugins_'.$pm_id.' p
                            WHERE p.p_id=?d', $p_demo_id);

    	if ($res_demo)
    	{
    		$p_title = $res_demo[0][0];
    		$p_sort = $res_demo[0][1];
    		$p_price1 = $res_demo[0][2];
    		$p_price2 = $res_demo[0][3];
    		$p_price3 = $res_demo[0][4];
    		$p_price4 = $res_demo[0][5];
    		$p_price5 = $res_demo[0][6];

    		for ($i = 14; $i < $num_fields; $i++)
    		{
    			$res[0][$i] = $res_demo[0][$i - 7];
    		}
    	}
    }

    $users_values = array();
    if ($num_fields > 14)
    {
        for ($i = 14; $i < $num_fields; $i++)
        {
            $users_values[] = $res[0][$i];
        }

        @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
        $users_values = sbLayout::parsePluginFields($users_fields, $users_values, $fields_temps, array('{P_ID}', '{P_TITLE}'), array($id, $p_title), $lang, $prefix, $sufix);
    }

    $cat_values = array();
    if ($cat_fields != '')
    {
        $cat_fields = unserialize($cat_fields);

        if ($cat_fields)
        {
            $res_cat = sbQueryCache::query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');
            if ($res_cat && $res_cat[0][0] != '')
            {
                $pd_categs = unserialize($res_cat[0][0]);
                if ($pd_categs)
                {
                    foreach ($pd_categs as $value)
                    {
                        if (isset($value['sql']) && $value['sql'] == 1 && isset($cat_fields['user_f_'.intval($value['id'])]))
                        {
	                        if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
			                {
			                	$tags[] = '{'.$value['tag'].'_LATITUDE}';
			                	$tags[] = '{'.$value['tag'].'_LONGTITUDE}';

				                if ($value['type'] == 'yandex_coords')
			                	{
			                		$tags[] = '{'.$value['tag'].'_API_KEY}';
			                	}

			                	if (isset($cat_fields['user_f_'.intval($value['id'])]))
			                	{
			               			$coords = explode('|', $cat_fields['user_f_'.intval($value['id'])]);
			               			if (isset($coords[0]) && $coords[0] != '')
			               				$cat_values[] = $coords[0];
			               			else
			               				$cat_values[] = null;

			               			if (isset($coords[1]) && $coords[1] != '')
			               				$cat_values[] = $coords[1];
			               			else
			               				$cat_values[] = null;
			                	}
			                	else
			                	{
			                		$cat_values[] = null;
			                		$cat_values[] = null;
			                	}
			                }
			                else
			                {
			                	$tags[] = '{'.$value['tag'].'}';

			                	$cat_values[] = isset($cat_fields['user_f_'.intval($value['id'])]) ? $cat_fields['user_f_'.intval($value['id'])] : null;
			                }
                        }
                    }
                }

				if (count($cat_values) > 0)
				{
					@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
					$cat_values = sbLayout::parsePluginFields($pd_categs, $cat_values, $categs_temps, array('{P_ID}', '{P_TITLE}'), array($id, $p_title), $lang, $prefix, $sufix);
				}
            }
		}
    }

    $tags = array_merge(array('{DOMAIN}',
                '{P_ID}',
                '{P_TITLE}',
    			'{P_PRICE_1}',
    			'{P_PRICE_2}',
    			'{P_PRICE_3}',
    			'{P_PRICE_4}',
    			'{P_PRICE_5}',
                '{P_URL}',
                '{P_TAGS}',
                '{P_SORT}',
                '{P_ACTIVE}',
                '{P_CAT_TITLE}',
                '{P_CAT_ID}',
                '{P_CAT_URL}',
				'{ORDER_LIST}',
				'{COUNT_POSITIONS}',
				'{COUNT_GOODS}',
				'{TOVAR_SUM}',
				'{TOVAR_SUM_DISCOUNT}',
				'{USER_DATA}'), $tags);

	$values = array();
	$values[] = str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, sbPlugins::getSetting('sb_site_name')); // DOMAIN
    $values[] = $id; // P_ID
    $values[] = $p_title; // P_TITLE

    if ((!isset($pm_elems_settings['price1_type']) || $pm_elems_settings['price1_type'] == 0) && isset($pm_elems_settings['price1_formula']))
	{
    	// рассчитываем цену
    	$p_price1 = fPlugin_Maker_Quoting($pm_id, $id, $pm_elems_settings['price1_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    }

    if ((!isset($pm_elems_settings['price2_type']) || $pm_elems_settings['price2_type'] == 0) && isset($pm_elems_settings['price2_formula']))
    {
    	// рассчитываем цену
    	$p_price2 = fPlugin_Maker_Quoting($pm_id, $id, $pm_elems_settings['price2_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    }

    if ((!isset($pm_elems_settings['price3_type']) || $pm_elems_settings['price3_type'] == 0) && isset($pm_elems_settings['price3_formula']))
    {
    	// рассчитываем цену
    	$p_price3 = fPlugin_Maker_Quoting($pm_id, $id, $pm_elems_settings['price3_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    }

    if ((!isset($pm_elems_settings['price4_type']) || $pm_elems_settings['price4_type'] == 0) && isset($pm_elems_settings['price4_formula']))
    {
    	// рассчитываем цену
    	$p_price4 = fPlugin_Maker_Quoting($pm_id, $id, $pm_elems_settings['price4_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    }

    if ((!isset($pm_elems_settings['price5_type']) || $pm_elems_settings['price5_type'] == 0) && isset($pm_elems_settings['price5_formula']))
    {
    	// рассчитываем цену
    	$p_price5 = fPlugin_Maker_Quoting($pm_id, $id, $pm_elems_settings['price5_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5);
    }

	$values[] = !is_null($p_price1) && isset($fields_temps['p_price1_val']) && trim($fields_temps['p_price1_val']) != '' ? str_replace(array('{P_ID}', '{P_TITLE}', '{VALUE}'), array($id, $p_title, $p_price1), $fields_temps['p_price1_val']) : '';	 // PRICE_1
    $values[] = !is_null($p_price2) && isset($fields_temps['p_price2_val']) && trim($fields_temps['p_price2_val']) != '' ? str_replace(array('{P_ID}', '{P_TITLE}', '{VALUE}'), array($id, $p_title, $p_price2), $fields_temps['p_price2_val']) : '';	 // PRICE_2
    $values[] = !is_null($p_price3) && isset($fields_temps['p_price3_val']) && trim($fields_temps['p_price3_val']) != '' ? str_replace(array('{P_ID}', '{P_TITLE}', '{VALUE}'), array($id, $p_title, $p_price3), $fields_temps['p_price3_val']) : '';	 // PRICE_3
    $values[] = !is_null($p_price4) && isset($fields_temps['p_price4_val']) && trim($fields_temps['p_price4_val']) != '' ? str_replace(array('{P_ID}', '{P_TITLE}', '{VALUE}'), array($id, $p_title, $p_price4), $fields_temps['p_price4_val']) : '';	 // PRICE_4
    $values[] = !is_null($p_price5) && isset($fields_temps['p_price5_val']) && trim($fields_temps['p_price5_val']) != '' ? str_replace(array('{P_ID}', '{P_TITLE}', '{VALUE}'), array($id, $p_title, $p_price5), $fields_temps['p_price5_val']) : '';	 // PRICE_5

    $values[] = $p_url; // P_URL

    $p_tags = '';
    $res_tags = sql_query('SELECT ct_tag FROM sb_clouds_tags, sb_clouds_links WHERE cl_ident="pl_plugin_'.$pm_id.'" AND cl_el_id="'.$id.'" AND ct_id=cl_tag_id');
    if ($res_tags)
    {
        $p_tags = array();
        foreach ($res_tags as $val)
        {
            $p_tags[] = $val[0];
        }

        $p_tags = implode(', ', $p_tags);
    }

    $values[] = $p_tags; // P_TAGS
    $values[] = $p_sort; // P_SORT
    $values[] = $p_active; // P_ACTIVE
    $values[] = $cat_title; // P_CAT_TITLE
    $values[] = $cat_id; // P_CAT_ID
    $values[] = $cat_url; // P_CAT_URL

	if (isset($pm_elems_settings['show_goods']) && $pm_elems_settings['show_goods'] == 1 &&
	   (strpos($temp, '{ORDER_LIST}') !== false || strpos($temp, '{COUNT_POSITIONS}') !== false ||
	   	strpos($temp, '{COUNT_GOODS}') !== false || strpos($temp, '{TOVAR_SUM}') !== false) ||
	   	strpos($temp, '{TOVAR_SUM_DISCOUNT}') !== false)
	{
		$basket_arg = $id_user_hash = ' ';
		$basket_where = ' AND b_hash = ?';

		if(isset($_SESSION['sbAuth']))
		{
			$basket_arg = $_SESSION['sbAuth']->getUserId();
			$basket_where = ' AND b_id_user = ?d';
		}
		elseif(isset($_COOKIE['pl_basket_user_id']))
		{
			$basket_arg = preg_replace('/[^A-Za-z0-9]+/', '', $_COOKIE['pl_basket_user_id']);
		}

		//	достаем товары из корзины для текущего пользователя и чтоб они не были отложенные.
		$res_basket = sql_param_query('SELECT b_id_user, b_id_mod, b_id_el, b_count_el, b_discount
								FROM sb_basket WHERE b_reserved = 0 AND (b_domain="all" OR b_domain="'.SB_COOKIE_DOMAIN.'") '.$basket_where, $basket_arg);

		$sum = $sum_discount = $count_overall = $i = 0;
		$res_goods = '';      // html код списка товаров находящихся в корзине

		//	ORDER_LIST
		if($res_basket)
		{
			$ids_elems = array(); // массив идентификаторов товаров для которых надо построить список
			foreach($res_basket as $key => $value)
			{
				list($b_id_user, $b_id_mod, $b_id_el, $b_count_el, $b_discount) = $value;

				$active_sql = '';
			    if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
			    {
					$now = time();
			    	if (SB_DEMO_SITE)
					{
						$active_sql = ' AND (p.p_demo_id > 0 OR (p.p_active IN ('.sb_get_workflow_demo_statuses().')
			            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
			                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.'))) ';
					}
					else
					{
						$active_sql = ' AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
			            	           AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
			                	       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.') ';
					}
			    }

				// достаем данные товара для того чтобы вычислить цены, общую сумму, со скидкой без скидки и т.д....
				$price_res = sbQueryCache::query('SELECT p.p_price1, p.p_price2, p.p_price3, p.p_price4, p.p_price5, p.p_demo_id,
									(
										SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$b_id_mod.'"
									) as pm_elems_settings

									FROM sb_plugins_'.$b_id_mod.' p
									WHERE p.p_id = ?d '.$active_sql, $b_id_el);

				// 	если для данного товара из данного модуля нет результата значит не считаем его в корзине вообще.
				if(!$price_res)
					continue;

				$i++;
				list($ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $p_demo_id, $ord_pm_elems_settings) = $price_res[0];

				if (SB_DEMO_SITE && $p_demo_id > 0)
				{
					$res_demo = sbQueryCache::query('SELECT p_price1, p_price2, p_price3, p_price4, p_price5
									FROM sb_plugins_'.$b_id_mod.'
									WHERE p_id = ?d', $p_demo_id);

					if ($res_demo)
					{
						$ord_p_price1 = $res_demo[0][0];
						$ord_p_price2 = $res_demo[0][1];
						$ord_p_price3 = $res_demo[0][2];
						$ord_p_price4 = $res_demo[0][3];
						$ord_p_price5 = $res_demo[0][4];
					}
				}

				if ($ord_pm_elems_settings != '')
					$ord_pm_elems_settings = unserialize($ord_pm_elems_settings);
				else
					$ord_pm_elems_settings = array();

				$params['cena'] = isset($params['cena']) ? intval($params['cena']) : '';
				// если цена расчетная расчитываем ее.
				if(isset($params['cena']) && $params['cena'] != '' && (!isset($ord_pm_elems_settings['price'.$params['cena'].'_type']) || $ord_pm_elems_settings['price'.$params['cena'].'_type'] == 0))
		    	{
					${'ord_p_price'.$params['cena']} = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $ord_pm_elems_settings['price'.$params['cena'].'_formula'], $ord_p_price1, $ord_p_price2, $ord_p_price3, $ord_p_price4, $ord_p_price5, $b_discount);
				}

				$count_overall += $b_count_el;
				if(isset($params['cena']) && intval($params['cena']) > 0)
				{
					$sum += ${'ord_p_price'.$params['cena']} * $b_count_el;
				}
				$ids_elems[$b_id_mod][] = $b_id_el;
			}
			if(strpos($temp, '{ORDER_LIST}') !== false)
			{

				foreach($ids_elems as $key => $value)
				{
					if(isset($fields_temps['mess_basket_temp_id'.$key]) && $fields_temps['mess_basket_temp_id'.$key] > 0)
					{
						$root_cat_id = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_ident = "pl_plugin_'.intval($key).'" AND cat_level=0');

						$goods_params = array();
						$goods_params['ids'] = isset($root_cat_id[0][0]) ? $root_cat_id[0][0] : 0;
						$goods_params['filter'] = 'all';
						$goods_params['sort1'] = 'p.p_title';
						$goods_params['sort2'] = '';
						$goods_params['sort3'] = '';
						$goods_params['order1'] = 'DESC';
						$goods_params['order2'] = '';
						$goods_params['order3'] = '';
						$goods_params['page'] = isset($params['full_page'.$key]) ? $params['full_page'.$key] : ''; // ????
			 			$goods_params['subcategs'] = 1;
						$goods_params['rubrikator'] = 0;
						$goods_params['cloud'] = 0;
						$goods_params['calendar'] = 0;
						$goods_params['use_filter'] = 0;
						$goods_params['moderate'] = 1;
						$goods_params['moderate_email'] = '';
						$goods_params['use_id_el_filter'] = 0;
				    	$goods_params['pm_id'] = $key;
						$goods_params['cena'] = $params['cena'];
				    	$goods_params['from_add_form'] = true;
						$goods_params = addslashes(serialize($goods_params));

						$res = fPlugin_Maker_Elem_List('0', $fields_temps['mess_basket_temp_id'.$key], $goods_params, '1', $ids_elems[$key]);

						if ($res)
						{
							$res_goods .= $res;
						}
					}
				}
			}
		}

		$values[] = $res_goods;  	// ORDER_LIST
		$values[] = $i;		  		// COUNT_POSITIONS
		$values[] = $count_overall; // COUNT_GOODS
		$values[] = $sum;  			// TOVAR_SUM
		$values[] = $sum_discount;	// TOVAR_SUM_DISCOUNT
	}
	else
	{
		$values[] = '';	// ORDER_LIST
		$values[] = '';	// COUNT_POSITIONS
		$values[] = '';	// COUNT_GOODS
		$values[] = '';	// TOVAR_SUM
		$values[] = '';	// TOVAR_SUM_DISCOUNT
	}

	$values[] = $user_data != '' ? $user_data : '';	// USER_DATA

	$values = array_merge($values, $users_values, $cat_values);
	return str_replace($tags, $values, $temp);
}

/**
 * Расчет цены по формуле
 *
 * @param $pm_id Идентификатор модуля.
 * @param $el_id Идентификатор элемента.
 * @param string $formula Формула.
 * @param float $p_price1 Цена 1.
 * @param float $p_price2 Цена 2.
 * @param float $p_price3 Цена 3.
 * @param float $p_price4 Цена 4.
 * @param float $p_price5 Цена 5.
 *
 * @return float Рассчитанная цена.
 */
function fPlugin_Maker_Quoting($pm_id, $el_id, $formula, $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $b_discount = 0)
{
	if ($pm_id == '' || $el_id == '' || trim($formula) == '')
		return null;

	// получаем курсы валют ЦБ
    static $cb_tags = array();
    static $cb_values = array();
    static $el_tags = array();
    static $el_sql = array();
    static $cat_sql = array();

    require_once SB_CMS_PL_PATH.'/pl_services_cb/prog/pl_services_cb.php';
    if (count($cb_tags) == 0 || count($cb_values) == 0)
    {
    	if (!preg_match('/\{VALUE.*?}/msu', $formula))
    	{
    		$cb_tags[] = '{CB_ZAGLUSHKA_'.strtoupper(uniqid()).'}';
    		$cb_values[] = '1';
    	}
    	else
    	{
	    	$error = false;
	    	fServices_cb_Get_K($cb_tags, $cb_values, $error);
	    	if (!$error)
	    	{
	    		foreach ($cb_values as $key => $value)
	    		{
	    			$cb_values[$key] = str_replace(',', '.', $value);
	    		}
	    	}
    	}
    }

    $price_tags = array_merge($cb_tags, array('{PRICE1}', '{PRICE2}', '{PRICE3}', '{PRICE4}', '{PRICE5}', '{DISCOUNT}'));

    $price_values = array_merge($cb_values, array(is_null($p_price1) ? 0 : $p_price1, is_null($p_price2) ? 0 : $p_price2,
    						  is_null($p_price3) ? 0 : $p_price3, is_null($p_price4) ? 0 : $p_price4,
    						  is_null($p_price5) ? 0 : $p_price5, $b_discount));

	$GLOBALS['sb_value'] = null;

    $formula = str_replace($price_tags, $price_values, $formula);

    if (preg_match('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, $formula) > 0)
    {
    	// парсинг пользовательских полей

	    if (!isset($el_tags[$pm_id]) || !isset($el_sql[$pm_id]) || !isset($cat_sql[$pm_id]))
	    {
		    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_plugin_'.$pm_id.'"');

		    $elems_fields = array();
	    	$categs_fields = array();

	    	$cat_sql[$pm_id] = array();
	    	$el_sql[$pm_id] = '';
	    	$el_tags[$pm_id] = array();

	    	// формируем SQL-запрос для пользовательских полей
		    if ($res)
		    {
		        if(trim($res[0][0]) != '')
		        {
		            $elems_fields = unserialize($res[0][0]);
		        }

		        if(trim($res[0][1]) != '')
		        {
		            $categs_fields = unserialize($res[0][1]);
		        }

		        if ($elems_fields)
		        {
		            foreach ($elems_fields as $value)
		            {
		                if (isset($value['sql']) && $value['sql'] == 1)
		                {
		                    $el_sql[$pm_id] .= ', p.user_f_'.$value['id'];

		                    if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
			                {
			                	$el_tags[$pm_id][] = '{'.$value['tag'].'_LATITUDE}';
			                	$el_tags[$pm_id][] = '{'.$value['tag'].'_LONGTITUDE}';
			                }
			                else
			                {
			                	$el_tags[$pm_id][] = '{'.$value['tag'].'}';
			                }
		                }
		            }
		        }

		        if ($categs_fields)
		        {
		            foreach ($categs_fields as $value)
		            {
		                if (isset($value['sql']) && $value['sql'] == 1)
		                {
		                	if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
			                {
			                	$el_tags[$pm_id][] = '{'.$value['tag'].'_LATITUDE}';
			                	$el_tags[$pm_id][] = '{'.$value['tag'].'_LONGTITUDE}';
			                }
			                else
			                {
			                	$el_tags[$pm_id][] = '{'.$value['tag'].'}';
			                }

		                    $cat_sql[$pm_id][] = 'user_f_'.$value['id'];
		                }
		            }
		        }
		    }

	    	$el_tags[$pm_id] = array_merge($el_tags[$pm_id], array('{CAT_TITLE}',
					'{CAT_LEVEL}',
					'{CAT_ID}',
					'{CAT_URL}',
					'{ID}',
					'{ELEM_URL}',
					'{TITLE}',
					'{SORT}'));
	    }

	    $res = sbQueryCache::query('SELECT c.cat_title, c.cat_level, c.cat_id, c.cat_fields, c.cat_url,
	                            p.p_id, p.p_url, p.p_title, p.p_sort'.$el_sql[$pm_id].'
	                            FROM sb_plugins_'.$pm_id.' p, sb_categs c, sb_catlinks l
	                            WHERE p.p_id = ?d AND l.link_el_id = p.p_id AND c.cat_id = l.link_cat_id',
	    						$el_id);

		if ($res)
		{
			$num_fields = count($res[0]);
	    	$num_cat_fields = count($cat_sql[$pm_id]);

			$values = array();
			if ($num_fields > 9)
	    	{
	        	for ($i = 9; $i < $num_fields; $i++)
	        	{
		       		$values[] = $res[0][$i];
	        	}
			}

			$cat_values = trim($res[0][3]) != '' ? unserialize($res[0][3]) : array();
	    	if ($num_cat_fields > 0)
	    	{
	        	foreach ($cat_sql[$pm_id] as $cat_field)
	        	{
	            	if (isset($cat_values[$cat_field]))
	                	$values[] = $cat_values[$cat_field];
	            	else
	                	$values[] = null;
	        	}
		    }

		    $values[] = $res[0][0]; // CAT_TITLE
		    $values[] = $res[0][1] + 1; // CAT_LEVEL
		    $values[] = $res[0][2]; // CAT_ID
		    $values[] = $res[0][4]; // CAT_URL
		    $values[] = $res[0][5]; // ID
		    $values[] = $res[0][6]; // ELEM_URL
		    $values[] = $res[0][7]; // TITLE
		    $values[] = $res[0][8]; // SORT

		    $formula = str_replace($el_tags[$pm_id], $values, $formula);
		}
    }

    $formula = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '0', $formula);
	eval($formula);

	if (!is_null($GLOBALS['sb_value']))
   	{
		if (trim($GLOBALS['sb_value'] != ''))
			$GLOBALS['sb_value'] = sprintf('%.2f', $GLOBALS['sb_value']);
		else
			$GLOBALS['sb_value'] = null;
	}

    $result = $GLOBALS['sb_value'];
    unset($GLOBALS['sb_value']);

    return $result;
}

/**
 * Функция создает массив пользовательских полей элементов/разделов.
 * Значением элемента массива является пользовательское поле в формате xml. Для дальнейшего внесения в базу.
 *
 * @param array $fields Массив данных пользовательских полей
 * @param array $array  Сгененрированный массив с полями элементов/разделов в формате xml
 */
function fPlugin_Maker_Gen_Xml_Fields($fields, &$array)
{
	foreach ($fields as $value)
	{
		if (isset($value['sql']) && $value['sql'] == 1)
		{
			$value['title'] = sb_htmlentities($value['title']);
            if(isset($value['settings']['sprav_title_fld']))
            {
                $value['settings']['sprav_title_fld'] = sb_htmlentities($value['settings']['sprav_title_fld']);
            }
            else
            {
                $value['settings']['sprav_title_fld'] = 's_title';
            }

			switch($value['type'])
			{
				case 'checkbox':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'checkbox\' checked_text=\''.sb_htmlentities($value['settings']['checked_text']).'\' not_checked_text=\''.sb_htmlentities($value['settings']['not_checked_text']).'\'>';
		    	    break;
				case 'categs':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'categs\' cat_id=\''.intval($value['settings']['cat_id']).'\' ident=\''.sb_htmlentities($value['settings']['ident']).'\'>';
		            break;
				case 'select_sprav':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'select_sprav\' sprav_ids=\''.sb_htmlentities($value['settings']['sprav_ids']).'\' subcategs=\''.intval($value['settings']['subcategs']).'\' sprav_title_fld=\''.$value['settings']['sprav_title_fld'].'\'>';
		            break;
		        case 'multiselect_sprav':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'multiselect_sprav\' sprav_ids=\''.sb_htmlentities($value['settings']['sprav_ids']).'\' subcategs=\''.intval($value['settings']['subcategs']).'\' separator=\''.sb_htmlentities($value['settings']['separator']).'\' sprav_title_fld=\''.$value['settings']['sprav_title_fld'].'\'>';
		            break;
		        case 'radio_sprav':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'radio_sprav\' count=\''.intval($value['settings']['count']).'\' separator=\''.sb_htmlentities($value['settings']['separator']).'\' sprav_ids=\''.sb_htmlentities($value['settings']['sprav_ids']).'\' subcategs=\''.intval($value['settings']['subcategs']).'\' sprav_title_fld=\''.$value['settings']['sprav_title_fld'].'\'>';
		            break;
		        case 'checkbox_sprav':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'checkbox_sprav\' count=\''.intval($value['settings']['count']).'\' separator=\''.sb_htmlentities($value['settings']['separator']).'\' sprav_ids=\''.sb_htmlentities($value['settings']['sprav_ids']).'\' subcategs=\''.intval($value['settings']['subcategs']).'\' sprav_title_fld=\''.$value['settings']['sprav_title_fld'].'\'>';
		            break;
		        case 'link_sprav':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'link_sprav\' sprav_title=\''.sb_htmlentities($value['settings']['sprav_title']).'\' sprav_id=\''.intval($value['settings']['sprav_id']).'\' subcategs=\''.sb_htmlentities($value['settings']['subcategs']).'\' sprav_title_fld=\''.sb_htmlentities($value['settings']['sprav_title_fld']).'\'>';
		            break;
		        case 'select_plugin':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'select_plugin\' ident=\''.$value['settings']['ident'].'\' modules_cat_id=\''.sb_htmlentities($value['settings']['modules_cat_id']).'\' subcategs=\''.intval($value['settings']['modules_subcategs']).'\' moduled_title_fld=\''.sb_htmlentities($value['settings']['modules_title_fld']).'\'>';
					break;
				case 'elems_plugin':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'elems_plugin\'  ident=\''.$value['settings']['ident'].'\'>';
					break;
				case 'multiselect_plugin':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'multiselect_plugin\' ident=\''.$value['settings']['ident'].'\' modules_cat_id=\''.sb_htmlentities($value['settings']['modules_cat_id']).'\' subcategs=\''.intval($value['settings']['modules_subcategs']).'\' separator=\''.sb_htmlentities($value['settings']['separator']).'\' modules_title_fld=\''.$value['settings']['modules_title_fld'].'\'>';
					break;
				case 'radio_plugin':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'radio_plugin\' ident=\''.$value['settings']['ident'].'\' count=\''.intval($value['settings']['count']).'\' separator=\''.sb_htmlentities($value['settings']['separator']).'\' modules_cat_id=\''.sb_htmlentities($value['settings']['modules_cat_id']).'\' subcategs=\''.intval($value['settings']['modules_subcategs']).'\' modules_title_fld=\''.sb_htmlentities($value['settings']['modules_title_fld']).'\'>';
					break;
				case 'checkbox_plugin':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'checkbox_plugin\' ident=\''.$value['settings']['ident'].'\' count=\''.intval($value['settings']['count']).'\' separator=\''.sb_htmlentities($value['settings']['separator']).'\' modules_cat_id=\''.sb_htmlentities($value['settings']['modules_cat_id']).'\' subcategs=\''.intval($value['settings']['modules_subcategs']).'\' modules_title_fld=\''.sb_htmlentities($value['settings']['modules_title_fld']).'\'>';
					break;
				case 'link_plugin':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\'link_plugin\' ident=\''.$value['settings']['ident'].'\' modules_link_title=\''.sb_htmlentities($value['settings']['modules_link_title']).'\' modules_cat_id=\''.intval($value['settings']['modules_cat_id']).'\' subcategs=\''.sb_htmlentities($value['settings']['modules_subcategs']).'\' moduled_title_fld=\''.sb_htmlentities($value['settings']['modules_title_fld']).'\'>';
					break;

		   		case 'string':
				case 'text':
				case 'longtext':
				case 'image':
				case 'link':
				case 'file':
				case 'color':
				case 'password':
				case 'google_coords':
				case 'yandex_coords':
				case 'table':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\''.$value['type'].'\'><![CDATA[';
					break;

				case 'number':
				case 'date':
					$array[$value['id']] = '<user_f_'.$value['id'].' tag=\''.$value['tag'].'\' name=\''.$value['title'].'\' type=\''.$value['type'].'\'>';
					break;
			}
        }
    }
}



function fPlugin_Maker_Get_Next_Prev($e_temp_id, $e_params, &$result, $output_page, $current_image_id, $pm_id, $pm_elems_settings)
{
    $result['href_prev'] = $result['href_next'] = $result['title_prev'] = $result['title_next'] = '';

	if($e_params != '')
		$e_params = unserialize($e_params);
	else
		$e_params = array();

//      выводить элементы зарегистрированных пользователей
	$user_link_id_sql = '';
	if(isset($e_params['registred_users']) && $e_params['registred_users'] == 1)
    {
		if(isset($_SESSION['sbAuth']))
		{
			$user_link_id_sql = ' AND p.p_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] == -1 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			return;
		}
		elseif(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] > 0 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND p.p_user_id = '.intval($_REQUEST['pl'.$pm_id.'_uid']);
		}
	}

	$cat_ids_tmp = array();
	if (isset($e_params['rubrikator']) && $e_params['rubrikator'] == 1 && (isset($_GET['pl'.$pm_id.'_scid']) || isset($_GET['pl'.$pm_id.'_cid'])))
	{
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['pl'.$pm_id.'_cid']))
        {
        	$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['pl'.$pm_id.'_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids_tmp[] = intval($_GET['pl'.$pm_id.'_cid']);
		}
		else
		{
			$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['pl'.$pm_id.'_scid'], 'pl_plugin_'.$pm_id);
			if ($res)
			{
				$cat_ids_tmp[] = $res[0][0];
			}
			else
			{
				$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['pl'.$pm_id.'_scid']);
				if ($res[0][0] > 0)
					$cat_ids_tmp[] = intval($_GET['pl'.$pm_id.'_scid']);
			}
		}

		if (count($cat_ids_tmp) == 0)
		{
			sb_404();
		}
	}
	else
	{
		$cat_ids_tmp = explode('^', $e_params['ids']);
	}

	// если следует выводить подразделы, то вытаскиваем их ID
	if (isset($e_params['subcategs']) && $e_params['subcategs'] == 1)
	{
		$res_tmp = sbQueryCache::query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
						WHERE c2.cat_left <= c.cat_left
						AND c2.cat_right >= c.cat_right
						AND c.cat_ident="pl_plugin_'.$pm_id.'"
						AND c2.cat_ident = "pl_plugin_'.$pm_id.'"
						AND c2.cat_id IN (?a)
						ORDER BY c.cat_left', $cat_ids_tmp);

		$cat_ids_tmp = array();
		if ($res_tmp)
		{
			foreach ($res_tmp as $value_tmp)
			{
				$cat_ids_tmp[] = $value_tmp[0];
			}
	    }
		else
		{
			// указанные разделы были удалены
			return;
		}
	}

	$categs_tmp = array();
	$res_cat = sbQueryCache::query('SELECT cat_id, cat_url FROM sb_categs WHERE cat_id IN (?a)', $cat_ids_tmp);

	if ($res_cat)
	{
		foreach ($res_cat as $value_tmp)
		{
			$categs_tmp[$value_tmp[0]] = array();
			$categs_tmp[$value_tmp[0]]['url'] = $value_tmp[1];
		}
	}

	// проверяем, есть ли закрытые разделы среди тех, которые надо выводить
	$res_tmp = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids_tmp);
	if ($res_tmp)
	{
		// проверяем права на закрытые разделы и исключаем их из вывода
	    $closed_ids = array();
	    foreach ($res_tmp as $value_tmp)
        {
			$closed_ids[] = $value_tmp[0];
		}

		$cat_ids_tmp = sbAuth::checkRights($closed_ids, $cat_ids_tmp, 'pl_plugin_'.$pm_id.'_read');
	}

	if (count($cat_ids_tmp) == 0)
	{
		// указанные разделы были удалены
		return;
	}

	// вытаскиваем макет дизайна
	$res_tmp = sql_param_query('SELECT ptl_checked, ptl_categ_top, ptl_element, ptl_categ_bottom
	   						FROM sb_plugins_temps_list WHERE ptl_id=?d', $e_temp_id);
	if (!$res_tmp)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);

		sb_404();
	}

	list ($ptl_checked, $ptl_top_cat, $ptl_element, $ptl_bottom_cat) = $res_tmp[0];

	$elems_fields_sort_sql = '';

	$votes_apply = $comments_sorting = false;
	if (isset($e_params['sort1']) && $e_params['sort1'] != '' && $e_params['sort1'] != 'RAND()')
	{
		if ($e_params['sort1'] == 'com_count' || $e_params['sort1'] == 'com_date')
		{
			$comments_sorting = true;
		}

		if ($e_params['sort1'] == 'p_rating' || $e_params['sort1'] == 'v.vr_num' || $e_params['sort1'] == 'v.vr_count')
		{
			$votes_apply = true;
		}

        $elems_fields_sort_sql .=  ', '.$e_params['sort1'];

        if (isset($e_params['order1']) && $e_params['order1'] != '')
        {
            $elems_fields_sort_sql .= ' '.$e_params['order1'];
        }
    }

    if (isset($e_params['sort2']) && $e_params['sort2'] != '' && $e_params['sort2'] != 'RAND()')
    {
		if ($e_params['sort2'] == 'com_count' || $e_params['sort2'] == 'com_date')
		{
			$comments_sorting = true;
		}

		if ($e_params['sort2'] == 'p_rating' || $e_params['sort2'] == 'v.vr_num' || $e_params['sort2'] == 'v.vr_count')
		{
			$votes_apply = true;
		}

        $elems_fields_sort_sql .=  ', '.$e_params['sort2'];
		if (isset($e_params['order2']) && $e_params['order2'] != '')
		{
			$elems_fields_sort_sql .= ' '.$e_params['order2'];
		}
    }

	if (isset($e_params['sort3']) && $e_params['sort3'] != '' && $e_params['sort3'] != 'RAND()')
	{
		if ($e_params['sort3'] == 'com_count' || $e_params['sort3'] == 'com_date')
		{
			$comments_sorting = true;
		}

		if ($e_params['sort3'] == 'p_rating' || $e_params['sort3'] == 'v.vr_num' || $e_params['sort3'] == 'v.vr_count')
		{
			$votes_apply = true;
		}

		$elems_fields_sort_sql .=  ', '.$e_params['sort3'];
		if (isset($e_params['order3']) && $e_params['order3'] != '')
		{
			$elems_fields_sort_sql .= ' '.$e_params['order3'];
		}
	}

	$now = time();
	$active_sql = '';
	if (isset($pm_elems_settings['show_active_field']) && $pm_elems_settings['show_active_field'] == 1)
	{
		$active_sql = 'AND p.p_active IN ('.sb_get_workflow_demo_statuses().')
	                       AND (p.p_pub_start IS NULL OR p.p_pub_start <= '.$now.')
	                       AND (p.p_pub_end IS NULL OR p.p_pub_end >= '.$now.')';
	}

	//	формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
	$elems_fields_where_sql = '';

	if ($ptl_checked != '')
	{
		$ptl_checked = explode(' ', $ptl_checked);
		foreach ($ptl_checked as $value_tmp)
		{
			$elems_fields_where_sql .= ' AND p.user_f_'.$value_tmp.'=1';
		}
	}

	if ($e_params['filter'] == 'last')
	{
		$last = intval($e_params['filter_last']) - 1;
		$last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND p.p_date >= '.$last.' AND p.p_date <= '.$now;
	}
	elseif ($e_params['filter'] == 'next')
	{
		$next = intval($e_params['filter_next']);
		$next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND p.p_date >= '.$now.' AND p.p_date <= '.$next;
	}

	// используется ли группировка по разделам
	if ($ptl_top_cat != '' || $ptl_bottom_cat != '')
	{
		$categs_tmp_output = true;
	}
	else
	{
		$categs_tmp_output = false;
	}

	//	выборка элементов, которые следует выводить
    if($categs_tmp_output)
    {
    	$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', p.p_sort');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' p.p_sort');
    }

    if($comments_sorting)
    {
    	$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = 'LEFT JOIN sb_comments com ON (com.c_el_id=p.p_id AND com.c_plugin="pl_plugin_'.$pm_id.'" AND com.c_show=1)';
	}
	else
    {
		$com_sort_fields = 'NULL, NULL';
		$com_sort_sql = '';
    }

    $group_str = '';
	$group_res = sql_param_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids_tmp);
    if ($group_res && $group_res[0][0] > 0 || $comments_sorting)
    {
		$group_str = ' GROUP BY p.p_id ';
	}

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL, ';

	if($votes_apply ||
		sb_strpos($ptl_element, '{RATING}') !== false ||
		sb_strpos($ptl_element, '{VOTES_COUNT}') !== false ||
		sb_strpos($ptl_element, '{VOTES_SUM}') !== false ||
		sb_strpos($ptl_element, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=p.p_id AND v.vr_plugin="pl_plugin_'.$pm_id.'" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS p_rating, ';
	}

	$res_tmp = sbQueryCache::query('SELECT l.link_cat_id, p.p_id, p.p_url, p.p_title,
				'.$votes_fields.
				$com_sort_fields.'
			FROM sb_plugins_'.$pm_id.' p
				'.$votes_sql.
				$com_sort_sql.'
				, sb_catlinks l'.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? ', sb_categs c' : '').'
			WHERE '.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
				(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=p.p_id
				'.$elems_fields_where_sql.' '.
				$user_link_id_sql.' '.
				$active_sql.' '.
				$group_str.' '.
				($categs_tmp_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql),
				$cat_ids_tmp);

	if (!$res_tmp)
	{
		return;
	}

	list($more_page, $more_ext) = sbGetMorePage($output_page);

	$n_next = $n_prev = '';

	$count = count($res_tmp);
	for($i = 0; $i != $count; $i ++)
	{
		if($res_tmp[$i][1] == $current_image_id)
		{
			$p_prev = $i - 1;
			if($p_prev < 0)
    			$p_prev = '';

            $p_next = $i + 1;
			if($p_next > count($res_tmp) - 1)
			    $p_next = '';

			break;
		}
	}

	// Ссылка на предыдущий элемент
	if($p_prev !== '')
	{
	    $result['title_prev'] = $res_tmp[$p_prev][3];

	    if ($more_page == '')
	    {
	        $result['href_prev'] = 'javascript: void(0);';
	    }
	    else
	    {
	        $result['href_prev'] = $more_page;
	        if (sbPlugins::getSetting('sb_static_urls') == 1)
	        {
	            // ЧПУ
	            $result['href_prev'] .= ($categs_tmp[$res_tmp[$p_prev][0]]['url'] != '' ? urlencode($categs_tmp[$res_tmp[$p_prev][0]]['url']).'/' : $res_tmp[$p_prev][0].'/').
									    ($res_tmp[$p_prev][2] != '' ? urlencode($res_tmp[$p_prev][2]) : $res_tmp[$p_prev][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] != '' ? '?pl'.$pm_id.'_uid='.$_REQUEST['pl'.$pm_id.'_uid'] : '');
	        }
	        else
	        {
	            $result['href_prev'] .= '?pl'.$pm_id.'_cid='.$res_tmp[$p_prev][0].'&pl'.$pm_id.'_id='.$res_tmp[$p_prev][1].(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] != '' ? '&pl'.$pm_id.'_uid='.$_REQUEST['pl'.$pm_id.'_uid'] : '');
	        }
	    }
	}

	// Ссылка на следующий элемент
	if($p_next !== '')
	{
	    $result['title_next'] = $res_tmp[$p_next][3];

	    if ($more_page == '')
	    {
	        $result['href_next'] = 'javascript: void(0);';
	    }
	    else
	    {
	        $result['href_next'] = $more_page;

	        if (sbPlugins::getSetting('sb_static_urls') == 1)
	        {
	            // ЧПУ
	            $result['href_next'] .= ($categs_tmp[$res_tmp[$p_next][0]]['url'] != '' ? urlencode($categs_tmp[$res_tmp[$p_next][0]]['url']).'/' : $res_tmp[$p_next][0].'/').
									    ($res_tmp[$p_next][2] != '' ? urlencode($res_tmp[$p_next][2]) : $res_tmp[$p_next][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] != '' ? '?pl'.$pm_id.'_uid='.$_REQUEST['pl'.$pm_id.'_uid'] : '');
	        }
	        else
	        {
	            $result['href_next'] .= '?pl'.$pm_id.'_cid='.$res_tmp[$p_next][0].'&pl'.$pm_id.'_id='.$res_tmp[$p_next][1].(isset($_REQUEST['pl'.$pm_id.'_uid']) && $_REQUEST['pl'.$pm_id.'_uid'] != '' ? '&pl'.$pm_id.'_uid='.$_REQUEST['pl'.$pm_id.'_uid'] : '');
	        }
	    }
	}
}

?>