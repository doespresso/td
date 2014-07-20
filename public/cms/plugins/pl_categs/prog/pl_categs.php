<?php
function fCategs_Show_Menu_Categs($temp_id, $params, $plugin_ident, $cat_ident, $get_ident, &$num_sub, $level, $show_sel)
{
    return fCategs_Show_Categs($temp_id, $params, '0', $plugin_ident, $cat_ident, $get_ident, $num_sub, $level, $show_sel);
}

function fCategs_Show_Categs($temp_id, $params, $tag_id, $plugin_ident, $cat_ident, $get_ident, &$num_sub, $level=-1, $show_sel=true)
{
	$cat_ident = preg_replace('/[^a-zA-Z0-9\-_]+/', '', $cat_ident);
    // если есть кэш, то выводим
    if ($level == -1 && $GLOBALS['sbCache']->check($plugin_ident, $tag_id, array(0, $temp_id, $params)))
        return '';

    if ($level == -1)
    {
        $params = unserialize(stripslashes($params));
    }

    if (!isset($params['query_string']))
    	$params['query_string'] = 1;

    $cat_ids = explode('^', $params['ids']);
    $like_str = '';

    if ($get_ident == 'pl_pages')
    {
    	$filename = basename($_SERVER['PHP_SELF']);
	    $dirname = trim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/\\');

	    $res = sql_query('SELECT c.cat_id FROM sb_categs c, sb_pages p, sb_catlinks l
    							WHERE p.p_default=1 AND p.p_filepath=? AND p.p_filename=?
    							AND l.link_el_id=p.p_id AND l.link_cat_id=c.cat_id
    							AND c.cat_ident=? ORDER BY c.cat_left LIMIT 1', $dirname, $filename, 'pl_pages');
        if ($res)
        {
            $like_str = 'c.cat_id='.$res[0][0];
        }

        if ($level == -1)
        {
        	$params['menu_temp_id'] = $temp_id;
        	$temp_id = -1;
        }
    }
    elseif ($show_sel)
    {
	    // строим строку SQL-запроса для выбора выделенного раздела
	    if (isset($_GET[$get_ident.'_cid']))
	    {
	        $like_str = 'c.cat_id='.intval($_GET[$get_ident.'_cid']);
	    }
	    elseif (isset($_GET[$get_ident.'_scid']))
	    {
	        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET[$get_ident.'_scid'], $cat_ident);
	        if ($res)
	        {
	            $like_str = 'c.cat_id='.$res[0][0];
	        }
	        else
	        {
	            $like_str = 'c.cat_id='.intval($_GET[$get_ident.'_scid']);
	        }
	    }
    }

    // формируем строку запроса разделов
    $res = sql_query('SELECT cat_left, cat_right, cat_level FROM sb_categs WHERE cat_id IN (?a) ORDER BY cat_left DESC', $cat_ids);
    $cat_like_str = '';

    if ($res)
    {
        foreach ($res as $value)
        {
            list($left, $right, $cat_level) = $value;
            $cat_like_str .= 'c.cat_left >= '.$left.' AND c.cat_right <= '.$right.' OR ';

            if (!isset($params['from']) || $params['from'] == '')
                $params['from'] = $cat_level + 1;
        }

        $cat_like_str = '('.sb_substr($cat_like_str, 0, -4).')';

        if ($level > -1 && $like_str == '')
        {
        	$params['parent_link'] = 0;
        }
    }

    if ($cat_like_str == '')
    {
        if ($level == -1)
        {
            $GLOBALS['sbCache']->save($plugin_ident, '');
        }
        return '';
    }

    $params_cat_like_str = $cat_like_str.' AND ';
    $cat_like_str .= ' AND c.cat_ident=\''.$cat_ident.'\'';

    $sel_level = -1;
    $sel_left = -1;
    $sel_right = -1;
    if ($like_str == '' && isset($params['parent_link']) && $params['parent_link'] == 1)
    {
        // если выбранного раздела нет и используется связь с выводом разделов верхнего уровня, то ничего не выводим
        if ($level == -1)
        {
            $GLOBALS['sbCache']->save($plugin_ident, '');
        }
        return '';
    }
    elseif (isset($params['parent_link']) && $params['parent_link'] == 1)
    {
    	$res = sql_query('SELECT cat_left, cat_right, cat_level FROM sb_categs c WHERE '.$like_str.' AND '.$cat_like_str);
    	if ($res)
    	{
    		list($sel_left, $sel_right, $sel_level) = $res[0];

	    	$cat_like_str = $params_cat_like_str.'c.cat_left >= '.$sel_left.' AND c.cat_right <= '.$sel_right.' AND c.cat_ident=\''.$cat_ident.'\'';

	    	if (!isset($params['show_hidden']) || $params['show_hidden'] != 1)
	    	{
		        $res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE
		                            cat_left <= (SELECT c.cat_left FROM sb_categs c WHERE '.$like_str.') AND
		                            cat_right >= (SELECT c.cat_right FROM sb_categs c WHERE '.$like_str.') AND
		                            cat_rubrik = 0 AND cat_ident=?', $cat_ident);

		        if ($res && $res[0][0] > 0)
		        {
		            if ($level == -1)
		            {
		                $GLOBALS['sbCache']->save($plugin_ident, '');
		            }
		            return '';
		        }
	    	}
    	}
    	else
    	{
    		if ($level == -1)
	        {
	            $GLOBALS['sbCache']->save($plugin_ident, '');
	            return;
	        }

	        $params['parent_link'] = 0;
    	}
    }

    $ctl_checked = '';
    $ctl_perpage = 0;
    $ctl_pagelist_id = -1;

    if ($temp_id == -1)
    {
        // вытаскиваем макет дизайна меню
        $res = sbQueryCache::getTemplate('sb_menu_temps', $params['menu_temp_id']);
        if (!$res)
        {
            return '';
        }

        list($ctl_lang, $ctl_levels, $mt_fields_temps) = $res[0];

        $ctl_levels = unserialize($ctl_levels);
        if ($mt_fields_temps != '')
            $mt_fields_temps = unserialize($mt_fields_temps);
        else
            $mt_fields_temps = array();

        if ($level > 0)
        {
        	if (isset($ctl_levels[$level]))
        	{
            	$tmp = array();
            	$j = 0;
            	for ($i = $level; $i < count($ctl_levels); $i++)
            	{
            		$tmp[$j++] = $ctl_levels[$i];
            	}

            	$ctl_levels = $tmp;
        		unset($tmp);
        	}
        }

        for ($i = 0; $i < count($ctl_levels); $i++)
        {
        	$ctl_levels[$i]['sub'] = str_replace('{COUNT}', '{SUB_COUNT}', $ctl_levels[$i]['sub']);
        	$ctl_levels[$i]['sub_sel'] = str_replace('{COUNT}', '{SUB_COUNT}', $ctl_levels[$i]['sub_sel']);
        	$ctl_levels[$i]['item'] = str_replace('{COUNT}', '{SUB_COUNT}', $ctl_levels[$i]['item']);
        	$ctl_levels[$i]['item_sel'] = str_replace('{COUNT}', '{SUB_COUNT}', $ctl_levels[$i]['item_sel']);
        }

        $ctl_categs_temps = array();
        foreach($mt_fields_temps as $key => $value)
        {
            $ctl_categs_temps[$key] = array();
            $ctl_categs_temps[$key]['closed_icon'] = $value['closed_icon'];
        }

        unset($mt_fields_temps);
    }
    else
    {
        // вытаскиваем макет дизайна вывода разделов
        $res = sbQueryCache::getTemplate('sb_categs_temps_list', $temp_id);
        if (!$res)
        {
            sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CATEGS_PLUGIN), SB_MSG_WARNING);
            if ($level == -1)
            {
                $GLOBALS['sbCache']->save($plugin_ident, '');
            }
            return '';
        }

        list($ctl_lang, $ctl_levels, $ctl_categs_temps, $ctl_checked, $ctl_perpage, $ctl_pagelist_id) = $res[0];
        $ctl_levels = unserialize($ctl_levels);
        if ($ctl_categs_temps != '')
        {
            $ctl_categs_temps = unserialize($ctl_categs_temps);
        }
        else
        {
            $ctl_categs_temps = array();
        }
    }

    if (!$ctl_levels)
    {
        if ($level == -1)
        {
            $GLOBALS['sbCache']->save($plugin_ident, '');
        }
        return '';
    }

    // вытаскиваем пользовательские поля раздела
    $res = sbQueryCache::query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);

    $categs_fields = array();
    $tags = array();

    if ($ctl_checked != '')
    {
        $ctl_checked = explode(' ', $ctl_checked);
    }
    else
    {
        $ctl_checked = array();
    }

    $checked_sql = '';

    // формируем список тегов для пользовательских полей
    if ($res && $res[0][0] != '')
    {
        $categs_fields = unserialize($res[0][0]);
        $categs_fields = ($categs_fields == '' ? array() : $categs_fields);

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

                    if (in_array($value['id'], $ctl_checked))
                    {
                        $checked_sql .= ' AND c.cat_fields LIKE \'%:"user_f_'.$value['id'].'";s:1:"1";%\'';
                    }
                }
            }
        }
    }

	$max_level = 0;
    foreach ($ctl_levels as $value)
    {
        if (sb_strpos($value['sub'], '{SUB_ITEMS}') !== false || sb_strpos($value['sub_sel'], '{SUB_ITEMS}') !== false)
        {
            $max_level++;
        }
        else
        {
            break;
        }
    }

    if ($max_level == count($ctl_levels))
        $max_level = 1000000;

    if (!isset($params['from']) || $params['from'] == '')
    	$params['from'] = 1;

    if (!isset($params['to']))
    	$params['to'] = '';

    if (!isset($params['count']))
    	$params['count'] = 0;

    // определяем уровень разделов с которого и по который выводить
    $from = max(0, intval($params['from']) - 1);
    $count = intval($params['count']);

	if ($count > 0 && isset($params['parent_link']) && $params['parent_link'] == 1)
    {
   		$from = min(max($sel_level, $from - 1), $params['to'] != '' ? intval($params['to']) - 1 : 1000000);
    	$to = min($max_level + $from, $from + $count - 1, $params['to'] != '' ? intval($params['to']) - 1 : 1000000);
    }
    elseif (isset($params['parent_link']) && $params['parent_link'] == 1)
    {
	$to = $params['to'] != '' ? intval($params['to']) - 1 : 1000000;
    }
    else
    {
    	$to = min($max_level + $from, $params['to'] != '' ? intval($params['to']) - 1 : 1000000);
    }

    $closed_str = '(r.group_ids IS NULL OR r.group_ids = \'\'';
    if (isset($_SESSION['sbAuth']))
    {
        foreach ($_SESSION['sbAuth']->getUserGroups() as $g_id)
        {
            $closed_str .= ' OR r.group_ids LIKE \'%'.$g_id.'%\'';
        }
    }
    $closed_str .= ')';

    if (isset($params['parent_link']) && $params['parent_link'] == 1)
    {
        // если идет связь с разделом верхнего уровня
    	if ($count > 0)
        {
        	/*$res = sql_query('SELECT c.cat_left, c.cat_right FROM sb_categs c WHERE c.cat_ident=?
        	                 '.$checked_sql.' AND c.cat_left < ?d AND c.cat_right > ?d ORDER BY c.cat_left DESC LIMIT 1', $cat_ident, $sel_left, $sel_right);

        	if ($res)
        	{
        		list($first_left, $first_right) = $res[0];
        	}
        	else
        	{
        		$first_left = $sel_left;
        		$first_right = $sel_right;
        	}*/

        	// считаем кол-во уровней у выбранного подпункта
        	$res_sub = sql_query('SELECT c.cat_level FROM sb_categs c WHERE c.cat_ident=?
                    		 '.$checked_sql.' AND c.cat_left > ?d AND c.cat_right < ?d'.
        					 (!isset($params['show_hidden']) || $params['show_hidden'] == 0 ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c2 WHERE c2.cat_left <= c.cat_left AND c2.cat_right >= c.cat_right AND c2.cat_ident=\''.$cat_ident.'\' AND c2.cat_rubrik=0))' : '').
        					 (!isset($params['show_closed']) || $params['show_closed'] == 0 ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c2 LEFT JOIN sb_catrights r ON r.cat_id=c2.cat_id AND r.right_ident=\''.$plugin_ident.'_read\' WHERE c2.cat_left <= c.cat_left AND c2.cat_right >= c.cat_right AND c2.cat_ident=\''.$cat_ident.'\' AND NOT ('.$closed_str.')))' : '').'
        					  GROUP BY (c.cat_level)', $cat_ident, $sel_left, $sel_right);

			$max_root_level = $sel_level;
        	$max_sub_level = $sel_level;
        	if ($res_sub)
        	{
	        	foreach ($res_sub as $value)
	        	{
	        		if ($params['to'] != '' && $value[0] > intval($params['to']) - 1)
	        			break;

	        		$max_sub_level = $value[0];
	        	}
        	}

        	$max_sub_level = max(0, $max_sub_level - $sel_level);
		    $max_root_level = max(0, $max_root_level - $sel_level + $max_sub_level);

        	$sub_count = min($to - $from, $max_root_level);

        	if ($max_sub_level < $count)
        	{
        		if ($max_root_level >= $count)
        		{
        			$from = max(0, intval($params['from']) - 1, $from - 1);
        		}
        		else
        		{
        			$from = max(0, intval($params['from']) - 2, $from - ($count - $sub_count));
        		}

                        if ($params['to'] != '')
        		    $to = min($max_level + $from + 1, $from + $count, $params['to'] != '' ? intval($params['to']) - 1 : 1000000);
                        else
                            $to = min($max_level + $from, $from + $count, $params['to'] != '' ? intval($params['to']) - 1 : 1000000);
        	}
        	else
        	{
        		$to = min($params['to'] != '' ? intval($params['to']) - 1 : 1000000, $to + 1);
        	}

        	$res = sql_query('SELECT c.cat_left, c.cat_right FROM sb_categs c WHERE c.cat_ident=?
        					 '.$checked_sql.' AND c.cat_left <= ?d AND c.cat_right >= ?d AND c.cat_level = ?d'.(!isset($params['show_hidden']) || $params['show_hidden'] == 0 ? ' AND c.cat_rubrik = 1' : ''), $cat_ident, $sel_left, $sel_right, $from);

            if ($res)
            {
                list($sel_left, $sel_right) = $res[0];
                $cat_like_str = $params_cat_like_str.'c.cat_left >= '.$sel_left.' AND c.cat_right <= '.$sel_right.' AND c.cat_ident=\''.$cat_ident.'\'';
            }

            $from++;
        }
        else
        {
        	$from = max($from, $sel_level + 1);
        }
    }
    elseif ($count > 0 && ($to - $from + 1) > $count)
    {
        $to = min($to, $from + $count - 1);
    }

    if ($like_str != '')
    {
        if (isset($params['parent_selection']) && $params['parent_selection'] == 1)
        {
            // если надо сохранять выделение у разделов верхнего уровня
            $res = sql_query('SELECT c.cat_left, c.cat_right FROM sb_categs c WHERE '.$cat_like_str.'
                                    AND '.$like_str);

            if ($res)
            {
                list($left, $right) = $res[0];
                $like_str = '(c.cat_left <= '.$left.' AND c.cat_right >= '.$right.')';
            }
            else
            {
                $like_str = '';
            }
        }
    }

    if ($like_str == '')
        $like_str = 'NULL';

    $pt_page_list = '';
    $pt_perstage = 1;

    // вытаскиваем разделы
    if ($level == -1 && $ctl_perpage > 0)
    {
        // вытаскиваем макет дизайна постраничного вывода
        $res = sbQueryCache::getTemplate('sb_pager_temps', $ctl_pagelist_id);

        if ($res)
        {
            list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
        }

        @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
        $pager = new sbDBPager($tag_id, $pt_perstage, $ctl_perpage);

        $categs_total = true;

        $res = false;
        if (isset($params['show_closed']) && $params['show_closed'] == 1)
        {
            $res = $pager->init($categs_total, 'SELECT c.cat_left, c.cat_right
                                FROM sb_categs c
                                WHERE '.$cat_like_str.'
                                '.(!isset($params['show_hidden']) || $params['show_hidden'] == 0 ? ' AND c.cat_rubrik = 1' : '').'
                                AND c.cat_level = '.$from.$checked_sql.'
                                ORDER BY c.cat_left');
        }
        else
        {
            $res = $pager->init($categs_total, 'SELECT c.cat_left, c.cat_right
                                FROM sb_categs c LEFT JOIN sb_catrights r ON r.cat_id=c.cat_id AND r.right_ident="'.$plugin_ident.'_read"
                                WHERE '.$cat_like_str.'
                                '.(!isset($params['show_hidden']) || $params['show_hidden'] == 0 ? ' AND c.cat_rubrik = 1' : '').'
                                AND c.cat_level = '.$from.$checked_sql.' AND '.$closed_str.'
                                ORDER BY c.cat_left');
        }

        // строим список номеров страниц
        if ($res)
        {
            $cat_like_str = '';
            foreach ($res as $value)
            {
                list($left, $right) = $value;

                $cat_like_str .= '(c.cat_left >= '.$left.' AND c.cat_right <= '.$right.') OR ';
            }

            $cat_like_str = '('.sb_substr($cat_like_str, 0, -4).') AND c.cat_ident=\''.$cat_ident.'\'';

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
        }
        else
        {
        	if ($level == -1)
        	{
            	$GLOBALS['sbCache']->save($plugin_ident, '');
        	}
            return '';
        }
    }

    $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_url, c.cat_level, c.cat_closed, c.cat_fields,
                          COUNT(DISTINCT l.link_el_id) AS cat_count, ('.$like_str.') AS cat_sel,
                          c.cat_left, c.cat_right, '.$closed_str.' AS cat_rights, c.cat_rubrik, r.group_ids
                          FROM sb_categs c
                             LEFT JOIN sb_catrights r ON r.cat_id=c.cat_id AND r.right_ident=?
                             LEFT JOIN sb_catlinks l ON l.link_cat_id=c.cat_id
                          WHERE '.$cat_like_str.'
                          AND c.cat_level >= ?d AND c.cat_level <= ?d'.$checked_sql.'
                          GROUP BY c.cat_id
                          ORDER BY c.cat_left', $plugin_ident.'_read', $from, $to);

    if (!$res)
    {
        if ($level == -1)
        {
            $GLOBALS['sbCache']->save($plugin_ident, '');
        }
        return '';
    }

    // вытаскиваем идентификатор родительского раздела
    $parent_id = 0;
    $parent_url = '';

    $left = $res[0][8];
    $right = $res[0][9];

    $par_res = sql_query('SELECT cat_id, cat_url FROM sb_categs WHERE cat_left < ?d AND cat_right > ?d AND cat_ident=? ORDER BY cat_left DESC LIMIT 0,1', $left, $right, $cat_ident);
    if ($par_res)
    {
        $parent_id = $par_res[0][0];
        $parent_url = $par_res[0][1];
    }

    $tags = array_merge(array('{ID}',
                              '{CAT_URL}',
                              '{PARENT_ID}',
    						  '{PARENT_URL}',
                              '{TEXT}',
                              '{COUNT}',
                              '{CAT_ELEMS}',
    						  '{SUB_COUNT}',
                              '{URL}',
                              '{SUB_ITEMS}',
                              '{CLOSED_ICON}',

                        ), $tags);

    $more_page = ($params['page'] != '' ? $params['page'] : $_SERVER['PHP_SELF']);
    list($more_page, $more_ext) = sbGetMorePage($more_page);

    $get_str = '';
    if ($get_ident != 'pl_pages')
    {
	    $chpu = array();
	    $chpu['page'] = $more_page;
	    $chpu['ext'] = $more_ext;
	    $chpu['get'] = $get_ident;

    	if ($params['query_string'] == 1)
	    {
	    	if (isset($_GET['sb_year']))
	    	{
	    		$get_str .= 'y'.intval($_GET['sb_year']).'/';

	    		if (isset($_GET['sb_month']))
	    			$get_str .= 'm'.preg_replace('/[^0-9]+/', '', $_GET['sb_month']).'/';
	    	}

	    	if  ($_SERVER['QUERY_STRING'] != '')
	    	{
	    		if (sbPlugins::getSetting('sb_static_urls') == 1)
	            	$get_str .= '?'.$_SERVER['QUERY_STRING'];
	        	else
	            	$get_str .= '&'.$_SERVER['QUERY_STRING'];
	    	}
	    }
    }
    else
    {
    	$chpu = array();
	    $chpu['page'] = 'pl_pages';
	    $chpu['ext'] = '';
	    $chpu['get'] = '';

	    if ($params['query_string'] == 1 && $_SERVER['QUERY_STRING'] != '')
	    {
	    	$get_str .= '?'.$_SERVER['QUERY_STRING'];
	    }
    }

    $i = 0;
    $result = fCategs_Parse_Tree($res, $i, 0, $parent_id, $parent_url, $ctl_lang, $ctl_levels, $ctl_categs_temps, $categs_fields, $tags, $chpu, $ctl_checked, $get_str, $params, $num_sub, $temp_id, $tag_id, $plugin_ident);
    $result = str_replace('{NUM_LIST}', $pt_page_list, $result);

    if ($level > -1)
    {
        return $result;
    }
    else
    {
        $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
        $GLOBALS['sbCache']->save($plugin_ident, $result);
    }

    return '';
}

function fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, $plugin_ident, $cat_ident, $get_ident)
{
    // если есть кэш, то выводим
    if ($GLOBALS['sbCache']->check($plugin_ident, $tag_id, array(0, $temp_id, $params)))
        return;

    $cat_id = -1;

    // строим строку SQL-запроса для выбора выделенного раздела
    if (isset($_GET[$get_ident.'_cid']))
    {
        $cat_id = intval($_GET[$get_ident.'_cid']);
    }
    elseif (isset($_GET[$get_ident.'_scid']))
    {
        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET[$get_ident.'_scid'], $cat_ident);
        if ($res)
        {
            $cat_id = $res[0][0];
        }
        else
        {
            $cat_id = intval($_GET[$get_ident.'_scid']);
        }
    }

	if ($cat_id == -1)
    {
        $GLOBALS['sbCache']->save($plugin_ident, '');
        return;
    }

    // вытаскиваем макет дизайна вывода разделов
    $res = sbQueryCache::getTemplate('sb_categs_temps_full', $temp_id);
    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_CATEGS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save($plugin_ident, '');
        return;
    }

    list($ctf_lang, $ctf_temp, $ctf_categs_temps, $ctf_checked) = $res[0];

    if ($ctf_categs_temps != '')
    {
        $ctf_categs_temps = unserialize($ctf_categs_temps);
    }
    else
    {
        $ctf_categs_temps = array();
    }

    // вытаскиваем пользовательские поля раздела
    $res = sbQueryCache::query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);

    $categs_fields = array();
    $tags = array();

    if ($ctf_checked != '')
    {
        $ctf_checked = explode(' ', $ctf_checked);
    }
    else
    {
        $ctf_checked = array();
    }

    $checked_sql = '';

    // формируем список тегов для пользовательских полей
    if ($res)
    {
        $categs_fields = unserialize($res[0][0]);
        $categs_fields = ($categs_fields == '' ? array() : $categs_fields);

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

                    if (in_array($value['id'], $ctf_checked))
                    {
                        $checked_sql .= ' AND c.cat_fields LIKE \'%:"user_f_'.$value['id'].'";s:1:"1";%\'';
                    }
                }
            }
        }
    }

    $res = sql_query('SELECT c.cat_title, c.cat_url, c.cat_level, c.cat_closed, c.cat_fields,
                          COUNT(DISTINCT l.link_el_id) AS cat_count, r.group_ids, c.cat_left, c.cat_right
                          FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id=c.cat_id
                          LEFT JOIN sb_catrights r ON r.cat_id=c.cat_id AND r.right_ident=?
                          WHERE c.cat_id = ?d '.$checked_sql.'
                          AND c.cat_rubrik=1 GROUP BY c.cat_id', $plugin_ident.'_read', $cat_id);

    if (!$res)
    {
        $GLOBALS['sbCache']->save($plugin_ident, '');
        return;
    }

    $res[0][1] = urlencode($res[0][1]);

    // вытаскиваем идентификатор родительского раздела
    $parent_id = 0;
    $parent_url = '';

    $left = $res[0][7];
    $right = $res[0][8];

    $par_res = sql_query('SELECT cat_id, cat_url FROM sb_categs WHERE cat_left < ?d AND cat_right > ?d AND cat_ident=? ORDER BY cat_left DESC LIMIT 0,1', $left, $right, $cat_ident);
    if ($par_res)
    {
        $parent_id = $par_res[0][0];
        $parent_url = urlencode($par_res[0][1]);
    }

    $tags = array_merge(array('{ID}',
                              '{CAT_URL}',
                              '{PARENT_ID}',
                              '{PARENT_URL}',
                              '{TEXT}',
                              '{COUNT}',
                              '{URL}',
                              '{CLOSED_ICON}'
                        ), $tags);

    $params = unserialize(stripslashes($params));
    if (!isset($params['query_string']))
    	$params['query_string'] = 1;

    $more_page = ($params['page'] != '' ? $params['page'] : $_SERVER['PHP_SELF']);
    list($more_page, $more_ext) = sbGetMorePage($more_page);

    $get_str = '';
    if ($params['query_string'] == 1 && $_SERVER['QUERY_STRING'] != '')
    {
        if (sbPlugins::getSetting('sb_static_urls') == 1)
            $get_str = '?'.$_SERVER['QUERY_STRING'];
        else
            $get_str = '&'.$_SERVER['QUERY_STRING'];
    }

    $href = $more_page;
	if (sbPlugins::getSetting('sb_static_urls') == 1)
    {
        // ЧПУ
        $href .= ($res[0][1] != '' ? $res[0][1] : $cat_id).($more_ext != 'php' ? '.'.$more_ext : '/').$get_str;
    }
    else
    {
        $href .= '?'.$get_ident.'_cid='.$cat_id.$get_str;
    }

	$values = array();

    $values[] = $cat_id;     // {ID}
	$values[] = $res[0][1];  // {CAT_URL}
	$values[] = $parent_id;  // {PARENT_ID}
	$values[] = $parent_url; // {PARENT_URL}
    $values[] = $res[0][0];  // {TEXT}
    $values[] = $res[0][5];  // {COUNT}
    $values[] = $href;       // {URL}

    $dop_tags = array('{ID}', '{CAT_URL}', '{PARENT_ID}', '{PARENT_URL}', '{TEXT}', '{COUNT}', '{URL}');
    $dop_values = array($cat_id, $res[0][1], $parent_id, $parent_url, $res[0][0], $res[0][5], $href);

	if ($res[0][3] == 1 && isset($ctf_categs_temps['closed_icon']))
    {
        $groups = explode('^', trim($res[0][6], '^'));

        $values[] = (isset($_SESSION['sbAuth']) && count(array_intersect($_SESSION['sbAuth']->getUserGroups(), $groups)) > 0 ? '' : str_replace($dop_tags, $dop_values, $ctf_categs_temps['closed_icon'])); // {CLOSED_ICON}
    }
    else
    {
        $values[] = ''; // {CLOSED_ICON}
    }

    if (count($categs_fields) > 0)
    {
		$fields_values = array();

	    if ($res[0][4] != '')
	    {
	        $fields_values = unserialize($res[0][4]);
	        if (!$fields_values)
	        {
	            $fields_values = array();
	        }
	    }

    	$categs_values = array();
        foreach ($categs_fields as $value)
        {
            if (isset($value['sql']) && $value['sql'] == 1)
            {
                $categs_values[] = (isset($fields_values['user_f_'.$value['id']]) ? $fields_values['user_f_'.$value['id']] : '');
            }
        }

        require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
        $allow_bb = 0;
		if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
			$allow_bb = 1;
        $categs_values = sbLayout::parsePluginFields($categs_fields, $categs_values, $ctf_categs_temps, $dop_tags, $dop_values, $ctf_lang, '', '', $allow_bb);
        $values = array_merge($values, $categs_values);
    }

    $result = str_replace($tags, $values, $ctf_temp);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save($plugin_ident, $result);
}

