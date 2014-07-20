<?php
/**
 * Случайный текстовый блок
 */
function fTexts_Elem_Random($el_id, $temp_id, $params, $tag_id)
{
	$params = unserialize(stripslashes($params));
    $el_id = intval($el_id);
    $temp_id = intval($temp_id);

    $subcategs = (isset($params['subcategs']) && $params['subcategs'] == 1);

    if(SB_DEMO_SITE)
	{
		$demo_statuses = explode(',', sb_get_workflow_demo_statuses());
	}
	else
	{
		$demo_statuses = array(1);
	}

    if ($subcategs)
    {
        $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id="'.$el_id.'"');
        if ($res)
        {
            list($cat_left, $cat_right) = $res[0];

            $res = sql_query('SELECT t.t_html FROM sb_categs c, sb_catlinks l, sb_texts t
                              WHERE c.cat_left >= "'.$cat_left.'"
                              AND c.cat_right <= "'.$cat_right.'"
                              AND c.cat_ident="pl_texts"
                              AND l.link_cat_id=c.cat_id
                              AND t.t_status IN (?a)
                              AND t.t_id=l.link_el_id
                              ORDER BY RAND() LIMIT 1', $demo_statuses);
            if ($res)
            {
                eval(' ?>'.$res[0][0].'<?php ');
            }
        }
    }
    else
    {
        $res = sql_query('SELECT t.t_html FROM sb_catlinks l, sb_texts t WHERE l.link_cat_id="'.$el_id.'" AND t.t_status IN (?a) AND t.t_id=l.link_el_id ORDER BY RAND() LIMIT 1', $demo_statuses);
        if ($res)
        {
            eval(' ?>'.$res[0][0].'<?php ');
        }
    }

    $GLOBALS['sbCache']->setLastModified(time());
}

function fTexts_Elem_Pager($el_id, $tag_id)
{
	$res = sql_param_query('SELECT t_html, t_old_html, t_status FROM sb_texts WHERE t_id=?d', $el_id);
	if (!$res)
		return;

	list($html, $t_old_html, $t_status) = $res[0];

	if(!SB_DEMO_SITE && $t_status != 1)
	{
		$html = $t_old_html;
	}

	$demo_statuses = explode(',', sb_get_workflow_demo_statuses());
	if (!in_array($t_status, $demo_statuses))
	{
		return;
	}

	$html = preg_split('/<div[\s]+style[\s]*=[\s]*["|\'][\s]*page\-break\-after[\s]*:[\s]*always[;]?[\s]*["|\']>[\s]*<span[\s]+style[\s]*=[\s]*["|\'][\s]*display[\s]*:[\s]*none[\s]*[;]?[\s]*["|\']>[\s]*&nbsp;[\s]*<\/span>[\s]*<\/div>/i', $html);

	$num = count($html);

	if ($num <= 1)
	{
		//чистим код от инъекций
		$html[0] = sb_clean_string($html[0]);

		eval(' ?>'.$html[0].'<?php ');
		return;
	}

	$res = sbQueryCache::getTemplate('sb_pager_temps', sbPlugins::getSetting('sb_texts_use_delim'));

	if (!$res)
	{
		//чистим код от инъекций
		$html = sb_clean_string($html);

		eval(' ?>'.$html.'<?php ');
		return;
	}

	list($pt_perstage, $pt_begin, $pt_next, $pt_previous, $pt_end, $pt_number, $pt_sel_number, $pt_page_list, $pt_delim) = $res[0];

	@require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');

    $pager = new sbDBPager($tag_id, $pt_perstage, 1);

    $pager->mNumElemsAll = $num;

	// строим список номеров страниц
    if ($pt_page_list != '')
    {
        $pager->mBeginTemp = $pt_begin;
        $pager->mBeginTempDisabled = '';
        $pager->mNextTemp = $pt_next;
        $pager->mNextTempDisabled = '';

        $pager->mPrevTemp = $pt_previous;
        $pager->mPrevTempDisabled = '';
        $pager->mEndTemp = $pt_end;
        $pager->mEndTempDisabled = '';

        $pager->mNumberTemp = $pt_number;
        $pager->mCurNumberTemp = $pt_sel_number;
        $pager->mDelimTemp = $pt_delim;
        $pager->mListTemp = $pt_page_list;

        $pt_page_list = $pager->show();
    }

    if (isset($html[$pager->mPage - 1]))
    {
    	$str = $html[$pager->mPage - 1].$pt_page_list;

    	//чистим код от инъекций
    	$str = sb_clean_string($str);

    	eval(' ?>'.$str.'<?php ');
    }
    else
    {
    	$str = $html[0].$pt_page_list;

    	//чистим код от инъекций
    	$str = sb_clean_string($str);

    	eval(' ?>'.$str.'<?php ');
    }
}

function fTexts_Elem_Html($el_id)
{
	$res = sql_param_query('SELECT t_html, t_old_html, t_status FROM sb_texts WHERE t_id=?d', $el_id);
	if (!$res)
		return;

	list($t_html, $t_old_html, $t_status) = $res[0];

	if(!SB_DEMO_SITE)
	{
		echo $t_old_html;
		return;
	}

	$demo_statuses = explode(',', sb_get_workflow_demo_statuses());
	if (in_array($t_status, $demo_statuses))
	{
		echo $t_html;
	}
}

function fTexts_Elem_Plain($el_id, $temp_id, $params, $tag_id)
{
	$res = sql_param_query('SELECT t_html, t_old_html, t_status FROM sb_texts WHERE t_id=?d', $el_id);
	if (!$res)
		return;

	list($t_html, $t_old_html, $t_status) = $res[0];

	if(!SB_DEMO_SITE)
	{
		echo strip_tags($t_old_html);
		return;
	}

	$demo_statuses = explode(',', sb_get_workflow_demo_statuses());
	if (in_array($t_status, $demo_statuses))
	{
		echo strip_tags($t_html);
	}
}
?>