<?php
function fDumper_Init()
{
	$tab_id = isset($_GET['tab_id']) ? intval($_GET['tab_id']) : -1;

	$layout = new sbLayout(SB_CMS_CONTENT_FILE.'?event=pl_dumper_init'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), '', 'post', '', 'pl_dumper_form', 'enctype="multipart/form-data"');

	$layout->mShowTitle = false;
	$layout->setAutoLoading(SB_CMS_EMPTY_FILE.'?event=pl_dumper_init');

   	$layout->addTab(PL_DUMPER_CREATE_POINT);
   	$layout->addHeader(PL_DUMPER_CREATE_POINT);

   	if ($tab_id == -1)
   	{
   	    $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_level=0 AND cat_ident="pl_pages"');

		echo '<script>
		    var sb_pages_root_cat = '.(isset($res) && $res[0][0] > 0 ? $res[0][0] : '-1').';

			function nextPage(url)
			{
			    sbGetE("sb_tab1").innerHTML = "'.str_replace('"', '\\"', sb_show_message(KERNEL_LOADING, true, 'loading', true)).'";
				sbLoadAsync(url, afterNextPage);
			}

			function afterNextPage(res)
			{
				if(res)
				{
					sbGetE("sb_tab1").innerHTML = res;
				}
			}

			function createPoint()
			{
           		var descr = sbGetE("description_id");
				if(descr.value == "")
				{
					sbShowMsgDiv("'.PL_DUMPER_NO_POINT_DESCRIPTION.'", "warning.png");
					return;
				}

				sbShowDisDiv();
				sbShowMsgDiv("'.PL_DUMPER_CREATING_POINT_WAIT.'", "loading.gif", false);
				sbMsgDiv.onclick = null;

				if(!sbSendFormAsync(sbGetE("pl_dumper_form"), afterCreatePoint))
				{
				    sbShowMsgDiv("'.PL_DUMPER_DUMP_CREATE_ERROR.'", "warning.png");
				    sbHideDisDiv();
				}
			}

			function afterCreatePoint(res)
			{
				if (res == "RELOAD")
				{
					sbShowMsgDiv("'.PL_DUMPER_CREATING_POINT_WAIT.'", "loading.gif", false);
					sbMsgDiv.onclick = null;

					res = sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_create_point_submit&dumper_reload=1", afterCreatePoint);
					return;
				}

   				sbHideDisDiv();

   				if(res == "TRUE")
   				{
					sbGetE("description_id").value = "";
   					sbShowMsgDiv("'.PL_DUMPER_DUMP_CREATE_OK.'", "information.png");
   				}
   				else
   				{
   					sbShowMsgDiv(res, "warning.png");
   				}
   			}

   			function restorePoint()
   			{
   			    sbShowMsgDiv("'.PL_DUMPER_RESTORING_POINT_WAIT.'", "loading.gif", false);
   			    sbMsgDiv.onclick = null;
				sbShowDisDiv();

				if(!sbSendFormAsync(sbGetE("pl_dumper_form"), afterRestorePoint))
				{
					sbShowMsgDiv(" '.PL_DUMPER_RESTORE_ERROR.'", "warning.png");
					sbHideDisDiv();
					return;
				}
			}

			function afterRestorePoint(res)
			{
				if (res == "RELOAD")
				{
					sbShowMsgDiv("'.PL_DUMPER_RESTORING_POINT_WAIT.'", "loading.gif", false);
					sbMsgDiv.onclick = null;
					sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_restore_point_submit&reload=1", afterRestorePoint);
					return;
				}

				if (res == "DOMAINS")
				{
					sbShowModalDialog("'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_dumper_domains", "resizable=1,width=700,height=500", afterAssignDomains);
				    return;
				}

				if (res == "DOMAINS SET")
				{
					setTimeout("sbShowDisDiv();", 100);
					sbShowMsgDiv("'.PL_DUMPER_RESTORING_POINT_WAIT.'", "loading.gif", false);
					sbMsgDiv.onclick = null;
					sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_restore_point_submit&domains=1", afterRestorePoint);
   					return;
   				}

   				var arr = res.split(",");

   				if(arr[0] == "UPDATE")
   				{
   				    sbShowMsgDiv("'.PL_DUMPER_UPDATE_WAIT.' "+"<b>"+arr[1]+"</b>...", "loading.gif", false);
   				    sbMsgDiv.onclick = null;
   				    sbLoadAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_restore_point_submit&update=1", afterRestorePoint);
   					return;
   				}

   				sbHideDisDiv();

   				if(res == "TRUE")
   				{
		            if (sb_pages_root_cat > 0)
		            {
       				    var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_gen_pages&id=" + sb_pages_root_cat + "&subcategs=1";
    	 	            var strAttr = "resizable=0,width=600,height=400";
                        sbShowDialog(strPage, strAttr);
                    }

   					sbShowMsgDiv("'.PL_DUMPER_RESTORE_OK2.'", "information.png");
   				}
   				else
   				{
   					sbShowMsgDiv(res, "warning.png");
   				}
   			}

   			function afterAssignDomains()
   			{
   		 	    if (sbModalDialog.returnValue != "TRUE")
        		{
        			sbShowMsgDiv("'.PL_DUMPER_ERROR_GET_DOMAINS_IN_DUMP.'", "warning.png");
        			return;
        		}

        		afterRestorePoint("DOMAINS SET");
        	}

        	function deleteRestorePoint(pointId, page)
            {
            	var str = pointId == "all" ? "'.PL_DUMPER_RESTORE_POINT_DELETE_ALL_REQUEST.'" : "'.PL_DUMPER_RESTORE_POINT_DELETE_REQUEST.'";

                if(confirm(str))
                {
                    var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_restore_point_delete&id="+pointId);
                    if(res != "")
                    {
                        sbShowMsgDiv(res, "warning.png");
                    }

                    if (pointId == "all")
                    {
                    	tabs.tabItems[1].loaded = 0;
                    	tabs.loadTab(1, true);
                    }
                    else if (res == "")
                    {
                    	nextPage("'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_init&tab_id=1&page_dumper=" + page);
                    }
                }
            }

   			function sbTabCheck(tab)
		    {
		        if (tab == 0)
		        {
		        	sbGetE("pl_dumper_form").action ="'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_create_point_submit";
		            sbGetE("button_create").style.display = "";
		            sbGetE("button_restore").style.display = "none";
		        }
		        else
		        {
		        	tabs.tabItems[1].loaded = 0;
       	            tabs.loadTab(1, true);
       	            sbGetE("pl_dumper_form").action ="'.SB_CMS_EMPTY_FILE.'?event=pl_dumper_restore_point_submit";
		         	sbGetE("button_create").style.display = "none";
		    	    sbGetE("button_restore").style.display = "";
		    	    sbGetE("button_restore").disabled = true;
		    	}

		    	return true;
		    }
       	</script>';
   	}

   	if ($tab_id == 0) // Закладка создание точки восстановления
   	{
   		$html = '<div class="hint_div" style="margin-top: 5px;">'.PL_DUMPER_CREATE_POINT_HINT.'</div>';
    	$layout->addField('', new sbLayoutHTML($html,true));

    	$fld = new sbLayoutTextarea('', 'description', 'description_id', 'style="width:100%;"');
   		$layout->addField('', $fld);

        $fld = new sbLayoutInput('checkbox', '1', 'save_cms', 'save_cms', 'checked="checked"');
        $layout->addField('', new sbLayoutHTML(PL_DUMPER_H_SAVE_CMS. $fld->getField()));
   	}

	$layout->addTab(PL_DUMPER_H_PLUGIN_NAME);
   	$layout->addHeader(PL_DUMPER_H_PLUGIN_NAME);

   	if ($tab_id == 1) // Закладка восстановление системы
	{
       	$dir = $GLOBALS['sbVfs']->scandir('cms/backup/');

       	$pagesCount = 10;  // Кол-во точек восстановления на одной странице
		$points = array(); // Массив точек восстановления

       	// Сканируем папку
       	for($i = count($dir) - 1; $i >= 0; $i--)
       	{
       		if ($GLOBALS['sbVfs']->is_file('cms/backup/'.$dir[$i]) && strpos($dir[$i], 'dump_') === 0 && strpos($dir[$i], '_cms') === false)
       		{
       			$ext = explode('.', $dir[$i]);
       			if (count($ext) != 2)
       				continue;

       			$file = $ext[0];
       			if (!isset($points[$file]))
       				$points[$file] = array();

       			if ($ext[1] == 'txt')
       			{
       				// Описание
       				$points[$file]['descr'] = $GLOBALS['sbVfs']->file_get_contents('cms/backup/' . $dir[$i]);
       			}
       			else
       			{
       				$points[$file]['date'] = str_replace('_', '.', substr($file, 5, 10));
       				$points[$file]['time'] = str_replace('_', ':', substr($file, 16, 8));
       				$points[$file]['ver'] = substr($file, 26, 5);
       				$points[$file]['ver'] = substr($points[$file]['ver'], 0, 1).'.'.substr($points[$file]['ver'], 1);
       			}
       		}
       	}

		function cmp_dates(&$ar1, &$ar2)
        {
            $time1 = sb_datetoint($ar1['date'].' '.$ar1['time']);
            $time2 = sb_datetoint($ar2['date'].' '.$ar2['time']);

            if ($time1 > $time2)
                return -1;
            if ($time1 < $time2)
                return 1;

            return 0;
        }

       	uasort($points, 'cmp_dates');

       	// Настраиваем страницы
       	$num = count($points); // Всего точек восстановления
       	if($num > 0)
       	{
	      	$totalPages = ceil($num / $pagesCount); // Всего страниц
       		// Номер текущей страницы
	    	if(isset($_GET['page_dumper']))
	    	{
	    		$page = min(max(1, intval($_GET['page_dumper'])), $totalPages);
	    		$_GET['page_dumper'] = $page;
	    	}
	       	else
	       	{
	       		$page = 1;
	       	}

	      	require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

	    	$pager = new sbDBPager('dumper', 7, $pagesCount);

	    	$pager->mNumberTemp = '&nbsp;<a href="javascript:nextPage(\'{HREF}\');">{NUMBER}</a>&nbsp;';

	    	$pager->mBeginTemp = '<a href="javascript:nextPage(\'{HREF}\');"><img src="'.SB_CMS_IMG_URL.'/begin.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_BEGIN.'" align="absmiddle"></a>';
	        $pager->mBeginTempDisabled = '<img src="'.SB_CMS_IMG_URL.'/begin_gray.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_BEGIN.'" align="absmiddle">';

	        $pager->mPrevTemp = '<a href="javascript:nextPage(\'{HREF}\');"><img src="'.SB_CMS_IMG_URL.'/previous.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_PREV.'" align="absmiddle"></a>';
	        $pager->mPrevTempDisabled = '<img src="'.SB_CMS_IMG_URL.'/previous_gray.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_PREV.'" align="absmiddle">';

	        $pager->mNextTemp = '<a href="javascript:nextPage(\'{HREF}\');"><img src="'.SB_CMS_IMG_URL.'/next.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_NEXT.'" align="absmiddle"></a>';
	        $pager->mNextTempDisabled = '<img src="'.SB_CMS_IMG_URL.'/next_gray.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_NEXT.'" align="absmiddle">';

	        $pager->mEndTemp =  '<a href="javascript:nextPage(\'{HREF}\');"><img src="'.SB_CMS_IMG_URL.'/end.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_END.'" align="absmiddle"></a>';
	        $pager->mEndTempDisabled = '<img src="'.SB_CMS_IMG_URL.'/end_gray.gif" width="14" height="15" border="0" alt="'.KERNEL_PAGER_END.'" align="absmiddle">';

	    	$pager->mNumElemsAll = $num;

       		$layout->addField('', new sbLayoutHTML('<script>sbGetE("button_restore").disabled = false;</script><div class="hint_div" style="margin-top: 5px;">'.PL_DUMPER_RESTORE_POINT_HINT.'</div>'));

    		$labels = array('', PL_DUMPER_CREATION_DATE, PL_DUMPER_CREATION_TIME, PL_DUMPER_CREATION_VER, PL_DUMPER_CREATION_DESCRIPTION, '');
        	$values = array();

        	$from = ($page - 1) * $pagesCount; // номер точки восстановления с которой нужно начать выводить список
        	$to = min($num, $from + $pagesCount);
        	$i = 0;
        	foreach($points as $file => $point)
       		{
       			if ($i < $from)
       			{
       				$i++;
       				continue;
       			}
       			if ($i >= $to)
       				break;

       			$descr = (isset($point['descr']) && $point['descr'] != '') ? $point['descr'] : PL_DUMPER_RESTORE_POINT_NO_DESCR;

       		    if (isset($point['date']))
       		    {
       				$values[] = array('<input type="radio" id="restore_point'.$i.'" name="restore_point" value="'.$file.'">',
       							  '<label for="restore_point'.$i.'">'.$point['date'].'</label>',
       							  '<label for="restore_point'.$i.'">'.$point['time'].'</label>',
       							  '<label for="restore_point'.$i.'">'.$point['ver'].'</label>',
       							  '<div class="hint_div">'.$descr.'</div>',
       							  '<img class="button" src="'.SB_CMS_IMG_URL.'/files_drop.png" width="20" height="20" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" alt="'.PL_DUMPER_DELETE_RESORE_POINT.'" onclick="deleteRestorePoint(\''.$file.'\', '.$page.');">'
       							  );
       		    }
       		    else
       		    {
       		    	$values[] = array('', '', '', '', '<div class="hint_div">'.$descr.'<br>'.PL_DUMPER_RESTORE_POINT_CORRUPTED.'</div>', '<img class="button" src="'.SB_CMS_IMG_URL.'/files_drop.png" width="20" height="20" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" alt="'.PL_DUMPER_DELETE_RESORE_POINT.'" onclick="deleteRestorePoint(\''.$file.'\', '.$page.');">');
       		    }

       			$i++;
       		}

     		$field = new sbLayoutTable($labels, $values);
     		$field->mWidth = array('20', '120', '120', '120', '', '20');
     		$field->mAlign = array('', 'center', 'center', 'center', 'left', '');
     		$field->mShowTable = true;
     		$layout->addField('', $field);

     		$html = '<a href="javascript:deleteRestorePoint(\'all\');">[ '.PL_DUMPER_RESTORE_POINT_DELETE_ALL.' ]</a>';
     		$layout->addField('', new sbLayoutHTML($html), '', 'align = "right"');

     		$pages = $pager->show();

     		if ($pages != '')
     		{
     			$layout->addField('', new sbLayoutDelim());
	        	$layout->addField('', new sbLayoutHTML('<center>'.$pages.'</center>'));
     		}
    	}
    	else
    	{
    		$layout->addField('', new sbLayoutHTML('<script>sbGetE("button_restore").disabled = true;</script>'.sb_show_message(PL_DUMPER_RESTORE_POINT_EMPTY, true, 'information', true).'<br><br>'));
    	}
	}

	$layout->addButton('button', PL_DUMPER_CREATE_POINT_BTN, '', 'button_create', 'onclick = "createPoint();"');
	$layout->addButton('button', PL_DUMPER_RESTORE_POINT_BTN, '', 'button_restore', 'onclick = "restorePoint();"');

   	$layout->show();
}

