<?php

function fPayment_preform($template, $sys_msg='')
{
    sbProgStartSession();
    if(strpos($template, '{TEST_TURING_SRC}'))
    {
        $turing = sbProgGetTuring();
    }
    else
    {
        $turing = array('', '');
    }
    $return = str_replace(array('{SP_SYSTEM_MESSAGE}', '{SP_PAGE_LINK}', '{SP_SURNAME}', '{SP_NAME}', '{SP_SECNAME}', '{SP_TEXT}', '{SP_COMMENT}',
                '{SP_SUMM}', '{SP_PUBLIC}', '{TEST_TURING_VALUE_HIDDEN}', '{TEST_TURING_SRC}', '{SP_EMAIL}', '{SP_PHONE}', '{SP_ADDRESS}'),

            array($sys_msg, $_SERVER['PHP_SELF'].'?sp_action=1', isset($_POST['sp_surname']) ? $_POST['sp_surname'] : '',  isset($_POST['sp_name']) ? $_POST['sp_name'] : '',
                isset($_POST['sp_secname']) ? $_POST['sp_secname'] : '', isset($_POST['sp_text']) ? $_POST['sp_text'] : '', isset($_POST['sp_comment']) ? $_POST['sp_comment'] : '',
                isset($_POST['sp_summ']) ? $_POST['sp_summ'] : '', isset($_POST['sp_public']) ? 'checked="checked"' : '', $turing[1],
                $turing[0], isset($_POST['sp_email']) ? $_POST['sp_email'] : '', isset($_POST['sp_phone']) ? $_POST['sp_phone'] : '',
                isset($_POST['sp_address']) ? $_POST['sp_address'] : ''), $template);

    //чистим код от инъекций
    $return = sb_clean_string($return);

    eval (' ?>'.$return.'<?php ');
}

