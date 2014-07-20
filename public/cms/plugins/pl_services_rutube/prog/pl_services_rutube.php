<?php

/**
 * Функция выводит список роликов
 *
 */
function fServices_Rutube_List ($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
	if ($GLOBALS['sbCache']->check('pl_services_rutube', $tag_id, array($el_id, $temp_id, $params)))
        return;
    $params = unserialize(stripslashes($params));
    if (!isset($params['filter_text_logic']))
    	$params['filter_text_logic'] = 'AND';

    if (!isset($params['filter_logic']))
    	$params['filter_logic'] = 'AND';

    if (!isset($params['filter_compare']))
    	$params['filter_compare'] = 'IN';

    if (!isset($params['filter_morph']))
    	$params['filter_morph'] = 1;

    if (!isset($params['use_filter']))
    	$params['use_filter'] = 1;

    if (!isset($params['edit_page']))
    	$params['edit_page'] = '';
    if(!isset($params['page']))
    	$params['page'] = '';

    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['rutube_scid']) || isset($_GET['rutube_cid'])))
    {
        // используется связь с выводом разделов и выводить следует ролики из соотв. раздела
        if (isset($_GET['rutube_cid']))
        {
        	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['rutube_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids[] = intval($_GET['rutube_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_services_rutube"', $_GET['rutube_scid']);
            if ($res)
            {
				$cat_ids[] = $res[0][0];
			}
			else
            {
            	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['rutube_scid']);
	        	if ($res[0][0] > 0)
					$cat_ids[] = intval($_GET['rutube_scid']);
            }
        }

    	if (count($cat_ids) == 0)
	    {
	       	sb_404();
	    }
	}
	else
    {
        $cat_ids = explode('^', $params['ids']);
    }

    // если следует выводить подразделы, то вытаскиваем их ID
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
		$res = sql_param_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_services_rutube"
							AND c2.cat_ident = "pl_services_rutube"
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
        	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['rutube_scid']) || isset($_GET['rutube_cid'])))
        	{
        		sb_404();
        	}

            // указанные разделы были удалены
            $GLOBALS['sbCache']->save('pl_services_rutube', '');
            return;
        }
    }

    // проверяем, есть ли закрытые разделы среди тех, которые надо выводить

    $comments_read_cat_ids = $cat_ids; // разделы, для которых есть права comments_read
    $comments_edit_cat_ids = $cat_ids; // разделы, для которых есть права comments_edit
    $vote_cat_ids = $cat_ids; // разделы, для которых есть права vote

    $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_services_rutube_read');
        $comments_read_cat_ids = sbAuth::checkRights($closed_ids, $comments_read_cat_ids, 'pl_services_rutube_comments_read');
        $comments_edit_cat_ids = sbAuth::checkRights($closed_ids, $comments_edit_cat_ids, 'pl_services_rutube_comments_edit');
        $vote_cat_ids = sbAuth::checkRights($closed_ids, $vote_cat_ids, 'pl_services_rutube_vote');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT ssrt_lang, ssrt_checked, ssrt_count_row, ssrt_top, ssrt_categ_top, ssrt_temp_elem, ssrt_empty, ssrt_delim,
    //            ssrt_categ_bottom, ssrt_bottom, ssrt_pagelist_id, ssrt_perpage, ssrt_no_movies, ssrt_fields_temps, ssrt_categs_temps, ssrt_votes_id, ssrt_comments_id, ssrt_user_data_id, ssrt_tags_list_id
    //            FROM sb_services_rutube_temps_list WHERE ssrt_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_services_rutube_temps_list', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_RUTUBE_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }

    list($ssrt_lang, $ssrt_checked, $ssrt_count_row, $ssrt_top, $ssrt_categ_top, $ssrt_temp_elem, $ssrt_empty, $ssrt_delim,
                $ssrt_categ_bottom, $ssrt_bottom, $ssrt_pagelist_id, $ssrt_perpage, $ssrt_no_movies, $ssrt_fields_temps, $ssrt_categs_temps, $ssrt_votes_id, $ssrt_comments_id, $ssrt_user_data_id, $ssrt_tags_list_id) = $res[0];

    $ssrt_fields_temps = unserialize($ssrt_fields_temps);
    $ssrt_categs_temps = unserialize($ssrt_categs_temps);

    // вытаскиваем макет дизайна постраничного вывода
    //$res = sql_param_query('SELECT pt_perstage, pt_begin, pt_next, pt_previous, pt_end, pt_number, pt_sel_number, pt_page_list, pt_delim
    //            FROM sb_pager_temps WHERE pt_id=?d', $ssrt_pagelist_id);
    $res = sbQueryCache::getTemplate('sb_pager_temps', $ssrt_pagelist_id);
    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

	$user_link_id_sql = '';
    if(isset($params['registred_users']) && $params['registred_users'] == 1)
    {
		if(isset($_SESSION['sbAuth']))
		{
			$user_link_id_sql = ' AND m.ssr_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			$GLOBALS['sbCache']->save('pl_services_rutube', $ssrt_no_movies);
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] == -1 && $params['use_filter'] == 1)
		{
			$GLOBALS['sbCache']->save('pl_services_rutube', $ssrt_no_movies);
			return;
		}
		elseif(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] > 0 && $params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND m.ssr_user_id = '.intval($_REQUEST['rutube_uid']);
		}
	}

    // вытаскиваем пользовательские поля ролика и раздела
    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_services_rutube"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_services_rutube');

    $elems_fields = array();
    $categs_fields = array();

    $categs_sql_fields = array();
    $elems_fields_select_sql = '';

    $tags = array();

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
            foreach ($elems_fields as $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $elems_fields_select_sql .= ', m.user_f_'.$value['id'];

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
    if ($ssrt_checked != '')
    {
        $ssrt_checked = explode(' ', $ssrt_checked);
        foreach ($ssrt_checked as $value)
        {
            $elems_fields_where_sql .= ' AND m.user_f_'.$value.'=1';
        }
    }

    $now = time();
    if (isset($params['filter']) && $params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND m.ssr_date >= '.$last.' AND m.ssr_date <= '.$now;
    }
    elseif (isset($params['filter']) && $params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;
        $elems_fields_where_sql .= ' AND m.ssr_date >= '.$now.' AND m.ssr_date <= '.$next;
    }

    if (isset($params['filter_2']) && $params['filter_2'] == 'rating')
    {
        $params['filter_2_from'] = intval($params['filter_2_from']);
        $params['filter_2_to'] = intval($params['filter_2_to']);

        $elems_fields_where_sql .= 'AND (
                                   SELECT (res.vr_count/res.vr_num)
                                   FROM sb_vote_results as res, sb_vote_ips as ips
                                   WHERE res.vr_el_id=m.ssr_id AND ips.vi_vr_id=res.vr_id
                                   GROUP BY ips.vi_vr_id
                              ) >= '.$params['filter_2_from'].' AND
                              (
                                   SELECT (res.vr_count/res.vr_num)
                                   FROM sb_vote_results as res, sb_vote_ips as ips
                                   WHERE res.vr_el_id=m.ssr_id AND ips.vi_vr_id=res.vr_id
                                   GROUP BY ips.vi_vr_id
                              ) < '.$params['filter_2_to'];

    }

    if (isset($params['filter_3']) && $params['filter_3'] == 'votes')
    {
        $params['filter_3_from'] = intval($params['filter_3_from']);
        $params['filter_3_to'] = intval($params['filter_3_to']);

        $elems_fields_where_sql .= ' AND vote.vr_count >= '.$params['filter_3_from'].' AND vote.vr_count < '.$params['filter_3_to'];
    }

    if (isset($params['filter_4']) && $params['filter_4'] == 'views')
    {
        $params['filter_4_from'] = intval($params['filter_4_from']);
        $params['filter_4_to'] = intval($params['filter_4_to']);

        $elems_fields_where_sql .= ' AND m.ssr_views >= '.$params['filter_4_from'].' AND m.ssr_views < '.$params['filter_4_to'];
    }

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	// формируем SQL-запрос для фильтра
	$elems_fields_filter_sql = '';
	 if ($params['use_filter'] == 1)
     {
     	$date_temp = '';
		if(isset($_REQUEST['sr_f_temp_id']))
    	{
			$date = sql_param_query('SELECT ssrtf_fields_temps FROM sb_services_rutube_temps_form WHERE ssrtf_id = ?d', $_REQUEST['sr_f_temp_id']);
			if($date)
			{
				list($ssrtf_fields_temps) = $date[0];
				$ssrtf_fields_temps = unserialize($ssrtf_fields_temps);
				$date_temp = $ssrtf_fields_temps['date_temps'];
			}
		}

		$morph_db = false;
		if ($params['filter_morph'] == 1)
		{
			require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
		}
		$elems_fields_filter_sql = '(';

		$elems_fields_filter_sql .= sbGetFilterNumberSql('m.ssr_id', 'sr_f_id', $params['filter_logic']);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('m.ssr_date', 'sr_f_date', $params['filter_logic'], true, $date_temp);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('m.ssr_views', 'sr_f_views', $params['filter_logic'], false);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('m.ssr_duration', 'sr_f_duration', $params['filter_logic'], false);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('m.ssr_size', 'sr_f_size', $params['filter_logic'], false);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('m.ssr_user_id', 'sr_f_user_id', $params['filter_logic']);

		$elems_fields_filter_sql .= sbGetFilterTextSql('m.ssr_name', 'sr_f_title', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    $elems_fields_filter_sql .= sbGetFilterTextSql('m.ssr_description', 'sr_f_desc', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    $elems_fields_filter_sql .= sbGetFilterTextSql('m.ssr_author', 'sr_f_author', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

		$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'm', 'sr_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);

	}

    if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
		$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
	else
		$elems_fields_filter_sql = '';

    // формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
	$votes_apply = $comments_sorting = false;

	if(isset($params['use_sort']) && $params['use_sort'] == '1' && isset($_REQUEST['s_f_sr']) && trim($_REQUEST['s_f_sr']) != '')
	{
		$elems_fields_sort_sql .= sbLayout::getPluginFieldsSortSql('sr', 'm');
	}
	else
	{
	    if (isset($params['sort1']) && $params['sort1'] != '')
	    {
			if ($params['sort1'] == 'com_count' || $params['sort1'] == 'com_date')
			{
				$comments_sorting = true;
			}
			if ($params['sort1'] == 'm_rating' || $params['sort1'] == 'v.vr_num' || $params['sort1'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

	        $elems_fields_sort_sql .=  ', '.$params['sort1'];

	    	if ($params['sort1'] == 'RAND()')
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
			if ($params['sort2'] == 'm_rating' || $params['sort2'] == 'v.vr_num' || $params['sort2'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

	        $elems_fields_sort_sql .=  ', '.$params['sort2'];

	    	if ($params['sort2'] == 'RAND()')
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

			if ($params['sort3'] == 'm_rating' || $params['sort3'] == 'v.vr_num' || $params['sort3'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

			$elems_fields_sort_sql .=  ', '.$params['sort3'];

			if ($params['sort3'] == 'RAND()')
	    	{
				$GLOBALS['sbCache']->mCacheOff = true;
	    	}

	        if (isset($params['order3']) && $params['order3'] != '')
	        {
	            $elems_fields_sort_sql .= ' '.$params['order3'];
	        }
	    }
	}

//	Связь с календарем
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

    	$elems_fields_where_sql .= ' AND m.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'" AND m.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'"';
    }

    // Отключаем выводимый ролик
	if (isset($params['show_selected']) && $params['show_selected'] == 1 && (isset($_GET['rutube_sid']) || isset($_GET['rutube_id'])))
	{
		if (isset($_GET['rutube_id']))
		{
			$elems_fields_where_sql .= ' AND m.ssr_id != "'.intval($_GET['rutube_id']).'"';
		}
		else
		{
			$res = sql_param_query('SELECT ssr_id FROM sb_services_rutube WHERE ssr_url=?', $_GET['rutube_sid']);
			if ($res)
			{
				$elems_fields_where_sql .= ' AND m.ssr_id != "'.$res[0][0].'"';
			}
			else
			{
				$elems_fields_where_sql .= ' AND m.ssr_id != "'.intval($_GET['rutube_sid']).'"';
			}
		}
	}

	//	связь с выводом облака тегов
	$cloud_where_sql = '';
	$ssr_tag = '';

    if (isset($params['cloud']) && $params['cloud'] == 1 && isset($_REQUEST['ssr_tag']) && $_REQUEST['ssr_tag'] != '')
    {
    	$tag = trim(preg_replace('/[^0-9\,\s]+/', '', $_REQUEST['ssr_tag']));
    	if ($tag != '')
        	$ssr_tag .= $tag;
    }

    if (isset($params['cloud_comp']) && $params['cloud_comp'] == 1)
    {
    	if (isset($_REQUEST['ssr_tag_comp']) && $_REQUEST['ssr_tag_comp'] != '')
    	{
	    	$tag = trim(preg_replace('/[^0-9\,\s\-]+/', '', $_REQUEST['ssr_tag_comp']));
	    	if ($tag != '')
	        	$ssr_tag .= ($ssr_tag != '' ? ',' : '').$tag;
    	}
    	else
    	{
    		$GLOBALS['sbCache']->save('pl_services_rutube', $ssrt_no_movies);
			return;
    	}
    }

    if ($ssr_tag != '')
	{
		$cloud_where_sql = ' AND cl.cl_ident="pl_services_rutube" AND m.ssr_id=cl.cl_el_id AND cl.cl_tag_id IN ('.$ssr_tag.')';
	}

    // используется ли группировка по разделам
    if ($ssrt_categ_top != '' || $ssrt_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }

    // Название для куки содержащего кол-во элементов на страничке
    $num_cookie_name = 'pl_services_rutube_'.$temp_id.'_'.$tag_id;

    require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
    $pager = new sbDBPager($tag_id, $pt_perstage, $ssrt_perpage, '', $num_cookie_name);
    if (isset($params['filter']) && $params['filter'] == 'from_to')
    {
		$pager->mFrom = intval($params['filter_from']);
		$pager->mTo = intval($params['filter_to']);
    }

    // Если ролики подгружаются как связанные, выводить не раздел, а список конкретных роликов
    $sql_linked = '';
    if($linked != 0)
    {
    	$sql_linked = ' AND m.ssr_id IN ('.$linked.') ';
    }

	// выборка роликов, которые следует выводить
	$movie_total = true;

	$group_str = '';
	$group_res = sql_param_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
	if ($group_res && $group_res[0][0] > 0 || $comments_sorting || $cloud_where_sql != '')
	{
		$group_str = ' GROUP BY m.ssr_id ';
	}

	if($categs_output)
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', m.ssr_date');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' m.ssr_date');
	}

    if($comments_sorting)
    {
    	$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = 'LEFT JOIN sb_comments com ON com.c_el_id=m.ssr_id AND com.c_plugin="pl_services_rutube" AND com.c_show=1';
	}
	else
    {
		$com_sort_fields = 'NULL, NULL';
		$com_sort_sql = '';
    }

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL,';
    if($votes_apply ||
		sb_strpos($ssrt_temp_elem, '{RATING}') !== false ||
		sb_strpos($ssrt_temp_elem, '{VOTES_COUNT}') !== false ||
		sb_strpos($ssrt_temp_elem, '{VOTES_SUM}') !== false ||
		sb_strpos($ssrt_temp_elem, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=m.ssr_id AND v.vr_plugin="pl_services_rutube" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS m_rating, ';
	}
	$res = $pager->init($movie_total, 'SELECT l.link_cat_id, m.ssr_id, m.ssr_name, m.ssr_date, m.ssr_rutube_id, m.ssr_url, m.ssr_description,
				m.ssr_views, m.ssr_author, m.ssr_duration, m.ssr_size,
				'.$votes_fields.'
				m.ssr_user_id,
				'.$com_sort_fields.'
				'.$elems_fields_select_sql.'
			FROM sb_services_rutube m
				'.$votes_sql.
				$com_sort_sql.'
				, sb_catlinks l '.
				(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').
				($cloud_where_sql != '' ? ', sb_clouds_links cl' : '').'
			WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ' c.cat_id IN (?a) AND c.cat_id=l.link_cat_id ' : ' l.link_cat_id IN (?a) ').' AND l.link_el_id=m.ssr_id
				'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').'
                AND m.ssr_status = 0
                AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
                AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
                AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
				'.$sql_linked.' '.
				$elems_fields_where_sql.' '.
				$user_link_id_sql.' '.
				$elems_fields_filter_sql.' '.
				$cloud_where_sql.' '.
				$group_str.' '.
				($elems_fields_sort_sql != '' ? ' ORDER BY '.substr($elems_fields_sort_sql, 1) : ' ORDER BY m.ssr_date '), $cat_ids);

	if(!$res)
	{
		$GLOBALS['sbCache']->save('pl_services_rutube', $ssrt_no_movies);
		return;
	}

	$count_movies = $pager->mFrom + 1;
	$comments_count = array();
	if(sb_substr_count($ssrt_temp_elem, '{COUNT_COMMENTS}') > 0)
	{
	    if ($comments_sorting)
	    {
	    	for($i = 0; $i < count($res); $i++)
	        {
		       $comments_count[$res[$i][1]] = $res[$i][15];
	        }
	    }
	    else
	    {
			$ids_arr = array();
			for($i = 0; $i < count($res); $i++)
	        {
				$ids_arr[] = $res[$i][1];
	        }

			// достаем кол-во комментариев
			require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
			$comments_count = fComments_Get_Count($ids_arr, 'pl_services_rutube');
		}
	}

    $categs = array();
    if (sb_substr_count($ssrt_categ_top, '{CAT_COUNT}') > 0 ||
        sb_substr_count($ssrt_categ_bottom, '{CAT_COUNT}') > 0 ||
        sb_substr_count($ssrt_temp_elem, '{CAT_COUNT}') > 0
       )
    {
        $res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
                (

                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_services_rutube m
                WHERE c.cat_id = c1.cat_id
                AND l.link_el_id=m.ssr_id
                AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
    	        AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
	            AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
				AND l.link_src_cat_id NOT IN (?a)

				) AS cat_count, c1.cat_closed
				FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids);
    }
	else
	{
		$res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, cat_closed
									FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
    }

    if ($res_cat)
    {
        foreach ($res_cat as $value)
        {
            $categs[$value[0]] = array();
            $categs[$value[0]]['id'] = $value[0];
            $categs[$value[0]]['title'] = $value[1];
            $categs[$value[0]]['level'] = $value[2] + 1;
            $categs[$value[0]]['fields'] = (trim($value[3]) != '' ? unserialize($value[3]) : array());
            $categs[$value[0]]['url'] = urlencode($value[4]);
            $categs[$value[0]]['count'] = $value[5];
            $categs[$value[0]]['closed'] = $value[6];
        }
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

	//	верх вывода списка роликов
    $flds_tags = array( '{SORT_ID_ASC}' ,'{SORT_ID_DESC}',
    					'{SORT_NAME_ASC}' ,'{SORT_NAME_DESC}',
    					'{SORT_DESCRIPTION_ASC}' ,'{SORT_DESCRIPTION_DESC}',
    					'{SORT_DATE_ASC}' ,'{SORT_DATE_DESC}',
    					'{SORT_VIEWS_ASC}' ,'{SORT_VIEWS_DESC}',
    					'{SORT_AUTHOR_ASC}' ,'{SORT_AUTHOR_DESC}',
    					'{SORT_DURATION_ASC}' ,'{SORT_DURATION_DESC}',
    					'{SORT_SIZE_ASC}' ,'{SORT_SIZE_DESC}',
    					'{SORT_USER_ID_ASC}' ,'{SORT_USER_ID_DESC}',
    					'{SORT_SHOW_ASC}' ,'{SORT_SHOW_DESC}');

    $query_str = $_SERVER['QUERY_STRING'];
    if(isset($_GET['s_f_sr']))
    {
    	$query_str = preg_replace('/[?&]?s_f_sr['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
    }

	$flds_href = $GLOBALS['PHP_SELF'].(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_sr=';

    $flds_vals = array( $flds_href.urlencode('ssr_id=ASC'),
    					$flds_href.urlencode('ssr_id=DESC'),
    					$flds_href.urlencode('ssr_name=ASC'),
    					$flds_href.urlencode('ssr_name=DESC'),
    					$flds_href.urlencode('ssr_description=ASC'),
    					$flds_href.urlencode('ssr_description=DESC'),
    					$flds_href.urlencode('ssr_date=ASC'),
    					$flds_href.urlencode('ssr_date=DESC'),
    					$flds_href.urlencode('ssr_views=ASC'),
    					$flds_href.urlencode('ssr_views=DESC'),
    					$flds_href.urlencode('ssr_author=ASC'),
    					$flds_href.urlencode('ssr_author=DESC'),
    					$flds_href.urlencode('ssr_duration=ASC'),
    					$flds_href.urlencode('ssr_duration=DESC'),
    					$flds_href.urlencode('ssr_size=ASC'),
    					$flds_href.urlencode('ssr_size=DESC'),
    					$flds_href.urlencode('ssr_user_id=ASC'),
    					$flds_href.urlencode('ssr_user_id=DESC'),
    					$flds_href.urlencode('ssr_show=ASC'),
    					$flds_href.urlencode('ssr_show=DESC'));

    sbLayout::getPluginFieldsTagsSort('sr', $flds_tags, $flds_vals, 'href_replace');

	// Заменяем значение селекта "Кол-во на странице" селектед
	if(isset($_REQUEST['num_'.$tag_id]))
    {
    	$ssrt_top = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $ssrt_top);
    }
    elseif(isset($_COOKIE[$num_cookie_name]))
    {
    	$ssrt_top = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $ssrt_top);
    }

	$result = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $movie_total, 'num_'.$tag_id),$flds_vals), $ssrt_top);
	$tags = array_merge($tags, array(
								     '{RATING}',
									 '{VOTES_COUNT}',
									 '{VOTES_SUM}',
									 '{VOTES_FORM}',
    								 '{ELEM_NUMBER}',
                                     '{ID}',
                                     '{ELEM_URL}',
									 '{TITLE}',
									 '{DESCRIPTION}',
									 '{DATE}',
    								 '{CHANGE_DATE}',
    								 '{EDIT_LINK}',
									 '{VIEWS}',
                                     '{MOVIE}',
									 '{MOVIE_HTML}',
									 '{AUTHOR}',
									 '{DURATION}',
									 '{SIZE}',
									 '{FOTO}',
									 '{LINK}',
                                     '{USER_DATA}',
									 '{ELEM_USER_LINK}',
    								 '{TAGS}',
                                     '{COUNT_COMMENTS}',
                                     '{FORM_COMMENTS}',
                                     '{LIST_COMMENTS}',
									 '{CAT_ID}',
                                     '{CAT_URL}',
									 '{CAT_TITLE}',
 									 '{CAT_COUNT}',
 									 '{CAT_LEVEL}'));

    $cur_cat_id = 0;
    $values = array();
    $cat_values = array();
    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $col = 0;

    $dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');

   	list($more_page, $more_ext) = sbGetMorePage($params['page']);

	$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
	if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
	{
		$auth_page = '/'.$auth_page;
	}

    $view_rating_form = (sb_strpos($ssrt_temp_elem, '{VOTES_FORM}') !== false && $ssrt_votes_id > 0);
    $view_comments_list = (sb_strpos($ssrt_temp_elem, '{LIST_COMMENTS}') !== false && $ssrt_comments_id > 0);
    $view_comments_form = (sb_strpos($ssrt_temp_elem, '{FORM_COMMENTS}') !== false && $ssrt_comments_id > 0);

    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');

 	if(sb_strpos($ssrt_temp_elem, '{TAGS}') !== false)
    {
     	// Достаю макеты для вывода списка тегов элементов
		$tags_template_error = false;
    	$res1 = sql_param_query('SELECT ct_pagelist_id, ct_perpage
                FROM sb_clouds_temps WHERE ct_id=?d', $ssrt_tags_list_id);

    	if (!$res1)
    	   $tags_template_error = true;

    	list($ct_pagelist_id, $ct_perpage) = $res1[0];

        // Вытаскиваем макет дизайна постраничного вывода
    	$res1 = sql_param_query('SELECT pt_perstage, pt_begin, pt_next, pt_previous, pt_end, pt_number, pt_sel_number, pt_page_list, pt_delim
                FROM sb_pager_temps WHERE pt_id=?d', $ct_pagelist_id);
     	if ($res1)
     	{
        	list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list_tags, $pt_delim) = $res1[0];
     	}
     	else
     	{
        	$pt_page_list_tags = '';
        	$pt_perstage = 1;
     	}

    }
    foreach ($res as $value)
    {
    	$value[5] = urlencode($value[5]);

   	    $old_values = $values;
        $values = array();

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
                         ($value[5] != '' ? $value[5] : $value[1]).($more_ext != 'php' ? '.'.$more_ext : '/');
            }
            else
            {
                $href .= '?rutube_cid='.$value[0].'&rutube_id='.$value[1];
            }
        }

        $href = ($href != '' ? str_replace('{LINK}', $href, $ssrt_fields_temps['ssrt_link']) : '');

        $dop_values = array($value[1], strip_tags($value[5]), strip_tags($value[2]), $value[0], $categs[$value[0]]['url'], strip_tags($categs[$value[0]]['title']), $href);
        if ($num_fields > 17)
        {
			for ($i = 17; $i < $num_fields; $i++)
			{
				$values[] = $value[$i];
			}

			$allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;
            $values = sbLayout::parsePluginFields($elems_fields, $values, $ssrt_fields_temps, $dop_tags, $dop_values, $ssrt_lang, '', '', $allow_bb, $link_level, $ssrt_temp_elem);
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
                $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ssrt_categs_temps, $dop_tags, $dop_values, $ssrt_lang, '', '', $allow_bb, $link_level, $ssrt_categ_top.$ssrt_temp_elem.$ssrt_categ_bottom);
            }
            $values = array_merge($values, $cat_values);
        }

        $votes_sum = ($value[11] != '' && !is_null($value[11]) ? $value[11] : 0);
        $votes_count = ($value[12] != '' && !is_null($value[12]) ? $value[12] : 0);
        $votes_rating = ($value[13] != '' && !is_null($value[13]) ? sprintf('%.2f', $value[13]) : 0);

        // VOTES_FORM
        if ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1)
        {
            $res_vote = fVoting_Form_Submit($ssrt_votes_id, 'pl_services_rutube', $value[1], $votes_sum, $votes_count, $votes_rating);
        }

        $values[] = $votes_rating; // RATING
        $values[] = $votes_count; // VOTES_COUNT
        $values[] = $votes_sum; // VOTES_SUM

        if($view_rating_form && ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1))
        {
			$values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ssrt_votes_id, 'pl_services_rutube', $value[1]));     //    VOTES_FORM
        }
        else
        {
			$values[] = '';	 //    VOTES_FORM
        }

		$values[] = $count_movies++;		//	ELEM_NUMBER
        $values[] = $value[1];              //  ID
        $values[] = $value[5];              //  ELEM_URL
        $values[] = $value[2];              //  TITLE
	    if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
		{
			//Если разрешен bb код
	    	$value[6] = sbProgParseBBCodes($value[6]);
		}
        $values[] = ($value[6] != '' && !is_null($value[6])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[6]), $dop_values),
                    $ssrt_fields_temps['ssrt_description']):'');       //  DESCRIPTION

        $values[] = sb_parse_date($value[3], $ssrt_fields_temps['ssrt_date'], $ssrt_lang);      //  DATE
     	// Дата последнего изменения
        if(sb_strpos($ssrt_temp_elem, '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_services_rutube');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ssrt_fields_temps['ssrt_change_date'], $ssrt_lang) : ''; //   CHANGE_DATE
        }
        else
       	{
        	$values[] = '';
       	}
       	 // Ссылка "Редактировать"
       	list($edit_page, $edit_ext) = sbGetMorePage($params['edit_page']);
	    if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($value[14] != $_SESSION['sbAuth']->getUserId())))
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
	                         ($value[5] != '' ? $value[5] : $value[1]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
	            }
	            else
	            {
	                $edit_href .= '?rutube_cid='.$value[0].'&rutube_id='.$value[1];
	            }
	        }

	    	if ($edit_page != '' && isset($ssrt_fields_temps['ssrt_edit_link']))
	        {
	        	$edit = str_replace(array_merge(array('{EDIT_LINK}'), $dop_tags), array_merge(array($edit_href), $dop_values), $ssrt_fields_temps['ssrt_edit_link']);
	        }
	        else
	        {
	        	$edit = '';
	        }
    	}

    	$values[] = $edit; //EDIT_LINK

        $values[] = ($value[7] != '' && !is_null($value[7])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[7]), $dop_values),
					$ssrt_fields_temps['ssrt_views']):0);       //  VIEWS