/**
 * Парсинг вывода разделов
 *
 * @param array $res Массив выборки разделов из БД.
 * @param int $i Текущий индекс массива $res.
 * @param int $level Текущий уровень раздела.
 * @param int $parent_id Идентификатор родительского раздела.
 * @param string $ctl_lang Язык макета дизайна.
 * @param array $ctl_levels Массив макетов дизайна уровней.
 * @param array $ctl_categs_temps Массив макетов дизайна пользовательских полей разделов.
 * @param array $categs_fields Описания пользовательских полей разделов.
 * @param array $tags Массив тегов.
 * @param array $chpu Массив настроек для ЧПУ.
 * @param bool $show_closed Показывать закрытые разделы.
 * @param string $get_str GET-параметры.
 * @param array $params Параметры компонента.
 * @param array $num_sub Кол-во выведенных пунктов.
 * @param array $temp_id Идентификатор макета дизайна.
 *
 * @return string Вывод разделов.
 */
function fCategs_Parse_Tree(&$res, &$i, $level, $parent_id, $parent_url, &$ctl_lang, &$ctl_levels, &$ctl_categs_temps, &$categs_fields, &$tags, &$chpu, &$ctl_checked, $get_str, $params, &$num_sub, $temp_id, $tag_id, $plugin_ident)
{
	$num = count($res);

	if (isset($ctl_levels[$level]))
	{
    	$levels = $ctl_levels[$level];
    	if ($level == 0 && $temp_id == -1 && (!isset($params['page']) || $params['page'] != 'pl_pages' || $params['page'] == 'pl_pages' && !isset($params['temp_id'])))
    	{
    		$levels['top'] = '';
    		$levels['bottom'] = '';
    	}
	}
	else
	{
		$levels = $ctl_levels[count($ctl_levels) - 1];
	}

    $categs_temps = isset($ctl_categs_temps[$level]) ? $ctl_categs_temps[$level] : (isset($ctl_categs_temps[count($ctl_categs_temps) - 1]) ? $ctl_categs_temps[count($ctl_categs_temps) - 1] : array());
    $show_sub = (sb_strpos($levels['sub_sel'], '{SUB_ITEMS}') !== false || sb_strpos($levels['sub'], '{SUB_ITEMS}') !== false);
    $show_elem = (sb_strpos($levels['sub_sel'], '{CAT_ELEMS}') !== false || sb_strpos($levels['item_sel'], '{CAT_ELEMS}') !== false ||
                  sb_strpos($levels['sub'], '{CAT_ELEMS}') !== false || sb_strpos($levels['item'], '{CAT_ELEMS}') !== false) && isset($categs_temps['elems_temp']);

    if ($i != 0)
    {
        $levels['top'] = str_replace('{NUM_LIST}', '', $levels['top']);
        $levels['bottom'] = str_replace('{NUM_LIST}', '', $levels['bottom']);
    }

    // верх вывода меню
    $result = '';
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $dop_tags = array('{ID}', '{CAT_URL}', '{PARENT_ID}', '{PARENT_URL}', '{TEXT}', '{COUNT}', '{URL}');

    while ($i < $num)
    {
        $show = ($res[$i][11] == 1 || $params['show_hidden'] == 1);
        $res[$i][2] = urlencode($res[$i][2]);

        if ((!isset($params['show_closed']) || $params['show_closed'] == 0) && $res[$i][10] == 0)
        {
        	$show = false;
        }

        // Показывать/не показывать елементы скрытых разделов
        if ($show && $show_elem && $res[$i][11] != 1 && isset($params['elems_show_hidden']) && $params['elems_show_hidden'] == 1) {
            $show_elem = false;
        }

        if (!$show)
        {
            $j = $i + 1;
            $own_i = $i;
            while ($j < $num && $res[$own_i][3] < $res[$j][3])
            {
                $i++;
                $j++;
            }

            if (($i + 1) < $num && $res[$own_i][3] > $res[$i + 1][3])
            {
                // выход из рекурсии
                if ($result != '')
                    $result = str_replace(array('{PARENT_ID}', '{PARENT_URL}'), array($parent_id, $parent_url), $levels['top']).$result.str_replace(array('{PARENT_ID}', '{PARENT_URL}'), array($parent_id, $parent_url), $levels['bottom']);

                $i++;
                return $result;
            }
            $i++;
            continue;
        }

        $id = $res[$i][0];
        $fields_values = array();

        if ($res[$i][5] != '')
        {
            $fields_values = unserialize($res[$i][5]);
            if (!$fields_values)
            {
                $fields_values = array();
            }
        }

        $sel = $res[$i][7] == 1;
        $href = '';

        if ($chpu['page'] != 'pl_pages')
        {
	        $href = $chpu['page'];
	        if (sbPlugins::getSetting('sb_static_urls') == 1)
	        {
	            // ЧПУ
	            $href .= ($res[$i][2] != '' ? $res[$i][2] : $id).($chpu['ext'] != 'php' ? '.'.$chpu['ext'] : '/').$get_str;
	        }
	        else
	        {
	            $href .= '?'.$chpu['get'].'_cid='.$id.$get_str;
	        }
        }
        else
        {
        	$page_res = sql_query('SELECT p.p_filepath, p.p_filename FROM sb_pages p, sb_catlinks l
        				WHERE l.link_cat_id = ?d AND p.p_id = l.link_el_id AND p.p_default = 1', $id);

        	if ($page_res)
        	{
        		$domain = '';
        		if (count($fields_values) > 0)
        		{
        			$cat_res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_left <= ?d AND cat_right >= ?d AND cat_ident=? AND cat_level = 1 LIMIT 1', $res[$i][8], $res[$i][9], 'pl_pages');
        			if ($cat_res)
        			{
        				$domain = $cat_res[0][0];
        			}
        		}
        		else
        		{
        			$domain = $res[$i][1];
        		}

        		if ($domain != '')
        		{
	        		if (substr_count($domain, '.') > 0)
	        		{
	        			$domain = 'www.'.$domain;
	        		}

	        		$domain = 'http://'.$domain;
        		}
        		else
        		{
        			$domain = SB_COOKIE_DOMAIN;
        		}

        		$href = $domain.(trim($page_res[0][0]) != '' ? '/'.$page_res[0][0] : '').'/'.($page_res[0][1] == trim(sbPlugins::getSetting('sb_directory_index')) ? '' : $page_res[0][1]);
        	}
        }

        $values = array();

        $values[] = $id;         // {ID}
    	$values[] = $res[$i][2]; // {CAT_URL}
    	$values[] = $parent_id;  // {PARENT_ID}
    	$values[] = $parent_url; // {PARENT_URL}
        $values[] = $res[$i][1]; // {TEXT}
        $values[] = $res[$i][6]; // {COUNT}

        //получаем список элементов
        if($show_elem)
        {
            $new_params = array(
                    'ids' => $id,
                    'pm_id' => isset($params['pm_id'])? $params['pm_id'] : null,
                    'page' => isset($params['elem_page'])? $params['elem_page'] : null,
                    'cena' => isset($params['cena'])? $params['cena'] : null,
                    'sort1' => isset($params['sort1'])? $params['sort1'] : null,
                    'sort2' => isset($params['sort2'])? $params['sort2'] : null,
                    'sort3' => isset($params['sort3'])? $params['sort3'] : null,
                    'order1' => isset($params['order1'])? $params['order1'] : null,
                    'order2' => isset($params['order2'])? $params['order2'] : null,
                    'order3' => isset($params['order3'])? $params['order3'] : null,
                    'subcategs' => isset($params['subcategs'])? $params['subcategs'] : null,
                    'show_hidden' => isset($params['show_hidden'])? $params['show_hidden'] : null,
                    'use_sort' => 0,

                );

            sbPlugins::getElemList($plugin_ident, -1, $categs_temps['elems_temp'], $new_params, $values, false, null, 0, false);
        }
        else
        {
            $values[] = '';
        }


        $num_sub_sub = 0;
        $subitems = '';
        $own_i = $i;
        while (($i + 1) < $num && $res[$own_i][3] < $res[$i + 1][3])
        {
		// вывод подпунктов
		$i++;
		if ($show_sub)
		{
			$subitems = fCategs_Parse_Tree($res, $i, $level + 1, $id, $res[$own_i][2], $ctl_lang, $ctl_levels, $ctl_categs_temps, $categs_fields, $tags, $chpu, $ctl_checked, $get_str, $params, $num_sub_sub, $temp_id, $tag_id, $plugin_ident);
		}
		else
		{
			while ($i < $num && $res[$own_i][3] < $res[$i][3])
	                {
	                    $i++;
	                }
		}

		$i--;
	}

        $values[] = $num_sub_sub; // {SUB_COUNT}
        $values[] = $href;        // {URL}
        $values[] = $subitems;    // {SUB_ITEMS}

        $dop_values = array($id, $res[$own_i][2], $parent_id, $parent_url, $res[$own_i][1], $res[$own_i][6], $href);

        if (isset($params['show_closed']) && $params['show_closed'] == 1 && $res[$own_i][4] == 1 && isset($categs_temps['closed_icon']))
        {
            $groups = explode('^', trim($res[$own_i][12], '^'));
            $values[] = (isset($_SESSION['sbAuth']) && count(array_intersect($_SESSION['sbAuth']->getUserGroups(), $groups)) > 0 ? '' : str_replace($dop_tags, $dop_values, $categs_temps['closed_icon'])); // {CLOSED_ICON}
        }
        else
        {
            $values[] = ''; // {CLOSED_ICON}
        }

        if (count($categs_fields) > 0)
        {
            $categs_values = array();
            foreach ($categs_fields as $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $categs_values[] = (isset($fields_values['user_f_'.$value['id']]) ? $fields_values['user_f_'.$value['id']] : '');
                }
            }
    		$allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;
            $categs_values = sbLayout::parsePluginFields($categs_fields, $categs_values, $categs_temps, $dop_tags, $dop_values, $ctl_lang, '', '', $allow_bb);

            $values = array_merge($values, $categs_values);
        }

        $num_sub++; //из очень старых ревизий (не работал тег для подсчета кол-ва разделов)

        if ($sel)
        {
            // вывод выбранного пункта
            if ($subitems != '')
            {
                // с подпунктами
                $result .= str_replace($tags, $values, $levels['sub_sel']);
            }
            else
            {
                // без подпунктов
                $result .= str_replace($tags, $values, $levels['item_sel']);
            }
        }
        else
        {
            if ($subitems != '')
            {
                // с подпунктами
                $result .= str_replace($tags, $values, $levels['sub']);
            }
            else
            {
                // без подпунктов
                $result .= str_replace($tags, $values, $levels['item']);
            }
        }

        if (($i + 1) < $num && $res[$own_i][3] > $res[$i + 1][3])
        {
            // выход из рекурсии
            if ($result != '')
                $result = str_replace(array('{PARENT_ID}', '{PARENT_URL}'), array($parent_id, $parent_url), $levels['top']).$result.str_replace(array('{PARENT_ID}', '{PARENT_URL}'), array($parent_id, $parent_url), $levels['bottom']);

            $i++;
            return $result;
        }

        $i++;
    }

    if ($result != '')
    {
       $result = str_replace(array('{PARENT_ID}', '{PARENT_URL}'), array($parent_id, $parent_url), $levels['top']).$result.str_replace(array('{PARENT_ID}', '{PARENT_URL}'), array($parent_id, $parent_url), $levels['bottom']);
    }

    return $result;
}

