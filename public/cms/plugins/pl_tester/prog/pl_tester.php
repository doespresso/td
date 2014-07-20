<?php

function fTester_Elem_Test($el_id, $temp_id, $params, $tag_id)
{
    // При включенном кешировании не обновляются вопросы
    /*
      if ($GLOBALS['sbCache']->check('pl_tester', $tag_id, array($el_id, $temp_id, $params)))
      return;
     */

	$users_fields = array();
	$users_fields_select_sql = '';

	$cat_tags = array();
    $user_tags = array();


    sbProgStartSession();

    $cur_time = time();
    $params = unserialize(stripslashes($params));

    $num_interrupts = $params['num_interrups'];

    $cat_ids = array();
    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['tester_scid']) || isset($_GET['tester_cid'])))
    {
        // используется связь с выводом разделов и выводить следует вопросы из соотв. раздела
        if (isset($_GET['tester_cid']))
        {
            $cat_ids[] = intval($_GET['tester_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=? AND cat_ident="pl_tester"', $_GET['tester_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
                $cat_ids[] = intval($_GET['tester_scid']);
            }
        }
    }
    else
    {
        $cat_ids[] = intval($params['id']);
    }
    $cat_check_ids = $cat_ids;
    $first_cat = $cat_ids[0]; // запоминаем id первичного теста.

    // вытаскиваем вложенные тесты и проверяем права на них
    if (isset($params['subcategs']) && $params['subcategs'] == 1)
    {
        $res = sql_param_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d '.((isset($params['show_hidden']) && $params['show_hidden'] == 1)? 'AND cat_rubrik=1' : ''), $first_cat);
        list($cat_left, $cat_right) = $res[0];

        $subcat_res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_ident="pl_tester" AND cat_left > ?d AND cat_right < ?d '.((isset($params['show_hidden']) && $params['show_hidden'] == 1)? 'AND cat_rubrik=1' : ''), $cat_left, $cat_right);

        if ($subcat_res)
        {
            foreach ($subcat_res as $row)
            {
                $cat_ids[] = $row[0];
            }
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
        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_tester_read');
    }


    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT stt_date, stt_lang, stt_top, stt_quest, stt_chet_answer, stt_nechet_answer, stt_empty_element,
    //                stt_always_chet, stt_bottom, stt_result, stt_system_message, stt_fields_temps, stt_categs_temps
    //                FROM sb_tester_temps WHERE stt_id=?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_tester_temps', $temp_id);
    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_tester', '');
        return;
    }

    list($stt_date, $stt_lang, $stt_top, $stt_quest, $stt_chet_answer, $stt_nechet_answer, $stt_empty_element,
    $stt_always_chet, $stt_bottom, $stt_result, $stt_system_message, $stt_fields_temps, $stt_categs_temps) = $res[0];

    $stt_fields_temps = unserialize($stt_fields_temps);
    $stt_system_message = unserialize($stt_system_message);
    $stt_categs_temps = unserialize($stt_categs_temps);

    if (!isset($stt_system_message['sorting_input']))
    {
        $stt_system_message['sorting_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_SORTING_INPUT;
    }

    if (!isset($stt_system_message['inline_input']))
    {
        $stt_system_message['inline_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_INLINE_INPUT;
    }

    if (!isset($stt_system_message['free_input']))
    {
        $stt_system_message['free_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_FREE_INPUT;
    }

    if ((count($cat_ids) == 0 && count($cat_check_ids) != 0) || !in_array($first_cat, $cat_ids))
    {
        // указанные тесты являются закрытыми или родидельский тест закрыт
        $GLOBALS['sbCache']->save('pl_tester', $stt_system_message['no_access']);
        return;
    }


    // зарегистрированный пользователь или нет
    if (isset($_SESSION['sbAuth']) && $_SESSION['sbAuth']->getUserId() != -1 && $_SESSION['sbAuth']->getUserId() != 0)
    {
        $user_id = $_SESSION['sbAuth']->getUserId();
    }
    elseif (isset($_COOKIE['sb_tester_su_id']))
    {
        $user_id = $_COOKIE['sb_tester_su_id'];
    }
    else
    {
        $user_id = '';
    }

    //фиксируем начало теста
    if(!isset($_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test']))
    {
        $_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test'] = time();
    }

    // вытаскиваем кол-во попыток использованных пользователем сдать тест
    $res = sql_param_query('SELECT str_num_attempts FROM sb_tester_results WHERE str_test_id=?d AND str_user_id=?', $first_cat, $user_id);
    if ($res)
    {
        list($retest_num) = $res[0];
    }
    else
    {
        $retest_num = 0;
    }

    // если первичный тест не является закрытым, то ставим его id на первое место
    if(in_array($first_cat, $cat_ids))
    {
        $key = array_search($first_cat, $cat_ids);
        $cat_ids[$key] = $cat_ids[0];
        $cat_ids[0] = $first_cat;
    }

    // если пользователь нажал на кнопку отмена, очистить временные результаты и закончить тест выводом соотв. сообщения
    if (isset($_POST['sb_tester_cancel']))
    {
        sql_param_query('DELETE FROM sb_tester_temp_results WHERE sttr_user_id=?d AND sttr_test_id = ?d', $user_id, $first_cat);
        sql_param_query('DELETE FROM sb_tester_temp_interrupts WHERE stti_user_id=?d AND stti_test_id = ?d', $user_id, $first_cat);
        sql_param_query('DELETE FROM sb_tester_temp_any_answer WHERE sttaa_user_id=?d', $user_id);

        unset($_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test']);

        $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;
        $GLOBALS['sbCache']->save('pl_tester', $stt_system_message['test_interrupted']);
        return;
    }


    // вытаскиваем свойства теста
    $test_max_questions = array();
    $cat_values = array();
    $test_settings = sql_param_query('SELECT cat_title, cat_fields, cat_left, cat_right, cat_id FROM sb_categs WHERE cat_id IN (?a)' .
    (isset($params['show_hidden']) && $params['show_hidden'] == 1 ? ' AND cat_rubrik=1' : ''), $cat_ids);

    if ($test_settings)
    {
        //   название теста  время тестирования  кол-во вопросов описание теста  доп. опции
        list($test_title, $cat_fields, $cat_left, $cat_right) = $test_settings[0];

        if ($cat_fields != '')
            $cat_fields = unserialize($cat_fields);
        else
            $cat_fields = '';

        // вытаскиваем пользовательские поля
        $res_user_fields = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_tester"');

        if ($cat_fields != '')
        {
            $test_num_questions = $cat_fields['spin_num_questions'];     // Кол-во задаваемых вопросов
            $test_num_questions_per_page = $cat_fields['spin_questions_per_page'];     // Кол-во вопросов на странице
            $test_test_time = $cat_fields['spin_test_time'];             // Время, отводимое на тест (в секундах):
            $test_retest_time = $cat_fields['spin_retest_time'];         // время повторной сдачи теста
            $test_email = $cat_fields['email'];        // e-mail куратора теста
            $test_answer_time = isset($cat_fields['spin_answer_time']) ? $cat_fields['spin_answer_time'] : -1; // время, отводимое на вопрос
            $test_retest_num = isset($cat_fields['spin_retest_num']) ? $cat_fields['spin_retest_num'] : -1; // кол-во попыток сдачи тест



            // Пользовательские поля теста
            if ($res_user_fields && $res_user_fields[0][1] != '')
			{
				$pd_categs = unserialize($res_user_fields[0][1]);
				foreach ($pd_categs as $value)
			    {
			        if (isset($value['sql']) && $value['sql'] == 1)
			        {
				        if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
		                {
		                	$cat_tags[] = '{'.$value['tag'].'_LATITUDE}';
		                	$cat_tags[] = '{'.$value['tag'].'_LONGTITUDE}';

			                if ($value['type'] == 'yandex_coords')
		                	{
		                		$cat_tags[] = '{'.$value['tag'].'_API_KEY}';
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
		                	}
		                }
		                else
		                {
		                	$cat_tags[] = '{'.$value['tag'].'}';
		                	if(isset($cat_fields['user_f_'.intval($value['id'])]))
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
			    $cat_values = sbLayout::parsePluginFields($pd_categs, $cat_values, $stt_categs_temps, array(), array(), 'ru', '', '', $allow_bb);
			}
        }
        else
        {
            $test_retest_time = -1;
            $test_email = '';
            $test_answer_time = -1;
            $test_retest_num = -1;
        }

        // если кол-во попыток сдать тест ограничено, то проверяем не истекло ли оно
        if ($test_retest_num != -1 && $retest_num >= $test_retest_num)
        {
            $GLOBALS['sbCache']->save('pl_tester', str_replace('{NUM_ATTEMPTS}', $test_retest_num, $stt_system_message['attempts_limit']));
            return;
        }

        //считаем количество вопросов по настройкам тестов
        $nq_tmp = array();
        foreach ($test_settings as $settings)
        {
            $fld = unserialize($settings[1]);
            if ($settings[4] == $first_cat)
            {
                $nq_tmp[$settings[4]] = $fld['spin_num_questions'];
            }
            else
            {
                $nq_tmp[$settings[4]] = (isset($fld['spin_num_questions_child'])) ? $fld['spin_num_questions_child'] : -1;
            }
        }

        // Корректируем количество с учетом количества выводимых вопросов в тестах
        $res = sql_param_query('SELECT scl.link_cat_id, COUNT(*)
                                FROM sb_catlinks scl
                                INNER JOIN sb_tester_questions stq ON stq.stq_id=scl.link_el_id
                                WHERE scl.link_cat_id IN (?a)
                                AND stq.stq_show=1
                                GROUP BY scl.link_cat_id', $cat_ids);

        $test_real_num_questions = array();
        if ($res)
        {
            foreach($res as $row)
            {
                $test_real_num_questions[$row[0]] = $row[1];
            }
        }

        if(!empty($nq_tmp))
        {
            foreach($nq_tmp as $key=>$val)
            {
                if(isset($test_real_num_questions[$key]) && $test_real_num_questions[$key] > $val && $val != -1)
                {
                    $test_real_num_questions[$key] = $val;
                }
            }
        }

        if($test_num_questions == -1)
        {
            $test_num_questions = array_sum($test_real_num_questions);
        }
        else
        {
            $test_num_questions = ($test_num_questions > array_sum($test_real_num_questions))? array_sum($test_real_num_questions) : $test_num_questions;
        }
    }
    else
    {
        $GLOBALS['sbCache']->save('pl_tester', '');
        return;
    }

    if ($user_id != '' && $test_retest_time != -1)
    {
        // проверяем, истекло ли время пересдачи теста, если пользователь уже сдавал тест
        $res = sql_param_query('SELECT str_time FROM sb_tester_results WHERE str_test_id=?d AND str_user_id=?d', $first_cat, $user_id);
        if ($res)
        {
            list($str_time) = $res[0];
            if ($cur_time - $str_time <= $test_retest_time * 24 * 60 * 60)
            {
                $GLOBALS['sbCache']->save('pl_tester', $stt_system_message['repeated_test']);
                return;
            }
        }
    }

    $query_string = '';
    foreach ($_GET as $key => $value)
    {
        if ($key != 'sb_q_id')
        {
            if (!is_array($value))
            {
                $query_string .= $key . '=' . urlencode($value) . '&';
            }
            else
            {
                foreach ($value as $key2 => $value2)
                {
                    $query_string .= $key . '[]' . '=' . urlencode($value2) . '&';
                }
            }
        }
    }

    $action = $_SERVER['PHP_SELF'];
    $continue_test = false;  // продолжать тест или начинать заново
    $num_questions = 0;   // кол-во заданных вопросов
    $show_result = false;   // тест закончен, вывод результатов
    $was_interrupted = false;  // был обрыв связи
    $test_num_interrupts = -1;  // кол-во обрывов связи

    if (isset($_POST['sb_tester_time']) && isset($_POST['sb_tester_hash']) && isset($_GET['sb_q_id']))
    {
        // пришел ответ
        $quests = explode(',', $_GET['sb_q_id']); // количество вопросов в текущем сеансе

        //проверяем, не пытаются ли нас взломать
        $begin_time = $_POST['sb_tester_time'];
        $num_numbers = strlen($begin_time);
        $sum_numbers = 0;
        for ($i = 0; $i < $num_numbers; $i++)
        {
            $number = substr($begin_time, $i, 1);
            $sum_numbers += $number;
        }
        $sum_numbers = md5('%' . $sum_numbers . '%');
        if ($sum_numbers != $_POST['sb_tester_hash'])
        {
            $GLOBALS['sbCache']->save('pl_tester', $stt_system_message['repeated_test']);
            return;
        }

        // подсчитываем время ответа
        if (count($quests) == 0 || empty($quests[0]))
        {
            // защита от редактирования GET-параметра и наказание :)
            $answer_time = PHP_INT_MAX;
        }
        else
        {
            $answer_time = round(($cur_time - $begin_time) / count($quests));
        }

        //проверяем, истекло ли время, отведенное на ответ
        if ($test_answer_time != -1 && $answer_time >= $test_answer_time || $answer_time == PHP_INT_MAX)
        {
            // тест закончен записываем результат
            // записываем результат в базу
            $res = sql_param_query('SELECT SUM(sttr_mark), SUM(sttr_answer_time) FROM sb_tester_temp_results WHERE sttr_test_id=?d AND sttr_user_id=?d GROUP BY sttr_test_id, sttr_user_id', $first_cat, $user_id);
            if ($res)
            {
                list($str_mark, $str_test_time) = $res[0];
            }
            else
            {
                $str_mark = $str_test_time = 0;
            }

            // если пользователь зарегистрированный, то записываем результат
            if (isset($_SESSION['sbAuth']))
            {
                sql_param_query('DELETE FROM sb_tester_results WHERE str_test_id=?d AND str_user_id=?d', $first_cat, $user_id);

                $row = array();
                $row['str_test_id'] = $first_cat;
                $row['str_user_id'] = $user_id;
                $row['str_time'] = time();
                $row['str_mark'] = $str_mark;
                $row['str_test_time'] = $str_test_time;
                $row['str_ip'] = $_SESSION['sbAuth']->getIP();
                $row['str_num_attempts'] = ($retest_num + 1);

                $res_temps = sql_param_query('SELECT sttr_quest_id, sttr_answer_ids, sttr_time, sttr_answer_time, sttr_mark, sttr_cat_id FROM sb_tester_temp_results WHERE sttr_test_id = ?d AND sttr_user_id= ?d', $first_cat, $user_id);
                if (isset($cat_fields['criteria_persent']) && $cat_fields['criteria_persent'] == 1)
                {
                    //Расчитываем процент набранных баллов от максимально возможных в заданных вопросах
                    $row['str_mark'] = fTester_Calc_Mark_Persent($res_temps, $str_mark);
                    $str_mark = $row['str_mark']; //Меняем, чтобы правильно отобразить результат теста (итоги в процентах)
                    $row['str_passed'] = ($row['str_mark'] >= $cat_fields['spin_criteria'] ? 1 : 0);
                }
                else
                {
                    $row['str_passed'] = ($str_mark >= $cat_fields['spin_criteria'] ? 1 : 0);
                }

                sql_param_query('INSERT INTO sb_tester_results SET ?a', $row);

                // записываем детальный результат теста для пользователя
                $quest_ids = array();
                if ($res_temps)
                {
                    for ($i = 0; $i < count($res_temps); $i++)
                    {
                        list($sttr_quest_id, $sttr_answer_ids, $sttr_time, $sttr_answer_time, $sttr_mark, $sttr_cat_id) = $res_temps[$i];

                        $quest_ids[] = $sttr_quest_id;

                        $row = array();
                        $row['star_user_id'] = $user_id;
                        $row['star_test_id'] = $first_cat;
                        $row['star_attempt_id'] = $retest_num + 1;
                        $row['star_quest_id'] = $sttr_quest_id;
                        $row['star_answer_ids'] = $sttr_answer_ids;
                        $row['star_time'] = $sttr_time;
                        $row['star_answer_time'] = $sttr_answer_time;
                        $row['star_mark'] = $sttr_mark;
                        $row['star_cat_id'] = $sttr_cat_id;

                        sql_param_query('INSERT INTO sb_tester_answers_results SET ?a', $row);
                    }
                }

                if (!empty($quest_ids))
                {
                    $res_any_temp = sql_param_query('SELECT sttaa_user_id, sttaa_quest_id, sttaa_answer_id, sttaa_answer_text FROM sb_tester_temp_any_answer WHERE sttaa_quest_id IN (?a) AND sttaa_user_id=?d', $quest_ids, $user_id);
                    if ($res_any_temp)
                    {
                        foreach ($res_any_temp as $row)
                        {
                            $row_any = array();
                            list($row_any['staa_user_id'], $row_any['staa_quest_id'], $row_any['staa_answer_id'], $row_any['staa_answer_text']) = $row;

                            sql_param_query('INSERT INTO sb_tester_any_answers SET ?a', $row_any);
                        }
                    }
                }
            }
            else
            {
                $user_login = '';
                $user_fio = '';
            }

            sql_param_query('DELETE FROM sb_tester_temp_results WHERE sttr_user_id= ?d AND sttr_test_id= ?d', $user_id, $first_cat);
            sql_param_query('DELETE FROM sb_tester_temp_interrupts WHERE stti_user_id= ?d AND stti_test_id= ?d', $user_id, $first_cat);
            sql_param_query('DELETE FROM sb_tester_temp_any_answer WHERE sttaa_user_id=?d AND sttaa_quest_id=?d', $user_id, $first_cat);

            unset($_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test']);

            $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;


            if ($test_test_time == -1)
            {
                $test_test_time = $stt_system_message['no_limit_text'];
            }
            if ($test_answer_time == -1)
            {
                $test_answer_time = $stt_system_message['no_limit_text'];
            }
            if ($test_retest_num == -1)
            {
                $test_retest_num = $stt_system_message['no_limit_text'];
            }
            if ($test_retest_time == -1)
            {
                $test_retest_time = $stt_system_message['no_limit_text'];
            }
            $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;

            // вывод результатов
            // отправляем письмо куратору
            if ($test_email != '')
            {
                $test_email_subj = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}'), array($test_title, $cat_fields['descr'], $stt_system_message['end_time_answer'], $str_mark, $str_test_time, $user_login, $user_fio), $stt_system_message['letter_theme']);
                $test_email_body = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}'), array($test_title, $cat_fields['descr'], $stt_system_message['end_time_answer'], $str_mark, $str_test_time, $user_login, $user_fio), $stt_system_message['letter_body']);

                //чистим код от инъекций
                $test_email_subj = sb_clean_string($test_email_subj);

                ob_start();
                eval(' ?>' . $test_email_subj . '<?php ');
                $test_email_subj = trim(ob_get_clean());

                //чистим код от инъекций
                $test_email_body = sb_clean_string($test_email_body);

                ob_start();
                eval(' ?>' . $test_email_body . '<?php ');
                $test_email_body = trim(ob_get_clean());

                if ($test_email_subj != '' && $test_email_body != '')
                {
                    include_once(SB_CMS_LIB_PATH . '/sbMail.inc.php');

                    $mailer = new sbMail();
                    $mailer->setSubject($test_email_subj);
                    $mailer->setHtml($test_email_body);
                    $mailer->send($test_email);
                }
            }

            $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;
            $GLOBALS['sbCache']->save('pl_tester', $stt_system_message['end_time_answer']);
            return;
        }

        //записываем ответ в базу
        $answersArr = array();
        if (isset($_POST['sb_tester_answer']))
        {
            foreach ($_POST['sb_tester_answer'] as $key => $val)
            {
                $answersArr[$key] = $val;
            }
        }

        if (isset($_POST['sb_tester_answer_chk']))
        {
            foreach ($_POST['sb_tester_answer_chk'] as $key => $val)
            {
                $answersArr[$key] = $val;
            }
        }

        if (isset($_POST['sb_tester_answer_sort']))
        {
            $result = array();
            foreach ($_POST['sb_tester_answer_sort'] as $key => $val)
            {
                asort($val);
                $answersArr[$key] = array_keys($val);
            }
        }

        if(isset($_POST['sb_tester_answer_inline']))
        {
            foreach ($_POST['sb_tester_answer_inline'] as $key => $val)
            {
                $answersArr[$key] = $val;
            }
        }

        foreach ($quests as $quest_id)
        {
            $answers = array();
            if (isset($answersArr[$quest_id]))
            {
                $answers = $answersArr[$quest_id];
            }
            //Проверяем на наличие произвольных ответов
            $any_answer = '';
            $any_id = 0;
            if (!empty($answers) && is_array($answers))
            {
                $tmp = array();
                foreach ($answers as $val)
                {
                    if (isset($_POST['any_answer_text_' . $val]) && trim($_POST['any_answer_text_' . $val]) == '')
                    {
                        continue;
                    }
                    elseif (isset($_POST['any_answer_text_' . $val]))
                    {
                        $any_answer = trim($_POST['any_answer_text_' . $val]);
                        $any_id = $val;
                    }
                    $tmp[] = $val;
                }
                $answers = $tmp;
            }
            elseif (!is_array($answers))
            {
                $answers = array($answers);
            }

            // записываем айдишники ответов в базу
            $answers_ids = '';
            $all_mark = 0; // оценка за ответ

            if (!empty($answers))
            {
                $answers_ids = implode(',', $answers);

                if (isset($_POST['sb_tester_answer_sort'][$quest_id]))
                {
                    //начисление баллов за правильную расстановку ответов
                    $res = sql_param_query('SELECT stw_id, stw_mark FROM sb_tester_answers WHERE stw_id IN (?a) AND stw_is_delete=0', $answers);
                    if ($res)
                    {
                        $all_right = true;
                        foreach ($res as $row)
                        {
                            $key = array_search($row[0], $answers);
                            if (false === $key || $key + 1 != $row[1])
                            {
                                $all_right = false;
                                break;
                            }
                        }

                        if ($all_right)
                        {
                            $res = sql_param_query('SELECT stq_ball FROM sb_tester_questions WHERE stq_id=?d', $quest_id);
                            if ($res)
                            {
                                $all_mark = $res[0][0];
                            }
                        }
                    }
                }
                elseif (isset($_POST['sb_tester_answer_inline'][$quest_id]))
                {
                    //начисление баллов за правильное сопоставление ответов
                    $res = sql_param_query('SELECT stw_id, stw_mark FROM sb_tester_answers WHERE stw_quest_id =?d AND stw_is_delete=0', $quest_id);
                    if($res)
                    {
                        //формируем массив с правильными ответами
                        $correct = array();
                        foreach($res as $row)
                        {
                            if(!isset($correct[$row[1]]))
                            {
                                $correct[$row[1]] = array();
                            }
                            $correct[$row[1]][] = $row[0];
                        }

                        //удаляем ответы для которых нет соответствия
                        foreach($correct as $key=>$val)
                        {
                            if(count($val) < 2)
                            {
                                unset($correct[$key]);
                            }
                        }

                        //формируем массив ответов пользователя
                        $user_answers = array();
                        foreach($answersArr[$quest_id] as $val=>$key)
                        {
                            if(!isset($user_answers[$key]))
                            {
                                $user_answers[$key] = array();
                            }
                            $user_answers[$key][] = $val;
                        }

                        //сравниваем оба массива и формируем данные для статистики
                        $count_correct_answers = 0;
                        $answers_ids = array();
                        foreach($user_answers as $arr)
                        {
                            if(count($arr) < 2)
                            {
                                continue; //ответ ни с чем не сопоставлен
                            }
                            $equals = false; //флаг наличия совпадения
                            foreach($correct as $correct_answer)
                            {
                                $diff = array_diff($arr, $correct_answer);
                                if(empty($diff))
                                {
                                    $equals = true;
                                }
                            }

                            $answers_ids[] = implode('^', $arr);

                            if($equals)
                            {
                                $count_correct_answers++;
                            }
                        }

                        if($count_correct_answers == count($correct))
                        {
                            $res = sql_param_query('SELECT stq_ball FROM sb_tester_questions WHERE stq_id=?d', $quest_id);
                            if ($res)
                            {
                                $all_mark = $res[0][0];
                            }
                        }
                        $answers_ids = implode(',',$answers_ids);
                    }
                }
                else
                {
                	//Вытаскиваем настроку вопроса "Засчитывать баллы при полностью верном ответе" и максимально возможный балл для вопроса
                	$quest_params = sql_param_query('SELECT q.stq_aps_write, a.summ FROM sb_tester_questions q
														LEFT JOIN ( SELECT stw_quest_id, SUM(stw_mark) as "summ" FROM sb_tester_answers WHERE stw_is_delete = 0 AND stw_mark>0 GROUP BY stw_quest_id ) AS a ON a.stw_quest_id = q.stq_id
														WHERE q.stq_id = ?d', $quest_id);



                    //баллы за ответы
                    $res = sql_param_query('SELECT SUM(stw_mark) FROM sb_tester_answers WHERE stw_id IN (?a) AND stw_is_delete=0', $answers);
                    if ($res)
                    {
                        $all_mark = $res[0][0];
						//если настройка "Засчитывать баллы при полностью верном ответе" включена и набранные баллы меньше чем максимально возможные засчитываем 0 баллов.
						if ( $quest_params && isset($quest_params[0][0]) && $quest_params[0][0] == 1 && $all_mark < $quest_params[0][1] ) $all_mark = 0;
                    }
                }
            }

            if ($all_mark < 0)
            {
                $all_mark = 0;
            }

            $row = array();
            $row['sttr_user_id'] = $user_id;
            $row['sttr_test_id'] = $first_cat;
            $row['sttr_cat_id'] = $_POST['sb_tester_cat_id'];
            $row['sttr_quest_id'] = $quest_id;
            $row['sttr_answer_ids'] = $answers_ids;
            $row['sttr_time'] = $cur_time;
            $row['sttr_answer_time'] = $answer_time;
            $row['sttr_mark'] = $all_mark;

            sql_param_query('INSERT INTO sb_tester_temp_results SET ?a', $row);

            if ($any_answer != '')
            {
                $row_any = array(
                    'sttaa_user_id' => $user_id,
                    'sttaa_quest_id' => $quest_id,
                    'sttaa_answer_id' => $any_id,
                    'sttaa_answer_text' => $any_answer,
                );
                sql_param_query('INSERT INTO sb_tester_temp_any_answer SET ?a', $row_any);
            }
        } //Цикл ответов
        header('Location: ' . sb_sanitize_header($action . ($query_string != '' ? '?' . $query_string : '')));
        $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;
        exit(0);
    }
    else
    {
        if (isset($_SESSION['sb_tester_' . $first_cat . '_refresh_count']))
        {
            $_SESSION['sb_tester_' . $first_cat . '_refresh_count']++;
            if ($_SESSION['sb_tester_' . $first_cat . '_refresh_count'] > 1)
            {
                // был нажат рефреш, считаем его за обрыв связи
                $row = array();
                $row['stti_user_id'] = $user_id;
                $row['stti_test_id'] = $first_cat;

                sql_param_query('INSERT INTO sb_tester_temp_interrupts SET ?a', $row);

                $res = sql_param_query('SELECT stti_test_id FROM sb_tester_temp_interrupts WHERE stti_user_id=?d AND stti_test_id=?d', $user_id, $first_cat);

                $test_num_interrupts = count($res);
                $was_interrupted = true;

                $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 1;
            }
        }
    }

    $res = sql_query('SELECT sttr_test_id, sttr_user_id, MIN(sttr_time), COUNT(sttr_id) FROM sb_tester_temp_results GROUP BY sttr_test_id, sttr_user_id');

    $num = count($res);
    for ($i = 0; $i < $num; $i++)
    {
        list($sttr_test_id, $sttr_user_id, $sttr_time, $sttr_count) = $res[$i];
        $sb_tester_restore_time = sbPlugins::getSetting('sb_tester_restore_time');

        if ($sb_tester_restore_time > 0 && $cur_time - $sttr_time > $sb_tester_restore_time * 24 * 60 * 60)
        {
            sql_param_query('DELETE FROM sb_tester_temp_results WHERE sttr_user_id=?d AND sttr_test_id=?d', $sttr_user_id, $sttr_test_id);
        }
        elseif ($sttr_user_id == $user_id && $sttr_test_id == $first_cat)
        {
            // в базе промежуточных результатов есть записи
            if (!isset($_SESSION['sb_tester_' . $first_cat . '_refresh_count']) || $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] > 1)
            {
                // произошел обрыв связи
                $row = array();

                $row['stti_user_id'] = $user_id;
                $row['stti_test_id'] = $first_cat;

                sql_param_query('INSERT INTO sb_tester_temp_interrupts SET ?a', $row);

                $res = sql_param_query('SELECT stti_test_id FROM sb_tester_temp_interrupts WHERE stti_user_id=?d AND stti_test_id=?d', $user_id, $first_cat);

                $test_num_interrupts = count($res);
                $was_interrupted = true;

                $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 1;
            }

            $continue_test = true; // продолжаем тест
            $num_questions = $sttr_count;
        }
    }

    if (!$continue_test && !isset($_SESSION['sb_tester_' . $first_cat . '_refresh_count']))
    {
        $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 1;
    }


    if ($num_questions >= $test_num_questions)
    {
        // если заданы все вопросы, то выводим результат
        $show_result = true;
    }
    elseif ($test_num_interrupts >= $num_interrupts)
    {
        // кол-во прерывов связи истекло, выводим результат
        $show_result = true;
    }
    elseif ($was_interrupted)
    {
        // был обрыв связи, выводим соотв. сообщение
        echo str_replace('{NUM_INTERRUPTS}', $num_interrupts - $test_num_interrupts, $stt_system_message['no_connection']);
    }

    if (!$show_result)
    {
        // вывод вопросов
        $in_str = array('0');
        $cat_in_str = array('0');
        $test_time = time() - $_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test'];

        if($test_time > 0)
        {
            $continue_test = true; //был обрыв связи на первой странице вопросов (ответов еще не было)
        }

        if ($test_test_time != -1)
        {
            $test_rest_time = $test_test_time;
        }
        else
        {
            $test_rest_time = $stt_system_message['no_limit_text'];
        }

        if ($continue_test)
        {
            $res = sql_param_query('SELECT sttr_quest_id FROM sb_tester_temp_results
									WHERE sttr_user_id=?d AND sttr_test_id=?d', $user_id, $first_cat);

            if($res)
            {
                foreach($res as $row)
                {
                    $in_str[] = $row[0];
                }
            }
            //проверяем, истекло ли время, отведенной на тест
            if ($test_test_time != -1)
            {
                if ($test_time >= $test_test_time)
                {
                    // записываем результат в базу
                    $res = sql_param_query('SELECT SUM(sttr_mark), SUM(sttr_answer_time) FROM sb_tester_temp_results WHERE sttr_test_id=?d AND sttr_user_id=?d GROUP BY sttr_test_id, sttr_user_id', $first_cat, $user_id);

                    if ($res)
                    {
                        list($str_mark, $str_test_time) = $res[0];
                    }
                    else
                    {
                        $str_mark = 0;
                        $str_test_time = 0;
                    }
                    // если пользователь зарегистрированный, то записываем результат
                    if (isset($_SESSION['sbAuth']))
                    {
                        sql_param_query('DELETE FROM sb_tester_results WHERE str_test_id=?d AND str_user_id=?d', $first_cat, $user_id);

                        $row = array();
                        $row['str_test_id'] = $first_cat;
                        $row['str_user_id'] = $user_id;
                        $row['str_time'] = time();
                        $row['str_mark'] = $str_mark;
                        $row['str_test_time'] = $str_test_time;
                        $row['str_ip'] = $_SERVER['REMOTE_ADDR'];
                        $row['str_num_attempts'] = $retest_num + 1;

                        $res_temps = sql_param_query('SELECT sttr_quest_id, sttr_answer_ids, sttr_time, sttr_answer_time, sttr_mark, sttr_cat_id FROM sb_tester_temp_results WHERE sttr_test_id = ?d AND sttr_user_id= ?d', $first_cat, $user_id);
                        if (isset($cat_fields['criteria_persent']) && $cat_fields['criteria_persent'] == 1)
                        {
                            //Расчитываем процент набранных баллов от максимально возможных в заданных вопросах
                            $row['str_mark'] = fTester_Calc_Mark_Persent($res_temps, $str_mark);
                            $str_mark = $row['str_mark']; //Меняем, чтобы правильно отобразить результат теста (итоги в процентах)
                            $row['str_passed'] = ($row['str_mark'] >= $cat_fields['spin_criteria'] ? 1 : 0);
                        }
                        else
                        {
                            $row['str_passed'] = ($str_mark >= $cat_fields['spin_criteria'] ? 1 : 0);
                        }

                        sql_param_query('INSERT INTO sb_tester_results SET ?a', $row);

                        $res_user = sql_param_query('SELECT su_login, su_name FROM sb_site_users WHERE su_id=?d', $_SESSION['sbAuth']->getUserId());
                        list($user_login, $user_fio) = $res_user[0];

                        // записываем детальный результат теста для пользователя
                        if ($res_temps)
                        {
                            $quest_ids = array();
                            for ($i = 0; $i < count($res_temps); $i++)
                            {
                                list($sttr_quest_id, $sttr_answer_ids, $sttr_time, $sttr_answer_time, $sttr_mark, $sttr_cat_id) = $res_temps[$i];

                                $quest_ids[] = $sttr_quest_id;

                                $row = array();
                                $row['star_user_id'] = $user_id;
                                $row['star_test_id'] = $first_cat;
                                $row['star_attempt_id'] = ($retest_num + 1);
                                $row['star_quest_id'] = $sttr_quest_id;
                                $row['star_answer_ids'] = $sttr_answer_ids;
                                $row['star_time'] = $sttr_time;
                                $row['star_answer_time'] = $sttr_answer_time;
                                $row['star_mark'] = $sttr_mark;
                                $row['star_cat_id'] = $sttr_cat_id;

                                sql_param_query('INSERT INTO sb_tester_answers_results SET ?a', $row);
                            }

                            // сохраняем произвольные ответы
                            if (!empty($quest_ids))
                            {
                                $res_any_temp = sql_param_query('SELECT sttaa_user_id, sttaa_quest_id, sttaa_answer_id, sttaa_answer_text FROM sb_tester_temp_any_answer WHERE sttaa_quest_id IN (?a) AND sttaa_user_id=?d', $quest_ids, $user_id);

                                if ($res_any_temp)
                                {
                                    foreach ($res_any_temp as $row)
                                    {
                                        $row_any = array();
                                        list($row_any['staa_user_id'], $row_any['staa_quest_id'], $row_any['staa_answer_id'], $row_any['staa_answer_text']) = $row;

                                        sql_param_query('REPLACE INTO sb_tester_any_answers SET ?a', $row_any);
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        $user_login = '';
                        $user_fio = '';
                    }

                    sql_param_query('DELETE FROM sb_tester_temp_results WHERE sttr_user_id=?d AND sttr_test_id=?d', $user_id, $first_cat);
                    sql_param_query('DELETE FROM sb_tester_temp_interrupts WHERE stti_user_id=?d AND stti_test_id=?d', $user_id, $first_cat);
                    sql_param_query('DELETE FROM sb_tester_temp_any_answer WHERE sttaa_user_id=?d', $user_id);

                    unset($_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test']);

                    $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;

                    if ($test_test_time == -1)
                    {
                        $test_test_time = $stt_system_message['no_limit_text'];
                    }
                    if ($test_answer_time == -1)
                    {
                        $test_answer_time = $stt_system_message['no_limit_text'];
                    }
                    if ($test_retest_num == -1)
                    {
                        $test_retest_num = $stt_system_message['no_limit_text'];
                    }
                    if ($test_retest_time == -1)
                    {
                        $test_retest_time = $stt_system_message['no_limit_text'];
                    }
                    $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;

                    // вывод результатов
                    $cur_date = sb_parse_date($cur_time, $stt_date, $stt_lang);

                    //отправляем письмо куратору
                    if ($test_email != '')
                    {
                        $test_email_subj = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}'), array($test_title, $cat_fields['descr'], $stt_system_message['end_time_answer'], $str_mark, $str_test_time, $user_login, $user_fio), $stt_system_message['letter_theme']);
                        $test_email_body = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}'), array($test_title, $cat_fields['descr'], $stt_system_message['end_time_answer'], $str_mark, $str_test_time, $user_login, $user_fio), $stt_system_message['letter_body']);

                        //чистим код от инъекций
                        $test_email_subj = sb_clean_string($test_email_subj);

                        ob_start();
                        eval(' ?>' . $test_email_subj . '<?php ');
                        $test_email_subj = trim(ob_get_clean());

                        //чистим код от инъекций
                        $test_email_body = sb_clean_string($test_email_body);

                        ob_start();
                        eval(' ?>' . $test_email_body . '<?php ');
                        $test_email_body = trim(ob_get_clean());

                        if ($test_email_subj != '' && $test_email_body != '')
                        {
                            include_once(SB_CMS_LIB_PATH . '/sbMail.inc.php');

                            $mailer = new sbMail();
                            $mailer->setSubject($test_email_subj);
                            $mailer->setHtml($test_email_body);
                            $mailer->send($test_email);
                        }
                    }
                    $GLOBALS['sbCache']->save('pl_tester', $stt_system_message['test_time']);
                    return;
                }
                else
                {
                    $test_rest_time = $test_test_time - $test_time;
                }
            }
        }
        elseif ($user_id == '')
        {
            $user_id = substr(md5(time()), 0, 11);
            sb_setcookie('sb_tester_su_id', $user_id, $cur_time + 360 * 24 * 60 * 60);
        }

        $elems_fields_sort_sql = '';
        if (isset($params['sort1']) && $params['sort1'] != '')
        {
            $elems_fields_sort_sql .= $params['sort1'] != 'RAND()' ? ', question.' . $params['sort1'] : ', ' . $params['sort1'];
            if (isset($params['order1']) && $params['order1'] != '')
            {
                $elems_fields_sort_sql .= ' ' . $params['order1'];
            }
        }

        $elems_fields_where_sql = '';

        if (isset($params) && $params['filter'] == 'order')
        {
            $from = intval($params['filter_order_from']);
            $to = intval($params['filter_order_to']);

            $elems_fields_where_sql .= ' AND question.stq_order >= ' . $from . ' AND question.stq_order <= ' . $to;
        }

        // формируем SQL-запрос для пользовательских полей
        if ($res_user_fields && $res_user_fields[0][0] != '')
        {
            $users_fields = unserialize($res_user_fields[0][0]);

            if ($users_fields)
            {
                foreach ($users_fields as $value)
                {
                    if (isset($value['sql']) && $value['sql'] == 1)
                    {
                        $users_fields_select_sql .= ', question.user_f_' . intval($value['id']);

                        if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
                        {
                            $user_tags[] = '{' . $value['tag'] . '_LATITUDE}';
                            $user_tags[] = '{' . $value['tag'] . '_LONGTITUDE}';

                            if ($value['type'] == 'yandex_coords')
                            {
                                $user_tags[] = '{' . $value['tag'] . '_API_KEY}';
                            }
                        }
                        else
                        {
                            $user_tags[] = '{' . $value['tag'] . '}';
                        }
                    }
                }
            }
        }

        //вытаскиваем вопросы, сортируя их по порядковому номеру
        // если нужно выводить вложенные тесты
        if (isset($params['subcategs']) && $params['subcategs'] == 1)
        {
            $sql_query = array();
            if(count($test_real_num_questions) > 0)
            {
                foreach($test_real_num_questions as $test_num=>$limit)
                {
                    $sql_query[] = '(SELECT question.stq_id, question.stq_question, question.stq_type, elems.link_cat_id '.$users_fields_select_sql.'
                       FROM sb_tester_questions question, sb_catlinks elems, sb_categs categs WHERE elems.link_cat_id=categs.cat_id
                       AND categs.cat_ident="pl_tester"
                       AND categs.cat_left >= '.$cat_left.'
                       AND categs.cat_right <= '.$cat_right.'
                       AND categs.cat_id = '.$test_num.'
					   AND categs.cat_rubrik = 1
                       AND question.stq_id=elems.link_el_id
                       AND question.stq_show IN (' . sb_get_workflow_demo_statuses() . ')
                       ' . $elems_fields_where_sql . '
                       AND question.stq_id NOT IN ('.implode(',', $in_str).') ' .
                        ($elems_fields_sort_sql != '' ? ' ORDER BY ' . substr($elems_fields_sort_sql, 1) : ' ORDER BY categs.cat_left ').'
                       LIMIT '.$limit.')';
                }
                if(count($sql_query) == 1)
                {
                    $str = $sql_query[0];
                    $str = ltrim(rtrim($str, ')'), '(');
                    $sql_query = $str;
                }
                else
                {
                    $sql_query = implode(' UNION ', $sql_query);
                }
            }
            $res = sql_param_query($sql_query);
        }
        else
        {

            $res = sql_param_query('SELECT question.stq_id, question.stq_question, question.stq_type, elems.link_cat_id '.$users_fields_select_sql.'
                       FROM sb_tester_questions question, sb_catlinks elems, sb_categs c
                       WHERE elems.link_cat_id=c.cat_id
                       AND c.cat_id = ?d
					   AND c.cat_rubrik = 1
                       AND question.stq_id=elems.link_el_id
                       AND question.stq_show IN (' . sb_get_workflow_demo_statuses() . ')
                       AND question.stq_id NOT IN (?a) ' . $elems_fields_where_sql . ' ' .
            ($elems_fields_sort_sql != '' ? ' ORDER BY ' . substr($elems_fields_sort_sql, 1) : ' '), $first_cat, $in_str);
        }

        $users_fields_count = count(explode(',', $users_fields_select_sql)) - 1;
        $users_fields_count = $users_fields_count > 0 ? $users_fields_count : 0;

        // Вывод нескольких вопросов на странице
        $q_count = $test_num_questions_per_page;

        if ($res)
        {
            if (count($res) < $q_count)
            {
                $q_count = count($res);
            }
        }
        else
        {
            $show_result = true;
        }

        if (!$show_result)
        {
            $begin_time = time();
            $num_numbers = strlen($begin_time);
            $sum_numbers = 0;

            for ($i = 0; $i < $num_numbers; $i++)
            {
                $number = substr($begin_time, $i, 1);
                $sum_numbers += $number;
            }
            $sum_numbers = md5('%' . $sum_numbers . '%');

            $dop_fields = '<input type="hidden" value="' . $begin_time . '" name="sb_tester_time"><input type="hidden" value="' . $sum_numbers . '" name="sb_tester_hash"><input type="hidden" value="' . (isset($sub_cat_id)? $sub_cat_id : ''). '" name="sb_tester_cat_id">';

            if ($test_test_time == -1)
            {
                $test_test_time = $stt_system_message['no_limit_text'];
            }
            if ($test_answer_time == -1)
            {
                $test_answer_time = $stt_system_message['no_limit_text'];
            }
            if ($test_retest_num == -1)
            {
                $test_retest_num = $stt_system_message['no_limit_text'];
            }
            if ($test_retest_time == -1)
            {
                $test_retest_time = $stt_system_message['no_limit_text'];
            }

            $result = '';
            $tags = array_merge(
                array('{DOP_FIELDS}', '{TEST_TITLE}', '{TEST_DESCR}', '{TEST_TEST_TIME}', '{TEST_ANSWER_TIME}', '{TEST_REST_TIME}', '{TEST_RETEST_TIME}'),
                $cat_tags
            );

            $values = array_merge(
                array($dop_fields, $test_title, $cat_fields['descr'], $test_test_time, $test_answer_time, $test_rest_time, $test_retest_time),
                $cat_values
            );

            $result .= str_replace($tags, $values, $stt_top);


            $stq_ids = array();
            for ($k = 0; $k < $q_count; $k++)
            {
                if($num_questions == $test_num_questions)
                {
                    break;
                }

                list($stq_id, $stq_question, $stq_type, $sub_cat_id) = $res[$k];

                //вытаскиваем значения пользовательских полей
                $user_values = array();
                if($users_fields_count > 0)
                {
                    $user_values = array();
                    $field_count = count($res[$k]);

                    for($counter = 0; $counter < $users_fields_count; $counter++)
                    {
                        $user_values[] = $res[$k][$field_count-$counter-1];
                    }
                    $user_values = array_reverse($user_values);
                    $allow_bb = 0;
                    if ((isset($params['allow_bbcode']) && $params['allow_bbcode'] == 1) || !isset($params['allow_bbcode']))
                        $allow_bb = 1;
                    $user_values = sbLayout::parsePluginFields($users_fields, $user_values, $stt_fields_temps, array(), array(), 'ru', '', '', $allow_bb);
                }

                $stq_ids[] = $stq_id;


                //вывод вопроса
                if ($stq_type == 'radio')
                {
                    $stt_answer_element = $stt_system_message['radio_input'];
                }
                elseif ($stq_type == 'checkbox')
                {
                    $stt_answer_element = $stt_system_message['checkbox_input'];
                }
                elseif ($stq_type == 'sorting')
                {
                    $stt_answer_element = $stt_system_message['sorting_input'];
                }
                elseif ($stq_type == 'inline')
                {
                    $stt_answer_element = $stt_system_message['inline_input'];
                }

                $res_answer = sql_param_query('SELECT stw_id, stw_answer, stw_mark, stw_any_answer FROM sb_tester_answers WHERE stw_quest_id=?d AND stw_is_delete=0 ORDER BY '.($stq_type == 'sorting' ? 'RAND()': 'stw_order'), $stq_id);
                $num = count($res_answer);

                if (!$res_answer || $num == 0)
                {
                    $GLOBALS['sbCache']->save('pl_tester', KERNEL_PROG_PL_TESTER_ERROR_MSG_NO_ANSWER);
                    return;
                }

                // Дата последнего изменения
                if ((sb_strpos($stt_quest, '{CHANGE_DATE}') !== false) || (sb_strpos($stt_bottom, '{CHANGE_DATE}') !== false))
                {
                    $res1 = sql_query('SELECT change_date FROM sb_catchanges WHERE el_id = ? AND cat_ident = ? ORDER BY change_date DESC LIMIT 0, 1', $stq_id, 'pl_tester');
                    $change_date = isset($res1[0][0]) ? sb_parse_date($res1[0][0], $stt_fields_temps['change_date'], $stt_lang) : ''; //   CHANGE_DATE
                }
                else
                {
                    $change_date = '';
                }

                $result .= str_replace(array('{TEST_QUESTION}', '{TEST_QUEST_NUMBER}', '{TEST_QUEST_ALL_NUMBER}', '{CHANGE_DATE}'), array($stq_question, ++$num_questions, $test_num_questions, $change_date), $stt_quest);
                $result = str_replace($user_tags, $user_values, $result);

                $answers_text = '';
                for ($i = 0; $i < $num; $i++)
                {
                    list($stw_id, $stw_answer, $stw_mark, $stw_any_answer) = $res_answer[$i];

                    if (1 == $stw_any_answer)
                    {
                        $stt_answer_element_tmp = $stt_answer_element . $stt_system_message['free_input'];
                    }
                    else
                    {
                        $stt_answer_element_tmp = $stt_answer_element;
                    }

                    $element_text = str_replace(array('{ANSWER_ID}', '{QUEST_ID}'), array($stw_id, $stq_id), $stt_answer_element_tmp);

                    if ($i % 2 == 0)
                    {
                        $answers_text .= str_replace(array('{INPUT_ELEMENT}', '{ANSWER}', '{MARK}'), array($element_text, $stw_answer, $stw_mark), $stt_nechet_answer);
                    }
                    else
                    {
                        $answers_text .= str_replace(array('{INPUT_ELEMENT}', '{ANSWER}', '{MARK}'), array($element_text, $stw_answer, $stw_mark), $stt_chet_answer);
                    }
                }

                if ($i % 2 != 0 && $stt_always_chet == 1)
                {
                    $answers_text .= ' ' . $stt_empty_element;
                }

                $result .= $answers_text;
            }

            $action = $action . '?sb_q_id=' . implode(',', $stq_ids) . ($query_string != '' ? '&' . $query_string : '');
            $result = sb_str_replace('{FORM_ACTION}', $action, $result);

            $tags = array_merge(
                array('{FORM_ACTION}', '{TEST_TITLE}', '{TEST_DESCR}', '{TEST_QUESTION}', '{TEST_TEST_TIME}', '{TEST_ANSWER_TIME}', '{TEST_REST_TIME}', '{TEST_RETEST_TIME}', '{TEST_QUEST_NUMBER}', '{TEST_QUEST_ALL_NUMBER}'),
                $cat_tags
            );

            $values = array_merge(
                array($_SERVER['PHP_SELF'], $test_title, $cat_fields['descr'], $stq_question, $test_test_time, $test_answer_time, $test_time, $test_retest_time, ++$num_questions, $test_num_questions),
                $cat_values
            );

            $result .= str_replace($tags, $values, $stt_bottom);

            $result = preg_replace('/\{[_A-Z0-9' . $GLOBALS['sb_reg_upper_interval'] . ']+\}/' . SB_PREG_MOD, '', $result);
            $GLOBALS['sbCache']->save('pl_tester', $result);
        }
    }

    if ($show_result)
    {
        // записываем результат в базу
        $res = sql_param_query('SELECT SUM(sttr_mark), SUM(sttr_answer_time) FROM sb_tester_temp_results WHERE sttr_test_id=?d AND sttr_user_id=?d GROUP BY sttr_test_id, sttr_user_id', $first_cat, $user_id);

        if ($res)
        {
            list($str_mark, $str_test_time) = $res[0];
        }
        else
        {
            $str_mark = 0;
            $str_test_time = 0;
        }
        // если пользователь зарегистрированный, то записываем результат
        if (isset($_SESSION['sbAuth']))
        {
            sql_param_query('DELETE FROM sb_tester_results WHERE str_test_id=?d AND str_user_id=?d', $first_cat, $user_id);

            $row = array();
            $row['str_test_id'] = $first_cat;
            $row['str_user_id'] = $user_id;
            $row['str_time'] = time();
            $row['str_mark'] = $str_mark;
            $row['str_test_time'] = $str_test_time;
            $row['str_ip'] = $_SERVER['REMOTE_ADDR'];
            $row['str_num_attempts'] = ($retest_num + 1);

            $res_temps = sql_param_query('SELECT sttr_quest_id, sttr_answer_ids, sttr_time, sttr_answer_time, sttr_mark, sttr_cat_id FROM sb_tester_temp_results WHERE sttr_test_id = ?d AND sttr_user_id= ?d', $first_cat, $user_id);
            if(isset($cat_fields['criteria_persent']) && $cat_fields['criteria_persent'] == 1)
            {
                //Расчитываем процент набранных баллов от максимально возможных в заданных вопросах
                $row['str_mark'] = fTester_Calc_Mark_Persent($res_temps, $str_mark);
                $str_mark = $row['str_mark']; //Меняем, чтобы правильно отобразить результат теста (итоги в процентах)
                $row['str_passed'] = ($row['str_mark'] >= $cat_fields['spin_criteria'] ? 1 : 0);
            }
            else
            {
                $row['str_passed'] = ($str_mark >= $cat_fields['spin_criteria'] ? 1 : 0);
            }

            sql_param_query('INSERT INTO sb_tester_results SET ?a', $row);

            $res_user = sql_param_query('SELECT su_login, su_name FROM sb_site_users WHERE su_id = ?d', $_SESSION['sbAuth']->getUserId());
            list($user_login, $user_fio) = $res_user[0];

            // записываем детальный результат теста для пользователя

            $quest_ids = array();
            if ($res_temps)
            {
                for ($i = 0; $i < count($res_temps); $i++)
                {
                    list($sttr_quest_id, $sttr_answer_ids, $sttr_time, $sttr_answer_time, $sttr_mark, $sttr_cat_id) = $res_temps[$i];

                    $quest_ids[] = $sttr_quest_id;
                    $row = array();
                    $row['star_user_id'] = $user_id;
                    $row['star_test_id'] = $first_cat;
                    $row['star_attempt_id'] = ($retest_num + 1);
                    $row['star_quest_id'] = $sttr_quest_id;
                    $row['star_answer_ids'] = $sttr_answer_ids;
                    $row['star_time'] = $sttr_time;
                    $row['star_answer_time'] = $sttr_answer_time;
                    $row['star_mark'] = $sttr_mark;
                    $row['star_cat_id'] = $sttr_cat_id;

                    sql_param_query('INSERT INTO sb_tester_answers_results SET ?a', $row);
                    //удаляем старые результаты
                    sql_param_query('DELETE FROM sb_tester_answers_results WHERE star_user_id=?d AND star_test_id=?d AND star_attempt_id < ?d', $row['star_user_id'], $row['star_test_id'], $row['star_attempt_id']);
                }
            }

            // сохраняем произвольные ответы
            if (!empty($quest_ids))
            {
                $res_any_temp = sql_param_query('SELECT sttaa_user_id, sttaa_quest_id, sttaa_answer_id, sttaa_answer_text FROM sb_tester_temp_any_answer WHERE sttaa_quest_id IN (?a) AND sttaa_user_id=?d', $quest_ids, $user_id);

                if ($res_any_temp)
                {
                    foreach ($res_any_temp as $row)
                    {
                        $row_any = array();
                        list($row_any['staa_user_id'], $row_any['staa_quest_id'], $row_any['staa_answer_id'], $row_any['staa_answer_text']) = $row;

                        sql_param_query('REPLACE INTO sb_tester_any_answers SET ?a', $row_any);
                    }
                }
            }
        }
        else
        {
            $user_login = '';
            $user_fio = '';
        }

        // чистим темповые таблицы
        sql_param_query('DELETE FROM sb_tester_temp_results WHERE sttr_test_id=?d AND sttr_user_id=?d', $first_cat, $user_id);
        sql_param_query('DELETE FROM sb_tester_temp_interrupts WHERE stti_test_id=?d AND stti_user_id= ?d', $first_cat, $user_id);
        sql_param_query('DELETE FROM sb_tester_temp_any_answer WHERE sttaa_user_id=?d', $user_id);
        unset($_SESSION['sb_tester_' . $first_cat . '_' . $user_id .'_start_test']);

        $res_text = sql_param_query('SELECT stmr_result FROM sb_tester_marks_results WHERE stmr_start <= ?d  AND stmr_end > ?d AND stmr_test_id=?d ORDER BY stmr_order;', $str_mark, $str_mark, $first_cat);
        if ($res_text)
        {
            list($result_text) = $res_text[0];
        }
        else
        {
            $result_text = '';
        }

        if ($test_test_time == -1)
        {
            $test_test_time = $stt_system_message['no_limit_text'];
        }
        if ($test_answer_time == -1)
        {
            $test_answer_time = $stt_system_message['no_limit_text'];
        }
        if ($test_retest_num == -1)
        {
            $test_retest_num = $stt_system_message['no_limit_text'];
        }
        if ($test_retest_time == -1)
        {
            $test_retest_time = $stt_system_message['no_limit_text'];
        }
        $_SESSION['sb_tester_' . $first_cat . '_refresh_count'] = 0;

        // вывод результатов
        $cur_date = sb_parse_date($cur_time, $stt_date, $stt_lang);
        //отправляем письмо куратору
        if ($test_email != '')
        {
            $test_email_subj = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}'), array($test_title, $cat_fields['descr'], $stt_system_message['end_time_answer'], $str_mark, $str_test_time, $user_login, $user_fio), $stt_system_message['letter_theme']);
            $test_email_body = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}'), array($test_title, $cat_fields['descr'], $stt_system_message['end_time_answer'], $str_mark, $str_test_time, $user_login, $user_fio), $stt_system_message['letter_body']);

            //чистим код от инъекций
            $test_email_subj = sb_clean_string($test_email_subj);

            ob_start();
            eval(' ?>' . $test_email_subj . '<?php ');
            $test_email_subj = trim(ob_get_clean());

            //чистим код от инъекций
            $test_email_body = sb_clean_string($test_email_body);

            ob_start();
            eval(' ?>' . $test_email_body . '<?php ');
            $test_email_body = trim(ob_get_clean());

            if ($test_email_subj != '' && $test_email_body != '')
            {
                include_once(SB_CMS_LIB_PATH . '/sbMail.inc.php');

                $mailer = new sbMail();
                $mailer->setSubject($test_email_subj);
                $mailer->setHtml($test_email_body);
                $mailer->send(array($test_email));
            }
        }

        $result = str_replace(array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TEST_TIME}', '{TEST_ANSWER_TIME}', '{TEST_TIME}', '{TEST_RETEST_TIME}', '{TEST_CUR_DATE}', '{TEST_ID}'), array($test_title, $cat_fields['descr'], $result_text, $str_mark, $test_test_time, $test_answer_time, $str_test_time, $test_retest_time, $cur_date, $first_cat), $stt_result);

        $GLOBALS['sbCache']->save('pl_tester', $result);
    }
}


function fTester_Elem_Rating ($el_id, $temp_id, $params, $tag_id)
{
    if ($GLOBALS['sbCache']->check('pl_tester', $tag_id, array($el_id, $temp_id, $params)))
        return;

    $params = unserialize(stripslashes($params));
    $cat_ids = array();

    if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['tester_scid']) || isset($_GET['tester_cid'])))
    {
        // используется связь с выводом разделов и выводить следует новости из соотв. раздела
        if (isset($_GET['tester_cid']))
        {
        	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['tester_cid']);
        	if ($res[0][0] > 0)
            	$cat_ids[] = intval($_GET['tester_cid']);
        }
        else
        {
            $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_url=?', $_GET['tester_scid']);
            if ($res)
            {
                $cat_ids[] = $res[0][0];
            }
            else
            {
            	$res = sql_param_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d', $_GET['tester_scid']);
	        	if ($res[0][0] > 0)
	                $cat_ids[] = intval($_GET['tester_scid']);
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
							AND c.cat_ident="pl_tester"
							AND c2.cat_ident = "pl_tester"
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
        	if (isset($params['rubrikator']) && $params['rubrikator'] == 1 && (isset($_GET['tester_scid']) || isset($_GET['tester_cid'])))
        	{
        		sb_404();
        	}

            // указанные разделы были удалены
            $GLOBALS['sbCache']->save('pl_tester', '');
            return;
        }
	}

	$check_cat_ids = count($cat_ids);

    // Есть ли закрытые разделы среди тех, которые надо выводить
    if($params['user_rights'] == 1)
    {
	    $res = sql_param_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
	    if ($res)
	    {
	        // проверяем права на закрытые разделы и исключаем их из вывода
	        $closed_ids = array();
	        foreach ($res as $value)
	        {
	            $closed_ids[] = $value[0];
	        }
	        $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_tester_read');
	    }
    }

    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT strt_pagelist_id, strt_perpage, strt_top, strt_elem, strt_delim,
    //                        strt_empty, strt_bottom, strt_count, strt_categs_temps, strt_system_message
    //                        FROM sb_tester_rating_temps WHERE strt_id=?d ', $temp_id);
    $res = sbQueryCache::getTemplate('sb_tester_rating_temps', $temp_id);
    if($res)
    {
        list($strt_pagelist_id, $strt_perpage, $strt_top, $strt_elem, $strt_delim, $strt_empty, $strt_bottom, $strt_count, $strt_categs_temps,
                $strt_system_message) = $res[0];

        $strt_system_message = unserialize(stripslashes($strt_system_message));
        $strt_categs_temps = unserialize(stripslashes($strt_categs_temps));
    }
    else
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_tester', '');
        return;
    }

    if (count($cat_ids) == 0 && $check_cat_ids > 0)
    {
        // У вас нет прав доступа к выбранным разделам
        $GLOBALS['sbCache']->save('pl_tester', $strt_system_message['dostup_zapr']);
        return;
    }

    $elems_fields_where_sql = '';
    if ($params['filter'] == 'date')
    {
        $from = sb_datetoint($params['filter_date_from']);
        $to = sb_datetoint($params['filter_date_to']);

        $elems_fields_where_sql .= ' AND tr.str_time >= '.$from.' AND tr.str_time <= '.$to;
    }
    elseif ($params['filter'] == 'mark')
    {
        $from = intval($params['filter_mark_from']);
        $to = intval($params['filter_mark_to']);

        $elems_fields_where_sql .= ' AND tr.str_mark >= '.$from.' AND tr.str_mark <= '.$to;
    }

    $elems_fields_sort_sql = '';
    if(!isset($_GET['sb_tester_sort']))
    {
	    // формируем SQL-запрос для сортировки
	    if (isset($params['sort1']) && $params['sort1'] != '')
	    {
		    if ($params['sort1'] == 'RAND()')
	    	{
	    		$GLOBALS['sbCache']->mCacheOff = true;
	    	}

	        if (isset($params['order1']) && $params['order1'] != '')
	        {
	            $elems_fields_sort_sql .= ', tr.'.$params['sort1'].' '.$params['order1'];
	        }
	    }

	    if (isset($params['sort2']) && $params['sort2'] != '')
	    {
		    if ($params['sort2'] == 'RAND()')
	    	{
	    		$GLOBALS['sbCache']->mCacheOff = true;
	    	}

	        if (isset($params['order2']) && $params['order2'] != '')
	        {
	            $elems_fields_sort_sql .= ', tr.'.$params['sort2'].' '.$params['order2'];
	        }
	    }

	    if (isset($params['sort3']) && $params['sort3'] != '')
	    {
		    if ($params['sort3'] == 'RAND()')
	    	{
	    		$GLOBALS['sbCache']->mCacheOff = true;
	    	}

	        if (isset($params['order3']) && $params['order3'] != '')
	        {
	            $elems_fields_sort_sql .= ', tr.'.$params['sort3'].' '.$params['order3'];
	        }
	    }
    }

    if (isset($_GET['sb_tester_sort']))
    {
    	$sort = explode('_', $_GET['sb_tester_sort']);
    	if ($sort[0] == 'login')
        {
            $elems_fields_sort_sql .= ', u.su_login '.$sort[1];
        }
        elseif ($sort[0] == 'fio')
        {
            $elems_fields_sort_sql .= ', u.su_name '.$sort[1];
        }
        elseif ($sort[0] == 'mark')
        {
            $elems_fields_sort_sql .= ', s_mark '.$sort[1];
        }
        elseif ($sort[0] == 'time')
        {
            $elems_fields_sort_sql .= ', s_test_time '.$sort[1];
        }
        elseif ($sort[0] == 'tested')
        {
            $elems_fields_sort_sql .= ', tested '.$sort[1];
        }
        elseif ($sort[0] == 'passed')
        {
            $elems_fields_sort_sql .= ', tests.passed '.$sort[1];
        }
    }

    if($params['passed_tests'])
    {
        $elems_fields_where_sql .= ' AND str_passed = 1';
    }

    if(isset($_GET['sb_tester_fio']) && $_GET['sb_tester_fio'] != '')
    {
    	$elems_fields_where_sql .= ' AND u.su_name LIKE "%'.$_GET['sb_tester_fio'].'%"';
    }

    // вытаскиваем макет дизайна постраничного вывода
    $res = sbQueryCache::getTemplate('sb_pager_temps', $strt_pagelist_id);

    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

    @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
    $pager = new sbDBPager($tag_id, $pt_perstage, $strt_perpage);

    $rating_total = true;
    // вытаскиваем пользователей, сдавших тест, с учетом пейджирования
    $res = $pager->init($rating_total, 'SELECT tr.str_test_id, tr.str_user_id, sum(tr.str_mark) AS s_mark, sum(tr.str_test_time) AS s_test_time, u.su_login, u.su_name, COUNT(DISTINCT(tr.str_test_id)) AS tested, tests.passed
                        FROM sb_tester_results tr LEFT JOIN
						(
							SELECT str_user_id, COUNT(str_test_id) AS passed FROM sb_tester_results WHERE str_passed=1
							GROUP BY str_user_id
						) tests
						ON tests.str_user_id = tr.str_user_id LEFT JOIN sb_site_users u ON tr.str_user_id = u.su_id
						WHERE tr.str_test_id IN (?a)
						'.$elems_fields_where_sql.'
						GROUP BY tr.str_user_id'.
						($elems_fields_sort_sql != '' ? ' ORDER BY '.substr($elems_fields_sort_sql, 1) : ' ORDER BY s_mark DESC, s_test_time DESC'), $cat_ids);

	$ar_user_id = array();
	if($res)
    {
        foreach ($res as $key => $value)
        {
            $ar_user_id[] = $value[1];
        }
	}
	else
	{
		// указанные разделы были удалены
		$GLOBALS['sbCache']->save('pl_tester', $strt_system_message['no_rating']);
		return;
	}

	sbProgStartSession();

    //  определяем место в рейтинге и записываем в базу для дальнейшего извлечения при пейджировании
    if(!isset($_GET['page_2']) && !isset($_GET['sb_tester_sort']))
    {
		$res_place = sql_param_query('SELECT tr.str_test_id, tr.str_user_id, sum(tr.str_mark) AS s_mark, sum(tr.str_test_time) AS s_test_time, u.su_login, u.su_name, COUNT(DISTINCT(tr.str_test_id)) AS tested, tests.passed
							FROM sb_tester_results tr LEFT JOIN
	                        (
	                            SELECT str_user_id, COUNT(str_test_id) AS passed FROM sb_tester_results WHERE str_passed=1 AND str_test_id IN (?a)
	                            GROUP BY str_user_id
	                        ) tests

	                        ON tests.str_user_id = tr.str_user_id LEFT JOIN sb_site_users u ON tr.str_user_id = u.su_id
	                        WHERE tr.str_test_id IN (?a)
	                        '.$elems_fields_where_sql.'
	                        GROUP BY tr.str_user_id'.
	                        ($elems_fields_sort_sql != '' ? ' ORDER BY '.substr($elems_fields_sort_sql, 1) : ' ORDER BY s_mark DESC, s_test_time DESC'), $cat_ids, $cat_ids);

        $count_ = count($res_place);
        for($i = 0; $i < $count_; $i++)
        {
            $_SESSION['rating_place'][$res_place[$i][1]] = ($i + 1);
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

    $query_string = '';
    foreach ($_GET as $key => $value)
    {
        if ($key != 'page_2' &&  $key != 'sb_tester_sort' && $key != 'sb_tester_fio')
        {
        	if (!is_array($value))
            {
                $query_string .= $key.'='.urlencode($value).'&';
            }
            else
            {
                foreach ($value as $key2 => $value2)
                {
                   $query_string .= $key.'[]'.'='.urlencode($value2).'&';
                }
            }
        }
    }

    $sort_query_string = $query_string.($query_string == '' ? '' : '&')
                         .(isset($_GET['page_2']) ? 'page_2='.$_GET['page_2'] : '')
                         .(isset($_GET['sb_tester_fio']) ? '&sb_tester_fio='.$_GET['sb_tester_fio'] : '');

    $action = $_SERVER['PHP_SELF'].'?'.$query_string;
    $sort_action = $_SERVER['PHP_SELF'].'?'.$sort_query_string;

    $link_sort_login_asc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=login_asc' : '&sb_tester_sort=login_asc');
    $link_sort_login_desc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=login_desc' : '&sb_tester_sort=login_desc');
    $link_sort_fio_asc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=fio_asc' : '&sb_tester_sort=fio_asc');
    $link_sort_fio_desc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=fio_desc' : '&sb_tester_sort=fio_desc');
    $link_sort_mark_asc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=mark_asc' : '&sb_tester_sort=mark_asc');
    $link_sort_mark_desc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=mark_desc' : '&sb_tester_sort=mark_desc');
    $link_sort_time_asc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=time_asc' : '&sb_tester_sort=time_asc');
    $link_sort_time_desc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=time_desc' : '&sb_tester_sort=time_desc');
    $link_sort_count_tested_asc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=tested_asc' : '&sb_tester_sort=tested_asc');
    $link_sort_count_tested_desc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=tested_desc' : '&sb_tester_sort=tested_desc');
    $link_sort_count_passed_asc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=passed_asc' : '&sb_tester_sort=passed_asc');
    $link_sort_count_passed_desc = $sort_action.($sort_query_string == '' ? 'sb_tester_sort=passed_desc' : '&sb_tester_sort=passed_desc');

    // верх вывода
    $result = str_replace(array('{NUM_LIST}', '{ALL_COUNT}', '{FORM_ACTION}', '{SORT_LOGIN_ASC}', '{SORT_LOGIN_DESC}', '{SORT_FIO_ASC}', '{SORT_FIO_DESC}', '{SORT_MARK_ASC}', '{SORT_MARK_DESC}', '{SORT_TIME_ASC}', '{SORT_TIME_DESC}', '{SORT_COUNT_TESTED_ASC}', '{SORT_COUNT_TESTED_DESC}', '{SORT_COUNT_PASSED_ASC}', '{SORT_COUNT_PASSED_DESC}'),
                     array($pt_page_list, $rating_total, $action, $link_sort_login_asc, $link_sort_login_desc, $link_sort_fio_asc, $link_sort_fio_desc, $link_sort_mark_asc, $link_sort_mark_desc, $link_sort_time_asc, $link_sort_time_desc, $link_sort_count_tested_asc, $link_sort_count_tested_desc, $link_sort_count_passed_asc, $link_sort_count_passed_desc), $strt_top);

    $tags = array('{USER_LOGIN}',
                  '{USER_FIO}',
                  '{RATING_PLACE}',
                  '{RATING_MARK}',
                  '{RATING_TIME}',
                  '{RATING_TESTED}',
                  '{RATING_PASSED}');
    $i = 0;
    $col = 0;
    foreach ($res as $value)
    {

    	$values = array();
        $values[] = $value[4];     //    USER_LOGIN
        $values[] = $value[5];     //    USER_FIO
        $values[] = $_SESSION['rating_place'][$value[1]];

        $values[] = $value[2];    // RATING_MARK
        $values[] = $value[3];    // RATING_TIME
        $values[] = $value[6];    // RATING_TESTED
        $values[] = (isset($value[7]) ? $value[7] : 0 );  // RATING_PASSED

        $result .= str_replace($tags, $values, $strt_elem);
        $col++;

        if ($col >= $strt_count)
        {
            $result .= $strt_delim;
            $col = 0;
        }
        $i++;
    }

    while ($col < $strt_count)
    {
        $result .= $strt_empty;
        $col++;
    }

    // низ вывода
    $result .= str_replace(array('{NUM_LIST}', '{ALL_COUNT}','{FORM_ACTION}', '{SORT_LOGIN_ASC}', '{SORT_LOGIN_DESC}', '{SORT_FIO_ASC}', '{SORT_FIO_DESC}', '{SORT_MARK_ASC}', '{SORT_MARK_DESC}', '{SORT_TIME_ASC}', '{SORT_TIME_DESC}', '{SORT_COUNT_TESTED_ASC}', '{SORT_COUNT_TESTED_DESC}', '{SORT_COUNT_PASSED_ASC}', '{SORT_COUNT_PASSED_DESC}'),
                     array($pt_page_list, $rating_total, $action, $link_sort_login_asc, $link_sort_login_desc, $link_sort_fio_asc, $link_sort_fio_desc, $link_sort_mark_asc, $link_sort_mark_desc, $link_sort_time_asc, $link_sort_time_desc, $link_sort_count_tested_asc, $link_sort_count_tested_desc, $link_sort_count_passed_asc, $link_sort_count_passed_desc), $strt_bottom);

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save('pl_tester', $result);
}


function fTester_Elem_Results ($el_id, $temp_id, $params, $tag_id)
{
	if ($GLOBALS['sbCache']->check('pl_tester', $tag_id, array($el_id, $temp_id, $params)))
        return;

	$params = unserialize(stripslashes($params));
    // вытаскиваем макет дизайна
    //$res = sql_param_query('SELECT strt_lang, strt_date, strt_top, strt_result, strt_bottom, strt_empty, strt_delim, strt_count,
    //                        strt_perpage, strt_pagelist_id, strt_system_message
    //                        FROM sb_tester_result_temps WHERE strt_id=?d ', $temp_id);
    $res = sbQueryCache::getTemplate('sb_tester_result_temps', $temp_id);

    if (!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_NEWS_PLUGIN), SB_MSG_WARNING);
        $GLOBALS['sbCache']->save('pl_tester', '');
        return;
    }

    if($res)
    {
        list($strt_lang, $strt_date, $strt_top, $strt_result, $strt_bottom, $strt_empty, $strt_delim, $strt_count,
                            $strt_perpage, $strt_pagelist_id, $strt_system_message) = $res[0];

        $strt_system_message = unserialize($strt_system_message);
    }

    // зарегистрированный пользователь или нет
    if (isset($_SESSION['sbAuth'])  && $_SESSION['sbAuth']->getUserId() != -1 && $_SESSION['sbAuth']->getUserId() != 0)
    {
        $user_id = $_SESSION['sbAuth']->getUserId();
    }
    else
    {
        $GLOBALS['sbCache']->save('pl_tester', $strt_system_message['neobxod_avtoriz']);
        return;
    }

    // вытаскиваем макет дизайна постраничного вывода
    $res = sbQueryCache::getTemplate('sb_pager_temps', $strt_pagelist_id);

    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

    // формируем SQL-запрос для флажков, которые необходимо учитывать при выводе
    $elems_fields_where_sql = '';

    if ($params['filter'] == 'date')
    {
        $from = sb_datetoint($params['filter_date_from']);
        $to = sb_datetoint($params['filter_date_to']);

        $elems_fields_where_sql .= ' AND str_time >= '.$from.' AND str_time <= '.$to;
    }

    // формируем SQL-запрос для сортировки
    $elems_fields_sort_sql = '';
    if (isset($params['sort1']) && $params['sort1'] != '')
    {
    	if ($params['sort1'] == 'RAND()')
    	{
    		$GLOBALS['sbCache']->mCacheOff = true;
    	}

        if (isset($params['order1']) && $params['order1'] != '')
        {
            $elems_fields_sort_sql .= ', '.$params['sort1'].' '.$params['order1'];
        }
    }

    if (isset($params['sort2']) && $params['sort2'] != '')
    {
    	if ($params['sort2'] == 'RAND()')
    	{
    		$GLOBALS['sbCache']->mCacheOff = true;
    	}

    	if (isset($params['order2']) && $params['order2'] != '')
        {
            $elems_fields_sort_sql .= ', '.$params['sort2'].' '.$params['order2'];
        }
    }

    if (isset($params['sort3']) && $params['sort3'] != '')
    {
    	if ($params['sort3'] == 'RAND()')
    	{
    		$GLOBALS['sbCache']->mCacheOff = true;
    	}

        if (isset($params['order3']) && $params['order3'] != '')
        {
            $elems_fields_sort_sql .= ', '.$params['sort3'].' '.$params['order3'];
        }
    }
    @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

    $pager = new sbDBPager($tag_id, $pt_perstage, $strt_perpage);
    $result_total = true;
    $res = $pager->init($result_total, 'SELECT distinct(str_test_id), max(str_mark) as max_mark, min(str_test_time) as min_time, str_id, str_time, str_ip, str_certificat
									FROM sb_tester_results
									WHERE str_user_id = ?d
									'.$elems_fields_where_sql.'
									GROUP BY str_test_id '.
                                    ($elems_fields_sort_sql != '' ? ' ORDER BY'.substr($elems_fields_sort_sql, 1) : ''), $user_id);
    if (!$res)
    {
        $GLOBALS['sbCache']->save('pl_tester', $strt_system_message['resultatov_net']);
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

    // верх вывода результатов
    $result = str_replace(array('{NUM_LIST}', '{ALL_COUNT}'), array($pt_page_list, $result_total), $strt_top);

    $tags = array();
    $tags = array_merge($tags, array('{TEST_TITLE}',
                                     '{TEST_DESC}',
                                     '{TEST_DATE}',
                                     '{TEST_RESULT_TEXT}',
                                     '{TEST_MARK}',
                                     '{TEST_TIME}',
                                     '{TEST_CERTIF}',
                                     '{TEST_PASSED}',
                                     '{RETEST_TIME}',
                                     '{USER_IP}'));

    $col = 0;
    foreach ($res as $value)
    {
        $categs = array();
        $res_cat = sql_param_query('SELECT cat_id, cat_title, cat_fields, cat_url
                                FROM sb_categs WHERE cat_id = ?d', $value[0]);
        if ($res_cat)
        {
            foreach ($res_cat as $val)
            {
                $categs[$val[0]] = array();
                $categs[$val[0]]['title'] = $val[1];
                $categs[$val[0]]['fields'] = unserialize($val[2]);
                $categs[$val[0]]['url'] = $val[3];
            }
        }
        $res_text = sql_param_query('SELECT stmr_result FROM sb_tester_marks_results WHERE stmr_start <= ?d  AND stmr_end > ?d AND stmr_test_id=?d ORDER BY stmr_order', $value[1], $value[1], $value[0]);

        if($res_text)
        {
        	list($result_text) = $res_text[0];
        }
        else
        {
            $result_text = '';
        }

        if ($categs[$value[0]]['fields']['spin_retest_time'] == -1)
        {
            $test_retest_time = $strt_system_message['retest'];
        }
        else
        {
            $passed_time = time() - $value[4];
            $passed_days = ceil($passed_time / (24 * 60 * 60));

            if ($passed_days >= $categs[$value[0]]['fields']['spin_retest_time'])
            {
                $test_retest_time = $strt_system_message['retest'];
            }
            else
            {
                $test_retest_time = str_replace('{TIME_TO_RETEST}', $categs[$value[0]]['fields']['spin_retest_time'] - $passed_days, $strt_system_message['no_retest']);
            }
        }


        if ($value[6] == 1)
        {
            $value[6] = $strt_system_message['certificat_vidan'];
        }
        else
        {
            $value[6] = $strt_system_message['certificat_ne_vidan'];
        }

        if ($value[1] >= $categs[$value[0]]['fields']['spin_criteria'])
        {
            $test_passed = $strt_system_message['test_sdan'];
        }
        else
        {
            $test_passed = $strt_system_message['test_ne_sdan'];
        }

        $values = array();
        $values[] = $categs[$value[0]]['title']; // TEST_TITLE
        $values[] = $categs[$value[0]]['fields']['descr']; // TEST_DESC
        $values[] = sb_parse_date($value[4], $strt_date, $strt_lang); // TEST_DATE
        $values[] = $result_text; // TEST_RESULT_TEXT
        $values[] = $value[1]; // TEST_MARK
        $values[] = $value[2]; // TEST_TIME
        $values[] = $value[6]; // TEST_CERTIF
        $values[] = $test_passed; // TEST_PASSED
        $values[] = $test_retest_time; // RETEST_TIME
        $values[] = $value[5]; // USER_IP

        if ($col >= $strt_count)
        {
        	$result .= $strt_delim;
            $col = 0;
        }

        $result .= str_replace($tags, $values, $strt_result);
        $col++;
    }

    while ($col < $strt_count)
    {
        $result .= $strt_empty;
        $col++;
    }

    // низ вывода результатов
    $result .= str_replace(array('{NUM_LIST}', '{ALL_COUNT}'), array($pt_page_list, $result_total), $strt_bottom);

    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
    $GLOBALS['sbCache']->save('pl_tester', $result);
}


function fTester_Elem_Categs($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    $num_sub = 0;
    fCategs_Show_Categs($temp_id, $params, $tag_id, 'pl_tester', 'pl_tester', 'tester', $num_sub);
}


function fTester_Elem_Selcat($el_id, $temp_id, $params, $tag_id)
{
    require_once SB_CMS_PL_PATH.'/pl_categs/prog/pl_categs.php';
    fCategs_Show_Sel_Cat($temp_id, $params, $tag_id, 'pl_tester', 'pl_tester', 'tester');
}

/**
 * Функция для расчета процента правильных ответов. За 100% принимается сумма положительных баллов
 * всех ответов на заданные вопросы
 *
 * @param array $tmp_results Массив с ответами пользователя
 * @param int $mark        Сумма набранных баллов за тест
 */
function fTester_Calc_Mark_Persent($tmp_results, $mark)
{
    $max_mark = 0;
    if (!empty($tmp_results))
    {
        $q_ids = array();
        foreach ($tmp_results as $row)
        {
            $q_ids[] = $row[0];
        }

        if (!empty($q_ids))
        {
            $res_mark = sql_param_query('SELECT SUM(stw_mark) FROM sb_tester_answers WHERE stw_quest_id in (?a) AND stw_mark > 0', $q_ids);
            if ($res_mark)
            {
                $max_mark = $res_mark[0][0];
            }
        }
    }
    return round($mark / $max_mark * 100);
}

?>