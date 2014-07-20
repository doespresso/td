<?php

require_once(SB_CMS_LANG_PATH.'/pl_dumper.h.lng.php');

if ($_SESSION['sbPlugins']->register('pl_dumper', PL_DUMPER_H_PLUGIN_NAME, 'http://www.sbuilder.ru/docs/helps40/pl_backup.doc'))
{
	$_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_ADMIN.'>'.PL_DUMPER_H_PLUGIN_NAME, 'init');

	$_SESSION['sbPlugins']->addUserEvent('init', 'fDumper_Init', 'read', PL_DUMPER_H_PLUGIN_NAME);
	$_SESSION['sbPlugins']->addUserEvent('create_point_submit', 'fDumper_Create_Point', 'read', '');
	$_SESSION['sbPlugins']->addUserEvent('restore_point_submit', 'fDumper_Restore_Point', 'read', '');
	$_SESSION['sbPlugins']->addUserEvent('domains', 'fDumper_Domains', 'read', PL_DUMPER_H_PLUGIN_NAME);
	$_SESSION['sbPlugins']->addUserEvent('domains_submit', 'fDumper_Domains_Submit', 'read', PL_DUMPER_H_PLUGIN_NAME);
	$_SESSION['sbPlugins']->addUserEvent('restore_point_delete', 'fDumper_Restore_Point_Delete', 'read', '');

    $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_autobackup_delim', PL_DUMPER_H_PLUGIN_NAME, '', '', '', 'all', false);
    $_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_autobackup_count', INDEX_KERNEL_SETTING_MAX_AUTOBCKP, 'number', '', '0', 'all', false);
}

$_SESSION['sbPlugins']->addToCron('pl_dumper/cron/pl_dumper.php', 'fDumper_Cron_Create_Point', PL_DUMPER_H_CRON_TITLE, PL_DUMPER_H_CRON_DESCR);
?>