//  	Тег {MOVIE} поддерживаем для сайтов у которых модуль rutube был настроен до добавления сервиса YouTube
		if(sb_strpos($value[4], 'youtube.com') !== false)
		{
			$values[] = ''; //   MOVIE
			$values[] = ($value[4] != '' && !is_null($value[4])? str_replace(array_merge(array('{YOUTUBE_MOVIE_ID}'), $dop_tags), array_merge(array($value[4]), $dop_values), isset($ssrt_fields_temps['ssrt_youtube_movie']) ? $ssrt_fields_temps['ssrt_youtube_movie'] : '') : '');	//	MOVIE_HTML
		}
		else
		{
			$values[] = $value[4];	//	MOVIE
			$values[] = ($value[4] != '' && !is_null($value[4])? str_replace(array_merge(array('{RUTUBE_MOVIE_ID}'), $dop_tags), array_merge(array($value[4]), $dop_values), isset($ssrt_fields_temps['ssrt_rutube_movie']) ? $ssrt_fields_temps['ssrt_rutube_movie'] : '') : '');	//	MOVIE_HTML
		}

		$values[] = ($value[8] != '' && !is_null($value[8])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[8]), $dop_values),
				$ssrt_fields_temps['ssrt_author']):'');       //  AUTHOR

		$dur = is_float($value[9] / 60) ? explode('.', round(($value[9] / 60), 2)) : ($value[9] / 60);
		if(is_array($dur))
		{
			if(sb_strlen($dur[0]) == 1)
			{
				$dur[0] = '0'.$dur[0];
			}

			$dur[1] =  round(($dur[1] * 60) / 100, 0);
			$duration = $dur[0].':'.$dur[1];
		}
		else
		{
			if(sb_strlen($dur) == 1)
			{
				$duration = '0'.$dur.':00';
			}
			else
			{
				$duration = $dur.':00';
			}
		}
		$values[] = ($value[9] != '' && !is_null($value[9])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($duration), $dop_values), $ssrt_fields_temps['ssrt_duration']):'');       //  DURATION

        $size = round(($value[10]/1024/1024), 2).' Mb' ;
        if($size < 1)
	        $size = round(($value[10]/1024), 2).' Kb';
	    if($size < 1)
	        $size = $value[10].' b';

        $values[] = ($size != '' && !is_null($size)? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($size), $dop_values), $ssrt_fields_temps['ssrt_size']):'');       //  SIZE

        if($GLOBALS['sbVfs']->exists('/upload/rutube/'.$value[1].'.jpg') && $GLOBALS['sbVfs']->is_file('/upload/rutube/'.$value[1].'.jpg'))
        {
			$prev_src = SB_DOMAIN.'/upload/rutube/'.$value[1].'.jpg';
		}
		else
		{
			$prev_src = SB_DOMAIN.'/upload/pl_services_rutube/'.$value[1].'.jpg';
        }

        $values[] = ($value[1] != '' ? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($prev_src), $dop_values), $ssrt_fields_temps['ssrt_foto']):'');       //  FOTO
        $values[] = $href;                        //  LINK

        if($ssrt_user_data_id > 0 && isset($value[14]) && $value[14] > 0 && sb_strpos($ssrt_temp_elem, '{USER_DATA}') !== false)
        {
            require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
            $values[] = fSite_Users_Get_Data($ssrt_user_data_id, $value[14]); //     USER_DATA
        }
        else
        {
            $values[] = '';   //   USER_DATA
        }

        if(isset($value[14]) && $value[14] > 0 && isset($ssrt_fields_temps['ssrt_registred_users']) && $ssrt_fields_temps['ssrt_registred_users'] != '' )
        {
			$action = $auth_page.'?rutube_uid='.$value[14].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');
			$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ssrt_fields_temps['ssrt_registred_users']);
        }
        else
        {
        	$values[] = '';  //   ELEM_USER_LINK
        }
        if(sb_strpos($ssrt_temp_elem, '{TAGS}') !== false)
        {
        	$tags_error = false;
	     	// Вывод тематических тегов
		    $pager_tags = new sbDBPager('t_'.$value[1], $pt_perstage, $ct_perpage);

		    // Вытаскиваю теги
			$tags_total = true;
			$res_tags = $pager_tags->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_clouds_links cl
	                            WHERE cl.cl_tag_id IN
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_services_rutube" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_services_rutube"
	                                AND ct.ct_id=cl.cl_tag_id
	                            GROUP BY cl.cl_tag_id', $value[1]);

	    	if (!$res_tags)
	    	{
	       		$tags_error = true;
	    	}

	    	if(!$tags_error && !$tags_template_error)
	    	{
		    	// строим список номеров страниц
			    if ($pt_page_list_tags != '')
			    {
			    	$pager_tags->mBeginTemp = $pt_begin;
			    	$pager_tags->mBeginTempDisabled = '';
			    	$pager_tags->mNextTemp = $pt_next;
			    	$pager_tags->mNextTempDisabled = '';

			    	$pager_tags->mPrevTemp = $pt_previous;
			       	$pager_tags->mPrevTempDisabled = '';
			       	$pager_tags->mEndTemp = $pt_end;
			        $pager_tags->mEndTempDisabled = '';

			        $pager_tags->mNumberTemp = $pt_number;
			        $pager_tags->mCurNumberTemp = $pt_sel_number;
			        $pager_tags->mDelimTemp = $pt_delim;
			        $pager_tags->mListTemp = $pt_page_list_tags;

			        $pt_page_list_tags_1 = $pager_tags->show();
			    }
	    		require_once (SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php');
				$values[] = fClouds_Show($res_tags, $ssrt_tags_list_id,  $pt_page_list_tags_1, $tags_total, '', 'ssr_tag'); //     TAGS
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

            if (fComments_Add_Comment($ssrt_comments_id, 'pl_services_rutube', $value[1], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
                $c_count++;
        }

        $values[] = $c_count; // COUNT_COMMENTS

        if ($view_comments_form)
        {
            $values[] = fComments_Get_Form($ssrt_comments_id, 'pl_services_rutube', $value[1], $add_comments); // FORM_COMMENTS
        }
        else
        {
            $values[] = ''; // FORM_COMMENTS
        }

        if ($view_comments_list)
        {
        	$exists_rights = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_read_cat_ids) || $categs[$value[0]]['closed'] != 1);
			$values[] = fComments_Get_List($ssrt_comments_id, 'pl_services_rutube', $value[1], $add_comments, '', 0, $exists_rights);	//	LIST_COMMENTS
        }
        else
        {
            $values[] = ''; // LIST_COMMENTS
        }

        $values[] = $categs[$value[0]]['id'];     // CAT_ID
        $values[] = $categs[$value[0]]['url'];     // CAT_URL
        $values[] = $categs[$value[0]]['title'];  // CAT_TITLE
        $values[] = $categs[$value[0]]['count'];  // CAT_COUNT
        $values[] = $categs[$value[0]]['level'];  // CAT_LEVEL

        if ($categs_output && $value[0] != $cur_cat_id)
        {
            if ($cur_cat_id != 0)
            {
                // низ вывода раздела
                while ($col < $ssrt_count_row)
                {
                    $result .= $ssrt_empty;
                    $col++;
                }

                $result .= sb_str_replace($tags, $old_values, $ssrt_categ_bottom);
            }

            // верх вывода раздела
            $result .= str_replace($tags, $values, $ssrt_categ_top);
            $col = 0;
        }

        if ($col >= $ssrt_count_row)
        {
            $result .= $ssrt_delim;
            $col = 0;
        }

        $result .= str_replace($tags, $values, $ssrt_temp_elem);

        $cur_cat_id = $value[0];
        $col++;
    }

    while ($col < $ssrt_count_row)
    {
        $result .= $ssrt_empty;
        $col++;
    }

    if ($categs_output)
    {
        // низ вывода раздела
        $result .= str_replace($tags, $values, $ssrt_categ_bottom);
    }

    // низ вывода списка роликов
// Заменяем значение селекта "Кол-во на странице" селектед
	if(isset($_REQUEST['num_'.$tag_id]))
    {
    	$ssrt_bottom = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $ssrt_bottom);
    }
    elseif(isset($_COOKIE[$num_cookie_name]))
    {
    	$ssrt_bottom = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $ssrt_bottom);
    }
    $result .= str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $movie_total, 'num_'.$tag_id),$flds_vals), $ssrt_bottom);

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    /***********************************************************/
    if($linked == 0)
    {
    	$GLOBALS['sbCache']->save('pl_services_rutube', $result);
    }
    else
    {
    	return $result;
    }
    /************************************************************/
}

