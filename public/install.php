<?php 
$php_version = phpversion();
$system_name = '<b>CMS <span style="color: #a30000;">S.</span>Builder 4.0</b>';

//Переменные которые зависят от версии PHP
$sbDistrData = array(
	'downloadPath' => 'http://www.sbuilder.ru/share/sbuilder.zip',
	'minPhpVersion' => '5.0.0',
	'zendName' => 'Zend Optimizer',
	'description' => '<span class="textSel"><b>Системные требования:</b></span> Система управления сайтом '.$system_name.' устанавливается на веб-сервер Apache версии 1.3.** и выше на базе операционных систем *nix или Microsoft<sup>&reg;</sup> Windows<sup>TM</sup>. 
      На сервере также должны быть установлены интерпретатор языка программирования PHP версии 5.0 и выше, СУБД mySQL версии 4.0 и выше и ПО Zend Optimizer версии 2.6 и выше. ',
);
	  
if (version_compare($php_version, '5.3.0', '>=') == 1) 
{
	$sbDistrData = array(
		'downloadPath' => 'http://www.sbuilder.ru/share/53/sbuilder.zip',
		'minPhpVersion' => '5.3.0',
		'zendName' => 'Zend Guard',
		'description' => '<span class="textSel"><b>Системные требования:</b></span> Система управления сайтом '.$system_name.' устанавливается на веб-сервер Apache версии 1.3.** и выше на базе операционных систем *nix или Microsoft<sup>&reg;</sup> Windows<sup>TM</sup>. 
	      На сервере также должны быть установлены интерпретатор языка программирования PHP версии 5.3 и выше, СУБД mySQL версии 4.0 и выше и ПО Zend Guard Loader. ',
	);
}
if (version_compare($php_version, '5.4.0', '>=') == 1) 
{
	$sbDistrData = array(
		'downloadPath' => 'http://www.sbuilder.ru/share/54/sbuilder.zip',
		'minPhpVersion' => '5.4.0',
		'zendName' => 'Zend Guard',
		'description' => '<span class="textSel"><b>Системные требования:</b></span> Система управления сайтом '.$system_name.' устанавливается на веб-сервер Apache версии 1.3.** и выше на базе операционных систем *nix или Microsoft<sup>&reg;</sup> Windows<sup>TM</sup>. 
	      На сервере также должны быть установлены интерпретатор языка программирования PHP версии 5.4 и выше, СУБД mySQL версии 4.0 и выше и ПО Zend Guard Loader. ',
	);
}

if (isset($_POST['act']) && intval($_POST['act']) >= 6)
{
    header('Location: /cms/admin/index.php');
    exit;
}



/*
 * Запрещаем кэширование страницы барузером и проксей
 */
header('ETag: PUB' . time());
header('Expires: '.date('r'));
header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, post-check=0, pre-check=0');
@session_cache_limiter('nocache');

/*
 * Сообщаем браузеру кодировку страницы
 */
header('Content-Language: ru, en');
header('Content-Type: text/html; charset=utf-8');
header('Content-Script-Type: text/javascript; charset=utf-8');

require_once './setup/lib/sbFunctions.inc.php';

@umask(0);

if (isset($_GET['download']) && intval($_GET['download']) == 1)
{
    // загрузка дистрибутива
    @ini_set('max_execution_time', '0');
    @ini_set('memory_limit', '32M');
    @ini_set('post_max_size', '16M');
    @ini_set('upload_max_filesize', '16M');

    @copy($sbDistrData['downloadPath'], './setup/sbuilder.zip');
    exit;
}
elseif (isset($_GET['size']) && intval($_GET['size']) == 1)
{
    echo @filesize('./setup/sbuilder.zip');
    exit;
}

if (!isset($_POST['act'])) 
    $step = 0;
else 
    $step = intval($_POST['act']);

$menu = array();
$menu[] = '<li%s><div>01</div>Начало установки</li>';
$menu[] = '<li%s><div>02</div>Лицензионный договор</li>';
$menu[] = '<li%s><div>03</div>Проверка параметров</li>';
$menu[] = '<li%s><div>04</div>Копирование файлов</li>';
$menu[] = '<li%s><div>05</div>Создание таблиц БД</li>';
$menu[] = '<li%s><div>06</div>Дополнительные параметры</li>';

$header = array();
$header[] = 'Начало установки';
$header[] = 'Лицензионный договор';
$header[] = 'Проверка параметров';
$header[] = 'Копирование файлов';
$header[] = 'Создание таблиц БД';
$header[] = 'Дополнительные параметры';

$error_msg = '';

// проверка ошибочных данных
if (isset($_POST['domains']))
{
    // домены
    $found = false;
    foreach ($_POST['domains'] as $domain)
    {
        if (trim($domain) != '')
        {
            $found = true;
            if (!preg_match('/[0-9a-zA-Z\-\.]+/', $domain))
            {
                $error_msg .= '<b>Не верно указано имя домена '.$domain.'!</b><br>';
            }
        }
    }
    
    if (!$found)
    {
        $error_msg .= '<b>Вы не указали ни одного домена.</b><br>';
    }
}

if (isset($_POST['basedirs']))
{
    foreach ($_POST['basedirs'] as $key => $basedir)
    {
        if (trim($_POST['domains'][$key]) != '' && trim($basedir) == '')
        {
            $error_msg .= '<b>Вы не указали корневой каталог для домена '.$_POST['domains'][$key].'.</b><br>';
        }
    }
}

