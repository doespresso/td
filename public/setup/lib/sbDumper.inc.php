<?php
/**
 * Реализация класса, отвечающего за создание или восстановления дампа базы данных
 *
 * @author Сергей Болотаев <sergey@binn.ru>
 * @version 4.0
 * @package SB_DB
 * @copyright Copyright (c) 2008, OOO "СИБИЭС Групп"
 */

define ('SB_DUMPER_ERROR_OPEN_IN_FILE', 'Ошибка! Не удалось открыть файл <i>%s</i>.');

/**
 * Класс, отвечающий за создание или восстановления дампа базы данных
 *
 * @author Сергей Болотаев <sergey@binn.ru>
 * @version 4.0
 * @package SB_DB
 * @copyright Copyright (c) 2008, OOO "СИБИЭС Групп"
 */
class sbDumper
{
	/**
    * В случае возникновения ошибки в эту переменную записывается текст ошибки.
    *
    * @var string
    */
	public $mError = '';

	/**
    * Код ошибки
    * 
    * @var int
    */
	public $mErrno = 0;
	
    /**
     * Идентификатор активного соединения с MySQL
     *
     * Хранит идентификатор последнего соединения с БД, возвращенный функцией mysql_connect() в методе connect().
     *
     * @var resource
     */
    public $mLinkId   = false;

    /**
     * Идентифиактор последнего запроса к MySQL
     *
     * Хранит идентификатор последнего запроса к БД, возвращенный функцией mysql_query() в различных методах класса.
     *
     * @var resource
     */
    public $mQueryId  = false;
	
    /**
     * Доступна ли библиотека mySQLi
     *
     * @var bool
     */
    private $mMysqli = false;
    
	/**
	 * Конструктор класса
	 *
	 * @param string $host Хост для подключения в БД.
	 * @param string $database Имя БД.
	 * @param string $user Пользователь для подключения к БД.
	 * @param string $password Пароль для подключения к БД.
	 */
	public function __construct($host, $database, $user, $password)
	{
		$this->mMysqli = false;
		if (extension_loaded('mysqli'))
		{
			@ini_set('zend.ze1_compatibility_mode', 0);
			if (ini_get('zend.ze1_compatibility_mode') == 0 || strtoupper(ini_get('zend.ze1_compatibility_mode')) == 'OFF')
		    	$this->mMysqli = true;
		}
		
		$this->mLinkId = false;
        /**
         * Если не задана база данных, хост или пользователь,
         * то выводим ошибку и возвращаем FALSE
         */
        if ( '' == $database || '' == $host || '' == $user )
        {
            return false;
        }
	        
		if ($this->mMysqli)
		{
	        /**
	         * Пытаемся установить соединение с БД и, в случае успеха, записываем объект соединения
	         * в переменную класса $mLinkId
	         */
	        $host = explode(':', $host);
	        if (count($host) > 1)
	        {
	        	$port = $host[1];
	            $host = $host[0];
	            $this->mLinkId = @mysqli_connect($host, $user, $password, $database, $port);
	        }
	        else
	        {
	            $host = $host[0];
	            $this->mLinkId = @mysqli_connect($host, $user, $password, $database);
	        }
	
	        /**
	         * Если не удалось установить соединение, выводим ошибку и возвращаем FALSE
	         */
	        if ( @mysqli_connect_errno() || !($this->mLinkId instanceof mysqli) )
	        {
	            $this->halt();
	            $this->mLinkId = false;
	            return false;
	        }
	        
	        @mysqli_query($this->mLinkId, 'SET NAMES "UTF8"');
		}
		else
		{
		    /**
	         * Пытаемся установить соединение с БД и, в случае успеха, записываем идентификатор соединения
	         * в переменную класса $mLinkId
	         */
	        $this->mLinkId = @mysql_connect($host, $user, $password, $password);
	
	        /**
	         * Если не удалось установить соединение, выводим ошибку и возвращаем FALSE
	         */
	        if ( @mysql_errno() || !is_resource($this->mLinkId) )
	        {
	            $this->halt();
	            $this->mLinkId = false;
	            return false;
	        }
	
	        /**
	         * Если не удалось выбрать базу данных, выводим ошибку и возвращаем FALSE
	         */
	        if (!@mysql_select_db($database, $this->mLinkId))
	        {
	        	$this->halt();
	            $this->mLinkId = false;
	            return false;
	        }
	        
	        @mysql_query('SET NAMES "UTF8"', $this->mLinkId);
		}
		
		return $this->mLinkId;
	}
	
