<?php

/**
 * Вывод миникорзины
 *
 */
function  fBasket_Elem_Mini_Form($el_id, $temp_id, $params, $tag_id)
{
	//	достаем макеты дизайна
	$res = sbQueryCache::getTemplate('sb_plugins_temps_form', $temp_id);
	if(!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_BASKET_PLUGIN), SB_MSG_ERROR);
		return;
	}
	list($ptf_title, $ptf_form, $ptf_messages, $ptf_user_data_id) = $res[0];

	$params = unserialize(stripslashes($params));
	$ptf_messages = unserialize($ptf_messages);

	$b_id_user = -1;
	$b_hash = '';
	if(isset($_SESSION['sbAuth']))
	{
		$b_id_user = $_SESSION['sbAuth']->getUserId();

		// Если у зарегистрированного пользователя есть кука, значит есть заказы. Переводим их к зарегистрированному пол-ю.
		if(isset($_COOKIE['pl_basket_user_id']))
		{
			if(!sql_param_query('UPDATE sb_basket SET b_id_user=?d, b_hash=? WHERE (b_id_user=?d OR b_hash = ?) AND (b_domain=? OR b_domain=?)', $b_id_user, $_COOKIE['pl_basket_user_id'], $b_id_user, $_COOKIE['pl_basket_user_id'], 'all', SB_COOKIE_DOMAIN))
			{
				sb_add_system_message(KERNEL_PROG_PLUGIN_BASKET_ERROR_UPDATE_ID_USER);
            }
    	}
	}
	elseif(isset($_COOKIE['pl_basket_user_id']))
	{
		$b_hash = preg_replace('/[^A-Za-z0-9]+/', '', $_COOKIE['pl_basket_user_id']);
	}
	else
	{
		echo eval(' ?>'.(isset($ptf_messages['no_elems']) ? $ptf_messages['no_elems'] : '').'<?php ');
		return;
	}

	$sum_discount = $sum = $count_overall = $pos_count = 0;
	$where_str = '';
	if($b_id_user > 0)
	{
		$where_str = ' AND b_id_user = ?d';
		$where_arg = $b_id_user;
	}
	elseif(trim($b_hash) != '')
	{
		$where_str = ' AND b_hash = ?';
		$where_arg = $b_hash;
	}
	else
	{
		return;
	}

    if(!isset($params['plugins']) || $params['plugins'] == '')
    {
        $res = sql_param_query('SELECT b_id_mod, b_id_el, b_count_el, b_date, b_reserved, b_prop, b_discount FROM sb_basket WHERE b_reserved = 0 AND (b_domain=? OR b_domain=?) '.$where_str, 'all', SB_COOKIE_DOMAIN, $where_arg);
    }
    else
    {
        $res = sql_param_query('SELECT DISTINCT b_id_mod, b_id_el, b_count_el, b_date, b_reserved, b_prop, b_discount
                                FROM sb_basket
                                WHERE b_reserved = 0
                                AND (b_domain=? OR b_domain=?) '
                                .$where_str.
                                ' AND b_id_mod IN (?a)', 'all', SB_COOKIE_DOMAIN, $where_arg, explode(',',$params['plugins']));
    }

	if (!$res)
	{
	    echo eval(' ?>'.(isset($ptf_messages['no_elems']) ? $ptf_messages['no_elems'] : '').'<?php ');
	    return;
	}

	$demo_statuses = sb_get_workflow_demo_statuses();

	include_once(SB_CMS_PL_PATH.'/pl_plugin_maker/prog/pl_plugin_maker.php');

	$pm_settings = array();

	foreach($res as $key => $value)
	{
		list($b_id_mod, $b_id_el, $b_count_el, $b_date, $b_reserved, $b_prop, $b_discount) = $value;

		if ($b_id_mod == '' || $b_count_el == '' || !isset($params['cena']))
		{
			continue;
		}

		if (!isset($pm_settings[$b_id_mod]))
		{
		    $res_settings = sql_param_query('SELECT pm_elems_settings FROM sb_plugins_maker WHERE pm_id="'.$b_id_mod.'"');

		    if ($res_settings && trim($res_settings[0][0]) != '')
			    $pm_settings[$b_id_mod] = unserialize($res_settings[0][0]);
		    else
			    $pm_settings[$b_id_mod] = array();
		}

		$pm_elems_settings = $pm_settings[$b_id_mod];

		$price_res = sql_param_query('SELECT p_price1, p_price2, p_price3, p_price4, p_price5, p_active, p_pub_start, p_pub_end, p_demo_id
							FROM sb_plugins_'.$b_id_mod.'
							WHERE p_id = ?d', $b_id_el);

		if(!$price_res)
			continue;

		list($p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $p_active, $p_pub_start, $p_pub_end, $p_demo_id) = $price_res[0];

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

		$price = ${'p_price'.intval($params['cena'])};
		if (isset($pm_elems_settings['price'.intval($params['cena']).'_formula']) && trim($pm_elems_settings['price'.intval($params['cena']).'_formula']) != '' && (!isset($pm_elems_settings['price'.intval($params['cena']).'_type']) || $pm_elems_settings['price'.intval($params['cena']).'_type'] == 0))
    	{
			// рассчитываем цену
			$price = fPlugin_Maker_Quoting($b_id_mod, $b_id_el, $pm_elems_settings['price'.intval($params['cena']).'_formula'], $p_price1, $p_price2, $p_price3, $p_price4, $p_price5, $b_discount);
    	}

		$pos_count++;
		$count_overall += $b_count_el;
		$sum += $price * $b_count_el;
	}

	if ($count_overall == 0)
	{
		echo eval(' ?>'.(isset($ptf_messages['no_elems']) ? $ptf_messages['no_elems'] : '').'<?php ');
		return;
	}

	$tags = array('{TOVAR_COUNT}', '{TOVAR_COUNT_OVERALL}', '{TOVAR_SUM}', '{TOVAR_SUM_DISCOUNT}', '{BASKET_LINK}', '{DELETE}', '{USER_DATA}');
	if ($sum != intval($sum))
    {
        $sum = sprintf('%01.2f', $sum);
    }

    if($sum_discount != intval($sum_discount))
    {
        $sum_discount = sprintf('%01.2f', $sum_discount);
    }

	$values = array();
	$values[] = $pos_count;	 		// TOVAR_COUNT
 	$values[] = $count_overall;	 	// TOVAR_COUNT_OVERALL
	$values[] = $sum;  				// TOVAR_SUM
	$values[] = $sum_discount;  	// TOVAR_SUM_DISCOUNT
	$values[] = isset($params['page']) ? $params['page'] : '';	// BASKET_LINK
	$values[] = '/cms/admin/basket.php?pl_plugin_order[0]=del_orders';

	if($ptf_user_data_id > 0 && isset($_SESSION['sbAuth']))
	{
		require_once(SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
		$values[] = fSite_Users_Get_Data($ptf_user_data_id, $_SESSION['sbAuth']->getUserId());	//	USER_DATA
	}
	else
	{
		$values[] = '';
	}

	$result = str_replace($tags, $values, $ptf_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	//чистим код от инъекций
	$result = sb_clean_string($result);

	eval(' ?>'.$result.'<?php ');
	$GLOBALS['sbCache']->setLastModified(time());
}

?>