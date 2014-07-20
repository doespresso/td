<?php
/**
 * Подключение сжатия страниц сайта и инициализация основных библиотек ядра системы
 *
 * В данном файле идет отсылка следующих заголовков браузеру:
 * <code>
 * // Cообщаем браузеру кодировку сайта
 * header('Content-Language: '.SB_CMS_LANG.', en');
 * header('Content-Type: text/html; charset='.strtolower(SB_CHARSET));
 *
 * // Если браузер поддерживает GZip-компрессию, то сообщаем о том, что контент сжат
 * header('Content-Encoding: gzip');
 * </code>
 *
 * Также здесь мы устанавливаем перехват ошибок PHP нашей функцией, чтобы в дальнейшем
 * записывать ошибки в системный журнал:
 * <code>
 * set_error_handler('sb_php_error_reporting');
 * </code>
 *
 * @see SB_CMS_LANG, SB_CHARSET
 * @see function sbPHPErrorReporting()
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

ob_start();

// убираем GET-параметры модулей
if (isset($_SERVER['QUERY_STRING']))
{
	$GLOBALS['QUERY_STRING'] = $_SERVER['QUERY_STRING'];

	$get_str = $_SERVER['QUERY_STRING'];

	// пейджирование
	$get_str = preg_replace('/[?&]?page_[0-9A-Z_]+=[0-9]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?num_[0-9A-Z_]+=[0-9]+/i', '', $get_str);
	// пейджирование для тегов
	$get_str = preg_replace('/[?&]?page_t_[0-9]+=[0-9]+/i', '', $get_str);
	// пейджирование для комментариев
	$get_str = preg_replace('/[?&]?page_c_[0-9]+=[0-9]+/i', '', $get_str);

	// пользователи сайта
	$get_str = preg_replace('/[?&]?su_action=[A-Z0-9_\-]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?su_lm=[0-9\-]+/i', '', $get_str);

	// идентификаторы разделов
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_cid=[0-9]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_scid=[0-9]+/i', '', $get_str);

	// идентификаторы элементов
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_id=[0-9]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_sid=[0-9]+/i', '', $get_str);

	// облако тегов
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_tag=[0-9]+/i', '', $get_str);

	// форум
	$get_str = preg_replace('/[?&]?pl_forum_cat=[0-9]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?pl_forum_cat_sel=[0-9]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?pl_forum_sub=[0-9]+/i', '', $get_str);
	$get_str = preg_replace('/[?&]?pl_forum_theme=[0-9]+/i', '', $get_str);

	// цитирование
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_hash=[A-Za-z0-9_]+/i', '', $get_str);

	// идентификатор пользователя
	$get_str = preg_replace('/[?&]?[A-Z0-9_\-]+_uid=[\-0-9]+/i', '', $get_str);

	// голосования
	$get_str = preg_replace('/[?&]?v_sum=[A-Za-z0-9_]+/i', '', $get_str);

	// индексация поиском
	$get_str = preg_replace('/[?&]?sb_search=1/i', '', $get_str);

	// индексация для поиском
	$get_str = preg_replace('/[?&]?sb_sitemap=1/i', '', $get_str);

	$_SERVER['QUERY_STRING'] = str_replace('?', '%3f', trim($get_str, '&?'));
	$GLOBALS['QUERY_STRING'] = str_replace('?', '%3f', trim($GLOBALS['QUERY_STRING'], '?&'));
	unset($get_str);
}
else
{
	$GLOBALS['QUERY_STRING'] = '';
	$_SERVER['QUERY_STRING'] = '';
}

// Вспомогательные функции ядра системы
require_once(dirname(__FILE__).'/../../lib/sbFunctions.inc.php');
require_once(dirname(__FILE__).'/../../lib/prog/sbFunctions.inc.php');
require_once(dirname(__FILE__).'/init.inc.php');

/*
 * Сообщаем браузеру кодировку страницы
 */
header('Content-Language: '.SB_CMS_LANG.', en');
header('Content-Type: '.(defined('SB_PAGE_CONTENT_TYPE') ? SB_PAGE_CONTENT_TYPE : 'text/html').'; charset='.strtolower(SB_CHARSET));

/*
 * Подключаем обязательные файлы ядра системы
 */
// Основные константы системы
require_once(SB_CMS_KERNEL_PATH.'/data.inc.php');
// Языковые константы ядра системы
require_once(SB_CMS_LANG_PATH.'/kernel.lng.php');

if (!function_exists('sb_anti_xss'))
{
	function sb_anti_xss(&$var)
	{
		// критическая ошибка PHP версий ниже 5.2.17 и 5.3.5
		$var = str_replace(array('2.2250738585072011', '22250738585072011'), '', $var);
		$var = trim(sb_htmlspecialchars(sbProgCleanXSS($var), ENT_QUOTES));
	}
}

