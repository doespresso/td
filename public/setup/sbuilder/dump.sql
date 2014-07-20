-- phpMyAdmin SQL Dump
-- version 3.5.5
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Окт 14 2013 г., 15:29
-- Версия сервера: 5.5.31-0+wheezy1
-- Версия PHP: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `sb_distr`
--

-- --------------------------------------------------------

--
-- Структура таблицы `sb_404_log`
--

CREATE TABLE IF NOT EXISTS `sb_404_log` (
  `l_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `l_url` varchar(255) NOT NULL COMMENT 'URL страницы',
  `l_domain` varchar(100) NOT NULL COMMENT 'Домен',
  `l_count` int(11) NOT NULL COMMENT 'Кол-во запросов',
  `l_date` int(11) NOT NULL COMMENT 'Время последнего запроса',
  `l_ip` varchar(30) NOT NULL COMMENT 'IP',
  PRIMARY KEY (`l_id`),
  KEY `l_date` (`l_date`),
  KEY `l_url` (`l_url`),
  KEY `l_domain` (`l_domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Журнал 404-ых ошибок' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_banners`
--

CREATE TABLE IF NOT EXISTS `sb_banners` (
  `sb_id` int(11) NOT NULL AUTO_INCREMENT,
  `sb_name` varchar(255) NOT NULL COMMENT 'Наименование баннера',
  `sb_link` varchar(255) NOT NULL COMMENT 'Ссылка, на которую ведет баннер',
  `sb_code` text NOT NULL COMMENT 'Код для текстового баннера',
  `sb_upload_name` varchar(255) NOT NULL COMMENT 'Путь к графическому баннеру',
  `sb_count_show` int(11) NOT NULL DEFAULT '-1' COMMENT 'Кол-во показов',
  `sb_priority` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Уровень приоритета',
  `sb_date_from` int(11) DEFAULT NULL COMMENT 'Дата, начиная с которой баннер будет показываться',
  `sb_date_to` int(11) DEFAULT NULL COMMENT 'Дата, до которой баннер будет показываться',
  `sb_plan_dates` text NOT NULL COMMENT 'Данные планирования показов баннера',
  `sb_active` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `sb_statistics` tinyint(2) NOT NULL COMMENT 'Вести статистику',
  `sb_restricted_cats` text COMMENT 'Идентификаторы разделов страниц',
  `sb_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор баннера',
  PRIMARY KEY (`sb_id`),
  KEY `sb_count_show` (`sb_count_show`),
  KEY `sb_priority` (`sb_priority`),
  KEY `sb_date_from` (`sb_date_from`),
  KEY `sb_date_to` (`sb_date_to`),
  KEY `sb_active` (`sb_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Данные баннеров' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_banners_restricted`
--

CREATE TABLE IF NOT EXISTS `sb_banners_restricted` (
  `sbr_bid` int(11) NOT NULL COMMENT 'Идентификатор баннера',
  `sbr_url` varchar(255) DEFAULT NULL COMMENT 'URL страницы',
  KEY `sbr_bid` (`sbr_bid`),
  KEY `sbr_url` (`sbr_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Запрещенные страницы для показа баннеров';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_banners_statistics`
--

CREATE TABLE IF NOT EXISTS `sb_banners_statistics` (
  `sb_bid` int(11) NOT NULL COMMENT 'Идентификатор баннера',
  `sb_count_clicks` int(11) NOT NULL DEFAULT '0' COMMENT 'Кол-во кликов',
  `sb_count_views` int(11) NOT NULL DEFAULT '0' COMMENT 'Кол-во показов',
  `sb_day` int(10) unsigned NOT NULL COMMENT 'День',
  `sb_month` int(10) unsigned NOT NULL COMMENT 'Месяц',
  `sb_year` int(10) unsigned NOT NULL COMMENT 'Год',
  KEY `sb_count_clicks` (`sb_count_clicks`),
  KEY `sb_count_views` (`sb_count_views`),
  KEY `sb_bid` (`sb_bid`),
  KEY `sb_year` (`sb_year`,`sb_month`,`sb_day`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=FIXED COMMENT='Статистика баннеров';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_banners_temps`
--

CREATE TABLE IF NOT EXISTS `sb_banners_temps` (
  `sbt_id` int(11) NOT NULL AUTO_INCREMENT,
  `sbt_title` varchar(255) NOT NULL,
  `sbt_element` text NOT NULL,
  `sbt_active` int(1) NOT NULL DEFAULT '0',
  `sbt_fields_temps` text COMMENT 'Макеты пользовательских полей',
  `sbt_checked` varchar(100) DEFAULT NULL,
  `sbt_lang` varchar(5) NOT NULL COMMENT 'Язык для даты',
  PRIMARY KEY (`sbt_id`),
  KEY `sbt_active` (`sbt_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты вывода баннеров' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_banners_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_banners_temps_list` (
  `sbdl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sbdl_title` varchar(100) NOT NULL COMMENT 'Название макета',
  `sbdl_checked` varchar(100) NOT NULL COMMENT 'Выводить елементы, для которых установлены флажки',
  `sbdl_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Количество выводимых баннеров',
  `sbdl_no_banners` longtext NOT NULL COMMENT 'Сообщение "Баннеров нет"',
  `sbdl_top` longtext COMMENT 'Макет верха вывода',
  `sbdl_elem_temps` int(10) unsigned NOT NULL COMMENT 'Идентификатор макета вывода элемента',
  `sbdl_bottom` longtext COMMENT 'Макет низа вывода',
  `sbdl_element` longtext COMMENT 'Макет вывода элемента',
  PRIMARY KEY (`sbdl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты вывода списка баннеров' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_basket`
--

CREATE TABLE IF NOT EXISTS `sb_basket` (
  `b_id_user` int(11) DEFAULT NULL COMMENT 'Идентификатор пользователя',
  `b_id_mod` int(11) NOT NULL COMMENT 'Идетификатор модуля',
  `b_id_el` int(11) NOT NULL COMMENT 'Идентификатор выбранного элемента',
  `b_count_el` int(11) NOT NULL COMMENT 'Количество элементов',
  `b_date` int(11) NOT NULL COMMENT 'Дата поступления',
  `b_reserved` tinyint(1) NOT NULL COMMENT 'Зарезервирован товар или нет',
  `b_prop` varchar(255) NOT NULL COMMENT 'Свойства',
  `b_hash` varchar(50) DEFAULT NULL COMMENT 'Хеш пользователя. Берем из кук.',
  `b_domain` varchar(100) NOT NULL DEFAULT 'all' COMMENT 'Домен',
  `b_discount` int(11) NOT NULL DEFAULT '0' COMMENT 'Размер скидки для одного экземпляра товара',
  KEY `b_date` (`b_id_user`,`b_date`),
  KEY `b_id_user` (`b_id_mod`,`b_id_el`,`b_id_user`,`b_hash`,`b_prop`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Корзина заказов';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_cache`
--

CREATE TABLE IF NOT EXISTS `sb_cache` (
  `c_ident` int(10) unsigned NOT NULL COMMENT 'Идентификатор блока кэша',
  `c_crc` int(10) unsigned NOT NULL COMMENT 'Контрольная сумма блока кэша',
  `c_time` int(10) unsigned NOT NULL COMMENT 'Время создания блока кэша',
  `c_el_id` int(11) unsigned DEFAULT NULL COMMENT 'Идентификатор элемента',
  `c_content` mediumblob NOT NULL COMMENT 'Содержимое кэша',
  `c_last_modified` int(10) unsigned NOT NULL COMMENT 'Время последнего изменения блока кэша',
  `c_domain` varchar(100) NOT NULL COMMENT 'Домен',
  `c_plugin_ident` varchar(100) NOT NULL COMMENT 'Идентификатор модуля',
  UNIQUE KEY `c_ident` (`c_ident`,`c_plugin_ident`,`c_domain`),
  KEY `c_time` (`c_time`),
  KEY `c_plugin_ident` (`c_plugin_ident`),
  KEY `c_last_modified` (`c_last_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Кэш сайта';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_cache_settings`
--

CREATE TABLE IF NOT EXISTS `sb_cache_settings` (
  `ident` varchar(50) NOT NULL COMMENT 'Идентификатор модуля',
  `time` smallint(5) unsigned NOT NULL COMMENT 'Время в минутах валидности кэша',
  `domain` varchar(100) NOT NULL COMMENT 'Домен',
  PRIMARY KEY (`ident`,`domain`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Настройки кэширования';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_calendar_temps`
--

CREATE TABLE IF NOT EXISTS `sb_calendar_temps` (
  `ct_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ct_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ct_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `ct_top` text NOT NULL COMMENT 'Верх вывода',
  `ct_num` text NOT NULL COMMENT 'Число',
  `ct_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `ct_delim` text NOT NULL COMMENT 'Разделитель строк',
  `ct_bottom` text NOT NULL COMMENT 'Низ вывода',
  `ct_fields` longtext NOT NULL COMMENT 'Макеты дизайна полей ',
  PRIMARY KEY (`ct_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты дизайна календаря' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_catchanges`
--

CREATE TABLE IF NOT EXISTS `sb_catchanges` (
  `el_id` int(11) NOT NULL COMMENT 'Идентификатор элемента',
  `cat_ident` varchar(50) NOT NULL COMMENT 'Идентификатор модуля',
  `change_user_id` int(11) NOT NULL COMMENT 'Идентификатор пользователя, последним изменившего элемент',
  `change_date` int(11) NOT NULL COMMENT 'Время последнего изменения элемента',
  `action` set('add','edit','cut') NOT NULL DEFAULT 'add' COMMENT 'Действие пользователя',
  KEY `link_id` (`el_id`,`cat_ident`,`change_date`),
  KEY `change_date` (`change_user_id`),
  KEY `action` (`action`,`change_user_id`,`cat_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='История изменений элемента';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_categs`
--

CREATE TABLE IF NOT EXISTS `sb_categs` (
  `cat_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор раздела',
  `cat_ident` varchar(50) NOT NULL COMMENT 'Идентификатор модуля',
  `cat_title` varchar(255) NOT NULL COMMENT 'Название раздела',
  `cat_left` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Левое значение узла в дереве разделов',
  `cat_right` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Правое значение узла в дереве разделов',
  `cat_level` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT 'Уровень раздела',
  `cat_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор раздела',
  `cat_rubrik` tinyint(4) NOT NULL COMMENT 'Выводить раздел в рубрикаторе',
  `cat_closed` tinyint(4) NOT NULL COMMENT 'Закрытый раздел',
  `cat_rights` varchar(255) NOT NULL COMMENT 'Группы пользователей системы, имеющие доступ к разделу',
  `cat_fields` longtext COMMENT 'Значения пользовательских полей раздела (сериализованный массив)',
  `cat_url` varchar(255) DEFAULT NULL COMMENT 'Псевдостатический адрес',
  PRIMARY KEY (`cat_id`),
  KEY `cat_left` (`cat_left`),
  KEY `cat_right` (`cat_right`),
  KEY `cat_level` (`cat_level`),
  KEY `cat_rubrik` (`cat_rubrik`),
  KEY `cat_closed` (`cat_closed`),
  KEY `cat_ext_id` (`cat_ext_id`),
  KEY `cat_id` (`cat_ident`,`cat_left`,`cat_right`,`cat_url`,`cat_id`),
  KEY `cat_id_rubrik` (`cat_id`,`cat_rubrik`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Разделы модулей' AUTO_INCREMENT=12 ;

--
-- Дамп данных таблицы `sb_categs`
--

INSERT INTO `sb_categs` (`cat_id`, `cat_ident`, `cat_title`, `cat_left`, `cat_right`, `cat_level`, `cat_ext_id`, `cat_rubrik`, `cat_closed`, `cat_rights`, `cat_fields`, `cat_url`) VALUES
(1, 'pl_users', 'Группы пользователей', 0, 3, 0, NULL, 1, 0, '', NULL, NULL),
(2, 'pl_users', 'Администраторы', 1, 2, 1, NULL, 1, 1, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `sb_categs_temps_full`
--

CREATE TABLE IF NOT EXISTS `sb_categs_temps_full` (
  `ctf_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ctf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ctf_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ctf_temp` longtext NOT NULL COMMENT 'Макет дизайна вывода раздела',
  `ctf_categs_temps` longtext NOT NULL COMMENT 'Пользовательские поля',
  `ctf_checked` varchar(100) NOT NULL COMMENT 'Выводить разделы, для которых помечены флажки',
  PRIMARY KEY (`ctf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна разделов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_categs_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_categs_temps_list` (
  `ctl_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ctl_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ctl_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ctl_levels` longtext NOT NULL COMMENT 'Макеты дизайна уровней разделов',
  `ctl_categs_temps` longtext NOT NULL COMMENT 'Пользовательские поля',
  `ctl_checked` varchar(100) NOT NULL COMMENT 'Выводить разделы, для которых помечены флажки',
  `ctl_perpage` int(11) unsigned NOT NULL COMMENT 'Кол-во разделов на странице',
  `ctl_pagelist_id` int(11) unsigned NOT NULL COMMENT 'Идентификатор макета дизайна постраничного вывода',
  PRIMARY KEY (`ctl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна вывода разделов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_catlinks`
--

CREATE TABLE IF NOT EXISTS `sb_catlinks` (
  `link_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор связи',
  `link_cat_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор раздела',
  `link_el_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор элемента',
  `link_src_cat_id` int(11) unsigned DEFAULT '0' COMMENT 'Идентификатор раздела, в котором находится оригинальный элемент (в случае ссылки)',
  PRIMARY KEY (`link_id`),
  KEY `link_el_id` (`link_el_id`),
  KEY `link_src_cat_id` (`link_src_cat_id`),
  KEY `link_cat_id` (`link_cat_id`,`link_el_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='Связь элементов с разделами' AUTO_INCREMENT=12 ;

--
-- Дамп данных таблицы `sb_catlinks`
--

INSERT INTO `sb_catlinks` (`link_id`, `link_cat_id`, `link_el_id`, `link_src_cat_id`) VALUES
(1, 2, 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `sb_catrights`
--

CREATE TABLE IF NOT EXISTS `sb_catrights` (
  `cat_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор раздела',
  `group_ids` varchar(255) DEFAULT NULL COMMENT 'Идентификаторы групп пользователей',
  `right_ident` varchar(100) NOT NULL COMMENT 'Идентификатор права доступа',
  PRIMARY KEY (`cat_id`,`right_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Права доступа к разделам';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_clouds_links`
--

CREATE TABLE IF NOT EXISTS `sb_clouds_links` (
  `cl_ident` varchar(100) NOT NULL COMMENT 'Идентификатор модуля',
  `cl_el_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор элемента',
  `cl_tag_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор тега',
  `cl_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cl_ident`,`cl_el_id`,`cl_tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Связи элементов с тегами';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_clouds_tags`
--

CREATE TABLE IF NOT EXISTS `sb_clouds_tags` (
  `ct_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор тега',
  `ct_tag` varchar(150) NOT NULL COMMENT 'Значение тега',
  PRIMARY KEY (`ct_id`,`ct_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Теги облака тегов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_clouds_temps`
--

CREATE TABLE IF NOT EXISTS `sb_clouds_temps` (
  `ct_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ct_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ct_perpage` int(10) unsigned NOT NULL COMMENT 'Кол-во тегов на странице',
  `ct_pagelist_id` int(10) unsigned NOT NULL COMMENT 'Макет дизайна постраничного вывода',
  `ct_count` int(10) unsigned NOT NULL COMMENT 'Кол-во тегов в строке',
  `ct_top` text NOT NULL COMMENT 'Верх вывода',
  `ct_element` text NOT NULL COMMENT 'Тег',
  `ct_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `ct_delim` text NOT NULL COMMENT 'Разделитель строк',
  `ct_bottom` text NOT NULL COMMENT 'Низ вывода',
  `ct_font_from` int(10) unsigned NOT NULL COMMENT 'Шрифт "от"',
  `ct_font_to` int(10) unsigned NOT NULL COMMENT 'Шрифт "до"',
  `ct_size_from` int(10) unsigned NOT NULL COMMENT 'Размер тега "от"',
  `ct_size_to` int(10) unsigned NOT NULL COMMENT 'Размер тега "до"',
  `ct_strip_words` text NOT NULL COMMENT 'Вырезаемые слова',
  `ct_color` varchar(7) NOT NULL COMMENT 'Цвет тега',
  `ct_color_percent` int(10) unsigned NOT NULL COMMENT 'Максимальный процент прозрачности тега',
  PRIMARY KEY (`ct_id`),
  KEY `ct_pagelist_id` (`ct_pagelist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты дизайна облака тегов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_comments`
--

CREATE TABLE IF NOT EXISTS `sb_comments` (
  `c_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_plugin` varchar(255) NOT NULL COMMENT 'Идентификатор модуля',
  `c_el_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор элемента',
  `c_author` varchar(255) DEFAULT NULL COMMENT 'Автор комментария',
  `c_email` varchar(255) DEFAULT NULL COMMENT 'E-Mail автора комментария',
  `c_subj` varchar(255) DEFAULT NULL COMMENT 'Тема комментария',
  `c_text` text COMMENT 'Текст комментария',
  `c_date` int(11) NOT NULL COMMENT 'Дата добавления комментария',
  `c_file` varchar(255) DEFAULT NULL COMMENT 'Ссылка на прикрепленный файл',
  `c_user_id` int(11) DEFAULT NULL COMMENT 'ID - пользователя, добавившего комментарий',
  `c_ip` varchar(255) NOT NULL COMMENT 'IP - пользователя, добавившего комментрий',
  `c_file_name` varchar(255) DEFAULT NULL COMMENT 'Имя прикрепленного файла',
  `c_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Показывать или не показывать комментарий при выводе',
  `c_left` int(11) unsigned NOT NULL COMMENT 'Левое значение узла в дереве комментариев',
  `c_right` int(11) unsigned NOT NULL COMMENT 'Правое значение узла в дереве комментариев',
  `c_level` int(11) unsigned NOT NULL COMMENT 'Уровень комментария',
  PRIMARY KEY (`c_id`),
  KEY `c_user_id` (`c_user_id`),
  KEY `c_el_id` (`c_el_id`,`c_plugin`,`c_date`,`c_show`),
  KEY `c_right` (`c_right`),
  KEY `c_left` (`c_left`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Комментарии' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_comments_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_comments_temps_list` (
  `ctl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ctl_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ctl_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ctl_perpage` int(11) DEFAULT NULL COMMENT 'Кол-во комментариев на странице',
  `ctl_pagelist_id` int(11) DEFAULT NULL COMMENT 'ID - макета дизайна постраничного вывода',
  `ctl_user_data_id` int(10) unsigned NOT NULL COMMENT 'Макет дизайна данных пользователя',
  `ctl_top` text COMMENT 'Вверх вывода',
  `ctl_element` text COMMENT 'Вывод комментария',
  `ctl_bottom` text NOT NULL COMMENT 'Низ вывода',
  `ctl_fields_temps` longtext COMMENT 'Шаблоны полей комментария',
  `ctl_messages` longtext COMMENT 'Шаблоны сообщений',
  `ctl_form` text NOT NULL COMMENT 'Форма добавления комментариев',
  PRIMARY KEY (`ctl_id`),
  KEY `ctl_pagelist_id` (`ctl_pagelist_id`),
  KEY `ctl_user_data_id` (`ctl_user_data_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна комментариев' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_counts`
--

CREATE TABLE IF NOT EXISTS `sb_counts` (
  `cs_id` int(11) NOT NULL AUTO_INCREMENT,
  `cs_domain` varchar(65) NOT NULL COMMENT 'Домен',
  `cs_service` varchar(65) NOT NULL COMMENT 'Сервис предоставляющий счетчик',
  `cs_code` text NOT NULL COMMENT 'Код счетчика',
  PRIMARY KEY (`cs_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Коды счетчиков Google Analytics и Яндекс.Метрика' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_cron`
--

CREATE TABLE IF NOT EXISTS `sb_cron` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор задания',
  `sc_name` varchar(255) NOT NULL COMMENT 'Наименование задания',
  `sc_plugin_ident` varchar(255) NOT NULL COMMENT 'Идентификатор модуля',
  `sc_date` int(11) DEFAULT NULL COMMENT 'Дата последнего запуска',
  `sc_active` tinyint(2) NOT NULL COMMENT 'Активно событие или нет',
  `sc_manual` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Ручной запуск задания',
  `sc_inprogress` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Задание выполняется',
  `sc_revoke` int(10) NOT NULL DEFAULT '1440' COMMENT 'Время в минутах, через которое задание будет переинициализировано',
  `sc_time_table` text NOT NULL COMMENT 'Расписание',
  `sc_start_interval` int(10) NOT NULL COMMENT 'Интервал запуска задания в минутах',
  `sc_func_name` varchar(255) NOT NULL COMMENT 'Название запускаемой функции',
  `sc_file_path` varchar(255) NOT NULL COMMENT 'Путь к запускаемому файлу',
  PRIMARY KEY (`sc_id`),
  KEY `sc_active` (`sc_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Задачи Планировщика заданий' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_editor`
--

CREATE TABLE IF NOT EXISTS `sb_editor` (
  `e_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `e_text` longtext NOT NULL,
  `e_u_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`e_id`),
  KEY `e_u_id` (`e_u_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Темповая таблица редактора' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_editor_styles`
--

CREATE TABLE IF NOT EXISTS `sb_editor_styles` (
  `se_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор стиля',
  `se_name` varchar(255) NOT NULL COMMENT 'Название стиля',
  `se_tag` varchar(35) NOT NULL COMMENT 'HTML-тег',
  `se_sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Номер сортировки',
  `se_attrs` longtext NOT NULL COMMENT 'Атрибуты и стили',
  PRIMARY KEY (`se_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Стили визуального редактора' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_editor_templates`
--

CREATE TABLE IF NOT EXISTS `sb_editor_templates` (
  `te_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор шаблона',
  `te_name` varchar(255) NOT NULL COMMENT 'Название шаблона',
  `te_code` text NOT NULL COMMENT 'HTML код шаблона',
  `te_desc` text NOT NULL COMMENT 'Описание шаблона',
  `te_img` varchar(255) NOT NULL COMMENT 'Изображение-иконка (полный путь) шаблона',
  `te_sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Номер сортировки',
  PRIMARY KEY (`te_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Шаблоны визуального редактора' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_elems`
--

CREATE TABLE IF NOT EXISTS `sb_elems` (
  `e_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор связи',
  `e_tag` varchar(50) NOT NULL COMMENT 'Тег макета дизайна, с которым связан компонент',
  `e_ident` varchar(50) NOT NULL COMMENT 'Идентификатор компонента',
  `e_link` set('page','temp') NOT NULL COMMENT 'Связь со страницей (page) или макетом дизайна (temp)',
  `e_temp_id` int(10) NOT NULL COMMENT 'Идентификатор макета дизайна компонента',
  `e_el_id` int(10) NOT NULL COMMENT 'Идентификатор элемента',
  `e_p_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор страницы или макета дизайна',
  `e_params` text NOT NULL COMMENT 'Параметры компонента',
  `e_search` set('text','links','all','none') NOT NULL DEFAULT 'none' COMMENT 'Параметры поисковой системы для тега',
  `e_edit` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Редактируемый блок или нет',
  `e_yandex` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Индексируется тег яндексом или нет',
  PRIMARY KEY (`e_id`),
  KEY `e_p_id` (`e_p_id`),
  KEY `e_edit` (`e_edit`),
  KEY `e_yandex` (`e_yandex`),
  KEY `e_el_id` (`e_el_id`),
  KEY `e_temp_id` (`e_temp_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Связь элементов со страницами' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_export`
--

CREATE TABLE IF NOT EXISTS `sb_export` (
  `se_user_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя системы',
  `se_pl_ident` varchar(255) NOT NULL COMMENT 'Идентификатор модуля',
  `se_data` text COMMENT 'Параметры последнего экспорта (XML)',
  UNIQUE KEY `se_user_id` (`se_user_id`,`se_pl_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Параметры экспорта модулей';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_export_import_templates`
--

CREATE TABLE IF NOT EXISTS `sb_export_import_templates` (
  `seit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seit_plugin_ident` varchar(50) NOT NULL COMMENT 'Идентификатор плагина',
  `seit_title` varchar(255) NOT NULL COMMENT 'Название шаблона',
  `seit_export` text COMMENT 'Шаблон экспорта',
  `seit_import` text COMMENT 'Шаблон импорта',
  PRIMARY KEY (`seit_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Таблица шаблонов экспорта/импорта модулей' AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_external_script`
--

CREATE TABLE IF NOT EXISTS `sb_external_script` (
  `es_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `es_url` varchar(255) NOT NULL COMMENT 'URL скрипта',
  `es_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Время последнего вызова',
  `es_handler` text COMMENT 'Обработчик результата',
  PRIMARY KEY (`es_id`),
  UNIQUE KEY `es_url` (`es_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Модуль вызова внешних скриптов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_faq`
--

CREATE TABLE IF NOT EXISTS `sb_faq` (
  `f_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор вопроса',
  `f_author` varchar(255) NOT NULL COMMENT 'Автор вопроса',
  `f_email` varchar(100) NOT NULL COMMENT 'E-mail автора',
  `f_phone` varchar(100) NOT NULL COMMENT 'Контактный телефон автора',
  `f_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата создания вопроса',
  `f_question` text NOT NULL COMMENT 'Текст вопроса',
  `f_answer` text NOT NULL COMMENT 'Текст ответа',
  `f_sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Индекс сортировки',
  `f_notify` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Уведомление о полученном ответе',
  `f_show` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `f_user_id` int(11) DEFAULT NULL COMMENT 'Идентификатор пользователя сайта, задавшего вопрос',
  `f_url` varchar(255) DEFAULT NULL COMMENT 'Псевдостатический адрес',
  `f_pub_start` int(11) unsigned DEFAULT NULL COMMENT 'Дата начала публикации вопроса',
  `f_pub_end` int(11) unsigned DEFAULT NULL COMMENT 'Дата окончания публикации вопроса',
  `f_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор вопроса',
  PRIMARY KEY (`f_id`),
  KEY `f_date` (`f_date`),
  KEY `f_sort` (`f_sort`),
  KEY `f_user_id` (`f_user_id`),
  KEY `f_show` (`f_show`,`f_pub_start`,`f_pub_end`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=88 ROW_FORMAT=DYNAMIC COMMENT='Данные вопросов и ответов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_faq_temps_form`
--

CREATE TABLE IF NOT EXISTS `sb_faq_temps_form` (
  `sftf_id` int(11) NOT NULL AUTO_INCREMENT,
  `sftf_title` varchar(255) NOT NULL COMMENT 'Наименование макета дизайна',
  `sftf_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `sftf_form` text NOT NULL COMMENT 'Макет дизайна вывода формы',
  `sftf_fields_temps` text NOT NULL COMMENT 'Макеты дизайна полей формы(сериализованный массив)',
  `sftf_messages` text COMMENT 'Системные сообщения',
  `sftf_categs_temps` text COMMENT 'Макеты дизайна пользовательских полей разделов',
  PRIMARY KEY (`sftf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна форм добавления' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_faq_temps_full`
--

CREATE TABLE IF NOT EXISTS `sb_faq_temps_full` (
  `ftf_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ftf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ftf_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `ftf_fullelement` text NOT NULL COMMENT 'Полный текст вопроса',
  `ftf_fields_temps` longtext NOT NULL COMMENT 'Массив полей вопросов',
  `ftf_categs_temps` longtext NOT NULL COMMENT 'Массив полей разделов',
  `ftf_checked` varchar(100) NOT NULL COMMENT 'Вывод тех вопросов, для которых установлены флажки',
  `ftf_user_data_id` int(10) NOT NULL COMMENT 'ID макет вывода данных пользователя',
  `ftf_tags_list_id` int(10) NOT NULL,
  `ftf_votes_id` int(10) NOT NULL COMMENT 'ID макетов голосований',
  `ftf_comments_id` int(10) NOT NULL COMMENT 'ID макетов комментариев',
  PRIMARY KEY (`ftf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна вопросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_faq_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_faq_temps_list` (
  `fdl_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `fdl_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `fdl_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `fdl_checked` varchar(100) NOT NULL COMMENT 'Вывод тех вопросов, для которых установлены флажки',
  `fdl_count` int(11) NOT NULL COMMENT 'Кол-во вопросов в строке',
  `fdl_top` text NOT NULL COMMENT 'Верх вывода общий',
  `fdl_categ_top` text NOT NULL COMMENT 'Верх вывода (раздел)',
  `fdl_element` text NOT NULL COMMENT 'Элемент списка вопросов',
  `fdl_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `fdl_delim` text NOT NULL COMMENT 'Разделитель строк',
  `fdl_categ_bottom` text NOT NULL COMMENT 'Низ вывода (раздел)',
  `fdl_bottom` text NOT NULL COMMENT 'Низ вывода общий',
  `fdl_perpage` int(10) NOT NULL COMMENT 'Кол-во вопросов на странице',
  `fdl_fields_temps` longtext NOT NULL COMMENT 'Массив полей вопросов',
  `fdl_categs_temps` longtext NOT NULL COMMENT 'Массив полей разделов',
  `fdl_pagelist_id` int(10) NOT NULL COMMENT 'Идентификатор макета дизайна постраничного вывода',
  `fdl_no_questions` text NOT NULL COMMENT 'Сообщение "нет вопросов"',
  `fdl_user_data_id` int(10) NOT NULL COMMENT 'ID макета вывода пользовательских данных',
  `fdl_tags_list_id` int(10) NOT NULL,
  `fdl_votes_id` int(10) NOT NULL COMMENT 'ID макетов голосований',
  `fdl_comments_id` int(10) NOT NULL COMMENT 'ID макетов комментариев',
  PRIMARY KEY (`fdl_id`),
  KEY `fdl_count` (`fdl_count`),
  KEY `fdl_pagelist_id` (`fdl_pagelist_id`),
  KEY `fdl_perpage` (`fdl_perpage`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна вопросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_folders_rights`
--

CREATE TABLE IF NOT EXISTS `sb_folders_rights` (
  `f_domain` varchar(50) NOT NULL COMMENT 'Домен',
  `f_path` varchar(255) NOT NULL COMMENT 'Путь',
  `f_ids` varchar(100) NOT NULL COMMENT 'Идентификаторы пользователей и групп',
  UNIQUE KEY `f_path` (`f_domain`,`f_path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum`
--

CREATE TABLE IF NOT EXISTS `sb_forum` (
  `f_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `f_text` text COMMENT 'Текст комментария',
  `f_date` int(11) NOT NULL COMMENT 'Дата добавления комментария',
  `f_file` text COMMENT 'Путь к прикрепленному файлу',
  `f_user_id` int(11) DEFAULT NULL COMMENT 'Идентификатор пользователя сайта, добавившего сообщение',
  `f_ip` varchar(255) NOT NULL COMMENT 'IP - пользователя добавившего комментрий',
  `f_file_name` text NOT NULL COMMENT 'Имя прикрепленного файла',
  `f_show` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `f_author` varchar(255) DEFAULT NULL COMMENT 'Автор сообщения',
  `f_email` varchar(255) DEFAULT NULL COMMENT 'e-mail автора сообщения',
  `f_glued` tinyint(1) NOT NULL COMMENT 'Приклеенное сообщение',
  PRIMARY KEY (`f_id`),
  KEY `f_user_id` (`f_user_id`),
  KEY `f_date` (`f_date`),
  KEY `f_show` (`f_show`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Модуль Форум' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_form_msg`
--

CREATE TABLE IF NOT EXISTS `sb_forum_form_msg` (
  `sffm_id` int(10) NOT NULL AUTO_INCREMENT,
  `sffm_lang` varchar(5) NOT NULL DEFAULT '' COMMENT 'Язык для даты',
  `sffm_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Наименование макета дизаайна',
  `sffm_text` text NOT NULL COMMENT 'Текст сообщения',
  `sffm_fields_temps` text NOT NULL COMMENT 'Макеты дизайна полей формы',
  `sffm_categs_temps` text COMMENT 'Макеты пользовательских поле разделов',
  `sffm_messages` text COMMENT 'Сообщения формы',
  PRIMARY KEY (`sffm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты формы добав-я сообщений' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_form_theme`
--

CREATE TABLE IF NOT EXISTS `sb_forum_form_theme` (
  `sftf_id` int(10) NOT NULL AUTO_INCREMENT,
  `sftf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `sftf_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `sftf_text` text NOT NULL COMMENT 'Макет формы добавления',
  `sftf_categs_temps` text NOT NULL COMMENT 'Макеты пользовательских полей',
  `sftf_messages` text NOT NULL COMMENT 'Сообщения формы',
  `sftf_fields_temps` text NOT NULL COMMENT 'Макеты полей элементов',
  PRIMARY KEY (`sftf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты формы добавления темы' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_maillist`
--

CREATE TABLE IF NOT EXISTS `sb_forum_maillist` (
  `sfm_id` int(11) NOT NULL AUTO_INCREMENT,
  `sfm_theme_id` int(10) NOT NULL COMMENT 'Идентификатор темы на кот-ую подписался пользователь',
  `sfm_user_id` int(10) DEFAULT NULL COMMENT 'ID пользователя. Если зарегистрированный',
  `sfm_email` varchar(255) NOT NULL COMMENT 'Email пользователя',
  PRIMARY KEY (`sfm_id`),
  UNIQUE KEY `sfm_theme_id` (`sfm_theme_id`,`sfm_email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Список подписчиков на темы форум' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_temps_categs`
--

CREATE TABLE IF NOT EXISTS `sb_forum_temps_categs` (
  `ftc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ftc_name` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ftc_categs_temps` text COMMENT 'Шаблоны разделов',
  `ftc_sub_categs_temps` text COMMENT 'Шаблоны подразделов',
  `ftc_pager_id` int(11) NOT NULL COMMENT 'ID - макета дизайна постраничного вывода',
  `ftc_perpage` int(11) NOT NULL COMMENT 'Кол-во разделов на странице',
  `ftc_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `ftc_checked` varchar(100) NOT NULL COMMENT 'Пользовательские флажки',
  `ftc_user_categs_temps` text NOT NULL COMMENT 'Пользовательские поля',
  `ftc_subjects_id` int(11) NOT NULL COMMENT 'ID-макета дизайна вывода тем',
  `ftc_user_subcategs_temps` text NOT NULL COMMENT 'Пользовательские поля подразделов',
  PRIMARY KEY (`ftc_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Вывод разделов и подразделов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_temps_messages`
--

CREATE TABLE IF NOT EXISTS `sb_forum_temps_messages` (
  `ftm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ftm_name` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ftm_templates` text NOT NULL COMMENT 'Шаблоны сообщений',
  PRIMARY KEY (`ftm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Шаблоны сообщений и уведомлений' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_temps_path`
--

CREATE TABLE IF NOT EXISTS `sb_forum_temps_path` (
  `sftp_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `sftp_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `sftp_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `sftp_checked` varchar(100) NOT NULL COMMENT 'Выводить пункты, для которых помечены флажки',
  `sftp_top` text NOT NULL COMMENT 'Верх вывода',
  `sftp_item` text NOT NULL COMMENT 'Вывод пункта',
  `sftp_last_item` text NOT NULL COMMENT 'Вывод последнего пункта',
  `sftp_bottom` text NOT NULL COMMENT 'Низ вывода',
  `sftp_fields_temps` longtext NOT NULL COMMENT 'Пользовательские поля',
  PRIMARY KEY (`sftp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты дизайна пути по форуму' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_temps_subjects`
--

CREATE TABLE IF NOT EXISTS `sb_forum_temps_subjects` (
  `fts_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fts_name` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `fts_checked` varchar(100) NOT NULL,
  `fts_messages_temps` text NOT NULL COMMENT 'Макеты дизайна сообщений',
  `fts_categs_temps` text NOT NULL COMMENT 'Макеты дизайна тем',
  `fts_user_fields_temps` text NOT NULL COMMENT 'Пользовательские поля сообщений',
  `fts_user_categs_temps` text NOT NULL COMMENT 'Пользовательские поля тем',
  `fts_perpage` int(11) DEFAULT NULL COMMENT 'Кол-во тем на странице',
  `fts_messages_id` int(11) DEFAULT NULL COMMENT 'ID макета дизайна сообщений и уведомлений',
  `fts_pagelist_id` int(11) DEFAULT NULL COMMENT 'ID макета дизайна постраничного вывода для тем',
  `fts_lang` varchar(5) NOT NULL,
  `fts_user_data_mess_id` int(11) DEFAULT NULL COMMENT 'ID макета дизайна вывода данных пользователя для сообщений',
  `fts_user_data_themes_id` int(11) DEFAULT NULL COMMENT 'ID макета дизайна вывода данных пользователя для тем',
  `fts_pagelist_mess_id` int(11) DEFAULT NULL COMMENT 'ID макета дизайна постраничного вывода для ссобщений',
  `fts_perpage_messages` int(10) NOT NULL COMMENT 'Кол-во сообщений на странице',
  `fts_theme_form_id` int(10) DEFAULT NULL COMMENT 'ID макета дизайна формы добавления тем',
  `fts_mess_checked` varchar(100) NOT NULL COMMENT 'Выводить сообщения для которых помечены флажки',
  PRIMARY KEY (`fts_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Вывод тем и сообщений' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_forum_viewing`
--

CREATE TABLE IF NOT EXISTS `sb_forum_viewing` (
  `fv_theme_id` int(10) NOT NULL COMMENT 'Идентификатор темы',
  `fv_user_id` int(10) NOT NULL COMMENT 'Идентификатор пользователя',
  `fv_date` int(11) DEFAULT NULL COMMENT 'Дата последнего простора темы',
  `fv_count_views` int(10) NOT NULL COMMENT 'Кол-во простмотров для данной темы данным пользователем',
  UNIQUE KEY `fv_theme_id` (`fv_theme_id`,`fv_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=FIXED COMMENT='Последние просмотры тем форумов';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_imagelib`
--

CREATE TABLE IF NOT EXISTS `sb_imagelib` (
  `im_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор изображения',
  `im_title` text NOT NULL COMMENT 'Название',
  `im_desc` text COMMENT 'Краткое описание',
  `im_url` varchar(255) DEFAULT NULL COMMENT 'Псевдостатический адрес',
  `im_big` varchar(255) NOT NULL COMMENT 'Путь к большому изображеннию',
  `im_big_from_server` tinyint(1) NOT NULL COMMENT 'Большое изображение загружено с сервера',
  `im_middle` varchar(255) NOT NULL COMMENT 'Путь к среднему изображеннию',
  `im_middle_from_server` tinyint(1) NOT NULL COMMENT 'Среднее изображение загружено с сервера',
  `im_small` varchar(255) NOT NULL COMMENT 'Путь к маленькому изображеннию',
  `im_small_from_server` tinyint(1) NOT NULL COMMENT 'Маленькое изображение загружено с сервера',
  `im_gal` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `im_user_id` int(11) DEFAULT NULL COMMENT 'Идентификатор пользователя сайта, добавившего изображение',
  `im_order_num` int(11) DEFAULT '0' COMMENT 'Порядковый номер',
  `im_date` int(11) NOT NULL COMMENT 'Дата добавления изображения',
  `im_active_date_start` int(11) DEFAULT NULL COMMENT '"Выводить в галерее в период" начальная дата',
  `im_active_date_end` int(11) DEFAULT NULL COMMENT '"Выводить в галерее в период" конечная дата',
  `im_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор изображения',
  PRIMARY KEY (`im_id`),
  KEY `im_active_date_end` (`im_active_date_end`),
  KEY `im_active_date_start` (`im_active_date_start`),
  KEY `im_date` (`im_date`),
  KEY `im_order_num` (`im_order_num`),
  KEY `im_user_id` (`im_user_id`),
  KEY `im_gal` (`im_gal`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Библиотека изображений' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_imagelib_temps_form`
--

CREATE TABLE IF NOT EXISTS `sb_imagelib_temps_form` (
  `itfrm_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID - макета дизайна',
  `itfrm_name` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `itfrm_lang` varchar(4) NOT NULL COMMENT 'Язык',
  `itfrm_form` text NOT NULL COMMENT 'Макет дизайна формы добавления',
  `itfrm_fields_temps` text NOT NULL COMMENT 'Макеты дизайна пользовательских полей',
  `itfrm_messages` text COMMENT 'Макет дизайна сообщений',
  `itfrm_categs_temps` text COMMENT 'Макеты дизайна пользовательских поле разделов',
  PRIMARY KEY (`itfrm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Форма добавления избражения' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_imagelib_temps_full`
--

CREATE TABLE IF NOT EXISTS `sb_imagelib_temps_full` (
  `itf_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID - макета дизайна',
  `itf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `itf_lang` varchar(5) DEFAULT NULL,
  `itf_element` text COMMENT 'Элемент макета дизайна',
  `itf_fields_temps` text COMMENT 'Шаблоны пользовательских полей библиотеки изображений',
  `itf_categs_temps` text COMMENT 'Шаблоны пользовательских полей разделов библиотеки изображений',
  `itf_checked` varchar(100) DEFAULT NULL COMMENT 'Флажки пользовательских полей',
  `itf_comments_id` int(11) DEFAULT NULL COMMENT 'ID-макета дизайна комментариев',
  `itf_voting_id` int(11) DEFAULT NULL COMMENT 'ID-макета дизайна голосований',
  `itf_user_data_id` int(10) DEFAULT NULL COMMENT 'ID макета вывода пользовательских данных',
  `itf_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`itf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Вывод большого изображения' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_imagelib_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_imagelib_temps_list` (
  `itl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Ид элемента',
  `itl_count_on_page` int(11) DEFAULT NULL COMMENT 'Кол-во изображений на странице',
  `itl_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `itl_lang` varchar(5) DEFAULT NULL COMMENT 'Язык',
  `itl_fields_temps` text,
  `itl_categs_temps` text,
  `itl_no_image_message` text COMMENT 'Сообщение нет изображений',
  `itl_count_on_line` int(11) DEFAULT NULL COMMENT 'Кол-во изображений в строке',
  `itl_top` text COMMENT 'Верх вывода (общий)',
  `itl_top_cat` text COMMENT 'Верх вывода раздел',
  `itl_image` text,
  `itl_empty` text,
  `itl_delim` text,
  `itl_bottom_cat` text,
  `itl_bottom` text,
  `itl_pagelist_id` int(11) DEFAULT NULL COMMENT 'ID-макета дизайна постраничного вывода',
  `itl_checked` varchar(100) DEFAULT NULL,
  `itl_date` text,
  `itl_votes_id` int(10) NOT NULL COMMENT 'ID макетов голосований',
  `itl_comments_id` int(11) NOT NULL COMMENT 'Макет дизайна комментариев',
  `itl_user_data_id` int(10) DEFAULT NULL COMMENT 'ID макета вывода пользовательских данных',
  `itl_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`itl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Вывод библиотеки изображений' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_maillist`
--

CREATE TABLE IF NOT EXISTS `sb_maillist` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор рассылки',
  `m_name` varchar(255) NOT NULL COMMENT 'Название рассылки',
  `m_conf_format` tinyint(1) NOT NULL COMMENT 'Формат сообщения',
  `m_conf_email` varchar(255) NOT NULL COMMENT 'E-mail отправителя',
  `m_conf_charset` varchar(255) NOT NULL COMMENT 'Кодировка письма',
  `m_conf_images_attach` tinyint(1) NOT NULL COMMENT 'Прикреплять картинки в виде вложений',
  `m_conf_signed` text NOT NULL COMMENT 'Подпись к письму (для каждого языка своя)',
  `m_mail_description` text NOT NULL COMMENT 'Описание рассылки',
  `m_site_users_fields` longtext NOT NULL COMMENT 'Поля пользователей системы',
  `m_site_users_custom_fields` longtext NOT NULL COMMENT 'Пользовательские поля пользователей сайта',
  `m_unsub_page` varchar(255) NOT NULL COMMENT 'Страница для отказа от рассылки',
  `m_news_temp_id` varchar(100) DEFAULT NULL COMMENT 'Идентификатор макета дизайна вывода ностной ленты',
  `m_mail_designs` longtext NOT NULL COMMENT 'Макеты дизайна тем и сообщений писем',
  `m_use_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Флаг. Использовать по умолчанию для рассылки новостей или нет',
  `m_active` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `m_pub_start` int(11) unsigned DEFAULT NULL COMMENT 'Дата начала публикации рассылки',
  `m_pub_end` int(11) unsigned DEFAULT NULL COMMENT 'Дата окончания публикации рассылки',
  `m_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор рассылки',
  PRIMARY KEY (`m_id`),
  KEY `m_active` (`m_active`,`m_pub_end`,`m_pub_start`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Модуль рассылки' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_maillist_temps`
--

CREATE TABLE IF NOT EXISTS `sb_maillist_temps` (
  `smt_id` int(11) NOT NULL AUTO_INCREMENT,
  `smt_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `smt_checked` varchar(100) NOT NULL COMMENT 'Вывод тех рассылок для которых  установлены флажки',
  `smt_top` text NOT NULL COMMENT 'Верх вывода',
  `smt_categ_top` text NOT NULL COMMENT 'Верх вывода (раздел)',
  `smt_elem` text NOT NULL COMMENT 'макет элемента',
  `smt_categ_bottom` text NOT NULL COMMENT 'Низ вывода (раздел)',
  `smt_bottom` text NOT NULL COMMENT 'Низ вывода (общий)',
  `smt_count` int(10) NOT NULL COMMENT 'Кол-во рассылок в строке',
  `smt_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `smt_delim` text NOT NULL COMMENT 'Разделитель строк',
  `smt_no_delivery` text NOT NULL COMMENT 'Сообщение Рассылок нет',
  `smt_fields_temps` text NOT NULL COMMENT 'Массив полей рассылок',
  `smt_categs_temps` text NOT NULL COMMENT 'Массив полей разделов',
  `smt_lang` varchar(5) NOT NULL COMMENT 'Язык',
  PRIMARY KEY (`smt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты вывода листов рассылки' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_menu`
--

CREATE TABLE IF NOT EXISTS `sb_menu` (
  `m_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор пункта меню',
  `m_title` varchar(255) NOT NULL COMMENT 'Название пункта меню',
  `m_text` text COMMENT 'Текст пункта мею',
  `m_url` varchar(255) DEFAULT NULL COMMENT 'Ссылка (href)',
  `m_alt` varchar(255) DEFAULT NULL COMMENT 'Подсказка (alt)',
  `m_target` varchar(10) DEFAULT NULL COMMENT 'Цель (target)',
  `m_type` set('item','delim','categs') NOT NULL DEFAULT 'item' COMMENT 'Тип пункта меню',
  `m_props` text COMMENT 'Свойства пункта меню',
  `m_show` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `m_left` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Левое значение узла в дереве',
  `m_right` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Правое значение узла в дереве',
  `m_level` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT 'Уровень пункта',
  `m_cat_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор раздела, которому принадлежит пункт',
  `m_status` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  PRIMARY KEY (`m_id`),
  KEY `mn_left` (`m_left`),
  KEY `mn_right` (`m_right`),
  KEY `mn_level` (`m_level`),
  KEY `mn_menu_id` (`m_cat_id`),
  KEY `m_url` (`m_url`),
  KEY `m_show` (`m_show`),
  KEY `m_type` (`m_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Навигация по сайту' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_menu_path_temps`
--

CREATE TABLE IF NOT EXISTS `sb_menu_path_temps` (
  `mpt_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `mpt_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `mpt_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `mpt_checked` varchar(100) NOT NULL COMMENT 'Выводить пункты, для которых помечены флажки',
  `mpt_top` text NOT NULL,
  `mpt_item` text NOT NULL COMMENT 'Вывод пукнт',
  `mpt_last_item` text NOT NULL COMMENT 'Вывод последнего пункта',
  `mpt_bottom` text NOT NULL,
  `mpt_fields_temps` longtext NOT NULL COMMENT 'Пользовательские поля',
  `mpt_plugin_ident` varchar(100) NOT NULL COMMENT 'Идентификатор модуля',
  PRIMARY KEY (`mpt_id`),
  KEY `mpt_plugin` (`mpt_plugin_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна пути по сайту' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_menu_temps`
--

CREATE TABLE IF NOT EXISTS `sb_menu_temps` (
  `mt_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `mt_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `mt_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `mt_levels` longtext NOT NULL COMMENT 'Макеты дизайна уровней меню',
  `mt_fields_temps` longtext NOT NULL COMMENT 'Пользовательские поля',
  `mt_checked` varchar(100) NOT NULL COMMENT 'Выводить пункты, для которых помечены флажки',
  PRIMARY KEY (`mt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна вывод меню' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_mobile`
--

CREATE TABLE IF NOT EXISTS `sb_mobile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) DEFAULT NULL COMMENT 'Хеш заголовков',
  `date` int(11) DEFAULT NULL COMMENT 'Дата добавления',
  `mobile` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'Мобильное устройство',
  `counter` int(11) NOT NULL DEFAULT '0' COMMENT 'Количество обращений',
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Данные мобильных устройств' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_news`
--

CREATE TABLE IF NOT EXISTS `sb_news` (
  `n_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор новости',
  `n_title` varchar(255) NOT NULL COMMENT 'Заголовок новости',
  `n_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата новости',
  `n_short` text COMMENT 'Анонс новости',
  `n_short_foto` varchar(255) DEFAULT NULL COMMENT 'Фото для анонса новости',
  `n_full` text COMMENT 'Полный текст новости',
  `n_full_foto` varchar(255) DEFAULT NULL COMMENT 'Фото для полного текста новости',
  `n_pub_start` int(11) unsigned DEFAULT NULL COMMENT 'Дата начала пбликации новости',
  `n_pub_end` int(11) unsigned DEFAULT NULL COMMENT 'Дата окончания публикации новости',
  `n_active` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `n_url` varchar(255) DEFAULT NULL COMMENT 'Псевдостатический адрес',
  `n_sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Порядковый номер',
  `n_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор новости',
  `n_ml_ids_sent` varchar(255) DEFAULT NULL COMMENT 'Идентификаторы рассылок по которым новость уже была отправлена',
  `n_ml_ids` varchar(255) DEFAULT NULL COMMENT 'Идентификатор листа рассылки',
  `n_user_id` int(11) DEFAULT NULL COMMENT 'Идентификатор пользователя добавившего новость',
  PRIMARY KEY (`n_id`),
  KEY `n_ext_id` (`n_ext_id`),
  KEY `n_url` (`n_url`),
  KEY `n_pub_start` (`n_date`,`n_sort`,`n_pub_start`,`n_pub_end`,`n_active`,`n_id`),
  KEY `n_maillist_id` (`n_ml_ids`),
  KEY `n_user_id` (`n_user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Новостная лента' AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_news_temps_form`
--

CREATE TABLE IF NOT EXISTS `sb_news_temps_form` (
  `sntf_id` int(11) NOT NULL AUTO_INCREMENT,
  `sntf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `sntf_lang` varchar(5) NOT NULL COMMENT 'Язык для даты',
  `sntf_form` text NOT NULL COMMENT 'Макет дизайна формы добавления',
  `sntf_fields_temps` text NOT NULL COMMENT 'Макет дизайна полей новости',
  `sntf_categs_temps` text COMMENT 'Макет дизайна полей разделов новостей',
  `sntf_messages` text COMMENT 'Макеты дизайна сообщений',
  PRIMARY KEY (`sntf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты формы добавления новости' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_news_temps_full`
--

CREATE TABLE IF NOT EXISTS `sb_news_temps_full` (
  `ntf_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор новости',
  `ntf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ntf_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `ntf_element` text NOT NULL COMMENT 'Макет дизайна полного текста новости',
  `ntf_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна пользовательских полей элементов',
  `ntf_categs_temps` longtext NOT NULL COMMENT 'Макеты дизайна пользовательских полей разделов',
  `ntf_checked` varchar(100) NOT NULL COMMENT 'Выводить новости, для которых помечены флажки',
  `ntf_votes_id` int(10) DEFAULT NULL COMMENT 'ID макетов голосования',
  `ntf_comments_id` int(10) DEFAULT NULL COMMENT 'ID макета комментариев',
  `ntf_user_data_id` int(10) NOT NULL COMMENT 'ID макета вывода пользовательских данных',
  `ntf_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`ntf_id`),
  KEY `ntf_votes_id` (`ntf_votes_id`),
  KEY `ntf_comments_id` (`ntf_comments_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна новостей' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_news_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_news_temps_list` (
  `ndl_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ndl_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ndl_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ndl_checked` varchar(100) NOT NULL COMMENT 'Выводить новости, для которых помечены флажки',
  `ndl_count` int(11) unsigned NOT NULL COMMENT 'Кол-во новостей в строке',
  `ndl_top` text NOT NULL COMMENT 'Верх вывода общий',
  `ndl_categ_top` text NOT NULL COMMENT 'Верх вывода раздела',
  `ndl_element` text NOT NULL COMMENT 'Новость',
  `ndl_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `ndl_delim` text NOT NULL COMMENT 'Разделитель строк',
  `ndl_categ_bottom` text NOT NULL COMMENT 'Низ вывода разделов',
  `ndl_bottom` text NOT NULL COMMENT 'Низ вывода общий',
  `ndl_pagelist_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор макета дизайна постраничного вывода',
  `ndl_perpage` int(10) unsigned NOT NULL COMMENT 'Кол-во новостей на странице',
  `ndl_no_news` text NOT NULL COMMENT 'Сообщение "новостей нет"',
  `ndl_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна пользовательских полей новости',
  `ndl_categs_temps` longtext NOT NULL COMMENT 'Макеты дизайна вывода пользовательских полей разделов',
  `ndl_votes_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна голосований',
  `ndl_comments_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна комментариев',
  `ndl_user_data_id` int(10) NOT NULL COMMENT 'ID макета вывода пользовательских данных',
  `ndl_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`ndl_id`),
  KEY `ndl_perpage` (`ndl_perpage`),
  KEY `ndl_pagelist_id` (`ndl_pagelist_id`),
  KEY `ndl_votes_id` (`ndl_votes_id`),
  KEY `ndl_comments_id` (`ndl_comments_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна новостей' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_pager_temps`
--

CREATE TABLE IF NOT EXISTS `sb_pager_temps` (
  `pt_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `pt_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `pt_perstage` int(11) unsigned NOT NULL COMMENT 'Кол-во номер страниц в выводе',
  `pt_begin` text NOT NULL COMMENT 'Ссылка "в начало"',
  `pt_next` text NOT NULL COMMENT 'Ссылка "следующая"',
  `pt_previous` text NOT NULL COMMENT 'Ссылка "предыдущая"',
  `pt_end` text NOT NULL COMMENT 'Ссылка "в конец"',
  `pt_number` text NOT NULL COMMENT 'Ссылка на номер страницы',
  `pt_sel_number` text NOT NULL COMMENT 'Номер текущей страницы',
  `pt_page_list` text NOT NULL COMMENT 'Вывод номеров страниц',
  `pt_delim` text COMMENT 'Разделитель номеров страниц',
  PRIMARY KEY (`pt_id`),
  KEY `pt_count` (`pt_perstage`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Постраничного вывод' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_pages`
--

CREATE TABLE IF NOT EXISTS `sb_pages` (
  `p_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор страницы',
  `p_name` varchar(255) NOT NULL COMMENT 'Название страницы',
  `p_filepath` varchar(255) NOT NULL COMMENT 'Имя файла страницы',
  `p_filename` varchar(255) NOT NULL COMMENT 'Путь к файлу страницы',
  `p_state` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `p_temp_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор макета дизайна страницы',
  `p_xslt` varchar(255) DEFAULT NULL COMMENT 'Путь к файлу с таблицей стилей XSLT',
  `p_title` text NOT NULL COMMENT 'Заголовок страницы (title)',
  `p_keywords` text NOT NULL COMMENT 'Ключевые слова страницы (keywords)',
  `p_description` text NOT NULL COMMENT 'Описание страницы (description)',
  `p_meta` text NOT NULL COMMENT 'Доп. мета-теги страницы',
  `p_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Главная страница раздела',
  `p_crc` int(10) unsigned DEFAULT NULL COMMENT 'Контрольная сумма файла страницы',
  `p_ctype` varchar(50) DEFAULT NULL COMMENT 'Content-type',
  `p_nocache` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Запретить кэширование страницы браузером и сервером',
  PRIMARY KEY (`p_id`),
  KEY `p_state` (`p_state`),
  KEY `p_temp_id` (`p_temp_id`),
  KEY `p_default` (`p_default`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Страницы сайта' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_payments`
--

CREATE TABLE IF NOT EXISTS `sb_payments` (
  `sp_id` int(11) NOT NULL AUTO_INCREMENT,
  `sp_title` varchar(255) NOT NULL COMMENT 'Заголовок счета',
  `sp_status` int(2) NOT NULL DEFAULT '0' COMMENT 'Статус платежа',
  `sp_summ` float NOT NULL DEFAULT '0' COMMENT 'Сумма платежа',
  `sp_attr1` text NOT NULL,
  `sp_attr2` text NOT NULL,
  `sp_attr3` text NOT NULL COMMENT 'Параметры платежа',
  `sp_name` varchar(255) NOT NULL COMMENT 'Имя плательщика',
  `sp_secname` varchar(255) NOT NULL COMMENT 'Отчество плательщика',
  `sp_surname` varchar(255) NOT NULL COMMENT 'Фамилия плательщика',
  `sp_text` varchar(255) NOT NULL COMMENT 'Описание платежа',
  `sp_comment` text NOT NULL COMMENT 'Комментарий к платежу',
  `sp_user_id` int(11) NOT NULL DEFAULT '0',
  `sp_date` int(11) NOT NULL DEFAULT '0' COMMENT 'Дата совершения платежа',
  `sp_active` int(1) NOT NULL DEFAULT '1' COMMENT 'Показыват/Не показыват на сайте',
  `sp_public` int(1) NOT NULL DEFAULT '1' COMMENT 'Анонимный плательщик',
  `sp_address` text NOT NULL COMMENT 'Адрес плательщика',
  `sp_email` varchar(100) NOT NULL COMMENT 'E-mail плательщика',
  `sp_phone` varchar(15) NOT NULL COMMENT 'Телефон плательщика',
  PRIMARY KEY (`sp_id`),
  KEY `sp_date` (`sp_date`),
  KEY `sp_user_id` (`sp_user_id`),
  KEY `sp_status` (`sp_status`),
  KEY `sp_public` (`sp_public`),
  KEY `sp_title` (`sp_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Данные платежей' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_payments_settings`
--

CREATE TABLE IF NOT EXISTS `sb_payments_settings` (
  `sps_id` int(11) NOT NULL AUTO_INCREMENT,
  `sps_cat_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID раздела',
  `sps_ident` varchar(255) NOT NULL COMMENT 'Идентификатор процессингового центра',
  `sps_name` varchar(255) NOT NULL COMMENT 'Наименование настройки',
  `sps_value` text NOT NULL COMMENT 'Значение настройки',
  PRIMARY KEY (`sps_id`),
  KEY `sps_cat_id` (`sps_cat_id`),
  KEY `sps_ident` (`sps_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Настройки процессинг-ых центров' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_payments_temps`
--

CREATE TABLE IF NOT EXISTS `sb_payments_temps` (
  `spt_id` int(11) NOT NULL AUTO_INCREMENT,
  `spt_title` varchar(255) NOT NULL COMMENT 'Наименование макета дизайна',
  `spt_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `spt_prerequest_main` text NOT NULL COMMENT 'Форма обработки платежа',
  `spt_confirm` text NOT NULL COMMENT 'Подтверждение введенной информации',
  `spt_payment_descr` text NOT NULL COMMENT 'Описание платежа',
  `spt_fields_temps` text NOT NULL COMMENT 'Массив макетов полей платежей',
  `spt_categs_temps` text NOT NULL COMMENT 'Массив макетов полей разделов',
  `spt_system_messages` longtext NOT NULL COMMENT 'Системные сообщения',
  PRIMARY KEY (`spt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна форм оплат' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_payments_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_payments_temps_list` (
  `sptl_id` int(11) NOT NULL AUTO_INCREMENT,
  `sptl_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Название макета дизайна',
  `sptl_list_header` text NOT NULL COMMENT 'Верх вывода транзакций',
  `sptl_categ_top` text NOT NULL COMMENT 'Верх вывода (раздел)',
  `sptl_list_main` text NOT NULL COMMENT 'Элемента списка транзакций',
  `sptl_categ_bottom` text NOT NULL COMMENT 'Низ вывода (раздел)',
  `sptl_list_footer` text NOT NULL COMMENT 'Низ списка транзакций',
  `sptl_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `sptl_delim` text NOT NULL COMMENT 'Разделитель строк',
  `sptl_pagelist_id` int(10) NOT NULL COMMENT 'ID макета дизайна постраничного вывода',
  `sptl_count` int(10) NOT NULL COMMENT 'Кол-во транзакций в строке',
  `sptl_perpage` int(10) NOT NULL COMMENT 'Кол-во элементов на странице',
  `sptl_lang` varchar(2) NOT NULL DEFAULT '' COMMENT 'Язык',
  `sptl_checked` varchar(100) NOT NULL DEFAULT '' COMMENT 'Вывод тех тразакций для которых помеченны флажки',
  `sptl_no_transaction` text NOT NULL COMMENT 'Сообщение нет транзакций',
  `sptl_fields_temps` longtext NOT NULL COMMENT 'Массив макетов полей платежей',
  `sptl_categs_temps` longtext NOT NULL COMMENT 'Массив макетов полей разделов',
  PRIMARY KEY (`sptl_id`),
  KEY `sptl_pagelist_id` (`sptl_pagelist_id`),
  KEY `sptl_count` (`sptl_count`),
  KEY `sptl_perpage` (`sptl_perpage`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна списка транзакци' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_plugins_data`
--

CREATE TABLE IF NOT EXISTS `sb_plugins_data` (
  `pd_plugin_ident` varchar(100) NOT NULL COMMENT 'Идентификатор модуля',
  `pd_fields` longtext NOT NULL COMMENT 'Поля элементов модуля (сериализованный массив)',
  `pd_categs` longtext NOT NULL COMMENT 'Поля разделов модуля (сериализованный массив)',
  `pd_increment` int(11) unsigned NOT NULL DEFAULT '1' COMMENT 'Идентификатор следующего поля',
  PRIMARY KEY (`pd_plugin_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Пользовательские поля модулей';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_plugins_maker`
--

CREATE TABLE IF NOT EXISTS `sb_plugins_maker` (
  `pm_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор модуля',
  `pm_title` varchar(50) NOT NULL COMMENT 'Название модуля',
  `pm_settings` text NOT NULL COMMENT 'Основные настройки модуля',
  `pm_categs_settings` text NOT NULL COMMENT 'Настройки разделов модуля',
  `pm_elems_settings` text NOT NULL COMMENT 'Настройки элементов модуля',
  `pm_allow_goods` int(1) NOT NULL DEFAULT '0' COMMENT 'Модуль доступен для выбора элементов в списке заказов',
  PRIMARY KEY (`pm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Конструктор модулей' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_plugins_temps_form`
--

CREATE TABLE IF NOT EXISTS `sb_plugins_temps_form` (
  `ptf_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ptf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ptf_messages` longtext COMMENT 'Макеты дизайна сообщений',
  `ptf_categs_temps` longtext COMMENT 'Макеты полей разделов',
  `ptf_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна полей формы',
  `ptf_form` longtext NOT NULL COMMENT 'Макет дизайна формы',
  `ptf_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ptf_user_data_id` int(10) DEFAULT NULL COMMENT 'ID макета вывода пользовательских данных',
  PRIMARY KEY (`ptf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты дизайна формы добавления ' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_plugins_temps_full`
--

CREATE TABLE IF NOT EXISTS `sb_plugins_temps_full` (
  `ptf_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ptf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ptf_lang` varchar(5) NOT NULL COMMENT 'Язык макета',
  `ptf_element` text NOT NULL COMMENT 'Макет дизайна элемента',
  `ptf_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна пользовательских полей элемента',
  `ptf_categs_temps` longtext NOT NULL COMMENT 'Макеты дизайна пользовательских полей раздела',
  `ptf_checked` varchar(100) NOT NULL COMMENT 'Выводить новости, для которых помечены флажки',
  `ptf_votes_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна голосований',
  `ptf_comments_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна комментариев',
  `ptf_user_data_id` int(10) NOT NULL COMMENT 'ID макета вывода пользовательских данных',
  `ptf_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`ptf_id`),
  KEY `ptf_votes_id` (`ptf_votes_id`),
  KEY `ptf_comments_id` (`ptf_comments_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты выбранного элемента' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_plugins_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_plugins_temps_list` (
  `ptl_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ptl_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ptl_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ptl_checked` varchar(100) NOT NULL COMMENT 'Выводить элементы, для которых помечены флажки',
  `ptl_count` int(11) unsigned NOT NULL COMMENT 'Кол-во элементов в строке',
  `ptl_top` text NOT NULL COMMENT 'Верх вывода общий',
  `ptl_categ_top` text NOT NULL COMMENT 'Верх вывода раздела',
  `ptl_element` text NOT NULL COMMENT 'Элемент',
  `ptl_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `ptl_delim` text NOT NULL COMMENT 'Разделитель строк',
  `ptl_categ_bottom` text NOT NULL COMMENT 'Низ вывода разделов',
  `ptl_bottom` text NOT NULL COMMENT 'Низ вывода общий',
  `ptl_pagelist_id` int(11) unsigned NOT NULL COMMENT 'Идентификатор макета дизайна постраничного вывода',
  `ptl_perpage` int(11) unsigned NOT NULL COMMENT 'Кол-во элементов на странице',
  `ptl_no_elems` text NOT NULL COMMENT 'Сообщение "элементов нет"',
  `ptl_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна пользовательских полей элемента',
  `ptl_categs_temps` longtext NOT NULL COMMENT 'Макеты дизайна вывода пользовательских полей разделов',
  `ptl_votes_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна голосований',
  `ptl_comments_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна комментариев',
  `ptl_user_data_id` int(10) NOT NULL COMMENT 'ID макета вывода пользовательских данных',
  `ptl_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`ptl_id`),
  KEY `ptl_votes_id` (`ptl_votes_id`),
  KEY `ptl_comments_id` (`ptl_comments_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты дизайна списка элементов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_polls`
--

CREATE TABLE IF NOT EXISTS `sb_polls` (
  `sp_id` int(11) NOT NULL AUTO_INCREMENT,
  `sp_url` varchar(255) DEFAULT NULL COMMENT 'ЧПУ',
  `sp_question` text NOT NULL COMMENT 'Вопрос опроса',
  `sp_active` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `sp_sort` int(10) NOT NULL COMMENT 'Индекс сортировки',
  `sp_pub_start` int(11) unsigned DEFAULT NULL COMMENT 'Дата начала публикации опроса',
  `sp_pub_end` int(11) unsigned DEFAULT NULL COMMENT 'Дата окончания публикации опроса',
  `sp_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор опроса',
  PRIMARY KEY (`sp_id`),
  KEY `sp_url` (`sp_url`),
  KEY `sp_pub_start` (`sp_active`,`sp_pub_start`,`sp_pub_end`),
  KEY `sp_sort` (`sp_sort`),
  KEY `sp_active` (`sp_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица вопросов для опросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_polls_options`
--

CREATE TABLE IF NOT EXISTS `sb_polls_options` (
  `spo_id` int(11) NOT NULL AUTO_INCREMENT,
  `spo_poll_id` int(11) NOT NULL COMMENT 'Идентификатор опроса',
  `spo_name` text NOT NULL COMMENT 'Название варианта ответа',
  `spo_type` varchar(50) NOT NULL COMMENT 'Тип варианта ответа',
  `spo_order` int(11) NOT NULL COMMENT 'Очередность вариантов ответа в выводе',
  PRIMARY KEY (`spo_id`),
  KEY `spo_poll_id` (`spo_poll_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица вариантов ответов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_polls_results`
--

CREATE TABLE IF NOT EXISTS `sb_polls_results` (
  `spr_id` int(11) NOT NULL AUTO_INCREMENT,
  `spr_option_id` int(11) NOT NULL COMMENT 'Идентификатор варианта ответа',
  `spr_date` int(11) NOT NULL COMMENT 'Дата голосования',
  `spr_ip` varchar(50) NOT NULL COMMENT 'IP-адресс проголосовавшего',
  `spr_text` varchar(255) NOT NULL COMMENT 'текст текстового варианта ответа',
  PRIMARY KEY (`spr_id`),
  KEY `spr_option_id` (`spr_option_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Результаты опросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_polls_temps`
--

CREATE TABLE IF NOT EXISTS `sb_polls_temps` (
  `spt_id` int(10) NOT NULL AUTO_INCREMENT,
  `spt_title` varchar(255) NOT NULL COMMENT 'Заголовок макета дизайна',
  `spt_lang` varchar(5) NOT NULL COMMENT 'Язык используется для даты',
  `spt_checked` varchar(100) NOT NULL COMMENT 'Помеченные флажки',
  `spt_count` int(10) NOT NULL COMMENT 'Кол-во опросов в строке',
  `spt_perpage` int(10) NOT NULL COMMENT 'Кол-во опросов на странице',
  `spt_delim` text NOT NULL COMMENT 'Разделитель строк',
  `spt_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `spt_top` text NOT NULL COMMENT 'Верх вывода (Общий)',
  `spt_cat_top` text NOT NULL COMMENT 'Верх вывода (раздел)',
  `spt_polls_top` text NOT NULL COMMENT 'Верх вывода голосования',
  `spt_element` text NOT NULL COMMENT 'Макет вывода элемента',
  `spt_polls_bottom` text NOT NULL COMMENT 'Низ вывода голосования',
  `spt_cat_bottom` text NOT NULL COMMENT 'Низ вывода (раздел)',
  `spt_bottom` text NOT NULL COMMENT 'Низ вывода (Общий)',
  `spt_pagelist_id` int(10) NOT NULL COMMENT 'ID  макета дизайна постраничного вывода',
  `spt_fields_temps` text NOT NULL COMMENT 'Макеты дизайна полей элемента',
  `spt_categs_temps` text NOT NULL COMMENT 'Макеты дизайна полей разделов',
  `spt_messages` text NOT NULL COMMENT 'Сообщения об ошибках',
  PRIMARY KEY (`spt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты вывода опросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_polls_temps_results`
--

CREATE TABLE IF NOT EXISTS `sb_polls_temps_results` (
  `sptr_id` int(11) NOT NULL AUTO_INCREMENT,
  `sptr_title` varchar(255) NOT NULL COMMENT 'Заголовок макета',
  `sptr_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `sptr_perpage` int(10) NOT NULL COMMENT 'Кол-во результатов на странице',
  `sptr_pagelist_id` int(10) NOT NULL COMMENT 'ID макета дизайна постраничного вывода',
  `sptr_count` int(10) NOT NULL COMMENT 'Кол-во результатов в строке',
  `sptr_checked` text NOT NULL COMMENT 'Помеченные флажки',
  `sptr_top` text NOT NULL COMMENT 'Верх вывода общий',
  `sptr_categs_top` text NOT NULL COMMENT 'Верх вывода раздел',
  `sptr_result_top` text NOT NULL COMMENT 'Верх вывода результата',
  `sptr_element` text NOT NULL COMMENT 'макет результата опроса',
  `sptr_result_bottom` text NOT NULL COMMENT 'Низ вывода результата',
  `sptr_categs_bottom` text NOT NULL COMMENT 'Низ вывода раздел',
  `sptr_bottom` text NOT NULL COMMENT 'Низ вывода общий',
  `sptr_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `sptr_delim` text NOT NULL COMMENT 'Разделитель строк',
  `sptr_fields_temps` text NOT NULL COMMENT 'Макеты дизайна полей результатов',
  `sptr_categs_temps` text NOT NULL COMMENT 'Макеты дизайна полей разделов',
  `sptr_no_results` text NOT NULL COMMENT 'Сообщение "Нет результатов"',
  PRIMARY KEY (`sptr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты вывода результатов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_profiler_plugins`
--

CREATE TABLE IF NOT EXISTS `sb_profiler_plugins` (
  `prp_id` int(10) NOT NULL AUTO_INCREMENT,
  `prp_ident` varchar(50) NOT NULL COMMENT 'Идентификатор компонента',
  `prp_time` float(10,6) NOT NULL COMMENT 'Время, затраченное на выполнение',
  `prp_sql_count` smallint(6) NOT NULL COMMENT 'Количество запросов за сеанс',
  `prp_uri` varchar(255) NOT NULL COMMENT 'Адрес страницы-инициатора',
  `prp_domain` varchar(50) NOT NULL COMMENT 'Домен',
  `prp_date` int(11) NOT NULL COMMENT 'Дата и время выполнения',
  `prp_hash` varchar(255) NOT NULL COMMENT 'Хэш для связи с профайлом SQL-запросов',
  PRIMARY KEY (`prp_id`),
  KEY `prp_ident` (`prp_ident`),
  KEY `prp_sql_count` (`prp_sql_count`),
  KEY `prp_uri` (`prp_uri`),
  KEY `prp_domain` (`prp_domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Данные профилирования модулей' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_profiler_sql`
--

CREATE TABLE IF NOT EXISTS `sb_profiler_sql` (
  `pr_id` int(10) NOT NULL AUTO_INCREMENT,
  `pr_date` int(15) NOT NULL DEFAULT '0' COMMENT 'Дата и время запроса',
  `pr_time` float(10,6) NOT NULL DEFAULT '0.000000' COMMENT 'Время выполнения',
  `pr_text_sql` text NOT NULL COMMENT 'SQL-запрос',
  `pr_tracker` text NOT NULL COMMENT 'Стек вызовов',
  `pr_plugin` varchar(50) NOT NULL COMMENT 'Плагин-инициатор',
  `pr_url` text NOT NULL COMMENT 'URL страницы, с которой пришел запрос',
  `pr_domain` varchar(255) NOT NULL COMMENT 'Домен',
  `pr_hash` varchar(255) NOT NULL COMMENT 'Хэш для связи с профайлингом модулей',
  PRIMARY KEY (`pr_id`),
  KEY `pr_plugin` (`pr_plugin`),
  KEY `pr_url` (`pr_url`(255)),
  KEY `pr_domain` (`pr_domain`),
  KEY `pr_hash` (`pr_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Данные профилирования SQL-запросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_redirect`
--

CREATE TABLE IF NOT EXISTS `sb_redirect` (
  `sr_id` int(10) NOT NULL AUTO_INCREMENT,
  `sr_old_url` text NOT NULL COMMENT 'Старый адрес (URL)',
  `sr_new_url` text NOT NULL COMMENT 'Новый адрес (URL)',
  `sr_redirect_type` enum('301','302') NOT NULL COMMENT 'Тип редиректа. 301 или 302',
  `sr_active` tinyint(1) NOT NULL COMMENT 'Активна переадресация или нет',
  `sr_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Кол-во переходов',
  PRIMARY KEY (`sr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица переадресаций' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_rss`
--

CREATE TABLE IF NOT EXISTS `sb_rss` (
  `r_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор канала',
  `r_title` varchar(255) NOT NULL COMMENT 'Название канала',
  `r_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Активен канал или нет',
  `r_url` varchar(255) NOT NULL COMMENT 'URL канала',
  `r_plugin_ident` varchar(100) NOT NULL COMMENT 'Идентификатор модуля',
  `r_settings` longtext NOT NULL COMMENT 'Настройки канала',
  `r_date` int(10) unsigned DEFAULT NULL COMMENT 'Дата последнего импорта',
  `r_error` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Была ошибка (1) или нет (0) при импорте',
  PRIMARY KEY (`r_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='RSS-каналы' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_search`
--

CREATE TABLE IF NOT EXISTS `sb_search` (
  `ss_id` int(10) NOT NULL AUTO_INCREMENT,
  `ss_date` int(11) DEFAULT NULL COMMENT 'Время индексации',
  `ss_domains` varchar(255) NOT NULL COMMENT 'Домены  для которых будут формироваться  базы',
  `ss_settings` text NOT NULL COMMENT 'Настройки поисковой системы',
  PRIMARY KEY (`ss_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица индексируемых доменов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_search_history`
--

CREATE TABLE IF NOT EXISTS `sb_search_history` (
  `sh_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sh_text` varchar(255) NOT NULL DEFAULT '' COMMENT 'Поисковый запрос',
  `sh_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Количество запросов',
  `sh_pages_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Количество найденных страниц',
  `sh_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата последнего запроса',
  `sh_search_page` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Индекс поисковой базы',
  PRIMARY KEY (`sh_id`),
  UNIQUE KEY `sh_text_sh_domain` (`sh_text`,`sh_search_page`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='История поисковых запросов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_search_robots`
--

CREATE TABLE IF NOT EXISTS `sb_search_robots` (
  `sr_id` int(10) NOT NULL AUTO_INCREMENT,
  `sr_robot` varchar(255) NOT NULL COMMENT 'наименование поиского робота',
  PRIMARY KEY (`sr_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=25 ROW_FORMAT=DYNAMIC COMMENT='База поисковых роботов' AUTO_INCREMENT=111 ;

--
-- Дамп данных таблицы `sb_search_robots`
--

INSERT INTO `sb_search_robots` (`sr_id`, `sr_robot`) VALUES
(1, 'StackRambler'),
(36, 'Araneo'),
(35, 'Arachnoidea'),
(4, 'Mediapartners-Google'),
(5, 'Adsbot-Google'),
(6, 'Googlebot'),
(7, 'Yandex'),
(8, 'YaDirectBot'),
(11, 'Bond, James Bond'),
(12, 'Aport'),
(13, 'WebAlta'),
(14, 'Mail'),
(15, 'UdiSearch'),
(16, 'Slurp'),
(17, 'Yahoo'),
(34, 'AnzwersCrawl'),
(33, 'Acoon'),
(21, 'msnbot'),
(22, 'WebCrawler'),
(23, 'ZyBorg'),
(24, 'Scooter'),
(25, 'Tarantula'),
(26, 'Trek17'),
(27, 'Lycos'),
(28, 'Cys'),
(29, 'Begun'),
(30, 'Fast'),
(31, 'Eurobot'),
(32, 'Cabot'),
(37, 'ArchitextSpider'),
(38, 'Atomz'),
(39, 'CMC'),
(40, 'ComputingSite'),
(41, 'Cruizer'),
(42, 'Datenbank'),
(43, 'DeepIndex'),
(44, 'Die Blinde Kuh'),
(45, 'DomainsDB.net'),
(46, 'Esther'),
(47, 'ExplorerSearch'),
(49, 'Fido'),
(50, 'FreeCrawl'),
(51, 'Gaisbot'),
(52, 'Gigabot'),
(53, 'Gulliver'),
(54, 'Gulper Web Bot'),
(55, 'Icorus'),
(56, 'InfoSeek'),
(57, 'Iron33'),
(58, 'IsraeliSearch'),
(59, 'JCrawler'),
(60, 'KIT Fireball'),
(61, 'KO Yappo'),
(62, 'Mercator'),
(63, 'Mewsoft Search Engine'),
(64, 'Motor'),
(65, 'MuscatFerret'),
(66, 'MwdSearch'),
(67, 'NEC MeshExplorer'),
(68, 'Nederland Zoek'),
(69, 'NetScoop'),
(70, 'Nutch'),
(71, 'Onet.pl'),
(72, 'Openbot'),
(73, 'Openfind data gatherer'),
(74, 'Orb Search'),
(75, 'RHCS'),
(77, 'Scrubby'),
(78, 'SearchTone'),
(79, 'Sidewinder'),
(81, 'SwissSearch'),
(85, 'UltraSeek'),
(86, 'VWbot'),
(87, 'Vagabondo'),
(88, 'Valkyrie'),
(89, 'Voyager'),
(91, 'WebQuest'),
(92, 'Wired Digital'),
(94, 'Zealbot'),
(96, 'aWapClient'),
(97, 'ah-ha.com'),
(98, 'appie'),
(99, 'ask jeeves'),
(100, 'bumblebee'),
(101, 'crawler3'),
(102, 'ia_archiver'),
(106, 'semanticdiscovery'),
(108, 'szukacz'),
(109, 'w3index'),
(110, 'whatuseek');

-- --------------------------------------------------------

--
-- Структура таблицы `sb_search_temps_form`
--

CREATE TABLE IF NOT EXISTS `sb_search_temps_form` (
  `sstf_id` int(11) NOT NULL AUTO_INCREMENT,
  `sstf_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `sstf_form` text NOT NULL COMMENT 'Макет формы поиска',
  PRIMARY KEY (`sstf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты вывода формы поиска' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_search_temps_results`
--

CREATE TABLE IF NOT EXISTS `sb_search_temps_results` (
  `sstr_id` int(10) NOT NULL AUTO_INCREMENT,
  `sstr_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `sstr_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `sstr_pagelist_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор макета дизайна постраничного вывода',
  `sstr_perpage` int(10) unsigned NOT NULL COMMENT 'Количество результатов на странице',
  `sstr_top` text NOT NULL COMMENT 'Верх вывода результатов поиска',
  `sstr_element` text NOT NULL COMMENT 'Макет вывода результата поиска',
  `sstr_bottom` text NOT NULL COMMENT 'Низ вывода результатов поиска',
  `sstr_no_results` text NOT NULL COMMENT 'Сообщение "Нет результатов"',
  `sstr_found_word` text NOT NULL COMMENT 'Сериализованный массив макетов начала и конца вывода найденной фразы',
  `sstr_cat_id` int(11) unsigned DEFAULT NULL COMMENT 'Идентификатор раздела в котором находиться макет дизайна',
  PRIMARY KEY (`sstr_id`),
  KEY `sstr_cat_id` (`sstr_cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты результатов поиска' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_search_words`
--

CREATE TABLE IF NOT EXISTS `sb_search_words` (
  `sw_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор слова',
  `sw_word` varchar(50) NOT NULL COMMENT 'Текст слова',
  PRIMARY KEY (`sw_id`),
  UNIQUE KEY `sw_word` (`sw_word`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица индексируемых слов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_services_cb_cache`
--

CREATE TABLE IF NOT EXISTS `sb_services_cb_cache` (
  `sscc_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор кэша',
  `sscc_ident` varchar(255) DEFAULT NULL COMMENT 'Идентификатор сервиса ЦБ',
  `sscc_date` varchar(255) DEFAULT NULL COMMENT 'Дата запроса',
  `sscc_params` text COMMENT 'Массив различных данных сервисов',
  PRIMARY KEY (`sscc_id`),
  KEY `sscc_date` (`sscc_date`),
  KEY `sscc_ident` (`sscc_ident`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1873 ROW_FORMAT=DYNAMIC COMMENT='Кэш сервисов ЦБ' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_services_cb_temps`
--

CREATE TABLE IF NOT EXISTS `sb_services_cb_temps` (
  `ssct_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ssct_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `ssct_date` text NOT NULL COMMENT 'Формат вывода даты',
  `ssct_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `ssct_body` text COMMENT 'Дизайн вывода элемента',
  `ssct_error` text NOT NULL COMMENT 'Сообщение при отсутствии соединения с ЦБ',
  PRIMARY KEY (`ssct_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1557 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна сервисов ЦБ' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_services_rutube`
--

CREATE TABLE IF NOT EXISTS `sb_services_rutube` (
  `ssr_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ssr_name` varchar(255) NOT NULL COMMENT 'Наименование ролика',
  `ssr_description` text NOT NULL COMMENT 'Описание ролика',
  `ssr_date` int(10) unsigned NOT NULL COMMENT 'Дата создания или закачки ролика',
  `ssr_views` int(10) unsigned NOT NULL COMMENT 'Количество просмотров',
  `ssr_rutube_id` varchar(100) NOT NULL COMMENT 'Уникальный код ролика',
  `ssr_author` varchar(255) NOT NULL COMMENT 'Автор ролика',
  `ssr_duration` int(10) unsigned NOT NULL COMMENT 'Продолжительность ролика',
  `ssr_size` int(10) unsigned NOT NULL COMMENT 'Размер ролика',
  `ssr_user_id` int(10) unsigned DEFAULT NULL COMMENT 'Идентификатор пользователя сайта, добавившего ролик',
  `ssr_status` int(10) unsigned NOT NULL COMMENT 'Статус публикации',
  `ssr_show` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `ssr_url` varchar(255) DEFAULT NULL COMMENT 'Псевдостатический адрес',
  `ssr_pub_start` int(11) unsigned DEFAULT NULL COMMENT 'Дата начала публикации ролика',
  `ssr_pub_end` int(11) unsigned DEFAULT NULL COMMENT 'Дата конца публикации ролика',
  `ssr_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор видеоролика',
  PRIMARY KEY (`ssr_id`),
  KEY `ssr_user_id` (`ssr_user_id`),
  KEY `ssr_views` (`ssr_views`),
  KEY `ssr_date` (`ssr_date`),
  KEY `ssr_duration` (`ssr_duration`),
  KEY `ssr_show` (`ssr_show`,`ssr_pub_start`,`ssr_pub_end`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=534 ROW_FORMAT=DYNAMIC COMMENT='Данные роликов модуля ruTube' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_services_rutube_temps_form`
--

CREATE TABLE IF NOT EXISTS `sb_services_rutube_temps_form` (
  `ssrtf_id` int(11) NOT NULL AUTO_INCREMENT,
  `ssrtf_title` varchar(255) NOT NULL COMMENT 'Наименование макета дизайна',
  `ssrtf_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `ssrtf_form` text NOT NULL COMMENT 'Макет дизайна формы добавления',
  `ssrtf_fields_temps` text NOT NULL COMMENT 'Макеты дизайна полей формы(сериализованный массив)',
  `ssrtf_messages` text COMMENT 'Сообщения формы',
  `ssrtf_categs_temps` text COMMENT 'Макеты дизайна пользовательских полей разделов',
  PRIMARY KEY (`ssrtf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты формы добавления роликов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_services_rutube_temps_full`
--

CREATE TABLE IF NOT EXISTS `sb_services_rutube_temps_full` (
  `ssrtf_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `ssrtf_title` varchar(255) NOT NULL COMMENT 'Наименование макета дизайна',
  `ssrtf_lang` varchar(5) NOT NULL COMMENT 'Язык',
  `ssrtf_fullelement` text NOT NULL COMMENT 'Макет дизайна полного вывода ролика',
  `ssrtf_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна массива полей роликов',
  `ssrtf_categs_temps` longtext NOT NULL COMMENT 'Макеты дизайна массива полей разделов',
  `ssrtf_checked` varchar(100) NOT NULL COMMENT 'Вывод тех роликов, для которых установлены флажки',
  `ssrtf_comments_id` int(10) DEFAULT NULL COMMENT 'ID макета комментариев',
  `ssrtf_voting_id` int(10) DEFAULT NULL COMMENT 'ID макета голосования',
  `ssrtf_user_data_id` int(10) NOT NULL COMMENT 'ID макета вывода данных пользователя',
  `ssrtf_tags_list_id` int(10) NOT NULL,
  PRIMARY KEY (`ssrtf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1876 COMMENT='Макеты дизайна просм-ра роликов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_services_rutube_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_services_rutube_temps_list` (
  `ssrt_id` int(10) NOT NULL AUTO_INCREMENT,
  `ssrt_name` varchar(255) NOT NULL COMMENT 'Наименование макета',
  `ssrt_lang` varchar(5) NOT NULL COMMENT 'Язык вывода',
  `ssrt_checked` varchar(100) NOT NULL COMMENT 'Вывод тех роликов, для которых установленый флажки',
  `ssrt_count_row` int(11) NOT NULL COMMENT 'Количество роликов в строке',
  `ssrt_top` text NOT NULL COMMENT 'Верх вывода (общий)',
  `ssrt_categ_top` text NOT NULL COMMENT 'Верх вывода раздела',
  `ssrt_temp_elem` text NOT NULL COMMENT 'Макет дизайна ролика',
  `ssrt_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `ssrt_delim` text NOT NULL COMMENT 'Разделитель строк',
  `ssrt_categ_bottom` text NOT NULL COMMENT 'Низ вывода раздела',
  `ssrt_bottom` text NOT NULL COMMENT 'Низ вывода (общий)',
  `ssrt_pagelist_id` int(10) NOT NULL COMMENT 'id макета дизайна постраничного вывода',
  `ssrt_no_movies` text NOT NULL COMMENT 'Сообщение "Нет роликов"',
  `ssrt_perpage` int(10) NOT NULL COMMENT 'Количество роликов на странице',
  `ssrt_fields_temps` longtext NOT NULL COMMENT 'Массив макетов полей роликов',
  `ssrt_categs_temps` longtext NOT NULL COMMENT 'Массив макетов полей разделов',
  `ssrt_user_data_id` int(10) NOT NULL COMMENT 'ID макет вывода данных пользователя',
  `ssrt_tags_list_id` int(10) NOT NULL,
  `ssrt_votes_id` int(10) DEFAULT NULL COMMENT 'ID макетов голосований',
  `ssrt_comments_id` int(10) DEFAULT NULL COMMENT 'ID макетов комментариев',
  PRIMARY KEY (`ssrt_id`),
  KEY `ssrt_perpage` (`ssrt_perpage`),
  KEY `ssrt_pagelist_id` (`ssrt_pagelist_id`),
  KEY `ssrt_count_row` (`ssrt_count_row`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1380 COMMENT='Макеты дизайна списков роликов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_settings`
--

CREATE TABLE IF NOT EXISTS `sb_settings` (
  `s_setting` varchar(50) NOT NULL COMMENT 'Идентификатор настройки',
  `s_value` text NOT NULL COMMENT 'Значение настройки',
  `s_domain` varchar(100) NOT NULL COMMENT 'Домен',
  PRIMARY KEY (`s_setting`,`s_domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Настройки системы';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_sitemap`
--

CREATE TABLE IF NOT EXISTS `sb_sitemap` (
  `sm_id` int(10) NOT NULL AUTO_INCREMENT,
  `sm_date` int(11) DEFAULT NULL COMMENT 'Время индексации',
  `sm_domain` varchar(255) NOT NULL COMMENT 'Домен для которого будет формироваться база',
  `sm_settings` text NOT NULL COMMENT 'Настройки индексации',
  PRIMARY KEY (`sm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_site_users`
--

CREATE TABLE IF NOT EXISTS `sb_site_users` (
  `su_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор пользователя сайта',
  `su_auto_login` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'Хэш для автологина',
  `su_last_ip` varchar(30) DEFAULT NULL COMMENT 'Последний IP пользователя',
  `su_activation_code` varchar(255) DEFAULT NULL COMMENT 'Код активации по e-mail',
  `su_name` varchar(255) DEFAULT NULL COMMENT 'Ф.И.О Пользователя',
  `su_pass` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Пароль',
  `su_email` varchar(50) DEFAULT NULL COMMENT 'E-mail',
  `su_login` varchar(50) DEFAULT NULL COMMENT 'Логин на сайте',
  `su_soc_login` varchar(50) DEFAULT NULL COMMENT 'Логин на сайте для авторизации через соц. сети',
  `su_reg_date` int(11) DEFAULT NULL COMMENT 'Дата регистрации',
  `su_last_date` int(11) DEFAULT NULL COMMENT 'Дата последней активности',
  `su_active_date` int(11) unsigned DEFAULT '0' COMMENT 'Дата, до которой активен аккаунт пользователя',
  `su_domains` varchar(255) NOT NULL COMMENT 'Домены',
  `su_status` tinyint(2) NOT NULL COMMENT 'Статус регистрации: зарегистрирован, заблокирован и т.д',
  `su_pers_foto` varchar(255) DEFAULT NULL COMMENT 'Персональное фото (аватар)',
  `su_pers_phone` varchar(50) DEFAULT NULL COMMENT 'Телефон',
  `su_pers_mob_phone` varchar(50) DEFAULT NULL COMMENT 'Мобильный телефон',
  `su_pers_birth` varchar(11) DEFAULT NULL COMMENT 'День рождения',
  `su_pers_sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Пол 0 - муж. 1- жен',
  `su_pers_zip` varchar(6) DEFAULT NULL COMMENT 'Почтовый код',
  `su_pers_adress` varchar(255) DEFAULT NULL COMMENT 'Адрес проживания',
  `su_pers_addition` varchar(255) DEFAULT NULL COMMENT 'Дополнительные персональные данные',
  `su_work_name` varchar(255) DEFAULT NULL COMMENT 'Название  компании',
  `su_work_phone` varchar(50) DEFAULT NULL COMMENT 'Рабочий телефон',
  `su_work_phone_inner` varchar(50) DEFAULT NULL COMMENT 'Внутренний рабочий телефон',
  `su_work_fax` varchar(50) DEFAULT NULL COMMENT 'Рабочий факс',
  `su_work_email` varchar(50) DEFAULT NULL COMMENT 'Рабочий e-mail',
  `su_work_addition` text COMMENT 'Дополнительно о месте работы',
  `su_work_office_number` varchar(50) DEFAULT NULL COMMENT 'Номер офиса',
  `su_work_unit` varchar(255) DEFAULT NULL COMMENT 'Отдел/Подразделение',
  `su_work_position` varchar(50) DEFAULT NULL COMMENT 'Должность',
  `su_forum_nick` varchar(50) DEFAULT NULL COMMENT 'Псевдоним (для форума)',
  `su_forum_text` varchar(255) DEFAULT NULL COMMENT 'Подпись (для форума)',
  `su_mail_lang` varchar(5) DEFAULT NULL COMMENT 'Язык рассылки',
  `su_mail_status` tinyint(4) DEFAULT NULL COMMENT 'Статус рассылки',
  `su_mail_date` int(11) DEFAULT NULL COMMENT 'Дата для статуса рассылки не активна до',
  `su_mail_subscription` text COMMENT 'Подписанные рассылки',
  `su_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор пользователя',
  PRIMARY KEY (`su_id`),
  KEY `su_active_date` (`su_active_date`),
  KEY `su_auto_login` (`su_auto_login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Пользователи сайта' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_site_users_temps`
--

CREATE TABLE IF NOT EXISTS `sb_site_users_temps` (
  `sut_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `sut_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `sut_lang` varchar(5) NOT NULL COMMENT 'Язык макета дизайна',
  `sut_form` text NOT NULL COMMENT 'Макет дизайна формы',
  `sut_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна полей формы (сериализованный массив)',
  `sut_categs_temps` longtext COMMENT 'Макеты полей группы пользователей',
  `sut_messages` longtext COMMENT 'Макеты дизайна сообщений (сериализованный массив)',
  `sut_votes_id` int(10) unsigned DEFAULT NULL COMMENT 'ID макета дизайна голосований',
  `sut_comments_id` int(10) unsigned DEFAULT NULL COMMENT 'ID макета комментариев',
  `sut_checked` varchar(100) DEFAULT NULL COMMENT 'Выводить пользователей, для которых помечены флажки',
  PRIMARY KEY (`sut_id`),
  KEY `sut_votes_id` (`sut_votes_id`),
  KEY `sut_comments_id` (`sut_comments_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна форм' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_site_users_temps_list`
--

CREATE TABLE IF NOT EXISTS `sb_site_users_temps_list` (
  `sutl_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `sutl_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Название макета дизайна',
  `sutl_lang` varchar(5) NOT NULL DEFAULT '' COMMENT 'Язык макета дизайна',
  `sutl_checked` varchar(100) NOT NULL DEFAULT '' COMMENT 'Выводить пользователей, для которых помечены флажки',
  `sutl_count` int(11) unsigned NOT NULL COMMENT 'Кол-во пользователей в строке',
  `sutl_top` text NOT NULL COMMENT 'Верх вывода',
  `sutl_categ_top` text NOT NULL COMMENT 'Верх вывода (раздел)',
  `sutl_element` text NOT NULL COMMENT 'Вывод пользователя',
  `sutl_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `sutl_delim` text NOT NULL COMMENT 'Разделитель строк',
  `sutl_categ_bottom` text NOT NULL COMMENT 'Низ вывода (раздел)',
  `sutl_bottom` text NOT NULL COMMENT 'Низ вывода',
  `sutl_pagelist_id` int(10) unsigned NOT NULL COMMENT 'Макет дизайна постраничного вывода',
  `sutl_perpage` int(10) unsigned NOT NULL COMMENT 'Кол-во пользователей на странице',
  `sutl_messages` longtext NOT NULL COMMENT 'Сообщения',
  `sutl_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна полей пользователя',
  `sutl_categs_temps` longtext NOT NULL COMMENT 'Макеты дизайна полей раздела',
  `sutl_votes_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна голосований',
  `sutl_comments_id` int(10) unsigned DEFAULT NULL COMMENT 'Макет дизайна комментариев',
  PRIMARY KEY (`sutl_id`),
  KEY `sutl_comments_id` (`sutl_comments_id`),
  KEY `sutl_votes_id` (`sutl_votes_id`),
  KEY `sutl_pagelist_id` (`sutl_pagelist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна списка пользователей' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_socnet_users`
--

CREATE TABLE IF NOT EXISTS `sb_socnet_users` (
  `sbsu_id` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор пользователя соц. сети',
  `sbsu_uid` int(10) unsigned DEFAULT NULL COMMENT 'Идентификатор пользователя сайта',
  `sbsu_sn_type` varchar(20) NOT NULL COMMENT 'Маркер соц. сети',
  KEY `sbsu_uid` (`sbsu_uid`),
  KEY `sbsu_sn_type` (`sbsu_sn_type`),
  KEY `sbsu_id` (`sbsu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='таблица связи аккаунтов соц. сетей с акк-ми польз-й сайта';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_sprav`
--

CREATE TABLE IF NOT EXISTS `sb_sprav` (
  `s_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор элемента справочника',
  `s_title` varchar(255) NOT NULL COMMENT 'Название элемента справочника',
  `s_prop1` text COMMENT 'Описание 1 элемента справочника',
  `s_prop2` text COMMENT 'Описание 2 элемента справочника',
  `s_prop3` text COMMENT 'Описание 3 элемента справочника',
  `s_sort` int(11) NOT NULL DEFAULT '0' COMMENT 'Порядок соритровки',
  `s_active` tinyint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Статус публикации',
  `s_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор элемента справочника',
  PRIMARY KEY (`s_id`),
  KEY `s_ext_id` (`s_ext_id`),
  KEY `s_order` (`s_sort`,`s_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Справочники' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_sprav_temps`
--

CREATE TABLE IF NOT EXISTS `sb_sprav_temps` (
  `st_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `st_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `st_pagelist_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор макета для постраничного вывода',
  `st_perpage` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Кол-во элементов на странице',
  `st_no_elems` text NOT NULL COMMENT 'Сообщение "элементов нет"',
  `st_fields_temps` longtext NOT NULL COMMENT 'Макеты дизайна описаний',
  `st_count` int(10) unsigned NOT NULL COMMENT 'Кол-во элементов в строке',
  `st_top` text NOT NULL COMMENT 'Верх вывода общий',
  `st_cat_top` text NOT NULL COMMENT 'Верх вывода раздела',
  `st_element` text NOT NULL COMMENT 'Элемент справочника',
  `st_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `st_delim` text NOT NULL COMMENT 'Разделитель строк',
  `st_cat_bottom` text NOT NULL COMMENT 'Низ вывода раздела',
  `st_bottom` text NOT NULL COMMENT 'Низ вывода общий',
  `st_full_element` text NOT NULL COMMENT 'Полный элемент справочника',
  PRIMARY KEY (`st_id`),
  KEY `st_pagelist_id` (`st_pagelist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Макеты дизайна справочников' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_system_log`
--

CREATE TABLE IF NOT EXISTS `sb_system_log` (
  `sl_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор записи',
  `sl_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата записи',
  `sl_user_id` int(11) NOT NULL COMMENT 'Идентификатор пользователя системы',
  `sl_user_login` varchar(50) NOT NULL COMMENT 'Логин пользователя системы',
  `sl_user_ip` varchar(30) NOT NULL COMMENT 'IP пользователя системы',
  `sl_message` text NOT NULL COMMENT 'Сообщение',
  `sl_type` tinyint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Тип сообщения',
  `sl_domain` varchar(100) NOT NULL COMMENT 'Домен',
  PRIMARY KEY (`sl_id`),
  KEY `sl_user_id` (`sl_user_id`),
  KEY `sl_type` (`sl_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Системный журнал' AUTO_INCREMENT=38 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_templates`
--

CREATE TABLE IF NOT EXISTS `sb_templates` (
  `t_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `t_name` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `t_html` longtext NOT NULL COMMENT '"Тело" макета дизайна',
  `t_xslt` varchar(255) DEFAULT NULL COMMENT 'Таблица стилей XSLT макета дизайна',
  `t_ctype` varchar(50) DEFAULT NULL COMMENT 'Content-type',
  PRIMARY KEY (`t_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна сайта' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_answers`
--

CREATE TABLE IF NOT EXISTS `sb_tester_answers` (
  `stw_id` int(11) NOT NULL AUTO_INCREMENT,
  `stw_quest_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID вопроса',
  `stw_answer` text NOT NULL COMMENT 'Ответ на вопрос',
  `stw_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Порядок расположения ответов',
  `stw_mark` double NOT NULL DEFAULT '0' COMMENT 'Оценка за ответ',
  `stw_is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Ответ удален (0-нет, 1-да)',
  `stw_any_answer` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Произвольный ответ (0-нет, 1-да)',
  PRIMARY KEY (`stw_id`),
  KEY `stw_quest_id` (`stw_quest_id`,`stw_order`,`stw_mark`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Таблица ответов для вопросов тестов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_answers_results`
--

CREATE TABLE IF NOT EXISTS `sb_tester_answers_results` (
  `star_id` int(11) NOT NULL AUTO_INCREMENT,
  `star_user_id` varchar(20) NOT NULL COMMENT 'ID пользователя сайта',
  `star_test_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID теста',
  `star_attempt_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Кол-во попыток сдать тест',
  `star_quest_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID вопроса',
  `star_answer_ids` varchar(100) NOT NULL COMMENT 'ID-ы ответов на вопрос',
  `star_time` int(11) NOT NULL DEFAULT '0' COMMENT 'Время дачи ответа',
  `star_answer_time` int(11) NOT NULL DEFAULT '0' COMMENT 'Время затраченное на ответ',
  `star_mark` double NOT NULL DEFAULT '0' COMMENT 'Оценка за ответ',
  `star_cat_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID подтеста',
  PRIMARY KEY (`star_id`),
  KEY `btar_user_id` (`star_user_id`,`star_test_id`,`star_attempt_id`,`star_quest_id`,`star_mark`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Таблица детальных результатов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_any_answers`
--

CREATE TABLE IF NOT EXISTS `sb_tester_any_answers` (
  `staa_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staa_user_id` varchar(20) NOT NULL,
  `staa_quest_id` int(10) unsigned NOT NULL,
  `staa_answer_id` int(10) unsigned NOT NULL,
  `staa_answer_text` text NOT NULL,
  PRIMARY KEY (`staa_id`),
  UNIQUE KEY `staa_user_id_staa_quest_id_staa_answer_id` (`staa_user_id`,`staa_quest_id`,`staa_answer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Текст произвольных ответов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_marks_results`
--

CREATE TABLE IF NOT EXISTS `sb_tester_marks_results` (
  `stmr_id` int(11) NOT NULL AUTO_INCREMENT,
  `stmr_test_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID теста',
  `stmr_start` int(11) NOT NULL DEFAULT '0' COMMENT 'Итоговая оценка "от"',
  `stmr_end` int(11) NOT NULL DEFAULT '0' COMMENT 'Итоговая оценка "до"',
  `stmr_result` text NOT NULL COMMENT 'Сообщение описывающее результат теста',
  `stmr_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Порядок расположения результатов теста',
  PRIMARY KEY (`stmr_id`),
  KEY `stmr_order` (`stmr_order`),
  KEY `stmr_test_id` (`stmr_test_id`),
  KEY `smr_test_id` (`stmr_test_id`,`stmr_start`,`stmr_end`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Итоговые оценки для тестов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_questions`
--

CREATE TABLE IF NOT EXISTS `sb_tester_questions` (
  `stq_id` int(11) NOT NULL AUTO_INCREMENT,
  `stq_question` text NOT NULL COMMENT 'Текст вопроса',
  `stq_type` set('radio','checkbox','sorting','inline') NOT NULL COMMENT 'Возможность выбрать один или несколько вариантов ответа',
  `stq_ball` int(11) NOT NULL DEFAULT '0' COMMENT 'Оценка вопроса типа "расстановка ответов" (sorting)',
  `stq_aps_write` int(1) NOT NULL DEFAULT '0' COMMENT 'Засчитывать баллы при полностью верном ответе',
  `stq_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Порядковый номер вывода вопроса',
  `stq_show` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  PRIMARY KEY (`stq_id`),
  KEY `stq_order` (`stq_order`),
  KEY `stq_show` (`stq_show`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Таблица вопросов для тестов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_rating_temps`
--

CREATE TABLE IF NOT EXISTS `sb_tester_rating_temps` (
  `strt_id` int(11) NOT NULL AUTO_INCREMENT,
  `strt_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `strt_pagelist_id` int(100) NOT NULL COMMENT 'ID макета дизайна постраничного вывода',
  `strt_perpage` int(10) NOT NULL COMMENT 'Кол-во рейтингов на странице',
  `strt_top` text NOT NULL COMMENT 'Верх вывода (общий)',
  `strt_elem` text NOT NULL COMMENT 'Макет вывода участника',
  `strt_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `strt_delim` text NOT NULL COMMENT 'Разделитель строк',
  `strt_bottom` text NOT NULL COMMENT 'Низ вывода (общий)',
  `strt_count` text NOT NULL COMMENT 'Кол-во рейтингов в строке',
  `strt_categs_temps` text NOT NULL COMMENT 'Массив макетов полей раздела',
  `strt_system_message` text NOT NULL COMMENT 'Системные сообщения',
  PRIMARY KEY (`strt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты вывода рейтинга' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_results`
--

CREATE TABLE IF NOT EXISTS `sb_tester_results` (
  `str_id` int(11) NOT NULL AUTO_INCREMENT,
  `str_test_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID пройденного теста',
  `str_user_id` varchar(20) NOT NULL DEFAULT '0' COMMENT 'ID пользователя сайта',
  `str_time` int(11) NOT NULL DEFAULT '0' COMMENT 'Дата прохождения теста',
  `str_mark` double NOT NULL DEFAULT '0' COMMENT 'Общее кол-во набранных баллов за тест',
  `str_test_time` int(11) NOT NULL DEFAULT '0' COMMENT 'Общее время затраченное на тест',
  `str_ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP-адрес с которого сдавали тест',
  `str_certificat` tinyint(1) NOT NULL DEFAULT '0',
  `str_num_attempts` int(11) NOT NULL DEFAULT '0' COMMENT 'Кол-во попыток использованных пользователем сдать тест',
  `str_passed` tinyint(2) NOT NULL COMMENT 'Сдан тест или нет',
  PRIMARY KEY (`str_id`),
  KEY `str_test_id` (`str_test_id`,`str_user_id`,`str_time`,`str_mark`),
  KEY `str_test_time` (`str_test_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Общие результаты тестов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_result_temps`
--

CREATE TABLE IF NOT EXISTS `sb_tester_result_temps` (
  `strt_id` int(11) NOT NULL AUTO_INCREMENT,
  `strt_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Наименование макета вывода результатов',
  `strt_lang` varchar(10) NOT NULL DEFAULT '' COMMENT 'Язык',
  `strt_date` text NOT NULL COMMENT 'Формат вывода даты',
  `strt_top` text NOT NULL COMMENT 'Верх вывода (Общий)',
  `strt_result` text NOT NULL COMMENT 'Макет вывода разультата',
  `strt_bottom` text NOT NULL COMMENT 'Низ вывода (Общий)',
  `strt_empty` text NOT NULL COMMENT 'Пустая ячейка',
  `strt_delim` text NOT NULL COMMENT 'Разделитель строк',
  `strt_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Кол-во результатов в строке',
  `strt_perpage` int(10) NOT NULL COMMENT 'Кол-во результатов тестов на странице',
  `strt_pagelist_id` int(10) NOT NULL COMMENT 'ID Макета дизайна постраничного вывода',
  `strt_system_message` text NOT NULL COMMENT 'Системные сообщения',
  PRIMARY KEY (`strt_id`),
  KEY `strt_title` (`strt_title`),
  KEY `strt_count` (`strt_count`),
  KEY `strt_perpage` (`strt_perpage`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты вывода результ-ов тестов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_temps`
--

CREATE TABLE IF NOT EXISTS `sb_tester_temps` (
  `stt_id` int(11) NOT NULL AUTO_INCREMENT,
  `stt_title` varchar(255) NOT NULL COMMENT 'Название макета дизайна',
  `stt_date` text NOT NULL COMMENT 'Формат вывода даты',
  `stt_lang` varchar(10) NOT NULL COMMENT 'Язык для вывода даты',
  `stt_top` text NOT NULL COMMENT 'Верх вывода',
  `stt_quest` text COMMENT 'Макет вопроса',
  `stt_chet_answer` text NOT NULL COMMENT 'Макет четного варианта ответа',
  `stt_nechet_answer` text NOT NULL COMMENT 'Макет нечетного варианта ответа',
  `stt_empty_element` text NOT NULL COMMENT 'Макет пустой ячейки',
  `stt_always_chet` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Всегда выводить четное число вариантов',
  `stt_bottom` text NOT NULL COMMENT 'Низ вывода',
  `stt_result` text NOT NULL COMMENT 'Макет вывода итогового результата',
  `stt_system_message` text NOT NULL COMMENT 'Сериализованный массив макетов системных сообщений',
  `stt_fields_temps` text NOT NULL COMMENT 'Массив макетов пользовательских полей',
  `stt_categs_temps` text NOT NULL COMMENT 'Массив макетов пользовательских полей (раздела)',
  PRIMARY KEY (`stt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна вывода тестов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_temp_any_answer`
--

CREATE TABLE IF NOT EXISTS `sb_tester_temp_any_answer` (
  `sttaa_user_id` varchar(20) NOT NULL,
  `sttaa_quest_id` int(10) unsigned NOT NULL,
  `sttaa_answer_id` int(10) unsigned NOT NULL,
  `sttaa_answer_text` text NOT NULL,
  KEY `sttaa_user_id_sttaa_quest_id_sttaa_answer_id` (`sttaa_user_id`,`sttaa_quest_id`,`sttaa_answer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Таблица для временного хранения произвольных ответов';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_temp_interrupts`
--

CREATE TABLE IF NOT EXISTS `sb_tester_temp_interrupts` (
  `stti_user_id` varchar(20) NOT NULL COMMENT 'ID прервавшего тест пользователя',
  `stti_test_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID теста который был прерван',
  KEY `btti_test_id` (`stti_test_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Таблица прерывания тестов';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_tester_temp_results`
--

CREATE TABLE IF NOT EXISTS `sb_tester_temp_results` (
  `sttr_id` int(11) NOT NULL AUTO_INCREMENT,
  `sttr_user_id` varchar(20) NOT NULL DEFAULT '0' COMMENT 'ID пользователя сайта ответившего на вопрос',
  `sttr_test_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID теста',
  `sttr_quest_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID вопроса на который дали ответ',
  `sttr_answer_ids` varchar(100) NOT NULL DEFAULT '0' COMMENT 'ID-ы ответов на вопрос',
  `sttr_time` int(11) NOT NULL DEFAULT '0' COMMENT 'Время ответа',
  `sttr_answer_time` int(11) NOT NULL DEFAULT '0' COMMENT 'Время затраченное на ответ',
  `sttr_mark` double NOT NULL DEFAULT '0' COMMENT 'Оценка за ответ',
  `sttr_cat_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID подтеста',
  PRIMARY KEY (`sttr_id`),
  KEY `bttr_mark` (`sttr_mark`),
  KEY `bttr_user_id` (`sttr_user_id`),
  KEY `bttr_test_id` (`sttr_test_id`),
  KEY `bttr_quest_id` (`sttr_quest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Временное хранение данных теста' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_texts`
--

CREATE TABLE IF NOT EXISTS `sb_texts` (
  `t_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор текстового блока',
  `t_name` varchar(255) NOT NULL COMMENT 'Название текстового блока',
  `t_html` longtext NOT NULL COMMENT 'Текст',
  `t_status` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Статус публикации',
  `t_old_html` longtext COMMENT 'Публикуемый текст',
  `t_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор текстового блока',
  PRIMARY KEY (`t_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Тексты' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_total_cache`
--

CREATE TABLE IF NOT EXISTS `sb_total_cache` (
  `stc_hash` varchar(32) NOT NULL COMMENT 'Хэш запроса',
  `stc_total_count` int(11) NOT NULL COMMENT 'Количество элементов по запросу',
  `stc_timestamp` int(11) NOT NULL COMMENT 'Время создания',
  PRIMARY KEY (`stc_hash`),
  KEY `stc_timestamp` (`stc_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Кеширование количества элементов для пейдженатора';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_updates`
--

CREATE TABLE IF NOT EXISTS `sb_updates` (
  `u_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор записи',
  `u_update_id` varchar(10) DEFAULT NULL COMMENT 'Идентификатор обновления',
  `u_release_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата релиза',
  `u_setup_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата установки',
  `u_info` text COMMENT 'Описание обновления',
  `u_sql` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Использовался в обновления SQL или нет',
  `u_type` set('normal','high') NOT NULL DEFAULT 'normal' COMMENT 'Тип обновления',
  PRIMARY KEY (`u_id`),
  KEY `u_update_id` (`u_update_id`),
  KEY `u_release_date` (`u_release_date`),
  KEY `u_setup_date` (`u_setup_date`),
  KEY `u_sql` (`u_sql`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Обновления системы' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_users`
--

CREATE TABLE IF NOT EXISTS `sb_users` (
  `u_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор пользователя',
  `u_auto_login` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'Хэш для автологина',
  `u_block` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Пользователь заблокирован или нет',
  `u_login` varchar(50) NOT NULL COMMENT 'Логин пользователя',
  `u_pass` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Пароль пользователя (MD5)',
  `u_name` varchar(100) DEFAULT NULL COMMENT 'Ф.И.О. пользователя',
  `u_admin` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Администратор или нет',
  `u_reg_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата регистрации пользователя',
  `u_last_ip` varchar(30) NOT NULL DEFAULT '-' COMMENT 'IP пользователя',
  `u_last_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата последней активности пользователя',
  `u_email` varchar(100) DEFAULT NULL COMMENT 'E-mail пользователя',
  `u_active_date` int(11) DEFAULT '0' COMMENT 'Пользователь активен до',
  `u_domains` varchar(255) NOT NULL COMMENT 'Домены, администрируемые пользователем',
  `u_uploading_exts` text NOT NULL COMMENT 'Расширения файлов, доступные для загрузки пользователем',
  `u_strip_php` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Вырезать PHP-код или нет',
  `u_pers_birth` varchar(10) DEFAULT NULL COMMENT 'Дата рождения',
  `u_pers_prof` varchar(100) DEFAULT NULL COMMENT 'Профессия',
  `u_pers_otdel` varchar(100) DEFAULT NULL COMMENT 'Отдел',
  `u_pers_dolj` varchar(100) DEFAULT NULL COMMENT 'Должность',
  `u_pers_messanger_type` smallint(3) unsigned DEFAULT NULL COMMENT 'Тип интернет-пейджера',
  `u_pers_messanger_id` varchar(100) DEFAULT NULL COMMENT 'Идентификатор интернет-пейджера',
  `u_pers_phone` varchar(50) DEFAULT NULL COMMENT 'Персональный телефон',
  `u_pers_mob_phone` varchar(50) DEFAULT NULL COMMENT 'Персональный мобильный телефон',
  `u_pers_address` varchar(255) DEFAULT NULL COMMENT 'Персональный адрес',
  `u_pers_dop` text COMMENT 'Доп. персональная информация',
  `u_pers_foto` varchar(255) DEFAULT NULL COMMENT 'Аватар внутри системы',
  `u_work_title` varchar(100) DEFAULT NULL COMMENT 'Название компании',
  `u_work_phone` varchar(50) DEFAULT NULL COMMENT 'Корпоративный телефон',
  `u_work_inner_phone` varchar(50) DEFAULT NULL COMMENT 'Корпоративный внутренний телефон',
  `u_work_fax` varchar(50) DEFAULT NULL COMMENT 'Корпоративный факс',
  `u_work_email` varchar(100) DEFAULT NULL COMMENT 'Корпоративный E-mail',
  `u_work_office` varchar(100) DEFAULT NULL COMMENT 'Номер офиса',
  `u_work_dop` text COMMENT 'Доп. корпоративная информация',
  `u_ext_id` varchar(100) DEFAULT NULL COMMENT 'Внешний идентификатор пользователя',
  `u_wrong_pass_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Кол-во последовательных входов с неправильным паролем',
  `u_soap_token` varchar(255) NOT NULL,
  `u_pass_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата последнего изменения пароля',
  `u_notify` tinyint(1) unsigned DEFAULT NULL COMMENT 'Получать системные уведомления',
  PRIMARY KEY (`u_id`),
  UNIQUE KEY `u_login` (`u_login`),
  KEY `bu_email` (`u_email`),
  KEY `bu_reg_date` (`u_reg_date`),
  KEY `bu_last_date` (`u_last_date`),
  KEY `u_active_date` (`u_active_date`),
  KEY `u_ext_id` (`u_ext_id`),
  KEY `u_auto_login` (`u_auto_login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Пользователи системы' AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `sb_users`
--

INSERT INTO `sb_users` (`u_id`, `u_auto_login`, `u_block`, `u_login`, `u_pass`, `u_name`, `u_admin`, `u_reg_date`, `u_last_ip`, `u_last_date`, `u_email`, `u_active_date`, `u_domains`, `u_uploading_exts`, `u_strip_php`, `u_pers_birth`, `u_pers_prof`, `u_pers_otdel`, `u_pers_dolj`, `u_pers_messanger_type`, `u_pers_messanger_id`, `u_pers_phone`, `u_pers_mob_phone`, `u_pers_address`, `u_pers_dop`, `u_pers_foto`, `u_work_title`, `u_work_phone`, `u_work_inner_phone`, `u_work_fax`, `u_work_email`, `u_work_office`, `u_work_dop`, `u_ext_id`, `u_wrong_pass_count`, `u_soap_token`, `u_pass_date`, `u_notify`) VALUES
(1, '', 0, 'Admin', '', '', 1, 0, '', 0, '', 0, '', '', 0, '', '', '', '', 0, '', '', '', '', '', NULL, '', '', '', '', '', '', '', NULL, 0, '', 0, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `sb_users_messages`
--

CREATE TABLE IF NOT EXISTS `sb_users_messages` (
  `um_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор сообщения',
  `um_to_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентияикатор пользователя, которому отправлено сообщение',
  `um_to_ids` text NOT NULL COMMENT 'Идентификаторы пользователей, которым отправлена копия сообщения',
  `um_from_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор пользователя, которым отправлено сообщение',
  `um_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Время и дата сообщения',
  `um_title` text NOT NULL COMMENT 'Заголовок сообщения',
  `um_message` text NOT NULL COMMENT 'Текст сообщения',
  `um_look` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Просмотрено сообщение или нет',
  `um_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Удално сообщение или нет',
  `um_parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор родительского сообщения',
  `um_files` text NOT NULL COMMENT 'Файлы, прикрепленные к сообщению',
  PRIMARY KEY (`um_id`),
  KEY `um_from_id` (`um_from_id`),
  KEY `um_time` (`um_time`),
  KEY `um_parent_id` (`um_parent_id`),
  KEY `um_to_id` (`um_to_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Личные сообщения' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_users_rights`
--

CREATE TABLE IF NOT EXISTS `sb_users_rights` (
  `ur_cat_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор группы пользователей',
  `ur_plugin` varchar(100) NOT NULL COMMENT 'Идентификатор модуля',
  `ur_plugin_rights` varchar(255) NOT NULL COMMENT 'Права доступа к модулю',
  `ur_workflow_rights` varchar(255) NOT NULL COMMENT 'Права доступа к цепокам публикаций',
  KEY `ur_cat_id` (`ur_cat_id`),
  KEY `ur_plugin` (`ur_plugin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Права группы пользователей';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_users_sessions`
--

CREATE TABLE IF NOT EXISTS `sb_users_sessions` (
  `us_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор сессии',
  `us_ip` varchar(30) NOT NULL COMMENT 'IP пользователя',
  `us_browser` text NOT NULL COMMENT 'Браузер пользователя',
  `us_sess_id` varchar(100) NOT NULL COMMENT 'Идентификатор PHP-сессии',
  `us_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор пользователя',
  `us_session_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Время сессии пользователя',
  `us_active_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Время активности пользователя',
  PRIMARY KEY (`us_id`),
  KEY `us_user_id` (`us_user_id`),
  KEY `us_session_time` (`us_session_time`),
  KEY `us_active_time` (`us_active_time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Сессии пользователей системы' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_users_settings`
--

CREATE TABLE IF NOT EXISTS `sb_users_settings` (
  `us_setting` varchar(50) NOT NULL COMMENT 'Идентификатор настройки',
  `us_value` text NOT NULL COMMENT 'Значение настройки',
  `us_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор пользователя системы',
  PRIMARY KEY (`us_setting`,`us_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Настройки интерфейса';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_vote_ips`
--

CREATE TABLE IF NOT EXISTS `sb_vote_ips` (
  `vi_vr_id` int(10) unsigned NOT NULL COMMENT 'ID элемента за который проголосовали из таб. sb_vote_results',
  `vi_ip` varchar(30) NOT NULL COMMENT 'ip-адрес',
  `vi_count` int(10) NOT NULL COMMENT 'Сколько раз проголосовали с данного ip-адреса',
  UNIQUE KEY `vi_vr_id` (`vi_vr_id`,`vi_ip`),
  KEY `vi_count` (`vi_count`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=24 COMMENT='ip-адреса с которых голосовали';

-- --------------------------------------------------------

--
-- Структура таблицы `sb_vote_results`
--

CREATE TABLE IF NOT EXISTS `sb_vote_results` (
  `vr_id` int(10) NOT NULL AUTO_INCREMENT,
  `vr_plugin` varchar(255) NOT NULL COMMENT 'идентификатор модуля',
  `vr_el_id` int(10) NOT NULL COMMENT 'ID элемента за который голосуют',
  `vr_count` double NOT NULL COMMENT 'Сумма баллов, отданных за элемент',
  `vr_num` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Кол-во проголосовавших',
  PRIMARY KEY (`vr_id`),
  UNIQUE KEY `vr_el_id` (`vr_plugin`,`vr_el_id`),
  KEY `vr_count` (`vr_count`,`vr_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=35 ROW_FORMAT=DYNAMIC COMMENT='Данные голосования' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_vote_temps`
--

CREATE TABLE IF NOT EXISTS `sb_vote_temps` (
  `vt_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор макета дизайна',
  `vt_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Заголовок макета дизайна',
  `vt_temp` text NOT NULL COMMENT 'Макет дизайна формы',
  `vt_not_vote` text NOT NULL COMMENT 'Сообщение "Вы уже голосовали"',
  `vt_vote_ok` text NOT NULL COMMENT 'Сообщение "Ваш голос принят"',
  PRIMARY KEY (`vt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1556 ROW_FORMAT=DYNAMIC COMMENT='Макеты дизайна формы голосовани' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sb_workflow`
--

CREATE TABLE IF NOT EXISTS `sb_workflow` (
  `w_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор статуса',
  `w_title` varchar(50) NOT NULL COMMENT 'Название статуса',
  `w_order` smallint(5) NOT NULL COMMENT 'Порядок статуса',
  `w_code` smallint(5) unsigned NOT NULL COMMENT 'Код статуса',
  `w_on` tinyint(1) NOT NULL COMMENT 'Флаг. Включен статус (1) или нет (0)',
  `w_accessible` varchar(255) DEFAULT NULL COMMENT 'Идентификаторы статусов доступных для данного статуса(через |)',
  `w_show_demo` tinyint(1) unsigned NOT NULL COMMENT 'Показывать на демо-сайте',
  PRIMARY KEY (`w_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 ROW_FORMAT=DYNAMIC COMMENT='Статусы цепочки публикаций' AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
