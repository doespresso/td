<?php
/**
 * Функции для получения идентификатора и версии браузера пользователя
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2008, OOO "СИБИЭС Групп"
 */

/**
 * Возвращает идентификатор браузера пользователя
 *
 * Результат функции - одно из значений:
 *
 * <ol>
 * <li>OPERA</li>
 * <li>IE</li>
 * <li>CHROME</li>
 * <li>SAFARI</li>
 * <li>MOZILLA</li>
 * <li>OTHER</li>
 * </ol>
 *
 * Если передан параметр $browser, то результат функции - массив, первый элемент - идентификатор браузера,
 * второй - версия браузера.
 *
 * @param string $browser Строка описания браузера.
 *
 * @return mixed Идентификатор браузера, если не передан параметр $browser, или массив, первый элемент которого
 * является идентификатором браузера, второй - версией браузера.
 */
function browser_get_agent($browser = '')
{
	if ($browser == '')
    	return $GLOBALS['sb_browser_agent'];

    $log_version = array();
	if (preg_match('!Opera/([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version))
	    return array('OPERA', $log_version[1]);
	else if (preg_match('!MSIE ([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version) || preg_match('!rv:(\d+\.\d+)\) like Gecko!', $browser, $log_version))
		return array('IE', $log_version[1]);
	else if (preg_match('!Chrome/([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version))
	    return array('CHROME', $log_version[1]);
	else if (preg_match('!Safari/([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version))
	{
		preg_match('!Version/([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version);
	    return array('SAFARI', $log_version[1]);
	}
	else if (preg_match('!Firefox/([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version) || preg_match('!Mozilla/([0-9]{1,2}.[0-9]{1,2})!', $browser, $log_version))
		return array('MOZILLA', $log_version[1]);
	else
		return array('OTHER', 0);
}

/**
 * Возвращает версию браузера пользователя
 *
 * @return string Версия браузера пользователя.
 */
function browser_get_version()
{
    return $GLOBALS['sb_browser_ver'];
}

/**
 * Возвращает идентификатор операционной системы пользователя
 *
 * Результат функции - одно из значений:
 *
 * <ol>
 * <li>Win</li>
 * <li>Mac</li>
 * <li>Linux</li>
 * <li>Unix</li>
 * <li>Other</li>
 * </ol>
 *
 * @return string Идентификато операционной системы пользователя.
 */
function browser_get_platform()
{
    return $GLOBALS['sb_browser_platform'];
}

/**
 * Возвращает TRUE, если операционная система пользователя Windows, и FALSE в ином случае.
 *
 * @return bool TRUE или FALSE.
 */
function browser_is_windows()
{
    if (browser_get_platform() == 'Win')
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Возвращает TRUE, если браузер пользователя Internet Explorer версии 5.5 и выше, и FALSE в ином случае.
 *
 * @return bool TRUE или FALSE.
 */
function browser_is_ie()
{
    if (browser_get_agent() == 'IE' && browser_get_version() >= '5.5')
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Возвращает TRUE, если браузер пользователя FireFox версии 3.0 и выше, и FALSE в ином случае.
 *
 * @return bool TRUE или FALSE.
 */
function browser_is_firefox()
{
    if (browser_get_agent() == 'MOZILLA' && browser_get_version() >= '3.0')
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Возвращает TRUE, если браузер пользователя Opera версии 9.5 и выше, и FALSE в ином случае.
 *
 * @return bool TRUE или FALSE.
 */
function browser_is_opera()
{
    if (browser_get_agent() == 'OPERA' && browser_get_version() >= '9.5')
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Возвращает TRUE, если браузер пользователя Safari версии 3.0 и выше, и FALSE в ином случае.
 *
 * @return bool TRUE или FALSE.
 */
function browser_is_safari()
{
    if (browser_get_agent() == 'SAFARI' && browser_get_version() >= '3.0')
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Возвращает TRUE, если браузер пользователя Google Chrome, и FALSE в ином случае.
 *
 * @return bool TRUE или FALSE.
 */
function browser_is_chrome()
{
    if (browser_get_agent() == 'CHROME')
    {
        return true;
    }
    else
    {
        return false;
    }
}

if (!isset($_SERVER['HTTP_USER_AGENT']))
{
	$GLOBALS['sb_browser_ver'] = 0;
	$GLOBALS['sb_browser_agent'] = 'OTHER';
	$GLOBALS['sb_browser_platform'] = 'Other';
}
else
{
	$log_version = array();
	if (preg_match('!Opera/([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version))
	{
	    $GLOBALS['sb_browser_ver'] = $log_version[1];
	    $GLOBALS['sb_browser_agent'] = 'OPERA';
	}
	else if (preg_match('!MSIE ([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version))
	{
	    $GLOBALS['sb_browser_ver'] = $log_version[1];
	    $GLOBALS['sb_browser_agent'] = 'IE';
	}
    else if (preg_match('!rv:(\d+\.\d+)\) like Gecko!', $_SERVER['HTTP_USER_AGENT'], $log_version)) {
        $GLOBALS['sb_browser_ver'] = $log_version[1];
        $GLOBALS['sb_browser_agent'] = 'IE';
    }
	else if (preg_match('!Chrome/([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version))
	{
	    $GLOBALS['sb_browser_ver'] = $log_version[1];
	    $GLOBALS['sb_browser_agent'] = 'CHROME';
	}
	else if (preg_match('!Safari/([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version))
	{
		if (preg_match('!Version/([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version))
		{
			$GLOBALS['sb_browser_ver'] = $log_version[1];
	    	$GLOBALS['sb_browser_agent'] = 'SAFARI';
		}
		else
		{
			// Dooble
			$GLOBALS['sb_browser_ver'] = 0;
			$GLOBALS['sb_browser_agent'] = 'OTHER';
		}
	}
	else if (preg_match('!Firefox/([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version) || preg_match('!Mozilla/([0-9]{1,2}.[0-9]{1,2})!', $_SERVER['HTTP_USER_AGENT'], $log_version))
	{
	    $GLOBALS['sb_browser_ver'] = $log_version[1];
	    $GLOBALS['sb_browser_agent'] = 'MOZILLA';
	}
	else
	{
	    $GLOBALS['sb_browser_ver'] = 0;
	    $GLOBALS['sb_browser_agent'] = 'OTHER';
	}

	if (strstr($_SERVER['HTTP_USER_AGENT'], 'Win'))
	{
	    $GLOBALS['sb_browser_platform'] = 'Win';
	}
	else if (strstr($_SERVER['HTTP_USER_AGENT'], 'Mac'))
	{
	    $GLOBALS['sb_browser_platform'] = 'Mac';
	}
	else if (strstr($_SERVER['HTTP_USER_AGENT'], 'Linux'))
	{
	    $GLOBALS['sb_browser_platform'] = 'Linux';
	}
	else if (strstr($_SERVER['HTTP_USER_AGENT'], 'Unix'))
	{
	    $GLOBALS['sb_browser_platform'] = 'Unix';
	}
	else
	{
	    $GLOBALS['sb_browser_platform'] = 'Other';
	}
}
