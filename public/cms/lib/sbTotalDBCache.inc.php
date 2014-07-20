<?php
/**
 * Кеширование результатов SQL-запросов и макетов дизайна элементов
 *
 * @author Артур Фурса <art@binn.ru>
 * @version 4.0
 * @package SB_CACHE
 * @copyright Copyright (c) 2012-2013, OOO "БИНН"
 */
class sbQueryCache{

	/**
	 * Хранилище результатов запросов
	 */
	static private $dataStore = array();

	/**
	 * Кеширует результат запроса в виде стандартного двумерного массива
	 */
	static public function query(){
		$args = func_get_args();

		$useSession = false;
		if(intval($args[0])){
			$useSession = true;
			array_shift($args);
		}
		$key = md5(serialize($args).(isset($_SESSION['sbAuth']) ? $_SESSION['sbAuth'] -> getUserId() : ''));

		if($useSession && isset($_SESSION) && isset($_SESSION['queryCache'][$key])){
			self::$dataStore[$key] = $_SESSION['queryCache'][$key];
		}

		if(!isset(self::$dataStore[$key])){
			self::$dataStore[$key] = call_user_func_array('sql_param_query', $args);
			if($useSession && isset($_SESSION)){
				$_SESSION['queryCache'][$key] = self::$dataStore[$key];
			}
		}

		return self::$dataStore[$key];
	}

	/**
	 * Метод получает макет дизайна элемента. Если файл соответствующего макета не найден, то используется БД
	 *
	 * @param $tableName таблица в которой хранится макет дизайна элемента
	 * @param $tplId идентификатор макета дизайна
	 */
	static public function getTemplate($tableName, $tplId, $assoc=false){
		$tpl = false;
		$tplCacheFile = SB_CMS_CACHE_PATH.'/'.$tableName.'_'.$tplId.'.tpl';

		$tmp = $GLOBALS['sbVfs']->mLocal;
		$GLOBALS['sbVfs']->mLocal = true;
		if(!isset($_REQUEST['sb_page_nocache']) && $GLOBALS['sbVfs']->exists($tplCacheFile)){
			$str = $GLOBALS['sbVfs']->file_get_contents($tplCacheFile);
			if(trim($str) != ''){
				$tpl = unserialize($str);
			} else {
                $GLOBALS['sbVfs']->mLocal = $tmp;
				return false;
			}
		} else {
			$sql = self::getSQL($tableName);
			if($sql != ''){
				$tpl = $assoc ? sql_param_assoc($sql, $tplId) : sql_query($sql, $tplId);
			} else {
                $GLOBALS['sbVfs']->mLocal = $tmp;
				return false;
			}

			//сохраняем полученный результат в файл
            if(!$GLOBALS['sbVfs']->exists(SB_CMS_CACHE_PATH) || !$GLOBALS['sbVfs']->is_dir(SB_CMS_CACHE_PATH))
            {
                $GLOBALS['sbVfs']->mkdir(SB_CMS_CACHE_PATH);
            }
            $GLOBALS['sbVfs']->fopen($tplCacheFile,'w+');
            $GLOBALS['sbVfs']->fwrite(serialize($tpl));
            $GLOBALS['sbVfs']->fclose();
		}
        $GLOBALS['sbVfs']->mLocal = $tmp;
		return $tpl;
	}