    /**
     * Деструктор класса
     *
     * Закрывает активное соединение с БД.
     * @see disconnect()
     */
    public function __destruct()
    {
        $this->disconnect();
    }
    
    /**
     * Запись ошибок
     *
     * Записывает код и текст ошибки в соотв. переменные класса и в системный журнал.
     * Если включен вывод ошибок, то выводит код и текст ошибки в браузер пользователя.
     *
     * @param string $query SQL-запрос, инициировавший ошибку
     */
    private function halt($query = '')
    {
    	if ($this->mMysqli)
    	{
    		if ( !($this->mLinkId instanceof mysqli) )
            	return;

	        $this->mError = @mysqli_error($this->mLinkId);
	        $this->mErrno = @mysqli_errno($this->mLinkId);
    	}
    	else
    	{
	        if ( !is_resource($this->mLinkId) )
	            return;
	
	        $this->mError = @mysql_error($this->mLinkId);
	        $this->mErrno = @mysql_errno($this->mLinkId);
    	}
    }
    
    /**
     * Закрывает соединение с БД
     *
     * Закрывает соединение с базой данных. Если в качестве параметра передан идентификатор соединения с БД,
     * то закрывается это соединение, иначе соединие, на которое указывает переменная класса mLinkId.
     *
     * @see mLinkId
     * @param resource $link_id
     */
    public function disconnect($link_id = false)
    {
    	if ($this->mMysqli)
    	{
	    	if ( $link_id instanceof  mysqli )
	        {
	            @mysqli_close($link_id);
	        }
	        else
	        {
	            if ( $this->mLinkId instanceof  mysqli )
	                @mysqli_close($this->mLinkId);
	
	            $this->mLinkId = false;
	        }
    	}
    	else
    	{
	        if ( is_resource($link_id) )
	        {
	            @mysql_close($link_id);
	        }
	        else
	        {
	            if ( is_resource($this->mLinkId) )
	                @mysql_close($this->mLinkId);
	
	            $this->mLinkId = false;
	        }
    	}
    }

    /**
     * Освобождает память, выделенную под результат запроса
     *
     * В качестве идентификатора запроса используется либо переданный в функцию идентификатор,
     * либо, если переданный идентификатор не является ресурсом, значение переменной класса mQueryId.
     *
     * @see mQueryId
     *
     * @param resource $query_id
     *
     */
    public function free($query_id = false)
    {
    	if ($this->mMysqli)
    	{
	    	if ( $query_id instanceof mysqli_result)
	        {
	            @mysqli_free_result($query_id);
	        }
	        else
	        {
	            if ( $this->mQueryId instanceof mysqli_result )
	            {
	                @mysqli_free_result($this->mQueryId);
	            }
	
	            $this->mQueryId = false;
	        }
    	}
    	else
    	{
	        if (is_resource($query_id))
	        {
	            @mysql_free_result($query_id);
	        }
	        else
	        {
	            if ( is_resource($this->mQueryId) )
	            {
	                @mysql_free_result($this->mQueryId);
	            }
	
	            $this->mQueryId = false;
	        }
    	}
    }

