<?php
/**
 * Вспомогательные функции ядра системы
 *
 * Файл содержит наиболее востребованные функции ядра системы. Нет смысла делать отдельный класс
 * для реализации этих функций. Конструктор класса и обращение к методам через имя класса только
 * снизит читабельность кода.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Перевод строки в верхний регистр
 *
 * Если доступна функция <i>mb_strtoupper</i>, то используем ее. Она корректно отрабатывает для большинства используемых кодировок.
 * Если же эта функция недоступна, то используем <i>sb_str_replace</i> и массивы <i>$GLOBALS['sb_str_upper_interval']</i> и
 * <i>$GLOBALS['sb_str_lower_interval']</i>.
 *
 * @see function sb_str_replace()
 * @see $GLOBALS['sb_str_upper_interval']
 * @see $GLOBALS['sb_str_lower_interval']
 *
 * @param string $str Строка, которую нужно преобразовать
 * @param string $charset Кодировка строки
 * @return string Преобразованная строка
 */
function sb_strtoupper($str, $charset='')
{
	if ($str === '')
		return '';

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_strtoupper'))
    {
        return mb_strtoupper($str, $charset);
    }
    else
    {
        // поскольку strtoupper не работает с кодировкой UTF-8 и не всегда корректно работает с локалью (например, для русских букв),
        // то сначала используем массивы прописных букв и строчных букв для замены в строке, а потом прогоняем через strtoupper

        return strtoupper(sb_str_replace($GLOBALS['sb_str_upper_interval'], $GLOBALS['sb_str_lower_interval'], $str, $charset));
    }
}

/**
 * Перевод строки в нижний регистр
 *
 * Если доступна функция <i>mb_strtolower</i>, то используем ее. Она корректно отрабатывает для большинства используемых кодировок.
 * Если же эта функция недоступна, то используем <i>sb_str_replace</i> и массивы <i>$GLOBALS['sb_str_upper_interval']</i> и
 * <i>$GLOBALS['sb_str_lower_interval']</i>.
 *
 * @see function sb_str_replace()
 * @see $GLOBALS['sb_str_upper_interval']
 * @see $GLOBALS['sb_str_lower_interval']
 *
 * @param string $str Строка, которую нужно преобразовать
 * @param string $charset Кодировка строки
 * @return string Преобразованная строка
 */
function sb_strtolower($str, $charset='')
{
	if ($str === '')
		return '';

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_strtolower'))
    {
        return mb_strtolower($str, $charset);
    }
    else
    {
        // поскольку strtolower не работает с кодировкой UTF-8 и не всегда корректно работает с локалью (например, для русских букв),
        // то сначала используем массивы прописных букв и строчных букв для замены в строке, а потом прогоняем через strtolower
        return strtolower(sb_str_replace($GLOBALS['sb_str_lower_interval'], $GLOBALS['sb_str_upper_interval'], $str, $charset));
    }
}

/**
 * Замена всех вхождений подстроки в строке
 *
 * Если кодировка строки UTF-8, то используем preg_replace c модификатором <i>u</i>.
 * Иначе используется функция str_replace.
 *
 * @param mixed $search Подстрока или массив подстрок, которые надо заменить.
 * @param mixed $replace Подстрока или массив подстрок, на которые надо заменить.
 * @param string $str Строка, в которой производится замена.
 * @param string $charset Кодировка строки.
 * @param bool $case Регистрозависимая замена (false) или нет (true).
 *
 * @return string Преобразованная строка
 */
function sb_str_replace($search, $replace, $str, $charset='', $case = false)
{
	if ($str === '')
		return '';

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = strtoupper(SB_CHARSET);
    }

    if ($charset == 'UTF-8')
    {
        // Если кодировка UTF-8, используем preg_replace
        if(!is_array($search))
        {
            $search = '!'.preg_quote($search).'!'.SB_PREG_MOD.($case ? 'i' : '');
        }
        else
        {
            foreach ($search as $k => $v)
            {
                $search[$k] = '!'.preg_quote($v).'!'.SB_PREG_MOD.($case ? 'i' : '');
            }
        }

        return preg_replace($search, $replace, $str);
    }
    else
    {
    	if ($case)
        	return str_ireplace($search, $replace, $str);
        else
        	return str_replace($search, $replace, $str);
    }
}

/**
 * Определяет кол-во символов в строке
 *
 * Если доступна функция <i>mb_strlen</i>, то используем ее. Она корректно отрабатывает для большинства используемых кодировок.
 * Если же эта функция недоступна, то используем <i>strlen</i>.
 *
 * @param string $str Строка, кол-во символов которой мы пытаемся получить
 * @param string $charset Кодировка строки
 * @return int Кол-во символов в строке
 */
function sb_strlen($str, $charset='')
{
	if ($str === '')
		return 0;

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_strlen'))
    {
        return mb_strlen($str, $charset);
    }
    else
    {
        return strlen($str);
    }
}

/**
 * Ищет позицию первого вхождения подстроки в строку
 *
 * @param string $haystack Строка, в которой производится поиск.
 * @param string $needle Подстрока, которую мы ищем.
 * @param int $offset Позиция в строке $haystack, начиная с которой необходимо производить поиск.
 * @param string $charset Кодировка строки.
 *
 * @return int Позиция в строке.
 */
function sb_strpos($haystack, $needle, $offset = null, $charset='')
{
	if ($haystack == '')
		return false;

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = defined('SB_CHARSET') ? SB_CHARSET : 'UTF-8';
    }

    if (function_exists('mb_strpos'))
    {
        return mb_strpos($haystack, $needle, (int)$offset, $charset);
    }
    else
    {
        return strpos($haystack, $needle, (int)$offset);
    }
}

/**
 * Ищет позицию последнего вхождения подстроки в строку
 *
 * @param string $haystack Строка, в которой производится поиск.
 * @param string $needle Подстрока, которую мы ищем.
 * @param int $offset Позиция в строке $haystack, начиная с которой необходимо производить поиск.
 * @param string $charset Кодировка строки.
 *
 * @return int Позиция в строке.
 */
function sb_strrpos($haystack, $needle, $offset = null, $charset='')
{
	if ($haystack == '')
		return false;

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_strrpos'))
    {
        if (version_compare(phpversion(), '5.2.0', '>='))
        	return mb_strrpos($haystack, $needle, (int)$offset, $charset);
        else
        	return mb_strrpos($haystack, $needle, $charset);
    }
    else
    {
        return strrpos($haystack, $needle, (int)$offset);
    }
}

/**
 * Ищет позицию первого вхождения подстроки в строку (без учета регистра)
 *
 * @param string $haystack Строка, в которой производится поиск.
 * @param string $needle Подстрока, которую мы ищем.
 * @param int $offset Позиция в строке $haystack, начиная с которой необходимо производить поиск.
 * @param string $charset Кодировка строки.
 *
 * @return int Позиция в строке.
 */
function sb_stripos($haystack, $needle, $offset = null, $charset='')
{
	if ($haystack == '')
		return false;

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_stripos'))
    {
        return mb_stripos($haystack, $needle, (int)$offset, $charset);
    }
    else
    {
        return stripos($haystack, $needle, (int)$offset);
    }
}

/**
 * Возвращает кол-во вхождений подстроки в строку
 *
 * @param string $haystack Строка, в которой производится поиск.
 * @param string $needle Подстрока, которую мы ищем.
 * @param string $charset Кодировка строки.
 *
 * @return int Кол-во вхождений подстроки в строку.
 */
function sb_substr_count($haystack, $needle, $charset='')
{
	if ($haystack == '')
		return 0;

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_substr_count'))
    {
        return mb_substr_count($haystack, $needle, $charset);
    }
    else
    {
        return substr_count($haystack, $needle);
    }
}

/**
 * Возвращает подстроку из строки
 *
 * Если доступна функция <i>mb_substr</i>, то используем ее. Она корректно отрабатывает для большинства используемых кодировок.
 * Если же эта функция недоступна, то используем <i>substr</i>.
 *
 * @param string $str Строка, подстроку которой пытаемся получить
 * @param int $start Начальная позиция в строке
 * @param int $length Кол-во символов
 * @param string $charset Кодировка строки
 * @return string Подстрока
 */
function sb_substr($str, $start, $length = null, $charset = '')
{
    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    if (function_exists('mb_substr'))
    {
        if (is_null($length))
        {
            $length = sb_strlen($str) - $start;
        }

        return mb_substr($str, (int)$start, (int)$length, $charset);
    }
    elseif ($charset == 'UTF-8')
    {
    	$match = array();
    	preg_match_all( '/./us', $str, $match );
		$chars = is_null( $length ) ? array_slice( $match[0], $start ) : array_slice( $match[0], $start, $length );
		return implode( '', $chars );
    }
    else
    {

        if (is_null($length))
        {
            return substr($str, (int)$start);
        }
        else
        {
            return substr($str, (int)$start, $length);
        }
    }
}

/**
 * Обрезает текст до заданной длины, добавляя ... в конце
 *
 * HTML-теги из текста вырезаются.
 *
 * @param string $str Текст, который будет обрезан.
 * @param string $limit Максимальная длина текста.
 *
 * @return string Обрезанный текст.
 */
function sb_short_text($str, $limit = 150, $delim = '...')
{
	if ($limit == 0)
		return $str;

	$clear_str = strip_tags($str);
    if(sb_strlen($clear_str) < $limit)
    	return $str;

    $m = array();
    if (preg_match('/(.{0,'.$limit.'}[^\s,\.\-!"\':;?\(\)\[\]<>]*)/ms'.SB_PREG_MOD, $clear_str, $m))
    {
    	$str = $m[1].' '.$delim;
    }
    else
    {
    	$str = sb_substr($clear_str, 0, $limit).' '.$delim;
    }

    return $str;
}

/**
 * Перевод строки в латиницу
 *
 * @param string $str Преобразуемая строка
 * @param string $charset Кодировка строки
 * @return string Преобразованная строка
 */
function sb_strtolat($str, $charset='')
{
	if ($str === '')
		return '';

    $str = sb_str_replace($GLOBALS['sb_str_upper_interval'], $GLOBALS['sb_str_latupper_interval'], $str, $charset);
    $str = sb_str_replace($GLOBALS['sb_str_lower_interval'], $GLOBALS['sb_str_latlower_interval'], $str, $charset);
	$str = preg_replace('/[^0-9a-zA-Z_\-\.]+/', '_', $str);

    $str = preg_replace('/[_]+/', '_', $str);

    return $str;
}

/**
 * Преобразует все символы строки в HTML-мнемоники
 *
 * @param string $str Строка, символы которой будут преобразованы
 * @param int $quote Будут ли преобразовываться одинарные и двойные кавычки. Возможные значения - ENT_QUOTES, ENT_NOQUOTES
 * @param string $charset Кодировка строки
 *
 * @return string Преобразованная строка
 */
function sb_htmlentities($str, $quote = ENT_QUOTES, $charset = '')
{
	if ($str === '')
		return '';

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    return htmlentities($str, $quote, $charset);
}

/**
 * Преобразует HTML-мнемоники в символы
 *
 * @param string $str Строка, символы которой будут преобразованы
 * @param int $quote Будут ли преобразовываться одинарные и двойные кавычки. Возможные значения - ENT_QUOTES, ENT_NOQUOTES
 *
 * @return string Преобразованная строка
 */
function sb_html_entity_decode($str, $quote = ENT_QUOTES, $charset = '')
{
	if ($str === '')
		return '';

	if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    return html_entity_decode($str, $quote, $charset);
}

/**
 * Преобразует специальные символы строки в HTML-мнемоники
 *
 * @param string $str Строка, символы которой будут преобразованы
 * @param int $quote Будут ли преобразовываться одинарные и двойные кавычки. Возможные значения - ENT_QUOTES, ENT_NOQUOTES
 * @param string $charset Кодировка строки
 *
 * @return string Преобразованная строка
 */
function sb_htmlspecialchars($str, $quote = ENT_QUOTES, $charset = '')
{
	if ($str === '')
		return '';

    if ($charset == '')
    {
        // Если кодировки нет, берем из настроек системы
        $charset = SB_CHARSET;
    }

    return htmlspecialchars($str, $quote, $charset);
}

/**
 * Переводит целое число в строку IP-адреса
 *
 * @param int $i Целое число
 * @return string IP-адрес
 */
function sb_inttoip($i)
{
	$d[0] = (int)($i/256/256/256);
	$d[1] = (int)(($i-$d[0]*256*256*256)/256/256);
	$d[2] = (int)(($i-$d[0]*256*256*256-$d[1]*256*256)/256);
	$d[3] = $i - $d[0]*256*256*256 - $d[1]*256*256 - $d[2]*256;

	return $d[0].'.'.$d[1].'.'.$d[2].'.'.$d[3];
}

/**
 * Переводит строку IP-адреса в целое число
 *
 * @param string $ip Строка IP-адреса
 * @return int Целое число
 */
function sb_iptoint($ip)
{
	$a = explode('.', $ip);

	return $a[0]*256*256*256 + $a[1]*256*256 + $a[2]*256 + $a[3];
}

/**
 * Вывод сообщений внутри системы
 *
 * Возможные типы сообщений:
 * <ul>
 * <li><b>information</b> - информационное сообщение</li>
 * <li><b>warning</b> - сообщение об ошибке</li>
 * <li><b>loading</b> - сообщение о загрузке</li>
 * </ul>
 * @param string $str Строка с сообщением, может содержать HTML-теги
 * @param bool $static Выводить статичное (true) или всплывающее (false) сообщение
 * @param string $type Тип сообщения
 * @param bool $return Выводить сообщение или вернуть в виде строки (true - вернуть в виде строки, false - вывести сообщение)
 * @return string Оформленное сообщение
 */
function sb_show_message($str, $static=false, $type='information', $return=false)
{
    switch ($type)
    {
        case 'information':
            $img = 'information.png';
            break;
        case 'warning':
            $img = 'warning.png';
            break;
        default:
            $img = 'loading.gif';
            break;
    }

    $str = ($static ? '<center><br /><br />':'').'<div '.(!$static ? 'class="sb_popup_msg_div" onclick="sbHidePopupMsgDiv();" name="sb_popup_msg_div"' : 'class="sb_static_msg_div"').'><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50"><img src="'.SB_CMS_IMG_URL.'/'.$img.'" class="sb_msg_img"></td><td class="sb_msg_text">'.$str.'</td></tr></table></div>'.($static ? '</center>':'');
    if ($return)
    {
        return $str;
    }
    else
    {
        echo $str;
        return $str;
    }
}

/**
 * Устанавливает cookie
 *
 * Если значение cookie равно '', то cookie удаляется.
 *
 * @param string $name Имя cookie
 * @param mixed $value Значение cookie
 * @param int $expire Время жизни cookie в секундах
 * @param string $path Путь, для которого устанавливается cookie. / - на весь домен.
 * @param bool $subdomain ставить куку на поддомены или нет.
 */
function sb_setcookie($name, $value='', $expire=0, $path='/', $subdomain=true)
{
    $domain = '';
    if (preg_match('/[^0-9\.]+/', SB_COOKIE_DOMAIN)){
		$domain = $subdomain ? ' domain=.'.SB_COOKIE_DOMAIN.';' : ' domain='.SB_COOKIE_DOMAIN.';';
    }


    if ($value == '')
    {
        header('Set-Cookie: '.sb_sanitize_header($name.'=;'.$domain.' expires='.gmdate('D, d M Y H:i:s', time() - 24 * 60 * 60).'; path='.$path.';'), false);
    }
    else
    {
        $value = str_replace('\'', '\\\'', $value);
        header('Set-Cookie: '.sb_sanitize_header($name.'='.$value.';'.$domain.($expire > 0 ? ' expires='.gmdate('D, d M Y H:i:s', $expire).';' : '').'path='.$path.';'), false);
    }
}