	/**
	 * Возвращает оригинальный текст SQL-запроса для получения макета дизайна
	 */
	 static private function getSQL($tableName){

	 	switch ($tableName){
			case 'sb_menu_temps':
				$sql = 'SELECT mt_lang, mt_levels, mt_fields_temps, mt_checked FROM sb_menu_temps WHERE mt_id=?d';
				break;
			case 'sb_menu_path_temps':
				$sql = 'SELECT mpt_lang, mpt_checked, mpt_top, mpt_item, mpt_last_item, mpt_bottom, mpt_fields_temps FROM sb_menu_path_temps WHERE mpt_id=?d';
				break;
			case 'sb_categs_temps_list':
				$sql = 'SELECT ctl_lang, ctl_levels, ctl_categs_temps, ctl_checked, ctl_perpage, ctl_pagelist_id FROM sb_categs_temps_list WHERE ctl_id=?d';
				break;
			case 'sb_categs_temps_full':
				$sql = 'SELECT ctf_lang, ctf_temp, ctf_categs_temps, ctf_checked FROM sb_categs_temps_full WHERE ctf_id=?d';
				break;

			case 'sb_news_temps_list':
				$sql = 'SELECT ndl_lang, ndl_checked, ndl_count, ndl_top, ndl_categ_top, ndl_element, ndl_empty, ndl_delim,
                ndl_categ_bottom, ndl_bottom, ndl_pagelist_id, ndl_perpage, ndl_no_news, ndl_fields_temps, ndl_categs_temps,
                ndl_votes_id, ndl_comments_id, ndl_user_data_id, ndl_tags_list_id
                FROM sb_news_temps_list WHERE ndl_id=?d';
				break;
			case 'sb_news_temps_full':
				$sql = 'SELECT ntf_lang, ntf_element, ntf_fields_temps, ntf_categs_temps, ntf_checked, ntf_comments_id, ntf_votes_id, ntf_user_data_id, ntf_tags_list_id
                FROM sb_news_temps_full WHERE ntf_id=?d';
				break;

			case 'sb_clouds_temps':
				$sql = 'SELECT ct_count, ct_top, ct_element, ct_empty, ct_delim, ct_bottom,
    			ct_font_from, ct_font_to, ct_strip_words, ct_color, ct_color_percent
                FROM sb_clouds_temps WHERE ct_id=?d';
				break;

			case 'sb_search_temps_form':
				$sql = 'SELECT sstf_title, sstf_form FROM sb_search_temps_form WHERE sstf_id=?d';
				break;

            case 'sb_search_temps_results':
                $sql = 'SELECT sstr_top, sstr_element, sstr_bottom, sstr_no_results, sstr_found_word, sstr_lang, sstr_perpage, sstr_pagelist_id
						FROM sb_search_temps_results WHERE sstr_id = ?d';
                break;

			case 'sb_site_users_temps':
				$sql = 'SELECT sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages FROM sb_site_users_temps WHERE sut_id=?d';
				break;

            case 'sb_site_users_temps_list':
                $sql = 'SELECT sutl_lang, sutl_checked, sutl_count, sutl_top, sutl_categ_top, sutl_element, sutl_empty, sutl_delim,
                    sutl_categ_bottom, sutl_bottom, sutl_pagelist_id, sutl_perpage, sutl_messages, sutl_fields_temps, sutl_categs_temps,
                    sutl_votes_id, sutl_comments_id
                    FROM sb_site_users_temps_list WHERE sutl_id=?d';
                break;

			case 'sb_pager_temps':
				$sql = 'SELECT pt_perstage, pt_begin, pt_next, pt_previous, pt_end, pt_number, pt_sel_number, pt_page_list, pt_delim FROM sb_pager_temps WHERE pt_id=?d';
				break;

            case 'sb_banners_temps':
                $sql = 'SELECT sbt_lang, sbt_element, sbt_fields_temps, sbt_checked FROM sb_banners_temps WHERE sbt_id=?d';
                break;

            case 'sb_banners_temps_list':
                $sql = 'SELECT sbtl.sbdl_element, sbtl.sbdl_checked, sbtl.sbdl_elem_temps, sbtl.sbdl_count, sbtl.sbdl_top, sbtl.sbdl_bottom, sbt.sbt_lang, sbt.sbt_element, sbt.sbt_fields_temps
                                FROM sb_banners_temps_list sbtl, sb_banners_temps sbt
                                WHERE sbdl_id=?d AND sbt.sbt_id=sbtl.sbdl_elem_temps';
                break;

            case 'sb_plugins_temps_form':
                $sql = 'SELECT ptf_title, ptf_form, ptf_messages, ptf_user_data_id FROM sb_plugins_temps_form WHERE ptf_id =?d';
                break;

            case 'sb_calendar_temps':
                $sql = 'SELECT ct_lang, ct_top, ct_num, ct_empty, ct_delim, ct_bottom, ct_fields FROM sb_calendar_temps WHERE ct_id=?d';
                break;

            case 'sb_comments_temps_list':
                $sql = 'SELECT ctl_lang, ctl_perpage, ctl_pagelist_id, ctl_user_data_id, ctl_top, ctl_element, ctl_bottom, ctl_fields_temps, ctl_messages FROM sb_comments_temps_list WHERE ctl_id = ?d';
                break;

            case 'sb_faq_temps_list':
                $sql = 'SELECT fdl_lang, fdl_checked, fdl_count, fdl_top, fdl_categ_top, fdl_element, fdl_empty, fdl_delim,
                        fdl_categ_bottom, fdl_bottom, fdl_pagelist_id, fdl_perpage, fdl_no_questions, fdl_fields_temps, fdl_categs_temps, fdl_votes_id, fdl_comments_id, fdl_user_data_id, fdl_tags_list_id
                        FROM sb_faq_temps_list WHERE fdl_id=?d';
                break;

            case 'sb_faq_temps_full':
                $sql = 'SELECT ftf_lang, ftf_fullelement, ftf_fields_temps, ftf_categs_temps, ftf_checked, ftf_votes_id, ftf_comments_id, ftf_user_data_id, ftf_tags_list_id
                        FROM sb_faq_temps_full WHERE ftf_id=?d';
                break;

            case 'sb_forum_temps_categs':
                $sql = 'SELECT ftc_categs_temps, ftc_sub_categs_temps, ftc_pager_id, ftc_perpage, ftc_lang, ftc_checked,
								ftc_user_categs_temps, ftc_user_subcategs_temps, ftc_subjects_id
								FROM sb_forum_temps_categs WHERE ftc_id = ?d';
                break;

            case 'sb_forum_temps_subjects':
                $sql = 'SELECT fts_checked, fts_categs_temps,
						fts_user_categs_temps, fts_perpage, fts_perpage_messages, fts_messages_id,  fts_pagelist_id, fts_lang,
			            fts_pagelist_mess_id, fts_user_data_themes_id, fts_user_data_mess_id, fts_messages_temps
			            FROM sb_forum_temps_subjects
			            WHERE fts_id = ?d';
                break;

            case 'sb_forum_form_theme':
                $sql = 'SELECT sftf_title, sftf_lang, sftf_text, sftf_categs_temps, sftf_fields_temps, sftf_messages
						FROM sb_forum_form_theme WHERE sftf_id=?d';
                break;

            case 'sb_forum_form_msg':
                $sql = 'SELECT sffm_lang, sffm_title, sffm_text, sffm_fields_temps, sffm_categs_temps, sffm_messages
                        FROM sb_forum_form_msg WHERE sffm_id=?d';
                break;

            case 'sb_imagelib_temps_list':
                $sql = 'SELECT itl_checked, itl_count_on_page, itl_title, itl_lang, itl_fields_temps, itl_categs_temps,
                        itl_no_image_message, itl_count_on_line, itl_top, itl_top_cat, itl_image, itl_empty, itl_delim,
	   					itl_bottom_cat, itl_bottom, itl_pagelist_id, itl_votes_id, itl_comments_id, itl_user_data_id, itl_tags_list_id
						FROM sb_imagelib_temps_list WHERE itl_id=?d';
                break;

            case 'sb_imagelib_temps_full':
                $sql = 'SELECT itf_lang, itf_element, itf_fields_temps, itf_categs_temps, itf_checked, itf_comments_id, itf_voting_id, itf_user_data_id, itf_tags_list_id
                        FROM sb_imagelib_temps_full WHERE itf_id=?d';
                break;

            case 'sb_imagelib_temps_form':
                $sql = 'SELECT itfrm_name, itfrm_lang, itfrm_form, itfrm_fields_temps, itfrm_categs_temps, itfrm_messages
						FROM sb_imagelib_temps_form WHERE itfrm_id=?d';
                break;

            case 'sb_payments_temps':
                $sql = 'SELECT spt_payment_descr, spt_prerequest_main, spt_confirm FROM sb_payments_temps WHERE spt_id=?d';
                break;

            case 'sb_payments_temps_list':
                $sql = 'SELECT sptl_list_header, sptl_categ_top, sptl_list_main, sptl_categ_bottom, sptl_list_footer, sptl_empty,
                        sptl_delim, sptl_pagelist_id, sptl_count, sptl_perpage, sptl_lang, sptl_checked, sptl_no_transaction, sptl_fields_temps, sptl_categs_temps
                        FROM sb_payments_temps_list WHERE sptl_id=?d';
                break;

            case 'sb_plugins_temps_list':
                $sql = 'SELECT ptl_lang, ptl_checked, ptl_count, ptl_top, ptl_categ_top, ptl_element, ptl_empty, ptl_delim,
                        ptl_categ_bottom, ptl_bottom, ptl_pagelist_id, ptl_perpage, ptl_no_elems, ptl_fields_temps, ptl_categs_temps,
                        ptl_votes_id, ptl_comments_id, ptl_user_data_id, ptl_tags_list_id
                        FROM sb_plugins_temps_list WHERE ptl_id=?d';
                break;

            case 'sb_plugins_temps_full':
                $sql = 'SELECT ptf_lang, ptf_element, ptf_fields_temps, ptf_categs_temps, ptf_checked, ptf_votes_id, ptf_comments_id, ptf_user_data_id, ptf_tags_list_id
                        FROM sb_plugins_temps_full WHERE ptf_id=?d';
                break;

            case 'sb_polls_temps':
                $sql = 'SELECT spt_title, spt_lang, spt_checked, spt_count, spt_perpage, spt_delim, spt_empty, spt_top, spt_cat_top,
    				spt_polls_top, spt_element, spt_polls_bottom, spt_cat_bottom, spt_bottom, spt_pagelist_id, spt_fields_temps,
    				spt_categs_temps, spt_messages
					FROM sb_polls_temps WHERE spt_id=?d';
                break;

            case 'sb_polls_temps_results':
                $sql = 'SELECT sptr_title, sptr_lang, sptr_perpage, sptr_pagelist_id, sptr_count, sptr_checked, sptr_top,
                    sptr_categs_top, sptr_result_top, sptr_element, sptr_result_bottom, sptr_categs_bottom, sptr_bottom, sptr_empty,
                    sptr_delim, sptr_fields_temps, sptr_categs_temps, sptr_no_results FROM sb_polls_temps_results WHERE sptr_id=?d';
                break;

            case 'sb_services_cb_temps':
                $sql = 'SELECT ssct_date, ssct_lang, ssct_body FROM sb_services_cb_temps WHERE ssct_id=?d';
                break;

            case 'sb_services_rutube_temps_list':
                $sql = 'SELECT ssrt_lang, ssrt_checked, ssrt_count_row, ssrt_top, ssrt_categ_top, ssrt_temp_elem, ssrt_empty, ssrt_delim,
                    ssrt_categ_bottom, ssrt_bottom, ssrt_pagelist_id, ssrt_perpage, ssrt_no_movies, ssrt_fields_temps, ssrt_categs_temps, ssrt_votes_id, ssrt_comments_id, ssrt_user_data_id, ssrt_tags_list_id
                    FROM sb_services_rutube_temps_list WHERE ssrt_id=?d';
                break;

            case 'sb_services_rutube_temps_full':
                $sql = 'SELECT ssrtf_lang, ssrtf_fullelement, ssrtf_fields_temps, ssrtf_categs_temps, ssrtf_checked, ssrtf_voting_id, ssrtf_comments_id, ssrtf_user_data_id, ssrtf_tags_list_id
                        FROM sb_services_rutube_temps_full WHERE ssrtf_id=?d';
                break;

            case 'sb_services_rutube_temps_form':
                $sql = 'SELECT ssrtf_lang, ssrtf_form, ssrtf_fields_temps, ssrtf_categs_temps, ssrtf_messages
                        FROM sb_services_rutube_temps_form WHERE ssrtf_id=?d';
                break;

            case 'sb_sprav_temps':
                $sql = 'SELECT st_count, st_top, st_cat_top, st_element, st_empty, st_delim,
                        st_cat_bottom, st_bottom, st_pagelist_id, st_perpage, st_no_elems, st_fields_temps
                        FROM sb_sprav_temps WHERE st_id=?d';
                break;

            case 'sb_tester_temps':
                $sql = 'SELECT stt_date, stt_lang, stt_top, stt_quest, stt_chet_answer, stt_nechet_answer, stt_empty_element,
                    stt_always_chet, stt_bottom, stt_result, stt_system_message, stt_fields_temps, stt_categs_temps
                    FROM sb_tester_temps WHERE stt_id=?d';
                break;

            case 'sb_tester_rating_temps':
                $sql = 'SELECT strt_pagelist_id, strt_perpage, strt_top, strt_elem, strt_delim,
                        strt_empty, strt_bottom, strt_count, strt_categs_temps, strt_system_message
                        FROM sb_tester_rating_temps WHERE strt_id=?d';
                break;
            case 'sb_tester_result_temps':
                $sql = 'SELECT strt_lang, strt_date, strt_top, strt_result, strt_bottom, strt_empty, strt_delim, strt_count,
                        strt_perpage, strt_pagelist_id, strt_system_message
                        FROM sb_tester_result_temps WHERE strt_id=?d';
                break;

            case 'sb_vote_temps':
                $sql = 'SELECT vt_temp FROM sb_vote_temps WHERE vt_id=?d';
                break;

			default:
				$sql = '';
		}

		return $sql;
	 }

