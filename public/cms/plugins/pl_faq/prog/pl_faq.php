<?php

/**
 * Вывод списка вопросов
 */
function fFaq_Elem_List($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
    if(!isset($GLOBALS['sbCache']))
    {
       $GLOBALS['sbCache'] = sbCache::factory();
    }
	if ($GLOBALS['sbCache']->check('pl_faq', $tag_id, array($el_id, $temp_id, $params)))
        return;

	if (isset($_GET['faq_sid']))
	{
		$res = sql_query('SELECT COUNT(*) FROM sb_faq WHERE f_url=? OR f_id=?d', $_GET['faq_sid'], $_GET['faq_sid']);
		if (!$res || $res[0][0] == 0)
	    	sb_404();
	}

	// вытаскиваем макет дизайна
	//$res = sql_param_query('SELECT fdl_lang, fdl_checked, fdl_count, fdl_top, fdl_categ_top, fdl_element, fdl_empty, fdl_delim,
	//			fdl_categ_bottom, fdl_bottom, fdl_pagelist_id, fdl_perpage, fdl_no_questions, fdl_fields_temps, fdl_categs_temps, fdl_votes_id, fdl_comments_id, fdl_user_data_id, fdl_tags_list_id
	//			FROM sb_faq_temps_list WHERE fdl_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_faq_temps_list', $temp_id);
	if (!$res)
    {
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
		$GLOBALS['sbCache']->save('pl_faq', '');
		return;
	}

	list($fdl_lang, $fdl_checked, $fdl_count, $fdl_top, $fdl_categ_top, $fdl_element, $fdl_empty, $fdl_delim,
	 	 $fdl_categ_bottom, $fdl_bottom, $fdl_pagelist_id, $fdl_perpage, $fdl_no_questions, $fdl_fields_temps, $fdl_categs_temps,
		 $fdl_votes_id, $fdl_comments_id, $fdl_user_data_id, $fdl_tags_list_id) = $res[0];

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

    if(!isset($params['page']))
    	$params['page'] = '';

	$cat_ids = array();

	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['faq_scid']) || isset($_GET['faq_cid'])))
	{
        // используется связь с выводом разделов и выводить следует вопросы из соотв. раздела
        if (isset($_GET['faq_cid']))
        {
        	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['faq_cid']);
        	if ($res[0][0] > 0)
				$cat_ids[] = intval($_GET['faq_cid']);
        }
		else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_faq"', $_GET['faq_scid']);
            if ($res)
            {
				$cat_ids[] = $res[0][0];
            }
            else
            {
            	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['faq_scid']);
        		if ($res[0][0] > 0)
                	$cat_ids[] = intval($_GET['faq_scid']);
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
							AND c.cat_ident="pl_faq"
							AND c2.cat_ident = "pl_faq"
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
        	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['faq_scid']) || isset($_GET['faq_cid'])))
            {
            	sb_404();
            }

            // указанные разделы были удалены
			$GLOBALS['sbCache']->save('pl_faq', $fdl_no_questions);
			return;
        }
    }

    // проверяем, есть ли закрытые разделы среди тех, которые надо выводить
    $comments_read_cat_ids = $cat_ids; // разделы, для которых есть права comments_read
    $comments_edit_cat_ids = $cat_ids; // разделы, для которых есть права comments_edit
    $vote_cat_ids = $cat_ids; // разделы, для которых есть права vote

    //$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    $res = sbQueryCache::query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
    	$closed_ids = array();
    	foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_faq_read');
        $comments_read_cat_ids = sbAuth::checkRights($closed_ids, $comments_read_cat_ids, 'pl_faq_comments_read');
        $comments_edit_cat_ids = sbAuth::checkRights($closed_ids, $comments_edit_cat_ids, 'pl_faq_comments_edit');
		$vote_cat_ids = sbAuth::checkRights($closed_ids, $vote_cat_ids, 'pl_faq_vote');
	}

	if (count($cat_ids) == 0)
	{
		// указанные разделы были удалены
		$GLOBALS['sbCache']->save('pl_faq', $fdl_no_questions);
		return;
	}

	if (trim($fdl_fields_temps) != '')
		$fdl_fields_temps = unserialize($fdl_fields_temps);
	else
		$fdl_fields_temps = array();

	if (trim($fdl_categs_temps) != '')
		$fdl_categs_temps = unserialize($fdl_categs_temps);
	else
		$fdl_categs_temps = array();

    // вытаскиваем макет дизайна постраничного вывода
    $res = sbQueryCache::getTemplate('sb_pager_temps', $fdl_pagelist_id);

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
			$user_link_id_sql = ' AND f.f_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			$GLOBALS['sbCache']->save('pl_faq', $fdl_no_questions);
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] == -1 && isset($params['use_filter']) && $params['use_filter'] == 1)
		{
			$GLOBALS['sbCache']->save('pl_faq', $fdl_no_questions);
			return;
		}
		elseif(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] > 0 && isset($params['use_filter']) && $params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND f.f_user_id = '.intval($_REQUEST['faq_uid']);
		}
	}

