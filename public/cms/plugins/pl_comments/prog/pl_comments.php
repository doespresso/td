<?php
/**
 * Возвращает массив - ключом является идентификатор элемента, значением - кол-во комментариев
 *
 * @param array $ids Массив идентификаторов элементов, для которых следует получить кол-во комментариев.
 * @param string $pl_ident Идентификатор модуля.
 *
 * @return array Массив идентификаторов элементов и кол-ва комментариев для каждого из них.
 */
function fComments_Get_Count($ids, $pl_ident)
{
    $res = sql_query('SELECT c_el_id, COUNT(*) FROM sb_comments WHERE c_el_id IN (?a) AND c_plugin=? AND c_show = 1 GROUP BY c_el_id', $ids, $pl_ident);
    $count = array();

    if($res)
    {
        foreach($res as $value)
        {
            $count[$value[0]] = $value[1];
        }
    }

    return $count;
}

/**
 * Функция генерирует HTML-код вывода комментариев.
 *
 * @param int $temp_id ID-макета дизайна вывода комментариев.
 * @param string $pl_ident Идентификатор модуля.
 * @param int $el_id Идентификатор элемента, для которого выводится список комментариев.
 * @param bool $add_form Выводить форму добавления комментария (TRUE) или нет (FALSE).
 * @param string $params Сериализованный массив настроек компонента вывод последних комментариев
 * @param int $tag_id Уникальный идентификатор компонента "вывод последних комментариев"
 * @param boolean $rights Флаг. Есть права на просмотр комментариев (TRUE) или нет (FALSE)
 *
 *
 * @return string HTML-код списка комментариев.
 */