     /**
      * Обновляет файл макета дизайна после изменения в админке
      *
      * @param string $tableName
      * @param int $tplId
      */
     static public function updateTemplate($tableName, $tplId, $assoc=false)
    {
        $tpl          = false;
        $tplCacheFile = SB_CMS_CACHE_PATH . '/' . $tableName . '_' . $tplId . '.tpl';

        $tmp                      = $GLOBALS['sbVfs']->mLocal;
        $GLOBALS['sbVfs']->mLocal = true;
        $sql                      = self::getSQL($tableName);
        if ($sql != '')
        {
            $tpl = $assoc ? sql_param_assoc($sql, $tplId) : sql_query($sql, $tplId);
            $GLOBALS['sbVfs']->fopen($tplCacheFile, 'w+');
            $GLOBALS['sbVfs']->fwrite(serialize($tpl));
            $GLOBALS['sbVfs']->fclose();
        }
        $GLOBALS['sbVfs']->mLocal = $tmp;
    }

     static public function deleteTemplate($tableName, $tplId)
     {
         if(!is_array($tplId))
         {
             $tplId = array($tplId);
         }

         $tmp                      = $GLOBALS['sbVfs']->mLocal;
         $GLOBALS['sbVfs']->mLocal = true;

         foreach($tplId as $id)
         {
             $tplCacheFile = SB_CMS_CACHE_PATH . '/' . $tableName . '_' . $id . '.tpl';

             if($GLOBALS['sbVfs']->exists($tplCacheFile))
             {
                 $GLOBALS['sbVfs']->delete($tplCacheFile);
             }
         }
         $GLOBALS['sbVfs']->mLocal = $tmp;
     }
}

