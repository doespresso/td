<?php

	function fForum_Elem_Forum_Path($el_id, $temp_id, $params, $tag_id)
	{
		if ($GLOBALS['sbCache']->check('pl_forum', $tag_id, array($el_id, $temp_id, $params)))
			return;

		$params = unserialize(stripslashes($params));
		$res = sql_param_query('SELECT mpt_top, mpt_bottom FROM sb_menu_path_temps WHERE mpt_id=?d', $temp_id);

		if (!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_MENU_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}
		list($mpt_top, $mpt_bottom) = $res[0];

		require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';

		$cat_params = array();
		$cat_params['ids'] = $params['ids'];
		$cat_params['from'] = 1;
		$cat_params['to'] = '';
	    $cat_params['show_closed'] = isset($params['show_closed']) ? $params['show_closed'] : 0;
	    $cat_params['show_hidden'] = isset($params['show_hidden']) ? $params['show_hidden'] : 0;
	    $cat_params['page'] = 'pl_forum';
		$cat_params['menu_temp_id'] = $temp_id;

		$forum_pages = array();
		$forum_pages['categs_page'] = isset($params['categs_page']) ? $params['categs_page'] : '';
		$forum_pages['themes_page'] = isset($params['themes_page']) ? $params['themes_page'] : '';
		$forum_pages['messages_page'] = $_SERVER['PHP_SELF'];

		$num_items = 10;
		$num = 1;
		$cat_temp = -1;

		$result = fCategs_Show_Menu_Path($cat_temp, $cat_params, 'pl_forum', 'pl_forum', 'forum', $num, $num_items, $forum_pages);

		if ($result != '')
		{
			$result = $mpt_top.preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result).$mpt_bottom;
		}

		$GLOBALS['sbCache']->save('pl_forum', $result);
	}

	/**
	 * Вывод информации о теме
	 */
	function fForum_Parse($temp, &$categs_temps, $id, $lang = 'ru')
	{
		if (trim($temp) == '')
			return '';

		// вытаскиваем пользовательские поля
		$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_forum"');

		$users_fields = array();
		$users_fields_select_sql = '';

		$categs_fields = array();
		$categs_sql_fields = array();


		$tags = array();
		//	формируем SQL-запрос для пользовательских полей
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

		$users_values = array();
		$res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, cat_closed, cat_left, cat_right
									FROM sb_categs WHERE cat_id = ?d', $id);

		if($res_cat)
		{
			$parent_cat = sql_param_query('SELECT cat_id, cat_title, cat_url FROM sb_categs WHERE
									cat_ident="pl_forum"
									AND cat_level != 0
									AND cat_left < ?d
									AND cat_right > ?d', $res_cat[0][7], $res_cat[0][8]);

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

		$values = array();
		$values[] = $id;
		$values[] = isset($categs[$id]['url']) ? $categs[$id]['url'] : '' ;			//  URL
		$values[] = isset($categs[$id]['title']) ? $categs[$id]['title'] : '' ;     //  THEME_TITLE
		$values[] = sb_parse_date(time(), $categs_temps['t_date_val'], $lang);		//  DATE
		$values[] = isset($categs[$id]['fields']['cat_subject_description']) ? $categs[$id]['fields']['cat_subject_description'] : '';	//  DESCRIPTION
		$values[] = isset($categs[$id]['fields']['cat_image']) ? $categs[$id]['fields']['cat_image'] : '' ;						//  ICON
		$values[] = isset($categs[$id]['fields']['cat_subject_main']) ? $categs[$id]['fields']['cat_subject_main'] : '';		//  ATTACH
		$values[] = isset($parent_cat[0][1]) ? $parent_cat[0][1]: '';      		//  CAT_TITLE
		$values[] = isset($parent_cat[0][0]) ? $parent_cat[0][0]: '';     		//  CAT_ID
		$values[] = isset($parent_cat[0][2]) ? $parent_cat[0][2]: '';	        //  CAT_URL
		$values[] = isset($parent_cat[1][1]) ? $parent_cat[1][1]: '';      		//  SUBCAT_TITLE
		$values[] = isset($parent_cat[1][0]) ? $parent_cat[1][0]: '';     		//  SUBCAT_ID
		$values[] = isset($parent_cat[1][2]) ? $parent_cat[1][2]: '';	        //  SUBCAT_URL
		$cat_values = array();
		$num_cat_fields = count($categs_sql_fields);

		if ($num_cat_fields > 0)
		{
			if (count($cat_values) == 0)
			{
				foreach ($categs_sql_fields as $cat_field)
				{
					if (isset($categs[$id]['fields'][$cat_field]))
						$cat_values[] = $categs[$id]['fields'][$cat_field];
					else
						$cat_values[] = null;
				}
	        	@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
				$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $categs_temps, array(), array(), $lang, '', '_val');
			}
			$users_values = array_merge($users_values, $cat_values);
		}

		$tags = array_merge(array('{ID}',
								  '{URL}',
								  '{THEME_TITLE}',
								  '{DATE}',
								  '{DESCRIPTION}',
								  '{ICON}',
								  '{ATTACH}',
								  '{CAT_TITLE}',
								  '{CAT_ID}',
								  '{CAT_URL}',
								  '{SUBCAT_TITLE}',
								  '{SUBCAT_ID}',
								  '{SUBCAT_URL}'), $tags);
		if ($users_values)
			$values = array_merge($values, $users_values);

		return str_replace($tags, $values, $temp);
	}

	/**
	 * Выводит информацию о сообщении
	 */
	function fForum_Msg_Parse($temp, &$fields_temps, $id, $lang = 'ru', $categs_temps = array())
	{
		if (trim($temp) == '')
			return '';

		// вытаскиваем пользовательские поля
		$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_forum"');

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
						$users_fields_select_sql .= ', f.user_f_'.$value['id'];

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

		$res = sql_param_query('SELECT f.f_text, f.f_date, f.f_file_name, f.f_ip, f.f_author, f.f_email, c.cat_title, c.cat_id, c.cat_url, c.cat_left, c.cat_right, su.su_forum_nick, su.su_login, su.su_email
								'.$users_fields_select_sql.'
								FROM sb_forum f LEFT JOIN sb_site_users su ON su.su_id = f.f_user_id, sb_categs c, sb_catlinks l
								WHERE c.cat_ident=? AND
								l.link_el_id = f.f_id AND l.link_cat_id = c.cat_id AND f_id=?d', 'pl_forum', $id);
		if (!$res)
		{
			return '';
		}

		list ($f_text, $f_date, $f_file_name, $f_ip, $f_author, $f_email, $cat_title, $cat_id, $cat_url, $cat_left, $cat_right, $su_forum_nick, $su_login, $su_email) = $res[0];

		$cat_url = urlencode($cat_url);

		if(isset($cat_left) && $cat_left != '' && isset($cat_right) && $cat_right != '')
		{
			$parent_cat = sql_param_query('SELECT cat_id, cat_title, cat_url FROM sb_categs WHERE
									cat_ident=?
									AND cat_level != 0
									AND cat_left < ?d
									AND cat_right > ?d', 'pl_forum', $cat_left, $cat_right);
		}

		$values = array();
		$values[] = $id;
		$values[] = ($f_date != '' && $f_date != 0 && isset($fields_temps['t_date_val']) && trim($fields_temps['t_date_val']) != '' ) ? sb_parse_date($f_date, $fields_temps['t_date_val'], $lang) : '';

		if((isset($su_forum_nick) && $su_forum_nick != '') || (isset($su_login) && $su_login != ''))
		{
			$values[] = (isset($fields_temps['t_author_val']) && trim($fields_temps['t_author_val']) != '' ) ? str_replace('{VALUE}', ($su_forum_nick != '' ? $su_forum_nick : $su_login), $fields_temps['t_author_val']) : '';
		}
		else
		{
			$values[] = ($f_author != '' && isset($fields_temps['t_author_val']) && trim($fields_temps['t_author_val']) != '' ) ? str_replace('{VALUE}', $f_author, $fields_temps['t_author_val']) : '';
		}

		if(isset($su_email) && $su_email != '')
		{
			$values[] = (isset($fields_temps['t_email_val']) && trim($fields_temps['t_email_val']) != '' ) ? str_replace('{VALUE}', $su_email, $fields_temps['t_email_val']) : '';
		}
		else
		{
			$values[] = ($f_email != '' && isset($fields_temps['t_email_val']) && trim($fields_temps['t_email_val']) != '' ) ? str_replace('{VALUE}', $f_email, $fields_temps['t_email_val']) : '';
		}

	    $values[] = ($f_text != '' && isset($fields_temps['t_text_val']) && trim($fields_temps['t_text_val']) != '' ) ? str_replace('{VALUE}', sbProgParseBBCodes($f_text, '', ''), $fields_temps['t_text_val']) : '';
	    $values[] = ($f_file_name != '' && isset($fields_temps['t_file_val']) && trim($fields_temps['t_file_val']) != '' ) ? str_replace('{VALUE}', str_replace('|', ', ', $f_file_name), $fields_temps['t_file_val']) : '';
		$values[] = ($f_ip != '' ? $f_ip : '');
		$values[] = ($cat_title != '') ? $cat_title : '';
	    $values[] = ($cat_id != '') ? $cat_id : '';
	    $values[] = ($cat_url != '') ? $cat_url : '';
   		$values[] = isset($parent_cat[1][1]) ? $parent_cat[1][1]: '';      		//  SUBCAT_TITLE
		$values[] = isset($parent_cat[1][0]) ? $parent_cat[1][0]: '';     		//  SUBCAT_ID
		$values[] = isset($parent_cat[1][2]) ? urlencode($parent_cat[1][2]): '';	        //  SUBCAT_URL
		$values[] = isset($parent_cat[0][1]) ? $parent_cat[0][1]: '';      		//  CAT_TITLE
		$values[] = isset($parent_cat[0][0]) ? $parent_cat[0][0]: '';     		//  CAT_ID
		$values[] = isset($parent_cat[0][2]) ? $parent_cat[0][2]: '';	        //  CAT_URL

 		$users_values = array();
		$num_fields = count($res[0]);

		if ($num_fields > 14)
		{
			for ($i = 14; $i < $num_fields; $i++)
			{
	            $users_values[] = $res[0][$i];
	        }

	        @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	        $users_values = sbLayout::parsePluginFields($users_fields, $users_values, $fields_temps, array(), array(), $lang, '', '_val');
	    }

	    $res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count, cat_closed
	                                FROM sb_categs WHERE cat_id IN (?a)', array($cat_id));

	    if ($res_cat)
	    {
	        foreach ($res_cat as $value)
	        {
	            $categs = array();
	            $categs['fields'] = unserialize($value[3]);
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
					if (isset($categs['fields'][$cat_field]))
						$cat_values[] = $categs['fields'][$cat_field];
					else
	                    $cat_values[] = null;
	            }

				@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
				$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $categs_temps, array(), array(), $lang, '', '_val');
			}

			$users_values = array_merge($users_values, $cat_values);
		}

		$tags = array_merge(array('{ID}',
								'{DATE}',
								'{AUTHOR}',
								'{EMAIL}',
								'{TEXT}',
								'{FILE}',
								'{IP_ADDRESS}',
								'{THEME_TITLE}',
								'{THEME_ID}',
								'{THEME_URL}',
								'{SUBCAT_TITLE}',
								'{SUBCAT_ID}',
								'{SUBCAT_URL}',
								'{CAT_TITLE}',
								'{CAT_ID}',
								'{CAT_URL}'), $tags);

		if ($users_values)
			$values = array_merge($values, $users_values);

		return str_replace($tags, $values, $temp);
	}

	/**
	 *
	 * Функция выводит форму добавления тем, а так же осуществляет сам процесс добавления тем.
	 *
	 */
	function fForum_Form_Themes($el_id, $temp_id, $params, $tag_id)
	{
		if (!isset($_POST['t_theme_name']))
		{
//			просто вывод формы, данные пока не пришли
			if ($GLOBALS['sbCache']->check('pl_forum', $tag_id, array($el_id, $temp_id, $params)))
				return;
		}
		//$res = sql_query('SELECT sftf_title, sftf_lang, sftf_text, sftf_categs_temps, sftf_fields_temps, sftf_messages
		//						FROM sb_forum_form_theme WHERE sftf_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_forum_form_theme', $temp_id);
		if (!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}

		list($sftf_title, $sftf_lang, $sftf_text, $sftf_categs_temps, $sftf_fields_temps, $sftf_messages) = $res[0];

		if (!isset($_POST['t_theme_name']) && trim($sftf_text) == '')
		{
//			вывод формы
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}

		$params = unserialize(stripslashes($params));
		$sftf_categs_temps = unserialize($sftf_categs_temps);
		$sftf_fields_temps = unserialize($sftf_fields_temps);

		$sftf_categs_temps['date_temps'] = (isset($sftf_categs_temps['date_temps']) ? $sftf_categs_temps['date_temps'] : '');

		$sftf_messages = unserialize($sftf_messages);

		$tags = array();
		$values = array();
		$file = array();

		$messages = '';
		$fields_message = '';

		$msg_theme_email = $msg_theme_author = $msg_theme_text = $t_captcha = $t_description = $t_theme_name = $t_icon = '' ;
		$msg_theme_glued = $t_attach_theme = '0' ;

		$error = false;
		$users_error = false;
		$users_msg_error = false;

		$notify_id = 0;
		$notify_cat_id = 0;
		$notify_sub_id = 0;

		if(isset($_SESSION['sbAuth']))
		{
			$u_id = $_SESSION['sbAuth']->getUserId();

			$res = sql_param_query('SELECT su_login, su_name, su_email, su_forum_nick FROM sb_site_users WHERE su_id=?d', $u_id);
			if($res)
			{
				list($su_login, $su_name, $su_email, $su_forum_nick) = $res[0];
			}
		}
		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		if(isset($_POST['add_theme']) || isset($_POST['t_theme_name']))
		{
			$t_icon = (isset($_FILES['t_icon']) && is_uploaded_file($_FILES['t_icon']['tmp_name']) ? $_FILES['t_icon']['name'] : '' );
			$t_theme_name = isset($_POST['t_theme_name']) ? $_POST['t_theme_name'] : '' ;
			$t_description = isset($_POST['t_description']) ? $_POST['t_description'] : '' ;
			$t_attach_theme = isset($_POST['t_attach_theme']) ? $_POST['t_attach_theme'] : '0' ;
			$t_captcha = isset($_POST['t_captcha']) ? $_POST['t_captcha'] : '' ;
			$t_subcat_notify_user = isset($_POST['t_subcat_notify_user']) && $_POST['t_subcat_notify_user'] == 1 ? 1 : 0;
			$t_cat_notify_user = isset($_POST['t_cat_notify_user']) && $_POST['t_cat_notify_user'] == 1 ? 1 : 0;
			$msg_theme_text = isset($_POST['msg_theme_text']) ? $_POST['msg_theme_text'] : '';
			$msg_theme_author = isset($_POST['msg_theme_author']) && $_POST['msg_theme_author'] != '' ? $_POST['msg_theme_author'] : '';
			$msg_theme_email = isset($_POST['msg_theme_email']) && $_POST['msg_theme_email'] != '' ? $_POST['msg_theme_email'] : '';
			$msg_theme_glued = isset($_POST['msg_theme_glued']) && $_POST['msg_theme_glued'] == 1 ? 1 : 0;
			$msg_theme_notify_user = isset($_POST['msg_theme_notify_user']) && $_POST['msg_theme_notify_user'] == 1 ? 1 : 0;

			$message_tags = array('{DATE}', '{THEME_TITLE}', '{DESCRIPTION}', '{ICON}');
			$message_values = array(sb_parse_date(time(), $sftf_categs_temps['t_date_val'], $sftf_lang), $t_theme_name, $t_description, $t_icon);

			// проверка данных и сохранение
			if(isset($sftf_categs_temps['description_need']) && $sftf_categs_temps['description_need'] == 1 && $t_description == '')
			{
				$error = true;

				$tags = array_merge($tags, array('{F_DESCRIPTION_SELECT_START}', '{F_DESCRIPTION_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}

			if((isset($sftf_categs_temps['theme_icon_need']) && $sftf_categs_temps['theme_icon_need'] == 1 && !is_uploaded_file($_FILES['t_icon']['tmp_name'])))
			{
				$error = true;

				$tags = array_merge($tags, array('{F_ICON_SELECT_START}', '{F_ICON_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}

			$theme_length = sbPlugins::getSetting('sb_forum_theme_max_length');
			if($t_theme_name == '')
			{
				$error = true;

				$tags = array_merge($tags, array('{F_THEME_TITLE_SELECT_START}', '{F_THEME_TITLE_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}
			elseif($t_theme_name != '' && $theme_length < sb_strlen($t_theme_name))
			{
				$error = true;

				$tags = array_merge($tags, array('{F_THEME_TITLE_SELECT_START}', '{F_THEME_TITLE_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$messages .= isset($sftf_messages['err_large_length_theme']) ? str_replace(array_merge($message_tags, array('{THEME_LENGTH}')), array_merge($message_values, array($theme_length)), $sftf_messages['err_large_length_theme']) : '';
			}

			// проверяем код каптчи
			if (sb_strpos($sftf_text, '{CAPTCHA}') !== false || sb_strpos($sftf_text, '{CAPTCHA_IMG}') !== false)
			{
				if(!sbProgCheckTuring('t_captcha', 't_captcha_hash'))
				{
					$error = true;

					$tags = array_merge($tags, array('{F_CAPTCHA_SELECT_START}', '{F_CAPTCHA_SELECT_END}'));
					$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

					$messages .= isset($sftf_messages['err_captcha_code']) ? str_replace($message_tags, $message_values, $sftf_messages['err_captcha_code']) : '' ;
				}
			}

	        if(sbPlugins::getSetting('sb_static_urls') == 1 && isset($_GET['forum_sid']) && $_GET['forum_sid'] != '')
	        {
				$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_forum"', $_GET['forum_sid']);
				if($res)
				{
					$_GET['pl_forum_sub'] = $res[0][0];
				}
				else
				{
					$_GET['pl_forum_sub'] = $_GET['forum_sid'];
				}
			}

	        if(isset($_GET['pl_forum_sub']) && $_GET['pl_forum_sub'] != '')
			{
				$cat_id = $_GET['pl_forum_sub'];
	        }
	        else
			{
				$cat_id = intval($params['id']);
	        }

			// проверяем права на добавление
			$closed_cat = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id = ?d', $cat_id);
			if($closed_cat)
			{
				$cat_id = sbAuth::checkRights(array($cat_id), array($cat_id), 'pl_forum_create');
				if(count($cat_id) < 1)
				{
					$error = true;
					$messages .= isset($sftf_messages['err_not_have_rights_add']) ? str_replace($message_tags, $message_values, $sftf_messages['err_not_have_rights_add']) : '' ;
				}
				$cat_id = isset($cat_id[0]) ? $cat_id[0] : 0;
			}

			$layout = new sbLayout();
			$cat_fields = array();
			$cat_fields = $layout->checkPluginInputFields('pl_forum', $users_error, $sftf_categs_temps, '', '', '', true, $sftf_categs_temps['date_temps']);

			if ($users_error)
			{
				foreach ($cat_fields as $f_name => $f_array)
				{
					$f_error = $f_array['error'];
					$f_tag = $f_array['tag'];

					$tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
					$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

					switch($f_error)
					{
						case 2:
							$messages .= isset($sftf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $sftf_messages['err_save_file']) : '';
							break;

						case 3:
							$messages .= isset($sftf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $sftf_messages['err_type_file']) : '';
							break;

						case 4:
							$messages .= isset($sftf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sftf_messages['err_size_too_large']) : '';
							break;

						case 5:
							$messages .= isset($sftf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sftf_messages['err_img_size']) : '';
							break;

						default:
							$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
							break;
					}
				}
			}

			if(isset($_FILES['msg_theme_file']))
			{
				foreach($_FILES['msg_theme_file']['tmp_name'] as $key => $value)
				{
					if(is_uploaded_file($_FILES['msg_theme_file']['tmp_name'][$key]))
					{
						$file[] = $_FILES['msg_theme_file']['name'][$key];
					}
				}

				$file_size = sbPlugins::getSetting('sb_files_max_upload_size');
				$file_types = sbPlugins::getSetting('sb_forum_allow_ext');

				foreach($file as $key => $value)
				{
					if($_FILES['msg_theme_file']['size'][$key] > $file_size)
					{
						$error = true;

						$tags = array_merge($tags, array('{F_MSG_ATTACH_FILE_SELECT_START}', '{F_MSG_ATTACH_FILE_SELECT_END}'));
						$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

						$messages .= isset($sftf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES['msg_theme_file']['name'][$key]) ? $_FILES['msg_theme_file']['name'][$key] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sftf_messages['err_size_too_large']) : '';
					}

					if($file_types != '')
					{
						$ext_pos = strrpos($_FILES['msg_theme_file']['name'][$key], '.');
						$ext = substr($_FILES['msg_theme_file']['name'][$key], ($ext_pos+1));

						if(strpos($file_types, $ext) === false)
						{
							$error = true;

							$tags = array_merge($tags, array('{F_MSG_ATTACH_FILE_SELECT_START}', '{F_MSG_ATTACH_FILE_SELECT_END}'));
							$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

							$messages .= isset($sftf_messages['err_type_file']) ? sb_str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_TYPE}')), array_merge($message_values, array($_FILES['msg_theme_file']['name'][$key], $file_types)), $sftf_messages['err_type_file']) : '';
						}
					}
				}
			}

			if(isset($sftf_categs_temps['msg_author_need']) && $sftf_categs_temps['msg_author_need'] == 1 && $msg_theme_author == '')
			{
				$error = true;

				$tags = array_merge($tags, array('{F_MSG_AUTHOR_SELECT_START}', '{F_MSG_AUTHOR_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}

			if(isset($sftf_categs_temps['msg_email_need']) && $sftf_categs_temps['msg_email_need'] == 1 && $msg_theme_email == '' || (($msg_theme_email != '' && !preg_match('/^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/is'.SB_PREG_MOD, $msg_theme_email))))
			{
				$error = true;

				$tags = array_merge($tags, array('{F_MSG_EMAIL_SELECT_START}', '{F_MSG_EMAIL_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}

			if(isset($sftf_categs_temps['msg_file_need']) && $sftf_categs_temps['msg_file_need'] == 1 && count($file) == 0)
			{
				$error = true;

				$tags = array_merge($tags, array('{F_MSG_ATTACH_FILE_SELECT_START}', '{F_MSG_ATTACH_FILE_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}

			if($msg_theme_text == '' && isset($sftf_categs_temps['msg_text_need']) && $sftf_categs_temps['msg_text_need'] == 1 ||
			 ((count($file) > 0 || trim($msg_theme_email) != '' || trim($msg_theme_author) != '' || $msg_theme_glued == 1)) && trim($msg_theme_text) == '')
			{
				$error = true;

				$tags = array_merge($tags, array('{F_MSG_TEXT_SELECT_START}', '{F_MSG_TEXT_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
			}

			$msg_legth = sbPlugins::getSetting('sb_forum_msg_max_length');
			if($msg_theme_text != '' && strlen($msg_theme_text) > $msg_legth)
			{
				$error = true;

				$tags = array_merge($tags, array('{F_MSG_TEXT_SELECT_START}', '{F_MSG_TEXT_SELECT_END}'));
				$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

				$messages .= isset($sftf_messages['err_msg_length_too_long']) ? str_replace(array_merge($message_tags, array('{MESSAGE_LENGTH}')), array_merge($message_values, array($msg_legth)), $sftf_messages['err_msg_length_too_long']) : '';
			}

			$row = array();
			$row = $layout->checkPluginInputFields('pl_forum', $users_msg_error, $sftf_categs_temps);

			if ($users_msg_error)
			{
				foreach ($row as $f_name => $f_array)
				{
					$f_error = $f_array['error'];
					$f_tag = $f_array['tag'];

					$tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
					$values = array_merge($values, array($sftf_categs_temps['select_start'], $sftf_categs_temps['select_end']));

					switch($f_error)
					{
						case 2:
							$messages .= isset($sftf_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $sftf_messages['err_save_file']) : '';
							break;

						case 3:
							$messages .= isset($sftf_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $sftf_messages['err_type_file']) : '';
							break;

						case 4:
							$messages .= isset($sftf_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sftf_messages['err_size_too_large']) : '';
							break;

						case 5:
							$messages .= isset($sftf_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sftf_messages['err_img_size']) : '';
							break;

						default:
							$fields_message = isset($sftf_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_necessary_field']) : '';
							break;
					}
				}
			}
//			достаем родительский подраздел и раздел. Чтобы узнать подписан ли кто-нибудь на них.
			$res = sql_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left >= c.cat_left
							AND c2.cat_right <= c.cat_right
							AND c.cat_ident=?
							AND c2.cat_ident = ?
							AND c2.cat_id = ?d
							AND c.cat_level != 0
							AND c2.cat_level != 0
							ORDER BY c.cat_left', 'pl_forum', 'pl_forum', $cat_id);

			$cat_ids = array();
			$cat_ids[] = $cat_id;
			$theme_cat_id = 0;
			$theme_sub_id = 0;

			if($res)
			{
				if(isset($res[0][0]))
				{
					$theme_cat_id = $res[0][0];
					$cat_ids[] = $theme_cat_id;
				}

				if(isset($res[1][0]))
				{
					$theme_sub_id = $res[1][0];
					$cat_ids[] = $theme_sub_id;
				}
			}

			$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id = ?d', $theme_cat_id);
			if($res)
			{
				$tmp_theme_cat_id = sbAuth::checkRights(array($theme_cat_id), array($theme_cat_id), 'pl_forum_create');
				if(count($tmp_theme_cat_id) < 1)
				{
					$error = true;
					$messages .= isset($sftf_messages['err_not_have_rights_add']) ? str_replace($message_tags, $message_values, $sftf_messages['err_not_have_rights_add']) : '' ;
				}
			}

			$notify_res = sql_param_query('SELECT sfm_email FROM sb_forum_maillist WHERE sfm_theme_id IN (?a)', $cat_ids);
			$emails = array();
			if($notify_res)
			{
				foreach($notify_res as $key => $value)
				{
					$emails[] = $value[0];
				}
				$emails = array_unique($emails);
			}

//			если нужно уведомлять пользователя об ответах на раздел
			if ($t_cat_notify_user == 1 && !$error)
			{
				if(isset($_SESSION['sbAuth']) && $msg_theme_email == '')
				{
					$msg_theme_email = $_SESSION['sbAuth']->getUserEmail();
				}

				$exist_row = 0;

				if($msg_theme_email != '' && $theme_cat_id > 0)
				{
					$res = sql_query('SELECT sfm_id FROM sb_forum_maillist WHERE sfm_theme_id = ?d AND sfm_email = ?', $theme_cat_id, $msg_theme_email);
					if(!$res)
					{
						sql_query('INSERT INTO sb_forum_maillist (sfm_theme_id, sfm_user_id, sfm_email) VALUES
									(?d, ?d, ?) ', $theme_cat_id, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), $msg_theme_email);
						$notify_cat_id = sql_insert_id();
					}
					else
					{
						$exist_row = 1;
					}
				}

				if($exist_row == 0 && ($notify_cat_id == 0 || $msg_theme_email == ''))
				{
					$error = true;
					$messages .= isset($sftf_messages['err_notify_user_cat']) ? str_replace($message_tags, $message_values, $sftf_messages['err_notify_user_cat']) : '' ;
				}
			}
//          если нужно уведомлять пользователя об ответах на подраздел
			if($t_subcat_notify_user == 1 && !$error)
			{
				if(isset($_SESSION['sbAuth']) && $msg_theme_email == '')
				{
					$msg_theme_email = $_SESSION['sbAuth']->getUserEmail();
				}
				$exist_row = 0;

				if($msg_theme_email != '' && $theme_sub_id > 0)
				{
					$res = sql_param_query('SELECT sfm_id FROM sb_forum_maillist WHERE sfm_theme_id = ?d AND sfm_email = ?', $theme_sub_id, $msg_theme_email);
					if(!$res)
					{
						sql_param_query('INSERT INTO sb_forum_maillist (sfm_theme_id, sfm_user_id, sfm_email) VALUES
								(?d, ?d, ?) ', $theme_sub_id, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), $msg_theme_email);

						$notify_sub_id = sql_insert_id();
					}
					else
					{
						$exist_row = 1;
					}
				}

				if($exist_row == 0 && ($notify_sub_id == 0 || $msg_theme_email == ''))
				{
					$error = true;
					$messages .= isset($sftf_messages['err_notify_user_subcat']) ? str_replace($message_tags, $message_values, $sftf_messages['err_notify_user_subcat']) : '' ;
				}
			}
		}

		$error = $error || $users_error || $users_msg_error;

		$icon_path = '';
		if(isset($_FILES['t_icon']['tmp_name']) && is_uploaded_file($_FILES['t_icon']['tmp_name']))
		{
			$icon_path = sbPlugins::getSetting('sb_forum_icon_path');
			if(trim($icon_path) == '')
			{
				$icon_path = '/forum_icons';
			}
			elseif(sb_substr($icon_path, 0, 1) != '/')
			{
				$icon_path = '/'.$icon_path;
			}

			if(sb_substr($icon_path, -1, 1) == '/')
			{
				$icon_path = sb_substr($icon_path, 0, -1);
			}
			$GLOBALS['sbVfs']->mLocal = true;

			if(!$GLOBALS['sbVfs']->exists(SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.$icon_path))
			{
				$GLOBALS['sbVfs']->mkdir(SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.$icon_path);
			}

			$dst = SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.$icon_path.'/'.$_FILES['t_icon']['name'];
			$GLOBALS['sbVfs']->move_uploaded_file($_FILES['t_icon']['tmp_name'], $dst);
			$GLOBALS['sbVfs']->mLocal = false;
		}

		$cat_fields['cat_subject_description'] = $t_description;
		$cat_fields['cat_image'] = isset($_FILES['t_icon']['name']) && $_FILES['t_icon']['name'] != '' ? SB_DOMAIN.'/upload'.$icon_path.'/'.$_FILES['t_icon']['name'] : '' ;
		$cat_fields['cat_subject_main'] = $t_attach_theme;
		$cat_fields['creator_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1;

		if(isset($t_theme_name) && $t_theme_name != '' && !$error)
		{
//			проверяем данные для добавления сообщения и формируем массив $row
			if(isset($_POST['msg_theme_text']) && $msg_theme_text != '')
			{
				$files_path = '';
				$paths = '';
				$names = '';
				if(count($file) > 0 && !$error)
				{
					$files_path = sbPlugins::getSetting('sb_forum_file_path');
					if(trim($files_path) == '')
					{
						$files_path = 'forum_files';
					}

					if(sb_substr($files_path, -1, 1) == '/')
					{
						$files_path = sb_substr($files_path, 0, -1);
					}

					if(sb_substr($files_path, 0, 1) == '/')
					{
						$files_path = sb_substr($files_path, 1);
					}

					$GLOBALS['sbVfs']->mLocal = true;

					if(!$GLOBALS['sbVfs']->exists(SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.'/'.$files_path))
					{
						$GLOBALS['sbVfs']->mkdir(SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.'/'.$files_path);
					}

					foreach($file as $key => $value)
					{
						$name =  str_replace('.', '_', microtime(true)).sb_strtolat($value);

						$dst = SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.'/'.$files_path.'/'.$name;
						$GLOBALS['sbVfs']->move_uploaded_file($_FILES['msg_theme_file']['tmp_name'][$key], $dst);

						$paths .= SB_DOMAIN.'/upload/'.$files_path.'/'.$name.'|';
						$names .= $name.'|';
					}

					$GLOBALS['sbVfs']->mLocal = false;
				}

				$row['f_text'] = nl2br($msg_theme_text);
				$row['f_date'] = time();
	    		$row['f_file'] = $paths;
	            $row['f_file_name'] = $names;
				$row['f_user_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : null;
	            $row['f_ip'] = sbAuth::getIp();
	            $row['f_show'] = 1;
				$row['f_author'] = $msg_theme_author;
				$row['f_email'] = $msg_theme_email;
				$row['f_glued'] = $msg_theme_glued;

				if(sb_strlen($msg_theme_text) > 50)
				{
					$msg_theme_text = sb_substr($msg_theme_text, 0, 50).'...';
				}
			}
			$t_url = sb_check_chpu('', $t_theme_name, $t_theme_name, 'sb_categs', 'cat_url', 'cat_id');
			if(isset($params['premod_themes']) && $params['premod_themes'] == 1)
			{
				$premod_themes = 0;
			}
			else
			{
				$premod_themes = 1;
			}

		    require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
		    $tree = new sbTree('pl_forum');

	        $fields = array();
	        $fields['cat_title'] = $t_theme_name;
	        $fields['cat_closed'] = 0;
	        $fields['cat_rubrik'] = $premod_themes;
	        $fields['cat_rights'] = '';
	        $fields['cat_url'] = $t_url;
	        $fields['cat_fields'] = serialize($cat_fields);

			$theme_id = $tree->insertNode($cat_id, $fields);

//			если нужно уведомлять пользователя об ответах на тему
			if($msg_theme_notify_user == 1 && !$error)
			{
				if(isset($_SESSION['sbAuth']) && $msg_theme_email == '')
				{
					$msg_theme_email = $_SESSION['sbAuth']->getUserEmail();
				}
				$exist_row = 0;

				if($msg_theme_email != '' && $theme_id > 0)
				{
					$res = sql_query('SELECT sfm_id FROM sb_forum_maillist WHERE sfm_theme_id = ?d AND sfm_email = ?', $theme_id, $msg_theme_email);
					if(!$res)
					{
						sql_query('INSERT INTO sb_forum_maillist (sfm_theme_id, sfm_user_id, sfm_email) VALUES
								(?d, ?d, ?) ', $theme_id, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), $msg_theme_email);
						$notify_id = sql_insert_id();
					}
					else
					{
						$exist_row = 1;
					}
				}

				if($exist_row == 0 && ($notify_id == 0 || $msg_theme_email == ''))
				{
					$tree->removeNode($theme_id);
					$error = true;
					$messages .= isset($sftf_messages['err_notify_user']) ? str_replace($message_tags, $message_values, $sftf_messages['err_notify_user']) : '' ;
				}
			}

			if(isset($theme_id) && $theme_id == 0 || !isset($theme_id))
			{
				$error = true;
				$messages .= isset($sftf_messages['err_add_theme']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_theme']) : '';
				sb_add_system_message(sprintf(KERNEL_PROG_PL_FORUM_FORM_ERR_ADD_THEME, $t_theme_name), SB_MSG_WARNING);
			}

			$f_id = 0;
//			добавляем сообщение
			if(isset($_POST['msg_theme_text']) && $msg_theme_text != '' && !$error)
	    	{
				if (!($f_id = sbProgAddElement('sb_forum', 'f_id', $row, array($theme_id))))
				{
					$error = true;
					$messages .= isset($sftf_messages['err_add_msg']) ? str_replace($message_tags, $message_values, $sftf_messages['err_add_msg']) : '';

					sb_add_system_message(sprintf(KERNEL_PROG_PL_FORUM_FORM_ERR_ADD_MSG, sbProgParseBBCodes($msg_theme_text, '', '', false)), SB_MSG_WARNING);
				}
				else
				{
					sb_add_system_message(sprintf(KERNEL_PROG_PL_FORUM_FORM_ADD_MSG, sbProgParseBBCodes($msg_theme_text, '', '', false)), SB_MSG_INFORMATION);
				}
			}

	    	$select_str = 'SELECT c.cat_id, c.cat_fields, c.cat_left, c.cat_right,';
			if($theme_id && !$error)
			{
				$select_str .= '(
			                        SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left > c.cat_left AND c3.cat_right < c.cat_right
			                        AND c3.cat_ident = "pl_forum" AND c3.cat_level = 2
								) as count_subcats,
		                        (
			                        SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left > c.cat_left AND c3.cat_right < c.cat_right
			                        AND c3.cat_ident = "pl_forum" AND c3.cat_level = 2 AND c3.cat_rubrik = 1
								) as subcats_without_hidden,
								(
			                        SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left > c.cat_left AND c3.cat_right < c.cat_right
			                        AND c3.cat_ident = "pl_forum" AND c3.cat_level = 3
		                        ) as count_themes,
								(
			                        SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left > c.cat_left AND c3.cat_right < c.cat_right
			                        AND c3.cat_ident = "pl_forum" AND c3.cat_level = 3 AND c3.cat_rubrik = 1
		                        ) as themes_without_hidden';
			}
			else
			{
				$select_str .= '"",  "", "", ""';
			}

			if($f_id > 0 && !$error)
			{
				$select_str .= ', (
							        SELECT COUNT(*)
					        		FROM sb_forum fo
					            	LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
					            	LEFT JOIN sb_categs categ ON categ.cat_id = li.link_cat_id
					                WHERE fo.f_show = 1
					                AND categ.cat_ident = "pl_forum"
					                AND categ.cat_left >= c.cat_left
					                AND categ.cat_right <= c.cat_right
								) as count_messages,
								(
									SELECT COUNT(*)
					        		FROM sb_forum fo
					            	LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
					            	LEFT JOIN sb_categs categ ON categ.cat_id = li.link_cat_id
					                WHERE fo.f_show = 1
					                AND categ.cat_ident = "pl_forum"
					                AND categ.cat_left >= c.cat_left
					                AND categ.cat_right <= c.cat_right
					                AND categ.cat_rubrik = 1
								) as mesgs_without_hid_themes';
			}
			else
			{
				$select_str .= ', "",  ""';
			}

			if(($theme_id || $f_id > 0) && !$error)
			{
//				устанавливаем значения кол-во подразделов, кол-во активных подразделов, кол-во тем, кол-во активных тем, кол-во сообщений.
				$res = sql_query($select_str.'
					            FROM sb_categs AS c, sb_categs AS c2
								WHERE c2.cat_left >= c.cat_left
								AND c2.cat_right <= c.cat_right
								AND c.cat_ident="pl_forum"
								AND c2.cat_ident = "pl_forum"
								AND c2.cat_id = ?d
								AND c.cat_level != 0
								ORDER BY c.cat_left', $theme_id);
				if($res)
				{
					foreach($res as $key => $value)
	                {
						list($_cat_id, $cat_fields, $cat_left, $cat_right, $count_subcats, $subcats_without_hidden, $count_themes,
							$themes_without_hidden, $_count_messages, $mesgs_without_hid_themes) = $value;

	                    $cat_fields = unserialize($cat_fields);
	                    $cat_fields['count_subcats'] = $count_subcats;
	                    $cat_fields['cats_without_hidden'] = $subcats_without_hidden;
	                    $cat_fields['count_themes'] = $count_themes;
	                    $cat_fields['themes_without_hidden'] = $themes_without_hidden;

	                    if($_count_messages != '')
	                    {
				        	$cat_fields['count_msgs'] = $_count_messages;
	                    }
				        if($mesgs_without_hid_themes != '')
				        {
				        	$cat_fields['mesgs_without_hid_themes'] = $mesgs_without_hid_themes;
				        }
						sql_query('UPDATE sb_categs SET cat_fields=? WHERE cat_id=?d', serialize($cat_fields), $_cat_id);
					}
				}
			}

			if(!$error)
			{
				require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
				$mail = new sbMail();

				$type = sbPlugins::getSetting('sb_letters_type');

				if($notify_res && count($emails) > 0 && isset($f_id) && $f_id > 0)
				{
					$tmp_email_subj = fForum_Msg_Parse($sftf_categs_temps['user_subj'], $sftf_fields_temps, $f_id, $sftf_lang, $sftf_categs_temps);

					//чистим код от инъекций
					$tmp_email_subj = sb_clean_string($tmp_email_subj);

					ob_start();
					eval(' ?>'.$tmp_email_subj.'<?php ');
					$tmp_email_subj = trim(ob_get_clean());

					$tmp_email_text = fForum_Msg_Parse($sftf_categs_temps['user_text'], $sftf_fields_temps, $f_id, $sftf_lang, $sftf_categs_temps);

					//чистим код от инъекций
					$tmp_email_text = sb_clean_string($tmp_email_text);

					ob_start();
			        eval(' ?>'.$tmp_email_text.'<?php ');
					$tmp_email_text = trim(ob_get_clean());

					foreach($emails as $value)
					{
						$unsub_page = isset($params['unsub_page']) ? $params['unsub_page'].'?forum_unsub_eid='. md5('*##'.$value.'##*').'&forum_unsub=' : '';

						$unsub_tags = array('{UNSUB_LINK}', '{UNSUB_SUB_LINK}', '{UNSUB_CAT_LINK}');
						$unsub_vals = array($cat_id != '' && $unsub_page != '' ? $unsub_page.md5('*##'.$cat_id.'##*') : '',
											$theme_sub_id != '' && $unsub_page != '' ? $unsub_page.md5('*##'.$theme_sub_id.'##*') : '',
											$theme_cat_id != '' && $unsub_page != '' ? $unsub_page.md5('*##'.$theme_cat_id.'##*') : '');

						$email_subj = sb_str_replace($unsub_tags, $unsub_vals, $tmp_email_subj);
						$email_text = sb_str_replace($unsub_tags, $unsub_vals, $tmp_email_text);

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
			            }

						$mail->send(array($value), false);
					}
				}

				// отправляем письма и делаем переадресацию
                $mod_emails = getForumModerators($cat_ids, $params);

				if (count($mod_emails) > 0)
				{
					$email_subj = fForum_Parse($sftf_categs_temps['admin_subj'], $sftf_categs_temps, $theme_id, $sftf_lang);

					//чистим код от инъекций
					$email_subj = sb_clean_string($email_subj);

					ob_start();
					eval(' ?>'.$email_subj.'<?php ');
					$email_subj = trim(ob_get_clean());

					$email_text = fForum_Parse($sftf_categs_temps['admin_text'], $sftf_categs_temps, $theme_id, $sftf_lang);

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

				$get_str = '';
				if(sbPlugins::getSetting('sb_static_urls') != 1)
				{
					foreach ($_GET as $key => $value)
				    {
				    	if($key == 'pl_forum_cat' || $key == 'pl_forum_cat_sel' || $key == 'pl_forum_sub' || $key == 'pl_forum_theme')
		    			{
		    				$get_str .= $key.'='.intval($value).'&';
		    			}
					}
				}

				if (isset($params['page']) && trim($params['page']) != '')
		        {
					//  если указанна переадресация на страницу отличающуюся от текущей, то не добавляем параметров. на другой странице они нам не нужны.
					if(sb_strpos($params['page'], SB_COOKIE_DOMAIN.$_SERVER['PHP_SELF']) === false)
					{
						$get_str = '';
					}
					header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'t_id='.$theme_id.($get_str != '' ? '&'.trim($get_str, '&') : '')));
				}
		        else
		        {
					header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ?  '?'.$_SERVER['QUERY_STRING'].'&t_id='.$theme_id.($get_str != '' ? '&'.trim($get_str, '&') : '') : '?t_id='.$theme_id.($get_str != '' ? '&'.trim($get_str, '&') : ''))));
				}
				exit(0);
	    	}
	    	else
	    	{
				$layout->deletePluginFieldsFiles();
	    	}
		}
		elseif(isset($t_theme_name) && $t_theme_name != '' && $error)
		{
			$layout->deletePluginFieldsFiles();
		}

		if(isset($_GET['t_id']) && !$error)
		{
			$messages .= fForum_Parse($sftf_messages['success_add_theme'], $sftf_categs_temps, $_GET['t_id'], $sftf_lang);
		}

		$messages .= $fields_message;
		$tags = array_merge($tags, array('{MESSAGES}',
								'{ACTION}',
								'{THEME_TITLE}',
								'{DESCRIPTION}',
								'{ICON}',
								'{THEME_GLUE}',
								'{CAPTCHA}',
								'{CAPTCHA_IMG}',
								'{AUTHOR}',
								'{EMAIL}',
								'{TEXT}',
								'{ATTACH_FILE}',
								'{GLUED_THEME_MSG}',
								'{NOTIFY_THEME_MSG}',
								'{NOTIFY_SUBCAT_MSG}',
								'{NOTIFY_CAT_MSG}'));

//  	вывод полей формы input
		$values[] = $messages;         // {MESSAGES}

		$get_str = '';
		if(sbPlugins::getSetting('sb_static_urls') != 1)
		{
			foreach ($_GET as $key => $value)
		    {
				if($key == 'pl_forum_cat' || $key == 'pl_forum_cat_sel' || $key == 'pl_forum_sub' || $key == 'pl_forum_theme')
		    	{
					$get_str .= $key.'='.intval($value).'&';
		    	}
			}
		}
		$values[] = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].($get_str != '' ? '&'.trim($get_str, '&') : '') : ($get_str != '' ? '?'.trim($get_str, '&') : ''));  // ACTION

		$values[] = (isset($sftf_categs_temps['theme_title']) && $sftf_categs_temps['theme_title'] != '') ? str_replace('{VALUE}', $t_theme_name, $sftf_categs_temps['theme_title']) : '';  // {THEME_TITLE}
		$values[] = (isset($sftf_categs_temps['description']) && $sftf_categs_temps['description'] != '') ? str_replace('{VALUE}', $t_description, $sftf_categs_temps['description']) : '';  // {DESCRIPTION}
		$values[] = (isset($sftf_categs_temps['theme_icon']) && $sftf_categs_temps['theme_icon'] != '') ? str_replace('{VALUE}', $t_icon, $sftf_categs_temps['theme_icon']) : '';      // {ICON}
		$values[] = (isset($sftf_categs_temps['attach_theme']) && $sftf_categs_temps['attach_theme'] != '') ? str_replace('{VALUE}', (isset($t_attach_theme) && $t_attach_theme == 1 ? 'checked="checked"' : '' ), $sftf_categs_temps['attach_theme']) : '';  //  {THEME_GLUE}

		// Вывод КАПЧИ
		if ((sb_strpos($sftf_text, '{CAPTCHA}') !== false || sb_strpos($sftf_text, '{CAPTCHA_IMG}') !== false) &&
			isset($sftf_categs_temps['captcha']) && trim($sftf_categs_temps['captcha']) != '' &&
			isset($sftf_categs_temps['captcha_img']) && trim($sftf_categs_temps['captcha_img']) != '')
		{
			$turing = sbProgGetTuring();
			if ($turing)
			{
				$values[] = $sftf_categs_temps['captcha'];
				$values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $sftf_categs_temps['captcha_img']);
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

		if(isset($_SESSION['sbAuth']) && ($msg_theme_author == '' || $msg_theme_email == ''))
		{
			if($su_forum_nick != '')
				$msg_theme_author = $su_forum_nick;
			elseif($su_login != '')
				$msg_theme_author = $su_login;
			elseif($su_name != '')
				$msg_theme_author = $su_name;

			if($su_email != '')
				$msg_theme_email = $su_email;
		}

		$values[] = (isset($sftf_categs_temps['msg_author']) && $sftf_categs_temps['msg_author'] != '') ? str_replace('{VALUE}', $msg_theme_author, $sftf_categs_temps['msg_author']) : '';    // AUTHOR
		$values[] = (isset($sftf_categs_temps['msg_email']) && $sftf_categs_temps['msg_email'] != '') ? str_replace('{VALUE}', $msg_theme_email, $sftf_categs_temps['msg_email']) : '';    	   // EMAIL
		$values[] = (isset($sftf_categs_temps['msg_text']) && $sftf_categs_temps['msg_text'] != '') ? str_replace('{VALUE}', preg_replace('/\<br\s*\/\s*\>/', '', $msg_theme_text), $sftf_categs_temps['msg_text']) : '';			   // TEXT
		$values[] = (isset($sftf_categs_temps['attach_file']) && $sftf_categs_temps['attach_file'] != '') ? str_replace('{VALUE}', '', $sftf_categs_temps['attach_file']) : '';				   // ATTACH_FILE
		$values[] = (isset($sftf_categs_temps['glued_msg']) && $sftf_categs_temps['glued_msg'] != '') ? str_replace('{VALUE}', (isset($msg_theme_glued) && $msg_theme_glued == 1 ? 'checked="checked"' : '' ), $sftf_categs_temps['glued_msg']) : '';
		$values[] = (isset($sftf_categs_temps['notify_user']) && $sftf_categs_temps['notify_user'] != '') ? str_replace('{VALUE}', (isset($msg_theme_notify_user) && $msg_theme_notify_user == 1 ? 'checked="checked"' : ''), $sftf_categs_temps['notify_user']) : '';
		$values[] = (isset($sftf_categs_temps['notify_user_subcat']) && $sftf_categs_temps['notify_user_subcat'] != '') ? str_replace('{VALUE}', (isset($t_subcat_notify_user) && $t_subcat_notify_user == 1 ? 'checked="checked"' : ''), $sftf_categs_temps['notify_user_subcat']) : '';
		$values[] = (isset($sftf_categs_temps['notify_user_cat']) && $sftf_categs_temps['notify_user_cat'] != '') ? str_replace('{VALUE}', (isset($t_cat_notify_user) && $t_cat_notify_user == 1 ? 'checked="checked"' : ''), $sftf_categs_temps['notify_user_cat']) : '';

		sbLayout::parsePluginInputFields('pl_forum', $sftf_categs_temps,  $sftf_categs_temps['t_date'], $tags, $values, -1, '', '', array(), array(), true);
		sbLayout::parsePluginInputFields('pl_forum', $sftf_fields_temps,  $sftf_categs_temps['t_date'], $tags, $values);

		$result = '';
		$result = str_replace($tags, $values, $sftf_text);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		//чистим код от инъекций
		$result = sb_clean_string($result);

		if (!isset($_POST['theme_name']) || trim($_POST['theme_name']) == '')
			$GLOBALS['sbCache']->save('pl_forum', $result);
		else
			eval(' ?>'.$result.'<?php ');
	}

	/**
	 * Функция выводит форму добавления сообщений, а так же осуществляет сам процесс добавления сообщений
	 *
	 */
	function fForum_Form_Msg($el_id, $temp_id, $params, $tag_id)
	{
        $is_closed = false;
        $tmp = array();
        parse_str($_SERVER['QUERY_STRING'], $tmp);

        if (isset($tmp['pl_forum_theme']))
        {
            $res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id=?d', $tmp['pl_forum_theme']);
            if ($res && $res[0][0] != '')
            {
                $cat_fields = unserialize($res[0][0]);
                if (isset($cat_fields['cat_theme_closed']) && $cat_fields['cat_theme_closed'] == 1)
                {
                    $is_closed = true;
                }
                unset($cat_fields);
            }
        }

		if (!isset($_POST['t_msg_text']))
		{
//			просто вывод формы, данные пока не пришли
			if ($GLOBALS['sbCache']->check('pl_forum', $tag_id, array($el_id, $temp_id, $params)))
				return;
		}
		//$res = sql_param_query('SELECT sffm_lang, sffm_title, sffm_text, sffm_fields_temps, sffm_categs_temps, sffm_messages
		//		FROM sb_forum_form_msg WHERE sffm_id=?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_forum_form_msg', $temp_id);

		if (!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}

        list($sffm_lang, $sffm_title, $sffm_text, $sffm_fields_temps, $sffm_categs_temps, $sffm_messages) = $res[0];

		if (!isset($_POST['t_msg_text']) && trim($sffm_text) == '')
		{
//			вывод формы
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}

		$params = unserialize(stripslashes($params));
		$sffm_fields_temps = unserialize($sffm_fields_temps);
		$sffm_categs_temps = unserialize($sffm_categs_temps);
		$sffm_messages = unserialize($sffm_messages);

        //Если тема закрыта, то выводим сообщение
        if($is_closed)
        {
            eval(' ?>'.(isset($sffm_messages['msg_closed_theme'])? $sffm_messages['msg_closed_theme'] : '').'<?php ');
            return;
        }

		$tags = array();
		$values = array();

		$messages = '';
		$fields_message = '';
		$upload_mess = '';
		$upload_mess_type = '';

		$text = $author = $email = $glued_msg = $captcha = '' ;
		$file = array();
		$notify_id = 0;
		$notify_sub_id = 0;
		$notify_cat_id = 0;

		if(isset($_SESSION['sbAuth']))
		{
			$u_id = $_SESSION['sbAuth']->getUserId();
			$res = sql_param_query('SELECT su_login, su_name, su_email, su_forum_nick FROM sb_site_users WHERE su_id=?d', $u_id);
			if($res)
			{
				list($su_login, $su_name, $su_email, $su_forum_nick) = $res[0];
			}
		}
		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		$error = $users_error = false;
		if(isset($_POST['t_msg_text']))
		{
			$text =  isset($_POST['t_msg_text']) ? $_POST['t_msg_text'] : '';
			if(isset($_POST['t_msg_author']) && $_POST['t_msg_author'] != '')
			{
				$author = $_POST['t_msg_author'];
			}
			else
			{
				if(isset($su_forum_nick) && $su_forum_nick != '')
					$author = $su_forum_nick;
				elseif(isset($su_login) && $su_login != '')
					$author = $su_login;
				elseif(isset($su_name) && $su_name != '')
					$author = $su_name;
			}
			if(isset($_POST['t_msg_email']) && $_POST['t_msg_email'] != '')
			{
				$email = $_POST['t_msg_email'];
			}
			else
			{
				if(isset($su_email) && $su_email != '')
					$email = $su_email;
			}

			$glued_msg = isset($_POST['t_glued_msg']) && $_POST['t_glued_msg'] == 1 ? $_POST['t_glued_msg'] : 0;
			$notify_msg = isset($_POST['t_notify_msg']) && $_POST['t_notify_msg'] == 1 ? $_POST['t_notify_msg'] : 0;
			$notify_subcat = isset($_POST['t_notify_subcat']) && $_POST['t_notify_subcat'] == 1 ? $_POST['t_notify_subcat'] : 0;
			$notify_cat = isset($_POST['t_notify_cat']) && $_POST['t_notify_cat'] == 1 ? $_POST['t_notify_cat'] : 0;
			$captcha = isset($_POST['t_msg_captcha']) ? $_POST['t_msg_captcha'] : '';

			$message_tags = array('{DATE}', '{AUTHOR}', '{EMAIL}', '{TEXT}', '{FILE}');
			$message_values = array(sb_parse_date(time(), $sffm_fields_temps['t_date'], $sffm_lang), $author, $email, $text, count($file) > 0 ? implode(',', $file) : '' );
			if(isset($_FILES['t_file']))
			{
				foreach($_FILES['t_file']['tmp_name'] as $key => $value)
				{
					if(is_uploaded_file($_FILES['t_file']['tmp_name'][$key]))
					{
						$file[] = $_FILES['t_file']['name'][$key];
					}
				}

				$file_size = sbPlugins::getSetting('sb_files_max_upload_size');
				$file_types = sbPlugins::getSetting('sb_forum_allow_ext');
				foreach($file as $key => $value)
				{
					if($_FILES['t_file']['size'][$key] > $file_size)
					{
						$error = true;

						$tags = array_merge($tags, array('{F_ATTACH_FILE_SELECT_START}', '{F_ATTACH_FILE_SELECT_END}'));
						$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

						$upload_mess .= isset($sffm_messages['err_size_too_large']) ? str_replace($message_tags, $message_values, $sffm_messages['err_size_too_large']) : '';
					}

					if($file_types != '')
					{
						$ext_pos = strrpos($_FILES['t_file']['name'][$key], '.');
						$ext = substr($_FILES['t_file']['name'][$key], ($ext_pos+1));

						if(strpos($file_types, $ext) === false)
						{
							$error = true;

							$tags = array_merge($tags, array('{F_ATTACH_FILE_SELECT_START}', '{F_ATTACH_FILE_SELECT_END}'));
							$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

							$upload_mess_type .= isset($sffm_messages['err_type_file']) ? sb_str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_TYPE}')), array_merge($message_values, array($_FILES['t_file']['name'][$key], $file_types)), $sffm_messages['err_type_file']) : '';
						}
					}
				}
			}
			$messages .= $upload_mess.$upload_mess_type;

			// проверка данных и сохранение
			if(isset($sffm_fields_temps['author_need']) && $sffm_fields_temps['author_need'] == 1 && $author == '')
			{
				$error = true;

				$tags = array_merge($tags, array('{F_AUTHOR_SELECT_START}', '{F_AUTHOR_SELECT_END}'));
				$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

				$fields_message = isset($sffm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sffm_messages['err_add_necessary_field']) : '';
			}

			if(isset($sffm_fields_temps['email_need']) && $sffm_fields_temps['email_need'] == 1 && $email == '' || (($email != '' && !preg_match('/^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/is'.SB_PREG_MOD, $email))))
			{
				$error = true;

				$tags = array_merge($tags, array('{F_EMAIL_SELECT_START}', '{F_EMAIL_SELECT_END}'));
				$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

				$fields_message = isset($sffm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sffm_messages['err_add_necessary_field']) : '';
			}

			if(isset($sffm_fields_temps['file_need']) && $sffm_fields_temps['file_need'] == 1 && count($file) == 0)
			{
				$error = true;

				$tags = array_merge($tags, array('{F_ATTACH_FILE_SELECT_START}', '{F_ATTACH_FILE_SELECT_END}'));
				$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

				$fields_message = isset($sffm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sffm_messages['err_add_necessary_field']) : '';
			}

			if($text == '')
			{
				$error = true;

				$tags = array_merge($tags, array('{F_TEXT_SELECT_START}', '{F_TEXT_SELECT_END}'));
				$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

				$fields_message = isset($sffm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sffm_messages['err_add_necessary_field']) : '';
			}

			$msg_legth = sbPlugins::getSetting('sb_forum_msg_max_length');
			if($text != '' && strlen($text) > $msg_legth)
			{
				$error = true;

				$tags = array_merge($tags, array('{F_TEXT_SELECT_START}', '{F_TEXT_SELECT_END}'));
				$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

				$messages .= isset($sffm_messages['msg_length_is_to_long']) ? str_replace(array_merge($message_tags, array('{MSG_LENGTH}')), array_merge($message_values, array($msg_legth)), $sffm_messages['msg_length_is_to_long']) : '';
			}

			// проверяем код каптчи
			if (sb_strpos($sffm_text, '{CAPTCHA}') !== false || sb_strpos($sffm_text, '{CAPTCHA_IMG}') !== false)
			{
				if(!sbProgCheckTuring('t_msg_captcha', 't_msg_captcha_hash'))
				{
					$error = true;

					$tags = array_merge($tags, array('{F_CAPTCHA_SELECT_START}', '{F_CAPTCHA_SELECT_END}'));
					$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

					$messages .= isset($sffm_messages['err_captcha_code']) ? str_replace($message_tags, $message_values, $sffm_messages['err_captcha_code']) : '' ;
				}
			}

	        if(sbPlugins::getSetting('sb_static_urls') == 1 && isset($_GET['forum_sid']) && $_GET['forum_sid'] != '')
	        {
				$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_forum" AND cat_level="3"', $_GET['forum_sid']);
				if($res)
				{
					$_GET['pl_forum_theme'] = $res[0][0];
				}
				else
				{
					$_GET['pl_forum_theme'] = $_GET['forum_sid'];
				}
			}

			if(isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] != '')
			{
				$cat_id = $_GET['pl_forum_theme'];
			}
			else
			{
				$cat_id = intval($params['id']);
			}

			// проверяем права на добавление
			$closed_cat = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id = ?d', $cat_id);
			if($closed_cat)
			{
				$cat_id = sbAuth::checkRights(array($cat_id), array($cat_id), 'pl_forum_write');
				if(count($cat_id) < 1)
				{
					$error = true;
					$messages .= isset($sffm_messages['err_not_have_rights_add']) ? str_replace($message_tags, $message_values, $sffm_messages['err_not_have_rights_add']) : '' ;
				}
				$cat_id = $cat_id[0];
			}

			$layout = new sbLayout();

			$row = array();
			$row = $layout->checkPluginInputFields('pl_forum', $users_error, $sffm_fields_temps, -1, '', '', false, $sffm_fields_temps['date_temps']);
			if ($users_error)
			{
				foreach ($row as $f_name => $f_array)
				{
					$f_error = $f_array['error'];
					$f_tag = $f_array['tag'];

					$tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
					$values = array_merge($values, array($sffm_fields_temps['select_start'], $sffm_fields_temps['select_end']));

					switch($f_error)
					{
						case 2:
							$messages .= isset($sffm_messages['err_save_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $sffm_messages['err_save_file']) : '';
							break;

						case 3:
							$messages .= isset($sffm_messages['err_type_file']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILES_TYPES}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $sffm_messages['err_type_file']) : '';
							break;

						case 4:
							$messages .= isset($sffm_messages['err_size_too_large']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sffm_messages['err_size_too_large']) : '';
							break;

						case 5:
							$messages .= isset($sffm_messages['err_img_size']) ? str_replace(array_merge($message_tags, array('{FILE_NAME}', '{IMG_WIDTH}', '{IMG_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sffm_messages['err_img_size']) : '';
							break;

						default:
							$fields_message = isset($sffm_messages['err_add_necessary_field']) ? str_replace($message_tags, $message_values, $sffm_messages['err_add_necessary_field']) : '';
							break;
					}
				}
			}

//			достаем родительский подраздел и раздел. Чтобы узнать подписан ли кто-нибудь на них.
			$res = sql_param_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left > c.cat_left
							AND c2.cat_right < c.cat_right
							AND c.cat_ident=?
							AND c2.cat_ident = ?
							AND c2.cat_id = ?d
							AND c.cat_level != 0
							AND c2.cat_level != 0
							ORDER BY c.cat_left', 'pl_forum', 'pl_forum', $cat_id);

			$cat_ids = array();
			$cat_ids[] = $cat_id;
			$theme_sub_id = 0;
			$theme_cat_id = 0;

			if($res)
			{
				if(isset($res[0][0]))
				{
					$theme_cat_id = $res[0][0];
					$cat_ids[] = $theme_cat_id;
				}

				if(isset($res[1][0]))
				{
					$theme_sub_id = $res[1][0];
					$cat_ids[] = $theme_sub_id;
				}
			}

			$notify_res = sql_param_query('SELECT sfm_email FROM sb_forum_maillist WHERE sfm_theme_id IN (?a)', $cat_ids);
			$emails = array();
			if($notify_res)
			{
				foreach($notify_res as $key => $value)
				{
					$emails[] = $value[0];
				}
				$emails = array_unique($emails);
			}

//          если нужно уведомлять пользователя об ответах на тему
			if($notify_cat == 1 && !$error)
			{
				$exist_row = 0;
				if($email != '' && $theme_cat_id > 0)
				{
					$res = sql_param_query('SELECT sfm_id FROM sb_forum_maillist WHERE sfm_theme_id = ?d AND sfm_email = ?', $theme_cat_id, $email);
					if(!$res)
					{
						sql_param_query('INSERT INTO sb_forum_maillist (sfm_theme_id, sfm_user_id, sfm_email) VALUES
								(?d, ?d, ?) ', $theme_cat_id, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), $email);

						$notify_cat_id = sql_insert_id();
					}
					else
					{
						$exist_row = 1;
					}
				}

				if($exist_row == 0 && ($notify_cat_id == 0 || $email == ''))
				{
					$error = true;
					$messages .= isset($sffm_messages['err_notify_user_cat']) ? str_replace($message_tags, $message_values, $sffm_messages['err_notify_user_cat']) : '' ;
				}
			}

//          если нужно уведомлять пользователя об ответах на тему
			if($notify_subcat == 1 && !$error)
			{
				$exist_row = 0;
				if($email != '' && $theme_sub_id > 0)
				{
					$res = sql_param_query('SELECT sfm_id FROM sb_forum_maillist WHERE sfm_theme_id = ?d AND sfm_email = ?', $theme_sub_id, $email);
					if(!$res)
					{
						sql_param_query('INSERT INTO sb_forum_maillist (sfm_theme_id, sfm_user_id, sfm_email) VALUES
								(?d, ?d, ?) ', $theme_sub_id, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), $email);

						$notify_sub_id = sql_insert_id();
					}
					else
					{
						$exist_row = 1;
					}
				}

				if($exist_row == 0 && ($notify_sub_id == 0 || $email == ''))
				{
					$error = true;
					$messages .= isset($sffm_messages['err_notify_user_subcat']) ? str_replace($message_tags, $message_values, $sffm_messages['err_notify_user_subcat']) : '' ;
				}
			}

//          если нужно уведомлять пользователя об ответах на тему
			if($notify_msg == 1 && !$error)
			{
				$exist_row = 0;
				if($email != '' && $cat_id > 0)
				{
					$res = sql_param_query('SELECT sfm_id FROM sb_forum_maillist WHERE sfm_theme_id = ?d AND sfm_email = ?', $cat_id, $email);
					if(!$res)
					{
						sql_param_query('INSERT INTO sb_forum_maillist (sfm_theme_id, sfm_user_id, sfm_email) VALUES
								(?d, ?d, ?) ', $cat_id, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), $email);

						$notify_id = sql_insert_id();
					}
					else
					{
						$exist_row = 1;
					}
				}

				if($exist_row == 0 && ($notify_id == 0 || $email == ''))
				{
					$error = true;
					$messages .= isset($sffm_messages['err_notify_user']) ? str_replace($message_tags, $message_values, $sffm_messages['err_notify_user']) : '' ;
				}
			}
		}

		$error = $error || $users_error;
		$files_path = '';
		$paths = '';
		$names = '';

		if(count($file) > 0 && !$error)
		{
			$files_path = sbPlugins::getSetting('sb_forum_file_path');
			if(trim($files_path) == '')
			{
				$files_path = 'forum_files';
			}

			if(sb_substr($files_path, -1, 1) == '/')
			{
				$files_path = sb_substr($files_path, 0, -1);
			}

			if(sb_substr($files_path, 0, 1) == '/')
			{
				$files_path = sb_substr($files_path, 1);
			}
			$GLOBALS['sbVfs']->mLocal = true;

			if(!$GLOBALS['sbVfs']->exists(SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.'/'.$files_path))
			{
				$GLOBALS['sbVfs']->mkdir(SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.'/'.$files_path);
			}

			foreach($file as $key => $value)
			{
				$name =  str_replace('.', '_', microtime(true)).sb_strtolat($value);

				$dst = SB_BASEDIR.SB_SITE_USER_UPLOAD_PATH.'/'.$files_path.'/'.$name;
				$GLOBALS['sbVfs']->move_uploaded_file($_FILES['t_file']['tmp_name'][$key], $dst);

				$paths .= SB_DOMAIN.'/upload/'.$files_path.'/'.$name.'|';
				$names .= $name.'|';
			}
			$GLOBALS['sbVfs']->mLocal = false;
		}

		if (isset($_POST['t_msg_text']) && $text != '' && !$error)
		{
			if(isset($params['premod_msg']) && $params['premod_msg'] == 1)
			{
				$premod_msg = 0;
			}
			else
			{
				$premod_msg = 1;
			}

			$row['f_text'] = nl2br($text);
			$row['f_date'] = time();
    		$row['f_file'] = $paths;
            $row['f_file_name'] = $names;
			$row['f_user_id'] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : null;
            $row['f_ip'] = sbAuth::getIp();
            $row['f_show'] = $premod_msg;
			$row['f_author'] = $author;
			$row['f_email'] = $email;
            $row['f_glued'] = $glued_msg;

			if(sb_strlen($text) > 50)
			{
				$text = sb_substr($text, 0, 50).'...';
			}

			if (!($f_id = sbProgAddElement('sb_forum', 'f_id', $row, array($cat_id))))
			{
				$error = true;
				$messages .= isset($sffm_messages['msg_error_send_message']) ? str_replace($message_tags, $message_values, $sffm_messages['msg_error_send_message']) : '';

				sb_add_system_message(sprintf(KERNEL_PROG_PL_FORUM_FORM_ERR_ADD_MSG, sbProgParseBBCodes($text, '', '', false)), SB_MSG_WARNING);
			}
			else
			{
				sb_add_system_message(sprintf(KERNEL_PROG_PL_FORUM_FORM_ADD_MSG, sbProgParseBBCodes($text, '', '', false)), SB_MSG_INFORMATION);
				//	устанавливаем значения кол-во сообщений, кол-во сообщений без скрытых тем для разделов подразделов и тем.
				$res = sql_query('SELECT c.cat_id, c.cat_fields, c.cat_left, c.cat_right,
							(
						        SELECT COUNT(*)
				        		FROM sb_forum fo
				            	LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
				            	LEFT JOIN sb_categs categ ON categ.cat_id = li.link_cat_id
				                WHERE fo.f_show = 1
				                AND categ.cat_ident = "pl_forum"
				                AND categ.cat_left >= c.cat_left
				                AND categ.cat_right <= c.cat_right
							) as count_messages,
							(
						        SELECT COUNT(*)
				        		FROM sb_forum fo
				            	LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
				            	LEFT JOIN sb_categs categ ON categ.cat_id = li.link_cat_id
				                WHERE fo.f_show = 1
				                AND categ.cat_ident = "pl_forum"
				                AND categ.cat_left >= c.cat_left
				                AND categ.cat_right <= c.cat_right
				                AND categ.cat_rubrik = 1
							) as mesgs_without_hid_themes

				            FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left >= c.cat_left
							AND c2.cat_right <= c.cat_right
							AND c.cat_ident="pl_forum"
							AND c2.cat_ident = "pl_forum"
							AND c2.cat_id = ?d
							AND c.cat_level != 0
							ORDER BY c.cat_left', $cat_id);
			    if($res)
			    {
			        foreach($res as $key => $value)
			        {
			            list($_cat_id, $_cat_fields, $_cat_left, $_cat_right, $_count_messages, $mesgs_without_hid_themes) = $value;

			            $_cat_fields = unserialize($_cat_fields);
			            $_cat_fields['count_msgs'] = $_count_messages;
			            $_cat_fields['mesgs_without_hid_themes'] = $mesgs_without_hid_themes;

			            sql_query('UPDATE sb_categs SET cat_fields=? WHERE cat_id=?d', serialize($_cat_fields), $_cat_id);
			        }
				}
			}

			if(!$error)
			{
				$admin_email = str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, sbPlugins::getSetting('sb_admin_email'));
				$type = sbPlugins::getSetting('sb_letters_type');

				if($notify_res)
				{
					require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
					$mail = new sbMail();

					$tmp_email_subj = fForum_Msg_Parse($sffm_fields_temps['user_subj'], $sffm_fields_temps, $f_id, $sffm_lang, $sffm_categs_temps);

					//чистим код от инъекций
					$tmp_email_subj = sb_clean_string($tmp_email_subj);

					ob_start();
					eval(' ?>'.$tmp_email_subj.'<?php ');
					$tmp_email_subj = trim(ob_get_clean());

					$tmp_email_text = fForum_Msg_Parse($sffm_fields_temps['user_text'], $sffm_fields_temps, $f_id, $sffm_lang, $sffm_categs_temps);

					//чистим код от инъекций
					$tmp_email_text = sb_clean_string($tmp_email_text);

					ob_start();
		            eval(' ?>'.$tmp_email_text.'<?php ');
		            $tmp_email_text = trim(ob_get_clean());

					$unsub_tags = array('{UNSUB_LINK}', '{UNSUB_SUB_LINK}', '{UNSUB_CAT_LINK}');
					foreach($emails as $value)
					{
						$unsub_page = isset($params['unsub_page']) ? $params['unsub_page'].'?forum_unsub_eid='. md5('*##'.$value.'##*').'&forum_unsub=' : '';

						$unsub_vals = array($cat_id != '' && $unsub_page != '' ? $unsub_page.md5('*##'.$cat_id.'##*') : '',
											$theme_sub_id != '' && $unsub_page != '' ? $unsub_page.md5('*##'.$theme_sub_id.'##*') : '',
											$theme_cat_id != '' && $unsub_page != '' ? $unsub_page.md5('*##'.$theme_cat_id.'##*') : '');

						$email_subj = sb_str_replace($unsub_tags, $unsub_vals, $tmp_email_subj);
						$email_text = sb_str_replace($unsub_tags, $unsub_vals, $tmp_email_text);

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
							$mail->send(array($value), false);
			            }
					}
				}

				$mod_emails = getForumModerators($cat_ids, $params);

				// отправляем письма и делаем переадресацию
        	    if (count($mod_emails) > 0)
	            {
					require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
					$mail = new sbMail();

					$email_subj = fForum_Msg_Parse($sffm_fields_temps['admin_subj'], $sffm_fields_temps, $f_id, $sffm_lang, $sffm_categs_temps);

					//чистим код от инъекций
					$email_subj = sb_clean_string($email_subj);

					ob_start();
					eval(' ?>'.$email_subj.'<?php ');
					$email_subj = trim(ob_get_clean());

					$email_text = fForum_Msg_Parse($sffm_fields_temps['admin_text'], $sffm_fields_temps, $f_id, $sffm_lang, $sffm_categs_temps);

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

				$get_str = '';
				if(sbPlugins::getSetting('sb_static_urls') != 1)
				{
					foreach ($_GET as $key => $value)
				    {
				    	if($key == 'pl_forum_cat' || $key == 'pl_forum_cat_sel' || $key == 'pl_forum_sub' || $key == 'pl_forum_theme')
		    			{
		    				$get_str .= $key.'='.intval($value).'&';
		    			}
					}
				}

		        if (isset($params['page']) && trim($params['page']) != '')
		        {
					//  если указанна переадресация на страницу отличающуюся от текущей, то не добавляем параметров. на другой странице они нам не нужны.
					if(sb_strpos($params['page'], SB_COOKIE_DOMAIN.$_SERVER['PHP_SELF']) === false)
					{
						$get_str = '';
					}

					header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'m_id='.$f_id.($get_str != '' ? '&'.trim($get_str, '&') : '')));
				}
		        else
		        {
					header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].'&m_id='.$f_id.($get_str != '' ? '&'.trim($get_str, '&') : '') : '?m_id='.$f_id.($get_str != '' ? '&'.trim($get_str, '&') : ''))));
				}
				exit (0);
			}
		}
		elseif(isset($_POST['t_msg_text']) && $error)
		{
			$layout->deletePluginFieldsFiles();
		}

		if(isset($_GET['m_id']) && !$error)
		{
			$messages .= fForum_Msg_Parse($sffm_messages['msg_sended'], $sffm_fields_temps, $_GET['m_id'], $sffm_lang, $sffm_categs_temps);
		}

		$messages .= $fields_message;
		$tags = array_merge($tags, array('{MESSAGES}',
										'{ACTION}',
										'{AUTHOR}',
										'{EMAIL}',
										'{TEXT}',
										'{ATTACH_FILE}',
										'{GLUED_MSG}',
										'{NOTIFY_USER}',
										'{NOTIFY_USER_SUBCAT}',
										'{NOTIFY_USER_CAT}',
										'{CAPTCHA}',
										'{CAPTCHA_IMG}'));

		//  вывод полей формы input
		$values[] = $messages;         // MESSAGES

		$get_str = '';
		if(sbPlugins::getSetting('sb_static_urls') != 1)
		{
			foreach ($_GET as $key => $value)
		    {
		    	if($key == 'pl_forum_cat' || $key == 'pl_forum_cat_sel' || $key == 'pl_forum_sub' || $key == 'pl_forum_theme')
		    	{
		    		$get_str .= $key.'='.intval($value).'&';
		    	}
			}
		}
		$values[] = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].($get_str != '' ? '&'.trim($get_str, '&') : '') : ($get_str != '' ? '?'.trim($get_str, '&') : ''));  // ACTION

		if($author == '')
		{
			if(isset($su_forum_nick) && $su_forum_nick != '')
				$author = $su_forum_nick;
			elseif(isset($su_login) && $su_login != '')
				$author = $su_login;
			elseif(isset($su_name) && $su_name != '')
				$author = $su_name;
		}

		$values[] = (isset($sffm_fields_temps['msg_author']) && $sffm_fields_temps['msg_author'] != '') ? str_replace('{VALUE}', $author, $sffm_fields_temps['msg_author']) : '';
		if($email == '')
		{
			if(isset($su_email) && $su_email != '')
				$email = $su_email;
		}

		$values[] = (isset($sffm_fields_temps['email']) && $sffm_fields_temps['email'] != '') ? str_replace('{VALUE}', $email, $sffm_fields_temps['email']) : '';

		$hash = md5('pl_forum_mess_h_'.(isset($_REQUEST['mess_id']) ? $_REQUEST['mess_id'] : ''));
		if(isset($_REQUEST['mess_id']) && isset($_REQUEST['mess_hash']) && $_REQUEST['mess_hash'] == $hash)
		{
			$quote_res = sql_param_query('SELECT m.f_author, m.f_date, m.f_email, m.f_text, su.su_forum_nick, su.su_login FROM sb_forum m LEFT JOIN sb_site_users su ON m.f_user_id = su.su_id WHERE f_id=?d', $_REQUEST['mess_id']);
			if($quote_res)
			{
				list($f_author, $f_date, $f_email, $f_text, $su_forum_nick, $su_login) = $quote_res[0];

				if(isset($su_forum_nick) && $su_forum_nick != '')
					$f_author = $su_forum_nick;
				elseif(isset($su_login) && $su_login != '')
					$f_author = $su_login;
				elseif ($f_author == '')
					$f_author = $f_email;

				$f_date = sb_parse_date($f_date, $sffm_fields_temps['t_date'], $sffm_lang);
				$text = '[quote'.($f_author != '' ? '='.$f_author : '').($f_date != '' ? ' date='.$f_date : '').']'.preg_replace('=<br.*?/?>=i', '', $f_text).'[/quote]';
			}
		}

		$values[] = (isset($sffm_fields_temps['msg_text']) && $sffm_fields_temps['msg_text'] != '') ? str_replace('{VALUE}', $text, $sffm_fields_temps['msg_text']) : '';
		$values[] = (isset($sffm_fields_temps['attach_file']) && $sffm_fields_temps['attach_file'] != '') ? str_replace('{VALUE}', '', $sffm_fields_temps['attach_file']) : '';
		$values[] = (isset($sffm_fields_temps['glued_msg']) && $sffm_fields_temps['glued_msg'] != '') ? str_replace('{VALUE}', (isset($glued_msg) && $glued_msg == 1 ? 'checked="checked"' : '' ), $sffm_fields_temps['glued_msg']) : '';
		$values[] = (isset($sffm_fields_temps['notify_user']) && $sffm_fields_temps['notify_user'] != '') ? str_replace('{VALUE}', (isset($notify_msg) && $notify_msg == 1 ? 'checked="checked"' : '' ), $sffm_fields_temps['notify_user']) : '';
		$values[] = (isset($sffm_fields_temps['notify_user_subcat']) && $sffm_fields_temps['notify_user_subcat'] != '') ? str_replace('{VALUE}', (isset($notify_subcat) && $notify_subcat == 1 ? 'checked="checked"' : '' ), $sffm_fields_temps['notify_user_subcat']) : '';
		$values[] = (isset($sffm_fields_temps['notify_user_cat']) && $sffm_fields_temps['notify_user_cat'] != '') ? str_replace('{VALUE}', (isset($notify_cat) && $notify_cat == 1 ? 'checked="checked"' : '' ), $sffm_fields_temps['notify_user_cat']) : '';

		// Вывод КАПЧИ
		if (isset($sffm_fields_temps['captcha']) && trim($sffm_fields_temps['captcha']) != '' &&
			isset($sffm_fields_temps['captcha_img']) && trim($sffm_fields_temps['captcha_img']) != '' &&
			(sb_strpos($sffm_text, '{CAPTCHA}') !== false || sb_strpos($sffm_text, '{CAPTCHA_IMG}') !== false))
		{
			$turing = sbProgGetTuring();
			if ($turing)
			{
				$values[] = $sffm_fields_temps['captcha'];
				$values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $sffm_fields_temps['captcha_img']);
			}
			else
			{
				$values[] = $sffm_fields_temps['captcha'];
				$values[] = '';
			}
		}
		else
		{
			$values[] = '';
			$values[] = '';
		}

		sbLayout::parsePluginInputFields('pl_forum', $sffm_fields_temps,  $sffm_fields_temps['t_date'], $tags, $values);

		$result = '';
		$result = str_replace($tags, $values, $sffm_text);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		//чистим код от инъекций
		$result = sb_clean_string($result);

		if (!isset($_POST['t_msg_text']) || trim($_POST['t_msg_text']) == '')
			$GLOBALS['sbCache']->save('pl_forum', $result);
		else
			eval(' ?>'.$result.'<?php ');
	}

	/**
	 *
	 * Вывод разделов и подразделов
	 */
	function fForum_Elem_Categ_List($el_id, $temp_id, $params, $tag_id)
	{
		if ($GLOBALS['sbCache']->check('pl_forum', $tag_id, array($el_id, $temp_id, $params)))
			return;

		$params = unserialize(stripslashes($params));
		$system_message = '';

		$hidden_cat = isset($params['hidden_cat']) && $params['hidden_cat'] == 1 ? 1 : 0;
		$hidden_subcat = isset($params['hidden_subcat']) && $params['hidden_subcat'] ? 1 : 0;
		$close_cat = isset($params['close_cat']) && $params['close_cat'] == 1 ? 1 : 0;
		$close_subcat = isset($params['close_subcat']) && $params['close_subcat'] == 1 ? 1 : 0;

//		Достаем макеты дизайна вывода разделов и подразделов форума
		//$res = sql_param_query('SELECT ftc_categs_temps, ftc_sub_categs_temps, ftc_pager_id, ftc_perpage, ftc_lang, ftc_checked,
		//						ftc_user_categs_temps, ftc_user_subcategs_temps, ftc_subjects_id
		//						FROM sb_forum_temps_categs WHERE ftc_id = ?d', $temp_id);
        $res = sbQueryCache::getTemplate('sb_forum_temps_categs', $temp_id);
		if(!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}

		list($ftc_categs_temps, $ftc_sub_categs_temps, $ftc_pager_id, $ftc_perpage, $ftc_lang, $ftc_checked, $ftc_user_categs_temps,
						$ftc_user_subcategs_temps, $ftc_subjects_id) = $res[0];

		$ftc_categs_temps = unserialize($ftc_categs_temps);
		$ftc_sub_categs_temps = unserialize($ftc_sub_categs_temps);
		$ftc_user_categs_temps = unserialize($ftc_user_categs_temps);
		$ftc_user_subcategs_temps = unserialize($ftc_user_subcategs_temps);

		if ($ftc_checked != '')
			$ftc_checked = explode(' ', $ftc_checked);
		else
			$ftc_checked = array();

//		массив сообщений форума
		$ftm_templates = array();

//      Если макет дизайна вывода тем указан то достаем макет
		if($ftc_subjects_id != -1)
		{
			//	fts_theme_form_id  ??????????   Зачем нам это поле в макетах дизайна тем и сообщений.  Проверить везде и удалить.
			//	Макет дизайна вывода тем и сообщений
			//$subject_res = sql_param_query('SELECT fts_checked, fts_categs_temps,
			//			fts_user_categs_temps, fts_perpage, fts_perpage_messages, fts_messages_id,  fts_pagelist_id, fts_lang,
			//            fts_pagelist_mess_id, fts_user_data_themes_id, fts_user_data_mess_id, fts_messages_temps
			//            FROM sb_forum_temps_subjects
			//            WHERE fts_id = ?d', $ftc_subjects_id);
            $subject_res = sbQueryCache::getTemplate('sb_forum_temps_subjects', $ftc_subjects_id);
			if(!$subject_res)
			{
				sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
				return;
			}

//			$fts_list_mess_id === $fts_pagelist_mess_id почему-то было изменено название переменной
			list($fts_checked, $fts_categs_temps, $fts_user_categs_temps, $fts_perpage, $fts_perpage_messages,
						$fts_messages_id,  $fts_pagelist_id, $fts_lang, $fts_pagelist_mess_id, $fts_user_data_themes_id,
						$fts_user_data_mess_id, $fts_messages_temps) = $subject_res[0];

			$fts_categs_temps = unserialize($fts_categs_temps);
			$fts_user_categs_temps = unserialize($fts_user_categs_temps);
			$fts_messages_temps = unserialize($fts_messages_temps);

//			Если в макетах дизайна вывода тем указан макет вывода сообщений и уведомлений
			if(intval($fts_messages_id) > 0)
			{
				$messages_res = sql_param_query('SELECT ftm_templates FROM sb_forum_temps_messages WHERE ftm_id = ?d', $fts_messages_id);
				if(!$messages_res)
				{
					sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
				}

				list($ftm_templates) = isset($messages_res[0]) ? $messages_res[0] : '';
				if($ftm_templates != '')
					$ftm_templates = unserialize($ftm_templates);
			}
		}

		$categs_sql_fields = $categs_fields = $tags3 = $tags2 = $tags = $us_tags = array();
		$checked_sql = '';

//		вытаскиваем пользовательские поля
		//$res = sql_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_forum"');
        $res = sbQueryCache::query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_forum');
		if($res && $res[0][0] != '')
		{
			@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

			$categs_fields = unserialize($res[0][0]);
			if($categs_fields == '')
			{
				$categs_fields = array();
			}

			if ($categs_fields)
			{
				foreach($categs_fields as $value)
				{
					if(isset($value['sql']) && $value['sql'] == 1)
					{
						$us_tags[] = '{'.$value['tag'].'}';
						$categs_sql_fields[] = 'user_f_'.$value['id'];

						if (in_array($value['id'], $ftc_checked))
						{
							$checked_sql .= ' AND NOT (c.cat_fields LIKE "%:\"user_f_'.$value['id'].'\";i:1;%" OR c.cat_fields LIKE "%:\"user_f_'.$value['id'].'\";s:1:\"1\";%")';
						}
					}
				}
			}
		}

		$num_cat_fields = count($categs_sql_fields);
		$pt_begin = $pt_next = $pt_previous = $pt_end = $pt_number = $pt_sel_number = $pt_page_list = $pt_delim = '';
		$pt_perstage = 0;

//		Достаем макет дизайна постраничного вывода разделов и подразделов
		if(isset($ftc_pager_id) && $ftc_pager_id > 0)
		{
			$res = sbQueryCache::getTemplate('sb_pager_temps', $ftc_pager_id);

			if(!$res)
			{
				return;
			}
			list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
		}

		@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
		$pager = new sbDBPager($tag_id, $pt_perstage, $ftc_perpage);

//		Если пользователь залогинен, то получаем его id.
		$su_id = (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1);

//      если используется ЧПУ утанавливаем значение переменных $_GET['pl_forum_cat'], $_GET['pl_forum_cat_sel'], $_GET['pl_forum_sub']
        if(sbPlugins::getSetting('sb_static_urls') == 1 && isset($_GET['forum_sid']) && $_GET['forum_sid'] != '')
        {
			$res = sql_param_query('SELECT cat_id, cat_level FROM sb_categs WHERE cat_url=? AND cat_ident="pl_forum"', $_GET['forum_sid']);
			if(!$res)
			{
				$res = sql_param_query('SELECT cat_id, cat_level FROM sb_categs WHERE cat_id=?d AND cat_ident="pl_forum"', $_GET['forum_sid']);
            }

			if(isset($res) && $res[0][1] == 1)
            {
				$_GET['pl_forum_cat'] = $res[0][0];
            }
            elseif(isset($res) && $res[0][1] == 2)
            {
            	$_GET['pl_forum_sub'] = $res[0][0];
				$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_forum"', $_GET['forum_scid']);
				if($res)
				{
					$_GET['pl_forum_cat_sel'] = $res[0][0];
				}
				else
				{
					$_GET['pl_forum_cat_sel'] = $_GET['forum_scid'];
            	}
            }
		}

		$where_str = '';
		$cat_ids = array();// массив id разделов которые буду выводиться.

//		Достаем из настроек компонента разделы, которые указаны для вывода. Но если нужно устанавливать связь с выводом разделов,
//		то они нам не нужны т.к. мы выводим только подразделы выбранного раздела(ориентируемся на выбранные разделы).
		if ((!isset($params['parent_link']) || $params['parent_link'] == 0) || (isset($params['parent_link']) && $params['parent_link'] == 1 && !isset($_GET['pl_forum_cat']) && !isset($_GET['pl_forum_cat_sel'])))
        {
			$cat_ids = explode('^', $params['ids']);
		}

		if((!isset($params['parent_link']) || $params['parent_link'] == 0) && (isset($_GET['pl_forum_cat']) || isset($_GET['pl_forum_cat_sel'])))
        {
			if(isset($_GET['pl_forum_cat']))
			{
				$cat_ids = array_merge($cat_ids, array($_GET['pl_forum_cat']));
				$where_str = ' AND (c.cat_level = 2 OR c.cat_level = 1)';
			}
			elseif(isset($_GET['pl_forum_cat_sel']))
			{
				$cat_ids = array_merge($cat_ids, array($_GET['pl_forum_cat_sel']));
				$where_str = ' AND (c.cat_level = 1 OR c.cat_level = 2 OR c.cat_level = 3)';
			}
		}	//	формируем запрос таким образом чтобы выводились только подразделы выбранных разделов
		elseif(isset($params['parent_link']) && $params['parent_link'] == 1 && (isset($_GET['pl_forum_cat']) || isset($_GET['pl_forum_cat_sel'])))
		{
			if(isset($_GET['pl_forum_cat']))
			{
				$cat_ids = array($_GET['pl_forum_cat']);
				$where_str = 'AND c.cat_level=2';
			}
			elseif(isset($_GET['pl_forum_cat_sel']))
			{
				$cat_ids = array($_GET['pl_forum_cat_sel']);
				$where_str = 'AND (c.cat_level=2 OR c.cat_level = 3)';
			}
		}
		else	//	значит только зашли на форум
		{
			if(isset($params['parent_link']) && $params['parent_link'] == 1 && !isset($_GET['pl_forum_cat']) && !isset($_GET['pl_forum_cat_sel']))
			{
				$params['parent_link'] = 0;
			}

			if(count($cat_ids) == 0)
			{
				$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_forum_empty']) ? $ftm_templates['msg_forum_empty'] : ''));    //    Нет разделов в форуме
				return;
			}
		}
		$system_message = '';

//		формируем sql строку для проверки прав текущего пользователя на разделы подразделы и темы форума.
		$closed_str = ' (r.group_ids IS NULL OR r.group_ids = ""';
		if (isset($_SESSION['sbAuth']))
		{
			foreach ($_SESSION['sbAuth']->getUserGroups() as $g_id)
			{
				$closed_str .= ' OR r.group_ids LIKE "%'.$g_id.'%"';
			}
		}
		$closed_str .= ')';

		$hidden_cats_str = '';
//		Если не стоит галочка выводить скрытые разделы.
		if($hidden_cat == 0)
		{
			$hidden_cats_str = 'c.cat_level=1';
		}

//		Если не стоит галочка выводить скрытые подразделы.
		if ($hidden_subcat == 0 && $hidden_cats_str != '')
		{
			$hidden_cats_str .= ' || c.cat_level=2 || c.cat_level=3';
		}
		elseif($hidden_subcat == 0)
		{
			$hidden_cats_str = 'c.cat_level=2 || c.cat_level=3';
		}

		$close_cats_str = '';
		if($close_cat == 0)
		{
			$close_cats_str = 'c.cat_level=1';
		}

		if($close_subcat == 0 && $close_cats_str != '')
		{
			$close_cats_str .= ' || c.cat_level=2 || c.cat_level=3';
		}
		elseif($close_subcat == 0)
		{
			$close_cats_str = 'c.cat_level=2 || c.cat_level=3';
		}

		$use_themes = false;
//		Если в макетах подразделов есть тег {THEMES} значит будут выводиться темы.
		if(sb_strpos($ftc_sub_categs_temps['sub_categ_with_sub'], '{THEMES}') !== false ||
			sb_strpos($ftc_sub_categs_temps['sub_categ_with_sub_sel'], '{THEMES}') !== false)
		{
			$use_themes = true;
		}

		$forum_total = true;
		if(!isset($params['parent_link']) || $params['parent_link'] != 1)
		{
			$level = 'c.cat_level = 1';
//			если постраничный вывод ведеться по разделам то убираем соответствующие теги из макетов подразделов
			if(strpos($ftc_sub_categs_temps['sub_top'], '{NUM_LIST}'))
			{
				$ftc_sub_categs_temps['sub_top'] = str_replace('{NUM_LIST}', '', $ftc_sub_categs_temps['sub_top']);
			}
			if(strpos($ftc_sub_categs_temps['sub_bottom'], '{NUM_LIST}'))
			{
				$ftc_sub_categs_temps['sub_bottom'] = str_replace('{NUM_LIST}', '', $ftc_sub_categs_temps['sub_bottom']);
			}
		}
		else
		{
			$level = 'c.cat_level = 2';
		}
//		???????	сделать проверку для этих запросов. если нет тегов для постраничного вывода то эти запросы не нужно т.к. одни необходимы только для
//			постраничного вывода.
		$pager_data = $pager->init($forum_total, 'SELECT DISTINCT(c.cat_id) FROM sb_categs c, sb_categs c2
							WHERE '.$level.'
									AND c2.cat_left <= c.cat_left
									AND c2.cat_right >= c.cat_right
									AND c.cat_ident="pl_forum"
									AND c2.cat_ident = "pl_forum"
									AND c2.cat_id IN (?a)

						'.($hidden_cat == 0 || $hidden_subcat == 0 ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND c3.cat_rubrik=0 AND ('.$hidden_cats_str.'))) ' : '').'
						'.($close_cat == 0 || $close_subcat == 0 ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 LEFT JOIN sb_catrights r ON r.cat_id=c3.cat_id AND r.right_ident="pl_forum_read" WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND NOT ('.$closed_str.') AND ('.$close_cats_str.'))) ' : '').'
						'.($hidden_subcat == 1 && $use_themes ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND c3.cat_rubrik=0 AND c.cat_level=3))' : '').'
						'.($close_subcat == 1 && $use_themes ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 LEFT JOIN sb_catrights r ON r.cat_id=c3.cat_id AND r.right_ident="pl_forum_read" WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND NOT ('.$closed_str.') AND c.cat_level=3))' : '').'
						'.($checked_sql != '' ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND c3.cat_level != 0 '.sb_str_replace('c.cat_', 'c3.cat_', $checked_sql).'))' : '').'
						ORDER BY c.cat_left', $cat_ids);
		if($pager_data)
		{
			$cat_ids = array();
			foreach($pager_data as $key => $value)
			{
				$cat_ids[] = $value[0];
			}
		}
		else
		{
			//	нет результата. определяем причину и выдаем ошибку.
			$res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
			if ($res)
			{
				$closed_ids = array();
				foreach ($res as $value)
				{
			    	$closed_ids[] = $value[0];
				}
				$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_forum_read');

				if(count($cat_ids) == 0)
				{
					if(isset($_GET['pl_forum_cat']))
					{
						if($su_id > 0)
						{
							$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_categ_opened_for_group_only']) ? $ftm_templates['msg_categ_opened_for_group_only'] : ''));
							return;
						}

						$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_categ_opened_for_registry_users']) ? $ftm_templates['msg_categ_opened_for_registry_users'] : ''));
						return;
					}
					elseif(isset($_GET['pl_forum_sub']))
					{
						if($su_id > 0)
						{
							$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_subcateg_opened_for_group_only']) ? $ftm_templates['msg_subcateg_opened_for_group_only'] : ''));
							return;
						}

						$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_subcateg_opened_for_registry_users']) ? $ftm_templates['msg_subcateg_opened_for_registry_users'] : ''));
						return;
					}
					else
					{
						if($su_id > 0)
						{
							$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_forum_opened_for_group_only']) ? $ftm_templates['msg_forum_opened_for_group_only'] : ''));
							return;
						}

						$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_forum_opened_for_registry_users']) ? $ftm_templates['msg_forum_opened_for_registry_users'] : ''));
						return;
					}
				}
			}

			if(!isset($_GET['pl_forum_cat']) && !isset($_GET['pl_forum_sub']))
			{
				$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_forum_empty']) ? $ftm_templates['msg_forum_empty'] : ''));
				return;
			}

			$exist_cat = sql_query('SELECT cat_id FROM sb_categs WHERE cat_id = ?d', $_GET['pl_forum_cat']);
			if(isset($_GET['pl_forum_cat']) && !$exist_cat)
			{
				$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_categ_not_found']) ? $ftm_templates['msg_categ_not_found'] : ''));
				return;
			}
			elseif(isset($_GET['pl_forum_sub']))
			{
				$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_sub_categs_not_found']) ? $ftm_templates['msg_sub_categs_not_found'] : ''));
				return;
			}
		}

//		Достаем все подразделы и темы для разделов выбранных в настройках. Запрос по сути дублируется, но это нужно для того чтобы постраничный вывод мог идти по разделам 1 либо 2 уровня, а не по всем разделам сразу.
		$res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_left, c.cat_right, c.cat_url,
						MIN(f.f_date), MAX(f.f_date)
						FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id
							LEFT JOIN sb_forum f ON f.f_id = l.link_el_id, sb_categs c2

						WHERE c2.cat_left <= c.cat_left
						AND c2.cat_right >= c.cat_right
						AND c.cat_ident="pl_forum"
						AND c2.cat_ident = "pl_forum"
						AND c2.cat_id IN (?a)

						'.(!$use_themes ? ' AND c.cat_level != 3' : '').'

		'.($hidden_cat == 0 || $hidden_subcat == 0 ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND c3.cat_rubrik=0 AND ('.$hidden_cats_str.'))) ' : '').'
		'.($close_cat == 0 || $close_subcat == 0 ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 LEFT JOIN sb_catrights r ON r.cat_id=c3.cat_id AND r.right_ident="pl_forum_read" WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND NOT ('.$closed_str.') AND ('.$close_cats_str.'))) ' : '').'
		'.($hidden_subcat == 1 && $use_themes ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND c3.cat_rubrik=0 AND c.cat_level=3))' : '').'
		'.($close_subcat == 1 && $use_themes ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 LEFT JOIN sb_catrights r ON r.cat_id=c3.cat_id AND r.right_ident="pl_forum_read" WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND NOT ('.$closed_str.') AND c.cat_level=3))' : '').'
		'.($checked_sql != '' ? ' AND (0 = (SELECT COUNT(*) FROM sb_categs c3 WHERE c3.cat_left <= c.cat_left AND c3.cat_right >= c.cat_right AND c3.cat_ident="pl_forum" AND c3.cat_level != 0 '.sb_str_replace('c.cat_', 'c3.cat_', $checked_sql).'))' : '').'
		'.$where_str.' GROUP BY c.cat_id ORDER BY c.cat_left', $cat_ids);

		$theme_ids = array();		//	массив идентификаторов тем
		$theme_creators = array();	//	массив идентификаторов авторов тем
		$forum_viewing = array();

		$parents = array();
		if($res)
		{
			foreach($res as $key => $value)
			{
				$cat_ids[] = $value[0];

				$value[3] = unserialize($value[3]);
				if($value[2] == 3)
				{
					$theme_ids[] = $value[0];
					$theme_creators[$value[0]] = (isset($value[3]['creator_id']) ? $value[3]['creator_id'] : 0);
				}
			}

			if(count($theme_ids) > 0)
			{
				$forum_view = sql_query('SELECT fv_theme_id, fv_user_id, fv_date, fv_count_views
												FROM sb_forum_viewing WHERE fv_theme_id IN (?a)', $theme_ids);
				if($forum_view)
		        {
					foreach($forum_view as $value)
		            {
						$forum_viewing[$value[0]][$value[1]]['date'] = $value[2];	// для определения того прочитанная тема или новая
						if(isset($forum_viewing[$value[0]]['fv_count_views']))
							$forum_viewing[$value[0]]['fv_count_views'] += $value[3];	// кол-во просмотров темы
						else
							$forum_viewing[$value[0]]['fv_count_views'] = $value[3];	// кол-во просмотров темы
					}
				}
			}

			if(count($theme_creators) > 0)
			{
//				достаем данные авторов тем и формируем массив ($authors)
				$user_res = sql_query('SELECT su_id, su_name, su_login, su_email, su_forum_nick FROM sb_site_users WHERE su_id IN (?a)', $theme_creators);
				$authors = array();

				if($user_res)
		        {
		            foreach($user_res as $key => $value)
		            {
		            	$theme_id = array_keys($theme_creators, $value[0]);
		            	if($theme_id)
		            	{
							foreach($theme_id as $k => $val)
							{
			                    $authors[$val]['id'] = $value[0];
			                    $authors[$val]['fio'] = $value[1];
			                    $authors[$val]['login'] = (isset($value[4]) && $value[4] != '' ? $value[4] : $value[2]);
			                    $authors[$val]['email'] = $value[3];
							}
		            	}
		            }
		        }
			}

			$count_new_themes_str = '0';
			if(strpos($ftc_categs_temps['categ_with_sub'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_categs_temps['categ_with_sub_sel'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_categs_temps['categ_without_sub'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_categs_temps['categ_without_sub_sel'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_with_sub'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_with_sub_sel'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_without_sub'], '{COUNT_NEW_THEMES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_without_sub_sel'], '{COUNT_NEW_THEMES}') !== false ||
				isset($fts_categs_temps['top']) && strpos($fts_categs_temps['top'], '{COUNT_NEW_THEMES}') !== false ||
				isset($fts_categs_temps['bottom']) && strpos($fts_categs_temps['bottom'], '{COUNT_NEW_THEMES}') !== false)
			{
				$count_new_themes_str = '(
								SELECT COUNT(DISTINCT(cate.cat_id))
								FROM sb_categs cate
								LEFT JOIN sb_catlinks lin ON lin.link_cat_id = cate.cat_id
								LEFT JOIN sb_forum foru ON foru.f_id = lin.link_el_id
								LEFT JOIN sb_forum_viewing v ON cate.cat_id = v.fv_theme_id AND v.fv_user_id = ?d
								WHERE cate.cat_ident = "pl_forum"
								AND IF(foru.f_date IS NOT NULL AND v.fv_date IS NOT NULL, foru.f_date > v.fv_date, IF(foru.f_date IS NULL AND v.fv_date IS NOT NULL, 0=1, 1=1))
								AND cate.cat_level = "3"
								AND cate.cat_left > c.cat_left
								AND cate.cat_right < c.cat_right
				                '.($hidden_cat == 1 || $hidden_subcat == 1 ? '' : '
								AND cate.cat_rubrik = 1 AND c.cat_rubrik = 1
				                ').'
							) as count_new_themes';
			}

			if ($count_new_themes_str != '0')
				$res_data = sql_query('SELECT c.cat_id, c.cat_level, c.cat_fields, '.$count_new_themes_str.'
						FROM sb_categs c WHERE c.cat_ident = "pl_forum" AND  c.cat_id IN (?a) '.$where_str.sb_str_replace('NOT', '', $checked_sql), $su_id, $cat_ids);
			else
				$res_data = sql_query('SELECT c.cat_id, c.cat_level, c.cat_fields, '.$count_new_themes_str.'
						FROM sb_categs c WHERE c.cat_ident = "pl_forum" AND  c.cat_id IN (?a) '.$where_str.sb_str_replace('NOT', '', $checked_sql), $cat_ids);

			$ids_cats = $ids_subcats = array();
			foreach($res_data as $key => $value)
			{
				list($cat_id, $cat_level, $cat_fields, $count_new_themes) = $value;

				$cat_fields = unserialize($cat_fields);
				if($cat_level == 1)
				{
					$ids_cats[$cat_id]['count_subcats'] = ($hidden_subcat == 1 ? (isset($cat_fields['count_subcats']) ? $cat_fields['count_subcats'] : '') : (isset($cat_fields['cats_without_hidden']) ? $cat_fields['cats_without_hidden'] : '')); 		// count_subcats
					$ids_cats[$cat_id]['count_themes'] = ($hidden_subcat == 1 ? (isset($cat_fields['count_themes']) ? $cat_fields['count_themes'] : '') : (isset($cat_fields['themes_without_hidden']) ? $cat_fields['themes_without_hidden'] : '')); 		// count_themes
					$ids_cats[$cat_id]['count_new_themes'] = $count_new_themes; 	// count_new_themes
				}
				elseif($cat_level == 2)
				{
					$ids_subcats[$cat_id]['count_themes'] = ($hidden_subcat == 1 ? (isset($cat_fields['count_themes']) ? $cat_fields['count_themes'] : '') : (isset($cat_fields['themes_without_hidden']) ? $cat_fields['themes_without_hidden'] : '')); 		// count_themes
					$ids_subcats[$cat_id]['count_new_themes'] = $count_new_themes;	//	count_new_themes
				}
			}
		}
		else
		{
			if(isset($_GET['pl_forum_cat']))
			{
				$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_no_sub_categs_in_categs']) ? $ftm_templates['msg_no_sub_categs_in_categs'] : ''));
				return;
			}
			elseif(isset($_GET['pl_forum_sub']))
			{
				$GLOBALS['sbCache']->save('pl_forum', (isset($ftm_templates['msg_no_subject_in_categs']) ? $ftm_templates['msg_no_subject_in_categs'] : ''));
				return;
			}
		}

		//	строим список номеров страниц
		if($pt_page_list != '')
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

        if(isset($params['page_subcat']) && trim($params['page_subcat']) != '')
        {
            $more_page_sub = $params['page_subcat'];
        }
        else
        {
            $more_page_sub = SB_DOMAIN.$_SERVER['PHP_SELF'];
        }

        $more_ext = '';
	    if ($more_page_sub != '')
	    {
	        list($more_page_sub, $more_ext) = sbGetMorePage($more_page_sub);
	    }

        if(isset($params['page_theme']) && trim($params['page_theme']) != '')
        {
            $more_page_theme = $params['page_theme'];
        }
        else
        {
            $more_page_theme = (isset($params['page_subcat']) ? $params['page_subcat'] : SB_DOMAIN.$_SERVER['PHP_SELF']);
        }

        if ($more_page_theme != '')
        {
			list($more_page_theme, $more_ext) = sbGetMorePage($more_page_theme);
		}

		$tags = array_merge($us_tags, array('{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}', '{LINK}', '{LAST_LINK}', '{CAT_DESCR}', '{CAT_ICON}', '{COUNT_SUB_CATEGS}', '{COUNT_THEMES}', '{COUNT_NEW_THEMES}', '{COUNT_MESSAGES}', '{COUNT_NEW_MESSAGES}', '{LAST_THEME_ID}', '{LAST_THEME_TITLE}', '{LAST_THEME_URL}', '{LAST_MSG_TEXT}', '{LAST_MSG_DATE}', '{LAST_MSG_AUTHOR}'));
		$tags2 = array_merge($us_tags, array('{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}', '{LINK}', '{LAST_LINK}', '{SUBCAT_DESCR}', '{SUBCAT_ICON}', '{PARENT_CAT_TITLE}', '{PARENT_CAT_ID}', '{PARENT_CAT_URL}', '{COUNT_THEMES}', '{COUNT_NEW_THEMES}', '{COUNT_MESSAGES}', '{COUNT_NEW_MESSAGES}', '{LAST_THEME_ID}', '{LAST_THEME_TITLE}', '{LAST_THEME_URL}', '{LAST_MSG_TEXT}', '{LAST_MSG_DATE}', '{LAST_MSG_AUTHOR}'));
		$tags3 = array_merge($us_tags, array('{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}', '{DATE}', '{AUTHOR}', '{EMAIL}', '{DESCRIPTION}', '{ICON}', '{COUNT_VIEWS}', '{USER_DATA}', '{LINK}', '{COUNT_MESSAGES}', '{COUNT_NEW_MESSAGES}', '{LAST_MSG_TEXT}', '{LAST_MSG_DATE}', '{LAST_MSG_AUTHOR}', '{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}'));

		$dop_tags = array('{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}', '{LINK}'); // доп теги для разделов (на вкладке "поля разделов")
		$dop_tags2 = array('{PARENT_CAT_TITLE}', '{PARENT_CAT_ID}', '{PARENT_CAT_URL}', '{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}', '{LINK}');  // доп теги для подразделов (на вкладке "поля подразделов")
		$dop_tags3 = array('{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}', '{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}', '{LINK}');   // доп теги для тем (на вкладке "поля тем")

		$theme_cat_values = $theme_cat_tags = $values = array();
        $parent1 = $parent2 = $res1 = $res2 = $res3 = $parent_sub_cat_url = $parent_sub_cat_id = $parent_sub_cat_title = '';

		$count_res = count($res);
		$i = 0;

		$arr_new_mess = array();
		if(strpos($ftc_categs_temps['categ_with_sub'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_categs_temps['categ_with_sub_sel'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_categs_temps['categ_without_sub'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_categs_temps['categ_without_sub_sel'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_with_sub'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_with_sub_sel'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_without_sub'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($ftc_sub_categs_temps['sub_categ_without_sub_sel'], '{COUNT_NEW_MESSAGES}') !== false ||
				isset($fts_categs_temps['theme_attach']) && strpos($fts_categs_temps['theme_attach'], '{COUNT_NEW_MESSAGES}') !== false ||
				isset($fts_categs_temps['theme_sel']) && strpos($fts_categs_temps['theme_sel'], '{COUNT_NEW_MESSAGES}') !== false ||
				isset($fts_categs_temps['theme_read']) && strpos($fts_categs_temps['theme_read'], '{COUNT_NEW_MESSAGES}') !== false ||
				isset($fts_categs_temps['theme']) && strpos($fts_categs_temps['theme'], '{COUNT_NEW_MESSAGES}') !== false)
		{
			$res_count_new_mess = sql_query('SELECT c2.cat_id, COUNT(*)
	                FROM sb_forum fo
	                LEFT JOIN sb_catlinks l ON l.link_el_id = fo.f_id
	                LEFT JOIN sb_categs c ON c.cat_id = l.link_cat_id
	                LEFT JOIN sb_forum_viewing v ON v.fv_theme_id = c.cat_id AND v.fv_user_id = ?d,
						sb_categs c2

					WHERE fo.f_show = "1"
	                AND (fo.f_date > v.fv_date OR v.fv_date IS NULL)
	                AND c.cat_ident = "pl_forum"
	                AND c2.cat_ident = "pl_forum"
	                AND c.cat_left >= c2.cat_left
	                '.($hidden_cat == 1 || $hidden_subcat == 1 ? '' : '
					AND c.cat_rubrik = 1 AND c2.cat_rubrik = 1
	                ').'
					AND c.cat_right <= c2.cat_right
					AND c2.cat_id IN (?a)
					GROUP BY c2.cat_id', $su_id, $cat_ids);

			if($res_count_new_mess)
			{
				foreach($res_count_new_mess as $key => $value)
				{
					$arr_new_mess[$value[0]] = $value[1];
				}
			}
		}
		$last = sql_query('SELECT c2.cat_id, c2.cat_url, c2.cat_level, c.cat_id, c.cat_title, c.cat_url, f.f_text, f.f_date, f.f_author, su.su_forum_nick, su.su_login
						FROM sb_categs c, sb_categs c2, sb_forum f LEFT JOIN sb_site_users su ON su.su_id = f.f_user_id, sb_catlinks l
						WHERE c.cat_level = "3"
							'.(!$use_themes ? ' AND c2.cat_level != 3' : '').'
							AND c.cat_ident = "pl_forum"
							AND c2.cat_ident = "pl_forum"
							AND c.cat_id = l.link_cat_id
							AND c.cat_left >= c2.cat_left
							AND c.cat_right <= c2.cat_right
							AND c.cat_rubrik = 1
							AND c2.cat_rubrik = 1
							AND l.link_el_id = f.f_id
							AND f.f_show = 1
							AND c2.cat_id IN (?a)
						ORDER BY f.f_date DESC', $cat_ids);

		$sub_ids = $arr_last = array();
		if ($last)
		{
			foreach($last as $key => $value)
			{
				if(isset($arr_last[$value[0]]) && $value[7] > $arr_last[$value[0]]['m_date'] || !isset($arr_last[$value[0]]))
				{
					$arr_last[$value[0]]['theme_id'] = $value[3];
					$arr_last[$value[0]]['theme_title'] = $value[4];
					$arr_last[$value[0]]['theme_url'] = $value[5];
					$arr_last[$value[0]]['m_text'] = $value[6];
					$arr_last[$value[0]]['m_date'] = $value[7];
					$arr_last[$value[0]]['m_author'] = $value[8];
					$arr_last[$value[0]]['su_nick'] = $value[9];
					$arr_last[$value[0]]['su_login'] = $value[10];

					if($value[2] == 2)
					{
						$sub_ids[$value[3]]['sub_id'] = $value[0];
						$sub_ids[$value[3]]['sub_url'] = $value[1];
					}
				}
			}
		}

		$parent_data = sql_query('SELECT c2.cat_id, c.cat_id, c.cat_title, c.cat_url, c.cat_fields, c.cat_level FROM sb_categs c, sb_categs c2
						WHERE c.cat_left < c2.cat_left
						AND c.cat_right > c2.cat_right
						AND c.cat_level != 0
						AND c.cat_ident="pl_forum"
						AND c2.cat_ident="pl_forum"
						AND c2.cat_id IN (?a)
						AND (c2.cat_level = 3 OR c2.cat_level = 2)
						ORDER BY c.cat_left', $cat_ids);

		if($parent_data)
		{
			$parents = array();
			foreach($parent_data as $val)
			{
				$k = '';
				if($val[5] == 1)
				{
					$k = 'cat';
				}
				elseif($val[5] == 2)
				{
					$k = 'sub';
				}
				if($k != '')
				{
					$parents[$val[0]][$k]['c_id'] = $val[1];
					$parents[$val[0]][$k]['c_title'] = $val[2];
					$parents[$val[0]][$k]['c_url'] = $val[3];
					$parents[$val[0]][$k]['c_fields'] = $val[4];
				}
			}
		}

		foreach($res as $key => $value)
		{
			++$i;
			//	если корневой раздел то не учитываем его
			if($value[2] == 0)
			{
				$root_id = $value[0];
				$root_url = $value[6];
				continue;
			}

			$us_values = array();
			$last_msg_page = '';

			if (isset($params['page_msg']) && $params['page_msg'] != '')
			{
				list($last_msg_page, $more_msg_ext) = sbGetMorePage($params['page_msg']);
			}

			//	если раздел является разделом первого уровня (является разделом)
			if($value[2] == 1)
			{
//				$system_message != ''  зачем здесь нужно было это условие
				if($res3 != '' && $res2 != '' && $flag_parent3 == $parent2 && $flag_parent3 != '')
				{
					$tmp = str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['top']) ? $fts_categs_temps['top'] : '');
					$res3 = $tmp.$res3;

					$res3 .= str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['bottom']) ? $fts_categs_temps['bottom'] : ''); //BOTTOM
					$res2 = str_replace('{THEMES}', $res3, $res2);
					$res3 = '';
				}

				if($res2 != '' && $flag_parent2 != $value[0])
				{
                	$sub_cat_tags = array('{NUM_LIST}', '{COUNT_SUBCATEGS}', '{SYSTEM_MESSAGE}', '{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}');
					$sub_cat_values = array($pt_page_list, $count_sub_cat, $system_message, $parent_sub_cat_title, $parent_sub_cat_id, $parent_sub_cat_url);

					$res2 = str_replace(array_merge($sub_cat_tags, $us_tags), array_merge($sub_cat_values, $us_values), $ftc_sub_categs_temps['sub_top']).
							$res2.str_replace(array_merge($sub_cat_tags, $us_tags), array_merge($sub_cat_values, $us_values), $ftc_sub_categs_temps['sub_bottom']); //BOTTOM

					$res1 = str_replace('{CAT_SUB_CATEGS}', $res2, $res1);
					$res2 = '';
				}

				$res1 = str_replace('{CAT_SUB_CATEGS}', '', $res1);
		        if (trim($value[6]) == '' || $more_page_sub == '')
		        {
					$href = 'javascript: void(0);';
                }
		        else
		        {
					$href = $more_page_sub;

					$query_str = '';
					if(!isset($params['page_subcat']) || $params['page_subcat'] == '')
	    			{
						if (isset($_GET['page_'.$tag_id]))
							$query_str .= 'page_'.$tag_id.'='.$_GET['page_'.$tag_id];
	    			}

					if (sbPlugins::getSetting('sb_static_urls') == 1)
					{
						if(!isset($root_url) || $root_url == '' && !isset($root_id) || $root_id == '')
						{
							$cat = $value[0].'/';
						}
						else
						{
							$cat = ($root_url != '' ? urlencode($root_url).'/' : $root_id.'/');
						}
						// ЧПУ
		                $href .= $cat.($value[6] != '' ? urlencode($value[6]) : $value[0]).($more_ext != 'php' ? '.'.$more_ext : '/').($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].($query_str != '' ? '&'.$query_str : '') : ($query_str != '' ? '?'.$query_str : ''));
                    }
		            else
		            {
						$href = (isset($params['page_subcat']) && $params['page_subcat'] != '' ? $params['page_subcat'] : $_SERVER['PHP_SELF']).'?pl_forum_cat='.$value[0].($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '' ).($query_str != '' ? '&'.$query_str : '');
                    }
                }
				$link_sub = $href;

                //    верх вывода раезделов
				if($res1 == '')
				{
                    $res1 .= str_replace(array('{NUM_LIST}', '{COUNT_CATEGS}', '{SYSTEM_MESSAGE}'), array($pt_page_list, $forum_total, $system_message), $ftc_categs_temps['top']);
                }

				$parent1 = $value[0];

	            $categs = $value[3] = unserialize($value[3]);
	            $dop_values = array($value[1], $value[0], $value[6], $link_sub);
	            $values = array();
	            $cat_values = array();

	            if ($num_cat_fields > 0)
		        {
					foreach ($categs_sql_fields as $cat_field)
	                {
	                    if (isset($categs[$cat_field]))
	                        $cat_values[] = $categs[$cat_field];
	                    else
	                        $cat_values[] = null;
	                }

	                $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ftc_user_categs_temps, $dop_tags, $dop_values, isset($fts_lang) ? $fts_lang : '');
					$values = array_merge($values, $cat_values);
		        }

				$values[] = $value[1];		//  CAT_TITLE
	            $values[] = $value[0];		//  CAT_ID
	            $values[] = $value[6];		//  CAT_URL
	            $values[] = $link_sub;		//  LINK

				if(isset($params['page_msg']) && $params['page_msg'] != '' && isset($arr_last[$value[0]]['theme_id'])
				&& (
						strpos($ftc_categs_temps['categ_with_sub'], '{LAST_LINK}') ||
						strpos($ftc_categs_temps['categ_with_sub_sel'], '{LAST_LINK}') ||
						strpos($ftc_categs_temps['categ_without_sub'], '{LAST_LINK}') ||
						strpos($ftc_categs_temps['categ_without_sub_sel'], '{LAST_LINK}') ))
	            {
	            	$last_theme_id = $arr_last[$value[0]]['theme_id'];
					if(isset($sub_ids[$last_theme_id]))
					{
						if(!isset($params['page_theme']))
						{
							$params['page_theme'] = '';
						}

						if (sbPlugins::getSetting('sb_static_urls') == 1)
			            {
							if(isset($params['page_msg']) && $params['page_msg'] != '' && $params['page_msg'] != $params['page_theme'])
							{
								$last_msg_page .= ($sub_ids[$last_theme_id]['sub_url'] != '' ? urlencode($sub_ids[$last_theme_id]['sub_url']).'/' : $sub_ids[$last_theme_id]['sub_id'].'/').
									($arr_last[$value[0]]['theme_url'] != '' ? urlencode($arr_last[$value[0]]['theme_url']) : $arr_last[$value[0]]['theme_id']).($more_msg_ext != 'php' ? '.'.$more_msg_ext : '');
							}
							else
							{
								$last_msg_page .= ($value[6] != '' ? urlencode($value[6]).'/' : $value[0].'/').
									($sub_ids[$last_theme_id]['sub_url'] != '' ? urlencode($sub_ids[$last_theme_id]['sub_url']) : $sub_ids[$last_theme_id]['sub_id']).($more_msg_ext != 'php' ? '.'.$more_msg_ext : '').'?pl_forum_theme='.(isset($arr_last[$value[0]]['theme_id']) ? $arr_last[$value[0]]['theme_id'] : '');
							}
						}
						else
			            {
							if(isset($params['page_msg']) && $params['page_msg'] != '' && $params['page_msg'] != $params['page_theme'])
							{
								$last_msg_page = $last_msg_page.'?pl_forum_theme='.$arr_last[$value[0]]['theme_id'];		//  LAST_LIST
							}
							else
							{
								$last_msg_page = $last_msg_page.'?pl_forum_sub='.$sub_ids[$last_theme_id]['sub_id'].'&pl_forum_theme='.(isset($arr_last[$value[0]]['theme_id']) ? $arr_last[$value[0]]['theme_id'] : '');		//  LAST_LIST
							}
			            }
					}
					else
					{
						$last_msg_page = 'javascript: void(0);';
					}
					$values[] = $last_msg_page;
				}
				else
				{
					$values[] = 'javascript: void(0);';
	            }

				if(isset($value[3]['cat_subject_description']) && $value[3]['cat_subject_description'] != '')
					$values[] = str_replace(array_merge($dop_tags, array('{CAT_DESCR}')), array_merge($dop_values, array($value[3]['cat_subject_description'])), $ftc_categs_temps['cat_descr_field']);   //  CAT_DESCR
				else
					$values[] = '';   //  CAT_DESCR

				if(isset($value[3]['cat_image']) && $value[3]['cat_image'] != '')
					$values[] = str_replace(array_merge($dop_tags, array('{CAT_ICON}')), array_merge($dop_values, array($value[3]['cat_image'])), $ftc_categs_temps['cat_icon_field']);   //  CAT_ICON
				else
					$values[] = '';   //  CAT_ICON

				$values[] = (isset($ids_cats[$value[0]]['count_subcats']) && $ids_cats[$value[0]]['count_subcats'] != '' ? $ids_cats[$value[0]]['count_subcats'] : 0);;   //  COUNT_SUB_CATEGS
	            $values[] = (isset($ids_cats[$value[0]]['count_themes']) && $ids_cats[$value[0]]['count_themes'] != '' ? $ids_cats[$value[0]]['count_themes'] : 0);    //  COUNT_THEMES
				$values[] = (isset($ids_cats[$value[0]]['count_new_themes']) && $ids_cats[$value[0]]['count_new_themes'] != '' && $su_id > 0 ? $ids_cats[$value[0]]['count_new_themes'] : 0); //  COUNT_NEW_THEMES
				$values[] = ($hidden_cat == 1 ? (isset($value[3]['count_msgs']) ? $value[3]['count_msgs'] : '0') : (isset($value[3]['mesgs_without_hid_themes']) ? $value[3]['mesgs_without_hid_themes'] : '0'));	//	COUNT_MESSAGES
				$values[] = (isset($arr_new_mess[$value[0]]) && $arr_new_mess[$value[0]] != '' && $su_id > 0 ? $arr_new_mess[$value[0]] : 0);    //  COUNT_NEW_MESSAGES

				$values[] = isset($arr_last[$value[0]]['theme_id']) ? $arr_last[$value[0]]['theme_id'] : '' ;   //   LAST_THEME_ID
				$values[] = isset($arr_last[$value[0]]['theme_title']) ? $arr_last[$value[0]]['theme_title'] : '' ;   //   LAST_THEME_TITLE
				$values[] = isset($arr_last[$value[0]]['theme_url']) ? $arr_last[$value[0]]['theme_url'] : '' ;   //   LAST_THEME_URL
				$values[] = isset($arr_last[$value[0]]['m_text']) ? sbProgParseBBCodes($arr_last[$value[0]]['m_text'], '', '', true) : '' ;   //   LAST_MSG_TEXT
				$values[] = isset($arr_last[$value[0]]['m_date']) && isset($fts_messages_temps['mess_date_filed']) ? sb_parse_date($arr_last[$value[0]]['m_date'], $fts_messages_temps['mess_date_filed'], isset($fts_lang) ? $fts_lang : '') : '' ;   //   LAST_MSG_DATE

				if(isset($arr_last[$value[0]]['su_nick']) && $arr_last[$value[0]]['su_nick'] != '')
					$author = $arr_last[$value[0]]['su_nick'];

				elseif(isset($arr_last[$value[0]]['su_login']) && $arr_last[$value[0]]['su_login'] != '')
					$author = $arr_last[$value[0]]['su_login'];
				else
					$author = (isset($arr_last[$value[0]]['m_author']) ? $arr_last[$value[0]]['m_author'] : '');

				$values[] = $author;	//	LAST_MSG_AUTHOR

//				кол-во тем в разделе
				$count_theme_cat = 0;

	            $parent_sub_cat_title = $value[1];
	            $parent_sub_cat_id = $value[0];
	            $parent_sub_cat_url = $value[6];

	            if(($value[5] - $value[4]) > 1)
	            {
                    if(isset($_GET['pl_forum_cat']) && $_GET['pl_forum_cat'] == $value[0] || isset($_GET['pl_forum_cat_sel']) && $_GET['pl_forum_cat_sel'] == $value[0])
	                {
						$res1 .= str_replace($tags, $values, $ftc_categs_temps['categ_with_sub_sel']);
                    }
					else
					{
						$res1 .= str_replace($tags, $values, $ftc_categs_temps['categ_with_sub']);
                    }
                }
                else
	            {
                    if(isset($_GET['pl_forum_cat']) && $_GET['pl_forum_cat'] == $value[0] || isset($_GET['pl_forum_cat_sel']) && $_GET['pl_forum_cat_sel'] == $value[0])
                    {
                        $res1 .= str_replace($tags, $values, $ftc_categs_temps['categ_without_sub_sel']);
                    }
                    else
                    {
                        $res1 .= str_replace($tags, $values, $ftc_categs_temps['categ_without_sub']);
                    }
                }
            }

			$values2 = array();
//          если раздел является разделом второго уровня (является подразделом)
			if($value[2] == 2)
			{
				if($res3 != '' && $flag_parent3 != '' && $flag_parent3 != $value[0])
				{
					$tmp = str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['top']) ? $fts_categs_temps['top'] : '');
					$res3 = $tmp.$res3;

					$res3 .= str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['bottom']) ? $fts_categs_temps['bottom'] : '');	//	BOTTOM
					$res2 = str_replace('{THEMES}', $res3, $res2);
					$res3 = '';
				}

				$parent_sub_cat_id = (isset($parents[$value[0]]['cat']['c_id']) ? $parents[$value[0]]['cat']['c_id'] : '');
				$parent_sub_cat_title = (isset($parents[$value[0]]['cat']['c_title']) ? $parents[$value[0]]['cat']['c_title'] : '');
				$parent_sub_cat_url = (isset($parents[$value[0]]['cat']['c_url']) ? $parents[$value[0]]['cat']['c_url'] : '');
				$parent_sub_cat_fields = (isset($parents[$value[0]]['cat']['c_fields']) ? unserialize($parents[$value[0]]['cat']['c_fields']) : array());
				$count_sub_cat = isset($ids_cats[$parent_sub_cat_id]['count_subcats']) ? $ids_cats[$parent_sub_cat_id]['count_subcats'] : 0;

				$res2 = str_replace('{THEMES}', '', $res2);
                if (trim($value[6]) == '' || $more_page_theme == '')
                {
                	$href = 'javascript: void(0);';
				}
				else
				{
					$href = $more_page_theme;

					$query_str = '';
					if(!isset($params['page_subcat']) || $params['page_subcat'] == '')
	    			{
	    				if (isset($_GET['page_'.$tag_id]))
							$query_str .= 'page_'.$tag_id.'='.$_GET['page_'.$tag_id];
	    			}

                    if (sbPlugins::getSetting('sb_static_urls') == 1)
                    {
                        // ЧПУ
                        $href .= ($parent_sub_cat_url != '' ? urlencode($parent_sub_cat_url).'/' : $parent_sub_cat_id.'/').
                                    ($value[6] != '' ? urlencode($value[6]) : $value[0]).($more_ext != 'php' ? '.'.$more_ext : '/').($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].($query_str != '' ? '&'.$query_str : '') : ($query_str != '' ? '?'.$query_str : ''));
                    }
                    else
                    {
                    	if(isset($params['page_theme']) && $params['page_theme'] != '')
                    	{
                            $page = $params['page_theme'];
                    	}
                    	elseif(isset($params['page_subcat']) && $params['page_subcat'] != '')
                    	{
                            $page = $params['page_subcat'];
                    	}
                    	else
                    	{
                            $page = $_SERVER['PHP_SELF'];
                    	}

                        if(!isset($parent_sub_cat_id) || $parent_sub_cat_id == '')
                        {
                            if(isset($_GET['pl_forum_cat']) && $_GET['pl_forum_cat'])
	                        {
	                            $parent_sub_cat_id = intval($_GET['pl_forum_cat']);
	                        }
	                        elseif(isset($_GET['pl_forum_cat_sel']) && $_GET['pl_forum_cat_sel'])
	                        {
	                            $parent_sub_cat_id = intval($_GET['pl_forum_cat_sel']);
							}
                        }

						$href = $page.'?pl_forum_sub='.$value[0].(!isset($params['page_theme']) || $params['page_theme'] == '' ? '&pl_forum_cat_sel='.$parent_sub_cat_id : '').($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : '').($query_str != '' ? '&'.$query_str : '');
					}
				}

				$link_theme = $href;
				$dop_values2 = array($parent_sub_cat_title, $parent_sub_cat_id, $parent_sub_cat_url, $value[1], $value[0], $value[6], $link_theme);

	            if ($num_cat_fields > 0)
		        {
		        	$cat_values = array();
					foreach($categs_sql_fields as $cat_field)
	                {
	                    if (isset($parent_sub_cat_fields[$cat_field]))
	                        $cat_values[] = $parent_sub_cat_fields[$cat_field];
	                    else
	                        $cat_values[] = null;
					}
					$us_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ftc_user_subcategs_temps, $dop_tags2, $dop_values2, $ftc_lang);
				}

//              ID родительского раздела для текущего подраздела
				$flag_parent2 = $parent1;
				$parent2 = $value[0];

				$categs = $value[3] = unserialize($value[3]);

				if ($num_cat_fields > 0)
				{
					$cat_values = array();
					foreach ($categs_sql_fields as $cat_field)
					{
	                    if (isset($categs[$cat_field]))
	                        $cat_values[] = $categs[$cat_field];
	                    else
	                        $cat_values[] = null;
	                }

					$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $ftc_user_subcategs_temps, $dop_tags2, $dop_values2, $ftc_lang);
					$values2 =  array_merge($values2, $cat_values);
		        }

				$values2[] = $value[1];    //  SUBCAT_TITLE
				$values2[] = $value[0];    //  SUBCAT_ID
                $values2[] = $value[6];    //  SUBCAT_URL
				$values2[] = $link_theme;  //  LINK

				if (isset($params['page_msg']) && $params['page_msg'] != '' && isset($arr_last[$value[0]]['theme_id']))
	            {
	            	if(!isset($params['page_theme']))
	            	{
						$params['page_theme'] = '';
	            	}

		            if (sbPlugins::getSetting('sb_static_urls') == 1)
		            {
						// ЧПУ
						if(isset($params['page_msg']) && $params['page_msg'] != '' && $params['page_msg'] != $params['page_theme'])
						{
							$last_msg_page .= ($value[6] != '' ? urlencode($value[6]).'/' : $value[0].'/').
								($arr_last[$value[0]]['theme_url'] != '' ? urlencode($arr_last[$value[0]]['theme_url']) : $arr_last[$value[0]]['theme_id']).($more_msg_ext != 'php' ? '.'.$more_msg_ext : '');
						}
						else
						{
							$last_msg_page .= ($parent_sub_cat_url != '' ? urlencode($parent_sub_cat_url).'/' : $parent_sub_cat_id.'/').
								($value[6] != '' ? urlencode($value[6]) : $value[0]).($more_msg_ext != 'php' ? '.'.$more_msg_ext : '').'?pl_forum_theme='.$arr_last[$value[0]]['theme_id'];
						}
					}
					else
					{
						if(isset($params['page_msg']) && $params['page_msg'] != '' && $params['page_msg'] != $params['page_theme'])
						{
							$last_msg_page = $last_msg_page.'?pl_forum_theme='.$arr_last[$value[0]]['theme_id'];		//  LAST_LIST
						}
						else
						{
							$last_msg_page = $last_msg_page.'?pl_forum_sub='.$value[0].'&pl_forum_theme='.$arr_last[$value[0]]['theme_id'];		//  LAST_LIST
						}
					}
					$values2[] = $last_msg_page;
	            }
				else
				{
					$values2[] = 'javascript: void(0);';
	            }

				if(isset($value[3]['cat_subject_description']) && $value[3]['cat_subject_description'] != '')
					$values2[] = str_replace(array_merge($dop_tags2, array('{SUBCAT_DESCR}')), array_merge($dop_values2, array(isset($value[3]['cat_subject_description']) ? $value[3]['cat_subject_description'] :'')), $ftc_sub_categs_temps['cat_descr_field']); //  SUBCAT_DESCR
				else
					$values2[] = '';

				if(isset($value[3]['cat_image']) && $value[3]['cat_image'] != '')
					$values2[] = str_replace(array_merge($dop_tags2, array('{SUBCAT_ICON}')), array_merge($dop_values2, array(isset($value[3]['cat_image']) ? $value[3]['cat_image'] :'')), $ftc_sub_categs_temps['cat_icon_field']); //  SUBCAT_ICON
				else
					$values2[] = '';

				$values2[] = $parent_sub_cat_title; //  PARENT_CAT_TITLE
				$values2[] = $parent_sub_cat_id; //  PARENT_CAT_ID
				$values2[] = $parent_sub_cat_url; //  PARENT_CAT_URL

                $values2[] = (isset($ids_subcats[$value[0]]['count_themes']) && $ids_subcats[$value[0]]['count_themes'] != '' ? $ids_subcats[$value[0]]['count_themes'] : 0 );  //  COUNT_THEMES
				$values2[] = (isset($ids_subcats[$value[0]]['count_new_themes']) && $ids_subcats[$value[0]]['count_new_themes'] != '' && $su_id > 0 ? $ids_subcats[$value[0]]['count_new_themes'] : 0);  //  COUNT_NEW_THEMES
				$values2[] = ($hidden_subcat == 1 ? (isset($value[3]['count_msgs']) ? $value[3]['count_msgs'] : '0') : (isset($value[3]['mesgs_without_hid_themes']) ? $value[3]['mesgs_without_hid_themes'] : '0'));	//	COUNT_MESSAGES
	            $values2[] = (isset($arr_new_mess[$value[0]]) && $arr_new_mess[$value[0]] != '' && $su_id > 0 ? $arr_new_mess[$value[0]] : 0);    //  COUNT_NEW_MESSAGES

				$values2[] = isset($arr_last[$value[0]]['theme_id']) ? $arr_last[$value[0]]['theme_id'] : '' ;   //   LAST_THEME_ID
				$values2[] = isset($arr_last[$value[0]]['theme_title']) ? $arr_last[$value[0]]['theme_title'] : '' ;   //   LAST_THEME_TITLE
				$values2[] = isset($arr_last[$value[0]]['theme_url']) ? $arr_last[$value[0]]['theme_url'] : '' ;   //   LAST_THEME_URL
				$values2[] = isset($arr_last[$value[0]]['m_text']) ? sbProgParseBBCodes($arr_last[$value[0]]['m_text'], '', '', true) : '' ;   //   LAST_MSG_TEXT
				$values2[] = isset($arr_last[$value[0]]['m_date']) && isset($fts_messages_temps['mess_date_filed']) ? sb_parse_date($arr_last[$value[0]]['m_date'], $fts_messages_temps['mess_date_filed'], $fts_lang) : '';   //   LAST_MSG_DATE

				if(isset($arr_last[$value[0]]['su_nick']) && $arr_last[$value[0]]['su_nick'] != '')
					$author = $arr_last[$value[0]]['su_nick'];
				elseif(isset($arr_last[$value[0]]['su_login']) && $arr_last[$value[0]]['su_login'] != '')
					$author = $arr_last[$value[0]]['su_login'];
				else
					$author = (isset($arr_last[$value[0]]['m_author']) ? $arr_last[$value[0]]['m_author'] : '');

				$values2[] = $author;   //   LAST_MSG_AUTHOR
				$count_theme_cat = 0;

//				$parents[3] = $value[1];
//				$parents[4] = $value[0];
//				$parents[5] = $value[6];

                if(($value[5] - $value[4]) > 1)
                {
                    if(isset($_GET['pl_forum_sub']) && $_GET['pl_forum_sub'] == $value[0])
                    {
						$res2 .= str_replace($tags2, $values2, $ftc_sub_categs_temps['sub_categ_with_sub_sel']);
                    }
                    else
                    {
						$res2 .= str_replace($tags2, $values2, $ftc_sub_categs_temps['sub_categ_with_sub']);
                    }
                }
				else
                {
					if(isset($_GET['pl_forum_sub']) && $_GET['pl_forum_sub'] == $value[0])
                    {
						$res2 .= str_replace($tags2, $values2, $ftc_sub_categs_temps['sub_categ_without_sub_sel']);
                    }
                    else
                    {
						$res2 .= str_replace($tags2, $values2, $ftc_sub_categs_temps['sub_categ_without_sub']);
                    }
                }
			}
			$values3 = array();

//			если раздел является разделом третьего уровня (является темой)
			if($value[2] == 3)
			{
				$flag_parent3 = $parent2;

				$p_c_id = (isset($parents[$value[0]]['cat']['c_id']) ? $parents[$value[0]]['cat']['c_id'] : '');
				$p_c_title = (isset($parents[$value[0]]['cat']['c_title']) ? $parents[$value[0]]['cat']['c_title'] : '');
				$p_c_url = (isset($parents[$value[0]]['cat']['c_url']) ? $parents[$value[0]]['cat']['c_url'] : '');

				$p_s_id = (isset($parents[$value[0]]['sub']['c_id']) ? $parents[$value[0]]['sub']['c_id'] : '');
				$p_s_title = (isset($parents[$value[0]]['sub']['c_title']) ? $parents[$value[0]]['sub']['c_title'] : '');
				$p_s_url = (isset($parents[$value[0]]['sub']['c_url']) ? $parents[$value[0]]['sub']['c_url'] : '');

				$theme_cat_tags = array('{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}', '{SUB_CAT_TITLE}', '{SUB_CAT_ID}', '{SUB_CAT_URL}', '{COUNT_THEMES}', '{COUNT_NEW_THEMES}');
				$theme_cat_values = array($p_c_title,
										$p_c_id,
										$p_c_url,
										$p_s_title,
										$p_s_id,
										$p_s_url,
										(isset($ids_subcats[$p_s_id]['count_themes']) ? $ids_subcats[$p_s_id]['count_themes'] : ''),
										(isset($ids_subcats[$p_s_id]['count_new_themes']) ? $ids_subcats[$p_s_id]['count_new_themes'] : ''));

				$dop_tags3 = array('{THEME_TITLE}', '{THEME_ID}',  '{THEMEME_URL}', '{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}', '{LINK}');   // доп теги для тем (на вкладке "поля тем")
				$dop_values3 = array($value[1], $value[0], $value[6], $p_s_title, $p_s_id, $p_s_url, $_SERVER['PHP_SELF']);

				$categs = $value[3] = unserialize($value[3]);
				if ($num_cat_fields > 0)
				{
					$cat_values = array();
					foreach ($categs_sql_fields as $cat_field)
					{
	                    if (isset($categs[$cat_field]))
							$cat_values[] = $categs[$cat_field];
	                    else
							$cat_values[] = null;
					}

					$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $fts_user_categs_temps, $dop_tags3, $dop_values3, $fts_lang);
					$values3 =  array_merge($values3, $cat_values);
				}

				$values3[] = $value[1];		// THEME_TITLE
                $values3[] = $value[0];		// THEME_ID
                $values3[] = $value[6];		// THEME_URL
				$values3[] = isset($fts_categs_temps['theme_date_field']) ? sb_parse_date((isset($value[7]) ? $value[7] : time()), $fts_categs_temps['theme_date_field'], $fts_lang) : ''; // DATE

				$author = '';
                if(isset($authors[$value[0]]['login']) && $authors[$value[0]]['login'] != '')
                {
                    $author = $authors[$value[0]]['login'];
                }
                elseif(isset($authors[$value[0]]['fio']) && $authors[$value[0]]['fio'] != '')
                {
                    $author = $authors[$value[0]]['fio'];
                }

                if(isset($authors[$value[0]]['email']) && $authors[$value[0]]['email'] != '')
                {
                    $email = $authors[$value[0]]['email'];
                }
                else
                {
					$email = '';
                }

				$values3[] = isset($fts_categs_temps['theme_author_field']) ? str_replace(array_merge($dop_tags3, array('{AUTHOR}')), array_merge($dop_values3, array($author)), $fts_categs_temps['theme_author_field']) : '';  //   AUTHOR
				$values3[] = isset($fts_categs_temps['theme_email_field']) ? str_replace(array_merge($dop_tags3, array('{EMAIL}')), array_merge($dop_values3, array($email)), $fts_categs_temps['theme_email_field']) : '';  //  MAIL
				$values3[] = isset($fts_categs_temps['theme_descr_field']) ? str_replace(array_merge($dop_tags3, array('{DESCRIPTION}')), array_merge($dop_values3, array(isset($value[3]['cat_subject_description']) ? $value[3]['cat_subject_description'] : '')), $fts_categs_temps['theme_descr_field']) : '';  // DESCRIPTION
				$values3[] = isset($fts_categs_temps['theme_icon_field']) ? str_replace(array_merge($dop_tags3, array('{CAT_ICON}')), array_merge($dop_values3, array(isset($value[3]['cat_image']) ? $value[3]['cat_image'] : '')), $fts_categs_temps['theme_icon_field']) : '';   // ICON
				$values3[] = isset($forum_viewing[$value[0]]['fv_count_views']) ? $forum_viewing[$value[0]]['fv_count_views'] : 0 ; // COUNT_VIEWS

				if(isset($fts_user_data_themes_id) && $fts_user_data_themes_id > 0 && isset($authors[$value[0]]['id']) && $authors[$value[0]]['id'] > 0)
				{
					require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
					$values3[] = fSite_Users_Get_Data($fts_user_data_themes_id, $authors[$value[0]]['id']); // USER_DATA
				}
				else
                {
					$values3[] = '';	//	USER_DATA
				}

				$query_str = '';
				if (isset($p_s_url) && $p_s_url != '' && $last_msg_page != '')
				{
					if(!isset($params['page_theme']))
					{
						$params['page_theme'] = '';
					}

					if (sbPlugins::getSetting('sb_static_urls') == 1)
					{
						// ЧПУ
						if(!isset($params['page_msg']) || $params['page_msg'] == '' || $params['page_msg'] == $params['page_theme'])
    					{
							$query_str .= ($query_str != '' ? '&' : '?').'pl_forum_theme='.$value[0];
							$last_msg_page .= (isset($p_c_url) && $p_c_url != '' ?  urlencode($p_c_url).'/' :  $p_c_id.'/').
								(isset($p_s_url) && $p_s_url != '' ? urlencode($p_s_url).'/' :  $p_s_id.'/').($more_ext != 'php' ? '.'.$more_ext : '/').$query_str;
    					}
    					else
    					{
							// ЧПУ
							$last_msg_page .= (isset($p_s_url) && $p_s_url != '' ? urlencode($p_s_url).'/' : $p_s_id.'/').
								($value[6] != '' ? urlencode($value[6]) : $value[0]).($more_msg_ext != 'php' ? '.'.$more_msg_ext : '');
    					}
					}
					else
					{
 						if(isset($params['page_msg']) && $params['page_msg'] != '' && $params['page_msg'] != $params['page_theme'])
						{
							$last_msg_page = $last_msg_page.'?pl_forum_theme='.$value[0];		//  LAST_LIST
						}
						else
						{
							$last_msg_page = $last_msg_page.'?pl_forum_sub='.$p_s_id.'&pl_forum_theme='.$value[0];		//  LAST_LIST
						}
					}
					$values3[] = $last_msg_page;	 // LINK
				}
				else
				{
					$href = 'javascript: void(0);';
					$values3[] = $href;  	 // LINK
				}

				$values3[] = ($hidden_subcat == 1 ? (isset($value[3]['count_msgs']) ? $value[3]['count_msgs'] : '0') : (isset($value[3]['mesgs_without_hid_themes']) ? $value[3]['mesgs_without_hid_themes'] : '0'));	//	COUNT_MESSAGES
				$values3[] = (isset($arr_new_mess[$value[0]]) && $arr_new_mess[$value[0]] != '' && $su_id > 0 ? $arr_new_mess[$value[0]] : 0);    //  COUNT_NEW_MESSAGES
				$values3[] = isset($arr_last[$value[0]]['m_text']) ? sbProgParseBBCodes($arr_last[$value[0]]['m_text'], '', '', true) : '';		// LAST_MSG_TEXT
				$values3[] = isset($arr_last[$value[0]]['m_date']) && isset($fts_messages_temps['mess_date_filed']) ? sb_parse_date($arr_last[$value[0]]['m_date'], $fts_messages_temps['mess_date_filed'], $fts_lang) : '';   //   LAST_MSG_DATE

				if(isset($arr_last[$value[0]]['su_nick']) && $arr_last[$value[0]]['su_nick'] != '')
					$author = $arr_last[$value[0]]['su_nick'];
				elseif(isset($arr_last[$value[0]]['su_login']) && $arr_last[$value[0]]['su_login'] != '')
					$author = $arr_last[$value[0]]['su_login'];
				else
					$author = (isset($arr_last[$value[0]]['m_author']) ? $arr_last[$value[0]]['m_author'] : '');

				$values3[] = $author;		 // LAST_MSG_AUTHOR
    			$values3[] = isset($p_s_title) ? $p_s_title : '';		// SUBCAT_TITLE
				$values3[] = isset($p_s_id) ? $p_s_id : '';       // SUBCAT_ID
				$values3[] = isset($p_s_url) ? $p_s_url : '';		// SUBCAT_URL


				if(isset($value[3]['cat_subject_main']) && $value[3]['cat_subject_main'] == 1)
				{
					$tmp = isset($fts_categs_temps['theme_attach']) ? str_replace($tags3, $values3, $fts_categs_temps['theme_attach']) : '';	//	Приклеенная тема
					$res3 = $tmp.$res3;
				}
				elseif(isset($_GET['pl_forum_sub']) && $_GET['pl_forum_sub'] == $value[0])
				{
					$res3 .= isset($fts_categs_temps['theme_sel']) ? str_replace($tags3, $values3, $fts_categs_temps['theme_sel']) : '';	//	Тема (выбранная)
				}
				elseif(isset($forum_viewing[$value[0]][$su_id]) && intval($value[8]) <= intval($forum_viewing[$value[0]][$su_id]['date']) && $su_id > 0)
				{
					$res3 .= isset($fts_categs_temps['theme_read']) ? str_replace($tags3, $values3, $fts_categs_temps['theme_read']) : '';	//	Прочитанная тема
				}
				elseif((!isset($forum_viewing[$value[0]][$su_id]) || intval($value[8]) > intval($forum_viewing[$value[0]][$su_id]['date'])) && $su_id > 0)
				{
					$res3 .= isset($fts_categs_temps['theme_new']) ? str_replace($tags3, $values3, $fts_categs_temps['theme_new']) : '';	//	Тема (новая)
				}
				else
				{
					$res3 .= isset($fts_categs_temps['theme']) ? str_replace($tags3, $values3, $fts_categs_temps['theme']) : '';	//	Тема
                }

//				если это последний пункт дерева разделов то
				if($count_res == $i)
				{
					if($res3 != '' && $res2 != '' && $flag_parent3 == $parent2)
					{
						$tmp = str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['top']) ? $fts_categs_temps['top'] : '');
                        $res3 = $tmp.$res3;
						$res3 .= str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['bottom']) ? $fts_categs_temps['bottom'] : ''); //BOTTOM

						$res2 = str_replace('{THEMES}', $res3, $res2);
						$res3 = '';
                    }
                }
			}

			$need_bottom = true;
//			если текущий раздел последний
            if($count_res == $i)
            {
				if($res3 != '' && $res2 != '' && $flag_parent3 == $parent2)
                {
                    $tmp = str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['top']) ? $fts_categs_temps['top'] : '');
                    $res3 = $tmp.$res3;

                    $res3 .= str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['bottom']) ? $fts_categs_temps['bottom'] : ''); //BOTTOM
                    $res2 = str_replace('{THEMES}', $res3, $res2);
                    $res3 = '';
                }

//              если дерево разделов заканчивается на разделе и в этот раздел нужно вывести подразделы
				if($res2 != '' && $res1 != '' && $flag_parent2 == $parent1)
                {
                	$sub_cat_tags = array('{NUM_LIST}', '{COUNT_SUBCATEGS}', '{SYSTEM_MESSAGE}', '{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}');
					$sub_cat_values = array($pt_page_list, $count_sub_cat, $system_message, $parent_sub_cat_title, $parent_sub_cat_id, $parent_sub_cat_url);

					$res2 = str_replace(array_merge($sub_cat_tags, $us_tags), array_merge($sub_cat_values, $us_values), $ftc_sub_categs_temps['sub_top']).
							$res2.str_replace(array_merge($sub_cat_tags, $us_tags), array_merge($sub_cat_values, $us_values), $ftc_sub_categs_temps['sub_bottom']); //BOTTOM

                    $res1 = str_replace('{CAT_SUB_CATEGS}', $res2, $res1);
                    $res2 = '';
                }
                elseif($res1 == '' && $res2 == '')	//	если нажат подраздел и нужно вывести список тем
                {
					$tmp = str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['top']) ? $fts_categs_temps['top'] : '');
					$res3 = $tmp.$res3;

					$res3 .= str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['bottom']) ? $fts_categs_temps['bottom'] : ''); //BOTTOM
					$res1 = $res3;

					$res3 = '';
					$need_bottom = false;
				}
				elseif($res1 == '')		//	если мы выводим начиная с подразделов
				{
                	$sub_cat_tags = array('{NUM_LIST}', '{COUNT_SUBCATEGS}', '{SYSTEM_MESSAGE}', '{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}');
					$sub_cat_values = array($pt_page_list, $count_sub_cat, $system_message, $parent_sub_cat_title, $parent_sub_cat_id, $parent_sub_cat_url);

					$res2 = str_replace(array_merge($sub_cat_tags, $us_tags), array_merge($sub_cat_values, $us_values), $ftc_sub_categs_temps['sub_top']).
							$res2.str_replace(array_merge($sub_cat_tags, $us_tags), array_merge($sub_cat_values, $us_values), $ftc_sub_categs_temps['sub_bottom']); //BOTTOM

					$res1 = $res2;
					$need_bottom = false;
                }

                if($need_bottom == true && $res1 != '')
                {
					$res1 .= str_replace(array('{NUM_LIST}', '{COUNT_CATEGS}', '{SYSTEM_MESSAGE}'),
							array($pt_page_list, $forum_total, $system_message),
							$ftc_categs_temps['bottom']);
				}
			}
			$old_us_values = $us_values;
		}

		$result = $res1;
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
		$GLOBALS['sbCache']->save('pl_forum', $result);
	}

	/**
	 * Функция вывода тем и сообщений. Используется для компонентов:
	 * 				"Вывод тем и сообщений",
	 * 				"Вывод поледних тем и сообщений",
	 * 				"Вывод сообщений автора"
	 *
	 * @param bool $for_su Флаг. Вывзвана функция для компонента "Вывод сообщений автора" (TRUE) или нет (FALSE)
	 */
	function fForum_Elem_Subject_List($el_id, $temp_id, $params, $tag_id, $for_su = false)
	{
		if ($GLOBALS['sbCache']->check('pl_forum', $tag_id, array($el_id, $temp_id, $params)))
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

		$cat_ids = array();

		$system_message = '';
//		макеты дизайна тем и сообщений
		$res = sql_param_query('SELECT fts_name, fts_checked, fts_mess_checked, fts_messages_temps, fts_categs_temps, fts_user_fields_temps,
				fts_user_categs_temps, fts_perpage, fts_perpage_messages, fts_messages_id, fts_pagelist_id, fts_lang, fts_pagelist_mess_id,
				fts_user_data_themes_id, fts_user_data_mess_id, fts_theme_form_id FROM sb_forum_temps_subjects WHERE fts_id=?d', $temp_id);

		if(!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
			return;
		}

		list($fts_name, $fts_checked, $fts_mess_checked, $fts_messages_temps, $fts_categs_temps, $fts_user_fields_temps,
				$fts_user_categs_temps, $fts_perpage, $fts_perpage_messages, $fts_messages_id, $fts_pagelist_id,
				$fts_lang, $fts_pagelist_mess_id, $fts_user_data_themes_id, $fts_user_data_mess_id, $fts_theme_form_id) = $res[0];

		$fts_messages_temps = unserialize($fts_messages_temps);
		$fts_categs_temps = unserialize($fts_categs_temps);
		$fts_user_fields_temps = unserialize($fts_user_fields_temps);
		$fts_user_categs_temps = unserialize($fts_user_categs_temps);

		$ftm_templates = array();
		// Макеты дизайна уведомлений
		if($fts_messages_id > 0)
		{
			$messages_res = sql_param_query('SELECT ftm_templates FROM sb_forum_temps_messages WHERE ftm_id = ?d', $fts_messages_id);
			if(!$messages_res)
			{
				sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
			}

			list($ftm_templates) = $messages_res[0];
			$ftm_templates = unserialize($ftm_templates);
		}

		if (sbPlugins::getSetting('sb_static_urls') == 1 && isset($_GET['forum_sid']) && $_GET['forum_sid'] != '')
		{
			$res = sql_query('SELECT cat_id, cat_level FROM sb_categs WHERE cat_url=? AND cat_ident="pl_forum"', $_GET['forum_sid']);
			if(!$res)
			{
				$res = sql_query('SELECT cat_id, cat_level FROM sb_categs WHERE cat_id=?d AND cat_ident="pl_forum"', $_GET['forum_sid']);
			}
			if($res)
			{
				if($res[0][1] == 2)
				{
					$_GET['pl_forum_sub'] = $res[0][0];
				}
				elseif($res[0][1] == 3)
				{
					$_GET['pl_forum_theme'] = $res[0][0];
				}
			}
			elseif(!$res)
			{
				sb_404();
				return;
			}
		}
		elseif(sbPlugins::getSetting('sb_static_urls') == 1 &&
			(isset($_GET['forum_sid']) && $_GET['forum_sid'] == '' || isset($_GET['forum_scid']) && $_GET['forum_scid'] == ''))
		{
			sb_404();
			return;
		}

		if(isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] != '' && sbPlugins::getSetting('sb_static_urls') != 1)
		{
			$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['pl_forum_theme']);
 	      	if (!isset($res[0][0]) || $res[0][0] <= 0)
			{
				sb_404();
				return;
			}
		}

