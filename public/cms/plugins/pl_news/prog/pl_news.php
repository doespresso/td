<?php
/**
 * Вывод новостной ленты
 */
function fNews_Elem_List($el_id, $temp_id, $params, $tag_id, $ml_news_ids = null, $link_level = 0)
{
	if(is_null($ml_news_ids))
	{
    	if ($GLOBALS['sbCache']->check('pl_news', $tag_id, array($el_id, $temp_id, $params)))
        	return;
	}

	if (isset($_GET['news_sid']))
	{
		$res = sql_query('SELECT COUNT(*) FROM sb_news WHERE n_url=? OR n_id=?d', $_GET['news_sid'], $_GET['news_sid']);
		if (!$res || $res[0][0] == 0)
	    	sb_404();
	}

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

	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['news_scid']) || isset($_GET['news_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['news_cid']))
        {
        	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['news_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids[] = intval($_GET['news_cid']);
        }
        else
        {
            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['news_scid'], 'pl_news');
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
            	$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['news_scid']);
        		if ($res[0][0] > 0)
                	$cat_ids[] = intval($_GET['news_scid']);
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
		$res = sbQueryCache::query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident = ?
							AND c2.cat_ident = ?
							AND c2.cat_id IN (?a)
							ORDER BY c.cat_left', 'pl_news', 'pl_news', $cat_ids);

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
        	if(is_null($ml_news_ids))
        	{
	            // указанные разделы были удалены
	            if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['news_scid']) || isset($_GET['news_cid'])))
	            {
	            	sb_404();
	            }
	        	$GLOBALS['sbCache']->save('pl_news', '');
	            return;
        	}
        	else
        	{
        		return false;
        	}
        }
    }

    //	проверяем, есть ли закрытые разделы среди тех, которые надо выводить
    $comments_read_cat_ids = $cat_ids; // разделы, для которых есть права comments_read
    $comments_edit_cat_ids = $cat_ids; // разделы, для которых есть права comments_edit
    $vote_cat_ids = $cat_ids; // разделы, для которых есть права vote

	$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

		if(is_null($ml_news_ids))
		{
			$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_news_read');
	        $comments_read_cat_ids = sbAuth::checkRights($closed_ids, $comments_read_cat_ids, 'pl_news_comments_read');
	        $comments_edit_cat_ids = sbAuth::checkRights($closed_ids, $comments_edit_cat_ids, 'pl_news_comments_edit');
	        $vote_cat_ids = sbAuth::checkRights($closed_ids, $vote_cat_ids, 'pl_news_vote');
		}
	}

	// вытаскиваем макет дизайна
    $res = sbQueryCache::getTemplate('sb_news_temps_list', $temp_id);

    if (!$res && is_null($ml_news_ids))
    {
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''), KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_news', '');
        return;
    }
	elseif(!$res && !is_null($ml_news_ids))
    {
    	return false;
    }

    list($ndl_lang, $ndl_checked, $ndl_count, $ndl_top, $ndl_categ_top, $ndl_element, $ndl_empty, $ndl_delim,
         $ndl_categ_bottom, $ndl_bottom, $ndl_pagelist_id, $ndl_perpage, $ndl_no_news, $ndl_fields_temps,
         $ndl_categs_temps, $ndl_votes_id, $ndl_comments_id, $ndl_user_data_id, $ndl_tags_list_id) = $res[0];

    $ndl_fields_temps = unserialize($ndl_fields_temps);
    $ndl_categs_temps = unserialize($ndl_categs_temps);

    if (count($cat_ids) == 0)
    {
    	if(is_null($ml_news_ids))
    	{
	        // указанные разделы были удалены
	        $GLOBALS['sbCache']->save('pl_news', $ndl_no_news);
	        return;
    	}
    	else
    	{
    		return false;
    	}
    }

    if(is_null($ml_news_ids) && $ndl_pagelist_id != 0)
    {
	    // вытаскиваем макет дизайна постраничного вывода
	    $res = sbQueryCache::getTemplate('sb_pager_temps', $ndl_pagelist_id);

	    if ($res)
	    {
	        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
	    }
	    else
	    {
	        $pt_page_list = '';
	        $pt_perstage = 1;
	    }
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
			$user_link_id_sql = ' AND n.n_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			$GLOBALS['sbCache']->save('pl_news', $ndl_no_news);
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] == -1 && isset($params['use_filter']) && $params['use_filter'] == 1)
		{
			$GLOBALS['sbCache']->save('pl_news', $ndl_no_news);
			return;
		}
		elseif(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] > 0 && isset($params['use_filter']) && $params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND n.n_user_id = '.intval($_REQUEST['news_uid']);
		}
	}

	// вытаскиваем пользовательские поля новости и раздела
	$res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_news');

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
	                $elems_fields_select_sql .= ', n.user_f_'.$value['id'];

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
    if ($ndl_checked != '')
    {
        $ndl_checked = explode(' ', $ndl_checked);
        foreach ($ndl_checked as $value)
        {
            $elems_fields_where_sql .= ' AND n.user_f_'.$value.'=1';
        }
    }

    $now = time();

    if (isset($params['filter']) && $params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND n.n_date >= '.$last.' AND n.n_date <= '.$now;
    }
    elseif (isset($params['filter']) && $params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND n.n_date >= '.$now.' AND n.n_date <= '.$next;
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

		$elems_fields_where_sql .= ' AND n.'.$params['calendar_field'].' >= '.intval(mktime(0, 0, 0, $month_from, $day_from, $year)).' AND n.'.$params['calendar_field'].' <= '.intval(mktime(23, 59, 59, $month_to, $day_to, $year));
	}

	// Отключаем выводимую новость, если выводится подробный текст новости
	if (isset($params['show_selected']) && $params['show_selected'] == 1 && (isset($_GET['news_sid']) || isset($_GET['news_id'])))
	{
		if (isset($_GET['news_id']))
		{
			$elems_fields_where_sql .= ' AND n.n_id != '.intval($_GET['news_id']);
		}
		else
		{
			$res = sql_query('SELECT n_id FROM sb_news WHERE n_url=?', $_GET['news_sid']);
			if ($res)
			{
				$elems_fields_where_sql .= ' AND n.n_id != '.$res[0][0];
			}
			else
			{
				$elems_fields_where_sql .= ' AND n.n_id != '.intval($_GET['news_sid']);
			}
		}
	}

	// связь с выводом облака тегов
    $cloud_where_sql = '';
    $n_tag = '';

    if (isset($params['cloud']) && $params['cloud'] == 1 && isset($_REQUEST['n_tag']) && $_REQUEST['n_tag'] != '')
    {
    	$tag = trim(preg_replace('/[^0-9\,\s]+/', '', $_REQUEST['n_tag']));
    	if ($tag != '')
        {
            $tmp = explode(',', $tag);
            foreach($tmp as $key=>$val)
            {
                $tmp[$key] = intval(trim($val));
            }
    		$n_tag .= implode(',',$tmp);
        }
    }

    if (isset($params['cloud_comp']) && $params['cloud_comp'] == 1)
    {
    	if (isset($_REQUEST['n_tag_comp']) && $_REQUEST['n_tag_comp'] != '')
    	{
    		$tag = trim(preg_replace('/[^0-9\,\s\-]+/', '', $_REQUEST['n_tag_comp']));
    		if ($tag != '')
            {
                $tmp = explode(',', $tag);
                foreach($tmp as $val)
                {
                    $val = intval(trim($val));
                    $n_tag .= ($n_tag != '' ? ',' : '').$val;
                }
            }
    	}
    	else
    	{
    		$GLOBALS['sbCache']->save('pl_news', $ndl_no_news);
			return;
    	}
    }

    if ($n_tag != '')
    {
    	$cloud_where_sql = ' AND cl.cl_ident=\'pl_news\' AND n.n_id=cl.cl_el_id AND cl.cl_tag_id IN ('.$n_tag.')';
    }

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	// формируем SQL-запрос для фильтра
	$elems_fields_filter_sql = '';
    if ($params['use_filter'] == 1)
    {
    	$date_temp = '';
		if(isset($_REQUEST['n_f_temp_id']))
    	{
			$date = sql_query('SELECT sntf_fields_temps FROM sb_news_temps_form WHERE sntf_id = ?d', $_REQUEST['n_f_temp_id']);
			if($date)
			{
				list($sntf_fields_temps) = $date[0];
				$sntf_fields_temps = unserialize($sntf_fields_temps);
				$date_temp = $sntf_fields_temps['date_temps'];
			}
		}

		$morph_db = false;
		if ($params['filter_morph'] == 1)
		{
			require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
		}

		$elems_fields_filter_sql = '(';

		$elems_fields_filter_sql .= sbGetFilterNumberSql('n.n_id', 'n_f_id', $params['filter_logic']);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('n.n_user_id', 'n_f_user_id', $params['filter_logic']);
		$elems_fields_filter_sql .= sbGetFilterTextSql('n.n_title', 'n_f_title', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterTextSql('n.n_short', 'n_f_short', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterTextSql('n.n_full', 'n_f_full', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('n.n_date', 'n_f_date', $params['filter_logic'], true, $date_temp);

		$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'n', 'n_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
	}

	if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
		$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
	else
		$elems_fields_filter_sql = '';

	// формируем SQL-запрос для сортировки
	$elems_fields_sort_sql = '';
	$votes_apply = $comments_sorting = false;

	if(isset($params['use_sort']) && $params['use_sort'] == '1' && isset($_REQUEST['s_f_news']) && trim($_REQUEST['s_f_news']) != '')
	{
		$elems_fields_sort_sql .= sbLayout::getPluginFieldsSortSql('news', 'n');
	}
	else
	{
		if (isset($params['sort1']) && $params['sort1'] != '')
		{
			if ($params['sort1'] == 'com_count' || $params['sort1'] == 'com_date')
			{
				$comments_sorting = true;
			}
   			if ($params['sort1'] == 'n_rating' || $params['sort1'] == 'v.vr_num' || $params['sort1'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

	    	$elems_fields_sort_sql .=  ', '.$params['sort1'];

	    	if ($params['sort1'] == 'RAND()' && is_null($ml_news_ids))
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

   			if ($params['sort2'] == 'n_rating' || $params['sort2'] == 'v.vr_num' || $params['sort2'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

	        $elems_fields_sort_sql .= ', '.$params['sort2'];

	    	if ($params['sort2'] == 'RAND()' && is_null($ml_news_ids))
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

			if ($params['sort3'] == 'n_rating' || $params['sort3'] == 'v.vr_num' || $params['sort3'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

	        $elems_fields_sort_sql .= ', '.$params['sort3'];

	    	if ($params['sort3'] == 'RAND()' && is_null($ml_news_ids))
	    	{
	    		$GLOBALS['sbCache']->mCacheOff = true;
	    	}

	        if (isset($params['order3']) && $params['order3'] != '')
	        {
	            $elems_fields_sort_sql .= ' '.$params['order3'];
	        }
	    }
	}

    $categs_output = false;
    // используется ли группировка по разделам
    if (trim($ndl_categ_top) != '' || trim($ndl_categ_bottom) != '')
    {
        $categs_output = true;
    }

    // Название для куки содержащего кол-во элементов на страничке
    $num_cookie_name = 'pl_news_'.$temp_id.'_'.$tag_id;
    $count = $ndl_perpage;
    $listIds = '';

    if($ndl_pagelist_id > 0)
    {
        require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
        $pager = new sbDBPager($tag_id, $pt_perstage, $ndl_perpage, '', $num_cookie_name);

        if (isset($params['filter']) && $params['filter'] == 'from_to')
        {
            $pager->mFrom = intval($params['filter_from']);
            $pager->mTo   = intval($params['filter_to']);
        }
    }
    else
    {
        if (intval(sbPlugins::getSetting('sb_preload_id')) > 0 && isset($params['filter']) && $params['filter'] == 'from_to')
        {
            $from  = intval($params['filter_from']);
            $to    = intval($params['filter_to']);
            $limit = '';
            if ($to == 0)
            {
                $count = $ndl_perpage == 0? 9999999: $ndl_perpage;
            }
            else
            {
                $count = abs($to - $from) + 1;
                $count = $ndl_perpage == 0? $count: min($count, $ndl_perpage);
            }

            if ($from > 0 && $from <= $to)
            {
                $limit = ' LIMIT ' . ($from - 1) . ', ' . ($count < 9999999 ? $count * 2
                            : $count);
            }
            elseif ($from > 0 && $from > $to)
            {
                $limit = ' LIMIT ' . ($to - 1) . ', ' . ($count < 9999999 ? $count * 2
                            : $count);
            }


            $resIds = sbQueryCache::query('SELECT DISTINCT link_el_id FROM sb_catlinks WHERE link_cat_id IN (?a) ORDER BY link_el_id DESC' . $limit, $cat_ids);
            if ($resIds)
            {
                $tmp = array();
                foreach ($resIds as $row)
                {
                    $tmp[]        = $row[0];
                }
                $listIds      = ' n.n_id IN (' . implode(',', $tmp) . ') AND ';
            }
        }
    }



    // выборка новостей, которые следует выводить
    $news_total = true;

    $group_str = '';
    //$group_res = sql_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
    //if ($group_res && $group_res[0][0] > 0 || $comments_sorting || $cloud_where_sql != '')
    if ($comments_sorting || $cloud_where_sql != '')
    {
		$group_str = ' GROUP BY n.n_id';
	}

 	// Если новости подгружаются как связанные или для рассылки, выводить не раздел, а список конкретных новостей.
	$only_sql = '';
	if(is_array($ml_news_ids))
    {
		foreach($ml_news_ids as $value)
		{
			$only_sql .= intval($value).',';
		}
		$only_sql = ' AND n.n_id IN ('.substr($only_sql, 0, -1).')';
	}

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL,';
    if($votes_apply ||
		sb_strpos($ndl_element, '{RATING}') !== false ||
		sb_strpos($ndl_element, '{VOTES_COUNT}') !== false ||
		sb_strpos($ndl_element, '{VOTES_SUM}') !== false ||
		sb_strpos($ndl_element, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=n.n_id AND v.vr_plugin="pl_news" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS n_rating, ';
	}

    if($comments_sorting)
    {
    	$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = 'LEFT JOIN sb_comments com ON (com.c_el_id=n.n_id AND com.c_plugin="pl_news" AND com.c_show=1)';
	}
	else
    {
		$com_sort_fields = 'NULL, NULL';
		$com_sort_sql = '';
	}

    if($categs_output)
    {
    	$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', n.n_date');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' n.n_date');
    }

    $query_str = 'SELECT STRAIGHT_JOIN l.link_cat_id, n.n_id, n.n_title, n.n_date, n.n_short, n.n_short_foto, n.n_full, n.n_full_foto, n.n_url,
				'.$votes_fields.
				' n.n_user_id,'.
				$com_sort_fields.
				$elems_fields_select_sql.'
		FROM sb_news n
				'.$votes_sql.'
				'.$com_sort_sql.'
				, sb_catlinks l'.
				(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').
				($cloud_where_sql != '' ? ', sb_clouds_links cl' : '').'
		WHERE '.$listIds.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
                ' AND l.link_src_cat_id NOT IN (?a) '.
				(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=n.n_id'.
				$elems_fields_where_sql.
				$elems_fields_filter_sql.
				$cloud_where_sql.
				$only_sql.'
                AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')'.
                $user_link_id_sql.' '.
				$group_str.' '.
				($categs_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql);

    if($ndl_pagelist_id > 0)
    {
        $res = $pager->init($news_total, $query_str, $cat_ids, $cat_ids);
    }
    else
    {
        $res = sql_param_query($query_str.($ndl_perpage > 0? ' LIMIT 0, '.$count:''), $cat_ids, $cat_ids);
    }

	if (!$res && is_null($ml_news_ids))
    {
        $GLOBALS['sbCache']->save('pl_news', $ndl_no_news);
        return;
    }
	elseif(!$res && !is_null($ml_news_ids))
	{
		return false;
	}

    if($ndl_pagelist_id > 0)
    {
        $count_news = $pager->mFrom + 1;
    }
    else
    {
        $count_news = count($res);
    }

    $comments_count = array();
    if(sb_strpos($ndl_element, '{COUNT_COMMENTS}') !== false)
    {
	    if ($comments_sorting)
	    {
	    	for($i = 0; $i < count($res); $i++)
	        {
		       $comments_count[$res[$i][1]] = $res[$i][13];
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
	        $comments_count = fComments_Get_Count($ids_arr, 'pl_news');
	    }
    }

    $categs = array();
    if (sb_substr_count($ndl_categ_top, '{CAT_COUNT}') > 0 ||
        sb_substr_count($ndl_categ_bottom, '{CAT_COUNT}') > 0 ||
        sb_substr_count($ndl_element, '{CAT_COUNT}') > 0
       )
    {
		$res_cat = sql_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
                (

                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_news n
                WHERE c.cat_id = c1.cat_id
                AND l.link_el_id=n.n_id
				'.$elems_fields_where_sql.'
				AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                AND l.link_src_cat_id NOT IN (?a)

                ) AS cat_count, c1.cat_closed
				FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids);
	}

    else
    {
        $res_cat = sbQueryCache::query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, ? AS cat_count, cat_closed
                FROM sb_categs WHERE cat_id IN (?a)', '', $cat_ids);
    }

    if ($res_cat)
    {
        foreach ($res_cat as $value)
        {
            $categs[$value[0]] = array();
            $categs[$value[0]]['title'] = $value[1];
            $categs[$value[0]]['level'] = $value[2] + 1;
            $categs[$value[0]]['fields'] = (trim($value[3]) != '' ? unserialize($value[3]) : array());
            $categs[$value[0]]['url'] = urlencode($value[4]);
            $categs[$value[0]]['count'] = $value[5];
            $categs[$value[0]]['closed'] = $value[6];
        }
    }

    // строим список номеров страниц
    if ($pt_page_list != '' && is_null($ml_news_ids))
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

	// верх вывода новостной ленты
    $flds_tags = array( '{SORT_ID_ASC}' ,'{SORT_ID_DESC}',
    					'{SORT_TITLE_ASC}','{SORT_TITLE_DESC}',
    					'{SORT_DATE_ASC}','{SORT_DATE_DESC}',
    					'{SORT_SHORT_ASC}','{SORT_SHORT_DESC}',
    					'{SORT_FULL_ASC}','{SORT_FULL_DESC}',
    					'{SORT_SORT_ASC}','{SORT_SORT_DESC}',
    					'{SORT_USER_ID_ASC}','{SORT_USER_ID_DESC}');

	$query_str = $_SERVER['QUERY_STRING'];
	if(isset($_GET['s_f_news']))
	{
		$query_str = preg_replace('/[?&]?s_f_news['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
	}

	$flds_href = (isset($GLOBALS['PHP_SELF']) ? $GLOBALS['PHP_SELF'] : '').(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_news=';
	$flds_vals = array( $flds_href.urlencode('n_id=ASC'),
    					$flds_href.urlencode('n_id=DESC'),
    					$flds_href.urlencode('n_title=ASC'),
    					$flds_href.urlencode('n_title=DESC'),
    					$flds_href.urlencode('n_date=ASC'),
    					$flds_href.urlencode('n_date=DESC'),
    					$flds_href.urlencode('n_short=ASC'),
    					$flds_href.urlencode('n_short=DESC'),
    					$flds_href.urlencode('n_full=ASC'),
    					$flds_href.urlencode('n_full=DESC'),
    					$flds_href.urlencode('n_sort=ASC'),
    					$flds_href.urlencode('n_sort=DESC'),
    					$flds_href.urlencode('n_user_id=ASC'),
    					$flds_href.urlencode('n_user_id=DESC'));

	sbLayout::getPluginFieldsTagsSort('news', $flds_tags, $flds_vals, 'href_replace');

	//	Заменяем значение селекта "Кол-во на странице" селектед
	if(isset($_REQUEST['num_'.$tag_id]))
	{
		$ndl_top = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $ndl_top);
    }
    elseif(isset($_COOKIE[$num_cookie_name]))
    {
    	$ndl_top = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $ndl_top);
    }
    $result = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'), $flds_tags), array_merge(array($pt_page_list, $news_total, 'num_'.$tag_id), $flds_vals), $ndl_top);

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
                                     '{DATE}',
    								 '{CHANGE_DATE}',
                                     '{SHORT}',
                                     '{SHORT_FOTO}',
                                     '{FULL}',
                                     '{FULL_FOTO}',
    								 '{TAGS}',
    								 '{VOTES_SUM}',
    								 '{VOTES_COUNT}',
                                     '{RATING}',
    								 '{VOTES_FORM}',
                                     '{COUNT_COMMENTS}',
    								 '{FORM_COMMENTS}',
    								 '{LIST_COMMENTS}',
    								 '{USER_DATA}',
    								 '{ELEM_USER_LINK}'));

    $cur_cat_id = 0;
    $values = array();
    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $col = 0;

	$dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');
	if(!is_null($ml_news_ids))
	{
		require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');
	}

	list($more_page, $more_ext) = sbGetMorePage($params['page']);
	list($edit_page, $edit_ext) = sbGetMorePage($params['edit_page']);

	$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''));
	if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
	{
		$auth_page = '/'.$auth_page;
	}

    $view_rating_form = (sb_strpos($ndl_element, '{VOTES_FORM}') !== false && $ndl_votes_id > 0);
    $view_comments_list = (sb_strpos($ndl_element, '{LIST_COMMENTS}') !== false && $ndl_comments_id > 0);
    $view_comments_form = (sb_strpos($ndl_element, '{FORM_COMMENTS}') !== false && $ndl_comments_id > 0);

    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');

	$news_ids = array();
	if(sb_strpos($ndl_element, '{TAGS}') !== false)
	{
		// Достаю макеты для вывода списка тегов элементов
		$tags_template_error = false;
    	$res_tags = sql_param_query('SELECT ct_pagelist_id, ct_perpage
                FROM sb_clouds_temps WHERE ct_id=?d', $ndl_tags_list_id);

    	if (!$res_tags)
    	   $tags_template_error = true;

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
	}

    foreach ($res as $value)
    {
    	$value[8] = urlencode($value[8]);

    	if(is_array($ml_news_ids))
    	{
			$news_ids[] = $value[1];
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
                         ($value[8] != '' ? $value[8] : $value[1]).($more_ext != 'php' ? '.'.$more_ext : '/');
            }
            else
            {
                $href .= '?news_cid='.$value[0].'&news_id='.$value[1];
            }
        }

        $dop_values = array($value[1], $value[8], strip_tags($value[2]), $value[0], strip_tags($categs[$value[0]]['url']), strip_tags($categs[$value[0]]['title']), $href);
        if (trim($value[6]) != '' && $more_page != '')
        {
        	$more = str_replace($dop_tags, $dop_values, $ndl_fields_temps['n_more']);
        }

        if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($value[12] != $_SESSION['sbAuth']->getUserId())))
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
	                         ($value[8] != '' ? $value[8] : $value[1]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
	            }
	            else
	            {
	                $edit_href .= '?news_cid='.$value[0].'&news_id='.$value[1];
	            }
	        }

	    	if ($edit_page != '' && isset($ndl_fields_temps['n_edit_link']))
	        {
	        	$edit = str_replace(array_merge(array('{EDIT_LINK}'), $dop_tags), array_merge(array($edit_href), $dop_values), $ndl_fields_temps['n_edit_link']);
	        }
    	}

        if ($num_fields > 15)
        {
            for ($i = 15; $i < $num_fields; $i++)
            {
				$values[] = $value[$i];
            }
 			$allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;
            $values = sbLayout::parsePluginFields($elems_fields, $values, $ndl_fields_temps, $dop_tags, $dop_values, $ndl_lang, '', '', $allow_bb, $link_level, $ndl_element);
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
				$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ndl_categs_temps, $dop_tags, $dop_values, $ndl_lang, '', '', $allow_bb, $link_level, $ndl_categ_top.$ndl_element.$ndl_categ_bottom);
            }

            $values = array_merge($values, $cat_values);
        }
        $values[] = $categs[$value[0]]['title'];  // CAT_TITLE
        $values[] = $categs[$value[0]]['level'];  // CAT_LEVEL
        $values[] = $categs[$value[0]]['count'];  // CAT_COUNT
        $values[] = $value[0]; //  CAT_ID
        $values[] = $categs[$value[0]]['url'];  // CAT_URL
		$values[] = $count_news++;  // ELEM_NUMBER
        $values[] = $value[1]; //  ID
        $values[] = $value[8]; //  ELEM_URL
        $values[] = $value[2]; //  TITLE
        $values[] = $href; //  LINK
        $values[] = $more; //  MORE
        $values[] = $edit; //EDIT_LINK

        $values[] = sb_parse_date($value[3], $ndl_fields_temps['n_date'], $ndl_lang); //   DATE

        // Дата последнего изменения
        if(sb_strpos($ndl_element, '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_news');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ndl_fields_temps['n_last_date'], $ndl_lang) : ''; //   CHANGE_DATE
        }
        else
       	{
        	$values[] = '';
       	}

	    if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
		{
	    	$values[] = sbProgParseBBCodes($value[4]); // SHORT
		}
		else
		{
			$values[] = $value[4]; // SHORT
		}
        $values[] = ($value[5] != '' && !is_null($value[5]) ? str_replace(array_merge(array('{IMG_LINK}'), $dop_tags),
                                   array_merge(array($value[5]), $dop_values),
                                   $ndl_fields_temps['n_short_foto']) : ''); //   SHORT_FOTO
    	if(isset($allow_bb) && isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
		{
	    	$values[] = sbProgParseBBCodes($value[6]); // FULL
		}
		else
		{
			$values[] = $value[6]; // FULL
		}
        $values[] = ($value[7] != '' && !is_null($value[7]) ? str_replace(array_merge(array('{IMG_LINK}'), $dop_tags),
                                   array_merge(array($value[7]), $dop_values),
                                   $ndl_fields_temps['n_full_foto']) : ''); //   FULL_FOTO

		if(sb_strpos($ndl_element, '{TAGS}') !== false)
		{
			$tags_error = false;
		    $pager_tags = new sbDBPager('t_'.$value[1], $pt_perstage, $ct_perpage);

		    // Вытаскиваю теги
			$tags_total = true;
			$res_tags = $pager_tags->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_clouds_links cl
	                            WHERE cl.cl_tag_id IN
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_news" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_news"
	                                AND ct.ct_id=cl.cl_tag_id
	                            GROUP BY cl.cl_tag_id', $value[1]);

	    	if (!$res_tags)
	    	{
	       		$tags_error = true;
	    	}

	    	if(!$tags_error && !$tags_template_error)
	    	{
		    	// строим список номеров страниц
		    	$pt_page_list_tags_1 = '';
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
				$values[] = fClouds_Show($res_tags, $ndl_tags_list_id,  $pt_page_list_tags_1, $tags_total, '', 'n_tag'); //     TAGS
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

        $votes_sum = ($value[9] != '' && !is_null($value[9]) ? $value[9] : 0);
		$votes_count = ($value[10] != '' && !is_null($value[10]) ? $value[10] : 0);
		$votes_rating = ($value[11] != '' && !is_null($value[11]) ? sprintf('%.2f', $value[11]) : 0);

		// VOTES_FORM
        if ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1)
        {
        	$res_vote = fVoting_Form_Submit($ndl_votes_id, 'pl_news', $value[1], $votes_sum, $votes_count, $votes_rating);
        }

        $values[] = $votes_sum; // VOTES_SUM
        $values[] = $votes_count; // VOTES_COUNT
        $values[] = $votes_rating; // RATING

		if(is_null($ml_news_ids) && $view_rating_form && ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1))
	    {
			$values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ndl_votes_id, 'pl_news', $value[1])); // VOTES_FORM
	    }
	    else
	    {
	        $values[] = ''; // VOTES_FORM
	    }

		$c_count = (isset($comments_count[$value[1]]) ? $comments_count[$value[1]] : 0);

	    $add_comments = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_edit_cat_ids) || $categs[$value[0]]['closed'] != 1);
    	if ($add_comments && is_null($ml_news_ids))
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
				$res1 = sql_query('SELECT u_email FROM sb_users WHERE u_id IN (?a)', $u_ids);
				if(!$res1)
					$res1 = array();
			}

			if(count($cat_mod_ids) > 0 )
			{
				$res2 = sql_query('SELECT u.u_email FROM sb_users u, sb_catlinks l
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

	    	if (fComments_Add_Comment($ndl_comments_id, 'pl_news', $value[1], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
	    		$c_count++;
	    }

	    $values[] = $c_count; // COUNT_COMMENTS

	    if ($view_comments_form && is_null($ml_news_ids))
	    {
	    	$values[] = fComments_Get_Form($ndl_comments_id, 'pl_news', $value[1], $add_comments); // FORM_COMMENTS
	    }
	    else
	    {
	    	$values[] = ''; // FORM_COMMENTS
	    }

	    if (is_null($ml_news_ids) && $view_comments_list)
	    {
			$exists_rights = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_read_cat_ids) || $categs[$value[0]]['closed'] != 1);
    		$values[] = fComments_Get_List($ndl_comments_id, 'pl_news', $value[1], $add_comments, '', 0, $exists_rights); // LIST_COMMENTS
	    }
	    else
	    {
	        $values[] = ''; // LIST_COMMENTS
	    }

        if($ndl_user_data_id > 0 && isset($value[12]) && $value[12] > 0 && sb_strpos($ndl_element, '{USER_DATA}') !== false)
        {
			require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
			$values[] = fSite_Users_Get_Data($ndl_user_data_id, $value[12]); //     USER_DATA
        }
		else
        {
			$values[] = '';   //   USER_DATA
        }

		if(isset($value[12]) && $value[12] > 0 && isset($ndl_fields_temps['n_registred_users']) && $ndl_fields_temps['n_registred_users'] != '')
        {
			$action = $auth_page.'?news_uid='.$value[12].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');	//	ELEM_USER_LINK
			$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ndl_fields_temps['n_registred_users']);
        }
        else
        {
			$values[] = '';	 //   ELEM_USER_LINK
        }

        if ($categs_output && $value[0] != $cur_cat_id)
        {
            if ($cur_cat_id != 0)
            {
                // низ вывода раздела
                while ($col < $ndl_count)
                {
                    $result .= $ndl_empty;
                    $col++;
                }
                $result .= str_replace($tags, $old_values, $ndl_categ_bottom);
            }
			// верх вывода раздела
			$result .= str_replace($tags, $values, $ndl_categ_top);
			$col = 0;
        }

        if ($col >= $ndl_count)
        {
            $result .= $ndl_delim;
            $col = 0;
        }

        $result .= str_replace($tags, $values, $ndl_element);

        $cur_cat_id = $value[0];
        $col++;
    }

    while ($col < $ndl_count)
    {
        $result .= $ndl_empty;
        $col++;
    }

    if ($categs_output)
    {
        // низ вывода раздела
        $result .= str_replace($tags, $values, $ndl_categ_bottom);
    }

    // низ вывода новостной ленты
	if(isset($_REQUEST['num_'.$tag_id])&& sb_strpos($ndl_bottom,'{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}'))
    {
    	$ndl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $ndl_bottom);
    }
    elseif(isset($_COOKIE[$num_cookie_name]) && sb_strpos($ndl_bottom,'{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}'))
    {
    	$ndl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $ndl_bottom);
    }

    $result .= str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $news_total, 'num_'.$tag_id), $flds_vals), $ndl_bottom);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	if(is_array($ml_news_ids) && $link_level > 0)
    {
		return $result;
	}
	else
    {
		if(is_null($ml_news_ids))
		{
    		$GLOBALS['sbCache']->save('pl_news', $result);
		}
    	else
    	{
    		//чистим код от инъекций
    		$result = sb_clean_string($result);

			ob_start();
			eval(' ?>'.$result.'<?php ');
			$result = ob_get_clean();
			return $result.'||#$'.addslashes(serialize($news_ids));
 	   }
    }
}