/**
 * Кеширование количества результатов для SQL-запросов выбора списка элементов
 *
 * @author Serg
 */
class sbTotalDBCache
{
    private static $_Instance = null;

    private $_type = null;
    private $_timeout = 0;

    private $_hash = null;
    private $_total = 0;
    private $_lastClear = 0;
    private $_useMemcache = true;
    private $_MemcacheLink = null;
    private $_memcacheCompressed = false;

    public static function getInstance()
    {
        if(sbTotalDBCache::$_Instance === null)
        {
            self::$_Instance = new sbTotalDBCache();
        }
        return self::$_Instance;
    }

    public function init($query)
    {
        if($query == '' || $this->_timeout == 0)
        {
            return;
        }

        $this->_total = 0;

        //отбрасываем из запроса лимит, сортировку и проверку интервала публикации
        $query_tmp = preg_replace('/order by .* limit .*/i', '', $query);
        $query_tmp = preg_replace('/and\s+\(.+_pub_start[^\)]+\)\s+and\s+\(.+_pub_end[^\)]+\)/i', '', $query_tmp);

        //Вычисляем хэш запроса
        $this->_hash      = md5($query_tmp.SB_COOKIE_DOMAIN);

        if(!$this->_useMemcache && (time() - $this->_lastClear) >= $this->_timeout)
        {
            $this->clear();
            $this->_lastClear = time();
        }

        $this->loadTotal();
    }