function fDumper_Create_Point()
{
	require_once(SB_CMS_LIB_PATH.'/sbDumper.inc.php');

	if (isset($_GET['dumper_reload']) && $_GET['dumper_reload'] == 1)
	{
		$dumper = new sbDumper(100, true);
	}
	else
	{
		$dumper = new sbDumper();
	}

	if(!isset($_GET['dumper_reload']))
	{
		if (isset($_POST['dumper_point_description']) && $_POST['dumper_point_description'] != '')
		{
			echo PL_DUMPER_NO_POINT_DESCRIPTION;
			return;
		}

		$time = time();
		$file = 'dump_'.sb_date('d_m_Y_H_i_s_', $time);

		$cur_update_id = '0' . str_replace('.', '', CMS_DISTR_VERSION);
		
		$res = sql_query('SELECT MAX(u_update_id) FROM sb_updates');
		if ($res)
		{
			list($update_id) = $res[0];
		
			if (!empty($update_id) && $update_id > $cur_update_id)
			{
				$cur_update_id = $update_id;
			}
		}

		$file .= $cur_update_id;

		$file_sql = 'cms/backup/'.$file.'.sql';
		$file_zip = 'cms/backup/'.$file.'.zip';
		$file_txt = 'cms/backup/'.$file.'.txt';
        $file_cms_zip = 'cms/backup/'.$file.'_cms.zip';
        $bkpFolder = 'cms/backup/'.$file;
		$_SESSION['PL_DUMPER_FILE_TXT'] = $file_txt;

		$description = nl2br(strip_tags(trim($_POST['description'])));
		$GLOBALS['sbVfs']->file_put_contents($file_txt, $description);

		if ($dumper->zlibAvailable)
		{
			$dumper->createDump(SB_DUMPER_DUMP_FULL, SB_DUMPER_DUMP_SQL, $file_sql, true, $file_zip);
            if(isset($_POST['save_cms']) && $_POST['save_cms'] == 1)
            {
                $dumper->createDumpCMS(true, $file_cms_zip);
            }
		}
		else
		{
			$dumper->createDump(SB_DUMPER_DUMP_FULL, SB_DUMPER_DUMP_SQL, $file_sql);
            if(isset($_POST['save_cms']) && $_POST['save_cms'] == 1)
            {
                $dumper->createDumpCMS(false, $bkpFolder);
            }
		}
	}

	if (!$dumper->errorFlag)
	{
		if (!$dumper->isNeedReload())
		{
			echo 'TRUE';
			sb_add_system_message(PL_DUMPER_DUMP_CREATE_OK);
		}
		else
		{
			echo 'RELOAD';
		}
	}
	else
	{
		if($dumper->packError)
		{
			echo PL_DUMPER_CREATE_POINT_PACK_ERROR;
		}
		else
		{
            //TODO Добавить удаление всех файлов
			if (isset($_SESSION['PL_DUMPER_FILE_TXT']) && trim($_SESSION['PL_DUMPER_FILE_TXT']) != '' && $GLOBALS['sbVfs']->is_file($_SESSION['PL_DUMPER_FILE_TXT']))
            {
				$GLOBALS['sbVfs']->delete($_SESSION['PL_DUMPER_FILE_TXT']);
                $sql = sb_str_replace('.txt', '.sql', $_SESSION['PL_DUMPER_FILE_TXT']);
                if(trim($sql != '') && $GLOBALS['sbVfs']->is_file($sql))
                {
                    $GLOBALS['sbVfs']->delete($sql);
                }

                $zip = sb_str_replace('.txt', '.zip', $_SESSION['PL_DUMPER_FILE_TXT']);
                if(trim($zip != '') && $GLOBALS['sbVfs']->is_file($zip))
                {
                    $GLOBALS['sbVfs']->delete($zip);
                }

                $zip_cms = sb_str_replace('.txt', '_cms.zip', $_SESSION['PL_DUMPER_FILE_TXT']);
                if(trim($zip_cms != '') && $GLOBALS['sbVfs']->is_file($zip_cms))
                {
                    $GLOBALS['sbVfs']->delete($zip_cms);
                }

                $cms_dir = sb_str_replace('.txt', '', $_SESSION['PL_DUMPER_FILE_TXT']);
                if(trim($cms_dir != '') && $GLOBALS['sbVfs']->is_dir($cms_dir))
                {
                    $GLOBALS['sbVfs']->delete($cms_dir);
                }
            }

			echo PL_DUMPER_DUMP_CREATE_ERROR;
		}

		sb_add_system_message($dumper->errorStr, SB_MSG_WARNING);
		return;
	}
}

