<?php

// Форма логина
function fSite_Users_Elem_Login($el_id, $temp_id, $params, $tag_id)
{
    //Для регистрации пользователей из соц. сетей
    sbProgStartSession();
    $_SESSION['login_params'] = $params;

	$res = sbQueryCache::getTemplate('sb_site_users_temps', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_SITE_USERS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_site_users', '');
        return;
	}

	list($sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages) = $res[0];

	$sut_fields_temps = (trim($sut_fields_temps) != '' ? unserialize($sut_fields_temps) : array());
	$sut_categs_temps = (trim($sut_categs_temps) != '' ? unserialize($sut_categs_temps) : array());
	$sut_messages = (trim($sut_messages) ? unserialize($sut_messages) : array());

	$result = '';
	$message = '';

	if (!isset($_SESSION['sbAuth']) && isset($_GET['su_action']))
	{
        switch($_GET['su_action'])
        {
			// выход пользователя, выводим соотв. сообщение
            case 'logout':
                $message = $sut_messages['logout'];
                break;

            case 'soc_error':
                if(isset($_SESSION['error_message']))
                {
                    $message = sb_str_replace('{ERROR_MESSAGE}', $_SESSION['error_message'], $sut_messages['generate_errors']);
                    unset($_SESSION['error_message']);
                }
                break;

            default: $message = '';
        }
	}
    elseif(isset($_GET['su_action']) && $_GET['su_action'] == 'soc_error')
    {
        if (isset($_SESSION['error_message']))
        {
            $message = sb_str_replace('{ERROR_MESSAGE}', $_SESSION['error_message'], $sut_messages['generate_errors']);
            unset($_SESSION['error_message']);
        }
    }

	if (isset($_SESSION['sbAuth']) && $_SESSION['sbAuth']->getErrorCode() == -1)
	{
		// пользователь уже залогинился, выводим соотв. сообщение
		if ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
        	return;

		$res = sql_param_query('SELECT su_login, su_email FROM sb_site_users WHERE su_id=?d', $_SESSION['sbAuth']->getUserId());

		if (!$res)
		{
			if (isset($sut_messages['wrong_login']))
			{
				// пользователь не найден или был удален
				$message = str_replace(array('{SU_LOGIN}', '{SU_EMAIL}'), array($_SESSION['sbAuth']->getUserLogin(), $_SESSION['sbAuth']->getUserEmail()), $sut_messages['wrong_login']);
			}
		}
		else
		{
			$result = fSite_Users_Parse($sut_messages['login'], $sut_fields_temps, $sut_categs_temps, $_SESSION['sbAuth']->getUserId(), $sut_lang);
			$result = str_replace('{SU_LOGOUT_LINK}', $GLOBALS['PHP_SELF'].($GLOBALS['QUERY_STRING'] != '' ? '?'.$GLOBALS['QUERY_STRING'].'&su_action=logout' : '?su_action=logout'), $result);
            $result = str_replace('{ERROR_MESSAGE}', $message, $result);
			$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

			$GLOBALS['sbCache']->save('pl_site_users', $result);
			return;
		}
	}

	if (!isset($_POST['su_login']) && !isset($_POST['su_email']) && !isset($_REQUEST['su_lm']))
	{
		// просто вывод формы, данные пока не пришли
		if (trim($sut_form) == '')
		{
			$GLOBALS['sbCache']->save('pl_site_users', '');
			return;
		}

		if ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
        	return;
	}

	$login = isset($_POST['su_login']) ? $_POST['su_login'] : '';
	$email = isset($_POST['su_email']) ? $_POST['su_email'] : '';

	$error_code = isset($_REQUEST['su_lm']) ? intval($_REQUEST['su_lm']) : sbAuth::getErrorCode();
	if ($error_code != -1)
	{
		// произошла ошибка при входе пользователя, выводим соотв. сообщение
		switch($error_code)
		{
			case SB_AUTH_SITE_ERROR_WRONG_LOGIN:
				$message = isset($sut_messages['wrong_login']) ? $sut_messages['wrong_login'] : '';
				break;

			case SB_AUTH_SITE_ERROR_WRONG_PASSWORD:
				$message = isset($sut_messages['wrong_password']) ? $sut_messages['wrong_password'] : '';
				break;

			case SB_AUTH_SITE_ERROR_WRONG_DOMAIN:
				$message = isset($sut_messages['wrong_domain']) ? $sut_messages['wrong_domain'] : '';
				break;

			case SB_AUTH_SITE_ERROR_PREMOD_ACCOUNT:
				$message = isset($sut_messages['premod_account']) ? $sut_messages['premod_account'] : '';
				break;

			case SB_AUTH_SITE_ERROR_EMAIL_ACCOUNT:
				$message = isset($sut_messages['email_account']) ? $sut_messages['email_account'] : '';
				break;

			case SB_AUTH_SITE_ERROR_PREMOD_EMAIL_ACCOUNT:
				$message = isset($sut_messages['premod_email_account']) ? $sut_messages['premod_email_account'] : '';
				break;

			case SB_AUTH_SITE_ERROR_BLOCKED_ACCOUNT:
				$message = isset($sut_messages['blocked_account']) ? $sut_messages['blocked_account'] : '';
				break;

			case SB_AUTH_SITE_ERROR_BLOCKED_GROUP:
				$message = isset($sut_messages['blocked_group']) ? $sut_messages['blocked_group'] : '';
				break;

			case SB_AUTH_SITE_ERROR_WRONG_IP:
				$message = isset($sut_messages['wrong_ip']) ? $sut_messages['wrong_ip'] : '';
				break;
		}

		$message = str_replace(array('{DOMAIN}', '{SU_LOGIN}', '{SU_EMAIL}'), array(SB_COOKIE_DOMAIN, $login, $email), $message);
	}

	// выводим форму логина
    $result = str_replace(
            array(
                '{SU_ACTION}',
                '{MESSAGE}',
                '{SU_LOGIN}',
                '{SU_EMAIL}',
            ),
            array(
                $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : ''),
                $message,
                $login,
                $email,
            ),
            $sut_form
    );

	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	if (!isset($_POST['su_login']) && !isset($_POST['su_email']) && !isset($_REQUEST['su_lm']))
	{
		$GLOBALS['sbCache']->save('pl_site_users', $result);
	}
	else
	{
		//чистим код от инъекций
        $result = sb_clean_string($result);

		eval(' ?>'.$result.'<?php ');
	}
}

