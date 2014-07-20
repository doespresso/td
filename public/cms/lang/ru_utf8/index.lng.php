<?php
/**
 * Языковые константы индексного файла (cms/admin/index.php) системы
 *
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
define ('INDEX_NO_PASSWORD', 'Не указан пароль пользователя!');
define ('INDEX_NO_PASSWORD_OR_USER_EMPTY', 'Не указаны пароль или логин пользователя!');

define ('INDEX_KERNEL_SETTING', 'Общие');
define ('INDEX_KERNEL_DOMAIN_SETTING', 'Основные');
define ('INDEX_KERNEL_DOMAIN_CACHE_SETTING', 'Настройки кеширования');
define ('INDEX_KERNEL_PLUGIN_SETTING', 'Модули');
define ('INDEX_KERNEL_CAPTCHA_SETTING', 'CAPTCHA');
define ('INDEX_KERNEL_SOCIAL_MEDIAS', 'Социальные медиа');

define ('INDEX_KERNEL_SETTING_GOOGLE', 'Сервисы Google');
define ('INDEX_KERNEL_SETTING_GOOGLE_ANALYTICS_ID', 'Идентификатор пользователя для Google Analytics (UA-XXXXXXXX-X)');
define ('INDEX_KERNEL_SETTING_YANDEX', 'Сервисы Яндекс');
define ('INDEX_KERNEL_SETTING_YANDEX_MAPS_ID', 'API-ключ Яндекс.Карт');
define ('INDEX_KERNEL_SETTING_YANDEX_MAPS_METRIKA', 'Идентификатор счётчика Яндекс.Метрики (XXXXXXX)');
define ('INDEX_KERNEL_SETTING_SITE_NAME', 'Название сайта');
define ('INDEX_KERNEL_SETTING_SITE_HASH', '"Соль", используемая системой для генерации хэшей');
define ('INDEX_KERNEL_SETTING_WWW_REDIRECT', 'Переадресация');
define ('INDEX_KERNEL_SETTING_ADMIN_EMAIL', 'Основной E-mail сайта');
define ('INDEX_KERNEL_SETTING_GZIP_HEADER', 'GZip-сжатие');
define ('INDEX_KERNEL_SETTING_HTML_COMPRESS', 'Использовать сжатие HTML-кода страниц и используемых CSS и JS-файлов <br /> (оптимизирует кол-во загружаемых стилей и скриптов, значительно увеличивает скорость загрузки страниц)');
define ('INDEX_KERNEL_SETTING_GZIP_COMPRESS', 'Использовать GZip-сжатие страниц <br /> (позволяет значительно увеличить скорость загрузки страниц даже на слабом канале)');
define ('INDEX_KERNEL_SETTING_URLS_HEADER', 'ЧПУ');
define ('INDEX_KERNEL_SETTING_STATIC_URLS', 'Использовать статичные URL на сайте (ЧПУ)<br /> (на сервере должен быть доступен модуль mod_rewrite)');
define ('INDEX_KERNEL_SETTING_RUSSIAN_URLS', 'Разрешить использовать русские буквы в статичных URL на сайте (ЧПУ)');
define ('INDEX_KERNEL_SETTING_STRTOLOWER_URLS', 'Генерировать статичные URL (ЧПУ) в нижнем регистре');
define ('INDEX_KERNEL_SETTING_USE_CACHE', 'Использовать кэширование на сайте<br />(позволяет значительно снизить нагрузку на сервер)');

define ('INDEX_KERNEL_SETTING_CACHE_MODULE_HEADER', 'Кеширование компонентов');
define ('INDEX_KERNEL_SETTING_USE_CACHE_COOKIE', 'Укажите cookie, значения которых необходимо использовать в идентификаторе кэша (названия через запятую)');
define ('INDEX_KERNEL_SETTING_DONTUSECACHE', 'Не использовать');
define ('INDEX_KERNEL_SETTING_DBCACHE', 'База данных');
define ('INDEX_KERNEL_SETTING_MEMCACHE', 'MemCache');
define ('INDEX_KERNEL_SETTING_USE_MCACHE_ZIP', 'Использовать сжатие данных для экономии ОЗУ<br />(возрастает нагрузка на ЦП сервера)');
define ('INDEX_KERNEL_SETTING_USE_MCACHE_HOST', 'Хост');
define ('INDEX_KERNEL_SETTING_USE_MCACHE_PORT', 'Порт');

define ('INDEX_KERNEL_SETTING_CACHE_PAGE_HEADER', 'Кеширование страниц');
define ('INDEX_KERNEL_SETTING_CACHE_PAGE_USE', 'Использовать полностраничный кэш');
define ('INDEX_KERNEL_SETTING_CACHE_PAGE_VALIDITY_TIME', 'Время валидности кеша (в секундах)');
define ('INDEX_KERNEL_SETTING_CACHE_PAGE_RPAGE', 'Запрещенные фрагменты урл (через запятую)');
define ('INDEX_KERNEL_SETTING_CACHE_PAGE_RGET', 'Запрещенные GET-параметры (через запятую)');
define ('INDEX_KERNEL_SETTING_CACHE_PAGE_RCOOKIE', 'Запрещенные куки (через запятую)');
define ('INDEX_KERNEL_SETTING_CACHE_PAGE_USE_COOKIE', 'Имена кук значения которых нужно учитывать при формировании кэша');

define ('INDEX_KERNEL_SETTING_WRONG_PASS_COUNT', 'Кол-во последовательных попыток ввода некорректного пароля, после которого аккаунт пользователя будет заблокирован');
define ('INDEX_KERNEL_SETTING_SESSION_TIMEOUT', 'Таймаут сессии пользователя (в минутах)');
define ('INDEX_KERNEL_SETTING_USER_STATUS', 'Сообщать об обрывах связи');
define ('INDEX_KERNEL_SETTING_DENY_INDEX', 'Запретить индексацию сайта поисковыми роботами');
define ('INDEX_KERNEL_SETTING_SUBMIT_TIMEOUT', 'Временная задержка между отправкой писем с сайта (в микросекундах)');
define ('INDEX_KERNEL_SETTING_DIRECTORY_INDEX', 'Индексная страница сайта');
define ('INDEX_KERNEL_SETTING_404_PAGE', 'Страница сайта для 404-ой ошибки сервера (относительный путь)');
define ('INDEX_KERNEL_SETTING_DATE_TIME_HEADER', 'Региональные стандарты');
define ('INDEX_KERNEL_SETTING_TIMEZONE', 'Временная зона');
define ('INDEX_KERNEL_SETTING_VERSION', 'Версия скриптов и стилей');
define ('INDEX_KERNEL_SETTING_VERSION_CSS', 'Версия стилей');
define ('INDEX_KERNEL_SETTING_VERSION_JS', 'Версия скриптов');
define ('INDEX_KERNEL_SETTING_PRELOAD_ID', 'Использовать предварительную выборку идентификаторов элементов если не используется постраничный вывод');

define ('INDEX_KERNEL_SETTING_VERSION_DESCR', '<div style="text-align:left;">Для избежания кеширования стилей или скриптов со стороны сайта, используйте следующую конструкцию в макетах дизайна сайта:
<br><br>
&lt;script src="some_file.js?{SB_JS_VERSION}" type="text/javascript"&gt;&lt;/script&gt;
<br>
&lt;link rel="stylesheet" type="text/css" href="some_file.css?{SB_CSS_VERSION}" /&gt;</div>');

define ('INDEX_KERNEL_SETTING_USE_MCACHE_DESCR', '<b>Внимание!</b> Перед выбором кеширования через Memcache убедитесь в том, что сервер Memcache запущен. Полностраничный кеш работает только при доступности сервера Memcached.');

define ('INDEX_KERNEL_SETTING_XMLRPC_HEADER', 'Возможность публикации новостей с помощью MS Word');
define ('INDEX_KERNEL_SETTING_XMLRPC_CALLS', 'Включить возможность публикации новостей с помощью MS Word');
define ('INDEX_KERNEL_SETTING_XMLRPC_EDIT_LIMIT', 'Кол-во последних новостей, выгружаемых в MS Word для редактирования');

define ('INDEX_KERNEL_SETTING_ADMIN_IP', 'Укажите через пробел маски подсетей, с которых возможен вход пользователей системы <br /> (если список пуст, вход в систему возможен с любого IP-адреса)');
define ('INDEX_KERNEL_SETTING_SITE_IP', 'Укажите через пробел маски подсетей, с которых возможен вход пользователей сайта <br /> (если список пуст, вход в закрытый раздел сайта возможен с любого IP-адреса)');
define ('INDEX_KERNEL_SETTING_ADMIN_SOAP_IP', 'Укажите через пробел маски подсетей, с которых возможна работа с системой по технологии SOAP <br /> (если список пуст, подключение по SOAP возможно с любого IP-адреса)');
define ('INDEX_KERNEL_SETTING_LETTERS_HEADER', 'Лист рассылки и системные письма');
define ('INDEX_KERNEL_SETTING_LETTERS_CHARSET', 'Кодировка писем');
define ('INDEX_KERNEL_SETTING_LETTERS_IMAGES', 'Картинки отправляются в виде вложений в письмах');
define ('INDEX_KERNEL_SETTING_LETTERS_TYPE', 'Формат писем');
define ('INDEX_KERNEL_SETTING_STRIP_DOMAIN', 'Вырезать имя домена из ссылок, генерируемых системой');
define ('INDEX_KERNEL_SETTING_DEBUG_MODE', 'Режим отладки (отключает перехват ошибок PHP и mySQL и включает вывод ошибок)');
define ('INDEX_KERNEL_SETTING_PAGES_CACHE', 'Кеширование постраничного вывода (в секундах)');
define ('INDEX_KERNEL_SETTING_CONFIRM_MOVE_FOLDERS', 'Запрашивать подтверждение при перемещении файлов и папок');
define ('INDEX_KERNEL_SETTING_RESTRICTED_FOLDERS', 'Список папок запрещенных для показа в файловой панели (через запятую)');
define ('INDEX_KERNEL_SETTING_LETTERS_SMTP_HOST', 'Сервер исходящей почты (SMTP)');
define ('INDEX_KERNEL_SETTING_LETTERS_SMTP_USER', 'Пользователь');
define ('INDEX_KERNEL_SETTING_LETTERS_SMTP_PASSWORD', 'Пароль');

define ('INDEX_KERNEL_SETTING_LETTERS_TYPE_TEXT', 'Текст');
define ('INDEX_KERNEL_SETTING_LETTERS_TYPE_HTML', 'HTML');

define ('INDEX_KERNEL_SETTING_WWW_REDIRECT_TYPE_WITH_WWW', 'с домена без WWW на домен с WWW');
define ('INDEX_KERNEL_SETTING_WWW_REDIRECT_TYPE_WITHOUT_WWW', 'с домена с WWW на домен без WWW');
define ('INDEX_KERNEL_SETTING_WWW_REDIRECT_TYPE_NONE', 'без переадресации');

define ('INDEX_KERNEL_SETTING_TIMEZONE_12', '(GMT - 12:00) Линия смены дат');
define ('INDEX_KERNEL_SETTING_TIMEZONE_11', '(GMT - 11:00) Самоа');
define ('INDEX_KERNEL_SETTING_TIMEZONE_10', '(GMT - 10:00) Гавайи');
define ('INDEX_KERNEL_SETTING_TIMEZONE_9', '(GMT - 09:00) Аляска');
define ('INDEX_KERNEL_SETTING_TIMEZONE_8', '(GMT - 08:00) Североамериканское тихоокеанское время (США и Канада)');
define ('INDEX_KERNEL_SETTING_TIMEZONE_7', '(GMT - 07:00) Горное время (США и Канада)');
define ('INDEX_KERNEL_SETTING_TIMEZONE_6', '(GMT - 06:00) Центральное время (США и Канада)');
define ('INDEX_KERNEL_SETTING_TIMEZONE_5', '(GMT - 05:00) Североамериканское восточное время (США и Канада)');
define ('INDEX_KERNEL_SETTING_TIMEZONE_4', '(GMT - 04:00) Атлантическое время (Канада)');
define ('INDEX_KERNEL_SETTING_TIMEZONE_3', '(GMT - 03:00) Бразилиа, Буэнос-Айрес, Джорджтаун');
define ('INDEX_KERNEL_SETTING_TIMEZONE_2', '(GMT - 02:00) Среднеатлантическое время');
define ('INDEX_KERNEL_SETTING_TIMEZONE_1', '(GMT - 01:00) Азорские острова, Кабо-Верде');
define ('INDEX_KERNEL_SETTING_TIMEZONE0', '(GMT) Дублин, Эдинбург, Лиссабон, Лондон, Касабланка, Монровия');
define ('INDEX_KERNEL_SETTING_TIMEZONE1', '(GMT + 01:00) Брюссель, Вена, Копенгаген, Мадрид, Париж, Рим, Стокгольм');
define ('INDEX_KERNEL_SETTING_TIMEZONE2', '(GMT + 02:00) Афины, Бухарест, Вильнюс, Киев, Минск, Рига, София, Таллин, Хельсинки');
define ('INDEX_KERNEL_SETTING_TIMEZONE3', '(GMT + 03:00) Калининград, Минск, Кения, Эфиопия, Эритрея, Танзания, Сомали, Ирак, Йемен, Кувейт, Саудовская Аравия');
define ('INDEX_KERNEL_SETTING_TIMEZONE4', '(GMT + 04:00) Москва, Объединённые Арабские Эмираты, Оман, Азербайджан, Армения, Грузия');
define ('INDEX_KERNEL_SETTING_TIMEZONE5', '(GMT + 05:00) Западный Казахстан, Пакистан, Таджикистан, Туркменистан, Узбекистан');
define ('INDEX_KERNEL_SETTING_TIMEZONE6', '(GMT + 06:00) Екатеринбург, Новосибирск, Центральноазиатское время (Бангладеш, Казахстан), Шри-Ланка');
define ('INDEX_KERNEL_SETTING_TIMEZONE7', '(GMT + 07:00) Новосибирск, Кемерово, Юго-Восточная Азия (Бангкок, Джакарта, Ханой)');
define ('INDEX_KERNEL_SETTING_TIMEZONE8', '(GMT + 08:00) Красноярск, Иркутск, Улан-Батор, Куала-Лумпур, Гонконг, Китай, Сингапур, Тайвань');
define ('INDEX_KERNEL_SETTING_TIMEZONE9', '(GMT + 09:00) Иркутск, Корея, Япония');
define ('INDEX_KERNEL_SETTING_TIMEZONE10', '(GMT + 10:00) Якутск, Брисбен, Канберра, Мельбурн, Сидней');
define ('INDEX_KERNEL_SETTING_TIMEZONE11', '(GMT + 11:00) Владивосток, Центрально-тихоокеанское время');
define ('INDEX_KERNEL_SETTING_TIMEZONE12', '(GMT + 12:00) Магадан, Камчатка, Маршалловы острова, Фиджи, Новая Зеландия');
define ('INDEX_KERNEL_SETTING_PROFILER_DESC', 'Профилирование');
define ('INDEX_KERNEL_SETTING_PROFILER_PL', 'Включить профилирование модулей');
define ('INDEX_KERNEL_SETTING_PROFILER_SQL', 'Включить профилирование SQL-запросов');
define ('INDEX_KERNEL_SETTING_PROFILER_IP', 'Укажите через пробел маски подсетей, запросы с которых будут профилироваться <br /> (если список пуст, то профилируются запросы с любого адреса)');

define ('INDEX_USER_SETTING', 'Основные');
define ('INDEX_USER_TOOLBAR_SETTING', 'Панель инструментов');

define ('INDEX_USER_SETTING_TOOLBAR', 'Выберите модули, иконки которых будут отображаться на панели инструментов');
define ('INDEX_USER_SETTING_TOOLBAR_QICONS_COUNT', 'Кол-во иконок в панели быстрого запуска');
define ('INDEX_USER_SETTING_CHARACTER', 'Выберите персонаж системы');
define ('INDEX_USER_SETTING_WITHOUT_CHARACTER', 'без персонажа');
define ('INDEX_USER_SETTING_MAIN_PLUGIN', 'Выберите модуль, который будет активным после входа в систему');
define ('INDEX_USER_SETTING_ELEMS_PER_PAGE', 'Кол-во элементов на странице (новости, пользователи и т.п.)');

define ('INDEX_NO_PLUGINS', 'Вам не доступен ни один модуль системы! Обратитесь к администратору системы.');

define ('INDEX_SYSTEM_TITLE', 'Система управления сайтом '.CMS_NAME);

define ('INDEX_EXPIRE_DAYS', 'Внимание! Вы используете демонстрационную версию системы. До окончания демонстрационного периода осталось %s дней!');
define ('INDEX_EXPIRE_HOURS', 'Внимание! Вы используете демонстрационную версию системы. До окончания демонстрационного периода осталось %s часов!');

define ('INDEX_TITLE', 'Вход в административную часть системы управления сайтом '.CMS_NAME);
define ('INDEX_REMINDER_TITLE', 'Напомнить пароль');

define ('INDEX_VISUAL_PLUGIN', 'Визуальное редактирование страницы');
define ('INDEX_KERNEL_PLUGIN', 'Главная страница системы');

define ('INDEX_BROWSER_INCORRECT_COOKIE', 'Для корректной работы системы следует разрешить в настройках браузера принятие файлов Cookie!');
define ('INDEX_BROWSER_INCORRECT_ACTIVEX', 'Для корректной работы системы следует разрешить в настройках браузера выполнение AJAX-запросов!');
define ('INDEX_BROWSER_INCORRECT_POP_WINDOW', 'Для корректной работы системы следует разрешить в настройках браузера всплывающие окна!');

define ('INDEX_TITLE_FORM', 'Введите Ваш логин и пароль:');
define ('INDEX_TITLE_REMINDER_FORM', 'Введите Ваш логин и e-mail:');
define ('INDEX_USER', 'Логин');
define ('INDEX_AUTO_LOGIN', 'запомнить меня на этом компьютере');
define ('INDEX_PASSWORD', 'Пароль');
define ('INDEX_FORGET_PASSWORD', 'Забыли пароль?');
define ('INDEX_EMAIL', 'E-mail');
define ('INDEX_LANG', 'Язык системы');
define ('INDEX_ENTER', 'Вход в систему');
define ('INDEX_REMIND', 'Отправить пароль');

define ('INDEX_WARNING_OPERA', 'Внимание! Вы используете браузер Opera. Для вызова контекстного меню в системе управления сайтом используйте Alt + клик левой кнопкой мышки.');
define ('INDEX_ERROR_NO_LOGIN', 'Ошибка! Не указан логин пользователя.');
define ('INDEX_ERROR_NO_EMAIL', 'Ошибка! Не указан электронный адрес пользователя.');
define ('INDEX_ERROR_NO_EMAIL_OR_LOGIN', 'Ошибка! Логин или электронный адрес указаны неверно.');

define ('INDEX_REMIND_SUBJECT', 'Напоминание пароля');
define ('INDEX_REMIND_BODY', '<html><head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
    <title>%s - напоминание пароля</title>
    <STYLE><!--
        body, table, tr, td {font-size: 10pt; font-family: Verdana,Tahoma,Arial,sans-serif;}
        span.bld {color:#004080;}
    --></STYLE>
    </head>
    <body>
    Здравствуйте, %s!
    <br><br>
    Вы запросили новый пароль для доступа в систему администрирования сайта %s.
    <br><br>
    Ваш логин: <span class="bld">%s</span><br>
    Ваш пароль: <span class="bld">%s</span>
    <br><br>
    Вы всегда можете поменять свой пароль в разделе "Меню администратора - Пользователи системы", либо обратившись к администратору сайта.
    <br><br>
    P.S.: Письмо выслано автоинформатором системы управления сайтом %s.</body></html>');

define ('INDEX_REMIND_OK', 'Новый пароль выслан на Ваш электронный адрес.');
define ('INDEX_REMIND_ENTER', 'войти в систему');

define ('INDEX_COPYRIGHT', 'Copyright &copy; '.CMS_COMP_YEAR.', '.CMS_COMP_NAME);
define ('INDEX_KNOW_LINK', 'Интерактивная книга CMS S.Builder');
define ('INDEX_API_LINK', 'Описание программного интерфейса (API)');
define ('INDEX_SITE_LINK', 'Официальный сайт CMS S.Builder');
define ('INDEX_FORUM_LINK', 'Официальный форум CMS S.Builder');

define ('INDEX_BROWSER_INCORRECT', "Для корректной работы системы требуется браузер Internet Explorer версии 5.5 и выше, или Mozilla Firefox версии 3.0 и выше, или Opera версии 9.5 и выше, или Safari версии 3.0 и выше, или Google Chrome! О том, как проверить версию Вашего браузера, читайте <a href=\\\"http://".CMS_SITE_NAME."/support/faq/ie_version.php\\\" target=\\\"_blank\\\">здесь</a>.");
define ('INDEX_BROWSER_INCORRECT_DEALER', 'Для корректной работы системы требуется браузер Internet Explorer версии 5.5 и выше, или Mozilla Firefox версии 3.0 и выше, или Opera версии 9.5 и выше, или Safari версии 3.0 и выше, или Google Chrome!');

define ('INDEX_NO_PLUGIN_MYSQL', 'Ошибка! Отсутствует расширение PHP для работы с СУБД MySQL! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');
define ('INDEX_NO_PLUGIN_MSSQL', 'Ошибка! Отсутствует расширение PHP для работы с СУБД MS SQL! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');
define ('INDEX_NO_PLUGIN_ICONV', 'Ошибка! Отсутствует расширение PHP для работы с различными кодировками (iconv)! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');
define ('INDEX_NO_PLUGIN_MBSTRING', 'Ошибка! Отсутствует расширение PHP для работы с кодировкой UTF-8 (mbstring)! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');
define ('INDEX_NO_PLUGIN_PREG', 'Ошибка! Отсутствует расширение PHP для работы с регулярными выражениями (preg)! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');
define ('INDEX_NO_PHP_5', 'Ошибка! Версия интерпретатора PHP ниже 5.0.0! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');

define ('INDEX_LOADING', 'Идет проверка логина и пароля! Пожалуйста, подождите...');

define ('INDEX_PASS_TITLE', 'Генерация паролей');
define ('INDEX_PASS_TITLE_FORM', 'Введите пароль:');
define ('INDEX_PASS_ENCRYPT_PASSWORD', 'Шифрованный пароль');
define ('INDEX_PASS_ENCRYPT', 'Шифровать');
define ('INDEX_PASS_LOADING', 'Идет шифрование пароля! Пожалуйста, подождите...');

define ('INDEX_CAPTCHA_TITLE', 'Настройки изображения с кодом (CAPTCHA)');
define ('INDEX_CAPTCHA_TYPE_CAPTCHA', 'Тип фона CAPTCHA');
define ('INDEX_CAPTCHA_TYPE_IMG', 'Изображение на фоне');
define ('INDEX_CAPTCHA_TYPE_STRING', 'Фон из букв');
define ('INDEX_CAPTCHA_TYPE_COLOR', 'Сплошной цвет');
define ('INDEX_CAPTCHA_FON_SHUM_COLORS', 'Цвета для шумов на изображении (через пробел)');
define ('INDEX_CAPTCHA_CODE_COLORS', 'Цвета для кода CAPTCHA (через пробел)');
define ('INDEX_CAPTCHA_FON_TEXT_COLORS', 'Цвет фонового текста (через пробел)');
define ('INDEX_CAPTCHA_HEIGHT', 'Высота CAPTCHA');
define ('INDEX_CAPTCHA_WIDTH', 'Ширина CAPTCHA');
define ('INDEX_CAPTCHA_FONT_SIZE_FROM', 'Размер шрифта кода CAPTCHA от');
define ('INDEX_CAPTCHA_FONT_SIZE_TO', 'Размер шрифта кода CAPTCHA до');
define ('INDEX_CAPTCHA_CODE_LENGTH_FROM', 'Генерировать кода CAPTCHA от');
define ('INDEX_CAPTCHA_CODE_LENGTH_TO', 'Генерировать кода CAPTCHA до');
define ('INDEX_CAPTCHA_FON_COLOR', 'Цвет фона CAPTCHA');
define ('INDEX_CAPTCHA_URL_IMG', 'Изображение для фона <br />(если указать директорию, изображения будут браться в случайном порядке)');
define ('INDEX_CAPTCHA_FONTS', 'Шрифт для кода');
define ('INDEX_CAPTCHA_SHUM_LINES', 'Использовать шумы в виде линий');
define ('INDEX_CAPTCHA_SHUM_POINTS', 'Использовать шумы в виде точек');
define ('INDEX_KERNEL_SETTING_MAX_AUTOBCKP', 'Максимальное количество точек восстановления системы создаваемых через планировщик заданий');
?>