foreach(array('_GET', '_POST', '_COOKIE', '_REQUEST') as $a)
{
	if(!empty(${$a}))
	{
		array_walk_recursive(${$a}, 'sb_anti_xss');
	}
}

if ($_SERVER['QUERY_STRING'] != '')
	$_SERVER['QUERY_STRING'] = trim(str_replace(array('<', '>'), array('&lt;', '&gt;'), sbProgCleanXSS($_SERVER['QUERY_STRING'])));

// Подключение библиотеки работы с файловой системой
require_once(SB_CMS_LIB_PATH.'/sbVfs.inc.php');

/*
 * Устанавливаем перехват ошибок PHP нашей функцией, чтобы в дальнейшем
 * записывать ошибки в системный журнал.
 */

set_error_handler('sb_php_error_reporting');
register_shutdown_function('sb_php_shutdown');

/*
 * Если все-таки включено экранирование GPC-массивов (мы пытаемся отключить его в config.inc.php через ini_set),
 * то убираем экранирование из значений этих массивов
 */
if (get_magic_quotes_gpc())
{
    sb_array_stripslashes($_POST);
    sb_array_stripslashes($_GET);
    sb_array_stripslashes($_COOKIE);
    sb_array_stripslashes($_FILES);
    sb_array_stripslashes($_REQUEST);
}

// Подключение библиотеки работы с СУБД
require_once(SB_CMS_LIB_PATH.'/sbSql.inc.php');

// Подключение библиотеки работы с модулями системы
require_once(SB_CMS_LIB_PATH.'/sbPlugins.inc.php');

// Подключение библиотеки управления авторизацией пользователей сайта и сессиями пользователей сайта
require_once(SB_CMS_LIB_PATH.'/prog/sbAuth.inc.php');

if (class_exists('sbProfiler'))
{
	sbProfiler::init();
}

// Инициализация настроек системы
sbPlugins::initSettings();

if(!function_exists('sb_make_get_str'))
{
	function sb_make_get_str($get_name, $get_val, $lev)
	{
		$get_str = '';
		if(is_array($get_val))
		{
			foreach($get_val as $val)
			{
				if(is_array($val))
				{
					$get_str .= sb_make_get_str($get_name, $val, $lev + 1);
				}
				else
				{
					$get_str .= $get_name.str_repeat('[]', $lev).'='.urlencode($val).'&';
				}
			}
		}
		else
		{
			$get_str = $get_name.'='.urlencode($get_val).'&';
		}
		return $get_str;
	}
}

//	проверка, нужна переадресация или нет
if (!defined('SB_CRON') || !SB_CRON)
{
	$need_redirect = false;
	$m_pages = array();
	preg_match_all('/[?&]?(page_[0-9A-Za-z]+)=([0-9]+)/i', $GLOBALS['QUERY_STRING'], $m_pages);
	if (count($m_pages) > 0)
	{
		foreach ($m_pages[2] as $key => $value)
		{
			if ($value == 1)
			{
				unset($_GET[$m_pages[1][$key]]);
				$need_redirect = true;
			}
		}
	}

	$ar = explode('/', $_SERVER['PHP_SELF']);
	$ar_num = count($ar);

	if ($ar_num > 0 && strpos($ar[$ar_num - 1], '.') === false && sb_strlen($_SERVER['PHP_SELF']) == 0)
	{
		$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'].'/';
		$need_redirect = true;
	}

	$www_redirect = sbPlugins::getSetting('sb_www_redirect');
	$directory_index = sbPlugins::getSetting('sb_directory_index');
	if (trim($directory_index) == '')
	{
		$directory_index = 'index.php';
	}

	if ($ar[$ar_num - 1] == $directory_index && !( empty( $_SERVER['REQUEST_URI'] ) || ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ))
	{
		array_splice($ar, $ar_num - 1);
		$_SERVER['PHP_SELF'] = implode('/', $ar).'/';
		$need_redirect = true;
	}

	$domain = SB_DOMAIN;
	if ($www_redirect != 'none')
	{
		$pos = sb_stripos($domain, '://www.');
		if ($www_redirect == 'with_www' && $pos === false)
		{
			$domain = sb_str_replace('://', '://www.', $domain);
			$need_redirect = true;
		}
		elseif ($www_redirect == 'without_www' && $pos !== false)
		{
			$domain = sb_str_replace('://www.', '://', $domain);
			$need_redirect = true;
		}
	}

	if ($need_redirect && count($_POST) == 0 && $_SERVER['PHP_SELF'] != '/cms/admin/editor.php')
	{
		$get_str = '';
		foreach($_GET as $key => $value)
		{
			$get_str .= sb_make_get_str($key, $value, 1);
		}
		$get_str = trim($get_str, '&');
		header ('Location: '.sb_sanitize_header($domain.$_SERVER['PHP_SELF'].($get_str != '' ? '?'.$get_str : '')), true, 301);
		exit;
	}

	unset($domain);
	unset($m_pages);
	unset($need_redirect);
	unset($www_redirect);
}