/**
 * Callback-функция формирования абсолютного адреса
 *
 * @access private
 * @param mixed $matches
 * @return string Строка с заменой адреса
 */
function sb_make_absolute_url_callback($matches)
{
    $url = parse_url($matches[2]);
    if (!isset($url['scheme']))
    {
        return str_replace($matches[2], SB_DOMAIN.$matches[2], $matches[0]);
    }
    else
    {
        return $matches[0];
    }
}

/**
 * Формирование абсолютных адресов в тексте
 *
 * Все относительные адреса в тегах <b>a</b>, <b>img</b>, <b>area</b> преобразуются в абсолютные адреса.
 *
 * @see SB_DOMAIN
 * @param string $text Ссылка на преобразуемый текст
 */
function sb_make_absolute_url(&$text)
{
    $text = preg_replace_callback("/<a[^>]+href=([\"']?)([^\\s\"'>]+)\\1/i".SB_PREG_MOD, 'sb_make_absolute_url_callback', $text);
    $text = preg_replace_callback("/<img[^>]+src=([\"']?)([^\\s\"'>]+)\\1/i".SB_PREG_MOD, 'sb_make_absolute_url_callback', $text);
    $text = preg_replace_callback("/<area[^>]+href=([\"']?)([^\\s\"'>]+)\\1/i".SB_PREG_MOD, 'sb_make_absolute_url_callback', $text);
}

/**
 * Функция, выполняемая при завершении скрипта
 *
 */
function sb_php_shutdown()
{
	if (version_compare('5.2.0', PHP_VERSION) > 0)
		return;

	$error = error_get_last();
	if (count($error) > 0 && $error['type'] == 1 || $error['type'] == 4 || $error['type'] == 16 ||
	    $error['type'] == 32 || $error['type'] == 64 || $error['type'] == 128)
	{
		sb_php_error_reporting($error['type'], $error['message'], $error['file'], $error['line']);
	}
}

/**
 * Функция записи ошибок PHP в системный журнал
 *
 * Ошибки типа E_STRICT в журнал не записываются.
 *
 * @param int $errorno Код ошибки
 * @param string $error Описание ошибки
 * @param string $file Файл, в котором произошла ошибка
 * @param int $line Строка в файле
 */
function sb_php_error_reporting($errorno, $error, $file, $line)
{
	if (class_exists('sbPlugins') && (sbPlugins::getSetting('sb_system_log_errors') != 1 || sbPlugins::getSetting('sb_system_log_date') <= 0))
		return false;

	if (!function_exists('sb_add_system_message') || $errorno == 2048)
		return false;

    // не пишем в журнал ошибки типа E_STRICT
    $aCallstack = debug_backtrace(false);
    $backtrace = '';
    if (count($aCallstack) > 0)
    {
        $aCallstack = array_reverse($aCallstack);

        $i = 1;
        foreach($aCallstack as $aCall)
		{
			if ($aCall['function'] == 'sb_php_error_reporting' || $aCall['function'] == 'sb_php_shutdown')
				continue;

			if (!isset($aCall['file']))
				$aCall['file'] = KERNEL_ERROR_BACKTRACE_PHP_KERNEL;

			if (!isset($aCall['line']))
				$aCall['line'] = '';

			$backtrace .= '<tr'.($i % 2 != 0 ? ' class="even_debug"' : ' class="odd_debug"').'><td>'.($i++).'</td><td>'.$aCall['function'].'</td><td>'.sb_str_replace(SB_BASEDIR, '', sb_str_replace('\\', '/', $aCall['file'])).'</td><td>'.$aCall['line'].'</td></tr>';
		}

		if ($i > 1)
		{
			$backtrace = '<br /><br /><span style="color:red;">'.KERNEL_ERROR_BACKTRACE.'</span>:<br /><br /><div align="center">
				<table class="form" width="95%" cellspacing="1" cellpadding="3">
        		<tr><th class="debug">№</th><th class="debug">'.KERNEL_ERROR_BACKTRACE_FUNCTION.'</th><th class="debug">'.KERNEL_ERROR_BACKTRACE_FILE.'</th><th class="debug">'.KERNEL_ERROR_BACKTRACE_LINE.'</th></tr>'
				.$backtrace.
				'</table></div><br />';
		}
    }

    $uri = '';
    if(isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '')
    {
        $uri = '<br /><br /><span style="color:red;">URI</span>: '.(sb_stripos($_SERVER['REQUEST_URI'], SB_COOKIE_DOMAIN) === false ? 'http://'.SB_COOKIE_DOMAIN : '').$_SERVER['REQUEST_URI'];
    }

    sb_add_system_message('<span style="color:red;">'.KERNEL_ERROR_PHP.'</span>:<br /><br />'.$error.' '.KERNEL_IN_FILE.' '.$file.' '.KERNEL_ON_LINE.' '.$line.$uri.$backtrace, SB_MSG_PHP_ERROR);

    return true;
}

/**
 * Функция убирает лишние слэши (/) из всех значений массива
 *
 * @param array $array Массив, из значений которого надо убирать слэши
 */
function sb_array_stripslashes(&$array)
{
    if (is_array($array))
    {
        while ((list($key) = each($array)) != false)
        {
            if (is_array($array[$key]))
            {
                sb_array_stripslashes($array[$key]);
            }
            else
            {
                $array[$key] = stripslashes($array[$key]);
            }
        }
        reset($array);
    }
}

/**
 * Функция возвращает название часового пояса по смещению времени
 *
 * @param int $offset Смещение времени.
 *
 * @return Название часового пояса.
 */
function sb_get_timezone_by_offset($offset)
{
	$timezones = array(
		'-12' => 'Pacific/Kwajalein',
        '-11' => 'Pacific/Samoa',
        '-10' => 'Pacific/Honolulu',
        '-9' => 'America/Juneau',
        '-8' => 'America/Los_Angeles',
        '-7' => 'America/Denver',
        '-6' => 'America/Mexico_City',
        '-5' => 'America/New_York',
        '-4' => 'America/Barbados',
        '-3' => 'America/Argentina/Buenos_Aires',
		'-2' => 'Atlantic/Azores',
        '-1' => 'Atlantic/Azores',
        '0' => 'Europe/London',
        '1' => 'Europe/Paris',
        '2' => 'Europe/Helsinki',
		'3' => 'Asia/Bahrain',
		'4' => 'Europe/Moscow',
        '5' => 'Asia/Karachi',
        '6' => 'Asia/Almaty',
		'7' => 'Asia/Bangkok',
		'8' => 'Asia/Singapore',
		'9' => 'Asia/Tokyo',
        '10' => 'Pacific/Guam',
        '11' => 'Asia/Sakhalin',
        '12' => 'Asia/Kamchatka'
	);

	if (isset($timezones[$offset]))
	{
		return $timezones[$offset];
	}

	return 'Europe/Moscow';
}

/**
 * Возвращает строку представления даты и времени согласно заданному формату
 *
 * @param string $layout Формат вывода даты и времени.
 * @param int $time Время (UNIX timestamp).
 * @param int $time_zone Временная зона (от -12 до 13).
 *
 * @return string Строка представления даты и времени согласно заданному формату.
 */
function sb_date($layout, $time=false, $time_zone=false)
{
	if ($time === '')
		return '';

	if ($time === false)
    {
        $time = time();
    }
    else
    {
    	$time = intval($time);
    }

    if ($time_zone === false)
    {
        $time_zone = sbPlugins::getSetting('sb_timezone');
    }

    $time_zone = sb_get_timezone_by_offset($time_zone);
    $date = '';

    if (class_exists('DateTime') && class_exists('DateTimeZone'))
    {
        $dateobj = new DateTime("@$time");
        $dateobj->setTimezone(new DateTimeZone($time_zone));
        $date = $dateobj->format($layout);
    }
    else if (function_exists('date_default_timezone_set'))
    {
    	$env_zone = date_default_timezone_get();

    	date_default_timezone_set($time_zone);
    	$date = date($layout, $time);
    	date_default_timezone_set($env_zone);
    }
	elseif (ini_get('safe_mode') != 1 && strtoupper(ini_get('safe_mode')) != 'ON')
	{
		$env_zone = '';
        if(isset($_ENV['TZ']) && getenv('TZ'))
        {
            $env_zone = getenv('TZ');
        }

        putenv('TZ='.$time_zone);

        $date = date($layout, $time);

        if ($env_zone != '')
        {
        	putenv('TZ=' . $env_zone);
        }
	}
	else
	{
		$date = date($layout, $time);
	}

    return $date;
}

/**
 * Переводит строковое представление даты (d.m.Y H:i:s) в Unix Timestamp
 *
 * @param string $date Строковое представление даты (d.m.Y H:i:s). Часть H:i может отсутствовать.
 * @param string $temp Шаблон ввода дат ({DAY}, {MONTH}, {LONG_YEAR}, {SHORT_YEAR}, {HOUR}, {MINUTE}). Если равен пустоте, то предполагаем, что дата приходит в формате d.m.Y H:i
 *
 * @return int Unix Timestamp
 */
function sb_datetoint($date, $temp = '')
{
	if (trim($date) == '')
		return null;

	$day = 1;
	$month = 1;
	$year = 1970;
	$hour = 0;
	$minute = 0;
	$sec = 0;

	if ($temp != '')
	{
		$tags = array();
        preg_match_all('/\{[_A-Z]+\}/'.SB_PREG_MOD, $temp, $tags);
        if ($tags[0])
        {
        	$date = preg_replace('/[^0-9]+/', '', $date);

        	foreach ($tags[0] as $tag)
        	{
        		switch ($tag)
        		{
        			case '{DAY}':
        				$day = substr($date, 0, 2);
        				$date = substr($date, 2);
        				break;

        			case '{MONTH}':
        				$month = substr($date, 0, 2);
        				$date = substr($date, 2);
        				break;

        			case '{SHORT_YEAR}':
        				$year = substr($date, 0, 2);
        				$date = substr($date, 2);
        				$year += 2000;
        				break;

        			case '{LONG_YEAR}':
        				$year = substr($date, 0, 4);
        				$date = substr($date, 4);
        				break;

        			case '{HOUR}':
        				$hour = substr($date, 0, 2);
        				$date = substr($date, 2);
        				break;

        			case '{MINUTE}':
        				$minute = substr($date, 0, 2);
        				$date = substr($date, 2);
        				break;
        		}
        	}
        }
        else
        {
        	return null;
        }
	}
	else
	{
	    $datetime = explode(' ', trim($date));

	    $date = explode('.', trim($datetime[0]));
	    $num = count($date);
		switch($num)
		{
			case 1:
				$day = intval($date[0]);
				break;

			case 2:
				$day = intval($date[0]);
				$month = intval($date[1]);
				break;

			case 3:
				$day = intval($date[0]);
				$month = intval($date[1]);
				$year = intval($date[2]);
				break;
		}

		if (isset($datetime[1]))
		{
		    $time = explode(':', trim($datetime[1]));
			$num = count($time);
			switch($num)
			{
				case 1:
					$hour = intval($time[0]);
					break;

				case 2:
					$hour = intval($time[0]);
					$minute = intval($time[1]);
					break;

				case 3:
					$hour = intval($time[0]);
					$minute = intval($time[1]);
					$sec = intval($time[2]);
					break;
			}
		}
	}

    $time_zone = sb_get_timezone_by_offset(sbPlugins::getSetting('sb_timezone'));

    if (class_exists('DateTime') && class_exists('DateTimeZone'))
    {
        $dateobj = new DateTime(null, new DateTimeZone($time_zone));
        $dateobj->setDate($year, $month, $day);
        $dateobj->setTime($hour, $minute, $sec);
        $result = $dateobj->format('U');
    }
    else if (function_exists('date_default_timezone_set'))
    {
        $env_zone = date_default_timezone_get();
        date_default_timezone_set($time_zone);
        $result = mktime($hour, $minute, $sec, $month, $day, $year);
        date_default_timezone_set($env_zone);
    }
    elseif (ini_get('safe_mode') != 1 && strtoupper(ini_get('safe_mode')) != 'ON')
    {
        $env_zone = '';
        if(isset($_ENV['TZ']) && getenv('TZ'))
        {
            $env_zone = getenv('TZ');
        }
        putenv('TZ='.$time_zone);
        $result = mktime($hour, $minute, $sec, $month, $day, $year);
        if ($env_zone != '')
        {
            putenv('TZ=' . $env_zone);
        }
    }
    else
        $result = mktime($hour, $minute, $sec, $month, $day, $year);

	if(!$result)
		return null;

	return $result;
}

/**
 * Уменьшает размер изображения
 *
 * @param string $src Путь и имя файла уменьшаемого изображения.
 * @param string $dest Путь и имя файла, куда будет сохранено сжатое изображение.
 * @param int $width Ширина генерируемого изображения.
 * @param int $height Высота генерируемого изображения.
 * @param int $quality Процент сжатия генерируемого изображения.
 *
 * @return bool TRUE, если изображение сжато успешно, FALSE в ином случае.
 */