/**
 * Функция выводит полный ролик
 */
function fServices_Rutube_Full ($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
	$ssr_id = $GLOBALS['sbCache']->check('pl_services_rutube', $tag_id, array($el_id, $temp_id, $params));
	if ($ssr_id)
	{
		@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
    	fClouds_Init_Tags('pl_services_rutube', array($ssr_id));
    	return;
	}

    $params = unserialize(stripslashes($params));
	if($linked > 0)
		$_GET['rutube_id'] = $linked;

    if (!isset($_GET['rutube_sid']) && !isset($_GET['rutube_id']))
    {
        if($linked > 0)
            unset($_GET['rutube_id']);

        return;
    }

    $cat_id = -1;
    if (isset($_GET['rutube_scid']) || isset($_GET['rutube_cid']))
    {
        // используется связь с выводом разделов и выводить следует ролики из соотв. раздела
        if (isset($_GET['rutube_cid']))
        {
            $cat_id = intval($_GET['rutube_cid']);
        }
        else
        {
        	$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_services_rutube"', $_GET['rutube_scid']);
        	if ($res)
            {
            	$cat_id = $res[0][0];
            }
            else
            {
                $cat_id = intval($_GET['rutube_scid']);
            }
        }
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT ssrtf_lang, ssrtf_fullelement, ssrtf_fields_temps, ssrtf_categs_temps, ssrtf_checked, ssrtf_voting_id, ssrtf_comments_id, ssrtf_user_data_id, ssrtf_tags_list_id
    //            FROM sb_services_rutube_temps_full WHERE ssrtf_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_services_rutube_temps_full', $temp_id);
    if (!$res)
    {
        if($linked > 0)
            unset($_GET['rutube_id']);

        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_RUTUBE_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }
    list($ssrtf_lang, $ssrtf_fullelement, $ssrtf_fields_temps, $ssrtf_categs_temps, $ssrtf_checked, $ssrtf_voting_id, $ssrtf_comments_id, $ssrtf_user_data_id, $ssrtf_tags_list_id) = $res[0];

    $ssrtf_fields_temps = unserialize($ssrtf_fields_temps);
    $ssrtf_categs_temps = unserialize($ssrtf_categs_temps);

    // вытаскиваем пользовательские поля новости и раздела
    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_services_rutube"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_services_rutube');

    $elems_fields = array();
    $categs_fields = array();

    $categs_sql_fields = array();
    $elems_fields_select_sql = '';

    $tags = array();
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
            foreach ($elems_fields as $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $elems_fields_select_sql .= ', m.user_f_'.$value['id'];

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

	$user_link_id_sql = '';
    if(isset($params['registred_users']) && $params['registred_users'] == 1)
    {
		if(isset($_SESSION['sbAuth']))
		{
			$user_link_id_sql = ' AND m.ssr_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			if($linked > 0)
    		{
    		    unset($_GET['rutube_id']);
    			sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_RUTUBE_PLUGIN), SB_MSG_WARNING);
    			return;
    		}
    		else
    		{
				sb_404();
    		}
		}
	}

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';
    if ($ssrtf_checked != '')
    {
        $ssrtf_checked = explode(' ', $ssrtf_checked);
        foreach ($ssrtf_checked as $value)
        {
            $elems_fields_where_sql .= ' AND m.user_f_'.$value.'=1';
        }
    }

    $now = time();
    if ($cat_id != -1 && $linked < 1)
    {
        $cat_dop_sql = 'AND c.cat_id="'.$cat_id.'"';
    }
    else
    {
        $cat_dop_sql = 'AND c.cat_ident="pl_services_rutube"';
    }

    if (isset($_GET['rutube_id']))
    {
			$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url, m.ssr_id, m.ssr_name,
                            m.ssr_date, m.ssr_url, m.ssr_description, m.ssr_views, m.ssr_author, m.ssr_duration, m.ssr_size,
                            m.ssr_rutube_id, v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS m_rating, m.ssr_user_id
                            '.$elems_fields_select_sql.'
                            FROM sb_services_rutube m LEFT JOIN sb_vote_results v ON v.vr_el_id=?d AND v.vr_plugin="pl_services_rutube", sb_categs c, sb_catlinks l
                            WHERE m.ssr_id=?d AND l.link_el_id=m.ssr_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
                        	AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
			    	        AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
				            AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
							'.$elems_fields_where_sql.$user_link_id_sql, $_GET['rutube_id'], $_GET['rutube_id']);
    }
	else
	{
			$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url, m.ssr_id, m.ssr_name,
                            m.ssr_date, m.ssr_url, m.ssr_description, m.ssr_views, m.ssr_author, m.ssr_duration, m.ssr_size,
                            m.ssr_rutube_id,
                            v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS m_rating, m.ssr_user_id
                            '.$elems_fields_select_sql.'
                            FROM sb_services_rutube m LEFT JOIN sb_vote_results v ON v.vr_el_id=m.ssr_id AND v.vr_plugin="pl_services_rutube", sb_categs c, sb_catlinks l
                            WHERE m.ssr_url=? AND l.link_el_id=m.ssr_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
							AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
			    	        AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
				            AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
                            '.$elems_fields_where_sql.$user_link_id_sql, $_GET['rutube_sid']);

            if(!$res)
            {
                $res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url, m.ssr_id, m.ssr_name,
                            m.ssr_date, m.ssr_url, m.ssr_description, m.ssr_views, m.ssr_author, m.ssr_duration, m.ssr_size,
                            m.ssr_rutube_id, v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS m_rating, m.ssr_user_id
                            '.$elems_fields_select_sql.'
                            FROM sb_services_rutube m LEFT JOIN sb_vote_results v ON v.vr_el_id=?d AND v.vr_plugin="pl_services_rutube", sb_categs c, sb_catlinks l
                            WHERE m.ssr_id=?d AND l.link_el_id=m.ssr_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
                        	AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
			    	        AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
				            AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
                            '.$elems_fields_where_sql.$user_link_id_sql, $_GET['rutube_sid'], $_GET['rutube_sid']);
            }
    }

    if (!$res)
    {
    	if($linked > 0)
    	{
    	    unset($_GET['rutube_id']);
    		sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_RUTUBE_PLUGIN), SB_MSG_WARNING);
    		return;
    	}
    	else
    	{
			sb_404();
    	}
    }

    $view_rating_form = (sb_strpos($ssrtf_fullelement, '{VOTES_FORM}') !== false && $ssrtf_voting_id > 0);
    $view_comments_list = (sb_strpos($ssrtf_fullelement, '{LIST_COMMENTS}') !== false && $ssrtf_comments_id > 0);
    $view_comments_form = (sb_strpos($ssrtf_fullelement, '{FORM_COMMENTS}') !== false && $ssrtf_comments_id > 0);
    $add_rating = true;
    $add_comments = true;

    $res[0][5] = urlencode($res[0][5]); // CAT_URL
    $res[0][9] = urlencode($res[0][9]); // ELEM_URL

    if ($res[0][4])
    {
        $cat_ids = sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_services_rutube_read');
        if (count($cat_ids) == 0)
        {
            if($linked > 0)
                unset($_GET['rutube_id']);

            $GLOBALS['sbCache']->save('pl_services_rutube', '');
            return;
        }

        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_services_rutube_vote')) == 0)
        {
            $view_rating_form = false;
            $add_rating = false;
        }

        if ($view_comments_list && count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_services_rutube_comments_read')) == 0)
        {
            $view_comments_list = false;
        }

        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_services_rutube_comments_edit')) == 0)
        {
			$add_comments = false;
		}
	}

    $cat_count = '';
    if (sb_substr_count($ssrtf_fullelement, '{CAT_COUNT}') > 0)
    {
        $res_cat = sql_param_query('SELECT COUNT(link_el_id) FROM sb_catlinks
                WHERE link_cat_id=?d AND link_src_cat_id != ?d', $res[0][0], $res[0][0]);
        if ($res_cat)
        {
            $cat_count = $res_cat[0][0];
        }
    }

    $comments_count = array();
    if(sb_strpos($ssrtf_fullelement, '{COUNT_COMMENTS}') !== false)
    {
        require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
        $comments_count = fComments_Get_Count(array($res[0][6]), 'pl_services_rutube');
    }

    $tags = array_merge($tags, array('{CAT_ID}',
                                    '{CAT_URL}',
                                    '{CAT_TITLE}',
                                    '{CAT_COUNT}',
                                    '{CAT_LEVEL}',
                                    '{ID}',
                                    '{ELEM_URL}',
						            '{TITLE}',
						            '{DESCRIPTION}',
						            '{DATE}',
    								'{CHANGE_DATE}',
    								'{EDIT_LINK}',
						            '{VIEWS}',
						            '{AUTHOR}',
						            '{DURATION}',
						            '{SIZE}',
                                    '{MOVIE}',
                                    '{MOVIE_HTML}',
                                    '{USER_DATA}',
    								'{ELEM_USER_LINK}',
    								'{TAGS}',
                                    '{COUNT_COMMENTS}',
                                    '{LIST_COMMENTS}',
                                    '{FORM_COMMENTS}',
    							    '{RATING}',
				    				'{VOTES_COUNT}',
		      						'{VOTES_SUM}',
       								'{VOTES_FORM}',
    								'{ELEM_PREV}',
									'{ELEM_NEXT}'));
    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $values = array();

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
    $dop_values = array($res[0][6], strip_tags($res[0][9]), strip_tags($res[0][7]), $res[0][0], strip_tags($res[0][5]), strip_tags($res[0][1]));

    if ($num_fields > 20)
    {
        for ($i = 20; $i < $num_fields; $i++)
        {
	        $values[] = $res[0][$i];
        }
        $allow_bb = 0;
		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
			$allow_bb = 1;
        $values = sbLayout::parsePluginFields($elems_fields, $values, $ssrtf_fields_temps, $dop_tags, $dop_values, $ssrtf_lang, '', '', $allow_bb, $link_level, $ssrtf_fullelement);
    }

	$res[0][3] = isset($res[0][3]) && $res[0][3] != '' ? unserialize($res[0][3]) : array();
	if ($num_cat_fields > 0)
    {
        $cat_values = array();
        foreach ($categs_sql_fields as $cat_field)
        {
        	if (isset($res[0][3][$cat_field]))
        	{
	        	$cat_values[] = $res[0][3][$cat_field];
        	}
            else
				$cat_values[] = null;
        }
		$allow_bb = 0;
		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
			$allow_bb = 1;
		$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ssrtf_categs_temps, $dop_tags, $dop_values, $ssrtf_lang, '', '', $allow_bb, $link_level, $ssrtf_fullelement);
		$values = array_merge($values, $cat_values);
    }

    $values[] = $res[0][0];         // CAT_ID
    $values[] = $res[0][5];         // CAT_URL
    $values[] = $res[0][1];         // CAT_TITLE
    $values[] = $cat_count;         // CAT_COUNT
    $values[] = $res[0][2] + 1;     // CAT_LEVEL
    $values[] = $res[0][6];         // ID
    $values[] = $res[0][9];         // ELEM_URL
    $values[] = $res[0][7];         // TITLE
	if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
	{
		//Если разрешен bb код
    	$res[0][10] = sbProgParseBBCodes($res[0][10]);
	}
    $values[] = ($res[0][10] != '' && !is_null($res[0][10]) ? str_replace(array_merge(array('{VALUE}'), $dop_tags),
                        array_merge(array($res[0][10]), $dop_values),
                        $ssrtf_fields_temps['ssrtf_description']) : '');          // DESCRIPTION
    $values[] = sb_parse_date($res[0][8], $ssrtf_fields_temps['ssrtf_date'], $ssrtf_lang);      // DATE
 	// Дата последнего изменения
    if(sb_strpos($ssrtf_fullelement, '{CHANGE_DATE}') !== false)
    {
        $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $res[0][6],'pl_services_rutube');
        $values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ssrtf_fields_temps['ssrtf_change_date'], $ssrtf_lang) : ''; //   CHANGE_DATE
    }
    else
    {
        $values[] = '';
    }


    // Ссылка "Редактировать"
    if(!isset($params['edit_page']))
       	$params['edit_page'] = '';

    require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');
	list($edit_page, $edit_ext) = sbGetMorePage($params['edit_page']);

	if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($res[0][19] != $_SESSION['sbAuth']->getUserId())))
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
                $edit_href .= ($res[0][5] != '' ? $res[0][5].'/' : $res[0][0].'/').
                         ($res[0][9] != '' ? $res[0][9] : $res[0][6]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
            }
            else
            {
                $edit_href .= '?rutube_cid='.$res[0][0].'&rutube_id='.$res[0][6];
            }
        }

    	if ($edit_page != '' && isset($ssrtf_fields_temps['ssrtf_edit_link']))
        {
        	$edit = str_replace(array_merge(array('{EDIT_LINK}'), $dop_tags), array_merge(array($edit_href), $dop_values), $ssrtf_fields_temps['ssrtf_edit_link']);
        }
        else
        {
        	$edit = '';
        }
    }
    $values[] = $edit; //EDIT_LINK

    $values[] = ($res[0][11] != '' && !is_null($res[0][11]) ? str_replace(array_merge(array('{VALUE}'), $dop_tags),
                        array_merge(array($res[0][11]), $dop_values),
                        $ssrtf_fields_temps['ssrtf_views']) : 0);          // VIEWS
    $values[] = ($res[0][12] != '' && !is_null($res[0][12]) ? str_replace(array_merge(array('{VALUE}'), $dop_tags),
                       array_merge(array($res[0][12]), $dop_values),
                        $ssrtf_fields_temps['ssrtf_author']) : '');         // AUTHOR

	if($res[0][13] != '' && !is_null($res[0][13]))
	{
		$dur = is_float($res[0][13] / 60) ? explode('.', round(($res[0][13] / 60), 2)) : ($res[0][13] / 60);
		if(is_array($dur))
		{
			if(sb_strlen($dur[0]) == 1)
			{
				$dur[0] = '0'.$dur[0];
			}

			$dur[1] =  round(($dur[1] * 60) / 100, 0);
			$duration = $dur[0].':'.$dur[1];
		}
		else
		{
			if(sb_strlen($dur) == 1)
			{
				$duration = '0'.$dur.':00';
			}
			else
			{
				$duration = $dur.':00';
			}
		}

		$values[] = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($duration), $dop_values),
				$ssrtf_fields_temps['ssrtf_duration']);       // DURATION
	}
	else
	{
		$values[] = '';
	}

	$size = round(($res[0][14]/1024/1024), 2).' Mb';
	if($size < 1)
        $size = round(($res[0][14]/1024), 2).' Kb';

    if($size < 1)
		$size = $res[0][14].' b';

	$values[] = ($size != '' && !is_null($size) ? str_replace(array_merge(array('{VALUE}'), $dop_tags),
						array_merge(array($size), $dop_values),
						$ssrtf_fields_temps['ssrtf_size']) : '');     // SIZE

