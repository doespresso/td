<?php

function fPolls_Elem_List($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
	$params = unserialize(stripslashes($params));
	$cat_ids = array();

	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['polls_scid']) || isset($_GET['polls_cid'])))
	{
		// используется связь с выводом разделов и выводить следует опросы из соотв. раздела
		if (isset($_GET['polls_cid']))
		{
			$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['polls_cid']);
        	if ($res[0][0] > 0)
				$cat_ids[] = intval($_GET['polls_cid']);
		}
		else
		{
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_polls"', $_GET['polls_scid']);
            if ($res)
            {
				$cat_ids[] = $res[0][0];
			}
			else
            {
            	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['polls_scid']);
	        	if (isset($res[0][0]) && $res[0][0] > 0)
					$cat_ids[] = intval($_GET['polls_scid']);
            }
        }

		if (count($cat_ids) == 0)
	    {
	       	sb_404();
	    }
    }
    elseif($linked != 0)
    {
    	// Если опрос выводится как связанный
    	$res = sql_query('SELECT c.cat_id FROM sb_categs as c, sb_catlinks as l WHERE l.link_el_id = ?d AND c.cat_ident = ? AND l.link_src_cat_id = 0 AND c.cat_id = l.link_cat_id', $linked, 'pl_polls');
    	$cat_ids = array($res[0][0]);
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
							AND c.cat_ident="pl_polls"
							AND c2.cat_ident = "pl_polls"
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
        	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['polls_scid']) || isset($_GET['polls_cid'])))
        	{
        		sb_404();
        	}

            // указанные разделы были удалены
            return;
        }
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT spt_title, spt_lang, spt_checked, spt_count, spt_perpage, spt_delim, spt_empty, spt_top, spt_cat_top,
    //				spt_polls_top, spt_element, spt_polls_bottom, spt_cat_bottom, spt_bottom, spt_pagelist_id, spt_fields_temps,
    //				spt_categs_temps, spt_messages
	//				FROM sb_polls_temps WHERE spt_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_polls_temps', $temp_id);

	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
		return;
	}

	list($spt_title, $spt_lang, $spt_checked, $spt_count, $spt_perpage, $spt_delim, $spt_empty, $spt_top, $spt_cat_top,
					$spt_polls_top, $spt_element, $spt_polls_bottom, $spt_cat_bottom, $spt_bottom, $spt_pagelist_id, $spt_fields_temps,
					$spt_categs_temps, $spt_messages) = $res[0];

    $spt_fields_temps = unserialize($spt_fields_temps);
    $spt_categs_temps = unserialize($spt_categs_temps);
    $spt_messages = unserialize($spt_messages);