//		id пользователя сообщения которого будут показываться (для компонента "Вывод тем и сообщений автора")
		$id_for_su = 0;
		if (isset($_REQUEST['su_id']) && $_REQUEST['su_id'] != 0)
		{
			$id_for_su = $_REQUEST['su_id'];
		}
		$su_id = (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1);

		$elems_fields = array();
		$categs_fields = array();

		$categs_sql_fields = array();
		$elems_fields_select_sql = '';

		$mess_tags = $us_tags = $tags = array();
		$checked_sql = '';
		$messages = '';

	    if ($fts_checked != '')
	    {
			$fts_checked = explode(' ', $fts_checked);
	    }
	    else
	    {
	        $fts_checked = array();
		}
		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
//      вытаскиваем пользовательские поля
		$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_forum"');
		if ($res)
	    {
	    	if($res[0][0] != '')
	    	{
	            $elems_fields = unserialize($res[0][0]);
	        	$elems_fields = ($elems_fields == '' ? array() : $elems_fields);
	    	}
	    	else
	    	{
	    		$elems_fields = array();
	    	}

	    	if($res[0][1] != '')
	    	{
				$categs_fields = unserialize($res[0][1]);
				$categs_fields = ($categs_fields == '' ? array() : $categs_fields);
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
	                    $mess_tags[] = '{'.$value['tag'].'}';
	                }
	            }
	        }

	        if ($categs_fields)
	        {
	            foreach ($categs_fields as $value)
	            {
	                if (isset($value['sql']) && $value['sql'] == 1)
	                {
	                	$us_tags[] = '{'.$value['tag'].'}';
	                    $categs_sql_fields[] = 'user_f_'.$value['id'];

						if (in_array($value['id'], $fts_checked))
						{
							$checked_sql .= ' AND (c.cat_fields LIKE "%:\"user_f_'.$value['id'].'\";i:1;%" OR c.cat_fields LIKE "%:\"user_f_'.$value['id'].'\";s:1:\"1\";%")';
						}
	                }
	            }
			}
	    }
		$num_cat_fields = count($categs_sql_fields);