// Форма регистрации
function fSite_Users_Elem_Reg($el_id, $temp_id, $params, $tag_id, $update=false)
{
	if (!isset($_POST['su_login_reg']) && !isset($_POST['su_email_reg']) && !$update)
	{
		// просто вывод формы, данные пока не пришли
		if ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
        	return;
	}

	//$res = sql_param_query('SELECT sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages FROM sb_site_users_temps WHERE sut_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_site_users_temps', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_SITE_USERS_PLUGIN), SB_MSG_WARNING);
        if (!$update)
        	$GLOBALS['sbCache']->save('pl_site_users', '');
        return;
	}
	list($sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages) = $res[0];

	$params = unserialize(stripslashes($params));
	$sut_fields_temps = ($sut_fields_temps != '' ? unserialize($sut_fields_temps) : array());
	$sut_categs_temps = ($sut_categs_temps != '' ? unserialize($sut_categs_temps) : array());
	$sut_messages = ($sut_messages != '' ? unserialize($sut_messages) : array());
	$make_link = 0; // Флаг указывающий нужно ли делать ссылку на пользователя

	if (isset($_GET['su_code']))
	{
		$res = sql_param_query('SELECT su_id, su_status FROM sb_site_users WHERE su_activation_code=?', $_GET['su_code']);
		if ($res)
		{
			list($su_id, $su_status) = $res[0];

			if ($su_status == 3 || $su_status == 1)
			{
				$su_status = 1;
				if (isset($sut_messages['activate_mod']))
				{
					$sut_messages['activate_mod'] = fSite_Users_Parse($sut_messages['activate_mod'], $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');
		  			eval(' ?>'.$sut_messages['activate_mod'].'<?php ');
				}
			}
			else if ($su_status == 2 || $su_status == 0)
			{
				$su_status = 0;
				if (isset($sut_messages['activate']))
				{
					$sut_messages['activate'] = fSite_Users_Parse($sut_messages['activate'], $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');
		  			eval(' ?>'.$sut_messages['activate'].'<?php ');
				}
			}
			else
			{
				if (isset($sut_messages['activate_error']))
				{
					eval(' ?>'.$sut_messages['activate_error'].'<?php ');
				}
				return;
			}

			sql_param_query('UPDATE sb_site_users SET su_status=?d, su_activation_code=NULL WHERE su_id=?d', $su_status, $su_id);
		}
		else if (isset($sut_messages['activate_error']))
		{
  			eval(' ?>'.$sut_messages['activate_error'].'<?php ');
		}

		return;
	}

	$result = '';
	$message = '';

	if (((!isset($_POST['su_login_reg']) && !isset($_POST['su_email_reg'])) || ($update && !isset($_POST['su_update_form']))) && trim($sut_form) == '')
	{
		// вывод формы
		if (!$update)
			$GLOBALS['sbCache']->save('pl_site_users', '');
        return;
	}

	if (isset($_GET['su_cid']) && isset($_COOKIE['su_show_info']) && $_COOKIE['su_show_info'] == 1)
	{
		if (isset($sut_messages['registrate']) && trim($sut_messages['registrate']) != '')
		{
			$result = fSite_Users_Parse($sut_messages['registrate'], $sut_fields_temps, $sut_categs_temps, $_GET['su_cid'], $sut_lang, '', '_val');
			$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

			//чистим код от инъекций
            $result = sb_clean_string($result);

			eval(' ?>'.$result.'<?php ');
		}

		$_COOKIE['su_show_info'] = 0;
		sb_setcookie('su_show_info');
		return;
	}

	if ($update && (!isset($_SESSION['sbAuth']) || $_SESSION['sbAuth']->getUserId() <= 0))
	{
		eval(' ?>'.$sut_messages['logged_error'].'<?php ');
		return;
	}

	$su_id = -1;
	if ($update)
	{
		$su_id = $_SESSION['sbAuth']->getUserId();
		$res = sql_param_query('SELECT su_name, su_email, su_login,
	                          su_status, su_pers_foto, su_pers_phone, su_pers_mob_phone, su_pers_birth,
	                          su_pers_sex, su_pers_zip, su_pers_adress, su_pers_addition, su_work_name,
	                          su_work_phone, su_work_phone_inner, su_work_fax, su_work_email, su_work_addition,
	                          su_work_office_number, su_work_unit, su_work_position, su_forum_nick, su_forum_text,
	                          su_mail_lang, su_mail_status, su_mail_date, su_mail_subscription
	                          FROM sb_site_users WHERE su_id=?d', $su_id);
		if (!$res)
		{
			eval(' ?>'.$sut_messages['logged_error'].'<?php ');
			return;
		}

		list($su_name_old, $su_email_old, $su_login_old, $su_status, $su_pers_foto_old, $su_pers_phone_old, $su_pers_mob_phone_old, $su_pers_birth_old,
             $su_pers_sex_old, $su_pers_zip_old, $su_pers_adress_old, $su_pers_addition_old, $su_work_name_old, $su_work_phone_old, $su_work_phone_inner_old, $su_work_fax_old,
             $su_work_email_old, $su_work_addition_old, $su_work_office_number_old, $su_work_unit_old, $su_work_position_old, $su_forum_nick_old, $su_forum_text_old, $su_mail_lang_old,
             $su_mail_status_old, $su_mail_date_old, $su_mail_subscription_old) = $res[0];

        $su_mail_subscription_old = explode(',', $su_mail_subscription_old);
        $su_pass = '';
        $su_pass2 = '';
		$su_pers_foto = '';

		if (!isset($_POST['su_update_form']))
		{
			$su_login = $su_login_old;
			$su_email = $su_email_old;
			$su_name = $su_name_old;
			$su_pers_birth = $su_pers_birth_old;
			$su_pers_sex = $su_pers_sex_old;
			$su_pers_phone = $su_pers_phone_old;
			$su_pers_mob_phone = $su_pers_mob_phone_old;
			$su_pers_zip = $su_pers_zip_old;
			$su_pers_adress = $su_pers_adress_old;
			$su_pers_addition = $su_pers_addition_old;
			$su_work_name = $su_work_name_old;
			$su_work_unit = $su_work_unit_old;
			$su_work_position = $su_work_position_old;
			$su_work_office_number = $su_work_office_number_old;
			$su_work_phone = $su_work_phone_old;
			$su_work_phone_inner = $su_work_phone_inner_old;
			$su_work_fax = $su_work_fax_old;
			$su_work_email = $su_work_email_old;
			$su_work_addition = $su_work_addition_old;
			$su_forum_nick = $su_forum_nick_old;
			$su_forum_text = $su_forum_text_old;
			$su_mail_date = $su_mail_date_old;
			$su_mail_status = $su_mail_status_old;
			$su_mail_lang = $su_mail_lang_old;
			$su_mail = $su_mail_subscription_old;
		}
		else
		{
			$su_login = isset($_POST['su_login_reg']) ? $_POST['su_login_reg'] : $su_login_old;
			$su_email = isset($_POST['su_email_reg']) ? $_POST['su_email_reg'] : $su_email_old;
			$su_pass = isset($_POST['su_pass1']) ? $_POST['su_pass1'] : '';
			$su_pass2 = isset($_POST['su_pass2']) ? $_POST['su_pass2'] : '';
			$su_name = isset($_POST['su_name']) ? $_POST['su_name'] : $su_name_old;
			$su_pers_birth = isset($_POST['su_pers_birth']) ? sb_datetoint($_POST['su_pers_birth']) : $su_pers_birth_old;
			$su_pers_sex = isset($_POST['su_pers_sex']) ? intval($_POST['su_pers_sex']) : $su_pers_sex_old;
			$su_pers_phone = isset($_POST['su_pers_phone']) ? $_POST['su_pers_phone'] : $su_pers_phone_old;
			$su_pers_mob_phone = isset($_POST['su_pers_mob_phone']) ? $_POST['su_pers_mob_phone'] : $su_pers_mob_phone_old;
			$su_pers_zip = isset($_POST['su_pers_zip']) ? $_POST['su_pers_zip'] : $su_pers_zip_old;
			$su_pers_adress = isset($_POST['su_pers_adress']) ? $_POST['su_pers_adress'] : $su_pers_adress_old;
			$su_pers_addition = isset($_POST['su_pers_addition']) ? $_POST['su_pers_addition'] : $su_pers_addition_old;
			$su_work_name = isset($_POST['su_work_name']) ? $_POST['su_work_name'] : $su_work_name_old;
			$su_work_unit = isset($_POST['su_work_unit']) ? $_POST['su_work_unit'] : $su_work_unit_old;
			$su_work_position = isset($_POST['su_work_position']) ? $_POST['su_work_position'] : $su_work_position_old;
			$su_work_office_number = isset($_POST['su_work_office_number']) ? $_POST['su_work_office_number'] : $su_work_office_number_old;
			$su_work_phone = isset($_POST['su_work_phone']) ? $_POST['su_work_phone'] : $su_work_phone_old;
			$su_work_phone_inner = isset($_POST['su_work_phone_inner']) ? $_POST['su_work_phone_inner'] : $su_work_phone_inner_old;
			$su_work_fax = isset($_POST['su_work_fax']) ? $_POST['su_work_fax'] : $su_work_fax_old;
			$su_work_email = isset($_POST['su_work_email']) ? $_POST['su_work_email'] : $su_work_email_old;
			$su_work_addition = isset($_POST['su_work_addition']) ? $_POST['su_work_addition'] : $su_work_addition_old;
			$su_forum_nick = isset($_POST['su_forum_nick']) ? $_POST['su_forum_nick'] : $su_forum_nick_old;
			$su_forum_text = isset($_POST['su_forum_text']) ? $_POST['su_forum_text'] : $su_forum_text_old;
			$su_mail_date = isset($_POST['su_mail_date']) ? $_POST['su_mail_date'] : $su_mail_date_old;
			$su_mail_status = isset($_POST['su_mail_status']) ? $_POST['su_mail_status'] : $su_mail_status_old;
			$su_mail_lang = isset($_POST['su_mail_lang']) ? $_POST['su_mail_lang'] : $su_mail_lang_old;
			$su_mail = isset($_POST['su_mail']) ? $_POST['su_mail'] : $su_mail_subscription_old;
		}
	}
	else
	{
		$su_login = isset($_POST['su_login_reg']) ? $_POST['su_login_reg'] : '';
		$su_email = isset($_POST['su_email_reg']) ? $_POST['su_email_reg'] : '';
		$su_pass = isset($_POST['su_pass1']) ? $_POST['su_pass1'] : '';
		$su_pass2 = isset($_POST['su_pass2']) ? $_POST['su_pass2'] : '';
		$su_name = isset($_POST['su_name']) ? $_POST['su_name'] : '';
		$su_pers_foto = '';
		$su_pers_foto_old = '';
		$su_pers_birth = isset($_POST['su_pers_birth']) && $_POST['su_pers_birth'] != '' ? sb_datetoint($_POST['su_pers_birth']) : '';
		$su_pers_sex = isset($_POST['su_pers_sex']) ? intval($_POST['su_pers_sex']) : 0;
		$su_pers_phone = isset($_POST['su_pers_phone']) ? $_POST['su_pers_phone'] : '';
		$su_pers_mob_phone = isset($_POST['su_pers_mob_phone']) ? $_POST['su_pers_mob_phone'] : '';
		$su_pers_zip = isset($_POST['su_pers_zip']) ? $_POST['su_pers_zip'] : '';
		$su_pers_adress = isset($_POST['su_pers_adress']) ? $_POST['su_pers_adress'] : '';
		$su_pers_addition = isset($_POST['su_pers_addition']) ? $_POST['su_pers_addition'] : '';
		$su_work_name = isset($_POST['su_work_name']) ? $_POST['su_work_name'] : '';
		$su_work_unit = isset($_POST['su_work_unit']) ? $_POST['su_work_unit'] : '';
		$su_work_position = isset($_POST['su_work_position']) ? $_POST['su_work_position'] : '';
		$su_work_office_number = isset($_POST['su_work_office_number']) ? $_POST['su_work_office_number'] : '';
		$su_work_phone = isset($_POST['su_work_phone']) ? $_POST['su_work_phone'] : '';
		$su_work_phone_inner = isset($_POST['su_work_phone_inner']) ? $_POST['su_work_phone_inner'] : '';
		$su_work_fax = isset($_POST['su_work_fax']) ? $_POST['su_work_fax'] : '';
		$su_work_email = isset($_POST['su_work_email']) ? $_POST['su_work_email'] : '';
		$su_work_addition = isset($_POST['su_work_addition']) ? $_POST['su_work_addition'] : '';
		$su_forum_nick = isset($_POST['su_forum_nick']) ? $_POST['su_forum_nick'] : '';
		$su_forum_text = isset($_POST['su_forum_text']) ? $_POST['su_forum_text'] : '';
		$su_mail_date = isset($_POST['su_mail_date']) ? $_POST['su_mail_date'] : '';

		if(isset($_POST['su_mail_status']))
		{
			$su_mail_status = $_POST['su_mail_status'];
		}
		elseif(isset($_POST['su_mail']) && count($_POST['su_mail']) > 0)
		{
			$su_mail_status = 0;
		}
		else
		{
			$su_mail_status = -1;
		}

		$su_mail_lang = isset($_POST['su_mail_lang']) ? $_POST['su_mail_lang'] : 'ru';
		$su_mail = isset($_POST['su_mail']) ? $_POST['su_mail'] : array();
	}

	$tags = array();
	$values = array();

	if(isset($_POST['su_login_reg']) || isset($_POST['su_email_reg']) || isset($_POST['su_update_form']))
	{
		$su_login = preg_replace('/[^A-z0-9А-я\-@\._!$%&+{}]/iu', '_', $su_login);

		//	проверка данных и сохранение
		$min_login = max((isset($sut_fields_temps['su_login_need']) ? 1 : 0), intval(sbPlugins::getSetting('pl_site_users_login_length')));
		$min_password = max((isset($sut_fields_temps['su_pass_need']) ? 1 : 0), intval(sbPlugins::getSetting('pl_site_users_password_length')));

		if ($update)
		{
			$message_tags = array('{SU_LOGIN}', '{SU_EMAIL}', '{SU_ID}');
			$message_values = array($su_login, $su_email, $su_id);
		}
		else
		{
			$message_tags = array('{SU_LOGIN}', '{SU_EMAIL}');
			$message_values = array($su_login, $su_email);
		}

		$error = false;
		$fields_message = '';

		if (isset($_POST['su_email_reg']))
		{
			$res = sql_query('SELECT su_id, su_pass, su_mail_subscription FROM sb_site_users WHERE su_email != "" AND su_email=? AND su_id != ?d', $su_email, $su_id);
			if ($res)
			{
				if ($update)
				{
					// редактируются данные, пользователь пытается указать мейл, указанный другим пользователем
					$error = true;

					$tags = array_merge($tags, array('{SU_EMAIL_SELECT_START}', '{SU_EMAIL_SELECT_END}'));
					$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

					$message .= isset($sut_messages['email_error']) ? str_replace($message_tags, $message_values, $sut_messages['email_error']) : '';
				}
				else
				{
					$empty_md5 = 'd41d8cd98f00b204e9800998ecf8427e';	//	md5 от пустой строки, если пользователь подписан, то у него пароль будет пустой.

					//	если такой емайл есть, но пароль пустой и пришел пароль, значит пользователь подписан на рассылку, но не зарегистрирован.
					if($res[0][1] != '' && $res[0][1] == $empty_md5 && isset($_POST['su_pass1']))
					{
						//	Подписан и регистрируется. Апдейтим запись и ставим пароль.
						$make_link = 1;
						$subscr_su_id = $res[0][0];
					}
					elseif($res[0][1] != '' && $res[0][1] != $empty_md5 && !isset($_POST['su_pass1']))
					{
						// Зарегистрирован. Подписывается. Делаем ссылку. Если в разделе уже есть ссылка то ничего не делаем.
						$make_link = 2;
						$su_mail_subscription_old = explode(',', $res[0][2]);
						$subscr_su_id = $res[0][0];
					}
					else
					{
						$error = true;

						$tags = array_merge($tags, array('{SU_EMAIL_SELECT_START}', '{SU_EMAIL_SELECT_END}'));
						$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

						$message .= isset($sut_messages['email_error']) ? str_replace($message_tags, $message_values, $sut_messages['email_error']) : '';
					}

				}
			}
		}

		if ((isset($_POST['su_login_reg']) && sb_strlen($su_login) < $min_login) ||
		    (isset($sut_fields_temps['su_login_need']) && (!isset($_POST['su_login_reg']) || $su_login == '')))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_LOGIN_SELECT_START}', '{SU_LOGIN_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$message .= isset($sut_messages['login_min_error']) ? str_replace(array_merge($message_tags, array('{SU_LOGIN_MIN}')), array_merge($message_values, array($min_login)), $sut_messages['login_min_error']) : '';
		}

		if ($update)
		{
			if ((isset($_POST['su_pass1']) && $su_pass != '' && sb_strlen($su_pass) < $min_password) ||
				(isset($sut_fields_temps['su_pass_need']) && (!isset($_POST['su_pass1']) || $su_pass == '')))
			{
				$error = true;

				$tags = array_merge($tags, array('{SU_PASS_SELECT_START}', '{SU_PASS_SELECT_END}'));
				$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

				$message .= isset($sut_messages['pass_min_error']) ? str_replace(array_merge($message_tags, array('{SU_PASS}', '{SU_PASS_MIN}')), array_merge($message_values, array($su_pass, $min_password)), $sut_messages['pass_min_error']) : '';
			}
		}
		else
		{
			if ((isset($_POST['su_pass1']) && sb_strlen($su_pass) < $min_password) ||
				(isset($sut_fields_temps['su_pass_need']) && (!isset($_POST['su_pass1']) || $su_pass == '')))
			{
				$error = true;

				$tags = array_merge($tags, array('{SU_PASS_SELECT_START}', '{SU_PASS_SELECT_END}'));
				$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

				$message .= isset($sut_messages['pass_min_error']) ? str_replace(array_merge($message_tags, array('{SU_PASS}', '{SU_PASS_MIN}')), array_merge($message_values, array($su_pass, $min_password)), $sut_messages['pass_min_error']) : '';
			}
		}

		if ((isset($_POST['su_pass2']) && isset($_POST['su_pass1']) && $su_pass != $su_pass2) ||
			(isset($sut_fields_temps['su_pass2_need']) && !isset($_POST['su_pass2'])))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PASS2_SELECT_START}', '{SU_PASS2_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$message .= isset($sut_messages['pass_error']) ? str_replace(array_merge($message_tags, array('{SU_PASS}', '{SU_PASS2}')), array_merge($message_values, array($su_pass, $su_pass2)), $sut_messages['pass_error']) : '';
		}

		if ((isset($_POST['su_email_reg']) && $su_email != '' && !preg_match('/^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/is'.SB_PREG_MOD, $su_email)) ||
			(isset($sut_fields_temps['su_email_need']) && (!isset($_POST['su_email_reg']) || $su_email == '')))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_EMAIL_SELECT_START}', '{SU_EMAIL_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_name_need']) && (!isset($_POST['su_name']) || $su_name == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_NAME_SELECT_START}', '{SU_NAME_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_foto_need']) && (!isset($_FILES['su_pers_foto']) || $_FILES['su_pers_foto']['tmp_name'] == '' || !is_uploaded_file($_FILES['su_pers_foto']['tmp_name'])))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_FOTO_SELECT_START}', '{SU_PERS_FOTO_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}
		elseif (isset($_FILES['su_pers_foto']) && is_uploaded_file($_FILES['su_pers_foto']['tmp_name']))
		{
			#TODO: При изменении данных сделать возможность удаления фотографии и удалять старую фотографию при замене

			// загружаем фотографию пользователя
			require_once(SB_CMS_LIB_PATH.'/sbUploader.inc.php');

	        $uploader = new sbUploader();
	        $uploader->setMaxFileSize(sbPlugins::getSetting('pl_site_users_max_upload_size'));
	        $uploader->setMaxImageSize(sbPlugins::getSetting('pl_site_users_max_upload_width'), sbPlugins::getSetting('pl_site_users_max_upload_height'));

	        // сохраняем файл
	        $success = $uploader->upload('su_pers_foto', array ('gif', 'jpeg', 'jpg', 'png'));
	        $file_name = false;
			if ($success)
			{
				$file_name = $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/', time().'_'.$_FILES['su_pers_foto']['name']);
			}

	        if (!$success || !$file_name)
			{
				$error = true;

				$tags = array_merge($tags, array('{SU_PERS_FOTO_SELECT_START}', '{SU_PERS_FOTO_SELECT_END}'));
				$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

				switch($uploader->getErrorCode())
				{
					case 2:
						$message .= isset($sut_messages['file_size_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}', '{SU_FILE_SIZE}')), array_merge($message_values, array($_FILES['su_pers_foto']['name'], sbPlugins::getSetting('pl_site_users_max_upload_size'))), $sut_messages['file_size_error']) : '';
						break;

					case 3:
						$message .= isset($sut_messages['image_size_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}', '{SU_IMAGE_WIDTH}', '{SU_IMAGE_HEIGHT}')), array_merge($message_values, array($_FILES['su_pers_foto']['name'], sbPlugins::getSetting('pl_site_users_max_upload_width'), sbPlugins::getSetting('pl_site_users_max_upload_height'))), $sut_messages['image_size_error']) : '';
						break;

					case 4:
						$message .= isset($sut_messages['file_ext_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}', '{SU_FILE_EXT}')), array_merge($message_values, array($_FILES['su_pers_foto']['name'], 'gif jpeg jpg png')), $sut_messages['file_ext_error']) : '';
						break;

					case 5:
					case 6:
						$message .= isset($sut_messages['file_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}')), array_merge($message_values, array($_FILES['su_pers_foto']['name'])), $sut_messages['file_error']) : '';
						break;

					default:
						$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
						break;
				}
			}
			else
			{
				$su_pers_foto = $file_name;
				if ($update && $su_pers_foto_old != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto_old) && $GLOBALS['sbVfs']->is_file(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto_old))
				{
					$GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto_old);
				}
			}
		}

		if (isset($sut_fields_temps['su_pers_birth_need']) && (!isset($_POST['su_pers_birth']) || $su_pers_birth == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_BIRTH_SELECT_START}', '{SU_PERS_BIRTH_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_sex_need']) && (!isset($_POST['su_pers_sex']) || $su_pers_sex === '' || intval($su_pers_sex) < 0))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_SEX_SELECT_START}', '{SU_PERS_SEX_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_phone_need']) && (!isset($_POST['su_pers_phone']) || $su_pers_phone == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_PHONE_SELECT_START}', '{SU_PERS_PHONE_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_mob_phone_need']) && (!isset($_POST['su_pers_mob_phone']) || $su_pers_mob_phone == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_MOB_PHONE_SELECT_START}', '{SU_PERS_MOB_PHONE_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_zip_need']) && (!isset($_POST['su_pers_zip']) || $su_pers_zip == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_ZIP_SELECT_START}', '{SU_PERS_ZIP_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_adress_need']) && (!isset($_POST['su_pers_adress']) || $su_pers_adress == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_ADRESS_SELECT_START}', '{SU_PERS_ADRESS_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_pers_addition_need']) && (!isset($_POST['su_pers_addition']) || $su_pers_addition == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_PERS_ADDITION_SELECT_START}', '{SU_PERS_ADDITION_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

        if(isset($_POST['su_pers_soc_akk']) && is_array($_POST['su_pers_soc_akk']))
        {
            $del_res = sql_param_query('DELETE FROM sb_socnet_users WHERE sbsu_id NOT IN (?a) AND sbsu_uid = ?d', $_POST['su_pers_soc_akk'], $su_id);
            $_SESSION['sbAuth']->getSocialNetworks(true);
        }

		if (isset($sut_fields_temps['su_work_name_need']) && (!isset($_POST['su_work_name']) || $su_work_name == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_NAME_SELECT_START}', '{SU_WORK_NAME_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_unit_need']) && (!isset($_POST['su_work_unit']) || $su_work_unit == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_UNIT_SELECT_START}', '{SU_WORK_UNIT_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_position_need']) && (!isset($_POST['su_work_position']) || $su_work_position == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_POSITION_SELECT_START}', '{SU_WORK_POSITION_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_office_number_need']) && (!isset($_POST['su_work_office_number']) || $su_work_office_number == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_OFFICE_NUMBER_SELECT_START}', '{SU_WORK_OFFICE_NUMBER_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_phone_need']) && (!isset($_POST['su_work_phone']) || $su_work_phone == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_PHONE_SELECT_START}', '{SU_WORK_PHONE_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_phone_inner_need']) && (!isset($_POST['su_work_phone_inner']) || $su_work_phone_inner == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_PHONE_INNER_SELECT_START}', '{SU_WORK_PHONE_INNER_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_fax_need']) && (!isset($_POST['su_work_fax']) || $su_work_fax == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_FAX_SELECT_START}', '{SU_WORK_FAX_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if ((isset($_POST['su_work_email']) && $su_work_email != '' && !preg_match('/^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/is'.SB_PREG_MOD, $su_work_email)) ||
		    (isset($sut_fields_temps['su_work_email_need']) && (!isset($_POST['su_work_email']) || $su_work_email == '')))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_EMAIL_SELECT_START}', '{SU_WORK_EMAIL_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_work_addition_need']) && (!isset($_POST['su_work_addition']) || $su_work_addition == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_WORK_ADDITION_SELECT_START}', '{SU_WORK_ADDITION_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_forum_nick_need']) && (!isset($_POST['su_forum_nick']) || $su_forum_nick == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_FORUM_NICK_SELECT_START}', '{SU_FORUM_NICK_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if (isset($sut_fields_temps['su_forum_text_need']) && (!isset($_POST['su_forum_text']) || $su_forum_text == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_FORUM_TEXT_SELECT_START}', '{SU_FORUM_TEXT_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if(isset($sut_fields_temps['su_mail_date_need']) && $sut_fields_temps['su_mail_date_need'] == 1 && $su_mail_date == '' || ($su_mail_status == 2 && $su_mail_date == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_MAILLISTS_DATE_SELECT_START}', '{SU_MAILLISTS_DATE_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if(isset($sut_fields_temps['su_mail_status_need']) && $sut_fields_temps['su_mail_status_need'] == 1 && ($su_mail_status == '' || $su_mail_status == -1))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_MAILLISTS_STATUS_SELECT_START}', '{SU_MAILLISTS_STATUS_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if(isset($sut_fields_temps['su_mail_lang_need']) && $sut_fields_temps['su_mail_lang_need'] == 1 && $su_mail_lang == '')
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_MAILLISTS_LANG_SELECT_START}', '{SU_MAILLISTS_LANG_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		if(isset($sut_fields_temps['su_mail_subscription_need']) && $sut_fields_temps['su_mail_subscription_need'] == 1 && (count($su_mail) == 0 || isset($su_mail[0]) && $su_mail[0] == ''))
		{
			$error = true;

			$tags = array_merge($tags, array('{SU_MAILLISTS_SELECT_START}', '{SU_MAILLISTS_SELECT_END}'));
			$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

			$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
		}

		require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

		$users_error = false;
		$layout = new sbLayout();

		if ($update)
		{
			$row = $layout->checkPluginInputFields('pl_site_users', $users_error, $sut_fields_temps, $su_id, 'sb_site_users', 'su_id', false, $sut_fields_temps['su_date_format']);
		}
		else
		{
			$row = $layout->checkPluginInputFields('pl_site_users', $users_error, $sut_fields_temps, -1, '', '', false, $sut_fields_temps['su_date_format']);
		}

		if ($users_error)
		{
			foreach ($row as $f_name => $f_array)
			{
				$f_error = $f_array['error'];
				$f_tag = $f_array['tag'];

				$tags = array_merge($tags, array('{'.sb_strtoupper($f_tag).'_SELECT_START}', '{'.sb_strtoupper($f_tag).'_SELECT_END}'));
				$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));
				switch($f_error)
				{
					case 2:
						$message .= isset($sut_messages['file_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}')), array_merge($message_values, array(isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : '')), $sut_messages['file_error']) : '';
						break;

					case 3:
						$message .= isset($sut_messages['file_ext_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}', '{SU_FILE_EXT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), $f_array['file_types'])), $sut_messages['file_ext_error']) : '';
						break;

					case 4:
						$message .= isset($sut_messages['file_size_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}', '{SU_FILE_SIZE}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_size'))), $sut_messages['file_size_error']) : '';
						break;

					case 5:
						$message .= isset($sut_messages['image_size_error']) ? str_replace(array_merge($message_tags, array('{SU_FILE}', '{SU_IMAGE_WIDTH}', '{SU_IMAGE_HEIGHT}')), array_merge($message_values, array((isset($_FILES[$f_name]) ? $_FILES[$f_name]['name'] : ''), sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'))), $sut_messages['image_size_error']) : '';
						break;

					default:
						$fields_message = isset($sut_messages['fields_error']) ? str_replace($message_tags, $message_values, $sut_messages['fields_error']) : '';
						break;
				}
			}
		}

		$message .= $fields_message;

		$error = $error || $users_error;

		if (!$error)
		{
			if (isset($_POST['su_login_reg']))
			{
				$res = sql_param_query('SELECT su_id FROM sb_site_users WHERE su_login <> "" AND su_login=? AND su_id <> ?d', $su_login, $su_id);
				if ($res)
				{
					$error = true;

					$tags = array_merge($tags, array('{SU_LOGIN_SELECT_START}', '{SU_LOGIN_SELECT_END}'));
					$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

					$message .= isset($sut_messages['login_error']) ? str_replace($message_tags, $message_values, $sut_messages['login_error']) : '';
				}
			}
		}

		if (sb_strpos($sut_form, '{SU_CAPTCHA}') !== false || sb_strpos($sut_form, '{SU_CAPTCHA_IMG}') !== false)
		{
			if (!sbProgCheckTuring('su_captcha', 'su_captcha_code'))
			{
				$error = true;

				$tags = array_merge($tags, array('{SU_CAPTCHA_SELECT_START}', '{SU_CAPTCHA_SELECT_END}'));
				$values = array_merge($values, array($sut_fields_temps['su_select_start'], $sut_fields_temps['su_select_end']));

				$message .= isset($sut_messages['captcha_error']) ? str_replace($message_tags, $message_values, $sut_messages['captcha_error']) : '';
			}
		}

		if((!isset($_POST['su_mail']) || count($_POST['su_mail']) == 0) && sb_strpos($sut_form, '{SU_MAILLISTS}') !== false)
		{
			$su_mail = array();
		}

		if ($error)
		{
			$layout->deletePluginFieldsFiles();
			if ($su_pers_foto != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto) && $GLOBALS['sbVfs']->is_file(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto))
			{
				$GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto);
			}
		}
		else
		{
			if($make_link == 2)
			{
				// Пользователь уже есть в базе, идет подписка на новости
				$group_ids = explode('^', $params['ids']);
				//	Достаю раздел
				$res = sql_query('SELECT c.cat_id, l.link_src_cat_id FROM sb_catlinks l, sb_categs c
							WHERE c.cat_ident="pl_site_users" AND l.link_el_id = ?d AND l.link_cat_id = c.cat_id', $subscr_su_id);

				if(!$res)
				{
					$error = true;
					$message .= isset($sut_messages['registrate_error']) ? str_replace($message_tags, $message_values, $sut_messages['registrate_error']) : '';
				}
				else
				{
					$real_cat_id = 0;
					foreach($res as $key => $value)
					{
						$k = array_keys($group_ids, $value[0]);
						if(count($k) > 0)
						{
							unset($group_ids[$k[0]]);
						}
						elseif($value[1] == 0)
						{
							$real_cat_id = $value[0];
						}
					}

					$mkl_err = false;
					foreach($group_ids as $cat_id)
			    	{
						$res_link = sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id) VALUES (?d, ?d, ?d)', $cat_id, $subscr_su_id, $real_cat_id);
						if(!$res_link)
						{
							$mkl_err = true;
							break;
						}
					}

					if(empty($group_ids))
					{
						$mail_categs = array_diff($su_mail, $su_mail_subscription_old);

						if (count($mail_categs) == 0)
						{
							// указанный мейл уже есть в группе пользователей, набор листов рассылок не изменился
							$group_ids = array(0);
							$error = true;
							$message .= isset($sut_messages['login_error']) ? str_replace($message_tags, $message_values, $sut_messages['login_error']) : '';
						}
						else
						{
							// изменился набор листов рассылок, апдейтим пользователя сайта
							$group_ids = array($real_cat_id);
							$su_id = $subscr_su_id;

							$row['su_mail_subscription'] = implode(',', array_merge($mail_categs, $su_mail_subscription_old));

							sql_param_query('UPDATE sb_site_users SET ?a WHERE su_id=?d', $row, $subscr_su_id, sprintf(KERNEL_PROG_SITEUSERS_UPDATE_OK, ($su_login != '' ? $su_login : $su_email)));
						}
					}
					else
					{
				    	if ($mkl_err)
						{
							$error = true;
							$message .= isset($sut_messages['registrate_error']) ? str_replace($message_tags, $message_values, $sut_messages['registrate_error']) : '';
							sb_add_system_message(sprintf(KERNEL_PROG_SITEUSERS_ADD_ERROR, ($su_login != '' ? $su_login : $su_email)), SB_MSG_WARNING);
						}
						else
						{
							$group_ids = array($real_cat_id);
							$su_id = $subscr_su_id;

							$mail_categs = array_diff($su_mail, $su_mail_subscription_old);

							if (count($mail_categs) != 0)
							{
								// изменился набор листов рассылок, апдейтим пользователя сайта
								$row['su_mail_subscription'] = implode(',', array_merge($mail_categs, $su_mail_subscription_old));

								sql_param_query('UPDATE sb_site_users SET ?a WHERE su_id=?d', $row, $subscr_su_id);
							}

				    		sb_add_system_message(sprintf(KERNEL_PROG_SITEUSERS_UPDATE_OK, ($su_login != '' ? $su_login : $su_email)), SB_MSG_INFORMATION);
						}
					}
				}
			}
			elseif ($update || $make_link == 1)
			{
				$res = sql_param_query('SELECT c.cat_id, c.cat_fields, l.link_src_cat_id FROM sb_categs c, sb_catlinks l WHERE c.cat_ident="pl_site_users" AND l.link_cat_id=c.cat_id AND l.link_el_id=?d', ($make_link == 1 ? $subscr_su_id : $su_id));
				if (!$res)
				{
					$error = true;
					$message .= isset($sut_messages['registrate_error']) ? str_replace($message_tags, $message_values, $sut_messages['registrate_error']) : '';
				}
				else
				{
					$su_status = 0;
					$cat_su_status = 0;
					$group_ids = array();
					$main_cat_id = 0;

					foreach ($res as $val)
					{
						list($cat_id, $cat_fields, $src) = $val;

						$group_ids[] = $cat_id;
						if ($cat_fields != '')
						{
							$cat_fields = unserialize($cat_fields);
							if (isset($cat_fields['cat_status']) && $cat_fields['cat_status'] > $cat_su_status)
								$cat_su_status = intval($cat_fields['cat_status']);
						}

						if ($src == 0)
						{
							$main_cat_id = $cat_id;
						}
					}

					if ($cat_su_status == 1)
					{
						// отправляем на модерацию
						$su_status = 1;
					}
					elseif ($cat_su_status == 3)
					{
						// модерация и активация по мейлу
						if ($su_email == $su_email_old)
							$su_status = 1;
						else
							$su_status = 3;
					}
					elseif ($cat_su_status == 2 && $su_email != $su_email_old)
					{
						// активация по мейлу
						$su_status = 2;
					}

					$row['su_last_ip'] = sbAuth::getIP();

					if ($su_status == 2 || $su_status == 3)
					{
						$su_activation_code = md5(time().uniqid());
					}
					else
					{
						$su_activation_code = '';
					}

					$row['su_activation_code'] = $su_activation_code;
					$row['su_email'] = $su_email;

					if ($su_pass != '')
						$row['su_pass'] = md5($su_pass);

					$row['su_login'] = $su_login;
					$row['su_name'] = $su_name;
					$row['su_last_date'] = time();
					$row['su_status'] = $su_status;
					$row['su_pers_foto'] = ($su_pers_foto != '' ? $su_pers_foto : $su_pers_foto_old);
					$row['su_pers_phone'] = $su_pers_phone;
					$row['su_pers_mob_phone'] = $su_pers_mob_phone;
					$row['su_pers_birth'] = $su_pers_birth;
					$row['su_pers_sex'] = $su_pers_sex;
					$row['su_pers_zip'] = $su_pers_zip;
					$row['su_pers_adress'] = $su_pers_adress;
					$row['su_pers_addition'] = $su_pers_addition;
					$row['su_work_name'] = $su_work_name;
					$row['su_work_phone'] = $su_work_phone;
					$row['su_work_phone_inner'] = $su_work_phone_inner;
					$row['su_work_fax'] = $su_work_fax;
					$row['su_work_email'] = $su_work_email;
					$row['su_work_addition'] = $su_work_addition;
					$row['su_work_office_number'] = $su_work_office_number;
					$row['su_work_unit'] = $su_work_unit;
					$row['su_work_position'] = $su_work_position;
					$row['su_forum_nick'] = $su_forum_nick;
					$row['su_forum_text'] = $su_forum_text;
					$row['su_mail_lang'] = $su_mail_lang;
					$row['su_mail_status'] = $su_mail_status;
					$row['su_mail_date'] = sb_datetoint($su_mail_date, $sut_fields_temps['su_date_format']);

					if(!empty($su_mail) && $make_link == 1 || $update)
					{
						$row['su_mail_subscription'] = implode(',', $su_mail);
					}

					sql_param_query('UPDATE sb_site_users SET ?a WHERE su_id=?d', $row, ($make_link == 1 ? $subscr_su_id : $su_id), sprintf(KERNEL_PROG_SITEUSERS_UPDATE_OK, ($su_login != '' ? $su_login : $su_email)));
					if($make_link == 1)
					{
						$group_ids = explode('^', $params['ids']);
						$root_id = -1;
				    	foreach($group_ids as $c_id)
				    	{
				    		if ($root_id == -1)
				    		{
								sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id)
															VALUES (?d, ?d, 0)', $c_id, $subscr_su_id);
								$id_l = sql_insert_id();
					    		sql_query('UPDATE sb_catlinks SET link_cat_id = ?d, link_el_id = ?d, link_src_cat_id = ?d
					    					WHERE
											link_cat_id = ?d AND link_el_id=?d AND link_id != ?d', $main_cat_id, $subscr_su_id, $c_id,
					    					$main_cat_id, $subscr_su_id, $id_l);

								$root_id = $cat_id;
							}
						    else
						    {
						    	sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id) VALUES (?d, ?d, ?d)', $c_id, $subscr_su_id, $root_id);
						    }
						}
						$su_id = $subscr_su_id;
					}
				}
			}
			else
			{
				$group_ids = explode('^', $params['ids']);
				$res = sql_param_query('SELECT cat_id, cat_fields FROM sb_categs WHERE cat_id IN (?a)', $group_ids);
				if (!$res)
				{
					$error = true;
					$message .= isset($sut_messages['registrate_error']) ? str_replace($message_tags, $message_values, $sut_messages['registrate_error']) : '';
				}
				else
				{
					$su_status = 0;
					$su_domains = array();
					$group_ids = array();

					foreach ($res as $val)
					{
						list($cat_id, $cat_fields) = $val;

						$group_ids[] = $cat_id;
						if ($cat_fields != '')
						{
							$cat_fields = unserialize($cat_fields);
							if (isset($cat_fields['cat_status']) && $cat_fields['cat_status'] > $su_status)
								$su_status = intval($cat_fields['cat_status']);

							if (isset($cat_fields['cat_domains']))
							{
								foreach ($cat_fields['cat_domains'] as $domain)
								{
									if (!in_array($domain, $su_domains))
									{
										$su_domains[] = $domain;
									}
								}
							}
						}
					}

					$row['su_last_ip'] = sbAuth::getIP();

					if ($su_status == 2 || $su_status == 3)
					{
						$su_activation_code = md5(time().uniqid());
					}
					else
					{
						$su_activation_code = '';
					}

					$row['su_activation_code'] = $su_activation_code;
					$row['su_email'] = $su_email;
					$row['su_pass'] = md5($su_pass);
					if($su_login == '' && $su_email != '' && $min_password == 0)
						$row['su_login'] = $su_email;
					else
						$row['su_login'] = $su_login;
					$row['su_name'] = $su_name;
					$row['su_reg_date'] = time();
					$row['su_last_date'] = time();
					$row['su_active_date'] = 0;
					$row['su_domains'] = implode(',', $su_domains);
					$row['su_status'] = $su_status;
					$row['su_pers_foto'] = $su_pers_foto;
					$row['su_pers_phone'] = $su_pers_phone;
					$row['su_pers_mob_phone'] = $su_pers_mob_phone;
					$row['su_pers_birth'] = $su_pers_birth;
					$row['su_pers_sex'] = $su_pers_sex;
					$row['su_pers_zip'] = $su_pers_zip;
					$row['su_pers_adress'] = $su_pers_adress;
					$row['su_pers_addition'] = $su_pers_addition;
					$row['su_work_name'] = $su_work_name;
					$row['su_work_phone'] = $su_work_phone;
					$row['su_work_phone_inner'] = $su_work_phone_inner;
					$row['su_work_fax'] = $su_work_fax;
					$row['su_work_email'] = $su_work_email;
					$row['su_work_addition'] = $su_work_addition;
					$row['su_work_office_number'] = $su_work_office_number;
					$row['su_work_unit'] = $su_work_unit;
					$row['su_work_position'] = $su_work_position;
					$row['su_forum_nick'] = $su_forum_nick;
					$row['su_forum_text'] = $su_forum_text;
					$row['su_mail_lang'] = $su_mail_lang;
					$row['su_mail_status'] = $su_mail_status;
					$row['su_mail_date'] = 	sb_datetoint($su_mail_date, $sut_fields_temps['su_date_format']);
					$row['su_mail_subscription'] = implode(',', $su_mail);

					$su_id = sbProgAddElement('sb_site_users', 'su_id', $row, $group_ids);

					if (!$su_id)
					{
						$error = true;
						$message .= isset($sut_messages['registrate_error']) ? str_replace($message_tags, $message_values, $sut_messages['registrate_error']) : '';
						sb_add_system_message(sprintf(KERNEL_PROG_SITEUSERS_ADD_ERROR, ($su_login != '' ? $su_login : $su_email)), SB_MSG_WARNING);
					}
				    else
				    {
				    	sb_add_system_message(sprintf(KERNEL_PROG_SITEUSERS_ADD_OK, ($su_login != '' ? $su_login : $su_email)), SB_MSG_INFORMATION);
				    }
				}
			}
		}

		if (!$error)
		{
			require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';

			$mail = new sbMail();

			$type = sbPlugins::getSetting('sb_letters_type');

        	// отправляем письма и делаем переадресацию
			if ($su_email != '')
			{
				$email_subj = str_replace('{SU_PASS}', $su_pass, $sut_messages['user_subj']);
				$email_subj = fSite_Users_Parse($email_subj, $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');

				//чистим код от инъекций
				$email_subj = sb_clean_string($email_subj);

				ob_start();
				eval(' ?>'.$email_subj.'<?php ');
				$email_subj = trim(ob_get_clean());

				$email_text = str_replace('{SU_PASS}', $su_pass, $sut_messages['user_text']);
				$email_text = fSite_Users_Parse($email_text, $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');

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

					$mail->send(array($su_email));
				}
			}

			$mod_emails = array();

			$mod_params_emails = explode(' ', trim(str_replace(',', ' ', $params['mod_emails'])));
			$mod_categs_emails = array();
			$mod_users_emails = array();

			$res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id IN (?a)', $group_ids);
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
				$email_subj = str_replace('{SU_PASS}', $su_pass, $sut_messages['admin_subj']);
				$email_subj = fSite_Users_Parse($email_subj, $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');

				//чистим код от инъекций
				$email_subj = sb_clean_string($email_subj);

				ob_start();
				eval(' ?>'.$email_subj.'<?php ');
				$email_subj = trim(ob_get_clean());

				$email_text = str_replace('{SU_PASS}', $su_pass, $sut_messages['admin_text']);
				$email_text = fSite_Users_Parse($email_text, $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');

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

			if (isset($params['do_login']) && $params['do_login'] == 1 && !isset($_SESSION['sbAuth']))
			{
				new sbAuth($su_login, $su_email, $su_pass);
			}

			sb_setcookie('su_show_info', 1);

			if (isset($params['page']) && trim($params['page']) != '')
			{
				header('Location: '.sb_sanitize_header($params['page'].(sb_substr_count($params['page'], '?') > 0 ? '&' : '?').'su_cid='.$su_id));
			}
			elseif (!isset($_GET['noredir']))
			{
				header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].'?su_cid='.$su_id));
			}
			else
			{
				if (isset($sut_messages['registrate']) && trim($sut_messages['registrate']) != '')
				{
					$result = fSite_Users_Parse($sut_messages['registrate'], $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '_val');
					$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

					//чистим код от инъекций
                    $result = sb_clean_string($result);

					eval(' ?>'.$result.'<?php ');
				}
			}
			exit (0);
		}
		else
		{
			$layout->deletePluginFieldsFiles();
			if ($su_pers_foto != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto) && $GLOBALS['sbVfs']->is_file(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto))
			{
				$GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto);
			}
		}
	}

	$tags = array_merge($tags, array('{MESSAGE}',
				'{SU_ACTION}',
				'{SU_LOGIN}',
                '{SU_EMAIL}',
				'{SU_PASS}',
				'{SU_PASS2}',
				'{SU_CAPTCHA}',
				'{SU_CAPTCHA_IMG}',
                '{SU_NAME}',
                '{SU_PERS_FOTO}',
                '{SU_PERS_BIRTH}',
                '{SU_PERS_SEX}',
                '{SU_PERS_PHONE}',
                '{SU_PERS_MOB_PHONE}',
			    '{SU_PERS_ZIP}',
				'{SU_PERS_ADRESS}',
				'{SU_PERS_ADDITION}',
				'{SU_WORK_NAME}',
				'{SU_WORK_UNIT}',
				'{SU_WORK_POSITION}',
				'{SU_WORK_OFFICE_NUMBER}',
				'{SU_WORK_PHONE}',
				'{SU_WORK_PHONE_INNER}',
				'{SU_WORK_FAX}',
				'{SU_WORK_EMAIL}',
				'{SU_WORK_ADDITION}',
				'{SU_FORUM_NICK}',
				'{SU_FORUM_TEXT}',
				'{SU_MAILLISTS}',
				'{SU_MAILLISTS_LANG}',
				'{SU_MAILLISTS_STATUS}',
				'{SU_MAILLISTS_DATE}',
                '{SU_PERS_SOCIAL_AKK}'));

	$values[] = $message;
	$values[] = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
	$values[] = (isset($sut_fields_temps['su_login']) && trim($sut_fields_temps['su_login']) != '' ? str_replace('{VALUE}', $su_login, $sut_fields_temps['su_login']) : '');
	$values[] = (isset($sut_fields_temps['su_email']) && trim($sut_fields_temps['su_email']) != '' ? str_replace('{VALUE}', $su_email, $sut_fields_temps['su_email']) : '');
	$values[] = (isset($sut_fields_temps['su_pass']) && trim($sut_fields_temps['su_pass']) != '' ? str_replace('{VALUE}', $su_pass, $sut_fields_temps['su_pass']) : '');
	$values[] = (isset($sut_fields_temps['su_pass2']) && trim($sut_fields_temps['su_pass2']) != '' ? str_replace('{VALUE}', $su_pass2, $sut_fields_temps['su_pass2']) : '');

	// Вывод КАПЧИ
	if ((sb_strpos($sut_form, '{SU_CAPTCHA}') !== false || sb_strpos($sut_form, '{SU_CAPTCHA_IMG}') !== false) &&
		isset($sut_fields_temps['su_captcha']) && trim($sut_fields_temps['su_captcha']) != '' &&
		isset($sut_fields_temps['su_captcha_img']) && trim($sut_fields_temps['su_captcha_img']) != '')
	{
		$turing = sbProgGetTuring();
		if ($turing)
		{
			$values[] = $sut_fields_temps['su_captcha'];
			$values[] = str_replace(array('{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}'), $turing, $sut_fields_temps['su_captcha_img']);
		}
		else
		{
			$values[] = $sut_fields_temps['su_captcha'];
			$values[] = '';
		}
	}
	else
	{
		$values[] = '';
		$values[] = '';
	}

	$values[] = (isset($sut_fields_temps['su_name']) && trim($sut_fields_temps['su_name']) != '' ? str_replace('{VALUE}', $su_name, $sut_fields_temps['su_name']) : '');
	$values[] = (isset($sut_fields_temps['su_pers_foto']) && trim($sut_fields_temps['su_pers_foto']) != '' ? str_replace('{VALUE}', $su_pers_foto, $sut_fields_temps['su_pers_foto']) : '');

	if ($su_pers_birth != '' && $su_pers_birth != 0)
		$su_pers_birth = sb_parse_date($su_pers_birth, $sut_fields_temps['su_date_format']);
	else
		$su_pers_birth = '';

	$values[] = (isset($sut_fields_temps['su_pers_birth']) && trim($sut_fields_temps['su_pers_birth']) != '' ? str_replace('{VALUE}', $su_pers_birth, $sut_fields_temps['su_pers_birth']) : '');

	// Пол
	if (isset($sut_fields_temps['su_pers_sex']) && trim($sut_fields_temps['su_pers_sex']) != '')
	{
		$options = '';
		if (isset($GLOBALS['sb_sex_arr'][$sut_lang]) && isset($sut_fields_temps['su_pers_sex_opt']) && trim($sut_fields_temps['su_pers_sex_opt']) != '')
		{
			$selected_str = sb_stripos($sut_fields_temps['su_pers_sex_opt'], 'option') !== false ? ' selected' : ' checked';
			foreach ($GLOBALS['sb_sex_arr'][$sut_lang] as $key => $value)
			{
				$options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}'),
										   array($key, ($key == $su_pers_sex ? $selected_str : ''), $value),
										   $sut_fields_temps['su_pers_sex_opt']);
			}
		}
		$values[] = str_replace('{OPTIONS}', $options, $sut_fields_temps['su_pers_sex']);
	}
	else
	{
		$values[] = '';
	}

	$values[] = (isset($sut_fields_temps['su_pers_phone']) && trim($sut_fields_temps['su_pers_phone']) != '' ? str_replace('{VALUE}', $su_pers_phone, $sut_fields_temps['su_pers_phone']) : '');
	$values[] = (isset($sut_fields_temps['su_pers_mob_phone']) && trim($sut_fields_temps['su_pers_mob_phone']) != '' ? str_replace('{VALUE}', $su_pers_mob_phone, $sut_fields_temps['su_pers_mob_phone']) : '');
	$values[] = (isset($sut_fields_temps['su_pers_zip']) && trim($sut_fields_temps['su_pers_zip']) != '' ? str_replace('{VALUE}', $su_pers_zip, $sut_fields_temps['su_pers_zip']) : '');
	$values[] = (isset($sut_fields_temps['su_pers_adress']) && trim($sut_fields_temps['su_pers_adress']) != '' ? str_replace('{VALUE}', $su_pers_adress, $sut_fields_temps['su_pers_adress']) : '');
	$values[] = (isset($sut_fields_temps['su_pers_addition']) && trim($sut_fields_temps['su_pers_addition']) != '' ? str_replace('{VALUE}', $su_pers_addition, $sut_fields_temps['su_pers_addition']) : '');
	$values[] = (isset($sut_fields_temps['su_work_name']) && trim($sut_fields_temps['su_work_name']) != '' ? str_replace('{VALUE}', $su_work_name, $sut_fields_temps['su_work_name']) : '');
	$values[] = (isset($sut_fields_temps['su_work_unit']) && trim($sut_fields_temps['su_work_unit']) != '' ? str_replace('{VALUE}', $su_work_unit, $sut_fields_temps['su_work_unit']) : '');
	$values[] = (isset($sut_fields_temps['su_work_position']) && trim($sut_fields_temps['su_work_position']) != '' ? str_replace('{VALUE}', $su_work_position, $sut_fields_temps['su_work_position']) : '');
	$values[] = (isset($sut_fields_temps['su_work_office_number']) && trim($sut_fields_temps['su_work_office_number']) != '' ? str_replace('{VALUE}', $su_work_office_number, $sut_fields_temps['su_work_office_number']) : '');
	$values[] = (isset($sut_fields_temps['su_work_phone']) && trim($sut_fields_temps['su_work_phone']) != '' ? str_replace('{VALUE}', $su_work_phone, $sut_fields_temps['su_work_phone']) : '');
	$values[] = (isset($sut_fields_temps['su_work_phone_inner']) && trim($sut_fields_temps['su_work_phone_inner']) != '' ? str_replace('{VALUE}', $su_work_phone_inner, $sut_fields_temps['su_work_phone_inner']) : '');
	$values[] = (isset($sut_fields_temps['su_work_fax']) && trim($sut_fields_temps['su_work_fax']) != '' ? str_replace('{VALUE}', $su_work_fax, $sut_fields_temps['su_work_fax']) : '');
	$values[] = (isset($sut_fields_temps['su_work_email']) && trim($sut_fields_temps['su_work_email']) != '' ? str_replace('{VALUE}', $su_work_email, $sut_fields_temps['su_work_email']) : '');
	$values[] = (isset($sut_fields_temps['su_work_addition']) && trim($sut_fields_temps['su_work_addition']) != '' ? str_replace('{VALUE}', $su_work_addition, $sut_fields_temps['su_work_addition']) : '');
	$values[] = (isset($sut_fields_temps['su_forum_nick']) && trim($sut_fields_temps['su_forum_nick']) != '' ? str_replace('{VALUE}', $su_forum_nick, $sut_fields_temps['su_forum_nick']) : '');
	$values[] = (isset($sut_fields_temps['su_forum_text']) && trim($sut_fields_temps['su_forum_text']) != '' ? str_replace('{VALUE}', $su_forum_text, $sut_fields_temps['su_forum_text']) : '');

	if(isset($params['maillist_categs']) && $params['maillist_categs'] != '')
	{
		$temps = array();
		$temps['su_mail_subscription'] = isset($sut_fields_temps['su_mail_subscription']) ? $sut_fields_temps['su_mail_subscription'] : '';
		$temps['su_mail_lang'] = isset($sut_fields_temps['su_mail_lang']) ? $sut_fields_temps['su_mail_lang'] : '';
		$temps['su_mail_lang_opt'] = isset($sut_fields_temps['su_mail_lang_opt']) ? $sut_fields_temps['su_mail_lang_opt'] : '';
		$temps['su_mail_status'] = isset($sut_fields_temps['su_mail_status']) ? $sut_fields_temps['su_mail_status'] : '';
		$temps['su_mail_status_opt'] = isset($sut_fields_temps['su_mail_status_opt']) ? $sut_fields_temps['su_mail_status_opt'] : '';
		$temps['su_mail_date'] = isset($sut_fields_temps['su_mail_date']) ? $sut_fields_temps['su_mail_date'] : '';

		if(stripos($temps['su_mail_subscription'], 'radio') !== false)
		{
			$tmp_su_mail = array();
			foreach($su_mail as $key => $value)
			{
				$tmp_su_mail[1] = $value;
			}
			$su_mail = $tmp_su_mail;
		}
		else
		{
			$tmp_su_mail = array();
			foreach($su_mail as $key => $value)
			{
				$tmp_su_mail[$value] = $value;
			}
			$su_mail = $tmp_su_mail;
		}

		$mail_data = array();
		if($update)
		{
			$mail_data['su_mail_date'] = isset($_POST['su_mail_date']) ? $_POST['su_mail_date'] : !is_null($su_mail_date) ? sb_parse_date($su_mail_date, $sut_fields_temps['su_date_format']) : null;
			$mail_data['su_mail_status'] = $su_mail_status;
			$mail_data['su_mail_lang'] = $su_mail_lang;
			$mail_data['su_mail'] = $su_mail;
		}

		$maillist_categs = isset($params['maillist_categs']) ? explode('^', $params['maillist_categs']) : '';
		$maillist_subcats = isset($params['maillist_subcats']) ? $params['maillist_subcats'] : '1';

		@include_once(SB_CMS_PL_PATH.'/pl_maillist/pl_maillist.inc.php');
		$maillist_temps = fMaillist_Parse_Maillists($temps, $maillist_categs, $maillist_subcats, $mail_data, isset($sut_fields_temps['su_mail_subscription']) ? $sut_fields_temps['su_mail_subscription'] : 0);

		$values[] = $maillist_temps['su_mail_subscription'];
		$values[] = $maillist_temps['su_mail_lang'];
		$values[] = $maillist_temps['su_mail_status'];
		$values[] = $maillist_temps['su_mail_date'];
	}
	else
	{
		$values[] = '';
		$values[] = '';
		$values[] = '';
		$values[] = '';
	}

    //Формируем чекбоксы с аккаунтами в соц. сетях
    $res = sql_param_query('SELECT sbsu_id, sbsu_sn_type FROM sb_socnet_users WHERE sbsu_uid=?d', $su_id);
    if($res)
    {
        require_once SB_CMS_LANG_PATH . '/pl_site_users.lng.php';
        $accounts = '<input type="hidden" value="0" name="su_pers_soc_akk[]"/>';
        foreach($res as $row)
        {
            $accounts .= (isset($sut_fields_temps['su_pers_soc_akk']) && trim($sut_fields_temps['su_pers_soc_akk']) != '' ? str_replace(array('{SOC_AKK_ID}', '{SOC_AKK_LABEL}'), array($row[0], $PL_SITE_USERS_ACCOUNTS[$row[1]]), $sut_fields_temps['su_pers_soc_akk']) : '');
        }
        $values[] = $accounts;
    }
    else
    {
        $values[] = (isset($sut_messages['social_akk']) && trim($sut_messages['social_akk']) != '' ? $sut_messages['social_akk'] : '');
    }

	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
	// формируем SQL-запрос для пользовательских полей
	if ($res && $res[0][0] != '')
	{
	    $users_fields = unserialize($res[0][0]);
		foreach ($users_fields as $value)
	    {
	        if ($value['type'] == 'yandex_coords')
            {
            	$tags[] = '{'.$value['tag'].'_API_KEY}';
            	$values[] = sbPlugins::getSetting('sb_yandex_maps_id');
            }
	    }
	}

	@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	sbLayout::parsePluginInputFields('pl_site_users', $sut_fields_temps, $sut_fields_temps['su_date_format'], $tags, $values, (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1), 'sb_site_users', 'su_id');

	$result = str_replace($tags, $values, $sut_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	if (!isset($_POST['su_login_reg']) && !isset($_POST['su_email_reg']))
	{
		$GLOBALS['sbCache']->save('pl_site_users', $result);
	}
	else
	{
		//чистим код от инъекций
        $result = sb_clean_string($result);

		eval(' ?>'.$result.'<?php ');
	}
}

