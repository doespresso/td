<?php
/*
 * Сборка CSS файлов в один и передача его в сжатом виде клиенту
 */

if (!isset($_GET['files']) || !is_array($_GET['files']))
{
    header('HTTP/1.0 404 Not Found');
    exit(0);
}

// Расширение файлов, с которыми работаем
define('FILE_EXT', 'css');
// Тип содержимого
define('CONTENT_TYPE', 'text/css');
// Политика кэширования
define('CACHE_CONTROL', 'public, max-age=604800, must-revalidate');
define('EXPIRES', gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');

// Подключаем библиотеки ядра
$dir = substr(dirname(__FILE__), 0, -15).'kernel/prog';
require_once($dir.'/header.inc.php');

$css_files = array();
$last_modified = 0;

foreach ($_GET['files'] as $file)
{
	//экранируем строку для защиты от нуль-байтовых символов
	$file = addslashes($file);
	
    // расширение файла подходит?
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext != FILE_EXT)
        continue;

    // Нам пытаются скормить неправильный файл
    if (sb_stripos('://', $file) !== false) 
        continue;

    // Дата модификации файла
    $mtime = $GLOBALS['sbVfs']->filectime($file);
    // Не удалось открыть файл
    if (!$mtime)
        continue;

    $css_files[] = $file;

    if ($mtime > $last_modified)
        $last_modified = $mtime;
}

// Если файлов нет, возвращаем 404
if (count($css_files) == 0)
{
	header('HTTP/1.0 404 Not Found');
	exit(0);
}

// Если был запрос If-Modified-Since, обрабатываем его
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	// Разбираем заголовок If-Modified-Since и формируем timestamp
	$if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']));

	if ($if_modified_since >= $last_modified)
	{
		// Изменений не было
		header('HTTP/1.1 304 Not Modified');
		header('Cache-Control: ' . CACHE_CONTROL);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
		exit(0);
	}
}

// Передаем заголовки
header('Expires: ' . EXPIRES);
header('Content-Type: ' . CONTENT_TYPE . '; charset='.strtolower(SB_CHARSET));
header('Cache-Control: ' . CACHE_CONTROL);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');


// Старт вывода
ob_start();

require_once(SB_CMS_LIB_PATH.'/compressor/sbCompressor.inc.php');

// Передаем файлы
foreach($css_files as $file)
{
    ob_start();
	include_once(SB_BASEDIR.$file);
	$css = ob_get_clean()."\n";

	$css = sbCompressorCSSImport::process($css, $file);

	echo sbCompressorCSS::compress($css);
}

$gzip_size = ob_get_length();
$html = ob_get_clean();

/*
 * Попробуем использовать компрессию, это должно заметно уменьшить время загрузки страницы
*/
if (sbPlugins::getSetting('sb_gzip_compress') == 1 && extension_loaded('zlib') && (!isset($GLOBALS['sb_gzip_compress']) || $GLOBALS['sb_gzip_compress']))
{
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strstr($_SERVER['HTTP_USER_AGENT'], 'compatible') || strstr($_SERVER['HTTP_USER_AGENT'], 'Gecko')))
    {
        ob_start('ob_gzhandler');
        echo $html;
        exit(0);
    }
    elseif (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
    {
        header('Content-Encoding: gzip');

        echo "\x1f\x8b\x08\x00\x00\x00\x00\x00".substr(gzcompress($html, 3), 0, -4);
        echo pack('V', crc32($html));
        echo pack('V', $gzip_size);
        exit(0);
    }
}

echo $html;
exit(0);
?>