//  достаем закрытые разделы если они есть
	$closed_cats = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);

	$messages = '';
	if(isset($_REQUEST['v']))
	{
		$v = array();
		extract($_REQUEST);

		$now = time();
		$row = array();
		$error = false;

//      Через какое кол-во дней можно повторно проголосовать
		$cookie_time = sbPlugins::getSetting('sb_polls_cookie_time');

//		сколько раз можно проголосовать с одного ip
		$ip_count = sbPlugins::getSetting('sb_polls_ip_count');
		$ip = sbAuth::getIP();

		// проверяем права на добавление
		$close_ids = array();
		if($closed_cats)
		{
	        foreach($closed_cats as $key => $value)
			{
				$close_ids[] = $value[0];
			}
			$cat_ids = sbAuth::checkRights($close_ids, $cat_ids, 'pl_polls_edit');

			if(count($cat_ids) < 1)
			{
				$error = true;
				$messages .= isset($spt_messages['not_have_rights_add']) ? $spt_messages['not_have_rights_add'] : '';
	    	}
		}

		if(!$error && is_array($v) && count($v) > 0)
		{
//			делаем все необходимые проверки и создаем массив row для добавления в БД
			foreach($v as $key => $value)
			{
				$result = explode('_', $key);
				if(count($result) != 3 || !isset($_REQUEST['v'][$result[0].'_'.$result[1].'_'.$result[2]]) || $_REQUEST['v'][$result[0].'_'.$result[1].'_'.$result[2]] == '')
				{
					continue;
				}

				if($result[0] == 'radio')
				{
					$id_opt = $_REQUEST['v'][$result[0].'_'.$result[1].'_'.$result[2]];
				}
				else
				{
					$id_opt = $result[2];
				}

				$hash = md5('option_hash_'.$id_opt);
				if(!isset($_REQUEST['option_hash_'.$id_opt]) || $hash != $_REQUEST['option_hash_'.$id_opt])
				{
					$messages = $spt_messages['err_add'];
					$error = true;
					continue;
				}

//				если есть кука и в настройках стоит ограничение
				$poll_hash = md5('poll_hash_'.$result[1]);
				if(isset($_COOKIE['poll_'.$poll_hash]) && $cookie_time != 0)
				{
					$messages = $spt_messages['err_voted'];
					$error = true;
					continue;
				}

				$res = sql_param_query('SELECT COUNT(DISTINCT(r.spr_date)) FROM sb_polls_results r, sb_polls_options op
							WHERE r.spr_option_id = op.spo_id  AND op.spo_poll_id = ?d AND r.spr_ip = ?', $result[1], $ip);

//              если голосовали с текущего ip за текущий опрос и если это кол-во превышает допустимое значение
				if ($res && $res[0][0] >= $ip_count && $ip_count > 0)
				{
					$messages = $spt_messages['err_voted'];
					$error = true;
					continue;
				}

				$add = (isset($_REQUEST['v'][$result[0].'_'.$result[1].'_'.$result[2]]) && $_REQUEST['v'][$result[0].'_'.$result[1].'_'.$result[2]] != '');
				if($add)
				{
					$row[$result[2]]['spr_option_id'] = $id_opt;
					$row[$result[2]]['spr_date'] = $now;
					$row[$result[2]]['spr_ip'] = $ip;
					$row[$result[2]]['spr_text'] = ($result[0] == 'text' ? $_REQUEST['v'][$result[0].'_'.$result[1].'_'.$result[2]] : '');

					$poll_hash = md5('poll_hash_'.$result[1]);
					sb_setcookie('poll_'.$poll_hash, 1, time() + $cookie_time * 24 * 60 * 60);
				}
			}

			if(count($row) > 0)
			{
				foreach($row as $key => $value)
				{
					$res = sql_param_query('INSERT INTO sb_polls_results SET ?a', $value);
					$v_id = sql_insert_id();

					if(!$v_id)
						$error = true;
				}

				if($error && $messages == '')
					$messages = $spt_messages['err_add'];
				elseif(!$error && $messages == '')
					$messages = $spt_messages['success_add'];
			}
		}
	}

    // проверяем, есть ли закрытые разделы среди тех, которые надо выводить
    if ($closed_cats)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($closed_cats as $value)
        {
			$closed_ids[] = $value[0];
		}

		$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_polls_read');
	}

	if (count($cat_ids) == 0)
	{
		// указанные разделы были удалены
		echo $spt_messages['no_polls'];
		return;
    }

	//	вытаскиваем макет дизайна постраничного вывода
	$res = sbQueryCache::getTemplate('sb_pager_temps', $spt_pagelist_id);

	if ($res)
	{
		list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
	}
	else
	{
		$pt_page_list = '';
		$pt_perstage = 1;
	}

	// вытаскиваем пользовательские поля опроса и раздела
	$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_polls"');

    $elems_fields = array();
    $categs_fields = array();

    $categs_sql_fields = array();
    $elems_fields_select_sql = '';

    $elems_tags = array();
	$elems_values = array();

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

	            	if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
	                {
	                	$elems_tags[] = '{'.$value['tag'].'_LATITUDE}';
	                	$elems_tags[] = '{'.$value['tag'].'_LONGTITUDE}';

	                	if ($value['type'] == 'yandex_coords')
	                	{
	                		$tags[] = '{'.$value['tag'].'_API_KEY}';
	                	}
	                }
	                else
	                {
	                	$elems_tags[] = '{'.$value['tag'].'}';
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
	                $categs_sql_fields[] = 'user_f_'.$value['id'];

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

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';
    if ($spt_checked != '')
    {
        $spt_checked = explode(' ', $spt_checked);
        foreach($spt_checked as $value)
        {
            $elems_fields_where_sql .= ' AND p.user_f_'.$value.'=1';
        }
    }

    $now = time();
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
    if ($spt_cat_top != '' || $spt_cat_bottom != '')
    {
		$categs_output = true;
	}
	else
	{
		$categs_output = false;
    }

	@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

    $pager = new sbDBPager($tag_id, $pt_perstage, $spt_perpage);
    if (isset($params['filter']) && $params['filter'] == 'from_to')
    {
        $pager->mFrom = intval($params['filter_from']);
        $pager->mTo = intval($params['filter_to']);
    }

	//	выборка опросов, которые следует выводить
	$polls_total = true;
	$linked_sql = '';

	if($linked != 0)
	{
		$linked_sql = ' AND p.sp_id IN ('.$linked.') ';
	}

	if($categs_output)
    {
    	$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', p.sp_id');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' p.sp_id');
	}

    $group_str = '';
    $group_res = sql_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
    if ($group_res && $group_res[0][0] > 0)
    {
		$group_str = ' GROUP BY p.sp_id';
	}
	$res = $pager->init($polls_total, 'SELECT l.link_cat_id, p.sp_id, p.sp_url, p.sp_question
				'.$elems_fields_select_sql.'
			FROM sb_polls p, sb_catlinks l
				'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').'
			WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ' c.cat_id IN (?a) AND c.cat_id=l.link_cat_id ' : ' l.link_cat_id IN (?a) ').' AND l.link_el_id=p.sp_id
				'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik = 1 ' : '').'
				'.$elems_fields_where_sql.'
				AND p.sp_active IN ('.sb_get_workflow_demo_statuses().')
				AND (p.sp_pub_start IS NULL OR p.sp_pub_start <= '.$now.')
				AND (p.sp_pub_end IS NULL OR p.sp_pub_end >= '.$now.')
				'.$linked_sql.' '.
				$group_str.' '.
				($categs_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql), $cat_ids);

	if (!$res)
	{
		echo $spt_messages['no_polls'];
		return;
	}

	$categs = array();
	if (sb_substr_count($spt_top, '{VOTES_COUNT}') > 0 || sb_substr_count($spt_bottom, '{VOTES_COUNT}') > 0 ||
		sb_substr_count($spt_cat_top, '{CAT_COUNT}') > 0 || sb_substr_count($spt_cat_top, '{CAT_COUNT_VOTES}') > 0 ||
		sb_substr_count($spt_cat_bottom, '{CAT_COUNT}') > 0 || sb_substr_count($spt_cat_bottom, '{CAT_COUNT_VOTES}') > 0 ||
		sb_substr_count($spt_element, '{CAT_COUNT}') > 0 || sb_substr_count($spt_element, '{CAT_COUNT_VOTES}') > 0
       )
	{
		$res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
                (

                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_polls p
                WHERE c.cat_id = c1.cat_id
				AND l.link_el_id=p.sp_id
				'.$elems_fields_where_sql.'
				AND p.sp_active IN ('.sb_get_workflow_demo_statuses().')
                AND (p.sp_pub_start IS NULL OR p.sp_pub_start <= '.$now.')
                AND (p.sp_pub_end IS NULL OR p.sp_pub_end >= '.$now.')
                AND l.link_src_cat_id NOT IN (?a)

				) AS cat_count,

				(

				SELECT COUNT(r.spr_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id LEFT JOIN sb_polls p ON (l.link_el_id=p.sp_id)
					LEFT JOIN  sb_polls_options op ON (p.sp_id=op.spo_poll_id) LEFT JOIN sb_polls_results r ON (op.spo_id=r.spr_option_id)
				WHERE c.cat_id = c1.cat_id
				AND p.sp_active IN ('.sb_get_workflow_demo_statuses().')
				AND (p.sp_pub_start IS NULL OR p.sp_pub_start <= '.$now.')
				AND (p.sp_pub_end IS NULL OR p.sp_pub_end >= '.$now.')
				AND l.link_src_cat_id NOT IN (?a)

				) AS votes_count

				FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids, $cat_ids);
	}
	else
	{
		$res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, "" AS votes_count
				FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
	}

	$votes_count = 0;
	if ($res_cat)
    {
		foreach($res_cat as $value)
		{
            $categs[$value[0]] = array();
			$categs[$value[0]]['title'] = $value[1];
            $categs[$value[0]]['level'] = $value[2] + 1;
			$categs[$value[0]]['fields'] = (trim($value[3]) != '' ? unserialize($value[3]) : array());
            $categs[$value[0]]['url'] = urlencode($value[4]);
			$categs[$value[0]]['count'] = isset($value[5]) && !is_null($value[5]) ? $value[5] : 0;
            $categs[$value[0]]['votes_count'] = isset($value[6]) && !is_null($value[6]) ? $value[6] : 0;

			$votes_count += $value[6]; // $votes_count общее кол-во голосов
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

	$tags = array_merge($tags, array('{FIELD_VARIANT}',
									 '{TEXT_VARIANT}',
									 '{COUNT_VOTES}',
									 '{ALL_COUNT}',
									 '{CAT_TITLE}',
									 '{CAT_LEVEL}',
									 '{CAT_ID}',
									 '{CAT_URL}',
									 '{CAT_COUNT_VOTES}',
									 '{CAT_COUNT}'));

	$cur_cat_id = 0;
	$values = array();
	$num_fields = count($res[0]);
	$num_cat_fields = count($categs_sql_fields);
	$col = 0;

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$dop_tags = array('{ID}', '{ELEM_URL}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}', '{LINK}');

	$result_page = $more_ext = '';
	if(isset($params['page']))
		list($result_page, $more_ext) = sbGetMorePage($params['page']);

	if(sb_substr_count($spt_element, '{COUNT_VOTES}') > 0)
	{
		$res_options = sql_param_query('SELECT op.spo_poll_id, op.spo_name, op.spo_type, op.spo_id,
						(
							SELECT COUNT(*) FROM sb_polls_results r WHERE op.spo_id = r.spr_option_id
						) as count_votes
						FROM sb_polls_options op ORDER BY op.spo_order');
	}
	else
	{
		$res_options = sql_param_query('SELECT spo_poll_id, spo_name, spo_type, spo_id, "" AS count_votes FROM sb_polls_options ORDER BY spo_order');
	}

	$all_count = 0;
	$result = '';
	foreach ($res as $value)
	{
		$value[2] = urlencode($value[2]);

		$old_values = $values;
		$values = array();

		if ($value[0] != $cur_cat_id)
		{
			$cat_values = array();
		}

        if (trim($value[2]) == '' || $result_page == '')
		{
			$href = 'javascript: void(0);';
		}
        else
        {
            $href = $result_page;
            if (sbPlugins::getSetting('sb_static_urls') == 1)
            {
                // ЧПУ
                $href .= ($categs[$value[0]]['url'] != '' ? $categs[$value[0]]['url'].'/' : $value[0].'/').
                         ($value[2] != '' ? $value[2] : $value[1]).($more_ext != 'php' ? '.'.$more_ext : '/');
            }
            else
            {
				$href .= '?polls_cid='.$value[0].'&polls_id='.$value[1];
            }
        }

        $dop_values = array($value[1], $value[2], $value[0], $categs[$value[0]]['url'], $categs[$value[0]]['title'], $href);
        if ($num_fields > 4)
        {
            for ($i = 4; $i < $num_fields; $i++)
            {
				$values[] = $value[$i];
			}
            $spt_data = $spt_polls_top.$spt_element.$spt_polls_bottom;
			$elems_values = sbLayout::parsePluginFields($elems_fields, $values, $spt_fields_temps, $dop_tags, $dop_values, $spt_lang, '', '', 0, $link_level, $spt_data);
			$values = $elems_values;
        }

		if ($num_cat_fields > 0)
		{
			if (count($cat_values) == 0)
			{
				foreach ($categs_sql_fields as $cat_field)
				{
					if (isset($categs[$value[0]]['fields'][$cat_field]))
						$cat_values[] = $categs[$value[0]]['fields'][$cat_field];
					else
						$cat_values[] = null;
				}
				$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $spt_categs_temps, $dop_tags, $dop_values, $spt_lang, '', '', 0, $link_level, $spt_element,  $spt_polls_top.$spt_element.$spt_polls_bottom);
			}
			$values = array_merge($values, $cat_values);
		}

		$variants = '';
		$votes_question = 0; // кол-во голосов отданных за опрос

		if($res_options)
		{
			foreach($res_options as $ke => $val)
			{
				if($val[0] != $value[1])
				{
					continue;
				}
				$votes_question += $val[4];
			}

			$group_end = $group_start = false; // флаг. открыто начало группы или нет.
			$radio_group = $group_str = '';
			$length = count($values);

			foreach($res_options as $ke => $val)
			{
				if($val[0] != $value[1])
				{
					continue;
				}

				$values = array_slice($values, 0, $length);

				$hash = md5('option_hash_'.$val[3]);
				$fileds_tags = array_merge(array('{POLLS_INPUT_VALUE}', '{POLLS_INPUT_NAME}', '{NAME_HASH}', '{HASH}'), $dop_tags);
				switch($val[2])
				{
					case 'checkbox':
						$values[] = str_replace($fileds_tags,
						array_merge(array($val[3], 'v[checkbox_'.$val[0].'_'.$val[3].']', 'option_hash_'.$val[3], $hash), $dop_values), $spt_fields_temps['sp_checkbox']); //  FIELD_VARIANT
						break;
					case 'radio':
						$values[] = str_replace($fileds_tags,
						array_merge(array($val[3], 'v[radio_'.$val[0].'_'.$val[0].$radio_group.']', 'option_hash_'.$val[3], $hash), $dop_values), $spt_fields_temps['sp_radio']); //  FIELD_VARIANT
						break;
					case 'text':
						$values[] = str_replace($fileds_tags,
								array_merge(array('', 'v[text_'.$val[0].'_'.$val[3].']', 'option_hash_'.$val[3], $hash), $dop_values), $spt_fields_temps['sp_text']);  //  FIELD_VARIANT
						break;
					case 'group':
						if(!$group_start)
						{
							$group_str = str_replace(array_merge(array('{NAME}'), $dop_tags),
								array_merge(array($val[1]), $dop_values), $spt_fields_temps['sp_group_start']);
							$group_start = true;
							$radio_group .= '1';
						}
						elseif($group_start)
						{
							$group_str = str_replace(array_merge(array('{NAME}'), $dop_tags),
								array_merge(array($old_name), $dop_values), $spt_fields_temps['sp_group_end']);
							$group_end = true;
						}
						$old_name = $val[1];
						break;
				}

				$values[] = $val[1]; 							//  TEXT_VARIANT
				$values[] = $val[4];							//  COUNT_VOTES
				$values[] = $votes_question;					//  ALL_COUNT
				$values[] = $categs[$value[0]]['title'];		//  CAT_TITLE
				$values[] = $categs[$value[0]]['level'];		//  CAT_LEVEL
				$values[] = $value[0];							//  CAT_ID
				$values[] = $categs[$value[0]]['url'];			//  CAT_URL
				$values[] = $categs[$value[0]]['votes_count'];	//  CAT_COUNT_VOTES
				$values[] = $categs[$value[0]]['count'];		//  CAT_COUNT

				if($val[2] == 'group')
				{
					$variants .= $group_str;
					if($group_start && $group_end)
					{
						$radio_group .= '1';
					}
				}
				else
				{
					if($group_start && $group_end)
					{
//						$radio_group .= '1';
						$group_end = false;
						$variants .= str_replace(array_merge(array('{NAME}'), $dop_tags),
							array_merge(array($old_name), $dop_values), $spt_fields_temps['sp_group_start']);
					}
					$variants .= str_replace($tags, $values, $spt_element);
				}
			}

			if($group_str != '' && !$group_end)
			{
				$variants .= str_replace(array_merge(array('{NAME}'), $dop_tags),
						array_merge(array($old_name), $dop_values), $spt_fields_temps['sp_group_end']);
			}
		}

		$all_count += $votes_question;
		// Дата последнего изменения
		if((sb_strpos($spt_polls_top, '{CHANGE_DATE}') !== false) || (sb_strpos($spt_polls_bottom, '{CHANGE_DATE}') !== false))
	    {
	        $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_polls');
	        $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $spt_fields_temps['sp_change_date'], $spt_lang) : ''; //   CHANGE_DATE
	    }
	    else
	    {
	        $change_date = '';
	    }

		$polls_top = str_replace(array_merge(array('{QUESTION}', '{COUNT_VOTES}', '{LINK}', '{CHANGE_DATE}'), $elems_tags),
					array_merge(array($value[3], $votes_question, $href, $change_date), $elems_values), $spt_polls_top);

		$polls_bottom = str_replace(array_merge(array('{QUESTION}', '{COUNT_VOTES}', '{LINK}', '{CHANGE_DATE}'), $elems_tags),
					array_merge(array($value[3], $votes_question, $href, $change_date), $elems_values), $spt_polls_bottom);

		$element = $polls_top.$variants.$polls_bottom;

		if ($categs_output && $value[0] != $cur_cat_id)
		{
			if ($cur_cat_id != 0)
			{
                // низ вывода раздела
                while ($col < $spt_count)
                {
                    $result .= $spt_empty;
                    $col++;
				}
				$result .= str_replace($tags, $old_values, $spt_cat_bottom);
            }
			// верх вывода раздела
			$result .= str_replace($tags, $values, $spt_cat_top);
			$col = 0;
		}

		if ($col >= $spt_count)
		{
			$result .= $spt_delim;
			$col = 0;
        }

        $result .= $element;
        $cur_cat_id = $value[0];
        $col++;
    }

	while ($col < $spt_count)
	{
		$result .= $spt_empty;
		$col++;
	}

	if ($categs_output)
    {
        // низ вывода раздела
        $result .= str_replace($tags, $values, $spt_cat_bottom);
    }

	// подключаем верх и низ вывода списка опросов
	$result = str_replace(array('{MESSAGES}', '{VOTES_COUNT}', '{POLLS_COUNT}', '{NUM_LIST}', '{ACTION}'),
		array($messages, $all_count, $polls_total, $pt_page_list, $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '')), $spt_top).
	$result.str_replace(array('{MESSAGES}', '{VOTES_COUNT}', '{POLLS_COUNT}', '{NUM_LIST}', '{ACTION}'),
		array($messages, $all_count, $polls_total, $pt_page_list, $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '')), $spt_bottom);

	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	if($linked == 0)
	{
		//чистим код от инъекций
        $result = sb_clean_string($result);

		eval(' ?>'.$result.'<?php ');
	}
	else
	{
		return $result;
	}
}


