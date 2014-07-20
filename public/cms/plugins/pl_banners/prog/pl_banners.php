<?php

function fBanners_Elem($el_id, $temp_id, $params, $tag_id)
{
	//	не выводим баннеры, если индексируем нашим поиском
	if (isset($_GET['sb_search']) && $_GET['sb_search'] == 1)
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

//	раньше параметр назывался $params['id'] теперь $params['ids'] оставили оба варианта для тех поль-ей у которых уже настроенно с $params['id']
	if (isset($params['ids']) && $params['ids'] != '')
    {
        $cat_ids = explode('^', $params['ids']);
    }
    elseif (isset($params['id']) && $params['id'] != '')
    {
		$cat_ids = array(intval($params['id']));
    }
    else
    {
		return;
    }

    // если следует выводить подразделы, то вытаскиваем их ID
    if (isset($params['sub_categs']) && $params['sub_categs'] == 1)
    {
		$res = sql_param_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_banners"
							AND c2.cat_ident = "pl_banners"
							AND c2.cat_id IN (?a)
							ORDER BY c.cat_left', $cat_ids);
        if ($res)
        {
        	$cat_ids = array();
            foreach ($res as $value)
            {
            	$value[0];
                $cat_ids[] = $value[0];
            }
        }
        else
        {
            //  указанные разделы были удалены
            return;
        }
    }

	$sbt_element = array();
	$sbt_fields_temps = array();
	$sbt_checked = '';
	$sbt_lang = '';

	if ($temp_id > 0)
	{
//		достаем макет дизайна баннера
		//$res = sql_param_query('SELECT sbt_lang, sbt_element, sbt_fields_temps, sbt_checked FROM sb_banners_temps WHERE sbt_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_banners_temps', $temp_id);
		list($sbt_lang, $sbt_element, $sbt_fields_temps, $sbt_checked) = $res[0];

		$sbt_element = unserialize($sbt_element);
		$sbt_fields_temps = unserialize($sbt_fields_temps);
	}

//  достаем пользовательские поля
    //$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_banners"');
    $res = sbQueryCache::query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_banners');

	$tags = array();
    $elems_fields = array();
    $elems_fields_select_sql = '';

	// формируем SQL-запрос для пользовательских полей
	if ($res)
    {
        if($res[0][0] != '')
        {
            $elems_fields = unserialize($res[0][0]);
        }

        if ($elems_fields)
        {
	        foreach ($elems_fields as $value)
	        {
	            if (isset($value['sql']) && $value['sql'] == 1)
	            {
	                $elems_fields_select_sql .= ', sb.user_f_'.$value['id'];

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

	$elems_fields_where_sql = '';
	if ($sbt_checked != '')
    {
        $sbt_checked = explode(' ', $sbt_checked);
        foreach ($sbt_checked as $value)
        {
            $elems_fields_where_sql .= ' AND sb.user_f_'.$value.'=1';
        }
    }

	$today = time();

	// формируем SQL-запрос для фильтра
	$elems_fields_filter_sql = '';
	if (isset($params['use_filter']) && $params['use_filter'] == 1)
	{
		$morph_db = false;
		if (isset($params['filter_morph']) && $params['filter_morph'] == 1)
		{
			require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
		}

		require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		if(!isset($date_temp))
			$date_temp = '{DAY}.{MONTH}.{LONG_YEAR}';

		$elems_fields_filter_sql = '(';
		$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'sb', 'b_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
    }

    if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
    	$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
	else
		$elems_fields_filter_sql = '';

		/**/

	// определяем раздел страницы, на которой выводится баннер
	$path_parts = @pathinfo($_SERVER['PHP_SELF']);
	$page_path = isset($path_parts['dirname']) ? trim($path_parts['dirname'], '/') : '';
	$page_filename = isset($path_parts['basename']) ? $path_parts['basename'] : '';

	// вытаскиваем данные страницы на которой выводиться баннер
    $res_rst = sql_param_query('SELECT c.cat_id FROM sb_pages p, sb_categs c, sb_catlinks l WHERE
			l.link_cat_id = c.cat_id AND l.link_el_id = p.p_id AND c.cat_ident="pl_pages" AND p.p_filename=? AND p.p_filepath=?', $page_filename, $page_path);

    // запоминаем раздел страницы для проверки на ограничения
    if ($res_rst)
	{
    	$ban_cat_id = $res_rst[0][0];
    }
    else
	{
        $ban_cat_id = '0';
	}

	// вытаскиваем идентификаторы баннеров, которые не надо выводить на этой странице
    $index_page = sbPlugins::getSetting('sb_directory_index');
    if (trim($index_page) == '')
    {
        $index_page = 'index.php';
    }

    $php_self = trim($GLOBALS['sbSql']->escape($_SERVER['PHP_SELF']), "'");
    $dirname = trim($GLOBALS['sbSql']->escape(rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/')), "'");

	$page_like_str = '(sbr_url = '.$GLOBALS['sbSql']->escape($_SERVER['PHP_SELF']).' OR
	                   sbr_url = '.$GLOBALS['sbSql']->escape(trim($_SERVER['PHP_SELF'], '/')).' OR
	                   sbr_url = \'http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\' OR
	                   sbr_url = \'http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\' OR
	                   sbr_url = \'https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\' OR
	                   sbr_url = \'https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\'
	 				 ';

	if (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '')
	{
		$page_like_str .= ' OR sbr_url = '.$GLOBALS['sbSql']->escape($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape(trim($_SERVER['PHP_SELF'], '/').'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      ';
	}

	if (isset($GLOBALS['PHP_SELF']) && $GLOBALS['PHP_SELF'] != $_SERVER['PHP_SELF'])
	{
	  	$chpu_self = trim($GLOBALS['sbSql']->escape($GLOBALS['PHP_SELF']), "'");

	   	$page_like_str .= ' OR '.$GLOBALS['sbSql']->escape($GLOBALS['PHP_SELF']).' LIKE `sbr_url`
	                      OR '.$GLOBALS['sbSql']->escape(trim($GLOBALS['PHP_SELF'], '/')).' LIKE `sbr_url`
	                      OR \'http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
	                      OR \'http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
	                      OR \'https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
	                      OR \'https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
						';

	    if (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '')
	    {
	    	$page_like_str .= ' OR sbr_url = '.$GLOBALS['sbSql']->escape($GLOBALS['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape(trim($GLOBALS['PHP_SELF'], '/').'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
						';
	    }
	}

	if ($page_filename == $index_page)
	{
		$page_like_str .= ' OR \'http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \'http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \'https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \'https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \''.$dirname.'/\' LIKE sbr_url
	                      ';

		if (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '')
	    {
	    	$page_like_str .= ' OR sbr_url = '.$GLOBALS['sbSql']->escape('http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape($dirname.'/?'.$_SERVER['QUERY_STRING']).'
							';
	    }
	}

	$page_like_str .= ')';

	$ban_b_ids = array(0);

	if ($page_like_str != '')
	{
    	$res_rst = sql_param_query('SELECT sbr_bid FROM sb_banners_restricted WHERE '.$page_like_str);
    	if ($res_rst)
    	{
    		foreach ($res_rst as $value)
    		{
    			$ban_b_ids[] = $value[0];
    		}
    	}
	}

	$cur_month = sb_date('n', $today);
	$cur_week_day = sb_date('w', $today);
	$cur_hour = sb_date('G', $today)+1;

	$ogranichenie_str = ' AND sb.sb_plan_dates LIKE "%'.($cur_week_day + 1).','.$cur_hour.';%"
		AND (CONCAT(";", sb.sb_plan_dates, ";") LIKE "%;'.$cur_month.';%" OR CONCAT("||", sb.sb_plan_dates, ";") LIKE "%||'.$cur_month.';%")';

//	извлечение баннеров из указанных разделов
	$res_bns = sql_param_query('SELECT sb.sb_id, sb.sb_priority, sb.sb_name, sb.sb_link, sb.sb_code, sb.sb_upload_name, sb.sb_plan_dates, sb.sb_statistics, l.link_cat_id
    				'.$elems_fields_select_sql.'
					FROM sb_banners sb, sb_catlinks l
					WHERE sb.sb_id NOT IN (?a)
					'.$elems_fields_where_sql.$elems_fields_filter_sql.$ogranichenie_str.'
					AND (CONCAT("^", sb.sb_restricted_cats, "^") NOT LIKE "%^'.$ban_cat_id.'^%" OR sb.sb_restricted_cats IS NULL)
					AND l.link_cat_id IN (?a)
			        AND sb.sb_id=l.link_el_id
					AND (sb.sb_count_show > 0 OR sb.sb_count_show = -1)
					AND (sb.sb_date_from IS NULL OR sb.sb_date_from <= '.$today.')
					AND (sb.sb_date_to IS NULL OR sb.sb_date_to >= '.$today.')
					AND sb.sb_active IN ('.sb_get_workflow_demo_statuses().') ORDER BY RAND()*sb.sb_priority DESC LIMIT 1', $ban_b_ids, $cat_ids);


	if($res_bns)
	{
        $result = '';
        $num_fields = count($res_bns[0]);
        $values = array();

        list($bid, $priority, $name, $link, $code, $upload, $sb_plan_dates, $stat, $cat_id) = $res_bns[0];

        if ($num_fields > 9)
        {
            for ($in = 9; $in < $num_fields; $in++)
            {
                $values[$bid][] = $res_bns[0][$in];
            }
        }

        $robot = sbIsSearchRobot();
        if (!$robot)
        {
            sql_param_query('UPDATE LOW_PRIORITY sb_banners SET sb_count_show = sb_count_show - 1 WHERE sb_id=?d AND sb_count_show != -1', $bid);

            if ($stat == 1)
            {
                $year = sb_date('Y', $today);
                $month = sb_date('n', $today);
                $day = sb_date('j', $today);

                $check_stat = sql_param_query('SELECT sb_bid FROM sb_banners_statistics WHERE sb_bid=?d AND
							sb_year = ?d AND sb_month = ?d AND sb_day = ?d', $bid, $year, $month, $day);

                if ($check_stat)
                {
                    sql_param_query('UPDATE LOW_PRIORITY sb_banners_statistics SET sb_count_views = sb_count_views + 1 WHERE sb_bid = ?d AND sb_year = ?d AND sb_month = ?d AND sb_day = ?d', $bid, $year, $month, $day);
                }
                else
                {
                    $row = array();
                    $row['sb_bid'] = $bid;
                    $row['sb_year'] = $year;
                    $row['sb_month'] = $month;
                    $row['sb_day'] = $day;
                    $row['sb_count_clicks'] = 0;
                    $row['sb_count_views'] = 1;

                    sql_param_query('INSERT LOW_PRIORITY INTO sb_banners_statistics SET ?a', $row);
                }
            }
        }

        if (isset($link) && $link != '' &&
        (sb_stripos($link, '://www.' . SB_COOKIE_DOMAIN) !== false ||
        sb_stripos($link, '://' . SB_COOKIE_DOMAIN) !== false ||
        sb_stripos($link, 'http://') === false))
        {
            if($stat == 1)
            {
                $ban_link = $link . (sb_strpos($link, '?') !== false ? '&' : '?') . 'bid=' . $bid;
            }
            else
            {
                $ban_link = $link;
            }
        }
        else
        {
            //$ban_link = $link;
            $ban_link = SB_DOMAIN . '/cms/admin/banner.php?bid=' . $bid;
        }

        $ban_name = $name;

        // Дата последнего изменения
        if (isset($sbt_element['text']) && sb_strpos($sbt_element['text'], '{CHANGE_DATE}') !== false)
        {
            $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $bid, 'pl_banners');
            if (!empty($code))
                $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sbt_fields_temps['sbt_text_change_date'], $sbt_lang) : ''; //   CHANGE_DATE
            elseif (!empty($upload))
                $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sbt_fields_temps['sbt_img_change_date'], $sbt_lang) : ''; //   CHANGE_DATE
        }
        else
        {
            $change_date = '';
        }

        $elem_tags = array();
        if (!empty($code))
        {
            $elem = $code;
            $elem_tags = array('{BANNER_TITLE}', '{BANNER_LINK}', '{BANNER_TEXT}', '{CHANGE_DATE}');
            $elem_values = array($ban_name, $ban_link, $elem, $change_date);

            if (count($values) > 0)
            {
                require_once(SB_CMS_LIB_PATH . '/sbLayout.inc.php');
                $values = sbLayout::parsePluginFields($elems_fields, $values[$bid], $sbt_fields_temps, array(), array(), $sbt_lang, 'text_');
            }

            if (!isset($sbt_element['text']) || $sbt_element['text'] == '')
            {
                $element = '<a href="{BANNER_LINK}">{BANNER_TEXT}</a>';
            }
            else
            {
                $element = $sbt_element['text'];
                $elem_values[2] = str_replace('{BANNER_LINK}', '/cms/admin/banner.php?bid=' . $bid, $elem);
            }
        }
        elseif (!empty($upload))
        {
            $elem = $upload;

//              значение для url изображения дублируется для того чтобы оно заменялось как на тег {BANNER_IMG} так и на {BANNER_URL}. Сделано это в связи с заменой названия тега {BANNER_IMG} на {BANNER_URL} в макетах дизайна.
            $elem_tags = array('{BANNER_TITLE}', '{BANNER_LINK}', '{BANNER_IMG}', '{BANNER_URL}', '{CHANGE_DATE}');
            $elem_values = array($ban_name, $ban_link, $elem, $elem, $change_date);

            if (count($values) > 0)
            {
                require_once(SB_CMS_LIB_PATH . '/sbLayout.inc.php');
                $values = sbLayout::parsePluginFields($elems_fields, $values[$bid], $sbt_fields_temps, array(), array(), $sbt_lang, 'img_');
            }

            if (!isset($sbt_element['img']) || $sbt_element['img'] == '')
            {
                $element = '<a href="{BANNER_LINK}"><img border=0 src="{BANNER_IMG}"></a>';
            }
            else
            {
                $element = $sbt_element['img'];
            }
        }

        $result .= str_replace(array_merge($elem_tags, $tags), array_merge($elem_values, $values), $element);
        $result = preg_replace('/\{[_A-Z0-9' . $GLOBALS['sb_reg_upper_interval'] . ']+\}/' . SB_PREG_MOD, '', $result);

        //чистим код от инъекций
        $result = sb_clean_string($result);

        eval(' ?>' . $result . '<?php ');
    }

    $GLOBALS['sbCache']->setLastModified(time());
}

function fBanners_Elem_List($el_id, $temp_id, $params, $tag_id)
{
	//	не выводим баннеры, если индексируем нашим поиском
	if (isset($_GET['sb_search']) && $_GET['sb_search'] == 1)
		return;

	$params = unserialize(stripslashes($params));

	if (isset($params['ids']) && $params['ids'] != '')
    {
        $cat_ids = explode('^', $params['ids']);
    }
    else
    {
		return;
    }

    // если следует выводить подразделы, то вытаскиваем их ID
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
		$res = sql_param_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_banners"
							AND c2.cat_ident = "pl_banners"
							AND c2.cat_id IN (?a)
							ORDER BY c.cat_left', $cat_ids);
        if ($res)
        {
        	$cat_ids = array();
            foreach ($res as $value)
            {
                $cat_ids[] = $value[0];
            }
        }
        else
        {
            //  указанные разделы были удалены
            return;
        }
    }


    // проверяем, является ли закрытым раздел который надо выводить
    $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed = 1 AND cat_id IN (?a)', $cat_ids);
    if ($res)
    {
        // проверяем права на закрытые разделы и исключаем их из вывода
        $closed_ids = array();
        foreach ($res as $value)
        {
            $closed_ids[] = $value[0];
        }
        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_banners_read');
    }

    $order_by_sql = '';
    if(isset($params['sort1']) && $params['sort1'] != '')
    {
        $order_by_sql .= $params['sort1'].' '.$params['order1'].', ';
    }

    if(isset($params['sort2']) && $params['sort2'] != '')
    {
        $order_by_sql .= $params['sort2'].' '.$params['order2'].', ';
    }

    if(isset($params['sort3']) && $params['sort3'] != '')
    {
        $order_by_sql .= $params['sort3'].' '.$params['order3'].', ';
    }

    $order_by_sql = sb_substr($order_by_sql, 0, -2);

    if($order_by_sql == '')
    {
        $order_by_sql = 'RAND()*sb.sb_priority DESC ';
    }

	$sbdl_element = array();
	$sbdl_checked = '';

	if ($temp_id > 0)
	{
        //достаем макеты дизайна списка и элемента
        $res = sbQueryCache::getTemplate('sb_banners_temps_list', $temp_id, true);

		extract($res[0]);

		$sbt_element = unserialize($sbt_element);
		$sbt_fields_temps = unserialize($sbt_fields_temps);
	}

    //достаем пользовательские поля
    $res = sbQueryCache::query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_banners');

	$tags = array();
    $elems_fields = array();
    $elems_fields_select_sql = '';

	// формируем SQL-запрос для пользовательских полей
	if ($res)
    {
        if($res[0][0] != '')
        {
            $elems_fields = unserialize($res[0][0]);
        }

        if ($elems_fields)
        {
	        foreach ($elems_fields as $value)
	        {
	            if (isset($value['sql']) && $value['sql'] == 1)
	            {
	                $elems_fields_select_sql .= ', sb.user_f_'.$value['id'];

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

	$elems_fields_where_sql = '';
	if ($sbdl_checked != '')
    {
        $sbdl_checked = explode(' ', $sbdl_checked);
        foreach ($sbdl_checked as $value)
        {
            $elems_fields_where_sql .= ' AND sb.user_f_'.$value.'=1';
        }
    }

	$today = time();


	// определяем раздел страницы, на которой выводится баннер
	$path_parts = @pathinfo($_SERVER['PHP_SELF']);
	$page_path = isset($path_parts['dirname']) ? trim($path_parts['dirname'], '/') : '';
	$page_filename = isset($path_parts['basename']) ? $path_parts['basename'] : '';


	// вытаскиваем данные страницы на которой выводиться баннер
    $res_rst = sql_param_query('SELECT c.cat_id FROM sb_pages p, sb_categs c, sb_catlinks l WHERE
			l.link_cat_id = c.cat_id AND l.link_el_id = p.p_id AND c.cat_ident="pl_pages" AND p.p_filename=? AND p.p_filepath=?', $page_filename, $page_path);

    // запоминаем раздел страницы для проверки на ограничения
    if ($res_rst)
	{
    	$ban_cat_id = $res_rst[0][0];
    }
    else
	{
        $ban_cat_id = '0';
	}

	// вытаскиваем идентификаторы баннеров, которые не надо выводить на этой странице
    $index_page = sbPlugins::getSetting('sb_directory_index');
    if (trim($index_page) == '')
    {
        $index_page = 'index.php';
    }

    $php_self = trim($GLOBALS['sbSql']->escape($_SERVER['PHP_SELF']), "'");
    $dirname = trim($GLOBALS['sbSql']->escape(rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/')), "'");

	$page_like_str = '(sbr_url = '.$GLOBALS['sbSql']->escape($_SERVER['PHP_SELF']).' OR
	                   sbr_url = '.$GLOBALS['sbSql']->escape(trim($_SERVER['PHP_SELF'], '/')).' OR
	                   sbr_url = \'http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\' OR
	                   sbr_url = \'http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\' OR
	                   sbr_url = \'https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\' OR
	                   sbr_url = \'https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'\'
	 				 ';

	if (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '')
	{
		$page_like_str .= ' OR sbr_url = '.$GLOBALS['sbSql']->escape($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape(trim($_SERVER['PHP_SELF'], '/').'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$php_self.'?'.$_SERVER['QUERY_STRING']).'
	                      ';
	}

	if (isset($GLOBALS['PHP_SELF']) && $GLOBALS['PHP_SELF'] != $_SERVER['PHP_SELF'])
	{
	  	$chpu_self = trim($GLOBALS['sbSql']->escape($GLOBALS['PHP_SELF']), "'");

	   	$page_like_str .= ' OR '.$GLOBALS['sbSql']->escape($GLOBALS['PHP_SELF']).' LIKE `sbr_url`
	                      OR '.$GLOBALS['sbSql']->escape(trim($GLOBALS['PHP_SELF'], '/')).' LIKE `sbr_url`
	                      OR \'http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
	                      OR \'http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
	                      OR \'https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
	                      OR \'https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'\' LIKE `sbr_url`
						';

	    if (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '')
	    {
	    	$page_like_str .= ' OR sbr_url = '.$GLOBALS['sbSql']->escape($GLOBALS['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape(trim($GLOBALS['PHP_SELF'], '/').'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
	                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$chpu_self.'?'.$_SERVER['QUERY_STRING']).'
						';
	    }
	}

	if ($page_filename == $index_page)
	{
		$page_like_str .= ' OR \'http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \'http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \'https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \'https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'/\' LIKE sbr_url
	                      OR \''.$dirname.'/\' LIKE sbr_url
	                      ';

		if (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING']) != '')
	    {
	    	$page_like_str .= ' OR sbr_url = '.$GLOBALS['sbSql']->escape('http://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape('http://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape('https://www.'.sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN).$dirname.'?'.$_SERVER['QUERY_STRING']).'
		                      OR sbr_url = '.$GLOBALS['sbSql']->escape($dirname.'/?'.$_SERVER['QUERY_STRING']).'
							';
	    }
	}

	$page_like_str .= ')';

	$ban_b_ids = array(0);

	if ($page_like_str != '')
	{
    	$res_rst = sql_param_query('SELECT sbr_bid FROM sb_banners_restricted WHERE '.$page_like_str);
    	if ($res_rst)
    	{
    		foreach ($res_rst as $value)
    		{
    			$ban_b_ids[] = $value[0];
    		}
    	}
	}

	$cur_month = sb_date('n', $today);
	$cur_week_day = sb_date('w', $today);
	$cur_hour = sb_date('G', $today)+1;

	$ogranichenie_str = ' AND sb.sb_plan_dates LIKE "%'.($cur_week_day + 1).','.$cur_hour.';%"
		AND (CONCAT(";", sb.sb_plan_dates, ";") LIKE "%;'.$cur_month.';%" OR CONCAT("||", sb.sb_plan_dates, ";") LIKE "%||'.$cur_month.';%")';


//	извлечение баннеров из указанных разделов
	$res_bns = sql_param_query('SELECT sb.sb_id, sb.sb_priority, sb.sb_name, sb.sb_link, sb.sb_code, sb.sb_upload_name, sb.sb_plan_dates, sb.sb_statistics, l.link_cat_id
    				'.$elems_fields_select_sql.'
					FROM sb_banners sb, sb_catlinks l
					WHERE sb.sb_id NOT IN (?a)
					'.$elems_fields_where_sql.$ogranichenie_str.'
					AND (CONCAT("^", sb.sb_restricted_cats, "^") NOT LIKE "%^'.$ban_cat_id.'^%" OR sb.sb_restricted_cats IS NULL)
					AND l.link_cat_id IN (?a)
			        AND sb.sb_id=l.link_el_id
					AND (sb.sb_count_show > 0 OR sb.sb_count_show = -1)
					AND (sb.sb_date_from IS NULL OR sb.sb_date_from <= '.$today.')
					AND (sb.sb_date_to IS NULL OR sb.sb_date_to >= '.$today.')
					AND sb.sb_active IN ('.sb_get_workflow_demo_statuses().') ORDER BY '.$order_by_sql.' '.($sbdl_count > 0? 'LIMIT '.$sbdl_count : ''), $ban_b_ids, $cat_ids);


	if ($res_bns)
    {
        $num_fields = count($res_bns[0]);
        $num = count($res_bns);

        $result = '';
        //Начало цикла
        $showCount = ($sbdl_count > 0 && $sbdl_count < $num) ? $sbdl_count : $num;

        for ($i = 0; $i < $showCount; $i++)
        {
            list($bid, $priority, $name, $link, $code, $upload, $sb_plan_dates, $stat, $cat_id) = $res_bns[$i];
            $values = array();
            if ($num_fields > 9)
            {
                for ($in = 9; $in < $num_fields; $in++)
                {
                    $values[$bid][] = $res_bns[$i][$in];
                }
            }

            $robot = sbIsSearchRobot();
            if (!$robot)
            {
                sql_param_query('UPDATE LOW_PRIORITY sb_banners SET sb_count_show = sb_count_show - 1 WHERE sb_id=?d AND sb_count_show != -1', $bid);

                if ($stat == 1)
                {
                    $year = sb_date('Y', $today);
                    $month = sb_date('n', $today);
                    $day = sb_date('j', $today);

                    $check_stat = sql_param_query('SELECT sb_bid FROM sb_banners_statistics WHERE sb_bid=?d AND
							sb_year = ?d AND sb_month = ?d AND sb_day = ?d', $bid, $year, $month, $day);

                    if ($check_stat)
                    {
                        sql_param_query('UPDATE LOW_PRIORITY sb_banners_statistics SET sb_count_views = sb_count_views + 1 WHERE sb_bid = ?d AND sb_year = ?d AND sb_month = ?d AND sb_day = ?d', $bid, $year, $month, $day);
                    }
                    else
                    {
                        $row = array();
                        $row['sb_bid'] = $bid;
                        $row['sb_year'] = $year;
                        $row['sb_month'] = $month;
                        $row['sb_day'] = $day;
                        $row['sb_count_clicks'] = 0;
                        $row['sb_count_views'] = 1;

                        sql_param_query('INSERT LOW_PRIORITY INTO sb_banners_statistics SET ?a', $row);
                    }
                }
            }

            if (isset($link) && $link != '' &&
            (sb_stripos($link, '://www.' . SB_COOKIE_DOMAIN) !== false ||
            sb_stripos($link, '://' . SB_COOKIE_DOMAIN) !== false ||
            sb_stripos($link, 'http://') === false))
            {
                if($stat == 1)
                {
                    $ban_link = $link . (sb_strpos($link, '?') !== false ? '&' : '?') . 'bid=' . $bid;
                }
                else
                {
                    $ban_link = $link;
                }
            }
            else
            {
                //$ban_link = $link;
                $ban_link = SB_DOMAIN . '/cms/admin/banner.php?bid='.$bid;
            }

            $ban_name = $name;

            // Дата последнего изменения
            if (isset($sbt_element['text']) && sb_strpos($sbt_element['text'], '{CHANGE_DATE}') !== false)
            {
                $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $bid, 'pl_banners');
                if (!empty($code))
                    $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sbt_fields_temps['sbt_text_change_date'], $sbt_lang) : ''; //   CHANGE_DATE
                elseif (!empty($upload))
                    $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sbt_fields_temps['sbt_img_change_date'], $sbt_lang) : ''; //   CHANGE_DATE
            }
            else
            {
                $change_date = '';
            }

            $elem_tags = array();
            $values_new = array();
            if (!empty($code))
            {
                $elem = $code;
                $elem_tags = array('{BANNER_TITLE}', '{BANNER_LINK}', '{BANNER_TEXT}', '{CHANGE_DATE}');
                $elem_values = array($ban_name, $ban_link, $elem, $change_date);

                if (count($values) > 0)
                {
                    require_once(SB_CMS_LIB_PATH . '/sbLayout.inc.php');
                    $values_new = sbLayout::parsePluginFields($elems_fields, $values[$bid], $sbt_fields_temps, array(), array(), $sbt_lang, 'text_');
                }

                if (!isset($sbt_element['text']) || $sbt_element['text'] == '')
                {
                    $element = '<a href="{BANNER_LINK}">{BANNER_TEXT}</a>';
                }
                else
                {
                    $element = $sbt_element['text'];
                    $elem_values[2] = str_replace('{BANNER_LINK}', '/cms/admin/banner.php?bid=' . $bid, $elem);
                }
            }
            elseif (!empty($upload))
            {
                $elem = $upload;

//              значение для url изображения дублируется для того чтобы оно заменялось как на тег {BANNER_IMG} так и на {BANNER_URL}. Сделано это в связи с заменой названия тега {BANNER_IMG} на {BANNER_URL} в макетах дизайна.
                $elem_tags = array('{BANNER_TITLE}', '{BANNER_LINK}', '{BANNER_IMG}', '{BANNER_URL}', '{CHANGE_DATE}');
                $elem_values = array($ban_name, $ban_link, $elem, $elem, $change_date);

                if (count($values) > 0)
                {
                    require_once(SB_CMS_LIB_PATH . '/sbLayout.inc.php');
                    $values_new = sbLayout::parsePluginFields($elems_fields, $values[$bid], $sbt_fields_temps, array(), array(), $sbt_lang, 'img_');
                }

                if (!isset($sbt_element['img']) || $sbt_element['img'] == '')
                {
                    $element = '<a href="{BANNER_LINK}"><img border=0 src="{BANNER_IMG}"></a>';
                }
                else
                {
                    $element = $sbt_element['img'];
                }
            }

            $element = str_replace('{ELEM}', $element, $sbdl_element);
            $result .= str_replace(array_merge($elem_tags, $tags), array_merge($elem_values, $values_new), $element);
        }

        $result = preg_replace('/\{[_A-Z0-9' . $GLOBALS['sb_reg_upper_interval'] . ']+\}/' . SB_PREG_MOD, '', $sbdl_top . $result . $sbdl_bottom);

        //чистим код от инъекций
        $result = sb_clean_string($result);

        eval(' ?>' . $result . '<?php ');
    }

    $GLOBALS['sbCache']->setLastModified(time());
}
?>