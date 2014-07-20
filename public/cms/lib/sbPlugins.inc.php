<?php
/**
 * Реализация класса работы с модулями системы
 *
 * Данный класс производит все операции инициализации модулей системы. Ссылка на класс сохраняется
 * в переменной сессии $_SESSION['sbPlugins']. Класс хранится в сессии, чтобы избежать переинициализации
 * модулей при каждом обращении к событиям системы.
 *
 * @see $_SESSION['sbPlugins']
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Plugins
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Класс для работы с модулями системы
 *
 * Данный класс производит все операции инициализации модулей системы. Инициализация модулей происходит только
 * один раз при входе пользователя в систему. Затем класс восстанавливается из сессиии. Ссылка на класс сохраняется
 * в переменной сессии $_SESSION['sbPlugins']. Класс хранится в сессии, чтобы избежать переинициализации
 * модулей при каждом обращении к событиям системы.
 *
 * Модуль системы представляет собой набор файлов. В системе есть четкие правила, где должны располагаться эти файлы и как называться.
 * Основные файлы модуля, отвечающие за регистрацию модуля в системе и реализацию всех событий, предоставляемых модулем, должны
 * располагаться в поддиректории директории <i>/cms/plugins/</i>. Имя этой поддиректории должно совпадать с уникальным идентификатором
 * модуля, как он указан в методе регистрации модуля plRegister, например <samp>pl_news</samp>. Основными файлами модуля являются:
 *
 * - <b><идентификатор_модуля>.php</b>     Содержит все функции, реализующие события модуля. Вызывается только когда запрашивается событие модуля.
 * - <b><идентификатор_модуля>.h.php</b>   Содержит код инициализации модуля, вызывается только один раз при входе пользователя в систему.
 * - <b><идентификатор_модуля>.inc.php</b> Всегда включается в систему и содержит функции, реализующие события модуля, которые должны быть доступны
 *                                  всегда, независимо от того, доступен ли сам модуль текущему пользователю или нет. Данный файл может
 *                                  отсутствовать.
 *
 * Языковые файлы модуля располагаются в директории <i>/cms/lang/<язык>/</i>. Языковыми файлами модуля являются:
 *
 * - <b><идентификатор_модуля>.lng.php</b>   Содержит языковые контстанты, используемые в файле <i><идентификатор_модуля>.php</i>.
 * - <b><идентификатор_модуля>.h.lng.php</b> Содержит языковые контстанты, используемые в файлах <i><идентификатор_модуля>.h.php</i> и <i><идентификатор_модуля>.inc.php</i>.
 *
 * Иконка модуля располагается в директории <i>/cms/images/</i>. Иконки может не быть вообще.
 * Файлы иконок должны именоваться следующим образом:
 *
 * - <b><идентификатор_модуля>_24.gif или <идентификатор_модуля>_24.png</b>   Иконка для меню и панели инструментов, размер иконки 24 х 24 px.
 *
 * И, наконец, файлы, реализующие вывод информации в браузер посетителя сайта, располагаются в поддиректории директории <i>/prog/</i>.
 * Имя этой поддиректории должно совпадать с уникальным идентификатором
 * модуля, как он указан в методе регистрации модуля plRegister, например <samp>pl_news</samp>. Имена файлов могут быть любыми и вызваются
 * в функциях, отвечающих за визуализацию элементов модуля.
 *
 * Каждый модуль системы представляет набор событий и функций-обработчиков этих событий.
 *
 * Данный класс использует библиотеку работы с базой данных и класс $_SESSION['sbAuth'].
 *
 * @see $_SESSION['sbPlugins']
 * @see $_SESSION['sbAuth']
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Plugins
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbPlugins
{
    /**
     * Уникальный идентификатор регистрируемого модуля
     *
     * @var string
     */
    private $mCurName = '';

    /**
     * Название модуля регистрируемого модуля
     *
     * @var string
     */
    private $mCurTitle = '';

    /**
     * Указывает, разрешен ли регистрируемый модуль текущему пользователю
     *
     * @var unknown_type
     */
    private $mCurAvailableUser = false;

    /**
     * Указывает, разрешен ли регистрируемый модуль в ключе системы
     *
     * @var bool
     */
    private $mCurAvailableKey = false;

    /**
     * TRUE, если доступен хотя бы одни не библиотечный модуль и FALSE в ином случае.
     *
     * @var bool
     */
    private $mIsRegistered = false;

    /**
     * Массив с информацией о полях модулей сайта
     *
     * @var array()
     */
	public $mFieldsInfo = array();

    /**
     * Многомерный массив, содержащий информацию о всех модулях системы
     *
     * <code>
     * $this->mPlugins[$pl_name] = array();
     * // иконка модуля
     * $this->mPlugins[$pl_name]['icon'] = ...;
     * // основной файл модуля, в котором должны располагаться все функции модуля для событий модуля
     * $this->mPlugins[$pl_name]['include_file'] = ...;
     * // файл с языковыми константами для модуля
     * $this->mPlugins[$pl_name]['lang_file'] = ...;
     * // URL на справочный файл для модуля
     * $this->mPlugins[$pl_name]['help_url'] = ...;
     * // таблица БД для данного модуля, если таблица указана, то она используется в конструкторе модулей
     * // для добавления новых полей
     * $this->mPlugins[$pl_name]['table'] = '';
     * // описание прав для событий модуля
     * $this->mPlugins[$pl_name]['rights'] = array();
     * // надо ли проверять модуль на ключ системы
     * $this->mPlugins[$pl_name]['check_key'] = ...;
     * // надо ли проверять модуль на доступность текущему пользователю
     * $this->mPlugins[$pl_name]['check_user'] = ...;
     * // это библиотечный модуль или нет?
     * $this->mPlugins[$pl_name]['core'] = ...;
     * </code>
     * @var array
     */
    private $mPlugins = array();

    /**
     * Массив inc-файлов, которые инклудятся всегда, независимо от вызываемого события
     *
     * @var array
     */
    private $mIncFiles = array();

    /**
     * Массив lng-файлов, которые инклудятся всегда, независимо от вызываемого события.
     * В них обычно хранятся языковые константы для inc-файлов.
     *
     * @var array
     */
    private $mIncLangFiles = array();

    /**
     * Массив, хранящий информацию о меню системы
     *
     * <code>
     * $i = count($this->mMenu);
     * $this->mMenu[$i] = array();
     *
     * // текст пункта меню
     * $this->mMenu[$i]['item'] = ...;
     * // иконка пункта меню
     * $this->mMenu[$i]['icon'] = ...;
     * // событие модуля, вызываемое при щелчке по пункту меню
     * $this->mMenu[$i]['event'] = ...;
     * </code>
     * @var array
     */
    public $mMenu = array();

    /**
     * Массив, хранящий все события всех модулей системы
     *
     * <code>
     * // модуль, к которому относится данное событие
     * $this->mEvents[$event]['plugin'] = ...;
     * // имя функции, реализующей событие
     * $this->mEvents[$event]['function'] = ...;
     * // права, которыми должен обладать пользователь для вызова события
     * $this->mEvents[$event]['rights'] = ...;
     * // название события (отображается в заголовках диалоговых окон и пр.)
     * $this->mEvents[$event]['title'] = ...;
     * // подсказки для события
     * $this->mEvents[$event]['tips'] = ...;
     * </code>
     *
     * @var array
     */
    private $mEvents = array();

    /**
     * Содержит список модулей, использующих рубрикатор
     *
     * @var array
     */
    public $mRubrikator = array();

    /**
     * Содержит массив названий заданий и файлов которые нужно запускать из крона
     *
     * @var array()
     */
    public $mCron = array();

    /**
     * Содержит массив данных элементов для комментариев
     *
     * @var array()
     */
    public $mComments = array();

    /**
     * Содержит список модулей, использующих календарь
     *
     * @var array
     */
    public $mCalendar = array();

    /**
     * Массив, содержащий значения системных настроек модуля
     *
     * Используется в методе addSetting.
     *
     * @var array
     */
    private static $mSettings = array();

    /**
     * Массив, содержащий значения пользовательских настроек модуля
     *
     * Используется в методе addUserSetting.
     *
     * @var array
     */
    private static $mUserSettings = array();

    /**
     * Массив, содержащий описание системных настроек модуля
     *
     * Используется в методе addSetting.
     *
     * @var array
     */
    private $mSettingsProps = array();

    /**
     * Массив, содержащий описание пользовательских настроек модуля
     *
     * Используется в методе addUserSetting.
     *
     * @var array
     */
    private $mUserSettingsProps = array();

    /**
     * Массив, содержащий события модулей, выносимые на главную страницу системы в виде перетаскиваемых областей
     * <code>
     * $this->mContainers[$i] = array();
     * // событие, вызываемое для получения содержимого области
     * $this->mContainers[$i]['event']   = ...;
     * // заголовок области
     * $this->mContainers[$i]['title']   = ...;
     * // иконка области
     * $this->mContainers[$i]['icon']    = ...;
     * // область свернута (true) или развернута (false)
     * $this->mContainers[$i]['roll']    = false;
     * </code>
     *
     * @var array
     */
    public $mContainers = array();

    /**
     * Массив, содержащий элементы модулей
     *
     * Элементы модуля - это те кирпичики, из которых строится сайт. Элементами, например, являются новостная лента и подробная новость,
     * форма подписки на лист рассылки, форма логина, форма регистрации пользователя и многие другие.
     *
     * @var array
     */
    private $mElems = array();

    /**
     * Иконка модуля
     *
     * @var string
     */
    public $mIcon = '';

    /**
     * Файл, содержащий функции-обработчики модуля
     *
     * Если файл не указан, то он ищется в папке /cms/plugins по идентификатору модуля.
     *
     * @var string
     */
    public $mFile = '';

    /**
     * Файл, содержащий языковые константы модуля
     *
     * Если файл не указан, то он ищется в папке /cms/lang/[язык] по идентификатору модуля.
     *
     * @var string
     */
    public $mLangFile = '';

    /**
     * Список плагинов, для которых доступны настройки экспорта и импорта
     * @var array
     */
    private $mExportImport = array();

    /**
     * @ignore
     */
    private $mCorePlugins = array('pl_about', 'pl_cache', 'pl_calendar', 'pl_categs', 'pl_clouds', 'pl_comments',
    							  'pl_cron', 'pl_dumper', 'pl_editor', 'pl_filelist', 'pl_folders', 'pl_kernel',
    							  'pl_know', 'pl_menu', 'pl_messages', 'pl_news', 'pl_pager_temps', 'pl_pages',
    							  'pl_plugin_data', 'pl_redirect', 'pl_settings', 'pl_site_users', 'pl_sprav', 'pl_systemlog',
    							  'pl_templates', 'pl_texts', 'pl_update', 'pl_user_settings', 'pl_users', 'pl_voting', 'pl_domains_manager',
    							  'pl_export', 'pl_sitemap');

    /**
     * @ignore
     */
    private $mAllPlugins = array('pl_about', 'pl_banners', 'pl_basket', 'pl_cache', 'pl_calendar', 'pl_categs', 'pl_clouds',
                                 'pl_comments', 'pl_cron', 'pl_dumper', 'pl_editor', 'pl_faq', 'pl_filelist', 'pl_folders',
                                 'pl_forum', 'pl_imagelib', 'pl_kernel', 'pl_know', 'pl_maillist', 'pl_menu', 'pl_messages',
                                 'pl_news', 'pl_pager_temps', 'pl_pages', 'pl_payment', 'pl_plugin_data', 'pl_plugin_maker',
                                 'pl_polls', 'pl_redirect', 'pl_rss', 'pl_search', 'pl_services_cb', 'pl_services_rutube', 'pl_settings',
                                 'pl_site_users', 'pl_sprav', 'pl_systemlog', 'pl_templates', 'pl_tester', 'pl_texts',
                                 'pl_update', 'pl_user_settings', 'pl_users', 'pl_voting', 'pl_workflow', 'pl_domains_manager',
    							 'pl_export', 'pl_sitemap');

    /**
     *  Массив, содержащий список коренных разделов модуля
     * @ignore
     */
    private $mCatList = array();

    /**
     * Массив, содержащий настройки модулей, созданных через конструктор модулей
     * @var array
     */
    private $pmPluginsSettings;

    /**
     * Конструктор класса
     *
     */
    public function __construct()
    {
    	$res = sql_query('SELECT pm_id FROM sb_plugins_maker');
    	if ($res)
    	{
    		foreach ($res as $value)
    		{
    			$this->mCorePlugins[] = 'pl_plugin_'.$value[0];
    			$this->mAllPlugins[] = 'pl_plugin_'.$value[0];
    		}
    	}

        $res = sql_param_query('SELECT cat_ident, cat_id FROM sb_categs WHERE cat_ident IN (?a) AND cat_level=0', $this->mAllPlugins);
        if($res)
        {
            foreach ($res as $value)
            {
                $this->mCatList[$value[0]] = $value[1];
            }
        }
    }

    /**
     * Деструктор класса, сбрасываем все переменные.
     *
     */
    public function __destruct()
    {
        $this->mCurName = '';
        $this->mCurTitle = '';
        $this->mCurAvailableUser = false;
        $this->mCurAvailableKey = false;
    }

    /**
     * Регистрирует модуль в системе
     *
     * Данный метод проверяет, разрешен ли модуль в ключе системы, доступен ли модуль текущему пользователю и, если
     * проверки прошли успешно, регистрирует модуль в системе.
     *
     * @param string $pl_name Уникальный идентификатор модуля, должен обязательно совпадать с именем папки,
     *                        в которой расположены файлы модуля (например 'pl_news').
     * @param string $pl_title Название модуля.
     * @param string $pl_help_url URL справочного файла для модуля.
     * @param bool $pl_check_user Надо ли проверять модуль на доступность текущему пользователю системы.
     * @param bool $pl_core TRUE, если модуль библиотечный, и FALSE в ином случае. Библиотечные модули не предоставляют никакого интерфейса
     *                      пользователю системы, а выполняют служебную роль.
     * @param string $pl_path Путь к файлам модуля относительно директории cms/plugins.
     *
     * @return bool TRUE, если модуль зарегистрирован, и FALSE в ином случае
     */
    public function register($pl_name, $pl_title, $pl_help_url='', $pl_check_user=true, $pl_core=false, $pl_path='')
    {
    	if ($pl_name == '')
    	{
    		$this->mFile = '';
            $this->mLangFile = '';

    		return false;
    	}

    	$pl_path = trim($pl_path, '\\/');
    	if (trim($pl_path) != '')
    	{
    		$pl_path .= '/';
    	}

    	$this->mCurAvailableKey = false;
        $this->mCurAvailableUser = false;

        $this->mCurName = '';
        $this->mCurTitle = '';

        if (!in_array($pl_name, $this->mCorePlugins) && in_array($pl_name, $this->mAllPlugins) && !$this->checkForKey($pl_name))
        {
        	/**
             * Модуль не разрешен в ключе системы
             */
        	$this->mFile = '';
            $this->mLangFile = '';

            return false;
        }

        $this->mCurAvailableKey = true;

        $inc_file = SB_CMS_PL_PATH.'/'.$pl_path.$pl_name.'/'.$pl_name.'.inc.php';
        if (@file_exists($inc_file) && !array_search($inc_file, $this->mIncFiles))
        {
            /**
             * У модуля есть inc-файл, который инклудится всегда, независимо от вызываемого события
             */
            $this->mIncFiles[] = $inc_file;
        }

        $lang_file = SB_CMS_LANG_PATH.'/'.$pl_path.$pl_name.'.h.lng.php';
        if (@file_exists($lang_file) && !array_search($lang_file, $this->mIncLangFiles))
        {
            /**
             * У модуля есть lng-файл, который инклудится всегда, независимо от вызываемого события
             */
            $this->mIncLangFiles[] = $lang_file;
        }

        $this->mCurName = $pl_name;
        $this->mCurTitle = $pl_title;

        $this->mPlugins[$pl_name] = array();

        // название модуля
        $this->mPlugins[$pl_name]['title'] = $pl_title;
        // иконка модуля
        if ($this->mIcon != '')
        {
        	$this->mPlugins[$pl_name]['icon'] = $this->mIcon;
        }
        else
        {
	        if (@file_exists(SB_CMS_IMG_PATH.'/plugins/'.$pl_path.$pl_name.'_24.gif'))
	        {
	            $this->mPlugins[$pl_name]['icon'] = SB_CMS_IMG_URL.'/plugins/'.$pl_path.$pl_name.'_24.gif';
	        }
	        else if (@file_exists(SB_CMS_IMG_PATH.'/plugins/'.$pl_path.$pl_name.'_24.png'))
	        {
	            $this->mPlugins[$pl_name]['icon'] = SB_CMS_IMG_URL.'/plugins/'.$pl_path.$pl_name.'_24.png';
	        }
	        else
	        {
	            $this->mPlugins[$pl_name]['icon'] = '';
	        }
        }

        $this->mIcon = '';

        // описание прав для событий модуля
        $this->mPlugins[$pl_name]['rights'] = array();
        $this->mPlugins[$pl_name]['check_user'] = $pl_check_user;

        if ($pl_check_user && !$this->checkForUser($pl_name))
        {
            /**
             * Модуль недоступен текущему пользователю
             */
        	$this->mFile = '';
            $this->mLangFile = '';

            return false;
        }
        else
        {
            if (!$pl_core)
            {
                // Ставим флаг, что в системе есть доступные пользователю модули
                $this->mIsRegistered = true;
            }

            if ($this->mFile == '')
            {
                $pl_file = SB_CMS_PL_PATH.'/'.$pl_path.$pl_name.'/'.$pl_name.'.php';
            }
            else
            {
                $pl_file = $this->mFile;
            }

            if ($this->mLangFile == '')
            {
	            $pl_lang_file = SB_CMS_LANG_PATH.'/'.$pl_path.$pl_name.'.lng.php';

	            if (!@file_exists($pl_lang_file))
	            {
	                $pl_lang_file = '';
	            }
            }
            else
            {
            	$pl_lang_file = $this->mLangFile;
            }

            // основной файл модуля, в котором должны располагаться все функции модуля
            $this->mPlugins[$pl_name]['include_file'] = $pl_file;
            // файл с языковыми константами для модуля
            $this->mPlugins[$pl_name]['lang_file'] = $pl_lang_file;
            // URL на справочный файл для модуля
            $this->mPlugins[$pl_name]['help_url'] = $pl_help_url;
            // таблица БД для данного модуля, если таблица указана, то она используется в конструкторе модулей
            // для добавления новых полей
            $this->mPlugins[$pl_name]['table'] = '';
            $this->mPlugins[$pl_name]['use_categs'] = false;
            $this->mPlugins[$pl_name]['use_cache'] = false;
            $this->mPlugins[$pl_name]['use_workflow'] = false;
			$this->mPlugins[$pl_name]['core'] = $pl_core;
            $this->mPlugins[$pl_name]['cat_id'] = '';

            if(isset($this->mCatList[$pl_name]))
            {
                $this->mPlugins[$pl_name]['cat_id'] = $this->mCatList[$pl_name];
            }

            $this->mCurAvailableUser = true;

            $this->mFile = '';
            $this->mLangFile = '';

            return true;
        }
    }

    /**
     * Убираем модуль из системы
     *
     * @param string $pl_name Уникальный идентификатор модуля, должен обязательно совпадать с именем папки,
     *                        в которой расположены файлы модуля (например 'pl_news').
     */
    public function unregister($pl_name)
    {
    	unset($this->mPlugins[$pl_name]);
    	foreach ($this->mEvents as $event => $ar)
    	{
    		if ($ar['plugin'] == $pl_name)
    		{
    			unset($this->mEvents[$event]);
    		}
    	}

    	foreach($this->mRubrikator as $cat_ident => $ar)
    	{
    		if ($ar['plugin_ident'] == $pl_name)
    		{
    			unset($this->mRubrikator[$cat_ident]);
    		}
    	}

    	foreach($this->mCalendar as $comp_ident => $ar)
    	{
    		if ($ar['plugin_ident'] == $pl_name)
    		{
    			unset($this->mCalendar[$comp_ident]);
    		}
    	}

    	if (isset($this->mCron[$pl_name]))
    		unset($this->mCron[$pl_name]);

    	if (isset($this->mComments[$pl_name]))
    		unset($this->mComments[$pl_name]);

    	foreach($this->mElems as $ident => $ar)
    	{
    		if ($ar['plugin'] == $pl_name)
    		{
    			unset($this->mElems[$ident]);
    		}
    	}

    	foreach ($this->mMenu as $key => $menu_item)
    	{
    		if (stripos($menu_item['event'], 'event='.$pl_name.'_') !== false)
    		{
    			unset($this->mMenu[$key]);
    		}
    	}
    }

    /**
     * Добавляет стандартные поля модуля.
     *
     * @param string $table Таблица в базе данных
	 * @param string $id_field Имя поля в БД где хранится автоинкрементное поле
	 * @param string $title_field Имя поля в БД где хранится название элементы
     * @param array() $fields Массив полей элементов вида:
     * 				array([имя поля в БД] => array([title] => Название поля
     * 											   [flags] => Флаг обозначающий тип поля))
     *
     * @param array() $cat_fields Массив стандарных полей разделов для конкретного модуля, которые хранятся в поле cat_fields базы данных:
	 * 				array([имя поля в БД] => array([title] => Название поля
	 * 											   [flags] => Флаг обозначающий тип поля))
	 */
	public function addFieldsInfo($table, $id_field, $title_field, $fields, $cat_fields = array())
	{
		if (!$this->mCurAvailableKey || $table == '' || $id_field == '' || !is_array($fields) || empty($fields))
		{
			//	если модуль не разрешен в ключе, ничего не делаем
			return;
		}

		if(!isset($this->mFieldsInfo[$this->mCurName]))
			$this->mFieldsInfo[$this->mCurName] = array();

		$this->mFieldsInfo[$this->mCurName]['id'] = $id_field;
		$this->mFieldsInfo[$this->mCurName]['title'] = $title_field;
		$this->mFieldsInfo[$this->mCurName]['table'] = $table;
		$this->mFieldsInfo[$this->mCurName]['fields'] = $fields;
		$this->mFieldsInfo[$this->mCurName]['cat_fields'] = $cat_fields;
	}

    /**
     * Добавляет в меню системы ссылку на событие модуля
     *
     * @param string $item Текст пункта меню. Указывается полный путь к пункту, разделитель - '>'. Например,
     *                          <samp>Меню пользователя>Обратная связь>Вопрос-Ответ</samp>
     * @param string $event Идентификатор события.
     * @param string $icon Иконка пункта меню, если не указана, то берется иконка модуля.
     */
    public function addToMenu($item, $event, $icon='')
    {
        if (!$this->mCurAvailableUser || empty($item) || empty($event))
        {
            // если модуль не доступен текущему пользователю или не задан текст пункта, в меню его не добавляем
            return;
        }

        if (strtolower(substr($event, 0, 10)) != 'javascript')
        	$event = SB_CMS_CONTENT_FILE.'?event='.$this->mCurName.'_'.$event;

        $i = count($this->mMenu);
        $this->mMenu[$i] = array();

        $this->mMenu[$i]['item'] = $item;
        if (empty($icon))
        {
            // если иконка не указана, то берем иконку модуля
            $this->mMenu[$i]['icon'] = $this->mPlugins[$this->mCurName]['icon'];
        }
        else
        {
        	if (substr_count($icon,  '/') == 0)
                $this->mMenu[$i]['icon'] = SB_CMS_IMG_URL.'/plugins/'.$icon;
            else
                $this->mMenu[$i]['icon'] = $icon;
        }

        $this->mMenu[$i]['event'] = $event;
    }

    /**
     * Устанавливает событие модуля, отвечающее за вывод списка элементов модуля.
     *
     * @param string $event
     */
    public function setMainEvent($event)
    {
    	if (!$this->mCurAvailableUser || empty($event))
        {
            // если модуль не доступен текущему пользователю или не задан текст пункта, в меню его не добавляем
            return;
        }

        $this->mPlugins[$this->mCurName]['main_event'] = $this->mCurName.'_'.$event;
    }

    /**
     * Добавляет пользовательское событие для регистрируемого модуля
     *
     * Пользовательские события, в отличие от системных, добавляются только в том случае, если модуль разрешен
     * текущему пользователю.
     *
     * @param string $event Идентификатор события (собирается из идентификатора модуля, знака _ и собственно идентификатора события). У одного
     *                      модуля не может быть несколько событий с одним идентификатором.
     * @param string $function Имя функция, реализующая добавляемое событие.
     * @param string $rights Какими правами должен обладать пользователь для вызова данного события (например 'read' или 'read|write').
     *                      Права описываются отдельно. Разделителем прав является |. Для событий, реализующих работу с разделами модуля
     *                      есть преопределенное кол-во прав - <i>categ_write</i>, <i>categ_delete</i>, <i>categ_rights</i>.
     *                      Эти идентификаторы зарезервированны. Если права не указаны, то событие будет доступно всегда, когда
     *                      доступен сам модуль.
     * @param string $title Название события (отображается в заголовках диалоговых окон и пр.)
     */
    public function addUserEvent($event, $function, $rights='', $title='')
    {
        if (!$this->mCurAvailableUser || $event == '' || $function == '')
        {
            // если модуль не доступен пользователю, или не указано события или функция-обработчик события,
            // то событие модуля не регистрируем
            return;
        }

        $event = $this->mCurName.'_'.$event;

        if (!isset($this->mEvents[$event]))
            $this->mEvents[$event] = array();

        $this->mEvents[$event]['plugin'] = $this->mCurName;
        $this->mEvents[$event]['system'] = false;
        $this->mEvents[$event]['function'] = $function;
        $this->mEvents[$event]['rights'] = $rights;
        $this->mEvents[$event]['title'] = ($title != '' ? $title : $this->mCurTitle);
    }

    /**
     * Добавляет системное событие для регистрируемого модуля
     *
     * Системные события, в отличие от пользовательских, добавляются всегда, независимо от того разрешен модуль
     * текущему пользователю или нет.
     *
     * @param string $event Идентификатор события (собирается из идентификатора модуля, знака _ и собственно идентификатора события).
     *                      У одного модуля не может быть несколько событий с одним идентификатором.
     * @param string $function Имя функция, реализующая добавляемое событие.
     * @param string $title Название события (отображается в заголовках диалоговых окон и пр.)
     */
    public function addSystemEvent($event, $function, $title='')
    {
        if (!$this->mCurAvailableKey || $event == '' || $function == '')
        {
            // если модуль не разрешен в ключе системы или не указано событие или функция-обработчик события, то ничего не делаем
            return;
        }
        $event = $this->mCurName.'_'.$event;

        if (!isset($this->mEvents[$event]))
            $this->mEvents[$event] = array();

        $this->mEvents[$event]['plugin'] = $this->mCurName;
        $this->mEvents[$event]['system'] = true;
        $this->mEvents[$event]['function'] = $function;
        $this->mEvents[$event]['rights'] = '';
        $this->mEvents[$event]['title'] = ($title != '' ? $title : $this->mCurTitle);
    }

    /**
     * Добавляет новое право в группу прав для событий модуля
     *
     * @param string $right_ident Идентификатор права.
     * @param string $right_desc Описание права.
     */
    public function addEventsRights($right_ident, $right_desc)
    {
        if (!$this->mCurAvailableKey || $right_ident == '' || $right_desc == '')
        {
            // если модуль не разрешен в ключе, ничего не делаем
            return;
        }

        $this->mPlugins[$this->mCurName]['rights'][$right_ident] = $right_desc;
    }

    /**
     * Делает разделы модуля доступными для вывода на сайте
     *
     * @param string $cat_ident Уникальный идентификатор разделов модуля.
     * @param string $title Название модуля в рубрикаторе.
     * @param string $get_ident Идентификатор GET-параметров модуля.
     * @param string $temps_list_ident Уникальный идентификатор разделов макетов дизайна вывода разделов.
     * @param string $temps_full_ident Уникальный идентификатор разделов макетов дизайна вывода выбранного раздела.
     */
    public function addToRubrikator($cat_ident, $title, $get_ident, $temps_list_ident='', $temps_full_ident='')
    {
        if (!$this->mCurAvailableKey || $cat_ident == '' || $title == '')
        {
            // если модуль не разрешен в ключе, ничего не делаем
            return;
        }

        $this->mRubrikator[$cat_ident] = array();
        $this->mRubrikator[$cat_ident]['title'] = $title;
        $this->mRubrikator[$cat_ident]['temps_list_ident'] = $temps_list_ident;
        $this->mRubrikator[$cat_ident]['temps_full_ident'] = $temps_full_ident;
        $this->mRubrikator[$cat_ident]['plugin_ident'] = $this->mCurName;
        $this->mRubrikator[$cat_ident]['get_ident'] = $get_ident;
    }

    /**
     * Связь компонента с календарем
     *
     * @param string $comp_ident Идентификатор компонента.
     * @param string $get_func Функция получения чисел, за которые есть элементы.
     * @param string $get_func_file Путь к файлу, содержащему функцию получения чисел (относительно папки cms/plugins).
     * @param string $date_fields Ассоциативный массив полей таблицы БД, содержащих дату (ключ массива - поле таблицы, значение - описание поля).
     */
    public function addToCalendar($comp_ident, $get_func, $get_func_file, $date_fields=array())
    {
        if (!$this->mCurAvailableKey || $comp_ident == '' || $get_func == '')
        {
            // если модуль не разрешен в ключе, ничего не делаем
            return;
        }

        $comp_ident = $this->mCurName.'_'.$comp_ident;
        $this->mCalendar[$comp_ident] = array();
        $this->mCalendar[$comp_ident]['plugin_ident'] = $this->mCurName;
        $this->mCalendar[$comp_ident]['get_func'] = $get_func;
        $this->mCalendar[$comp_ident]['get_func_file'] = $get_func_file;
        $this->mCalendar[$comp_ident]['date_fields'] = $date_fields;
    }

    /**
     * Добавляет модуль в конструктор модулей
     *
     * @param string $table Таблица БД модуля, в которую будут добавлены новые поля модуля.
     * @param bool $use_categs Разрешено ли добавлять пользовательские поля к разделам модуля.
     */
    public function addToConstructor($table, $use_categs=true)
    {
        if (!$this->mCurAvailableUser || $table == '')
        {
            // если модуль не доступен пользователю
            return;
        }

        $this->mPlugins[$this->mCurName]['table'] = $table;
        $this->mPlugins[$this->mCurName]['use_categs'] = $use_categs;
    }

    /**
     * Добавляет модуль в список модулей, использующих кэширование на сайте
     *
     */
    public function addToCache()
    {
        if (!$this->mCurAvailableKey)
        {
            return;
        }

        $this->mPlugins[$this->mCurName]['use_cache'] = true;
    }

	/**
     * Добавляет модуль в список модулей, использующих цепочки публикаций
     *
     */
    public function addToWorkflow()
    {
        if (!$this->mCurAvailableKey)
        {
            return;
        }

        $this->mPlugins[$this->mCurName]['use_workflow'] = true;
    }

    /**
     * Добовляет задачи модуля в Планировщик заданий.
     *
     * @param string $task_path Путь к файлу, содержащему функцию, которую необходимо запускать (относительно папки cms/plugins).
     * @param string $task_func Имя запускаемой функции.
     * @param string $task_name Название задания.
     * @param string $task_descr Описание задания.
     */
    public function addToCron($task_path, $task_func, $task_name, $task_descr)
    {
		if (!$this->mCurAvailableKey || $task_path == '' || $task_func == '' || $task_name == '')
		{
			// если модуль не разрешен в ключе, ничего не делаем
			return;
		}

        if(isset($this->mCron[$this->mCurName]))
        {
            $count = count($this->mCron[$this->mCurName]);
        }
        else
        {
            $count = 0;
            $this->mCron[$this->mCurName] = array();
        }

        $this->mCron[$this->mCurName][$count] = array();

        $this->mCron[$this->mCurName][$count]['task_path'] = $task_path;
        $this->mCron[$this->mCurName][$count]['task_func'] = $task_func;
        $this->mCron[$this->mCurName][$count]['task_name'] = $task_name;
        $this->mCron[$this->mCurName][$count]['task_descr'] = $task_descr;
    }

    /**
	 * Массив содержит данные таблиц элементов модулей (изображений, роликов, новостей...). Необходим для определения того в какой таблице
	 * находиться элемент к которому относиться комментарий.
	 *
     * @param string $table Название таблицы в которой находяться элементы
     * @param string $id_field Название поля таблицы элементов, в котором хранятся ID элементов
     * @param string $title_field Название поля таблицы элементов, в котором хранятся названия элементов.
     * @param string $url_field Название поля таблицы элементов, в котором хранятся псевдостатический адрес(url) элементов.
     */
	public function addToComments($table, $id_field, $title_field, $url_field)
	{
		if (!$this->mCurAvailableKey || $table == '' || $id_field == '')
		{
			// если модуль не разрешен в ключе, ничего не делаем
			return;
		}

		$this->mComments[$this->mCurName] = array();
		$this->mComments[$this->mCurName]['title'] = $this->mCurTitle;
		$this->mComments[$this->mCurName]['table'] = $table;
		$this->mComments[$this->mCurName]['id_field'] = $id_field;
		$this->mComments[$this->mCurName]['title_field'] = $title_field;
		$this->mComments[$this->mCurName]['url_field'] = $url_field;
    }

    /**
     * Добавляет событе на главную страницу системы (перетаскиваемые области)
     *
     * @param string $event Добавляемое событие.
     * @param string $title Заголовок перетаскиваемой области.
     * @param string $icon Иконка перетаскиваемой области. Если не указана, используется иконка модуля.
     */
    public function addToMainPage($event, $title, $icon='')
    {
        if (!$this->mCurAvailableUser || $event == '')
        {
            // если модуль не доступен пользователю, ничего не делаем
            return;
        }

        $event = $this->mCurName.'_'.$event;

        if (empty($icon))
        {
            // если не указана иконка, то берем иконку модуля
            $icon = str_replace('_24', '_16', $this->mPlugins[$this->mCurName]['icon']);
            if (!@file_exists(str_replace(SB_CMS_IMG_URL, SB_CMS_IMG_PATH, $icon)))
            {
                $icon = '';
            }
        }

        $i = count($this->mContainers);

        $this->mContainers[$i] = array();
        $this->mContainers[$i]['event']   = $event;
        $this->mContainers[$i]['title']   = $title;
        $this->mContainers[$i]['icon']    = $icon;
        $this->mContainers[$i]['roll']    = false;
    }

    /**
     * Добавляет в систему элемент модуля
     *
     * Элементы модуля - это те кирпичики, из которых строится сайт. Элементами, например, являются новостная лента и подробная новость,
     * форма подписки на лист рассылки, форма логина, форма регистрации пользователя и многие другие.
     *
     * @param string $name Название элемента.
     * @param string $ident Уникальный идентификатор элемента. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора элемента.
     * @param string $get_func Имя функции, выводящей элемент. Результат, возвращаемый данной функцией, должен быть HTML или PHP кодом.
     *                         Именно этот код будет подставлен вместо элемента в сгенерированной системой странице вместо элемента.
     * @param string $get_event Событие, вызываемое при сопоставлении элемента с тегом макета дизайна.
     * @param int dlg_width Ширина диалогового окна, вызываемого при сопоставлении элемента с тегом макета дизайна.
     * @param int dlg_height Высота диалогового окна, вызываемого при сопоставлении элемента с тегом макета дизайна.
     * @param string template_event Событие, вызываемое для редактирования макета дизайна компонента.
     * @param string template_ident Идентификатор разделов макета дизайна компонента.
     * @param string edit_event Событие, вызываемое для редактирования компонента.
     *
     */
    public function addElem($name, $ident, $get_func, $info_func='', $get_event='', $dlg_width=640, $dlg_height=480, $template_event='', $template_ident='', $edit_event='')
    {
        if (!$this->mCurAvailableKey || $name == '' || $ident == '' || $get_func == '')
        {
            // если модуль не доступен в системе, не добавляем его элементы
            return;
        }

        $ident = $this->mCurName.'_'.$ident;
        if ($get_event != '')
            $get_event = $this->mCurName.'_'.$get_event;

        if ($template_event != '')
        	$template_event = $this->mCurName.'_'.$template_event;

        if ($edit_event != '')
        	$edit_event = $this->mCurName.'_'.$edit_event;

        $this->mElems[$ident] = array();
        $this->mElems[$ident]['plugin'] = $this->mCurName;
        $this->mElems[$ident]['name'] = $this->mPlugins[$this->mCurName]['title'].'|'.$name;
        $this->mElems[$ident]['get_func'] = $get_func;
        $this->mElems[$ident]['info_func'] = $info_func;
        $this->mElems[$ident]['get_event'] = $get_event;
        $this->mElems[$ident]['dlg_width'] = $dlg_width;
        $this->mElems[$ident]['dlg_height'] = $dlg_height;
        $this->mElems[$ident]['template_event'] = $template_event;
        $this->mElems[$ident]['template_ident'] = $template_ident;
        $this->mElems[$ident]['edit_event'] = $edit_event;
    }


    /**
     * Добавляет стандартный модуль в конструктор шаблонов импорта и экспорта
     * @param boolean $export   Доступны шаблоны экспорта
     * @param boolean $import   Доступны шаблоны импорта
     */
    public function addToExportImport($export = 1, $import = 1)
    {
        if (!$this->mCurAvailableUser || $this->mCurName == '')
        {
            // если модуль не доступен пользователю
            return;
        }

        $this->mExportImport[$this->mCurName]['title'] = $this->mCurTitle;
        $this->mExportImport[$this->mCurName]['export'] = intval($export);
        $this->mExportImport[$this->mCurName]['import'] = intval($import);
    }

    /**
     * Метод включает для псевдо-модуля (например pl_polls_results) возможность задавать макеты экспорта и/или импорта
     * @param string $ident     Идентификатор модуля
     * @param boolean $export   Модуль участвует в экспорте
     * @param boolean $import   Модуль участвует в импорте
     * @param string $title     Название для списка модулей
     * @param string $table     Таблица псевдо-модуля
     * @param array $fields     Массив с описанием полей таблицы (ключ - поле в таблице БД, значение - массив array('title'=>Человекопонятное название))
     */
    public function setExportImport($ident, $export, $import, $title='', $table='', $fields=array())
    {
        if(!isset($this->mExportImport[$ident]))
        {
            $this->mExportImport[$ident] = array();
        }

        $this->mExportImport[$ident]['export'] = intval($export);
        $this->mExportImport[$ident]['import'] = intval($import);
        $this->mExportImport[$ident]['title'] = $title;
        $this->mExportImport[$ident]['table'] = $table;
        $this->mExportImport[$ident]['fields'] = $fields;
    }

    // --- Методы для работы с настройками системы и пользователей --- //

	/**
	 * Инициализация настроек системы
	 *
	 */
    static function initSettings()
    {
    	$res = sql_query('SELECT s_setting, s_value, s_domain FROM sb_settings');
    	if ($res)
    	{
    		foreach ($res as $value)
    		{
    			list($s_setting, $s_value, $s_domain) = $value;

    			if ($s_value != '')
    				$s_value = unserialize($s_value);

    			if (!isset(self::$mSettings[$s_domain]))
    			{
    				self::$mSettings[$s_domain] = array();
    			}

	            self::$mSettings[$s_domain][$s_setting] = array();
	            self::$mSettings[$s_domain][$s_setting]['value'] = str_replace('{DOMAIN}', $s_domain, $s_value);
    		}
    	}
    }

    /**
     * Инициализация пользовательских настроек системы
     *
     */
	static function initUserSettings()
    {
    	if (isset($_SESSION['sbAuth']) && $_SESSION['sbAuth'] instanceof sbAuth && method_exists($_SESSION['sbAuth'], 'getUserId'))
    	{
    		$res = sql_query('SELECT us_setting, us_value FROM sb_users_settings WHERE us_user_id=?d', $_SESSION['sbAuth']->getUserId());
    		if ($res)
    		{
    			foreach ($res as $value)
    			{
    				list($us_setting, $us_value) = $value;

    				$us_value = unserialize($us_value);

    				self::$mUserSettings[$us_setting] = array();
			        self::$mUserSettings[$us_setting]['value'] = $us_value;
    			}
    		}
    	}
    }

	/**
     * Возвращает массив со всеми настройками системы
     *
     * @param string $domain Домен, настройки которого следует возвратить.
     *
     * @return array Массив с настройками системы.
     */
    static function getAllSettings($domain = 'all')
    {
    	if (isset(self::$mSettings[$domain]))
    		return self::$mSettings[$domain];
    	else
    		return array();
    }

	/**
     * Возвращает массив со всеми настройками системы
     *
     * @param string $domain Домен, настройки которого следует возвратить.
     *
     * @return array Массив с настройками системы.
     */
    public function getAllSettingsProps($domain = 'all')
    {
    	if (isset($this->mSettingsProps[$domain]))
    		return $this->mSettingsProps[$domain];
    	elseif ($domain != 'all' && isset($this->mSettingsProps[SB_COOKIE_DOMAIN]))
    		return $this->mSettingsProps[SB_COOKIE_DOMAIN];
    	else
    		return array();
    }

	/**
     * Возвращает массив со всеми пользовательскими настройками системы
     *
     * @return array Массив с пользовательскими настройками системы.
     */
    static function getAllUserSettings()
    {
        return self::$mUserSettings;
    }

	/**
     * Возвращает массив со всеми пользовательскими настройками системы
     *
     * @return array Массив с пользовательскими настройками системы.
     */
    public function getAllUserSettingsProps()
    {
        return $this->mUserSettingsProps;
    }

	/**
     * Возвращает значение настройки системы по ее идентификатору
     *
     * @param string $setting_ident Идентификатор настройки.
     * @param string $domain Домен, для которого возвращается настройка.
     *
     * @return mixed Значение настройки.
     */
    static function getSetting($setting_ident, $domain='')
    {
    	$return = null;
    	if ($domain == '')
    		$domain = defined('SB_KEY_DOMAIN') ? SB_KEY_DOMAIN : SB_COOKIE_DOMAIN;

    	if (isset(self::$mSettings[$domain]) && isset(self::$mSettings[$domain][$setting_ident]))
        {
            $return = self::$mSettings[$domain][$setting_ident]['value'];
        }
    	elseif (isset(self::$mSettings['all']) && isset(self::$mSettings['all'][$setting_ident]))
        {
            $return = self::$mSettings['all'][$setting_ident]['value'];
        }
        else
        {
        	$res = sql_query('SELECT s_value, s_domain FROM sb_settings WHERE s_setting=? AND (s_domain=? OR s_domain=?)', $setting_ident, 'all', $domain);
            if ($res)
            {
            	$return = unserialize($res[0][0]);

            	if (!isset(self::$mSettings[$res[0][1]]))
            		self::$mSettings[$res[0][1]] = array();

            	self::$mSettings[$res[0][1]][$setting_ident] = array();
            	self::$mSettings[$res[0][1]][$setting_ident]['value'] = $return;
            }
        }

        return $return;
    }

	/**
     * Возвращает значение пользовательской настройки системы по ее идентификатору
     *
     * @param string $setting_ident Идентификатор настройки.
     *
     * @return mixed Значение настройки.
     */
    static function getUserSetting($setting_ident)
    {
    	$return = null;

        if (isset(self::$mUserSettings[$setting_ident]))
        {
            $return = self::$mUserSettings[$setting_ident]['value'];
        }
        elseif (isset($_SESSION['sbAuth']) && $_SESSION['sbAuth'] instanceof sbAuth && method_exists($_SESSION['sbAuth'], 'getUserId'))
        {
        	$res = sql_query('SELECT us_value FROM sb_users_settings WHERE us_user_id=?d AND us_setting=?', $_SESSION['sbAuth']->getUserId(), $setting_ident);
        	if ($res)
        	{
        		$return = unserialize($res[0][0]);
        		self::$mUserSettings[$setting_ident] = $return;
        	}
        }

        return $return;
    }

    /**
     * Изменяет значение настройки системы
     *
     * @param string $setting_ident Идентификатор настройки.
     * @param mixed $setting_value Значение настройки.
     * @param string $setting_domain Домен, для которого меняется значение настройки.
     */
    private static function editSetting($setting_ident, $setting_value, $setting_domain)
    {
    	if (!isset(self::$mSettings[$setting_domain]))
    	{
    		self::$mSettings[$setting_domain] = array();
    	}

    	if (!isset(self::$mSettings[$setting_domain][$setting_ident]))
    	{
    		self::$mSettings[$setting_domain][$setting_ident] = array();
    	}

    	self::$mSettings[$setting_domain][$setting_ident]['value'] = $setting_value;

    	$res = sql_query('SELECT COUNT(*) FROM sb_settings WHERE s_setting=? AND s_domain=?', $setting_ident, $setting_domain);
    	if ($res && $res[0][0] > 0)
    	{
        	sql_query('UPDATE sb_settings SET s_value = ? WHERE s_setting=? AND s_domain=?', serialize($setting_value), $setting_ident, $setting_domain);
    	}
    	else
    	{
    		sql_query('INSERT INTO sb_settings (s_value, s_setting, s_domain) VALUES (?, ?, ?)', serialize($setting_value), $setting_ident, $setting_domain);
    	}
    }

    /**
     * Изменяет значение пользовательской настройки системы
     *
     * @param string $setting_ident Идентификатор настройки.
     * @param mixed $setting_value Значение настройки.
     */
    private static function editUserSetting($setting_ident, $setting_value)
    {
    	if (isset($_SESSION['sbAuth']) && $_SESSION['sbAuth'] instanceof sbAuth && method_exists($_SESSION['sbAuth'], 'getUserId'))
    	{
    		if (!isset(self::$mUserSettings[$setting_ident]))
    		{
    			self::$mUserSettings[$setting_ident] = array();
    		}

    		self::$mUserSettings[$setting_ident]['value'] = $setting_value;

	        $res = sql_query('SELECT COUNT(*) FROM sb_users_settings WHERE us_setting=? AND us_user_id=?d', $setting_ident, $_SESSION['sbAuth']->getUserId());
	        if ($res && $res[0][0] > 0)
	        {
	        	sql_query('UPDATE sb_users_settings SET us_value=? WHERE us_setting=? AND us_user_id=?d', serialize($setting_value), $setting_ident, $_SESSION['sbAuth']->getUserId());
	        }
	        else
	        {
	        	sql_query('INSERT INTO sb_users_settings (us_value, us_setting, us_user_id) VALUES (?, ?, ?d)', serialize($setting_value), $setting_ident, $_SESSION['sbAuth']->getUserId());
	        }
    	}
    }

	/**
     * Изменяет допустимые значения системной настройки для модуля.
     *
     * @param string $setting_ident Уникальный идентификатор настройки. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора настройки.
     *
     * @param string $setting_type_values Для типов checkboxes, select, multiselect и radio - массив, ключи которого являются значениями
     *                                    опций, а значения - описаниями.
     *
     * @param strign $setting_domain Домен, для которого устанавливается настройка. Если передать 'all', настройка будет установлена
     *                              для всех доменов.
     */
    public function editSettingValues($setting_ident, $setting_type_values, $setting_domain=SB_COOKIE_DOMAIN)
    {
        if ($setting_ident == '' || !isset($this->mSettingsProps[$setting_domain]) || !isset($this->mSettingsProps[$setting_domain][$setting_ident]))
        {
            // если настройка не была до этого добавлена ничего не делаем
            return;
        }

        $this->mSettingsProps[$setting_domain][$setting_ident]['type_values'] = $setting_type_values;
    }

	/**
     * Изменяет допустимые значения системной настройки для модуля.
     *
     * @param string $setting_ident Уникальный идентификатор настройки. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора настройки.
     *
     * @param string $setting_type_values Для типов checkboxes, select, multiselect и radio - массив, ключи которого являются значениями
     *                                    опций, а значения - описаниями.
     */
    public function editUserSettingValues($setting_ident, $setting_type_values)
    {
        if ($setting_ident == '' || !isset($this->mUserSettingsProps[$setting_ident]))
        {
            // если настройка не была до этого добавлена ничего не делаем
            return;
        }

        $this->mUserSettingsProps[$setting_ident]['type_values'] = $setting_type_values;
    }

    /**
     * Изменение свойств настройки
     *
     * @param string $setting_ident Уникальный идентификатор настройки. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора настройки.
     *
     * @param string $setting_type Тип настройки. Возможны следующие типы настроек: string, number, checkbox, checkboxes, select,
     *                             multiselect, radio.
     *
     * @param string $setting_value Значение для настройки. Для типа string это строка. Для типа number - число. Для типа checkbox 1, если
     *                              настройка помечена по умолчанию и 0 в ином случае. Для типов checkboxes и multiselect -
     *                              массив значений из списка $setting_type_vlues.
     *
     * @param strign $setting_domain Домен, для которого устанавливается настройка. Если передать 'all', настройка будет установлена
     *                              для всех доменов.
     */
    private static function editSettingProps($setting_ident, $setting_type, $setting_value, $setting_domain)
    {
        if (!isset(self::$mSettings[$setting_domain][$setting_ident]['value']) && $setting_type != '')
        {
        	self::$mSettings[$setting_domain][$setting_ident]['value'] = str_replace('{DOMAIN}', $setting_domain, $setting_value);

        	if ($setting_domain == 'all')
        	{
        		$res = sql_query('SELECT s_value FROM sb_settings WHERE s_setting=? AND s_domain=?', $setting_ident, 'all');
                if (!$res)
                {
                    // если настройка не сохранена в таблице, добавляем ее туда
                    sql_query('INSERT INTO sb_settings (s_setting, s_value, s_domain) VALUES (?, ?, ?)', $setting_ident, serialize($setting_value), 'all');
                }
                else
                {
                    self::$mSettings['all'][$setting_ident]['value'] = unserialize($res[0][0]);
                }
        	}
        	else
        	{
            	foreach ($GLOBALS['sb_domains'] as $domain => $tmp)
            	{
                    $res = sql_query('SELECT s_value FROM sb_settings WHERE s_setting=? AND s_domain=?', $setting_ident, $domain);
                    if (!$res)
                    {
                        // если настройка не сохранена в таблице, добавляем ее туда
                        sql_query('INSERT INTO sb_settings (s_setting, s_value, s_domain) VALUES (?, ?, ?)', $setting_ident, serialize($setting_value), $domain);
                    }
                    else
                    {
                    	self::$mSettings[$setting_domain][$setting_ident]['value'] = unserialize($res[0][0]);
                    }
                }
            }
        }
    }

    /**
     * Изменение свойств пользовательской настройки
     *
     * @param string $setting_ident Уникальный идентификатор настройки. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора настройки.
     *
     * @param string $setting_type Тип настройки. Возможны следующие типы настроек: string, number, checkbox, checkboxes, select,
     *                             multiselect, radio.
     *
     * @param string $setting_value Значение для настройки. Для типа string это строка. Для типа number - число. Для типа checkbox 1, если
     *                              настройка помечена по умолчанию и 0 в ином случае. Для типов checkboxes, select, multiselect и radio -
     *                              массив значений из списка $setting_type_vlues.
     */
    private static function editUserSettingProps($setting_ident, $setting_type, $setting_value)
    {
        if (!isset(self::$mUserSettings[$setting_ident]['value']) && $setting_type != '')
        {
        	self::$mUserSettings[$setting_ident]['value'] = $setting_value;

	        $res = sql_param_query('SELECT us_value FROM sb_users_settings WHERE us_setting=? AND us_user_id=?d', $setting_ident, $_SESSION['sbAuth']->getUserId());
	        if (!$res)
	        {
	            sql_param_query('INSERT INTO sb_users_settings (us_setting, us_value, us_user_id) VALUES (?, ?, ?d)', $setting_ident, serialize($setting_value), $_SESSION['sbAuth']->getUserId());
	        }
	        else
	        {
	            self::$mUserSettings[$setting_ident]['value'] = unserialize($res[0][0]);
	        }
        }
    }

    /**
     * Устанавливает значение настройки системы
     *
     * Метод отрабатывает, только если у пользователя есть доступ к модулю настроек системы.
     *
     * @param string $setting_ident Идентификатор настройки.
     * @param mixed $setting_value Значение настройки.
     * @param string $setting_domain Домен, для которого меняется значение настройки.
     */
    public function setSetting($setting_ident, $setting_value, $setting_domain)
    {
    	if ($this->isRightAvailable('pl_settings', 'read'))
    	{
    		self::editSetting($setting_ident, $setting_value, $setting_domain);
    	}
    }

    /**
     * Устанавливает пользовательскую настройку
     *
     * Метод отрабатывает, только если у пользователя есть доступ к модулю настроек интерфейса системы.
     *
     * @param string $setting_ident Идентификатор настройки.
     * @param mixed $setting_value Значение настройки.
     */
    public function setUserSetting($setting_ident, $setting_value)
    {
    	if ($this->isRightAvailable('pl_user_settings', 'read'))
    	{
    		self::editUserSetting($setting_ident, $setting_value);
    	}
    }

	/**
     * Добавляет системную настройку для модуля.
     *
     * @param string $tab_name Название закладки, в которую будет добавлена настройка.
     *
     * @param string $setting_ident Уникальный идентификатор настройки. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора настройки.
     *
     * @param string $setting_desc Описание настройки.
     *
     * @param string $setting_type Тип настройки. Возможны следующие типы настроек: string, number, checkbox, checkboxes, select,
     *                             multiselect, radio, password.
     *
     * @param string $setting_type_values Для типов checkboxes, select, multiselect и radio - массив, ключи которого являются значениями
     *                                    опций, а значения - описаниями.
     *
     * @param string $setting_value Значение для настройки. Для типа string это строка. Для типа number - число. Для типа checkbox 1, если
     *                              настройка помечена по умолчанию и 0 в ином случае. Для типов checkboxes и multiselect -
     *                              массив значений из списка $setting_type_vlues.
     *
     * @param strign $setting_domain Домен, для которого устанавливается настройка. Если передать 'all', настройка будет установлена
     *                              для всех доменов.
     *
     * @param bool $check TRUE, если это настройка ядра системы и должна быть доступна всем пользователям системы, FALSE в том случае,
     *                       если настройка не должна быть доступна пользователям, не имеющим прав доступа к модулю.
     */
    public function addSetting($tab_name, $setting_ident, $setting_desc='', $setting_type='', $setting_type_values='', $setting_value='', $setting_domain=SB_COOKIE_DOMAIN, $check=true)
    {
        if (!$this->mCurAvailableKey && $check || $tab_name == '' || $setting_ident == '')
        {
            // если модуль не доступен в системе и добавляется не настройка ядра, ничего не делаем
            return;
        }

        if (!isset($this->mSettingsProps[$setting_domain]))
        	$this->mSettingsProps[$setting_domain] = array();

        if (!isset($this->mSettingsProps[$setting_domain][$setting_ident]))
        	$this->mSettingsProps[$setting_domain][$setting_ident] = array();

        $this->mSettingsProps[$setting_domain][$setting_ident]['tab_name'] = $tab_name;
        $this->mSettingsProps[$setting_domain][$setting_ident]['desc'] = $setting_desc;
        $this->mSettingsProps[$setting_domain][$setting_ident]['type'] = $setting_type;
        $this->mSettingsProps[$setting_domain][$setting_ident]['type_values'] = $setting_type_values;

        self::editSettingProps($setting_ident, $setting_type, $setting_value, $setting_domain);
    }

	/**
     * Добавляет пользовательскую настройку для модуля.
     *
     * @param string $tab_name Название закладки, в которую будет добавлена настройка.
     *
     * @param string $setting_ident Уникальный идентификатор настройки. Формируется из идентификатора модуля, знака _ и переданного
     *                              идентификатора настройки.
     *
     * @param string $setting_desc Описание настройки.
     *
     * @param string $setting_type Тип настройки. Возможны следующие типы настроек: string, number, checkbox, checkboxes, select,
     *                             multiselect, radio, password
     *
     * @param string $setting_type_values Для типов checkboxes, select, multiselect и radio -  - массив, ключи которого являются значениями
     *                                    опций, а значения - описаниями.
     *
     * @param string $setting_value Значение для настройки. Для типа string это строка. Для типа number - число. Для типа checkbox 1, если
     *                              настройка помечена по умолчанию и 0 в ином случае. Для типов checkboxes, select, multiselect и radio -
     *                              массив значений из списка $setting_type_vlues.
     *
     * @param string $check FALSE, если это настройка ядра системы и должна быть доступна всем пользователям системы, TRUE в том случае,
     *                       если настройка не должна быть доступна пользователям, не имеющим прав доступа к модулю.
     */
    public function addUserSetting($tab_name, $setting_ident, $setting_desc='', $setting_type='', $setting_type_values='', $setting_value='', $check=true)
    {
        if (!$this->mCurAvailableKey && $check || $tab_name == '' || $setting_ident == '')
        {
            // если модуль не доступен в системе и добавляется не настройка ядра, ничего не делаем
            return;
        }

       	if (!isset($this->mUserSettingsProps[$setting_ident]))
    		$this->mUserSettingsProps[$setting_ident] = array();

        $this->mUserSettingsProps[$setting_ident]['tab_name'] = $tab_name;
        $this->mUserSettingsProps[$setting_ident]['desc'] = $setting_desc;
        $this->mUserSettingsProps[$setting_ident]['type'] = $setting_type;
        $this->mUserSettingsProps[$setting_ident]['type_values'] = $setting_type_values;

		self::editUserSettingProps($setting_ident, $setting_type, $setting_value);
    }

    // ------------------------------------------------------------------------------------ //

    /**
     * Возвращает массив, содержащий кнопки панели инструментов для текущего пользователя
     *
     * <code>
     * // иконка кнопки
     * $result[$i]['icon'] = $value['icon'];
     * // всплывающая подсказка для кнопки
     * $result[$i]['desc'] = $value['desc'];
     * // событие, вызываемое при щелчке по кнопке
     * $result[$i]['event'] = $value['event'];
     * </code>
     * @return array
     */
    public function getToolbarIcons()
    {
        $result = array();

        // вытаскиваем из настроек пользователя все иконки, которые надо отобразить на панели инструментов
        $icons = $this->getUserSetting('sb_toolbar_icons');

        if ($icons != '')
        {
            $i = 0;
            foreach ($this->mMenu as $value)
            {
            	if (strtolower(substr($value['event'], 0, 10)) != 'javascript')
            	{
            		$href = $value['event'];
            		$target = 'content';
            	}
            	else
            	{
            		$href = substr($value['event'], 11);
            		$target = '_blank';
            	}

            	$event = sb_str_replace(SB_CMS_CONTENT_FILE.'?event=', '', $value['event']);
                if (in_array($event, $icons))
                {
                    $result[$i] = array();
                    $result[$i]['icon'] = $value['icon'];
                    $desc = explode('>', $value['item']);
                    $result[$i]['desc'] = $desc[count($desc) - 1];
                    $result[$i]['event'] = $event;
                    $result[$i]['href'] = $href;
                    $result[$i]['target'] = $target;
                    $i++;
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает массив, содержащий информацию о кнопке панели инструментов
     *
     * <code>
     * // иконка кнопки
     * $result['icon']
     * // всплывающая подсказка для кнопки
     * $result['desc']
     * </code>
     *
     * @param string $event Событие, для которого пытаемся определить кнопку.
     *
     * @return array
     */
    public function getQuickToolbarIcons($event)
    {
        foreach ($this->mMenu as $value)
        {
        	$value['event'] = sb_str_replace(SB_CMS_CONTENT_FILE.'?event=', '', $value['event']);

        	$menu_event = explode('&', $value['event']);
            if ($menu_event[0] == $event && $value['icon'] != '')
            {
                $result = array();
                $result['icon'] = $value['icon'];
                $desc = explode('>', $value['item']);
                $result['desc'] = $desc[count($desc) - 1];
                $result['event'] = $value['event'];

                return $result;
            }
        }

        return false;
    }

    /**
     * Возвращает массив, содержащий функцию обработки события и файлы, которые необходимо проинклудить,
     * либо FALSE, если у пользователя недостаточно прав на вызов данного события.
     *
     * <code>
     * $result = array();
     * // файл модуля, содержащий функцию-обработчик события
	 * $result['include_file'] = ...;
	 * // языковой файл модуля
	 * $result['lang_file'] = ...;
	 * // имя функции-обработчика события
     * $result['function'] = ...;
     * </code>
     * @param string $event Событие, для которого пытаемся определить функцию-обработчик.
     * @return mixed Массив, содержащий функцию обработки события и файлы, которые необходимо проинклудить,
     *               либо FALSE, если у пользователя недостаточно прав на вызов данного события.
     */
    public function getEventFunction($event)
    {
        $plugin = $this->getEventPlugin($event);
        if (!$plugin)
        {
            // вызываемое событие не зарегистрированно в системе
            return false;
        }

        $result = array();
		$result['include_file'] = '';
		$result['lang_file'] = '';
        $result['function'] = '';

        if ($this->mEvents[$event]['system'])
        {
            // если событие системное, то нужно только название функции
            $result['function'] = $this->mEvents[$event]['function'];

            return $result;
        }
        else
        {
            if (!$this->isEventAvailable($event))
                return false;

		    $result['include_file'] = $this->mPlugins[$plugin]['include_file'];
		    $result['lang_file'] = $this->mPlugins[$plugin]['lang_file'];
            $result['function'] = $this->mEvents[$event]['function'];

            return $result;
        }
    }

    /**
     * Возвращает идентификатор модуля, к которому принадлежит событие, или FALSE, если событие
     * не зарегистрированно в системе
     *
     * @param string $event Идентификатор события.
     * @return mixed Идентификатор модуля, к которому принадлежит событие, или FALSE, если событие
     *               не зарегистрированно в системе.
     */
    public function getEventPlugin($event)
    {
        $event = explode('&', $event);
        $event = $event[0];

        if (isset($this->mEvents[$event]))
            return $this->mEvents[$event]['plugin'];
        else
            return false;
    }

    /**
     * Возвращает подсказку и индекс подсказки для события.
     *
     * @param string $event Идентификатор события.
     * @param int $index Идентификатор текущей подсказки.
     * @return mixed Массив, первый элемент которого - подсказка, второй - индекс подсказки. Или FALSE, если у события нет подсказок.
     */
    public function getEventTip($event, $index=-1)
    {
        if (isset($this->mEvents[$event]) && isset($GLOBALS['sb_character_help'][$event]) && count($GLOBALS['sb_character_help'][$event]) > 0)
        {
            $num = count($GLOBALS['sb_character_help'][$event]);
            if ($index != -1)
            {
                if ($index + 1 > $num - 1)
                    $index = 0;
                else
                    $index++;
            }
            else
            {
                $index = rand(0, $num-1);
            }

            return array($GLOBALS['sb_character_help'][$event][$index], $index);
        }
        else
        {
            return false;
        }
    }

    /**
     * Возвращает массив inc-файлов, которые инклудятся всегда, независимо от вызываемого события
     *
     * @return mixed Массив inc-файлов, либо FALSE, если такие файлы не зарегистрированны.
     */
    public function getIncFiles()
    {
        if (count($this->mIncFiles) != 0)
            return $this->mIncFiles;
        else
            return false;
    }

    /**
     * Возвращает массив lng-файлов, которые инклудятся всегда, независимо от вызываемого события.
     * В них обычно хранятся языковые константы для inc-файлов.
     *
     * @return mixed Массив lng-файлов, либо FALSE, если такие файлы не зарегистрированны.
     */
    public function getIncLangFiles()
    {
        if (count($this->mIncLangFiles) != 0)
            return $this->mIncLangFiles;
        else
            return false;
    }

    /**
     * Возвращает название события
     *
     * @param string $event Идентификатор события.
     * @return mixed Название события или FALSE, если такого события нет.
     */
    public function getEventTitle($event) // Функция вывода заголовка плагина
    {
  	    if (isset($this->mEvents[$event]))
    	    return $this->mEvents[$event]['title'];
    	else
    	    return false;
    }

    /**
     * Возвращает URL справочного файла модуля, к которому принадлежит указанное событие
     *
     * @param string $event Идентификатор события.
     * @return string URL справочного файла, или пустая строка, если не удалось определить URL.
     */
    public function getPluginHelp($event)
    {
        $plugin = $this->getEventPlugin($event);
        if ($plugin && isset($this->mPlugins[$plugin]))
        {
            return $this->mPlugins[$plugin]['help_url'];
        }

        return '';
    }

    /**
     * Возвращает название модуля
     *
     * @param string $plugin Идентификатор модуля или события.
     * @return string Название модуля, или пустая строка.
     */
    public function getPluginTitle($plugin)
    {
        if (isset($this->mPlugins[$plugin]))
        {
            return $this->mPlugins[$plugin]['title'];
        }
        else
        {
            $plugin = $this->getEventPlugin($plugin);
            if ($plugin && isset($this->mPlugins[$plugin]))
            {
                return $this->mPlugins[$plugin]['title'];
            }
        }

        return '';
    }

    /**
     * Возвращает иконку модуля
     *
     * @param string $plugin Идентификатор модуля или события.
     * @return string Иконка модуля, или пустая строка.
     */
    public function getPluginIcon($plugin)
    {
        if (isset($this->mPlugins[$plugin]))
        {
            return $this->mPlugins[$plugin]['icon'];
        }
        else
        {
            $plugin = $this->getEventPlugin($plugin);
            if ($plugin && isset($this->mPlugins[$plugin]))
            {
                return $this->mPlugins[$plugin]['icon'];
            }
        }

        return '';
    }

    /**
     * Возвращает ассоциативный массив с описанием модулей системы
     *
     * Ключ массива - идентификатор модуля. Значение - ассоциативный массив, в котором присутствуют следующие ключи:
     *
     * <i>title</i> - название модуля
     * <i>icon</i> - иконка модуля
     * <i>rights</i> - описание прав модуля
     *
     * @return array Ассоциативный массив с описанием модулей системы. Ключ масива - идентификатор модуля, значение - массив с описанием.
     */
    public function getPluginsInfo()
    {
        $res = array();
        foreach($this->mPlugins as $key => $value)
        {
            if (!isset($value['core']) || !$value['core'])
            {
                $res[$key] = array();
                $res[$key]['title'] = $value['title'];
                $res[$key]['icon'] = $value['icon'];
                $res[$key]['rights'] = $value['rights'];
                $res[$key]['check_user'] = $value['check_user'];
                $res[$key]['table'] = isset($value['table']) ? $value['table'] : '';
                $res[$key]['use_categs'] = isset($value['use_categs']) ? $value['use_categs'] : 0;
                $res[$key]['use_cache'] = isset($value['use_cache']) ? $value['use_cache'] : 0;
                $res[$key]['use_workflow'] = isset($value['use_workflow']) ? $value['use_workflow'] : 0;
                $res[$key]['main_event'] = isset($value['main_event']) ? $value['main_event'] : '';
                $res[$key]['cat_id'] = isset($value['cat_id']) ? $value['cat_id'] : '';
                $res[$key]['meta_data'] = self::getPluginSqlParams($key);
                // Если модуль из конструктора модулей - получаем настройки (иначе false)
                $res[$key]['pm_settings'] = $this->getPmPluginsSettings($key);
            }
        }

        return $res;
    }

    private function getPmPluginsSettings($key)
    {
        $result = false;
        // Если ключ модуля из конструктора модулей
        if (sb_strpos($key, 'pl_plugin_') !== false)
        {
            // Если pmPluginsSettings не инициализировано - достаем данные из базы
            if (!isset($this->pmPluginsSettings) && !is_array($this->pmPluginsSettings)) {
                $this->pmPluginsSettings = array();
                $res = sql_query('SELECT pm_id, pm_settings, pm_categs_settings, pm_elems_settings FROM sb_plugins_maker');
                if ($res) {
                    foreach ($res as $row) {
                        $pm_ident = 'pl_plugin_'.$row[0];
                        $pm_settings	= (!empty($row[1])) ? unserialize($row[1]) : false;
                        $categs_settings = (!empty($row[2])) ? unserialize($row[2]) : false;
                        $elems_settings	= (!empty($row[3])) ? unserialize($row[3]) : false;
                        $this->pmPluginsSettings[$pm_ident] = array(
                            'settings'          => $pm_settings,
                            'categs_settings'   => $categs_settings,
                            'elems_settings'    => $elems_settings
                        );
                    }
                }
            }

            if (is_array($this->pmPluginsSettings) && array_key_exists($key, $this->pmPluginsSettings)) {
                $result = $this->pmPluginsSettings[$key];
            }
        }
        return $result;
    }

    /**
     * Возвращает массив с зарегистрированными элементами модулей
     *
     * @return array
     */
    public function getElems()
    {
        return $this->mElems;
    }

    /**
     * Возвращает JavaScript-код для построения меню системы
     *
     * @return string JavaScript-код для построения меню системы.
     */
    public function getMenu()
    {
        $menu = array();
        $first_level = 0;
        foreach ($this->mMenu as $value)
        {
            $menu_items = explode('>', $value['item']);
            if (!isset($menu[$menu_items[0]]))
            {
                $menu[$menu_items[0]] = array();
                $menu[$menu_items[0]]['name'] = 'root';
                $menu[$menu_items[0]]['len']  = 0;
                $menu[$menu_items[0]]['href'] = array();
                $menu[$menu_items[0]]['icon'] = array();
                $menu[$menu_items[0]]['item'] = array();
            }

            $len = count($menu_items);
            if ($len > 2)
            {
                $menu_prev_name = $menu_items[0];

                for ($j = 0; $j < $len - 2; $j++)
                {
                    $menu_name = $menu_prev_name.'>'.$menu_items[$j+1];

                    if (!isset($menu[$menu_name]))
                    {
                        $item_len = sb_strlen($menu_items[$j+1]);
                        if ($menu[$menu_prev_name]['len'] < $item_len)
                        {
                            $menu[$menu_prev_name]['len'] = $item_len;
                        }

                        $menu[$menu_prev_name]['href'][] = 'm'.$first_level;
                        $menu[$menu_prev_name]['icon'][] = '';
                        $menu[$menu_prev_name]['item'][] = '<div class="menu_icon">&nbsp;</div><div class="menu_text">'.$menu_items[$j+1].'</div>';

                        $menu[$menu_name] = array();
                        $menu[$menu_name]['name'] = 'm'.$first_level;
                        $menu[$menu_name]['len']  = 0;
                        $menu[$menu_name]['href'] = array();
                        $menu[$menu_name]['icon'] = array();
                        $menu[$menu_name]['item'] = array();

                        $first_level++;
                    }

                    $menu_prev_name = $menu_name;
                }

                $item_len = sb_strlen($menu_items[$len - 1]);
                if ($menu[$menu_name]['len'] < $item_len)
                {
                    $menu[$menu_name]['len'] = $item_len;
                }

                $menu[$menu_name]['href'][] = $value['event'];
                $menu[$menu_name]['item'][] = '<div class="menu_icon">'.($value['icon'] != '' ? '<img src="'.$value['icon'].'" class="menu_icon">' : '&nbsp;').'</div><div class="menu_text">'.$menu_items[$len - 1].'</div>';
            }
            else
            {
                $item_len = sb_strlen($menu_items[1]);
                if ($menu[$menu_items[0]]['len'] < $item_len)
                {
                    $menu[$menu_items[0]]['len'] = $item_len;
                }

               	$menu[$menu_items[0]]['href'][] = $value['event'];
                $menu[$menu_items[0]]['item'][] = '<div class="menu_icon">'.($value['icon'] != '' ? '<img src="'.$value['icon'].'" class="menu_icon">' : '&nbsp;').'</div><div class="menu_text">'.$menu_items[1].'</div>';
            }
        }

        $menu_str = '';
        $result = 'startMenu("root", false, 96, 46, 18, hBar, "navmenu", true);'."\n";

        foreach ($menu as $key => $value)
        {
           if ($value['name'] == 'root')
           {
               $result .= "addItem('".sb_str_replace("'", "\\'", $key)."', 'm$first_level', 'sm:', null, ".(sb_strlen($key) * 8 + 20).");\n";

               $menu_str .= "startMenu('m$first_level', true, 0, 'content.page.scrollY()', ".($value['len'] * 7 + 60).", subM, 'content', false);\n";
               for ($j = 0; $j < count($menu[$key]['item']); $j++)
               {
                   if ($menu[$key]['href'][$j][0] != 'm')
                   {
                       if (strtolower(substr($menu[$key]['href'][$j], 0, 10)) != 'javascript')
                       	   $menu_str .= "addItem('".sb_str_replace("'", "\\'", $menu[$key]['item'][$j])."', '".$menu[$key]['href'][$j]."', 'content');\n";
                       else
                       	   $menu_str .= "addItem('".sb_str_replace("'", "\\'", $menu[$key]['item'][$j])."', '".substr($menu[$key]['href'][$j], 11)."', '_blank');\n";
                   }
                   else
                   {
                       $menu_str .= "addItem('".sb_str_replace("'", "\\'", $menu[$key]['item'][$j])."', '".$menu[$key]['href'][$j]."', 'sm:');\n";
                   }
               }

               $first_level++;
           }
           else
           {
               $prev_menu = sb_substr($key, 0, sb_strrpos($key, '>'));

               $menu_str .= "startMenu('".$value['name']."', true, ".($menu[$prev_menu]['len'] * 7 + 64).", -2, ".($value['len'] * 7 + 60).", subM, 'content', false);\n";

               for ($j = 0; $j < count($menu[$key]['item']); $j++)
               {
               	   if (strtolower(substr($menu[$key]['href'][$j], 0, 10)) != 'javascript')
                       $menu_str .= "addItem('".sb_str_replace("'", "\\'", $menu[$key]['item'][$j])."', '".$menu[$key]['href'][$j]."', '".(substr($menu[$key]['href'][$j], 0, 1) == 'm' ? 'sm:' : 'content')."');\n";
                   else
                   	   $menu_str .= "addItem('".sb_str_replace("'", "\\'", $menu[$key]['item'][$j])."', '".substr($menu[$key]['href'][$j], 11)."', '_blank');\n";
               }
           }
        }

        return $result.$menu_str;
    }

    public function getExportImportInfo()
    {
        return $this->mExportImport;
    }

    /**
     * Проверяет, разрешен ли модуль текущему пользователю
     *
     * @param string $pl_name Идентификатор модуля.
     * @return bool TRUE, если модуль разрешен, и FALSE, если модуль запрещен.
     */
    public function checkForUser($pl_name)
    {
        if ($_SESSION['sbAuth']->isAdmin())
        {
            /**
             * Если текущий пользователь - админ, то ему доступны всегда все модули
             */
            return true;
        }

        $res = sql_query('SELECT COUNT(*) FROM sb_users_rights WHERE ur_plugin=? AND ur_cat_id IN (?a) AND ur_plugin_rights <> ?', $pl_name, $_SESSION['sbAuth']->getUserGroups(), '');

        if($res[0][0] == 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Проверяет, разрешен ли модуль в ключе системы
     *
     * @param string $pl_name Идентификатор модуля.
     * @return bool TRUE, если модуль разрешен, и FALSE, если модуль запрещен.
     */
    private function checkForKey($pl_name)
    {
        if (!$_SESSION['sbAuth']->checkPlugin($pl_name))
            return false;
        else
            return true;
    }

    /**
     * Проверяет, есть ли зарегистрированные пользовательские модули
     *
     * @return bool TRUE, если модули есть, и FALSE в ином случае.
     */
    public function isRegisteredPlugins()
    {
        return $this->mIsRegistered;
    }

    /**
     * Проверяет, доступно ли событие текущему пользователю
     *
     * @param string $event Идентификатор события.
     * @return bool TRUE, если событие доступно, и FALSE в ином случае.
     */
    public function isEventAvailable($event)
    {
        $plugin = $this->getEventPlugin($event);
        if (!$plugin || !isset($this->mPlugins[$plugin]['check_user']))
        {
            // вызываемое событие не зарегистрированно в системе
            return false;
        }

        if ($this->mPlugins[$plugin]['check_user'] && !$_SESSION['sbAuth']->isAdmin())
        {
            // проверяем права пользователя
            $res = sql_param_query('SELECT ur_plugin_rights FROM sb_users_rights WHERE ur_plugin=? AND ur_cat_id IN (?a) AND ur_plugin_rights <> ?', $plugin, $_SESSION['sbAuth']->getUserGroups(), '');

            if (!$res)
            {
                // вызываемое событие не доступно пользователю
                return false;
			}

			$rights = array();
			foreach($res as $val)
			{
				$rights = array_merge($rights, explode('|', $val[0]));
			}
			$rights = array_unique($rights);

            $event = explode('&', $event);
        	$event = $event[0];

            $event_rights = explode('|', $this->mEvents[$event]['rights']);

            foreach ($event_rights as $value)
            {
                if (in_array($value, $rights))
                {
                    return true;
                }
            }

            // требуемого уровня прав нет у пользователя
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Проверяет, есть ли у пользователя указанные права доступа к модулю
     *
     * @param string $plugin Идентификатор модуля.
     * @param string $rights Права доступа. Разделяются через |.
     * @return bool TRUE, если у пользователя есть права доступа, и FALSE в ином случае.
     */
    public function isRightAvailable($plugin, $rights)
    {
        if (!isset($this->mPlugins[$plugin]) || !isset($this->mPlugins[$plugin]['check_user']))
        {
            // указанный модуль не зарегистрирован в системе
            return false;
        }

        if ($this->mPlugins[$plugin]['check_user'] && !$_SESSION['sbAuth']->isAdmin())
        {
            // проверяем права пользователя
            $res = sql_query('SELECT ur_plugin_rights FROM sb_users_rights WHERE ur_plugin=? AND ur_cat_id IN (?a) AND ur_plugin_rights <> ?', $plugin, $_SESSION['sbAuth']->getUserGroups(), '');

            if (!$res)
            {
                // информации о правах доступа нет
                return false;
            }

            $rights = explode('|', $rights);
            foreach($res as $value)
			{
				list($user_rights) = $value;
				$user_rights = explode('|', $user_rights);

				foreach($rights as $value2)
				{
					if(in_array($value2, $user_rights))
						return true;
				}
			}

            // требуемого уровня прав нет у пользователя
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Проверяет, есть ли модуль в системе (в ключе)
     *
     * @param string $plugin Идентификатор модуля.
     * @return bool TRUE, если модуль доступен, и FALSE в ином случае.
     */
    public function isPluginAvailable($plugin)
    {
        return isset($this->mPlugins[$plugin]);
    }

	/**
     * Проверяет, есть ли модуль в цепочке публикаций
     *
     * @param string $plugin Идентификатор модуля.
     * @return bool TRUE, если модуль использует цепочку публикаций, и FALSE в ином случае.
     */
    public function isPluginInWorkflow($plugin)
    {
        return isset($this->mPlugins[$plugin]) && isset($this->mPlugins[$plugin]['use_workflow']) && $this->mPlugins[$plugin]['use_workflow'];
    }

    /**
     * Метод возвращает sql данные для плагина
     *
     * @param string $plugin идентификатор плагина
     */
    static function getPluginSqlParams($plugin)
    {
        $params = array();
        if ($plugin == 'pl_pages')
        {
            $params = array('table' => 'sb_pages',
                'id' => 'p_id',
                'title' => 'p_name',
                'sort' => 'p_name',
                'show' => '');
        }
        elseif ($plugin == 'pl_news')
        {
            $params = array('table' => 'sb_news',
                'id' => 'n_id',
                'title' => 'n_title',
                'sort' => 'n_sort',
                'date' => 'n_date',
                'show' => 'n_active');
        }
        elseif ($plugin == 'pl_imagelib')
        {
            $params = array('table' => 'sb_imagelib',
                'id' => 'im_id',
                'title' => 'im_title',
                'sort' => 'im_order_num',
                'date' => 'im_date',
                'show' => 'im_gal');
        }
        elseif ($plugin == 'pl_services_rutube')
        {
            $params = array('table' => 'sb_services_rutube',
                'id' => 'ssr_id',
                'title' => 'ssr_name',
                'sort' => 'ssr_date',
                'date' => 'ssr_date',
                'show' => 'ssr_show');
        }
        elseif ($plugin == 'pl_faq')
        {
            $params = array('table' => 'sb_faq',
                'id' => 'f_id',
                'title' => 'f_id',
                'sort' => 'f_sort',
                'date' => 'f_date',
                'show' => 'f_show');
        }
        elseif ($plugin == 'pl_polls')
        {
            $params = array('table' => 'sb_polls',
                'id' => 'sp_id',
                'title' => 'sp_question',
                'sort' => 'sp_sort',
                'show' => 'sp_active');
        }
        elseif (preg_match('/^pl_plugin_[0-9]+$/i', $plugin))
        {
            $p_id = intval(sb_str_replace('pl_plugin_', '', $plugin));
            $params = array('table' => 'sb_plugins_' . $p_id,
                'id' => 'p_id',
                'title' => 'p_title',
                'sort' => 'p_sort',
                'show' => 'p_active');
        }
        elseif ($plugin == 'pl_banners')
        {
            $params = array('table' => 'sb_banners',
                'id' => 'sb_id',
                'title' => 'sb_name',
                'sort' => 'sb_priority',
                'show' => 'sb_active');
        }
        elseif ($plugin == 'pl_maillist')
        {
            $params = array('table' => 'sb_maillist',
                'id' => 'm_id',
                'title' => 'm_name',
                'show' => 'm_active');
        }
        elseif ($plugin == 'pl_texts')
        {
            $params = array('table' => 'sb_texts',
                'id' => 't_id',
                'title' => 't_name',
                'show' => 't_status');
        }
        elseif ($plugin == 'pl_tester')
        {
            $params = array('table' => 'sb_tester_questions',
                'id' => 'stq_id',
                'title' => 'stq_question',
                'show' => 'stq_show');
        }
        elseif ($plugin == 'pl_menu')
        {
            $params = array('table' => 'sb_menu',
                'id' => 'm_id',
                'title' => 'm_title',
                'show' => 'm_show');
        }
        elseif ($plugin == 'pl_forum')
        {
            $params = array('table' => 'sb_forum',
                'id' => 'f_id',
                'title' => 'f_author',
                'show' => 'f_show');
        }
        elseif ($plugin == 'pl_rss')
        {
            $params = array('table' => 'sb_rss',
                'id' => 'r_id',
                'title' => 'r_title',
                'show' => 'r_active');
        }
        elseif ($plugin == 'pl_banners_design')
        {
            $params = array('table' => 'sb_banners_temps',
                'id' => 'sbt_id',
                'title' => 'sbt_title',
                'show' => 'sbt_active');
        }
        elseif ($plugin == 'pl_basket_mini_basket')
        {
            $params = array('table' => 'sb_plugins_temps_form',
                'id' => 'ptf_id',
                'title' => 'ptf_title');
        }
        elseif ($plugin == 'pl_calendar')
        {
            $params = array('table' => 'sb_calendar_temps',
                'id' => 'ct_id',
                'title' => 'ct_title');
        }
        elseif ($plugin == 'pl_clouds')
        {
            $params = array('table' => 'sb_clouds_temps',
                'id' => 'ct_id',
                'title' => 'ct_title');
        }
        elseif ($plugin == 'pl_faq_list')
        {
            $params = array('table' => 'sb_faq_temps_list',
                'id' => 'fdl_id',
                'title' => 'fdl_title');
        }
        elseif ($plugin == 'pl_faq_full')
        {
            $params = array('table' => 'sb_faq_temps_full',
                'id' => 'ftf_id',
                'title' => 'ftf_title');
        }
        elseif ($plugin == 'pl_faq_form' || $plugin == 'pl_faq_filter')
        {
            $params = array('table' => 'sb_faq_temps_form',
                'id' => 'sftf_id',
                'title' => 'sftf_title');
        }
        elseif ($plugin == 'pl_faq_design_categs' || $plugin == 'pl_imagelib_design_categ' || $plugin == 'pl_news_design_categs' ||
        (sb_strpos($plugin, 'pl_plugin_') !== false && sb_strpos($plugin, '_design_categs') !== false) ||
        $plugin == 'pl_polls_design_categs' || $plugin == 'pl_services_rutube_design_categs' ||
        $plugin == 'pl_tester_categs_design')
        {
            $params = array('table' => 'sb_categs_temps_list',
                'id' => 'ctl_id',
                'title' => 'ctl_title');
        }
        elseif ($plugin == 'pl_faq_design_sel_cat' || $plugin == 'pl_imagelib_design_categ_select' || $plugin == 'pl_news_design_sel_cat' ||
        (sb_strpos($plugin, 'pl_plugin_') !== false && sb_strpos($plugin, '_design_sel_cat') !== false) ||
        $plugin == 'pl_polls_design_selcat' || $plugin == 'pl_services_rutube_design_selcat' ||
        $plugin == 'pl_tester_selcat_design')
        {
            $params = array('table' => 'sb_categs_temps_full',
                'id' => 'ctf_id',
                'title' => 'ctf_title');
        }
        elseif ($plugin == 'pl_faq_filter')
        {
            $params = array('table' => 'sb_categs_temps_full',
                'id' => 'ctf_id',
                'title' => 'ctf_title');
        }
        elseif ($plugin == 'pl_forum_messages')
        {
            $params = array('table' => 'sb_forum_temps_messages',
                'id' => 'ftm_id',
                'title' => 'ftm_name');
        }
        elseif ($plugin == 'pl_forum_form_themes')
        {
            $params = array('table' => 'sb_forum_form_theme',
                'id' => 'sftf_id',
                'title' => 'sftf_title');
        }
        elseif ($plugin == 'pl_forum_filter' || $plugin == 'pl_forum_form_msg')
        {
            $params = array('table' => 'sb_forum_form_msg',
                'id' => 'sffm_id',
                'title' => 'sffm_title');
        }
        elseif ($plugin == 'pl_forum_subjects')
        {
            $params = array('table' => 'sb_forum_temps_subjects',
                'id' => 'fts_id',
                'title' => 'fts_name');
        }
        elseif ($plugin == 'pl_forum_categs')
        {
            $params = array('table' => 'sb_forum_temps_categs',
                'id' => 'ftc_id',
                'title' => 'ftc_name');
        }
        elseif ($plugin == 'pl_imagelib_list')
        {
            $params = array('table' => 'sb_imagelib_temps_list',
                'id' => 'itl_id',
                'title' => 'itl_title');
        }
        elseif ($plugin == 'pl_imagelib_full')
        {
            $params = array('table' => 'sb_imagelib_temps_full',
                'id' => 'itf_id',
                'title' => 'itf_title');
        }
        elseif ($plugin == 'pl_imagelib_form' || $plugin == 'pl_imagelib_filter')
        {
            $params = array('table' => 'sb_imagelib_temps_form',
                'id' => 'itfrm_id',
                'title' => 'itfrm_name');
        }
        elseif ($plugin == 'pl_maillist_design_list')
        {
            $params = array('table' => 'sb_maillist_temps',
                'id' => 'smt_id',
                'title' => 'smt_title');
        }
        elseif ($plugin == 'pl_menu_path_temps')
        {
            $params = array('table' => 'sb_menu_path_temps',
                'id' => 'mpt_id',
                'title' => 'mpt_title');
        }
        elseif ($plugin == 'pl_menu_temps')
        {
            $params = array('table' => 'sb_menu_temps',
                'id' => 'mt_id',
                'title' => 'mt_title');
        }
        elseif ($plugin == 'pl_news_list')
        {
            $params = array('table' => 'sb_news_temps_list',
                'id' => 'ndl_id',
                'title' => 'ndl_title');
        }
        elseif ($plugin == 'pl_news_full')
        {
            $params = array('table' => 'sb_news_temps_full',
                'id' => 'ntf_id',
                'title' => 'ntf_title');
        }
        elseif ($plugin == 'pl_news_form' || $plugin == 'pl_news_filter')
        {
            $params = array('table' => 'sb_news_temps_form',
                'id' => 'sntf_id',
                'title' => 'sntf_title');
        }
        elseif ($plugin == 'pl_pager_temps')
        {
            $params = array('table' => 'sb_pager_temps',
                'id' => 'pt_id',
                'title' => 'pt_title');
        }
        elseif ($plugin == 'pl_payments_temps_list')
        {
            $params = array('table' => 'sb_payments_temps_list',
                'id' => 'sptl_id',
                'title' => 'sptl_title');
        }
        elseif ($plugin == 'pl_payment_temps')
        {
            $params = array('table' => 'sb_payments_temps',
                'id' => 'spt_id',
                'title' => 'spt_title');
        }
        elseif (sb_strpos($plugin, 'pl_plugin_') !== false && sb_strpos($plugin, '_design_list') !== false)
        {
            $params = array('table' => 'sb_plugins_temps_list',
                'id' => 'ptl_id',
                'title' => 'ptl_title');
        }
        elseif (sb_strpos($plugin, 'pl_plugin_') !== false && sb_strpos($plugin, '_design_full') !== false)
        {
            $params = array('table' => 'sb_plugins_temps_full',
                'id' => 'ptf_id',
                'title' => 'ptf_title');
        }
        elseif (sb_strpos($plugin, 'pl_plugin_') !== false && (sb_strpos($plugin, '_design_form') !== false || sb_strpos($plugin, '_filter') !== false || sb_strpos($plugin, '_maker_informer') !== false))
        {
            $params = array('table' => 'sb_plugins_temps_form',
                'id' => 'ptf_id',
                'title' => 'ptf_title');
        }
        elseif ($plugin == 'pl_polls_design_list')
        {
            $params = array('table' => 'sb_polls_temps',
                'id' => 'spt_id',
                'title' => 'spt_title');
        }
        elseif ($plugin == 'pl_polls_design_result')
        {
            $params = array('table' => 'sb_polls_temps_results',
                'id' => 'sptr_id',
                'title' => 'sptr_title');
        }
        elseif (sb_strpos($plugin, 'pl_services_cb_') !== false)
        {
            $params = array('table' => 'sb_services_cb_temps',
                'id' => 'ssct_id',
                'title' => 'ssct_title');
        }
        elseif ($plugin == 'pl_services_rutube_temps_list')
        {
            $params = array('table' => 'sb_services_rutube_temps_list',
                'id' => 'ssrt_id',
                'title' => 'ssrt_name');
        }
        elseif ($plugin == 'pl_services_rutube_temps_full')
        {
            $params = array('table' => 'sb_services_rutube_temps_full',
                'id' => 'ssrtf_id',
                'title' => 'ssrtf_title');
        }
        elseif ($plugin == 'pl_services_rutube_form' || $plugin == 'pl_services_rutube_filter')
        {
            $params = array('table' => 'sb_services_rutube_temps_form',
                'id' => 'ssrtf_id',
                'title' => 'ssrtf_title');
        }
        elseif ($plugin == 'pl_site_users_temp' || $plugin == 'pl_site_users_reg_temp' || $plugin == 'pl_site_users_update_temp' ||
        $plugin == 'pl_site_users_remind_temp' || $plugin == 'pl_site_users_data_temp' || $plugin == 'pl_site_users_filter')
        {
            $params = array('table' => 'sb_site_users_temps',
                'id' => 'sut_id',
                'title' => 'sut_title');
        }
        elseif ($plugin == 'pl_site_users')
        {
            $params = array('table' => 'sb_site_users',
                'id' => 'su_id',
                'title' => 'su_login',
                'sort' => 'su_login');
        }
        elseif ($plugin == 'pl_site_users_list_temp')
        {
            $params = array('table' => 'sb_site_users_temps_list',
                'id' => 'sutl_id',
                'title' => 'sutl_title');
        }
        elseif ($plugin == 'pl_sprav')
        {
            $params = array('table' => 'sb_sprav',
                'id' => 's_id',
                'title' => 's_title');
        }
        elseif ($plugin == 'pl_sprav_design')
        {
            $params = array('table' => 'sb_sprav_temps',
                'id' => 'st_id',
                'title' => 'st_title');
        }
        elseif ($plugin == 'pl_templates')
        {
            $params = array('table' => 'sb_templates',
                'id' => 't_id',
                'title' => 't_name');
        }
        elseif ($plugin == 'pl_tester_design')
        {
            $params = array('table' => 'sb_tester_temps',
                'id' => 'stt_id',
                'title' => 'stt_title');
        }
        elseif ($plugin == 'pl_tester_result_design')
        {
            $params = array('table' => 'sb_tester_result_temps',
                'id' => 'strt_id',
                'title' => 'strt_title');
        }
        elseif ($plugin == 'pl_tester_rating_design')
        {
            $params = array('table' => 'sb_tester_rating_temps',
                'id' => 'strt_id',
                'title' => 'strt_title');
        }
        return $params;
    }

    /**
     * Возвращает или выводит список элементов раздела
     *
     * @param string $plugin_ident  Идентификатор модуля
     * @param string $values        ID выводимых элементов через запятую
     * @param int $temps            ID макета вывода списка элементов
     * @param array $params         Массив параметров
     * @param array $result         Массив с распарсенными списками элементов
     * @param bool $full            Вывод списка элементов (false) или данных по элементу (true)
     * @param string $ids_elem      ID элементов, которые надо выводить
     * @param int $link_level       Выводимый уровень
     * @param bool $show            Выводить на сайте (TRUE) или возвращать результат (FALSE)
     */
    static function getElemList($plugin_ident, $values, $temps, &$params, &$result, $full = false, $ids_elem = NULL, $link_level = 0, $show = true)
    {
        $func = '';
        $page_name = '';

        if (sb_strpos($plugin_ident, 'pl_plugin_') !== false)
        {
            require_once(SB_CMS_PL_PATH . '/pl_plugin_maker/prog/pl_plugin_maker.php');
        }
        else
        {
            require_once(SB_CMS_PL_PATH . '/' . $plugin_ident . '/prog/' . $plugin_ident . '.php');
        }

        if ($plugin_ident == 'pl_news')
        {
            if ($full)
            {
                $func = 'fNews_Elem_Full';
                $page_name = '';
            }
            else
            {
                $params['sort1'] = (isset($params['sort1']) && $params['sort1']) ? $params['sort1'] : 'n.n_title';
                $params = serialize($params);
                $ids_elem = ($ids_elem !== NULL) ? explode(',', $ids_elem) : $ids_elem;
                $func = 'fNews_Elem_List';
                $page_name = 'link_news';
            }
        }
        elseif ($plugin_ident == 'pl_imagelib')
        {
            if ($full)
            {
                $func = 'fImagelib_Elem_Full';
                $page_name = '';
            }
            else
            {
                $params['sort1'] = (isset($params['sort1']) && $params['sort1']) ? $params['sort1'] : 'im.im_title';
                $params = serialize($params);
                $func = 'fImagelib_Elem_List';
                $page_name = 'link_im';
            }
        }
        elseif ($plugin_ident == 'pl_faq')
        {
            if ($full)
            {
                $func = 'fFaq_Elem_Full';
                $page_name = '';
            }
            else
            {
                $params['sort1'] = (isset($params['sort1']) && $params['sort1']) ? $params['sort1'] : 'f.f_author';
                $params = serialize($params);
                $func = 'fFaq_Elem_List';
                $page_name = 'link_faq';
            }
        }
        elseif ($plugin_ident == 'pl_polls')
        {
            if ($full)
            {
                $func = 'fPolls_Elem_Full';
                $page_name = '';
            }
            else
            {
                $params['sort1'] = (isset($params['sort1']) && $params['sort1'])? $params['sort1'] : 'p.sp_question';
                $params = serialize($params);
                $func = 'fPolls_Elem_List';
                $page_name = 'link_poll';
            }
        }
        elseif ($plugin_ident == 'pl_services_rutube')
        {
            if ($full)
            {
                $func = 'fServices_Rutube_Full';
                $page_name = '';
            }
            else
            {
                $params['sort1'] = (isset($params['sort1']) && $params['sort1'])? $params['sort1'] : 'm.ssr_name';
                $params = serialize($params);
                $func = 'fServices_Rutube_List';
                $page_name = 'link_rutube';
            }
        }
        elseif($plugin_ident == 'pl_site_users')
        {
            if ($full)
            {
                $func = 'fSite_Users_Elem_Data';
                $page_name = '';
            }
            else
            {
                $params['filter'] = (isset($params['filter']) && $params['filter']) ? $params['filter'] : 'all';
                $params = serialize($params);
                $func = 'fSite_Users_Elem_List';
                $page_name = 'link_su';
            }
        }
        elseif (sb_strpos($plugin_ident, 'pl_plugin_') !== false)
        {
            if (!is_array($params))
                $params = array();

            $params['sort1'] = (isset($params['sort1']) && $params['sort1'])? $params['sort1'] : 'p.p_title';
            $params['pm_id'] = intval(sb_str_replace('pl_plugin_', '', $plugin_ident));
            $params = serialize($params);

            if ($full)
            {
                $func = 'fPlugin_Maker_Elem_Full';
                $page_name = '';
            }
            else
            {
                $ids_elem = ($ids_elem !== NULL) ? explode(',', $ids_elem) : $ids_elem;
                $func = 'fPlugin_Maker_Elem_List';
                $page_name = 'link_pm';
            }
        }

        if($func != '')
        {
            if($show)
            {
                $result[] = $func($values, $temps, $params, $page_name, $ids_elem, $link_level);
            }
            else
            {
                ob_start();
                $func($values, $temps, $params, $page_name, $ids_elem, $link_level);
                $result[] = ob_get_clean();
            }
        }
        else
        {
            $result[] = '';
        }
    }

    /**
     * Ф-ция возвращает select с макетами дизайна для модулей
     *
     * @param string $plugin - идентификатор модуля
     * @param string $type - Тип макетов: list - макеты списка элементов, full - макеты подробного вывода
     * @param object $layout Объект sbLayout, к которому будут добавлены поля.
     * @param string $field_value Идентификатор выбранного макета дизайна.
     * @param string $field_name Имя поля, в котором будет храниться идентификатор макета дизайна.
     * @param bool $edit_group Используется для группового редактирования элементов (TRUE) или нет (FALSE)
     */
    static function getPluginDesignTemps($plugin, $type, &$layout, $field_value, $field_name, $field_text = '', $edit_group = false)
    { 
        //	Запросы и проверка прав
        if ($plugin == 'pl_news')
        {
            if ($type == 'full')
            {
                $res = sql_query('SELECT c.cat_title, d.ntf_id, d.ntf_title FROM sb_categs c, sb_catlinks l, sb_news_temps_full d WHERE d.ntf_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ntf_title', 'pl_news_full');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_NEWS_TEMP_MSG . '</div>', '', '', false);
            }
            elseif ($type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.ndl_id, d.ndl_title FROM sb_categs c, sb_catlinks l, sb_news_temps_list d WHERE d.ndl_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ndl_title', 'pl_news_list');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_NEWS_LIST_TEMP_MSG . '</div>', '', '', false);
            }
        }
        elseif ($plugin == 'pl_imagelib')
        {
            if ($type == 'full')
            {
                $res = sql_query('SELECT c.cat_title, d.itf_id, d.itf_title FROM sb_categs c, sb_catlinks l, sb_imagelib_temps_full d WHERE d.itf_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.itf_title', 'pl_imagelib_full');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_IMAGE_LIB_TEMP_MSG . '</div>', '', '', false);
            }
            elseif ($type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.itl_id, d.itl_title FROM sb_categs c, sb_catlinks l, sb_imagelib_temps_list d WHERE d.itl_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.itl_title', 'pl_imagelib_list');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_IMAGE_LIST_LIB_TEMP_MSG . '</div>', '', '', false);
            }
        }
        elseif ($plugin == 'pl_services_rutube')
        {
            if ($type == 'full')
            {
                $res = sql_query('SELECT c.cat_title, d.ssrtf_id, d.ssrtf_title FROM sb_categs c, sb_catlinks l,  sb_services_rutube_temps_full d WHERE d.ssrtf_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ssrtf_title', 'pl_services_rutube_temps_full');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_RUTUBE_TEMP_MSG . '</div>', '', '', false);
            }
            elseif ($type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.ssrt_id, d.ssrt_name FROM sb_categs c, sb_catlinks l,  sb_services_rutube_temps_list d WHERE d.ssrt_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ssrt_name', 'pl_services_rutube_temps_list');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_RUTUBE_LIST_TEMP_MSG . '</div>', '', '', false);
            }
        }
        elseif ($plugin == 'pl_faq')
        {
            if ($type == 'full')
            {
                $res = sql_query('SELECT c.cat_title, d.ftf_id, d.ftf_title FROM sb_categs c, sb_catlinks l,  sb_faq_temps_full d WHERE d.ftf_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ftf_title', 'pl_faq_full');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_FAQ_TEMP_MSG . '</div>', '', '', false);
            }
            elseif ($type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.fdl_id, d.fdl_title FROM sb_categs c, sb_catlinks l, sb_faq_temps_list d WHERE d.fdl_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.fdl_title', 'pl_faq_list');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_FAQ_LIST_TEMP_MSG . '</div>', '', '', false);
            }
        }
        elseif ($plugin == 'pl_polls')
        {
            if ($type == 'full' || $type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.spt_id, d.spt_title FROM sb_categs c, sb_catlinks l,  sb_polls_temps d WHERE d.spt_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.spt_title', 'pl_polls_design_list');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_POLLS_TEMP_MSG . '</div>', '', '', false);
            }
        }
        elseif ($plugin == 'pl_site_users')
        {
            if ($type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.sutl_id, d.sutl_title FROM sb_categs c, sb_catlinks l,  sb_site_users_temps_list d WHERE d.sutl_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.sutl_title', 'pl_site_users_list_temp');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_SITE_USERS_TEMP_MSG . '</div>', '', '', false);
            }
            elseif ($type == 'full')
            {
                $res = sql_query('SELECT c.cat_title, d.sut_id, d.sut_title FROM sb_categs c, sb_catlinks l,  sb_site_users_temps d WHERE d.sut_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.sut_title', 'pl_site_users_data_temp');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . PLUGINS_DESIGN_TEMPS_SELECT_NO_SITE_USERS_TEMP_MSG . '</div>', '', '', false);
            }
        }
        elseif (sb_strpos($plugin, 'pl_plugin') !== false)
        {
            if ($type == 'full')
            {
                $res = sql_query('SELECT c.cat_title, d.ptf_id, d.ptf_title FROM sb_categs c, sb_catlinks l,  sb_plugins_temps_full d WHERE d.ptf_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ptf_title', $plugin . '_design_full');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . sprintf(PLUGINS_DESIGN_TEMPS_SELECT_NO_PLUGIN_MAKER_TEMP_MSG, $_SESSION['sbPlugins']->getPluginTitle($plugin)) . '</div>', '', '', false);
            }
            elseif ($type == 'list')
            {
                $res = sql_query('SELECT c.cat_title, d.ptl_id, d.ptl_title FROM sb_categs c, sb_catlinks l, sb_plugins_temps_list d WHERE d.ptl_id=l.link_el_id AND c.cat_id=l.link_cat_id AND c.cat_ident=? ORDER BY c.cat_left, d.ptl_title', $plugin . '_design_list');
                if (!$res)
                    $fld = new sbLayoutLabel('<div class="hint_div">' . sprintf(PLUGINS_DESIGN_TEMPS_SELECT_NO_PLUGIN_MAKER_LIST_TEMP_MSG, $_SESSION['sbPlugins']->getPluginTitle($plugin)) . '</div>', '', '', false);
            }
        }

        //	Генерация селекта
        $options = array('-1' => PLUGINS_DESIGN_TEMPS_SELECT_GET_ONLY_DATA);
        
        if ($res)
        {
            $old_cat_title = '';
            foreach ($res as $value)
            {
                list($cat_title, $d_id, $d_title) = $value;

                if ($old_cat_title != $cat_title)
                {
                    $options[uniqid()] = '-' . $cat_title;
                    $old_cat_title = $cat_title;
                }
                $options[$d_id] = $d_title;
            }
        }
        $fldSel = new sbLayoutSelect($options, $field_name, '', '');
        $fldSel->mSelOptions = array($field_value);
        $fldSel->mHTML = isset($fld) ? $fld->getField() : '';

        $layout->addField($field_text . sbGetGroupEditCheckbox($field_name, $edit_group), $fldSel);
    }

    /**
     * Функция возвращает массив полей сортировки
     *
     * @param string $plugin_ident  Идентификатор модуля
     * @param array $fields_names  Массив названий полей
     * @param array $pm_elems_settings Массив настроек конструктора модулей
     * 
     * @return array Массив, ключ массива - название поля в базе данных, значение - описание поля.
     */
    static function getSortFields($plugin_ident, $fields_names = array(), $pm_elems_settings = array())
    {
        $fields = array();

        if(sb_strpos($plugin_ident, 'pl_plugin') !== false)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_RAND,
					'p.p_id' => isset($fields_names['p_id']) ? $fields_names['p_id'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_ID,
                    'p.p_title' => isset($fields_names['p_title']) ? $fields_names['p_title'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_TITLE,
    				'p_rating' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_RATING,
    				'v.vr_num' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_COUNT,
    				'v.vr_count' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_SUM,
    				'com_count' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_COM_COUNT,
    				'com_date' => PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_COM_DATE);
            
            if($_SESSION['sbPlugins']->isPluginAvailable('pl_basket') && (!isset($pm_elems_settings['need_basket']) || $pm_elems_settings['need_basket'] == 1))
            {
				$fields = array_merge($fields, array(
					'p.p_price1' => isset($fields_names['p_price1']) ? $fields_names['p_price1'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_PRICE.' 1',
					'p.p_price2' => isset($fields_names['p_price2']) ? $fields_names['p_price2'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_PRICE.' 2',
					'p.p_price3' => isset($fields_names['p_price3']) ? $fields_names['p_price3'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_PRICE.' 3',
					'p.p_price4' => isset($fields_names['p_price4']) ? $fields_names['p_price4'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_PRICE.' 4',
					'p.p_price5' => isset($fields_names['p_price5']) ? $fields_names['p_price5'] : PL_PLUGIN_MAKER_H_ELEM_LIST_SORT_PRICE.' 5'));
            }
        }
        elseif('pl_news' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_NEWS_H_ELEM_LIST_SORT_RAND,
					'n.n_id'	=>	PL_NEWS_H_ELEM_LIST_SORT_ID,
                    'n.n_title' => PL_NEWS_H_ELEM_LIST_SORT_TITLE,
                    'n.n_date' => PL_NEWS_H_ELEM_LIST_SORT_DATE,
                    'n.n_sort' => PL_NEWS_H_ELEM_LIST_SORT_SORT,
    				'n_rating' => PL_NEWS_H_ELEM_LIST_SORT_RATING,
    				'v.vr_num' => PL_NEWS_H_ELEM_LIST_SORT_COUNT,
    				'v.vr_count' => PL_NEWS_H_ELEM_LIST_SORT_SUM,
    				'com_count' => PL_NEWS_H_ELEM_LIST_SORT_COM_COUNT,
    				'com_date' => PL_NEWS_H_ELEM_LIST_SORT_COM_DATE);
        }
        elseif('pl_imagelib' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_IMAGELIB_H_ELEM_LIST_SORT_RAND,
    				'im.im_id' => PL_IMAGELIB_H_ELEM_LIST_SORT_ID,
                    'im.im_title' => PL_IMAGELIB_H_ELEM_LIST_SORT_TITLE,
                    'im.im_date' => PL_IMAGELIB_H_ELEM_LIST_SORT_DATE,
                    'im.im_order_num' => PL_IMAGELIB_H_ELEM_LIST_SORT_SORT,
    				'im_rating' => PL_IMAGELIB_H_ELEM_LIST_SORT_RATING,
    				'v.vr_num' => PL_IMAGELIB_H_ELEM_LIST_SORT_COUNT,
    				'v.vr_count' => PL_IMAGELIB_H_ELEM_LIST_SORT_SUM,
    				'com_count' => PL_IMAGELIB_H_ELEM_LIST_SORT_COM_COUNT,
    				'com_date' => PL_IMAGELIB_H_ELEM_LIST_SORT_COM_DATE);
        }
        elseif('pl_polls' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_POLLS_H_ELEM_LIST_SORT_RAND,
    				'p.sp_id' => PL_POLLS_H_ELEM_LIST_ID,
                    'p.sp_question' => PL_POLLS_H_ELEM_LIST_QUESTIONS,
                    'p.sp_sort' => PL_POLLS_H_ELEM_LIST_SORT_SORT);
        }
        elseif('pl_services_rutube' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_SR_H_ELEM_LIST_SORT_RAND,
					'm.ssr_id' => PL_SR_H_ELEM_LIST_SORT_ID,
                    'm.ssr_name' => PL_SR_H_ELEM_LIST_SORT_TITLE,
                    'm.ssr_date' => PL_SR_H_ELEM_LIST_SORT_DATE,
                    'm_rating' => PL_SR_H_ELEM_LIST_SORT_RATING,
                    'v.vr_num' => PL_SR_H_ELEM_LIST_SORT_COUNT,
                    'v.vr_count' => PL_SR_H_ELEM_LIST_SORT_SUM,
    				'com_count' => PL_SR_H_ELEM_LIST_SORT_COM_COUNT,
    				'com_date' => PL_SR_H_ELEM_LIST_SORT_COM_DATE);
        }
        elseif('pl_faq' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_FAQ_H_ELEM_LIST_SORT_RAND,
					'f.f_id' => PL_FAQ_H_ELEM_LIST_SORT_ID,
                    'f.f_author' => PL_FAQ_H_ELEM_LIST_SORT_AUTHOR,
                    'f.f_date' => PL_FAQ_H_ELEM_LIST_SORT_DATE,
                    'f.f_sort' => PL_FAQ_H_ELEM_LIST_SORT_SORT,
                    'f_rating' => PL_FAQ_H_ELEM_LIST_SORT_RATING,
                    'v.vr_num' => PL_FAQ_H_ELEM_LIST_SORT_COUNT,
                    'v.vr_count' => PL_FAQ_H_ELEM_LIST_SORT_SUM,
    				'com_count' => PL_FAQ_H_ELEM_LIST_SORT_COM_COUNT,
    				'com_date' => PL_FAQ_H_ELEM_LIST_SORT_COM_DATE);
        }
        elseif('pl_payment' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_PAYMENT_H_ELEM_LIST_SORT_RAND,
                    'sp_id' => PL_PAYMENT_H_ELEM_LIST_SORT_SCHET,
                    'sp_date' => PL_PAYMENT_H_ELEM_LIST_SORT_DATE,
                    'sp_name' => PL_PAYMENT_H_ELEM_LIST_SORT_NAME,
                    'sp_surname' => PL_PAYMENT_H_ELEM_LIST_SORT_SURNAME,
                    'sp_summ' => PL_PAYMENT_H_ELEM_LIST_SORT_SUMM,
                    'sp_phone' => PL_PAYMENT_H_ELEM_LIST_SORT_PHONE,
                    'sp_email' => PL_PAYMENT_H_ELEM_LIST_SORT_EMAIL);
        }
        elseif('pl_site_users' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_SITE_USERS_H_ELEM_LIST_SORT_RAND,
    				'su.su_id' => PL_SITE_USERS_H_ELEM_LIST_SORT_ID,
                    'su.su_login' => PL_SITE_USERS_H_ELEM_LIST_SORT_LOGIN,
                    'su.su_email' => PL_SITE_USERS_H_ELEM_LIST_SORT_EMAIL,
                    'su.su_name' => PL_SITE_USERS_H_ELEM_LIST_SORT_NAME,
                    'su.su_reg_date' => PL_SITE_USERS_H_ELEM_LIST_SORT_REG_DATE,
                    'su.su_last_date' => PL_SITE_USERS_H_ELEM_LIST_SORT_LAST_DATE,
                    'su.su_work_name' => PL_SITE_USERS_H_ELEM_LIST_SORT_WORK_NAME,
    				'su_rating' => PL_SITE_USERS_H_ELEM_LIST_SORT_RATING,
    				'v.vr_num' => PL_SITE_USERS_H_ELEM_LIST_SORT_COUNT,
    				'v.vr_count' => PL_SITE_USERS_H_ELEM_LIST_SORT_SUM,
    				'com_count' => PL_SITE_USERS_H_ELEM_LIST_SORT_COM_COUNT,
    				'com_date' => PL_SITE_USERS_H_ELEM_LIST_SORT_COM_DATE);
        }
        elseif('pl_tester' == $plugin_ident)
        {
            $fields = array('' => ' --- ',
                    'RAND()' => PL_TESTER_H_ELEM_LIST_SORT_RAND,
    				'stq_id' => PL_TESTER_H_ELEM_LIST_SORT_ID,
                    'stq_order' => PL_TESTER_H_ELEM_LIST_SORT_ORDER);
        }
        elseif('pl_banners == $plugin_ident')
        {
            $fields = array('' => ' --- ',
                    'sb.sb_priority' => PL_BANNERS_H_ELEM_LIST_PRIORITY_SORT,
                    'sb.sb_id' => PL_BANNERS_H_ELEM_LIST_ID_SORT,
                    'sb.sb_name' => PL_BANNERS_H_ELEM_LIST_NAME_SORT);
        }

        return $fields;
    }

    /**
     * @ignore
     */
    public function init()
    {
        $GLOBALS['sb_cmp_sort_field'] = 'name';
        uasort($this->mElems, 'sb_cmp_array');
    }
}
?>