function sb_resize_image($src, $dest, $width, $height, $quality=80)
{
    $width = intval($width);
    $height = intval($height);
    $quality = intval($quality);

    // вытаскиваем расширение файла
    $im = $GLOBALS['sbVfs']->getimagesize($src);
    if (!$im)
        return false;

    // определяем начальную высоту и ширину картинки
    $srcWidth = $im[0];
    $srcHeight = $im[1];
    $type = $im[2];

    // проверяем, превышают ли они переданные в функцию
    if (($srcWidth > $width && $width > 0) || ($srcHeight > $height && $height > 0))
    {
        $srcImg = false;
        switch($type)
        {
            case IMAGETYPE_JPEG:
                $srcImg = $GLOBALS['sbVfs']->imagecreatefromjpeg($src);
                break;

            case IMAGETYPE_PNG:
                $srcImg = $GLOBALS['sbVfs']->imagecreatefrompng($src);
                break;

            case IMAGETYPE_GIF:
               $srcImg = $GLOBALS['sbVfs']->imagecreatefromgif($src);
                break;

            default:
                return false;
        }

        if (!$srcImg)
            return false;

        if ($width > 0)
            $ratioWidth = $srcWidth / $width;
        else
            $ratioWidth = 0;

        if ($height > 0)
            $ratioHeight = $srcHeight / $height;
        else
            $ratioHeight = 0;

        if($ratioWidth < $ratioHeight)
        {
            $destHeight = $height;
            $destWidth = round($srcWidth / $ratioHeight, 0);
        }
        else
        {
            $destHeight = round($srcHeight / $ratioWidth, 0);
            $destWidth = $width;
        }

        // создаем новую картинку с конечными данными ширины и высоты
        $destImg = function_exists('imagecreatetruecolor') ? @imagecreatetruecolor($destWidth, $destHeight) : imagecreate($destWidth, $destHeight);
        if (!$destImg)
        {
            imagedestroy($srcImg);
            return false;
        }

        if ($type == IMAGETYPE_GIF)
        {
            $t_idx = imagecolortransparent($srcImg);

            if ($t_idx >= 0)
            {
                $t_color = imagecolorsforindex($srcImg, $t_idx);
                $t_idx = imagecolorallocate($destImg, $t_color['red'], $t_color['green'], $t_color['blue']);
                imagefill($destImg, 0, 0, $t_idx);
                imagecolortransparent($destImg, $t_idx);
            }
        }

        if ($type == IMAGETYPE_PNG)
        {
            imagealphablending($destImg, false);
        }

        // копируем srcImage (исходная) в destImage (конечную)
        if (function_exists('imagecopyresampled') && $type != IMAGETYPE_GIF)
        {
            $res = imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
        }
        else
        {
            $res = imagecopyresized($destImg, $srcImg, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
        }

        if (!$res)
        {
            imagedestroy($srcImg);
            imagedestroy($destImg);
            return false;
        }

        $res = true;
        switch($type)
        {
            case IMAGETYPE_JPEG:
                //сохраняем уменьшенный файл в jpeg с качеством 80%(больше не имеет смысла)
                $res = $GLOBALS['sbVfs']->imagejpeg($destImg, $dest, $quality);
                break;

            case IMAGETYPE_PNG:
                imagesavealpha($destImg, true);
                $res = $GLOBALS['sbVfs']->imagepng($destImg, $dest, min(9, round(10 - $quality / 10)));
                break;

            case IMAGETYPE_GIF:
                $res = $GLOBALS['sbVfs']->imagegif($destImg, $dest);
                break;

            default:
                return false;
        }

        // освобождаем память
        imagedestroy($srcImg);
        imagedestroy($destImg);

        return $res;

    }
    else
    {
        $GLOBALS['sbVfs']->copy($src, $dest);
    }

    return true;
}

/**
 * Накладывает водяной знак в виде текста или картинки на изображение
 *
 * @param string $src Путь и имя файла исходного изображения.
 * @param string $dest Путь и имя файла, куда будет сохранено новое изображение.
 * @param set $position Позиция водяного знака. Возможные значения:
 * <ul>
 *   <li>TL - Верхний левый угол
 *   <li>TM - Сверху по центру
 *   <li>TR - Верхний правый угол
 *   <li>CL - По центру слева
 *   <li>C - По центру
 *   <li>CR - По центру справа
 *   <li>BL - Нижний левый угол
 *   <li>BM - Снизу по центру
 *   <li>BR - Нижний правый угол
 *   <li>RND - Случайным образом
 * </ul>
 * @param integer $opacity Процент прозрачности водяного знака.
 * @param integer $margin Отступ для водяного знака.
 * @param string $watermark Путь к файлу с водяным знаком.
 * @param string $copyright Текст, накладываемый на изображение.
 * @param string $color Цвет текста, накладываемого на изображение.
 * @param string $font Шрифт для текста, накладываемого на изображение. Возможные значения:
 * <ul>
 *   <li> arial.ttf - Arial
 *   <li> tahoma.ttf - Tahoma
 *   <li> times.ttf - Times New Roman
 *   <li> verdana.ttf - Verdana
 * </ul>
 * @param integer $font_size Размер шрифта в пикселах для текста, накладываемого на изображение.
 *
 * @return bool TRUE, если водяной знак наложен успешно, FALSE в ином случае.
 */
function sb_watermark_image($src, $dest, $position='BR', $opacity='60', $margin='10', $watermark = '', $copyright = '', $color='#000000', $font = 'arial.ttf', $size = 11)
{
	$opacity = intval($opacity);
	$margin = intval($margin);
	$size = intval($size);
	$color = strtoupper($color);

	if($watermark == '' && $copyright == '')
    {
        return false;
    }

    $src_im = $GLOBALS['sbVfs']->getimagesize($src);
    $src_width  = $src_im[0];
	$src_height = $src_im[1];
	$src_type = $src_im[2];

	switch($src_type)
    {
        case IMAGETYPE_JPEG:
            $src_img = $GLOBALS['sbVfs']->imagecreatefromjpeg($src);
            break;

        case IMAGETYPE_PNG:
            $src_img = $GLOBALS['sbVfs']->imagecreatefrompng($src);
            break;

        case IMAGETYPE_GIF:
            $src_img = $GLOBALS['sbVfs']->imagecreatefromgif($src);
            break;

        default:
			return false;
	}

	if(!$src_img)
		return false;

	if ($position == 'RND')
	{
		$pos_ar = array(0 => 'TL',
		                1 => 'TM',
		                2 => 'TR',
		                3 => 'CL',
		                4 => 'C',
		                5 => 'CR',
		                6 => 'BL',
		                7 => 'BM',
		                8 => 'BR');

		$position = $pos_ar[rand(0, 8)];
    }

    if ($watermark != '')
    {
        if (substr($watermark, 0, 7) == 'http://')
        {
            $w_im = getimagesize($watermark);
            if (!$w_im)
            {
                imagedestroy($src_img);
                return false;
            }
    		$w_width  = $w_im[0];
    		$w_height = $w_im[1];
    		$w_type   = $w_im[2];

    		switch($w_type)
            {
                case IMAGETYPE_JPEG:
                    $w_img = imagecreatefromjpeg($watermark);
                    break;

                case IMAGETYPE_PNG:
                    $w_img = imagecreatefrompng($watermark);
                    break;

                case IMAGETYPE_GIF:
                    $w_img = imagecreatefromgif($watermark);
                    break;

                default:
                    imagedestroy($src_img);
                    return false;
            }
        }
        else
        {
            $w_im = $GLOBALS['sbVfs']->getimagesize($watermark);
            if (!$w_im)
            {
                imagedestroy($src_img);
                return false;
            }
    		$w_width  = $w_im[0];
    		$w_height = $w_im[1];
    		$w_type   = $w_im[2];

    		switch($w_type)
            {
                case IMAGETYPE_JPEG:
                    $w_img = $GLOBALS['sbVfs']->imagecreatefromjpeg($watermark);
                    break;

                case IMAGETYPE_PNG:
                    $w_img = $GLOBALS['sbVfs']->imagecreatefrompng($watermark);
                    break;

                case IMAGETYPE_GIF:
                    $w_img = $GLOBALS['sbVfs']->imagecreatefromgif($watermark);
                    break;

                default:
                    imagedestroy($src_img);
                    return false;
            }
        }

        if (!$w_img)
        {
            imagedestroy($src_img);
            return false;
        }
        elseif($w_type == 3)
        {
        	imagealphablending($w_img, false);
            imagesavealpha($w_img, true);

			$trcolor = ImageColorAllocate($w_img, 255, 255, 255);
			ImageColorTransparent($w_img , $trcolor);
		}

        $w_left = $margin;
        $w_top = $margin;

		switch ($position)
		{
		    case 'TM':
		        $w_left = ($src_width - $w_width) / 2;
		        break;

		    case 'TR':
		        $w_left = $src_width - $w_width - $margin;
		        break;

		    case 'CL':
		        $w_top = ($src_height - $w_height) / 2;
		        break;

		    case 'C':
		        $w_left = ($src_width - $w_width) / 2;
		        $w_top = ($src_height - $w_height) / 2;
		        break;

		    case 'CR':
		        $w_left = $src_width - $w_width - $margin;
		        $w_top = ($src_height - $w_height) / 2;
		        break;

		    case 'BL':
		        $w_top = $src_height - $w_height - $margin;
		        break;

		    case 'BM':
		        $w_left = ($src_width - $w_width) / 2;
		        $w_top = $src_height - $w_height - $margin;
		        break;

		    case 'BR':
		        $w_left = $src_width - $w_width - $margin;
		        $w_top = $src_height - $w_height - $margin;
		        break;
		}

		if ($src_type == IMAGETYPE_PNG)
		{
		    imagealphablending($src_img, false);
		}

        if($w_type == 3)
        {
			$result = imagecopy($src_img, $w_img, $w_left, $w_top, 0, 0, $w_width, $w_height);
        }
        else
        {
			$result = imagecopymerge($src_img, $w_img, $w_left, $w_top, 0, 0, $w_width, $w_height, $opacity);
        }
		if(!$result)
        {
             imagedestroy($w_img);
             imagedestroy($src_img);
             return false;
        }

        if ($src_type == IMAGETYPE_PNG)
        {
            imagesavealpha($src_img, true);
        }

        imagedestroy($w_img);
    }

    if ($copyright != '')
    {
        $font = SB_CMS_LANG_PATH.'/fonts/'.$font;
        if (!file_exists($font))
        {
            imagedestroy($src_img);
            return false;
        }

        $matches = array();
        if (!preg_match('/^#?([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})$/i', $color, $matches))
        {
            imagedestroy($src_img);
            return false;
        }

        $c_sizes = imagettfbbox($size, 0, $font, $copyright);
        if (!$c_sizes)
        {
            imagedestroy($src_img);
            return false;
        }

        switch($src_type)
        {
            case IMAGETYPE_JPEG:
                $w_img = $GLOBALS['sbVfs']->imagecreatefromjpeg($src);
                break;

            case IMAGETYPE_PNG:
                $w_img = $GLOBALS['sbVfs']->imagecreatefrompng($src);
                break;

            case IMAGETYPE_GIF:
                $w_img = $GLOBALS['sbVfs']->imagecreatefromgif($src);
                break;

            default:
                return false;
        }

        if (!$w_img)
        {
            imagedestroy($src_img);
            return false;
        }

        if ($watermark != '')
        {
            if (!imagecopy($w_img, $src_img, 0, 0, 0, 0, $src_width, $src_height))
            {
                imagedestroy($src_img);
                imagedestroy($w_img);
                return false;
            }
        }

        $w_left = abs(min($c_sizes[0], $c_sizes[6])) + $margin;
        $w_top = abs(min($c_sizes[5], $c_sizes[7]));
        $w_width = abs($c_sizes[4] - $c_sizes[0]);
        $w_height = abs($c_sizes[5] - $c_sizes[1]);

        switch ($position)
		{
		    case 'TL':
		        $w_top += $margin;
		        break;

		    case 'TM':
		        $w_left = ($src_width - $w_width) / 2;
		        $w_top += $margin;
		        break;

		    case 'TR':
		        $w_left = $src_width - $w_width - $margin;
		        $w_top += $margin;
		        break;

		    case 'CL':
		        $w_top = ($src_height - $w_height + $w_top) / 2;
		        break;

		    case 'C':
		        $w_left = ($src_width - $w_width) / 2;
		        $w_top = ($src_height - $w_height + $w_top) / 2;
		        break;

		    case 'CR':
		        $w_left = $src_width - $w_width - $margin;
		        $w_top = ($src_height - $w_height + $w_top) / 2;
		        break;

		    case 'BL':
		        $w_top = $src_height - $margin;
		        break;

		    case 'BM':
		        $w_left = ($src_width - $w_width) / 2;
		        $w_top = $src_height - $margin;
		        break;

		    case 'BR':
		        $w_left = $src_width - $w_width - $margin;
		        $w_top = $src_height - $margin;
		        break;
		}

        $red = hexdec($matches[1]);
	    $green = hexdec($matches[2]);
	    $blue = hexdec($matches[3]);

        $color  = imagecolorallocate($w_img, $red, $green, $blue);

        if (SB_CHARSET != 'UTF-8')
            $copyright = iconv(SB_CHARSET, 'UTF-8//IGNORE', $copyright);

        imagettftext($w_img, $size, 0, $w_left, $w_top, $color, $font, $copyright);

        if ($src_type == IMAGETYPE_PNG)
        {
            imagealphablending($src_img, false);
            imagealphablending($w_img, false);

            if (!imagecopy($src_img, $w_img, 0, 0, 0, 0, $src_width, $src_height))
            {
                imagedestroy($src_img);
                imagedestroy($w_img);
                return false;
            }

            imagesavealpha($src_img, true);
        }
        elseif (!imagecopymerge($src_img, $w_img, 0, 0, 0, 0, $src_width, $src_height, $opacity))
        {
            imagedestroy($src_img);
            imagedestroy($w_img);
            return false;
        }

        imagedestroy($w_img);
    }

    switch($src_type)
    {
        case IMAGETYPE_JPEG:
            $res = $GLOBALS['sbVfs']->imagejpeg($src_img, $dest, 80);
            break;

        case IMAGETYPE_PNG:
            $res = $GLOBALS['sbVfs']->imagepng($src_img, $dest, 2);
            break;

        case IMAGETYPE_GIF:
            $res = $GLOBALS['sbVfs']->imagegif($src_img, $dest);
            break;

        default:
            imagedestroy($src_img);
            return false;
    }

    imagedestroy($src_img);

    if (!$res)
        return false;

    return true;
}

/**
 * Разархивирует ZIP-архив в указанную папку
 *
 * @param string $file Полный путь к ZIP-архиву.
 * @param string $path Путь, куда будет разархивирован архив.
 * @param array $accept_types Допустимые типы файлов.
 * @param bool $overwrite Перезаписывать существующие файлы.
 *
 * @return mixed Массив файлов, извлеченных из архива (с путями) или FALSE, в случае возникновения ошибки.
 */
function sb_unzip($file, $path, $accept_types = array(), $overwrite=false)
{
    $path = rtrim($path, '/');
    $zip = zip_open($file);

    if (!is_resource($zip))
        return false;

    foreach ($accept_types as $key => $value)
    {
    	$accept_types[$key] = sb_strtoupper($value);
    }

    $result = array();
    while (($entry = zip_read($zip)) != false)
    {
        if (zip_entry_filesize($entry))
        {
        	$entry_name = iconv('CP866', SB_CHARSET.'//IGNORE', zip_entry_name($entry));
        	if ($entry_name == '')
        		$entry_name = zip_entry_name($entry);

        	$dir = dirname($entry_name);
        	$file_name = basename($entry_name);

        	if ($dir != '' && $dir != '.' && $dir != '..')
            {
            	$dir = explode('/', str_replace(array(' ', '%20', '\\'), array('_', '_', '/'), $dir));
            	foreach ($dir as $key => $value)
            	{
            		$dir[$key] = sb_strtolat($value);
            	}
            	$dir = implode('/', $dir);

            	$dir = preg_replace('/[^a-zA-Z0-9\._\-\/]/'.SB_PREG_MOD, '', $dir);
            	if (!$GLOBALS['sbVfs']->exists($path.'/'.$dir))
                {
                    if (!$GLOBALS['sbVfs']->mkdir($path.'/'.$dir))
                        return false;
                }
            }
            else
            {
                $dir = '';
            }

        	if (!zip_entry_open($zip, $entry))
            {
                return false;
            }

			$file_name = sb_strtolat($file_name);

        	$file_ext = pathinfo($file_name);
			$file_ext = isset($file_ext['extension']) ? $file_ext['extension'] : '';

			if ($file_ext != '')
			{
			    $pos = strrpos($file_name, '.'.$file_ext);
				$file_raw_name = substr($file_name, 0, $pos);
			}
			else
			{
				$file_raw_name = $file_name;
			}

            // проверяем, есть ли расширение файла в списке разрешенных расширений
            if (count($accept_types) != 0 && !in_array(sb_strtoupper($file_ext), $accept_types))
                continue;

		    //только буквы, цифры, знак подчеркивания, точка и тире
			$file_name = preg_replace('/[^a-zA-Z0-9\._-]/'.SB_PREG_MOD, '', str_replace(array(' ', '%20'), array('_', '_'), $file_name));
			$file_name = str_replace('//', '/', '/'.$dir.'/'.$file_name);

        	if ($GLOBALS['sbVfs']->exists($path.$file_name) && !$overwrite)
		    {
    		    $n = 0;
    		    $copy = '_copy'.$n;
    		    $file_name = str_replace('//', '/', '/'.$dir.'/'.$file_raw_name.$copy.'.'.$file_ext);

    		    // создать новый с индексом
    			while($GLOBALS['sbVfs']->exists($path.$file_name))
    			{
    			    $n++;
    				$copy = '_copy'.$n;

    				$file_name = str_replace('//', '/', '/'.$dir.'/'.$file_raw_name.$copy.'.'.$file_ext);
    			}
		    }

            $fh = $GLOBALS['sbVfs']->fopen($path.$file_name, 'w');
            if (!$fh)
            {
                return false;
            }

            if (!$GLOBALS['sbVfs']->fwrite(zip_entry_read($entry, @zip_entry_filesize($entry))))
            {
                return false;
            }

            $GLOBALS['sbVfs']->fclose();
            zip_entry_close($entry);

            $result[] = $file_name;
        }
    }
    return $result;
}

/**
 * Создает GZ-архив
 *
 * @param string $src Полный путь к файлу, который следует поместить в GZ-архив.
 * @param string $path Полный путь к создаваемому архиву.
 *
 * @return mixed TRUE, если массив создан успешно, FALSE, в случае возникновения ошибки.
 */
function sb_gzip($src, $dest)
{
	$fp = fopen($src, 'rb');
	if (!$fp)
		return false;

	if (function_exists('gzopen64'))
    	$zp = gzopen64($dest, 'wb6');
    else
    	$zp = gzopen($dest, 'wb6');

    if (!$zp)
    {
    	fclose($fp);
    	return false;
    }

	while (!feof($fp))
	{
		$str = fread($fp, 65535);
		if (!gzwrite($zp, $str))
			return false;
	}

	gzclose($zp);
	fclose($fp);

	return true;
}

/**
 * @ignore
 */
function sb_encrypt($string, $key)
{
    $result = '';
    for($i = 0; $i < strlen($string); $i++)
    {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key))-1, 1);
        $char = chr(ord($char)+ord($keychar));
        $result .= $char;
    }

    return base64_encode($result);
}

