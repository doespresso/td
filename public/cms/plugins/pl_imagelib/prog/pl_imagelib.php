<?php

	function fImagelib_Elem_List($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
	{
		if ($GLOBALS['sbCache']->check('pl_imagelib', $tag_id, array($el_id, $temp_id, $params)))
			return;

		if (isset($_GET['imagelib_sid']))
		{
			$res = sql_query('SELECT COUNT(*) FROM sb_imagelib WHERE im_url=? OR im_id=?d', $_GET['imagelib_sid'], $_GET['imagelib_sid']);
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

	    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['imagelib_scid']) || isset($_GET['imagelib_cid'])))
	    {
	        // используется связь с выводом разделов и выводить следует изображение из соотв. раздела
	        if (isset($_GET['imagelib_cid']))
	        {
	        	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['imagelib_cid']);
        		if ($res[0][0] > 0)
	            	$cat_ids[] = intval($_GET['imagelib_cid']);
	        }
	        else
	        {
	            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_imagelib"', $_GET['imagelib_scid']);
	            if ($res)
	            {
	                $cat_ids[] = $res[0][0];
				}
				else
				{
					$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['imagelib_scid']);
        			if ($res[0][0] > 0)
						$cat_ids[] = intval($_GET['imagelib_scid']);
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
							AND c.cat_ident="pl_imagelib"
							AND c2.cat_ident = "pl_imagelib"
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
	            if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['imagelib_scid']) || isset($_GET['imagelib_cid'])))
	            {
	            	sb_404();
	            }

	            $GLOBALS['sbCache']->save('pl_imagelib', '');
	            return;
	        }
	    }

	    $comments_read_cat_ids = $cat_ids; // разделы, для которых есть права comments_read
	    $comments_edit_cat_ids = $cat_ids; // разделы, для которых есть права comments_edit
	    $vote_cat_ids = $cat_ids; 		   // разделы, для которых есть права vote

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

	        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_imagelib_read');
		    $comments_read_cat_ids = sbAuth::checkRights($closed_ids, $comments_read_cat_ids, 'pl_imagelib_comments_read');
        	$comments_edit_cat_ids = sbAuth::checkRights($closed_ids, $comments_edit_cat_ids, 'pl_imagelib_comments_edit');
        	$vote_cat_ids = sbAuth::checkRights($closed_ids, $vote_cat_ids, 'pl_imagelib_vote');
	    }

	    if (count($cat_ids) == 0)
	    {
	        // указанные разделы были удалены
	        $GLOBALS['sbCache']->save('pl_imagelib', '');
	        return;
	    }

	    //вытаскиваем макет дизайна
	    //$res = sql_param_query('SELECT itl_checked, itl_count_on_page, itl_title, itl_lang, itl_fields_temps, itl_categs_temps,
	   	//								itl_no_image_message, itl_count_on_line, itl_top, itl_top_cat, itl_image, itl_empty, itl_delim,
	   	//							    itl_bottom_cat, itl_bottom, itl_pagelist_id, itl_votes_id, itl_comments_id, itl_user_data_id, itl_tags_list_id
		//								FROM sb_imagelib_temps_list WHERE itl_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_imagelib_temps_list', $temp_id);

		if (!$res)
	    {
	        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_IMAGELIB_PLUGIN), SB_MSG_WARNING);
	        $GLOBALS['sbCache']->save('pl_imagelib', '');
	        return;
	    }

	    list ($itl_checked, $itl_count_on_page, $itl_title, $itl_lang, $itl_fields_temps, $itl_categs_temps,
	    	 $itl_no_image_message, $itl_count_on_line, $itl_top, $itl_top_cat, $itl_image, $itl_empty, $itl_delim, $itl_bottom_cat,
	    	 $itl_bottom, $itl_pagelist_id, $itl_votes_id, $itl_comments_id, $itl_user_data_id, $itl_tags_list_id) = $res[0];

	    $itl_fields_temps = unserialize($itl_fields_temps);
	    $itl_categs_temps = unserialize($itl_categs_temps);

		$res = false;
	    if(isset($itl_pagelist_id) && $itl_pagelist_id > 0)
	    {
		    // вытаскиваем макет дизайна постраничного вывода
		    $res = sbQueryCache::getTemplate('sb_pager_temps', $itl_pagelist_id);
	    }

		if($res)
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
				$user_link_id_sql = ' AND im.im_user_id = '.intval($_SESSION['sbAuth']->getUserId());
			}
			else
			{
				$GLOBALS['sbCache']->save('pl_imagelib', $itl_no_image_message);
				return;
			}
		}
		else
		{
			if(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] == -1 && isset($params['use_filter']) && $params['use_filter'] == 1)
			{
				$GLOBALS['sbCache']->save('pl_imagelib', $itl_no_image_message);
				return;
			}
			elseif(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] > 0 && isset($params['use_filter']) && $params['use_filter'] == 1)
			{
				$user_link_id_sql = ' AND im.im_user_id = '.intval($_REQUEST['imagelib_uid']);
			}
		}

		// вытаскиваем пользовательские поля библиотеки изображений и разделов
		$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_imagelib"');
        sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_imagelib');

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
		                $elems_fields_select_sql .= ', im.user_f_'.$value['id'];

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

	    if ($itl_checked != '')
	    {
	        $itl_checked = explode(' ', $itl_checked);
	        foreach ($itl_checked as $value)
	        {
				$elems_fields_where_sql .= ' AND im.user_f_'.$value.'=1';
	        }
	    }

		$now = time();
		if (isset($params['filter']) && $params['filter'] == 'last')
	    {
	        $last = intval($params['filter_last']) - 1;
	        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

	        $elems_fields_where_sql .= ' AND im.im_date >= '.$last.' AND im.im_date <= '.$now;
	    }
	    elseif (isset($params['filter']) && $params['filter'] == 'next')
	    {
	        $next = intval($params['filter_next']);
	        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

	        $elems_fields_where_sql .= ' AND im.im_date >= '.$now.' AND im.im_date <= '.$next;
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

	    	$elems_fields_where_sql .= ' AND im.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'" AND im.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'"';
	    }

		//	формируем SQL-запрос для сортировки
		$elems_fields_sort_sql = '';
		$votes_apply = $comments_sorting = false;

		if(isset($params['use_sort']) && $params['use_sort'] == '1' && isset($_REQUEST['s_f_im']) && trim($_REQUEST['s_f_im']) != '')
		{
			require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
			$elems_fields_sort_sql .= sbLayout::getPluginFieldsSortSql('im', 'im');
		}
		else
		{
			if (isset($params['sort1']) && $params['sort1'] != '')
			{
				if ($params['sort1'] == 'com_count' || $params['sort1'] == 'com_date')
				{
					$comments_sorting = true;
				}
				if ($params['sort1'] == 'im_rating' || $params['sort1'] == 'v.vr_num' || $params['sort1'] == 'v.vr_count')
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
				if ($params['sort2'] == 'im_rating' || $params['sort2'] == 'v.vr_num' || $params['sort2'] == 'v.vr_count')
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
				if ($params['sort3'] == 'im_rating' || $params['sort3'] == 'v.vr_num' || $params['sort3'] == 'v.vr_count')
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

		// Отключаем выводимое изображение, если выводится подробное фото
		if (isset($params['show_selected']) && $params['show_selected'] == 1 && (isset($_GET['imagelib_sid']) || isset($_GET['imagelib_id'])))
		{
			if (isset($_GET['imagelib_id']))
			{
				$elems_fields_where_sql .= ' AND im.im_id != "'.intval($_GET['imagelib_id']).'"';
			}
			else
			{
				$res = sql_param_query('SELECT im_id FROM sb_imagelib WHERE im_url=?', $_GET['imagelib_sid']);
				if ($res)
				{
					$elems_fields_where_sql .= ' AND im.im_id != "'.$res[0][0].'"';
				}
				else
				{
					$elems_fields_where_sql .= ' AND im.im_id != "'.intval($_GET['imagelib_sid']).'"';
				}
			}
		}

	    // связь с выводом облака тегов
	    $cloud_where_sql = '';
	    $im_tag = '';

	    if (isset($params['cloud']) && $params['cloud'] == 1 && isset($_REQUEST['im_tag']) && $_REQUEST['im_tag'] != '')
	    {
	    	$tag = trim(preg_replace('/[^0-9\,\s]+/', '', $_REQUEST['im_tag']));
	    	if ($tag != '')
	        	$im_tag .= $tag;
	    }

	    if (isset($params['cloud_comp']) && $params['cloud_comp'] == 1)
	    {
	    	if (isset($_REQUEST['im_tag_comp']) && $_REQUEST['im_tag_comp'] != '')
	    	{
	    		$tag = trim(preg_replace('/[^0-9\,\s\-]+/', '', $_REQUEST['im_tag_comp']));
	    		if ($tag != '')
					$im_tag .= ($im_tag != '' ? ',' : '').$tag;
	    	}
	    	else
	    	{
	    		$GLOBALS['sbCache']->save('pl_imagelib', $itl_no_image_message);
				return;
	    	}
	    }

		if ($im_tag != '')
		{
			$cloud_where_sql = ' AND cl.cl_ident="pl_imagelib" AND im.im_id=cl.cl_el_id AND cl.cl_tag_id IN ('.$im_tag.')';
	    }

		require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		// формируем SQL-запрос для фильтра
		$elems_fields_filter_sql = '';
	    if ($params['use_filter'] == 1)
	    {
	    	$date_temp = '';
			if(isset($_REQUEST['im_f_temp_id']))
	    	{
				$date = sql_param_query('SELECT itfrm_fields_temps FROM sb_imagelib_temps_form WHERE itfrm_id = ?d', $_REQUEST['im_f_temp_id']);
				if($date)
				{
					list($itfrm_fields_temps) = $date[0];
					$itfrm_fields_temps = unserialize($itfrm_fields_temps);
					$date_temp = $itfrm_fields_temps['date_temps'];
				}
			}

		    $morph_db = false;
		    if ($params['filter_morph'] == 1)
		    {
		    	require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
				$morph_db = new sbSearch();
		    }

	    	$elems_fields_filter_sql = '(';

			$elems_fields_filter_sql .= sbGetFilterNumberSql('im.im_id', 'im_f_id', $params['filter_logic']);
			$elems_fields_filter_sql .= sbGetFilterNumberSql('im.im_user_id', 'im_f_user_id', $params['filter_logic']);

	    	$elems_fields_filter_sql .= sbGetFilterTextSql('im.im_big', 'im_f_big', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    	$elems_fields_filter_sql .= sbGetFilterTextSql('im.im_middle', 'im_f_middle', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    	$elems_fields_filter_sql .= sbGetFilterTextSql('im.im_small', 'im_f_small', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    	$elems_fields_filter_sql .= sbGetFilterTextSql('im.im_title', 'im_f_title', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    	$elems_fields_filter_sql .= sbGetFilterTextSql('im.im_desc', 'im_f_desc', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
	    	$elems_fields_filter_sql .= sbGetFilterNumberSql('im.im_date', 'im_f_date', $params['filter_logic'], true, $date_temp);

	        $elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'im', 'im_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
	    }

	    if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
	    	$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
		else
			$elems_fields_filter_sql = '';

	    // используется ли группировка по разделам
	    if ($itl_top_cat != '' || $itl_bottom_cat != '')
	    {
	        $categs_output = true;
	    }
	    else
	    {
	        $categs_output = false;
	    }

		$num_cookie_name = 'pl_imagelib_'.$temp_id.'_'.$tag_id;
		require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
	    $pager = new sbDBPager($tag_id, $pt_perstage, $itl_count_on_page, '', $num_cookie_name);
	    if (isset($params['filter']) && $params['filter'] == 'from_to')
	    {
	        $pager->mFrom = intval($params['filter_from']);
	        $pager->mTo = intval($params['filter_to']);
	    }

    	// Если фото подгружаются как связанные, выводить не раздел, а список конкретных фото
    	$sql_linked = '';
    	if ($linked != 0)
    	{
    		$sql_linked = ' AND im.im_id IN ('.$linked.') ';
    	}

		$group_str = '';
	    $group_res = sql_param_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
	    if ($group_res && $group_res[0][0] > 0 || $comments_sorting || $cloud_where_sql != '')
	    {
	    	$group_str = ' GROUP BY im.im_id ';
	    }

		$votes_sql = '';
		$votes_fields = ' NULL, NULL, NULL,';
    	if($votes_apply ||
			sb_strpos($itl_image, '{RATING}') !== false ||
			sb_strpos($itl_image, '{VOTES_COUNT}') !== false ||
			sb_strpos($itl_image, '{VOTES_SUM}') !== false ||
			sb_strpos($itl_image, '{VOTES_FORM}') !== false)
		{
			$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=im.im_id AND v.vr_plugin="pl_imagelib" ';
			$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS im_rating, ';
		}

    	if($comments_sorting)
    	{
			$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
			$com_sort_sql = 'LEFT JOIN sb_comments com ON (com.c_el_id=im.im_id AND com.c_plugin="pl_imagelib" AND com.c_show=1)';
		}
		else
    	{
			$com_sort_fields = 'NULL, NULL';
			$com_sort_sql = '';
		}

		if($categs_output)
    	{
    		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', im.im_date');
		}
		else
		{
			$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' im.im_date');
    	}

		//	выборка изображений, которые следует выводить
		$images_total = true;

		$res = $pager->init($images_total, 'SELECT l.link_cat_id, im.im_id, im.im_title, im.im_desc, im.im_big, im.im_middle, im.im_small, im.im_url, im.im_user_id, im.im_date,
					'.$votes_fields.'
					'.$com_sort_fields.'
					'.$elems_fields_select_sql.'
				FROM sb_imagelib im
					'.$votes_sql.
					$com_sort_sql.'
					, sb_catlinks l'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').
					($cloud_where_sql != '' ? ', sb_clouds_links cl' : '').'
				WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
					(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=im.im_id
					'.$elems_fields_where_sql.
					$elems_fields_filter_sql.
					$cloud_where_sql.'
					AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
					AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
					AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
					'.$sql_linked.'
					'.$user_link_id_sql.'
					'.$group_str.'
					'.($categs_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql), $cat_ids);

		if (!$res)
		{
			$GLOBALS['sbCache']->save('pl_imagelib', $itl_no_image_message);
			return;
		}

		$count_images = $pager->mFrom + 1;
	    $comments_count = array();
    	if(sb_strpos($itl_image, '{COUNT_COMMENTS}') !== false)
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
	        	$comments_count = fComments_Get_Count($ids_arr, 'pl_imagelib');
		    }
    	}

	    $categs = array();
	    if (sb_substr_count($itl_top_cat, '{CAT_NUM_IMAGES}') > 0 ||
	        sb_substr_count($itl_bottom_cat, '{CAT_NUM_IMAGES}') > 0 ||
	        sb_substr_count($itl_image, '{CAT_NUM_IMAGES}') > 0
	       )
	    {
	        $res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
	                (
	                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_imagelib im
	                WHERE c.cat_id = c1.cat_id
	                AND l.link_el_id=im.im_id
					AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
	                AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
	                AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
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

	    // верх вывода списка изображений
	    $flds_tags = array( '{SORT_ID_ASC}' ,'{SORT_ID_DESC}',
	    					'{SORT_TITLE_ASC}','{SORT_TITLE_DESC}',
	    					'{SORT_DESC_ASC}' ,'{SORT_DESC_DESC}',
	    					'{SORT_BIG_ASC}' ,'{SORT_BIG_DESC}',
	    					'{SORT_MIDDLE_ASC' ,'{SORT_MIDDLE_DESC}',
	    					'{SORT_SMALL_ASC}' ,'{SORT_SMALL_DESC}',
	    					'{SORT_GAL_ASC}' ,'{SORT_GAL_DESC}',
	    					'{SORT_USER_ID_ASC}' ,'{SORT_USER_ID_DESC}',
	    					'{SORT_ORDER_NUM_ASC}' ,'{SORT_ORDER_NUM_DESC}',
	    					'{SORT_DATE_ASC}' ,'{SORT_DATE_DESC}');

	    $query_str = $_SERVER['QUERY_STRING'];
	    if(isset($_GET['s_f_im']))
	    {
	    	$query_str = preg_replace('/[?&]?s_f_im['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
	    }

	    $flds_href = $GLOBALS['PHP_SELF'].(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_im=';

	    $flds_vals = array( $flds_href.urlencode('im_id=ASC'),
	    					$flds_href.urlencode('im_id=DESC'),
	    					$flds_href.urlencode('im_title=ASC'),
	    					$flds_href.urlencode('im_title=DESC'),
	    					$flds_href.urlencode('im_desc=ASC'),
	    					$flds_href.urlencode('im_desc=DESC'),
	    					$flds_href.urlencode('im_big=ASC'),
	    					$flds_href.urlencode('im_big=DESC'),
	    					$flds_href.urlencode('im_middle=ASC'),
	    					$flds_href.urlencode('im_middle=DESC'),
	    					$flds_href.urlencode('im_small=ASC'),
	    					$flds_href.urlencode('im_small=DESC'),
	    					$flds_href.urlencode('im_gal=ASC'),
	    					$flds_href.urlencode('im_gal=DESC'),
	    					$flds_href.urlencode('im_user_id=ASC'),
	    					$flds_href.urlencode('im_user_id=DESC'),
	    					$flds_href.urlencode('im_order_num=ASC'),
	    					$flds_href.urlencode('im_order_num=DESC'),
	    					$flds_href.urlencode('im_date=ASC'),
	    					$flds_href.urlencode('im_date=DESC'));

	    sbLayout::getPluginFieldsTagsSort('im', $flds_tags, $flds_vals, 'href_replace');
		// Заменяем значение селекта "Кол-во на странице" селектед
		if(isset($_REQUEST['num_'.$tag_id]))
	    {
	    	$itl_top = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $itl_top);
	    }
	    elseif(isset($_COOKIE[$num_cookie_name]))
	    {
	    	$itl_top = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $itl_top);
	    }
	    $result = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $pager->mNumElemsAll, 'num_'.$tag_id),$flds_vals), $itl_top);
	    $tags = array_merge($tags, array('{CAT_TITLE}',
	                                     '{CAT_LEVEL}',
	                                     '{CAT_ID}',
	                                     '{CAT_URL}',
										 '{CAT_NUM_IMAGES}',
	    								 '{ELEM_NUMBER}',
										 '{IMG_NAME}',
										 '{ELEM_DESC}',
	   									 '{ELEM_SMALL_IMAGE}',
	    								 '{ELEM_SMALL_IMAGE_WIDTH}',
	    								 '{ELEM_SMALL_IMAGE_HEIGHT}',
	    								 '{ELEM_MIDDLE_IMAGE}',
	    								 '{ELEM_MIDDLE_IMAGE_WIDTH}',
	     								 '{ELEM_MIDDLE_IMAGE_HEIGHT}',
	    								 '{ELEM_BIG_IMAGE}',
	    								 '{ELEM_BIG_IMAGE_WIDTH}',
	    								 '{ELEM_BIG_IMAGE_HEIGHT}',
										 '{TAGS}',
	    								 '{ELEM_LINK}',
	    								 '{ELEM_ID}',
	                                     '{ELEM_URL}',
	    								 '{ELEM_USER_ID}',
	    								 '{ELEM_USER_LOGIN}',
	    								 '{ELEM_USER_LINK}',
	    								 '{ELEM_ADD_DATE}',
	    								 '{CHANGE_DATE}',
	    								 '{EDIT_LINK}',
    									 '{VOTES_SUM}',
    									 '{VOTES_COUNT}',
                	                     '{RATING}',
    									 '{VOTES_FORM}',
	    								 '{COUNT_COMMENTS}',
	    								 '{FORM_COMMENTS}',
	    								 '{LIST_COMMENTS}',
	                                     '{USER_DATA}'));
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

	    $view_rating_form = (sb_strpos($itl_image, '{VOTES_FORM}') !== false && $itl_votes_id > 0);
	    $view_comments_list = (sb_strpos($itl_image, '{LIST_COMMENTS}') !== false && $itl_comments_id > 0);
	    $view_comments_form = (sb_strpos($itl_image, '{FORM_COMMENTS}') !== false && $itl_comments_id > 0);

	    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
	    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');

	    $img_name = '';

		if(sb_strpos($itl_image, '{TAGS}') !== false)
        {
		    // Достаю макеты для вывода списка тегов элементов
			$tags_template_error = false;
	    	$res1 = sql_param_query('SELECT ct_pagelist_id, ct_perpage
	                FROM sb_clouds_temps WHERE ct_id=?d', $itl_tags_list_id);

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

	    foreach ($res as $value)
	    {
	    	$value[7] = urlencode($value[7]); // URL

	    	$img_name = $value[2];

	        $old_values = $values;
	        $values = array();
	        $more = '';

	        if ($value[0] != $cur_cat_id)
	        {
	        	$cat_values = array();
	        }

	        if ($value[4] == '' || $more_page == '')
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
	                         ($value[7] != '' ? $value[7] : $value[1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] != '' ? '?imagelib_uid='.$_REQUEST['imagelib_uid'] : '');
	            }
	            else
	            {
	                $href .= '?imagelib_cid='.$value[0].'&imagelib_id='.$value[1].(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] != '' ? '&imagelib_uid='.$_REQUEST['imagelib_uid'] : '');
	            }
			}
			$dop_values = array($value[1], strip_tags($value[7]), strip_tags($value[2]), $value[0], $categs[$value[0]]['url'], strip_tags($categs[$value[0]]['title']), $href);

			if ($num_fields > 15)
			{
	            for ($i = 15; $i < $num_fields; $i++)
	            {
					$values[] = $value[$i];
				}
				$allow_bb = 0;
				if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
					$allow_bb = 1;
				$values = sbLayout::parsePluginFields($elems_fields, $values, $itl_fields_temps, $dop_tags, $dop_values, $itl_lang, '', '', $allow_bb, $link_level, $itl_image);
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
	                $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $itl_categs_temps, $dop_tags, $dop_values, $itl_lang, '', '', $allow_bb, $link_level, $itl_top_cat.$itl_image.$itl_bottom_cat);
	            }
	            $values = array_merge($values, $cat_values);
	        }

	        $im_small_width = $im_small_height = $im_middle_width = $im_middle_height = $im_big_width = $im_big_height = '';

			$im_big_loc = isset($value[4]) ? $value[4] : '';
	    	$im_middle_loc = isset($value[5]) ? $value[5] : '';
	    	$im_small_loc = isset($value[6]) ? $value[6] : '';

		    $GLOBALS['sbVfs']->mLocal = false;
		    if($GLOBALS['sbVfs']->exists($im_big_loc))
		    {
				$tmp = $GLOBALS['sbVfs']->getimagesize($im_big_loc);

				$im_big_height = $tmp[1];
				$im_big_width = $tmp[0];
			}

			if($value[5] != '0')
			{
				if($GLOBALS['sbVfs']->exists($im_middle_loc))
				{
					$tmp = $GLOBALS['sbVfs']->getimagesize($im_middle_loc);

		    		$im_middle_height = $tmp[1];
		    		$im_middle_width = $tmp[0];
		    	}
		    }

		    if($value[6] != '0')
		    {
		    	if($GLOBALS['sbVfs']->exists($im_small_loc))
		    	{
		    		$tmp = $GLOBALS['sbVfs']->getimagesize($im_small_loc);

		    		$im_small_height = $tmp[1];
		    		$im_small_width = $tmp[0];
		    	}
		    }

			$values[] = $categs[$value[0]]['title'];  	// CAT_TITLE
	        $values[] = $categs[$value[0]]['level']; 	// CAT_LEVEL
	        $values[] = $value[0];                      // CAT_ID
            $values[] = $categs[$value[0]]['url'];      // CAT_URL
	        $values[] = $categs[$value[0]]['count'];	// CAT_NUM_IMAGES
			$values[] = $count_images++;				//	ELEM_NUMBER
	       	$values[] = $value[2];

			if(isset($itl_fields_temps['itl_description_image']) && $itl_fields_temps['itl_description_image'] != '')
			{
				if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1' && $value[3] != '')
				{
					//Если разрешен bb код
			    	$value[3] = sbProgParseBBCodes($value[3]);
				}
				$values[] = (($value[3] != '') ? str_replace(array_merge(array('{IMG_DESC}'), $dop_tags),
						array_merge(array($value[3]), $dop_values), $itl_fields_temps['itl_description_image']) : '');  // ELEM_DESC
			}
			else
			{
				$values[] = '';
			}
	        if($value[6] != '0')
	        {
				$values[] = (($value[6] != '') ? str_replace(array_merge(array('{IMG_HREF}', '{ELEM_SMALL_WIDTH}', '{ELEM_SMALL_HEIGHT}'), $dop_tags),
						array_merge(array($value[6], $im_small_width, $im_small_height), $dop_values), $itl_fields_temps['itl_small_image']) : '');  // ELEM_SMALL
	        }
			else
	        {
				$values[] = '';
	        }

			$values[] = $im_small_width;	//	ELEM_SMALL_WIDTH
			$values[] = $im_small_height;	//	ELEM_SMALL_HEIGHT

	        if($value[5] != '0')
	        {
                $values[] = (($value[5] != '') ? str_replace(array_merge(array('{IMG_HREF}', '{ELEM_MIDDLE_WIDTH}', '{ELEM_MIDDLE_HEIGHT}'), $dop_tags),
                        array_merge(array($value[5], $im_middle_width, $im_middle_height), $dop_values), $itl_fields_temps['itl_middle_image']) : '');  // ELEM_MIDDLE
	        }
            else
	        {
	        	$values[] = '';
	        }

	        $values[] = $im_middle_width;           	// ELEM_MIDDLE_WIDTH
	        $values[] = $im_middle_height;          	// ELEM_MIDDLE_HEIGHT
            $values[] = str_replace(array_merge(array('{IMG_HREF}', '{ELEM_BIG_WIDTH}', '{ELEM_BIG_HEIGHT}'), $dop_tags),
                        array_merge(array($value[4], $im_big_width, $im_big_height), $dop_values), $itl_fields_temps['itl_big_image']);  // ELEM_BIG

	        $values[] = $im_big_width;                		// ELEM_BIG_WIDTH
	        $values[] = $im_big_height;               		// ELEM_BIG_HEIGHT
	        if(sb_strpos($itl_image, '{TAGS}') !== false)
	        {
	        	$tags_error = false;
			    // Вывод тематических тегов
				$pager_tags = new sbDBPager('t_'.$value[1], $pt_perstage, $ct_perpage);

			    // Вытаскиваю теги
				$tags_total = true;
				$res_tags = $pager_tags->init($tags_total, 'SELECT ct.ct_id, ct.ct_tag, COUNT( cl.cl_el_id ) AS ct_rating, MAX( UNIX_TIMESTAMP(cl.cl_time) )
	                            FROM sb_clouds_tags ct, sb_clouds_links cl
	                            WHERE cl.cl_tag_id IN
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_imagelib" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_imagelib"
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
					$values[] = fClouds_Show($res_tags, $itl_tags_list_id,  $pt_page_list_tags_1, $tags_total, '', 'im_tag'); //     TAGS
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

	        $values[] = $href; 								// ELEM_LINK
	        $values[] = $value[1]; 							// ELEM_ID
            $values[] = $value[7];                          // ELEM_URL
	        $values[] = $value[8];							// ELEM_USER_ID

			if(isset($value[8]) && $value[8] > 0)
			{
				$su_res = sql_param_query('SELECT su_login FROM sb_site_users WHERE su_id = ?d', intval($value[8]));
			}

	        if(!isset($su_res) || $su_res == false)
	        {
	        	 $values[] = ''; // ELEM_USER_LOGIN
	        }
	        elseif(isset($su_res) && $su_res)
	        {
	        	$values[] = $su_res[0][0]; // ELEM_USER_LOGIN
	        }

	        if(isset($value[8]) && $value[8] > 0 && isset($itl_fields_temps['itl_registred_users']) && $itl_fields_temps['itl_registred_users'] != '')
	        {
				$action = $auth_page.'?imagelib_uid='.$value[8].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');   //   ELEM_USER_LINK
				$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $itl_fields_temps['itl_registred_users']);
	        }
			else
	        {
				$values[] = '';
	        }

			$values[] = sb_parse_date($value[9], $itl_fields_temps['itl_date'], $itl_lang);	//	ELEM_ADD_DATE
		    // Дата последнего изменения
	        if(sb_strpos($itl_image, '{CHANGE_DATE}') !== false)
	        {
	        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_imagelib');
	        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $itl_fields_temps['itl_change_date'], $itl_lang) : ''; //   CHANGE_DATE
	        }
	        else
	       	{
	        	$values[] = '';
	       	}

	        // Ссылка "Редактировать"
		    require_once(SB_CMS_LIB_PATH.'/prog/sbFunctions.inc.php');
			list($edit_page, $edit_ext) = sbGetMorePage($params['edit_page']);

		    if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($value[8] != $_SESSION['sbAuth']->getUserId())))
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
		                         ($value[7] != '' ? $value[7] : $value[1]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
		            }
		            else
		            {
		                $edit_href .= '?imagelib_cid='.$value[0].'&imagelib_id='.$value[1];
		            }
		        }

		    	if ($edit_page != '' && isset($itl_fields_temps['itl_edit_link']))
		        {
		        	$edit = str_replace(array_merge(array('{EDIT_LINK}'), $dop_tags), array_merge(array($edit_href), $dop_values), $itl_fields_temps['itl_edit_link']);
		        }
	    	}
	    	if(isset($edit))
	    		$values[] = $edit; //EDIT_LINK
	    	else
	    		$values[] = '';

			$votes_sum = ($value[10] != '' && !is_null($value[10]) ? $value[10] : 0);
	        $votes_count = ($value[11] != '' && !is_null($value[11]) ? $value[11] : 0);
			$votes_rating = ($value[12] != '' && !is_null($value[12]) ? sprintf('%.2f', $value[12]) : 0);

	         // VOTES_FORM
	        if ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1)
	        {
		        $res_vote = fVoting_Form_Submit($itl_votes_id, 'pl_imagelib', $value[1], $votes_sum, $votes_count, $votes_rating);
	        }

			$values[] = $votes_sum; // VOTES_SUM
	        $values[] = $votes_count; // VOTES_COUNT
	        $values[] = $votes_rating; // RATING

	       	if($view_rating_form && ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $vote_cat_ids) || $categs[$value[0]]['closed'] != 1))
	    	{
				$values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($itl_votes_id, 'pl_imagelib', $value[1]));
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

		    	if (fComments_Add_Comment($itl_comments_id, 'pl_imagelib', $value[1], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
		    		$c_count++;
		    }

		    $values[] = $c_count; // COUNT_COMMENTS

		    if ($view_comments_form)
		    {
		    	$values[] = fComments_Get_Form($itl_comments_id, 'pl_imagelib', $value[1], $add_comments); // FORM_COMMENTS
		    }
		    else
		    {
		     	$values[] = ''; // FORM_COMMENTS
		    }

		    if ($view_comments_list)
		    {
		    	$exists_rights = ($categs[$value[0]]['closed'] == 1 && in_array($value[0], $comments_read_cat_ids) || $categs[$value[0]]['closed'] != 1);
	    		$values[] = fComments_Get_List($itl_comments_id, 'pl_imagelib', $value[1], $add_comments, '', 0, $exists_rights);	//	LIST_COMMENTS
			}
		    else
		    {
		        $values[] = ''; // LIST_COMMENTS
		    }

	        if($itl_user_data_id > 0 && isset($value[8]) && $value[8] > 0 && sb_strpos($itl_image, '{USER_DATA}') !== false)
	        {
                require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
                $values[] = fSite_Users_Get_Data($itl_user_data_id, $value[8]); //     USER_DATA
	        }
	        else
	        {
	            $values[] = '';   //   USER_DATA
	        }

	        if ($categs_output && $value[0] != $cur_cat_id)
	        {
	            if ($cur_cat_id != 0)
	            {
	                // низ вывода раздела
	                while ($col < $itl_count_on_line)
	                {
	                    $result .= $itl_empty;
	                    $col++;
	                }

	                $result .= str_replace($tags, $old_values, $itl_bottom_cat);
	            }

	            // верх вывода раздела
	            $result .= str_replace($tags, $values, $itl_top_cat);
	            $col = 0;
	        }

	        if ($col >= $itl_count_on_line)
	        {
	            $result .= $itl_delim;
	            $col = 0;
	        }

	        $result .= str_replace($tags, $values, $itl_image);

	        $cur_cat_id = $value[0];
	        $col++;
	    }

	    while ($col < $itl_count_on_line)
	    {
	        $result .= $itl_empty;
	        $col++;
	    }

	    if ($categs_output)
	    {
	        // низ вывода раздела
	        $result .= str_replace($tags, $values, $itl_bottom_cat);
	    }

	    // низ вывода библиотеки изображений
			// Заменяем значение селекта "Кол-во на странице" селектед
		if(isset($_REQUEST['num_'.$tag_id]))
	    {
	    	$itl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $itl_bottom);
	    }
	    elseif(isset($_COOKIE[$num_cookie_name]))
	    {
	    	$itl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $itl_bottom);
	    }
	    $result .= str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $pager->mNumElemsAll, 'num_'.$tag_id),$flds_vals), $itl_bottom);

	    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	    if($linked == 0)
	    {
	    	$GLOBALS['sbCache']->save('pl_imagelib', $result);
	    }
	 	else
    	{
    		return $result;
    	}
	}

	function fImagelib_Get_Next_Prev($e_temp_id, $e_params, &$result, $output_page, $current_image_id)
	{
		$result['href_prev'] = $result['href_next'] = $result['title_prev'] = $result['title_next'] = '';

		if($e_params != '')
			$e_params = unserialize($e_params);
		else
			$e_params = array();

//      выводить изображения зарегистрированных пользователей
		$user_link_id_sql = '';
		if(isset($e_params['registred_users']) && $e_params['registred_users'] == 1)
	    {
			if(isset($_SESSION['sbAuth']))
			{
				$user_link_id_sql = ' AND im.im_user_id = '.intval($_SESSION['sbAuth']->getUserId());
			}
			else
			{
				return;
			}
		}
		else
		{
			if(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] == -1 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
			{
				return;
			}
			elseif(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] > 0 && isset($e_params['use_filter']) && $e_params['use_filter'] == 1)
			{
				$user_link_id_sql = ' AND im.im_user_id = '.intval($_REQUEST['imagelib_uid']);
			}
		}

		$cat_ids_tmp = array();
		if (isset($e_params['rubrikator']) && $e_params['rubrikator'] == 1 && (isset($_GET['imagelib_scid']) || isset($_GET['imagelib_cid'])))
		{
	        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
	    	if (isset($_GET['imagelib_cid']))
	        {
	        	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['imagelib_cid']);
	        	if ($res[0][0] > 0)
	            	$cat_ids_tmp[] = intval($_GET['imagelib_cid']);
			}
			else
			{
				$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['imagelib_scid'], 'pl_imagelib');
				if ($res)
				{
					$cat_ids_tmp[] = $res[0][0];
				}
				else
				{
					$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['imagelib_scid']);
					if ($res[0][0] > 0)
						$cat_ids_tmp[] = intval($_GET['imagelib_scid']);
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
							AND c.cat_ident="pl_imagelib"
							AND c2.cat_ident = "pl_imagelib"
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

			$cat_ids_tmp = sbAuth::checkRights($closed_ids, $cat_ids_tmp, 'pl_imagelib_read');
		}

		if (count($cat_ids_tmp) == 0)
		{
			// указанные разделы были удалены
			return;
		}

		//	вытаскиваем макет дизайна
		$res_tmp = sql_query('SELECT itl_checked, itl_top_cat, itl_image, itl_bottom_cat  FROM sb_imagelib_temps_list WHERE itl_id=?d', $e_temp_id);
		if (!$res_tmp)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_IMAGELIB_PLUGIN), SB_MSG_WARNING);

			sb_404();
		}

		list ($itl_checked, $itl_top_cat, $itl_image, $itl_bottom_cat) = $res_tmp[0];

		$elems_fields_sort_sql = '';
		$votes_apply = $comments_sorting = false;

	    if (isset($e_params['sort1']) && $e_params['sort1'] != '' && $e_params['sort1'] != 'RAND()')
	    {
			if ($e_params['sort1'] == 'com_count' || $e_params['sort1'] == 'com_date')
			{
				$comments_sorting = true;
			}

			if ($e_params['sort1'] == 'im_rating' || $e_params['sort1'] == 'v.vr_num' || $e_params['sort1'] == 'v.vr_count')
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
			if ($e_params['sort2'] == 'im_rating' || $e_params['sort2'] == 'v.vr_num' || $e_params['sort2'] == 'v.vr_count')
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
			if ($e_params['sort3'] == 'im_rating' || $e_params['sort3'] == 'v.vr_num' || $e_params['sort3'] == 'v.vr_count')
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

		if ($itl_checked != '')
		{
			$itl_checked = explode(' ', $itl_checked);
			foreach ($itl_checked as $value_tmp)
			{
				$elems_fields_where_sql .= ' AND im.user_f_'.$value_tmp.'=1';
			}
		}

		$now = time();
		if ($e_params['filter'] == 'last')
		{
			$last = intval($e_params['filter_last']) - 1;
			$last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

			$elems_fields_where_sql .= ' AND im.im_date >= '.$last.' AND im.im_date <= '.$now;
		}
		elseif ($e_params['filter'] == 'next')
		{
			$next = intval($e_params['filter_next']);
			$next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

			$elems_fields_where_sql .= ' AND im.im_date >= '.$now.' AND im.im_date <= '.$next;
		}

		// используется ли группировка по разделам
		if ($itl_top_cat != '' || $itl_bottom_cat != '')
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
	    	$group_str = ' GROUP BY im.im_id ';
	    }

		$votes_sql = '';
		$votes_fields = ' NULL, NULL, NULL,';
    	if($votes_apply ||
			sb_strpos($itl_image, '{RATING}') !== false ||
			sb_strpos($itl_image, '{VOTES_COUNT}') !== false ||
			sb_strpos($itl_image, '{VOTES_SUM}') !== false ||
			sb_strpos($itl_image, '{VOTES_FORM}') !== false)
		{
			$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=im.im_id AND v.vr_plugin="pl_imagelib" ';
			$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS im_rating, ';
		}

    	if($comments_sorting)
    	{
			$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
			$com_sort_sql = 'LEFT JOIN sb_comments com ON (com.c_el_id=im.im_id AND com.c_plugin="pl_imagelib" AND com.c_show=1)';
		}
		else
    	{
			$com_sort_fields = 'NULL, NULL';
			$com_sort_sql = '';
		}

		if($categs_tmp_output)
		{
			$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', im.im_date');
		}
		else
		{
			$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' im.im_date');
    	}

		//	выборка изображений, которые следует выводить
		$images_total = true;

		$res_tmp = sql_query('SELECT l.link_cat_id, im.im_id, im.im_url, im.im_title,
					'.$votes_fields.'
					'.$com_sort_fields.'
				FROM sb_imagelib im
					'.$votes_sql.
					$com_sort_sql.', sb_catlinks l'.
					(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? ', sb_categs c' : '').'
				WHERE '.(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 || $categs_tmp_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
					(isset($e_params['show_hidden']) && $e_params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=im.im_id
					'.$elems_fields_where_sql.'
					AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
					AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
					AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
					'.$user_link_id_sql.'
					'.$group_str.'
					'.($categs_tmp_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql), $cat_ids_tmp);



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
					($res_tmp[$n_prev][2] != '' ? urlencode($res_tmp[$n_prev][2]) : $res_tmp[$n_prev][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] != '' ? '?imagelib_uid='.$_REQUEST['imagelib_uid'] : '');
				}
				else
				{
					$result['href_prev'] .= '?imagelib_cid='.$res_tmp[$n_prev][0].'&imagelib_id='.$res_tmp[$n_prev][1].(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] != '' ? '&imagelib_uid='.$_REQUEST['imagelib_uid'] : '');
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
					($res_tmp[$n_next][2] != '' ? urlencode($res_tmp[$n_next][2]) : $res_tmp[$n_next][1]).($more_ext != 'php' ? '.'.$more_ext : '/').(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] != '' ? '?imagelib_uid='.$_REQUEST['imagelib_uid'] : '');
				}
				else
				{
					$result['href_next'] .= '?imagelib_cid='.$res_tmp[$n_next][0].'&imagelib_id='.$res_tmp[$n_next][1].(isset($_REQUEST['imagelib_uid']) && $_REQUEST['imagelib_uid'] != '' ? '&imagelib_uid='.$_REQUEST['imagelib_uid'] : '');
				}
			}
		}
	}

	function fImagelib_Elem_Full($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
	{
		$im_id = $GLOBALS['sbCache']->check('pl_imagelib', $tag_id, array($el_id, $temp_id, $params), true);

		if ($im_id)
		{
			@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
    		fClouds_Init_Tags('pl_imagelib', array($im_id));
			return;
		}
		$params = unserialize(stripslashes($params));

		if($linked > 0)
			$_GET['imagelib_id'] = $linked;

		if (!isset($_GET['imagelib_sid']) && !isset($_GET['imagelib_id']))
		{
		    if($linked > 0)
		        unset($_GET['imagelib_id']);

			return;
		}

		$cat_id = -1;
		if (isset($_GET['imagelib_scid']) || isset($_GET['imagelib_cid']))
		{
			// используется связь с выводом разделов и выводить следует изображения из соотв. раздела
			if (isset($_GET['imagelib_cid']))
			{
				$cat_id = intval($_GET['imagelib_cid']);
			}
			else
			{
				$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_imagelib"', $_GET['imagelib_scid']);
				if ($res)
				{
					$cat_id = $res[0][0];
				}
				else
				{
					$cat_id = intval($_GET['imagelib_scid']);
				}
			}
		}

		// вытаскиваем макет дизайна
		//$res = sql_param_query('SELECT itf_lang, itf_element, itf_fields_temps, itf_categs_temps, itf_checked, itf_comments_id, itf_voting_id, itf_user_data_id, itf_tags_list_id
		//									FROM sb_imagelib_temps_full WHERE itf_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_imagelib_temps_full', $temp_id);

		if (!$res)
		{
		    if($linked > 0)
		        unset($_GET['imagelib_id']);

			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_IMAGELIB_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_imagelib', '');
			return;
		}

        list($itf_lang, $itf_element, $itf_fields_temps, $itf_categs_temps, $itf_checked, $itf_comments_id, $itf_voting_id, $itf_user_data_id, $itf_tags_list_id) = $res[0];

		$itf_element = unserialize($itf_element);
		$itf_fields_temps = unserialize($itf_fields_temps);
		$itf_categs_temps = unserialize($itf_categs_temps);

		// 	вытаскиваем пользовательские поля изображения и раздела
		//$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_imagelib" ');
        $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_imagelib');

		$elems_fields = array();
		$categs_fields = array();

		$categs_sql_fields = array();
		$elems_fields_select_sql = '';

		$tags = array();
		// 	формируем SQL-запрос для пользовательских полей
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
						$elems_fields_select_sql .= ', im.user_f_'.$value['id'];

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
		if (isset($params['registred_users']) && $params['registred_users'] == 1)
	    {
			if(isset($_SESSION['sbAuth']))
			{
				$user_link_id_sql = ' AND im.im_user_id = '.intval($_SESSION['sbAuth']->getUserId());
			}
			else
			{
				if($linked > 0)
	    		{
	    		    unset($_GET['imagelib_id']);
	    			sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_IMAGELIB_PLUGIN), SB_MSG_WARNING);
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
		if ($itf_checked != '')
		{
			$itf_checked = explode(' ', $itf_checked);
			foreach ($itf_checked as $value)
			{
				$elems_fields_where_sql .= ' AND im.user_f_'.$value.'=1';
			}
		}

		$now = time();
		if ($cat_id != -1 && $linked < 1)
		{
			$cat_dop_sql = 'AND c.cat_id="'.$cat_id.'"';
		}
		else
		{
			$cat_dop_sql = 'AND c.cat_ident="pl_imagelib"';
		}

		if (isset($_GET['imagelib_id']))
		{
			$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
						im.im_id, im.im_title, im.im_date, im.im_desc, im.im_small, im.im_middle, im.im_big, im.im_user_id, im.im_url,
						v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS im_rating
						'.$elems_fields_select_sql.'
						FROM sb_imagelib im LEFT JOIN sb_vote_results v ON v.vr_el_id=?d AND v.vr_plugin="pl_imagelib", sb_categs c, sb_catlinks l
						WHERE im.im_id=?d AND l.link_el_id=im.im_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
						AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
						AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
						AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
						'.$elems_fields_where_sql.$user_link_id_sql, $_GET['imagelib_id'], $_GET['imagelib_id']);
		}
		else
		{
			$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
						im.im_id, im.im_title, im.im_date, im.im_desc, im.im_small, im.im_middle, im.im_big, im.im_user_id, im.im_url,
						v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS im_rating
						'.$elems_fields_select_sql.'
       				    FROM sb_imagelib im LEFT JOIN sb_vote_results v ON v.vr_el_id=im.im_id AND v.vr_plugin="pl_imagelib", sb_categs c, sb_catlinks l
                        WHERE im.im_url=? AND l.link_el_id=im.im_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
						AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
						AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
						AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
						'.$elems_fields_where_sql.$user_link_id_sql, $_GET['imagelib_sid']);

			if (!$res)
			{
				$res = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_closed, c.cat_url,
                		im.im_id, im.im_title, im.im_date, im.im_desc, im.im_small, im.im_middle, im.im_big, im.im_user_id, im.im_url,
						v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS im_rating
                        '.$elems_fields_select_sql.'
						FROM sb_imagelib im LEFT JOIN sb_vote_results v ON v.vr_el_id=?d AND v.vr_plugin="pl_imagelib", sb_categs c, sb_catlinks l
                        WHERE im.im_id=?d AND l.link_el_id=im.im_id AND c.cat_id=l.link_cat_id '.$cat_dop_sql.'
						AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
						AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
						AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
						'.$elems_fields_where_sql.$user_link_id_sql, $_GET['imagelib_sid'], $_GET['imagelib_sid']);
            }
        }

		if (!$res)
		{
			if($linked > 0)
	    	{
	    	    unset($_GET['imagelib_id']);
	    		sb_add_system_message(sprintf(KERNEL_PROG_LINKS_NO_ELEMENT, $_SERVER['PHP_SELF'], KERNEL_PROG_IMAGELIB_PLUGIN), SB_MSG_WARNING);
	    		return;
	    	}
	    	else
	    	{
				sb_404();
				return;
	    	}
		}

	    $view_rating_form = (sb_strpos($itf_element['itf_image'], '{VOTES_FORM}') !== false && $itf_voting_id > 0);
	    $view_comments_list = (sb_strpos($itf_element['itf_image'], '{LIST_COMMENTS}') !== false && $itf_comments_id > 0);
	    $view_comments_form = (sb_strpos($itf_element['itf_image'], '{FORM_COMMENTS}') !== false && $itf_comments_id > 0);
		$add_rating = true;
    	$add_comments = true;

    	$res[0][5] = urlencode($res[0][5]); // CAT_URL
    	$res[0][14] = urlencode($res[0][14]); // ELEM_URL

		// Если каталог закрыт то проверяем права пользователя для доступа к этому разделу
		if ($res[0][4])
		{
			$cat_ids = sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_imagelib_read');
			if(count($cat_ids) == 0)
			{
			    unset($_GET['imagelib_id']);
				$GLOBALS['sbCache']->save('pl_imagelib', '');
				return;
			}

	        if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_imagelib_vote')) == 0)
	        {
	        	$view_rating_form = false;
	        	$add_rating = false;
	        }

	    	if ($view_comments_list && count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_imagelib_comments_read')) == 0)
	        {
	        	$view_comments_list = false;
	        }

	    	if (count(sbAuth::checkRights(array($res[0][0]), array($res[0][0]), 'pl_imagelib_comments_edit')) == 0)
	        {
	        	$add_comments = false;
	        }
		}

		$cat_count = '';
		if (sb_substr_count($itf_element['itf_image'], '{CAT_NUM_IMAGES}') > 0)
		{
			$res_cat = sql_param_query('SELECT COUNT(*) FROM sb_catlinks
													  WHERE link_cat_id=?d AND link_src_cat_id != ?d', $res[0][0], $res[0][0]);
			if ($res_cat)
			{
				$cat_count = $res_cat[0][0];
			}
		}

		$comments_count = array();
	    if(sb_strpos($itf_element['itf_image'], '{COUNT_COMMENTS}') !== false)
	    {
		    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
	        $comments_count = fComments_Get_Count(array($res[0][6]), 'pl_imagelib');
	    }

		// Наши теги
		$tags = array_merge($tags, array('{CAT_TITLE}',
										'{CAT_LEVEL}',
										'{CAT_NUM_IMAGES}',
										'{CAT_ID}',
                                        '{CAT_URL}',
										'{ELEM_DESC}',
										'{ELEM_SMALL_IMAGE}',
										'{ELEM_SMALL_IMAGE_WIDTH}',
										'{ELEM_SMALL_IMAGE_HEIGHT}',
										'{ELEM_MIDDLE_IMAGE}',
										'{ELEM_MIDDLE_IMAGE_WIDTH}',
										'{ELEM_MIDDLE_IMAGE_HEIGHT}',
										'{ELEM_BIG_IMAGE}',
										'{ELEM_BIG_IMAGE_WIDTH}',
										'{ELEM_BIG_IMAGE_HEIGHT}',
										'{TAGS}',
										'{ELEM_ID}',
                                        '{ELEM_URL}',
										'{ELEM_USER_ID}',
										'{ELEM_USER_LOGIN}',
										'{ELEM_USER_LINK}',
										'{IMAGE_PREV}',
										'{IMAGE_NEXT}',
										'{ELEM_ADD_DATE}',
										'{CHANGE_DATE}',
										'{EDIT_LINK}',
    								    '{COUNT_COMMENTS}',
                                        '{LIST_COMMENTS}',
                                        '{FORM_COMMENTS}',
                                        '{VOTES_SUM}',
                                        '{VOTES_COUNT}',
                                        '{RATING}',
                                        '{VOTES_FORM}',
                                        '{USER_DATA}'));

		$num_fields = count($res[0]);
		$num_cat_fields = count($categs_sql_fields);
    	$values = array();

		require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		$dop_tags = array('{ID}', '{ELEM_URL}', '{TITLE}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');
		$dop_values = array($res[0][6], $res[0][14], strip_tags($res[0][7]), $res[0][0], strip_tags($res[0][6]), strip_tags($res[0][1]));

		// Если полей вытаскивали больше чем 18 то, остальные значит пользовательские. Обрабатываем их.
		if ($num_fields > 18)
		{
			for ($i = 18; $i < $num_fields; $i++)
			{
				$values[] = $res[0][$i];
			}
			$allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;
			$values = sbLayout::parsePluginFields($elems_fields, $values, $itf_fields_temps, $dop_tags, $dop_values, $itf_lang, '', '', $allow_bb, $link_level, $itf_element['itf_image']);
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
			$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $itf_categs_temps, $dop_tags, $dop_values, $itf_lang, '', '', $allow_bb, $link_level, $itf_element['itf_image']);
			$values = array_merge($values, $cat_values);
		}

		$im_small_width = $im_big_width = $im_big_height = $im_small_width = $im_small_height = $im_middle_height = $im_middle_width = '';

		$im_big_loc = isset($res[0][12]) ? $res[0][12] : '';
		$im_middle_loc = isset($res[0][11]) ? $res[0][11] : '';
		$im_small_loc = isset($res[0][10]) ? $res[0][10] : '';

		$GLOBALS['sbVfs']->mLocal = false;
		if($GLOBALS['sbVfs']->exists($im_big_loc))
		{
			$tmp = $GLOBALS['sbVfs']->getimagesize($im_big_loc);

			$im_big_height = $tmp[1];
			$im_big_width = $tmp[0];
		}

		if($res[0][11] != '0')
		{
			if($GLOBALS['sbVfs']->exists($im_middle_loc))
			{
				$tmp = $GLOBALS['sbVfs']->getimagesize($im_middle_loc);

				$im_middle_height = $tmp[1];
				$im_middle_width = $tmp[0];
			}
		}

		if($res[0][10] != '0')
		{
			if($GLOBALS['sbVfs']->exists($im_small_loc))
			{
				$tmp = $GLOBALS['sbVfs']->getimagesize($im_small_loc);

				$im_small_height = $tmp[1];
				$im_small_width = $tmp[0];
			}
		}

     	$values[] = $res[0][1];      // CAT_TITLE
		$values[] = $res[0][2];      // CAT_LEVEL
		$values[] = $cat_count;      // CAT_COUNT
		$values[] = $res[0][0];      // CAT_ID
        $values[] = $res[0][5];      // CAT_URL

        if(isset($itf_fields_temps['itf_description_image']) && $itf_fields_temps['itf_description_image'] != '')
        {
		    if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
			{
				//Если разрешен bb код
		    	$res[0][9] = sbProgParseBBCodes($res[0][9]);
			}
        	$values[] = str_replace(array_merge(array('{IMG_DESC}'), $dop_tags),
						array_merge(array($res[0][9]), $dop_values), $itf_fields_temps['itf_description_image']);  // ELEM_DESC
        }
        else
        {
			$values[] = '';
        }

		$img_name = $res[0][7];
		if($res[0][10] != '0')
		{
            $values[] = str_replace(array_merge(array('{IMG_HREF}', '{ELEM_SMALL_WIDTH}', '{ELEM_SMALL_HEIGHT}'), $dop_tags),
									array_merge(array($res[0][10], $im_small_width, $im_small_height), $dop_values),
									$itf_fields_temps['itf_small_image']);  // ELEM_SMALL
		}
		else
		{
	       	$values[] = ''; // ELEM_SMALL
	    }

		$values[] = $im_small_width;    // ELEM_SMALL_WIDTH
		$values[] = $im_small_height;   // ELEM_SMALL_HEIGHT

		if($res[0][11] != '0')
		{
			$values[] = str_replace(array_merge(array('{IMG_HREF}', '{ELEM_MIDDLE_WIDTH}', '{ELEM_MIDDLE_HEIGHT}'), $dop_tags),
						array_merge(array($res[0][11], $im_middle_width, $im_middle_height), $dop_values), $itf_fields_temps['itf_middle_image']);  // ELEM_MIDDLE
		}
		else
		{
			$values[] = '';   // ELEM_MIDDLE
		}

		$values[] = $im_middle_width;    //  ELEM_MIDDLE_WIDTH
		$values[] = $im_middle_height;   //  ELEM_MIDDLE_HEIGHT
		$values[] = str_replace(array_merge(array('{IMG_HREF}', '{ELEM_BIG_WIDTH}', '{ELEM_BIG_HEIGHT}'), $dop_tags),
								array_merge(array($res[0][12], $im_big_width, $im_big_height), $dop_values), $itf_fields_temps['itf_big_image']);  // ELEM_BIG

		$values[] = $im_big_width;     //  ELEM_BIG_WIDTH
		$values[] = $im_big_height;    //  ELEM_BIG_HEIGHT

		if(sb_strpos($itf_element['itf_image'], '{TAGS}') !== false)
		{
			// Вывод тематических тегов
			$tags_error = false;
		    // вытаскиваем макет дизайна тэгов
		    $res_tags = sql_param_query('SELECT ct_pagelist_id, ct_perpage
		                FROM sb_clouds_temps WHERE ct_id=?d', $itf_tags_list_id);

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
	                                (SELECT cl_tag_id FROM sb_clouds_links cl2 WHERE cl2.cl_ident="pl_imagelib" AND cl2.cl_el_id=?d)
	                                AND cl.cl_ident="pl_imagelib"
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
				$values[] = fClouds_Show($res_tags, $itf_tags_list_id,  $pt_page_list_tags, $tags_total, $params['page'], 'im_tag'); //     TAGS
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

		$values[] = $res[0][6];        //  ELEM_ID
		$values[] = $res[0][14];       //  ELEM_URL
		$values[] = $res[0][13];       //  ELEM_USER_ID

		if(sb_strpos($itf_element['itf_image'], '{ELEM_USER_LOGIN}') !== false)
		{
			$su_res = sql_param_query('SELECT su_login FROM sb_site_users WHERE su_id = ?d', intval($res[0][13]));
			if($su_res)
			{
				$values[] = $su_res[0][0];   //   ELEM_USER_LOGIN
			}
			else
			{
				$values[] = '';   //   ELEM_USER_LOGIN
			}
		}
		else
		{
			$values[] = '';   //   ELEM_USER_LOGIN
		}

		// Ссылка на изображения пользователя
		if (isset($res[0][13]) && $res[0][13] > 0 && isset($itf_element['itf_registred_users']) && $itf_element['itf_registred_users'] != '')
		{
			$auth_page = (isset($params['auth_page']) && trim($params['auth_page']) != '' ? trim($params['auth_page']) : $_SERVER['PHP_SELF']);
			if (stripos($auth_page, 'http:') !== 0 && stripos($auth_page, 'https:') !== 0 && stripos($auth_page, '/') !== 0 && stripos($auth_page, '\\') !== 0)
			{
				$auth_page = '/'.$auth_page;
			}

			$action = $auth_page.'?imagelib_uid='.$res[0][13].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '');
			$values[] = str_replace(array_merge(array('{USER_LINK}'), $dop_tags), array_merge(array($action), $dop_values), $itf_element['itf_registred_users']);	//	ELEM_USER_LINK
		}
		else
		{
			$values[] = '';		//   ELEM_USER_LINK
		}

		/* Ссылки вперед и назад */
		if(sb_strpos($itf_element['itf_image'], '{IMAGE_PREV}') !== false || sb_strpos($itf_element['itf_image'], '{IMAGE_NEXT}') !== false)
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
						AND elem.e_ident = "pl_imagelib_list" AND elem.e_tag = ? LIMIT 1', $cats_ids,  $im_page, $im_file_path, $e_tag);
			}

			if($res_tmp != false)
			{
				list($e_temp_id, $e_params) = $res_tmp[0];

				$next_prev_result = array();

				fImagelib_Get_Next_Prev($e_temp_id, $e_params, $next_prev_result, $_SERVER['PHP_SELF'], $res[0][6]);

				$href_prev = $next_prev_result['href_prev'];
				$href_next = $next_prev_result['href_next'];
				$title_prev = $next_prev_result['title_prev'];
				$title_next = $next_prev_result['title_next'];
			}

			if($href_prev != '' && isset($itf_element['itf_image_prev']))
			{
				$href_prev = str_replace(array_merge(array('{IMAGE_PREV_HREF}', '{PREV_TITLE}'), $dop_tags), array_merge(array($href_prev, $title_prev), $dop_values), $itf_element['itf_image_prev']);
			}

			if($href_next != '' && isset($itf_element['itf_image_next']))
			{
				$href_next = str_replace(array_merge(array('{IMAGE_NEXT_HREF}', '{NEXT_TITLE}'), $dop_tags), array_merge(array($href_next, $title_next), $dop_values), $itf_element['itf_image_next']);
			}

			$values[] = $href_prev; // PREV
			$values[] = $href_next;  // NEXT
		}
		else
		{
			$values[] = ''; // PREV
			$values[] = '';  // NEXT
		}

		$values[] =  sb_parse_date($res[0][8], $itf_fields_temps['itf_date'], $itf_lang);   //  ELEM_ADD_DATE
 		// Дата последнего изменения
        if(sb_strpos($itf_element['itf_image'], '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $res[0][6],'pl_imagelib');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $itf_fields_temps['itf_change_date'], $itf_lang) : ''; //   CHANGE_DATE
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

	    if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($res[0][13] != $_SESSION['sbAuth']->getUserId())))
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
	                         ($res[0][14] != '' ? $res[0][14] : $res[0][6]).($edit_ext != 'php' ? '.'.$edit_ext : '/');
	            }
	            else
	            {
	                $edit_href .= '?imagelib_cid='.$res[0][0].'&imagelib_id='.$res[0][6];
	            }
	        }

	    	if ($edit_page != '' && isset($itf_fields_temps['itf_edit_link']))
	        {
	        	$edit = str_replace(array_merge(array('{EDIT_LINK}'), $dop_tags), array_merge(array($edit_href), $dop_values), $itf_fields_temps['itf_edit_link']);
	        }
    	}
    	if(isset($edit))
    		$values[] = $edit; //EDIT_LINK
    	else
    		$values[] = '';

		require_once (SB_CMS_PL_PATH .'/pl_comments/prog/pl_comments.php');

		// Комментарии
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

			if (fComments_Add_Comment($itf_comments_id, 'pl_imagelib', $res[0][6], (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
    			$c_count++;
	    }

	    $values[] = $c_count;

		if ($view_comments_list)
		{
		    $values[] = fComments_Get_List($itf_comments_id, 'pl_imagelib', $res[0][6], $add_comments); // LIST_COMMENTS
		}
		else
		{
		    $values[] = ''; // LIST_COMMENTS
		}

	    if ($view_comments_form)
		{
		   	$values[] = fComments_Get_Form($itf_comments_id, 'pl_imagelib', $res[0][6], $add_comments); // FORM_COMMENTS
		}
	    else
	    {
	       	$values[] = ''; // FORM_COMMENTS
	    }

	    $votes_sum = ($res[0][15] != '' && !is_null($res[0][15]) ? $res[0][15] : 0); // VOTES_SUM
	    $votes_count = ($res[0][16] != '' && !is_null($res[0][16]) ? $res[0][16] : 0); // VOTES_COUNT
	    $votes_rating = ($res[0][17] != '' && !is_null($res[0][17]) ? sprintf('%.2f', $res[0][17]) : 0); // RATING

	    if ($add_rating)
	    {
			// VOTES_FORM
		    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
			$res_vote = fVoting_Form_Submit($itf_voting_id, 'pl_imagelib', $res[0][6], $votes_sum, $votes_count, $votes_rating);
	    }

		$values[] = $votes_sum; // VOTES_SUM
		$values[] = $votes_count; // VOTES_COUNT
		$values[] = $votes_rating; // RATING

	    if ($add_rating && $view_rating_form)
	    {
			$values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($itf_voting_id, 'pl_imagelib', $res[0][6]));  // VOTES_FORM
		}
		else
		{
			$values[] = '';		//		VOTES_FORM
	    }

	    if(isset($res[0][13]) && $res[0][13] != -1 &&  sb_strpos($itf_element['itf_image'], '{USER_DATA}') !== false)
	    {
	        require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
            $values[] = fSite_Users_Get_Data($itf_user_data_id, $res[0][13]); //     USER_DATA
	    }
	    else
	    {
			$values[] = '';   //   USER_DATA
	    }

		$result = str_replace($tags, $values, $itf_element['itf_image']);
		$result =  str_replace('{IMG_NAME}', $img_name, $result);

		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		if($linked < 1)
    	{
			$GLOBALS['sbCache']->save('pl_imagelib', $result, $res[0][6]);

			@require_once SB_CMS_PL_PATH.'/pl_clouds/prog/pl_clouds.php';
    		fClouds_Init_Tags('pl_imagelib', array($res[0][6]));
    	}
		else
    	{
    	     unset($_GET['imagelib_id']);
    		 return $result;
    	}
	}

	function fImagelib_Elem_Categs($el_id, $temp_id, $params, $tag_id)
	{
	    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
	    $num_sub = 0;
	    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_imagelib', 'pl_imagelib', 'imagelib', $num_sub);
	}

    function fImagelib_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
    {
        require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
        fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_imagelib', 'pl_imagelib', 'imagelib');
    }

	// Функция проверяет нужно ли наложить водяной знак и накладывает его в случае необходимости
	function fImagelib_Draw_Watermark($file_path, $file_path_watermark, $big_path, $resize = 1)
	{
		$quality = sbPlugins::getSetting('sb_files_resize_quality');
		$watermark_position = sbPlugins::getSetting('sb_files_watermark_position');
		$watermark_opacity = sbPlugins::getSetting('sb_files_watermark_opacity');
		$watermark_margin = sbPlugins::getSetting('sb_files_watermark_margin');
		$watermark_path = sbPlugins::getSetting('sb_files_watermark_img');
		$watermark_copyright = sbPlugins::getSetting('sb_files_copyright');
		$watermark_color = sbPlugins::getSetting('sb_files_copyright_color');
		$watermark_font = sbPlugins::getSetting('sb_files_copyright_font');
		$watermark_font_size = sbPlugins::getSetting('sb_files_copyright_size');

        $water_path = $watermark_path;
        if(basename($file_path) != basename($big_path) && $resize == 1 && trim($watermark_path) != '')
        {
        	if(sb_substr($water_path, 0, 7) == 'http://')
        	{
        	   $water_path = str_replace('http://', '', $water_path);
        	   $water_path = sb_substr($water_path, sb_strpos($water_path, '/'));
        	}

			$watermark_size = $GLOBALS['sbVfs']->getimagesize(sb_strtolower($water_path));
			if(!$watermark_size)
				return false;

            // высчитывем диагональ среднего|маленького изображения (квадрат гипатинузы...)
            $raznica = 0;
			$im_size = $GLOBALS['sbVfs']->getimagesize(sb_strtolower($file_path));
            $gipat_m = ($im_size[0]*$im_size[0]) + ($im_size[1]*$im_size[1]);
            $gipat_m = sqrt($gipat_m);

			// высчитывем диагональ большого изображения
			$im_size_big = $GLOBALS['sbVfs']->getimagesize(sb_strtolower($big_path));
			$gipat_b = ($im_size_big[0]*$im_size_big[0]) + ($im_size_big[1]*$im_size_big[1]);
			$gipat_b = sqrt($gipat_b);

			// во сколько раз диагональ большого больше диагонали среднего|маленького
			$raznica = $gipat_b / $gipat_m;

            // создаем временную, уменьшенную копию водяного знака
            $wate_name = basename($water_path);
            $tmp_water_path = str_replace($wate_name, '', $water_path).time().$wate_name;

            $GLOBALS['sbVfs']->copy($water_path, $tmp_water_path);

            if(sb_resize_image($tmp_water_path, $tmp_water_path, floor($watermark_size[0] / $raznica), floor($watermark_size[1] / $raznica)) == false)
            {
                sb_add_system_message(KERNEL_PROG_PL_IMAGELIB_WATERMARK_ERROR, SB_MSG_INFORMATION);
                return false;
			}
			else
			{
				$watermark_path = $tmp_water_path;
            }
        }

        if(sb_watermark_image($file_path, $file_path_watermark, $watermark_position, $watermark_opacity,
										  $watermark_margin, $watermark_path, $watermark_copyright, $watermark_color,
										  $watermark_font, $watermark_font_size) == false)
		{
			return false;
		}

        if(isset($tmp_water_path) && $tmp_water_path != '' && $GLOBALS['sbVfs']->is_file($tmp_water_path))
        {
            //$GLOBALS['sbVfs']->delete($tmp_water_path);
        }
        return true;
	}

	// Функция генерирует маленькое или среднее изображение
	function fImagelib_Generate_Image($file_path, $file_path_middle = '', $file_path_small = '',  $big_width = '', $big_height = '')
	{
        if($file_path_middle == '' && $file_path_small == '')
        {
			if(sb_resize_image($file_path, $file_path, $big_width, $big_height))
			{
				return true;
            }
        }

        if($file_path_middle != '')
        {
			$middle_width = sbPlugins::getSetting('pl_imagelib_middle_width');
			$middle_height = sbPlugins::getSetting('pl_imagelib_middle_height');

			if(sb_resize_image($file_path, $file_path_middle, $middle_width, $middle_height))
			{
				return true;
            }
        }

        if($file_path_small != '')
        {
            $small_width = sbPlugins::getSetting('pl_imagelib_small_width');
            $small_height = sbPlugins::getSetting('pl_imagelib_small_height');

            if(sb_resize_image($file_path, $file_path_small, $small_width, $small_height))
            {
				return true;
            }
        }

		return false;
	}

	function fImagelib_Elem_Form($el_id, $temp_id, $params, $tag_id)
	{
		if (!isset($_FILES['im_file']) || @is_uploaded_file($_FILES['im_file']['tmp_name']) == false)
		{
			// просто вывод формы, данные пока не пришли
			if ($GLOBALS['sbCache']->check('pl_imagelib', $tag_id, array($el_id, $temp_id, $params)))
				return;
		}
		// достаем макеты дизайна формы добавления
		//$res = sql_param_query('SELECT itfrm_name, itfrm_lang, itfrm_form, itfrm_fields_temps, itfrm_categs_temps, itfrm_messages
		//						FROM sb_imagelib_temps_form WHERE itfrm_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_imagelib_temps_form', $temp_id);
		if (!$res)
	    {
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_IMAGELIB_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_imagelib', '');
			return;
		}

		list($itfrm_name, $itfrm_lang, $itfrm_form, $itfrm_fields_temps, $itfrm_categs_temps, $itfrm_messages) = $res[0];

	    $params = unserialize(stripslashes($params));
	    $itfrm_fields_temps = unserialize($itfrm_fields_temps);
	    $itfrm_categs_temps = unserialize($itfrm_categs_temps);
	    $itfrm_messages = unserialize($itfrm_messages);
	    if(!isset($itfrm_messages['err_edit_image']))
	    	$itfrm_messages['err_edit_image'] = '';
	    if(!isset($itfrm_messages['err_edit_user_field']))
	    	$itfrm_messages['err_edit_user_field'] = '';

		// если не пришло большое изображение выводим просто форму добавления и все
	    if (!isset($_FILES['im_file']) && trim($itfrm_form) == '')
	    {
	        $GLOBALS['sbCache']->save('pl_imagelib', '');
	        return;
	    }


	    // ID и ID раздела
	    $edit_id = -1;
		$now_cat = -1;
		if(isset($params['edit']) && $params['edit'] == '1')
		{
			//Если редактирование - узнаю id элемента
			if(isset($_GET['imagelib_id']))
			{
				$edit_id = intval($_GET['imagelib_id']);
			}
			elseif(isset($_GET['imagelib_sid']))
			{
				$res = sql_query('SELECT im_id FROM sb_imagelib WHERE im_url = ?', $_GET['imagelib_sid']);
				if ($res)
				{
					$edit_id = $res[0][0];
				}
				else
				{
					$res = sql_query('SELECT im_id FROM sb_imagelib WHERE im_id = ?', $_GET['imagelib_sid']);
					if ($res)
					{
						$edit_id = $res[0][0];
					}
				}
			}

			//Если редактирование - узнаю id текущего раздела

			if(isset($_GET['imagelib_cid']))
			{
				$now_cat = intval($_GET['imagelib_cid']);
			}
			elseif(isset($_GET['imagelib_scid']))
			{
				$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url = ? AND cat_ident="pl_imagelib"', $_GET['imagelib_scid']);
				if ($res)
				{
					$now_cat = $res[0][0];
				}
				else
				{
					$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id = ?', $_GET['imagelib_scid']);
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

		//Получаю поля изображения
		if($edit_id > 0)
		{
			$element_fields = sql_query('SELECT im_title, im_date, im_url, im_order_num, im_desc, im_big, im_middle, im_small, im_user_id FROM sb_imagelib WHERE im_id = ?d', $edit_id);

			$res = sql_query('SELECT t.ct_tag FROM sb_clouds_links l, sb_clouds_tags t WHERE l.cl_ident="pl_imagelib" AND l.cl_el_id = ?d AND t.ct_id = l.cl_tag_id', $edit_id);

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

		if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($element_fields[0][8] != $_SESSION['sbAuth']->getUserId())))
	    {
	        //Можно редактировать только свои новости
	        $GLOBALS['sbCache']->save('pl_imagelib', $itfrm_messages['err_edit_user_field']);
	        return;
	    }
	    elseif(isset($params['registred_users_add_only']) && $params['registred_users_add_only'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
	    {
	        //Только зарегистрированный пользователь может добавлять новость
	        $GLOBALS['sbCache']->save('pl_imagelib', $itfrm_messages['err_add_user_field']);
	        return;
	    }

		// Определение инпутов
		$im_title = '';
		$im_date = '';
		$im_url = '';
		$im_order_num = '';
		$im_desc = '';
		$im_tags = '';
		$im_file_now = '';
		$im_middle_file_now = '';
		$im_small_file_now = '';
	    if(isset($_POST['im_title'])) // Название
			$im_title =  $_POST['im_title'];
		elseif($edit_id > 0 && isset($element_fields[0][0]))
			$im_title = $element_fields[0][0];

		if (isset($_POST['im_date'])) // Дата
			$im_date = $_POST['im_date'];
		elseif($edit_id > 0 && isset($element_fields[0][1]))
			$im_date = sb_parse_date($element_fields[0][1], $itfrm_fields_temps['date_temps']);

		if(isset($_POST['im_url'])) // Урл
			$im_url = $_POST['im_url'];
		elseif($edit_id > 0 && isset($element_fields[0][2]))
			$im_url = $element_fields[0][2];

		if(isset($_POST['im_order_num'])) // Индекс сортировки
			$im_order_num = $_POST['im_order_num'];
		elseif($edit_id > 0 && isset($element_fields[0][3]))
			$im_order_num = $element_fields[0][3];

		if(isset($_POST['im_desc'])) // Описание
			$im_desc = $_POST['im_desc'];
		elseif($edit_id > 0 && isset($element_fields[0][4]))
			$im_desc = $element_fields[0][4];

		if(isset($_POST['im_tags'])) // Тэги
			$im_tags = $_POST['im_tags'];
		elseif($edit_id > 0 && $element_fields_tags != '')
			$im_tags = $element_fields_tags;

		if($edit_id > 0 && isset($element_fields[0][5]))  // Текущее большое фото
			$im_file_now = $element_fields[0][5];

		if($edit_id > 0 && isset($element_fields[0][6]))  // Текущее среднее фото
			$im_middle_file_now = $element_fields[0][6];

		if($edit_id > 0 && isset($element_fields[0][7]))  // Текущее маленькое фото
			$im_small_file_now = $element_fields[0][7];

		//Разделы
	    $im_categ = array();
	    if (isset($_POST['im_categ']))
	    {
		    if (is_array($_POST['im_categ']))
	    	{
	    		$im_categ = $_POST['im_categ'];
	    	}
	    	else
	    	{
	        	$im_categ[] = intval($_POST['im_categ']);
	    	}
	    }
		elseif($edit_id > 0)
		{
			$im_categ = null;
		}
	    elseif (isset($params['rubrikator_form']) && $params['rubrikator_form'] == 1)
	    {
	    	if (isset($_GET['imagelib_cid']))
	    	{
	        	$im_categ[] = intval($_GET['imagelib_cid']);
	    	}
		    elseif (isset($_GET['imagelib_scid']))
		    {
				$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_imagelib"', $_GET['imagelib_scid']);
				if ($res)
				{
					$im_categ[] = $res[0][0];
		        }
		        else
		        {
			        $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_id=?d', $_GET['imagelib_scid']);
					if ($res)
			        {
						$im_categ[] = $res[0][0];
			        }
		        }
		    }
	    }
		$tags = array();
		$values = array();

    	if(isset($_SESSION['sbAuth']))
		{
			$im_author = $_SESSION['sbAuth']->getUserLogin();
			$im_email = $_SESSION['sbAuth']->getUserEmail();
		}
		else
		{
			$im_author = '';
			$im_email = '';
		}

		$message_tags = array('{TITLE}', '{DATE}', '{AUTHOR}', '{EMAIL}', '{DESCRIPTION}');
		$message_values = array($im_title, sb_parse_date(time(), $itfrm_fields_temps['date_val'], SB_CMS_LANG), $im_author, $im_email, $im_desc);

		$message = '';
		$fields_message = '';

		$message_msg = '';

		if (isset($_POST['pl_plugin_ident']) && $_POST['pl_plugin_ident'] == 'pl_imagelib')
	    {
			// проверка данных и сохранение
			$error = false;
			if(!@is_uploaded_file($_FILES['im_file']['tmp_name']) && $edit_id < 1)
			{
				$error = true;

				$tags = array_merge($tags, array('{IM_FILE_SELECT_START}', '{IM_FILE_SELECT_END}'));
				$values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

				$fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
		    }

		    if(isset($params['registred_users_edit_link']) && $params['registred_users_edit_link'] == 1 && $edit_id > 0 && (!isset($_SESSION['sbAuth']) || ($element_fields[0][8] != $_SESSION['sbAuth']->getUserId())))
	        {
	        	//Можно редактировать только свои новости
	        	$error = true;
	        	$fields_message = $itfrm_messages['err_edit_user_field'];
	        }
	        elseif(isset($params['registred_users_add_only']) && $params['registred_users_add_only'] == 1 && $edit_id < 1 && !isset($_SESSION['sbAuth']))
	        {
	        	//Только зарегистрированный пользователь может добавлять новость
	        	$error = true;
	        	$fields_message = $itfrm_messages['err_add_user_field'];
	        }

	        if(isset($itfrm_fields_temps['date_need']) && $itfrm_fields_temps['date_need'] == 1 && (!isset($_POST['im_date']) || $_POST['im_date'] == ''))
			{
				// Если поле "Дата" является обязательным
				$error = true;
				$tags = array_merge($tags, array('{IM_DATE_SELECT_START}', '{IM_DATE_SELECT_END}'));
				$values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

				$fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
			}
			if(isset($itfrm_fields_temps['url_need']) && $itfrm_fields_temps['url_need'] == 1 && (!isset($_POST['im_url']) || $_POST['im_url'] == ''))
			{
				// Если поле "Псевдостатический адрес (ЧПУ)" является обязательным
				$error = true;
				$tags = array_merge($tags, array('{IM_URL_SELECT_START}', '{IM_URL_SELECT_END}'));
				$values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

				$fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
			}

			if(isset($itfrm_fields_temps['sort_need']) && $itfrm_fields_temps['sort_need'] == 1 && (!isset($_POST['im_order_num']) || $_POST['im_order_num'] == ''))
			{
				// Если поле "Индекс сортировки" является обязательным
				$error = true;
				$tags = array_merge($tags, array('{IM_SORT_SELECT_START}', '{IM_SORT_SELECT_END}'));
				$values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

				$fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
			}

            if(isset($itfrm_fields_temps['title_need']) && $itfrm_fields_temps['title_need'] == 1 && $im_title == '')
            {
                $error = true;

                $tags = array_merge($tags, array('{IM_TITLE_SELECT_START}', '{IM_TITLE_SELECT_END}'));
                $values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

                $fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field'])  : '';
            }

            if(isset($itfrm_fields_temps['desc_need']) && $itfrm_fields_temps['desc_need'] == 1 && $im_desc == '')
            {
                $error = true;

                $tags = array_merge($tags, array('{IM_DESCR_SELECT_START}', '{IM_DESCR_SELECT_END}'));
                $values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

                $fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
            }

            if(isset($itfrm_fields_temps['tags_need']) && $itfrm_fields_temps['tags_need'] == 1 && $im_tags == '')
            {
                $error = true;

                $tags = array_merge($tags, array('{IM_TAGS_SELECT_START}', '{IM_TAGS_SELECT_END}'));
                $values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

                $fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
            }

            if(isset($itfrm_fields_temps['cat_list_need']) && $itfrm_fields_temps['cat_list_need'] == 1)
            {
            	$found = false;
            	if(is_array($im_categ))
            	{
					foreach ($im_categ as $value)
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

	                $tags = array_merge($tags, array('{IM_CATEGS_LIST_SELECT_START}', '{IM_CATEGS_LIST_SELECT_END}'));
	                $values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

	                $fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
				}
            }

	        // проверяем код каптчи
	        if (sb_strpos($itfrm_form, '{CAPTCHA}') !== false || sb_strpos($itfrm_form, '{CAPTCHA_IMG}') !== false)
	        {
				if(!sbProgCheckTuring('im_captcha', 'captcha_code'))
				{
					$error = true;

					$tags = array_merge($tags, array('{IM_CAPTCHA_SELECT_START}', '{IM_CAPTCHA_SELECT_END}'));
					$values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

					$message .= isset($itfrm_messages['err_captcha_code']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_captcha_code']) : '' ;
				}
			}

			//	Если указан конкретный раздел добавляем в него. Если используется связь с выводом разделов добавлем в соответствующий раздел.
			//	Или добавляем в раздел выбранный при связке компонента.
			if (is_array($im_categ))
	        {
		        $cat_ids = array();

		        if(count($im_categ) == 0)
		        {
		        	$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', explode('^', $params['ids']));
		        }
		        else
		        {
		        	$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id IN (?a)', $im_categ);
		        }

				if ($res)
		        {
		            foreach ($res as $val)
		            {
		                list($cat_id) = $val;
		                $cat_ids[] = $cat_id;
		            }
		        }

				// проверяем права на добавление
				$closed_cats = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
		        $close_ids = array();
		        if($closed_cats)
		        {
		            foreach($closed_cats as $value)
		            {
		                $close_ids[] = $value[0];
		            }
		            $cat_ids = sbAuth::checkRights($close_ids, $cat_ids, 'pl_imagelib_edit');

		            if(count($cat_ids) < 1)
		            {
						$error = true;
						$message .= isset($itfrm_messages['not_have_rights_add']) ? str_replace($message_tags, $message_values, $itfrm_messages['not_have_rights_add']) : '' ;
		            }
		        }
	        }
	        else
	        {
	        	$cat_ids = null;
	        }

			require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	        $users_error = false;
			$layout = new sbLayout();
			$row = $layout->checkPluginInputFields('pl_imagelib', $users_error, $itfrm_fields_temps, $edit_id, 'sb_imagelib', 'im_id', false, $itfrm_fields_temps['date_temps']);

	        if ($users_error)
	        {
	            foreach($row as $im_name => $im_array)
	            {
	                $im_error = $im_array['error'];
	                $im_tag = $im_array['tag'];

	                $tags = array_merge($tags, array('{'.sb_strtoupper($im_tag).'_SELECT_START}', '{'.sb_strtoupper($im_tag).'_SELECT_END}'));
	                $values = array_merge($values, array($itfrm_fields_temps['select_start'], $itfrm_fields_temps['select_end']));

	                switch($im_error)
	                {
	                    case 2:
	                        $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$im_name]) ? $_FILES[$im_name]['name'] : '')), $itfrm_messages['err_save_file']) : '';
	                        break;

	                    case 3:
	                        $message .= isset($itfrm_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES[$im_name]) ? $_FILES[$im_name]['name'] : ''), $im_array['file_types'])), $itfrm_messages['err_type_file']) : '';
	                        break;

	                    case 4:
	                        $message .= isset($itfrm_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$im_name]) ? $_FILES[$im_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $itfrm_messages['err_size_too_large']) : '';
	                        break;

	                    case 5:
	                        $message .= isset($itfrm_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$im_name]) ? $_FILES[$im_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $itfrm_messages['err_img_size']) : '';
	                        break;

	                    default:
	                        $fields_message = isset($itfrm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $itfrm_messages['err_add_necessary_field']) : '';
	                        break;
	                }
	            }
	        }

	        $error = $error || $users_error;
	        if ($error)
	        {
	            $layout->deletePluginFieldsFiles();
	        }
	        else
	        {
	        	//ЗАПИСЬ В БАЗУ
				//Добавление/редактирвоание даты
		        if(!isset($_POST['im_date']) || trim($_POST['im_date']) == '')
		        {
		        	if($edit_id > 0)
		        	{
		        		if(isset($row['im_date']))
		        			unset($row['im_date']);
		        	}
		        	else
		        	{
						$row['im_date'] = time();
		        	}
		        }
		        else
		        {
		        	$im_date = sb_datetoint($_POST['im_date'], $itfrm_fields_temps['date_temps']);
		        	if($im_date == null)
		        		unset($row['im_date']);
		        	else
		        		$row['im_date'] = $im_date;
		        }

			    //Добавление/редактирование ЧПУ
		        if(!isset($_POST['im_url']))
		        {
		        	// Если не пришел чпу
		        	if($edit_id > 0)
		        	{
		        		// При редактировании оставляем все как есть
		        		if(isset($row['im_url']))
		        			unset($row['im_url']);
		        	}
		        	else
		        	{
		        		// При добавлении
		        		if($im_title != '')
		        		{
		        			// Если известен title - генерируем
		        			$row['im_url'] = sb_check_chpu('', '', $im_title, 'sb_imagelib', 'im_url', 'im_id');
		        		}
		        		else
		        		{
		        			// Если урл не пришел, и названия нет не в посте на в базе, урл оставляю пустым
		        			if(isset($row['im_url']))
		        				unset ($row['im_url']);
		        		}
		        	}
		        }
		        elseif(isset($element_fields[0][2]) && $element_fields[0][2] == $_POST['im_url'])
		        {
		        	//Если новый чпу совпадает со старым - оставляем в базе как есть
		        	if(isset($row['im_url']))
		        		unset($row['im_url']);
		        }
		        else
		        {
		        	// Если пришел урл
		        	if($im_title == '' && $_POST['im_url'] == '')
		        	{
		        		// но урл и тайтл пустые - оставляем как есть
		        		if(isset($row['im_url']))
		        			unset ($row['im_url']);
		        	}
		        	else
		        	{
		        		// Если есть либо урл, либо тайтл, либо оба - генерируем(проверяем) урл
			        	if($edit_id > 0)
			        	{
			        		$row['im_url'] = sb_check_chpu($edit_id, $_POST['im_url'], $im_title, 'sb_imagelib', 'im_url', 'im_id');
			        	}
			        	else
			        	{
			        		$row['im_url'] = sb_check_chpu('', $_POST['im_url'], $im_title, 'sb_imagelib', 'im_url', 'im_id');
			        	}
		        	}
		        }

		        //Добавление/редактирвоание индекса сортировки
				if(!isset($_POST['im_order_num']) || intval($_POST['im_order_num']) == 0)
				{
					if($edit_id > 0)
					{
						if(isset($row['im_order_num']))
		        			unset($row['im_order_num']);
					}
		        	else
		        	{
			        	$res = sql_query('SELECT MAX(im_order_num) FROM sb_imagelib');
						if ($res)
						    $row['im_order_num'] = intval($res[0][0])+10;
				        else
				        	$row['im_order_num'] = 0;
			    	}
				}
				else
				{
					$row['im_order_num'] = intval($_POST['im_order_num']);
				}

				// Загрузка изображений
				require_once SB_CMS_LIB_PATH . '/sbUploader.inc.php';

				$uid = time();
				$file_path_big = sb_strtolower(SB_SITE_USER_UPLOAD_PATH.'/imagelib/'.$uid.sb_strtolat($_FILES['im_file']['name']));

                $GLOBALS['sbVfs']->mLocal = true;
                $im = $GLOBALS['sbVfs']->getimagesize($_FILES['im_file']['tmp_name']);
                $GLOBALS['sbVfs']->mLocal = false;

				$b_im_w = $im[0];
				$b_im_h = $im[1];

				if(isset($_FILES['im_middle_file']) && $_FILES['im_middle_file']['name'] != '')
					$file_path_middle = sb_strtolower(SB_SITE_USER_UPLOAD_PATH.'/imagelib/m_'.$uid.sb_strtolat($_FILES['im_middle_file']['name']));
				else
					$file_path_middle = sb_strtolower(SB_SITE_USER_UPLOAD_PATH.'/imagelib/m_'.$uid.sb_strtolat($_FILES['im_file']['name']));

                if(isset($_FILES['im_small_file']) && $_FILES['im_small_file']['name'] != '')
                    $file_path_small = sb_strtolower(SB_SITE_USER_UPLOAD_PATH.'/imagelib/s_'.$uid.sb_strtolat($_FILES['im_small_file']['name']));
                else
                    $file_path_small = sb_strtolower(SB_SITE_USER_UPLOAD_PATH.'/imagelib/s_'.$uid.sb_strtolat($_FILES['im_file']['name']));

				$ext = explode(' ', sbPlugins::getSetting('pl_imagelib_max_upload_ext'));

				$uploader = new sbUploader();
				$uploader->setMaxFileSize(sbPlugins::getSetting('pl_imagelib_max_upload_size'));
				$uploader->setMaxImageSize(sbPlugins::getSetting('pl_imagelib_max_upload_width'), sbPlugins::getSetting('pl_imagelib_max_upload_height'));

// большое изображение
				// Нужно ли грузить большое изображение, или оставить старое
				$is_big_edit = true;
				if($edit_id > 0 && (!isset($itfrm_fields_temps['img_file_need']) || $itfrm_fields_temps['img_file_need'] != 1) && (!isset($_FILES['im_file']['tmp_name']) || $_FILES['im_file']['tmp_name'] == ''))
				{
					$is_big_edit = false;
				}
				if($is_big_edit && $uploader->upload('im_file' , $ext) == false)
				{
					$error = true;
					switch($uploader->getErrorCode())
					{
						case 2:
	                        $message .= isset($itfrm_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['im_file']) ? $_FILES['im_file']['name'] : ''), sbPlugins::getSetting( 'pl_imagelib_max_upload_size' ))), $itfrm_messages['err_size_too_large']) : '';
	                        break;

						case 3:
                            $message .= isset($itfrm_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES['im_file']) ? $_FILES['im_file']['name'] : ''), sbPlugins::getSetting('pl_imagelib_max_upload_width'), sbPlugins::getSetting('pl_imagelib_max_upload_height'))), $itfrm_messages['err_img_size']) : '';
	                        break;

	                    case 4:
	                        $message .= isset($itfrm_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES['im_file']) ? $_FILES['im_file']['name'] : ''), sbPlugins::getSetting('pl_imagelib_max_upload_ext'))), $itfrm_messages['err_type_file']) : '';
	                        break;

	                    case 1:
	                    case 5:
						case 6:
	                        $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_file']) ? $_FILES['im_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
	                        break;
					}
				}

				if($is_big_edit && !$error && $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/imagelib/', $uid.sb_strtolower($_FILES['im_file']['name'])) == false)
				{
					$error = true;

					if($uploader->getErrorCode() == 5 || $uploader->getErrorCode() == 6)
					{
                        $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_file']) ? $_FILES['im_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
					}
				}


				$big_width = sbPlugins::getSetting('pl_imagelib_big_width');
				$big_height = sbPlugins::getSetting('pl_imagelib_big_height');
				if($is_big_edit && $big_width != '' && $big_height != '')
				{
					if(!$error && fImagelib_Generate_Image($file_path_big, '', '', $big_width, $big_height) == false)
                    {
						$error = true;
						$message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                    }
				}


//	Среднее изображение
	        	$is_middle_edit = true;
				if($edit_id > 0 &&
				!isset($itfrm_fields_temps['img_middle_need']) &&
				(!isset($_FILES['im_middle_file']['tmp_name']) ||
				(isset($_FILES['im_middle_file']['tmp_name']) && $_FILES['im_middle_file']['tmp_name'] == '')))
				{
					$is_middle_edit = false;
				}

				if(isset($_FILES['im_middle_file']['tmp_name']) && $_FILES['im_middle_file']['tmp_name'] != '')
				{
                    $GLOBALS['sbVfs']->mLocal = true;
                    $im = $GLOBALS['sbVfs']->getimagesize($_FILES['im_middle_file']['tmp_name']);
                    $GLOBALS['sbVfs']->mLocal = false;

                    $height = sbPlugins::getSetting('pl_imagelib_middle_height');
                    $width = sbPlugins::getSetting('pl_imagelib_middle_width');
                }
                else
                {
                    $im = array('', '');
                    $width = '';
                    $height = '';
                }
				// Если обязательно, и есть переменная, но она пуста или
				// Если обязательно, но неверные размеры, и переменная пуста или
				// Если необязательно, переменная заданна, но она пуста или
				// Если необязательно, и размеры неверные или
				// Если переменной не существет

                if((isset($itfrm_fields_temps['img_middle_need']) && $itfrm_fields_temps['img_middle_need'] == 1 && isset($_FILES['im_middle_file']['tmp_name']) && $_FILES['im_middle_file']['tmp_name'] == '') ||
                   (isset($itfrm_fields_temps['img_middle_need']) && $itfrm_fields_temps['img_middle_need'] == 1 && ($im[0] > $width || $im[1] > $height) && $_FILES['im_middle_file']['tmp_name'] != '') ||
                   (!isset($itfrm_fields_temps['img_middle_need']) && isset($_FILES['im_middle_file']['tmp_name']) && $_FILES['im_middle_file']['tmp_name'] == '') ||
                   (!isset($itfrm_fields_temps['img_middle_need']) && ($im[0] > $width || $im[1] > $height)) ||
                   (!isset($_FILES['im_middle_file']['tmp_name']))
                )
                {
                    if($is_middle_edit && !$error && fImagelib_Generate_Image($file_path_big, $file_path_middle, '') == false)
                    {
                        $error = true;
                        $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                    }
                }
                elseif((!isset($itfrm_fields_temps['img_middle_need']) && $_FILES['im_middle_file']['tmp_name'] != '') ||
                        (isset($_FILES['im_middle_file']['tmp_name']) && $_FILES['im_middle_file']['tmp_name'] != '' && isset($itfrm_fields_temps['img_middle_need']) && $itfrm_fields_temps['img_middle_need'] == 1 &&  ($im[0] <= $width && $im[1] <= $height)))
                {
                	if(isset($_FILES['im_middle_file']) && $_FILES['im_middle_file']['tmp_name'] != '')
                	{
                        if($is_middle_edit && $uploader->upload('im_middle_file', $ext) == false)
                        {
                            $error = true;
  				            switch($uploader->getErrorCode())
                            {
                                case 2:
                                    $message .= isset($itfrm_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : ''), sbPlugins::getSetting( 'pl_imagelib_max_upload_size' ))), $itfrm_messages['err_size_too_large']) : '';
                                    break;

                                case 3:
                                    $message .= isset($itfrm_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : ''), sbPlugins::getSetting('pl_imagelib_max_upload_width'), sbPlugins::getSetting('pl_imagelib_max_upload_height'))), $itfrm_messages['err_img_size']) : '';
                                    break;

                                case 4:
                                    $message .= isset($itfrm_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : ''), sbPlugins::getSetting('pl_imagelib_max_upload_ext'))), $itfrm_messages['err_type_file']) : '';
                                    break;

                                case 1:
                                case 5:
                                case 6:
                                    $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                                    break;
                            }
                        }

                        if($is_middle_edit && !$error && $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/imagelib/', 'm_'.$uid.sb_strtolower($_FILES['im_middle_file']['name'])) == false)
                        {
                            $error = true;
                            if($uploader->getErrorCode() == 5 || $uploader->getErrorCode() == 6)
                            {
								$message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                            }
                        }
                    }
                }