/**
 * Возвращает TRUE, если в указанной ветке есть выбранные разделы, и FALSE в ином случае.
 *
 * @param int $temp_id Идентификатор макета дизайна.
 * @param array $params Параметры связанного с меню вывода разделов.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $cat_ident Идентификатор раздела.
 * @param string $get_ident Идентификатор GET-параметра.
 *
 * @return bool TRUE, если в указанной ветке есть выбранные разделы, и FALSE в ином случае.
 */
function fCategs_Count_Menu_Categs($temp_id, $params, $plugin_ident, $cat_ident, $get_ident)
{
	$cat_ident = preg_replace('/[^a-zA-Z0-9\-_]+/', '', $cat_ident);
    $sel_cat_id = -1;

    // строим строку SQL-запроса для выбора выделенного раздела
	if ($get_ident == 'pl_pages')
    {
    	$filename = basename($_SERVER['PHP_SELF']);
	    $dirname = trim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/\\');

	    $res = sql_query('SELECT c.cat_id FROM sb_categs c, sb_pages p, sb_catlinks l
    							WHERE p.p_default=1 AND p.p_filepath=? AND p.p_filename=?
    							AND l.link_el_id=p.p_id AND l.link_cat_id=c.cat_id
    							AND c.cat_ident=? ORDER BY c.cat_left LIMIT 1', $dirname, $filename, 'pl_pages');

        if ($res)
        {
            $sel_cat_id = $res[0][0];
        }

        $temp_id = -1;
    }
    else
    {
	    if (isset($_GET[$get_ident.'_cid']))
	    {
	        $sel_cat_id = intval($_GET[$get_ident.'_cid']);
	    }
	    elseif (isset($_GET[$get_ident.'_scid']))
	    {
	        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET[$get_ident.'_scid'], $cat_ident);
	        if ($res)
	        {
	            $sel_cat_id = $res[0][0];
	        }
	        else
	        {
	            $sel_cat_id = intval($_GET[$get_ident.'_scid']);
	        }
	    }
    }

    if ($sel_cat_id == -1)
    	return false;

    $cat_ids = explode('^', $params['ids']);
    $cat_like_str = '';

    // формируем строку запроса разделов
    $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id IN (?a) ORDER BY cat_left DESC', $cat_ids);
    if ($res)
    {
        foreach ($res as $value)
        {
            list($left, $right) = $value;
            $cat_like_str .= '(c.cat_left >= '.$left.' AND c.cat_right <= '.$right.') OR ';
        }

        $cat_like_str = '('.sb_substr($cat_like_str, 0, -4).') AND c.cat_ident=\''.$cat_ident.'\'';
    }
	else
	{
		return false;
	}

    $ctl_checked = '';
    if ($temp_id != -1)
    {
        // вытаскиваем макет дизайна вывода разделов
        $res = sql_query('SELECT ctl_checked FROM sb_categs_temps_list WHERE ctl_id=?d', $temp_id);
        if ($res)
        {
            list($ctl_checked) = $res[0];
        }
    }

    $checked_sql = '';

    // вытаскиваем пользовательские поля раздела
    if ($ctl_checked != '')
    {
        $ctl_checked = explode(' ', $ctl_checked);
        $res = sbQueryCache::query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);

        if ($res && $res[0][0] != '')
        {
        	$categs_fields = unserialize($res[0][0]);
	        if ($categs_fields)
	        {
	            foreach ($categs_fields as $value)
	            {
	                if (isset($value['sql']) && $value['sql'] == 1 && in_array($value['id'], $ctl_checked))
	                {
	                    $checked_sql .= ' AND c.cat_fields LIKE \'%:"user_f_'.$value['id'].'";s:1:"1";%\'';
	                }
	            }
	        }
        }
    }

	// определяем уровень разделов с которого и по который выводить
    $from = max(0, intval($params['from']) - ($get_ident != 'pl_pages' ? 1 : 0));
	$to = ($params['to'] != '' ? intval($params['to']) - ($get_ident != 'pl_pages' ? 1 : 0) : 1000000);

    $closed_str = '(1)';
    if ($params['show_closed'] == 0)
    {
	    $closed_str = '(r.group_ids IS NULL OR r.group_ids = ""';
	    if (isset($_SESSION['sbAuth']))
	    {
	        foreach ($_SESSION['sbAuth']->getUserGroups() as $g_id)
	        {
	            $closed_str .= ' OR r.group_ids LIKE \'%'.$g_id.'%\'';
	        }
	    }
	    $closed_str .= ')';
    }

    $rubrik_str = '(1)';
    if ($params['show_hidden'] == 0)
    {
    	$rubrik_str = 'c.cat_rubrik';
    }

    $res = sql_query('SELECT c.cat_id, c.cat_level, '.$closed_str.' AS cat_rights, '.$rubrik_str.' AS cat_rubrik
                          FROM sb_categs c
                             LEFT JOIN sb_catrights r ON r.cat_id=c.cat_id AND r.right_ident=?
                          WHERE '.$cat_like_str.'
                          AND c.cat_level >= ?d AND c.cat_level <= ?d '.$checked_sql.'
                          ORDER BY c.cat_left', $plugin_ident.'_read', $from, $to);

    if ($res)
    {
    	$num = count($res);
    	$found = false;
    	for ($i = 0; $i < $num; $i++)
    	{
    		list($cat_id, $cat_level, $cat_closed, $cat_rubrik) = $res[$i];

    		if (!$cat_closed || !$cat_rubrik)
    		{
    		    continue;
    		}

    		if ($cat_id == $sel_cat_id)
    		{
    			$found = true;
    			break;
    		}
    	}

    	if ($found)
        	return true;
    }

    return false;
}