/**
 * @ignore
 */
function sb_decrypt($string, $key)
{
    $result = '';
    $string = base64_decode($string);

    for($i = 0; $i < strlen($string); $i++)
    {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key))-1, 1);
        $char = chr(ord($char)-ord($keychar));
        $result .= $char;
    }

    return $result;
}

/**
 * Заменяет имя домена на пустую строку в значениях массива
 *
 * @param array $array Массив, в котором производится замена.
 */
function sb_replace_domain(&$array)
{
    if (is_array($array))
    {
        while ((list($key) = each($array)) != false)
        {
            if (is_array($array[$key]))
            {
                sb_replace_domain($array[$key]);
            }
            else
            {
            	$str = preg_replace('!(href|src)(.*?)("|\')(http://www.'.SB_COOKIE_DOMAIN.')([^"\'].*?)!iU', '$1$2$3$5', $array[$key]);
            	if ($str != '')
            	{
                	$str = preg_replace('!(href|src)(.*?)("|\')(http://'.SB_COOKIE_DOMAIN.')([^"\'].*?)!iU', '$1$2$3$5', $str);
                	if ($str != '')
                	{
                		$str = $array[$key];
                	}
            	}
            	else
            	{
            		$str = $array[$key];
            	}

            	$array[$key] = $str;
            }
        }
    }
    reset($array);
}

/**
 * Вырезает из значений массива PHP-код
 *
 * @param array $array Массив, в котором производится замена.
 */
function sb_replace_php(&$array)
{
    if (is_array($array))
    {
        while ((list($key) = each($array)) != false)
        {
            if (is_array($array[$key]))
            {
                sb_replace_php($array[$key]);
            }
            else
            {
                $array[$key] = preg_replace('%<\?.*?\?>%si'.SB_PREG_MOD, '', $array[$key]);
            }
        }

        reset($array);
    }
    elseif (is_string($array))
    {
    	$array = preg_replace('%<\?.*?\?>%si'.SB_PREG_MOD, '', $array);
    }
}

/**
 * Вывод персонажа
 *
 * @ignore
 */
function sb_show_character()
{
    //вывод персонажа, если у события есть подсказки
    $event_help = $_SESSION['sbPlugins']->getEventTip($_GET['event']);
    if (!$event_help || sbPlugins::getUserSetting('sb_character') == '-')
    {
    	return;
    }

    $dir = SB_CMS_CHARACTERS_PATH.'/'.sbPlugins::getUserSetting('sb_character').'/';
    if (!is_dir($dir))
    	return;

    $url = SB_CMS_CHARACTERS_URL.'/'.sbPlugins::getUserSetting('sb_character').'/';

    // считываем все файлы из директории персонажа
    $files = scandir($dir);
    $characters = array();

    if ($files)
    {
        for ($i = 0; $i < count($files); $i++)
        {
            if ($files[$i] == '.' || $files[$i] == '..' || is_dir($dir.$files[$i]))
                continue;

            // если файл не картинка, игнорируем его
	        $ext = pathinfo($files[$i]);
			$ext = isset($ext['extension']) ? sb_strtolower($ext['extension']) : '';

			if ($ext != 'gif' && $ext != 'png' && $ext != 'jpg' && $ext != 'jpeg')
				continue;

            $ar = getimagesize($dir.$files[$i]);
            if (!in_array($ar[2], array(1,2,3)))
                continue;

            $characters[] = $url.$files[$i];
        }
    }

    $num = count($characters);
    if ($num != 0)
    {
        // выбираем случаным образом картинку персонажа
        $i = rand(0, $num - 1);

        echo '
            <script src="'.SB_CMS_JSCRIPT_URL.'/sbCharacter.js.php"></script>
            <div id="sb_character_help">
            <table cellpadding="0" cellspacing="0" width="100%" id="sb_character_table">
            <tr style="height:7px;">
                <td width="7"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_tl.gif" width="7" height="7"></td>
                <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_t.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="7"></td>
                <td width="18" style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_t.gif);"></td>
                <td width="7"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_tr.gif" width="7" height="7"></td>
            </tr>
            <tr>
                <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_l.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="1"></td>
                <td style="background-color: #ffffe1;" id="sb_character_hint">'.$event_help[0].'</td>
                <td style="background-color: #ffffe1;" valign="top"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_button.gif" width="18" height="18" style="cursor:hand;cursor:pointer;" onclick="sbCloseCharHelp();"></td>
                <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_r.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="1"></td>
            </tr>
            <tr>
                <td valign="top"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_bl.gif" width="7" height="7"></td>
                <td style="padding-right: 10px;background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_bottom_bg.gif);" align="right"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_bottom.gif" width="20" height="27"></td>
                <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_bottom_bg.gif);"></td>
                <td valign="top"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_br.gif" width="7" height="7"></td>
            </tr>
            </table>
            </div>
            <script>
                var sb_char_help_url = "'.SB_CMS_EMPTY_FILE.'?event=event_tip&tip_event='.$_GET['event'].'";
            </script>
            <img src="'.$characters[$i].'" id="sb_character_img" ondblclick="sbShowCharHelp();" onclick="sbShowCharHelp();" help_index="'.$event_help[1].'" title="'.KERNEL_CHARACTER_ALT.'">';
    }
}

/**
 * Проверка и инициализация пользовательского интерфейса
 *
 * @ignore
 */
function sb_init_user_interface()
{
	if (!isset($_SESSION['sbAuth']))
    {
        // сессия пользователя упала
        exit ('
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
        <html lang="ru">
        <head>
        <style type="text/css">
        img
        {
            behavior: url("'.SB_CMS_JSCRIPT_URL.'/sbFixPng.htc.php");
        }
        </style>
        <link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbContent.css">
        <title></title>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbContent.js.php"></script>
        </head>
        <body>'.sb_show_message(KERNEL_NO_SESSIONS, true, 'warning', true).'</body></html>');
    }

    $_SESSION['sbAuth']->updateEventSession();

    /**
     * Если в настройках системы включена опция 'Убирать PHP-код', то вырезаем PHP-код
     */
    if ($_SESSION['sbAuth']->checkStripPHP())
    {
        sb_replace_php($_POST);
        sb_replace_php($_GET);
        sb_replace_php($_COOKIE);
        sb_replace_php($_REQUEST);
    }

    $GLOBALS['sbVfs']->mFileMode = octdec(sbPlugins::getSetting('sb_file_rights'));
    $GLOBALS['sbVfs']->mDirMode = octdec(sbPlugins::getSetting('sb_folder_rights'));

    if ($_SESSION['sbAuth']->mError != '')
    {
        exit ('
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
        <html lang="ru">
        <head>
        <style type="text/css">
        img
        {
            behavior: url(/cms/jscript/sbFixPng.htc.php);
        }
        </style>
        <link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbContent.css">
        <title></title>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbContent.js.php"></script>
        </head>
        <body>'.sb_show_message($_SESSION['sbAuth']->mError, true, 'warning', true).'</body></html>');
    }

    if (!isset($_GET['event']))
    {
        $_GET['event'] = 'pl_kernel_read';
    }
    else
    {
        $_GET['event'] = preg_replace('/[^a-zA-Z0-9_]+/', '', $_GET['event']);

        if (empty($_GET['event']))
        {
            $_GET['event'] = 'pl_kernel_read';
        }
    }

    // подключаем все языковые файлы модулей для inc-файлов
    $inc_lang_files = $_SESSION['sbPlugins']->getIncLangFiles();
    if ($inc_lang_files)
    {
        foreach ($inc_lang_files as $value)
        {
            require_once($value);
        }
    }

    // подлючаем все inc-файлы модулей
    $inc_files = $_SESSION['sbPlugins']->getIncFiles();
    if ($inc_files)
    {
        foreach ($inc_files as $value)
        {
            require_once($value);
        }
    }

    // определяем описание события модуля
    $event_title = $_SESSION['sbPlugins']->getEventTitle($_GET['event']);
    if (!$event_title)
    {
        $event_title = '';
    }

    return $event_title;
}

/**
 * Инициализация интерфейса работы с модулями
 *
 * @ignore
 */
function sb_init_plugin_interface()
{
    $error = false;
    $ar = $_SESSION['sbPlugins']->getEventFunction($_GET['event']);

    if (!$ar)
    {
        // Событие не зарегистрировано в системе
        sb_show_message(KERNEL_NO_EVENT, true, 'warning');
        $error = true;
    }
    else
    {
        // подключаем файл с языковыми константами
        if (!$error && $ar['lang_file'] != '')
        {
            if (file_exists($ar['lang_file']))
            {
                require_once($ar['lang_file']);
            }
            else
            {
                sb_show_message(sprintf(KERNEL_NO_LANG_FILE, '<b>'.str_replace(SB_BASEDIR.'/', '', $ar['lang_file']).'</b>'), true, 'warning');
                $error = true;
            }
        }

        // подключаем файл, содержащий функцию-обработчик вызываемого события
        if ($ar['include_file'] != '')
        {
            if (file_exists($ar['include_file']))
            {
                require_once($ar['include_file']);
            }
            else
            {
                sb_show_message(sprintf(KERNEL_NO_FILE, '<b>'.str_replace(SB_BASEDIR.'/', '', $ar['include_file']).'</b>'), true, 'warning');
                $error = true;
            }
        }

        // вызываем функцию-обработчик события
        if (!$error)
        {
            if (function_exists($ar['function']))
            {
                $ar['function']();
            }
            else
            {
                sb_show_message(sprintf(KERNEL_NO_FUNCTION, '<b>'.$ar['function'].'()</b>'), true, 'warning');
                $error = true;
            }
        }
    }

    return $error;
}

/**
 * Меняет местами два элемента ассоциативного массива
 *
 * @param array $array Массив, элементы которого меняются местами.
 * @param string $key1 Ключ первого элемента.
 * @param string $key2 Ключ второго элемента.
 *
 * @return array Массив, в котором элементы поменялись местами.
 */
function sb_array_swap(&$array, $key1, $key2)
{
    $v1 = $array[$key1];
    $v2 = $array[$key2];
    $out = array();

    foreach($array as $i => $v)
    {
        if ($i === $key1)
        {
            $i = $key2;
            $v = $v2;
        }
        elseif ($i === $key2)
        {
            $i = $key1;
            $v = $v1;
        }

        $out[$i] = $v;
    }

    return $out;
}

/**
 * Производит замену строки или массива строк $search на строку или массив строк $replace в многомерных массивах
 *
 * @param mixed $search Строка или массив строк, которые следует заменить.
 * @param mixed $replace Строка или массив строк, на которые будут производиться замены.
 * @param array $ar Массив, в котором будут производиться замены.
 *
 * @return array Массив, в котором все вхождения $search заменены на $replace.
 */
function sb_array_replace($search, $replace, $ar)
{
    $out_ar = array();
    if (is_array($ar))
    {
    	foreach($ar as $key => $tmp)
        {
        	if (!is_numeric($key))
        	{
        		$key = sb_str_replace($search, $replace, $key);
        	}

            $out_ar[$key] = sb_array_replace($search, $replace, $tmp);
        }
    }
    else
    {
        $out_ar = sb_str_replace($search, $replace, $ar);
    }

    return $out_ar;
}

/**
 * Используется для сравнения элементов массива, вызывается функцией uasort
 *
 * Ключ массивов, по которому производится сравнение, задается в глобальной переменной $GLOBALS['sb_cmp_sort_field'].
 *
 * @param array $ar1 Элемент первого массива.
 * @param array $ar2 Элемент второго массива.
 *
 * @return int -1, если $ar1 < $ar2; 1, если $ar1 > $ar2; 0, если $ar1 = $ar2
 */
function sb_cmp_array($ar1, $ar2)
{
    if ($ar1[$GLOBALS['sb_cmp_sort_field']] < $ar2[$GLOBALS['sb_cmp_sort_field']])
        return -1;
    if ($ar1[$GLOBALS['sb_cmp_sort_field']] > $ar2[$GLOBALS['sb_cmp_sort_field']])
        return 1;

    return 0;
}

/**
 * Добавляет стандартные описания прав для событий работы с разделами и элементами
 *
 */
function sb_add_rights()
{
    $_SESSION['sbPlugins']->addEventsRights('categs_edit', RIGHTS_H_CATEGS_EDIT_RIGHT);
    $_SESSION['sbPlugins']->addEventsRights('categs_delete', RIGHTS_H_CATEGS_DELETE_RIGHT);
    $_SESSION['sbPlugins']->addEventsRights('categs_rights', RIGHTS_H_CATEGS_RIGHTS_RIGHT);
    $_SESSION['sbPlugins']->addEventsRights('elems_edit', RIGHTS_H_ELEMS_EDIT_RIGHT);
    $_SESSION['sbPlugins']->addEventsRights('elems_delete', RIGHTS_H_ELEMS_DELETE_RIGHT);
}

/**
 * Разбивает длинные слова в строке, добавляя разделитель $char.
 *
 * @param string $str Строка, в которой ищем длинные слова.
 * @param int $length Длина слова, после которого вставляется разделитель.
 * @param string $char Разделитель.
 *
 * @return string Преобразованная строка.
 */
function sb_break_long_words($str, $length, $char)
{
	$end_chars = array(" ", "\n", "\r", "\0");
	$count = 0;
	$new_str = '';
	$open_tag = false;

	for($i = 0; $i < sb_strlen($str); $i++)
	{
		$cur_char = sb_substr($str, $i, 1);
		$new_str .= $cur_char;

		if($cur_char == '<')
		{
			$open_tag = true;
			continue;
		}

		if($open_tag && $cur_char == '>')
		{
			$open_tag = false;
			$count = 0;
			continue;
		}

		if(!$open_tag)
		{
			if(!in_array($cur_char, $end_chars))
			{
				$count++;
				if($count == $length)
				{
					$new_str .= $char;
					$count = 0;
				}
			}
			else
			{
				$count = 0;
			}
		}
	}

	return $new_str;
}

/**
 * Возвращает контрольную сумму строки
 *
 * @param string $str Строка, для которой считаем контрольную сумму.
 *
 * @return int Контрольная сумма строки.
 */
function sb_crc($str)
{
	$crc = abs(crc32($str));
  	if( $crc & 0x80000000)
  	{
        $crc ^= 0xffffffff;
        $crc += 1;
    }

    return $crc;
}

/**
 * Возвращает последнее число месяца
 *
 * @param int $month Месяц.
 * @param int $year Год.
 * @return int Последнее число месяца.
 */
function sb_get_last_day($month, $year)
{
    for ($day = 28; $day <= 32; $day++)
    {
        $date = getdate(mktime(0, 0, 0, $month, $day, $year));
        if ($date['mon'] != $month)
            break;
    }

    $day--;
    return $day;
}

/**
 * Функция изменения пароля пользователя система.
 *
 */
function sb_need_change_password()
{
	$limit = intval(sbPlugins::getSetting('sb_pass_date_limit'));
	$first_change = intval(sbPlugins::getSetting('sb_admin_first_change_pass')) && $_SESSION['sbAuth']->isFirstLogin();

	if($limit == 0 && $first_change == 0)
		return false;

	//  получаем лимит в секундах
	$limit = $limit * 86400;

	$id = $_SESSION['sbAuth']->getUserId();
	$u_pass_date = $_SESSION['sbAuth']->getPassDate();
	$u_login = $_SESSION['sbAuth']->getUserLogin();

	if ($u_pass_date + $limit > time())
	{
		if (!$first_change)
			return false;
	}

	$confirm_new_pass = isset($_POST['changing_confirm_new_pass']) && $_POST['changing_confirm_new_pass'] != '' ? $_POST['changing_confirm_new_pass'] : '';
	$new_pass = isset($_POST['changing_new_pass']) && $_POST['changing_new_pass'] != '' ? $_POST['changing_new_pass'] : '';
	$old_pass = isset($_POST['changing_old_pass']) && $_POST['changing_old_pass'] != '' ? $_POST['changing_old_pass'] : '';

	if($confirm_new_pass != '' && $new_pass != ''&& $old_pass != '')
	{
		$error = false;
		$res = sql_query('SELECT u_pass FROM sb_users WHERE u_id = ?d AND u_pass=?', $id, md5($old_pass));
		if(!$res)
		{
			sb_show_message(KERNEL_CHANGING_PASSWORD_ERROR_OLD_PASS, false, 'warning');
			$error = true;
		}

		if($old_pass == $new_pass)
		{
			sb_show_message(KERNEL_CHANGING_PASSWORD_ERROR_OLD_NEW, false, 'warning');
			$error = true;
		}

		if($confirm_new_pass != $new_pass)
		{
			sb_show_message(KERNEL_CHANGING_PASSWORD_ERROR_CONFIRM_PASS, false, 'warning');
			$error = true;
		}

		if($new_pass != '' && sb_strlen($new_pass) < intval(sbPlugins::getSetting('sb_pass_length_limit')))
		{
			sb_show_message(sprintf(KERNEL_CHANGING_PASSWORD_ERROR_LENGTH_PASS, intval(sbPlugins::getSetting('sb_pass_length_limit'))), false, 'warning');
			$error = true;
		}

		if(!$error)
		{
			$values = array('u_pass' => md5($new_pass), 'u_pass_date' => time());
			if ($_SESSION['sbAuth']->isFirstLogin())
			{
				$values['u_last_date'] = time();
				$_SESSION['sbAuth']->setFirstLogin(false);
			}

			// При смене пароля, меняем соап_токен
			$res = sql_query('SELECT u_soap_token, u_login FROM sb_users WHERE u_id = ?d', $id);
			if($res && $res != '')
			{
				$soap_token = explode('|',$res[0][0]);
				if(count($soap_token) == 3)
				{
					require_once (SB_CMS_LIB_PATH.'/sbSoap.inc.php');
					$soap = new sbSoap();
					$new_st = $soap -> getSoapTokenHash($res[0][1], md5($new_pass), $id, $soap_token[0], $soap_token[1]);
					$values['u_soap_token'] = $soap_token[0].'|'.$soap_token[1].'|'.$new_st;
				}
			}
			sql_query('UPDATE sb_users SET ?a WHERE u_id=?d', $values, $id, sprintf(KERNEL_CHANGING_PASSWORD_SYS_ERROR_CHANGED_PASS, $u_login));

			header('Location: '.sb_sanitize_header($_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '')));
			exit(0);
		}
	}

	require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');
	$result = '<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbIndex.css">
			<script>
				function sbMakePass()
				{
				    var form = sbGetE("sb_form_changing");
				    var old_pass = sbGetE("changing_old_pass");
				    var new_pass = sbGetE("changing_new_pass");
				    var confirm_pass = sbGetE("changing_confirm_new_pass");

					if (!form || old_pass.value == "" || new_pass.value == "" || confirm_pass.value == "" || old_pass.value == new_pass.value || new_pass.value != confirm_pass.value)
					{
						alert("'.KERNEL_CHANGING_PASSWORD_ERROR_SEND_FORM.'");
						return false;
				    }
				}
			</script>
			<div align="center"><br /><br />';

	$result .= sb_show_message(($_SESSION['sbAuth']->isFirstLogin() ? KERNEL_CHANGING_PASSWORD_FIRST_LOGIN_INFO_MSG : KERNEL_CHANGING_PASSWORD_INFO_MSG), true, 'information', true);
	$result .= '<br /><br /><br />';

	$layout = new sbLayout($_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : ''), 'content', 'post', 'sbMakePass()', 'sb_form_changing');

	$layout->mTableWidth = '470';
	$layout->mTitleWidth = '115';

	$layout->addHeader(KERNEL_CHANGING_PASSWORD_NEED);

	$layout->addField('', new sbLayoutInput('hidden', $u_login, 'changing_login'));
	$layout->addField(KERNEL_CHANGING_PASSWORD_OLD_PASS_FIELD, new sbLayoutInput('password', '', 'changing_old_pass', '', 'style="width:320px;" tabIndex="1"', true), 'style="padding-top:10px;"', 'style="padding:10px 0px 5px 0px"');

	$fld = new sbLayoutInput('password', '', 'changing_new_pass', '', 'style="width:320px;" autocomplete="off" tabIndex="2" onkeyup="sb_pass_strength(\'changing_new_pass\', \'changing_login\', \'pass_meter\', '.intval(sbPlugins::getSetting('sb_pass_length_limit')).');"', true);
	$fld->mHTML = '<div id="pass_meter">'.KERNEL_CHANGING_PASSWORD_PASS_METER.'</div>';
	$layout->addField(KERNEL_CHANGING_PASSWORD_NEW_PASS_FIELD, $fld, 'style="padding-top:10px;"', 'style="padding:10px 0px 8px 0px;"');

	$layout->addField(KERNEL_CHANGING_PASSWORD_NEW_PASS_CONFIRM_FIELD, new sbLayoutInput('password', '', 'changing_confirm_new_pass', '', 'style="width:320px;" tabIndex="3" autocomplete="off"', true),  'style="padding-top:10px;"', 'style="padding:10px 0px 8px 0px;"');
	$layout->addButton('submit', KERNEL_SAVE, '', '', 'tabIndex="4"');

	$result .= $layout->show(false);
	$result .= '</div>';

	return $result;
}