function fPayment_Form ($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_payment', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));

    if (isset($_GET['sp_offline']) && intval($_GET['sp_offline']) == 1)
        $p_system = 'pl_payment_offline';

    $centr_name = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_fields LIKE "%'.$params['id'].'%" AND cat_level="1"');
    $centr_name = unserialize($centr_name[0][0]);
    $p_system = $centr_name['cat_ident'];


    // Проверка подлинности данных
    if(!isset($_GET['sp_action']) || !is_numeric($_GET['sp_action']) || $_GET['sp_action'] > 2 || $_GET['sp_action'] < 0)
    {
    	$sp_action = 0;
    }
    else
    {
        $sp_action = $_GET['sp_action'];
    }

    $result = sql_param_query('SELECT spt_system_messages FROM sb_payments_temps WHERE spt_id=?d', $temp_id);
    $result = unserialize($result[0][0]);
    foreach ($result as  $code => $msg)
    {
        $sys_msgs[$code] = $msg;
    }

    // Получаем шаблон
    //$result = sql_param_query('SELECT spt_payment_descr, spt_prerequest_main, spt_confirm FROM sb_payments_temps WHERE spt_id=?d', $temp_id);
    $result = sbQueryCache::getTemplate('sb_payments_temps', $temp_id);
    if (!$result)
        return;

    list($spt_payment_descr, $spt_prerequest_main, $spt_confirm) = $result[0];

    // Получаем идентификатор системы оплаты и настройки
    $res = sql_query("SELECT sps_name, sps_value FROM sb_payments_settings WHERE sps_ident='$p_system'");
    $settings = array();
    for($i = 0; $i < count($res); $i++)
    {
        list($sps_name, $sps_value) = $res[$i];
        $settings[$sps_name] = $sps_value;
    }

    $sp_name = $sp_secname = $sp_surname = $sp_text = $sp_comment = $sp_email = $sp_phone = $sp_address = '';
    $sp_summ = 0;
    $sp_public = 0;

    extract($_POST);

    if(session_id() == '')
    {
        @session_start();
    }

    // Генерация "шагов" формы оплаты
    switch($sp_action)
    {
        // Генерация формы предварительного запроса
        case 0:
        	fPayment_preform($spt_prerequest_main);
            break;

            // Подтверждение платежа, создание формы запроса к платежной системе
        case 1:
        	$turing_access = true;
            if(strpos($spt_prerequest_main, '{TEST_TURING_SRC}'))
            {
                if (isset($_POST['sp_turing_hid']) && isset($_POST['sp_turing']))
                {
                    $hash_turing_get = md5(substr(md5($_POST['sp_turing']), 0, 5));
                    if($_POST['sp_turing_hid'] != $hash_turing_get || !isset($_SESSION[$hash_turing_get]))
                    {
                    	$turing_access = false;
                    }
                    unset($_SESSION[$_POST['sp_turing_hid']]);
                }
                else
                {
                    $turing_access = false;
                }
            }

            if(!isset($_POST['sp_summ']) || floatval($_POST['sp_summ']) <= 0 || !isset($_POST['sp_name']) || strlen($_POST['sp_name']) < 1 || !isset($_POST['sp_surname']) || strlen($_POST['sp_surname']) < 1)
            {
                fPayment_preform($spt_prerequest_main, $sys_msgs['spt_err_form']);
                break;
            }

            if (!$turing_access)
            {
                fPayment_preform($spt_prerequest_main, $sys_msgs['spt_err_captcha']);
                break;
            }

            if(isset($_SESSION['sbAuth']))
                $sp_user_id = $_SESSION['sbAuth']->getUserId();
            else
                $sp_user_id = -1;

            $rows = array();
            $rows['sp_title'] = '';
            $rows['sp_status'] = 0;
            $rows['sp_summ'] = $sp_summ;
            $rows['sp_attr1'] = '';
            $rows['sp_attr2'] = '';
            $rows['sp_attr3'] = '';
            $rows['sp_name'] = $sp_name;
            $rows['sp_secname'] = $sp_secname;
            $rows['sp_surname'] = $sp_surname;
            $rows['sp_text'] = $sp_text;
            $rows['sp_comment'] = $sp_comment;
            $rows['sp_user_id'] = $sp_user_id;
            $rows['sp_date'] = mktime();
            $rows['sp_public'] = $sp_public;
            $rows['sp_email'] = $sp_email;
            $rows['sp_phone'] = $sp_phone;
            $rows['sp_address'] = $sp_address;

            sql_query('LOCK TABLE sb_payments WRITE, sb_categs READ, sb_catlinks WRITE, sb_catchanges WRITE');

            sql_param_query('INSERT INTO sb_payments SET ?a', $rows);
            $sp_id = sql_insert_id();

            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_fields LIKE "%'.$p_system.'%" AND cat_ident="pl_payments"');
            list($cat_id) = $res[0];

            if(isset($_SESSION['sbAuth']))
            {
                $change_user_id = $_SESSION['sbAuth']->getUserId;
            }
            else
            {
                $change_user_id = -1;
            }

            $rows = array();
            $rows['link_cat_id'] = $cat_id;
            $rows['link_el_id'] = $sp_id;
            $rows['link_src_cat_id'] = 0;


            sql_param_query('INSERT INTO sb_catlinks SET ?a', $rows);
            sql_param_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, "pl_payments", ?d, ?d, "add")', $sp_id, time(), $change_user_id);
            sql_query('UNLOCK TABLES');

            header('Location: '.sb_sanitize_header($_SERVER['PHP_SELF'].'?sp_id='.$sp_id.'&sp_hash='.md5('~'.$_POST['sp_name'].$_POST['sp_surname'].$_POST['sp_summ'].'~').'&sp_action=2'.(isset($_GET['sp_offline']) ? '&sp_offline='.$_GET['sp_offline'] : '')));
            exit(0);

        case 2:
            $sp_id = intval($_GET['sp_id']);
        	$res = sql_param_query('SELECT sp_summ, sp_name, sp_secname, sp_surname, sp_text, sp_comment, sp_email, sp_phone, sp_address, sp_public FROM sb_payments WHERE sp_id=?d', $_GET['sp_id']);
            if (!$res)
                return;

            list($sp_summ, $sp_name, $sp_secname, $sp_surname, $sp_text, $sp_comment, $sp_email, $sp_phone, $sp_address, $sp_public) = $res[0];
            if (md5('~'.$sp_name.$sp_surname.$sp_summ.'~') != $_GET['sp_hash'])
                return;

            $sp_subj = str_replace(array('{SP_ID}', '{SP_SURNAME}', '{SP_NAME}', '{SP_SECNAME}', '{SP_TEXT}', '{SP_COMMENT}', '{SP_SUMM}', '{SP_EMAIL}', '{SP_PHONE}', '{SP_ADDRESS}'),
                                    array($sp_id, $sp_surname, $sp_name, $sp_secname, $sp_text, $sp_comment, $sp_summ, $sp_email, $sp_phone, $sp_address), $spt_payment_descr);

            $sp_name_lat = sb_strtolat($sp_name);
            $sp_surname_lat = sb_strtolat($sp_surname);

            $sp_form = '';
            switch($p_system)
            {
            	// Для системы ASSIST. Форма предварительного запроса.
                case 'pl_payment_assist':
					$sp_form_action = 'https://payments.paysecure.ru/pay/order.cfm';
					$sp_form .= '
                        <INPUT TYPE="HIDDEN" NAME="Merchant_ID" VALUE='.$settings['sps_option_id'].'>
                        <INPUT TYPE="HIDDEN" NAME="OrderNumber" VALUE="'.$sp_id.'">
                        <INPUT TYPE="HIDDEN" NAME="OrderAmount" VALUE="'.$sp_summ.'">
                        <INPUT TYPE="HIDDEN" NAME="OrderCurrency" VALUE="'.$settings['sps_option_currency'].'">
                        <INPUT TYPE="HIDDEN" NAME="Language" VALUE="'.$settings['sps_option_lang'].'">
                        <INPUT TYPE="HIDDEN" NAME="URL_RETURN_OK" VALUE="'.(isset($params['p_success']) ? $params['p_success'] : '').'">
						<INPUT TYPE="HIDDEN" NAME="URL_RETURN_NO" VALUE="'.(isset($params['p_failed']) ? $params['p_failed'] : '').'">

						'.(isset($settings['sps_option_payment_card']) && $settings['sps_option_payment_card'] ? '<INPUT TYPE="HIDDEN" NAME="CardPayment" VALUE="1">' : '').'
						'.(isset($settings['sps_option_payment_yandex']) && $settings['sps_option_payment_yandex'] ? '<INPUT TYPE="HIDDEN" NAME="YMPayment" VALUE="1">' : '').'
                        '.(isset($settings['sps_option_payment_webmoney']) && $settings['sps_option_payment_webmoney'] ? '<INPUT TYPE="HIDDEN" NAME="WMPayment" VALUE="1">' : '').'
                        '.(isset($settings['sps_option_payment_qiwi']) && $settings['sps_option_payment_qiwi'] ? '<INPUT TYPE="HIDDEN" NAME="QIWIPayment" VALUE="1">' : '').'
                        '.(isset($settings['sps_option_payment_demo']) && $settings['sps_option_payment_demo'] ? '<INPUT TYPE="HIDDEN" NAME="TestMode" VALUE="1">' : '').'
                        <INPUT TYPE="HIDDEN" NAME="OrderComment " VALUE="'.$sp_subj.'">';

					break;

                case 'pl_payment_chronopay':
                	$sp_form_action = 'https://secure.chronopay.com/index_shop.cgi';

                    $sp_form .= "
                        <INPUT TYPE='HIDDEN' NAME='product_id' VALUE='".$settings['sps_option_id']."'>
                        <INPUT TYPE='HIDDEN' NAME='product_name' VALUE='$sp_subj'>
                        <INPUT TYPE='HIDDEN' NAME='product_price' VALUE='$sp_summ'>
                        <INPUT TYPE='HIDDEN' NAME='cs1' VALUE='$sp_id'>
                        <INPUT TYPE='HIDDEN' NAME='cs2' VALUE='".md5('~'.$sp_id.$sp_summ.$sp_name.$sp_surname.$sp_secname.'~')."'>
                        <INPUT TYPE='HIDDEN' NAME='language' VALUE='".$settings['sps_option_lang']."'>
                        <INPUT TYPE='HIDDEN' NAME='cb_url' VALUE='".SB_DOMAIN."/prog/pl_payment/pl_payment_processing.php'>
                        <INPUT TYPE='HIDDEN' NAME='cb_type' VALUE='P'>
                        <INPUT TYPE='HIDDEN' NAME='decline_url' VALUE='".(isset($params['p_failed']) ? $params['p_failed'] : '')."'> ";

                    if($sp_name_lat && $sp_surname_lat && $sp_email && $sp_phone)
                       $sp_form .= "
                        <INPUT TYPE='HIDDEN' NAME='f_name' VALUE='".$sp_name_lat."'>
                        <INPUT TYPE='HIDDEN' NAME='s_name' VALUE='".$sp_surname_lat."'>
                        <INPUT TYPE='HIDDEN' NAME='email' VALUE='".$sp_email."'>
                        <INPUT TYPE='HIDDEN' NAME='phone' VALUE='".$sp_phone."'>";
                    break;
                case 'pl_payment_rupay':
                    $sp_form_action = "http://www.rupay.ru/rupay/pay/index.php";
                    $sp_form .= "
                        <INPUT TYPE='HIDDEN' NAME='pay_id' VALUE='{$settings['sps_option_rupay_id']}'>
                        <INPUT TYPE='HIDDEN' NAME='sum_pol' VALUE='$sp_summ'>
                        <INPUT TYPE='HIDDEN' NAME='sum_val' VALUE='USD'>
                        <INPUT TYPE='HIDDEN' NAME='name_service' VALUE='$sp_subj'>
                        <INPUT TYPE='HIDDEN' NAME='order_id' VALUE='$sp_id'>
                        <INPUT TYPE='HIDDEN' NAME='success_url' VALUE='".(isset($params['p_success']) ? $params['p_success'] : '')."'>
                        <INPUT TYPE='HIDDEN' NAME='fail_url' VALUE='".(isset($params['p_failed']) ? $params['p_failed'] : '')."'>";
                    break;
            }

        $result = str_replace(array('{SP_ID}', '{SP_ACTION}', '{SP_METHOD}', '{SP_FORM}', '{SP_NAME}', '{SP_SURNAME}', '{SP_SECNAME}', '{SP_TEXT}', '{SP_SUMM}', '{SP_COMMENT}', '{SP_EMAIL}', '{SP_PHONE}', '{SP_ADDRESS}', '{SP_PUBLIC}'),
            array($sp_id, $sp_form_action, 'POST', $sp_form, $sp_name, $sp_surname, $sp_secname, $sp_text, $sp_summ, $sp_comment, $sp_email, $sp_phone, $sp_address, ($sp_public == 1 ? $sys_msgs['spt_err_anonymous'] : '')), $spt_confirm);

        $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
        $GLOBALS['sbCache']->save('pl_payment', $result);

    }
}


