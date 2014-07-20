<?php
/**
 * Вывод формы логина и авторизация внутри системы
 *
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Подключение сжатия страниц внутри системы и инициализация основных библиотек ядра системы
 */

$dir = substr(dirname(__FILE__), 0, -5).'kernel';
require_once($dir.'/header.inc.php');

sb_setcookie('sb_cms_lang', SB_CMS_LANG);

/**
 * Подключение языковых констант
 */
require_once(SB_CMS_LANG_PATH.'/index.lng.php');

if (isset($_POST['sb_adm_login']))
{
    /**
     * Пользователь ввел логин и пароль и пытается авторизоваться в системе.
     * Форма авторизации отправляется через AJAX, поэтому здесь только вывод
     * ошибок.
     *
     * Проверяем, есть ли IP-адрес пользователя в сессии. Если нет, то либо
     * попытка взлома, либо не работают сессии.
     */
    if (array_key_exists('sb_cms_open_key', $_SESSION))
    {
        if(!isset($_POST['sb_adm_pass']))
        {
            // Пароля нет
            exit (INDEX_NO_PASSWORD);
        }

        $_POST['sb_adm_login'] = trim($_POST['sb_adm_login']);
        $_POST['sb_adm_pass'] = trim($_POST['sb_adm_pass']);

        /**
         * Вычищаем логин и пароль от посторонних символов
         */
        $sb_adm_login = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $_POST['sb_adm_login']);
        if ($sb_adm_login != $_POST['sb_adm_login'])
        {
            exit(SB_AUTH_WRONG_LOGIN);
        }
        $sb_adm_pass = preg_replace('/["\']+/', '', $_POST['sb_adm_pass']);

        if(empty($sb_adm_login) || empty($sb_adm_pass))
        {
            exit (INDEX_NO_PASSWORD_OR_USER_EMPTY);
        }

        /**
         * Создаем в сессии класс sbAuth
         */
        $_SESSION['sbAuth'] = new sbAuth($sb_adm_login, $sb_adm_pass, $_SESSION['sb_cms_open_key']);
        if ($_SESSION['sbAuth']->mError != '')
        {
            $error = $_SESSION['sbAuth']->mError;
            unset($_SESSION['sbAuth']);

            exit ($error);
        }

        $_SESSION['sbQuickToolbar'] = array();
        $_SESSION['sbPlugins'] = new sbPlugins();

        // общие настройки для всех сайтов
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_wrong_pass_count', INDEX_KERNEL_SETTING_WRONG_PASS_COUNT, 'number', '', '10', 'all', false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_session_timeout', INDEX_KERNEL_SETTING_SESSION_TIMEOUT, 'number', '', '60', 'all', false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_report_user_status', INDEX_KERNEL_SETTING_USER_STATUS, 'checkbox', '', '1', 'all', false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_strip_domain', INDEX_KERNEL_SETTING_STRIP_DOMAIN, 'checkbox', '', '1', 'all', false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_debug_mode', INDEX_KERNEL_SETTING_DEBUG_MODE, 'checkbox', '', '0', 'all', false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_xmlrpc_delim', INDEX_KERNEL_SETTING_XMLRPC_HEADER, '', '', '', 'all', false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_xmlrpc_calls', INDEX_KERNEL_SETTING_XMLRPC_CALLS, 'checkbox', '', '0', 'all', false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_xmlrpc_edit_limit', INDEX_KERNEL_SETTING_XMLRPC_EDIT_LIMIT, 'number', '', '50', 'all', false);

        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_site_name', INDEX_KERNEL_SETTING_SITE_NAME, 'string', '', '{DOMAIN}', SB_COOKIE_DOMAIN, false);

        $www_redirect_type_ar = array();
        $www_redirect_type_ar['with_www'] = INDEX_KERNEL_SETTING_WWW_REDIRECT_TYPE_WITH_WWW;
        $www_redirect_type_ar['without_www'] = INDEX_KERNEL_SETTING_WWW_REDIRECT_TYPE_WITHOUT_WWW;
        $www_redirect_type_ar['none'] = INDEX_KERNEL_SETTING_WWW_REDIRECT_TYPE_NONE;

        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_www_redirect', INDEX_KERNEL_SETTING_WWW_REDIRECT, 'select', $www_redirect_type_ar, 'none', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_directory_index', INDEX_KERNEL_SETTING_DIRECTORY_INDEX, 'string', '', 'index.php', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_404_page', INDEX_KERNEL_SETTING_404_PAGE, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_admin_ip', INDEX_KERNEL_SETTING_ADMIN_IP, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_site_ip', INDEX_KERNEL_SETTING_SITE_IP, 'string', '', '', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_admin_soap_ip', INDEX_KERNEL_SETTING_ADMIN_SOAP_IP, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_deny_index', INDEX_KERNEL_SETTING_DENY_INDEX, 'checkbox', '', '0', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_site_hash', INDEX_KERNEL_SETTING_SITE_HASH, 'string', '', md5(SB_COOKIE_DOMAIN), SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_preload_id', INDEX_KERNEL_SETTING_PRELOAD_ID, 'checkbox', '', '0', SB_COOKIE_DOMAIN, false);

        //Настройки профилирования модулей и SQL-запросов
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_profiler_delim', INDEX_KERNEL_SETTING_PROFILER_DESC, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_profiler_plugins', INDEX_KERNEL_SETTING_PROFILER_PL, 'checkbox', '0', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_profiler_sql', INDEX_KERNEL_SETTING_PROFILER_SQL, 'checkbox', '0', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_profiler_ip', INDEX_KERNEL_SETTING_PROFILER_IP, 'string', '', '', SB_COOKIE_DOMAIN, false);

		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_gzip_delim', INDEX_KERNEL_SETTING_GZIP_HEADER, '', '', '', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_html_compress', INDEX_KERNEL_SETTING_HTML_COMPRESS, 'checkbox', '', '1', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_gzip_compress', INDEX_KERNEL_SETTING_GZIP_COMPRESS, 'checkbox', '', '1', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_urls_delim', INDEX_KERNEL_SETTING_URLS_HEADER, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_static_urls', INDEX_KERNEL_SETTING_STATIC_URLS, 'checkbox', '', '1', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_russian_static_urls', INDEX_KERNEL_SETTING_RUSSIAN_URLS, 'checkbox', '', '0', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_strtolower_static_urls', INDEX_KERNEL_SETTING_STRTOLOWER_URLS, 'checkbox', '', '0', SB_COOKIE_DOMAIN, false);
        
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_cache_module_delim', INDEX_KERNEL_SETTING_CACHE_MODULE_HEADER, '', '', '', SB_COOKIE_DOMAIN, false);
        
        $cacheList = array(
            SB_CACHE_DONT_USE => INDEX_KERNEL_SETTING_DONTUSECACHE,
            SB_CACHE_DB => INDEX_KERNEL_SETTING_DBCACHE,
        );

        if(class_exists('Memcache')){
            $cacheList[SB_CACHE_MEMCACHE] = INDEX_KERNEL_SETTING_MEMCACHE;
        }
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache', INDEX_KERNEL_SETTING_USE_CACHE, 'select', $cacheList, '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_mcache_delim', '', '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_cookie', INDEX_KERNEL_SETTING_USE_CACHE_COOKIE, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_cache_total', INDEX_KERNEL_SETTING_PAGES_CACHE, 'number', '', '300', SB_COOKIE_DOMAIN, false);

		/*
		 * Использование MemCache
		 */
        if(class_exists('Memcache')){
            $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_mcache_zip', INDEX_KERNEL_SETTING_USE_MCACHE_ZIP, 'checkbox', '', '0', SB_COOKIE_DOMAIN, false);
            $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_mcache_host', INDEX_KERNEL_SETTING_USE_MCACHE_HOST, 'string', '', 'localhost', SB_COOKIE_DOMAIN, false);
            $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_mcache_port', INDEX_KERNEL_SETTING_USE_MCACHE_PORT, 'string', '', '11211', SB_COOKIE_DOMAIN, false);
        }
        
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_cache_page_delim', INDEX_KERNEL_SETTING_CACHE_PAGE_HEADER, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_page', INDEX_KERNEL_SETTING_CACHE_PAGE_USE, 'checkbox', '', '0', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_page_time', INDEX_KERNEL_SETTING_CACHE_PAGE_VALIDITY_TIME, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_page_rpage', INDEX_KERNEL_SETTING_CACHE_PAGE_RPAGE, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_page_rget', INDEX_KERNEL_SETTING_CACHE_PAGE_RGET, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_page_rcookie', INDEX_KERNEL_SETTING_CACHE_PAGE_RCOOKIE, 'string', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_use_cache_page_use_cookie', INDEX_KERNEL_SETTING_CACHE_PAGE_USE_COOKIE, 'string', '', '', SB_COOKIE_DOMAIN, false); 
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_CACHE_SETTING, 'sb_memcache_descr', INDEX_KERNEL_SETTING_USE_MCACHE_DESCR, '', '', '', SB_COOKIE_DOMAIN, false);
        
        
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_delim_top', INDEX_KERNEL_SETTING_LETTERS_HEADER, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_charset', INDEX_KERNEL_SETTING_LETTERS_CHARSET, 'string', '', 'WINDOWS-1251', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_images', INDEX_KERNEL_SETTING_LETTERS_IMAGES, 'checkbox', '', '1', SB_COOKIE_DOMAIN, false);

        $letters_ar = array();
        $letters_ar['html'] = INDEX_KERNEL_SETTING_LETTERS_TYPE_HTML;
        $letters_ar['text'] = INDEX_KERNEL_SETTING_LETTERS_TYPE_TEXT;

		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_type', INDEX_KERNEL_SETTING_LETTERS_TYPE, 'select', $letters_ar, 'html', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_submit_timeout', INDEX_KERNEL_SETTING_SUBMIT_TIMEOUT, 'number', '', '300000', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_delim_bottom', '', '', '', '', SB_COOKIE_DOMAIN, false);

		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_admin_email', INDEX_KERNEL_SETTING_ADMIN_EMAIL, 'string', '', 'info@{DOMAIN}', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_smtp_host', INDEX_KERNEL_SETTING_LETTERS_SMTP_HOST, 'string', '', '', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_smtp_user', INDEX_KERNEL_SETTING_LETTERS_SMTP_USER, 'string', '', '', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_letters_smtp_password', INDEX_KERNEL_SETTING_LETTERS_SMTP_PASSWORD, 'password', '', '', SB_COOKIE_DOMAIN, false);

        $time_zones = array();
        $time_zones[-12] = INDEX_KERNEL_SETTING_TIMEZONE_12;
        $time_zones[-11] = INDEX_KERNEL_SETTING_TIMEZONE_11;
        $time_zones[-10] = INDEX_KERNEL_SETTING_TIMEZONE_10;
        $time_zones[-9] = INDEX_KERNEL_SETTING_TIMEZONE_9;
        $time_zones[-8] = INDEX_KERNEL_SETTING_TIMEZONE_8;
        $time_zones[-7] = INDEX_KERNEL_SETTING_TIMEZONE_7;
        $time_zones[-6] = INDEX_KERNEL_SETTING_TIMEZONE_6;
        $time_zones[-5] = INDEX_KERNEL_SETTING_TIMEZONE_5;
        $time_zones[-4] = INDEX_KERNEL_SETTING_TIMEZONE_4;
        $time_zones[-3] = INDEX_KERNEL_SETTING_TIMEZONE_3;
        $time_zones[-2] = INDEX_KERNEL_SETTING_TIMEZONE_2;
        $time_zones[-1] = INDEX_KERNEL_SETTING_TIMEZONE_1;
        $time_zones[0] = INDEX_KERNEL_SETTING_TIMEZONE0;
        $time_zones[1] = INDEX_KERNEL_SETTING_TIMEZONE1;
        $time_zones[2] = INDEX_KERNEL_SETTING_TIMEZONE2;
        $time_zones[3] = INDEX_KERNEL_SETTING_TIMEZONE3;
        $time_zones[4] = INDEX_KERNEL_SETTING_TIMEZONE4;
        $time_zones[5] = INDEX_KERNEL_SETTING_TIMEZONE5;
        $time_zones[6] = INDEX_KERNEL_SETTING_TIMEZONE6;
        $time_zones[7] = INDEX_KERNEL_SETTING_TIMEZONE7;
        $time_zones[8] = INDEX_KERNEL_SETTING_TIMEZONE8;
        $time_zones[9] = INDEX_KERNEL_SETTING_TIMEZONE9;
        $time_zones[10] = INDEX_KERNEL_SETTING_TIMEZONE10;
        $time_zones[11] = INDEX_KERNEL_SETTING_TIMEZONE11;
        $time_zones[12] = INDEX_KERNEL_SETTING_TIMEZONE12;

        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_regional_standarts', INDEX_KERNEL_SETTING_DATE_TIME_HEADER, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_timezone', INDEX_KERNEL_SETTING_TIMEZONE, 'select', $time_zones, '3', SB_COOKIE_DOMAIN, false);

        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_new_version', INDEX_KERNEL_SETTING_VERSION, '', '', '', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_new_version_js_descr', INDEX_KERNEL_SETTING_VERSION_DESCR, '', '', '', SB_COOKIE_DOMAIN, false);

		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_new_version_css', INDEX_KERNEL_SETTING_VERSION_CSS, 'number', '', 1, SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_DOMAIN_SETTING, 'sb_new_version_js', INDEX_KERNEL_SETTING_VERSION_JS, 'number', '', 1, SB_COOKIE_DOMAIN, false);
 
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_google_delim', INDEX_KERNEL_SETTING_GOOGLE, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_google_analytics_id', INDEX_KERNEL_SETTING_GOOGLE_ANALYTICS_ID, 'string', '', '', SB_COOKIE_DOMAIN, false);

        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_yandex_delim', INDEX_KERNEL_SETTING_YANDEX, '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_yandex_maps_id', INDEX_KERNEL_SETTING_YANDEX_MAPS_ID, 'string', '', '', SB_COOKIE_DOMAIN, false);
		$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_yandex_maps_metrika', INDEX_KERNEL_SETTING_YANDEX_MAPS_METRIKA, 'string', '', '', SB_COOKIE_DOMAIN, false);

        $type_list = array('string' => INDEX_CAPTCHA_TYPE_STRING,
                           'img' => INDEX_CAPTCHA_TYPE_IMG,
                           'color' => INDEX_CAPTCHA_TYPE_COLOR);

        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_type', INDEX_CAPTCHA_TYPE_CAPTCHA, 'select', $type_list, 'color', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_fon_color', INDEX_CAPTCHA_FON_COLOR, 'string', '', '#A6D9BA #C7CCD0 #F8E4ED #BDBDBB', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_delim1', '', '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_shum_lines', INDEX_CAPTCHA_SHUM_LINES, 'checkbox', '0', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_shum_points', INDEX_CAPTCHA_SHUM_POINTS, 'checkbox', '1', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_fon_shum_colors', INDEX_CAPTCHA_FON_SHUM_COLORS, 'string', '', '#A6D9BA #C7CCD0 #F8E4ED #BDBDBB', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_delim2', '', '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_width', INDEX_CAPTCHA_WIDTH, 'number', '', '170', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_height', INDEX_CAPTCHA_HEIGHT, 'number', '', '50', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_delim3', '', '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_fsize_from', INDEX_CAPTCHA_FONT_SIZE_FROM, 'number', '', '25', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_fsize_to', INDEX_CAPTCHA_FONT_SIZE_TO, 'number', '', '45', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_code_length_from', INDEX_CAPTCHA_CODE_LENGTH_FROM, 'number', '', '100000', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_code_length_to', INDEX_CAPTCHA_CODE_LENGTH_TO, 'number', '', '999999', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_delim4', '', '', '', '', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_code_colors', INDEX_CAPTCHA_CODE_COLORS, 'string', '', '#3D4552 #2A4C55 #4E3F60 #57342E #255B37', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_code_fon_colors', INDEX_CAPTCHA_FON_TEXT_COLORS, 'string', '', '#3D4552 #2A4C55 #4E3F60 #57342E #255B37', SB_COOKIE_DOMAIN, false);

        $fonts = array();
        $GLOBALS['sbVfs']->mLocal = true;
        $GLOBALS['sbVfs']->opendir(SB_CMS_LANG_PATH.'/fonts');
        while(($file = $GLOBALS['sbVfs']->readdir(SB_CMS_LANG_PATH.'/fonts')) !== false)
        {
            if(strtolower(sb_substr($file, -4)) == '.ttf')
            {
                $fonts[$file] = $file;
            }
        }
        $GLOBALS['sbVfs']->mLocal = false;
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_fonts', INDEX_CAPTCHA_FONTS, 'select', $fonts, 'rubbfont.ttf', SB_COOKIE_DOMAIN, false);
        $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_CAPTCHA_SETTING, 'sb_captcha_img', INDEX_CAPTCHA_URL_IMG, 'file', 'jpg gif', '', SB_COOKIE_DOMAIN, false);

        // Модули системы
        require_once(SB_CMS_KERNEL_PATH.'/core_plugins.inc.php');

        // Пользовательские модули
        include_once(SB_CMS_KERNEL_PATH.'/plugins.inc.php');

        //Библиотечные плагины
        require_once(SB_CMS_PL_PATH.'/pl_editor/pl_editor.h.php'); // Диалоговые окна визуального редактора
        require_once(SB_CMS_PL_PATH.'/pl_folders/pl_folders.h.php'); // Работа с файлами и директориями в файловой панели
        require_once(SB_CMS_PL_PATH.'/pl_categs/pl_categs.h.php'); // Работа с разделами
        require_once(SB_CMS_PL_PATH.'/pl_kernel/pl_kernel.h.php'); // Главная страница системы (должна инклудится всегда последней из-за настроек интерфейса для контейнеров)
		require_once(SB_CMS_PL_PATH.'/pl_export/pl_export.h.php'); // Экспорт данных

        // Пользовательские настройки
        $toolbar_ar = array();
        $plugin_ar = array();

        if (count($_SESSION['sbPlugins']->mContainers) > 0)
            $plugin_ar['pl_kernel_read'] = INDEX_KERNEL_PLUGIN;
        if ($_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'elems_edit'))
            $plugin_ar['pl_pages_edit_content'] = INDEX_VISUAL_PLUGIN;

        // Считываем события, вынесенные в меню, и строим строку значений для выпадающего списка выбора события,
        // вызываемого после входа в систему, и событий, иконки которых будут вынесены на панель инструментов.
        foreach ($_SESSION['sbPlugins']->mMenu as $value)
        {
            $value['item'] = explode('>', $value['item']);
            if ($value['item'][1] == KERNEL_MENU_DEVELOP_DESIGN)
            	continue;

            $value['item'] = $value['item'][count($value['item']) - 1];
			$value['event'] = sb_str_replace(SB_CMS_CONTENT_FILE.'?event=', '', $value['event']);

            if ($value['icon'] != '')
                $toolbar_ar[$value['event']] = '&nbsp;<img src="'.str_replace('_24', '_16', $value['icon']).'" align="absmiddle">&nbsp;&nbsp;&nbsp;'.$value['item'];

            $plugin_ar[$value['event']] = $value['item'];
        }

        function cmp_ar($ar1, $ar2)
        {
            $ar1 = strip_tags($ar1);
            $ar2 = strip_tags($ar2);

            if ($ar1 < $ar2)
                return -1;
            if ($ar1 > $ar2)
                return 1;

            return 0;
        }

        uasort($plugin_ar, 'cmp_ar');
        uasort($toolbar_ar, 'cmp_ar');

        $_SESSION['sbPlugins']->addUserSetting(INDEX_USER_TOOLBAR_SETTING, 'sb_toolbar_qicons_count', INDEX_USER_SETTING_TOOLBAR_QICONS_COUNT, 'number', '', 3, false);
        $_SESSION['sbPlugins']->addUserSetting(INDEX_USER_TOOLBAR_SETTING, 'sb_toolbar_delim', '', '');
        $_SESSION['sbPlugins']->addUserSetting(INDEX_USER_TOOLBAR_SETTING, 'sb_toolbar_icons', INDEX_USER_SETTING_TOOLBAR, 'checkboxes', $toolbar_ar, array('pl_pages_init', 'pl_menu_init', 'pl_news_init', 'pl_messages_read', 'pl_user_settings_read', 'pl_filelist_init', 'pl_dumper_init'), false);

        $_SESSION['sbPlugins']->addUserSetting(INDEX_USER_SETTING, 'sb_start_plugin', INDEX_USER_SETTING_MAIN_PLUGIN, 'select', $plugin_ar, 'pl_kernel_read', false);

        // Считываем персонажей системы и устанавливаем персонаж по умолчанию
        $character_ar = array();
        $character_ar['-'] = INDEX_USER_SETTING_WITHOUT_CHARACTER;
        if ($handle = @opendir(SB_CMS_CHARACTERS_PATH))
        {
            while (false !== ($file = @readdir($handle)))
            {
                if ($file != '.' && $file != '..' && @is_dir(SB_CMS_CHARACTERS_PATH.'/'.$file))
                {
                    $character_ar[$file] = $file;
                }
            }
            @closedir($handle);
        }

        $_SESSION['sbPlugins']->addUserSetting(INDEX_USER_SETTING, 'sb_character', INDEX_USER_SETTING_CHARACTER, 'select', $character_ar, '-', false);

        if (!$_SESSION['sbPlugins']->isRegisteredPlugins())
        {
            // пользователю не доступен ни один пользовательский модуль системы
            $_SESSION['sbAuth']->doLogout(true);
            exit(INDEX_NO_PLUGINS);
        }
        $_SESSION['sbPlugins']->init();

        // убираем ненужные переменные сессии
        unset($_SESSION['sb_cms_user_ip']);
        unset($_SESSION['sb_cms_user_browser']);
        unset($_SESSION['sb_cms_open_key']);
    }
    else
    {
        echo KERNEL_NO_SESSIONS;
    }

    exit(0);
}
else if (isset($_SESSION['sbAuth']) && (!isset($_GET['logout']) || $_GET['logout'] != 1))
{
    // пользователь успешно залогинился, выводим интерфейс системы
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
          <html lang="ru">
          <head><title>'.INDEX_SYSTEM_TITLE.'</title>
          <meta http-equiv="Content-Script-Type" content="text/javascript">';

    $error = '';

    // Стартуем сессию пользователя
	$_SESSION['sbAuth']->updateEventSession();
	if ($_SESSION['sbAuth']->mError != '')
    {
		$error = $_SESSION['sbAuth']->mError;
		$_SESSION['sbAuth']->doLogout();
	}

    if ($error != '')
    {
        $error = str_replace('"', '\\"', $error);

        echo '<style type="text/css">
                img
                {
                    behavior: url("'.SB_CMS_JSCRIPT_URL.'/sbFixPng.htc.php");
                }
              </style>
              <link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbIndex.css">
              <script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
              <script src="'.SB_CMS_JSCRIPT_URL.'/sbIndex.js.php"></script>
              <script>
                  var sb_cms_img_url = "'.SB_CMS_IMG_URL.'";
              </script>
            </head>
            <body onload=\'sbShowMsgDiv("'.$error.'");\'>
            <div id="sb_msg_div" class="sb_msg_div">
                <table cellspacing="0" cellpadding="0" style="width: 390px;">
                <tr>
                    <td width="60">
                        <img src="'.SB_CMS_IMG_URL.'/warning.png" width="40" height="40" align="absmiddle" id="sb_msg_img" class="sb_msg_img" />
                    </td>
                    <td align="center" style="font-weight: bold;" id="sb_msg_div_text" class="sb_msg_div_text"></td>
                </tr>
                </table>
            </div>
            </body></html>';
        exit(0);
    }

    if (SB_CLIENT_VALIDITY != -1)
    {
    	// Если срок демонстрационного периода скоро истекает, предупреждаем об этом пользователя
        $demo_time = SB_CLIENT_VALIDITY - time();
        $day_sec = 24 * 60 * 60;
        if ($demo_time > $day_sec)
        {
            $num_days = ceil($demo_time / $day_sec);
            echo '<script>'.sprintf('alert("'.INDEX_EXPIRE_DAYS.'");', $num_days).'</script>';
        }
        else
        {
            $num_hours = ceil($demo_time / (60 * 60));
            echo '<script>'.sprintf('alert("'.INDEX_EXPIRE_HOURS.'");', $num_hours).'</script>';
        }
    }

    $start_plugin = sbPlugins::getUserSetting('sb_start_plugin');
    if (is_null($start_plugin))
    {
        $start_plugin = 'pl_kernel_read';
    }

    if (($start_plugin == 'pl_pages_edit_content' && !$_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'elems_edit'))
         || ($start_plugin != 'pl_pages_edit_content' && !$_SESSION['sbPlugins']->isEventAvailable($start_plugin))
         || ($start_plugin == 'pl_kernel_read' && count($_SESSION['sbPlugins']->mContainers) <= 0))
    {
    	if (strtolower(substr($_SESSION['sbPlugins']->mMenu[0]['event'], 0, 10)) != 'javascript')
        	$start_plugin = sb_str_replace(SB_CMS_CONTENT_FILE.'?event=', '', $_SESSION['sbPlugins']->mMenu[0]['event']);
        else
        	$start_plugin = $_SESSION['sbPlugins']->mMenu[0]['event'];
    }

    if (!isset($_SESSION['sb_event']))
    {
	    if ($start_plugin == 'pl_pages_edit_content')
	    {
	    	$index_page = sbPlugins::getSetting('sb_directory_index');
		    if (trim($index_page) == '')
		    {
		    	$index_page = 'index.php';
		    }

		    $res = sql_query('SELECT p_id FROM sb_pages WHERE p_filepath=? AND p_filename=?', '', $index_page);
		    if (!$res)
		    	$res = sql_query('SELECT p_id FROM sb_pages ORDER BY p_id LIMIT 1');

		    if ($res)
		    {
		    	echo '<script>
		    	var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_edit_content&p_id='.$res[0][0].'";
		        var strAttr = "resizable=1, width="+screen.availWidth+", height="+screen.availHeight;

		        window.open(strPage, "_blank", strAttr);
		    	</script>';

		    	$start_plugin = 'pl_pages_init&page='.$res[0][0];
		    }
		    else
		        $start_plugin = 'pl_pages_init';
	    }
    }
    else
    {
    	$start_plugin = $_SESSION['sb_event'];
    }

    echo '<script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
    	<script src="'.SB_CMS_JSCRIPT_URL.'/sbMainMenu.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbMainMenuData.js.php"></script>
        <script>
		var sbMainMenu = new PopupMenu("sbMainMenu");
        with (sbMainMenu)
        {'.
            $_SESSION['sbPlugins']->getMenu().'
        }
        sbMenuSelectBoxFix(sbMainMenu);
        sbMenuAddBorder(sbMainMenu, window.subM, null, "#000000", 1, "#FFFFFF", 2);

        sbSetCookie("sb_screenw", screen.width, "", "/", "'.SB_COOKIE_DOMAIN.'");
        sbSetCookie("sb_screenh", screen.height, "", "/", "'.SB_COOKIE_DOMAIN.'");

        </script>
        </head>
        <frameset rows="64, *" border="0" frameborder="0">
            <frame scrolling="no" noresize="noresize" frameborder="0" name="navmenu" src="'.SB_CMS_NAVMENU_FILE.'"></frame>
            <frame scrolling="no" name="content" noresize="noresize" frameborder="0" src="'.SB_CMS_CONTENT_FILE.'?event='.$start_plugin.'"></frame>
        </frameset>
        </html>';
}
else
{
    /**
     * Пользователь вышел из системы, либо пытается зайти в систему
     */
    if (isset($_SESSION['sbAuth']) && isset($_GET['logout']) && $_GET['logout'] == 1)
    {
        $_SESSION['sbAuth']->doLogout();
        header('Location: '.sb_sanitize_header($_SERVER['PHP_SELF'].'?action=logout'));
        exit(0);
    }

    /**
     * Убиваем переменные сессии
     */
    $_SESSION = array();
    $_SESSION['sb_cms_open_key'] = md5(uniqid(rand(), true));

    // вывод страницы c формой логина
    echo '
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html lang="ru">
    <head>
    <title>'.INDEX_TITLE.'</title>
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <style type="text/css">
    img
    {
        behavior: url("'.SB_CMS_JSCRIPT_URL.'/sbFixPng.htc.php");
    }
    </style>
    <link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbIndex.css">
    <script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
    <script src="'.SB_CMS_JSCRIPT_URL.'/sbAJAX.js.php"></script>
    <script src="'.SB_CMS_JSCRIPT_URL.'/sbMD5.js.php"></script>
    <script src="'.SB_CMS_JSCRIPT_URL.'/sbIndex.js.php"></script>';

    $auto_login = '';

    if (isset($_COOKIE['sb_auto_login']) && trim($_COOKIE['sb_auto_login']) != '' && (!isset($_GET['action']) || $_GET['action'] != 'logout'))
    {
    	$res = sql_query('SELECT u_login, u_pass FROM sb_users WHERE u_auto_login=?', $_COOKIE['sb_auto_login']);
    	if ($res)
    	{
    		$auto_login = $res[0][0];
    		$auto_pass = md5($res[0][1].md5($_SESSION['sb_cms_open_key']));
    	}
    }

    if ($auto_login == '')
    {
	    echo '<script>
	    var openKey = "'.$_SESSION['sb_cms_open_key'].'";
	    var sb_cms_img_url = "'.SB_CMS_IMG_URL.'";
	    var sb_auto_login = false;
	    var sb_check_ok = false;

	    function sbCheckParams()
	    {
	        if(navigator.cookieEnabled == false)
	        {
	            sbShowMsgDiv("'.INDEX_BROWSER_INCORRECT_COOKIE.'");
	            sb_check_ok = false;
	            return;
	        }
	        else
	        {
	            if(!window.XMLHttpRequest)
	            {
	                try
	            	{
	            	    new ActiveXObject("Msxml2.XMLHTTP");
	            	}
	            	catch (e)
	            	{
	            	    try
	            	    {
	            	        new ActiveXObject("Microsoft.XMLHTTP");
	                    }
	            		catch (e)
	            		{
	            		    sbShowMsgDiv("'.INDEX_BROWSER_INCORRECT_ACTIVEX.'");
	                        sb_check_ok = false;
	            			return;
	            		}
	            	}
	            }
	        }

	        sbGetE("sb_adm_login").focus();
	        sb_check_ok = true;
	        return;
	    }
	    </script>
	    </head>';

	    require_once(SB_CMS_LIB_PATH.'/sbCheckBrowser.inc.php');

	    if ((browser_is_ie() || browser_is_firefox() || browser_is_opera() || browser_is_safari() || browser_is_chrome()) &&
	    	( ((!defined('SB_DB_TYPE') || SB_DB_TYPE == 'mysql') && (extension_loaded('mysql') || extension_loaded('mysqli'))) ||
	    	  (defined('SB_DB_TYPE') && SB_DB_TYPE == 'mssql' && extension_loaded('sqlsrv')) ) &&
	    	extension_loaded('iconv') && extension_loaded('pcre') && version_compare('5.0.0', PHP_VERSION) <= 0)
	    {
	    	require_once(SB_CMS_LIB_PATH.'/sbDB.inc.php');

        	$plugins = array();
        	$error = sbKeyCheck($plugins);

	        // Если все расширения PHP подгружены и версия PHP > 5
	        echo '<body onload="sbCheckParams();">
	            <form id="sb_login_form" action="'.SB_DOMAIN.'/cms/admin/" method="post" onsubmit="return sbMakePass();">
	            <table cellpadding="0" cellspacing="0" style="height:100%;width:100%;">
	            <tr><td valign="middle" align="center">';

	        if (CMS_SITE_NAME == 'www.sbuilder.ru')
	            echo '<div class="login_img">
	                    <a href="http://www.sbuilder.ru" target="_blank"><img src="'.SB_CMS_IMG_URL.'/login.png" class="login_img" width="161" height="207" /></a>
	                </div>';

	        echo '<table cellpadding="0" cellspacing="0" class="form" width="330">
	              <tr>
	                <th class="header" colspan="2">
	                  '.INDEX_TITLE_FORM.'
	                </th>
	              </tr>
	              <tr>
	                <th width="80" style="padding-top: 15px;">'.INDEX_USER.':</th>
	                <td style="padding-top: 15px;"><input type="text" name="sb_adm_login" id="sb_adm_login" value="'.(SB_CLIENT_LIC == '0400000000000000000' ? 'demo' : '').'" tabIndex="1" style="width: 200px;"></td>
	              </tr>
	              <tr>
	                  <th>'.INDEX_PASSWORD.':</th>
	                  <td>
	                    <input type="password" id="sb_pass" value="'.(SB_CLIENT_LIC == '0400000000000000000' ? 'demo' : '').'" tabIndex="2" style="width: 200px;">
	                    <input type="hidden" id="sb_adm_pass" name="sb_adm_pass">
	                  </td>
	              </tr>';

	        if (is_array($GLOBALS['sb_cms_lang']) && count($GLOBALS['sb_cms_lang']) > 1)
	        {
	            echo '<tr>
	                  <th>'.INDEX_LANG.':</td>
	                  <td>
	                    <select name="sb_adm_lang" tabIndex="3">';
	            foreach ($GLOBALS['sb_cms_lang'] as $key => $value)
	            {
	                if ($value['lang'] == SB_CMS_LANG)
	                {
	                    echo '<option value="'.$value['lang'].'" selected="selected">'.$value['desc'].'</option>';
	                }
	                else
	                {
	                    echo '<option value="'.$value['lang'].'">'.$value['desc'].'</option>';
	                }
	            }
	            echo '</select>
	                  </td>
	              </tr>';
	        }
	        else
	        {
	            echo '<input type="hidden" name="sb_adm_lang" value="'.SB_CMS_LANG.'">';
	        }

	        echo '<tr>
	                <td colspan="2" style="padding-right: 25px;text-align: right;"><input type="checkbox" value="1" name="sb_auto_login" id="sb_auto_login" value="" tabIndex="4"'.(isset($_COOKIE['sb_auto_login']) && trim($_COOKIE['sb_auto_login']) != '' ? ' checked="checked"' : '').' /> <label for="sb_auto_login">'.INDEX_AUTO_LOGIN.'</label></td>
	              </tr>
	              <tr>
	    			  <td colspan="2" class="delim"></td>
	    		  </tr>
	              <tr>
	                <td colspan="2" class="footer">
	                    <div class="pass_link"><a href="/cms/admin/reminder.php">'.INDEX_FORGET_PASSWORD.'</a></div>
	                    <div class="footer"><input type="submit" tabIndex="5" value="'.INDEX_ENTER.'"></div>
	                </td>
	              </tr>
	              </table>
	            </td></tr>';

	        if (CMS_SITE_NAME == 'www.sbuilder.ru')
	        {
	            echo '<tr><td class="links">
	                <a href="http://art.sbuilder.ru" target="_blank">'.INDEX_KNOW_LINK.'</a><br>
	                <a href="http://api.sbuilder.ru" target="_blank">'.INDEX_API_LINK.'</a><br>
	                <a href="http://www.sbuilder.ru/forum/" target="_blank">'.INDEX_FORUM_LINK.'</a><br>
	                <a href="http://www.sbuilder.ru" target="_blank">'.INDEX_SITE_LINK.'</a><br>
	                '.INDEX_COPYRIGHT.'
	            </td></tr>';
	        }

	        echo '</table></form>
	            <div id="sb_msg_div" class="sb_msg_div"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" id="sb_msg_img" /></td><td id="sb_msg_div_text" class="sb_msg_div_text"></td></tr></table></div>';
	    }
	    else
	    {
	        echo '<center>';

	        if(version_compare('5.0.0', PHP_VERSION) > 0)
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_NO_PHP_5.'</td></tr></table></div>';
	        }
	        elseif((!defined('SB_DB_TYPE') || SB_DB_TYPE == 'mysql') && !extension_loaded('mysql') && !extension_loaded('mysqli'))
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_NO_PLUGIN_MYSQL.'</td></tr></table></div>';
	        }
	    	elseif(defined('SB_DB_TYPE') && SB_DB_TYPE == 'mssql' && !extension_loaded('sqlsrv'))
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_NO_PLUGIN_MSSQL.'</td></tr></table></div>';
	        }
	        elseif (!extension_loaded('iconv'))
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_NO_PLUGIN_ICONV.'</td></tr></table></div>';
	        }
	    	elseif (!extension_loaded('mbstring'))
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_NO_PLUGIN_MBSTRING.'</td></tr></table></div>';
	        }
	        elseif(!extension_loaded('pcre'))
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_NO_PLUGIN_PREG.'</td></tr></table></div>';
	        }
	        else
	        {
	            echo '<div id="sb_msg_div" class="sb_msg_div" style="display: block;position: relative;"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" /></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.(CMS_SITE_NAME == 'www.sbuilder.ru' ? INDEX_BROWSER_INCORRECT : INDEX_BROWSER_INCORRECT_DEALER).'</td></tr></table></div>';
	        }
	        echo '</center>';
	    }
    }
    else
    {
    	echo '</head><body onload="doLogin()">
    		<script>
    			var sb_cms_img_url = "'.SB_CMS_IMG_URL.'";
    			var sb_auto_login = true;

    			function doLogin()
    			{
    				sbShowLoadingDiv(true);
    				setTimeout("sbSendForm()", 0);
    			}
    		</script>
    		<table cellpadding="0" cellspacing="0" style="height:100%;width:100%;">
	        <tr><td valign="middle" align="center">
	        <form id="sb_login_form" action="" method="post">
	        	<input type="hidden" name="sb_adm_login" value="'.$auto_login.'" />
	        	<input type="hidden" name="sb_adm_pass" value="'.$auto_pass.'" />
	        	<input type="hidden" name="sb_adm_lang" value="'.SB_CMS_LANG.'" />
	        	<input type="hidden" name="sb_auto_login" value="1" />
	        </form>
	        </td></tr>';

    	if (CMS_SITE_NAME == 'www.sbuilder.ru')
	    {
	        echo '<tr><td class="links">
	                <a href="http://art.sbuilder.ru" target="_blank">'.INDEX_KNOW_LINK.'</a><br>
	                <a href="http://api.sbuilder.ru" target="_blank">'.INDEX_API_LINK.'</a><br>
	                <a href="http://www.sbuilder.ru/forum/" target="_blank">'.INDEX_FORUM_LINK.'</a><br>
	                <a href="http://www.sbuilder.ru" target="_blank">'.INDEX_SITE_LINK.'</a><br>
	                '.INDEX_COPYRIGHT.'
	            </td></tr>';
	    }

	    echo '</table><div id="sb_msg_div" class="sb_msg_div"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/warning.png" width="32" height="32" id="sb_msg_img" /></td><td id="sb_msg_div_text" class="sb_msg_div_text"></td></tr></table></div>';
    }

    echo '<div id="sb_loading_div" class="sb_msg_div"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="60"><img src="'.SB_CMS_IMG_URL.'/loading.gif"></td><td id="sb_msg_div_text" class="sb_msg_div_text">'.INDEX_LOADING.'</td></tr></table></div>
        </body></html>';
}

require_once(SB_CMS_KERNEL_PATH.'/footer.inc.php');
?>