/**
 * Функция чистит лишние теги, вставляемые при публикации текста через Word.
 *
 * @param string $html HTML-код, переданный из Word.
 *
 * @return string HTML-код, очищенный от лишних тегов.
 */
function sb_clean_word($html)
{
	$html = preg_replace('/<o:p>\s*<\/o:p>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<o:p>[\s\S]*?<\/o:p>/im'.SB_PREG_MOD, '&nbsp;', $html);
	$html = preg_replace('/\s*mso\-[^:]+:[^;"]+;?/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*MARGIN: 0cm 0cm 0pt\s*;/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*MARGIN: 0cm 0cm 0pt\s*"/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*TEXT\-INDENT: 0cm\s*;/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*TEXT\-INDENT: 0cm\s*"/im'.SB_PREG_MOD, '"', $html);
	$html = preg_replace('/\s*TEXT\-ALIGN: [^\s;]+;?"/im'.SB_PREG_MOD, '"', $html);
	$html = preg_replace('/\s*PAGE\-BREAK\-BEFORE: [^\s;]+;?"/im'.SB_PREG_MOD, '"', $html);
	$html = preg_replace('/\s*FONT\-VARIANT: [^\s;]+;?"/im'.SB_PREG_MOD, '"', $html);
	$html = preg_replace('/\s*tab\-stops:[^;"]*;?/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*tab\-stops:[^"]*/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*face="[^"]*"/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*face=[^ >]*/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*FONT\-FAMILY:[^;"]*;?/im'.SB_PREG_MOD, '', $html);
	//$html = preg_replace('/<(\w[^>]*) class=([^ |>]*)([^>]*)/im'.SB_PREG_MOD, '<$1$3', $html);
	//$html = preg_replace('/<(\w[^>]*) style="([^\"]*)"([^>]*)/im'.SB_PREG_MOD, '<$1$3', $html);
	$html = preg_replace('/<STYLE[^>]*>[\s\S]*?<\/STYLE[^>]*>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<(?:META|LINK)[^>]*>\s*/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/\s*style="\s*"/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<SPAN\s*[^>]*>\s*&nbsp;\s*<\/SPAN>/im'.SB_PREG_MOD, '&nbsp;', $html);
	$html = preg_replace('/<SPAN\s*[^>]*><\/SPAN>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<(\w[^>]*) lang=([^ |>]*)([^>]*)/im'.SB_PREG_MOD, '<$1$3', $html);
	$html = preg_replace('/<SPAN>([\s\S]*?)<\/SPAN>/im'.SB_PREG_MOD, '$1', $html);
	$html = preg_replace('/<FONT\s*>([\s\S]*?)<\/FONT>/im'.SB_PREG_MOD, '$1', $html);
	$html = preg_replace('/<\\?\?xml[^>]*>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<w:[^>]*>[\s\S]*?<\/w:[^>]*>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<\/?\w+:[^>]*>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<\!\-\-[\s\S]*?\-\->/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<(U|I|STRIKE)>&nbsp;<\/\1>/im'.SB_PREG_MOD, '&nbsp;', $html);
	$html = preg_replace('/<H\d>\s*<\/H\d>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<(\w+)[^>]*\sstyle="[^"]*DISPLAY\s?:\s?none[\s\S]*?<\/\1>/im'.SB_PREG_MOD, '', $html);
	$html = preg_replace('/<(\w[^>]*) language=([^ |>]*)([^>]*)/im'.SB_PREG_MOD, '<$1$3', $html);
	$html = preg_replace('/<(\w[^>]*) onmouseover="([^\"]*)"([^>]*)/im'.SB_PREG_MOD, '<$1$3', $html);
	$html = preg_replace('/<(\w[^>]*) onmouseout="([^\"]*)"([^>]*)/im'.SB_PREG_MOD, '<$1$3', $html);
	$html = preg_replace('/<([^>]*)>\s*?<\/\1>/im'.SB_PREG_MOD, '', $html);

	return $html ;
}

/**
 * Вывод списка со статусом публикации
 *
 * @param object $layout Объект типа sbLayout.
 * @param string $plugin_ident Идентификатор модуля.
 * @param mixed $el_ident Идентификатор элемента. Строка либо массив.
 * @param string $active_name Название поля, содержащего статус публикации.
 * @param mixed $active_value Текущий статус публикации. Строка либо массив.
 * @param string $pub_start_name Название поля, содержащего дату начала публикации.
 * @param string $pub_start_value Значение поля, содержащего дату начала публикации.
 * @param string $pub_end_name Название поля, содержащего дату окончания публикации.
 * @param string $pub_end_value Значение поля, содержащего дату окончания публикации.
 *
 * @return bool TRUE, если редактирование элемента доступно, FALSE в ином случае.
 */