function fComments_Get_List($temp_id, $pl_ident = '', $el_id = 0, $add_form = true, $params = '', $tag_id = 0, $rights = true)
{
    $page = '';
    if($pl_ident == '')
    {
        if ($GLOBALS['sbCache']->check('pl_comments', $tag_id, array($el_id, $temp_id, $params)))
            return;

        if (isset($_REQUEST['page_'.$tag_id]))
        {
            $page = 'page_'.$tag_id.'='.intval($_REQUEST['page_'.$tag_id]);
        }

    }
    else
    {
        if (isset($_REQUEST['page_'.$el_id]))
        {
            $page = 'page_'.$el_id.'='.intval($_REQUEST['page_'.$el_id]);
        }
    }

    if(!is_array($params) && $params != '')
        $params = unserialize(stripslashes($params));

    //$res = sql_query('SELECT ctl_lang, ctl_perpage, ctl_pagelist_id, ctl_user_data_id, ctl_top, ctl_element, ctl_bottom, ctl_fields_temps, ctl_messages FROM sb_comments_temps_list WHERE ctl_id = ?d', $temp_id);
    $res = sbQueryCache::getTemplate('sb_comments_temps_list', $temp_id);
    if(!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_COMMENTS_PLUGIN), SB_MSG_WARNING);
        return '';
    }

    list($ctl_lang, $ctl_perpage, $ctl_pagelist_id, $ctl_user_data_id, $ctl_top, $ctl_element, $ctl_bottom, $ctl_fields_temps, $ctl_messages) = $res[0];

    if ($ctl_fields_temps != '')
        $ctl_fields_temps = unserialize($ctl_fields_temps);
    else
        $ctl_fields_temps = array();

    if ($ctl_messages != '')
        $ctl_messages = unserialize($ctl_messages);
    else
        $ctl_messages = array();

    if(!$rights)
    {
        return (isset($ctl_messages['rights_error']) ? $ctl_messages['rights_error'] : '');
    }

    // вытаскиваем макет дизайна постраничного вывода
    $res = sbQueryCache::getTemplate('sb_pager_temps', $ctl_pagelist_id);

    if ($res)
    {
        list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];
    }
    else
    {
        $pt_page_list = '';
        $pt_perstage = 1;
    }

    //  строка SQL-запроса для сортировки
    if((!isset($ctl_fields_temps['ctl_sort']) || $ctl_fields_temps['ctl_sort'] == 0) || $pl_ident == '')
    {
        $sort_sql = ' c.c_date DESC';
    }
    else
    {
        $sort_sql = ' c.c_date ASC';
    }

    if(isset($ctl_fields_temps['ctl_answer_top']) && trim($ctl_fields_temps['ctl_answer_top']) != '' &&
        isset($ctl_fields_temps['ctl_answer_bottom']) && trim($ctl_fields_temps['ctl_answer_bottom']) != '' &&
        $pl_ident != '')
    {
        $comments_as_tree = 1;
    }
    else
    {
        $comments_as_tree = 0;
    }

    if(intval($comments_as_tree) == 1)
    {
        $sort_sql = ' c.c_left ASC, '.$sort_sql;
    }
    $sort_sql = ' ORDER BY '.$sort_sql;

    //выводить комментарии только авторизованного пользователя
    $user_sql = '';
    if(isset($params['only_own_comments']) && $params['only_own_comments'] == 1)
    {
        $uid = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : -1;
        $user_sql = ' AND c.c_user_id = '.$uid;
    }

    //  выборка комментариев, которые следует выводить
    @require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

    $comments_total = true;
    if($pl_ident == '')
    {
        $pager = new sbDBPager('c_'.$tag_id, $pt_perstage, $ctl_perpage);
        if($params['filter'] == 'from_to')
        {
            $pager->mFrom = intval($params['filter_from']);
            $pager->mTo = intval($params['filter_to']);
        }

        $cat_ids = explode('^', $params['ids']);
        if(isset($params['sub_cats']) && $params['sub_cats'] == 1)
        {
            $res = sql_query('SELECT c.cat_id FROM sb_categs AS c, sb_categs AS c2
                            WHERE c2.cat_left <= c.cat_left
                            AND c2.cat_right >= c.cat_right
                            AND c.cat_ident = ?
                            AND c2.cat_ident = ?
                            AND c2.cat_id IN (?a)
                            ORDER BY c.cat_left', $params['pl_ident'], $params['pl_ident'], $cat_ids);

            $cat_ids = array();
            if($res)
            {
                foreach($res as $value)
                {
                    $cat_ids[] = $value[0];
                }
            }
            else
            {
//              разделы были удалены
                $GLOBALS['sbCache']->save('pl_comments', '');
                return;
            }
        }

        $tmp_ids = $cat_ids;
        if(!isset($params['close_cat']) || $params['close_cat'] == 0)
        {
            $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_closed=1 AND cat_id IN (?a)', $cat_ids);
            if ($res)
            {
                // проверяем права на закрытые разделы и исключаем их из вывода
                $closed_ids = array();
                foreach ($res as $value)
                {
                    $closed_ids[] = $value[0];
                }

                $cat_ids = sbAuth::checkRights($closed_ids, $cat_ids, 'pl_comments_read');
            }
        }

        if(count($cat_ids) == 0 && count($tmp_ids) > 0)
        {
            $GLOBALS['sbCache']->save('pl_comments', isset($ctl_messages['rights_error']) ? $ctl_messages['rights_error'] : '');
            return;
        }
        elseif(count($cat_ids) == 0)
        {
//          разделы были удалены
            $GLOBALS['sbCache']->save('pl_comments', '');
            return;
        }

        $hidden_str = '';
        if(!isset($params['hidden_cat']) || $params['hidden_cat'] == 0)
        {
            $hidden_str = ' AND cat.cat_rubrik = 1';
        }

        $res = $pager->init($comments_total, 'SELECT c.c_id, c.c_author, c.c_email, c.c_subj, c.c_text, c.c_date, c.c_file, c.c_user_id, c.c_ip, c.c_file_name, c.c_plugin, c.c_el_id, c.c_left, c.c_right, c.c_level
                    FROM sb_comments c, sb_catlinks l, sb_categs cat WHERE cat.cat_id = l.link_cat_id
                    AND c.c_plugin = ?
                    AND l.link_el_id = c.c_el_id AND cat.cat_id IN (?a) '.$hidden_str.' AND c.c_show = 1
                    '.$user_sql.$sort_sql, $params['pl_ident'], $cat_ids);
    }
    else
    {
        $pager = new sbDBPager('c_'.$el_id, $pt_perstage, $ctl_perpage);
        if($comments_as_tree == 1)
        {
//          если вывод в виде дерева, то не выводим комментарии-ответы если родительский комментарий не активен.
            //Вытаскиваем c_left и c_right неактивных элементов
            $noActiveIDs = array(0);
            $res_tmp = sql_param_query('SELECT c_left, c_right FROM sb_comments WHERE c_plugin = ? AND c_el_id = ?d AND c_show = 0', $pl_ident, $el_id);
            if($res_tmp)
            {
                foreach($res_tmp as $row)
                {
                    if($row[1] - $row[0] == 1)
                    {
                        //Нет смысла делать запрос, если нет дочерних элементов
                        continue;
                    }

                    $r1 = sql_param_query('SELECT c_id FROM sb_comments WHERE c_left>?d AND c_right<?d', $row[0], $row[1]);
                    if($r1)
                    {
                        foreach($r1 as $row1)
                        {
                            $noActiveIDs[] = $row1[0];
                        }
                    }
                }
                $noActiveIDs = array_unique($noActiveIDs);
            }
            $res = $pager->init($comments_total, 'SELECT c.c_id, c.c_author, c.c_email, c.c_subj, c.c_text, c.c_date, c.c_file, c.c_user_id, c.c_ip, c.c_file_name, "", "", c.c_left, c.c_right, c.c_level
                    FROM sb_comments c WHERE
                    c.c_id NOT IN (?a) AND
                    c.c_plugin = ? AND c.c_el_id = ?d AND c.c_show = 1 '.$user_sql.$sort_sql, $noActiveIDs, $pl_ident, $el_id);
        }
        else
        {
            $res = $pager->init($comments_total, 'SELECT c.c_id, c.c_author, c.c_email, c.c_subj, c.c_text, c.c_date, c.c_file, c.c_user_id, c.c_ip, c.c_file_name, "", "", c.c_left, c.c_right, c.c_level
                    FROM sb_comments c WHERE
                    c.c_plugin = ? AND c.c_el_id = ?d AND c.c_show = 1 '.$user_sql.$sort_sql, $pl_ident, $el_id);
        }
    }

    if(!$res)
    {
        if($pl_ident == '')
        {
            $GLOBALS['sbCache']->save('pl_comments', isset($ctl_messages['no_comments']) ? $ctl_messages['no_comments'] : '');
            return;
        }
        else
        {
            return isset($ctl_messages['no_comments']) ? $ctl_messages['no_comments'] : '';
        }
    }

    $count_comments = $pager->mFrom + 1;

    //  HTML-код формы комментариев
    if(sb_strpos($ctl_top, '{COMMENTS_FORM}') !== false || sb_strpos($ctl_bottom, '{COMMENTS_FORM}'))
    {
        $comments_form = fComments_Get_Form($temp_id, $pl_ident, $el_id, $add_form);
    }
    else
    {
        $comments_form = '';
    }

    $query_str = ($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
    foreach ($_GET as $key => $value)
    {
        if (preg_match('/^[A-Z0-9_\-]+_cid$/i', $key) || preg_match('/^[A-Z0-9_\-]+_id$/i', $key) && $key != 'c_answer_id' && $key != 'c_id' && $key != 'c_edit_id')
        {
            if ($query_str != '')
            {
                $query_str .= '&'.$key.'='.intval($value);
            }
            else
            {
                $query_str .= '?'.$key.'='.intval($value);
            }
        }
    }

    //  строим список номеров страниц
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

    // верх вывода
    $result = str_replace(array('{NUM_LIST}', '{ALL_COUNT}', '{COMMENTS_FORM}'), array($pt_page_list, $comments_total, $comments_form), $ctl_top);

    $tags = array('{COMMENTS_NUMBER}',
                  '{COMMENTS_ID}',
                  '{COMMENTS_DATE}',
                  '{COMMENTS_AUTHOR}',
                  '{COMMENTS_EMAIL}',
                  '{COMMENTS_SUBJ}',
                  '{COMMENTS_TEXT}',
                  '{COMMENTS_AUTHOR_IP}',
                  '{COMMENTS_FILE}',
                  '{COMMENTS_QUOTE_ACTION}',
                  '{COMMENTS_USER_ID}',
                  '{COMMENTS_USER_DATA}',
                  '{COMMENTS_HASH}',
                  '{COMMENTS_TO_ANSWER}',
                  '{COMMENTS_EDIT_LINK}',
                  '{ELEM_ID}',
                  '{ELEM_TITLE}',
                  '{ELEM_URL}',
                  '{CAT_ID}',
                  '{CAT_TITLE}',
                  '{CAT_URL}',
                  '{LINK}');

    $dop_tags = array('{AUTHOR}', '{EMAIL}', '{USER_ID}', '{ID}');
    $show_user_data = (sb_strpos($ctl_element, '{COMMENTS_USER_DATA}') !== false && $ctl_user_data_id > 0);

    $prev_level = 1;


    foreach ($res as $value)
    {
        list ($c_id, $c_author, $c_email, $c_subj, $c_text, $c_date, $c_file, $c_user_id, $c_ip, $c_file_name, $c_plugin, $c_el_id, $c_left, $c_right, $c_level) = $value;

        //для корректного формирования хеша при выводе последних комментариев
        $pl_ident_hash = $pl_ident != '' ? $pl_ident : (isset($params['pl_ident']) ? $params['pl_ident'] : '');
        $el_id_hash = isset($params[$c_plugin]) ? $c_el_id : $el_id;

        $hash = md5($pl_ident_hash.' - '.$el_id_hash);
        $quote_action = $GLOBALS['PHP_SELF'].($query_str != '' ? $query_str.(!isset($_GET['sb_search']) || $_GET['sb_search'] != 1 ? '&c_hash='.$hash : '') : (!isset($_GET['sb_search']) || $_GET['sb_search'] != 1 ? '?c_hash='.$hash : ''));

        $table_data = array();
        if(isset($params[$c_plugin]))
        {
            $table_data = explode('__', $params[$c_plugin]);
        }
        elseif (is_array($params))
        {
            $table_data = $params;
        }

        if (count($table_data) > 0)
        {
            $el_res = sql_query('SELECT ?#, ?#, ?#, c.cat_id, c.cat_title, c.cat_url
                        FROM ?# INNER JOIN sb_catlinks l ON l.link_el_id = ?# INNER JOIN sb_categs c ON c.cat_id = l.link_cat_id
                        AND c.cat_ident=?
                        WHERE ?# = ?d', $table_data[2], $table_data[3], $table_data[4], $table_data[1], $table_data[2],
                        ($pl_ident != '' ? $pl_ident : $params['pl_ident']),
                        $table_data[2], isset($params[$c_plugin]) ? $c_el_id : $el_id);
        }

        $dop_values = array();
        $dop_values[] = $c_author;
        $dop_values[] = $c_email;
        $dop_values[] = $c_user_id;
        $dop_values[] = $c_id;

        $values = array();
        $values[] = $count_comments++;  //  COMMENTS_NUMBER
        $values[] = $c_id;
        $values[] = ($c_date == 0 || is_null($c_date)) ? '' : str_replace($dop_tags, $dop_values, sb_parse_date($c_date, $ctl_fields_temps['ctl_date'], $ctl_lang)); // COMMENTS_DATE
        $values[] = ($c_author == '' || is_null($c_author)) ? '' : str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($c_author)), $ctl_fields_temps['ctl_author']); // COMMENTS_AUTHOR
        $values[] = ($c_email == '' || is_null($c_email)) ? '' : str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($c_email)), $ctl_fields_temps['ctl_email']); // COMMENTS_EMAIL
        $values[] = ($c_subj == '' || is_null($c_subj)) ? '' : str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($c_subj)), $ctl_fields_temps['ctl_subj']); // COMMENTS_SUBJ

        if($c_text != '' && !is_null($c_text) && isset($ctl_fields_temps['ctl_bb_codes']) && $ctl_fields_temps['ctl_bb_codes'] == 1)
        {
            $c_text = sbProgParseBBCodes($c_text, $ctl_fields_temps['ctl_quote_top'], $ctl_fields_temps['ctl_quote_bottom']);
        }

        $values[] = ($c_text == '' || is_null($c_text)) ? '' : str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($c_text)), $ctl_fields_temps['ctl_text']); // COMMENTS_TEXT
        $values[] = $c_ip;  // COMMENTS_AUTHOR_IP

        if ($c_file != '' && !is_null($c_file))
        {
            $size = '';
            if (sb_strpos($ctl_fields_temps['ctl_file'], '{SIZE}') !== false)
            {
                $img_src = $c_file;
                if (stripos($img_src, 'http://') !== false)
                {
                    $img_src = substr($img_src, 8);
                    $img_src = substr($img_src, strpos($img_src, '/'));
                }

                if ($GLOBALS['sbVfs']->exists($img_src))
                {
                    $size = $GLOBALS['sbVfs']->filesize($img_src);
                }
            }

            $values[] = str_replace(array_merge($dop_tags, array('{VALUE}', '{NAME}', '{SIZE}')), array_merge($dop_values, array($c_file, $c_file_name, $size)), $ctl_fields_temps['ctl_file']); // COMMENTS_FILE
        }
        else
        {
            $values[] = '';  // COMMENTS_FILE
        }

        $values[] = $quote_action.'&c_id='.$c_id.($page != '' ? '&'.$page : ''); // COMMENTS_QUOTE_ACTION
        $values[] = $c_user_id; // COMMENTS_USER_ID

        if ($show_user_data && $c_user_id > 0)
        {
            require_once(SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
            $values[] = fSite_Users_Get_Data($ctl_user_data_id, $c_user_id);
        }
        else
        {
            $values[] = '';
        }
        $values[] = $hash;
        $values[] = $quote_action.'&c_answer_id='.$c_id.($page != '' ? '&'.$page : ''); //  COMMENTS_TO_ANSWER

        $quote_action = $GLOBALS['PHP_SELF'].($query_str != '' ? $query_str.(!isset($_GET['sb_search']) || $_GET['sb_search'] != 1 ? '&c_hash='.$hash : '') : (!isset($_GET['sb_search']) || $_GET['sb_search'] != 1 ? '?c_hash='.$hash : ''));

        if(!isset($ctl_fields_temps['ctl_edit_link'])
           || (isset($ctl_fields_temps['registred_users_edit_link']) && $ctl_fields_temps['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($value[7] != $_SESSION['sbAuth']->getUserId()))))
        {
            //Можно редактировать только свои комментарии
            $values[] = '';
        }
        else
        {
            $values[] = sb_str_replace('{URL}', $quote_action.'&c_edit_id='.$c_id.($page != '' ? '&'.$page : ''), $ctl_fields_temps['ctl_edit_link']); // COMMENTS_EDIT_LINK
        }

        $values[] = isset($el_res) && $el_res ? $el_res[0][0] : ''; //  ELEM_ID
        $values[] = isset($el_res) && $el_res ? $el_res[0][1] : ''; //  ELEM_TITLE
        $values[] = isset($el_res) && $el_res ? urlencode($el_res[0][2]) : '';  //  ELEM_URL
        $values[] = isset($el_res) && $el_res ? $el_res[0][3] : ''; //  CAT_ID
        $values[] = isset($el_res) && $el_res ? $el_res[0][4] : ''; //  CAT_TITLE
        $values[] = isset($el_res) && $el_res ? urlencode($el_res[0][5]) : '';  //  CAT_URL

        $more_page = (isset($params['page']) ? $params['page'] : '');
        list($more_page, $more_ext) = sbGetMorePage($more_page);

        if(!isset($el_res) || (isset($el_res[0][2]) && trim($el_res[0][2]) == '') || $more_page == '')
        {
            $href = 'javascript: void(0);';
        }
        else
        {
            $href = $more_page;
            if (sbPlugins::getSetting('sb_static_urls') == 1)
            {
                // ЧПУ
                $href .= ($el_res[0][5] != '' ? urlencode($el_res[0][5]).'/' : $el_res[0][3].'/').
                        ($el_res[0][2] != '' ? urlencode($el_res[0][2]) : $el_res[0][0]).($more_ext != 'php' ? '.'.$more_ext : '/');
            }
            else
            {
                $href .= '?'.$table_data[0].'_cid='.$el_res[0][3].'&'.$table_data[0].'_id='.$el_res[0][0];
            }
        }
        $values[] = $href;      //  LINK

        if(intval($comments_as_tree) == 1)
        {
            if ($c_level < $prev_level)
            {
                for ($l = 0; $l < $prev_level - $c_level; $l++)
                {
                    $result .= $ctl_fields_temps['ctl_answer_bottom'];
                }
            }

            if ($c_level > $prev_level)
            {
                for ($l = 0; $l < $c_level - $prev_level; $l++)
                {
                    $result .= $ctl_fields_temps['ctl_answer_top'];
                }
            }

            $result .= str_replace($tags, $values, $ctl_element);
        }
        else
        {
            $result .= str_replace($tags, $values, $ctl_element);
        }

        $prev_level = $c_level;
    }

    if(intval($comments_as_tree) == 1)
    {
        for ($l = 1; $l < $prev_level; $l++)
        {
            $result .= $ctl_fields_temps['ctl_answer_bottom'];
        }
    }

    //  низ вывода
    $result .= str_replace(array('{NUM_LIST}', '{ALL_COUNT}', '{COMMENTS_FORM}'), array($pt_page_list, $comments_total, $comments_form), $ctl_bottom);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    if($pl_ident == '')
    {
        $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);
        $GLOBALS['sbCache']->save('pl_comments', $result);
    }
    else
    {
        return $result;
    }
}

/**
 * Функция генерирует HTML-код формы добавления комментария
 *
 * @param int $temp_id ID-макета дизайна вывода комментариев.
 * @param string $pl_ident Идентификатор модуля.
 * @param int $el_id Идентификатор элемента, для которого выводится список комментариев.
 * @param boolean $rights Есть права на вывод формы комментариев (TRUE) или нет (FALSE)
 *
 * @return string HTML-код формы добавления комментария.
 */
function fComments_Get_Form($temp_id, $pl_ident, $el_id, $rights = true)
{
    $hash = md5($pl_ident.' - '.$el_id);

    $res = sql_query('SELECT ctl_lang, ctl_fields_temps, ctl_form, ctl_messages, ctl_user_data_id FROM sb_comments_temps_list WHERE ctl_id = ?d', $temp_id);
    if(!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_COMMENTS_FORM_PLUGIN), SB_MSG_WARNING);
        return '';
    }

    list($ctl_lang, $ctl_fields_temps, $ctl_form, $ctl_messages, $ctl_user_data_id) = $res[0];

    if ($ctl_fields_temps != '')
        $ctl_fields_temps = unserialize($ctl_fields_temps);
    else
        $ctl_fields_temps = array();


    if ($ctl_messages != '')
        $ctl_messages = unserialize($ctl_messages);
    else
        $ctl_messages = array();

    if(isset($ctl_fields_temps['registred_users_add_only']) && $ctl_fields_temps['registred_users_add_only'] == 1  && !isset($_SESSION['sbAuth'])
        || !$rights)
    {
        //Только зарегистрированный пользователь может добавлять комментарии
        return $ctl_messages['auth_add_error'];
    }

    $tags = array('{SYSTEM_MESSAGE}', '{COMMENTS_ACTION}', '{COMMENTS_HASH}', '{COMMENTS_EDIT_ID}',
                  '{COMMENTS_AUTHOR}', '{COMMENTS_AUTHOR_EL}', '{FIELD_AUTHOR_SELECT_START}', '{FIELD_AUTHOR_SELECT_END}',
                  '{COMMENTS_EMAIL}', '{COMMENTS_EMAIL_EL}', '{FIELD_EMAIL_SELECT_START}', '{FIELD_EMAIL_SELECT_END}',
                  '{COMMENTS_DATE}', '{COMMENTS_DATE_EL}', '{FIELD_DATE_SELECT_START}', '{FIELD_DATE_SELECT_END}',
                  '{COMMENTS_SUBJ}', '{COMMENTS_SUBJ_EL}', '{FIELD_SUBJ_SELECT_START}', '{FIELD_SUBJ_SELECT_END}',
                  '{COMMENTS_TEXT}', '{COMMENTS_TEXT_EL}', '{FIELD_TEXT_SELECT_START}', '{FIELD_TEXT_SELECT_END}',
                  '{COMMENTS_FILE_NOW_EL}',
                  '{COMMENTS_FILE_EL}', '{FIELD_FILE_SELECT_START}', '{FIELD_FILE_SELECT_END}',
                  '{CAPTCHA_IMAGE}', '{CAPTCHA_IMAGE_HID}', '{CAPTCHA_IMAGE_EL}', '{COMMENTS_CAPTCHA_TEXT_EL}', '{FIELD_CAPTCHA_SELECT_START}', '{FIELD_CAPTCHA_SELECT_END}','{COMMENTS_ANSW}');

    if(isset($_REQUEST['c_hash']) && !isset($_REQUEST['c_id']) && !isset($_REQUEST['c_answer_id']) && !isset($_REQUEST['c_edit_id']) && $_REQUEST['c_hash'] == $hash)
    {
        // форма была отправлена, извлекаем значения полей
        $c_author = isset($_REQUEST['c_author']) ? $_REQUEST['c_author'] : '';
        $c_email = isset($_REQUEST['c_email']) ? $_REQUEST['c_email'] : '';
        $c_subj = isset($_REQUEST['c_subj']) ? $_REQUEST['c_subj'] : '';
        $c_text = isset($_REQUEST['c_text']) ? $_REQUEST['c_text'] : '';
        $c_date = isset($_REQUEST['c_date']) ? $_REQUEST['c_date'] : '';
        $c_f_url = '';
        $c_f_name = '';
    }
    elseif(isset($_REQUEST['c_hash']) && !isset($_REQUEST['c_id']) && !isset($_REQUEST['c_answer_id']) && isset($_REQUEST['c_edit_id']) && $_REQUEST['c_hash'] == $hash)
    {
        // Редактирование комментария
        $c_edit_id = isset($_REQUEST['c_edit_id']) ? intval($_REQUEST['c_edit_id']) : 0;
        $comments_fields = sql_query('SELECT c_author, c_email, c_subj, c_text, c_date, c_file, c_file_name FROM sb_comments WHERE c_id = ?d', $c_edit_id);
        if(isset($_REQUEST['c_author'])) // Автор
            $c_author = $_REQUEST['c_author'];
        else
            $c_author = $comments_fields[0][0];

        if(isset($_REQUEST['c_email'])) // E-mail
            $c_email = $_REQUEST['c_email'];
        else
            $c_email = $comments_fields[0][1];

        if(isset($_REQUEST['c_date'])) // Дата
            $c_date = $_REQUEST['c_date'];
        else
            $c_date = sb_parse_date($comments_fields[0][4], $ctl_fields_temps['ctl_date']);

        if(isset($_REQUEST['c_subj'])) // E-mail
            $c_subj = $_REQUEST['c_subj'];
        else
            $c_subj = $comments_fields[0][2];

        if(isset($_REQUEST['c_text'])) // E-mail
            $c_text = $_REQUEST['c_text'];
        else
            $c_text = $comments_fields[0][3];

        $c_f_url = $comments_fields[0][5];
        $c_f_name = $comments_fields[0][6];
    }
    else
    {
        if(isset($_SESSION['sbAuth']))
        {
            // залогиненный пользователь
            $c_author = $_SESSION['sbAuth']->getUserName();
            if ($c_author == '')
                $c_author = $_SESSION['sbAuth']->getUserLogin();

            $c_email = $_SESSION['sbAuth']->getUserEmail();
        }
        else
        {
            $c_author = '';
            $c_email = '';
        }

        $c_subj = '';
        $c_text = '';
        $c_date = '';

        if(isset($_REQUEST['c_hash']) && (isset($_REQUEST['c_id']) || isset($_REQUEST['c_answer_id'])) && $_REQUEST['c_hash'] == $hash)
        {
            // Цитируем
            $c_id = isset($_REQUEST['c_id']) ? intval($_REQUEST['c_id']) : intval($_REQUEST['c_answer_id']);

            $res = sql_query('SELECT c_text, c_date, c_author, c_email, c_user_id FROM sb_comments WHERE c_id = ?d', $c_id);
            if($res)
            {
                list($text, $date, $author, $email, $user_id) = $res[0];

                $dop_tags = array('{AUTHOR}', '{EMAIL}', '{USER_ID}', '{ID}');
                $dop_values = array($author, $email, $user_id, $c_id);

                if ($author == '')
                    $author = $email;

                $date = str_replace($dop_tags, $dop_values, sb_parse_date($date, $ctl_fields_temps['ctl_date'], $ctl_lang));

                $c_text = '[quote'.($author != '' ? '="'.$author.'"' : '').($date != '' ? ' date="'.$date.'"' : '').']'.preg_replace('=<br.*?/?>=i', '', $text).'[/quote]';
            }
        }
    }

    $query_str = ($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '');
    foreach ($_GET as $key => $value)
    {
        if (preg_match('/^[A-Z0-9_\-]+_cid$/i', $key))
        {
            if ($query_str != '')
                $query_str .= '&'.$key.'='.intval($value);
            else
                $query_str .= '?'.$key.'='.intval($value);
        }
        elseif (preg_match('/^[A-Z0-9_\-]+_id$/i', $key))
        {
            if ($query_str != '')
                $query_str .= '&'.$key.'='.intval($value);
            else
                $query_str .= '?'.$key.'='.intval($value);
        }
        elseif (preg_match('/^page_.+/i', $key))
        {
            if ($query_str != '')
                $query_str .= '&'.$key.'='.intval($value);
            else
                $query_str .= '?'.$key.'='.intval($value);
        }
    }

    $query_str = preg_replace('/[?&]?c_id=[0-9]+/i', '', $query_str);
    $query_str = preg_replace('/[?&]?c_hash=[A-Za-z0-9]+/i', '', $query_str);
    $query_str = preg_replace('/[?&]?c_answer_id=[0-9]+/i', '', $query_str);
    $query_str = preg_replace('/[?&]?c_edit_id=[0-9]+/i', '', $query_str);

    $action = $GLOBALS['PHP_SELF'].$query_str;

    $values = array();
    $values[0] = ''; // SYSTEM_MESSAGE
    $values[] = $action; // COMMENTS_ACTION
    $values[] = $hash; // COMMENTS_HASH
    if(isset($_REQUEST['c_edit_id']) && intval($_REQUEST['c_edit_id']) > 0)
        $values[] = intval($_REQUEST['c_edit_id']);
    else
        $values[] = '0';

    //  Проверка на ошибки при отправленной форме
    if(isset($_REQUEST['c_hash']) && !isset($_REQUEST['c_id']) && !isset($_REQUEST['c_answer_id']) && !isset($_GET['c_edit_id']) && $_REQUEST['c_hash'] == $hash)
    {
        fComments_Check($values, $ctl_fields_temps, $ctl_messages, $ctl_form, $pl_ident, $el_id);
        if (isset($GLOBALS['sb_c_hash_'.$hash]) && (isset($GLOBALS['sb_c_hash_'.$hash]['add_ok']) || isset($GLOBALS['sb_c_hash_'.$hash]['edit_ok']) ))
        {
            if(isset($GLOBALS['sb_c_hash_'.$hash]['add_ok']))
                $comment_id = $GLOBALS['sb_c_hash_'.$hash]['add_ok'];
            elseif(isset($GLOBALS['sb_c_hash_'.$hash]['edit_ok']))
                $comment_id = $GLOBALS['sb_c_hash_'.$hash]['edit_ok'];

            // комментарий добавлен
            $res = sql_query('SELECT c_id, c_email, c_author, c_subj, c_text, c_date, c_ip, c_file, c_file_name, c_user_id  FROM `sb_comments` WHERE c_id = ?d', $comment_id);

            if($res)
            {
                list($c_mes_id, $c_mes_email, $c_mes_author, $c_mes_subj, $c_mes_text, $c_mes_date, $c_mes_ip, $c_mes_file, $c_mes_file_name, $c_mes_user_id) = $res[0];

                $dop_mes_tags = array('{AUTHOR}', '{EMAIL}', '{USER_ID}', '{ID}');
                $dop_mes_values = array();
                $dop_mes_values[] = $c_mes_author;
                $dop_mes_values[] = $c_mes_email;
                $dop_mes_values[] = $c_mes_user_id;
                $dop_mes_values[] = $c_mes_id;

                $mes_tags = array('{COMMENTS_ID}',
                            '{COMMENTS_DATE}',
                            '{COMMENTS_AUTHOR}',
                            '{COMMENTS_EMAIL}',
                            '{COMMENTS_SUBJ}',
                            '{COMMENTS_TEXT}',
                            '{COMMENTS_AUTHOR_IP}',
                            '{COMMENTS_FILE}',
                            '{COMMENTS_USER_ID}',
                            '{COMMENTS_USER_DATA}');

                $mes_values = array();
                $mes_values[] = $c_mes_id; // COMMENTS_ID
                $mes_values[] = ($c_mes_date == 0 || is_null($c_mes_date)) ? '' : str_replace($dop_mes_tags, $dop_mes_values, sb_parse_date($c_mes_date, $ctl_fields_temps['ctl_date_mes'], $ctl_lang)); // COMMENTS_DATE
                $mes_values[] = ($c_mes_author == '' || is_null($c_mes_author)) ? '' : str_replace(array_merge($dop_mes_tags, array('{VALUE}')), array_merge($dop_mes_values, array($c_mes_author)), $ctl_fields_temps['ctl_author_mes']); // COMMENTS_AUTHOR
                $mes_values[] = ($c_mes_email == '' || is_null($c_mes_email)) ? '' : str_replace(array_merge($dop_mes_tags, array('{VALUE}')), array_merge($dop_mes_values, array($c_mes_email)), $ctl_fields_temps['ctl_email_mes']); // COMMENTS_EMAIL
                $mes_values[] = ($c_mes_subj == '' || is_null($c_mes_subj)) ? '' : str_replace(array_merge($dop_mes_tags, array('{VALUE}')), array_merge($dop_mes_values, array($c_mes_subj)), $ctl_fields_temps['ctl_subj_mes']); // COMMENTS_SUBJ

                if(trim($c_mes_text) != '' && isset($ctl_fields_temps['ctl_bb_codes']) && $ctl_fields_temps['ctl_bb_codes'] == 1)
                {
                    $c_mes_text = sbProgParseBBCodes($c_mes_text, $ctl_fields_temps['ctl_quote_top'], $ctl_fields_temps['ctl_quote_bottom']);
                }

                $mes_values[] = ($c_mes_text == '' || is_null($c_mes_text)) ? '' : str_replace(array_merge($dop_mes_tags, array('{VALUE}')), array_merge($dop_mes_values, array($c_mes_text)), $ctl_fields_temps['ctl_text']); // COMMENTS_TEXT
                $mes_values[] = $c_mes_ip; // COMMENTS_AUTHOR_IP

                if ($c_mes_file != '')
                {
                    $size = '';
                    if (sb_strpos($ctl_fields_temps['ctl_file'], '{SIZE}') !== false)
                    {
                        $img_src = $c_mes_file;
                        if (stripos($img_src, 'http://') !== false)
                        {
                            $img_src = substr($img_src, 8);
                            $img_src = substr($img_src, strpos($img_src, '/'));
                        }

                        if ($GLOBALS['sbVfs']->exists($img_src))
                        {
                            $size = $GLOBALS['sbVfs']->filesize($img_src);
                        }
                    }

                    $mes_values[] = str_replace(array_merge($dop_mes_tags, array('{VALUE}', '{NAME}', '{SIZE}')), array_merge($dop_mes_values, array($c_mes_file, $c_mes_file_name, $size)), $ctl_fields_temps['ctl_file']); // COMMENTS_FILE
                }
                else
                {
                    $mes_values[] = ''; // COMMENTS_FILE
                }

                $mes_values[] = $c_mes_user_id; // COMMENTS_USER_ID

                if (isset($GLOBALS['sb_c_hash_'.$hash]['add_ok']) && $c_mes_user_id > 0 && sb_strpos($ctl_messages['add_ok'], '{COMMENTS_USER_DATA}') !== false && $ctl_user_data_id > 0)
                {
                    require_once(SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
                    $mes_values[] = fSite_Users_Get_Data($ctl_user_data_id, $c_mes_user_id);
                }
                elseif (isset($GLOBALS['sb_c_hash_'.$hash]['edit_ok']) && $c_mes_user_id > 0 && sb_strpos($ctl_messages['edit_ok'], '{COMMENTS_USER_DATA}') !== false && $ctl_user_data_id > 0)
                {
                    require_once(SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
                    $mes_values[] = fSite_Users_Get_Data($ctl_user_data_id, $c_mes_user_id);
                }
                else
                {
                    $mes_values[] = '';
                }

                if(sb_strpos($ctl_form, '{COMMENTS_AUTHOR}'))
                    $values[4] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserLogin() : '';
                elseif(sb_strpos($ctl_form, '{COMMENTS_AUTHOR_EL}'))
                    $values[5] = sb_str_replace('{VALUE}', (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserLogin() : ''), $ctl_fields_temps['c_author_el']);
                else
                {
                    $values[4] = '';
                    $values[5] = '';
                }

                if(sb_strpos($ctl_form, '{COMMENTS_EMAIL}'))
                    $values[8] = isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserEmail() : '';
                elseif(sb_strpos($ctl_form, '{COMMENTS_EMAIL_EL}'))
                    $values[9] = sb_str_replace('{VALUE}', (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserEmail() : ''), $ctl_fields_temps['c_mail_el']);
                else
                {
                    $values[8] = '';
                    $values[9] = '';
                }

                if(sb_strpos($ctl_form, '{COMMENTS_SUBJ}'))
                    $values[16] = '';
                elseif(sb_strpos($ctl_form, '{COMMENTS_SUBJ_EL}'))
                    $values[17] = $ctl_fields_temps['c_subject_el'];
                else
                {
                    $values[16] = '';
                    $values[17] = '';
                }

                if(sb_strpos($ctl_form, '{COMMENTS_TEXT}'))
                    $values[20] = '';
                elseif(sb_strpos($ctl_form, '{COMMENTS_TEXT_EL}'))
                    $values[21] = $ctl_fields_temps['c_text_el'];
                else
                {
                    $values[20] = '';
                    $values[21] = '';
                }

                if(sb_strpos($ctl_form, '{COMMENTS_DATE}'))
                    $values[12] = '';
                elseif(sb_strpos($ctl_form, '{COMMENTS_DATE_EL}'))
                    $values[13] = $ctl_fields_temps['c_date_el'];
                else
                {
                    $values[12] = '';
                    $values[13] = '';
                }

                if(isset($GLOBALS['sb_c_hash_'.$hash]['add_ok']))
                    $values[0] = str_replace($mes_tags, $mes_values, $ctl_messages['add_ok']);
                elseif(isset($GLOBALS['sb_c_hash_'.$hash]['edit_ok']))
                    $values[0] = str_replace($mes_tags, $mes_values, $ctl_messages['edit_ok']);
            }
            else
            {
                $values[0] = $ctl_messages['add_error'];
            }
        }
        elseif (isset($GLOBALS['sb_c_hash_'.$hash]) && isset($GLOBALS['sb_c_hash_'.$hash]['add_error']) && $GLOBALS['sb_c_hash_'.$hash]['add_error'])
        {
            $values[0] = $ctl_messages['add_error'];
        }
    }
    else
    {
        // Автор
        if(sb_strpos($ctl_form, '{COMMENTS_AUTHOR_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_author, $ctl_fields_temps['c_author_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_AUTHOR}') !== false)
        {
            $values[] = $c_author;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }

        $values[] = '';  //FIELD_AUTHOR_SELECT_START
        $values[] = '';  //FIELD_AUTHOR_SELECT_END

        // Mail
        if(sb_strpos($ctl_form, '{COMMENTS_EMAIL_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_email, $ctl_fields_temps['c_mail_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_EMAIL}') !== false)
        {
            $values[] = $c_email;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }
        $values[] = '';  //FIELD_EMAIL_SELECT_START
        $values[] = '';  //FIELD_EMAIL_SELECT_END

        // Дата
        if(sb_strpos($ctl_form, '{COMMENTS_DATE_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_date, $ctl_fields_temps['c_date_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_DATE}') !== false)
        {
            $values[] = $c_date;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }
        $values[] = '';  //FIELD_DATE_SELECT_START
        $values[] = '';  //FIELD_DATE_SELECT_END

        // Тема
        if(sb_strpos($ctl_form, '{COMMENTS_SUBJ_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_subj, $ctl_fields_temps['c_subject_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_SUBJ}') !== false)
        {
            $values[] = $c_subj;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }

        $values[] = '';  //FIELD_SUBJ_SELECT_START
        $values[] = '';  //FIELD_SUBJ_SELECT_END

        // Текст
        if(sb_strpos($ctl_form, '{COMMENTS_TEXT_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_text, $ctl_fields_temps['c_text_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_TEXT}') !== false)
        {
            $values[] = $c_text;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }

        $values[] = '';  //FIELD_TEXT_SELECT_START
        $values[] = '';  //FIELD_TEXT_SELECT_END

        // Текущий файл
        if(sb_strpos($ctl_form, '{COMMENTS_FILE_NOW_EL}') !== false && isset($c_f_url) && $c_f_url != '')
            $values[] = str_replace(array('{URL}','{NAME}'), array($c_f_url, $c_f_name), $ctl_fields_temps['c_file_now_el']);
        else
            $values[] = '';

        // Файл
        if(sb_strpos($ctl_form, '{COMMENTS_FILE_EL}') !== false)
            $values[] = $ctl_fields_temps['c_file_el'];
        else
            $values[] = '';

        $values[] = '';  //FIELD_FILE_SELECT_START
        $values[] = '';  //FIELD_FILE_SELECT_END

        // Капча
        if (sb_strpos($ctl_form, '{CAPTCHA_IMAGE}') !== false)
        {
            $turing = sbProgGetTuring();

            if ($turing)
            {
                $values[] = $turing[0]; // CAPTCHA_IMAGE
                $values[] = $turing[1]; // CAPTCHA_IMAGE_HID
                $values[] = '';
            }
            else
            {
                $values[] = ''; // CAPTCHA_IMAGE
                $values[] = ''; // CAPTCHA_IMAGE_HID
                $values[] = '';
            }
        }
        elseif (sb_strpos($ctl_form, '{CAPTCHA_IMAGE_EL}') !== false)
        {
            $turing = sbProgGetTuring();

            if ($turing)
            {
                $values[] = '';
                $values[] = '';
                $values[] = sb_str_replace(array('{IMAGE}', '{IMAGE_HID}'), $turing, $ctl_fields_temps['c_captcha_image_el']);
            }
            else
            {
                $values[] = '';
                $values[] = '';
                $values[] = '';
            }
        }
        else
        {
            $values[] = ''; // CAPTCHA_IMAGE
            $values[] = ''; // CAPTCHA_IMAGE_HID
            $values[] = '';
        }

        // Поле ввода для капчи
        if(sb_strpos($ctl_form, '{COMMENTS_CAPTCHA_TEXT_EL}') !== false)
            $values[] = $ctl_fields_temps['c_captcha_code_el'];
        else
            $values[] = '';

        $values[] = ''; // FIELD_CAPTCHA_SELECT_START
        $values[] = ''; // FIELD_CAPTCHA_SELECT_END
    }

    $values[] = (isset($_REQUEST['c_answer_id']) && intval($_REQUEST['c_answer_id']) > 0 ? intval($_REQUEST['c_answer_id']) : 0);   //  COMMENTS_ANSW
    $result = str_replace($tags, $values, $ctl_form);
    $result = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $result);

    return $result;
}

/**
 * Проверка переданных полей комментария
 *
 * @param array $values Массив значений полей.
 * @param array $ctl_fields_temps Массив макетов дизайна полей.
 * @param array $ctl_messages Массив макетов дизайна сообщений.
 * @param string $ctl_form Массив макета вывода формы.
 * @param string $pl_ident Идентификатор модуля.
 * @param int $el_id Идентификатор элемента, для которого выводится список комментариев.
 * @param bool $return Производить возврат из функции сразу при возникновении ошибки или нет.
 *
 * @return TRUE, если проверка прошла успешно, FALSE в ином случае.
 */
function fComments_Check(&$values, &$ctl_fields_temps, &$ctl_messages, &$ctl_form, $pl_ident, $el_id, $return = false)
{
    $hash = md5($pl_ident.' - '.$el_id);
    if(isset($_REQUEST['c_edit_id']) && intval($_REQUEST['c_edit_id']) > 0)
        $edit_id = intval($_REQUEST['c_edit_id']);
    else
        $edit_id = 0;

    // форма была отправлена, извлекаем значения полей
    $c_author = isset($_REQUEST['c_author']) ? $_REQUEST['c_author'] : '';
    $c_email = isset($_REQUEST['c_email']) ? $_REQUEST['c_email'] : '';
    $c_date = isset($_REQUEST['c_date']) ? $_REQUEST['c_date'] : '';
    $c_subj = isset($_REQUEST['c_subj']) ? $_REQUEST['c_subj'] : '';
    $c_text = isset($_REQUEST['c_text']) ? $_REQUEST['c_text'] : '';

    if($edit_id > 0)
    {
        $res = sql_query('SELECT c_user_id, c_file FROM sb_comments WHERE c_id = ?d', $edit_id);
        if(!$res)
            return false;
    }

    if($edit_id < 1 && isset($ctl_fields_temps['registred_users_add_only']) && $ctl_fields_temps['registred_users_add_only'] == 1  && !isset($_SESSION['sbAuth']))
     {
        //Только авторизированные пользователи могут добавлять комментарии
         $values[0] = $ctl_messages['auth_add_error'];
     }
    if($edit_id > 0 && isset($ctl_fields_temps['registred_users_edit_link']) && $ctl_fields_temps['registred_users_edit_link'] == 1 && (!isset($_SESSION['sbAuth']) || ($res[0][0] != $_SESSION['sbAuth']->getUserId())))
    {
        //Можно редактировать только свои комментарии
        $values[0] = $ctl_messages['auth_edit_error'];
    }
    // проверяем поля
    //Автор
    if ($return)
    {
        if($edit_id < 1 || ($edit_id > 0 && isset($_REQUEST['c_author'])))
            $values['c_author'] = $c_author;
    }
    else
    {
        if(sb_strpos($ctl_form, '{COMMENTS_AUTHOR_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_author, $ctl_fields_temps['c_author_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_AUTHOR}') !== false)
        {
            $values[] = $c_author;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }
    }

    if($c_author == '' && isset($ctl_fields_temps['ctl_field_need_author']) && $ctl_fields_temps['ctl_field_need_author'] == 1)
    {
        if ($return)
        {
            return false;
        }
        else
        {
            $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_AUTHOR_SELECT_START
            $values[] = $ctl_fields_temps['ctl_select_end'];   // FIELD_AUTHOR_SELECT_END

            $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
        }
    }
    elseif (!$return)
    {
        $values[] = ''; //FIELD_AUTHOR_SELECT_START
        $values[] = ''; //FIELD_AUTHOR_SELECT_END
    }

    //Mail
    if ($return)
    {
        if($edit_id < 1 || ($edit_id > 0 && isset($_REQUEST['c_email'])))
            $values['c_email'] = $c_email;
    }
    else
    {
        if(sb_strpos($ctl_form, '{COMMENTS_EMAIL_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_email, $ctl_fields_temps['c_mail_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_EMAIL}') !== false)
        {
            $values[] = $c_email;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }

    }

    if(($c_email == '' && isset($ctl_fields_temps['ctl_field_need_email']) && $ctl_fields_temps['ctl_field_need_email'] == 1)
       || ($c_email != '' && !preg_match('/^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/is'.SB_PREG_MOD, $c_email)))
    {
        if ($return)
        {
            return false;
        }
        else
        {
            $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_EMAIL_SELECT_START
            $values[] = $ctl_fields_temps['ctl_select_end'];   // FIELD_EMAIL_SELECT_END

            $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
        }
    }
    elseif (!$return)
    {
        $values[] = ''; //FIELD_EMAIL_SELECT_START
        $values[] = ''; //FIELD_EMAIL_SELECT_END
    }
    // Date
    if ($return)
    {
        if($edit_id < 1 || ($edit_id > 0 && isset($_REQUEST['c_date'])))
            $values['c_date'] = $c_date;
    }
    else
    {
        if(sb_strpos($ctl_form, '{COMMENTS_DATE_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_date, $ctl_fields_temps['c_date_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_DATE}') !== false)
        {
            $values[] = $c_date;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }
    }

    if(($c_date == '' && isset($ctl_fields_temps['ctl_field_need_date']) && $ctl_fields_temps['ctl_field_need_date'] == 1))
    {
        if ($return)
        {
            return false;
        }
        else
        {
            $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_DATE_SELECT_START
            $values[] = $ctl_fields_temps['ctl_select_end'];   // FIELD_DATE_SELECT_END

            $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
        }
    }
    elseif (!$return)
    {
        $values[] = ''; //FIELD_DATE_SELECT_START
        $values[] = ''; //FIELD_DATE_SELECT_END
    }

    // Тема
    if ($return)
    {
        if($edit_id < 1 || ($edit_id > 0 && isset($_REQUEST['c_subj'])))
            $values['c_subj'] = $c_subj;
    }
    else
    {
        if(sb_strpos($ctl_form, '{COMMENTS_SUBJ_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_subj, $ctl_fields_temps['c_subject_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_SUBJ}') !== false)
        {
            $values[] = $c_subj;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }
    }

    if($c_subj == '' && isset($ctl_fields_temps['ctl_field_need_subj']) && $ctl_fields_temps['ctl_field_need_subj'] == 1)
    {
        if ($return)
        {
            return false;
        }
        else
        {
            $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_SUBJ_SELECT_START
            $values[] = $ctl_fields_temps['ctl_select_end'];   // FIELD_SUBJ_SELECT_END

            $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
        }
    }
    elseif (!$return)
    {
        $values[] = ''; //FIELD_SUBJ_SELECT_START
        $values[] = ''; //FIELD_SUBJ_SELECT_END
    }

    // Текст
    if ($return)
    {
        if($edit_id < 1 || ($edit_id > 0 && isset($_REQUEST['c_text'])))
            $values['c_text'] = $c_text;
    }
    else
    {
        if(sb_strpos($ctl_form, '{COMMENTS_TEXT_EL}') !== false)
        {
            $values[] = '';
            $values[] = sb_str_replace('{VALUE}', $c_text, $ctl_fields_temps['c_text_el']);
        }
        elseif(sb_strpos($ctl_form, '{COMMENTS_TEXT}') !== false)
        {
            $values[] = $c_text;
            $values[] = '';
        }
        else
        {
            $values[] = '';
            $values[] = '';
        }
    }

    if($c_text == '' && isset($ctl_fields_temps['ctl_field_need_text']) && $ctl_fields_temps['ctl_field_need_text'] == 1)
    {
        if ($return)
        {
            return false;
        }
        else
        {
            $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_TEXT_SELECT_START
            $values[] = $ctl_fields_temps['ctl_select_end'];   // FIELD_TEXT_SELECT_END

            $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
        }
    }
    elseif (!$return)
    {
        $values[] = ''; //FIELD_TEXT_SELECT_START
        $values[] = ''; //FIELD_TEXT_SELECT_END
    }

    // Текущий файл
    if(!$return)
    {
        $values[] = '';
    }

    // Файл
    if (isset($ctl_fields_temps['ctl_field_need_file']) && (!isset($_FILES['c_file']) || $_FILES['c_file']['tmp_name'] == '' || (!isset($GLOBALS['sb_c_hash_'.$hash]) && !is_uploaded_file($_FILES['c_file']['tmp_name']))))
    {
        if ($return)
        {
            return false;
        }
        else
        {
            if(sb_strpos($ctl_form, '{COMMENTS_FILE_EL}') !== false)
                $values[] = $ctl_fields_temps['c_file_el'];
            else
                $values[] = '';

            $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_FILE_SELECT_START
            $values[] = $ctl_fields_temps['ctl_select_end']; // FIELD_FILE_SELECT_END

            $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
        }
    }
    elseif (isset($_FILES['c_file']))
    {
        // загружаем файл пользователя
        if (isset($GLOBALS['sb_c_hash_'.$hash]))
        {
            if (isset($GLOBALS['sb_c_hash_'.$hash]['error_file']))
            {
                if(sb_strpos($ctl_form, '{COMMENTS_FILE_EL}') !== false)
                    $values[] = $ctl_fields_temps['c_file_el'];
                else
                    $values[] = '';
                $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_FILE_SELECT_START
                $values[] = $ctl_fields_temps['ctl_select_end']; // FIELD_FILE_SELECT_END

                switch($GLOBALS['sb_c_hash_'.$hash]['error_file'])
                {
                    case 2:
                        $values[0] .= str_replace(array('{FILE}', '{FILE_SIZE}'), array($_FILES['c_file']['name'], $ctl_fields_temps['ctl_files_size']), $ctl_messages['file_size_error']);
                        break;

                    case 4:
                        $values[0] .= str_replace(array('{FILE}', '{FILE_EXT}'), array($_FILES['c_file']['name'], $ctl_fields_temps['ctl_files_ext']), $ctl_messages['file_ext_error']);
                        break;

                    case 5:
                    case 6:
                        $values[0] .= str_replace('{FILE}', $_FILES['c_file']['name'], $ctl_messages['file_error']);
                        break;

                    default:
                        $values[0] = $ctl_messages['fields_error']; // SYSTEM_MESSAGE
                        break;
                }
            }
            else
            {
                if(sb_strpos($ctl_form, '{COMMENTS_FILE_EL}') !== false)
                    $values[] = $ctl_fields_temps['c_file_el'];
                else
                    $values[] = '';
                $values[] = ''; // FIELD_FILE_SELECT_START
                $values[] = ''; // FIELD_FILE_SELECT_END
            }
        }
        elseif (is_uploaded_file($_FILES['c_file']['tmp_name']))
        {
            require_once(SB_CMS_LIB_PATH.'/sbUploader.inc.php');

            $uploader = new sbUploader();
            $uploader->setMaxFileSize(intval($ctl_fields_temps['ctl_files_size']));

            // сохраняем файл
            $success = $uploader->upload('c_file', explode(' ', $ctl_fields_temps['ctl_files_ext']));
            $file_name = false;
            if ($success)
            {
                $file_name = $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/'.$pl_ident.'/', time().'_'.$_FILES['c_file']['name']);
            }

            if (!$success || !$file_name)
            {
                $GLOBALS['sb_c_hash_'.$hash] = array();
                $GLOBALS['sb_c_hash_'.$hash]['error_file'] = $uploader->getErrorCode();
                return false;
            }
            else
            {
                $values['c_file'] = SB_SITE_USER_UPLOAD_PATH.'/'.$pl_ident.'/'.$file_name;
                $values['c_file_name'] = $file_name;

                // Если нужно удалить старый файл
                $GLOBALS['sbVfs']->mLocal = true;
                if(isset($_POST['c_file_delete']) && $GLOBALS['sbVfs']->exists(SB_BASEDIR.$res[0][1]) && $GLOBALS['sbVfs']->is_file(SB_BASEDIR.$res[0][1]) && $res[0][1] != '')
                {
                    $GLOBALS['sbVfs']->delete(SB_BASEDIR.$res[0][1]);
                }
                $GLOBALS['sbVfs']->mLocal = false;
            }
        }
    }
    elseif (!$return)
    {
        if(sb_strpos($ctl_form, '{COMMENTS_FILE_EL}') !== false)
            $values[] = $ctl_fields_temps['c_file_el'];
        else
            $values[] = '';
        $values[] = ''; // FIELD_FILE_SELECT_START
        $values[] = ''; // FIELD_FILE_SELECT_END
    }

    // Капча
    if (sb_strpos($ctl_form, '{CAPTCHA_IMAGE}') !== false || sb_strpos($ctl_form, '{CAPTCHA_IMAGE_EL}') !== false)
    {
        if (!$return)
        {
            $turing = sbProgGetTuring();
            if (sb_strpos($ctl_form, '{CAPTCHA_IMAGE}') !== false)
            {
                if ($turing)
                {
                    $values[] = $turing[0]; // CAPTCHA_IMAGE
                    $values[] = $turing[1]; // CAPTCHA_IMAGE_HID
                    $values[] = '';
                    $values[] = '';
                }
                else
                {
                    $values[] = ''; // CAPTCHA_IMAGE
                    $values[] = ''; // CAPTCHA_IMAGE_HID
                    $values[] = '';
                    $values[] = '';
                }
            }
            elseif (sb_strpos($ctl_form, '{CAPTCHA_IMAGE_EL}') !== false)
            {
                if ($turing)
                {
                    $values[] = '';
                    $values[] = '';
                    $values[] = sb_str_replace(array('{IMAGE}', '{IMAGE_HID}'), $turing, $ctl_fields_temps['c_captcha_image_el']);
                    $values[] = $ctl_fields_temps['c_captcha_code_el'];
                }
                else
                {
                    $values[] = '';
                    $values[] = '';
                    $values[] = '';
                    $values[] = $ctl_fields_temps['c_captcha_code_el'];
                }
            }
        }
        if (isset($GLOBALS['sb_c_hash_'.$hash]))
        {
            if (isset($GLOBALS['sb_c_hash_'.$hash]['error_captcha']) && $GLOBALS['sb_c_hash_'.$hash]['error_captcha'])
            {
                $values[] = $ctl_fields_temps['ctl_select_start']; // FIELD_CAPTCHA_SELECT_START
                $values[] = $ctl_fields_temps['ctl_select_end']; // FIELD_CAPTCHA_SELECT_END

                $values[0] .= $ctl_messages['captcha_error']; // SYSTEM_MESSAGE
            }
            else
            {
                $values[] = ''; // FIELD_CAPTCHA_SELECT_START
                $values[] = ''; // FIELD_CAPTCHA_SELECT_END
            }
        }
        else
        {
            if (!sbProgCheckTuring('c_captcha_code', 'c_captcha_hid'))
            {
                $GLOBALS['sb_c_hash_'.$hash] = array();
                $GLOBALS['sb_c_hash_'.$hash]['error_captcha'] = true;
                return false;
            }
        }
    }
    elseif (!$return)
    {
        $values[] = ''; // CAPTCHA_IMAGE
        $values[] = ''; // CAPTCHA_IMAGE_HID
        $values[] = ''; // FIELD_CAPTCHA_SELECT_START
        $values[] = ''; // FIELD_CAPTCHA_SELECT_END
    }

    return true;
}

/**
 * Добавление комментария
 *
 * @param int $temp_id ID-макета дизайна вывода комментариев.
 * @param string $pl_ident Идентификатор модуля.
 * @param int $el_id Идентификатор элемента, для которого выводится список комментариев.
 * @param bool $moderate Премодерируемые комментарии или нет.
 * @param string $moderate_email E-Mail адреса модераторов.
 *
 * @return bool TRUE, если комментарий был добавлен, FALSE в ином случае.
 */
function fComments_Add_Comment($temp_id, $pl_ident, $el_id, $moderate, $moderate_email)
{
    if(isset($_REQUEST['c_edit_id']) && intval($_REQUEST['c_edit_id']) > 0)
    {
        // Если редактирование
        if(!fComments_Edit_Comment_Prog($temp_id, $pl_ident, $el_id, $moderate_email))
            return false;

        return true;
    }
    $hash = md5($pl_ident.' - '.$el_id);

    if($temp_id <= 0 || isset($GLOBALS['sb_c_hash_'.$hash]) || !isset($_REQUEST['c_hash']) || isset($_REQUEST['c_id']) || isset($_REQUEST['c_answer_id']) || $_REQUEST['c_hash'] != $hash)
    {
        return false;
    }

    $res = sql_query('SELECT ctl_lang, ctl_fields_temps, ctl_form, ctl_messages, ctl_user_data_id FROM sb_comments_temps_list WHERE ctl_id = ?d', $temp_id);
    if(!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_COMMENTS_FORM_PLUGIN), SB_MSG_WARNING);
        return false;
    }

    list($ctl_lang, $ctl_fields_temps, $ctl_form, $ctl_messages, $ctl_user_data_id) = $res[0];

    if ($ctl_fields_temps != '')
        $ctl_fields_temps = unserialize($ctl_fields_temps);
    else
        $ctl_fields_temps = array();

    if ($ctl_messages != '')
        $ctl_messages = unserialize($ctl_messages);
    else
        $ctl_messages = array();

    $row = array();
    if (!fComments_Check($row, $ctl_fields_temps, $ctl_messages, $ctl_form, $pl_ident, $el_id, true))
    {
        if (isset($row['c_file']) && trim($row['c_file']) != '' && $GLOBALS['sbVfs']->is_file($row['c_file']))
        {
            $GLOBALS['sbVfs']->delete($row['c_file']);
        }

        if (!isset($GLOBALS['sb_c_hash_'.$hash]))
        {
            $GLOBALS['sb_c_hash_'.$hash] = array();
            $GLOBALS['sb_c_hash_'.$hash]['fields_error'] = true;
        }
        return false;
    }
    $pl_ident = preg_replace('/[^A-Z0-9a-z_\-]/', '', $pl_ident);

    $row['c_subj'] = nl2br($row['c_subj']);
    $row['c_text'] = nl2br($row['c_text']);
    $row['c_plugin'] = $pl_ident;
    $row['c_el_id'] = intval($el_id);

    if(isset($row['c_date']) && trim($row['c_date']) != '')
    {
        $row['c_date'] = sb_datetoint($row['c_date'], $ctl_fields_temps['ctl_date']);
        if (is_null($row['c_date']))
            $row['c_date'] = time();
    }
    else
    {
        $row['c_date'] = time();
    }

    $row['c_show'] = ($moderate ? 0 : 1);
    $row['c_ip'] = sbAuth::getIP();

    if(isset($_SESSION['sbAuth']))
    {
        $row['c_user_id'] = $_SESSION['sbAuth']->getUserId();
    }
    else
    {
        $row['c_user_id'] = 0;
    }
    $res = sql_query('SELECT c_id FROM sb_comments WHERE c_plugin=? AND c_el_id=?d AND c_subj=? AND c_text=?',
                    $row['c_plugin'], $row['c_el_id'], $row['c_subj'], $row['c_text']);
    if ($res)
    {
        //  сбрасываем кэш модуля
        $GLOBALS['sb_c_hash_'.$hash] = array();
        $GLOBALS['sb_c_hash_'.$hash]['add_ok'] = $res[0][0];

        $GLOBALS['sbCache']->mCacheOff = true;
        return false;
    }

    $c_id = isset($_REQUEST['c_ans_id']) && intval($_REQUEST['c_ans_id']) > 0 ? $_REQUEST['c_ans_id'] : 0;
    if ($c_id != 0)
    {
        $res = sql_query('SELECT c_right, c_level FROM sb_comments WHERE c_id=?d', $c_id);
        if (!$res)
            return false;

        list($right, $level) = $res[0];
    }
    else
    {
//      всегда подразумеваем что у нас есть корневой комментарий с level = 0
        $res = sql_query('SELECT MAX(c_right) FROM sb_comments WHERE c_el_id=?d AND c_plugin=?', $el_id, $pl_ident);

//      правое значение и уровень корневого узла(комментария, реально не существующего)
        $right = (isset($res[0][0]) ? $res[0][0] + 1 : 2);
        $level = 0;
    }

    sql_query('LOCK TABLES sb_comments WRITE');
    sql_query('UPDATE sb_comments SET c_right = c_right + 2 WHERE c_el_id=?d AND c_plugin=? AND c_right >= ?d', $el_id, $pl_ident, $right);
    sql_query('UPDATE sb_comments SET c_left = c_left + 2 WHERE c_el_id=?d AND c_plugin=? AND c_left > ?d', $el_id, $pl_ident, $right);

    $level++;

    $row['c_left'] = $right;
    $row['c_right'] = ($right + 1);
    $row['c_level'] = $level;

    sql_query('INSERT INTO sb_comments (?#) VALUES (?a)', array_keys($row), array_values($row));
    $id = sql_insert_id();
    sql_query('UNLOCK TABLES');

    if(!isset($id) || !$id)
    {
        if (isset($row['c_file']) && trim($row['c_file']) != '' && $GLOBALS['sbVfs']->is_file($row['c_file']))
        {
            $GLOBALS['sbVfs']->delete($row['c_file']);
        }
        $GLOBALS['sb_c_hash_'.$hash] = array();
        $GLOBALS['sb_c_hash_'.$hash]['add_error'] = true;
        return false;
    }
    else
    {
        // сбрасываем кэш модуля
        $GLOBALS['sb_c_hash_'.$hash] = array();
        $GLOBALS['sb_c_hash_'.$hash]['add_ok'] = $id;

        $GLOBALS['sbCache']->mCacheOff = true;
    }

    if($moderate_email != '')
    {
        $row['c_id'] = $id;
        fComments_Send_Email($row, $ctl_fields_temps, $ctl_messages, $ctl_lang, $ctl_user_data_id, $moderate_email);
    }

    if ($row['c_show'] == 1)
        return true;

    return false;
}

/**
 * Отправка письма модератору
 *
 * @param array $row Массив значений полей.
 * @param array $ctl_fields_temps Массив макетов дизайна полей.
 * @param array $ctl_messages Массив макетов дизайна сообщений.
 * @param string $ctl_lang Язык макета дизайна.
 * @param int $ctl_user_data_id Идентификатор макета дизайна вывода данных пользователя.
 * @param bool $moderate_email E-Mail адреса модераторов (через запятую).
 * @param int $user_id - идентификатор пользователя добавившего комментарий, используеться при редактировании
 * @param string $user_ip - IP адрес пользователя добавившего комментарий, используеться при редактировании
 */
function fComments_Send_Email($row, &$ctl_fields_temps, &$ctl_messages, $ctl_lang, $ctl_user_data_id, $moderate_email, $user_id = 0, $user_ip = '')
{
    if (trim($ctl_messages['admin_subj']) == '' || trim($ctl_messages['admin_text']) == '')
        return;

    $moderate_email = str_replace(',', ' ', $moderate_email);
    $moderate_email = explode(' ', $moderate_email);
    $c_user_ip = '';
    if(isset($row['c_ip']))
        $c_user_ip = $row['c_ip'];
    elseif($user_ip != '')
        $c_user_ip = $user_ip;
    $c_user_id = 0;
    if(isset($row['c_user_id']) && $row['c_user_id'] > 0)
        $c_user_id = $row['c_user_id'];
    elseif($user_id > 0)
        $c_user_id = $user_id;

    $dop_tags = array('{AUTHOR}', '{EMAIL}', '{USER_ID}', '{ID}');
    $dop_values = array();
    $dop_values[] = $row['c_author'];
    $dop_values[] = $row['c_email'];
    $dop_values[] = $c_user_id;
    $dop_values[] = $row['c_id'];

    $tags = array('{DOMAIN}',
                '{COMMENTS_ID}',
                '{COMMENTS_DATE}',
                '{COMMENTS_AUTHOR}',
                '{COMMENTS_EMAIL}',
                '{COMMENTS_SUBJ}',
                '{COMMENTS_TEXT}',
                '{COMMENTS_AUTHOR_IP}',
                '{COMMENTS_FILE}',
                '{COMMENTS_USER_ID}',
                '{COMMENTS_USER_DATA}');

    $values = array();
    $values[] = SB_COOKIE_DOMAIN; // DOMAIN
    $values[] = $row['c_id']; // COMMENTS_ID
    $values[] = str_replace($dop_tags, $dop_values, sb_parse_date($row['c_date'], $ctl_fields_temps['ctl_date_mes'], $ctl_lang)); // COMMENTS_DATE
    $values[] = (trim($row['c_author']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($row['c_author'])), $ctl_fields_temps['ctl_author_mes']) : ''); // COMMENTS_AUTHOR
    $values[] = (trim($row['c_email']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($row['c_email'])), $ctl_fields_temps['ctl_email_mes']) : ''); // COMMENTS_EMAIL
    $values[] = (trim($row['c_subj']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($row['c_subj'])), $ctl_fields_temps['ctl_subj_mes']) : ''); // COMMENTS_SUBJ

    if(trim($row['c_text']) != '' && isset($ctl_fields_temps['ctl_bb_codes']) && $ctl_fields_temps['ctl_bb_codes'] == 1)
    {
        $row['c_text'] = sbProgParseBBCodes($row['c_text'], $ctl_fields_temps['ctl_quote_top'], $ctl_fields_temps['ctl_quote_bottom']);
    }

    $values[] = (trim($row['c_text']) != '' ? str_replace(array_merge($dop_tags, array('{VALUE}')), array_merge($dop_values, array($row['c_text'])), $ctl_fields_temps['ctl_text_mes']) : ''); // COMMENTS_TEXT
    $values[] = $c_user_ip; // COMMENTS_AUTHOR_IP

    if (isset($row['c_file']) && trim($row['c_file']) != '')
    {
        $size = '';
        if (sb_strpos($ctl_fields_temps['ctl_file'], '{SIZE}') !== false)
        {
            $img_src = $row['c_file'];
            if (stripos($img_src, 'http://') !== false)
            {
                $img_src = substr($img_src, 8);
                $img_src = substr($img_src, strpos($img_src, '/'));
            }

            if ($GLOBALS['sbVfs']->exists($img_src))
            {
                $size = $GLOBALS['sbVfs']->filesize($img_src);
            }
        }

        $values[] = str_replace(array_merge($dop_tags, array('{VALUE}', '{NAME}', '{SIZE}')), array_merge($dop_values, array($row['c_file'], $row['c_file_name'], $size)), $ctl_fields_temps['ctl_file']); // COMMENTS_FILE
    }
    else
    {
        $values[] = ''; // COMMENTS_FILE
    }

    $values[] = $c_user_id; // COMMENTS_USER_ID

    if ($c_user_id > 0 && (sb_strpos($ctl_messages['admin_text'], '{COMMENTS_USER_DATA}') !== false || sb_strpos($ctl_messages['admin_subj'], '{COMMENTS_USER_DATA}') !== false) && $ctl_user_data_id > 0)
    {
        require_once(SB_CMS_PL_PATH.'/pl_site_users/prog/pl_site_users.php');
        $values[] = fSite_Users_Get_Data($ctl_user_data_id, $c_user_id);
    }
    else
    {
        $values[] = '';
    }

    if(isset($_REQUEST['c_edit_id']) && intval($_REQUEST['c_edit_id']) > 0)
        $email_subj = str_replace($tags, $values, $ctl_messages['admin_subj_edit']);
    else
        $email_subj = str_replace($tags, $values, $ctl_messages['admin_subj']);

    //чистим код от инъекций
    $email_subj = sb_clean_string($email_subj);

    ob_start();
    eval(' ?>'.$email_subj.'<?php ');
    $email_subj = trim(ob_get_clean());

    if(isset($_REQUEST['c_edit_id']) && intval($_REQUEST['c_edit_id']) > 0)
        $email_text = str_replace($tags, $values, $ctl_messages['admin_text_edit']);
    else
        $email_text = str_replace($tags, $values, $ctl_messages['admin_text']);

    //чистим код от инъекций
    $email_text = sb_clean_string($email_text);

    ob_start();
    eval(' ?>'.$email_text.'<?php ');
    $email_text = trim(ob_get_clean());

    if ($email_text != '' && $email_subj != '')
    {
        require_once(SB_CMS_LIB_PATH.'/sbMail.inc.php');
        $mail = new sbMail();

        $type = sbPlugins::getSetting('sb_letters_type');

        $mail->setSubject($email_subj);   // Тема письма
        if ($type == 'html')
        {
            $mail->setHtml($email_text);
        }
        else
        {
            $mail->setText(strip_tags(preg_replace('=<br.*?/?>=i', '', $email_text)));
        }
        $mail->send($moderate_email);
    }
}

/**
 * Добавление комментария
 *
 * @param int $temp_id ID-макета дизайна вывода комментариев.
 * @param string $pl_ident Идентификатор модуля.
 * @param int $el_id Идентификатор элемента, для которого выводится список комментариев.
 * @param string $moderate_email E-Mail адреса модераторов.
 *
 * @return bool TRUE, если комментарий был добавлен, FALSE в ином случае.
 */
function fComments_Edit_Comment_Prog($temp_id, $pl_ident, $el_id, $moderate_email)
{
    $hash = md5($pl_ident.' - '.$el_id);
    if(isset($_REQUEST['c_edit_id']))
        $edit_id = intval($_REQUEST['c_edit_id']);

    if($temp_id <= 0 || isset($GLOBALS['sb_c_hash_'.$hash]) || !isset($_REQUEST['c_hash']) || isset($_REQUEST['c_id']) || isset($_REQUEST['c_answer_id']) || $_REQUEST['c_hash'] != $hash)
    {
        return false;
    }

    $res = sql_query('SELECT ctl_lang, ctl_fields_temps, ctl_form, ctl_messages, ctl_user_data_id FROM sb_comments_temps_list WHERE ctl_id = ?d', $temp_id);
    if(!$res)
    {
        sb_add_system_message(sprintf(KERNEL_PROG_NO_TEMPLATE, $_SERVER['PHP_SELF'], KERNEL_PROG_COMMENTS_FORM_PLUGIN), SB_MSG_WARNING);
        return false;
    }

    list($ctl_lang, $ctl_fields_temps, $ctl_form, $ctl_messages, $ctl_user_data_id) = $res[0];

    if ($ctl_fields_temps != '')
        $ctl_fields_temps = unserialize($ctl_fields_temps);
    else
        $ctl_fields_temps = array();

    if ($ctl_messages != '')
        $ctl_messages = unserialize($ctl_messages);
    else
        $ctl_messages = array();


    $row = array();
    if (!fComments_Check($row, $ctl_fields_temps, $ctl_messages, $ctl_form, $pl_ident, $el_id, true))
    {
        if (isset($row['c_file']) && trim($row['c_file']) != '' && $GLOBALS['sbVfs']->is_file($row['c_file']))
        {
            $GLOBALS['sbVfs']->delete($row['c_file']);
        }

        if (!isset($GLOBALS['sb_c_hash_'.$hash]))
        {
            $GLOBALS['sb_c_hash_'.$hash] = array();
            $GLOBALS['sb_c_hash_'.$hash]['fields_error'] = true;
        }
        return false;
    }

    $pl_ident = preg_replace('/[^A-Z0-9a-z_\-]/', '', $pl_ident);

    if(isset($row['c_subj']))
        $row['c_subj'] = nl2br($row['c_subj']);
    if(isset($row['c_text']))
        $row['c_text'] = nl2br($row['c_text']);
    $row['c_plugin'] = $pl_ident;
    $row['c_el_id'] = intval($el_id);

    if(isset($row['c_date']))
        $row['c_date'] = sb_datetoint($row['c_date'], $ctl_fields_temps['ctl_date']);

    // Существует ли редактируемый комментарий
    $res = sql_query('SELECT c_id, c_user_id, c_ip FROM sb_comments WHERE c_plugin=? AND c_el_id=?d AND c_id=?d',
                    $row['c_plugin'], $row['c_el_id'], $edit_id);
    if (!$res)
    {
        if (isset($row['c_file']) && trim($row['c_file']) != '' && $GLOBALS['sbVfs']->is_file($row['c_file']))
        {
            $GLOBALS['sbVfs']->delete($row['c_file']);
        }
        $GLOBALS['sb_c_hash_'.$hash] = array();
        $GLOBALS['sb_c_hash_'.$hash]['edit_error'] = true;
        return false;
    }
    $user_id = $res[0][1];
    $user_ip = $res[0][2];

    $res = sql_query('UPDATE sb_comments SET ?a WHERE c_id = ?d', $row, $edit_id);

    if(!$res)
    {
        if (isset($row['c_file']) && trim($row['c_file']) != '' && $GLOBALS['sbVfs']->is_file($row['c_file']))
        {
            $GLOBALS['sbVfs']->delete($row['c_file']);
        }
        $GLOBALS['sb_c_hash_'.$hash] = array();
        $GLOBALS['sb_c_hash_'.$hash]['edit_error'] = true;
        return false;
    }
    else
    {
        // сбрасываем кэш модуля
        $GLOBALS['sb_c_hash_'.$hash] = array();
        $GLOBALS['sb_c_hash_'.$hash]['edit_ok'] = $edit_id;

        $GLOBALS['sbCache']->mCacheOff = true;
    }

    if($moderate_email != '')
    {
        $row['c_id'] = $edit_id;
        fComments_Send_Email($row, $ctl_fields_temps, $ctl_messages, $ctl_lang, $ctl_user_data_id, $moderate_email, $user_id, $user_ip);
    }

    return true;
}

?>