    public function clear()
    {
        switch ($this->_type)
        {
            case 'mysqli':
                @mysqli_query($GLOBALS['sbSql']->mLinkId, 'DELETE FROM sb_total_cache WHERE stc_timestamp <= ' . (time() - $this->_timeout));
                break;
            case 'mysql':
                @mysql_query('DELETE FROM sb_total_cache WHERE stc_timestamp <= ' . (time() - $this->_timeout), $GLOBALS['sbSql']->mLinkId);
                break;
        }
    }

    public function getTotal()
    {
        return $this->_total;
    }

    private function loadTotal()
    {
        if ($this->_hash !== null)
        {
            if($this->_useMemcache)
            {
                $data = $this->_MemcacheLink->get($this->_hash);
                if(is_array($data))
                {
                    $this->_total = $data['count'];
                    return;
                }
            }

            switch ($this->_type)
            {
                case 'mysqli':
                    $res = @mysqli_query($GLOBALS['sbSql']->mLinkId, 'SELECT stc_total_count FROM sb_total_cache WHERE stc_hash="' . $this->_hash . '"');
                    if ($res instanceof mysqli_result)
                    {
                        $row          = @mysqli_fetch_row($res);
                        $this->_total = $row[0];
                    }
                    break;
                case 'mysql':
                    $res = @mysql_query('SELECT stc_total_count FROM sb_total_cache WHERE stc_hash="' . $this->_hash . '"', $GLOBALS['sbSql']->mLinkId);
                    if (is_resource($res))
                    {
                        $row              = @mysql_fetch_row($res);
                        $this->_total = $row[0];
                    }
                    break;
            }
        }
    }