function sb_add_workflow_status(&$layout, $plugin_ident, $el_ident, $active_name, $active_value = 0, $pub_start_name = '', $pub_start_value = '', $pub_end_name = '', $pub_end_value = '')
{
	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow($plugin_ident))
	{
		if (is_array($el_ident))
		{
			if (count($el_ident) == 0)
				$active_value = array(0);
			elseif(!is_array($active_value))
				$active_value = array($active_value);

			$layout->addField('', new sbLayoutInput('hidden', implode(',', $active_value), 'old_workflow'));
		}
		elseif($el_ident == '')
		{
			$active_value = -1;
			$layout->addField('', new sbLayoutInput('hidden', $active_value, 'old_workflow'));
		}
		else
		{
		    $layout->addField('', new sbLayoutInput('hidden', $active_value, 'old_workflow'));
		}

		// используем модуль Цепочки публикаций
		$res = sql_query('SELECT w_title, w_code FROM sb_workflow WHERE w_on=1 ORDER BY w_order');
		$workflow_rights = array();

		if ($res)
		{
			foreach ($res as $value)
			{
				$workflow_rights[$value[1]] = $value[0];
			}
		}

		$all_rights = $workflow_rights;
		$avail_rights = $workflow_rights;

		if (!$_SESSION['sbAuth']->isAdmin())
		{
			if (count($workflow_rights) > 0 && is_array($el_ident) && count($el_ident) > 0)
			{
				$active_value = array_unique($active_value);

				$res = sql_query('SELECT w_accessible FROM sb_workflow WHERE w_code IN (?a)', $active_value);
				if ($res)
				{
					$user_rights = array();
					foreach($res as $val)
					{
						$user_rights = explode('|', $val[0]);
						foreach($workflow_rights as $ke => $v)
						{
							if (!in_array($ke, $user_rights) && !in_array($ke, $active_value))
							{
								unset($workflow_rights[$ke]);
							}
						}
					}
				}
			}
			elseif (count($workflow_rights) > 0 && $el_ident > 0)
			{
				$res = sql_query('SELECT w_accessible FROM sb_workflow WHERE w_code=?d', $active_value);
				if ($res)
				{
					if (trim($res[0][0]) != '')
					{
						$user_rights = explode('|', $res[0][0]);

						foreach ($workflow_rights as $key => $value)
						{
							if (!in_array($key, $user_rights) && $key != $active_value)
							{
								unset($workflow_rights[$key]);
							}
						}
					}
					else
					{
						$workflow_rights = array();
					}
				}
			}

			$avail_rights = $workflow_rights;
			if (count($avail_rights) > 0)
			{
				$res = sql_query('SELECT ur_workflow_rights FROM sb_users_rights WHERE ur_plugin=? AND ur_cat_id IN (?a) AND ur_workflow_rights <> ?', $plugin_ident, $_SESSION['sbAuth']->getUserGroups(), '');
				if (!$res)
				{
					$avail_rights = array();
				}
				else
				{
					if (trim($res[0][0]) != '')
					{
						$user_rights = explode('|', $res[0][0]);
						foreach ($avail_rights as $key => $value)
						{
							if (!in_array($key, $user_rights))
							{
								unset($avail_rights[$key]);
							}
						}
					}
					else
					{
						$avail_rights = array();
					}
				}
			}

			if (is_array($el_ident) && count($el_ident) == 0 || $el_ident == '')
				$workflow_rights = $avail_rights;
		}

		if (count($workflow_rights) > 0)
		{
			echo '<script>
			var sbWorkflowOldStatus = "'.(is_array($el_ident) ? implode(',', $active_value) : $active_value).'";
			function sbWorkflowChangeStatus(el)
			{
				var dsp = (el.value != sbWorkflowOldStatus ? "" : "none");

				sbGetE("'.$active_name.'_msg_del_tr").style.display = dsp;
				sbGetE("'.$active_name.'_msg_tr").style.display = dsp;
				sbGetE("'.$active_name.'_users_del_tr").style.display = dsp;

				if (dsp != "none")
				{
					sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_users_get_groups&plugin_ident='.$plugin_ident.'&workflow_status=" + el.value, sbWorkflowAfterLoadUsers);
				}

				sbGetE("'.$active_name.'_users_td").innerHTML = "<div style=\'width:200px; text-align:center;\'><img src=\''.SB_CMS_IMG_URL.'/loading.gif\' /></div>";
				sbGetE("'.$active_name.'_users_tr").style.display = dsp;
			}

			function sbWorkflowAfterLoadUsers(res)
			{
				if (res != "")
				{
					sbGetE("'.$active_name.'_users_td").innerHTML = res;
				}
				else
				{
					sbGetE("'.$active_name.'_users_td").innerHTML = "<div class=\'hint_div\'>'.KERNEL_WORKFLOW_USERS_ERROR.'</div>";
				}
			}
			</script>';
			$layout->addField('', new sbLayoutDelim(), 'id="'.$active_name.'_del_th"', 'id="'.$active_name.'_del_td"', 'id="'.$active_name.'_del_tr"');

			$tmp_ids = array();
			if(isset($_GET['ids']))
			{
				if(is_array($_GET['ids']))
				{
					$tmp_ids = $_GET['ids'];
				}
				else
				{
					$tmp_ids = explode(',', $_GET['ids']);
				}
			}

			if ((empty($tmp_ids) || count($tmp_ids) <= 1) && $el_ident != '')
			{
				$fld = new sbLayoutLabel(isset($all_rights[$active_value]) ? $all_rights[$active_value] : KERNEL_STATUS_UNKNOWN, '', 'style="color: '.($active_value == 1 ? 'green' : ($active_value == 2 ? 'blue' : 'red')).';"');
				$fld->mHint = true;
				$layout->addField(KERNEL_ACTIVE_STATUS, $fld, 'id="'.$active_name.'_label_th"', 'id="'.$active_name.'_label_td"', 'id="'.$active_name.'_label_tr"');
			}

//			если производится групповое редактирование элементов.
			$return_right = (is_array($active_value) && count(array_intersect($active_value, array_keys($avail_rights))) > 0);
			if(is_array($el_ident) && !$return_right)
			{
				$fld = new sbLayoutLabel(KERNEL_STATUS_NO_RIGHTS, '', 'style="color: red"');
				$fld->mHint = true;
				$layout->addField(KERNEL_ACTIVE_STATUS, $fld, 'id="'.$active_name.'_label_th"', 'id="'.$active_name.'_label_td"', 'id="'.$active_name.'_label_tr"');
			}

			if (is_array($el_ident) && (count($el_ident) == 0 || $return_right))
			{
				$fld = new sbLayoutSelect($workflow_rights, $active_name, '', 'onchange="sbWorkflowChangeStatus(this);" onkeyup="sbWorkflowChangeStatus(this);"');
				$layout->addField((is_array($el_ident) && count($el_ident) > 0 ? KERNEL_NEW_STATUS : KERNEL_STATUS).sbGetGroupEditCheckbox($active_name, is_array($el_ident)), $fld, 'id="'.$active_name.'_th"', 'id="'.$active_name.'_td"', 'id="'.$active_name.'_tr"');
			}
			elseif($el_ident == '' || (!is_array($active_value) && (count($avail_rights) > 0 && isset($avail_rights[$active_value]) || !isset($all_rights[$active_value]))))
			{
				$fld = new sbLayoutSelect($workflow_rights, $active_name, '', 'onchange="sbWorkflowChangeStatus(this);" onkeyup="sbWorkflowChangeStatus(this);"');
       			$fld->mSelOptions = array($active_value);
				$layout->addField($el_ident != '' ? KERNEL_NEW_STATUS : KERNEL_STATUS, $fld, 'id="'.$active_name.'_th"', 'id="'.$active_name.'_td"', 'id="'.$active_name.'_tr"');
			}
			else
			{
				if(is_array($el_ident))
				{
					$layout->addField('', new sbLayoutInput('hidden', implode(',', $active_value), $active_name));
				}
				else
				{
					$layout->addField('', new sbLayoutInput('hidden', $active_value, $active_name));
				}
			}

			if ($pub_start_name != '' && $pub_end_name != '')
			{
				$fld2 = new sbLayoutInput('checkbox', '1', 'ch_pub_start_end', '', (isset($_REQUEST['ch_pub_start_end']) && $_REQUEST['ch_pub_start_end'] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');

				$fld_start = new sbLayoutDate($pub_start_value, $pub_start_name);
				$fld_start->mDropButton = true;

				$fld_end = new sbLayoutDate($pub_end_value, $pub_end_name);
				$fld_end->mDropButton = true;

				$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.KERNEL_DATE_FROM.'&nbsp;&nbsp;</td><td>'.($layout->fieldTypeExists('sbLayoutDate') ? '' : $fld_start->getJavaScript()).$fld_start->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_DATE_TO.'&nbsp;&nbsp;</td><td>'.$fld_end->getField().'</td></tr></table>';
				$fld = new sbLayoutHTML($html);
				$fld->mShowColon = false;

				$layout->addField(KERNEL_ACTIVE_PERIOD.(is_array($el_ident) && count($el_ident) > 0 ? sbGetGroupEditCheckbox('pub_start_end', is_array($el_ident)) : '')
					, $fld,	'id="'.$active_name.'_date_th"', 'id="'.$active_name.'_date_td"', 'id="'.$active_name.'_date_tr"');
			}

			$layout->addField('', new sbLayoutDelim(), 'id="'.$active_name.'_msg_del_th"', 'id="'.$active_name.'_msg_del_td"', 'id="'.$active_name.'_msg_del_tr" style="display:none;"');

			$fld = new sbLayoutTextarea('', 'workflow_message', 'workflow_message', 'style="width:100%;height:100px;"');
			$fld->mShowToolbar = false;
			$layout->addField(KERNEL_WORKFLOW_MESSAGE, $fld, 'id="'.$active_name.'_msg_th"', 'id="'.$active_name.'_msg_td"', 'id="'.$active_name.'_msg_tr" style="display:none;"');

			$layout->addField('', new sbLayoutDelim(), 'id="'.$active_name.'_users_del_th"', 'id="'.$active_name.'_users_del_td"', 'id="'.$active_name.'_users_del_tr" style="display:none;"');

			include_once(SB_CMS_PL_PATH.'/pl_site_users/pl_site_users.inc.php');

			$fld = new sbLayoutHTML('');
			$fld->mShowColon = false;

			$layout->addField(KERNEL_WORKFLOW_USERS, $fld, 'id="'.$active_name.'_users_th"', 'id="'.$active_name.'_users_td"', 'id="'.$active_name.'_users_tr" style="display:none;"');

			if (is_array($el_ident) && count($el_ident) == 0 || $el_ident == '')
			{
				$layout->addField('', new sbLayoutHTML('<script>sbWorkflowChangeStatus(sbGetE("'.$active_name.'"));</script>'));
			}

			if (is_array($el_ident) && count($el_ident) == 0 || $el_ident == '' || (count($avail_rights) > 0 && !is_array($active_value) && (isset($avail_rights[$active_value]) || !isset($all_rights[$active_value]))) || $return_right)
				return true;
		}
		else
		{
			$layout->addField(KERNEL_STATUS, new sbLayoutLabel('<div class="hint_div">'.KERNEL_WORKFLOW_NO_STATUS_AVAILABEL.'</div>', '', '', false));
			$layout->addField('', new sbLayoutInput('hidden', 0, $active_name));

			if ($pub_start_name != '' && $pub_end_name != '')
			{
				$layout->addField('', new sbLayoutInput('hidden', $pub_start_value, $pub_start_name));
				$layout->addField('', new sbLayoutInput('hidden', $pub_end_value, $pub_end_name));
			}
		}
	}
	else
	{
		if($_SESSION['sbPlugins']->isRightAvailable($plugin_ident, 'elems_public'))
		{
			$layout->addField('', new sbLayoutDelim(), 'id="'.$active_name.'_del_th"', 'id="'.$active_name.'_del_td"', 'id="'.$active_name.'_del_tr"');
			$layout->addField((is_array($el_ident) && count($el_ident) > 0 ? KERNEL_NEW_STATUS : KERNEL_STATUS).sbGetGroupEditCheckbox($active_name, is_array($el_ident))
				, new sbLayoutInput('checkbox', '1', $active_name, '', ($active_value == 1 ? ' checked="checked"' : '')), 'id="'.$active_name.'_th"', 'id="'.$active_name.'_td"', 'id="'.$active_name.'_tr"');

			if ($pub_start_name != '' && $pub_end_name != '')
			{
				$fld_start = new sbLayoutDate($pub_start_value, $pub_start_name);
				$fld_start->mDropButton = true;

				$fld_end = new sbLayoutDate($pub_end_value, $pub_end_name);
				$fld_end->mDropButton = true;

				$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.KERNEL_DATE_FROM.'&nbsp;&nbsp;</td><td>'.($layout->fieldTypeExists('sbLayoutDate') ? '' : $fld_start->getJavaScript()).$fld_start->getField().'</td><td>&nbsp;&nbsp;'.KERNEL_DATE_TO.'&nbsp;&nbsp;</td><td>'.$fld_end->getField().'</td></tr></table>';

				$fld = new sbLayoutHTML($html);
				$fld->mShowColon = false;

				$layout->addField(KERNEL_ACTIVE_PERIOD.sbGetGroupEditCheckbox('pub_start_end', is_array($el_ident))
					, $fld, 'id="'.$active_name.'_date_th"', 'id="'.$active_name.'_date_td"', 'id="'.$active_name.'_date_tr"');
			}
		}
		else
		{
			$layout->addField('', new sbLayoutInput('hidden', 0, $active_name));
			if ($pub_start_name != '' && $pub_end_name != '')
			{
				$layout->addField('', new sbLayoutInput('hidden', $pub_start_value, $pub_start_name));
				$layout->addField('', new sbLayoutInput('hidden', $pub_end_value, $pub_end_name));
			}
		}
		return true;
	}
	return false;
}

/**
 * Добавление статуса публикации в фильтр элементов
 *
 * @param object $layout Объект типа sbElements.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $active_name Название поля, содержащего статус публикации.
 * @param bool $show_chk Показывать флажок при отключенном модуле Цепочки публикаций.
 */
function sb_add_workflow_filter(&$elems, $plugin_ident, $active_name, $show_chk = true)
{
	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow($plugin_ident))
    {
    	// используем модуль Цепочки публикаций
		$res = sql_query('SELECT w_title, w_code FROM sb_workflow WHERE w_on=1 ORDER BY w_order');
		$workflow_rights = array();

		if ($res)
		{
			foreach ($res as $value)
			{
				$workflow_rights[$value[1]] = $value[0];
			}
		}

		$elems->addFilter(KERNEL_STATUS, $active_name, 'select', $workflow_rights);
    }
    else
    {
    	if ($show_chk)
    		$elems->addFilter(KERNEL_ACTIVE, $active_name, 'checkbox');
    }
}

/**
 * Вывод статуса публикации в списке элементов
 *
 * @param string $result Строка описания элемента.
 * @param string $plugin_ident Идентификатор модуля.
 * @param string $active_value Текущий статус публикации.
 * @param string $pub_start_value Значение поля, содержащего дату начала публикации.
 * @param string $pub_end_value Значение поля, содержащего дату окончания публикации.
 * @param bool $show_chk Показывать статус публикации при отключенном модуле Цепочки публикаций.
 */
function sb_get_workflow_status(&$result, $plugin_ident, $active_value = 0, $pub_start_value = 0, $pub_end_value = 0, $show_chk = true)
{
	static $workflow_rights = null;

	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow($plugin_ident))
	{
		// используем модуль Цепочки публикаций
		if (is_null($workflow_rights))
		{
			$res = sql_query('SELECT w_title, w_code FROM sb_workflow WHERE w_on=1 ORDER BY w_order');
			$workflow_rights = array();

			if ($res)
			{
				foreach ($res as $value)
				{
					$workflow_rights[$value[1]] = $value[0];
				}
			}
		}

		$result .= '<br />'.KERNEL_STATUS.': ';
		foreach ($workflow_rights as $right_ident => $right_title)
		{
			if ($right_ident == $active_value)
			{
				$result .= '<span style="color: '.($right_ident == 1 ? 'green' : ($right_ident == '2' ? 'blue' : 'red')).';">'.$right_title.'</span>';
				break;
			}
		}
	}
	elseif ($show_chk)
	{
		$result .= '<br />'.KERNEL_ACTIVE.': ';

		if ($active_value != 1)
	    {
	        $result .= '<span style="color: red;">'.KERNEL_NO.'</span>';
	    }
	    else
	    {
	    	$result .= '<span style="color: green;">'.KERNEL_YES.'</span>';
	    }
	}

    if ($show_chk && ($pub_start_value != 0 || $pub_end_value != 0))
    {
        $result .= '<br />'.KERNEL_ACTIVE_PERIOD.': <span style="color: blue;">'.
                   ($pub_start_value != 0 ? KERNEL_DATE_FROM.' '.sb_date('d.m.Y H:i', $pub_start_value).' ' : '').
                   ($pub_end_value != 0 ? KERNEL_DATE_TO.' '.sb_date('d.m.Y H:i', $pub_end_value) : '').
                   '</span>';
    }
}

/**
 * Возвращает массив с идентификаторами доступных данному пользователю статусами.
 *
 * @param string $plugin_ident Идентификатор модуля.
 *
 * @return int Массив с идентификаторами доступных данному пользователю статусами.
 */
function sb_get_avail_workflow_status($plugin_ident)
{
	$res = sql_query('SELECT w_code FROM sb_workflow WHERE w_on=1 ORDER BY w_order');

	if (!$res)
		return array(0);

	$workflow_rights = array();
	foreach ($res as $value)
	{
		$workflow_rights[] = $value[0];
	}

	if ($_SESSION['sbAuth']->isAdmin())
	{
		return $workflow_rights;
	}

	$res = sql_query('SELECT ur_workflow_rights FROM sb_users_rights WHERE ur_plugin=? AND ur_cat_id IN (?a) AND ur_workflow_rights <> ?', $plugin_ident, $_SESSION['sbAuth']->getUserGroups(), '');
	if (!$res || trim($res[0][0]) == '')
		return array(0);

	$user_rights = explode('|', $res[0][0]);
	foreach ($workflow_rights as $key => $value)
	{
		if (!in_array($value, $user_rights))
		{
			unset($workflow_rights[$key]);
		}
	}

	if (count($workflow_rights) > 0)
		return $workflow_rights;
	else
		return array(0);
}

