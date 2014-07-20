<?php

class sbExport
{
    private $layout = null;
    private $templateName = '';
    //private $userFields = null;
    private $elemsFields = null;
    private $categsFields = null;
    private $ident = '';
    private $editTplId = null;
    private $xml = null;
    private $hasError = false;
    //private $exportFilePath = '';
    private $orderFields = null;

    public function __construct($action = null)
    {
        $this->layout = new sbLayout($action);
    }

    public function getForm($ident)
    {
        /** @var sbPlugins[] $_SESSION['sbPlugins'] */
        $this->ident = $ident;
        $pluginInfo = $_SESSION['sbPlugins']->getPluginsInfo();
        $exportInfo = $_SESSION['sbPlugins']->getExportImportInfo();

        // Если модуль из конструктора модулей - получаем его настройки
        if(!isset($pluginInfo[$ident]))
        {
            $fields = $exportInfo[$ident]['fields'];
        }
        else
        {
            $fields = $_SESSION['sbPlugins']->mFieldsInfo[$ident]['fields'];
            // Если модуль конструктора модулей - проверяем нужно ли выводить поля цен и инфо о заказе
            if (sb_strpos($this->ident, 'pl_plugin_') !== false && is_array($pluginInfo[$this->ident]['pm_settings']) && isset($pluginInfo[$this->ident]['pm_settings']['elems_settings'])) {
                $elems_settings = $pluginInfo[$this->ident]['pm_settings']['elems_settings'];
                if ($elems_settings['need_basket'] != 1) {
                    unset($fields['p_price1']);
                    unset($fields['p_price2']);
                    unset($fields['p_price3']);
                    unset($fields['p_price4']);
                    unset($fields['p_price5']);
                }
                if (!isset($elems_settings['show_goods']) || $elems_settings['show_goods'] != 1) {
                    unset($fields['p_order']);
                } else {
                    // поля информации о заказе
                    $this->initOrderFields();
                }
            }

            //Вытягиваем пользовательские поля элементов и разделов
            $res = sql_param_assoc('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', $ident);
            if($res)
            {
                if(trim($res[0]['pd_fields']) !== '')
                {
                    $this->elemsFields = unserialize(trim($res[0]['pd_fields']));
                }

                if(trim($res[0]['pd_categs']) !== '')
                {
                    $this->categsFields = unserialize(trim($res[0]['pd_categs']));
                }
            }
        }

        $this->layout->addTab(PL_EXPORT_EDIT_TAB1);

        $this->layout->addField(SB_EXPORT_IMPORT_TEMPLATE_TITLE, new sbLayoutInput('text', $this->templateName, 'templateName', 'templateName', 'style="width: 300px"', true));

        $options = array();
        $options['csv'] = PL_EXPORT_EDIT_CVS_EXPORT_FORMAT;
        $options['sb_xml'] = PL_EXPORT_EDIT_SB_XML_EXPORT_FORMAT;

        $fld = new sbLayoutSelect($options, 'export_type', '', 'onchange="ChTypeExport(this);"');
        if($this->xml !== null)
        {
            $type = $this->xml->xpath('export_type');
            $fld->mSelOptions = array((string)$type[0]);
        }
        $this->layout->addField (PL_EXPORT_EDIT_EXPORT_FORMAT, $fld);

        if ($ident == 'pl_polls_results')
        {
            $this->layout->addField('', new sbLayoutInput('hidden', '0', 'show_titles', '', ''));
            $this->layout->addField('', new sbLayoutInput('hidden', '0', 'sub_categs', '', ''));
        }
        else
        {

            $show_title = $in_one_row = $link_two_columns = null;
            if($this->xml !== null)
            {
                $show_title = $this->xml->xpath('show_titles');
                $show_title = (int)$show_title[0];
                $in_one_row = $this->xml->xpath('in_one_row');
                $in_one_row = (int)$in_one_row[0];
                $link_two_columns = $this->xml->xpath('links_two_columns');
                $link_two_columns = (int)$link_two_columns[0];
            }
            $this->layout->addField(PL_EXPORT_EDIT_TITLE_ELS, new sbLayoutInput('checkbox', '1', 'show_titles', '', ($show_title==1? 'checked="checked"' : '')), '', '', 'id="tr_show_titles"');
            $this->layout->addField(PL_EXPORT_EDIT_IN_ONE_ROW, new sbLayoutInput('checkbox', '1', 'in_one_row', '', ($in_one_row==1? 'checked="checked"' : '')), '', '', 'id="tr_one_row"');
            $this->layout->addField(PL_EXPORT_EDIT_LINKS_TITLE_ELS, new sbLayoutInput('checkbox', '1', 'links_two_columns', '', ($link_two_columns==1? 'checked="checked"' : '')), '', '', 'id="tr_links_two_columns"');
        }

        $this->layout->addTab(PL_EXPORT_EDIT_TAB2);
        if (!isset($pluginInfo[$ident]))
        {
            $this->layout->addField('', new sbLayoutInput('hidden', '1', 'with_els', '', ''));
        }
        else
        {
            $with_els = 1;
            if($this->xml !== null)
            {
                $elems = $this->xml->xpath('elems_fields/field');
                if(count($elems) == 0)
                {
                    $with_els = 0;
                }
            }
            $this->layout->addField(PL_EXPORT_EDIT_WITH_ELS, new sbLayoutInput('checkbox', '1', 'with_els', '', ($with_els == 1? 'checked="checked"' : '') . ' onClick="sbShowExportCats(this, \'els\');"'));
            $this->layout->addField('', new sbLayoutDelim());
        }

        $this->createSpravFields();
        $this->createOrderFields();
        $this->createElementsFields($fields);

        if(isset($pluginInfo[$ident]))
        {
            $this->layout->addTab(PL_EXPORT_EDIT_TAB3);
            $with_categs = 0;
            if($this->xml !== null)
            {
                $elems = $this->xml->xpath('cat_fields/field');
                if(count($elems) > 0)
                {
                    $with_categs = 1;
                }
            }
            $this->layout->addField(PL_EXPORT_EDIT_WITH_CATEGS, new sbLayoutInput('checkbox', '1', 'with_categs', '', ($with_categs == 1 ? 'checked="checked"' : '') . ' onClick="sbShowExportCats(this, \'categs\');"'), '', '', 'id="tr_with_categs"');
            $this->layout->addField('', new sbLayoutDelim(), '', '', 'id="delim_with_categs"');
            $this->createSpravFields(true);
            $this->createCategsFields();
        }

        $edit_rights = $_SESSION ['sbPlugins']->isRightAvailable((($ident != 'pl_polls_results')? $ident : 'pl_polls'), 'read');

        $this->layout->addButton('submit', KERNEL_SAVE, 'btn_save', 'btf_save', ($edit_rights ? 'sbnotdisable="yes"' : 'disabled="disabled" sbnotdisable="yes"'));
        $this->layout->addButton('button', KERNEL_CANCEL, '', 'btn_cancel', 'onclick="window.close();" sbnotdisable="yes"');

        echo '<script>
            var args = (_isIE ? dialogArguments : window.opener.sbModalDialog.args);
            function sbDelFields(s, all)
            {

                var count = s.length - 1;
                for(var i = count; i >= 0; i--)
                {
                    if(s[i].selected || all)
                    {
                        s[i].removeNode(true);
                    }
                }
            }
            function sbAddFields(s1, s2, all)
            {
                if(!s1 || !s2)
                {
                    return;
                }

                if (s2.length == 1 && s2[0].value == "0")
                {
                    s2[0].removeNode();
                }

                for (var i = 0; i < s1.length; i++)
                {
                    if(s1[i].selected || all)
                    {
                        var err = false;
                        for(var j = 0; j < s2.length; j++)
                        {
                            if(s2[j].value == s1[i].value)
                            {
                                err = true;
                                break;
                            }
                        }
                        if(err)
                        {
                            continue;
                        }
                        var op = document.createElement("option");
                        op.setAttribute("value", s1[i].value);
                        op.setAttribute("ondblclick", "this.removeNode(true);");

                        var text = document.createTextNode(s1[i].innerHTML);
                        op.appendChild(text);
                        s2.appendChild(op);
                    }
                }
            }
            function checkValues()
            {
                var tmpName = sbGetE("templateName");
                if(!tmpName || tmpName.value == "")
                {
                    alert("'.PL_EXPORT_NO_TEMPLATE_NAME.'");
                    return false;
                }
                var els = sbGetE("fields");
                var with_els = sbGetE("with_els");

                if(els && with_els.checked)
                {
                    var err = true;
                    if(els.length > 0)
                    {
                        for(var i = 0; i < els.length; i++)
                        {
                            if(els[i].value != "" && els[i].value != "0")
                            {
                                err = false;
                                break;
                            }
                        }
                    }
                    if(err)
                    {
                        alert("'.PL_EXPORT_EDIT_NO_CHECKED_ELS_FIELDS.'");
                        return false;
                    }
                }

                var cats = sbGetE("cat_fields");
                var with_cats = sbGetE("with_categs");
                if(cats && with_cats.checked)
                {
                    var err = true;
                    if(cats.length > 0)
                    {
                        for(var i = 0; i < cats.length; i++)
                        {
                            if(cats[i].value != "" && cats[i].value != "0")
                            {
                                err = false;
                                break;
                            }
                        }
                    }
                    if(err)
                    {
                        alert("'.PL_EXPORT_EDIT_NO_CHECKED_CAT_FIELDS.'");
                        return false;
                    }
                }
                if(cats && els && !with_cats.checked && !with_els.checked)
                {
                    alert("'.PL_EXPORT_EDIT_NO_CHECKED_FIELDS.'");
                    return false;
                }
                if(els)
                {
                    for(var i = 0; i < els.length ; i++)
                    {
                        els[i].selected = true;
                    }
                }
                if(cats)
                {
                    for(var i = 0; i < cats.length;i++)
                    {
                        cats[i].selected = true;
                    }
                }

                var frm = sbGetE("main");
                var reg1 = /^(cat_)?sprav_setting/;

                for(elem in frm)
                {
                    var name = null;
                    if(typeof frm[elem] == "object")
                    {
                        try
                        {
                            name = frm[elem].name;
                        }
                        catch(e)
                        {
                            continue;
                        }
                        if(name && reg1.test(name) && -1 == frm[elem].selectedIndex)
                        {
                            frm[elem].selectedIndex = 0;
                        }
                    }
                }

                if(args)
                {
                    var frm = sbGetE("main");
                    for(ind in args)
                    {
                        var reg = new RegExp("_link");
                        if(!reg.test(ind))
                        {
                            var el = document.createElement("input");
                            el.id = ind;
                            el.type = "hidden";
                            el.value = args[ind]["value"]+"|"+args[ind]["type"];
                            el.name = "filter["+ind+"]";
                            frm.appendChild(el);
                        }
                        else
                        {
                            var link_sprav = sbGetE(ind.slice(0, -5));
                            if(link_sprav)
                            {
                                var tmp = link_sprav.value.split("|");
                                link_sprav.value = tmp[0]+","+args[ind]["value"]+"|"+args[ind]["type"];
                            }
                        }
                    }
                }
            }

            function sbShowExportCats(ch, prefix)
            {
                var el = sbGetE(prefix + "_fields");
                if(el && ch.checked)
                {
                    var a = "";
                }
                else if (el)
                {
                    var a = "none";
                }
                var els = el.parentNode.rows;

                var reg1 = /sprav_user_f/;
                var reg2 = /sprav_c_user_f/;

                var t = sbGetE("export_type");
                for(i = 2; i < els.length; i++)
                {
                    if(t && t.value == "sb_xml")
                    {
                        var id = els[i].id;
                        if(a == "" && (id == "sprav_su_status" || id == "sprav_su_mail_lang" || id == "sprav_su_mail_status" ||
                            id == "sprav_su_mail_subscription" || id == "sprav_m_conf_format" || id == "sprav_m_target" ||
                            id == "sprav_moderates_list" || id == "sprav_cat_m_conf_format" || id == "sprav_cat_user_lang" ||
                            id == "sprav_cat_status" || id == "sprav_moderates_list" || id == "sprav_ur_plugin_rights" ||
                            id == "sprav_ur_workflow_rights" || id == "cat_delim" || id == "cat_delim" || id == "el_delim" ||
                            reg1.test(id) || reg2.test(id)))
                        {
                            continue;
                        }
                    }

                    els[i].style.display = a;
                }
            }

            window.onload = function()
            {
                sbShowExportCats(sbGetE("with_categs"), \'categs\');
                sbShowExportCats(sbGetE("with_els"), \'els\');
                ChTypeExport(sbGetE("export_type"));
            }
            function ChTypeExport(el)
            {
                if(el.value == "sb_xml")
                {
                    var a = a_c = c_c = "none";
                    sbGetE("with_categs").checked = true;
                    sbShowExportCats(sbGetE("with_categs"), \'categs\');
                    sbGetE("tr_one_row").style.display = "none";
                    sbGetE("tr_links_two_columns").style.display = "none";
                }
                else
                {
                    var els = sbGetE("with_els");
                    var cats = sbGetE("with_categs");

                    var a = a_c = c_c = "";
                    if(!els.checked)
                    {
                        var a = "none";
                    }
                    if(!cats.checked)
                    {
                        var a_c = "none";
                    }
                    sbGetE("tr_one_row").style.display = "";
                    sbGetE("tr_links_two_columns").style.display = "";
                }

                sbGetE("delim_with_categs").style.display = a_c;
                sbGetE("tr_with_categs").style.display = c_c;
                sbGetE("tr_show_titles").style.display = c_c;
                sbGetE("show_titles").style.display = c_c;

                var e = sbGetE("sprav_su_status");
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_su_mail_lang")
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_su_mail_status");
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_su_mail_subscription");
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_m_conf_format");
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_m_target");
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_moderates_list");
                if(e)
                    e.style.display = a;
                e = sbGetE("sprav_cat_m_conf_format");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("sprav_cat_user_lang");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("sprav_cat_status");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("sprav_moderates_list");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("sprav_ur_plugin_rights");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("sprav_ur_workflow_rights");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("cat_delim");
                if(e)
                    e.style.display = a_c;
                e = sbGetE("el_delim");
                if(e)
                    e.style.display = a;

                var e = sbGetE("sprav_user_f_1");
                if(e)
                {
                    e.style.display = a;
                }
                var c_e = sbGetE("sprav_c_user_f_1");
                if(c_e)
                {
                    c_e.style.display = a_c;
                }
                var i = 2;
                while(e || c_e)
                {
                    var e = sbGetE("sprav_user_f_"+i);
                    if(e)
                    {
                        e.style.display = a;
                    }
                    var c_e = sbGetE("sprav_c_user_f_"+i);
                    if(c_e)
                    {
                        c_e.style.display = a_c;
                    }
                    i++;
                }
            }
        </script>';
        return $this->layout;
    }

    public function createTemplate($id = null)
    {
        $xml = simplexml_load_string('<export></export>');
        extract($_POST);
        $ident = $_GET['ident'];

        $row = array(
            'seit_title' => $templateName,
            'seit_plugin_ident' => $ident,
        );

        $pl_ident = $xml->addChild('pl_ident');
        $node = dom_import_simplexml($pl_ident);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($ident));

        $xml->addChild('export_type', isset($export_type)? $export_type : 'csv');
        $xml->addChild('show_titles', isset($show_titles)? $show_titles : 0);
        $xml->addChild('in_one_row', isset($in_one_row)? $in_one_row : 0);
        $xml->addChild('links_two_columns', isset($links_two_columns)? $links_two_columns : 0);
        $elems_sprav = $xml->addChild('elems_sprav');
        $elems_order = $xml->addChild('elems_order');
        $elems_fields = $xml->addChild('elems_fields');
        $categ_sprav = $xml->addChild('cat_sprav');
        $categ_fields = $xml->addChild('cat_fields');

        if(isset($with_els) && 1 == $with_els)
        {
            //Формируем XML-данные по полям элемента
            if(isset($sprav_setting) && !empty($sprav_setting) && isset($export_type) && $export_type != 'sb_xml')
            {
                //Получаем номера пользовательских полей, содержащих справочники или модули
                $sprav_keys = array_keys($sprav_setting);
                if(!empty($sprav_keys))
                {
                    $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?s', $ident);
                    if($res && $res[0][0] != '')
                    {
                        $pd_fields = unserialize($res[0][0]);
                        if(!empty($pd_fields))
                        {
                            foreach($pd_fields as $field)
                            {
                                if(in_array($field['id'], $sprav_keys))
                                {
                                    $item = $elems_sprav->addChild('item');
                                    $item->addAttribute('fld_id', $field['id']);
                                    $item->addAttribute('sprav_type', $field['type']);
                                    $plugin = explode('__', $sprav_setting[$field['id']][0]);
                                    if(count($plugin) == 2)
                                    {
                                        $item->addAttribute('plugin', $plugin[0]);
                                    }
                                    else
                                    {
                                        $item->addAttribute('plugin', 'pl_sprav');
                                    }

                                    foreach($sprav_setting[$field['id']] as $sprav_fld)
                                    {
                                        $sprav_fld = preg_replace('/^.*__(.*)$/', '$1', $sprav_fld);
                                        $item->addChild('sprav_field', $sprav_fld);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if(isset($order_setting) && !empty($order_setting) && isset($export_type) && $export_type != 'sb_xml')
            {
                // Поля информации по заказу
                foreach($order_setting as $order_fld)
                {
                    //$sprav_fld = preg_replace('/^.*__(.*)$/', '$1', $sprav_fld);
                    $elems_order->addChild('order_field', $order_fld);
                }
            }

            if(isset($fields) && !empty($fields))
            {
                foreach($fields as $field)
                {
                    $fld = $elems_fields->addChild('field');
                    $fld->addAttribute('name', $field);
                }
            }
        }

        if(isset($with_categs) && 1 == $with_categs)
        {
            //Формируем XML-данные по полям раздела
            if(isset($sprav_setting) && !empty($sprav_setting) && isset($export_type) && $export_type != 'sb_xml')
            {
                //Получаем номера пользовательских полей, содержащих справочники или модули
                $sprav_keys = array_keys(isset($cat_sprav_setting)? $cat_sprav_setting : array());

                if(!empty($sprav_keys))
                {
                    $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?s', $ident);
                    if($res && $res[0][0] != '')
                    {
                        $pd_categs = unserialize($res[0][0]);
                        if(!empty($pd_categs))
                        {
                            foreach($pd_categs as $field)
                            {
                                if(in_array($field['id'], $sprav_keys))
                                {
                                    $item = $categ_sprav->addChild('item');
                                    $item->addAttribute('fld_id', $field['id']);
                                    $item->addAttribute('sprav_type', $field['type']);
                                    $plugin = explode('__', $cat_sprav_setting[$field['id']][0]);
                                    if(count($plugin) == 2)
                                    {
                                        $item->addAttribute('plugin', $plugin[0]);
                                    }
                                    else
                                    {
                                        $item->addAttribute('plugin', 'pl_sprav');
                                    }

                                    foreach($cat_sprav_setting[$field['id']] as $sprav_fld)
                                    {
                                        $sprav_fld = preg_replace('/^.*__(.*)$/', '$1', $sprav_fld);
                                        $item->addChild('sprav_field', $sprav_fld);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (isset($cat_fields) && !empty($cat_fields))
            {
                foreach ($cat_fields as $field)
                {
                    $fld = $categ_fields->addChild('field');
                    $fld->addAttribute('name', $field);
                }
            }
        }

        $row['seit_export'] = $xml->asXML();
        if(null == $id)
        {
            sql_param_query('INSERT INTO sb_export_import_templates SET ?a', $row);
        }
        else
        {
            sql_param_query('UPDATE sb_export_import_templates SET ?a WHERE seit_id=?d', $row, $id);
        }
    }

    public function getTemplatesList($ident)
    {
        $result = array();

        $res = sql_param_assoc('SELECT seit_id, seit_title FROM sb_export_import_templates WHERE seit_plugin_ident=?s AND seit_export <> ""', $ident);
        if($res)
        {
            foreach($res as $row)
            {
                $result[$row['seit_id']] = $row['seit_title'];
            }
        }
        return $result;
    }

    public function export($ident, $tpl_id, $cat_id, $subcat=false)
    {
        $this->ident = $ident;
        $res = sql_param_assoc('SELECT seit_export FROM sb_export_import_templates WHERE seit_id=?d AND seit_export <> ""', $tpl_id);
        if(!$res)
        {
            sb_add_system_message(sprintf(PL_EXPORT_ERROR_NO_TPL_FOUND, $tpl_id), SB_MSG_WARNING);
            $this->hasError = true;
            return;
        }

        $this->xml = simplexml_load_string($res[0]['seit_export']);
        $type = $this->xml->xpath('export_type');

        if('pl_polls_results' == $this->ident)
        {
            if('csv' == (string)$type[0])
            {
                $this->exportPollsResultsCSV();
            }
            elseif('sb_xml' == (string)$type[0])
            {
                $this->exportPollsResultsSBXML();
            }
            return;
        }

        switch ((string)$type[0])
        {
            case 'csv':
                $this->exportCSV($cat_id, $subcat);
                break;
            case 'sb_xml':
                $this->exportSBXML($cat_id, $subcat);
                break;
        }
    }

    private function exportPollsResultsCSV()
    {
        $pl_info = $_SESSION['sbPlugins']->getExportImportInfo();
        $pl_info = $pl_info['pl_polls_results'];

        if(!isset($_POST['elem_id']) || intval($_POST['elem_id']) == 0)
        {
            return;
        }

        $file = '/cms/tmp/'.mktime().'.csv';
        $filename_output = 'pl_polls_results.csv';

        $GLOBALS['sbVfs']->fopen($file, 'a+');

        //Вытаскиваем вопрос и записываем его первой строкой в файле экспорта
        $res = sql_param_query('SELECT sp_question FROM sb_polls WHERE sp_id=?d', $_POST['elem_id']);
        if(!$res)
        {
            return;
        }

        $str = $this->dataToCsv($res[0][0]).';';
        $str = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $str);
        $GLOBALS['sbVfs']->fwrite($str."\r\n");

        $fields_xml = $this->xml->xpath('elems_fields/field');
        $fields = array();
        if(!empty($fields_xml))
        {
            foreach($fields_xml as $item)
            {
                $fields[] = (string)$item['name'];
            }
        }

        if(empty($fields))
        {
            sb_add_system_message(PL_EXPORT_EDIT_NO_CHECKED_FIELDS, SB_MSG_WARNING);
            $this->hasError = true;
        }

        if(!$this->hasError && isset($_POST['details']) && $_POST['details'] == 1)
        {
            //Формируем строку с заголовками полей
            $str = $this->dataToCsv('').';';

            foreach($fields as $cell)
            {
                $str .= $this->dataToCsv($pl_info['fields'][$cell]['title']).';';
            }
            $str = sb_substr($str, 0, -1);
            $str = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $str);
            $GLOBALS['sbVfs']->fwrite($str."\r\n");

            //Детальный отчет
            $res = sql_param_assoc('SELECT sb_polls_results.*, spo_name, spo_id FROM sb_polls_results, sb_polls_options
                WHERE spo_poll_id = ?d AND spr_option_id=spo_id ORDER BY spo_id asc, spr_date asc', $_POST['elem_id']);

            if($res)
            {
                $last = 0;
                foreach($res as $row)
                {
                    if($row['spo_id'] == $last)
                    {
                        $str = ';';
                    }
                    else
                    {
                        $str = $this->dataToCsv($row['spo_name']).';';
                        $last = $row['spo_id'];
                    }

                    foreach($fields as $cell)
                    {
                        if('spr_date' == $cell)
                        {
                            $str .= $this->dataToCsv(sb_date('d.m.Y H:i', $row[$cell])).';';
                        }
                        else
                        {
                            $str .= $this->dataToCsv($row[$cell]).';';
                        }
                    }
                    $str = sb_substr($str, 0, -1);
                    $str = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $str);
                    $GLOBALS['sbVfs']->fwrite($str."\r\n");
                }
            }
        }
        elseif(!$this->hasError)
        {
            //Если отчет не детализированный, то игнорируем перечень полей и просто подсчитываем количество ответов
            //для каждого варианта

            $str = '';
            $str1 = '';
            $res = sql_param_assoc('SELECT count(*) as cnt, spo_name FROM sb_polls_results, sb_polls_options
                WHERE spo_poll_id = ?d AND spr_option_id=spo_id GROUP BY spo_id ORDER BY spo_id asc, spr_date asc', $_POST['elem_id']);
            if($res)
            {
                foreach($res as $row)
                {
                    $str .= $this->dataToCsv($row['spo_name']).';';
                    $str1 .= $row['cnt'].';';
                }
                $str = sb_substr($str, 0, -1);
                $str = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $str);
                $str1 = sb_substr($str1, 0, -1);
                $GLOBALS['sbVfs']->fwrite($str."\r\n".$str1."\r\n");
            }
        }

        $GLOBALS['sbVfs']->fclose();
        if(!$this->hasError)
        {
            header ("Content-Type: text/comma-separated-values; charset=windows-1251");
            header ("Accept-Ranges: bytes");
            header ("Content-Length: ".$GLOBALS['sbVfs']->filesize($file));
            header ("Content-Disposition: attachment; filename=".$filename_output);
            readfile(SB_BASEDIR . $file);
        }
        $GLOBALS['sbVfs']->delete($file);
        exit();
    }

    private function exportPollsResultsSBXML()
    {
        $filename_output = 'pl_polls_results.xml';

        $exportXML = simplexml_load_string('<sb_plugins></sb_plugins>');

        $exportXML->addChild('sb_plugin')->addAttribute('p_id', 'pl_polls_results');

        $quest = $exportXML->addChild('sb_poll');
        //Вытаскиваем вопрос
        $res = sql_param_query('SELECT sp_question FROM sb_polls WHERE sp_id=?d', $_POST['elem_id']);
        if($res)
        {
            $node = dom_import_simplexml($quest);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($res[0][0]));
        }
        $quest->addAttribute('id', intval($_POST['elem_id']));

        //Выбираем поля, которые надо экспортировать
        $fields_xml = $this->xml->xpath('elems_fields/field');
        $fields = array();
        if(!empty($fields_xml))
        {
            foreach($fields_xml as $item)
            {
                $fields[] = (string)$item['name'];
            }
        }

        if(empty($fields))
        {
            sb_add_system_message(PL_EXPORT_EDIT_NO_CHECKED_FIELDS, SB_MSG_WARNING);
            $this->hasError = true;
        }

        if(!$this->hasError && isset($_POST['details']) && $_POST['details'] == 1)
        {
            //Детальный отчет
            $res = sql_param_assoc('SELECT sb_polls_results.*, spo_name, spo_id FROM sb_polls_results, sb_polls_options
                WHERE spo_poll_id = ?d AND spr_option_id=spo_id ORDER BY spo_id asc, spr_date asc', $_POST['elem_id']);

            if($res)
            {
                $last = 0;
                foreach($res as $row)
                {
                    if($row['spo_id'] != $last)
                    {
                        $option = $exportXML->addChild('sb_option');
                        $option->addAttribute('id', $row['spo_id']);
                        $option->addAttribute('text', $row['spo_name']);
                        $last = $row['spo_id'];
                    }

                    $field = $option->addChild('sb_field');

                    foreach($fields as $cell)
                    {
                        $field->addAttribute($cell, $row[$cell]);
                    }
                }
            }
        }
        elseif(!$this->hasError)
        {
            //Если отчет не детализированный, то игнорируем перечень полей и просто подсчитываем количество ответов
            //для каждого варианта

            $res = sql_param_assoc('SELECT count(*) as cnt, spo_name, spo_id FROM sb_polls_results, sb_polls_options
                WHERE spo_poll_id = ?d AND spr_option_id=spo_id GROUP BY spo_id ORDER BY spo_id asc, spr_date asc', $_POST['elem_id']);
            if($res)
            {
                foreach($res as $row)
                {
                    $option = $exportXML->addChild('sb_option');
                    $option->addAttribute('id', $row['spo_id']);
                    $option->addAttribute('text', $row['spo_name']);
                    $option->addAttribute('count', $row['cnt']);
                }
            }
        }

        if(!$this->hasError)
        {
            header ("Content-Type: Content-Type: text/xml; charset=utf-8");
            header ("Accept-Ranges: bytes");
            header ("Content-Length: ".  strlen($exportXML->asXML()));
            header ("Content-Disposition: attachment; filename=".$filename_output);
            echo $exportXML->asXML();
        }
        return;
    }

    private function createSpravFields($categs = false)
    {
        $fields = ($categs)? $this->categsFields : $this->elemsFields;

        $sprav_options = array();
        $sprav_options['name'] = PL_EXPORT_EDIT_EXPORT_NAME;
        $sprav_options['id'] = PL_EXPORT_EDIT_EXPORT_ID;
        if(!$categs)
        {
            //  справочники элементов
            switch ($this->ident)
            {
                case 'pl_site_users':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[su_status][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_SU_STATUS, $fld, '', '', 'id="sprav_su_status"');

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[su_mail_lang][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_SU_MAIL_LNG, $fld, '', '', 'id="sprav_su_mail_lang"');

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[su_mail_status][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_SU_MAIL_STS, $fld, '', '', 'id="sprav_su_mail_status"');

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[su_mail_subscription][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_SU_MAIL_SUBSCR, $fld, '', '', 'id="sprav_su_mail_subscription"');

                    break;
                case 'pl_maillist':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[m_conf_format][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_FORMAT_MESS, $fld, '', '', 'id="sprav_m_conf_format"');
                    break;

                case 'pl_menu':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[m_target][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_TARGET, $fld, '', '', 'id="sprav_m_target"');
                    break;
            }
        }
        else
        {
            switch ($this->ident)
            {
                case 'pl_faq':
                case 'pl_imagelib':
                case 'pl_news':
                case 'pl_plugin_maker':
                case 'pl_services_rutube':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[moderates_list][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_MOD_LIST, $fld, '', '', 'id="sprav_moderates_list"');
                    break;
                case 'pl_maillist':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[cat_m_conf_format][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_FORMAT_MESS_CAT, $fld, '', '', 'id="sprav_cat_m_conf_format"');
                    break;

                case 'pl_site_users':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[cat_user_lang][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_SU_CAT_LNG, $fld, '', '', 'id="sprav_cat_user_lang"');

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[cat_status][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_SU_CAT_STS, $fld, '', '', 'id="sprav_cat_status"');

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[moderates_list][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_MOD_LIST, $fld, '', '', 'id="sprav_moderates_list"');
                    break;

                case 'pl_users':
                    $delim = true;
                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[ur_plugin_rights][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_PL_RIGHTS, $fld, '', '', 'id="sprav_ur_plugin_rights"');

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[ur_workflow_rights][]', '', 'multiple="multiple"');
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING_WF_RIGHTS, $fld, '', '', 'id="sprav_ur_workflow_rights"');
                    break;
            }
        }

        $i = 1;
        $tr_name = $categs? 'sprav_c_user_f_' : 'sprav_user_f_';
        if($fields !== null)
        {
            $user_flds = array();

            foreach ($fields as $value)
            {
                $user_flds[] = 'user_f_'.$value['id'];
            }

            $f_rights = $_SESSION['sbAuth']->getFieldRight($this->ident, $user_flds, array('view', 'edit'), false, $fields);

            foreach ($fields as $value)
            {
                if (isset($value['sql']) && $value ['sql'] == 1 && ($value ['type'] == 'select_sprav' || $value ['type'] == 'radio_sprav' || $value ['type'] == 'link_sprav' || $value ['type'] == 'multiselect_sprav' || $value ['type'] == 'checkbox_sprav' ||
                        $value ['type'] == 'select_plugin' || $value ['type'] == 'radio_plugin' || $value ['type'] == 'checkbox_plugin' || $value ['type'] == 'multiselect_plugin' || $value ['type'] == 'elems_plugin' || $value ['type'] == 'link_plugin')
                    && (isset($f_rights['user_f_' . $value['id']]['view']) && $f_rights['user_f_' . $value['id']]['view'] == 1 ||
                        isset($f_rights['user_f_' . $value['id']]['edit']) && $f_rights['user_f_' . $value['id']]['edit'] == 1))
                {
                    $delim = true;
                    if ($value['type'] == 'select_sprav' || $value['type'] == 'radio_sprav' || $value ['type'] == 'link_sprav' || $value ['type'] == 'multiselect_sprav' || $value ['type'] == 'checkbox_sprav')
                    {
                        $sprav_options = array();
                        $sprav_options['name'] = PL_EXPORT_EDIT_EXPORT_NAME;
                        $sprav_options['id'] = PL_EXPORT_EDIT_EXPORT_ID;
                        $sprav_options['descr_1'] = PL_EXPORT_EDIT_EXPORT_DESCR_1;
                        $sprav_options['descr_2'] = PL_EXPORT_EDIT_EXPORT_DESCR_2;
                        $sprav_options['descr_3'] = PL_EXPORT_EDIT_EXPORT_DESCR_3;
                    }
                    elseif ($value ['type'] == 'select_plugin' || $value ['type'] == 'radio_plugin' || $value ['type'] == 'checkbox_plugin' || $value ['type'] == 'multiselect_plugin' || $value ['type'] == 'elems_plugin' || $value ['type'] == 'link_plugin')
                    {
                        if (isset($value['settings']['ident']))
                        {
                            //  опции элементов справочников для пользовательских полей связывающие элементы.
                            $sprav_options = array();
                            $sprav_options[$value['settings']['ident'] . '__name'] = PL_EXPORT_EDIT_EXPORT_NAME;
                            $sprav_options[$value['settings']['ident'] . '__id'] = PL_EXPORT_EDIT_EXPORT_ID;
                        }
                    }

                    $fld = new sbLayoutSelect($sprav_options, ($categs ? 'cat_' : '').'sprav_setting[' . $value ['id'] . '][]', '', 'multiple="multiple"');
                    if($this->xml !== null)
                    {
                        $xml_fields = $this->xml->xpath(($categs? 'cat_sprav' : 'elems_sprav').'/item[@fld_id='.$value ['id'].']');
                        if(count($xml_fields) > 0)
                        {
                            $fld->mSelOptions = array();
                            $plugin = $xml_fields[0]['plugin'];
                            $items = $xml_fields[0]->xpath('sprav_field');
                            foreach($items as $item)
                            {
                                $fld->mSelOptions[] = ($plugin[0] == 'pl_sprav')? (string)$item : $plugin[0].'__'.(string)$item;
                            }
                        }
                    }
                    $this->layout->addField(PL_EXPORT_EDIT_SPRAV_SETTING . '"' . $value['title'] . '"', $fld, '', '', 'id="'.$tr_name.$i++.'"');
                }
            }
        }
    }

    /** Добавляет поля "информация о заказе" (кол-во позиций, кол-во товаров, сумма по заказу, сумма со скидкой) */
    private function createOrderFields()
    {
        if (isset($this->orderFields) && is_array($this->orderFields)) {
            $delim = true;
            $order_options = $this->orderFields;
            $fld = new sbLayoutSelect($order_options, 'order_setting[]', '', 'multiple="multiple"');
            if($this->xml !== null)
            {
                $xml_fields = $this->xml->xpath('elems_order');
                if(count($xml_fields) > 0)
                {
                    $fld->mSelOptions = array();
                    $items = $xml_fields[0]->xpath('order_field');
                    foreach($items as $item)
                    {
                        $fld->mSelOptions[] = (string)$item;
                    }
                }
            }
            $this->layout->addField(PL_EXPORT_EDIT_EXPORT_ORDER_SUMMARY_SETTING, $fld, '', '', 'id="order_summary"');
        }
    }

    private function createElementsFields($fields)
    {
        $options = array();
        foreach($fields as $key => $value)
        {
            $options[$key] = $value['title'];
        }

        // Пользовательские поля элементов (user_f_)
        if($this->elemsFields !== null)
        {
            $user_flds = array();

            foreach ($this->elemsFields as $value)
            {
                $user_flds[] = 'user_f_'.$value['id'];
            }

            $f_rights = $_SESSION['sbAuth']->getFieldRight($this->ident, $user_flds, array('view', 'edit'), false, $this->elemsFields);

            foreach ($this->elemsFields as $value)
            {
                //  проверяем права на поля.
                if(isset($value['sql']) && $value ['sql'] == 1 &&
                    (isset($f_rights['user_f_'.$value['id']]) && ($f_rights['user_f_'.$value['id']]['view'] == 1 || $f_rights['user_f_'.$value['id']]['edit'] == 1)
                        || !isset($f_rights['user_f_'.$value['id']])))
                {
                    $options['user_f_'.$value['id']] = $value ['title'];
                }
            }
        }

        $fld = new sbLayoutSelect($options, '', 'export_fields', 'ondblclick="sbAddFields(this, sbGetE(\'fields\'));" multiple="multiple" size="10" style="width:100%;height:200px;"');
        if (strpos($this->ident, 'pl_plugin_') !== false && $this->xml === null)
        {
            unset($options['p_price1']);
            unset($options['p_price2']);
            unset($options['p_price3']);
            unset($options['p_price4']);
            unset($options['p_price5']);
            unset($options['p_ext_id']);
            unset($options['p_order']);
        }

        if(empty($options))
        {
            $options[] = '';
        }
        elseif($this->xml !== null)
        {
            $xml_fields = $this->xml->xpath('elems_fields/field');
            $tmp = array();
            if(count($xml_fields) > 0)
            {
                foreach($xml_fields as $field)
                {
                    $tmp[] = (string)$field['name'];
                }
            }

            if(!empty($tmp))
            {
                $result = array();
                foreach($tmp as $key)
                {
                    if(isset($options[$key]))
                    {
                        $result[$key] = $options[$key];
                    }
                }
                $options = $result;
            }
        }

        $fld1 = new sbLayoutSelect($options, 'fields[]', 'fields', 'ondblclick="sbDelFields(this);" multiple="multiple" size="10" style="width:100%;height:200px;"');
        $html = '<table style="width:100%;" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:8px;"><b>'.PL_EXPORT_EDIT_ADD_ALL_FIELDS.'</b></td>
                <td></td>
                <td style="padding:8px;"><b>'.PL_EXPORT_EDIT_ADD_EXPORT_FIELDS.'</b></td>
            </tr>
            <tr>
                <td style="text-align:right;width:50%;">'.$fld->getField().'</td>
                <td style="text-align:center;">
                    <div style="width:30px;">
                        <img title="'.KERNEL_ADD.'" onclick="sbAddFields(sbGetE(\'export_fields\'), sbGetE(\'fields\'));" src="'.SB_CMS_IMG_URL.'/next.gif" style="margin-bottom:20px;">
                        <img title="'.KERNEL_DELETE.'" onclick="sbDelFields(sbGetE(\'fields\'));" src="'.SB_CMS_IMG_URL.'/previous.gif" style="margin-top:20px;">
                    </div>
                </td>
                <td style="text-align:left;width:50%;">'.$fld1->getField().'</td>
            </tr>
            <tr>
                <td style="padding:8px;"><a href="javascript:void(0);" onclick="sbAddFields(sbGetE(\'export_fields\'), sbGetE(\'fields\'), true);">'.PL_EXPORT_EDIT_ADD_ALL_FIELDS_LABEL.'</a></td>
                <td></td>
                <td style="padding:8px;"><a href="javascript:void(0);" onclick="sbDelFields(sbGetE(\'fields\'), true);">'.PL_EXPORT_EDIT_ADD_DEL_FIELDS_LABEL.'</a></td>
            </tr>
        </table>';

        $this->layout->addField('', new sbLayoutHTML($html, true), '', 'id="els_fields_td"', 'id="els_fields"');
    }

    private function createCategsFields()
    {
        $cat_fields_ = array();
        if ($this->categsFields)
        {
            foreach ($this->categsFields as $value)
            {
                $cat_fields_[] = 'user_f_' . $value['id'];
            }
        }

        //  строим текстарии с полями разделов.
        $options = $this->getCatFieldsTitles($this->categsFields, $cat_fields_);
        $fld = new sbLayoutSelect($options, '', 'cat_export_fields', 'ondblclick="sbAddFields(this, sbGetE(\'cat_fields\'));" multiple="multiple" size="10" style="width:100%;height:200px;"');

        if ($this->xml === null)
        {
            unset($options['cat_fields']);
            unset($options['cat_ident']);
            unset($options['cat_left']);
            unset($options['cat_right']);
            unset($options['cat_ext_id']);
            unset($options['cat_rubrik']);
            unset($options['answer_temp_id']);
            unset($options['moderates_list']);

            if ($this->ident == 'pl_polls_results')
            {
                unset($options['cat_level']);
                unset($options['cat_closed']);
                unset($options['cat_rights']);
                unset($options['cat_url']);
                unset($options['cat_rights']);
            }
        }
        else
        {
            $xml_fields = $this->xml->xpath('cat_fields/field');
            $tmp = array();
            if(count($xml_fields) > 0)
            {
                foreach($xml_fields as $field)
                {
                    $tmp[] = (string)$field['name'];
                }
            }

            if(!empty($tmp))
            {
                $result = array();
                foreach($tmp as $key)
                {
                    if(isset($options[$key]))
                    {
                        $result[$key] = $options[$key];
                    }
                }
                $options = $result;
            }
        }

        if (empty($options))
        {
            $options[] = '';
        }

        $fld1 = new sbLayoutSelect($options, 'cat_fields[]', 'cat_fields', 'ondblclick="sbDelFields(this);" multiple="multiple" size="10" style="width:100%;height:200px;"');
        $html = '<table style="width:100%;" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:8px;"><b>' . PL_EXPORT_EDIT_ADD_ALL_FIELDS . '</b></td>
                    <td></td>
                    <td style="padding:8px;"><b>' . PL_EXPORT_EDIT_ADD_EXPORT_CAT_FIELDS . '</b></td>
                </tr>
                <tr>
                    <td style="text-align:right;width:50%;">' . $fld->getField() . '</td>
                    <td style="text-align:center;">
                        <div style="width:30px;">
                            <img title="' . KERNEL_ADD . '" onclick="sbAddFields(sbGetE(\'cat_export_fields\'), sbGetE(\'cat_fields\'));" src="' . SB_CMS_IMG_URL . '/next.gif" style="margin-bottom:20px;">
                            <img title="' . KERNEL_DELETE . '" onclick="sbDelFields(sbGetE(\'cat_fields\'));" src="' . SB_CMS_IMG_URL . '/previous.gif" style="margin-top:20px;">
                        </div>
                    </td>
                    <td style="text-align:left;width:50%;">' . $fld1->getField() . '</td>
                </tr>
                <tr>
                    <td style="padding:8px;"><a href="javascript:void(0);" onclick="sbAddFields(sbGetE(\'cat_export_fields\'), sbGetE(\'cat_fields\'), true);">' . PL_EXPORT_EDIT_ADD_ALL_CAT_FIELDS_LABEL . '</a></td>
                    <td></td>
                    <td style="padding:8px;"><a href="javascript:void(0);" onclick="sbDelFields(sbGetE(\'cat_fields\'), true);">' . PL_EXPORT_EDIT_ADD_DEL_CAT_FIELDS_LABEL . '</a></td>
                </tr>
            </table>';

        $this->layout->addField('', new sbLayoutHTML($html, true), '', 'id="categs_fields_td"', 'id="categs_fields"');
    }

    private function getCatFieldsTitles($categs_fields, $fields)
    {
        $options = array();
        $options['cat_id'] = PL_EXPORT_CAT_ID_LABEL;
        $options['cat_ident'] = PL_EXPORT_CAT_IDENT_LABEL;
        $options['cat_title'] = PL_EXPORT_CAT_TITLE_LABEL;
        $options['cat_left'] = PL_EXPORT_CAT_LEFT_LABEL;
        $options['cat_right'] = PL_EXPORT_CAT_RIGHT_LABEL;
        $options['cat_level'] = PL_EXPORT_CAT_LEVEL_LABEL;
        $options['cat_ext_id'] = PL_EXPORT_CAT_EXT_ID_LABEL;
        $options['cat_rubrik'] = PL_EXPORT_CAT_RUBRIK_LABEL;
        $options['cat_closed'] = PL_EXPORT_CAT_CLOSED_LABEL;
        $options['cat_rights'] = PL_EXPORT_CAT_RIGHTS_LABEL;
        $options['cat_fields'] = PL_EXPORT_CAT_FIELDS_LABEL;
        $options['cat_url'] = PL_EXPORT_CAT_URL_LABEL;
        $options['parent_cat_id'] = PL_EXPORT_CAT_PARENT_ID;

        if (isset($_SESSION ['sbPlugins']->mFieldsInfo[$this->ident]['cat_fields']) && count($_SESSION['sbPlugins']->mFieldsInfo[$this->ident]['cat_fields']) > 0)
        {
            foreach ($_SESSION['sbPlugins']->mFieldsInfo[$this->ident]['cat_fields'] as $key => $value)
            {
                $options[$key] = $value['title'];
            }
        }

        //  вытаскиваем пользовательские поля раздела
        if ($categs_fields)
        {
            $f_rights = $_SESSION['sbAuth']->getFieldRight($this->ident, $fields, array('view', 'edit'), true, $categs_fields);
            foreach ($categs_fields as $value)
            {
                if (isset($value['sql']) && $value['sql'] == 1 &&
                    isset($f_rights['user_f_' . $value['id']]) && ($f_rights['user_f_' . $value['id']]['view'] == 1 || $f_rights['user_f_' . $value['id']]['edit'] == 1)
                    || !isset($f_rights['user_f_' . $value['id']]))
                {
                    $options['user_f_' . $value['id']] = $value['title'];
                }
            }
        }
        return $options;
    }

    private function getElementsFieldsTitles($elem_fields)
    {
        $plugin_fields = $_SESSION['sbPlugins']->mFieldsInfo[$this->ident]['fields'];

        $options = array(
            'cat_id' => PL_EXPORT_CAT_ID_LABEL
        );
        foreach($plugin_fields as $key => $value)
        {
            $options[$key] = $value['title'];
        }

        //if($this->elemsFields !== null)
        //{
        // создаем массив с названиями пользовательских полей и получаем права на них
        $user_flds = array();
        foreach ($elem_fields as $value)
        {
            $user_flds[] = 'user_f_'.$value['id'];
        }
        $f_rights = $_SESSION['sbAuth']->getFieldRight($this->ident, $user_flds, array('view', 'edit'), false, $elem_fields);
        unset($user_flds);
        // проверяем права на поля.
        foreach($elem_fields as $value)
        {
            if(isset($value['sql']) && $value ['sql'] == 1 &&
                (isset($f_rights['user_f_'.$value['id']]) && ($f_rights['user_f_'.$value['id']]['view'] == 1 || $f_rights['user_f_'.$value['id']]['edit'] == 1)
                    || !isset($f_rights['user_f_'.$value['id']])))
            {
                $options['user_f_'.$value['id']] = $value ['title'];
            }
        }
        //}
        return $options;
    }

    private function exportCSV($cat_id, $subcat)
    {
        $cat_ids = array();
        $tmp = explode('^',$cat_id);
        $this->pl_info = $pl_info = $_SESSION['sbPlugins']->getPluginsInfo();

        foreach ($tmp as $cat)
        {
            if (fCategs_Check_Rights($cat)) {
                $cat_ids[] = $cat;
            }
        }

        //Если надо экспортировать вложенные разжелы, то вытаскиваем их идентификаторы
        if($subcat)
        {
            $res = sql_param_assoc('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id IN (?a)', $cat_ids);
            if($res)
            {
                foreach ($res as $parent_row)
                {
                    $res1 = sql_param_assoc('SELECT cat_id FROM sb_categs WHERE cat_ident=?s AND cat_left > ?d AND cat_right < ?d', $this->ident, $parent_row['cat_left'], $parent_row['cat_right']);
                    if ($res1)
                    {
                        foreach ($res1 as $row)
                        {
                            if (fCategs_Check_Rights($row['cat_id']))
                            {
                                $cat_ids[] = $row['cat_id'];
                            }
                        }
                    }
                }
            }
        }

        if(empty($cat_ids))
        {
            sb_add_system_message(PL_EXPORT_NO_CAT_RIGHTS_ERROR, SB_MSG_WARNING);
            $this->hasError = true;
            return;
        }

        //Определяем таблицу элементов
        $table = '';
        $pkey = '';
        if($this->ident == 'pl_polls_results')
        {
            $table = 'sb_polls_results';
            $pkey = 'spr_id';
        }
        elseif(sb_stripos($this->ident, 'pl_plugin_') !== false)
        {
            $pl_id = sb_str_replace('pl_plugin_', '', $this->ident);
            $table = 'sb_plugins_'.$pl_id;
            $pkey = 'p_id';
        }
        else
        {
            if(isset($pl_info[$this->ident]['meta_data']) && isset($pl_info[$this->ident]['meta_data']['table']) && isset($pl_info[$this->ident]['meta_data']['id']))
            {
                $table = $pl_info[$this->ident]['meta_data']['table'];
                $pkey = $pl_info[$this->ident]['meta_data']['id'];
            }
        }

        if(!$table || !$pkey)
        {
            $this->hasError = true;
            sb_add_system_message(PL_EXPORT_NO_MODULE_TABLE_ERROR, SB_MSG_ERROR);
            return;
        }

        $elems_fields = array(); //Поля элементов
        $cat_fields = array(); //Поля разделов

        $tmp = $this->xml->xpath('elems_fields/field');
        if(count($tmp) > 0)
        {
            $elems_fields[] = "$table.$pkey";
            $elems_fields[] = 'cat_id';
            foreach($tmp as $field)
            {
                $elems_fields[] = $table.'.'.(string)$field['name'];
            }
        }
        $elems_fields = array_unique($elems_fields);

        $tmp = $this->xml->xpath('cat_fields/field');
        if(count($tmp) > 0)
        {
            $cat_fields = array_merge($cat_fields, array('cat_id', 'parent_cat_id'));
            foreach($tmp as $field)
            {
                $cat_fields[] = (string)$field['name'];
            }
        }
        $cat_fields = array_unique($cat_fields);

        //Отсеиваем запрещенные поля разделов и элементов FIXME
        $elems_fields = $this->getAllowedFields($elems_fields);
        $cat_fields = $this->getAllowedFields($cat_fields, true);

        // Инициализируем поля за информации о заказе
        $this->initOrderFields(true);

        //Формируем массивы с названиями полей разделов и элементов
        $cat_titles = array();

        $res = sql_param_query('SELECT pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?s', $this->ident);
        if($res)
        {
            $tmp = ($res[0][0] != '')? unserialize($res[0][0]) : array();
            $cat_titles = $this->getCatFieldsTitles($tmp, $cat_fields);
        }

        $elem_titles = array();
        $flds_type = array();
        $res = sql_param_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident=?s', $this->ident);
        if($res && trim($res[0][0]) !== '')
        {
            //$tmp = ($res[0][0] != '')? unserialize($res[0][0]) : array();
            $fld_info = unserialize($res[0][0]);
            $elem_titles = $this->getElementsFieldsTitles($fld_info);
            foreach($fld_info as $fld)
            {
                $flds_type['user_f_'.$fld['id']] = $fld['type'];
            }
            unset($fld_info);
        }

        $elems_data = null;
        $cat_data = null;

        if(!empty($elems_fields))
        {
            $tmp = $elems_fields;
            unset($tmp[1]);

            $filter_flds = $this->getFilterFields($flds_type, $elems_fields, $table);

            $elems_data = sql_param_assoc('SELECT '.implode(',', $tmp).', sbctl.link_cat_id'.' FROM '.$table.', sb_catlinks sbctl WHERE sbctl.link_cat_id IN (?a) AND '.$table.'.'.$pkey.'=sbctl.link_el_id'.$filter_flds, $cat_ids);
        }

        if(!empty($cat_fields))
        {
            //Отделяем мух от котлет
            $tmp_standart = array('cat_fields');
            $tmp_user = array();
            foreach($cat_fields as $field)
            {
                if(sb_stripos($field, 'cat_') === 0)
                {
                    $tmp_standart[] = $field;
                }
                else
                {
                    $tmp_user[] = $field;
                }
            }

            $cat_data = sql_param_assoc('SELECT '.implode(',', $tmp_standart).' FROM sb_categs WHERE cat_id IN(?a)', $cat_ids);
            if($cat_data)
            {
                //Формируем массив с настройками связанных справочников и модулей.
                //Если разделы не экспортируются, то нет смысла экспортировать и связанные плагины

                $sprav = $this->getSpravStruct(true);

                foreach($cat_data as $key=>$row)
                {
                    $tmp_cat_fields = ($row['cat_fields'] != '')? unserialize($row['cat_fields']): array();
                    foreach($tmp_user as $user_field)
                    {
                        if(isset($tmp_cat_fields[$user_field]))
                        {
                            $cat_data[$key][$user_field] = $tmp_cat_fields[$user_field];
                        }
                        else
                        {
                            $cat_data[$key][$user_field] = '';
                        }
                    }
                    unset($cat_data[$key]['cat_fields']);
                }
            }
            unset($tmp_standart);
            unset($tmp_user);
        }

        $file = '/cms/tmp/'.mktime().'.csv';
        $filename_output = $this->xml->xpath('pl_ident');
        $filename_output = (string)$filename_output[0].'.csv';

        $GLOBALS['sbVfs']->fopen($file, 'a+');
        //$count = max(array(sizeof($cat_fields), sizeof($elems_fields)));

        $show_titles = $this->xml->xpath('show_titles');
        $show_titles = (int)$show_titles[0];
        //Экспорт разделов
        if(!empty($cat_fields) && !empty($cat_data))
        {
            //Формируем строку заголовков для разделов
            if(1 == $show_titles)
            {
                $this->showTitlesCSV($cat_fields, $cat_titles, $sprav);
            }

            //Формируем строки с данными разделов
            $this->createDataCSV($cat_fields, $cat_data, $sprav, true);
        }

        //Вставляем пустую строку как разделитель данных разделов и элементов
        $str = $this->dataToCsv('').';';
        $GLOBALS['sbVfs']->fwrite($str."\r\n");


        //Экспорт данных элементов
        if(!empty($elems_fields) && !empty($elems_data))
        {
            //Формируем данные связанных плагинов для элементов
            $sprav = $this->getSpravStruct();

            if(1 == $show_titles)
            {
                $this->showTitlesCSV($elems_fields, $elem_titles, $sprav);
            }

            //Формируем строки с данными элементов
            $this->createDataCSV($elems_fields, $elems_data, $sprav);
        }

        $GLOBALS['sbVfs']->fclose();
        header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
        header('Content-Description: File Transfer');
        header("Content-Type: text/comma-separated-values; charset=windows-1251");
        header('Last-Modified: ' . gmdate('r', filemtime(SB_BASEDIR.$file)));

        header('ETag: ' . sprintf('%x-%x-%x', fileinode(SB_BASEDIR.$file), filesize(SB_BASEDIR.$file), filemtime(SB_BASEDIR.$file)));
        header("Content-Length: ".$GLOBALS['sbVfs']->filesize($file));
        header('Connection: close');

        header ("Content-Disposition: attachment; filename=".basename($filename_output));

        //echo file_get_contents(SB_BASEDIR.$file);
        readfile(SB_BASEDIR . $file);
        $GLOBALS['sbVfs']->delete($file);
        exit();
    }

    private function dataToCsv($val)
    {
        //  чтобы не ломать csv кладем символы (,)(")(;)(первод строки) в ковычки,
        //  но если симовол (;) обрамлен набором символов(!==!) то этот символ не трогаем. (!==!;!==!)
        if (preg_match('/[\,\"\n\r]/', $val) || !(preg_match('/(?<=\!\=\=\!)\;/', $val) && preg_match('/\;(?=\!\=\=\!)/', $val)))
        {
            $val = '"' . str_replace('"', '""', $val) . '"';
        }
        return $val;
    }

    private function createDataCSV($fields, $export_data, $sprav, $categs = false)
    {
        $flds_type = array();
        $res = sql_param_query('SELECT '.($categs ? 'pd_categs' : 'pd_fields').' FROM sb_plugins_data WHERE pd_plugin_ident=?', ($this->ident == 'pl_polls_results')? 'pl_polls' : $this->ident);
        if($res)
        {
            $fld_info = unserialize($res[0][0]);
            if(is_array($fld_info) && count($fld_info) > 0)
            {
                foreach($fld_info as $fld)
                {
                    $flds_type[$fld['id']] = $fld['type'];
                }
            }
            unset($fld_info);
        }
        unset($res);

        $links_two_columns = $this->xml->xpath('links_two_columns');
        $links_two_columns = (int)$links_two_columns[0];
        if (!empty($export_data))
        {
            foreach ($export_data as $data)
            {
                $str = '';
                foreach ($fields as $key)
                {
                    if(!$categs)
                    {
                        $key = preg_replace('/^\w+\.(.+)/', '$1', $key);
                    }
                    $id = sb_str_replace('user_f_', '', $key);
                    if (sb_stripos($key, 'user_f_') !== false)
                    {

                        if (isset($sprav[$id]) && isset($sprav[$id]['pkey']))
                        {
                            $res = false;
                            $result = array(array());
                            //для связанных справочников достает связанный раздел
                            if('link_sprav' == $sprav[$id]['type'])
                            {
                                $ids = explode(',', $data[$key]);
                                $catField = isset($sprav[$id]['fields']['id']) && $sprav[$id]['fields']['id'] === 's_id' ? 'c.cat_id' : 'c.cat_title';

                                if (!empty($ids[1])) {
                                    $res = sql_param_query('SELECT '.$catField.', ' . implode(', ', $sprav[$id]['fields']) . ' FROM sb_categs c, ' . $sprav[$id]['table'] . ' WHERE c.cat_id = ? AND ' . $sprav[$id]['pkey'] . ' =?', $ids[0], $ids[1]);
                                }
                            }
                            else
                            {
                                $res = sql_param_query('SELECT ' . implode(', ', $sprav[$id]['fields']) . ' FROM ' . $sprav[$id]['table'] . ' WHERE ' . $sprav[$id]['pkey'] . ' IN (?a)', explode(',', $data[$key]));
                            }

                            if (!empty($res))
                            {
                                //"Склеиваем" данные справочника
                                $res_count = count($res[0]);
                                for ($i = 0; $i < $res_count; $i++)
                                {
                                    foreach ($res as $row)
                                    {
                                        $result[0][$i] = (isset($result[0][$i]) ? $result[0][$i] . $row[$i]
                                                    : $row[$i]) . '^';
                                    }
                                    if('link_sprav' == $sprav[$id]['type'] && 0 == $links_two_columns)
                                    {
                                        $result[0][$i] = sb_substr($result[0][$i], 0, -1);
                                    }
                                    else
                                    {
                                        $result[0][$i] = $this->dataToCsv(sb_substr($result[0][$i], 0, -1));
                                    }
                                }
                            }
                            else
                            {
                                $null = $this->dataToCsv('');
                                $str .= implode(';', array_fill(0, count($sprav[$id]['fields']), $null)).';';
                            }

                            if (!empty($result[0]))
                            {
                                if('link_sprav' == $sprav[$id]['type'] && 0 == $links_two_columns)
                                {
                                    $str .= $this->dataToCsv(implode('|', $result[0])) . ';';
                                }
                                else
                                {
                                    $str .= implode(';', $result[0]) . ';';
                                }
                            }
                        }
                        else
                        {
                            //Проверяем, является ли поле датой
                            if(isset($flds_type[$id]) && $flds_type[$id] == 'date')
                            {
                                $data[$key] = sb_date('d.m.Y H:i', $data[$key]);
                            }
                            $str .= $this->dataToCsv($data[$key]) . ';';
                        }
                    }
                    elseif ($categs && 'parent_cat_id' == $key)
                    {
                        $res = sql_param_query('SELECT sc1.cat_id FROM sb_categs sc1 JOIN sb_categs sc2 on sc1.cat_ident = ?s
                                    WHERE sc1.cat_left < sc2.cat_left AND sc1.cat_right > sc2.cat_right
                                    AND sc1.cat_level = sc2.cat_level-1 AND sc2.cat_id=?d', $this->ident, $data['cat_id']);

                        $str .= ($res ? $res[0][0] : $this->dataToCsv('')) . ';';
                    }
                    elseif (!$categs && 'cat_id' == $key)
                    {
                        $str .= $this->dataToCsv($data['link_cat_id']).';';
                    }
                    elseif (array_key_exists($key, $data))
                    {
                        // если это дата
                        if(in_array($key, array(
                                'im_date', 'b_date', 'c_date', 'f_date', 'f_pub_start', 'f_pub_end',
                                'm_pub_start', 'm_pub_end', 'n_date', 'n_pub_start', 'n_pub_end', 'sp_date',
                                'p_pub_start', 'p_pub_end', 'sp_pub_start', 'sp_pub_end', 'ssr_pub_start', 'ssr_pub_end',
                                'su_reg_date', 'su_last_date', 'su_active_date', 'su_mail_date')) && $data[$key])
                        {
                            $str .= $this->dataToCsv( sb_date('d.m.Y H:i', $data[$key]) ) . ';';
                        }
                        // если это информация по заказу
                        elseif ($key == 'p_order' && isset($this->orderFields) && $data[$key])
                        {
                            $res = $this->getOrderDataCSV($data[$key]);
                            foreach ($this->orderFields as $k => $v) {
                                $str .= $this->dataToCsv($res[$k]) . ';';
                            }
                        }
                        else
                            $str .= $this->dataToCsv($data[$key]) . ';';
                    }
                }
                $str = sb_substr($str, 0, -1);
                $str = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $str);
                $GLOBALS['sbVfs']->fwrite($str . "\r\n");
            }
        }
    }

    private function showTitlesCSV($fields, $titles, $sprav)
    {
        $str = '';
        $links_two_columns = $this->xml->xpath('links_two_columns');
        $links_two_columns = (int)$links_two_columns[0];
        foreach ($fields as $key)
        {
            if(preg_match('/^\w+\.(.+)/', $key))
            {
                $key   = preg_replace('/^\w+\.(.+)/', '$1', $key);
            }

            $count = 0;
            if (sb_stripos($key, 'user_f_') !== false)
            {
                $id    = sb_str_replace('user_f_', '', $key);
                $count = (isset($sprav[$id]['fields'])) ? count($sprav[$id]['fields']) - 1
                    : 0;
                if(isset($sprav[$id]) && 'link_sprav' == $sprav[$id]['type'] && 1 == $links_two_columns)
                {
                    //TODO зачем нужно было считать длину массива, если всегда добавляется только одна колонка для каждого элемента?
                    //$str .= $this->dataToCsv($titles[$key]) . ';' . ($count > 0 ? implode('', array_fill(0, $count, ';')) : '');
                    $str .= $this->dataToCsv($titles[$key]) . ';' . ';';
                }
                else
                {
                    $str .= $this->dataToCsv($titles[$key]) . ';';
                }
            }
            // Если это поле данных заказа, и есть поля информации о заказе - выводим их вместо поля заказа
            elseif ($key == 'p_order' && $this->orderFields)
            {
                foreach ($this->orderFields as $key => $val) {
                    $str .= $this->dataToCsv($val) . ';';
                }
            }
            else
            {
                $str .= $this->dataToCsv($titles[$key]) . ';' . ($count > 0 ? implode('', array_fill(0, $count, ';'))
                        : '');
            }
        }

        $str   = sb_substr($str, 0, -1);
        $str   = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $str);
        $GLOBALS['sbVfs']->fwrite($str . "\r\n");
    }

    private function getSpravStruct($categs = false)
    {
        $sprav = array();
        if($categs)
        {
            $sprav_data = $this->xml->xpath('cat_sprav/item');
        }
        else
        {
            $sprav_data = $this->xml->xpath('elems_sprav/item');
        }

        if (count($sprav_data) > 0)
        {
            foreach ($sprav_data as $item)
            {
                $sprav[(int) $item['fld_id']] = array(
                    'plugin' => (string) $item['plugin'],
                    'type'   => (string) $item['sprav_type'],
                    'pkey'   => isset($this->pl_info[(string) $item['plugin']]['meta_data']['id'])
                            ? $this->pl_info[(string) $item['plugin']]['meta_data']['id']
                            : ''
                );

                if (isset($this->pl_info[(string) $item['plugin']]['meta_data']['table']))
                {
                    $sprav[(int) $item['fld_id']]['table'] = $this->pl_info[(string) $item['plugin']]['meta_data']['table'];
                }
                else
                {
                    $sprav[(int) $item['fld_id']]['table'] = $this->pl_info[(string) $item['plugin']]['table'];
                }

                $sprav[(int) $item['fld_id']]['fields'] = array();

                foreach ($item->sprav_field as $tmp)
                {
                    $tmp = (string) $tmp;
                    if ('id' == $tmp)
                    {
                        if (isset($this->pl_info[(string) $item['plugin']]['meta_data']))
                        {
                            $sprav[(int) $item['fld_id']]['fields']['id'] = $this->pl_info[(string) $item['plugin']]['meta_data']['id'];
                        }
                        else
                        {
                            $sprav[(int) $item['fld_id']]['fields']['id'] = $tmp;
                        }
                    }
                    elseif (sb_stripos($tmp, 'descr_') !== false)
                    {
                        if ('select_sprav' == (string) $item['sprav_type'] || 'multiselect_sprav' == (string) $item['sprav_type']
                            || 'radio_sprav' == (string) $item['sprav_type'] || 'checkbox_sprav' == (string) $item['sprav_type'])
                        {
                            $sprav[(int) $item['fld_id']]['fields'][$tmp] = sb_str_replace('descr_', 's_prop', $tmp);
                        }
                    }
                    elseif ('name' == $tmp)
                    {
                        if (isset($this->pl_info[(string) $item['plugin']]['meta_data']))
                        {
                            $sprav[(int) $item['fld_id']]['fields'][$tmp] = $this->pl_info[(string) $item['plugin']]['meta_data']['title'];
                        }
                        else
                        {
                            $sprav[(int) $item['fld_id']]['fields'][$tmp] = $tmp;
                        }
                    }
                }
            }
        }

        return $sprav;
    }

    private function exportSBXML($cat_id, $subcat)
    {
        $sb_xml = simplexml_load_string('<sb_plugins></sb_plugins>');
        $plugin_xml = $sb_xml->addChild('sb_plugin');
        $plugin_xml->addAttribute('p_id', $this->ident);

        $cat_ids = explode('^', $cat_id);
        $export_cats = array(); //Перечень идентификаторов экспортируемых разделов
        $rights = ''; //Часть SQL-запроса для фильтра по правам

        if (!$_SESSION['sbAuth']->isAdmin())
        {
            $user_groups = $_SESSION['sbAuth']->getUserGroups();
            $user_id     = $_SESSION['sbAuth']->getUserId();

            $rights = 'c.cat_rights LIKE "%u'.$user_id.'%" ';
            if(count($user_groups) > 0)
            {
                foreach($user_groups as $group)
                {
                    $rights .= "OR c.cat_rights LIKE '%g$group%' ";
                }
            }
            if($rights != '')
            {
                $rights = " AND ($rights) ";
            }
        }

        if($subcat)
        {
            $export_cats = sql_param_assoc('SELECT DISTINCT c.* FROM sb_categs AS c, sb_categs AS c2
                            WHERE c2.cat_left <= c.cat_left
                            AND c2.cat_right >= c.cat_right
                            AND c.cat_ident = ?s
                            AND c2.cat_ident = ?s
                            AND c2.cat_id IN (?a)
                            '.$rights.'
                            ORDER BY c.cat_left', $this->ident, $this->ident, $cat_ids);
        }
        else
        {
            $export_cats = sql_param_assoc('SELECT DISTINCT c.* FROM sb_categs AS c
                            WHERE c.cat_ident = ?s
                            AND c.cat_id IN (?a)
                            '.$rights.'
                            ORDER BY c.cat_left', $this->ident, $cat_ids);
        }

        //Формируем массив с экспортируемыми полями разделов
        $cat_fields = array();
        $tmp = $this->xml->xpath('cat_fields/field');
        foreach ($tmp as $item)
        {
            if('parent_cat_id' == (string) $item['name'])
            {
                continue; //Это поле необходимо только для CSV файла
            }
            $cat_fields[] = (string) $item['name'];
        }
        $cat_fields = $this->getAllowedFields($cat_fields, true);

        if(!empty($export_cats) && !empty($cat_fields))
        {
            $cur_level = PHP_INT_MAX; //Текущий уровень вложенности
            foreach($export_cats as $cat)
            {
                if($cat['cat_level'] < $cur_level) //Переход на уровень выше или начало цикла
                {
                    if(!isset($cur_xml)) //Начало цикла
                    {
                        $cur_xml = $plugin_xml->addChild('sb_cat');
                        //$cur_xml->addAttribute('id', $cat['cat_id']);
                    }
                    else
                    {
                        $parent = $cur_xml->xpath('ancestor::*'); //Получаем всех родителей ноды
                        $parent = $parent[$cat['cat_level']+1];
                        $cur_xml = $parent->addChild('sb_cat');
                    }
                }
                elseif($cat['cat_level'] == $cur_level) //Остаемся на том же уровне
                {
                    $parent = $cur_xml->xpath('parent::*'); //Получаем непосредственного родителя
                    $parent = $parent[0];
                    $cur_xml = $parent->addChild('sb_cat');
                }
                else //Углубляемся
                {
                    $cur_xml = $cur_xml->addChild('sb_cat');
                }

                //$cur_xml->addAttribute('id', $cat['cat_id']);
                $user_cat_fields = $cat['cat_fields'] != ''? unserialize($cat['cat_fields']) : array();
                foreach($cat_fields as $field)
                {
                    $fld = $cur_xml->addChild('sb_field');
                    $fld->addAttribute('name', $field);

                    if(isset($cat[$field]) && !empty($cat[$field]))
                    {
                        $node = dom_import_simplexml($fld);
                        $no = $node->ownerDocument;
                        $node->appendChild($no->createCDATASection($cat[$field]));
                    }
                    elseif(isset($user_cat_fields[$field]) && !empty($user_cat_fields[$field]))
                    {
                        $node = dom_import_simplexml($fld);
                        $no = $node->ownerDocument;
                        if(is_array($user_cat_fields[$field]))
                        {
                            $node->appendChild($no->createCDATASection(serialize($user_cat_fields[$field])));
                        }
                        else
                        {
                            $node->appendChild($no->createCDATASection($user_cat_fields[$field]));
                        }
                    }
                }

                $cur_xml = $this->addElemsSBXML($cur_xml, $cat['cat_id']);

                $cur_level = $cat['cat_level'];
            }
        }

        if(!$this->hasError)
        {
            $filename_output = $this->ident.'.xml';
            header ("Content-Type: Content-Type: text/xml; charset=utf-8");
            header ("Accept-Ranges: bytes");
            header ("Content-Length: ".  strlen($sb_xml->asXML()));
            header ("Content-Disposition: attachment; filename=".$filename_output);
            echo $sb_xml->asXML();
        }
        return;
    }

    private function addElemsSBXML($xml_node, $cat_id)
    {
        //Формируем список экспортируемых полей элементов
        if($this->elemsFields === null)
        {
            $tmp = $this->xml->xpath('elems_fields/field');
            $this->elemsFields = array();

            if(sizeof($tmp) > 0)
            {
                foreach($tmp as $item)
                {
                    $this->elemsFields[] = (string)$item['name'];
                }
            }
        }

        if(empty($this->elemsFields) || $this->elemsFields === null)
        {
            return $xml_node;
        }

        //Получаем метаданные таблицы плагина
        $this->pl_info = $_SESSION['sbPlugins']->getPluginsInfo();
        if(!isset($this->pl_info[$this->ident]['meta_data']))
        {
            return $xml_node;
        }

        $table = $this->pl_info[$this->ident]['meta_data']['table'];
        $pkey = $this->pl_info[$this->ident]['meta_data']['id'];

        //Выбираем элементы для текущего раздела
        $res = sql_param_assoc('SELECT pl.*, ctl.link_src_cat_id
                                FROM sb_catlinks AS ctl, '.$table.' AS pl
                                WHERE ctl.link_cat_id=?d AND pl.'.$pkey.'=ctl.link_el_id', $cat_id);

        if(!$res)
        {
            return $xml_node;
        }

        foreach($res as $row)
        {
            $elem = $xml_node->addChild('sb_elem');
            if(intval($row['link_src_cat_id']) > 0)
            {
                $elem->addChild('sb_link', intval($row['link_src_cat_id']));
            }

            foreach($this->elemsFields as $field)
            {
                $fld = $elem->addChild('sb_field');
                $fld->addAttribute('name', $field);
                if(array_key_exists($field, $row) && !is_null($row[$field]) && $row[$field] != '')
                {
                    $node = dom_import_simplexml($fld);
                    $no = $node->ownerDocument;
                    $node->appendChild($no->createCDATASection($row[$field]));
                }
            }
        }

        return $xml_node;
    }

    private function getAllowedFields($fields, $categs=false)
    {
        $result = array();
        //Читаем пользоввательские поля
        $res = sql_param_assoc('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident=?', ($this->ident == 'pl_polls_results')? 'pl_polls' : $this->ident);
        if($res)
        {
            if($categs)
            {
                $user_fields = unserialize($res[0]['pd_categs']);
            }
            else
            {
                $user_fields = unserialize($res[0]['pd_fields']);
            }

            //исключаем имя таблицы из элементов массива
            foreach($fields as $key => $val)
            {
                $fName = explode('.', $val);
                $fields[$key] = isset($fName[1]) ? $fName[1] : $fName[0];
            }

            $rights = $_SESSION['sbAuth']->getFieldRight($this->ident, $fields, array('view'), $categs, $user_fields);

            if(!$rights)
            {
                return array();
            }

            foreach($fields as $field)
            {
                if(sb_stripos($field, 'user_f_') === false || (isset($rights[$field]) && $rights[$field]['view'] == 1))
                {
                    $result[] = $field;
                }
            }
            $result[1] = 'cat_id'; //хардкод, первым индексом всегда идет id раздела

            return $result;
        }
        else
        {
            return $fields;
        }
    }

    /**
     * Возвращает доп. строку SQL-запроса для выборки элементов
     */
    private function getFilterFields($flds_type, $elemFields, $table)
    {
        $filter_flds = '';
        if(isset($_SESSION['sb_elems_filter'][$this->ident]) && count($_SESSION['sb_elems_filter'][$this->ident]) > 0)
        {
            $filter = array();
            foreach($_SESSION['sb_elems_filter'][$this->ident] as $key => $value)
            {
                if(isset($flds_type[$key]) && is_array($value))
                {
                    if(isset($value['lo']) && trim($value['lo']) != '')
                    {
                        $filter[$key.'_lo'] = $value['lo'].'|'.$flds_type[$key];
                    }
                    if(isset($value['hi']) && trim($value['hi']) != '')
                    {
                        $filter[$key.'_hi'] = $value['hi'].'|'.$flds_type[$key];
                    }
                }
                elseif(isset($flds_type[$key]) && $flds_type[$key] != 'link_sprav')
                {
                    $filter[$key] = $value.'|'.$flds_type[$key];
                }
                elseif(isset($flds_type[$key]) && $flds_type[$key] == 'link_sprav')
                {
                    if(isset($_SESSION['sb_elems_filter'][$this->ident][$key.'_link']))
                    {
                        $v = $value.','.$_SESSION['sb_elems_filter'][$this->ident][$key.'_link'];
                        $filter[$key] = $v.'|'.$flds_type[$key];
                    }
                }
                elseif(in_array($key, $elemFields))
                {
                    if(is_array($value))
                    {
                        if(isset($value['lo']) && trim($value['lo']) != '')
                        {
                            $filter[$key.'_lo'] = $value['lo'].'|number';
                        }
                        if(isset($value['hi']) && trim($value['hi']) != '')
                        {
                            $filter[$key.'_hi'] = $value['hi'].'|number';
                        }
                    }
                    else
                    {
                        $filter[$key] = $value.'|string';
                    }
                }
            }

            foreach($filter as $key => $value)
            {
                list($value, $type) = explode('|', $value);
                if ($value != '')
                {
                    switch ($type)
                    {
                        case 'number':
                            if (strpos($key, '_lo'))
                            {
                                $key = substr(str_replace('_lo', '', $key ), 0);
                                $filter_flds .= ' AND '.$table.'.'.$key.'>='.floatval($value);
                            }
                            elseif(strpos($key, '_hi'))
                            {
                                $key = substr(str_replace('_hi', '', $key), 0);
                                $filter_flds .= ' AND '.$table.'.'.$key.'<='.floatval($value);
                            }
                            break;
                        case 'string':
                            $key = substr(str_replace('_hi', '', $key), 0);
                            $filter_flds .= ' AND '.$table.'.'.$key.' LIKE '.$GLOBALS['sbSql']->escape('%'.$value.'%');
                            break;
                        case 'select':
                        case 'checkbox':
                            $key = substr($key, 0);
                            if ($value != - 1 && $value != '')
                                $filter_flds .= ' AND '.$table.'.' . $key . '="' . $value . '"';
                            break;
                        case 'multyselect':
                            $key = substr($key, 0);
                            if ($value != '')
                            {
                                $filter_flds .= ' AND '.$table.'.'.$key.' IN ('.$value.')';
                            }
                            break;
                        case 'date':
                            if (strpos($key, '_lo'))
                            {
                                $key = substr(str_replace('_lo', '', $key), 0);
                                $filter_flds .= ' AND '.$table.'.' . $key . '>=' . sb_datetoint($value);
                            }
                            elseif (strpos($key, '_hi'))
                            {
                                $key = substr(str_replace('_hi', '', $key), 0);
                                $filter_flds .= ' AND '.$table.'.'.$key.'<='.sb_datetoint($value);
                            }
                            break;
                        case 'link_sprav':
                            if(sb_substr_count($value, ',') > 0)
                            {
                                $filter_flds .= ' AND '.$table.'.'.$key.' = '.$GLOBALS['sbSql']->escape($value);
                            }
                            else
                            {
                                $filter_flds .= ' AND '.$table.'.'.$key.' LIKE '.$GLOBALS['sbSql']->escape('%'.$value);
                            }
                            break;
                    }
                }
            }
        }

        return $filter_flds;
    }

    public function setEditTpl($id)
    {
        $res = sql_param_assoc('SELECT seit_export, seit_title FROM sb_export_import_templates WHERE seit_id=?d AND seit_export <> ""', intval($id));
        if($res)
        {
            $this->editTplId = intval($id);
            $this->templateName = $res[0]['seit_title'];
            $this->xml = simplexml_load_string($res[0]['seit_export']);
        }
    }

    public function hasError()
    {
        return $this->hasError;
    }

    private function initOrderFields($fromXML = false) {
        if (sb_strpos($this->ident, 'pl_plugin_') !== false ) {
            $pluginInfo = $_SESSION['sbPlugins']->getPluginsInfo();
            if (isset($pluginInfo[$this->ident]) && $pluginInfo[$this->ident]['pm_settings']) {
                $this->pm_elems_settings = $pluginInfo[$this->ident]['pm_settings']['elems_settings'];

                if (isset($this->pm_elems_settings['show_goods']) && $this->pm_elems_settings['show_goods'] == 1) {
                    $fields = array(
                        'count_positions'	=> PL_EXPORT_EDIT_EXPORT_ORDER_ORDER_POSITIONS,
                        'count_goods'		=> PL_EXPORT_EDIT_EXPORT_ORDER_ORDER_GOODS,
                        'tovar_sum'			=> PL_EXPORT_EDIT_EXPORT_ORDER_TOVAR_SUM,
                        //'tovar_sum_discount' => PL_EXPORT_EDIT_EXPORT_ORDER_TOVAR_SUM_DISCOUNT
                    );
                    if ($fromXML) {
                        $this->orderFields = array();
                        if ($this->xml !== null) {
                            $tmp = $this->xml->xpath('elems_order/order_field');
                            if ($tmp !== null && is_array($tmp) && !empty($tmp)) {
                                foreach ($tmp as $key) {
                                    $key = (string)$key[0];
                                    $this->orderFields[$key] = $fields[$key];
                                }
                            }
                        }
                    } else {
                        $this->orderFields = $fields;
                    }
                }
            }
        }
    }

    private function getOrderDataCSV($root_xml) {
        if(!isset($this->pm_elems_settings) || !is_array($this->pm_elems_settings) || empty($this->pm_elems_settings) || $root_xml == '')
        {
            return false;
        }
        $root_xml = simplexml_load_string($root_xml);
        if(!$root_xml)
        {
            return false;
        }

        //уникальные id-шники модулей, товары которых есть в заказе.
        $xml = $root_xml->xpath('good/@pm_id');
        $plugin_ids = array();
        foreach($xml as $value)
        {
            $plugin_ids[] = (string) $value;
        }
        $plugin_ids = array_unique($plugin_ids);

        // нет id ни одного модуля = в заказе нет товаров
        if (count($plugin_ids) <= 0)
        {
            return false;
        }

        // суда будем собирать результат
        $ord_fields = array(
            'count_positions'=> 0,
            'count_goods'	=> 0,
            'tovar_sum'		=> 0
        );
        // ID цены по которой считается сумма заказа
        $price_id = $this->pm_elems_settings['cena'];

        // перебираем товары каждого модуля
        foreach($plugin_ids as $pl_value) {
            // все товары данного модуля
            $xml = $root_xml->xpath('good[@pm_id="'.$pl_value.'"]');
            // Количество позиций
            $ord_fields['count_positions'] += count($xml);
            foreach($xml as $ord_pos)
            {
                $el = $ord_pos->children();
                $ord_fields['count_goods']	+= $el->p_count;
                $ord_fields['tovar_sum']	+= (string) $el->p_count * (isset($el->{'p_price'.$price_id}) ? (string) $el->{'p_price'.$price_id} : 0);
            }
        }
        return $ord_fields;
    }

}