// 	вытаскиваем пользовательские поля вопроса и раздела
	//$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_faq"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_faq');

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
    	else
    	{
    		$elems_fields = array();
    	}

    	if($res[0][1] != '')
    	{
            $categs_fields = unserialize($res[0][1]);
    	}
    	else
    	{
            $categs_fields = array();
    	}

        if ($elems_fields)
        {
            foreach ($elems_fields as $value)
            {
            	if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $elems_fields_select_sql .= ', f.user_f_'.$value['id'];

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
    if ($fdl_checked != '')
    {
        $fdl_checked = explode(' ', $fdl_checked);
        foreach ($fdl_checked as $value)
        {
            $elems_fields_where_sql .= ' AND f.user_f_'.$value.'=1';
        }
    }
    $now = time();
    if (isset($params['filter']) && $params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND f.f_date >= '.$last.' AND f.f_date <= '.$now;
    }

    if(isset($params['filter']) && $params['show_all'] == 0)
    {
        $elems_fields_where_sql .= ' AND f.f_answer != "" ';
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

    	$elems_fields_where_sql .= ' AND f.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'" AND f.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'"';
    }

	// Отключаем выводимый вопрос, если выводится подробный ответ
	if (isset($params['show_selected']) && $params['show_selected'] == 1 && (isset($_GET['faq_sid']) || isset($_GET['faq_id'])))
	{
		if (isset($_GET['faq_id']))
		{
			$elems_fields_where_sql .= ' AND f.f_id != "'.intval($_GET['faq_id']).'"';
		}
		else
		{
			$res = sql_param_query('SELECT f_id FROM sb_faq WHERE f_url=?', $_GET['faq_sid']);
			if ($res)
			{
				$elems_fields_where_sql .= ' AND f.f_id != "'.$res[0][0].'"';
			}
			else
			{
				$elems_fields_where_sql .= ' AND f.f_id != "'.intval($_GET['faq_sid']).'"';
			}
		}
	}

    // связь с выводом облака тегов
    $cloud_where_sql = '';
	$f_tag = '';

	if (isset($params['cloud']) && $params['cloud'] == 1 && isset($_REQUEST['f_tag']) && $_REQUEST['f_tag'] != '')
    {
    	$tag = trim(preg_replace('/[^0-9\,\s]+/', '', $_REQUEST['f_tag']));
    	if ($tag != '')
			$f_tag .= $tag;
    }

	if(isset($params['cloud_comp']) && $params['cloud_comp'] == 1)
	{
		if (isset($_REQUEST['f_tag_comp']) && $_REQUEST['f_tag_comp'] != '')
		{
			$tag = trim(preg_replace('/[^0-9\,\s\-]+/', '', $_REQUEST['f_tag_comp']));
			if ($tag != '')
				$f_tag .= ($f_tag != '' ? ',' : '').$tag;
		}
		else
		{
			$GLOBALS['sbCache']->save('pl_faq', $fdl_no_questions);
			return;
		}
	}

	if ($f_tag != '')
		$cloud_where_sql = ' AND cl.cl_ident="pl_faq" AND f.f_id=cl.cl_el_id AND cl.cl_tag_id IN ('.$f_tag.')';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	// формируем SQL-запрос для фильтра
	$elems_fields_filter_sql = '';
	if (isset($params['use_filter']) && $params['use_filter'] == 1)
	{
    	$date_temp = '';
		if(isset($_REQUEST['f_f_temp_id']))
    	{
			$date = sql_param_query('SELECT sftf_fields_temps FROM sb_faq_temps_form WHERE sftf_id = ?d', $_REQUEST['f_f_temp_id']);
			if($date)
			{
				list($sftf_fields_temps) = $date[0];
				$sftf_fields_temps = unserialize($sftf_fields_temps);
				$date_temp = $sftf_fields_temps['date_temps'];
			}
		}

		$morph_db = false;
		if (isset($params['filter_morph']) && $params['filter_morph'] == 1)
		{
			require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
		}

		$elems_fields_filter_sql = '(';

    	$elems_fields_filter_sql .= sbGetFilterNumberSql('f.f_id', 'f_f_id', $params['filter_logic']);
		$elems_fields_filter_sql .= sbGetFilterNumberSql('f.f_date', 'f_f_date', $params['filter_logic'], true, $date_temp);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('f.f_user_id', 'f_f_user_id', $params['filter_logic']);

		$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_author', 'f_f_author', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_email', 'f_f_email', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_phone', 'f_f_phone', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_question', 'f_f_question', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_answer', 'f_f_answer', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

        $elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'f', 'f_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
    }

    if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
    	$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
	else
		$elems_fields_filter_sql = '';

    // формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
    $votes_apply = $comments_sorting = false;
    if(isset($params['use_sort']) && $params['use_sort'] == '1' && isset($_REQUEST['s_f_faq']) && trim($_REQUEST['s_f_faq']) != '')
	{
		$elems_fields_sort_sql .= sbLayout::getPluginFieldsSortSql('faq', 'f');
	}
	else
	{
	    if (isset($params['sort1']) && $params['sort1'] != '')
	    {
			if ($params['sort1'] == 'com_count' || $params['sort1'] == 'com_date')
			{
				$comments_sorting = true;
			}
			if ($params['sort1'] == 'f_rating' || $params['sort1'] == 'v.vr_num' || $params['sort1'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

	        $elems_fields_sort_sql .= ', '.$params['sort1'];

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
			if ($params['sort2'] == 'f_rating' || $params['sort2'] == 'v.vr_num' || $params['sort2'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

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
	    	if ($params['sort3'] == 'com_count' || $params['sort3'] == 'com_date')
			{
				$comments_sorting = true;
			}

			if ($params['sort3'] == 'f_rating' || $params['sort3'] == 'v.vr_num' || $params['sort3'] == 'v.vr_count')
			{
				$votes_apply = true;
			}

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
	}

    // используется ли группировка по разделам
    if ($fdl_categ_top != '' || $fdl_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }


	// Название для куки содержащего кол-во элементов на страничке
	require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
    $num_cookie_name = 'pl_faq_'.$temp_id.'_'.$tag_id;
    $pager = new sbDBPager($tag_id, $pt_perstage, $fdl_perpage, '',  $num_cookie_name);

	if (isset($params['filter']) && $params['filter'] == 'from_to')
    {
		$pager->mFrom = intval($params['filter_from']);
		$pager->mTo = intval($params['filter_to']);
    }

 	// Если вопросы подгружаются как связанные, выводить не раздел, а список конкретных роликов
    $sql_linked = '';
    if($linked != 0)
    {
    	$sql_linked = ' AND f.f_id IN ('.$linked.') ';
    }

	$group_str = '';
    $group_res = sql_param_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
    if ($group_res && $group_res[0][0] > 0 || $comments_sorting || $cloud_where_sql != '')
    {
    	$group_str = ' GROUP BY f.f_id ';
	}

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL,';
    if($votes_apply ||
		sb_strpos($fdl_element, '{RATING}') !== false ||
		sb_strpos($fdl_element, '{VOTES_COUNT}') !== false ||
		sb_strpos($fdl_element, '{VOTES_SUM}') !== false ||
		sb_strpos($fdl_element, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=f.f_id AND v.vr_plugin="pl_faq" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS f_rating, ';
	}

    if($comments_sorting)
    {
    	$com_sort_fields = ' COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = ' LEFT JOIN sb_comments com ON (com.c_el_id=f.f_id AND com.c_plugin="pl_faq" AND com.c_show=1)';
	}
	else
    {
		$com_sort_fields = ' NULL, NULL ';
		$com_sort_sql = '';
	}

    if($categs_output)
    {
    	$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', f.f_date');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' f.f_date');
	}

	//	выборка вопросов, которые следует выводить
	$faq_total = true;
	$res = $pager->init($faq_total, 'SELECT l.link_cat_id, f.f_id, f.f_author, f.f_date, f.f_answer, f.f_question, f.f_url, f.f_email, f.f_phone, f.f_user_id,
				'.$votes_fields.
				$com_sort_fields.
				$elems_fields_select_sql.'
			FROM sb_faq f
				'.$votes_sql.
				$com_sort_sql.'
				, sb_catlinks l'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').
				($cloud_where_sql != '' ? ', sb_clouds_links cl' : '').'
			WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
					(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=f.f_id
				'.$elems_fields_where_sql.
				$elems_fields_filter_sql.
				$cloud_where_sql.'
				AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
				AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
				AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
				'.$sql_linked.$user_link_id_sql.$group_str.'
				'.($categs_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql), $cat_ids);

	if(!$res)
	{
		$GLOBALS['sbCache']->save('pl_faq', $fdl_no_questions);
		return;
	}
	$count_questions = $pager->mFrom + 1;

    $comments_count = array();
    if(sb_strpos($fdl_element, '{COUNT_COMMENTS}') !== false)
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
	        $comments_count = fComments_Get_Count($ids_arr, 'pl_faq');
	    }
    }

	$categs = array();
    if (sb_substr_count($fdl_categ_top, '{CAT_COUNT}') > 0 ||
        sb_substr_count($fdl_categ_bottom, '{CAT_COUNT}') > 0 ||
        sb_substr_count($fdl_element, '{CAT_COUNT}') > 0
       )
	{
		$res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
				(
	                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_faq f
	                WHERE c.cat_id = c1.cat_id
	                AND l.link_el_id=f.f_id
					AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
	                AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
	                AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
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

    // верх вывода списка вопросов
    $flds_tags = array( '{SORT_ID_ASC}' ,'{SORT_ID_DESC}',
    					'{SORT_AUTHOR_ASC}' ,'{SORT_AUTHOR_DESC}',
    					'{SORT_EMAIL_ASC}' ,'{SORT_EMAIL_DESC}',
    					'{SORT_PHONE_ASC}' ,'{SORT_PHONE_DESC}',
    					'{SORT_DATE_ASC}' ,'{SORT_DATE_DESC}',
    					'{SORT_QUESTION_ASC}' ,'{SORT_QUESTION_DESC}',
    					'{SORT_ANSWER_ASC}' ,'{SORT_ANSWER_DESC}',
    					'{SORT_SORT_ASC}' ,'{SORT_SORT_DESC}',
    					'{SORT_SHOW_ASC}' ,'{SORT_SHOW_DESC}',
    					'{SORT_USER_ID_ASC}' ,'{SORT_USER_ID_DESC}');

    $query_str = $_SERVER['QUERY_STRING'];
    if(isset($_GET['s_f_faq']))
    {
    	$query_str = preg_replace('/[?&]?s_f_faq['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
    }

    $flds_href = (isset($GLOBALS['PHP_SELF']) ? $GLOBALS['PHP_SELF'] : '').(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_faq=';

    $flds_vals = array( $flds_href.urlencode('f_id=ASC'),
    					$flds_href.urlencode('f_id=DESC'),
    					$flds_href.urlencode('f_author=ASC'),
    					$flds_href.urlencode('f_author=DESC'),
    					$flds_href.urlencode('f_email=ASC'),
    					$flds_href.urlencode('f_email=DESC'),
    					$flds_href.urlencode('f_phone=ASC'),
    					$flds_href.urlencode('f_phone=DESC'),
    					$flds_href.urlencode('f_date=ASC'),
    					$flds_href.urlencode('f_date=DESC'),
    					$flds_href.urlencode('f_question=ASC'),
    					$flds_href.urlencode('f_question=DESC'),
    					$flds_href.urlencode('f_answer=ASC'),
    					$flds_href.urlencode('f_answer=DESC'),
    					$flds_href.urlencode('f_sort=ASC'),
    					$flds_href.urlencode('f_sort=DESC'),
    					$flds_href.urlencode('f_show=ASC'),
    					$flds_href.urlencode('f_show=DESC'),
    					$flds_href.urlencode('f_user_id=ASC'),
    					$flds_href.urlencode('f_user_id=DESC'));

    sbLayout::getPluginFieldsTagsSort('faq', $flds_tags, $flds_vals, 'href_replace');

 	// Заменяем значение селекта "Кол-во на странице" селектед
	if(isset($_REQUEST['num_'.$tag_id]))
    {
    	$fdl_top = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $fdl_top);
    }
    elseif(isset($_COOKIE[$num_cookie_name]))
    {
    	$fdl_top = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $fdl_top);
    }

    $result = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $faq_total, 'num_'.$tag_id),$flds_vals), $fdl_top);
    $tags = array_merge($tags, array('{QUESTION}',
                                    '{ANSWER}',
									'{DATE}',
    								'{CHANGE_DATE}',
									'{AUTHOR}',
									'{EMAIL}',
									'{PHONE}',
									'{ID}',
                                    '{ELEM_URL}',
									'{LINK}',
                                    '{USER_DATA}',
									'{ELEM_USER_LINK}',
									'{ELEM_NUMBER}',
    								'{TAGS}',
									'{CAT_ID}',
                                    '{CAT_URL}',
									'{CAT_TITLE}',
									'{CAT_COUNT}',
                                    '{CAT_LEVEL}',
                                    '{MORE}',
                                    '{RATING}',
                                    '{VOTES_COUNT}',
                                    '{VOTES_SUM}',
                                    '{VOTES_FORM}',
                                    '{COUNT_COMMENTS}',
                                    '{FORM_COMMENTS}',
                                    '{LIST_COMMENTS}'));
	$cur_cat_id = 0;
    $values = array();
    $cat_values = array();
    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $col = 0;

    $dop_tags = array('{ID}', '{ELEM_URL}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');

    list($more_page, $more_ext) = sbGetMorePage($params['page']);

	$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
	if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
	{
		$auth_page = '/'.$auth_page;
	}

    $view_rating_form = (sb_strpos($fdl_element, '{VOTES_FORM}') !== false && $fdl_votes_id > 0);
    $view_comments_list = (sb_strpos($fdl_element, '{LIST_COMMENTS}') !== false && $fdl_comments_id > 0);
    $view_comments_form = (sb_strpos($fdl_element, '{FORM_COMMENTS}') !== false && $fdl_comments_id > 0);

    if(sb_strpos($fdl_element, '{TAGS}') !== false)
	{
		// Достаю макеты для вывода списка тегов элементов
	    $tags_template_error = false;
	    $res1 = sql_param_query('SELECT ct_pagelist_id, ct_perpage
	                FROM sb_clouds_temps WHERE ct_id=?d', $fdl_tags_list_id);

	    if (!$res1)
	       $tags_template_error = true;

	    list($ct_pagelist_id, $ct_perpage) = $res1[0];

	        // Вытаскиваем макет дизайна постраничного вывода
	    $res1 = sbQueryCache::getTemplate('sb_pager_temps', $ct_pagelist_id);

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

	require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
	require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');

	foreach ($res as $value)
	{
		$value[6] = urlencode($value[6]); // ELEM_URL

    	$old_values = $values;
    	$values = array();
        $more = '';

        if ($value[0] != $cur_cat_id)
        {
            $cat_values = array();
        }

    	if (trim($value[4]) == '' || $more_page == '')
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
                            ($value[6] != '' ? $value[6] : $value[1]).($more_ext != 'php' ? '.'.$more_ext : '/');
            }
            else
            {
            	$href .= '?faq_cid='.$value[0].'&faq_id='.$value[1];
            }
        }

        $dop_values = array($value[1], $value[6], $value[0], $categs[$value[0]]['url'], strip_tags($categs[$value[0]]['title']), $href);

        $answer = trim(sb_short_text($value[4], intval($fdl_fields_temps['f_count_char'])));

	    if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
		{
			//Если разрешен bb код
	    	$answer = sbProgParseBBCodes($answer);
		}

        if ($answer != '' && trim($value[4]) != $answer)
        {
        	$more = str_replace($dop_tags, $dop_values, $fdl_fields_temps['f_more']);
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
            $values = sbLayout::parsePluginFields($elems_fields, $values, $fdl_fields_temps, $dop_tags, $dop_values, $fdl_lang, '', '', $allow_bb, $link_level, $fdl_element);
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

				$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $fdl_categs_temps, $dop_tags, $dop_values, $fdl_lang, '', '', $allow_bb, $link_level, $fdl_categ_top.$fdl_element.$fdl_categ_bottom);
            }
            $values = array_merge($values, $cat_values);
        }

    	if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
		{
			//Если разрешен bb код
	    	$value[5] = sbProgParseBBCodes($value[5]);
		}
        $values[] = trim($value[5]) != '' ? str_replace(array_merge(array('{QUESTION}'), $dop_tags),
                array_merge(array($value[5]), $dop_values), $fdl_fields_temps['f_question']):'';          // QUESTION

        $values[] = ($answer != '') ? str_replace(array_merge(array('{ANSWER}'), $dop_tags),
                array_merge(array($answer), $dop_values), $fdl_fields_temps['f_answer']):'';            // ANSWER
        $values[] = sb_parse_date($value[3], $fdl_fields_temps['f_date'], $fdl_lang);                     // DATE
    	// Дата последнего изменения
        if(sb_strpos($fdl_element, '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_faq');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $fdl_fields_temps['f_change_date'], $fdl_lang) : ''; //   CHANGE_DATE
        }
        else
       	{
        	$values[] = '';
       	}

        $values[] = trim($value[2]) != '' ? str_replace(array_merge(array('{AUTHOR}'), $dop_tags),
                array_merge(array($value[2]), $dop_values), $fdl_fields_temps['f_author']):'';            // AUTHOR
        $values[] = trim($value[7]) != '' ? str_replace(array_merge(array('{EMAIL}'), $dop_tags),
                array_merge(array($value[7]), $dop_values), $fdl_fields_temps['f_email']):'';             // EMAIL
        $values[] = trim($value[8]) != '' ? str_replace(array_merge(array('{PHONE}'), $dop_tags),
                array_merge(array($value[8]), $dop_values), $fdl_fields_temps['f_phone']):'';             // PHONE
        $values[] = $value[1];  // ID
        $values[] = $value[6];  // ELEM_URL
        $values[] = $href;      // LINK

        if($fdl_user_data_id > 0 && isset($value[9]) && $value[9] > 0 && sb_strpos($fdl_element, '{USER_DATA}') !== false)
        {
            require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
            $values[] = fSite_Users_Get_Data($fdl_user_data_id, $value[9]); //     USER_DATA
        }
		else
        {
			$values[] = '';   //   USER_DATA
        }

        if(isset($value[9]) && $value[9] > 0 && isset($fdl_fields_temps['f_registred_users']) && $fdl_fields_temps['f_registred_users'] != '' )
        {
			$action = $auth_page.'?faq_uid='.$value[9].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');   //   ELEM_USER_LINK
			$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $fdl_fields_temps['f_registred_users']);
        }
        else
        {
			$values[] = '';		//   ELEM_USER_LINK
        }

		$values[] = $count_questions++;		//		ELEM_NUMBER
		// Список тэгов
		if(sb_strpos($fdl_element, '{TAGS}') !== false)
		{
			$tags_error = false;
			$pager_tags = new sbDBPager('t_'.$value[1], $pt_perstage, $ct_perpage);

			// Вытаскиваю теги
			$tags_total = true;
			$res_tags = $pager_tags->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_clouds_links cl
	                            WHERE cl.cl_tag_id IN
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_faq" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_faq"
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
				$values[] = fClouds_Show($res_tags, $fdl_tags_list_id,  $pt_page_list_tags_1, $tags_total, '', 'f_tag'); //     TAGS
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

        $values[] = $value[0];  // CAT_ID
        $values[] = $categs[$value[0]]['url'];  // CAT_URL
        $values[] = $categs[$value[0]]['title']; // CAT_TITLE
        $values[] = $categs[$value[0]]['count']; // CAT_COUNT
        $values[] = $categs[$value[0]]['level']; // CAT_LEVEL
        $values[] = ($more && $more != '')? str_replace($dop_tags, $dop_values, $fdl_fields_temps['f_more']):'';  // MORE

        $votes_sum = (isset($value[10]) && $value[10] != '' && !is_null($value[10]) ? $value[10] : 0);
        $votes_count = (isset($value[11]) && $value[11] != '' && !is_null($value[11]) ? $value[11] : 0);
        $votes_rating = (isset($value[12]) && $value[12] != '' && !is_null($value[12]) ? sprintf('%.2f', $value[12]) : 0);

        if ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1)
        {
	        // VOTES_FORM
	        require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
	        $res_vote = fVoting_Form_Submit($fdl_votes_id, 'pl_faq', $value[1], $votes_sum, $votes_count, $votes_rating);
        }

        $values[] = $votes_sum; // VOTES_SUM
        $values[] = $votes_count; // VOTES_COUNT
        $values[] = $votes_rating; // RATING

        if($view_rating_form && ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1))
        {
            $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($fdl_votes_id, 'pl_faq', $value[1]));
        }
        else
        {
			$values[] = '';
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

			if (fComments_Add_Comment($fdl_comments_id, 'pl_faq', $value[1], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
				$c_count++;
        }

        $values[] = $c_count; // COUNT_COMMENTS

        if ($view_comments_form)
        {
			$values[] = fComments_Get_Form($fdl_comments_id, 'pl_faq', $value[1], $add_comments); // FORM_COMMENTS
        }
        else
        {
            $values[] = ''; // FORM_COMMENTS
        }

        if ($view_comments_list)
        {
			$exists_rights = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_read_cat_ids) || $categs[$value[0]]['closed'] != 1);
            $values[] = fComments_Get_List($fdl_comments_id, 'pl_faq', $value[1], $add_comments, '', 0, $exists_rights); // LIST_COMMENTS
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
                while ($col < $fdl_count)
                {
                    $result .= $fdl_empty;
                    $col++;
                }
                $result .= str_replace($tags, $old_values, $fdl_categ_bottom);
            }

            // верх вывода раздела
            $result .= str_replace($tags, $values, $fdl_categ_top);
            $col = 0;
        }

        if ($col >= $fdl_count)
        {
            $result .= $fdl_delim;
            $col = 0;
        }

        $result .= str_replace($tags, $values, $fdl_element);
        $cur_cat_id = $value[0];
        $col++;
    }

    while ($col < $fdl_count)
    {
    	$result .= $fdl_empty;
        $col++;
    }

    if ($categs_output)
    {
        // низ вывода раздела
        $result .= str_replace($tags, $values, $fdl_categ_bottom);
    }

    // низ вывода списка вопросов
	if(isset($_REQUEST['num_'.$tag_id])&& sb_strpos($fdl_bottom,'{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}'))
    {
    	$fdl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $fdl_bottom);
    }
    elseif(isset($_COOKIE[$num_cookie_name]) && sb_strpos($fdl_bottom,'{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}'))
    {
    	$fdl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $fdl_bottom);
    }

    $result .= str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $faq_total, 'num_'.$tag_id),$flds_vals), $fdl_bottom);

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    if($linked == 0)
    {
    	$GLOBALS['sbCache']->save('pl_faq', $result);
    }
    else
    {
    	return $result;
    }
}

