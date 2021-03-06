<?php
/**
 * Языковые константы модуля для вызова отдельных функций
 *
 *
 * @author Артур Фурса <art@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2014, OOO "БИНН"
 */
define ('PL_CODE_H_PLUGIN_NAME', 'Средства разработки');
define ('PL_CODE_H_FUNC', 'Вызов внешней функции/метода');
define ('PL_CODE_H_HINT', 'Указанный ниже код должен представлять собой корректное PHP-выражение:
    - вызов функции <i>someFunc()</i>;
    - вызов статического метода класса <i>SomeClass::someMethod()</i>;
    - вызов метода объекта <i>$someObject->someMethod()</i>;

    Вызываемая функция/метод класса должны быть подключены до этого вызова или использовать загрузчик.
    <b>Не нужно обрамлять код в открывающий и закрывающий теги PHP</b>.');

define ('PL_CODE_H_INFO_FUNC', 'Исполняемый код');
