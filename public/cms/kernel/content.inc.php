<?php
/**
 * Вывод основного фрейма системы
 *
 * Стартуется сессия для вызываемого события (активность пользователя), выводится персонаж системы,
 * здесь же идет проверка на доступность вызываемого события пользователю.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Core
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

require_once(dirname(__FILE__).'/header.inc.php');

$event_title = sb_init_user_interface();

$_SESSION['sb_event'] = $_GET['event'].(isset($_GET['pm_id']) ? '&pm_id='.$_GET['pm_id'] : '');

echo '
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
        <title>'.$event_title.'</title>
        <meta http-equiv="Content-Type" content="text/html; charset='.SB_CHARSET.'">
        <meta http-equiv="Content-Script-Type" content="text/javascript">
        <style type="text/css">
        img
        {
            behavior: url("'.SB_CMS_JSCRIPT_URL.'/sbFixPng.htc.php");
        }
        </style>
        <link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbContent.css" />
        <script>

            var sb_domain = "'.SB_DOMAIN.'";
            var sb_cms_lang_url = "'.SB_CMS_LANG_URL.'";
            var sb_cms_css_url = "'.SB_CMS_CSS_URL.'";
            var sb_cms_img_url = "'.SB_CMS_IMG_URL.'";
            var sb_cms_content_file = "'.SB_CMS_CONTENT_FILE.'";
            var sb_cms_empty_file = "'.SB_CMS_EMPTY_FILE.'";
            var sb_cms_dialog_file = "'.SB_CMS_DIALOG_FILE.'";
            var sb_cms_modal_dialog_file = "'.SB_CMS_MODAL_DIALOG_FILE.'";

        </script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbAJAX.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbMessageDivs.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbMainMenuEvents.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbDialogs.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbContent.js.php"></script>
    </head>
    <body oncontextmenu="return sbDisCMenu(event);" tabindex="-1" onselectstart="sbCancelSelect(event);" unselectable="on">
    <table cellpadding="0" cellspacing="0" width="100%"><tr><td id="event_title"><nobr>&nbsp;'.$event_title.'&nbsp;</nobr></td></tr></table>
    <div id="event_content" style="z-index:2">';

	$form_html = sb_need_change_password();
	if(trim($form_html) != '')
	{
		echo $form_html;
		echo '</div>';
	}
	else
	{
		$error = sb_init_plugin_interface();
		echo '</div>';

		if (!$error)
		{
		    echo '<div id="sb_msg_div" onclick="sbHideMsgDiv();"><table cellspacing="0" cellpadding="0" width="100%"><tr><td width="1"><img src="'.SB_CMS_IMG_URL.'/blank.gif" id="sb_msg_div_img"></td><td id="sb_msg_div_text"></td></tr></table></div>';
		
		    sb_show_character();
		
		    $all_count = sbPlugins::getUserSetting('sb_toolbar_qicons_count');
		    
		    if ($all_count > 0)
		    {
			    $event_icon = $_SESSION['sbPlugins']->getQuickToolbarIcons($_GET['event']);
			    if ($event_icon)
			    {
			        $menu_event = $event_icon['event'];
			        if (!array_key_exists($menu_event, $_SESSION['sbQuickToolbar']))
			        {
			            unset($event_icon['event']);
			           
			            $num = count($_SESSION['sbQuickToolbar']);
			            if ($num >= $all_count)
			            {
			                while (count($_SESSION['sbQuickToolbar']) >= $all_count)
			                {
			                	array_shift($_SESSION['sbQuickToolbar']);
			                }
			                $_SESSION['sbQuickToolbar'][$menu_event] = $event_icon;
			            }
			            else 
			            {
			                $_SESSION['sbQuickToolbar'][$menu_event] = $event_icon;
			            }
			        }
			        else 
			        {
			        	$res = array();
			        	foreach($_SESSION['sbQuickToolbar'] as $key => $value)
			        	{
			        		if ($key != $menu_event)
			        		{
			        			$res[$key] = $value;
			        		}
			        	}
			        	
			        	$res[$menu_event] = $_SESSION['sbQuickToolbar'][$menu_event];
			        	$_SESSION['sbQuickToolbar'] = $res;
			        }
			        
			        echo '<script>
			                window.parent.navmenu.sbShowQuickToolbar();
			            </script>';
			    }
		    }
		    else 
		    {
		  		$_SESSION['sbQuickToolbar'] = array();
		    }
		}
	}

	// определяем ссылку на справку для модуля
	$help_url = 'http://'.CMS_SITE_NAME.'/training/helps/';
	
	/*$_SESSION['sbPlugins']->getPluginHelp($_GET['event']);
	if ($help_url == '')
	{
	    $help_url = 'http://'.CMS_SITE_NAME.'/training/helps/';
	}*/
	
echo '<script>
            sbResizeEventDiv();
            //sbSetEventHelp(\''.$help_url.'\');
            sbShowPopupMsgDiv();
		</script>
	<div id="disable_div" class="disable"></div>
	</body></html>';

require_once(SB_CMS_KERNEL_PATH.'/footer.inc.php');
?>