//      достаем родительские подразделы и разделы
		$path_values = array();
		$path_tags = array('{CAT_TITLE}', '{CAT_ID}', '{CAT_URL}', '{SUB_CAT_TITLE}', '{SUB_CAT_ID}', '{SUB_CAT_URL}');

//		достаем все родительские разделы и подразелы для всех подразделов и тем
		$path_res = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left < c.cat_left
							AND c2.cat_right > c.cat_right
							AND c.cat_ident="pl_forum"
							AND c2.cat_ident = "pl_forum"
							AND c.cat_level != 0
							AND c2.cat_level != 0
							ORDER BY c.cat_left, c2.cat_left');
		if($path_res)
		{
			foreach($path_res as $value)
			{
				$path_values[$value[0]][] = $value[2];
				$path_values[$value[0]][] = $value[1];
				$path_values[$value[0]][] = $value[3];
			}
		}

//		если нужно выводить темы и сообщения для конкретного пользователя(если функция вызвана для компонента "Вывод сообщений автора")
		$sql_str_for_su = '';
		if($id_for_su > 0)
		{
			if(isset($params['themes_link']) && $params['themes_link'] == 1)
			{
				$sql_str_for_su = ' AND f.f_user_id = "'.$id_for_su.'" ';
			}
			else
			{
				$sql_str_for_su = ' AND c.cat_fields LIKE "%s:10:\"creator_id\";s:'.strlen($id_for_su).':\"'.$id_for_su.'\"%" ';
			}
		}
		elseif($for_su == true)
		{
			if(isset($params['themes_link']) && $params['themes_link'] == 1)
			{
				$sql_str_for_su = ' AND f.f_user_id = "'.$su_id.'" ';
			}
			else
			{
				$sql_str_for_su = ' AND c.cat_fields LIKE "%s:10:\"creator_id\";s:'.strlen($su_id).':\"'.$su_id.'\"%" ';
			}
		}

