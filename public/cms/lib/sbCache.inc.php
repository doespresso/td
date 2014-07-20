<?php
/**
 * Реализация системы кеширования на сайте
 *
 * @author Сергей Черячукин <sc@binn.ru>
 * @package SB_Cache
 */

/**
 * Интерфейс, описывающий функциональность кеширования
 *
 * sbICache
 * @package SB_Cache
 */
interface sbICache
{
    /**
     * Проверяет, есть ли кэш для переданных параметров
     *
     * @param string $plugin_ident Идентификатор модуля, для которого проверяется кэш.
     * @param int $tag_id Идентификатор тега на странице, с которым связан кэшируемый элемент.
     * @param array $params Массив параметров, от которых зависит вывод элемента.
     * @param bool $use_session Добавлять или нет к массиву идентификатор зарегистрированного пользователя.
     *
     * @return bool TRUE или идентификатор элемента, если кэш есть и он валиден, FALSE в ином случае.
     */
    function check($plugin_ident, $tag_id, $params, $use_session=true);

    /**
     * Выполняет переданную строку как PHP-код и сохраняет результат в кэш
     *
     * @param string $plugin_ident Идентификатор модуля.
     * @param string $str Строка, которую необходимо сохранить в кэше.
     * @param string $el_id Идентификатор элемента.
     */
    function save($plugin_ident, $str, $el_id = '');

    /**
     * Сбрасывает кэш для указанного модуля и домена
     *
     * Если домен не указан, то берется текущий домен. Если в качестве идентификатора модуля указать 'all',
     * то будет сброшен весь кэш.
     *
     * @param string $plugin_ident Идентифкатор модуля.
     * @param string $domain Домен, для которого скидывается кэш.
     *
     * @return bool TRUE, если кэш был сброшен успешно, FALSE в ином случае.
     */
    function drop($plugin_ident, $domain = SB_COOKIE_DOMAIN);

    /**
     * Возвращает время последней модификации страницы
     *
     * @return int Время последней модификации страницы
     */
    function getLastModified();

    /**
     * Устанавливает время последней модификации страницы
     *
     * @param timestamp $time Время последней модификации страницы.
     *
     * @return int Время последней модификации страницы
     */
    function setLastModified($time);
}

/**
 * Класс, определяет кеширующий класс (фабрика)
 *
 * @package SB_Cache
 */
class sbCache
{
    static function factory()
    {
        if (class_exists('Memcache') && sbPlugins::getSetting('sb_use_cache') == SB_CACHE_MEMCACHE)
        {
            $cache = new sbMemcache();

            /**
             * Если сервер MemCache отключен или "упал", то используем кеширование
             * в базе данных
             */
            if($cache->isConnected())
            {
                return $cache;
            }

            return new sbDbCache();
        }
        else
        {
            return new sbDbCache();
        }
    }
}


