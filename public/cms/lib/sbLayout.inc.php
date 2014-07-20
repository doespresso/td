<?php
/**
 * Реализация набора классов, отвечающих за вывод форм данных и построение таблиц и других элементов
 * интерфейса системы.
 *
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Layout
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Класс, отвечающий за вывод форм и таблиц в системе
 *
 * Поля формы могут быть разбиты на закладки. Каждое поле формы представлено своим классом.
 * См. соотв. документацию.
 *
 * @see sbLayout
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_Layout
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbLayout
{
    /**
     * Атрибут <i>action</i> формы
     *
     * Если не указан, то тег <i>form</i> добавлен не будет.
     *
     * @var string
     */
    private $mFormAction = '';

    /**
     * Атрибут <i>target</i> формы
     *
     * @var string
     */
    private $mFormTarget = '';

    /**
     * JavaScript функция, вызываемая перед отправкой данных формы
     *
     * Обычно используется для проверки введенных пользователем данных.
     *
     * @var string
     */
    private $mFormOnSubmit = '';

    /**
     * Атрибут <i>method</i> формы
     *
     * @var string
     */
    private $mFormMethod = '';

    /**
     * Дополнительные св-ва формы (например, стили)
     *
     * @var string
     */
    private $mFormDopStr = '';

    /**
     * Массив объектов типа sbLayoutButton (кнопки, выводимые внизу формы)
     *
     * @var array
     */
    private $mButtons = array();

    /**
     * Массив заголовков для закладок формы
     *
     * @var array
     */
    private $mTabs = array();

    /**
     * JavaScript-функция, вызываемая после загрузки содержимого закладки
     *
     * @var string
     */
    public $mTabsOnLoadTab = '';

    /**
     * JavaScript-функция, вызываемая после загрузки и изменения размеров контейнера с закладками
     *
     * @var string
     */
    public $mTabsOnLoad = '';

    /**
     * Массив полей формы
     *
     * Массив, каждый элемент которого представляет собой массив вида <i>array(tabIndex, fieldTitle, filedObject, thStr, tdStr, trStr)</i>,
     * где tab_index - индекс текущей закладки, field_title - заголовок поля, field_object - объект типа sbLayoutText, sbLayoutTextarea и т.д.,
     * thStr - доп. свойства тегов <i>th</i>, tdStr - доп. свойства тегов <i>td</i>, trStr - доп. свойства тегов <i>tr</i>.
     *
     * @var array
     */
    private $mFields = array();

    /**
     * Используются ли в выводе подгружаемые подсказки (TRUE) или нет (FALSE)
     *
     * @var boolean
     */
    public $mShowInfo = false;

    /**
     * Ширина основной таблицы
     *
     * @var string
     */
    public $mTableWidth = '98%';

    /**
     * Ширина столбца заголовков полей
     *
     * @var string
     */
    public $mTitleWidth = '150';

    /**
     * Отображать или нет столбец заголовков полей
     *
     * @var bool
     */
    public $mShowTitle = true;

    /**
     * Отображать или нет двоеточие в столбце заголовков полей
     *
     * @var bool
     */
    public $mShowColon = false;

    /**
     * Javascript-код, выполняемый при отправке формы
     *
     * @var string
     */
    private $mOnSubmitJavascriptStr = '';

    /**
     * Javascript-код, выполняемый при открытии формы
     *
     * @var string
     */
    private $mOnLoadJavascriptStr = '';

    /**
     * Событие и параметры, вызываемое для подгрузки закладок через AJAX
     *
     * Данное событие должно возвращать HTML-код подгружаемой закладки. ID закладки передается событию
     * через $_GET['tab_id'].
     *
     * @var string
     */
    private $mAutoLoadingURL = '';

    /**
     * Массив, содержащий загруженные на сервер файлы через пользовательские поля
     *
     * Используется для удаления файлов в случае не успешного сохранения пользовательских полей в БД.
     *
     * @var array
     */
    private $mUploadedFiles = array();

    /**
     * Конструктор класса
     *
     * @param string $action Атрибут <i>action</i> формы.
     * @param string $target Атрибут <i>target</i> формы.
     * @param string $method Атрибут <i>method</i> формы.
     * @param string $on_submit JavaScript функция, вызываемая перед отправкой данных формы.
     * @param string $id Атрибут <i>id</i> формы.
     * @param string $dop_str Дополнительные св-ва формы (например, стили).
     */
    public function __construct($action='', $target='thisDialog', $method='post', $on_submit='checkValues()', $id='', $dop_str='')
    {
        $this->mFormAction = $action;
        if ($target == 'thisDialog' && function_exists('browser_get_agent'))
        {
            if (browser_get_agent() != 'IE')
            {
                $target = '_self';
            }
        }

        $this->mFormTarget = $target;
        $this->mFormMethod = $method;
        $this->mFormOnSubmit = $on_submit;
        $this->mFormDopStr = $dop_str;
        $this->mFormId = ($id != '' ? $id : 'main');
    }

    /**
     * Добавляет новую кнопку в форму
     *
     * Кнопки выводятся внизу формы.
     *
     * @param string $type Тип кнопки (значение атрибута <i>type</i>).
     * @param string $value Текст кнопки (значение атрибута <i>value</i>).
     * @param string $name Имя кнопки (значение атрибута <i>name</i>).
     * @param string $id Идентификатор кнопки (значение атрибута <i>id</i>).
     * @param string $dop_str Дополнительные св-ва кнопки (например, стили).
     */
    public function addButton($type='submit', $value, $name='', $id='', $dop_str='')
    {
        $this->mButtons[] = new sbLayoutInput($type, $value, $name, $id, $dop_str);
    }

    /**
     * Добавляет новую закладку в форму и запоминает ее индекс (для последующего добавления полей)
     *
     * @param string $title Заголовок закладки.
     * @param bool $show Показывать закладку (TRUE) или нет (FALSE).
     */
    public function addTab($title, $show = true)
    {
        $i = count($this->mTabs);
        $this->mTabs[$i] = array();
        $this->mTabs[$i]['title'] = ($i % 5 == 0 && $i != 0) ? '$'.$title : $title;
        $this->mTabs[$i]['show'] = $show;
    }

    /**
     * Добавляет новое поле в форму
     *
     * @param string $title Заголовок поля.
     * @param mixed $field Поле (объект типа sbLayoutText, sbLayoutTextarea и т.д.).
     * @param string $th_str Доп. свойства тегов <i>th</i>.
     * @param string $td_str Доп. свойства тегов <i>td</i>.
     * @param string $tr_str Доп. свойства тегов <i>tr</i>.
     */
    public function addField($title, &$field, $th_str = '', $td_str = '', $tr_str = '')
    {
        $i = count($this->mTabs) - 1;
        $this->mFields[] = array($i, $title, $field, $th_str, $td_str, $tr_str);
    }

    /**
     * Проверка, есть поле с указанным типом или нет
     *
     * @param string $type Тип поля.
     *
     * @return bool TRUE, если поле есть, FALSE в ином случае.
     */
    public function fieldTypeExists($type)
    {
        foreach ($this->mFields as $value)
        {
            if ($value[2] instanceof $type)
                return true;
        }

        return false;
    }

    /**
     * Добавляет новый заголовок в форму
     *
     * @param $title Текст заголовка.
     */
    public function addHeader($title)
    {
        $i = count($this->mTabs) - 1;
        $this->mFields[] = array($i, $title, false, '', '', '');
    }

    /**
     * Выводит пользовательские поля, настраиваемые в Макетах данных модулей
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param int $id Уникальный идентификатор элемента, если элемент редактируется, или раздела, если раздел редактируется.
     * @param string $id_name Наименование поля таблицы, где хранится уникальный идентификатор элемента. Для разделов '';
     * @param bool $categs Выводить поля разделов (TRUE) или элементов (FALSE).
     * @param bool $edit_group функция вызвана для группового редактирования (TRUE) иначе (FALSE).
     *
     * @return bool TRUE, если пользовательские поля найдены, FALSE - в ином случае.
     */
    public function getPluginFields($ident, $id = -1, $id_name = '', $categs = false, $edit_group = false)
    {
        $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
        if(!isset($plugins[$ident]))
            return false;

        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || $res[0][0] == '')
        {
            return false;
        }

        $fields = unserialize($res[0][0]);
        if (!$fields || count($fields) <= 0)
        {
            return false;
        }

        $values = array();
        $keys = array();
        $sql = '';

        $num_post = count($_POST);
        foreach ($fields as $value)
        {
            if (isset($value['sql']) && $value['sql'] == 1)
            {
                $keys[] = $value['id'];
                $sql .= 'user_f_'.$value['id'].',';

                if ($num_post > 0)
                {
                    switch ($value['type'])
                    {
                        case 'checkbox':
                            $values['user_f_'.$value['id']] = isset($_POST['user_f_'.$value['id']]) && $_POST['user_f_'.$value['id']] > 0 ? '1' : '0';
                            break;

                        case 'checkbox_sprav':
                            if (isset($_POST['user_f_'.$value['id']]) && is_array($_POST['user_f_'.$value['id']]))
                                $values['user_f_'.$value['id']] = implode(',', $_POST['user_f_'.$value['id']]);
                            break;

                        case 'date':
                            if (isset($_POST['user_f_'.$value['id']]))
                                $values['user_f_'.$value['id']] = $_POST['user_f_'.$value['id']] != '' ? sb_datetoint($_POST['user_f_'.$value['id']]) : '';
                            break;

                        case 'link_sprav':
                            if (isset($_POST['user_f_'.$value['id']]) && isset($_POST['user_f_'.$value['id'].'_link']))
                                $values['user_f_'.$value['id']] = $_POST['user_f_'.$value['id']].','.$_POST['user_f_'.$value['id'].'_link'];
                            else
                                $values['user_f_'.$value['id']] = '';
                            break;

                         case 'table':
                            if (isset($_POST['user_f_'.$value['id']]))
                                $values['user_f_'.$value['id']] = $_POST['user_f_'.$value['id']] != '' ? $_POST['user_f_'.$value['id']] : '';
                           else
                                $values['user_f_'.$value['id']] = '';
                            break;

                        default:
                            if (isset($_POST['user_f_'.$value['id']]))
                            {
                                $values['user_f_'.$value['id']] = $_POST['user_f_'.$value['id']];
                            }
                            break;
                    }
                }
            }

            if (!isset($values['user_f_'.$value['id']]))
            {
                $values['user_f_'.$value['id']] = '';
                if (isset($value['settings']))
                {
                    if (isset($value['settings']['default']))
                    {
                        $values['user_f_'.$value['id']] = $value['settings']['default'];
                    }
                    elseif (isset($value['settings']['default_latitude']) && isset($value['settings']['default_longtitude']))
                    {
                        $values['user_f_'.$value['id']] = $value['settings']['default_latitude'].'|'.$value['settings']['default_longtitude'];
                    }
                }
            }
        }

        if ($num_post == 0 && $id != -1 && $id != '' && !$edit_group)
        {
            if (!$categs && $sql != '')
            {
                $sql = substr($sql, 0, -1);
                // получаем значения элементов
                $res = sql_query('SELECT '.$sql.' FROM '.$plugins[$ident]['table'].' WHERE ?#=?d', $id_name, $id);
                if ($res)
                {
                    for ($i = 0; $i < count($res[0]); $i++)
                    {
                        if (!is_null($res[0][$i]))
                            $values['user_f_'.$keys[$i]] = $res[0][$i];
                        else
                            $values['user_f_'.$keys[$i]] = '';
                    }
                }
            }
            elseif ($categs)
            {
                // получаем значения разделов
                $res = sql_query('SELECT cat_fields FROM sb_categs WHERE cat_id=?d', $id);
                if ($res)
                {
                    list($cat_fields) = $res[0];
                    if ($cat_fields != '')
                        $values = array_merge($values, unserialize($cat_fields));
                }
            }
        }

        $google_map_js = false;
        $yandex_map_js = false;

        foreach ($fields as $value)
        {
            $type = $value['type'];
            $settings = array();

            $f_name = 'user_f_'.$value['id'];
            $f_value = isset($values[$f_name]) ? $values[$f_name] : '';
            $f_mandatory = isset($value['mandatory']) && $value['mandatory'] == 1 ? true : false;
            $f_title = isset($value['title']) ? $value['title'] : '';
            $f_style = '';
            $f_attribs = isset($value['attribs']) ? $value['attribs'] : '';

            if (isset($value['settings']) && $value['settings'] != '')
            {
                $settings = $value['settings'];

                $field_right = 'edit';
                $f_right = $_SESSION['sbAuth']->getFieldRight($ident, array($f_name), array('view', 'edit'), $categs, $fields);

                if(isset($f_right[$f_name]['view']) && $f_right[$f_name]['view'] == 0 &&
                   isset($f_right[$f_name]['edit']) && $f_right[$f_name]['edit'] == 0)
                {
                    continue;
                }
                elseif(isset($f_right[$f_name]['view']) && $f_right[$f_name]['view'] == 1 &&
                       isset($f_right[$f_name]['edit']) && $f_right[$f_name]['edit'] == 0)
                {
                    $f_attribs .= ' disabled="disabled"';
                    $field_right = 'view';
                }

                if (isset($settings['width']) && $settings['width'] != '')
                {
                    $f_style .= 'width: '.$settings['width'].';';
                }

                if (isset($settings['widths']) && $settings['widths'] != '')
                {
                    $f_style .= 'width: '.$settings['widths'].';';
                }

                if (isset($settings['height']) && $settings['height'] != '')
                {
                    $f_style .= 'height: '.$settings['height'].';';
                }

                $matches = array();
                if (preg_match_all('/style[\s]*=[\s]*["\']([^"\'].*?)["\']/si'.SB_PREG_MOD, $f_attribs, $matches))
                {
                    foreach($matches[1] as $style)
                    {
                        $f_style .= $style;
                    }

                    $f_attribs = trim(preg_replace('/style[\s]*=[\s]*["\']([^"\'].*?)["\']/si'.SB_PREG_MOD, '', $f_attribs));
                }

                if ($f_style != '')
                    $f_style = 'style="'.$f_style.'"';

                $f_style .= ' '.$f_attribs;
            }

            switch ($type)
            {
                case 'file':
                    $fld = new sbLayoutFile($f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mLocal = (isset($settings['local']) && $settings['local'] == 1);
                    $fld->mShow = (isset($settings['show']) && $settings['show'] == 1);
                    $fld->mFileTypes = $settings['file_types'];
                    $fld->mReadOnly = ($field_right != 'edit');

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'longtext':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mShowEditorBtn = ($field_right == 'edit' && $settings['html'] == 1);
                    if(isset($settings['char_count']))
                        $fld->mShowCharCount = $settings['char_count'] == 1;
                    if(isset($settings['max_length']))
                        $fld->mMaxValue = $settings['max_length'];

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'image':
                    $fld = new sbLayoutImage($f_value, $f_name, '', $f_style, $f_mandatory, $settings);
                    $fld->mLocal = $settings['local'] == 1;
                    $fld->mShow = $settings['show'] == 1;
                    $fld->mFileTypes = $settings['file_types'];
                    $fld->mReadOnly = ($field_right != 'edit');

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    $_SESSION[$f_name] = $settings;
                    break;

                case 'date':
                    if ($f_value == 'current')
                    {
                        $f_value = time();
                    }

                    if ($f_value != '')
                    {
                        if ($settings['time'] == 1)
                        {
                            $f_value = sb_date('d.m.Y H:i', $f_value);
                        }
                        else
                        {
                            $f_value = sb_date('d.m.Y', $f_value);
                            $f_style = 'style="width: 80px;"';
                        }
                    }

                    $fld = new sbLayoutDate($f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mDropButton = ($field_right == 'edit' && $settings['drop_btn'] == 1);
                    $fld->mReadOnly = ($field_right != 'edit');
                    $fld->mShowTime = $settings['time'] == 1;

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'password':
                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, new sbLayoutInput('password', $f_value, $f_name, '', ($settings['max_length'] != '' ? 'maxlength="'.$settings['max_length'].'" ' : '').$f_style, $f_mandatory), 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'string':
                    $fld = new sbLayoutInput($settings['hidden'] == 1 ? 'hidden' : 'text', $f_value, $f_name, '', ($settings['max_length'] != '' ? 'maxlength="'.$settings['max_length'].'" ' : '').$f_style, $f_mandatory && $settings['hidden'] != 1);
                    if(isset($settings['char_count']))
                        $fld->mShowCharCount = $settings['char_count'] == 1;
                    if(isset($settings['max_length']))
                        $fld->mMaxValue = $settings['max_length'];

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, ($settings['hidden'] == 1 ? '' : 'id="'.$f_name.'_th"'), ($settings['hidden'] == 1 ? '' : 'id="'.$f_name.'_td"'), ($settings['hidden'] == 1 ? '' : 'id="'.$f_name.'_tr"'));
                    break;

                case 'link':
                    $fld = new sbLayoutPage($f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mReadOnly = ($field_right != 'edit');

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'text':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mShowEditorBtn = ($field_right == 'edit' && $settings['html'] == 1);
                    if(isset($settings['char_count']))
                        $fld->mShowCharCount = $settings['char_count'] == 1;
                    if(isset($settings['max_length']))
                        $fld->mMaxValue = $settings['max_length'];

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'checkbox':
                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, new sbLayoutInput('checkbox', '1', $f_name, '', ($f_value == 1 ? 'checked="checked" ' : '').$f_style), 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'color':
                    $fld = new sbLayoutColor($f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mDropButton = $settings['drop_btn'] == 1;
                    $fld->mReadOnly = ($field_right != 'edit');

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'number':
                    $fld = new sbLayoutInput('text', $f_value, $f_name, 'spin_'.$f_name, $f_style, $f_mandatory);
                    $fld->mMinValue = $settings['min_value'];
                    $fld->mMaxValue = $settings['max_value'];
                    $fld->mIncrement = $settings['increment'];

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'google_coords':
                case 'yandex_coords':
                    $latitude = '';
                    $longtitude = '';
                    $showLink = false;

                    if(isset($settings['geocoding_fld']))
                    {
                        $tmp_fields = $fields;
                        $tmp_id = sb_str_replace('user_f_', '', $settings['geocoding_fld']);
                        foreach($tmp_fields as $tmp_val)
                        {
                            if($tmp_val['id'] == $tmp_id)
                            {
                                $showLink = true;
                                break;
                            }
                        }
                        unset($tmp_fields);
                    }

                    if ($f_value != '')
                    {
                        $f_value = explode('|', $f_value);
                        if (isset($f_value[0]))
                        {
                            $latitude = $f_value[0];
                        }
                        if (isset($f_value[1]))
                        {
                            $longtitude = $f_value[1];
                        }
                    }

                    $html = '';
                    if ($type == 'google_coords' && $settings['show_map'] == 1)
                    {
                        $html = '<div id="'.$f_name.'_google_map" style="width:'.(isset($settings['map_width']) && trim($settings['map_width']) != '' ? $settings['map_width'] : '300px').';height:'.(isset($settings['map_height']) && trim($settings['map_height']) != '' ? $settings['map_height'] : '300px').';border: 1px solid black;"></div>';
                        if (!$google_map_js)
                        {
                            $html .= '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
                            <script>
                                function change_google_coords_map(f_name)
                                {
                                    var gMarker = eval(f_name + "_gMarker");
                                    var gMap = eval(f_name + "_gMap");
                                    if (gMarker && gMap)
                                    {
                                        var lat = sbGetE("spin_" + f_name + "_latitude").value;
                                        var lng = sbGetE("spin_" + f_name + "_longtitude").value;
                                        var gLatLng = new google.maps.LatLng(lat, lng);

                                        gMarker.setPosition(gLatLng);
                                        gMap.setCenter(gLatLng);
                                    }
                                }
                            </script>';
                            $google_map_js = true;
                        }

                        $html .= '<script>
                            if (typeof(sbTabsWaitLoad) == "undefined")
                                var sbTabsWaitLoad = 0;

                            sbTabsWaitLoad++;

                            var '.$f_name.'_gMarker = false;
                            var '.$f_name.'_gMap = false;
                            function '.$f_name.'_google_map_load(marker)
                            {
                                if(typeof(marker) == "undefined" || typeof(marker.Za) == "undefined" || typeof(marker.Ya) == "undefined")
                                {
                                    var gLat = "'.($latitude != '' ? $latitude : '55.755917028179084').'";
                                    var gLng = "'.($longtitude != '' ? $longtitude : '37.6167314605713').'";
                                }
                                else
                                {
                                    var gLat = marker.Ya;
                                    var gLng = marker.Za;
                                }

                                var elLat = sbGetE("spin_'.$f_name.'_latitude");
                                var elLng = sbGetE("spin_'.$f_name.'_longtitude");
                                if (elLat && elLng)
                                {
                                    elLat.value = gLat;
                                    elLng.value = gLng;
                                }

                                var gLatLng = new google.maps.LatLng(gLat, gLng);
                                var gOptions = {
                                  zoom: 15,
                                  center: gLatLng,
                                  mapTypeId: google.maps.MapTypeId.ROADMAP
                                }

                                '.$f_name.'_gMap = new google.maps.Map(sbGetE("'.$f_name.'_google_map"), gOptions);
                                var gMap = '.$f_name.'_gMap;
                                gMap.mapTypeControlOptions = {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU};
                                gMap.navigationControlOptions = {style: google.maps.NavigationControlStyle.SMALL};

                                '.$f_name.'_gMarker = new google.maps.Marker({'.($field_right == 'edit' ? 'draggable: true,' : '').' position: gLatLng, map: gMap});
                                var gMarker = '.$f_name.'_gMarker;
                                google.maps.event.addListener(gMarker, "drag", function()
                                {
                                    var lat_lng = gMarker.getPosition();

                                    sbGetE("'.($settings['show_titude'] == 1 ? 'spin_' : '').$f_name.'_latitude").value = lat_lng.lat();
                                    sbGetE("'.($settings['show_titude'] == 1 ? 'spin_' : '').$f_name.'_longtitude").value = lat_lng.lng();
                                });

                                google.maps.event.addListener(gMap, "tilesloaded", function()
                                {
                                    sbTabsWaitLoad--;
                                });
                                ';
                        if(isset($settings['geocoding_fld']) && $settings['geocoding_fld'] != '-1' && isset($settings['load_address']) && $settings['load_address'] == 1)
                        {
                            $html .= '
                                google.maps.event.addListener(gMarker, "dragend", function(){
                                    if(!sbGetE("'.$settings['geocoding_fld'].'"))
                                    {
                                        return false;
                                    }
                                    var position = gMarker.getPosition();
                                    var latlng = new google.maps.LatLng(position.Xa, position.Ya);
                                    var geocoder = new google.maps.Geocoder();

                                    geocoder.geocode({"latLng": latlng}, function(results, status) {
                                        if (status == google.maps.GeocoderStatus.OK)
                                        {
                                            if (results[0])
                                            {
                                                sbGetE("'.$settings['geocoding_fld'].'").value = results[0].formatted_address;
                                            }
                                        }
                                    });
                                });
                            ';
                        }

                        $html .='
                            }

                            google.maps.event.addDomListener(window, "load", '.$f_name.'_google_map_load);
                        </script>';

                        if(isset($settings['geocoding_fld']) && $settings['geocoding_fld'] != '-1')
                        {
                            $html .= '
                                <script type="text/javascript">
                                    function getGoogleCoords(fld_id)
                                    {
                                        if(!sbGetE(fld_id))
                                        {
                                            alert("'.KERNEL_POST_ADDRESS_NO_MATCH_ERROR.'");
                                            return false;
                                        }

                                        var address = sbGetE(fld_id).value;
                                        address = address.replace(/^\s+(.*)\s$/, "");
                                        if(address == "")
                                        {
                                            alert("'.KERNEL_POST_ADDRESS_ERROR.'");
                                            return false;
                                        }
                                        address = address.replace(" ", "+");
                                        var geocoder = new google.maps.Geocoder();

                                        geocoder.geocode( { "address": address}, function(results, status)
                                        {
                                            if (status == google.maps.GeocoderStatus.OK)
                                            {
                                                var marker = results[0].geometry.location
                                                '.$f_name.'_google_map_load(marker);
                                            } else {
                                                switch(status)
                                                {
                                                    case "ZERO_RESULTS":
                                                        alert("'.KERNEL_GEO_ERROR_ZERO_RESULTS.'");
                                                        break;

                                                    case "OVER_QUERY_LIMIT":
                                                        alert("'.KERNEL_GEO_ERROR_OVER_QUERY_LIMIT.'");
                                                        break;

                                                    case "REQUEST_DENIED":
                                                    case "INVALID_REQUEST":
                                                        alert("'.KERNEL_GEO_ERROR_REQUEST_DENIED.'");
                                                        break;
                                                }
                                            }
                                        });
                                        return false;
                                    }
                                </script>';
                            if($showLink)
                            {
                                $html .= '<a href="#" class="small" style="background: url(/cms/images/hdelim.gif) 0 100% repeat-x;" onclick="getGoogleCoords(\''.$settings['geocoding_fld'].'\')">'.$settings['link_title'].'</a>';
                            }
                        }
                    }
                    elseif ($type == 'yandex_coords' && $settings['show_map'] == 1)
                    {
                        $html = '<div id="'.$f_name.'_yandex_map" style="width:'.(isset($settings['map_width']) && trim($settings['map_width']) != '' ? $settings['map_width'] : '300px').';height:'.(isset($settings['map_height']) && trim($settings['map_height']) != '' ? $settings['map_height'] : '300px').';border: 1px solid black;"></div>';
                        if (!$yandex_map_js)
                        {
                            $html .= '<script src="'.(defined('SB_HTTPS') && SB_HTTPS ? 'https' : 'http').'://api-maps.yandex.ru/2.0/?lang=ru-RU&load=package.full"></script>
                            <script>
                            function change_yandex_coords_map(f_name)
                            {
                                var yMarker = eval(f_name + "_yMarker");
                                var yMap = eval(f_name + "_yMap");
                                if (yMarker && yMap)
                                {
                                    var yLat = sbGetE("spin_" + f_name + "_latitude");
                                    var yLng = sbGetE("spin_" + f_name + "_longtitude");

                                    if(!yLat || !yLng)
                                    {
                                        yLat = sbGetE(f_name + "_latitude");
                                        yLng = sbGetE(f_name + "_longtitude");
                                    }

                                    yLat = yLat.value;
                                    yLng = yLng.value;

                                    yMarker.geometry.setCoordinates([yLat, yLng]);
                                    yMap.setCenter( [yLat, yLng] );
                                }
                            }
                            </script>';
                            $yandex_map_js = true;
                        }

                        $html .= '<script>
                            if (typeof(sbTabsWaitLoad) == "undefined")
                                var sbTabsWaitLoad = 0;

                            sbTabsWaitLoad++;

                            var '.$f_name.'_yMarker = false;
                            var '.$f_name.'_yMap = false;

                            ymaps.ready(function () {
                                '.$f_name.'_yandex_map_load();
                            });

                            function '.$f_name.'_yandex_map_load()
                            {
                                var yLat = "'.($latitude != '' ? $latitude : '55.755917028179084').'";
                                var yLng = "'.($longtitude != '' ? $longtitude : '37.6167314605713').'";

                                var elLat = sbGetE("spin_'.$f_name.'_latitude");
                                var elLng = sbGetE("spin_'.$f_name.'_longtitude");
                                if (elLat && elLng)
                                {
                                    elLat.value = yLat;
                                    elLng.value = yLng;
                                }

                                '.$f_name.'_yMap = new ymaps.Map("'.$f_name.'_yandex_map", {
                                    center: [yLat, yLng],
                                    zoom: 10,
                                    behaviors: ["default", "DblClickZoom", "scrollZoom", "Drag", "LeftMouseButtonMagnifier"]
                                });

                                '.$f_name.'_yMap.controls.add("smallZoomControl", { top: 10, left: 10 }).add("typeSelector");

                                '.$f_name.'_yMarker = new ymaps.Placemark(
                                        [yLat, yLng], {
                                            hintContent: "Передвиньте метку для изменения координат."
                                        }, {
                                            '.($field_right == 'edit' ? 'draggable: true' : '').'
                                        }
                                    );

                                '.$f_name.'_yMap.geoObjects.add('.$f_name.'_yMarker);

                                '.$f_name.'_yMarker.events.add("drag", function(e) {
                                    var point_str = new String('.$f_name.'_yMarker.geometry.getBounds());
                                    point_str = point_str.split(",");

                                    sbGetE("'.($settings['show_titude'] == 1 ? 'spin_' : '').$f_name.'_latitude").value = point_str[0];
                                    sbGetE("'.($settings['show_titude'] == 1 ? 'spin_' : '').$f_name.'_longtitude").value = point_str[1];
                                });
                                ';
                        if(isset($settings['geocoding_fld']) && $settings['geocoding_fld'] != '-1'  && isset($settings['load_address']) && $settings['load_address'] == 1)
                        {
                            $html .= $f_name.'_yMarker.events.add("dragend", function(e)
                                {
                                    var point_str = new String('.$f_name.'_yMarker.geometry.getBounds());
                                    point_str = point_str.split(",");
                                    ymaps.geocode(point_str).then(function (res)
                                    {
                                        var names = [];
                                        res.geoObjects.each(function (obj)
                                        {
                                            names.push(obj.properties.get("name"));
                                        });

                                        if(names.length > 4)
                                        {
                                            names[1] = "";
                                        }
                                        sbGetE("'.$settings['geocoding_fld'].'").value = names.join(", ");
                                        sbGetE("'.$settings['geocoding_fld'].'").value = sbGetE("'.$settings['geocoding_fld'].'").value.replace(" , ", " ");
                                    });
                                });
                            ';
                        }

                        $html .='

                                sbTabsWaitLoad--;
                            }
                        </script>';

                        if(isset($settings['geocoding_fld']) && $settings['geocoding_fld'] != '-1')
                        {
                            $html .= '
                                <script type="text/javascript">
                                    function getYandexCoords(fld_id)
                                    {
                                        if(!sbGetE(fld_id))
                                        {
                                            alert("'.KERNEL_POST_ADDRESS_NO_MATCH_ERROR.'");
                                            return false;
                                        }

                                        var address = sbGetE(fld_id).value;
                                        address = address.replace(/^\s+(.*)\s$/, "");
                                        if(address == "")
                                        {
                                            alert("'.KERNEL_POST_ADDRESS_ERROR.'");
                                            return false;
                                        }
                                        address = address.replace(" ", "+");
                                        var geocoder = new ymaps.geocode(address, {results: 1});
                                        geocoder.then(function(res)
                                        {
                                            if (res.geoObjects.getLength())
                                            {
                                                var point = res.geoObjects.get(0);
                                                var Lat = sbGetE("spin_'.$f_name.'_latitude");
                                                var Lng = sbGetE("spin_'.$f_name.'_longtitude");

                                                if(!Lat || !Lng)
                                                {
                                                    Lat = sbGetE("'.$f_name.'_latitude");
                                                    Lng = sbGetE("'.$f_name.'_longtitude");

                                                }

                                                coords = point.geometry.getBounds();
                                                if(coords && coords.length > 0)
                                                {
                                                    Lat.value = coords[0][0];
                                                    Lng.value = coords[0][1];
                                                }
                                                change_yandex_coords_map("'.$f_name.'");
                                            }
                                            else
                                            {
                                                alert("'.KERNEL_GEO_ERROR_ZERO_RESULTS.'");
                                            }
                                        },
                                        function(error){
                                            alert("'.KERNEL_GEO_ERROR.' "+error.message);
                                        });
                                        return false;
                                    }
                                </script>';
                            if($showLink)
                            {
                                $html .= '<a href="#" class="small" style="background: url(/cms/images/hdelim.gif) 0 100% repeat-x;"  onclick="getYandexCoords(\''.$settings['geocoding_fld'].'\')">'.$settings['link_title'].'</a>';
                            }
                        }
                    }

                    if ($settings['show_titude'] == 1)
                    {
                        $fld_latitude = new sbLayoutInput('text', $latitude, $f_name.'_latitude', 'spin_'.$f_name.'_latitude', $f_style.($settings['show_map'] == 1 ? ' onchange="change_'.$type.'_map(\''.$f_name.'\')" onkeyup="change_'.$type.'_map(\''.$f_name.'\')"' : ''), $f_mandatory);
                        $fld_latitude->mIncrement = '0.0000000000001';

                        $fld_longtitude = new sbLayoutInput('text', $longtitude, $f_name.'_longtitude', 'spin_'.$f_name.'_longtitude', $f_style.($settings['show_map'] == 1 ? ' onchange="change_'.$type.'_map(\''.$f_name.'\')" onkeyup="change_'.$type.'_map(\''.$f_name.'\')"' : ''), $f_mandatory);
                        $fld_longtitude->mIncrement = '0.0000000000001';

                        $html .= $fld_latitude->getJavaScript();
                        $html .= '<table cellpadding="5" cellspacing="0"><tr style="background-color: transparent;"><td>'.KERNEL_LATITUDE.'</td><td>'.$fld_latitude->getField().'</td></tr><tr style="background-color: transparent;"><td>'.KERNEL_LONGTITUDE.'</td><td>'.$fld_longtitude->getField().'</td></tr></table>';
                    }
                    else
                    {
                        $fld_latitude = new sbLayoutInput('hidden', $latitude, $f_name.'_latitude', $f_name.'_latitude', $f_style);
                        $fld_longtitude = new sbLayoutInput('hidden', $longtitude, $f_name.'_longtitude', $f_name.'_longtitude', $f_style);

                        $html .= $fld_latitude->getField().$fld_longtitude->getField();
                    }

                    $fld = new sbLayoutHTML($html);
                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $fld->mShowColon = false;
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'jscript':
                    if ($field_right != 'none')
                    {
                        if ($field_right == 'edit')
                            $this->mOnSubmitJavascriptStr .= $f_value;

                        if (isset($settings['code']) && trim($settings['code']) != '')
                            $this->mOnLoadJavascriptStr .= $settings['code'];
                    }
                    break;

                case 'php':
                    if (isset($settings['code']) && trim($settings['code']) != '' && $field_right != 'none')
                    {
                        $GLOBALS['sb_layout'] = $this;
                        $GLOBALS['sb_value'] = $f_value;
                        $this->addField('', new sbLayoutInput('hidden', $f_value, $f_name));
                        eval($settings['code']);
                    }

                    break;
                case 'select_sprav':
                    $fld = new sbLayoutSpravData('select', $settings['sprav_ids'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['subcategs'] == 1);
                    $fld->mRows = $settings['rows'];
                    $fld->mShowEditLink = (isset($settings['editable'])? $settings['editable'] : true);

                    if (isset($settings['sprav_title_fld']))
                    {
                        $fld->mTitleFld = $settings['sprav_title_fld'];
                    }
                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                 case 'select_plugin':
                    $fld = new sbLayoutPluginData('select', $settings['ident'], $settings['modules_cat_id'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = (isset($settings['modules_subcategs']) && $settings['modules_subcategs'] == 1);
                    $fld->mRows = $settings['rows'];

                    if (isset($settings['modules_title_fld']))
                    {
                        $fld->mTitleFld = $settings['modules_title_fld'];
                    }
                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'multiselect_sprav':
                    $fld = new sbLayoutSpravData('multiselect_sprav', $settings['sprav_ids'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['subcategs'] == 1);
                    $fld->mAJAX = (isset($settings['sprav_ajax']) && $settings['sprav_ajax'] == 1);
                    $fld->mSeparator = (isset($settings['separator']) ? $settings['separator'] : ',&nbsp;');
                    $fld->mReadOnly = ($field_right != 'edit');
                    $fld->mShowEditLink = (isset($settings['editable'])? $settings['editable'] : true);

                    if (isset($settings['sprav_title_fld']))
                    {
                        $fld->mTitleFld = $settings['sprav_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'categs':
                    $fld = new sbLayoutCategs($settings['ident'], $settings['cat_id'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mReadOnly = ($field_right != 'edit');
                    $fld->mMultiSelect = (isset($value['multiselect']) && $value['multiselect'] == 1);

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'radio_sprav':
                    $fld = new sbLayoutSpravData('radio', $settings['sprav_ids'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['subcategs'] == 1);
                    $fld->mSeparator = $settings['separator'];
                    $fld->mRows = $settings['count'];
                    $fld->mReadOnly = ($field_right != 'edit');
                    $fld->mShowEditLink = (isset($settings['editable'])? $settings['editable'] : true);

                    if (isset($settings['sprav_title_fld']))
                    {
                        $fld->mTitleFld = $settings['sprav_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'checkbox_sprav':
                    $fld = new sbLayoutSpravData('checkboxes', $settings['sprav_ids'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['subcategs'] == 1);
                    $fld->mSeparator = $settings['separator'];
                    $fld->mRows = $settings['count'];
                    $fld->mReadOnly = ($field_right != 'edit');
                    $fld->mShowEditLink = (isset($settings['editable']) ? $settings['editable'] : true);

                    if (isset($settings['sprav_title_fld']))
                    {
                        $fld->mTitleFld = $settings['sprav_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'link_sprav':
                    $fld = new sbLayoutSpravLink($settings['sprav_id'], $f_value, $f_name, '', $f_style, ($settings['width_link'] != '' ? 'style="width:'.$settings['width_link'].'" ' : '').$f_style, $f_mandatory);
                    $fld->mFirstRows = $settings['rows'];
                    $fld->mSecondRows = $settings['rows_link'];
                    $fld->mSubCategs = ($settings['subcategs'] == 1);
                    $fld->mSpravTitle = $settings['sprav_title'];
                    $fld->mShowEditLink = (isset($settings['editable'])? $settings['editable'] : true);

                    if (isset($settings['sprav_title_fld']))
                    {
                        $fld->mTitleFld = $settings['sprav_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'radio_plugin':
                    $fld = new sbLayoutPluginData('radio', $settings['ident'], $settings['modules_cat_id'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['modules_subcategs'] == 1);
                    $fld->mSeparator = $settings['separator'];
                    $fld->mRows = $settings['count'];
                    $fld->mReadOnly = ($field_right != 'edit');

                    if (isset($settings['modules_title_fld']))
                    {
                        $fld->mTitleFld = $settings['modules_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'checkbox_plugin':
                    $fld = new sbLayoutPluginData('checkboxes', $settings['ident'], $settings['modules_cat_id'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['modules_subcategs'] == 1);
                    $fld->mSeparator = $settings['separator'];
                    $fld->mRows = $settings['count'];
                    $fld->mReadOnly = ($field_right != 'edit');

                    if (isset($settings['modules_title_fld']))
                    {
                        $fld->mTitleFld = $settings['modules_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'multiselect_plugin':
                    $fld = new sbLayoutPluginData('multiselect_plugin', $settings['ident'], $settings['modules_cat_id'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    $fld->mSubCategs = ($settings['modules_subcategs'] == 1);
                    $fld->mAJAX = (isset($settings['modules_ajax']) && $settings['modules_ajax'] == 1);
                    $fld->mReadOnly = ($field_right != 'edit');
                    $fld->mSeparator = $settings['separator'];

                    if (isset($settings['modules_title_fld']))
                    {
                        $fld->mTitleFld = $settings['modules_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                 case 'link_plugin':
                    $fld = new sbLayoutPluginLink($settings['ident'], $settings['modules_cat_id'], $f_value, $f_name, '', $f_style, ($settings['width_link'] != '' ? 'style="width:'.$settings['width_link'].'" ' : '').$f_style, $f_mandatory);
                    $fld->mFirstRows = $settings['rows'];
                    $fld->mSecondRows = $settings['rows_link'];
                    $fld->mSubCategs = ($settings['modules_subcategs'] == 1);
                    $fld->mLinkTitle = $settings['modules_link_title'];

                    if (isset($settings['modules_title_fld']))
                    {
                        $fld->mTitleFld = $settings['modules_title_fld'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');

                    break;

               case 'elems_plugin':
                    $fld = new sbLayoutElement($settings['ident'], $f_value, $f_name, '', $f_style, $f_mandatory);
                    if(isset($settings['modules_cat_id']) && trim($settings['modules_cat_id']) != '')
                    {
                        $fld->mOnlyCats = explode('^', $settings['modules_cat_id']);
                        $subcat = intval($settings['modules_subcategs']);
                        if($subcat == 1)
                        {
                            $leftRight = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id IN (0, '.str_replace('^', ',', $settings['modules_cat_id']).')');
                            if ($leftRight)
                            {
                                $leftRightSql = '(';
                                foreach ($leftRight as $leftRightValue)
                                {
                                    list($leftRightLeft, $leftRightRight) = $leftRightValue;
                                    $leftRightSql .= 'cat_left >= '.$leftRightLeft.' AND cat_right <= '.$leftRightRight.' OR ';
                                }
                                $leftRightSql = substr($leftRightSql, 0, -4).')';

                                $res1 = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident = ?s AND '.$leftRightSql, $settings['ident']);
                                if($res1)
                                {
                                    foreach($res1 as $row)
                                    {
                                        if(!in_array($row[0], $fld->mOnlyCats))
                                        {
                                            $fld->mOnlyCats[] = $row[0];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $fld->mReadOnly = ($field_right != 'edit');

                    echo $fld->getJavaScript();

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($f_title.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'tab':
                    if(count($this->mTabs) == 0)
                    {
                        $this->addTab(KERNEL_MAIN_TABS);
                        for($i = 0; $i < count($this->mFields); $i++)
                        {
                            $this->mFields[$i][0] = 0;
                        }
                    }
                    $this->addTab($f_title);
                    $this->addHeader($f_title);
                    break;

                case 'hr':
                    $this->addField('', new sbLayoutDelim(), 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    break;

                case 'label':
                    if ($settings['hint'] == 1)
                    {
                        $f_value = '<div class="hint_div"'.($f_style != '' ? ' '.$f_style : '').'>'.$f_value.'</div>';
                    }
                    elseif ($f_style != '')
                    {
                        $f_value = '<div '.$f_style.'>'.$f_value.'</div>';
                    }

                    if ($f_title == '')
                    {
                        $this->addField($f_title, new sbLayoutHTML($f_value, true), 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    }
                    else
                    {
                        $this->addField($f_title, new sbLayoutLabel($f_value, $f_name, '', false), 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td"', 'id="'.$f_name.'_tr"');
                    }
                    break;

                case 'table':
                    $settings = $value['settings'];

                    $labels = array(); // массив заголовков
                    $values1 = array(); // массив содержимого ячеек таблицы
                    $rowsCapFilled = false; //флаг, показывающий заполнен ли хоть однин заголовок строки
                    $collsCapFilled = false; //флаг, показывающий заполнен ли хоть однин заголовок строки
                    $js = '';
                    $xml = array();

                    //Проверяю есть ли заголовки строк,
                    //если их нету - первый столбец выводить ненужно
                    foreach($settings['table_rows_name'] as $value2)
                    {
                        if(!empty($value2))
                        {
                            // Если есть хоть один заголовок строки
                            // устанавливаем флаг
                            $rowsCapFilled = true;
                            $labels[] = '';
                            break;
                        }
                    }

                    //Если массив
                    if(is_array($f_value))
                    {
                        foreach($f_value as $key1 => $value1)
                        {
                            foreach($value1 as $key2 => $value2)
                            {
                                if($settings['table_cell_type'] == 'check')
                                    $xml['row'.$key1]['col'.$key2] = $value2;
                                else
                                    $xml['row'.$key1]['col'.($key2+1)] = $value2;
                            }
                        }
                    }//Если строка (xml)
                    elseif(!empty($f_value))
                    {
                        //Парсю xml
                        $xml_ob = simplexml_load_string($f_value);
                        foreach($xml_ob as $key1 => $value1)
                        {
                            foreach($value1 as $key2 =>$value2)
                            {
                                $xml[$key1][$key2] = $value2;
                            }
                        }
                    }

                    //Заголовки
                    for($i = 0; $i < $settings['table_colls']; $i++ )
                    {
                        $labels[] = $settings['table_colls_name'][$i];
                        if(!empty($settings['table_colls_name'][$i])) //Если хотя бы один заголовок столбца заполнен
                        {
                            $collsCapFilled = true;  // Устанавливаю флаг
                        }
                    }

                    if(!$collsCapFilled) //Если не заплнен ни один заголовок столбца
                    {
                        $labels = array(); //Сбрасываю массив заголовков столбцов, содержащий пустые значения
                    }

                    // Двумерный массив содержимого таблицы
                    for($i = 1; $i <= $settings['table_rows']; $i++ )
                    {
                        $str_width = '';
                        $val1 = array();
                        $aligns = array();

                        if($rowsCapFilled)
                        {
                            $aligns[0] = '';
                        }

                        if($rowsCapFilled) //Если нужен первый столбец (столбец с заголовками строк)
                        {
                            $val1[] = '<span class="custom_table_row_cap">'.$settings['table_rows_name'][$i-1].'</span>';
                        }

                        for($j = 1; $j <= $settings['table_colls']; $j++)
                        {
                            $col_value = ''; // Значение поля
                            if(isset($xml['row'.$i]['col'.$j]))
                            {
                                $col_value = $xml['row'.$i]['col'.$j];
                            }

                            if($settings['table_cell_type'] == 'string')
                            {
                                $str_width = '';
                                if(isset($settings['table_cell_settings']['string_width']) && $settings['table_cell_settings']['string_width'] != '')
                                {
                                    $str_width = ' style = "width: '.$settings['table_cell_settings']['string_width'].'" ';
                                }

                                $fld = new sbLayoutInput('text', $col_value, $f_name.'['.$i.'][]','',($settings['table_cell_settings']['string_max'] != '' ? 'maxlength="'.$settings['table_cell_settings']['string_max'].';" ' : '').$str_width.$f_style);
                            }
                            elseif($settings['table_cell_type'] == 'num')
                            {
                                if(isset($settings['table_cell_settings']['num_width']) && $settings['table_cell_settings']['num_width'] != '')
                                {
                                    $str_width = ' style = "width: '.$settings['table_cell_settings']['num_width'].'" ';
                                }

                                if((isset($settings['table_cell_settings']['num_default']) && $settings['table_cell_settings']['num_default'] != '') && ($col_value == '') && (!isset($_GET['id']) || $_GET['id'] == ''))
                                {
                                    $col_value = $settings['table_cell_settings']['num_default'];
                                }

                                $fld = new sbLayoutInput('text', $col_value, $f_name.'['.$i.'][]','spin_'.$j.'_'.$i, $str_width.$f_style);

                                //Устанавливаю свойства для числового типа данных
                                //Минимальное значение
                                if(isset($settings['table_cell_settings']['num_min']) && $settings['table_cell_settings']['num_min'] != '')
                                    $fld -> mMinValue = $settings['table_cell_settings']['num_min'];

                                //Максимальное значение
                                if(isset($settings['table_cell_settings']['num_max']) && $settings['table_cell_settings']['num_max'] != '')
                                    $fld -> mMaxValue = $settings['table_cell_settings']['num_max'];

                                //Инкримент
                                if(isset($settings['table_cell_settings']['num_increment']) && $settings['table_cell_settings']['num_increment'] != '')
                                    $fld -> mIncrement = $settings['table_cell_settings']['num_increment'];
                            }
                            elseif($settings['table_cell_type'] == 'color')
                            {
                                if((isset($settings['table_cell_settings']['color_default']) && $settings['table_cell_settings']['color_default'] != '') && ($col_value == '')&& (!isset($_GET['id']) || $_GET['id'] == ''))
                                {
                                    $col_value = $settings['table_cell_settings']['color_default'];
                                }

                                $fld = new sbLayoutColor($col_value, $f_name.'['.$i.'][]', $f_name.'_'.$j.'_'.$i, 'style="width:80px; background-color:'.$col_value.'"'.$f_style, false);
                                if ($field_right != 'edit')
                                    $fld->mReadOnly = true;

                                $fld->mDropButton = true;

                                $js .= $fld -> getJavaScript();

                                //Кнопка "разрешить сброс значения"
                                if(isset($settings['table_cell_settings']['color_allow_reset']) && $settings['table_cell_settings']['color_allow_reset'] == '1')
                                    $fld -> mDropButton = true;
                            }
                            elseif($settings['table_cell_type'] == 'check')
                            {
                                if((isset($settings['table_cell_settings']['check_default']) && $settings['table_cell_settings']['check_default'] != '') && ($col_value == '') && (!isset($_GET['id']) || $_GET['id'] == ''))
                                {
                                    $col_value = '1';
                                }

                                if(empty($col_value) || !isset($col_value))
                                {
                                    $col_value = 0;
                                }
                                elseif($col_value != '0' && $col_value != '1')
                                {
                                    $col_value = '1';
                                }

                                $fld = new sbLayoutInput('checkbox', $f_name.'_'.$i.'_'.$j, $f_name.'['.$i.']['.$j.']', $f_name.'_'.$i.'_'.$j, ($col_value == 1 ? 'checked="checked" ' : '').$f_style);
                            }
                            elseif($settings['table_cell_type'] == 'date')
                            {   // Если установлен флаг "Текущее время"
                                if((isset($settings['table_cell_settings']['date_default_now']) && $settings['table_cell_settings']['date_default_now'] == '1') && $col_value == '' && (!isset($_GET['id']) || $_GET['id'] == ''))
                                {
                                    $col_value = sb_date("d.m.Y");
                                    if(isset($settings['table_cell_settings']['date_show_time']) && $settings['table_cell_settings']['date_show_time'] == '1')
                                    {
                                        // Если нужно добавить к текущей дате еще и время
                                        $col_value .= sb_date(" H:i");
                                    }
                                }
                                // Если флажек "Текущее время не установлен"
                                elseif((isset($settings['table_cell_settings']['date_default']) && $settings['table_cell_settings']['date_default'] != '') && $col_value == '' && (!isset($_GET['id']) || $_GET['id'] == ''))
                                {
                                    $col_value = $settings['table_cell_settings']['date_default'];
                                    if(!isset($settings['table_cell_settings']['date_show_time']) || $settings['table_cell_settings']['date_show_time'] != '1')
                                    {
                                        // Если нужно добавить к текущей дате еще и время
                                        $col_value = explode(" ", $col_value);
                                        $col_value = $col_value[0];
                                    }
                                }

                                //Устанавливаю свойства для типа "дата"
                                $fld = new sbLayoutDate($col_value, $f_name.'['.$i.'][]', $f_name.'_'.$i.'_'.$j, $f_style, false);
                                echo $fld -> getJavaScript();

                                //Кнопка "разрешить сброс значения"
                                if($field_right == 'edit' && isset($settings['table_cell_settings']['date_allow_reset']) && $settings['table_cell_settings']['date_allow_reset'] == '1')
                                {
                                    $fld -> mDropButton = true;
                                }

                                if ($field_right != 'edit')
                                {
                                    $fld -> mReadOnly = true;
                                }

                                //Отображение времени
                                if(isset($settings['table_cell_settings']['date_show_time']) && $settings['table_cell_settings']['date_show_time'] == '1')
                                    $fld -> mShowTime = true;
                                else
                                    $fld -> mShowTime = false;
                            }
                            $val1[] = $fld->getField();
                            $aligns[] = 'center';
                                                }
                        $values1[] = $val1;
                    }

                    if(isset($js) && !empty($js))
                        echo $js;

                    //Создание таблицы
                    $fld = new sbLayoutTable($labels, $values1);
                    $fld -> mShowTable = true;
                    $fld -> mAlign = $aligns;

                    $table_cap = '';
                    if(isset($settings['table_cell_caption']) && $settings['table_cell_caption'] == 1)
                    {
                        $fld->mShowColon = false;
                        $table_cap = $value['title'];
                    }

                    $edit_group_str = '';
                    if($edit_group)
                    {
                        $ch_field = new sbLayoutInput('checkbox', '1', 'ch_'.$f_name, '', (isset($_POST['ch_'.$f_name]) && $_POST['ch_'.$f_name] == 1 ? 'checked="checked"' : '').'style="margin-left:0;"');
                        $edit_group_str = '<br><div style="float:right;"><div style="float:left;">'.$ch_field->getField().'</div> <div style="float:left;margin-top:1px;font-weight:normal;"><label for="ch_'.$f_name.'">'.KERNEL_WORKFLOW_EDIT_GROUP_TITLE.'</label></div></div>';
                    }
                    $this->addField($table_cap.$edit_group_str, $fld, 'id="'.$f_name.'_th"', 'id="'.$f_name.'_td" class="custom_table_td"', 'id="'.$f_name.'_tr"');
                    break;

                default:
                    break;
            }
        }

        if (isset($GLOBALS['sb_layout']))
            unset($GLOBALS['sb_layout']);

        if (isset($GLOBALS['sb_value']))
            unset($GLOBALS['sb_value']);

        return true;
    }

    /**
     * Проверяет пользовательские поля на корректность заполнения.
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param int $id Уникальный идентификатор элемента, если элемент редактируется, или раздела, если раздел редактируется.
     * @param string $id_name Наименование поля таблицы, где хранится уникальный идентификатор элемента.
     * @param bool $categs Выводить поля разделов (TRUE) или элементов (FALSE).
     * @param bool $edit_group Используется для группового редактирования (TRUE) или нет (FALSE). только для элементов.
     *
     * @return mixed Ассоциативный массив для вставки в базу, если проверка прошла успешно, FALSE - в ином случае.
     */
    public function checkPluginFields($ident, $id = -1, $id_name = '', $categs = false, $edit_group = false)
    {
        $this->mUploadedFiles = array();

        $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
        if (!isset($plugins[$ident]))
        {
            return array();
        }
        $plugin_table = $plugins[$ident]['table'];

        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || $res[0][0] == '')
        {
            // полей нет
            return array();
        }

        $fields = unserialize($res[0][0]);
        $values = array();
        $old_values = array();
        if ($categs && $id != -1 && $id != '')
        {
            $res = sql_query('SELECT cat_fields FROM sb_categs WHERE cat_id=?d', $id);
            if ($res)
            {
                $old_values = unserialize($res[0][0]);
            }
        }

        //Для отложенной генерации миниатюр запоминаем последний индекс массива полей
        $lastIndex = max(array_keys($fields));
        $counter = 0;
        //foreach ($fields as $value)
        while($counter < count($fields))
        {
            $value = $fields[$counter];
            $counter++;
            if(isset($value['sql']) && $value['sql'] == 1)
            {
//              если это групповое редактирование и само поле не редактируется то не проверяем его ни на что.
                $gr_edit_ch = (isset($_POST['ch_user_f_'.$value['id']]) && $_POST['ch_user_f_'.$value['id']] == 1);
                if($edit_group && !$gr_edit_ch)
                {
                    continue;
                }

                //  Проверяю права
                $f_right = $_SESSION['sbAuth']->getFieldRight($ident, array('user_f_'.$value['id']), array('edit'), $categs, $fields);
                if(isset($f_right['user_f_'.$value['id']]['edit']) && $f_right['user_f_'.$value['id']]['edit'] == 0)
                {
                    if(!$edit_group || $edit_group && isset($_POST['ch_user_f_'.$value['id']]) && $_POST['ch_user_f_'.$value['id']] == 1)
                    {
                        if(isset($old_values['user_f_'.$value['id']]))
                        {
                            $values['user_f_'.$value['id']] = $old_values['user_f_'.$value['id']];
                        }
                        elseif ($categs)
                        {
                            $values['user_f_'.$value['id']] = '';
                        }
                    }
                    continue;
                }

                $settings = $value['settings'];
                $f_value = '';
                switch ($value['type'])
                {
                    case 'number':
                        $f_value = isset($_POST['user_f_'.$value['id']]) && trim($_POST['user_f_'.$value['id']]) != '' ? floatval($_POST['user_f_'.$value['id']]) : null;

                        if ($settings['min_value'] != '' && ($f_value === null || $f_value < floatval($settings['min_value'])))
                        {
                            sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_MIN_ERROR, $value['title'], $settings['min_value']), false, 'warning');
                            return false;
                        }

                        if ($settings['max_value'] != '' && $f_value > floatval($settings['max_value']))
                        {
                            sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_MAX_ERROR, $value['title'], $settings['max_value']), false, 'warning');
                            return false;
                        }
                        break;

                    case 'google_coords':
                    case 'yandex_coords':
                        $latitude = isset($_POST['user_f_'.$value['id'].'_latitude']) && trim($_POST['user_f_'.$value['id'].'_latitude']) != '' ? floatval($_POST['user_f_'.$value['id'].'_latitude']) : null;
                        $longtitude = isset($_POST['user_f_'.$value['id'].'_longtitude']) && trim($_POST['user_f_'.$value['id'].'_longtitude']) != '' ? floatval($_POST['user_f_'.$value['id'].'_longtitude']) : null;

                        if (is_null($latitude) && is_null($longtitude))
                        {
                            $f_value = null;
                        }
                        else
                        {
                            $f_value = (is_null($latitude) ? '' : $latitude).'|'.(is_null($longtitude) ? '' : $longtitude);
                        }
                        $_POST['user_f_'.$value['id']] = is_null($f_value) ? '' : $f_value;
                        break;

                    case 'checkbox':
                        $f_value = isset($_POST['user_f_'.$value['id']]) ? '1' : '0';
                        $_POST['user_f_'.$value['id']] = $f_value;
                        break;

                    case 'checkbox_sprav':
                    case 'checkbox_plugin':
                        $f_value = isset($_POST['user_f_'.$value['id']]) && is_array($_POST['user_f_'.$value['id']]) && count($_POST['user_f_'.$value['id']]) > 0 ? implode(',', $_POST['user_f_'.$value['id']]) : null;
                        break;

                    case 'date':
                        $f_value = isset($_POST['user_f_'.$value['id']]) && trim($_POST['user_f_'.$value['id']]) != '' ? sb_datetoint($_POST['user_f_'.$value['id']]) : null;
                        break;

                    case 'elems_plugin':
                        $f_value = isset($_POST['user_f_'.$value['id']]) && trim($_POST['user_f_'.$value['id']]) != '' ? $_POST['user_f_'.$value['id']] : null;

                        if (isset($_POST['user_f_'.$value['id'].'_title']) && trim($_POST['user_f_'.$value['id'].'_title']) == '')
                        {
                            $f_value = null;
                        }
                        break;

                    case 'link_sprav':
                    case 'link_plugin':
                        if (isset($_POST['user_f_'.$value['id']]) && $_POST['user_f_'.$value['id']] > 0 && isset($_POST['user_f_'.$value['id'].'_link']) && $_POST['user_f_'.$value['id'].'_link'] > 0)
                        {
                            $f_value = intval($_POST['user_f_'.$value['id']]).','.intval($_POST['user_f_'.$value['id'].'_link']);
                        }
                        else
                        {
                            $f_value = null;
                        }

                        if ($value['mandatory'] == 1 && is_null($f_value))
                        {
                            sb_show_message($value['mandatory_err'], false, 'warning');
                            return false;
                        }
                        break;

                    case 'php':
                        $GLOBALS['sb_value'] = null;
                        if (isset($settings['submit_code']) && trim($settings['submit_code']) != '')
                        {
                            eval($settings['submit_code']);
                        }
                        elseif ($id != -1 && $id != '')
                        {
                            continue;
                        }

                        $f_value = $GLOBALS['sb_value'];
                        unset($GLOBALS['sb_value']);

                        break;

                    case 'table':
                        $i = 1;
                        $j = 1;
                        $value2='';
                        $value3 = '';
                        $null_check = 0; // Заполненно ли хотя бы одно поле
                        if(isset($_POST['user_f_'.$value['id']]))
                        {
                            $f_value = '<table>';
                            //Создание xml кода для таблицы
                            if($settings['table_cell_type'] == 'string' || $settings['table_cell_type'] == 'color' || $settings['table_cell_type'] == 'num' || $settings['table_cell_type'] == 'date')
                            {
                                foreach($_POST['user_f_'.$value['id']] as $value2)
                                {
                                    //  Строки
                                    $f_value .= '<row'.$i.'>';
                                    foreach ($value2 as $value3)
                                    {
                                        if(isset($value3) && $value3 != '')
                                            $null_check = '1'; //Как минимум одно поле заполненно
                                        //Столбцы
                                        $f_value .= '<col'.$j.'>'.sb_htmlspecialchars($value3).'</col'.$j.'>';
                                        $j++;
                                    }
                                    $f_value .= '</row'.$i.'>';
                                    $i++;
                                    $j = 1;
                                }
                            }
                            //Для чекбоксов формат массива в пост другой
                            elseif($settings['table_cell_type'] == 'check')
                            {
                                //Перебираю строки
                                for($i = 1; $i <= $settings['table_rows']; $i++)
                                {
                                    $f_value .= '<row'.$i.'>';
                                    //Перебираю столбцы
                                    for($j = 1; $j <= $settings['table_colls']; $j++)
                                    {
                                        $check_value = 0; //Флаг совпадения
                                        if(isset($_POST['user_f_'.$value['id']][$i]))
                                        {
                                                if(isset($_POST['user_f_'.$value['id']][$i][$j]) && $_POST['user_f_'.$value['id']][$i][$j] ==  'user_f_'.$value['id'].'_'.$i.'_'.$j)
                                                {
                                                    $check_value = 1; //Устанавливаю флаг совпадения
                                                    $null_check = 1; //Как минимум одно поле заполненно
                                                }
                                        }
                                            $f_value .= '<col'.$j.'>'.$check_value.'</col'.$j.'>';
                                    }
                                    $f_value .= '</row'.$i.'>';
                                }
                            }
                            $f_value .= '</table>';
                        }

                        /*
                         * Проверки
                         */
                        //Хоть одна ячейка должна быть заполненна

                        if($null_check == 0 && $value['mandatory'] == 1)
                        {
                            // Если ни одна ячейка не заполненна - выводить ошибку
                            sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_TABLE_ERROR, $value['title']), false, 'warning');
                            return false;
                        }
                        //Длина строки менее чем...
                        if(isset($settings['table_cell_settings']['string_min']) && $settings['table_cell_settings']['string_min'] != '' && $settings['table_cell_type'] == 'string')
                        {
                            foreach($_POST['user_f_'.$value['id']] as $key2 => $value2)
                            {
                                foreach($value2 as $key3 => $value3)
                                {
                                    if(strlen($value3) < $settings['table_cell_settings']['string_min'])
                                    {
                                        sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_MIN_LENGTH_ERROR, $value['title'],$settings['table_cell_settings']['string_min']), false, 'warning');
                                        return false;
                                    }
                                }
                            }
                        }
                        $_POST['user_f_'.$value['id']] = is_null($f_value) ? '' : $f_value;
                    break;

                    default:
                        $f_value = isset($_POST['user_f_'.$value['id']]) && $_POST['user_f_'.$value['id']] != '' ? $_POST['user_f_'.$value['id']] : null;
                        $postfix = '';
                        if($value['type'] == 'image')
                            unset($settings['local']);

                        if($value['type'] == 'image' && isset($_POST['user_f_'.$value['id'].'_type']) && $_POST['user_f_'.$value['id'].'_type'] == 'local')
                        {
                            $postfix = '_local';
                            $settings['local'] = 1;
                        }

                        if (($value['type'] == 'image' || $value['type'] == 'file') && isset($settings['local']) && $settings['local'] == 1)
                        {
                            $old_img = '';

                            // вытаскиваем загруженную ранее картинку
                            if ($id != -1 && $id != '')
                            {
                                // удаляем загруженную ранее картинку
                                if (!$categs && $id_name != '')
                                {
                                    // элементы
                                    $res = sql_query('SELECT user_f_'.$value['id'].' FROM '.$plugin_table.' WHERE '.$id_name.'='.intval($id));
                                    if ($res)
                                    {
                                        list($old_img) = $res[0];
                                    }
                                }
                                elseif ($categs && isset($old_values['user_f_'.$value['id']]))
                                {
                                    // разделы
                                    $old_img = $old_values['user_f_'.$value['id']];
                                }
                            }

                            $tmp_name = '';
                            if(isset($_POST['user_f_'.$value['id'].'_delete']))
                            {
                                //Если выбрано удаление файла, то игнорируем загрузку нового файла в данное поле
                            }

                            if($value['type'] == 'image' && isset($_POST['user_f_'.$value['id'].$postfix.'_uploaded']) && $_POST['user_f_'.$value['id'].$postfix.'_uploaded'] == 1)
                            {
                                $tmp_name = $_POST['user_f_'.$value['id'].$postfix.'_tmp'];
                            }
                            else
                            {
                                $tmp_name = isset($_FILES['user_f_'.$value['id'].$postfix])? $_FILES['user_f_'.$value['id'].$postfix]['tmp_name'] : '';
                            }

                            if ($tmp_name != '')
                            {
                                $tmp_local = $GLOBALS['sbVfs']->mLocal;
                                $GLOBALS['sbVfs']->mLocal = true;
                                if (!is_uploaded_file($tmp_name) && (isset($_POST['user_f_'.$value['id'].$postfix.'_uploaded']) && $_POST['user_f_'.$value['id'].$postfix.'_uploaded'] ==1 && !$GLOBALS['sbVfs']->is_file(SB_CMS_TMP_PATH. '/' . $tmp_name)))
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_ERROR, $value['title']), false, 'warning');
                                    return false;
                                }
                                $GLOBALS['sbVfs']->mLocal = $tmp_local;

                                // загружаем новую картинку, заменяя старую
                                $path = $settings['path'];
                                if (!fFolders_Check_Rights($path))
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR.' '.SB_FILELIST_DENY_ERROR_MSG, $value['title']), false, 'warning');
                                    return false;
                                }

                                if ($settings['file_types'] == '')
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR2, $value['title']), false, 'warning');
                                    return false;
                                }

                                // подключаем библиотеку работы с загрузкой файлов
                                require_once(SB_CMS_LIB_PATH.'/sbUploader.inc.php');

                                $accept_types = array();
                                $img_types = explode(' ', $settings['file_types']);
                                if (!$_SESSION['sbAuth']->isAdmin())
                                {
                                    $admin_types = $_SESSION['sbAuth']->getUploadingExts();
                                    $num = count($admin_types);
                                    if ($num == 0)
                                    {
                                        sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR2, $value['title']), false, 'warning');
                                        return false;
                                    }

                                    for($i = 0; $i < $num; $i++)
                                    {
                                        if($value['type'] == 'file')
                                        {
                                            if(in_array($admin_types[$i], $img_types))
                                            {
                                                $accept_types[] = $admin_types[$i];
                                            }
                                        }
                                        else
                                        {
                                            if (in_array($admin_types[$i], $img_types) && ($admin_types[$i] == 'jpg' || $admin_types[$i] == 'png' || $admin_types[$i] == 'gif' || $admin_types[$i] == 'jpeg'))
                                            {
                                                $accept_types[] = $admin_types[$i];
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    $accept_types = $img_types;
                                }

                                if (count($accept_types) == 0)
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR2, $value['title']), false, 'warning');
                                    return false;
                                }

                                $uploader = new sbUploader();
                                $uploader->setMaxFileSize(sbPlugins::getSetting('sb_files_max_upload_size'));
                                $uploader->setMaxImageSize(sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'));

                                // сохраняем файл
                                if($value['type'] == 'file')
                                {
                                    $success = $uploader->upload('user_f_'.$value['id'].$postfix, $accept_types);
                                }
                                else
                                {
                                    $newFileName = $this->setUniqueName($_POST['user_f_'.$value['id'].$postfix]);
                                    $newPath = SB_BASEDIR.$path.'/'.$newFileName;
                                    
                                    $tmp_local = $GLOBALS['sbVfs']->mLocal;
                                    $GLOBALS['sbVfs']->mLocal = true;
                                    $success = $GLOBALS['sbVfs']->copy(SB_CMS_TMP_PATH. '/' . $tmp_name, $newPath, true);
                                    
                                    $GLOBALS['sbVfs']->mLocal = $tmp_local;
                                }

                                $file_name = false;
                                if ($success)
                                {
                                    if ($old_img != '' && $GLOBALS['sbVfs']->exists($old_img) && $GLOBALS['sbVfs']->is_file($old_img))
                                    {
                                        $GLOBALS['sbVfs']->delete($old_img);
                                    }
                                    
                                    if($value['type'] == 'file')
                                    {
                                        $file_name = $uploader->move($path, '', 2);
                                    }
                                    else
                                    {
                                        $file_name = isset($newFileName) ? $newFileName : false;
                                    }
                                }


                                if ($file_name)
                                {
                                    $file = str_replace('//', '/', $path.'/'.$file_name);
                                }
                                else
                                {
                                    $file = str_replace('//', '/', $path.'/'.$_FILES['user_f_'.$value['id'].$postfix]['name']);
                                }

                                if (!$success || !$file_name)
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR.' '.$uploader->getError(), $value['title']), false, 'warning');
                                    sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_ERROR3, $file), SB_MSG_WARNING);
                                    return false;
                                }
                                sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_OK, $file));

                                if ($value['type'] == 'image' && $settings['resize'] == 1)
                                {
                                    // пытаемся сжать изображение
                                    if (!sb_resize_image($file, $file, $settings['img_width'], $settings['img_height'], $settings['img_quality']))
                                    {
                                        sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_RESIZE_ERROR, $file), SB_MSG_WARNING);
                                    }
                                }

                                if ($value['type'] == 'image' && $settings['watermark'] == 1)
                                {
                                    if (!sb_watermark_image($file, $file, $settings['watermark_position'], $settings['watermark_opacity'], $settings['watermark_margin'], $settings['watermark_file'], $settings['copyright'], $settings['copyright_color'], $settings['copyright_font'], $settings['copyright_size']))
                                    {
                                        sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_WATERMARK_ERROR, $file), SB_MSG_WARNING);
                                        $error = true;
                                    }
                                }

                                $page_domain = SB_COOKIE_DOMAIN;

                                if (SB_PORT != '80')
                                    $page_domain .= ':'.SB_PORT;

                                if ((SB_HTTPS && 'https://'.$page_domain != SB_DOMAIN || !SB_HTTPS && 'http://'.$page_domain != SB_DOMAIN) && substr_count(SB_COOKIE_DOMAIN, '.') > 0)
                                    $page_domain = 'www.'.$page_domain;

                                $page_domain = 'http'.(SB_HTTPS ? 's' : '').'://'.$page_domain;

                                $f_value = $page_domain.$file;
                                $_POST['user_f_'.$value['id'].$postfix] = $file;
                                $this->mUploadedFiles[] = $file;
                            }
                            elseif (isset($_POST['user_f_'.$value['id'].'_delete']))
                            {
                                // удаляем загруженную ранее картинку
                                $f_value = null;
                                if (stristr($old_img, 'http://') !== false || stristr($old_img, 'https://') !== false)
                                {
                                    $old_img = str_ireplace(array('https://', 'http://'), '', $old_img);
                                    $old_img = substr($old_img, strpos($old_img, '/'));
                                }
                                if ($old_img != '' && $GLOBALS['sbVfs']->exists($old_img) && $GLOBALS['sbVfs']->is_file($old_img))
                                        $GLOBALS['sbVfs']->delete($old_img);
                            }
                            else
                            {
                                $f_value = $old_img;
                            }

                            $_POST['user_f_'.$value['id'].$postfix] = $f_value;
                        }

                        if($value['type'] == 'image'
                        && isset($settings['parent_field'])
                        && $settings['parent_field'] != ''
                        && isset($_POST['user_f_'.$value['id'].'_type'])
                        && $_POST['user_f_'.$value['id'].'_type'] == 'generate')
                        {
                            //Генерируем изображение на основе существующего
                            $img = isset($values[$settings['parent_field']]) ? $values[$settings['parent_field']] : '';
                            if($img != '')
                            {
                                $parse_url = parse_url($img);
                                $parse_file = pathinfo($parse_url['path']);

                                $tmp = $GLOBALS['sbVfs']->mLocal;
                                $GLOBALS['sbVfs']->mLocal = true;
                                if($GLOBALS['sbVfs']->is_file(SB_BASEDIR . $parse_url['path']))
                                {
                                    // пытаемся сжать изображение
                                    //$newFile = $parse_file['filename'].'_thumb_'.$value['id'].'.'.$parse_file['extension'];
                                    $newFile = $parse_file['filename'].'_thumb_'.  uniqid($value['id']).'.'.$parse_file['extension'];
                                    // Удаляем старые миниатюры

                                    $oldFile = '';
                                    if (!$categs && isset($values['user_f_'.$value['id']]))
                                    {
                                        $oldFile = str_ireplace(array('https://', 'http://'), '', $values['user_f_'.$value['id']]);
                                        $oldFile = substr($oldFile, strpos($oldFile, '/'));
                                    }
                                    elseif(isset($old_values['user_f_'.$value['id']]))
                                    {
                                        $oldFile = str_ireplace(array('https://', 'http://'), '', $old_values['user_f_'.$value['id']]);
                                        $oldFile = substr($oldFile, strpos($oldFile, '/'));
                                    }
                                    if ($oldFile != '' && $GLOBALS['sbVfs']->is_file(SB_BASEDIR . $oldFile))
                                    {
                                        $GLOBALS['sbVfs']->delete(SB_BASEDIR . $oldFile);
                                    }

                                    if (!sb_resize_image(SB_BASEDIR . $parse_url['path'], SB_BASEDIR . $settings['path'].'/'.$newFile, $_POST['user_f_'.$value['id'].'_generate_width'], $_POST['user_f_'.$value['id'].'_generate_height'], $settings['img_quality']))
                                    {
                                        sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_RESIZE_ERROR, SB_BASEDIR . $parse_url['path']), SB_MSG_WARNING);
                                    }
                                    else
                                    {
                                        $f_value = 'http'.(SB_HTTPS ? 's' : '').'://'.SB_COOKIE_DOMAIN.$settings['path'].'/'.$newFile;
                                    }
                                }
                            }
                            else
                            {
                                if($counter <= $lastIndex)
                                {
                                    //Если мы еще в пределах исходного массива, то откладываем генерацию
                                    $fields[] = $value;
                                    continue;
                                }
                                else
                                {
                                    //Мы за пределами исходного массива
                                    sb_add_system_message(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_RESIZE_ERROR2, SB_MSG_ERROR);
                                }
                            }
                        }

                        if (!is_null($f_value))
                        {
                            if (($value['type'] == 'text' || $value['type'] == 'string' || $value['type'] == 'longtext' || $value['type'] == 'table') && $settings['html'] != 1)
                            {
                                $f_value = strip_tags($f_value);
                                $_POST['user_f_'.$value['id']] = $f_value;
                            }

                            if ($value['type'] == 'string' || $value['type'] == 'password')
                            {
                                if ($settings['min_length'] != '' && sb_strlen($f_value) < intval($settings['min_length']))
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_MIN_LENGTH_ERROR, $value['title'], $settings['min_length']), false, 'warning');
                                    return false;
                                }

                                if ($settings['max_length'] != '' && sb_strlen($f_value) > intval($settings['max_length']))
                                {
                                    sb_show_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_MAX_LENGTH_ERROR, $value['title'], $settings['max_length']), false, 'warning');
                                    return false;
                                }
                            }
                        }

                        if ($value['type'] == 'select_sprav' && $f_value == -1)
                            $f_value = null;

                        break;
                }

                if ($value['type'] == 'link_sprav')
                {
                    $values['user_f_'.$value['id']] = $f_value;
                    continue;
                }

                if ($value['mandatory'] == 1)
                {
                    $error = false;
                    if ($value['type'] == 'color' || $value['type'] == 'string' || $value['type'] == 'text' ||
                        $value['type'] == 'longtext' || $value['type'] == 'number' || $value['type'] == 'image' ||
                        $value['type'] == 'link' || $value['type'] == 'file')
                    {
                        $expr = str_replace('{VALUE}', addslashes($f_value), $value['mandatory_val']);
                        eval($expr);
                        $_POST['user_f_'.$value['id']] = $f_value;
                    }
                    elseif ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
                    {
                        if (is_null($f_value))
                        {
                            $error = true;
                        }
                        else
                        {
                            $val = explode('|', $f_value);
                            if (trim($val[0]) == '' || trim($val[1]) == '')
                            {
                                $error = true;
                            }
                        }
                    }
                    elseif (is_null($f_value))
                    {
                        $error = true;
                    }

                    if ($error)
                    {
                        sb_show_message($value['mandatory_err'], false, 'warning');
                        return false;
                    }
                }

                $values['user_f_'.$value['id']] = $f_value;
            }
        }
        return $values;

    }

    /**
     * Проверяет пользовательские поля на корректность заполнения со стороны сайта.
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param string $fields_error Переменная, указывающая, произошла ошибка при проверке значений полей (TRUE) или нет (FALSE).
     * @param array $fields_temps Массив макетов дизайна пользовательских полей с указанными обязательными полями.
     * @param int $id Уникальный идентификатор элемента, если элемент редактируется, или раздела, если раздел редактируется.
     * @param string $table_name Наименование таблицы, где хранятся элементы.
     * @param string $id_name Наименование поля таблицы, где хранится уникальный идентификатор элемента.
     * @param bool $categs Выводить поля разделов (TRUE) или элементов (FALSE).
     * @param string $date_temp Формат ввода дат.
     *
     * @return mixed Ассоциативный массив для вставки в базу, если проверка прошла успешно,
     *               ассоциативный массив тегов полей (ключи массива) и кодов ошибок (значения массива), если
     *               в результате проверки полей произошла ошибка. Коды ошибок:
     *               <ul>
     *                  <li>1 - передано неверное значение поля
     *                  <li>2 - не удалось сохранить файл
     *                  <li>3 - неверный тип файла
     *                  <li>4 - размер файла превышает допустимый
     *                  <li>5 - размер изображения превышает допустимый
     *               </ul>
     */
    public function checkPluginInputFields($ident, &$fields_error, $fields_temps, $id = -1, $table_name = '', $id_name = '', $categs = false, $date_temp = '')
    {
        $this->mUploadedFiles = array();

        $error_result = array();
        $ok_result = array();

        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || $res[0][0] == '')
        {
            //  полей нет
            return $ok_result;
        }
        $fields = unserialize($res[0][0]);

        $old_values = array();
        if ($categs && $id != -1 && $id != '')
        {
            $res = sql_query('SELECT cat_fields FROM sb_categs WHERE cat_id=?d', $id);
            if ($res)
            {
                $old_values = unserialize($res[0][0]);
            }
        }
        elseif (!$categs && $id != -1 && $id != '' && $table_name != '' && $id_name != '')
        {
            $select_sql = '';
            foreach ($fields as $key => $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1)
                {
                    $select_sql .= 'user_f_'.$value['id'].',';
                    $old_values['user_f_'.$value['id']] = null;
                }
            }

            if ($select_sql != '')
            {
                $select_sql = substr($select_sql, 0, -1);
                $res = sql_query('SELECT '.$select_sql.' FROM ?# WHERE ?#=?d', $table_name, $id_name, $id);
                if ($res)
                {
                    $i = 0;
                    foreach ($old_values as $key => $value)
                    {
                        $old_values[$key] = $res[0][$i++];
                    }
                }
            }
        }

        foreach ($fields as $key => $value)
        {
            if (isset($value['sql']) && $value['sql'] == 1)
            {
                $settings = $value['settings'];
                $f_name = 'user_f_'.$value['id'];
                $f_value = '';
                $f_tag = $value['tag'];

                switch ($value['type'])
                {
                    case 'number':
                        $f_value = (isset($_POST[$f_name]) ? floatval($_POST[$f_name]) : (isset($old_values[$f_name]) ? $old_values[$f_name] : null));

                        if (isset($fields_temps[$f_name.'_need']) && (!isset($_POST[$f_name]) || $f_value == '' || is_null($f_value)))
                        {
                            $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                            continue 2;
                        }

                        if (isset($_POST[$f_name]))
                        {
                            if ($settings['min_value'] != '' && ($f_value === null || $f_value < floatval($settings['min_value'])))
                            {
                                $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                                continue 2;
                            }

                            if ($settings['max_value'] != '' && $f_value > floatval($settings['max_value']))
                            {
                                $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                                continue 2;
                            }
                        }
                        break;

                    case 'checkbox':
                        if (isset($_POST[$f_name]))
                        {
                            $f_value = 1;
                        }
                        elseif (isset($_POST[$f_name.'_cb']))
                        {
                            $f_value = 0;
                        }
                        else
                        {
                            $f_value = (isset($old_values[$f_name]) ? $old_values[$f_name] : 0);
                        }

                        if (isset($fields_temps[$f_name.'_need']) && $f_value == 0)
                        {
                            $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                            continue 2;
                        }
                        break;

                    case 'checkbox_sprav':
                    case 'multiselect_sprav':
                    case 'checkbox_plugin':
                    case 'multiselect_plugin':
                        $f_value = (isset($_POST[$f_name]) && is_array($_POST[$f_name]) && count($_POST[$f_name]) > 0 ? implode(',', $_POST[$f_name]) : (isset($old_values[$f_name]) ? $old_values[$f_name] : null));
                        if (isset($fields_temps[$f_name.'_need']) && (!isset($_POST[$f_name]) || !is_array($_POST[$f_name]) || count($_POST[$f_name]) <= 0 || $f_value == '' || is_null($f_value)))
                        {
                            $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                            continue 2;
                        }
                        break;

                    case 'date':
                        $f_value = (isset($_POST[$f_name]) ? sb_datetoint($_POST[$f_name], $date_temp) : (isset($old_values[$f_name]) ? $old_values[$f_name] : null));
                        if (isset($fields_temps[$f_name.'_need']) && (!isset($_POST[$f_name]) || $f_value == '' || is_null($f_value)))
                        {
                            $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                            continue 2;
                        }
                        break;

                    case 'link_sprav':
                    case 'link_plugin':
                        $first_value = 0;
                        $second_value = 0;

                        if (isset($_POST[$f_name]) && isset($_POST[$f_name.'_link']))
                        {
                            if (intval($_POST[$f_name]) > 0 && intval($_POST[$f_name.'_link']) > 0)
                            {
                                $first_value = intval($_POST[$f_name]);
                                $second_value = intval($_POST[$f_name.'_link']);
                                $f_value = $first_value.','.$second_value;
                            }
                            else
                            {
                                $f_value = null;
                            }
                        }
                        else
                        {
                            $f_value = isset($old_values[$f_name]) ? $old_values[$f_name] : null;
                        }

                        if (isset($fields_temps[$f_name.'_need']) && (is_null($f_value) || $first_value <= 0 || $second_value <= 0))
                        {
                            $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                            continue 2;
                        }
                        break;

                    default:
                        if ($value['type'] == 'image' || $value['type'] == 'file')
                        {
                            // вытаскиваем загруженную ранее картинку
                            if (isset($fields_temps[$f_name.'_need']) && (!isset($_FILES[$f_name]) || $_FILES[$f_name]['tmp_name'] == ''))
                            {
                                $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                                continue 2;
                            }

                            $old_img = isset($old_values[$f_name]) ? $old_values[$f_name] : '';

                            if (isset($_POST['user_f_'.$value['id'].'_delete']))
                            {
                                // Удаляю текущее фото, если установленна соответствующая галочка
                                $f_value = null;
                                // Получаю путь к файлу, относительно файловой системы
                                $old_img_file = SB_BASEDIR.str_replace(array('http://','www.',SB_COOKIE_DOMAIN), array('','',''), $old_img);
                                $old_img_file = str_replace('//','/',$old_img_file);
                                // Если есть файл - удаляю
                                $GLOBALS['sbVfs']->mLocal = true;
                                if ($old_img_file != '' && $GLOBALS['sbVfs']->exists($old_img_file) && $GLOBALS['sbVfs']->is_file($old_img_file))
                                {
                                        $GLOBALS['sbVfs']->delete($old_img_file);
                                }
                                $GLOBALS['sbVfs']->mLocal = false;
                            }

                            if (isset($_FILES[$f_name]) && $_FILES[$f_name]['tmp_name'] != '')
                            {
                                if (!is_uploaded_file($_FILES[$f_name]['tmp_name']))
                                {
                                    $error_result[$f_name] = array('error' => 2, 'tag' => $f_tag);
                                    continue 2;
                                }

                                if (trim($settings['file_types']) == '')
                                {
                                    $error_result[$f_name] = array('error' => 3, 'tag' => $f_tag);
                                    continue 2;
                                }

                                // загружаем новую картинку, заменяя старую
                                $path = isset($settings['path']) && trim($settings['path']) != '' ? $settings['path'] : '/upload/'.$ident;
                                $accept_types = explode(' ', trim($settings['file_types']));

                                // подключаем библиотеку работы с загрузкой файлов
                                require_once(SB_CMS_LIB_PATH.'/sbUploader.inc.php');
                                $uploader = new sbUploader();
                                $uploader->setMaxFileSize(sbPlugins::getSetting('sb_files_max_upload_size'));
                                $uploader->setMaxImageSize(sbPlugins::getSetting('sb_files_max_upload_width'), sbPlugins::getSetting('sb_files_max_upload_height'));

                                // сохраняем файл
                                $success = $uploader->upload('user_f_'.$value['id'], $accept_types);

                                $file_name = false;
                                if ($success)
                                {
                                    $file_name = $uploader->move($path, '', 2);
                                }

                                if (!$success || !$file_name)
                                {
                                    switch($uploader->getErrorCode())
                                    {
                                        case 2:
                                            $error_result[$f_name] = array('error' => 4, 'tag' => $f_tag);
                                            break;

                                        case 3:
                                            $error_result[$f_name] = array('error' => 5, 'tag' => $f_tag);
                                            break;

                                        case 4:
                                            $error_result[$f_name] = array('error' => 3, 'tag' => $f_tag, 'file_types' => $settings['file_types']);
                                            break;

                                        default:
                                            $error_result[$f_name] = array('error' => 2, 'tag' => $f_tag);
                                            break;
                                    }

                                    continue 2;
                                }

                                $file = str_replace('//', '/', $path.'/'.$file_name);

                                sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_OK, $file));

                                if ($value['type'] == 'image')
                                {
                                    if ($settings['resize'] == 1)
                                    {
                                        // пытаемся сжать изображение
                                        if (!sb_resize_image($file, $file, $settings['img_width'], $settings['img_height'], $settings['img_quality']))
                                        {
                                            sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_RESIZE_ERROR, $file), SB_MSG_WARNING);
                                        }
                                    }

                                    if ($settings['watermark'] == 1)
                                    {
                                        if (!sb_watermark_image($file, $file, $settings['watermark_position'], $settings['watermark_opacity'], $settings['watermark_margin'], $settings['watermark_file'], $settings['copyright'], $settings['copyright_color'], $settings['copyright_font'], $settings['copyright_size']))
                                        {
                                            sb_add_system_message(sprintf(SB_LAYOUT_PLUGIN_FIELDS_IMAGE_UPLOAD_WATERMARK_ERROR, $file), SB_MSG_WARNING);
                                        }
                                    }
                                }

                                $page_domain = SB_COOKIE_DOMAIN;

                                if (SB_PORT != '80')
                                    $page_domain .= ':'.SB_PORT;

                                if ((SB_HTTPS && 'https://'.$page_domain != SB_DOMAIN || !SB_HTTPS && 'http://'.$page_domain != SB_DOMAIN) && substr_count(SB_COOKIE_DOMAIN, '.') > 0)
                                    $page_domain = 'www.'.$page_domain;

                                $page_domain = 'http'.(SB_HTTPS ? 's' : '').'://'.$page_domain;

                                $f_value = $page_domain.$file;
                                $this->mUploadedFiles[] = $file;
                            }
                            else
                            {
                                if (isset($_POST['user_f_'.$value['id'].'_delete']))
                                {
                                    $f_value = '';
                                }
                                else
                                {
                                    $f_value = $old_img;
                                }
                            }
                        }
                        else
                        {
                            if (isset($_POST[$f_name]))
                            {
                                if ($value['type'] == 'text' || $value['type'] == 'longtext')
                                    $f_value = nl2br($_POST[$f_name]);
                                else
                                    $f_value = $_POST[$f_name];
                            }
                            elseif (isset($old_values[$f_name]))
                            {
                                $f_value = $old_values[$f_name];
                            }
                            else
                            {
                                $f_value = null;
                            }

                            if (($value['type'] == 'select_sprav' || $value['type'] == 'categs') && $f_value <= 0)
                                $f_value = null;

                            if (isset($fields_temps[$f_name.'_need']) && (!isset($_POST[$f_name]) || $f_value == '' || is_null($f_value)))
                            {
                                $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                                continue 2;
                            }

                            if (!is_null($f_value) && $f_value != '' && ($value['type'] == 'string' || $value['type'] == 'password'))
                            {
                                if ($settings['min_length'] != '' && sb_strlen($f_value) < intval($settings['min_length']))
                                {
                                    $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                                    continue 2;
                                }

                                if ($settings['max_length'] != '' && sb_strlen($f_value) > intval($settings['max_length']))
                                {
                                    $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                                    continue 2;
                                }
                            }
                        }
                        break;

                    case 'google_coords':
                    case 'yandex_coords':
                        if (isset($old_values[$f_name]) && !is_null($old_values[$f_name]) && $old_values[$f_name] != '')
                        {
                            $vals = explode('|', $old_values[$f_name]);

                            $f_value_latitude = $vals[0];
                            $f_value_longtitude = $vals[1];
                        }
                        else
                        {
                            $f_value_latitude = '';
                            $f_value_longtitude = '';
                        }

                        $f_value_latitude = (isset($_POST[$f_name.'_latitude']) ? floatval($_POST[$f_name.'_latitude']) : ($f_value_latitude != '' ? $f_value_latitude : null));
                        $f_value_longtitude = (isset($_POST[$f_name.'_longtitude']) ? floatval($_POST[$f_name.'_longtitude']) : ($f_value_longtitude != '' ? $f_value_longtitude : null));

                        $error = false;
                        if (isset($fields_temps[$f_name.'_latitude_need']) && (!isset($_POST[$f_name.'_latitude']) || $f_value_latitude == '' || is_null($f_value_latitude)))
                        {
                            $error_result[$f_name.'_latitude'] = array('error' => 1, 'tag' => $f_tag.'_LATITUDE');
                            $error = true;
                        }

                        if (isset($fields_temps[$f_name.'_longtitude_need']) && (!isset($_POST[$f_name.'_longtitude']) || $f_value_longtitude == '' || is_null($f_value_longtitude)))
                        {
                            $error_result[$f_name.'_longtitude'] = array('error' => 1, 'tag' => $f_tag.'_LONGTITUDE');
                            $error = true;
                        }

                        if ($error)
                        {
                            continue 2;
                        }

                        break;

                    case 'php':
                        $GLOBALS['sb_value'] = null;
                        if (isset($settings['site_code']) && trim($settings['site_code']) != '')
                        {
                            eval($settings['site_code']);
                        }

                        $f_value = $GLOBALS['sb_value'];
                        unset($GLOBALS['sb_value']);
                        break;
                }

                if ($value['type'] == 'link_sprav')
                {
                    $ok_result[$f_name] = $f_value;
                    continue;
                }

                if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
                {
                    $ok_result[$f_name] = $f_value_latitude.'|'.$f_value_longtitude;
                    continue;
                }

                if (isset($value['mandatory']) && $value['mandatory'] == 1 && isset($value['mandatory_val']) && trim($value['mandatory_val']) != '')
                {
                    $expr = str_replace('{VALUE}', addslashes($f_value), $value['mandatory_val']);
                    $error = false;
                    eval($expr);

                    if ($error)
                    {
                        $error_result[$f_name] = array('error' => 1, 'tag' => $f_tag);
                        continue;
                    }
                }
                $ok_result[$f_name] = $f_value;
            }
        }

        if (count($error_result) > 0)
        {
            $fields_error = true;
            return $error_result;
        }
        return $ok_result;
    }

    /**
     * Удаляет файлы, загруженные на сервер с помощью пользовательских полей
     *
     */
    public function deletePluginFieldsFiles()
    {
        foreach ($this->mUploadedFiles as $value)
        {
            if ($value != '' && $GLOBALS['sbVfs']->exists($value) && $GLOBALS['sbVfs']->is_file($value))
                $GLOBALS['sbVfs']->delete($value);
        }

        $this->mUploadedFiles = array();
    }

    /**
     * Выводит список значений пользовательских полей
     *
     * @param string $plugin_ident Уникальный идентификатор модуля, для которого получаем список полей.
     * @param array $values Значения пользовательских полей.
     *
     * @return string Отформатированный список значений пользовательских полей.
     */
    static function getPluginFieldsInfo($plugin_ident, $values)
    {
        static $plugins_info = false;
        if (is_null($plugins_info))
            return '';

        if (!$plugins_info)
        {
            $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);
            if ($res && $res[0][0] != '')
            {
                $plugins_info = unserialize($res[0][0]);
            }
            else
            {
                $plugins_info = null;
                return '';
            }
        }

        $result = '';
        foreach ($plugins_info as $value)
        {
            if ($value['info'] == 1 && isset($values['user_f_'.$value['id']]) && !is_null($values['user_f_'.$value['id']]))
            {
                $val = $values['user_f_'.$value['id']];

                $result .= '<br />'.$value['title'].': ';

                switch ($value['type'])
                {
                    case 'file':
                    case 'link':
                        $result .= '<a href="'.$val.'" target="_blank">'.$val.'</a>';
                        break;

                    case 'image':
                        if ($value['settings']['show'] == 1)
                            $result .= '<img src="'.$val.'" align="absmiddle" width="250" />';
                        else
                            $result .= '<a href="'.$val.'" target="_blank">'.$val.'</a>';
                        break;

                    case 'date':
                        if ($value['settings']['time'] == 1)
                            $result .= '<span style="color: #33805E;">'.sb_date('d.m.Y H:i', $val).'</span>';
                        else
                            $result .= '<span style="color: #33805E;">'.sb_date('d.m.Y', $val).'</span>';
                        break;

                    case 'checkbox':
                        if ($val == 1)
                            $result .= '<span style="color: green;">'.$value['settings']['checked_text'].'</span>';
                        else
                            $result .= '<span style="color: red;">'.$value['settings']['not_checked_text'].'</span>';
                        break;

                    case 'color':
                        $result .= '<span style="background-color:'.$val.';text-align:center;">'.$val.'</span>';
                        break;

                    case 'checkbox_plugin':
                    case 'select_plugin':
                    case 'elems_plugin':
                    case 'radio_plugin':
                    case 'multiselect_plugin':
                        if (!$val)
                            break;
                        $sql_params = getPluginSqlParams($value['settings']['ident']);

                        if (isset($value['settings']['modules_title_fld']))
                        {
                            $fld = preg_replace('/[^A-Za-z_0-9]/', '', $value['settings']['modules_title_fld']);
                        }
                        else
                        {
                            break;
                        }

                        $res = sql_query('SELECT '.$fld.' FROM '.$sql_params['table'].' WHERE '.$sql_params['id'].' IN (0,'.$val.')');
                        if ($res)
                        {
                            $num = min(count($res), 4);

                            $str = array();
                            for ($i = 0; $i < $num; $i++)
                            {
                                list($str[]) = $res[$i];
                            }

                            if(isset($sql_params['date']) && $fld == $sql_params['date'])
                            {
                                foreach($str as $v)
                                    $str1[] = date('d.m.Y h:i', $v);
                                $str = $str1;
                                unset($str1);
                            }

                            $str = implode(', ', $str);
                            if (count($res) > 4)
                                $str .= ' ...';

                            $result .= '<span style="color: #CC0066;">'.$str.'</span>';
                        }
                        break;

                    case 'multiselect_sprav':
                    case 'checkbox_sprav':
                    case 'select_sprav':
                    case 'radio_sprav':
                        if (!$val)
                            break;
                        if (isset($value['settings']['sprav_title_fld']))
                        {
                            $fld = preg_replace('/[^A-Za-z_0-9]/', '', $value['settings']['sprav_title_fld']);
                        }
                        else
                        {
                            $fld = 's_title';
                        }

                        $res = sql_query('SELECT '.$fld.' FROM sb_sprav WHERE s_id IN (0,'.$val.')');
                        if ($res)
                        {
                            $num = min(count($res), 4);

                            $str = array();
                            for ($i = 0; $i < $num; $i++)
                            {
                                list($str[]) = $res[$i];
                            }
                            $str = implode(', ', $str);
                            if (count($res) > 4)
                                $str .= ' ...';

                            $result .= '<span style="color: #CC0066;">'.$str.'</span>';
                        }
                        break;

                    case 'link_sprav':
                        if (!$val)
                            break;

                        $val = explode(',', $val);

                        if (!isset($val[0]) || !isset($val[1]))
                            break;

                        $cat_id = intval($val[0]);
                        $s_id = intval($val[1]);

                        if (isset($value['settings']['sprav_title_fld']))
                        {
                            $fld = preg_replace('/[^A-Za-z_0-9]/', '', $value['settings']['sprav_title_fld']);
                        }
                        else
                        {
                            $fld = 's_title';
                        }

                        $res_cat = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $cat_id);
                        $res_sprav = sql_query('SELECT '.$fld.' FROM sb_sprav WHERE s_id=?d', $s_id);

                        if ($res_cat && $res_sprav)
                        {
                            $result .= '<span style="color: #CC0066;">'.$res_cat[0][0].' -&gt; '.$res_sprav[0][0].'</span>';
                        }
                        break;

                     case 'link_plugin':
                        if (!$val)
                            break;
                        $sql_params = getPluginSqlParams($value['settings']['ident']);
                        $val = explode(',', $val);

                        if (!isset($val[0]) || !isset($val[1]))
                            break;

                        $cat_id = intval($val[0]);
                        $s_id = intval($val[1]);

                        if (isset($value['settings']['modules_title_fld']))
                        {
                            $fld = preg_replace('/[^A-Za-z_0-9]/', '', $value['settings']['modules_title_fld']);
                        }
                        else
                        {
                            break;
                        }

                        $res_cat = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $cat_id);
                        $res_sprav = sql_query('SELECT '.$fld.' FROM '.$sql_params['table'].' WHERE '.$sql_params['id'].'=?d', $s_id);

                        if ($res_cat && $res_sprav)
                        {
                            if(isset($sql_params['date']) && $fld == $sql_params['date'])
                                $result .= '<span style="color: #CC0066;">'.$res_cat[0][0].' -&gt; '.date('d.m.Y h:i',$res_sprav[0][0]).'</span>';
                            else
                                $result .= '<span style="color: #CC0066;">'.$res_cat[0][0].' -&gt; '.$res_sprav[0][0].'</span>';

                        }
                        break;

                    case 'categs':
                        if (!$val)
                            break;

                        if (isset($value['multiselect']) && $value['multiselect'] == 1)
                        {
                            $ids = explode('^', $val);
                            $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id IN (?a)', $ids);

                            $output = array();
                            if ($res)
                            {
                                foreach ($res as $cat_title)
                                {
                                    $output[] = $cat_title[0];
                                }
                            }
                            $result .= '<span style="color: #33805E;">' . implode(', ', $output) . '</span>';
                        }
                        else
                        {
                            $res = sql_query('SELECT cat_title FROM sb_categs WHERE cat_id=?d', $val);
                            if ($res)
                            {
                                $result .= '<span style="color: #33805E;">' . $res[0][0] . '</span>';
                            }
                        }
                        break;

                    case 'google_coords':
                    case 'yandex_coords':
                        $latitude = '';
                        $longtitude = '';

                        if (trim($val) != '')
                        {
                            $val = explode('|', $val);

                            $latitude = $val[0];
                            $longtitude = $val[1];
                        }

                        $result .= '<span style="color: #33805E;">'.KERNEL_LATITUDE.' &mdash; '.$latitude.', '.KERNEL_LONGTITUDE.' &mdash; '.$longtitude.'</span>';
                        break;

                    default:
                        $result .= '<span style="color: #33805E;">'.$val.'</span>';
                        break;
                }
            }
            elseif ($value['info'] == 1 &&  $value['type'] == 'hr')
            {
                $result .= '<br />';
            }
        }

        return $result;
    }

    /**
     * Выводит фильтр по пользовательским полям внутри класса sbElements
     *
     * @param array $pd_fields Массив с описанием пользовательских полей.
     * @param array $i Инкремент.
     * @param array $cols Кол-во столбцов в таблице с фильтром.
     * @param array $values Массив, содержащий значения пользовательских полей.
     */
    static function getPluginFieldsFilter($pd_fields, &$i, $cols, $values)
    {
        foreach ($pd_fields as $value)
        {
            if ($value['filter'] == 1)
            {
                $settings = $value['settings'];
                $field = 'user_f_'.$value['id'];
                if (!isset($values[$field]))
                {
                    if ($value['type'] == 'select_sprav' || $value['type'] == 'checkbox')
                        $values[$field] = -1;
                    else
                        $values[$field] = null;
                }

                if ($i % $cols == 0 && $i != 0)
                {
                    echo '</tr><tr>';
                }

                echo '<th class="sb_filter_th">'.$value['title'].':</th><td '.(($i + 1) % $cols == 0 ? 'class="sb_filter_td_noborder"' : 'class="sb_filter_td"').'><nobr>';
                switch ($value['type'])
                {
                    case 'file':
                    case 'longtext':
                    case 'image':
                    case 'string':
                    case 'link':
                    case 'text':
                    case 'php':
                        $fld = new sbLayoutInput('text', $values[$field], 'f_user_f_'.$value['id'], '', 'style="width:100%;"');

                        echo $fld->getJavaScript();
                        echo $fld->getField();
                        break;

                    case 'number':
                        $fld_lo = new sbLayoutInput('text', $values[$field]['lo'], 'f_user_f_'.$value['id'].'_lo', 'spin_f_user_f_'.$value['id'].'_lo', 'style="width:50px;"');
                        $fld_lo->mMinValue = $settings['min_value'];
                        $fld_lo->mMaxValue = $settings['max_value'];
                        $fld_lo->mIncrement = $settings['increment'];

                        $fld_hi = new sbLayoutInput('text', $values[$field]['hi'], 'f_user_f_'.$value['id'].'_hi', 'spin_f_user_f_'.$value['id'].'_hi', 'style="width:50px;"');
                        $fld_hi->mMinValue = $settings['min_value'];
                        $fld_hi->mMaxValue = $settings['max_value'];
                        $fld_hi->mIncrement = $settings['increment'];

                        echo $fld_lo->getJavaScript();
                        echo '<table cellpadding="0" cellspacing="0"><tr><td>'.KERNEL_FROM.'&nbsp;</td><td>'.$fld_lo->getField().'</td><td>&nbsp;'.KERNEL_TO.'&nbsp;</td><td>'.$fld_hi->getField().'</td></tr></table>';
                        break;

                    case 'date':
                        $fld_lo = new sbLayoutDate($values[$field]['lo'], 'f_user_f_'.$value['id'].'_lo', 'date_user_f_'.$value['id'].'_lo');
                        $fld_lo->mDropButton = true;
                        $fld_lo->mShowTime = $settings['time'] == 1;

                        $fld_hi = new sbLayoutDate($values[$field]['hi'], 'f_user_f_'.$value['id'].'_hi', 'date_user_f_'.$value['id'].'_hi');
                        $fld_hi->mDropButton = true;
                        $fld_hi->mShowTime = $settings['time'] == 1;

                        echo $fld_lo->getJavaScript();
                        echo KERNEL_FROM.'&nbsp;'.$fld_lo->getField().KERNEL_TO.'&nbsp;'.$fld_hi->getField();
                        break;

                    case 'checkbox':
                        $options = array('-1' => ' --- ', '1' => KERNEL_YES, '0' => KERNEL_NO);
                        $fld = new sbLayoutSelect($options, 'f_user_f_'.$value['id']);
                        $fld->mSelOptions[] = $values[$field];
                        echo $fld->getField();
                        break;

                    case 'categs':
                        $res = sql_query('SELECT cat_left, cat_right, cat_level FROM sb_categs WHERE cat_id=?d', $settings['cat_id']);
                        $options = array('-1' => ' --- ');
                        if ($res)
                        {
                            list($cat_left, $cat_right, $cat_level) = $res[0];
                            $res = sql_query('SELECT cat_id, cat_title, cat_level FROM sb_categs WHERE cat_ident=? AND cat_left >= ?d AND cat_right <= ?d', $settings['ident'], $cat_left, $cat_right);
                            if ($res)
                            {
                                foreach ($res as $val)
                                {
                                    $options[$val[0]] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $val[2] - $cat_level).$val[1];
                                }
                            }
                        }
                        $fld = new sbLayoutSelect($options, 'f_user_f_'.$value['id']);
                        $fld->mSelOptions[] = $values[$field];
                        echo $fld->getField();
                        break;

                    case 'color':
                        $fld = new sbLayoutColor($values[$field], 'f_user_f_'.$value['id']);
                        $fld->mDropButton = true;

                        echo $fld->getJavaScript();
                        echo $fld->getField();
                        break;

                    case 'radio_sprav':
                    case 'select_sprav':
                        $fld = new sbLayoutSpravData('select', $settings['sprav_ids'], $values[$field], 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = $settings['subcategs'] == 1;

                        if (isset($settings['sprav_title_fld']))
                        {
                            $fld->mTitleFld = $settings['sprav_title_fld'];
                        }

                        echo $fld->getField();
                        break;

                    case 'multiselect_sprav':
                    case 'checkbox_sprav':
                        $fld = new sbLayoutSpravData('multiselect', $settings['sprav_ids'], $values[$field], 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = $settings['subcategs'] == 1;
                        $fld->mRows = 3;

                        if (isset($settings['sprav_title_fld']))
                        {
                            $fld->mTitleFld = $settings['sprav_title_fld'];
                        }

                        echo $fld->getField();
                        break;

                    case 'link_sprav':
                        $fld = new sbLayoutSpravLink($settings['sprav_id'], (!is_null($values[$field]) ? $values[$field].(isset($values[$field.'_link']) && !is_null($values[$field.'_link']) ? ','.$values[$field.'_link'] : '') : null), 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = $settings['subcategs'] == 1;
                        $fld->mSpravTitle = $settings['sprav_title'];

                        if (isset($settings['sprav_title_fld']))
                        {
                            $fld->mTitleFld = $settings['sprav_title_fld'];
                        }

                        echo $fld->getJavaScript();
                        echo $fld->getField();
                        break;

                    case 'radio_plugin':
                    case 'select_plugin':
                        $fld = new sbLayoutPluginData('select', $settings['ident'], $settings['modules_cat_id'], $values[$field], 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = $settings['modules_subcategs'] == 1;

                        if (isset($settings['modules_title_fld']))
                        {
                            $fld->mTitleFld = $settings['modules_title_fld'];
                        }
                        echo $fld->getField();
                        break;

                   case 'elems_plugin':
                        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident = ? AND cat_level = 0', $settings['ident']);
                        if($res)
                        {
                            $settings['modules_cat_id'] = $res[0][0];
                        }

                        $fld = new sbLayoutPluginData('select', $settings['ident'], $settings['modules_cat_id'], $values[$field], 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = 1;
                        echo $fld->getField();
                        break;

                   case 'multiselect_plugin':
                   case 'checkbox_plugin':
                        $fld = new sbLayoutPluginData('multiselect', $settings['ident'], $settings['modules_cat_id'], $values[$field], 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = $settings['modules_subcategs'] == 1;
                        $fld->mRows = 3;

                        if (isset($settings['modules_title_fld']))
                        {
                            $fld->mTitleFld = $settings['modules_title_fld'];
                        }

                        echo $fld->getField();
                        break;

                   case 'link_plugin':
                        $fld = new sbLayoutPluginLink($settings['ident'] ,$settings['modules_cat_id'], (!is_null($values[$field]) ? $values[$field].(isset($values[$field.'_link']) && !is_null($values[$field.'_link']) ? ','.$values[$field.'_link'] : '') : null), 'f_user_f_'.$value['id']);
                        $fld->mSubCategs = $settings['modules_subcategs'] == 1;
                        $fld->mSpravTitle = $settings['modules_title_fld'];

                        if (isset($settings['modules_title_fld']))
                        {
                            $fld->mTitleFld = $settings['modules_title_fld'];
                        }

                        echo $fld->getJavaScript();
                        echo $fld->getField();
                        break;
                }
                echo '</nobr></td>';
                $i++;
            }
        }
    }

    /**
     * Возвращает SQL-запрос для поиска по пользовательским полям.
     *
     * @param array $pd_fields Массив с описанием пользовательских полей.
     * @param string $field_prefix Псевдоним таблицы БД в SQL-запросе.
     * @param string $get_prefix Префикс REQUEST-параметра.
     * @param string $filter_compare Искать по вхождению или по полному совпадению в текстовых полях.
     * @param string $filter_logic Логика фильтра (AND или OR).
     * @param string $filter_text_logic Логика поиска слов в фразе (AND или OR).
     * @param object $morph_db Использовать морфологию или нет.
     * @param string $date_temp Формат ввода дат.
     *
     * @return string SQL-запрос для поиска по пользовательским полям.
     */
    static function getPluginFieldsFilterSql($pd_fields, $field_prefix, $get_prefix, $filter_compare, $filter_logic, $filter_text_logic, &$morph_db, $date_temp = '')
    {
        if (!is_array($pd_fields))
            return '';

        $filter_sql = '';
        foreach ($pd_fields as $value)
        {
            if (isset($value['sql']) && $value['sql'] == 1)
            {
                switch ($value['type'])
                {
                    case 'string':
                    case 'text':
                    case 'longtext':
                    case 'php':
                        $filter_sql .= sbGetFilterTextSql($field_prefix.'.user_f_'.$value['id'], $get_prefix.'_'.$value['id'], $filter_compare, $filter_logic, $filter_text_logic, $morph_db);
                        break;

                    case 'image':
                    case 'link':
                    case 'file':
                    case 'color':
                        if (isset($_REQUEST[$get_prefix.'_'.$value['id']]) && trim($_REQUEST[$get_prefix.'_'.$value['id']]) != '')
                        {
                            if ($filter_compare == 'IN')
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].' LIKE '.$GLOBALS['sbSql']->escape('%'.$_REQUEST[$get_prefix.'_'.$value['id']].'%').' '.$filter_logic.' ';
                            else
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].' = '.$GLOBALS['sbSql']->escape('%'.$_REQUEST[$get_prefix.'_'.$value['id']].'%').' '.$filter_logic.' ';
                        }
                        break;

                    case 'link_sprav':
                    case 'link_plugin':
                        if (isset($_REQUEST[$get_prefix.'_'.$value['id']]) && intval($_REQUEST[$get_prefix.'_'.$value['id']]) > 0)
                        {
                            if (isset($_REQUEST[$get_prefix.'_'.$value['id'].'_link']) && intval($_REQUEST[$get_prefix.'_'.$value['id'].'_link']) > 0)
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].'=\''.intval($_REQUEST[$get_prefix.'_'.$value['id']]).','.intval($_REQUEST[$get_prefix.'_'.$value['id'].'_link']).'\' '.$filter_logic.' ';
                            else
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].' LIKE \''.intval($_REQUEST[$get_prefix.'_'.$value['id']]).',%\' '.$filter_logic.' ';
                        }
                        break;

                    case 'categs':
                        if (isset($_REQUEST[$get_prefix.'_'.$value['id']]))
                        {
                            if (is_array($_REQUEST[$get_prefix.'_'.$value['id']]) && count($_REQUEST[$get_prefix.'_'.$value['id']]) > 0)
                            {
                                $sql = '';
                                foreach ($_REQUEST[$get_prefix.'_'.$value['id']] as $number)
                                {
                                    $number = intval(trim($number));
                                    if ($number > 0)
                                    {
                                        $sql .= 'CONCAT("^", '.$field_prefix.'.user_f_'.$value['id'].", \"^\") LIKE '%^".$number."^%' OR ";
                                    }
                                }

                                if ($sql != '')
                                {
                                    $filter_sql .= '('.sb_substr($sql, 0, -4).') '.$filter_logic.' ';
                                }
                            }
                            elseif (intval($_REQUEST[$get_prefix.'_'.$value['id']]) > 0)
                            {
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].' REGEXP "[[:<:]]'.intval($_REQUEST[$get_prefix.'_'.$value['id']]).'[[:>:]]" '.$filter_logic.' ';
                            }
                        }
                        break;

                    case 'select_sprav':
                    case 'radio_sprav':
                    case 'select_plugin':
                    case 'radio_plugin':
                    case 'elems_plugin':
                        if (isset($_REQUEST[$get_prefix.'_'.$value['id']]))
                        {
                            if (is_array($_REQUEST[$get_prefix.'_'.$value['id']]))
                            {
                                foreach ($_REQUEST[$get_prefix.'_'.$value['id']] as $key => $val)
                                {
                                    if (intval($val) > 0)
                                        $_REQUEST[$get_prefix.'_'.$value['id']][$key] = intval($val);
                                }
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].' IN ('.implode(',', $_REQUEST[$get_prefix.'_'.$value['id']]).') '.$filter_logic.' ';
                            }
                            elseif (intval($_REQUEST[$get_prefix.'_'.$value['id']]) > 0)
                            {
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].'="'.intval($_REQUEST[$get_prefix.'_'.$value['id']]).'" '.$filter_logic.' ';
                            }
                        }
                        break;

                    case 'multiselect_sprav':
                    case 'checkbox_sprav':
                    case 'multiselect_plugin':
                    case 'checkbox_plugin':
                        if (isset($_REQUEST[$get_prefix.'_'.$value['id']]))
                        {
                            if (is_array($_REQUEST[$get_prefix.'_'.$value['id']]) && count($_REQUEST[$get_prefix.'_'.$value['id']]) > 0)
                            {
                                $sql = '';
                                foreach ($_REQUEST[$get_prefix.'_'.$value['id']] as $number)
                                {
                                    $number = intval(trim($number));
                                    if ($number > 0)
                                    {
                                        $sql .= 'CONCAT(",", '.$field_prefix.'.user_f_'.$value['id'].", \",\") LIKE '%,".$number.",%' OR ";
                                    }
                                }

                                if ($sql != '')
                                {
                                    $filter_sql .= '('.sb_substr($sql, 0, -4).') '.$filter_logic.' ';
                                }
                            }
                            elseif (intval($_REQUEST[$get_prefix.'_'.$value['id']]) > 0)
                            {
                                $filter_sql .= 'CONCAT(",", '.$field_prefix.'.user_f_'.$value['id'].", \",\") LIKE '%,".intval(trim($_REQUEST[$get_prefix.'_'.$value['id']])).",%' ".$filter_logic.' ';
                            }
                        }
                        break;

                    case 'checkbox':
                        if (isset($_REQUEST[$get_prefix.'_'.$value['id']]))
                        {
                            if ($_REQUEST[$get_prefix.'_'.$value['id']] === '0')
                            {
                                $filter_sql .= '('.$field_prefix.'.user_f_'.$value['id'].'=0 OR '.$field_prefix.'.user_f_'.$value['id'].' IS NULL) '.$filter_logic.' ';
                            }
                            else
                            {
                                $filter_sql .= $field_prefix.'.user_f_'.$value['id'].'=1 '.$filter_logic.' ';
                            }
                        }
                        break;

                    case 'number':
                    case 'date':
                        $filter_sql .= sbGetFilterNumberSql($field_prefix.'.user_f_'.$value['id'], $get_prefix.'_'.$value['id'], $filter_logic, $value['type'] == 'date', $date_temp);
                        break;
                }
            }
        }

        return $filter_sql;
    }

    /**
     * Выводит множественный селект для выбора флажков, влияющих на вывод элементов, в макет дизайна вывода элемента или списка элементов
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param string $field_value Значение селекта.
     * @param array $field_name Имя поля селекта.
     * @param bool $categs Выводить флажки разделов (TRUE) или элементов (FALSE).
     */
    public function addPluginFieldsTempsCheckboxes($ident, $field_value, $field_name, $categs=false)
    {
        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if ($res)
        {
            list($pd_fields) = $res[0];
            if ($pd_fields != '')
            {
                $options = array();
                $pd_fields = unserialize($pd_fields);
                foreach ($pd_fields as $key => $value)
                {
                    if ($value['type'] == 'checkbox')
                    {
                        $options[$value['id']] = $value['title'];
                    }
                }

                if (count($options) > 0)
                {
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutSelect($options, $field_name.'[]', '', 'multiple="multiple"');
                    $fld->mSelOptions = $field_value;
                    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.SB_LAYOUT_PLUGIN_FIELDS_CHECKED_LABEL.'</div>';
                    $this->addField(($categs ? SB_LAYOUT_PLUGIN_CATEGS_CHECKED : SB_LAYOUT_PLUGIN_FIELDS_CHECKED), $fld);
                }
            }
        }
    }

    /**
     * Выводит макеты дизайна пользовательских полей, настраиваемых в Макетах данных модулей
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param array $values Массив, содержащий значения для каждого поля.
     * @param string $prefix Префикс, использующийся для массива.
     * @param array $tags
     * @param array $tags_values
     * @param bool $categs Выводить макеты дизайна полей разделов (TRUE) или элементов (FALSE).
     * @param string $sufix Суффикс, использующийся для массива.
     * @param string $name_prefix Префикс, использующийся для имен полей.
     * @param string $name_sufix Суффикс, использующийся для имен полей.
     * @param bool $filter Добавлять теги Начало интервала и Конец интервала (TRUE) или нет (FALSE).
     * @param bool $form Относится ли поле к форме ввода данных (TRUE) или нет (FALSE).
     * @param array $params
     *
     * @internal param array $param Массив различных параметров.
     *              $param['edit_group'] - Используется для группового редактирования (TRUE) или нет (FALSE)
     */
    public function addPluginFieldsTemps($ident, $values, $prefix, $tags = array(), $tags_values = array(), $categs = false, $sufix = '', $name_prefix = '', $name_sufix = '', $filter = false, $form = false, $params = array())
    {
        $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
        if (!isset($plugins[$ident]))
            return;

        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || $res[0][0] == '')
        {
            return;
        }

        $fields = unserialize($res[0][0]);
        if (!$fields || count($fields) <= 0)
        {
            return;
        }

        foreach ($fields as $key => $value)
        {
            $type = $value['type'];

            if ($type ==  'jscript' || $type == 'hr' || $type == 'label')
                continue;
            if($type == 'table' && ($filter || $form))
                continue;

            if ($type == 'tab')
            {
                $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.$value['title'].'</div>';
                $this->addField('', new sbLayoutHTML($html, true));
                $this->addField('', new sbLayoutDelim());
                continue;
            }

            $f_name = $name_prefix.'user_f_'.$value['id'].$name_sufix;

            if ($type == 'google_coords' || $type == 'yandex_coords')
            {
                $f_value_latitude = isset($values[$f_name.'_latitude']) ? $values[$f_name.'_latitude'] : null;
                $f_value_longtitude = isset($values[$f_name.'_longtitude']) ? $values[$f_name.'_longtitude'] : null;
            }

            $f_value = isset($values[$f_name]) ? $values[$f_name] : null;
            $f_name_latitude = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name.'_latitude]';
            $f_name_longtitude = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name.'_longtitude]';
            $f_name = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name.']';

            $f_title = isset($value['title']) ? $value['title'] : '';

            if (is_null($f_value))
            {
                switch ($type)
                {
                    case 'file':
                    case 'link':
                        $f_value = '<a href="{VALUE}">{VALUE}</a>';
                        break;

                    case 'longtext':
                    case 'string':
                    case 'text':
                    case 'checkbox':
                    case 'color':
                    case 'number':
                    case 'password':
                    case 'categs':
                        $f_value = '{VALUE}';
                        break;

                    case 'image':
                        $f_value = '<img src="{VALUE}" alt="" />';
                        break;
                    case 'table':
                        $f_value = '{VALUE}';
                        break;

                    case 'date':
                        $f_value = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
                        break;

                    case 'select_sprav':
                    case 'radio_sprav':
                    case 'link_sprav':
                        $f_value = '{SPRAV_TITLE}';
                        break;

                    case 'select_plugin':
                    case 'radio_plugin':
                    case 'link_plugin':
                    case 'elems_plugin':
                    case 'multiselect_plugin':
                        $f_value = 0;
                        break;
                }
            }

            if (($type == 'google_coords' || $type == 'yandex_coords') && is_null($f_value_latitude))
            {
                $f_value_latitude = '{VALUE}';
            }

            if (($type == 'google_coords' || $type == 'yandex_coords') && is_null($f_value_longtitude))
            {
                $f_value_longtitude = '{VALUE}';
            }

            switch ($type)
            {
                case 'date':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}', '{TIMESTAMP}'), $tags);
                    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG, KERNEL_TIMESTAMP_TAG), $tags_values);
                    $this->addField($f_title.sbGetGroupEditCheckbox($f_name, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);
                    break;

                case 'select_sprav':
                case 'radio_sprav':
                case 'link_sprav':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array('{SPRAV_CAT_TITLE}', '{SPRAV_CAT_ID}', '{SPRAV_TITLE}', '{SPRAV_ID}', '{SPRAV_DESC1}', '{SPRAV_DESC2}', '{SPRAV_DESC3}'), $tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_SPRAV_CAT_TITLE, SB_LAYOUT_SPRAV_CAT_ID, SB_LAYOUT_SPRAV_TITLE, SB_LAYOUT_SPRAV_ID, SB_LAYOUT_SPRAV_DESC1, SB_LAYOUT_SPRAV_DESC2, SB_LAYOUT_SPRAV_DESC3), $tags_values);
                    $this->addField($f_title.sbGetGroupEditCheckbox($f_name, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);
                    break;

                case 'select_plugin':
                case 'radio_plugin':
                case 'link_plugin':
                case 'elems_plugin':
                    if(isset($value['settings']['ident']) && $value['settings']['ident'] != 'pl_pages')
                        sbPlugins::getPluginDesignTemps($value['settings']['ident'], 'full', $this, $f_value, $f_name, $f_title, (isset($params['edit_group']) ? $params['edit_group'] : false));
                    break;

                case 'multiselect_plugin':
                case 'checkbox_plugin':
                    if(isset($value['settings']['ident']) && $value['settings']['ident'] != 'pl_pages')
                        sbPlugins::getPluginDesignTemps($value['settings']['ident'], 'list', $this, $f_value, $f_name, $f_title, (isset($params['edit_group']) ? $params['edit_group'] : false));
                    break;

                case 'multiselect_sprav':
                case 'checkbox_sprav':
                    $res = sql_query('SELECT categs.cat_title, sprav.st_id, sprav.st_title FROM sb_categs categs, sb_catlinks links, sb_sprav_temps sprav WHERE sprav.st_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, sprav.st_title', 'pl_sprav_design');
                    
                    $options = array('-1' => PLUGINS_DESIGN_TEMPS_SELECT_GET_ONLY_DATA);
                    if ($res)
                    {
                        $old_cat_title = '';
                        foreach ($res as $value)
                        {
                            list($cat_title, $st_id, $st_title) = $value;
                            if ($old_cat_title != $cat_title)
                            {
                                $options[uniqid()] = '-'.$cat_title;
                                $old_cat_title = $cat_title;
                            }
                            $options[$st_id] = $st_title;
                        }
                    }
                    else
                    {
                        $fldLabel = new sbLayoutLabel('<div class="hint_div">'.SB_LAYOUT_SPRAV_NO_DESIGN_MSG.'</div>', '', '', false);
                    }

					$fld = new sbLayoutSelect($options, $f_name);
                    $fld->mSelOptions = array($f_value);
                    $fld->mHTML = isset($fldLabel) ? $fldLabel->getField() : '';
                    
                    $this->addField($f_title.sbGetGroupEditCheckbox($f_name, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);
                    
                    break;

                case 'google_coords':
                case 'yandex_coords':
                    $own_tags = array('{VALUE}');
                    $own_values = array(SB_LAYOUT_VALUE);

                    $fld = new sbLayoutTextarea($f_value_latitude, $f_name_latitude, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge($own_tags, $tags);
                    $fld->mValues = array_merge($own_values, $tags_values);
                    $this->addField($f_title.' ('.KERNEL_LATITUDE.')'.sbGetGroupEditCheckbox($f_name_latitude, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);

                    $fld = new sbLayoutTextarea($f_value_longtitude, $f_name_longtitude, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge($own_tags, $tags);
                    $fld->mValues = array_merge($own_values, $tags_values);
                    $this->addField($f_title.' ('.KERNEL_LONGTITUDE.')'.sbGetGroupEditCheckbox($f_name_longtitude, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);
                    break;

                case 'checkbox':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');

                    $own_tags = array('{VALUE}', '{VALUE_INT}');
                    $own_values = array(SB_LAYOUT_VALUE_CHECKBOX, SB_LAYOUT_VALUE);

                    $fld->mTags = array_merge($own_tags, $tags);
                    $fld->mValues = array_merge($own_values, $tags_values);
                    $this->addField($f_title.sbGetGroupEditCheckbox($f_name, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);
                    break;

                default:
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');

                    $own_tags = array('{VALUE}');
                    $own_values = array(SB_LAYOUT_VALUE);

                    if ($type == 'image')
                    {
                        $own_tags[] = '{SIZE}';
                        $own_tags[] = '{WIDTH}';
                        $own_tags[] = '{HEIGHT}';

                        $own_values[] = SB_LAYOUT_VALUE_SIZE;
                        $own_values[] = SB_LAYOUT_VALUE_WIDTH;
                        $own_values[] = SB_LAYOUT_VALUE_HEIGHT;
                    }
                    elseif ($type == 'file')
                    {
                        $own_tags[] = '{SIZE}';
                        $own_values[] = SB_LAYOUT_VALUE_SIZE;
                    }

                    $fld->mTags = array_merge($own_tags, $tags);
                    $fld->mValues = array_merge($own_values, $tags_values);
                    $this->addField($f_title.sbGetGroupEditCheckbox($f_name, (isset($params['edit_group']) ? $params['edit_group'] : false)), $fld);
                    break;
            }
        }
    }

    /** Выводит макеты дизайна ввода пользовательских полей, настраиваемых в Макетах данных модулей
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param array $values Массив, содержащий значения для каждого поля.
     * @param string $prefix Префикс, использующийся для массива.
     * @param string $sufix Суффикс, использующийся для массива.
     * @param array $tags
     * @param array $tags_values
     * @param bool $categs Выводить макеты дизайна полей разделов (TRUE) или элементов (FALSE).
     * @param bool $add_required Добавлять (TRUE) или нет (FALSE) чекбокс "Обязательно для заполнения".
     * @param string $field_prefix Префикс имени поля (name), заменяет user_f.
     * @param string $field_sufix Суфикс имени поля (name).
     * @param bool $filter Добавлять поля lo и hi (TRUE) или нет (FALSE).
     * @param bool $form Форма ввода (TRUE) или нет (FALSE).
     */
    public function addPluginInputFieldsTemps($ident, $values, $prefix, $sufix = '', $tags = array(), $tags_values = array(), $categs = false, $add_required = true, $field_prefix = '', $field_sufix = '', $filter = false, $form = false)
    {
        $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
        if (!isset($plugins[$ident]))
            return;

        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || trim($res[0][0]) == '')
        {
            return;
        }

        $fields = unserialize($res[0][0]);
        if (!$fields || count($fields) <= 0)
        {
            return;
        }

        $tags = array('{VALUE}');
        $tags_values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);
        $num_post = count($_POST);

        foreach ($fields as $key => $value)
        {
            $type = $value['type'];

            if ($type ==  'jscript' || $type == 'hr' || $type == 'label' || $type == 'php' || (($type == 'google_coords' || $type == 'yandex_coords') && $filter))
                continue;

            if ($type == 'tab')
            {
                $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.$value['title'].'</div>';
                $this->addField('', new sbLayoutHTML($html, true));
                $this->addField('', new sbLayoutDelim());
                continue;
            }
            if($type == 'table' && ($filter || $form))
                continue;

            $f_name_site = ($field_prefix != '' ? $field_prefix : 'user_f').'_'.$value['id'].($field_sufix != '' ? '_'.$field_sufix : '');

            $f_name = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.']';
            $f_name_lo = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_lo]';
            $f_name_hi = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_hi]';
            $f_name_link = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_link]';
            $f_name_req = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_need]';
            $f_name_opt = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_opt]';
            $f_name_opt_link = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_opt_link]';
            $f_name_template = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_template]';
            $f_name_pic_now = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_pic_now]';
            $f_name_file_now = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_file_now]';

            $f_title = '';
            $f_title_lo = '';
            $f_title_hi = '';
            $f_title_link = '';

            if ($type == 'link_sprav')
            {
                $f_title_link = $value['settings']['sprav_title'] != '' ? $value['title'].' ('.$value['settings']['sprav_title'].')' : $value['title'].' ('.SB_LAYOUT_LINK_SPRAV.')';
            }

            if ($type == 'link_plugin')
            {
                $f_title_module_link = $value['settings']['modules_link_title'] != '' ? $value['title'].' ('.$value['settings']['modules_link_title'].')' : $value['title'].' ('.SB_LAYOUT_LINK_SPRAV.')';
            }

            if ($type == 'select_plugin' || $type == 'radio_plugin' || $type == 'checkbox_plugin' || $type == 'multiselect_plugin' || $type == 'elems_plugin' || $type == 'link_plugin')
            {
                $default_links_title_tag = '';
                if($value['settings']['ident'] == 'pl_news')
                    $default_links_title_tag = $_SESSION['sbPlugins']->mFieldsInfo['pl_news']['fields']['n_title']['tag'];
                elseif($value['settings']['ident'] == 'pl_imagelib')
                    $default_links_title_tag = $_SESSION['sbPlugins']->mFieldsInfo['pl_imagelib']['fields']['im_title']['tag'];
                elseif($value['settings']['ident'] == 'pl_services_rutube')
                    $default_links_title_tag = $_SESSION['sbPlugins']->mFieldsInfo['pl_services_rutube']['fields']['ssr_name']['tag'];
                elseif($value['settings']['ident'] == 'pl_faq')
                    $default_links_title_tag = $_SESSION['sbPlugins']->mFieldsInfo['pl_faq']['fields']['f_url']['tag'];
                elseif($value['settings']['ident'] == 'pl_polls')
                    $default_links_title_tag = $_SESSION['sbPlugins']->mFieldsInfo['pl_polls']['fields']['sp_question']['tag'];
                elseif(strpos($value['settings']['ident'], 'pl_plugin_') !== false)
                    $default_links_title_tag = $_SESSION['sbPlugins']->mFieldsInfo[$value['settings']['ident']]['fields']['p_title']['tag'];

            }

            if ($type == 'google_coords' || $type == 'yandex_coords')
            {
                $f_name_latitude = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_latitude]';
                $f_name_longtitude = $prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_longtitude]';

                if (isset($value['title']))
                {
                    if ($add_required)
                    {
                        $f_title_latitude = $value['title'].' ('.KERNEL_LATITUDE.')<br><input type="checkbox" value="1" name="'.$prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_latitude_need]'.'" id="'.$prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_latitude_need]'.'"'.(isset($values[$f_name_site.'_latitude_need']) && isset($values[$f_name_site.'_latitude_need']) == 1 ? 'checked' : '').'><label for="'.$prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_latitude]'.'">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>';;
                        $f_title_longtitude = $value['title'].' ('.KERNEL_LONGTITUDE.')<br><input type="checkbox" value="1" name="'.$prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_longtitude_need]'.'" id="'.$prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_longtitude_need]'.'"'.(isset($values[$f_name_site.'_longtitude_need']) && isset($values[$f_name_site.'_longtitude_need']) == 1 ? 'checked' : '').'><label for="'.$prefix.($categs ? 'categs' : 'fields').'_temps'.$sufix.'['.$f_name_site.'_longtitude]'.'">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>';;
                    }
                    else
                    {
                        $f_title_latitude = $value['title'].' ('.KERNEL_LATITUDE.')';
                        $f_title_longtitude = $value['title'].' ('.KERNEL_LONGTITUDE.')';
                    }
                }
                else
                {
                    $f_title_latitude = '';
                    $f_title_longtitude = '';
                }

                $f_value_latitude = isset($values[$f_name_site.'_latitude']) ? $values[$f_name_site.'_latitude'] : null;
                $f_value_longtitude = isset($values[$f_name_site.'_longtitude']) ? $values[$f_name_site.'_longtitude'] : null;

                $f_value_default_latitude = '';
                $f_value_default_longtitude = '';
            }

            if (isset($value['title']))
            {
                if ($add_required)
                {
                    $f_title = $value['title'].'<br><input type="checkbox" value="1" name="'.$f_name_req.'" id="'.$f_name_req.'"'.(isset($values[$f_name_site.'_need']) && isset($values[$f_name_site.'_need']) == 1 ? 'checked' : '').'><label for="'.$f_name_req.'">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>';
                    $f_title_lo = $value['title'].'<br />('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_LO.')'.'<br><input type="checkbox" value="1" name="'.$f_name_req.'" id="'.$f_name_req.'"'.(isset($values[$f_name_site.'_need']) && isset($values[$f_name_site.'_need']) == 1 ? 'checked' : '').'><label for="'.$f_name_req.'">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>';
                    $f_title_hi = $value['title'].'<br />('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_HI.')'.'<br><input type="checkbox" value="1" name="'.$f_name_req.'" id="'.$f_name_req.'"'.(isset($values[$f_name_site.'_need']) && isset($values[$f_name_site.'_need']) == 1 ? 'checked' : '').'><label for="'.$f_name_req.'">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>';
                }
                else
                {
                    $f_title = $value['title'];
                    $f_title_lo = $value['title'].'<br /><span style="font-weight: normal;">('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_LO.')</span>';
                    $f_title_hi = $value['title'].'<br /><span style="font-weight: normal;">('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_HI.')</span>';
                }
            }

            $f_value = isset($values[$f_name_site]) ? $values[$f_name_site] : null;
            $f_value_lo = isset($values[$f_name_site.'_lo']) ? $values[$f_name_site.'_lo'] : null;
            $f_value_hi = isset($values[$f_name_site.'_hi']) ? $values[$f_name_site.'_hi'] : null;
            $f_value_opt = isset($values[$f_name_site.'_opt']) ? $values[$f_name_site.'_opt'] : null;
            $f_value_link = isset($values[$f_name_site.'_link']) ? $values[$f_name_site.'_link'] : null;
            $f_value_opt_link = isset($values[$f_name_site.'_opt_link']) ? $values[$f_name_site.'_opt_link'] : null;
            $f_value_pic_now = isset($values[$f_name_site.'_pic_now']) ? $values[$f_name_site.'_pic_now'] : null;
            $f_value_file_now = isset($values[$f_name_site.'_file_now']) ? $values[$f_name_site.'_file_now'] : null;

            $f_value_default = '';
            $f_value_default_lo = '';
            $f_value_default_hi = '';
            $f_value_default_opt = '';
            $f_value_default_link = '';
            $f_value_default_opt_link = '';

            switch ($type)
            {
                case 'file':
                case 'image':
                    $f_value_default = '<input type=\'file\' name=\''.$f_name_site.'\' value=\'\' />';
                    break;

                case 'date':
                case 'number':
                    $f_value_default = '<input type=\'text\' name=\''.$f_name_site.'\' value=\'{VALUE}\' />';
                    $f_value_default_lo = '<input type=\'text\' name=\''.$f_name_site.'_lo\' value=\'{VALUE}\' />';
                    $f_value_default_hi = '<input type=\'text\' name=\''.$f_name_site.'_hi\' value=\'{VALUE}\' />';
                    break;

                case 'link':
                case 'string':
                case 'color':
                    $f_value_default = '<input type=\'text\' name=\''.$f_name_site.'\' value=\'{VALUE}\' />';
                    break;

                case 'password':
                    $f_value_default = '<input type=\'password\' name=\''.$f_name_site.'\' value=\'{VALUE}\' />';
                    break;

                case 'longtext':
                case 'text':
                    $f_value_default = '<textarea name=\''.$f_name_site.'\'>{VALUE}</textarea>';
                    break;

                case 'checkbox':
                    $f_value_default = '<input type=\'checkbox\' name=\''.$f_name_site.'\' value=\'1\'{OPT_SELECTED} />
<input type=\'hidden\' name=\''.$f_name_site.'_cb\' value=\'1\' />';
                    break;

                case 'categs':
                case 'select_sprav':
                    $f_value_default = '<select name=\''.$f_name_site.'\'>
{OPTIONS}
</select>';
                    $f_value_default_opt = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>{OPT_TEXT}</option>';
                    break;

                case 'select_plugin':
                case 'elems_plugin':
                    $f_value_default = '<select name=\''.$f_name_site.'\'>
{OPTIONS}
</select>';
                    $f_value_default_opt = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>'.$default_links_title_tag.'</option>';
                    break;

                case 'radio_sprav':
                    $f_value_default = '{OPTIONS}';
                    $f_value_default_opt = '<input type=\'radio\' name=\''.$f_name_site.'\' value=\'{OPT_VALUE}\'{OPT_SELECTED} /> - {OPT_TEXT}';
                    break;

                case 'radio_plugin':
                    $f_value_default = '{OPTIONS}';
                    $f_value_default_opt = '<input type=\'radio\' name=\''.$f_name_site.'\' value=\'{OPT_VALUE}\'{OPT_SELECTED} /> - '.$default_links_title_tag;
                    break;

                case 'multiselect_sprav':
                    $f_value_default = '<select name=\''.$f_name_site.'[]\' multiple=\'multiple\' size=\'5\'>
{OPTIONS}
</select>';
                    $f_value_default_opt = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>{OPT_TEXT}</option>';
                    break;

                 case 'multiselect_plugin':
                    $f_value_default = '<select name=\''.$f_name_site.'[]\' multiple=\'multiple\' size=\'5\'>
{OPTIONS}
</select>';
                    $f_value_default_opt = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>'.$default_links_title_tag.'</option>';
                    break;

                case 'checkbox_sprav':
                    $f_value_default = '{OPTIONS}';
                    $f_value_default_opt = '<input type=\'checkbox\' name=\''.$f_name_site.'[]\' value=\'{OPT_VALUE}\'{OPT_SELECTED} /> - {OPT_TEXT}';
                    break;

                case 'checkbox_plugin':
                    $f_value_default = '{OPTIONS}';
                    $f_value_default_opt = '<input type=\'checkbox\' name=\''.$f_name_site.'[]\' value=\'{OPT_VALUE}\'{OPT_SELECTED} /> - '.$default_links_title_tag;
                    break;

                case 'link_sprav':
                    $f_value_default = '<select name=\''.$f_name_site.'\' id=\''.$f_name_site.'\' fname=\'user_f_'.$value['id'].'\' ident=\''.$ident.($categs ? '_categs' : '').'\' onchange=\'sbGetLinkData(this, 0)\'>
{OPTIONS}
</select>';
                    $f_value_default_opt = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>{OPT_TEXT}</option>';
                    $f_value_default_link = '<select name=\''.$f_name_site.'_link\' id=\''.$f_name_site.'_link\'>
{OPTIONS}
</select>';
                    $f_value_default_opt_link = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>{OPT_TEXT}</option>';
                    break;

                 case 'link_plugin':
                    $f_value_default = '<select name=\''.$f_name_site.'\' id=\''.$f_name_site.'\' fname=\'user_f_'.$value['id'].'\' ident=\''.$ident.($categs ? '_categs' : '').'\' onchange=\'sbGetLinkData(this, 1)\'>
{OPTIONS}
</select>';
                    $f_value_default_opt = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>{OPT_TEXT}</option>';
                    $f_value_default_link = '<select name=\''.$f_name_site.'_link\' id=\''.$f_name_site.'_link\'>
{OPTIONS}
</select>';
                    $f_value_default_opt_link = '<option value=\'{OPT_VALUE}\'{OPT_SELECTED}>'.$default_links_title_tag.'</option>';
                    break;

                case 'google_coords':
                case 'yandex_coords':
                    $f_value_default_latitude = '<input type=\'text\' name=\''.$f_name_site.'_latitude\' id=\''.$f_name_site.'_latitude\' value=\'{VALUE}\' />';
                    $f_value_default_longtitude = '<input type=\'text\' name=\''.$f_name_site.'_longtitude\' id=\''.$f_name_site.'_longtitude\' value=\'{VALUE}\' />';
                    break;
            }

            if (is_null($f_value))
            {
                $f_value = $f_value_default;
                $f_value_opt = $f_value_default_opt;
                $f_value_link = $f_value_default_link;
                $f_value_opt_link = $f_value_default_opt_link;
            }

            if (is_null($f_value_lo))
            {
                $f_value_lo = $f_value_default_lo;
            }

            if (is_null($f_value_hi))
            {
                $f_value_hi = $f_value_default_hi;
            }
            if (is_null($f_value_pic_now))
            {
                $f_value_pic_now = SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG;
            }
            if (is_null($f_value_file_now))
            {
                $f_value_file_now = SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_TAG;
            }

            if ($type == 'google_coords' || $type == 'yandex_coords')
            {
                if (is_null($f_value_latitude))
                {
                    $f_value_latitude = $f_value_default_latitude;
                }

                if (is_null($f_value_longtitude))
                {
                    $f_value_longtitude = $f_value_default_longtitude;
                }
            }

            switch ($type)
            {
                case 'categs':
                case 'select_sprav':
                case 'multiselect_sprav':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default, '{OPTIONS}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title, $fld);

                    $fld = new sbLayoutTextarea($f_value_opt, $f_name_opt, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_opt, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_DESC1}', '{OPT_DESC2}', '{OPT_DESC3}', '{OPT_SELECTED}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_SPRAV_CAT_TITLE, SB_LAYOUT_SPRAV_CAT_ID, SB_LAYOUT_SPRAV_TITLE, SB_LAYOUT_SPRAV_ID, SB_LAYOUT_SPRAV_DESC1, SB_LAYOUT_SPRAV_DESC2, SB_LAYOUT_SPRAV_DESC3, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
                    $this->addField('', $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                case 'select_plugin':
                case 'elems_plugin':
                case 'multiselect_plugin':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default, '{OPTIONS}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title, $fld);

                    $title_flds_tags = array();
                    $title_flds_vals = array();
                    $title_flds = getPluginTitleFields($value['settings']['ident']);
                    foreach ($title_flds as $v)
                    {
                        $title_flds_tags[] = $v['tag'];
                        $title_flds_vals[] = $v['title'];
                    }
                    $fld = new sbLayoutTextarea($f_value_opt, $f_name_opt, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array($f_value_default_opt, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_SELECTED}', '{OPT_VALUE}'), $title_flds_tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_ELEM_CAT_TITLE, SB_LAYOUT_ELEM_CAT_ID, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG, SB_LAYOUT_ELEM_ID),$title_flds_vals);
                    $this->addField('', $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                case 'radio_sprav':
                case 'checkbox_sprav':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default);
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title, $fld);

                    $fld = new sbLayoutTextarea($f_value_opt, $f_name_opt, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_opt, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_DESC1}', '{OPT_DESC2}', '{OPT_DESC3}', '{OPT_SELECTED}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_SPRAV_CAT_TITLE, SB_LAYOUT_SPRAV_CAT_ID, SB_LAYOUT_SPRAV_TITLE, SB_LAYOUT_SPRAV_ID, SB_LAYOUT_SPRAV_DESC1, SB_LAYOUT_SPRAV_DESC2, SB_LAYOUT_SPRAV_DESC3, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
                    $this->addField('', $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                case 'radio_plugin':
                case 'checkbox_plugin':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default);
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title, $fld);

                    $title_flds_tags = array();
                    $title_flds_vals = array();
                    $title_flds = getPluginTitleFields($value['settings']['ident']);
                    foreach ($title_flds as $v)
                    {
                        $title_flds_tags[] = $v['tag'];
                        $title_flds_vals[] = $v['title'];
                    }
                    $fld = new sbLayoutTextarea($f_value_opt, $f_name_opt, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array($f_value_default_opt, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_SELECTED}', '{OPT_VALUE}'), $title_flds_tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_ELEM_CAT_TITLE, SB_LAYOUT_ELEM_CAT_ID, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG, SB_LAYOUT_ELEM_ID), $title_flds_vals);
                    $this->addField('', $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                case 'link_sprav':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default, '{OPTIONS}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title, $fld);

                    $fld = new sbLayoutTextarea($f_value_opt, $f_name_opt, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_opt, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_DESC1}', '{OPT_DESC2}', '{OPT_DESC3}', '{OPT_SELECTED}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_SPRAV_CAT_TITLE, SB_LAYOUT_SPRAV_CAT_ID, SB_LAYOUT_SPRAV_TITLE, SB_LAYOUT_SPRAV_ID, SB_LAYOUT_SPRAV_DESC1, SB_LAYOUT_SPRAV_DESC2, SB_LAYOUT_SPRAV_DESC3, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
                    $this->addField('', $fld);

                    $fld = new sbLayoutTextarea($f_value_link, $f_name_link, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_link, '{OPTIONS}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title_link, $fld);

                    $fld = new sbLayoutTextarea($f_value_opt_link, $f_name_opt_link, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_opt_link, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_DESC1}', '{OPT_DESC2}', '{OPT_DESC3}', '{OPT_SELECTED}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_SPRAV_CAT_TITLE, SB_LAYOUT_SPRAV_CAT_ID, SB_LAYOUT_SPRAV_TITLE, SB_LAYOUT_SPRAV_ID, SB_LAYOUT_SPRAV_DESC1, SB_LAYOUT_SPRAV_DESC2, SB_LAYOUT_SPRAV_DESC3, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
                    $this->addField('', $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                 case 'link_plugin':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default, '{OPTIONS}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title, $fld);

                    $fld = new sbLayoutTextarea($f_value_opt, $f_name_opt, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_opt, '{OPT_TEXT}', '{OPT_VALUE}', '{OPT_SELECTED}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_ELEM_CAT_TITLE, SB_LAYOUT_ELEM_CAT_ID, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
                    $this->addField('', $fld);

                    $fld = new sbLayoutTextarea($f_value_link, $f_name_link, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default_link, '{OPTIONS}');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
                    $this->addField($f_title_module_link, $fld);

                    $title_flds_tags = array();
                    $title_flds_vals = array();
                    $title_flds = getPluginTitleFields($value['settings']['ident']);
                    foreach ($title_flds as $v)
                    {
                        $title_flds_tags[] = $v['tag'];
                        $title_flds_vals[] = $v['title'];
                    }
                    $fld = new sbLayoutTextarea($f_value_opt_link, $f_name_opt_link, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array($f_value_default_opt_link, '{OPT_CAT_TITLE}', '{OPT_CAT_ID}', '{OPT_SELECTED}', '{OPT_VALUE}'), $title_flds_tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_ELEM_CAT_TITLE, SB_LAYOUT_ELEM_CAT_ID, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG, SB_LAYOUT_ELEM_ID), $title_flds_vals);
                    $this->addField('', $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                case 'checkbox':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default);
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
                    $this->addField($f_title, $fld);
                    break;
                case 'image':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default, '<input type=\'checkbox\' name=\'user_f_'.$value['id'].'_delete\'>');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_DELETE, $value['title']));
                    $this->addField($f_title, $fld);

                    $fld = new sbLayoutTextarea($f_value_pic_now, $f_name_pic_now, '', 'style="width:100%; height:50px;"');
                    $fld->mTags = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TAG);
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_FIELD);
                    $this->addField(sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_NOW, $value['title']), $fld);
                    break;
                case 'file':
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array($f_value_default, '<input type=\'checkbox\' name=\'user_f_'.$value['id'].'_delete\'>');
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_DELETE, $value['title']));
                    $this->addField($f_title, $fld);

                    $fld = new sbLayoutTextarea($f_value_file_now, $f_name_file_now, '', 'style="width:100%; height:50px;"');
                    $fld->mTags = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_TAG,SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_URL_TAG);
                    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_FIELD, SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_URL_FIELD);
                    $this->addField(sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_NOW, $value['title']), $fld);
                    break;

                case 'date':
                case 'number':
                    if (!$filter)
                    {
                        $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                        $fld->mTags = array_merge(array($f_value_default), $tags);
                        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                        $this->addField($f_title, $fld);
                    }
                    else
                    {
                        $this->addField('', new sbLayoutDelim());

                        $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                        $fld->mTags = array_merge(array($f_value_default), $tags);
                        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                        $this->addField($f_title, $fld);

                        $fld = new sbLayoutTextarea($f_value_lo, $f_name_lo, '', 'style="width:100%;height:50px;"');
                        $fld->mTags = array_merge(array($f_value_default_lo), $tags);
                        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                        $this->addField($f_title_lo, $fld);

                        $fld = new sbLayoutTextarea($f_value_hi, $f_name_hi, '', 'style="width:100%;height:50px;"');
                        $fld->mTags = array_merge(array($f_value_default_hi), $tags);
                        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                        $this->addField($f_title_hi, $fld);

                        $this->addField('', new sbLayoutDelim());
                    }
                    break;

                case 'google_coords':
                case 'yandex_coords':
                    $this->addField('', new sbLayoutDelim());

                    $fld = new sbLayoutTextarea($f_value_latitude, $f_name_latitude, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array($f_value_default_latitude), $tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                    $this->addField($f_title_latitude, $fld);

                    $fld = new sbLayoutTextarea($f_value_longtitude, $f_name_longtitude, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array($f_value_default_longtitude), $tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                    $this->addField($f_title_longtitude, $fld);

                    $this->addField('', new sbLayoutDelim());
                    break;

                default:
                    $fld = new sbLayoutTextarea($f_value, $f_name, '', 'style="width:100%;height:50px;"');
                    $fld->mTags = array_merge(array($f_value_default), $tags);
                    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $tags_values);
                    $this->addField($f_title, $fld);
                    break;
            }
        }
    }

    /**
     * Возвращает массив тегов и их описаний для пользовательских полей
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param array $tags Ссылка на массив тегов.
     * @param array $tags_values Ссылка на массив описаний.
     * @param bool $categs Выводить поля разделов (TRUE) или элементов (FALSE).
     * @param bool $input Прибавлять к названию поля приставку "Поле ввода ..." (TRUE) или нет (FALSE).
     * @param bool $require Вставлять теги начала и конца выделения обязательных полей (TRUE) или нет (FALSE).
     * @param bool $filter Добавлять теги Начало интервала и Конец интервала (TRUE) или нет (FALSE).
     * @param bool $form Относится ли поле к форме (TRUE) или нет (FALSE).
     * @param bool $cur_data
     *
     * @internal param bool $car_data выводить теги текущих значений изображений и файлов (TRUE) или нет (FALSE).
     */
    public function getPluginFieldsTags($ident, &$tags, &$tags_values, $categs = false, $input = false, $require = false, $filter = false, $form = false, $cur_data = false)
    {
        $plugins = $_SESSION['sbPlugins']->getPluginsInfo();
        if (!isset($plugins[$ident]))
            return;

        $res = sql_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || $res[0][0] == '')
        {
            return;
        }

        $fields = unserialize($res[0][0]);
        if (!$fields || count($fields) <= 0)
        {
            return;
        }

        foreach ($fields as $key => $value)
        {
            $type = $value['type'];

            if ($type ==  'jscript' || $type == 'hr' || $type == 'label' || ($type == 'php' && ($input || $filter)) || (($type == 'google_coords' || $type == 'yandex_coords') && $filter))
                continue;
            if($type == 'table' && ($filter || $form))
                continue;
            $f_title = '';
            $f_title_lo = '';
            $f_title_hi = '';
            $f_title_latitude = '';
            $f_title_longtitude = '';

            if (isset($value['title']))
            {
                $f_title = $value['title'];

                if (($type == 'number' || $type == 'date') && $filter)
                {
                    $f_title_lo = $value['title'].' ('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_LO.')';
                    $f_title_hi = $value['title'].' ('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_HI.')';
                }
                elseif ($type == 'google_coords' || $type == 'yandex_coords')
                {
                    $f_title .= ' ('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_MAP_CODE.')';
                    $f_title_latitude = $value['title'].' ('.KERNEL_LATITUDE.')';
                    $f_title_longtitude = $value['title'].' ('.KERNEL_LONGTITUDE.')';
                }

                if ($input && $type != 'tab')
                {
                    if ($type == 'checkbox')
                    {
                        $f_title = SB_LAYOUT_PLUGIN_INPUT_FIELDS_CHK_TITLE.' "'.$f_title.'"';
                    }
                    elseif ($type == 'categs' || $type == 'select_sprav' || $type == 'radio_sprav' ||
                            $type == 'multiselect_sprav' || $type == 'checkbox_sprav' || $type == 'link_sprav' ||
                            $type == 'select_plugin' || $type == 'radio_plugin' || $type == 'checkbox_plugin' || $type == 'link_plugin' || $type == 'elems_plugin')
                    {
                        $f_title = SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE.' "'.$f_title.'"';
                    }
                    elseif (($type == 'number' || $type == 'date') && $filter)
                    {
                        $f_title = SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE.' "'.$f_title.'"';
                        $f_title_lo = SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE.' "'.$f_title_lo.'"';
                        $f_title_hi = SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE.' "'.$f_title_hi.'"';
                    }
                    elseif ($type == 'google_coords' || $type == 'yandex_coords')
                    {
                        $f_title = SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG.' "'.$f_title.'"';
                        $f_title_latitude = SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE.' "'.$f_title_latitude.'"';
                        $f_title_longtitude = SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE.' "'.$f_title_longtitude.'"';
                    }
                    else
                    {
                        $f_title = SB_LAYOUT_PLUGIN_INPUT_FIELDS_IN_TITLE.' "'.$f_title.'"';
                    }
                }
            }

            if ($type == 'tab')
            {
                $tags[] = '-';
            }
            elseif (($type == 'number' || $type == 'date') && $filter)
            {
                $tags[] = $require ? '{'.$value['tag'].'_SELECT_START}'.$value['title'].'{'.$value['tag'].'_SELECT_END}: {'.$value['tag'].'}' : '{'.$value['tag'].'}';
                $tags[] = $require ? '{'.$value['tag'].'_LO_SELECT_START}'.$value['title'].' ('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_LO.')'.'{'.$value['tag'].'_LO_SELECT_END}: {'.$value['tag'].'_LO}' : '{'.$value['tag'].'_LO}';
                $tags[] = $require ? '{'.$value['tag'].'_HI_SELECT_START}'.$value['title'].' ('.SB_LAYOUT_PLUGIN_INPUT_FIELDS_HI.')'.'{'.$value['tag'].'_HI_SELECT_END}: {'.$value['tag'].'_HI}' : '{'.$value['tag'].'_HI}';
            }
            elseif ($type == 'google_coords' || $type == 'yandex_coords')
            {
                if ($input)
                {
                    $f_name_site = 'user_f_'.$value['id'];

                    $tags[] = str_replace(array('{DIV_ID}', '{LATITUDE_ID}', '{LONGTITUDE_ID}', '{API_KEY}'), array($value['tag'], $f_name_site.'_latitude', $f_name_site.'_longtitude', '{'.$value['tag'].'_API_KEY}'), $type == 'google_coords' ? SB_LAYOUT_GOOGLE_MAP_CODE_INPUT : SB_LAYOUT_YANDEX_MAP_CODE_INPUT);
                }
                else
                {
                    $tags[] = str_replace(array('{LATITUDE}', '{LONGTITUDE}', '{DIV_ID}', '{API_KEY}'), array('{'.$value['tag'].'_LATITUDE}', '{'.$value['tag'].'_LONGTITUDE}', $value['tag'], '{'.$value['tag'].'_API_KEY}'), $type == 'google_coords' ? SB_LAYOUT_GOOGLE_MAP_CODE_FULL : SB_LAYOUT_YANDEX_MAP_CODE_FULL);
                }

                $tags[] = $require ? '{'.$value['tag'].'_LATITUDE_SELECT_START}'.$value['title'].' ('.KERNEL_LATITUDE.')'.'{'.$value['tag'].'_LATITUDE_SELECT_END}: {'.$value['tag'].'_LATITUDE}' : '{'.$value['tag'].'_LATITUDE}';
                $tags[] = $require ? '{'.$value['tag'].'_LONGTITUDE_SELECT_START}'.$value['title'].' ('.KERNEL_LONGTITUDE.')'.'{'.$value['tag'].'_LONGTITUDE_SELECT_END}: {'.$value['tag'].'_LONGTITUDE}' : '{'.$value['tag'].'_LONGTITUDE}';
            }
            elseif($type == 'image')
            {
                $tags[] = $require ? '{'.$value['tag'].'_SELECT_START}'.$value['title'].'{'.$value['tag'].'_SELECT_END}: {'.$value['tag'].'}' : '{'.$value['tag'].'}';
                if($cur_data)
                {
                    $tags[] = $require ? '{'.$value['tag'].'_NOW_SELECT_START}'.sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_TITLE, $value['title']).'{'.$value['tag'].'_NOW_SELECT_END}: {'.$value['tag'].'_NOW}' : '{'.$value['tag'].'_NOW}';
                }
            }
            elseif($type == 'file')
            {
                $tags[] = $require ? '{'.$value['tag'].'_SELECT_START}'.$value['title'].'{'.$value['tag'].'_SELECT_END}: {'.$value['tag'].'}' : '{'.$value['tag'].'}';
                if($cur_data)
                {
                    $tags[] = $require ? '{'.$value['tag'].'_NOW_SELECT_START}'.sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_TITLE, $value['title']).'{'.$value['tag'].'_NOW_SELECT_END}: {'.$value['tag'].'_NOW}' : '{'.$value['tag'].'_NOW}';
                }
            }
            else
            {
                $tags[] = $require ? '{'.$value['tag'].'_SELECT_START}'.$value['title'].'{'.$value['tag'].'_SELECT_END}: {'.$value['tag'].'}' : '{'.$value['tag'].'}';
            }

            if (($type == 'number' || $type == 'date') && $filter)
            {
                $tags_values[] = $f_title;
                $tags_values[] = $f_title_lo;
                $tags_values[] = $f_title_hi;
            }
            elseif ($type == 'google_coords' || $type == 'yandex_coords')
            {
                $tags_values[] = $f_title;
                $tags_values[] = $f_title_latitude;
                $tags_values[] = $f_title_longtitude;
            }
            elseif($type == 'image')
            {
                $tags_values[] = $f_title;
                if($cur_data)
                {
                    $tags_values[] = sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_PIC_NOW_TAG,$value['title']);
                }

            }
            elseif($type == 'file')
            {
                $tags_values[] = $f_title;
                if($cur_data)
                {
                    $tags_values[] = sprintf(SB_LAYOUT_PLUGIN_INPUT_FIELDS_FILE_NOW_TAG,$value['title']);
                }
            }
            else
            {
                $tags_values[] = $f_title;
            }

            if ($type == 'link_sprav' && $input)
            {
                $tags[] = '{'.$value['tag'].'_LINK}';
                $tags_values[] = $value['settings']['sprav_title'] != '' ? SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE.' "'.$value['title'].' ('.$value['settings']['sprav_title'].')"' : SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE.' "'.$value['title'].' ('.SB_LAYOUT_LINK_SPRAV.')"';
            }

            if ($type == 'link_plugin' && $input)
            {
                $tags[] = '{'.$value['tag'].'_LINK}';
                $tags_values[] = $value['settings']['modules_link_title'] != '' ? SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE.' "'.$value['title'].' ('.$value['settings']['modules_link_title'].')"' : SB_LAYOUT_PLUGIN_INPUT_FIELDS_SEL_TITLE.' "'.$value['title'].' ('.SB_LAYOUT_LINK_SPRAV.')"';
            }
        }
    }

    /**
     * Возвращает массив тегов и их описаний для пользовательских полей (Сортировка)
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param array $user_flds_tags Ссылка на массив тегов.
     * @param array $user_flds_vals Ссылка на массив описаний.
     * @param string $tags_type Тип возвращаемых тегов,
     *                  'link'-ссылка,
     *                  'option' - option,
     *                  'href_replace' - href для ссылок сортировки при выводе списка элемента
     * @param array $pd_fields
     */
    public static function getPluginFieldsTagsSort($ident, &$user_flds_tags, &$user_flds_vals, $tags_type = 'link', $pd_fields = array())
    {
        $plugin_ident = 'pl_plugin_'.$ident;
        switch ($ident)
        {
            case 'faq':
                $plugin_ident = 'pl_faq';
                break;

            case 'im':
                $plugin_ident = 'pl_imagelib';
                break;

            case 'news':
                $plugin_ident = 'pl_news';
                break;

            case 'sr':
                $plugin_ident = 'pl_services_rutube';
                break;

            case 'su':
                $plugin_ident = 'pl_site_users';
                break;

            case 'spr':
                $plugin_ident = 'pl_sprav';
                break;
        }

        $user_flds_sort = array();
        if (count($pd_fields) > 0)
        {
            $user_flds_sort = $pd_fields;
        }
        else
        {
            $res = sbQueryCache::query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?', $plugin_ident);
            if($res && $res[0][0] != '')
            {
                $user_flds_sort = unserialize($res[0][0]);
            }
        }

        if(count($user_flds_sort) > 0)
        {
            if($tags_type == 'href_replace')
            {
                $query_str = preg_replace('/[?&]?s_f_'.$ident.'['.urlencode('[]').']*?=[A-z0-9%]+/i', '', $_SERVER['QUERY_STRING']);
            }

            foreach($user_flds_sort as $value)
            {
                if($value['sql'] == 1)
                {
                    switch ($value['type'])
                    {
                        case 'string':
                        case 'text':
                        case 'longtext':
                        case 'number':
                        case 'date':
                        case 'checkbox':
                        case 'image':
                        case 'link':
                        case 'file':
                        case 'color':
                            if($tags_type == 'link')
                            {
                                $user_flds_tags[] = '<a href=\'{SORT_USER_F_'.$value['id'].'_ASC}\'>'.sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_ASC,$value['title']).'</a>';
                                $user_flds_tags[] = '<a href=\'{SORT_USER_F_'.$value['id'].'_DESC}\'>'.sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_DESC,$value['title']).'</a>';
                                $user_flds_vals[] = sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_ASC, $value['title']);
                                $user_flds_vals[] = sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_DESC, $value['title']);

                            }
                            elseif($tags_type == 'option')
                            {
                                $user_flds_tags[] = '<option value=\'user_f_'.$value['id'].'=ASC\' {OPT_SELECTED_USER_F_'.$value['id'].'_ASC}>'.sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_ASC,$value['title']).'</option>';
                                $user_flds_tags[] = '<option value=\'user_f_'.$value['id'].'=DESC\' {OPT_SELECTED_USER_F_'.$value['id'].'_DESC}>'.sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_DESC,$value['title']).'</option>';
                                $user_flds_vals[] = sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_ASC, $value['title']);
                                $user_flds_vals[] = sprintf(KERNEL_FORM_EDIT_SORT_USER_FIELDS_DESC, $value['title']);
                            }
                            elseif($tags_type == 'href_replace')
                            {
                                $user_flds_tags[] = '{SORT_USER_F_'.$value['id'].'_ASC}';
                                $user_flds_tags[] = '{SORT_USER_F_'.$value['id'].'_DESC}';
                                $user_flds_vals[] = (isset($GLOBALS['PHP_SELF']) ? $GLOBALS['PHP_SELF'] : '').(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_'.$ident.'='.urlencode('user_f_'.$value['id'].'=ASC');
                                $user_flds_vals[] = (isset($GLOBALS['PHP_SELF']) ? $GLOBALS['PHP_SELF'] : '').(!empty($query_str) ? '?'.$query_str.'&':'?').'s_f_'.$ident.'='.urlencode('user_f_'.$value['id'].'=DESC');
                            }
                        break;
                    }
                }
            }
        }
    }

     /**
     * Заменяет теги указывающие выделен ли option, на значение 'selected' в поле сортировки формы фильтра
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param string $fields_templ Поле сортировки в макете фильтра.
     * @param string $form_templ Макет фильтра.
     *
     * @return string Распарсенный макет дизайна.
     */
    public function replacePluginFieldsTagsFilterSelect($ident, $fields_templ = '', $form_templ = '')
    {
        if (trim($fields_templ) == '' || trim($form_templ) == '' || sb_strpos($form_templ, '{SORT_SELECT}') === false)
            return '';

        $sort = array();

        //Беру значение
        //post, get
        if(isset($_POST['s_f_'.$ident]) || isset($_GET['s_f_'.$ident]))
        {
            $_REQUEST['s_f_'.$ident] = isset($_POST['s_f_'.$ident]) ? $_POST['s_f_'.$ident] : $_GET['s_f_'.$ident];

            if(is_array($_REQUEST['s_f_'.$ident]))
                $sort = $_REQUEST['s_f_'.$ident];
            else
                $sort[] = $_REQUEST['s_f_'.$ident];
        }
        elseif(isset($_REQUEST['s_f_'.$ident]))             //cookie
        {
                $sort = explode(';', $_REQUEST['s_f_'.$ident]);
        }

        //Заменяю
        foreach($sort as $value)
        {
            $value = explode('=', $value);
            if (count($value) != 2)
                continue;

            $fld_name = preg_replace('/[^a-zA-Z0-9_\-]+/', '', trim($value[0]));
            $fld_asc = sb_strtoupper(trim($value[1]));

            if ($fld_asc != 'ASC' && $fld_asc != 'DESC')
                $fld_asc = 'ASC';

            $fields_templ = sb_str_replace('{OPT_SELECTED_'.sb_strtoupper($fld_name).'_'.$fld_asc.'}', 'selected', $fields_templ);
        }

        return $fields_templ;
    }

     /**
     * Возвращает sql код для сортировки элементов по полям
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param string $table_alias Алиас таблицы в SQL-pfghjct.
     *
     * @return string SQL для сортировки.
     */
    public function getPluginFieldsSortSql($ident, $table_alias)
    {
        $sort = array();
        $sql = '';
        $is_cookie = false;

        //Беру значение
        //post
        if(isset($_POST['s_f_'.$ident]) || isset($_GET['s_f_'.$ident]))
        {
            $_REQUEST['s_f_'.$ident] = isset($_POST['s_f_'.$ident]) ? $_POST['s_f_'.$ident] : $_GET['s_f_'.$ident];

            if(is_array($_REQUEST['s_f_'.$ident]))
                $sort = $_REQUEST['s_f_'.$ident];
            else
                $sort[] = $_REQUEST['s_f_'.$ident];
        }
        //cookie
        elseif(isset($_REQUEST['s_f_'.$ident]))
        {
            $is_cookie = true;
            $sort = explode('|', $_REQUEST['s_f_'.$ident]);
        }

        $cookie_sort = array();

        //Сортирую
        foreach($sort as $value)
        {
            $value = explode('=', $value);
            if (count($value) != 2)
                continue;

            $fld_name = str_replace('s_f_', '', preg_replace('/[^a-zA-Z0-9_\-]+/', '', trim($value[0])));
            $fld_asc = sb_strtoupper(trim($value[1]));

            if ($fld_asc != 'ASC' && $fld_asc != 'DESC')
                $fld_asc = 'ASC';

            $cookie_sort[] = $fld_name.'='.$fld_asc;

            $sql .= ', '.$table_alias.'.'.$fld_name.' '.$fld_asc;
        }

        //Устанавливаю куки
        if(count($cookie_sort) > 0 && !$is_cookie)
        {
            sb_setcookie('s_f_'.$ident, implode('|', $cookie_sort));
        }

        return $sql;
    }

    /**
     * Функция для парсинга пользовательских полей
     *
     * @param array $fields Массив пользовательских полей.
     * @param array $values Массив значений пользовательских полей.
     * @param array $temps Массив макетов дизайна пользовательских полей.
     * @param array $dop_tags Массив доп. тегов, использующихся в макете дизайна элемента.
     * @param array $dop_values Массив значений доп. тегов, использующихся в макете дизайна элемента.
     * @param string $lang Язык (используется для парсинга полей типа "Дата".
     * @param string $prefix Префикс имени поля в макете дизайна полей.
     * @param string $sufix Суффикс имени поля в макете дизайна полей.
     * @param int $allow_bb Разрешать или нет bbcode в полях. 0 - нет, 1 - да.
     * @param int $link_level Уровень подгрузки связанных элементов.
     * @param string $element_temp Макет вывода элемента.
     * @param array  $func_params Различные параметры ф-ции.
     *
     * @return array Массив распарсенных значений пользовательских полей.
     */
    static function parsePluginFields($fields, $values, &$temps, $dop_tags=array(), $dop_values=array(), $lang='ru', $prefix='', $sufix='', $allow_bb = 0, $link_level = 0, $element_temp = '', $func_params = array())
    {
        static $select_sprav_cache = array();

        $result = array();
        $i = 0;
        $link_level++;

        foreach ($fields as $value)
        {
            $f_name = $prefix.'user_f_'.$value['id'].$sufix;
            if (!isset($value['sql']) || $value['sql'] != 1)
                continue;

            if ($value['type'] != 'google_coords' && $value['type'] != 'yandex_coords')
            {
                if (($value['type'] != 'checkbox' && (!isset($values[$i]) || is_null($values[$i]) || $values[$i] == '')) || !isset($temps[$f_name]) || trim($temps[$f_name]) == '')
                {
                    $i++;
                    $result[] = '';
                    continue;
                }

                if (trim($element_temp) != '' && sb_strpos($element_temp, '{' . $value['tag'] . '}') === false)
                {
                    $found = false;
                    foreach ($temps as $fld_temp)
                    {
                        if (sb_strpos($fld_temp, '{' . $value['tag'] . '}') !== false)
                        {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found)
                    {
                        $i++;
                        $result[] = '';
                        $temps[$f_name] = ''; // сбрасываем шаблон, чтобы для следующего элемента в списке не проваливаться в цикл
                        continue;
                    }
                }
            }

            switch($value['type'])
            {
                case 'string':
                case 'text':
                case 'longtext':
                case 'number':
                case 'link':
                case 'categs':
                case 'color':
                case 'password':
                case 'table':
                case 'php':
                    if($allow_bb == '1' && ($value['type'] == 'text' || $value['type'] == 'longtext'))
                    {
                        //Если разрешен bb код
                        $result[] = sbProgParseBBCodes(str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($values[$i]), $dop_values), $temps[$f_name]));
                    }
                    else
                    {
                        $result[] = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($values[$i]), $dop_values), $temps[$f_name]);
                    }
                    break;

                case 'google_coords':
                case 'yandex_coords':
                    if ($value['type'] == 'yandex_coords')
                    {
                        $api_key = sbPlugins::getSetting('sb_yandex_maps_id');
                    }

                    if (is_null($values[$i]) || $values[$i] == '')
                    {
                        $result[] = '';
                        $result[] = '';
                        if ($value['type'] == 'yandex_coords')
                        {
                            $result[] = $api_key;
                        }
                        break;
                    }

                    $val = explode('|', $values[$i]);

                    if (!isset($temps[$f_name.'_latitude']) || trim($temps[$f_name.'_latitude']) == '')
                    {
                        $result[] = '';
                    }
                    else
                    {
                        if (isset($val[0]) && $val[0] != '')
                            $result[] = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($val[0]), $dop_values), $temps[$f_name.'_latitude']);
                        else
                            $result[] = '';
                    }

                    if (!isset($temps[$f_name.'_longtitude']) || trim($temps[$f_name.'_longtitude']) == '')
                    {
                        $result[] = '';
                    }
                    else
                    {
                        if (isset($val[1]) && $val[1] != '')
                            $result[] = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($val[1]), $dop_values), $temps[$f_name.'_longtitude']);
                        else
                            $result[] = '';
                    }

                    if ($value['type'] == 'yandex_coords')
                    {
                        $result[] = $api_key;
                    }
                    break;

                case 'image':
                    $size = '';
                    $width = '';
                    $height = '';

                    if (sb_strpos($temps[$f_name], '{SIZE}') > 0 ||
                        sb_strpos($temps[$f_name], '{WIDTH}') > 0 ||
                        sb_strpos($temps[$f_name], '{HEIGHT}') > 0 )
                    {
                        $img_src = $values[$i];
                        if (stristr($img_src, 'http://') !== false || stristr($img_src, 'https://') !== false)
                        {
                            $img_src = substr($img_src, 8);
                            $img_src = substr($img_src, strpos($img_src, '/'));
                        }

                        if ($GLOBALS['sbVfs']->exists($img_src))
                        {
                            $size = $GLOBALS['sbVfs']->filesize($img_src);
                            $img = $GLOBALS['sbVfs']->getimagesize($img_src);
                            if ($img)
                            {
                                $width = $img[0];
                                $height = $img[1];
                            }
                        }
                    }

                    $result[] = str_replace(array_merge(array('{VALUE}', '{SIZE}', '{WIDTH}', '{HEIGHT}'), $dop_tags),
                                               array_merge(array($values[$i], $size, $width, $height), $dop_values),
                                               $temps[$f_name]);
                    break;

                case 'file':
                    $size = '';

                    if (sb_strpos($temps[$f_name], '{SIZE}') > 0)
                    {
                        $img_src = $values[$i];
                        if (strtolower(substr($img_src, 0, 7)) == 'http://')
                        {
                            $img_src = substr($img_src, 8);
                            $img_src = substr($img_src, strpos($img_src, '/'));
                        }
                        elseif (strtolower(substr($img_src, 0, 8)) == 'https://')
                        {
                            $img_src = substr($img_src, 9);
                            $img_src = substr($img_src, strpos($img_src, '/'));
                        }

                        if ($GLOBALS['sbVfs']->exists($img_src))
                        {
                            $size = $GLOBALS['sbVfs']->filesize($img_src);
                        }
                    }
                    $result[] = str_replace(array_merge(array('{VALUE}', '{SIZE}'), $dop_tags),
                                               array_merge(array($values[$i], $size), $dop_values),
                                               $temps[$f_name]);
                    break;

                case 'date':
                    $temp = sb_parse_date($values[$i], $temps[$f_name], $lang);
                    $result[] = str_replace($dop_tags, $dop_values, $temp);
                    break;

                case 'checkbox':
                    if (!isset($values[$i]) || is_null($values[$i]) || $values[$i] == 0)
                    {
                        $val = $value['settings']['not_checked_text'];
                    }
                    else
                    {
                        $val = $value['settings']['checked_text'];
                    }
                    $result[] = str_replace(array_merge(array('{VALUE}', '{VALUE_INT}'), $dop_tags), array_merge(array($val), $dop_values), $temps[$f_name]);
                    break;

                case 'select_sprav':
                case 'radio_sprav':

                    if (!isset($select_sprav_cache[$values[$i]]))
                    {
                        $res = sql_query('SELECT c.cat_id, c.cat_title, s.s_id, s.s_title, s.s_prop1, s.s_prop2, s.s_prop3 FROM sb_sprav s, sb_categs c, sb_catlinks l
                                            WHERE s.s_id=?d AND l.link_el_id=s.s_id AND c.cat_id=l.link_cat_id AND c.cat_ident="pl_sprav" AND s.s_active=1', $values[$i]);

                        $select_sprav_cache[$values[$i]] = $res;
                    }
                    else
                    {
                        $res = $select_sprav_cache[$values[$i]];
                    }

                    if ($res)
                    {
                        list($cat_id, $cat_title, $s_id, $s_title, $s_prop1, $s_prop2, $s_prop3) = $res[0];
                    }
                    else
                    {
                        $result[] = '';
                        break;
                    }

                    $result[] = str_replace(array_merge(array('{SPRAV_CAT_TITLE}', '{SPRAV_CAT_ID}', '{SPRAV_TITLE}', '{SPRAV_ID}', '{SPRAV_DESC1}', '{SPRAV_DESC2}', '{SPRAV_DESC3}'), $dop_tags),
                                               array_merge(array($cat_title, $cat_id, $s_title, $s_id, $s_prop1, $s_prop2, $s_prop3), $dop_values),
                                               $temps[$f_name]);
                    break;

                case 'multiselect_sprav':
                case 'checkbox_sprav':
                    @require_once(SB_CMS_PL_PATH.'/pl_sprav/prog/pl_sprav.php');

                    $sprav_ids = null;
                    // Если вывод идет в корзине, то нужно выводить не все элементы,
                    // только выбранные пользователем при заказе товара
                    if(isset($func_params['from_cart']))
                    {
                        $from_cart_fields_one = explode('||', $func_params['from_cart']);
                        foreach($from_cart_fields_one as $v)
                        {
                            $from_cart_fields_two = explode('::', $v);
                            $from_cart_fields[$from_cart_fields_two[0]] = $from_cart_fields_two[1];
                        }

                        if(isset($from_cart_fields[$value['id']]) && $from_cart_fields[$value['id']] != '')
                        {
                            $sprav_ids = $from_cart_fields[$value['id']];
                        }
                    }

                    if (is_null($sprav_ids))
                    {
                        if(is_array($values[$i]))
                            $sprav_ids = implode(',', $values[$i]);
                        else
                            $sprav_ids = $values[$i];
                    }
					
                    //выполняем парсинг только если указан макет дизайна
                    if($temps[$f_name] != -1)
                    {
                        $result[] = fSprav_Elem_Parse_List($temps[$f_name], $sprav_ids);
                    }
                    else
                    {
                        $result[] = $sprav_ids;
                    }
                    
                    break;

                case 'link_sprav':
                    $ids = explode(',', $values[$i]);
                    if ($ids[0] == -1 || $ids[1] == -1)
                    {
                        $result[] = '';
                        break;
                    }

                    $res = sql_query('SELECT c.cat_id, c.cat_title, s.s_id, s.s_title, s.s_prop1, s.s_prop2, s.s_prop3 FROM sb_sprav s, sb_categs c
                                            WHERE s.s_id=?d AND c.cat_id=?d AND s.s_active=1', $ids[1], $ids[0]);
                    if ($res)
                    {
                        list($cat_id, $cat_title, $s_id, $s_title, $s_prop1, $s_prop2, $s_prop3) = $res[0];
                    }
                    else
                    {
                        $result[] = '';
                        break;
                    }

                    $result[] = str_replace(array_merge(array('{SPRAV_CAT_TITLE}', '{SPRAV_CAT_ID}', '{SPRAV_TITLE}', '{SPRAV_ID}', '{SPRAV_DESC1}', '{SPRAV_DESC2}', '{SPRAV_DESC3}'), $dop_tags),
                                               array_merge(array($cat_title, $cat_id, $s_title, $s_id, $s_prop1, $s_prop2, $s_prop3), $dop_values),
                                               $temps[$f_name]);
                    break;

                case 'select_plugin':
                case 'radio_plugin':
                case 'link_plugin':
                case 'elems_plugin':
                    $link_val = preg_replace ('/[^\-0-9,]/','',$values[$i]);
                    if($value['type'] == 'link_plugin')
                    {
                        $link_val = explode(',', $link_val);
                        $link_val = intval($link_val[1]);
                    }

                    if($link_level > 11 || $link_val < 1)
                    {
                        $result[] = '';
                        break;
                    }

                    //выполняем парсинг только если указан макет дизайна
                    if($temps[$f_name] != -1)
                    {
                        $params = '';
                        sbPlugins::getElemList($value['settings']['ident'], $values[$i], $temps[$f_name], $params, $result, true, $link_val, $link_level);
                    }
                    else
                    {
                    	$result[] = $values[$i];
                    }
                    
                    break;

                case 'multiselect_plugin':
                case 'checkbox_plugin':
                    $values[$i] = preg_replace ('/[^0-9,]/','',$values[$i]);
                    $link_val = $values[$i];

                    $params = array();

                    // Идентификаторы элементов или разделов
                    $params['ids'] = '';

                    // Если значение $params['ids_from'] = 'id' - то подразумевается, что $params['ids']
                    // содержит идентификаторы элементов,  в других случаях - подразумеваются разделы
                    // Актуально только для fPlugin_Maker_Elem_List
                    $params['ids_from'] = '';

                    // Достаю значения связанных полей
                    if(isset($func_params['from_list_order']) && $func_params['from_list_order'] == 1)
                    {
                        // Если вывод идет в редактирование заказа в админке, на вкладке "Заказанные товары"
                        $params['ids_from'] = 'id';
                        $params['ids'] = $link_val;
                    }
                    elseif(isset($func_params['from_cart']) && $func_params['from_cart'] != '')
                    {
                        // Если вывод идет в корзине, то нужно выводить не все элементы,
                        // только выбранные пользователем при заказе товара
                        $params['ids_from'] = 'id';
                        $from_cart_fields_one = explode('||', $func_params['from_cart']);
                        foreach($from_cart_fields_one as $v)
                        {
                            $from_cart_fields_two = explode('::', $v);
                            $from_cart_fields[$from_cart_fields_two[0]] = $from_cart_fields_two[1];
                        }
                        if(isset($from_cart_fields[$value['id']]) && $from_cart_fields[$value['id']] != '')
                        {
                            if(sb_stripos($from_cart_fields[$value['id']], ',') !== false)
                            {
                                // Массив значений поля
                                $from_cart_fields_ids = explode(',', $from_cart_fields[$value['id']]);
                                foreach($from_cart_fields_ids as $v)
                                {
                                    $params['ids'] .= trim($v[0]).'^';
                                }
                                $params['ids'] = sb_substr($params['ids'],0,-1);
                            }
                            else
                            {
                                // если одно значение
                                $params['ids'] = trim($from_cart_fields[$value['id']]);
                            }
                        }
                    }
                    else
                    {
                        //  Если обычный вывод, достаю все элементы раздела
                        $link_val = explode(',', $link_val);
                        if($link_level > 11 || count($link_val) == 0)
                        {
                            $result[] = '';
                            break;
                        }

                        $res1 = sql_query('SELECT DISTINCT c.cat_id FROM sb_categs c, sb_catlinks l WHERE
                                   l.link_el_id IN (?a) AND c.cat_ident = ? AND l.link_src_cat_id = 0 AND c.cat_id = l.link_cat_id', $link_val, $value['settings']['ident']);

                        if(!$res1)
                        {
                            $result[] = '';
                            break;
                        }
                        foreach($res1 as $value1)
                        {
                            $params['ids'] .= $value1[0].'^';
                        }
                        $params['ids'] = sb_substr($params['ids'],0,-1);

                    }
                    $params['ids'] = preg_replace ('/[^0-9,^]/','',$params['ids']);
                    $params['order1'] = 'ASC';

                    //выполняем парсинг только если указан макет дизайна
                    if($temps[$f_name] != -1)
                    {
                        sbPlugins::getElemList($value['settings']['ident'], $values[$i], $temps[$f_name], $params, $result, false, $values[$i], $link_level);
                    }
                    else
                    {
                        $result[] = $values[$i];
                    }
                    
                    break;
                default:
                    $result[] = '';
                    break;
            }
            $i++;
        }
        return $result;
    }

    /**
     * Функция для парсинга пользовательских полей ввода
     *
     * @param string $ident Уникальный идентификатор модуля.
     * @param array $temps Массив макетов дизайна пользовательских полей.
     * @param array $date_temp Формат ввода дат.
     * @param array $tags Массив тегов, в который будут добавлены теги пользовательских полей для последущей замены.
     * @param array $values Массив значений, в который будут добавлены макеты пользовательских полей для замены.
     * @param int $id Уникальный идентификатор элемента, если элемент редактируется, или раздела, если раздел редактируется.
     * @param string $table_name Наименование таблицы БД, где хранятся элементы. Для разделов '';
     * @param string $id_name Наименование поля таблицы, где хранится уникальный идентификатор элемента. Для разделов '';
     * @param array $dop_tags Массив доп. тегов, использующихся в макетах дизайна полей.
     * @param array $dop_values Массив значений доп. тегов, использующихся в макетах дизайна полей.
     * @param bool $categs Выводить поля разделов (TRUE) или элементов (FALSE).
     * @param string $field_prefix Префикс имени поля (name), заменяет user_f.
     * @param string $field_sufix Суфикс имени поля (name).
     * @param bool $filter Добавлять поля lo и hi (TRUE) или нет (FALSE).
     *
     * @return array Массив распарсенных значений пользовательских полей.
     */
    static function parsePluginInputFields($ident, &$temps, $date_temp, &$tags, &$values, $id = -1, $table_name = '', $id_name = '', $dop_tags=array(), $dop_values=array(), $categs = false, $field_prefix = '', $field_sufix = '', $filter = false)
    {
        $res = sbQueryCache::query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
        if (!$res || $res[0][0] == '')
        {
            return false;
        }

        $fields = unserialize($res[0][0]);
        if (!$fields || count($fields) <= 0)
        {
            return false;
        }

        $field_vals = array();
        $field_keys = array();
        $sql = '';

        if ($filter)
        {
            $num_post = count($_REQUEST);
            $post_ar = &$_REQUEST;
        }
        else
        {
            $num_post = count($_POST);
            $post_ar = &$_POST;
        }

        $google_coords_fields_ids = array();
        foreach ($fields as $key => $value)
        {
            if ($field_prefix == '')
                $f_name_site = 'user_f_' . $value['id'] . ($field_sufix != '' ? '_' . $field_sufix
                            : '');
            else
                $f_name_site = $field_prefix . '_' . $value['id'] . ($field_sufix != ''
                            ? '_' . $field_sufix : '');

            if (isset($value['sql']) && $value['sql'] == 1 && isset($temps[$f_name_site]))
            {
                $field_keys[] = $value['id'];
                $sql .= 'user_f_'.$value['id'].',';

                if ($num_post > 0)
                {
                    switch ($value['type'])
                    {
                        case 'file':
                        case 'image':
                            break;

                        case 'string':
                        case 'text':
                        case 'longtext':
                        case 'link':
                        case 'color':
                        case 'password':
                            if (isset($post_ar[$f_name_site]))
                                $field_vals[$f_name_site] = $post_ar[$f_name_site];
                            break;

                        case 'number':
                            if (isset($post_ar[$f_name_site]) && trim($post_ar[$f_name_site]) != '')
                                $field_vals[$f_name_site] = floatval($post_ar[$f_name_site]);
                            if (isset($post_ar[$f_name_site.'_lo']) && trim($post_ar[$f_name_site.'_lo']) != '')
                                $field_vals[$f_name_site.'_lo'] = floatval($post_ar[$f_name_site.'_lo']);
                            if (isset($post_ar[$f_name_site.'_hi']) && trim($post_ar[$f_name_site.'_hi']) != '')
                                $field_vals[$f_name_site.'_hi'] = floatval($post_ar[$f_name_site.'_hi']);
                            break;

                        case 'date':
                            if (isset($post_ar[$f_name_site]) && trim($post_ar[$f_name_site]) != '')
                                $field_vals[$f_name_site] = $post_ar[$f_name_site] != '' ? sb_datetoint($post_ar[$f_name_site], $date_temp) : '';
                            if (isset($post_ar[$f_name_site.'_lo']) && trim($post_ar[$f_name_site.'_lo']) != '')
                                $field_vals[$f_name_site.'_lo'] = $post_ar[$f_name_site.'_lo'] != '' ? sb_datetoint($post_ar[$f_name_site.'_lo'], $date_temp) : '';
                            if (isset($post_ar[$f_name_site.'_hi']) && trim($post_ar[$f_name_site.'_hi']) != '')
                                $field_vals[$f_name_site.'_hi'] = $post_ar[$f_name_site.'_hi'] != '' ? sb_datetoint($post_ar[$f_name_site.'_hi'], $date_temp) : '';
                            break;

                        case 'checkbox':
                            $field_vals[$f_name_site] = (isset($post_ar[$f_name_site]) && $post_ar[$f_name_site] > 0 ? '1' : '0');
                            break;

                        case 'categs':
                        case 'select_sprav':
                        case 'radio_sprav':
                        case 'select_plugin':
                        case 'radio_plugin':
                        case 'elems_plugin':
                            if (isset($post_ar[$f_name_site]))
                                $field_vals[$f_name_site] = intval($post_ar[$f_name_site]);
                            break;

                        case 'multiselect_sprav':
                        case 'checkbox_sprav':
                        case 'checkbox_plugin':
                        case 'multiselect_plugin':
                            if (isset($post_ar[$f_name_site]))
                            {
                                if (is_array($post_ar[$f_name_site]))
                                {
                                    $field_vals[$f_name_site] = implode(',', $post_ar[$f_name_site]);
                                }
                                else
                                {
                                    $field_vals[$f_name_site] = intval($post_ar[$f_name_site]);
                                }
                            }
                            break;

                        case 'link_sprav':
                        case 'link_plugin':
                            if (isset($post_ar[$f_name_site]) && isset($post_ar[$f_name_site.'_link']))
                                $field_vals[$f_name_site] = intval($post_ar[$f_name_site]).','.intval($post_ar[$f_name_site.'_link']);
                            else
                                $field_vals[$f_name_site] = '';
                            break;

                        case 'google_coords':
                        case 'yandex_coords':
                            $field_vals[$f_name_site] = '';

                            if (isset($post_ar[$f_name_site.'_latitude']) && trim($post_ar[$f_name_site.'_latitude']) != '')
                            {
                                $field_vals[$f_name_site.'_latitude'] = floatval($post_ar[$f_name_site.'_latitude']);
                                $field_vals[$f_name_site] = floatval($post_ar[$f_name_site.'_latitude']).'|';
                            }
                            else
                            {
                                $field_vals[$f_name_site] = '|';
                            }

                            if (isset($post_ar[$f_name_site.'_longtitude']) && trim($post_ar[$f_name_site.'_longtitude']) != '')
                            {
                                $field_vals[$f_name_site.'_longtitude'] = floatval($post_ar[$f_name_site.'_longtitude']);
                                $field_vals[$f_name_site] .= floatval($post_ar[$f_name_site.'_longtitude']);
                            }
                            break;

                        default:
                            if (isset($post_ar[$f_name_site]))
                            {
                                $field_vals[$f_name_site] = $post_ar[$f_name_site];
                            }
                            break;
                    }

                    if (!isset($field_vals[$f_name_site]))
                    {
                        $field_vals[$f_name_site] = '';

                        if (!$filter && isset($value['settings']))
                        {
                            if (isset($value['settings']['default']))
                            {
                                $field_vals[$f_name_site] = $value['settings']['default'];
                            }
                            elseif (isset($value['settings']['default_latitude']) && isset($value['settings']['default_longtitude']))
                            {
                                $field_vals[$f_name_site.'_latitude'] = $value['settings']['default_latitude'];
                                $field_vals[$f_name_site.'_longtitude'] = $value['settings']['default_longtitude'];
                            }
                        }
                    }
                }
                else
                {
                    $field_vals[$f_name_site] = '';
                }

                if ($value['type'] == 'google_coords' || $value['type'] == 'yandex_coords')
                {
                    $google_coords_fields_ids[] = $value['id'];
                }
            }
        }

        if ($num_post == 0 && $id != -1 && $id != '' && !$filter)
        {
            if (!$categs && $sql != '')
            {
                $sql = substr($sql, 0, -1);
                // получаем значения элементов
                $res = sql_query('SELECT '.$sql.' FROM ?# WHERE ?#=?d', $table_name, $id_name, $id);
                if ($res)
                {
                    for ($i = 0; $i < count($res[0]); $i++)
                    {
                        if (in_array($field_keys[$i], $google_coords_fields_ids))
                        {
                            if (!is_null($res[0][$i]))
                            {
                                $val = explode('|', $res[0][$i]);

                                if (isset($val[0]) && trim($val[0]) != '')
                                {
                                    $field_vals['user_f_'.$field_keys[$i].'_latitude'] = $val[0];
                                }
                                else
                                {
                                    $field_vals['user_f_'.$field_keys[$i].'_latitude'] = '';
                                }

                                if (isset($val[1]) && trim($val[1]) != '')
                                {
                                    $field_vals['user_f_'.$field_keys[$i].'_longtitude'] = $val[1];
                                }
                                else
                                {
                                    $field_vals['user_f_'.$field_keys[$i].'_longtitude'] = '';
                                }
                            }
                            else
                            {
                                $field_vals['user_f_'.$field_keys[$i].'_latitude'] = '';
                                $field_vals['user_f_'.$field_keys[$i].'_longtitude'] = '';
                            }
                        }
                        else
                        {
                            if (!is_null($res[0][$i]))
                                $field_vals['user_f_'.$field_keys[$i]] = $res[0][$i];
                            else
                                $field_vals['user_f_'.$field_keys[$i]] = '';
                        }
                    }
                }
            }
            elseif ($categs)
            {
                // получаем значения разделов
                $res = sql_query('SELECT cat_fields FROM sb_categs WHERE cat_id=?d', $id);
                if ($res)
                {
                    list($cat_fields) = $res[0];
                    if ($cat_fields != '')
                    {
                        $field_vals = unserialize($cat_fields);

                        if (count($google_coords_fields_ids) > 0)
                        {
                            foreach ($google_coords_fields_ids as $field_id)
                            {
                                if (isset($field_vals['user_f_'.$field_id]) && $field_vals['user_f_'.$field_id] != '')
                                {
                                    $val = explode('|', $field_vals['user_f_'.$field_id]);

                                    if (isset($val[0]) && trim($val[0]) != '')
                                    {
                                        $field_vals['user_f_'.$field_id.'_latitude'] = $val[0];
                                    }
                                    else
                                    {
                                        $field_vals['user_f_'.$field_id.'_latitude'] = '';
                                    }

                                    if (isset($val[1]) && trim($val[1]) != '')
                                    {
                                        $field_vals['user_f_'.$field_id.'_longtitude'] = $val[1];
                                    }
                                    else
                                    {
                                        $field_vals['user_f_'.$field_id.'_longtitude'] = '';
                                    }
                                }
                                else
                                {
                                    $field_vals['user_f_'.$field_id.'_latitude'] = '';
                                    $field_vals['user_f_'.$field_id.'_longtitude'] = '';
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($fields as $key => $value)
        {
            if (!isset($value['sql']) || $value['sql'] != 1)
                continue;

            $f_type = $value['type'];

            if ($f_type != 'google_coords' && $f_type != 'yandex_coords')
            {
                if ($field_prefix == '')
                    $f_name_site = 'user_f_'.$value['id'].($field_sufix != '' ? '_'.$field_sufix : '');
                else
                    $f_name_site = $field_prefix.'_'.$value['id'].($field_sufix != '' ? '_'.$field_sufix : '');

                if (!isset($temps[$f_name_site]) || trim($temps[$f_name_site]) == '')
                    continue;

                $f_value = isset($field_vals[$f_name_site]) ? $field_vals[$f_name_site] : '';

                $f_value_lo = '';
                $f_value_hi = '';

                $f_temp = $temps[$f_name_site];
                $f_temp_lo = '';
                $f_temp_hi = '';

                $f_temp_pic_now = '';
                $f_temp_file_now = '';
            }
            else
            {
                if ($field_prefix == '')
                {
                    $f_name_site_latitude = 'user_f_'.$value['id'].'_latitude'.($field_sufix != '' ? '_'.$field_sufix : '');
                    $f_name_site_longtitude = 'user_f_'.$value['id'].'_longtitude'.($field_sufix != '' ? '_'.$field_sufix : '');
                }
                else
                {
                    $f_name_site_latitude = $field_prefix.'_'.$value['id'].'_latitude'.($field_sufix != '' ? '_'.$field_sufix : '');
                    $f_name_site_longtitude = $field_prefix.'_'.$value['id'].'_longtitude'.($field_sufix != '' ? '_'.$field_sufix : '');
                }

                $f_value_latitude = isset($field_vals[$f_name_site_latitude]) ? $field_vals[$f_name_site_latitude] : '';
                $f_value_longtitude = isset($field_vals[$f_name_site_longtitude]) ? $field_vals[$f_name_site_longtitude] : '';

                $f_temp_latitude = isset($temps[$f_name_site_latitude]) ? $temps[$f_name_site_latitude] : '';
                $f_temp_longtitude = isset($temps[$f_name_site_longtitude]) ? $temps[$f_name_site_longtitude] : '';
            }

            if ($f_type != 'google_coords' && $f_type != 'yandex_coords')
            {
                $tags[] = '{'.$value['tag'].'}';
                if ($filter && ($f_type == 'number' || $f_type == 'date'))
                {
                    $tags[] = '{'.$value['tag'].'_LO}';
                    $tags[] = '{'.$value['tag'].'_HI}';

                    $f_value_lo = isset($field_vals[$f_name_site.'_lo']) ? $field_vals[$f_name_site.'_lo'] : '';
                    $f_value_hi = isset($field_vals[$f_name_site.'_hi']) ? $field_vals[$f_name_site.'_hi'] : '';

                    $f_temp_lo = isset($temps[$f_name_site.'_lo']) ? $temps[$f_name_site.'_lo'] : '';
                    $f_temp_hi = isset($temps[$f_name_site.'_hi']) ? $temps[$f_name_site.'_hi'] : '';
                }
                if($f_type == 'image' || $f_type == 'file')
                {
                    $tags[] = '{'.$value['tag'].'_NOW}';
                }
            }
            else
            {
                $tags[] = '{'.$value['tag'].'_LATITUDE}';
                $tags[] = '{'.$value['tag'].'_LONGTITUDE}';
            }

            switch ($f_type)
            {
                case 'image':
                    if(isset($temps[$f_name_site.'_pic_now']))
                    {
                        $f_temp_pic_now = str_replace('{PIC_SRC}', $f_value, $temps[$f_name_site.'_pic_now']);
                    }
                    break;
                case 'file':
                    if(isset($temps[$f_name_site.'_file_now']))
                    {
                        $f_temp_file_now = str_replace(array('{FILE_NAME}', '{FILE_URL}'), array(basename($f_value),$f_value), $temps[$f_name_site.'_file_now']);
                    }
                    break;

                case 'number':
                    if(!$filter && $id == -1 && isset($value['settings']['default']))
                    {
                        $f_value = isset($field_vals[$f_name_site]) ? $field_vals[$f_name_site] : $value['settings']['default'];
                    }
                    $f_temp = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value), $dop_values), $f_temp);
                    $f_temp_lo = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value_lo), $dop_values), $f_temp_lo);
                    $f_temp_hi = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value_hi), $dop_values), $f_temp_hi);
                    break;

                case 'string':
                case 'text':
                case 'longtext':
                case 'link':
                case 'password':
                case 'color':
                    if(!$filter && $id == -1 && isset($value['settings']['default']))
                    {
                        $f_value = isset($field_vals[$f_name_site]) ? $field_vals[$f_name_site] : $value['settings']['default'];
                    }
                    $f_temp = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value), $dop_values), $f_temp);
                    break;

                case 'date':
                    if ($f_value == 'current')
                    {
                        $f_value = time();
                    }
                    elseif(!$filter && $id == -1 && isset($value['settings']['default']))
                    {
                        $f_value = isset($field_vals[$f_name_site]) ? $field_vals[$f_name_site] : ($value['settings']['default'] == 'current'? time() : $value['settings']['default']);
                    }

                    if ($f_value != '')
                    {
                        $f_value = sb_parse_date($f_value, $date_temp);
                    }

                    if ($f_value_lo == 'current')
                    {
                        $f_value_lo = time();
                    }

                    if ($f_value_lo != '')
                    {
                        $f_value_lo = sb_parse_date($f_value_lo, $date_temp);
                    }

                    if ($f_value_hi == 'current')
                    {
                        $f_value_hi = time();
                    }

                    if ($f_value_hi != '')
                    {
                        $f_value_hi = sb_parse_date($f_value_hi, $date_temp);
                    }

                    $f_temp = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value), $dop_values), $f_temp);
                    $f_temp_lo = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value_lo), $dop_values), $f_temp_lo);
                    $f_temp_hi = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value_hi), $dop_values), $f_temp_hi);
                    break;

                case 'checkbox':
                    $selected_str = sb_stripos($f_temp, 'option') !== false ? ' selected="selected"' : ' checked="checked"';
                    if(!$filter && $id == -1 && isset($value['settings']['default']))
                    {
                        $f_value = $value['settings']['default'];
                    }

                    if($id == -1 && isset($value['settings']['save_status']) && $value['settings']['save_status'] == 1
                    && isset($_POST['user_f_'.$field_keys[$key]]) && $_POST['user_f_'.$field_keys[$key]] == 1)
                    {
                        $f_value = 1;
                    }

                    if(isset($_POST['user_f_'.$value['id']]) && $_POST['user_f_'.$value['id']] == 1)
                    {
                        $f_value = 1;
                    }

                    $f_temp = str_replace(array_merge(array('{OPT_SELECTED}'), $dop_tags), array_merge(array(($f_value == 1 ? $selected_str : '')), $dop_values), $f_temp);
                    break;

                case 'categs':
                    if (!isset($value['settings']) || !is_array($value['settings']) || !isset($value['settings']['ident']) || !isset($value['settings']['cat_id']))
                        break;

                    if (!isset($temps[$f_name_site.'_opt']) || trim($temps[$f_name_site.'_opt']) == '')
                        break;

                    $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $value['settings']['cat_id']);
                    if (!$res)
                        break;

                    list($left, $right) = $res[0];
                    $res = sql_query('SELECT cat_id, cat_title, cat_level, cat_url, cat_fields FROM sb_categs WHERE cat_ident = ? AND cat_left >= ?d AND cat_right <= ?d ORDER BY cat_left', $value['settings']['ident'], $left, $right);
                    if (!$res)
                        break;

                    $options = '';
                    $selected_str = sb_stripos($temps[$f_name_site.'_opt'], 'option') !== false ? ' selected="selected"' : ' checked="checked"';
                    foreach ($res as $res_val)
                    {
                        list($cat_id, $cat_title, $cat_level, $cat_url, $cat_fields) = $res_val;

                        $cat_url = urlencode($cat_url);

                        $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_LEVEL}', '{OPT_URL}'),
                                                   array($cat_id, ($cat_id == $f_value ? $selected_str : ''), $cat_title, $cat_level, $cat_url),
                                                   $temps[$f_name_site.'_opt']);
                    }

                    $f_temp = str_replace('{OPTIONS}', $options, $f_temp);
                    break;

                case 'select_sprav':
                case 'multiselect_sprav':
                case 'radio_sprav':
                case 'checkbox_sprav':
                    $f_value = explode(',', $f_value);
                    if (!isset($value['settings']) || !is_array($value['settings']) || !isset($value['settings']['sprav_ids']))
                        break;

                    if (!isset($temps[$f_name_site.'_opt']) || trim($temps[$f_name_site.'_opt']) == '')
                        break;

                    $subcategs = isset($value['settings']['subcategs']) && $value['settings']['subcategs'] == 1;
                    if ($subcategs)
                    {
                        $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id IN (0, '.$value['settings']['sprav_ids'].')');
                        if ($res)
                        {
                            $sql = '(';
                            $num = count($res);
                            for ($i = 0; $i < $num; $i++)
                            {
                                list($left, $right) = $res[$i];
                                $sql .= 'c.cat_left >= '.$left.' AND c.cat_right <= '.$right.($i != $num - 1 ? ' OR ' : '');
                            }
                            $sql .= ')';

                            $res = sql_query('SELECT DISTINCT s.s_id, s.s_title, s.s_prop1, s.s_prop2, s.s_prop3, c.cat_id, c.cat_title FROM sb_sprav s, sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND '.$sql.' AND l.link_cat_id=c.cat_id AND l.link_el_id=s.s_id AND s.s_active=1 ORDER BY c.cat_left, s.s_sort, s.s_title', 'pl_sprav');
                        }
                    }
                    else
                    {
                        $res = sql_query('SELECT DISTINCT s.s_id, s.s_title, s.s_prop1, s.s_prop2, s.s_prop3, c.cat_id, c.cat_title FROM sb_sprav s, sb_catlinks l, sb_categs c WHERE c.cat_id IN (0, '.$value['settings']['sprav_ids'].') AND l.link_cat_id = c.cat_id AND l.link_el_id=s.s_id AND s.s_active=1 ORDER BY c.cat_left, s.s_sort, s.s_title');
                    }

                    if (!$res)
                        break;

                    $options = '';
                    $selected_str = sb_stripos($temps[$f_name_site.'_opt'], 'option') !== false ? ' selected="selected"' : ' checked="checked"';
                    foreach ($res as $res_val)
                    {
                        list($s_id, $s_title, $s_prop1, $s_prop2, $s_prop3, $cat_id, $cat_title) = $res_val;

                        $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_DESC1}', '{OPT_DESC2}', '{OPT_DESC3}', '{OPT_CAT_ID}', '{OPT_CAT_TITLE}'),
                                                   array($s_id, (in_array($s_id, $f_value) ? $selected_str : ''), $s_title, $s_prop1, $s_prop2, $s_prop3, $cat_id, $cat_title),
                                                   $temps[$f_name_site.'_opt']);
                    }

                    $f_temp = str_replace('{OPTIONS}', $options, $f_temp);
                    break;

                case 'link_sprav':
                    if (!isset($value['settings']) || !isset($value['settings']['sprav_id']))
                        break;

                    if (!isset($temps[$f_name_site.'_opt']) || trim($temps[$f_name_site.'_opt']) == '')
                        break;

                    $res = sql_query('SELECT cat_left, cat_right, cat_level FROM sb_categs WHERE cat_id=?d', $value['settings']['sprav_id']);
                    if (!$res)
                        break;

                    list($left, $right, $level) = $res[0];

                    $tmp_first_cat_level = intval($level) + 1;
                    $scat_sql = (!$value['settings']['subcategs']) ? ' AND c.cat_level='.$tmp_first_cat_level : '';
                    // Получаем категории первого уровня и их подкатегории
                    $tmp_res = sql_query('SELECT c.cat_id, c.cat_title, c.cat_level, c.cat_left, c.cat_right, c.cat_level
                        FROM sb_categs c
                        WHERE c.cat_left > ?d AND c.cat_right < ?d
                        AND c.cat_ident=?
                        '.$scat_sql.'
                        ORDER BY c.cat_left', $left, $right, 'pl_sprav');

                    $tmp_categs = array();
                    foreach ($tmp_res as $row)
                    {
                        $row['el_cnt'] = 0;
                        $tmp_categs[$row[0]] = $row;
                    }
                    // Количество активных элементов категорий
                    $tmp_res = sql_query('SELECT l.link_cat_id, count(s.s_id) AS c_el_cnt FROM sb_catlinks l, sb_sprav s
                              WHERE l.link_el_id=s.s_id
                              AND s.s_active=1
                              AND l.link_cat_id IN (?a)
                              GROUP BY l.link_cat_id', array_keys($tmp_categs));

                    foreach ($tmp_res as $row)
                    {
                        $tmp_categs[$row[0]]['el_cnt'] = intval($row[1]);
                    }

                    // если нужно учитывать эелементы из подкатегорий
                    if ($value['settings']['subcategs'])
                    {
                        $first_res = array();
                        // Переносим категории первого уровня
                        foreach($tmp_categs as $key => $cat)
                        {
                            if ($cat[5] == $tmp_first_cat_level)
                            {
                                unset($cat[5]);
                                $first_res[$key] = $cat;
                                unset($tmp_categs[$key]);
                            }
                        }
                        // Для категорий не первого уровня - добавляем количество хи элементов к количеству элементов родительской категории
                        foreach ($tmp_categs as $s_key => $s_cat)
                        {
                            if (intval($s_cat['el_cnt']) > 0)
                            {
                                foreach ($first_res as $key => $cat)
                                {
                                    if ($cat[3] < $s_cat[3] && $s_cat[4] < $cat[4]) {
                                        $first_res[$key]['el_cnt'] += $s_cat['el_cnt'];
                                    }
                                }
                            }
                        }
                    }
                    else
                        $first_res = $tmp_categs;

                    // Удаляем категории без элементов
                    foreach ($first_res as $key => $cat)
                    {
                        if (!intval($cat['el_cnt']))
                            unset($first_res[$key]);
                        else
                            unset($first_res[$key]['el_cnt']);
                    }
                    $first_res = array_values($first_res);

                    if (!$first_res)
                        break;

                    $first_value = 0;
                    $second_value = 0;

                    if ($f_value != '')
                    {
                        $f_value = explode(',', $f_value);

                        $first_value = intval($f_value[0]);
                        $second_value = intval($f_value[1]);
                    }

                    $left = $first_res[0][3];
                    $right = $first_res[0][4];
                    $options = '';
                    $first_cat_title = '';
                    $first_cat_level = '';

                    foreach ($first_res as $res_val)
                    {
                        list($cat_id, $cat_title, $cat_level, $cat_left, $cat_right) = $res_val;

                        if ($cat_id == $first_value)
                        {
                            $left = $cat_left;
                            $right = $cat_right;
                            $first_cat_title = $cat_title;
                            $first_cat_level = $cat_level;

                            $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_LEVEL}'),
                                                   array($cat_id, ' selected="selected"', $cat_title, $cat_level),
                                                   $temps[$f_name_site.'_opt']);
                        }
                        else
                        {
                            $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_LEVEL}'),
                                                   array($cat_id, '', $cat_title, $cat_level),
                                                   $temps[$f_name_site.'_opt']);
                        }
                    }

                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', str_replace('{OPTIONS}', $options, $f_temp));

                    if (!isset($temps[$f_name_site.'_link']) || trim($temps[$f_name_site.'_link']) == '' ||
                        !isset($temps[$f_name_site.'_opt_link']) || trim($temps[$f_name_site.'_opt_link']) == '')
                        continue 2;

                    $second_res = false;
                    $options = '';

                    if (preg_match('/[^a-zA-Z]+option[\s]+/'.SB_PREG_MOD, $f_temp) == 0)
                    {
                        if ($first_cat_level == '')
                        {
                            $first_value = $first_res[0][0];
                            $first_cat_title = $first_res[0][1];
                            $first_cat_level = $first_res[0][2];
                        }
                    }
                    elseif ($first_cat_level == '')
                    {
                        $left = -1;
                        $right = -1;
                    }

                    if ($value['settings']['subcategs'] == 1)
                    {
                        $second_res = sql_query('SELECT DISTINCT s.s_id, s.s_title, s.s_prop1, s.s_prop2, s.s_prop3 FROM sb_sprav s, sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND c.cat_left >= ?d AND c.cat_right <= ?d AND l.link_cat_id=c.cat_id AND l.link_el_id=s.s_id AND s.s_active=1 ORDER BY s.s_sort, s.s_title', 'pl_sprav', $left, $right);
                    }
                    else
                    {
                        $second_res = sql_query('SELECT DISTINCT s.s_id, s.s_title, s.s_prop1, s.s_prop2, s.s_prop3 FROM sb_sprav s, sb_catlinks l WHERE l.link_cat_id = ?d AND l.link_el_id=s.s_id AND s.s_active=1 ORDER BY s.s_sort, s.s_title', $first_value);
                    }

                    $tags[] = '{'.$value['tag'].'_LINK}';

                    if ($second_res)
                    {
                        foreach ($second_res as $res_val)
                        {
                            list($s_id, $s_title, $s_prop1, $s_prop2, $s_prop3) = $res_val;

                            $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_DESC1}', '{OPT_DESC2}', '{OPT_DESC3}', '{OPT_CAT_ID}', '{OPT_CAT_TITLE}'),
                                                       array($s_id, ($s_id == $second_value ? ' selected="selected"' : ''), $s_title, $s_prop1, $s_prop2, $s_prop3, $first_value, $first_cat_title),
                                                       $temps[$f_name_site.'_opt_link']);
                        }
                    }

                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', str_replace('{OPTIONS}', $options, $temps[$f_name_site.'_link']));
                    continue 2;

                case 'select_plugin':
                case 'radio_plugin':
                case 'checkbox_plugin':
                case 'elems_plugin':
                case 'multiselect_plugin':
                    $f_value = explode(',', $f_value);
                    if (!isset($value['settings']) || !is_array($value['settings']) || (!isset($value['settings']['modules_cat_id']) && $f_type != 'elems_plugin'))
                        break;

                    if (!isset($temps[$f_name_site.'_opt']) || trim($temps[$f_name_site.'_opt']) == '')
                        break;

                    $sql_params = getPluginSqlParams($value['settings']['ident']);
                    $sql_fields = '';
                    $plugin_tags = array();
                    if(!isset($_SESSION['sbPlugins']))
                    {
                        $templates_tags = array();
                        if(preg_match_all('/{OPT_STD_([A-Z_]+)}/s'.SB_PREG_MOD, $temps[$f_name_site.'_opt'], $templates_tags))
                        {
                            foreach($templates_tags[1] as $v)
                            {
                                if (!in_array('{OPT_STD_'.$v.'}', $plugin_tags))
                                {
                                    $sql_fields .= ', el.'.strtolower($v);
                                    $plugin_tags[] = '{OPT_STD_'.$v.'}';
                                }
                            }
                        }
                    }

                    $plugin_fields = getPluginTitleFields($value['settings']['ident']);
                    foreach($plugin_fields as $v)
                    {
                        $sql_fields .= ', el.'.$v['field'];
                        $plugin_tags[] = $v['tag'];
                    }

                    // Если тип поля - выбор элемента - достаем корневой раздел
                    if($f_type == 'elems_plugin')
                    {
                        $res = sql_query('SELECT cat_id FROM sb_categs WHERE cat_ident = ? AND cat_level = 0', $value['settings']['ident']);
                        if($res)
                        {
                            $value['settings']['modules_cat_id'] = $res[0][0];
                        }
                        $subcategs = 1;
                    }
                    else
                    {
                        $subcategs = isset($value['settings']['modules_subcategs']) && $value['settings']['modules_subcategs'] == 1;
                    }

                    if ($subcategs)
                    {
                        $res = sql_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id IN (0, '.str_replace('^', ',', $value['settings']['modules_cat_id']).')');
                        if ($res)
                        {
                            $sql = '(';
                            $num = count($res);
                            for ($i = 0; $i < $num; $i++)
                            {
                                list($left, $right) = $res[$i];
                                $sql .= 'c.cat_left >= '.$left.' AND c.cat_right <= '.$right.($i != $num - 1 ? ' OR ' : '');
                            }
                            $sql .= ')';

                            $res = sql_query('SELECT DISTINCT c.cat_id, c.cat_title, el.'.$sql_params['id'].' '.$sql_fields.' FROM '.$sql_params['table'].' el, sb_catlinks l, sb_categs c WHERE c.cat_ident=? AND '.$sql.' AND l.link_cat_id=c.cat_id AND l.link_el_id=el.'.$sql_params['id'].(isset($sql_params['show']) && $sql_params['show'] != '' ? ' AND el.'.$sql_params['show'].'=1' : '').' ORDER BY c.cat_left'.(isset($sql_params['sort']) ? ', el.'.$sql_params['sort'] : ''), $value['settings']['ident']);
                        }
                    }
                    else
                    {
                        $res = sql_query('SELECT DISTINCT c.cat_id, c.cat_title, el.'.$sql_params['id'].' '.$sql_fields.' FROM '.$sql_params['table'].' el, sb_catlinks l, sb_categs c WHERE c.cat_id IN (0, '.str_replace('^', ',', $value['settings']['modules_cat_id']).') AND l.link_cat_id = c.cat_id AND l.link_el_id=el.'.$sql_params['id'].(isset($sql_params['show']) && $sql_params['show'] != '' ? ' AND el.'.$sql_params['show'].'=1' : '').' ORDER BY c.cat_left'.(isset($sql_params['sort']) ? ', el.'.$sql_params['sort'] : ''));
                    }

                    if (!$res)
                        break;

                    $options = '';
                    $selected_str = sb_stripos($temps[$f_name_site.'_opt'], 'option') !== false ? ' selected="selected"' : ' checked="checked"';
                    foreach ($res as $res_val)
                    {
                        $options .= str_replace(array_merge(array('{OPT_CAT_ID}', '{OPT_CAT_TITLE}', '{OPT_VALUE}'), $plugin_tags, array('{OPT_SELECTED}')),
                                                array_merge($res_val, array((in_array($res_val[2], $f_value) ? $selected_str : ''))), $temps[$f_name_site.'_opt']);
                    }
                    $f_temp = str_replace('{OPTIONS}', $options, $f_temp);
                    break;

                case 'link_plugin':
                    if (!isset($value['settings']) || !isset($value['settings']['modules_cat_id']))
                        break;

                    if (!isset($temps[$f_name_site.'_opt']) || trim($temps[$f_name_site.'_opt']) == '')
                        break;

                    $res = sql_query('SELECT cat_left, cat_right, cat_level FROM sb_categs WHERE cat_id=?d', $value['settings']['modules_cat_id']);
                    if (!$res)
                        break;

                    list($left, $right, $level) = $res[0];

                    $first_res = sql_query('SELECT cat_id, cat_title, cat_level, cat_left, cat_right, cat_level FROM sb_categs WHERE cat_left > ?d AND cat_right < ?d AND cat_ident=? AND cat_level > ?d ORDER BY cat_left', $left, $right, $value['settings']['ident'], $level);
                    if (!$first_res)
                        break;

                    $first_value = 0;
                    $second_value = 0;

                    if ($f_value != '')
                    {
                        $f_value = explode(',', $f_value);

                        $first_value = intval($f_value[0]);
                        $second_value = intval($f_value[1]);
                    }

                    $left = $first_res[0][3];
                    $right = $first_res[0][4];
                    $options = '';
                    $first_cat_title = '';
                    $first_cat_level = '';
                    
                    foreach ($first_res as $res_val)
                    {
                        list($cat_id, $cat_title, $cat_level, $cat_left, $cat_right) = $res_val;
                        $r = $cat_level-$level-1;
                        $sep = '';
                        if($r > 0)
                        {
                            for($i = 0; $i < $r; $i++)
                            {
                                $sep .= '&nbsp;&nbsp;';
                            }
                        }
                        
                        if ($cat_id == $first_value)
                        {
                            $left = $cat_left;
                            $right = $cat_right;
                            $first_cat_title = $cat_title;
                            $first_cat_level = $cat_level;

                            $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_LEVEL}'),
                                                   array($cat_id, ' selected="selected"', $sep.$cat_title, $cat_level),
                                                   $temps[$f_name_site.'_opt']);
                        }
                        else
                        {
                            $options .= str_replace(array('{OPT_VALUE}', '{OPT_SELECTED}', '{OPT_TEXT}', '{OPT_LEVEL}'),
                                                   array($cat_id, '', $sep.$cat_title, $cat_level),
                                                   $temps[$f_name_site.'_opt']);
                        }
                    }

                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', str_replace('{OPTIONS}', $options, $f_temp));
                    if (!isset($temps[$f_name_site.'_link']) || trim($temps[$f_name_site.'_link']) == '' ||
                        !isset($temps[$f_name_site.'_opt_link']) || trim($temps[$f_name_site.'_opt_link']) == '')
                        continue 2;

                    $second_res = false;
                    $options = '';

                    if (preg_match('/[^a-zA-Z]+option[\s]+/'.SB_PREG_MOD, $f_temp) == 0)
                    {
                        if ($first_cat_level == '')
                        {
                            $first_value = $first_res[0][0];
                            $first_cat_title = $first_res[0][1];
                            $first_cat_level = $first_res[0][2];
                        }
                    }
                    elseif ($first_cat_level == '')
                    {
                        $left = -1;
                        $right = -1;
                    }

                    $sql_params = getPluginSqlParams($value['settings']['ident']);
                    $sql_fields = '';
                    $plugin_tags = array();
                    if(!isset($_SESSION['sbPlugins']))
                    {
                        $templates_tags = array();
                        preg_match_all('/{OPT_STD_([A-Z_]+)}/s'.SB_PREG_MOD, $temps[$f_name_site.'_opt_link'], $templates_tags);
                        if(isset($templates_tags[1]))
                        {
                            foreach($templates_tags[1] as $v)
                            {
                                $sql_fields .= ', el.'.strtolower($v);
                                $plugin_tags[] = '{OPT_STD_'.$v.'}';
                            }
                        }
                    }

                    $plugin_fields = getPluginTitleFields($value['settings']['ident']);
                    foreach($plugin_fields as $v)
                    {
                        $sql_fields .= ', el.'.$v['field'];
                        $plugin_tags[] = $v['tag'];
                    }
                    if ($value['settings']['modules_subcategs'] == 1)
                    {
                        $second_res = sql_query('SELECT DISTINCT el.'.$sql_params['id'].' '.$sql_fields.' FROM '.$sql_params['table'].' el, sb_catlinks l, sb_categs c WHERE el.p_active AND c.cat_ident=? AND c.cat_left >= ?d AND c.cat_right <= ?d AND l.link_cat_id=c.cat_id AND l.link_el_id=el.'.$sql_params['id'].' ORDER BY el.'.$sql_params['sort'], $value['settings']['ident'], $left, $right);
                    }
                    else
                    {
                        $second_res = sql_query('SELECT DISTINCT el.'.$sql_params['id'].', '.$sql_fields.' FROM '.$sql_params['table'].' el, sb_catlinks l WHERE el.p_active AND l.link_cat_id = ?d AND l.link_el_id=el.'.$sql_params['id'].' ORDER BY el.'.$sql_params['id'], $first_value);
                    }

                    $tags[] = '{'.$value['tag'].'_LINK}';

                    if ($second_res)
                    {
                        foreach ($second_res as $res_val)
                        {
                            $options .= str_replace(array_merge(array('{OPT_CAT_ID}', '{OPT_CAT_TITLE}', '{OPT_VALUE}'), $plugin_tags, array('{OPT_SELECTED}')),
                                                array_merge(array($first_value, $first_cat_title), $res_val, array(($res_val[0] == $second_value ? ' selected="selected"' : ''))),  $temps[$f_name_site.'_opt_link']);
                        }
                    }

                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', str_replace('{OPTIONS}', $options, $temps[$f_name_site.'_link']));
                    continue 2;

                case 'google_coords':
                case 'yandex_coords':
                    if($id == -1)
                    {
                        $f_value_longtitude = $value['settings']['default_longtitude'];
                        $f_value_latitude = $value['settings']['default_latitude'];
                    }
                    $f_temp_latitude = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value_latitude), $dop_values), $f_temp_latitude);
                    $f_temp_longtitude = str_replace(array_merge(array('{VALUE}'), $dop_tags), array_merge(array($f_value_longtitude), $dop_values), $f_temp_longtitude);
                    break;

                default:
                    break;
            }

            if ($f_type != 'google_coords' && $f_type != 'yandex_coords')
            {
                $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $f_temp);
                if ($filter && ($f_type == 'number' || $f_type == 'date'))
                {
                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $f_temp_lo);
                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $f_temp_hi);
                }
                if($f_type == 'image')
                {
                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\_NOW}/'.SB_PREG_MOD, '', $f_temp_pic_now);
                }
                if($f_type == 'file')
                {
                    $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\_NOW}/'.SB_PREG_MOD, '', $f_temp_file_now);
                }

            }
            else
            {
                $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $f_temp_latitude);
                $values[] = preg_replace('/\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\}/'.SB_PREG_MOD, '', $f_temp_longtitude);
            }
        }
        return true;
    }

    /**
     * Устанавливает событие и параметры, вызываемое для подгрузки закладок через AJAX
     *
     * Данное событие должно возвращать HTML-код подгружаемой закладки. ID закладки передается событию
     * через $_GET['tab_id'].
     *
     * @param string $url Событие и параметры.
     */
    public function setAutoLoading($url)
    {
        $this->mAutoLoadingURL = $url;
    }

    /**
     * Функция возвращает кол-во закладок
     *
     * @return int Кол-во закладок
     */
    public function getTabCount()
    {
       return count($this->mTabs);
    }

    /**
     * Выводит форму (если параметр $echo установлен в false, то возвращает HTML-код формы
     *
     * @param bool $echo Выводить форму в браузер пользователя или нет
     *
     * @return string HTML-код формы.
     */
    public function show($echo = true)
    {
        if (count($this->mFields) == 0)
            return;

        if (!$this->mShowTitle)
            $this->mTitleWidth = 0;

        $result = '
        <script>
            sb_layout_hcol_width = '.$this->mTitleWidth.';
        </script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbLayout.js.php"></script>';

        if($this->mShowInfo)
        {
            $result .= '
            <div id="sb_layout_info" style="position: absolute;visibility: hidden; z-index: 10000;">
                <table cellpadding="0" cellspacing="0" width="100%">
                <tr style="height:7px;">
                    <td width="7"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_tl.gif" width="7" height="7"></td>
                    <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_t.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="7"></td>
                    <td width="7"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_tr.gif" width="7" height="7"></td>
                </tr>
                <tr>
                    <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_l.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="1"></td>
                    <td style="background-color: #ffffe1;font-size: 11px;font-family: Arial, Sans-Serif;" id="sb_layout_info_text">&nbsp;</td>
                    <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_r.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="1"></td>
                </tr>
                <tr>
                    <td valign="top"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_bl.gif" width="7" height="7"></td>
                    <td style="background-image: url('.SB_CMS_CHARACTERS_URL.'/hint_bottom_bg.gif);"><img src="'.SB_CMS_IMG_URL.'/blank.gif" width="7" height="7"></td>
                    <td valign="top"><img src="'.SB_CMS_CHARACTERS_URL.'/hint_br.gif" width="7" height="7"></td>
                </tr>
                </table>
            </div>
            <script>
                sbAddEvent(document, "mouseover", sbLayoutShowInfo);
            </script>';
        }

        if (count($this->mTabs) == 0)
        {
            // закладок нет
            if ($this->mFormAction != '')
            {
                // если есть action формы, выводим тег form
                $result .= '<form action="'.$this->mFormAction.'" method="'.$this->mFormMethod.'"'.($this->mFormTarget != '' ? ' target="'.$this->mFormTarget.'"' : '').($this->mFormOnSubmit != '' ? ' onsubmit="return '.$this->mFormOnSubmit.'"' : '').' id="'.$this->mFormId.'" '.$this->mFormDopStr.'>';
            }

            // начало основной таблицы
            $result .= '<table width="'.$this->mTableWidth.'" id="sb_layout_table" cellspacing="0" cellpadding="5" class="form" align="center">';

            foreach ($this->mFields as $key => $value)
            {
                // вывод полей формы
                list($tab_id, $field_title, $field_object, $th_str, $td_str, $tr_str) = $value;

                if ($field_object != false)
                {
                    if(method_exists($field_object, 'getJavaScript'))
                    {
                        $result .= $field_object->getJavaScript();
                    }

                    // вывод поля
                    if (method_exists($field_object, 'getType') && strtolower($field_object->getType()) == 'hidden')
                    {
                        // если это hidden-поле, то просто выводим его
                        $result .= $field_object->getField();
                    }
                    else
                    {
                        if(method_exists($field_object, 'getFull'))
                        {
                            $result .= $field_object->getFull($this->mTitleWidth, $field_title, $this->mShowTitle, 'class="even"'.$tr_str, $th_str, $td_str);
                        }
                        else
                        {
                            $result .= '<tr class="even"'.($tr_str != '' ? ' '.$tr_str : '').'>'.
                                      ($this->mShowTitle ? '<th width="'.$this->mTitleWidth.'"'.($th_str != '' ? ' '.$th_str : '').'><img src="'.SB_CMS_IMG_URL.'/blank.gif" height="1" width="'.$this->mTitleWidth.'" /><br />'.($field_title != '' ? $field_title.($this->mShowColon ? ':' : '') : '' ).'</th>' : '').
                                      '<td width="100%"'.($td_str != '' ? ' '.$td_str : '').'>'.$field_object->getField().'</td>
                                    </tr>';
                        }
                    }
                }
                else
                {
                    // вывод заголовка
                    $result .= '<tr><th class="header"'.($this->mShowTitle ? ' colspan="2"' : '').'>'.$field_title.'</th></tr>';
                }
            }

            if (count($this->mButtons) > 0)
            {
                // вывод кнопок формы
                $result .= '<tr><td class="footer"'.($this->mShowTitle ? ' colspan="2"' : '').'>
                    <div class="footer">';
                foreach ($this->mButtons as $key => $value)
                {
                    $result .= $value->getField().'&nbsp;&nbsp;';
                }
                $result .= '</div></td></tr>';
            }

            $result .= '</table>';

            if ($this->mFormAction != '')
                $result .= '</form>';
        }
        else
        {
            // если есть закладки
            require_once(SB_CMS_LIB_PATH.'/sbTabs.inc.php');

            $tabs = new sbTabs($this->mFormAction, $this->mFormTarget, $this->mFormMethod, $this->mFormOnSubmit, 'id="'.$this->mFormId.'" '.$this->mFormDopStr);
            $tabs->mOnLoad = $this->mTabsOnLoad;
            $tabs->mTabsOnLoad = $this->mTabsOnLoadTab;

            if ($this->mAutoLoadingURL != '')
            {
                $tabId = (isset($_GET['tab_id']) ? intval($_GET['tab_id']) : -1);
                $tabs->setAutoLoading($this->mAutoLoadingURL);
            }
            else
            {
                $tabId = -1;
            }

            $bottom = '';
            if (count($this->mButtons) > 0)
            {
                // выводим кнопки
                foreach ($this->mButtons as $value)
                {
                    $bottom .= $value->getField().'&nbsp;&nbsp;';
                }
            }

            $tabs->setBottom($bottom);

            $tabNum = 0;
            foreach ($this->mTabs as $key => $value)
            {
                if ($this->mAutoLoadingURL != '' && $tabNum != $tabId)
                {
                    $tabStr = '';
                }
                else
                {
                    $tabStr = '<table width="'.$this->mTableWidth.'" cellspacing="0" cellpadding="5" class="form" align="center">';

                    foreach ($this->mFields as $field)
                    {
                        list($tab_id, $field_title, $field_object, $th_str, $td_str, $tr_str) = $field;

                        if ($tab_id != $key) continue;

                        if ($field_object != false)
                        {
                            if(method_exists($field_object, 'getJavaScript'))
                            {
                                $result .= $field_object->getJavaScript();
                            }

                            // вывод поля
                            if (method_exists($field_object, 'getType') && strtolower($field_object->getType()) == 'hidden')
                            {
                                // если это hidden-поле, то просто выводим его
                                $tabStr .= $field_object->getField();
                            }
                            else
                            {
                                if(method_exists($field_object, 'getFull'))
                                {
                                    $tabStr .= $field_object->getFull($this->mTitleWidth, $field_title, $this->mShowTitle, $tr_str, $th_str, $td_str);
                                }
                                else
                                {
                                    $tabStr .= '<tr'.($tr_str != '' ? ' '.$tr_str : '').'>'.
                                              ($this->mShowTitle ? '<th width="'.$this->mTitleWidth.'"'.($th_str != '' ? ' '.$th_str : '').'><img src="'.SB_CMS_IMG_URL.'/blank.gif" height="1" width="'.$this->mTitleWidth.'" /><br />'.($field_title != '' ? $field_title.($this->mShowColon ? ':' : '') : '' ).'</th>' : '').
                                              '<td width="100%"'.($td_str != '' ? ' '.$td_str : '').'>'.$field_object->getField().'</td>
                                            </tr>';
                                }
                            }
                        }
                        else
                        {
                            // вывод заголовка
                            $tabStr .= '<tr><th class="header"'.($this->mShowTitle ? ' colspan="2"' : '').'>'.$field_title.'</th></tr>';
                        }
                    }
                    $tabStr .= '</table>';
                }

                $tabs->addTab($value['title'], $tabStr, $value['show']);

                $tabNum++;
            }

            $result .= $tabs->show(false);
        }

        if ($this->mOnSubmitJavascriptStr != '' || $this->mOnLoadJavascriptStr != '')
        {
            $result .= '<script>';

            if ($this->mOnSubmitJavascriptStr != '')
            {
                $result .= '
                var frm = sbGetE("'.$this->mFormId.'");
                function sbLayoutOnSubmitJS(e)
                {
                    var event = e ? e : window.event;
                    '.$this->mOnSubmitJavascriptStr.'
                }

                if (frm)
                    sbAddEvent(frm, "submit", sbLayoutOnSubmitJS);';
            }

            if ($this->mOnLoadJavascriptStr != '')
            {
                $result .= '
                function sbLayoutOnLoadJS(e)
                {
                    var event = e ? e : window.event;
                    '.$this->mOnLoadJavascriptStr.'
                }

                sbAddEvent(window, "load", sbLayoutOnLoadJS);';
            }

            $result .= '</script>';
        }

        if ($echo)
        {
            echo $result;
            return $result;
        }
        else
        {
            return $result;
        }
    }
    
    /**
     * Генерирует уникальное имя файла при аяксовой загрузке
     */
    private function setUniqueName($oldPath)
    {
        $data = pathinfo($oldPath);
        $uName = $data['filename'].'_'.uniqid().'.'.$data['extension'];
        
        return $uName;
    }
}
