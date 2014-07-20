<?php
/**
 * @var sbVfs $GLOBALS['sbVfs']
 */
function fDumper_Cron_Create_Point()
{
	require_once(SB_CMS_LIB_PATH.'/sbDumper.inc.php');
		
	$dumper = new sbDumper();
	$dumper->unlimitTime = true;

	$time = time();
    $file = 'dump_'.sb_date('d_m_Y_H_i_s_', $time);
			
	$res = sql_param_query('SELECT COUNT(*) FROM sb_updates WHERE u_setup_date <= ?d ORDER BY u_setup_date', $time);
			
	if (!$res)
	{
		return;
	}
			
	list($count) = $res[0];
			
	if ($count > 0)
	{
		$res = sql_param_query('SELECT u_update_id FROM sb_updates WHERE u_setup_date <= ?d ORDER BY u_setup_date DESC LIMIT 1', $time);
				
		if(!$res)
		{
			return;	
		}
				
		list($u_update_id) = $res[0];
	}
	else
	{
		$u_update_id = '0'.str_replace('.', '', CMS_DISTR_VERSION);
	}

	$file .= $u_update_id;
		
	$file_sql = 'cms/backup/'.$file.'.sql';
	$file_zip = 'cms/backup/'.$file.'.zip';
	$file_txt = 'cms/backup/'.$file.'.txt';		

	$GLOBALS['sbVfs']->file_put_contents($file_txt, KERNEL_PROG_PL_DUMPER_CRON_DESCR);

	if ($dumper->zlibAvailable)
	{
		$dumper->createDump(SB_DUMPER_DUMP_FULL, SB_DUMPER_DUMP_SQL, $file_sql, true, $file_zip, '', false, '', false);
	}
	else
	{
		$dumper->createDump(SB_DUMPER_DUMP_FULL, SB_DUMPER_DUMP_SQL, $file_sql);
	}

	if (!$dumper->errorFlag)
	{
		sb_add_system_message(KERNEL_PROG_PL_DUMPER_CRON_CREATE_OK);
		// ищем совпадение файлов по маске
        $maxBackup = sbPlugins::getSetting('sb_autobackup_count');
        $dumpsList = 'cms/backup/autodump.txt';
        if ($maxBackup > 0) {
            if (!$GLOBALS['sbVfs']->is_file($dumpsList)) {
                $GLOBALS['sbVfs']->file_put_contents($dumpsList, '');
            } else {
                $oldPoints = explode(';', $GLOBALS['sbVfs']->file_get_contents($dumpsList));
            }

            if ($dumper->zlibAvailable) {
                $newPoints[] = $file_zip . '^^' . $file_txt;
            } else {
                $newPoints[] = $file_sql . '^^' . $file_txt;
            }

            $i = 1;
            if (!empty($oldPoints)) {
                foreach ($oldPoints as $point) {
                    if ($i < $maxBackup) {
                        $newPoints[] = $point;
                        $i++;
                    } else {
                        $pointsToDelete = explode('^^', $point);
                        if ($GLOBALS['sbVfs']->is_file($pointsToDelete[0])) {
                            $GLOBALS['sbVfs']->delete($pointsToDelete[0]);
                        }
                        if ($GLOBALS['sbVfs']->is_file($pointsToDelete[1])) {
                            $GLOBALS['sbVfs']->delete($pointsToDelete[1]);
                        }
                    }
                }
            }

            $GLOBALS['sbVfs']->file_put_contents($dumpsList, implode(';', $newPoints));
        }
				
	}
	else
	{
		if($dumper->packError)
		{
			sb_add_system_message(KERNEL_PROG_PL_DUMPER_CRON_PACK_ERROR, SB_MSG_WARNING);
		}
		else
		{
			if ($GLOBALS['sbVfs']->is_file($file_txt))
			{
				$GLOBALS['sbVfs']->delete($file_txt);
			}
			
			sb_add_system_message(KERNEL_PROG_PL_DUMPER_CRON_CREATE_ERROR, SB_MSG_WARNING);
		}

		sb_add_system_message($dumper->errorStr, SB_MSG_WARNING);
		return;
	}
}
