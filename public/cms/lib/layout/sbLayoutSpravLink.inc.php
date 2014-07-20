<?php
/**
 * Класс, отвечающий за вывод двух связанных списков
 *
 * @see sbLayout
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Layout
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbLayoutSpravLink
{
    /**
     * ID выбранного значения первого списка
     *
     * @var string
     */
    private $mFirstValue = '';

    /**
     * ID выбранного значения второго списка
     *
     * @var string
     */
    private $mSecondValue = '';

    /**
     * Имя поля (значение атрибута <i>name</i>) первого списка
     *
     * Для второго списка прибавляется суффикс <i>_link</i>
     *
     * @var string
     */
    private $mName = '';

    /**
     * Идентификатор поля (значение атрибута <i>id</i>) первого списка
     *
     * Для второго списка прибавляется суффикс <i>_link</i>
     *
     * @var string
     */
    private $mId = '';

    /**
     * Дополнительные св-ва поля (например, стили) первого списка
     *
     * @var string
     */
    private $mFirstDopStr = '';

    /**
     * Дополнительные св-ва поля (например, стили) второго списка
     *
     * @var string
     */
    private $mSecondDopStr = '';

    /**
     * Дополнительный HTML-код, вставляемый после полей
     *
     * @var string
     */
    public $mHTML = '';

    /**
     * Выводить (TRUE) или нет (FALSE) значения из вложенных справочников
     *
     * @var boolean
     */
    public $mSubCategs = true;

    /**
     * Уникальный идентификатор справочника, значения которого будут выводиться
     *
     * @var array
     */
    private $mSpravId = '';

    /**
     * Наименование связанного списка
     *
     * @var string
     */
    public $mSpravTitle = '';

    /**
     * Кол-во строк в первом списке
     *
     * @var integer
     */
    public $mFirstRows = 1;

    /**
     * Кол-во строк во втором списке
     *
     * @var integer
     */
    public $mSecondRows = 1;

    /**
     * Поле обязательно для заполнения (TRUE) или нет (FALSE)
     *
     * @var bool
     */
    private $mRequired = false;

    /**
     * Поле справочника, использующееся в качестве названия опции второго списка
     *
     * @var string
     */
    public $mTitleFld = 's_title';

    /**
     * Возможность редактирования справочников "на лету"
     * @var boolean
     */
    public $mShowEditLink = true;

    /**
     * Конструктор класса
     *
     * @param integet $sprav_id Уникальный идентификатор справочника, значения которого будут выводиться.
     * @param string $value ID выбранных значений списков (через запятую, первым идет значение первого списка соотв.).
     * @param string $name Имя поля (значение атрибута <i>name</i>).
     * @param string $id Идентификатор поля (значение атрибута <i>id</i>).
     * @param string $first_dop_str Дополнительные св-ва первого списка (например, стили).
     * @param string $second_dop_str Дополнительные св-ва второго списка (например, стили).
     * @param bool $required Поле обязательно для заполнения (TRUE) или нет (FALSE).
     */
    public function __construct($sprav_id='', $value='', $name='', $id='', $first_dop_str='', $second_dop_str='', $required=false)
    {
        if ($id == '') $id = $name;

        if ($value != '')
        {
            $value = explode(',', $value);

            $this->mFirstValue = intval($value[0]);
            $this->mSecondValue = isset($value[1]) ? intval($value[1]) : '';
        }

        $this->mSpravId = intval($sprav_id);
        $this->mName = $name;
        $this->mId = $id;
        $this->mFirstDopStr = $first_dop_str;
        $this->mSecondDopStr = $second_dop_str;
        $this->mRequired = $required;
    }

    /**
     * Возвращает HTML-код поля
     *
     * @return string HTML-код поля.
     */
    public function getField()
    {
        $this->mTitleFld = preg_replace('/[^A-Za-z_0-9]/', '', $this->mTitleFld);

        $res = sql_query('SELECT cat_left, cat_right, cat_level FROM sb_categs WHERE cat_id=?d', $this->mSpravId);
        if (!$res)
            return '';

        list ($left, $right, $level) = $res[0];

        $checkLevel = 'cat_level > '.$level;
        $first_res = sql_query('SELECT cat_id, cat_title, cat_left, cat_right, cat_level FROM sb_categs WHERE cat_left > '.$left.' AND cat_right < '.$right.' AND cat_ident=? AND '.$checkLevel.' ORDER BY cat_left', 'pl_sprav');

        if (!$first_res)
            return '';

        $left = 0;
        $right = 0;
        $found = false;
        if ($this->mFirstValue != '')
        {
            foreach ($first_res as $value)
            {
                if ($value[0] == $this->mFirstValue)
                {
                    $left = $value[2];
                    $right = $value[3];
                    $found = true;
                    break;
                }
            }
        }

        if (!$found)
        {
            $this->mFirstValue = $first_res[0][0];
            $left = $first_res[0][2];
            $right = $first_res[0][3];
        }

        $second_res = false;
        if ($this->mSubCategs)
        {
            $second_res = sql_query('SELECT DISTINCT s.s_id, s.'.$this->mTitleFld.' FROM sb_sprav s, sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND c.cat_left >= ?d AND c.cat_right <= ?d AND l.link_cat_id=c.cat_id AND l.link_el_id=s.s_id ORDER BY s.s_sort, s.'.$this->mTitleFld, 'pl_sprav', $left, $right);
        }
        else
        {
            $second_res = sql_query('SELECT DISTINCT s.s_id, s.'.$this->mTitleFld.' FROM sb_sprav s, sb_catlinks l WHERE l.link_cat_id = ?d AND l.link_el_id=s.s_id ORDER BY s.s_sort, s.'.$this->mTitleFld, $this->mFirstValue);
        }

        $result = '<input type="hidden" id="'.$this->mId.'_subcateg" value="'.($this->mSubCategs ? '1' : '0').'">
            <input type="hidden" id="'.$this->mId.'_fld" value="'.$this->mTitleFld.'">
            <select name="'.$this->mName.'" id="'.$this->mId.'" onchange="sbGetLinkData(this)" size="'.$this->mFirstRows.'"'.($this->mFirstDopStr != '' ? ' '.$this->mFirstDopStr : '').'>';

        foreach ($first_res as $value)
        {
            list($cat_id, $cat_title) = $value;
            $result .= '<option value="'.$cat_id.'"'.($cat_id == $this->mFirstValue ? ' selected="selected"' : '').'>'.$cat_title.'</option>';
        }
        $result .= '</select><br><br>'.($this->mSpravTitle != '' ? '<b>'.$this->mSpravTitle.':</b><br><br>' : '');

        $result .= '<select name="'.$this->mName.'_link" id="'.$this->mId.'_link" size="'.$this->mSecondRows.'"'.($this->mSecondDopStr != '' ? ' '.$this->mSecondDopStr : '').'>
            <option value="-1"> --- </option>';

        if ($second_res)
        {
            foreach ($second_res as $value)
            {
                list($s_id, $s_title) = $value;
                $result .= '<option value="'.$s_id.'"'.($s_id == $this->mSecondValue ? ' selected="selected"' : '').'>'.$s_title.'</option>';
            }
        }

        $result .= '</select>'.($this->mRequired ? ' <sup class="red">*</sup>' : '');
        if($this->mShowEditLink && sb_strpos($this->mFirstDopStr, 'disabled="disabled"') === false && sb_strpos($this->mSecondDopStr, 'disabled="disabled"') === false)
        {
            $result .= '<br/><a href="#" onclick="editSpravLink(\''.$this->mSpravId.'\', '.($this->mSubCategs ? '1' : '0').', \''.$this->mId.'\')" class="small" style="background: url(/cms/images/hdelim.gif) 0 100% repeat-x">'.SB_LAYOUT_EDIT_SPRAV_LINK.'</a>';
        }

        return $result.$this->mHTML;
    }

    /**
     * Возвращает JavaScript-код поля
     *
     * @return string JavaScript-код поля.
     */
    public function getJavaScript()
    {
        static $echo = true;

        if ($echo)
        {
            $echo = false;
            return '<script>

                sbSpravLinkIds = null;
                sbSpravLinkElId = null;

                function sbGetLinkData(el)
                {
                    var link_el = sbGetE(el.id + "_link");
                    var subcateg_el = sbGetE(el.id + "_subcateg");
                    var fld_el = sbGetE(el.id + "_fld");

                    if (link_el && subcateg_el)
                    {
                        while (link_el.options.length > 1)
                            link_el.remove(1);

                        var options = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_sprav_get_options&cat_id="+el.value+"&subcategs="+subcateg_el.value+"&fld="+fld_el.value);
                        if (options && options != "")
                        {
                            options = options.split("::");
                            for (var i = 0; i < options.length; i++)
                            {
                                var oOption = document.createElement("OPTION");
                                link_el.options.add(oOption);

                                var opt = options[i].split("^^");
                                oOption.value = opt[0];
                                oOption.innerHTML = opt[1];
                            }
                        }
                    }
                }

                function editSpravLink(sprav_ids, subcategs, el_id)
                {
                    sbSpravLinkIds = sprav_ids;
                    sbSpravLinkElId = el_id;
                    var strPage = sb_cms_modal_dialog_file + "?event=pl_sprav_init&show_only="+sprav_ids+"&subcategs="+subcategs;
                    var strAttr = "resizable=1,width=900,height=700";

                    sbShowModalDialog(strPage, strAttr, sbAfterEditSpravLink);
                }

                function sbAfterEditSpravLink()
                {
                    sbLoadAsync(sb_cms_empty_file+"?event=pl_sprav_field_reload&fld_type=sprav_link&sprav_ids="+sbSpravLinkIds+"&fld_name="+sbSpravLinkElId+"&id='.(isset($_GET['id'])?$_GET['id']:'').'"+"&cat_id='.(isset($_GET['cat_id'])?$_GET['cat_id']:'').'", sbRefreshLinkFld);
                }

                function sbRefreshLinkFld(res)
                {
                    if(res != "FALSE")
                    {
                        var container = sbGetE(sbSpravLinkElId+"_td");
                        container.innerHTML = res;
                    }
                }
                </script>';
        }
        else
        {
            return '';
        }
    }
}