    /**
     * Выполняет SQL-запрос к базе данных
     *
     * Если выполняется SELECT-запрос, то возвращается двумерный массив с результатами запроса (порядок столбцов соотв.
     * порядку полей в SELECT-запросе), с помощью метода класса <i>getAffectedRows()</i> можно получить общее кол-во записей,
     * возвращенное запросом. Для всех остальных запросов возвращается идентификатор запроса (результат работы функции
     * mysql_query()). Для запросов INSERT, UPDATE и DELETE с помощью метода класса <i>getAffectedRows()</i> можно получить общее
     * кол-во записей, затронутых запросом. Для INSERT-запроса с помощью метода класса <i>getInsertId()</i> можно получить
     * значение автоинкрементного поля, сгенерированного после запроса.
     *
     * @see getAffectedRows
     * @see getInsertId
     *
     * @param string $query Текст запроса
     * @return mixed Двумерный массив для SELECT-запросов или идентификатор запроса. FALSE в случае
     *         возникновения ошибки.
     */
    public function query( $query )
    {
        /**
         * Если текст запроса пустой, или не установлено соединение с базой, вернуть FALSE
         */
        if ( $query == '' || ($this->mMysqli && !($this->mLinkId instanceof mysqli)) || (!$this->mMysqli && !is_resource($this->mLinkId)) )
        {
            return false;
        }
        
        /**
         * Освобождаем память, выделенную под предыдущий запрос, если таковой был
         */
        $this->free();

        /**
         * Определяем тип запроса (SELECT, DELETE, INSERT, UPDATE) и записываем в переменную класса mQueryType.
         * Если тип запроса определить не удалось, то 'UNDEFINED'.
         */
        $m = null;
        if (preg_match('/\s* ([a-zA-Z]*) \s+/sixU', $query, $m))
        {
            $type = mb_strtoupper($m[1], 'UTF-8');
        }
        else
        {
            $type = 'UNDEFINED';
        }

        /**
         * Выполняем запрос. Если произошла ошибка, то записываем ее в системный журнал и возвращаем FALSE.
         */
        
        if ($this->mMysqli)
        	$this->mQueryId = @mysqli_query($this->mLinkId, $query);
        else
        	$this->mQueryId = @mysql_query($query, $this->mLinkId);
        
        if ( $this->mQueryId === false )
        {
            $this->halt($query);
            return false;
        }

        /**
         * Возвращаем идентификатор запроса
         */
        return $this->mQueryId;
    }
    
	/**
     * Экранирует переданную строку в соотв. с правилами СУБД
     *
     * @param string $str Строка для экранирования
     *
     * @return string Экранированная строка
     */
    public function escape($str)
    {
        if ($this->mMysqli)
        {
        	if ($this->mLinkId instanceof mysqli)
                $str = @mysqli_real_escape_string($this->mLinkId, $str);
            else
                $str = @addslashes($str);
        }
        else
        {
            if (function_exists('mysql_real_escape_string') && is_resource($this->mLinkId))
                $str = @mysql_real_escape_string($str, $this->mLinkId);
            elseif (function_exists('mysql_escape_string'))
                $str = @mysql_escape_string($str);
            else
                $str = @addslashes($str);
        }
        
        return $str;
    }
    
    /**
     * Загружает дамп из SQL файла.
     *
     * Если передан zip архив, то он должен содержать файл с названием dump.sql, в котором храниться дамп.
     * 
     * @param string $filePath Имя и путь к файлу, содержащему дамп.
     * 
     * @return boolean TRUE, если дамп был успешно загружен, FALSE в случае ошибки.
     */
	public function loadDumpFile($filePath)
    { 	 
        $filePath = preg_replace('/[^0-9a-zA-Z_:\-\.\[\]\/\s]+/', '', $filePath);
        $filePath = preg_replace('/\.\.+/', '', $filePath);
        $filePath = str_replace('\\', '/', $filePath);

        $fp = @fopen($filePath, 'rb');
        if (!$fp)
        {
            $this->mErrno = 1;
            $this->mError = sprintf(SB_DUMPER_ERROR_OPEN_IN_FILE, $filePath);
        
            return false;
        }
    
        $sql = $insert = '';
        $query_len = $execute = 0;
   
        while (!@feof($fp))
        {
            $str = trim(@fgets($fp));
            if (empty($str) || preg_match('/^(#|--)/U', $str)) 
            {
                continue;
            }
        
            $query_len += mb_strlen($str, 'UTF-8');
            if (!$insert && preg_match('/^(INSERT INTO `?([^` ]+)`? .*?VALUES)(.*)$/iU', $str, $m)) 
            {
                $insert = $m[1] . ' ';
                $sql .= $m[3];
            }
            else
            {
                $sql .= $str;
            }

            if ($sql)
            {
            	if (mb_substr($str, -1, mb_strlen($str, 'UTF-8'), 'UTF-8') == ';')
                {
                    $sql = mb_substr($insert . $sql, 0, -1, 'UTF-8');
                    $insert = '';
                    $execute = 1;
                }

                if ($query_len >= 65536 && preg_match('/,$/U', $str)) 
                {
                    $sql = mb_substr($insert . $sql, 0, -1, 'UTF-8');
                    $execute = 1;
                }
              
                if ($execute) 
                {
                	$this->query($sql);

                    if ($this->mErrno != 0)
                    {
                        @fclose($fp);
                        return false;
                    }
        
                    $sql = '';
                    $query_len = 0;
                    $execute = 0;
                }
            }
        }

        @fclose($fp);
        return true;
    } 
}
?>