if (basename($_SERVER['PHP_SELF']) == '' || substr($_SERVER['PHP_SELF'], -1) == '/')
{
	$_SERVER['PHP_SELF'] = preg_replace('#/+#', '/', $_SERVER['PHP_SELF'].'/'.sbPlugins::getSetting('sb_directory_index'));
}

if(isset($_GET['bid']) && $_GET['bid'] != '')
{
	sbProgBannerClick();
}

if ($GLOBALS['sb_demo_site'])
{
	$availIPs = trim(sbPlugins::getSetting('sb_demo_ip'));
	$error = false;
	if ($availIPs != '')
    {
        $error = true;
        $availIPs = explode(' ', $availIPs);
        $u_ip = sbAuth::getIP();
        foreach ($availIPs as $value)
        {
            if (trim($value) != '')
            {
                if (sbAuth::compareIP($value, $u_ip))
                {
                    $error = false;
                    break;
                }
            }
        }
    }

	if (!$error)
	{
		define('SB_DEMO_SITE', true);
	}
	else
	{
		define('SB_DEMO_SITE', false);
		sb_404(false);
	}
}
else
{
	define('SB_DEMO_SITE', false);
}

if (sbPlugins::getSetting('sb_debug_mode') == 1)
{
	restore_error_handler();
	@ini_set('display_errors', '1');
	$GLOBALS['sbSql']->mShowErrors = true;
}

if (isset($_SESSION['sbAuth']))
{
	$_SESSION['sbAuth']->checkSession();
}

$error = sbAuth::checkKey();

if ($error != '')
{
    sb_exit($error);
}

// Вход пользователя
if ((isset($_POST['su_pass']) && (isset($_POST['su_login']) || isset($_POST['su_email']))) || (isset($_COOKIE['sb_site_auto_login']) && trim($_COOKIE['sb_site_auto_login']) != '' && !isset($_SESSION['sbAuth']) && (!isset($_GET['su_action']) || $_GET['su_action'] != 'logout')))
{
	new sbAuth((isset($_POST['su_login']) ? $_POST['su_login'] : ''), (isset($_POST['su_email']) ? $_POST['su_email'] : ''), (isset($_POST['su_pass']) ? $_POST['su_pass'] : ''));
}
elseif (isset($_GET['su_action']) && $_GET['su_action'] == 'logout')
{
	sbAuth::doLogout();
}

//	отписываем пользователя от рассылки
if(isset($_GET['ml_unsub']) && $_GET['ml_unsub'] != '' && isset($_GET['ml_unsub_id']) && $_GET['ml_unsub_id'] != '')
{
	$res = sql_query('SELECT su_id, su_mail_subscription FROM sb_site_users WHERE MD5(CONCAT(\'*##\', su_id, \'##*\')) = ?', $_GET['ml_unsub']);
	if($res)
	{
		list($su_id, $su_mail_subscription) = $res[0];
		$su_mail_subscription = explode(',', $su_mail_subscription);

		$tmp_su_mail = array();
		foreach($su_mail_subscription as $key => $value)
		{
			$tmp_su_mail[$value] = $value;
		}
		$su_mail_subscription = $tmp_su_mail;

		unset($su_mail_subscription[intval($_GET['ml_unsub_id'])]);
		$su_mail_subscription = implode(',', $su_mail_subscription);

		sql_query('UPDATE sb_site_users SET su_mail_subscription = ? WHERE su_id =?d', $su_mail_subscription, $su_id);
	}
}

//	отписываем пользователя от подписки на форуме
if (isset($_GET['forum_unsub']) && $_GET['forum_unsub'] != '' && isset($_GET['forum_unsub_eid']) && $_GET['forum_unsub_eid'] != '')
{
	sql_query('DELETE FROM sb_forum_maillist WHERE MD5(CONCAT(\'*##\', sfm_email, \'##*\')) = ?
			AND MD5(CONCAT(\'*##\', sfm_theme_id, \'##*\')) = ?', $_GET['forum_unsub_eid'], $_GET['forum_unsub']);
}

sb_replace_php($_POST);
sb_replace_php($_GET);
sb_replace_php($_COOKIE);
sb_replace_php($_REQUEST);

$GLOBALS['sbVfs']->mFileMode = octdec(sbPlugins::getSetting('sb_file_rights'));
$GLOBALS['sbVfs']->mDirMode = octdec(sbPlugins::getSetting('sb_folder_rights'));

require_once(SB_CMS_LIB_PATH.'/sbCache.inc.php');
require_once(SB_CMS_LIB_PATH.'/sbCachePage.inc.php');
if(class_exists('sbCachePage'))
{
    sbCachePage::getInstance()->check();
}

$GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'];

?>