/**
 * Вывод полного текста новости
 *
 */
function fNews_Elem_Full($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
	$n_id = $GLOBALS['sbCache']->check('pl_news', $tag_id, array($el_id, $temp_id, $params));
    if ($n_id)
    {
		@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
		fClouds_Init_Tags('pl_news', array($n_id));
		return;
    }

    $params = unserialize(stripslashes($params));
    if($linked > 0)
    {
		$_GET['news_id'] = $linked;
    }

    if (!isset($_GET['news_sid']) && !isset($_GET['news_id']))
    {
    	$GLOBALS['sbCache']->save('pl_news', '');
        return;
    }

    $cat_id = -1;
    if (isset($_GET['news_scid']) || isset($_GET['news_cid']))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
        if (isset($_GET['news_cid']))
        {
            $cat_id = intval($_GET['news_cid']);
        }
        else
        {
            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['news_scid'], 'pl_news');
            if ($res)
            {
                $cat_id = $res[0][0];
            }
            else
            {
                $cat_id = intval($_GET['news_scid']);
            }
        }
    }

    // вытаскиваем макет дизайна
    $res = sbQueryCache::getTemplate('sb_news_temps_full', $temp_id);

    if (!$res)
    {
        if ($linked > 0)
        {
            unset($_GET['news_id']);
        }

        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_news', '');
        return;
    }

    list($ntf_lang, $ntf_element, $ntf_fields_temps, $ntf_categs_temps, $ntf_checked, $ntf_comments_id, $ntf_votes_id, $ntf_user_data_id, $ntf_tags_list_id) = $res[0];

    $ntf_fields_temps = unserialize($ntf_fields_temps);
    $ntf_categs_temps = unserialize($ntf_categs_temps);

    // вытаскиваем пользовательские поля новости и раздела
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_news');

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
	                $elems_fields_select_sql .= ', n.user_f_'.$value['id'];

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
			$user_link_id_sql = ' AND n.n_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			if($linked > 0)
    		{
    		    unset($_GET['news_id']);

    		    sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
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
    if ($ntf_checked != '')
    {
        $ntf_checked = explode(' ', $ntf_checked);
        foreach ($ntf_checked as $value)
        {
            $elems_fields_where_sql .= ' AND n.user_f_'.$value.'=1';
        }
    }

    $now = time();

    if ($cat_id != -1 && $linked < 1)
    {
        $cat_dop_sql = 'AND c.cat_id='.intval($cat_id);
    }
    else
    {
        $cat_dop_sql = 'AND c.cat_ident=\'pl_news\'';
    }

    if (isset($_GET['news_id']))
    {
        $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                          n.n_id, n.n_title, n.n_date, n.n_short, n.n_short_foto, n.n_full, n.n_full_foto, n.n_url,
                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS n_rating, n.n_user_id
                          '.$elems_fields_select_sql.'
                          FROM sb_news n LEFT JOIN sb_vote_results v ON (v.vr_el_id=?d AND v.vr_plugin=?), sb_categs c, sb_catlinks l
                          WHERE n.n_id=?d AND l.link_el_id=n.n_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
                          AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                          AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                          AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                          '.$elems_fields_where_sql.$user_link_id_sql, $_GET['news_id'], 'pl_news', $_GET['news_id']);
    }
    else
    {
        $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                          n.n_id, n.n_title, n.n_date, n.n_short, n.n_short_foto, n.n_full, n.n_full_foto, n.n_url,
                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS n_rating, n.n_user_id
                          '.$elems_fields_select_sql.'
                          FROM sb_news n LEFT JOIN sb_vote_results v ON (v.vr_el_id=n.n_id AND v.vr_plugin=?), sb_categs c, sb_catlinks l
                          WHERE n.n_url=? AND l.link_el_id=n.n_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
                          AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                          AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                          AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                          '.$elems_fields_where_sql.$user_link_id_sql, 'pl_news', $_GET['news_sid']);

        if (!$res)
        {
            $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                          n.n_id, n.n_title, n.n_date, n.n_short, n.n_short_foto, n.n_full, n.n_full_foto, n.n_url,
                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS n_rating, n.n_user_id
                          '.$elems_fields_select_sql.'
                          FROM sb_news n LEFT JOIN sb_vote_results v ON (v.vr_el_id=?d AND v.vr_plugin=?), sb_categs c, sb_catlinks l
                          WHERE n.n_id=?d AND l.link_el_id=n.n_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
                          AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                          AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                          AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                          '.$elems_fields_where_sql.$user_link_id_sql, $_GET['news_sid'], 'pl_news', $_GET['news_sid']);
        }
    }

    if (!$res)
    {
    	if($linked > 0)
    	{
    	    unset($_GET['news_id']);

    	    sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
    		return;
    	}
    	else
    	{
			sb_404();
    	}
    }

    $view_rating_form = (sb_strpos($ntf_element, '{VOTES_FORM}') !== false && $ntf_votes_id > 0);
    $view_comments_list = (sb_strpos($ntf_element, '{LIST_COMMENTS}') !== false && $ntf_comments_id > 0);
    $view_comments_form = (sb_strpos($ntf_element, '{FORM_COMMENTS}') !== false && $ntf_comments_id > 0);

    $add_rating = true;
    $add_comments = true;

    $res[0][5] = urlencode($res[0][5]); // CAT_URL
    $res[0][13] = urlencode($res[0][13]); // ELEM_URL

    if ($res[0][4] == 1)
    {
        $cat_ids = sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_news_read');
        if (count($cat_ids) == 0)
        {
            if($linked > 0)
                unset($_GET['news_id']);

            $GLOBALS['sbCache']->save('pl_news', '');
            return;
        }

        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_news_vote')) == 0)
        {
        	$view_rating_form = false;
        	$add_rating = false;
        }

    	if ($view_comments_list && count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_news_comments_read')) == 0)
        {
        	$view_comments_list = false;
        }

		if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_news_comments_edit')) == 0)
		{
			$add_comments = false;
        }
    }

    $cat_count = '';
    if (sb_strpos($ntf_element, '{CAT_COUNT}') !== false)
    {
        $res_cat = sql_query('SELECT COUNT(link_el_id) FROM sb_catlinks
                WHERE link_cat_id=?d AND link_src_cat_id != ?d', $res[0][0], $res[0][0]);

        if ($res_cat)
        {
            $cat_count = $res_cat[0][0];
        }
    }

	$comments_count = array();
    if(sb_strpos($ntf_element, '{COUNT_COMMENTS}') !== false)
    {
	    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
        $comments_count = fComments_Get_Count(array($res[0][6]), 'pl_news');
    }

    $tags = array_merge($tags, array('{CAT_TITLE}',
                                     '{CAT_LEVEL}',
                                     '{CAT_COUNT}',
                                     '{CAT_ID}',
                                     '{CAT_URL}',
                                     '{ID}',
                                     '{ELEM_URL}',
                                     '{TITLE}',
                                     '{DATE}',
    								 '{EDIT_LINK}',
    								 '{CHANGE_DATE}',
                                     '{SHORT}',
                                     '{SHORT_FOTO}',
                                     '{FULL}',
                                     '{FULL_FOTO}',
    								 '{TAGS}',
                                     '{VOTES_SUM}',
                                     '{VOTES_COUNT}',
                                     '{RATING}',
                                     '{VOTES_FORM}',
    								 '{COUNT_COMMENTS}',
                                     '{FORM_COMMENTS}',
                                     '{LIST_COMMENTS}',
    								 '{USER_DATA}',
    								 '{ELEM_USER_LINK}',
    								 '{NEWS_PREV}',
    								 '{NEWS_NEXT}'));

    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $values = array();

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_TITLE}', '{CAT_URL}', '{LINK}');
    $dop_values = array($res[0][6], strip_tags($res[0][13]), strip_tags($res[0][7]), $res[0][0], strip_tags($res[0][1]), strip_tags($res[0][5]));

    if ($num_fields > 18)
    {
        for ($i = 18; $i < $num_fields; $i++)
        {
		   $values[] = $res[0][$i];
        }
		$allow_bb = 0;

		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
		{
			$allow_bb = 1;
		}

		$values = sbLayout::parsePluginFields($elems_fields, $values, $ntf_fields_temps, $dop_tags, $dop_values, $ntf_lang, '', '', $allow_bb, $link_level, $ntf_element);
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
            {
                $cat_values[] = null;
            }
        }

		$allow_bb = 0;
		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
		{
			$allow_bb = 1;
		}

        $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ntf_categs_temps, $dop_tags, $dop_values, $ntf_lang, '', '', $allow_bb, $ntf_element);

        $values = array_merge($values, $cat_values);
    }

    //Ссылка "редактировать"
    $edit = '';

    if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($res[0][17] != $_SESSION['sbAuth']->getUserId())))
    {
        $edit = '';
    }
    elseif (sb_strpos($ntf_element, '{EDIT_LINK}') !== false)
    {
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
	    	$categs = sql_query('SELECT cat_url, cat_title FROM sb_categs where cat_id = ?d', $res[0][0]);
	    	$edit_href = $edit_page;
	        if (sbPlugins::getSetting('sb_static_urls') == 1)
	        {
	        	// ЧПУ
				$edit_href .= ($categs[0][0] != '' ? urlencode($categs[0][0]).'/' : $res[0][0].'/').
	                          ($res[0][13] != '' ? $res[0][13] : $res[0][6]).
				              ($edit_ext != 'php' ? '.'.$edit_ext : '/');
			}
	        else
	        {
	        	$edit_href .= '?news_cid='.$res[0][0].'&news_id='.$res[0][6];
	        }
	    }

		if ($edit_page != '' && isset($ntf_fields_temps['n_edit_link']))
	    {
	      	$edit = str_replace(array_merge(array('{EDIT_LINK}'),$dop_tags), array_merge(array($edit_href),$dop_values), $ntf_fields_temps['n_edit_link']);
	    }
    }

    $values[] = $res[0][1];  //		CAT_TITLE
    $values[] = $res[0][2] + 1; //	CAT_LEVEL
    $values[] = $cat_count;  //		CAT_COUNT
    $values[] = $res[0][0];  //		CAT_ID
    $values[] = $res[0][5];  //		CAT_URL
    $values[] = $res[0][6];  //		ID
    $values[] = $res[0][13]; //		ELEM_URL
    $values[] = $res[0][7];  //		TITLE
    $values[] = sb_parse_date($res[0][8], $ntf_fields_temps['n_date'], $ntf_lang); //	DATE

	// Дата последнего изменения
    if(sb_strpos($ntf_element, '{CHANGE_DATE}') !== false)
    {
    	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $res[0][6],'pl_news');
        $values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ntf_fields_temps['n_last_date'], $ntf_lang) : ''; //   CHANGE_DATE
    }
    else
    {
        $values[] = '';
    }

    $values[] = $edit;

	if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
	{
    	$values[] = sbProgParseBBCodes($res[0][9]); // SHORT
	}
	else
	{
		$values[] = $res[0][9]; // SHORT
	}

    $values[] = ($res[0][10] != '' && !is_null($res[0][10]) ? str_replace(array_merge(array('{IMG_LINK}'), $dop_tags),
                               array_merge(array($res[0][10]), $dop_values),
                               $ntf_fields_temps['n_short_foto']) : ''); //		SHORT_FOTO

    $n_full = $res[0][11];

    // разбивка на страницы
    $full_html = preg_split('/<div[\s]+style[\s]*=[\s]*["|\'][\s]*page\-break\-after[\s]*:[\s]*always[;]?[\s]*["|\']>[\s]*<span[\s]+style[\s]*=[\s]*["|\'][\s]*display[\s]*:[\s]*none[\s]*[;]?[\s]*["|\']>[\s]*&nbsp;[\s]*<\/span>[\s]*<\/div>/i', $res[0][11]);
	$num = count($full_html);

	if ($num > 1)
	{
		$res_pager = sql_query('SELECT pt_perstage, pt_begin, pt_next, pt_previous, pt_end, pt_number, pt_sel_number, pt_page_list, pt_delim
    	            FROM sb_pager_temps WHERE pt_id=?d', sbPlugins::getSetting('sb_news_use_delim'));

		if ($res_pager)
		{
			list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res_pager[0];

			@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

		    $pager = new sbDBPager($tag_id, $pt_perstage, 1);

		    $pager->mNumElemsAll = $num;

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

			if (isset($full_html[$pager->mPage - 1]))
		    {
		    	$n_full = $full_html[$pager->mPage - 1].$pt_page_list;
		    }
		    else
		    {
		    	$n_full = $full_html[0].$pt_page_list;
		    }
		}
	}

	if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
	{
    	$values[] = sbProgParseBBCodes($n_full); // FULL
	}
	else
	{
		$values[] = $n_full; // FULL
	}


    $values[] = ($res[0][12] != '' && !is_null($res[0][12]) ? str_replace(array_merge(array('{IMG_LINK}'), $dop_tags),
                               array_merge(array($res[0][12]), $dop_values),
                               $ntf_fields_temps['n_full_foto']) : ''); //		FULL_FOTO

    if(sb_strpos($ntf_element, '{TAGS}') !== false)
    {
		// Вывод тематических тегов
		$tags_error = false;
	    // вытаскиваем макет дизайна тэгов
	    $res_tags = sql_param_query('SELECT ct_pagelist_id, ct_perpage FROM sb_clouds_temps WHERE ct_id=?d', $ntf_tags_list_id);

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
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_news" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_news"
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
			$values[] = fClouds_Show($res_tags, $ntf_tags_list_id,  $pt_page_list_tags, $tags_total, $params['page'], 'n_tag'); //     TAGS
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

    $votes_sum = ($res[0][14] != '' && !is_null($res[0][14]) ? $res[0][14] : 0); //		VOTES_SUM
    $votes_count = ($res[0][15] != '' && !is_null($res[0][15]) ? $res[0][15] : 0); //	VOTES_COUNT
    $votes_rating = ($res[0][16] != '' && !is_null($res[0][16]) ? sprintf('%.2f', $res[0][16]) : 0); //		RATING

	$res_vote = '';
	if ($add_rating)
	{
		// VOTES_FORM
		require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
		$res_vote = fVoting_Form_Submit($ntf_votes_id, 'pl_news', $res[0][6], $votes_sum, $votes_count, $votes_rating);
    }

    $values[] = $votes_sum; // VOTES_SUM
    $values[] = $votes_count; // VOTES_COUNT
    $values[] = $votes_rating; // RATING

    if ($add_rating && $view_rating_form)
    {
        $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ntf_votes_id, 'pl_news', $res[0][6]));  // VOTES_FORM
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
			$res1 = sql_query('SELECT u_email FROM sb_users WHERE u_id IN (?a)', $u_ids);
			if(!$res1)
				$res1 = array();
		}

		if(count($cat_mod_ids) > 0 )
		{
			$res2 = sql_query('SELECT u.u_email FROM sb_users u, sb_catlinks l
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

		if (fComments_Add_Comment($ntf_comments_id, 'pl_news', $res[0][6], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
			$c_count++;
    }

	$values[] = $c_count;

    if ($view_comments_form)
    {
    	$values[] = fComments_Get_Form($ntf_comments_id, 'pl_news', $res[0][6], $add_comments);	//	FORM_COMMENTS
    }
    else
    {
    	$values[] = ''; // FORM_COMMENTS
	}

    if($view_comments_list)
	{
		$values[] = fComments_Get_List($ntf_comments_id, 'pl_news', $res[0][6], $add_comments); // LIST_COMMENTS
	}
	else
	{
	    $values[] = ''; // LIST_COMMENTS
	}

  	if($ntf_user_data_id > 0 && isset($res[0][17]) && $res[0][17] != '' && sb_strpos($ntf_element, '{USER_DATA}') !== false)
    {
		require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
        $values[] = fSite_Users_Get_Data($ntf_user_data_id, $res[0][17]); //     USER_DATA
    }
    else
    {
		$values[] = '';   //   USER_DATA
    }

	if (isset($res[0][17]) && $res[0][17] > 0 && isset($ntf_fields_temps['n_registred_users']) && $ntf_fields_temps['n_registred_users'] != '')
	{
		$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
		if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
		{
			$auth_page = '/'.$auth_page;
		}

		$action = $auth_page.'?news_uid='.$res[0][17].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');
		$values[] =  str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ntf_fields_temps['n_registred_users']);	//	ELEM_USER_LINK
    }
    else
    {
		$values[] = '';  //   ELEM_USER_LINK
    }

    /* Ссылки вперед и назад */
    if (sb_strpos($ntf_element, '{NEWS_PREV}') !== false || sb_strpos($ntf_element, '{NEWS_NEXT}') !== false)
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

    	$res_cat_ids = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_pages"
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

            $res_tmp = sql_param_query('SELECT elem.e_temp_id, elem.e_params
    					FROM sb_elems elem, sb_pages page LEFT JOIN sb_catlinks l ON l.link_el_id = page.p_id
    					WHERE l.link_cat_id IN (?a) AND page.p_filename = ? AND page.p_filepath = ? AND elem.e_p_id = page.p_id
    					AND elem.e_ident = "pl_news_list" AND elem.e_tag = ? LIMIT 1', $cats_ids,  $im_page, $im_file_path, $e_tag);
    	}

    	if($res_tmp != false)
    	{
    		list($e_temp_id, $e_params) = $res_tmp[0];

    		$next_prev_result = array();

    		fNews_Get_Next_Prev($e_temp_id, $e_params, $next_prev_result, $_SERVER['PHP_SELF'], $res[0][6]);

    		$href_prev = $next_prev_result['href_prev'];
    		$href_next = $next_prev_result['href_next'];
    		$title_prev = $next_prev_result['title_prev'];
    		$title_next = $next_prev_result['title_next'];
    	}

    	if($href_prev != '' && isset($ntf_fields_temps['n_prev_link']))
    	{
    		$href_prev = str_replace(array_merge(array('{PREV_HREF}', '{PREV_TITLE}'), $dop_tags), array_merge(array($href_prev, $title_prev), $dop_values), $ntf_fields_temps['n_prev_link']);
    	}

    	if($href_next != '' && isset($ntf_fields_temps['n_next_link']))
    	{
    		$href_next = str_replace(array_merge(array('{NEXT_HREF}', '{NEXT_TITLE}'), $dop_tags), array_merge(array($href_next, $title_next), $dop_values), $ntf_fields_temps['n_next_link']);
    	}

    	$values[] = $href_prev; // PREV
    	$values[] = $href_next;  // NEXT
    }
    else
    {
        $values[] = ''; // PREV
        $values[] = '';  // NEXT
    }

    $result = str_replace($tags, $values, $ntf_element);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    if($linked < 1)
    {
    	$GLOBALS['sbCache']->save('pl_news', $result, $res[0][6]);

    	@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
    	fClouds_Init_Tags('pl_news', array($res[0][6]));
    }
 	else
    {
         unset($_GET['news_id']);
    	 return $result;
    }
}