/**
 * Класс, отвечающий за кэширование на сайте
 *
 * Данный класс использует при работе библиотеку работы с файловой системой.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Cache
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbDbCache implements sbICache
{
    /**
     * Время последнего изменения страницы
     *
     * @var int
     */
    private $mLastModified = -1;

    /**
     * Идентификатор кэша
     *
     * @var int
     */
    private $mHash = null;

    /**
     * Массив идентификаторов кэша (когда функция check вызывается из разных модулей вложенным функциями)
     *
     * @var array
     */
    private $mHashAr = array();

    /**
     * Контрольная сумма кэша
     *
     * @var int
     */
    private $mCrc = -1;

    /**
     * Ассициативный массив с настройками кэширования для модулей текущего домена
     *
     * Ключ массива - идентификатор модуля, значение - время в минутах валидности кэша для модуля.
     *
     * @var array
     */
    public $mSettings = array();

    /**
     * Временно отключает кэш
     *
     * @var bool
     */
    public $mCacheOff = false;

    /**
     * Включен ли кэш для домена или нет
     *
     * @var bool
     */
    private $mCacheOn = false;

    /**
     * Конструктор класса
     *
     * Считывает настройки кэширования на сайте для текущего домена.
     *
     * @return object sbCache
     */
    public function __construct()
    {
        // считываем настройки кэширования для модулей домена
        $res = sql_query('SELECT ident, time FROM sb_cache_settings WHERE domain=?', SB_COOKIE_DOMAIN);
        if ($res)
        {
            foreach ($res as $value)
            {
                $this->mSettings[$value[0]] = $value[1];
            }
        }

        $this->mCacheOn = (sbPlugins::getSetting('sb_use_cache') == SB_CACHE_DB);
    }

    /**
     * Проверяет, есть ли кэш для переданных параметров
     *
     * @param string $plugin_ident Идентификатор модуля, для которого проверяется кэш.
     * @param int $tag_id Идентификатор тега на странице, с которым связан кэшируемый элемент.
     * @param array $params Массив параметров, от которых зависит вывод элемента.
     * @param bool $use_session Добавлять или нет к массиву идентификатор зарегистрированного пользователя.
     *
     * @return bool TRUE или идентификатор элемента, если кэш есть и он валиден, FALSE в ином случае.
     */
    public function check($plugin_ident, $tag_id, $params, $use_session=true)
    {
    	if (!is_null($this->mHash))
        {
            array_push($this->mHashAr, $this->mHash);
        }

    	$this->mHash = null;

        if (!$this->mCacheOn || $_SERVER['PHP_SELF'] == '/cms/admin/editor.php' || isset($_REQUEST['sb_page_nocache']) || (defined('SB_PAGE_NOCACHE') && SB_PAGE_NOCACHE))
            return false;

        $this->mCrc = -1;

        $plugin_ident = preg_replace('/[^0-9a-zA-Z\-_]+/', '', $plugin_ident);
        $tag_id = intval($tag_id);

        if (!isset($this->mSettings[$plugin_ident]))
        	$this->mSettings[$plugin_ident] = 30;

        if ($this->mSettings[$plugin_ident] <= 0)
        {
            $this->mLastModified = time();
            array_push($this->mHashAr, null);
            return false;
        }

        //Формируем хэш в зависимости от настроек
        $url = SB_COOKIE_DOMAIN.$_SERVER['PHP_SELF'];
        $gets = serialize($_GET);
        $hashSettings = array();
        if(isset($params[2]) && is_string($params[2]))
        {
            $hashSettings = unserialize(sb_str_replace('\"', '"',$params[2]));
        }

        if(isset($hashSettings['use_component_cache']) && $hashSettings['use_component_cache'] == 1)
        {
        	array_push($this->mHashAr, null);
        	return false;
        }

        if (isset($hashSettings['use_component_cache']) && $hashSettings['use_component_cache'] == 0)
        {
            if (isset($hashSettings['cache_not_url']) && $hashSettings['cache_not_url'] == 1)
            {
                $url = '';
            }
            if (isset($hashSettings['cache_not_get']) && $hashSettings['cache_not_get'] == 1)
            {
                $hashSettings['cache_not_get_list'] = explode(' ', sb_strtolower($hashSettings['cache_not_get_list']));
                $gets                               = array();
                foreach ($_GET as $key => $val)
                {
                    if (in_array($key, $hashSettings['cache_not_get_list']))
                    {
                        $gets[$key] = $val;
                    }
                }
                $gets       = serialize($gets);
            }
        }

        $hash = $url.$plugin_ident.$tag_id.$gets.serialize($params);

        $cookie = sbPlugins::getSetting('sb_use_cache_cookie');
        if (trim($cookie) != '')
        {
            $cookie = explode(',', $cookie);

            foreach ($cookie as $val)
            {
                $val = trim($val);
                if (isset($_COOKIE[$val]))
                {
                    $hash .= serialize($_COOKIE[$val]);
                }
                else
                {
                    return false;
                }
            }
        }

        if ($use_session)
            $hash .= (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : '');

        $this->mHash = sb_crc($hash);

        $res = sql_query('SELECT c_crc, c_time, c_content, c_last_modified, c_el_id FROM sb_cache WHERE c_ident=? AND c_domain=? AND c_plugin_ident=?', $this->mHash, SB_KEY_DOMAIN, $plugin_ident);
    	if (!$res)
        {
            return false;
        }

        list($this->mCrc, $time, $content, $last_modified, $el_id) = $res[0];

        if ($el_id > 0 && isset($_REQUEST['c_hash']) && $_REQUEST['c_hash'] == md5($plugin_ident.' - '.$el_id))
        {
        	return false;
        }

        if ($last_modified > $this->mLastModified)
        {
                $this->mLastModified = $last_modified;
        }

        if ((time() - $time) > (intval($this->mSettings[$plugin_ident]) * 60))
        {
            // кэш истек
            return false;
        }

        if (@function_exists('gzuncompress'))
        {
        	$content = gzuncompress($content);
        }

        echo $content;

        $this->mHash = array_pop($this->mHashAr);

        if (is_null($el_id))
        {
        	return true;
        }

        return $el_id;
    }

    /**
     * Выполняет переданную строку как PHP-код и сохраняет результат в кэш
     *
     * @param string $plugin_ident Идентификатор модуля.
     * @param string $str Строка, которую необходимо сохранить в кэше.
     * @param string $el_id Идентификатор элемента.
     */
    public function save($plugin_ident, $str, $el_id = '')
    {
    	ob_start();

    	//чистим код от инъекций
    	$str = sb_clean_string($str);

        eval(' ?>'.$str.'<?php ');
        $str = ob_get_flush();

        if ($this->mCacheOff)
        {
        	$this->mLastModified = time();
        	$this->mCacheOff = false;
        	$this->mHash = array_pop($this->mHashAr);
        	return;
        }

    	if (is_null($this->mHash))
        {
            $this->mHash = array_pop($this->mHashAr);
        	return;
        }

        $plugin_ident = preg_replace('/[^0-9a-zA-Z\-_]+/', '', $plugin_ident);
        $time = time();

        $crc = sb_crc($str);
        if ($crc == $this->mCrc)
        {
        	// содержимое не изменилось
        	sql_query('UPDATE sb_cache SET c_time=? WHERE c_ident=? AND c_domain=? AND c_plugin_ident=?', $time, $this->mHash, SB_KEY_DOMAIN, $plugin_ident);

        	$this->mHash = array_pop($this->mHashAr);
        	return;
        }

        // содержимое изменилось
        $row = array();
        $row['c_crc'] = $crc;
        $row['c_last_modified'] = $time;
        $row['c_content'] = @function_exists('gzcompress') ? gzcompress($str, 3) : $str;
        $row['c_time'] = $time;
        $row['c_el_id'] = $el_id != '' ? $el_id : null;

        $this->mLastModified = $time;

        // удаляем кэш, который не запрашивался за последние 180 дней
        sql_query('DELETE FROM sb_cache WHERE c_time < '.($time - 180 * 24 * 60 * 60));

        $res = sql_query('SELECT COUNT(*) FROM sb_cache WHERE c_ident=? AND c_domain=? AND c_plugin_ident=?', $this->mHash, SB_KEY_DOMAIN, $plugin_ident);

        if (!$res || $res[0][0] == 0)
        {
        	$row['c_ident'] = $this->mHash;
        	$row['c_plugin_ident'] = $plugin_ident;
        	$row['c_domain'] = SB_KEY_DOMAIN;

        	sql_query('INSERT INTO sb_cache (?#) VALUES (?a)', array_keys($row), array_values($row));

        	$this->mHash = array_pop($this->mHashAr);
        	return;
        }

        sql_query('UPDATE sb_cache SET ?a WHERE c_ident=? AND c_domain=? AND c_plugin_ident=?', $row, $this->mHash, SB_KEY_DOMAIN, $plugin_ident);

        $this->mHash = array_pop($this->mHashAr);
    }

    /**
     * Сбрасывает кэш для указанного модуля и домена
     *
     * Если домен не указан, то берется текущий домен. Если в качестве идентификатора модуля указать 'all',
     * то будет сброшен весь кэш.
     *
     * @param string $plugin_ident Идентифкатор модуля.
     * @param string $domain Домен, для которого скидывается кэш.
     *
     * @return bool TRUE, если кэш был сброшен успешно, FALSE в ином случае.
     */
    public function drop($plugin_ident, $domain = SB_COOKIE_DOMAIN)
    {
        $domain = preg_replace('/[^0-9a-zA-Z\-_\.]+/', '', $domain);

        if ($domain == '')
            return false;

        if ($plugin_ident == 'all')
        {
        	if (sql_query('DELETE FROM sb_cache WHERE c_domain=?', $domain))
            {
                sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_ALL, $domain));
                return true;
            }
            else
            {
                sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_ALL_ERROR, $domain));
            }
        }
        else
        {
            $plugin_ident = preg_replace('/[^0-9a-zA-Z\-_]+/', '', $plugin_ident);

            $plugin_title = '';
            if (isset($_SESSION['sbPlugins']))
            {
                $plugins = $_SESSION['sbPlugins']->getPluginsInfo();

                if (isset($plugins[$plugin_ident]))
                {
                    $plugin_title = $plugins[$plugin_ident]['title'];
                }
            }

            if (sql_query('DELETE FROM sb_cache WHERE c_domain=? AND c_plugin_ident=?', $domain, $plugin_ident))
            {
            	if ($plugin_title != '')
            	{
            		sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_PLUGIN, $plugin_title, $domain));
            	}

                return true;
            }
            else
            {
                if ($plugin_title != '')
                {
                	sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_PLUGIN_ERROR, $plugin_title, $domain));
                }
            }
        }

        return false;
    }

    /**
     * Возвращает время последней модификации страницы
     *
     * @return int Время последней модификации страницы
     */
    public function getLastModified()
    {
        return ($this->mLastModified != -1 ? $this->mLastModified : time());
    }

	/**
     * Устанавливает время последней модификации страницы
     *
     * @param timestamp $time Время последней модификации страницы.
     *
     * @return int Время последней модификации страницы
     */
    public function setLastModified($time)
    {
        $this->mLastModified = $time;
    }
}