if (!isset($_POST['add_domain']) || $_POST['add_domain'] != 1)
{
    if (isset($_POST['db_host']) && trim($_POST['db_host']) == '')
    {
        $error_msg .= '<b>Вы не указали хост для подключения к базе данных.</b><br>';
    }
    
    if (isset($_POST['db_user']) && trim($_POST['db_user']) == '')
    {
        $error_msg .= '<b>Вы не указали пользователя для подключения к базе данных.</b><br>';
    }
    
    if (isset($_POST['db_name']) && trim($_POST['db_name']) == '')
    {
        $error_msg .= '<b>Вы не указали название базы данных.</b><br>';
    }
    
    if ($error_msg == '' && (isset($_POST['db_host']) || isset($_POST['db_user']) || isset($_POST['db_name'])) && $step == 1)
    {
        require_once './setup/lib/sbDumper.inc.php';
        $dumper = new sbDumper($_POST['db_host'], $_POST['db_name'], $_POST['db_user'], $_POST['db_pass']);
        if (!$dumper->mLinkId)
        {
            $error_msg .= '<b>Не удалось подключиться к указанной Вами базе данных. Проверьте правильность реквизитов доступа.</b><br>';
        }
    }

    if ($error_msg == '' && $step == 1)
    {
        $dir_name = $_POST['basedirs'][0];
        if ($dir_name != '/')
            $dir_name = rtrim($dir_name, '/\\');
    
        $dir_name .= '/cms/kernel';
        if (!@is_dir($dir_name))
        {
            if (!@mkdir($dir_name, octdec(intval($_POST['folder_rights'])), true))
            {
                $error_msg .= '<b>'.sprintf('Ошибка при создании директории <i>%s</i> ! Возможно у Вас недостаточно прав доступа.', $dir_name).'</b><br>';
            }
        }
        
        if ($error_msg == '') 
        {
            $file_name = $dir_name.'/config.inc.php';
            if (@file_exists($file_name))
            {
                @unlink($file_name);
            }
            
            $str = '<?php
        
/**
 * Конфигурационный файл системы
 *
 * В этом файле хранятся установочные настройки системы (абсолютный путь к рутовой директории сайта, реквизиты доступа к
 * MySQL и пр.). При переносе сайта обязательно нужно прописать новые настройки здесь.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2009, OOO "СИБИЭС Групп"
 */

/**
 * @access private
 */
defined(\'CONFIG_VALID_INCLUDE\') or die(\'Error.\');

/**
 * Тип СУБД (возможные значения: mssql, mysql, mysqli).
 * 
 */
//define(\'SB_DB_TYPE\', \'mysql\');

/**
 * Используется шифрование имени пользователя и пароля для подключения к БД или нет
 *
 */
define(\'SB_USE_ENCRYPT\', false);

/**
 * Хост MySQL
 *
 * Хост, на котором работает СУБД MySQL.
 *
 * @global string $GLOBALS[\'sb_db_host\']
 */
$GLOBALS[\'sb_db_host\'] = \''.$_POST['db_host'].'\';

/**
 * Пользователь MySQL
 *
 * Имя пользователя для соединения с базой данных MySQL.
 *
 * @global string $GLOBALS[\'sb_db_user\']
 */
$GLOBALS[\'sb_db_user\'] = \''.$_POST['db_user'].'\';

/**
 * Пароль MySQL
 *
 * Пароль для соединения с базой данных MySQL.
 *
 * @global string $GLOBALS[\'sb_db_password\']
 */
$GLOBALS[\'sb_db_password\'] = \''.$_POST['db_pass'].'\';

/**
 * Пользователь MySQL
 *
 * Имя пользователя для соединения с базой данных MySQL со стороны сайта.
 *
 * @global string $GLOBALS[\'sb_db_site_user\']
 */
//$GLOBALS[\'sb_db_site_user\'] = \'user\';

/**
 * Пароль MySQL
 *
 * Пароль для соединения с базой данных MySQL со стороны сайта.
 *
 * @global string $GLOBALS[\'sb_db_site_password\']
 */
//$GLOBALS[\'sb_db_site_password\'] = \'password\';

/**
 * База данных MySQL
 *
 * База данных MySQL, которая будет использоваться системой.
 *
 * @global string $GLOBALS[\'sb_db_database\']
 */
$GLOBALS[\'sb_db_database\'] = \''.$_POST['db_name'].'\';

/**
 * Доменные имена сайтов и реквизиты доступа к ним
 *
 * Доменные имена сайтов, на которых будет работать система.
 *
 * <code>
 * $GLOBALS[\'sb40.ru\'] = array();
 * // Абсолютный путь до рутовой директории HTTP-аккаунта. Указывается полностью.
 * // Например d:/www/sbuilder/html для Windows-серверов
 * // или /home/sbuilder/public_html для Unix-серверов.
 * $GLOBALS[\'sb40.ru\'][\'basedir\'] = ...;
 * // Хост и порт для FTP. Например ftp.sb40.ru:21. Порт указывать необязательно.
 * // По умолчанию работа идет черз порт 21. Для локальной работы с файловой
 * // системой необходимо указать \'local\'.
 * $GLOBALS[\'sb40.ru\'][\'ftp_host\'] = ...;
 * // Абсолютный путь до рутовой директории FTP-аккаунта. Указывается полностью.
 * // Например C:/Internet/www/sbuilder/4.0 для Windows-серверов
 * // или /home/sbuilder для Unix-серверов.
 * $GLOBALS[\'sb40.ru\'][\'ftp_basedir\'] = ...;
 * // Имя пользователя для FTP.
 * $GLOBALS[\'sb40.ru\'][\'ftp_user\'] = ...;
 * // Пароль для FTP.
 * $GLOBALS[\'sb40.ru\'][\'ftp_password\'] = ...;
 * // Алиасы домена.
 * $GLOBALS[\'sb40.ru\'][\'pointers\'] = ...;
 * </code>
 *
 * @global string $GLOBALS[\'sb40.ru\']
 */

$GLOBALS[\'sb_domains\'] = array();

';

            foreach ($_POST['basedirs'] as $key => $basedir)
            { 
                if (trim($_POST['domains'][$key]) != '' && trim($basedir) != '')
                {
                    $domain = str_ireplace(array('http://', 'www.'), array('', ''), trim($_POST['domains'][$key]));
                    $pointers = trim($_POST['pointers'][$key]);
                    if ($pointers != '')
                    {
                        $pointers = explode(' ', $pointers);
                        $ptr_str = 'array(';
                        foreach($pointers as $value)
                        {
                            $value = str_ireplace(array('http://', 'www.'), array('', ''), trim($value));
                            $ptr_str .= '\''.$value.'\', ';
                        }
                        $pointers = substr($ptr_str, 0, -2).');';
                    }
                    else 
                    {
                        $pointers = 'array();'; 
                    }
    
                    $str .= '$GLOBALS[\'sb_domains\'][\''.$domain.'\'] = array();
$GLOBALS[\'sb_domains\'][\''.$domain.'\'][\'basedir\'] = \''.str_replace('\\', '/', rtrim($basedir, '\\/')).'\';
$GLOBALS[\'sb_domains\'][\''.$domain.'\'][\'ftp_host\'] = \'local\';
$GLOBALS[\'sb_domains\'][\''.$domain.'\'][\'ftp_basedir\'] = \'\';
$GLOBALS[\'sb_domains\'][\''.$domain.'\'][\'ftp_user\'] = \'\';
$GLOBALS[\'sb_domains\'][\''.$domain.'\'][\'ftp_password\'] = \'\';
$GLOBALS[\'sb_domains\'][\''.$domain.'\'][\'pointers\'] = '.$pointers.'

';
                }
            }
        
            $str .= '
/**
 * Кодировка системы
 *
 * Например <samp>WINDOWS-1251</samp> или <samp>UTF-8</samp>.
 *
 * @see $GLOBALS[\'sb_db_charset\']
 * @global string $GLOBALS[\'sb_charset\']
 */
$GLOBALS[\'sb_charset\'] = \'UTF-8\';

/**
 * Массив с языками системы
 *
 * Для каждого языка системы существует папка cms/lang/[идентификатор языка], содержащая
 * языковые файлы системы (каждый файл - набор констант с строковыми значениями). Пользователи системы
 * могут самостоятельно добавлять новые языки, локализуя существующие языковые файлы и расширяя данный массив.
 *
 * <code>
 *     $GLOBALS[\'sb_cms_lang\'][0] = array();
 *     $GLOBALS[\'sb_cms_lang\'][0][\'lang\'] = \'ru\'; // идентификатор языка
 *     $GLOBALS[\'sb_cms_lang\'][0][\'desc\'] = \'Russian\'; // название языка
 *
 *     $GLOBALS[\'sb_cms_lang\'][1] = array();
 *     $GLOBALS[\'sb_cms_lang\'][1][\'lang\'] = \'en\';
 *     $GLOBALS[\'sb_cms_lang\'][1][\'desc\'] = \'English\';
 * </code>
 *
 * @global array $GLOBALS[\'sb_cms_lang\']
 */
$GLOBALS[\'sb_cms_lang\'] = array();

$GLOBALS[\'sb_cms_lang\'][0] = array();
$GLOBALS[\'sb_cms_lang\'][0][\'lang\'] = \'ru\';
$GLOBALS[\'sb_cms_lang\'][0][\'desc\'] = \'Russian\';

?>';
            
            $fp = @fopen($file_name, 'wb');
            if (!$fp)
            {
                $error_msg .= '<b>'.sprintf('Ошибка при создании файла <i>%s</i> ! Возможно у Вас недостаточно прав доступа.', $file_name).'</b><br>';
            }
            else
            {
                if (!@fwrite($fp, $str))
                {
                    $error_msg .= '<b>'.sprintf('Ошибка при создании файла <i>%s</i> ! Возможно у Вас недостаточно прав доступа.', $file_name).'</b><br>';
                }
                
                @fclose($fp);
                @chmod($file_name, octdec(intval($_POST['file_rights'])));
            }
        }
        
        if ($error_msg == '' && count($_POST['basedirs']) > 1)
        {
            $target = $_POST['basedirs'][0].'/cms';
            if (@mkdir($_POST['basedirs'][0].'/upload', octdec($_POST['folder_rights']), true))
                $target_upload = $_POST['basedirs'][0].'/upload';
            else 
                $target_upload = '';
                
            for ($i = 1; $i < count($_POST['basedirs']); $i++)
            {
                $link = rtrim(trim($_POST['basedirs'][1]), '\\/');
                sb_symlink($target, $link.'/cms');
                
                if ($target_upload != '')
                    sb_symlink($target_upload, $link.'/upload');
            }
        }
    }
}

if ($error_msg != '')
{
    $step = max(0, $step - 1);
}

// инициализация начальных значений
if (!isset($_POST['domains'])) 
{
    $domains = array();
    $url = sb_get_host();
    if (count($url) != 0)
    {
        $domains[] = str_ireplace('www.', '', $url['host']);
    }
    else
    {
        $domains[] = '';
    }
}
else 
{
    $domains = $_POST['domains'];
}

if (!isset($_POST['pointers'])) 
{
    $pointers = array();
    $pointers[] = '';
}
else
{
    $pointers = $_POST['pointers'];
}

if (!isset($_POST['basedirs']))
{
    $basedirs = array();
    $basedirs[] = $_SERVER['DOCUMENT_ROOT'].'/';
}
else 
{
    $basedirs = $_POST['basedirs'];
}

if (isset($_POST['add_domain']) && $_POST['add_domain'] == 1)
{
    $step = max(0, $step - 1);
    $domains[] = '';
    $pointers[] = '';
    $basedirs[] = '';
}

if (!isset($_POST['db_host']))
    $db_host = 'localhost';
else 
    $db_host = trim($_POST['db_host']);
    
if (!isset($_POST['db_user']))
    $db_user = '';
else 
    $db_user = trim($_POST['db_user']);
    
if (!isset($_POST['db_pass']))
    $db_pass = '';
else 
    $db_pass = $_POST['db_pass'];
    
if (!isset($_POST['db_name']))
    $db_name = '';
else 
    $db_name = $_POST['db_name'];

if (!isset($_POST['folder_rights']))
    $folder_rights = '755';
else 
    $folder_rights = intval($_POST['folder_rights']);
    
if (!isset($_POST['file_rights']))
    $file_rights = '644';
else 
    $file_rights = intval($_POST['file_rights']);
    
if (!isset($_POST['source']))
    $source = 'site';
else 
    $source = trim($_POST['source']);

if (!isset($_POST['adm_login']))
    $adm_login = 'Admin';
else 
    $adm_login = trim($_POST['adm_login']);
    
if (!isset($_POST['adm_pass']))
    $adm_pass = '';
else 
    $adm_pass = trim($_POST['adm_pass']);
    
if (!isset($_POST['adm_pass2']))
    $adm_pass2 = '';
else 
    $adm_pass2 = trim($_POST['adm_pass2']);
    
if (!isset($_POST['adm_fio']))
    $adm_fio = 'Администратор';
else 
    $adm_fio = trim($_POST['adm_fio']);
    
if (!isset($_POST['adm_email']))
    $adm_email = '';
else 
    $adm_email = trim($_POST['adm_email']);

if (isset($_POST['adm_login']))
{
    if ($adm_login == '' || mb_strlen($adm_login) < 4)
    {
        $error_msg .= '<b>Не верно указан логин. Длина логина не может быть менее 4 символов.</b><br>';
    }
    
    if ($adm_pass == '' || mb_strlen($adm_pass) < 6)
    {
        $error_msg .= '<b>Не верно указан пароль. Длина пароля не может быть менее 6 символов.</b><br>';
    }
    
    if ($adm_pass != $adm_pass2)
    {
        $error_msg .= '<b>Пароль и подтверждение пароля не совпадают. Проверьте правильность ввода пароля.</b><br>';
    }
    
    if ($adm_email == '')
    {
        $error_msg .= '<b>Не указан электронный адрес.</b><br>';
    }
}
        
if (isset($_GET['unzip']) && intval($_GET['unzip']) == 1)
{
    // распаковка дистрибутива системы
    if (sb_unzip(str_replace('//', '/', $basedirs[0].'/setup/sbuilder.zip'), str_replace('//', '/', $basedirs[0].'/setup/'), $folder_rights, $file_rights))
    {
        echo 'TRUE';
    }
    exit;
}
elseif (isset($_GET['copy']) && intval($_GET['copy']) == 1)
{
    $error = false;
    $htaccess = '';

    if (file_exists($basedirs[0].'/.htaccess'))
    {
        $htaccess = trim(file_get_contents($basedirs[0].'/.htaccess'));
    }

    if (!sb_copy(str_replace('//', '/', $basedirs[0].'/setup/sbuilder/'), str_replace('//', '/', $basedirs[0].'/'), $folder_rights, $file_rights))
    {
        $error = true;
    }
    
    if ($htaccess != '')
    {
        $str = trim(file_get_contents($basedirs[0].'/.htaccess'));
        $str = $htaccess."\n\n".$str;
        file_put_contents($basedirs[0].'/.htaccess', $str);
        @chmod(str_replace('//', '/', $basedirs[0].'/.htaccess'), octdec($file_rights));
    }

    if (!$error && count($basedirs) > 1)
    {
        for($i = 1; $i < count($basedirs); $i++)
        {
            if (trim($basedirs[$i]) != '')
            {
                if (file_exists($basedirs[$i].'/.htaccess'))
                {
                    $htaccess = trim(file_get_contents($basedirs[$i].'/.htaccess'));
                }
                               
                if (!@copy(str_replace('//', '/', $basedirs[0].'/setup/sbuilder/.htaccess'), str_replace('//', '/', $basedirs[$i].'/.htaccess')))
                {
                    $error = true;
                    break;
                }
                
                if ($htaccess != '')
                {
                    $str = trim(file_get_contents($basedirs[$i].'/.htaccess'));
                    $str = $htaccess."\n\n".$str;
                    file_put_contents($basedirs[$i].'/.htaccess', $str);
                }

                @chmod(str_replace('//', '/', $basedirs[$i].'/.htaccess'), octdec($file_rights));
            }
        }
    }
    
    if (!$error)
        echo 'TRUE';
        
    exit;
}
elseif (isset($_GET['dump']) && intval($_GET['dump']) == 1)
{
    require_once './setup/lib/sbDumper.inc.php';
    $dumper = new sbDumper($db_host, $db_name, $db_user, $db_pass);
    if (!$dumper->mLinkId)
    {
        echo 'Не удалось подключиться к базе данных.';
        exit;
    }

    if (!$dumper->loadDumpFile(str_replace('//', '/', $basedirs[0].'/setup/sbuilder/dump.sql')))
    {
        echo $dumper->mError;
        exit;
    }
        
    exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="/setup/css/styles.css" type="text/css">
<!--[if IE]><style type="text/css"> @import "/setup/css/ie.css"; </style><![endif]-->
<title>Установка системы управления сайтом S.Builder</title>
<?php

if ($step != 0)
{
    echo '<style type="text/css">#header_r{background:url(/setup/images/header_r_'.$step.'.jpg) 100% 0 no-repeat}</style>';
}
?>
</head>
<body>
<div class="main_cont">
  <div id="header"><div id="header_r"><div id="header_l">
      <a href="http://www.sbuilder.ru" target="_blank" title="Система управления сайтом"><img src="/setup/images/logo.jpg" alt="www.sbuilder.ru"></a>  
  </div></div></div>
<div id="centFon">
  <table class="cent_tb">
    <tr>
      <td id="left_column">
      <div id="l_menu_cont"><div id="l_menu_right"><div id="l_menu_rt"><div id="l_menu_rb">
        <ul id="left_menu">
<?php
foreach ($menu as $key => $value)
{
    if ($key < $step)
        printf($value, ' class="instDone"');
    elseif ($key > $step)
        printf($value, '');
    else 
        printf($value, ' class="instCurr"');
}
?>
          </ul>
        </div></div></div></div>
        </td>
        <td id="center_column">
        <div id="h_cont"><h1><?php echo (isset($header[$step]) ? $header[$step] : ''); ?></h1></div>    
        <div id="content">              
<?php

switch ($step)
{
    case 0:
        echo '
        <form action="'.$_SERVER['PHP_SELF'].'" method="post" id="main">
        <input type="hidden" name="act" id="act" value="1">
        
        <p>Благодарим Вас за проявленный интерес к системе управления сайтом '.$system_name.'!</p> 
        
        <p>Мастер установки поможет Вам развернуть и настроить систему на сервере. Перед началом установки 
        будет проведена проверка сервера на наличие всего необходимого программного обеспечения, а также 
        проверка настроек сервера. В случае возникновения проблем мастер установки даст Вам рекомендации по их устранению.</p>

        <p><b>Подробную инструкцию</b> по работе с мастером установки Вы можете прочитать в <a href="http://www.webincubator.ru/articles/create_site/install_cms" target="_blank">интерактивной книге</a>,
        посвященной системе управления сайтом '.$system_name.'.</p>
        
        <p>В дальнейшем мы советуем регулярно обновлять систему с помощью модуля обновлений 
        (<i>Меню администратора</i>, пункт <i>Обновление системы</i>). Это позволит Вам использовать 
        самую последнюю версию системы.</p>

        <p>В случае возникновения вопросов, а так же для получения более подробной информации о системе 
        обращайтесь в службу <a href="http://www.sbuilder.ru/support/enter/" target="_blank">технической поддержки</a>
	или задавайте вопросы на <a href="http://www.sbuilder.ru/forum/" target="_blank">официальном форуме</a> '.$system_name.'.</p>';        
        if (@file_exists('./cms/admin/index.php')) 
        {
            if (@file_exists('./cms/kernel/data.inc.php'))
            {
                @include_once('./cms/kernel/data.inc.php');
                if (defined('CMS_DISTR_VERSION'))
                    $ver = ' версии '.CMS_DISTR_VERSION;
                else 
                    $ver = '';
            }
            elseif (@file_exists('./cms/kernel/ver.php'))
            {
                @include_once('./cms/kernel/ver.php');
                if (defined('BINN_DISTR_VERSION'))
                    $ver = ' версии '.BINN_DISTR_VERSION;
                else
                    $ver = '';
            }
            else
            {
                $ver = '';
            }
            
            echo '<div class="delim">&nbsp;</div><p><b><span class="textSel">Внимание!</span> На сервере обнаружена установленная система управления сайтом <b><span style="color: #a30000;">S</span>.Builder</b>
            '.$ver.'. Для продолжения установки следует удалить файлы установленной системы и очистить базу данных.</b></p>
            <div class="delim">&nbsp;</div>
            <div align="right"><input type="submit" title="Обновить" class="subm" value="Обновить" onclick="document.getElementById(\'act\').value=0;"></div>
            </form>';
            break;
        }

        echo '<p><b><span class="textSel">Внимательно</span> заполните приведенную ниже форму. Проверьте корневой каталог сайта, так как он мог определиться неверно.</b></p>
        '.($error_msg != '' ? '<b><span class="textSel">Ошибка!</span></b><div class="delim">&nbsp;</div>'.$error_msg.'<div class="delim">&nbsp;</div>' : '').'
        <table class="f_tb">
          <tr>
            <td colspan="2" class="title"><div>Настройка базы данных</div></td>
          </tr>
          <tr>
            <td class="l_td">Хост:</td>
            <td class="r_td"><input type="text" name="db_host" value="'.$db_host.'"></td>
          </tr>
          <tr>
            <td class="l_td">База данных :</td>
            <td class="r_td"><input type="text" name="db_name" value="'.$db_name.'"></td>
          </tr>
          <tr>
            <td class="l_td">Имя пользователя:</td>
            <td class="r_td"><input type="text" name="db_user" value="'.$db_user.'"></td>
          </tr>
          <tr>
            <td class="l_td">Пароль:</td>
            <td class="r_td"><input type="password" name="db_pass" value="'.$db_pass.'"></td>
          </tr>
          <tr>
            <td colspan="2" class="title"><div>Настройка доменов</div></td>
          </tr>
        ';
        
        $num = count($domains);
        foreach ($domains as $key => $value)
        {
            echo '<tr>
              <td class="l_td">'.($key == 0 ? 'Основной домен' : 'Дополнительный домен '.$key).':</td>
              <td class="r_td"><input type="text" name="domains[]" value="'.$value.'"></td>
            </tr>
            <tr>
              <td class="l_td">Псевдонимы (через пробел):</td>
              <td class="r_td"><input type="text" name="pointers[]" value="'.$pointers[$key].'"></td>
            </tr>
            <tr>
              <td class="l_td">Корневой каталог домена:</td>
              <td class="r_td"><input type="text" name="basedirs[]" value="'.$basedirs[$key].'"></td>
            </tr>';
            
            if ($key + 1 != $num && $num != 1)
            {
                echo '<tr>
                <td colspan="2" class="title">&nbsp;</td>
                </tr>';
            }
        }
        
        echo '<tr>
          <td class="l_td">&nbsp;</td>
          <td align="right">
            <input type="hidden" name="add_domain" value="0" id="add_domain">
            <input type="button" title="Добавить домен" class="subm" value="Добавить" onclick="document.getElementById(\'add_domain\').value=1;document.getElementById(\'main\').submit();"></td>
          </tr>
          <tr>
          <td colspan="2" class="title"><div>Настройка файловой системы</div></td>
          </tr>
          <tr>
            <td class="l_td">Права на создаваемые папки:</td>
            <td class="r_td"><input type="text" name="folder_rights" value="'.$folder_rights.'"></td>
          </tr>
          <tr>
            <td class="l_td">Права на создаваемые файлы:</td>
            <td class="r_td"><input type="text" name="file_rights" value="'.$file_rights.'"></td>
          </tr>
          <tr>
          <td class="l_td">&nbsp;</td>
          <td align="right"><div class="delim">&nbsp;</div><input type="submit" title="Далее" class="subm" value="Далее"></td>
          </tr>
        </table>
        </form>';
        break;
        
    case 1:
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post" id="main" onsubmit="return checkForm();" enctype="multipart/form-data">
        <input type="hidden" name="act" value="2">
        <input type="hidden" name="db_host" value="'.$db_host.'">
        <input type="hidden" name="db_user" value="'.$db_user.'">
        <input type="hidden" name="db_pass" value="'.$db_pass.'">
        <input type="hidden" name="db_name" value="'.$db_name.'">
        <input type="hidden" name="folder_rights" value="'.$folder_rights.'">
        <input type="hidden" name="file_rights" value="'.$file_rights.'">
        ';
        
        foreach ($domains as $key => $value)
        {
            echo '<input type="hidden" name="domains[]" value="'.$value.'">
            <input type="hidden" name="pointers[]" value="'.$pointers[$key].'">
            <input type="hidden" name="basedirs[]" value="'.$basedirs[$key].'">';
        }
        
        if (file_exists('./setup/sbuilder') && is_dir('./setup/sbuilder'))
        {
            echo '<input type="hidden" name="source" value="dir">'; 
        }
        elseif (file_exists('./setup/sbuilder.zip') && is_file('./setup/sbuilder.zip'))
        {
            echo '<input type="hidden" name="source" value="zip">';
        }
        else
        {
            echo '<input type="hidden" name="source" value="site">';
        }
        
        echo '<script type="text/javascript">
        function checkForm()
        {
            var chk = document.getElementById("licenseChk");
            if(!chk.checked)
                return false;
        }
        
        function checkLicense (chk)
        {
            var btn = document.getElementById("licenseBtn");
            if(chk.checked && btn)
            {
                btn.style.color = "#585151";
            }
            else
            {
                btn.style.color = "#cccccc";
            }
        }
        </script>
        <p>Пожалуйста, внимательно прочтите следующий Лицензионный Договор. Вы должны принять условия этого Договора,
        чтобы продолжить установку системы управления сайтом '.$system_name.'! Если Вы уже получили лицензионный ключ для 
        указанных ранее доменов, укажите путь к файлу ключа на Вашем локальном компьютере.</p>
        <div class="delim">&nbsp;</div>
        <iframe frameborder="0" style="width:550px;height:300px;margin:0px; border: 1px solid #C0C8D5" src="/setup/license.php"></iframe>
        <div class="delim">&nbsp;</div>
        <table class="f_tb">
        <tr>
            <td class="l_td" style="width: 330px;"><label for="licenseChk">Я принимаю условия Оферты:</label></td>
            <td class="r_td"><input type="checkbox" id="licenseChk" style="border: 0px;width: 20px;" onclick="checkLicense(this)"></td>
        </tr>
        <tr>
            <td class="l_td" style="width: 330px;">Путь к файлу ключа системы:</td>
            <td class="r_td"><input type="file" name="key_file"></td>
        </tr>
        <tr>
            <td class="l_td">&nbsp;</td>
            <td align="right"><input type="submit" title="Далее" id="licenseBtn" class="subm" value="Далее" style="color: #cccccc;"></td>
        </tr>
        </table>';
        break;
        
    case 2:
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post" id="main">
        <input type="hidden" name="act" id="act" value="3">
        <input type="hidden" name="db_host" value="'.$db_host.'">
        <input type="hidden" name="db_user" value="'.$db_user.'">
        <input type="hidden" name="db_pass" value="'.$db_pass.'">
        <input type="hidden" name="db_name" value="'.$db_name.'">
        <input type="hidden" name="folder_rights" value="'.$folder_rights.'">
        <input type="hidden" name="file_rights" value="'.$file_rights.'">
        <input type="hidden" name="source" value="'.$source.'">';
        
        foreach ($domains as $key => $value)
        {
            echo '<input type="hidden" name="domains[]" value="'.$value.'">
            <input type="hidden" name="pointers[]" value="'.$pointers[$key].'">
            <input type="hidden" name="basedirs[]" value="'.$basedirs[$key].'">';
        }
        
        $checkRights = true;
        
        if (isset($_FILES['key_file']) && $_FILES['key_file']['tmp_name'] != '')
        {
            if (@is_uploaded_file($_FILES['key_file']['tmp_name']))
            {
                // загрузка файла ключа
                require_once './setup/lib/sbUploader.inc.php';
            
                $uploader = new sbUploader();
                if (!$uploader->upload('key_file', array('php')))
                {
                    $error_msg .= $uploader->getError();
                }
                else
                {
                    if (!$uploader->move(str_replace('//', '/', $basedirs[0].'/cms/kernel'), 'key.php', $folder_rights, $file_rights))
                    {
                        $error_msg .= $uploader->getError();
                    }
                    else
                    {
                        $checkRights = false;
                    }
                }
            }
            else
            {
                $error_msg .= '<b>Файл с лицензионным ключем не был загружен на сервер.</b><br>';
            }
        }
        
        echo ($error_msg != '' ? '<b><span class="textSel">Загрузка лицензионного ключа:</span></b><div class="delim">&nbsp;</div>'.$error_msg.'<div class="delim">&nbsp;</div>' : '').
        '<table class="f_tb">
        <tr>
          <td colspan="2" class="title"><div>Параметры сервера</div></td>
        </tr>';
        
        $fatal_error = false;

        //версия PHP
        echo '<tr>
            <td class="l_td">Версия PHP:</td>
            <td class="r_td">';
        
		//TODO минимальная версия php для данного дистрибутива
        if (version_compare($php_version, $sbDistrData['minPhpVersion'], '>') == -1) 
        {
                echo '<div class="ok">'.$php_version.'</div>';
        }
        else 
        {
            echo '<div class="error">Ошибка! Версия PHP - '.$php_version.' ниже требуемой '.$sbDistrData['minPhpVersion'].'. Скачать новую версию можно с сайта <a href="http://www.php.net/downloads.php" target="_blank">www.php.net</a>.</div>';
            $fatal_error = true;
        }

        //Наличие Zend Optimizer
        echo '</td></tr>
        <tr>
            <td class="l_td">Наличие ПО '.$sbDistrData['zendName'].':</td>
            <td class="r_td">';

        if (preg_grep('/'.$sbDistrData['zendName'].'/i', get_loaded_extensions()))
        {
            echo '<div class="ok">'.$sbDistrData['zendName'].' установлен</div>';
        }
        else 
        {
            echo '<div class="error">Ошибка! Отсутствует '.$sbDistrData['zendName'].'. Скачать его можно с сайта <a href="http://www.zend.com/en/products/guard/downloads/" target="_blank">www.zend.com</a>.</div>';
            $fatal_error = true;
        }
        
        echo '</td></tr>
        <tr>
            <td class="l_td" style="width: 300px;">Свободное пространство на диске:</td>
            <td class="r_td">';
        
        $size_disk = intval((@disk_free_space($basedirs[0]) / 1024) / 1024);
        if($size_disk >= 20)
        {
            echo '<div class="ok">'.$size_disk.' Мб. свободно</div>';
        }
        else 
        {
            echo '<div class="error">Ошибка! Hедостаточно свободного пространства на диске для установки системы (свободно - '.$size_disk.' Мб., требуется ~20 Мб.).</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Доступ к корневой директории:</td>
            <td class="r_td">'; 
         
        if ($checkRights && !is_writable($basedirs[0]))
        {
            echo '<div class="error">Ошибка! Недостаточно прав доступа к директории '.$basedirs[0].'. Для установки системы необходимы права на запись в корневую директорию сайта.</div>';
            $fatal_error = true;
        }
        else 
        {
            echo '<div class="ok">Директория '.$basedirs[0].' доступна для записи</div>';
        }    

        //iconv, mbstring, mysql, mysqli, pcre, session, SimpleXML
        echo '</td></tr>
         <tr>
           <td colspan="2" class="title"><div>Обязательные модули PHP</div></td>
         </tr>
         <tr>
            <td class="l_td">Модуль <i>mysql</i> или <i>mysqli</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('mysql') || extension_loaded('mysqli')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль отсутствует</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>iconv</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('iconv')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль отсутствует</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>mbstring</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('mbstring')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль отсутствует</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>pcre</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('pcre')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль отсутствует</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>SimpleXML</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('SimpleXML')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль отсутствует</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Работа с сессиями:</td>
            <td class="r_td">';
        
        if (extension_loaded('session')) 
        {
            echo '<div class="ok">Работа с сессиями включена</div>';
        }
        else 
        {
            echo '<div class="error">Работа с сессиями отключена</div>';
            $fatal_error = true;
        }
         
        echo '</td></tr>';
        
        // curl, sockets, gd, zlib
        echo '
        <tr>
           <td colspan="2" class="title"><div>Рекомендуемые модули PHP</div></td>
        </tr>
        <tr>
            <td class="l_td">Модуль <i>curl</i> или <i>sockets</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('curl') || extension_loaded('sockets')) 
        {
            echo '<div class="ok">Модуль '.(extension_loaded('curl') ? 'curl' : 'sockets').' установлен</div>';
        }
        else 
        {
            echo '<div class="error">Ни один из модулей не установлен. Возможно, автоматическое обновление системы не будет работать!</div>';
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>gd</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('gd')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль не установлен. Вывод Captcha на сайте, а также функции обработки изображений будут не доступны!</div>';
        }
         
        echo '</td></tr>
	<tr>
            <td class="l_td">Модуль <i>gettext</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('gettext')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль не установлен. Вывод Captcha на сайте, а также функции обработки изображений будут не доступны!</div>';
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>zlib</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('zlib')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль не установлен. Gzip-сжатие выводимых данных будет не доступно!</div>';
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">Модуль <i>zip</i>:</td>
            <td class="r_td">';
        
        if (extension_loaded('zip')) 
        {
            echo '<div class="ok">Модуль установлен</div>';
        }
        else 
        {
            echo '<div class="error">Модуль не установлен. Работа с zip-архивами будет не доступна!</div>';
        }
        
        echo '</td></tr>
        <tr>
           <td colspan="2" class="title"><div>Рекомендуемые настройки PHP</div></td>
        </tr>
        <tr>
            <td class="l_td" style="width: 300px;">Доступная память для выполнения PHP-скриптов (<i>memory_limit</i>):</td>
            <td class="r_td">';
        
        $size_memory = intval(ini_get('memory_limit'));
        if($size_memory >= 32)
        {
            echo '<div class="ok">Доступная память - '.$size_memory.' Мб.</div>';
        }
        else 
        {
            echo '<div class="error">Доступная память - '.$size_memory.' Мб. Рекомендуется увеличить объем доступной памяти минимум до 32 Мб.</div>';
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td" style="width: 300px;">Загрузка файлов на сервер (<i>file_uploads</i>):</td>
            <td class="r_td">';
        
        if(ini_get('file_uploads'))
        {
            echo '<div class="ok">Разрешена</div>';
        }
        else 
        {
            echo '<div class="error">Запрещена. Некоторые модули системы (Файловая панель, Библиотека изображений и пр.) будут работать некорректно.</div>';
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td" style="width: 300px;">Максимальный размер загружаемых файлов (<i>upload_max_filesize</i>):</td>
            <td class="r_td">';
        
        $max_size = intval(ini_get('upload_max_filesize'));
        if($max_size >= 2)
        {
            echo '<div class="ok">'.$max_size.' Мб.</div>';
        }
        else 
        {
            echo '<div class="error">'.$max_size.' Мб. Рекомендуется увеличить максимальный размер загружаемых файлов минимум до 2 Mb.</div>';
        }
         
        echo '</td></tr>
        <tr>
            <td class="l_td">&nbsp;</td>
            <td align="right">
                <input type="submit" title="Обновить" class="subm" value="Обновить" onclick="document.getElementById(\'act\').value=2;">
                &nbsp;&nbsp;&nbsp;';
        
        if ($fatal_error)
            echo '<input type="button" title="Далее" class="subm" value="Далее" style="color: #cccccc;">';
        else 
            echo '<input type="submit" title="Далее" class="subm" value="Далее">';
            
        echo '</td></tr></table>';
        break;
        
    case 3:
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post" id="main">
        <input type="hidden" name="db_host" value="'.$db_host.'">
        <input type="hidden" name="db_user" value="'.$db_user.'">
        <input type="hidden" name="db_pass" value="'.$db_pass.'">
        <input type="hidden" name="db_name" value="'.$db_name.'">
        <input type="hidden" name="folder_rights" value="'.$folder_rights.'">
        <input type="hidden" name="file_rights" value="'.$file_rights.'">
        ';
        
        foreach ($domains as $key => $value)
        {
            echo '<input type="hidden" name="domains[]" value="'.$value.'">
            <input type="hidden" name="pointers[]" value="'.$pointers[$key].'">
            <input type="hidden" name="basedirs[]" value="'.$basedirs[$key].'">';
        }
        
        if ($source == 'site' && file_exists('./setup/sbuilder.zip'))
        {
            $source = 'zip';
        }
        
        if ($source == 'site' && !file_exists('./setup/sbuilder.zip'))
        {
            if (ini_get('allow_url_fopen') != 1 && strtoupper(ini_get('allow_url_fopen')) != 'ON')
            {
            	//TODO путь к дистрибутиву системы
                echo '
                <input type="hidden" name="source" value="zip">
                <input type="hidden" name="act" id="act" value="3">
                <b><span class="textSel">Ошибка:</span></b><div class="delim">&nbsp;</div>На сервере отключена настройка PHP <i>allow_url_fopen</i>. Не удалось загрузить дистрибутив системы. 
                <a href=\''.$sbDistrData['downloadPath'].'\' target=\'_blank\'>Скачайте дистрибутив системы</a> самостоятельно и поместите его в папку <i>/setup/</i>, затем продолжите установку.
                <div class="delim">&nbsp;</div>
                <div align="right"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>';
            }
            else 
            {
                echo '
                    <input type="hidden" name="source" value="zip">
                    <input type="hidden" name="act" id="act" value="3">
                    <p align="center">
                    Пожалуйста, подождите, осуществляется загрузка дистрибутива системы с сервера <b>www.sbuilder.ru</b>...
                    <br><br><span id="size">загружено - 0 байт</span><br><br>
                    <div class="error" align="center" id="error"><img src="/setup/images/loading.gif"></div></p>
                    <script type="text/javascript" src="/setup/scripts/sbAJAX.js"></script>
                    <script type="text/javascript">
                        sbLoadAsync("'.$_SERVER['PHP_SELF'].'?download=1", null);
                         
                        var int = setInterval("sbLoadAsync(\''.$_SERVER['PHP_SELF'].'?size=1\', afterCheckSize);", 5000);
                        var size = "0";
                        function afterCheckSize(res)
                        {
                            if (res != "")
                            {
                                document.getElementById("size").innerHTML = "загружено - " + res + " байт";
                            }
                            else
                            {
                                clearInterval(int);
                                document.getElementById("error").innerHTML = "Произошла ошибка при загрузке дистрибутива системы. Возможно, проблема связана с плохим каналом Интернета. <a href=\''.$sbDistrData['downloadPath'].'\' target=\'_blank\'>Скачайте дистрибутив системы</a> самостоятельно и поместите его в папку <i>/setup/</i>, затем продолжите установку.";
                            }
                            
                            if (res == size)
                            {
                                clearInterval(int);
                                document.getElementById("size").innerHTML = "Файл успешно загружен!";
                                document.getElementById("error").innerHTML = "";
                                setTimeout("document.getElementById(\'main\').submit();", 500);
                            }
                            
                            size = res;
                        }
                    </script>';
            }
        }
        elseif ($source == 'zip')
        {
            if (!extension_loaded('zip'))
            {
                echo '
                <input type="hidden" name="source" value="dir">
                <input type="hidden" name="act" id="act" value="3">
                <b><span class="textSel">Ошибка:</span></b><div class="delim">&nbsp;</div>На сервере не установлена библиотека <i>zip</i>. Не удалось распаковать дистрибутив системы <i>/setup/sbuilder.zip</i>. Разархивируйте его самостоятельно, содержимое архива поместите в папку <i>/setup/</i>.
                <div class="delim">&nbsp;</div>
                <div align="right"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>';
            }
            elseif (!@file_exists('./setup/sbuilder.zip') || !@is_file('./setup/sbuilder.zip'))
            {
                echo '<input type="hidden" name="source" value="'.$source.'">
                <input type="hidden" name="act" id="act" value="3">
                <b><span class="textSel">Ошибка:</span></b><div class="delim">&nbsp;</div>На сервере не найден файл <i>/setup/sbuilder.zip</i>.
                <div class="delim">&nbsp;</div>
                <div align="right"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>';
            }
            else
            {
                echo '<input type="hidden" name="source" value="dir">
                <input type="hidden" name="act" id="act" value="3">
                <p align="center">
                Пожалуйста, подождите, осуществляется распаковка дистрибутива системы...
                <br><br>
                <div class="error" align="center" id="error"><img src="/setup/images/loading.gif"></div></p>
                <div align="right" id="error_btn" style="display:none;"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>
                <script type="text/javascript" src="/setup/scripts/sbAJAX.js"></script>
                <script type="text/javascript">
                    var frm = document.getElementById("main");
                    frm.action = frm.action + "?unzip=1";

                    sbSendFormAsync(frm, afterUnzip);

                    function afterUnzip(res)
                    {
                        frm.action = frm.action.replace("?unzip=1", "");
                        
                        if (res == "TRUE")
                        {
                            document.getElementById("main").submit();
                        }
                        else
                        {
                            document.getElementById("error").innerHTML = "Произошла ошибка при распаковке дистрибутива системы <i>/setup/sbuilder.zip</i>. Разархивируйте его самостоятельно, содержимое архива поместите в папку <i>/setup/</i>.";
                            document.getElementById("error_btn").style.display = "block";
                        }
                    }
                </script>';
            }
        }
        elseif ($source == 'dir')
        {
            if (!@file_exists('./setup/sbuilder') || !@is_dir('./setup/sbuilder'))
            {
                echo '
                <input type="hidden" name="source" value="'.$source.'">
                <input type="hidden" name="act" id="act" value="3">
                <b><span class="textSel">Ошибка:</span></b><div class="delim">&nbsp;</div>На сервере не найдена директория <i>/setup/sbuilder/</i>.
                <div class="delim">&nbsp;</div>
                <div align="right"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>';
            }
            else
            {
                echo '<input type="hidden" name="source" value="dir">
                <input type="hidden" name="act" id="act" value="4">
                <p align="center">
                Пожалуйста, подождите, осуществляется копирование файлов дистрибутива системы...
                <br><br>
                <div class="error" align="center" id="error"><img src="/setup/images/loading.gif"></div></p>
                <div align="right" id="error_btn" style="display:none;"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>
                <script type="text/javascript" src="/setup/scripts/sbAJAX.js"></script>
                <script type="text/javascript">
                    var frm = document.getElementById("main");
                    frm.action += "?copy=1";
                    
                    sbSendFormAsync(frm, afterCopy);

                    function afterCopy(res)
                    {
                        frm.action = frm.action.replace("?copy=1", "");
                        if (res == "TRUE")
                        {
                            document.getElementById(\'main\').submit();
                        }
                        else
                        {
                            document.getElementById("error").innerHTML = "Произошла ошибка при копировании файлов дистрибутива системы из папки <i>/setup/sbuilder/</i>. Скопируйте папку <i>/cms/</i> и файл <i>.htaccess</i> самостоятельно в корневой каталог основного сайта, а также файл <i>.htaccess</i> в корневые каталоги дополнительных доменов.";
                            document.getElementById("act").value = "3";
                            document.getElementById("error_btn").style.display = "block";
                        }
                    }
                </script>';
            }
        }
        
        echo '</form>';
        break;
        
    case 4:
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post" id="main">
        <input type="hidden" name="db_host" value="'.$db_host.'">
        <input type="hidden" name="db_user" value="'.$db_user.'">
        <input type="hidden" name="db_pass" value="'.$db_pass.'">
        <input type="hidden" name="db_name" value="'.$db_name.'">
        <input type="hidden" name="folder_rights" value="'.$folder_rights.'">
        <input type="hidden" name="file_rights" value="'.$file_rights.'">
        <input type="hidden" name="source" value="'.$source.'">';
        
        foreach ($domains as $key => $value)
        {
            echo '<input type="hidden" name="domains[]" value="'.$value.'">
            <input type="hidden" name="pointers[]" value="'.$pointers[$key].'">
            <input type="hidden" name="basedirs[]" value="'.$basedirs[$key].'">';
        }
        
        if (!@file_exists('./setup/sbuilder/dump.sql') || !@is_file('./setup/sbuilder/dump.sql'))
        {
            echo '<input type="hidden" name="act" id="act" value="4">
                <b><span class="textSel">Ошибка:</span></b><div class="delim">&nbsp;</div>На сервере не найден файл <i>/setup/sbuilder/dump.sql</i>.
                <div class="delim">&nbsp;</div>
                <div align="right"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>';
        }
        else
        {
            echo '<input type="hidden" name="act" id="act" value="5">
                <p align="center">
                Пожалуйста, подождите, осуществляется создание таблиц базы данных...
                <br><br>
                <div class="error" align="center" id="error"><img src="/setup/images/loading.gif"></div></p>
                <div align="right" id="error_btn" style="display:none;"><input type="submit" title="Обновить" class="subm" value="Обновить"></div>
                <script type="text/javascript" src="/setup/scripts/sbAJAX.js"></script>
                <script type="text/javascript">
                    var frm = document.getElementById("main");
                    frm.action += "?dump=1";
                    
                    sbSendFormAsync(frm, afterDump);

                    function afterDump(res)
                    {
                        frm.action = frm.action.replace("?dump=1", "");
                        if (res == "")
                        {
                            document.getElementById(\'main\').submit();
                        }
                        else
                        {
                            document.getElementById("error").innerHTML = "Произошла ошибка при создании таблиц базы данных дистрибутива системы. Текст ошибки:<br><br>" + res;
                            document.getElementById("act").value = "4";
                            document.getElementById("error_btn").style.display = "block";
                        }
                    }
                </script>';
        }
            
        echo '</form>';
        break;
        
    case 5:
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post" id="main">
            <input type="hidden" name="db_host" value="'.$db_host.'">
            <input type="hidden" name="db_user" value="'.$db_user.'">
            <input type="hidden" name="db_pass" value="'.$db_pass.'">
            <input type="hidden" name="db_name" value="'.$db_name.'">
            <input type="hidden" name="folder_rights" value="'.$folder_rights.'">
            <input type="hidden" name="file_rights" value="'.$file_rights.'">
            <input type="hidden" name="source" value="'.$source.'">';
        
        $next_step = false;
        if (isset($_POST['adm_login']) && $error_msg == '')
        {
            // Создаем запись админа и перебрасываем на вход в систему
            require_once './setup/lib/sbDumper.inc.php';
            $dumper = new sbDumper($db_host, $db_name, $db_user, $db_pass);
            if (!$dumper->mLinkId)
            {
                $error_msg .= '<b>Не удалось подключиться к базе данных. Проверьте правильность реквизитов доступа.</b><br>';
            }
            
            if(!$dumper->query('UPDATE sb_users SET u_login = "'.$dumper->escape($adm_login).'", u_pass = "'.md5($adm_pass).'", u_name = "'.$dumper->escape($adm_fio).'", u_email="'.$dumper->escape($adm_email).'"
                                WHERE u_login="Admin"')) 
            {
                $error_msg .= '<b>Не удалось добавить учетную запись администратора в таблицу sb_users базы данных.</b><br>';
            }

            if ($error_msg == '')
            {
                $next_step = true;
                
                $dumper->query('INSERT INTO sb_settings (s_setting, s_value, s_domain) VALUES ("sb_file_rights", "'.$dumper->escape(serialize($file_rights)).'", "all")');
                $dumper->query('INSERT INTO sb_settings (s_setting, s_value, s_domain) VALUES ("sb_folder_rights", "'.$dumper->escape(serialize($folder_rights)).'", "all")');
            
                echo '<p>Поздравляем! Система управления сайтом '.$system_name.' успешно установлена.</p>
                      <p>Для перехода в административный интерфейс системы нажмите кнопку <i>Далее</i>.</p>
                      <div class="delim">&nbsp;</div>
                      <p><b><span class="textSel">Внимание!</span> Не забудьте удалить директорию <i>setup</i> и файл <i>install.php</i></b> !</p>
                      <div class="delim">&nbsp;</div>
                      <p>Научиться работе с нашей CMS Вы можете:
                        <blockquote>
                        
                        <a href="http://www.webincubator.ru/" target="_blank"><b>Прочитав интерактивную книгу</b></a><br><br>
                        <a href="http://www.sbuilder.ru/training/helps/" target="_blank"><b>Изучив справочную информацию по системе</b></a>
                        </blockquote>
                      </p>
                      <div class="delim">&nbsp;</div>
                      <input type="hidden" name="act" value="6">
                      <div align="right"><input type="submit" title="Далее" class="subm" value="Далее"></div>';
            }
            else 
            {
                echo '<input type="hidden" name="act" value="5">';
            }
        }
        else 
        {
            echo '<input type="hidden" name="act" value="5">';
        }
        
        foreach ($domains as $key => $value)
        {
            echo '<input type="hidden" name="domains[]" value="'.$value.'">
            <input type="hidden" name="pointers[]" value="'.$pointers[$key].'">
            <input type="hidden" name="basedirs[]" value="'.$basedirs[$key].'">';
        }
        
        if (!$next_step)
        {
            echo '<p>На этом шаге будет создана учетная запись администратора системы управления сайтом. <span class="textSel">Внимательно</span> проверьте введенные
            Вами данные, изменить их Вы сможете только после входа в административный интерфейс системы.</p>
            <p><span class="textSel">Помните</span>, чем больше пароль, тем меньше вероятность его подбора злоумышленником.</p> 
            <p><b>Все поля формы являются обязательными для заполнения.</b></p>
            '.($error_msg != '' ? '<b><span class="textSel">Ошибка!</span></b><div class="delim">&nbsp;</div>'.$error_msg.'<div class="delim">&nbsp;</div>' : '').'
            <table class="f_tb">
              <tr>
                <td colspan="2" class="title"><div>Создание учетной записи администратора</div></td>
              </tr>
              <tr>
                <td class="l_td">Логин (мин. 4 символа):</td>
                <td class="r_td"><input type="text" name="adm_login" value="'.$adm_login.'"></td>
              </tr>
              <tr>
                <td class="l_td">Пароль (мин. 6 символов):</td>
                <td class="r_td"><input type="password" name="adm_pass" value="'.$adm_pass.'"></td>
              </tr>
              <tr>
                <td class="l_td">Пароль (мин. 6 символов):</td>
                <td class="r_td"><input type="password" name="adm_pass2" value="'.$adm_pass2.'"></td>
              </tr>
              <tr>
                <td class="l_td">Ф.И.О.:</td>
                <td class="r_td"><input type="text" name="adm_fio" value="'.$adm_fio.'"></td>
              </tr>
              <tr>
                <td class="l_td">Электронный адрес:</td>
                <td class="r_td"><input type="text" name="adm_email" value="'.$adm_email.'"></td>
              </tr>
              <tr>
                <td class="l_td">&nbsp;</td>
                <td align="right"><div class="delim">&nbsp;</div><input type="submit" title="Далее" class="subm" value="Далее"></td>
              </tr>
            </table>';
        }
        
        echo '</form>';
        break;
}
?>
        </div>
        </td></tr>
  </table>
</div>  
  <div id="footer">
  <table width="100%" cellpadding="0" cellspacing="0"><tr>
  <td id="footer_r">
    <span class="textSel"><b>Техническая поддержка:</b></span>
    <div class="copyright">Телефон: (495) 988-07-66<br>
    Сайт: <a href="http://www.sbuilder.ru/support/enter/" target="_blank" title="Техническая поддержка">http://www.sbuilder.ru</a><br>
    Форум: <a href="http://www.sbuilder.ru/forum/" target="_blank" title="Форум">http://www.sbuilder.ru/forum/</a>
    </div>
    Copyright &copy; 2001-2010, ООО "СИБИЭС-Групп"
  </td>  
  <td id="footer_l">
      <span class="textSel"><b>Справочная информация:</b></span> 
      <a href="http://www.webincubator.ru/" target="_blank">интерактивная книга <?php echo $system_name; ?></a>, <a href="http://api.sbuilder.ru" target="_blank">описание программного интерфейса <?php echo $system_name; ?></a>
      <div class="delim">&nbsp;</div>
      <?php
      	echo $sbDistrData['description'];
      ?>
  </td>
  </tr></table>
  </div>
</div>
</body>
</html>