/**
 * Сохранение статуса публикации
 *
 * @param array $row Массив для вставки в таблицу БД.
 * @param string $active_name Название поля, содержащего статус публикации.
 * @param string $pub_start_name Название поля, содержащего дату начала публикации.
 * @param string $pub_end_name Название поля, содержащего дату окончания публикации.
 */
function sb_submit_workflow_status(&$row, $active_name, $pub_start_name = '', $pub_end_name = '', $edit_group = false)
{
	if($edit_group && isset($_POST['ch_'.$active_name]) && $_POST['ch_'.$active_name] == 1 || !$edit_group)
	{
		$row[$active_name] = isset($_POST[$active_name]) ? intval($_POST[$active_name]) : 0;
	}

	if ($pub_start_name != '' && $pub_end_name != '')
	{
		if($edit_group && isset($_POST['ch_pub_start_end']) && $_POST['ch_pub_start_end'] == 1 || !$edit_group)
		{
			if (isset($_POST[$pub_start_name]) && trim($_POST[$pub_start_name]) != '')
			{
				$row[$pub_start_name] = sb_datetoint($_POST[$pub_start_name]);
		    }
		    else
		    {
				$row[$pub_start_name] = null;
		    }
		}

		if($edit_group && isset($_POST['ch_pub_start_end']) && $_POST['ch_pub_start_end'] == 1 || !$edit_group)
		{
			if (isset($_POST[$pub_end_name]) && trim($_POST[$pub_end_name]) != '')
		    {
				$row[$pub_end_name] = sb_datetoint($_POST[$pub_end_name]);
		    }
			else
		    {
				$row[$pub_end_name] = null;
			}
		}
	}
}

/**
 * Отправка на почту и в личные сообщения, уведомлений об изменении статуса элемента.
 *
 * @param int $plugin_ident Идентификатор модуля элемент которого был изменен.
 * @param int $el_id Идентификатор(ы) элемента(ов) у котрого(ых) был изменен статус.
 * @param int $el_name Название элемента.
 * @param int $new_workflow Идентификатор нового статуса элемента.
 */
function sb_mail_workflow_status($plugin_ident, $el_id, $el_name, $new_workflow)
{
//	Если есть модуль и если указаны пользователи кому отправлять уведомление.
	if ($_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow($plugin_ident)
		&& isset($_POST['users']) && is_array($_POST['users']) && count($_POST['users']) > 0)
	{
		$old_workflow = (isset($_POST['old_workflow']) ? intval($_POST['old_workflow']) : -1);
		$res = sql_query('SELECT DISTINCT(u.u_email) FROM sb_users u, sb_categs c, sb_catlinks l
					WHERE l.link_el_id = u.u_id
					AND l.link_cat_id = c.cat_id
					AND c.cat_ident = ?
					AND u.u_block = 0
					AND u.u_id IN (?a)', 'pl_users', $_POST['users']);

		$emails = array();
		if($res)
		{
			foreach($res as $value)
			{
				$emails[] = $value[0];
			}
		}

//		Определяем старый и новый статусы.
		$new_status = $old_status = '';
		$status_res = sql_query('SELECT w_code, w_title FROM sb_workflow WHERE w_code IN (?a)', array($new_workflow, $old_workflow));
		if($status_res)
		{
			foreach($status_res as $value)
			{
				if($new_workflow == $value[0])
				{
					$new_status = isset($value[1]) ? $value[1] : '';
				}
				elseif($old_workflow == $value[0])
				{
					$old_status = isset($value[1]) ? $value[1] : '';
				}
			}
		}

		if ($old_status == '')
			$old_status = KERNEL_WORKFLOW_NO_STATUS;

		if ($new_status == '')
			$new_status = KERNEL_WORKFLOW_NO_STATUS;

		$fio = (isset($_SESSION['sbAuth']->mName) && $_SESSION['sbAuth']->mName != '' ? ' ('.$_SESSION['sbAuth']->mName.')' : '');

		$id = isset($_SESSION['sbPlugins']->mFieldsInfo[$plugin_ident]['id']) ? $_SESSION['sbPlugins']->mFieldsInfo[$plugin_ident]['id'] : '';
		$title = isset($_SESSION['sbPlugins']->mFieldsInfo[$plugin_ident]['title']) ? $_SESSION['sbPlugins']->mFieldsInfo[$plugin_ident]['title'] : '';
		$table = isset($_SESSION['sbPlugins']->mFieldsInfo[$plugin_ident]['table']) ? $_SESSION['sbPlugins']->mFieldsInfo[$plugin_ident]['table'] : '';

		$el_titles = array();
		if($id != '' && $title != '' && $table != '')
		{
		    if(is_array($el_id) && count($el_id) > 0)
		    {
			    $res_titles = sql_query('SELECT '.$id.', '.$title.' FROM '.$table.' WHERE '.$id.' IN (?a)', $el_id);
		    }
		    else
		    {
		        $res_titles = sql_query('SELECT '.$id.', '.$title.' FROM '.$table.' WHERE '.$id.' = ?d', $el_id);
		    }

			if($res_titles)
			{
				foreach($res_titles as $value)
				{
					$el_titles[$value[0]] = $value[1];
				}
			}
		}

		$plugins_info = $_SESSION['sbPlugins']->getPluginsInfo();

		$els_str = $els_str_m = '';
		if(!is_array($el_id) && intval($el_id) > 0)
		{
			if(isset($plugins_info[$plugin_ident]['main_event']))
			{
				$els_str = '<a href="'.SB_CMS_CONTENT_FILE.'?event='.$plugins_info[$plugin_ident]['main_event'].'&sb_sel_id='.$el_id.'">'.$el_name.'</a>';
			}
			else
			{
				$els_str = $el_name;
			}
			$els_str_m = $el_name;
			$constant = KERNEL_WORKFLOW_STATUS_MAIL_TEXT;
		}
		elseif(is_array($el_id) && count($el_id) > 0)
		{
			foreach($el_id as $val)
			{
				if(isset($plugins_info[$plugin_ident]['main_event']))
				{
					$els_str .= '<a href="'.SB_CMS_CONTENT_FILE.'?event='.$plugins_info[$plugin_ident]['main_event'].'&sb_sel_id='.$val.'">'.(isset($el_titles[$val]) ? $el_titles[$val] : '').'</a>, ';
				}
				else
				{
					$els_str .= (isset($el_titles[$val]) ? $el_titles[$val] : '').', ';
				}
				$els_str_m .= (isset($el_titles[$val]) ? $el_titles[$val] : '').', ';
			}
			$els_str = sb_substr($els_str, 0, -2);
			$els_str_m = sb_substr($els_str_m, 0, -2);

			$constant = KERNEL_WORKFLOW_STATUS_GROUP_MAIL_TEXT;
		}

		$msg_subj = KERNEL_WORKFLOW_STATUS_MAIL_SUBJ;
		$msg_text = sprintf($constant,
							SB_COOKIE_DOMAIN,
							$_SESSION['sbAuth']->getUserLogin().$fio,
							$_SESSION['sbPlugins']->getPluginTitle($plugin_ident),
							$els_str_m, $old_status, $new_status).
							(isset($_POST['workflow_message']) && trim($_POST['workflow_message']) != '' ? '<br />'.KERNEL_WORKFLOW_COMMENT_STATUS_LABEL.': '.$_POST['workflow_message'] : '');
        $msg_text .= '<br />'.KERNEL_WORKFLOW_ADMINPAGE_LINK;

//		отправляем письмо если есть email-ы
		if (count($emails) > 0)
		{
			require_once SB_CMS_LIB_PATH.'/sbMail.inc.php';
			$mail = new sbMail();

			$type = sbPlugins::getSetting('sb_letters_type');

			$email_subj = $msg_subj;
			$email_text = $msg_text;

			$mail->setSubject($email_subj);
            if ($type == 'html')
            {
            	$mail->setHtml($email_text);
			}
			else
            {
				$mail->setText(strip_tags(preg_replace('=<br.*?/?>=i', '', $email_text)));
			}
			$mail->send($emails);
		}

		if (isset($plugins_info[$plugin_ident]['main_event']))
		{
			$msg_text = sprintf($constant,
							SB_COOKIE_DOMAIN,
							$_SESSION['sbAuth']->getUserLogin().$fio,
							$_SESSION['sbPlugins']->getPluginTitle($plugin_ident),
							$els_str,
							$old_status, $new_status).
							(isset($_POST['workflow_message']) && trim($_POST['workflow_message']) != ''  ? '<br /><br /><div class="hint_div">'.$_POST['workflow_message'].'</div>' : '');
		}

//		Отправляем личные сообщения.
		require_once(SB_CMS_PL_PATH.'/pl_messages/pl_messages.php');

		$_POST['um_title'] = $msg_subj;
		$_POST['um_message'] = $msg_text;
		$_POST['um_copy'] = 0;
		$_POST['um_to_ids'] = implode(',', $_POST['users']);

//		Нас интересует только отправка личных сообщений. Все что выводится функцией нам не нужно.
		ob_start();
		fMessages_Add_Submit(false);
		ob_end_clean();
	}
}

/**
 * Возвращает идентификаторы статусов публикации, через запятую, для которых
 * разрешен вывод на демо-сайте.
 *
 * @param $check_demo Проверять на демо-сайт или возвращать все статусы публикации.
 * @param $return_allow Флаг. Возвращать разрешенные для публикации на демо-сайте статусы (TRUE) или запрещенные (FALSE)
 *
 * @return string Идентификаторы статусов публикации, через запятую, для которых разрешен вывод на демо-сайте
 */
function sb_get_workflow_demo_statuses($check_demo = true, $return_allow = true)
{
	static $result = '';
	if($result != '')
	{
		return $result;
	}

	$demo_statuses = array(($return_allow ? 1 : 0));
	if(SB_DEMO_SITE || !$check_demo)
	{
		$res = sql_query('SELECT w_code FROM sb_workflow WHERE w_show_demo = '.($return_allow ? 1 : 0).' AND w_code != 1 AND w_code != 0 AND w_on = 1');
		if($res)
		{
			foreach($res as $value)
			{
				$demo_statuses[] = $value[0];
			}
		}
    }

	$result = implode(',', $demo_statuses);
	return $result;
}

/**
 * Возвращает TRUE, если статус доступен и FALSE в ином случае.
 *
 * @param int $status Код статуса или all для проверки, есть ли доступные статусы публикации.
 * @param string $plugin_ident Идентификатор модуля.
 * @param int $el_ident Идентификатор элемента.
 * @param int $active_value Текущий статус публикации.
 *
 * @return bool TRUE, если статус доступен и FALSE в ином случае.
 */
function sb_workflow_status_available($status, $plugin_ident, $el_ident, $active_value = 0)
{
	if (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow($plugin_ident))
	{
		return true;
	}

	$res = sql_query('SELECT w_code FROM sb_workflow WHERE w_on=1 ORDER BY w_order');
	$workflow_rights = array();

	if ($res)
	{
		foreach ($res as $value)
		{
			$workflow_rights[$value[0]] = true;
		}
	}

	if (!$_SESSION['sbAuth']->isAdmin())
	{
		if (count($workflow_rights) > 0 && $el_ident > 0)
		{
			$res = sql_query('SELECT w_accessible FROM sb_workflow WHERE w_code=?d', $active_value);
			if ($res)
			{
				if (trim($res[0][0]) != '')
				{
					$user_rights = explode('|', $res[0][0]);
					foreach ($workflow_rights as $key => $value)
					{
						if (!in_array($key, $user_rights) && $key != $active_value)
						{
							unset($workflow_rights[$key]);
						}
					}
				}
				else
				{
					$workflow_rights = array();
				}
			}
		}

		if (count($workflow_rights) > 0)
		{
			$res = sql_query('SELECT ur_workflow_rights FROM sb_users_rights WHERE ur_plugin=? AND ur_cat_id IN (?a) AND ur_workflow_rights <> ?', $plugin_ident, $_SESSION['sbAuth']->getUserGroups(), '');
			if (!$res)
			{
				$workflow_rights = array();
			}
			else
			{
				if (trim($res[0][0]) != '')
				{
					$user_rights = explode('|', $res[0][0]);
					foreach ($workflow_rights as $key => $value)
					{
						if (!in_array($key, $user_rights))
						{
							unset($workflow_rights[$key]);
						}
					}
				}
				else
				{
					$workflow_rights = array();
				}
			}
		}
	}

	if ($status != 'all')
	{
		if (array_key_exists($status, $workflow_rights))
			return true;
	}
	else
	{
		if (count($workflow_rights) > 0)
			return true;
	}

	return false;
}

/**
 * Функция кодирует строку в соответствии со стандартом rfc3986
 *
 * @param string $string
 * @return string закодированная строка.
 *
 */
function sb_encode_rfc3986($string)
{
	return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode(($string))));
}

if(!function_exists('hash_hmac'))
{
	function hash_hmac($algo, $data, $key, $raw_output = false)
	{
	    $algo = strtolower($algo);
	    $pack = 'H'.strlen($algo('test'));
	    $size = 64;
	    $opad = str_repeat(chr(0x5C), $size);
	    $ipad = str_repeat(chr(0x36), $size);

	    if (strlen($key) > $size)
	    {
	        $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
	    }
	    else
	    {
	        $key = str_pad($key, $size, chr(0x00));
	    }

	    for ($i = 0; $i < strlen($key) - 1; $i++)
	    {
	        $opad[$i] = $opad[$i] ^ $key[$i];
	        $ipad[$i] = $ipad[$i] ^ $key[$i];
	    }

	    $output = $algo($opad.pack($pack, $algo($ipad.$data)));

	    return ($raw_output) ? pack($pack, $output) : $output;
	}
}

/**
 * Функция возвращает строку, обрезанную до заданной длины с учетом целостности слов
 * @param string $str       Исходная строка
 * @param int $len          Необходимая длина без учета символов продолжения
 * @param string $continue  Символ(ы) продолжения
 * @param boolean $hard     Сохранять (false) или не сохранять (true) целостность слов
 */
function sb_wordwrap($str, $len=50, $continue='...', $hard=false)
{
    $str = preg_replace('/\s{2,}/', ' ', $str);

    if(strlen($str) <= $len)
    {
        return $str;
    }

    if($hard)
    {
        $str = substr($str, 0, $len);
    }
    else
    {
        while (strlen($str) > $len)
        {
            $pos = strripos($str, ' ');
            if ($pos !== false)
            {
                $str = substr($str, 0, $pos);
            }
            else
            {
                break;
            }
        }
    }

    return $str.$continue;
}

#--------------------------------------------- функция, использующиеся при выводе на сайте -------------------------------#

/**
 * Выкидывает 404 ошибку и отображает нужную страницу
 *
 */
