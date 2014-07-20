<?php
/**
 * Инициализация основных переменных и настроек PHP
 *
 * В этом файле производится инициализация переменных и настроек PHP. Изменяются следующие настройки PHP:
 * <code>
 * ini_set('file_uploads', 'on');
 * ini_set('implicit_flush', 'off');
 * ini_set('magic_quotes_sybase', 'off');
 * ini_set('magic_quotes_runtime', 'off');
 * set_magic_quotes_runtime(false);
 * ini_set('max_execution_time', '300');
 * ini_set('register_globals', 'off');
 * ini_set('short_open_tag', 'off');
 * ini_set('zlib.output_compression_level', 3);
 * </code>
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

spl_autoload_register('sb_autoload');
spl_autoload_register('sb_ext_autoload');
require_once 'constants.inc.php';

define('SB_ADMIN_MODE', true);
define('SB_DEMO_SITE', false);

/**
 * Фнукция для аварийного выхода из системы с выводом сообщения пользователю
 *
 * @param string $str Сообщение, которое необходимо вывести.
 */
function sb_exit($str)
{
	header('Content-Language: '.(defined('SB_CMS_LANG') ? SB_CMS_LANG : 'ru').', en');
    header('Content-Type: text/html; charset='.(defined('SB_CHARSET') ? strtolower(SB_CHARSET) : 'utf-8'));
    header('Content-Script-Type: text/javascript; charset='.(defined('SB_CHARSET') ? strtolower(SB_CHARSET) : 'utf-8'));

    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset='.(defined('SB_CHARSET') ? strtolower(SB_CHARSET) : 'utf-8').'">
    </head>
    <body>
    <div style="text-align: center;font-family: Arial, Tahoma;font-size: 14px;color: red;margin: 20px;">
    '.$str.'
    </div>
    </body>
    </html>';

    exit (0);
}

/*
 * Защита от взлома
 */
if (isset($_REQUEST['GLOBALS']) || isset($_REQUEST['_SERVER']) || isset($_REQUEST['_SESSION']))
{
	sb_exit('');
}

if (isset($_SESSION) && !is_array($_SESSION))
{
	sb_exit('');
}

// PHP_SELF
if (isset($_SERVER['REQUEST_URI']))
{
	$_SERVER['PHP_SELF'] = preg_replace('/(\?.*)?$/', '', $_SERVER['REQUEST_URI']);
}
elseif (isset($_SERVER['SCRIPT_NAME']))
{
	$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
}

$_SERVER['PHP_SELF'] = preg_replace('#/+#', '/', str_replace('\\', '/', preg_replace('/[^0-9a-zA-Z\/\\\\_\-\.\:]+/', '', $_SERVER['PHP_SELF'])));

@ini_set('file_uploads', 'on');
@ini_set('implicit_flush', 'off');
@ini_set('magic_quotes_sybase', 'off');
@ini_set('magic_quotes_runtime', 'off');
@ini_set('magic_quotes_gpc', 'off');
//@set_magic_quotes_runtime(0); //Is deprecated

@ini_set('max_input_time', '-1');
@ini_set('register_globals', 'off');
@ini_set('short_open_tag', 'off');
@ini_set('display_errors', '0');

if (function_exists('memory_get_usage') && intval(@ini_get('memory_limit')) < 32)
	@ini_set('memory_limit', '32M');

if (intval(@ini_get('post_max_size')) < 16)
	@ini_set('post_max_size', '16M');

if (intval(@ini_get('upload_max_filesize')) < 16)
	@ini_set('upload_max_filesize', '16M');

if (intval(@ini_get('max_execution_time')) < 60)
	@ini_set('max_execution_time', '60');

