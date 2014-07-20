<?php
define('SB_SITEMAP_PAGE_ERROR', 0);
define('SB_SITEMAP_PAGE_OK', 1);

/**
 * Запуск задания индексации из планировщика
 *
 * @param int $sm_id Идентификатор настроек индексации.
 */
function fSitemap_Cron_Refresh($sm_id)
{
	@set_time_limit(0);
	//	взять из базы параметры

	$query = sql_query('SELECT sm_id, sm_domain, sm_settings FROM sb_sitemap WHERE sm_id = ?', $sm_id);
	if($query)
	{
		list($sm_id, $domain, $arr_settings) = $query[0];
		$arr_settings = unserialize($arr_settings);

		//	поместить в глобальный массив установки
		if (!isset($GLOBALS['sb_sitemap_settings']) || !is_array($GLOBALS['sb_sitemap_settings']))
			$GLOBALS['sb_sitemap_settings'] = array();

		$GLOBALS['sb_sitemap_settings'][$sm_id] = array();
		//	домены
		$GLOBALS['sb_sitemap_settings'][$sm_id]['domain'] = $domain;
		//	порт
		$GLOBALS['sb_sitemap_settings'][$sm_id]['port'] = isset($arr_settings['port']) && trim($arr_settings['port']) != '' ? $arr_settings['port'] : '80';
		//	протокол
		$GLOBALS['sb_sitemap_settings'][$sm_id]['scheme'] = isset($arr_settings['scheme']) && trim($arr_settings['scheme']) != '' ? $arr_settings['scheme'] : 'http';
		//	недопустимые параметры в URL
		$GLOBALS['sb_sitemap_settings'][$sm_id]['words_url'] = explode(' ', $arr_settings['impermissible_params']);
		//	список запрещенных директорий
		$GLOBALS['sb_sitemap_settings'][$sm_id]['dirs'] = explode(' ', $arr_settings['dirs_not_allow']);
		//	стартовая страница
		$GLOBALS['sb_sitemap_settings'][$sm_id]['indexing_start_page'] = '';
		//	приоритет ссылки по умолчанию
		$GLOBALS['sb_sitemap_settings'][$sm_id]['priority_default'] = (isset($arr_settings['priority_default']) ? $arr_settings['priority_default'] : '0.5');
		//	частота обновления по умолчанию
		$GLOBALS['sb_sitemap_settings'][$sm_id]['freq'] = (isset($arr_settings['freq']) ? $arr_settings['freq'] : 'weekly');
		//	ручное модерирование новых ссылок.
		$GLOBALS['sb_sitemap_settings'][$sm_id]['manual_moderate'] = (isset($arr_settings['manual_moderate']) ? $arr_settings['manual_moderate'] : 0);

		if (isset($arr_settings['indexing_start_page']) && trim($arr_settings['indexing_start_page']) != '')
		{
			if (sb_stripos($arr_settings['indexing_start_page'], 'http://') !== false || sb_stripos($arr_settings['indexing_start_page'], 'https://') !== false)
			{
				$arr_settings['indexing_start_page'] = sb_str_replace(array('http://', 'https://'), '', $arr_settings['indexing_start_page']);
				$pos = sb_strpos($arr_settings['indexing_start_page'], '/');
				if ($pos !== false)
				{
					$arr_settings['indexing_start_page'] = sb_substr($arr_settings['indexing_start_page'], $pos);
				}
			}

			$GLOBALS['sb_sitemap_settings'][$sm_id]['indexing_start_page'] = trim($arr_settings['indexing_start_page'], '\\/');
		}

        //Список расширений индексируемых файлов
        $GLOBALS['sb_sitemap_settings'][$sm_id]['passible_extentions'] = (isset($arr_settings['passible_extensions'])?$arr_settings['passible_extensions']:'');

        //Максимальный уровень вложенности ссылок
        $GLOBALS['sb_sitemap_settings'][$sm_id]['nesting_level'] = (isset($arr_settings['nesting_level'])?$arr_settings['nesting_level']:'');

        //Необходимость сжатия файла sitemap.xml
        $GLOBALS['sb_sitemap_settings'][$sm_id]['gzip_compression'] = (isset($arr_settings['gzip_compression'])?$arr_settings['gzip_compression']:0);

        //Домен с WWW?
        $GLOBALS['sb_sitemap_settings'][$sm_id]['www'] = (isset($arr_settings['www'])?$arr_settings['www']:0);
        $domain = ($GLOBALS['sb_sitemap_settings'][$sm_id]['www'] == 1)? 'www.' . $domain : $domain;

		// вызвать функцию индексации для каждого индексируемого домена указанного в настройках
		sb_add_system_message(sprintf(KERNEL_PROG_PL_SITEMAP_SYSTEMLOG_INDEXING_START, $domain));
		$tm_start = microtime(true);

		fSitemap_Indexing($sm_id, $domain);

		$tm = round(microtime(true) - $tm_start, 3);
		$res = sql_query('SELECT COUNT(*) FROM sb_sitemap_pages_'.$sm_id);
		sb_add_system_message(sprintf(KERNEL_PROG_PL_SITEMAP_SYSTEMLOG_INDEXING_END, $domain, $res[0][0], $tm));

		//	обновить время индексации домена
		sql_query('UPDATE sb_sitemap SET sm_date = ?d WHERE sm_id = ?d', time(), $sm_id);

		//	генерируем файл sitemap.xml
		$res = sql_query('SELECT smp_url, smp_last_modified, smp_change_freq, smp_priority
						FROM sb_sitemap_pages_'.$sm_id.' WHERE smp_flags=1 AND smp_active = 1');
		if ($res)
		{
			$file = $GLOBALS['sb_domains'][sb_str_replace('www.', '', $domain)]['basedir'].'/sitemap.xml';
			$tmp_file = SB_CMS_TMP_PATH.'/sitemap.xml';

			$str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

			foreach($res as $value)
			{
				list($smp_url, $smp_last_modified, $smp_change_freq, $smp_priority) = $value;

				$smp_last_modified = sb_date('Y-m-d', $smp_last_modified);

				$str .= "<url>\n\t<loc>".htmlentities($smp_url, ENT_QUOTES)."</loc>\n\t<lastmod>".$smp_last_modified."</lastmod>\n\t<changefreq>".$smp_change_freq."</changefreq>\n\t<priority>".$smp_priority."</priority>\n</url>\n";
			}
			$str .= '</urlset>';

            //Если необходимо, то сжимаем файл
            if(extension_loaded('zlib') && 1 == $GLOBALS['sb_sitemap_settings'][$sm_id]['gzip_compression'])
            {
                $str = gzcompress($str);

                $file .= '.gz';
                $tmp_file .= '.gz';
            }

//			Кладем сгенерированный xml во временный файл.
			$GLOBALS['sbVfs']->mLocal = true;
			$GLOBALS['sbVfs']->file_put_contents($tmp_file, $str);
			$crc_tmp_file = sb_crc($str);
			$crc_file = -1;

			if ($GLOBALS['sbVfs']->exists($file))
			{
//			    Читаем существующий файл и создаем контрольную сумму.
			    $str = $GLOBALS['sbVfs']->file_get_contents($file);
			    $crc_file = sb_crc($str);
			}

//			Если контрольные суммы не совпадают, значит файл xml изменился. И значит копируем новый файл.
			if($crc_tmp_file != $crc_file)
			{
				$GLOBALS['sbVfs']->copy($tmp_file, $file, true);

			}

			if($GLOBALS['sbVfs']->exists($tmp_file) && $GLOBALS['sbVfs']->is_file($tmp_file))
			{
				$GLOBALS['sbVfs']->delete($tmp_file);
			}
			$GLOBALS['sbVfs']->mLocal = false;

            // Анализируем и корректируем файл robots.txt
            fSitemap_Robots($file, $domain, $sm_id);
		}

		//	уничтожаем глобальные переменные
		unset($GLOBALS['sb_sitemap_settings'][$sm_id]);
		unset($GLOBALS['sb_sitemap_esc']);
	}
}

/**
 * Формирует полную ссылку и проверяет ее на корректность
 *
 * @param int $sm_id Идентификатор настроек индексации.
 * @param string $base Протокол, порт и домен.
 * @param string $url Ссылка.
 *
 * @return string Абсолютная ссылка или FALSE, если ссылка не проходит по условиям.
 */
function fSitemap_Parse_URL($sm_id, &$base, &$url)
{
	static $index_file = '';
	if ($index_file == '')
	{
		$index_file = trim(sbPlugins::getSetting('sb_directory_index'));
		if ($index_file == '')
		{
			$index_file = 'index.php';
		}
	}

	$domain = '';

	$ar = @parse_url($url);

    if($GLOBALS['sb_sitemap_settings'][$sm_id]['www'] != 0 && isset($ar['host']))
    {
        if(!preg_match('/^www\./i'.SB_PREG_MOD, $ar['host']))
        {
            $ar['host'] = 'www.'.$ar['host'];
        }
    }
    elseif($GLOBALS['sb_sitemap_settings'][$sm_id]['www'] == 0 && isset($ar['host']))
    {
        $ar['host'] = preg_replace('/^www\./', '', $ar['host']);
    }

	if (isset($ar['path']))
	{
		$ar['path'] = str_replace('\\', '/', $ar['path']);
	}

	$ar_base = @parse_url($base);
	// scheme
	$scheme = strtolower(isset($ar['scheme']) ? $ar['scheme'] : $ar_base['scheme']);
	if($scheme != 'http' && $scheme != 'https' && $scheme != 'ftp')
	{
		return false;
	}

	// host
	$domain = sb_strtolower(isset($ar['host']) ? $ar['host'] : $ar_base['host']);
	$domain = str_ireplace('www.', '', $domain);

	// проверить домен на допустимый
	if($domain != $GLOBALS['sb_sitemap_settings'][$sm_id]['domain'])
	{
		$found = false;
		foreach ($GLOBALS['sb_domains'] as $config_domain => $value)
		{
			if (isset($value['pointers']) && is_array($value['pointers']) && in_array($domain, $value['pointers']))
			{
				if ($config_domain != $GLOBALS['sb_sitemap_settings'][$sm_id]['domain'])
				{
					return false;
				}
				else
				{
					$found = true;
					break;
				}
			}
		}

		if (!$found)
		{
			return false;
		}
	}
    $domain = sb_strtolower(isset($ar['host']) ? $ar['host'] : $ar_base['host']);

	// port
	$port = isset($ar['port']) ? $ar['port'] : (isset($ar_base['port']) ? $ar_base['port'] : '80');

	// path
	$path = '';
	$path_index = '';
	if(isset($ar['path']) && trim($ar['path']) != '')
	{
		$path_info = pathinfo($ar['path']);

		$filename = '';
		$extension = '';
		if (!isset($path_info['extension']))
		{
			$path = trim($ar['path'], '/');
			$dirs = explode('/', $path);
			$path .= '/';
			$path_index = $path.$index_file;

			$filename = $index_file;
			$extension = pathinfo($index_file, PATHINFO_EXTENSION);
		}
		else
		{
			$path_info['dirname'] = str_replace('\\', '/', $path_info['dirname']);
			$path = trim($path_info['dirname'], '/');
			$dirs = explode('/', $path);

			$filename = trim($path_info['basename']);
			$extension = $path_info['extension'];

			if ($filename == $index_file)
			{
				$path .= '/';
				$path_index = $path.$index_file;
			}
			else
			{
				$path .= '/'.$filename;
			}
		}

        //Проверка допустимых расширений
        $extensions_list = explode(' ', $GLOBALS['sb_sitemap_settings'][$sm_id]['passible_extentions']);

        if(!in_array($extension, $extensions_list))
        {
            return false;
        }

		// проверить на список запрещенных файлов и директорий
		if(!empty($GLOBALS['sb_sitemap_settings'][$sm_id]['dirs']))
        {
            foreach($GLOBALS['sb_sitemap_settings'][$sm_id]['dirs'] as $dir)
            {
                $dir = sb_str_replace('\\', '/', $dir);

                if(preg_match('/\S+\.\S+/', $dir))
                {
                    if(preg_match('/'.preg_quote($dir, '/').'$/', $path) || preg_match('/'.preg_quote($dir, '/').'$/', $path_index))
                    {
                        return false;
                    }
                }
                else
                {
                    if (preg_match('/\/' . preg_quote($dir, '/') . '\//', '/'.$path) || preg_match('/\/' . preg_quote($dir, '/') . '\//', '/'.$path_index))
                    {
                        return false;
                    }
                }
            }
        }
	}

	// query
	if(isset($ar['query']) && trim($ar['query']) != '')
	{
		// проверить слова в строке запроса URL
		$query = explode('&', trim($ar['query']));
		$ar_query = array();

		foreach($query as $pair)
		{
			// разбить каждый элемент по "="
			$pair = explode('=', $pair);
			if(isset($pair[0]))
			{
                //Если параметр является массивом, то оставляем только имя параметра
                $pair[0] = preg_replace('/\[.*?\]/', '', $pair[0]);
				if(in_array(sb_strtolower($pair[0]), $GLOBALS['sb_sitemap_settings'][$sm_id]['words_url']))
				{
					return false;
				}

				if(!isset($pair[1]))
					continue;

				if(in_array(sb_strtolower($pair[1]), $GLOBALS['sb_sitemap_settings'][$sm_id]['words_url']))
				{
					return false;
				}

				$ar_query[] = $pair[0].'='.$pair[1];
			}
		}
		$path .= '?'.implode('&', $ar_query);
	}

	$base = $scheme.'://'.$domain;
	if ($port != '80')
	{
		$base .= ':'.$port;
	}
	$base .= '/';

	$url = $base.ltrim($path, '/');

	return true;
}

/**
 * Функция, содержащая основной цикл индексации
 *
 * @param int $sm_id Идентификатор настроек индексации.
 * @param string $domain Индексируемый домен.
 */
function fSitemap_Indexing($sm_id, $domain)
{
	$sm_id = intval($sm_id);

	$was_active_urls = false;
	$res = sql_query('SELECT COUNT(*) FROM sb_sitemap_pages_'.$sm_id.' WHERE smp_active = 1');
	if ($res && $res[0][0] > 0)
	{
		$was_active_urls = true;
	}

	$priority_default = (isset($GLOBALS['sb_sitemap_settings'][$sm_id]['priority_default']) ? $GLOBALS['sb_sitemap_settings'][$sm_id]['priority_default'] : '0.5');
	$freq = (isset($GLOBALS['sb_sitemap_settings'][$sm_id]['freq']) ? $GLOBALS['sb_sitemap_settings'][$sm_id]['freq'] : 'weekly');

//	если это первая индексация то отменяем настройку ручного редактирования
	if (!$was_active_urls)
	{
		$manual_moderate = 0;
	}
	else
	{
		$manual_moderate = (isset($GLOBALS['sb_sitemap_settings'][$sm_id]['manual_moderate']) ? $GLOBALS['sb_sitemap_settings'][$sm_id]['manual_moderate'] : 0);
	}

	//	подключаем класс для загрузки страницы
	require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');

	$start_url = $GLOBALS['sb_sitemap_settings'][$sm_id]['scheme'].'://'.$domain;
	if ($GLOBALS['sb_sitemap_settings'][$sm_id]['port'] != '80')
	{
		$start_url .= ':'.$GLOBALS['sb_sitemap_settings'][$sm_id]['port'];
	}
	$start_url .= '/';

	if ($GLOBALS['sb_sitemap_settings'][$sm_id]['indexing_start_page'] != '' && $GLOBALS['sb_sitemap_settings'][$sm_id]['indexing_start_page'] != 'index.php')
	{
		$start_url .= $GLOBALS['sb_sitemap_settings'][$sm_id]['indexing_start_page'];
	}
	elseif($GLOBALS['sb_sitemap_settings'][$sm_id]['indexing_start_page'] == '')
	{
		$index_file = trim(sbPlugins::getSetting('sb_directory_index'));

		if (!preg_match('/^\S+\.\S+$/'.SB_PREG_MOD, strtolower($index_file)))
		{
			$start_url .= trim($index_file, '\\/');
		}
		elseif(sb_strtolower($index_file) != 'index.php')
		{
			$start_url .= strtolower($index_file);
		}
	}

	//	снимает активность со всех ссылок, чтобы если при индексации станица не будет найдена, она не попала в sitemap.xml
	sql_query('UPDATE sb_sitemap_pages_'.intval($sm_id).' SET smp_delete = 1');

	// ссылки, которые еще не индексировались
	$to_visit = array();
	$to_visit[$start_url] = array('base' => $start_url, 'level' => 0);

	// ссылки, которые уже проиндексировали
	$visited = array();

	//протокол, домен и порт для относительных ссылок
	$base = $start_url;

	while (($ar = each($to_visit)) != false)
	{
		$url = $ar['key'];
		$base = $ar['value']['base'];
		$level = $ar['value']['level'] + 1;

		// удаляем из массива ссылок, которые еще надо обойти
		unset($to_visit[$url]);

		// пауза между загрузкой содержимого
		@usleep(sbPlugins::getSetting('sb_sitemap_delay_microsecond'));

		// загружаем содержимое страницы
		$download = new sbDownload($url);
		$html_text = $download->download();
		$head = $download->mHeader;

		$canonical = array();
		if (preg_match('/<[\s]*link[\s]+(.*?)rel[\s]*=[\s]*["\']canonical["\'](.*?)>/imsu', $html_text, $canonical) > 0)
		{
			$canonical_href = array();
			if (preg_match('/href[\s]*=[\s]*[\'"](.*?)["\']/imsu', $canonical[1], $canonical_href) > 0)
			{
				$download->mRedirectUrl = trim($canonical_href[1]);
			}
			elseif(preg_match('/href[\s]*=[\s]*[\'"](.*?)["\']/imsu', $canonical[2], $canonical_href) > 0)
			{
				$download->mRedirectUrl = trim($canonical_href[1]);
			}
		}

        if($download->mRedirectUrl != '')
        {
            $visited[$url] = 0;
            $old_url = $url;

            $url = $download->mRedirectUrl;

            $result = fSitemap_Parse_URL($sm_id, $new_base, $url);
            if (!$result || (isset($visited[$url]) && $old_url != $url))
            {
                $visited[$url] = 0;
                if (isset($to_visit[$url]))
                    unset($to_visit[$url]);

                unset($download);

                continue;
            }

            if (isset($to_visit[$url]))
                unset($to_visit[$url]);
        }

		unset($download);

		$visited[$url] = 0;

		$cp = 'UTF-8';
		$tm_page = time();

		$code = SB_SITEMAP_PAGE_OK;

		$matches = array();
		if (trim($html_text) == '')
		{
			$code = SB_SITEMAP_PAGE_ERROR;
		}
		elseif(trim($head) != '')
		{
			if(preg_match('/HTTP\/\d\.\d\s200\s/si', $head, $matches))
			{
				if(preg_match('/\scharset=(.*?)\s/si', $head, $matches))
				{
					$cp = trim(strtolower($matches[1]));
				}
				if(preg_match('/Last-Modified: ([a-zA-Z]{3},\s+\d{2}\s+[a-zA-Z]{3}\s+\d{4}\s+\d{2}:\d{2}:\d{2})/si', $head, $matches))
				{
					$tm_page = strtotime($matches[1]);
				}
			}
			else
			{
				$code = SB_SITEMAP_PAGE_ERROR;
			}
		}

		//есть такой урл в базе?
		$smp_active = -1;
		$res = sql_query('SELECT smp_active FROM sb_sitemap_pages_'.$sm_id.' WHERE smp_url=?', $url);

		if ($res)
		{
		    $smp_active = $res[0][0];
		}

		if ($code == SB_SITEMAP_PAGE_ERROR)
		{
			$row = array();
			$row['smp_delete'] = 0;
			$row['smp_flags'] = $code;
			$row['smp_active'] = 0;

			if ($smp_active == -1)
			{
				$row['smp_url'] = $url;
				$row['smp_priority'] = $priority_default;
				$row['smp_change_freq'] = $freq;

				sql_query('INSERT INTO sb_sitemap_pages_'.$sm_id.' SET ?a', $row);
			}
			else
			{
				sql_query('UPDATE sb_sitemap_pages_'.$sm_id.' SET ?a WHERE smp_url=?', $row, $url);
			}

			continue;
		}

		if(preg_match('/<meta[^>]+content=(["\']?)[^"\'>]+charset=([^\s;"\'>]+)\\1/si', $html_text, $matches))
		{
			$cp = trim(strtoupper($matches[2]));
		}

		if($cp != 'UTF-8')
		{
			$html_text = iconv($cp, 'UTF-8//IGNORE', $html_text);
		}

		// вытащить заголовок
		$title = '';
		if(preg_match('/<title>(.*?)<\/title>/siu', $html_text, $matches))
		{
		    $title = $matches[1];
		}

        // вырезаем стили и скрипты
        $html_text = preg_replace('/<script.*?<\/script>/siu', ' ', $html_text);
		$html_text = preg_replace('/<style.*?<\/style>/siu', ' ', $html_text);

        //выбираем ссылки следующего уровня вложенности
        if ($level <= $GLOBALS['sb_sitemap_settings'][$sm_id]['nesting_level'])
        {
            $new_links = fSitemap_Get_All_Links($html_text);

            $nofollow = $new_links['nofollow'];
            $new_links = $new_links['links'];

            foreach ($new_links as $link)
            {
                //	сформировать полную ссылку и проверить ее на корректность
                $new_base = $base;
                $old_link = $link;
                $result = fSitemap_Parse_URL($sm_id, $new_base, $link);
                if ($result && !isset($visited[$link]) && !isset($to_visit[$link]))
                {
                    $to_visit[$link] = array('base' => $new_base, 'level' => $level);
                    if(($key = array_search($old_link, $nofollow)) != false)
                    {
                        $nofollow[$key] = $link;
                    }
                }
            }
        }

		$row = array();
		$row['smp_flags'] = $code;
		$row['smp_title'] = $title;
		$row['smp_last_modified'] = $tm_page;
		$row['smp_delete'] = 0;

		if ($smp_active == -1)
		{
			$row['smp_url'] = $url;
			$row['smp_priority'] = $priority_default;
			$row['smp_active'] = (in_array($url, $nofollow) ? 0 : ($manual_moderate == 1 ? 0 : 1));
			$row['smp_change_freq'] = $freq;

			sql_query('INSERT INTO sb_sitemap_pages_'.$sm_id.' SET ?a', $row);
		}
		else
		{
			$row['smp_active'] = $smp_active;

			sql_query('UPDATE sb_sitemap_pages_'.$sm_id.' SET ?a WHERE smp_url=?', $row, $url);
		}
	}

	sql_query('DELETE FROM sb_sitemap_pages_'.intval($sm_id).' WHERE smp_delete = 1');
}

/**
 * Возвращает массив ссылок из текста
 *
 * @param string $text Анализируемый текст.
 *
 * @return array Массив ссылок.
 */
function fSitemap_Get_All_Links($text)
{
    // вырезаем идентификатор сессии из ссылок
	$text = preg_replace('/([\?&])'.@session_name().'[=](.){32}/', '\$1', $text);
    // удаляем комментарии
    $text = preg_replace('/<!--.*?-->/su', ' ', $text);

	$links = array();
	$matches = array();
    $nofollow = array();

	preg_match_all('/<a([^>]+)href\s*=\s*(["\']?)([^"\'>]+)\\2([^>]*)/siu', $text, $matches, PREG_SET_ORDER);
    for($i = 0; $i < count($matches); $i++)
    {
    	$url = trim($matches[$i][3]);
    	$url = preg_replace(array('/#.*/', '/&amp;/'), array('', '&'), $url);

    	if ($url != '')
        {
        	$links[] = $url;
            if(preg_match('/rel\s*=\s*["\']nofollow["\']/', $matches[$i][0]) || preg_match('/rel\s*=\s*["\']nofollow["\']/', $matches[$i][4]))
            {
                $nofollow[] = $url;
            }
        }
	}

    preg_match_all('/<frame[^>]+src=(["\']?)([^"\'>]+)\\1/siu', $text, $matches, PREG_SET_ORDER);
    for($i = 0; $i < count($matches); $i++)
    {
    	$url = trim($matches[$i][2]);
    	$url = preg_replace(array('/#.*/', '/&amp;/'), array('', '&'), $url);

    	if ($url != '')
        	$links[] = $matches[$i][2];
    }

	preg_match_all('/<iframe[^>]+src=(["\']?)([^"\'>]+)\\1/siu', $text, $matches, PREG_SET_ORDER);
    for($i = 0; $i < count($matches); $i++)
    {
    	$url = trim($matches[$i][2]);
    	$url = preg_replace(array('/#.*/', '/&amp;/'), array('', '&'), $url);

    	if ($url != '')
        	$links[] = $matches[$i][2];
    }

    preg_match_all('/<area[^>]+href=(["\']?)([^"\'>]+)\\1/siu', $text, $matches, PREG_SET_ORDER);
    for($i = 0; $i < count($matches); $i++)
    {
    	$url = trim($matches[$i][2]);
    	$url = preg_replace(array('/#.*/', '/&amp;/'), array('', '&'), $url);

    	if ($url != '')
        	$links[] = $matches[$i][2];
    }

    return array('links'=>$links, 'nofollow'=>$nofollow);
}

/**
 * Проверяет наличие файла robots.txt, создает или редактирует его
 *
 * @param string $file Имя файла карты сайта
 * @param string $domain Домен для которого проверяется файл robots.txt
 */
function fSitemap_Robots($file, $domain, $sm_id)
{
    $basedir = $GLOBALS['sb_domains'][sb_str_replace('www.', '', $domain)]['basedir'];
    $GLOBALS['sbVfs']->mLocal = true;
    $robots = $GLOBALS['sbVfs']->file_get_contents($basedir.'/robots.txt');
    $pathToSitemap = $GLOBALS['sb_sitemap_settings'][$sm_id]['scheme'] . '://' . $domain . sb_str_replace($basedir, '', $file);
    $checkSummBefore = sb_crc($robots);

    if ($robots)
    {
        if (preg_match('/Host:/i' . SB_PREG_MOD, $robots))
        {
            $robots = preg_replace('/(Host:\s+)(\S*)(\s+)/i' . SB_PREG_MOD, '$1' . $domain . '$3', $robots);
        }
        else
        {
            $robots .= "\r\n" . 'Host: ' . $domain;
        }

        if (preg_match('/Sitemap:/i' . SB_PREG_MOD, $robots))
        {
            $robots = preg_replace('/(Sitemap:\s+)(\S*)(\s*)/i' . SB_PREG_MOD, '$1' . $pathToSitemap . '$3', $robots);
        }
        else
        {
            $robots .= "\r\n" . 'Sitemap: ' . $pathToSitemap;
        }
    }
    else
    {
        $robots = "User-agent: *\r\n";
        foreach($GLOBALS['sb_sitemap_settings'][$sm_id]['dirs'] as $dir)
        {
            $robots .= "Disallow: /$dir\r\n";
        }

        $robots .= 'Host: ' . $domain . "\r\n";
        $robots .= 'Sitemap: ' . $pathToSitemap . "\r\n";
    }

    $checkSummAfter = sb_crc($robots);

    if($checkSummAfter != $checkSummBefore)
    {
        $GLOBALS['sbVfs']->file_put_contents($basedir.'/robots.txt', $robots);
    }

    $GLOBALS['sbVfs']->mLocal = false;
}
?>