/**
 * Реализация класса, отвечающего за кэширование на сайте
 *
 * Данный класс использует при работе библиотеку MemCache.
 *
 * @author Артур Фурса <art@binn.ru>
 * @version 4.0
 * @package SB_Cache
 * @copyright Copyright (c) 2012, OOO "БИНН"
 */

class sbMemcache implements sbICache
{
	/**
     * Время последнего изменения страницы
     *
     * @var int
     */
    private $mLastModified = -1;

    /**
     * Идентификатор кэша
     *
     * @var int
     */
    private $mHash = null;

    /**
     * Массив идентификаторов кэша (когда функция check вызывается из разных модулей вложенным функциями)
     *
     * @var array
     */
    private $mHashAr = array();

    /**
     * Контрольная сумма кэша
     *
     * @var int
     */
    private $mCrc = -1;

    /*
     * Время последнего изменения кэша
     *
     * @var int
     */
    private $mCacheLastModified = -1;

    /**
     * Ассоциативный массив с настройками кэширования для модулей текущего домена
     *
     * Ключ массива - идентификатор модуля, значение - время в минутах валидности кэша для модуля.
     *
     * @var array
     */
    public $mSettings = array();

    /**
     * Включен ли кэш для домена или нет
     *
     * @var bool
     */
    private $mCacheOn = false;

