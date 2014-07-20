<?php
/**
 * Реализация класса, отвечающего за аутентификацию пользователей сайта и работу с пользовательскими сессиями
 *
 * Данный класс использует при работе библиотеку работы с СУБД MySQL.
 * После обявления класса вызывается конструктор класса и ссылка на класс сохраняется в глобальной переменной $_SESSION['sbAuth'].
 *
 * @see $_SESSION['sbAuth']
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_SiteAuth
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Класс, отвечающий за аутентификацию пользователя
 *
 * Класс, отвечающий за аутентификацию пользователей сайта и работу с пользовательскими сессиями.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_SiteAuth
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

define('SB_AUTH_SITE_ERROR_WRONG_LOGIN', 1);
define('SB_AUTH_SITE_ERROR_PREMOD_ACCOUNT', 2);
define('SB_AUTH_SITE_ERROR_EMAIL_ACCOUNT', 3);
define('SB_AUTH_SITE_ERROR_PREMOD_EMAIL_ACCOUNT', 4);
define('SB_AUTH_SITE_ERROR_BLOCKED_ACCOUNT', 5);
define('SB_AUTH_SITE_ERROR_WRONG_PASSWORD', 6);
define('SB_AUTH_SITE_ERROR_WRONG_DOMAIN', 7);
define('SB_AUTH_SITE_ERROR_BLOCKED_GROUP', 8);
define('SB_AUTH_SITE_ERROR_HACK_ATTACK', 9);
define('SB_AUTH_SITE_ERROR_WRONG_IP', 10);
define('SB_AUTH_SITE_ERROR_CROSS_EXIT', 11);

class sbAuth
{
    /**
     * Идентификатор пользователя сайта
     *
     * @var int
     */
    private $mId = -1;

    /**
     * Идентификаторы групп пользователя сайта
     *
     * @var int
     */
    private $mCatIds = array();

    /**
     * Логин пользователя сайта
     *
     * @var string
     */
    private $mLogin = '';

    /**
     * E-mail пользователя сайта
     *
     * @var string
     */
    private $mEmail = '';

    /**
     * Ф.И.О. пользователя сайта
     *
     * @var string
     */
    private $mName = '';

    /**
     * IP-адрес пользователя сайта
     *
     * @var string
     */
    private $mIP = '';

    /**
     * Браузер пользователя сайта
     *
     * @var string
     */
    private $mBrowser = '';

    /**
     * Идентификатор сессии пользователя сайта
     *
     * @var int
     */
    private $mSessionId = '';

    /**
     * Код ошибки
     *
     * @var int
     */
    private static $mErrorCode = -1;

    /**
     * Признак авторизации через социальную сеть
     * @var type
     */
    private $socialLogin = false;

    /**
     * Список маркеров соцсетей пользователя
     * @var array
     */
    private $socialNetworks = array();

    /**
     * Маркер пользователя.
     * @var boolean
     */
    public $isSystemUser = false;

    /**
     * Конструктор класса
     *
     * В конструкторе класса производится аутентификация пользователя, определяется браузер пользователя,
     * его IP-адрес и идентификатор сессии.
     *
     * @param string $login Логин пользователя сайта.
     * @param string $email Электронный адрес пользователя сайта.
     * @param string $password Пароль пользователя сайта.
     *
     * @return sbAuth
     */
    public function __construct($login, $email, $password, $social=false)
    {
        if (isset($_SESSION['sbAuth']))
        {
            // пользователь уже залогинился
            return;
        }

        /**
         * Запоминаем IP-адрес и браузер пользователя, запросившего страницу,
         * а также идентификатор сессии
         */
        $this->mIP = $this->getIP();
        $this->mBrowser = preg_replace('/\s*chromeframe(\/[0-9\.]+)?/i', '', $_SERVER['HTTP_USER_AGENT']);
        $this->socialLogin = $social;

        if ($this->doLogin($login, $email, $password))
        {
            // все проверки прошли успешно, устанавливаем флаг старта сессии, создаем класс sbAuth
            sbProgStartSession();

            $this->mSessionId = session_id();

            $_SESSION['sbAuth'] = $this;
        }
    }

    /**
     * Определяет IP-адрес пользователя учитывая прокси-сервер
     *
     * @return string IP-адрес пользователя
     */
    static function getIP()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown'))
            $ip = getenv('HTTP_CLIENT_IP');
        elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown'))
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown'))
            $ip = getenv('REMOTE_ADDR');
        elseif (!empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = 'unknown';

        return $ip;
    }

    /**
     * Сравнивает IP-адрес с маской.
     *
     * @param string $ip_a Маска.
     * @param string $ip_b IP-адрес.
     *
     * @return bool TRUE, если IP-адрес совпадает с маской и FALSE в ином случае.
     */
    static function compareIP($ip_a, $ip_b)
    {
        $ip_a = explode('.', $ip_a);
        $ip_b = explode('.', $ip_b);

        foreach ($ip_a as $key => $value)
        {
            if ($value == '*')
                continue;
            if ($value != $ip_b[$key])
                return false;
        }

        return true;
    }

    /**
     * Сравнение текущего IP-адреса пользователя, браузера и идентификатора сессии с сохраненными при входе пользователя
     *
     * Если эти параметры не совпадают, то считаем, что имеет место нарушение безопасности системы.
     *
     * @param string $user_ip IP-адрес пользователя.
     * @param string $user_browser Браузер пользователя.
     * @param integer $session_id Идентификатор сессии.
     */
    private function checkIP($user_ip, $user_browser, $session_id)
    {
    	$user_browser = preg_replace('/\s*chromeframe(\/[0-9\.]+)?/i', '', $user_browser);
        if ($this->mIP != $user_ip || $this->mBrowser != $user_browser || $this->mSessionId != $session_id)
        {
            //безопасность системы под угрозой
            return false;
        }

        return true;
    }

    /**
     * Проверяем сессию пользователя
     */
    public function checkSession()
    {
        sql_query('UPDATE sb_site_users SET su_status = 4 WHERE su_active_date <> 0 AND su_active_date < '.time());

        $res = sql_param_query('SELECT su_status, su_domains, su_auto_login FROM sb_site_users WHERE su_id=?d', $this->mId);
        if (!$res)
        {
            // пользователь с заданным логином не найден
            self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_LOGIN;
            $this->doLogout();
	        return false;
        }

        list($su_status, $su_domains, $su_cross_domain_exit) = $res[0];

		if($su_cross_domain_exit == -1){
		    if(isset($_SESSION['sbAuth']))
            {
                sql_query('UPDATE sb_site_users SET su_auto_login = "" WHERE su_id =?', $_SESSION['sbAuth']->getUserId());
            }
            if(!isset($_COOKIE['sb_site_logout']))
            {
                self::$mErrorCode = SB_AUTH_SITE_ERROR_CROSS_EXIT;
                $this->doLogout(true);
                return false;
            }
		}
        sb_setcookie('sb_site_logout', '');

        switch ($su_status)
        {
            case 0:
                break;

            case 1:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_PREMOD_ACCOUNT;
                $this->doLogout();
                return false;

            case 2:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_EMAIL_ACCOUNT;
                $this->doLogout();
                return false;

            case 3:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_PREMOD_EMAIL_ACCOUNT;
                $this->doLogout();
                return false;

            default:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_BLOCKED_ACCOUNT;
                $this->doLogout();
                return false;
        }

        if (!$this->checkIP($this->getIP(), $_SERVER['HTTP_USER_AGENT'], session_id()))
        {
            // IP-адрес или браузер пользователя не совпадают с ID-сессии
            self::$mErrorCode = SB_AUTH_SITE_ERROR_HACK_ATTACK;
            $this->doLogout();
            return false;
        }

        $su_domains = explode(',', $su_domains);
        if (!in_array(SB_KEY_DOMAIN, $su_domains))
        {
            // пользователь не может админить выбранный домен
            self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_DOMAIN;
            $this->doLogout();
            return false;
        }

        // группа пользователя
        $res = sql_param_query('SELECT DISTINCT links.link_cat_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND categs.cat_rubrik = 1 AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_site_users', $this->mId);
        if ($res)
        {
        	$this->mCatIds = array();
            foreach ($res as $value)
            {
                $this->mCatIds[] = $value[0];
            }
        }
        else
        {
            self::$mErrorCode = SB_AUTH_SITE_ERROR_BLOCKED_GROUP;
            $this->doLogout();
 	        return false;
        }

    	// IP-адрес
    	$availIPs = sbPlugins::getSetting('sb_site_ip');
        if ($availIPs != '')
        {
            $error = true;
            $availIPs = explode(' ', $availIPs);
            foreach ($availIPs as $value)
            {
                if (trim($value) != '')
                {
                    if ($this->compareIP($value, $this->mIP))
                    {
                        $error = false;
                        break;
                    }
                }
            }

            if ($error)
            {
            	self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_IP;
                $this->doLogout();
                return false;
            }
        }

        // выставляем дату последней активности
        sql_param_query('UPDATE sb_site_users SET su_last_date='.time().' WHERE su_id=?d', $this->mId);

        return true;
    }

    /**
     * Аутентификация пользователя сайта
     *
     * Проверяет логин и пароль пользователя сайта.
     *
     * @param string $login Логин пользователя сайта.
     * @param string $email Электронный адрес пользователя сайта.
     * @param string $password Пароль пользователя сайта.
     *
     * @return TRUE, если аутентификация прошла успешно, FALSE в ином случае.
     */
    private function doLogin($login, $email, $password)
    {
    	if ($login == '' && $email == '' && $password == '' && isset($_COOKIE['sb_site_auto_login']) && trim($_COOKIE['sb_site_auto_login']) != '')
    	{
    		$res = sql_query('SELECT su_login, su_email, su_pass FROM sb_site_users WHERE su_auto_login=?', $_COOKIE['sb_site_auto_login']);
    		if ($res)
    		{
    			list($login, $email, $password) = $res[0];
    		}
    	}
        elseif ($login != '' && $this->socialLogin)
        {
            $res = sql_query('SELECT su_id, su_pass, su_name, su_email, su_status, su_domains, su_login FROM sb_site_users WHERE su_soc_login=?s', $login);
        }
    	else
    	{
    		$password = md5($password);
    	}

    	$hash = '';
    	sql_query('UPDATE sb_site_users SET su_status=4 WHERE su_active_date != 0 AND su_active_date < '.time());

        if (!$this->socialLogin)
        {
            if ($email == '')
            {
                $res = sql_query('SELECT su_id, su_pass, su_name, su_email, su_status, su_domains, su_login FROM sb_site_users WHERE su_login=?', $login);
            }
            elseif ($login == '')
            {
                $res = sql_param_query('SELECT su_id, su_pass, su_name, su_email, su_status, su_domains, su_login FROM sb_site_users WHERE su_email=?', $email);
            }
            else
            {
                $res = sql_param_query('SELECT su_id, su_pass, su_name, su_email, su_status, su_domains, su_login FROM sb_site_users WHERE su_login=? AND su_email=?', $login, $email);
            }
        }

        if (!$res)
        {
            // пользователь с заданным логином не найден
            self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_LOGIN;
            sb_setcookie('sb_site_auto_login', $hash);
	        return false;
        }

        list($su_id, $su_pass, $su_name, $su_email, $su_status, $su_domains, $su_login) = $res[0];

    	if ($password != $su_pass && !$this->socialLogin)
        {
            // неверно указан пароль
            self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_PASSWORD;
            sb_setcookie('sb_site_auto_login', $hash);
 	        return false;
        }

        switch ($su_status)
        {
            case 0:
                break;

            case 1:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_PREMOD_ACCOUNT;
                sb_setcookie('sb_site_auto_login', $hash);
                return false;

            case 2:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_EMAIL_ACCOUNT;
                sb_setcookie('sb_site_auto_login', $hash);
                return false;

            case 3:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_PREMOD_EMAIL_ACCOUNT;
                sb_setcookie('sb_site_auto_login', $hash);
                return false;

            default:
                self::$mErrorCode = SB_AUTH_SITE_ERROR_BLOCKED_ACCOUNT;
                sb_setcookie('sb_site_auto_login', $hash);
                return false;
        }

        $su_domains = explode(',', $su_domains);
        if (!in_array(SB_KEY_DOMAIN, $su_domains))
        {
            // пользователь не может админить выбранный домен
            self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_DOMAIN;
            sb_setcookie('sb_site_auto_login', $hash);
            return false;
        }

        // группа пользователя
        $res = sql_param_query('SELECT DISTINCT links.link_cat_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND categs.cat_rubrik=1 AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_site_users', $su_id);
        $su_cat_ids = array();
        if ($res)
        {
            foreach ($res as $value)
            {
                $su_cat_ids[] = $value[0];
            }
        }
        else
        {
            self::$mErrorCode = SB_AUTH_SITE_ERROR_BLOCKED_GROUP;
            sb_setcookie('sb_site_auto_login', $hash);
 	        return false;
        }

        if (isset($_SESSION['login_params']))
        {
        	$params = unserialize(stripslashes($_SESSION['login_params']));
	        if(isset($params['ids']))
	        {
	        	$res = array_intersect($su_cat_ids, explode('^', $params['ids']));
	        	if (count($res) <= 0)
	        	{
	        		self::$mErrorCode = SB_AUTH_SITE_ERROR_BLOCKED_GROUP;
	        		return false;
	        	}
	        }
        }

        // IP-адрес
    	$availIPs = sbPlugins::getSetting('sb_site_ip');
        if ($availIPs != '')
        {
            $error = true;
            $availIPs = explode(' ', $availIPs);
            foreach ($availIPs as $value)
            {
                if (trim($value) != '')
                {
                    if ($this->compareIP($value, $this->mIP))
                    {
                        $error = false;
                        break;
                    }
                }
            }

            if ($error)
            {
            	self::$mErrorCode = SB_AUTH_SITE_ERROR_WRONG_IP;
                sb_setcookie('sb_site_auto_login', $hash);
                return false;
            }
        }

        $this->mId = $su_id;
        $this->mLogin = $su_login;
        $this->mName = $su_name;
        $this->mEmail = $su_email;
        $this->mCatIds = $su_cat_ids;

    	if (isset($_POST['su_auto_login']) && $_POST['su_auto_login'] == 1)
        {
        	$hash = md5(time().uniqid());
        	sb_setcookie('sb_site_auto_login', $hash, time() + 180 * 24 * 60 * 60);
        	sql_param_query('UPDATE sb_site_users SET su_auto_login=? WHERE su_id=?d', $hash, $this->mId);
        }

        // выставляем дату последнего входа в систему и IP
        sql_param_query('UPDATE sb_site_users SET ?a WHERE su_id=?d', array('su_last_date' => time(),
                        'su_last_ip' => $this->mIP.(isset($_SERVER['REMOTE_PORT']) ? ':'.$_SERVER['REMOTE_PORT'] : '')), $this->mId);

        return true;
    }

    /**
     * Закрывает сессию пользователя
     */
    static function doLogout($clearAutoLogin=false)
    {
        /**
         * Пользователь вышел из системы
         */
		if(isset($_SESSION['sbAuth']) && !$clearAutoLogin){
        	sql_query('UPDATE sb_site_users SET su_auto_login = -1 WHERE su_id =?', $_SESSION['sbAuth']->getUserId());
        }

        $captcha = '';

        // сохраняем переменную сессии для капчи
        if (isset($_SESSION['sb_captcha']) && is_array($_SESSION['sb_captcha']))
        {
            $captcha = $_SESSION['sb_captcha'];
        }

        sb_setcookie('sb_start_session', '');
        sb_setcookie('sb_site_auto_login', '');
        sb_setcookie('sb_site_logout', '1');

        $_SESSION = array();

        if ($captcha != '')
        {
            $_SESSION['sb_captcha'] = $captcha;
        }
        else
        {
            if (isset($_COOKIE[session_name()]))
            {
                sb_setcookie(session_name(), '');
            }

            if (@session_id() != '')
            {
                @session_destroy();
            }
        }
    }

    /**
     * Проверка ключа системы
     *
     * @ignore
     */
    static function checkKey()
    {
    	if (!file_exists(SB_CMS_KERNEL_PATH.'/key.php'))
        {
            // файл key.php отсутствует
            return SB_AUTH_NO_KEY;
        }
        require_once(SB_CMS_LIB_PATH.'/sbDB.inc.php');

        $tmp = array();
        $error = sbKeyCheck($tmp, false);
        if ($error != '')
        {
            return $error;
        }

        if (SB_CLIENT_VALIDITY != -1 && SB_CLIENT_VALIDITY < time())
        {
            // срок действия ключа истек
            return SB_AUTH_LIC_EXPIRED;
        }

        return '';
    }

     /**
     * Проверка модуля на наличие в ключе системы
     *
     * @ignore
     */
    static function checkPlugin($plugin)
    {

    	$plugins = '';
    	$error = sbKeyCheck($plugins);

    	$key = SB_CLIENT_VALIDITY.'(w)'.SB_CLIENT_LIC.'#*'.$plugin.'%@Dc';
        $key = md5($key);

        if ($error != '')
        {
			return false;
		}

		if (!in_array($key, $plugins))
            return false;
        else
            return true;
    }


    /**
     * Возвращает идентификатор пользователя
     *
     * @return int Идентификатор пользователя.
     */
    public function getUserId()
    {
        return $this->mId;
    }

    /**
     * Возвращает логин пользователя
     *
     * @return string Логин пользователя.
     */
    public function getUserLogin()
    {
        return $this->mLogin;
    }

    /**
     * Возвращает Ф.И.О. пользователя
     *
     * @return string Ф.И.О. пользователя.
     */
    public function getUserName()
    {
    	return $this->mName;
    }

    /**
     * Возвращает E-mail пользователя
     *
     * @return string E-mail пользователя.
     */
    public function getUserEmail()
    {
        return $this->mEmail;
    }

    /**
     * Возвращает идентификаторы групп, в которые входит пользователь
     *
     * @return array Идентификаторы групп, в которые входит пользователь.
     */
    public function getUserGroups()
    {
        return $this->mCatIds;
    }

    /**
     * Возвращает IP-адрес пользователя.
     *
     * @return int IP-адрес пользователя.
     */
    public function getUserIP()
    {
        return $this->mIP;
    }

 	/** Возвращает код ошибки
     *
     * @return int Код ошибки.
     */
    public static function getErrorCode()
    {
        return self::$mErrorCode;
    }

    /**
     * Проверяет, имеет ли зарегистрированный пользователь доступ к указанным разделам
     *
     * @param array $closed_ids Массив идентификаторов закрытых разделов.
     * @param array $cat_ids Массив идентификаторов всех разделов.
     * @param mixed $rights_ident Идентификаторы прав (массив или строка).
     *
     * @return array Массив идентификаторов разделов, к которым пользователь имеет доступ.
     */
    static function checkRights($closed_ids, $cat_ids, $rights_ident)
    {
        if(isset($_GET['sb_search']) && $_GET['sb_search'] == 1 && isset($_GET['sb_search_hash']))
        {
            $tables = $GLOBALS['sbSql']->showTables();
            if($tables && array_search('sb_search_mem', $tables) !== false)
            {
                $res = sql_param_query('SELECT COUNT(*) FROM sb_search_mem WHERE hash=?s', $_GET['sb_search_hash']);
                if($res && $res[0][0] == 1)
                {
                    return $cat_ids; //поисковому боту можно все
                }
            }
        }

    	if (!is_array($rights_ident))
        {
        	$rights_ident = array($rights_ident);
        }

        if (isset($_SESSION['sbAuth']))
        {
        	$user_groups = $_SESSION['sbAuth']->getUserGroups();

	        $group_ids = '';
	        foreach ($user_groups as $value)
	        {
	            $group_ids .= "group_ids LIKE '%^$value^%' OR ";
	        }

	        $group_ids = " AND ($group_ids group_ids = '')";
        }
        else
        {
        	// если пользователь не залогинился
        	$group_ids = " AND group_ids = ''";
        }

        $res = sbQueryCache::query(1, 'SELECT cat_id FROM sb_catrights WHERE cat_id IN (?a) '.$group_ids.' AND right_ident IN (?a)', $closed_ids, $rights_ident);

        $result = array();
        if ($res)
        {
            foreach ($res as $value)
            {
                $result[] = $value[0];
            }
        }

        $cat_ids = array_diff($cat_ids, $closed_ids);
        $cat_ids = array_merge($cat_ids, $result);

        return $cat_ids;
    }

    /**
     * Проверяет доступность закрытой страницы текущему пользователю
     *
     * @param int $cat_id Идентификатор раздела страницы.
     *
     * @return TRUE, если страница доступна, FALSE в ином случае.
     */
    public function checkPage($cat_id)
    {
        $cat_id = $this->checkRights(array($cat_id), array($cat_id), 'pl_pages_read');

        if (count($cat_id) == 0)
            return false;
        else
            return true;
    }

    /**
     * Возвращает массив со списком соц. сетей пользователя сайта
     * facebook - Facebook
     * vk - ВКонтакте
     * odk - Одноклассники
     * twitter - Twitter
     * livejournal - Livejournal
     *
     * @param boolean $reload Принудительно переинициализировать массив
     * @return array
     */
    public function getSocialNetworks($reload = false)
    {
        if(empty($this->socialNetworks) || $reload)
        {
            $res = sql_param_query('SELECT sbsu_sn_type FROM sb_socnet_users WHERE sbsu_uid=?d', $this->mId);
            $this->socialNetworks = array();
            if($res)
            {
                foreach($res as $row)
                {
                    $this->socialNetworks[] = $row[0];
                }
            }
        }
        return $this->socialNetworks;
    }
}

if (isset($_COOKIE['sb_start_session']) && $_COOKIE['sb_start_session'] == 1)
{
    sbProgStartSession();
}
?>