// Форма напоминания пароля
function fSite_Users_Elem_Remind($el_id, $temp_id, $params, $tag_id)
{
	//$res = sql_param_query('SELECT sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages FROM sb_site_users_temps WHERE sut_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_site_users_temps', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_SITE_USERS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_site_users', '');
        return;
	}

	list($sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages) = $res[0];

	$sut_fields_temps = (trim($sut_fields_temps) != '' ? unserialize($sut_fields_temps) : array());
	$sut_categs_temps = (trim($sut_categs_temps) != '' ? unserialize($sut_categs_temps) : array());
	$sut_messages = (trim($sut_messages) != '' ? unserialize($sut_messages) : array());

	$result = '';
	$message = '';

	$login = isset($_POST['su_login_remind']) && trim($_POST['su_login_remind']) != '' ? $_POST['su_login_remind'] : null;
	$email = isset($_POST['su_email_remind']) && trim($_POST['su_email_remind']) != '' ? $_POST['su_email_remind'] : null;

	if (is_null($login) && is_null($email))
	{
		// просто вывод формы, данные пока не пришли
		if (trim($sut_form) == '')
		{
			$GLOBALS['sbCache']->save('pl_site_users', '');
			return;
		}

		if (isset($_POST['su_login_remind']) || isset($_POST['su_email_remind']))
		{
			$message = str_replace(array('{DOMAIN}', '{SU_LOGIN}', '{SU_EMAIL}'), array(SB_COOKIE_DOMAIN, '', ''), $sut_messages['error']);
		}
		elseif ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
		{
			return;
		}
	}
	else
	{
		$res = sql_param_query('SELECT su_id, su_email FROM sb_site_users WHERE 1 { AND su_login=? } {AND su_email=?}', (!is_null($login) ? $login : SB_SQL_SKIP), (!is_null($email) ? $email : SB_SQL_SKIP));
	    if ($res)
	    {
	        list($su_id, $su_email) = $res[0];

	        $error = false;
	        $new_pass = substr(md5(uniqid()), 0, 7);

	        sql_param_query('UPDATE sb_site_users SET su_pass=? WHERE su_id=?d', md5($new_pass), $su_id);

	        require_once(SB_CMS_LIB_PATH.'/sbMail.inc.php');
	        $mail = new sbMail();

	        $type = sbPlugins::getSetting('sb_letters_type');

	        $email_subj = fSite_Users_Parse($sut_messages['mail_subj'], $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang);
			$email_subj = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $email_subj);

			//чистим код от инъекций
			$email_subj = sb_clean_string($email_subj);

			ob_start();
        	eval(' ?>'.$email_subj.'<?php ');
        	$email_subj = trim(ob_get_clean());

	        $email_text = fSite_Users_Parse($sut_messages['mail_text'], $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang);
			$email_text = str_replace('{SU_NEW_PASS}', $new_pass, $email_text);
			$email_text = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $email_text);

			//чистим код от инъекций
			$email_text = sb_clean_string($email_text);

			ob_start();
        	eval(' ?>'.$email_text.'<?php ');
        	$email_text = trim(ob_get_clean());

        	if ($email_text != '' && $email_subj != '')
        	{
		        $mail->setSubject($email_subj);   // Тема письма
		        if ($type == 'html')
		        {
		            $mail->setHtml($email_text);
		        }
		        else
		        {
		            $mail->setText(strip_tags(preg_replace('=<br.*?/?>=i', '', $email_text)));
		        }
        	}

	        $mail->send(array(trim($su_email)));

	        $message = fSite_Users_Parse($sut_messages['ok'], $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang);
	        $message = str_replace('{SU_NEW_PASS}', $new_pass, $message);
			$message = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $message);

			$login = '';
			$email = '';
	    }
	    else
	    {
	        $message = str_replace(array('{DOMAIN}', '{SU_LOGIN}', '{SU_EMAIL}'), array(SB_COOKIE_DOMAIN, $login, $email), $sut_messages['error']);
	    }
	}

	// выводим форму напоминания пароля
	$result = str_replace(array('{SU_ACTION}', '{MESSAGE}', '{SU_LOGIN}', '{SU_EMAIL}'), array($GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : ''), $message, $login, $email), $sut_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	if (!isset($_POST['su_login_remind']) && !isset($_POST['su_email_remind']))
	{
		$GLOBALS['sbCache']->save('pl_site_users', $result);
	}
	else
	{
		//чистим код от инъекций
        $result = sb_clean_string($result);

		eval(' ?>'.$result.'<?php ');
	}
}