	/**
	 * Экземпляр класса Memcache
	 */
    private $memcache;

    /**
     * Временно отключает кэш
     *
     * @var bool
     */
    public $mCacheOff = false;

    /**
     * Состояние подключения к серверу MemCache. (true - есть подключение, false - не подключен)
     *
     * @var bool
     */
    private $connection;

    /**
     * Конструктор класса
     *
     * Считывает настройки кэширования на сайте для текущего домена.
     *
     * @return object sbMemCache
     */
    public function __construct()
    {
        // считываем настройки кэширования для модулей домена
        $res = sql_query('SELECT ident, time FROM sb_cache_settings WHERE domain=?', SB_COOKIE_DOMAIN);
        if ($res)
        {
            foreach ($res as $value)
            {
                $this->mSettings[$value[0]] = $value[1];
            }
        }

        $this->mCacheOn = (sbPlugins::getSetting('sb_use_cache') == SB_CACHE_MEMCACHE);

		//инициализируем соединение
		$this->memcache = new Memcache;
		$this->connection = $this->memcache->pconnect(sbPlugins::getSetting('sb_use_mcache_host'), sbPlugins::getSetting('sb_use_mcache_port'));
		$this->memcache->setCompressThreshold(1000000, 0);
    }

	/**
	 * Получаем список всех ключей
	 */
	private function getMemcacheKeys()
	{
		$list = array();
		$slabs = $this->memcache->getExtendedStats('slabs');

		foreach($slabs as $slab)
		{
			foreach($slab as $slab_id => $slab_meta)
			{
				$slab_id = intval($slab_id);

				if($slab_id != 0)
				{
					$cdump = $this->memcache->getExtendedStats('cachedump', $slab_id);
					foreach($cdump as $val)
					{
						if (!is_array($val))
							continue;

						foreach($val as $k => $v)
						{
							$list[] = $k;
						}
					}
				}
			}
		}

		return $list;
	}