// Маленькое изображение
	        	$is_small_edit = true;
				if($edit_id > 0 && (!isset($itfrm_fields_temps['img_small_need']) || $itfrm_fields_temps['img_small_need'] != 1) && (!isset($_FILES['im_small_file']['tmp_name']) || $_FILES['im_small_file']['tmp_name'] == ''))
				{
					$is_small_edit = false;
				}
                if($is_small_edit && isset($_FILES['im_small_file']['tmp_name']) && $_FILES['im_small_file']['tmp_name'] != '')
                {
	                $GLOBALS['sbVfs']->mLocal = true;
	                $im = $GLOBALS['sbVfs']->getimagesize($_FILES['im_small_file']['tmp_name']);
	                $GLOBALS['sbVfs']->mLocal = false;

                    $height = sbPlugins::getSetting('pl_imagelib_small_height');
                    $width = sbPlugins::getSetting('pl_imagelib_small_width');
                }
                else
                {
                	$im = array('', '');
                    $width = '';
                    $height = '';
                }

                if((isset($itfrm_fields_temps['img_small_need']) && $itfrm_fields_temps['img_small_need'] == 1 && isset($_FILES['im_small_file']['tmp_name']) && $_FILES['im_small_file']['tmp_name'] == '') ||
                   (isset($itfrm_fields_temps['img_small_need']) && $itfrm_fields_temps['img_small_need'] == 1 && ($im[0] > $width || $im[1] > $height) && $_FILES['im_small_file']['tmp_name'] != '') ||
                   (!isset($itfrm_fields_temps['img_small_need']) && isset($_FILES['im_small_file']['tmp_name']) && $_FILES['im_small_file']['tmp_name'] == '') ||
                   (!isset($itfrm_fields_temps['img_small_need']) && ($im[0] > $width || $im[1] > $height)) ||
                   (!isset($_FILES['im_small_file']['tmp_name']))
                )
                {
                    if($is_small_edit && !$error && fImagelib_Generate_Image($file_path_big, '', $file_path_small) == false)
                    {
                        $error = true;
                        $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                    }
                }
                elseif((!isset($itfrm_fields_temps['img_small_need']) && $_FILES['im_small_file']['tmp_name'] != '') ||
                    (isset($_FILES['im_small_file']['tmp_name']) && $_FILES['im_small_file']['tmp_name'] != '' && isset($itfrm_fields_temps['img_small_need']) && $itfrm_fields_temps['img_small_need'] == 1 &&  ($im[0] <= $width && $im[1] <= $height)))
                {
                    if(isset($_FILES['im_small_file']) && $_FILES['im_small_file']['tmp_name'] != '')
                    {
						// маленькое изображение
                        if($is_small_edit && $uploader->upload('im_small_file', $ext) == false)
                        {
                            $error = true;
                            switch($uploader->getErrorCode())
                            {
                                case 2:
                                    $message .= isset($itfrm_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : ''), sbPlugins::getSetting( 'pl_imagelib_max_upload_size' ))), $itfrm_messages['err_size_too_large']) : '';
                                    break;

                                case 3:
                                    $message .= isset($itfrm_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : ''), sbPlugins::getSetting('pl_imagelib_max_upload_width'), sbPlugins::getSetting('pl_imagelib_max_upload_height'))), $itfrm_messages['err_img_size']) : '';
                                    break;

                                case 4:
                                    $message .= isset($itfrm_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : ''), sbPlugins::getSetting('pl_imagelib_max_upload_ext'))), $itfrm_messages['err_type_file']) : '';
                                    break;

                                case 1:
                                case 5:
                                case 6:
                                    $message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                                    break;
                            }
                        }

                        if($is_small_edit && !$error && $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/imagelib/', 's_'.$uid.sb_strtolower($_FILES['im_small_file']['name'])) == false)
                        {
                            $error = true;
                            if($uploader->getErrorCode() == 5 || $uploader->getErrorCode() == 6)
                            {
								$message .= isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                            }
                        }
                    }
                }
                if(isset($_FILES['im_file']['tmp_name']) && $_FILES['im_file']['tmp_name'] != '' && (!$error && sbPlugins::getSetting('sb_files_watermark') == 1 && (!isset($params['watermark_big']) || $params['watermark_big'] == 1)))
                {
                    if(fImagelib_Draw_Watermark($file_path_big, $file_path_big, SB_SITE_USER_UPLOAD_PATH.'/imagelib/'.$uid.sb_strtolat($_FILES['im_file']['name'])) == false)
                    {
                        $error = true;
                        sb_add_system_message(KERNEL_PROG_PL_IMAGELIB_WATERMARK_BIG_ERROR, SB_MSG_INFORMATION);
                        $message_msg = isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_file']) ? $_FILES['im_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                    }
                }

                if(isset($_FILES['im_middle_file']['tmp_name']) && $_FILES['im_middle_file']['tmp_name'] != '' && (!$error && sbPlugins::getSetting('sb_files_watermark') == 1 && (!isset($params['watermark_middle']) || $params['watermark_middle'] == 1)))
				{
					$resize_mid = (isset($params['diminish_middle']) && $params['diminish_middle'] == 1 ? 1 : 0);
					if(fImagelib_Draw_Watermark($file_path_middle, $file_path_middle, SB_SITE_USER_UPLOAD_PATH.'/imagelib/'.$uid.sb_strtolat($_FILES['im_file']['name']), $resize_mid) == false)
					{
						$error = true;
						sb_add_system_message(KERNEL_PROG_PL_IMAGELIB_WATERMARK_MIDDLE_ERROR, SB_MSG_INFORMATION);
                        $message_msg = isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_middle_file']) ? $_FILES['im_middle_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
                	}
				}
				if(isset($_FILES['im_small_file']['tmp_name']) && $_FILES['im_small_file']['tmp_name'] != '' && (!$error && sbPlugins::getSetting('sb_files_watermark') == 1 && (!isset($params['watermark_small']) || $params['watermark_small'] == 1)))
				{
					$resize_sm = (isset($params['diminish_small']) && $params['diminish_small'] == 1 ? 1 : 0);
					if(fImagelib_Draw_Watermark($file_path_small, $file_path_small, SB_SITE_USER_UPLOAD_PATH.'/imagelib/'.$uid.sb_strtolat($_FILES['im_file']['name']), $resize_sm) == false)
                    {
						$error = true;
						sb_add_system_message(KERNEL_PROG_PL_IMAGELIB_WATERMARK_SMALL_ERROR, SB_MSG_INFORMATION);
                        $message_msg = isset($itfrm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES['im_small_file']) ? $_FILES['im_small_file']['name'] : '')), $itfrm_messages['err_save_file']) : '';
					}
                }

				if(!$error)
				{
					$im_title = ($im_title == '') ? KERNEL_PROG_PL_IMAGELIB_KER_NEW_IMAGE_TITLE.time() : $im_title;

					$row['im_title'] = $im_title;
					$row['im_desc'] = $im_desc;
					if($is_big_edit)
					{
						$row['im_big'] = $file_path_big;
						$row['im_big_from_server'] = 0;
					}
                    elseif(isset($_POST['im_file_delete']))
                    {
                        //если файл картинки удаляется, а не заменяется новым
                        $row['im_big'] = '';
                        $row['im_big_from_server'] = 0;
                    }

					if($is_middle_edit)
					{
						$row['im_middle'] = $file_path_middle;
						$row['im_middle_from_server'] = 0;
					}
                    elseif(isset($_POST['im_middle_file_delete']))
                    {
                        $row['im_middle'] = '';
                        $row['im_middle_from_server'] = 0;
                    }

					if($is_small_edit)
					{
						$row['im_small'] = $file_path_small;
						$row['im_small_from_server'] = 0;
					}
                    elseif(isset($_POST['im_small_file_delete']))
                    {
                        $row['im_small'] = '';
                        $row['im_small_from_server'] = 0;
                    }

					$row['im_gal'] = isset($params['premod_images']) ? 0 : 1 ;
					$row['im_user_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : null;
					$row['im_active_date_start'] = null;
					$row['im_active_date_end'] = null;

					$image_id = sbProgAddElement('sb_imagelib', 'im_id', $row, $cat_ids, $edit_id, $now_cat);

					if(!$image_id)
					{
		                $error = true;
		                $message .= isset($itfrm_messages['err_add_image']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array($_FILES['im_file']['name'])), $itfrm_messages['err_add_image']) : '';
		                // Если не прошло добавление - удаляем загруженные фото
						if($GLOBALS['sbVfs']->exists($file_path_big) && $GLOBALS['sbVfs']->is_file($file_path_big) && $file_path_big != '')
						{
							$GLOBALS['sbVfs']->delete($file_path_big);
						}

						if($GLOBALS['sbVfs']->exists($file_path_middle) && $GLOBALS['sbVfs']->is_file($file_path_middle) && $file_path_middle != '')
						{
							$GLOBALS['sbVfs']->delete($file_path_middle);
						}

						if($GLOBALS['sbVfs']->exists($file_path_small) && $GLOBALS['sbVfs']->is_file($file_path_small) && $file_path_small != '')
						{
							$GLOBALS['sbVfs']->delete($file_path_small);
						}
						sb_add_system_message(sprintf(KERNEL_PROG_PL_IMAGELIB_KER_ADD_SYSTEMLOG_ERROR, $im_title), SB_MSG_WARNING);
					}
					else
		            {
		                include_once(SB_CMS_PL_PATH.'/pl_clouds/pl_clouds.inc.php');
		                fClouds_Set_Field($image_id, 'pl_imagelib', $im_tags);

		                // Если стоят галочки - удаляем старые фото
		                if(isset($_POST['im_file_delete']) && $GLOBALS['sbVfs']->exists($element_fields[0][5]) && $GLOBALS['sbVfs']->is_file($element_fields[0][5]) && $element_fields[0][5] != '')
						{
							$GLOBALS['sbVfs']->delete($element_fields[0][5]);
						}
		            	if(isset($_POST['im_middle_file_delete']) && $GLOBALS['sbVfs']->exists($element_fields[0][6]) && $GLOBALS['sbVfs']->is_file($element_fields[0][6]) && $element_fields[0][6] != '')
						{
							$GLOBALS['sbVfs']->delete($element_fields[0][6]);
						}
		            	if(isset($_POST['im_small_file_delete']) && $GLOBALS['sbVfs']->exists($element_fields[0][7]) && $GLOBALS['sbVfs']->is_file($element_fields[0][7]) && $element_fields[0][7] != '')
						{
							$GLOBALS['sbVfs']->delete($element_fields[0][7]);
						}
						if($edit_id > 0)
							sb_add_system_message(sprintf(KERNEL_PROG_PL_IMAGELIB_KER_EDIT_OK, $im_title), SB_MSG_INFORMATION);
						else
		                	sb_add_system_message(sprintf(KERNEL_PROG_PL_IMAGELIB_KER_ADD_OK, $im_title), SB_MSG_INFORMATION);
		            }
				}

		        if (!$error)
		        {
		            require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
		            $mail = new sbMail();

		            // ОТПРАВКА ПОЧТЫ
					$type = sbPlugins::getSetting('sb_letters_type');

					$tags_email = array('{ID}',
										'{DESCRIPTION}',
										'{DATE}',
										'{AUTHOR}',
										'{EMAIL}',
										'{LINK}',
										'{TITLE}',
										'{BIG_IMG_SIZE}',
										'{BIG_IMG_WIDTH}',
										'{BIG_IMG_HEIGHT}',
										'{CAT_TITLE}',
										'{CAT_ID}',
										'{CAT_URL}');

					if(isset($_SESSION['sbAuth']))
					{
						$im_author = str_replace('{VALUE}', $_SESSION['sbAuth']->getUserLogin(), $itfrm_fields_temps['author_val']);
						$im_email = str_replace('{VALUE}', $_SESSION['sbAuth']->getUserEmail(), $itfrm_fields_temps['email_val']);
					}
					else
					{
                        $im_author = '';
                        $im_email = '';
					}
					$image_add_date = time();

                    $im_desc = ($im_desc != '') ? str_replace('{VALUE}', $im_desc,  $itfrm_fields_temps['desc_val']) : '';
                    $cat_data = sql_param_query('SELECT cat_id, cat_title, cat_url FROM sb_categs WHERE cat_id = ?d', $cat_ids[0]);
                    $values_email = array($image_id, $im_desc, sb_parse_date($image_add_date, $itfrm_fields_temps['date_val'], $itfrm_lang), $im_author, $im_email, $im_url, $im_title, $_FILES['im_file']['size'], $b_im_w, $b_im_h, $cat_data[0][1], $cat_data[0][0], $cat_data[0][2]);

		            // вытаскиваем пользовательские поля
                    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_imagelib"');

					$categs_sql_fields = array();
					$users_fields = array();
					$users_values = array();

					// формируем SQL-запрос для пользовательских полей
                    if ($res && $res[0][0] != '')
                    {
                        $users_fields = unserialize($res[0][0]);
                        if ($users_fields)
                        {
                            foreach ($users_fields as $t_value)
                            {
								if ($t_value['sql'])
								{
									$tags_email[] = '{'.$t_value['tag'].'}';
                                    $users_values[] = isset($_POST[ 'user_f_'.$t_value['id'] ]) ? $_POST[ 'user_f_'.$t_value['id'] ] : '';
                                }
                            }
                        }
                    }

					$res_cat = sql_param_query('SELECT cat_id, cat_fields FROM sb_categs WHERE cat_id IN (?a)', array($cat_ids[0]));
					if ($res_cat)
					{
						foreach ($res_cat as $value)
						{
							$categs[$value[0]] = array();
							$categs[$value[0]]['fields'] = unserialize($value[1]);
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
									$tags_email[] = '{'.$value['tag'].'}';
									$categs_sql_fields[] = 'user_f_'.$value['id'];
				                }
				            }
				        }
				    }

				    $cat_values = array();
				    $num_cat_fields = count($categs_sql_fields);
				    if ($num_cat_fields > 0)
				    {
				        if (count($cat_values) == 0)
				        {
				            foreach ($categs_sql_fields as $cat_field)
				            {
				                if (isset($categs[$cat_ids[0]]['fields'][$cat_field]))
				                    $cat_values[] = $categs[$cat_ids[0]]['fields'][$cat_field];
				                else
				                    $cat_values[] = null;
                            }
                            $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $itfrm_categs_temps, array(), array(), $itfrm_lang, '', '_cat_val');
				        }
				    }

                    @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
                    $users_values = sbLayout::parsePluginFields($users_fields, $users_values, $itfrm_fields_temps, array(), array(), $itfrm_lang, '', '_val');

                    if ($users_values || $cat_values)
                        $values_email = array_merge($values_email, $users_values, $cat_values);

					$mod_emails = array();

					$mod_params_emails = explode(' ', trim(str_replace(',', ' ', $params['mod_emails'])));
					$mod_categs_emails = array();
					$mod_users_emails = array();

					$res = false;
					if(!is_null($cat_ids))
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

					//	отправляем письма и делаем переадресацию
		            if (count($mod_emails) > 0)
		            {
		                if($edit_id > 0)
		    				$email_subj = str_replace($tags_email, $values_email, $itfrm_messages['admin_subj_edit']);
		                else
		            		$email_subj = str_replace($tags_email, $values_email, $itfrm_messages['admin_subj']);

		                //чистим код от инъекций
		                $email_subj = sb_clean_string($email_subj);

		                ob_start();
		                eval(' ?>'.$email_subj.'<?php ');
		                $email_subj = trim(ob_get_clean());

		                if($edit_id > 0)
		                	$email_text = str_replace($tags_email, $values_email, $itfrm_messages['admin_text_edit']);
		                else
		                	$email_text = str_replace($tags_email, $values_email, $itfrm_messages['admin_text']);

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
                		header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'im_id='.$image_id));
            		}
            		else
        		    {
						//Если изменяем ЧПУ, то перекидываем пользователя на адрес с новым чпу
						$php_self = $GLOBALS['PHP_SELF'];
						if(isset($row['im_url']) && isset($_GET['imagelib_sid']) && $row['im_url'] != $_GET['imagelib_sid'])
						{
							$php_self = str_replace($_GET['imagelib_sid'],$row['im_url'],$php_self);
						}
						header('Location: '.sb_sanitize_header($php_self.($_SERVER['QUERY_STRING'] != '' ?  '?'.$_SERVER['QUERY_STRING'].'&im_id='.$image_id : '?im_id='.$image_id)));
        		    }
		            exit (0);
		        }
		        else
		        {
		            $layout->deletePluginFieldsFiles();
		        }
	    	}
	    }
		$message .= $fields_message.$message_msg;
	    if(isset($_GET['im_id']) && $_GET['im_id'] != '' && $message == '')
    	{
    		$new_message_values = array();

			// вытаскиваем пользовательские поля
			$mess_res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_imagelib"');

			$new_users_fields = array();
			$new_users_fields_select_sql = '';

			$message_user_tags = array();

//			формируем SQL-запрос для пользовательских полей
			if ($mess_res && $mess_res[0][0] != '')
			{
				$new_users_fields = unserialize($mess_res[0][0]);
				if ($new_users_fields)
				{
					foreach ($new_users_fields as $value)
					{
						if (isset($value['sql']) && $value['sql'] == 1)
						{
							$new_users_fields_select_sql .= ', user_f_'.$value['id'];
							$message_user_tags[] = '{'.$value['tag'].'}';
						}
					}
				}
			}

			$mess_res = sql_param_query('SELECT im_user_id, im_date, im_title, im_big, im_desc
											'.$new_users_fields_select_sql.'
											FROM sb_imagelib WHERE im_id=?d', intval($_GET['im_id']));
			if ($mess_res != false)
			{
				list ($im_usr_id, $im_date_im, $im_title_im, $im_big, $im_descr) = $mess_res[0];
				$usr_res = sql_param_query('SELECT su_login, su_email FROM sb_site_users WHERE su_id = ?d', $im_usr_id);
				if(!$usr_res)
				{
					$im_mes_author = '';
					$im_mes_email = '';
				}
				else
				{
					list($im_mes_author, $im_mes_email) = $usr_res[0];
				}

                $message_user_tags = array_merge($message_tags, array('{FILE_NAME}'), $message_user_tags);
	    		$new_message_values = array();
	    		$new_message_values[] = $im_title_im;
		    	$new_message_values[] = sb_parse_date($im_date_im, $itfrm_fields_temps['date_temps'], $itfrm_lang);
				$new_message_values[] = $im_mes_author;
				$new_message_values[] = $im_mes_email;
                $new_message_values[] = $im_descr;
                $new_message_values[] = $im_big;

		    	$new_users_values = array();
	    		$new_num_fields = count($mess_res[0]);
	    		if ($new_num_fields > 5)
	    		{
	        		for ($i = 5; $i < $new_num_fields; $i++)
	        		{
	            		$new_users_values[] = $mess_res[0][$i];
	        		}

	        		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	        		$users_values = sbLayout::parsePluginFields($new_users_fields, $new_users_values, $itfrm_fields_temps, array(), array(), $itfrm_lang, '', '');
	    		}

	    		if ($new_users_values)
	        		$new_message_values = array_merge($new_message_values, $new_users_values);
				if(isset($params['edit']) && $params['edit'] == '1')
					$message = str_replace($message_user_tags, $new_message_values, $itfrm_messages['success_edit_image']);
				else
                	$message = str_replace($message_user_tags, $new_message_values, $itfrm_messages['success_add_image']);
			}
    	}

        $tags = array_merge($tags, array('{MESSAGES}',
						                  '{ACTION}',
										  '{FILE}',
        								  '{FILE_NOW}',
						                  '{MIDDLE_FILE}',
        								  '{MIDDLE_FILE_NOW}',
					                      '{SMALL_FILE}',
        								  '{SMALL_FILE_NOW}',
									      '{TAGS}',
									      '{DESCR}',
										  '{TITLE}',
									      '{CAPTCHA}',
									      '{CAPTCHA_IMG}',
									      '{CATEGS_LIST}',
        								  '{DATE}',
									      '{URL}',
        								  '{SORT}'
        ));

	    // вывод полей формы
	    $values[] = $message;

		$edit_param = '';
		if (isset($params['edit']) && $params['edit'] == 1 && isset($_GET['imagelib_id']) && $_GET['imagelib_id'] != '' &&
			isset($_GET['imagelib_cid']) && $_GET['imagelib_cid'] != '')
		{
			$edit_param = 'imagelib_cid='.$_GET['imagelib_cid'].'&imagelib_id='.$_GET['imagelib_id'];
		}

	    $values[] = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].'&'.$edit_param : '?'.$edit_param);

	    // Большое изображение
	    $values[] = (isset($itfrm_fields_temps['file']) && $itfrm_fields_temps['file'] != '') ? $itfrm_fields_temps['file'] : '';
	    // Текущее большое изображение
	    $values[] = (isset($itfrm_fields_temps['file_now']) && $itfrm_fields_temps['file_now'] != '' && $im_file_now != '') ? str_replace('{PIC_SRC}', $im_file_now, $itfrm_fields_temps['file_now']) : '';
	    // Среднее изображение
        $values[] = (isset($itfrm_fields_temps['middle_file']) && $itfrm_fields_temps['middle_file'] != '') ? $itfrm_fields_temps['middle_file'] : '';
        // Текущее среднее изображение
	    $values[] = (isset($itfrm_fields_temps['middle_file_now']) && $itfrm_fields_temps['middle_file_now'] != '' && $im_middle_file_now != '') ? str_replace('{PIC_SRC}', $im_middle_file_now, $itfrm_fields_temps['middle_file_now']) : '';
	    // Маленькое изображение
        $values[] = (isset($itfrm_fields_temps['small_file']) && $itfrm_fields_temps['small_file'] != '') ? $itfrm_fields_temps['small_file'] : '';
        // Текущее среднее изображение
	    $values[] = (isset($itfrm_fields_temps['small_file_now']) && $itfrm_fields_temps['small_file_now'] != '' && $im_small_file_now != '') ? str_replace('{PIC_SRC}', $im_small_file_now, $itfrm_fields_temps['small_file_now']) : '';
	    // TAGS
	    $values[] = (isset($itfrm_fields_temps['tags']) && $itfrm_fields_temps['tags'] != '') ? str_replace('{VALUE}', $im_tags, $itfrm_fields_temps['tags']) : '';
	    // DESCR
	    $values[] = (isset($itfrm_fields_temps['description']) && $itfrm_fields_temps['description'] != '') ? str_replace('{VALUE}', $im_desc, $itfrm_fields_temps['description']) : '';
	    // TITLE
	    $values[] = (isset($itfrm_fields_temps['im_title']) && $itfrm_fields_temps['im_title'] != '') ? str_replace('{VALUE}', $im_title, $itfrm_fields_temps['im_title']) : '';

	    // Вывод КАПЧИ
	    if ((sb_strpos($itfrm_form, '{CAPTCHA}') !== false || sb_strpos($itfrm_form, '{CAPTCHA_IMG}') !== false) &&
	    	isset($itfrm_fields_temps['captcha']) && trim($itfrm_fields_temps['captcha']) != '' &&
	        isset($itfrm_fields_temps['img_captcha']) && trim($itfrm_fields_temps['img_captcha']) != '')
	    {
	        $turing = sbProgGetTuring();
	        if ($turing)
	        {
	            $values[] = $itfrm_fields_temps['captcha'];
	            $values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $itfrm_fields_temps['img_captcha']);
	        }
	        else
	        {
	            $values[] = $itfrm_fields_temps['captcha'];
	            $values[] = '';
	        }
	    }
	    else
	    {
	        $values[] = '';
	        $values[] = '';
	    }
		// Разделы
		if (sb_strpos($itfrm_form, '{CATEGS_LIST}') !== false)
		{
			$cat_ids = explode('^', $params['ids']);

			if (!is_array($im_categ) && $edit_id > 0)
			{
				$res = sql_query('SELECT c.cat_id FROM sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND l.link_el_id = ?d AND l.link_cat_id = c.cat_id', 'pl_imagelib', $edit_id);
				if ($res)
				{
					$im_categ = array();
					foreach ($res as $value)
					{
						$im_categ[] = $value[0];
					}
				}
			}

			$values[] = sbProgGetCategsList($cat_ids, 'pl_imagelib', $im_categ, $itfrm_fields_temps['categs_list_options'], $itfrm_fields_temps['categs_list'], 'pl_imagelib_edit');
		}
		else
		{
			$values[] = '';  //   CATEGS_LIST
		}
		// Дата
	    $values[] = (isset($itfrm_fields_temps['date']) && $itfrm_fields_temps['date'] != '') ? str_replace('{VALUE}', ($im_date != '' ? $im_date : ''), $itfrm_fields_temps['date']) : '';
	    // ЧПУ
	    $values[] = (isset($itfrm_fields_temps['url']) && $itfrm_fields_temps['url'] != '') ? str_replace('{VALUE}', $im_url, $itfrm_fields_temps['url']) : '';
		// Индекс сортироки
	    $values[] = (isset($itfrm_fields_temps['sort']) && $itfrm_fields_temps['sort'] != '') ? str_replace('{VALUE}', $im_order_num, $itfrm_fields_temps['sort']) : '';

	    @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	    sbLayout::parsePluginInputFields('pl_imagelib', $itfrm_fields_temps,  $itfrm_fields_temps['date_temps'], $tags, $values, $edit_id, 'sb_imagelib', 'im_id');

	    $result = str_replace($tags, $values, $itfrm_form);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		//чистим код от инъекций
		$result = sb_clean_string($result);

	    if (!isset($_FILES['im_file']))
	        $GLOBALS['sbCache']->save('pl_imagelib', $result);
	    else
	        eval(' ?>'.$result.'<?php ');
	}

    function fImagelib_Elem_Cloud($el_id, $temp_id, $params, $tag_id)
    {
	    if ($GLOBALS['sbCache']->check('pl_imagelib', $tag_id, array($el_id, $temp_id, $params)))
	        return;

	    $params = unserialize(stripslashes($params));
	    $cat_ids = array();

	    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['imagelib_scid']) || isset($_GET['imagelib_cid'])))
	    {
	        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
	        if (isset($_GET['imagelib_cid']))
	        {
	            $cat_ids[] = intval($_GET['imagelib_cid']);
	        }
	        else
	        {
	            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_imagelib"', $_GET['imagelib_scid']);
	            if ($res)
	            {
	                $cat_ids[] = $res[0][0];
	            }
	            else
	            {
	                $cat_ids[] = intval($_GET['imagelib_scid']);
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
							AND c.cat_ident="pl_imagelib"
							AND c2.cat_ident = "pl_imagelib"
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

	        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_imagelib_read');
	    }

	    if (count($cat_ids) == 0)
	    {
	        // указанные разделы были удалены
	        $GLOBALS['sbCache']->save('pl_imagelib', '');
	        return;
	    }

	    // вытаскиваем макет дизайна
	    $res = sql_param_query('SELECT ct_pagelist_id, ct_perpage, ct_size_from, ct_size_to
	                FROM sb_clouds_temps WHERE ct_id=?d', $temp_id);

	    if (!$res)
	    {
	        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CLOUDS_PLUGIN), SB_MSG_WARNING);
	        $GLOBALS['sbCache']->save('pl_imagelib', '');
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

	        $where_sql .= ' AND im.im_date >= '.$last.' AND im.im_date <= '.$now;
	    }
	    elseif ($params['filter'] == 'next')
	    {
	        $next = intval($params['filter_next']);
	        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

	        $where_sql .= ' AND im.im_date >= '.$now.' AND im.im_date <= '.$next;
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
	                            FROM sb_clouds_tags ct, sb_imagelib im, sb_clouds_links cl, sb_catlinks l, sb_categs c
	                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=im.im_id
	                            AND cl.cl_ident="pl_imagelib" AND cl.cl_el_id=im.im_id AND ct.ct_id=cl.cl_tag_id
	                            '.$where_sql.'
								AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
	                            AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
	                            AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
	                            AND LENGTH(ct.ct_tag) >= ?d AND LENGTH(ct.ct_tag) <= ?d
	                            GROUP BY cl.cl_tag_id '
	                            .($sort_sql != '' ? 'ORDER BY '.$sort_sql : 'ORDER BY ct.ct_tag'),
	                            $cat_ids, $ct_size_from, $ct_size_to);

	    if (!$res_tags)
	    {
	        $GLOBALS['sbCache']->save('pl_imagelib', '');
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
	    $result = fClouds_Show($res_tags, $temp_id, $pt_page_list, $tags_total, $params['page'], 'im_tag');

	    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
	    $GLOBALS['sbCache']->save('pl_imagelib', $result);
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
	function fImagelib_Get_Calendar($year, $month, $params, $rubrikator, $filter)
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

	    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['imagelib_scid']) || isset($_GET['imagelib_cid'])))
	    {
	        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
	    	if (isset($_GET['imagelib_cid']))
	        {
	            $cat_ids[] = intval($_GET['imagelib_cid']);
	        }
	        else
	        {
	            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_imagelib"', $_GET['imagelib_scid']);
	            if ($res)
	            {
	                $cat_ids[] = $res[0][0];
	            }
	            else
	            {
	                $cat_ids[] = intval($_GET['imagelib_scid']);
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
							AND c.cat_ident="pl_imagelib"
							AND c2.cat_ident = "pl_imagelib"
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

	        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_imagelib_read');
	    }

		if (count($cat_ids) == 0)
		{
			// указанные разделы были удалены
			return $result;
	    }

		// вытаскиваем макет дизайна
		$res = sql_param_query('SELECT itl_checked, itl_bottom_cat, itl_top_cat FROM sb_imagelib_temps_list WHERE itl_id=?d', $params['temp_id']);
		if (!$res)
	    {
			$itl_checked = array();
			$itl_bottom_cat = $itl_top_cat = '';
		}
		else
	    {
			list($itl_checked, $itl_bottom_cat, $itl_top_cat) = $res[0];
	    	if ($itl_checked != '')
	    	{
	        	$itl_checked = explode(' ', $itl_checked);
	    	}
	    	else
	    	{
	    		$itl_checked = array();
	    	}
	    }

	    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
	    $elems_fields_where_sql = '';

	    foreach ($itl_checked as $value)
	    {
	        $elems_fields_where_sql .= ' AND im.user_f_'.$value.'=1';
	    }

	    $now = time();
		if ($params['filter'] == 'last')
	    {
	        $last = intval($params['filter_last']) - 1;
	        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

	        $elems_fields_where_sql .= ' AND im.im_date >= '.$last.' AND im.im_date <= '.$now;
	    }
	    elseif ($params['filter'] == 'next')
	    {
	        $next = intval($params['filter_next']);
	        $next = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) + $next * 24 * 60 * 60;

	        $elems_fields_where_sql .= ' AND im.im_date >= '.$now.' AND im.im_date <= '.$next;
	    }

		$from_date = mktime(0, 0, 0, $month, 1, $year);
	    $to_date = mktime(23, 59, 59, $month, sb_get_last_day($month, $year), $year);

	    if ($from_date <= 0 || $to_date <= 0)
	    {
	    	return $result;
	    }

		$elems_fields_where_sql .= ' AND im.'.$field.' >= "'.$from_date.'" AND im.'.$field.' <= "'.$to_date.'"';

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
	    if ($itl_top_cat != '' || $itl_bottom_cat != '')
	    {
	        $categs_output = true;
	    }
	    else
	    {
	        $categs_output = false;
	    }

	    if($categs_output)
	    {
			$res = sql_param_query('SELECT im.'.$field.'
                            FROM sb_imagelib im, sb_catlinks l, sb_categs c
                            WHERE  c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=im.im_id
                            '.$elems_fields_where_sql.'
							AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
                            AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
                            AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
                            GROUP BY im.im_id
                            ORDER BY c.cat_left'.($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', im.im_date DESC').
					    	($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
	    }
	    else
	    {
			$res = sql_param_query('SELECT im.'.$field.'
							FROM sb_imagelib im, sb_catlinks l
							WHERE l.link_cat_id IN (?a) AND l.link_el_id=im.im_id
							'.$elems_fields_where_sql.'
							AND im.im_gal IN ('.sb_get_workflow_demo_statuses().')
							AND (im.im_active_date_start IS NULL OR im.im_active_date_start <= '.$now.')
							AND (im.im_active_date_end IS NULL OR im.im_active_date_end >= '.$now.')
							GROUP BY im.im_id'.
							($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY im.im_date DESC').
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
	function fImagelib_Elem_Filter($el_id, $temp_id, $params, $tag_id)
	{
		if ($GLOBALS['sbCache']->check('pl_imagelib', $tag_id, array($el_id, $temp_id, $params)))
			return;

		$res = sql_param_query('SELECT itfrm_id, itfrm_name, itfrm_lang, itfrm_form, itfrm_fields_temps
				FROM sb_imagelib_temps_form WHERE itfrm_id=?d', $temp_id);

		if(!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_imagelib', '');
			return;
		}

		list($itfrm_id, $itfrm_name, $itfrm_lang, $itfrm_form, $itfrm_fields_temps) = $res[0];

		$params = unserialize(stripslashes($params));
		$itfrm_fields_temps = unserialize($itfrm_fields_temps);

		$result = '';
		if (trim($itfrm_form) == '')
		{
			$GLOBALS['sbCache']->save('pl_imagelib', '');
			return;
		}

		$tags = array('{ACTION}', '{TEMP_ID}', '{TITLE}', '{BIG}', '{MIDDLE}', '{SMALL}', '{DESCRIPTION}', '{ID}', '{ID_LO}', '{ID_HI}', '{DATE}',
								'{DATE_LO}', '{DATE_HI}', '{SORT_SELECT}');
		if(isset($params['page']) && trim($params['page']) != '')
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
		$values[] = $itfrm_id;
		$values[] = (isset($itfrm_fields_temps['imagelib_title']) && $itfrm_fields_temps['imagelib_title'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['im_f_title']) && $_REQUEST['im_f_title'] != '' ? $_REQUEST['im_f_title'] : ''), $itfrm_fields_temps['imagelib_title']) : '');
		$values[] = (isset($itfrm_fields_temps['imagelib_big']) && $itfrm_fields_temps['imagelib_big'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['im_f_big']) && $_REQUEST['im_f_big'] != '' ? $_REQUEST['im_f_big'] : ''), $itfrm_fields_temps['imagelib_big']) : '');
		$values[] = (isset($itfrm_fields_temps['imagelib_middle']) && $itfrm_fields_temps['imagelib_middle'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['im_f_middle']) && $_REQUEST['im_f_middle'] != '' ? $_REQUEST['im_f_middle'] : ''), $itfrm_fields_temps['imagelib_middle']) : '');
		$values[] = (isset($itfrm_fields_temps['imagelib_small']) && $itfrm_fields_temps['imagelib_small'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_small']) && $_REQUEST['im_f_small'] != '' ? $_REQUEST['im_f_small'] : ''), $itfrm_fields_temps['imagelib_small']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_desc']) && $itfrm_fields_temps['imagelib_desc'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_desc']) && $_REQUEST['im_f_desc'] != '' ? $_REQUEST['im_f_desc'] : ''), $itfrm_fields_temps['imagelib_desc']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_id']) && $itfrm_fields_temps['imagelib_id'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_id']) && $_REQUEST['im_f_id'] != '' ? $_REQUEST['im_f_id'] : ''), $itfrm_fields_temps['imagelib_id']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_id_lo']) && $itfrm_fields_temps['imagelib_id_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_id_lo']) && $_REQUEST['im_f_id_lo'] != '' ? $_REQUEST['im_f_id_lo'] : ''), $itfrm_fields_temps['imagelib_id_lo']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_id_hi']) && $itfrm_fields_temps['imagelib_id_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_id_hi']) && $_REQUEST['im_f_id_hi'] != '' ? $_REQUEST['im_f_id_hi'] : ''), $itfrm_fields_temps['imagelib_id_hi']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_date']) && $itfrm_fields_temps['imagelib_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_date']) && $_REQUEST['im_f_date'] != '' ? $_REQUEST['im_f_date'] : ''), $itfrm_fields_temps['imagelib_date']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_date_lo']) && $itfrm_fields_temps['imagelib_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_date_lo']) && $_REQUEST['im_f_date_lo'] != '' ? $_REQUEST['im_f_date_lo'] : ''), $itfrm_fields_temps['imagelib_date_lo']) : '';
		$values[] = (isset($itfrm_fields_temps['imagelib_date_hi']) && $itfrm_fields_temps['imagelib_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['im_f_date_hi']) && $_REQUEST['im_f_date_hi'] != '' ? $_REQUEST['im_f_date_hi'] : ''), $itfrm_fields_temps['imagelib_date_hi']) : '';
		$values[] = sbLayout::replacePluginFieldsTagsFilterSelect('pl_imagelib', 's_f_im_', $itfrm_fields_temps['imagelib_sort_select'], $itfrm_form);

		sbLayout::parsePluginInputFields('pl_imagelib', $itfrm_fields_temps, $itfrm_fields_temps['date_temps'], $tags, $values, -1, '', '', array(), array(), false, 'im_f', '', true);

		$result = str_replace($tags, $values, $itfrm_form);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		$GLOBALS['sbCache']->save('pl_imagelib', $result);
	}

?>