/**
 * Вывод информации о пользователе
 *
 * @param string $temp Макет дизайна.
 * @param array $field_temps Макеты дизайна полей пользователя.
 * @param array $categs_temps Макеты дизайна полей группы пользователей.
 * @param int $id Идентификатор пользователя.
 * @param string $lang Язык макета дизайна.
 * @param string $prefix Префикс имени поля в макете дизайна полей.
 * @param string $sufix Суффикс имени поля в макете дизайна полей.
 * @param int $sut_votes_id Идентификатор макета дизайна рейтингов.
 * @param int $sut_comments_id Идентификатор макета дизайна комментариев.
 * @param int $sut_checked Идентификатор полей, которые должны быть помечены у пользователя (иначе 404-ая ошибка).
 * @param array $params Настройки компонента.
 *
 * @return string Отпарсенный макет дизайна вывода информации о пользователе.
 */
function fSite_Users_Parse($temp, &$fields_temps, &$categs_temps, $id, $lang = 'ru', $prefix = '', $sufix = '', $sut_votes_id = 0, $sut_comments_id = 0, $sut_checked = array(), $params = array())
{
	if (trim($temp) == '')
		return '';

	// вытаскиваем пользовательские поля
	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');

	$users_fields = array();
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
		            $users_fields_select_sql .= ', su.user_f_'.intval($value['id']);

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
    $users_fields_where_sql = '';
    if (count($sut_checked) > 0)
    {
        foreach ($sut_checked as $value)
        {
            $users_fields_where_sql .= ' AND su.user_f_'.$value.'=1';
        }
    }

    $res = sql_param_query('SELECT su.su_last_ip, su.su_activation_code, su.su_name, su.su_email, su.su_login, su.su_reg_date,
                            su.su_last_date, su.su_status, su.su_active_date, su.su_pers_foto, su.su_pers_phone, su.su_pers_mob_phone,
                            su.su_pers_birth, su.su_pers_sex, su.su_pers_zip, su.su_pers_adress, su.su_pers_addition, su.su_work_name,
                            su.su_work_phone, su.su_work_phone_inner, su.su_work_fax, su.su_work_email, su.su_work_addition,
                            su.su_work_office_number, su.su_work_unit, su.su_work_position, su.su_forum_nick, su.su_forum_text,
                            su.su_mail_lang, su.su_mail_status, su.su_mail_date, su.su_mail_subscription, c.cat_id, c.cat_title, c.cat_fields, c.cat_closed,
                            v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS m_rating
                            '.$users_fields_select_sql.'
                            FROM sb_site_users su LEFT JOIN sb_vote_results v ON (v.vr_el_id=?d
							AND v.vr_plugin="pl_site_users"), sb_catlinks l, sb_categs c
                            WHERE su.su_id=?d AND l.link_el_id=su.su_id AND l.link_src_cat_id=0 AND c.cat_id=l.link_cat_id
                            '.$users_fields_where_sql.'
                            AND c.cat_ident="pl_site_users"', $id, $id);

	if (!$res)
	{
		return false;
	}

	list ($su_last_ip, $su_activation_code, $su_name, $su_email, $su_login, $su_reg_date, $su_last_date,
	      $su_status, $su_active_date, $su_pers_foto, $su_pers_phone, $su_pers_mob_phone, $su_pers_birth,
	      $su_pers_sex, $su_pers_zip, $su_pers_adress, $su_pers_addition, $su_work_name,
	      $su_work_phone, $su_work_phone_inner, $su_work_fax, $su_work_email, $su_work_addition,
	      $su_work_office_number, $su_work_unit, $su_work_position, $su_forum_nick, $su_forum_text,
	      $su_mail_lang, $su_mail_status, $su_mail_date, $su_mail_subscription, $cat_id, $cat_title,
	      $cat_fields, $cat_closed, $vr_count, $vi_count, $rating) = $res[0];

	if((isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1') || !isset($params['allow_bbcode']))
	{
		//Если разрешен bb код
    	$su_pers_addition = sbProgParseBBCodes($su_pers_addition);
    	$su_work_addition = sbProgParseBBCodes($su_work_addition);
    	$su_forum_text = sbProgParseBBCodes($su_forum_text);
	}

	$su_mail_subscription = !is_null($su_mail_subscription) && $su_mail_subscription != '' ? explode(',', $su_mail_subscription) : array();

	$cat_values = array();
	if ($cat_fields != '')
	{
		$cat_fields = unserialize($cat_fields);
		if ($cat_fields)
		{
			$res_cat = sql_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
			if ($res_cat && $res_cat[0][0] != '')
			{
				$pd_categs = unserialize($res_cat[0][0]);
				foreach ($pd_categs as $value)
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

		                	if (isset($cat_fields['user_f_'.intval($value['id'])]))
		                	{
		               			$coords = explode('|', $cat_fields['user_f_'.intval($value['id'])]);
		               			if (isset($coords[0]) && $coords[0] != '')
		               				$cat_values[] = $coords[0];
		               			else
		               				$cat_values[] = null;

		               			if (isset($coords[1]) && $coords[1] != '')
		               				$cat_values[] = $coords[1];
		               			else
		               				$cat_values[] = null;
		                	}
		                	else
		                	{
		                		$cat_values[] = null;
		                		$cat_values[] = null;
		                	}
		                }
		                else
		                {
		                	$tags[] = '{'.$value['tag'].'}';
		                	if(isset($cat_fields['user_f_'.intval($value['id'])]) && trim($cat_fields['user_f_'.intval($value['id'])]) != '')
		                	{
			                	$cat_values[] = $cat_fields['user_f_'.intval($value['id'])];
		                	}
		                }
			        }
			    }

			    @require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
				$allow_bb = 0;
				if ((isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1) || !isset($params['allow_bbcode']))
					$allow_bb = 1;
			    $cat_values = sbLayout::parsePluginFields($pd_categs, $cat_values, $categs_temps, array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), $lang, $prefix, $sufix, $allow_bb);
			}
		}
	}

	$tags = array_merge(array('{DOMAIN}',
				'{SU_CAT_ID}',
	            '{SU_CAT_TITLE}',
				'{SU_ID}',
				'{SU_LAST_IP}',
				'{SU_ACTIVATION_PAGE}',
				'{SU_ACTIVATION_CODE}',
				'{SU_NAME}',
				'{SU_EMAIL}',
				'{SU_LOGIN}',
                '{SU_REG_DATE}',
				'{CHANGE_DATE}',
				'{SU_LAST_DATE}',
				'{SU_STATUS}',
				'{SU_ACTIVE_DATE}',
                '{SU_PERS_FOTO}',
                '{SU_PERS_PHONE}',
                '{SU_PERS_MOB_PHONE}',
                '{SU_PERS_BIRTH}',
                '{SU_PERS_SEX}',
			    '{SU_PERS_ZIP}',
				'{SU_PERS_ADRESS}',
				'{SU_PERS_ADDITION}',
				'{SU_WORK_NAME}',
				'{SU_WORK_PHONE}',
				'{SU_WORK_PHONE_INNER}',
				'{SU_WORK_FAX}',
				'{SU_WORK_EMAIL}',
				'{SU_WORK_ADDITION}',
				'{SU_WORK_OFFICE_NUMBER}',
				'{SU_WORK_UNIT}',
				'{SU_WORK_POSITION}',
				'{SU_FORUM_NICK}',
				'{SU_FORUM_TEXT}',
				'{SU_FORUM_COUNT_MSG}',
				'{SU_FORUM_MESSAGES_LINK}',
	            '{RATING}',
	            '{VOTES_COUNT}',
	            '{VOTES_SUM}',
	            '{VOTES_FORM}',
	            '{COUNT_COMMENTS}',
                '{FORM_COMMENTS}',
                '{LIST_COMMENTS}',
				'{SU_MAILLISTS}',
				'{SU_MAILLISTS_LANG}',
				'{SU_MAILLISTS_STATUS}',
				'{SU_MAILLISTS_DATE}'), $tags);


	$values = array();
	$values[] = str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, sbPlugins::getSetting('sb_site_name'));
	$values[] = $cat_id;
	$values[] = $cat_title;
	$values[] = $id;
	$values[] = $su_last_ip;
	$values[] = 'http://'.SB_COOKIE_DOMAIN.$GLOBALS['PHP_SELF'];
	$values[] = $su_activation_code;
	$values[] = ($su_name != '' && !is_null($su_name) && isset($fields_temps[$prefix.'su_name'.$sufix]) && trim($fields_temps[$prefix.'su_name'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_name, $id, $su_login, $su_email), $fields_temps[$prefix.'su_name'.$sufix]) : '');
	$values[] = ($su_email != '' && !is_null($su_email) && isset($fields_temps[$prefix.'su_email'.$sufix]) && trim($fields_temps[$prefix.'su_email'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}'), array($su_email, $id, $su_login), $fields_temps[$prefix.'su_email'.$sufix]) : '');
	$values[] = ($su_login != '' && !is_null($su_login) && isset($fields_temps[$prefix.'su_login'.$sufix]) && trim($fields_temps[$prefix.'su_login'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_EMAIL}'), array($su_login, $id, $su_email), $fields_temps[$prefix.'su_login'.$sufix]) : '');
	$values[] = ($su_reg_date != '' && !is_null($su_reg_date) && $su_reg_date != 0 && isset($fields_temps[$prefix.'su_reg_date'.$sufix]) && trim($fields_temps[$prefix.'su_reg_date'.$sufix]) != '' ? str_replace(array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), sb_parse_date($su_reg_date, $fields_temps[$prefix.'su_reg_date'.$sufix], $lang)) : '');
	// Дата последнего изменения
    if(sb_strpos($temp, '{CHANGE_DATE}') !== false)
    {
        $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $id,'pl_site_users');
        $values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $fields_temps['su_change_date'], $lang) : ''; //   CHANGE_DATE
    }
    else
    {
        $values[] = '';
    }

	$values[] = ($su_last_date != '' && !is_null($su_last_date) && $su_last_date != 0 && isset($fields_temps[$prefix.'su_last_date'.$sufix]) && trim($fields_temps[$prefix.'su_last_date'.$sufix]) != '' ? str_replace(array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), sb_parse_date($su_last_date, $fields_temps[$prefix.'su_last_date'.$sufix], $lang)) : '');
	$values[] = (isset($fields_temps[$prefix.'su_status'.$sufix]) && trim($fields_temps[$prefix.'su_status'.$sufix]) != '' ? str_replace(array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), $GLOBALS['sb_user_status'][$lang][$su_status]) : '');
	$values[] = ($su_active_date != '' && !is_null($su_active_date) && $su_active_date != 0 && isset($fields_temps[$prefix.'su_active_date'.$sufix]) && trim($fields_temps[$prefix.'su_active_date'.$sufix]) != '' ? str_replace(array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), sb_parse_date($su_active_date, $fields_temps[$prefix.'su_active_date'.$sufix], $lang)) : '');

	if ($su_pers_foto != '' && !is_null($su_pers_foto) && isset($fields_temps[$prefix.'su_pers_foto'.$sufix]) && trim($fields_temps[$prefix.'su_pers_foto'.$sufix]) != '')
	{
		$size = '';
        $width = '';
        $height = '';

        if (sb_strpos($fields_temps[$prefix.'su_pers_foto'.$sufix], '{SIZE}') > 0 ||
            sb_strpos($fields_temps[$prefix.'su_pers_foto'.$sufix], '{WIDTH}') > 0 ||
            sb_strpos($fields_temps[$prefix.'su_pers_foto'.$sufix], '{HEIGHT}') > 0 )
        {
            $img_src = SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto;
            if ($GLOBALS['sbVfs']->exists($img_src))
            {
                $size = $GLOBALS['sbVfs']->filesize($img_src);
                $img = $GLOBALS['sbVfs']->getimagesize($img_src);
                if ($img)
                {
                    $width = $img[0];
                    $height = $img[1];
                }
            }
        }

		$values[] =  str_replace(array('{VALUE}', '{SIZE}', '{WIDTH}', '{HEIGHT}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'),
									array(SB_SITE_USER_UPLOAD_URL.'/pl_site_users/'.$su_pers_foto, $size, $width, $height, $id, $su_login, $su_email),
									$fields_temps[$prefix.'su_pers_foto'.$sufix]);
	}
	else
	{
		$values[] = '';
	}

	$values[] = ($su_pers_phone != '' && !is_null($su_pers_phone) && isset($fields_temps[$prefix.'su_pers_phone'.$sufix]) && trim($fields_temps[$prefix.'su_pers_phone'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_pers_phone, $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_phone'.$sufix]) : '');
	$values[] = ($su_pers_mob_phone != '' && !is_null($su_pers_mob_phone) && isset($fields_temps[$prefix.'su_pers_mob_phone'.$sufix]) && trim($fields_temps[$prefix.'su_pers_mob_phone'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_pers_mob_phone, $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_mob_phone'.$sufix]) : '');
	$values[] = ($su_pers_birth != '' && !is_null($su_pers_birth) && $su_pers_birth != 0 && isset($fields_temps[$prefix.'su_pers_birth'.$sufix]) && trim($fields_temps[$prefix.'su_pers_birth'.$sufix]) != '' ? str_replace(array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), sb_parse_date($su_pers_birth, $fields_temps[$prefix.'su_pers_birth'.$sufix], $lang)) : '');

	// Пол
	if (isset($fields_temps[$prefix.'su_pers_sex'.$sufix]) && trim($fields_temps[$prefix.'su_pers_sex'.$sufix]) != '')
	{
		if (isset($GLOBALS['sb_sex_arr'][$lang]) && isset($GLOBALS['sb_sex_arr'][$lang][$su_pers_sex]))
		{
			$values[] = str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($GLOBALS['sb_sex_arr'][$lang][$su_pers_sex], $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_sex'.$sufix]);
		}
		elseif (isset($GLOBALS['sb_sex_arr']['ru']) && isset($GLOBALS['sb_sex_arr']['ru'][$su_pers_sex]))
		{
			$values[] = str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($GLOBALS['sb_sex_arr']['ru'][$su_pers_sex], $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_sex'.$sufix]);
		}
		else
		{
			$values[] = '';
		}
	}
	else
	{
		$values[] = '';
	}

	$values[] = ($su_pers_zip != '' && !is_null($su_pers_zip) && isset($fields_temps[$prefix.'su_pers_zip'.$sufix]) && trim($fields_temps[$prefix.'su_pers_zip'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_pers_zip, $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_zip'.$sufix]) : '');
	$values[] = ($su_pers_adress != '' && !is_null($su_pers_adress) && isset($fields_temps[$prefix.'su_pers_adress'.$sufix]) && trim($fields_temps[$prefix.'su_pers_adress'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_pers_adress, $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_adress'.$sufix]) : '');
	$values[] = ($su_pers_addition != '' && !is_null($su_pers_addition) && isset($fields_temps[$prefix.'su_pers_addition'.$sufix]) && trim($fields_temps[$prefix.'su_pers_addition'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_pers_addition, $id, $su_login, $su_email), $fields_temps[$prefix.'su_pers_addition'.$sufix]) : '');
	$values[] = ($su_work_name != '' && !is_null($su_work_name) && isset($fields_temps[$prefix.'su_work_name'.$sufix]) && trim($fields_temps[$prefix.'su_work_name'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_name, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_name'.$sufix]) : '');
	$values[] = ($su_work_phone != '' && !is_null($su_work_phone) && isset($fields_temps[$prefix.'su_work_phone'.$sufix]) && trim($fields_temps[$prefix.'su_work_phone'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_phone, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_phone'.$sufix]) : '');
	$values[] = ($su_work_phone_inner != '' && !is_null($su_work_phone_inner) && isset($fields_temps[$prefix.'su_work_phone_inner'.$sufix]) && trim($fields_temps[$prefix.'su_work_phone_inner'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_phone_inner, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_phone_inner'.$sufix]) : '');
	$values[] = ($su_work_fax != '' && !is_null($su_work_fax) && isset($fields_temps[$prefix.'su_work_fax'.$sufix]) && trim($fields_temps[$prefix.'su_work_fax'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_fax, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_fax'.$sufix]) : '');
	$values[] = ($su_work_email != '' && !is_null($su_work_email) && isset($fields_temps[$prefix.'su_work_email'.$sufix]) && trim($fields_temps[$prefix.'su_work_email'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_email, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_email'.$sufix]) : '');
	$values[] = ($su_work_addition != '' && !is_null($su_work_addition) && isset($fields_temps[$prefix.'su_work_addition'.$sufix]) && trim($fields_temps[$prefix.'su_work_addition'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_addition, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_addition'.$sufix]) : '');
	$values[] = ($su_work_office_number != '' && !is_null($su_work_office_number) && isset($fields_temps[$prefix.'su_work_office_number'.$sufix]) && trim($fields_temps[$prefix.'su_work_office_number'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_office_number, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_office_number'.$sufix]) : '');
	$values[] = ($su_work_unit != '' && !is_null($su_work_unit) && isset($fields_temps[$prefix.'su_work_unit'.$sufix]) && trim($fields_temps[$prefix.'su_work_unit'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_unit, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_unit'.$sufix]) : '');
	$values[] = ($su_work_position != '' && !is_null($su_work_position) && isset($fields_temps[$prefix.'su_work_position'.$sufix]) && trim($fields_temps[$prefix.'su_work_position'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_work_position, $id, $su_login, $su_email), $fields_temps[$prefix.'su_work_position'.$sufix]) : '');
	$values[] = ($su_forum_nick != '' && !is_null($su_forum_nick) && isset($fields_temps[$prefix.'su_forum_nick'.$sufix]) && trim($fields_temps[$prefix.'su_forum_nick'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_forum_nick, $id, $su_login, $su_email), $fields_temps[$prefix.'su_forum_nick'.$sufix]) : '');

	if ($su_forum_text != '' && !is_null($su_forum_text) && isset($fields_temps[$prefix.'su_forum_text'.$sufix]) && trim($fields_temps[$prefix.'su_forum_text'.$sufix]) != '')
	{
		$su_forum_text = sbProgParseBBCodes($su_forum_text);
		$values[] = str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_forum_text, $id, $su_login, $su_email), $fields_temps[$prefix.'su_forum_text'.$sufix]);
	}
	else
	{
		$values[] = '';
	}

	$count_msg = array();
	if(sb_strpos($temp, '{SU_FORUM_COUNT_MSG}'))
   	{
//		Кол-во сообщений автора в форуме
		$count_msg = sql_param_query('SELECT COUNT(*) FROM sb_forum WHERE f_user_id = ?d AND f_show=1', $id);
	}
	$values[] = (isset($count_msg[0][0]) && $count_msg[0][0] != 0 && isset($fields_temps['su_forum_count_msg']) && trim($fields_temps['su_forum_count_msg']) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($count_msg[0][0], $id, $su_login, $su_email), $fields_temps['su_forum_count_msg']) : '0');

	if(isset($fields_temps['author_msg_page']) && $fields_temps['author_msg_page'] != '')
	{
		$forum_link = $fields_temps['author_msg_page'].'?su_id='.$id;
		$values[] = (isset($fields_temps['su_forum_messages_link']) && trim($fields_temps['su_forum_messages_link']) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($forum_link, $id, $su_login, $su_email), $fields_temps['su_forum_messages_link']) : '');
	}
	else
	{
		$values[] = 'javascript: void(0);';
	}

	$view_rating_form = (sb_strpos($temp, '{VOTES_FORM}') !== false && $sut_votes_id > 0);
    $view_comments_list = (sb_strpos($temp, '{LIST_COMMENTS}') !== false && $sut_comments_id > 0);
    $view_comments_form = (sb_strpos($temp, '{FORM_COMMENTS}') !== false && $sut_comments_id > 0);

	$comments_count = array();
    if(sb_strpos($temp, '{COUNT_COMMENTS}') !== false)
    {
	    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
        $comments_count = fComments_Get_Count(array($id), 'pl_site_users');
    }

	$votes_sum = ($vr_count != '' && !is_null($vr_count) ? $vr_count : 0); // VOTES_SUM
    $votes_count = ($vi_count != '' && !is_null($vi_count) ? $vi_count : 0); // VOTES_COUNT
    $votes_rating = ($rating != '' && !is_null($rating) ? sprintf('%.2f', $rating) : 0); // RATING

    // VOTES_FORM
   	require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
    $res_vote = fVoting_Form_Submit($sut_votes_id, 'pl_site_users', $id, $votes_sum, $votes_count, $votes_rating);

    $values[] = $votes_rating; // RATING
    $values[] = $votes_count;  // VOTES_COUNT
	$values[] = $votes_sum;    // VOTES_SUM

	if($view_rating_form)
    {
		$values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($sut_votes_id, 'pl_site_users', $id)); // VOTES_FORM
    }
	else
    {
		$values[] = ''; // VOTES_FORM
    }

    $c_count = (isset($comments_count[$id]) ? $comments_count[$id] : 0); // COUNT_COMMENTS

	require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');

	$mod_emails = array();

	$mod_params_emails = isset($params['moderate_email']) ? explode(' ', trim(str_replace(',', ' ', $params['moderate_email']))) : array();
	$mod_categs_emails = array();
	$mod_users_emails = array();

	if(isset($cat_fields['categs_moderate_email']) && $cat_fields['categs_moderate_email'] != '')
	{
		$mod_categs_emails = array_merge($mod_categs_emails, explode(' ', trim(str_replace(',', ' ', $cat_fields['categs_moderate_email']))));
	}

	$moderates_list = array();
	if(isset($cat_fields['moderates_list']) && trim($cat_fields['moderates_list']) != '')
	{
		$moderates_list = explode('^', $cat_fields['moderates_list']);
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

	if (fComments_Add_Comment($sut_comments_id, 'pl_site_users', $id, (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
    	$c_count++;

    $values[] = $c_count;

    if ($view_comments_form)
    {
    	$values[] = fComments_Get_Form($sut_comments_id, 'pl_site_users', $id); // FORM_COMMENTS
    }
    else
    {
    	$values[] = ''; // FORM_COMMENTS
    }

    if($view_comments_list)
	{
	    $values[] = fComments_Get_List($sut_comments_id, 'pl_site_users', $id, true); // LIST_COMMENTS
	}
	else
	{
	    $values[] = ''; // LIST_COMMENTS
	}

	if(isset($fields_temps[$prefix.'su_mail_subscription'.$sufix]) && trim($fields_temps[$prefix.'su_mail_subscription'.$sufix]) != '' && count($su_mail_subscription) > 0)
	{
		$m_ids = array();
		foreach($su_mail_subscription as $key => $value)
		{
			$m_ids[] = $value;
		}

		$res_m = sql_param_query('SELECT m_id, m_name FROM sb_maillist WHERE m_id IN (?a)', $m_ids);
		$mailist_str = '';
		if($res_m)
		{
			foreach($res_m as $key => $value)
			{
				$mailist_str .= str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($value[1], $id, $su_login, $su_email), $fields_temps[$prefix.'su_mail_subscription'.$sufix]);
			}
		}
		$values[] = $mailist_str;
	}
	else
	{
		$values[] = '';
	}

	if($su_mail_lang != '' && !is_null($su_mail_lang) && isset($fields_temps[$prefix.'su_mail_lang'.$sufix]) && trim($fields_temps[$prefix.'su_mail_lang'.$sufix]) != '' )
	{
		$values[] = str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($su_mail_lang, $id, $su_login, $su_email), $fields_temps[$prefix.'su_mail_lang'.$sufix]);
	}
	else
	{
		$values[] = '';
	}

	if(!is_null($su_mail_status) && $su_mail_status != -1 && isset($fields_temps[$prefix.'su_mail_status'.$sufix]) && trim($fields_temps[$prefix.'su_mail_status'.$sufix]) != '' )
	{
		$mail_status = '';
		switch($su_mail_status)
		{
			case 0:
				$mail_status = KERNEL_PROG_PL_SITE_USERS_MAILLIST_STATUS_ACTIVE;
				break;
			case 1:
				$mail_status = KERNEL_PROG_PL_SITE_USERS_MAILLIST_STATUS_NOT_ACTIVE;
				break;
			case 2:
				$mail_status = KERNEL_PROG_PL_SITE_USERS_MAILLIST_STATUS_NOT_ACTIVE_FOR;
				break;
		}
		$values[] = str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($mail_status, $id, $su_login, $su_email), $fields_temps[$prefix.'su_mail_status'.$sufix]);
	}
	else
	{
		$values[] = '';
	}

	$values[] = ($su_mail_date != '' && !is_null($su_mail_date) && isset($fields_temps[$prefix.'su_mail_date'.$sufix]) && trim($fields_temps[$prefix.'su_mail_date'.$sufix]) != '' ? str_replace(array('{VALUE}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array(sb_parse_date($su_mail_date, $fields_temps['su_date_format'], $lang), $id, $su_login, $su_email), $fields_temps[$prefix.'su_mail_date'.$sufix]) : '');

	$users_values = array();
	$num_fields = count($res[0]);

	if ($num_fields > 39)
	{
		for ($i = 39; $i < $num_fields; $i++)
		{
			$users_values[] = $res[0][$i];
		}

		@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
		$allow_bb = 0;
		if ((isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1) || !isset($params['allow_bbcode']))
			$allow_bb = 1;
		$users_values = sbLayout::parsePluginFields($users_fields, $users_values, $fields_temps, array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}'), array($id, $su_login, $su_email), $lang, $prefix, $sufix, $allow_bb);
	}

	$values = array_merge($values, $users_values, $cat_values);

	return str_replace($tags, $values, $temp);
}

// Вывод данных
function fSite_Users_Elem_Data($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
        return;

	$res = sql_param_query('SELECT sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages, sut_checked, sut_votes_id, sut_comments_id FROM sb_site_users_temps WHERE sut_id=?d', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_SITE_USERS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_site_users', '');
        return;
	}

	list($sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages, $sut_checked, $sut_votes_id, $sut_comments_id) = $res[0];

	$sut_fields_temps = ($sut_fields_temps != '' ? unserialize($sut_fields_temps) : array());
	$sut_categs_temps = ($sut_categs_temps != '' ? unserialize($sut_categs_temps) : array());
	$sut_messages = unserialize($sut_messages);
	$sut_checked = ($sut_checked != '' ? explode(' ', $sut_checked) : array());

	$params = unserialize(stripslashes($params));

	$su_id = -1;
	$su_sid = '';
	if (isset($params['need_auth']) && intval($params['need_auth']) == 1)
	{
		if (!isset($_SESSION['sbAuth']))
		{
			$GLOBALS['sbCache']->save('pl_site_users', isset($sut_messages['need_auth']) ? $sut_messages['need_auth'] : '');
        	return;
		}

		$su_id = $_SESSION['sbAuth']->getUserId();
	}
	else
	{
        if(isset($el_id) && intval($el_id) > 0)
        {
            $su_id = $el_id;
        }
        else
        {
            if (!isset($_GET['su_cid']) && !isset($_GET['su_scid']))
            {
                $GLOBALS['sbCache']->save('pl_site_users', isset($sut_messages['need_auth']) ? $sut_messages['need_auth'] : '');
                return;
            }

            if (isset($_GET['su_cid']))
            {
                $su_id = intval($_GET['su_cid']);
            }
            elseif(is_numeric(urldecode($_GET['su_sid'])))
            {
                $su_id = intval(urldecode($_GET['su_sid']));
            }
            else
            {
                $res = sql_param_query('SELECT su_id FROM sb_site_users WHERE su_login = ?', urldecode($_GET['su_sid']));
                if ($res)
                {
                    list($su_id) = $res[0];
                }
            }
        }
	}

	if ($su_id == -1 && $su_sid == '')
	{
		$GLOBALS['sbCache']->save('pl_site_users', '');
        return;
	}

	$result = fSite_Users_Parse($sut_form, $sut_fields_temps, $sut_categs_temps, $su_id, $sut_lang, '', '', $sut_votes_id, $sut_comments_id, $sut_checked, $params);

	if ($result === false)
	{
		sb_404();
	}

	if ($result != '')
	{
		$result = str_replace('{SU_LOGOUT_LINK}', $GLOBALS['PHP_SELF'].($GLOBALS['QUERY_STRING'] != '' ? '?'.$GLOBALS['QUERY_STRING'].'&su_action=logout' : '?su_action=logout'), $result);
		$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
	}

    if(intval($el_id) > 0) //для залинкованных данных возвращаем результат
    {
        return $result;
    }
    else
    {
       $GLOBALS['sbCache']->save('pl_site_users', $result);
    }
}

// Вывод списка пользователей
function fSite_Users_Elem_List($el_id, $temp_id, $params, $tag_id, $linked = 0, $link_level = 0)
{
	if ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
        return;

    sql_query('UPDATE sb_site_users SET su_status="4" WHERE su_active_date != 0 AND su_active_date < "'.time().'"');

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

	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['su_scid']) || isset($_GET['su_cid'])))
    {
        // используется связь с выводом разделов и выводить следует пользователей из соотв. раздела
    	if (isset($_GET['su_cid']))
        {
        	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['su_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids[] = intval($_GET['su_cid']);
        }
        else
        {
            $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident=?', $_GET['su_scid'], 'pl_site_users');
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
            	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['su_scid']);
        		if ($res[0][0] > 0)
                	$cat_ids[] = intval($_GET['su_scid']);
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

    // если следует выводить из подгрупп, то вытаскиваем их ID
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
		$res = sql_param_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_site_users"
							AND c2.cat_ident = "pl_site_users"
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
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        $GLOBALS['sbCache']->save('pl_site_users', '');
        return;
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT sutl_lang, sutl_checked, sutl_count, sutl_top, sutl_categ_top, sutl_element, sutl_empty, sutl_delim,
    //            sutl_categ_bottom, sutl_bottom, sutl_pagelist_id, sutl_perpage, sutl_messages, sutl_fields_temps, sutl_categs_temps,
    //            sutl_votes_id, sutl_comments_id
    //            FROM sb_site_users_temps_list WHERE sutl_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_site_users_temps_list', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_SITE_USERS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_site_users', '');
        return;
    }

    list($sutl_lang, $sutl_checked, $sutl_count, $sutl_top, $sutl_categ_top, $sutl_element, $sutl_empty, $sutl_delim,
         $sutl_categ_bottom, $sutl_bottom, $sutl_pagelist_id, $sutl_perpage, $sutl_messages, $sutl_fields_temps,
         $sutl_categs_temps, $sutl_votes_id, $sutl_comments_id) = $res[0];

    $sutl_fields_temps = unserialize($sutl_fields_temps);
    $sutl_categs_temps = unserialize($sutl_categs_temps);
    $sutl_messages = unserialize($sutl_messages);

    $sutl_no_users = '';
    if (isset($sutl_messages['no_users']))
    {
    	$sutl_no_users = $sutl_messages['no_users'];
    }

    // вытаскиваем макет дизайна постраничного вывода
    $res = sbQueryCache::getTemplate('sb_pager_temps', $sutl_pagelist_id);

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
    //$res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    $res = sbQueryCache::query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', 'pl_site_users');

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
	                $elems_fields_select_sql .= ', su.user_f_'.$value['id'];

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
    if ($sutl_checked != '')
    {
        $sutl_checked = explode(' ', $sutl_checked);
        foreach ($sutl_checked as $value)
        {
            $elems_fields_where_sql .= ' AND su.user_f_'.$value.'=1';
        }
    }

    $now = time();

    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND su.su_reg_date >= '.$last;
    }

    if (isset($params['status']) && $params['status'] == 1)
    {
    	$elems_fields_where_sql .= ' AND su.su_status = 0';
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

    	$elems_fields_where_sql .= ' AND su.'.$params['calendar_field'].' >= "'.mktime(0, 0, 0, $month_from, $day_from, $year).'" AND su.'.$params['calendar_field'].' <= "'.mktime(23, 59, 59, $month_to, $day_to, $year).'"';
    }

    // Отключаем вывод пользователя, информацию о котором просматриваем
    if (isset($params['show_selected']) && $params['show_selected'] == 1)
	{
		$su_selected_id = -1;
		if (isset($_GET['su_cid']))
		{
			$su_id = intval($_GET['su_cid']);
		}
		elseif (isset($_GET['su_scid']))
		{
			$res = sql_param_query('SELECT su_id FROM sb_site_users WHERE su_login = ?', urldecode($_GET['su_scid']));
			if ($res)
			{
				list($su_id) = $res[0];
			}
		}
		elseif (isset($_SESSION['sbAuth']))
		{
			$su_id = $_SESSION['sbAuth']->getUserId();
		}

        $su_selected_id = isset($su_id) ? $su_id : $su_selected_id;

		if ($su_selected_id > 0)
		{
			$elems_fields_where_sql .= ' AND su.su_id != "'.intval($su_selected_id).'"';
		}
	}

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $elems_fields_filter_sql = '';
	if (isset($params['use_filter']) && $params['use_filter'] == 1)
    {
    	$date_temp = '';
		if(isset($_REQUEST['su_f_temp_id']))
    	{
			$date = sql_param_query('SELECT sut_fields_temps FROM sb_site_users_temps WHERE sut_id = ?d', $_REQUEST['su_f_temp_id']);
			if($date)
			{
				list($sut_fields_temps) = $date[0];
				$sut_fields_temps = unserialize($sut_fields_temps);
				$date_temp = $sut_fields_temps['date_temps'];
			}
		}
		$morph_db = false;
		if ($params['filter_morph'] == 1)
		{
			require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
		}

		$elems_fields_filter_sql = '(';
		$morph_db_false = false;

		$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_id', 'su_f_id', $params['filter_logic']);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_login', 'su_f_login', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_name', 'su_f_name', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_email', 'su_f_email', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_reg_date', 'su_f_reg_date', $params['filter_logic'], true, $date_temp);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_last_date', 'su_f_last_date', $params['filter_logic'], true, $date_temp);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_active_date', 'su_f_active_date', $params['filter_logic'], true, $date_temp);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_phone', 'su_f_pers_phone', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_mob_phone', 'su_f_pers_mob_phone', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_pers_birth', 'su_f_pers_birth', $params['filter_logic'], true, $date_temp);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_zip', 'su_f_pers_zip', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_adress', 'su_f_pers_adress', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_addition', 'su_f_pers_addition', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_name', 'su_f_work_name', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_phone', 'su_f_work_phone', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_phone_inner', 'su_f_work_phone_inner', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_fax', 'su_f_work_fax', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_email', 'su_f_work_email', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_addition', 'su_f_work_addition', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

		if(isset($_REQUEST['su_f_status']))
		{
			$filter_sql = '';
			if (is_array($_REQUEST['su_f_status']))
	        {
				foreach ($_REQUEST['su_f_status'] as $number)
				{
					if(trim($number) == '')
						continue;

					$filter_sql .= ' su.su_status='.intval($number).' OR ';
				}

				if($filter_sql != '')
					$filter_sql = '('.sb_substr($filter_sql, 0, -4).') '.$params['filter_logic'].' ';
			}
			elseif (trim($_REQUEST['su_f_status']) != '')
			{
				$filter_sql .= '(su.su_status='.intval($_REQUEST['su_f_status']).') '.$params['filter_logic'].' ';
			}

			$elems_fields_filter_sql .= $filter_sql;
		}

		if(isset($_REQUEST['su_f_sex']))
		{
			$filter_sql = '';
			if (is_array($_REQUEST['su_f_sex']))
	        {
				foreach ($_REQUEST['su_f_sex'] as $number)
				{
					if(trim($number) == '')
						continue;

					$filter_sql .= ' su.su_pers_sex='.intval($number).' OR ';
				}

				if($filter_sql != '')
					$filter_sql = '('.sb_substr($filter_sql, 0, -4).') '.$params['filter_logic'].' ';

			}
			elseif (trim($_REQUEST['su_f_sex']) != '')
			{
				$filter_sql .= '(su.su_pers_sex='.intval($_REQUEST['su_f_sex']).') '.$params['filter_logic'].' ';
			}
			$elems_fields_filter_sql .= $filter_sql;
		}

		$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_office_number', 'su_f_work_office_number', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_unit', 'su_f_work_unit', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_position', 'su_f_work_position', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_forum_nick', 'su_f_forum_nick', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_forum_text', 'su_f_forum_text', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

		$elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'su', 'su_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db, $date_temp);
	}

	if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
		$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
	else
		$elems_fields_filter_sql = '';

	// формируем SQL-запрос для сортировки
	$elems_fields_sort_sql = '';
	$votes_apply = $comments_sorting = false;

	if(isset($params['use_sort']) && $params['use_sort'] == '1' && isset($_REQUEST['s_f_su']) && trim($_REQUEST['s_f_su']) != '')
	{
		$elems_fields_sort_sql .= sbLayout::getPluginFieldsSortSql('su', 'su');
	}
	else
	{
	    if (isset($params['sort1']) && $params['sort1'] != '')
	    {
			if ($params['sort1'] == 'com_count' || $params['sort1'] == 'com_date')
			{
				$comments_sorting = true;
			}
			if ($params['sort1'] == 'su_rating' || $params['sort1'] == 'v.vr_num' || $params['sort1'] == 'v.vr_count')
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
			if ($params['sort2'] == 'su_rating' || $params['sort2'] == 'v.vr_num' || $params['sort2'] == 'v.vr_count')
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

			if ($params['sort3'] == 'su_rating' || $params['sort3'] == 'v.vr_num' || $params['sort3'] == 'v.vr_count')
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
    if ($sutl_categ_top != '' || $sutl_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }

    $num_cookie_name = 'pl_site_users_'.$temp_id.'_'.$tag_id;
    @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

    $pager = new sbDBPager($tag_id, $pt_perstage, $sutl_perpage, '', $num_cookie_name);
    if ($params['filter'] == 'from_to')
    {
        $pager->mFrom = intval($params['filter_from']);
        $pager->mTo = intval($params['filter_to']);
    }

    // выборка новостей, которые следует выводить
    $users_total = true;

	$group_str = '';
	$group_res = sql_param_query('SELECT COUNT(*) FROM sb_catlinks WHERE link_cat_id IN (?a) AND link_src_cat_id != 0', $cat_ids);
	if ($group_res && $group_res[0][0] > 0 || $comments_sorting)
	{
		$group_str = ' GROUP BY su.su_id ';
	}

	$votes_sql = '';
	$votes_fields = ' NULL, NULL, NULL, ';
	if($votes_apply ||
		sb_strpos($sutl_element, '{RATING}') !== false ||
		sb_strpos($sutl_element, '{VOTES_COUNT}') !== false ||
		sb_strpos($sutl_element, '{VOTES_SUM}') !== false ||
		sb_strpos($sutl_element, '{VOTES_FORM}') !== false)
	{
		$votes_sql = ' LEFT JOIN sb_vote_results v ON v.vr_el_id=su.su_id AND v.vr_plugin="pl_site_users" ';
		$votes_fields = ' v.vr_count, v.vr_num, (v.vr_count / v.vr_num) AS su_rating, ';
	}

	if($comments_sorting)
    {
		$com_sort_fields = 'COUNT(com.c_id) AS com_count, MAX(com.c_date) AS com_date';
		$com_sort_sql = 'LEFT JOIN sb_comments com ON com.c_el_id=su.su_id AND com.c_plugin="pl_site_users" AND com.c_show=1';
	}
	else
    {
		$com_sort_fields = 'NULL, NULL';
		$com_sort_sql = '';
	}

    if($categs_output)
    {
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', su.su_reg_date DESC');
	}
	else
	{
		$elems_fields_sort_sql = ($elems_fields_sort_sql != '' ? substr($elems_fields_sort_sql, 1) : ' su.su_reg_date DESC');
	}

	// Если фото подгружаются как связанные, выводить не раздел, а список конкретных фото
	$sql_linked = '';
	if ($linked != 0)
	{
	    $sql_linked = ' AND su.su_id IN ('.$linked.') ';
	}

	$res = $pager->init($users_total, 'SELECT l.link_cat_id, su.su_id, su.su_name, su.su_email, su.su_login, su.su_reg_date,
			su.su_last_date, su.su_status, su.su_active_date, su.su_pers_foto, su.su_pers_phone, su.su_pers_mob_phone,
            su.su_pers_birth, su.su_pers_sex, su.su_pers_zip, su.su_pers_adress, su.su_pers_addition, su.su_work_name,
            su.su_work_phone, su.su_work_phone_inner, su.su_work_fax, su.su_work_email, su.su_work_addition,
            su.su_work_office_number, su.su_work_unit, su.su_work_position, su.su_forum_nick, su.su_forum_text,
            su.su_mail_lang, su.su_mail_status, su.su_mail_date, su.su_mail_subscription,
            '.$votes_fields.
			$com_sort_fields.
			$elems_fields_select_sql.'
		FROM sb_site_users su
			'.$votes_sql.
			$com_sort_sql.'
			, sb_catlinks l '.
			(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? ', sb_categs c' : '').'

		WHERE '.(isset($params['show_hidden']) && $params['show_hidden'] == 1 || $categs_output ? 'c.cat_id IN (?a) AND c.cat_id=l.link_cat_id' : 'l.link_cat_id IN (?a)').
			(isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND c.cat_rubrik=1' : '').' AND l.link_el_id=su.su_id
            '.$elems_fields_where_sql.' '.
			$elems_fields_filter_sql.' '.
			$group_str.' '.
	        $sql_linked.' '.
			($categs_output ? ' ORDER BY c.cat_left '.$elems_fields_sort_sql : ' ORDER BY '.$elems_fields_sort_sql), $cat_ids);

	if (!$res)
	{
		$GLOBALS['sbCache']->save('pl_site_users', $sutl_no_users);
		return;
	}

    $count_su_users = $pager->mFrom + 1;
    $comments_count = array();
    if(sb_strpos($sutl_element, '{COUNT_COMMENTS}') !== false)
    {
	    if ($comments_sorting)
	    {
	    	for($i = 0; $i < count($res); $i++)
	        {
				$comments_count[$res[$i][1]] = $res[$i][35];
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
	        $comments_count = fComments_Get_Count($ids_arr, 'pl_site_users');
	    }
    }

    $categs = array();
    if (sb_substr_count($sutl_categ_top, '{SU_CAT_COUNT}') > 0 ||
        sb_substr_count($sutl_categ_bottom, '{SU_CAT_COUNT}') > 0 ||
        sb_substr_count($sutl_element, '{SU_CAT_COUNT}') > 0
       )
    {

    	$res_cat = sql_param_query('SELECT c1.cat_id, c1.cat_title, c1.cat_level, c1.cat_fields
                (

                SELECT COUNT(l.link_el_id) FROM sb_categs c LEFT JOIN sb_catlinks l ON l.link_cat_id = c.cat_id, sb_site_users su
                WHERE c.cat_id = c1.cat_id
                AND l.link_el_id=su.su_id
                '.$elems_fields_select_sql.'
                AND l.link_src_cat_id NOT IN (?a)

                ) AS cat_count
                FROM sb_categs c1 WHERE c1.cat_id IN (?a)', $cat_ids, $cat_ids);
    }
    else
    {
        $res_cat = sql_param_query('SELECT cat_id, cat_title, cat_level, cat_fields, "" AS cat_count
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
            $categs[$value[0]]['count'] = $value[4];
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

    // верх вывода списка пользователей
     $flds_tags = array( '{SORT_ID_ASC}' ,'{SORT_ID_DESC}',
    					  '{SORT_NAME_ASC}' ,'{SORT_NAME_DESC}',
    					  '{SORT_EMAIL_ASC}' ,'{SORT_EMAIL_DESC}',
    					  '{SORT_LOGIN_ASC}' ,'{SORT_LOGIN_DESC}',
    					  '{SORT_REG_DATE_ASC}' ,'{SORT_REG_DATE_DESC}',
    					  '{SORT_LAST_DATE_ASC}' ,'{SORT_LAST_DATE_DESC}',
    					  '{SORT_PERS_FOTO_ASC}' ,'{SORT_PERS_FOTO_DESC}',
    					  '{SORT_PERS_PHONE_ASC}' ,'{SORT_PERS_PHONE_DESC}',
    					  '{SORT_PERS_MOB_PHONE_ASC}' ,'{SORT_PERS_MOB_PHONE_DESC}',
    					  '{SORT_PERS_BIRTH_ASC}' ,'{SORT_PERS_BIRTH_DESC}',
    					  '{SORT_PERS_SEX_ASC}' ,'{SORT_PERS_SEX_DESC}',
    					  '{SORT_PERS_ZIP_ASC}' ,'{SORT_PERS_ZIP_DESC}',
    					  '{SORT_PERS_ADRESS_ASC}' ,'{SORT_PERS_ADRESS_DESC}',
    					  '{SORT_PERS_ADDITION_ASC}' ,'{SORT_PERS_ADDITION_DESC}',
    					  '{SORT_WORK_NAME_ASC}' ,'{SORT_WORK_NAME_DESC}',
    					  '{SORT_WORK_PHONE_ASC}' ,'{SORT_WORK_PHONE_DESC}',
    					  '{SORT_WORK_PHONE_INNER_ASC}' ,'{SORT_WORK_PHONE_INNER_DESC}',
    					  '{SORT_WORK_FAX_ASC}' ,'{SORT_WORK_FAX_DESC}',
    					  '{SORT_WORK_EMAIL_ASC}' ,'{SORT_WORK_EMAIL_DESC}',
    					  '{SORT_WORK_ADDITION_ASC}' ,'{SORT_WORK_ADDITION_DESC}',
    					  '{SORT_WORK_OFFICE_NUMBER_ASC}' ,'{SORT_WORK_OFFICE_NUMBER_DESC}',
    					  '{SORT_WORK_UNIT_ASC}' ,'{SORT_WORK_UNIT_DESC}',
    					  '{SORT_WORK_POSITION_ASC}' ,'{SORT_WORK_POSITION_DESC}',
    					  '{SORT_FORUM_NICK_ASC}' ,'{SORT_FORUM_NICK_DESC}',
    					  '{SORT_FORUM_TEXT_ASC}' ,'{SORT_FORUM_TEXT_DESC}');
    $query_str = $_SERVER['QUERY_STRING'];
    if(isset($_GET['s_f_su']))
    {
    	$query_str = preg_replace('/[?&]?s_f_su['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
    }

    $flds_href = $GLOBALS['PHP_SELF'].(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_su=';

    $flds_vals = array( $flds_href.urlencode('su_id=ASC'),
    					$flds_href.urlencode('su_id=DESC'),
    					$flds_href.urlencode('su_name=ASC'),
    					$flds_href.urlencode('su_name=DESC'),
    					$flds_href.urlencode('su_email=ASC'),
    					$flds_href.urlencode('su_email=DESC'),
    					$flds_href.urlencode('su_login=ASC'),
    					$flds_href.urlencode('su_login=DESC'),
    					$flds_href.urlencode('su_reg_date=ASC'),
    					$flds_href.urlencode('su_reg_date=DESC'),
    					$flds_href.urlencode('su_last_date=ASC'),
    					$flds_href.urlencode('su_last_date=DESC'),
    					$flds_href.urlencode('su_pers_foto=ASC'),
    					$flds_href.urlencode('su_pers_foto=DESC'),
    					$flds_href.urlencode('su_pers_phone=ASC'),
    					$flds_href.urlencode('su_pers_phone=DESC'),
    					$flds_href.urlencode('su_pers_mob_phone=ASC'),
    					$flds_href.urlencode('su_pers_mob_phone=DESC'),
    					$flds_href.urlencode('su_pers_birth=ASC'),
    					$flds_href.urlencode('su_pers_birth=DESC'),
    					$flds_href.urlencode('su_pers_sex=ASC'),
    					$flds_href.urlencode('su_pers_sex=DESC'),
    					$flds_href.urlencode('su_pers_zip=ASC'),
    					$flds_href.urlencode('su_pers_zip=DESC'),
    					$flds_href.urlencode('su_pers_adress=ASC'),
    					$flds_href.urlencode('su_pers_adress=DESC'),
    					$flds_href.urlencode('su_pers_addition=ASC'),
    					$flds_href.urlencode('su_pers_addition=DESC'),
    					$flds_href.urlencode('su_work_name=ASC'),
    					$flds_href.urlencode('su_work_name=DESC'),
    					$flds_href.urlencode('su_work_phone=ASC'),
    					$flds_href.urlencode('su_work_phone=DESC'),
    					$flds_href.urlencode('su_work_phone_inner=ASC'),
    					$flds_href.urlencode('su_work_phone_inner=DESC'),
    					$flds_href.urlencode('su_work_fax=ASC'),
    					$flds_href.urlencode('su_work_fax=DESC'),
    					$flds_href.urlencode('su_work_email=ASC'),
    					$flds_href.urlencode('su_work_email=DESC'),
    					$flds_href.urlencode('su_work_addition=ASC'),
    					$flds_href.urlencode('su_work_addition=DESC'),
    					$flds_href.urlencode('su_work_office_number=ASC'),
    					$flds_href.urlencode('su_work_office_number=DESC'),
    					$flds_href.urlencode('su_work_unit=ASC'),
    					$flds_href.urlencode('su_work_unit=DESC'),
    					$flds_href.urlencode('su_work_position=ASC'),
    					$flds_href.urlencode('su_work_position=DESC'),
    					$flds_href.urlencode('su_forum_nick=ASC'),
    					$flds_href.urlencode('su_forum_nick=DESC'),
    					$flds_href.urlencode('su_forum_text=ASC'),
    					$flds_href.urlencode('su_forum_text=DESC'));

    sbLayout::getPluginFieldsTagsSort('su', $flds_tags, $flds_vals, 'href_replace');
 	// Заменяем значение селекта "Кол-во на странице" селектед
	if(isset($_REQUEST['num_'.$tag_id]))
    {
    	$sutl_top = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $sutl_top);
    }
    elseif(isset($_COOKIE[$num_cookie_name]))
    {
    	$sutl_top = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $sutl_top);
    }
    $result = str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $users_total, 'num_'.$tag_id),$flds_vals), $sutl_top);

    $tags = array_merge($tags, array('{SU_CAT_TITLE}',
                                     '{SU_CAT_LEVEL}',
                                     '{SU_CAT_COUNT}',
                                     '{SU_CAT_ID}',
    								 '{SU_LINK}',
    								 '{SU_NUMBER}',
                                     '{SU_ID}',
 									 '{SU_NAME}',
									 '{SU_EMAIL}',
									 '{SU_LOGIN}',
					                 '{SU_REG_DATE}',
    								 '{CHANGE_DATE}',
									 '{SU_LAST_DATE}',
									 '{SU_STATUS}',
									 '{SU_ACTIVE_DATE}',
					                 '{SU_PERS_FOTO}',
					                 '{SU_PERS_PHONE}',
					                 '{SU_PERS_MOB_PHONE}',
					                 '{SU_PERS_BIRTH}',
					                 '{SU_PERS_SEX}',
								     '{SU_PERS_ZIP}',
									 '{SU_PERS_ADRESS}',
									 '{SU_PERS_ADDITION}',
									 '{SU_WORK_NAME}',
									 '{SU_WORK_PHONE}',
									 '{SU_WORK_PHONE_INNER}',
									 '{SU_WORK_FAX}',
									 '{SU_WORK_EMAIL}',
									 '{SU_WORK_ADDITION}',
									 '{SU_WORK_OFFICE_NUMBER}',
									 '{SU_WORK_UNIT}',
									 '{SU_WORK_POSITION}',
									 '{SU_FORUM_NICK}',
									 '{SU_FORUM_TEXT}',
     								 '{SU_FORUM_COUNT_MSG}',
     								 '{SU_FORUM_MESSAGES_LINK}',
    								 '{RATING}',
    								 '{VOTES_SUM}',
    								 '{VOTES_COUNT}',
    								 '{VOTES_FORM}',
                                     '{COUNT_COMMENTS}',
    								 '{FORM_COMMENTS}',
    								 '{LIST_COMMENTS}'));

    $cur_cat_id = 0;
    $values = array();
    $num_fields = count($res[0]);
    $num_cat_fields = count($categs_sql_fields);
    $col = 0;

    $dop_tags = array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_CAT_ID}', '{SU_CAT_TITLE}', '{SU_LINK}');
	$fields_tags = array_merge(array('{VALUE}'), $dop_tags);

	list($more_page, $more_ext) = sbGetMorePage(isset($params['page']) ? $params['page'] : '');

    $view_rating_form = (sb_strpos($sutl_element, '{VOTES_FORM}') !== false && $sutl_votes_id > 0);
    $view_comments_list = (sb_strpos($sutl_element, '{LIST_COMMENTS}') !== false && $sutl_comments_id > 0);
    $view_comments_form = (sb_strpos($sutl_element, '{FORM_COMMENTS}') !== false && $sutl_comments_id > 0);

    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
    require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');

    foreach ($res as $value)
    {
    	list ($cat_id, $su_id, $su_name, $su_email, $su_login, $su_reg_date, $su_last_date,
	      $su_status, $su_active_date, $su_pers_foto, $su_pers_phone, $su_pers_mob_phone, $su_pers_birth,
	      $su_pers_sex, $su_pers_zip, $su_pers_adress, $su_pers_addition, $su_work_name,
	      $su_work_phone, $su_work_phone_inner, $su_work_fax, $su_work_email, $su_work_addition,
	      $su_work_office_number, $su_work_unit, $su_work_position, $su_forum_nick, $su_forum_text,
	      $su_mail_lang, $su_mail_status, $su_mail_date, $su_mail_subscription, $vr_count, $vi_count, $rating) = $value;

	    if(isset($params['allow_bbcode']) && $params['allow_bbcode'] == '1')
		{
			//Если разрешен bb код
	    	$su_pers_addition = sbProgParseBBCodes($su_pers_addition);
	    	$su_work_addition = sbProgParseBBCodes($su_work_addition);
	    	$su_forum_text = sbProgParseBBCodes($su_forum_text);
		}

		$count_msg = array();
		if(strpos($sutl_element, '{SU_FORUM_COUNT_MSG}'))
    	{
//			Кол-во сообщений автора в форуме
			$count_msg = sql_param_query('SELECT COUNT(*) FROM sb_forum WHERE f_user_id = ?d AND f_show=1', $su_id);
    	}

        $old_values = $values;
        $values = array();

        if ($cat_id != $cur_cat_id)
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
                $href .= $cat_id . '/' . ($su_login != '' && sb_substr_count($su_login, '@') == 0 ? urlencode($su_login) : $su_id) . '/';
            }
            else
            {
                $href .= '?su_cid='.$su_id;
            }
        }

        $dop_values = array($su_id, $su_login, $su_email, $cat_id, strip_tags($categs[$cat_id]['title']), $href);

        if ($num_fields > 37)
        {
            for ($i = 37; $i < $num_fields; $i++)
            {
	            $values[] = $value[$i];
            }
			$allow_bb = 0;
			if (isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1)
				$allow_bb = 1;
            $values = sbLayout::parsePluginFields($elems_fields, $values, $sutl_fields_temps, $dop_tags, $dop_values, $sutl_lang,'', '', $allow_bb);
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
                $cat_values = sbLayout::parsePluginFields($categs_fields, $cat_values, $sutl_categs_temps, $dop_tags, $dop_values, $sutl_lang, '', '', $allow_bb);
            }

            $values = array_merge($values, $cat_values);
        }

        $values[] = $categs[$cat_id]['title'];  // SU_CAT_TITLE
        $values[] = $categs[$cat_id]['level'];  // SU_CAT_LEVEL
        $values[] = $categs[$cat_id]['count'];  // SU_CAT_COUNT
        $values[] = $cat_id;  // SU_CAT_ID
        $values[] = $href;  // SU_LINK
        $values[] = $count_su_users++;	// SU_NUMBER
        $values[] = $su_id;   // SU_ID
	    $values[] = ($su_name != '' && !is_null($su_name) && isset($sutl_fields_temps['su_name']) && trim($sutl_fields_temps['su_name']) != '' ? str_replace($fields_tags, array_merge(array($su_name), $dop_values), $sutl_fields_temps['su_name']) : '');
		$values[] = ($su_email != '' && !is_null($su_email) && isset($sutl_fields_temps['su_email']) && trim($sutl_fields_temps['su_email']) != '' ? str_replace($fields_tags, array_merge(array($su_email), $dop_values), $sutl_fields_temps['su_email']) : '');
		$values[] = ($su_login != '' && !is_null($su_login) && isset($sutl_fields_temps['su_login']) && trim($sutl_fields_temps['su_login']) != '' ? str_replace($fields_tags, array_merge(array($su_login), $dop_values), $sutl_fields_temps['su_login']) : str_replace($fields_tags, array_merge(array($su_email), $dop_values), $sutl_fields_temps['su_login']));
		$values[] = ($su_reg_date != '' && !is_null($su_reg_date) && $su_reg_date != 0 && isset($sutl_fields_temps['su_reg_date']) && trim($sutl_fields_temps['su_reg_date']) != '' ? str_replace($fields_tags, array_merge(array(''), $dop_values), sb_parse_date($su_reg_date, $sutl_fields_temps['su_reg_date'], $sutl_lang)) : '');
		// Дата последнего изменения
        if(sb_strpos($sutl_element, '{CHANGE_DATE}') !== false)
        {
        	$res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $su_id,'pl_site_users');
        	$values[] = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $sutl_fields_temps['su_change_date'], $sutl_lang) : ''; //   CHANGE_DATE
        }
        else
       	{
        	$values[] = '';
       	}

		$values[] = ($su_last_date != '' && !is_null($su_last_date) && $su_last_date != 0 && isset($sutl_fields_temps['su_last_date']) && trim($sutl_fields_temps['su_last_date']) != '' ? str_replace($fields_tags, array_merge(array(''), $dop_values), sb_parse_date($su_last_date, $sutl_fields_temps['su_last_date'], $sutl_lang)) : '');
		$values[] = (isset($sutl_fields_temps['su_status']) && trim($sutl_fields_temps['su_status']) != '' ? str_replace($fields_tags, array_merge(array($GLOBALS['sb_user_status'][$sutl_lang][$su_status]), $dop_values), $sutl_fields_temps['su_status']) : '');
		$values[] = ($su_active_date != '' && !is_null($su_active_date) && $su_active_date != 0 && isset($sutl_fields_temps['su_active_date']) && trim($sutl_fields_temps['su_active_date']) != '' ? str_replace($fields_tags, array_merge(array(''), $dop_values), sb_parse_date($su_active_date, $sutl_fields_temps['su_active_date'], $sutl_lang)) : '');

		if ($su_pers_foto != '' && !is_null($su_pers_foto) && isset($sutl_fields_temps['su_pers_foto']) && trim($sutl_fields_temps['su_pers_foto']) != '')
		{
			$size = '';
	        $width = '';
	        $height = '';

	        if (sb_strpos($sutl_fields_temps['su_pers_foto'], '{SIZE}') > 0 ||
	            sb_strpos($sutl_fields_temps['su_pers_foto'], '{WIDTH}') > 0 ||
	            sb_strpos($sutl_fields_temps['su_pers_foto'], '{HEIGHT}') > 0 )
	        {
	            $img_src = SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto;
	            if ($GLOBALS['sbVfs']->exists($img_src))
	            {
	                $size = $GLOBALS['sbVfs']->filesize($img_src);
	                $img = $GLOBALS['sbVfs']->getimagesize($img_src);
	                if ($img)
	                {
	                    $width = $img[0];
	                    $height = $img[1];
	                }
	            }
	        }

			$values[] =  str_replace(array_merge($fields_tags, array('{SIZE}', '{WIDTH}', '{HEIGHT}')),
									 array_merge(array(SB_SITE_USER_UPLOAD_URL.'/pl_site_users/'.$su_pers_foto), $dop_values, array($size, $width, $height)),
									 $sutl_fields_temps['su_pers_foto']);
		}
		else
		{
			$values[] = '';
		}

		$values[] = ($su_pers_phone != '' && !is_null($su_pers_phone) && isset($sutl_fields_temps['su_pers_phone']) && trim($sutl_fields_temps['su_pers_phone']) != '' ? str_replace($fields_tags, array_merge(array($su_pers_phone), $dop_values), $sutl_fields_temps['su_pers_phone']) : '');
		$values[] = ($su_pers_mob_phone != '' && !is_null($su_pers_mob_phone) && isset($sutl_fields_temps['su_pers_mob_phone']) && trim($sutl_fields_temps['su_pers_mob_phone']) != '' ? str_replace($fields_tags, array_merge(array($su_pers_mob_phone), $dop_values), $sutl_fields_temps['su_pers_mob_phone']) : '');
		$values[] = ($su_pers_birth != '' && !is_null($su_pers_birth) && $su_pers_birth != 0 && isset($sutl_fields_temps['su_pers_birth']) && trim($sutl_fields_temps['su_pers_birth']) != '' ? str_replace($fields_tags, array_merge(array(''), $dop_values), sb_parse_date($su_pers_birth, $sutl_fields_temps['su_pers_birth'], $sutl_lang)) : '');

		// Пол
		if (isset($sutl_fields_temps['su_pers_sex']) && trim($sutl_fields_temps['su_pers_sex']) != '')
		{
			if (isset($GLOBALS['sb_sex_arr'][$sutl_lang]) && isset($GLOBALS['sb_sex_arr'][$sutl_lang][$su_pers_sex]))
			{
				$values[] = str_replace($fields_tags, array_merge(array($GLOBALS['sb_sex_arr'][$sutl_lang][$su_pers_sex]), $dop_values), $sutl_fields_temps['su_pers_sex']);
			}
			elseif (isset($GLOBALS['sb_sex_arr']['ru']) && isset($GLOBALS['sb_sex_arr']['ru'][$su_pers_sex]))
			{
				$values[] = str_replace($fields_tags, array_merge(array($GLOBALS['sb_sex_arr']['ru'][$su_pers_sex]), $dop_values), $sutl_fields_temps['su_pers_sex']);
			}
			else
			{
				$values[] = '';
			}
		}
		else
		{
			$values[] = '';
		}

		$values[] = ($su_pers_zip != '' && !is_null($su_pers_zip) && isset($sutl_fields_temps['su_pers_zip']) && trim($sutl_fields_temps['su_pers_zip']) != '' ? str_replace($fields_tags, array_merge(array($su_pers_zip), $dop_values), $sutl_fields_temps['su_pers_zip']) : '');
		$values[] = ($su_pers_adress != '' && !is_null($su_pers_adress) && isset($sutl_fields_temps['su_pers_adress']) && trim($sutl_fields_temps['su_pers_adress']) != '' ? str_replace($fields_tags, array_merge(array($su_pers_adress), $dop_values), $sutl_fields_temps['su_pers_adress']) : '');
		$values[] = ($su_pers_addition != '' && !is_null($su_pers_addition) && isset($sutl_fields_temps['su_pers_addition']) && trim($sutl_fields_temps['su_pers_addition']) != '' ? str_replace($fields_tags, array_merge(array($su_pers_addition), $dop_values), $sutl_fields_temps['su_pers_addition']) : '');
		$values[] = ($su_work_name != '' && !is_null($su_work_name) && isset($sutl_fields_temps['su_work_name']) && trim($sutl_fields_temps['su_work_name']) != '' ? str_replace($fields_tags, array_merge(array($su_work_name), $dop_values), $sutl_fields_temps['su_work_name']) : '');
		$values[] = ($su_work_phone != '' && !is_null($su_work_phone) && isset($sutl_fields_temps['su_work_phone']) && trim($sutl_fields_temps['su_work_phone']) != '' ? str_replace($fields_tags, array_merge(array($su_work_phone), $dop_values), $sutl_fields_temps['su_work_phone']) : '');
		$values[] = ($su_work_phone_inner != '' && !is_null($su_work_phone_inner) && isset($sutl_fields_temps['su_work_phone_inner']) && trim($sutl_fields_temps['su_work_phone_inner']) != '' ? str_replace($fields_tags, array_merge(array($su_work_phone_inner), $dop_values), $sutl_fields_temps['su_work_phone_inner']) : '');
		$values[] = ($su_work_fax != '' && !is_null($su_work_fax) && isset($sutl_fields_temps['su_work_fax']) && trim($sutl_fields_temps['su_work_fax']) != '' ? str_replace($fields_tags, array_merge(array($su_work_fax), $dop_values), $sutl_fields_temps['su_work_fax']) : '');
		$values[] = ($su_work_email != '' && !is_null($su_work_email) && isset($sutl_fields_temps['su_work_email']) && trim($sutl_fields_temps['su_work_email']) != '' ? str_replace($fields_tags, array_merge(array($su_work_email), $dop_values), $sutl_fields_temps['su_work_email']) : '');
		$values[] = ($su_work_addition != '' && !is_null($su_work_addition) && isset($sutl_fields_temps['su_work_addition']) && trim($sutl_fields_temps['su_work_addition']) != '' ? str_replace($fields_tags, array_merge(array($su_work_addition), $dop_values), $sutl_fields_temps['su_work_addition']) : '');
		$values[] = ($su_work_office_number != '' && !is_null($su_work_office_number) && isset($sutl_fields_temps['su_work_office_number']) && trim($sutl_fields_temps['su_work_office_number']) != '' ? str_replace($fields_tags, array_merge(array($su_work_office_number), $dop_values), $sutl_fields_temps['su_work_office_number']) : '');
		$values[] = ($su_work_unit != '' && !is_null($su_work_unit) && isset($sutl_fields_temps['su_work_unit']) && trim($sutl_fields_temps['su_work_unit']) != '' ? str_replace($fields_tags, array_merge(array($su_work_unit), $dop_values), $sutl_fields_temps['su_work_unit']) : '');
		$values[] = ($su_work_position != '' && !is_null($su_work_position) && isset($sutl_fields_temps['su_work_position']) && trim($sutl_fields_temps['su_work_position']) != '' ? str_replace($fields_tags, array_merge(array($su_work_position), $dop_values), $sutl_fields_temps['su_work_position']) : '');
		$values[] = ($su_forum_nick != '' && !is_null($su_forum_nick) && isset($sutl_fields_temps['su_forum_nick']) && trim($sutl_fields_temps['su_forum_nick']) != '' ? str_replace($fields_tags, array_merge(array($su_forum_nick), $dop_values), $sutl_fields_temps['su_forum_nick']) : '');
		$values[] = ($su_forum_text != '' && !is_null($su_forum_text) && isset($sutl_fields_temps['su_forum_text']) && trim($sutl_fields_temps['su_forum_text']) != '' ? str_replace($fields_tags, array_merge(array($su_forum_text), $dop_values), $sutl_fields_temps['su_forum_text']) : '');
		$values[] = (isset($count_msg[0][0]) && $count_msg[0][0] != 0 && isset($sutl_fields_temps['su_forum_count_msg']) && trim($sutl_fields_temps['su_forum_count_msg']) != '' ? str_replace($fields_tags, array_merge(array($count_msg[0][0]), $dop_values), $sutl_fields_temps['su_forum_count_msg']) : '0');

		if(isset($params['author_msg_page']) && $params['author_msg_page'] != '')
		{
			$forum_link = $params['author_msg_page'].'?su_id='.$su_id;
			$values[] = (isset($sutl_fields_temps['su_forum_messages_link']) && trim($sutl_fields_temps['su_forum_messages_link']) != '' ? str_replace($fields_tags, array_merge(array($forum_link), $dop_values), $sutl_fields_temps['su_forum_messages_link']) : '');
		}
		else
		{
			$values[] = 'javascript: void(0);';
		}

		$comments_count = array();
		if(sb_strpos($sutl_element, '{COUNT_COMMENTS}') !== false)
	    {
		    require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');
	        $comments_count = fComments_Get_Count(array($su_id), 'pl_site_users');
	    }

		$votes_sum = ($vr_count != '' && !is_null($vr_count) ? $vr_count : 0); // VOTES_SUM
	    $votes_count = ($vi_count != '' && !is_null($vi_count) ? $vi_count : 0); // VOTES_COUNT
	    $votes_rating = ($rating != '' && !is_null($rating) ? sprintf('%.2f', $rating) : 0); // RATING

    	// VOTES_FORM
    	require_once(SB_CMS_PL_PATH.'/pl_voting/prog/pl_voting.php');
        $res_vote = fVoting_Form_Submit($sutl_votes_id, 'pl_site_users', $su_id, $votes_sum, $votes_count, $votes_rating);

        $values[] = $votes_rating; // RATING
	    $values[] = $votes_sum;    // VOTES_SUM
	    $values[] = $votes_count;  // VOTES_COUNT

	    if($view_rating_form)
	    {
	        $values[] = str_replace('{MESSAGE}', $res_vote, fVoting_Get_Form($sutl_votes_id, 'pl_site_users', $su_id)); // VOTES_FORM
	    }
	    else
	    {
	        $values[] = ''; // VOTES_FORM
	    }

    	$c_count = (isset($comments_count[$su_id]) ? $comments_count[$su_id] : 0); // COUNT_COMMENTS

		require_once(SB_CMS_PL_PATH.'/pl_comments/prog/pl_comments.php');

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

		if (fComments_Add_Comment($sutl_comments_id, 'pl_site_users', $su_id, (isset($params['moderate']) ? intval($params['moderate']) : false), $str_emails))
			$c_count++;

		$values[] = $c_count;

	    if ($view_comments_form)
	    {
	    	$values[] = fComments_Get_Form($sutl_comments_id, 'pl_site_users', $su_id); // FORM_COMMENTS
	    }
	    else
	    {
	    	$values[] = ''; // FORM_COMMENTS
	    }

	    if($view_comments_list)
		{
			$values[] = fComments_Get_List($sutl_comments_id, 'pl_site_users', $su_id, true); // LIST_COMMENTS
		}
		else
		{
			$values[] = ''; // LIST_COMMENTS
		}

        if ($categs_output && $cat_id != $cur_cat_id)
        {
            if ($cur_cat_id != 0)
            {
                // низ вывода раздела
                while ($col < $sutl_count)
                {
                    $result .= $sutl_empty;
                    $col++;
                }
                $result .= str_replace($tags, $old_values, $sutl_categ_bottom);
            }
            // верх вывода раздела
            $result .= str_replace($tags, $values, $sutl_categ_top);
            $col = 0;
        }

        if ($col >= $sutl_count)
        {
            $result .= $sutl_delim;
            $col = 0;
        }

        $result .= str_replace($tags, $values, $sutl_element);

        $cur_cat_id = $cat_id;
        $col++;
    }

    while ($col < $sutl_count)
    {
        $result .= $sutl_empty;
        $col++;
    }

    if ($categs_output)
    {
        // низ вывода раздела
        $result .= str_replace($tags, $values, $sutl_categ_bottom);
    }

    // низ вывода новостной ленты
		// Заменяем значение селекта "Кол-во на странице" селектед
	if(isset($_REQUEST['num_'.$tag_id]))
    {
    	$sutl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_REQUEST['num_'.$tag_id].'}', 'selected', $sutl_bottom);
    }
    elseif(isset($_COOKIE[$num_cookie_name]))
    {
    	$sutl_bottom = sb_str_replace('{ONPAGE_SEL_'.$_COOKIE[$num_cookie_name].'}', 'selected', $sutl_bottom);
    }

    $result .= str_replace(array_merge(array('{NUM_LIST}', '{ALL_COUNT}', '{ONPAGE_NAME}'),$flds_tags), array_merge(array($pt_page_list, $users_total, 'num_'.$tag_id),$flds_vals), $sutl_bottom);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    if ($linked == 0)
    {
        $GLOBALS['sbCache']->save('pl_site_users', $result);
    }
    else
    {
        return $result;
    }
}

/**
 * Возвращает HTML-код вывода данных пользователя
 *
 * @param int $temp_id Идентификатор макета дизайна вывода данных пользователя.
 * @param int $user_id Идентификатор пользователя.
 *
 * @return string HTML-код вывода данных пользователя.
 */
function fSite_Users_Get_Data($temp_id, $user_id)
{
	static $users_data = array();

	$hash = md5($temp_id.' - '.$user_id);

	if (isset($users_data[$hash]))
		return $users_data[$hash];

	$res = sql_param_query('SELECT sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_votes_id FROM sb_site_users_temps WHERE sut_id=?d', $temp_id);
	if (!$res)
	{
		$users_data[$hash] = '';
		return '';
	}

	list($sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_votes_id) = $res[0];

	$sut_fields_temps = ($sut_fields_temps != '' ? unserialize($sut_fields_temps) : array());
	$sut_categs_temps = ($sut_categs_temps != '' ? unserialize($sut_categs_temps) : array());

	$result = fSite_Users_Parse($sut_form, $sut_fields_temps, $sut_categs_temps, $user_id, $sut_lang, '', '', $sut_votes_id);
	$result = str_replace('{SU_LOGOUT_LINK}', $GLOBALS['PHP_SELF'].($GLOBALS['QUERY_STRING'] != '' ? '?'.$GLOBALS['QUERY_STRING'].'&su_action=logout' : '?su_action=logout'), $result);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	$users_data[$hash] = $result;

	return $result;
}

// Форма изменения данных
function fSite_Users_Elem_Update($el_id, $temp_id, $params, $tag_id)
{
	fSite_Users_Elem_Reg($el_id, $temp_id, $params, $tag_id, true);
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
function fSite_Users_Get_Calendar($year, $month, $params, $rubrikator, $filter)
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

    $cat_ids = explode('^', $params['ids']);

    // если следует выводить из подгрупп, то вытаскиваем их ID
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
		$res = sql_param_query('SELECT c.cat_id, c2.cat_id, c2.cat_title, c2.cat_url FROM sb_categs AS c, sb_categs AS c2
							WHERE c2.cat_left <= c.cat_left
							AND c2.cat_right >= c.cat_right
							AND c.cat_ident="pl_site_users"
							AND c2.cat_ident = "pl_site_users"
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
    }

    if (count($cat_ids) == 0)
    {
        // указанные разделы были удалены
        return $result;
    }

    // вытаскиваем макет дизайна
    $res = sql_param_query('SELECT sutl_checked, sutl_categ_top, sutl_categ_bottom
    						FROM sb_site_users_temps_list WHERE sutl_id=?d', $params['temp_id']);

    if (!$res)
    {
        $sutl_checked = array();
        $sutl_categ_top = '';
        $sutl_categ_bottom = '';
    }
    else
    {
    	list($sutl_checked, $sutl_categ_top, $sutl_categ_bottom) = $res[0];
    	if (trim($sutl_checked) != '')
    	{
        	$sutl_checked = explode(' ', $sutl_checked);
    	}
    	else
    	{
    		$sutl_checked = array();
    	}
    }

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';
    foreach ($sutl_checked as $value)
    {
        $elems_fields_where_sql .= ' AND su.user_f_'.$value.'=1';
    }

    $now = time();

    if ($params['filter'] == 'last')
    {
        $last = intval($params['filter_last']) - 1;
        $last = mktime(0, 0, 0, sb_date('n', $now), sb_date('j', $now), sb_date('Y', $now)) - $last * 24 * 60 * 60;

        $elems_fields_where_sql .= ' AND su.su_reg_date >= '.$last;
    }

    if (isset($params['status']) && $params['status'] == 1)
    {
    	$elems_fields_where_sql .= ' AND su.su_status = 0';
    }

    $elems_fields_filter_sql = '';
	if (isset($params['use_filter']) && $params['use_filter'] == 1)
    {
    	$morph_db = false;
	    if ($params['filter_morph'] == 1)
	    {
	    	require_once SB_CMS_LIB_PATH.'/sbSearch.inc.php';
			$morph_db = new sbSearch();
	    }

    	$elems_fields_filter_sql = '(';

    	$morph_db_false = false;

    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_id', 'su_f_id', $params['filter_logic']);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_login', 'su_f_login', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_name', 'su_f_name', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
    	$elems_fields_filter_sql .= sbGetFilterTextSql('su.su_email', 'su_f_email', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_reg_date', 'su_f_reg_date', $params['filter_logic'], true);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_last_date', 'su_f_last_date', $params['filter_logic'], true);
    	$elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_active_date', 'su_f_active_date', $params['filter_logic'], true);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_phone', 'su_f_pers_phone', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_mob_phone', 'su_f_pers_mob_phone', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterNumberSql('su.su_pers_birth', 'su_f_pers_birth', $params['filter_logic'], true);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_zip', 'su_f_pers_zip', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_adress', 'su_f_pers_adress', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_pers_addition', 'su_f_pers_addition', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_name', 'su_f_work_name', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_phone', 'su_f_work_phone', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_phone_inner', 'su_f_work_phone_inner', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_fax', 'su_f_work_fax', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_email', 'su_f_work_email', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_addition', 'su_f_work_addition', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
        $elems_fields_filter_sql .= sbGetFilterSql('su.su_status', 'su_f_status', $params['filter_logic']);
        $elems_fields_filter_sql .= sbGetFilterSql('su.su_pers_sex', 'su_f_pers_sex', $params['filter_logic']);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_office_number', 'su_f_work_office_number', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_unit', 'su_f_work_unit', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_work_position', 'su_f_work_position', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_forum_nick', 'su_f_forum_nick', 'EQ', $params['filter_logic'], $params['filter_text_logic'], $morph_db_false);
        $elems_fields_filter_sql .= sbGetFilterTextSql('su.su_forum_text', 'su_f_forum_text', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);

        require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

        $elems_fields_filter_sql .= sbLayout::getPluginFieldsFilterSql($elems_fields, 'su', 'su_f', $params['filter_compare'], $params['filter_logic'], $params['filter_text_logic'], $morph_db);
    }

    if ($elems_fields_filter_sql != '' && $elems_fields_filter_sql != '(')
    	$elems_fields_filter_sql = ' AND '.sb_substr($elems_fields_filter_sql, 0, -(2 + strlen($params['filter_logic']))).')';
	else
		$elems_fields_filter_sql = '';

	$from_date = mktime(0, 0, 0, $month, 1, $year);
    $to_date = mktime(23, 59, 59, $month, sb_get_last_day($month, $year), $year);

    if ($from_date <= 0 || $to_date <= 0)
    {
    	return $result;
    }

    $elems_fields_where_sql .= ' AND su.'.$field.' >= "'.$from_date.'" AND su.'.$field.' <= "'.$to_date.'"';

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
    if ($sutl_categ_top != '' || $sutl_categ_bottom != '')
    {
        $categs_output = true;
    }
    else
    {
        $categs_output = false;
    }

    if ($categs_output)
    {
        $res = sql_param_query('SELECT su.'.$field.'
                            FROM sb_site_users su, sb_catlinks l, sb_categs c
                            WHERE c.cat_id IN (?a) AND c.cat_id=l.link_cat_id AND l.link_el_id=su.su_id
                            '.$elems_fields_where_sql.$elems_fields_filter_sql.'
                            GROUP BY su.su_id
                            ORDER BY c.cat_left'.($elems_fields_sort_sql != '' ? $elems_fields_sort_sql : ', su.su_reg_date DESC').
        					($params['filter'] == 'from_to' ? ' LIMIT '.(max(0, intval($params['filter_from']) - 1)).', '.(intval($params['filter_to']) != 0 ? (intval($params['filter_to']) - intval($params['filter_from']) + 1) : '9999999999') : ''), $cat_ids);
    }
    else
    {
        $res = sql_param_query('SELECT su.'.$field.'
                            FROM sb_site_users su, sb_catlinks l
                            WHERE l.link_cat_id IN (?a) AND l.link_el_id=su.su_id
                            '.$elems_fields_where_sql.$elems_fields_filter_sql.'
                            GROUP BY su.su_id
                            '.($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ' ORDER BY su.su_reg_date DESC').
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
function fSite_Users_Elem_Filter($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_site_users', $tag_id, array($el_id, $temp_id, $params)))
		return;

	$res = sql_param_query('SELECT sut_id, sut_title, sut_lang, sut_form, sut_fields_temps
							FROM sb_site_users_temps WHERE sut_id=?d', $temp_id);
	if (!$res)
	{
		sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_FAQ_PLUGIN), SB_MSG_WARNING);
		$GLOBALS['sbCache']->save('pl_site_users', '');
		return;
	}

	list($sut_id, $sut_title, $sut_lang, $sut_form, $sut_fields_temps) = $res[0];

	$params = unserialize(stripslashes($params));
	$sut_fields_temps = unserialize($sut_fields_temps);

	$result = '';
	if (trim($sut_form) == '')
	{
		$GLOBALS['sbCache']->save('pl_site_users', '');
		return;
	}


	$tags = array('{ACTION}',
						'{TEMP_ID}',
						'{SU_ID}',
						'{SU_ID_LO}',
						'{SU_ID_HI}',
						'{SU_LOGIN}',
						'{SU_EMAIL}',
						'{SU_REG_DATE}',
						'{SU_REG_DATE_LO}',
						'{SU_REG_DATE_HI}',
						'{SU_LAST_DATE}',
						'{SU_LAST_DATE_LO}',
						'{SU_LAST_DATE_HI}',
						'{SU_STATUS}',
						'{SU_ACTIVE_DATE}',
						'{SU_ACTIVE_DATE_LO}',
						'{SU_ACTIVE_DATE_HI}',
						'{SU_NAME}',
						'{SU_PERS_BIRTH}',
						'{SU_PERS_SEX}',
						'{SU_PERS_PHONE}',
						'{SU_PERS_MOB_PHONE}',
						'{SU_PERS_ZIP}',
						'{SU_PERS_ADRESS}',
						'{SU_PERS_ADDITION}',
						'{SU_WORK_NAME}',
						'{SU_WORK_UNIT}',
						'{SU_WORK_POSITION}',
						'{SU_WORK_OFFICE_NUMBER}',
						'{SU_WORK_PHONE}',
						'{SU_WORK_PHONE_INNER}',
						'{SU_WORK_FAX}',
						'{SU_WORK_EMAIL}',
						'{SU_WORK_ADDITION}',
						'{SU_FORUM_NICK}',
						'{SU_FORUM_TEXT}',
						'{SORT_SELECT}');


	if (isset($params['page']) && trim($params['page']) != '')
	{
		$action = $params['page'];
	}
	else
	{
		$action = $GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
	}

	//	вывод полей формы input
	$values[] = $action;
	$values[] = $sut_id;
	$values[] = (isset($sut_fields_temps['su_id']) && $sut_fields_temps['su_id'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['su_f_id']) && $_REQUEST['su_f_id'] != '' ? $_REQUEST['su_f_id'] : ''), $sut_fields_temps['su_id']) : '');
	$values[] = (isset($sut_fields_temps['su_id_lo']) && $sut_fields_temps['su_id_lo'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['su_f_id_lo']) && $_REQUEST['su_f_id_lo'] != '' ? $_REQUEST['su_f_id_lo'] : ''), $sut_fields_temps['su_id_lo']) : '');
	$values[] = (isset($sut_fields_temps['su_id_hi']) && $sut_fields_temps['su_id_hi'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['su_f_id_hi']) && $_REQUEST['su_f_id_hi'] != '' ? $_REQUEST['su_f_id_hi'] : ''), $sut_fields_temps['su_id_hi']) : '');
	$values[] = (isset($sut_fields_temps['su_login']) && $sut_fields_temps['su_login'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['su_f_login']) && $_REQUEST['su_f_login'] != '' ? $_REQUEST['su_f_login'] : ''), $sut_fields_temps['su_login']) : '');
	$values[] = (isset($sut_fields_temps['su_email']) && $sut_fields_temps['su_email'] != '' ? str_replace('{VALUE}', (isset($_REQUEST['su_f_email']) && $_REQUEST['su_f_email'] != '' ? $_REQUEST['su_f_email'] : ''), $sut_fields_temps['su_email']) : '');
	$values[] = (isset($sut_fields_temps['su_reg_date']) && $sut_fields_temps['su_reg_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_reg_date']) && $_REQUEST['su_f_reg_date'] != '' ? $_REQUEST['su_f_reg_date'] : ''), $sut_fields_temps['su_reg_date']) : '';
	$values[] = (isset($sut_fields_temps['su_reg_date_lo']) && $sut_fields_temps['su_reg_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_reg_date_lo']) && $_REQUEST['su_f_reg_date_lo'] != '' ? $_REQUEST['su_f_reg_date_lo'] : ''), $sut_fields_temps['su_reg_date_lo']) : '';
	$values[] = (isset($sut_fields_temps['su_reg_date_hi']) && $sut_fields_temps['su_reg_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_reg_date_hi']) && $_REQUEST['su_f_reg_date_hi'] != '' ? $_REQUEST['su_f_reg_date_hi'] : ''), $sut_fields_temps['su_reg_date_hi']) : '';
	$values[] = (isset($sut_fields_temps['su_last_date']) && $sut_fields_temps['su_last_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_last_date']) && $_REQUEST['su_f_last_date'] != '' ? $_REQUEST['su_f_last_date'] : ''), $sut_fields_temps['su_last_date']) : '';
	$values[] = (isset($sut_fields_temps['su_last_date_lo']) && $sut_fields_temps['su_last_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_last_date_lo']) && $_REQUEST['su_f_last_date_lo'] != '' ? $_REQUEST['su_f_last_date_lo'] : ''), $sut_fields_temps['su_last_date_lo']) : '';
	$values[] = (isset($sut_fields_temps['su_last_date_hi']) && $sut_fields_temps['su_last_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_last_date_hi']) && $_REQUEST['su_f_last_date_hi'] != '' ? $_REQUEST['su_f_last_date_hi'] : ''), $sut_fields_temps['su_last_date_hi']) : '';

	$status_str = '';
	if(isset($sut_fields_temps['su_status_option']) && $sut_fields_temps['su_status_option'] != '')
	{
		$status = array();
		$status[] = KERNEL_PROG_PL_SITE_USERS_STATUS_REG;
		$status[] = KERNEL_PROG_PL_SITE_USERS_STATUS_MOD;
		$status[] = KERNEL_PROG_PL_SITE_USERS_STATUS_EMAIL;
		$status[] = KERNEL_PROG_PL_SITE_USERS_STATUS_MOD_EMAIL;
		$status[] = KERNEL_PROG_PL_SITE_USERS_STATUS_BLOCK;

		foreach($status as $key => $value)
		{
			$selected = false;
			if(isset($_REQUEST['su_f_status']) && is_array($_REQUEST['su_f_status']))
			{
				$selected = (in_array($key, $_REQUEST['su_f_status']));
			}
			else
			{
				$selected = (isset($_REQUEST['su_f_status']) && $_REQUEST['su_f_status'] == $key && $_REQUEST['su_f_status'] !== '');
			}
			$status_str .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}'),
				array($key, ($selected ? 'selected="selected"' : ''), $value), $sut_fields_temps['su_status_option']);
		}
	}

	$values[] = (isset($sut_fields_temps['su_status']) && $sut_fields_temps['su_status'] != '') ? str_replace('{OPTIONS}', $status_str, $sut_fields_temps['su_status']) : '';
	$values[] = (isset($sut_fields_temps['su_active_date']) && $sut_fields_temps['su_active_date'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_active_date']) && $_REQUEST['su_f_active_date'] != '' ? $_REQUEST['su_f_active_date'] : ''), $sut_fields_temps['su_active_date']) : '';
	$values[] = (isset($sut_fields_temps['su_active_date_lo']) && $sut_fields_temps['su_active_date_lo'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_active_date_lo']) && $_REQUEST['su_f_active_date_lo'] != '' ? $_REQUEST['su_f_active_date_lo'] : ''), $sut_fields_temps['su_active_date_lo']) : '';
	$values[] = (isset($sut_fields_temps['su_active_date_hi']) && $sut_fields_temps['su_active_date_hi'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_active_date_hi']) && $_REQUEST['su_f_active_date_hi'] != '' ? $_REQUEST['su_f_active_date_hi'] : ''), $sut_fields_temps['su_active_date_hi']) : '';
	$values[] = (isset($sut_fields_temps['su_name']) && $sut_fields_temps['su_name'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_name']) && $_REQUEST['su_f_name'] != '' ? $_REQUEST['su_f_name'] : ''), $sut_fields_temps['su_name']) : '';
	$values[] = (isset($sut_fields_temps['su_birth']) && $sut_fields_temps['su_birth'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_pers_birth']) && $_REQUEST['su_f_pers_birth'] != '' ? $_REQUEST['su_f_pers_birth'] : ''), $sut_fields_temps['su_birth']) : '';

	$sex_str = '';
	if(isset($sut_fields_temps['su_sex_option']) && $sut_fields_temps['su_sex_option'] != '')
	{
		$sex = array();
		$sex[] = KERNEL_PROG_PL_SITE_USERS_SEX_MALE;
		$sex[] = KERNEL_PROG_PL_SITE_USERS_SEX_FEMALE;

		foreach($sex as $key => $value)
		{
			$selected = false;
			if(isset($_REQUEST['su_f_sex']) && is_array($_REQUEST['su_f_sex']))
				$selected = (in_array($key, $_REQUEST['su_f_sex']));
			else
				$selected = (isset($_REQUEST['su_f_sex']) && $_REQUEST['su_f_sex'] == $key && $_REQUEST['su_f_sex'] !== '');

			$sex_str .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}'),
				array($key, ($selected ? 'selected="selected"' : ''), $value), $sut_fields_temps['su_sex_option']);
		}
	}

	@require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$values[] = (isset($sut_fields_temps['su_sex']) && $sut_fields_temps['su_sex'] != '') ? str_replace('{OPTIONS}', $sex_str, $sut_fields_temps['su_sex']) : '';
	$values[] = (isset($sut_fields_temps['su_phone']) && $sut_fields_temps['su_phone'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_pers_phone']) && $_REQUEST['su_f_pers_phone'] != '' ? $_REQUEST['su_f_pers_phone'] : ''), $sut_fields_temps['su_phone']) : '';
	$values[] = (isset($sut_fields_temps['su_mob_phone']) && $sut_fields_temps['su_mob_phone'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_pers_mob_phone']) && $_REQUEST['su_f_pers_mob_phone'] != '' ? $_REQUEST['su_f_pers_mob_phone'] : ''), $sut_fields_temps['su_mob_phone']) : '';
	$values[] = (isset($sut_fields_temps['su_zip']) && $sut_fields_temps['su_zip'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_pers_zip']) && $_REQUEST['su_f_pers_zip'] != '' ? $_REQUEST['su_f_pers_zip'] : ''), $sut_fields_temps['su_zip']) : '';
	$values[] = (isset($sut_fields_temps['su_adress']) && $sut_fields_temps['su_adress'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_pers_adress']) && $_REQUEST['su_f_pers_adress'] != '' ? $_REQUEST['su_f_pers_adress'] : ''), $sut_fields_temps['su_adress']) : '';
	$values[] = (isset($sut_fields_temps['su_addition']) && $sut_fields_temps['su_addition'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_pers_addition']) && $_REQUEST['su_f_pers_addition'] != '' ? $_REQUEST['su_f_pers_addition'] : ''), $sut_fields_temps['su_addition']) : '';
	$values[] = (isset($sut_fields_temps['su_work_name']) && $sut_fields_temps['su_work_name'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_name']) && $_REQUEST['su_f_work_name'] != '' ? $_REQUEST['su_f_work_name'] : ''), $sut_fields_temps['su_work_name']) : '';
	$values[] = (isset($sut_fields_temps['su_work_unit']) && $sut_fields_temps['su_work_unit'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_unit']) && $_REQUEST['su_f_work_unit'] != '' ? $_REQUEST['su_f_work_unit'] : ''), $sut_fields_temps['su_work_unit']) : '';
	$values[] = (isset($sut_fields_temps['su_work_position']) && $sut_fields_temps['su_work_position'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_position']) && $_REQUEST['su_f_work_position'] != '' ? $_REQUEST['su_f_work_position'] : ''), $sut_fields_temps['su_work_position']) : '';
	$values[] = (isset($sut_fields_temps['su_work_office_number']) && $sut_fields_temps['su_work_office_number'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_office_number']) && $_REQUEST['su_f_work_office_number'] != '' ? $_REQUEST['su_f_work_office_number'] : ''), $sut_fields_temps['su_work_office_number']) : '';
	$values[] = (isset($sut_fields_temps['su_work_phone']) && $sut_fields_temps['su_work_phone'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_phone']) && $_REQUEST['su_f_work_phone'] != '' ? $_REQUEST['su_f_work_phone'] : ''), $sut_fields_temps['su_work_phone']) : '';
	$values[] = (isset($sut_fields_temps['su_work_phone_inner']) && $sut_fields_temps['su_work_phone_inner'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_phone_inner']) && $_REQUEST['su_f_work_phone_inner'] != '' ? $_REQUEST['su_f_work_phone_inner'] : ''), $sut_fields_temps['su_work_phone_inner']) : '';
	$values[] = (isset($sut_fields_temps['su_work_fax']) && $sut_fields_temps['su_work_fax'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_fax']) && $_REQUEST['su_f_work_fax'] != '' ? $_REQUEST['su_f_work_fax'] : ''), $sut_fields_temps['su_work_fax']) : '';
	$values[] = (isset($sut_fields_temps['su_work_email']) && $sut_fields_temps['su_work_email'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_email']) && $_REQUEST['su_f_work_email'] != '' ? $_REQUEST['su_f_work_email'] : ''), $sut_fields_temps['su_work_email']) : '';
	$values[] = (isset($sut_fields_temps['su_work_addition']) && $sut_fields_temps['su_work_addition'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_work_addition']) && $_REQUEST['su_f_work_addition'] != '' ? $_REQUEST['su_f_work_addition'] : ''), $sut_fields_temps['su_work_addition']) : '';
	$values[] = (isset($sut_fields_temps['su_forum_nick']) && $sut_fields_temps['su_forum_nick'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_forum_nick']) && $_REQUEST['su_f_forum_nick'] != '' ? $_REQUEST['su_f_forum_nick'] : ''), $sut_fields_temps['su_forum_nick']) : '';
	$values[] = (isset($sut_fields_temps['su_forum_text']) && $sut_fields_temps['su_forum_text'] != '') ? str_replace('{VALUE}', (isset($_REQUEST['su_f_forum_text']) && $_REQUEST['su_f_forum_text'] != '' ? $_REQUEST['su_f_forum_text'] : ''), $sut_fields_temps['su_forum_text']) : '';
	$values[] = sbLayout::replacePluginFieldsTagsFilterSelect('su', $sut_fields_temps['su_sort_select'], $sut_form);


	sbLayout::parsePluginInputFields('pl_site_users', $sut_fields_temps, $sut_fields_temps['date_temps'], $tags, $values, -1, '', '', array(), array(), false, 'su_f', '', true);

	$result = str_replace($tags, $values, $sut_form);
	$result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

	$GLOBALS['sbCache']->save('pl_site_users', $result);
}

/**
 * Вывод разделов
 *
 */
function fSite_Users_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    $num_sub = 0;
    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_site_users', 'pl_site_users', 'su', $num_sub);
}
?>