//		если не установлена галочка "выводить только сообщения" то выводим темы
		if (!isset($params['themes_link']) || $params['themes_link'] != 1)
		{
			$pt_page_list = $pt_begin = $pt_next = $pt_previous = $pt_end = $pt_number = $pt_sel_number = $pt_page_list = $pt_delim = '';
			$pt_perstage = 0;
//			макет дизайн постраничного вывода тем
			if(isset($fts_pagelist_id) && $fts_pagelist_id > 0)
			{
				$res = sbQueryCache::getTemplate('sb_pager_temps', $fts_pagelist_id);

				if(!$res)
				{
					sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
					return;
				}
				list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
			}

//      	если выбран подраздел то вносим в cat_ids айдишник подраздела и проверяем права на раздел, иначе вносим в cat_ids те разделы которые указаны в выводе тем
			if(isset($_GET['pl_forum_sub']) && $_GET['pl_forum_sub'] != '')
			{
				$is_closed = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed="1" AND cat_ident="pl_forum" AND cat_id = ?d', $_GET['pl_forum_sub']);
				if ($is_closed)
				{
					// проверяем права на закрытые разделы и исключаем их из вывода
					$cat_ids = sbAuth::checkRights(array($_GET['pl_forum_sub']), array($_GET['pl_forum_sub']), 'pl_forum_read');
					if(count($cat_ids) == 0)
					{
						if(!isset($_SESSION['sbAuth']))
						{
							$messages = (count($ftm_templates) > 0 ? $ftm_templates['msg_subcateg_opened_for_registry_users'] : '');   //  Подpаздел открыт только для зарегистрированных участников
						}
						else
						{
							$messages = (count($ftm_templates) > 0 ? $ftm_templates['msg_subcateg_opened_for_group_only'] : '');   //    Подраздел открыт только для определенной группы
						}
						$GLOBALS['sbCache']->save('pl_forum', $messages);
						return;
					}
				}
				$cat_ids[] = intval($_GET['pl_forum_sub']);
			}
			else
			{
				$cat_ids = explode('^', $params['ids']);
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

//			достаем темы из подразделов или разделов, которые были выбраны или указаны в выводе тем
			$cats = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_forum"
								AND cat_left >= ANY
								(
									SELECT cat_left FROM sb_categs WHERE cat_ident = "pl_forum" AND cat_id IN (?a)
								)
								AND cat_right <= ANY
								(
									SELECT cat_right FROM sb_categs WHERE cat_ident="pl_forum" AND cat_id IN (?a)
								) AND cat_level = 3', $cat_ids, $cat_ids);

//			создаем новый $cat_ids в котором будут только темы
			if($cats)
			{
				$cat_ids = array();
				foreach($cats as $key => $value)
				{
					$cat_ids[] = $value[0];
				}
			}
			else
			{
				$GLOBALS['sbCache']->save('pl_forum', (count($ftm_templates) > 0 ? $ftm_templates['msg_no_subject_in_categs'] : ''));   //  Нет тем в подразделе
				return;
			}

			$where_str = '';
//			проверяем, есть ли закрытые темы среди тех, которые надо выводить. Если стоит галочка "Выводить закрытые темы".
			if(!(isset($params['close_cat']) && $params['close_cat'] == 1))
			{
				$res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed="1" AND cat_ident="pl_forum" AND cat_id IN (?a)', $cat_ids);
				if ($res)
				{
					// проверяем права на закрытые темы и исключаем их из вывода
					$closed_ids = array();
					foreach ($res as $value)
					{
						$closed_ids[] = $value[0];
					}

//					если закрытые темы выводить не нужно и нет прав на них то добавляем соответствующую строку в sql
					$tmp = count($cat_ids);
					$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_forum_read');
				}

				if(count($cat_ids) == 0)
				{
					if(!isset($_SESSION['sbAuth']))
						$messages = (count($ftm_templates) > 0 ? $ftm_templates['msg_subcateg_opened_for_registry_users'] : '');   //  Подpаздел открыт только для зарегистрированных участников
					else
						$messages = (count($ftm_templates) > 0 ? $ftm_templates['msg_subcateg_opened_for_group_only'] : '');   //    Подраздел открыт только для определенной группы

					$GLOBALS['sbCache']->save('pl_forum', $messages);
					return;
				}
			}

//			если нужно выводить скрытые темы строим sql строку соответствующим образом
			if(!isset($params['hidden_cat']) || $params['hidden_cat'] == 0)
			{
				$where_str .= ' AND c.cat_rubrik="1"';
			}

			@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
			$pager = new sbDBPager($tag_id, $pt_perstage, $fts_perpage);

			// Если выбрана последняя тема в списке разделов и подразделов, то определяем номер страницы на которой находиться тема.

			if(!isset($_GET['page_'.$tag_id]) && isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] != '' && $fts_perpage != 0)
	        {
				$from = false;
				// вытаскиваем страницу, на которой находится нужный нам элемент
				$res = sql_param_query('SELECT c.cat_id, l.link_src_cat_id, MAX(f.f_date) max_date FROM sb_categs c
						LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id
						LEFT JOIN sb_forum f ON f.f_id = l.link_el_id
						WHERE c.cat_ident = "pl_forum"
						AND c.cat_id IN (?a)
						'.$where_str.$checked_sql.$sql_str_for_su.'
						GROUP BY c.cat_id '.($elems_fields_sort_sql != '' ? ' ORDER BY '.sb_substr($elems_fields_sort_sql, 1) : ' ORDER BY c.cat_left'), $cat_ids);

				$pos = false;
				if ($res)
				{
					$pos = 0;
					foreach($res as $key => $value)
					{
						if($_GET['pl_forum_theme'] == $value[0])
						{
							break;
						}
						$pos++;
					}
	            }

				if ($pos !== false)
				{
					$from = ceil(($pos + 1) / $fts_perpage);
				}
				$pager->mPage = $from;
			}

			$themes_total = true;

//			Вывод тем
			$res = $pager->init($themes_total, 'SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_fields, c.cat_left, c.cat_right,
				c.cat_url, MIN(f.f_date), MAX(f.f_date) max_date
				FROM sb_categs c
	            LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id
				LEFT JOIN sb_forum f ON f.f_id = l.link_el_id
				WHERE c.cat_ident = "pl_forum"
				AND c.cat_id IN (?a) '.$where_str.$checked_sql.$sql_str_for_su.'
				GROUP BY c.cat_id '.($elems_fields_sort_sql != '' ? ' ORDER BY '.sb_substr($elems_fields_sort_sql, 1) : ' ORDER BY c.cat_left'), $cat_ids);

			$count_num_themes = $pager->mFrom + 1;

			$count_all_msg_str = '0';
			if(strpos($fts_categs_temps['theme_attach'], '{COUNT_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme_sel'], '{COUNT_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme_read'], '{COUNT_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme_new'], '{COUNT_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme'], '{COUNT_MESSAGES}') !== false)
			{
				$count_all_msg_str = '(
								SELECT COUNT(*) FROM sb_forum fo
								LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
								LEFT JOIN sb_categs categ ON categ.cat_id = li.link_cat_id
								WHERE fo.f_show = 1
			                    AND categ.cat_ident = "pl_forum"
								AND categ.cat_id = c.cat_id) AS count_all_msg';
			}

			$use_su_id = false;
			$new_msg_str = '0';
			if(strpos($fts_categs_temps['theme_attach'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme_sel'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme_read'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme_new'], '{COUNT_NEW_MESSAGES}') !== false ||
				strpos($fts_categs_temps['theme'], '{COUNT_NEW_MESSAGES}') !== false)
			{
				$new_msg_str = '(SELECT COUNT(*)
			                        FROM sb_forum fo
			                        LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
			                        LEFT JOIN sb_categs categ ON categ.cat_id = li.link_cat_id
									LEFT JOIN sb_forum_viewing v ON v.fv_theme_id = categ.cat_id AND v.fv_user_id = ?d
									WHERE fo.f_show = "1"
									AND (fo.f_date > v.fv_date OR v.fv_date IS NULL)
			                        AND categ.cat_ident = "pl_forum"
			                        AND categ.cat_left >= c.cat_left
			                        AND categ.cat_right <= c.cat_right
		                        ) AS new_msg';
				$use_su_id = true;
			}

			if($use_su_id)
			{
				$res_data = sql_param_query('SELECT c.cat_id, c.cat_level, '.$count_all_msg_str.', '.$new_msg_str.'
					FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id LEFT JOIN sb_forum f ON f.f_id = l.link_el_id
					WHERE c.cat_ident = "pl_forum"
					AND cat_id IN (?a) '.$where_str.$checked_sql.$sql_str_for_su, $su_id, $cat_ids);
			}
			else
			{
				$res_data = sql_param_query('SELECT c.cat_id, c.cat_level, '.$count_all_msg_str.', '.$new_msg_str.'
					FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id LEFT JOIN sb_forum f ON f.f_id = l.link_el_id
					WHERE c.cat_ident = "pl_forum"
					AND cat_id IN (?a) '.$where_str.$checked_sql.$sql_str_for_su, $cat_ids);
			}

			$ids_themes = array();

			if ($res_data)
			{
    			foreach($res_data as $key => $value)
    			{
    				if($value[1] == 3)
    				{
    					$ids_themes[$value[0]]['count_msg'] = $value[2]; 		// count_themes
    					$ids_themes[$value[0]]['count_new_msg'] = $value[3]; 	// count_new_themes
    				}
    			}
			}

			$count_them = count($res);
			$theme_creators = array();
			$themes_cats = array();

			if($res)
			{
				foreach($res as $value)
				{
					$values[3] = unserialize($value[3]);
					$theme_creators[$value[0]] = (isset($values[3]['creator_id']) ? $values[3]['creator_id'] : 0);
					$themes_cats[] = $value[0];
				}
			}
			else
			{
				$GLOBALS['sbCache']->save('pl_forum', (count($ftm_templates) > 0 ? $ftm_templates['msg_no_subject_in_categs'] : ''));	//  Нет тем в подразделе
				return;
			}

			if ($pt_page_list != '')
			{
			    // строим список номеров страниц
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

//      	достаем данные авторов тем и формируем массив ($authors)
	        $user_res = sql_param_query('SELECT su_id, su_name, su_login, su_email, su_forum_nick FROM sb_site_users WHERE su_id IN (?a)', $theme_creators);
	        $authors = array();
	        if($user_res)
	        {
				foreach($user_res as $value)
				{
					foreach($theme_creators as $k => $v)
					{
						if($v == $value[0])
						{
							$authors[$k]['id'] = $value[0];
							$authors[$k]['fio'] = $value[1];
							$authors[$k]['login'] = (isset($value[4]) && $value[4] != '' ? $value[4] : $value[2]);
							$authors[$k]['email'] = $value[3];
		            	}
					}
	            }
	        }

//		    достаем данные просмотров всех тем
	        $forum_view = sql_param_query('SELECT fv_theme_id, fv_user_id, fv_date, fv_count_views
											FROM sb_forum_viewing WHERE fv_theme_id IN (?a)', $themes_cats);
	        $forum_viewing = array();
			if($forum_view)
	        {
	            foreach($forum_view as $value)
	            {
					$forum_viewing[$value[0]][$value[1]]['date'] = $value[2];			// для определения того прочитанная тема или новая

					if(isset($forum_viewing[$value[0]]['fv_count_views']))
						$forum_viewing[$value[0]]['fv_count_views'] += $value[3];	// кол-во просмотров темы
					else
						$forum_viewing[$value[0]]['fv_count_views'] = $value[3];	// кол-во просмотров темы
				}
			}

			if(isset($params['page_mess']) && trim($params['page_mess']) != '')
			{
				$more_page = $params['page_mess'];
	        }
	        else
	        {
				$more_page = SB_DOMAIN.$_SERVER['PHP_SELF'];
	        }

		    list($more_page, $more_ext) = sbGetMorePage($more_page);
		}

		$tags = array_merge($us_tags, array('{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}', '{ELEM_NUMBER}', '{DATE}', '{AUTHOR}', '{EMAIL}', '{DESCRIPTION}', '{ICON}', '{COUNT_VIEWS}', '{USER_DATA}', '{LINK}', '{COUNT_MESSAGES}', '{COUNT_NEW_MESSAGES}', '{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}', '{LAST_MSG_TEXT}', '{LAST_MSG_DATE}', '{LAST_MSG_AUTHOR}', '{MESSAGES_LIST}'));  // для тем
		$not_allow_ids = array();

//		Сообщения не выводим только тогда когда нет галочки уст-ть связь с выводом тем и нет соответствующих тегов в макетах дизайна.
		if(isset($params['themes_link']) && $params['themes_link'] == 1 ||
		(
			preg_match('/{MESSAGES_LIST}/', $fts_categs_temps['theme_attach']) ||
			preg_match('/{MESSAGES_LIST}/', $fts_categs_temps['theme_sel']) ||
			preg_match('/{MESSAGES_LIST}/', $fts_categs_temps['theme_read']) ||
			preg_match('/{MESSAGES_LIST}/', $fts_categs_temps['theme_new']) ||
			preg_match('/{MESSAGES_LIST}/', $fts_categs_temps['theme'])
		))
		{
			if(isset($themes_cats))
				$cat_ids = $themes_cats;
			elseif(isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] != '')
				$cat_ids = array($_GET['pl_forum_theme']);
			else
			{
				$cat_ids = explode('^', $params['ids']);
//				достаем все темы указанных разделов
				$cats = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_forum"
								AND cat_left >= ANY
								(
									SELECT cat_left FROM sb_categs WHERE cat_ident = "pl_forum" AND cat_id IN (?a)
								)
								AND cat_right <= ANY
								(
									SELECT cat_right FROM sb_categs WHERE cat_ident="pl_forum" AND cat_id IN (?a)
								) AND cat_level = 3', $cat_ids, $cat_ids);

//				создаем новый $cat_ids в котором будут только темы
				if($cats)
				{
					$cat_ids = array();
					foreach($cats as $key => $value)
					{
						$cat_ids[] = $value[0];
					}
				}
			}

//			достаем данные просмотров всех тем
			$forum_view = sql_param_query('SELECT fv_theme_id, fv_user_id, fv_date, fv_count_views
											FROM sb_forum_viewing WHERE fv_theme_id IN (?a)', $cat_ids);

			$forum_viewing = array();
			if($forum_view)
	        {
	            foreach($forum_view as $value)
	            {
					$forum_viewing[$value[0]][$value[1]]['date'] = $value[2];			// для определения того прочитанная тема или новая

					if(isset($forum_viewing[$value[0]]['fv_count_views']))
						$forum_viewing[$value[0]]['fv_count_views'] += $value[3];	// кол-во просмотров темы
					else
						$forum_viewing[$value[0]]['fv_count_views'] = $value[3];	// кол-во просмотров темы
				}
			}

//			достаем закрытые темы, проверяем права и удаляем их из $cat_ids.
			$res_close = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed="1" AND cat_ident="pl_forum" AND cat_id IN (?a)', $cat_ids);
			if($res_close)
			{
				$closed_ids = array();
				foreach($res_close as $key => $value)
				{
					$closed_ids[] = $value[0];
				}

				$tmp = $cat_ids;
				$cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_forum_read');
				$not_allow_ids = array_diff($tmp, $cat_ids);

				if(isset($_SESSION['sbAuth']) && count($cat_ids) == 0)
				{
					$messages = (count($ftm_templates) > 0 ? $ftm_templates['msg_subject_opened_for_group_only'] : '');	     //    Тема открыта только для определенной группы
				}
				elseif(count($cat_ids) == 0)
				{
					$messages = (count($ftm_templates) > 0 ? $ftm_templates['msg_subject_opened_for_registry_users'] : '');	 //    Тема открыта только для зарегистрированных участников
				}

				if(!isset($themes_cats) && $messages != '')
				{
					$GLOBALS['sbCache']->save('pl_forum', $messages);
					return;
				}
			}
			$pager_msg = '';
			$pt_page_list_msg = '';

//          Когда сообщения выводятся в темах, постраничный вывод сообщений нам не нужен.
			if($fts_pagelist_mess_id > 0 && !isset($themes_cats))
			{
				$pt_begin_msg = $pt_next_msg = $pt_previous_msg = $pt_end_msg = $pt_number_msg = $pt_sel_number_msg = $pt_page_list_msg = $pt_delim = '';
				$pt_perstage_msg = 0;

//              постраничный вывод для сообщений
				$res_pt = sbQueryCache::getTemplate('sb_pager_temps', $fts_pagelist_mess_id);

				if(!$res_pt)
				{
					sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FORUM_PLUGIN), SB_MSG_WARNING);
					return;
				}
				list($pt_perstage_msg, $pt_begin_msg, $pt_next_msg, $pt_previous_msg, $pt_end_msg, $pt_number_msg, $pt_sel_number_msg, $pt_page_list_msg, $pt_delim) = $res_pt[0];

				if(isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] != '')
				{
					$_SERVER['QUERY_STRING'] = ($_SERVER['QUERY_STRING'] != '' ? 'pl_forum_theme='.$_GET['pl_forum_theme'] : 'pl_forum_theme='.$_GET['pl_forum_theme']);
				}

				@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
				$pager_msg = new sbDBPager($tag_id, $pt_perstage_msg, $fts_perpage_messages);
			}

			// формируем SQL-запрос для фильтра
			$elems_fields_filter_sql = '';
			if (isset($params['use_filter']) && $params['use_filter'] == 1)
			{
				$date_temp = '';
				if(isset($_REQUEST['m_f_temp_id']))
		    	{
					$date = sql_param_query('SELECT sffm_fields_temps FROM sb_forum_form_msg WHERE sffm_id = ?d', $_REQUEST['m_f_temp_id']);
					if($date)
					{
						list($sffm_fields_temps) = $date[0];
						$sffm_fields_temps = unserialize($sffm_fields_temps);
						$date_temp = $sffm_fields_temps['date_temps'];
					}
				}

				$morph_db = false;
				if (isset($params['filter_morph']) && $params['filter_morph'] == 1)
				{
					require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
					$morph_db = new sbSearch();
				}

				$elems_fields_filter_sql = '(';

				$elems_fields_filter_sql .= sbGetFilterNumberSql('f.f_id', 'm_f_id', $params['filter_logic']);
				$elems_fields_filter_sql .= sbGetFilterNumberSql('f.f_date', 'm_f_date', $params['filter_logic'], true, $date_temp);
				$elems_fields_filter_sql .= sbGetFilterNumberSql('f.f_user_id', 'm_f_user_id', $params['filter_logic']);

				$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_author', 'm_f_author', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		    	$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_email', 'm_f_email', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
		    	$elems_fields_filter_sql .= sbGetFilterTextSql('f.f_text', 'm_f_text', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

				$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'f', 'm_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
		    }
			if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
				$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
			else
				$elems_fields_filter_sql = '';

			$elems_fields_where_sql = '';
			if ($fts_mess_checked != '')
			{
				$fts_mess_checked = explode(' ', $fts_mess_checked);
				foreach ($fts_mess_checked as $value)
		        {
					$elems_fields_where_sql .= ' AND f.user_f_'.$value.'=1';
		        }
		    }

			$mess_tags = array_merge($mess_tags, array('{MESSAGE_NUMBER}', '{MESSAGE_ID}', '{MESSAGE}', '{MESSAGE_DATE}', '{CHANGE_DATE}', '{FILE}', '{USER_IP}', (isset($fts_categs_temps['theme_link_quote'])? '{LINK_QUOTE}' : '{QUOTE}'), '{AUTHOR}',
						'{EMAIL}', '{ALIAS}', '{SIGNATURE}', '{COUNT_USER_MSG}', '{USER_DATA}', '{THEME_NAME}', '{THEME_ID}', '{THEME_URL}'));

			if(!isset($params['themes_link']) || $params['themes_link'] != 1)
			{
				$sql_str_for_su = '';
			}

			$sort_str = '';
			if (isset($params['sort_mess']) && $params['sort_mess'] != '')
			{
				$sort_str .= ', '.$params['sort_mess'];
				if (isset($params['order_mess']) && $params['order_mess'] != '')
				{
					$sort_str .= ' '.$params['order_mess'];
				}
			}

			$res_mess = false;
			if(count($cat_ids) > 0)
			{
				if(is_object($pager_msg))
				{
					$total = true;
					$res_mess = $pager_msg->init($total, 'SELECT c.cat_id, c.cat_title, c.cat_url, c.cat_fields, f.f_id, f.f_text, f.f_date, f.f_file, f.f_file_name, f.f_user_id, f.f_ip,
									f.f_author, f.f_email, f.f_glued,
									(
										SELECT COUNT(*) FROM sb_forum fo LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
										WHERE fo.f_show="1" AND li.link_cat_id =c.cat_id
									) as count,

									(
										SELECT COUNT(*) FROM sb_forum fo
										LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
										LEFT JOIN sb_forum_viewing v ON v.fv_theme_id = li.link_cat_id AND v.fv_user_id = ?d
										WHERE fo.f_show="1"
										AND li.link_cat_id = c.cat_id
										AND (fo.f_date > v.fv_date OR v.fv_date IS NULL)

									) as new_m, u.su_forum_nick, u.su_forum_text, u.su_id,

									(
										SELECT COUNT(*) FROM sb_forum fo WHERE fo.f_user_id=u.su_id
									) as coun_user_msg, u.su_login, u.su_email

									'.$elems_fields_select_sql.'

									FROM sb_categs c LEFT JOIN sb_catlinks l ON c.cat_id=l.link_cat_id
									LEFT JOIN sb_forum f ON l.link_el_id=f.f_id
									LEFT JOIN sb_site_users u ON u.su_id = f.f_user_id
									WHERE f.f_show = "1"
									'.$elems_fields_where_sql.$elems_fields_filter_sql.$sql_str_for_su.'
									AND l.link_el_id = f.f_id
									AND c.cat_id IN (?a)
									ORDER BY c.cat_left '.
									($sort_str != '' ? $sort_str : ', f.f_date DESC'), $su_id, $cat_ids);

					$count_messages = $pager_msg->mFrom + 1;
				}
				else
				{
					$array_mess = array();
					$res_mess = sql_param_query('SELECT c.cat_id, c.cat_title, c.cat_url, c.cat_fields, f.f_id, f.f_text, f.f_date, f.f_file, f.f_file_name, f.f_user_id, f.f_ip,
									f.f_author, f.f_email, f.f_glued,
									(
										SELECT COUNT(*) FROM sb_forum fo LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
										WHERE fo.f_show="1" AND li.link_cat_id =c.cat_id
									) as count,

									(
										SELECT COUNT(*) FROM sb_forum fo
										LEFT JOIN sb_catlinks li ON li.link_el_id = fo.f_id
										LEFT JOIN sb_forum_viewing v ON v.fv_theme_id = li.link_cat_id AND v.fv_user_id = ?d
										WHERE fo.f_show="1"
										AND li.link_cat_id = c.cat_id
										AND (fo.f_date > v.fv_date OR v.fv_date IS NULL)

									) as new_m, u.su_forum_nick, u.su_forum_text, u.su_id,

									(
										SELECT COUNT(*) FROM sb_forum fo WHERE fo.f_user_id=u.su_id
									) as coun_user_msg, u.su_login, u.su_email

									'.$elems_fields_select_sql.'
									FROM sb_categs c LEFT JOIN sb_catlinks l ON c.cat_id=l.link_cat_id
									LEFT JOIN sb_forum f ON l.link_el_id=f.f_id
									LEFT JOIN sb_site_users u ON u.su_id = f.f_user_id
									WHERE f.f_show = "1"
									'.$elems_fields_where_sql.$elems_fields_filter_sql.$sql_str_for_su.'
									AND l.link_el_id = f.f_id
									AND c.cat_id IN (?a)
									ORDER BY c.cat_left '.
									($sort_str != '' ? $sort_str : ', f.f_date DESC'), $su_id, $cat_ids);

					$count_messages = 1;
				}
			}

			if(!$res_mess && !isset($themes_cats))
			{
				$GLOBALS['sbCache']->save('pl_forum', (count($ftm_templates) > 0 ? $ftm_templates['msg_messages_not_found'] : ''));  //  Нет сообщений в теме
				return;
			}

			$num_fields = count($res_mess[0]);
			if($pt_page_list_msg != '')
			{
				$pager_msg->mBeginTemp = $pt_begin_msg;
				$pager_msg->mBeginTempDisabled = '';
				$pager_msg->mNextTemp = $pt_next_msg;
				$pager_msg->mNextTempDisabled = '';

				$pager_msg->mPrevTemp = $pt_previous_msg;
				$pager_msg->mPrevTempDisabled = '';
				$pager_msg->mEndTemp = $pt_end_msg;
				$pager_msg->mEndTempDisabled = '';

				$pager_msg->mNumberTemp = $pt_number_msg;
				$pager_msg->mCurNumberTemp = $pt_sel_number_msg;
				$pager_msg->mDelimTemp = $pt_delim;
				$pager_msg->mListTemp = $pt_page_list_msg;

				$pt_page_list_msg = $pager_msg->show();
			}
			$dop_tags_mess = array('{MESSAGE_ID}', '{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}');

			$top_bottom_tags = array_merge($us_tags, array('{NUM_LIST}', '{COUNT_MESSAGE}', '{COUNT_NEW_MESSAGE}', '{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}'), $path_tags);
			$top_bottom_values = array();
			$result = '';

			$count = count($res_mess);
			$all_msg = $all_new_msg = $old_t_id = 0;
			$attach_res = $mess_result = '';
			$views = $old_values = array();

			for($i = 0; $i < $count; $i++)
			{
				$cat_fields = unserialize($res_mess[$i][3]);
				$dop_values_mess = array($res_mess[$i][4], $res_mess[$i][1], $res_mess[$i][0], $res_mess[$i][2]);

				$f_file_name = explode('|', $res_mess[$i][8]);
				$f_file = explode('|', $res_mess[$i][7]);

				$options = '';
				if($fts_categs_temps['theme_files_list_options'] != '')
				{
					foreach($f_file as $ke => $val)
					{
						$val = str_replace(SB_DOMAIN.'/', '', $val);
						if($val != '')
						{
							$im = array();
							$size = '';
							if($GLOBALS['sbVfs']->exists($val))
							{
								$im = $GLOBALS['sbVfs']->getimagesize($val);
								$size = $GLOBALS['sbVfs']->filesize($val);
							}
							$options .= str_replace(array('{DESCRIPTION}', '{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}', '{FILE_NAME}', '{FILE_SIZE}', '{FILE_WIDTH}', '{FILE_HEIGHT}', '{FILE_LINK}'),
									array($cat_fields['cat_subject_description'], $res_mess[$i][1], $res_mess[$i][0], $res_mess[$i][2], $f_file_name[$ke], (isset($size) ? $size : '') , (isset($im[0]) ? $im[0] : ''), (isset($im[1]) ? $im[1] : '') , '/'.$val),
									$fts_categs_temps['theme_files_list_options']);    //    ПРИКРЕПЛЕННЫЙ ФАЙЛ
						}
					}
				}

				$mess_values = array();
				$val = array();

				if ($num_fields > 22)
				{
					for ($in = 22; $in < $num_fields; $in++)
		            {
						$val[] = $res_mess[$i][$in];
		            }
					$mess_values = sbLayout::parsePluginFields($elems_fields, $val, $fts_user_fields_temps, $dop_tags_mess, $dop_values_mess, $fts_lang);
				}

				$mess_values[] = $count_messages++;	//	MESSAGE_NUMBER
				$mess_values[] = $res_mess[$i][4];    //  MESSAGE_ID
				if(trim($res_mess[$i][5]) != '' && isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				{
					$res_mess[$i][5] = sbProgParseBBCodes($res_mess[$i][5], $fts_messages_temps['quote_top'], $fts_messages_temps['quote_bottom']); //  MESSAGE
				}

				if($res_mess[$i][5] != '')
				{
					$mess_values[] = $res_mess[$i][5];   //   MESSAGE
				}
				else
				{
					$mess_values[] = '';          //   MESSAGE
				}

				$mess_values[] = sb_parse_date($res_mess[$i][6], $fts_messages_temps['mess_date_filed'], $fts_lang) ;    				//  MESSAGE_DATE
				// Дата последнего изменения
		        if((sb_strpos($fts_messages_temps['element_new'], '{CHANGE_DATE}') !== false) || (sb_strpos($fts_messages_temps['element'], '{CHANGE_DATE}') !== false) || (sb_strpos($fts_messages_temps['element_attach'], '{CHANGE_DATE}') !== false))
		        {
		        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $res_mess[$i][4],'pl_forum');
		        	$mess_values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $fts_messages_temps['mess_date_change_filed'], $fts_lang) : ''; //   CHANGE_DATE
		        }
		        else
		       	{
		        	$mess_values[] = '';
		       	}
				$mess_values[] = isset($options) && $options != '' ? str_replace(array('{FILES_LIST}'), array($options), $fts_categs_temps['theme_files_list_field']) : '';  	//  FILE
				$mess_values[] = $res_mess[$i][10];   //  USER_IP


                $link_quote = isset($fts_categs_temps['theme_link_quote'])? $fts_categs_temps['theme_link_quote'] : '{QUOTE}'; //Для совместимости со старыми макетами
                if(isset($cat_fields['cat_theme_closed']) && $cat_fields['cat_theme_closed'] == 1)
                {
                    $mess_values[] = '';
                }
                else
                {
                    $hash = md5('pl_forum_mess_h_'.$res_mess[$i][4]);
                    $url = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'].(isset($_GET['sb_search']) && $_GET['sb_search'] == 1 ? '' : '&mess_hash='.$hash.'&mess_id='.$res_mess[$i][4]) : (isset($_GET['sb_search']) && $_GET['sb_search'] == 1 ? '' : '?mess_hash='.$hash).'&mess_id='.$res_mess[$i][4]);
                    $mess_values[] = str_replace('{QUOTE}', $url, $link_quote);
                }

				if(isset($res_mess[$i][16]) && $res_mess[$i][16] != '')
					$author = $res_mess[$i][16];
				elseif(isset($res_mess[$i][20]) && $res_mess[$i][20] != '')
					$author = $res_mess[$i][20];
				elseif(isset($res_mess[$i][11]) && $res_mess[$i][11] != '')
					$author = $res_mess[$i][11];

				$mess_values[] = isset($author) && $author != '' ? str_replace(array_merge($dop_tags_mess, array('{AUTHOR}')), array_merge($dop_values_mess, array($author)), $fts_messages_temps['mess_author_filed']) : ''; 	//  AUTHOR

				if(isset($res_mess[$i][21]) && $res_mess[$i][21] != '')
					$email = $res_mess[$i][21];
				elseif(isset($res_mess[$i][12]) && $res_mess[$i][12] != '')
					$email = $res_mess[$i][12];

				$mess_values[] = isset($email) && $email != '' ? str_replace(array_merge($dop_tags_mess, array('{EMAIL}')), array_merge($dop_values_mess, array($email)), $fts_messages_temps['mess_email_filed']) : '';   	//  EMAIL
				$mess_values[] = isset($res_mess[$i][16]) ? $res_mess[$i][16] : '';		//	ALIAS

				if(trim($res_mess[$i][17]) != '' && isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				{
					$res_mess[$i][17] = sbProgParseBBCodes($res_mess[$i][17]); //	SIGNATURE
				}

				$mess_values[] = $res_mess[$i][17];		//	SIGNATURE
				$mess_values[] = $res_mess[$i][19];		//	COUNT_USER_MSG

				if($fts_user_data_mess_id > 0 && isset($res_mess[$i][9]) && $res_mess[$i][9] > 0)
				{
					require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
					$mess_values[] = fSite_Users_Get_Data($fts_user_data_mess_id, $res_mess[$i][9]);    //   USER_DATA
				}
				else
				{
					$mess_values[] = '';	//	USER_DATA
				}

				$mess_values[] = $res_mess[$i][1];    //  THEME_NAME
				$mess_values[] = $res_mess[$i][0];    //  THEME_ID
				$mess_values[] = $res_mess[$i][2];    //  THEME_URL

				$cat_values = array();
				if ($num_cat_fields > 0)
				{
					foreach ($categs_sql_fields as $cat_field)
					{
	                    if (isset($cat_fields[$cat_field]))
	                        $cat_values[] = $cat_fields[$cat_field];
	                    else
							$cat_values[] = null;
					}
					$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $fts_user_categs_temps, array(), array(), $fts_lang);
					$top_bottom_values = $cat_values;
				}

				$top_bottom_values = array_merge($top_bottom_values, array($pt_page_list_msg, $res_mess[$i][14], ($su_id > 0 ? $res_mess[$i][15] : 0), $res_mess[$i][1], $res_mess[$i][0], $res_mess[$i][2]), isset($path_values[$res_mess[$i][0]]) ? $path_values[$res_mess[$i][0]] : array());
				if($res_mess[$i][0] != $old_t_id && $old_t_id != 0)
				{
					$all_new_msg += $old_all_new_msg;

					$top = str_replace($top_bottom_tags, $old_values, $fts_messages_temps['top']);
					$bottom = str_replace($top_bottom_tags, $old_values, $fts_messages_temps['bottom']);
					if(!isset($params['themes_link']) || $params['themes_link'] != 1)
					{
						$array_mess[$old_t_id] = $top.$attach_res.$result.$bottom;
					}
					else
					{
						$mess_result .= $top.$result.$bottom;
						$views[] = $old_t_id;
					}
					$attach_res = $result = '';
				}

				if($res_mess[$i][13] == 1)
				{
					$attach_res .= str_replace($mess_tags, $mess_values, $fts_messages_temps['element_attach']);
				}
				elseif((!isset($forum_viewing[$res_mess[$i][0]][$su_id]) || $res_mess[$i][6] > $forum_viewing[$res_mess[$i][0]][$su_id]['date']) && $su_id > 0)
				{
					$result .= str_replace($mess_tags, $mess_values, $fts_messages_temps['element_new']);
				}
				else
				{
					$result .= str_replace($mess_tags, $mess_values, $fts_messages_temps['element']);
				}

				if(($count - $i) == 1)
				{
					$all_new_msg += ($su_id > 0 ? $res_mess[$i][15] : 0);
					$top = str_replace($top_bottom_tags, $top_bottom_values, $fts_messages_temps['top']);
					$bottom = str_replace($top_bottom_tags, $top_bottom_values, $fts_messages_temps['bottom']);

					if(isset($params['themes_link']) && $params['themes_link'] == 1)
					{
						$mess_result .= $top.$attach_res.$result.$bottom;
						$views[] = $res_mess[$i][0];
					}
					else
					{
						$array_mess[$res_mess[$i][0]] = $top.$attach_res.$result.$bottom;
					}
				}

				$old_t_id = $res_mess[$i][0];
				$old_all_new_msg = $res_mess[$i][15];

				$old_values = $top_bottom_values;
				$top_bottom_values = array();
			}

			$common_top = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ALL_NEW_COUNT}'), $path_tags), array_merge(array($pt_page_list_msg, $count, $all_new_msg), (isset($path_values[$res_mess[0][0]]) ? $path_values[$res_mess[0][0]] : array())), isset($fts_messages_temps['common_top']) ? $fts_messages_temps['common_top'] : '');
			$common_bottom = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ALL_NEW_COUNT}'), $path_tags), array_merge(array($pt_page_list_msg, $count, $all_new_msg), (isset($path_values[$res_mess[0][0]]) ? $path_values[$res_mess[0][0]] : array())), isset($fts_messages_temps['common_bottom']) ? $fts_messages_temps['common_bottom'] : '');
			$result = $mess_result != '' ? $common_top.$mess_result.$common_bottom : '';
		}