@ini_set('session.use_cookies', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.cookie_lifetime', '0');

@ini_set('gd.jpeg_ignore_warning', '1');
@ini_set('zlib.output_compression_level', '3');
@ini_set('pcre.backtrack_limit', 1000000);

@ignore_user_abort(true);

header('Server: ');
header('X-Powered-By: ');

/**
 * @access private
 */
define('CONFIG_VALID_INCLUDE', true);

require_once(dirname(__FILE__).'/config.inc.php');

$sb_charset = (isset($GLOBALS['sb_charset']) ? strtoupper(preg_replace('/[^a-zA-Z\-0-9_]+/', '', $GLOBALS['sb_charset'])) : 'WINDOWS-1251');
switch ($sb_charset)
{
    case 'WINDOWS-1251':
        /**
         * Кодировка базы данных MySQL
         *
         * В зависимости от кодировки системы, выбираем кодировку базы данных MySQL.
         * Логично предположить, что если кодировка системы <i>WINDOWS-1251</i>, то кодировка базы
         * будет <i>cp1251</i>.
         */
        define('SB_DB_CHARSET', 'cp1251');
        break;
    case 'WINDOWS-1252':
        /**
         * @ignore
         */
        define('SB_DB_CHARSET', 'latin1');
        break;
    case 'UTF-8':
        /**
         * @ignore
         */
        define('SB_DB_CHARSET', 'utf8');
        break;
    default:
        /**
         * @ignore
         */
        define('SB_DB_CHARSET', 'cp1251');
        break;
}

/**
 * Кодировка системы
 */
define('SB_CHARSET', $sb_charset);

/**
 * Модификатор PCRE-выражений
 *
 * Если кодировка системы UTF-8, то во всех регулярных выражениях
 * необходимо использовать модификатор <i>u</i>.
 */
define('SB_PREG_MOD', ($GLOBALS['sb_charset'] == 'UTF-8' ? 'u' : ''));

unset($GLOBALS['sb_charset']);
unset($GLOBALS['sb_db_charset']);

require_once(dirname(__FILE__).'/../lang/prog_'.SB_DB_CHARSET.'.lng.php');

/**
 * Определяет домен, на котором была запущена система
 *
 * @return string Домен.
 */
function sb_get_host()
{
    $url = array();

    // сначала пытаемся отпарсить REQUEST_URI, возможно там есть полный путь
    if ( !empty($_SERVER['REQUEST_URI']) )
    {
        $pos = strpos($_SERVER['REQUEST_URI'], '?');
        if ($pos)
            $request_uri = substr($_SERVER['REQUEST_URI'], 0, $pos);
        else
            $request_uri = $_SERVER['REQUEST_URI'];

        $url = @parse_url($request_uri);
    }
    if (!$url) $url = array();
    // Если протокола нет, значит путь не полный
    // копаем глубже
    if ( !isset($url['scheme']) || empty($url['scheme']) )
    {
		if ( !empty($_SERVER['HTTP_X_FORWARDED_HOST']) )
		{
		    if ( strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ':') !== false )
	            {
	                list( $url['host'], $url['port'] ) = explode(':', $_SERVER['HTTP_X_FORWARDED_HOST']);
	            }
	            else
	            {
	                $url['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
	            }
		}
        elseif ( !empty($_SERVER['HTTP_HOST']) )
        {
            if ( strpos($_SERVER['HTTP_HOST'], ':') !== false )
            {
                list( $url['host'], $url['port'] ) = explode(':', $_SERVER['HTTP_HOST']);
            }
            else
            {
                $url['host'] = $_SERVER['HTTP_HOST'];
            }
        }
        elseif ( !empty($_SERVER['SERVER_NAME']) )
        {
            $url['host'] = $_SERVER['SERVER_NAME'];
        }
        else
        {
            return array();
        }
    }

    if (isset($url['host']))
    	$url['host'] = sb_strtolower($url['host']);

    return $url;
}

$url = sb_get_host();
if (count($url) == 0)
{
    sb_exit(KERNEL_PROG_INIT_ERROR_DOMAIN);
}

/**
 * Доменное имя сайта
 */
if (	(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' && $_SERVER['HTTPS'] != 'off') || 
	(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
	(isset($_SERVER['HTTP_X_SCHEME']) && sb_strtoupper($_SERVER['HTTP_X_SCHEME']) == 'HTTPS') ||
	(isset($_SERVER['HTTP_X_HTTPS']) && sb_strtoupper($_SERVER['HTTP_X_HTTPS']) == 'ON'))
{
	$protocol = 'https://';
	define('SB_HTTPS', true);
}
else
{
	$protocol = 'http://';
	define('SB_HTTPS', false);
}

$host = str_ireplace('www.', '', $url['host']);
$host = str_ireplace('www-demo.', '', $host);

if (!isset($GLOBALS['sb_domains'][$host]))
{
	$found = false;
    foreach ($GLOBALS['sb_domains'] as $conf_host => $value)
    {
        if (isset($value['pointers']) && in_array($host, $value['pointers']))
        {
        	if (!defined('SB_JSCRIPT'))
        	{
	        	if (substr_count($url['host'], 'www.') > 0)
	            	header('Location: '.sb_sanitize_header($protocol.'www.'.$conf_host.'/cms/admin/'));
	            else
	              	header('Location: '.sb_sanitize_header($protocol.$conf_host.'/cms/admin/'));

	            exit(0);
        	}
        	else
        	{
        		$host = $conf_host;
        		$found = true;
        	}
        }
    }

    if (!$found)
		sb_exit(sprintf(KERNEL_PROG_INIT_ERROR_DOMAIN_SETTINGS, $host));
}

$domain = $protocol.$url['host'];
if (isset($url['port']) && $url['port'] != '' && $url['port'] != '80')
    $domain .= ':'.$url['port'];

if (isset($url['port']))
    define('SB_PORT', $url['port']);
else
    define('SB_PORT', '80');

define('SB_DOMAIN', $domain);

/*
 * Абсолютный путь до рутовой директории HTTP-аккаунта
 *
 * Заменяем <samp>\</samp> на <samp>/</samp> (<samp>\</samp> работает только в Windows,
 * <samp>/</samp> работает как в Windows, так и в Unix системах). Удаляем последний <samp>/</samp>.
 *
 */
define('SB_BASEDIR', str_replace('\\', '/', rtrim($GLOBALS['sb_domains'][$host]['basedir'], '/\\')));

if (isset($GLOBALS['sb_domains'][$host]['ftp_basedir']))
{
    /*
     * Абсолютный путь до рутовой директории FTP-аккаунта
     *
     * Заменяем <samp>\</samp> на <samp>/</samp> (<samp>\</samp> работает только в Windows,
     * <samp>/</samp> работает как в Windows, так и в Unix системах). Удаляем последний <samp>/</samp>.
     *
     */
    define('SB_FTP_BASEDIR', str_replace(array('\\', ':'), array('/', ''), rtrim($GLOBALS['sb_domains'][$host]['ftp_basedir'], '/\\')));
}

/**
 * Доменное имя сайта для <i>cookie</i>
 */
define('SB_COOKIE_DOMAIN', $host);

@ini_set('session.cookie_domain', '.'.SB_COOKIE_DOMAIN);

/**
 * @ignore
 */

define('SB_HASH', md5('#j%84i#'.$GLOBALS['sb_db_database'].'#@&TmG8%'));

/**
 * Путь к файлам модулей системы
 *
 * Абсолютный путь к папке, в которой хранятся файлы модулей системы (используется для инклуда соотв. файлов).
 */
define('SB_CMS_PL_PATH', SB_BASEDIR.'/cms/plugins');

/**
 * Путь к библиотечным файлам системы
 *
 * Абсолютный путь к папке, в которой хранятся библиотечные файлы системы (используется для инклуда соотв. файлов).
 */
define('SB_CMS_LIB_PATH', SB_BASEDIR.'/cms/lib');

/**
 * Путь к файлам дополнительных расширений
 *
 * Абсолютный путь к папке, в которой хранятся файлы расширений
 */
define('SB_CMS_EXT_PATH', SB_BASEDIR.'/cms/extensions');

/**
 * Путь к кэш-файлам
 *
 * Абсолютный путь к папке, в которой хранятся кэш файлы
 */
define('SB_CMS_CACHE_PATH', SB_BASEDIR.'/cms/cache');


/**
 * Массив, содержащий все прописные буквы выбранного языка
 *
 * Используется для преобразования строк в нижний или верхний регистр. Также используется
 * для корректной работы шаблонизатора (теги шаблонов можно писать на своем языке, например <samp>{ВЕРХНЕЕ_МЕНЮ}</samp>).
 * <code>
 * $GLOBALS['sb_str_upper_interval'] = array_merge($GLOBALS['sb_str_upper_interval'],
 *       array('А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т',
 *       'У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я',' '));
 *   </code>
 * Инициализируется в файле:
 * @see strings.lng.php
 *
 * @global array $GLOBALS['sb_str_upper_interval']
 */
$GLOBALS['sb_str_upper_interval'] = array();

/**
 * Массив, содержащий все строчные буквы выбранного языка
 *
 * Используется для преобразования строк в нижний или верхний регистр. Последовательность
 * букв должна точно соответствовать последовательности букв в массиве $GLOBALS['sb_str_upper_interval'].
 * <code>
 * $GLOBALS['sb_str_lower_interval'] = array_merge($GLOBALS['sb_str_lower_interval'],
 *       'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т',
 *       'у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',' ');
 * </code>
 * Инициализируется в файле:
 * @see strings.lng.php, $GLOBALS['sb_str_upper_interval']
 *
 * @global array $GLOBALS['sb_str_lower_interval']
 */
$GLOBALS['sb_str_lower_interval'] = array();

/**
 * Массив, содержащий написание латиницей прописных букв выбранного языка
 *
 * Используется для преобразования строк в латиницу (например, при изменении имен файлов,
 * набранных русскими буквами, при загрузке на сервер). Последовательность
 * написаний должна точно соответствовать последовательности букв в массиве $GLOBALS['sb_str_upper_interval'].
 * <code>
 * $GLOBALS['sb_str_latupper_interval'] = array_merge($GLOBALS['sb_str_latupper_interval'],
 *       array('A','B','V','G','D','E','YO','ZH','Z','I','J','K','L','M','N','O','P','R','S','T',
 *       'U','F','H','TC','CH','SH','SH','_','I','_','E','YU','YA','_'));
 * </code>
 * Инициализируется в файле:
 * @see strings.lng.php, $GLOBALS['sb_str_latupper_interval']
 *
 * @global array $GLOBALS['sb_str_latupper_interval']
 */
$GLOBALS['sb_str_latupper_interval'] = array();

/**
 * Массив, содержащий написание латиницей строчных букв выбранного языка
 *
 * Используется для преобразования строк в латиницу (например, при изменении имен файлов,
 * набранных русскими буквами, при загрузке на сервер). Последовательность
 * написаний должна точно соответствовать последовательности букв в массиве $GLOBALS['sb_str_lower_interval'].
 * <code>
 * $GLOBALS['sb_str_latlower_interval'] = array_merge($GLOBALS['sb_str_latlower_interval'],
 *       array('a','b','v','g','d','e','yo','zh','z','i','j','k','l','m','n','o','p','r','s','t',
 *       'u','f','h','tc','ch','sh','sh','_','i','_','e','yu','ya','_'));
 * </code>
 * Инициализируется в файле:
 * @see strings.lng.php, $GLOBALS['sb_str_latlower_interval']
 *
 * @global array $GLOBALS['sb_str_latlower_interval']
 */
$GLOBALS['sb_str_latlower_interval'] = array();

/**
 * Строка, содержащая интервал строчных букв для использования в регулярных выражениях выбранного языка
 *
 * <code>
 * $GLOBALS['sb_reg_lower_interval'] = 'а-я';
 * </code>
 *
 * Инициализируется в файле:
 * @see strings.lng.php, $GLOBALS['sb_reg_lower_interval']
 *
 * @global string $GLOBALS['sb_reg_lower_interval']
 */
$GLOBALS['sb_reg_lower_interval'] = '';

/**
 * Строка, содержащая интервал прописных букв для использования в регулярных выражениях выбранного языка
 *
 * <code>
 * $GLOBALS['sb_reg_upper_interval'] = 'А-Я';
 * </code>
 *
 * Инициализируется в файле:
 * @see strings.lng.php, $GLOBALS['sb_reg_upper_interval']
 *
 * @global string $GLOBALS['sb_reg_upper_interval']
 */
$GLOBALS['sb_reg_upper_interval'] = '';

/**
 * Определяем язык системы и инклудим файлы, инициализирующие языковые массивы системы
 */
if (isset($_POST['sb_adm_lang']))
{
    $sb_adm_lang = preg_replace('/[^a-z_\-]+/', '', $_POST['sb_adm_lang']);

    if(!empty($sb_adm_lang) && (@is_dir(SB_BASEDIR.'/cms/lang/'.$sb_adm_lang.'_'.SB_DB_CHARSET) || @is_dir(SB_BASEDIR.'/cms/lang/'.$sb_adm_lang)))
    {
        /**
         * Язык системы (ru, en и т.п.)
         */
        define('SB_CMS_LANG', $sb_adm_lang);
    }
}
elseif (!isset($_COOKIE['sb_cms_lang']))
{
    if (is_array($GLOBALS['sb_cms_lang']))
    {
        /**
         * @ignore
         */
        define('SB_CMS_LANG', preg_replace('/[^a-z_\-]+/', '', $GLOBALS['sb_cms_lang'][0]['lang']));
    }
    else
    {
        /**
         * @ignore
         */
        define('SB_CMS_LANG', 'ru');
    }
}
else
{
    /**
     * @ignore
     */
    define('SB_CMS_LANG', preg_replace('/[^a-z_\-]+/', '', $_COOKIE['sb_cms_lang']));
}

/**
 * Путь к языковым файлам системы
 *
 * Абсолютный путь к папке, в которой хранятся языковые файлы системы (используется для инклуда соотв. файлов).
 */
if (@is_dir(SB_BASEDIR.'/cms/lang/'.SB_CMS_LANG.'_'.SB_DB_CHARSET))
{
    define('SB_CMS_LANG_PATH', SB_BASEDIR.'/cms/lang/'.SB_CMS_LANG.'_'.SB_DB_CHARSET);
    define('SB_CMS_LANG_URL', SB_DOMAIN.'/cms/lang/'.SB_CMS_LANG.'_'.SB_DB_CHARSET);
}
else
{
    define('SB_CMS_LANG_PATH', SB_BASEDIR.'/cms/lang/'.SB_CMS_LANG);
    define('SB_CMS_LANG_URL', SB_DOMAIN.'/cms/lang/'.SB_CMS_LANG);
}

if (is_array($GLOBALS['sb_cms_lang']))
{
    foreach ($GLOBALS['sb_cms_lang'] as $key => $value)
    {
        $lang = preg_replace('/[^a-z_\-]+/', '', $value['lang']);

        if (@is_dir(SB_BASEDIR.'/cms/lang/'.$lang.'_'.SB_DB_CHARSET))
            @include_once(SB_BASEDIR.'/cms/lang/'.$lang.'_'.SB_DB_CHARSET.'/strings.lng.php');
        else
            @include_once(SB_BASEDIR.'/cms/lang/'.$lang.'/strings.lng.php');
    }
}
else
{
    @include_once(SB_CMS_LANG_PATH.'/strings.lng.php');
}

/**
 * Путь к файлам ядра системы
 *
 * Абсолютный путь к папке, в которой хранятся файлы ядра системы (используется для инклуда соотв. файлов).
 */
define('SB_CMS_KERNEL_PATH', SB_BASEDIR.'/cms/kernel');

/**
 * Путь к файлам визуального редактора
 *
 * Относительный путь к файлам визуального редактора.
 */
define('SB_CMS_EDITOR_URL', '/cms/editor');

/**
 * Путь к временным файлам ядра системы
 *
 * Абсолютный путь к папке, в которой хранятся временные файлы ядра системы.
 */
define('SB_CMS_TMP_PATH', SB_BASEDIR.'/cms/tmp');

/**
 * Путь к файлам, загружаемым пользователями системы
 *
 * Абсолютный путь к файлам, загружаемым пользователями системы.
 */
define('SB_CMS_USER_UPLOAD_PATH', SB_BASEDIR.'/cms/upload');

/**
 * Полный URL файлов, загружаемых пользователями системы
 *
 * Используется для ссылок на эти файлы.
 */
define('SB_CMS_USER_UPLOAD_URL', SB_DOMAIN.'/cms/upload');

/**
 * Путь к файлам, загружаемым пользователями сайта
 *
 * Относительный путь к файлам, загружаемым пользователями сайта.
 */
define('SB_SITE_USER_UPLOAD_PATH', '/upload');

/**
 * Полный URL файлов, загружаемых пользователями сайта
 *
 * Используется для ссылок на эти файлы.
 */
define('SB_SITE_USER_UPLOAD_URL', SB_DOMAIN.'/upload');

/**
 * Путь к файлам с картинками
 */
define('SB_CMS_IMG_PATH', SB_BASEDIR.'/cms/images');

/**
 * Путь к файлам с персонажами
 */
define('SB_CMS_CHARACTERS_PATH', SB_BASEDIR.'/cms/characters');

/**
 * Полный URL к файлам с персонажами
 */
define('SB_CMS_CHARACTERS_URL', SB_DOMAIN.'/cms/characters');

/**
 * Полный URL к картинкам системы
 */
define('SB_CMS_IMG_URL', SB_DOMAIN.'/cms/images');

/**
 * Полный URL к яваскриптам системы
 */
define('SB_CMS_JSCRIPT_URL', SB_DOMAIN.'/cms/jscript');

/**
 * Полный URL к таблицам стилей системы
 *
 * Для каждого языка системы используются свои таблицы стилей, поскольку шрифты могут различаться.
 */
define('SB_CMS_CSS_URL', SB_CMS_LANG_URL.'/css');

/**
 * Полный URL файла верхнего фрейма системы
 */
define('SB_CMS_NAVMENU_FILE', SB_DOMAIN.'/cms/admin/navmenu.php');

/**
 * Полный URL основного файла для выполнения событий системы
 */
define('SB_CMS_CONTENT_FILE', SB_DOMAIN.'/cms/admin/content.php');

/**
 * Полный URL основного файла для подгрузки через AJAX
 */
define('SB_CMS_EMPTY_FILE', SB_DOMAIN.'/cms/admin/empty.php');

/**
 * Полный URL основного файла для немодальных диалоговых окон системы
 */
define('SB_CMS_DIALOG_FILE', SB_DOMAIN.'/cms/admin/dialog.php');

/**
 * Полный URL основного файла для модальных диалоговых окон системы
 */
define('SB_CMS_MODAL_DIALOG_FILE', SB_DOMAIN.'/cms/admin/modal_dialog.php');

unset($host);
unset($domain);

/**
 * Подсказки персонажа
 *
 * @var array
 */
$GLOBALS['sb_character_help'] = array();

/**
 * Функция для вывода отладочной информации
 *
 * Функция записывает значение переданной ей переменной в файл debug_output.txt или в пользовательский файл
 * в рутовой папке аккаунта.
 *
 * @param mixed $debug_var Переменная, значение которой будет записано в файл.
 * @param string $mode Режим записи в файл (<i>w+</i>, <i>a</i> и пр.).
 * @param string $file Имя файла, в который будет записана отладочная информация.
 */
function debug_output($debug_var, $mode = 'w+', $file = '')
{
    if(empty($file))
    {
        $file = SB_BASEDIR.'/debug_output.txt';
    }

    $fp = @fopen($file, $mode);
    if ($fp)
    {
        // получаем значение переменной в виде строки
        if($mode == 'a+')
        {
            @fwrite($fp, "\r\n");
        }
        @fwrite($fp, print_r($debug_var, true));
        @fclose($fp);
    }
}

/**
 * Автозагрузчик классов. Ищет файлы с классами в папке lib и в подпапках первого уровня
 *
 * @param string $class
 */
function sb_autoload($class)
{
    if(!class_exists($class))
    {
        $dirs = scandir(SB_CMS_LIB_PATH);
        if(!file_exists(SB_CMS_LIB_PATH . '/' . $class .'.inc.php'))
        {
            foreach($dirs as $dir)
            {
                if($dir == '.' || $dir == '..' || !is_dir(SB_CMS_LIB_PATH . '/' . $dir))
                {
                    continue;
                }
                elseif(is_file(SB_CMS_LIB_PATH . '/' . $dir . '/' . $class . '.inc.php'))
                {
                    require_once SB_CMS_LIB_PATH . '/' . $dir . '/' . $class . '.inc.php';
                }
            }
        }
        else
        {
            require_once SB_CMS_LIB_PATH . '/' . $class .'.inc.php';
        }
    }
}

/**
 * Автозагрузчик классов. Ищет файлы с классами в папке extensions и в подпапках первого уровня. Работает как со стороны сайта, так и со стороны системы
 *
 * @param string $class
 */
function sb_ext_autoload($class)
{
    if(!class_exists($class) && is_dir(SB_CMS_EXT_PATH))
    {
        $dirs = scandir(SB_CMS_EXT_PATH);
        if(!file_exists(SB_CMS_EXT_PATH . '/' . $class .'.php'))
        {
            foreach($dirs as $dir)
            {
                if($dir == '.' || $dir == '..' || !is_dir(SB_CMS_EXT_PATH . '/' . $dir))
                {
                    continue;
                }
                elseif(is_file(SB_CMS_EXT_PATH . '/' . $dir . '/' . $class . '.php'))
                {
                    require_once SB_CMS_EXT_PATH . '/' . $dir . '/' . $class . '.php';
                }
            }
        }
        else
        {
            require_once SB_CMS_EXT_PATH . '/' . $class .'.php';
        }
    }
}
?>