    public function save($count)
    {
        if($this->_timeout == 0 || $this->_hash === null)
        {
            return;
        }

        if($this->_useMemcache)
        {
            $row = array(
                'count' => $count,
                'time' => time()
            );
            $this->_MemcacheLink->add($this->_hash, $row, $this->_memcacheCompressed, $this->_timeout);
            return;
        }
        switch ($this->_type)
        {
            case 'mysqli':
                @mysqli_query($GLOBALS['sbSql']->mLinkId, 'INSERT INTO sb_total_cache VALUES("'.$this->_hash.'",'.$count.','.time().')');
                break;
            case 'mysql':
                @mysql_query('INSERT INTO sb_total_cache VALUES("'.$this->_hash.'",'.$count.','.time().')', $GLOBALS['sbSql']->mLinkId);
                break;
        }
    }

    private function __construct()
    {
        if (class_exists('Mysqli') && $GLOBALS['sbSql']->mLinkId instanceof mysqli)
        {
            $this->_type = 'mysqli';
        }
        elseif (is_resource($GLOBALS['sbSql']->mLinkId))
        {
            $this->_type = 'mysql';
        }

        $this->_timeout = intval(sbPlugins::getSetting('sb_cache_total'));
        $host = sbPlugins::getSetting('sb_use_mcache_host');
        $port = sbPlugins::getSetting('sb_use_mcache_port');
		$useMemcache = sbPlugins::getSetting('sb_use_cache');

        if(class_exists('Memcache') && !is_null($host) && $host != '' && intval($port) != 0 && $useMemcache == SB_CACHE_MEMCACHE)
        {
            $this->_useMemcache = true;
            $this->_MemcacheLink = new Memcache();
            if(!$this->_MemcacheLink->pconnect($host, $port))
            {
                $this->_useMemcache = false;
                $this->_MemcacheLink = null;
            }
            else
            {
                $this->_memcacheCompressed = (sbPlugins::getSetting('sb_use_mcache_zip') == 1) ? MEMCACHE_COMPRESSED : false;
            }
        } else {
        	$this->_useMemcache = false;
            $this->_MemcacheLink = null;
        }
    }
}

?>
