<?php
/**
 * Файл отвечает за редирект ЧПУ
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
require_once(dirname(__FILE__).'/header.inc.php');

// Для IIS с PHP ISAPI
if ( empty( $_SERVER['REQUEST_URI'] ) || ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {

    // IIS Mod-Rewrite
    if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
    {
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
    }
    // IIS Isapi_Rewrite
    else if (isset($_SERVER['HTTP_X_REWRITE_URL']))
    {
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
    }
    else
    {
        // Используем ORIG_PATH_INFO, если нет PATH_INFO
        if ( !isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']) )
            $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

        if ( isset($_SERVER['PATH_INFO']) )
        {
            if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
                $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
            else
                $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
        }

        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
        {
            $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
        }
    }
}

$uri = isset($_SERVER['REDIRECT_REQUEST_URI']) ? $_SERVER['REDIRECT_REQUEST_URI'] : $_SERVER['REQUEST_URI'];
$uri = urldecode($uri);

$query = '';
if (($pos = sb_strpos($uri, '?')) !== false)
{
    $query = sb_substr($uri, $pos + 1);
    $uri = trim(sb_substr($uri, 0, $pos));
}

$uri = str_replace('\\', '/', preg_replace('/[^0-9a-zA-Z'.$GLOBALS['sb_reg_lower_interval'].$GLOBALS['sb_reg_upper_interval'].'\/\\\\_\-\.\:]+/'.SB_PREG_MOD, '', $uri));
$ar = explode('/', $uri);
$ar_num = count($ar);

// Обрабатываем модуль редиректов
$redirect_res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_title=? AND cat_level=1 AND cat_ident=?', SB_KEY_DOMAIN, 'pl_redirect');
if ($redirect_res)
{
    list($cat_left, $cat_right) = $redirect_res[0];

    $redirect_res = sql_query('SELECT s.sr_id, s.sr_old_url, s.sr_new_url, s.sr_redirect_type
									 FROM sb_redirect s, sb_categs c, sb_catlinks l
									 WHERE s.sr_active = 1
									 AND s.sr_id = l.link_el_id
									 AND l.link_cat_id = c.cat_id
									 AND c.cat_ident = ?
									 AND c.cat_left >= ?d
									 AND c.cat_right <= ?d
									 AND (s.sr_old_url LIKE ? OR s.sr_old_url LIKE ? {OR s.sr_old_url LIKE ?} {OR s.sr_old_url LIKE ?})
									 ORDER BY s.sr_id DESC', 'pl_redirect', $cat_left, $cat_right, '/'.trim($uri, '/'), '/'.trim($uri, '/').'/', ($query == '' ? SB_SQL_SKIP : '/'.trim($uri, '/').'?%'), ($query == '' ? SB_SQL_SKIP : '/'.trim($uri, '/').'/?%'));
    if ($redirect_res)
    {
        if ($query == '')
        {
            list($sr_id, $sr_old_url, $sr_new_url, $sr_redirect_type) = $redirect_res[0];
            sql_query('UPDATE sb_redirect SET sr_count=sr_count + 1 WHERE sr_id=?d', $sr_id);
            $sr_new_url = (sb_strpos($sr_new_url, '?') === false && sb_strpos($sr_new_url, '.php') === false && substr($sr_new_url, -1) != '/') ? $sr_new_url.'/' : $sr_new_url;
            header ('Location: '.sb_sanitize_header($sr_new_url), true, $sr_redirect_type);
            exit(0);
        }
        else
        {
            $query_params = explode('&', $query);
            $redirect_url = '';
            $redirect_type = '';
            $max_found = -1;
            $max_query = 1000000;
            $sr_real_id = -1;

            foreach ($redirect_res as $value)
            {
                list($sr_id, $sr_old_url, $sr_new_url, $sr_redirect_type) = $value;

                $pos = sb_strpos($sr_old_url, '?');
                $found = 0;

                $sr_old_query = array();

                if ($pos)
                {
                    $sr_old_query = explode('&', sb_substr($sr_old_url, $pos + 1));

                    foreach ($query_params as $query_param)
                    {
                        foreach ($sr_old_query as $old_query_param)
                        {
                            if (sb_strtolower($query_param) == sb_strtolower($old_query_param))
                            {
                                $found++;
                                break;
                            }
                        }
                    }
                }

                if ($found > $max_found || $found == $max_found && $max_query > count($sr_old_query))
                {
                    $sr_real_id = $sr_id;
                    $max_found = $found;
                    $redirect_type = $sr_redirect_type;
                    $redirect_url = $sr_new_url;
                    $max_query = count($sr_old_query);
                }
            }

            if ($max_found > -1)
            {
                sql_query('UPDATE sb_redirect SET sr_count=sr_count + 1 WHERE sr_id=?d', $sr_real_id);
                $redirect_url = (sb_strpos($redirect_url, '?') === false && sb_strpos($redirect_url, '.php') === false && substr($redirect_url, -1) != '/') ? $redirect_url.'/' : $redirect_url;
                header ('Location: '.sb_sanitize_header($redirect_url), true, $redirect_type);
                exit(0);
            }
        }
    }
}

if ($uri != '' && count($_POST) == 0 && $ar_num > 0 && sb_strpos($ar[$ar_num - 1], '.') === false && sb_substr($uri, -1) != '/')
{
    header ('Location: '.sb_sanitize_header($uri.'/'.($query != '' ? '?'.$query : '')), true, 301);
    exit(0);
}

if ($ar_num == 0 || sbPlugins::getSetting('sb_static_urls') != 1)
{
    sb_404();
}

$pl_id_key = '';
$ext = '';
$filename = '';

$dot_count = sb_substr_count($ar[$ar_num - 1], '.');
if ($dot_count >= 2)
{
    $ext_pos = sb_strrpos($ar[$ar_num - 1], '.');
    $ext = sb_substr($ar[$ar_num - 1], $ext_pos + 1);
    $ar[$ar_num - 1] = sb_substr($ar[$ar_num - 1], 0, -1 - sb_strlen($ext));

    $pl_id_pos = sb_strrpos($ar[$ar_num - 1], '.');
    $pl_id_key = preg_replace('/[^A-Za-z0-9_\-]+/', '', sb_substr($ar[$ar_num - 1], $pl_id_pos + 1));
    $ar[$ar_num - 1] = sb_substr($ar[$ar_num - 1], 0, -1 - sb_strlen($pl_id_key));

    if (count($_POST) == 0 && $pl_id_key != 'su')
    {
        if (sb_strtolower($ext) == 'php')
        {
            header ('Location: '.sb_sanitize_header(SB_DOMAIN.implode('/', $ar).($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '')), true, 301);
            exit(0);
        }
        else
        {
            header ('Location: '.sb_sanitize_header(SB_DOMAIN.implode('/', $ar).'.'.$ext.($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '')), true, 301);
            exit(0);
        }
    }
}
elseif ($dot_count == 1)
{
    $ext_pos = sb_strrpos($ar[$ar_num - 1], '.');
    $ext = sb_substr($ar[$ar_num - 1], $ext_pos + 1);
    $ar[$ar_num - 1] = sb_substr($ar[$ar_num - 1], 0, -1 - sb_strlen($ext));
}

$no_ext = false;
if ($ext == '')
{
    $no_ext = true;
    $ext = 'php';
}

$GLOBALS['sbVfs']->mLocal = true;
$path = '';
foreach ($ar as $key => $dir)
{
    $dir = trim($dir);
    if ($dir == '.' || $dir == '..' || $dir == '')
    {
        continue;
    }

    if (!$GLOBALS['sbVfs']->exists(SB_BASEDIR.$path.'/'.$dir))
    {
        if (!$GLOBALS['sbVfs']->exists(SB_BASEDIR.$path.'/'.$dir.'.'.$ext))
        {
            if ($no_ext)
            {
                $index_page = sbPlugins::getSetting('sb_directory_index');
                if (trim($index_page) == '')
                {
                    $index_page = 'index.php';
                }

                $ext_idx_pos = sb_strrpos($index_page, '.');
                $ext_idx = sb_substr($index_page, $ext_idx_pos + 1);

                $filename = $path.'/'.$index_page;

                if ($ext_idx != $ext || !$GLOBALS['sbVfs']->exists(SB_BASEDIR.$filename))
                {
                    sb_404();
                }
            }
            else
            {
                sb_404();
            }
        }
        else
        {
            $ar[$key] = '';
            $filename = $path.'/'.$dir.'.'.$ext;
        }

        break;
    }

    $ar[$key] = '';
    $path .= '/'.$dir;
}
$GLOBALS['sbVfs']->mLocal = false;

$tmp_ar = array();
foreach ($ar as $value)
{
    if ($value != '')
    {
        $tmp_ar[] = $value;
    }
}

$ar = $tmp_ar;
$num = count($ar);
if ($num < 1)
{
    sb_404();
}

// первым всегда идет раздел или год
if (sb_strlen($ar[0]) != 5 || sb_substr($ar[0], 0, 1) != 'y' || (sb_substr($ar[0], 0, 1) == 'y' && !is_numeric(sb_substr($ar[0], 1))))
{
    // раздел
    if ($pl_id_key == '')
    {
        if ($ar[0] == preg_replace('/[^0-9]+/', '', $ar[0]))
        {
            $res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_id=?d', $ar[0]);
            if (!$res)
            {
                $res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_url=?', $ar[0]);
                if (!$res)
                {
                    sb_404();
                }
            }
        }
        else
        {
            $res = sql_query('SELECT cat_ident FROM sb_categs WHERE cat_url=?', $ar[0]);
            if (!$res)
            {
                sb_404();
            }
        }

        list($pl_id_key) = $res[0];

        if ($pl_id_key == 'pl_services_rutube')
        {
            $pl_id_key = 'rutube';
        }
        elseif ($pl_id_key == 'pl_site_users')
        {
            $pl_id_key = 'su';
        }
        elseif (sb_substr($pl_id_key, 0, 10) == 'pl_plugin_')
        {
            // для конструктора модулей
            $pl_id_key = 'pl'.sb_substr($pl_id_key, 10);
        }
        else
        {
            $pl_id_key = sb_str_replace('pl_', '', $pl_id_key);
        }
    }

    $_GET[$pl_id_key.'_scid'] = $ar[0];
}
else
{
    // год
    $_GET['sb_year'] = sb_substr($ar[0], 1);
}

if (isset($_GET['sb_year']) && $num > 1)
{
    // если год, то дальше идет месяц и день или только месяц
    if (sb_strlen($ar[1]) == 3 && sb_substr($ar[1], 0, 1) == 'm' && is_numeric(sb_substr($ar[1], 1)))
    {
        // месяц
        $_GET['sb_month'] = sb_substr($ar[1], 1);
    }
    else
    {
        sb_404();
    }

    if ($num > 2)
    {
        // день
        if (sb_strlen($ar[2]) == 3 && sb_substr($ar[2], 0, 1) == 'd' && is_numeric(sb_substr($ar[2], 1)))
        {
            // день
            $_GET['sb_day'] = sb_substr($ar[2], 1);
        }
        else
        {
            sb_404();
        }

        if ($num > 3)
        {
            sb_404();
        }
    }
}
elseif ($num > 1)
{
    // первым был раздел, далее может идти год или элемент
    if (sb_strlen($ar[1]) != 5 || sb_substr($ar[1], 0, 1) != 'y' || (sb_substr($ar[1], 0, 1) == 'y' && !is_numeric(sb_substr($ar[1], 1))))
    {
        // элемент
        $_GET[$pl_id_key.'_sid'] = $ar[1];

        if ($num > 2)
        {
            sb_404();
        }
    }
    else
    {
        // год
        $_GET['sb_year'] = sb_substr($ar[1], 1);
    }

    if (isset($_GET['sb_year']) && $num > 2)
    {
        // если год, то дальше идет месяц и день или только месяц
        if (sb_strlen($ar[2]) == 3 && sb_substr($ar[2], 0, 1) == 'm' && is_numeric(sb_substr($ar[2], 1)))
        {
            // месяц
            $_GET['sb_month'] = sb_substr($ar[2], 1);
        }
        else
        {
            sb_404();
        }

        if ($num > 3)
        {
            // день
            if (sb_strlen($ar[3]) == 3 && sb_substr($ar[3], 0, 1) == 'd' && is_numeric(sb_substr($ar[3], 1)))
            {
                // день
                $_GET['sb_day'] = sb_substr($ar[3], 1);
            }
            else
            {
                sb_404();
            }

            if ($num > 4)
            {
                sb_404();
            }
        }
    }
}

$_SERVER['PHP_SELF'] = preg_replace('#/+#', '/', str_replace('\\', '/', preg_replace('/[^0-9a-zA-Z%'.$GLOBALS['sb_reg_lower_interval'].$GLOBALS['sb_reg_upper_interval'].'\/\\\\_\-\.\:]+/'.SB_PREG_MOD, '', $filename)));

$GLOBALS['PHP_SELF'] = preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']);
$GLOBALS['PHP_SELF'] = preg_replace('#/+#', '/', str_replace('\\', '/', preg_replace('/[^0-9a-zA-Z%'.$GLOBALS['sb_reg_lower_interval'].$GLOBALS['sb_reg_upper_interval'].'\/\\\\_\-\.\:]+/'.SB_PREG_MOD, '', $GLOBALS['PHP_SELF'])));

if (isset($_POST['su_pass']) && sbAuth::getErrorCode() == -1)
{
    header('Location: '.sb_sanitize_header($GLOBALS['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '')));
    exit(0);
}

$_SERVER['QUERY_STRING'] = preg_replace('/[?&]?'.$pl_id_key.'_scid=[A-Za-z0-9_\-]+/i'.SB_PREG_MOD, '', $_SERVER['QUERY_STRING']);
$_SERVER['QUERY_STRING'] = preg_replace('/[?&]?'.$pl_id_key.'_sid=[A-Za-z0-9_\-]+/i'.SB_PREG_MOD, '', $_SERVER['QUERY_STRING']);
$_SERVER['QUERY_STRING'] = preg_replace('/[?&]?sb_day=[A-Za-z0-9_\-]+/i'.SB_PREG_MOD, '', $_SERVER['QUERY_STRING']);
$_SERVER['QUERY_STRING'] = preg_replace('/[?&]?sb_month=[A-Za-z0-9_\-]+/i'.SB_PREG_MOD, '', $_SERVER['QUERY_STRING']);
$_SERVER['QUERY_STRING'] = preg_replace('/[?&]?sb_year=[A-Za-z0-9_\-]+/i'.SB_PREG_MOD, '', $_SERVER['QUERY_STRING']);

include_once(SB_BASEDIR.$filename);

require_once(SB_PROG_KERNEL_PATH.'/footer.inc.php');