//		если не установлена галочка устанавливать связь сообщений с выводом тем
		if(!isset($params['themes_link']) || $params['themes_link'] != 1)
		{
			$parent_theme_url = $parent_theme_title = $parent_theme_id = '';

			$count_new_themes = 0;
			$dop_tags = array('{SUBCAT_TITLE}', '{SUBCAT_ID}', '{SUBCAT_URL}', '{THEME_TITLE}', '{THEME_ID}', '{THEME_URL}', '{LINK}');   // доп теги для тем (на вкладке "поля тем")

			$res_cat_themes = $res_themes = '';
			$tmp_message = '';
			$views = array();
			$parent_theme_fields = array();
			$all_new_count = $old_parent_id = 0;

			$i = 1;
			$theme_cat_tags = array_merge(array('{SYSTEM_MESSAGES}', '{NUM_LIST}','{COUNT_THEMES}', '{COUNT_NEW_THEMES}', '{SUB_CAT_TITLE}', '{SUB_CAT_ID}', '{SUB_CAT_URL}'), $us_tags, $path_tags);

			foreach($res as $key => $value)
			{
				$values = array ();
				$last = sql_param_query('SELECT f.f_text, f.f_date, f.f_author, su.su_forum_nick, su.su_login
								FROM sb_categs c, sb_forum f LEFT JOIN sb_site_users su ON f.f_user_id=su.su_id, sb_catlinks l
								WHERE c.cat_level = "3" AND c.cat_ident = "pl_forum"
								AND c.cat_id = l.link_cat_id AND l.link_el_id = f.f_id
								AND c.cat_left >= ?d AND c.cat_right <= ?d
                                AND f.f_show = 1
								ORDER BY f.f_date DESC LIMIT 0, 1', $value[4], $value[5]);

//              если данные родительского подраздела не актуальны для текущей темы или если данных о родительском разделе нет
				if(!(isset($parent_theme_left) && $parent_theme_left < $value[4] && isset($parent_theme_right) && $parent_theme_right > $value[5]) ||
				$parent_theme_id == '' && $parent_theme_title == '' && $parent_theme_url == '')
				{
					$parent_data = sql_param_query('SELECT cat_id, cat_title, cat_url, cat_left, cat_right, cat_fields
									FROM sb_categs
									WHERE cat_left < ?d AND cat_right > ?d AND cat_level = 2 AND cat_ident="pl_forum"',$value[4], $value[5]);

					$parent_theme_title = $parent_data[0][1];
					$parent_theme_id = $parent_data[0][0];
					$parent_theme_url = $parent_data[0][2];

					$parent_theme_left = $parent_data[0][3];
					$parent_theme_right = $parent_data[0][4];
					$parent_theme_fields = unserialize($parent_data[0][5]);

					$count_new_themes = 0;
					$count_themes = 0;
				}

				$query_str = ($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
				if(!isset($params['page_mess']) || $params['page_mess'] == '')
    			{
    				if (isset($_GET['page_'.$tag_id]))
						$query_str .= ($query_str != '' ? '&' : '?').'page_'.$tag_id.'='.$_GET['page_'.$tag_id];
    			}

				if (trim($value[6]) == '' || $more_page == '')
				{
					$href = 'javascript: void(0);';
				}
				else
				{
					$href = $more_page;
					if (sbPlugins::getSetting('sb_static_urls') == 1)
					{
						// ЧПУ
						if(!isset($params['page_mess']) || $params['page_mess'] == '' ||
						(isset($params['page_mess']) && sb_strpos($params['page_mess'], SB_COOKIE_DOMAIN.$_SERVER['PHP_SELF']) !== false ))
    					{
							$query_str .= ($query_str != '' ? '&' : '?').'pl_forum_theme='.$value[0];

							if(isset($_GET['forum_scid']) && $_GET['forum_scid'] != '')
							{
								$href .= $_GET['forum_scid'].'/'.($parent_theme_url != '' ? urlencode($parent_theme_url) : $parent_theme_id).($more_ext != 'php' ? '.'.$more_ext : '/').$query_str;
							}
							else
							{
								$href = 'javascript: void(0);';
							}
    					}
    					else
    					{
							$href .= ($parent_theme_url != '' ? $parent_theme_url.'/' : $parent_theme_id.'/').
								($value[6] != '' ? $value[6] : $value[0]).($more_ext != 'php' ? '.'.$more_ext : '/');
    					}
					}
					else
					{
						if (isset($_GET['pl_forum_sub']) && ((!isset($params['page_mess']) || $params['page_mess'] == '') ||
							(isset($params['page_mess']) && sb_strpos($params['page_mess'], SB_COOKIE_DOMAIN.$_SERVER['PHP_SELF']) !== false)))
						{
							$query_str .= ($query_str != '' ? '&' : '?').'pl_forum_sub='.$_GET['pl_forum_sub'];
						}

						$query_str .= ($query_str != '' ? '&' : '?').'pl_forum_theme='.$value[0];
						$href = (isset($params['page_mess']) && $params['page_mess'] != '' ? $params['page_mess'] : $_SERVER['PHP_SELF']).$query_str;
					}
				}

//				если тема новая увеличиваем счетчик новых тем
				if((!isset($forum_viewing[$value[0]][$su_id]) || intval($value[8]) > $forum_viewing[$value[0]][$su_id]['date']) && $su_id > 0)
				{
		        	++$all_new_count;
					++$count_new_themes;
				}
				++$count_themes;

				$dop_values = array($parent_theme_title, $parent_theme_id, $parent_theme_url, $value[1], $value[0], $value[6], $href);
				$categs = unserialize($value[3]);

				$cat_values = array();
				if ($num_cat_fields > 0)
		        {
	                foreach ($categs_sql_fields as $cat_field)
	                {
	                    if (isset($categs[$cat_field]))
	                        $cat_values[] = $categs[$cat_field];
	                    else
	                        $cat_values[] = null;
					}

					$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $fts_user_categs_temps, $dop_tags, $dop_values, $fts_lang);
					$values = array_merge($values, $cat_values);
		        }

				$values[] = $value[1];    //  THEME_TITLE
				$values[] = $value[0];    //  THEME_ID
				$values[] = $value[6];    //  THEME_URL
				$values[] = $count_num_themes++;    //  ELEM_NUMBER
				$values[] = sb_parse_date((isset($value[7]) ? $value[7] : time()), $fts_categs_temps['theme_date_field'], $fts_lang) ;   //  {DATE}

				$author = '';
				if(isset($authors[$value[0]]['login']) && $authors[$value[0]]['login'] != '')
				{
					$author = $authors[$value[0]]['login'];
				}
				elseif(isset($authors[$value[0]]['fio']) && $authors[$value[0]]['fio'] != '')
				{
					$author = $authors[$value[0]]['fio'];
				}

				if(isset($authors[$value[0]]['email']) && $authors[$value[0]]['email'] != '')
				{
					$email = $authors[$value[0]]['email'];
				}
				else
				{
					$email = '';
				}

				$values[] = isset($author) && $author != '' ? str_replace(array_merge($dop_tags, array('{AUTHOR}')), array_merge($dop_values, array($author)), $fts_categs_temps['theme_author_field']) : '';   //   {AUTHOR}
				$values[] = isset($email) && $email != '' ? str_replace(array_merge($dop_tags, array('{EMAIL}')), array_merge($dop_values, array($email)), $fts_categs_temps['theme_email_field']) : '';   //  {EMAIL}
				$values[] = isset($categs['cat_subject_description']) && $categs['cat_subject_description'] != '' ? str_replace(array_merge($dop_tags, array('{DESCRIPTION}')), array_merge($dop_values, array($categs['cat_subject_description'])), $fts_categs_temps['theme_descr_field']) : '';  // {DESCRIPTION}

                //{ICON}
                if(isset($categs['cat_theme_closed']) && $categs['cat_theme_closed'] == 1)
                {
                    $values[] = isset($categs['cat_close_image']) && $categs['cat_close_image'] != '' ? str_replace(array_merge($dop_tags, array('{CAT_ICON}')), array_merge($dop_values, array($categs['cat_close_image'])), $fts_categs_temps['theme_icon_field']) : '';
                }
                else
                {
                    $values[] = isset($categs['cat_image']) && $categs['cat_image'] != '' ? str_replace(array_merge($dop_tags, array('{CAT_ICON}')), array_merge($dop_values, array($categs['cat_image'])), $fts_categs_temps['theme_icon_field']) : '';
                }
				$values[] = isset($forum_viewing[$value[0]]['fv_count_views']) ? $forum_viewing[$value[0]]['fv_count_views'] : 0 ;   // {COUNT_VIEWS}

				if($fts_user_data_themes_id > 0 && isset($authors[$value[0]]['id']) && $authors[$value[0]]['id'] > 0)
				{
					require_once (SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
					$values[] = fSite_Users_Get_Data($fts_user_data_themes_id, $authors[$value[0]]['id']);     //   {USER_DATA}
				}
				else
				{
					$values[] = '';   //   USER_DATA
				}

				$values[] = $href;     				  // {LINK}
	            $values[] = isset($ids_themes[$value[0]]['count_msg']) ? $ids_themes[$value[0]]['count_msg'] : '0';	// {COUNT_MESSAGES}

				if($su_id > 0)
					$values[] = isset($ids_themes[$value[0]]['count_new_msg']) ? $ids_themes[$value[0]]['count_new_msg'] : '0'; // {COUNT_NEW_MESSAGES}
				else
					$values[] = '0';

	            $values[] = $parent_theme_title;      // SUBCAT_TITLE
	            $values[] = $parent_theme_id;         // SUBCAT_ID
				$values[] = $parent_theme_url;        // SUBCAT_URL

				if(isset($last[0][0]) && trim($last[0][0]) != '' && isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				{
					$last[0][0] = sbProgParseBBCodes($last[0][0], $fts_messages_temps['quote_top'], $fts_messages_temps['quote_bottom']); //  MESSAGE
				}

				$values[] = isset($last[0][0]) ? $last[0][0] : '';		//		LAST_MSG_TEXT
				$values[] = isset($last[0][1]) ? sb_parse_date($last[0][1], $fts_messages_temps['mess_date_filed'], $fts_lang) : '' ;	//	LAST_MSG_DATE

				if(isset($last[0][3]) && $last[0][3] != '')
					$author = $last[0][3];
				elseif(isset($last[0][4]) && $last[0][4] != '')
					$author = $last[0][4];
				elseif(isset($last[0][2]) && $last[0][2] != '')
					$author = $last[0][2];

				$values[] = isset($author) ? $author : '' ;		//		LAST_MSG_AUTHOR

				if(in_array($value[0], $not_allow_ids) && isset($_GET['pl_forum_theme']) && $value[0] == $_GET['pl_forum_theme'])
				{
					if(isset($_SESSION['sbAuth']))
					{
						$tmp_message = (count($ftm_templates) > 0 ? $ftm_templates['msg_subject_opened_for_group_only'] : '');	     //    Тема открыта только для определенной группы
					}
					else
					{
						$tmp_message = (count($ftm_templates) > 0 ? $ftm_templates['msg_subject_opened_for_registry_users'] : '');	 //    Тема открыта только для зарегистрированных участников
					}
					$values[] = '';
				}
				else
				{
					if(isset($array_mess[$value[0]]))
					{
						$values[] = $array_mess[$value[0]];		//	MESSAGES_LIST
					}
					elseif(isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] == $value[0])
					{
						$values[] = '';		//	MESSAGES_LIST
						$tmp_message = isset($ftm_templates['msg_messages_not_found']) && $ftm_templates['msg_messages_not_found'] != '' ? $ftm_templates['msg_messages_not_found'] : '';    //Нет сообщений
					}
				}

			    if ($num_cat_fields > 0)
		        {
					$cat_values = array();
	                foreach($categs_sql_fields as $cat_field)
	                {
						if (isset($parent_theme_fields[$cat_field]))
							$cat_values[] = $parent_theme_fields[$cat_field];
	                    else
	                        $cat_values[] = null;
					}

					$cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $fts_user_categs_temps, $dop_tags, $dop_values, $fts_lang);
		        }

				$theme_cat_values = array_merge(array($messages, $pt_page_list, $count_themes, $count_new_themes, $parent_theme_title, $parent_theme_id, $parent_theme_url), $cat_values, isset($path_values[$parent_theme_id]) ? $path_values[$parent_theme_id] : array());
				if ($old_parent_id != $parent_theme_id && $old_parent_id != 0)
				{
					$res_themes .= str_replace($theme_cat_tags, $old_parent_values, $fts_categs_temps['top']).$res_cat_themes.
								str_replace($theme_cat_tags, $old_parent_values, $fts_categs_temps['bottom']);
					$res_cat_themes = '';
		        }

				if(isset($categs['cat_subject_main']) && $categs['cat_subject_main'] == 1)
				{
					$tmp = str_replace($tags, $values, $fts_categs_temps['theme_attach']);          //    Приклеенная тема
					$res_cat_themes = $tmp.$res_cat_themes;

					if (strpos($fts_categs_temps['theme_attach'], '{MESSAGES_LIST}') && $tmp_message == '' && isset($array_mess[$value[0]]) && $array_mess[$value[0]] != '')
						$views[] = $value[0];
				}
				elseif((isset($_GET['pl_forum_theme']) && $_GET['pl_forum_theme'] == $value[0]))
				{
					$res_cat_themes .= str_replace($tags, $values, $fts_categs_temps['theme_sel']);     //     Тема (выбранная)
					if(strpos($fts_categs_temps['theme_sel'], '{MESSAGES_LIST}') && $tmp_message == '' && isset($array_mess[$value[0]]) && $array_mess[$value[0]] != '')
						$views[] = $value[0];
				}
				elseif(isset($forum_viewing[$value[0]][$su_id]) && intval($value[8]) < intval($forum_viewing[$value[0]][$su_id]['date']) && $su_id > 0)
				{
					$res_cat_themes .= str_replace($tags, $values, $fts_categs_temps['theme_read']);    //     Прочитанная тема
					if(strpos($fts_categs_temps['theme_read'], '{MESSAGES_LIST}') && $tmp_message == '' && isset($array_mess[$value[0]]) && $array_mess[$value[0]] != '')
						$views[] = $value[0];
				}
				elseif((!isset($forum_viewing[$value[0]][$su_id]) || intval($value[8]) > intval($forum_viewing[$value[0]][$su_id]['date'])) && $su_id > 0)
				{
					$res_cat_themes .= str_replace($tags, $values, $fts_categs_temps['theme_new']);     //    Тема (новая)
					if(strpos($fts_categs_temps['theme_new'], '{MESSAGES_LIST}') && $tmp_message == '' && isset($array_mess[$value[0]]) && $array_mess[$value[0]] != '')
						$views[] = $value[0];
				}
				else
				{
					$res_cat_themes .= str_replace($tags, $values, $fts_categs_temps['theme']);         //     Тема
					if(strpos($fts_categs_temps['theme'], '{MESSAGES_LIST}') && $tmp_message == '' && isset($array_mess[$value[0]]) && $array_mess[$value[0]] != '')
						$views[] = $value[0];
				}

				if($i == $count_them)
				{
					$res_themes .= str_replace($theme_cat_tags, $theme_cat_values, $fts_categs_temps['top']).$res_cat_themes.
							str_replace($theme_cat_tags, $theme_cat_values, $fts_categs_temps['bottom']);
				}

				$i++;
			    $old_parent_id = $parent_theme_id;
			    $old_parent_values = $theme_cat_values;
			}

			$messages .= $tmp_message;
			$theme_cat_values = array_merge(array($messages, $pt_page_list, $count_them, $all_new_count, $parent_theme_title, $parent_theme_id, $parent_theme_url), $cat_values, isset($path_values[$parent_theme_id]) ? $path_values[$parent_theme_id] : array());

			$tmp = str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['common_top']) ? $fts_categs_temps['common_top'] : '');
			$result = $tmp.$res_themes.str_replace($theme_cat_tags, $theme_cat_values, isset($fts_categs_temps['common_bottom']) ? $fts_categs_temps['common_bottom'] : ''); //BOTTOM
		}