//	Тег {MOVIE} поддерживаем для сайтов у которых модуль rutube был настроен до добавления сервиса YouTube
	if(sb_strpos($res[0][15], 'youtube.com') !== false)
	{
		$values[] = ''; //   MOVIE
		$values[] = ($res[0][15] != '' && !is_null($res[0][15])? str_replace(array_merge(array('{YOUTUBE_MOVIE_ID}'), $dop_tags), array_merge(array($res[0][15]), $dop_values), isset($ssrtf_fields_temps['ssrt_youtube_movie']) ? $ssrtf_fields_temps['ssrt_youtube_movie'] : '') : '');	//	MOVIE_HTML
	}
	else
	{
		$values[] = $res[0][15];	//	MOVIE
		$values[] = ($res[0][15] != '' && !is_null($res[0][15])? str_replace(array_merge(array('{RUTUBE_MOVIE_ID}'), $dop_tags), array_merge(array($res[0][15]), $dop_values), isset($ssrtf_fields_temps['ssrt_rutube_movie']) ? $ssrtf_fields_temps['ssrt_rutube_movie'] : '') : '');	//	MOVIE_HTML
	}

	if(isset($res[0][19]) && $res[0][19] != '' &&  sb_strpos($ssrtf_fullelement, '{USER_DATA}') !== false)
	{
		require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
        $values[] = fSite_Users_Get_Data($ssrtf_user_data_id, $res[0][19]); //     USER_DATA
	}
	else
	{
		$values[] = '';   //   USER_DATA
    }

	if (isset($res[0][19]) && $res[0][19] > 0 && isset($ssrtf_fields_temps['ssrtf_registred_users']) && $ssrtf_fields_temps['ssrtf_registred_users'] != '')
	{
		$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
		if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
		{
			$auth_page = '/'.$auth_page;
		}

		$action = $auth_page.'?rutube_uid='.$res[0][19].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');
		$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ssrtf_fields_temps['ssrtf_registred_users']);
    }
	else
    {
		$values[] = '';	//	ELEM_USER_LINK
    }

    if(sb_strpos($ssrtf_fullelement, '{TAGS}') !== false)
    {
	    // Вывод тематических тегов
		$tags_error = false;
	    // вытаскиваем макет дизайна тэгов
	    $res_tags = sql_param_query('SELECT ct_pagelist_id, ct_perpage
	                FROM sb_clouds_temps WHERE ct_id=?d', $ssrtf_tags_list_id);

	    if (!$res_tags)
	    	$tags_error = true;

	    list($ct_pagelist_id, $ct_perpage) = $res_tags[0];

	    // Вытаскиваем макет дизайна постраничного вывода
	    $res_tags = sql_param_query('SELECT pt_perstage, pt_begin, pt_next, pt_previous, pt_end, pt_number, pt_sel_number, pt_page_list, pt_delim
	                FROM sb_pager_temps WHERE pt_id=?d', $ct_pagelist_id);
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
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_services_rutube" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_services_rutube"
	                                AND ct.ct_id=cl.cl_tag_id
	                            GROUP BY cl.cl_tag_id', $res[0][6]);

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
			$values[] = fClouds_Show($res_tags, $ssrtf_tags_list_id,  $pt_page_list_tags, $tags_total, $params['page'], 'ssr_tag'); //     TAGS
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

        if (fComments_Add_Comment($ssrtf_comments_id, 'pl_services_rutube', $res[0][6], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
            $c_count++;
    }

    $values[] = $c_count;
    if ($view_comments_list)
    {
        $values[] = fComments_Get_List($ssrtf_comments_id, 'pl_services_rutube', $res[0][6], $add_comments); // LIST_COMMENTS
    }
    else
    {
        $values[] = ''; // LIST_COMMENTS
    }

    if ($view_comments_form)
    {
        $values[] = fComments_Get_Form($ssrtf_comments_id, 'pl_services_rutube', $res[0][6], $add_comments); // FORM_COMMENTS
    }
    else
    {
        $values[] = ''; // FORM_COMMENTS
    }

    $votes_sum = ($res[0][16] != '' && !is_null($res[0][16]) ? $res[0][16] : 0); // VOTES_SUM
    $votes_count = ($res[0][17] != '' && !is_null($res[0][17]) ? $res[0][17] : 0); // VOTES_COUNT
    $votes_rating = ($res[0][18] != '' && !is_null($res[0][18]) ? sprintf('%.2f', $res[0][18]) : 0); // RATING

    if ($add_rating)
    {
	    // VOTES_FORM
	    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
	    $res_vote = fVoting_Form_Submit($ssrtf_voting_id, 'pl_services_rutube', $res[0][6], $votes_sum, $votes_count, $votes_rating);
    }

    $values[] = $votes_rating; // RATING
    $values[] = $votes_count; // VOTES_COUNT
    $values[] = $votes_sum; // VOTES_SUM

    if($add_rating && $view_rating_form)
    {
        $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ssrtf_voting_id, 'pl_services_rutube', $res[0][6]));  // VOTES_FORM
    }
    else
    {
        $values[] = '';
    }

    /* Ссылки вперед и назад */
    if (sb_strpos($ssrtf_fullelement, '{ELEM_PREV}') !== false || sb_strpos($ssrtf_fullelement, '{ELEM_NEXT}') !== false)
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

    	$res_cat_ids = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_pages"
    	        AND cat_left >= (SELECT cat_left FROM sb_categs WHERE cat_ident="pl_pages" AND cat_title=?)
    	        AND cat_right <= (SELECT cat_right FROM sb_categs WHERE cat_ident="pl_pages" AND cat_title=?)',
    	        SB_COOKIE_DOMAIN, SB_COOKIE_DOMAIN);

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

			$res_tmp = sql_param_query('SELECT elem.e_temp_id, elem.e_params
    					FROM sb_elems elem, sb_pages page LEFT JOIN sb_catlinks l ON l.link_el_id = page.p_id
    					WHERE l.link_cat_id IN (?a) AND page.p_filename = ? AND page.p_filepath = ? AND elem.e_p_id = page.p_id
    					AND elem.e_ident = "pl_services_rutube_list" AND elem.e_tag = ? LIMIT 1', $cats_ids,  $im_page, $im_file_path, $e_tag);
		}

		if($res_tmp != false)
		{
			list($e_temp_id, $e_params) = $res_tmp[0];

			$next_prev_result = array();

			fServices_Rutube_Get_Next_Prev($e_temp_id, $e_params, $next_prev_result, $_SERVER['PHP_SELF'], $res[0][6]);

			$href_prev = $next_prev_result['href_prev'];
			$href_next = $next_prev_result['href_next'];
			$title_prev = $next_prev_result['title_prev'];
			$title_next = $next_prev_result['title_next'];
		}

		if($href_prev != '' && isset($ssrtf_fields_temps['ssrtf_prev_link']))
		{
			$href_prev = str_replace(array_merge(array('{PREV_HREF}', '{PREV_TITLE}'), $dop_tags), array_merge(array($href_prev, $title_prev), $dop_values), $ssrtf_fields_temps['ssrtf_prev_link']);
		}

		if($href_next != '' && isset($ssrtf_fields_temps['ssrtf_next_link']))
		{
			$href_next = str_replace(array_merge(array('{NEXT_HREF}', '{NEXT_TITLE}'), $dop_tags), array_merge(array($href_next, $title_next), $dop_values), $ssrtf_fields_temps['ssrtf_next_link']);
		}

		$values[] = $href_prev; // PREV
		$values[] = $href_next;  // NEXT
    }
    else
    {
    	$values[] = ''; // PREV
    	$values[] = '';  // NEXT
    }

    /*******************************************************************************/

	$ip = sbAuth::getIP();
    $robot = sbIsSearchRobot();
    if (!$robot)
    {
    	if(isset($_GET['rutube_id']))
        {
	        if($ip)
	        {
	            sql_param_query('UPDATE sb_services_rutube SET ssr_views = ssr_views + 1 WHERE ssr_id = "'.$_GET['rutube_id'].'"');
	        }
        }
        else
        {
        	if($ip)
            {
                sql_param_query('UPDATE sb_services_rutube SET ssr_views = ssr_views + 1 WHERE ssr_url = "'.$_GET['rutube_sid'].'"');
                if(sql_num_rows() == 0)
                {
                 	sql_param_query('UPDATE sb_services_rutube SET ssr_views = ssr_views + 1 WHERE ssr_id = "'.$_GET['rutube_sid'].'"');
                }
            }
        }
    }

    $result = str_replace($tags, $values, $ssrtf_fullelement);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    if($linked < 1)
    {
    	$GLOBALS['sbCache']->save('pl_services_rutube', $result, $res[0][6]);

    	@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
    	fClouds_Init_Tags('pl_services_rutube', array($res[0][6]));
    }
    else
    {
         unset($_GET['rutube_id']);
    	 return $result;
    }
}

/**
 * Вывод списка разделов
 *
 */
function fServices_Rutube_Categs($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    $num_sub = 0;
    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_services_rutube', 'pl_services_rutube', 'rutube', $num_sub);
}

/**
 * Вывод выбранного раздела
 *
 */
function fServices_Rutube_Sel_Categ($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_services_rutube', 'pl_services_rutube', 'rutube');
}

/**
 * Вывод формы добавления роликов
 *
 */