    /**
     * Проверяет, есть ли кэш для переданных параметров
     *
     * @param string $plugin_ident Идентификатор модуля, для которого проверяется кэш.
     * @param int $tag_id Идентификатор тега на странице, с которым связан кэшируемый элемент.
     * @param array $params Массив параметров, от которых зависит вывод элемента.
     * @param bool $use_session Добавлять или нет к массиву идентификатор зарегистрированного пользователя.
     *
     * @return bool TRUE или идентификатор элемента, если кэш есть и он валиден, FALSE в ином случае.
     */
    public function check($plugin_ident, $tag_id, $params, $use_session=true)
    {
        if (!is_null($this->mHash))
        {
            array_push($this->mHashAr, $this->mHash);
        }

    	$this->mHash = null;

        if (!$this->mCacheOn || $_SERVER['PHP_SELF'] == '/cms/admin/editor.php' || isset($_REQUEST['sb_page_nocache']) || (defined('SB_PAGE_NOCACHE') && SB_PAGE_NOCACHE))
            return false;

        $this->mCrc = -1;

        $plugin_ident = preg_replace('/[^0-9a-zA-Z\-_]+/', '', $plugin_ident);
        $tag_id = intval($tag_id);

        if (!isset($this->mSettings[$plugin_ident]))
        	$this->mSettings[$plugin_ident] = 30;

        if ($this->mSettings[$plugin_ident] <= 0)
        {
            $this->mLastModified = time();
            array_push($this->mHashAr, null);
            return false;
        }

        $hashSettings = array();
        if(isset($params[2]))
        {
        	if (is_string($params[2]))
        		$hashSettings = unserialize(stripslashes($params[2]));
        	elseif (is_array($params[2]))
            	$hashSettings = $params[2];
        }

        if(isset($hashSettings['use_component_cache']) && $hashSettings['use_component_cache'] == 1)
        {
        	array_push($this->mHashAr, null);
            return false;
        }

        //Формируем хэш в зависимости от настроек
        $url = SB_COOKIE_DOMAIN.$_SERVER['PHP_SELF'];
        $gets = serialize($_GET);

        if (isset($hashSettings['use_component_cache']) && $hashSettings['use_component_cache'] == 0)
        {
            if (isset($hashSettings['cache_not_url']) && $hashSettings['cache_not_url'] == 1)
            {
                $url = '';
            }
            if (isset($hashSettings['cache_not_get']) && $hashSettings['cache_not_get'] == 1)
            {
                $hashSettings['cache_not_get_list'] = explode(' ', sb_strtolower($hashSettings['cache_not_get_list']));
                $gets                               = array();
                foreach ($_GET as $key => $val)
                {
                    if (in_array($key, $hashSettings['cache_not_get_list']))
                    {
                        $gets[$key] = $val;
                    }
                }
                $gets = serialize($gets);
            }
        }

        $hash = $url.$plugin_ident.$tag_id.$gets.serialize($params);

        $cookie = sbPlugins::getSetting('sb_use_cache_cookie');
        if (trim($cookie) != '')
        {
            $cookie = explode(',', $cookie);

            foreach ($cookie as $val)
            {
                $val = trim($val);
                if (isset($_COOKIE[$val]))
                {
                    $hash .= serialize($_COOKIE[$val]);
                }
                else
                {
                    return false;
                }
            }
        }

        if ($use_session)
            $hash .= (isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth']->getUserId() : '');

        $this->mHash = $plugin_ident.'|'.sb_crc($hash);

		$cache_data = $this->memcache->get($this->mHash, (sbPlugins::getSetting('sb_use_mcache_zip') == 1 ? MEMCACHE_COMPRESSED : false));

		if (!is_array($cache_data))
        {
            return false;
        }

        $this->mCrc = $cache_data['crc'];
        $time = $cache_data['time'];
        $content = $cache_data['content'];
        $last_modified = $this->mCacheLastModified = $cache_data['last_modified'];
        $el_id = $cache_data['el_id'];

        if ($el_id > 0 && isset($_REQUEST['c_hash']) && $_REQUEST['c_hash'] == md5($plugin_ident.' - '.$el_id))
        {
        	return false;
        }

        if ($last_modified > $this->mLastModified)
        {
                $this->mLastModified = $last_modified;
        }

        if ((time() - $time) > (intval($this->mSettings[$plugin_ident]) * 60))
        {
            // кэш истек
            return false;
        }

        echo $content;

        $this->mHash = array_pop($this->mHashAr);

        if (is_null($el_id))
        {
        	return true;
        }

        return $el_id;
    }

    /**
     * Выполняет переданную строку как PHP-код и сохраняет результат в кэш
     *
     * @param string $plugin_ident Идентификатор модуля.
     * @param string $str Строка, которую необходимо сохранить в кэше.
     * @param string $el_id Идентификатор элемента.
     */
    public function save($plugin_ident, $str, $el_id = '')
    {
    	ob_start();

    	//чистим код от инъекций
    	$str = sb_clean_string($str);

		eval(' ?>'.$str.'<?php ');
        $str = ob_get_flush();

        if ($this->mCacheOff)
        {
        	$this->mLastModified = time();
        	$this->mCacheOff = false;
        	$this->mHash = array_pop($this->mHashAr);

        	return;
        }

        if (is_null($this->mHash))
        {
            $this->mHash = array_pop($this->mHashAr);
        	return;
        }

        $plugin_ident = preg_replace('/[^0-9a-zA-Z\-_]+/', '', $plugin_ident);
        $time = time();

    	$crc = sb_crc($str);

    	$data = array(
            'el_id' => $el_id != '' ? $el_id : null,
            'content' => $str,
            'last_modified' => $this->mCacheLastModified,
        	'time' => $time,
        	'crc' => $crc
        );

        if ($crc == $this->mCrc)
        {
        	// содержимое не изменилось
        	//$this->memcache->replace($this->mHash, $data, (sbPlugins::getSetting('sb_use_mcache_zip') == 1 ? MEMCACHE_COMPRESSED : false), 0);
            $this->mHash = array_pop($this->mHashAr);

        	return;
        }

        $data['last_modified'] = $time;
		$this->mLastModified = $time;

		$this->memcache->set($this->mHash, $data, (sbPlugins::getSetting('sb_use_mcache_zip') == 1 ? MEMCACHE_COMPRESSED : false), 0);
        $this->mHash = array_pop($this->mHashAr);
    }

    /**
     * Сбрасывает кэш для указанного модуля
     *
     * Если в качестве идентификатора модуля указать 'all',
     * то будет сброшен весь кэш.
     *
     * @param string $plugin_ident Идентифкатор модуля.
     * @param string $domain Домен, для которого скидывается кэш.
     *
     * @return bool TRUE, если кэш был сброшен успешно, FALSE в ином случае.
     */
    public function drop($plugin_ident, $domain = SB_COOKIE_DOMAIN)
    {
        $domain = preg_replace('/[^0-9a-zA-Z\-_\.]+/', '', $domain);

        if ($domain == '')
            return false;

        if ($plugin_ident == 'all')
        {
        	if ($this->memcache->flush()) //очищаем весь кеш
            {
                sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_ALL, $domain));
                return true;
            }
            else
            {
                sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_ALL_ERROR, $domain));
            }
        }
        else
        {
            $plugin_ident = preg_replace('/[^0-9a-zA-Z\-_]+/', '', $plugin_ident);

            $plugin_title = '';
            if (isset($_SESSION['sbPlugins']))
            {
                $plugins = $_SESSION['sbPlugins']->getPluginsInfo();

                if (isset($plugins[$plugin_ident]))
                {
                    $plugin_title = $plugins[$plugin_ident]['title'];
                }
            }

			//получаем список всех ключей из кеша
			$list = $this->getMemcacheKeys();

			if (count($list) > 0)
			{
				foreach ($list as $key)
				{
					$key = trim($key);

					if (sb_strpos($key, $plugin_ident.'|') === 0)
					{
					    $this->memcache->delete($key);
					}
				}
			}

			if ($plugin_title != '')
        	{
        		sb_add_system_message(sprintf(SB_CACHE_LOG_DROP_PLUGIN, $plugin_title, $domain));
        	}


            return true;
        }

        return false;
    }

    /**
     * Возвращает время последней модификации страницы
     *
     * @return int Время последней модификации страницы
     */
    public function getLastModified()
    {
        return ($this->mLastModified != -1 ? $this->mLastModified : time());
    }

	/**
     * Устанавливает время последней модификации страницы
     *
     * @param timestamp $time Время последней модификации страницы.
     *
     * @return int Время последней модификации страницы
     */
    public function setLastModified($time)
    {
        $this->mLastModified = $time;
    }

    /**
     * Проверка доступности сервера MemCache
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connection;
    }
}

$GLOBALS['sbCache'] = sbCache::factory();
?>