function fPayment_List ($el_id, $temp_id, $params, $tag_id)
{
    if ($GLOBALS['sbCache']->check('pl_payment', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));
    $cat_ids = array();
/*
    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['payment_scid']) || isset($_GET['payment_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
        if (isset($_GET['payment_cid']))
        {
            $cat_ids[] = intval($_GET['payment_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=?', $_GET['payment_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['payment_scid']);
            }
        }
    }
    else
    {
        $cat_ids = explode('^', $params['ids']);
    }
*/
    $cat_ids = explode('^', $params['ids']);
    // если следует выводить подразделы, то вытаскиваем их ID
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
		$res = sql_param_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_payments"
							AND c2.cat_ident = "pl_payments"
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
        	//  указанные разделы были удалены
            sb_404();
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

        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_payment_read');
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_payment', '');
        return;
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT sptl_list_header, sptl_categ_top, sptl_list_main, sptl_categ_bottom, sptl_list_footer, sptl_empty,
    //sptl_delim, sptl_pagelist_id, sptl_count, sptl_perpage, sptl_lang, sptl_checked, sptl_no_transaction, sptl_fields_temps, sptl_categs_temps
    //FROM sb_payments_temps_list WHERE sptl_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_payments_temps_list', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_payment', '');
        return;
    }

    list($sptl_list_header, $sptl_categ_top, $sptl_list_main, $sptl_categ_bottom, $sptl_list_footer, $sptl_empty, $sptl_delim,
    $sptl_pagelist_id, $sptl_count, $sptl_perpage, $sptl_lang, $sptl_checked, $sptl_no_transaction, $sptl_fields_temps, $sptl_categs_temps) = $res[0];

    $sptl_fields_temps = unserialize($sptl_fields_temps);
    $sptl_categs_temps = unserialize($sptl_categs_temps);

    // вытаскиваем макет дизайна постраничного вывода
    $res = sbQueryCache::getTemplate('sb_pager_temps', $sptl_pagelist_id);

    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

    // вытаскиваем пользовательские поля новости и раздела
    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_payment"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_payment');

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
    if ($sptl_checked != '')
    {
        $sptl_checked = explode(' ', $sptl_checked);
        foreach ($sptl_checked as $value)
        {
            $elems_fields_where_sql .= ' AND p.user_f_'.$value.'=1';
        }
    }

    $now = time();
    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND p.sp_date >= '.$last.' AND p.sp_date <= '.$now;
    }
    elseif ($params['filter'] == 'summ')
    {
        $from = intval($params['filter_summ_from']);
        $to = intval($params['filter_summ_to']);

        $elems_fields_where_sql .= ' AND p.sp_summ >= '.$from.' AND p.sp_summ <= '.$to;
    }

    if(isset($params['filter_stat']) && $params['filter_stat'] == 'filter_stat')
    {
        $filter_status = intval($params['filter_status']);
    	$elems_fields_where_sql .= ' AND p.sp_status ='.$filter_status;
    }

    // формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
    if (isset($params['sort1']) && $params['sort1'] != '')
    {
        $elems_fields_sort_sql .= $params['sort1'] != 'RAND()' ? ', p.'.$params['sort1'] : ', '.$params['sort1'];

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
        $elems_fields_sort_sql .= $params['sort2'] != 'RAND()' ? ', p.'.$params['sort2'] : ', '.$params['sort2'];

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
        $elems_fields_sort_sql .= $params['sort1'] != 'RAND()' ? ', p.'.$params['sort3'] : ', '.$params['sort3'];

    	if ($params['sort3'] == 'RAND()')
    	{
    		$GLOBALS['sbCache']->mCacheOff = true;
    	}

        if (isset($params['order3']) && $params['order3'] != '')
        {
            $elems_fields_sort_sql .= ' '.$params['order3'];
        }
    }

    // используется ли группировка по разделам
    if ($sptl_categ_top != '' || $sptl_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }

	@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
    $pager = new sbDBPager($tag_id, $pt_perstage, $sptl_perpage);
    if ($params['filter'] == 'from_to')
    {
        $pager->mFrom = intval($params['filter_from']);
        $pager->mTo = intval($params['filter_to']);
    }

    // выборка новостей, которые следует выводить
    $payment_total = true;
    if ($categs_output)
    {
        $res = $pager->init($payment_total, 'SELECT l.link_cat_id, p.sp_id, p.sp_title, p.sp_summ, p.sp_name, p.sp_secname, p.sp_surname, p.sp_text,
                            p.sp_comment, p.sp_date, p.sp_public, p.sp_address, p.sp_email, p.sp_phone
                            '.$elems_fields_select_sql.'
                            FROM sb_payments p, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=p.sp_id
                            '.$elems_fields_where_sql.'
                            AND p.sp_active = 1
                            GROUP BY p.sp_id
                            ORDER BY c.cat_left'.$elems_fields_sort_sql, $cat_ids);
    }
    else
    {
        $res = $pager->init($payment_total, 'SELECT l.link_cat_id, p.sp_id, p.sp_title, p.sp_summ, p.sp_name, p.sp_secname, p.sp_surname, p.sp_text,
                            p.sp_comment, p.sp_date, p.sp_public, p.sp_address, p.sp_email, p.sp_phone
                            '.$elems_fields_select_sql.'
                            FROM sb_payments p, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=p.sp_id
                            '.$elems_fields_where_sql.'
                            AND p.sp_active = 1
                            GROUP BY p.sp_id
                            '.($elems_fields_sort_sql != '' ? 'ORDER BY'.substr($elems_fields_sort_sql, 1) : 'ORDER BY p.sp_date'), $cat_ids);
    }

    if (!$res)
    {
        $GLOBALS['sbCache']->save('pl_payment', $sptl_no_transaction);
        return;
    }

    $count_payments = $pager->mFrom + 1;

    $categs = array();
    if (sb_substr_count($sptl_categ_top, '{CAT_COUNT}') > 0 ||
        sb_substr_count($sptl_categ_bottom, '{CAT_COUNT}') > 0 ||
        sb_substr_count($sptl_list_main, '{CAT_COUNT}') > 0
       )
    {
        $res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields, c1.cat_url,
                (

                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_payments p
                WHERE c.cat_id = c1.cat_id
                AND l.link_el_id=p.sp_id
                AND p.sp_active = 1
                AND l.link_src_cat_id NOT IN (?a)

                ) AS cat_count
                FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids);
    }
    else
    {
        $res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, cat_url, "" AS cat_count
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
            $categs[$value[0]]['url'] = $value[4];
            $categs[$value[0]]['count'] = $value[5];
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

    // верх вывода новостной ленты
    $result = str_replace(array('{NUM_LIST}', '{ALL_COUNT}'), array($pt_page_list, $payment_total), $sptl_list_header);
    $tags = array_merge($tags, array('{CAT_ID}',
                                     '{CAT_TITLE}',
                                     '{CAT_COUNT}',
                                     '{CAT_LEVEL}',
    								 '{SP_NUMBER}',
                                     '{SP_ID}',
 									 '{SP_SURNAME}',
									 '{SP_NAME}',
									 '{SP_SECNAME}',
									 '{SP_EMAIL}',
									 '{SP_PHONE}',
									 '{SP_ADDRESS}',
									 '{SP_TEXT}',
									 '{SP_COMMENT}',
									 '{SP_SUMM}',
									 '{SP_DATE}',
    								 '{SP_CHANGE_DATE}'));

    $cur_cat_id = 0;
    $values = array();
    $cat_values = array();
    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $col = 0;

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $dop_tags = array('{ID}', '{CAT_ID}', '{CAT_TITLE}');

    foreach ($res as $value)
    {
        $old_values = $values;
        $values = array();

        $dop_values = array($value[1], $value[0], strip_tags($categs[$value[0]]['title']));

        if ($num_fields > 13)
        {
            for ($i = 13; $i < $num_fields; $i++)
            {
                $values[] = $value[$i];
            }

            $values = sbLayout::parsePluginFields($elems_fields, $values, $sptl_fields_temps, $dop_tags, $dop_values, $sptl_lang);
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
                $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $sptl_categs_temps, $dop_tags, $dop_values, $sptl_lang);
            }

            $values = array_merge($values, $cat_values);
        }

        $values[] = $value[0];  // CAT_ID
        $values[] = $categs[$value[0]]['title']; // CAT_TITLE
        $values[] = $categs[$value[0]]['count']; // CAT_COUNT
        $values[] = $categs[$value[0]]['level']; // CAT_LEVEL;
		$values[] = $count_payments++;	//	  SP_NUMBER
        $values[] = $value[1];  // ID
        $values[] = ($value[6] != '' && !is_null($value[6])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[6]), $dop_values),
                    $sptl_fields_temps['sptl_surname']):'');       // SURNAME
        $values[] = ($value[4] != '' && !is_null($value[4])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[4]), $dop_values),
                    $sptl_fields_temps['sptl_name']):'');          // NAME
        $values[] = ($value[5] != '' && !is_null($value[5])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[5]), $dop_values),
                    $sptl_fields_temps['sptl_secname']):'');          // SECNAME
        $values[] = ($value[12] != '' && !is_null($value[12])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[12]), $dop_values),
                    $sptl_fields_temps['sptl_email']):'');          // EMAIL
        $values[] = ($value[13] != '' && !is_null($value[13])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[13]), $dop_values),
                    $sptl_fields_temps['sptl_phone']):'');          // PHONE
        $values[] = ($value[11] != '' && !is_null($value[11])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[11]), $dop_values),
                    $sptl_fields_temps['sptl_address']):'');          // ADDRESS
        $values[] = ($value[7] != '' && !is_null($value[7])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[7]), $dop_values),
                    $sptl_fields_temps['sptl_naznachenie']):'');          // TEXT
        $values[] = ($value[8] != '' && !is_null($value[8])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[8]), $dop_values),
                    $sptl_fields_temps['sptl_comment']):'');          // COMMENT
        $values[] = ($value[3] != '' && !is_null($value[3])? str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($value[3]), $dop_values),
                    $sptl_fields_temps['sptl_summ']):'');          // SUMM
		$values[] = sb_parse_date($value[9], $sptl_fields_temps['sptl_date'], $sptl_lang); // DATE
        // Дата последнего изменения
        if(sb_strpos($sptl_list_main, '{SP_CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $value[1],'pl_payments');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sptl_fields_temps['sptl_date'], $sptl_lang) : ''; //   CHANGE_DATE
        }
        else
       	{
        	$values[] = '';
       	}


        if ($categs_output && $value[0] != $cur_cat_id)
        {
            if ($cur_cat_id != 0)
            {
                // низ вывода раздела
                while ($col < $sptl_count)
                {
                    $result .= $sptl_empty;
                    $col++;
                }
                $result .= str_replace($tags, $old_values, $sptl_categ_bottom);
                $cat_values = array();
            }
            // верх вывода раздела
            $result .= str_replace($tags, $values, $sptl_categ_top);
            $col = 0;
        }
        if ($col >= $sptl_count)
        {
            $result .= $sptl_delim;
            $col = 0;
        }
        $result .= str_replace($tags, $values, $sptl_list_main);

        $cur_cat_id = $value[0];
        $col++;
    }

    while ($col < $sptl_count)
    {
        $result .= $sptl_empty;
        $col++;
    }

    if ($categs_output)
    {
        // низ вывода раздела
        $result .= str_replace($tags, $values, $sptl_categ_bottom);
    }

    // низ вывода списка транзакций
    $result .= str_replace(array('{NUM_LIST}', '{ALL_COUNT}'), array($pt_page_list, $payment_total), $sptl_list_footer);

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save('pl_payment', $result);
}

?>