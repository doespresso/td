<?php
/**
 * Реализация класса, отвечающего за аутентификацию пользователей в системе и работу с пользовательскими сессиями
 *
 * Данный класс использует при работе библиотеку работы с СУБД MySQL.
 * После обявления класса вызывается конструктор класса и ссылка на класс сохраняется в глобальной переменной $_SESSION['sbAuth'].
 *
 * @see $_SESSION['sbAuth']
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Auth
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Класс, отвечающий за аутентификацию пользователя
 *
 * Класс, отвечающий за аутентификацию пользователей в системе и работу с пользовательскими сессиями.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Auth
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbAuth
{
    /**
     * Идентификатор пользователя системы
     *
     * @var int
     */
    private $mId = -1;

    /**
     * Идентификаторы групп пользователей системы
     *
     * @var array
     */
    private $mCatIds = array();

    /**
     * Логин пользователя системы
     *
     * @var string
     */
    private $mLogin = '';

    /**
     * E-mail пользователя системы
     *
     * @var string
     */
    private $mEmail = '';

    /**
     * Ф.И.О. пользователя системы
     *
     * @var string
     */
    public $mName = '';

    /**
     * Входит пользователь в группу администраторов или нет
     * 1 - входит, 0 - нет
     *
     * @var int
     */
    private $mAdmin = 0;

    /**
     * Массив расширений файлов, разрешенных для загрузки на сервер пользователем
     *
     * @var array
     */
    private $mUploadingExts = array();

    /**
     * Вырезать PHP-код из всех REQUEST-данных, отправляемых пользователем
     *
     * TRUE - удалять, FALSE - не удалять.
     *
     * @var bool
     */
    private $mStripPHP = true;

    /**
     * IP-адрес пользователя системы
     *
     * @var string
     */
    private $mIP = '';

    /**
     * Время последней смены пароля
     *
     * @var int
     */
    private $mPassDate = 0;

    /**
     * Браузер пользователя системы
     *
     * @var string
     */
    private $mBrowser = '';

    /**
     * Идентификатор сессии пользователя
     *
     * @var int
     */
    private $mSessionId = '';

    /**
     * Текст ошибки
     *
     * @var string
     */
    public $mError = '';

    /**
     * Модули, разрешенные в ключе
     *
     * @var array
     */
    private $mPlugins = array();

    /**
     * Информация о ключе
     *
     * @var array
     */
    private $mKeyInfo = array();

    /**
     * Флаг, указывающий первый раз входит пользователь в систему (TRUE) или нет (FALSE).
     *
     * @var bool
     */
    private $mFirstLogin = false;
    
    /**
     * Маркер пользователя
     * @var boolean 
     */
    public $isSystemUser = true;

    /**
     * Конструктор класса
     *
     * В конструкторе класса определяется браузер пользователя, его IP-адрес и идентификатор сессии.
     * Проверяется логин и пароль пользователя, стартуется сессия пользователя.
     *
     * @param string $login Логин пользователя.
     * @param string $password Пароль пользователя.
     * @param strign $open_key Открытый ключ для шифрования пароля.
     *
     * @return sbAuth
     */
    public function __construct($login, $password, $open_key)
    {
        /**
         * Запоминаем IP-адрес и браузер пользователя, запросившего страницу,
         * а также идентификатор сессии
         */
        $this->mIP = $this->getIP();
        $this->mBrowser = preg_replace('/\s*chromeframe(\/[0-9\.]+)?/i', '', $_SERVER['HTTP_USER_AGENT']);
        $this->mSessionId = session_id();

        $this->doLogin($login, $password, $open_key);
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

        return preg_replace('/[^A-Za-z\.0-9\-_:]+/', '', $ip);
    }

    /**
     * Определяет, есть ли у пользователя права администратора
     *
     * Администратору системы всегда доступны все модули системы, разрешенные в ключе системы.
     *
     * @return bool TRUE, если администратор, и FALSE в ином случае.
     */
    public function isAdmin()
    {
        return $this->mAdmin == 1;
    }

	/**
     * Определяет, первый вход пользователя в систему или нет
     *
     * @return bool TRUE, если первый вход, и FALSE в ином случае.
     */
    public function isFirstLogin()
    {
        return $this->mFirstLogin == 1;
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

    /**
     * Возвращает идентификатор пользователя.
     *
     * @return int Идентификатор пользователя.
     */
    public function getUserId()
    {
        return $this->mId;
    }

    /**
     * Возвращает время последней смены пароля
     *
     * @return int
     */
    public function getPassDate()
    {
    	return $this->mPassDate;
    }

    /**
     * Возвращает логин пользователя.
     *
     * @return string Логин пользователя.
     */
    public function getUserLogin()
    {
        return $this->mLogin;
    }

    /**
     * Возвращает e-mail пользователя.
     *
     * @return string E-mail пользователя.
     */
    public function getUserEmail()
    {
        return $this->mEmail;
    }

    /**
     * Устанавливает логин пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param string $user_login Логин пользователя.
     *
     */
    public function setUserLogin($user_id, $user_login)
    {
        if ($this->mId == $user_id)
            $this->mLogin = $user_login;
    }

    /**
     * Устанавливает e-mail пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param string $user_email E-mail пользователя.
     *
     */
    public function setUserEmail($user_id, $user_email)
    {
        if ($this->mId == $user_id)
            $this->mEmail = $user_email;
    }

	/**
     * Устанавливает флаг первого входа пользователя в систему
     *
     * @param $first bool TRUE, если первый вход, и FALSE в ином случае.
     */
    public function setFirstLogin($first)
    {
        $this->mFirstLogin = $first;
    }

    /**
     * Возвращает идентификаторы групп, в которые входит пользователь.
     *
     * @return string Идентификаторы групп, в которые входит пользователь.
     */
    public function getUserGroups()
    {
        return $this->mCatIds;
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
		
		/* Для корректной обработки ситуации с прокси, когда несколько IP перечисляются через запятую */
		$ip_ar = explode(',', $ip_b);
		
		if(count($ip_ar) == 1){/* Если пришёл один IP, то сравниваем по-старому */
			$ip_b = explode('.', trim($ip_b));
			foreach ($ip_a as $key => $value)
			{
			    if ($value == '*')
			        continue;
			    if ($value != $ip_b[$key])
			        return false;
			}
		}else{ /* Если несколько IP, то для каждого запускаем проверку */
			foreach($ip_ar as $ip_b)
			{
				/* Сравниваем каждый IP и если хоть один совпадает, то возвращаем true */
				if(self::compareIP(implode('.', $ip_a), $ip_b))
					return true;
			}
			/* Если ни один IP не совпадает, то возвращаем false */
			return false;
		}

        return true;
    }
    /**
     * Сравнение текущего IP-адреса пользователя, браузера и идентификатора сессии с сохраненными при входе пользователя
     *
     * Если эти параметры не совпадают, то считаем, что имеет место нарушение безопасности системы. Записываем соотв.
     * предупреждение в переменную mError.
     *
     * @param string $user_ip IP-адрес пользователя.
     * @param string $user_browser Браузер пользователя.
     * @param integer $session_id Идентификатор сессии.
     */
    public function checkIP($user_ip, $user_browser, $session_id)
    {
		$user_browser = preg_replace('/\s*chromeframe(\/[0-9\.]+)?/i', '', $user_browser);
        if ($this->mIP != $user_ip || $this->mBrowser != $user_browser || $this->mSessionId != $session_id)
        {
            //безопасность системы под угрозой
            $this->mError = SB_AUTH_SECURITY_ERROR;
        }
    }

    /**
     * Продляет сессию пользователя через каждые 15 сек.
     *
     * Каждые 15 сек. система посылает запрос на сервер с целью продлить сессию пользователя.
     *
     * Данный метод принимает такой запрос, проверяет IP-адрес, браузер пользователя и идентификатор сессии. Если они не совпадают с теми,
     * которые пришли при старте сессии, значит есть попытка несанкционированного входа в систему. Иначе меняем время старта сессии.
     *
     * @param int $user_id Идентификатор пользователя.
     */
    public function updateSession()
    {
        // проверяем, есть ли сессия для данного пользователя
        $res = sql_query('SELECT users.u_admin, users.u_block, sess.us_ip, sess.us_browser, sess.us_sess_id , sess.us_id
                          FROM sb_users users, sb_users_sessions sess WHERE users.u_id=?d AND sess.us_user_id=users.u_id', $this->mId);

        if (!$res)
        {
            $this->mError = SB_AUTH_NO_USER_OR_SESSION;
            return;
        }

        list($this->mAdmin, $u_block, $u_ip, $u_browser, $u_sess_id, $us_id) = $res[0];

        if($u_block == 1)
        {
            // аккаунт заблокирован
            $this->mError = SB_AUTH_USER_BLOCKED;
 	        return;
        }

    	$this->checkKey();
        if ($this->mError != '')
        {
            // ключ системы неверный
            return;
        }

        $this->checkIP($u_ip, $u_browser, $u_sess_id);
        if ($this->mError != '')
        {
            // IP-адрес или браузер пользователя не совпадают с ID-сессии
            return;
        }

        // группа пользователя
        $this->mCatIds = array();
        $res = sql_query('SELECT DISTINCT links.link_cat_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND categs.cat_rubrik=1 AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_users', $this->mId);
        if ($res)
        {
            foreach ($res as $value)
            {
                $this->mCatIds[] = $value[0];
            }
        }
        else
        {
            $this->mError = SB_AUTH_CATEG_BLOCKED;
 	        return;
        }

        sql_query('UPDATE sb_users_sessions SET us_session_time=? WHERE us_id=?d', time(), $us_id);
    }

    /**
     * Продляет сессию пользователя при вызове события системы
     *
     * Перед запуском события системы нам необходимо проверить, не произошел ли таймаут сессии пользователя.
     * Данный метод проверяет IP-адрес, браузер пользователя и идентификатор сессии. Если они не совпадают с теми,
     * которые пришли при старте сессии, значит есть попытка несанкционированного входа в систему. Иначе проверяем,
     * не было ли таймаута сессии, и если таймаут не произошел, меняем время последней активности пользователя.
     *
     * @param integer $user_id - идентификатор пользователя
     */
    public function updateEventSession()
    {
        sql_query('UPDATE sb_users SET u_block = 1 WHERE u_active_date != 0 AND u_active_date < '.time());

        // проверяем, есть ли сессия для данного пользователя
        $res = sql_query('SELECT users.u_login, users.u_name, users.u_email, users.u_admin, users.u_block, users.u_domains, users.u_uploading_exts,
                          users.u_strip_php, users.u_pass_date, sess.us_ip, sess.us_browser, sess.us_sess_id , sess.us_active_time, sess.us_id, users.u_auto_login
                          FROM sb_users users, sb_users_sessions sess WHERE users.u_id=?d AND sess.us_user_id=users.u_id', $this->mId);

        if (!$res)
        {
            $this->mError = SB_AUTH_NO_USER_OR_SESSION;
            return;
        }
        list($this->mLogin, $this->mName, $this->mEmail, $this->mAdmin, $u_block, $u_domains, $this->mUploadingExts, $this->mStripPHP, $this->mPassDate, $u_ip, $u_browser, $u_sess_id, $u_active_time, $us_id, $u_auto_login) = $res[0];

        if($u_block == 1)
        {
            // аккаунт заблокирован
            $this->mError = SB_AUTH_USER_BLOCKED;
 	        return;
        }

    	$this->checkKey();
        if ($this->mError != '')
        {
            // ключ системы неверный
            return;
        }

        $this->checkIP($u_ip, $u_browser, $u_sess_id);
        if ($this->mError != '')
        {
            // IP-адрес или браузер пользователя не совпадают с ID-сессии
            return;
        }

        $this->mUploadingExts = explode(' ', $this->mUploadingExts);

        if ($this->mAdmin != 1)
        {
            $res = sql_param_query('SELECT c.cat_id FROM sb_categs c, sb_catlinks l
                         WHERE c.cat_closed=1 AND c.cat_ident=? AND l.link_cat_id=c.cat_id AND l.link_el_id=?d', 'pl_users', $this->mId);

            if ($res)
            {
                $this->mAdmin = 1;
            }
            else
            {
                $u_domains = explode(',', $u_domains);
                if (!in_array(SB_COOKIE_DOMAIN, $u_domains))
                {
                    // пользователь не может админить выбранный домен
                    $this->mError = sprintf(SB_AUTH_WRONG_DOMAIN, SB_COOKIE_DOMAIN);
                    return;
                }
            }
        }

        // группа пользователя
        $this->mCatIds = array();
        $res = sql_query('SELECT DISTINCT links.link_cat_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND categs.cat_rubrik=1 AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_users', $this->mId);
        if ($res)
        {
            foreach ($res as $value)
            {
                $this->mCatIds[] = $value[0];
            }
        }
        else
        {
            $this->mError = SB_AUTH_CATEG_BLOCKED;
 	        return;
        }

        // берем время таймаута сессии из настроек системы, если в настройках его нет, то 60 мин.
	    $timeout = sbPlugins::getSetting('sb_session_timeout');
		if (is_null($timeout) || $timeout <= 0)
		{
		    $timeout = 60;
		}

        if ($u_active_time < time() - 60 * $timeout && !(isset($_COOKIE['sb_auto_login']) && $_COOKIE['sb_auto_login'] == $u_auto_login))
        {
			$this->mError = SB_AUTH_TIMEOUT;
            return;
		}

        sql_query('UPDATE sb_users_sessions SET us_active_time=? WHERE us_id=?d', time(), $us_id);
    }

    /**
     * Аутентификация пользователя в системе
     *
     * Проверяет логин и пароль пользователя, стартует сессию пользователя.
     *
     * @param string $login Логин пользователя.
     * @param string $password Пароль пользователя.
     * @param strign $open_key Открытый ключ для шифрования пароля.
     */
    private function doLogin($login, $password, $open_key)
    {
    	$hash = '';
        $this->checkKey();
        if ($this->mError != '')
        {
        	// ключ системы неверный
            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
            return;
        }

        sql_query('UPDATE sb_users SET u_block = 1 WHERE u_active_date != 0 AND u_active_date < ?d', time());

        $res = sql_query('SELECT u_id, u_pass, u_name, u_email, u_admin, u_block, u_domains, u_uploading_exts, u_strip_php, u_last_date, u_pass_date FROM sb_users WHERE u_login=?', $login);

        if (!$res)
        {
            // пользователь с заданным логином не найден
            $this->mError = SB_AUTH_WRONG_LOGIN;
            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
            if (sb_strlen($login) > 10)
            {
            	$login = sb_substr($login, 0, 10).'... ';
            }

            sb_add_system_message(sprintf(SB_AUTH_USER_ENTER_ERROR_LOGIN_MSG, $login), SB_MSG_WARNING);
	        return;
        }

        $this->mLogin = $login;

        list($this->mId, $pass, $this->mName, $this->mEmail, $this->mAdmin, $u_block, $u_domains, $this->mUploadingExts, $this->mStripPHP, $u_last_date, $this->mPassDate) = $res[0];

        $this->mFirstLogin = ($u_last_date == 0);

        sql_query('DELETE FROM sb_editor WHERE e_u_id=?d', $this->mId);

        if($u_block == 1)
        {
            // аккаунт заблокирован
            $this->mError = SB_AUTH_USER_BLOCKED;
            sql_query('DELETE FROM sb_users_sessions WHERE us_user_id=?d', $this->mId);
            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
 	        return;
        }

        $pass = md5($pass.md5($open_key));
        if ($pass != $password)
        {
            // неверно указан пароль
            $this->mError = SB_AUTH_WRONG_PASSWORD;
            sb_add_system_message(sprintf(SB_AUTH_USER_ENTER_ERROR_PASSWORD_MSG, $login), SB_MSG_WARNING);

            if ($this->mAdmin != 1)
            {
            	$res = sql_query('SELECT u_wrong_pass_count FROM sb_users WHERE u_id=?d', $this->mId);
	            if ($res)
	            {
	            	list($u_wrong_pass_count) = $res[0];
	            	$u_wrong_pass_count++;
	            }
	            else
	            {
	            	$this->mError = SB_AUTH_USER_BLOCKED;
		            sql_query('DELETE FROM sb_users_sessions WHERE us_user_id=?d', $this->mId);
		            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
		 	        return;
	            }

            	// берем кол-во повторных вводов пароля из настроек системы, если в настройках его нет, то 10 раз
            	$wrong_pass_count = sbPlugins::getSetting('sb_wrong_pass_count');
		        if (is_null($wrong_pass_count) || intval($wrong_pass_count) == 0)
		        {
		            $wrong_pass_count = 10;
		        }

		        if ($u_wrong_pass_count >= $wrong_pass_count)
		        {
		        	// блокируем аккаунт
		        	sql_query('UPDATE sb_users SET u_block=1, u_wrong_pass_count = 0 WHERE u_id=?d', $this->mId);
		        	$this->mError = SB_AUTH_USER_BLOCKED;
		            sql_query('DELETE FROM sb_users_sessions WHERE us_user_id=?d', $this->mId);
		            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
		            sb_add_system_message(sprintf(SB_AUTH_WRONG_PASSWORD_BLOCK, $login), SB_MSG_WARNING);
		 	        return;
		        }
		        else
		        {
		        	$this->mError = sprintf(SB_AUTH_WRONG_PASSWORD_COUNT, $wrong_pass_count - $u_wrong_pass_count);
		        }

		        sql_query('UPDATE sb_users SET u_wrong_pass_count = ?d WHERE u_id=?d', $u_wrong_pass_count, $this->mId);
            }

 	        return;
        }

        if ($this->mAdmin != 1)
        {
            $res = sql_param_query('SELECT c.cat_id FROM sb_categs c, sb_catlinks l
                         WHERE c.cat_closed=1 AND c.cat_ident=? AND l.link_cat_id=c.cat_id AND l.link_el_id=?d', 'pl_users', $this->mId);

            if ($res)
            {
                $this->mAdmin = 1;
            }
            else
            {
                $u_domains = explode(',', $u_domains);
                if (!in_array(SB_COOKIE_DOMAIN, $u_domains))
                {
                    // пользователь не может админить выбранный домен
                    $this->mError = sprintf(SB_AUTH_WRONG_DOMAIN, SB_COOKIE_DOMAIN);
                    sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
                    return;
                }
            }
        }

        $availIPs = sbPlugins::getSetting('sb_admin_ip');
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
                //пытаемся зайти не с разрешенного IP-адреса
                $this->mError = SB_AUTH_IP_ERROR;
                sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
                sb_add_system_message(sprintf(SB_AUTH_USER_ENTER_ERROR_IP_MSG, $login, $this->mIP), SB_MSG_WARNING);
                return;
            }
        }

        $this->mUploadingExts = explode(' ', $this->mUploadingExts);

        // берем время таймаута сессии из настроек системы, если в настройках его нет, то 60 мин.
	    $timeout = sbPlugins::getSetting('sb_session_timeout');
		if (is_null($timeout) || $timeout <= 0)
		{
		    $timeout = 60;
		}

        // Удаляем сессии пользователей, время бездействия которых больше таймаута
        sql_query('DELETE FROM sb_users_sessions WHERE us_active_time < ?', intval(time() - 60 * $timeout));

        // Пытаемся начать новую сессию пользователя
        $res = sql_query('SELECT us_session_time FROM sb_users_sessions WHERE us_user_id = ?d', $this->mId);
        if ($res)
        {
            /**
             * Если пользователь с таким логином уже есть в системе, выводим соотв. сообщение
             */
            list($session_time) = $res[0];
            if ($session_time > time() - 30)
            {
                $this->mError = SB_AUTH_USER_ALREADY_LOGGED;
                sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
                return;
            }
            else
            {
                sql_query('DELETE FROM sb_users_sessions WHERE us_user_id = ?d', $this->mId);
            }
        }

        // группа пользователя
        $this->mCatIds = array();
        $res = sql_query('SELECT DISTINCT links.link_cat_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND categs.cat_rubrik=1 AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_users', $this->mId);
        if ($res)
        {
            foreach ($res as $value)
            {
                $this->mCatIds[] = $value[0];
            }
        }
        else
        {
            $this->mError = SB_AUTH_CATEG_BLOCKED;
            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
 	        return;
        }

        $res = sql_query('INSERT INTO sb_users_sessions (us_ip, us_browser, us_sess_id, us_user_id, us_session_time, us_active_time) VALUES (?, ?, ?, ?d, ?d, ?d)', $this->mIP, $this->mBrowser, @session_id(), $this->mId, time(), time());

        if (!$res)
        {
            $this->mError = KERNEL_NO_SESSIONS;
            sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');
 	        return;
        }
        $this->mLogin = $login;

        if (isset($_POST['sb_auto_login']) && $_POST['sb_auto_login'] == 1)
        {
        	$hash = md5(time().uniqid());
        }

        sb_setcookie('sb_auto_login', $hash, time() + 180 * 24 * 60 * 60, '/cms/admin/');

    	$first_change = sbPlugins::getSetting('sb_admin_first_change_pass');

    	$values = array('u_auto_login' => $hash,
                        'u_last_ip' => $this->mIP.(isset($_SERVER['REMOTE_PORT']) ? ':'.$_SERVER['REMOTE_PORT'] : ''),
                        'u_wrong_pass_count' => 0);

        if ($first_change == 0 || $u_last_date > 0)
        {
        	$values['u_last_date'] = time();
        }

        // выставляем дату последнего входа в систему и IP
        sql_query('UPDATE sb_users SET ?a WHERE u_id=?d', $values, $this->mId,
                        SB_AUTH_USER_ENTER_MSG.' <i>'.$this->mLogin.'</i>.');
    }

    /**
     * Закрывает сессию пользователя
     *
     * @param bool $only_user_sess Уничтожает только пользовательскую сессию.
     */
	public function doLogout($only_user_sess=false)
    {
        /**
         * Пользователь вышел из системы
         */
        $res = sql_query('SELECT u_login FROM sb_users WHERE u_id=?d', $this->mId);
        if ($res)
        {
			list($u_login) = $res[0];

            sql_query('UPDATE sb_users SET u_auto_login = "" WHERE u_id=?d', $this->mId);
            sql_query('DELETE FROM sb_users_sessions WHERE us_user_id=?d AND us_sess_id=?', $this->mId, session_id());
            sb_add_system_message(sprintf(SB_AUTH_LOGOUT, '<i>'.$u_login.'</i>'), SB_MSG_INFORMATION);
		}

		sb_setcookie('sb_auto_login', '', time() + 180 * 24 * 60 * 60, '/cms/admin/');

        if (!$only_user_sess)
        {
            $_SESSION = array();
            if (isset($_COOKIE[session_name()]))
            {
                sb_setcookie(session_name(), '');
                session_destroy();
            }
        }
    }

    /**
     * Проверка ключа системы
     *
     * @ignore
     */
    private function checkKey()
    {
    	if (count($this->mKeyInfo) > 0)
    	{
    		define('SB_CLIENT_VALIDITY', $this->mKeyInfo['validity']);
		    define('SB_CLIENT_LIC', $this->mKeyInfo['lic']);
		    define('SB_CLIENT_NAME', $this->mKeyInfo['name']);
		    define('SB_DEALER', $this->mKeyInfo['dealer']);
    		define('SB_MAIN_DOMAIN', $this->mKeyInfo['domain']);

	    	if (SB_CLIENT_VALIDITY != -1 && SB_CLIENT_VALIDITY < time())
	        {
	            // срок действия ключа истек
	            $this->mError = SB_AUTH_LIC_EXPIRED;
	        }

    		return;
    	}

        if (!file_exists(SB_CMS_KERNEL_PATH.'/key.php'))
        {
            // файл key.php отсутствует
            $this->mError = SB_AUTH_NO_KEY;
            return;
        }

        require_once(SB_CMS_LIB_PATH.'/sbDB.inc.php');

        $error = sbKeyCheck($this->mPlugins);

        if ($error != '')
        {
            $this->mError = $error;
            return;
        }

        if (SB_CLIENT_VALIDITY != -1 && SB_CLIENT_VALIDITY < time())
        {
            // срок действия ключа истек
            $this->mError = SB_AUTH_LIC_EXPIRED;
            return;
        }

        $this->mKeyInfo['validity'] = SB_CLIENT_VALIDITY;
        $this->mKeyInfo['lic'] = SB_CLIENT_LIC;
        $this->mKeyInfo['name'] = SB_CLIENT_NAME;
        $this->mKeyInfo['dealer'] = SB_DEALER;
        $this->mKeyInfo['domain'] = SB_MAIN_DOMAIN;
    }

    /**
     * Проверка модуля на наличие в ключе системы
     *
     * @ignore
     */
    public function checkPlugin($plugin)
    {
        $key = SB_CLIENT_VALIDITY.'(w)'.SB_CLIENT_LIC.'#*'.$plugin.'%@Dc';
        $key = md5($key);

        if(empty($this->mPlugins))
        {
        	require_once(SB_CMS_LIB_PATH.'/sbDB.inc.php');

			$error = sbKeyCheck($this->mPlugins);

        	if ($error != '')
        	{
	            $this->mError = $error;
				return;
			}
		}

		if (!in_array($key, $this->mPlugins))
            return false;
        else
            return true;
    }

    /**
     * Возвращает массив расширений файлов, разрешенных для загрузки на сервер пользователем
     *
     * @return array
     */
    public function getUploadingExts()
    {
        return $this->mUploadingExts;
    }

    /**
     * Возвращает TRUE, если необходимо вырезать PHP-код из REQUEST-данных пользователя, и FALSE в ином случае
     *
     * @return bool
     */
    public function checkStripPHP()
    {
        if ($this->isAdmin())
            return false;

		return ($this->mStripPHP == 1);
	}

	/**
     * Возвращает права текущего пользователя для полей.
     *
     * @param string $pl_ident Идентификатор модуля.
     * @param array $fields Массив с именами полей для которых нужно проверить права.
     * @param array $rights Массив с названиями прав, на которые нужно проверить(view, edit).
     * @param bool $categs Флаг. Поле для разделов или для элементов.
     * @param array $fields_data Массив данных пользовательских полей.
	 *
     * @return mixed Массив
	 *               $return['название поля']['название права'] = 1 или 0;
	 *               FALSE в случае неудачи.
     */
	public function getFieldRight($pl_ident, $fields, $rights, $categs = false, $fields_data = array())
	{
		$return = array();
		// если пользователь администратор, то для всех полей возвращаем 1 (имеет права)
		if($this->isAdmin())
		{
			foreach($fields as $key => $value)
			{
				foreach($rights as $ke => $val)
				{
					if(!isset($return[$value]))
					{
						$return[$value] = array();
					}
					$return[$value][$val] = 1;
				}
			}
			return $return;
		}

//		если нет массива данных полей, то достаем его из базы.
		if(empty($fields_data))
		{
			$res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $pl_ident);
			if($res)
			{
				$fields_data = (isset($res[0][0]) && $res[0][0] != '' ? unserialize($res[0][0]) : array());
			}
			else
			{
				//	нет полей.
				return false;
			}
		}

		$user_id = 'u'.$this->mId;
		$user_groups = $this->mCatIds;
		foreach($user_groups as $key => $value)
		{
			$user_groups[$key] = 'g'.$value;
		}

		foreach($fields_data as $key => $value)
		{
			//	если поля нет в списке проверяемых, то не проверяем его.
			if(!in_array('user_f_'.$value['id'], $fields))
			{
				continue;
			}

			//	если для поля не нужно проверять права, то устанавливаем для всех его прав значения 1 (имеет права).
			if(!isset($value['rights_set']) || $value['rights_set'] != 1)
			{
				if(!isset($return['user_f_'.$value['id']]))
				{
					$return['user_f_'.$value['id']] = array();
				}
				foreach($rights as $ke => $val)
				{
					$return['user_f_'.$value['id']][$val] = 1;
				}
			}
			elseif(isset($value['rights_set']) && $value['rights_set'] == 1)
			{
				if(!isset($return['user_f_'.$value['id']]))
				{
					$return['user_f_'.$value['id']] = array();
				}

				foreach($rights as $ke => $val)
				{
					$users = explode('^', $value['rights_'.$val.'_list']);
					if(!in_array($user_id, $users))
					{
						if(!array_intersect($users, $user_groups))
						{
							$return['user_f_'.$value['id']][$val] = 0;
							continue;
						}
					}
					$return['user_f_'.$value['id']][$val] = 1;
				}
			}
		}
		return $return;
	}
}

?>