function fPolls_Elem_Results($el_id, $temp_id, $params, $tag_id)
{
//	if ($GLOBALS['sbCache']->check('pl_polls', $tag_id, array($el_id, $temp_id, $params)))
//		return;

	$params = unserialize(stripslashes($params));
	$cat_ids = array();

	if ((isset($params['polls_list']) && $params['polls_list'] == 1 ||
		isset($params['rubrikator']) && $params['rubrikator'] == 1) && (isset($_GET['polls_scid']) || isset($_GET['polls_cid'])))
	{
		//	используется связь с выводом разделов и выводить следует опросы из соотв. раздела
		if (isset($_GET['polls_cid']))
		{
			$cat_ids[] = intval($_GET['polls_cid']);
		}
		else
		{
			$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_polls"', $_GET['polls_scid']);
			if ($res)
            {
				$cat_ids[] = $res[0][0];
			}
			else
            {
				$cat_ids[] = intval($_GET['polls_scid']);
            }
        }
    }
    else
    {
		$cat_ids = explode('^', $params['ids']);
	}

	//	если следует выводить подразделы, то вытаскиваем их ID
	if (isset($params['subcategs']) && $params['subcategs'] == 1)
	{
		$res = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_polls"
							AND c2.cat_ident = "pl_polls"
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
//        	$GLOBALS['sbCache']->save('pl_polls', '');
            return;
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
		$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_polls_read');
	}

	if (count($cat_ids) == 0)
	{
		// указанные разделы были удалены
//		$GLOBALS['sbCache']->save('pl_polls', '');
		return;
	}

	// вытаскиваем макет дизайна
	//$res = sql_param_query('SELECT sptr_title, sptr_lang, sptr_perpage, sptr_pagelist_id, sptr_count, sptr_checked, sptr_top,
	//			sptr_categs_top, sptr_result_top, sptr_element, sptr_result_bottom, sptr_categs_bottom, sptr_bottom, sptr_empty,
	//			sptr_delim, sptr_fields_temps, sptr_categs_temps, sptr_no_results FROM sb_polls_temps_results WHERE sptr_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_polls_temps_results', $temp_id);

	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
//		$GLOBALS['sbCache']->save('pl_polls', '');
		return;
	}

	list($sptr_title, $sptr_lang, $sptr_perpage, $sptr_pagelist_id, $sptr_count, $sptr_checked, $sptr_top,
				$sptr_categs_top, $sptr_result_top, $sptr_element, $sptr_result_bottom, $sptr_categs_bottom, $sptr_bottom, $sptr_empty,
				$sptr_delim, $sptr_fields_temps, $sptr_categs_temps, $sptr_no_results) = $res[0];

	$sptr_fields_temps = unserialize($sptr_fields_temps);
	$sptr_categs_temps = unserialize($sptr_categs_temps);

	// вытаскиваем макет дизайна постраничного вывода
	$res = sbQueryCache::getTemplate('sb_pager_temps', $sptr_pagelist_id);

    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

	// вытаскиваем пользовательские поля опроса и раздела
	//$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_polls"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_polls');

    $elems_fields = array();
    $categs_fields = array();

    $categs_sql_fields = array();
    $elems_fields_select_sql = '';

	$elems_tags = array();
	$elems_values = array();

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

	            	if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
	                {
	                	$elems_tags[] = '{'.$value['tag'].'_LATITUDE}';
	                	$elems_tags[] = '{'.$value['tag'].'_LONGTITUDE}';

	                	if ($value['type'] == 'yandex_coords')
	                	{
	                		$tags[] = '{'.$value['tag'].'_API_KEY}';
	                	}
	                }
	                else
	                {
	                	$elems_tags[] = '{'.$value['tag'].'}';
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
	                $categs_sql_fields[] = 'user_f_'.$value['id'];
	                $tags[] = '{'.$value['tag'].'}';
	            }
	        }
        }
    }

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';
    if ($sptr_checked != '')
    {
        $sptr_checked = explode(' ', $sptr_checked);
        foreach ($sptr_checked as $value)
        {
            $elems_fields_where_sql .= ' AND r.user_f_'.$value.'=1';
        }
    }

    $now = time();
    // формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
    if (isset($params['sort1']) && $params['sort1'] != '')
    {
    	$elems_fields_sort_sql .=  ', '.$params['sort1'];

    	if ($params['sort1'] == 'RAND()')
    	{
//    		$GLOBALS['sbCache']->mCacheOff = true;
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
//    		$GLOBALS['sbCache']->mCacheOff = true;
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
//    		$GLOBALS['sbCache']->mCacheOff = true;
    	}

        if (isset($params['order3']) && $params['order3'] != '')
        {
            $elems_fields_sort_sql .= ' '.$params['order3'];
        }
    }

	// используется ли группировка по разделам
	if ($sptr_categs_top != '' || $sptr_categs_bottom != '')
	{
		$categs_output = true;
	}
	else
	{
		$categs_output = false;
    }

    @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

    $pager = new sbDBPager($tag_id, $pt_perstage, $sptr_perpage);
    if ($params['filter'] == 'from_to')
    {
        $pager->mFrom = intval($params['filter_from']);
        $pager->mTo = intval($params['filter_to']);
	}