function fServices_Rutube_Form($el_id, $temp_id, $params, $tag_id)
{
    if (!isset($_FILES['ssr_file_path']) || $_FILES['ssr_file_path']['tmp_name'] == '')
    {
        // просто вывод формы, данные пока не пришли
        if ($GLOBALS['sbCache']->check('pl_services_rutube', $tag_id, array($el_id, $temp_id, $params)))
            return;
    }

    //$res = sql_param_query('SELECT ssrtf_lang, ssrtf_form, ssrtf_fields_temps, ssrtf_categs_temps, ssrtf_messages
    //                        FROM sb_services_rutube_temps_form WHERE ssrtf_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_services_rutube_temps_form', $temp_id);
    if(!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_SITE_USERS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }

    list($ssrtf_lang, $ssrtf_form, $ssrtf_fields_temps, $ssrtf_categs_temps, $ssrtf_messages) = $res[0];

    $params = unserialize(stripslashes($params));
    $ssrtf_fields_temps = unserialize($ssrtf_fields_temps);
    $ssrtf_categs_temps = unserialize($ssrtf_categs_temps);
    $ssrtf_messages = unserialize($ssrtf_messages);

    $result = '';

    // ID и ID раздела
    $edit_id = -1;
	$now_cat = -1;
	if(isset($params['edit']) && $params['edit'] == '1')
	{
		//Если редактирование - узнаю id элемента
		if(isset($_GET['rutube_id']))
		{
			$edit_id = intval($_GET['rutube_id']);
		}
		elseif(isset($_GET['rutube_sid']))
		{
			$res = sql_query('SELECT ssr_id FROM sb_services_rutube WHERE ssr_url = ?', $_GET['rutube_sid']);
			if ($res)
			{
				$edit_id = $res[0][0];
			}
			else
			{
				$res = sql_query('SELECT ssr_id FROM sb_services_rutube WHERE ssr_id = ?', $_GET['rutube_sid']);
				if ($res)
				{
					$edit_id = $res[0][0];
				}
			}
		}

		//Если редактирование - узнаю id текущего раздела
		if(isset($_GET['rutube_cid']))
		{
			$now_cat = intval($_GET['rutube_cid']);
		}
		elseif(isset($_GET['rutube_scid']))
		{
			$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url = ? AND cat_ident="pl_services_rutube"', $_GET['rutube_scid']);
			if ($res)
			{
				$now_cat = $res[0][0];
			}
			else
			{
				$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id = ?', $_GET['rutube_scid']);
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

	//Получаю поля ролика
	if($edit_id > 0)
	{
		$element_fields = sql_query('SELECT ssr_name, ssr_date, ssr_url, ssr_rutube_id, ssr_author, ssr_description, ssr_user_id FROM sb_services_rutube WHERE ssr_id = ?d', $edit_id);

		$res = sql_query('SELECT t.ct_tag FROM sb_clouds_links l, sb_clouds_tags t WHERE l.cl_ident="pl_services_rutube" AND l.cl_el_id = ?d AND t.ct_id = l.cl_tag_id', $edit_id);

		$element_fields_tags = '';
		if($res && count($res) > 0)
		{
			foreach($res as $value)
			{
				$element_fields_tags .= ', '.$value[0];
			}
			$element_fields_tags = sb_substr($element_fields_tags, 2);
		}
	}
	if ((!isset($_FILES['ssr_file_path']) || $_FILES['ssr_file_path']['tmp_name'] == '') && trim($ssrtf_form) == '' && $edit_id < 1)
    {
        // вывод формы
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }
	elseif(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($element_fields[0][6] != $_SESSION['sbAuth']->getUserId())))
    {
        //Можно редактировать только свои ролики
        $GLOBALS['sbCache']->save('pl_services_rutube', $ssrtf_messages['err_not_auth_edit']);
        return;
    }
    elseif(isset($params['registred_users_add_only']) && $params['registred_users_add_only'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
    {
        //Только зарегистрированный пользователь может добавлять новость
        $GLOBALS['sbCache']->save('pl_services_rutube', $ssrtf_messages['err_not_auth']);
        return;
    }

    // Определение инпутов
    $ssr_movie_name = '';
    $ssr_date = '';
    $ssr_url = '';
    $ssr_rutube_id = '';
    $ssr_author = '';
    $ssr_description = '';
    $ssr_category = '';
    $ssr_tags = '';

    if(isset($_POST['ssr_movie_name'])) // Название
		$ssr_movie_name =  $_POST['ssr_movie_name'];
	elseif($edit_id > 0 && isset($element_fields[0][0]))
		$ssr_movie_name = $element_fields[0][0];


	if(isset($_POST['ssr_date'])) // Дата
		$ssr_date =  $_POST['ssr_date'];
	elseif($edit_id > 0 && isset($element_fields[0][1]))
		$ssr_date = sb_parse_date($element_fields[0][1], $ssrtf_fields_temps['date_temps']);

	if(isset($_POST['ssr_url'])) // УРЛ
		$ssr_url =  $_POST['ssr_url'];
	elseif($edit_id > 0 && isset($element_fields[0][2]))
		$ssr_url = $element_fields[0][2];

	if($edit_id > 0 && isset($element_fields[0][3])) // Текущий ролик
		$ssr_rutube_id = $element_fields[0][3];

	if(isset($_POST['ssr_author'])) // Автор
		$ssr_author =  $_POST['ssr_author'];
	elseif($edit_id > 0 && isset($element_fields[0][4]))
		$ssr_author = $element_fields[0][4];

	if(isset($_POST['ssr_description'])) // Описание
		$ssr_description =  $_POST['ssr_description'];
	elseif($edit_id > 0 && isset($element_fields[0][5]))
		$ssr_description = $element_fields[0][5];

	if(isset($_POST['ssr_tags'])) // Тэги
		$ssr_tags = $_POST['ssr_tags'];
	elseif($edit_id > 0 && $element_fields_tags != '')
		$ssr_tags = $element_fields_tags;

	if(isset($_POST['ssr_category'])) // Разделы на ютюб
		$ssr_category = $_POST['ssr_category'];
	elseif($edit_id > 0 && $params['service'] == 'youtube')
	{
		$ch = curl_init();
		$moovie_id = array();
		preg_match('/http:\/\/www.youtube.com\/v\/(.*)\?[.]*/im'.SB_PREG_MOD,$ssr_rutube_id, $moovie_id);
		if(isset($moovie_id[1]))
		{
			curl_setopt($ch, CURLOPT_URL, 'http://gdata.youtube.com/feeds/api/videos/'.$moovie_id[1]);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Language:ru'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			$xml_now_cat = curl_exec($ch);
			curl_close($ch);
			$xml_now_cat = simplexml_load_string($xml_now_cat);
			foreach ($xml_now_cat->category as $v)
			{
				if(isset($v->attributes()->scheme) && (string)$v->attributes()->scheme == 'http://gdata.youtube.com/schemas/2007/categories.cat')
				{
					$ssr_category = (string)$v->attributes()->term;
					break;
				}
			}
		}
	}

	$ssr_file_path = isset($_POST['ssr_file_path']) ? $_POST['ssr_file_path'] : ''; // Путь к файлу
	$ssr_show_rutube = isset($_POST['ssr_show_rutube']) ? $_POST['ssr_show_rutube'] : ''; // Публиковать на ruTube

    //Разделы
    $ssr_categ = array();
    if (isset($_POST['ssr_categ']))
    {
	    if (is_array($_POST['ssr_categ']))
    	{
    		$ssr_categ = $_POST['ssr_categ'];
    	}
    	else
    	{
        	$ssr_categ[] = intval($_POST['ssr_categ']);
    	}
    }
	elseif($edit_id > 0)
	{
		$ssr_categ = null;
	}
 	elseif (isset($params['rubrikator_form']) && $params['rubrikator_form'] == 1)
    {
    	if (isset($_GET['rutube_cid']))
	    {
	        $ssr_categ[] = intval($_GET['rutube_cid']);
	    }
	    elseif (isset($_GET['rutube_scid']))
	    {
			$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_services_rutube"', $_GET['rutube_scid']);
			if ($res)
			{
				$ssr_categ[] = $res[0][0];
	        }
	        else
	        {
	        	$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id=?d', $_GET['rutube_scid']);
				if ($res)
		        {
					$ssr_categ[] = $res[0][0];
		        }
	        }
	    }
    }

	$tags = array();
	$values = array();

	$fields_message = '';
	$message = '';

    if (isset($_POST['pl_plugin_ident']) && $_POST['pl_plugin_ident'] == 'pl_services_rutube')
    {
    	// проверка данных и сохранение
        $message_tags = array('{DATE}', '{MOVIE_NAME}', '{AUTHOR}');
        $message_values = array(sb_parse_date(time(), $ssrtf_fields_temps['m_date_val'], $ssrtf_lang), $ssr_movie_name, (isset($_FILES['ssr_file_path']['size']) ? $_FILES['ssr_file_path']['size'] : ''));

        $error = false;

        if(isset($ssrtf_fields_temps['author_need']) && $ssrtf_fields_temps['author_need'] == 1 && $ssr_author == '')
        {
            $error = true;

            $tags = array_merge($tags, array('{F_AUTHOR_SELECT_START}', '{F_AUTHOR_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

    	if(isset($ssrtf_fields_temps['date_need']) && $ssrtf_fields_temps['date_need'] == 1 && $ssr_date == '')
        {
            $error = true;

            $tags = array_merge($tags, array('{F_DATE_SELECT_START}', '{F_DATE_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

        if($ssr_movie_name == '')
        {
            $error = true;

            $tags = array_merge($tags, array('{F_MOVIE_NAME_SELECT_START}', '{F_MOVIE_NAME_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

		if ($ssr_movie_name != '' && sb_strlen($ssr_movie_name) > 60 && isset($params['service']) && $params['service'] == 'youtube')
        {
			$error = true;

			$tags = array_merge($tags, array('{F_MOVIE_NAME_SELECT_START}', '{F_MOVIE_NAME_SELECT_END}'));
			$values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

			$message .= isset($ssrtf_messages['err_name_length']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_name_length']) : '';
		}

		if ($ssr_tags == '' && $edit_id < 1)
        {
			$error = true;

			$tags = array_merge($tags, array('{F_TAGS_SELECT_START}', '{F_TAGS_SELECT_END}'));
			$values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

			$fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

		if($ssr_tags != '' && sb_strlen($ssr_tags) > 120 && isset($params['service']) && $params['service'] == 'youtube')
		{
			$error = true;

			$tags = array_merge($tags, array('{F_TAGS_SELECT_START}', '{F_TAGS_SELECT_END}'));
			$values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

			$message .= isset($ssrtf_messages['err_tags_length']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_tags_length']) : '';
        }

        if (isset($ssrtf_fields_temps['categories_need']) && $ssrtf_fields_temps['categories_need'] == 1 && $ssr_category == '')
        {
            $error = true;

            $tags = array_merge($tags, array('{F_CATEGORIES_SELECT_START}', '{F_CATEGORIES_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

        if (isset($ssrtf_fields_temps['categ_need']) && $ssrtf_fields_temps['categ_need'] == 1 && (count($ssr_categ) == 0 || isset($ssr_categ[0]) && $ssr_categ[0] == ''))
        {
            $error = true;

            $tags = array_merge($tags, array('{F_CATEGS_LIST_SELECT_START}', '{F_CATEGS_LIST_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

        if (isset($ssrtf_fields_temps['description_need']) && $ssrtf_fields_temps['description_need'] == 1 && $ssr_description == '')
        {
            $error = true;
            $tags = array_merge($tags, array('{F_DESCRIPTION_SELECT_START}', '{F_DESCRIPTION_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

		if(sb_strlen($ssr_description) > 5000 && isset($params['service']) && $params['service'] == 'youtube')
        {
			$error = true;

			$tags = array_merge($tags, array('{F_DESCRIPTION_SELECT_START}', '{F_DESCRIPTION_SELECT_END}'));
			$values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

			$message .= isset($ssrtf_messages['err_desrc_length']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_desrc_length']) : '';
    	}

        if (isset($_FILES['ssr_file_path']['tmp_name']) && $_FILES['ssr_file_path']['tmp_name'] == '' && $edit_id < 1)
        {
			$error = true;

			$tags = array_merge($tags, array('{F_FILE_SELECT_START}', '{F_FILE_SELECT_END}'));
			$values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

			$fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
        }

		$max_f_size = sbPlugins::getSetting('sb_files_max_upload_size');

        if(isset($_FILES['ssr_file_path']['size']) && $_FILES['ssr_file_path']['size'] > $max_f_size)
        {
            $error = true;

            $tags = array_merge($tags, array('{F_FILE_SELECT_START}', '{F_FILE_SELECT_END}'));
            $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

            $message .= (isset($ssrtf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['ssr_file_path']) ? $_FILES['ssr_file_path']['name'] : ''), $max_f_size)), $ssrtf_messages['err_size_too_large']) : '');
        }

		$cat_ids = array();
		if((count($ssr_categ) == 0 || isset($ssr_categ[0]) && $ssr_categ[0] == ''))
        {
			$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', explode('^', $params['ids']));
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', $ssr_categ);
        }

    	if ($res)
        {
            foreach ($res as $val)
            {
                list($cat_id) = $val;
                $cat_ids[] = $cat_id;
            }
        }

        if(count($cat_ids) > 0)
        {
			// проверка прав на добавления ролика
			$closed_cats = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);

	        $close_ids = array();
	        if($closed_cats)
	        {
				foreach($closed_cats as $key => $value)
		        {
					$close_ids[] = $value;
				}
				$cat_ids = sbAuth::checkRights($close_ids, $cat_ids, 'pl_services_rutube_edit');

				if(count($cat_ids) < 1)
	            {
					$error = true;
					$message .= isset($ssrtf_messages['err_not_have_rights_add']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_not_have_rights_add']) : '' ;
	            }
	        }
    	}
    	else
    	{
			$cat_ids = null;
    	}

        // проверяем код каптчи
        if (sb_strpos($ssrtf_form, '{CAPTCHA}') !== false || sb_strpos($ssrtf_form, '{CAPTCHA_IMG}') !== false)
        {
            if(!sbProgCheckTuring('ssr_captcha', 'captcha_hash'))
            {
                $error = true;

                $tags = array_merge($tags, array('{F_CAPTCHA_SELECT_START}', '{F_CAPTCHA_SELECT_END}'));
                $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));

                $message .= isset($ssrtf_messages['err_captcha_code']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_captcha_code']) : '' ;
            }
        }

		require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		$users_error = false;
		$layout = new sbLayout();
		$row = $layout->checkPluginInputFields('pl_services_rutube', $users_error, $ssrtf_fields_temps, $edit_id, 'sb_services_rutube', 'ssr_id', false, $ssrtf_fields_temps['date_temps']);

		if ($users_error)
        {
            foreach ($row as $f_name => $f_array)
            {
                $f_error = $f_array['error'];
                $f_tag = $f_array['tag'];

                $tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
                $values = array_merge($values, array($ssrtf_fields_temps['select_start'], $ssrtf_fields_temps['select_end']));
                switch($f_error)
                {
                    case 2:
                        $message .= isset($ssrtf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $ssrtf_messages['err_save_file']) : '';
                        break;

                    case 3:
                        $message .= isset($ssrtf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_TYPE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $ssrtf_messages['err_type_file']) : '';
                        break;

                    case 4:
                        $message .= isset($ssrtf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $ssrtf_messages['err_size_too_large']) : '';
                        break;

                    case 5:
                        $message .= isset($ssrtf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $ssrtf_messages['err_img_size']) : '';
                        break;

                    default:
                        $fields_message = isset($ssrtf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_necessary_field']) : '';
                        break;
                }
            }
        }

        if(isset($params['service']) && $params['service'] == 'youtube' && $ssr_description == '' && (!isset($ssrtf_fields_temps['description_need']) || $ssrtf_fields_temps['description_need'] != 1 ))
		{
			$_POST['ssr_description'] = $ssr_movie_name;
		}

		$error = $error || $users_error;
        if ($error)
        {
			$layout->deletePluginFieldsFiles();
        }
        elseif($edit_id > 0)
        {
        	//Добавление/редактирвоание даты
	        if(!isset($_POST['ssr_date']) || trim($_POST['ssr_date']) == '')
	        {
	        	if(isset($row['ssr_date']))
	        		unset($row['ssr_date']);
	        }
	        else
	        {
	        	$ssr_date = sb_datetoint($_POST['ssr_date'], $ssrtf_fields_temps['date_temps']);
	        	if($ssr_date != null)
	        		$row['ssr_date'] = $ssr_date;
	        }

		    //Добавление/редактирование ЧПУ
	        if(isset($_POST['ssr_url']) && isset($element_fields[0][2]) && $element_fields[0][2] == $_POST['ssr_url'])
	        {
	        	//Если новый чпу совпадает со старым - оставляем в базе как есть
	        	if(isset($row['ssr_url']))
	        		unset($row['ssr_url']);
	        }
	        else
	        {
	        	// Если пришел урл
	        	if($ssr_movie_name == '' && $_POST['ssr_url'] == '')
	        	{
	        		// но урл и тайтл пустые - оставляем как есть
	        		if(isset($row['ssr_url']))
	        			unset ($row['ssr_url']);
	        	}
	        	else
	        	{
	        		// Если есть либо урл, либо тайтл, либо оба - генерируем(проверяем) урл
		        	if($edit_id > 0)
		        	{
		        		$row['ssr_url'] = sb_check_chpu($edit_id, (isset($_POST['ssr_url']) ? $_POST['ssr_url'] : ''), $ssr_movie_name, ' sb_services_rutube', 'ssr_url', 'ssr_id');
		        	}
		        	else
		        	{
		        		$row['ssr_url'] = sb_check_chpu('', (isset($_POST['ssr_url']) ? $_POST['ssr_url'] : ''), $ssr_movie_name, ' sb_services_rutube', 'ssr_url', 'ssr_id');
		        	}
	        	}
	        }
			$row['ssr_name'] = $ssr_movie_name;
			$row['ssr_author'] = $ssr_author;
			$row['ssr_description'] = $ssr_description;

	        // Записываю в базу
	        $m_id = sbProgAddElement('sb_services_rutube', 'ssr_id', $row, $ssr_categ, $edit_id, $now_cat);
	        if(!$m_id)
		    {
		    	 $error = true;
		    	 $message .= isset($ssrtf_messages['err_edit_movie']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_edit_movie']) : '';
		    }
		    else
		    {
             	include_once(SB_CMS_PL_PATH.'/pl_clouds/pl_clouds.inc.php');
             	require_once SB_CMS_LIB_PATH.'/prog/sbServicesRutube.inc.php';
                fClouds_Set_Field($edit_id, 'pl_services_rutube', $ssr_tags);
                if(isset($params['service']) && $params['service'] == 'youtube')
                {
                	// Обновляем инфу на youtube
                    if(!sbEditYouTubeMovies($edit_id, $row, $ssr_category, $ssr_tags))
	                {
	                	$error = true;
                		$message .= isset($ssrtf_messages['err_add_movie']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_movie']) : '';
	                }
                }
		    }
		}
        elseif(isset($_FILES['ssr_file_path']['tmp_name']) && $_FILES['ssr_file_path']['tmp_name'] != '')
        {
			require_once SB_CMS_LIB_PATH.'/prog/sbServicesRutube.inc.php';
			$premod_movies = isset($params['premod_movies']) ? $params['premod_movies'] : '';

            if(isset($params['service']) && $params['service'] == 'youtube')
            {
				$m_id = sbAddYouTubeMovies($row, $cat_ids, $premod_movies);
            }
			else
            {
				$m_id = sbAddMovies($row, $cat_ids, $premod_movies);
			}

            if (!$m_id)
            {
                $error = true;
                $message .= isset($ssrtf_messages['err_add_movie']) ? str_replace($message_tags, $message_values, $ssrtf_messages['err_add_movie']) : '';
            }
        }

        if (!$error)
        {
            require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
            $mail = new sbMail();

            $type = sbPlugins::getSetting('sb_letters_type');

			$mod_emails = array();

			$mod_params_emails = explode(' ', trim(str_replace(',', ' ', $params['mod_emails'])));
			$mod_categs_emails = array();
			$mod_users_emails = array();

			$res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
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

			// отправляем письма и делаем переадресацию
            if (count($mod_emails) > 0)
            {
                if($edit_id > 0)
                	$email_subj = fRutube_Parse($ssrtf_messages['admin_subj_edit'], $ssrtf_fields_temps, $m_id, $ssrtf_lang, '', '_val', $ssrtf_categs_temps);
                else
            		$email_subj = fRutube_Parse($ssrtf_messages['admin_subj'], $ssrtf_fields_temps, $m_id, $ssrtf_lang, '', '_val', $ssrtf_categs_temps);

                //чистим код от инъекций
                $email_subj = sb_clean_string($email_subj);

                ob_start();
                eval(' ?>'.$email_subj.'<?php ');
                $email_subj = trim(ob_get_clean());

                if($edit_id > 0)
                	$email_text = fRutube_Parse($ssrtf_messages['admin_text_edit'], $ssrtf_fields_temps, $m_id, $ssrtf_lang, '', '_val', $ssrtf_categs_temps);
                else
                	$email_text = fRutube_Parse($ssrtf_messages['admin_text'], $ssrtf_fields_temps, $m_id, $ssrtf_lang, '', '_val', $ssrtf_categs_temps);

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
	                $mail->send($mod_emails, false);
                }
            }

            if (isset($params['page']) && trim($params['page']) != '')
            {
                header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'mov_id='.$m_id));
            }
            else
            {
            	//Если изменяем ЧПУ, то перекидываем пользователя на адрес с новым чпу
				$php_self = $GLOBALS['PHP_SELF'];
				if(isset($row['ssr_url']) && isset($_GET['rutube_sid']) && $row['ssr_url'] != $_GET['rutube_sid'])
				{
					$php_self = str_replace($_GET['rutube_sid'],$row['ssr_url'],$php_self);
				}
                header('Location: '.sb_sanitize_header($php_self.($_SERVER['QUERY_STRING'] != '' ?  '?'.$_SERVER['QUERY_STRING'].'&mov_id='.$m_id : '?mov_id='.$m_id)));
            }
            exit (0);
        }
        else
        {
            $layout->deletePluginFieldsFiles();
        }
    }

    if(isset($_GET['mov_id']))
    {
    	if(isset($params['edit']) && $params['edit'] == '1')
        	$message .= fRutube_Parse($ssrtf_messages['success_edit_movie'], $ssrtf_fields_temps, $_GET['mov_id'], $ssrtf_lang, '', '_val', $ssrtf_categs_temps);
    	else
    		$message .= fRutube_Parse($ssrtf_messages['success_add_movie'], $ssrtf_fields_temps, $_GET['mov_id'], $ssrtf_lang, '', '_val', $ssrtf_categs_temps);
    }
    $message .= $fields_message;

    $tags = array_merge($tags, array('{MESSAGES}',
                '{ACTION}',
                '{AUTHOR}',
                '{MOVIE_NAME}',
    			'{DATE}',
    			'{URL}',
    			'{MOVIE_HTML_NOW}',
                '{FILE}',
                '{CATEGORIES}',
                '{SHOW_RUTUBE}',
                '{DESCRIPTION}',
                '{CAPTCHA}',
                '{CAPTCHA_IMG}',
                '{TAGS}',
                '{CATEGS_LIST}'));
	$values[] = $message;

	$edit_param = '';
	if (isset($params['edit']) && $params['edit'] == 1 && isset($_GET['rutube_id']) && $_GET['rutube_id'] != '' &&
		isset($_GET['rutube_cid']) && $_GET['rutube_cid'] != '')
	{
		$edit_param = 'rutube_cid='.$_GET['rutube_cid'].'&rutube_id='.$_GET['rutube_id'];
	}

	$get_str = trim(preg_replace('/mov_id=[0-9]+/i'.SB_PREG_MOD, '', $_SERVER['QUERY_STRING']), '&');
	$values[] = $GLOBALS['PHP_SELF'].($get_str != '' ? '?'.$get_str.'&'.$edit_param : '?'.$edit_param);

	if(isset($_SESSION['sbAuth']))
	{
		$res = sql_param_query('SELECT su_login, su_name FROM sb_site_users WHERE su_id=?d', $_SESSION['sbAuth']->getUserId());
		if($res)
		{
			list($su_login, $su_name) = $res[0];
			if(!isset($_POST['ssr_author']) && $su_name != '')
				$ssr_author = $su_name;

			elseif(!isset($_POST['ssr_author']) && $su_login != '')
				$ssr_author = $su_login;
		}
	}

    $values[] = (isset($ssrtf_fields_temps['author']) && trim($ssrtf_fields_temps['author']) != '' ? str_replace('{VALUE}', $ssr_author, $ssrtf_fields_temps['author']) : '');
    $values[] = (isset($ssrtf_fields_temps['movie_name']) && trim($ssrtf_fields_temps['movie_name']) != '' ? str_replace('{VALUE}', $ssr_movie_name, $ssrtf_fields_temps['movie_name']) : '');
    // Дата
	$values[] = (isset($ssrtf_fields_temps['date']) && $ssrtf_fields_temps['date'] != '') ? str_replace('{VALUE}', ($ssr_date != '' ? $ssr_date : ''), $ssrtf_fields_temps['date']) : '';
	// ЧПУ
	$values[] = (isset($ssrtf_fields_temps['url']) && $ssrtf_fields_temps['url'] != '') ? str_replace('{VALUE}', $ssr_url, $ssrtf_fields_temps['url']) : '';
	// Текущее изображение
	if(sb_strpos($ssr_rutube_id, 'youtube.com') !== false)
	{
		$values[] = ($ssr_rutube_id != '' && !is_null($ssr_rutube_id) ? str_replace('{YOUTUBE_MOVIE_ID}', $ssr_rutube_id, (isset($ssrtf_fields_temps['ssrtf_youtube_movie']) ? $ssrtf_fields_temps['ssrtf_youtube_movie'] : '')) : '');	//	MOVIE_HTML
	}
	else
	{
		$values[] = ($ssr_rutube_id != '' && !is_null($ssr_rutube_id)? str_replace('{RUTUBE_MOVIE_ID}', $ssr_rutube_id, (isset($ssrtf_fields_temps['ssrtf_rutube_movie']) ? $ssrtf_fields_temps['ssrtf_rutube_movie'] : '')) : '');	//	MOVIE_HTML
	}

    $values[] = (isset($ssrtf_fields_temps['file_name']) && trim($ssrtf_fields_temps['file_name']) != '' ? str_replace('{VALUE}', $ssr_file_path, $ssrtf_fields_temps['file_name']) : '');

	$xml = '';
	if(isset($params['service']) && $params['service'] == 'youtube')
    {
		$xml_str = '';
		if(function_exists('curl_init'))
		{
			// Список разделов
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'http://gdata.youtube.com/schemas/2007/categories.cat');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Language:ru'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

			$xml_str = curl_exec($ch);
			curl_close($ch);
		}

		if ($xml_str)
		{
			$xml = simplexml_load_string(preg_replace('/<(\/)?([a-z0-9]+):([a-z0-9]+)/i', '<$1$2-$3', $xml_str));
		}
    }
	else
    {
	    $xml = @simplexml_load_file('http://rutube.ru/cgi-bin/xmlapi.cgi?rt_mode=categories');
    }
    if($xml)
    {
		if(isset($params['service']) && $params['service'] == 'youtube')
	    {
			$option = '';
			$browsable_cats = $xml->xpath('/app-categories/atom-category[yt-browsable]');
			foreach($browsable_cats as $key => $value)
			{
				$label = SB_CHARSET != 'UTF-8' ? iconv('UTF-8', SB_CHARSET.'//IGNORE', (string) $value->attributes()->label) : (string) $value->attributes()->label;
				$term = (string) $value->attributes()->term;
				$option .= '<option value=\''.$term.'\' '.($ssr_category != '' && $term != '' && $ssr_category == $term ? 'selected="selected"' : '' ).'>'.$label.'</option>';
			}
		}
		else
	    {
			$count = count($xml->categories[0]);
			$option = '';

			for($i = 0; $i < $count; $i++)
	        {
				$name = SB_CHARSET != 'UTF-8' ? iconv('UTF-8', SB_CHARSET.'//IGNORE', $xml->categories[0]->category[$i]) : $xml->categories[0]->category[$i];
				$key = (string) $xml->categories[0]->category[$i]['id'];
				$option .= '<option value=\''.$key.'\' '.($ssr_category != '' && $key != '' && $ssr_category == $key ? 'selected="selected"' : '' ) .'>'.$name.'</option>';
	        }
	    }

        $values[] = (isset($ssrtf_fields_temps['categories']) && trim($ssrtf_fields_temps['categories']) != '' ? str_replace('{VALUE}', $option, $ssrtf_fields_temps['categories']) : '');
    }
    else
    {
        $values[] = (isset($ssrtf_messages['err_no_categories']) ? $ssrtf_messages['err_no_categories'] : '');
    }
    $values[] = (isset($ssrtf_fields_temps['show_rutube']) && trim($ssrtf_fields_temps['show_rutube']) != '' ? str_replace('{VALUE}', ($ssr_show_rutube == 1 ? 'checked="checked"' : '' ), $ssrtf_fields_temps['show_rutube']) : '');
    $values[] = (isset($ssrtf_fields_temps['description']) && trim($ssrtf_fields_temps['description']) != '' ? str_replace('{VALUE}', $ssr_description, $ssrtf_fields_temps['description']) : '');

    // Вывод КАПЧИ
    if ((sb_strpos($ssrtf_form, '{CAPTCHA}') !== false || sb_strpos($ssrtf_form, '{CAPTCHA_IMG}') !== false) &&
    	isset($ssrtf_fields_temps['captcha']) && trim($ssrtf_fields_temps['captcha']) != '' &&
        isset($ssrtf_fields_temps['captcha_img']) && trim($ssrtf_fields_temps['captcha_img']) != '')
    {
        $turing = sbProgGetTuring();
        if ($turing)
        {
            $values[] = $ssrtf_fields_temps['captcha'];
            $values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $ssrtf_fields_temps['captcha_img']);
        }
        else
        {
            $values[] = $ssrtf_fields_temps['captcha'];
            $values[] = '';
        }
    }
    else
    {
		$values[] = '';
		$values[] = '';
	}

	$values[] = (isset($ssrtf_fields_temps['theme_tags']) && trim($ssrtf_fields_temps['theme_tags']) != '' ? str_replace('{VALUE}', $ssr_tags, $ssrtf_fields_temps['theme_tags']) : '');

	// Разделы
	if (sb_strpos($ssrtf_form, '{CATEGS_LIST}') !== false)
	{
		$cat_ids = explode('^', $params['ids']);

		if (!is_array($ssr_categ) && $edit_id > 0)
		{
			$res = sql_query('SELECT c.cat_id FROM sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND l.link_el_id = ?d AND l.link_cat_id = c.cat_id', 'pl_services_rutube', $edit_id);
			if ($res)
			{
				$ssr_categ = array();
				foreach ($res as $value)
				{
					$ssr_categ[] = $value[0];
				}
			}
		}

		$values[] = sbProgGetCategsList($cat_ids, 'pl_services_rutube', $ssr_categ, $ssrtf_fields_temps['categs_list_options'], $ssrtf_fields_temps['categs_list'], 'pl_services_rutube_edit');
	}
	else
	{
		$values[] = '';  //   CATEGS_LIST
	}

	@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	sbLayout::parsePluginInputFields('pl_services_rutube', $ssrtf_fields_temps, $ssrtf_fields_temps['date_temps'], $tags, $values, $edit_id, 'sb_services_rutube', 'ssr_id');
	$result = str_replace($tags, $values, $ssrtf_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    if (!isset($_FILES['ssr_file_path']) || $_FILES['ssr_file_path']['tmp_name'] == '')
    {
        $GLOBALS['sbCache']->save('pl_services_rutube', $result);
    }
    else
    {
    	//чистим код от инъекций
        $result = sb_clean_string($result);

		eval(' ?>'.$result.'<?php ');
    }
}

/**
 *
 * Вывод информации о вопросе
 *
 * @param string $temp Макет дизайна.
 * @param array $field_temps Макеты дизайна полей.
 * @param int $id Идентификатор ролика.
 * @param string $lang Язык макета дизайна.
 * @param string $prefix Префикс имени поля в макете дизайна полей.
 * @param string $sufix Суффикс имени поля в макете дизайна полей.
 *
 * @return string Отпарсенный макет дизайна вывода информации о ролике.
 */
function fRutube_Parse($temp, &$fields_temps, $id, $lang = 'ru', $prefix = '', $sufix = '', $categs_temps = '')
{
    if (trim($temp) == '')
        return '';

    // вытаскиваем пользовательские поля
    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_services_rutube"');

    $users_fields = array();
    $categs_sql_fields = array();
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
                    $users_fields_select_sql .= ', user_f_'.$value['id'];

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

    if ($res && $res[0][1] != '')
    {
        $categs_fields = unserialize($res[0][1]);
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

	$res = sql_param_query('SELECT r.ssr_name, r.ssr_description, r.ssr_date, r.ssr_author, r.ssr_duration, r.ssr_url, r.ssr_size, c.cat_title, c.cat_id, c.cat_url
                            '.$users_fields_select_sql.'
                            FROM sb_services_rutube r, sb_categs c, sb_catlinks l
							WHERE l.link_el_id = r.ssr_id AND l.link_cat_id = c.cat_id AND c.cat_ident="pl_services_rutube"
							AND l.link_src_cat_id=0 AND r.ssr_id=?d', $id);
	if (!$res)
	{
		return '';
	}

	list ($ssr_name, $ssr_description, $ssr_date, $ssr_author, $ssr_duration, $ssr_url, $ssr_size, $cat_title, $cat_id, $cat_url) = $res[0];
	$tags = array_merge(array('{ID}',
                              '{DATE}',
							  '{AUTHOR}',
							  '{MOVIE_NAME}',
                              '{DURATION}',
                              '{FILE_SIZE}',
							  '{DESCRIPTION}',
							  '{LINK}',
                              '{CAT_TITLE}',
                              '{CAT_ID}',
                              '{CAT_URL}'), $tags);

	$values = array();
    $values[] = $id;     // ID
    $values[] = ($ssr_date != '' && isset($fields_temps['m_date_val']) && trim($fields_temps['m_date_val']) != '' ) ? sb_parse_date($ssr_date, $fields_temps['m_date_val'], $lang) : '';     // DATE
    $values[] = ($ssr_author != '' && isset($fields_temps['m_author_movie_val']) && trim($fields_temps['m_author_movie_val']) != '' ) ? str_replace('{VALUE}', $ssr_author, $fields_temps['m_author_movie_val']) : '';   // AUTHOR
    $values[] = ($ssr_name != '' && isset($fields_temps['m_name_movie_val']) && trim($fields_temps['m_name_movie_val']) != '' ) ? str_replace('{VALUE}', $ssr_name, $fields_temps['m_name_movie_val']) : '';   // MOVIE_NAME


	if($ssr_duration != '' && isset($fields_temps['m_duration_movie_val']) && trim($fields_temps['m_duration_movie_val']) != '')
	{
		$dur = is_float($ssr_duration / 60) ? explode('.', ($ssr_duration / 60)) : ($ssr_duration / 60);

		if(is_array($dur))
		{
			if(sb_strlen($dur[0]) == 1)
			{
				$dur[0] = '0'.$dur[0];
			}

			$dur[1] =  ($dur[1] * 60) / 100;
			$duration = $dur[0].':'.$dur[1];
		}
		else
		{
			if(sb_strlen($dur) == 1)
			{
				$duration = '0'.$dur.':00';
			}
			else
			{
				$duration = $dur.':00';
			}
		}
    	$values[] = str_replace('{VALUE}', $duration, $fields_temps['m_duration_movie_val']);   // DURATION
	}
	else
    {
		$values[] = '';
    }

    $values[] = ($ssr_size != '' && isset($fields_temps['m_size_movie_val']) && trim($fields_temps['m_size_movie_val']) != '' ) ? str_replace('{VALUE}', $ssr_size, $fields_temps['m_size_movie_val']) : '';   // FILE_SIZE
    $values[] = ($ssr_description != '' && isset($fields_temps['m_description_movie_val']) && trim($fields_temps['m_description_movie_val']) != '' ) ? str_replace('{VALUE}', $ssr_description, $fields_temps['m_description_movie_val']) : '';   //  DESCRIPTION
    $values[] = ($ssr_url != '' && !is_null($ssr_url) ? $ssr_url : '' );  //   LINK
    $values[] = ($cat_title != '' ? $cat_title : '' );  //   CAT_TITLE
    $values[] = ($cat_id != '') ? $cat_id : '';     //   CAT_ID
    $values[] = ($cat_url != '') ? $cat_url : '';    //   CAT_URL

    $users_values = array();
    $num_fields = count($res[0]);
    if ($num_fields > 10)
    {
        for ($i = 10; $i < $num_fields; $i++)
        {
            $users_values[] = $res[0][$i];
        }

        @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
        $users_values = sbLayout::parsePluginFields($users_fields, $users_values, $fields_temps, array(), array(), $lang, $prefix, $sufix);
    }

    $res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, cat_closed
                                FROM sb_categs WHERE cat_id IN (?a)', array($cat_id));
    if($res_cat)
    {
        foreach ($res_cat as $value)
        {
            $categs[$value[0]] = array();
            $categs[$value[0]]['title'] = $value[1];
            $categs[$value[0]]['level'] = $value[2] + 1;
            $categs[$value[0]]['fields'] = (trim($value[3]) != '' ? unserialize($value[3]) : array());
            $categs[$value[0]]['url'] = $value[4];
            $categs[$value[0]]['count'] = $value[5];
            $categs[$value[0]]['closed'] = $value[6];
        }
    }

    $cat_values = array();
    $num_cat_fields = count($categs_sql_fields);
    if ($num_cat_fields > 0)
    {
       	@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		foreach ($categs_sql_fields as $cat_field)
        {
        	if (isset($categs[$cat_id]['fields'][$cat_field]))
				$cat_values[] = $categs[$cat_id]['fields'][$cat_field];
			else
            	$cat_values[] = null;
		}
		$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $categs_temps, array(), array(), $lang, $prefix, '_cat_val');
    }

    if ($users_values || $cat_values)
        $values = array_merge($values, $users_values, $cat_values);

    return str_replace($tags, $values, $temp);
}

/**
 * Вывод облака тегов
 *
 */
function fServices_Rutube_Elem_Cloud($el_id, $temp_id, $params, $tag_id)
{
    if ($GLOBALS['sbCache']->check('pl_services_rutube', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));
    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['rutube_scid']) || isset($_GET['rutube_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
        if (isset($_GET['rutube_cid']))
        {
            $cat_ids[] = intval($_GET['rutube_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_services_rutube"', $_GET['rutube_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['rutube_scid']);
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
		$res = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_services_rutube"
							AND c2.cat_ident = "pl_services_rutube"
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

    $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_services_rutube_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }

    // вытаскиваем макет дизайна
    $res = sql_param_query('SELECT ct_pagelist_id, ct_perpage, ct_size_from, ct_size_to
                FROM sb_clouds_temps WHERE ct_id=?d', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CLOUDS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
        return;
    }

    list($ct_pagelist_id, $ct_perpage, $ct_size_from, $ct_size_to) = $res[0];

    // вытаскиваем макет дизайна постраничного вывода
    $res = sql_param_query('SELECT pt_perstage, pt_begin, pt_next, pt_previous, pt_end, pt_number, pt_sel_number, pt_page_list, pt_delim
                FROM sb_pager_temps WHERE pt_id=?d', $ct_pagelist_id);

    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

    $now = time();
    $where_sql = '';

    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $where_sql .= ' AND m.ssr_date >= '.$last.' AND m.ssr_date <= '.$now;
    }
    elseif ($params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

        $where_sql .= ' AND m.ssr_date >= '.$now.' AND m.ssr_date <= '.$next;
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
	if ($params['filter'] == 'from_to')
	{
		$pager->mFrom = intval($params['filter_from']);
		$pager->mTo = intval($params['filter_to']);
    }

	$tags_total = true;
	$res_tags = $pager->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
                            FROM sb_clouds_tags ct, sb_services_rutube m, sb_clouds_links cl, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=m.ssr_id
                            AND cl.cl_ident="pl_services_rutube" AND cl.cl_el_id=m.ssr_id AND ct.ct_id=cl.cl_tag_id
                            '.$where_sql.'
                        	AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
			    	        AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
				            AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
                            AND LENGTH(ct.ct_tag) >= ?d AND LENGTH(ct.ct_tag) <= ?d
                            GROUP BY cl.cl_tag_id '
                            .($sort_sql != '' ? 'ORDER BY '.$sort_sql : 'ORDER BY ct.ct_tag'),
                            $cat_ids, $ct_size_from, $ct_size_to);

	if (!$res_tags)
    {
        $GLOBALS['sbCache']->save('pl_services_rutube', '');
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
    $result = fClouds_Show($res_tags, $temp_id, $pt_page_list, $tags_total, $params['page'], 'ssr_tag');

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save('pl_services_rutube', $result);
}

/**
 * Вывод календаря
 *
 * @param int $year Год, за который необходимо сделать выборку дней.
 * @param int $month Месяц, за который необходимо сделать выборку дней.
 * @param string $params Параметры компонента.
 * @param string $field Поле элемента с датой.
 * @param int $rubrikator Учитывать вывод разделов.
 * @param int $filter Учитывать фильтр.
 *
 * @return array Массив дней, за которые есть элементы.
 */
function fServices_Rutube_Get_Calendar($year, $month, $params, $rubrikator, $filter)
{
	$result = array();

	$params = unserialize(stripslashes($params));
	if (!isset($params['calendar']) || $params['calendar'] != 1 || !isset($params['calendar_field']) || $params['calendar_field'] == '')
	{
		return $result;
	}

	$field = $params['calendar_field'];

	$params['rubrikator'] = $rubrikator;
	$params['filter'] = $filter;

    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['rutube_scid']) || isset($_GET['rutube_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['rutube_cid']))
        {
            $cat_ids[] = intval($_GET['rutube_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_services_rutube"', $_GET['rutube_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['rutube_scid']);
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
		$res = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_services_rutube"
							AND c2.cat_ident = "pl_services_rutube"
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
    $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_services_rutube_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        return $result;
    }

	// вытаскиваем макет дизайна
	$res = sql_param_query('SELECT ssrt_checked, ssrt_categ_top, ssrt_categ_bottom FROM sb_services_rutube_temps_list WHERE ssrt_id=?d', $params['temp_id']);
	if (!$res)
    {
        $ssrt_checked = array();
        $ssrt_categ_bottom = $ssrt_categ_top = '';
	}
	else
	{
		list($ssrt_checked, $ssrt_categ_bottom, $ssrt_categ_top) = $res[0];
		if ($ssrt_checked != '')
    	{
        	$ssrt_checked = explode(' ', $ssrt_checked);
    	}
    	else
    	{
    		$ssrt_checked = array();
    	}
    }

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';

    foreach ($ssrt_checked as $value)
    {
        $elems_fields_where_sql .= ' AND m.user_f_'.$value.'=1';
    }

    $now = time();
    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND m.ssr_date >= '.$last.' AND m.ssr_date <= '.$now;
    }
    elseif ($params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;
        $elems_fields_where_sql .= ' AND m.ssr_date >= '.$now.' AND m.ssr_date <= '.$next;
    }

    if (isset($params['filter_2']) && $params['filter_2'] == 'rating')
    {
        $params['filter_2_from'] = intval($params['filter_2_from']);
        $params['filter_2_to'] = intval($params['filter_2_to']);

        $elems_fields_where_sql .= 'AND (
                                   SELECT (res.vr_count/res.vr_num)
                                   FROM sb_vote_results as res, sb_vote_ips as ips
                                   WHERE res.vr_el_id=m.ssr_id AND ips.vi_vr_id=res.vr_id
                                   GROUP BY ips.vi_vr_id
                              ) >= '.$params['filter_2_from'].' AND
                              (
                                   SELECT (res.vr_count/res.vr_num)
                                   FROM sb_vote_results as res, sb_vote_ips as ips
                                   WHERE res.vr_el_id=m.ssr_id AND ips.vi_vr_id=res.vr_id
                                   GROUP BY ips.vi_vr_id
                              ) < '.$params['filter_2_to'];

    }

    if (isset($params['filter_3']) && $params['filter_3'] == 'votes')
    {
        $params['filter_3_from'] = intval($params['filter_3_from']);
        $params['filter_3_to'] = intval($params['filter_3_to']);

        $elems_fields_where_sql .= ' AND vote.vr_count >= '.$params['filter_3_from'].' AND vote.vr_count < '.$params['filter_3_to'];
    }

    if (isset($params['filter_4']) && $params['filter_4'] == 'views')
    {
        $params['filter_4_from'] = intval($params['filter_4_from']);
        $params['filter_4_to'] = intval($params['filter_4_to']);

        $elems_fields_where_sql .= ' AND m.ssr_views >= '.$params['filter_4_from'].' AND m.ssr_views < '.$params['filter_4_to'];
    }

	$from_date = mktime(0, 0, 0, $month, 1, $year);
    $to_date = mktime(23, 59, 59, $month, sb_get_last_day($month, $year), $year);

    if ($from_date <= 0 || $to_date <= 0)
    {
    	return $result;
    }

	$elems_fields_where_sql .= ' AND m.'.$field.' >= "'.$from_date.'" AND m.'.$field.' <= "'.$to_date.'"';

	// формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
    if (isset($params['sort1']) && $params['sort1'] != '')
    {
    	$elems_fields_sort_sql .=  ', '.$params['sort1'];
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
	if ($ssrt_categ_top != '' || $ssrt_categ_bottom != '')
    {
		$categs_output = true;
	}
	else
	{
		$categs_output = false;
    }

    if ($categs_output)
    {
		$res = sql_param_query('SELECT m.'.$field.'
					FROM sb_services_rutube m LEFT JOIN sb_vote_results v ON v.vr_el_id=m.ssr_id
					AND v.vr_plugin="pl_services_rutube", sb_catlinks l, sb_categs c
                    WHERE c.cat_id IN (?a)
                    AND c.cat_id = l.link_cat_id
                    AND l.link_el_id = m.ssr_id
                    AND m.ssr_status =0
					AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
			    	AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
				    AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
                    '.$elems_fields_where_sql.'
                    GROUP BY m.ssr_id
					ORDER BY c.cat_left'.($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', m.ssr_date DESC').
					($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
	}
	else
	{
		$res = sql_param_query('SELECT m.'.$field.'
        			FROM sb_services_rutube m LEFT JOIN sb_vote_results v ON v.vr_el_id=m.ssr_id
        			AND v.vr_plugin="pl_services_rutube", sb_catlinks l
                    WHERE l.link_cat_id IN (?a) AND l.link_el_id=m.ssr_id
                    AND m.ssr_status = 0
					AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
			    	AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
				    AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')
                    '.$elems_fields_where_sql.'
					GROUP BY m.ssr_id'.
					($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY m.ssr_date DESC').
					($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
	}

	if($res)
	{
		foreach ($res as $value)
		{
			$day = date('j', $value[0]);
			if (!in_array($day, $result))
    		{
    			$result[] = $day;
    		}
    	}
    }

	return $result;
}

/**
 * Вывод формы фильтра
 *
 */
function fServices_Rutube_Elem_Filter($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_services_rutube', $tag_id, array($el_id, $temp_id, $params)))
		return;

	$res = sql_param_query('SELECT ssrtf_id, ssrtf_title, ssrtf_lang, ssrtf_form, ssrtf_fields_temps
							FROM sb_services_rutube_temps_form WHERE ssrtf_id=?d', $temp_id);

	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
		$GLOBALS['sbCache']->save('pl_services_rutube', '');
		return;
    }

	list($ssrtf_id, $ssrtf_title, $ssrtf_lang, $ssrtf_form, $ssrtf_fields_temps) = $res[0];

	$params = unserialize(stripslashes($params));
	$ssrtf_fields_temps = unserialize($ssrtf_fields_temps);

	$result = '';
	if (trim($ssrtf_form) == '')
	{
		$GLOBALS['sbCache']->save('pl_services_rutube', '');
		return;
	}

	$tags = array('{ACTION}',
					'{TEMP_ID}',
					'{TITLE}',
					'{AUTHOR}',
					'{DESCRIPTION}',
					'{ID}',
					'{ID_LO}',
					'{ID_HI}',
					'{DATE}',
					'{DATE_LO}',
					'{DATE_HI}',
					'{VIEWS}',
					'{VIEWS_LO}',
					'{VIEWS_HI}',
					'{DURATION}',
					'{DURATION_LO}',
					'{DURATION_HI}',
					'{SIZE}',
					'{SIZE_LO}',
					'{SIZE_HI}',
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
	$values[] = $ssrtf_id;
	$values[] = (isset($ssrtf_fields_temps['movie_title']) && $ssrtf_fields_temps['movie_title'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_title']) && $_REQUEST['sr_f_title'] != '' ? $_REQUEST['sr_f_title'] : ''), $ssrtf_fields_temps['movie_title']) : '');
	$values[] = (isset($ssrtf_fields_temps['movie_author']) && $ssrtf_fields_temps['movie_author'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_author']) && $_REQUEST['sr_f_author'] != '' ? $_REQUEST['sr_f_author'] : ''), $ssrtf_fields_temps['movie_author']) : '');
	$values[] = (isset($ssrtf_fields_temps['movie_desc']) && $ssrtf_fields_temps['movie_desc'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_desc']) && $_REQUEST['sr_f_desc'] != '' ? $_REQUEST['sr_f_desc'] : ''), $ssrtf_fields_temps['movie_desc']) : '');
	$values[] = (isset($ssrtf_fields_temps['movie_id']) && $ssrtf_fields_temps['movie_id'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_id']) && $_REQUEST['sr_f_id'] != '' ? $_REQUEST['sr_f_id'] : ''), $ssrtf_fields_temps['movie_id']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_id_lo']) && $ssrtf_fields_temps['movie_id_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_id_lo']) && $_REQUEST['sr_f_id_lo'] != '' ? $_REQUEST['sr_f_id_lo'] : ''), $ssrtf_fields_temps['movie_id_lo']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_id_hi']) && $ssrtf_fields_temps['movie_id_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_id_hi']) && $_REQUEST['sr_f_id_hi'] != '' ? $_REQUEST['sr_f_id_hi'] : ''), $ssrtf_fields_temps['movie_id_hi']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_date']) && $ssrtf_fields_temps['movie_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_date']) && $_REQUEST['sr_f_date'] != '' ? $_REQUEST['sr_f_date'] : ''), $ssrtf_fields_temps['movie_date']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_date_lo']) && $ssrtf_fields_temps['movie_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_date_lo']) && $_REQUEST['sr_f_date_lo'] != '' ? $_REQUEST['sr_f_date_lo'] : ''), $ssrtf_fields_temps['movie_date_lo']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_date_hi']) && $ssrtf_fields_temps['movie_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_date_hi']) && $_REQUEST['sr_f_date_hi'] != '' ? $_REQUEST['sr_f_date_hi'] : ''), $ssrtf_fields_temps['movie_date_hi']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_views']) && $ssrtf_fields_temps['movie_views'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_views']) && $_REQUEST['sr_f_views'] != '' ? $_REQUEST['sr_f_views'] : ''), $ssrtf_fields_temps['movie_views']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_views_lo']) && $ssrtf_fields_temps['movie_views_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_views_lo']) && $_REQUEST['sr_f_views_lo'] != '' ? $_REQUEST['sr_f_views_lo'] : ''), $ssrtf_fields_temps['movie_views_lo']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_views_hi']) && $ssrtf_fields_temps['movie_views_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_views_hi']) && $_REQUEST['sr_f_views_hi'] != '' ? $_REQUEST['sr_f_views_hi'] : ''), $ssrtf_fields_temps['movie_views_hi']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_duration']) && $ssrtf_fields_temps['movie_duration'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_duration']) && $_REQUEST['sr_f_duration'] != '' ? $_REQUEST['sr_f_duration'] : ''), $ssrtf_fields_temps['movie_duration']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_duration_lo']) && $ssrtf_fields_temps['movie_duration_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_duration_lo']) && $_REQUEST['sr_f_duration_lo'] != '' ? $_REQUEST['sr_f_duration_lo'] : ''), $ssrtf_fields_temps['movie_duration_lo']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_duration_hi']) && $ssrtf_fields_temps['movie_duration_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_duration_hi']) && $_REQUEST['sr_f_duration_hi'] != '' ? $_REQUEST['sr_f_duration_hi'] : ''), $ssrtf_fields_temps['movie_duration_hi']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_size']) && $ssrtf_fields_temps['movie_size'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_size']) && $_REQUEST['sr_f_size'] != '' ? $_REQUEST['sr_f_size'] : ''), $ssrtf_fields_temps['movie_size']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_size_lo']) && $ssrtf_fields_temps['movie_size_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_size_lo']) && $_REQUEST['sr_f_size_lo'] != '' ? $_REQUEST['sr_f_size_lo'] : ''), $ssrtf_fields_temps['movie_size_lo']) : '';
	$values[] = (isset($ssrtf_fields_temps['movie_size_hi']) && $ssrtf_fields_temps['movie_size_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['sr_f_size_hi']) && $_REQUEST['sr_f_size_hi'] != '' ? $_REQUEST['sr_f_size_hi'] : ''), $ssrtf_fields_temps['movie_size_hi']) : '';
	$values[] = sbLayout::replacePluginFieldsTagsFilterSelect('pl_services_rutube', 's_f_ssr_', $ssrtf_fields_temps['movie_sort_select'], $ssrtf_form);

	sbLayout::parsePluginInputFields('pl_services_rutube', $ssrtf_fields_temps, $ssrtf_fields_temps['date_temps'], $tags, $values, -1, '', '', array(), array(), false, 'sr_f', '', true);

	$result = str_replace($tags, $values, $ssrtf_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	$GLOBALS['sbCache']->save('pl_services_rutube', $result);
}


function fServices_Rutube_Get_Next_Prev($e_temp_id, $e_params, &$result, $output_page, $current_image_id)
{
	$result['href_prev'] = $result['href_next'] = $result['title_prev'] = $result['title_next'] = '';

	if($e_params != '')
		$e_params = unserialize($e_params);
	else
		$e_params = array();

//      выводить ролики зарегистрированных пользователей
	$user_link_id_sql = '';
	if(isset($e_params['registred_users']) && $e_params['registred_users'] == 1)
    {
		if(isset($_SESSION['sbAuth']))
		{
			$user_link_id_sql = ' AND m.ssr_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] == -1 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			return;
		}
		elseif(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] > 0 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND m.ssr_user_id = '.intval($_REQUEST['rutube_uid']);
		}
	}

	$cat_ids_tmp = array();
	if (isset($e_params['rubrikator']) && $e_params['rubrikator'] == 1 && (isset($_GET['rutube_scid']) || isset($_GET['rutube_cid'])))
	{
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['rutube_cid']))
        {
        	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['rutube_cid']);
        	if ($res[0][0] > 0)
        	{
            	$cat_ids_tmp[] = intval($_GET['rutube_cid']);
        	}
		}
		else
		{
			$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['rutube_scid'], 'pl_services_rutube');
			if ($res)
			{
				$cat_ids_tmp[] = $res[0][0];
			}
			else
			{
				$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['rutube_scid']);
				if ($res[0][0] > 0)
				{
					$cat_ids_tmp[] = intval($_GET['rutube_scid']);
				}
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
		$res_tmp = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
						WHERE c2.cat_left <= c.cat_left
						AND c2.cat_right >= c.cat_right
						AND c.cat_ident="pl_services_rutube"
						AND c2.cat_ident = "pl_services_rutube"
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
	$res_cat = sql_param_query('SELECT cat_id, cat_url FROM sb_categs WHERE cat_id IN (?a)', $cat_ids_tmp);

	if ($res_cat)
	{
		foreach ($res_cat as $value_tmp)
		{
			$categs_tmp[$value_tmp[0]] = array();
			$categs_tmp[$value_tmp[0]]['url'] = $value_tmp[1];
		}
	}

	// проверяем, есть ли закрытые разделы среди тех, которые надо выводить
	$res_tmp = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids_tmp);
	if ($res_tmp)
	{
		// проверяем права на закрытые разделы и исключаем их из вывода
	    $closed_ids = array();
	    foreach ($res_tmp as $value_tmp)
            {
			$closed_ids[] = $value_tmp[0];
		}

		$cat_ids_tmp = sbAuth::checkRights($closed_ids, $cat_ids_tmp, 'pl_services_rutube_read');
	}

	if (count($cat_ids_tmp) == 0)
	{
		// указанные разделы были удалены
		return;
	}

	// вытаскиваем макет дизайна
	$res_tmp = sql_param_query('SELECT ssrt_checked, ssrt_perpage, ssrt_name, ssrt_lang, ssrt_fields_temps, ssrt_categs_temps,
	  						ssrt_no_movies, ssrt_count_row, ssrt_top, ssrt_categ_top, ssrt_temp_elem, ssrt_empty, ssrt_delim,
	   						ssrt_categ_bottom, ssrt_bottom, ssrt_pagelist_id  FROM  sb_services_rutube_temps_list WHERE ssrt_id=?d', $e_temp_id);
	if (!$res_tmp)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);

		sb_404();
	}

	list ($ssrtf_checked, $ssrtf_top_cat, $ssrtf_image, $ssrtf_bottom_cat) = $res_tmp[0];

	$elems_fields_sort_sql = '';

	$votes_apply = $comments_sorting = false;

    if (isset($e_params['sort1']) && $e_params['sort1'] != '' && $e_params['sort1'] != 'RAND()')
    {
		if ($e_params['sort1'] == 'com_count' || $e_params['sort1'] == 'com_date')
		{
			$comments_sorting = true;
		}
        if ($e_params['sort1'] == 'm_rating' || $e_params['sort1'] == 'v.vr_num' || $e_params['sort1'] == 'v.vr_count')
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
        if ($e_params['sort2'] == 'm_rating' || $e_params['sort2'] == 'v.vr_num' || $e_params['sort2'] == 'v.vr_count')
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
        if ($e_params['sort3'] == 'm_rating' || $e_params['sort3'] == 'v.vr_num' || $e_params['sort3'] == 'v.vr_count')
		{
			$votes_apply = true;
		}

		$elems_fields_sort_sql .=  ', '.$e_params['sort3'];
		if (isset($e_params['order3']) && $e_params['order3'] != '')
		{
			$elems_fields_sort_sql .= ' '.$e_params['order3'];
		}
	}

	// формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
	$elems_fields_where_sql = '';

	if ($ssrtf_checked != '')
	{
		$ssrtf_checked = explode(' ', $ssrtf_checked);
		foreach ($ssrtf_checked as $value_tmp)
		{
			$elems_fields_where_sql .= ' AND m.user_f_'.$value_tmp.'=1';
		}
	}

	$now = time();
	if ($e_params['filter'] == 'last')
	{
		$last = intval($e_params['filter_last']) - 1;
		$last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND m.ssr_date >= '.$last.' AND m.ssr_date <= '.$now;
	}
	elseif ($e_params['filter'] == 'next')
	{
		$next = intval($e_params['filter_next']);
		$next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND m.ssr_date >= '.$now.' AND m.ssr_date <= '.$next;
	}

	// используется ли группировка по разделам
	if ($ssrtf_top_cat != '' || $ssrtf_bottom_cat != '')
	{
		$categs_tmp_output = true;
	}
	else
	{
		$categs_tmp_output = false;
	}

	$group_str = '';
	$group_res = sql_param_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids_tmp);
	if ($group_res && $group_res[0][0] > 0 || $comments_sorting)
	{
		$group_str = ' GROUP BY m.ssr_id ';
	}

	if($categs_tmp_output)
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', m.ssr_date');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' m.ssr_date');
	}

    if($comments_sorting)
    {
    	$com_sort_fields = ' COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = 'LEFT JOIN sb_comments com ON com.c_el_id=m.ssr_id AND com.c_plugin="pl_services_rutube" AND com.c_show=1';
	}
	else
    {
		$com_sort_fields = ' NULL, NULL';
		$com_sort_sql = '';
    }

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL,';
    if($votes_apply ||
		sb_strpos($ssrtf_image, '{RATING}') !== false ||
		sb_strpos($ssrtf_image, '{VOTES_COUNT}') !== false ||
		sb_strpos($ssrtf_image, '{VOTES_SUM}') !== false ||
		sb_strpos($ssrtf_image, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=m.ssr_id AND v.vr_plugin="pl_services_rutube" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS m_rating ';
	}

	$res_tmp = sql_query('SELECT  l.link_cat_id, m.ssr_id, m.ssr_url, m.ssr_name,
				'.$votes_fields.'
				'.$com_sort_fields.'
			FROM sb_services_rutube m
				'.$votes_sql.
				$com_sort_sql.'
				, sb_catlinks l '.
				(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? ', sb_categs c' : '').'
			WHERE '.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? ' c.cat_id IN (?a) AND c.cat_id=l.link_cat_id ' : ' l.link_cat_id IN (?a) ').' AND l.link_el_id=m.ssr_id
				'.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').'
				AND m.ssr_status = 0
				AND m.ssr_show IN ('.sb_get_workflow_demo_statuses().')
                AND (m.ssr_pub_start IS NULL OR m.ssr_pub_start <= '.$now.')
                AND (m.ssr_pub_end IS NULL OR m.ssr_pub_end >= '.$now.')'.
				$elems_fields_where_sql.' '.
				$user_link_id_sql.' '.
				$group_str.' '.
				($elems_fields_sort_sql != '' ? ' ORDER BY '.substr($elems_fields_sort_sql, 1) : ' ORDER BY m.ssr_date '), $cat_ids_tmp);

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
			$n_prev = $i - 1;
			if($n_prev < 0)
    			$n_prev = '';

            $n_next = $i + 1;
			if($n_next > count($res_tmp) - 1)
			    $n_next = '';

			break;
		}
	}

	// Ссылка на предыдущий элемент
	if($n_prev !== '')
	{
		$result['title_prev'] = $res_tmp[$n_prev][3];

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
				$result['href_prev'] .= ($categs_tmp[$res_tmp[$n_prev][0]]['url'] != '' ? urlencode($categs_tmp[$res_tmp[$n_prev][0]]['url']).'/' : $res_tmp[$n_prev][0].'/').
				($res_tmp[$n_prev][2] != '' ? urlencode($res_tmp[$n_prev][2]) : $res_tmp[$n_prev][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] != '' ? '?rutube_uid='.$_REQUEST['rutube_uid'] : '');
			}
			else
			{
				$result['href_prev'] .= '?rutube_cid='.$res_tmp[$n_prev][0].'&rutube_id='.$res_tmp[$n_prev][1].(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] != '' ? '&rutube_uid='.$_REQUEST['rutube_uid'] : '');
			}
		}
	}

	// Ссылка на следующий элемент
	if($n_next !== '')
	{
	    $result['title_next'] = $res_tmp[$n_next][3];

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
				$result['href_next'] .= ($categs_tmp[$res_tmp[$n_next][0]]['url'] != '' ? urlencode($categs_tmp[$res_tmp[$n_next][0]]['url']).'/' : $res_tmp[$n_next][0].'/').
									($res_tmp[$n_next][2] != '' ? urlencode($res_tmp[$n_next][2]) : $res_tmp[$n_next][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] != '' ? '?rutube_uid='.$_REQUEST['rutube_uid'] : '');
			}
			else
			{
				$result['href_next'] .= '?rutube_cid='.$res_tmp[$n_next][0].'&rutube_id='.$res_tmp[$n_next][1].(isset($_REQUEST['rutube_uid']) && $_REQUEST['rutube_uid'] != '' ? '&rutube_uid='.$_REQUEST['rutube_uid'] : '');
			}
		}
	}
}

?>