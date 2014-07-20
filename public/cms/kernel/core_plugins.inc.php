<?php

//	Меню пользователя
include_once(SB_CMS_PL_PATH.'/pl_pages/pl_pages.h.php'); // Управление страницами
include_once(SB_CMS_PL_PATH.'/pl_menu/pl_menu.h.php'); // Навигация по сайту
include_once(SB_CMS_PL_PATH.'/pl_texts/pl_texts.h.php'); // Управление текстами
include_once(SB_CMS_PL_PATH.'/pl_news/pl_news.h.php'); // Управление новостями
include_once(SB_CMS_PL_PATH.'/pl_imagelib/pl_imagelib.h.php'); // Библиотека изображений
include_once(SB_CMS_PL_PATH.'/pl_services_rutube/pl_services_rutube.h.php'); // Библиотека видеороликов
include_once(SB_CMS_PL_PATH.'/pl_banners/pl_banners.h.php'); // Рекламная компания
include_once(SB_CMS_PL_PATH.'/pl_rss/pl_rss.h.php'); // RSS-импорт
include_once(SB_CMS_PL_PATH.'/pl_faq/pl_faq.h.php'); // Вопрос-Ответ
include_once(SB_CMS_PL_PATH.'/pl_forum/pl_forum.h.php'); // Форум
include_once(SB_CMS_PL_PATH.'/pl_maillist/pl_maillist.h.php'); // Листы рассылки
include_once(SB_CMS_PL_PATH.'/pl_polls/pl_polls.h.php'); // Опросы
include_once(SB_CMS_PL_PATH.'/pl_tester/pl_tester.h.php'); // Тестирование
include_once(SB_CMS_PL_PATH.'/pl_comments/pl_comments.h.php'); // Обсуждения
include_once(SB_CMS_PL_PATH.'/pl_payment/pl_payment.h.php'); // Авто оплаты
include_once(SB_CMS_PL_PATH.'/pl_messages/pl_messages.h.php'); // Личные сообщения
include_once(SB_CMS_PL_PATH.'/pl_user_settings/pl_user_settings.h.php'); // Настройки интерфейса

//	Меню разработчкиа
include_once(SB_CMS_PL_PATH.'/pl_basket/pl_basket.h.php'); // Корзина
include_once(SB_CMS_PL_PATH.'/pl_pager_temps/pl_pager_temps.h.php'); // Макеты дизайна постраничного вывода
include_once(SB_CMS_PL_PATH.'/pl_voting/pl_voting.h.php'); // Голосования
include_once(SB_CMS_PL_PATH.'/pl_clouds/pl_clouds.h.php'); // Макеты дизайна облака тегов
include_once(SB_CMS_PL_PATH.'/pl_calendar/pl_calendar.h.php'); // Макеты дизайна календаря
include_once(SB_CMS_PL_PATH.'/pl_services_cb/pl_services_cb.h.php'); // Курсы ЦБ
include_once(SB_CMS_PL_PATH.'/pl_robots/pl_robots.h.php'); // Редактирование robots.txt
include_once(SB_CMS_PL_PATH.'/pl_domains_manager/pl_domains_manager.h.php'); // Управление доменами
include_once(SB_CMS_PL_PATH.'/pl_templates/pl_templates.h.php'); // Макеты дизайна страниц
include_once(SB_CMS_PL_PATH.'/pl_plugin_data/pl_plugin_data.h.php'); // Макеты данных модулей
include_once(SB_CMS_PL_PATH.'/pl_plugin_maker/pl_plugin_maker.h.php'); // Конструктор модулей
include_once(SB_CMS_PL_PATH.'/pl_sprav/pl_sprav.h.php'); // Справочники
include_once(SB_CMS_PL_PATH.'/pl_search/pl_search.h.php'); // Поисковая система
include_once(SB_CMS_PL_PATH.'/pl_sitemap/pl_sitemap.h.php'); // Работа с файлом sitemap.xml
// Added SCheryachukin
include_once(SB_CMS_PL_PATH.'/pl_social_login/pl_social_login.h.php'); //Авторизация через соц. сети
include_once(SB_CMS_PL_PATH.'/pl_external_script/pl_external_script.h.php'); //Вызов скрипта со стороннего сайта
include_once(SB_CMS_PL_PATH.'/pl_search_history/pl_search_history.h.php'); //История поисковых запросов
include_once(SB_CMS_PL_PATH.'/pl_code/pl_code.h.php'); //Подключение внешних функций

//	Меню администратора
include_once(SB_CMS_PL_PATH.'/pl_users/pl_users.h.php'); // Пользователи системы
include_once(SB_CMS_PL_PATH.'/pl_site_users/pl_site_users.h.php'); // Пользователи сайта
include_once(SB_CMS_PL_PATH.'/pl_filelist/pl_filelist.h.php'); // Работа с файлами
include_once(SB_CMS_PL_PATH.'/pl_settings/pl_settings.h.php'); // Настройки системы
include_once(SB_CMS_PL_PATH.'/pl_systemlog/pl_systemlog.h.php'); // Системный журнал
include_once(SB_CMS_PL_PATH.'/pl_profiling/pl_profiling.h.php'); // Профилирование
include_once(SB_CMS_PL_PATH.'/pl_redirect/pl_redirect.h.php'); // Перенаправления
include_once(SB_CMS_PL_PATH.'/pl_update/pl_update.h.php'); // Обновление системы
include_once(SB_CMS_PL_PATH.'/pl_cron/pl_cron.h.php'); // Модуль планирования
include_once(SB_CMS_PL_PATH.'/pl_cache/pl_cache.h.php'); // Настройки кэширования
include_once(SB_CMS_PL_PATH.'/pl_dumper/pl_dumper.h.php'); // Восстановление системы
include_once(SB_CMS_PL_PATH.'/pl_workflow/pl_workflow.h.php'); // Цепочки публикаций
include_once(SB_CMS_PL_PATH.'/pl_about/pl_about.h.php'); // О системе

//	Техническая поддержка
include_once(SB_CMS_PL_PATH.'/pl_know/pl_know.h.php');	//	База знаний
?>