/**
 * Вывод полного текста вопросов
 */
function fFaq_Elem_Full($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
	$params = unserialize(stripslashes($params));
	if($linked > 0)
		$_GET['faq_id'] = $linked;

    if ($f_id = $GLOBALS['sbCache']->check('pl_faq', $tag_id, array($el_id, $temp_id, $params)))
    {
        if($linked > 0)
            unset($_GET['faq_id']);

    	@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
    	fClouds_Init_Tags('pl_faq', array($f_id));
    	return;
    }

    if(!isset($_GET['faq_sid']) && !isset($_GET['faq_id']))
    {
        if($linked > 0)
            unset($_GET['faq_id']);

    	return;
    }

    $cat_id = -1;
    if (isset($_GET['faq_scid']) || isset($_GET['faq_cid']))
    {
    	// используется связь с выводом разделов и выводить следует вопросы из соотв. раздела
    	if (isset($_GET['faq_cid']))
    	{
    		$cat_id = intval($_GET['faq_cid']);
    	}
        else
        {
        	$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_faq"', $_GET['faq_scid']);
        	if ($res)
        	{
        		$cat_id = $res[0][0];
        	}
            else
            {
                $cat_id = intval($_GET['faq_scid']);
            }
        }
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT ftf_lang, ftf_fullelement, ftf_fields_temps, ftf_categs_temps, ftf_checked, ftf_votes_id, ftf_comments_id, ftf_user_data_id, ftf_tags_list_id
    //            FROM sb_faq_temps_full WHERE ftf_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_faq_temps_full', $temp_id);

    if (!$res)
    {
        if($linked > 0)
            unset($_GET['faq_id']);

        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_faq', '');
        return;
    }

    list($ftf_lang, $ftf_fullelement, $ftf_fields_temps, $ftf_categs_temps, $ftf_checked, $ftf_votes_id, $ftf_comments_id, $ftf_user_data_id, $ftf_tags_list_id) = $res[0];

    $ftf_fields_temps = unserialize($ftf_fields_temps);
    $ftf_categs_temps = unserialize($ftf_categs_temps);

    // вытаскиваем пользовательские поля вопроса и раздела
    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_faq"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_faq');

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
                    $elems_fields_select_sql .= ', f.user_f_'.$value['id'];

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
			$user_link_id_sql = ' AND f.f_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			if($linked > 0)
    		{
    		    unset($_GET['faq_id']);
    			sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
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
	if ($ftf_checked != '')
    {
        $ftf_checked = explode(' ', $ftf_checked);
        foreach ($ftf_checked as $value)
        {
            $elems_fields_where_sql .= ' AND f.user_f_'.$value.'=1';
        }
    }

    $now = time();
    if ($cat_id != -1 && $linked < 1)
    {
        $cat_dop_sql = 'AND c.cat_id="'.$cat_id.'"';
    }
    else
    {
        $cat_dop_sql = 'AND c.cat_ident="pl_faq"';
    }

    if (isset($_GET['faq_id']))
    {
    	$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                          f.f_id, f.f_author, f.f_date, f.f_answer, f.f_question, f.f_url, f.f_email, f.f_phone, f.f_user_id,
                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS f_rating
                          '.$elems_fields_select_sql.'
                          FROM sb_faq f LEFT JOIN sb_vote_results v ON v.vr_el_id = ?d AND v.vr_plugin="pl_faq", sb_categs c, sb_catlinks l
                          WHERE f.f_id=?d AND l.link_el_id=f.f_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
				          AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
        		          AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
		                  AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
                          '.$elems_fields_where_sql.$user_link_id_sql, $_GET['faq_id'], $_GET['faq_id']);
    }
    else
    {
		$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                          f.f_id, f.f_author, f.f_date, f.f_answer, f.f_question, f.f_url, f.f_email, f.f_phone, f.f_user_id,
                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS f_rating
                          '.$elems_fields_select_sql.'
                          FROM sb_faq f LEFT JOIN sb_vote_results v ON v.vr_el_id = f.f_id AND v.vr_plugin="pl_faq", sb_categs c, sb_catlinks l
                          WHERE f.f_url=? AND l.link_el_id=f.f_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
				          AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
        		          AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
		                  AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
                          '.$elems_fields_where_sql.$user_link_id_sql, $_GET['faq_sid']);
		if (!$res)
        {
			$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                          f.f_id, f.f_author, f.f_date, f.f_answer, f.f_question, f.f_url, f.f_email, f.f_phone, f.f_user_id,
                          v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS f_rating
                          '.$elems_fields_select_sql.'
                          FROM sb_faq f LEFT JOIN sb_vote_results v ON v.vr_el_id = ?d AND v.vr_plugin="pl_faq", sb_categs c, sb_catlinks l
                          WHERE f.f_id=?d AND l.link_el_id=f.f_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
				          AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
        		          AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
		                  AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
                          '.$elems_fields_where_sql.$user_link_id_sql, $_GET['faq_sid'], $_GET['faq_sid']);
        }
    }

    if (!$res)
    {
    	if($linked > 0)
    	{
    	    unset($_GET['faq_id']);
    		sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
    		return;
    	}
    	else
    	{
			sb_404();
    	}
    }

    $view_rating_form = (sb_strpos($ftf_fullelement, '{VOTES_FORM}') !== false && $ftf_votes_id > 0);
    $view_comments_list = (sb_strpos($ftf_fullelement, '{LIST_COMMENTS}') !== false && $ftf_comments_id > 0);
    $view_comments_form = (sb_strpos($ftf_fullelement, '{FORM_COMMENTS}') !== false && $ftf_comments_id > 0);
    $add_rating = true;
    $add_comments = true;

    if ($res[0][4])
    {
        $cat_ids = sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_faq_read');
        if (count($cat_ids) == 0)
        {
            if($linked > 0)
                unset($_GET['faq_id']);

            $GLOBALS['sbCache']->save('pl_faq', '');
            return;
        }

        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_faq_vote')) == 0)
        {
            $view_rating_form = false;
            $add_rating = false;
        }

        if ($view_comments_list && count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_faq_comments_read')) == 0)
        {
            $view_comments_list = false;
        }

        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_faq_comments_edit')) == 0)
        {
            $add_comments = false;
        }
    }

    $cat_count = '';
    if(sb_substr_count($ftf_fullelement, '{CAT_COUNT}') > 0)
    {
        $res_cat = sql_param_query('SELECT COUNT(*) FROM sb_catlinks
					                WHERE link_cat_id = ?d AND link_src_cat_id != ?d', $res[0][0], $res[0][0]);
        if ($res_cat)
        {
            $cat_count = $res_cat[0][0];
        }
    }

    $comments_count = array();
    if(sb_strpos($ftf_fullelement, '{COUNT_COMMENTS}') !== false)
    {
        require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
        $comments_count = fComments_Get_Count(array($res[0][6]), 'pl_faq');
    }

    $tags = array_merge($tags, array('{CAT_LEVEL}',
                                     '{CAT_COUNT}',
                                     '{CAT_ID}',
                                     '{CAT_URL}',
                                     '{CAT_TITLE}',
                                     '{ID}',
                                     '{ELEM_URL}',
                                     '{AUTHOR}',
                                     '{DATE}',
    								 '{CHANGE_DATE}',
                                     '{ANSWER}',
                                     '{QUESTION}',
                                     '{EMAIL}',
                                     '{PHONE}',
                                     '{USER_DATA}',
									 '{ELEM_USER_LINK}',
    								 '{TAGS}',
                                     '{RATING}',
                                     '{VOTES_COUNT}',
                                     '{VOTES_SUM}',
                                     '{VOTES_FORM}',
                                     '{COUNT_COMMENTS}',
                                     '{FORM_COMMENTS}',
                                     '{LIST_COMMENTS}',
									 '{ELEM_PREV}',
    								 '{ELEM_NEXT}'));

    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $values = array();

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $dop_tags = array('{ID}', '{ELEM_URL}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
    $dop_values = array($res[0][6], strip_tags($res[0][11]), $res[0][0], strip_tags($res[0][5]), strip_tags($res[0][1]));

    if ($num_fields > 18)
    {
        for ($i = 18; $i < $num_fields; $i++)
        {
				$values[] = $res[0][$i];
        }
		$allow_bb = 0;
		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
			$allow_bb = 1;
        $values = sbLayout::parsePluginFields($elems_fields, $values, $ftf_fields_temps, $dop_tags, $dop_values, $ftf_lang, '', '', $allow_bb, $link_level, $ftf_fullelement);
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
        $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ftf_categs_temps, $dop_tags, $dop_values, $ftf_lang, '', '', $allow_bb, $link_level, $ftf_fullelement);
        $values = array_merge($values, $cat_values);
    }

	$values[] = $res[0][2];   // CAT_LEVEL
    $values[] = $cat_count;   // CAT_COUNT
    $values[] = $res[0][0];   // CAT_ID
    $values[] = $res[0][5];   // CAT_URL
    $values[] = $res[0][1];   // CAT_TITLE
    $values[] = $res[0][6];   // ID
    $values[] = $res[0][11];  // ELEM_URL

    $values[] = ($res[0][7] != '' ? str_replace(array_merge(array('{AUTHOR}'),$dop_tags), array_merge(array($res[0][7]), $dop_values), $ftf_fields_temps['f_author']) : ''); // AUTHOR
    $values[] = sb_parse_date($res[0][8], $ftf_fields_temps['f_date'], $ftf_lang); // DATE
    // Дата последнего изменения
        if(sb_strpos($ftf_fullelement, '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $res[0][6], 'pl_faq');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $ftf_fields_temps['f_change_date'], $ftf_lang) : ''; //   CHANGE_DATE
        }
 		else
       	{
        	$values[] = '';
       	}

    if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
	{
		//Если разрешен bb код
    	$res[0][9] = sbProgParseBBCodes($res[0][9]);
    	$res[0][10] = sbProgParseBBCodes($res[0][10]);
	}
    $values[] = ($res[0][9] != '' ? str_replace(array_merge(array('{ANSWER}'), $dop_tags), array_merge(array($res[0][9]), $dop_values), $ftf_fields_temps['f_answer']) : ''); // ANSWER
    $values[] = ($res[0][10] != '' ? str_replace(array_merge(array('{QUESTION}'), $dop_tags), array_merge(array($res[0][10]), $dop_values), $ftf_fields_temps['f_question']) : ''); // QUESTION
    $values[] = ($res[0][12] != '' ? str_replace(array_merge(array('{EMAIL}'), $dop_tags), array_merge(array($res[0][12]), $dop_values), $ftf_fields_temps['f_email']) : ''); // EMAIL
    $values[] = ($res[0][13] != '' ? str_replace(array_merge(array('{PHONE}'), $dop_tags), array_merge(array($res[0][13]), $dop_values), $ftf_fields_temps['f_phone']) : ''); // PHONE

    if(isset($res[0][14]) && $res[0][14] != '' &&  sb_strpos($ftf_fullelement, '{USER_DATA}') !== false)
    {
		require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
        $values[] = fSite_Users_Get_Data($ftf_user_data_id, $res[0][14]); //     USER_DATA
    }
    else
    {
		$values[] = '';   //   USER_DATA
    }

    if (isset($res[0][14]) && $res[0][14] > 0 && isset($ftf_fields_temps['f_registred_users']) && $ftf_fields_temps['f_registred_users'] != '')
	{
		$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
		if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
		{
			$auth_page = '/'.$auth_page;
		}

		$action = $auth_page.'?faq_uid='.$res[0][14].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');
		$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $ftf_fields_temps['f_registred_users']);	//	ELEM_USER_LINK
    }
    else
    {
		$values[] = '';
    }
	if(sb_strpos($ftf_fullelement, '{TAGS}') !== false)
	{
	    // Вывод тематических тегов
		$tags_error = false;
	    // вытаскиваем макет дизайна тэгов
	    $res_tags = sql_param_query('SELECT ct_pagelist_id, ct_perpage
	                FROM sb_clouds_temps WHERE ct_id=?d', $ftf_tags_list_id);

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
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_faq" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_faq"
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
			$values[] = fClouds_Show($res_tags, $ftf_tags_list_id,  $pt_page_list_tags, $tags_total, $params['page'], 'f_tag'); //     TAGS
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
    $votes_rating = ($res[0][17] != '' && !is_null($res[0][17]) ? sprintf('%.2f', $res[0][17]) : 0); // RATING
    $votes_count = ($res[0][16] != '' && !is_null($res[0][16]) ? $res[0][16] : 0); // VOTES_COUNT
    $votes_sum = ($res[0][15] != '' && !is_null($res[0][15]) ? $res[0][15] : 0); // VOTES_SUM

	if ($add_rating)
    {
        // VOTES_FORM
        require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
        $res_vote = fVoting_Form_Submit($ftf_votes_id, 'pl_faq', $res[0][6], $votes_sum, $votes_count, $votes_rating);
    }

    $values[] = $votes_rating; // RATING
    $values[] = $votes_count; // VOTES_COUNT
    $values[] = $votes_sum; // VOTES_SUM

    if ($add_rating && $view_rating_form)
    {
        $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($ftf_votes_id, 'pl_faq', $res[0][6]));  // VOTES_FORM
    }
    else
    {
		$values[] = '';
	}

	$c_count = (isset($comments_count[$res[0][6]]) ? $comments_count[$res[0][6]] : 0);	//	COUNT_COMMENTS

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

		if (fComments_Add_Comment($ftf_comments_id, 'pl_faq', $res[0][6], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
            $c_count++;
    }

    $values[] = $c_count;

    if ($view_comments_form)
    {
        $values[] = fComments_Get_Form($ftf_comments_id, 'pl_faq', $res[0][6], $add_comments); // FORM_COMMENTS
    }
    else
    {
        $values[] = ''; // FORM_COMMENTS
    }

    if ($view_comments_list)
    {
		$values[] = fComments_Get_List($ftf_comments_id, 'pl_faq', $res[0][6], $add_comments); // LIST_COMMENTS
    }
	else
    {
		$values[] = ''; // LIST_COMMENTS
    }

    /* Ссылки вперед и назад */
    if(isset($params['page']) && $params['page'] != '')
	{
		$page = $params['page'];
	}
    else
    {
		$page = $GLOBALS['PHP_SELF'];
    }

	$page = str_replace(array('http://'.SB_COOKIE_DOMAIN.'/', 'http://www.'.SB_COOKIE_DOMAIN.'/'), '', $page);

	$im_page = $page;

	$im_page = explode('/', $im_page);
	$im_file_path = '';
	for($i = 0; $i < count($im_page) - 1; $i ++)
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


	$domain = str_ireplace('http://', '', SB_DOMAIN);
	$domain = str_ireplace('www.', '', $domain);
	$domain = str_ireplace('www-demo.', '', $domain);

	$cat_l_r = sql_param_query('SELECT cat_left, cat_right  FROM sb_categs WHERE cat_ident="pl_pages"  AND cat_title=?', $domain);
	$res_cat_ids = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_pages" AND cat_left >= ?d AND cat_right <= ?d', $cat_l_r[0][0], $cat_l_r[0][1]);

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
        	$e_tag = '{'.$params['component'].'}';
        $res_tmp = sql_param_query('SELECT elem.e_temp_id, elem.e_params
					FROM sb_elems elem, sb_pages page LEFT JOIN sb_catlinks l ON l.link_el_id = page.p_id
					WHERE l.link_cat_id IN (?a) AND page.p_filename = ? AND page.p_filepath = ? AND elem.e_p_id = page.p_id
					AND elem.e_ident = "pl_faq_list" AND elem.e_tag = ? LIMIT 1', $cats_ids,  $im_page, $im_file_path, $e_tag);
	}

	if($res_tmp != false)
	{
		list($e_temp_id, $e_params) = $res_tmp[0];
	}


	$href_prev = $href_next = '';

	if($res_tmp != false)
	{
		fFaq_Get_Next_Prev($e_temp_id, $e_params, $href_next, $href_prev, $_SERVER['PHP_SELF'], $res[0][6]);
	}


	if($href_prev == '' || !isset($ftf_fields_temps['f_prev_link']))
	{
		$res_tmp2 = '';
	}
	else
	{
            $res_tmp2 = str_replace(array_merge(array('{PREV_HREF}'), $dop_tags), array_merge(array($href_prev), $dop_values), $ftf_fields_temps['f_prev_link']);
	}

	if($href_next == '' || !isset($ftf_fields_temps['f_next_link']))
	{
		$res_tmp = '';
	}
	else
	{
        $res_tmp = str_replace(array_merge(array('{NEXT_HREF}'), $dop_tags), array_merge(array($href_next), $dop_values), $ftf_fields_temps['f_next_link']);
	}

	$values[] = $res_tmp2; // PREV
	$values[] = $res_tmp;  // NEXT


    $result = sb_str_replace($tags, $values, $ftf_fullelement);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    if($linked < 1)
    {
		$GLOBALS['sbCache']->save('pl_faq', $result, $res[0][6]);
		@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
		fClouds_Init_Tags('pl_faq', array($res[0][6]));
    }
    else
    {
        if($linked > 0)
            unset($_GET['faq_id']);

    	 return $result;
    }
}

/**
 * Вывод формы добавления вопросов
 */
function fFaq_Elem_Form($el_id, $temp_id, $params, $tag_id)
{
	if (!isset($_POST['f_question']))
	{
		//	просто вывод формы, данные пока не пришли
		if	($GLOBALS['sbCache']->check('pl_faq', $tag_id, array($el_id, $temp_id, $params)))
			return;
    }

    $res = sql_param_query('SELECT sftf_lang, sftf_form, sftf_fields_temps, sftf_fields_temps, sftf_messages, sftf_categs_temps
                            FROM sb_faq_temps_form WHERE sftf_id=?d', $temp_id);
    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_faq', '');
        return;
    }

    list($sftf_lang, $sftf_form, $sftf_fields_temps, $sftf_fields_temps, $sftf_messages, $sftf_categs_temps) = $res[0];

    $params = unserialize(stripslashes($params));
    $sftf_fields_temps = unserialize($sftf_fields_temps);
    $sftf_categs_temps = unserialize($sftf_categs_temps);
    $sftf_messages = unserialize($sftf_messages);

    $result = '';
    if (!isset($_POST['f_question']) && trim($sftf_form) == '')
    {
        // вывод формы
        $GLOBALS['sbCache']->save('pl_faq', '');
        return;
    }

    $f_question = isset($_POST['f_question']) ? $_POST['f_question'] : '' ;
    $f_author = isset($_POST['f_author']) ? $_POST['f_author'] : '' ;
    $f_email = isset($_POST['f_email']) ? $_POST['f_email'] : '' ;
    $f_phone = isset($_POST['f_phone']) ? $_POST['f_phone'] : '' ;
    $f_tags = isset($_POST['f_tags']) ? $_POST['f_tags'] : '' ;
	$notify_email = isset($_POST['notify_email']) ? $_POST['notify_email'] : '' ;

	$f_categ = array();
	if (isset($_POST['f_categ']))
	{
		if (is_array($_POST['f_categ']))
    	{
    		$f_categ = $_POST['f_categ'];
    	}
    	else
    	{
        	$f_categ[] = intval($_POST['f_categ']);
    	}
	}
	elseif (isset($params['rubrikator_form']) && $params['rubrikator_form'] == 1)
    {
    	if (isset($_GET['faq_cid']))
    	{
        	$f_categ[] = intval($_GET['faq_cid']);
	    }
	    elseif (isset($_GET['faq_scid']))
	    {
			$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_faq"', $_GET['faq_scid']);
			if ($res)
	        {
				$f_categ[] = $res[0][0];
	        }
	        else
	        {
		        $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id=?d', $_GET['faq_scid']);
				if ($res)
		        {
					$f_categ[] = $res[0][0];
		        }
	        }
	    }
    }

	$tags = array();
	$values = array();

    $message_tags = array('{DATE}', '{QUESTION}', '{AUTHOR}', '{EMAIL}', '{PHONE}');
    $message_values = array(sb_parse_date(time(), $sftf_fields_temps['f_date_val'], SB_CMS_LANG), $f_question, $f_author, $f_email, $f_phone);

    $message = '';
    $fields_message = '';

	// проверка данных и сохранение
	$error = false;
	$users_error = false;

	$cat_ids = array();

	if(isset($_POST['f_question']))
	{
		if($f_question == '')
		{
			$error = true;

			$tags = array_merge($tags, array('{F_QUESTON_SELECT_START}', '{F_QUESTION_SELECT_END}'));
			$values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));
			$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
		}

		if(isset($sftf_fields_temps['author_need']) && $sftf_fields_temps['author_need'] == 1 && $f_author == '')
		{
			$error = true;

			$tags = array_merge($tags, array('{F_AUTHOR_SELECT_START}', '{F_AUTHOR_SELECT_END}'));
			$values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));

			$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
		}

	    if((isset($sftf_fields_temps['email_need']) && $sftf_fields_temps['email_need'] == 1 && $f_email == '') || ($f_email != '' && !preg_match('/^\w+[\.\w\-_]*@[a-z0-9]+([\.\w\-]*\w)*\.\w{2,6}$/is'.SB_PREG_MOD, $f_email)))
	    {
		    $error = true;

		    $tags = array_merge($tags, array('{F_EMAIL_SELECT_START}', '{F_EMAIL_SELECT_END}'));
		    $values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));

		    $fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
	    }

	    if(isset($sftf_fields_temps['phone_need']) && $sftf_fields_temps['phone_need'] == 1 && $f_phone == '')
	    {
            $error = true;

            $tags = array_merge($tags, array('{F_PHONE_SELECT_START}', '{F_PHONE_SELECT_END}'));
            $values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));

            $fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
        }

        if(isset($sftf_fields_temps['tags_need']) && $sftf_fields_temps['tags_need'] == 1 && $f_tags == '')
        {
            $error = true;

            $tags = array_merge($tags, array('{F_TAGS_SELECT_START}', '{F_TAGS_SELECT_END}'));
            $values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));

            $fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
        }

        if(isset($sftf_fields_temps['cat_list_need']) && $sftf_fields_temps['cat_list_need'] == 1)
        {
        	$found = false;
        	foreach ($f_categ as $val)
        	{
        		if ($val > 0)
        		{
        			$found = true;
        			break;
        		}
        	}

        	if (!$found)
        	{
	            $error = true;

	            $tags = array_merge($tags, array('{F_CATEGS_LIST_SELECT_START}', '{F_CATEGS_LIST_SELECT_END}'));
	            $values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));

	            $fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
        	}
        }

        // проверяем код каптчи
        if (sb_strpos($sftf_form, '{CAPTCHA}') !== false || sb_strpos($sftf_form, '{CAPTCHA_IMG}') !== false)
        {
		    if(!sbProgCheckTuring('f_captcha', 'captcha_code'))
		    {
		        $error = true;

		        $tags = array_merge($tags, array('{F_CAPTCHA_SELECT_START}', '{F_CAPTCHA_SELECT_END}'));
		        $values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));

	            $message .= isset($sftf_messages['err_captcha_code']) ? str_replace($message_tags, $message_values, $sftf_messages['err_captcha_code']) : '' ;
		    }
        }

        $cat_ids = array();
		if(count($f_categ) == 0)
        {
        	$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', explode('^', $params['ids']));
        }
        else
        {
        	$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', $f_categ);
        }

		if ($res)
        {
            foreach ($res as $val)
            {
                list($cat_id) = $val;
                $cat_ids[] = $cat_id;
            }
        }

        if (count($cat_ids) > 0)
        {
	        // проверяем права на добавление
	        $closed_cats = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
	        if($closed_cats)
	        {
	        	$close_ids = array();
	            foreach($closed_cats as $key => $value)
	            {
	                $close_ids[] = $value[0];
	            }
	            $cat_ids = sbAuth::checkRights($close_ids, $cat_ids, 'pl_faq_edit');

	            if(count($cat_ids) < 1)
	            {
	                $error = true;
	                $message .= isset($sftf_messages['not_have_rights_add']) ? str_replace($message_tags, $message_values, $sftf_messages['not_have_rights_add']) : '' ;
	            }
	        }
        }
        else
        {
			$cat_ids = null;
        }

        require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
        $layout = new sbLayout();
        $row = $layout->checkPluginInputFields('pl_faq', $users_error, $sftf_fields_temps, -1, '', '', false, $sftf_fields_temps['date_temps']);

        if ($users_error)
        {
            foreach ($row as $f_name => $f_array)
            {
                $f_error = $f_array['error'];
                $f_tag = $f_array['tag'];

                $tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
                $values = array_merge($values, array($sftf_fields_temps['select_start'], $sftf_fields_temps['select_end']));
                switch($f_error)
                {
					case 2:
						$message .= isset($sftf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $sftf_messages['err_save_file']) : '';
						break;

					case 3:
						$message .= isset($sftf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $sftf_messages['err_type_file']) : '';
						break;

					case 4:
						$message .= isset($sftf_messages['f_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sftf_messages['f_size_too_large']) : '';
						break;

					case 5:
						$message .= isset($sftf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sftf_messages['err_img_size']) : '';
						break;

					default:
						$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
						break;
				}
			}
		}
	}

	$error = $error || $users_error;

	if ($f_question != '' && !$error)
	{
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

        $row['f_url'] = sb_check_chpu('', '', strip_tags($f_question), 'sb_faq', 'f_url', 'f_id');
		$row['f_author'] = $f_author;
        $row['f_email'] = $f_email;
        $row['f_phone'] = $f_phone;
        $row['f_date'] = time();
        $row['f_question'] = $f_question;
        $row['f_answer'] = '';
        $row['f_sort'] = $f_sort;
        $row['f_notify'] = ($notify_email == 1) ? 1 : 0;
        $row['f_show'] = isset($params['premod_question']) ? 0 : 1 ;
        $row['f_user_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : null;

        $question = (sb_strlen($f_question) > 150) ? sb_substr($f_question, 0, 150).'...' : $f_question;
        if (!($f_id = sbProgAddElement('sb_faq', 'f_id', $row, $cat_ids)))
		{
            $error = true;
            $message .= isset($sftf_messages['err_add_question']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_question']) : '';

        	sb_add_system_message(sprintf(KERNEL_PROG_PL_FAQ_FORM_ERR_ADD_QUESTION, $question), SB_MSG_WARNING);
        }
        else
		{
            if($f_tags != '')
			{
                require_once(SB_CMS_PL_PATH.'/pl_clouds/pl_clouds.inc.php');
            	fClouds_Set_Field($f_id, 'pl_faq', $f_tags);
            }
        	sb_add_system_message(sprintf(KERNEL_PROG_PL_FAQ_FORM_ADD_QUESTION, $question), SB_MSG_INFORMATION);
		}

        if (!$error)
        {
            require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
            $mail = new sbMail();

            $type = sbPlugins::getSetting('sb_letters_type');

			if($f_email != '')
			{
				$email_subj = fFaq_Parse($sftf_messages['user_subj'], $sftf_fields_temps, $f_id, $sftf_lang, '', '_val', $sftf_categs_temps);

				//чистим код от инъекций
				$email_subj = sb_clean_string($email_subj);

				ob_start();
				eval(' ?>'.$email_subj.'<?php ');
				$email_subj = trim(ob_get_clean());

				$email_text = fFaq_Parse($sftf_messages['user_text'], $sftf_fields_temps, $f_id, $sftf_lang, '', '_val', $sftf_categs_temps);

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
					$mail->send(array($f_email));
                }
			}

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
                $email_subj = fFaq_Parse($sftf_messages['admin_subj'], $sftf_fields_temps, $f_id, $sftf_lang, '', '_val', $sftf_categs_temps);
                ob_start();
                eval(' ?>'.$email_subj.'<?php ');
                $email_subj = trim(ob_get_clean());

                $email_text = fFaq_Parse($sftf_messages['admin_text'], $sftf_fields_temps, $f_id, $sftf_lang, '', '_val', $sftf_categs_temps);
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
                header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'f_id='.$f_id));
            }
            else
            {
            	if (sbPlugins::getSetting('sb_static_urls') != 1)
            	{
            		$query_str = '';
            		foreach ($_GET as $key => $value)
            		{
            			if ($key == 'f_id')
            				continue;

            			if (is_array($value))
            			{
            				foreach ($value as $val)
            				{
            					$query_str .= urldecode($key).'[]='.urldecode($val).'&';
            				}
            			}
            			else
            			{
            				$query_str .= urldecode($key).'='.urldecode($value).'&';
            			}
            		}

            		$query_str = trim($query_str, '&');

            		header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].($query_str != '' ?  '?'.$query_str.'&f_id='.$f_id : '?f_id='.$f_id)));
            	}
            	else
            	{
                	header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ?  '?'.$_SERVER['QUERY_STRING'].'&f_id='.$f_id : '?f_id='.$f_id)));
            	}
            }
            exit (0);
		}
		else
		{
			$layout->deletePluginFieldsFiles();
		}
	}
	elseif(isset($_POST['f_question']) && !$error)
	{
		$layout->deletePluginFieldsFiles();
    }

    if(isset($_GET['f_id']))
    {
        $message .= fFaq_Parse($sftf_messages['success_add_question'], $sftf_fields_temps, $_GET['f_id'], $sftf_lang, '', '_val', $sftf_categs_temps);
    }

    $message .= $fields_message;
    $tags = array_merge($tags, array('{MESSAGES}',
                  '{ACTION}',
	              '{QUESTON}',
	              '{AUTHOR}',
	              '{EMAIL}',
	              '{PHONE}',
                  '{TAGS}',
	              '{CAPTCHA}',
	              '{CAPTCHA_IMG}',
                  '{CATEGS_LIST}',
                  '{NOTIFY_EMAIL}'));

    //  вывод полей формы input
    $values[] = $message;
    $values[] = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
    $values[] = (isset($sftf_fields_temps['question']) && $sftf_fields_temps['question'] != '') ? str_replace('{VALUE}', $f_question, $sftf_fields_temps['question']) : '';

	if(isset($_SESSION['sbAuth']))
	{
		$res = sql_param_query('SELECT su_login, su_name, su_email FROM sb_site_users WHERE su_id=?d', $_SESSION['sbAuth']->getUserId());
		if($res)
		{
			list($su_login, $su_name, $su_email) = $res[0];

			if(!isset($_POST['f_author']) && $su_name != '')
				$f_author = $su_name;
			elseif(!isset($_POST['f_author']) && $su_login != '')
				$f_author = $su_login;

			if(!isset($_POST['f_email']) && $su_email != '')
				$f_email = $su_email;
		}
	}

    $values[] = (isset($sftf_fields_temps['author']) && $sftf_fields_temps['author'] != '') ? str_replace('{VALUE}', $f_author, $sftf_fields_temps['author']) : '';
    $values[] = (isset($sftf_fields_temps['email']) && $sftf_fields_temps['email'] != '') ? str_replace('{VALUE}', $f_email, $sftf_fields_temps['email']) : '';
    $values[] = (isset($sftf_fields_temps['phone']) && $sftf_fields_temps['phone'] != '') ? str_replace('{VALUE}', $f_phone, $sftf_fields_temps['phone']) : '';
    $values[] = (isset($sftf_fields_temps['tags']) && $sftf_fields_temps['tags'] != '') ? str_replace('{VALUE}', $f_tags, $sftf_fields_temps['tags']) : '';

    // Вывод КАПЧИ
    if ((sb_strpos($sftf_form, '{CAPTCHA}') !== false || sb_strpos($sftf_form, '{CAPTCHA_IMG}') !== false) &&
    	isset($sftf_fields_temps['captcha']) && trim($sftf_fields_temps['captcha']) != '' &&
        isset($sftf_fields_temps['img_captcha']) && trim($sftf_fields_temps['img_captcha']) != '')
    {
        $turing = sbProgGetTuring();
        if ($turing)
        {
            $values[] = $sftf_fields_temps['captcha'];
            $values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $sftf_fields_temps['img_captcha']);
        }
        else
        {
            $values[] = $sftf_fields_temps['captcha'];
            $values[] = '';
        }
    }
    else
    {
        $values[] = '';
        $values[] = '';
    }

	if (sb_strpos($sftf_form, '{CATEGS_LIST}') !== false)
	{
		$cat_ids = explode('^', $params['ids']);
		$values[] = sbProgGetCategsList($cat_ids, 'pl_faq', $f_categ, $sftf_fields_temps['categs_list_options'], $sftf_fields_temps['categs_list'], 'pl_faq_edit');
	}
	else
	{
		$values[] = '';
	}

	if(isset($sftf_fields_temps['notify_email']) && $sftf_fields_temps['notify_email'] != '')
	{
		if(isset($_POST['f_question']) && $notify_email == '')
	    {
			$sftf_fields_temps['notify_email'] = str_replace('{VALUE}', '', $sftf_fields_temps['notify_email']);
		}
		elseif(isset($_POST['f_question']) && $notify_email == '1')
		{
			$sftf_fields_temps['notify_email'] = str_replace('{VALUE}', 'checked="checked"', $sftf_fields_temps['notify_email']);
		}
		elseif(!isset($_POST['f_question']))
	    {
			$sftf_fields_temps['notify_email'] = (isset($sftf_fields_temps['notify_email_need']) && $sftf_fields_temps['notify_email_need'] == 1) ? str_replace('{VALUE}', 'checked="checked"', $sftf_fields_temps['notify_email']) : $sftf_fields_temps['notify_email'];
	    }
		$values[] = $sftf_fields_temps['notify_email'];		//	NOTIFY_EMAIL
	}
	else
	{
		$values[] = '';
	}

    @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    sbLayout::parsePluginInputFields('pl_faq', $sftf_fields_temps,  $sftf_fields_temps['date_temps'], $tags, $values, -1, 'sb_faq', 'f_id');

    $result = str_replace($tags, $values, $sftf_form);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    //чистим код от инъекций
    $result = sb_clean_string($result);

    if (!isset($_POST['f_question']))
        $GLOBALS['sbCache']->save('pl_faq', $result);
    else
        eval(' ?>'.$result.'<?php ');
}

