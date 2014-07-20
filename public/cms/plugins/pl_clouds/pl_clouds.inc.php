<?php
function fClouds_Load_Tags()
{
	// подгрузка тегов из списка
	$result = '';

	$res = sql_query('SELECT ct_tag, COUNT(cl_el_id) FROM sb_clouds_tags LEFT JOIN sb_clouds_links ON ct_id=cl_tag_id WHERE ct_tag LIKE ? GROUP BY ct_id ORDER BY ct_tag LIMIT 25', trim($_GET['letters']).'%');
	if ($res)
	{
		$result = array();
		foreach ($res as $value)
		{
			$result[] = $value[1].'###'.$value[0];
		}
		$result = implode('|', $result);
	}

	echo $result;
}

/**
 * Функция, выводящая поле выбора тематических тегов
 *
 * @param object $layout Объект sbLayout, к которому будут добавлены поля.
 * @param mixed $el_id Идентификатор(ы) элемента(ов).
 * @param string $pl_ident Идентификатор модуля.
 * @param string $field_name Имя поля.
 * @param int $field_width Ширина поля в пикселах.
 */
function fClouds_Get_Field(&$layout, $field_name, $el_id, $pl_ident, $field_width = '440px', $edit_group = false)
{
	$value = '';
	if(!is_array($el_id) && $el_id != '')
	{
        if(!isset($_POST['p_tags']) || $_POST['p_tags'] == '')
        {
            $res = sql_query('SELECT ct_tag FROM sb_clouds_tags, sb_clouds_links WHERE cl_ident=? AND cl_el_id = ?d AND ct_id=cl_tag_id', $pl_ident, $el_id);
            if($res)
            {
                $value = array();
                foreach($res as $val)
                {
                    $value[] = $val[0];
                }

                $value = implode(', ', $value);
            }
        }
        else
            $value = $_POST['p_tags'];
	}

	$fld = new sbLayoutAutocomplete($value, $field_name, '', 'style="width:'.$field_width.';"');
	$fld->mAction = SB_CMS_EMPTY_FILE.'?event=pl_clouds_load_tags';

	$layout->addField (KERNEL_THEME_TAGS.sbGetGroupEditCheckbox('clouds', $edit_group), $fld, 'id="'.$field_name.'_th"', 'id="'.$field_name.'_td"', 'id="'.$field_name.'_tr"');
	$layout->addField ('', new sbLayoutDelim(), 'id="'.$field_name.'_del_th"', 'id="'.$field_name.'_del_td"', 'id="'.$field_name.'_del_tr"');
}

/**
 * Функция сохраняет связи тегов с элементами модуля
 *
 * @param mixed $el_id Идентификатор(ы) элемента(ов).
 * @param string $pl_ident Идентификатор модуля.
 * @param string $tags Теги.
 * @param bool $edit_group Используется для группового редактирования или нет.
 */
function fClouds_Set_Field($el_id, $pl_ident, $tags, $edit_group = false)
{
    sql_query('LOCK TABLES sb_clouds_links WRITE, sb_clouds_tags WRITE');

	if($edit_group)
	{
		sql_query('DELETE FROM sb_clouds_links WHERE cl_ident=? AND cl_el_id IN (?a)', $pl_ident, $el_id);
	}
	else
	{
		sql_query('DELETE FROM sb_clouds_links WHERE cl_ident=? AND cl_el_id=?d', $pl_ident, $el_id);
	}

	if (trim($tags) == '')
	{
	    sql_query('UNLOCK TABLES');
		return;
	}

	$tags = explode(',', $tags);
	if (count($tags) <= 0)
	{
	    sql_query('UNLOCK TABLES');
		return;
	}

	$ct_ids = array();

	foreach ($tags as $value)
	{
		$tag = sb_strtolower(trim($value));
		$res = sql_query('SELECT ct_id FROM sb_clouds_tags WHERE LOWER(ct_tag) = ?', $tag);
		if ($res)
		{
			$id = $res[0][0];
			if (!in_array($id, $ct_ids))
				$ct_ids[] = $res[0][0];
		}
		else
		{
			sql_query('INSERT INTO sb_clouds_tags (ct_tag) VALUES (?)', $tag);

			$id = sql_insert_id();
			if ($id)
			{
				$ct_ids[] = $id;
			}
		}
	}

	if($edit_group)
	{
		foreach ($el_id as $val)
		{
			foreach ($ct_ids as $value)
			{
				$row = array();
				$row['cl_ident'] = $pl_ident;
				$row['cl_el_id'] = intval($val);
				$row['cl_tag_id'] = $value;

				sql_query('INSERT INTO sb_clouds_links (?#) VALUES (?a)', array_keys($row), array_values($row));
			}
		}
	}
	else
	{
		foreach ($ct_ids as $value)
		{
			$row = array();
			$row['cl_ident'] = $pl_ident;
			$row['cl_el_id'] = intval($el_id);
			$row['cl_tag_id'] = $value;

			sql_query('INSERT INTO sb_clouds_links (?#) VALUES (?a)', array_keys($row), array_values($row));
		}
	}

	sql_query('UNLOCK TABLES');
}

