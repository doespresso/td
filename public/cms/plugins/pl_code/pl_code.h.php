<?php
require_once(SB_CMS_LANG_PATH.'/pl_code.h.lng.php');
/** @var sbPlugins[] $_SESSION['sbPlugins']  */

if ($_SESSION['sbPlugins']->register('pl_code', PL_CODE_H_PLUGIN_NAME, ''))
{
    $_SESSION['sbPlugins']->addSystemEvent('code_add', 'fCode_getFunc', PL_CODE_H_FUNC);

    $_SESSION['sbPlugins']->addElem(
        PL_CODE_H_FUNC, 'code', 'fCode_Generate',
        'fCode_Info', 'code_add', 800, 240,
        '', '', ''
    );
}