/**
 *
 * Вывод информации о вопросе
 *
 * @param string $temp Макет дизайна.
 * @param array $field_temps Макеты дизайна полей.
 * @param int $id Идентификатор вопроса.
 * @param string $lang Язык макета дизайна.
 * @param string $prefix Префикс имени поля в макете дизайна полей.
 * @param string $sufix Суффикс имени поля в макете дизайна полей.
 *
 * @return string Отпарсенный макет дизайна вывода информации о вопросе.
 */
function fFaq_Parse($temp, &$fields_temps, $id, $lang = 'ru', $prefix = '', $sufix = '', $categs_temps = array())
{
	if (trim($temp) == '')
        return '';

    // вытаскиваем пользовательские поля
    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_faq"');

    $users_fields = array();
    $users_fields_select_sql = '';

    $categs_fields = array();
    $categs_sql_fields = array();

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

    $res = sql_param_query('SELECT f.f_author, f.f_email, f.f_phone, f.f_date, f.f_question, f.f_url, c.cat_title, c.cat_id, c.cat_url, f.f_answer
                            '.$users_fields_select_sql.'
                             FROM sb_faq f, sb_categs c, sb_catlinks l
                             WHERE l.link_el_id = f.f_id AND l.link_cat_id = c.cat_id AND c.cat_ident="pl_faq" AND
                             l.link_src_cat_id=0 AND f_id=?d', $id);
    if (!$res)
    {
        return '';
    }

    list ($f_author, $f_email, $f_phone, $f_date, $f_question, $f_url, $cat_title, $cat_id, $cat_url, $f_answer) = $res[0];

    $values = array();
    $values[] = $id;
    $values[] = ($f_question != '' && isset($fields_temps['f_question_val']) && trim($fields_temps['f_question_val']) != '' ) ? str_replace('{VALUE}', $f_question, $fields_temps['f_question_val']) : '';
    $values[] = ($f_date != '' && $f_date != 0 && isset($fields_temps['f_date_val']) && trim($fields_temps['f_date_val']) != '' ) ? sb_parse_date($f_date, $fields_temps['f_date_val'], $lang) : '';
    $values[] = ($f_author != '' && isset($fields_temps['f_author_val']) && trim($fields_temps['f_author_val']) != '' ) ? str_replace('{VALUE}', $f_author, $fields_temps['f_author_val']) : '';
    $values[] = ($f_email != '' && isset($fields_temps['f_email_val']) && trim($fields_temps['f_email_val']) != '' ) ? str_replace('{VALUE}', $f_email, $fields_temps['f_email_val']) : '';
    $values[] = ($f_phone != '' && isset($fields_temps['f_phone_val']) && trim($fields_temps['f_phone_val']) != '' ) ? str_replace('{VALUE}', $f_phone, $fields_temps['f_phone_val']) : '';
    $values[] = ($f_url != '' && !is_null($f_url) ? $f_url : '' );
	$values[] = ($f_answer != '') ? $f_answer : '';
    $values[] = ($cat_title != '') ? $cat_title : '';
    $values[] = ($cat_id != '') ? $cat_id : '';
    $values[] = ($cat_url != '') ? $cat_url : '';

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

    if ($res_cat)
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
        	if (isset($categs[$value[0]]['fields'][$cat_field]))
            	$cat_values[] = $categs[$value[0]]['fields'][$cat_field];
			else
            	$cat_values[] = null;
		}
        $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $categs_temps, array(), array(), $lang, $prefix, '_cat'.$sufix);
        $users_values = array_merge($users_values, $cat_values);
    }

	$tags = array_merge(array('{ID}',
                              '{QUESTION}',
                              '{DATE}',
                              '{AUTHOR}',
                              '{EMAIL}',
                              '{PHONE}',
                              '{LINK}',
							  '{ANSWER}',
                              '{CAT_TITLE}',
                              '{CAT_ID}',
                              '{CAT_URL}'), $tags);

    if ($users_values)
        $values = array_merge($values, $users_values);

    return str_replace($tags, $values, $temp);
}