/**
 * Функция, удаляющая связи элемента с тегами
 *
 * @param int $el_id ID Удаляемого элемента.
 * @param string $plugin_id Уникальный идентификатор модуля.
 */
function fClouds_Delete($el_id, $plugin_id)
{
	if(is_array($el_id))
    	sql_query('DELETE FROM sb_clouds_links WHERE cl_ident=? AND cl_el_id IN (?a)', $plugin_id, $el_id);
    else
    	sql_query('DELETE FROM sb_clouds_links WHERE cl_ident=? AND cl_el_id=?d', $plugin_id, $el_id);
}

/**
 * Функция, копирующая связи элемента с тегами
 *
 * @param array $el_ids Массив идентификаторов элементов, ключ массива - идентификатор копируемого элемента, значение массива - идентификатор копии.
 * @param string $plugin_id Уникальный идентификатор модуля.
 */
function fClouds_Copy($el_ids, $plugin_id)
{
	$in_str = implode(',', array_keys($el_ids));

	$res = sql_query('SELECT cl_el_id, cl_tag_id FROM sb_clouds_links WHERE cl_ident=? AND cl_el_id IN ('.$in_str.')', $plugin_id);
	if (!$res)
		return;

    foreach($res as $value)
    {
		list($el_id, $tag_id) = $value;

		sql_query('INSERT INTO sb_clouds_links (cl_ident, cl_el_id, cl_tag_id) VALUES (?, ?d, ?d)', $plugin_id, $el_ids[$el_id], $tag_id);
    }
}

/**
 * Функция, выводящая поле выбора макет дизайна облака тегов
 *
 * @param object $layout Объект sbLayout, к которому будут добавлены поля.
 * @param string $field_value Идентификатор выбранного макета дизайна.
 * @param string $field_name Имя поля, в котором будет храниться идентификатор макета дизайна.
 * @param string $field_place Место расположения компонента: component - в настройках компонента, 'element' - в макете дизайна элементов
 */
function fClouds_Design_Get(&$layout, $field_value, $field_name, $field_place = 'component')
{
	$options = array();
    $res = sql_query('SELECT categs.cat_title, temps.ct_id, temps.ct_title FROM sb_categs categs, sb_catlinks links, sb_clouds_temps temps WHERE temps.ct_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, temps.ct_title', 'pl_clouds');
    if ($res)
    {
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $ct_id, $ct_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$ct_id] = $ct_title;
        }

        $fld = new sbLayoutSelect($options, $field_name);
        $fld->mSelOptions = array($field_value);
    }
    else
    {
        $fld = new sbLayoutLabel('<div class="hint_div">'.PL_CLOUDS_H_ELEM_CLOUD_NO_TEMPS_MSG.'</div>', '', '', false);
    }
	if($field_place == 'component')
    	$layout->addField(PL_CLOUDS_H_DESIGN_GET, $fld);
    elseif($field_place == 'element')
    	$layout->addField(PL_CLOUDS_H_DESIGN_GET_COMPONENT, $fld);

    return (count($options) > 0);
}
?>