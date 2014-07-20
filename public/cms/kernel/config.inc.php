<?php
        
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
defined('CONFIG_VALID_INCLUDE') or die('Error.');

/**
 * Тип СУБД (возможные значения: mssql, mysql, mysqli).
 * 
 */
//define('SB_DB_TYPE', 'mysql');

/**
 * Используется шифрование имени пользователя и пароля для подключения к БД или нет
 *
 */
define('SB_USE_ENCRYPT', false);

/**
 * Хост MySQL
 *
 * Хост, на котором работает СУБД MySQL.
 *
 * @global string $GLOBALS['sb_db_host']
 */
$GLOBALS['sb_db_host'] = 'localhost';

/**
 * Пользователь MySQL
 *
 * Имя пользователя для соединения с базой данных MySQL.
 *
 * @global string $GLOBALS['sb_db_user']
 */
$GLOBALS['sb_db_user'] = 'terradom';

/**
 * Пароль MySQL
 *
 * Пароль для соединения с базой данных MySQL.
 *
 * @global string $GLOBALS['sb_db_password']
 */
$GLOBALS['sb_db_password'] = 'domterra';

/**
 * Пользователь MySQL
 *
 * Имя пользователя для соединения с базой данных MySQL со стороны сайта.
 *
 * @global string $GLOBALS['sb_db_site_user']
 */
//$GLOBALS['sb_db_site_user'] = 'user';

/**
 * Пароль MySQL
 *
 * Пароль для соединения с базой данных MySQL со стороны сайта.
 *
 * @global string $GLOBALS['sb_db_site_password']
 */
//$GLOBALS['sb_db_site_password'] = 'password';

/**
 * База данных MySQL
 *
 * База данных MySQL, которая будет использоваться системой.
 *
 * @global string $GLOBALS['sb_db_database']
 */
$GLOBALS['sb_db_database'] = 'terradomloc';

/**
 * Доменные имена сайтов и реквизиты доступа к ним
 *
 * Доменные имена сайтов, на которых будет работать система.
 *
 * <code>
 * $GLOBALS['sb40.ru'] = array();
 * // Абсолютный путь до рутовой директории HTTP-аккаунта. Указывается полностью.
 * // Например d:/www/sbuilder/html для Windows-серверов
 * // или /home/sbuilder/public_html для Unix-серверов.
 * $GLOBALS['sb40.ru']['basedir'] = ...;
 * // Хост и порт для FTP. Например ftp.sb40.ru:21. Порт указывать необязательно.
 * // По умолчанию работа идет черз порт 21. Для локальной работы с файловой
 * // системой необходимо указать 'local'.
 * $GLOBALS['sb40.ru']['ftp_host'] = ...;
 * // Абсолютный путь до рутовой директории FTP-аккаунта. Указывается полностью.
 * // Например C:/Internet/www/sbuilder/4.0 для Windows-серверов
 * // или /home/sbuilder для Unix-серверов.
 * $GLOBALS['sb40.ru']['ftp_basedir'] = ...;
 * // Имя пользователя для FTP.
 * $GLOBALS['sb40.ru']['ftp_user'] = ...;
 * // Пароль для FTP.
 * $GLOBALS['sb40.ru']['ftp_password'] = ...;
 * // Алиасы домена.
 * $GLOBALS['sb40.ru']['pointers'] = ...;
 * </code>
 *
 * @global string $GLOBALS['sb40.ru']
 */

$GLOBALS['sb_domains'] = array();

$GLOBALS['sb_domains']['terradom.loc'] = array();
$GLOBALS['sb_domains']['terradom.loc']['basedir'] = '/Users/jd/servers/LOCALHOST/terradom.loc/public';
$GLOBALS['sb_domains']['terradom.loc']['ftp_host'] = 'local';
$GLOBALS['sb_domains']['terradom.loc']['ftp_basedir'] = '';
$GLOBALS['sb_domains']['terradom.loc']['ftp_user'] = '';
$GLOBALS['sb_domains']['terradom.loc']['ftp_password'] = '';
$GLOBALS['sb_domains']['terradom.loc']['pointers'] = array();


/**
 * Кодировка системы
 *
 * Например <samp>WINDOWS-1251</samp> или <samp>UTF-8</samp>.
 *
 * @see $GLOBALS['sb_db_charset']
 * @global string $GLOBALS['sb_charset']
 */
$GLOBALS['sb_charset'] = 'UTF-8';

/**
 * Массив с языками системы
 *
 * Для каждого языка системы существует папка cms/lang/[идентификатор языка], содержащая
 * языковые файлы системы (каждый файл - набор констант с строковыми значениями). Пользователи системы
 * могут самостоятельно добавлять новые языки, локализуя существующие языковые файлы и расширяя данный массив.
 *
 * <code>
 *     $GLOBALS['sb_cms_lang'][0] = array();
 *     $GLOBALS['sb_cms_lang'][0]['lang'] = 'ru'; // идентификатор языка
 *     $GLOBALS['sb_cms_lang'][0]['desc'] = 'Russian'; // название языка
 *
 *     $GLOBALS['sb_cms_lang'][1] = array();
 *     $GLOBALS['sb_cms_lang'][1]['lang'] = 'en';
 *     $GLOBALS['sb_cms_lang'][1]['desc'] = 'English';
 * </code>
 *
 * @global array $GLOBALS['sb_cms_lang']
 */
$GLOBALS['sb_cms_lang'] = array();

$GLOBALS['sb_cms_lang'][0] = array();
$GLOBALS['sb_cms_lang'][0]['lang'] = 'ru';
$GLOBALS['sb_cms_lang'][0]['desc'] = 'Russian';

?>