function fDumper_Restore_Point_Delete()
{
    if (!isset($_GET['id']) || (strpos($_GET['id'], 'dump_') !== 0 && $_GET['id'] != 'all'))
    {
    	echo PL_DUMPER_RESTORE_POINT_DELETE_ERROR;
        return;
    }

	$error = false;   // флаг ошибки

	if ($_GET['id'] == 'all')
	{
		$dir = $GLOBALS['sbVfs']->scandir('cms/backup/');
		foreach($dir as $value)
		{
		    if (($GLOBALS['sbVfs']->is_file('cms/backup/'.$value) || $GLOBALS['sbVfs']->is_dir('cms/backup/'.$value)) && strpos($value, 'dump_') === 0)
			    $error = !$GLOBALS['sbVfs']->delete('cms/backup/'.$value);
		}
	}
	else
	{
		$file_zip = 'cms/backup/'.$_GET['id'].'.zip';
		$file_sql = 'cms/backup/'.$_GET['id'].'.sql';
		$file_txt = 'cms/backup/'.$_GET['id'].'.txt';
        $file_zip_cms = 'cms/backup/'.$_GET['id'].'_cms.zip';
        $dir_name = 'cms/backup/'.$_GET['id'];

		if ($GLOBALS['sbVfs']->exists($file_sql) && !$GLOBALS['sbVfs']->delete($file_sql))
			$error = true;

		if ($GLOBALS['sbVfs']->exists($file_zip) && !$GLOBALS['sbVfs']->delete($file_zip))
			$error = true;

		if ($GLOBALS['sbVfs']->exists($file_txt) && !$GLOBALS['sbVfs']->delete($file_txt))
			$error = true;

        if ($GLOBALS['sbVfs']->exists($file_zip_cms) && !$GLOBALS['sbVfs']->delete($file_zip_cms))
			$error = true;

        if ($GLOBALS['sbVfs']->exists($dir_name) && !$GLOBALS['sbVfs']->delete($dir_name))
			$error = true;
	}

	if($error && $_GET['id'] != 'all')
	{
		sb_add_system_message(PL_DUMPER_RESTORE_POINT_DELETE_SYSTEMLOG_ERROR, SB_MSG_WARNING);
		echo PL_DUMPER_RESTORE_POINT_DELETE_ERROR;
		return;
	}

	if ($_GET['id'] == 'all')
	{
		sb_add_system_message(PL_DUMPER_RESTORE_POINT_DELETE_ALL_OK);
	}
	else
	{
		sb_add_system_message(sprintf(PL_DUMPER_RESTORE_POINT_DELETE_OK, $_GET['id']));
	}
}