//	если нужно устанавливать связь с выводом опросов
	if(isset($params['polls_list']) && $params['polls_list'] == 1)
	{
		if(isset($_GET['polls_id']) && $_GET['polls_id'] != '')
		{
			$elems_fields_where_sql .= 'AND p.sp_id = '.intval($_GET['polls_id']);
		}
		elseif(isset($_GET['polls_sid']) && $_GET['polls_sid'] != '')
		{
			if(!preg_match('/^[0-9]+$/', $_GET['polls_sid']))
			{
				$elems_fields_where_sql .= 'AND p.sp_url = '.$GLOBALS['sbSql']->escape($_GET['polls_sid']);
			}
			else
			{
				$elems_fields_where_sql .= 'AND p.sp_id = '.intval($_GET['polls_sid']);
			}
		}
	}

	if($categs_output)
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', p.sp_id');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' p.sp_id');
    }

	//	выборка результатов, которые следует выводить
	$polls_total = true;
	$res = $pager->init($polls_total, 'SELECT l.link_cat_id, r.spr_id, r.spr_text, p.sp_id, p.sp_question, p.sp_url
				'.$elems_fields_select_sql.'
			FROM sb_polls p
				LEFT JOIN sb_polls_options op ON op.spo_poll_id=p.sp_id
				LEFT JOIN sb_polls_results r ON r.spr_option_id = op.spo_id, sb_catlinks l

				'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ' , sb_categs c ' : '').'
			WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ' c.cat_id IN (?a) AND c.cat_id=l.link_cat_id ' : ' l.link_cat_id IN (?a) ').' AND l.link_el_id=p.sp_id
				'.(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1 ' : '').'

				'.$elems_fields_where_sql.'
                AND p.sp_active IN ('.sb_get_workflow_demo_statuses().')
				AND (p.sp_pub_start IS NULL OR p.sp_pub_start <= '.$now.')
				AND (p.sp_pub_end IS NULL OR p.sp_pub_end >= '.$now.')
				GROUP BY p.sp_id '.

				($categs_output ? 'ORDER BY c.cat_left '.$elems_fields_sort_sql : 'ORDER BY '.$elems_fields_sort_sql), $cat_ids);

	if (!$res)
	{
		echo $sptr_no_results;
//		$GLOBALS['sbCache']->save('pl_polls', $sptr_no_results);
		return;
	}

	$count_result = $pager->mFrom + 1;
	$categs = array();
	if (sb_substr_count($sptr_categs_top, '{CAT_COUNT_VOTES}') > 0 ||
		sb_substr_count($sptr_categs_bottom, '{CAT_COUNT_VOTES}') > 0 ||
		sb_substr_count($sptr_element, '{CAT_COUNT_VOTES}') > 0
       )
	{
		$res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
				(

				SELECT COUNT(r.spr_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id LEFT JOIN sb_polls p ON (l.link_el_id=p.sp_id)
					LEFT JOIN  sb_polls_options op ON (p.sp_id=op.spo_poll_id) LEFT JOIN sb_polls_results r ON (op.spo_id=r.spr_option_id)
				WHERE c.cat_id = c1.cat_id
                AND p.sp_active IN ('.sb_get_workflow_demo_statuses().')
				AND (p.sp_pub_start IS NULL OR p.sp_pub_start <= '.$now.')
				AND (p.sp_pub_end IS NULL OR p.sp_pub_end >= '.$now.')
				AND l.link_src_cat_id NOT IN (?a)

				) AS votes_count

				FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids);
	}
	else
	{
		$res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS votes_count
				FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
	}

	$votes_count = 0;
	if ($res_cat)
	{
		foreach ($res_cat as $value)
		{
			$categs[$value[0]] = array();
			$categs[$value[0]]['title'] = $value[1];
			$categs[$value[0]]['level'] = $value[2] + 1;
            $categs[$value[0]]['fields'] = (trim($value[3]) != '' ? unserialize($value[3]) : array());
			$categs[$value[0]]['url'] = $value[4];
            $categs[$value[0]]['votes_count'] = $value[5];

			$votes_count += $value[5];   //   $votes_count общее кол-во голосов
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

	// верх вывода списка опросов
	$tags = array_merge($tags, array('{ID_QUESTION}',
									'{ID}',
									'{TEXT_VARIANT}',
									'{COUNT_VOTES}',
									'{ALL_COUNT}',
									'{CAT_TITLE}',
									'{CAT_COUNT_VOTES}',
									'{CAT_LEVEL}',
									'{CAT_ID}',
									'{CAT_URL}'));

	$dop_tags = array('{ID}', '{ELEM_URL}', '{CAT_ID}', '{CAT_URL}', '{CAT_TITLE}');

	$cur_cat_id = 0;
	$values = array();
	$num_fields = count($res[0]);
	$num_cat_fields = count($categs_sql_fields);
	$col = 0;

	if(sb_substr_count($sptr_element, '{COUNT_VOTES}') > 0)
	{
		$res_options = sql_param_query('SELECT op1.spo_poll_id, op1.spo_name, op1.spo_id,
					(

						SELECT COUNT(r.spr_id) FROM sb_polls_results r, sb_polls_options op WHERE op.spo_id=spr_option_id
						AND op.spo_id = op1.spo_id

					) as count_votes, spo_type
					FROM sb_polls_options op1 ORDER BY op1.spo_order');
	}
	else
	{
		$res_options = sql_param_query('SELECT spo_poll_id, spo_name, spo_id, "" AS count_votes, spo_type FROM sb_polls_options ORDER BY spo_order');
	}
	$result = '';
	$all_count = 0;
	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	foreach ($res as $value)
	{
		$old_values = $values;
		$el_values = $values = array();

		if ($value[0] != $cur_cat_id)
		{
			$cat_values = array();
        }

		if ($num_fields > 6)
        {
			for ($i = 6; $i < $num_fields; $i++)
            {
				$el_values[] = $value[$i];
			}

			$elems_values = sbLayout::parsePluginFields($elems_fields, $el_values, $sptr_fields_temps, array(), array(), $sptr_lang);
			$values = array_merge($values, $elems_values);
		}

		if ($num_cat_fields > 0)
		{
			if (count($cat_values) == 0)
			{
				foreach ($categs_sql_fields as $cat_field)
				{
					if (isset($categs[$value[0]]['fields'][$cat_field]))
						$cat_values[] = $categs[$value[0]]['fields'][$cat_field];
					else
						$cat_values[] = null;
				}

				$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $sptr_categs_temps, array(), array(), $sptr_lang);
			}
			$values = array_merge($values, $cat_values);
		}

		$dop_values = array($value[3], (isset($value[5]) ? $value[5] : ''), $value[0], $categs[$value[0]]['url'], $categs[$value[0]]['title']);

		$variants = '';
		$votes_question = ''; // кол-во голосов отданных за опрос

		$group_end = $group_start = false; // флаг. открыто начало группы или нет.
		$old_name = $group_str = '';

		$length = count($values);
		if($res_options)
		{
			foreach($res_options as $ke => $val)
			{
				if($val[0] == $value[3])
				{
					$votes_question += $val[3];
				}
			}

			foreach($res_options as $ke => $val)
			{
				if ($val[0] != $value[3])
					continue;

				if($val[4] == 'group')
				{
					if(!$group_start)
					{
						$group_str = str_replace(array_merge(array('{NAME}'), $dop_tags), array_merge(array($val[1]), $dop_values), $sptr_fields_temps['sp_group_start']);
						$group_start = true;
					}
					elseif($group_start)
					{
						$group_str = str_replace(array_merge(array('{NAME}'), $dop_tags), array_merge(array($old_name), $dop_values), $sptr_fields_temps['sp_group_end']);
						$group_end = true;
					}
					$old_name = $val[1];
				}

				$values = array_slice($values, 0, $length);

				$values[] = $value[3]; 							//  ID_QUESTION
				$values[] = $val[2]; 							//  ID
				$values[] = $val[1]; 							//  TEXT_RESULT
				$values[] = $val[3];							//  COUNT_VOTES
				$values[] = $votes_question;					//  ALL_COUNT
				$values[] = $categs[$value[0]]['title'];		//  CAT_TITLE
				$values[] = $categs[$value[0]]['votes_count'];	//  CAT_COUNT_VOTES
				$values[] = $categs[$value[0]]['level'];		//  CAT_LEVEL
				$values[] = $value[0];							//  CAT_ID
				$values[] = $categs[$value[0]]['url'];			//  CAT_URL

				if($val[4] == 'group')
				{
					$variants .= $group_str;
				}
				else
				{
					if($group_start && $group_end)
					{
						$variants .= str_replace(array_merge(array('{NAME}'), $dop_tags),
							array_merge(array($old_name), $dop_values), $sptr_fields_temps['sp_group_start']);
						$group_end = false;
					}

					$variants .= str_replace($tags, $values, $sptr_element);
				}
			}

			if($group_str != '' && !$group_end)
			{
				$variants .= str_replace(array_merge(array('{NAME}'), $dop_tags),
						array_merge(array($old_name), $dop_values), $sptr_fields_temps['sp_group_end']);
			}
		}

		$all_count += $votes_question;
		// Дата последнего изменения
		if((sb_strpos($sptr_result_top, '{CHANGE_DATE}') !== false) || (sb_strpos($sptr_result_bottom, '{CHANGE_DATE}') !== false))
	    {
	        $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[3],'pl_polls');
	        $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sptr_fields_temps['sp_change_date'], $sptr_lang) : ''; //   CHANGE_DATE
	    }
	    else
	    {
	        $change_date = '';
	    }
		$result_top = str_replace(array_merge(array('{ELEM_NUMBER}', '{ID}', '{QUESTION}', '{COUNT_VOTES}', '{CHANGE_DATE}'), $elems_tags),
				array_merge(array($count_result, $value[3], $value[4], $votes_question, $change_date), $elems_values), $sptr_result_top);

		$result_bottom = str_replace(array_merge(array('{ELEM_NUMBER}', '{ID}', '{QUESTION}', '{COUNT_VOTES}', '{CHANGE_DATE}'), $elems_tags),
				array_merge(array($count_result, $value[3], $value[4], $votes_question, $change_date), $elems_values), $sptr_result_bottom);

		$count_result++;

		$element = $result_top.$variants.$result_bottom;
		if ($categs_output && $value[0] != $cur_cat_id)
		{
			if ($cur_cat_id != 0)
			{
                // низ вывода раздела
                while ($col < $sptr_count)
                {
                    $result .= $sptr_empty;
                    $col++;
                }
				$result .= str_replace($tags, $old_values, $sptr_categs_bottom);
            }

			// верх вывода раздела
			$result .= str_replace($tags, $values, $sptr_categs_top);
			$col = 0;
		}

		if ($col >= $sptr_count)
		{
			$result .= $sptr_delim;
			$col = 0;
		}

		$result .= $element;
		$cur_cat_id = $value[0];
		$col++;
	}

	while ($col < $sptr_count)
	{
		$result .= $sptr_empty;
		$col++;
	}

	if ($categs_output)
	{
		// низ вывода раздела
		$result .= str_replace($tags, $values, $sptr_categs_bottom);
	}

	$result = str_replace(array('{NUM_LIST}', '{ALL_VOTES_COUNT}'), array($pt_page_list, $all_count), $sptr_top).$result.
			str_replace(array('{NUM_LIST}', '{ALL_VOTES_COUNT}'), array($pt_page_list, $all_count), $sptr_bottom);

	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	//чистим код от инъекций
    $result = sb_clean_string($result);

	eval (' ?>'.$result.'<?php ');

//	$GLOBALS['sbCache']->save('pl_polls', $result);
}


function fPolls_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
	require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
	$num_sub = 0;
	fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_polls', 'pl_polls', 'polls', $num_sub);
}


function fPolls_Elem_Sel_Cat($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_polls', 'pl_polls', 'polls');
}

?>