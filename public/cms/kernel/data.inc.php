<?php
/**
 * Основные константы системы
 *
 * Объявленные в данном файле константы используются для локализации системы под конкретного дилера.
 *
 * @author Дмитрий Новиков <dn@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Название системы без форматирования
 *
 */
define('CMS_NAME', 'S.Builder');

/**
 * Название системы с форматированием
 *
 */
define('CMS_NAME_FULL', '<span style="color:#990000;">S.</span>Builder');

/**
 * Адрес сайта
 *
 */
define('CMS_SITE_NAME', 'www.sbuilder.ru');

/**
 * E-mail системы
 *
 */
define('CMS_EMAIL', 'info@sbuilder.ru');

/**
 * Название компании
 *
 */
define('CMS_COMP_NAME', 'ООО "ЭсБилдер"');

/**
 * Годы работы компании
 *
 */
define('CMS_COMP_YEAR', '2001-'.date('Y', time()));

/**
 * Отображать или нет лицензию системы
 *
 * Отображать лицензию - 1 (скриншот в разделе <kbd>Меню администратора -> О системе</kbd>), не отображать - 0.
 */
define('CMS_SHOW_LIC', '1');

/**
 * Версия системы
 *
 */
define('CMS_DISTR_VERSION', '4.121');
