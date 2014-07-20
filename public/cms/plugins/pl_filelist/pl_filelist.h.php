<?php
require_once(SB_CMS_LANG_PATH.'/pl_filelist.h.lng.php');

if ($_SESSION['sbPlugins']->register('pl_filelist', PL_FILELIST_H_PLUGIN_NAME, 'http://www.sbuilder.ru/docs/helps40/file_panel.doc'))
{
	$_SESSION['sbPlugins']->addToMenu(KERNEL_MENU_ADMIN.'>'.PL_FILELIST_H_PLUGIN_NAME, 'init');

	$_SESSION['sbPlugins']->addUserEvent('init', 'fFilelist_Init', 'read', PL_FILELIST_H_TITLE);
	$_SESSION['sbPlugins']->addUserEvent('read', 'fFilelist_Read', 'read');
	$_SESSION['sbPlugins']->addUserEvent('get_files', 'fFilelist_Get_Files', 'read', PL_FILELIST_H_TITLE);

	$_SESSION['sbPlugins']->addUserEvent('edit_file', 'fFilelist_Edit_File', 'files_edit', PL_FILELIST_H_EDIT_TITLE);
	$_SESSION['sbPlugins']->addUserEvent('edit_file_submit', 'fFilelist_Edit_File_Submit', 'files_edit', PL_FILELIST_H_EDIT_TITLE);
	$_SESSION['sbPlugins']->addUserEvent('search_file', 'fFilelist_Search_File', 'files_edit', PL_FILELIST_H_EDIT_TITLE);
	$_SESSION['sbPlugins']->addUserEvent('edit_file_access', 'fFilelist_Edit_File_Access', 'files_folders_access', PL_FILELIST_H_RIGHTS_TITLE);
	$_SESSION['sbPlugins']->addUserEvent('edit_file_access_submit', 'fFilelist_Edit_File_Access_Submit', 'files_folders_access', PL_FILELIST_H_RIGHTS_TITLE);

    $_SESSION['sbPlugins']->addUserEvent('ajax_upload', 'fFilelist_Ajax_Upload', 'files_folders_access');

	$_SESSION['sbPlugins']->addEventsRights('folders_edit', PL_FILELIST_H_FOLDERS_EDIT_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('folders_delete', PL_FILELIST_H_FOLDERS_DELETE_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('folders_rights', PL_FILELIST_H_FOLDERS_RIGHTS_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('files_cut_copy', PL_FILELIST_H_FILES_CUT_COPY_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('files_edit', PL_FILELIST_H_FILES_EDIT_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('files_delete', PL_FILELIST_H_FILES_DELETE_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('folders_upload', PL_FILELIST_H_FOLDERS_UPLOAD_RIGHT);
	$_SESSION['sbPlugins']->addEventsRights('files_folders_access', PL_FILELIST_H_FILES_FOLDERS_ACCESS_RIGHT);

	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_max_upload_size', PL_FILELIST_H_SETTING_MAX_UPLOAD_FILESIZE, 'number', '', '2097152');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_max_upload_width', PL_FILELIST_H_SETTING_MAX_IMAGE_WIDTH, 'number', '', '2000');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_max_upload_height', PL_FILELIST_H_SETTING_MAX_IMAGE_HEIGHT, 'number', '', '2000');

	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_resize_delim', '', '');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_resize', PL_FILELIST_H_SETTING_RESIZE, 'checkbox', '', '0');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_max_resize_width', PL_FILELIST_H_SETTING_MAX_RESIZE_WIDTH, 'number', '', '320');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_max_resize_height', PL_FILELIST_H_SETTING_MAX_RESIZE_HEIGHT, 'number', '', '320');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_resize_quality', PL_FILELIST_H_SETTING_RESIZE_QUALITY, 'number', '', '80');

	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark_delim', '', '');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark', PL_FILELIST_H_SETTING_WATERMARK, 'checkbox', '', '0');

	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark_position', PL_FILELIST_H_SETTING_WATERMARK_POSITION, 'select', $GLOBALS['sb_watermark_positions'], 'BR');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark_opacity', PL_FILELIST_H_SETTING_WATERMARK_OPACITY, 'number', '', '60');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark_margin', PL_FILELIST_H_SETTING_WATERMARK_MARGIN, 'number', '', '10');

	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark_img_delim', '', '');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_watermark_img', PL_FILELIST_H_SETTING_WATERMARK_IMG, 'file', 'jpg gif png', '');

	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_copyright_delim', '', '');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_copyright', PL_FILELIST_H_SETTING_COPYRIGHT, 'string', '', '');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_copyright_color', PL_FILELIST_H_SETTING_COPYRIGHT_COLOR, 'color', '', '#000000');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_copyright_font', PL_FILELIST_H_SETTING_COPYRIGHT_FONT, 'select', $GLOBALS['sb_watermark_fonts'], 'arial.ttf');
	$_SESSION['sbPlugins']->addSetting(PL_FILELIST_H_SETTING, 'sb_files_copyright_size', PL_FILELIST_H_SETTING_COPYRIGHT_SIZE, 'select', $GLOBALS['sb_watermark_sizes'], '11');

	$_SESSION['sbPlugins']->addUserSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_files_num_delim', PL_FILELIST_H_PLUGIN_NAME, '', '');
	$_SESSION['sbPlugins']->addUserSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_files_num_cols', PL_FILELIST_H_SETTING_NUM_COLS, 'number', '', '3');
	$_SESSION['sbPlugins']->addUserSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_files_num_rows', PL_FILELIST_H_SETTING_NUM_ROWS, 'number', '', '10');
	$_SESSION['sbPlugins']->addUserSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_files_truncate_names', PL_FILELIST_H_SETTING_TRUNCATE_NAMES, 'checkbox', '', '0');
	$_SESSION['sbPlugins']->addUserSetting(INDEX_KERNEL_PLUGIN_SETTING, 'sb_files_small_icons', PL_FILELIST_H_SETTING_SMALL_ICONS, 'checkbox', '', '0');
}

$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_files_rights_delim', PL_FILELIST_H_SETTING, '', '', '', 'all', false);
$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_file_rights', PL_FILELIST_H_SETTING_FILE_RIGHTS, 'number', '', '644', 'all', false);
$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_folder_rights', PL_FILELIST_H_SETTING_FOLDER_RIGHTS, 'number', '', '755', 'all', false);
$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_confirm_move_folders', INDEX_KERNEL_SETTING_CONFIRM_MOVE_FOLDERS, 'checkbox', '', '0', 'all', false);
$_SESSION['sbPlugins']->addSetting(INDEX_KERNEL_SETTING, 'sb_folder_restricted', INDEX_KERNEL_SETTING_RESTRICTED_FOLDERS, 'string', '', 'cms', 'all', false);
?>