function sb_404($show_index = true)
{
	$GLOBALS['sbVfs']->mLocal = false;

	while (ob_get_level() > 0)
	{
		ob_end_clean();
	}

	$uri = isset($_SERVER['REDIRECT_REQUEST_URI']) ? trim($_SERVER['REDIRECT_REQUEST_URI'], '/&') : trim($_SERVER['REQUEST_URI'], '/&');

	if (SB_DEMO_SITE)
	{
		$path = explode('?', $uri);
    	$path = '/'.$path[0].'.demo';

    	if ($GLOBALS['sbVfs']->exists($path))
    	{
    		$GLOBALS['sb_not_show_demo_page'] = true;
    		$GLOBALS['PHP_SELF'] = $path;
        	$_SERVER['PHP_SELF'] = $GLOBALS['PHP_SELF'];

        	ob_start();
       	 	include_once(SB_BASEDIR.$path);
       	 	exit(0);
    	}
	}

	$images = array('gif', 'jpeg', 'png', 'jpg', 'swf', 'bmp', 'ico');

	/**
	 * Пишем в журнал отсутствующих страниц
	 */
	$log_date = intval(sbPlugins::getSetting('sb_404_log_date'));
	$time = time() - $log_date * 24 * 60 * 60;
    sql_query('DELETE FROM sb_404_log WHERE l_date < '.$time);

    if ($log_date > 0)
    {
        $res = sql_param_query('SELECT l_count FROM sb_404_log WHERE l_url=? AND l_domain=?', $uri, sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN));
        if ($res)
        {
        	list($count) = $res[0];
        	$count++;

        	sql_param_query('UPDATE sb_404_log SET l_count=?d, l_date=?d, l_ip=? WHERE l_url=? AND l_domain=?', $count, time(), sbAuth::getIP(), $uri, sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN));
        }
        else
        {
        	$row = array();
        	$row['l_url'] = $uri;
        	$row['l_domain'] = sb_str_replace('www-demo.', '', SB_COOKIE_DOMAIN);
        	$row['l_count'] = 1;
        	$row['l_date'] = time();
        	$row['l_ip'] = sbAuth::getIP();

        	sql_param_query('INSERT INTO sb_404_log (?#) VALUES (?a)', array_keys($row), array_values($row));
        }
    }

    header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');

    if (!$show_index || (isset($GLOBALS['sb_not_show_404_page']) && $GLOBALS['sb_not_show_404_page']))
    {
    	exit(0);
    }

    if (($pos = strpos($uri, '?')) !== false)
	{
	    $uri = substr($uri, 0, $pos);
	}

	$ext_pos = strrpos($uri, '.');
	if ($ext_pos)
	{
	    $ext = substr($uri, $ext_pos + 1);
	}
	else
	{
		$ext = '';
	}

	if (in_array(strtolower($ext), $images))
	{
		exit(0);
	}

	$_GET = array();

    $page_404 = trim(sbPlugins::getSetting('sb_404_page'));
    if ($page_404 == '' && $GLOBALS['sbVfs']->exists('.htaccess'))
    {
    	$file_str = $GLOBALS['sbVfs']->file('.htaccess');

        foreach ($file_str as $line)
        {
            $line = trim($line);
            if (substr($line, 0, 1) == '#')
                continue;

            $m = null;
            if (preg_match('/errordocument[\s]+404[\s]+(.*)?/si', $line, $m))
            {
            	$page_404 = $m[1];
            	break;
            }
        }
    }

    if ($page_404 != '' && $GLOBALS['sbVfs']->exists($page_404))
    {
    	$GLOBALS['sb_gzip_compress'] = false;
    	$GLOBALS['sb_not_show_404_page'] = true;
    	$GLOBALS['sb_not_show_demo_page'] = true;

        ob_start();
        $GLOBALS['PHP_SELF'] = '/'.trim($page_404, '\\/');
        $_SERVER['PHP_SELF'] = $GLOBALS['PHP_SELF'];

        include_once(SB_BASEDIR.$GLOBALS['PHP_SELF']);
    }

    exit(0);
}

/**
 * Распарсивает дату под переданный макет дизайна
 *
 * @param int $time Время (UNIX timestamp).
 * @param str $templ Макет вывода даты.
 * @param str $lang Язык.
 * @param str $im Выводить название месяца в именительном падеже.
 *
 * @return string Строка со значением даты, преобразованной в нужный формат.
 */
function sb_parse_date($time, $templ, $lang='ru', $im=false)
{
    if (trim($templ) == '')
        return '';

	list($year_l, $year_s, $month_t, $month, $week_day, $day, $hour, $min) = explode('/', sb_date('Y/y/n/m/w/d/H/i', $time));

	$mTags = array ('{LONG_YEAR}', '{SHORT_YEAR}', '{MONTH_TEXTUAL}', '{MONTH}', '{WEEKDAY_SHORT}', '{WEEKDAY}' , '{DAY}', '{HOUR}', '{MINUTE}', '{TIMESTAMP}');
	$mTagsVal = array ($year_l, $year_s, $im ? $GLOBALS['sb_month_arr_im'][$lang][$month_t] : $GLOBALS['sb_month_arr'][$lang][$month_t], $month, $GLOBALS['sb_day_arr_short'][$lang][$week_day], $GLOBALS['sb_day_arr'][$lang][$week_day], $day, $hour, $min, $time);

	$result = str_replace ($mTags, $mTagsVal, $templ);

	return $result;
}

/**
 * Приведение псевдостатического адреса к нужному виду.
 *
 * @param int $id Идентификатор элемента.
 * @param string $url Псевдостатический адрес элемента.
 * @param string $title Название элемента.
 * @param string $table Название таблицы базы данных элемента.
 * @param string $url_field Название поля таблицы, которое хранит псевдостатический адрес элемента.
 * @param string $id_field Название поля таблицы, которое хранит идентификатор элемента.
 * @param string $dop_sql Дополнительные SQL-условия, добавляемые после WHERE.
 *
 * @return string Новый псевдостатический адрес.
 */
function sb_check_chpu($id, $url, $title, $table, $url_field, $id_field, $dop_sql='')
{
	if (sbPlugins::getSetting('sb_russian_static_urls') != 1)
	{
		$url = trim(preg_replace('/[^0-9a-zA-Z_\-]+/', '', $url));
	}
	else
	{
		$url = trim(preg_replace('/[^0-9a-zA-Z_\-'.$GLOBALS['sb_reg_lower_interval'].$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '', $url));
	}

    if ($url == '')
    {
    	if (sbPlugins::getSetting('sb_russian_static_urls') != 1)
    	{
        	$url = trim(preg_replace('/[^0-9a-zA-Z_\-]+/', '', trim(sb_strtolat($title), ' -_')));
    	}
    	else
    	{
    		$url = trim(preg_replace('/[^0-9a-zA-Z_\-'.$GLOBALS['sb_reg_lower_interval'].$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '-', $title));
    		$url = trim(preg_replace('/[\-]+/', '-', $url), ' -_');
    	}

        $url = str_replace('_', '-', $url);
        if (sb_strlen($url) > 50)
        {
            $pos = sb_strpos($url, '-', 50);
            if ($pos !== false)
            {
                $url = sb_substr($url, 0, $pos);
            }
        }
    }

    if ($url == strval(intval($url)))
    {
    	$url = '_'.$url;
    }

    $res = sql_query('SELECT COUNT(*) FROM '.$table.' WHERE '.($dop_sql != '' ? $dop_sql.' AND ' : '').$url_field.'=? {AND '.$id_field.' != ?d}', $url, ($id != '' ? $id : SB_SQL_SKIP));
    $new_url = $url;

    if ($res && $res[0][0] > 0)
    {
	    $i = preg_replace('/[^0-9]/', '_', $new_url);
		$pos = sb_strrpos($i, '_');

		if ($pos !== false)
		{
			$new_url = $url = sb_substr($url, 0, $pos + 1);
	    	$i = sb_substr($i, $pos + 1);
		}

		$i = intval($i);
	    while($res && $res[0][0] > 0)
	    {
	        $i++;
	        $new_url = $url.$i;

	        $res = sql_query('SELECT COUNT(*) FROM '.$table.' WHERE '.$url_field.'=? {AND '.$id_field.' != ?d}', $new_url, ($id != '' ? $id : SB_SQL_SKIP));
	    }
    }
	if (sbPlugins::getSetting('sb_strtolower_static_urls') == 1)
    {
        $new_url = sb_strtolower($new_url);
    }
	
    return $new_url;
}

/**
 * Перевод переменной в формат JSON
 *
 * @param string $var Переменная для перевода.
 *
 * @return mixed Cтрока в формате JSON или FALSE в случае ошибки.
 */
function sb_json_encode($var)
{
	if (function_exists('json_encode'))
		return json_encode($var);

	require_once (SB_CMS_LIB_PATH.'/sbJSON.inc.php');
	$json = new sbJSON();

	return $json->encode($var);
}

/**
 * Перевод строки из формата JSON в переменную
 *
 * @param string $str Строка для перевода.
 *
 * @return mixed Обработанная переменная.
 */
function sb_json_decode($str)
{
	if (function_exists('json_decode'))
		return json_decode($str);

	require_once (SB_CMS_LIB_PATH.'/sbJSON.inc.php');
	$json = new sbJSON();

	return $json->decode($str);
}
/**
 * Функция возвращает список компонентов страницы
 *
 * @param string $page_url Url страницы
 * @param string  $e_ident Идентификатор типа компонента
 * @param string $to Формат результата
 */
function sb_get_elem_components($page_url, $e_ident, $to = 'js')
{
	// Узнаем урл
    $page_id = fPages_Get_Page_By_Url($page_url);
    if ($page_id != -1)
    {
    	// Достаем макет дизайна
		$res = sql_param_query('SELECT p_temp_id FROM sb_pages WHERE p_id=?d', $page_id);
		if(!$res)
			return $to == 'php' ? array('' => ' --- ') : '{"keys":[""],"values":[" --- "]}';

		$p_temp_id = $res[0][0];
	    //	Достаем компоненты страницы
		$res = sql_param_query('SELECT e_tag FROM sb_elems WHERE ((e_p_id=?d AND e_link="page") OR (e_p_id=?d AND e_link="temp")) AND e_ident = ?', $page_id, $p_temp_id, $e_ident);
		if(!$res)
			return $to == 'php' ? array('' => ' --- ') : '{"keys":[""],"values":[" --- "]}';

		if($to == 'php')
		{
			$components = array('' => ' --- ');
			foreach($res as $value)
			{
				$components[addslashes(trim($value[0], '{}'))] = addslashes(trim(str_replace('_',' ', $value[0]),'{}'));
			}
			return $components;
		}
		elseif($to == 'js')
		{
			$keys = '"",';
    		$values = '" --- ",';
			foreach($res as $value)
			{
				$keys .= '"'.addslashes(trim($value[0], '{}')).'",';
	    		$values .= '"'.addslashes(trim(str_replace('_',' ', $value[0]),'{}')).'",';
	    	}
	    	$keys = sb_substr($keys, 0, -1);
	    	$values = sb_substr($values, 0, -1);

	    	$result = '{"keys":['.$keys.'],"values":['.$values.']}';
			return $result;
		}
	}
	else
	{
		return $to == 'php' ? array('' => ' --- ') : '{"keys":[""],"values":[" --- "]}';
	}
}
/**
 * Ф-ция возвращает sql данные для плагина
 *
 * @param string $plugin идентификатор плагина
 */
function getPluginSqlParams($plugin)
{
    return sbPlugins::getPluginSqlParams($plugin);
}

/**
 * Функция getPluginDesignTemps перенесена в sbPlugins
 */


/**
 * Возвращает список полей модуля, которые можно использовать в качестве названия
 *
 * @param string $plugin - Идентификатор модуля
 * @param string $ret - формат возвращаемых данных: json, array
 *
 */
function getPluginTitleFields($plugin)
{
	$flds = array();
	// Стандартные поля
	if(isset($_SESSION['sbPlugins']) && isset($_SESSION['sbPlugins']->mFieldsInfo[$plugin]['fields']))
	{
		foreach ($_SESSION['sbPlugins']->mFieldsInfo[$plugin]['fields'] as $key => $value)
		{
			if(trim($key) != '' && isset($value['link_title']) && isset($value['title']) && isset($value['tag']) && $value['link_title'] == '1')
				$flds[] = array('field' => $key, 'title' => $value['title'], 'tag' => $value['tag']);
		}
	}

	// Пользовательские поля
	$res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident = ?', $plugin);
	if(!$res)
	{
		return $flds;
	}
	$flds_t = unserialize($res[0][0]);

	if(is_array($flds_t) && count($flds_t) > 0)
	{
		foreach($flds_t as $value)
		{
			if($value['type'] == 'string' || $value['type'] == 'text' || $value['type'] == 'number' || $value['type'] == 'date' || $value['type'] == 'color')
			{
				$flds[] = array('field' => 'user_f_'.$value['id'], 'title' => $value['title'], 'tag' => '{OPT_USER_F_'.$value['id'].'}');
			}
		}
	}
	return $flds;
}

/**
 * Возвращает чекбокс для групповог редактирования элементов
 *
 * @param string $field Название поля для которого нужно вернуть чекбокс.
 * @param string $edit_group Флаг. Ипользуется групповое редактирование (TRUE) или нет (FALSE)
 *
 */
function sbGetGroupEditCheckbox($field, $edit_group)
{
	if(!$edit_group)
		return '';

	$f = new sbLayoutInput('checkbox', '1', 'ch_'.$field, '', (isset($_POST['ch_'.$field]) && $_POST['ch_'.$field] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
	return '<br>
			<div style="float:right;">
				<div style="float:left;">'.$f->getField().'</div>
				<div style="float:left;margin-top:1px;font-weight:normal;">
					<label for="ch_'.$field.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label>
				</div>
			</div>';
}

/**
 * Проверяет является ли редактирование элемента(ов) групповым.
 *
 * @param bool $check_count Проверять или нет кол-во элементов массива $_GET['ids'].
 *
 * @return bool (TRUE) групповое, (FALSE) не групповое
 */
function sbIsGroupEdit($check_count = true)
{
	if (!isset($_GET['ids']) || trim($_GET['ids']) == '')
	{
		$_GET['ids'] = '';
		return false;
	}

	$_GET['ids'] = trim(preg_replace('/[^0-9,]+/'.SB_PREG_MOD, '', $_GET['ids']));

	if ($_GET['ids'] == '')
		return false;

	$_GET['ids'] = array_unique(explode(',', $_GET['ids']));

	$tmp = array();
	foreach ($_GET['ids'] as $value)
	{
		$value = intval($value);
		if ($value <= 0)
			continue;

		$tmp[] = $value;
	}

	if($check_count && count($tmp) <= 1)
	{
		$_GET['ids'] = '';
		return false;
	}

	$_GET['ids'] = $tmp;

	return true;
}

/**
 * Убирает небезопасные символы ("\n", "\r", "\t") в строке (используется при передаче в функцию header переменных).
 *
 * @param string $str Строка, в которой необходимо вырезать небезопасные символы.
 *
 * @return string Строка с вырезанными небезопасными символами.
 */
function sb_sanitize_header($str)
{
	return str_replace(array("\n", "\r", "\t"), '', $str);
}

/**
 * Определяет поддерживает браузер пользователя HTML5 или нет
 * @return boolean (TRUE) - поддерживает, (FALSE) - не поддерживает
 */
function sb_supportHTML5()
{
    if(browser_is_firefox() || browser_is_safari() || browser_is_chrome())
    {
        return true;
    }

    return false;
}

/**
 * Функция для очистки кода от уязвимостей с использованием HEREDOC-синтаксиса
 * 
 * @param $str Строка для очистки
 * @return $str Очищенная строка
 */
function sb_clean_string($str)
{
	$str = preg_replace('/\{\s*?\$\s*?\{.*?\}\s*?\}/imsu', '', $str);
 	$str = preg_replace('/\$\s*?\{.*?\}/imsu', '', $str);
	
	return $str;
}
?>