function fDumper_Restore_Point()
{
	if (isset($_GET['reload']))
	{
		$dumper = new sbDumper(100, true);
	}
	else
	{
		$dumper = new sbDumper();
	}

	$dumper->replaceInfo = isset($_SESSION['PL_DUMPER_REPLACE_INFO']) ? $_SESSION['PL_DUMPER_REPLACE_INFO'] : array();;
	if (isset($_GET['domains']))
	{
		if (isset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']) && trim($_SESSION['PL_DUMPER_FILE_TO_RESTORE']) != '')
		{
			$dumper->loadDumpFile('cms/backup/'.$_SESSION['PL_DUMPER_FILE_TO_RESTORE']);
		}
		else
		{
			if (isset($_SESSION['PL_DUMPER_DOMAINS']))
			    unset($_SESSION['PL_DUMPER_DOMAINS']);

			if (isset($_SESSION['PL_DUMPER_REPLACE_INFO']))
				unset($_SESSION['PL_DUMPER_REPLACE_INFO']);

			if (isset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']))
	    		unset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']);

	    	if (isset($_SESSION['PL_DUMPER_SESSION']))
	    		unset($_SESSION['PL_DUMPER_SESSION']);

	    	echo PL_DUMPER_RESTORE_ERROR;
    		return;
		}
	}
	elseif (!isset($_GET['reload']) && !isset($_GET['update']))
	{
		if (isset($_SESSION['PL_DUMPER_DOMAINS']))
			unset($_SESSION['PL_DUMPER_DOMAINS']);

		if (isset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']))
    		unset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']);

		if (isset($_SESSION['PL_DUMPER_REPLACE_INFO']))
			unset($_SESSION['PL_DUMPER_REPLACE_INFO']);

		if(isset($_SESSION['PL_DUMPER_UPDATES_INFO']))
			unset($_SESSION['PL_DUMPER_UPDATES_INFO']);

		if(isset($_SESSION['PL_DUMPER_UPDATES_POS']))
			unset($_SESSION['PL_DUMPER_UPDATES_POS']);

		if (isset($_SESSION['PL_DUMPER_SESSION']))
	    	unset($_SESSION['PL_DUMPER_SESSION']);

		if (!isset($_POST['restore_point']))
		{
			echo PL_DUMPER_NO_POINT_SELECTED;
			return;
		}

		$_POST['restore_point'] = trim(preg_replace('/[^0-9a-zA-Z_\.]+/'.SB_PREG_MOD, '', $_POST['restore_point']));
		if ($_POST['restore_point'] == '')
		{
			echo PL_DUMPER_NO_POINT_SELECTED;
			return;
		}

        //Если нет архива с файлами CMS, то проверяем соответствие версий
        $cmsRestore = '';
        if($GLOBALS['sbVfs']->exists('cms/backup/' . $_POST['restore_point'] . '_cms.zip') && $GLOBALS['sbVfs']->is_file('cms/backup/' . $_POST['restore_point'] . '_cms.zip'))
        {
            $cmsRestore = $_POST['restore_point'].'_cms.zip';
        }
        elseif($GLOBALS['sbVfs']->is_dir('cms/backup/' . $_POST['restore_point']))
        {
            $cmsRestore = $_POST['restore_point'];
        }

        if ($cmsRestore == '')
        {
            $u_update_id = substr($_POST['restore_point'], 25, 6);
            $cur_update_id = '0' . str_replace('.', '', CMS_DISTR_VERSION);

            $res = sql_query('SELECT MAX(u_update_id) FROM sb_updates');
            if ($res)
            {
                list($update_id) = $res[0];

                if (!empty($update_id) && $update_id > $cur_update_id)
                {
                    $cur_update_id = $update_id;
                }
            }

            if ($u_update_id > $cur_update_id)
            {
                echo sprintf(PL_DUMPER_RESTORE_ERROR_VERSION, substr($u_update_id, 1, 1) . '.' . substr($u_update_id, 2), substr($cur_update_id, 1, 1) . '.' . substr($cur_update_id, 2));
                return;
            }
            elseif ($cur_update_id > $u_update_id)
            {
                $res = sql_param_query('SELECT u_update_id FROM sb_updates WHERE u_update_id > ? ORDER BY u_update_id ASC', $u_update_id);
                if (!$res)
                {
                    $res = array();
                    $start = max(4001, intval($u_update_id) + 1);
                    $end = intval($cur_update_id);

                    $i = 0;
                    while ($end >= $start)
                    {
                        $res[$i] = array('0' . $start);
                        $start++;
                        $i++;
                    }
                }

                $_SESSION['PL_DUMPER_UPDATES_INFO'] = $res;
                $_SESSION['PL_DUMPER_UPDATES_POS'] = 0;
            }
        }

		$fileName = $_POST['restore_point'];
		if ($GLOBALS['sbVfs']->exists('cms/backup/'.$fileName.'.zip'))
			$fileName .= '.zip';
		else
			$fileName .= '.sql';

		$domains = $dumper->checkDomains('cms/backup/'.$fileName);

		if($domains === false)
		{
			echo $dumper->errorStr;
			return;
		}

		$_SESSION['PL_DUMPER_FILE_TO_RESTORE'] = $fileName;

		if ($domains === true)
		{
            if($cmsRestore != '')
            {
                $dumper->restoreDumpCMS($cmsRestore);
            }
            
            $dumper->loadDumpFile('cms/backup/' . $fileName);
            if ($dumper->errorFlag)
            {
                echo PL_DUMPER_RESTORE_ERROR;
                sb_add_system_message(PL_DUMPER_RESTORE_SYSTEMLOG_ERROR . '<br /><br /><span style="color:red;">' . KERNEL_ERROR_MYSQL . '</span>:<br />' . $dumper->errorStr, SB_MSG_WARNING);
                return;
            }
		}
		elseif ($domains !== false)
		{
			$_SESSION['PL_DUMPER_DOMAINS'] = $domains;
			echo 'DOMAINS';
			return;
		}
	}

	if ($dumper->errorFlag)
	{
		echo PL_DUMPER_RESTORE_ERROR;
		sb_add_system_message(PL_DUMPER_RESTORE_SYSTEMLOG_ERROR . '<br /><br /><span style="color:red;">' . KERNEL_ERROR_MYSQL . '</span>:<br />' . $dumper->errorStr, SB_MSG_WARNING);
		return;
	}

	if ($dumper->isNeedReload())
	{
		echo 'RELOAD';
		return;
	}

	if (isset($_SESSION['PL_DUMPER_UPDATES_INFO']) && isset($_SESSION['PL_DUMPER_UPDATES_POS']) &&
	    isset($_SESSION['PL_DUMPER_UPDATES_INFO'][intval($_SESSION['PL_DUMPER_UPDATES_POS'])]))
	{
		list($u_update_id) = $_SESSION['PL_DUMPER_UPDATES_INFO'][intval($_SESSION['PL_DUMPER_UPDATES_POS'])];

		if (fUpdate_Dump($u_update_id))
		{
			echo 'UPDATE,'.$u_update_id;
			$_SESSION['PL_DUMPER_UPDATES_POS']++;
		}
		else
		{
			echo sprintf(PL_DUMPER_RESTORE_ERROR_LOADING_UPDATE, substr($u_update_id, 1, 1).'.'.substr($u_update_id, 2));
		}

		return;
	}

    // Очищаем cms/cache/
    $mlocal = $GLOBALS['sbVfs']->mLocal;
    $GLOBALS['sbVfs']->mLocal = true;
    if($GLOBALS['sbVfs']->exists(SB_CMS_CACHE_PATH) && $GLOBALS['sbVfs']->is_dir(SB_CMS_CACHE_PATH))
    {
        $GLOBALS['sbVfs']->delete(SB_CMS_CACHE_PATH);
    }
    $GLOBALS['sbVfs']->mLocal = $mlocal;
    unset($mlocal);

	if (!$dumper->isNeedReload())
	{
		echo 'TRUE';

		$date = str_replace('_', '.', substr($_SESSION['PL_DUMPER_FILE_TO_RESTORE'], 5, 10));
       	$time = str_replace('_', ':', substr($_SESSION['PL_DUMPER_FILE_TO_RESTORE'], 16, 8));
		sb_add_system_message(sprintf(PL_DUMPER_RESTORE_OK, $date, $time ) );

		$tables = $GLOBALS['sbSql']->showTables();

		if (count($tables) > 0)
		{
		    $sitemap_table_ids = array();
            $plugins_table_ids = array();
            $search_table_ids = array();

            foreach ($tables as $value)
            {
                $matches = array();
                if (preg_match('/^sb_sitemap_pages_([0-9]+)$/i', $value, $matches))
                {
                    $sitemap_table_ids[] = $matches[1];
                }
                elseif (preg_match('/^sb_search_pages_([0-9]+)$/i', $value, $matches))
                {
                    $search_table_ids[] = $matches[1];
                }
                elseif (preg_match('/^sb_plugins_([0-9]+)$/i', $value, $matches))
                {
                    $plugins_table_ids[] = $matches[1];
                }
            }

            if (count($plugins_table_ids) > 0)
            {
                $plugins_table_real_ids = array();

        		// удаляем таблицы sb_plugins_... для несуществующих модулей
        		$res = sql_query('SELECT pm_id FROM sb_plugins_maker');
        		if ($res)
        		{
        		    foreach ($res as $value)
        		    {
        		        $plugins_table_real_ids[] = $value[0];
        		    }
        		}

        		$plugins_table_ids = array_diff($plugins_table_ids, $plugins_table_real_ids);
            }

            if (count($search_table_ids) > 0)
            {
                $search_table_real_ids = array();

                // удаляем таблицы sb_search_pages... для несуществующих модулей
                $res = sql_query('SELECT ss_id FROM sb_search');
                if ($res)
                {
                    foreach ($res as $value)
                    {
                        $search_table_real_ids[] = $value[0];
                    }
                }

                $search_table_ids = array_diff($search_table_ids, $search_table_real_ids);
            }

            if (count($sitemap_table_ids) > 0)
            {
                $sitemap_table_real_ids = array();

                // удаляем таблицы sb_sitemap_pages... для несуществующих модулей
                $res = sql_query('SELECT sm_id FROM sb_sitemap');
                if ($res)
                {
                    foreach ($res as $value)
                    {
                        $sitemap_table_real_ids[] = $value[0];
                    }
                }

                $sitemap_table_ids = array_diff($sitemap_table_ids, $sitemap_table_real_ids);
            }

            if (count($plugins_table_ids) > 0)
            {
                foreach ($plugins_table_ids as $value)
                {
                    sql_query('DROP TABLE sb_plugins_'.$value);
                }
            }

            if (count($search_table_ids) > 0)
            {
                foreach ($search_table_ids as $value)
                {
                    sql_query('DROP TABLE sb_search_pages_'.$value);
                }
            }

            if (count($sitemap_table_ids) > 0)
            {
                foreach ($sitemap_table_ids as $value)
                {
                    sql_query('DROP TABLE sb_sitemap_pages_'.$value);
                }
            }
		}

		if (isset($_SESSION['PL_DUMPER_DOMAINS']))
			unset($_SESSION['PL_DUMPER_DOMAINS']);

		if (isset($_SESSION['PL_DUMPER_REPLACE_INFO']))
			unset($_SESSION['PL_DUMPER_REPLACE_INFO']);

		if (isset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']))
    		unset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']);

		if(isset($_SESSION['PL_DUMPER_UPDATES_INFO']))
			unset($_SESSION['PL_DUMPER_UPDATES_INFO']);

		if(isset($_SESSION['PL_DUMPER_UPDATES_POS']))
			unset($_SESSION['PL_DUMPER_UPDATES_POS']);

		return;
	}
}

function fDumper_Domains()
{
	if(isset($_SESSION['PL_DUMPER_DOMAINS']) && is_array($_SESSION['PL_DUMPER_DOMAINS']))
	{
		$domains = $_SESSION['PL_DUMPER_DOMAINS'];
	}
	else
	{
		if (isset($_SESSION['PL_DUMPER_DOMAINS']))
			unset($_SESSION['PL_DUMPER_DOMAINS']);

		if (isset($_SESSION['PL_DUMPER_REPLACE_INFO']))
			unset($_SESSION['PL_DUMPER_REPLACE_INFO']);

		if (isset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']))
    		unset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']);

		echo '<script>
				sbCloseDialog();
		</script>';

		return;
	}

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	echo '<br><br>';

	$layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_dumper_domains_submit', 'thisDialog', 'post', '');
	$layout->addHeader(PL_DUMPER_CHECK_DOMAINS);
    $layout->addField('', new sbLayoutHTML('<div class="hint_div" style="margin-top: 5px;">'.PL_DUMPER_CHECK_DOMAINS_HINT.'</div>', true));

    $i = 0;
	foreach ($GLOBALS['sb_domains'] as $key => $value)
	{
		if ($key == SB_COOKIE_DOMAIN && SB_PORT != '80')
		{
			$key .= ':'.SB_PORT;
		}

		$fld = new sbLayoutSelect($domains, 'domains['.$key.']');
		$fld->mSelOptions = array($i);

		$layout->addField($key, $fld);

		$i++;
	}

	$layout->addButton('submit', KERNEL_APPLY);
	$layout->show();

	return;
}

function fDumper_Domains_Submit()
{
	if(!isset($_SESSION['PL_DUMPER_DOMAINS']) || !is_array($_SESSION['PL_DUMPER_DOMAINS']) ||
	   !isset($_POST['domains']) || !is_array($_POST['domains']))
	{
		if (isset($_SESSION['PL_DUMPER_DOMAINS']))
			unset($_SESSION['PL_DUMPER_DOMAINS']);

		if (isset($_SESSION['PL_DUMPER_REPLACE_INFO']))
			unset($_SESSION['PL_DUMPER_REPLACE_INFO']);

		if (isset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']))
    		unset($_SESSION['PL_DUMPER_FILE_TO_RESTORE']);

		echo '<script>
			sbCloseDialog();
		</script>';

		return;
	}

	$domains = array();
	foreach ($_POST['domains'] as $key => $value)
	{
		if (!isset($domains[$_SESSION['PL_DUMPER_DOMAINS'][$value]]))
		{
			$domains[$_SESSION['PL_DUMPER_DOMAINS'][$value]] = $key;
			$domains['www.'.$key] = $key;

			$pos = strpos($_SESSION['PL_DUMPER_DOMAINS'][$value], ':');
	   	    if ($pos !== false)
	   	    {
	   			$domain = substr($_SESSION['PL_DUMPER_DOMAINS'][$value], 0, $pos);
	   			$domains[$domain] = $key;
	   			$domains['www.'.$key] = $key;
	   		}
		}
	}

	if (isset($_SESSION['PL_DUMPER_REPLACE_INFO']))
		$_SESSION['PL_DUMPER_REPLACE_INFO'] = array_merge($_SESSION['PL_DUMPER_REPLACE_INFO'], $domains);
	else
		$_SESSION['PL_DUMPER_REPLACE_INFO'] = $domains;

	unset($_SESSION['PL_DUMPER_DOMAINS']);

	echo '<script>
		sbReturnValue("TRUE");
	</script>';
}
?>