//		если в результате вывода есть что нибудь то вносим запись в таблицу просмотров тем
		if(($result != '' || isset($array_mess) && count($array_mess) > 0) && $messages == '')
		{

			static $is_view = false;//если на странице связанно несколько компонентов вывода тем. нужно считать просмотры только один раз.

//			если страницу просматривает не робот
			$robot = sbIsSearchRobot();

			if(!$robot && count($views) > 0 && $is_view === false)
		    {
				$update = array();
				$exist_viewing = sql_param_query('SELECT fv_theme_id FROM sb_forum_viewing WHERE fv_theme_id IN (?a) AND fv_user_id = ?d',$views, $su_id);
				if($exist_viewing)
				{
					foreach($exist_viewing as $key => $value)
					{
						$update[] = $value[0];
					}
				}

				foreach($views as $value)
				{
					if(in_array($value, $update))
					{
						sql_param_query('UPDATE sb_forum_viewing SET fv_count_views = fv_count_views + 1, fv_date = ?d WHERE fv_theme_id = ?d AND fv_user_id =?d', time(), $value, $su_id);
					}
					else
					{
						$row = array();
						$row['fv_date'] = time();
						$row['fv_count_views'] = 1;
						$row['fv_theme_id'] = $value;
						$row['fv_user_id'] = $su_id;

						sql_param_query('INSERT INTO sb_forum_viewing SET ?a ', $row);
					}
				}

				$is_view = true;
			}
		}

		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
		$GLOBALS['sbCache']->save('pl_forum', $result);
	}

	/**
	 *
	 *  Вывод формы фильтра
	*/
	function fForum_Elem_Forum_Filter($el_id, $temp_id, $params, $tag_id)
	{
		if ($GLOBALS['sbCache']->check('pl_forum', $tag_id, array($el_id, $temp_id, $params)))
			return;

		$res = sql_param_query('SELECT sffm_id, sffm_title, sffm_lang, sffm_text, sffm_fields_temps
								FROM sb_forum_form_msg WHERE sffm_id=?d', $temp_id);
		if (!$res)
		{
			sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
	    }

		list($sffm_id, $sffm_title, $sffm_lang, $sffm_text, $sffm_fields_temps) = $res[0];

		$params = unserialize(stripslashes($params));
		$sffm_fields_temps = unserialize($sffm_fields_temps);

		$result = '';
		if (trim($sffm_text) == '')
		{
			$GLOBALS['sbCache']->save('pl_forum', '');
			return;
		}

		$tags = array('{ACTION}', '{TEMP_ID}', '{ID}', '{ID_LO}', '{ID_HI}', '{DATE}', '{DATE_LO}', '{DATE_HI}', '{AUTHOR}', '{EMAIL}', '{TEXT}');
		if (isset($params['page']) && trim($params['page']) != '')
		{
			$action = $params['page'];
		}
		else
		{
			$action = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
		}

		//	вывод полей формы
		$values[] = $action;
		$values[] = $sffm_id;
		$values[] = (isset($sffm_fields_temps['msg_id']) && $sffm_fields_temps['msg_id'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['m_f_id']) && $_REQUEST['m_f_id'] != '' ? $_REQUEST['m_f_id'] : ''), $sffm_fields_temps['msg_id']) : '';
		$values[] = (isset($sffm_fields_temps['msg_id_lo']) && $sffm_fields_temps['msg_id_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['m_f_id_lo']) && $_REQUEST['m_f_id_lo'] != '' ? $_REQUEST['m_f_id_lo'] : ''), $sffm_fields_temps['msg_id_lo']) : '';
		$values[] = (isset($sffm_fields_temps['msg_id_hi']) && $sffm_fields_temps['msg_id_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['m_f_id_hi']) && $_REQUEST['m_f_id_hi'] != '' ? $_REQUEST['m_f_id_hi'] : ''), $sffm_fields_temps['msg_id_hi']) : '';
		$values[] = (isset($sffm_fields_temps['msg_date']) && $sffm_fields_temps['msg_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['m_f_date']) && $_REQUEST['m_f_date'] != '' ? $_REQUEST['m_f_date'] : ''), $sffm_fields_temps['msg_date']) : '';
		$values[] = (isset($sffm_fields_temps['msg_date_lo']) && $sffm_fields_temps['msg_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['m_f_date_lo']) && $_REQUEST['m_f_date_lo'] != '' ? $_REQUEST['m_f_date_lo'] : ''), $sffm_fields_temps['msg_date_lo']) : '';
		$values[] = (isset($sffm_fields_temps['msg_date_hi']) && $sffm_fields_temps['msg_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['m_f_date_hi']) && $_REQUEST['m_f_date_hi'] != '' ? $_REQUEST['m_f_date_hi'] : ''), $sffm_fields_temps['msg_date_hi']) : '';
		$values[] = (isset($sffm_fields_temps['msg_author']) && $sffm_fields_temps['msg_author'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['m_f_author']) && $_REQUEST['m_f_author'] != '' ? $_REQUEST['m_f_author'] : ''), $sffm_fields_temps['msg_author']) : '');
		$values[] = (isset($sffm_fields_temps['msg_email']) && $sffm_fields_temps['msg_email'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['m_f_email']) && $_REQUEST['m_f_email'] != '' ? $_REQUEST['m_f_email'] : ''), $sffm_fields_temps['msg_email']) : '');
		$values[] = (isset($sffm_fields_temps['msg_text']) && $sffm_fields_temps['msg_text'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['m_f_text']) && $_REQUEST['m_f_text'] != '' ? $_REQUEST['m_f_text'] : ''), $sffm_fields_temps['msg_text']) : '');

		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		sbLayout::parsePluginInputFields('pl_forum', $sffm_fields_temps, $sffm_fields_temps['date_temps'], $tags, $values, -1, 'sb_forum', 'f_id', array(), array(), false, 'm_f', '', true);

		$result = str_replace($tags, $values, $sffm_text);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

		$GLOBALS['sbCache']->save('pl_forum', $result);
	}

if(!function_exists('sbIsSearchRobot'))
{
	function sbIsSearchRobot()
	{
	//	если нет user_agent-а то полюбому считаем посетителя роботом.
		if(!isset($_SERVER['HTTP_USER_AGENT']))
		{
			return true;
		}

		$robots = sql_query('SELECT sr_robot FROM sb_search_robots');
		if($robots)
	    {
		    foreach ($robots[0] as $robot)
		    {
		        if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != '' && stristr($_SERVER['HTTP_USER_AGENT'], $robot))
		        {
		            return true;
		        }
			}
		}
		return false;
	}
}

/**
 * Фунцкция возвращает массив e-mail адресов модераторов для определенных разделов.
 *
 * @param array $catIds Идентификаторы разделов.
 * @param array $params Параметры компонента.
 *
 * @return array
 */
function getForumModerators(array $catIds, array $params)
{
    $mod_emails = array();

    $mod_params_emails = explode(' ', trim(str_replace(',', ' ', $params['mod_emails'])));
    $mod_categs_emails = array();
    $mod_users_emails = array();

    $res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id IN (?a)', $catIds);

    if ($res)
    {
        $cat_mod_ids = $u_ids = array();
        foreach($res as $value)
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

    return $mod_emails;
}

?>