/**
 * Enter description here...
 *
 * @param unknown_type $temp_id
 * @param unknown_type $params
 * @param unknown_type $plugin_ident
 * @param unknown_type $cat_ident
 * @param unknown_type $get_ident
 * @param unknown_type $num
 * @param unknown_type $num_items
 * @param array() $pages страницы вывода подразделов, тем, сообщений (только для модуля "Форум")
 * @return unknown
 */
function fCategs_Show_Menu_Path($temp_id, $params, $plugin_ident, $cat_ident, $get_ident, &$num, $num_items, $pages = array())
{
	$cat_ids = explode('^', $params['ids']);
	$like_str = '';

	if ($get_ident == 'pl_pages')
	{
    	$filename = basename($_SERVER['PHP_SELF']);
	    $dirname = trim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/\\');

	    $res = sql_query('SELECT c.cat_id FROM sb_categs c, sb_pages p, sb_catlinks l
    							WHERE p.p_default=1 AND p.p_filepath=? AND p.p_filename=?
    							AND l.link_el_id=p.p_id AND l.link_cat_id=c.cat_id
    							AND c.cat_ident=? ORDER BY c.cat_left LIMIT 1', $dirname, $filename, 'pl_pages');
        if ($res)
        {
            $like_str = 'c.cat_id='.$res[0][0];
        }
		$temp_id = -1;
    }
	else
    {
	    if($plugin_ident != 'pl_forum')
	    {
		    if (isset($_GET[$get_ident.'_cid']))
		    {
		        $like_str = 'c.cat_id='.intval($_GET[$get_ident.'_cid']);
		    }
		    elseif (isset($_GET[$get_ident.'_scid']))
		    {
		        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET[$get_ident.'_scid'], $cat_ident);
		        if ($res)
		        {
		            $like_str = 'c.cat_id='.$res[0][0];
		        }
		        else
		        {
		            $like_str = 'c.cat_id='.intval($_GET[$get_ident.'_scid']);
		        }
		    }
	    }
		elseif($plugin_ident == 'pl_forum')
	    {
			if(sbPlugins::getSetting('sb_static_urls') == 1 && isset($_GET['forum_sid']) && $_GET['forum_sid'] != '')
	        {
				$res = sql_query('SELECT cat_id, cat_level FROM sb_categs WHERE cat_id=?d AND cat_ident=?', $_GET['forum_sid'], 'pl_forum');
				if(!$res)
				{
					$res = sql_query('SELECT cat_id, cat_level FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['forum_sid'], 'pl_forum');
				}

				if($res && $res[0][1] == 1)
				{
					$_GET['pl_forum_cat'] = $res[0][0];
				}
	            elseif($res && $res[0][1] == 2)
	            {
					$_GET['pl_forum_sub'] = $res[0][0];
	            }
				elseif($res && $res[0][1] == 3)
	            {
					$_GET['pl_forum_theme'] = $res[0][0];
	            }
	        }

			$like_str = '';
		    if (isset($_GET['pl_forum_theme']))
		    {
				$like_str = 'c.cat_id='.intval($_GET['pl_forum_theme']);
		    }
		    elseif (isset($_GET['pl_forum_sub']))
		    {
				$like_str = 'c.cat_id='.intval($_GET['pl_forum_sub']);
		    }
			elseif (isset($_GET['pl_forum_cat']))
			{
				$like_str = 'c.cat_id='.intval($_GET['pl_forum_cat']);
		    }
		}
    }

    if ($like_str == '')
        return '';

    // формируем строку запроса разделов
    $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id IN (?a) ORDER BY cat_left DESC', $cat_ids);
    $cat_like_str = '';

    if ($res)
    {
        foreach ($res as $value)
        {
            list($left, $right) = $value;
            $cat_like_str .= '(c.cat_left >= '.$left.' AND c.cat_right <= '.$right.') OR ';
        }
        $cat_like_str = '('.sb_substr($cat_like_str, 0, -4).') AND c.cat_ident=\''.preg_replace('/[^a-zA-Z0-9\-_]+/', '', $cat_ident).'\'';
    }

    if ($cat_like_str == '')
    {
        return '';
    }

	$res = sql_query('SELECT mpt_item, mpt_last_item FROM sb_menu_path_temps WHERE mpt_id=?d', $params['menu_temp_id']);
	if (!$res)
    {
		return '';
    }

    list($mpt_item, $mpt_last_item) = $res[0];

    $checked_sql = '';

	if ($temp_id != -1)
    {
        $res = sql_query('SELECT ctl_checked FROM sb_categs_temps_list WHERE ctl_id=?d', $temp_id);
        if ($res)
        {
            $ctl_checked = $res[0][0];
            if ($ctl_checked != '')
            {
                $ctl_checked = explode(' ', $ctl_checked);

                $res = sbQueryCache::query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);
                if ($res)
                {
                    $categs_fields = unserialize($res[0][0]);
                    if ($categs_fields)
                    {
                        foreach ($categs_fields as $value)
                        {
                            if (in_array($value['id'], $ctl_checked))
                            {
                                $checked_sql .= ' AND c.cat_fields LIKE \'%:"user_f_'.$value['id'].'";s:1:"1";%\'';
                            }
                        }
                    }
                }
            }
        }
	}

	$from = max(1, intval($params['from'])) - ($get_ident != 'pl_pages' ? 1 : 0);
	$to = $params['to'] != '' ? max(1, intval($params['to'])) - ($get_ident != 'pl_pages' ? 1 : 0) : 1000000;

	$res = sql_query('SELECT c.cat_left, c.cat_right FROM sb_categs c WHERE '.$cat_like_str.'
					AND '.$like_str.$checked_sql.' AND c.cat_level >= '.$from.' AND c.cat_level <= '.$to);

	if (!$res)
	{
		return '';
	}

	list($left, $right) = $res[0];
	$like_str = ' AND c.cat_left <= '.$left.' AND c.cat_right >= '.$right;

	if($plugin_ident == 'pl_forum')
		$like_str .= ' AND c.cat_level != 0 ';

	$closed_str = '';
	if (!isset($params['show_closed']) || $params['show_closed'] == 0)
    {
		$closed_str = ' AND (r.group_ids IS NULL';
		if (isset($_SESSION['sbAuth']))
		{
			foreach ($_SESSION['sbAuth']->getUserGroups() as $g_id)
			{
				$closed_str .= ' OR r.group_ids LIKE \'%'.$g_id.'%\'';
			}
		}
		$closed_str .= ')';
	}

	if ($closed_str != '')
    {
        $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_url, c.cat_level, c.cat_rubrik, c.cat_fields, c.cat_left, c.cat_right
                             FROM sb_categs c LEFT JOIN sb_catrights r ON r.cat_id=c.cat_id
                             WHERE '.$cat_like_str.$like_str.'
                             AND c.cat_level >= '.$from.' AND c.cat_level <= '.$to.$checked_sql.$closed_str.'
                             GROUP BY (c.cat_id)
                             ORDER BY c.cat_left');
    }
    else
    {
        $res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_url, c.cat_level, c.cat_rubrik, c.cat_fields, c.cat_left, c.cat_right
                             FROM sb_categs c
                             WHERE '.$cat_like_str.$like_str.'
                             AND c.cat_level >= '.$from.' AND c.cat_level <= '.$to.$checked_sql.'
                             ORDER BY c.cat_left');
    }

	if (!$res)
    {
		return '';
	}

	$tags = array('{ID}',
                  '{TEXT}',
                  '{URL}');

	if($plugin_ident != 'pl_forum')
	{
		$more_page = ($params['page'] != '' ? $params['page'] : $_SERVER['PHP_SELF']);
	    list($more_page, $more_ext) = sbGetMorePage($more_page);
	}
	else
	{
		$forum_pages = array();
		foreach($pages as $key => $more_page)
		{
			list($forum_pages[$key], $more_ext) = sbGetMorePage($more_page);
		}
	}

	$get_str = '';
	if ($get_ident == 'pl_pages')
	{
		$get_str = ($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
	}
	else
	{
    	if ($_SERVER['QUERY_STRING'] != '')
		{
	        if (sbPlugins::getSetting('sb_static_urls') == 1)
	            $get_str = '?'.$_SERVER['QUERY_STRING'];
	        else
	            $get_str = '&'.$_SERVER['QUERY_STRING'];
	    }
    }

	$result = '';
    for ($i = count($res) - 1; $i >= 0; $i--)
    {
    	$res[$i][2] = urlencode($res[$i][2]);

        if ((!isset($params['show_hidden']) || $params['show_hidden'] == 0) && $res[$i][4] != 1)
        {
            continue;
        }

        if ($num > $num_items)
            break;

        $values = array();
        if ($get_ident == 'pl_pages')
        {
        	$page_res = sql_query('SELECT p.p_filepath, p.p_filename FROM sb_pages p, sb_catlinks l
        				WHERE l.link_cat_id=?d AND p.p_id=l.link_el_id AND p.p_default=1', $res[$i][0]);

        	if ($page_res)
        	{
        		$domain = '';
        		$fields_values = array();
        		if ($res[$i][5] != '')
        		{
        			$fields_values = unserialize($res[$i][5]);
        		}

        		if (count($fields_values) > 0)
        		{
        			$cat_res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_left <= ?d AND cat_right >= ?d AND cat_ident=? AND cat_level=1 LIMIT 1', $res[$i][6], $res[$i][7], 'pl_pages');
        			if ($cat_res)
        			{
        				$domain = $cat_res[0][0];
        			}
        		}
        		else
        		{
        			$domain = $res[$i][1];
        		}

        		if ($domain != '')
        		{
	        		if (substr_count($domain, '.') > 0)
	        		{
	        			$domain = 'www.'.$domain;
	        		}

	        		$domain = 'http://'.$domain;
        		}
        		else
        		{
        			$domain = SB_COOKIE_DOMAIN;
        		}

        		$href = $domain.(trim($page_res[0][0]) != '' ? '/'.$page_res[0][0] : '').'/'.$page_res[0][1].$get_str;
        	}
        }
        else
        {
        	if($plugin_ident == 'pl_forum')
        	{
	        	if($res[$i][3] == 1)
		        {
					$href = $forum_pages['categs_page'];
		        }
		        elseif($res[$i][3] == 2)
		        {
		        	$href = $forum_pages['themes_page'];
		        }
		        else
		        {
		        	$href = $forum_pages['messages_page'];
		        }

	        	if (sbPlugins::getSetting('sb_static_urls') == 1)
				{
					if(isset($res[$i-1][2]))
					{
						$cat = (isset($res[$i-1][2]) && $res[$i-1][2] != '' ? $res[$i-1][2].'/' : $res[$i-1][0].'/');
					}
					else
					{
						$cat = $res[$i][0].'/';
					}

					$href .= $cat.($res[$i][2] != '' ? $res[$i][2] : $res[$i][0]).($more_ext != 'php' ? '.'.$more_ext : '/').$get_str;
		        }
		        else
		        {
					if($res[$i][3] == 1)
		        	{
						$href .= '?pl_forum_cat='.$res[$i][0].$get_str;
		        	}
		        	elseif($res[$i][3] == 2)
		        	{
						$href .= '?pl_forum_cat_sel='.(isset($res[$i-1][0]) ? $res[$i-1][0] : '').'&pl_forum_sub='.$res[$i][0].$get_str;
		        	}
					elseif($res[$i][3] == 3)
		        	{
						$href .= '?pl_forum_cat_sel='.(isset($res[$i-2][0]) ? $res[$i-2][0] : '').'&pl_forum_sub='.(isset($res[$i-1][0]) ? $res[$i-1][0] : '').'&pl_forum_theme='.$res[$i][0].$get_str;
		        	}
		        }
        	}
		    else
		    {
				$href = $more_page;
		        if (sbPlugins::getSetting('sb_static_urls') == 1)
		        {
					// ЧПУ
					$href .= ($res[$i][2] != '' ? $res[$i][2] : $res[$i][0]).($more_ext != 'php' ? '.'.$more_ext : '/').$get_str;
		        }
		        else
		        {
					$href .= '?'.$get_ident.'_cid='.$res[$i][0].$get_str;
		        }
		    }
        }

        $values[] = $res[$i][0];	// {ID}
		$values[] = $res[$i][1];	// {TEXT}
		$values[] = $href;			// {URL}

		if ($num != 1)
        {
            // не последний пункт
            $result = str_replace($tags, $values, $mpt_item).$result;
        }
        else
        {
			// последний пункт
			$result = str_replace($tags, $values, $mpt_last_item).$result;
        }
		$num++;
    }

	$num--;
	return $result;
}

function fCategs_Get_Func_Elems_List($plugin_ident)
{
    if(preg_match('/^pl_plugin/', $plugin_ident))
    {
        return 'fPlugin_Maker_Elem_List';
    }

    switch ($plugin_ident)
    {
        case 'pl_news': return 'fBanners_Elem';
        case 'pl_basket': return 'fBasket_Elem_Mini_Form';
        case 'pl_calendar': return 'fCalendar_Elem_List';
        case 'pl_faq': return 'fFaq_Elem_List';
        case 'pl_forum': return 'fForum_Elem_Forum_Path';
        case 'pl_imagelib': return 'fImagelib_Elem_List';
        case 'pl_menu': return 'fMenu_Elem_Tree';
        case 'pl_news': return 'fNews_Elem_List';
        case 'pl_payment': return 'fPayment_List';
        case 'pl_polls': return 'fPolls_Elem_List';
        case 'pl_search': return 'fSearch_Elem_Results';
        case 'pl_services_rutube': return 'fServices_Rutube_List';
        case 'pl_site_users': return 'fSite_Users_Elem_Data';
        case 'pl_sprav': return 'fSprav_Elem_List';
        case 'pl_tester': return 'fTester_Elem_Test';
        default: return false;
    }
}
?>