/**
 * Вывод разделов
 *
 */
function fFaq_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    $num_sub = 0;
    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_faq', 'pl_faq', 'faq', $num_sub);

}

/**
 * Вывод выбранного раздела
 *
 */
function fFaq_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_faq', 'pl_faq', 'faq');
}

/**
 * Вывод облака тегов
 *
 */
function fFaq_Elem_Cloud($el_id, $temp_id, $params, $tag_id)
{
    if ($GLOBALS['sbCache']->check('pl_faq', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));
    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['faq_scid']) || isset($_GET['faq_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
        if (isset($_GET['faq_cid']))
        {
            $cat_ids[] = intval($_GET['faq_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_faq"', $_GET['faq_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['faq_scid']);
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
							AND c.cat_ident="pl_faq"
							AND c2.cat_ident = "pl_faq"
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

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_faq_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_faq', '');
        return;
    }

    // вытаскиваем макет дизайна
    $res = sql_param_query('SELECT ct_pagelist_id, ct_perpage, ct_size_from, ct_size_to
                FROM sb_clouds_temps WHERE ct_id=?d', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CLOUDS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_faq', '');
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

        $where_sql .= ' AND f.f_date >= '.$last.' AND f.f_date <= '.$now;
    }
    elseif ($params['filter'] == 'next')
    {
        $next = intval($params['filter_next']);
        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

        $where_sql .= ' AND f.f_date >= '.$now.' AND f.f_date <= '.$next;
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
    $res_tags = $pager->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time))
                            FROM sb_clouds_tags ct, sb_faq f, sb_clouds_links cl, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=f.f_id
                            AND cl.cl_ident="pl_faq" AND cl.cl_el_id=f.f_id AND ct.ct_id=cl.cl_tag_id
                            '.$where_sql.'
				            AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
                            AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
                            AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
                            AND LENGTH(ct.ct_tag) >= ?d AND LENGTH(ct.ct_tag) <= ?d
                            GROUP BY cl.cl_tag_id '
                            .($sort_sql != '' ? 'ORDER BY '.$sort_sql : 'ORDER BY ct.ct_tag'),
                            $cat_ids, $ct_size_from, $ct_size_to);
    if (!$res_tags)
    {
        $GLOBALS['sbCache']->save('pl_faq', '');
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
    $result = fClouds_Show($res_tags, $temp_id, $pt_page_list, $tags_total, $params['page'], 'f_tag');

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save('pl_faq', $result);

}

/**
 * @param int $year Год, за который необходимо сделать выборку дней.
 * @param int $month Месяц, за который необходимо сделать выборку дней.
 * @param string $params Параметры компонента.
 * @param string $field Поле элемента с датой.
 * @param int $rubrikator Учитывать вывод разделов.
 * @param int $filter Учитывать фильтр.
 *
 * @return array Массив дней, за которые есть элементы.
 */
function fFaq_Get_Calendar($year, $month, $params, $rubrikator, $filter)
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

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['faq_scid']) || isset($_GET['faq_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
    	if (isset($_GET['faq_cid']))
        {
            $cat_ids[] = intval($_GET['faq_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_faq"', $_GET['faq_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['faq_scid']);
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
							AND c.cat_ident="pl_faq"
							AND c2.cat_ident = "pl_faq"
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

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_faq_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        return $result;
    }

    // вытаскиваем макет дизайна
    $res = sql_param_query('SELECT fdl_checked, fdl_categ_top, fdl_categ_bottom FROM sb_faq_temps_list WHERE fdl_id=?d', $params['temp_id']);
    if (!$res)
    {
        $fdl_checked = array();
		$fdl_categ_bottom = $fdl_categ_top = '';
    }
    else
    {
		list($fdl_checked, $fdl_categ_top, $fdl_categ_bottom) = $res[0];
    	if ($fdl_checked != '')
    	{
        	$fdl_checked = explode(' ', $fdl_checked);
    	}
    	else
    	{
			$fdl_checked = array();
		}
	}

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';

    foreach ($fdl_checked as $value)
    {
        $elems_fields_where_sql .= ' AND f.user_f_'.$value.'=1';
    }

	$now = time();
	if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND f.f_date >= '.$last.' AND f.f_date <= '.$now;
    }

    if($params['show_all'] == 0)
    {
        $elems_fields_where_sql .= ' AND f.f_answer != "" ';
    }

	$from_date = mktime(0, 0, 0, $month, 1, $year);
    $to_date = mktime(23, 59, 59, $month, sb_get_last_day($month, $year), $year);

    if ($from_date <= 0 || $to_date <= 0)
    {
    	return $result;
    }

    $elems_fields_where_sql .= ' AND f.'.$field.' >= "'.$from_date.'" AND f.'.$field.' <= "'.$to_date.'"';

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
    if ($fdl_categ_top != '' || $fdl_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }

	if ($categs_output)
	{
		$res = sql_param_query('SELECT f.'.$field.'
                            FROM sb_faq f, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=f.f_id
                            '.$elems_fields_where_sql.'
				            AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
                            AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
                            AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
                            GROUP BY c.cat_left'.($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', f.f_date DESC').
							($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
	}
	else
	{
		$res = sql_param_query('SELECT f.'.$field.'
							FROM sb_faq f, sb_catlinks l
							WHERE l.link_cat_id IN (?a) AND l.link_el_id=f.f_id
							'.$elems_fields_where_sql.'
				            AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
                            AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
                            AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
							GROUP BY f.f_id'.
							($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY f.f_date DESC').
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
function fFaq_Elem_Filter($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_faq', $tag_id, array($el_id, $temp_id, $params)))
		return;

	$res = sql_param_query('SELECT sftf_id, sftf_title, sftf_lang, sftf_form, sftf_fields_temps
							FROM sb_faq_temps_form WHERE sftf_id=?d', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
		$GLOBALS['sbCache']->save('pl_faq', '');
		return;
    }

	list($sftf_id, $sftf_title, $sftf_lang, $sftf_form, $sftf_fields_temps) = $res[0];

	$params = unserialize(stripslashes($params));
	$sftf_fields_temps = unserialize($sftf_fields_temps);

	$result = '';
	if (trim($sftf_form) == '')
	{
		$GLOBALS['sbCache']->save('pl_faq', '');
		return;
	}
	$tags = array('{ACTION}', '{TEMP_ID}', '{AUTHOR}', '{EMAIL}', '{PHONE}', '{QUESTION}', '{ANSWER}', '{ID}', '{ID_LO}', '{ID_HI}', '{DATE}',
					'{DATE_LO}', '{DATE_HI}', '{SORT_SELECT}');
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
	$values = array();
	$values[] = $action;
	$values[] = $sftf_id;
	$values[] = (isset($sftf_fields_temps['faq_author']) && $sftf_fields_temps['faq_author'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_author']) && $_REQUEST['f_f_author'] != '' ? $_REQUEST['f_f_author'] : ''), $sftf_fields_temps['faq_author']) : '';
	$values[] = (isset($sftf_fields_temps['faq_email']) && $sftf_fields_temps['faq_email'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_email']) && $_REQUEST['f_f_email'] != '' ? $_REQUEST['f_f_email'] : ''), $sftf_fields_temps['faq_email']) : '';
	$values[] = (isset($sftf_fields_temps['faq_phone']) && $sftf_fields_temps['faq_phone'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_phone']) && $_REQUEST['f_f_phone'] != '' ? $_REQUEST['f_f_phone'] : ''), $sftf_fields_temps['faq_phone']) : '';
	$values[] = (isset($sftf_fields_temps['faq_question']) && $sftf_fields_temps['faq_question'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_question']) && $_REQUEST['f_f_question'] != '' ? $_REQUEST['f_f_question'] : ''), $sftf_fields_temps['faq_question']) : '';
	$values[] = (isset($sftf_fields_temps['faq_answer']) && $sftf_fields_temps['faq_answer'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_answer']) && $_REQUEST['f_f_answer'] != '' ? $_REQUEST['f_f_answer'] : ''), $sftf_fields_temps['faq_answer']) : '';
	$values[] = (isset($sftf_fields_temps['faq_id']) && $sftf_fields_temps['faq_id'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['f_f_id']) && $_REQUEST['f_f_id'] != '' ? $_REQUEST['f_f_id'] : ''), $sftf_fields_temps['faq_id']) : '');
	$values[] = (isset($sftf_fields_temps['faq_id_lo']) && $sftf_fields_temps['faq_id_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['f_f_id_lo']) && $_REQUEST['f_f_id_lo'] != '' ? $_REQUEST['f_f_id_lo'] : ''), $sftf_fields_temps['faq_id_lo']) : '');
	$values[] = (isset($sftf_fields_temps['faq_id_hi']) && $sftf_fields_temps['faq_id_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['f_f_id_hi']) && $_REQUEST['f_f_id_hi'] != '' ? $_REQUEST['f_f_id_hi'] : ''), $sftf_fields_temps['faq_id_hi']) : '');
	$values[] = (isset($sftf_fields_temps['faq_date']) && $sftf_fields_temps['faq_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_date']) && $_REQUEST['f_f_date'] != '' ? $_REQUEST['f_f_date'] : ''), $sftf_fields_temps['faq_date']) : '';
	$values[] = (isset($sftf_fields_temps['faq_date_lo']) && $sftf_fields_temps['faq_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_date_lo']) && $_REQUEST['f_f_date_lo'] != '' ? $_REQUEST['f_f_date_lo'] : ''), $sftf_fields_temps['faq_date_lo']) : '';
	$values[] = (isset($sftf_fields_temps['faq_date_hi']) && $sftf_fields_temps['faq_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['f_f_date_hi']) && $_REQUEST['f_f_date_hi'] != '' ? $_REQUEST['f_f_date_hi'] : ''), $sftf_fields_temps['faq_date_hi']) : '';
	$values[] = sbLayout::replacePluginFieldsTagsFilterSelect('pl_faq', 's_f_f_', $sftf_fields_temps['faq_sort_select'], $sftf_form);


	sbLayout::parsePluginInputFields('pl_faq', $sftf_fields_temps, $sftf_fields_temps['date_temps'], $tags, $values, -1, 'sb_faq', 'f_id', array(), array(), false, 'f_f', '', true);

	$result = str_replace($tags, $values, $sftf_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	$GLOBALS['sbCache']->save('pl_faq', $result);
}

function fFaq_Get_Next_Prev($e_temp_id, $e_params, &$href_next, &$href_prev, $output_page, $current_faq_id)
{
	$user_link_id_sql = '';
	if($e_params != '')
		$e_params = unserialize($e_params);
	else
		$e_params = array();

//      выводить новости зарегистрированных пользователей
	$user_link_id_sql = '';
	if(isset($e_params['registred_users']) && $e_params['registred_users'] == 1)
    {
		if(isset($_SESSION['sbAuth']))
		{
			$user_link_id_sql = ' AND f.f_user_id = '.intval($_SESSION['sbAuth']->getUserId());
		}
		else
		{
			$href_prev = $href_next = '';
			return;
		}
	}
	else
	{
		if(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] == -1 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			$href_prev = $href_next = '';
			return;
		}
		elseif(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] > 0 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
		{
			$user_link_id_sql = ' AND f.f_user_id = '.intval($_REQUEST['faq_uid']);
		}
	}

	$cat_ids_tmp = array();
	if (isset($e_params['rubrikator']) && $e_params['rubrikator'] == 1 && (isset($_GET['faq_scid']) || isset($_GET['faq_cid'])))
	{
		//	используется связь с выводом разделов и выводить следует новости из соотв. раздела
		if (isset($_GET['faq_cid']))
        {
        	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['faq_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids_tmp[] = intval($_GET['faq_cid']);
		}
		else
		{
			$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['faq_scid'], 'pl_faq');
			if ($res)
			{
				$cat_ids_tmp[] = $res[0][0];
			}
			else
			{
				$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['faq_scid']);
				if ($res[0][0] > 0)
					$cat_ids_tmp[] = intval($_GET['faq_scid']);
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

	//	если следует выводить подразделы, то вытаскиваем их ID
	if (isset($e_params['subcategs']) && $e_params['subcategs'] == 1)
	{
		$res_tmp = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
						WHERE c2.cat_left <= c.cat_left
						AND c2.cat_right >= c.cat_right
						AND c.cat_ident="pl_faq"
						AND c2.cat_ident = "pl_faq"
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
			$href_next = $href_prev = '';
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

		$cat_ids_tmp = sbAuth::checkRights($closed_ids, $cat_ids_tmp, 'pl_faq_read');
	}

	if (count($cat_ids_tmp) == 0)
	{
		// указанные разделы были удалены
		$href_prev = $href_next = '';
		return;
	}

	// вытаскиваем макет дизайна
	$res_tmp = sql_param_query('SELECT fdl_checked, fdl_categ_top, fdl_element, fdl_categ_bottom
					FROM  sb_faq_temps_list WHERE fdl_id=?d', $e_temp_id);

	if (!$res_tmp)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
		$href_prev = $href_next = '';

		sb_404();
		return;
	}
	list ($fdl_checked, $fdl_top_cat, $fdl_element, $fdl_bottom_cat) = $res_tmp[0];

	$elems_fields_sort_sql = '';

	$votes_apply = $comments_sorting = false;
    if (isset($e_params['sort1']) && $e_params['sort1'] != '' && $e_params['sort1'] != 'RAND()')
    {
		if ($e_params['sort1'] == 'com_count' || $e_params['sort1'] == 'com_date')
		{
			$comments_sorting = true;
		}
		if ($e_params['sort1'] == 'f_rating' || $e_params['sort1'] == 'v.vr_num' || $e_params['sort1'] == 'v.vr_count')
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
		if ($e_params['sort2'] == 'f_rating' || $e_params['sort2'] == 'v.vr_num' || $e_params['sort2'] == 'v.vr_count')
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

		if ($e_params['sort3'] == 'f_rating' || $e_params['sort3'] == 'v.vr_num' || $e_params['sort3'] == 'v.vr_count')
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

	if ($fdl_checked != '')
	{
		$fdl_checked = explode(' ', $fdl_checked);
		foreach ($fdl_checked as $value_tmp)
		{
			$elems_fields_where_sql .= ' AND f.user_f_'.$value_tmp.'=1';
		}
	}

	$now = time();
	if ($e_params['filter'] == 'last')
	{
		$last = intval($e_params['filter_last']) - 1;
		$last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND f.f_date >= '.$last.' AND f.f_date <= '.$now;
	}
	elseif ($e_params['filter'] == 'next')
	{
		$next = intval($e_params['filter_next']);
		$next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

		$elems_fields_where_sql .= ' AND f.f_date >= '.$now.' AND f.f_date <= '.$next;
	}

	// используется ли группировка по разделам
	if ($fdl_top_cat != '' || $fdl_bottom_cat != '')
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
    	$group_str = ' GROUP BY f.f_id ';
	}

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL,';
    if($votes_apply ||
		sb_strpos($fdl_element, '{RATING}') !== false ||
		sb_strpos($fdl_element, '{VOTES_COUNT}') !== false ||
		sb_strpos($fdl_element, '{VOTES_SUM}') !== false ||
		sb_strpos($fdl_element, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=f.f_id AND v.vr_plugin="pl_faq" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS f_rating, ';
	}

	if($comments_sorting)
    {
		$com_sort_fields = ' COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = ' LEFT JOIN sb_comments com ON (com.c_el_id=f.f_id AND com.c_plugin="pl_faq" AND com.c_show=1)';
	}
	else
    {
		$com_sort_fields = ' NULL, NULL ';
		$com_sort_sql = '';
	}

    if($categs_tmp_output)
    {
    	$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', f.f_date');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' f.f_date');
	}

	$res_tmp = sql_query('SELECT l.link_cat_id, f.f_id, f.f_url,
				'.$votes_fields.
				$com_sort_fields.'
			FROM sb_faq f
				'.$votes_sql.
				$com_sort_sql.'
				, sb_catlinks l'.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? ', sb_categs c' : '').'
			WHERE '.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
					(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=f.f_id
				'.$elems_fields_where_sql.'
				AND f.f_show IN ('.sb_get_workflow_demo_statuses().')
				AND (f.f_pub_start IS NULL OR f.f_pub_start <= '.$now.')
				AND (f.f_pub_end IS NULL OR f.f_pub_end >= '.$now.')
				'.$user_link_id_sql.$group_str.'
				'.($categs_tmp_output ? 'ORDER BY c.cat_left '.$elems_fields_sort_sql : 'ORDER BY '.$elems_fields_sort_sql), $cat_ids_tmp);

	if (!$res_tmp)
	{
		$href_prev = $href_next = '';
		return;
	}

	list($more_page, $more_ext) = sbGetMorePage($output_page);

	if(isset($_GET['faq_id']))
	{
		$id = $_GET['faq_id'];
	}
	elseif(isset($_GET['faq_sid']))
	{
		$id = $_GET['faq_sid'];
	}

	$n_next = $n_prev = '';

	$count = count($res_tmp);
	for($i = 0; $i != $count; $i++)
	{
		if($res_tmp[$i][1] == $current_faq_id)
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

	if($n_prev == '')
	{
		$href_prev = '';
	}

	if($n_next == '')
	{
		$href_next = '';
	}

	//	Ссылка на предыдущий элемент
	if($n_prev !== '')
	{
		if ($more_page == '')
		{
			$href_prev = 'javascript: void(0);';
		}
		else
		{
			$href_prev = $more_page;
			if (sbPlugins::getSetting('sb_static_urls') == 1)
			{
				// ЧПУ
				$href_prev .= ($categs_tmp[$res_tmp[$n_prev][0]]['url'] != '' ? urlencode($categs_tmp[$res_tmp[$n_prev][0]]['url']).'/' : $res_tmp[$n_prev][0].'/').
									($res_tmp[$n_prev][2] != '' ? urlencode($res_tmp[$n_prev][2]) : $res_tmp[$n_prev][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] != '' ? '?faq_uid='.$_REQUEST['faq_uid'] : '');
			}
			else
			{
				$href_prev .= '?faq_cid='.$res_tmp[$n_prev][0].'&faq_id='.$res_tmp[$n_prev][1].(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] != '' ? '&faq_uid='.$_REQUEST['faq_uid'] : '');
			}
		}
	}

	// Ссылка на следующий элемент
	if($n_next !== '')
	{
		if ($more_page == '')
		{
			$href_next = 'javascript: void(0);';
		}
		else
		{
			$href_next = $more_page;

			if (sbPlugins::getSetting('sb_static_urls') == 1)
			{
				// ЧПУ
				$href_next .= ($categs_tmp[$res_tmp[$n_next][0]]['url'] != '' ? urlencode($categs_tmp[$res_tmp[$n_next][0]]['url']).'/' : $res_tmp[$n_next][0].'/').
									($res_tmp[$n_next][2] != '' ? urlencode($res_tmp[$n_next][2]) : $res_tmp[$n_next][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] != '' ? '?faq_uid='.$_REQUEST['faq_uid'] : '');
			}
			else
			{
				$href_next .= '?faq_cid='.$res_tmp[$n_next][0].'&faq_id='.$res_tmp[$n_next][1].(isset($_REQUEST['faq_uid']) && $_REQUEST['faq_uid'] != '' ? '&faq_uid='.$_REQUEST['faq_uid'] : '');
			}
		}
	}

}

?>