/**
 * Вывод заголовка новости (HTML)
 *
 */
function fNews_Elem_Header_Html($el_id, $temp_id, $params, $tag_id, $strip_tags=false)
{
    if ($GLOBALS['sbCache']->check('pl_news', $tag_id, array($el_id, $temp_id, $params)))
        return;

    if (!isset($_GET['news_sid']) && !isset($_GET['news_id']))
    {
        return;
    }

    if (isset($_GET['news_id']))
    {
        $res = sql_query('SELECT c.cat_id, c.cat_closed, n.n_title FROM sb_categs c, sb_catlinks l, sb_news n
                          WHERE n.n_id=?d AND l.link_el_id=n.n_id AND c.cat_id=l.link_cat_id AND c.cat_ident=?', $_GET['news_id'], 'pl_news');
    }
    else
    {
        $res = sql_query('SELECT c.cat_id, c.cat_closed, n.n_title FROM sb_categs c, sb_catlinks l, sb_news n
                          WHERE n.n_url=? AND l.link_el_id=n.n_id AND c.cat_id=l.link_cat_id AND c.cat_ident=?', $_GET['news_sid'], 'pl_news');

        if (!$res)
        {
            $res = sql_query('SELECT c.cat_id, c.cat_closed, n.n_title FROM sb_categs c, sb_catlinks l, sb_news n
                          WHERE n.n_id=?d AND l.link_el_id=n.n_id AND c.cat_id=l.link_cat_id AND c.cat_ident=?', $_GET['news_sid'], 'pl_news');
        }
    }

    if (!$res)
    {
        sb_404();
    }

    list($cat_id, $cat_closed, $n_title) = $res[0];

    if ($cat_closed)
    {
        $cat_ids = sbAuth::checkRights(array($cat_id), array($cat_id), 'pl_news_read');
        if (count($cat_ids) == 0)
        {
            $GLOBALS['sbCache']->save('pl_news', '');
            return;
        }
    }

    if ($strip_tags)
        $n_title = strip_tags($n_title);

    $GLOBALS['sbCache']->save('pl_news', $n_title);
}

/**
 * Вывод заголовка новости (без форматирования)
 *
 */
function fNews_Elem_Header_Plain($el_id, $temp_id, $params, $tag_id)
{
    fNews_Elem_Header_Html($el_id, $temp_id, $params, $tag_id, true);
}

/**
 * Вывод разделов
 *
 */
function fNews_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    $num_sub = 0;
    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_news', 'pl_news', 'news', $num_sub);
}

/**
 * Вывод выбранного раздела
 *
 */
function fNews_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_news', 'pl_news', 'news');
}

/**
 * Вывод облака тегов
 *
 */
function fNews_Elem_Cloud($el_id, $temp_id, $params, $tag_id)
{
    if ($GLOBALS['sbCache']->check('pl_news_cloud', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));
    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['news_scid']) || isset($_GET['news_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['news_cid']))
        {
            $cat_ids[] = intval($_GET['news_cid']);
        }
        else
        {
            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['news_scid'], 'pl_news');
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['news_scid']);
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
		$res = sql_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident = ?
							AND c2.cat_ident = ?
							AND c2.cat_id IN (?a)
							ORDER BY c.cat_left', 'pl_news', 'pl_news', $cat_ids);

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

	$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed = 1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_news_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_news_cloud', '');
        return;
    }

    // вытаскиваем макет дизайна
    $res = sql_query('SELECT ct_pagelist_id, ct_perpage, ct_size_from, ct_size_to
                FROM sb_clouds_temps WHERE ct_id=?d', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CLOUDS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_news_cloud', '');
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

	$now = time();
    $where_sql = '';

    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $where_sql .= ' AND n.n_date >= '.$last.' AND n.n_date <= '.$now;
    }
    elseif ($params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

        $where_sql .= ' AND n.n_date >= '.$now.' AND n.n_date <= '.$next;
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
                            FROM sb_clouds_tags ct, sb_news n, sb_clouds_links cl, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=n.n_id
                            AND cl.cl_ident="pl_news" AND cl.cl_el_id=n.n_id AND ct.ct_id=cl.cl_tag_id
                            '.$where_sql.'
                          	AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                            AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                            AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                            AND LENGTH(ct.ct_tag) >= ?d AND LENGTH(ct.ct_tag) <= ?d
                            GROUP BY cl.cl_tag_id '
                            .($sort_sql != '' ? 'ORDER BY '.$sort_sql : 'ORDER BY ct.ct_tag'),
    					    $cat_ids, $ct_size_from, $ct_size_to);

    if (!$res_tags)
    {
        $GLOBALS['sbCache']->save('pl_news_cloud', '');
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
    $result = fClouds_Show($res_tags, $temp_id, $pt_page_list, $tags_total, $params['page'], 'n_tag');

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save('pl_news_cloud', $result);
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
function fNews_Get_Calendar($year, $month, $params, $rubrikator, $filter)
{
	$result = array();

	$params = unserialize(stripslashes($params));
	if (!isset($params['calendar']) || $params['calendar'] != 1 || !isset($params['calendar_field']) || $params['calendar_field'] == '')
	{
		return $result;
	}

	$field = $params['calendar_field'];

	$params['rubrikator'] = $rubrikator;
	$params['use_filter'] = $filter;

    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['news_scid']) || isset($_GET['news_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['news_cid']))
        {
            $cat_ids[] = intval($_GET['news_cid']);
        }
        else
        {
            $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['news_scid'], 'pl_news');
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['news_scid']);
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
		$res = sql_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident = ?
							AND c2.cat_ident = ?
							AND c2.cat_id IN (?a)
							ORDER BY c.cat_left', 'pl_news', 'pl_news', $cat_ids);

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
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_news_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        return $result;
    }

    // вытаскиваем макет дизайна
    $res = sql_query('SELECT ndl_checked, ndl_categ_top, ndl_categ_bottom FROM sb_news_temps_list WHERE ndl_id=?d', $params['temp_id']);
    if (!$res)
    {
        $ndl_checked = array();
        $ndl_categ_top = '';
        $ndl_categ_bottom = '';
	}
	else
    {
    	list($ndl_checked, $ndl_categ_top, $ndl_categ_bottom) = $res[0];
    	if (trim($ndl_checked) != '')
    	{
        	$ndl_checked = explode(' ', $ndl_checked);
    	}
    	else
    	{
    		$ndl_checked = array();
    	}
    }

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';

    foreach ($ndl_checked as $value)
    {
        $elems_fields_where_sql .= ' AND n.user_f_'.$value.'=1';
    }

    $now = time();

    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND n.n_date >= '.$last.' AND n.n_date <= '.$now;
    }
    elseif ($params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND n.n_date >= '.$now.' AND n.n_date <= '.$next;
    }

    $from_date = mktime(0, 0, 0, $month, 1, $year);
    $to_date = mktime(23, 59, 59, $month, sb_get_last_day($month, $year), $year);

    if ($from_date <= 0 || $to_date <= 0)
    {
    	return $result;
    }

    $elems_fields_where_sql .= ' AND n.'.$field.' >= '.$from_date.' AND n.'.$field.' <= '.$to_date;

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
    if ($ndl_categ_top != '' || $ndl_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }

	$group_str = '';
    $group_res = sql_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
    if ($group_res && $group_res[0][0] > 0)
    {
    	$group_str = ' GROUP BY n.n_id ';
    }

    if ($categs_output)
    {
    	$res = sql_query('SELECT n.'.$field.'
                            FROM sb_news n, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=n.n_id
                            '.$elems_fields_where_sql.'
                            AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                            AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                            AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                            '.$group_str.'
                            ORDER BY c.cat_left'.($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', n.n_date DESC').
					    	($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
    }
    else
    {
    	$res = sql_query('SELECT n.'.$field.'
                            FROM sb_news n, sb_catlinks l
                            WHERE l.link_cat_id IN (?a) AND l.link_el_id=n.n_id
                            '.$elems_fields_where_sql.'
                            AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                            AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                            AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                            '.$group_str.' '.
    						($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY n.n_date DESC').
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
 * Вывод в формате RSS 2.0
 *
 */
function fNews_Elem_Rss($el_id, $temp_id, $params, $tag_id)
{
	header('Content-Type: application/rss+xml; charset='.strtolower(SB_CHARSET));

	if ($GLOBALS['sbCache']->check('pl_news', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));

    $rss_title = sb_htmlspecialchars(trim(strip_tags(stripslashes($params['rss_title']))));
    $rss_descr = sb_htmlspecialchars(trim(strip_tags(stripslashes($params['rss_descr']))));
    $rss_logo = sb_htmlspecialchars(trim(strip_tags(stripslashes($params['rss_logo']))));
	$rss_url = sb_htmlspecialchars($params['list_page']);

	$rss = '<?php echo \'<?xml version="1.0" encoding="'.SB_CHARSET.'" ?>\'; ?>
          <rss version="2.0" '.($params['yandex'] == 1 ? ' xmlns:yandex="http://news.yandex.ru"' : '').'>
          <channel>
          <title>'.$rss_title.'</title>
          <link>'.$rss_url.'</link>
          <generator>CMS S.Builder</generator>'.
          ($rss_descr != '' ? '<description>'.$rss_descr.'</description>' : '<description />');

    //если указан рисунок, то выводим и его
    if ($rss_logo != '')
    {
        $rss .= '
          <image>
            <url>'.$rss_logo.'</url>
            <link>'.$rss_url.'</link>
            <title>'.$rss_title.'</title>
          </image>';
    }

    $rss_bottom = '</channel></rss>';

    $cat_ids = explode('^', $params['ids']);

    // если следует выводить подразделы, то вытаскиваем их ID
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
		$res = sql_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident = ?
							AND c2.cat_ident = ?
							AND c2.cat_id IN (?a)
							ORDER BY c.cat_left', 'pl_news', 'pl_news', $cat_ids);

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
        	$GLOBALS['sbCache']->save('pl_news', $rss.$rss_bottom);
            return;
        }
    }

    // проверяем, есть ли закрытые разделы среди тех, которые надо выводить
    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_news_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_news', $rss.$rss_bottom);
        return;
    }

    $elems_fields_where_sql = '';
    $now = time();

    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND n.n_date >= '.$last.' AND n.n_date <= '.$now;
    }
    elseif ($params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND n.n_date >= '.$now.' AND n.n_date <= '.$next;
    }

	if (isset($params['use_filter']) && $params['use_filter'] == 1)
    {
    	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		$morph_db = false;
		if ($params['filter_morph'] == 1)
		{
			require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
		}

		//Фильтр по системным полям
		$elems_fields_filter_sql = '(';

		$elems_fields_filter_sql .= sbGetFilterNumberSql('n.n_id', 'n_f_id', $params['filter_logic']);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('n.n_user_id', 'n_f_user_id', $params['filter_logic']);
		$elems_fields_filter_sql .= sbGetFilterTextSql('n.n_title', 'n_f_title', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterTextSql('n.n_short', 'n_f_short', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterTextSql('n.n_full', 'n_f_full', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

		//фильтр по пользовательским полям
		$elems_fields = array();
		// вытаскиваем пользовательские поля новости
		$res = sbQueryCache::query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_news');
		if($res){
			$elems_fields = unserialize($res[0][0]);
			$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'n', 'n_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, '');
		}

		if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '('){
			$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
			$elems_fields_where_sql .= $elems_fields_filter_sql;
		}
	}


    // формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
    if (isset($params['sort1']) && $params['sort1'] != '')
    {
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
        $elems_fields_sort_sql .= ', '.$params['sort2'];

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
        $elems_fields_sort_sql .= ', '.$params['sort3'];

    	if ($params['sort3'] == 'RAND()')
    	{
    		$GLOBALS['sbCache']->mCacheOff = true;
    	}

        if (isset($params['order3']) && $params['order3'] != '')
        {
			$elems_fields_sort_sql .= ' '.$params['order3'];
        }
    }

	$group_str = '';
    $group_res = sql_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
    if ($group_res && $group_res[0][0] > 0)
    {
    	$group_str = ' GROUP BY n.n_id ';
	}

	$res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_url, n.n_id, n.n_title, n.n_date, n.n_short, n.n_full, n.n_url, (v.vr_count / v.vr_num) AS n_rating, n.n_short_foto
                        FROM sb_news n LEFT JOIN sb_vote_results v ON (v.vr_el_id=n.n_id AND v.vr_plugin=?),
                        	 sb_catlinks l, sb_categs c
                        WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=n.n_id
                        '.$elems_fields_where_sql.'
                        AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                        AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                        AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')
                        '.$group_str.'
                        '.($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY n.n_date DESC').
    					($params['filter'] == 'from_to' ? ' LIMIT '.(intval($params['filter_from']) - 1).', '.(intval($params['filter_to']) - intval($params['filter_from']) + 1) : ''), 'pl_news', $cat_ids);

    if (!$res)
    {
        $GLOBALS['sbCache']->save('pl_news', $rss.$rss_bottom);
        return;
    }

    $rss_items = '';

    list($more_page, $more_ext) = sbGetMorePage($params['full_page']);

    $max_date = -1;
    foreach ($res as $value)
    {
        list($cat_id, $cat_title, $cat_url, $n_id, $n_title, $n_date, $n_short, $n_full, $n_url, $n_rating, $n_short_foto) = $value;

        $cat_url = urlencode($cat_url);
        $n_url = urlencode($n_url);

        if ($n_date > $max_date)
        {
        	$max_date = $n_date;
        }

        $n_date = date('r', $n_date);
        $n_full = trim($n_full);

        if ($more_page == '' || $n_full == '')
        {
            $href = '';
        }
        else
        {
            $href = $more_page;
            if (sbPlugins::getSetting('sb_static_urls') == 1)
            {
                // ЧПУ
                $href .= ($cat_url != '' ? $cat_url.'/' : $cat_id.'/').
                         ($n_url != '' ? $n_url : $n_id).($more_ext != 'php' ? '.'.$more_ext : '/');
            }
            else
            {
                $href .= '?news_cid='.$cat_id.'&news_id='.$n_id;
            }
        }

        $href = sb_htmlspecialchars($href);
        $enclosure = '';

        if (isset($params['make_anons']) && $params['make_anons'] == 1)
        {
        	$n_short = strip_tags($n_full);
			$n_short = sb_short_text($n_short, 250);
		}

		$img_types = array(
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpe' => 'image/jpeg',
			'bmp' => 'image/bmp',
			'png' => 'image/png',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
		);

		// Изображение для анонса новости
		if (!empty($n_short_foto)) {
			$fileExtension = strtolower(substr($n_short_foto, strrpos($n_short_foto, '.') + 1));
			if (isset($img_types[$fileExtension])) {
				$enclosure .= '<enclosure url="' . $n_short_foto . '" type="' . $img_types[$fileExtension] . '" />';
			}
		}

        if (isset($params['yandex']) && $params['yandex'] == 1)
        {
        	if ($n_full != '')
        		$n_full = sb_htmlspecialchars(strip_tags(sb_html_entity_decode($n_full)));

        	$n_title = trim($n_title, '.');

	        $images = array();
	        // Вытаскиваем все изображения
	        if (preg_match_all('/(?:"|\')([^"\']+\.('.implode('|', array_keys($img_types)).'))(?:"|\'|\))/i', $n_short, $images))
	        {
	            for ($i = 0; $i < count($images[1]); $i++)
	            {
	            	$src = $images[1][$i];
	                $pos = stripos($src, 'url(');
	                if ($pos !== false)
	                {
	                    // для случая background: url(...);
	                    continue;
	                }

	                $content_type = $img_types[strtolower(substr($src, strrpos($src, '.') + 1))];
	                $enclosure .= '<enclosure url="'.$src.'" type="'.$content_type.'" />';
	            }
	        }

	        $n_short = strip_tags($n_short);
        }
        else
        {
        	$n_full = '';
        }

        $rss_items .= '<item>
        	<title>'.sb_htmlspecialchars(trim(strip_tags(sb_html_entity_decode(stripslashes($n_title))))).'</title>'.
        	($href != '' ? '<link>'.$href.'</link>' : '').
	        '<description><![CDATA['.$n_short.']]></description>
	        <category>'.$cat_title.'</category>
	        '.$enclosure.'
	        <pubDate>'.$n_date.'</pubDate>'.
	        ($params['yandex'] == 1 ? '<yandex:full-text>'.$n_full.'</yandex:full-text>' : '').
	        '<guid isPermaLink="false">'.SB_COOKIE_DOMAIN.'_'.$n_id.'</guid>
        </item>';
    }

	if ($max_date != -1)
    {
        $max_date = date('r', $max_date);
        $rss .= '<lastBuildDate>'.$max_date.'</lastBuildDate>';
    }

    // низ вывода новостной ленты
    $GLOBALS['sbCache']->save('pl_news', $rss.$rss_items.$rss_bottom);
}

/**
 * Вывод формы добавления/редактирования новостей
 */
function fNews_Elem_Form($el_id, $temp_id, $params, $tag_id)
{
	if (!isset($_POST['n_title']))
	{
		// просто вывод формы, данные пока не пришли
		if ($GLOBALS['sbCache']->check('pl_news', $tag_id, array($el_id, $temp_id, $params)))
			return;
	}

	require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$layout = new sbLayout();

	$res = sql_query('SELECT sntf_lang, sntf_form, sntf_fields_temps, sntf_categs_temps, sntf_messages
							FROM sb_news_temps_form WHERE sntf_id=?d', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
		$GLOBALS['sbCache']->save('pl_news', '');
		return;
    }

	list($sntf_lang, $sntf_form, $sntf_fields_temps, $sntf_categs_temps, $sntf_messages) = $res[0];

	$params = unserialize(stripslashes($params));
	$sntf_fields_temps = unserialize($sntf_fields_temps);
	$sntf_categs_temps = unserialize($sntf_categs_temps);
	$sntf_messages = unserialize($sntf_messages);
	if(!isset($sntf_messages['err_edit_user_field']))
		$sntf_messages['err_edit_user_field'] = '';
	if(!isset($sntf_messages['err_add_user_field']))
		$sntf_messages['err_add_user_field'] = '';

	$result = '';
	if (!isset($_POST['n_title']) && trim($sntf_form) == '')
	{
		// вывод формы
		$GLOBALS['sbCache']->save('pl_news', '');
		return;
	}

	$edit_id = -1;
	$now_cat = -1;

	if (isset($params['edit']) && $params['edit'] == 1)
	{
		//Если редактирование - узнаю id элемента
		if(isset($_GET['news_id']))
		{
			$edit_id = intval($_GET['news_id']);
		}
		elseif(isset($_GET['news_sid']))
		{
			$res = sql_query('SELECT n_id FROM sb_news WHERE n_url = ?', $_GET['news_sid']);
			if ($res)
			{
				$edit_id = $res[0][0];
			}
			else
			{
				$res = sql_query('SELECT n_id FROM sb_news WHERE n_id = ?', $_GET['news_sid']);
				if ($res)
				{
					$edit_id = $res[0][0];
				}
			}
		}

		//Если редактирование - узнаю id текущего раздела

		if(isset($_GET['news_cid']))
		{
			$now_cat = intval($_GET['news_cid']);
		}
		elseif(isset($_GET['news_scid']))
		{
			$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url = ? AND cat_ident="pl_news"', $_GET['news_scid']);
			if ($res)
			{
				$now_cat = $res[0][0];
			}
			else
			{
				$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_id = ?', $_GET['news_scid']);
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

	//Получаю поля новости
	if($edit_id > 0)
	{
		$news_fields = sql_query('SELECT n_title, n_short, n_short_foto, n_full, n_full_foto, n_date, n_url, n_sort, n_user_id FROM sb_news WHERE n_id = ?d', $edit_id);

		$res = sql_query('SELECT t.ct_tag FROM sb_clouds_links l, sb_clouds_tags t WHERE l.cl_ident="pl_news" AND l.cl_el_id = ?d AND t.ct_id = l.cl_tag_id', $edit_id);
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

	if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($news_fields[0][8] != $_SESSION['sbAuth']->getUserId())))
    {
        //Можно редактировать только свои новости
        $GLOBALS['sbCache']->save('pl_news', $sntf_messages['err_edit_user_field']);
        return;
    }
    elseif(isset($params['registred_users_news_add']) && $params['registred_users_news_add'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
    {
        //Только зарегистрированный пользователь может добавлять новость
        $GLOBALS['sbCache']->save('pl_news', $sntf_messages['err_add_user_field']);
        return;
    }

	//Определяю значение инпутов
	$n_title = '';
	$n_short = '';
	$n_full='';
	$n_tags = '';
	$n_categ = array();
	$n_short_photo_now = '';
	$n_full_photo_now = '';
	$n_date = '';
	$n_url = '';
	$n_sort = '';

	//Название новости
	if(isset($_POST['n_title']))
	{
		$n_title = $_POST['n_title'];
	}
	elseif($edit_id > 0 && isset($news_fields[0][0]))
	{
		$n_title = $news_fields[0][0];
	}

	// Дата новости
	if(isset($_POST['n_date']))
	{
		$n_date = $_POST['n_date'];
	}
	elseif($edit_id > 0 && isset($news_fields[0][5]))
	{
		$n_date = sb_parse_date($news_fields[0][5], $sntf_fields_temps['date_temps']);
	}

	// ЧПУ новости
	if(isset($_POST['n_url']))
	{
		$n_url = $_POST['n_url'];
	}
	elseif($edit_id > 0 && isset($news_fields[0][6]))
	{
		$n_url = $news_fields[0][6];
	}

	// Индекс сортировки новости
	if(isset($_POST['n_sort']))
	{
		$n_sort = $_POST['n_sort'];
	}
	elseif($edit_id > 0 && isset($news_fields[0][7]))
	{
		$n_sort = $news_fields[0][7];
	}

	//Анонс новости
	if(isset($_POST['n_short']))
	{
		$n_short = $_POST['n_short'];
	}
	elseif($edit_id > 0 && isset($news_fields[0][1]))
	{
		$n_short = $news_fields[0][1];
	}

	//Полный текст
	if(isset($_POST['n_full']))
	{
		$n_full = $_POST['n_full'];
	}
	elseif($edit_id > 0 && isset($news_fields[0][3]))
	{
		$n_full = $news_fields[0][3];
	}

	//Тэги
	if(isset($_POST['n_tags']))
	{
		$n_tags = $_POST['n_tags'];
	}
	elseif($edit_id > 0 && isset($news_fields_tags))
	{
		$n_tags = $news_fields_tags;
	}

	//Текущие изображения для фото
	if($edit_id > 0 && $news_fields[0][4] != '')
	{
		$n_full_photo_now = $news_fields[0][4];
	}
	if($edit_id > 0 && $news_fields[0][2] != '')
	{
		$n_short_photo_now = $news_fields[0][2];
	}

	//Разделы
	if (isset($_POST['n_categ']))
	{
		if (is_array($_POST['n_categ']))
    	{
    		$n_categ = $_POST['n_categ'];
    	}
    	else
    	{
        	$n_categ[] = intval($_POST['n_categ']);
    	}
	}
	elseif($edit_id > 0)
	{
		$n_categ = null;
	}
	elseif(isset($params['rubrikator_form']) && $params['rubrikator_form'] == 1)
	{
		if (isset($_GET['news_cid']))
		{
			$n_categ[] = intval($_GET['news_cid']);
    	}
		elseif (isset($_GET['news_scid']))
	    {
			$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['news_scid'], 'pl_news');
			if($res)
	        {
				$n_categ[] = $res[0][0];
			}
			else
			{
				$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_id=?d', $_GET['news_scid']);
				if ($res)
		        {
					$n_categ[] = $res[0][0];
		        }
			}
		}
	}

	$uid = explode(' ', microtime());
	$uid = substr($uid[0], 2);
	$n_short_foto = isset($_FILES['n_short_foto']) && $_FILES['n_short_foto']['tmp_name'] != '' ? $uid.sb_strtolat($_FILES['n_short_foto']['name']) : '';

	$uid = explode(' ', microtime());
	$uid = substr($uid[0], 2);
	$n_full_foto = isset($_FILES['n_full_foto']) && $_FILES['n_full_foto']['tmp_name'] != '' ? $uid.sb_strtolat($_FILES['n_full_foto']['name']) : '';

	$tags = array();
	$values = array();

	$short_url = ($n_short_foto != '' ? SB_DOMAIN.'/upload/news/'.$n_short_foto : '');
	$full_url = ($n_full_foto != '' ? SB_DOMAIN.'/upload/news/'.$n_full_foto : '');

	$message_tags = array('{TITLE}', '{DATE}', '{SHORT}', '{SHORT_FOTO}', '{FULL}', '{FULL_FOTO}');
	$message_values = array($n_title, sb_parse_date(time(), $sntf_fields_temps['news_date_val'], SB_CMS_LANG), $n_short, $short_url, $n_full, $full_url);

	$message = '';
	$fields_message = '';

	// проверка данных и сохранение
	$error = false;
	$users_error = false;

	$cat_ids = array();
	if(isset($_POST['pl_plugin_ident']) && $_POST['pl_plugin_ident'] == 'pl_news')
	{
		if(isset($sntf_fields_temps['news_title_need']) && $sntf_fields_temps['news_title_need'] == 1 && (!isset($_POST['n_title']) || $_POST['n_title'] == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{F_TITLE_SELECT_START}', '{F_TITLE_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
		}

		if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($news_fields[0][8] != $_SESSION['sbAuth']->getUserId())))
        {
        	//Можно редактировать только свои новости
        	$error = true;
        	$fields_message = $sntf_messages['err_edit_user_field'];
        }
        elseif(isset($params['registred_users_news_add']) && $params['registred_users_news_add'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
        {
        	//Только зарегистрированный пользователь может добавлять новость
        	$error = true;
        	$fields_message = $sntf_messages['err_add_news'];
        }

        if(isset($sntf_fields_temps['news_date_need']) && $sntf_fields_temps['news_date_need'] == 1 && (!isset($_POST['n_date']) || $_POST['n_date'] == ''))
		{
			// Если поле "Дата" является обязательным
			$error = true;
			$tags = array_merge($tags, array('{F_DATE_SELECT_START}', '{F_DATE_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
		}

		if(isset($sntf_fields_temps['news_url_need']) && $sntf_fields_temps['news_url_need'] == 1 && (!isset($_POST['n_url']) || $_POST['n_url'] == ''))
		{
			// Если поле "Псевдостатический адрес (ЧПУ)" является обязательным
			$error = true;
			$tags = array_merge($tags, array('{F_URL_SELECT_START}', '{F_URL_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
		}

		if(isset($sntf_fields_temps['news_sort_need']) && $sntf_fields_temps['news_sort_need'] == 1 && (!isset($_POST['n_sort']) || $_POST['n_sort'] == ''))
		{
			// Если поле "Индекс сортировки" является обязательным
			$error = true;
			$tags = array_merge($tags, array('{F_SORT_SELECT_START}', '{F_SORT_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
		}

		if(isset($sntf_fields_temps['news_full_need']) && $sntf_fields_temps['news_full_need'] == 1 && $n_full == '')
		{
			$error = true;

			$tags = array_merge($tags, array('{F_FULL_SELECT_START}', '{F_FULL_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
		}

		if(isset($sntf_fields_temps['news_short_need']) && $sntf_fields_temps['news_short_need'] == 1 && $n_short == '')
		{
			$error = true;

			$tags = array_merge($tags, array('{F_SHORT_SELECT_START}', '{F_SHORT_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
		}

		if(isset($sntf_fields_temps['news_short_foto_need']) && $sntf_fields_temps['news_short_foto_need'] == 1 && !@is_uploaded_file($_FILES['n_short_foto']['tmp_name']))
		{
			$error = true;

			$tags = array_merge($tags, array('{F_SHORT_FOTO_SELECT_START}', '{F_SHORT_FOTO_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
        }

		if(isset($sntf_fields_temps['news_full_foto_need']) && $sntf_fields_temps['news_full_foto_need'] == 1 && !@is_uploaded_file($_FILES['n_full_foto']['tmp_name']))
		{
			$error = true;

			$tags = array_merge($tags, array('{F_FULL_FOTO_SELECT_START}', '{F_FULL_FOTO_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
        }

		if(isset($sntf_fields_temps['tags_need']) && $sntf_fields_temps['tags_need'] == 1 && $n_tags == '')
		{
			$error = true;

			$tags = array_merge($tags, array('{F_TAGS_SELECT_START}', '{F_TAGS_SELECT_END}'));
			$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

			$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
        }

		if(isset($sntf_fields_temps['news_categs_list_need']) && $sntf_fields_temps['news_categs_list_need'] == 1)
		{
			$found = false;

			if (is_array($n_categ))
			{
				foreach ($n_categ as $value)
				{
					if ($value > 0)
					{
						$found = true;
						break;
					}
				}
			}

			if (!$found)
			{
				$error = true;
				$tags = array_merge($tags, array('{F_CATEGS_SELECT_START}', '{F_CATEGS_SELECT_END}'));
				$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

				$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
			}
        }

		// проверяем код каптчи
		if (sb_strpos($sntf_form, '{CAPTCHA}') !== false || sb_strpos($sntf_form, '{CAPTCHA_IMG}') !== false)
        {
			if(!sbProgCheckTuring('n_captcha', 'n_captcha_code'))
			{
				$error = true;

				$tags = array_merge($tags, array('{F_CAPTCHA_SELECT_START}', '{F_CAPTCHA_SELECT_END}'));
				$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

				$message .= isset($sntf_messages['err_captcha_code']) ? str_replace($message_tags, $message_values, $sntf_messages['err_captcha_code']) : '' ;
		    }
        }

        if (is_array($n_categ))
        {
	        $cat_ids = array();

	        if(count($n_categ) == 0)
	        {
	        	$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', explode('^', $params['ids']));
	        }
	        else
	        {
	        	$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', $n_categ);
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
				// проверяем права на добавление
				$closed_cats = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
		        $close_ids = array();
		        if($closed_cats)
		        {
		            foreach($closed_cats as $value)
		            {
		                $close_ids[] = $value[0];
		            }
		            $cat_ids = sbAuth::checkRights($close_ids, $cat_ids, 'pl_news_edit');

		            if(count($cat_ids) < 1)
		            {
						$error = true;
						$message .= isset($sntf_messages['not_have_rights_add']) ? str_replace($message_tags, $message_values, $sntf_messages['not_have_rights_add']) : '' ;
		            }
		        }
        	}
	        else
	        {
        		$cat_ids = null;
	        }
        }
        else
        {
        	$cat_ids = null;
        }

		if($n_short_foto != '' || $n_full_foto != '')
        {
			require_once SB_CMS_LIB_PATH . '/sbUploader.inc.php';

			$uploader = new sbUploader();
			$uploader->setMaxFileSize(sbPlugins::getSetting('sb_files_max_upload_size'));
			$uploader->setMaxImageSize(sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'));

			// изображение для анонса новости
			$ext = array('jpg', 'jpeg', 'png', 'gif');
			if(isset($_FILES['n_short_foto']) && $_FILES['n_short_foto']['tmp_name'] != '')
			{
				if($uploader->upload('n_short_foto', $ext) == false)
				{
					$error = true;

					$tags = array_merge($tags, array('{F_SHORT_FOTO_SELECT_START}', '{F_SHORT_FOTO_SELECT_END}'));
					$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

					switch($uploader->getErrorCode())
					{
						case 2:
							$message .= isset($sntf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['n_short_foto']) ? $_FILES['n_short_foto']['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sntf_messages['err_size_too_large']) : '';
							break;

						case 3:
							$message .= isset($sntf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES['n_short_foto']) ? $_FILES['n_short_foto']['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sntf_messages['err_img_size']) : '';
							break;

						case 4:
							$message .= isset($sntf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES['n_short_foto']) ? $_FILES['n_short_foto']['name'] : ''), implode(', ',$ext))), $sntf_messages['err_type_file']) : '';
							break;

						case 1:
						case 5:
						case 6:
							$message .= isset($sntf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['n_short_foto']) ? $_FILES['n_short_foto']['name'] : '')), $sntf_messages['err_save_file']) : '';
							break;
					}
				}

				if(!$error && $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/news/', $n_short_foto) == false)
				{
					$error = true;
					if($uploader->getErrorCode() == 5 || $uploader->getErrorCode() == 6)
					{
						$message .= isset($sntf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['n_short_foto']) ? $_FILES['n_short_foto']['name'] : '')), $sntf_messages['err_save_file']) : '';
					}
				}
			}

			// изображение для полного текста новости
			if(isset($_FILES['n_full_foto']) && $_FILES['n_full_foto']['tmp_name'] != '')
			{
				if($uploader->upload('n_full_foto', $ext) == false)
				{
					$error = true;

					$tags = array_merge($tags, array('{F_FULL_FOTO_SELECT_START}', '{F_FULL_FOTO_SELECT_END}'));
					$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

					switch($uploader->getErrorCode())
					{
						case 2:
							$message .= isset($sntf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['n_full_foto']) ? $_FILES['n_full_foto']['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sntf_messages['err_size_too_large']) : '';
							break;

						case 3:
							$message .= isset($sntf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES['n_full_foto']) ? $_FILES['n_full_foto']['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sntf_messages['err_img_size']) : '';
							break;

						case 4:
							$message .= isset($sntf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES['n_full_foto']) ? $_FILES['n_full_foto']['name'] : ''), implode(', ', $ext))), $sntf_messages['err_type_file']) : '';
							break;

						case 1:
		                case 5:
						case 6:
							$message .= isset($sntf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['n_full_foto']) ? $_FILES['n_full_foto']['name'] : '')), $sntf_messages['err_save_file']) : '';
							break;
					}
				}

				if(!$error && $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/news/', $n_full_foto) == false)
				{
					$error = true;
					if($uploader->getErrorCode() == 5 || $uploader->getErrorCode() == 6)
					{
						$message .= isset($sntf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['n_full_foto']) ? $_FILES['n_full_foto']['name'] : '')), $sntf_messages['err_save_file']) : '';
					}
				}
			}
		}
		elseif (isset($_POST['n_short_foto_delete']) || isset($_POST['n_full_foto_delete']))
       	{
       		//Удаляю фото для анонса
       		if(isset($_POST['n_short_foto_delete']) && (stristr($news_fields[0][2], 'http://') !== false || stristr($news_fields[0][2], 'https://') !== false))
       		{
       			$n_short_photo_now = str_ireplace(array('http://', 'https://'), '', $news_fields[0][2]);
       			$n_short_photo_now = substr($n_short_photo_now, strpos($n_short_photo_now, '/'));
       		}
			if ($n_short_photo_now != '' && $GLOBALS['sbVfs']->exists($n_short_photo_now) && $GLOBALS['sbVfs']->is_file($n_short_photo_now))
			{
            	$GLOBALS['sbVfs']->delete($n_short_photo_now);
            	$n_short_photo_now = '';

			}
       		//Удаляю фото для полного текста
       		if(isset($_POST['n_full_foto_delete']) && (stristr($news_fields[0][4], 'http://') !== false || stristr($news_fields[0][4], 'https://') !== false))
       		{
       			$n_full_photo_now = str_ireplace(array('http://', 'https://'), '', $news_fields[0][4]);
       			$n_full_photo_now = substr($n_full_photo_now, strpos($n_full_photo_now, '/'));
       		}
			if ($n_full_photo_now != '' && $GLOBALS['sbVfs']->exists($n_full_photo_now) && $GLOBALS['sbVfs']->is_file($n_full_photo_now))
			{
            	$GLOBALS['sbVfs']->delete($n_full_photo_now);
            	$n_full_photo_now = '';

			}
        }

		$row = $layout->checkPluginInputFields('pl_news', $users_error, $sntf_fields_temps, $edit_id, 'sb_news', 'n_id', false, $sntf_fields_temps['date_temps']);
		if ($users_error)
        {
            foreach ($row as $f_name => $f_array)
            {
				$f_error = $f_array['error'];
                $f_tag = $f_array['tag'];

				$tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
				$values = array_merge($values, array($sntf_fields_temps['select_start'], $sntf_fields_temps['select_end']));

				switch($f_error)
				{
					case 2:
						$message .= isset($sntf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $sntf_messages['err_save_file']) : '';
						break;

					case 3:
						$message .= isset($sntf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $sntf_messages['err_type_file']) : '';
						break;

					case 4:
						$message .= isset($sntf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sntf_messages['err_size_too_large']) : '';
						break;

					case 5:
						$message .= isset($sntf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sntf_messages['err_img_size']) : '';
						break;

					default:
						$fields_message = isset($sntf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_necessary_field']) : '';
						break;
				}
			}
		}
	}
	$error = $error || $users_error;

	if (isset($_POST['pl_plugin_ident']) && $_POST['pl_plugin_ident'] == 'pl_news' && !$error)
	{
		// Редактируем БД
		//Добавление/редактирвоание индекса сортировки
		if(!isset($_POST['n_sort']) || intval($_POST['n_sort']) == 0)
		{
			if($edit_id > 0)
			{
				if(isset($row['n_sort']))
        			unset($row['n_sort']);
			}
        	else
        	{
	        	$res = sql_query('SELECT MAX(n_sort) FROM sb_news');
				if ($res)
				    $row['n_sort'] = intval($res[0][0])+10;
		        else
		        	$row['n_sort'] = 0;
	    	}
		}
		else
		{
			$row['n_sort'] = intval($_POST['n_sort']);
		}

		//Добавление/редактирвоание заголовка
		$row['n_title'] = $n_title;

		//Добавление/редактирвоание даты
        if(!isset($_POST['n_date']) || trim($_POST['n_date']) == '')
        {
        	if($edit_id > 0)
        	{
        		if(isset($row['n_date']))
        			unset($row['n_date']);
        	}
        	else
        	{
				$row['n_date'] = time();
        	}
        }
        else
        {
        	$n_date = sb_datetoint($_POST['n_date'], $sntf_fields_temps['date_temps']);
        	if($n_date == null)
        		unset($row['n_date']);
        	else
        		$row['n_date'] = $n_date;
        }

        //Добавление/редактирование ЧПУ
        if(!isset($_POST['n_url']))
        {
        	if($edit_id > 0)
        	{
        		if(isset($row['n_url']))
        			unset($row['n_url']);
        	}
        	else
        	{
        		$row['n_url'] = sb_check_chpu('', '', $n_title, 'sb_news', 'n_url', 'n_id');
        	}
        }
        elseif(isset($news_fields[0][6]) && $news_fields[0][6] == $_POST['n_url'])
        {
        	//Если новый чпу совпадает со старым - оставляем в базе как есть
        	if(isset($row['n_url']))
        		unset($row['n_url']);
        }
        else
        {
        	if($edit_id > 0)
        		$row['n_url'] = sb_check_chpu($edit_id, $_POST['n_url'], $n_title, 'sb_news', 'n_url', 'n_id');
        	else
        		$row['n_url'] = sb_check_chpu('', $_POST['n_url'], $n_title, 'sb_news', 'n_url', 'n_id');
        }

        $row['n_short'] = $n_short;

        if($short_url != '')
		{
        	$row['n_short_foto'] = $short_url;
		}
		elseif(isset($n_short_photo_now))
		{
			$row['n_short_foto'] = $n_short_photo_now;
		}
        $row['n_full'] = $n_full;
		if($full_url != '')
		{
			$row['n_full_foto'] = $full_url;
		}
		elseif(isset($n_full_photo_now))
		{
			$row['n_full_foto'] = $n_full_photo_now;
		}
		$row['n_active'] = isset($params['premod_news']) && $params['premod_news'] == 1 ? 0 : 1;
		if($edit_id < 1)
			$row['n_user_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : null;

		$n_title = (sb_strlen($n_title) > 150) ? sb_substr($n_title, 0, 150).'...' : $n_title;

		$n_id = sbProgAddElement('sb_news', 'n_id', $row, $cat_ids, $edit_id, $now_cat);

		if (!$n_id)
		{
			$error = true;
			if($edit_id > 0)
				$message .= isset($sntf_messages['err_edit_news']) ? str_replace($message_tags, $message_values, $sntf_messages['err_edit_news']) : '';
			else
				$message .= isset($sntf_messages['err_add_news']) ? str_replace($message_tags, $message_values, $sntf_messages['err_add_news']) : '';
			sb_add_system_message(sprintf($edit_id > 0 ? KERNEL_PROG_PL_NEWS_FORM_ERR_EDIT : KERNEL_PROG_PL_NEWS_FORM_ERR_ADD, $n_title), SB_MSG_WARNING);
		}
		else
		{
			if($n_tags != '')
			{
				require_once(SB_CMS_PL_PATH.'/pl_clouds/pl_clouds.inc.php');
				fClouds_Set_Field($n_id, 'pl_news', $n_tags);
			}

			sb_add_system_message(sprintf($edit_id > 0 ? KERNEL_PROG_PL_NEWS_FORM_EDIT : KERNEL_PROG_PL_NEWS_FORM_ADD, $n_title), SB_MSG_INFORMATION);
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

			if($cat_ids === null)
			{
				$cat_ids = array();
				$res1 = sql_query('SELECT l.link_cat_id FROM sb_catlinks as l, sb_categs as c WHERE l.link_el_id = ?d AND c.cat_ident = "pl_news" AND c.cat_id = l.link_cat_id',$n_id);
				foreach($res1 as $v)
				{
					$cat_ids[] = $v[0];
				}
			}
			$res = sql_query('SELECT cat_fields FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
			if($res)
			{
				$cat_mod_ids = $u_ids = array();
				foreach($res as $value)
				{
					if(trim($value[0]) == '')
						continue;

					$value = unserialize($value[0]);

					if (isset($value['categs_moderate_email']) && $value['categs_moderate_email'] != '')
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
					$res1 = sql_query('SELECT u_email FROM sb_users WHERE u_id IN (?a)', $u_ids);
					if(!$res1)
						$res1 = array();
				}

				if(count($cat_mod_ids) > 0 )
				{
					$res2 = sql_query('SELECT u.u_email FROM sb_users u, sb_catlinks l
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

			// отправляем письма модераторам
            if (count($mod_emails) > 0)
			{
				$temp_suf = '';
				if($edit_id > 0)
					$temp_suf = '_edit';

				if(!isset($sntf_messages['admin_subj'.$temp_suf]))
					$sntf_messages['admin_subj'.$temp_suf] = '';
				if(!isset($sntf_messages['admin_text'.$temp_suf]))
					$sntf_messages['admin_text'.$temp_suf] = '';

				$email_subj = fNews_Parse($sntf_messages['admin_subj'.$temp_suf], $sntf_fields_temps, $n_id, $sntf_lang, $sntf_categs_temps);

				//чистим код от инъекций
				$email_subj = sb_clean_string($email_subj);

				ob_start();
				eval(' ?>'.$email_subj.'<?php ');
				$email_subj = trim(ob_get_clean());

				$email_text = fNews_Parse($sntf_messages['admin_text'.$temp_suf], $sntf_fields_temps, $n_id, $sntf_lang, $sntf_categs_temps);

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

            //делаем переадресацию
			if (isset($params['page']) && trim($params['page']) != '')
			{
				header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'n_id='.$n_id));
			}
			else
			{
				$php_self = $GLOBALS['PHP_SELF'];
				//Если изменяем ЧПУ, то перекидываем пользователя на адрес с новым чпу
				if(isset($row['n_url']) && isset($_GET['news_sid']) && $row['n_url'] != $_GET['news_sid'])
				{
					$php_self = str_replace($_GET['news_sid'],$row['n_url'],$php_self);
				}
				header('Location: '.sb_sanitize_header($php_self.($_SERVER['QUERY_STRING'] != '' ?  '?'.$_SERVER['QUERY_STRING'].'&n_id='.$n_id : '?n_id='.$n_id)));
			}
			exit (0);
		}
		else
		{
			$layout->deletePluginFieldsFiles();
		}
	}
	elseif(isset($_POST['n_full']) && !$error)
	{
		$layout->deletePluginFieldsFiles();
    }

	if(isset($_GET['n_id']))
	{
		if($edit_id > 0)
			$message .= fNews_Parse($sntf_messages['success_edit_news'], $sntf_fields_temps, $_GET['n_id'], $sntf_lang, $sntf_categs_temps);
		else
			$message .= fNews_Parse($sntf_messages['success_add_news'], $sntf_fields_temps, $_GET['n_id'], $sntf_lang, $sntf_categs_temps);

	}

	$message .= $fields_message;
    $tags = array_merge($tags, array('{MESSAGES}',
									 '{ACTION}',
									 '{TITLE}',
    								 '{DATE}',
    								 '{URL}',
    								 '{SORT}',
									 '{SHORT}',
									 '{SHORT_FOTO}',
    								 '{PIC_SRC_SHORT}',
									 '{FULL}',
									 '{FULL_FOTO}',
    								 '{PIC_SRC_FULL}',
									 '{CATEGS_LIST}',
									 '{TAGS}',
									 '{CAPTCHA}',
									 '{CAPTCHA_IMG}'));
	//  вывод полей формы input
	$values[] = $message;

	$edit_param = '';
	if (isset($params['edit']) && $params['edit'] == 1 && isset($_GET['news_id']) && $_GET['news_id'] != '' &&
		isset($_GET['news_cid']) && $_GET['news_cid'] != '')
	{
		$edit_param = 'news_cid='.$_GET['news_cid'].'&news_id='.$_GET['news_id'];
	}

	$values[] = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].'&'.$edit_param : '?'.$edit_param);
	$values[] = (isset($sntf_fields_temps['news_title']) && $sntf_fields_temps['news_title'] != '') ? str_replace('{VALUE}', $n_title, $sntf_fields_temps['news_title']) : '';
    $values[] = (isset($sntf_fields_temps['news_date']) && $sntf_fields_temps['news_date'] != '') ? str_replace('{VALUE}', $n_date, $sntf_fields_temps['news_date']) : '';
	$values[] = (isset($sntf_fields_temps['news_url']) && $sntf_fields_temps['news_url'] != '') ? str_replace('{VALUE}', $n_url, $sntf_fields_temps['news_url']) : '';
	$values[] = (isset($sntf_fields_temps['news_sort']) && $sntf_fields_temps['news_sort'] != '') ? str_replace('{VALUE}', $n_sort, $sntf_fields_temps['news_sort']) : '';
    $values[] = (isset($sntf_fields_temps['news_short']) && $sntf_fields_temps['news_short'] != '') ? str_replace('{VALUE}', $n_short, $sntf_fields_temps['news_short']) : '';
	$values[] = (isset($sntf_fields_temps['news_short_foto']) && $sntf_fields_temps['news_short_foto'] != '') ? $sntf_fields_temps['news_short_foto'] : '';
	$values[] = ($n_short_photo_now != '' && isset($sntf_fields_temps['news_short_foto_now']) && !is_null($sntf_fields_temps['news_short_foto_now']) ? str_replace('{PIC_SRC}', $n_short_photo_now, $sntf_fields_temps['news_short_foto_now']) : '');
	$values[] = (isset($sntf_fields_temps['news_full']) && $sntf_fields_temps['news_full'] != '') ? str_replace('{VALUE}', $n_full, $sntf_fields_temps['news_full']) : '';
	$values[] = (isset($sntf_fields_temps['news_full_foto']) && $sntf_fields_temps['news_full_foto'] != '') ? $sntf_fields_temps['news_full_foto'] : '';
	$values[] = ($n_full_photo_now != '' && isset($sntf_fields_temps['news_full_foto_now']) && !is_null($sntf_fields_temps['news_full_foto_now']) ? str_replace('{PIC_SRC}', $n_full_photo_now, $sntf_fields_temps['news_full_foto_now']) : '');

	if (sb_strpos($sntf_form, '{CATEGS_LIST}') !== false && isset($params['ids']))
	{
		$cat_ids = explode('^', $params['ids']);
		if (!is_array($n_categ) && $edit_id > 0)
		{
			$res = sql_query('SELECT c.cat_id FROM sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND l.link_el_id = ?d AND l.link_cat_id = c.cat_id', 'pl_news', $edit_id);
			if ($res)
			{
				$n_categ = array();
				foreach ($res as $value)
				{
					$n_categ[] = $value[0];
				}
			}
		}

		$values[] = sbProgGetCategsList($cat_ids, 'pl_news', $n_categ, $sntf_fields_temps['news_categs_list_options'], $sntf_fields_temps['news_categs_list'], 'pl_news_edit');
	}
	else
	{
		$values[] = '';
	}
	$values[] = (isset($sntf_fields_temps['tags']) && $sntf_fields_temps['tags'] != '') ? str_replace('{VALUE}', $n_tags, $sntf_fields_temps['tags']) : '';

    // Вывод КАПЧИ
    if ((sb_strpos($sntf_form, '{CAPTCHA}') !== false || sb_strpos($sntf_form, '{CAPTCHA_IMG}') !== false) &&
    	isset($sntf_fields_temps['captcha']) && trim($sntf_fields_temps['captcha']) != '' &&
		isset($sntf_fields_temps['img_captcha']) && trim($sntf_fields_temps['img_captcha']) != '')
    {
		$turing = sbProgGetTuring();
        if ($turing)
        {
            $values[] = $sntf_fields_temps['captcha'];
            $values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $sntf_fields_temps['img_captcha']);
        }
        else
        {
            $values[] = $sntf_fields_temps['captcha'];
            $values[] = '';
        }
    }
    else
    {
        $values[] = '';
        $values[] = '';
    }

	sbLayout::parsePluginInputFields('pl_news', $sntf_fields_temps, $sntf_fields_temps['date_temps'], $tags, $values, $edit_id, 'sb_news', 'n_id');

    $result = str_replace($tags, $values, $sntf_form);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    //чистим код от инъекций
    $result = sb_clean_string($result);

    if (!isset($_POST['n_title']))
		$GLOBALS['sbCache']->save('pl_news', $result);
    else
        eval(' ?>'.$result.'<?php ');
}

/**
 * Вывод формы фильтра
 *
 */
function fNews_Elem_Filter_Form($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_news', $tag_id, array($el_id, $temp_id, $params)))
		return;

	$res = sql_query('SELECT sntf_id, sntf_form, sntf_fields_temps
							FROM sb_news_temps_form WHERE sntf_id=?d', $temp_id);

	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
		$GLOBALS['sbCache']->save('pl_news', '');
		return;
	}

	list($sntf_id, $sntf_form, $sntf_fields_temps) = $res[0];

	$params = unserialize(stripslashes($params));
	$sntf_fields_temps = unserialize($sntf_fields_temps);

	$result = '';
	if (trim($sntf_form) == '')
	{
		$GLOBALS['sbCache']->save('pl_news', '');
		return;
	}

	$tags = array('{ACTION}', '{TEMP_ID}', '{ID}', '{ID_LO}', '{ID_HI}', '{TITLE}', '{SHORT}', '{FULL}', '{DATE}', '{DATE_LO}', '{DATE_HI}', '{SORT_SELECT}');
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
	$values[] = $sntf_id;
	$values[] = (isset($sntf_fields_temps['news_id']) && trim($sntf_fields_temps['news_id']) != '' ? str_replace('{VALUE}', (isset($_REQUEST['n_f_id']) && $_REQUEST['n_f_id'] != '' ? $_REQUEST['n_f_id'] : ''), $sntf_fields_temps['news_id']) : '');
	$values[] = (isset($sntf_fields_temps['news_id_lo']) && trim($sntf_fields_temps['news_id_lo']) != '' ? str_replace('{VALUE}', (isset($_REQUEST['n_f_id_lo']) && $_REQUEST['n_f_id_lo'] != '' ? $_REQUEST['n_f_id_lo'] : ''), $sntf_fields_temps['news_id_lo']) : '');
	$values[] = (isset($sntf_fields_temps['news_id_hi']) && trim($sntf_fields_temps['news_id_hi']) != '' ? str_replace('{VALUE}', (isset($_REQUEST['n_f_id_hi']) && $_REQUEST['n_f_id_hi'] != '' ? $_REQUEST['n_f_id_hi'] : ''), $sntf_fields_temps['news_id_hi']) : '');
	$values[] = (isset($sntf_fields_temps['news_title']) && trim($sntf_fields_temps['news_title']) != '') ? str_replace('{VALUE}', (isset($_REQUEST['n_f_title']) && $_REQUEST['n_f_title'] != '' ? $_REQUEST['n_f_title'] : ''), $sntf_fields_temps['news_title']) : '';
	$values[] = (isset($sntf_fields_temps['news_short']) && trim($sntf_fields_temps['news_short']) != '') ? str_replace('{VALUE}', (isset($_REQUEST['n_f_short']) && $_REQUEST['n_f_short'] != '' ? $_REQUEST['n_f_short'] : ''), $sntf_fields_temps['news_short']) : '';
	$values[] = (isset($sntf_fields_temps['news_full']) && trim($sntf_fields_temps['news_full']) != '') ? str_replace('{VALUE}', (isset($_REQUEST['n_f_full']) && $_REQUEST['n_f_full'] != '' ? $_REQUEST['n_f_full'] : ''), $sntf_fields_temps['news_full']) : '';
	$values[] = (isset($sntf_fields_temps['news_date']) && trim($sntf_fields_temps['news_date']) != '') ? str_replace('{VALUE}', (isset($_REQUEST['n_f_date']) && $_REQUEST['n_f_date'] != '' ? $_REQUEST['n_f_date'] : ''), $sntf_fields_temps['news_date']) : '';
	$values[] = (isset($sntf_fields_temps['news_date_lo']) && trim($sntf_fields_temps['news_date_lo']) != '') ? str_replace('{VALUE}', (isset($_REQUEST['n_f_date_lo']) && $_REQUEST['n_f_date_lo'] != '' ? $_REQUEST['n_f_date_lo'] : ''), $sntf_fields_temps['news_date_lo']) : '';
	$values[] = (isset($sntf_fields_temps['news_date_hi']) && trim($sntf_fields_temps['news_date_hi']) != '') ? str_replace('{VALUE}', (isset($_REQUEST['n_f_date_hi']) && $_REQUEST['n_f_date_hi'] != '' ? $_REQUEST['n_f_date_hi'] : ''), $sntf_fields_temps['news_date_hi']) : '';

	if (isset($sntf_fields_temps['news_sort_select']) && trim($sntf_fields_temps['news_sort_select']) != '')
	{
		$values[] = sbLayout::replacePluginFieldsTagsFilterSelect('pl_news', 's_f_n_', $sntf_fields_temps['news_sort_select'], $sntf_form);
	}
	else
	{
		$values[] = '';
	}

	sbLayout::parsePluginInputFields('pl_news', $sntf_fields_temps, $sntf_fields_temps['date_temps'], $tags, $values, -1, '', '', array(), array(), false, 'n_f', '', true);

	$result = str_replace($tags, $values, $sntf_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	$GLOBALS['sbCache']->save('pl_news', $result);
}

function fNews_Parse($temp, &$fields_temps, $id, $lang, $categs_temps = array())
{
	if (trim($temp) == '')
		return '';

    // вытаскиваем пользовательские поля
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_news');

	$users_fields = array();
	$users_fields_select_sql = '';

	$categs_fields = array();
	$categs_sql_fields = array();

    $mess_tags = $tags = array();
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
                    $mess_tags[] = '{'.$value['tag'].'}';
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
                    $mess_tags[] = '{'.$value['tag'].'}';
                    $categs_sql_fields[] = 'user_f_'.$value['id'];
                }
            }
        }
	}

	$res = sql_query('SELECT n.n_title, n.n_url, n.n_date, n.n_short, n.n_short_foto, n.n_full, n.n_full_foto, c.cat_title, c.cat_id, c.cat_url
							'.$users_fields_select_sql.'
                            FROM sb_news n, sb_categs c, sb_catlinks l
                            WHERE l.link_el_id = n.n_id AND l.link_cat_id = c.cat_id AND c.cat_ident=? AND
                            l.link_src_cat_id=0 AND n.n_id=?d', 'pl_news', $id);
	if (!$res)
	{
		return '';
    }

	list ($n_title, $n_url, $n_date, $n_short, $n_short_foto, $n_full, $n_full_foto, $cat_title, $cat_id, $cat_url) = $res[0];

	$mess_values = array();
	$mess_values[] = $id;  //  ID
	$mess_values[] = ($n_url != '' ? $n_url : '' );    //   URL
	$mess_values[] = ($n_title != '' && isset($fields_temps['news_title_val']) && trim($fields_temps['news_title_val']) != '' ) ? str_replace('{VALUE}', $n_title, $fields_temps['news_title_val']) : '';   //  TITLE
	$mess_values[] = ($n_date != '' && $n_date != 0 && isset($fields_temps['news_date_val']) && trim($fields_temps['news_date_val']) != '' ) ? sb_parse_date($n_date, $fields_temps['news_date_val'], $lang) : '';  //  DATE
	$mess_values[] = ($n_short != '' && isset($fields_temps['news_short_val']) && trim($fields_temps['news_short_val']) != '' ) ? str_replace('{VALUE}', $n_short, $fields_temps['news_short_val']) : '';      //  SHORT
    $mess_values[] = ($n_short_foto != '' && isset($fields_temps['news_short_foto_val']) && trim($fields_temps['news_short_foto_val']) != '' ) ? str_replace('{VALUE}', $n_short_foto, $fields_temps['news_short_foto_val']) : '';
	$mess_values[] = ($n_full != '' && isset($fields_temps['news_full_val']) && trim($fields_temps['news_full_val']) != '' ) ? str_replace('{VALUE}', $n_full, $fields_temps['news_full_val']) : '';
	$mess_values[] = ($n_full_foto != '' && isset($fields_temps['news_full_foto_val']) && trim($fields_temps['news_full_foto_val']) != '' ) ? str_replace('{VALUE}', $n_full_foto, $fields_temps['news_full_foto_val']) : '';
    $mess_values[] = ($cat_title != '') ? $cat_title : '';
    $mess_values[] = ($cat_id != '') ? $cat_id : '';
    $mess_values[] = ($cat_url != '') ? $cat_url : '';

    $users_values = array();
    $num_fields = count($res[0]);
    if ($num_fields > 10)
    {
        for ($i = 10; $i < $num_fields; $i++)
        {
			$users_values[] = $res[0][$i];
        }

		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		$users_values = sbLayout::parsePluginFields($users_fields, $users_values, $fields_temps, array(), array(), $lang, '', '_val');
	}
	$res_cat = sql_query('SELECT cat_id, cat_fields FROM sb_categs WHERE cat_id IN (?a)', array($cat_id));

	if ($res_cat)
    {
        foreach ($res_cat as $value)
        {
			$categs[$value[0]] = array();
			$categs[$value[0]]['fields'] = (trim($value[1]) != '' ? unserialize($value[1]) : array());
        }
    }

	$cat_values = array();
	$num_cat_fields = count($categs_sql_fields);
	if ($num_cat_fields > 0)
	{
		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		foreach ($categs_sql_fields as $cat_field)
		{
			if (isset($categs[$value[0]]['fields'][$cat_field]))
				$cat_values[] = $categs[$value[0]]['fields'][$cat_field];
			else
				$cat_values[] = null;
		}

		$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $categs_temps, array(), array(), $lang, '', '_cat_val');
		$users_values = array_merge($users_values, $cat_values);
	}

	$mess_tags = array_merge(array('{ID}', '{URL}', '{TITLE}', '{DATE}', '{SHORT}', '{SHORT_FOTO}', '{FULL}', '{FULL_FOTO}', '{CAT_TITLE}',
								'{CAT_ID}', '{CAT_URL}'), $mess_tags);

	if ($users_values)
		$mess_values = array_merge($mess_values, $users_values);

	return str_replace($mess_tags, $mess_values, $temp);
}

function fNews_Get_Next_Prev($e_temp_id, $e_params, &$result, $output_page, $current_image_id)
{
    $result['href_prev'] = $result['href_next'] = $result['title_prev'] = $result['title_next'] = '';

	if($e_params != '')
		$e_params = unserialize($e_params);
	else
		$e_params = array();

    //	выводить новости зарегистрированных пользователей
	$user_link_id_sql = '';
	if(isset($e_params['registred_users']) && $e_params['registred_users'] == 1)
    {
		if(isset($_SESSION['sbAuth']))
		{
			$user_link_id_sql = ' AND n.n_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] == -1 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			return;
		}
		elseif(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] > 0 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND n.n_user_id = '.intval($_REQUEST['news_uid']);
		}
	}

	$cat_ids_tmp = array();
	if (isset($e_params['rubrikator']) && $e_params['rubrikator'] == 1 && (isset($_GET['news_scid']) || isset($_GET['news_cid'])))
	{
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['news_cid']))
        {
        	$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['news_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids_tmp[] = intval($_GET['news_cid']);
		}
		else
		{
			$res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['news_scid'], 'pl_news');
			if ($res)
			{
				$cat_ids_tmp[] = $res[0][0];
			}
			else
			{
				$res = sbQueryCache::query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['news_scid']);
				if ($res[0][0] > 0)
					$cat_ids_tmp[] = intval($_GET['news_scid']);
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
						AND c.cat_ident="pl_news"
						AND c2.cat_ident = "pl_news"
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

		$cat_ids_tmp = sbAuth::checkRights($closed_ids, $cat_ids_tmp, 'pl_news_read');
	}

	if (count($cat_ids_tmp) == 0)
	{
		//	указанные разделы были удалены
		return;
	}

	// вытаскиваем макет дизайна
	$res_tmp = sql_query('SELECT ndl_checked, ndl_categ_top, ndl_element, ndl_categ_bottom
						FROM  sb_news_temps_list WHERE ndl_id=?d', $e_temp_id);
	if (!$res_tmp)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);

		sb_404();
	}

	list ($ndl_checked, $ndl_top_cat, $ndl_element, $ndl_bottom_cat) = $res_tmp[0];

	$elems_fields_sort_sql = '';
	$votes_apply = $comments_sorting = false;

    if (isset($e_params['sort1']) && $e_params['sort1'] != '' && $e_params['sort1'] != 'RAND()')
    {
		if ($e_params['sort1'] == 'com_count' || $e_params['sort1'] == 'com_date')
		{
			$comments_sorting = true;
		}
   		if ($e_params['sort1'] == 'n_rating' || $e_params['sort1'] == 'v.vr_num' || $e_params['sort1'] == 'v.vr_count')
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
       	if ($e_params['sort2'] == 'n_rating' || $e_params['sort2'] == 'v.vr_num' || $e_params['sort2'] == 'v.vr_count')
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
       	if ($e_params['sort3'] == 'n_rating' || $e_params['sort3'] == 'v.vr_num' || $e_params['sort3'] == 'v.vr_count')
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

	if ($ndl_checked != '')
	{
		$ndl_checked = explode(' ', $ndl_checked);
		foreach ($ndl_checked as $value_tmp)
		{
			$elems_fields_where_sql .= ' AND n.user_f_'.$value_tmp.'=1';
		}
	}

	$now = time();
	if ($e_params['filter'] == 'last')
	{
		$last = intval($e_params['filter_last']) - 1;
		$last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND n.n_date >= '.$last.' AND n.n_date <= '.$now;
	}
	elseif ($e_params['filter'] == 'next')
	{
		$next = intval($e_params['filter_next']);
		$next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND n.n_date >= '.$now.' AND n.n_date <= '.$next;
	}

	// используется ли группировка по разделам
	if ($ndl_top_cat != '' || $ndl_bottom_cat != '')
	{
		$categs_tmp_output = true;
	}
	else
	{
		$categs_tmp_output = false;
	}

    $group_str = '';
    $group_res = sql_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids_tmp);
    if ($group_res && $group_res[0][0] > 0 || $comments_sorting)
    {
		$group_str = ' GROUP BY n.n_id';
	}

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL,';
    if($votes_apply ||
		sb_strpos($ndl_element, '{RATING}') !== false ||
		sb_strpos($ndl_element, '{VOTES_COUNT}') !== false ||
		sb_strpos($ndl_element, '{VOTES_SUM}') !== false ||
		sb_strpos($ndl_element, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=n.n_id AND v.vr_plugin="pl_news" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS n_rating, ';
	}

    if($comments_sorting)
    {
    	$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = 'LEFT JOIN sb_comments com ON (com.c_el_id=n.n_id AND com.c_plugin="pl_news" AND com.c_show=1)';
	}
	else
    {
		$com_sort_fields = 'NULL, NULL';
		$com_sort_sql = '';
	}

	if($categs_tmp_output)
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', n.n_date');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' n.n_date');
	}

	$res_tmp = sql_query('SELECT STRAIGHT_JOIN l.link_cat_id, n.n_id, n.n_url, n.n_title,
				'.$votes_fields.
				$com_sort_fields.'
		FROM sb_news n
				'.$votes_sql.'
				'.$com_sort_sql.'
				, sb_catlinks l'.
				(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? ', sb_categs c' : '').'
		WHERE '.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
				(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=n.n_id'.
				$elems_fields_where_sql.'
                AND n.n_active IN ('.sb_get_workflow_demo_statuses().')
                AND (n.n_pub_start IS NULL OR n.n_pub_start <= '.$now.')
                AND (n.n_pub_end IS NULL OR n.n_pub_end >= '.$now.')'.
                $user_link_id_sql.' '.
				$group_str.' '.
				($categs_tmp_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql), $cat_ids_tmp);

	if(!$res_tmp)
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
									    ($res_tmp[$n_prev][2] != '' ? urlencode($res_tmp[$n_prev][2]) : $res_tmp[$n_prev][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] != '' ? '?news_uid='.$_REQUEST['news_uid'] : '');
			}
			else
			{
				$result['href_prev'] .= '?news_cid='.$res_tmp[$n_prev][0].'&news_id='.$res_tmp[$n_prev][1].(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] != '' ? '&news_uid='.$_REQUEST['news_uid'] : '');
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
									($res_tmp[$n_next][2] != '' ? urlencode($res_tmp[$n_next][2]) : $res_tmp[$n_next][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] != '' ? '?news_uid='.$_REQUEST['news_uid'] : '');
			}
			else
			{
				$result['href_next'] .= '?news_cid='.$res_tmp[$n_next][0].'&news_id='.$res_tmp[$n_next][1].(isset($_REQUEST['news_uid']) && $_REQUEST['news_uid'] != '' ? '&news_uid='.$_REQUEST['news_uid'] : '');
			}
		}
	}
}

?>