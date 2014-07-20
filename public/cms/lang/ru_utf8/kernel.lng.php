<?php
/**
 * Языковые константы ядра системы
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

define ('KERNEL_TIMEOUT', 'В целях безопасности время бездействия системы ограничено. Повторите, пожалуйста, вход в систему!');
define ('KERNEL_FILE_NOT_FOUND', 'Файл, содержащий функцию обработки события, не найден!');
define ('KERNEL_NO_SESSIONS', 'Не удалось создать сессию пользователя! Проверьте настройки сервера.');
define ('KERNEL_NO_EVENT', 'Вызываемое событие не зарегистрировано в системе, либо у Вас недостаточно прав доступа!');
define ('KERNEL_NO_FILE', 'Файл %s не найден! Проверьте целостность системы и правильность настроек в файле /cms/kernel/config.inc.php.');
define ('KERNEL_NO_LANG_FILE', 'Файл с языковыми константами %s для выбранного языка не найден! Проверьте целостность системы и правильность настроек в файле cms/kernel/config.inc.php.');
define ('KERNEL_NO_FUNCTION', 'Функция-обработчки %s для вызываемого события не найдена!');

define ('KERNEL_STATIC_URL', 'Псевдостатический адрес (ЧПУ)');
define ('KERNEL_STATIC_URL_HINT', 'В псевдостатическом адресе разрешается использовать буквы латинского алфавита, цифры, дефис(-) и знак подчеркивания (_). Этот адрес используется для построения статичных URL на сайте (ЧПУ).');
define ('KERNEL_THEME_TAGS', 'Тематические теги (слова или словосочетания через запятую)');
define ('KERNEL_CHARACTER_ALT', 'Щелкните, чтобы показать следующую подсказку');

define ('KERNEL_CANCEL', 'Отменить');
define ('KERNEL_SAVE', 'Сохранить');
define ('KERNEL_SAVE_AS', 'Сохранить как...');
define ('KERNEL_CREATE', 'Создать');
define ('KERNEL_RENAME', 'Переименовать');
define ('KERNEL_INSERT', 'Вставить');
define ('KERNEL_ADD', 'Добавить');
define ('KERNEL_APPLY', 'Применить');
define ('KERNEL_CHOOSE', 'Выбрать');
define ('KERNEL_LOAD', 'Загрузить');
define ('KERNEL_CLOSE', 'Закрыть');
define ('KERNEL_BROWSE', 'Обзор...');
define ('KERNEL_EDITOR', 'Редактор...');
define ('KERNEL_CLEAR', 'Очистить');
define ('KERNEL_DELETE', 'Удалить');
define ('KERNEL_EDIT', 'Редактировать');
define ('KERNEL_REFRESH', 'Обновить');
define ('KERNEL_PRINT', 'Распечатать');
define ('KERNEL_ENLARGE', 'Развернуть');
define ('KERNEL_BACK', 'назад');
define ('KERNEL_RUBRIK', 'выводить в рубрикаторе');
define ('KERNEL_PROMPT', 'Запрос системы');
define ('KERNEL_MAIN_TABS', 'Основные');
define ('KERNEL_MORE', 'подробнее...');
define ('KERNEL_GOTO', 'перейти...');
define ('KERNEL_LOADING', 'Идет загрузка! Пожалуйста, подождите...');
define ('KERNEL_SAVING', 'Идет сохранение! Пожалуйста, подождите...');
define ('KERNEL_IN', 'в');
define ('KERNEL_IN_FILE', 'в файле');
define ('KERNEL_ON_LINE', 'в строке');
define ('KERNEL_FROM', 'от');
define ('KERNEL_TO', 'до');
define ('KERNEL_WITHIN', 'из');
define ('KERNEL_FILTER', 'Фильтр');
define ('KERNEL_OR', 'ИЛИ');
define ('KERNEL_AND', 'И');
define ('KERNEL_YES', 'да');
define ('KERNEL_NO', 'нет');
define ('KERNEL_ACTIVE', 'Публиковать на сайте');
define ('KERNEL_STATUS', 'Статус публикации');
define ('KERNEL_STATUS_UNKNOWN', 'не известно (статус был удален)');
define ('KERNEL_STATUS_NO_RIGHTS', 'Для указанной группы элементов нет доступных статусов.');
define ('KERNEL_NEW_STATUS', 'Новый статус публикации');
define ('KERNEL_WORKFLOW_USERS_ERROR', 'Внимание! В системе нет пользователей кроме администраторов, для которых доступен выбранный статус публикации.');
define ('KERNEL_WORKFLOW_MESSAGE', 'Комментарий о смене статуса');
define ('KERNEL_WORKFLOW_USERS', 'Уведомить пользователей системы о смене статуса');
define ('KERNEL_WORKFLOW_NO_STATUS', 'Без статуса');
define ('KERNEL_WORKFLOW_NO_STATUS_AVAILABEL', 'Недостаточно прав для изменения статуса публикации выбранного элемента.');
define ('KERNEL_WORKFLOW_STATUS_MAIL_SUBJ', 'Изменение статуса публикации элемента');
define ('KERNEL_WORKFLOW_STATUS_MAIL_TEXT', 'На сайте %s пользователем <i>%s</i> в модуле <i>%s</i> был изменен статус элемента %s c <i>%s</i> на <i>%s</i>.');
define ('KERNEL_WORKFLOW_STATUS_GROUP_MAIL_TEXT', 'На сайте %s пользователем <i>%s</i> в модуле <i>%s</i> был изменен статус группы элементов %s c <i>%s</i> на <i>%s</i>.');
define ('KERNEL_WORKFLOW_COMMENT_STATUS_LABEL', 'Комментарий');
define ('KERNEL_WORKFLOW_EDIT_GROUP_TITLE', 'применить изменения');
define ('KERNEL_WORKFLOW_ADMINPAGE_LINK', 'Перейти в <a href="'.SB_COOKIE_DOMAIN.'/cms/admin/" target="_blank">панель администрирования сайта</a>');
define ('KERNEL_ACTIVE_STATUS', 'Текущий статус публикации');
define ('KERNEL_ACTIVE_PERIOD', 'Публиковать в период');
define ('KERNEL_DATE_FROM', 'с');
define ('KERNEL_DATE_TO', 'по');
define ('KERNEL_SETTINGS', 'Настройки');
define ('KERNEL_LATITUDE', 'широта');
define ('KERNEL_LONGTITUDE', 'долгота');
define ('KERNEL_POST_ADDRESS_ERROR', 'Не указан почтовый адрес');
define ('KERNEL_POST_ADDRESS_NO_MATCH_ERROR', 'Не найдено поле с почтовым адресом');
define ('KERNEL_GEO_ERROR_ZERO_RESULTS', 'Для указанного адреса не удалось получить координаты');
define ('KERNEL_GEO_ERROR_OVER_QUERY_LIMIT', 'Превышен лимит количества запросов.');
define ('KERNEL_GEO_ERROR_REQUEST_DENIED', 'Запрос отклонен сервисом.');
define ('KERNEL_GEO_ERROR', 'Ошибка!');

define ('KERNEL_PAGER_BEGIN', 'Начало');
define ('KERNEL_PAGER_PREV', 'Предыдущие');
define ('KERNEL_PAGER_NEXT', 'Следующие');
define ('KERNEL_PAGER_END', 'Конец');

define ('KERNEL_ERROR_PHP', 'Ошибка PHP');
define ('KERNEL_ERROR_BACKTRACE', 'Обратная трассировка');
define ('KERNEL_ERROR_BACKTRACE_FILE', 'Файл');
define ('KERNEL_ERROR_BACKTRACE_LINE', 'Строка');
define ('KERNEL_ERROR_BACKTRACE_FUNCTION', 'Функция');
define ('KERNEL_ERROR_BACKTRACE_PHP_KERNEL', 'Ядро PHP');
define ('KERNEL_ERROR_MYSQL', 'Ошибка MySQL');
define ('KERNEL_ERROR_SQL_QUERY', 'SQL-запрос');
define ('KERNEL_ERROR_CONNECT_DB', 'Ошибка! Не удалось подключиться к базе данных. Проверьте правильность реквизитов доступа в файле /cms/kernel/config.inc.php.');
define ('KERNEL_ERROR_VERSION_DB', 'Ошибка! Версия СУБД MySQL ниже 4.1! Обратитесь к своему хостинг-провайдеру для решения данной проблемы.');

define ('SB_AUTH_USER_LABEL', 'Пользователь системы');
define ('SB_AUTH_USER_OWN_ELEMS', 'Мои элементы');
define ('SB_AUTH_SECURITY_ERROR', 'IP-адрес или браузер пользователя, инициировавшего сессию и IP-адрес или браузер пользователя, производящего вход, не совпадают. Вход не возможен!');
define ('SB_AUTH_IP_ERROR', 'Ошибка! Вход в систему с Вашего IP-адреса запрещен.');
define ('SB_AUTH_USER_BLOCKED', 'Ваша учетная запись заблокирована администратором системы!');
define ('SB_AUTH_CATEG_BLOCKED', 'Группа пользователей, в которую входит Ваша учетная запись, заблокирована администратором системы!');
define ('SB_AUTH_NO_USER_OR_SESSION', 'Произошел таймаут сессии, войдите заново в систему. Если выполнить это действие не удастся, значит пользователь был удален или заблокирован.');
define ('SB_AUTH_TIMEOUT', 'Произошел таймаут сессии пользователя! Повторите вход в систему.');
define ('SB_AUTH_WRONG_LOGIN', 'Ошибка! Указанное имя пользователя не найдено.');
define ('SB_AUTH_WRONG_PASSWORD', 'Ошибка! Неверно указан пароль.');
define ('SB_AUTH_WRONG_PASSWORD_COUNT', 'Ошибка! Неверно указан пароль. Кол-во оставших попыток - <i>%s</i>.');
define ('SB_AUTH_WRONG_PASSWORD_BLOCK', 'Аккаунт пользователя <i>%s</i> был заблокирован. Возможно, была попытка подбора пароля злоумышленником.');
define ('SB_AUTH_WRONG_DOMAIN', 'Ошибка! У Вас нет прав на администрирование домена <i>%s</i>.');
define ('SB_AUTH_USER_ALREADY_LOGGED', 'Пользователь с таким логином уже работает в системе! Одновременный вход в систему под одним логином невозможен.');
define ('SB_AUTH_USER_ENTER_MSG', 'Вход пользователя с логином');
define ('SB_AUTH_USER_ENTER_ERROR_LOGIN_MSG', 'Попытка входа пользователя с некорректным логином <i>%s</i>.');
define ('SB_AUTH_USER_ENTER_ERROR_PASSWORD_MSG', 'Попытка входа пользователя <i>%s</i> с некорректным паролем.');
define ('SB_AUTH_USER_ENTER_ERROR_IP_MSG', 'Попытка входа пользователя <i>%s</i> с некорректного IP-адреса <i>%s</i>.');
define ('SB_AUTH_NO_KEY', 'Ключ системы не найден! Обратитесь к разработчикам системы (<a href="http://'.CMS_SITE_NAME.'">http://'.CMS_SITE_NAME.'</a>).');
define ('SB_AUTH_WRONG_KEY', 'Ключ системы неверный! Обратитесь к разработчикам системы (<a href="http://'.CMS_SITE_NAME.'">http://'.CMS_SITE_NAME.'</a>).');
define ('SB_AUTH_WRONG_MAIN_DOMAIN', 'Ошибка! Основной домен системы <i>%s</i> и домен, с которого осуществляется вход в систему, <i>%s</i> должны располагаться на одном аккаунте хостинга. Смотрите лицензионное соглашение.');
define ('SB_AUTH_LIC_EXPIRED', 'Срок действия Вашей лицензии истек! Вам необходимо обновить ключ или оплатить лицензию на сайте <a href="http://www.sbuilder.ru/">sbuilder.ru</a>.');
define ('SB_AUTH_NO_LOGIN_OR_PASSWORD', 'Не указаны имя пользователя или пароль!');
define ('SB_AUTH_LOGOUT', 'Пользователь %s успешно вышел из системы.');
define ('SB_LIC_RENEW_OK', 'Лицензионный ключ успешно обновлен.');
define ('SB_LIC_RENEW_ERROR', 'Ошибка! Не удалось обновить лицензионный ключ.');

define ('SB_CONTAINERS_NO_ITEMS', 'Вывод контейнеров всех модулей на Рабочем столе отключен.<br><br>Воспользуйтесь пунктом <i>Меню пользователя - Настройки интерфейса</i> для подключения необходимых контейнеров.');

define ('KERNEL_MENU_USER', 'Меню пользователя');
define ('KERNEL_MENU_DEVELOP', 'Меню разработчика');
define ('KERNEL_MENU_DEVELOP_DESIGN', 'Макеты дизайна компонентов');
define ('KERNEL_MENU_ADMIN', 'Меню администратора');
define ('KERNEL_MENU_LOGS', 'Журналы');
define ('KERNEL_MENU_SUPPORT', 'Справка и поддержка');
define ('KERNEL_MENU_DEVELOP_SERVICES', 'Сервисы');

define ('SB_FOLDERS_PASTE_FILES_MENU', 'Вставить файлы');
define ('SB_FOLDERS_PASTE_FOLDS_MENU', 'Вставить папку');
define ('SB_FOLDERS_ADD_MENU', 'Создать папку');
define ('SB_FOLDERS_EDIT_MENU', 'Переименовать папку');
define ('SB_FOLDERS_DELETE_MENU', 'Удалить папку');
define ('SB_FOLDERS_COPY_MENU', 'Копировать папку');
define ('SB_FOLDERS_CUT_MENU', 'Вырезать папку');
define ('SB_FOLDERS_REFRESH_MENU', 'Обновить');
define ('SB_FOLDERS_UPLOAD_MENU', 'Загрузить файлы');
define ('SB_FOLDERS_RIGHTS_MENU', 'Права доступа');

define ('SB_FILELIST_READ_ERROR_MSG', 'Ошибка при чтении папки <i>%s</i>! Возможно, папка была удалена.');
define ('SB_FILELIST_DENY_ERROR_MSG', 'У Вас недостаточно прав доступа к выбранной папке! Обратитесь к администратору системы.');
define ('SB_FILELIST_NO_FILES', 'Папка не содержит файлов.');
define ('SB_FILELIST_FILE_SIZE', 'Размер');
define ('SB_FILELIST_FILE_SIZE_KB', 'Кбайт');
define ('SB_FILELIST_FILE_SIZE_BYTE', 'байт');
define ('SB_FILELIST_FILE_TIME', 'Последнее изменение');
define ('SB_FILELIST_FILE_EXISTS', 'Ошибка! Существует файл с указанным именем.');

define ('SB_FILELIST_COPY_MENU', 'Копировать файлы');
define ('SB_FILELIST_CUT_MENU', 'Вырезать файлы');
define ('SB_FILELIST_SELECTALL_MENU', 'Выделить все');
define ('SB_FILELIST_RENAME_MENU', 'Переименовать файл');
define ('SB_FILELIST_DELETE_MENU', 'Удалить файлы');
define ('SB_FILELIST_REFRESH_MENU', 'Обновить');
define ('SB_FILELIST_SORT_BY_TIME_MENU', 'Сортировать по Времени изменения файла');
define ('SB_FILELIST_SORT_BY_SIZE_MENU', 'Сортировать по Размеру файла');
define ('SB_FILELIST_SORT_BY_NAME_MENU', 'Сортировать по Имени файла');

define ('SB_UPLOADER_NO_FILE', 'Файл не был загружен!');
define ('SB_UPLOADER_SIZE_ERROR', 'Размер файла не должен превышать %s Кб! Это ограничение можно изменить в настройках системы.');
define ('SB_UPLOADER_IMAGE_SIZE_ERROR', 'Размеры загружаемого изображения не должны быть больше %s пикселей по ширине и %s пикселей по высоте! Это ограничение можно изменить в настройках системы.');
define ('SB_UPLOADER_TYPE_ERROR', 'Запрещено закачивать файлы данного типа!');
define ('SB_UPLOADER_MKPATH_ERROR', 'Ошибка при создании директории <i>%s</i>! Возможно у Вас недостаточно прав доступа.');
define ('SB_UPLOADER_FILE_EXIST_ERROR', 'Файл с именем <i>%s</i> уже существует!');
define ('SB_UPLOADER_FILE_SAVE_ERROR', 'Ошибка при сохранении файла <i>%s</i>!');

define ('SB_CATEGS_ROOT_NAME', 'Разделы');
define ('SB_CATEGS_PASTE_MENU', 'Вставить элементы');
define ('SB_CATEGS_PASTE_LINKS_MENU', 'Вставить ссылки на элементы');
define ('SB_CATEGS_PASTE_CATEGS_MENU', 'Вставить разделы');
define ('SB_CATEGS_ELEMENTS_PASTE_CATEGS_MENU', 'Вставить разделы и элементы');
define ('SB_CATEGS_EDIT_GROUP', 'Групповое редактирование');
define ('SB_CATEGS_ADD_MENU', 'Добавить подраздел');
define ('SB_CATEGS_ASTE_CATEGS_WITH_ELEMENTS_MENU', 'Вставить разделы и элементы');
define ('SB_CATEGS_EDIT_MENU', 'Редактировать раздел');
define ('SB_CATEGS_DELETE_MENU', 'Удалить раздел');
define ('SB_CATEGS_DELETE_WITH_ELEMENT_MENU', 'Удалить разделы и элементы');
define ('SB_CATEGS_COPY_MENU', 'Копировать раздел');
define ('SB_CATEGS_CUT_MENU', 'Вырезать раздел');
define ('SB_CATEGS_REFRESH_MENU', 'Обновить');
define ('SB_CATEGS_RIGHTS_MENU', 'Права доступа');
define ('SB_CATEGS_UP_MENU', 'Переместить вверх');
define ('SB_CATEGS_DOWN_MENU', 'Переместить вниз');

define('SB_MAIL_SMTP_MESSAGES_SIZE', 'Ошибка! Размер сообщения, отправляемого через SMTP, превышает допустимый.');
define('SB_MAIL_SMTP_GET_FILE_ERR', 'Ошибка! Не удалось получить данные файла.');
define('SB_MAIL_SMTP_EMAIL_ERR', 'Ошибка! Не корректный E-mail адрес для отправки через SMTP.');
define('SB_MAIL_SMTP_AUTH_ERR', 'Ошибка! Не удалось авторизоваться на SMTP сервере.');
define('SB_MAIL_SMTP_CONNECT_ERR', 'Ошибка! Не удалось соединиться с сервером SMTP.');

define ('SB_ELEMS_DENY_MSG', 'Доступ к выбранному разделу запрещен администратором!');
define ('SB_ELEMS_NO_CATEG_MSG', 'Не удалось загрузить содержимое раздела! Возможно, раздел был удален другим пользователем.');
define ('SB_ELEMS_LINK_TEXT', 'ссылка');
define ('SB_ELEMS_LAST_MODIFIED', 'Последнее изменение');
define ('SB_ELEMS_NO_ELEMS_MSG', 'Раздел не содержит элементов!');
define ('SB_ELEMS_NO_FUNC_MSG', 'Функция получения содержимого раздела не определена!');
define ('SB_ELEMS_BEGIN', 'в начало');
define ('SB_ELEMS_PREV', 'предыдущие');
define ('SB_ELEMS_NEXT', 'следующие');
define ('SB_ELEMS_END', 'в конец');
define ('SB_ELEMS_FILTER', 'Фильтр');
define ('SB_ELEMS_FILTER_ON', 'Фильтр задействован');
define ('SB_ELEMS_COPY_MENU', 'Копировать элементы');
define ('SB_ELEMS_CUT_MENU', 'Вырезать элементы');
define ('SB_ELEMS_SELECTALL_MENU', 'Выделить всё');
define ('SB_ELEMS_EDIT_MENU', 'Редактировать');
define ('SB_ELEMS_ADD_MENU', 'Добавить элемент');
define ('SB_ELEMS_DELETE_MENU', 'Удалить');
define ('SB_ELEMS_REFRESH_MENU', 'Обновить');
define ('SB_ELEMS_IS_LINK', 'Перейти к элементу, на который ссылается данный элемент');
define ('SB_ELEMS_HISTORY', 'История изменений');
define ('SB_ELEMS_SORT_BY', 'Сортировать по');
define ('SB_ELEMS_ACTIVE_BY', 'Переключить');

$GLOBALS['sb_watermark_positions'] = array(
                 'TL'  => 'Верхний левый угол',
                 'TM'  => 'Сверху по центру',
                 'TR'  => 'Верхний правый угол',
                 'CL'  => 'По центру слева',
                 'C'   => 'По центру',
                 'CR'  => 'По центру справа',
                 'BL'  => 'Нижний левый угол',
                 'BM'  => 'Снизу по центру',
                 'BR'  => 'Нижний правый угол',
                 'RND' => 'Случайным образом');

$GLOBALS['sb_watermark_fonts'] = array(
                 'arial.ttf'   => 'Arial',
                 'arialbd.ttf'   => 'Arial (Полужирный)',
                 'times.ttf'     => 'Times New Roman',
                 'timesbd.ttf'   => 'Times New Roman (Полужирный)',
                 'verdana.ttf'   => 'Verdana',
                 'verdanabd.ttf' => 'Verdana (Полужирный)');

$GLOBALS['sb_watermark_sizes'] = array(
                 '8'  => '8 px',
                 '9'  => '9 px',
                 '10' => '10 px',
                 '11' => '11 px',
                 '12' => '12 px',
                 '14' => '14 px',
                 '16' => '16 px',
                 '18' => '18 px',
                 '20' => '20 px',
                 '22' => '22 px',
                 '24' => '24 px',
                 '26' => '26 px',
                 '28' => '28 px',
                 '36' => '36 px',
                 '48' => '48 px',
                 '72' => '72 px');

define ('SB_LAYOUT_NO_CATEG', 'Разделы не выбраны');
define ('SB_LAYOUT_NO_SPRAV', 'Значения не выбраны');
define ('SB_LAYOUT_EDIT_SPRAV_LINK', 'Редактировать справочник');
define ('SB_LAYOUT_DELETE_FOTO', 'удалить изображение с сервера');
define ('SB_LAYOUT_DELETE_FILE', 'удалить файл с сервера');
define ('SB_LAYOUT_PLUGIN_IDENT_ERROR', 'Ошибка! Не удалось найти указанный идентификатор модуля.');
define ('SB_LAYOUT_PLUGIN_FIELDS_REQ', '<span style="font-weight:normal">Обязательно для заполнения</span>');
define ('SB_LAYOUT_PLUGIN_FIELDS_ERROR', 'Ошибка! Не все данные переданы корректно на сервер.');
define ('SB_LAYOUT_PLUGIN_FIELDS_MIN_ERROR', 'Ошибка! Значение поля <i>%s</i> не должно быть меньше %s.');
define ('SB_LAYOUT_PLUGIN_FIELDS_MAX_ERROR', 'Ошибка! Значение поля <i>%s</i> не должно быть больше %s.');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_ERROR', 'Ошибка! Изображение <i>%s</i> некорректно загружено на сервер.');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR', 'Ошибка! Не удалось сохранить изображение <i>%s</i>.');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR2', 'Ошибка! Неверный тип файла для изображения <i>%s</i>.');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR3', 'Ошибка при загрузке на сервер файла <i>%s</i>!');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_RESIZE_ERROR', 'Ошибка при изменении размеров изображения <i>%s</i>!');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_RESIZE_ERROR2', 'Ошибка! Отсутствует поле источника изображения для миниатюры.');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_WATERMARK_ERROR', 'Ошибка при наложении водяного знака на изображение <i>%s</i>!');
define ('SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_OK', 'Файл <i>%s</i> успешно загружен на сервер!');
define ('SB_LAYOUT_PLUGIN_FIELDS_MIN_LENGTH_ERROR', 'Ошибка! Длина значения поля <i>%s</i> не должна быть меньше %s.');
define ('SB_LAYOUT_PLUGIN_FIELDS_MAX_LENGTH_ERROR', 'Ошибка! Длина значения поля <i>%s</i> не должна быть больше %s.');
define ('SB_LAYOUT_PLUGIN_FIELDS_TABLE_ERROR', 'Некорректно заполнено поле "%s"');

define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE', 'Поле ввода');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE', 'Поле выбора');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_CHK_TITLE', 'Флажок');

define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_FIELD', 'Изображение');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_FIELD', 'Имя файла');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_URL_FIELD', 'Адрес файла (URL)');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG', '<img src=\'{PIC_SRC}\' />');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_TAG', '{FILE_NAME}');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_URL_TAG', '{FILE_URL}');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_NOW', 'Отображение текущего изображения для поля %s');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_NOW', 'Отображение текущего файла для поля %s');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_NOW_TAG', 'Текущее фото для поля %s');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_NOW_TAG', 'Текущий файл для поля %s');

define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG', 'HTML-код поля');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_SELECT', 'HTML-код поля (select)');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG_CHECKBOX', 'HTML-код поля (checkbox)');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG', 'Введенное пользователем значение поля');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG', 'Опции выбора');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG', 'Выбрана/Не выбрана опция');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_LO', 'начало интервала');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_HI', 'конец интервала');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_MAP_CODE', 'код карты');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TITLE', 'Текущее изображение для поля %s');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_TITLE', 'Текущий файл для поля %s');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_DELETE', 'Флажок для удаления изображения поля %s');
define ('SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_DELETE', 'Флажок для удаления файла поля %s');

define ('SB_LAYOUT_FIND_REPLACE', 'Поиск / Замена');
define ('SB_LAYOUT_HIGHLIGHT', 'Включить подсветку синтаксиса');
define ('SB_LAYOUT_TAGS', 'Теги');
define ('SB_LAYOUT_INSERT', 'Вставить');
define ('SB_LAYOUT_ENLARGE', 'Развернуть / Свернуть');
define ('SB_LAYOUT_VALUE', 'Значение поля');
define ('SB_LAYOUT_VALUE_CHECKBOX', 'Значение помеченного / не помеченного флажка');
define ('SB_LAYOUT_MAX', 'максимум');
define ('SB_LAYOUT_VALUE_SIZE', 'Размер файла (в байтах)');
define ('SB_LAYOUT_VALUE_WIDTH', 'Ширина изображения (в пикселях)');
define ('SB_LAYOUT_VALUE_HEIGHT', 'Высота изображения (в пикселях)');
define ('SB_LAYOUT_SPRAV_TITLE', 'Название элемента справочника');
define ('SB_LAYOUT_SPRAV_ID', 'ID элемента справочника');
define ('SB_LAYOUT_SPRAV_DESC1', 'Описание 1 элемента справочника');
define ('SB_LAYOUT_SPRAV_DESC2', 'Описание 2 элемента справочника');
define ('SB_LAYOUT_SPRAV_DESC3', 'Описание 3 элемента справочника');
define ('SB_LAYOUT_SPRAV_CAT_TITLE', 'Название раздела справочника');
define ('SB_LAYOUT_SPRAV_CAT_ID', 'ID раздела справочника');

define ('SB_LAYOUT_ELEM_CAT_TITLE', 'Название раздела элементов');
define ('SB_LAYOUT_ELEM_CAT_ID', 'ID раздела элементов');
define ('SB_LAYOUT_ELEM_TITLE', 'Название элемента');
define ('SB_LAYOUT_ELEM_ID', 'ID элемента');

define ('SB_LAYOUT_SPRAV_NO_DESIGN_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода элементов справочника (<i>Меню разработчика - Макеты дизайна компонентов - Общие - Вывод элементов справочника</i>). Значения элементов справочников выводиться не будут.');
define ('SB_LAYOUT_LINK_SPRAV', 'связанный список');
define ('SB_LAYOUT_PLUGIN_FIELDS_CHECKED', 'Выводить элементы, для которых помечены флажки');
define ('SB_LAYOUT_PLUGIN_CATEGS_CHECKED', 'Выводить разделы, для которых помечены флажки');
define ('SB_LAYOUT_PLUGIN_FIELDS_CHECKED_LABEL', 'Отметить или снять отметку с названия флажка Вы можете с помощью <b>Ctrl + щелчек мышью</b>.');
define ('SB_LAYOUT_COUNT_TITLE_ARRAY_ERROR', 'Кол-во заголовков в массиве не соотв. кол-ву значений!');
define ('SB_LAYOUT_VALUE_CHAR_COUNT', 'Осталось символов:');

define ('SB_LAYOUT_UPLOAD_TOO_MANY_FILES', 'Ошибка! Вы пытаетесь загрузить несколько файлов.<br /> Выберите один файл.');
define ('SB_LAYOUT_UPLOAD_SIZE_TOO_BIG', 'Ошибка! Размер файла превышает допустимый.');
define ('SB_LAYOUT_UPLOAD_FILE_EMPTY', 'Ошибка! Вы выбрали пустой файл. Выберите другой файл.');
define ('SB_LAYOUT_UPLOAD_FILE_TYPE_ERROR', 'Ошибка! Данный тип файлов запрещен для загрузки.');
define ('SB_LAYOUT_UPLOAD_ERROR', 'Произошла ошибка при загрузке файла.');
define ('SB_LAYOUT_UPLOAD_SERVER_ERROR', 'Ошибка! Не удалось загрузить файл на сервер.<br />Подробности смотрите в Системном журнале.');
define ('SB_LAYOUT_UPLOAD_UPLOADING', 'Идет загрузка файла на сервер...');
define ('SB_LAYOUT_UPLOAD_COMPLETE', 'Загрузка файла на сервер завершена.');
define ('SB_LAYOUT_UPLOAD_CANCALED', 'Загрузка файла на сервер отменена.');
define ('SB_LAYOUT_UPLOAD_STOPPED', 'Загрузка файла на сервер остановлена.');
define ('SB_LAYOUT_UPLOAD_TYPE_LOCAL', 'С локального компьютера');
define ('SB_LAYOUT_UPLOAD_TYPE_SERVER', 'С сервера');
define ('SB_LAYOUT_UPLOAD_TYPE_GENERATE', 'Генерировать миниатюру');

define ('SB_LAYOUT_GET_FLASH', 'Установить Flash-плеер');
define ('SB_LAYOUT_GET_FLASH_HINT', '<b>Внимание:</b> Для просмотра графиков Вам необходимо установить Flash-плеер версии 9.0 или выше.');

define ('SB_LAYOUT_GOOGLE_MAP_CODE_FULL', "<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false'></script>
<script type='text/javascript'>
google.maps.event.addDomListener(window, 'load', function ()
{
    var latlng = new google.maps.LatLng('{LATITUDE}', '{LONGTITUDE}');
    var myOptions =
    {
      zoom: 15,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    var map = new google.maps.Map(document.getElementById('{DIV_ID}'), myOptions);
    var marker = new google.maps.Marker({position: latlng, map: map});
});
</script>
<div id='{DIV_ID}' style='width: 400px; height: 400px;'></div>");

define ('SB_LAYOUT_GOOGLE_MAP_CODE_INPUT', "<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false'></script>
<script type='text/javascript'>
google.maps.event.addDomListener(window, 'load', function ()
{
    var latlng = new google.maps.LatLng(document.getElementById('{LATITUDE_ID}').value, document.getElementById('{LONGTITUDE_ID}').value);
    var myOptions =
    {
      zoom: 15,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    var map = new google.maps.Map(document.getElementById('{DIV_ID}'), myOptions);
    var marker = new google.maps.Marker({draggable: true, position: latlng, map: map});

    google.maps.event.addListener(marker, 'drag', function()
    {
        var lat_lng = marker.getPosition();

        document.getElementById('{LATITUDE_ID}').value = lat_lng.lat();
    	document.getElementById('{LONGTITUDE_ID}').value = lat_lng.lng();
  	});
});
</script>
<div id='{DIV_ID}' style='width: 400px; height: 400px;'></div>");

define ('SB_LAYOUT_YANDEX_MAPS_ERROR', '<b>Внимание:</b> Для отображения Яндекс.Карт необходимо указать API-ключ в настройках системы для соответствующего домена.');

define ('SB_LAYOUT_YANDEX_MAP_CODE_FULL', "<script src='http://api-maps.yandex.ru/2.0/?lang=ru-RU&load=package.full' type='text/javascript'></script>
<script type='text/javascript'>
ymaps.ready(function(){
    var b_map = new ymaps.Map('{DIV_ID}', {
         center: ['{LATITUDE}', '{LONGTITUDE}'],
   	     zoom: 15
    });

    b_map.controls.add('typeSelector').add('zoomControl').add('mapTools');

    var placemark = new ymaps.Placemark(['{LATITUDE}', '{LONGTITUDE}']);

    b_map.geoObjects.add(placemark);
});
</script>
<div id='{DIV_ID}' style='width: 400px; height: 400px;'></div>");

define ('SB_LAYOUT_YANDEX_MAP_CODE_INPUT', "<script src='http://api-maps.yandex.ru/2.0/?lang=ru-RU&load=package.full' type='text/javascript'></script>
<script type='text/javascript'>
ymaps.ready(function(){
    var lng = document.getElementById('{LONGTITUDE_ID}').value;
    var lat = document.getElementById('{LATITUDE_ID}').value;

    if (lng == '')
        lng = '37.6167314605713';

    if (lat == '')
        lat = '55.755917028179084';

    var b_map = new ymaps.Map('{DIV_ID}', {
         center: [lat, lng],
   	     zoom: 15
    });

    b_map.controls.add('typeSelector').add('zoomControl').add('mapTools');

    var placemark = new ymaps.Placemark(
        [lat, lng],
        {hintContent: 'Передвиньте метку для изменения координат.'},
        {draggable: true}
    );

    b_map.geoObjects.add(placemark);

    placemark.events.add('drag', function(e) {
        var point_str = new String(placemark.geometry.getBounds());
        point_str = point_str.split(',');

        document.getElementById('{LATITUDE_ID}').value = point_str[0];
        document.getElementById('{LONGTITUDE_ID}').value = point_str[1];
    });
});
</script>
<div id='{DIV_ID}' style='width: 400px; height: 400px;'></div>");

define ('SB_LAYOUT_PLAN_WEEK_DAY_1', 'Пн');
define ('SB_LAYOUT_PLAN_WEEK_DAY_2', 'Вт');
define ('SB_LAYOUT_PLAN_WEEK_DAY_3', 'Ср');
define ('SB_LAYOUT_PLAN_WEEK_DAY_4', 'Чт');
define ('SB_LAYOUT_PLAN_WEEK_DAY_5', 'Пт');
define ('SB_LAYOUT_PLAN_WEEK_DAY_6', 'Сб');
define ('SB_LAYOUT_PLAN_WEEK_DAY_7', 'Вс');
define ('SB_LAYOUT_PLAN_MONTH_1', 'Январь');
define ('SB_LAYOUT_PLAN_MONTH_2', 'Февраль');
define ('SB_LAYOUT_PLAN_MONTH_3', 'Март');
define ('SB_LAYOUT_PLAN_MONTH_4', 'Апрель');
define ('SB_LAYOUT_PLAN_MONTH_5', 'Май');
define ('SB_LAYOUT_PLAN_MONTH_6', 'Июнь');
define ('SB_LAYOUT_PLAN_MONTH_7', 'Июль');
define ('SB_LAYOUT_PLAN_MONTH_8', 'Август');
define ('SB_LAYOUT_PLAN_MONTH_9', 'Сентябрь');
define ('SB_LAYOUT_PLAN_MONTH_10', 'Октябрь');
define ('SB_LAYOUT_PLAN_MONTH_11', 'Ноябрь');
define ('SB_LAYOUT_PLAN_MONTH_12', 'Декабрь');

define ('RIGHTS_H_CATEGS_EDIT_RIGHT', 'Управление разделами');
define ('RIGHTS_H_CATEGS_DELETE_RIGHT', 'Удаление разделов');
define ('RIGHTS_H_CATEGS_RIGHTS_RIGHT', 'Установка прав доступа к разделам');
define ('RIGHTS_H_ELEMS_EDIT_RIGHT', 'Редактирование элементов');
define ('RIGHTS_H_ELEMS_DELETE_RIGHT', 'Удаление элементов');
define ('RIGHTS_H_ELEMS_PUBLIC_RIGHT', 'Публикация на сайте');
define ('RIGHTS_H_ELEMS_COM_SHOW_RIGHT', 'Просмотр комментариев');
define ('RIGHTS_H_ELEMS_COM_ADD_RIGHT', 'Добавление комментариев');
define ('RIGHTS_H_ELEMS_COM_DEL_RIGHT', 'Удаление комментариев');
define ('RIGHTS_H_ELEMS_COM_EDIT_RIGHT', 'Редактирование комментариев');
define ('RIGHTS_H_DESIGN_READ_RIGHT', 'Просмотр макетов дизайна');
define ('RIGHTS_H_DESIGN_EDIT_RIGHT', 'Редактирование макетов дизайна');

define ('KERNEL_ELEM_NOT_CHOOSED', 'не выбран');
define ('KERNEL_ELEM_NOT_CHOOSED_PAGES', 'не выбран (по умолчанию)');
define ('KERNEL_ELEM_TAG', 'Редактируемый блок');
define ('KERNEL_ELEM_TYPE', 'Тип компонента');
define ('KERNEL_ELEM_SEARCH', 'Тип индексации');
define ('KERNEL_ELEM_EDIT', 'Редактируемый компонент');
define ('KERNEL_ELEM_YANDEX', '&lt;noindex&gt;');

define ('KERNEL_ELEM_TAGS_SEARCH_0', 'Не индексировать');
define ('KERNEL_ELEM_TAGS_SEARCH_INDEX', 'Индексировать');
define ('KERNEL_ELEM_TAGS_SEARCH_1', 'текст и ссылки');
define ('KERNEL_ELEM_TAGS_SEARCH_2', 'только ссылки');
define ('KERNEL_ELEM_TAGS_SEARCH_3', 'только текст');

define ('KERNEL_DESIGN_ROOT_NAME', 'Макеты дизайна');
define ('KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE', 'Вставить макеты дизайна');
define ('KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE', 'Добавить новый макет дизайна');
define ('KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE', 'Редактировать макет дизайна');
define ('KERNEL_DESIGN_ELEMS_DELETE_MENU_TITLE', 'Удалить макет дизайна');
define ('KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE', 'Копировать макет дизайна');
define ('KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE', 'Вырезать макет дизайна');

define ('KERNEL_LINK_PAGES_MENU_ITEM', 'Список связанных страниц');
define ('KERNEL_LINK_TEMPS_MENU_ITEM', 'Список связанных макетов дизайна');

define ('KERNEL_USED_ON_PAGES', 'Используется на страницах');
define ('KERNEL_USED_ON_TEMPS', 'Используется в макетах дизайна');
define ('KERNEL_USED_IN_MENU', 'Используется в меню');
define ('KERNEL_NOT_USED', 'не используется');
define ('KERNEL_USED', 'используется');

define ('KERNEL_DAY_TAG', 'Число');
define ('KERNEL_WEEKDAY_TAG_SHORT', 'День недели (короткое)');
define ('KERNEL_WEEKDAY_TAG_FULL', 'День недели (полное)');
define ('KERNEL_MONTH_TAG', 'Месяц (число)');
define ('KERNEL_MONTH_NAME_TAG', 'Месяц (название)');
define ('KERNEL_YEAR_4_TAG', 'Год (4 цифры)');
define ('KERNEL_YEAR_2_TAG', 'Год (2 цифры)');
define ('KERNEL_HOURS_TAG', 'Часы');
define ('KERNEL_MINUTES_TAG', 'Минуты');
define ('KERNEL_TIMESTAMP_TAG', 'Unix Timestamp');

define ('SB_DUMPER_ERROR_ZLIB', 'Ошибка! Не удалось упаковать файл с дампом. Библиотека <i>zlib</i> не установлена на сервере или версия PHP ниже 5.2.0. Обратитесь к Вашему хостинг-провайдеру для решения данной проблемы.');
define ('SB_DUMPER_ERROR_ZIP_OPEN', 'Ошибка при упаковке файла с дампом. Не удалось открыть файл <i>%s</i>.');
define ('SB_DUMPER_ERROR_ZIP_ADD', 'Ошибка при упаковке файла с дампом. Не удалось добавить в архив <i>%s</i> файл <i>%s</i>.');
define ('SB_DUMPER_ERROR_WRITE_OUT_FILE', 'Ошибка! Не удалось записать данные в файл <i>%s</i>.');
define ('SB_DUMPER_ERROR_OPEN_OUT_FILE', 'Ошибка создания файла <i>%s</i> для записи дампа.');
define ('SB_DUMPER_ERROR_SELECT_DB', 'Ошибка! Не удалось получить название базы данных.');
define ('SB_DUMPER_ERROR_OPEN_IN_FILE', 'Ошибка! Не удалось открыть файл <i>%s</i>.');
define ('SB_DUMPER_ERROR_GET_DOMAINS', 'Ошибка! Не удалось извлечь информацию о доменах из файла с дампом <i>%s</i>.');
define ('SB_DUMPER_ERROR_BACKUP_CMS', 'Ошибка! Не удалось сохранить файлы системы.');
define ('SB_DUMPER_ERROR_ZIP_CMS_CREATE', 'Ошибка! Не удалось создать архив с файлами системы.');
define ('SB_DUMPER_ERROR_ZIP_CMS_EXTRACT', 'Не удалось извлечь из архива файлы системы. Возможно отсутствует библиотека Zlib. Обратитесь к вашему провайдеру для решения этой проблемы.');
define ('SB_DUMPER_ERROR_CMS_RESTORE', 'Не удалось восстановить файлы системы.');

define ('KERNEL_EDITOR_CUT', 'Вырезать');
define ('KERNEL_EDITOR_COPY', 'Копировать');
define ('KERNEL_EDITOR_PASTE', 'Вставить');
define ('KERNEL_EDITOR_FIND', 'Найти/Заменить текст');
define ('KERNEL_EDITOR_UNDO', 'Отменить');
define ('KERNEL_EDITOR_REDO', 'Вернуть');
define ('KERNEL_EDITOR_SELECT_ALL', 'Выделить все');

define ('KERNEL_PAGER_NUMBERS_PERPAGE', 'Кол-во элементов на странице');
define ('KERNEL_PAGER_NUMBERS_COUNT', 'Кол-во выводимых номеров страниц');
define ('KERNEL_PAGER_NUMBERS_FIRST_LINK', 'Ссылка на первую страницу');
define ('KERNEL_PAGER_NUMBERS_PREV_LINK', 'Ссылка на предыдущую страницу');
define ('KERNEL_PAGER_NUMBERS_NEXT_LINK', 'Ссылка на следующую страницу');
define ('KERNEL_PAGER_NUMBERS_END_LINK', 'Ссылка на последнюю страницу');
define ('KERNEL_PAGER_NUMBERS_NUMBER', 'Номер страницы');
define ('KERNEL_PAGER_NUMBERS_NUMBER_LINK', 'Ссылка на страницу');
define ('KERNEL_PAGER_NUMBERS_CUR_NUMBER', 'Номер текущей страницы');
define ('KERNEL_PAGER_NUM_LIST', 'Номера страниц');

define ('SB_TEMPLATES_ERROR1', 'Произошла ошибка пре генерации страницы <i>%s</i>. Раздел, содержащий страницу, либо макет дизайна сайта, использующийся страницей, были удалены.');
define ('SB_TEMPLATES_ERROR2', 'Произошла ошибка пре генерации страницы <i>%s</i>. Файл <i>%s</i> не доступен для записи.');
define ('SB_TEMPLATES_ERROR3', 'Произошла ошибка пре генерации страницы <i>%s</i>. Директория <i>%s</i> не доступна для записи.');
define ('SB_TEMPLATES_ERROR4', 'Произошла ошибка пре генерации страницы <i>%s</i>. Не удалось создать директорию <i>%s</i>.');
define ('SB_TEMPLATES_ERROR5', 'Произошла ошибка пре генерации страницы <i>%s</i>. Не удалось определить домен страницы.');
define ('SB_TEMPLATES_OK', 'Страница <i>%s</i> успешно сгенерирована.');
define ('SB_TEMPLATES_NO_ELEM', 'С редактируемым блоком <i>%s</i> компонент не связан.');
define ('SB_TEMPLATES_DRAGGING', 'Перетащите поверх редактируемого блока (выделены пунктиром)');
define ('SB_TEMPLATES_HINT', 'Двойной щелчёк, чтобы редактировать...');

define ('SB_CACHE_LOG_DROP_ALL', 'Кэш для домена <i>%s</i> успешно сброшен.');
define ('SB_CACHE_LOG_DROP_PLUGIN', 'Кэш модуля <i>%s</i> для домена <i>%s</i> успешно сброшен.');

define ('SB_CACHE_LOG_DROP_ALL_ERROR', 'Не удалось сбросить кэш для домена <i>%s</i>.');
define ('SB_CACHE_LOG_DROP_PLUGIN_ERROR', 'Не удалось сбросить кэш модуля <i>%s</i> для домена <i>%s</i>.');


define ('KERNEL_PROG_PL_CB_K_CRON_ERR', 'Ошибка! Не удалось установить соединение с сервисом ЦБ');

define ('KERNEL_PROG_PL_DUMPER_CRON_DESCR', 'Автоматическое создание точки восстановления (расписание задается в модуле Планировщик заданий).');
define ('KERNEL_PROG_PL_DUMPER_CRON_CREATE_ERROR', 'Ошибка автоматического создания точки восстановления.');
define ('KERNEL_PROG_PL_DUMPER_CRON_CREATE_OK', 'Точка восстановления успешно создана.');
define ('KERNEL_PROG_PL_DUMPER_CRON_PACK_ERROR', 'Точка восстановления создана, но упаковать файл точки не удалось.');

define ('KERNEL_PROG_PL_TESTER_ERROR_MSG_NO_ANSWER', 'Нет никаких ответов');

define ('KERNEL_PROG_NO_TEMPLATE', 'Ошибка на странице <i>%s</i> модуля <i>%s</i> - не удалось найти макет дизайна вывода компонента.');
define ('KERNEL_PROG_LINKS_NO_ELEMENT', 'Ошибка на странице <i>%s</i> модуля <i>%s</i> - не удалось найти связанный элемент .');
define ('KERNEL_PROG_LINKS_NO_ELEMENT_PLUGIN_MAKER', 'Ошибка на странице <i>%s</i> - не удалось найти связанный элемент .');

define ('KERNEL_PROG_NEWS_PLUGIN', 'Новостная лента');
define ('KERNEL_PROG_FORUM_PLUGIN', 'Форум');
define ('KERNEL_PROG_SPRAV_PLUGIN', 'Справочники');
define ('KERNEL_PROG_CLOUDS_PLUGIN', 'Облако тегов');
define ('KERNEL_PROG_COMMENTS_PLUGIN', 'Вывод комментариев');
define ('KERNEL_PROG_COMMENTS_FORM_PLUGIN', 'Форма комментариев');
define ('KERNEL_PROG_FAQ_PLUGIN', 'Вопрос-Ответ');
define ('KERNEL_PROG_SEARCH_PLUGIN', 'Поиск по сайту');
define ('KERNEL_PROG_SITE_USERS_PLUGIN', 'Пользователи сайта');
define ('KERNEL_PROG_SERVICIES_CB_PLUGIN', 'Сервисы ЦБ');
define ('KERNEL_PROG_MENU_PLUGIN', 'Навигация по сайту');
define ('KERNEL_PROG_RUTUBE_PLUGIN', 'Библиотека видеороликов');
define ('KERNEL_PROG_IMAGELIB_PLUGIN', 'Библиотека  изображений');
define ('KERNEL_PROG_BASKET_PLUGIN', 'Интернет-магазин');
define ('KERNEL_PROG_CATEGS_PLUGIN', 'Вывод разделов');
define ('KERNEL_PROG_SITEUSERS_ADD_OK', 'Пользователь <i>%s</i> успешно зарегистрирован.');
define ('KERNEL_PROG_SITEUSERS_ADD_ERROR', 'Не удалось зарегистрировать пользователя <i>%s</i>.');
define ('KERNEL_PROG_SITEUSERS_AUTH_ERROR', 'Не удалось авторизовать пользователя <i>%s</i>.');
define ('KERNEL_PROG_SITEUSERS_ACCOUNT_ISSET', 'Такой аккаунт уже есть в системе.');
define ('KERNEL_PROG_SITEUSERS_UPDATE_OK', 'Пользователь <i>%s</i> успешно изменил свои персональные данные.');
define ('KERNEL_PROG_SITEUSERS_NO_LOGIN_PARAMS', 'Сессия не содержит параметров формы регистрации');
define ('KERNEL_PROG_SITEUSERS_NO_IDS', 'Не указаны группы для пользователей из соц. сетей');
define ('KERNEL_PROG_SITEUSERS_WRONG_IDS', 'Возможно группы для пользователей из соц. сетей были удалены администратором.');
define ('KERNEL_PROG_SITEUSERS_NO_SU_ID', 'Не определен уникальный идентификатор');
define ('KERNEL_PROG_PLUGINS_ADD_ERROR', 'Не удалось добавить элемент <i>%s</i> модуля <i>%s</i>.');
define ('KERNEL_PROG_PLUGINS_EDIT_ERROR', 'Не удалось отредактировать элемент <i>%s</i> модуля <i>%s</i>.');
define ('KERNEL_PROG_PLUGINS_ADD_OK', 'Элемент <i>%s</i> модуля <i>%s</i> успешно добавлен.');
define ('KERNEL_PROG_PLUGINS_EDIT_OK', 'Элемент <i>%s</i> модуля <i>%s</i> успешно отредактирован.');

define ('KERNEL_PROG_PLUGIN_BASKET_ERROR_UPDATE_ID_USER', 'Ошибка! Не удалось перевести заказы зарегистрированному пользователю.');
define ('KERNEL_PROG_PLUGIN_MAKER_ERROR_ADD', 'Ошибка! Не удалось поместить заказ в корзину.');
define ('KERNEL_PROG_PLUGIN_MAKER_ERROR_RESERVING', 'Ошибка! Не удалось зарезервировать заказ.');

define ('SB_CAPTCHA_FONT_ERR', 'Не найден файл шрифта <i>%s</i>.');
define ('SB_CAPTCHA_CODE_ERR', 'Не удалось сгенерировать код CAPTCHA. Проверьте правильность диапазона в настройках системы.');
define ('SB_CAPTCHA_COLOR_ERR', 'Не удалось сгенерировать изображение CAPTCHA.');
define ('SB_CAPTCHA_IMG_URL_ERR', 'Не найден фоновый файл CAPTCHA <i>%s</i>.');
define ('SB_CAPTCHA_IMG_DIR_ERR', 'Не удалось открыть директорию с фоновыми файлами CAPTCHA <i>%s</i>.');
define ('SB_CAPTCHA_IMG_EMPTY_DIR_ERR', 'Не удалось найти фоновыми файлами CAPTCHA (.jpg, .gif) в директории <i>%s</i>.');
define ('SB_CAPTCHA_IMG_ERR', 'Не удалось открыть фоновый файл CAPTCHA <i>%s</i>.');
define ('SB_CAPTCHA_GD_ERR', 'Библиотека GD не подключена. Не удалось сгенерировать изображение CAPTCHA.');

define ('KERNEL_PROG_PL_FORUM_FORM_ERR_ADD_THEME', 'Ошибка! Не удалось добавить тему <i>%s</i>.');
define ('KERNEL_PROG_PL_FORUM_FORM_ERR_ADD_MSG', 'Ошибка! Не удалось добавить сообщение <i>%s</i>.');
define ('KERNEL_PROG_PL_FORUM_FORM_ADD_MSG', 'Сообщение <i>%s</i> успешно добавлено.');
define ('KERNEL_PROG_PL_FORUM_OUT_ERROR_PAGELIST_MESSAGE_DESIGN', 'Ошибка! Макет дизайна постраничного вывода для сообщений форума не найден.');

define ('KERNEL_PROG_PL_MAILLIST_H_PARSE_ACTIVE', 'Активна');
define ('KERNEL_PROG_PL_MAILLIST_H_PARSE_NOT_ACTIVE', 'Не активна');
define ('KERNEL_PROG_PL_MAILLIST_H_PARSE_NOT_ACTIVE_TO', 'Не активна до');

define ('KERNEL_PROG_PL_FAQ_FORM_ERR_ADD_QUESTION', 'Ошибка! Не удалось добавить вопрос <i>%s</i>.');
define ('KERNEL_PROG_PL_FAQ_FORM_ADD_QUESTION', 'Вопрос <i>%s</i> успешно добавлен.');

define ('KERNEL_PROG_PL_SR_FORM_ERR_ADD_MOVIE', 'Ошибка! Не удалось добавить ролик <i>%s</i>.');
define ('KERNEL_PROG_PL_SR_ERR_UPLOAD', 'Ошибка! %s');
define ('KERNEL_PROG_PL_SR_ERR_CURL', 'Ошибка! В модуле <b>Планировщик заданий</b> не удалось получить данные ролика. Работа с сервисом YouTube требует наличие библиотеки cURL.');


define ('KERNEL_PROG_PL_IMAGELIB_KER_ADD_OK', 'Элемент библиотеки изображений %s успешно добавлен.');
define ('KERNEL_PROG_PL_IMAGELIB_KER_EDIT_OK', 'Элемент библиотеки изображений %s успешно изменен.');
define ('KERNEL_PROG_PL_IMAGELIB_KER_ADD_SYSTEMLOG_ERROR','Ошибка! Не удалось добавить новый элемент библиотеки изображений (название - %s).');
define ('KERNEL_PROG_PL_IMAGELIB_KER_EDIT_SYSTEMLOG_ERROR','Ошибка! Не удалось изменить элемент библиотеки изображений (название - %s).');

define ('KERNEL_PROG_PL_IMAGELIB_KER_NEW_IMAGE_TITLE','Новое изображение ');
define ('KERNEL_PROG_PL_IMAGELIB_WATERMARK_BIG_ERROR', 'Ошибка при наложении водяного знака на большое изображение');
define ('KERNEL_PROG_PL_IMAGELIB_WATERMARK_MIDDLE_ERROR', 'Ошибка при наложении водяного знака на среднее изображение');
define ('KERNEL_PROG_PL_IMAGELIB_WATERMARK_SMALL_ERROR', 'Ошибка при наложении водяного знака на маленькое изображение');
define ('KERNEL_PROG_PL_IMAGELIB_WATERMARK_ERROR', 'Ошибка при наложении водяного знака на изображение');


define ('KERNEL_PROG_PL_CRON_START_MSG', 'Запуск задания <i>%s</i> в модуле Планировщик заданий.');
define ('KERNEL_PROG_PL_CRON_END_MSG', 'Задание <i>%s</i> в модуле Планировщик заданий успешно выполнено.');
define ('KERNEL_PROG_PL_CRON_RESTART_MSG', 'Автоматический перезапуск следующих заданий:<br/><i>%s</i>');

define ('KERNEL_PROG_PL_RSS_PROPS_ERROR', 'Ошибка! Не верно указаны свойства импорта для RSS-канала <i>%s</i>.');
define ('KERNEL_PROG_PL_RSS_CATS_ERROR', 'Ошибка! Разделы, в которые должен осуществляться импорт элементов RSS-канала <i>%s</i>, были удалены.');
define ('KERNEL_PROG_PL_RSS_READ_ERROR', 'Ошибка! Не удалось получить доступ к RSS-каналу <i>%s (URL - <a href="%s" target="_blank">%s</a>)</i>.');
define ('KERNEL_PROG_PL_RSS_FORMAT_ERROR', 'Ошибка! Неверный формат RSS-канала <i>%s (URL - <a href="%s" target="_blank">%s</a>)</i>.');
define ('KERNEL_PROG_PL_RSS_ITEM_ADD_ERROR', 'Ошибка! Не удалось добавить элемент <i>%s</i> RSS-канала <i>%s</i>.');
define ('KERNEL_PROG_PL_RSS_ITEM_ADD_OK', 'Импорт элемента <i>%s</i> RSS-канала <i>%s</i>.');
define ('KERNEL_PROG_PL_RSS_IMPORT_OK', 'Импорт всех элементов RSS-канала <i>%s</i> завершен успешно.');
define ('KERNEL_PROG_PL_RSS_IMPORT_ERROR', 'Возникли ошибки при импорте элементов RSS-канала <i>%s</i>.');

define ('KERNEL_PROG_PL_SYSTEMLOG_CRON_ERROR', 'Ошибка! Не удалось создать архив сообщений системного журнала <i>%s</i>.');
define ('KERNEL_PROG_PL_REDIRECTLOG_CRON_ERROR', 'Ошибка! Не удалось создать архив журнала отсутствующих страниц <i>%s</i>.');

define ('KERNEL_PROG_PL_PAGES_CRON_SUBJ_LETER', 'Внимание! На %s %s были внесены несанкционированные изменения');
define ('KERNEL_PROG_PL_PAGES_CRON_TEXT_LETER', '<div style="color:red; font-size:16px;">Внимание!</div><br />
<span style="font-size:14px;">В файловую структуру %s <span style="font-weight:bold;">%s</span> были внесены несанкционированные изменения. Возможно, сайт был поражен вирусом. Необходимо тщательно проверить сайт на наличие посторонних скриптов и перегенерировать страницы сайта из системы управления сайтом. Список измененных страниц: </span><br />%s');

define ('KERNEL_PROG_PL_PAGES_CRON_ON_SITE_LABEL', 'сайте');
define ('KERNEL_PROG_PL_PAGES_CRON_ON_SITES_LABEL', 'сайтах');
define ('KERNEL_PROG_PL_PAGES_CRON_SITE_LABEL', 'сайта');
define ('KERNEL_PROG_PL_PAGES_CRON_SITES_LABEL', 'сайтов');

define ('KERNEL_PROG_PL_NEWS_FORM_ERR_ADD', 'Ошибка! Не удалось добавить новость <i>%s</i>.');
define ('KERNEL_PROG_PL_NEWS_FORM_ADD', 'Новость <i>%s</i> успешно добавлена.');

define ('KERNEL_PROG_PL_NEWS_FORM_ERR_EDIT', 'Ошибка! Не удалось отредактировать новость <i>%s</i>.');
define ('KERNEL_PROG_PL_NEWS_FORM_EDIT', 'Новость <i>%s</i> успешно отредактирована.');

define ('KERNEL_PROG_PL_SITE_USERS_STATUS_REG', 'активный');
define ('KERNEL_PROG_PL_SITE_USERS_STATUS_MOD', 'ожидает модерации');
define ('KERNEL_PROG_PL_SITE_USERS_STATUS_EMAIL', 'активирует по e-mail');
define ('KERNEL_PROG_PL_SITE_USERS_STATUS_MOD_EMAIL', 'ожидает модерации и активирует по e-mail');
define ('KERNEL_PROG_PL_SITE_USERS_STATUS_BLOCK', 'заблокирован');
define ('KERNEL_PROG_PL_SITE_USERS_SEX_MALE', 'Мужской');
define ('KERNEL_PROG_PL_SITE_USERS_SEX_FEMALE', 'Женский');

define ('KERNEL_PROG_PL_SITE_USERS_MAILLIST_STATUS_ACTIVE', 'Активна');
define ('KERNEL_PROG_PL_SITE_USERS_MAILLIST_STATUS_NOT_ACTIVE', 'Не активна');
define ('KERNEL_PROG_PL_SITE_USERS_MAILLIST_STATUS_NOT_ACTIVE_FOR', 'Не активная до');

define ('XMLRPC_NO_PHP_EXT', 'Ошибка! Расширение PHP XMLRPC не подключено. Обратитесь к Вашему хостинг-провайдеру для решения данной проблемы.');
define ('XMLRPC_SERVER_ERROR', 'Ошибка! Не удалось создать XMLRPC-сервер.');
define ('XMLRPC_REGISTER_METHOD_ERROR', 'Ошибка! Не удалось зарегистрировать необходимые методы XMLRPC-сервера.');
define ('XMLRPC_OFF_ERROR', 'Ошибка! В настройках системы отключена возможность публикации новостей с помощью MS Word.');
define ('XMLRPC_LOGIN_ERROR', 'Ошибка! Указанное имя пользователя или пароль не верные.');
define ('XMLRPC_NEWS_BLOGNAME', 'Новостная лента на %s.');
define ('XMLRPC_NEWS_CATEGS_ERROR', 'Ошибка! Вам не доступен ни один раздел новостной ленты для публикации.');
define ('XMLRPC_NEWS_WAS_DELETED', 'Ошибка! Выбранная новость не найдена или была удалена другим пользователем.');
define ('XMLRPC_NEWS_EDIT_ERROR', 'Ошибка! У Вас не прав на редактирование выбранной новости.');
define ('XMLRPC_NEWS_TITLE_ERROR', 'Ошибка! Не указан заголовок новости.');
define ('XMLRPC_NEWS_EDIT_SYSTEM_LOG', 'Редактирование новости <i>%s</i>.');
define ('XMLRPC_NEWS_NO_CATEGS_ERROR', 'Ошибка! Не указаны разделы, в которых необходимо опубликовать новость.');
define ('XMLRPC_NEWS_CATEGS_RIGHTS_ERROR', 'Ошибка! У Вас нет прав доступа к выбранным разделам.');
define ('XMLRPC_NEWS_ADD_ERROR', 'Ошибка! Не удалось добавить новость %s.');
define ('XMLRPC_NEWS_ADD_SYSTEM_LOG', 'Добавление новости <i>%s</i>.');
define ('XMLRPC_MEDIA_RIGHTS_ERROR', 'Ошибка! У Вас нет прав на закачку файлов данного типа.');
define ('XMLRPC_MEDIA_MKDIR_ERROR', 'Ошибка! Не удалось создать директорию %s.');
define ('XMLRPC_MEDIA_FILE_ERROR', 'Ошибка! Не удалось сохранить файл %s. Проверьте права доступа к директории %s.');
define ('XMLRPC_NEWS_DELETE_RIGHTS_ERROR', 'Ошибка! У Вас не прав на удаление выбранной новости.');
define ('XMLRPC_NEWS_DELETE_SYSTEM_LOG', 'Удаление новости <i>%s</i>.');
define ('XMLRPC_NEWS_DELETE_ERROR', 'Ошибка! Не удалось удалить новость.');
define ('XMLRPC_LOGIN_BLOCKED_ERROR', 'Ошибка! Ваша учетная запись заблокирована администратором сайта.');
define ('XMLRPC_LOGIN_DOMAIN_ERROR', 'Ошибка! У Вас нет прав на администрирование домена %s.');
define ('XMLRPC_LOGIN_IP_ERROR', 'Ошибка! Вход в систему с Вашего IP-адреса запрещен.');
define ('XMLRPC_LOGIN_GROUP_ERROR', 'Ошибка! Группа пользователей, в которую входит Ваша учетная запись, заблокирована администратором сайта.');
define ('XMLRPC_NEWS_CATEGS_EDIT_SYSTEM_LOG', 'Редактирование раздела новостной ленты <i>%s</i>.');

define ('KERNEL_CHANGING_PASSWORD_NEED', 'Требуется смена пароля');
define ('KERNEL_CHANGING_PASSWORD_OLD_PASS_FIELD', 'Текущий пароль');
define ('KERNEL_CHANGING_PASSWORD_NEW_PASS_FIELD', 'Новый пароль');
define ('KERNEL_CHANGING_PASSWORD_NEW_PASS_CONFIRM_FIELD', 'Подтвердите новый пароль');
define ('KERNEL_CHANGING_PASSWORD_ERROR_SEND_FORM', 'Ошибка! Не все поля формы заполнены корректно.');
define ('KERNEL_CHANGING_PASSWORD_PASS_METER', 'Индикатор надежности пароля');
define ('KERNEL_CHANGING_PASSWORD_ERROR_OLD_PASS', 'Ошибка! Неверно указан текущий пароль.');
define ('KERNEL_CHANGING_PASSWORD_ERROR_OLD_NEW', 'Ошибка! Текущий пароль совпадает с новым.');
define ('KERNEL_CHANGING_PASSWORD_ERROR_LENGTH_PASS', 'Ошибка! Неверно указан пароль. Длина пароля должна быть не менее %s символов.');
define ('KERNEL_CHANGING_PASSWORD_ERROR_CONFIRM_PASS', 'Ошибка! Неверно указано подтверждение пароля.');
define ('KERNEL_CHANGING_PASSWORD_SYS_ERROR_CHANGED_PASS', 'Успешное изменение пароля для пользователя системы с логином <i>%s</i>.');
define ('KERNEL_CHANGING_PASSWORD_INFO_MSG', '<b>Внимание!</b> Срок действия Вашего пароля истек. В целях безопасности необходимо изменить пароль. Введите свой текущий и новый пароль ниже.');
define ('KERNEL_CHANGING_PASSWORD_FIRST_LOGIN_INFO_MSG', '<b>Внимание!</b> Это Ваш первый вход в систему. В целях соблюдения политики безопасности системы необходимо изменить пароль. Введите свой текущий и новый пароль ниже.');

define ('KERNEL_PROG_PL_SEARCH_SYSTEMLOG_INDEXING_START', 'Начало индексации домена <i>%s</i> модулем <i>Поиск</i>.');
define ('KERNEL_PROG_PL_SEARCH_SYSTEMLOG_INDEXING_END', 'Завершение индексации домена <i>%s</i> модулем <i>Поиск</i>. Обработано %s страниц(ы) за %s секунд.');

define ('KERNEL_PROG_PL_SITEMAP_SYSTEMLOG_INDEXING_START', 'Начало индексации домена <i>%s</i> модулем <i>Sitemap</i>.');
define ('KERNEL_PROG_PL_SITEMAP_SYSTEMLOG_INDEXING_END', 'Завершение индексации домена <i>%s</i> модулем <i>Sitemap</i>. Обработано %s страниц(ы) за %s секунд.');

define ('KERNEL_FORM_EDIT_SORT_USER_FIELDS_ASC', 'Сортировка по %s (по возрастанию)');
define ('KERNEL_FORM_EDIT_SORT_USER_FIELDS_DESC', 'Сортировка по %s (по убыванию)');

define('SOAP_SPRAV_TITLE', 'Справочники');
define('SOAP_NEWS_TITLE', 'Новостная лента');
define('SOAP_IMAGELIB_TITLE', 'Библиотека изображений');
define('SOAP_FAQ_TITLE', 'Вопрос-Ответ');
define('SOAP_POLLS_TITlE', 'Опросы');
define('SOAP_TEXTS_TITLE', 'Текстовые блоки');
define('SOAP_BANNERS_TITLE', 'Рекламные кампании');
define('SOAP_MAILLIST_TITLE', 'Листы рассылки');
define('SOAP_SERVICES_RUTUBE_TITLE', 'Библиотека видеороликов');
define('SOAP_SITE_USERS_TITLE', 'Пользователи сайта');
define('SOAP_USERS_TITLE', 'Пользователи системы');
define('SOAP_AT_EMPTY_ERROR', 'Ошибка! Вы не передали soap token.');
define('SOAP_AT_WRONG_ERROR', 'Ошибка! Вы передали неверный soap token.');
define('SOAP_AT_OLD_ERROR', 'Ошибка! Вы передали устаревший soap token.');
define('SOAP_XML_EMPTY_ERROR', 'Ошибка! Вы не передали XML код.');
define('SOAP_XML_WRONG_ERROR', 'Ошибка! Вы передали неверный xml код.');
define('SOAP_AT_USER_BLOCKED', 'Ошибка! Ваша учетная запись заблокирована администратором системы.');
define('SOAP_AT_WRONG_DOMAIN', 'Ошибка! У Вас нет прав на администрирование домена <i>%s</i>.');
define('SOAP_AT_IP_ERROR', 'Ошибка! Вход в систему с Вашего IP-адреса запрещен.');
define('SOAP_AT_IP_ERROR_MSG', 'Попытка входа пользователя <i>%s</i> с некорректного IP-адреса <i>%s</i>.');
define('SOAP_AT_CATEG_BLOCKED', 'Ошибка! Группа пользователей, в которую входит Ваша учетная запись, заблокирована администратором системы.');
define('SOAP_RIGTS_PLUGIN_ERROR', 'Ошибка! У Вас нет прав на редактирование модуля <i>%s</i>.');
define('SOAP_RIGTS_PLUGIN_KEY_ERROR', 'Ошибка! Данный модуль невходит в сборку вашей системы.');
define('SOAP_NO_LIB_ERROR', 'Ошибка! На сервере не установлена библиотека SOAP.');
define('SOAP_RIGTS_CATS_ERROR', 'Предупреждение! Невозможно добавить дочерний раздел <i>%s</i>, т.к. у Вас нет прав на редактирование родительского раздела <i>%s</i>, в модуле <i>%s</i>.');
define('SOAP_RIGTS_CATS_EDIT_ERROR', 'Предупреждение! Невозможно изменить раздел <i>%s</i>, модуля <i>%s</i>, т.к. у Вас нет прав на редактирование этого раздела.');
define('SOAP_NO_RIGHTS_FOR_PARENT_CAT', 'Предупреждение! Невозможно добавить раздел <i>%s</i> модуля <i>%s</i>, т.к. не добавлен родительский раздел.');
define('SOAP_NO_RIGHTS_FOR_PARENT_EL', 'Предупреждение! Невозможно добавить элемент <i>%s</i> для модуля <i>%s</i>, т.к. не добавлен родительский раздел.');
define('SOAP_EL_PARENT_CAT_ERROR', 'Предупреждение! Невозможно добавить/изменить элемент <i>%s</i> для модуля <i>%s</i>, т.к. не удалось определить родительский раздел.');
define('SOAP_RIGTS_EL_EDIT_ERROR', 'Предупреждение! Невозможно добавить/изменить элемент <i>%s</i> для модуля <i>%s</i>, т.к. у Вас нет прав на редактирование родительского раздела <i>%s</i>.');
define('SOAP_RIGTS_EL_REMOVE_ERROR', 'Предупреждение! Невозможно переместить элемент <i>%s</i>, т.к. у Вас нет прав на редактирование раздела <i>%s</i> модуля <i>%s</i>.');
define('SOAP_EDIT_ERROR', 'Предупреждение! Невозможно добавить/изменить элемент <i>%s</i>, в модуле <i>%s</i> (Возможно неверный xml).');
define('SOAP_EDIT_ERROR_CATS', 'Предупреждение! Невозможно добавить/изменить раздел <i>%s</i> в модуле <i>%s</i>.');
define('SOAP_EDIT_ERROR_CATS_NO_TITLE', 'Предупреждение! Невозможно добавить/изменить раздел в модуле <i>%s</i>, т.к. не задано название раздела');
define('SOAP_RIGTS_EL_CREATE_LINK_ERROR', 'Предупреждение! Невозможно создать ссылку на элемент <i>%s</i> в разделе <i>%s</i> модуля <i>%s</i>, т.к. у Вас нет прав на редактирование раздела <i>%s</i>.');
define('SOAP_EL_CREATE_LINK_ERROR', 'Предупреждение! Невозможно создать ссылку на элемент <i>%s</i> в разделе <i>%s</i> модуля <i>%s</i>.');
define('SOAP_RIGTS_FIELD_ERROR', 'Невозможно изменить поле <i>%s (%s)</i> в модуле <i>%s</i>, т.к. у Вас нет прав на редактирование этого поля.');
define('SOAP_EDIT_OK_CATS', 'Раздел <i>%s</i>, модуля <i>%s</i> успешно изменен.');
define('SOAP_DELETE_OK_CATS', 'Раздел <i>%s</i> успешно удален.');
define('SOAP_DELETE_ERROR_CATS_WRONG_ID', 'Предупреждение! Невозможно удалить раздел <i>%s</i> в модуле <i>%s</i>, т.к. не задан идентификатор.');
define('SOAP_DELETE_ERROR_CATS', 'Предупреждение! Невозможно удалить раздел <i>%s</i> модуля <i>%s</i>. Возможно раздел, или его подразделы содержат элементы.');
define('SOAP_DELETE_ERROR_ELEM', 'Предупреждение! Невозможно удалить элемент <i>%s</i> в модуле <i>%s</i>.');
define('SOAP_DELETE_ERROR_ELEM_WRONG_ID', 'Предупреждение! Невозможно удалить элемент <i>%s</i> в модуле <i>%s</i>, т.к. не задан идентификатор.');
define('SOAP_RIGTS_DEL_ELEMS_PLUGIN_ERROR', 'Предупреждение! У Вас нет прав на удаления элементов модуля <i>%s</i>.');
define('SOAP_RIGTS_DEL_CAT_PLUGIN_ERROR', 'Предупреждение! У Вас нет прав на удаления разделов модуля <i>%s</i>.');
define('SOAP_SPRAV_FIELD_ID_ERROR', 'Предупреждение! Невозможно изменить поле <i>%s (%s)</i> элемента <i>%s</i> в модуле <i>%s</i>, т.к. неверно задан внешний идентификатор справочника.');
define('SOAP_SPRAV_CATS_ID_ERROR', 'Предупреждение! Невозможно изменить поле <i>%s (%s)</i> раздела <i>%s</i> в модуле <i>%s</i>, т.к. неверно задан внешний идентификатор справочника.');
define('SOAP_FIELD_ELEM_WRONG_ERROR', 'Предупреждение! Неизвестное поле <i>%s</i> в элементе <i>%s</i> модуля <i>%s</i>.');
define('SOAP_FIELD_CAT_WRONG_ERROR', 'Предупреждение! Неизвестное поле <i>%s</i> в разделе <i>%s</i> модуля <i>%s</i>.');
define('SOAP_FIELD_CAT_WRONG_XML_PLUGIN', 'Предупреждение! Невозможно добавить разделы модуля <i>%s</i> в модуль <i>%s</i>.');
define('SOAP_IMAGE_UPLOAD_WATERMARK_ERROR', 'Предупреждение! Невозможно Наложить водяной знак на изображение в поле <i>%s</i>!');

define('SOAP_ADD_OK_CATS', 'Раздел <i>%s</i> для модуля <i>%s</i> успешно добавлен.');
define('SOAP_ADD_OK_EL', 'Элемент <i>%s</i> в модуле <i>%s</i> успешно добавлен/изменен.');
define('SOAP_DELETE_OK_EL', 'Элемент <i>%s</i> в модуле <i>%s</i> успешно удален.');
define('SOAP_DELETE_OK_CAT', 'Раздел <i>%s</i> в модуле <i>%s</i> успешно удален.');
define('SOAP_EL_CREATE_LINK_OK', 'Ссылка на элемент <i>%s</i> в разделе <i>%s</i> модуля <i>%s</i> успешно добавлена.');
define('SOAP_SYSTEM_MESSAGE_PREFIX', 'SOAP - ');

define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_RUTUBE_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода подробной карточки ролика. (<i>Меню разработчика - Макеты дизайна компонентов - Библиотека видеороликов - Вывод ролика</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_NEWS_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода подробной карточки новости. (<i>Меню разработчика - Макеты дизайна компонентов - Новостная лента - Вывод полного текста новости</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_IMAGE_LIB_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода подробной карточки изображения. (<i>Меню разработчика - Макеты дизайна компонентов - Библиотека изображений - Вывод выбранного изображения</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_FAQ_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода полного текста вопроса. (<i>Меню разработчика - Макеты дизайна компонентов - Вопрос-Ответ - Вывод полного текста вопроса</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_POLLS_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода опросов. (<i>Меню разработчика - Макеты дизайна компонентов - Опросы - Вывод опросов</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_PLUGIN_MAKER_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода элементов. (<i>Меню разработчика - Макеты дизайна компонентов - %s - Вывод выбранного элемента</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_SITE_USERS_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода списка пользователей сайта. (<i>Меню разработчика - Макеты дизайна компонентов - Пользователи сайта - Вывод списка пользователей</i>). Связанные элементы выводиться не будут.');

define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_RUTUBE_LIST_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода списка роликов. (<i>Меню разработчика - Макеты дизайна компонентов - Библиотека видеороликов - Вывод списка роликов</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_NEWS_LIST_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода списка новостей. (<i>Меню разработчика - Макеты дизайна компонентов - Новостная лента - Вывод новостной ленты</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_IMAGE_LIST_LIB_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода списка изображений. (<i>Меню разработчика - Макеты дизайна компонентов - Библиотека изображений - Вывод списка изображений</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_FAQ_LIST_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода списка вопросов. (<i>Меню разработчика - Макеты дизайна компонентов - Вопрос-Ответ - Вывод списка вопросов</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_NO_PLUGIN_MAKER_LIST_TEMP_MSG', '<b style="color:red;">Внимание:</b> Вы не создали ни одного макета дизайна для вывода элементов. (<i>Меню разработчика - Макеты дизайна компонентов - %s - Вывод списка элментов</i>). Связанные элементы выводиться не будут.');
define ('PLUGINS_DESIGN_TEMPS_NO_ENTRIES_IN_LINKED_ERROR', 'Ошибка! Не удалось получить элементы связанного модуля <i>%s</i>.');
define ('PLUGINS_DESIGN_TEMPS_SELECT_GET_ONLY_DATA', 'Значение поля');

define ('SB_NOPARSE_RESPONSE_SERVER', 'Не удалось распознать ответ сервера');

define ('KERNEL_COUNT_ELEMS_PER_PAGE', 'Элементов на странице');

define ('KERNEL_ASSOC', 'Сопоставить');

define ('PL_CRON_SYMBOLIC_CHECK_ERROR', 'Отсутствует корневой каталог модуля <i>%s</i>');

define ('SB_EXPORT_IMPORT_TEMPLATE_TITLE', 'Название шаблона');
?>